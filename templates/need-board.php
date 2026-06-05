<?php
/**
 * Participant-need board. Vars: $query (WP_Query), $atts.
 * Visibility + moderation enforced in Shuffles_SSJ_Query::need_args() (logged-in only; published only).
 * Privacy: shows the pseudonym + suburb only — NEVER a name or contact details.
 * Theme override: wp-content/themes/<theme>/shuffles-jobs/need-board.php
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var WP_Query $query */
/** @var array $atts */

$heading = ! empty( $atts['title'] ) ? $atts['title'] : __( 'Participants seeking workers', 'shuffles-social-services-jobs' );
$support = get_terms( array( 'taxonomy' => 'sssjt_support_category', 'hide_empty' => false ) );
$funding = get_terms( array( 'taxonomy' => 'sssjt_funding_source', 'hide_empty' => false ) );
$cur_s   = isset( $_GET['sssj_support'] ) ? sanitize_title( wp_unslash( $_GET['sssj_support'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$cur_f   = isset( $_GET['sssj_funding'] ) ? sanitize_title( wp_unslash( $_GET['sssj_funding'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="sssj sssj--needs">
	<div class="sssj-panel">
		<h2><?php echo esc_html( $heading ); ?></h2>
		<p class="description"><?php esc_html_e( 'These requests come from participants or their nominees. Identities are protected — first contact is made through the site, never by exposing personal details. Responding requires a recorded ABN.', 'shuffles-social-services-jobs' ); ?></p>
		<form class="sssj-row" method="get">
			<select class="sssj-select" name="sssj_support">
				<option value=""><?php esc_html_e( 'All support types', 'shuffles-social-services-jobs' ); ?></option>
				<?php
				if ( ! is_wp_error( $support ) ) {
					foreach ( $support as $t ) {
						echo '<option value="' . esc_attr( $t->slug ) . '" ' . selected( $cur_s, $t->slug, false ) . '>' . esc_html( $t->name ) . '</option>';
					}
				}
				?>
			</select>
			<select class="sssj-select" name="sssj_funding">
				<option value=""><?php esc_html_e( 'Any funding', 'shuffles-social-services-jobs' ); ?></option>
				<?php
				if ( ! is_wp_error( $funding ) ) {
					foreach ( $funding as $t ) {
						echo '<option value="' . esc_attr( $t->slug ) . '" ' . selected( $cur_f, $t->slug, false ) . '>' . esc_html( $t->name ) . '</option>';
					}
				}
				?>
			</select>
			<button class="sssj-btn sssj-btn--primary" type="submit"><?php esc_html_e( 'Filter', 'shuffles-social-services-jobs' ); ?></button>
		</form>
	</div>

	<?php if ( $query->have_posts() ) : ?>
		<div class="sssj-grid">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				$pid     = get_the_ID();
				$ref     = (string) get_post_meta( $pid, 'participant_ref', true );
				$suburb  = (string) get_post_meta( $pid, 'location_suburb', true );
				$state   = (string) get_post_meta( $pid, 'location_state', true );
				$sched   = (string) get_post_meta( $pid, 'schedule_pattern', true );
				$ongoing = (string) get_post_meta( $pid, 'ongoing_or_temp', true );
				$gender  = (string) get_post_meta( $pid, 'gender_preference', true );
				$supps   = wp_get_post_terms( $pid, 'sssjt_support_category', array( 'fields' => 'names' ) );
				$funds   = wp_get_post_terms( $pid, 'sssjt_funding_source', array( 'fields' => 'names' ) );
				?>
				<article class="sssj-card sssj-card--need">
					<div class="sssj-row">
						<span class="sssj-badge sssj-badge--need"><?php echo esc_html( $ref ? $ref : __( 'Participant', 'shuffles-social-services-jobs' ) ); ?></span>
						<?php if ( $ongoing ) : ?><span class="sssj-badge"><?php echo esc_html( 'temporary' === $ongoing ? __( 'Temporary', 'shuffles-social-services-jobs' ) : __( 'Ongoing', 'shuffles-social-services-jobs' ) ); ?></span><?php endif; ?>
					</div>
					<h3 style="margin:8px 0"><?php the_title(); ?></h3>
					<?php if ( $suburb || $state ) : ?><p>📍 <?php echo esc_html( trim( $suburb . ' ' . $state ) ); ?></p><?php endif; ?>
					<?php if ( ! is_wp_error( $supps ) && ! empty( $supps ) ) : ?><p><strong><?php esc_html_e( 'Support:', 'shuffles-social-services-jobs' ); ?></strong> <?php echo esc_html( implode( ', ', $supps ) ); ?></p><?php endif; ?>
					<?php if ( ! is_wp_error( $funds ) && ! empty( $funds ) ) : ?><p><strong><?php esc_html_e( 'Funding:', 'shuffles-social-services-jobs' ); ?></strong> <?php echo esc_html( implode( ', ', $funds ) ); ?></p><?php endif; ?>
					<?php if ( $sched ) : ?><p><strong><?php esc_html_e( 'When:', 'shuffles-social-services-jobs' ); ?></strong> <?php echo esc_html( ucfirst( str_replace( '-', ' ', $sched ) ) ); ?></p><?php endif; ?>
					<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 28 ) ); ?></p>
					<p class="description"><?php esc_html_e( 'To respond you need a recorded ABN and an active provider subscription. Secure messaging is coming soon.', 'shuffles-social-services-jobs' ); ?></p>
				</article>
			<?php endwhile; ?>
		</div>
	<?php else : ?>
		<div class="sssj-panel"><p><?php esc_html_e( 'No participant requests are open right now.', 'shuffles-social-services-jobs' ); ?></p></div>
	<?php endif; ?>
</div>
