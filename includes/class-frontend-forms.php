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

		$meta = array(
			'engagement_basis'  => $basis,
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
		);
		foreach ( $meta as $k => $v ) {
			update_post_meta( $post_id, $k, $v );
		}

		if ( ! empty( $_POST['category'] ) ) {
			$term_id = (int) $_POST['category'];
			if ( $term_id > 0 ) {
				wp_set_object_terms( $post_id, array( $term_id ), 'sssjt_category' );
			}
		}
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
			'years_experience'  => isset( $_POST['years_experience'] ) ? absint( $_POST['years_experience'] ) : 0,
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
		$res   = Shuffles_SSJ_Applications::apply( $job_id, $need_id, $cover );
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
		$abn = isset( $_POST['org_abn'] ) ? preg_replace( '/\D+/', '', (string) wp_unslash( $_POST['org_abn'] ) ) : '';
		if ( '' !== $abn && ! Shuffles_SSJ_ABN::is_valid( $abn ) ) {
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

		$meta = array(
			'org_user_id'       => $uid,
			'org_hidden'        => empty( $_POST['org_hidden'] ) ? '' : '1',
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

		do_action( 'shuffles_ssj_profile_saved', 'org', $post_id, get_current_user_id() );

		wp_safe_redirect( add_query_arg( 'sssj_org', '1', $redirect ) );
		exit;
	}
}
