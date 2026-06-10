<?php
/**
 * "Create an asset" wizard (Phase 1 + 1b): worker / sole-trader résumé, service flyer and social
 * graphic. Renders a live preview of the chosen locked template from the member's profile, a small
 * wording panel, a readability check, and the download / copy actions ($0 browser path).
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	echo '<div class="sssj"><div class="sssj-panel"><p>' . esc_html__( 'Log in to create your asset.', 'shuffles-social-services-jobs' )
		. ' <a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' . esc_url( Shuffles_SSJ_Shortcodes::login_url( get_permalink() ) ) . '">' . esc_html__( 'Log in', 'shuffles-social-services-jobs' ) . '</a></p></div></div>';
	return;
}

$atts  = isset( $atts ) && is_array( $atts ) ? $atts : array();
$types = array(
	'resume' => array( 'assets/resume.php', __( 'Résumé', 'shuffles-social-services-jobs' ), __( 'A clean one-page résumé.', 'shuffles-social-services-jobs' ) ),
	'flyer'  => array( 'assets/service-flyer.php', __( 'Service flyer', 'shuffles-social-services-jobs' ), __( 'A promotional flyer of what you offer.', 'shuffles-social-services-jobs' ) ),
	'social' => array( 'assets/social.php', __( 'Social post', 'shuffles-social-services-jobs' ), __( 'A square image for social media.', 'shuffles-social-services-jobs' ) ),
);
// Job flyer is offered only to members who can post jobs.
if ( current_user_can( 'sssj_post_job' ) || current_user_can( 'manage_options' ) ) {
	$types['job'] = array( 'assets/job-flyer.php', __( 'Job flyer', 'shuffles-social-services-jobs' ), __( 'A flyer for one of your job ads.', 'shuffles-social-services-jobs' ) );
}

// Colour themes (keys must match the .sssj-asset--theme-* CSS classes and the JS THEMES map).
$asset_themes = array(
	'teal'    => __( 'Ocean Teal', 'shuffles-social-services-jobs' ),
	'indigo'  => __( 'Indigo', 'shuffles-social-services-jobs' ),
	'plum'    => __( 'Plum', 'shuffles-social-services-jobs' ),
	'rose'    => __( 'Rose', 'shuffles-social-services-jobs' ),
	'emerald' => __( 'Emerald', 'shuffles-social-services-jobs' ),
	'amber'   => __( 'Sunset Amber', 'shuffles-social-services-jobs' ),
	'slate'   => __( 'Slate', 'shuffles-social-services-jobs' ),
);

/** Render the colour-theme picker panel. */
$theme_picker = function () use ( $asset_themes ) {
	echo '<div class="sssj-panel"><h3 style="margin-top:0">' . esc_html__( 'Colour theme', 'shuffles-social-services-jobs' ) . '</h3>';
	echo '<label class="sssj-field"><span>' . esc_html__( 'Pick a colour theme', 'shuffles-social-services-jobs' ) . '</span>';
	echo '<select class="sssj-select" data-asset-theme>';
	foreach ( $asset_themes as $k => $lbl ) {
		echo '<option value="' . esc_attr( $k ) . '">' . esc_html( $lbl ) . '</option>';
	}
	echo '</select></label>';
	echo '<p class="description">' . esc_html__( 'Changes the colours of your asset. It applies to the live preview, the PDF and the saved image.', 'shuffles-social-services-jobs' ) . '</p></div>';
};

/** Render the ATS vs styled layout toggle (résumé only). */
/** Render the page-size toggle (A4 portrait vs web card) for printable assets (resume / flyers). */
$size_picker = function () {
	echo '<div class="sssj-panel"><h3 style="margin-top:0">' . esc_html__( 'Page size', 'shuffles-social-services-jobs' ) . '</h3>';
	echo '<div class="sssj-row" data-asset-size>';
	echo '<button type="button" class="sssj-btn sssj-btn--sm sssj-btn--primary" data-asset-size-pick="a4">' . esc_html__( 'A4 Portrait', 'shuffles-social-services-jobs' ) . '</button>';
	echo '<button type="button" class="sssj-btn sssj-btn--sm sssj-btn--ghost" data-asset-size-pick="card">' . esc_html__( 'Web card', 'shuffles-social-services-jobs' ) . '</button>';
	echo '</div>';
	echo '<p class="description">' . esc_html__( 'A4 Portrait shows the page exactly as it prints (210 x 297 mm). Web card is a compact preview for sharing on screen. Both export to PDF.', 'shuffles-social-services-jobs' ) . '</p></div>';
};

