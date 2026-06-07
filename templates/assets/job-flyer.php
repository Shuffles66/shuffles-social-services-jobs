<?php
/**
 * Employer job flyer asset (v2 style). Receives $data from Shuffles_SSJ_Assets::job_data().
 * Location and pay lead; honours anonymous advertising (no org name/logo when anonymous).
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$d = isset( $data ) && is_array( $data ) ? $data : array();
?>
<div class="sssj-asset sssj-asset--jobflyer" id="sssj-asset">
	<div class="sssj-asset__head">
		<?php if ( ! empty( $d['logo'] ) ) : ?>
			<div class="sssj-asset__avatar sssj-asset__avatar--square"><img src="<?php echo esc_url( $d['logo'] ); ?>" alt="" /></div>
		<?php endif; ?>
		<div class="sssj-asset__id">
			<p class="sssj-asset__org"><?php echo esc_html( isset( $d['org'] ) ? $d['org'] : '' ); ?></p>
			<h2 class="sssj-asset__name"><?php echo esc_html( isset( $d['title'] ) ? $d['title'] : '' ); ?></h2>
			<p class="sssj-asset__where">📍 <?php echo esc_html( isset( $d['location'] ) ? $d['location'] : '' ); ?></p>
		</div>
	</div>

	<div class="sssj-asset__facts">
		<?php if ( ! empty( $d['pay'] ) ) : ?><div class="sssj-asset__fact"><b><?php esc_html_e( 'Pay', 'shuffles-social-services-jobs' ); ?></b><span><?php echo esc_html( $d['pay'] ); ?></span></div><?php endif; ?>
		<?php if ( ! empty( $d['basis'] ) ) : ?><div class="sssj-asset__fact"><b><?php esc_html_e( 'Type', 'shuffles-social-services-jobs' ); ?></b><span><?php echo esc_html( $d['basis'] ); ?></span></div><?php endif; ?>
		<?php if ( ! empty( $d['employment'] ) ) : ?><div class="sssj-asset__fact"><b><?php esc_html_e( 'Hours', 'shuffles-social-services-jobs' ); ?></b><span><?php echo esc_html( $d['employment'] ); ?></span></div><?php endif; ?>
		<?php if ( ! empty( $d['closes'] ) ) : ?><div class="sssj-asset__fact"><b><?php esc_html_e( 'Closes', 'shuffles-social-services-jobs' ); ?></b><span><?php echo esc_html( mysql2date( get_option( 'date_format' ), $d['closes'] ) ); ?></span></div><?php endif; ?>
	</div>

	<?php if ( '' !== trim( (string) ( isset( $d['blurb'] ) ? $d['blurb'] : '' ) ) ) : ?>
		<h3 class="sssj-asset__h"><?php esc_html_e( 'About the role', 'shuffles-social-services-jobs' ); ?></h3>
		<p class="sssj-asset__about"><?php echo esc_html( wp_trim_words( $d['blurb'], 60 ) ); ?></p>
	<?php endif; ?>

	<?php if ( ! empty( $d['services'] ) ) : ?>
		<h3 class="sssj-asset__h"><?php esc_html_e( 'What it involves', 'shuffles-social-services-jobs' ); ?></h3>
		<ul class="sssj-asset__chips">
			<?php foreach ( (array) $d['services'] as $s ) : ?><li><?php echo esc_html( $s ); ?></li><?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<div class="sssj-asset__cta"><?php esc_html_e( 'Apply safely through Just Tasks', 'shuffles-social-services-jobs' ); ?></div>
</div>
