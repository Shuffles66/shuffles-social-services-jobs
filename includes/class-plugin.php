<?php
/**
 * Plugin orchestrator (singleton).
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Shuffles_SSJ_Plugin {

	/** @var Shuffles_SSJ_Plugin|null */
	private static $instance = null;

	/** @var Shuffles_SSJ_Settings */
	public $settings;

	/** @var Shuffles_SSJ_Integrations */
	public $integrations;

	/** @var Shuffles_SSJ_Taxonomy_Registrar */
	public $taxonomies;

	/** @var Shuffles_SSJ_CPT_Registrar */
	public $cpt;

	/** @var Shuffles_SSJ_Admin|null */
	public $admin = null;

	/** @var Shuffles_SSJ_Shortcodes */
	public $shortcodes;

	/** @var Shuffles_SSJ_Display */
	public $display;

	/** @var Shuffles_SSJ_Elementor */
	public $elementor;

	/** @var Shuffles_SSJ_SEO */
	public $seo;

	/** @var Shuffles_SSJ_Frontend_Forms */
	public $forms;

	/** @var Shuffles_SSJ_Verification */
	public $verification;

	/** @var Shuffles_SSJ_Alerts */
	public $alerts;

	/** @var Shuffles_SSJ_Field_Registry */
	public $field_registry;

	/** @var Shuffles_SSJ_CRM_Sync */
	public $crm_sync;

	/** @var Shuffles_SSJ_Cron */
	public $cron;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->settings     = new Shuffles_SSJ_Settings();
		$this->integrations = new Shuffles_SSJ_Integrations( $this->settings );
		$this->taxonomies   = new Shuffles_SSJ_Taxonomy_Registrar();
		$this->cpt          = new Shuffles_SSJ_CPT_Registrar();

		add_action( 'init', array( $this, 'on_init' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_front_assets' ) );

		// Phase 1 — front-end boards/forms, SEO, lifecycle.
		$this->shortcodes = new Shuffles_SSJ_Shortcodes( $this->settings );
		$this->shortcodes->register();
		$this->display = new Shuffles_SSJ_Display( $this->settings );
		$this->display->register();
		$this->elementor = new Shuffles_SSJ_Elementor();
		$this->elementor->register();
		$this->seo = new Shuffles_SSJ_SEO( $this->settings );
		$this->seo->register();
		$this->forms = new Shuffles_SSJ_Frontend_Forms();
		$this->forms->register();
		$this->verification = new Shuffles_SSJ_Verification();
		$this->verification->register();
		$this->alerts = new Shuffles_SSJ_Alerts( $this->settings );
		$this->alerts->register();
		// Smart synonym-aware keyword search (C5) — opts in per query via the sssj_smart_search var.
			Shuffles_SSJ_Search::init();
			Shuffles_SSJ_Resumes::register(); // Stored résumés (upload / manage / private serve).
			// An employer applied to with a résumé may view it.
			add_filter( 'shuffles_ssj_resume_can_view', array( 'Shuffles_SSJ_Plugin', 'resume_can_view' ), 10, 3 );
			// "View as" a demo user (admin-only, demo accounts only) + a toolbar "Return to admin".
			add_action( 'admin_post_sssj_view_as', array( 'Shuffles_SSJ_Plugin', 'handle_view_as' ) );
			add_action( 'admin_post_sssj_view_as_return', array( 'Shuffles_SSJ_Plugin', 'handle_view_as_return' ) );
			add_action( 'admin_bar_menu', array( 'Shuffles_SSJ_Plugin', 'view_as_admin_bar' ), 90 );
			// ABR enrichment whenever an ABN is recorded (free GUID required; no-op otherwise).
		add_action( 'shuffles_ssj_abn_recorded', array( 'Shuffles_SSJ_ABN', 'on_abn_recorded' ), 10, 3 );
		// NDIS register auto-scan hook whenever an NDIS provider number is recorded (best-effort).
		add_action( 'shuffles_ssj_ndis_recorded', array( 'Shuffles_SSJ_Org', 'on_ndis_recorded' ), 10, 2 );
		// Live NDIS Commission register reader: on-save lookup + monthly change-scan/alert.
		Shuffles_SSJ_NDIS_Register::set_settings( $this->settings );
		Shuffles_SSJ_NDIS_Register::register();
		$this->field_registry = new Shuffles_SSJ_Field_Registry();
		$this->field_registry->register();
		$this->crm_sync = new Shuffles_SSJ_CRM_Sync( $this->settings );
		$this->crm_sync->register();
		$this->cron = new Shuffles_SSJ_Cron();
		$this->cron->register();
		// Cron monitor — records last-run/next-run/status for the “Cron Jobs” tab.
		Shuffles_SSJ_Cron_Monitor::register();
		// Provider CSV importer (PoC) — admin-post handler.
		Shuffles_SSJ_Provider_Import::register();
		// Header-menu sync — mirrors [sssj_menu] into Appearance → Menus + maintains it.
		Shuffles_SSJ_Nav_Sync::register();

		// Daily licence re-validation (cached + grace-handled; never on the hot path).
		add_action( 'shuffles_ssj_daily', array( 'Shuffles_SSJ_License', 'check' ) );

		// Monetisation gates (provider-response filter; employer cap enforced at posting).
		Shuffles_SSJ_Monetisation::register();

		// Compliance & verification — credential add/delete/serve + admin approve/reject handlers.
		Shuffles_SSJ_Credentials::register();

		// Apply schema upgrades in place on already-installed sites (cheap version guard).
		add_action( 'admin_init', array( 'Shuffles_SSJ_Activator', 'maybe_upgrade' ) );

		if ( is_admin() && class_exists( 'Shuffles_SSJ_Admin' ) ) {
			$this->admin = new Shuffles_SSJ_Admin( $this->settings, $this->integrations );
			$this->admin->register();
		}
	}

	/**
	 * Register entities on init.
	 */
	public function on_init() {
		load_plugin_textdomain( 'shuffles-social-services-jobs', false, dirname( SHUFFLES_SSJ_BASENAME ) . '/languages' );
		$this->cpt->register_post_types();
		$this->taxonomies->register_taxonomies();
		$this->cpt->register_meta();
	}

	/**
	 * Register (not enqueue) the design-system stylesheet. Boards/forms enqueue it from Phase 1.
	 */
	public function register_front_assets() {
		wp_register_style( 'sssj', SHUFFLES_SSJ_URL . 'public/assets/css/sssj.css', array(), SHUFFLES_SSJ_VERSION );
	}

	/** Filter: allow an employer who was applied to with a résumé to view it. */
	public static function resume_can_view( $allowed, $row, $viewer_id ) {
		if ( $allowed || ! class_exists( 'Shuffles_SSJ_Applications' ) ) {
			return $allowed;
		}
		return Shuffles_SSJ_Applications::employer_can_view_resume( (int) $row->id, (int) $viewer_id );
	}

	/* ----------------------------------------------------------- "View as" demo users (QA) */

	const VIEWAS_COOKIE = 'sssj_viewas';

	/** Admin → impersonate a DEMO user. Strictly: manage_options + nonce + target is a demo account. */
	public static function handle_view_as() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_view_as' );
		$target = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : 0;
		if ( ! $target || ! get_user_by( 'id', $target ) || ! get_user_meta( $target, '_sssj_demo', true ) ) {
			wp_die( esc_html__( '“View as” is only available for demo/test accounts.', 'shuffles-social-services-jobs' ) );
		}
		$origin = get_current_user_id();
		$token  = wp_generate_password( 24, false, false );
		set_transient( 'sssj_viewas_' . $token, $origin, HOUR_IN_SECONDS );
		$path = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
		$dom  = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '';
		setcookie( self::VIEWAS_COOKIE, $token, time() + HOUR_IN_SECONDS, $path, $dom, is_ssl(), true );
		wp_clear_auth_cookie();
		wp_set_current_user( $target );
		wp_set_auth_cookie( $target, false );
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}

	/** Return from impersonation to the original admin (validated via the stored origin id). */
	public static function handle_view_as_return() {
		check_admin_referer( 'sssj_view_as_return' );
		$token  = isset( $_COOKIE[ self::VIEWAS_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::VIEWAS_COOKIE ] ) ) : '';
		$origin = $token ? (int) get_transient( 'sssj_viewas_' . $token ) : 0;
		$path   = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
		$dom    = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '';
		if ( $token ) {
			delete_transient( 'sssj_viewas_' . $token );
		}
		setcookie( self::VIEWAS_COOKIE, '', time() - 3600, $path, $dom, is_ssl(), true );
		if ( $origin && user_can( $origin, 'manage_options' ) ) {
			wp_clear_auth_cookie();
			wp_set_current_user( $origin );
			wp_set_auth_cookie( $origin, false );
			wp_safe_redirect( admin_url( 'admin.php?page=shuffles-ssj&tab=demo' ) );
		} else {
			wp_safe_redirect( home_url( '/' ) );
		}
		exit;
	}

	/** Toolbar node shown while impersonating: "Viewing as X — Return to admin". */
	public static function view_as_admin_bar( $bar ) {
		if ( empty( $_COOKIE[ self::VIEWAS_COOKIE ] ) ) {
			return;
		}
		$token  = sanitize_text_field( wp_unslash( $_COOKIE[ self::VIEWAS_COOKIE ] ) );
		$origin = $token ? (int) get_transient( 'sssj_viewas_' . $token ) : 0;
		if ( ! $origin ) {
			return; // stale cookie — ignore
		}
		$cur = wp_get_current_user();
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=sssj_view_as_return' ), 'sssj_view_as_return' );
		$bar->add_node( array(
			'id'    => 'sssj-viewas',
			'title' => '👁 ' . sprintf( __( 'Viewing as %s — Return to admin', 'shuffles-social-services-jobs' ), esc_html( $cur->display_name ) ),
			'href'  => $url,
			'meta'  => array( 'class' => 'sssj-viewas-bar' ),
		) );
	}
}
