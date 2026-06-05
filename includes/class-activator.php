<?php
/**
 * Activation: create custom tables, seed taxonomies, add capabilities, flush rewrites.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Activator {

	public static function activate() {
		// Register entities first so rewrite rules + taxonomies exist before we seed and flush.
		$cpt = new Shuffles_SSJ_CPT_Registrar();
		$cpt->register_post_types();

		$taxes = new Shuffles_SSJ_Taxonomy_Registrar();
		$taxes->register_taxonomies();

		self::create_tables();

		$seeder = new Shuffles_SSJ_Taxonomy_Seeder();
		$seeder->seed_all();

		Shuffles_SSJ_Roles::add_caps();

		$settings = new Shuffles_SSJ_Settings();
		$settings->ensure_defaults();

		update_option( 'shuffles_ssj_version', SHUFFLES_SSJ_VERSION );
		if ( ! get_option( 'shuffles_ssj_installed_at' ) ) {
			update_option( 'shuffles_ssj_installed_at', current_time( 'mysql' ) );
		}

		flush_rewrite_rules();
	}

	/**
	 * Create the plugin's custom tables (idempotent via dbDelta).
	 */
	private static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$p               = $wpdb->prefix . 'sssj_';

		$statements = array();

		$statements[] = "CREATE TABLE {$p}match_score (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  source_type VARCHAR(10) NOT NULL,
  source_id BIGINT UNSIGNED NOT NULL,
  target_type VARCHAR(10) NOT NULL,
  target_id BIGINT UNSIGNED NOT NULL,
  score DECIMAL(4,3) NOT NULL DEFAULT 0.000,
  reason_json LONGTEXT NULL,
  computed_at DATETIME NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY pair (source_type,source_id,target_type,target_id),
  KEY src (source_type,source_id,score),
  KEY tgt (target_type,target_id,score)
) $charset_collate;";

		$statements[] = "CREATE TABLE {$p}application (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  need_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  worker_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  applicant_user_id BIGINT UNSIGNED NOT NULL,
  cover_message LONGTEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'new',
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY  (id),
  KEY job (job_id),
  KEY need (need_id),
  KEY applicant (applicant_user_id)
) $charset_collate;";

		$statements[] = "CREATE TABLE {$p}message (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  thread_id BIGINT UNSIGNED NOT NULL,
  from_user_id BIGINT UNSIGNED NOT NULL,
  to_user_id BIGINT UNSIGNED NOT NULL,
  context_entity_type VARCHAR(10) NULL,
  context_entity_id BIGINT UNSIGNED NULL,
  body LONGTEXT NOT NULL,
  read_at DATETIME NULL,
  created_at DATETIME NULL,
  PRIMARY KEY  (id),
  KEY thread (thread_id,created_at),
  KEY recipient (to_user_id,read_at),
  KEY sender (from_user_id)
) $charset_collate;";

		$statements[] = "CREATE TABLE {$p}credential (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  worker_id BIGINT UNSIGNED NOT NULL,
  kind VARCHAR(64) NOT NULL,
  number VARCHAR(120) NULL,
  issued_date DATE NULL,
  expires_date DATE NULL,
  evidence_path VARCHAR(255) NULL,
  verified_at DATETIME NULL,
  verified_by_admin_id BIGINT UNSIGNED NULL,
  PRIMARY KEY  (id),
  KEY worker (worker_id),
  KEY expires (expires_date)
) $charset_collate;";

		foreach ( $statements as $sql ) {
			dbDelta( $sql );
		}

		update_option( 'shuffles_ssj_db_version', '1' );
	}
}
