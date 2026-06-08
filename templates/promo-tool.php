<?php
/**
 * Self-promotion studio ([sssj_promo]). Vars: $positives (array), $today (int).
 * Reuses the shareable-asset rasteriser (sssj-assets.js): same [data-sssj-asset-wizard]
 * + #sssj-asset + [data-action] + data-caption hooks. sssj-promo.js cycles the positives.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array $positives */
/** @var int   $today */

$cur  = $positives[ $today ];
$site = Shuffles_SSJ_Promo::site_name();
$host = Shuffles_SSJ_Promo::site_host();
?>
<div class="sssj sssj--promo" data-sssj-asset-wizard data-sssj-promo
	data-caption="<?php echo esc_attr( $cur['caption'] ); ?>"
	data-filename="shuffles-promo.png">
	<div class="sssj-panel">
		<h2><?php esc_html_e( 'Promote the platform', 'shuffles-social-services-jobs' ); ?></h2>
		<p class="description"><?php echo esc_html( sprintf( __( 'One positive at a time, straight from real %s activity. Pick a highlight, save the square image and copy the caption, then post it to your channels. Nothing here ever shows a participant or any private detail.', 'shuffles-social-services-jobs' ), $site ) ); ?></p>

		<div class="sssj-promo-studio">

			<div class="sssj-promo-stage">
				<div class="sssj-promo" id="sssj-asset" data-accent="<?php echo esc_attr( (int) $cur['accent'] ); ?>">
					<div class="sssj-promo__brand">
						<span class="sssj-promo__brandname"><?php echo esc_html( $site ); ?></span>
					</div>
					<div class="sssj-promo__body" data-promo-body><?php echo Shuffles_SSJ_Promo::body_html( $cur ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<div class="sssj-promo__foot">
						<span class="sssj-promo__url"><?php echo esc_html( $host ); ?></span>
						<span class="sssj-promo__safe">🛡️ <?php esc_html_e( 'Safety, built in', 'shuffles-social-services-jobs' ); ?></span>
					</div>
				</div>
			</div>

			<div class="sssj-promo-controls">
				<div class="sssj-row" style="justify-content:space-between">
					<button type="button" class="sssj-btn sssj-btn--ghost sssj-btn--sm" data-promo-nav="prev">&laquo; <?php esc_html_e( 'Previous', 'shuffles-social-services-jobs' ); ?></button>
					<button type="button" class="sssj-btn sssj-btn--secondary sssj-btn--sm" data-promo-nav="next"><?php esc_html_e( 'Show another', 'shuffles-social-services-jobs' ); ?> &raquo;</button>
				</div>

				<p class="description" style="margin:10px 0 4px"><strong><?php esc_html_e( 'Choose a highlight', 'shuffles-social-services-jobs' ); ?></strong></p>
				<select class="sssj-select" data-promo-pick>
					<?php foreach ( $positives as $i => $p ) : ?>
						<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $i, $today ); ?>><?php echo esc_html( $p['label'] ); ?></option>
					<?php endforeach; ?>
				</select>

				<p class="description" style="margin:12px 0 4px"><strong><?php esc_html_e( 'Caption (copy and paste)', 'shuffles-social-services-jobs' ); ?></strong></p>
				<textarea class="sssj-textarea" rows="5" data-promo-caption readonly><?php echo esc_textarea( $cur['caption'] ); ?></textarea>

				<div class="sssj-row" style="margin-top:10px">
					<button type="button" class="sssj-btn sssj-btn--primary sssj-btn--sm" data-action="png"><?php esc_html_e( 'Save image', 'shuffles-social-services-jobs' ); ?></button>
					<button type="button" class="sssj-btn sssj-btn--ghost sssj-btn--sm" data-action="caption"><?php esc_html_e( 'Copy caption', 'shuffles-social-services-jobs' ); ?></button>
				</div>
				<p class="description" data-asset-msg style="margin-top:8px"></p>
			</div>

		</div>

		<div class="sssj-readme" style="margin-top:16px">
			<details>
				<summary style="cursor:pointer;padding:10px 14px;font-weight:700"><?php esc_html_e( 'How this works', 'shuffles-social-services-jobs' ); ?></summary>
				<div style="padding:0 14px 12px">
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Every highlight is built from real, public platform numbers or our own brand messages — never participant data.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'A counter only appears once it is big enough to impress; small early numbers stay hidden.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( '“Save image” downloads a square PNG ready for Instagram, Facebook or LinkedIn. “Copy caption” copies the matching words and hashtags.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Post one at a time, whenever you like. Automatic posting can come later.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				</div>
			</details>
		</div>
	</div>
</div>
