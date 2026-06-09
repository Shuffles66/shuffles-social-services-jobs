<?php
/**
 * Org team manager (D). Vars: $org_id (int), $managed (int[] of orgs the user administers).
 * Theme override: wp-content/themes/<theme>/shuffles-jobs/org-team.php
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$org_id  = isset( $org_id ) ? (int) $org_id : 0;
$managed = isset( $managed ) ? (array) $managed : array();
if ( ! $org_id || ! class_exists( 'Shuffles_SSJ_Org_Team' ) ) {
	return;
}

$uid      = get_current_user_id();
$is_admin = Shuffles_SSJ_Org_Team::is_admin( $org_id, $uid );
$roster   = Shuffles_SSJ_Org_Team::roster( $org_id );
$org_name = get_the_title( $org_id );
$action   = admin_url( 'admin-post.php' );

// Notice from a just-completed action.
$notice = isset( $_GET['sssj_team'] ) ? sanitize_key( wp_unslash( $_GET['sssj_team'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$notices = array(
	'added'    => __( 'Team member added.', 'shuffles-social-services-jobs' ),
	'updated'  => __( 'Role updated.', 'shuffles-social-services-jobs' ),
	'removed'  => __( 'Team member removed.', 'shuffles-social-services-jobs' ),
	'approved' => __( 'Request approved, they’re now on the team.', 'shuffles-social-services-jobs' ),
	'declined' => __( 'Request declined.', 'shuffles-social-services-jobs' ),
	'nouser'   => __( 'No account was found for that email or username. Ask them to sign up first, then add them.', 'shuffles-social-services-jobs' ),
	'err'      => __( 'That change could not be applied.', 'shuffles-social-services-jobs' ),
);
$requests = $is_admin ? Shuffles_SSJ_Org_Team::join_requests( $org_id ) : array();
?>
<div class="sssj sssj--team">
	<div class="sssj-panel">
		<h3 style="margin-top:0"><?php echo esc_html( sprintf( __( 'Team, %s', 'shuffles-social-services-jobs' ), $org_name ) ); ?></h3>

		<?php if ( count( $managed ) > 1 ) : ?>
			<form method="get" class="sssj-row" style="margin-bottom:10px">
				<label><?php esc_html_e( 'Organisation:', 'shuffles-social-services-jobs' ); ?>
					<select class="sssj-select" name="sssj_org_id" onchange="this.form.submit()">
						<?php foreach ( $managed as $oid ) : ?>
							<option value="<?php echo esc_attr( $oid ); ?>" <?php selected( $oid, $org_id ); ?>><?php echo esc_html( get_the_title( $oid ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</form>
		<?php endif; ?>

		<?php if ( $notice && isset( $notices[ $notice ] ) ) : ?>
			<div class="sssj-note <?php echo esc_attr( in_array( $notice, array( 'nouser', 'err' ), true ) ? 'sssj-note--warn' : 'sssj-note--ok' ); ?>"><?php echo esc_html( $notices[ $notice ] ); ?></div>
		<?php endif; ?>

		<table class="sssj-team-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Member', 'shuffles-social-services-jobs' ); ?></th>
					<th><?php esc_html_e( 'Email', 'shuffles-social-services-jobs' ); ?></th>
					<th><?php esc_html_e( 'Role', 'shuffles-social-services-jobs' ); ?></th>
					<?php if ( $is_admin ) : ?><th><?php esc_html_e( 'Actions', 'shuffles-social-services-jobs' ); ?></th><?php endif; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $roster as $r ) : ?>
					<tr>
						<td><?php echo esc_html( $r['name'] ); ?><?php if ( (int) $r['user_id'] === $uid ) : ?> <span class="sssj-badge sssj-badge--sm"><?php esc_html_e( 'you', 'shuffles-social-services-jobs' ); ?></span><?php endif; ?></td>
						<td><?php echo esc_html( $r['email'] ); ?></td>
						<td>
							<?php if ( $is_admin && 'owner' !== $r['role'] ) : ?>
								<form method="post" action="<?php echo esc_url( $action ); ?>" class="sssj-inline-form">
									<?php wp_nonce_field( 'sssj_org_team', 'sssj_team_nonce' ); ?>
									<input type="hidden" name="action" value="sssj_org_team" />
									<input type="hidden" name="op" value="role" />
									<input type="hidden" name="org_id" value="<?php echo esc_attr( $org_id ); ?>" />
									<input type="hidden" name="user_id" value="<?php echo esc_attr( $r['user_id'] ); ?>" />
									<select class="sssj-select sssj-select--sm" name="role" onchange="this.form.submit()">
										<option value="member" <?php selected( $r['role'], 'member' ); ?>><?php esc_html_e( 'Member', 'shuffles-social-services-jobs' ); ?></option>
										<option value="admin" <?php selected( $r['role'], 'admin' ); ?>><?php esc_html_e( 'Admin', 'shuffles-social-services-jobs' ); ?></option>
									</select>
								</form>
							<?php else : ?>
								<span class="sssj-badge <?php echo esc_attr( 'owner' === $r['role'] ? 'sssj-badge--verified' : '' ); ?>"><?php echo esc_html( Shuffles_SSJ_Org_Team::role_label( $r['role'] ) ); ?></span>
							<?php endif; ?>
						</td>
						<?php if ( $is_admin ) : ?>
							<td>
								<?php if ( 'owner' !== $r['role'] ) : ?>
									<form method="post" action="<?php echo esc_url( $action ); ?>" class="sssj-inline-form" onsubmit="return confirm('<?php echo esc_js( __( 'Remove this person from the team? Their account is not deleted.', 'shuffles-social-services-jobs' ) ); ?>');">
										<?php wp_nonce_field( 'sssj_org_team', 'sssj_team_nonce' ); ?>
										<input type="hidden" name="action" value="sssj_org_team" />
										<input type="hidden" name="op" value="remove" />
										<input type="hidden" name="org_id" value="<?php echo esc_attr( $org_id ); ?>" />
										<input type="hidden" name="user_id" value="<?php echo esc_attr( $r['user_id'] ); ?>" />
										<button type="submit" class="sssj-btn sssj-btn--danger sssj-btn--sm"><?php esc_html_e( 'Remove', 'shuffles-social-services-jobs' ); ?></button>
									</form>
								<?php else : ?>
									<span class="description">-</span>
								<?php endif; ?>
							</td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $is_admin && $requests ) : ?>
			<h4><?php echo esc_html( sprintf( __( 'Requests to join (%d)', 'shuffles-social-services-jobs' ), count( $requests ) ) ); ?></h4>
			<table class="sssj-team-table">
				<tbody>
					<?php foreach ( $requests as $rq ) : ?>
						<tr>
							<td>
								<strong><?php echo esc_html( $rq['name'] ); ?></strong><br />
								<span class="description"><?php echo esc_html( $rq['email'] ); ?></span>
								<?php if ( ! empty( $rq['msg'] ) ) : ?><br /><span class="description">“<?php echo esc_html( $rq['msg'] ); ?>”</span><?php endif; ?>
							</td>
							<td style="white-space:nowrap">
								<form method="post" action="<?php echo esc_url( $action ); ?>" class="sssj-inline-form">
									<?php wp_nonce_field( 'sssj_org_team', 'sssj_team_nonce' ); ?>
									<input type="hidden" name="action" value="sssj_org_team" />
									<input type="hidden" name="op" value="approve" />
									<input type="hidden" name="org_id" value="<?php echo esc_attr( $org_id ); ?>" />
									<input type="hidden" name="user_id" value="<?php echo esc_attr( $rq['user_id'] ); ?>" />
									<select class="sssj-select sssj-select--sm" name="role">
										<option value="member"><?php esc_html_e( 'as Member', 'shuffles-social-services-jobs' ); ?></option>
										<option value="admin"><?php esc_html_e( 'as Admin', 'shuffles-social-services-jobs' ); ?></option>
									</select>
									<button type="submit" class="sssj-btn sssj-btn--primary sssj-btn--sm"><?php esc_html_e( 'Approve', 'shuffles-social-services-jobs' ); ?></button>
								</form>
								<form method="post" action="<?php echo esc_url( $action ); ?>" class="sssj-inline-form" onsubmit="return confirm('<?php echo esc_js( __( 'Decline this request?', 'shuffles-social-services-jobs' ) ); ?>');">
									<?php wp_nonce_field( 'sssj_org_team', 'sssj_team_nonce' ); ?>
									<input type="hidden" name="action" value="sssj_org_team" />
									<input type="hidden" name="op" value="decline" />
									<input type="hidden" name="org_id" value="<?php echo esc_attr( $org_id ); ?>" />
									<input type="hidden" name="user_id" value="<?php echo esc_attr( $rq['user_id'] ); ?>" />
									<button type="submit" class="sssj-btn sssj-btn--ghost sssj-btn--sm"><?php esc_html_e( 'Decline', 'shuffles-social-services-jobs' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<?php if ( $is_admin ) : ?>
			<h4><?php esc_html_e( 'Add a team member', 'shuffles-social-services-jobs' ); ?></h4>
			<p class="description"><?php esc_html_e( 'Enter the email address or username of someone who already has an account. We never create accounts on their behalf, ask them to sign up first if they haven’t.', 'shuffles-social-services-jobs' ); ?></p>
			<form method="post" action="<?php echo esc_url( $action ); ?>" class="sssj-row">
				<?php wp_nonce_field( 'sssj_org_team', 'sssj_team_nonce' ); ?>
				<input type="hidden" name="action" value="sssj_org_team" />
				<input type="hidden" name="op" value="add" />
				<input type="hidden" name="org_id" value="<?php echo esc_attr( $org_id ); ?>" />
				<input class="sssj-input" type="text" name="member" placeholder="<?php esc_attr_e( 'Email or username', 'shuffles-social-services-jobs' ); ?>" required />
				<select class="sssj-select sssj-select--sm" name="role">
					<option value="member"><?php esc_html_e( 'Member', 'shuffles-social-services-jobs' ); ?></option>
					<option value="admin"><?php esc_html_e( 'Admin (can manage the team)', 'shuffles-social-services-jobs' ); ?></option>
				</select>
				<button type="submit" class="sssj-btn sssj-btn--primary sssj-btn--sm"><?php esc_html_e( 'Add member', 'shuffles-social-services-jobs' ); ?></button>
			</form>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'Only an organisation admin can add or remove team members.', 'shuffles-social-services-jobs' ); ?></p>
		<?php endif; ?>
	</div>
</div>