$format_picker = function () {
	echo '<div class="sssj-panel"><h3 style="margin-top:0">' . esc_html__( 'Layout', 'shuffles-social-services-jobs' ) . '</h3>';
	echo '<div class="sssj-row" data-asset-format>';
	echo '<button type="button" class="sssj-btn sssj-btn--sm sssj-btn--primary" data-asset-format-pick="ats">' . esc_html__( 'ATS-friendly (recommended)', 'shuffles-social-services-jobs' ) . '</button>';
	echo '<button type="button" class="sssj-btn sssj-btn--sm sssj-btn--ghost" data-asset-format-pick="styled">' . esc_html__( 'Styled', 'shuffles-social-services-jobs' ) . '</button>';
	echo '</div>';
	echo '<p class="description">' . esc_html__( 'ATS-friendly is plain, single-column and photo-free, what health, disability and aged-care employers and their recruitment systems prefer. Styled adds your photo and colour, nicer on screen but not ATS-safe.', 'shuffles-social-services-jobs' ) . ' <a href="' . esc_url( apply_filters( 'shuffles_ssj_ats_guide_url', 'https://au.indeed.com/career-advice/resumes-cover-letters/applicant-tracking-systems' ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'What is an ATS? (guide)', 'shuffles-social-services-jobs' ) . '</a></p>';
		echo '<details class="sssj-ats-tips"><summary>' . esc_html__( 'How to make your résumé ATS-friendly', 'shuffles-social-services-jobs' ) . '</summary>';
		echo '<p class="description" style="margin-top:6px"><strong>' . esc_html__( 'Do', 'shuffles-social-services-jobs' ) . '</strong></p><ul>';
		foreach ( array(
			__( 'Keep it single-column and plain (the ATS-friendly option above does this for you).', 'shuffles-social-services-jobs' ),
			__( 'Use standard headings: Professional summary, Employment history, Education.', 'shuffles-social-services-jobs' ),
			__( 'Match the words used in the job ad (e.g. manual handling, progress notes, behaviour support).', 'shuffles-social-services-jobs' ),
			__( 'State your qualifications, screening checks and licences clearly.', 'shuffles-social-services-jobs' ),
			__( 'Save and send it as a PDF using the buttons below.', 'shuffles-social-services-jobs' ),
		) as $tip ) { echo '<li>' . esc_html( $tip ) . '</li>'; }
		echo '</ul><p class="description"><strong>' . esc_html__( 'Avoid', 'shuffles-social-services-jobs' ) . '</strong></p><ul>';
		foreach ( array(
			__( 'Columns, tables, text boxes, icons or graphics.', 'shuffles-social-services-jobs' ),
			__( 'Putting contact details in the page header or footer (some systems skip them).', 'shuffles-social-services-jobs' ),
			__( 'A photo (it is not expected in Australia and can confuse the software).', 'shuffles-social-services-jobs' ),
			__( 'Keyword-stuffing; it must still read naturally to a person.', 'shuffles-social-services-jobs' ),
		) as $tip ) { echo '<li>' . esc_html( $tip ) . '</li>'; }
		echo '</ul></details></div>';
};

$type = isset( $_GET['sssj_asset'] ) ? sanitize_key( wp_unslash( $_GET['sssj_asset'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( '' === $type && ! empty( $atts['asset'] ) ) {
	$type = sanitize_key( (string) $atts['asset'] );
}
if ( ! isset( $types[ $type ] ) ) {
	$type = 'resume';
}

$uid = get_current_user_id();

// Job flyer flow (advertiser): pick one of the member's own jobs, then render its flyer.
if ( 'job' === $type ) {
	$jobs   = Shuffles_SSJ_Assets::member_jobs( $uid );
	$ids    = array_map( function ( $p ) { return (int) $p->ID; }, $jobs );
	$job_id = isset( $_GET['sssj_job_id'] ) ? absint( $_GET['sssj_job_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$jd     = ( $job_id && in_array( $job_id, $ids, true ) ) ? Shuffles_SSJ_Assets::job_data( $job_id ) : null;
	$post_job_url = class_exists( 'Shuffles_SSJ_Shortcodes' ) ? Shuffles_SSJ_Shortcodes::page_link( 'page_post_job', '[sssj_post_job]' ) : '';
	?>
	<div class="sssj sssj--create-asset"<?php if ( $jd ) { echo ' data-sssj-asset-wizard data-asset-type="job" data-asset-job="' . esc_attr( (int) $jd['job_id'] ) . '" data-caption="' . esc_attr( Shuffles_SSJ_Assets::job_caption( $jd ) ) . '"'; } ?>>
		<div class="sssj-panel">
			<h2 style="margin-top:0"><?php esc_html_e( 'Create an asset', 'shuffles-social-services-jobs' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Make a clean, shareable flyer for one of your job ads, with the location and pay leading.', 'shuffles-social-services-jobs' ); ?></p>
			<div class="sssj-asset-types">
				<?php foreach ( $types as $key => $meta ) : ?>
					<a class="sssj-btn sssj-btn--sm <?php echo $key === $type ? 'sssj-btn--primary' : 'sssj-btn--ghost'; ?>" href="<?php echo esc_url( remove_query_arg( 'sssj_job_id', add_query_arg( 'sssj_asset', $key ) ) ) . '#dash-create-asset'; ?>"><?php echo esc_html( $meta[1] ); ?></a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php if ( empty( $jobs ) ) : ?>
			<div class="sssj-panel"><p><?php esc_html_e( 'You have no published job ads yet. Post a job first, then come back to make a flyer for it.', 'shuffles-social-services-jobs' ); ?></p>
				<?php if ( $post_job_url ) : ?><a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="<?php echo esc_url( $post_job_url ); ?>"><?php esc_html_e( 'Post a job', 'shuffles-social-services-jobs' ); ?></a><?php endif; ?></div>
		<?php elseif ( ! $jd ) : ?>
			<div class="sssj-panel"><h3 style="margin-top:0"><?php esc_html_e( 'Pick a job to make a flyer for', 'shuffles-social-services-jobs' ); ?></h3>
				<ul class="sssj-asset-joblist">
					<?php foreach ( $jobs as $j ) : ?>
						<li><a href="<?php echo esc_url( add_query_arg( array( 'sssj_asset' => 'job', 'sssj_job_id' => (int) $j->ID ) ) ) . '#dash-create-asset'; ?>"><?php echo esc_html( get_the_title( $j ) ); ?></a></li>
					<?php endforeach; ?>
				</ul></div>
		<?php else : $jcheck = Shuffles_SSJ_Assets::job_readability( $jd ); ?>
			<div class="sssj-asset-wizard">
				<div class="sssj-asset-wizard__controls">
					<div class="sssj-panel">
						<h3 style="margin-top:0"><?php echo esc_html( $jd['title'] ); ?></h3>
						<p class="description"><?php esc_html_e( 'This flyer is built from the job ad. To change the wording, pay or location, edit the job.', 'shuffles-social-services-jobs' ); ?>
							<a href="<?php echo esc_url( remove_query_arg( 'sssj_job_id', add_query_arg( 'sssj_asset', 'job' ) ) ) . '#dash-create-asset'; ?>"><?php esc_html_e( 'Pick another job', 'shuffles-social-services-jobs' ); ?></a></p>
					</div>
						<?php $theme_picker(); if ( in_array( $type, array( 'resume', 'flyer', 'job' ), true ) ) { $size_picker(); } if ( 'resume' === $type ) { $format_picker(); } ?>
					<div class="sssj-panel">
						<h3 style="margin-top:0"><?php esc_html_e( 'Readability check', 'shuffles-social-services-jobs' ); ?></h3>
						<ul class="sssj-asset-check">
							<?php foreach ( $jcheck['items'] as $item ) : ?>
								<li class="<?php echo $item['ok'] ? 'is-ok' : 'is-warn'; ?>"><span class="sssj-asset-check__mark" aria-hidden="true"><?php echo $item['ok'] ? '✓' : '!'; ?></span><span><?php echo esc_html( $item['label'] ); ?><?php if ( ! $item['ok'] ) : ?><br /><small class="description"><?php echo esc_html( $item['note'] ); ?></small><?php endif; ?></span></li>
							<?php endforeach; ?>
						</ul>
					</div>
					<div class="sssj-panel">
						<h3 style="margin-top:0"><?php esc_html_e( 'Download and share', 'shuffles-social-services-jobs' ); ?></h3>
						<div class="sssj-row" style="flex-wrap:wrap;gap:8px">
							<?php if ( class_exists( 'Shuffles_SSJ_Asset_Renderer' ) && Shuffles_SSJ_Asset_Renderer::enabled() ) : ?>
								<button type="button" class="sssj-btn sssj-btn--primary sssj-btn--sm" data-action="server-pdf"><?php esc_html_e( 'Print-quality PDF', 'shuffles-social-services-jobs' ); ?></button>
								<button type="button" class="sssj-btn sssj-btn--ghost sssj-btn--sm" data-action="pdf"><?php esc_html_e( 'Quick PDF (browser)', 'shuffles-social-services-jobs' ); ?></button>
							<?php else : ?>
								<button type="button" class="sssj-btn sssj-btn--primary sssj-btn--sm" data-action="pdf"><?php esc_html_e( 'Download PDF', 'shuffles-social-services-jobs' ); ?></button>
							<?php endif; ?>
							<button type="button" class="sssj-btn sssj-btn--secondary sssj-btn--sm" data-action="png"><?php esc_html_e( 'Save image', 'shuffles-social-services-jobs' ); ?></button>
							<button type="button" class="sssj-btn sssj-btn--ghost sssj-btn--sm" data-action="caption"><?php esc_html_e( 'Copy caption', 'shuffles-social-services-jobs' ); ?></button>
						</div>
						<p class="description" data-asset-msg style="margin-top:8px"></p>
					</div>
				</div>
				<div class="sssj-asset-wizard__preview">
					<div class="sssj-asset-print">
						<?php Shuffles_SSJ_Plugin::instance()->shortcodes->load_template( 'assets/job-flyer.php', array( 'data' => $jd ) ); ?>
					</div>
				</div>
			</div>
					<?php
					// Structured data for the themed "Save image" canvas renderer (job flyer).
					$brand  = get_bloginfo( 'name' );
					$jfacts = array();
					if ( ! empty( $jd['pay'] ) ) { $jfacts[] = array( 'label' => __( 'Pay', 'shuffles-social-services-jobs' ), 'value' => (string) $jd['pay'] ); }
					if ( ! empty( $jd['basis'] ) ) { $jfacts[] = array( 'label' => __( 'Engagement', 'shuffles-social-services-jobs' ), 'value' => (string) $jd['basis'] ); }
					$jblob = array(
						'kind'        => 'job',
						'shape'       => 'portrait',
						'name'        => (string) ( isset( $jd['title'] ) ? $jd['title'] : '' ),
						'role'        => (string) ( ! empty( $jd['org'] ) ? $jd['org'] : ( isset( $jd['basis'] ) ? $jd['basis'] : '' ) ),
						'location'    => (string) ( isset( $jd['location'] ) ? $jd['location'] : '' ),
						'facts'       => $jfacts,
						'about'       => '',
						'chips'       => array(),
						'checks'      => array(),
						'cta'         => sprintf( __( 'Apply safely through %s', 'shuffles-social-services-jobs' ), $brand ),
						'photo'       => '',
						'avatarRound' => false,
					);
					echo '<script type="application/json" id="sssj-asset-data">' . wp_json_encode( $jblob, JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>';
					?>
		<?php endif; ?>
	</div>
	<?php
	return;
}

$data = Shuffles_SSJ_Assets::resume_data( $uid );

if ( null === $data ) {
	$profile_url = class_exists( 'Shuffles_SSJ_Shortcodes' ) ? Shuffles_SSJ_Shortcodes::page_link( 'page_post_worker', '[sssj_post_worker]' ) : '';
	echo '<div class="sssj"><div class="sssj-panel"><h2 style="margin-top:0">' . esc_html__( 'Create an asset', 'shuffles-social-services-jobs' ) . '</h2>'
		. '<p>' . esc_html__( 'First create your worker profile. Your résumé, flyer and social post are all built from it automatically, so you never start from a blank page.', 'shuffles-social-services-jobs' ) . '</p>';
	if ( $profile_url ) {
		echo '<a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' . esc_url( $profile_url ) . '">' . esc_html__( 'Create my profile', 'shuffles-social-services-jobs' ) . '</a>';
	}
	echo '</div></div>';
	return;
}

$check       = Shuffles_SSJ_Assets::readability( $data );
$caption     = Shuffles_SSJ_Assets::caption( $data );
$profile_url = class_exists( 'Shuffles_SSJ_Shortcodes' ) ? Shuffles_SSJ_Shortcodes::page_link( 'page_post_worker', '[sssj_post_worker]' ) : '';
$show_blurb  = in_array( $type, array( 'resume', 'flyer' ), true );
?>
<div class="sssj sssj--create-asset" data-sssj-asset-wizard data-asset-type="<?php echo esc_attr( $type ); ?>" data-caption="<?php echo esc_attr( $caption ); ?>">
	<div class="sssj-panel">
		<h2 style="margin-top:0"><?php esc_html_e( 'Create an asset', 'shuffles-social-services-jobs' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Built from your profile in a clean, easy-to-read layout, with your location leading so people see where you work at a glance. Pick what to make, polish the wording, then download or copy a caption.', 'shuffles-social-services-jobs' ); ?></p>
		<div class="sssj-asset-types">
			<?php foreach ( $types as $key => $meta ) : ?>
				<a class="sssj-btn sssj-btn--sm <?php echo $key === $type ? 'sssj-btn--primary' : 'sssj-btn--ghost'; ?>" href="<?php echo esc_url( add_query_arg( 'sssj_asset', $key ) ) . '#dash-create-asset'; ?>"><?php echo esc_html( $meta[1] ); ?></a>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="sssj-asset-wizard">
		<div class="sssj-asset-wizard__controls">
			<div class="sssj-panel">
				<h3 style="margin-top:0"><?php esc_html_e( 'Polish your wording', 'shuffles-social-services-jobs' ); ?></h3>
				<label class="sssj-field">
					<span><?php esc_html_e( 'Headline (your role)', 'shuffles-social-services-jobs' ); ?></span>
					<input type="text" class="sssj-input" data-edit="tagline" maxlength="60" value="<?php echo esc_attr( $data['tagline'] ); ?>" />
				</label>
				<?php if ( $show_blurb ) : ?>
				<label class="sssj-field">
					<span><?php esc_html_e( 'Short introduction', 'shuffles-social-services-jobs' ); ?></span>
					<textarea class="sssj-input" data-edit="blurb" rows="4" maxlength="500"><?php echo esc_textarea( $data['blurb'] ); ?></textarea>
					<span class="description" data-blurb-count></span>
				</label>
				<?php endif; ?>
				<p class="description">
					<?php esc_html_e( 'Your photo, location, services, languages and verified checks come from your profile.', 'shuffles-social-services-jobs' ); ?>
					<?php if ( $profile_url ) : ?><a href="<?php echo esc_url( $profile_url ); ?>"><?php esc_html_e( 'Edit my profile', 'shuffles-social-services-jobs' ); ?></a><?php endif; ?>
				</p>
			</div>

			<?php $theme_picker(); if ( in_array( $type, array( 'resume', 'flyer', 'job' ), true ) ) { $size_picker(); } if ( 'resume' === $type ) { $format_picker(); } ?>

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
					<?php if ( class_exists( 'Shuffles_SSJ_Asset_Renderer' ) && Shuffles_SSJ_Asset_Renderer::enabled() && in_array( $type, array( 'resume', 'flyer' ), true ) ) : ?>
						<button type="button" class="sssj-btn sssj-btn--primary sssj-btn--sm" data-action="server-pdf"><?php esc_html_e( 'Print-quality PDF', 'shuffles-social-services-jobs' ); ?></button>
						<button type="button" class="sssj-btn sssj-btn--ghost sssj-btn--sm" data-action="pdf"><?php esc_html_e( 'Quick PDF (browser)', 'shuffles-social-services-jobs' ); ?></button>
					<?php else : ?>
						<button type="button" class="sssj-btn sssj-btn--primary sssj-btn--sm" data-action="pdf"><?php esc_html_e( 'Download PDF', 'shuffles-social-services-jobs' ); ?></button>
					<?php endif; ?>
					<?php if ( 'resume' === $type && class_exists( 'Shuffles_SSJ_Asset_Renderer' ) && Shuffles_SSJ_Asset_Renderer::enabled() ) : ?>
						<button type="button" class="sssj-btn sssj-btn--secondary sssj-btn--sm" data-action="save-resume">&#128190; <?php esc_html_e( 'Save to My résumés', 'shuffles-social-services-jobs' ); ?></button>
					<?php endif; ?>
						<button type="button" class="sssj-btn sssj-btn--secondary sssj-btn--sm" data-action="png"><?php esc_html_e( 'Save image', 'shuffles-social-services-jobs' ); ?></button>
					<button type="button" class="sssj-btn sssj-btn--ghost sssj-btn--sm" data-action="caption"><?php esc_html_e( 'Copy caption', 'shuffles-social-services-jobs' ); ?></button>
				</div>
				<p class="description" data-asset-msg style="margin-top:8px"></p>
				<p class="description"><?php echo esc_html( ( class_exists( 'Shuffles_SSJ_Asset_Renderer' ) && Shuffles_SSJ_Asset_Renderer::enabled() && in_array( $type, array( 'resume', 'flyer' ), true ) ) ? __( '“Print-quality PDF” builds a crisp, identical PDF on the server. “Quick PDF” uses your browser print dialog. Save image downloads a picture, ideal for the social post.', 'shuffles-social-services-jobs' ) : __( 'PDF uses your browser print dialog (choose “Save as PDF”). Save image downloads a picture, ideal for the social post.', 'shuffles-social-services-jobs' ) ); ?></p>
			</div>
		</div>

		<div class="sssj-asset-wizard__preview">
			<div class="sssj-asset-print">
				<?php Shuffles_SSJ_Plugin::instance()->shortcodes->load_template( $types[ $type ][0], array( 'data' => $data ) ); ?>
			</div>
		</div>
	</div>
	<?php
	// Structured data for the themed "Save image" canvas renderer (sssj-assets.js reads this by id).
	$brand = get_bloginfo( 'name' );
	$facts = array( array( 'label' => __( 'Available', 'shuffles-social-services-jobs' ), 'value' => (string) ( isset( $data['available'] ) ? $data['available'] : '' ) ) );
	if ( ! empty( $data['experience'] ) ) {
		$facts[] = array( 'label' => __( 'Experience', 'shuffles-social-services-jobs' ), 'value' => (string) $data['experience'] );
	}
	if ( ! empty( $data['languages'] ) ) {
		$facts[] = array( 'label' => __( 'Languages', 'shuffles-social-services-jobs' ), 'value' => implode( ', ', (array) $data['languages'] ) );
	}
	$blob = array(
		'kind'        => $type,
		'shape'       => ( 'social' === $type ) ? 'square' : 'portrait',
		'name'        => (string) ( isset( $data['name'] ) ? $data['name'] : '' ),
		'role'        => (string) ( isset( $data['tagline'] ) ? $data['tagline'] : '' ),
		'location'    => (string) ( isset( $data['location'] ) ? $data['location'] : '' ),
		'facts'       => ( 'social' === $type ) ? array() : $facts,
		'about'       => $show_blurb ? (string) ( isset( $data['blurb'] ) ? $data['blurb'] : '' ) : '',
		'aboutLabel'  => __( 'About me', 'shuffles-social-services-jobs' ),
		'chips'       => array_values( (array) ( isset( $data['services'] ) ? $data['services'] : array() ) ),
		'chipsLabel'  => __( 'What I help with', 'shuffles-social-services-jobs' ),
		'checks'      => ( 'social' === $type ) ? array() : array_values( (array) ( isset( $data['checks'] ) ? $data['checks'] : array() ) ),
		'cta'         => sprintf( __( 'Get in touch safely through %s', 'shuffles-social-services-jobs' ), $brand ),
		'photo'       => (string) ( isset( $data['photo'] ) ? $data['photo'] : '' ),
		'avatarRound' => true,
	);
	echo '<script type="application/json" id="sssj-asset-data">' . wp_json_encode( $blob, JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>';
	?>
</div>
