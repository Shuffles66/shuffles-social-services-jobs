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
		<?php elseif ( 'error' === $st ) : ?>
			<p class="sssj-badge" style="background:#fee2e2;color:#b91c1c"><?php esc_html_e( 'Sorry, that didn\'t work. Please try again.', 'shuffles-social-services-jobs' ); ?></p>
		<?php endif; ?>

		<?php
		if ( ! is_user_logged_in() ) {
			echo '<p>' . esc_html__( 'Log in to apply.', 'shuffles-social-services-jobs' ) . ' <a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' . esc_url( wp_login_url( get_permalink( $job_id ) ) ) . '">' . esc_html__( 'Log in', 'shuffles-social-services-jobs' ) . '</a></p>';
		} elseif ( Shuffles_SSJ_Applications::already_applied( $job_id, 0, $uid ) ) {
			echo '<p>' . esc_html__( 'You have applied for this job.', 'shuffles-social-services-jobs' ) . '</p>';
		} elseif ( ! Shuffles_SSJ_Applications::can_respond( $basis ) ) {
			echo '<p>' . esc_html__( 'This is contractor (ABN) work. Add a valid ABN to your worker profile to apply.', 'shuffles-social-services-jobs' ) . '</p>';
		} else {
			?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sssj-stack">
				<input type="hidden" name="action" value="sssj_apply" />
				<input type="hidden" name="job_id" value="<?php echo esc_attr( $job_id ); ?>" />
				<?php wp_nonce_field( 'sssj_apply', 'sssj_apply_nonce' ); ?>
				<div class="sssj-field">
					<label for="sssj-cover"><?php esc_html_e( 'Message to the advertiser (optional)', 'shuffles-social-services-jobs' ); ?></label>
					<textarea class="sssj-textarea" id="sssj-cover" name="cover_message" rows="4"></textarea>
				</div>
				<div><button class="sssj-btn sssj-btn--primary" type="submit"><?php esc_html_e( 'Apply now', 'shuffles-social-services-jobs' ); ?></button></div>
			</form>
			<?php
		}
		?>
	</div>
</div>
