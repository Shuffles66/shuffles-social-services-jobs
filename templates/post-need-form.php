<?php
/**
 * Participant-need posting form (participant or nominee). Var: $settings.
 * Privacy: no name field, a pseudonym is generated server-side, suburb only, submissions are
 * moderated (pending) before they appear. Theme override: themes/<theme>/shuffles-jobs/post-need-form.php
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$can     = is_user_logged_in() && ( current_user_can( 'sssj_post_need' ) || current_user_can( 'manage_options' ) );
$st      = isset( $_GET['sssj_need'] ) ? sanitize_key( wp_unslash( $_GET['sssj_need'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$support = get_terms( array( 'taxonomy' => 'sssjt_support_category', 'hide_empty' => false ) );
$funding = get_terms( array( 'taxonomy' => 'sssjt_funding_source', 'hide_empty' => false ) );
$min_end = current_time( 'Y-m-d' );
$def_end = gmdate( 'Y-m-d', strtotime( $min_end . ' +60 days' ) );
?>
<div class="sssj sssj--post-need">
	<div class="sssj-panel sssj-need-intro">
		<h2><?php esc_html_e( 'Request a worker', 'shuffles-social-services-jobs' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Tell us what support you are looking for. It only takes a couple of minutes. You never share a name or contact details, a private code and suburb are all that show, and every request is checked before it goes live. You can edit, close or reopen your request any time.', 'shuffles-social-services-jobs' ); ?></p>

		<?php if ( 'pending' === $st ) : ?>
			<p class="sssj-badge sssj-badge--verified"><?php esc_html_e( 'Thank you, your request has been submitted and will appear once it is reviewed.', 'shuffles-social-services-jobs' ); ?></p>
		<?php elseif ( 'error' === $st ) : ?>
			<p class="sssj-badge" style="background:#fee2e2;color:#b91c1c"><?php esc_html_e( 'A short description is required.', 'shuffles-social-services-jobs' ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( ! $can ) : ?>
		<div class="sssj-panel">
			<p><?php esc_html_e( 'Log in to post a participant request.', 'shuffles-social-services-jobs' ); ?>
				<a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"><?php esc_html_e( 'Log in', 'shuffles-social-services-jobs' ); ?></a></p>
		</div>
	<?php else : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sssj-stack sssj-need-form" data-sssj-busy="<?php esc_attr_e( 'Saving your request…', 'shuffles-social-services-jobs' ); ?>">
			<input type="hidden" name="action" value="sssj_post_need" />
			<?php wp_nonce_field( 'sssj_post_need', 'sssj_need_nonce' ); ?>

			<!-- 1. What you need -->
			<div class="sssj-panel sssj-need-step">
				<h3 class="sssj-need-step__title"><span class="sssj-need-step__n">1</span> <?php esc_html_e( 'What you need', 'shuffles-social-services-jobs' ); ?></h3>

				<div class="sssj-field">
					<label for="sssj-ndesc"><?php esc_html_e( 'Short description', 'shuffles-social-services-jobs' ); ?> <span class="sssj-req">*</span></label>
					<input class="sssj-input" id="sssj-ndesc" type="text" name="short_description" maxlength="120" placeholder="<?php esc_attr_e( 'e.g. Morning personal care support, Armidale', 'shuffles-social-services-jobs' ); ?>" required />
					<p class="description"><?php esc_html_e( 'One clear line. Please do not include any names.', 'shuffles-social-services-jobs' ); ?></p>
				</div>

				<div class="sssj-field">
					<label for="sssj-seeking"><?php esc_html_e( 'What are you seeking?', 'shuffles-social-services-jobs' ); ?></label>
					<select class="sssj-select" id="sssj-seeking" name="seeking_type">
						<option value="support"><?php esc_html_e( 'Ongoing support from a worker', 'shuffles-social-services-jobs' ); ?></option>
						<option value="task"><?php esc_html_e( 'Help with a one-off task', 'shuffles-social-services-jobs' ); ?></option>
						<option value="provider"><?php esc_html_e( 'An ongoing provider or organisation', 'shuffles-social-services-jobs' ); ?></option>
					</select>
				</div>

				<div class="sssj-field">
					<label for="sssj-ndetails"><?php esc_html_e( 'Details (what help is needed)', 'shuffles-social-services-jobs' ); ?></label>
					<textarea class="sssj-textarea" id="sssj-ndetails" name="details" rows="5" placeholder="<?php esc_attr_e( 'Describe the support in everyday words. What does a good day look like, and what would help most?', 'shuffles-social-services-jobs' ); ?>"></textarea>
				</div>
			</div>

			<!-- 2. Where -->
			<div class="sssj-panel sssj-need-step">
				<h3 class="sssj-need-step__title"><span class="sssj-need-step__n">2</span> <?php esc_html_e( 'Where', 'shuffles-social-services-jobs' ); ?></h3>

				<div class="sssj-field" data-sssj-place-group>
					<label for="sssj-nplace"><?php esc_html_e( 'Suburb', 'shuffles-social-services-jobs' ); ?></label>
					<input class="sssj-input" id="sssj-nplace" type="text" data-sssj-place placeholder="<?php esc_attr_e( 'Start typing a suburb, or fill the fields below', 'shuffles-social-services-jobs' ); ?>" />
					<div class="sssj-row" style="margin-top:8px">
						<div class="sssj-field"><label><?php esc_html_e( 'Suburb', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="location_suburb" data-sssj-suburb /></div>
						<div class="sssj-field"><label><?php esc_html_e( 'State', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="location_state" data-sssj-state /></div>
					</div>
					<input type="hidden" name="location_lat" data-sssj-lat value="" />
					<input type="hidden" name="location_lng" data-sssj-lng value="" />
					<p class="description"><?php esc_html_e( 'Only the suburb is shown publicly, never a street address.', 'shuffles-social-services-jobs' ); ?></p>
				</div>

				<div class="sssj-field">
					<label for="sssj-ntravel"><?php esc_html_e( 'How far can a worker be? (km)', 'shuffles-social-services-jobs' ); ?></label>
					<input class="sssj-input" id="sssj-ntravel" type="number" min="0" max="500" step="5" name="travel_radius_km" />
					<p class="description"><?php esc_html_e( 'Helps match nearby workers. Leave blank for no limit.', 'shuffles-social-services-jobs' ); ?></p>
				</div>
			</div>

			<!-- 3. The kind of support -->
			<div class="sssj-panel sssj-need-step">
				<h3 class="sssj-need-step__title"><span class="sssj-need-step__n">3</span> <?php esc_html_e( 'The kind of support', 'shuffles-social-services-jobs' ); ?></h3>

				<div class="sssj-field">
					<label for="sssj-support"><?php esc_html_e( 'Type of support', 'shuffles-social-services-jobs' ); ?></label>
					<select class="sssj-select" id="sssj-support" name="support_categories[]" multiple data-placeholder="<?php esc_attr_e( 'Search and add support types…', 'shuffles-social-services-jobs' ); ?>">
						<?php
						if ( ! is_wp_error( $support ) ) {
							foreach ( $support as $t ) {
								echo '<option value="' . esc_attr( $t->term_id ) . '">' . esc_html( $t->name ) . '</option>';
							}
						}
						?>
					</select>
				</div>

				<div class="sssj-field">
					<label for="sssj-funding"><?php esc_html_e( 'Funding (choose one, several, or none)', 'shuffles-social-services-jobs' ); ?></label>
					<select class="sssj-select" id="sssj-funding" name="funding_sources[]" multiple data-placeholder="<?php esc_attr_e( 'Search and add funding sources…', 'shuffles-social-services-jobs' ); ?>">
						<?php
						if ( ! is_wp_error( $funding ) ) {
							foreach ( $funding as $t ) {
								echo '<option value="' . esc_attr( $t->term_id ) . '">' . esc_html( $t->name ) . '</option>';
							}
						}
						?>
					</select>
				</div>

				<?php Shuffles_SSJ_Shortcodes::culture_language_fields(); ?>
				<?php if ( class_exists( 'Shuffles_SSJ_Field_Registry' ) ) { Shuffles_SSJ_Field_Registry::render( 'need', 0 ); } ?>
			</div>

			<!-- 4. When and preferences -->
			<div class="sssj-panel sssj-need-step">
				<h3 class="sssj-need-step__title"><span class="sssj-need-step__n">4</span> <?php esc_html_e( 'When and preferences', 'shuffles-social-services-jobs' ); ?></h3>
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
			</div>

			<!-- 5. Close date -->
			<div class="sssj-panel sssj-need-step">
				<h3 class="sssj-need-step__title"><span class="sssj-need-step__n">5</span> <?php esc_html_e( 'Close date', 'shuffles-social-services-jobs' ); ?></h3>
				<div class="sssj-field">
					<label for="sssj-nend"><?php esc_html_e( 'Close this request on', 'shuffles-social-services-jobs' ); ?></label>
					<input class="sssj-input" id="sssj-nend" type="date" name="expires_at" min="<?php echo esc_attr( $min_end ); ?>" value="<?php echo esc_attr( $def_end ); ?>" />
					<p class="description"><?php esc_html_e( 'If your request is not filled by this date it closes automatically, so nobody contacts you about something already sorted. We send a reminder before it closes, and one when it does, and you can reopen it any time with one click. Leave it as is to close in about two months.', 'shuffles-social-services-jobs' ); ?></p>
				</div>
			</div>

			<div class="sssj-need-submit">
				<button class="sssj-btn sssj-btn--primary" type="submit" data-i18n="submit_request"><?php esc_html_e( 'Submit request', 'shuffles-social-services-jobs' ); ?></button>
				<span class="description"><?php esc_html_e( 'You can edit or reopen it later from your dashboard.', 'shuffles-social-services-jobs' ); ?></span>
			</div>
		</form>
	<?php endif; ?>
</div>
