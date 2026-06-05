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

	/* ------------------------------------------------------------------ *
	 * Featured placement — the "+ featured" half of the advertiser sub.
	 * Boards already sort `menu_order DESC, date DESC`, so a featured job
	 * (menu_order = 1) floats above the rest with NO change to the query
	 * layer. We keep `is_promoted` meta (for the badge) and `menu_order`
	 * synced to live subscription status at post time and on the daily cron.
	 * ------------------------------------------------------------------ */

	/**
	 * Is this job featured? Featured = the AUTHOR holds the employer advertising subscription.
	 *
	 * Deliberately does NOT honour the site-wide resale licence (`License::is_pro`) — that
	 * would feature EVERY job on a licensed site, defeating the purpose. This is a
	 * per-advertiser distinction only (PMPro level, admin, or the filter override).
	 *
	 * @param int $job_id Job post ID.
	 * @return bool
	 */
	public static function is_job_featured( $job_id ) {
		if ( ! self::enabled() ) {
			return false;
		}
		$author = (int) get_post_field( 'post_author', (int) $job_id );
		if ( ! $author ) {
			return false;
		}
		$featured = user_can( $author, 'manage_options' )
			|| self::has_pmpro_level( $author, self::settings()->get( 'advertiser_pmpro_level', 0 ) )
			|| (bool) apply_filters( 'shuffles_ssj_has_advertiser_sub', false, $author );
		return (bool) apply_filters( 'shuffles_ssj_is_job_featured', (bool) $featured, (int) $job_id, $author );
	}

	/**
	 * Sync a single job's featured state into `is_promoted` meta + `menu_order`.
	 * menu_order is written directly (not via wp_update_post) to avoid re-firing save
	 * hooks and bumping post_modified on the daily refresh.
	 *
	 * @param int $job_id Job post ID.
	 * @return bool The resolved featured state.
	 */
	public static function refresh_job_feature_flag( $job_id ) {
		$job_id = (int) $job_id;
		if ( ! $job_id ) {
			return false;
		}
		$featured = self::is_job_featured( $job_id );
		update_post_meta( $job_id, 'is_promoted', $featured ? 1 : 0 );

		$desired = $featured ? 1 : 0;
		if ( (int) get_post_field( 'menu_order', $job_id ) !== $desired ) {
			global $wpdb;
			$wpdb->update( $wpdb->posts, array( 'menu_order' => $desired ), array( 'ID' => $job_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			clean_post_cache( $job_id );
		}
		return $featured;
	}

	/**
	 * Daily refresh of every published job's featured state, so a lapsed subscription
	 * un-features and a new one features without a re-save. Batched.
	 *
	 * @param int $limit Max jobs to process.
	 */
	public static function sync_featured( $limit = 500 ) {
		$ids = get_posts(
			array(
				'post_type'      => 'sssj_job',
				'post_status'    => 'publish',
				'posts_per_page' => (int) $limit,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $ids as $id ) {
			self::refresh_job_feature_flag( $id );
		}
	}
}
