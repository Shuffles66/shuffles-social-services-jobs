<?php
/**
 * Daily cron. Sends a "closing soon" pre-reminder, auto-closes listings past their end date
 * (status to draft) and emails the owner so they can reopen ("rebirth") if still needed. Covers
 * job ads and participant requests. Also re-syncs featured placement and sweeps credentials.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Cron {

	const HOOK = 'shuffles_ssj_daily';

	/** How many days before the end date the "closing soon" reminder goes out. */
	public static function pre_window_days() {
		return (int) apply_filters( 'shuffles_ssj_expiry_reminder_days', 7 );
	}

	public function register() {
		add_action( self::HOOK, array( $this, 'close_expired' ) );
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::HOOK );
		}
	}

	/**
	 * Daily sweep: pre-reminders, auto-close, owner notifications; then featured + credential upkeep.
	 */
	public function close_expired() {
		$this->process_listing_expiry( 'sssj_job' );
		$this->process_listing_expiry( 'sssj_need' );

		// Re-sync featured placement so a lapsed/added advertiser subscription is reflected.
		if ( class_exists( 'Shuffles_SSJ_Monetisation' ) ) {
			Shuffles_SSJ_Monetisation::sync_featured();
		}

		// Expire lapsed credentials, refresh verified badges, send renewal reminders.
		if ( class_exists( 'Shuffles_SSJ_Credentials' ) ) {
			Shuffles_SSJ_Credentials::expiry_sweep();
		}
	}

	/**
	 * For one listing type: warn owners whose listing closes within the reminder window, then
	 * auto-close anything already past its end date and tell the owner how to reopen it.
	 *
	 * @param string $post_type sssj_job | sssj_need.
	 */
	private function process_listing_expiry( $post_type ) {
		$today = current_time( 'Y-m-d' );
		$soon  = gmdate( 'Y-m-d', strtotime( $today . ' +' . max( 1, self::pre_window_days() ) . ' days' ) );

		// 1) Pre-expiry reminder: closes within the window and not yet reminded this cycle.
		$pre = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => 'expires_at',
						'value'   => array( $today, $soon ),
						'compare' => 'BETWEEN',
						'type'    => 'DATE',
					),
					array(
						'key'     => '_sssj_pre_reminded',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);
		foreach ( $pre as $id ) {
			$this->notify_owner( (int) $id, 'expiring' );
			update_post_meta( (int) $id, '_sssj_pre_reminded', '1' );
		}

		// 2) Auto-close: past the end date -> draft, then notify the owner with a reopen prompt.
		$exp = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array(
					array(
						'key'     => 'expires_at',
						'value'   => $today,
						'compare' => '<',
						'type'    => 'DATE',
					),
				),
			)
		);
		foreach ( $exp as $id ) {
			wp_update_post( array( 'ID' => (int) $id, 'post_status' => 'draft' ) );
			$this->notify_owner( (int) $id, 'closed' );
		}
	}

	/**
	 * Email a listing's owner that it is closing soon, or has closed (with a reopen prompt).
	 * Suppressible via the shuffles_ssj_send_listing_email filter.
	 *
	 * @param int    $id    Listing post id.
	 * @param string $state 'expiring' | 'closed'.
	 */
	private function notify_owner( $id, $state ) {
		if ( ! apply_filters( 'shuffles_ssj_send_listing_email', true, $id, $state ) ) {
			return;
		}
		$author = (int) get_post_field( 'post_author', $id );
		$user   = $author ? get_user_by( 'id', $author ) : null;
		if ( ! $user || ! $user->user_email ) {
			return;
		}
		$title = get_the_title( $id );
		$dash  = class_exists( 'Shuffles_SSJ_Shortcodes' )
			? Shuffles_SSJ_Shortcodes::page_link( 'page_my_listings', '[sssj_dashboard]' )
			: home_url( '/' );
		if ( '' === (string) $dash ) {
			$dash = home_url( '/' );
		}

		if ( 'expiring' === $state ) {
			$when    = (string) get_post_meta( $id, 'expires_at', true );
			$subject = sprintf( __( 'Closing soon: %s', 'shuffles-social-services-jobs' ), $title );
			$body    = sprintf(
				/* translators: 1: listing title, 2: end date, 3: dashboard URL */
				__( "Your listing \"%1\$s\" is set to close on %2\$s.\n\nStill need it? You can reopen or extend it any time from your dashboard:\n%3\$s\n\nIf it has been filled, you can simply let it close.", 'shuffles-social-services-jobs' ),
				$title,
				$when,
				$dash
			);
		} else {
			$subject = sprintf( __( 'Now closed: %s', 'shuffles-social-services-jobs' ), $title );
			$body    = sprintf(
				/* translators: 1: listing title, 2: dashboard URL */
				__( "Your listing \"%1\$s\" has reached its end date and is now closed.\n\nYou can reopen it any time (this gives it a fresh end date) from your dashboard:\n%2\$s", 'shuffles-social-services-jobs' ),
				$title,
				$dash
			);
		}
		wp_mail( $user->user_email, $subject, $body );
	}
}
