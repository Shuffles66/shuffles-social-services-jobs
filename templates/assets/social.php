<?php
/**
 * Social graphic asset (square, for a social post). Receives $data from
 * Shuffles_SSJ_Assets::resume_data(). Compact and bold; location leads.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$d        = isset( $data ) && is_array( $data ) ? $data : array();
$initials = Shuffles_SSJ_Assets::initials( isset( $d['name'] ) ? $d['name'] : '' );
$services = isset( $d['services'] ) ? array_slice( (array) $d['services'], 0, 4 ) : array();
?>
<div class="sssj-asset sssj-asset--social" id="sssj-asset">
	<div class="sssj-asset__social-top">
		<div class="sssj-asset__avatar">
			<?php if ( ! empty( $d['photo'] ) ) : ?><img src="<?php echo esc_url( $d['photo'] ); ?>" alt="" /><?php else : ?><span><?php echo esc_html( $initials ); ?></span><?php endif; ?>
		</div>
		<p class="sssj-asset__where">📍 <span data-bind="location"><?php echo esc_html( isset( $d['location'] ) ? $d['location'] : '' ); ?></span></p>
		<h2 class="sssj-asset__name"><?php echo esc_html( isset( $d['name'] ) ? $d['name'] : '' ); ?></h2>
		<p class="sssj-asset__role" data-bind="tagline"><?php echo esc_html( isset( $d['tagline'] ) ? $d['tagline'] : '' ); ?></p>
	</div>

	<?php if ( $services ) : ?>
		<ul class="sssj-asset__chips sssj-asset__chips--lg">
			<?php foreach ( $services as $s ) : ?><li><?php echo esc_html( $s ); ?></li><?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<div class="sssj-asset__social-foot">
		<span class="sssj-asset__avail"><?php echo esc_html( isset( $d['available'] ) ? $d['available'] : __( 'Available now', 'shuffles-social-services-jobs' ) ); ?></span>
		<?php if ( ! empty( $d['checks'] ) ) : ?>
			<span class="sssj-asset__vcount">✓ <?php echo esc_html( sprintf( _n( '%d verified check', '%d verified checks', count( (array) $d['checks'] ), 'shuffles-social-services-jobs' ), count( (array) $d['checks'] ) ) ); ?></span>
		<?php endif; ?>
	</div>
	<div class="sssj-asset__cta"><?php esc_html_e( 'Find me on Just Tasks', 'shuffles-social-services-jobs' ); ?></div>
</div>
