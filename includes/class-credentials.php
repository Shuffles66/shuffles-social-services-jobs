<?php
/**
 * Compliance & verification, worker credentials (WWCC, NDIS screening, police check, etc.).
 *
 * Trust model (non-negotiable, mirrors the CLAUDE.md privacy block):
 *   - Evidence files live in a deny-all directory (uploads/sssj-credentials) and are served ONLY
 *     through a capability + ownership-checked handler. There is no public URL.
 *   - The public "✓ Verified" badge is reachable ONLY through admin approval. User-supplied
 *     "verified" claims are never trusted.
 *   - MIME is validated server-side (not by extension); filenames are unguessable.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Credentials {

	const STATUSES = array( 'pending', 'verified', 'rejected', 'expired' );

	/* ------------------------------------------------------------------ *
	 * Wiring
	 * ------------------------------------------------------------------ */

	public static function register() {
		// Worker-facing (logged-in).
		add_action( 'admin_post_sssj_cred_add', array( __CLASS__, 'handle_add' ) );
		add_action( 'admin_post_sssj_cred_delete', array( __CLASS__, 'handle_delete' ) );
		add_action( 'admin_post_sssj_cred_file', array( __CLASS__, 'handle_serve' ) );
		// Admin-only.
		add_action( 'admin_post_sssj_cred_status', array( __CLASS__, 'handle_status' ) );
	}

	private static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sssj_credential';
	}

	/**
	 * Credential kinds: machine key => human label. Filterable per site.
	 *
	 * @return array
	 */
	public static function kinds() {
		return apply_filters(
			'shuffles_ssj_credential_kinds',
			array(
				'ndis_screening'  => __( 'NDIS Worker Screening Check', 'shuffles-social-services-jobs' ),
				'wwcc'            => __( 'Working with Children Check', 'shuffles-social-services-jobs' ),
				'police_check'    => __( 'National Police Check', 'shuffles-social-services-jobs' ),
				'first_aid'       => __( 'First Aid / CPR', 'shuffles-social-services-jobs' ),
				'qualification'   => __( 'Qualification (Cert III/IV, diploma, degree)', 'shuffles-social-services-jobs' ),
				'insurance'       => __( 'Insurance (PI / public liability)', 'shuffles-social-services-jobs' ),
				'drivers_licence' => __( "Driver's licence", 'shuffles-social-services-jobs' ),
				'other'           => __( 'Other', 'shuffles-social-services-jobs' ),
			)
		);
	}

	public static function kind_label( $kind ) {
		$k = self::kinds();
		return isset( $k[ $kind ] ) ? $k[ $kind ] : ucfirst( str_replace( '_', ' ', (string) $kind ) );
	}

	/* ------------------------------------------------------------------ *
	 * Secure evidence storage
	 * ------------------------------------------------------------------ */

	/**
	 * Evidence is stored IN THE DATABASE and served only through the authenticated handler, so there
	 * is no on-disk, web-reachable file to protect, kept as a (near) no-op for the activation hook.
	 *
	 * Why DB storage: this host (like most managed/Nginx stacks) ignores .htaccess AND will not let
	 * PHP write above the web root. That leaves no filesystem location that is both writable and not
	 * web-served, so a file in wp-content/uploads is directly fetchable. Storing the bytes in the DB
	 * removes the public URL entirely. (max_allowed_packet is ample; files are capped at 8 MB.)
	 */
	public static function ensure_protected_dir() {
		// Remove the resolved-path option from older builds; nothing else to do.
		delete_option( 'sssj_credentials_dir' );
	}

	/** Allowed evidence MIME types. */
	private static function allowed_mimes() {
		return apply_filters(
			'shuffles_ssj_credential_mimes',
			array(
				'pdf'  => 'application/pdf',
				'jpg'  => 'image/jpeg',
				'jpeg' => 'image/jpeg',
				'png'  => 'image/png',
			)
		);
	}

	/** Max evidence size in bytes (default 8 MB). */
	private static function max_bytes() {
		return (int) apply_filters( 'shuffles_ssj_credential_max_bytes', 8 * 1024 * 1024 );
	}

	/**
	 * Validate an uploaded evidence file and return it base64-encoded for DB storage (no disk write).
	 *
	 * @param array $file A single $_FILES entry.
	 * @return array|WP_Error { data (base64), mime, original }
	 */
	private static function read_evidence( $file ) {
		if ( empty( $file ) || ! isset( $file['tmp_name'] ) || UPLOAD_ERR_NO_FILE === (int) $file['error'] ) {
			return new WP_Error( 'nofile', __( 'No file was uploaded.', 'shuffles-social-services-jobs' ) );
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
			return new WP_Error( 'badtype', __( 'Only PDF, JPG or PNG files are accepted.', 'shuffles-social-services-jobs' ) );
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

	/* ------------------------------------------------------------------ *
	 * CRUD
	 * ------------------------------------------------------------------ */

	/**
	 * Add a credential for a user (status starts 'pending'). Optional evidence file.
	 *
	 * @return int|WP_Error new id
	 */
	public static function add( $uid, $data, $file = null ) {
		global $wpdb;
		$uid  = (int) $uid;
		$kind = sanitize_key( isset( $data['kind'] ) ? $data['kind'] : '' );
		if ( ! $uid || ! array_key_exists( $kind, self::kinds() ) ) {
			return new WP_Error( 'invalid', __( 'Please choose a valid credential type.', 'shuffles-social-services-jobs' ) );
		}
		$edata = null;
		$emime = null;
		$orig  = null;
		if ( $file && isset( $file['tmp_name'] ) && UPLOAD_ERR_NO_FILE !== (int) $file['error'] ) {
			$stored = self::read_evidence( $file );
			if ( is_wp_error( $stored ) ) {
				return $stored;
			}
			$edata = $stored['data'];
			$emime = $stored['mime'];
			$orig  = $stored['original'];
		}

		$ok = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'worker_id'     => $uid,
				'kind'          => $kind,
				'number'        => isset( $data['number'] ) ? substr( sanitize_text_field( $data['number'] ), 0, 120 ) : null,
				'issued_date'   => self::clean_date( isset( $data['issued_date'] ) ? $data['issued_date'] : '' ),
				'expires_date'  => self::clean_date( isset( $data['expires_date'] ) ? $data['expires_date'] : '' ),
				'evidence_data' => $edata,
				'evidence_mime' => $emime,
				'original_name' => $orig,
				'status'        => 'pending',
				'created_at'    => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
		if ( ! $ok ) {
			return new WP_Error( 'db', __( 'Could not save the credential.', 'shuffles-social-services-jobs' ) );
		}
		return (int) $wpdb->insert_id;
	}

	private static function clean_date( $v ) {
		$v = trim( (string) $v );
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ? $v : null;
	}

	/** Columns for list views, everything EXCEPT the (potentially large) evidence blob. */
	const LIST_COLS = 'id, worker_id, kind, number, issued_date, expires_date, original_name, evidence_mime, status, note, created_at, verified_at, verified_by_admin_id, (evidence_data IS NOT NULL) AS has_evidence';

	/** Full row including the evidence blob, used only when serving a file. */
	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', (int) $id ) ); // phpcs:ignore WordPress.DB
	}

	/** A user's credentials, newest first (no blob loaded). */
	public static function for_worker( $uid ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT ' . self::LIST_COLS . ' FROM ' . self::table() . ' WHERE worker_id = %d ORDER BY id DESC', (int) $uid ) ); // phpcs:ignore WordPress.DB
	}

	/** Admin queue: credentials by status (default pending), newest first (no blob loaded). */
	public static function query( $status = 'pending', $limit = 200 ) {
		global $wpdb;
		$limit = max( 1, (int) $limit );
		if ( 'all' === $status ) {
			return $wpdb->get_results( $wpdb->prepare( 'SELECT ' . self::LIST_COLS . ' FROM ' . self::table() . ' ORDER BY id DESC LIMIT %d', $limit ) ); // phpcs:ignore WordPress.DB
		}
		return $wpdb->get_results( $wpdb->prepare( 'SELECT ' . self::LIST_COLS . ' FROM ' . self::table() . ' WHERE status = %s ORDER BY id DESC LIMIT %d', $status, $limit ) ); // phpcs:ignore WordPress.DB
	}

	public static function count_by_status( $status ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE status = %s', $status ) ); // phpcs:ignore WordPress.DB
	}

	/** Delete a credential (and its evidence file). Owner or admin only. */
	public static function delete( $id, $actor_id ) {
		global $wpdb;
		$row = self::get( $id );
		if ( ! $row ) {
			return false;
		}
		if ( (int) $row->worker_id !== (int) $actor_id && ! user_can( (int) $actor_id, 'manage_options' ) ) {
			return false;
		}
		// Evidence lives in the row, so deleting the row removes it.
		$wpdb->delete( self::table(), array( 'id' => (int) $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		self::recompute_worker_verified( (int) $row->worker_id );
		return true;
	}

	/* ------------------------------------------------------------------ *
	 * Verification (admin) + the worker "✓ Verified" badge
	 * ------------------------------------------------------------------ */

	/**
	 * Admin sets a credential's status. Only 'verified' stamps verified_at/by; recomputes the badge.
	 */
	public static function set_status( $id, $status, $admin_id, $note = '' ) {
		global $wpdb;
		$status = sanitize_key( $status );
		if ( ! in_array( $status, self::STATUSES, true ) ) {
			return false;
		}
		$row = self::get( $id );
		if ( ! $row ) {
			return false;
		}
		$data    = array( 'status' => $status, 'note' => '' !== $note ? wp_strip_all_tags( $note ) : null );
		$formats = array( '%s', '%s' );
		if ( 'verified' === $status ) {
			$data['verified_at']          = current_time( 'mysql' );
			$data['verified_by_admin_id'] = (int) $admin_id;
			$formats[]                    = '%s';
			$formats[]                    = '%d';
		} else {
			$data['verified_at']          = null;
			$data['verified_by_admin_id'] = null;
			$formats[]                    = '%s';
			$formats[]                    = '%d';
		}
		$wpdb->update( self::table(), $data, array( 'id' => (int) $id ), $formats, array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		self::recompute_worker_verified( (int) $row->worker_id );
		return true;
	}

	/** Is a credential currently valid (verified and not past expiry)? */
	private static function is_currently_valid( $row ) {
		if ( 'verified' !== $row->status ) {
			return false;
		}
		if ( $row->expires_date && $row->expires_date < current_time( 'Y-m-d' ) ) {
			return false;
		}
		return true;
	}

	/** Find a user's worker profile post id (one per user). */
	public static function worker_post_id( $uid ) {
		$ids = get_posts(
			array(
				'post_type'      => 'sssj_worker',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => 'worker_user_id', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => (int) $uid, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		return ! empty( $ids ) ? (int) $ids[0] : 0;
	}

	/**
	 * Recompute the worker profile's verified badge. The badge is set ONLY when the worker holds
	 * at least one currently-valid verified credential (admin-approved + not expired).
	 */
	public static function recompute_worker_verified( $uid ) {
		$post_id = self::worker_post_id( $uid );
		if ( ! $post_id ) {
			return;
		}
		$valid = false;
		$kinds = array();
		foreach ( self::for_worker( $uid ) as $row ) {
			if ( self::is_currently_valid( $row ) ) {
				$valid   = true;
				$kinds[] = $row->kind;
			}
		}
		if ( $valid ) {
			if ( ! get_post_meta( $post_id, 'verified_at', true ) ) {
				update_post_meta( $post_id, 'verified_at', current_time( 'mysql' ) );
			}
			update_post_meta( $post_id, 'verified_kinds', array_values( array_unique( $kinds ) ) );
		} else {
			delete_post_meta( $post_id, 'verified_at' );
			delete_post_meta( $post_id, 'verified_kinds' );
		}
	}

	/* ------------------------------------------------------------------ *
	 * Expiry sweep (daily cron)
	 * ------------------------------------------------------------------ */

	/**
	 * Daily: expire lapsed verified credentials (recompute badges), and email a single reminder
	 * at the configured lead time + on the expiry day. No extra state needed, reminders fire on
	 * exact date boundaries so workers are never spammed.
	 */
	public static function expiry_sweep() {
		global $wpdb;
		$today = current_time( 'Y-m-d' );

		// 1) Verified + past expiry -> expired; recompute affected workers.
		$lapsed = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . " WHERE status = 'verified' AND expires_date IS NOT NULL AND expires_date < %s", $today ) ); // phpcs:ignore WordPress.DB
		$touched = array();
		foreach ( (array) $lapsed as $row ) {
			$wpdb->update( self::table(), array( 'status' => 'expired' ), array( 'id' => (int) $row->id ), array( '%s' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$touched[ (int) $row->worker_id ] = true;
		}
		foreach ( array_keys( $touched ) as $uid ) {
			self::recompute_worker_verified( $uid );
		}

		// 2) Reminders: lead-day warning + expiry-day notice.
		$lead    = (int) ( new Shuffles_SSJ_Settings() )->get( 'credential_reminder_days', 30 );
		$lead_day = gmdate( 'Y-m-d', strtotime( $today . ' +' . max( 1, $lead ) . ' days' ) );
		$due = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE expires_date IN (%s, %s)', $lead_day, $today ) ); // phpcs:ignore WordPress.DB
		foreach ( (array) $due as $row ) {
			self::email_reminder( $row, $row->expires_date === $today );
		}
	}

	private static function email_reminder( $row, $is_today ) {
		$user = get_userdata( (int) $row->worker_id );
		if ( ! $user || ! $user->user_email ) {
			return;
		}
		$label = self::kind_label( $row->kind );
		$subject = $is_today
			? sprintf( __( 'Your %s has expired', 'shuffles-social-services-jobs' ), $label )
			: sprintf( __( 'Your %s is expiring soon', 'shuffles-social-services-jobs' ), $label );
		$body = $is_today
			? sprintf( __( "Your %1\$s expired on %2\$s. Please renew it and upload the new document so your verified status is maintained.", 'shuffles-social-services-jobs' ), $label, $row->expires_date )
			: sprintf( __( "Your %1\$s expires on %2\$s. Please renew it and upload the new document to keep your verified status.", 'shuffles-social-services-jobs' ), $label, $row->expires_date );
		/** Allow an integration (e.g. FluentCRM) to own this notice; return true to suppress wp_mail. */
		if ( apply_filters( 'shuffles_ssj_credential_reminder', false, $row, $is_today, $user ) ) {
			return;
		}
		wp_mail( $user->user_email, $subject, $body );
	}

	/* ------------------------------------------------------------------ *
	 * Handlers (admin-post)
	 * ------------------------------------------------------------------ */

	public static function handle_add() {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Please log in.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_cred_add', 'sssj_cred_nonce' );
		$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		$uid      = get_current_user_id();
		$data     = array(
			'kind'         => isset( $_POST['kind'] ) ? wp_unslash( $_POST['kind'] ) : '',
			'number'       => isset( $_POST['number'] ) ? wp_unslash( $_POST['number'] ) : '',
			'issued_date'  => isset( $_POST['issued_date'] ) ? wp_unslash( $_POST['issued_date'] ) : '',
			'expires_date' => isset( $_POST['expires_date'] ) ? wp_unslash( $_POST['expires_date'] ) : '',
		);
		$file = isset( $_FILES['evidence'] ) ? $_FILES['evidence'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$res  = self::add( $uid, $data, $file );
		$arg  = is_wp_error( $res ) ? 'error' : '1';
		wp_safe_redirect( add_query_arg( 'sssj_cred', $arg, $redirect ) );
		exit;
	}

	public static function handle_delete() {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Please log in.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_cred_delete', 'sssj_cred_nonce' );
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		self::delete( $id, get_current_user_id() );
		$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		wp_safe_redirect( add_query_arg( 'sssj_cred', 'deleted', $redirect ) );
		exit;
	}

	/** Stream an evidence file to its owner or an admin only. */
	public static function handle_serve() {
		$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		if ( ! is_user_logged_in() || ! $id ) {
			wp_die( esc_html__( 'Not allowed.', 'shuffles-social-services-jobs' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'sssj_cred_file_' . $id );
		$row = self::get( $id );
		if ( ! $row || ! $row->evidence_data ) {
			wp_die( esc_html__( 'File not found.', 'shuffles-social-services-jobs' ), '', array( 'response' => 404 ) );
		}
		$uid = get_current_user_id();
		if ( (int) $row->worker_id !== (int) $uid && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'shuffles-social-services-jobs' ), '', array( 'response' => 403 ) );
		}
		$bytes = base64_decode( $row->evidence_data ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		$mime  = $row->evidence_mime ? $row->evidence_mime : 'application/octet-stream';
		nocache_headers();
		header( 'Content-Type: ' . $mime );
		header( 'Content-Length: ' . strlen( $bytes ) );
		header( 'Content-Disposition: inline; filename="' . ( $row->original_name ? sanitize_file_name( $row->original_name ) : ( 'credential-' . (int) $id ) ) . '"' );
		header( 'X-Content-Type-Options: nosniff' );
		echo $bytes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/** Admin approve/reject. */
	public static function handle_status() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_cred_status' );
		$id     = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		$note   = isset( $_POST['note'] ) ? wp_unslash( $_POST['note'] ) : '';
		self::set_status( $id, $status, get_current_user_id(), $note );
		$back = isset( $_POST['_back'] ) ? esc_url_raw( wp_unslash( $_POST['_back'] ) ) : admin_url( 'admin.php?page=shuffles-ssj-verify' );
		wp_safe_redirect( add_query_arg( 'sssj_v', '1', $back ) );
		exit;
	}

	/** Signed URL to view an evidence file (for templates / admin). */
	public static function file_url( $id ) {
		return wp_nonce_url( admin_url( 'admin-post.php?action=sssj_cred_file&id=' . (int) $id ), 'sssj_cred_file_' . (int) $id );
	}

	/** Badge class + label for a status. */
	public static function status_badge( $status ) {
		$map = array(
			'pending'  => array( 'sssj-badge--pending', __( 'Pending review', 'shuffles-social-services-jobs' ) ),
			'verified' => array( 'sssj-badge--verified', __( '✓ Verified', 'shuffles-social-services-jobs' ) ),
			'rejected' => array( 'sssj-badge--rejected', __( 'Rejected', 'shuffles-social-services-jobs' ) ),
			'expired'  => array( 'sssj-badge--expired', __( 'Expired', 'shuffles-social-services-jobs' ) ),
		);
		return isset( $map[ $status ] ) ? $map[ $status ] : array( '', ucfirst( $status ) );
	}
}
