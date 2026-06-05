<?php
/**
 * Participant-need posting form (participant or nominee). Var: $settings.
 * Privacy: no name field — a pseudonym is generated server-side; suburb only; submissions are
 * moderated (pending) before they appear. Theme override: themes/<theme>/shuffles-jobs/post-need-form.php
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$can = is_user_logged_in() && ( current_user_can( 'sssj_post_need' ) || current_user_can( 'manage_options' ) );
$st  = isset( $_GET['sssj_need'] ) ? sanitize_key( wp_unslash( $_GET['sssj_need'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$support = get_terms( array( 'taxonomy' => 'sssjt_support_category', 'hide_empty' => false ) );
$funding = get_terms( array( 'taxonomy' => 'sssjt_funding_source', 'hide_empty' => false ) );
?>
<div class="sssj sssj--post-need">
	<div class="sssj-panel">
		<h2><?php esc_html_e( 'Request a worker', 'shuffles-social-services-jobs' ); ?></h2>
		<p class="description"><?php esc_html_e( 'For a participant, or a nominee posting on their behalf. Do not enter the participant\'s name or contact details — a private code is created automatically and only a suburb is shown. Requests are reviewed before they go live.', 'shuffles-social-services-jobs' ); ?></p>

		<?php if ( 'pending' === $st ) : ?>
			<p class="sssj-badge sssj-badge--verified"><?php esc_html_e( 'Thank you — your request has been submitted and will appear once it is reviewed.', 'shuffles-social-services-jobs' ); ?></p>
		<?php elseif ( 'error' === $st ) : ?>
			<p class="sssj-badge" style="background:#fee2e2;color:#b91c1c"><?php esc_html_e( 'A short description is required.', 'shuffles-social-services-jobs' ); ?></p>
		<?php endif; ?>

		<?php if ( ! $can ) : ?>
			<p><?php esc_html_e( 'Log in to post a participant request.', 'shuffles-social-services-jobs' ); ?>
				<a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"><?php esc_html_e( 'Log in', 'shuffles-social-services-jobs' ); ?></a></p>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sssj-stack">
				<input type="hidden" name="action" value="sssj_post_need" />
				<?php wp_nonce_field( 'sssj_post_need', 'sssj_need_nonce' ); ?>

				<div class="sssj-field">
					<label for="sssj-ndesc"><?php esc_html_e( 'Short description (no names)', 'shuffles-social-services-jobs' ); ?> *</label>
					<input class="sssj-input" id="sssj-ndesc" type="text" name="short_description" placeholder="<?php esc_attr_e( 'e.g. Morning personal care support, Armidale', 'shuffles-social-services-jobs' ); ?>" required />
				</div>

				<div class="sssj-field">
					<label for="sssj-ndetails"><?php esc_html_e( 'Details (what help is needed)', 'shuffles-social-services-jobs' ); ?></label>
					<textarea class="sssj-textarea" id="sssj-ndetails" name="details" rows="5"></textarea>
				</div>

				<div class="sssj-field" data-sssj-place-group>
					<label for="sssj-nplace"><?php esc_html_e( 'Suburb (location)', 'shuffles-social-services-jobs' ); ?></label>
					<input class="sssj-input" id="sssj-nplace" type="text" data-sssj-place placeholder="<?php esc_attr_e( 'Start typing a suburb… (or fill the fields below)', 'shuffles-social-services-jobs' ); ?>" />
					<div class="sssj-row" style="margin-top:8px">
						<div class="sssj-field"><label><?php esc_html_e( 'Suburb', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="location_suburb" data-sssj-suburb /></div>
						<div class="sssj-field"><label><?php esc_html_e( 'State', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="location_state" data-sssj-state /></div>
					</div>
					<input type="hidden" name="location_lat" data-sssj-lat value="" />
					<input type="hidden" name="location_lng" data-sssj-lng value="" />
					<p class="description"><?php esc_html_e( 'Only the suburb is shown publicly — never a street address.', 'shuffles-social-services-jobs' ); ?></p>
				</div>

				<div class="sssj-field">
					<label><?php esc_html_e( 'Type of support', 'shuffles-social-services-jobs' ); ?></label>
					<div class="sssj-row" style="flex-wrap:wrap">
						<?php
						if ( ! is_wp_error( $support ) ) {
							foreach ( $support as $t ) {
								echo '<label class="sssj-chip"><input type="checkbox" name="support_categories[]" value="' . esc_attr( $t->term_id ) . '" /> ' . esc_html( $t->name ) . '</label>';
							}
						}
						?>
					</div>
				</div>

				<div class="sssj-field">
					<label><?php esc_html_e( 'Funding (choose one, several, or none)', 'shuffles-social-services-jobs' ); ?></label>
					<div class="sssj-row" style="flex-wrap:wrap">
						<?php
						if ( ! is_wp_error( $funding ) ) {
							foreach ( $funding as $t ) {
								echo '<label class="sssj-chip"><input type="checkbox" name="funding_sources[]" value="' . esc_attr( $t->term_id ) . '" /> ' . esc_html( $t->name ) . '</label>';
							}
						}
						?>
					</div>
				</div>

				<div class="sssj-row">
					<div class="sssj-field">
						<label><?php esc_html_e( 'When', 'shuffles-social-services-jobs' ); ?></label>
						<select class="sssj-select" name="schedule_pattern">
							<?php
							foreach ( array(
								'flexible'  => __( 'Flexible', 'shuffles-social-services-jobs' ),
								'morning'   => __( 'Mornings', 'shuffles-social-services-jobs' ),
								'evening'   => __( 'Evenings', 'shuffles-social-services-jobs' ),
								'weekends'  => __( 'Weekends', 'shuffles-social-services-jobs' ),
								'overnight' => __( 'Overnight', 'shuffles-social-services-jobs' ),
							) as $v => $l ) {
								echo '<option value="' . esc_attr( $v ) . '">' . esc_html( $l ) . '</option>';
							}
							?>
						</select>
					</div>
					<div class="sssj-field">
						<label><?php esc_html_e( 'Duration', 'shuffles-social-services-jobs' ); ?></label>
						<select class="sssj-select" name="ongoing_or_temp">
							<option value="ongoing"><?php esc_html_e( 'Ongoing', 'shuffles-social-services-jobs' ); ?></option>
							<option value="temporary"><?php esc_html_e( 'Temporary', 'shuffles-social-services-jobs' ); ?></option>
						</select>
					</div>
					<div class="sssj-field">
						<label><?php esc_html_e( 'Worker gender preference', 'shuffles-social-services-jobs' ); ?></label>
						<select class="sssj-select" name="gender_preference">
							<option value="any"><?php esc_html_e( 'Any', 'shuffles-social-services-jobs' ); ?></option>
							<option value="female"><?php esc_html_e( 'Female', 'shuffles-social-services-jobs' ); ?></option>
							<option value="male"><?php esc_html_e( 'Male', 'shuffles-social-services-jobs' ); ?></option>
							<option value="non-binary"><?php esc_html_e( 'Non-binary', 'shuffles-social-services-jobs' ); ?></option>
						</select>
					</div>
					<div class="sssj-field">
						<label><?php esc_html_e( 'Funding managed by', 'shuffles-social-services-jobs' ); ?></label>
						<select class="sssj-select" name="funding_management">
							<option value="self"><?php esc_html_e( 'Self-managed', 'shuffles-social-services-jobs' ); ?></option>
							<option value="plan"><?php esc_html_e( 'Plan-managed', 'shuffles-social-services-jobs' ); ?></option>
							<option value="agency"><?php esc_html_e( 'Agency-managed', 'shuffles-social-services-jobs' ); ?></option>
							<option value="private"><?php esc_html_e( 'Privately', 'shuffles-social-services-jobs' ); ?></option>
						</select>
					</div>
				</div>

				<div><button class="sssj-btn sssj-btn--primary" type="submit"><?php esc_html_e( 'Submit request', 'shuffles-social-services-jobs' ); ?></button></div>
			</form>
		<?php endif; ?>
	</div>
</div>
