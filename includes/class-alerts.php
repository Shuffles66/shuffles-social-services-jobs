<?php
/**
 * Email alerts (digest). Three opt-in alert types, all sent by a daily cron:
 *   1. Worker → new JOBS that match their profile (services/area/basis).      [user meta sssj_alert_jobs]
 *   2. Advertiser → new CANDIDATES (worker profiles) matching their job.        [job meta sssj_alert_candidates]
 *   3. Saved searches → new listings matching a search the member saved.        [user meta _sssj_saved_searches]
 *
 * "New" = published since the last alert (per user / per job / per saved search). Matching reuses
 * Shuffles_SSJ_Matcher. Email goes via wp_mail (FluentSMTP transport) unless a FluentCRM automation
 * claims it through the shuffles_ssj_alert_suppress_default filter / shuffles_ssj_alert_sent action.
 * Nothing is sent unless the master switch is on AND the recipient opted in.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Alerts {

	const CRON   = 'sssj_alerts_daily';
	const SEARCH = '_sssj_saved_searches';

	/** @var Shuffles_SSJ_Settings */
	private $settings;

	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	public function register() {
		add_action( self::CRON, array( $this, 'run' ) );
		if ( ! wp_next_scheduled( self::CRON ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON );
		}
		// Worker job-alert opt-in saved with the worker profile.
		add_action( 'shuffles_ssj_profile_saved', array( $this, 'save_worker_optin' ), 15, 3 );
		add_shortcode( 'sssj_saved_searches', array( $this, 'saved_searches_panel' ) );
		add_action( 'admin_post_sssj_save_search', array( $this, 'handle_save_search' ) );
		add_action( 'admin_post_sssj_del_search', array( $this, 'handle_del_search' ) );
		add_action( 'admin_post_sssj_alerts_run_now', array( $this, 'handle_run_now' ) );
	}

	/** Master switch (default on) + a mailer present. */
	public function available() {
		return '1' === (string) $this->settings->get( 'alerts_enabled', '1' );
	}

	private function default_since() {
		return gmdate( 'Y-m-d H:i:s', time() - 7 * DAY_IN_SECONDS );
	}

	/* --------------------------------------------------------------- Opt-in capture */

	/** Save the worker's "email me new matching jobs" choice (fires on worker profile save). */
	public function save_worker_optin( $entity, $post_id, $user_id ) {
		if ( 'worker' !== $entity || ! $user_id ) {
			return;
		}
		if ( empty( $_POST['alert_jobs'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			delete_user_meta( $user_id, 'sssj_alert_jobs' );
		} else {
			update_user_meta( $user_id, 'sssj_alert_jobs', '1' );
		}
	}

	/* --------------------------------------------------------------- Cron */

	public function run() {
		if ( ! $this->available() ) {
			return;
		}
		$this->run_worker_job_alerts();
		$this->run_advertiser_candidate_alerts();
		$this->run_saved_search_alerts();
	}

	private function run_worker_job_alerts() {
		$users = get_users( array( 'meta_key' => 'sssj_alert_jobs', 'meta_value' => '1', 'fields' => 'ids' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		foreach ( $users as $uid ) {
			$w = get_posts( array( 'post_type' => 'sssj_worker', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => true, 'meta_key' => 'worker_user_id', 'meta_value' => $uid ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
			if ( ! $w ) {
				continue;
			}
			$last  = (string) get_user_meta( $uid, 'sssj_alert_jobs_last', true );
			$since = $last ? $last : $this->default_since();
			$items = array();
			if ( class_exists( 'Shuffles_SSJ_Matcher' ) ) {
				foreach ( Shuffles_SSJ_Matcher::jobs_for_worker( (int) $w[0], 10, $since ) as $m ) {
					$items[] = array( 'title' => get_the_title( $m['id'] ), 'url' => get_permalink( $m['id'] ) );
				}
			}
			if ( $items ) {
				$this->send_digest( $uid, __( 'New jobs matching your profile', 'shuffles-social-services-jobs' ), __( 'New roles match your services and area:', 'shuffles-social-services-jobs' ), $items, 'worker_jobs' );
			}
			update_user_meta( $uid, 'sssj_alert_jobs_last', current_time( 'mysql' ) );
		}
	}

	private function run_advertiser_candidate_alerts() {
		$jobs = get_posts( array( 'post_type' => 'sssj_job', 'post_status' => 'publish', 'posts_per_page' => 300, 'fields' => 'ids', 'no_found_rows' => true, 'meta_key' => 'sssj_alert_candidates', 'meta_value' => '1' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		foreach ( $jobs as $jid ) {
			$author = (int) get_post_field( 'post_author', $jid );
			if ( ! $author ) {
				continue;
			}
			$last  = (string) get_post_meta( $jid, 'sssj_alert_candidates_last', true );
			$since = $last ? $last : $this->default_since();
			$items = array();
			if ( class_exists( 'Shuffles_SSJ_Matcher' ) ) {
				foreach ( Shuffles_SSJ_Matcher::workers_for_job( (int) $jid, 10, $since ) as $m ) {
					$items[] = array( 'title' => get_the_title( $m['id'] ), 'url' => get_permalink( $m['id'] ) );
				}
			}
			if ( $items ) {
				/* translators: %s: job title */
				$this->send_digest( $author, sprintf( __( 'New candidates for “%s”', 'shuffles-social-services-jobs' ), get_the_title( $jid ) ), __( 'New worker profiles match this role:', 'shuffles-social-services-jobs' ), $items, 'advertiser_candidates' );
			}
			update_post_meta( $jid, 'sssj_alert_candidates_last', current_time( 'mysql' ) );
		}
	}

	private function run_saved_search_alerts() {
		$users = get_users( array( 'meta_key' => self::SEARCH, 'fields' => 'ids' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		foreach ( $users as $uid ) {
			$searches = get_user_meta( $uid, self::SEARCH, true );
			if ( ! is_array( $searches ) || ! $searches ) {
				continue;
			}
			$changed = false;
			foreach ( $searches as $i => $s ) {
				if ( empty( $s['alert'] ) ) {
					continue;
				}
				$since = ! empty( $s['last'] ) ? $s['last'] : $this->default_since();
				$items = $this->run_saved_query( $s, $since );
				if ( $items ) {
					/* translators: %s: saved-search label */
					$this->send_digest( $uid, sprintf( __( 'New results for your saved search: %s', 'shuffles-social-services-jobs' ), $s['label'] ), __( 'New listings match your saved search:', 'shuffles-social-services-jobs' ), $items, 'saved_search' );
				}
				$searches[ $i ]['last'] = current_time( 'mysql' );
				$changed                = true;
			}
			if ( $changed ) {
				update_user_meta( $uid, self::SEARCH, $searches );
			}
		}
	}

	/** Re-run a saved search for items newer than $since. Returns [ {title,url}, ... ]. */
	private function run_saved_query( $s, $since ) {
		$map = array( 'jobs' => 'sssj_job', 'workers' => 'sssj_worker', 'orgs' => 'sssj_org', 'needs' => 'sssj_need' );
		$pt  = isset( $map[ $s['type'] ] ) ? $map[ $s['type'] ] : 'sssj_job';
		$q   = is_array( $s['query'] ?? null ) ? $s['query'] : array();
		$args = array(
			'post_type'      => $pt,
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'date_query'     => array( array( 'after' => $since, 'inclusive' => false ) ),
		);
		if ( ! empty( $q['q'] ) ) {
			$args['s'] = sanitize_text_field( $q['q'] );
		}
		$tax = array();
		if ( ! empty( $q['cat'] ) ) {
			$tax[] = array( 'taxonomy' => 'sssjt_category', 'field' => 'slug', 'terms' => array_map( 'sanitize_title', (array) $q['cat'] ) );
		}
		if ( ! empty( $q['sector'] ) ) {
			$tax[] = array( 'taxonomy' => 'sssjt_category', 'field' => 'slug', 'terms' => sanitize_title( $q['sector'] ) );
		}
		if ( ! empty( $q['support'] ) ) {
			$tax[] = array( 'taxonomy' => 'sssjt_support_category', 'field' => 'slug', 'terms' => sanitize_title( $q['support'] ) );
		}
		if ( ! empty( $q['funding'] ) ) {
			$tax[] = array( 'taxonomy' => 'sssjt_funding_source', 'field' => 'slug', 'terms' => sanitize_title( $q['funding'] ) );
		}
		if ( $tax ) {
			if ( count( $tax ) > 1 ) {
				$tax['relation'] = 'AND';
			}
			$args['tax_query'] = $tax; // phpcs:ignore WordPress.DB.SlowDBQuery
		}
		if ( 'sssj_worker' === $pt ) {
			$args['meta_query'] = array( array( 'key' => 'visibility', 'value' => array( 'public', 'logged_in' ), 'compare' => 'IN' ) );
		} elseif ( 'sssj_org' === $pt ) {
			$args['meta_query'] = array( 'relation' => 'OR', array( 'key' => 'org_hidden', 'compare' => 'NOT EXISTS' ), array( 'key' => 'org_hidden', 'value' => '1', 'compare' => '!=' ) );
		}
		$ids   = ( new WP_Query( $args ) )->posts;
		$items = array();
		foreach ( $ids as $id ) {
			$items[] = array( 'title' => get_the_title( $id ), 'url' => ( 'sssj_need' === $pt ) ? '' : get_permalink( $id ) );
		}
		return $items;
	}

	/* --------------------------------------------------------------- Email */

	private function send_digest( $user_id, $subject, $intro, $items, $type ) {
		$user = get_userdata( $user_id );
		if ( ! $user || ! is_email( $user->user_email ) || ! $items ) {
			return;
		}
		$lines = array();
		foreach ( $items as $it ) {
			$lines[] = $it['url'] ? ( '• ' . $it['title'] . ' — ' . $it['url'] ) : ( '• ' . $it['title'] );
		}
		$body = $intro . "\n\n" . implode( "\n", $lines ) . "\n\n"
			/* translators: %s: site name */
			. sprintf( __( 'You receive this because you turned on alerts on %s. Manage your alerts from your dashboard.', 'shuffles-social-services-jobs' ), get_bloginfo( 'name' ) );

		if ( ! apply_filters( 'shuffles_ssj_alert_suppress_default', false, $user_id, $type, $items ) ) {
			wp_mail( $user->user_email, $subject, $body );
		}
		do_action( 'shuffles_ssj_alert_sent', $user_id, $type, $items, $subject );
	}

	/* --------------------------------------------------------------- Saved-search UI */

	/** Filter params we capture for a saved search, per directory type. */
	private static function capture_params() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$g = $_GET;
		$out = array();
		if ( ! empty( $g['sssj_q'] ) ) { $out['q'] = sanitize_text_field( wp_unslash( $g['sssj_q'] ) ); }
		if ( ! empty( $g['sssj_cat'] ) ) { $out['cat'] = array_map( 'sanitize_title', (array) wp_unslash( $g['sssj_cat'] ) ); }
		if ( ! empty( $g['sssj_sector'] ) ) { $out['sector'] = sanitize_title( wp_unslash( $g['sssj_sector'] ) ); }
		if ( ! empty( $g['sssj_support'] ) ) { $out['support'] = sanitize_title( wp_unslash( $g['sssj_support'] ) ); }
		if ( ! empty( $g['sssj_funding'] ) ) { $out['funding'] = sanitize_title( wp_unslash( $g['sssj_funding'] ) ); }
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		return $out;
	}

	/** "Save this search & alert me" button for a directory ($type: jobs|workers|orgs|needs). */
	public static function save_search_button( $type ) {
		if ( ! is_user_logged_in() ) {
			return;
		}
		$params = self::capture_params();
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="sssj-savesearch">';
		echo '<input type="hidden" name="action" value="sssj_save_search" />';
		echo '<input type="hidden" name="type" value="' . esc_attr( $type ) . '" />';
		echo '<input type="hidden" name="query" value="' . esc_attr( wp_json_encode( $params ) ) . '" />';
		echo '<input type="hidden" name="redirect" value="' . esc_attr( ( is_ssl() ? 'https://' : 'http://' ) . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) ) . sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ) ) . '" />';
		wp_nonce_field( 'sssj_save_search', '_sssj_ss' );
		echo '<button type="submit" class="sssj-btn sssj-btn--ghost sssj-btn--sm" title="' . esc_attr__( 'Save this search and email me new matches', 'shuffles-social-services-jobs' ) . '">🔔 ' . esc_html__( 'Save & alert me', 'shuffles-social-services-jobs' ) . '</button>';
		echo '</form>';
	}

	public function handle_save_search() {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Please log in.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_save_search', '_sssj_ss' );
		$uid   = get_current_user_id();
		$type  = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'jobs';
		$query = isset( $_POST['query'] ) ? json_decode( wp_unslash( $_POST['query'] ), true ) : array(); // phpcs:ignore WordPress.Security
		$query = is_array( $query ) ? array_map( 'wp_kses_post', wp_unslash( $query ) ) : array();
		$label = $this->label_for( $type, $query );

		$searches   = get_user_meta( $uid, self::SEARCH, true );
		$searches   = is_array( $searches ) ? $searches : array();
		$searches[] = array( 'id' => uniqid( 's', false ), 'type' => $type, 'label' => $label, 'query' => $query, 'alert' => 1, 'last' => current_time( 'mysql' ) );
		update_user_meta( $uid, self::SEARCH, $searches );

		$redirect = isset( $_POST['redirect'] ) ? esc_url_raw( wp_unslash( $_POST['redirect'] ) ) : home_url();
		wp_safe_redirect( add_query_arg( 'sssj_saved', '1', $redirect ) );
		exit;
	}

	public function handle_del_search() {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Please log in.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_del_search', '_sssj_ss' );
		$uid = get_current_user_id();
		$id  = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
		$searches = get_user_meta( $uid, self::SEARCH, true );
		$searches = is_array( $searches ) ? $searches : array();
		$searches = array_values( array_filter( $searches, function ( $s ) use ( $id ) { return ( $s['id'] ?? '' ) !== $id; } ) );
		update_user_meta( $uid, self::SEARCH, $searches );
		$redirect = isset( $_POST['redirect'] ) ? esc_url_raw( wp_unslash( $_POST['redirect'] ) ) : home_url();
		wp_safe_redirect( $redirect );
		exit;
	}

	private function label_for( $type, $query ) {
		$labels = array( 'jobs' => __( 'Jobs', 'shuffles-social-services-jobs' ), 'workers' => __( 'Workers', 'shuffles-social-services-jobs' ), 'orgs' => __( 'Organisations', 'shuffles-social-services-jobs' ), 'needs' => __( 'Participant requests', 'shuffles-social-services-jobs' ) );
		$bits   = array( isset( $labels[ $type ] ) ? $labels[ $type ] : ucfirst( $type ) );
		if ( ! empty( $query['q'] ) ) {
			$bits[] = '“' . $query['q'] . '”';
		}
		foreach ( array( 'cat', 'sector', 'support', 'funding' ) as $k ) {
			if ( ! empty( $query[ $k ] ) ) {
				$bits[] = is_array( $query[ $k ] ) ? implode( '/', $query[ $k ] ) : $query[ $k ];
			}
		}
		return implode( ' · ', $bits );
	}

	/** [sssj_saved_searches] — manage saved searches (member dashboard). */
	public function saved_searches_panel( $atts ) {
		wp_enqueue_style( 'sssj' );
		if ( ! is_user_logged_in() ) {
			return '<div class="sssj"><div class="sssj-panel"><p>' . esc_html__( 'Log in to manage your saved searches.', 'shuffles-social-services-jobs' ) . '</p></div></div>';
		}
		$uid      = get_current_user_id();
		$searches = get_user_meta( $uid, self::SEARCH, true );
		$searches = is_array( $searches ) ? $searches : array();
		$redirect = ( is_ssl() ? 'https://' : 'http://' ) . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) ) . sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ); // phpcs:ignore
		ob_start();
		echo '<div class="sssj sssj--saved"><div class="sssj-panel"><h3 style="margin-top:0">🔔 ' . esc_html__( 'Your saved searches', 'shuffles-social-services-jobs' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'We email you when new listings match these searches. Save a search from any directory using the “Save & alert me” button.', 'shuffles-social-services-jobs' ) . '</p>';
		if ( ! $searches ) {
			echo '<p>' . esc_html__( 'You have no saved searches yet.', 'shuffles-social-services-jobs' ) . '</p>';
		} else {
			echo '<table class="sssj-tests__table" style="width:100%">';
			foreach ( $searches as $s ) {
				echo '<tr><td><strong>' . esc_html( $s['label'] ) . '</strong></td><td style="text-align:right">';
				echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline">';
				echo '<input type="hidden" name="action" value="sssj_del_search" /><input type="hidden" name="id" value="' . esc_attr( $s['id'] ?? '' ) . '" /><input type="hidden" name="redirect" value="' . esc_attr( $redirect ) . '" />';
				echo wp_nonce_field( 'sssj_del_search', '_sssj_ss', true, false );
				echo '<button class="sssj-btn sssj-btn--ghost sssj-btn--sm">' . esc_html__( 'Remove', 'shuffles-social-services-jobs' ) . '</button></form>';
				echo '</td></tr>';
			}
			echo '</table>';
		}
		echo '</div></div>';
		return ob_get_clean();
	}

	/* --------------------------------------------------------------- Admin "run now" */

	public function handle_run_now() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_alerts_run' );
		$this->run();
		wp_safe_redirect( add_query_arg( array( 'page' => Shuffles_SSJ_Admin::PAGE_SLUG, 'tab' => 'alerts', 'sssj_alerts' => 'ran' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
