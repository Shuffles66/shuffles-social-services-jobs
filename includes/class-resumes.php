<?php
/**
 * Stored résumés, a candidate can keep one or more named résumé files against their profile and
 * pick one when applying to a TFN (employee) vacancy.
 *
 * Privacy mirrors credentials: files are stored IN THE DATABASE (this host serves /uploads directly
 * and ignores .htaccess, so a private dir isn't reliable) and streamed only through an authenticated
 * handler. A résumé is viewable by its owner, an admin, and (Phase 2) an employer the candidate has
 * applied to. MIME is validated server-side; filenames are sanitised.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Resumes {

	public static function register() {
		add_action( 'admin_post_sssj_resume_add', array( __CLASS__, 'handle_add' ) );
		add_action( 'admin_post_sssj_resume_delete', array( __CLASS__, 'handle_delete' ) );
		add_action( 'admin_post_sssj_resume_default', array( __CLASS__, 'handle_default' ) );
		add_action( 'admin_post_sssj_resume_file', array( __CLASS__, 'handle_serve' ) );
	}

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sssj_resume';
	}

	/** List columns, never load the (large) blob in list views. */
	const LIST_COLS = 'id, user_id, label, original_name, resume_mime, is_default, created_at';

	private static function allowed_mimes() {
		return apply_filters(
			'shuffles_ssj_resume_mimes',
			array(
				'pdf'  => 'application/pdf',
				'doc'  => 'application/msword',
				'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'rtf'  => 'application/rtf',
				'odt'  => 'application/vnd.oasis.opendocument.text',
			)
		);
	}

	/** Max résumé size in bytes (default 8 MB). */
	private static function max_bytes() {
		return (int) apply_filters( 'shuffles_ssj_resume_max_bytes', 8 * 1024 * 1024 );
	}

	/** Max résumés a member may store (default 5). */
	public static function max_count() {
		return (int) apply_filters( 'shuffles_ssj_resume_max_count', 5 );
	}

	/**
	 * Validate an uploaded résumé and return it base64-encoded for DB storage.
	 *
	 * @param array $file A single $_FILES entry.
	 * @return array|WP_Error { data (base64), mime, original }
	 */
	private static function read_file( $file ) {
		if ( empty( $file ) || ! isset( $file['tmp_name'] ) || UPLOAD_ERR_NO_FILE === (int) $file['error'] ) {
			return new WP_Error( 'nofile', __( 'Please choose a résumé file to upload.', 'shuffles-social-services-jobs' ) );
		}
		if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
			return new WP_Error( 'upload', __( 'The file failed to upload. Please try again.', 'shuffles-social-services-jobs' ) );
		}
		if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new WP_Error( 'badsource', __( 'Invalid upload.', 'shuffles-social-services-jobs' ) );
		}
		if ( (int) $file['size'] > self::max_bytes() ) {
			return new WP_Error( 'toobig', sprintf( __( 'File too large. Maximum %d MB.', 'shuffles-social-services-jobs' ), (int) ( self::max_bytes() / 1048576 ) ) );
		}
		$allowed = self::allowed_mimes();
		$check   = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed );
		$type    = $check['type'];
		if ( ! $check['ext'] || ! $type || ! in_array( $type, array_values( $allowed ), true ) ) {
			return new WP_Error( 'badtype', __( 'Please upload a PDF, Word (DOC/DOCX), RTF or ODT file.', 'shuffles-social-services-jobs' ) );
		}
		$bytes = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( false === $bytes ) {
			return new WP_Error( 'read', __( 'Could not read the file.', 'shuffles-social-services-jobs' ) );
		}
		return array(
			'data'     => base64_encode( $bytes ),
			'mime'     => $type,
			'original' => sanitize_file_name( $file['name'] ),
		);
	}

	/**
	 * Store a résumé for a user.
	 *
	 * @return int|WP_Error new id
	 */
	public static function add( $uid, $label, $file, $make_default = false ) {
		global $wpdb;
		$uid = (int) $uid;
		if ( ! $uid ) {
			return new WP_Error( 'auth', __( 'Please log in.', 'shuffles-social-services-jobs' ) );
		}
		if ( self::count_for_user( $uid ) >= self::max_count() ) {
			return new WP_Error( 'limit', sprintf( __( 'You can store up to %d résumés. Remove one first.', 'shuffles-social-services-jobs' ), self::max_count() ) );
		}
		$stored = self::read_file( $file );
		if ( is_wp_error( $stored ) ) {
			return $stored;
		}
		$label = trim( wp_strip_all_tags( (string) $label ) );
		if ( '' === $label ) {
			$label = preg_replace( '/\.[a-z0-9]+$/i', '', $stored['original'] );
		}
		$first = ( 0 === self::count_for_user( $uid ) );
		$ok    = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'user_id'       => $uid,
				'label'         => substr( $label, 0, 160 ),
				'resume_data'   => $stored['data'],
				'resume_mime'   => $stored['mime'],
				'original_name' => $stored['original'],
				'is_default'    => ( $make_default || $first ) ? 1 : 0,
				'created_at'    => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d', '%s' )
		);
		if ( ! $ok ) {
			return new WP_Error( 'db', __( 'Could not save the résumé.', 'shuffles-social-services-jobs' ) );
		}
		$id = (int) $wpdb->insert_id;
		if ( $make_default || $first ) {
			self::set_default( $id, $uid );
		}
		return $id;
	}

	/** A user's résumés, newest first (no blob loaded). Always an array (empty if table is new/missing). */
	public static function for_user( $uid ) {
		global $wpdb;
		return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT ' . self::LIST_COLS . ' FROM ' . self::table() . ' WHERE user_id = %d ORDER BY is_default DESC, id DESC', (int) $uid ) ); // phpcs:ignore WordPress.DB
	}

	public static function count_for_user( $uid ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE user_id = %d', (int) $uid ) ); // phpcs:ignore WordPress.DB
	}

	/** Full row incl. blob, used only when serving a file. */
	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', (int) $id ) ); // phpcs:ignore WordPress.DB
	}

	/** The user's default résumé id (0 if none). */
	public static function default_id( $uid ) {
		global $wpdb;
		$id = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table() . ' WHERE user_id = %d AND is_default = 1 ORDER BY id DESC LIMIT 1', (int) $uid ) ); // phpcs:ignore WordPress.DB
		if ( ! $id ) {
			$id = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table() . ' WHERE user_id = %d ORDER BY id DESC LIMIT 1', (int) $uid ) ); // phpcs:ignore WordPress.DB
		}
		return (int) $id;
	}

	/** Make $id the user's only default (owner only). */
	public static function set_default( $id, $uid ) {
		global $wpdb;
		$row = self::get( $id );
		if ( ! $row || (int) $row->user_id !== (int) $uid ) {
			return false;
		}
		$wpdb->update( self::table(), array( 'is_default' => 0 ), array( 'user_id' => (int) $uid ), array( '%d' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( self::table(), array( 'is_default' => 1 ), array( 'id' => (int) $id ), array( '%d' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return true;
	}

	/** Delete a résumé (owner or admin). Keeps a default if any remain. */
	public static function delete( $id, $actor_id ) {
		global $wpdb;
		$row = self::get( $id );
		if ( ! $row ) {
			return false;
		}
		if ( (int) $row->user_id !== (int) $actor_id && ! user_can( (int) $actor_id, 'manage_options' ) ) {
			return false;
		}
		$was_default = (int) $row->is_default;
		$uid         = (int) $row->user_id;
		$wpdb->delete( self::table(), array( 'id' => (int) $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( $was_default ) {
			$next = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table() . ' WHERE user_id = %d ORDER BY id DESC LIMIT 1', $uid ) ); // phpcs:ignore WordPress.DB
			if ( $next ) {
				self::set_default( $next, $uid );
			}
		}
		return true;
	}

	/** Can $viewer_id see résumé $id? Owner, admin, or (later) an employer applied to. */
	public static function can_view( $row, $viewer_id ) {
		$viewer_id = (int) $viewer_id;
		if ( ! $row || ! $viewer_id ) {
			return false;
		}
		if ( (int) $row->user_id === $viewer_id || user_can( $viewer_id, 'manage_options' ) ) {
			return true;
		}
		/** Allow an employer the candidate applied to (wired in Phase 2). */
		return (bool) apply_filters( 'shuffles_ssj_resume_can_view', false, $row, $viewer_id );
	}

	/* --------------------------------------------------------------- handlers */

	public static function handle_add() {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Please log in.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_resume_add', 'sssj_resume_nonce' );
		$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		$label    = isset( $_POST['label'] ) ? wp_unslash( $_POST['label'] ) : '';
		$def      = ! empty( $_POST['make_default'] );
		$file     = isset( $_FILES['resume'] ) ? $_FILES['resume'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$res      = self::add( get_current_user_id(), $label, $file, $def );
		wp_safe_redirect( add_query_arg( 'sssj_resume', is_wp_error( $res ) ? 'error' : 'added', remove_query_arg( 'sssj_resume', $redirect ) ) );
		exit;
	}

	public static function handle_delete() {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Please log in.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_resume_delete', 'sssj_resume_nonce' );
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		self::delete( $id, get_current_user_id() );
		$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		wp_safe_redirect( add_query_arg( 'sssj_resume', 'deleted', remove_query_arg( 'sssj_resume', $redirect ) ) );
		exit;
	}

	public static function handle_default() {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Please log in.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_resume_default', 'sssj_resume_nonce' );
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		self::set_default( $id, get_current_user_id() );
		$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		wp_safe_redirect( add_query_arg( 'sssj_resume', 'default', remove_query_arg( 'sssj_resume', $redirect ) ) );
		exit;
	}

	/** Stream a résumé to its owner / an admin / an authorised employer only. */
	public static function handle_serve() {
		$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		if ( ! is_user_logged_in() || ! $id ) {
			wp_die( esc_html__( 'Not allowed.', 'shuffles-social-services-jobs' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'sssj_resume_file_' . $id );
		$row = self::get( $id );
		if ( ! $row || ! $row->resume_data ) {
			wp_die( esc_html__( 'File not found.', 'shuffles-social-services-jobs' ), '', array( 'response' => 404 ) );
		}
		if ( ! self::can_view( $row, get_current_user_id() ) ) {
			wp_die( esc_html__( 'Not allowed.', 'shuffles-social-services-jobs' ), '', array( 'response' => 403 ) );
		}
		$bytes = base64_decode( $row->resume_data ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		$mime  = $row->resume_mime ? $row->resume_mime : 'application/octet-stream';
		nocache_headers();
		header( 'Content-Type: ' . $mime );
		header( 'Content-Length: ' . strlen( $bytes ) );
		header( 'Content-Disposition: inline; filename="' . ( $row->original_name ? sanitize_file_name( $row->original_name ) : ( 'resume-' . (int) $id ) ) . '"' );
		header( 'X-Content-Type-Options: nosniff' );
		echo $bytes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/** Signed URL to view a résumé file. */
	public static function file_url( $id ) {
		return wp_nonce_url( admin_url( 'admin-post.php?action=sssj_resume_file&id=' . (int) $id ), 'sssj_resume_file_' . (int) $id );
	}
}
