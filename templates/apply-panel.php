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
			echo '<p class="sssj-badge sssj-badge--verified">' . esc_html__( 'You have applied for this job.', 'shuffles-social-services-jobs' ) . '</p>';
			$my_app = null;
			foreach ( Shuffles_SSJ_Applications::for_applicant( $uid ) as $sssj_a ) {
				if ( (int) $sssj_a->job_id === $job_id ) { $my_app = $sssj_a; break; }
			}
			if ( $my_app ) {
				$applied = ! empty( $my_app->created_at ) ? mysql2date( get_option( 'date_format' ), $my_app->created_at ) : '';
				echo '<ul class="ul-disc" style="margin:8px 0 0 18px">';
				echo '<li>' . esc_html( sprintf( __( 'Status: %s', 'shuffles-social-services-jobs' ), Shuffles_SSJ_Applications::status_label( (string) $my_app->status ) ) ) . '</li>';
				if ( '' !== $applied ) {
					echo '<li>' . esc_html( sprintf( __( 'Applied %s', 'shuffles-social-services-jobs' ), $applied ) ) . '</li>';
				}
				echo '</ul>';
				$dash = Shuffles_SSJ_Shortcodes::page_link( 'page_dashboard', '[sssj_dashboard]' );
				echo '<div class="sssj-row" style="gap:8px;flex-wrap:wrap;margin-top:10px">';
				if ( $dash ) {
					echo '<a class="sssj-btn sssj-btn--ghost sssj-btn--sm" href="' . esc_url( $dash ) . '#dash-applications">' . esc_html__( 'View in My applications', 'shuffles-social-services-jobs' ) . '</a>';
				}
				if ( ! in_array( (string) $my_app->status, array( 'withdrawn', 'hired', 'declined', 'rejected' ), true ) ) {
					echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(\'' . esc_js( __( 'Withdraw this application?', 'shuffles-social-services-jobs' ) ) . '\');" style="margin:0">';
					echo '<input type="hidden" name="action" value="sssj_app_withdraw" />';
					echo '<input type="hidden" name="app_id" value="' . esc_attr( $my_app->id ) . '" />';
					echo '<input type="hidden" name="sssj_withdraw_nonce" value="' . esc_attr( wp_create_nonce( 'sssj_app_withdraw' ) ) . '" />';
					echo '<button type="submit" class="sssj-btn sssj-btn--ghost sssj-btn--sm">' . esc_html__( 'Withdraw application', 'shuffles-social-services-jobs' ) . '</button>';
					echo '</form>';
				}
				echo '</div>';
				echo '<div class="sssj-flowwrap" style="margin-top:12px"><h4 style="margin:0 0 6px">' . esc_html__( 'Where your application is up to', 'shuffles-social-services-jobs' ) . '</h4>' . Shuffles_SSJ_Shortcodes::application_workflow( $basis, (string) $my_app->status ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
				if ( in_array( (string) $my_app->status, array( 'declined', 'rejected' ), true ) ) {
					echo Shuffles_SSJ_Shortcodes::application_setback_note( $basis ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
			}
		} elseif ( ! Shuffles_SSJ_Applications::can_respond( $basis ) ) {
			$profile_url = Shuffles_SSJ_Shortcodes::page_link( 'page_post_worker', '[sssj_post_worker]' );
			echo '<p>' . esc_html__( 'This is contractor (ABN) work. Add a valid ABN to your worker profile to apply.', 'shuffles-social-services-jobs' ) . '</p>';
			if ( $profile_url ) {
				echo '<p><a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' . esc_url( $profile_url ) . '">' . esc_html__( 'Edit my profile', 'shuffles-social-services-jobs' ) . '</a></p>';
			}
		} else {
			?>
			<details class="sssj-flow-details" style="margin-bottom:14px"><summary><?php echo esc_html( 'abn' === $basis ? __( 'How the review works for this contractor (ABN) job', 'shuffles-social-services-jobs' ) : __( 'How the review works for this employee (TFN) job', 'shuffles-social-services-jobs' ) ); ?></summary><?php echo Shuffles_SSJ_Shortcodes::application_workflow( $basis ); // phpcs:ignore WordPress.Security.EscapeOutput ?></details>
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
