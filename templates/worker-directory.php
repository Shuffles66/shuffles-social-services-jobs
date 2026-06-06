<?php
/**
 * Worker directory template. Vars: $query (WP_Query), $atts (array).
 * Visibility is already enforced in Shuffles_SSJ_Query::worker_args().
 * Theme override: wp-content/themes/<theme>/shuffles-jobs/worker-directory.php
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var WP_Query $query */
/** @var array $atts */

$heading = ! empty( $atts['title'] ) ? $atts['title'] : __( 'Available workers', 'shuffles-social-services-jobs' );
$cats    = get_terms( array( 'taxonomy' => 'sssjt_category', 'hide_empty' => false ) );
$cur_cats = isset( $_GET['sssj_cat'] ) ? array_filter( array_map( 'sanitize_title', (array) wp_unslash( $_GET['sssj_cat'] ) ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$cur_q   = isset( $_GET['sssj_q'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$avail   = ! empty( $_GET['sssj_avail'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$cur_loc = isset( $_GET['sssj_loc'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_loc'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$cur_lat = isset( $_GET['sssj_lat'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_lat'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$cur_lng = isset( $_GET['sssj_lng'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_lng'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$cur_rad = isset( $_GET['sssj_radius'] ) ? (int) $_GET['sssj_radius'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$w_maps  = ! empty( $maps );
$w_pts   = ! empty( $has_points );
?>
<div class="sssj sssj--workers">
	<div class="sssj-panel">
		<h2<?php echo empty( $atts['title'] ) ? ' data-i18n="d_workers"' : ''; ?>><?php echo esc_html( $heading ); ?></h2>
		<form class="sssj-row" method="get" data-sssj-place-group>
			<input class="sssj-input" type="search" name="sssj_q" value="<?php echo esc_attr( $cur_q ); ?>" placeholder="<?php esc_attr_e( 'Search workers…', 'shuffles-social-services-jobs' ); ?>" data-i18n-placeholder="ph_workers" />
			<select class="sssj-select" name="sssj_cat[]" multiple data-placeholder="<?php esc_attr_e( 'All services', 'shuffles-social-services-jobs' ); ?>">
				<?php
				if ( ! is_wp_error( $cats ) ) {
					foreach ( $cats as $t ) {
						echo '<option value="' . esc_attr( $t->slug ) . '" ' . ( in_array( $t->slug, $cur_cats, true ) ? 'selected' : '' ) . '>' . esc_html( $t->name ) . '</option>';
					}
				}
				?>
			</select>
			<input class="sssj-input" type="text" name="sssj_loc" data-sssj-place value="<?php echo esc_attr( $cur_loc ); ?>" placeholder="<?php esc_attr_e( 'Near a suburb…', 'shuffles-social-services-jobs' ); ?>" data-i18n-placeholder="ph_near" />
			<input type="hidden" name="sssj_lat" data-sssj-lat value="<?php echo esc_attr( $cur_lat ); ?>" />
			<input type="hidden" name="sssj_lng" data-sssj-lng value="<?php echo esc_attr( $cur_lng ); ?>" />
			<label class="sssj-radius" style="display:flex;align-items:center;gap:8px">
				<span data-i18n="within"><?php esc_html_e( 'Within', 'shuffles-social-services-jobs' ); ?></span>
				<input type="range" name="sssj_radius" min="0" max="200" step="5" value="<?php echo esc_attr( $cur_rad ); ?>" oninput="this.nextElementSibling.value=(this.value==0?'<?php echo esc_js( __( 'Any', 'shuffles-social-services-jobs' ) ); ?>':this.value+' km')" />
				<output><?php echo esc_html( $cur_rad > 0 ? ( $cur_rad . ' km' ) : __( 'Any', 'shuffles-social-services-jobs' ) ); ?></output>
			</label>
			<label class="sssj-chip <?php echo $avail ? 'is-on' : ''; ?>"><input type="checkbox" name="sssj_avail" value="1" <?php checked( $avail ); ?> /> <span data-i18n="avail_now"><?php esc_html_e( 'Available now', 'shuffles-social-services-jobs' ); ?></span></label>
			<button class="sssj-btn sssj-btn--primary" type="submit" data-i18n="filter"><?php esc_html_e( 'Filter', 'shuffles-social-services-jobs' ); ?></button>
		</form>
	</div>

	<?php if ( $w_maps && $w_pts ) : ?>
		<div class="sssj-panel sssj-map-panel" style="padding:0;overflow:hidden"><div data-sssj-map style="height:320px;width:100%"></div></div>
	<?php endif; ?>

	<?php if ( $query->have_posts() ) : ?>
		<div class="sssj-grid">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				$pid    = get_the_ID();
				$avail2 = (string) get_post_meta( $pid, 'is_available', true );
				$status = (string) get_post_meta( $pid, 'employment_status', true );
				$yrs    = (int) get_post_meta( $pid, 'years_experience', true );
				$rmin   = (float) get_post_meta( $pid, 'rate_min', true );
				$runit  = (string) get_post_meta( $pid, 'rate_unit', true );
				$verified = (string) get_post_meta( $pid, 'verified_at', true );
				$vkinds   = (array) get_post_meta( $pid, 'verified_kinds', true );
				$svcs   = wp_get_post_terms( $pid, 'sssjt_category', array( 'fields' => 'names' ) );
				?>
				<article class="sssj-card">
					<h3 style="margin-top:0"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<div class="sssj-row">
						<?php if ( '1' === $avail2 ) : ?><span class="sssj-badge sssj-badge--verified"><?php esc_html_e( 'Available', 'shuffles-social-services-jobs' ); ?></span><?php endif; ?>
						<?php if ( $verified ) : ?><span class="sssj-badge sssj-badge--verified">✓ <?php esc_html_e( 'Verified', 'shuffles-social-services-jobs' ); ?></span><?php endif; ?>
						<?php if ( $yrs > 0 ) : ?><span class="sssj-badge"><?php echo esc_html( sprintf( _n( '%d yr', '%d yrs', $yrs, 'shuffles-social-services-jobs' ), $yrs ) ); ?></span><?php endif; ?>
					</div>
					<?php if ( $verified && ! empty( $vkinds ) && class_exists( 'Shuffles_SSJ_Credentials' ) ) : ?>
						<p class="description" style="margin:4px 0">✓ <?php
						$names = array();
						foreach ( array_slice( $vkinds, 0, 4 ) as $vk ) { $names[] = Shuffles_SSJ_Credentials::kind_label( $vk ); }
						echo esc_html( implode( ' · ', $names ) );
						?></p>
					<?php endif; ?>
					<?php if ( ! is_wp_error( $svcs ) && ! empty( $svcs ) ) : ?>
						<p><?php echo esc_html( implode( ', ', array_slice( $svcs, 0, 4 ) ) ); ?></p>
					<?php endif; ?>
					<?php if ( $rmin > 0 ) : ?><p>💲 <?php echo esc_html( __( 'from', 'shuffles-social-services-jobs' ) . ' ' . number_format_i18n( $rmin ) . ' / ' . ( $runit ? $runit : 'hour' ) ); ?></p><?php endif; ?>
					<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 22 ) ); ?></p>
					<a class="sssj-btn sssj-btn--secondary sssj-btn--sm" href="<?php the_permalink(); ?>" data-i18n="view_profile"><?php esc_html_e( 'View profile', 'shuffles-social-services-jobs' ); ?></a>
				</article>
			<?php endwhile; ?>
		</div>
		<?php
		$total = (int) $query->max_num_pages;
		$cur   = isset( $_GET['sssj_paged'] ) ? max( 1, (int) $_GET['sssj_paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $total > 1 ) :
			?>
			<div class="sssj-row" style="margin-top:16px">
				<?php if ( $cur > 1 ) : ?><a class="sssj-btn sssj-btn--ghost sssj-btn--sm" href="<?php echo esc_url( add_query_arg( 'sssj_paged', $cur - 1 ) ); ?>">&laquo; <?php esc_html_e( 'Previous', 'shuffles-social-services-jobs' ); ?></a><?php endif; ?>
				<span class="sssj-badge"><?php echo esc_html( sprintf( __( 'Page %1$d of %2$d', 'shuffles-social-services-jobs' ), $cur, $total ) ); ?></span>
				<?php if ( $cur < $total ) : ?><a class="sssj-btn sssj-btn--ghost sssj-btn--sm" href="<?php echo esc_url( add_query_arg( 'sssj_paged', $cur + 1 ) ); ?>"><?php esc_html_e( 'Next', 'shuffles-social-services-jobs' ); ?> &raquo;</a><?php endif; ?>
			</div>
		<?php endif; ?>
	<?php else : ?>
		<div class="sssj-panel"><p>
			<?php
			echo is_user_logged_in()
				? esc_html__( 'No worker profiles match yet.', 'shuffles-social-services-jobs' )
				: esc_html__( 'Log in to see more worker profiles.', 'shuffles-social-services-jobs' );
			?>
		</p></div>
	<?php endif; ?>
</div>
