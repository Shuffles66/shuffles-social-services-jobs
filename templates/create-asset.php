<?php
/**
 * "Create an asset" wizard, Phase 1: the worker / sole-trader résumé.
 * Renders a live preview of the locked template from the member's profile, a small wording panel,
 * a readability check, and the download / copy actions ($0 browser path).
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	echo '<div class="sssj"><div class="sssj-panel"><p>' . esc_html__( 'Log in to create your résumé.', 'shuffles-social-services-jobs' )
		. ' <a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Log in', 'shuffles-social-services-jobs' ) . '</a></p></div></div>';
	return;
}

$uid  = get_current_user_id();
$data = Shuffles_SSJ_Assets::resume_data( $uid );

if ( null === $data ) {
	$profile_url = class_exists( 'Shuffles_SSJ_Shortcodes' ) ? Shuffles_SSJ_Shortcodes::page_link( 'page_post_worker', '[sssj_post_worker]' ) : '';
	echo '<div class="sssj"><div class="sssj-panel"><h2 style="margin-top:0">' . esc_html__( 'Create your résumé', 'shuffles-social-services-jobs' ) . '</h2>'
		. '<p>' . esc_html__( 'First create your worker profile. Your résumé is built from it automatically, so you never start from a blank page.', 'shuffles-social-services-jobs' ) . '</p>';
	if ( $profile_url ) {
		echo '<a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' . esc_url( $profile_url ) . '">' . esc_html__( 'Create my profile', 'shuffles-social-services-jobs' ) . '</a>';
	}
	echo '</div></div>';
	return;
}

$check       = Shuffles_SSJ_Assets::readability( $data );
$caption     = Shuffles_SSJ_Assets::caption( $data );
$profile_url = class_exists( 'Shuffles_SSJ_Shortcodes' ) ? Shuffles_SSJ_Shortcodes::page_link( 'page_post_worker', '[sssj_post_worker]' ) : '';
?>
<div class="sssj sssj--create-asset" data-sssj-asset-wizard data-caption="<?php echo esc_attr( $caption ); ?>">
	<div class="sssj-panel">
		<h2 style="margin-top:0"><?php esc_html_e( 'Create your résumé', 'shuffles-social-services-jobs' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Built from your profile, in a clean, easy-to-read layout. Polish the wording on the left, then download a PDF, save the image, or copy a caption for social media. Your location leads, so people see where you work at a glance.', 'shuffles-social-services-jobs' ); ?></p>
	</div>

	<div class="sssj-asset-wizard">
		<div class="sssj-asset-wizard__controls">
			<div class="sssj-panel">
				<h3 style="margin-top:0"><?php esc_html_e( 'Polish your wording', 'shuffles-social-services-jobs' ); ?></h3>
				<label class="sssj-field">
					<span><?php esc_html_e( 'Headline (your role)', 'shuffles-social-services-jobs' ); ?></span>
					<input type="text" class="sssj-input" data-edit="tagline" maxlength="60" value="<?php echo esc_attr( $data['tagline'] ); ?>" />
				</label>
				<label class="sssj-field">
					<span><?php esc_html_e( 'Short introduction', 'shuffles-social-services-jobs' ); ?></span>
					<textarea class="sssj-input" data-edit="blurb" rows="4" maxlength="500"><?php echo esc_textarea( $data['blurb'] ); ?></textarea>
					<span class="description" data-blurb-count></span>
				</label>
				<p class="description">
					<?php esc_html_e( 'Your photo, location, services, languages and verified checks come from your profile.', 'shuffles-social-services-jobs' ); ?>
					<?php if ( $profile_url ) : ?>
						<a href="<?php echo esc_url( $profile_url ); ?>"><?php esc_html_e( 'Edit my profile', 'shuffles-social-services-jobs' ); ?></a>
					<?php endif; ?>
				</p>
			</div>

			<div class="sssj-panel">
				<h3 style="margin-top:0"><?php esc_html_e( 'Readability check', 'shuffles-social-services-jobs' ); ?></h3>
				<ul class="sssj-asset-check">
					<?php foreach ( $check['items'] as $item ) : ?>
						<li class="<?php echo $item['ok'] ? 'is-ok' : 'is-warn'; ?>">
							<span class="sssj-asset-check__mark" aria-hidden="true"><?php echo $item['ok'] ? '✓' : '!'; ?></span>
							<span><?php echo esc_html( $item['label'] ); ?><?php if ( ! $item['ok'] ) : ?><br /><small class="description"><?php echo esc_html( $item['note'] ); ?></small><?php endif; ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="sssj-panel">
				<h3 style="margin-top:0"><?php esc_html_e( 'Download and share', 'shuffles-social-services-jobs' ); ?></h3>
				<div class="sssj-row" style="flex-wrap:wrap;gap:8px">
					<button type="button" class="sssj-btn sssj-btn--primary sssj-btn--sm" data-action="pdf"><?php esc_html_e( 'Download PDF', 'shuffles-social-services-jobs' ); ?></button>
					<button type="button" class="sssj-btn sssj-btn--secondary sssj-btn--sm" data-action="png"><?php esc_html_e( 'Save image', 'shuffles-social-services-jobs' ); ?></button>
					<button type="button" class="sssj-btn sssj-btn--ghost sssj-btn--sm" data-action="caption"><?php esc_html_e( 'Copy caption', 'shuffles-social-services-jobs' ); ?></button>
				</div>
				<p class="description" data-asset-msg style="margin-top:8px"></p>
				<p class="description"><?php esc_html_e( 'PDF uses your browser print dialog (choose “Save as PDF”). The image is sized for a social post.', 'shuffles-social-services-jobs' ); ?></p>
			</div>
		</div>

		<div class="sssj-asset-wizard__preview">
			<div class="sssj-asset-print">
				<?php Shuffles_SSJ_Plugin::instance()->shortcodes->load_template( 'assets/resume.php', array( 'data' => $data ) ); ?>
			</div>
		</div>
	</div>
</div>
