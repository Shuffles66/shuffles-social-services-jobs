<?php
/**
 * Internal messaging relay. First contact never exposes an email address; participant
 * identities stay pseudonymous (the worker sees the participant_ref, not the nominee's name).
 * Threads are seeded when someone applies to a job / responds to a need.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Messaging {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sssj_message';
	}

	/** Page hosting [sssj_messages]; filterable. */
	public static function inbox_url() {
		$pid = (int) ( new Shuffles_SSJ_Settings() )->get( 'page_messages', 0 );
		$url = $pid ? get_permalink( $pid ) : home_url( '/' );
		return apply_filters( 'shuffles_ssj_messages_url', $url ? $url : home_url( '/' ) );
	}

	/**
	 * Send a message. Creates/reuses a thread between the pair for the given context.
	 *
	 * @return int thread_id (0 on failure)
	 */
	public static function send( $from, $to, $body, $ctype = '', $cid = 0, $thread_id = 0 ) {
		global $wpdb;
		$t    = self::table();
		$from = (int) $from;
		$to   = (int) $to;
		if ( $from <= 0 || $to <= 0 || $from === $to ) {
			return 0;
		}
		$ctype = sanitize_key( (string) $ctype );
		$cid   = (int) $cid;
		$body  = trim( (string) $body );
		if ( '' === $body ) {
			$body = __( '(started a conversation)', 'shuffles-social-services-jobs' );
		}

		if ( ! $thread_id ) {
			$thread_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT thread_id FROM {$t} WHERE ( ( from_user_id = %d AND to_user_id = %d ) OR ( from_user_id = %d AND to_user_id = %d ) ) AND context_entity_type = %s AND context_entity_id = %d ORDER BY id ASC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$from, $to, $to, $from, $ctype, $cid
				)
			);
		}

		$wpdb->insert(
			$t,
			array(
				'thread_id'           => $thread_id ? $thread_id : 0,
				'from_user_id'        => $from,
				'to_user_id'          => $to,
				'context_entity_type' => $ctype,
				'context_entity_id'   => $cid,
				'body'                => wp_kses_post( $body ),
				'created_at'          => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%s', '%d', '%s', '%s' )
		);
		$id = (int) $wpdb->insert_id;
		if ( ! $thread_id ) {
			$thread_id = $id;
			$wpdb->update( $t, array( 'thread_id' => $id ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
		}
		self::notify( $to );
		return $thread_id;
	}

	public static function is_party( $thread_id, $uid ) {
		global $wpdb;
		$t = self::table();
		return (bool) $wpdb->get_var(
			$wpdb->prepare( "SELECT 1 FROM {$t} WHERE thread_id = %d AND ( from_user_id = %d OR to_user_id = %d ) LIMIT 1", (int) $thread_id, (int) $uid, (int) $uid ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	/** Threads involving a user, newest first, with unread counts. */
	public static function threads_for( $uid ) {
		global $wpdb;
		$t   = self::table();
		$uid = (int) $uid;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT thread_id, MAX(created_at) AS last_at, SUM( CASE WHEN to_user_id = %d AND read_at IS NULL THEN 1 ELSE 0 END ) AS unread FROM {$t} WHERE from_user_id = %d OR to_user_id = %d GROUP BY thread_id ORDER BY last_at DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$uid, $uid, $uid
			)
		);
		return $rows ? $rows : array();
	}

	/** Messages in a thread (only if the viewer is a party); marks them read. */
	public static function messages( $thread_id, $uid, $mark_read = true ) {
		global $wpdb;
		$t = self::table();
		if ( ! self::is_party( $thread_id, $uid ) ) {
			return array();
		}
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE thread_id = %d ORDER BY created_at ASC, id ASC", (int) $thread_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $mark_read ) {
			$wpdb->query( $wpdb->prepare( "UPDATE {$t} SET read_at = %s WHERE thread_id = %d AND to_user_id = %d AND read_at IS NULL", current_time( 'mysql' ), (int) $thread_id, (int) $uid ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		return $rows ? $rows : array();
	}

	public static function last( $thread_id ) {
		global $wpdb;
		$t = self::table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE thread_id = %d ORDER BY id DESC LIMIT 1", (int) $thread_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/** Privacy-aware label for the other party (participant_ref pseudonym for need contexts). */
	public static function other_label( $msg, $uid ) {
		$uid   = (int) $uid;
		$other = ( (int) $msg->from_user_id === $uid ) ? (int) $msg->to_user_id : (int) $msg->from_user_id;
		if ( 'need' === $msg->context_entity_type && $msg->context_entity_id ) {
			$author = (int) get_post_field( 'post_author', (int) $msg->context_entity_id );
			if ( $author !== $uid ) {
				$ref = (string) get_post_meta( (int) $msg->context_entity_id, 'participant_ref', true );
				return $ref ? $ref : __( 'Participant', 'shuffles-social-services-jobs' );
			}
		}
		$u = get_userdata( $other );
		return $u ? $u->display_name : __( 'User', 'shuffles-social-services-jobs' );
	}

	public static function context_label( $msg ) {
		if ( ! empty( $msg->context_entity_id ) ) {
			$tt = get_the_title( (int) $msg->context_entity_id );
			if ( $tt ) {
				return $tt;
			}
		}
		return '';
	}

	/** Email the recipient a no-content notice (never the sender's address or the message body). */
	private static function notify( $to ) {
		$u = get_userdata( (int) $to );
		if ( ! $u || empty( $u->user_email ) ) {
			return;
		}
		$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		/* translators: %s: site name */
		$subject = sprintf( __( '[%s] You have a new message', 'shuffles-social-services-jobs' ), $site );
		$message = __( 'You have a new message about a listing. Log in to read and reply:', 'shuffles-social-services-jobs' ) . "\n\n" . self::inbox_url();
		wp_mail( $u->user_email, $subject, $message );
	}
}
