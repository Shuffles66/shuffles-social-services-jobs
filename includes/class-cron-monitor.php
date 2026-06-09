<?php
/**
 * Cron monitor, tracks this plugin's scheduled (WP-Cron) jobs so the admin can see, per job:
 * frequency, next run due, last run, and whether the last run completed or errored.
 *
 * WP-Cron stores neither last-run times nor errors, so we record them: a PHP_INT_MIN recorder
 * stamps "started" when a hook fires and a PHP_INT_MAX recorder stamps "finished" after all
 * callbacks complete. If a run starts but never finishes (a fatal mid-run), started > finished
 * and we surface "Did not complete". Jobs may also record an explicit note via record_note().
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Cron_Monitor {

	const OPTION = 'sssj_cron_runs';

	/** The plugin's scheduled jobs: hook => [ label, desc ]. */
	public static function jobs() {
		return array(
			'shuffles_ssj_daily' => array(
				'label' => __( 'Daily maintenance', 'shuffles-social-services-jobs' ),
				'desc'  => __( 'Closes expired job ads, refreshes featured placement, runs the credential-expiry sweep (reminder emails + dropping a lapsed verified badge), and re-validates the resale licence.', 'shuffles-social-services-jobs' ),
			),
			'sssj_alerts_daily'  => array(
				'label' => __( 'Daily email alerts', 'shuffles-social-services-jobs' ),
				'desc'  => __( 'Sends worker job-match alerts, advertiser new-candidate alerts, and saved-search digests.', 'shuffles-social-services-jobs' ),
			),
			'sssj_ndis_monthly'  => array(
				'label' => __( 'Monthly NDIS register check', 'shuffles-social-services-jobs' ),
				'desc'  => __( 'Re-reads each registered organisation’s NDIS Commission listing and emails staff if the status, registration groups, or expiry date change.', 'shuffles-social-services-jobs' ),
			),
		);
	}

	public static function register() {
		foreach ( array_keys( self::jobs() ) as $hook ) {
			add_action( $hook, array( __CLASS__, 'mark_start' ), PHP_INT_MIN );
			add_action( $hook, array( __CLASS__, 'mark_finish' ), PHP_INT_MAX );
		}
		add_action( 'admin_post_sssj_cron_run', array( __CLASS__, 'handle_run_now' ) );
	}

	/* ------------------------------------------------------------- Recording */

	protected static function log() {
		$l = get_option( self::OPTION, array() );
		return is_array( $l ) ? $l : array();
	}

	protected static function save( $l ) {
		update_option( self::OPTION, $l, false );
	}

	/** Which hook is currently firing (the action name). */
	protected static function current_hook() {
		return (string) current_action();
	}

	public static function mark_start() {
		$hook = self::current_hook();
		if ( '' === $hook ) {
			return;
		}
		$l                      = self::log();
		$l[ $hook ]['started']  = time();
		$l[ $hook ]['note']     = '';
		$l[ $hook ]['status']   = 'running';
		self::save( $l );
	}

	public static function mark_finish() {
		$hook = self::current_hook();
		if ( '' === $hook ) {
			return;
		}
		$l                      = self::log();
		$l[ $hook ]['finished'] = time();
		if ( 'failed' !== ( isset( $l[ $hook ]['status'] ) ? $l[ $hook ]['status'] : '' ) ) {
			$l[ $hook ]['status'] = 'ok';
		}
		self::save( $l );
	}

	/**
	 * A running job can record a human note / status that shows on the Cron Jobs tab.
	 *
	 * @param string $hook   Cron hook name.
	 * @param string $note   Short human message (e.g. "12 checked, 1 could not be read").
	 * @param string $status ok | failed.
	 */
	public static function record_note( $hook, $note, $status = 'ok' ) {
		$l                    = self::log();
		$l[ $hook ]['note']   = (string) $note;
		$l[ $hook ]['status'] = ( 'failed' === $status ) ? 'failed' : 'ok';
		self::save( $l );
	}

	/* ------------------------------------------------------------- Reporting */

	/** Human label for a schedule slug. */
	protected static function frequency_label( $slug ) {
		if ( ! $slug ) {
			return __( 'Not scheduled', 'shuffles-social-services-jobs' );
		}
		$all = wp_get_schedules();
		if ( isset( $all[ $slug ]['display'] ) ) {
			return $all[ $slug ]['display'];
		}
		return ucfirst( str_replace( '_', ' ', $slug ) );
	}

	/**
	 * Status rows for the admin tab.
	 *
	 * @return array[] each: hook, label, desc, frequency, next (ts|0), last (ts|0), state, note.
	 */
	public static function rows() {
		$log  = self::log();
		$rows = array();
		foreach ( self::jobs() as $hook => $meta ) {
			$entry    = isset( $log[ $hook ] ) ? $log[ $hook ] : array();
			$started  = isset( $entry['started'] ) ? (int) $entry['started'] : 0;
			$finished = isset( $entry['finished'] ) ? (int) $entry['finished'] : 0;
			$note     = isset( $entry['note'] ) ? (string) $entry['note'] : '';
			$recorded = isset( $entry['status'] ) ? (string) $entry['status'] : '';

			$last = max( $started, $finished );

			if ( 'failed' === $recorded ) {
				$state = 'failed';
			} elseif ( ! $started && ! $finished ) {
				$state = 'never'; // scheduled but hasn't fired yet on this site
			} elseif ( $started > $finished ) {
				$state = 'incomplete'; // started but never finished → likely a fatal mid-run
			} else {
				$state = 'ok';
			}

			$rows[] = array(
				'hook'      => $hook,
				'label'     => $meta['label'],
				'desc'      => $meta['desc'],
				'frequency' => self::frequency_label( wp_get_schedule( $hook ) ),
				'next'      => (int) wp_next_scheduled( $hook ),
				'last'      => $last,
				'state'     => $state,
				'note'      => $note,
			);
		}
		return $rows;
	}

	/* ------------------------------------------------------------- Run now */

	public static function handle_run_now() {
		$hook = isset( $_GET['hook'] ) ? sanitize_text_field( wp_unslash( $_GET['hook'] ) ) : '';
		if ( ! current_user_can( 'manage_options' ) || ! array_key_exists( $hook, self::jobs() ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_cron_run_' . $hook );
		do_action( $hook );
		wp_safe_redirect( add_query_arg( array( 'page' => 'shuffles-ssj', 'tab' => 'cron', 'sssj_cron_ran' => rawurlencode( $hook ) ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
