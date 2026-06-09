<?php
/**
 * Job syndication (#7), Phase A: a standard job XML feed for aggregators (Jora, Adzuna, …).
 *
 * Push-out direction. Outputs eligible published, non-expired jobs in the widely-accepted
 * "<source><job>…</job></source>" XML format that Jora and similar aggregators ingest. Google for
 * Jobs is covered separately by the on-page JobPosting JSON-LD (Shuffles_SSJ_SEO), no feed needed.
 *
 * PRIVACY: participant needs are a different CPT (`sssj_need`) and never appear here. Anonymous jobs
 * syndicate with company = "Private advertiser" and no org name (same rule as on-site). A filter
 * (shuffles_ssj_syndication_args) lets a later phase gate the feed to purchased ("Boost") jobs only.
 *
 * ENDPOINT: home_url('/?sssj_feed=jobs') (always works) and a pretty /sssj-jobs-feed.xml (flushed once).
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Syndication {

	const QV = 'sssj_feed';

	/** Is the public job feed switched on? (Settings → SEO.) Default ON. */
	public static function enabled() {
		$o = get_option( 'shuffles_ssj_settings', array() );
		return ! ( is_array( $o ) && isset( $o['syndication_feed_enabled'] ) && ! $o['syndication_feed_enabled'] );
	}

	public static function register() {
		add_action( 'init', array( __CLASS__, 'add_rewrite' ) );
		add_filter( 'query_vars', array( __CLASS__, 'add_query_var' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_output' ) );
	}

	public static function add_query_var( $vars ) {
		$vars[] = self::QV;
		return $vars;
	}

	public static function add_rewrite() {
		add_rewrite_rule( '^sssj-jobs-feed\.xml$', 'index.php?' . self::QV . '=jobs', 'top' );
		// Flush exactly once so the pretty URL resolves (the query-var URL works regardless).
		if ( 'v1' !== get_option( 'sssj_feed_rewrite' ) ) {
			flush_rewrite_rules( false );
			update_option( 'sssj_feed_rewrite', 'v1' );
		}
	}

	/** Guaranteed-working feed URL (query var). */
	public static function feed_url() {
		return home_url( '/?' . self::QV . '=jobs' );
	}

	/** Pretty feed URL (works after the one-time rewrite flush). */
	public static function pretty_feed_url() {
		return home_url( '/sssj-jobs-feed.xml' );
	}

	public static function maybe_output() {
		$is_feed = ( 'jobs' === get_query_var( self::QV ) )
			|| ( isset( $_GET[ self::QV ] ) && 'jobs' === sanitize_key( wp_unslash( $_GET[ self::QV ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $is_feed ) {
			return;
		}
		if ( ! self::enabled() ) {
			status_header( 404 );
			nocache_headers();
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo 'Job feed is disabled.';
			exit;
		}
		self::output_feed();
	}

	private static function eligible_jobs() {
		$args = array(
			'post_type'      => 'sssj_job',
			'post_status'    => 'publish',
			'posts_per_page' => (int) apply_filters( 'shuffles_ssj_syndication_limit', 500 ),
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
			'meta_query'     => array(
				'relation' => 'OR',
				array( 'key' => 'expires_at', 'compare' => 'NOT EXISTS' ),
				array( 'key' => 'expires_at', 'value' => '', 'compare' => '=' ),
				array( 'key' => 'expires_at', 'value' => current_time( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ),
			),
		);
		// Phase B can add a `_sssj_syndicate = 1` clause here to gate the feed to purchased jobs.
		$args = apply_filters( 'shuffles_ssj_syndication_args', $args );
		return get_posts( $args );
	}

	/** CDATA-wrap a value safely (handles a literal ]]> in the content). */
	private static function cd( $s ) {
		return '<![CDATA[' . str_replace( ']]>', ']]]]><![CDATA[>', (string) $s ) . ']]>';
	}

	public static function output_feed() {
		nocache_headers();
		header( 'Content-Type: application/xml; charset=utf-8' );
		$jobs = self::eligible_jobs();
		echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
		echo "<source>\n";
		echo '  <publisher>' . self::cd( get_bloginfo( 'name' ) ) . "</publisher>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '  <publisherurl>' . self::cd( home_url( '/' ) ) . "</publisherurl>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '  <lastBuildDate>' . self::cd( gmdate( 'D, d M Y H:i:s' ) . ' GMT' ) . "</lastBuildDate>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
		foreach ( $jobs as $p ) {
			echo self::job_xml( $p ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo "</source>\n";
		exit;
	}

	private static function job_xml( $post ) {
		$id     = (int) $post->ID;
		$anon   = '1' === (string) get_post_meta( $id, 'is_anonymous', true );
		$org_id = (int) get_post_meta( $id, 'organisation_id', true );
		if ( $anon ) {
			$company = __( 'Private advertiser', 'shuffles-social-services-jobs' );
		} else {
			$company = $org_id ? get_the_title( $org_id ) : ( get_the_author_meta( 'display_name', (int) $post->post_author ) ?: get_bloginfo( 'name' ) );
		}

		$suburb = (string) get_post_meta( $id, 'location_suburb', true );
		$state  = (string) get_post_meta( $id, 'location_state', true );
		$pc     = (string) get_post_meta( $id, 'location_postcode', true );

		$emp     = wp_get_post_terms( $id, 'sssjt_employment_type', array( 'fields' => 'names' ) );
		$jobtype = ( ! is_wp_error( $emp ) && $emp ) ? $emp[0] : '';
		$cats    = wp_get_post_terms( $id, 'sssjt_category', array( 'fields' => 'names' ) );
		$category = ( ! is_wp_error( $cats ) && $cats ) ? implode( ', ', $cats ) : '';

		$rmin  = (float) get_post_meta( $id, 'rate_min', true );
		$rmax  = (float) get_post_meta( $id, 'rate_max', true );
		$runit = (string) get_post_meta( $id, 'rate_unit', true );
		$salary = '';
		if ( $rmin > 0 || $rmax > 0 ) {
			$amt = ( $rmin > 0 && $rmax > 0 && $rmax != $rmin )
				? '$' . rtrim( rtrim( number_format( $rmin, 2 ), '0' ), '.' ) . ' - $' . rtrim( rtrim( number_format( $rmax, 2 ), '0' ), '.' )
				: '$' . rtrim( rtrim( number_format( $rmax > 0 ? $rmax : $rmin, 2 ), '0' ), '.' );
			$salary = $amt . ( $runit ? ' / ' . $runit : '' );
		}

		$desc = trim( (string) $post->post_content );
		if ( '' === $desc ) {
			$desc = get_the_title( $id );
		}

		$out  = "  <job>\n";
		$out .= '    <title>' . self::cd( get_the_title( $id ) ) . "</title>\n";
		$out .= '    <date>' . self::cd( get_post_time( 'D, d M Y H:i:s', true, $id ) . ' GMT' ) . "</date>\n";
		$out .= '    <referencenumber>' . self::cd( $id ) . "</referencenumber>\n";
		$out .= '    <url>' . self::cd( get_permalink( $id ) ) . "</url>\n";
		$out .= '    <company>' . self::cd( $company ) . "</company>\n";
		$out .= '    <city>' . self::cd( $suburb ) . "</city>\n";
		$out .= '    <state>' . self::cd( $state ) . "</state>\n";
		$out .= '    <country>' . self::cd( 'Australia' ) . "</country>\n";
		if ( '' !== $pc ) {
			$out .= '    <postalcode>' . self::cd( $pc ) . "</postalcode>\n";
		}
		if ( '' !== $jobtype ) {
			$out .= '    <jobtype>' . self::cd( $jobtype ) . "</jobtype>\n";
		}
		if ( '' !== $category ) {
			$out .= '    <category>' . self::cd( $category ) . "</category>\n";
		}
		if ( '' !== $salary ) {
			$out .= '    <salary>' . self::cd( $salary ) . "</salary>\n";
		}
		$out .= '    <description>' . self::cd( wpautop( $desc ) ) . "</description>\n";
		$out .= "  </job>\n";
		return $out;
	}
}
