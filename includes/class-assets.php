<?php
/**
 * Shareable assets (Workstream E), Phase 1: the worker / sole-trader résumé.
 *
 * Pulls the member's existing worker-profile data, exposes it to a locked, readable template
 * (templates/assets/resume.php), runs a readability check, and builds a copy-paste caption.
 * The $0 browser path (print-to-PDF + client image + copy text) renders client-side; a higher
 * fidelity server backend can be added later behind renderer_backend().
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Assets {

	/** Is the asset builder switched on? (Default ON.) */
	public static function enabled() {
		$o = get_option( 'shuffles_ssj_settings', array() );
		return ! ( is_array( $o ) && isset( $o['assets_enabled'] ) && ! $o['assets_enabled'] );
	}

	/** The active render backend. Phase 1 = browser; a server backend can be filtered in later. */
	public static function renderer_backend() {
		return (string) apply_filters( 'shuffles_ssj_asset_renderer', 'browser' );
	}

	/** The current user's worker profile post id, or 0. */
	public static function worker_id_for_user( $uid ) {
		$uid   = (int) $uid;
		$found = $uid ? get_posts( array(
			'post_type'      => 'sssj_worker',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_key'       => 'worker_user_id',
			'meta_value'     => $uid,
		) ) : array();
		return ! empty( $found ) ? (int) $found[0] : 0;
	}

	/** Prettify a credential kind key into a label. */
	private static function kind_label( $kind ) {
		$map = array(
			'wwcc'           => __( 'Working With Children Check', 'shuffles-social-services-jobs' ),
			'blue_card'      => __( 'Blue Card', 'shuffles-social-services-jobs' ),
			'ndis_screening' => __( 'NDIS Worker Screening', 'shuffles-social-services-jobs' ),
			'ndis'           => __( 'NDIS Worker Screening', 'shuffles-social-services-jobs' ),
			'police'         => __( 'Police Check', 'shuffles-social-services-jobs' ),
			'police_check'   => __( 'Police Check', 'shuffles-social-services-jobs' ),
			'first_aid'      => __( 'First Aid', 'shuffles-social-services-jobs' ),
			'cpr'            => __( 'CPR', 'shuffles-social-services-jobs' ),
			'first_aid_cpr'  => __( 'First Aid and CPR', 'shuffles-social-services-jobs' ),
			'insurance'      => __( 'Insurance', 'shuffles-social-services-jobs' ),
		);
		$k = strtolower( (string) $kind );
		if ( isset( $map[ $k ] ) ) {
			return $map[ $k ];
		}
		return ucwords( str_replace( array( '_', '-' ), ' ', $k ) );
	}

	/** Approved, in-date credential labels for a worker (the only checks we display). */
	private static function verified_checks( $worker_id ) {
		global $wpdb;
		$t    = $wpdb->prefix . 'sssj_credential';
		$rows = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT kind FROM {$t} WHERE worker_id = %d AND status = 'approved' AND ( expires_date IS NULL OR expires_date = '0000-00-00' OR expires_date >= CURDATE() )", (int) $worker_id ) ); // phpcs:ignore WordPress.DB
		$out  = array();
		foreach ( (array) $rows as $kind ) {
			$out[] = self::kind_label( $kind );
		}
		return $out;
	}

	/** Term names for a taxonomy on a post. */
	private static function term_names( $post_id, $tax ) {
		$terms = wp_get_post_terms( (int) $post_id, $tax, array( 'fields' => 'names' ) );
		return is_wp_error( $terms ) ? array() : array_values( $terms );
	}

	/**
	 * Assemble the résumé data from the member's worker profile.
	 *
	 * @return array|null null if there is no worker profile yet.
	 */
	public static function resume_data( $uid ) {
		$wid = self::worker_id_for_user( $uid );
		if ( ! $wid ) {
			return null;
		}
		$post     = get_post( $wid );
		$services = self::term_names( $wid, 'sssjt_category' );
		$langs    = array_merge( self::term_names( $wid, 'sssjt_language' ), self::term_names( $wid, 'sssjt_culture' ) );

		$suburb = (string) get_post_meta( $wid, 'location_suburb', true );
		$state  = (string) get_post_meta( $wid, 'location_state', true );
		$km     = (int) get_post_meta( $wid, 'travel_radius_km', true );
		$loc    = trim( $suburb . ( $state ? ', ' . $state : '' ) );
		if ( $km > 0 ) {
			$loc .= ', ' . sprintf( __( 'travels up to %d km', 'shuffles-social-services-jobs' ), $km );
		}

		$available = '1' === (string) get_post_meta( $wid, 'is_available', true );
		$years     = (int) get_post_meta( $wid, 'years_experience', true );

		return array(
			'worker_id'  => $wid,
			'name'       => $post ? $post->post_title : '',
			'tagline'    => ! empty( $services ) ? $services[0] : __( 'Support Worker', 'shuffles-social-services-jobs' ),
			'location'   => $loc,
			'suburb'     => $suburb,
			'available'  => $available ? __( 'Available now', 'shuffles-social-services-jobs' ) : __( 'Available by arrangement', 'shuffles-social-services-jobs' ),
			'experience' => $years > 0 ? sprintf( _n( '%d year', '%d years', $years, 'shuffles-social-services-jobs' ), $years ) : '',
			'languages'  => $langs,
			'services'   => $services,
			'blurb'      => $post ? wp_strip_all_tags( (string) $post->post_content ) : '',
			'photo'      => (string) get_the_post_thumbnail_url( $wid, 'medium' ),
			'checks'     => self::verified_checks( $wid ),
			'abn'        => (string) get_post_meta( $wid, 'worker_abn', true ),
		);
	}

	/** Initials for the avatar fallback. */
	public static function initials( $name ) {
		$name  = trim( (string) $name );
		if ( '' === $name ) {
			return '★';
		}
		$parts = preg_split( '/\s+/', $name );
		$ini   = strtoupper( substr( $parts[0], 0, 1 ) );
		if ( count( $parts ) > 1 ) {
			$ini .= strtoupper( substr( $parts[ count( $parts ) - 1 ], 0, 1 ) );
		}
		return $ini;
	}

	/**
	 * Readability check. Returns [ ok (bool), items[ {label, ok, note} ] ].
	 * Mirrors the rules in docs/BEST-PRACTICE-ASSETS.md / RESUME-BUILDER-PLAN.md.
	 */
	public static function readability( $data ) {
		$items   = array();
		$blurb   = trim( (string) $data['blurb'] );
		$wordcnt = '' === $blurb ? 0 : str_word_count( $blurb );

		$items[] = array(
			'label' => __( 'Location is set and shows at the top', 'shuffles-social-services-jobs' ),
			'ok'    => '' !== trim( (string) $data['location'] ),
			'note'  => __( 'Add your suburb and service area on your profile so people see where you work at a glance.', 'shuffles-social-services-jobs' ),
		);
		$items[] = array(
			'label' => __( 'You list at least one service', 'shuffles-social-services-jobs' ),
			'ok'    => ! empty( $data['services'] ),
			'note'  => __( 'Add the services you offer on your profile.', 'shuffles-social-services-jobs' ),
		);
		$items[] = array(
			'label' => __( 'Your blurb is short and scannable', 'shuffles-social-services-jobs' ),
			'ok'    => $wordcnt > 0 && $wordcnt <= 70,
			'note'  => 0 === $wordcnt ? __( 'Add a short, friendly introduction (one to three sentences).', 'shuffles-social-services-jobs' ) : __( 'Trim your introduction to about 70 words so it stays easy to read.', 'shuffles-social-services-jobs' ),
		);
		$items[] = array(
			'label' => __( 'At least one verified check', 'shuffles-social-services-jobs' ),
			'ok'    => ! empty( $data['checks'] ),
			'note'  => __( 'Upload your checks (WWCC, NDIS screening, etc.) for admin verification, so they can show here.', 'shuffles-social-services-jobs' ),
		);

		$ok = true;
		foreach ( $items as $i ) {
			if ( ! $i['ok'] ) {
				$ok = false;
			}
		}
		return array( 'ok' => $ok, 'items' => $items );
	}

	/** A copy-paste social caption built from the résumé data. */
	public static function caption( $data ) {
		$bits = array();
		if ( '' !== trim( (string) $data['location'] ) ) {
			$bits[] = '📍 ' . $data['location'];
		}
		$bits[] = (string) $data['tagline'] . '.';
		if ( ! empty( $data['services'] ) ) {
			$top    = array_slice( $data['services'], 0, 4 );
			$bits[] = sprintf( __( 'I help with %s.', 'shuffles-social-services-jobs' ), implode( ', ', $top ) );
		}
		if ( ! empty( $data['available'] ) ) {
			$bits[] = $data['available'] . '.';
		}
		if ( ! empty( $data['checks'] ) ) {
			$bits[] = sprintf( __( 'Verified: %s.', 'shuffles-social-services-jobs' ), implode( ', ', $data['checks'] ) );
		}
		$bits[] = __( 'Get in touch safely through Just Tasks.', 'shuffles-social-services-jobs' );
		return implode( "\n", $bits );
	}
}
