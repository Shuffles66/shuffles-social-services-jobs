<?php
/**
 * Affiliate / referral earning — optional, standalone-safe integration with the FluentAffiliate
 * plugin (slug: fluent-affiliate).
 *
 * Purpose: invite members — ESPECIALLY participants — to earn a bit of income by referring others
 * to the marketplace. We never bundle or require FluentAffiliate; if it is not active (and no
 * affiliate URL is configured), the promo simply does not appear.
 *
 * FluentAffiliate exposes its affiliate area (sign-up + dashboard) via the [fluent_affiliate_portal]
 * shortcode on an admin-designated page. We detect that and link to it (or to a configured URL).
 *
 * IMPORTANT (product requirement): members must be told they need a PayPal account to be paid, and
 * that they can set the affiliate side up LATER — it never blocks onboarding.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Affiliate {

	/** Is the FluentAffiliate plugin active? (version-agnostic — checks its portal shortcode.) */
	public static function is_active() {
		return shortcode_exists( 'fluent_affiliate_portal' ) || defined( 'FLUENT_AFFILIATE_VERSION' ) || function_exists( 'fluentAffiliate' );
	}

	/** Are referral promos switched on? (Default ON when FluentAffiliate is active or a URL is set.) */
	public static function enabled() {
		$o = get_option( 'shuffles_ssj_settings', array() );
		if ( is_array( $o ) && isset( $o['affiliate_enabled'] ) && ! $o['affiliate_enabled'] ) {
			return false;
		}
		return self::is_active() || ( is_array( $o ) && ! empty( $o['affiliate_url'] ) );
	}

	/** The affiliate area URL: a configured override, else the page that runs [fluent_affiliate_portal]. */
	public static function url() {
		$o = get_option( 'shuffles_ssj_settings', array() );
		$u = ( is_array( $o ) && ! empty( $o['affiliate_url'] ) ) ? trim( (string) $o['affiliate_url'] ) : '';
		if ( '' !== $u ) {
			return esc_url_raw( $u );
		}
		if ( class_exists( 'Shuffles_SSJ_Shortcodes' ) ) {
			$found = Shuffles_SSJ_Shortcodes::page_link( '', '[fluent_affiliate_portal]' );
			if ( $found ) {
				return (string) $found;
			}
		}
		return '';
	}

	/**
	 * The "earn money by referring" promo card. Returns '' if disabled or there is nowhere to link.
	 *
	 * @param int        $uid     The member (0 for logged-out).
	 * @param array|null $roles   Member roles (fetched if null) — used to tailor for participants.
	 * @param string     $context 'onboard' tweaks the copy ("after you finish onboarding").
	 * @return string
	 */
	public static function render_card( $uid = 0, $roles = null, $context = '' ) {
		if ( ! self::enabled() ) {
			return '';
		}
		$url = self::url();
		if ( '' === $url ) {
			return '';
		}
		$uid = (int) $uid;
		if ( null === $roles ) {
			$roles = ( $uid && class_exists( 'Shuffles_SSJ_Roles' ) ) ? Shuffles_SSJ_Roles::member_roles( $uid ) : array();
		}
		$is_participant = is_array( $roles ) && ( in_array( 'participant', $roles, true ) || in_array( 'representative', $roles, true ) );

		ob_start();
		?>
		<div class="sssj sssj--affiliate">
			<div class="sssj-panel sssj-affiliate">
				<h3 class="sssj-affiliate__title">💸 <?php esc_html_e( 'Earn money by referring others', 'shuffles-social-services-jobs' ); ?></h3>
				<p>
					<?php esc_html_e( 'Love using the marketplace? You can earn a little income by inviting other people to join. When someone you refer signs up through your personal link, you earn a referral reward.', 'shuffles-social-services-jobs' ); ?>
				</p>
				<?php if ( $is_participant ) : ?>
					<p class="sssj-affiliate__forparticipants">
						<?php esc_html_e( 'This is a great, flexible way for participants to earn a bit of extra money — share your link with people who might find support or work here.', 'shuffles-social-services-jobs' ); ?>
					</p>
				<?php endif; ?>

				<div class="sssj-affiliate__paypal" role="note">
					<strong>💳 <?php esc_html_e( 'You’ll need a PayPal account to be paid.', 'shuffles-social-services-jobs' ); ?></strong>
					<span>
						<?php
						printf(
							/* translators: %s: paypal.com link */
							esc_html__( 'Referral rewards are paid to your PayPal account. Don’t have one yet? It’s free to create at %s.', 'shuffles-social-services-jobs' ),
							'<a href="https://www.paypal.com/au/" target="_blank" rel="noopener nofollow">paypal.com</a>'
						);
						?>
					</span>
				</div>

				<p class="sssj-affiliate__later description">
					<?php
					if ( 'onboard' === $context ) {
						esc_html_e( 'No rush — you can set this up later, after you finish setting up your account. It’s completely optional.', 'shuffles-social-services-jobs' );
					} else {
						esc_html_e( 'It’s completely optional, and you can set it up whenever you’re ready.', 'shuffles-social-services-jobs' );
					}
					?>
				</p>

				<p style="margin-bottom:0">
					<a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">
						<?php esc_html_e( 'Join the referral program', 'shuffles-social-services-jobs' ); ?> →
					</a>
				</p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/** [sssj_affiliate] — the referral promo card, placeable anywhere (e.g. the dashboard). */
	public static function shortcode( $atts ) {
		wp_enqueue_style( 'sssj' );
		return self::render_card( get_current_user_id() );
	}
}
