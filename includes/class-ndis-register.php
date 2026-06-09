<?php
/**
 * NDIS Commission provider-register auto-check.
 *
 * Given an organisation's NDIS Commission listing ID (the number after `?id=` in the
 * find-registered-provider URL, it is the Commission's Drupal node id), this reads the
 * provider's PUBLIC listing, parses the Registration status, the approved registration
 * groups and the "in force until" date, stores them, renders them as a table on the org
 * profile, and on a monthly cron re-reads each listing and ALERTS STAFF on any change.
 *
 * Design notes / decisions:
 * - There is NO official public JSON API for the Commission register, so this reads the
 *   server-rendered HTML of /node/{id} (which 301-redirects to the canonical slug page).
 *   Verified working server-side from the live host (plain wp_remote_get, browser UA).
 * - Reads the Commission's OWN public data, like the Google Business display, it is fine
 *   to show with attribution. The cadence is gentle (monthly + a short per-id cache).
 * - Parser is markup-targeted (Drupal field__label / field__item / ul.field-items). If the
 *   markup changes and parsing fails, we DO NOT overwrite stored values and we alert STAFF
 *   so a layout change never silently reads as "no change".
 * - Plugs into the existing `shuffles_ssj_ndis_scan` filter fired by Shuffles_SSJ_Org.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_NDIS_Register {

	const CRON_HOOK = 'sssj_ndis_monthly';
	const SCHEDULE  = 'sssj_monthly';

	/** @var Shuffles_SSJ_Settings|null */
	protected static $settings = null;

	public static function set_settings( $settings ) {
		self::$settings = $settings;
	}

	/* --------------------------------------------------------------- Boot */

	public static function register() {
		// Feed the existing on-save scan hook.
		add_filter( 'shuffles_ssj_ndis_scan', array( __CLASS__, 'scan_filter' ), 10, 3 );

		// Custom monthly schedule + the monthly scan event.
		add_filter( 'cron_schedules', array( __CLASS__, 'add_schedule' ) );
		add_action( self::CRON_HOOK, array( __CLASS__, 'cron_scan_all' ) );
		add_action( 'init', array( __CLASS__, 'maybe_schedule' ), 20 );

		// Admin "Re-scan now" action (manage_options).
		add_action( 'admin_post_sssj_ndis_scan_now', array( __CLASS__, 'handle_scan_now' ) );

		// Front-end "Scan now" preview (logged-in members, on the org/worker forms).
		add_action( 'wp_ajax_sssj_ndis_scan_preview', array( __CLASS__, 'ajax_scan_preview' ) );
	}

	/**
	 * AJAX: look up an NDIS Registration No and return the live details for preview (no store).
	 * Lets a member confirm their number on the form before saving.
	 */
	public static function ajax_scan_preview() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'msg' => __( 'Please log in first.', 'shuffles-social-services-jobs' ) ) );
		}
		check_ajax_referer( 'sssj_ndis_scan', 'nonce' );
		$id = isset( $_POST['id'] ) ? preg_replace( '/\D+/', '', (string) wp_unslash( $_POST['id'] ) ) : '';
		if ( '' === $id ) {
			wp_send_json_error( array( 'msg' => __( 'Enter your NDIS Registration No first.', 'shuffles-social-services-jobs' ) ) );
		}
		$res = self::scan( $id, false );
		if ( 'ok' !== $res['scan_state'] ) {
			$msg = ( 'fetch_fail' === $res['scan_state'] )
				? __( 'Could not reach the NDIS register just now, please try again shortly.', 'shuffles-social-services-jobs' )
				: __( 'That number didn’t return a registration. Please check it and try again.', 'shuffles-social-services-jobs' );
			wp_send_json_error( array( 'msg' => $msg ) );
		}
		wp_send_json_success(
			array(
				'status'         => $res['status'],
				'in_force_until' => $res['in_force_until'],
				'groups'         => array_values( $res['groups'] ),
				'legal_name'     => $res['legal_name'],
				'abn'            => $res['abn'],
				'address'        => $res['address'],
				'website'        => $res['website'],
				'outlets'        => array_values( $res['outlets'] ),
				'phone'          => $res['phone'],
				'tone'           => self::status_tone( $res['status'] ),
				'register_url'   => self::public_url( $id ),
			)
		);
	}

	public static function add_schedule( $schedules ) {
		if ( ! isset( $schedules[ self::SCHEDULE ] ) ) {
			$schedules[ self::SCHEDULE ] = array(
				'interval' => 30 * DAY_IN_SECONDS,
				'display'  => __( 'Once monthly (Shuffles NDIS check)', 'shuffles-social-services-jobs' ),
			);
		}
		return $schedules;
	}

	public static function maybe_schedule() {
		$scheduled = wp_next_scheduled( self::CRON_HOOK );
		if ( self::is_enabled() ) {
			if ( ! $scheduled ) {
				wp_schedule_event( time() + HOUR_IN_SECONDS, self::SCHEDULE, self::CRON_HOOK );
			}
		} elseif ( $scheduled ) {
			wp_unschedule_event( $scheduled, self::CRON_HOOK );
		}
	}

	/* --------------------------------------------------------------- Settings */

	protected static function setting( $key, $default = '' ) {
		if ( self::$settings && method_exists( self::$settings, 'get' ) ) {
			return self::$settings->get( $key, $default );
		}
		$all = get_option( 'shuffles_ssj_settings', array() );
		return ( is_array( $all ) && array_key_exists( $key, $all ) ) ? $all[ $key ] : $default;
	}

	public static function is_enabled() {
		return '1' === (string) self::setting( 'ndis_scan_enabled', '1' );
	}

	protected static function user_agent() {
		return apply_filters( 'shuffles_ssj_ndis_user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36' );
	}

	/* --------------------------------------------------------------- IDs / URLs */

	/** Digits-only register record id for an org (explicit field, else a numeric provider number). */
	public static function register_id_for( $org_id ) {
		$id = (string) get_post_meta( (int) $org_id, 'ndis_register_id', true );
		if ( '' === trim( $id ) ) {
			$pn = preg_replace( '/\D+/', '', (string) get_post_meta( (int) $org_id, 'ndis_provider_number', true ) );
			if ( strlen( $pn ) >= 3 ) {
				$id = $pn;
			}
		}
		return preg_replace( '/\D+/', '', (string) $id );
	}

	/** The entity's OWN stored ABN (org_abn for orgs, worker_abn for sole-trader workers), digits only. */
	public static function own_abn( $post_id ) {
		$key = ( 'sssj_worker' === get_post_type( (int) $post_id ) ) ? 'worker_abn' : 'org_abn';
		return preg_replace( '/\D+/', '', (string) get_post_meta( (int) $post_id, $key, true ) );
	}

	/** Canonical node URL we fetch (301-redirects to the slug page, which is server-rendered). */
	public static function fetch_url( $id ) {
		$base = apply_filters( 'shuffles_ssj_ndis_base_url', 'https://www.ndiscommission.gov.au/node/' );
		return $base . preg_replace( '/\D+/', '', (string) $id );
	}

	/** Public, human-facing register link (the search UI URL by id). */
	public static function public_url( $id ) {
		$id = preg_replace( '/\D+/', '', (string) $id );
		return $id
			? add_query_arg( 'id', $id, 'https://www.ndiscommission.gov.au/provider-registration/find-registered-provider' )
			: 'https://www.ndiscommission.gov.au/about/ndis-provider-register';
	}

	/* --------------------------------------------------------------- Fetch + parse */

	/**
	 * Fetch the listing HTML. Returns [ ok, code, body, error ]. Cached per id (short).
	 */
	public static function fetch( $id, $force = false ) {
		$id = preg_replace( '/\D+/', '', (string) $id );
		if ( '' === $id ) {
			return array( 'ok' => false, 'code' => 0, 'body' => '', 'error' => 'no_id' );
		}
		$tkey = 'sssj_ndis_' . $id;
		if ( ! $force ) {
			$cached = get_transient( $tkey );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}
		// wp_safe_remote_get blocks requests to private/internal hosts (SSRF guard); the target host is
		// fixed, but a redirect from upstream could otherwise be steered elsewhere, so cap redirects and
		// the response size too.
		$resp = wp_safe_remote_get(
			self::fetch_url( $id ),
			array(
				'timeout'             => 25,
				'redirection'         => 2,
				'limit_response_size' => 2 * MB_IN_BYTES,
				'user-agent'          => self::user_agent(),
				'headers'             => array( 'Accept' => 'text/html,application/xhtml+xml' ),
			)
		);
		if ( is_wp_error( $resp ) ) {
			$r = array( 'ok' => false, 'code' => 0, 'body' => '', 'error' => $resp->get_error_message() );
			set_transient( $tkey, $r, HOUR_IN_SECONDS );
			return $r;
		}
		$code = (int) wp_remote_retrieve_response_code( $resp );
		$body = (string) wp_remote_retrieve_body( $resp );
		$r    = array( 'ok' => ( 200 === $code && '' !== $body ), 'code' => $code, 'body' => $body, 'error' => '' );
		set_transient( $tkey, $r, $r['ok'] ? 6 * HOUR_IN_SECONDS : HOUR_IN_SECONDS );
		return $r;
	}

	/** Empty parse result. */
	protected static function empty_parse() {
		return array( 'parse_ok' => false, 'status' => '', 'status_class' => '', 'in_force_until' => '', 'in_force_iso' => '', 'groups' => array(), 'legal_name' => '', 'abn' => '', 'address' => '', 'website' => '', 'outlets' => array(), 'phone' => '' );
	}

	/**
	 * Parse the listing HTML into structured fields (markup-targeted, DOM/XPath).
	 */
	public static function parse( $html ) {
		$out = self::empty_parse();
		if ( '' === trim( (string) $html ) ) {
			return $out;
		}
		$prev = libxml_use_internal_errors( true );
		$dom  = new DOMDocument();
		$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );
		$xp = new DOMXPath( $dom );

		// Registration status, label "Registration status:" → adjacent field__item.
		$node = $xp->query( '//div[contains(@class,"field__label") and contains(normalize-space(.),"Registration status")]/following-sibling::div[contains(@class,"field__item")][1]' );
		if ( $node && $node->length ) {
			$el                  = $node->item( 0 );
			$out['status']       = trim( preg_replace( '/\s+/', ' ', $el->textContent ) );
			$out['status_class'] = $el instanceof DOMElement ? (string) $el->getAttribute( 'class' ) : '';
		}

		// Period of registration, the machine-readable <time datetime> + human text.
		$t = $xp->query( '//div[contains(@class,"field__label") and contains(normalize-space(.),"in force until")]/following-sibling::div[contains(@class,"field__item")][1]//time' );
		if ( $t && $t->length ) {
			$tel                   = $t->item( 0 );
			$out['in_force_until'] = trim( preg_replace( '/\s+/', ' ', $tel->textContent ) );
			if ( $tel instanceof DOMElement ) {
				$out['in_force_iso'] = (string) $tel->getAttribute( 'datetime' );
			}
		}

		// Approved registration groups, ul.field-items > li.
		$lis = $xp->query( '//div[contains(@class,"field__label") and contains(normalize-space(.),"Approved registration groups")]/following-sibling::ul[contains(@class,"field-items")][1]/li' );
		if ( $lis && $lis->length ) {
			foreach ( $lis as $li ) {
				$g = trim( preg_replace( '/\s+/', ' ', $li->textContent ) );
				if ( '' !== $g ) {
					$out['groups'][] = $g;
				}
			}
		}

		// Legal name (nice-to-have for display + drift detection).
		$ln = $xp->query( '//div[contains(@class,"field__label") and contains(normalize-space(.),"Legal name")]/following-sibling::div[contains(@class,"field__item")][1]' );
		if ( $ln && $ln->length ) {
			$out['legal_name'] = trim( preg_replace( '/\s+/', ' ', $ln->item( 0 )->textContent ) );
		}

		// ABN / Head office address / Website, targeted by the listing's own field-name classes
		// (more robust + language-independent than label text). &nbsp; → space.
		$item_text = function ( $field_class ) use ( $xp ) {
			$n = $xp->query( '//div[contains(@class,"' . $field_class . '")]//div[contains(@class,"field__item")][1]' );
			if ( $n && $n->length ) {
				return trim( preg_replace( '/\s+/', ' ', str_replace( "\xc2\xa0", ' ', $n->item( 0 )->textContent ) ) );
			}
			return '';
		};
		$out['abn']     = preg_replace( '/\D+/', '', $item_text( 'field--name-field-abn' ) );
		$out['address'] = $item_text( 'field--name-field-head-office-address' );
		// Website: prefer the link href, else the text.
		$wn = $xp->query( '//div[contains(@class,"field--name-field-website")]//div[contains(@class,"field__item")][1]//a/@href' );
		if ( $wn && $wn->length ) {
			$out['website'] = trim( (string) $wn->item( 0 )->nodeValue );
		} else {
			$out['website'] = $item_text( 'field--name-field-website' );
		}

		// Outlets (with phone), read from the listing's footer "Outlets" section. Authoritative
		// register data: surfaced read-only, never user-editable.
		$onodes = $xp->query( '//div[contains(@class,"paragraph--type--outlet")]' );
		if ( $onodes && $onodes->length ) {
			foreach ( $onodes as $on ) {
				$nm = '';
				$ph = '';
				$nn = $xp->query( './/div[contains(@class,"field--name-field-title")]', $on );
				if ( $nn && $nn->length ) {
					$nm = trim( preg_replace( '/\s+/', ' ', $nn->item( 0 )->textContent ) );
				}
				$pn = $xp->query( './/div[contains(@class,"field--name-field-phone")]//div[contains(@class,"field__item")][1]', $on );
				if ( $pn && $pn->length ) {
					$ph = trim( preg_replace( '/\s+/', ' ', $pn->item( 0 )->textContent ) );
				}
				if ( '' !== $nm || '' !== $ph ) {
					$out['outlets'][] = array( 'name' => $nm, 'phone' => $ph );
				}
			}
		}
		foreach ( $out['outlets'] as $o ) {
			if ( '' !== $o['phone'] ) {
				$out['phone'] = $o['phone'];
				break;
			}
		}

		// We consider the parse good only if we extracted a status (the critical field).
		$out['parse_ok'] = ( '' !== $out['status'] );
		return $out;
	}

	/** Fetch + parse → adds http + scan_state. */
	public static function scan( $id, $force = false ) {
		$f = self::fetch( $id, $force );
		if ( ! $f['ok'] ) {
			return array_merge( self::empty_parse(), array( 'scan_state' => 'fetch_fail', 'http' => $f['code'], 'error' => $f['error'] ) );
		}
		$p               = self::parse( $f['body'] );
		$p['http']       = $f['code'];
		$p['error']      = '';
		$p['scan_state'] = $p['parse_ok'] ? 'ok' : 'parse_fail';
		return $p;
	}

	/* --------------------------------------------------------------- Store */

	/** Stored groups as an array (prefers the JSON list, falls back to the comma string). */
	public static function stored_groups( $org_id ) {
		$json = (string) get_post_meta( (int) $org_id, 'ndis_groups_list', true );
		$arr  = json_decode( $json, true );
		if ( is_array( $arr ) ) {
			return array_values( array_filter( array_map( 'strval', $arr ) ) );
		}
		$csv = (string) get_post_meta( (int) $org_id, 'ndis_groups', true );
		if ( '' === trim( $csv ) ) {
			return array();
		}
		return array_values( array_filter( array_map( 'trim', explode( ',', $csv ) ) ) );
	}

	/**
	 * Scan a given org and store the result. Never overwrites good stored values on a
	 * fetch/parse failure (only updates last_scanned + scan_state in that case).
	 *
	 * @return array|null Scan result, or null if no register id is known.
	 */
	public static function scan_and_store( $org_id, $id = null, $force = false ) {
		$org_id = (int) $org_id;
		$id     = ( null !== $id && '' !== preg_replace( '/\D+/', '', (string) $id ) ) ? preg_replace( '/\D+/', '', (string) $id ) : self::register_id_for( $org_id );
		if ( '' === $id ) {
			return null;
		}
		$res = self::scan( $id, $force );
		update_post_meta( $org_id, 'ndis_last_scanned', current_time( 'mysql' ) );
		update_post_meta( $org_id, 'ndis_scan_state', $res['scan_state'] );
		if ( 'ok' === $res['scan_state'] ) {
			update_post_meta( $org_id, 'ndis_status', $res['status'] );
			update_post_meta( $org_id, 'ndis_groups', implode( ', ', $res['groups'] ) );
			update_post_meta( $org_id, 'ndis_groups_list', wp_json_encode( array_values( $res['groups'] ) ) );
			update_post_meta( $org_id, 'ndis_in_force_until', $res['in_force_until'] );
			if ( '' !== $res['legal_name'] ) {
				update_post_meta( $org_id, 'ndis_legal_name', $res['legal_name'] );
			}
			update_post_meta( $org_id, 'ndis_abn', $res['abn'] );
			update_post_meta( $org_id, 'ndis_address', $res['address'] );
			update_post_meta( $org_id, 'ndis_website', $res['website'] );
			// Outlets + phone, read from the register footer; authoritative, never user-editable.
			update_post_meta( $org_id, 'ndis_outlets', wp_json_encode( array_values( $res['outlets'] ) ) );
			update_post_meta( $org_id, 'ndis_phone', $res['phone'] );
		}
		return $res;
	}

	/* --------------------------------------------------------------- Hook: on-save scan */

	/**
	 * Fired via apply_filters('shuffles_ssj_ndis_scan', null, $number, $org_id) by
	 * Shuffles_SSJ_Org::on_ndis_recorded(). Returns the [status, groups] array that method
	 * expects (so the existing store path also fires), and does the richer store itself.
	 */
	public static function scan_filter( $pre, $number, $org_id ) {
		if ( ! self::is_enabled() ) {
			return $pre;
		}
		$res = self::scan_and_store( $org_id );
		if ( ! is_array( $res ) ) {
			// No stored register id, the passed number may itself be the id.
			$num = preg_replace( '/\D+/', '', (string) $number );
			if ( strlen( $num ) >= 3 ) {
				$res = self::scan_and_store( $org_id, $num );
			}
		}
		if ( is_array( $res ) && 'ok' === $res['scan_state'] ) {
			return array( 'status' => $res['status'], 'groups' => implode( ', ', $res['groups'] ) );
		}
		return $pre;
	}

	/* --------------------------------------------------------------- Monthly cron + alerts */

	public static function cron_scan_all() {
		if ( ! self::is_enabled() ) {
			return;
		}
		// Organisations AND NDIS-registered sole traders (individual worker profiles).
		$orgs = get_posts(
			array(
				'post_type'      => array( 'sssj_org', 'sssj_worker' ),
				'post_status'    => 'publish',
				'posts_per_page' => 1000,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					'relation' => 'OR',
					array( 'key' => 'ndis_register_id', 'compare' => 'EXISTS' ),
					array( 'key' => 'ndis_provider_number', 'compare' => 'EXISTS' ),
				),
			)
		);
		$checked = 0;
		$failed  = 0;
		foreach ( $orgs as $oid ) {
			$id = self::register_id_for( $oid );
			if ( '' === $id ) {
				continue;
			}
			$checked++;
			$old = array(
				'status'   => (string) get_post_meta( $oid, 'ndis_status', true ),
				'in_force' => (string) get_post_meta( $oid, 'ndis_in_force_until', true ),
				'groups'   => self::stored_groups( $oid ),
				'state'    => (string) get_post_meta( $oid, 'ndis_scan_state', true ),
			);
			$res = self::scan_and_store( $oid, $id, true );
			if ( ! is_array( $res ) ) {
				continue;
			}
			if ( 'ok' === $res['scan_state'] ) {
				self::maybe_alert_change( $oid, $old, $res );
			} else {
				$failed++;
				if ( 'ok' === $old['state'] ) {
					// Was readable before, now isn't → likely a markup change or the listing moved.
					self::alert_failure( $oid, $res['scan_state'] );
				}
			}
		}
		if ( class_exists( 'Shuffles_SSJ_Cron_Monitor' ) ) {
			Shuffles_SSJ_Cron_Monitor::record_note(
				self::CRON_HOOK,
				sprintf( __( '%1$d listing(s) checked, %2$d could not be read.', 'shuffles-social-services-jobs' ), $checked, $failed ),
				$failed > 0 ? 'failed' : 'ok'
			);
		}
	}

	/** Compare old vs new and, on a meaningful change, alert staff. */
	protected static function maybe_alert_change( $org_id, $old, $new ) {
		// First successful read just sets the baseline, no alert.
		if ( 'ok' !== $old['state'] ) {
			return;
		}
		$changes  = array();
		$new_grp  = array_values( $new['groups'] );
		$added    = array_values( array_diff( $new_grp, $old['groups'] ) );
		$removed  = array_values( array_diff( $old['groups'], $new_grp ) );

		if ( 0 !== strcasecmp( trim( (string) $old['status'] ), trim( (string) $new['status'] ) ) ) {
			$changes[] = sprintf( __( 'Status: %1$s → %2$s', 'shuffles-social-services-jobs' ), $old['status'] ? $old['status'] : '-', $new['status'] ? $new['status'] : '-' );
		}
		if ( trim( (string) $old['in_force'] ) !== trim( (string) $new['in_force_until'] ) ) {
			$changes[] = sprintf( __( 'In force until: %1$s → %2$s', 'shuffles-social-services-jobs' ), $old['in_force'] ? $old['in_force'] : '-', $new['in_force_until'] ? $new['in_force_until'] : '-' );
		}
		if ( $added ) {
			$changes[] = sprintf( __( 'Groups added: %s', 'shuffles-social-services-jobs' ), implode( ', ', $added ) );
		}
		if ( $removed ) {
			$changes[] = sprintf( __( 'Groups removed: %s', 'shuffles-social-services-jobs' ), implode( ', ', $removed ) );
		}
		if ( ! $changes ) {
			return;
		}

		// Let an integration own the notification (FluentCRM automation, Reference Check, etc.).
		do_action( 'shuffles_ssj_ndis_changed', $org_id, $changes, $old, $new );

		if ( apply_filters( 'shuffles_ssj_ndis_suppress_default_email', false, $org_id, $changes ) ) {
			return;
		}
		$to = self::alert_recipient( $org_id );
		if ( ! $to ) {
			return;
		}
		$name    = get_the_title( $org_id );
		$subject = sprintf( __( '[%1$s] NDIS registration changed: %2$s', 'shuffles-social-services-jobs' ), wp_specialchars_decode( get_bloginfo( 'name' ) ), $name );
		$lines   = array(
			sprintf( __( 'The NDIS registration details changed for: %s', 'shuffles-social-services-jobs' ), $name ),
			'',
			implode( "\n", array_map( static function ( $c ) { return '• ' . $c; }, $changes ) ),
			'',
			sprintf( __( 'Public register listing: %s', 'shuffles-social-services-jobs' ), self::public_url( self::register_id_for( $org_id ) ) ),
			sprintf( __( 'Edit organisation: %s', 'shuffles-social-services-jobs' ), get_edit_post_link( $org_id, '' ) ),
			'',
			__( 'Please re-verify this provider. (This alert goes to staff only, the provider was not notified.)', 'shuffles-social-services-jobs' ),
		);
		wp_mail( $to, $subject, implode( "\n", $lines ) );
	}

	/** Alert staff that a previously-readable listing can no longer be parsed/fetched. */
	protected static function alert_failure( $org_id, $state ) {
		if ( apply_filters( 'shuffles_ssj_ndis_suppress_default_email', false, $org_id, array() ) ) {
			return;
		}
		$to = self::alert_recipient( $org_id );
		if ( ! $to ) {
			return;
		}
		$name    = get_the_title( $org_id );
		$reason  = ( 'parse_fail' === $state )
			? __( 'the listing page loaded but its details could not be read (the register page layout may have changed)', 'shuffles-social-services-jobs' )
			: __( 'the listing page could not be loaded', 'shuffles-social-services-jobs' );
		$subject = sprintf( __( '[%1$s] NDIS auto-check needs attention: %2$s', 'shuffles-social-services-jobs' ), wp_specialchars_decode( get_bloginfo( 'name' ) ), $name );
		$body    = sprintf( __( "The monthly NDIS register check for %1\$s could not complete because %2\$s.\n\nThe previously stored details were kept unchanged. Please check the listing manually: %3\$s", 'shuffles-social-services-jobs' ), $name, $reason, self::public_url( self::register_id_for( $org_id ) ) );
		wp_mail( $to, $subject, $body );
	}

	protected static function alert_recipient( $org_id ) {
		$to = (string) self::setting( 'ndis_alert_email', '' );
		if ( '' === trim( $to ) ) {
			$to = (string) get_option( 'admin_email' );
		}
		$to = apply_filters( 'shuffles_ssj_ndis_alert_email', $to, $org_id );
		return is_email( $to ) ? $to : '';
	}

	/* --------------------------------------------------------------- Admin re-scan */

	public static function handle_scan_now() {
		$org_id = isset( $_GET['org'] ) ? (int) $_GET['org'] : 0;
		if ( ! current_user_can( 'manage_options' ) || ! $org_id ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_ndis_scan_now_' . $org_id );
		$res    = self::scan_and_store( $org_id, null, true );
		$state  = is_array( $res ) ? $res['scan_state'] : 'no_id';
		$back   = wp_get_referer();
		$back   = $back ? $back : get_permalink( $org_id );
		wp_safe_redirect( add_query_arg( 'sssj_ndis', rawurlencode( $state ), $back ? $back : home_url( '/' ) ) );
		exit;
	}

	/* --------------------------------------------------------------- Render */

	/**
	 * Colour bucket for a status word. Returns 'verified' (green), 'rejected' (red), or ''.
	 * NEGATIVE words are checked FIRST so "Registration revoked" / "Banned" go red, not green
	 * (it contains "registration", which would otherwise match the positive set).
	 */
	public static function status_tone( $status ) {
		$s = strtolower( (string) $status );
		if ( false !== strpos( $s, 'revok' ) || false !== strpos( $s, 'ban' ) || false !== strpos( $s, 'suspend' ) || false !== strpos( $s, 'cancel' ) || false !== strpos( $s, 'expir' ) || false !== strpos( $s, 'surrender' ) || false !== strpos( $s, 'reject' ) ) {
			return 'rejected';
		}
		if ( false !== strpos( $s, 'approv' ) || false !== strpos( $s, 'register' ) || false !== strpos( $s, 'active' ) ) {
			return 'verified';
		}
		return '';
	}

	/**
	 * Neat HTML table of the stored NDIS register details for an org.
	 * Returns '' when the org is not flagged registered and has no register id.
	 */
	public static function status_table_html( $org_id ) {
		$org_id = (int) $org_id;
		$id     = self::register_id_for( $org_id );
		$flagged = '1' === (string) get_post_meta( $org_id, 'ndis_registered', true );
		if ( '' === $id && ! $flagged ) {
			return '';
		}
		$status   = (string) get_post_meta( $org_id, 'ndis_status', true );
		$in_force = (string) get_post_meta( $org_id, 'ndis_in_force_until', true );
		$groups   = self::stored_groups( $org_id );
		$scanned  = (string) get_post_meta( $org_id, 'ndis_last_scanned', true );
		$state    = (string) get_post_meta( $org_id, 'ndis_scan_state', true );
		$tone     = self::status_tone( $status );

		$out  = '<div class="sssj-ndis">';
		$out .= '<h3 class="sssj-ndis__title">' . esc_html__( 'NDIS provider registration', 'shuffles-social-services-jobs' ) . '</h3>';
		$out .= '<table class="sssj-ndis__table"><tbody>';

		$out .= '<tr><th scope="row">' . esc_html__( 'Registration status', 'shuffles-social-services-jobs' ) . '</th><td>';
		if ( '' !== $status ) {
			$out .= '<span class="sssj-badge ' . ( $tone ? 'sssj-badge--' . esc_attr( $tone ) : '' ) . '">' . esc_html( $status ) . '</span>';
		} else {
			$out .= '<span class="sssj-badge">' . esc_html__( 'Pending verification', 'shuffles-social-services-jobs' ) . '</span>';
		}
		$out .= '</td></tr>';

		if ( '' !== $in_force ) {
			$out .= '<tr><th scope="row">' . esc_html__( 'In force until', 'shuffles-social-services-jobs' ) . '</th><td>' . esc_html( $in_force ) . '</td></tr>';
		}

		// Details read from the listing: legal name, ABN (with mismatch flag), address, website.
		$legal = (string) get_post_meta( $org_id, 'ndis_legal_name', true );
		$nabn  = preg_replace( '/\D+/', '', (string) get_post_meta( $org_id, 'ndis_abn', true ) );
		$addr  = (string) get_post_meta( $org_id, 'ndis_address', true );
		$web   = (string) get_post_meta( $org_id, 'ndis_website', true );
		$own   = self::own_abn( $org_id );
		if ( '' !== $legal ) {
			$out .= '<tr><th scope="row">' . esc_html__( 'Legal name (register)', 'shuffles-social-services-jobs' ) . '</th><td>' . esc_html( $legal ) . '</td></tr>';
		}
		if ( '' !== $nabn ) {
			$out .= '<tr><th scope="row">' . esc_html__( 'ABN (register)', 'shuffles-social-services-jobs' ) . '</th><td><code>' . esc_html( $nabn ) . '</code>';
			if ( '' !== $own && $own !== $nabn ) {
				$out .= '<div class="sssj-ndis__abnwarn">' . esc_html( sprintf( __( '⚠ This differs from the ABN on file (%s), please check.', 'shuffles-social-services-jobs' ), $own ) ) . '</div>';
			}
			$out .= '</td></tr>';
		}
		if ( '' !== $addr ) {
			$out .= '<tr><th scope="row">' . esc_html__( 'Head office (register)', 'shuffles-social-services-jobs' ) . '</th><td>' . esc_html( $addr ) . '</td></tr>';
		}
		if ( '' !== $web ) {
			$out .= '<tr><th scope="row">' . esc_html__( 'Website (register)', 'shuffles-social-services-jobs' ) . '</th><td><a href="' . esc_url( $web ) . '" target="_blank" rel="noopener nofollow">' . esc_html( preg_replace( '#^https?://#', '', $web ) ) . '</a></td></tr>';
		}
		// Phone + outlets, read from the register footer (authoritative, not user-editable).
		$phone   = (string) get_post_meta( $org_id, 'ndis_phone', true );
		$outlets = json_decode( (string) get_post_meta( $org_id, 'ndis_outlets', true ), true );
		$outlets = is_array( $outlets ) ? $outlets : array();
		if ( '' !== $phone ) {
			$out .= '<tr><th scope="row">' . esc_html__( 'Phone (register)', 'shuffles-social-services-jobs' ) . '</th><td><a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ) . '">' . esc_html( $phone ) . '</a></td></tr>';
		}
		if ( $outlets ) {
			$lis = array();
			foreach ( $outlets as $o ) {
				$nm = isset( $o['name'] ) ? (string) $o['name'] : '';
				$ph = isset( $o['phone'] ) ? (string) $o['phone'] : '';
				if ( '' === $nm && '' === $ph ) {
					continue;
				}
				$lis[] = '<li>' . esc_html( $nm ) . ( $ph ? ', ' . esc_html( $ph ) : '' ) . '</li>';
			}
			if ( $lis ) {
				$total = count( $lis );
				$limit = 5;
				// Show the first few outlets; collapse the rest behind a native (no-JS) "show all" toggle.
				$cell = '<ul class="sssj-ndis__outlets">' . implode( '', array_slice( $lis, 0, $limit ) ) . '</ul>';
				if ( $total > $limit ) {
					$cell .= '<details class="sssj-ndis__outlets-more"><summary>'
						. esc_html( sprintf( __( 'Show all %d outlets', 'shuffles-social-services-jobs' ), $total ) )
						. '</summary><ul class="sssj-ndis__outlets">' . implode( '', array_slice( $lis, $limit ) ) . '</ul></details>';
				}
				$out .= '<tr><th scope="row">' . esc_html( sprintf( _n( 'Outlet', 'Outlets (%d)', $total, 'shuffles-social-services-jobs' ), $total ) ) . '</th><td>' . $cell . '</td></tr>';
			}
		}

		if ( $groups ) {
			$chips = '';
			foreach ( $groups as $g ) {
				$chips .= '<li>' . esc_html( $g ) . '</li>';
			}
			$out .= '<tr><th scope="row">' . esc_html__( 'Approved registration groups', 'shuffles-social-services-jobs' ) . '</th><td><ul class="sssj-ndis__groups">' . $chips . '</ul></td></tr>';
		}

		$out .= '</tbody></table>';

		// Provenance + register link.
		$out .= '<p class="sssj-ndis__meta">';
		if ( '' !== $id ) {
			$out .= '<a class="sssj-link" href="' . esc_url( self::public_url( $id ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View on the NDIS Commission register', 'shuffles-social-services-jobs' ) . '</a>';
		}
		if ( '' !== $scanned ) {
			$out .= ' <span class="sssj-ndis__checked">' . esc_html( sprintf( __( 'Last checked %s', 'shuffles-social-services-jobs' ), mysql2date( get_option( 'date_format' ), $scanned ) ) ) . '</span>';
		}
		$out .= '</p>';

		// Honest note if we couldn't confirm automatically and have nothing stored.
		if ( '' === $status && in_array( $state, array( 'fetch_fail', 'parse_fail' ), true ) ) {
			$out .= '<p class="sssj-ndis__note">' . esc_html__( 'We could not confirm this automatically, an administrator will verify it.', 'shuffles-social-services-jobs' ) . '</p>';
		}

		// Admin-only re-scan control.
		if ( current_user_can( 'manage_options' ) && '' !== $id ) {
			if ( function_exists( 'wp_enqueue_script' ) ) {
				wp_enqueue_script( 'sssj-spinner' );
			}
			$url = wp_nonce_url( admin_url( 'admin-post.php?action=sssj_ndis_scan_now&org=' . $org_id ), 'sssj_ndis_scan_now_' . $org_id );
			$out .= '<p class="sssj-ndis__admin"><a class="sssj-btn sssj-btn--ghost sssj-btn--sm" data-sssj-busy="' . esc_attr__( 'Re-checking the NDIS register…', 'shuffles-social-services-jobs' ) . '" href="' . esc_url( $url ) . '">' . esc_html__( 'Re-check NDIS register now', 'shuffles-social-services-jobs' ) . '</a>';
			if ( '' !== $state && 'ok' !== $state ) {
				$out .= ' <span class="sssj-ndis__state">' . esc_html( sprintf( __( '(last check: %s)', 'shuffles-social-services-jobs' ), $state ) ) . '</span>';
			}
			$out .= '</p>';
		}

		$out .= '</div>';
		return $out;
	}
}
