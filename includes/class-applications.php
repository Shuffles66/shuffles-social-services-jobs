<?php
/**
 * Applications service — apply to a job / respond to a participant need.
 *
 * Gating: TFN jobs accept any logged-in member; ABN jobs and participant needs (always ABN)
 * require the responder to hold a recorded, checksum-valid ABN. A `shuffles_ssj_can_respond`
 * filter lets the provider application-subscription plug in later. Duplicate applications are
 * prevented both here and by the uniq_app DB key.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Applications {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sssj_application';
	}

	/** The current user's recorded ABN (from their worker profile), or ''. */
	public static function current_user_abn() {
		$uid = get_current_user_id();
		if ( ! $uid ) {
			return '';
		}
		$w = self::worker_id_for_user( $uid );
		return $w ? (string) get_post_meta( $w, 'worker_abn', true ) : '';
	}

	/** The current user's worker-profile post ID, or 0. */
	public static function worker_id_for_user( $uid ) {
		$ids = get_posts(
			array(
				'post_type'      => 'sssj_worker',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => 'worker_user_id',
				'meta_value'     => (int) $uid,
			)
		);
		return $ids ? (int) $ids[0] : 0;
	}

	/**
	 * May the current user respond on this engagement basis?
	 *
	 * @param string $basis 'abn' | 'tfn'
	 * @return bool
	 */
	public static function can_respond( $basis ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$ok = true;
		if ( 'abn' === $basis ) {
			$abn = self::current_user_abn();
			$ok  = ( '' !== $abn && Shuffles_SSJ_ABN::is_valid( $abn ) );
		}
		/**
		 * Allow the provider application-subscription (and other gates) to veto responding.
		 *
		 * @param bool   $ok    Whether the ABN/login checks passed.
		 * @param string $basis Engagement basis.
		 * @param int    $uid   Current user ID.
		 */
		return (bool) apply_filters( 'shuffles_ssj_can_respond', $ok, $basis, get_current_user_id() );
	}

	/** Has this user already applied to this job/need? */
	public static function already_applied( $job_id, $need_id, $uid ) {
		global $wpdb;
		$t = self::table();
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$id = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$t} WHERE job_id = %d AND need_id = %d AND applicant_user_id = %d LIMIT 1", (int) $job_id, (int) $need_id, (int) $uid )
		);
		return ! empty( $id );
	}

	/**
	 * Record an application. Returns the new row ID or WP_Error.
	 *
	 * @param int    $job_id  0 for a need response.
	 * @param int    $need_id 0 for a job application.
	 * @param string $cover   Cover message.
	 * @return int|WP_Error
	 */
	public static function apply( $job_id, $need_id, $cover ) {
		global $wpdb;
		$uid = get_current_user_id();
		if ( ! $uid ) {
			return new WP_Error( 'auth', __( 'Please log in.', 'shuffles-social-services-jobs' ) );
		}
		if ( self::already_applied( $job_id, $need_id, $uid ) ) {
			return new WP_Error( 'dup', __( 'You have already applied.', 'shuffles-social-services-jobs' ) );
		}
		$now = current_time( 'mysql' );
		$ok  = $wpdb->insert(
			self::table(),
			array(
				'job_id'            => (int) $job_id,
				'need_id'           => (int) $need_id,
				'worker_id'         => self::worker_id_for_user( $uid ),
				'applicant_user_id' => (int) $uid,
				'cover_message'     => wp_kses_post( $cover ),
				'status'            => 'new',
				'created_at'        => $now,
				'updated_at'        => $now,
			),
			array( '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
		);
		if ( false === $ok ) {
			return new WP_Error( 'db', __( 'Could not record your application.', 'shuffles-social-services-jobs' ) );
		}
		// Capture the new row ID BEFORE any further query — update_post_meta() below runs its
		// own INSERT and would otherwise clobber $wpdb->insert_id.
		$app_id = (int) $wpdb->insert_id;
		if ( $job_id ) {
			update_post_meta( $job_id, 'apply_count', (int) get_post_meta( $job_id, 'apply_count', true ) + 1 );
		}
		return $app_id;
	}

	/** Applications submitted by a user (applicant view). */
	public static function for_applicant( $uid ) {
		global $wpdb;
		$t = self::table();
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE applicant_user_id = %d ORDER BY created_at DESC", (int) $uid ) );
	}

	/** Applications for a given entity (advertiser/nominee view). */
	public static function for_entity( $type, $entity_id ) {
		global $wpdb;
		$t   = self::table();
		$col = 'need' === $type ? 'need_id' : 'job_id';
		// phpcs:ignore WordPress.DB.PreparedSQL
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE {$col} = %d ORDER BY created_at DESC", (int) $entity_id ) );
	}

	/** Allowed application statuses. */
	public static function statuses() {
		return array( 'new', 'viewed', 'shortlisted', 'interview', 'offer', 'rejected', 'withdrawn' );
	}

	/**
	 * Update an application's status, but only if $uid owns the linked job/need.
	 *
	 * @return bool
	 */
	public static function set_status( $app_id, $status, $uid ) {
		global $wpdb;
		if ( ! in_array( $status, self::statuses(), true ) ) {
			return false;
		}
		$t = self::table();
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT job_id, need_id FROM {$t} WHERE id = %d", (int) $app_id ) );
		if ( ! $row ) {
			return false;
		}
		$entity_id = $row->job_id ? (int) $row->job_id : (int) $row->need_id;
		$owner     = (int) get_post_field( 'post_author', $entity_id );
		if ( $owner !== (int) $uid && ! user_can( $uid, 'manage_options' ) ) {
			return false;
		}
		return (bool) $wpdb->update(
			$t,
			array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => (int) $app_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}
}
