<?php
/**
 * Daily cron — auto-close expired job ads (status -> draft).
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Cron {

	const HOOK = 'shuffles_ssj_daily';

	public function register() {
		add_action( self::HOOK, array( $this, 'close_expired' ) );
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::HOOK );
		}
	}

	/**
	 * Move job ads whose expires_at is in the past to draft.
	 */
	public function close_expired() {
		$q = new WP_Query(
			array(
				'post_type'      => 'sssj_job',
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array(
					array(
						'key'     => 'expires_at',
						'value'   => current_time( 'Y-m-d' ),
						'compare' => '<',
						'type'    => 'DATE',
					),
				),
			)
		);
		foreach ( $q->posts as $id ) {
			wp_update_post(
				array(
					'ID'          => (int) $id,
					'post_status' => 'draft',
				)
			);
		}
	}
}
