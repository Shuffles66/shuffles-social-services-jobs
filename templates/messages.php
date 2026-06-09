<?php
/**
 * Messages inbox + thread view. Relay only, no email addresses shown; participant identities
 * stay pseudonymous. Theme override: themes/<theme>/shuffles-jobs/messages.php
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	echo '<div class="sssj"><div class="sssj-panel"><p>' . esc_html__( 'Log in to view your messages.', 'shuffles-social-services-jobs' )
		. ' <a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' . esc_url( Shuffles_SSJ_Shortcodes::login_url( get_permalink() ) ) . '">' . esc_html__( 'Log in', 'shuffles-social-services-jobs' ) . '</a></p></div></div>';
	return;
}

$uid    = get_current_user_id();
$thread = isset( $_GET['sssj_thread'] ) ? (int) $_GET['sssj_thread'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$base   = get_permalink();
$msgst  = isset( $_GET['sssj_msg'] ) ? sanitize_key( wp_unslash( $_GET['sssj_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="sssj sssj--messages">
<?php
if ( $thread && Shuffles_SSJ_Messaging::is_party( $thread, $uid ) ) :
	$msgs  = Shuffles_SSJ_Messaging::messages( $thread, $uid );
	$last  = ! empty( $msgs ) ? end( $msgs ) : null;
	$label = $last ? Shuffles_SSJ_Messaging::other_label( $last, $uid ) : __( 'Conversation', 'shuffles-social-services-jobs' );
	$ctx   = $last ? Shuffles_SSJ_Messaging::context_label( $last ) : '';
	?>
	<div class="sssj-panel">
		<p><a href="<?php echo esc_url( remove_query_arg( array( 'sssj_thread', 'sssj_msg' ), $base ) ); ?>">&laquo; <?php esc_html_e( 'All conversations', 'shuffles-social-services-jobs' ); ?></a></p>
		<h2 style="margin:0"><?php echo esc_html( $label ); ?></h2>
		<?php if ( $ctx ) : ?><p class="description"><?php echo esc_html( sprintf( __( 'Re: %s', 'shuffles-social-services-jobs' ), $ctx ) ); ?></p><?php endif; ?>
		<?php if ( '1' === $msgst ) : ?><p class="sssj-badge sssj-badge--verified"><?php esc_html_e( 'Sent.', 'shuffles-social-services-jobs' ); ?></p><?php endif; ?>

		<div class="sssj-stack" style="margin:12px 0">
			<?php
			foreach ( $msgs as $m ) :
				$mine = ( (int) $m->from_user_id === (int) $uid );
				?>
				<div class="sssj-card" style="<?php echo $mine ? 'margin-left:auto;max-width:80%;background:#eaf3fb' : 'margin-right:auto;max-width:80%'; ?>">
					<div class="description"><?php echo esc_html( $mine ? __( 'You', 'shuffles-social-services-jobs' ) : Shuffles_SSJ_Messaging::other_label( $m, $uid ) ); ?> · <?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' H:i', $m->created_at ) ); ?></div>
					<div><?php echo esc_html( wp_strip_all_tags( $m->body ) ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sssj-stack">
			<input type="hidden" name="action" value="sssj_send_message" />
			<input type="hidden" name="thread_id" value="<?php echo esc_attr( $thread ); ?>" />
			<?php wp_nonce_field( 'sssj_send_message', 'sssj_msg_nonce' ); ?>
			<textarea class="sssj-textarea" name="body" rows="3" required placeholder="<?php esc_attr_e( 'Write a reply…', 'shuffles-social-services-jobs' ); ?>"></textarea>
			<div><button class="sssj-btn sssj-btn--primary" type="submit"><?php esc_html_e( 'Send', 'shuffles-social-services-jobs' ); ?></button></div>
		</form>
	</div>

<?php else : ?>
	<div class="sssj-panel">
		<h2 style="margin-top:0"><?php esc_html_e( 'Messages', 'shuffles-social-services-jobs' ); ?></h2>
		<?php
		$threads = Shuffles_SSJ_Messaging::threads_for( $uid );
		if ( empty( $threads ) ) :
			?>
			<p class="description"><?php esc_html_e( 'No conversations yet. When you apply to a job or respond to a participant request, a private conversation starts here.', 'shuffles-social-services-jobs' ); ?></p>
		<?php else : ?>
			<?php
			foreach ( $threads as $th ) :
				$last = Shuffles_SSJ_Messaging::last( $th->thread_id );
				if ( ! $last ) {
					continue;
				}
				$label  = Shuffles_SSJ_Messaging::other_label( $last, $uid );
				$ctx    = Shuffles_SSJ_Messaging::context_label( $last );
				$unread = (int) $th->unread;
				$url    = add_query_arg( 'sssj_thread', (int) $th->thread_id, $base );
				?>
				<a class="sssj-card" style="display:block;text-decoration:none;color:inherit;margin:8px 0" href="<?php echo esc_url( $url ); ?>">
					<div class="sssj-row" style="justify-content:space-between">
						<strong><?php echo esc_html( $label ); ?></strong>
						<?php if ( $unread > 0 ) : ?><span class="sssj-badge sssj-badge--need"><?php echo esc_html( sprintf( _n( '%d new', '%d new', $unread, 'shuffles-social-services-jobs' ), $unread ) ); ?></span><?php endif; ?>
					</div>
					<?php if ( $ctx ) : ?><div class="description"><?php echo esc_html( sprintf( __( 'Re: %s', 'shuffles-social-services-jobs' ), $ctx ) ); ?></div><?php endif; ?>
					<div><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $last->body ), 16 ) ); ?></div>
				</a>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
<?php endif; ?>
</div>
