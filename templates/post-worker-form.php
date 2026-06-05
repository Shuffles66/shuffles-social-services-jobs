<?php
/**
 * Worker profile form (create/update the logged-in user's own profile). Var: $settings.
 * Theme override: wp-content/themes/<theme>/shuffles-jobs/post-worker-form.php
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$can = is_user_logged_in() && ( current_user_can( 'sssj_post_worker' ) || current_user_can( 'manage_options' ) );
$st  = isset( $_GET['sssj_worker'] ) ? sanitize_key( wp_unslash( $_GET['sssj_worker'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$cats = get_terms( array( 'taxonomy' => 'sssjt_category', 'hide_empty' => false ) );

// Pre-fill from the user's existing profile, if any.
$existing = null;
$ex_services = array();
if ( is_user_logged_in() ) {
	$found = get_posts(
		array(
			'post_type'      => 'sssj_worker',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'meta_key'       => 'worker_user_id',
			'meta_value'     => get_current_user_id(),
		)
	);
	if ( ! empty( $found ) ) {
		$existing    = $found[0];
		$ex_services = wp_get_post_terms( $existing->ID, 'sssjt_category', array( 'fields' => 'ids' ) );
	}
}
$gm = function ( $key, $default = '' ) use ( $existing ) {
	return $existing ? get_post_meta( $existing->ID, $key, true ) : $default;
};
$ex_name = $existing ? $existing->post_title : '';
$ex_bio  = $existing ? $existing->post_content : '';
$ex_vis  = $existing ? (string) get_post_meta( $existing->ID, 'visibility', true ) : 'logged_in';
?>
<div class="sssj sssj--post-worker">
	<div class="sssj-panel">
		<h2><?php echo $existing ? esc_html__( 'Edit your worker profile', 'shuffles-social-services-jobs' ) : esc_html__( 'Create your worker profile', 'shuffles-social-services-jobs' ); ?></h2>

		<?php if ( '1' === $st ) : ?>
			<p class="sssj-badge sssj-badge--verified"><?php esc_html_e( 'Profile saved.', 'shuffles-social-services-jobs' ); ?></p>
		<?php elseif ( 'abn' === $st ) : ?>
			<p class="sssj-badge" style="background:#fee2e2;color:#b91c1c"><?php esc_html_e( 'That ABN failed validation. Leave it blank or enter a valid 11-digit ABN.', 'shuffles-social-services-jobs' ); ?></p>
		<?php elseif ( 'error' === $st ) : ?>
			<p class="sssj-badge" style="background:#fee2e2;color:#b91c1c"><?php esc_html_e( 'A display name is required.', 'shuffles-social-services-jobs' ); ?></p>
		<?php endif; ?>

		<?php if ( ! $can ) : ?>
			<p><?php esc_html_e( 'Log in to create your worker profile.', 'shuffles-social-services-jobs' ); ?>
				<a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"><?php esc_html_e( 'Log in', 'shuffles-social-services-jobs' ); ?></a></p>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sssj-stack">
				<input type="hidden" name="action" value="sssj_post_worker" />
				<?php wp_nonce_field( 'sssj_post_worker', 'sssj_worker_nonce' ); ?>

				<div class="sssj-field">
					<label for="sssj-wname"><?php esc_html_e( 'Display name', 'shuffles-social-services-jobs' ); ?> *</label>
					<input class="sssj-input" id="sssj-wname" type="text" name="display_name" value="<?php echo esc_attr( $ex_name ); ?>" required />
				</div>

				<div class="sssj-field">
					<label for="sssj-wbio"><?php esc_html_e( 'About you', 'shuffles-social-services-jobs' ); ?></label>
					<textarea class="sssj-textarea" id="sssj-wbio" name="bio" rows="5"><?php echo esc_textarea( $ex_bio ); ?></textarea>
				</div>

				<div class="sssj-field">
					<label><input type="checkbox" name="is_available" value="1" <?php checked( '1', (string) $gm( 'is_available', '1' ) ); ?> /> <?php esc_html_e( 'I am available for work now', 'shuffles-social-services-jobs' ); ?></label>
				</div>

				<div class="sssj-field">
					<label for="sssj-wstatus"><?php esc_html_e( 'Status', 'shuffles-social-services-jobs' ); ?></label>
					<select class="sssj-select" id="sssj-wstatus" name="employment_status">
						<?php
						$cur_status = (string) $gm( 'employment_status', 'seeking' );
						$opts       = array(
							'seeking'                => __( 'Seeking work', 'shuffles-social-services-jobs' ),
							'employed-open-to-more'  => __( 'Employed — open to more', 'shuffles-social-services-jobs' ),
							'not-looking'            => __( 'Not currently looking', 'shuffles-social-services-jobs' ),
						);
						foreach ( $opts as $v => $l ) {
							echo '<option value="' . esc_attr( $v ) . '" ' . selected( $cur_status, $v, false ) . '>' . esc_html( $l ) . '</option>';
						}
						?>
					</select>
				</div>

				<div class="sssj-field">
					<label><?php esc_html_e( 'Services you offer', 'shuffles-social-services-jobs' ); ?></label>
					<div class="sssj-row" style="flex-wrap:wrap">
						<?php
						if ( ! is_wp_error( $cats ) ) {
							foreach ( $cats as $t ) {
								$checked = in_array( $t->term_id, (array) $ex_services, true ) ? 'checked' : '';
								echo '<label class="sssj-chip"><input type="checkbox" name="services[]" value="' . esc_attr( $t->term_id ) . '" ' . esc_attr( $checked ) . ' /> ' . esc_html( $t->name ) . '</label>';
							}
						}
						?>
					</div>
				</div>

				<div class="sssj-row">
					<div class="sssj-field"><label><?php esc_html_e( 'Years experience', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="number" min="0" name="years_experience" value="<?php echo esc_attr( (string) $gm( 'years_experience', '' ) ); ?>" /></div>
					<div class="sssj-field"><label><?php esc_html_e( 'Rate min', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="number" min="0" step="0.01" name="rate_min" value="<?php echo esc_attr( (string) $gm( 'rate_min', '' ) ); ?>" /></div>
					<div class="sssj-field"><label><?php esc_html_e( 'Rate max', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="number" min="0" step="0.01" name="rate_max" value="<?php echo esc_attr( (string) $gm( 'rate_max', '' ) ); ?>" /></div>
					<div class="sssj-field"><label><?php esc_html_e( 'Per', 'shuffles-social-services-jobs' ); ?></label>
						<select class="sssj-select" name="rate_unit">
							<?php
							$ru = (string) $gm( 'rate_unit', 'hour' );
							foreach ( array( 'hour' => __( 'Hour', 'shuffles-social-services-jobs' ), 'day' => __( 'Day', 'shuffles-social-services-jobs' ), 'annum' => __( 'Annum', 'shuffles-social-services-jobs' ) ) as $v => $l ) {
								echo '<option value="' . esc_attr( $v ) . '" ' . selected( $ru, $v, false ) . '>' . esc_html( $l ) . '</option>';
							}
							?>
						</select>
					</div>
				</div>

				<div class="sssj-field">
					<label for="sssj-wabn"><?php esc_html_e( 'Your ABN (optional — required to respond to ABN/participant work)', 'shuffles-social-services-jobs' ); ?></label>
					<input class="sssj-input" id="sssj-wabn" type="text" name="worker_abn" inputmode="numeric" placeholder="11 digits" value="<?php echo esc_attr( (string) $gm( 'worker_abn', '' ) ); ?>" />
				</div>

				<div class="sssj-field">
					<label for="sssj-wvis"><?php esc_html_e( 'Who can see your profile', 'shuffles-social-services-jobs' ); ?></label>
					<select class="sssj-select" id="sssj-wvis" name="visibility">
						<option value="logged_in" <?php selected( $ex_vis, 'logged_in' ); ?>><?php esc_html_e( 'Logged-in members', 'shuffles-social-services-jobs' ); ?></option>
						<option value="public" <?php selected( $ex_vis, 'public' ); ?>><?php esc_html_e( 'Everyone (public)', 'shuffles-social-services-jobs' ); ?></option>
					</select>
				</div>

				<div><button class="sssj-btn sssj-btn--primary" type="submit"><?php echo $existing ? esc_html__( 'Save profile', 'shuffles-social-services-jobs' ) : esc_html__( 'Create profile', 'shuffles-social-services-jobs' ); ?></button></div>
			</form>
		<?php endif; ?>
	</div>
</div>
