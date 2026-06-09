<?php
/**
 * Banned / sanctioned provider register + ABN cross-match (safety).
 *
 * Standalone-first: the register of banned ABNs lives in THIS plugin (a custom table), populated by
 * a CSV import of the NDIS Commission compliance & enforcement actions and/or manual entries. It does
 * NOT depend on the sibling Reference Check plugin or any live API.
 *
 * INVARIANTS (non-negotiable, mirror the rest of the safety design):
 *  - FLAG-ONLY: a match never blocks posting, never auto-rejects, never changes a listing's status.
 *  - STAFF-ONLY: a flag is shown to administrators and emailed to staff only, never to the public,
 *    never to the provider (the data can be stale or about a different entity sharing an ABN, so a
 *    public accusation would be unfair and risky). A human reviews every flag.
 *
 * When an ABN is recorded on a job / worker / org (do_action shuffles_ssj_abn_recorded), we cross-match
 * it against the register and stamp a private flag on the entity if it matches.
 *
 * Storage: custom table {prefix}sssj_ban_register (see Shuffles_SSJ_Activator, DB_VERSION 10).
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Ban_Register {

	const FLAG_META      = '_sssj_ban_flag';
	const FLAG_INFO_META = '_sssj_ban_flag_info';

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sssj_ban_register';
	}

	/** Master switch (Settings → Safety Register). Default ON. */
	public static function enabled() {
		$o = get_option( 'shuffles_ssj_settings', array() );
		return ! ( is_array( $o ) && isset( $o['ban_register_enabled'] ) && ! $o['ban_register_enabled'] );
	}

	/** Wire the cross-match onto the ABN-recorded action (after ABN's own ABR enrichment at 10). */
	public static function register() {
		add_action( 'shuffles_ssj_abn_recorded', array( __CLASS__, 'on_abn_recorded' ), 20, 3 );
	}

	public static function normalise( $abn ) {
		return class_exists( 'Shuffles_SSJ_ABN' ) ? Shuffles_SSJ_ABN::normalise( $abn ) : preg_replace( '/\D+/', '', (string) $abn );
	}

	/** Staff alert email (Settings → Safety Register), else the site admin email. */
	public static function staff_email() {
		$o = get_option( 'shuffles_ssj_settings', array() );
		$e = is_array( $o ) && ! empty( $o['ban_alert_email'] ) ? (string) $o['ban_alert_email'] : '';
		return is_email( $e ) ? $e : (string) get_option( 'admin_email' );
	}

	/* ----------------------------------------------------------- register CRUD */

	/** Active (in-force) register entries matching an ABN. */
	public static function active_matches( $abn ) {
		global $wpdb;
		$n = self::normalise( $abn );
		if ( 11 !== strlen( $n ) ) {
			return array();
		}
		$today = current_time( 'Y-m-d' );
		return (array) $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM ' . self::table() . " WHERE abn_norm = %s AND ( expiry_date IS NULL OR expiry_date = '0000-00-00' OR expiry_date >= %s ) ORDER BY effective_date DESC",
			$n, $today
		) ); // phpcs:ignore WordPress.DB
	}

	public static function is_banned( $abn ) {
		return ! empty( self::active_matches( $abn ) );
	}

	/** Add a register entry (admin). Skips an exact duplicate (abn + action + effective date). */
	public static function add( $data ) {
		global $wpdb;
		$abn = self::normalise( isset( $data['abn'] ) ? $data['abn'] : '' );
		if ( 11 !== strlen( $abn ) ) {
			return false;
		}
		$action = sanitize_text_field( isset( $data['action_type'] ) ? $data['action_type'] : '' );
		$eff    = self::clean_date( isset( $data['effective_date'] ) ? $data['effective_date'] : '' );

		if ( '' === $eff ) {
			$dupe = (int) $wpdb->get_var( $wpdb->prepare(
				'SELECT COUNT(*) FROM ' . self::table() . " WHERE abn_norm = %s AND action_type = %s AND ( effective_date IS NULL OR effective_date = '' )",
				$abn, $action
			) ); // phpcs:ignore WordPress.DB
		} else {
			$dupe = (int) $wpdb->get_var( $wpdb->prepare(
				'SELECT COUNT(*) FROM ' . self::table() . ' WHERE abn_norm = %s AND action_type = %s AND effective_date = %s',
				$abn, $action, $eff
			) ); // phpcs:ignore WordPress.DB
		}
		if ( $dupe > 0 ) {
			return 0; // already present
		}

		$ok = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'abn_norm'       => $abn,
				'provider_name'  => sanitize_text_field( isset( $data['provider_name'] ) ? $data['provider_name'] : '' ),
				'action_type'    => $action,
				'details'        => sanitize_textarea_field( isset( $data['details'] ) ? $data['details'] : '' ),
				'source'         => sanitize_text_field( isset( $data['source'] ) ? $data['source'] : '' ),
				'reference'      => esc_url_raw( isset( $data['reference'] ) ? $data['reference'] : '' ),
				'effective_date' => '' === $eff ? null : $eff,
				'expiry_date'    => self::clean_date( isset( $data['expiry_date'] ) ? $data['expiry_date'] : '' ) ?: null,
				'created_at'     => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
		return $ok ? (int) $wpdb->insert_id : false;
	}

	public static function delete( $id ) {
		global $wpdb;
		return (bool) $wpdb->delete( self::table(), array( 'id' => (int) $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/** Register entries (newest first), optionally filtered by an ABN/name search. */
	public static function all( $search = '', $limit = 200 ) {
		global $wpdb;
		$limit  = (int) $limit;
		$search = trim( (string) $search );
		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$ndig = self::normalise( $search );
			return (array) $wpdb->get_results( $wpdb->prepare(
				'SELECT * FROM ' . self::table() . ' WHERE provider_name LIKE %s OR abn_norm LIKE %s ORDER BY created_at DESC LIMIT %d',
				$like, '%' . $wpdb->esc_like( $ndig ) . '%', $limit
			) ); // phpcs:ignore WordPress.DB
		}
		return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' ORDER BY created_at DESC LIMIT %d', $limit ) ); // phpcs:ignore WordPress.DB
	}

	public static function count() {
		global $wpdb;
		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table() ); // phpcs:ignore WordPress.DB
	}

	private static function clean_date( $d ) {
		$d = trim( (string) $d );
		if ( '' === $d ) {
			return '';
		}
		$ts = strtotime( $d );
		return $ts ? gmdate( 'Y-m-d', $ts ) : '';
	}

	/* ----------------------------------------------------------- cross-match + flags */

	/** The entity's own recorded ABN (worker_abn / org_abn), digits only. */
	public static function entity_abn( $entity_id ) {
		$pt  = get_post_type( (int) $entity_id );
		$key = ( 'sssj_worker' === $pt ) ? 'worker_abn' : 'org_abn';
		return self::normalise( (string) get_post_meta( (int) $entity_id, $key, true ) );
	}

	/** Hooked to shuffles_ssj_abn_recorded: stamp or clear the private safety flag on the entity. */
	public static function on_abn_recorded( $abn, $type, $entity_id ) {
		if ( ! self::enabled() ) {
			return;
		}
		$entity_id = (int) $entity_id;
		$matches   = self::active_matches( $abn );
		$was       = '1' === (string) get_post_meta( $entity_id, self::FLAG_META, true );
		if ( ! empty( $matches ) ) {
			self::flag_entity( $entity_id, $abn, $matches );
			if ( ! $was ) {
				self::notify_staff( $entity_id, $abn, $matches, $type );
			}
		} else {
			self::clear_flag( $entity_id );
		}
	}

	public static function flag_entity( $entity_id, $abn, $matches ) {
		$first = $matches[0];
		update_post_meta( (int) $entity_id, self::FLAG_META, '1' );
		update_post_meta( (int) $entity_id, self::FLAG_INFO_META, wp_json_encode( array(
			'abn'      => self::normalise( $abn ),
			'name'     => (string) $first->provider_name,
			'action'   => (string) $first->action_type,
			'source'   => (string) $first->source,
			'when'     => current_time( 'mysql' ),
			'count'    => count( $matches ),
		) ) );
	}

	public static function clear_flag( $entity_id ) {
		delete_post_meta( (int) $entity_id, self::FLAG_META );
		delete_post_meta( (int) $entity_id, self::FLAG_INFO_META );
	}

	/** Decoded flag info for an entity, or null. */
	public static function flag_info( $entity_id ) {
		if ( '1' !== (string) get_post_meta( (int) $entity_id, self::FLAG_META, true ) ) {
			return null;
		}
		$raw = get_post_meta( (int) $entity_id, self::FLAG_INFO_META, true );
		$d   = is_string( $raw ) ? json_decode( $raw, true ) : ( is_array( $raw ) ? $raw : null );
		return is_array( $d ) ? $d : array();
	}

	/** Entities currently carrying a safety flag (across worker / org / job). */
	public static function flagged_entities( $limit = 200 ) {
		return get_posts( array(
			'post_type'      => array( 'sssj_worker', 'sssj_org', 'sssj_job' ),
			'post_status'    => 'any',
			'posts_per_page' => (int) $limit,
			'no_found_rows'  => true,
			'meta_key'       => self::FLAG_META,
			'meta_value'     => '1',
		) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	}

	/** Re-check every worker/org with a recorded ABN against the register. Returns [scanned, flagged]. */
	public static function rescan_all() {
		$scanned = 0;
		$flagged = 0;
		foreach ( array( 'sssj_worker' => 'worker_abn', 'sssj_org' => 'org_abn' ) as $pt => $key ) {
			$ids = get_posts( array(
				'post_type'      => $pt,
				'post_status'    => 'any',
				'posts_per_page' => 5000,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => $key,
				'meta_compare'   => 'EXISTS',
			) ); // phpcs:ignore WordPress.DB.SlowDBQuery
			foreach ( (array) $ids as $id ) {
				$abn = self::normalise( (string) get_post_meta( $id, $key, true ) );
				if ( 11 !== strlen( $abn ) ) {
					continue;
				}
				$scanned++;
				$matches = self::active_matches( $abn );
				if ( ! empty( $matches ) ) {
					self::flag_entity( $id, $abn, $matches );
					$flagged++;
				} else {
					self::clear_flag( $id );
				}
			}
		}
		do_action( 'shuffles_ssj_ban_rescan_done', $scanned, $flagged );
		return array( $scanned, $flagged );
	}

	/** Staff-only email when a NEW match is found. Never sent to the provider. */
	private static function notify_staff( $entity_id, $abn, $matches, $type ) {
		$to = self::staff_email();
		if ( ! is_email( $to ) ) {
			return;
		}
		$first   = $matches[0];
		$title   = get_the_title( (int) $entity_id );
		$edit    = get_edit_post_link( (int) $entity_id, '' );
		$subject = sprintf( __( '[Safety] ABN match on the banned register, %s', 'shuffles-social-services-jobs' ), wp_strip_all_tags( (string) $title ) );
		$lines   = array(
			__( 'A recorded ABN matches an entry on the banned/sanctioned provider register. This is a FLAG ONLY, nothing has been blocked or published about it. Please review.', 'shuffles-social-services-jobs' ),
			'',
			sprintf( __( 'Listing: %s (%s, #%d)', 'shuffles-social-services-jobs' ), wp_strip_all_tags( (string) $title ), (string) $type, (int) $entity_id ),
			sprintf( __( 'ABN: %s', 'shuffles-social-services-jobs' ), self::normalise( $abn ) ),
			sprintf( __( 'Register entry: %1$s, %2$s (%3$s)', 'shuffles-social-services-jobs' ), (string) $first->provider_name, (string) $first->action_type, (string) $first->source ),
			$edit ? sprintf( __( 'Review: %s', 'shuffles-social-services-jobs' ), $edit ) : '',
			'',
			__( 'Reminder: this register data can be stale or about a different entity sharing an ABN. Confirm before acting, and never contact the provider on the basis of this flag alone.', 'shuffles-social-services-jobs' ),
		);
		wp_mail( $to, $subject, implode( "\n", array_filter( $lines, 'strlen' ) ) );
	}

	/* ----------------------------------------------------------- CSV import */

	/**
	 * Import register rows from an uploaded CSV (e.g. the NDIS Commission compliance / enforcement
	 * actions dataset). Auto-maps columns by header name. Returns a report array.
	 */
	public static function import_csv( $tmp_path, $source_label = 'NDIS Commission' ) {
		$report = array( 'imported' => 0, 'skipped' => 0, 'rows' => 0, 'error' => '', 'mapping' => array() );
		if ( ! is_string( $tmp_path ) || ! is_file( $tmp_path ) ) {
			$report['error'] = __( 'No file received.', 'shuffles-social-services-jobs' );
			return $report;
		}
		$fh = fopen( $tmp_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! $fh ) {
			$report['error'] = __( 'Could not read the uploaded file.', 'shuffles-social-services-jobs' );
			return $report;
		}
		$headers = fgetcsv( $fh );
		if ( ! is_array( $headers ) ) {
			fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			$report['error'] = __( 'The file appears to be empty.', 'shuffles-social-services-jobs' );
			return $report;
		}
		$map = self::auto_map( $headers );
		$report['mapping'] = $map;
		if ( ! isset( $map['abn'] ) ) {
			fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			$report['error'] = __( 'Could not find an ABN column in that file. Expected a column header containing “ABN”.', 'shuffles-social-services-jobs' );
			return $report;
		}
		while ( ( $row = fgetcsv( $fh ) ) !== false ) {
			if ( ! is_array( $row ) || ( 1 === count( $row ) && '' === trim( (string) $row[0] ) ) ) {
				continue;
			}
			if ( $report['rows'] >= 100000 ) {
				break;
			}
			$report['rows']++;
			$abn = self::normalise( self::cell( $row, $map, 'abn' ) );
			if ( 11 !== strlen( $abn ) ) {
				$report['skipped']++;
				continue;
			}
			$res = self::add( array(
				'abn'            => $abn,
				'provider_name'  => self::cell( $row, $map, 'name' ),
				'action_type'    => self::cell( $row, $map, 'action' ),
				'details'        => self::cell( $row, $map, 'details' ),
				'effective_date' => self::cell( $row, $map, 'date' ),
				'expiry_date'    => self::cell( $row, $map, 'expiry' ),
				'source'         => $source_label,
			) );
			if ( $res ) {
				$report['imported']++;
			} else {
				$report['skipped']++;
			}
		}
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		return $report;
	}

	/** Map CSV headers to our fields by fuzzy name match. */
	private static function auto_map( $headers ) {
		$want = array(
			'abn'     => array( 'abn' ),
			'name'    => array( 'provider', 'entity', 'name', 'legal name', 'business name' ),
			'action'  => array( 'action', 'outcome', 'type', 'sanction', 'decision', 'order' ),
			'details' => array( 'detail', 'description', 'reason', 'notes' ),
			'date'    => array( 'effective', 'date', 'commenc', 'start', 'from' ),
			'expiry'  => array( 'expiry', 'end', 'until', 'cease', 'to' ),
		);
		$map = array();
		foreach ( $headers as $i => $h ) {
			$hl = strtolower( trim( (string) $h ) );
			if ( '' === $hl ) {
				continue;
			}
			foreach ( $want as $field => $needles ) {
				if ( isset( $map[ $field ] ) ) {
					continue;
				}
				foreach ( $needles as $needle ) {
					if ( false !== strpos( $hl, $needle ) ) {
						$map[ $field ] = (int) $i;
						break;
					}
				}
			}
		}
		return $map;
	}

	private static function cell( $row, $map, $field ) {
		if ( ! isset( $map[ $field ] ) ) {
			return '';
		}
		$i = (int) $map[ $field ];
		return isset( $row[ $i ] ) ? trim( (string) $row[ $i ] ) : '';
	}

	/* ----------------------------------------------------------- staff-only display */

	/** A staff-only red banner for a flagged entity (shown on the single page to admins only). */
	public static function staff_banner_html( $entity_id ) {
		$info = self::flag_info( $entity_id );
		if ( null === $info ) {
			return '';
		}
		$name   = isset( $info['name'] ) ? (string) $info['name'] : '';
		$action = isset( $info['action'] ) ? (string) $info['action'] : '';
		$source = isset( $info['source'] ) ? (string) $info['source'] : '';
		$abn    = isset( $info['abn'] ) ? (string) $info['abn'] : '';
		ob_start();
		?>
		<div class="sssj sssj--banflag">
			<div class="sssj-banflag" role="alert">
				<strong>⚠️ <?php esc_html_e( 'Staff only, safety flag', 'shuffles-social-services-jobs' ); ?></strong>
				<p><?php echo esc_html( sprintf( __( 'This listing’s recorded ABN (%s) matches an entry on the banned / sanctioned provider register: %s%s%s. This is a flag only, nothing is shown publicly and nothing has been blocked. Confirm before acting; the data can be stale or about a different entity sharing an ABN.', 'shuffles-social-services-jobs' ),
					$abn,
					'' !== $name ? $name . ', ' : '',
					'' !== $action ? $action : __( 'listed', 'shuffles-social-services-jobs' ),
					'' !== $source ? ' (' . $source . ')' : ''
				) ); ?></p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
