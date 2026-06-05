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
			<p class="sssj-badge" style="background:#fee2e2;color:#b91c1c"><?php esc_html_e( 'That ABN failed validation.', 'shuffles-social-services-jobs' ); ?></p>
		<?php elseif ( 'error' === $st ) : ?>
			<p class="sssj-badge" style="background:#fee2e2;color:#b91c1c"><?php esc_html_e( 'An organisation name is required.', 'shuffles-social-services-jobs' ); ?></p>
		<?php endif; ?>

		<?php if ( ! $can ) : ?>
			<p><?php esc_html_e( 'Log in with an employer account to create a profile.', 'shuffles-social-services-jobs' ); ?>
				<a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"><?php esc_html_e( 'Log in', 'shuffles-social-services-jobs' ); ?></a></p>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sssj-stack">
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
					<div class="sssj-field"><label><?php esc_html_e( 'Website', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="url" name="org_website" value="<?php echo esc_attr( $gm( 'org_website' ) ); ?>" placeholder="https://" /></div>
					<div class="sssj-field"><label><?php esc_html_e( 'Phone', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="org_phone" value="<?php echo esc_attr( $gm( 'org_phone' ) ); ?>" /></div>
					<div class="sssj-field"><label><?php esc_html_e( 'ABN (optional)', 'shuffles-social-services-jobs' ); ?></label><input class="sssj-input" type="text" name="org_abn" inputmode="numeric" value="<?php echo esc_attr( $gm( 'org_abn' ) ); ?>" /></div>
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

				<div><button class="sssj-btn sssj-btn--primary" type="submit"><?php echo $existing ? esc_html__( 'Save profile', 'shuffles-social-services-jobs' ) : esc_html__( 'Create profile', 'shuffles-social-services-jobs' ); ?></button></div>
			</form>
		<?php endif; ?>
	</div>
</div>
