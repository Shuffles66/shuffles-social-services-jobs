<?php
/**
 * Appended to a single organisation profile: contact, locations, and the org's open jobs
 * (the "browse jobs by company" view). Var: $org_id.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$org_id = isset( $org_id ) ? (int) $org_id : 0;
if ( ! $org_id ) {
	return;
}
$website = (string) get_post_meta( $org_id, 'org_website', true );
$phone   = (string) get_post_meta( $org_id, 'org_phone', true );
$type    = (string) get_post_meta( $org_id, 'org_type', true );
$p_sub   = (string) get_post_meta( $org_id, 'location_suburb', true );
$p_state = (string) get_post_meta( $org_id, 'location_state', true );
$extra   = json_decode( (string) get_post_meta( $org_id, 'locations', true ), true );
$extra   = is_array( $extra ) ? $extra : array();
?>
<div class="sssj sssj--org">
	<div class="sssj-panel">
		<?php $logo = Shuffles_SSJ_Org::logo_url( $org_id, 'medium' ); ?>
		<?php if ( $logo ) : ?><img class="sssj-org-logo" src="<?php echo esc_url( $logo ); ?>" alt="" style="margin-bottom:10px" /><?php endif; ?>
		<div class="sssj-row">
			<?php if ( $type ) : ?><span class="sssj-badge"><?php echo esc_html( ucfirst( $type ) ); ?></span><?php endif; ?>
			<?php if ( $website ) : ?><a class="sssj-btn sssj-btn--secondary sssj-btn--sm" href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener nofollow"><?php esc_html_e( 'Website', 'shuffles-social-services-jobs' ); ?></a><?php endif; ?>
			<?php if ( $phone ) : ?><span class="sssj-badge"><?php echo esc_html( $phone ); ?></span><?php endif; ?>
		</div>
		<?php echo Shuffles_SSJ_Org::social_html( $org_id ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<?php if ( $p_sub || $p_state || ! empty( $extra ) ) : ?>
			<h3><?php esc_html_e( 'Locations', 'shuffles-social-services-jobs' ); ?></h3>
			<ul class="ul-disc" style="margin-left:18px">
				<?php if ( $p_sub || $p_state ) : ?><li>📍 <?php echo esc_html( trim( $p_sub . ' ' . $p_state ) ); ?></li><?php endif; ?>
				<?php
				foreach ( $extra as $loc ) {
					$label = isset( $loc['label'] ) ? $loc['label'] : '';
					$line  = trim( ( isset( $loc['suburb'] ) ? $loc['suburb'] : '' ) . ' ' . ( isset( $loc['state'] ) ? $loc['state'] : '' ) . ' ' . ( isset( $loc['postcode'] ) ? $loc['postcode'] : '' ) );
					if ( '' === $label && '' === $line ) {
						continue;
					}
					echo '<li>📍 ' . esc_html( trim( ( $label ? $label . ' — ' : '' ) . $line ) ) . '</li>';
				}
				?>
			</ul>
		<?php endif; ?>
	</div>

	<?php
	$jobs = new WP_Query( Shuffles_SSJ_Query::base_args( '', array( 'org' => $org_id, 'posts_per_page' => 20 ) ) );
	?>
	<div class="sssj-panel">
		<h3 style="margin-top:0"><?php esc_html_e( 'Open positions', 'shuffles-social-services-jobs' ); ?></h3>
		<?php if ( $jobs->have_posts() ) : ?>
			<div class="sssj-stack">
				<?php
				while ( $jobs->have_posts() ) :
					$jobs->the_post();
					$basis = (string) get_post_meta( get_the_ID(), 'engagement_basis', true );
					?>
					<div class="sssj-row" style="justify-content:space-between;border-bottom:1px solid #e2e8f0;padding:6px 0">
						<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						<span class="sssj-badge sssj-badge--<?php echo esc_attr( 'tfn' === $basis ? 'tfn' : 'abn' ); ?>"><?php echo esc_html( 'tfn' === $basis ? __( 'TFN', 'shuffles-social-services-jobs' ) : __( 'ABN', 'shuffles-social-services-jobs' ) ); ?></span>
					</div>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'No open positions right now.', 'shuffles-social-services-jobs' ); ?></p>
		<?php endif; ?>
	</div>
</div>
