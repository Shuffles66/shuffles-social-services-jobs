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
