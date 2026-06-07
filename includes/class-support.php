<?php
/**
 * Support desk integration (standalone-safe, runtime-detected).
 *
 * If a front-end support portal is available, we surface it as a "Support" tab inside the member
 * dashboard so members raise and track help requests without leaving the marketplace. We never
 * bundle or require the support desk: if it is not present (and no support URL is configured), the
 * tab simply does not appear.
 *
 * The support desk exposes its customer portal via the [fluent_support_portal] shortcode on an
 * admin-designated page. We detect that and embed it, or link to a configured URL.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Support {

	/** Is a front-end support portal available? (version-agnostic, checks the portal shortcode.) */
	public static function is_active() {
		return shortcode_exists( 'fluent_support_portal' ) || defined( 'FLUENT_SUPPORT_VERSION' );
	}

	/** Should the support tab show? (Active portal, or a configured support URL.) */
	public static function enabled() {
		$o = get_option( 'shuffles_ssj_settings', array() );
		if ( is_array( $o ) && isset( $o['support_enabled'] ) && ! $o['support_enabled'] ) {
			return false;
		}
		return self::is_active() || ( is_array( $o ) && ! empty( $o['support_url'] ) );
	}

	/** A configured support URL, else the page that runs the portal shortcode, else ''. */
	public static function url() {
		$o = get_option( 'shuffles_ssj_settings', array() );
		$u = ( is_array( $o ) && ! empty( $o['support_url'] ) ) ? trim( (string) $o['support_url'] ) : '';
		if ( '' !== $u ) {
			return esc_url_raw( $u );
		}
		if ( class_exists( 'Shuffles_SSJ_Shortcodes' ) ) {
			$found = Shuffles_SSJ_Shortcodes::page_link( '', '[fluent_support_portal]' );
			if ( $found ) {
				return (string) $found;
			}
		}
		return '';
	}

	/**
	 * The support dashboard for embedding in a tab: the portal itself if available, otherwise a
	 * friendly link out. Returns '' only if there is nothing to show.
	 *
	 * @return string
	 */
	public static function render() {
		if ( ! self::enabled() ) {
			return '';
		}
		if ( shortcode_exists( 'fluent_support_portal' ) ) {
			$inner = do_shortcode( '[fluent_support_portal]' );
			if ( '' !== trim( (string) $inner ) ) {
				return '<div class="sssj sssj--support">' . $inner . '</div>';
			}
		}
		$url = self::url();
		if ( '' !== $url ) {
			ob_start();
			?>
			<div class="sssj sssj--support">
				<div class="sssj-panel">
					<h3 style="margin-top:0"><?php esc_html_e( 'Support', 'shuffles-social-services-jobs' ); ?></h3>
					<p><?php esc_html_e( 'Need a hand? Raise a request and track it in one place.', 'shuffles-social-services-jobs' ); ?></p>
					<p style="margin-bottom:0"><a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Open support', 'shuffles-social-services-jobs' ); ?> →</a></p>
				</div>
			</div>
			<?php
			return ob_get_clean();
		}
		return '';
	}
}
