<?php
/**
 * Sole-trader service flyer asset (v2 style, promotional). Receives $data from
 * Shuffles_SSJ_Assets::resume_data(). Same locked, readable rules as the résumé: location leads,
 * large text, one clear call to action.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$d         = isset( $data ) && is_array( $data ) ? $data : array();
$initials  = Shuffles_SSJ_Assets::initials( isset( $d['name'] ) ? $d['name'] : '' );
$has_blurb = '' !== trim( (string) ( isset( $d['blurb'] ) ? $d['blurb'] : '' ) );
?>
<div class="sssj-asset sssj-asset--flyer" id="sssj-asset">
	<div class="sssj-asset__head">
		<div class="sssj-asset__avatar">
			<?php if ( ! empty( $d['photo'] ) ) : ?><img src="<?php echo esc_url( $d['photo'] ); ?>" alt="" /><?php else : ?><span><?php echo esc_html( $initials ); ?></span><?php endif; ?>
		</div>
		<div class="sssj-asset__id">
			<h2 class="sssj-asset__name"><?php echo esc_html( isset( $d['name'] ) ? $d['name'] : '' ); ?></h2>
			<p class="sssj-asset__role" data-bind="tagline"><?php echo esc_html( isset( $d['tagline'] ) ? $d['tagline'] : '' ); ?></p>
			<p class="sssj-asset__where">📍 <span data-bind="location"><?php echo esc_html( isset( $d['location'] ) ? $d['location'] : '' ); ?></span></p>
		</div>
	</div>

	<div class="sssj-asset__ribbon"><?php echo esc_html( isset( $d['available'] ) ? $d['available'] : __( 'Available now', 'shuffles-social-services-jobs' ) ); ?></div>

	<h3 class="sssj-asset__h"><?php esc_html_e( 'What I can help with', 'shuffles-social-services-jobs' ); ?></h3>
	<?php if ( ! empty( $d['services'] ) ) : ?>
		<ul class="sssj-asset__chips sssj-asset__chips--lg">
			<?php foreach ( (array) $d['services'] as $s ) : ?><li><?php echo esc_html( $s ); ?></li><?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<div class="sssj-asset__about-wrap" data-block="about"<?php echo $has_blurb ? '' : ' hidden'; ?>>
		<p class="sssj-asset__about" data-bind="blurb"><?php echo esc_html( isset( $d['blurb'] ) ? $d['blurb'] : '' ); ?></p>
	</div>

	<?php if ( ! empty( $d['checks'] ) ) : ?>
		<h3 class="sssj-asset__h"><?php esc_html_e( 'Verified checks', 'shuffles-social-services-jobs' ); ?></h3>
		<ul class="sssj-asset__checks">
			<?php foreach ( (array) $d['checks'] as $c ) : ?><li><?php echo esc_html( $c ); ?></li><?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<div class="sssj-asset__cta"><?php esc_html_e( 'Get in touch safely through Just Tasks', 'shuffles-social-services-jobs' ); ?></div>
</div>
