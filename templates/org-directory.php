<?php
/**
 * Organisation directory. Var: $query (WP_Query of sssj_org). Public + SEO-able.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var WP_Query $query */
// phpcs:disable WordPress.Security.NonceVerification.Recommended
$cur_q   = isset( $_GET['sssj_q'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_q'] ) ) : '';
$cur_loc = isset( $_GET['sssj_loc'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_loc'] ) ) : '';
$cur_lat = isset( $_GET['sssj_lat'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_lat'] ) ) : '';
$cur_lng = isset( $_GET['sssj_lng'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_lng'] ) ) : '';
$cur_rad = isset( $_GET['sssj_radius'] ) ? (int) $_GET['sssj_radius'] : 0;
$maps    = ! empty( $maps );
$has_pts = ! empty( $has_points );
$rad_lbl = $cur_rad > 0 ? ( $cur_rad . ' km' ) : __( 'Any', 'shuffles-social-services-jobs' );
$cur_sector  = isset( $_GET['sssj_sector'] ) ? sanitize_title( wp_unslash( $_GET['sssj_sector'] ) ) : '';
$cur_funding = isset( $_GET['sssj_funding'] ) ? sanitize_title( wp_unslash( $_GET['sssj_funding'] ) ) : '';
$cur_open    = ! empty( $_GET['sssj_open'] );
$cur_orgcat  = isset( $_GET['sssj_orgcat'] ) ? sanitize_key( wp_unslash( $_GET['sssj_orgcat'] ) ) : '';
$o_cats      = class_exists( 'Shuffles_SSJ_Org' ) ? Shuffles_SSJ_Org::categories() : array();
$o_sectors   = get_terms( array( 'taxonomy' => 'sssjt_category', 'hide_empty' => false ) );
$o_fundings  = get_terms( array( 'taxonomy' => 'sssjt_funding_source', 'hide_empty' => false ) );
?>
<div class="sssj sssj--orgs">
	<div class="sssj-panel">
		<h2 data-i18n="d_orgs"><?php esc_html_e( 'Organisations', 'shuffles-social-services-jobs' ); ?></h2>
			<?php Shuffles_SSJ_Shortcodes::render_readme( 'orgs' ); ?>
		<form class="sssj-row" method="get" data-sssj-place-group data-sssj-filter-form data-sssj-board="org">
			<input class="sssj-input" type="search" name="sssj_q" value="<?php echo esc_attr( $cur_q ); ?>" placeholder="<?php esc_attr_e( 'Search by company name…', 'shuffles-social-services-jobs' ); ?>" />
			<div class="sssj-break" aria-hidden="true"></div>
			<input class="sssj-input" type="text" name="sssj_loc" data-sssj-place value="<?php echo esc_attr( $cur_loc ); ?>" placeholder="<?php esc_attr_e( 'Near a suburb…', 'shuffles-social-services-jobs' ); ?>" data-i18n-placeholder="ph_near" />
			<input type="hidden" name="sssj_lat" data-sssj-lat value="<?php echo esc_attr( $cur_lat ); ?>" />
			<input type="hidden" name="sssj_lng" data-sssj-lng value="<?php echo esc_attr( $cur_lng ); ?>" />
			<label class="sssj-radius" style="display:flex;align-items:center;gap:8px">
				<span data-i18n="within"><?php esc_html_e( 'Within', 'shuffles-social-services-jobs' ); ?></span>
				<input type="range" name="sssj_radius" min="0" max="200" step="5" value="<?php echo esc_attr( $cur_rad ); ?>" oninput="this.nextElementSibling.value=(this.value==0?'<?php echo esc_js( __( 'Any', 'shuffles-social-services-jobs' ) ); ?>':this.value+' km')" />
				<output><?php echo esc_html( $rad_lbl ); ?></output>
			</label>
			<?php Shuffles_SSJ_Shortcodes::location_button(); ?>
				<select class="sssj-select" name="sssj_sector">
					<option value=""><?php esc_html_e( 'All sectors', 'shuffles-social-services-jobs' ); ?></option>
					<?php if ( ! is_wp_error( $o_sectors ) ) { foreach ( $o_sectors as $t ) { echo '<option value="' . esc_attr( $t->slug ) . '" ' . selected( $cur_sector, $t->slug, false ) . '>' . esc_html( $t->name ) . '</option>'; } } ?>
				</select>
				<select class="sssj-select" name="sssj_funding">
					<option value=""><?php esc_html_e( 'All funding', 'shuffles-social-services-jobs' ); ?></option>
					<?php if ( ! is_wp_error( $o_fundings ) ) { foreach ( $o_fundings as $t ) { echo '<option value="' . esc_attr( $t->slug ) . '" ' . selected( $cur_funding, $t->slug, false ) . '>' . esc_html( $t->name ) . '</option>'; } } ?>
				</select>
				<?php if ( $o_cats ) : ?>
				<select class="sssj-select" name="sssj_orgcat">
					<option value=""><?php esc_html_e( 'All organisation types', 'shuffles-social-services-jobs' ); ?></option>
					<?php foreach ( $o_cats as $ck => $cl ) { echo '<option value="' . esc_attr( $ck ) . '" ' . selected( $cur_orgcat, $ck, false ) . '>' . esc_html( $cl ) . '</option>'; } ?>
				</select>
				<?php endif; ?>
				<?php $cur_size = isset( $_GET['sssj_size'] ) ? sanitize_key( wp_unslash( $_GET['sssj_size'] ) ) : ''; ?>
				<select class="sssj-select" name="sssj_size">
					<option value=""><?php esc_html_e( 'Any size', 'shuffles-social-services-jobs' ); ?></option>
					<?php if ( class_exists( 'Shuffles_SSJ_Org' ) ) { foreach ( Shuffles_SSJ_Org::sizes() as $sk => $sl ) { echo '<option value="' . esc_attr( $sk ) . '" ' . selected( $cur_size, $sk, false ) . '>' . esc_html( $sl ) . '</option>'; } } ?>
				</select>
				<?php $cur_struct = isset( $_GET['sssj_structure'] ) ? sanitize_key( wp_unslash( $_GET['sssj_structure'] ) ) : ''; ?>
				<select class="sssj-select" name="sssj_structure">
					<option value=""><?php esc_html_e( 'Any structure', 'shuffles-social-services-jobs' ); ?></option>
					<?php if ( class_exists( 'Shuffles_SSJ_Org' ) ) { foreach ( Shuffles_SSJ_Org::structures() as $sk => $sl ) { echo '<option value="' . esc_attr( $sk ) . '" ' . selected( $cur_struct, $sk, false ) . '>' . esc_html( $sl ) . '</option>'; } } ?>
				</select>
				<label class="sssj-chip <?php echo $cur_open ? 'is-on' : ''; ?>"><input type="checkbox" name="sssj_open" value="1" <?php checked( $cur_open ); ?> /> <span data-i18n="open_only"><?php esc_html_e( 'Only with open placements', 'shuffles-social-services-jobs' ); ?></span></label>
			<?php Shuffles_SSJ_Field_Registry::render_banner_filters( 'org' ); Shuffles_SSJ_Shortcodes::filter_actions(); ?>
		</form>
		<?php if ( class_exists( 'Shuffles_SSJ_Alerts' ) ) { Shuffles_SSJ_Alerts::save_search_button( 'orgs' ); } ?>
	</div>

	<?php if ( $maps && $has_pts ) : ?>
		<div class="sssj-panel sssj-map-panel" style="padding:0;overflow:hidden"><div data-sssj-map style="height:320px;width:100%"></div></div>
	<?php endif; ?>

	<div class="sssj-results" data-sssj-results>
	<?php if ( $query->have_posts() ) : ?>
		<div class="sssj-grid">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				$oid   = get_the_ID();
				$type  = (string) get_post_meta( $oid, 'org_type', true );
				$sub   = (string) get_post_meta( $oid, 'location_suburb', true );
				$state = (string) get_post_meta( $oid, 'location_state', true );
				$extra = json_decode( (string) get_post_meta( $oid, 'locations', true ), true );
				$locn  = ( is_array( $extra ) ? count( $extra ) : 0 ) + ( ( $sub || $state ) ? 1 : 0 );
				?>
				<article class="sssj-card" data-sssj-id="<?php echo esc_attr( $oid ); ?>">
					<div class="sssj-row" style="gap:10px;flex-wrap:nowrap;align-items:flex-start">
						<?php $logo = Shuffles_SSJ_Org::logo_url( $oid, 'thumbnail' ); ?>
						<?php if ( $logo ) : ?><img class="sssj-org-logo" src="<?php echo esc_url( $logo ); ?>" alt="" /><?php endif; ?>
						<h3 style="margin:0"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a> <?php echo Shuffles_SSJ_Verification::tick_html( $oid, false ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
					</div>
					<div class="sssj-row">
						<?php echo Shuffles_SSJ_Shortcodes::distance_pill( $oid, isset( $center ) ? $center : null, true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php if ( Shuffles_SSJ_Org::is_sponsored( $oid ) ) : ?><span class="sssj-badge sssj-badge--featured" title="<?php esc_attr_e( 'Sponsored listing', 'shuffles-social-services-jobs' ); ?>">★ <?php esc_html_e( 'Sponsored', 'shuffles-social-services-jobs' ); ?></span><?php endif; ?>
						<?php $o_cat = Shuffles_SSJ_Org::category_label( get_post_meta( $oid, 'org_category', true ) ); ?>
						<?php if ( $o_cat ) : ?><span class="sssj-badge"><?php echo esc_html( $o_cat ); ?></span><?php endif; ?>
						<?php $o_size = Shuffles_SSJ_Org::size_label( get_post_meta( $oid, 'org_size', true ) ); ?>
						<?php if ( $o_size ) : ?><span class="sssj-badge sssj-badge--abn"><?php echo esc_html( $o_size ); ?></span><?php endif; ?>
						<?php if ( $type ) : ?><span class="sssj-badge"><?php echo esc_html( ucfirst( $type ) ); ?></span><?php endif; ?>
						<?php echo Shuffles_SSJ_ABN::abr_badge_html( $oid ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php echo Shuffles_SSJ_Org::ndis_badge_html( $oid ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php echo Shuffles_SSJ_Shortcodes::openness_badges( $oid ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php if ( $locn > 0 ) : ?><span class="sssj-badge"><?php echo esc_html( sprintf( _n( '%d location', '%d locations', $locn, 'shuffles-social-services-jobs' ), $locn ) ); ?></span><?php endif; ?>
						<?php $o_stats = Shuffles_SSJ_Org::stats( $oid ); ?>
						<span class="sssj-badge sssj-badge--verified" title="<?php esc_attr_e( 'Currently open positions', 'shuffles-social-services-jobs' ); ?>"><?php echo esc_html( sprintf( _n( '%d open job', '%d open jobs', $o_stats['open'], 'shuffles-social-services-jobs' ), $o_stats['open'] ) ); ?></span>
						<?php if ( $o_stats['placed'] > 0 ) : ?><span class="sssj-badge sssj-badge--featured" title="<?php esc_attr_e( 'People placed, all time', 'shuffles-social-services-jobs' ); ?>"><?php echo esc_html( sprintf( _n( '%d placed', '%d placed', $o_stats['placed'], 'shuffles-social-services-jobs' ), $o_stats['placed'] ) ); ?></span><?php endif; ?>
					</div>
					<?php echo Shuffles_SSJ_Org::social_html( $oid ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php
					$o_sec = wp_get_post_terms( $oid, 'sssjt_category', array( 'fields' => 'names' ) );
					$o_fun = wp_get_post_terms( $oid, 'sssjt_funding_source', array( 'fields' => 'names' ) );
					$has_tags = ( ! is_wp_error( $o_sec ) && $o_sec ) || ( ! is_wp_error( $o_fun ) && $o_fun );
					?>
					<?php if ( $has_tags ) : ?>
						<div class="sssj-row" style="margin-top:6px">
							<?php foreach ( array_slice( (array) $o_sec, 0, 4 ) as $n ) { echo '<span class="sssj-badge sssj-badge--abn">' . esc_html( $n ) . '</span>'; } ?>
							<?php foreach ( array_slice( (array) $o_fun, 0, 4 ) as $n ) { echo '<span class="sssj-badge sssj-badge--need">' . esc_html( $n ) . '</span>'; } ?>
						</div>
					<?php endif; ?>
					<?php if ( $sub || $state ) : ?><p>📍 <?php echo esc_html( trim( $sub . ' ' . $state ) ); ?></p><?php endif; ?>
					<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 22 ) ); ?></p>
					<a class="sssj-btn sssj-btn--secondary sssj-btn--sm" href="<?php the_permalink(); ?>" data-i18n="view_profile_jobs"><?php esc_html_e( 'View profile & jobs', 'shuffles-social-services-jobs' ); ?></a>
				</article>
			<?php endwhile; ?>
		</div>
	<?php else : ?>
		<div class="sssj-panel"><p><?php esc_html_e( 'No organisations yet.', 'shuffles-social-services-jobs' ); ?></p></div>
	<?php endif; ?>
	</div>
</div>
