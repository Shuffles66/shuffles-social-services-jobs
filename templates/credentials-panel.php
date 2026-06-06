<?php
/**
 * Worker credential manager: list current credentials + add new ones with secure evidence upload.
 * Theme override: wp-content/themes/<theme>/shuffles-jobs/credentials-panel.php
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	echo '<div class="sssj"><div class="sssj-panel"><p>' . esc_html__( 'Log in to manage your credentials.', 'shuffles-social-services-jobs' )
		. ' <a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Log in', 'shuffles-social-services-jobs' ) . '</a></p></div></div>';
	return;
}

$uid    = get_current_user_id();
$action = esc_url( admin_url( 'admin-post.php' ) );
$rows   = Shuffles_SSJ_Credentials::for_worker( $uid );
$status = isset( $_GET['sssj_cred'] ) ? sanitize_key( wp_unslash( $_GET['sssj_cred'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$has_profile = (bool) Shuffles_SSJ_Credentials::worker_post_id( $uid );
?>
<div class="sssj sssj--credentials">
	<div class="sssj-panel">
		<h2><?php esc_html_e( 'My credentials', 'shuffles-social-services-jobs' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Add your checks and certificates. An administrator reviews each one — only admin-approved credentials earn the ✓ Verified badge on your profile. Your documents are stored privately and are never shown publicly.', 'shuffles-social-services-jobs' ); ?></p>

		<?php if ( '1' === $status ) : ?>
			<p class="sssj-badge sssj-badge--verified"><?php esc_html_e( 'Credential submitted for review. Thank you!', 'shuffles-social-services-jobs' ); ?></p>
		<?php elseif ( 'deleted' === $status ) : ?>
			<p class="sssj-badge"><?php esc_html_e( 'Credential removed.', 'shuffles-social-services-jobs' ); ?></p>
		<?php elseif ( 'error' === $status ) : ?>
			<p class="sssj-badge" style="background:#fee2e2;color:#b91c1c"><?php esc_html_e( 'Sorry — that could not be saved. Check the file is a PDF, JPG or PNG under 8 MB and try again.', 'shuffles-social-services-jobs' ); ?></p>
		<?php endif; ?>

		<?php if ( ! $has_profile ) : ?>
			<p class="sssj-badge" style="background:#fef3c7;color:#92400e"><?php esc_html_e( 'Tip: create your worker profile too, so your verified badge has somewhere to show.', 'shuffles-social-services-jobs' ); ?></p>
		<?php endif; ?>

		<?php if ( empty( $rows ) ) : ?>
			<p class="description"><?php esc_html_e( 'You have not added any credentials yet.', 'shuffles-social-services-jobs' ); ?></p>
		<?php else : ?>
			<div class="sssj-stack" style="margin-top:12px">
				<?php
				foreach ( $rows as $r ) :
					list( $bcls, $blabel ) = Shuffles_SSJ_Credentials::status_badge( $r->status );
					?>
					<div class="sssj-card">
						<div class="sssj-row" style="justify-content:space-between">
							<strong><?php echo esc_html( Shuffles_SSJ_Credentials::kind_label( $r->kind ) ); ?></strong>
							<span class="sssj-badge <?php echo esc_attr( $bcls ); ?>"><?php echo esc_html( $blabel ); ?></span>
						</div>
						<p class="description" style="margin:6px 0">
							<?php if ( $r->number ) : ?><?php echo esc_html__( 'Ref:', 'shuffles-social-services-jobs' ) . ' ' . esc_html( $r->number ); ?> · <?php endif; ?>
							<?php if ( $r->expires_date ) : ?><?php echo esc_html__( 'Expires:', 'shuffles-social-services-jobs' ) . ' ' . esc_html( $r->expires_date ); ?><?php else : ?><?php esc_html_e( 'No expiry', 'shuffles-social-services-jobs' ); ?><?php endif; ?>
						</p>
						<?php if ( 'rejected' === $r->status && $r->note ) : ?>
							<p class="description" style="color:#b91c1c"><?php echo esc_html__( 'Reviewer note:', 'shuffles-social-services-jobs' ) . ' ' . esc_html( $r->note ); ?></p>
						<?php endif; ?>
						<div class="sssj-row">
							<?php if ( ! empty( $r->has_evidence ) ) : ?>
								<a class="sssj-btn sssj-btn--ghost sssj-btn--sm" href="<?php echo esc_url( Shuffles_SSJ_Credentials::file_url( $r->id ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View document', 'shuffles-social-services-jobs' ); ?></a>
							<?php endif; ?>
							<form method="post" action="<?php echo $action; // phpcs:ignore WordPress.Security.EscapeOutput ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Remove this credential?', 'shuffles-social-services-jobs' ) ); ?>');">
								<input type="hidden" name="action" value="sssj_cred_delete" />
								<input type="hidden" name="id" value="<?php echo esc_attr( $r->id ); ?>" />
								<?php wp_nonce_field( 'sssj_cred_delete', 'sssj_cred_nonce' ); ?>
								<button class="sssj-btn sssj-btn--ghost sssj-btn--sm" type="submit"><?php esc_html_e( 'Remove', 'shuffles-social-services-jobs' ); ?></button>
							</form>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="sssj-panel">
		<h3><?php esc_html_e( 'Add a credential', 'shuffles-social-services-jobs' ); ?></h3>
		<form method="post" action="<?php echo $action; // phpcs:ignore WordPress.Security.EscapeOutput ?>" enctype="multipart/form-data" class="sssj-stack">
			<input type="hidden" name="action" value="sssj_cred_add" />
			<?php wp_nonce_field( 'sssj_cred_add', 'sssj_cred_nonce' ); ?>

			<div class="sssj-field">
				<label for="sssj-cred-kind"><?php esc_html_e( 'Credential type', 'shuffles-social-services-jobs' ); ?> *</label>
				<select class="sssj-select" id="sssj-cred-kind" name="kind" required>
					<option value=""><?php esc_html_e( '— Select —', 'shuffles-social-services-jobs' ); ?></option>
					<?php foreach ( Shuffles_SSJ_Credentials::kinds() as $k => $label ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="sssj-field">
				<label for="sssj-cred-number"><?php esc_html_e( 'Reference / card number', 'shuffles-social-services-jobs' ); ?></label>
				<input class="sssj-input" id="sssj-cred-number" type="text" name="number" />
			</div>

			<div class="sssj-row">
				<div class="sssj-field"><label><?php esc_html_e( 'Issued', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="date" name="issued_date" /></div>
				<div class="sssj-field"><label><?php esc_html_e( 'Expires', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="date" name="expires_date" /></div>
			</div>

			<div class="sssj-field">
				<label for="sssj-cred-file"><?php esc_html_e( 'Evidence (PDF, JPG or PNG, max 8 MB)', 'shuffles-social-services-jobs' ); ?></label>
				<input id="sssj-cred-file" type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" />
				<p class="description"><?php esc_html_e( 'Stored privately. Only you and the verifying administrator can open it.', 'shuffles-social-services-jobs' ); ?></p>
			</div>

			<div><button class="sssj-btn sssj-btn--primary" type="submit"><?php esc_html_e( 'Submit for verification', 'shuffles-social-services-jobs' ); ?></button></div>
		</form>
	</div>
</div>
