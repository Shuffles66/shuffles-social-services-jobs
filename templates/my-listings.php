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
		. ' <a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Log in', 'shuffles-social-services-jobs' ) . '</a></p></div></div>';
	return;
}

$uid    = get_current_user_id();
$nonce  = wp_create_nonce( 'sssj_app_status' );
$action = esc_url( admin_url( 'admin-post.php' ) );

$badge = function ( $status ) {
	$cls = in_array( $status, array( 'offer', 'shortlisted', 'interview' ), true ) ? ' sssj-badge--verified' : '';
	return '<span class="sssj-badge' . $cls . '">' . esc_html( ucfirst( $status ) ) . '</span>';
};

/** Render the applicant rows + status control for an entity owned by the current user. */
$render_apps = function ( $type, $entity_id ) use ( $badge, $nonce, $action ) {
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
		echo '<form method="post" action="' . $action . '" class="sssj-row">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<input type="hidden" name="action" value="sssj_app_status" />';
		echo '<input type="hidden" name="app_id" value="' . esc_attr( $a->id ) . '" />';
		echo '<input type="hidden" name="sssj_status_nonce" value="' . esc_attr( $nonce ) . '" />';
		echo '<select class="sssj-select" name="status">';
		foreach ( Shuffles_SSJ_Applications::statuses() as $s ) {
			echo '<option value="' . esc_attr( $s ) . '" ' . selected( (string) $a->status, $s, false ) . '>' . esc_html( ucfirst( $s ) ) . '</option>';
		}
		echo '</select> <button class="sssj-btn sssj-btn--secondary sssj-btn--sm" type="submit">' . esc_html__( 'Update', 'shuffles-social-services-jobs' ) . '</button>';
		echo '</form>';
		echo '</div>';
	}
};
?>
<div class="sssj sssj--dashboard">

	<div class="sssj-panel">
		<h2><?php esc_html_e( 'My applications', 'shuffles-social-services-jobs' ); ?></h2>
		<?php
		$mine = Shuffles_SSJ_Applications::for_applicant( $uid );
		if ( empty( $mine ) ) {
			echo '<p class="description">' . esc_html__( 'You have not applied to anything yet.', 'shuffles-social-services-jobs' ) . '</p>';
		} else {
			foreach ( $mine as $a ) {
				$eid   = $a->job_id ? (int) $a->job_id : (int) $a->need_id;
				$title = get_the_title( $eid );
				echo '<div class="sssj-row" style="justify-content:space-between;border-bottom:1px solid #e2e8f0;padding:6px 0">';
				echo '<span>' . esc_html( $title ? $title : '#' . $eid ) . '</span> ' . $badge( (string) $a->status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '</div>';
			}
		}
		?>
	</div>

	<div class="sssj-panel">
		<h2><?php esc_html_e( 'My job ads', 'shuffles-social-services-jobs' ); ?></h2>
		<?php
		$jobs = get_posts( array( 'post_type' => 'sssj_job', 'post_status' => 'any', 'author' => $uid, 'posts_per_page' => 50 ) );
		if ( empty( $jobs ) ) {
			echo '<p class="description">' . esc_html__( 'You have not posted any jobs.', 'shuffles-social-services-jobs' ) . '</p>';
		} else {
			foreach ( $jobs as $j ) {
				$feat = get_post_meta( $j->ID, 'is_promoted', true ) ? ' <span class="sssj-badge sssj-badge--featured">' . esc_html__( '★ Featured', 'shuffles-social-services-jobs' ) . '</span>' : '';
				echo '<h3 style="margin:14px 0 4px"><a href="' . esc_url( (string) get_permalink( $j ) ) . '">' . esc_html( get_the_title( $j ) ) . '</a> <span class="sssj-badge">' . esc_html( get_post_status( $j ) ) . '</span>' . $feat . '</h3>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
				echo '<h3 style="margin:14px 0 4px">' . esc_html( get_the_title( $n ) ) . ' <span class="sssj-badge">' . esc_html( get_post_status( $n ) ) . '</span></h3>';
				$render_apps( 'need', $n->ID );
			}
		}
		?>
	</div>
</div>
