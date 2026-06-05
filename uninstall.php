<?php
/**
 * Uninstall handler.
 *
 * Conservative by default: content (CPT posts, taxonomy terms) and custom tables are PRESERVED.
 * Options/tables are only removed when the admin explicitly opted in via the
 * "Delete all data on uninstall" setting (Privacy & Moderation tab).
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$settings = get_option( 'shuffles_ssj_settings', array() );
$delete   = is_array( $settings ) && isset( $settings['delete_data_on_uninstall'] ) && '1' === (string) $settings['delete_data_on_uninstall'];

if ( ! $delete ) {
	return;
}

global $wpdb;

// Remove options.
delete_option( 'shuffles_ssj_settings' );
delete_option( 'shuffles_ssj_version' );
delete_option( 'shuffles_ssj_db_version' );
delete_option( 'shuffles_ssj_installed_at' );
delete_option( 'shuffles_ssj_seeded' );

// Drop custom tables.
$tables = array( 'sssj_match_score', 'sssj_application', 'sssj_message', 'sssj_credential' );
foreach ( $tables as $t ) {
	$table = $wpdb->prefix . $t;
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB
}

// Note: CPT posts and taxonomy terms are intentionally left in place even on opt-in delete,
// so an accidental uninstall never destroys participant/worker/job records. Remove manually if required.
