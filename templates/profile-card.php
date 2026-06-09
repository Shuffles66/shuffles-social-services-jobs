<?php
/**
 * AI Profile Card generator ([sssj_profile_card]). Members only; gated + off until an OpenAI key is set.
 *
 * The browser fetches styles from /sssj/v1/card-styles, asks /card-generate for a TEXT-FREE AI background,
 * then composites the member's location + services (top) and name/tagline (bottom) on a <canvas> and offers
 * download + save-to-media. All wiring is in sssj-profilecard.js; window.SSSJ_Card holds the endpoints.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$uid    = get_current_user_id();
$member = Shuffles_SSJ_Profile_Card::member_data( $uid );
$limit  = Shuffles_SSJ_Profile_Card::limit_for( $uid );
$used   = Shuffles_SSJ_Profile_Card::used_this_month( $uid );
$styles = Shuffles_SSJ_Profile_Card::styles();
$has_profile = '' !== trim( (string) $member['name'] ) && ( '' !== trim( (string) $member['location'] ) || ! empty( $member['services'] ) );
?>
<div class="sssj sssj--card" data-sssj-card>
	<div class="sssj-panel">
		<h2><?php esc_html_e( 'Make your profile card', 'shuffles-social-services-jobs' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Turn your profile into a beautiful, shareable square image. Pick a style and the artwork is created for you, then your location, services and name are placed on top. Save it and post it anywhere.', 'shuffles-social-services-jobs' ); ?></p>

		<?php if ( ! $has_profile ) : ?>
			<div class="sssj-note" style="margin:10px 0">
				<?php esc_html_e( 'Tip: fill in your location and the services you offer on your profile first, so they appear on the card.', 'shuffles-social-services-jobs' ); ?>
			</div>
		<?php endif; ?>

		<div class="sssj-promo-studio">

			<div class="sssj-promo-stage">
				<div class="sssj-card-stage" style="position:relative;width:100%;max-width:480px;margin:0 auto">
					<canvas id="sssj-card-canvas" width="1080" height="1080" style="width:100%;aspect-ratio:1/1;border-radius:12px;background:#eef1f5;display:block"></canvas>
					<div class="sssj-card-empty" data-card-empty style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;text-align:center;color:#64748b;padding:24px">
						<span style="font-size:40px;line-height:1" aria-hidden="true">🎨</span>
						<span style="font-weight:600"><?php esc_html_e( 'Your card preview will appear here', 'shuffles-social-services-jobs' ); ?></span>
						<span class="description" style="margin:0"><?php esc_html_e( 'Pick a style, then click “Create my card”.', 'shuffles-social-services-jobs' ); ?></span>
					</div>
				</div>
			</div>

			<div class="sssj-promo-controls">
				<p class="description" style="margin:0 0 4px"><strong><?php esc_html_e( 'Choose a style', 'shuffles-social-services-jobs' ); ?></strong></p>
				<select class="sssj-select" data-card-style>
					<?php foreach ( $styles as $key => $s ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $s['emoji'] . '  ' . $s['label'] ); ?></option>
					<?php endforeach; ?>
					<option value="custom">✏️  <?php esc_html_e( 'Custom (describe your own)', 'shuffles-social-services-jobs' ); ?></option>
				</select>

				<div class="sssj-card-custom" data-card-custom-wrap style="margin-top:8px;display:none">
					<textarea class="sssj-textarea" data-card-custom rows="3" maxlength="280" placeholder="<?php echo esc_attr__( 'e.g. soft watercolour sunset over the ocean, calm pastel blues and corals, native Australian banksia', 'shuffles-social-services-jobs' ); ?>"></textarea>
					<p class="description" style="margin:4px 0 0"><?php esc_html_e( 'Describe the look and colours you want. The artwork stays text-free; your details are placed on top.', 'shuffles-social-services-jobs' ); ?></p>
				</div>

				<div class="sssj-card-fields" style="margin-top:12px">
					<p class="description" style="margin:0 0 4px"><strong><?php esc_html_e( 'On your card', 'shuffles-social-services-jobs' ); ?></strong></p>
					<ul class="ul-disc" style="margin:0">
						<li><?php echo esc_html( $member['name'] ? $member['name'] : __( '(your name)', 'shuffles-social-services-jobs' ) ); ?></li>
						<li><?php echo esc_html( $member['location'] ? $member['location'] : __( '(add your location)', 'shuffles-social-services-jobs' ) ); ?></li>
						<li><?php echo esc_html( ! empty( $member['services'] ) ? implode( ', ', array_slice( (array) $member['services'], 0, 4 ) ) : __( '(add your services)', 'shuffles-social-services-jobs' ) ); ?></li>
					</ul>
				</div>

				<div class="sssj-row" style="margin-top:14px">
					<button type="button" class="sssj-btn sssj-btn--primary" data-card-generate>
						<?php esc_html_e( 'Create my card', 'shuffles-social-services-jobs' ); ?>
					</button>
				</div>

				<div class="sssj-row" style="margin-top:10px;display:none" data-card-actions>
					<button type="button" class="sssj-btn sssj-btn--secondary sssj-btn--sm" data-card-download><?php esc_html_e( 'Download', 'shuffles-social-services-jobs' ); ?></button>
					<button type="button" class="sssj-btn sssj-btn--ghost sssj-btn--sm" data-card-save><?php esc_html_e( 'Save to my media', 'shuffles-social-services-jobs' ); ?></button>
				</div>

				<p class="description" data-card-msg style="margin-top:8px"></p>

				<?php if ( $limit < 999 ) : ?>
					<p class="description" data-card-usage style="margin-top:4px;opacity:.8">
						<?php echo esc_html( sprintf( __( 'Used %1$d of %2$d this month.', 'shuffles-social-services-jobs' ), $used, $limit ) ); ?>
					</p>
				<?php endif; ?>
			</div>

		</div>

		<div class="sssj-readme" style="margin-top:16px">
			<details>
				<summary style="cursor:pointer;padding:10px 14px;font-weight:700"><?php esc_html_e( 'How this works', 'shuffles-social-services-jobs' ); ?></summary>
				<div style="padding:0 14px 12px">
					<ul class="ul-disc">
						<li><?php esc_html_e( 'The artwork is a decorative, text-free background made for the style you pick. Your details are drawn on top in your browser, so the words are always sharp and correct.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Only your own profile details are used: your name, suburb and services. Nothing private and no one else’s information is ever included.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( '“Download” saves a square PNG ready for Instagram, Facebook or LinkedIn. “Save to my media” keeps a copy in your library.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				</div>
			</details>
		</div>
	</div>
</div>
