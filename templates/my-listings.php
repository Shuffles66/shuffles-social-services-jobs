<?php
/**
 * Member dashboard: my applications, my job ads (+ applicants), my participant requests (+ responses).
 * Theme override: themes/<theme>/shuffles-jobs/my-listings.php
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	echo '<div class="sssj"><div class="sssj-panel"><p>' . esc_html__( 'Log in to see your listings.', 'shuffles-social-services-jobs' )
		. ' <a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' . esc_url( Shuffles_SSJ_Shortcodes::login_url( get_permalink() ) ) . '">' . esc_html__( 'Log in', 'shuffles-social-services-jobs' ) . '</a></p></div></div>';
	return;
}

$uid        = get_current_user_id();
$nonce      = wp_create_nonce( 'sssj_app_status' );
$wd_nonce   = wp_create_nonce( 'sssj_app_withdraw' );
$react_nonce = wp_create_nonce( 'sssj_listing_reactivate' );
$action     = esc_url( admin_url( 'admin-post.php' ) );

$badge = function ( $status ) {
	$cls = in_array( $status, array( 'offer', 'shortlisted', 'interview', 'hired' ), true ) ? ' sssj-badge--verified' : '';
	return '<span class="sssj-badge' . $cls . '">' . esc_html( Shuffles_SSJ_Applications::status_label( $status ) ) . '</span>';
};

// End-date line + Reopen ("rebirth") control for a listing the current user owns.
$lifecycle = function ( $post ) use ( $action, $react_nonce ) {
	$ends   = (string) get_post_meta( $post->ID, 'expires_at', true );
	$status = get_post_status( $post );
	$out    = '';
	if ( '' !== $ends ) {
		if ( 'publish' === $status ) {
			$out .= '<span class="sssj-life__ends description">' . esc_html( sprintf( __( 'Closes on %s', 'shuffles-social-services-jobs' ), mysql2date( get_option( 'date_format' ), $ends ) ) ) . '</span>';
		} else {
			$out .= '<span class="sssj-life__ends description">' . esc_html( sprintf( __( 'Closed (was due %s)', 'shuffles-social-services-jobs' ), mysql2date( get_option( 'date_format' ), $ends ) ) ) . '</span>';
		}
	}
	$out .= Shuffles_SSJ_Shortcodes::listing_actions_html( $post );
		return $out ? '<div class="sssj-life">' . $out . '</div>' : '';
};

/** Render the applicant rows + status control for an entity owned by the current user. */
$render_apps = function ( $type, $entity_id ) use ( $badge, $nonce, $action ) {
	// Per-job application-handling mode (jobs only): full pipeline vs simple.
	$mode = ( 'job' === $type ) ? ( get_post_meta( $entity_id, 'application_mode', true ) ? get_post_meta( $entity_id, 'application_mode', true ) : 'full' ) : 'full';
	$apps = Shuffles_SSJ_Applications::for_entity( $type, $entity_id );
	if ( empty( $apps ) ) {
		echo '<p class="description">' . esc_html__( 'No applications yet.', 'shuffles-social-services-jobs' ) . '</p>';
		return;
	}
	foreach ( $apps as $a ) {
		$u = get_userdata( (int) $a->applicant_user_id );
		echo '<div class="sssj-card" style="margin:8px 0">';
		echo '<div class="sssj-row"><strong>' . esc_html( $u ? $u->display_name : __( 'Applicant', 'shuffles-social-services-jobs' ) ) . '</strong> ' . $badge( (string) $a->status ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( ! empty( $a->cover_message ) ) {
			echo '<p>' . esc_html( wp_trim_words( wp_strip_all_tags( $a->cover_message ), 50 ) ) . '</p>';
		}
		// Employee (TFN) application extras: résumé, availability, start, right-to-work, screening answers.
		if ( ! empty( $a->resume_id ) && class_exists( 'Shuffles_SSJ_Resumes' ) ) {
			echo '<p>📄 <a href="' . esc_url( Shuffles_SSJ_Resumes::file_url( (int) $a->resume_id ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View résumé', 'shuffles-social-services-jobs' ) . '</a></p>';
		}
		$sssj_ex = Shuffles_SSJ_Applications::extra( $a );
		if ( ! empty( $sssj_ex['availability'] ) ) {
			echo '<p><strong>' . esc_html__( 'Availability:', 'shuffles-social-services-jobs' ) . '</strong> ' . esc_html( $sssj_ex['availability'] ) . '</p>';
		}
		if ( ! empty( $sssj_ex['start_date'] ) ) {
			echo '<p><strong>' . esc_html__( 'Earliest start:', 'shuffles-social-services-jobs' ) . '</strong> ' . esc_html( $sssj_ex['start_date'] ) . '</p>';
		}
		if ( ! empty( $sssj_ex['right_to_work'] ) ) {
			echo '<p>✓ ' . esc_html__( 'Confirmed right to work in Australia', 'shuffles-social-services-jobs' ) . '</p>';
		}
		if ( ! empty( $sssj_ex['screening'] ) && is_array( $sssj_ex['screening'] ) ) {
			echo '<ul class="ul-disc" style="margin-left:18px">';
			foreach ( $sssj_ex['screening'] as $sssj_qa ) {
				$sssj_a = ( isset( $sssj_qa['a'] ) && '' !== $sssj_qa['a'] ) ? $sssj_qa['a'] : '-';
				echo '<li><strong>' . esc_html( isset( $sssj_qa['q'] ) ? $sssj_qa['q'] : '' ) . '</strong>, ' . esc_html( $sssj_a ) . '</li>';
			}
			echo '</ul>';
		}
		echo '<form method="post" action="' . $action . '" class="sssj-row">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<input type="hidden" name="action" value="sssj_app_status" />';
		echo '<input type="hidden" name="app_id" value="' . esc_attr( $a->id ) . '" />';
		echo '<input type="hidden" name="sssj_status_nonce" value="' . esc_attr( $nonce ) . '" />';
		echo '<select class="sssj-select" name="status">';
		$status_opts = Shuffles_SSJ_Applications::statuses_for_mode( $mode );
		// Always keep the current status visible even if it's outside the mode's offered set.
		if ( ! in_array( (string) $a->status, $status_opts, true ) ) {
			array_unshift( $status_opts, (string) $a->status );
		}
		foreach ( $status_opts as $s ) {
			echo '<option value="' . esc_attr( $s ) . '" ' . selected( (string) $a->status, $s, false ) . '>' . esc_html( Shuffles_SSJ_Applications::status_label( $s ) ) . '</option>';
		}
		echo '</select> <button class="sssj-btn sssj-btn--secondary sssj-btn--sm" type="submit">' . esc_html__( 'Update', 'shuffles-social-services-jobs' ) . '</button>';
		echo '</form>';
		// Status history (full pipeline mode only).
		if ( 'full' === $mode ) {
			$hist = Shuffles_SSJ_Applications::history( $a );
			if ( $hist ) {
				echo '<details class="sssj-apphist"><summary>' . esc_html__( 'Status history', 'shuffles-social-services-jobs' ) . '</summary><ul class="ul-disc" style="margin:6px 0 0 18px">';
				foreach ( $hist as $h ) {
					$when = isset( $h['at'] ) ? (string) $h['at'] : '';
					echo '<li>' . esc_html( Shuffles_SSJ_Applications::status_label( isset( $h['s'] ) ? $h['s'] : '' ) . ( $when ? ', ' . $when : '' ) ) . '</li>';
				}
				echo '</ul></details>';
			}
		}
		echo '</div>';
	}
};
?>
<div class="sssj sssj--dashboard">

	<?php
	$react_note = isset( $_GET['sssj_react'] ) ? sanitize_key( wp_unslash( $_GET['sssj_react'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( 'ok' === $react_note ) {
		echo '<div class="sssj-panel"><p class="sssj-badge sssj-badge--verified">' . esc_html__( 'Reopened, your listing is live again with a fresh close date.', 'shuffles-social-services-jobs' ) . '</p></div>';
	}
	$close_note = isset( $_GET['sssj_close'] ) ? sanitize_key( wp_unslash( $_GET['sssj_close'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( 'filled' === $close_note ) {
		echo '<div class="sssj-panel"><p class="sssj-badge sssj-badge--verified">' . esc_html__( 'Marked as filled and closed. You can reopen it any time.', 'shuffles-social-services-jobs' ) . '</p></div>';
	} elseif ( 'closed' === $close_note ) {
		echo '<div class="sssj-panel"><p class="sssj-badge sssj-badge--verified">' . esc_html__( 'Closed. You can reopen it any time.', 'shuffles-social-services-jobs' ) . '</p></div>';
	}
	?>

	<?php echo Shuffles_SSJ_Shortcodes::render_my_applications( $uid ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<div class="sssj-panel">
		<h2><?php esc_html_e( 'My job ads', 'shuffles-social-services-jobs' ); ?></h2>
		<?php
		$jobs = get_posts( array( 'post_type' => 'sssj_job', 'post_status' => 'any', 'author' => $uid, 'posts_per_page' => 50 ) );
		if ( empty( $jobs ) ) {
			echo '<p class="description">' . esc_html__( 'You have not posted any jobs.', 'shuffles-social-services-jobs' ) . '</p>';
		} else {
			foreach ( $jobs as $j ) {
				$feat = get_post_meta( $j->ID, 'is_promoted', true ) ? ' <span class="sssj-badge sssj-badge--featured">' . esc_html__( '★ Featured', 'shuffles-social-services-jobs' ) . '</span>' : '';
				echo '<h3 style="margin:14px 0 4px"><a href="' . esc_url( (string) get_permalink( $j ) ) . '">' . esc_html( get_the_title( $j ) ) . '</a> <span class="sssj-badge ' . esc_attr( Shuffles_SSJ_Shortcodes::listing_state( $j )['class'] ) . '">' . esc_html( Shuffles_SSJ_Shortcodes::listing_state( $j )['label'] ) . '</span>' . $feat . '</h3>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $lifecycle( $j ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$render_apps( 'job', $j->ID );
			}
		}
		?>
	</div>

	<div class="sssj-panel">
		<h2><?php esc_html_e( 'My participant requests', 'shuffles-social-services-jobs' ); ?></h2>
		<?php
		$needs = get_posts( array( 'post_type' => 'sssj_need', 'post_status' => 'any', 'author' => $uid, 'posts_per_page' => 50 ) );
		if ( empty( $needs ) ) {
			echo '<p class="description">' . esc_html__( 'You have not posted any participant requests.', 'shuffles-social-services-jobs' ) . '</p>';
		} else {
			foreach ( $needs as $n ) {
				echo '<h3 style="margin:14px 0 4px">' . esc_html( get_the_title( $n ) ) . ' <span class="sssj-badge ' . esc_attr( Shuffles_SSJ_Shortcodes::listing_state( $n )['class'] ) . '">' . esc_html( Shuffles_SSJ_Shortcodes::listing_state( $n )['label'] ) . '</span></h3>';
				echo $lifecycle( $n ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$render_apps( 'need', $n->ID );
			}
		}
		?>
	</div>
</div>
