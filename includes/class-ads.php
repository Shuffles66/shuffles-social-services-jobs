<?php
/**
 * Advanced Ads integration (standalone-first, runtime-detected).
 *
 * We do NOT bundle or depend on Advanced Ads. If the Advanced Ads plugin is active, this class
 * lets its banners appear inside the marketplace:
 *   - [sssj_ad placement="slug"] / [sssj_ad id="123"] — drop an ad anywhere.
 *   - Named slots (board top/bottom, single listing) mapped to an Advanced Ads placement in
 *     Settings → Ads, rendered automatically by the boards and single listing pages.
 * If Advanced Ads is not active (or a slot is unmapped), everything renders as empty — nothing breaks.
 *
 * Advanced Ads template tags used (all guarded by function_exists):
 *   get_the_ad( $id ) · get_the_ad_placement( $id_or_slug ) · get_the_ad_group( $id )
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Ads {

	/** Named slots we expose (slot key without the ad_slot_ prefix). */
	public static function slots() {
		return array(
			'board_top'    => __( 'Board — top (above results)', 'shuffles-social-services-jobs' ),
			'board_bottom' => __( 'Board — bottom (below results)', 'shuffles-social-services-jobs' ),
			'single'       => __( 'Single listing — below content', 'shuffles-social-services-jobs' ),
		);
	}

	/** Is the Advanced Ads plugin active? */
	public static function is_active() {
		return function_exists( 'get_the_ad' ) || function_exists( 'the_ad' ) || class_exists( 'Advanced_Ads' );
	}

	/** Are our ad placements switched on? (Default ON when Advanced Ads is active.) */
	public static function enabled() {
		if ( ! self::is_active() ) {
			return false;
		}
		$o = get_option( 'shuffles_ssj_settings', array() );
		return ! ( is_array( $o ) && isset( $o['ads_enabled'] ) && ! $o['ads_enabled'] );
	}

	/**
	 * Render an Advanced Ads ad (by id), placement or group (by id/slug). Returns '' if Advanced
	 * Ads is inactive or the result is empty.
	 *
	 * @param array $args id, placement, group.
	 * @return string
	 */
	public static function render( $args ) {
		if ( ! self::is_active() ) {
			return '';
		}
		$id        = isset( $args['id'] ) ? (int) $args['id'] : 0;
		$placement = isset( $args['placement'] ) ? trim( (string) $args['placement'] ) : '';
		$group     = isset( $args['group'] ) ? (int) $args['group'] : 0;

		$html = '';
		if ( $id && function_exists( 'get_the_ad' ) ) {
			$html = get_the_ad( $id );
		} elseif ( '' !== $placement && function_exists( 'get_the_ad_placement' ) ) {
			$html = get_the_ad_placement( $placement );
		} elseif ( $group && function_exists( 'get_the_ad_group' ) ) {
			$html = get_the_ad_group( $group );
		}
		$html = trim( (string) $html );
		if ( '' === $html ) {
			return '';
		}
		return '<div class="sssj-ad" aria-label="' . esc_attr__( 'Advertisement', 'shuffles-social-services-jobs' ) . '">'
			. '<span class="sssj-ad__label">' . esc_html__( 'Advertisement', 'shuffles-social-services-jobs' ) . '</span>'
			. $html . '</div>';
	}

	/**
	 * Render a named slot's configured Advanced Ads placement. '' if disabled or unmapped.
	 *
	 * @param string $slot board_top | board_bottom | single.
	 * @return string
	 */
	public static function slot( $slot ) {
		if ( ! self::enabled() ) {
			return '';
		}
		$slot = sanitize_key( (string) $slot );
		if ( ! array_key_exists( $slot, self::slots() ) ) {
			return '';
		}
		$o         = get_option( 'shuffles_ssj_settings', array() );
		$placement = ( is_array( $o ) && ! empty( $o[ 'ad_slot_' . $slot ] ) ) ? trim( (string) $o[ 'ad_slot_' . $slot ] ) : '';
		if ( '' === $placement ) {
			return '';
		}
		// A slot value may be a placement slug, or "id:123" to target a specific ad.
		if ( preg_match( '/^id:(\d+)$/', $placement, $m ) ) {
			return self::render( array( 'id' => (int) $m[1] ) );
		}
		return self::render( array( 'placement' => $placement ) );
	}

	/** [sssj_ad placement="slug" id="123" group="4"] — place an Advanced Ads unit anywhere. */
	public static function shortcode( $atts ) {
		$a = shortcode_atts(
			array( 'placement' => '', 'id' => 0, 'group' => 0 ),
			is_array( $atts ) ? $atts : array(),
			'sssj_ad'
		);
		// The shortcode honours the master switch too (so you can turn all marketplace ads off).
		if ( ! self::enabled() ) {
			return '';
		}
		return self::render( array( 'placement' => (string) $a['placement'], 'id' => (int) $a['id'], 'group' => (int) $a['group'] ) );
	}
}
