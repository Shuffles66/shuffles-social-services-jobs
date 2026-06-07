<?php
/**
 * Organisation profile form (create/update the user's own org). Public + SEO-able.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$can = is_user_logged_in() && ( current_user_can( 'sssj_post_org' ) || current_user_can( 'manage_options' ) );
$st  = isset( $_GET['sssj_org'] ) ? sanitize_key( wp_unslash( $_GET['sssj_org'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$existing = null;
if ( is_user_logged_in() ) {
	$found = get_posts(
		array(
			'post_type'      => 'sssj_org',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'meta_key'       => 'org_user_id',
			'meta_value'     => get_current_user_id(),
		)
	);
	if ( ! empty( $found ) ) {
		$existing = $found[0];
	}
}
$gm = function ( $k, $d = '' ) use ( $existing ) {
	return $existing ? (string) get_post_meta( $existing->ID, $k, true ) : $d;
};
$ex_name = $existing ? $existing->post_title : '';
$ex_desc = $existing ? $existing->post_content : '';
$ex_type = $gm( 'org_type', 'employer' );
$ex_sectors    = $existing ? (array) wp_get_post_terms( $existing->ID, 'sssjt_category', array( 'fields' => 'ids' ) ) : array();
$ex_funding    = $existing ? (array) wp_get_post_terms( $existing->ID, 'sssjt_funding_source', array( 'fields' => 'ids' ) ) : array();
$sector_terms  = get_terms( array( 'taxonomy' => 'sssjt_category', 'hide_empty' => false ) );
$funding_terms = get_terms( array( 'taxonomy' => 'sssjt_funding_source', 'hide_empty' => false ) );
$ex_locs = '';
if ( $existing ) {
	$arr = json_decode( (string) get_post_meta( $existing->ID, 'locations', true ), true );
	if ( is_array( $arr ) ) {
		foreach ( $arr as $l ) {
			$ex_locs .= trim( ( isset( $l['label'] ) ? $l['label'] : '' ) . ' | ' . ( isset( $l['suburb'] ) ? $l['suburb'] : '' ) . ' | ' . ( isset( $l['state'] ) ? $l['state'] : '' ) . ' | ' . ( isset( $l['postcode'] ) ? $l['postcode'] : '' ) ) . "\n";
		}
	}
}
?>
<div class="sssj sssj--post-org">
	<div class="sssj-panel">
		<h2><?php echo $existing ? esc_html__( 'Edit your organisation profile', 'shuffles-social-services-jobs' ) : esc_html__( 'Create an organisation profile', 'shuffles-social-services-jobs' ); ?></h2>
		<p class="description"><?php esc_html_e( 'A permanent, searchable profile for your business — it lists all your open positions and is indexable by search engines.', 'shuffles-social-services-jobs' ); ?></p>

		<?php if ( '1' === $st ) : ?>
			<p class="sssj-badge sssj-badge--verified"><?php esc_html_e( 'Profile saved.', 'shuffles-social-services-jobs' ); ?></p>
		<?php elseif ( 'abn' === $st ) : ?>
			<p class="sssj-badge" style="background:#fee2e2;color:#b91c1c"><?php esc_html_e( 'A valid 11-digit ABN is required for an organisation.', 'shuffles-social-services-jobs' ); ?></p>
		<?php elseif ( 'error' === $st ) : ?>
			<p class="sssj-badge" style="background:#fee2e2;color:#b91c1c"><?php esc_html_e( 'An organisation name is required.', 'shuffles-social-services-jobs' ); ?></p>
		<?php endif; ?>

		<?php if ( ! $can ) : ?>
			<p><?php esc_html_e( 'Log in with an employer account to create a profile.', 'shuffles-social-services-jobs' ); ?>
				<a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"><?php esc_html_e( 'Log in', 'shuffles-social-services-jobs' ); ?></a></p>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sssj-stack" enctype="multipart/form-data" data-sssj-busy="<?php esc_attr_e( 'Saving and checking your details (NDIS register, ABN and location)…', 'shuffles-social-services-jobs' ); ?>">
				<input type="hidden" name="action" value="sssj_post_org" />
				<?php wp_nonce_field( 'sssj_post_org', 'sssj_org_nonce' ); ?>

				<div class="sssj-field">
					<label for="sssj-oname"><?php esc_html_e( 'Organisation name', 'shuffles-social-services-jobs' ); ?> *</label>
					<input class="sssj-input" id="sssj-oname" type="text" name="org_name" value="<?php echo esc_attr( $ex_name ); ?>" required />
				</div>
				<div class="sssj-field">
					<label for="sssj-odesc"><?php esc_html_e( 'About the organisation', 'shuffles-social-services-jobs' ); ?></label>
					<textarea class="sssj-textarea" id="sssj-odesc" name="description" rows="5"><?php echo esc_textarea( $ex_desc ); ?></textarea>
				</div>

				<div class="sssj-field">
					<label for="sssj-ologo"><?php esc_html_e( 'Logo', 'shuffles-social-services-jobs' ); ?></label>
					<?php if ( $existing && has_post_thumbnail( $existing->ID ) ) : ?>
						<div style="margin-bottom:6px"><?php echo get_the_post_thumbnail( $existing->ID, 'thumbnail', array( 'class' => 'sssj-org-logo' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<?php endif; ?>
					<input id="sssj-ologo" type="file" name="org_logo" accept="image/png,image/jpeg,image/webp,image/svg+xml,.png,.jpg,.jpeg,.webp,.svg" />
					<p class="description"><?php esc_html_e( 'PNG, JPG, WebP or SVG. Shown on your profile, cards and search results.', 'shuffles-social-services-jobs' ); ?></p>
				</div>
				<div class="sssj-row">
					<div class="sssj-field">
						<label><?php esc_html_e( 'Type', 'shuffles-social-services-jobs' ); ?></label>
						<select class="sssj-select" name="org_type">
							<?php
							foreach ( array( 'employer' => __( 'Employer', 'shuffles-social-services-jobs' ), 'agency' => __( 'Agency', 'shuffles-social-services-jobs' ), 'provider' => __( 'Provider', 'shuffles-social-services-jobs' ) ) as $v => $l ) {
								echo '<option value="' . esc_attr( $v ) . '" ' . selected( $ex_type, $v, false ) . '>' . esc_html( $l ) . '</option>';
							}
							?>
						</select>
					</div>
					<div class="sssj-field">
						<label><?php esc_html_e( 'Organisation category', 'shuffles-social-services-jobs' ); ?></label>
						<select class="sssj-select" name="org_category">
							<?php $ex_cat = $gm( 'org_category', 'support' ); foreach ( Shuffles_SSJ_Org::categories() as $v => $l ) { echo '<option value="' . esc_attr( $v ) . '" ' . selected( $ex_cat, $v, false ) . '>' . esc_html( $l ) . '</option>'; } ?>
						</select>
						<p class="description"><?php esc_html_e( 'What kind of organisation — support provider, supplier/services, SDA/housing, real estate or professional services. Drives where you appear in the directory.', 'shuffles-social-services-jobs' ); ?></p>
					</div>
					<div class="sssj-field">
						<label><?php esc_html_e( 'Organisation size', 'shuffles-social-services-jobs' ); ?></label>
						<select class="sssj-select" name="org_size">
							<option value=""><?php esc_html_e( '— select —', 'shuffles-social-services-jobs' ); ?></option>
							<?php $ex_size = $gm( 'org_size', '' ); foreach ( Shuffles_SSJ_Org::sizes() as $v => $l ) { echo '<option value="' . esc_attr( $v ) . '" ' . selected( $ex_size, $v, false ) . '>' . esc_html( $l ) . '</option>'; } ?>
						</select>
						<p class="description"><?php esc_html_e( 'Helps people filter sole traders vs larger providers.', 'shuffles-social-services-jobs' ); ?></p>
					</div>
					<div class="sssj-field">
						<label><?php esc_html_e( 'Legal structure', 'shuffles-social-services-jobs' ); ?></label>
						<select class="sssj-select" name="org_structure">
							<option value=""><?php esc_html_e( '— select —', 'shuffles-social-services-jobs' ); ?></option>
							<?php $ex_struct = $gm( 'org_structure', '' ); foreach ( Shuffles_SSJ_Org::structures() as $v => $l ) { echo '<option value="' . esc_attr( $v ) . '" ' . selected( $ex_struct, $v, false ) . '>' . esc_html( $l ) . '</option>'; } ?>
						</select>
					</div>
					<div class="sssj-field"><label><?php esc_html_e( 'Website', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="url" name="org_website" value="<?php echo esc_attr( $gm( 'org_website' ) ); ?>" placeholder="https://" />
						<button type="button" class="sssj-btn sssj-btn--ghost sssj-btn--sm" data-sssj-autofill data-loading="<?php esc_attr_e( 'Reading your site…', 'shuffles-social-services-jobs' ); ?>" data-empty="<?php esc_attr_e( 'Enter your website URL first (including https://).', 'shuffles-social-services-jobs' ); ?>" style="margin-top:6px">✨ <?php esc_html_e( 'Fetch details from my website', 'shuffles-social-services-jobs' ); ?></button>
						<p class="description"><?php esc_html_e( 'Our AI reads your website and suggests a name, description and phone — review before saving. (It only fills empty fields.)', 'shuffles-social-services-jobs' ); ?></p>
					</div>
					<div class="sssj-field"><label><?php esc_html_e( 'Phone', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="org_phone" value="<?php echo esc_attr( $gm( 'org_phone' ) ); ?>" /></div>
					<div class="sssj-field"><label><?php esc_html_e( 'ABN', 'shuffles-social-services-jobs' ); ?> *</label><input class="sssj-input" type="text" name="org_abn" inputmode="numeric" placeholder="11 digits" required value="<?php echo esc_attr( $gm( 'org_abn' ) ); ?>" /><p class="description"><?php esc_html_e( 'Required — organisations are businesses. Checked against the Australian Business Register when ABR verification is configured.', 'shuffles-social-services-jobs' ); ?></p></div>

					<?php if ( $existing && class_exists( 'Shuffles_SSJ_ABN' ) ) { $abr_rec = Shuffles_SSJ_ABN::abr_record_text( $existing->ID ); if ( '' !== $abr_rec ) : ?>
					<div class="sssj-field">
						<label for="sssj-oabr"><?php esc_html_e( 'Australian Business Register — recorded details', 'shuffles-social-services-jobs' ); ?></label>
						<textarea class="sssj-textarea" id="sssj-oabr" rows="8" readonly><?php echo esc_textarea( $abr_rec ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Recorded automatically from the ABR when your ABN was saved (includes trading / business names). Read-only — this is the Register’s own data.', 'shuffles-social-services-jobs' ); ?></p>
					</div>
					<?php endif; } ?>

				<div class="sssj-field">
					<label><input type="checkbox" name="ndis_registered" value="1" <?php checked( '1', (string) $gm( 'ndis_registered' ) ); ?> /> <?php esc_html_e( 'We are a registered NDIS provider', 'shuffles-social-services-jobs' ); ?></label>
				</div>
				<div class="sssj-field">
					<label for="sssj-ndisid"><?php esc_html_e( 'NDIS Registration No', 'shuffles-social-services-jobs' ); ?></label>
					<input class="sssj-input" id="sssj-ndisid" type="text" inputmode="numeric" name="ndis_register_id" value="<?php echo esc_attr( $gm( 'ndis_register_id' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. 902439', 'shuffles-social-services-jobs' ); ?>" />
					<button type="button" class="sssj-btn sssj-btn--secondary sssj-btn--sm" data-sssj-ndis-scan style="margin-top:6px">🛡️ <?php esc_html_e( 'Scan now', 'shuffles-social-services-jobs' ); ?></button>
					<div data-sssj-ndis-result style="margin-top:8px"></div>
					<p class="description"><?php echo wp_kses_post( sprintf( __( 'The number after <code>?id=</code> in your listing URL on the <a href="%s" target="_blank" rel="noopener">NDIS Commission register</a> (e.g. <code>…find-registered-provider?id=902439</code> → <code>902439</code>). When set, we read your public listing and show your verified registration status, the approved registration groups and the expiry date — refreshed automatically each month.', 'shuffles-social-services-jobs' ), esc_url( 'https://www.ndiscommission.gov.au/provider-registration/find-registered-provider' ) ) ); ?></p>
				</div>
				</div>

				<div class="sssj-row">
					<div class="sssj-field">
						<label for="sssj-osectors"><?php esc_html_e( 'Sectors you cover', 'shuffles-social-services-jobs' ); ?></label>
						<select class="sssj-select" id="sssj-osectors" name="org_sectors[]" multiple data-placeholder="<?php esc_attr_e( 'Choose sectors…', 'shuffles-social-services-jobs' ); ?>">
							<?php if ( ! is_wp_error( $sector_terms ) ) { foreach ( $sector_terms as $t ) { echo '<option value="' . esc_attr( $t->term_id ) . '" ' . ( in_array( $t->term_id, $ex_sectors, true ) ? 'selected' : '' ) . '>' . esc_html( $t->name ) . '</option>'; } } ?>
						</select>
					</div>
					<div class="sssj-field">
						<label for="sssj-ofunding"><?php esc_html_e( 'Funding sources accepted', 'shuffles-social-services-jobs' ); ?></label>
						<select class="sssj-select" id="sssj-ofunding" name="org_funding[]" multiple data-placeholder="<?php esc_attr_e( 'Choose funding…', 'shuffles-social-services-jobs' ); ?>">
							<?php if ( ! is_wp_error( $funding_terms ) ) { foreach ( $funding_terms as $t ) { echo '<option value="' . esc_attr( $t->term_id ) . '" ' . ( in_array( $t->term_id, $ex_funding, true ) ? 'selected' : '' ) . '>' . esc_html( $t->name ) . '</option>'; } } ?>
						</select>
					</div>
				</div>

				<div class="sssj-field">
					<label><?php esc_html_e( 'Social & profile links', 'shuffles-social-services-jobs' ); ?></label>
					<div class="sssj-stack">
						<?php foreach ( Shuffles_SSJ_Org::networks() as $nk => $net ) : ?>
							<input class="sssj-input" type="url" name="<?php echo esc_attr( $nk ); ?>" value="<?php echo esc_attr( $gm( $nk ) ); ?>" placeholder="<?php echo esc_attr( sprintf( __( '%s URL', 'shuffles-social-services-jobs' ), $net['label'] ) ); ?>" />
						<?php endforeach; ?>
					</div>
					<p class="description"><?php esc_html_e( 'Optional. Use the full URL for each (e.g. https://facebook.com/yourpage). “Shuffles profile” is your page on the Shuffles site.', 'shuffles-social-services-jobs' ); ?></p>
				</div>

				<div class="sssj-field" data-sssj-place-group>
					<label for="sssj-oplace"><?php esc_html_e( 'Main location', 'shuffles-social-services-jobs' ); ?></label>
					<input class="sssj-input" id="sssj-oplace" type="text" data-sssj-place placeholder="<?php esc_attr_e( 'Start typing a suburb…', 'shuffles-social-services-jobs' ); ?>" />
					<div class="sssj-row" style="margin-top:8px">
						<div class="sssj-field"><label><?php esc_html_e( 'Suburb', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="location_suburb" data-sssj-suburb value="<?php echo esc_attr( $gm( 'location_suburb' ) ); ?>" /></div>
						<div class="sssj-field"><label><?php esc_html_e( 'State', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="location_state" data-sssj-state value="<?php echo esc_attr( $gm( 'location_state' ) ); ?>" /></div>
						<div class="sssj-field"><label><?php esc_html_e( 'Postcode', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="location_postcode" data-sssj-postcode value="<?php echo esc_attr( $gm( 'location_postcode' ) ); ?>" /></div>
					</div>
					<input type="hidden" name="location_lat" data-sssj-lat value="<?php echo esc_attr( $gm( 'location_lat' ) ); ?>" />
					<input type="hidden" name="location_lng" data-sssj-lng value="<?php echo esc_attr( $gm( 'location_lng' ) ); ?>" />
				</div>

				<div class="sssj-field">
					<label for="sssj-olocs"><?php esc_html_e( 'Additional locations', 'shuffles-social-services-jobs' ); ?></label>
					<textarea class="sssj-textarea" id="sssj-olocs" name="locations" rows="4" placeholder="<?php esc_attr_e( 'One per line:  Label | Suburb | State | Postcode', 'shuffles-social-services-jobs' ); ?>"><?php echo esc_textarea( trim( $ex_locs ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One location per line, fields separated by a vertical bar: e.g.  North office | Newcastle | NSW | 2300', 'shuffles-social-services-jobs' ); ?></p>
				</div>

				<div class="sssj-field">
					<label for="sssj-otravel"><?php esc_html_e( 'Service area radius (km)', 'shuffles-social-services-jobs' ); ?></label>
					<input class="sssj-input" id="sssj-otravel" type="number" min="0" max="500" step="5" name="travel_radius_km" value="<?php echo esc_attr( (string) $gm( 'travel_radius_km' ) ); ?>" />
					<p class="description"><?php esc_html_e( 'How far from your location(s) you provide services. Helps match you to nearby work. Leave blank for no limit.', 'shuffles-social-services-jobs' ); ?></p>
				</div>

				<?php if ( class_exists( 'Shuffles_SSJ_Field_Registry' ) ) { Shuffles_SSJ_Field_Registry::render( 'org', $existing ? $existing->ID : 0 ); } ?>

				<div class="sssj-field">
					<label><input type="checkbox" name="org_hidden" value="1" <?php checked( '1', (string) $gm( 'org_hidden' ) ); ?> /> <?php esc_html_e( 'Do not display — hide this organisation from search engines and the directory', 'shuffles-social-services-jobs' ); ?></label>
				</div>

				<fieldset class="sssj-fieldset">
					<legend><?php esc_html_e( 'Open to…', 'shuffles-social-services-jobs' ); ?></legend>
					<div class="sssj-field"><label class="sssj-check"><input type="checkbox" name="offers_sponsorship" value="1" <?php checked( '1', (string) $gm( 'offers_sponsorship' ) ); ?> /> ✈️ <?php esc_html_e( 'Open to overseas applicants — we sponsor visas', 'shuffles-social-services-jobs' ); ?></label></div>
					<div class="sssj-field"><label class="sssj-check"><input type="checkbox" name="accepts_placements" value="1" <?php checked( '1', (string) $gm( 'accepts_placements' ) ); ?> /> 🎓 <?php esc_html_e( 'We accept work-placement / student-placement enquiries', 'shuffles-social-services-jobs' ); ?></label></div>
					<div class="sssj-field"><label class="sssj-check"><input type="checkbox" name="welcomes_volunteers" value="1" <?php checked( '1', (string) $gm( 'welcomes_volunteers' ) ); ?> /> 🤝 <?php esc_html_e( 'We welcome volunteer enquiries', 'shuffles-social-services-jobs' ); ?></label></div>
				</fieldset>

				<?php if ( class_exists( 'Shuffles_SSJ_Privacy' ) ) { Shuffles_SSJ_Privacy::fields_html( 'org', $existing ? $existing->ID : 0 ); } ?>
				<div><button class="sssj-btn sssj-btn--primary" type="submit"><?php echo $existing ? esc_html__( 'Save profile', 'shuffles-social-services-jobs' ) : esc_html__( 'Create profile', 'shuffles-social-services-jobs' ); ?></button></div>
			</form>
		<?php endif; ?>
	</div>
</div>
