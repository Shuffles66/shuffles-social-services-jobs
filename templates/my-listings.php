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

// AI match ranking is a proof of concept; the advertiser can switch it off (persisted per user).
if ( isset( $_GET['sssj_ai'], $_GET['_sssj_ai_n'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_sssj_ai_n'] ) ), 'sssj_ai_rank' ) ) {
	update_user_meta( $uid, '_sssj_ai_rank', ( 'off' === sanitize_key( wp_unslash( $_GET['sssj_ai'] ) ) ) ? '0' : '1' );
}
$ai_on = ( '0' !== (string) get_user_meta( $uid, '_sssj_ai_rank', true ) );

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

/** Render the applicant rows (sortable / filterable, grouped) + status control, for an entity the user owns. */
$render_apps = function ( $type, $entity_id ) use ( $badge, $nonce, $action, $ai_on ) {
	$mode = ( 'job' === $type ) ? ( get_post_meta( $entity_id, 'application_mode', true ) ? get_post_meta( $entity_id, 'application_mode', true ) : 'full' ) : 'full';
	$apps = Shuffles_SSJ_Applications::for_entity( $type, $entity_id );
	if ( empty( $apps ) ) {
		echo '<p class="description">' . esc_html__( 'No applications yet.', 'shuffles-social-services-jobs' ) . '</p>';
		return;
	}
	$basis = ( 'need' === $type ) ? 'abn' : ( ( 'abn' === (string) get_post_meta( $entity_id, 'engagement_basis', true ) ) ? 'abn' : 'tfn' );
	$rows  = array();
	foreach ( $apps as $a ) {
		$sc     = ( $ai_on && 'job' === $type ) ? Shuffles_SSJ_Shortcodes::applicant_match_score( $entity_id, (int) $a->applicant_user_id ) : null;
		$rows[] = array( 'a' => $a, 'score' => $sc );
	}
	if ( $ai_on && 'job' === $type ) {
		usort( $rows, function ( $x, $y ) {
			$sx = $x['score'] ? (int) $x['score']['score'] : -1;
			$sy = $y['score'] ? (int) $y['score']['score'] : -1;
			return $sy <=> $sx;
		} );
	}
	echo '<div class="sssj-applicants" data-applicants>';
	foreach ( $rows as $row ) {
		$a  = $row['a'];
		$sc = $row['score'];
		$u  = get_userdata( (int) $a->applicant_user_id );
		$nm = $u ? $u->display_name : __( 'Applicant', 'shuffles-social-services-jobs' );
		$ts = ! empty( $a->created_at ) ? strtotime( $a->created_at ) : 0;
		echo '<div class="sssj-card sssj-applicant" style="margin:8px 0" data-app-status="' . esc_attr( (string) $a->status ) . '" data-app-applied="' . esc_attr( (int) $ts ) . '" data-app-score="' . esc_attr( $sc ? (int) $sc['score'] : -1 ) . '" data-app-name="' . esc_attr( strtolower( (string) $nm ) ) . '">';
		echo '<div class="sssj-row" style="gap:8px;flex-wrap:wrap;align-items:center"><strong>' . esc_html( $nm ) . '</strong> ' . $badge( (string) $a->status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( $sc ) {
			$tip = $sc['reasons'] ? implode( ', ', array_map( 'sanitize_text_field', (array) $sc['reasons'] ) ) : '';
			echo ' <span class="sssj-badge sssj-badge--match" title="' . esc_attr( $tip ) . '">' . esc_html( sprintf( __( 'Match %d%%', 'shuffles-social-services-jobs' ), (int) $sc['score'] ) ) . ' <span class="sssj-beta">' . esc_html__( 'beta', 'shuffles-social-services-jobs' ) . '</span></span>';
		}
		echo '</div>';
		if ( $ts ) {
			echo '<p class="description" style="margin:2px 0 0">' . esc_html( sprintf( __( 'Applied %s', 'shuffles-social-services-jobs' ), mysql2date( get_option( 'date_format' ), $a->created_at ) ) ) . '</p>';
		}
		if ( ! empty( $a->cover_message ) ) {
			echo '<p>' . esc_html( wp_trim_words( wp_strip_all_tags( $a->cover_message ), 50 ) ) . '</p>';
		}
		if ( ! empty( $a->resume_id ) && class_exists( 'Shuffles_SSJ_Resumes' ) ) {
			echo '<p>&#128196; <a href="' . esc_url( Shuffles_SSJ_Resumes::file_url( (int) $a->resume_id ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View résumé', 'shuffles-social-services-jobs' ) . '</a></p>';
		}
		$sssj_ex = Shuffles_SSJ_Applications::extra( $a );
		if ( ! empty( $sssj_ex['availability'] ) ) {
			echo '<p><strong>' . esc_html__( 'Availability:', 'shuffles-social-services-jobs' ) . '</strong> ' . esc_html( $sssj_ex['availability'] ) . '</p>';
		}
		if ( ! empty( $sssj_ex['start_date'] ) ) {
			echo '<p><strong>' . esc_html__( 'Earliest start:', 'shuffles-social-services-jobs' ) . '</strong> ' . esc_html( $sssj_ex['start_date'] ) . '</p>';
		}
		if ( ! empty( $sssj_ex['right_to_work'] ) ) {
			echo '<p>&#10003; ' . esc_html__( 'Confirmed right to work in Australia', 'shuffles-social-services-jobs' ) . '</p>';
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
		if ( ! in_array( (string) $a->status, $status_opts, true ) ) {
			array_unshift( $status_opts, (string) $a->status );
		}
		foreach ( $status_opts as $s ) {
			echo '<option value="' . esc_attr( $s ) . '" ' . selected( (string) $a->status, $s, false ) . '>' . esc_html( Shuffles_SSJ_Applications::status_label( $s ) ) . '</option>';
		}
		echo '</select> <button class="sssj-btn sssj-btn--secondary sssj-btn--sm" type="submit">' . esc_html__( 'Update', 'shuffles-social-services-jobs' ) . '</button>';
		echo '</form>';
		echo '<details class="sssj-flow-details"><summary>' . esc_html__( 'Review steps', 'shuffles-social-services-jobs' ) . '</summary>' . Shuffles_SSJ_Shortcodes::application_workflow( $basis, (string) $a->status, array( 'audience' => 'advertiser' ) ) . '</details>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
	echo '</div>';
};?>
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
				$sort_def = $ai_on ? 'score' : 'newest';
				echo '<div class="sssj-applicants-controls" data-applicants-controls>';
				echo '<label>' . esc_html__( 'Sort', 'shuffles-social-services-jobs' ) . ' <select class="sssj-select" data-app-sort data-no-enhance>';
				$sssj_sorts = array( 'score' => __( 'Best match', 'shuffles-social-services-jobs' ), 'newest' => __( 'Newest', 'shuffles-social-services-jobs' ), 'oldest' => __( 'Oldest', 'shuffles-social-services-jobs' ), 'status' => __( 'Stage', 'shuffles-social-services-jobs' ), 'name' => __( 'Name', 'shuffles-social-services-jobs' ) );
				foreach ( $sssj_sorts as $sk => $sl ) { if ( 'score' === $sk && ! $ai_on ) { continue; } echo '<option value="' . esc_attr( $sk ) . '"' . selected( $sort_def, $sk, false ) . '>' . esc_html( $sl ) . '</option>'; }
				echo '</select></label>';
				echo '<label>' . esc_html__( 'Show', 'shuffles-social-services-jobs' ) . ' <select class="sssj-select" data-app-filter data-no-enhance>';
				echo '<option value="all">' . esc_html__( 'All stages', 'shuffles-social-services-jobs' ) . '</option>';
				foreach ( array( 'new', 'viewed', 'shortlisted', 'interview', 'offer', 'hired', 'declined', 'withdrawn' ) as $stk ) { echo '<option value="' . esc_attr( $stk ) . '">' . esc_html( Shuffles_SSJ_Applications::status_label( $stk ) ) . '</option>'; }
				echo '</select></label>';
				$ai_link = wp_nonce_url( add_query_arg( 'sssj_ai', $ai_on ? 'off' : 'on' ), 'sssj_ai_rank', '_sssj_ai_n' );
				echo '<a class="sssj-btn sssj-btn--ghost sssj-btn--sm" href="' . esc_url( $ai_link ) . '#dash-listings">' . esc_html( $ai_on ? __( 'Turn AI match off', 'shuffles-social-services-jobs' ) : __( 'Turn AI match on', 'shuffles-social-services-jobs' ) ) . '</a>';
				echo '</div>';
				if ( $ai_on ) { echo '<p class="description sssj-poc">' . esc_html__( 'AI match ranking is a proof of concept (beta). The percentage is an early estimate from profile overlap (services, location, availability, verification, rate) to help you triage, not a hiring decision. Use the button above to turn it off.', 'shuffles-social-services-jobs' ) . '</p>'; }
			foreach ( $jobs as $j ) {
				$feat = get_post_meta( $j->ID, 'is_promoted', true ) ? ' <span class="sssj-badge sssj-badge--featured">' . esc_html__( '★ Featured', 'shuffles-social-services-jobs' ) . '</span>' : '';
				echo '<h3 style="margin:14px 0 4px"><a href="' . esc_url( (string) get_permalink( $j ) ) . '">' . esc_html( get_the_title( $j ) ) . '</a> <span class="sssj-badge ' . esc_attr( Shuffles_SSJ_Shortcodes::listing_state( $j )['class'] ) . '">' . esc_html( Shuffles_SSJ_Shortcodes::listing_state( $j )['label'] ) . '</span>' . $feat . '</h3>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo Shuffles_SSJ_Shortcodes::listing_audit_line( $j ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
