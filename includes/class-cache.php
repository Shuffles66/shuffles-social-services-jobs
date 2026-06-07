<?php
/**
 * Cache busting. When a job, worker, organisation or participant request is created or updated,
 * clear the common page-cache layers so the change is immediately visible (no waiting for a TTL
 * or a manual purge). Works with whatever cache is in use; each call is a no-op if that cache is
 * not present. Fires a shuffles_ssj_purge_cache action so a host-specific purge can be hooked too.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Cache {

	/** Hook content saves so updates bypass the cache straight away. */
	public static function register() {
		foreach ( array( 'sssj_job', 'sssj_worker', 'sssj_org', 'sssj_need' ) as $pt ) {
			add_action( 'save_post_' . $pt, array( __CLASS__, 'on_save' ), 20, 3 );
		}
		// Status flips (auto-close, reopen, moderation publish) also change what visitors see.
		add_action( 'transition_post_status', array( __CLASS__, 'on_transition' ), 20, 3 );
	}

	/** Save handler: skip revisions/autosaves, then purge. */
	public static function on_save( $post_id, $post = null, $update = null ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		self::purge();
	}

	/** Purge when one of our CPTs changes published state. */
	public static function on_transition( $new, $old, $post ) {
		if ( $new === $old ) {
			return;
		}
		if ( $post && in_array( $post->post_type, array( 'sssj_job', 'sssj_worker', 'sssj_org', 'sssj_need' ), true ) ) {
			self::purge();
		}
	}

	/**
	 * Clear every page-cache layer we can reach. Each branch is guarded, so it is a safe no-op when
	 * that cache is not installed. Covers the popular plugins and the host page cache (Varnish via
	 * the Cloudways cache plugin).
	 */
	public static function purge() {
		// WordPress object cache.
		if ( function_exists( 'wp_cache_flush' ) ) {
			// Avoid flushing the whole object cache on busy sites; only do it for page-cache plugins
			// that key off it. Most of the calls below handle their own page cache.
			wp_cache_flush();
		}
		// WP Rocket.
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		// W3 Total Cache.
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}
		// WP Super Cache.
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}
		// LiteSpeed Cache.
		do_action( 'litespeed_purge_all' );
		// Cloudways Breeze (also purges the Varnish full-page cache on Cloudways).
		do_action( 'breeze_clear_all_cache' );
		// WP Fastest Cache.
		do_action( 'wpfc_clear_all_cache' );
		// SG Optimizer (SiteGround).
		do_action( 'sg_cachepress_purge_cache' );
		// Anything else can hook this to purge a host-specific layer.
		do_action( 'shuffles_ssj_purge_cache' );
	}
}
