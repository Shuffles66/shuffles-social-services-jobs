<?php
/**
 * Organisation branding helpers — social links (single source of truth) + logo.
 * The logo is the org post's featured image (so it also feeds Organization JSON-LD).
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Org {

	/**
	 * Social networks: meta_key => [ label, slug, glyph, brand colour ]. Filterable.
	 *
	 * @return array
	 */
	public static function networks() {
		return apply_filters(
			'shuffles_ssj_org_networks',
			array(
				'org_facebook'  => array( 'label' => __( 'Facebook', 'shuffles-social-services-jobs' ), 'slug' => 'facebook', 'glyph' => 'f', 'color' => '#1877f2' ),
				'org_linkedin'  => array( 'label' => __( 'LinkedIn', 'shuffles-social-services-jobs' ), 'slug' => 'linkedin', 'glyph' => 'in', 'color' => '#0a66c2' ),
				'org_instagram' => array( 'label' => __( 'Instagram', 'shuffles-social-services-jobs' ), 'slug' => 'instagram', 'glyph' => '◎', 'color' => '#e4405f' ),
				'org_twitter'   => array( 'label' => __( 'X', 'shuffles-social-services-jobs' ), 'slug' => 'x', 'glyph' => '𝕏', 'color' => '#0f172a' ),
				'org_youtube'   => array( 'label' => __( 'YouTube', 'shuffles-social-services-jobs' ), 'slug' => 'youtube', 'glyph' => '▶', 'color' => '#ff0000' ),
				'org_shuffles'  => array( 'label' => __( 'Shuffles profile', 'shuffles-social-services-jobs' ), 'slug' => 'shuffles', 'glyph' => 'S', 'color' => '#3897e0' ),
			)
		);
	}

	/** The org's logo URL (its featured image), or '' if none. */
	public static function logo_url( $org_id, $size = 'medium' ) {
		$url = get_the_post_thumbnail_url( (int) $org_id, $size );
		return $url ? (string) $url : '';
	}

	/**
	 * A job's logo: its own featured image if set, otherwise the linked organisation's logo.
	 * "Inherit from organisation by default" — so a job without a logo shows its company's brand.
	 *
	 * @param int    $job_id Job post ID.
	 * @param string $size   Image size.
	 * @return string URL or ''.
	 */
	public static function job_logo_url( $job_id, $size = 'thumbnail' ) {
		$own = get_the_post_thumbnail_url( (int) $job_id, $size );
		if ( $own ) {
			return (string) $own;
		}
		$org = (int) get_post_meta( (int) $job_id, 'organisation_id', true );
		return $org ? self::logo_url( $org, $size ) : '';
	}

	/** Rendered social-icon row for an org (empty string if none set). */
	public static function social_html( $org_id ) {
		$out = '';
		foreach ( self::networks() as $key => $net ) {
			$url = (string) get_post_meta( (int) $org_id, $key, true );
			if ( '' === $url ) {
				continue;
			}
			$out .= '<a class="sssj-social sssj-social--' . esc_attr( $net['slug'] ) . '" style="background:' . esc_attr( $net['color'] ) . '"'
				. ' href="' . esc_url( $url ) . '" target="_blank" rel="noopener nofollow"'
				. ' aria-label="' . esc_attr( $net['label'] ) . '" title="' . esc_attr( $net['label'] ) . '"><span>' . esc_html( $net['glyph'] ) . '</span></a>';
		}
		return '' !== $out ? '<div class="sssj-socials">' . $out . '</div>' : '';
	}

	/**
	 * All geocoded points for an org — its primary location plus any additional locations that
	 * carry coordinates. Each: [ lat, lng, label ].
	 *
	 * @return array
	 */
	public static function location_points( $org_id ) {
		$org_id = (int) $org_id;
		$pts    = array();
		$plat   = (float) get_post_meta( $org_id, 'location_lat', true );
		$plng   = (float) get_post_meta( $org_id, 'location_lng', true );
		if ( $plat && $plng ) {
			$pts[] = array( 'lat' => $plat, 'lng' => $plng, 'label' => trim( (string) get_post_meta( $org_id, 'location_suburb', true ) . ' ' . (string) get_post_meta( $org_id, 'location_state', true ) ) );
		}
		$extra = json_decode( (string) get_post_meta( $org_id, 'locations', true ), true );
		if ( is_array( $extra ) ) {
			foreach ( $extra as $l ) {
				$la = isset( $l['lat'] ) ? (float) $l['lat'] : 0;
				$lo = isset( $l['lng'] ) ? (float) $l['lng'] : 0;
				if ( $la && $lo ) {
					$lbl = trim( ( isset( $l['label'] ) ? $l['label'] . ' ' : '' ) . ( isset( $l['suburb'] ) ? $l['suburb'] : '' ) . ' ' . ( isset( $l['state'] ) ? $l['state'] : '' ) );
					$pts[] = array( 'lat' => $la, 'lng' => $lo, 'label' => $lbl );
				}
			}
		}
		return $pts;
	}

	/** Nearest of an org's locations to a centre, in km (null if the org has no coordinates). */
	public static function nearest_km( $org_id, $lat, $lng ) {
		if ( ! class_exists( 'Shuffles_SSJ_Geo' ) ) {
			return null;
		}
		$min = null;
		foreach ( self::location_points( $org_id ) as $p ) {
			$d = Shuffles_SSJ_Geo::distance_km( $lat, $lng, $p['lat'], $p['lng'] );
			if ( null === $min || $d < $min ) {
				$min = $d;
			}
		}
		return $min;
	}

	/**
	 * Placement stats for an org card: open = currently-published jobs; placed = all-time applications
	 * to ANY of the org's jobs (incl. closed) that reached 'offer' status (people the org placed).
	 *
	 * @return array { open, placed }
	 */
	public static function stats( $org_id ) {
		global $wpdb;
		$org_id   = (int) $org_id;
		$open_ids = get_posts( array( 'post_type' => 'sssj_job', 'post_status' => 'publish', 'posts_per_page' => 500, 'fields' => 'ids', 'no_found_rows' => true, 'meta_query' => array( array( 'key' => 'organisation_id', 'value' => $org_id ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		$all_ids  = get_posts( array( 'post_type' => 'sssj_job', 'post_status' => 'any', 'posts_per_page' => 1000, 'fields' => 'ids', 'no_found_rows' => true, 'meta_query' => array( array( 'key' => 'organisation_id', 'value' => $org_id ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		$placed   = 0;
		if ( $all_ids ) {
			$in     = implode( ',', array_map( 'intval', $all_ids ) );
			$placed = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sssj_application WHERE status = 'offer' AND job_id IN ($in)" ); // phpcs:ignore WordPress.DB
		}
		return array( 'open' => count( $open_ids ), 'placed' => $placed );
	}

	/** Plain list of an org's social URLs (for JSON-LD sameAs). */
	public static function social_urls( $org_id ) {
		$urls = array();
		foreach ( array_keys( self::networks() ) as $key ) {
			$u = (string) get_post_meta( (int) $org_id, $key, true );
			if ( '' !== $u ) {
				$urls[] = $u;
			}
		}
		return $urls;
	}
}
