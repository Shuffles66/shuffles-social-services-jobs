<?php
/**
 * Marketing master, rendered on the site. Reads the canonical living document
 * docs/MARKETING-MASTER.md (the single source) and converts a safe subset of Markdown to HTML so
 * it can be published as a readable page via [sssj_marketing] and previewed in Settings to Marketing.
 *
 * Keep the content in docs/MARKETING-MASTER.md. This class only renders it.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Marketing {

	/** Absolute path to the canonical marketing document. */
	public static function source_path() {
		return SHUFFLES_SSJ_DIR . 'docs/MARKETING-MASTER.md';
	}

	/** Raw markdown ('' if the file is not present). */
	public static function markdown() {
		$path = self::source_path();
		if ( is_readable( $path ) ) {
			$md = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			return is_string( $md ) ? $md : '';
		}
		return '';
	}

	/** Inline markdown to HTML (links, bold, italic, code) applied to one escaped line. */
	private static function inline( $raw ) {
		$t = esc_html( (string) $raw );
		// [text](url)
		$t = preg_replace_callback(
			'/\[([^\]]+)\]\(([^)\s]+)\)/',
			function ( $m ) {
				return '<a href="' . esc_url( $m[2] ) . '" target="_blank" rel="noopener">' . $m[1] . '</a>';
			},
			$t
		);
		$t = preg_replace( '/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $t );
		$t = preg_replace( '/(?<!\*)\*(?!\*)([^*]+)\*(?!\*)/', '<em>$1</em>', $t );
		$t = preg_replace( '/`([^`]+)`/', '<code>$1</code>', $t );
		return $t;
	}

	/**
	 * Convert the supported subset of Markdown (headings, hr, unordered + ordered lists, paragraphs,
	 * and inline bold/italic/code/links) to HTML. No tables, blockquotes or code fences are needed.
	 *
	 * @param string $md Markdown.
	 * @return string HTML.
	 */
	public static function to_html( $md ) {
		$lines = preg_split( '/\r\n|\r|\n/', (string) $md );
		$html  = '';
		$list  = ''; // '', 'ul' or 'ol'
		$para  = array();

		$flush_para = function () use ( &$para, &$html ) {
			if ( ! empty( $para ) ) {
				$html .= '<p>' . implode( ' ', $para ) . '</p>';
				$para  = array();
			}
		};
		$close_list = function () use ( &$list, &$html ) {
			if ( '' !== $list ) {
				$html .= ( 'ul' === $list ) ? '</ul>' : '</ol>';
				$list  = '';
			}
		};

		foreach ( $lines as $line ) {
			$trim = rtrim( $line );

			if ( '' === trim( $trim ) ) {
				$flush_para();
				$close_list();
				continue;
			}
			// Headings.
			if ( preg_match( '/^(#{1,6})\s+(.*)$/', $trim, $m ) ) {
				$flush_para();
				$close_list();
				$lvl   = min( 6, max( 2, strlen( $m[1] ) + 1 ) ); // shift down one (no second h1 on the page)
				$html .= '<h' . $lvl . '>' . self::inline( $m[2] ) . '</h' . $lvl . '>';
				continue;
			}
			// Horizontal rule.
			if ( preg_match( '/^(-{3,}|\*{3,})$/', trim( $trim ) ) ) {
				$flush_para();
				$close_list();
				$html .= '<hr />';
				continue;
			}
			// Ordered list item.
			if ( preg_match( '/^\s*\d+\.\s+(.*)$/', $trim, $m ) ) {
				$flush_para();
				if ( 'ol' !== $list ) {
					$close_list();
					$html .= '<ol>';
					$list  = 'ol';
				}
				$html .= '<li>' . self::inline( $m[1] ) . '</li>';
				continue;
			}
			// Unordered list item.
			if ( preg_match( '/^\s*[-*]\s+(.*)$/', $trim, $m ) ) {
				$flush_para();
				if ( 'ul' !== $list ) {
					$close_list();
					$html .= '<ul>';
					$list  = 'ul';
				}
				$html .= '<li>' . self::inline( $m[1] ) . '</li>';
				continue;
			}
			// Paragraph text (accumulate consecutive lines).
			$close_list();
			$para[] = self::inline( $trim );
		}
		$flush_para();
		$close_list();

		return wp_kses_post( $html );
	}

	/**
	 * Render the marketing master as a readable block.
	 *
	 * @param array $atts (unused for now).
	 * @return string
	 */
	public static function render( $atts = array() ) {
		// Members-only: the marketing master is not readable by logged-out visitors.
		if ( ! is_user_logged_in() ) {
			return '<div class="sssj sssj--marketing"><div class="sssj-panel"><p>'
				. esc_html__( 'Please log in to read this.', 'shuffles-social-services-jobs' )
				. ' <a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Log in', 'shuffles-social-services-jobs' ) . '</a></p></div></div>';
		}
		$md = self::markdown();
		if ( '' === trim( $md ) ) {
			return '<div class="sssj sssj--marketing"><div class="sssj-panel"><p>'
				. esc_html__( 'The marketing document is not available on this site yet.', 'shuffles-social-services-jobs' )
				. '</p></div></div>';
		}
		return '<div class="sssj sssj--marketing"><div class="sssj-panel sssj-doc">' . self::to_html( $md ) . '</div></div>';
	}

	/** [sssj_marketing] */
	public static function shortcode( $atts ) {
		wp_enqueue_style( 'sssj' );
		return self::render( is_array( $atts ) ? $atts : array() );
	}

	/** Keep the configured Marketing page out of search engines (not findable). */
	public static function noindex_marketing( $robots ) {
		$opts = get_option( 'shuffles_ssj_settings', array() );
		$pid  = is_array( $opts ) && ! empty( $opts['page_marketing'] ) ? (int) $opts['page_marketing'] : 0;
		if ( $pid && ( is_page( $pid ) || is_singular() && get_queried_object_id() === $pid ) ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
		}
		return $robots;
	}
}
