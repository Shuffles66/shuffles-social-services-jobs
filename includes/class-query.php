<?php
/**
 * Query helper — the ABN/TFN segregation gate.
 *
 * CRITICAL: every board / list path routes through base_args(). The engagement_basis
 * meta clause is mandatory, enforced here in the query layer, never in templates.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Query {

	/**
	 * Build WP_Query args for the job boards.
	 *
	 * @param string $basis '' | 'abn' | 'tfn'  (when set, hard-filters to that basis).
	 * @param array  $extra paged, posts_per_page, category (slug), s (search).
	 * @return array
	 */
	public static function base_args( $basis = '', $extra = array() ) {
		$args = array(
			'post_type'      => 'sssj_job',
			'post_status'    => 'publish',
			'posts_per_page' => isset( $extra['posts_per_page'] ) ? max( 1, (int) $extra['posts_per_page'] ) : 12,
			'paged'          => isset( $extra['paged'] ) ? max( 1, (int) $extra['paged'] ) : 1,
			'orderby'        => array( 'menu_order' => 'DESC', 'date' => 'DESC' ),
			'meta_query'     => array(),
			'tax_query'      => array(),
		);

		$basis = sanitize_key( (string) $basis );
		if ( in_array( $basis, array( 'abn', 'tfn' ), true ) ) {
			$args['meta_query'][] = array(
				'key'   => 'engagement_basis',
				'value' => $basis,
			);
		}

		// Exclude expired ads (no expiry, blank, or >= today).
		$args['meta_query'][] = array(
			'relation' => 'OR',
			array( 'key' => 'expires_at', 'compare' => 'NOT EXISTS' ),
			array( 'key' => 'expires_at', 'value' => '', 'compare' => '=' ),
			array( 'key' => 'expires_at', 'value' => current_time( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ),
		);

		self::add_radius_clauses( $args, $extra );

		if ( ! empty( $extra['org'] ) ) {
			$args['meta_query'][] = array( 'key' => 'organisation_id', 'value' => (int) $extra['org'] );
		}

		if ( count( $args['meta_query'] ) > 1 ) {
			$args['meta_query']['relation'] = 'AND';
		}

		if ( ! empty( $extra['category'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'sssjt_category',
				'field'    => 'slug',
				'terms'    => sanitize_title( $extra['category'] ),
			);
		}

		if ( ! empty( $extra['s'] ) ) {
			$args['s'] = sanitize_text_field( $extra['s'] );
		}

		/**
		 * Filter the board query args (e.g. to add radius/location later).
		 *
		 * @param array  $args  WP_Query args.
		 * @param string $basis Engagement basis.
		 * @param array  $extra Raw extra inputs.
		 */
		return apply_filters( 'shuffles_ssj_board_query_args', $args, $basis, $extra );
	}

	/**
	 * WP_Query args for the worker directory — visibility enforced in the query layer.
	 *
	 * Guests see only 'public'; logged-in users also see 'logged_in'. 'verified_only' is
	 * deliberately excluded until verified-worker detection ships, so a profile is never
	 * shown more widely than its owner intended.
	 *
	 * @param array $extra paged, posts_per_page, category (slug), s, available.
	 * @return array
	 */
	public static function worker_args( $extra = array() ) {
		$visible = array( 'public' );
		if ( is_user_logged_in() ) {
			$visible[] = 'logged_in';
		}

		$args = array(
			'post_type'      => 'sssj_worker',
			'post_status'    => 'publish',
			'posts_per_page' => isset( $extra['posts_per_page'] ) ? max( 1, (int) $extra['posts_per_page'] ) : 12,
			'paged'          => isset( $extra['paged'] ) ? max( 1, (int) $extra['paged'] ) : 1,
			'orderby'        => array( 'date' => 'DESC' ),
			'meta_query'     => array(
				array(
					'key'     => 'visibility',
					'value'   => $visible,
					'compare' => 'IN',
				),
			),
			'tax_query'      => array(),
		);

		if ( ! empty( $extra['available'] ) ) {
			$args['meta_query'][] = array(
				'key'   => 'is_available',
				'value' => 1,
			);
		}
		if ( count( $args['meta_query'] ) > 1 ) {
			$args['meta_query']['relation'] = 'AND';
		}
		if ( ! empty( $extra['category'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'sssjt_category',
				'field'    => 'slug',
				'terms'    => sanitize_title( $extra['category'] ),
			);
		}
		if ( ! empty( $extra['s'] ) ) {
			$args['s'] = sanitize_text_field( $extra['s'] );
		}

		return apply_filters( 'shuffles_ssj_worker_query_args', $args, $extra );
	}

	/**
	 * WP_Query args for the participant-need board.
	 *
	 * Privacy: only PUBLISHED (admin-moderated) needs; never public — callers MUST gate on
	 * is_user_logged_in() before querying. 'verified_workers_only' is excluded until
	 * verified-worker detection ships, so a need is never shown more widely than intended.
	 *
	 * @param array $extra paged, posts_per_page, support (slug), funding (slug).
	 * @return array
	 */
	public static function need_args( $extra = array() ) {
		$args = array(
			'post_type'      => 'sssj_need',
			'post_status'    => 'publish',
			'posts_per_page' => isset( $extra['posts_per_page'] ) ? max( 1, (int) $extra['posts_per_page'] ) : 12,
			'paged'          => isset( $extra['paged'] ) ? max( 1, (int) $extra['paged'] ) : 1,
			'orderby'        => array( 'date' => 'DESC' ),
			'meta_query'     => array(
				array(
					'key'     => 'visibility',
					'value'   => array( 'logged_in' ),
					'compare' => 'IN',
				),
			),
			'tax_query'      => array(),
		);

		self::add_radius_clauses( $args, $extra );
		if ( count( $args['meta_query'] ) > 1 ) {
			$args['meta_query']['relation'] = 'AND';
		}

		if ( ! empty( $extra['support'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'sssjt_support_category',
				'field'    => 'slug',
				'terms'    => sanitize_title( $extra['support'] ),
			);
		}
		if ( ! empty( $extra['funding'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'sssjt_funding_source',
				'field'    => 'slug',
				'terms'    => sanitize_title( $extra['funding'] ),
			);
		}
		if ( count( $args['tax_query'] ) > 1 ) {
			$args['tax_query']['relation'] = 'AND';
		}

		return apply_filters( 'shuffles_ssj_need_query_args', $args, $extra );
	}

	/**
	 * Append bounding-box lat/lng clauses when a geocoded centre + radius are supplied.
	 * Approximate (square, not circle) — cheap, index-friendly, good enough for board filtering.
	 *
	 * @param array $args  WP_Query args (by reference).
	 * @param array $extra lat, lng, radius (km).
	 */
	private static function add_radius_clauses( &$args, $extra ) {
		if ( empty( $extra['lat'] ) || empty( $extra['lng'] ) || empty( $extra['radius'] ) ) {
			return;
		}
		$lat   = (float) $extra['lat'];
		$lng   = (float) $extra['lng'];
		$r     = (float) $extra['radius'];
		$d_lat = $r / 111.045;
		$d_lng = $r / ( 111.045 * max( 0.05, cos( deg2rad( $lat ) ) ) );
		$args['meta_query'][] = array( 'key' => 'location_lat', 'value' => array( $lat - $d_lat, $lat + $d_lat ), 'type' => 'DECIMAL(10,6)', 'compare' => 'BETWEEN' );
		$args['meta_query'][] = array( 'key' => 'location_lng', 'value' => array( $lng - $d_lng, $lng + $d_lng ), 'type' => 'DECIMAL(10,6)', 'compare' => 'BETWEEN' );
	}
}
