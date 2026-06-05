<?php
/**
 * Monetisation gates — the two end-user subscriptions (distinct from the resale licence).
 *
 * 1. Employer advertising subscription — free tier posts up to `free_active_listings`;
 *    a subscriber (PMPro level or filter) posts unlimited.
 * 2. Provider application-fee subscription — required to respond to participant needs / ABN tasks
 *    (rides the existing `shuffles_ssj_can_respond` filter).
 *
 * All gates are OFF unless `monetisation_enabled` is on, so a site is never accidentally locked.
 * Everything is filterable so FluentCart / custom logic can plug in. Enforced server-side.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Monetisation {

	public static function register() {
		add_filter( 'shuffles_ssj_can_respond', array( __CLASS__, 'gate_respond' ), 20, 3 );
	}

	private static function settings() {
		return new Shuffles_SSJ_Settings();
	}

	public static function enabled() {
		return '1' === (string) self::settings()->get( 'monetisation_enabled', '0' );
	}

	private static function has_pmpro_level( $uid, $level ) {
		$level = (int) $level;
		return $level > 0 && function_exists( 'pmpro_hasMembershipLevel' ) && pmpro_hasMembershipLevel( $level, (int) $uid );
	}

	/** Does the user hold the employer advertising subscription? */
	public static function has_advertiser_sub( $uid ) {
		$ok = self::has_pmpro_level( $uid, self::settings()->get( 'advertiser_pmpro_level', 0 ) )
			|| ( class_exists( 'Shuffles_SSJ_License' ) && Shuffles_SSJ_License::is_pro() );
		return (bool) apply_filters( 'shuffles_ssj_has_advertiser_sub', $ok, (int) $uid );
	}

	/** Does the user hold the provider application-fee subscription? */
	public static function has_provider_sub( $uid ) {
		$ok = self::has_pmpro_level( $uid, self::settings()->get( 'provider_pmpro_level', 0 ) )
			|| ( class_exists( 'Shuffles_SSJ_License' ) && Shuffles_SSJ_License::is_pro() );
		return (bool) apply_filters( 'shuffles_ssj_has_provider_sub', $ok, (int) $uid );
	}

	/**
	 * May this user post another job? (free-tier listing cap unless subscribed).
	 *
	 * @return bool
	 */
	public static function can_post_job( $uid ) {
		if ( ! self::enabled() ) {
			return true;
		}
		$uid = (int) $uid;
		if ( ! $uid ) {
			return false;
		}
		if ( user_can( $uid, 'manage_options' ) || self::has_advertiser_sub( $uid ) ) {
			return (bool) apply_filters( 'shuffles_ssj_can_post_job', true, $uid );
		}
		$free = (int) self::settings()->get( 'free_active_listings', 1 );
		if ( $free <= 0 ) {
			return (bool) apply_filters( 'shuffles_ssj_can_post_job', true, $uid ); // 0 = unlimited free
		}
		$active = count(
			get_posts(
				array(
					'post_type'      => 'sssj_job',
					'post_status'    => 'publish',
					'author'         => $uid,
					'posts_per_page' => $free + 1,
					'fields'         => 'ids',
				)
			)
		);
		return (bool) apply_filters( 'shuffles_ssj_can_post_job', $active < $free, $uid );
	}

	/** Short reason for the upgrade prompt. */
	public static function post_job_block_reason() {
		$free = (int) self::settings()->get( 'free_active_listings', 1 );
		/* translators: %d: number of free active listings */
		return sprintf( _n( 'You have reached your free limit of %d active job. Subscribe to post more.', 'You have reached your free limit of %d active jobs. Subscribe to post more.', $free, 'shuffles-social-services-jobs' ), $free );
	}

	/**
	 * Gate responding to participant needs / ABN tasks behind the provider subscription.
	 * Only tightens (never loosens) — runs after the ABN check.
	 *
	 * @param bool   $ok    Result so far (ABN/login checks).
	 * @param string $basis 'abn' | 'tfn'.
	 * @param int    $uid   User.
	 * @return bool
	 */
	public static function gate_respond( $ok, $basis, $uid ) {
		if ( ! $ok || ! self::enabled() || 'abn' !== $basis ) {
			return $ok;
		}
		if ( user_can( (int) $uid, 'manage_options' ) ) {
			return true;
		}
		return self::has_provider_sub( $uid );
	}
}
