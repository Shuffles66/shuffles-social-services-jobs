<?php
/**
 * Media helpers: consistent image guidance (preferred dimensions + what makes good, valuable
 * content) shown under every photo upload, and a promotional video field plus a safe, responsive
 * YouTube / Vimeo embed used to help members sell their brand or service.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Media {

	/**
	 * Helper text for an image upload: preferred dimensions and a short guide to valuable content.
	 *
	 * @param string $context photo | gallery | logo | job_logo.
	 * @return string description HTML (already escaped).
	 */
	public static function image_guidance( $context = 'photo' ) {
		switch ( $context ) {
			case 'gallery':
				$dims = __( 'Preferred: landscape, at least 1200 x 800 pixels, JPG, PNG or WebP, under 3 MB each (up to 6).', 'shuffles-social-services-jobs' );
				$tip  = __( 'Show your real work and the value you bring (support activities, gardening, a tidy result). Use bright, in-focus, recent photos. Only include people who have agreed to appear, and never identify a participant.', 'shuffles-social-services-jobs' );
				break;
			case 'logo':
				$dims = __( 'Preferred: square or wide, at least 400 x 400 pixels (or 800 pixels wide), transparent PNG or SVG, under 1 MB.', 'shuffles-social-services-jobs' );
				$tip  = __( 'Use your real, current logo. Keep it simple and legible at small sizes. Avoid screenshots, photos of a logo, or text that becomes unreadable on a card.', 'shuffles-social-services-jobs' );
				break;
			case 'job_logo':
				$dims = __( 'Preferred: square, at least 400 x 400 pixels, transparent PNG or SVG, under 1 MB.', 'shuffles-social-services-jobs' );
				$tip  = __( 'Leave blank to use your organisation logo. Upload one only to override it for this listing. Keep it clean and legible at small sizes.', 'shuffles-social-services-jobs' );
				break;
			case 'photo':
			default:
				$dims = __( 'Preferred: square, at least 600 x 600 pixels, JPG, PNG or WebP, under 2 MB.', 'shuffles-social-services-jobs' );
				$tip  = __( 'A warm, well-lit headshot with your face clearly visible and a plain background builds trust. Avoid group shots, heavy filters, logos or text. Be yourself and look approachable.', 'shuffles-social-services-jobs' );
				break;
		}
		return '<p class="description sssj-media-guide"><strong>' . esc_html__( 'Image guide:', 'shuffles-social-services-jobs' ) . '</strong> '
			. esc_html( $dims ) . ' ' . esc_html( $tip ) . '</p>';
	}

	/** Short guidance for the promotional video field. */
	public static function video_help() {
		return __( 'Paste a YouTube or Vimeo link. A good clip is 30 to 90 seconds, landscape, with captions, a friendly introduction to your service or brand, and one clear call to action. Only include people who have agreed to appear, pay anyone in a commercial production fairly, and never identify a participant.', 'shuffles-social-services-jobs' );
	}

	/**
	 * A complete "Promotional video" form field (label + url input + guidance).
	 *
	 * @param string $value Current value.
	 * @param string $context worker | org | job (tunes the label only).
	 * @return string
	 */
	public static function video_field( $value = '', $context = 'worker' ) {
		$label = ( 'org' === $context )
			? __( 'Promotional video (YouTube or Vimeo)', 'shuffles-social-services-jobs' )
			: ( ( 'job' === $context )
				? __( 'Video for this listing (YouTube or Vimeo)', 'shuffles-social-services-jobs' )
				: __( 'Introduce yourself on video (YouTube or Vimeo)', 'shuffles-social-services-jobs' ) );
		$out  = '<div class="sssj-field">';
		$out .= '<label for="sssj-video-' . esc_attr( $context ) . '">🎬 ' . esc_html( $label ) . '</label>';
		$out .= '<input class="sssj-input" id="sssj-video-' . esc_attr( $context ) . '" type="url" name="video_url" value="' . esc_attr( $value ) . '" placeholder="https://www.youtube.com/watch?v=…  or  https://vimeo.com/…" />';
		$out .= '<p class="description">' . esc_html( self::video_help() ) . '</p>';
		$out .= '</div>';
		return $out;
	}

	/**
	 * Normalise a submitted video URL. Accepts YouTube or Vimeo only; returns '' for anything else
	 * (so we never embed an arbitrary third-party iframe).
	 */
	public static function clean_video_url( $raw ) {
		$raw = trim( (string) $raw );
		if ( '' === $raw ) {
			return '';
		}
		$url  = esc_url_raw( $raw );
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$ok   = array( 'youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'youtube-nocookie.com', 'www.youtube-nocookie.com', 'vimeo.com', 'www.vimeo.com', 'player.vimeo.com' );
		foreach ( $ok as $h ) {
			if ( $host === $h ) {
				return $url;
			}
		}
		return '';
	}

	/** Extract the YouTube video id from a URL, or '' . */
	private static function youtube_id( $url ) {
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		if ( false !== strpos( $host, 'youtu.be' ) ) {
			return trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
		}
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		if ( 0 === strpos( $path, '/embed/' ) ) {
			return sanitize_text_field( substr( $path, 7 ) );
		}
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $q );
		return isset( $q['v'] ) ? sanitize_text_field( $q['v'] ) : '';
	}

	/** Extract the numeric Vimeo id from a URL, or '' . */
	private static function vimeo_id( $url ) {
		if ( preg_match( '#vimeo\.com/(?:video/)?(\d+)#', $url, $m ) ) {
			return $m[1];
		}
		return '';
	}

	/**
	 * A responsive, privacy-friendly embed for a YouTube or Vimeo URL. Returns '' if unrecognised.
	 *
	 * @param string $url Stored video URL.
	 * @return string
	 */
	public static function video_embed( $url ) {
		$url = self::clean_video_url( $url );
		if ( '' === $url ) {
			return '';
		}
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$src  = '';
		if ( false !== strpos( $host, 'youtu' ) ) {
			$id = self::youtube_id( $url );
			if ( '' !== $id ) {
				$src = 'https://www.youtube-nocookie.com/embed/' . rawurlencode( $id );
			}
		} elseif ( false !== strpos( $host, 'vimeo' ) ) {
			$id = self::vimeo_id( $url );
			if ( '' !== $id ) {
				$src = 'https://player.vimeo.com/video/' . rawurlencode( $id );
			}
		}
		if ( '' === $src ) {
			return '';
		}
		return '<div class="sssj-video"><iframe src="' . esc_url( $src ) . '" title="' . esc_attr__( 'Promotional video', 'shuffles-social-services-jobs' ) . '" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div>';
	}
}
