<?php
/**
 * Job board template. Vars: $query (WP_Query), $basis (string), $atts (array), $locked_basis (bool),
 * $sort (string), $maps (bool), $has_points (bool), $center (array|null).
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

$basis        = isset( $basis ) ? $basis : '';
$locked_basis = ! empty( $locked_basis );
$sort         = isset( $sort ) && $sort ? $sort : 'newest';
$heading      = ! empty( $atts['title'] ) ? $atts['title'] : ( 'tfn' === $basis ? __( 'Employee positions', 'shuffles-social-services-jobs' ) : ( 'abn' === $basis ? __( 'Contractor & ABN engagements', 'shuffles-social-services-jobs' ) : ( 'vol' === $basis ? __( 'Volunteer opportunities', 'shuffles-social-services-jobs' ) : __( 'Jobs', 'shuffles-social-services-jobs' ) ) ) );
$cats         = get_terms( array( 'taxonomy' => 'sssjt_category', 'hide_empty' => false ) );
$cur_cats     = isset( $_GET['sssj_cat'] ) ? array_filter( array_map( 'sanitize_title', (array) wp_unslash( $_GET['sssj_cat'] ) ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$cur_q        = isset( $_GET['sssj_q'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$card_mod     = 'tfn' === $basis ? 'sssj-card--tfn' : ( 'abn' === $basis ? 'sssj-card--abn' : '' );
$maps         = ! empty( $maps );
$has_points   = ! empty( $has_points );
$cur_loc      = isset( $_GET['sssj_loc'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_loc'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$cur_lat      = isset( $_GET['sssj_lat'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_lat'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$cur_lng      = isset( $_GET['sssj_lng'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_lng'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$cur_rad      = isset( $_GET['sssj_radius'] ) ? (int) $_GET['sssj_radius'] : Shuffles_SSJ_Shortcodes::default_travel_radius(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$found        = (int) $query->found_posts;

// Inline-SVG icons (replace emoji, crisp at any size / colour).
$svg = function ( $paths, $cls = 'sssj-i' ) {
	return '<svg class="' . esc_attr( $cls ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths . '</svg>';
};
$I_PIN    = '<path d="M12 21s7-5.5 7-11a7 7 0 10-14 0c0 5.5 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>';
$I_DOLLAR = '<path d="M12 3v18M16 7c0-2-2-3-4-3s-4 1-4 3 2 3 4 3 4 1 4 3-2 3-4 3-4-1-4-3"/>';
$I_GRID   = '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>';
$I_LIST   = '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/>';

$basis_toggle = array(
	''    => __( 'All', 'shuffles-social-services-jobs' ),
	'tfn' => __( 'Employee (TFN)', 'shuffles-social-services-jobs' ),
	'abn' => __( 'Contractor (ABN)', 'shuffles-social-services-jobs' ),
	'vol' => __( 'Volunteer', 'shuffles-social-services-jobs' ),
);
$sort_opts = array(
	'newest' => __( 'Newest', 'shuffles-social-services-jobs' ),
	'oldest' => __( 'Oldest', 'shuffles-social-services-jobs' ),
	'az'     => __( 'Title A to Z', 'shuffles-social-services-jobs' ),
);
?>
<div class="sssj sssj--board">
	<div class="sssj-panel sssj-board__filters" data-sssj-sticky>
		<h2><?php echo esc_html( $heading ); ?></h2>
			<?php Shuffles_SSJ_Shortcodes::render_readme( 'jobs' ); ?>

		<?php if ( ! $locked_basis ) : ?>
			<div class="sssj-segmented" role="group" aria-label="<?php esc_attr_e( 'Engagement type', 'shuffles-social-services-jobs' ); ?>">
				<?php
				foreach ( $basis_toggle as $bk => $bl ) {
					$on = ( $bk === $basis );
					echo '<button type="button" class="sssj-seg__btn' . ( $on ? ' is-on' : '' ) . '" data-sssj-basis-pick="' . esc_attr( $bk ? $bk : 'all' ) . '" aria-pressed="' . ( $on ? 'true' : 'false' ) . '">' . esc_html( $bl ) . '</button>';
				}
				?>
			</div>
		<?php endif; ?>

		<form class="sssj-row" method="get" data-sssj-place-group data-sssj-filter-form data-sssj-board="<?php echo esc_attr( $locked_basis ? $basis : 'job' ); ?>">
			<?php
			foreach ( array( 'sssj_paged' ) as $drop ) {
				unset( $_GET[ $drop ] );
			}
			?>
			<input type="hidden" name="sssj_basis" data-sssj-basis value="<?php echo esc_attr( $basis ); ?>" />
			<input class="sssj-input" type="search" name="sssj_q" value="<?php echo esc_attr( $cur_q ); ?>" placeholder="<?php esc_attr_e( 'Search jobs…', 'shuffles-social-services-jobs' ); ?>" />
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
			<label class="sssj-sort">
				<span class="screen-reader-text"><?php esc_html_e( 'Sort', 'shuffles-social-services-jobs' ); ?></span>
				<select class="sssj-select" name="sssj_sort" data-no-enhance aria-label="<?php esc_attr_e( 'Sort jobs', 'shuffles-social-services-jobs' ); ?>">
					<?php
					foreach ( $sort_opts as $sk => $sl ) {
						echo '<option value="' . esc_attr( $sk ) . '" ' . selected( $sort, $sk, false ) . '>' . esc_html( sprintf( __( 'Sort: %s', 'shuffles-social-services-jobs' ), $sl ) ) . '</option>';
					}
					?>
				</select>
			</label>
			<?php Shuffles_SSJ_Shortcodes::filter_actions(); ?>
		</form>
		<div class="sssj-chips" data-sssj-chips></div>
			<?php if ( class_exists( 'Shuffles_SSJ_Alerts' ) ) { Shuffles_SSJ_Alerts::save_search_button( 'jobs' ); } ?>
	</div>

	<?php if ( $maps && $has_points ) : ?>
		<div class="sssj-panel sssj-map-panel" style="padding:0;overflow:hidden"><div data-sssj-map style="height:320px;width:100%"></div></div>
	<?php endif; ?>

	<div class="sssj-board__head">
		<p class="sssj-count" data-sssj-count role="status" aria-live="polite" data-one="<?php esc_attr_e( '%d job', 'shuffles-social-services-jobs' ); ?>" data-many="<?php esc_attr_e( '%d jobs', 'shuffles-social-services-jobs' ); ?>"><?php echo esc_html( sprintf( _n( '%d job', '%d jobs', $found, 'shuffles-social-services-jobs' ), $found ) ); ?></p>
		<div class="sssj-viewtoggle" role="group" aria-label="<?php esc_attr_e( 'Results layout', 'shuffles-social-services-jobs' ); ?>">
			<button type="button" class="sssj-view__btn is-on" data-sssj-view="grid" aria-pressed="true" title="<?php esc_attr_e( 'Grid view', 'shuffles-social-services-jobs' ); ?>"><?php echo $svg( $I_GRID ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<button type="button" class="sssj-view__btn" data-sssj-view="list" aria-pressed="false" title="<?php esc_attr_e( 'List view', 'shuffles-social-services-jobs' ); ?>"><?php echo $svg( $I_LIST ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		</div>
	</div>

	<div class="sssj-results" data-sssj-results>
	<?php if ( $query->have_posts() ) : ?>
		<span data-sssj-found="<?php echo (int) $found; ?>" hidden></span>
		<div class="sssj-grid" data-sssj-grid>
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				$pid      = get_the_ID();
				$suburb   = (string) get_post_meta( $pid, 'location_suburb', true );
				$state    = (string) get_post_meta( $pid, 'location_state', true );
				$etype    = (string) get_post_meta( $pid, 'engagement_type', true );
				$rmin     = (float) get_post_meta( $pid, 'rate_min', true );
				$rmax     = (float) get_post_meta( $pid, 'rate_max', true );
				$runit    = (string) get_post_meta( $pid, 'rate_unit', true );
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
						<?php if ( $anon ) : ?><span class="sssj-badge sssj-badge--anon" title="<?php esc_attr_e( 'The advertiser has chosen to remain anonymous', 'shuffles-social-services-jobs' ); ?>"><?php esc_html_e( 'Anonymous', 'shuffles-social-services-jobs' ); ?></span><?php endif; ?>
						<?php echo Shuffles_SSJ_Shortcodes::openness_badges( $pid ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
					<?php if ( $suburb || $state ) : ?>
						<p class="sssj-card__line"><?php echo $svg( $I_PIN ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( trim( $suburb . ' ' . $state ) ); ?></p>
					<?php endif; ?>
					<?php if ( $rmin > 0 || $rmax > 0 ) : ?>
						<p class="sssj-card__line"><?php echo $svg( $I_DOLLAR ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( $rmin > 0 ? number_format_i18n( $rmin ) : '' ); ?><?php echo esc_html( $rmax > 0 ? ' – ' . number_format_i18n( $rmax ) : '' ); ?> / <?php echo esc_html( $runit ? $runit : 'hour' ); ?></p>
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
		<span data-sssj-found="0" hidden></span>
		<div class="sssj-empty">
			<p class="sssj-empty__title"><?php esc_html_e( 'No jobs match your filters yet.', 'shuffles-social-services-jobs' ); ?></p>
			<p class="sssj-empty__sub"><?php esc_html_e( 'Try widening the distance, clearing a filter, or saving this search so we can alert you when something matches.', 'shuffles-social-services-jobs' ); ?></p>
			<div class="sssj-row" style="justify-content:center;gap:8px;flex-wrap:wrap">
				<button type="button" class="sssj-btn sssj-btn--secondary sssj-btn--sm" data-sssj-clear><?php esc_html_e( 'Clear all filters', 'shuffles-social-services-jobs' ); ?></button>
				<?php
				$post_job = Shuffles_SSJ_Shortcodes::page_link( 'page_post_job', '[sssj_post_job]' );
				if ( $post_job ) {
					echo '<a class="sssj-btn sssj-btn--ghost sssj-btn--sm" href="' . esc_url( $post_job ) . '">' . esc_html__( 'Post a job', 'shuffles-social-services-jobs' ) . '</a>';
				}
				?>
			</div>
		</div>
	<?php endif; ?>
	</div>
</div>
