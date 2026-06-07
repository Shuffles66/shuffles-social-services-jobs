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
		// Only 'verified' credentials count (matches Shuffles_SSJ_Credentials::STATUSES — there is no 'approved').
		$rows = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT kind FROM {$t} WHERE worker_id = %d AND status = 'verified' AND ( expires_date IS NULL OR expires_date = '0000-00-00' OR expires_date >= CURDATE() )", (int) $worker_id ) ); // phpcs:ignore WordPress.DB
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

	/* ------------------------------------------------------------------ */
	/* Employer job flyer (Phase 1b)                                      */
	/* ------------------------------------------------------------------ */

	/** The member's own published job ads (for the flyer picker). */
	public static function member_jobs( $uid ) {
		return get_posts( array(
			'post_type'      => 'sssj_job',
			'post_status'    => 'publish',
			'author'         => (int) $uid,
			'posts_per_page' => 50,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
	}

	/** Assemble flyer data for a single job. Null if it is not a job. Honours anonymous advertising. */
	public static function job_data( $job_id ) {
		$job_id = (int) $job_id;
		$post   = get_post( $job_id );
		if ( ! $post || 'sssj_job' !== $post->post_type ) {
			return null;
		}
		$basis  = (string) get_post_meta( $job_id, 'engagement_basis', true );
		$blabel = class_exists( 'Shuffles_SSJ_Query' ) ? Shuffles_SSJ_Query::basis_label( $basis ) : ucfirst( $basis );

		$suburb = (string) get_post_meta( $job_id, 'location_suburb', true );
		$state  = (string) get_post_meta( $job_id, 'location_state', true );
		$loc    = trim( $suburb . ( $state ? ', ' . $state : '' ) );

		$rmin = (float) get_post_meta( $job_id, 'rate_min', true );
		$rmax = (float) get_post_meta( $job_id, 'rate_max', true );
		$runit = (string) get_post_meta( $job_id, 'rate_unit', true );
		$pay  = '';
		if ( $rmin > 0 || $rmax > 0 ) {
			$amount = ( $rmin > 0 && $rmax > 0 && $rmax !== $rmin ) ? '$' . rtrim( rtrim( number_format( $rmin, 2 ), '0' ), '.' ) . ' to $' . rtrim( rtrim( number_format( $rmax, 2 ), '0' ), '.' ) : '$' . rtrim( rtrim( number_format( $rmax > 0 ? $rmax : $rmin, 2 ), '0' ), '.' );
			$pay    = $amount . ( $runit ? ' / ' . $runit : '' );
		}

		$anon   = '1' === (string) get_post_meta( $job_id, 'is_anonymous', true );
		$org_id = (int) get_post_meta( $job_id, 'organisation_id', true );
		$org    = $anon ? __( 'Private advertiser', 'shuffles-social-services-jobs' ) : ( $org_id ? get_the_title( $org_id ) : '' );
		$logo   = ( ! $anon && $org_id ) ? (string) get_the_post_thumbnail_url( $org_id, 'medium' ) : '';
		if ( '' === $logo && ! $anon ) {
			$logo = (string) get_the_post_thumbnail_url( $job_id, 'medium' );
		}

		$expires = (string) get_post_meta( $job_id, 'expires_at', true );

		return array(
			'job_id'   => $job_id,
			'title'    => $post->post_title,
			'basis'    => $blabel,
			'location' => $loc,
			'pay'      => $pay,
			'org'      => $org,
			'logo'     => $logo,
			'anon'     => $anon,
			'employment' => implode( ', ', self::term_names( $job_id, 'sssjt_employment_type' ) ),
			'services' => self::term_names( $job_id, 'sssjt_category' ),
			'closes'   => $expires,
			'blurb'    => wp_strip_all_tags( (string) $post->post_content ),
		);
	}

	/** Readability check for a job flyer. */
	public static function job_readability( $jd ) {
		$items   = array();
		$items[] = array( 'label' => __( 'Location is set and shows at the top', 'shuffles-social-services-jobs' ), 'ok' => '' !== trim( (string) $jd['location'] ), 'note' => __( 'Add a suburb to the job so people see where it is.', 'shuffles-social-services-jobs' ) );
		$items[] = array( 'label' => __( 'Pay is shown', 'shuffles-social-services-jobs' ), 'ok' => '' !== trim( (string) $jd['pay'] ), 'note' => __( 'Add a pay rate to the job. Listings with pay get far more responses.', 'shuffles-social-services-jobs' ) );
		$items[] = array( 'label' => __( 'A short description is present', 'shuffles-social-services-jobs' ), 'ok' => '' !== trim( (string) $jd['blurb'] ), 'note' => __( 'Add a short description of the role to the job.', 'shuffles-social-services-jobs' ) );
		$ok = true;
		foreach ( $items as $i ) { if ( ! $i['ok'] ) { $ok = false; } }
		return array( 'ok' => $ok, 'items' => $items );
	}

	/** A copy-paste caption for a job flyer. */
	public static function job_caption( $jd ) {
		$bits   = array();
		$bits[] = sprintf( __( 'Hiring: %s.', 'shuffles-social-services-jobs' ), $jd['title'] );
		if ( '' !== trim( (string) $jd['location'] ) ) {
			$bits[] = '📍 ' . $jd['location'] . '.';
		}
		if ( '' !== trim( (string) $jd['basis'] ) ) {
			$bits[] = $jd['basis'] . '.';
		}
		if ( '' !== trim( (string) $jd['pay'] ) ) {
			$bits[] = $jd['pay'] . '.';
		}
		$bits[] = __( 'Apply safely through Just Tasks.', 'shuffles-social-services-jobs' );
		return implode( "\n", $bits );
	}
}
