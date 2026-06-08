<?php
/**
 * Activation + schema management: create/upgrade custom tables, seed taxonomies,
 * add capabilities, flush rewrites.
 *
 * Tables are tuned for speed (composite indexes matching query patterns) and accuracy
 * (NOT NULL + defaults, and a UNIQUE key preventing duplicate applications).
 * create_tables() runs via dbDelta so it both CREATES and idempotently ADDS missing
 * indexes/columns to existing tables — maybe_upgrade() applies it to already-deployed sites.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Activator {

	/** Bump when the schema changes so maybe_upgrade() re-runs dbDelta. */
	const DB_VERSION = 10;

	public static function activate() {
		$cpt = new Shuffles_SSJ_CPT_Registrar();
		$cpt->register_post_types();

		$taxes = new Shuffles_SSJ_Taxonomy_Registrar();
		$taxes->register_taxonomies();

		self::create_tables();

		if ( class_exists( 'Shuffles_SSJ_Credentials' ) ) {
			Shuffles_SSJ_Credentials::ensure_protected_dir();
		}

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
	 * Apply schema upgrades on already-installed sites (activation only fires on activate,
	 * not on a plain file update). Cheap guard — runs dbDelta only when the version lags.
	 */
	public static function maybe_upgrade() {
		if ( (int) get_option( 'shuffles_ssj_db_version', 0 ) < self::DB_VERSION ) {
			self::create_tables();
		}
	}

	/**
	 * Create or upgrade the custom tables (idempotent via dbDelta) and stamp the db version.
	 */
	public static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$p               = $wpdb->prefix . 'sssj_';
		$statements      = array();

		// Match scores — read by "matches for a source/target ordered by score".
		$statements[] = "CREATE TABLE {$p}match_score (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  source_type VARCHAR(10) NOT NULL DEFAULT '',
  source_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  target_type VARCHAR(10) NOT NULL DEFAULT '',
  target_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  score DECIMAL(4,3) NOT NULL DEFAULT 0.000,
  reason_json LONGTEXT NULL,
  computed_at DATETIME NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY pair (source_type,source_id,target_type,target_id),
  KEY src (source_type,source_id,score),
  KEY tgt (target_type,target_id,score),
  KEY computed (computed_at)
) $charset_collate;";

		// Applications — UNIQUE(job,need,applicant) stops duplicate applications (accuracy);
		// composite (id,status) indexes serve "applicants for a job/need filtered by status".
		$statements[] = "CREATE TABLE {$p}application (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  need_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  worker_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  applicant_user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  cover_message LONGTEXT NULL,
  resume_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  extra LONGTEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'new',
  created_at DATETIME NULL DEFAULT NULL,
  updated_at DATETIME NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY uniq_app (job_id,need_id,applicant_user_id),
  KEY job_status (job_id,status),
  KEY need_status (need_id,status),
  KEY applicant (applicant_user_id),
  KEY worker (worker_id),
  KEY created (created_at)
) $charset_collate;";

		// Messages — thread view, recipient unread, sender, by-context.
		$statements[] = "CREATE TABLE {$p}message (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  thread_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  from_user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  to_user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  context_entity_type VARCHAR(10) NULL,
  context_entity_id BIGINT UNSIGNED NULL,
  body LONGTEXT NOT NULL,
  read_at DATETIME NULL DEFAULT NULL,
  created_at DATETIME NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY thread (thread_id,created_at),
  KEY recipient (to_user_id,read_at),
  KEY sender (from_user_id),
  KEY context (context_entity_type,context_entity_id)
) $charset_collate;";

		// Credentials — a worker's credentials, by-kind, and expiry/verification sweeps.
		$statements[] = "CREATE TABLE {$p}credential (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  worker_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  kind VARCHAR(64) NOT NULL DEFAULT '',
  number VARCHAR(120) NULL,
  issued_date DATE NULL DEFAULT NULL,
  expires_date DATE NULL DEFAULT NULL,
  evidence_path VARCHAR(255) NULL,
  evidence_data LONGTEXT NULL,
  evidence_mime VARCHAR(100) NULL,
  original_name VARCHAR(255) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  note TEXT NULL,
  created_at DATETIME NULL DEFAULT NULL,
  verified_at DATETIME NULL DEFAULT NULL,
  verified_by_admin_id BIGINT UNSIGNED NULL,
  PRIMARY KEY  (id),
  KEY worker_kind (worker_id,kind),
  KEY expires (expires_date),
  KEY status (status),
  KEY verified (verified_at)
) $charset_collate;";

		// Résumés — a candidate's stored résumé files (bytes in DB; served only via auth handler).
		$statements[] = "CREATE TABLE {$p}resume (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  label VARCHAR(160) NOT NULL DEFAULT '',
  resume_data LONGTEXT NULL,
  resume_mime VARCHAR(100) NULL,
  original_name VARCHAR(255) NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY user_default (user_id,is_default),
  KEY created (created_at)
) $charset_collate;";

		// CRM sync log — per-user history of FluentCRM tag/list attach/detach + missing-target events.
		$statements[] = "CREATE TABLE {$p}crm_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  email VARCHAR(190) NOT NULL DEFAULT '',
  entity VARCHAR(10) NOT NULL DEFAULT '',
  action VARCHAR(20) NOT NULL DEFAULT '',
  object_type VARCHAR(10) NOT NULL DEFAULT '',
  object_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  object_label VARCHAR(190) NOT NULL DEFAULT '',
  token VARCHAR(190) NOT NULL DEFAULT '',
  created_at DATETIME NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY user_time (user_id,created_at),
  KEY action (action),
  KEY created (created_at)
) $charset_collate;";

		// Reviews & ratings — one (editable) review per reviewer per subject; moderation + averages.
		$statements[] = "CREATE TABLE {$p}review (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  reviewer_user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  subject_type VARCHAR(10) NOT NULL DEFAULT '',
  subject_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  rating TINYINT UNSIGNED NOT NULL DEFAULT 0,
  title VARCHAR(160) NULL,
  body LONGTEXT NULL,
  response LONGTEXT NULL,
  response_at DATETIME NULL DEFAULT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  moderated_by BIGINT UNSIGNED NULL,
  moderated_at DATETIME NULL DEFAULT NULL,
  created_at DATETIME NULL DEFAULT NULL,
  updated_at DATETIME NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY uniq_review (reviewer_user_id,subject_type,subject_id),
  KEY subject_status (subject_type,subject_id,status),
  KEY status (status),
  KEY created (created_at)
) $charset_collate;";

		$statements[] = "CREATE TABLE {$p}testimonial (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  subject_type VARCHAR(10) NOT NULL DEFAULT '',
  subject_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  author_user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  author_name VARCHAR(160) NULL,
  author_role VARCHAR(160) NULL,
  body LONGTEXT NULL,
  source VARCHAR(20) NOT NULL DEFAULT 'submitted',
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  featured TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  moderated_by BIGINT UNSIGNED NULL,
  moderated_at DATETIME NULL DEFAULT NULL,
  created_at DATETIME NULL DEFAULT NULL,
  updated_at DATETIME NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY subject_status (subject_type,subject_id,status),
  KEY subject_featured (subject_type,subject_id,featured),
  KEY status (status),
  KEY created (created_at)
) $charset_collate;";

		$statements[] = "CREATE TABLE {$p}ban_register (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  abn_norm VARCHAR(14) NOT NULL DEFAULT '',
  provider_name VARCHAR(255) NULL,
  action_type VARCHAR(160) NULL,
  details TEXT NULL,
  source VARCHAR(120) NULL,
  reference VARCHAR(255) NULL,
  effective_date DATE NULL DEFAULT NULL,
  expiry_date DATE NULL DEFAULT NULL,
  created_at DATETIME NULL DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY abn (abn_norm),
  KEY created (created_at)
) $charset_collate;";

		foreach ( $statements as $sql ) {
			dbDelta( $sql );
		}

		update_option( 'shuffles_ssj_db_version', self::DB_VERSION );
	}
}
