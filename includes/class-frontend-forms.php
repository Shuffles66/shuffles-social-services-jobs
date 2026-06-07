<?php
/**
 * Front-end form handlers (admin-post). Phase 1: advertiser job posting.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Frontend_Forms {

	public function register() {
		add_action( 'admin_post_sssj_post_job', array( $this, 'handle_post_job' ) );
		add_action( 'admin_post_nopriv_sssj_post_job', array( $this, 'deny' ) );
		add_action( 'admin_post_sssj_post_worker', array( $this, 'handle_post_worker' ) );
		add_action( 'admin_post_nopriv_sssj_post_worker', array( $this, 'deny' ) );
		add_action( 'admin_post_sssj_post_need', array( $this, 'handle_post_need' ) );
		add_action( 'admin_post_nopriv_sssj_post_need', array( $this, 'deny' ) );
		add_action( 'admin_post_sssj_apply', array( $this, 'handle_apply' ) );
		add_action( 'admin_post_nopriv_sssj_apply', array( $this, 'deny' ) );
		add_action( 'admin_post_sssj_app_status', array( $this, 'handle_app_status' ) );
		add_action( 'admin_post_sssj_send_message', array( $this, 'handle_send_message' ) );
		add_action( 'admin_post_sssj_post_org', array( $this, 'handle_post_org' ) );
		add_action( 'admin_post_nopriv_sssj_post_org', array( $this, 'deny' ) );
		add_action( 'wp_ajax_sssj_autofill', array( $this, 'ajax_autofill' ) );
		add_action( 'admin_post_sssj_save_roles', array( $this, 'handle_save_roles' ) );
		add_action( 'admin_post_sssj_org_team', array( $this, 'handle_org_team' ) );
		add_action( 'admin_post_nopriv_sssj_org_team', array( $this, 'deny' ) );
		add_action( 'admin_post_sssj_org_join', array( $this, 'handle_org_join' ) );
		add_action( 'admin_post_nopriv_sssj_org_join', array( $this, 'deny' ) );
	}

	/**
	 * Member-initiated request to join an organisation (admin approves later). Any logged-in member
	 * may request; the org chooses whether to accept. Never creates a membership directly.
	 */
	public function handle_org_join() {
		$nonce = isset( $_POST['sssj_join_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['sssj_join_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'sssj_org_join' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'shuffles-social-services-jobs' ) );
		}
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Please log in.', 'shuffles-social-services-jobs' ) );
		}
		$org_id   = isset( $_POST['org_id'] ) ? (int) $_POST['org_id'] : 0;
		$op       = isset( $_POST['op'] ) ? sanitize_key( wp_unslash( $_POST['op'] ) ) : '';
		$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		if ( ! $org_id || 'sssj_org' !== get_post_type( $org_id ) ) {
			wp_die( esc_html__( 'That organisation could not be found.', 'shuffles-social-services-jobs' ) );
		}
		$uid    = get_current_user_id();
		$notice = 'ok';
		if ( 'request' === $op ) {
			$msg = isset( $_POST['msg'] ) ? wp_unslash( $_POST['msg'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$res = Shuffles_SSJ_Org_Team::add_request( $org_id, $uid, $msg );
			if ( is_wp_error( $res ) ) {
				$notice = 'err';
			} else {
				$notice = 'requested';
				$this->notify_join_request( $org_id, $uid );
			}
		} elseif ( 'cancel' === $op ) {
			Shuffles_SSJ_Org_Team::cancel_request( $org_id, $uid );
			$notice = 'cancelled';
		}
		wp_safe_redirect( add_query_arg( 'sssj_join', $notice, remove_query_arg( 'sssj_join', $redirect ) ) );
		exit;
	}

	/** Best-effort email to the org's admins that someone asked to join (suppress via filter). */
	private function notify_join_request( $org_id, $applicant_id ) {
		if ( ! apply_filters( 'shuffles_ssj_send_join_request_email', true, $org_id, $applicant_id ) ) {
			return;
		}
		$applicant = get_user_by( 'id', (int) $applicant_id );
		if ( ! $applicant ) {
			return;
		}
		$admins = array();
		foreach ( Shuffles_SSJ_Org_Team::roster( $org_id ) as $row ) {
			if ( in_array( $row['role'], array( 'owner', 'admin' ), true ) && ! empty( $row['email'] ) ) {
				$admins[] = $row['email'];
			}
		}
		$admins = array_values( array_unique( array_filter( $admins ) ) );
		if ( ! $admins ) {
			return;
		}
		$org_name = get_the_title( $org_id );
		$subject  = sprintf( __( 'New request to join %s', 'shuffles-social-services-jobs' ), $org_name );
		$body     = sprintf(
			/* translators: 1: applicant name, 2: org name */
			__( "%1\$s has asked to join %2\$s.\n\nReview and approve or decline this request from the Team tab on your dashboard.", 'shuffles-social-services-jobs' ),
			$applicant->display_name ? $applicant->display_name : $applicant->user_login,
			$org_name
		);
		wp_mail( $admins, $subject, $body );
	}

	/**
	 * Org team management (D): add / change-role / remove a member. Only an org admin
	 * (owner, an 'admin' member, or a site admin) may act, and only on an org they manage.
	 */
	public function handle_org_team() {
		$nonce = isset( $_POST['sssj_team_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['sssj_team_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'sssj_org_team' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'shuffles-social-services-jobs' ) );
		}
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Please log in.', 'shuffles-social-services-jobs' ) );
		}
		$org_id   = isset( $_POST['org_id'] ) ? (int) $_POST['org_id'] : 0;
		$op       = isset( $_POST['op'] ) ? sanitize_key( wp_unslash( $_POST['op'] ) ) : '';
		$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );

		if ( ! $org_id || ! Shuffles_SSJ_Org_Team::is_admin( $org_id, get_current_user_id() ) ) {
			wp_die( esc_html__( 'You do not manage this organisation’s team.', 'shuffles-social-services-jobs' ) );
		}

		$notice = 'ok';
		if ( 'add' === $op ) {
			$needle = isset( $_POST['member'] ) ? sanitize_text_field( wp_unslash( $_POST['member'] ) ) : '';
			$role   = ( isset( $_POST['role'] ) && 'admin' === $_POST['role'] ) ? 'admin' : 'member';
			$uid    = Shuffles_SSJ_Org_Team::find_user( $needle );
			if ( ! $uid ) {
				$notice = 'nouser';
			} else {
				$res    = Shuffles_SSJ_Org_Team::add_member( $org_id, $uid, $role );
				$notice = is_wp_error( $res ) ? 'err' : 'added';
			}
		} elseif ( 'role' === $op ) {
			$uid  = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : 0;
			$role = ( isset( $_POST['role'] ) && 'admin' === $_POST['role'] ) ? 'admin' : 'member';
			$res  = Shuffles_SSJ_Org_Team::set_role( $org_id, $uid, $role );
			$notice = is_wp_error( $res ) ? 'err' : 'updated';
		} elseif ( 'remove' === $op ) {
			$uid    = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : 0;
			$res    = Shuffles_SSJ_Org_Team::remove_member( $org_id, $uid );
			$notice = is_wp_error( $res ) ? 'err' : 'removed';
		} elseif ( 'approve' === $op ) {
			$uid    = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : 0;
			$role   = ( isset( $_POST['role'] ) && 'admin' === $_POST['role'] ) ? 'admin' : 'member';
			$res    = Shuffles_SSJ_Org_Team::approve_request( $org_id, $uid, $role );
			$notice = is_wp_error( $res ) ? 'err' : 'approved';
		} elseif ( 'decline' === $op ) {
			$uid    = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : 0;
			Shuffles_SSJ_Org_Team::decline_request( $org_id, $uid );
			$notice = 'declined';
		}

		wp_safe_redirect( add_query_arg( 'sssj_team', $notice, remove_query_arg( 'sssj_team', $redirect ) ) );
		exit;
	}

	/** Save a member's declared roles (from [sssj_roles]) and grant the matching capabilities. */
	public function handle_save_roles() {
		$nonce = isset( $_POST['sssj_roles_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['sssj_roles_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'sssj_save_roles' ) || ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Security check failed.', 'shuffles-social-services-jobs' ) );
		}
		$roles = isset( $_POST['sssj_roles'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['sssj_roles'] ) ) : array();
		Shuffles_SSJ_Roles::set_member_roles( get_current_user_id(), $roles );
		$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		wp_safe_redirect( add_query_arg( 'sssj_roles', '1', $redirect ) );
		exit;
	}

	/**
	 * AJAX: read a provider's website and return suggested profile fields (name, description, phone).
	 * Keyless by default; an AI/Tavily integration can enhance/replace via the shuffles_ssj_autofill
	 * filter. Member-facing copy calls this "our AI" — never names a vendor.
	 */
	public function ajax_autofill() {
		check_ajax_referer( 'sssj_autofill', 'nonce' );
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'msg' => __( 'Please log in.', 'shuffles-social-services-jobs' ) ) );
		}
		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
		if ( '' === $url || ! preg_match( '#^https?://#i', $url ) ) {
			wp_send_json_error( array( 'msg' => __( 'Enter your website URL first (including https://).', 'shuffles-social-services-jobs' ) ) );
		}
		$resp = wp_remote_get( $url, array( 'timeout' => 15, 'redirection' => 3, 'user-agent' => 'Mozilla/5.0 (compatible; ShufflesJobs/1.0)' ) );
		if ( is_wp_error( $resp ) || 200 !== (int) wp_remote_retrieve_response_code( $resp ) ) {
			wp_send_json_error( array( 'msg' => __( 'Could not read that website. Check the address and try again.', 'shuffles-social-services-jobs' ) ) );
		}
		$html = (string) wp_remote_retrieve_body( $resp );
		$data = array( 'org_name' => '', 'description' => '', 'org_phone' => '' );

		$meta_content = function ( $attr, $val ) use ( $html ) {
			if ( preg_match( '/<meta[^>]*(?:name|property)=["\']' . preg_quote( $val, '/' ) . '["\'][^>]*>/i', $html, $tag )
				&& preg_match( '/content=["\'](.*?)["\']/is', $tag[0], $c ) ) {
				return trim( wp_strip_all_tags( html_entity_decode( $c[1], ENT_QUOTES ) ) );
			}
			return '';
		};
		if ( preg_match( '/<title[^>]*>(.*?)<\/title>/is', $html, $m ) ) {
			$data['org_name'] = trim( wp_strip_all_tags( html_entity_decode( $m[1], ENT_QUOTES ) ) );
		}
		$og_site = $meta_content( 'property', 'og:site_name' );
		if ( $og_site ) {
			$data['org_name'] = $og_site;
		}
		$desc = $meta_content( 'name', 'description' );
		if ( '' === $desc ) {
			$desc = $meta_content( 'property', 'og:description' );
		}
		$data['description'] = $desc;
		if ( preg_match( '/tel:\+?([0-9 ()\-]{6,})/i', $html, $m ) ) {
			$data['org_phone'] = trim( $m[1] );
		}

		// Let an AI/Tavily integration enhance/replace these suggestions.
		$data = apply_filters( 'shuffles_ssj_autofill', $data, $url, $html );
		wp_send_json_success( $data );
	}

	public function deny() {
		wp_die( esc_html__( 'You must be logged in to post a job.', 'shuffles-social-services-jobs' ) );
	}

	/**
	 * Process the [sssj_post_job] submission.
	 */
	public function handle_post_job() {
		$nonce = isset( $_POST['sssj_job_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['sssj_job_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'sssj_post_job' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'shuffles-social-services-jobs' ) );
		}
		if ( ! current_user_can( 'sssj_post_job' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to post a job.', 'shuffles-social-services-jobs' ) );
		}

		$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );

		// Employer advertising subscription / free-tier listing cap.
		if ( ! Shuffles_SSJ_Monetisation::can_post_job( get_current_user_id() ) ) {
			wp_safe_redirect( add_query_arg( 'sssj_posted', 'limit', $redirect ) );
			exit;
		}

		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$basis = isset( $_POST['engagement_basis'] ) ? sanitize_key( wp_unslash( $_POST['engagement_basis'] ) ) : '';

		if ( '' === $title || ! in_array( $basis, array( 'abn', 'tfn' ), true ) ) {
			wp_safe_redirect( add_query_arg( 'sssj_posted', 'error', $redirect ) );
			exit;
		}

		// ABN: required + checksum-valid for ABN basis; never stored for TFN.
		$abn = isset( $_POST['advertiser_abn'] ) ? preg_replace( '/\D+/', '', (string) wp_unslash( $_POST['advertiser_abn'] ) ) : '';
		if ( 'abn' === $basis ) {
			if ( ! Shuffles_SSJ_ABN::is_valid( $abn ) ) {
				wp_safe_redirect( add_query_arg( 'sssj_posted', 'abn', $redirect ) );
				exit;
			}
		} else {
			$abn = '';
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'sssj_job',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_content' => isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '',
				'post_author'  => get_current_user_id(),
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			wp_safe_redirect( add_query_arg( 'sssj_posted', 'error', $redirect ) );
			exit;
		}

		// Employer screening questions (one per line) + how applications are handled.
		$screening_qs = array();
		if ( ! empty( $_POST['screening_questions'] ) ) {
			foreach ( preg_split( '/\r\n|\r|\n/', (string) wp_unslash( $_POST['screening_questions'] ) ) as $line ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$line = trim( sanitize_text_field( $line ) );
				if ( '' !== $line ) {
					$screening_qs[] = $line;
				}
			}
			$screening_qs = array_slice( $screening_qs, 0, 12 );
		}

		$meta = array(
			'engagement_basis'   => $basis,
			'application_mode'   => ( isset( $_POST['application_mode'] ) && 'simple' === $_POST['application_mode'] ) ? 'simple' : 'full',
			'screening_questions' => $screening_qs,
			'engagement_type'   => isset( $_POST['engagement_type'] ) ? sanitize_key( wp_unslash( $_POST['engagement_type'] ) ) : 'ongoing',
			'organisation_type' => isset( $_POST['organisation_type'] ) ? sanitize_key( wp_unslash( $_POST['organisation_type'] ) ) : 'employer',
			'advertiser_abn'    => $abn,
			'location_suburb'   => isset( $_POST['location_suburb'] ) ? sanitize_text_field( wp_unslash( $_POST['location_suburb'] ) ) : '',
			'location_state'    => isset( $_POST['location_state'] ) ? sanitize_text_field( wp_unslash( $_POST['location_state'] ) ) : '',
			'location_postcode' => isset( $_POST['location_postcode'] ) ? sanitize_text_field( wp_unslash( $_POST['location_postcode'] ) ) : '',
			'location_lat'      => isset( $_POST['location_lat'] ) ? (float) $_POST['location_lat'] : 0,
			'location_lng'      => isset( $_POST['location_lng'] ) ? (float) $_POST['location_lng'] : 0,
			'rate_min'          => isset( $_POST['rate_min'] ) ? (float) $_POST['rate_min'] : 0,
			'rate_max'          => isset( $_POST['rate_max'] ) ? (float) $_POST['rate_max'] : 0,
			'rate_unit'         => isset( $_POST['rate_unit'] ) ? sanitize_key( wp_unslash( $_POST['rate_unit'] ) ) : 'hour',
			'expires_at'        => isset( $_POST['expires_at'] ) ? sanitize_text_field( wp_unslash( $_POST['expires_at'] ) ) : '',
			'organisation_id'   => isset( $_POST['organisation_id'] ) ? absint( $_POST['organisation_id'] ) : 0,
			'sssj_alert_candidates' => empty( $_POST['alert_candidates'] ) ? '' : '1',
		);
		foreach ( $meta as $k => $v ) {
			update_post_meta( $post_id, $k, $v );
		}

		// Optional per-job logo → featured image (otherwise the job inherits its organisation's logo).
		if ( ! empty( $_FILES['job_logo']['name'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			$att = media_handle_upload( 'job_logo', $post_id );
			if ( ! is_wp_error( $att ) ) {
				set_post_thumbnail( $post_id, $att );
			}
		}

		if ( ! empty( $_POST['category'] ) ) {
			$term_id = (int) $_POST['category'];
			if ( $term_id > 0 ) {
				wp_set_object_terms( $post_id, array( $term_id ), 'sssjt_category' );
			}
		}
		// Funding source(s) — how the role is funded (NDIS / Aged Care / DVA / …).
		$fund_ids = ( ! empty( $_POST['funding_sources'] ) && is_array( $_POST['funding_sources'] ) ) ? array_filter( array_map( 'absint', (array) wp_unslash( $_POST['funding_sources'] ) ) ) : array();
		wp_set_object_terms( $post_id, $fund_ids, 'sssjt_funding_source' );
		if ( ! empty( $_POST['employment_type'] ) ) {
			$et = (int) $_POST['employment_type'];
			if ( $et > 0 ) {
				wp_set_object_terms( $post_id, array( $et ), 'sssjt_employment_type' );
			}
		}

		// Geocode from address parts when no client-side coordinates were captured (keyless).
		if ( empty( $meta['location_lat'] ) && empty( $meta['location_lng'] ) ) {
			$geo = Shuffles_SSJ_Geo::geocode_parts( $meta['location_suburb'], $meta['location_state'], $meta['location_postcode'] );
			if ( $geo ) {
				update_post_meta( $post_id, 'location_lat', $geo['lat'] );
				update_post_meta( $post_id, 'location_lng', $geo['lng'] );
			}
		}

		// ABN recorded → let the Reference Check plugin cross-match later (flag-only).
		if ( 'abn' === $basis && $abn ) {
			do_action( 'shuffles_ssj_abn_recorded', $abn, 'job', $post_id );
		}

		// Culture + language (shared multi-select taxonomies).
		foreach ( array( 'culture' => 'sssjt_culture', 'language' => 'sssjt_language' ) as $clf => $cltax ) {
			$clids = ( ! empty( $_POST[ $clf ] ) && is_array( $_POST[ $clf ] ) ) ? array_filter( array_map( 'absint', (array) wp_unslash( $_POST[ $clf ] ) ) ) : array();
			wp_set_object_terms( $post_id, $clids, $cltax );
		}

		// Featured placement: stamp is_promoted + menu_order from the advertiser's sub.
		Shuffles_SSJ_Monetisation::refresh_job_feature_flag( $post_id );

		wp_safe_redirect( add_query_arg( 'sssj_posted', '1', $redirect ) );
		exit;
	}

	/**
	 * Process the [sssj_post_worker] submission (create or update the user's own profile).
	 */
	public function handle_post_worker() {
		$nonce = isset( $_POST['sssj_worker_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['sssj_worker_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'sssj_post_worker' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'shuffles-social-services-jobs' ) );
		}
		if ( ! is_user_logged_in() || ( ! current_user_can( 'sssj_post_worker' ) && ! current_user_can( 'manage_options' ) ) ) {
			wp_die( esc_html__( 'You do not have permission to create a worker profile.', 'shuffles-social-services-jobs' ) );
		}

		$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		$uid      = get_current_user_id();

		$name = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '';
		if ( '' === $name ) {
			wp_safe_redirect( add_query_arg( 'sssj_worker', 'error', $redirect ) );
			exit;
		}

		// Optional ABN — if provided it must be checksum-valid (accuracy).
		$abn = isset( $_POST['worker_abn'] ) ? preg_replace( '/\D+/', '', (string) wp_unslash( $_POST['worker_abn'] ) ) : '';
		if ( '' !== $abn && ! Shuffles_SSJ_ABN::is_valid( $abn ) ) {
			wp_safe_redirect( add_query_arg( 'sssj_worker', 'abn', $redirect ) );
			exit;
		}

		$visibility = isset( $_POST['visibility'] ) ? sanitize_key( wp_unslash( $_POST['visibility'] ) ) : 'logged_in';
		if ( ! in_array( $visibility, array( 'public', 'logged_in', 'verified_only', 'hidden' ), true ) ) {
			$visibility = 'logged_in';
		}

		$post_data = array(
			'post_type'    => 'sssj_worker',
			'post_status'  => 'publish',
			'post_title'   => $name,
			'post_content' => isset( $_POST['bio'] ) ? wp_kses_post( wp_unslash( $_POST['bio'] ) ) : '',
			'post_author'  => $uid,
		);

		// One profile per user — update the existing one if present.
		$existing = get_posts(
			array(
				'post_type'      => 'sssj_worker',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => 'worker_user_id',
				'meta_value'     => $uid,
			)
		);
		if ( ! empty( $existing ) ) {
			$post_data['ID'] = (int) $existing[0];
			$post_id         = wp_update_post( $post_data, true );
		} else {
			$post_id = wp_insert_post( $post_data, true );
		}
		if ( is_wp_error( $post_id ) ) {
			wp_safe_redirect( add_query_arg( 'sssj_worker', 'error', $redirect ) );
			exit;
		}

		$meta = array(
			'worker_user_id'    => $uid,
			'is_available'      => empty( $_POST['is_available'] ) ? 0 : 1,
			'employment_status' => isset( $_POST['employment_status'] ) ? sanitize_key( wp_unslash( $_POST['employment_status'] ) ) : 'seeking',
			'worker_abn'        => $abn,
			'ndis_registered'   => empty( $_POST['ndis_registered'] ) ? '' : '1',
			'ndis_register_id'  => isset( $_POST['ndis_register_id'] ) ? preg_replace( '/\D+/', '', (string) wp_unslash( $_POST['ndis_register_id'] ) ) : '',
			'years_experience'  => isset( $_POST['years_experience'] ) ? absint( $_POST['years_experience'] ) : 0,
			'travel_radius_km'  => isset( $_POST['travel_radius_km'] ) ? absint( $_POST['travel_radius_km'] ) : 0,
			'rate_min'          => isset( $_POST['rate_min'] ) ? (float) $_POST['rate_min'] : 0,
			'rate_max'          => isset( $_POST['rate_max'] ) ? (float) $_POST['rate_max'] : 0,
			'rate_unit'         => isset( $_POST['rate_unit'] ) ? sanitize_key( wp_unslash( $_POST['rate_unit'] ) ) : 'hour',
			'visibility'        => $visibility,
			'location_suburb'   => isset( $_POST['location_suburb'] ) ? sanitize_text_field( wp_unslash( $_POST['location_suburb'] ) ) : '',
			'location_state'    => isset( $_POST['location_state'] ) ? sanitize_text_field( wp_unslash( $_POST['location_state'] ) ) : '',
			'location_postcode' => isset( $_POST['location_postcode'] ) ? sanitize_text_field( wp_unslash( $_POST['location_postcode'] ) ) : '',
			'location_lat'      => isset( $_POST['location_lat'] ) ? (float) $_POST['location_lat'] : 0,
			'location_lng'      => isset( $_POST['location_lng'] ) ? (float) $_POST['location_lng'] : 0,
		);
		// Geocode from suburb/state when no client-side coordinates (keyless), so radius search finds them.
		if ( empty( $meta['location_lat'] ) && empty( $meta['location_lng'] ) ) {
			$geo = Shuffles_SSJ_Geo::geocode_parts( $meta['location_suburb'], $meta['location_state'], $meta['location_postcode'] );
			if ( $geo ) {
				$meta['location_lat'] = $geo['lat'];
				$meta['location_lng'] = $geo['lng'];
			}
		}
		foreach ( $meta as $k => $v ) {
			update_post_meta( $post_id, $k, $v );
		}

		// Per-field privacy masking (C4) — "members only" toggles for sensitive fields.
		if ( class_exists( 'Shuffles_SSJ_Privacy' ) ) {
			Shuffles_SSJ_Privacy::save( $post_id, 'worker', isset( $_POST['sssj_mask'] ) ? (array) wp_unslash( $_POST['sssj_mask'] ) : array() ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		// Profile photo → featured image; extra photos → gallery (attachment IDs in _sssj_gallery).
		if ( ! empty( $_FILES['worker_photo']['name'] ) || ! empty( $_FILES['worker_gallery']['name'][0] ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}
		if ( ! empty( $_FILES['worker_photo']['name'] ) ) {
			$att = media_handle_upload( 'worker_photo', $post_id );
			if ( ! is_wp_error( $att ) ) {
				set_post_thumbnail( $post_id, $att );
			}
		}
		if ( ! empty( $_FILES['worker_gallery']['name'][0] ) ) {
			$files   = $_FILES['worker_gallery']; // phpcs:ignore WordPress.Security
			$gallery = array_filter( array_map( 'intval', (array) get_post_meta( $post_id, '_sssj_gallery', true ) ) );
			$n       = count( (array) $files['name'] );
			for ( $i = 0; $i < $n && count( $gallery ) < 6; $i++ ) {
				if ( empty( $files['name'][ $i ] ) ) {
					continue;
				}
				$_FILES['sssj_g_one'] = array(
					'name'     => $files['name'][ $i ],
					'type'     => $files['type'][ $i ],
					'tmp_name' => $files['tmp_name'][ $i ],
					'error'    => $files['error'][ $i ],
					'size'     => $files['size'][ $i ],
				);
				$gid = media_handle_upload( 'sssj_g_one', $post_id );
				if ( ! is_wp_error( $gid ) ) {
					$gallery[] = (int) $gid;
				}
			}
			unset( $_FILES['sssj_g_one'] );
			update_post_meta( $post_id, '_sssj_gallery', array_values( array_unique( array_map( 'intval', $gallery ) ) ) );
		}

		if ( ! empty( $_POST['services'] ) && is_array( $_POST['services'] ) ) {
			$ids = array_filter( array_map( 'absint', (array) wp_unslash( $_POST['services'] ) ) );
			wp_set_object_terms( $post_id, $ids, 'sssjt_category' );
		}

		// Culture + language (shared multi-select taxonomies).
		foreach ( array( 'culture' => 'sssjt_culture', 'language' => 'sssjt_language' ) as $clf => $cltax ) {
			$clids = ( ! empty( $_POST[ $clf ] ) && is_array( $_POST[ $clf ] ) ) ? array_filter( array_map( 'absint', (array) wp_unslash( $_POST[ $clf ] ) ) ) : array();
			wp_set_object_terms( $post_id, $clids, $cltax );
		}

		if ( '' !== $abn ) {
			do_action( 'shuffles_ssj_abn_recorded', $abn, 'worker', $post_id );
		}

		// Sole-trader NDIS registration → live register lookup (same hook the org form uses).
		if ( ! empty( $_POST['ndis_registered'] ) && '' !== (string) get_post_meta( $post_id, 'ndis_register_id', true ) ) {
			do_action( 'shuffles_ssj_ndis_recorded', (string) get_post_meta( $post_id, 'ndis_register_id', true ), $post_id );
		}

		do_action( 'shuffles_ssj_profile_saved', 'worker', $post_id, get_current_user_id() );

		wp_safe_redirect( add_query_arg( 'sssj_worker', '1', $redirect ) );
		exit;
	}

	/**
	 * Process the [sssj_post_need] submission.
	 *
	 * Privacy: stores a generated pseudonym (never a real name), suburb-only location, and
	 * defaults to 'pending' for admin moderation before it can appear on the board.
	 */
	public function handle_post_need() {
		$nonce = isset( $_POST['sssj_need_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['sssj_need_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'sssj_post_need' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'shuffles-social-services-jobs' ) );
		}
		if ( ! is_user_logged_in() || ( ! current_user_can( 'sssj_post_need' ) && ! current_user_can( 'manage_options' ) ) ) {
			wp_die( esc_html__( 'You do not have permission to post a participant need.', 'shuffles-social-services-jobs' ) );
		}

		$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		$uid      = get_current_user_id();

		// Title is a SHORT DESCRIPTION, never a name.
		$desc = isset( $_POST['short_description'] ) ? sanitize_text_field( wp_unslash( $_POST['short_description'] ) ) : '';
		if ( '' === $desc ) {
			wp_safe_redirect( add_query_arg( 'sssj_need', 'error', $redirect ) );
			exit;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'sssj_need',
				'post_status'  => 'pending', // Always moderated before publish.
				'post_title'   => $desc,
				'post_content' => isset( $_POST['details'] ) ? wp_kses_post( wp_unslash( $_POST['details'] ) ) : '',
				'post_author'  => $uid,
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			wp_safe_redirect( add_query_arg( 'sssj_need', 'error', $redirect ) );
			exit;
		}

		$pseudonym = 'P-' . strtoupper( wp_generate_password( 7, false, false ) );

		$meta = array(
			'participant_ref'    => $pseudonym,
			'nominee_user_id'    => $uid,
			'location_suburb'    => isset( $_POST['location_suburb'] ) ? sanitize_text_field( wp_unslash( $_POST['location_suburb'] ) ) : '',
			'location_state'     => isset( $_POST['location_state'] ) ? sanitize_text_field( wp_unslash( $_POST['location_state'] ) ) : '',
			'location_lat'       => isset( $_POST['location_lat'] ) ? (float) $_POST['location_lat'] : 0,
			'location_lng'       => isset( $_POST['location_lng'] ) ? (float) $_POST['location_lng'] : 0,
			'travel_radius_km'   => isset( $_POST['travel_radius_km'] ) ? absint( $_POST['travel_radius_km'] ) : 0,
			'seeking_type'       => isset( $_POST['seeking_type'] ) ? sanitize_key( wp_unslash( $_POST['seeking_type'] ) ) : 'support',
			'schedule_pattern'   => isset( $_POST['schedule_pattern'] ) ? sanitize_key( wp_unslash( $_POST['schedule_pattern'] ) ) : 'flexible',
			'ongoing_or_temp'    => isset( $_POST['ongoing_or_temp'] ) ? sanitize_key( wp_unslash( $_POST['ongoing_or_temp'] ) ) : 'ongoing',
			'funding_management' => isset( $_POST['funding_management'] ) ? sanitize_key( wp_unslash( $_POST['funding_management'] ) ) : 'self',
			'gender_preference'  => isset( $_POST['gender_preference'] ) ? sanitize_key( wp_unslash( $_POST['gender_preference'] ) ) : 'any',
			'contact_mode'       => 'internal-only', // First contact always via internal relay.
			'visibility'         => 'logged_in',
		);
		foreach ( $meta as $k => $v ) {
			update_post_meta( $post_id, $k, $v );
		}

		// Geocode from suburb/state when no client-side coordinates were captured (keyless).
		if ( empty( $meta['location_lat'] ) && empty( $meta['location_lng'] ) ) {
			$geo = Shuffles_SSJ_Geo::geocode_parts( $meta['location_suburb'], $meta['location_state'], '' );
			if ( $geo ) {
				update_post_meta( $post_id, 'location_lat', $geo['lat'] );
				update_post_meta( $post_id, 'location_lng', $geo['lng'] );
			}
		}

		// Support categories + funding sources (one, many, or none).
		if ( ! empty( $_POST['support_categories'] ) && is_array( $_POST['support_categories'] ) ) {
			$ids = array_filter( array_map( 'absint', (array) wp_unslash( $_POST['support_categories'] ) ) );
			wp_set_object_terms( $post_id, $ids, 'sssjt_support_category' );
		}
		if ( ! empty( $_POST['funding_sources'] ) && is_array( $_POST['funding_sources'] ) ) {
			$ids = array_filter( array_map( 'absint', (array) wp_unslash( $_POST['funding_sources'] ) ) );
			wp_set_object_terms( $post_id, $ids, 'sssjt_funding_source' );
		}

		// Culture + language (shared multi-select taxonomies).
		foreach ( array( 'culture' => 'sssjt_culture', 'language' => 'sssjt_language' ) as $clf => $cltax ) {
			$clids = ( ! empty( $_POST[ $clf ] ) && is_array( $_POST[ $clf ] ) ) ? array_filter( array_map( 'absint', (array) wp_unslash( $_POST[ $clf ] ) ) ) : array();
			wp_set_object_terms( $post_id, $clids, $cltax );
		}

		do_action( 'shuffles_ssj_profile_saved', 'need', $post_id, get_current_user_id() );

		wp_safe_redirect( add_query_arg( 'sssj_need', 'pending', $redirect ) );
		exit;
	}

	/**
	 * Process an application to a job or a response to a participant need.
	 */
	public function handle_apply() {
		$nonce = isset( $_POST['sssj_apply_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['sssj_apply_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'sssj_apply' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'shuffles-social-services-jobs' ) );
		}
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Please log in to apply.', 'shuffles-social-services-jobs' ) );
		}

		$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		$job_id   = isset( $_POST['job_id'] ) ? absint( $_POST['job_id'] ) : 0;
		$need_id  = isset( $_POST['need_id'] ) ? absint( $_POST['need_id'] ) : 0;

		if ( ! $job_id && ! $need_id ) {
			wp_safe_redirect( add_query_arg( 'sssj_applied', 'error', $redirect ) );
			exit;
		}

		// Validate the target is published + of the right type, and resolve the engagement basis.
		if ( $need_id ) {
			$post  = get_post( $need_id );
			$valid = ( $post && 'sssj_need' === $post->post_type && 'publish' === $post->post_status );
			$basis = 'abn'; // needs are always ABN
		} else {
			$post  = get_post( $job_id );
			$valid = ( $post && 'sssj_job' === $post->post_type && 'publish' === $post->post_status );
			$basis = ( 'abn' === (string) get_post_meta( $job_id, 'engagement_basis', true ) ) ? 'abn' : 'tfn';
		}
		if ( ! $valid ) {
			wp_safe_redirect( add_query_arg( 'sssj_applied', 'error', $redirect ) );
			exit;
		}

		if ( ! Shuffles_SSJ_Applications::can_respond( $basis ) ) {
			wp_safe_redirect( add_query_arg( 'sssj_applied', 'denied', $redirect ) );
			exit;
		}

		$cover = isset( $_POST['cover_message'] ) ? wp_unslash( $_POST['cover_message'] ) : '';

		// Employee (TFN) applications capture extra detail: résumé, availability, start date,
		// right-to-work, and answers to the employer's per-listing screening questions.
		$extra = array();
		if ( 'tfn' === $basis && $job_id ) {
			$extra['resume_id']     = isset( $_POST['resume_id'] ) ? (int) $_POST['resume_id'] : 0;
			$extra['availability']  = isset( $_POST['availability'] ) ? sanitize_text_field( wp_unslash( $_POST['availability'] ) ) : '';
			$extra['start_date']    = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
			$extra['right_to_work'] = ! empty( $_POST['right_to_work'] ) ? 1 : 0;
			// Right-to-work is required for employee positions.
			if ( ! $extra['right_to_work'] ) {
				wp_safe_redirect( add_query_arg( 'sssj_applied', 'rtw', $redirect ) );
				exit;
			}
			$questions = (array) get_post_meta( $job_id, 'screening_questions', true );
			if ( $questions ) {
				$posted  = isset( $_POST['screening'] ) ? (array) wp_unslash( $_POST['screening'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$answers = array();
				foreach ( $questions as $i => $q ) {
					$answers[] = array(
						'q' => (string) $q,
						'a' => isset( $posted[ $i ] ) ? sanitize_textarea_field( $posted[ $i ] ) : '',
					);
				}
				$extra['screening'] = $answers;
			}
		}

		$res = Shuffles_SSJ_Applications::apply( $job_id, $need_id, $cover, $extra );
		if ( is_wp_error( $res ) ) {
			$code = ( 'dup' === $res->get_error_code() ) ? 'dup' : 'error';
			wp_safe_redirect( add_query_arg( 'sssj_applied', $code, $redirect ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( 'sssj_applied', '1', $redirect ) );
		exit;
	}

	/**
	 * Advertiser/nominee updates an application's status (ownership checked in the service).
	 */
	public function handle_app_status() {
		$nonce = isset( $_POST['sssj_status_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['sssj_status_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'sssj_app_status' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'shuffles-social-services-jobs' ) );
		}
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Please log in.', 'shuffles-social-services-jobs' ) );
		}
		$app_id = isset( $_POST['app_id'] ) ? absint( $_POST['app_id'] ) : 0;
		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		Shuffles_SSJ_Applications::set_status( $app_id, $status, get_current_user_id() );
		$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		wp_safe_redirect( add_query_arg( 'sssj_status', '1', $redirect ) );
		exit;
	}

	/**
	 * Send a reply within an existing messaging thread (sender must be a party to it).
	 */
	public function handle_send_message() {
		$nonce = isset( $_POST['sssj_msg_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['sssj_msg_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'sssj_send_message' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'shuffles-social-services-jobs' ) );
		}
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Please log in.', 'shuffles-social-services-jobs' ) );
		}
		$uid      = get_current_user_id();
		$thread   = isset( $_POST['thread_id'] ) ? absint( $_POST['thread_id'] ) : 0;
		$body     = isset( $_POST['body'] ) ? wp_unslash( $_POST['body'] ) : '';
		$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );

		if ( ! $thread || '' === trim( wp_strip_all_tags( (string) $body ) ) || ! Shuffles_SSJ_Messaging::is_party( $thread, $uid ) ) {
			wp_safe_redirect( add_query_arg( array( 'sssj_thread' => $thread, 'sssj_msg' => 'error' ), $redirect ) );
			exit;
		}
		$last  = Shuffles_SSJ_Messaging::last( $thread );
		$other = ( (int) $last->from_user_id === (int) $uid ) ? (int) $last->to_user_id : (int) $last->from_user_id;
		Shuffles_SSJ_Messaging::send( $uid, $other, $body, (string) $last->context_entity_type, (int) $last->context_entity_id, $thread );
		wp_safe_redirect( add_query_arg( array( 'sssj_thread' => $thread, 'sssj_msg' => '1' ), $redirect ) );
		exit;
	}

	/**
	 * Create/update the user's own organisation profile (one per user). Public + SEO-able.
	 */
	public function handle_post_org() {
		$nonce = isset( $_POST['sssj_org_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['sssj_org_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'sssj_post_org' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'shuffles-social-services-jobs' ) );
		}
		if ( ! is_user_logged_in() || ( ! current_user_can( 'sssj_post_org' ) && ! current_user_can( 'manage_options' ) ) ) {
			wp_die( esc_html__( 'You do not have permission to create an organisation profile.', 'shuffles-social-services-jobs' ) );
		}
		$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		$uid      = get_current_user_id();

		$name = isset( $_POST['org_name'] ) ? sanitize_text_field( wp_unslash( $_POST['org_name'] ) ) : '';
		if ( '' === $name ) {
			wp_safe_redirect( add_query_arg( 'sssj_org', 'error', $redirect ) );
			exit;
		}
		// Organisations are businesses (non-TFN) — a valid ABN is required.
		$abn = isset( $_POST['org_abn'] ) ? preg_replace( '/\D+/', '', (string) wp_unslash( $_POST['org_abn'] ) ) : '';
		if ( '' === $abn || ! Shuffles_SSJ_ABN::is_valid( $abn ) ) {
			wp_safe_redirect( add_query_arg( 'sssj_org', 'abn', $redirect ) );
			exit;
		}

		$post_data = array(
			'post_type'    => 'sssj_org',
			'post_status'  => 'publish',
			'post_title'   => $name,
			'post_content' => isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '',
			'post_author'  => $uid,
		);
		$existing = get_posts(
			array(
				'post_type'      => 'sssj_org',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => 'org_user_id',
				'meta_value'     => $uid,
			)
		);
		if ( ! empty( $existing ) ) {
			$post_data['ID'] = (int) $existing[0];
			$post_id         = wp_update_post( $post_data, true );
		} else {
			$post_id = wp_insert_post( $post_data, true );
		}
		if ( is_wp_error( $post_id ) ) {
			wp_safe_redirect( add_query_arg( 'sssj_org', 'error', $redirect ) );
			exit;
		}

		// Extra locations: one per line "Label | Suburb | State | Postcode".
		$locations = array();
		$raw       = isset( $_POST['locations'] ) ? (string) wp_unslash( $_POST['locations'] ) : '';
		foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			$parts = array_map( 'trim', explode( '|', $line ) );
			$loc   = array(
				'label'    => sanitize_text_field( isset( $parts[0] ) ? $parts[0] : '' ),
				'suburb'   => sanitize_text_field( isset( $parts[1] ) ? $parts[1] : '' ),
				'state'    => sanitize_text_field( isset( $parts[2] ) ? $parts[2] : '' ),
				'postcode' => sanitize_text_field( isset( $parts[3] ) ? $parts[3] : '' ),
			);
			// Geocode each location (keyless) so the org finder can match by radius across all of them.
			$g = Shuffles_SSJ_Geo::geocode_parts( $loc['suburb'], $loc['state'], $loc['postcode'] );
			if ( $g ) {
				$loc['lat'] = $g['lat'];
				$loc['lng'] = $g['lng'];
			}
			$locations[] = $loc;
		}

		$ndis_reg_no = isset( $_POST['ndis_register_id'] ) ? preg_replace( '/\D+/', '', (string) wp_unslash( $_POST['ndis_register_id'] ) ) : '';

		$meta = array(
			'org_user_id'       => $uid,
			'org_hidden'        => empty( $_POST['org_hidden'] ) ? '' : '1',
			'org_category'      => isset( $_POST['org_category'] ) ? sanitize_key( wp_unslash( $_POST['org_category'] ) ) : 'support',
			'org_size'          => isset( $_POST['org_size'] ) ? sanitize_key( wp_unslash( $_POST['org_size'] ) ) : ( ( class_exists( 'Shuffles_SSJ_Roles' ) && Shuffles_SSJ_Roles::is_contractor( $uid ) ) ? 'sole_trader' : '' ),
			'org_structure'     => isset( $_POST['org_structure'] ) ? sanitize_key( wp_unslash( $_POST['org_structure'] ) ) : '',
			'travel_radius_km'  => isset( $_POST['travel_radius_km'] ) ? absint( $_POST['travel_radius_km'] ) : 0,
			// One field now: "NDIS Registration No" (the ?id= value). Mirror it into the legacy
			// ndis_provider_number key so older readers (e.g. the directory badge) keep working.
			'ndis_registered'      => empty( $_POST['ndis_registered'] ) ? '' : '1',
			'ndis_register_id'     => $ndis_reg_no,
			'ndis_provider_number' => $ndis_reg_no,
			'org_abn'           => $abn,
			'org_website'       => isset( $_POST['org_website'] ) ? esc_url_raw( trim( (string) wp_unslash( $_POST['org_website'] ) ) ) : '',
			'org_type'          => isset( $_POST['org_type'] ) ? sanitize_key( wp_unslash( $_POST['org_type'] ) ) : 'employer',
			'org_phone'         => isset( $_POST['org_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['org_phone'] ) ) : '',
			'location_suburb'   => isset( $_POST['location_suburb'] ) ? sanitize_text_field( wp_unslash( $_POST['location_suburb'] ) ) : '',
			'location_state'    => isset( $_POST['location_state'] ) ? sanitize_text_field( wp_unslash( $_POST['location_state'] ) ) : '',
			'location_postcode' => isset( $_POST['location_postcode'] ) ? sanitize_text_field( wp_unslash( $_POST['location_postcode'] ) ) : '',
			'location_lat'      => isset( $_POST['location_lat'] ) ? (float) $_POST['location_lat'] : 0,
			'location_lng'      => isset( $_POST['location_lng'] ) ? (float) $_POST['location_lng'] : 0,
			'locations'         => wp_json_encode( $locations ),
		);
		// Social + profile links (single source of truth).
		foreach ( array_keys( Shuffles_SSJ_Org::networks() ) as $netkey ) {
			$meta[ $netkey ] = isset( $_POST[ $netkey ] ) ? esc_url_raw( trim( (string) wp_unslash( $_POST[ $netkey ] ) ) ) : '';
		}
		foreach ( $meta as $k => $v ) {
			update_post_meta( $post_id, $k, $v );
		}

		// Per-field privacy masking (C4) — "members only" toggles for contact fields.
		if ( class_exists( 'Shuffles_SSJ_Privacy' ) ) {
			Shuffles_SSJ_Privacy::save( $post_id, 'org', isset( $_POST['sssj_mask'] ) ? (array) wp_unslash( $_POST['sssj_mask'] ) : array() ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		// Logo → the org post's featured image (also used by Organization JSON-LD).
		if ( ! empty( $_FILES['org_logo']['name'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			$att = media_handle_upload( 'org_logo', $post_id );
			if ( ! is_wp_error( $att ) ) {
				set_post_thumbnail( $post_id, $att );
			}
		}

		// Geocode the primary location from address parts when none were captured (keyless).
		if ( empty( $meta['location_lat'] ) && empty( $meta['location_lng'] ) ) {
			$geo = Shuffles_SSJ_Geo::geocode_parts( $meta['location_suburb'], $meta['location_state'], $meta['location_postcode'] );
			if ( $geo ) {
				update_post_meta( $post_id, 'location_lat', $geo['lat'] );
				update_post_meta( $post_id, 'location_lng', $geo['lng'] );
			}
		}

		// Sectors covered + funding sources accepted.
		foreach ( array( 'org_sectors' => 'sssjt_category', 'org_funding' => 'sssjt_funding_source' ) as $field => $tax ) {
			$ids = ( ! empty( $_POST[ $field ] ) && is_array( $_POST[ $field ] ) ) ? array_filter( array_map( 'absint', (array) wp_unslash( $_POST[ $field ] ) ) ) : array();
			wp_set_object_terms( $post_id, $ids, $tax );
		}

		if ( '' !== $abn ) {
			do_action( 'shuffles_ssj_abn_recorded', $abn, 'org', $post_id );
		}
		if ( ! empty( $_POST['ndis_registered'] ) ) {
			$ndis_ref = (string) get_post_meta( $post_id, 'ndis_register_id', true );
			if ( '' === trim( $ndis_ref ) ) {
				$ndis_ref = (string) get_post_meta( $post_id, 'ndis_provider_number', true );
			}
			if ( '' !== trim( $ndis_ref ) ) {
				do_action( 'shuffles_ssj_ndis_recorded', $ndis_ref, $post_id );
			}
		}
		// Directory listing eligibility (providers pay to list) — stamped on save (free when monetisation off).
		update_post_meta( $post_id, 'org_listed', ( class_exists( 'Shuffles_SSJ_Monetisation' ) && Shuffles_SSJ_Monetisation::can_list_directory( $uid ) ) ? '1' : '' );

		do_action( 'shuffles_ssj_profile_saved', 'org', $post_id, get_current_user_id() );

		wp_safe_redirect( add_query_arg( 'sssj_org', '1', $redirect ) );
		exit;
	}
}
