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
$cur_cats = isset( $_GET['sssj_cat'] ) ? array_filter( array_map( 'sanitize_title', (array) wp_unslash( $_GET['sssj_cat'] ) ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$cur_q   = isset( $_GET['sssj_q'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$card_mod = 'tfn' === $basis ? 'sssj-card--tfn' : ( 'abn' === $basis ? 'sssj-card--abn' : '' );
$maps       = ! empty( $maps );
$has_points = ! empty( $has_points );
$cur_loc    = isset( $_GET['sssj_loc'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_loc'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$cur_lat    = isset( $_GET['sssj_lat'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_lat'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$cur_lng    = isset( $_GET['sssj_lng'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_lng'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$cur_rad    = isset( $_GET['sssj_radius'] ) ? (int) $_GET['sssj_radius'] : Shuffles_SSJ_Shortcodes::default_travel_radius(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="sssj sssj--board">
	<div class="sssj-panel">
		<h2><?php echo esc_html( $heading ); ?></h2>
			<?php Shuffles_SSJ_Shortcodes::render_readme( 'jobs' ); ?>

		<form class="sssj-row" method="get" data-sssj-place-group data-sssj-filter-form data-sssj-board="<?php echo esc_attr( $basis ? $basis : 'job' ); ?>">
			<?php
			// Preserve other query args (e.g. page id) implicitly via current URL.
			foreach ( array( 'sssj_paged' ) as $drop ) {
				unset( $_GET[ $drop ] ); // not re-emitted
			}
			?>
			<input class="sssj-input" type="search" name="sssj_q" value="<?php echo esc_attr( $cur_q ); ?>" placeholder="<?php esc_attr_e( 'Search jobs…', 'shuffles-social-services-jobs' ); ?>" />
			<?php // Location group sits to the right of the search field on desktop; categories/funding drop to the next row. ?>
			<input class="sssj-input" type="text" name="sssj_loc" data-sssj-place value="<?php echo esc_attr( $cur_loc ); ?>" placeholder="<?php esc_attr_e( 'Near a suburb…', 'shuffles-social-services-jobs' ); ?>" data-i18n-placeholder="ph_near" />
			<input type="hidden" name="sssj_lat" data-sssj-lat value="<?php echo esc_attr( $cur_lat ); ?>" />
			<input type="hidden" name="sssj_lng" data-sssj-lng value="<?php echo esc_attr( $cur_lng ); ?>" />
			<label class="sssj-radius" style="display:flex;align-items:center;gap:8px">
				<span data-i18n="within"><?php esc_html_e( 'Within', 'shuffles-social-services-jobs' ); ?></span>
				<input type="range" name="sssj_radius" min="0" max="200" step="5" value="<?php echo esc_attr( $cur_rad ); ?>" oninput="this.nextElementSibling.value=(this.value==0?'<?php echo esc_js( __( 'Any', 'shuffles-social-services-jobs' ) ); ?>':this.value+' km')" />
				<output><?php echo esc_html( $cur_rad > 0 ? ( $cur_rad . ' km' ) : __( 'Any', 'shuffles-social-services-jobs' ) ); ?></output>
			</label>
			<?php Shuffles_SSJ_Shortcodes::location_button(); ?>
			<div class="sssj-break" aria-hidden="true"></div>
			<select class="sssj-select" name="sssj_cat[]" multiple data-placeholder="<?php esc_attr_e( 'All categories', 'shuffles-social-services-jobs' ); ?>">
				<?php
				if ( ! is_wp_error( $cats ) ) {
					foreach ( $cats as $t ) {
						echo '<option value="' . esc_attr( $t->slug ) . '" ' . ( in_array( $t->slug, $cur_cats, true ) ? 'selected' : '' ) . '>' . esc_html( $t->name ) . '</option>';
					}
				}
				?>
			</select>
			<?php Shuffles_SSJ_Shortcodes::funding_chips(); ?>
			<?php Shuffles_SSJ_Shortcodes::filter_actions(); ?>
		</form>
			<?php if ( class_exists( 'Shuffles_SSJ_Alerts' ) ) { Shuffles_SSJ_Alerts::save_search_button( 'jobs' ); } ?>
	</div>

	<?php if ( $maps && $has_points ) : ?>
		<div class="sssj-panel sssj-map-panel" style="padding:0;overflow:hidden"><div data-sssj-map style="height:320px;width:100%"></div></div>
	<?php endif; ?>

	<div class="sssj-results" data-sssj-results>
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
				<article class="sssj-card <?php echo esc_attr( $mod ); ?>" data-sssj-id="<?php echo esc_attr( $pid ); ?>">
					<?php $anon = (bool) get_post_meta( $pid, 'is_anonymous', true ); ?>
					<div class="sssj-row" style="gap:10px;flex-wrap:nowrap;align-items:flex-start">
						<?php $job_logo = ( ! $anon && class_exists( 'Shuffles_SSJ_Org' ) ) ? Shuffles_SSJ_Org::job_logo_url( $pid, 'thumbnail' ) : ''; ?>
						<?php if ( $job_logo ) : ?><img class="sssj-org-logo" src="<?php echo esc_url( $job_logo ); ?>" alt="" /><?php endif; ?>
						<h3 style="margin:0"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a> <?php if ( ! $anon ) { $job_org = (int) get_post_meta( $pid, 'organisation_id', true ); if ( $job_org ) { echo Shuffles_SSJ_Verification::tick_html( $job_org, false ); } } // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
					</div>
					<div class="sssj-row">
						<?php echo Shuffles_SSJ_Shortcodes::distance_pill( $pid, isset( $center ) ? $center : null ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php if ( $featured ) : ?><span class="sssj-badge sssj-badge--featured" data-i18n="featured"><?php esc_html_e( '★ Featured', 'shuffles-social-services-jobs' ); ?></span><?php endif; ?>
						<span class="sssj-badge sssj-badge--<?php echo esc_attr( Shuffles_SSJ_Query::basis_class( $basis_m ) ); ?>"><?php echo esc_html( Shuffles_SSJ_Query::basis_label( $basis_m ) ); ?></span>
						<?php if ( $etype ) : ?><span class="sssj-badge"><?php echo esc_html( 'one-off' === $etype ? __( 'One-off', 'shuffles-social-services-jobs' ) : __( 'Ongoing', 'shuffles-social-services-jobs' ) ); ?></span><?php endif; ?>
						<?php if ( ! $anon ) { echo Shuffles_SSJ_ABN::abr_badge_html( $pid ); } // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php if ( $anon ) : ?><span class="sssj-badge sssj-badge--anon" title="<?php esc_attr_e( 'The advertiser has chosen to remain anonymous', 'shuffles-social-services-jobs' ); ?>">🕶️ <?php esc_html_e( 'Anonymous', 'shuffles-social-services-jobs' ); ?></span><?php endif; ?>
						<?php echo Shuffles_SSJ_Shortcodes::openness_badges( $pid ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
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
</div>
