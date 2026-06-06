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
$o_sectors   = get_terms( array( 'taxonomy' => 'sssjt_category', 'hide_empty' => false ) );
$o_fundings  = get_terms( array( 'taxonomy' => 'sssjt_funding_source', 'hide_empty' => false ) );
?>
<div class="sssj sssj--orgs">
	<div class="sssj-panel">
		<h2 data-i18n="d_orgs"><?php esc_html_e( 'Organisations', 'shuffles-social-services-jobs' ); ?></h2>
		<form class="sssj-row" method="get" data-sssj-place-group>
			<input class="sssj-input" type="search" name="sssj_q" value="<?php echo esc_attr( $cur_q ); ?>" placeholder="<?php esc_attr_e( 'Search by company name…', 'shuffles-social-services-jobs' ); ?>" />
			<input class="sssj-input" type="text" name="sssj_loc" data-sssj-place value="<?php echo esc_attr( $cur_loc ); ?>" placeholder="<?php esc_attr_e( 'Near a suburb…', 'shuffles-social-services-jobs' ); ?>" data-i18n-placeholder="ph_near" />
			<input type="hidden" name="sssj_lat" data-sssj-lat value="<?php echo esc_attr( $cur_lat ); ?>" />
			<input type="hidden" name="sssj_lng" data-sssj-lng value="<?php echo esc_attr( $cur_lng ); ?>" />
			<label class="sssj-radius" style="display:flex;align-items:center;gap:8px">
				<span data-i18n="within"><?php esc_html_e( 'Within', 'shuffles-social-services-jobs' ); ?></span>
				<input type="range" name="sssj_radius" min="0" max="200" step="5" value="<?php echo esc_attr( $cur_rad ); ?>" oninput="this.nextElementSibling.value=(this.value==0?'<?php echo esc_js( __( 'Any', 'shuffles-social-services-jobs' ) ); ?>':this.value+' km')" />
				<output><?php echo esc_html( $rad_lbl ); ?></output>
			</label>
				<select class="sssj-select" name="sssj_sector">
					<option value=""><?php esc_html_e( 'All sectors', 'shuffles-social-services-jobs' ); ?></option>
					<?php if ( ! is_wp_error( $o_sectors ) ) { foreach ( $o_sectors as $t ) { echo '<option value="' . esc_attr( $t->slug ) . '" ' . selected( $cur_sector, $t->slug, false ) . '>' . esc_html( $t->name ) . '</option>'; } } ?>
				</select>
				<select class="sssj-select" name="sssj_funding">
					<option value=""><?php esc_html_e( 'All funding', 'shuffles-social-services-jobs' ); ?></option>
					<?php if ( ! is_wp_error( $o_fundings ) ) { foreach ( $o_fundings as $t ) { echo '<option value="' . esc_attr( $t->slug ) . '" ' . selected( $cur_funding, $t->slug, false ) . '>' . esc_html( $t->name ) . '</option>'; } } ?>
				</select>
				<label class="sssj-chip <?php echo $cur_open ? 'is-on' : ''; ?>"><input type="checkbox" name="sssj_open" value="1" <?php checked( $cur_open ); ?> /> <span data-i18n="open_only"><?php esc_html_e( 'Only with open placements', 'shuffles-social-services-jobs' ); ?></span></label>
			<button class="sssj-btn sssj-btn--primary" type="submit" data-i18n="filter"><?php esc_html_e( 'Filter', 'shuffles-social-services-jobs' ); ?></button>
		</form>
	</div>

	<?php if ( $maps && $has_pts ) : ?>
		<div class="sssj-panel" style="padding:0;overflow:hidden;margin-bottom:16px"><div data-sssj-map style="height:360px;width:100%"></div></div>
	<?php endif; ?>

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
				<article class="sssj-card">
					<div class="sssj-row" style="gap:10px;flex-wrap:nowrap;align-items:flex-start">
						<?php $logo = Shuffles_SSJ_Org::logo_url( $oid, 'thumbnail' ); ?>
						<?php if ( $logo ) : ?><img class="sssj-org-logo" src="<?php echo esc_url( $logo ); ?>" alt="" /><?php endif; ?>
						<h3 style="margin:0"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					</div>
					<div class="sssj-row">
						<?php if ( $type ) : ?><span class="sssj-badge"><?php echo esc_html( ucfirst( $type ) ); ?></span><?php endif; ?>
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
