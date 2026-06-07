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
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sssj-stack" enctype="multipart/form-data" data-sssj-busy="<?php esc_attr_e( 'Saving your profile and checking your details…', 'shuffles-social-services-jobs' ); ?>">
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
					<label for="sssj-wphoto"><?php esc_html_e( 'Profile photo', 'shuffles-social-services-jobs' ); ?></label>
					<?php $cur_photo = $existing ? get_the_post_thumbnail_url( $existing->ID, 'thumbnail' ) : ''; ?>
					<?php if ( $cur_photo ) : ?><img src="<?php echo esc_url( $cur_photo ); ?>" alt="" class="sssj-worker-photo" style="width:72px;height:72px;margin-bottom:6px" /><?php endif; ?>
					<input id="sssj-wphoto" type="file" name="worker_photo" accept="image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp" />
					<?php echo Shuffles_SSJ_Media::image_guidance( 'photo' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>

				<div class="sssj-field">
					<label for="sssj-wgallery"><?php esc_html_e( 'More photos (optional gallery)', 'shuffles-social-services-jobs' ); ?></label>
					<input id="sssj-wgallery" type="file" name="worker_gallery[]" accept="image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp" multiple />
					<?php echo Shuffles_SSJ_Media::image_guidance( 'gallery' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>

				<?php echo Shuffles_SSJ_Media::video_field( (string) $gm( 'video_url', '' ), 'worker' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

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
					<label for="sssj-wservices"><?php esc_html_e( 'Services you offer', 'shuffles-social-services-jobs' ); ?></label>
					<select class="sssj-select" id="sssj-wservices" name="services[]" multiple data-placeholder="<?php esc_attr_e( 'Search and add services…', 'shuffles-social-services-jobs' ); ?>">
						<?php
						if ( ! is_wp_error( $cats ) ) {
							foreach ( $cats as $t ) {
								echo '<option value="' . esc_attr( $t->term_id ) . '" ' . ( in_array( $t->term_id, (array) $ex_services, true ) ? 'selected' : '' ) . '>' . esc_html( $t->name ) . '</option>';
							}
						}
						?>
					</select>
				</div>

				<?php
				Shuffles_SSJ_Shortcodes::culture_language_fields(
					$existing ? (array) wp_get_post_terms( $existing->ID, 'sssjt_culture', array( 'fields' => 'ids' ) ) : array(),
					$existing ? (array) wp_get_post_terms( $existing->ID, 'sssjt_language', array( 'fields' => 'ids' ) ) : array()
				);
				if ( class_exists( 'Shuffles_SSJ_Field_Registry' ) ) {
					Shuffles_SSJ_Field_Registry::render( 'worker', $existing ? $existing->ID : 0 );
				}
				?>

				<div class="sssj-field" data-sssj-place-group>
					<label for="sssj-wplace"><?php esc_html_e( 'Your location', 'shuffles-social-services-jobs' ); ?></label>
					<input class="sssj-input" id="sssj-wplace" type="text" data-sssj-place placeholder="<?php esc_attr_e( 'Start typing a suburb…', 'shuffles-social-services-jobs' ); ?>" />
					<div class="sssj-row" style="margin-top:8px">
						<div class="sssj-field"><label><?php esc_html_e( 'Suburb', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="location_suburb" data-sssj-suburb value="<?php echo esc_attr( (string) $gm( 'location_suburb' ) ); ?>" /></div>
						<div class="sssj-field"><label><?php esc_html_e( 'State', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="location_state" data-sssj-state value="<?php echo esc_attr( (string) $gm( 'location_state' ) ); ?>" /></div>
						<div class="sssj-field"><label><?php esc_html_e( 'Postcode', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="location_postcode" data-sssj-postcode value="<?php echo esc_attr( (string) $gm( 'location_postcode' ) ); ?>" /></div>
					</div>
					<input type="hidden" name="location_lat" data-sssj-lat value="<?php echo esc_attr( (string) $gm( 'location_lat' ) ); ?>" />
					<input type="hidden" name="location_lng" data-sssj-lng value="<?php echo esc_attr( (string) $gm( 'location_lng' ) ); ?>" />
					<p class="description"><?php esc_html_e( 'Lets employers and participants find you by location / radius. Only your suburb is shown publicly.', 'shuffles-social-services-jobs' ); ?></p>
				</div>

				<div class="sssj-field">
					<label for="sssj-wtravel"><?php esc_html_e( 'How far are you willing to travel? (km)', 'shuffles-social-services-jobs' ); ?></label>
					<input class="sssj-input" id="sssj-wtravel" type="number" min="0" max="500" step="5" name="travel_radius_km" value="<?php echo esc_attr( (string) $gm( 'travel_radius_km', '' ) ); ?>" />
					<p class="description"><?php esc_html_e( 'Used to match you to nearby work, and to set the default distance on your searches. Leave blank for no limit.', 'shuffles-social-services-jobs' ); ?></p>
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

				<?php if ( $existing && class_exists( 'Shuffles_SSJ_ABN' ) ) { $abr_rec = Shuffles_SSJ_ABN::abr_record_text( $existing->ID ); if ( '' !== $abr_rec ) : ?>
				<div class="sssj-field">
					<label for="sssj-wabr"><?php esc_html_e( 'Australian Business Register — recorded details', 'shuffles-social-services-jobs' ); ?></label>
					<textarea class="sssj-textarea" id="sssj-wabr" rows="8" readonly><?php echo esc_textarea( $abr_rec ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Recorded automatically from the ABR when your ABN was saved (includes trading / business names). Read-only — this is the Register’s own data.', 'shuffles-social-services-jobs' ); ?></p>
				</div>
				<?php endif; } ?>

				<div class="sssj-field">
					<label><input type="checkbox" name="ndis_registered" value="1" <?php checked( '1', (string) $gm( 'ndis_registered' ) ); ?> /> <?php esc_html_e( 'I’m an NDIS-registered sole trader (registered in my own right)', 'shuffles-social-services-jobs' ); ?></label>
				</div>
				<div class="sssj-field">
					<label for="sssj-wndisid"><?php esc_html_e( 'NDIS Registration No', 'shuffles-social-services-jobs' ); ?></label>
					<input class="sssj-input" id="sssj-wndisid" type="text" inputmode="numeric" name="ndis_register_id" value="<?php echo esc_attr( (string) $gm( 'ndis_register_id', '' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. 902439', 'shuffles-social-services-jobs' ); ?>" />
					<button type="button" class="sssj-btn sssj-btn--secondary sssj-btn--sm" data-sssj-ndis-scan style="margin-top:6px">🛡️ <?php esc_html_e( 'Scan now', 'shuffles-social-services-jobs' ); ?></button>
					<div data-sssj-ndis-result style="margin-top:8px"></div>
					<p class="description"><?php echo wp_kses_post( sprintf( __( 'If you hold NDIS registration as an individual / sole trader, enter the number after <code>?id=</code> in your listing URL on the <a href="%s" target="_blank" rel="noopener">NDIS Commission register</a>. We’ll read your public listing and show your verified status, registration groups and expiry — refreshed automatically each month.', 'shuffles-social-services-jobs' ), esc_url( 'https://www.ndiscommission.gov.au/provider-registration/find-registered-provider' ) ) ); ?></p>
				</div>

				<div class="sssj-field">
					<label for="sssj-wvis"><?php esc_html_e( 'Who can see your profile', 'shuffles-social-services-jobs' ); ?></label>
					<select class="sssj-select" id="sssj-wvis" name="visibility">
						<option value="logged_in" <?php selected( $ex_vis, 'logged_in' ); ?>><?php esc_html_e( 'Logged-in members', 'shuffles-social-services-jobs' ); ?></option>
						<option value="public" <?php selected( $ex_vis, 'public' ); ?>><?php esc_html_e( 'Everyone (public)', 'shuffles-social-services-jobs' ); ?></option>
						<option value="hidden" <?php selected( $ex_vis, 'hidden' ); ?>><?php esc_html_e( 'Do not display — hide from search engines and public listings', 'shuffles-social-services-jobs' ); ?></option>
					</select>
				</div>

				<div class="sssj-field">
					<label><input type="checkbox" name="alert_jobs" value="1" <?php checked( '1', (string) get_user_meta( get_current_user_id(), 'sssj_alert_jobs', true ) ); ?> /> <?php esc_html_e( 'Email me when new jobs match my profile', 'shuffles-social-services-jobs' ); ?></label>
				</div>

				<?php if ( class_exists( 'Shuffles_SSJ_Privacy' ) ) { Shuffles_SSJ_Privacy::fields_html( 'worker', $existing ? $existing->ID : 0 ); } ?>
				<div><button class="sssj-btn sssj-btn--primary" type="submit"><?php echo $existing ? esc_html__( 'Save profile', 'shuffles-social-services-jobs' ) : esc_html__( 'Create profile', 'shuffles-social-services-jobs' ); ?></button></div>
			</form>
		<?php endif; ?>
	</div>
</div>
