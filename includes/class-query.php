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
}
