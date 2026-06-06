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

	/** @var Shuffles_SSJ_SEO */
	public $seo;

	/** @var Shuffles_SSJ_Frontend_Forms */
	public $forms;

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
		$this->seo = new Shuffles_SSJ_SEO( $this->settings );
		$this->seo->register();
		$this->forms = new Shuffles_SSJ_Frontend_Forms();
		$this->forms->register();
		$this->cron = new Shuffles_SSJ_Cron();
		$this->cron->register();

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
}
