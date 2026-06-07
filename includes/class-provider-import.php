<?php
/**
 * Provider CSV importer — PROOF OF CONCEPT.
 *
 * Ingests the NDIS Commission's OFFICIAL bulk datasets rather than scraping the register:
 *   - "Active providers" CSV  → dataresearch.ndis.gov.au/datasets/provider-datasets  → draft sssj_org records.
 *   - "Compliance actions" CSV → data.gov.au (NDIS Commission)                       → previewed + a per-row
 *     hook (shuffles_ssj_ndis_compliance_row) so the Reference Check banning plugin can consume it.
 *
 * Safety for a PoC: admin-only, nonce-checked, DRY-RUN by default (no writes), capped row count,
 * idempotent by ABN, imported orgs are created as DRAFT and flagged _sssj_imported for easy removal.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Provider_Import {

	const MAX_SCAN = 100000; // hard safety cap on rows scanned in one request
	const REPORT_T = 'sssj_import_report_';

	/**
	 * PROOF OF CONCEPT — preview only. While true, the importer NEVER writes anything and NEVER
	 * fires the compliance hook: it only parses and reports. Flip to false (and ship a UI for it)
	 * when bulk import is actually wanted. Filterable so it can be force-enabled deliberately later.
	 */
	const PREVIEW_ONLY = true;

	public static function preview_only() {
		return (bool) apply_filters( 'shuffles_ssj_provider_import_preview_only', self::PREVIEW_ONLY );
	}

	public static function register() {
		add_action( 'admin_post_sssj_provider_import', array( __CLASS__, 'handle' ) );
	}

	/* ------------------------------------------------------------- Column mapping */

	/** Map our fields → the CSV column index, by fuzzy header matching. */
	public static function auto_map( $headers ) {
		$norm = array();
		foreach ( $headers as $i => $h ) {
			$norm[ $i ] = strtolower( trim( preg_replace( '/[^a-z0-9]+/i', ' ', (string) $h ) ) );
		}
		$find = function ( $needles ) use ( $norm ) {
			foreach ( $norm as $i => $h ) {
				foreach ( (array) $needles as $n ) {
					if ( false !== strpos( $h, $n ) ) {
						return $i;
					}
				}
			}
			return null;
		};
		return array(
			'legal_name'   => $find( array( 'legal name', 'legal entity', 'entity name', 'provider name', 'organisation name', 'registered name' ) ),
			'trading_name' => $find( array( 'trading name', 'business name', 'trading as' ) ),
			'abn'          => $find( array( 'abn' ) ),
			'register_id'  => $find( array( 'registration number', 'provider number', 'registration id', 'provider id' ) ),
			'status'       => $find( array( 'registration status', 'status' ) ),
			'groups'       => $find( array( 'registration group', 'registration groups', 'groups' ) ),
			'state'        => $find( array( 'state', 'jurisdiction' ) ),
			'suburb'       => $find( array( 'suburb', 'locality', 'town', 'city' ) ),
		);
	}

	protected static function cell( $row, $map, $key ) {
		$i = isset( $map[ $key ] ) ? $map[ $key ] : null;
		return ( null !== $i && isset( $row[ $i ] ) ) ? trim( (string) $row[ $i ] ) : '';
	}

	/* ------------------------------------------------------------- Handler */

	public static function handle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_provider_import' );

		$uid     = get_current_user_id();
		$kind    = isset( $_POST['kind'] ) ? sanitize_key( wp_unslash( $_POST['kind'] ) ) : 'active';
		// PoC is preview-only: force dry-run regardless of any submitted value — nothing is written.
		$dry_run = self::preview_only() ? true : empty( $_POST['do_import'] );
		$cap     = isset( $_POST['cap'] ) ? max( 1, min( 1000, (int) $_POST['cap'] ) ) : 25;

		$report = array( 'kind' => $kind, 'dry_run' => $dry_run, 'preview_only' => self::preview_only(), 'cap' => $cap, 'error' => '', 'total' => 0, 'mapping' => array(), 'headers' => array(), 'sample' => array(), 'created' => 0, 'updated' => 0, 'skipped' => 0, 'fired' => 0, 'truncated' => false );

		if ( empty( $_FILES['csv']['tmp_name'] ) || ! is_uploaded_file( $_FILES['csv']['tmp_name'] ) ) {
			$report['error'] = __( 'Please choose a CSV file to upload.', 'shuffles-social-services-jobs' );
			return self::finish( $uid, $report );
		}
		$tmp  = $_FILES['csv']['tmp_name'];
		$name = isset( $_FILES['csv']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['csv']['name'] ) ) : 'upload.csv';
		$ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, array( 'csv', 'txt' ), true ) ) {
			$report['error'] = __( 'That doesn’t look like a .csv file.', 'shuffles-social-services-jobs' );
			return self::finish( $uid, $report );
		}

		$fh = fopen( $tmp, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $fh ) {
			$report['error'] = __( 'Could not read the uploaded file.', 'shuffles-social-services-jobs' );
			return self::finish( $uid, $report );
		}

		$headers = fgetcsv( $fh );
		if ( ! is_array( $headers ) ) {
			fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			$report['error'] = __( 'The file appears to be empty.', 'shuffles-social-services-jobs' );
			return self::finish( $uid, $report );
		}
		$map               = self::auto_map( $headers );
		$report['headers'] = array_map( 'strval', $headers );
		$report['mapping'] = $map;

		$rownum = 0;
		while ( ( $row = fgetcsv( $fh ) ) !== false ) {
			if ( ! is_array( $row ) || ( 1 === count( $row ) && ( null === $row[0] || '' === trim( (string) $row[0] ) ) ) ) {
				continue; // blank line
			}
			$rownum++;
			if ( $rownum >= self::MAX_SCAN ) {
				$report['truncated'] = true;
				break;
			}
			$mapped = array(
				'legal_name'   => self::cell( $row, $map, 'legal_name' ),
				'trading_name' => self::cell( $row, $map, 'trading_name' ),
				'abn'          => preg_replace( '/\D+/', '', self::cell( $row, $map, 'abn' ) ),
				'register_id'  => preg_replace( '/\D+/', '', self::cell( $row, $map, 'register_id' ) ),
				'status'       => self::cell( $row, $map, 'status' ),
				'groups'       => self::cell( $row, $map, 'groups' ),
				'state'        => self::cell( $row, $map, 'state' ),
				'suburb'       => self::cell( $row, $map, 'suburb' ),
			);
			if ( count( $report['sample'] ) < 10 ) {
				$report['sample'][] = $mapped;
			}
			if ( ! $dry_run && ( $report['created'] + $report['updated'] + $report['skipped'] ) < $cap ) {
				if ( 'compliance' === $kind ) {
					/** A banning/Reference Check integration can consume each compliance row. */
					do_action( 'shuffles_ssj_ndis_compliance_row', $mapped, $row );
					$report['fired']++;
				} else {
					self::import_active_row( $mapped, $report );
				}
			}
		}
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		$report['total'] = $rownum;

		return self::finish( $uid, $report );
	}

	/** Create (draft) or update one organisation from an "active providers" row. Idempotent by ABN. */
	protected static function import_active_row( $m, &$report ) {
		$name = '' !== $m['legal_name'] ? $m['legal_name'] : $m['trading_name'];
		if ( '' === $name ) {
			$report['skipped']++;
			return;
		}
		$existing = $m['abn'] ? get_posts( array( 'post_type' => 'sssj_org', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => true, 'meta_key' => 'org_abn', 'meta_value' => $m['abn'] ) ) : array(); // phpcs:ignore WordPress.DB.SlowDBQuery
		if ( $existing ) {
			$pid = (int) $existing[0];
			if ( '' !== $m['status'] ) {
				update_post_meta( $pid, 'ndis_status', $m['status'] );
			}
			if ( '' !== $m['groups'] ) {
				update_post_meta( $pid, 'ndis_groups', $m['groups'] );
			}
			$report['updated']++;
			return;
		}
		$pid = wp_insert_post(
			array(
				'post_type'   => 'sssj_org',
				'post_status' => 'draft', // never auto-publish imported records
				'post_title'  => $name,
			),
			true
		);
		if ( is_wp_error( $pid ) || ! $pid ) {
			$report['skipped']++;
			return;
		}
		update_post_meta( $pid, 'org_abn', $m['abn'] );
		update_post_meta( $pid, 'ndis_registered', '1' );
		if ( '' !== $m['register_id'] ) {
			update_post_meta( $pid, 'ndis_register_id', $m['register_id'] );
		}
		if ( '' !== $m['status'] ) {
			update_post_meta( $pid, 'ndis_status', $m['status'] );
		}
		if ( '' !== $m['groups'] ) {
			update_post_meta( $pid, 'ndis_groups', $m['groups'] );
		}
		if ( '' !== $m['state'] ) {
			update_post_meta( $pid, 'location_state', $m['state'] );
		}
		if ( '' !== $m['suburb'] ) {
			update_post_meta( $pid, 'location_suburb', $m['suburb'] );
		}
		update_post_meta( $pid, '_sssj_imported', '1' );
		update_post_meta( $pid, '_sssj_import_source', 'ndis_csv' );
		$report['created']++;
	}

	/* ------------------------------------------------------------- Report transient */

	protected static function finish( $uid, $report ) {
		set_transient( self::REPORT_T . $uid, $report, 5 * MINUTE_IN_SECONDS );
		wp_safe_redirect( add_query_arg( array( 'page' => 'shuffles-ssj', 'tab' => 'import', 'sssj_imported' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/** The last report for the current admin (consumed once). */
	public static function pull_report() {
		$uid = get_current_user_id();
		$r   = get_transient( self::REPORT_T . $uid );
		if ( $r ) {
			delete_transient( self::REPORT_T . $uid );
		}
		return is_array( $r ) ? $r : null;
	}

	/** Count of orgs created by a CSV import (for the tab's "remove imported" helper). */
	public static function imported_count() {
		$ids = get_posts( array( 'post_type' => 'sssj_org', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => true, 'meta_key' => '_sssj_imported', 'meta_value' => '1' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		return count( $ids );
	}
}
