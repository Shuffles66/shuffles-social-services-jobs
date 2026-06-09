<?php
/**
 * Organisation branding helpers, social links (single source of truth) + logo.
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
	 * "Inherit from organisation by default", so a job without a logo shows its company's brand.
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
	 * All geocoded points for an org, its primary location plus any additional locations that
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

	/** Organisation categories (what kind of organisation) → label. */
	public static function categories() {
		return array(
			'support'      => __( 'Support provider', 'shuffles-social-services-jobs' ),
			'supplier'     => __( 'Supplier / services to the sector', 'shuffles-social-services-jobs' ),
			'sda_housing'  => __( 'SDA / housing', 'shuffles-social-services-jobs' ),
			'real_estate'  => __( 'Real estate', 'shuffles-social-services-jobs' ),
			'professional' => __( 'Professional services', 'shuffles-social-services-jobs' ),
			'other'        => __( 'Other', 'shuffles-social-services-jobs' ),
		);
	}

	public static function category_label( $key ) {
		$c = self::categories();
		return isset( $c[ (string) $key ] ) ? $c[ (string) $key ] : '';
	}

	/** Organisation size bands → label (for filtering: sole traders vs larger providers). */
	public static function sizes() {
		return array(
			'sole_trader' => __( 'Sole trader', 'shuffles-social-services-jobs' ),
			'small'       => __( 'Small (2–10)', 'shuffles-social-services-jobs' ),
			'medium'      => __( 'Medium (11–50)', 'shuffles-social-services-jobs' ),
			'large'       => __( 'Large (50+)', 'shuffles-social-services-jobs' ),
		);
	}

	public static function size_label( $key ) {
		$s = self::sizes();
		return isset( $s[ (string) $key ] ) ? $s[ (string) $key ] : '';
	}

	/** Organisation legal structure → label. */
	public static function structures() {
		return array(
			'sole_trader' => __( 'Sole trader', 'shuffles-social-services-jobs' ),
			'partnership' => __( 'Partnership', 'shuffles-social-services-jobs' ),
			'company'     => __( 'Company', 'shuffles-social-services-jobs' ),
			'nfp'         => __( 'Not-for-profit', 'shuffles-social-services-jobs' ),
			'government'  => __( 'Government', 'shuffles-social-services-jobs' ),
		);
	}

	public static function structure_label( $key ) {
		$s = self::structures();
		return isset( $s[ (string) $key ] ) ? $s[ (string) $key ] : '';
	}

	/** Is this org flagged as a sponsor (admin-granted placement)? */
	public static function is_sponsored( $org_id ) {
		return '1' === (string) get_post_meta( (int) $org_id, 'org_sponsored', true );
	}

	/** Public NDIS Commission "find a registered provider" search URL (by number if given). */
	public static function ndis_register_url( $number = '' ) {
		$base = 'https://www.ndiscommission.gov.au/about/ndis-provider-register';
		$number = preg_replace( '/[^0-9A-Za-z]/', '', (string) $number );
		return $number ? add_query_arg( 'q', $number, $base ) : $base;
	}

	/** Badge + Commission link for an org's NDIS registration ('' if not flagged registered). */
	public static function ndis_badge_html( $org_id ) {
		$org_id = (int) $org_id;
		if ( '1' !== (string) get_post_meta( $org_id, 'ndis_registered', true ) ) {
			return '';
		}
		$num    = class_exists( 'Shuffles_SSJ_NDIS_Register' )
			? (string) Shuffles_SSJ_NDIS_Register::register_id_for( $org_id )
			: (string) get_post_meta( $org_id, 'ndis_provider_number', true );
		$status = (string) get_post_meta( $org_id, 'ndis_status', true );
		$active = ( '' === $status ) || ( false !== stripos( $status, 'register' ) || false !== stripos( $status, 'active' ) );
		$label  = $num ? sprintf( __( 'NDIS Registered · #%s', 'shuffles-social-services-jobs' ), $num ) : __( 'NDIS Registered', 'shuffles-social-services-jobs' );
		$out    = '<a class="sssj-badge ' . ( $active ? 'sssj-badge--verified' : '' ) . '" href="' . esc_url( self::ndis_register_url( $num ) ) . '" target="_blank" rel="noopener" title="' . esc_attr__( 'Check on the NDIS Commission register', 'shuffles-social-services-jobs' ) . '">🛡️ ' . esc_html( $label ) . '</a>';
		$groups = (string) get_post_meta( $org_id, 'ndis_groups', true );
		if ( '' !== $groups ) {
			$out .= ' <span class="sssj-badge" title="' . esc_attr__( 'Registration groups', 'shuffles-social-services-jobs' ) . '">' . esc_html( wp_trim_words( $groups, 8 ) ) . '</span>';
		}
		return $out;
	}

	/**
	 * Fired on shuffles_ssj_ndis_recorded, give an integration the chance to auto-scan the
	 * NDIS Commission register (there is no official public API, so this is hook-driven /
	 * best-effort). A filter returning [ 'status' => …, 'groups' => … ] is stored; else manual.
	 */
	public static function on_ndis_recorded( $number, $org_id ) {
		$org_id = (int) $org_id;
		$scan   = apply_filters( 'shuffles_ssj_ndis_scan', null, $number, $org_id );
		if ( is_array( $scan ) ) {
			if ( isset( $scan['status'] ) ) {
				update_post_meta( $org_id, 'ndis_status', sanitize_text_field( (string) $scan['status'] ) );
			}
			if ( isset( $scan['groups'] ) ) {
				update_post_meta( $org_id, 'ndis_groups', sanitize_text_field( (string) $scan['groups'] ) );
			}
		}
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
