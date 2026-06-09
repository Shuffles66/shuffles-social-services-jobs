<?php
/**
 * Apply panel appended to a single job page. Var: $job_id.
 * Theme override: themes/<theme>/shuffles-jobs/apply-panel.php
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$job_id = isset( $job_id ) ? (int) $job_id : 0;
if ( ! $job_id ) {
	return;
}
$basis = ( 'abn' === (string) get_post_meta( $job_id, 'engagement_basis', true ) ) ? 'abn' : 'tfn';
$st    = isset( $_GET['sssj_applied'] ) ? sanitize_key( wp_unslash( $_GET['sssj_applied'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$uid   = get_current_user_id();
?>
<div class="sssj sssj--apply">
	<div class="sssj-panel">
		<h3 style="margin-top:0"><?php esc_html_e( 'Apply for this job', 'shuffles-social-services-jobs' ); ?></h3>

		<?php if ( '1' === $st ) : ?>
			<p class="sssj-badge sssj-badge--verified"><?php esc_html_e( 'Application sent. Good luck!', 'shuffles-social-services-jobs' ); ?></p>
		<?php elseif ( 'dup' === $st ) : ?>
			<p class="sssj-badge"><?php esc_html_e( 'You have already applied for this job.', 'shuffles-social-services-jobs' ); ?></p>
		<?php elseif ( 'denied' === $st ) : ?>
			<p class="sssj-badge" style="background:#fee2e2;color:#b91c1c"><?php esc_html_e( 'A recorded ABN is required to apply for contractor work.', 'shuffles-social-services-jobs' ); ?></p>
		<?php elseif ( 'rtw' === $st ) : ?>
			<p class="sssj-badge" style="background:#fee2e2;color:#b91c1c"><?php esc_html_e( 'Please confirm your right to work in Australia to apply for an employee position.', 'shuffles-social-services-jobs' ); ?></p>
		<?php elseif ( 'error' === $st ) : ?>
			<p class="sssj-badge" style="background:#fee2e2;color:#b91c1c"><?php esc_html_e( 'Sorry, that didn\'t work. Please try again.', 'shuffles-social-services-jobs' ); ?></p>
		<?php endif; ?>

		<?php
		if ( ! is_user_logged_in() ) {
			echo '<p>' . esc_html__( 'Log in to apply.', 'shuffles-social-services-jobs' ) . ' <a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' . esc_url( Shuffles_SSJ_Shortcodes::login_url( get_permalink( $job_id ) ) ) . '">' . esc_html__( 'Log in', 'shuffles-social-services-jobs' ) . '</a></p>';
		} elseif ( Shuffles_SSJ_Applications::already_applied( $job_id, 0, $uid ) ) {
			echo '<p>' . esc_html__( 'You have applied for this job.', 'shuffles-social-services-jobs' ) . '</p>';
		} elseif ( ! Shuffles_SSJ_Applications::can_respond( $basis ) ) {
			$profile_url = Shuffles_SSJ_Shortcodes::page_link( 'page_post_worker', '[sssj_post_worker]' );
			echo '<p>' . esc_html__( 'This is contractor (ABN) work. Add a valid ABN to your worker profile to apply.', 'shuffles-social-services-jobs' ) . '</p>';
			if ( $profile_url ) {
				echo '<p><a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' . esc_url( $profile_url ) . '">' . esc_html__( 'Edit my profile', 'shuffles-social-services-jobs' ) . '</a></p>';
			}
		} else {
			?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sssj-stack">
				<input type="hidden" name="action" value="sssj_apply" />
				<input type="hidden" name="job_id" value="<?php echo esc_attr( $job_id ); ?>" />
				<?php wp_nonce_field( 'sssj_apply', 'sssj_apply_nonce' ); ?>

				<?php if ( 'tfn' === $basis ) : ?>
					<?php
					$resumes  = class_exists( 'Shuffles_SSJ_Resumes' ) ? Shuffles_SSJ_Resumes::for_user( $uid ) : array();
					$def_res  = class_exists( 'Shuffles_SSJ_Resumes' ) ? Shuffles_SSJ_Resumes::default_id( $uid ) : 0;
					$res_page = Shuffles_SSJ_Shortcodes::page_link( 'page_dashboard', '[sssj_dashboard]' );
					?>
					<div class="sssj-field">
						<label for="sssj-resume"><?php esc_html_e( 'Résumé', 'shuffles-social-services-jobs' ); ?></label>
						<?php if ( $resumes ) : ?>
							<select class="sssj-select" id="sssj-resume" name="resume_id">
								<?php foreach ( $resumes as $r ) : ?>
									<option value="<?php echo esc_attr( $r->id ); ?>" <?php selected( (int) $r->id, (int) $def_res ); ?>><?php echo esc_html( $r->label ); ?></option>
								<?php endforeach; ?>
							</select>
						<?php else : ?>
							<p class="description"><?php esc_html_e( 'You have no résumé saved yet, you can still apply, but adding one helps.', 'shuffles-social-services-jobs' ); ?>
							<?php if ( $res_page ) : ?> <a href="<?php echo esc_url( $res_page ); ?>"><?php esc_html_e( 'Add a résumé', 'shuffles-social-services-jobs' ); ?></a><?php endif; ?></p>
						<?php endif; ?>
					</div>
					<div class="sssj-row">
						<div class="sssj-field"><label for="sssj-avail"><?php esc_html_e( 'Your availability', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" id="sssj-avail" name="availability" placeholder="<?php esc_attr_e( 'e.g. weekdays + Sat mornings', 'shuffles-social-services-jobs' ); ?>" /></div>
						<div class="sssj-field"><label for="sssj-start"><?php esc_html_e( 'Earliest start date', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="date" id="sssj-start" name="start_date" /></div>
					</div>
					<?php
					$questions = (array) get_post_meta( $job_id, 'screening_questions', true );
					if ( $questions ) :
						?>
						<fieldset class="sssj-fieldset">
							<legend><?php esc_html_e( 'A few questions from the employer', 'shuffles-social-services-jobs' ); ?></legend>
							<?php foreach ( $questions as $i => $q ) : ?>
								<div class="sssj-field">
									<label for="sssj-scr-<?php echo (int) $i; ?>"><?php echo esc_html( $q ); ?></label>
									<input class="sssj-input" type="text" id="sssj-scr-<?php echo (int) $i; ?>" name="screening[<?php echo (int) $i; ?>]" />
								</div>
							<?php endforeach; ?>
						</fieldset>
					<?php endif; ?>
					<div class="sssj-field">
						<label class="sssj-check"><input type="checkbox" name="right_to_work" value="1" required /> <?php esc_html_e( 'I confirm I have the right to work in Australia', 'shuffles-social-services-jobs' ); ?></label>
					</div>
				<?php endif; ?>

				<div class="sssj-field">
					<label for="sssj-cover"><?php esc_html_e( 'Message to the advertiser (optional)', 'shuffles-social-services-jobs' ); ?></label>
					<textarea class="sssj-textarea" id="sssj-cover" name="cover_message" rows="4"></textarea>
				</div>
				<div><button class="sssj-btn sssj-btn--primary" type="submit" data-i18n="apply"><?php esc_html_e( 'Apply now', 'shuffles-social-services-jobs' ); ?></button></div>
			</form>
			<?php
		}
		?>
	</div>
</div>
