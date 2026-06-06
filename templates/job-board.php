<?php
/**
 * Job board template. Vars: $query (WP_Query), $basis (string), $atts (array).
 * Theme override: wp-content/themes/<theme>/shuffles-jobs/job-board.php
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var WP_Query $query */
/** @var string $basis */
/** @var array $atts */

$basis = isset( $basis ) ? $basis : '';
$heading = ! empty( $atts['title'] ) ? $atts['title'] : ( 'tfn' === $basis ? __( 'Employee positions', 'shuffles-social-services-jobs' ) : ( 'abn' === $basis ? __( 'Contractor & ABN engagements', 'shuffles-social-services-jobs' ) : __( 'Jobs', 'shuffles-social-services-jobs' ) ) );
$cats    = get_terms( array( 'taxonomy' => 'sssjt_category', 'hide_empty' => false ) );
$cur_cat = isset( $_GET['sssj_cat'] ) ? sanitize_title( wp_unslash( $_GET['sssj_cat'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$cur_q   = isset( $_GET['sssj_q'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$card_mod = 'tfn' === $basis ? 'sssj-card--tfn' : ( 'abn' === $basis ? 'sssj-card--abn' : '' );
$maps       = ! empty( $maps );
$has_points = ! empty( $has_points );
$cur_loc    = isset( $_GET['sssj_loc'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_loc'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$cur_lat    = isset( $_GET['sssj_lat'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_lat'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$cur_lng    = isset( $_GET['sssj_lng'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_lng'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$cur_rad    = isset( $_GET['sssj_radius'] ) ? (int) $_GET['sssj_radius'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="sssj sssj--board">
	<div class="sssj-panel">
		<h2><?php echo esc_html( $heading ); ?></h2>

		<form class="sssj-row" method="get" data-sssj-place-group>
			<?php
			// Preserve other query args (e.g. page id) implicitly via current URL.
			foreach ( array( 'sssj_paged' ) as $drop ) {
				unset( $_GET[ $drop ] ); // not re-emitted
			}
			?>
			<input class="sssj-input" type="search" name="sssj_q" value="<?php echo esc_attr( $cur_q ); ?>" placeholder="<?php esc_attr_e( 'Search jobs…', 'shuffles-social-services-jobs' ); ?>" />
			<select class="sssj-select" name="sssj_cat">
				<option value=""><?php esc_html_e( 'All categories', 'shuffles-social-services-jobs' ); ?></option>
				<?php
				if ( ! is_wp_error( $cats ) ) {
					foreach ( $cats as $t ) {
						echo '<option value="' . esc_attr( $t->slug ) . '" ' . selected( $cur_cat, $t->slug, false ) . '>' . esc_html( $t->name ) . '</option>';
					}
				}
				?>
			</select>
			<input class="sssj-input" type="text" name="sssj_loc" data-sssj-place value="<?php echo esc_attr( $cur_loc ); ?>" placeholder="<?php esc_attr_e( 'Near a suburb…', 'shuffles-social-services-jobs' ); ?>" />
			<input type="hidden" name="sssj_lat" data-sssj-lat value="<?php echo esc_attr( $cur_lat ); ?>" />
			<input type="hidden" name="sssj_lng" data-sssj-lng value="<?php echo esc_attr( $cur_lng ); ?>" />
			<label class="sssj-radius" style="display:flex;align-items:center;gap:8px">
				<span><?php esc_html_e( 'Within', 'shuffles-social-services-jobs' ); ?></span>
				<input type="range" name="sssj_radius" min="0" max="200" step="5" value="<?php echo esc_attr( $cur_rad ); ?>" oninput="this.nextElementSibling.value=(this.value==0?'<?php echo esc_js( __( 'Any', 'shuffles-social-services-jobs' ) ); ?>':this.value+' km')" />
				<output><?php echo esc_html( $cur_rad > 0 ? ( $cur_rad . ' km' ) : __( 'Any', 'shuffles-social-services-jobs' ) ); ?></output>
			</label>
			<button class="sssj-btn sssj-btn--primary" type="submit" data-i18n="filter"><?php esc_html_e( 'Filter', 'shuffles-social-services-jobs' ); ?></button>
		</form>
	</div>

	<?php if ( $maps && $has_points ) : ?>
		<div class="sssj-panel" style="padding:0;overflow:hidden;margin-bottom:16px"><div data-sssj-map style="height:360px;width:100%"></div></div>
	<?php endif; ?>

	<?php if ( $query->have_posts() ) : ?>
		<p class="sssj-count"><?php echo esc_html( sprintf( _n( '%d job', '%d jobs', (int) $query->found_posts, 'shuffles-social-services-jobs' ), (int) $query->found_posts ) ); ?></p>
		<div class="sssj-grid">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				$pid     = get_the_ID();
				$suburb  = (string) get_post_meta( $pid, 'location_suburb', true );
				$state   = (string) get_post_meta( $pid, 'location_state', true );
				$etype   = (string) get_post_meta( $pid, 'engagement_type', true );
				$rmin    = (float) get_post_meta( $pid, 'rate_min', true );
				$rmax    = (float) get_post_meta( $pid, 'rate_max', true );
				$runit   = (string) get_post_meta( $pid, 'rate_unit', true );
				$basis_m  = (string) get_post_meta( $pid, 'engagement_basis', true );
				$featured = (bool) get_post_meta( $pid, 'is_promoted', true );
				$mod      = $card_mod ? $card_mod : ( 'tfn' === $basis_m ? 'sssj-card--tfn' : 'sssj-card--abn' );
				if ( $featured ) {
					$mod .= ' sssj-card--featured';
				}
				?>
				<article class="sssj-card <?php echo esc_attr( $mod ); ?>">
					<h3 style="margin-top:0"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<div class="sssj-row">
						<?php if ( $featured ) : ?><span class="sssj-badge sssj-badge--featured" data-i18n="featured"><?php esc_html_e( '★ Featured', 'shuffles-social-services-jobs' ); ?></span><?php endif; ?>
						<span class="sssj-badge sssj-badge--<?php echo esc_attr( 'tfn' === $basis_m ? 'tfn' : 'abn' ); ?>"><?php echo esc_html( 'tfn' === $basis_m ? __( 'TFN (employee)', 'shuffles-social-services-jobs' ) : __( 'ABN (contractor)', 'shuffles-social-services-jobs' ) ); ?></span>
						<?php if ( $etype ) : ?><span class="sssj-badge"><?php echo esc_html( 'one-off' === $etype ? __( 'One-off', 'shuffles-social-services-jobs' ) : __( 'Ongoing', 'shuffles-social-services-jobs' ) ); ?></span><?php endif; ?>
					</div>
					<?php if ( $suburb || $state ) : ?>
						<p>📍 <?php echo esc_html( trim( $suburb . ' ' . $state ) ); ?></p>
					<?php endif; ?>
					<?php if ( $rmin > 0 || $rmax > 0 ) : ?>
						<p>💲 <?php echo esc_html( $rmin > 0 ? number_format_i18n( $rmin ) : '' ); ?><?php echo esc_html( $rmax > 0 ? ' – ' . number_format_i18n( $rmax ) : '' ); ?> / <?php echo esc_html( $runit ? $runit : 'hour' ); ?></p>
					<?php endif; ?>
					<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 24 ) ); ?></p>
					<a class="sssj-btn sssj-btn--secondary sssj-btn--sm" href="<?php the_permalink(); ?>" data-i18n="view_job"><?php esc_html_e( 'View job', 'shuffles-social-services-jobs' ); ?></a>
				</article>
			<?php endwhile; ?>
		</div>

		<?php
		$total = (int) $query->max_num_pages;
		$cur   = isset( $_GET['sssj_paged'] ) ? max( 1, (int) $_GET['sssj_paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $total > 1 ) :
			?>
			<div class="sssj-row" style="margin-top:16px">
				<?php if ( $cur > 1 ) : ?>
					<a class="sssj-btn sssj-btn--ghost sssj-btn--sm" href="<?php echo esc_url( add_query_arg( 'sssj_paged', $cur - 1 ) ); ?>">&laquo; <?php esc_html_e( 'Previous', 'shuffles-social-services-jobs' ); ?></a>
				<?php endif; ?>
				<span class="sssj-badge"><?php echo esc_html( sprintf( __( 'Page %1$d of %2$d', 'shuffles-social-services-jobs' ), $cur, $total ) ); ?></span>
				<?php if ( $cur < $total ) : ?>
					<a class="sssj-btn sssj-btn--ghost sssj-btn--sm" href="<?php echo esc_url( add_query_arg( 'sssj_paged', $cur + 1 ) ); ?>"><?php esc_html_e( 'Next', 'shuffles-social-services-jobs' ); ?> &raquo;</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

	<?php else : ?>
		<div class="sssj-panel"><p><?php esc_html_e( 'No jobs found. Try widening your search.', 'shuffles-social-services-jobs' ); ?></p></div>
	<?php endif; ?>
</div>
