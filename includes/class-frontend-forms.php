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
			'rate_min'          => isset( $_POST['rate_min'] ) ? (float) $_POST['rate_min'] : 0,
			'rate_max'          => isset( $_POST['rate_max'] ) ? (float) $_POST['rate_max'] : 0,
			'rate_unit'         => isset( $_POST['rate_unit'] ) ? sanitize_key( wp_unslash( $_POST['rate_unit'] ) ) : 'hour',
			'expires_at'        => isset( $_POST['expires_at'] ) ? sanitize_text_field( wp_unslash( $_POST['expires_at'] ) ) : '',
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

		// ABN recorded → let the Reference Check plugin cross-match later (flag-only).
		if ( 'abn' === $basis && $abn ) {
			do_action( 'shuffles_ssj_abn_recorded', $abn, 'job', $post_id );
		}

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
		if ( ! in_array( $visibility, array( 'public', 'logged_in', 'verified_only' ), true ) ) {
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
		);
		foreach ( $meta as $k => $v ) {
			update_post_meta( $post_id, $k, $v );
		}

		if ( ! empty( $_POST['services'] ) && is_array( $_POST['services'] ) ) {
			$ids = array_filter( array_map( 'absint', (array) wp_unslash( $_POST['services'] ) ) );
			wp_set_object_terms( $post_id, $ids, 'sssjt_category' );
		}

		if ( '' !== $abn ) {
			do_action( 'shuffles_ssj_abn_recorded', $abn, 'worker', $post_id );
		}

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

		// Support categories + funding sources (one, many, or none).
		if ( ! empty( $_POST['support_categories'] ) && is_array( $_POST['support_categories'] ) ) {
			$ids = array_filter( array_map( 'absint', (array) wp_unslash( $_POST['support_categories'] ) ) );
			wp_set_object_terms( $post_id, $ids, 'sssjt_support_category' );
		}
		if ( ! empty( $_POST['funding_sources'] ) && is_array( $_POST['funding_sources'] ) ) {
			$ids = array_filter( array_map( 'absint', (array) wp_unslash( $_POST['funding_sources'] ) ) );
			wp_set_object_terms( $post_id, $ids, 'sssjt_funding_source' );
		}

		wp_safe_redirect( add_query_arg( 'sssj_need', 'pending', $redirect ) );
		exit;
	}
}
