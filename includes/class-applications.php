<?php
/**
 * Applications service, apply to a job / respond to a participant need.
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
	public static function apply( $job_id, $need_id, $cover, $extra = array() ) {
		global $wpdb;
		$uid = get_current_user_id();
		if ( ! $uid ) {
			return new WP_Error( 'auth', __( 'Please log in.', 'shuffles-social-services-jobs' ) );
		}
		if ( self::already_applied( $job_id, $need_id, $uid ) ) {
			return new WP_Error( 'dup', __( 'You have already applied.', 'shuffles-social-services-jobs' ) );
		}
		// Verify a chosen résumé actually belongs to this applicant.
		$resume_id = isset( $extra['resume_id'] ) ? (int) $extra['resume_id'] : 0;
		if ( $resume_id && class_exists( 'Shuffles_SSJ_Resumes' ) ) {
			$r = Shuffles_SSJ_Resumes::get( $resume_id );
			if ( ! $r || (int) $r->user_id !== (int) $uid ) {
				$resume_id = 0;
			}
		}
		unset( $extra['resume_id'] ); // stored in its own column
		$now = current_time( 'mysql' );
		$ok  = $wpdb->insert(
			self::table(),
			array(
				'job_id'            => (int) $job_id,
				'need_id'           => (int) $need_id,
				'worker_id'         => self::worker_id_for_user( $uid ),
				'applicant_user_id' => (int) $uid,
				'cover_message'     => wp_kses_post( $cover ),
				'resume_id'         => $resume_id,
				'extra'             => ! empty( $extra ) ? wp_json_encode( $extra ) : null,
				'status'            => 'new',
				'created_at'        => $now,
				'updated_at'        => $now,
			),
			array( '%d', '%d', '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s' )
		);
		if ( false === $ok ) {
			return new WP_Error( 'db', __( 'Could not record your application.', 'shuffles-social-services-jobs' ) );
		}
		// Capture the new row ID BEFORE any further query, update_post_meta() below runs its
		// own INSERT and would otherwise clobber $wpdb->insert_id.
		$app_id = (int) $wpdb->insert_id;
		if ( $job_id ) {
			update_post_meta( $job_id, 'apply_count', (int) get_post_meta( $job_id, 'apply_count', true ) + 1 );
		}
		// Seed a messaging thread to the listing owner (relay, emails never exposed).
		if ( class_exists( 'Shuffles_SSJ_Messaging' ) ) {
			$entity = $job_id ? (int) $job_id : (int) $need_id;
			$owner  = (int) get_post_field( 'post_author', $entity );
			if ( $owner && $owner !== (int) $uid ) {
				Shuffles_SSJ_Messaging::send( (int) $uid, $owner, $cover, $job_id ? 'job' : 'need', $entity );
			}
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

	/** True if $viewer owns a job/need that this résumé was submitted to (lets them view it). */
	public static function employer_can_view_resume( $resume_id, $viewer_id ) {
		global $wpdb;
		$resume_id = (int) $resume_id;
		$viewer_id = (int) $viewer_id;
		if ( ! $resume_id || ! $viewer_id ) {
			return false;
		}
		$t    = self::table();
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT job_id, need_id FROM {$t} WHERE resume_id = %d", $resume_id ) ); // phpcs:ignore WordPress.DB
		foreach ( (array) $rows as $row ) {
			$entity = (int) $row->job_id ? (int) $row->job_id : (int) $row->need_id;
			if ( $entity && (int) get_post_field( 'post_author', $entity ) === $viewer_id ) {
				return true;
			}
		}
		return false;
	}

	/** Decoded extra answers (availability, start date, right-to-work, screening) for an application row. */
	public static function extra( $row ) {
		if ( is_object( $row ) && isset( $row->extra ) && $row->extra ) {
			$d = json_decode( (string) $row->extra, true );
			return is_array( $d ) ? $d : array();
		}
		return array();
	}

	/** Allowed application statuses (full pipeline). */
	public static function statuses() {
		return array( 'new', 'viewed', 'shortlisted', 'interview', 'offer', 'hired', 'declined', 'withdrawn' );
	}

	/** Statuses an employer can set, given the job's chosen application-handling mode. */
	public static function statuses_for_mode( $mode ) {
		if ( 'simple' === $mode ) {
			return array( 'new', 'viewed', 'declined' );
		}
		return self::statuses();
	}

	/** Human label for a status (handles the legacy 'rejected'). */
	public static function status_label( $status ) {
		$map = array(
			'new'         => __( 'New', 'shuffles-social-services-jobs' ),
			'viewed'      => __( 'Viewed', 'shuffles-social-services-jobs' ),
			'shortlisted' => __( 'Shortlisted', 'shuffles-social-services-jobs' ),
			'interview'   => __( 'Interview', 'shuffles-social-services-jobs' ),
			'offer'       => __( 'Offer', 'shuffles-social-services-jobs' ),
			'hired'       => __( 'Hired', 'shuffles-social-services-jobs' ),
			'declined'    => __( 'Declined', 'shuffles-social-services-jobs' ),
			'rejected'    => __( 'Declined', 'shuffles-social-services-jobs' ), // legacy alias
			'withdrawn'   => __( 'Withdrawn', 'shuffles-social-services-jobs' ),
		);
		return isset( $map[ $status ] ) ? $map[ $status ] : ucfirst( (string) $status );
	}

	/** Status-change history for an application (oldest first): [ s, at, by ]. */
	public static function history( $row ) {
		$ex = self::extra( $row );
		return ( ! empty( $ex['history'] ) && is_array( $ex['history'] ) ) ? $ex['history'] : array();
	}

	/**
	 * Update an application's status, but only if $uid owns the linked job/need. Records a
	 * history entry and emails the applicant.
	 *
	 * @return bool
	 */
	public static function set_status( $app_id, $status, $uid ) {
		global $wpdb;
		if ( ! in_array( $status, self::statuses(), true ) ) {
			return false;
		}
		$t   = self::table();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id = %d", (int) $app_id ) ); // phpcs:ignore WordPress.DB
		if ( ! $row ) {
			return false;
		}
		$entity_id = $row->job_id ? (int) $row->job_id : (int) $row->need_id;
		$owner     = (int) get_post_field( 'post_author', $entity_id );
		if ( $owner !== (int) $uid && ! user_can( $uid, 'manage_options' ) ) {
			return false;
		}
		if ( (string) $row->status === $status ) {
			return true; // no change
		}
		$extra              = self::extra( $row );
		$extra['history'][] = array( 's' => $status, 'at' => current_time( 'mysql' ), 'by' => (int) $uid );
		$ok = (bool) $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$t,
			array( 'status' => $status, 'extra' => wp_json_encode( $extra ), 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => (int) $app_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
		if ( $ok ) {
			self::notify_applicant( $row, $status );
		}
		return $ok;
	}

	/** The applicant withdraws their own application. */
	public static function withdraw( $app_id, $uid ) {
		global $wpdb;
		$t   = self::table();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id = %d", (int) $app_id ) ); // phpcs:ignore WordPress.DB
		if ( ! $row || (int) $row->applicant_user_id !== (int) $uid ) {
			return false;
		}
		if ( in_array( (string) $row->status, array( 'withdrawn', 'hired' ), true ) ) {
			return true;
		}
		$extra              = self::extra( $row );
		$extra['history'][] = array( 's' => 'withdrawn', 'at' => current_time( 'mysql' ), 'by' => (int) $uid );
		return (bool) $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$t,
			array( 'status' => 'withdrawn', 'extra' => wp_json_encode( $extra ), 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => (int) $app_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	/** Email the applicant when an advertiser changes their application status (suppress via filter). */
	private static function notify_applicant( $row, $status ) {
		if ( ! apply_filters( 'shuffles_ssj_send_application_email', true, $row, $status ) ) {
			return;
		}
		$u = (int) $row->applicant_user_id ? get_user_by( 'id', (int) $row->applicant_user_id ) : null;
		if ( ! $u || ! $u->user_email ) {
			return;
		}
		$entity_id = $row->job_id ? (int) $row->job_id : (int) $row->need_id;
		$title     = get_the_title( $entity_id );
		$subject   = sprintf( __( 'Update on your application: %s', 'shuffles-social-services-jobs' ), $title );
		$body      = sprintf(
			/* translators: 1: listing title, 2: new status label */
			__( "Your application for \"%1\$s\" is now: %2\$s.\n\nLog in to your dashboard to see the details.", 'shuffles-social-services-jobs' ),
			$title,
			self::status_label( $status )
		);
		wp_mail( $u->user_email, $subject, $body );
	}
}
