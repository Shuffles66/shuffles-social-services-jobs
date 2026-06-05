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
}
