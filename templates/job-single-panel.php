<?php
/**
 * Job details panel appended to a single job page (the structured meta the board cards show, expanded).
 * Var: $job_id. Theme override: themes/<theme>/shuffles-jobs/job-single-panel.php
 * Honours anonymous advertising (hides org name/logo). Only fields with a value are shown.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$job_id = isset( $job_id ) ? (int) $job_id : 0;
if ( ! $job_id ) {
	return;
}

$anon     = (bool) get_post_meta( $job_id, 'is_anonymous', true );
$basis    = (string) get_post_meta( $job_id, 'engagement_basis', true );
$etype    = (string) get_post_meta( $job_id, 'engagement_type', true );
$featured = (bool) get_post_meta( $job_id, 'is_promoted', true );
$org_id   = (int) get_post_meta( $job_id, 'organisation_id', true );
$suburb   = (string) get_post_meta( $job_id, 'location_suburb', true );
$state    = (string) get_post_meta( $job_id, 'location_state', true );
$rmin     = (float) get_post_meta( $job_id, 'rate_min', true );
$rmax     = (float) get_post_meta( $job_id, 'rate_max', true );
$runit    = (string) get_post_meta( $job_id, 'rate_unit', true );
$hmin     = (float) get_post_meta( $job_id, 'hours_per_week_min', true );
$hmax     = (float) get_post_meta( $job_id, 'hours_per_week_max', true );
$wmode    = (string) get_post_meta( $job_id, 'work_mode', true );
$emp      = (string) get_post_meta( $job_id, 'employment_type', true );
$start    = (string) get_post_meta( $job_id, 'start_date', true );
$closes   = (string) get_post_meta( $job_id, 'expires_at', true );

$logo = ( ! $anon && class_exists( 'Shuffles_SSJ_Org' ) ) ? (string) Shuffles_SSJ_Org::job_logo_url( $job_id, 'thumbnail' ) : '';
?>
<div class="sssj sssj--jobpanel">
	<div class="sssj-panel">
		<h3 style="margin-top:0"><?php esc_html_e( 'Job details', 'shuffles-social-services-jobs' ); ?></h3>

		<div class="sssj-row" style="align-items:center;gap:8px">
			<?php if ( $logo ) : ?><img class="sssj-org-logo" src="<?php echo esc_url( $logo ); ?>" alt="" /><?php endif; ?>
			<?php
			if ( $anon ) {
				echo '<strong>' . esc_html__( 'Private advertiser', 'shuffles-social-services-jobs' ) . '</strong>';
			} elseif ( $org_id ) {
				echo '<strong><a href="' . esc_url( get_permalink( $org_id ) ) . '">' . esc_html( get_the_title( $org_id ) ) . '</a></strong> ';
				if ( class_exists( 'Shuffles_SSJ_Verification' ) ) {
					echo Shuffles_SSJ_Verification::tick_html( $org_id, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}
			?>
		</div>

		<div class="sssj-row" style="margin-top:8px">
			<?php if ( '' !== $basis && class_exists( 'Shuffles_SSJ_Query' ) ) : ?>
				<span class="sssj-badge sssj-badge--<?php echo esc_attr( Shuffles_SSJ_Query::basis_class( $basis ) ); ?>"><?php echo esc_html( Shuffles_SSJ_Query::basis_label( $basis ) ); ?></span>
			<?php endif; ?>
			<?php if ( $etype ) : ?><span class="sssj-badge"><?php echo esc_html( 'one-off' === $etype ? __( 'One-off', 'shuffles-social-services-jobs' ) : __( 'Ongoing', 'shuffles-social-services-jobs' ) ); ?></span><?php endif; ?>
			<?php if ( $featured ) : ?><span class="sssj-badge sssj-badge--featured"><?php esc_html_e( '★ Featured', 'shuffles-social-services-jobs' ); ?></span><?php endif; ?>
			<?php
			if ( ! $anon && class_exists( 'Shuffles_SSJ_ABN' ) ) {
				echo Shuffles_SSJ_ABN::abr_badge_html( $job_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			if ( class_exists( 'Shuffles_SSJ_Shortcodes' ) ) {
				echo Shuffles_SSJ_Shortcodes::openness_badges( $job_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</div>

		<?php
		$facts = array();
		if ( $suburb || $state ) {
			$facts[] = array( '📍', __( 'Location', 'shuffles-social-services-jobs' ), trim( $suburb . ' ' . $state ) );
		}
		if ( $rmin > 0 || $rmax > 0 ) {
			$rate    = ( $rmin > 0 ? '$' . number_format_i18n( $rmin ) : '' ) . ( $rmax > 0 ? ' – $' . number_format_i18n( $rmax ) : '' ) . ' / ' . ( $runit ? $runit : 'hour' );
			$facts[] = array( '💲', __( 'Pay rate', 'shuffles-social-services-jobs' ), trim( $rate ) );
		}
		if ( $hmin > 0 || $hmax > 0 ) {
			$hrs     = ( $hmin > 0 ? number_format_i18n( $hmin ) : '' ) . ( $hmax > 0 ? ' – ' . number_format_i18n( $hmax ) : '' );
			$facts[] = array( '⏱️', __( 'Hours per week', 'shuffles-social-services-jobs' ), trim( $hrs ) );
		}
		if ( $emp ) {
			$facts[] = array( '🧾', __( 'Employment type', 'shuffles-social-services-jobs' ), $emp );
		}
		if ( $wmode ) {
			$facts[] = array( '🏷️', __( 'Work mode', 'shuffles-social-services-jobs' ), $wmode );
		}
		if ( $start ) {
			$facts[] = array( '📅', __( 'Start date', 'shuffles-social-services-jobs' ), $start );
		}
		if ( $closes ) {
			$facts[] = array( '⏳', __( 'Closes', 'shuffles-social-services-jobs' ), $closes );
		}
		if ( $facts ) {
			echo '<ul class="sssj-facts">';
			foreach ( $facts as $f ) {
				echo '<li><span class="sssj-facts__k">' . esc_html( $f[0] . ' ' . $f[1] ) . '</span><span class="sssj-facts__v">' . esc_html( $f[2] ) . '</span></li>';
			}
			echo '</ul>';
		}

		// Taxonomy chips (only those with assigned terms).
		$taxes = array(
			'sssjt_category'       => __( 'Categories', 'shuffles-social-services-jobs' ),
			'sssjt_role'           => __( 'Role', 'shuffles-social-services-jobs' ),
			'sssjt_qualification'  => __( 'Required qualifications', 'shuffles-social-services-jobs' ),
			'sssjt_funding_source' => __( 'Funding', 'shuffles-social-services-jobs' ),
		);
		foreach ( $taxes as $tax => $label ) {
			$terms = get_the_terms( $job_id, $tax );
			if ( $terms && ! is_wp_error( $terms ) ) {
				echo '<p class="sssj-facts__tax"><strong>' . esc_html( $label ) . ':</strong> ';
				foreach ( $terms as $term ) {
					echo '<span class="sssj-badge">' . esc_html( $term->name ) . '</span> ';
				}
				echo '</p>';
			}
		}
		?>
	</div>
</div>
