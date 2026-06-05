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
$cur_q = isset( $_GET['sssj_q'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="sssj sssj--orgs">
	<div class="sssj-panel">
		<h2><?php esc_html_e( 'Organisations', 'shuffles-social-services-jobs' ); ?></h2>
		<form class="sssj-row" method="get">
			<input class="sssj-input" type="search" name="sssj_q" value="<?php echo esc_attr( $cur_q ); ?>" placeholder="<?php esc_attr_e( 'Search by company name…', 'shuffles-social-services-jobs' ); ?>" />
			<button class="sssj-btn sssj-btn--primary" type="submit" data-i18n="filter"><?php esc_html_e( 'Filter', 'shuffles-social-services-jobs' ); ?></button>
		</form>
	</div>

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
					<h3 style="margin-top:0"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<div class="sssj-row">
						<?php if ( $type ) : ?><span class="sssj-badge"><?php echo esc_html( ucfirst( $type ) ); ?></span><?php endif; ?>
						<?php if ( $locn > 0 ) : ?><span class="sssj-badge"><?php echo esc_html( sprintf( _n( '%d location', '%d locations', $locn, 'shuffles-social-services-jobs' ), $locn ) ); ?></span><?php endif; ?>
					</div>
					<?php if ( $sub || $state ) : ?><p>📍 <?php echo esc_html( trim( $sub . ' ' . $state ) ); ?></p><?php endif; ?>
					<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 22 ) ); ?></p>
					<a class="sssj-btn sssj-btn--secondary sssj-btn--sm" href="<?php the_permalink(); ?>"><?php esc_html_e( 'View profile & jobs', 'shuffles-social-services-jobs' ); ?></a>
				</article>
			<?php endwhile; ?>
		</div>
	<?php else : ?>
		<div class="sssj-panel"><p><?php esc_html_e( 'No organisations yet.', 'shuffles-social-services-jobs' ); ?></p></div>
	<?php endif; ?>
</div>
