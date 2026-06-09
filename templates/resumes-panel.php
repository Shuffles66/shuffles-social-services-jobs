<?php
/**
 * "My résumés" panel ([sssj_resumes]), a candidate stores one or more named résumé files
 * (private), sets a default, and removes old ones. Used standalone and in the dashboard.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	echo '<div class="sssj"><div class="sssj-panel"><p>' . esc_html__( 'Please log in to manage your résumés.', 'shuffles-social-services-jobs' )
		. ' <a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' . esc_url( Shuffles_SSJ_Shortcodes::login_url( get_permalink() ) ) . '">' . esc_html__( 'Log in', 'shuffles-social-services-jobs' ) . '</a></p></div></div>';
	return;
}

$uid     = get_current_user_id();
$list    = class_exists( 'Shuffles_SSJ_Resumes' ) ? Shuffles_SSJ_Resumes::for_user( $uid ) : array();
$max     = class_exists( 'Shuffles_SSJ_Resumes' ) ? Shuffles_SSJ_Resumes::max_count() : 5;
$action  = admin_url( 'admin-post.php' );
$notice  = isset( $_GET['sssj_resume'] ) ? sanitize_key( wp_unslash( $_GET['sssj_resume'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$notices = array(
	'added'   => __( 'Résumé uploaded.', 'shuffles-social-services-jobs' ),
	'deleted' => __( 'Résumé removed.', 'shuffles-social-services-jobs' ),
	'default' => __( 'Default résumé updated.', 'shuffles-social-services-jobs' ),
	'error'   => __( 'That résumé could not be saved, check the file type and size.', 'shuffles-social-services-jobs' ),
);
?>
<div class="sssj sssj--resumes">
	<div class="sssj-panel">
		<h3 style="margin-top:0">📄 <?php esc_html_e( 'My résumés', 'shuffles-social-services-jobs' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Store one or more résumés here and pick which to send when you apply for an employee (TFN) job. They are private, shared only with employers you apply to, and our admins.', 'shuffles-social-services-jobs' ); ?></p>

		<?php if ( $notice && isset( $notices[ $notice ] ) ) : ?>
			<div class="sssj-note <?php echo esc_attr( 'error' === $notice ? 'sssj-note--warn' : 'sssj-note--ok' ); ?>"><?php echo esc_html( $notices[ $notice ] ); ?></div>
		<?php endif; ?>

		<?php if ( $list ) : ?>
			<table class="sssj-team-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Résumé', 'shuffles-social-services-jobs' ); ?></th>
						<th><?php esc_html_e( 'File', 'shuffles-social-services-jobs' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'shuffles-social-services-jobs' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $list as $r ) : ?>
						<tr>
							<td>
								<strong><?php echo esc_html( $r->label ); ?></strong>
								<?php if ( (int) $r->is_default ) : ?> <span class="sssj-badge sssj-badge--verified sssj-badge--sm"><?php esc_html_e( 'Default', 'shuffles-social-services-jobs' ); ?></span><?php endif; ?>
							</td>
							<td>
								<a href="<?php echo esc_url( Shuffles_SSJ_Resumes::file_url( $r->id ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $r->original_name ? $r->original_name : __( 'View', 'shuffles-social-services-jobs' ) ); ?></a>
							</td>
							<td style="white-space:nowrap">
								<?php if ( ! (int) $r->is_default ) : ?>
									<form method="post" action="<?php echo esc_url( $action ); ?>" class="sssj-inline-form">
										<?php wp_nonce_field( 'sssj_resume_default', 'sssj_resume_nonce' ); ?>
										<input type="hidden" name="action" value="sssj_resume_default" />
										<input type="hidden" name="id" value="<?php echo esc_attr( $r->id ); ?>" />
										<button type="submit" class="sssj-btn sssj-btn--ghost sssj-btn--sm"><?php esc_html_e( 'Make default', 'shuffles-social-services-jobs' ); ?></button>
									</form>
								<?php endif; ?>
								<form method="post" action="<?php echo esc_url( $action ); ?>" class="sssj-inline-form" onsubmit="return confirm('<?php echo esc_js( __( 'Remove this résumé?', 'shuffles-social-services-jobs' ) ); ?>');">
									<?php wp_nonce_field( 'sssj_resume_delete', 'sssj_resume_nonce' ); ?>
									<input type="hidden" name="action" value="sssj_resume_delete" />
									<input type="hidden" name="id" value="<?php echo esc_attr( $r->id ); ?>" />
									<button type="submit" class="sssj-btn sssj-btn--danger sssj-btn--sm"><?php esc_html_e( 'Remove', 'shuffles-social-services-jobs' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php esc_html_e( 'You haven’t added a résumé yet.', 'shuffles-social-services-jobs' ); ?></p>
		<?php endif; ?>

		<?php if ( count( $list ) < (int) $max ) : ?>
			<h4><?php esc_html_e( 'Add a résumé', 'shuffles-social-services-jobs' ); ?></h4>
			<form method="post" action="<?php echo esc_url( $action ); ?>" enctype="multipart/form-data" class="sssj-stack" data-sssj-busy="<?php esc_attr_e( 'Uploading…', 'shuffles-social-services-jobs' ); ?>">
				<?php wp_nonce_field( 'sssj_resume_add', 'sssj_resume_nonce' ); ?>
				<input type="hidden" name="action" value="sssj_resume_add" />
				<div class="sssj-field">
					<label for="sssj-resume-label"><?php esc_html_e( 'Name this résumé (optional)', 'shuffles-social-services-jobs' ); ?></label>
					<input class="sssj-input" type="text" id="sssj-resume-label" name="label" maxlength="160" placeholder="<?php esc_attr_e( 'e.g. Aged care 2026', 'shuffles-social-services-jobs' ); ?>" />
				</div>
				<div class="sssj-field">
					<label for="sssj-resume-file"><?php esc_html_e( 'Résumé file (PDF, Word, RTF or ODT, max 8 MB)', 'shuffles-social-services-jobs' ); ?></label>
					<input class="sssj-input" type="file" id="sssj-resume-file" name="resume" accept=".pdf,.doc,.docx,.rtf,.odt" required />
				</div>
				<div class="sssj-field">
					<label class="sssj-check"><input type="checkbox" name="make_default" value="1" /> <?php esc_html_e( 'Make this my default résumé', 'shuffles-social-services-jobs' ); ?></label>
				</div>
				<div><button type="submit" class="sssj-btn sssj-btn--primary"><?php esc_html_e( 'Upload résumé', 'shuffles-social-services-jobs' ); ?></button></div>
			</form>
		<?php else : ?>
			<p class="description"><?php echo esc_html( sprintf( __( 'You’ve reached the limit of %d résumés. Remove one to add another.', 'shuffles-social-services-jobs' ), (int) $max ) ); ?></p>
		<?php endif; ?>
	</div>
</div>
