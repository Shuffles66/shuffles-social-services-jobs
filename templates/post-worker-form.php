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

// Participants (and their representatives) are not businesses: do not ask them for ABN or NDIS
// provider-registration details. Only hide when the member has declared hats and they are all
// participant / representative (legacy no-hat users still see everything).
$roles_w          = ( is_user_logged_in() && class_exists( 'Shuffles_SSJ_Roles' ) ) ? (array) Shuffles_SSJ_Roles::member_roles( get_current_user_id() ) : array();
$participant_only = ! empty( $roles_w ) && ! array_diff( $roles_w, array( 'participant', 'representative' ) );

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
				<a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="<?php echo esc_url( Shuffles_SSJ_Shortcodes::login_url( get_permalink() ) ); ?>"><?php esc_html_e( 'Log in', 'shuffles-social-services-jobs' ); ?></a></p>
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
							'employed-open-to-more'  => __( 'Employed, open to more', 'shuffles-social-services-jobs' ),
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

				<?php if ( ! $participant_only ) : ?>
				<div class="sssj-field">
					<label for="sssj-wabn"><?php esc_html_e( 'Your ABN (optional, required to respond to ABN/participant work)', 'shuffles-social-services-jobs' ); ?></label>
					<input class="sssj-input" id="sssj-wabn" type="text" name="worker_abn" inputmode="numeric" placeholder="11 digits" value="<?php echo esc_attr( (string) $gm( 'worker_abn', '' ) ); ?>" />
				</div>

				<?php if ( $existing && class_exists( 'Shuffles_SSJ_ABN' ) ) { $abr_rec = Shuffles_SSJ_ABN::abr_record_text( $existing->ID ); if ( '' !== $abr_rec ) : ?>
				<div class="sssj-field">
					<label for="sssj-wabr"><?php esc_html_e( 'Australian Business Register, recorded details', 'shuffles-social-services-jobs' ); ?></label>
					<textarea class="sssj-textarea" id="sssj-wabr" rows="8" readonly><?php echo esc_textarea( $abr_rec ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Recorded automatically from the ABR when your ABN was saved (includes trading / business names). Read-only, this is the Register’s own data.', 'shuffles-social-services-jobs' ); ?></p>
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
					<p class="description"><?php echo wp_kses_post( sprintf( __( 'If you hold NDIS registration as an individual / sole trader, enter the number after <code>?id=</code> in your listing URL on the <a href="%s" target="_blank" rel="noopener">NDIS Commission register</a>. We’ll read your public listing and show your verified status, registration groups and expiry, refreshed automatically each month.', 'shuffles-social-services-jobs' ), esc_url( 'https://www.ndiscommission.gov.au/provider-registration/find-registered-provider' ) ) ); ?></p>
				</div>
				<?php
				// Multiple registered outlets usually mean an organisation, not one person. Ask.
				$wk_outlets = $existing ? json_decode( (string) get_post_meta( $existing->ID, 'ndis_outlets', true ), true ) : array();
				$wk_outlets = is_array( $wk_outlets ) ? array_values( array_filter( $wk_outlets, function ( $o ) { return ! empty( $o['name'] ) || ! empty( $o['phone'] ); } ) ) : array();
				if ( count( $wk_outlets ) > 1 ) :
					$org_url = class_exists( 'Shuffles_SSJ_Shortcodes' ) ? Shuffles_SSJ_Shortcodes::page_link( 'page_post_org', '[sssj_post_org]' ) : '';
					?>
					<div class="sssj-ask">
						<strong><?php echo esc_html( sprintf( __( 'Your registration lists %d outlets.', 'shuffles-social-services-jobs' ), count( $wk_outlets ) ) ); ?></strong>
						<p><?php esc_html_e( 'Multiple outlets usually belong to an organisation, not one person. Would you like to attribute this registration to an organisation instead? The registration and its locations then sit on the organisation, and your personal profile keeps your own single location.', 'shuffles-social-services-jobs' ); ?></p>
						<p class="description"><?php esc_html_e( 'Note: the register only shares each outlet’s name and phone, not its street address, so outlet locations are added on the organisation, not placed on the map automatically.', 'shuffles-social-services-jobs' ); ?></p>
						<?php if ( $org_url ) : ?>
							<a class="sssj-btn sssj-btn--secondary sssj-btn--sm" href="<?php echo esc_url( $org_url ); ?>"><?php esc_html_e( 'Create an organisation and attribute it there', 'shuffles-social-services-jobs' ); ?></a>
						<?php endif; ?>
						<p class="description"><?php esc_html_e( 'Prefer to keep it on your profile? No change is needed.', 'shuffles-social-services-jobs' ); ?></p>
					</div>
				<?php endif; ?>
				<?php endif; // ! participant_only ?>

				<div class="sssj-field">
					<label for="sssj-wvis"><?php esc_html_e( 'Who can see your profile', 'shuffles-social-services-jobs' ); ?></label>
					<select class="sssj-select" id="sssj-wvis" name="visibility">
						<option value="logged_in" <?php selected( $ex_vis, 'logged_in' ); ?>><?php esc_html_e( 'Logged-in members', 'shuffles-social-services-jobs' ); ?></option>
						<option value="public" <?php selected( $ex_vis, 'public' ); ?>><?php esc_html_e( 'Everyone (public)', 'shuffles-social-services-jobs' ); ?></option>
						<option value="hidden" <?php selected( $ex_vis, 'hidden' ); ?>><?php esc_html_e( 'Do not display, hide from search engines and public listings', 'shuffles-social-services-jobs' ); ?></option>
					</select>
				</div>

				<div class="sssj-field">
					<label><input type="checkbox" name="alert_jobs" value="1" <?php checked( '1', (string) get_user_meta( get_current_user_id(), 'sssj_alert_jobs', true ) ); ?> /> <?php esc_html_e( 'Email me when new jobs match my profile', 'shuffles-social-services-jobs' ); ?></label>
				</div>

				<details class="sssj-resume-details" <?php echo $existing ? "" : "open"; ?>>
						<summary><strong><?php esc_html_e( 'Résumé details (private)', 'shuffles-social-services-jobs' ); ?></strong></summary>
						<p class="description"><?php esc_html_e( 'These fields are used only to build your downloadable résumé. Your phone, email and employment history are never shown on your public profile or listings.', 'shuffles-social-services-jobs' ); ?></p>
						<p class="description">🛡️ <?php echo wp_kses_post( sprintf( __( 'Your résumé downloads in an ATS-friendly layout (plain, and easy for recruitment systems to read) by default. Match your wording to the job ad. <a href="%s" target="_blank" rel="noopener noreferrer">What is an ATS?</a>', 'shuffles-social-services-jobs' ), esc_url( apply_filters( 'shuffles_ssj_ats_guide_url', 'https://au.indeed.com/career-advice/resumes-cover-letters/applicant-tracking-systems' ) ) ) ); ?></p>

						<div class="sssj-field"><label><?php esc_html_e( 'Professional summary (3 to 5 lines)', 'shuffles-social-services-jobs' ); ?></label>
							<textarea class="sssj-textarea" name="resume_summary" rows="4" maxlength="600" placeholder="<?php esc_attr_e( 'e.g. Compassionate support worker experienced in community access, personal care and psychosocial support, known for calm communication and reliable documentation.', 'shuffles-social-services-jobs' ); ?>"><?php echo esc_textarea( (string) $gm( 'resume_summary' ) ); ?></textarea></div>

						<div class="sssj-row">
							<div class="sssj-field"><label><?php esc_html_e( 'Phone (résumé only)', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="resume_phone" value="<?php echo esc_attr( (string) $gm( 'resume_phone' ) ); ?>" /></div>
							<div class="sssj-field"><label><?php esc_html_e( 'Email (résumé only)', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="email" name="resume_email" value="<?php echo esc_attr( (string) $gm( 'resume_email' ) ); ?>" /></div>
							<div class="sssj-field"><label><?php esc_html_e( 'LinkedIn (optional)', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="url" name="resume_linkedin" value="<?php echo esc_attr( (string) $gm( 'resume_linkedin' ) ); ?>" placeholder="https://www.linkedin.com/in/" /></div>
						</div>

						<h4 style="margin:14px 0 4px"><?php esc_html_e( 'Compliance & credentials', 'shuffles-social-services-jobs' ); ?></h4>
						<p class="description"><?php esc_html_e( 'Verified screening checks (NDIS, WWCC, police) come from My credentials. Add your other details here.', 'shuffles-social-services-jobs' ); ?></p>
						<div class="sssj-field"><label><?php esc_html_e( 'Qualifications (one per line)', 'shuffles-social-services-jobs' ); ?></label>
							<textarea class="sssj-textarea" name="resume_qualifications" rows="3" placeholder="Cert III Individual Support&#10;Diploma of Nursing"><?php echo esc_textarea( (string) $gm( 'resume_qualifications' ) ); ?></textarea></div>
						<div class="sssj-row">
							<div class="sssj-field"><label><?php esc_html_e( 'Registration (e.g. AHPRA)', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="resume_registration" value="<?php echo esc_attr( (string) $gm( 'resume_registration' ) ); ?>" /></div>
							<div class="sssj-field"><label><?php esc_html_e( 'Work rights', 'shuffles-social-services-jobs' ); ?></label>
								<select class="sssj-select" name="work_rights">
									<?php $wrk = (string) $gm( 'work_rights' ); foreach ( array( '' => __( 'Prefer not to say', 'shuffles-social-services-jobs' ), 'citizen' => __( 'Australian citizen', 'shuffles-social-services-jobs' ), 'pr' => __( 'Permanent resident', 'shuffles-social-services-jobs' ), 'visa' => __( 'Valid working visa', 'shuffles-social-services-jobs' ), 'other' => __( 'Other', 'shuffles-social-services-jobs' ) ) as $wv => $wl ) { echo '<option value="' . esc_attr( $wv ) . '" ' . selected( $wrk, $wv, false ) . '>' . esc_html( $wl ) . '</option>'; } ?>
								</select></div>
						</div>
						<div class="sssj-field"><label><?php esc_html_e( 'Training (First Aid, CPR, manual handling, medication assistance)', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="resume_training" value="<?php echo esc_attr( (string) $gm( 'resume_training' ) ); ?>" /></div>
						<div class="sssj-field"><label><?php esc_html_e( 'Licences (driver licence, reliable vehicle, insurance)', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="resume_licences" value="<?php echo esc_attr( (string) $gm( 'resume_licences' ) ); ?>" /></div>
						<div class="sssj-field"><label><?php esc_html_e( 'Visa details, if relevant', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="work_rights_note" value="<?php echo esc_attr( (string) $gm( 'work_rights_note' ) ); ?>" /></div>

						<div class="sssj-field"><label><?php esc_html_e( 'Core skills (keywords, comma or line separated)', 'shuffles-social-services-jobs' ); ?></label>
							<textarea class="sssj-textarea" name="resume_skills" rows="3" placeholder="<?php esc_attr_e( 'Personal care, community access, behaviour support, progress notes, manual handling, mental health support', 'shuffles-social-services-jobs' ); ?>"><?php echo esc_textarea( (string) $gm( 'resume_skills' ) ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Match these to the job ad. Leave blank to use the services you offer.', 'shuffles-social-services-jobs' ); ?></p></div>

						<h4 style="margin:14px 0 4px"><?php esc_html_e( 'Employment history (newest first)', 'shuffles-social-services-jobs' ); ?></h4>
						<div data-sssj-repeater="emp">
							<?php
							$rep_emp = $existing ? json_decode( (string) get_post_meta( $existing->ID, 'resume_employment', true ), true ) : array();
							$rep_emp = is_array( $rep_emp ) ? $rep_emp : array();
							$emp_row = function ( $j = array() ) {
								$bb = ! empty( $j['bullets'] ) ? implode( "\n", (array) $j['bullets'] ) : '';
								?>
								<div class="sssj-rep-row sssj-panel" style="padding:12px;margin:8px 0">
									<div class="sssj-row">
										<div class="sssj-field"><label><?php esc_html_e( 'Job title', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="emp_title[]" value="<?php echo esc_attr( isset( $j['title'] ) ? $j['title'] : '' ); ?>" /></div>
										<div class="sssj-field"><label><?php esc_html_e( 'Employer', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="emp_employer[]" value="<?php echo esc_attr( isset( $j['employer'] ) ? $j['employer'] : '' ); ?>" /></div>
									</div>
									<div class="sssj-row">
										<div class="sssj-field"><label><?php esc_html_e( 'Location', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="emp_location[]" value="<?php echo esc_attr( isset( $j['location'] ) ? $j['location'] : '' ); ?>" /></div>
										<div class="sssj-field"><label><?php esc_html_e( 'Dates', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="emp_dates[]" placeholder="2022 to present" value="<?php echo esc_attr( isset( $j['dates'] ) ? $j['dates'] : '' ); ?>" /></div>
									</div>
									<div class="sssj-field"><label><?php esc_html_e( 'What you did (one bullet per line, 4 to 6)', 'shuffles-social-services-jobs' ); ?></label><textarea class="sssj-textarea" name="emp_bullets[]" rows="4"><?php echo esc_textarea( $bb ); ?></textarea></div>
									<button type="button" class="sssj-btn sssj-btn--ghost sssj-btn--sm" data-sssj-rep-remove><?php esc_html_e( 'Remove this role', 'shuffles-social-services-jobs' ); ?></button>
								</div>
								<?php
							};
							if ( $rep_emp ) { foreach ( $rep_emp as $j ) { $emp_row( $j ); } } else { $emp_row(); }
							?>
							<template data-sssj-rep-tpl><?php $emp_row(); ?></template>
							<button type="button" class="sssj-btn sssj-btn--secondary sssj-btn--sm" data-sssj-rep-add><?php esc_html_e( '+ Add another role', 'shuffles-social-services-jobs' ); ?></button>
						</div>

						<h4 style="margin:14px 0 4px"><?php esc_html_e( 'Education & training (newest first)', 'shuffles-social-services-jobs' ); ?></h4>
						<div data-sssj-repeater="edu">
							<?php
							$rep_edu = $existing ? json_decode( (string) get_post_meta( $existing->ID, 'resume_education', true ), true ) : array();
							$rep_edu = is_array( $rep_edu ) ? $rep_edu : array();
							$edu_row = function ( $e = array() ) {
								?>
								<div class="sssj-rep-row sssj-panel" style="padding:12px;margin:8px 0">
									<div class="sssj-row">
										<div class="sssj-field"><label><?php esc_html_e( 'Qualification / course', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="edu_qual[]" value="<?php echo esc_attr( isset( $e['qualification'] ) ? $e['qualification'] : '' ); ?>" /></div>
										<div class="sssj-field"><label><?php esc_html_e( 'Institution', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="edu_inst[]" value="<?php echo esc_attr( isset( $e['institution'] ) ? $e['institution'] : '' ); ?>" /></div>
										<div class="sssj-field"><label><?php esc_html_e( 'Year', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="edu_year[]" value="<?php echo esc_attr( isset( $e['year'] ) ? $e['year'] : '' ); ?>" /></div>
									</div>
									<button type="button" class="sssj-btn sssj-btn--ghost sssj-btn--sm" data-sssj-rep-remove><?php esc_html_e( 'Remove', 'shuffles-social-services-jobs' ); ?></button>
								</div>
								<?php
							};
							if ( $rep_edu ) { foreach ( $rep_edu as $e ) { $edu_row( $e ); } } else { $edu_row(); }
							?>
							<template data-sssj-rep-tpl><?php $edu_row(); ?></template>
							<button type="button" class="sssj-btn sssj-btn--secondary sssj-btn--sm" data-sssj-rep-add><?php esc_html_e( '+ Add education', 'shuffles-social-services-jobs' ); ?></button>
						</div>

						<div class="sssj-field"><label><?php esc_html_e( 'Referees', 'shuffles-social-services-jobs' ); ?></label>
							<?php $rm = (string) $gm( 'resume_referees_mode', 'request' ); ?>
							<select class="sssj-select" name="resume_referees_mode">
								<option value="request" <?php selected( $rm, 'request' ); ?>><?php esc_html_e( 'Available on request (recommended)', 'shuffles-social-services-jobs' ); ?></option>
								<option value="upfront" <?php selected( $rm, 'upfront' ); ?>><?php esc_html_e( 'List referees on the résumé', 'shuffles-social-services-jobs' ); ?></option>
							</select>
							<textarea class="sssj-textarea" name="resume_referees" rows="3" placeholder="<?php esc_attr_e( 'Name, role, organisation, phone, email, one per line (only used if you choose to list them).', 'shuffles-social-services-jobs' ); ?>" style="margin-top:6px"><?php echo esc_textarea( (string) $gm( 'resume_referees' ) ); ?></textarea>
						</div>
					</details>

					<?php if ( class_exists( 'Shuffles_SSJ_Privacy' ) ) { Shuffles_SSJ_Privacy::fields_html( 'worker', $existing ? $existing->ID : 0 ); } ?>
				<div><button class="sssj-btn sssj-btn--primary" type="submit"><?php echo $existing ? esc_html__( 'Save profile', 'shuffles-social-services-jobs' ) : esc_html__( 'Create profile', 'shuffles-social-services-jobs' ); ?></button></div>
			</form>
		<?php endif; ?>
	</div>
</div>
