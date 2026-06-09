<?php
/**
 * Worker / sole-trader résumé asset. Receives $data from Shuffles_SSJ_Assets::resume_data().
 * Renders every résumé section in one ATS-friendly DOM (Name & contact, Professional summary,
 * Compliance & credentials, Core skills, Employment history, Education & training, Referees).
 * The chosen skin is a CSS class on the container: "ats" (plain, single column, no photo, the
 * health-sector default) or "styled" (photo + colour). Toggled on the Create-an-asset résumé tab.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$d        = isset( $data ) && is_array( $data ) ? $data : array();
$fmt      = ( isset( $d['format'] ) && 'styled' === $d['format'] ) ? 'styled' : 'ats';
$initials = Shuffles_SSJ_Assets::initials( isset( $d['name'] ) ? $d['name'] : '' );
$g        = function ( $k ) use ( $d ) { return isset( $d[ $k ] ) ? $d[ $k ] : ''; };
$contact  = ( isset( $d['contact'] ) && is_array( $d['contact'] ) ) ? $d['contact'] : array();
$summary  = (string) $g( 'summary' );

// Compliance & credentials snapshot (label, value); only rows with a value render.
$compliance = array();
if ( '' !== trim( (string) $g( 'qualifications' ) ) ) { $compliance[] = array( __( 'Qualifications', 'shuffles-social-services-jobs' ), (string) $g( 'qualifications' ) ); }
if ( '' !== trim( (string) $g( 'registration' ) ) )   { $compliance[] = array( __( 'Registration', 'shuffles-social-services-jobs' ), (string) $g( 'registration' ) ); }
if ( ! empty( $d['checks'] ) )                         { $compliance[] = array( __( 'Screening & checks', 'shuffles-social-services-jobs' ), implode( ', ', (array) $d['checks'] ) ); }
if ( '' !== trim( (string) $g( 'training' ) ) )        { $compliance[] = array( __( 'Training', 'shuffles-social-services-jobs' ), (string) $g( 'training' ) ); }
if ( '' !== trim( (string) $g( 'licences' ) ) )        { $compliance[] = array( __( 'Licences', 'shuffles-social-services-jobs' ), (string) $g( 'licences' ) ); }
if ( '' !== trim( (string) $g( 'work_rights' ) ) )     { $compliance[] = array( __( 'Work rights', 'shuffles-social-services-jobs' ), (string) $g( 'work_rights' ) ); }

$contact_bits = array();
if ( ! empty( $contact['phone'] ) )            { $contact_bits[] = esc_html( $contact['phone'] ); }
if ( ! empty( $contact['email'] ) )            { $contact_bits[] = esc_html( $contact['email'] ); }
if ( '' !== trim( (string) $g( 'location' ) ) ) { $contact_bits[] = esc_html( (string) $g( 'location' ) ); }
if ( ! empty( $contact['linkedin'] ) )         { $contact_bits[] = esc_html( preg_replace( '#^https?://#', '', (string) $contact['linkedin'] ) ); }
?>
<div class="sssj-asset sssj-asset--resume sssj-asset--<?php echo esc_attr( $fmt ); ?>" id="sssj-asset" data-asset-format="<?php echo esc_attr( $fmt ); ?>">
	<div class="sssj-asset__head">
		<div class="sssj-asset__avatar">
			<?php if ( ! empty( $d['photo'] ) ) : ?><img src="<?php echo esc_url( $d['photo'] ); ?>" alt="" /><?php else : ?><span><?php echo esc_html( $initials ); ?></span><?php endif; ?>
		</div>
		<div class="sssj-asset__id">
			<h2 class="sssj-asset__name"><?php echo esc_html( (string) $g( 'name' ) ); ?></h2>
			<p class="sssj-asset__role" data-bind="tagline"><?php echo esc_html( (string) $g( 'tagline' ) ); ?></p>
			<?php if ( $contact_bits ) : ?><p class="sssj-asset__contact"><?php echo implode( ' &nbsp;|&nbsp; ', $contact_bits ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p><?php endif; ?>
		</div>
	</div>

	<?php if ( '' !== trim( $summary ) ) : ?>
		<section class="sssj-asset__sec">
			<h3 class="sssj-asset__h"><?php esc_html_e( 'Professional summary', 'shuffles-social-services-jobs' ); ?></h3>
			<p class="sssj-asset__about" data-bind="blurb"><?php echo esc_html( $summary ); ?></p>
		</section>
	<?php endif; ?>

	<?php if ( $compliance ) : ?>
		<section class="sssj-asset__sec">
			<h3 class="sssj-asset__h"><?php esc_html_e( 'Compliance & credentials', 'shuffles-social-services-jobs' ); ?></h3>
			<ul class="sssj-asset__facts2">
				<?php foreach ( $compliance as $row ) : ?>
					<li><b><?php echo esc_html( $row[0] ); ?></b><span><?php echo esc_html( $row[1] ); ?></span></li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $d['skills'] ) ) : ?>
		<section class="sssj-asset__sec">
			<h3 class="sssj-asset__h"><?php esc_html_e( 'Core skills', 'shuffles-social-services-jobs' ); ?></h3>
			<ul class="sssj-asset__chips">
				<?php foreach ( (array) $d['skills'] as $s ) : ?><li><?php echo esc_html( $s ); ?></li><?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $d['employment'] ) ) : ?>
		<section class="sssj-asset__sec">
			<h3 class="sssj-asset__h"><?php esc_html_e( 'Employment history', 'shuffles-social-services-jobs' ); ?></h3>
			<?php
			foreach ( (array) $d['employment'] as $job ) :
				if ( empty( $job['title'] ) && empty( $job['employer'] ) ) {
					continue;
				}
				$head = trim( (string) ( isset( $job['title'] ) ? $job['title'] : '' ) . ( ! empty( $job['employer'] ) ? ', ' . $job['employer'] : '' ) );
				$meta = trim( (string) ( isset( $job['location'] ) ? $job['location'] : '' ) . ( ! empty( $job['dates'] ) ? ' · ' . $job['dates'] : '' ) );
				?>
				<div class="sssj-asset__role">
					<div class="sssj-asset__role-head">
						<strong><?php echo esc_html( $head ); ?></strong>
						<?php if ( '' !== $meta ) : ?><span class="sssj-asset__role-meta"><?php echo esc_html( $meta ); ?></span><?php endif; ?>
					</div>
					<?php if ( ! empty( $job['bullets'] ) ) : ?>
						<ul class="sssj-asset__bullets"><?php foreach ( (array) $job['bullets'] as $b ) : ?><li><?php echo esc_html( $b ); ?></li><?php endforeach; ?></ul>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $d['education'] ) ) : ?>
		<section class="sssj-asset__sec">
			<h3 class="sssj-asset__h"><?php esc_html_e( 'Education & training', 'shuffles-social-services-jobs' ); ?></h3>
			<ul class="sssj-asset__edu">
				<?php
				foreach ( (array) $d['education'] as $e ) :
					if ( empty( $e['qualification'] ) ) {
						continue;
					}
					$tail = trim( (string) ( isset( $e['institution'] ) ? $e['institution'] : '' ) . ( ! empty( $e['year'] ) ? ' · ' . $e['year'] : '' ) );
					?>
					<li><strong><?php echo esc_html( $e['qualification'] ); ?></strong><?php if ( '' !== $tail ) : ?> <span><?php echo esc_html( $tail ); ?></span><?php endif; ?></li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<section class="sssj-asset__sec">
		<h3 class="sssj-asset__h"><?php esc_html_e( 'Referees', 'shuffles-social-services-jobs' ); ?></h3>
		<?php
		if ( 'upfront' === (string) $g( 'referees_mode' ) && '' !== trim( (string) $g( 'referees' ) ) ) {
			echo '<p class="sssj-asset__ref">' . nl2br( esc_html( (string) $g( 'referees' ) ) ) . '</p>';
		} else {
			echo '<p class="sssj-asset__ref">' . esc_html__( 'Referees available on request.', 'shuffles-social-services-jobs' ) . '</p>';
		}
		?>
	</section>
</div>
