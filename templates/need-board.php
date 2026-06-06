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
$has_map = ! empty( $has_map );
$cur_loc = isset( $_GET['sssj_loc'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_loc'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$cur_lat = isset( $_GET['sssj_lat'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_lat'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$cur_lng = isset( $_GET['sssj_lng'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_lng'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$cur_rad = isset( $_GET['sssj_radius'] ) ? (int) $_GET['sssj_radius'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="sssj sssj--needs">
	<div class="sssj-panel">
		<h2><?php echo esc_html( $heading ); ?></h2>
		<?php
		$applied = isset( $_GET['sssj_applied'] ) ? sanitize_key( wp_unslash( $_GET['sssj_applied'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '1' === $applied ) {
			echo '<p class="sssj-badge sssj-badge--verified">' . esc_html__( 'Your response was sent.', 'shuffles-social-services-jobs' ) . '</p>';
		} elseif ( 'dup' === $applied ) {
			echo '<p class="sssj-badge">' . esc_html__( 'You have already responded to that request.', 'shuffles-social-services-jobs' ) . '</p>';
		} elseif ( 'denied' === $applied ) {
			echo '<p class="sssj-badge" style="background:#fee2e2;color:#b91c1c">' . esc_html__( 'A recorded ABN is required to respond.', 'shuffles-social-services-jobs' ) . '</p>';
		}
		?>
		<p class="description"><?php esc_html_e( 'These requests come from participants or their nominees. Identities are protected — first contact is made through the site, never by exposing personal details. Responding requires a recorded ABN.', 'shuffles-social-services-jobs' ); ?></p>
		<?php Shuffles_SSJ_Shortcodes::render_readme( 'needs' ); ?>
		<form class="sssj-row" method="get" data-sssj-place-group data-sssj-filter-form>
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
			<?php if ( $has_map ) : ?>
				<input class="sssj-input" type="text" name="sssj_loc" data-sssj-place value="<?php echo esc_attr( $cur_loc ); ?>" placeholder="<?php esc_attr_e( 'Near a suburb…', 'shuffles-social-services-jobs' ); ?>" />
				<input type="hidden" name="sssj_lat" data-sssj-lat value="<?php echo esc_attr( $cur_lat ); ?>" />
				<input type="hidden" name="sssj_lng" data-sssj-lng value="<?php echo esc_attr( $cur_lng ); ?>" />
				<select class="sssj-select" name="sssj_radius">
					<option value="0"><?php esc_html_e( 'Any distance', 'shuffles-social-services-jobs' ); ?></option>
					<?php
					foreach ( array( 5, 10, 25, 50, 100 ) as $km ) {
						echo '<option value="' . esc_attr( $km ) . '" ' . selected( $cur_rad, $km, false ) . '>' . esc_html( sprintf( __( 'within %d km', 'shuffles-social-services-jobs' ), $km ) ) . '</option>';
					}
					?>
				</select>
				<?php Shuffles_SSJ_Shortcodes::location_button(); ?>
			<?php endif; ?>
			<?php Shuffles_SSJ_Field_Registry::render_banner_filters( 'need' ); Shuffles_SSJ_Shortcodes::filter_actions(); ?>
		</form>
		<?php if ( class_exists( 'Shuffles_SSJ_Alerts' ) ) { Shuffles_SSJ_Alerts::save_search_button( 'needs' ); } ?>
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
				<article class="sssj-card sssj-card--need" data-sssj-id="<?php echo esc_attr( $pid ); ?>">
					<div class="sssj-row">
						<span class="sssj-badge sssj-badge--need"><?php echo esc_html( $ref ? $ref : __( 'Participant', 'shuffles-social-services-jobs' ) ); ?></span>
						<?php echo Shuffles_SSJ_Shortcodes::distance_pill( $pid, isset( $center ) ? $center : null ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php if ( $ongoing ) : ?><span class="sssj-badge"><?php echo esc_html( 'temporary' === $ongoing ? __( 'Temporary', 'shuffles-social-services-jobs' ) : __( 'Ongoing', 'shuffles-social-services-jobs' ) ); ?></span><?php endif; ?>
						<?php
						$seeking  = (string) get_post_meta( $pid, 'seeking_type', true );
						$seek_lbl = array( 'provider' => __( 'Seeking a provider', 'shuffles-social-services-jobs' ), 'task' => __( 'One-off task', 'shuffles-social-services-jobs' ), 'support' => __( 'Ongoing support', 'shuffles-social-services-jobs' ) );
						if ( isset( $seek_lbl[ $seeking ] ) ) : ?><span class="sssj-badge sssj-badge--abn"><?php echo esc_html( $seek_lbl[ $seeking ] ); ?></span><?php endif; ?>
					</div>
					<h3 style="margin:8px 0"><?php the_title(); ?></h3>
					<?php if ( $suburb || $state ) : ?><p>📍 <?php echo esc_html( trim( $suburb . ' ' . $state ) ); ?></p><?php endif; ?>
					<?php if ( ! is_wp_error( $supps ) && ! empty( $supps ) ) : ?><p><strong><?php esc_html_e( 'Support:', 'shuffles-social-services-jobs' ); ?></strong> <?php echo esc_html( implode( ', ', $supps ) ); ?></p><?php endif; ?>
					<?php if ( ! is_wp_error( $funds ) && ! empty( $funds ) ) : ?><p><strong><?php esc_html_e( 'Funding:', 'shuffles-social-services-jobs' ); ?></strong> <?php echo esc_html( implode( ', ', $funds ) ); ?></p><?php endif; ?>
					<?php if ( $sched ) : ?><p><strong><?php esc_html_e( 'When:', 'shuffles-social-services-jobs' ); ?></strong> <?php echo esc_html( ucfirst( str_replace( '-', ' ', $sched ) ) ); ?></p><?php endif; ?>
					<?php
					$need_full  = trim( wp_strip_all_tags( get_the_content() ) );
					$need_short = wp_trim_words( $need_full, 28 );
					?>
					<p><?php echo esc_html( $need_short ); ?></p>
					<?php if ( $need_full && mb_strlen( $need_full ) > mb_strlen( $need_short ) ) : ?>
						<details class="sssj-readme sssj-need-more">
							<summary class="sssj-readme__summary"><span class="sssj-readme__icon" aria-hidden="true">⤢</span> <?php esc_html_e( 'View full request', 'shuffles-social-services-jobs' ); ?></summary>
							<div class="sssj-readme__body">
								<?php if ( $sched ) : ?><p><strong><?php esc_html_e( 'When:', 'shuffles-social-services-jobs' ); ?></strong> <?php echo esc_html( ucfirst( str_replace( '-', ' ', $sched ) ) ); ?></p><?php endif; ?>
								<?php if ( $gender ) : ?><p><strong><?php esc_html_e( 'Worker gender preference:', 'shuffles-social-services-jobs' ); ?></strong> <?php echo esc_html( ucfirst( str_replace( '-', ' ', $gender ) ) ); ?></p><?php endif; ?>
								<?php echo wp_kses_post( wpautop( $need_full ) ); ?>
							</div>
						</details>
					<?php endif; ?>
					<?php
					if ( ! is_user_logged_in() ) {
						echo '<p class="description">' . esc_html__( 'Log in to respond.', 'shuffles-social-services-jobs' ) . '</p>';
					} elseif ( Shuffles_SSJ_Applications::already_applied( 0, $pid, get_current_user_id() ) ) {
						echo '<p class="description">' . esc_html__( 'You have responded to this request.', 'shuffles-social-services-jobs' ) . '</p>';
					} elseif ( Shuffles_SSJ_Applications::can_respond( 'abn' ) ) {
						?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sssj-stack" style="margin-top:8px">
							<input type="hidden" name="action" value="sssj_apply" />
							<input type="hidden" name="need_id" value="<?php echo esc_attr( $pid ); ?>" />
							<?php wp_nonce_field( 'sssj_apply', 'sssj_apply_nonce' ); ?>
							<textarea class="sssj-textarea" name="cover_message" rows="2" placeholder="<?php esc_attr_e( 'Short message (optional)', 'shuffles-social-services-jobs' ); ?>"></textarea>
							<button class="sssj-btn sssj-btn--primary sssj-btn--sm" type="submit" data-i18n="respond"><?php esc_html_e( 'Respond', 'shuffles-social-services-jobs' ); ?></button>
						</form>
						<?php
					} else {
						echo '<p class="description">' . esc_html__( 'To respond, add a valid ABN to your worker profile. (A provider subscription will also be required.)', 'shuffles-social-services-jobs' ) . '</p>';
					}
					?>
				</article>
			<?php endwhile; ?>
		</div>
	<?php else : ?>
		<div class="sssj-panel"><p><?php esc_html_e( 'No participant requests are open right now.', 'shuffles-social-services-jobs' ); ?></p></div>
	<?php endif; ?>
</div>
