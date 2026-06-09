<?php
/**
 * Job permalink structure (admin-selectable), with 301 redirects from the previous URLs.
 *
 * Custom post type URLs are defined in code, NOT in WP's Settings -> Permalinks (which only governs
 * standard posts/pages). This class lets an admin choose the job URL format in the plugin's own
 * settings (Settings -> SEO -> "Job URL format"), registers the matching rewrite rule, rewrites
 * get_permalink() to match, 301-redirects the old URLs to the new ones, and flushes the rewrite rules
 * once whenever the format or plugin version changes. Only the sssj_job post type is affected.
 *
 * Default: id_title -> /jobs/{id}/{slug}/ (collision-proof, never produces -2/-3 duplicate slugs).
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Permalinks {

	const FLUSH_OPT = 'sssj_perma_signature';

	/** Allowed formats. */
	public static function formats() {
		return array( 'title', 'id_title', 'date_title', 'ym_title', 'id_date_title' );
	}

	/** Human labels for the settings select. */
	public static function format_labels() {
		return array(
			'title'         => __( 'Title only  (/jobs/title/)', 'shuffles-social-services-jobs' ),
			'id_title'      => __( 'ID + title  (/jobs/1378/title/), recommended, never collides', 'shuffles-social-services-jobs' ),
			'date_title'    => __( 'Date + title  (/jobs/2026/06/09/title/)', 'shuffles-social-services-jobs' ),
			'ym_title'      => __( 'Year + month + title  (/jobs/2026/06/title/)', 'shuffles-social-services-jobs' ),
			'id_date_title' => __( 'ID + year + month + title  (/jobs/1378/2026/06/title/)', 'shuffles-social-services-jobs' ),
		);
	}

	/** Current job URL format (defaults to id_title). */
	public static function format() {
		$o = get_option( 'shuffles_ssj_settings', array() );
		$f = ( is_array( $o ) && ! empty( $o['job_url_format'] ) ) ? (string) $o['job_url_format'] : 'id_title';
		return in_array( $f, self::formats(), true ) ? $f : 'id_title';
	}

	public static function register() {
		add_action( 'init', array( __CLASS__, 'add_rules' ), 11 );
		add_filter( 'post_type_link', array( __CLASS__, 'job_link' ), 10, 2 );
		add_action( 'template_redirect', array( __CLASS__, 'canonical_redirect' ) );
		add_filter( 'redirect_canonical', array( __CLASS__, 'no_core_canonical' ), 10, 2 );
		// One-time flush on the first request after the format/version changes (then it self-disables).
		add_action( 'init', array( __CLASS__, 'maybe_flush' ), 99 );
	}

	/** The path segment(s) inserted between "jobs/" and the slug, per format. '' = none. */
	private static function prefix( $post ) {
		$id = (int) $post->ID;
		$ts = ( ! empty( $post->post_date_gmt ) && '0000-00-00 00:00:00' !== $post->post_date_gmt )
			? strtotime( $post->post_date_gmt . ' UTC' )
			: strtotime( (string) $post->post_date );
		$y  = $ts ? gmdate( 'Y', $ts ) : '';
		$m  = $ts ? gmdate( 'm', $ts ) : '';
		$d  = $ts ? gmdate( 'd', $ts ) : '';
		switch ( self::format() ) {
			case 'id_title':
				return (string) $id;
			case 'date_title':
				return ( $y && $m && $d ) ? "$y/$m/$d" : '';
			case 'ym_title':
				return ( $y && $m ) ? "$y/$m" : '';
			case 'id_date_title':
				return ( $y && $m ) ? "$id/$y/$m" : (string) $id;
		}
		return '';
	}

	/** Rewrite get_permalink() for jobs to the chosen structure. */
	public static function job_link( $link, $post ) {
		if ( empty( $post ) || ! is_object( $post ) || 'sssj_job' !== $post->post_type ) {
			return $link;
		}
		if ( 'title' === self::format() ) {
			return $link;
		}
		if ( 'publish' !== $post->post_status || '' === (string) $post->post_name ) {
			return $link; // drafts / previews keep the default
		}
		$prefix = self::prefix( $post );
		if ( '' === $prefix ) {
			return $link;
		}
		return home_url( user_trailingslashit( 'jobs/' . $prefix . '/' . $post->post_name ) );
	}

	/** Register the rewrite rule that routes the chosen structure back to the job. */
	public static function add_rules() {
		switch ( self::format() ) {
			case 'id_title':
				add_rewrite_rule( '^jobs/([0-9]+)/[^/]+/?$', 'index.php?post_type=sssj_job&p=$matches[1]', 'top' );
				break;
			case 'date_title':
				add_rewrite_rule( '^jobs/[0-9]{4}/[0-9]{2}/[0-9]{2}/([^/]+)/?$', 'index.php?sssj_job=$matches[1]', 'top' );
				break;
			case 'ym_title':
				add_rewrite_rule( '^jobs/[0-9]{4}/[0-9]{2}/([^/]+)/?$', 'index.php?sssj_job=$matches[1]', 'top' );
				break;
			case 'id_date_title':
				add_rewrite_rule( '^jobs/([0-9]+)/[0-9]{4}/[0-9]{2}/[^/]+/?$', 'index.php?post_type=sssj_job&p=$matches[1]', 'top' );
				break;
		}
	}

	/** 301 any job URL that is not the canonical one (e.g. an old /jobs/slug/ link) to the new format. */
	public static function canonical_redirect() {
		if ( is_admin() || 'title' === self::format() || ! is_singular( 'sssj_job' ) ) {
			return;
		}
		$id = get_queried_object_id();
		if ( ! $id ) {
			return;
		}
		$canonical = get_permalink( $id );
		if ( ! $canonical ) {
			return;
		}
		$cur = wp_parse_url( home_url( add_query_arg( array() ) ), PHP_URL_PATH );
		$can = wp_parse_url( $canonical, PHP_URL_PATH );
		if ( $cur && $can && untrailingslashit( $cur ) !== untrailingslashit( $can ) ) {
			wp_safe_redirect( $canonical, 301 );
			exit;
		}
	}

	/** Stop core's redirect_canonical from fighting our custom job structure. */
	public static function no_core_canonical( $redirect_url, $requested_url ) {
		if ( 'title' !== self::format() && is_singular( 'sssj_job' ) ) {
			return false;
		}
		return $redirect_url;
	}

	/** Flush rewrite rules once per format/version change (soft flush; nginx/Cloudways friendly). */
	public static function maybe_flush() {
		$sig = self::format() . '|' . SHUFFLES_SSJ_VERSION;
		if ( get_option( self::FLUSH_OPT ) === $sig ) {
			return;
		}
		self::add_rules();
		flush_rewrite_rules( false );
		update_option( self::FLUSH_OPT, $sig, false );
	}
}
