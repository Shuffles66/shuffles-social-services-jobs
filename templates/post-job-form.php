<?php
/**
 * Advertiser job-posting form. Var: $settings.
 * Theme override: wp-content/themes/<theme>/shuffles-jobs/post-job-form.php
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$can_post = is_user_logged_in() && ( current_user_can( 'sssj_post_job' ) || current_user_can( 'manage_options' ) );
$status   = isset( $_GET['sssj_posted'] ) ? sanitize_key( wp_unslash( $_GET['sssj_posted'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$can_quota = ! is_user_logged_in() || Shuffles_SSJ_Monetisation::can_post_job( get_current_user_id() );
$cats     = get_terms( array( 'taxonomy' => 'sssjt_category', 'hide_empty' => false ) );
$etypes   = get_terms( array( 'taxonomy' => 'sssjt_employment_type', 'hide_empty' => false ) );
?>
<div class="sssj sssj--post-job">
	<div class="sssj-panel">
		<h2><?php esc_html_e( 'Post a job', 'shuffles-social-services-jobs' ); ?></h2>

		<?php if ( '1' === $status ) : ?>
			<p class="sssj-badge sssj-badge--verified"><?php esc_html_e( 'Your job has been posted. Thank you!', 'shuffles-social-services-jobs' ); ?></p>
		<?php elseif ( 'abn' === $status ) : ?>
			<p class="sssj-badge" style="background:#fee2e2;color:#b91c1c"><?php esc_html_e( 'That ABN failed validation. Please check the 11-digit ABN and try again.', 'shuffles-social-services-jobs' ); ?></p>
		<?php elseif ( 'error' === $status ) : ?>
			<p class="sssj-badge" style="background:#fee2e2;color:#b91c1c"><?php esc_html_e( 'Something was missing — a title and engagement type are required.', 'shuffles-social-services-jobs' ); ?></p>
		<?php elseif ( 'limit' === $status ) : ?>
			<p class="sssj-badge" style="background:#fef3c7;color:#92400e"><?php echo esc_html( Shuffles_SSJ_Monetisation::post_job_block_reason() ); ?></p>
		<?php endif; ?>

		<?php if ( ! $can_post ) : ?>
			<p>
				<?php esc_html_e( 'You need an advertiser account to post a job.', 'shuffles-social-services-jobs' ); ?>
				<a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"><?php esc_html_e( 'Log in', 'shuffles-social-services-jobs' ); ?></a>
			</p>
		<?php elseif ( ! $can_quota ) : ?>
			<p class="sssj-badge" style="background:#fef3c7;color:#92400e"><?php echo esc_html( Shuffles_SSJ_Monetisation::post_job_block_reason() ); ?></p>
			<p class="description"><?php esc_html_e( 'Upgrade your advertiser subscription to post more jobs.', 'shuffles-social-services-jobs' ); ?></p>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sssj-stack">
				<input type="hidden" name="action" value="sssj_post_job" />
				<?php wp_nonce_field( 'sssj_post_job', 'sssj_job_nonce' ); ?>

				<div class="sssj-field">
					<label for="sssj-title"><?php esc_html_e( 'Job title', 'shuffles-social-services-jobs' ); ?> *</label>
					<input class="sssj-input" id="sssj-title" type="text" name="title" required />
				</div>

				<div class="sssj-field">
					<label for="sssj-desc"><?php esc_html_e( 'Description', 'shuffles-social-services-jobs' ); ?></label>
					<textarea class="sssj-textarea" id="sssj-desc" name="description" rows="6"></textarea>
				</div>

				<div class="sssj-field">
					<label><?php esc_html_e( 'Engagement basis', 'shuffles-social-services-jobs' ); ?> *</label>
					<label><input type="radio" name="engagement_basis" value="tfn" checked /> <?php esc_html_e( 'TFN — employee position (no ABN)', 'shuffles-social-services-jobs' ); ?></label>
					<label><input type="radio" name="engagement_basis" value="abn" /> <?php esc_html_e( 'ABN — contractor / sole-trader (ABN required)', 'shuffles-social-services-jobs' ); ?></label>
				</div>

				<div class="sssj-field">
					<label for="sssj-abn"><?php esc_html_e( 'Advertiser ABN (required for ABN engagements)', 'shuffles-social-services-jobs' ); ?></label>
					<input class="sssj-input" id="sssj-abn" type="text" name="advertiser_abn" inputmode="numeric" placeholder="11 digits" />
				</div>

				<div class="sssj-field">
					<label><?php esc_html_e( 'Engagement type', 'shuffles-social-services-jobs' ); ?></label>
					<select class="sssj-select" name="engagement_type">
						<option value="ongoing"><?php esc_html_e( 'Ongoing', 'shuffles-social-services-jobs' ); ?></option>
						<option value="one-off"><?php esc_html_e( 'One-off / single task', 'shuffles-social-services-jobs' ); ?></option>
					</select>
				</div>

				<?php
				$my_orgs = get_posts( array( 'post_type' => 'sssj_org', 'post_status' => 'any', 'author' => get_current_user_id(), 'posts_per_page' => 20 ) );
				if ( ! empty( $my_orgs ) ) :
					?>
					<div class="sssj-field">
						<label for="sssj-job-org"><?php esc_html_e( 'Organisation profile', 'shuffles-social-services-jobs' ); ?></label>
						<select class="sssj-select" id="sssj-job-org" name="organisation_id">
							<option value="0"><?php esc_html_e( '— None —', 'shuffles-social-services-jobs' ); ?></option>
							<?php foreach ( $my_orgs as $o ) { echo '<option value="' . esc_attr( $o->ID ) . '">' . esc_html( get_the_title( $o ) ) . '</option>'; } ?>
						</select>
						<p class="description"><?php esc_html_e( 'Attach this job to your organisation profile so it appears on your company page.', 'shuffles-social-services-jobs' ); ?></p>
					</div>
				<?php endif; ?>

				<div class="sssj-field">
					<label for="sssj-cat"><?php esc_html_e( 'Category', 'shuffles-social-services-jobs' ); ?></label>
					<select class="sssj-select" id="sssj-cat" name="category">
						<option value=""><?php esc_html_e( '— Select —', 'shuffles-social-services-jobs' ); ?></option>
						<?php
						if ( ! is_wp_error( $cats ) ) {
							foreach ( $cats as $t ) {
								echo '<option value="' . esc_attr( $t->term_id ) . '">' . esc_html( $t->name ) . '</option>';
							}
						}
						?>
					</select>
				</div>

				<div class="sssj-field">
					<label for="sssj-emp"><?php esc_html_e( 'Employment type', 'shuffles-social-services-jobs' ); ?></label>
					<select class="sssj-select" id="sssj-emp" name="employment_type">
						<option value=""><?php esc_html_e( '— Select —', 'shuffles-social-services-jobs' ); ?></option>
						<?php
						if ( ! is_wp_error( $etypes ) ) {
							foreach ( $etypes as $t ) {
								echo '<option value="' . esc_attr( $t->term_id ) . '">' . esc_html( $t->name ) . '</option>';
							}
						}
						?>
					</select>
				</div>

				<div class="sssj-field" data-sssj-place-group>
					<label for="sssj-place"><?php esc_html_e( 'Location', 'shuffles-social-services-jobs' ); ?></label>
					<input class="sssj-input" id="sssj-place" type="text" data-sssj-place placeholder="<?php esc_attr_e( 'Start typing a suburb… (or fill the fields below)', 'shuffles-social-services-jobs' ); ?>" />
					<div class="sssj-row" style="margin-top:8px">
						<div class="sssj-field"><label><?php esc_html_e( 'Suburb', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="location_suburb" data-sssj-suburb /></div>
						<div class="sssj-field"><label><?php esc_html_e( 'State', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="location_state" data-sssj-state /></div>
						<div class="sssj-field"><label><?php esc_html_e( 'Postcode', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="location_postcode" data-sssj-postcode /></div>
					</div>
					<input type="hidden" name="location_lat" data-sssj-lat value="" />
					<input type="hidden" name="location_lng" data-sssj-lng value="" />
				</div>

				<div class="sssj-row">
					<div class="sssj-field"><label><?php esc_html_e( 'Rate min', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="number" min="0" step="0.01" name="rate_min" /></div>
					<div class="sssj-field"><label><?php esc_html_e( 'Rate max', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="number" min="0" step="0.01" name="rate_max" /></div>
					<div class="sssj-field"><label><?php esc_html_e( 'Per', 'shuffles-social-services-jobs' ); ?></label>
						<select class="sssj-select" name="rate_unit">
							<option value="hour"><?php esc_html_e( 'Hour', 'shuffles-social-services-jobs' ); ?></option>
							<option value="day"><?php esc_html_e( 'Day', 'shuffles-social-services-jobs' ); ?></option>
							<option value="annum"><?php esc_html_e( 'Annum', 'shuffles-social-services-jobs' ); ?></option>
						</select>
					</div>
				</div>

				<div class="sssj-field">
					<label for="sssj-exp"><?php esc_html_e( 'Closes on', 'shuffles-social-services-jobs' ); ?></label>
					<input class="sssj-input" id="sssj-exp" type="date" name="expires_at" />
				</div>

				<div><button class="sssj-btn sssj-btn--primary" type="submit" data-i18n="post_job"><?php esc_html_e( 'Post job', 'shuffles-social-services-jobs' ); ?></button></div>
			</form>
		<?php endif; ?>
	</div>
</div>
