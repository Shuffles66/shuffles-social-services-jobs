<?php
/**
 * Appended to a single worker / contractor profile: photo, availability, services, rate, location,
 * verified credentials, culture/language, custom fields and a photo gallery. Var: $worker_id.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$worker_id = isset( $worker_id ) ? (int) $worker_id : 0;
if ( ! $worker_id ) {
	return;
}

$avail    = '1' === (string) get_post_meta( $worker_id, 'is_available', true );
$status   = (string) get_post_meta( $worker_id, 'employment_status', true );
$yrs      = (int) get_post_meta( $worker_id, 'years_experience', true );
$rmin     = (float) get_post_meta( $worker_id, 'rate_min', true );
$rmax     = (float) get_post_meta( $worker_id, 'rate_max', true );
$runit    = (string) get_post_meta( $worker_id, 'rate_unit', true );
$suburb   = (string) get_post_meta( $worker_id, 'location_suburb', true );
$state    = (string) get_post_meta( $worker_id, 'location_state', true );
$verified = (string) get_post_meta( $worker_id, 'verified_at', true );
$vkinds   = (array) get_post_meta( $worker_id, 'verified_kinds', true );
$svcs     = wp_get_post_terms( $worker_id, 'sssjt_category', array( 'fields' => 'names' ) );
$culture  = wp_get_post_terms( $worker_id, 'sssjt_culture', array( 'fields' => 'names' ) );
$langs    = wp_get_post_terms( $worker_id, 'sssjt_language', array( 'fields' => 'names' ) );
$photo    = get_the_post_thumbnail_url( $worker_id, 'medium' );
$gallery  = array_filter( array_map( 'intval', (array) get_post_meta( $worker_id, '_sssj_gallery', true ) ) );
$status_labels = array(
	'seeking'               => __( 'Seeking work', 'shuffles-social-services-jobs' ),
	'employed-open-to-more' => __( 'Employed — open to more', 'shuffles-social-services-jobs' ),
	'not-looking'           => __( 'Not currently looking', 'shuffles-social-services-jobs' ),
);
?>
<div class="sssj sssj--worker">
	<div class="sssj-panel">
		<div class="sssj-worker-head">
			<?php if ( $photo ) : ?>
				<img class="sssj-worker-photo" src="<?php echo esc_url( $photo ); ?>" alt="" />
			<?php endif; ?>
			<div class="sssj-row" style="gap:8px;flex-wrap:wrap">
				<?php echo Shuffles_SSJ_Verification::tick_html( $worker_id, true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php if ( $avail ) : ?><span class="sssj-badge sssj-badge--verified"><?php esc_html_e( 'Available now', 'shuffles-social-services-jobs' ); ?></span><?php endif; ?>
				<?php if ( $verified ) : ?><span class="sssj-badge sssj-badge--verified">✓ <?php esc_html_e( 'Verified', 'shuffles-social-services-jobs' ); ?></span><?php endif; ?>
				<?php if ( $status && isset( $status_labels[ $status ] ) ) : ?><span class="sssj-badge"><?php echo esc_html( $status_labels[ $status ] ); ?></span><?php endif; ?>
				<?php if ( $yrs > 0 ) : ?><span class="sssj-badge"><?php echo esc_html( sprintf( _n( '%d yr experience', '%d yrs experience', $yrs, 'shuffles-social-services-jobs' ), $yrs ) ); ?></span><?php endif; ?>
			</div>
		</div>

		<div class="sssj-worker-facts">
			<?php if ( ! is_wp_error( $svcs ) && $svcs ) : ?>
				<p><strong><?php esc_html_e( 'Services:', 'shuffles-social-services-jobs' ); ?></strong> <?php echo esc_html( implode( ', ', $svcs ) ); ?></p>
			<?php endif; ?>
			<?php if ( $rmin > 0 || $rmax > 0 ) : ?>
				<p><strong><?php esc_html_e( 'Rate:', 'shuffles-social-services-jobs' ); ?></strong> 💲 <?php echo esc_html( $rmin > 0 ? number_format_i18n( $rmin ) : '' ); ?><?php echo esc_html( $rmax > 0 ? ' – ' . number_format_i18n( $rmax ) : '' ); ?> / <?php echo esc_html( $runit ? $runit : 'hour' ); ?></p>
			<?php endif; ?>
			<?php if ( $suburb || $state ) : ?>
				<p><strong><?php esc_html_e( 'Location:', 'shuffles-social-services-jobs' ); ?></strong> 📍 <?php echo esc_html( trim( $suburb . ' ' . $state ) ); ?></p>
			<?php endif; ?>
			<?php $travel = (int) get_post_meta( $worker_id, 'travel_radius_km', true ); if ( $travel > 0 ) : ?>
				<p><strong><?php esc_html_e( 'Willing to travel:', 'shuffles-social-services-jobs' ); ?></strong> <?php echo esc_html( sprintf( __( 'up to %d km', 'shuffles-social-services-jobs' ), $travel ) ); ?></p>
			<?php endif; ?>
			<?php if ( ! is_wp_error( $culture ) && $culture ) : ?>
				<p><strong><?php esc_html_e( 'Cultural / community focus:', 'shuffles-social-services-jobs' ); ?></strong> <?php echo esc_html( implode( ', ', $culture ) ); ?></p>
			<?php endif; ?>
			<?php if ( ! is_wp_error( $langs ) && $langs ) : ?>
				<p><strong><?php esc_html_e( 'Languages:', 'shuffles-social-services-jobs' ); ?></strong> <?php echo esc_html( implode( ', ', $langs ) ); ?></p>
			<?php endif; ?>
			<?php
			if ( class_exists( 'Shuffles_SSJ_Field_Registry' ) ) {
				foreach ( Shuffles_SSJ_Field_Registry::display_pairs( 'worker', $worker_id ) as $pair ) {
					echo '<p><strong>' . esc_html( $pair[0] ) . ':</strong> ' . esc_html( $pair[1] ) . '</p>';
				}
			}
			?>
		</div>

		<?php if ( $verified && ! empty( $vkinds ) && class_exists( 'Shuffles_SSJ_Credentials' ) ) : ?>
			<h3><?php esc_html_e( 'Verified checks', 'shuffles-social-services-jobs' ); ?></h3>
			<div class="sssj-row" style="gap:6px;flex-wrap:wrap">
				<?php foreach ( $vkinds as $vk ) : ?>
					<span class="sssj-badge sssj-badge--verified">✓ <?php echo esc_html( Shuffles_SSJ_Credentials::kind_label( $vk ) ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $gallery ) : ?>
		<div class="sssj-panel">
			<h3 style="margin-top:0"><?php esc_html_e( 'Photos', 'shuffles-social-services-jobs' ); ?></h3>
			<div class="sssj-gallery">
				<?php
				foreach ( $gallery as $att_id ) {
					$src = wp_get_attachment_image_url( $att_id, 'large' );
					$thumb = wp_get_attachment_image_url( $att_id, 'medium' );
					if ( ! $thumb ) {
						continue;
					}
					echo '<a class="sssj-gallery__item" href="' . esc_url( $src ? $src : $thumb ) . '" target="_blank" rel="noopener"><img src="' . esc_url( $thumb ) . '" alt="" loading="lazy" /></a>';
				}
				?>
			</div>
		</div>
	<?php endif; ?>
</div>
