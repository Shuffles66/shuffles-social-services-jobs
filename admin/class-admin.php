<?php
/**
 * Admin: top-level menu (parent for the CPTs) + tabbed settings page.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Admin {

	const PAGE_SLUG = 'shuffles-ssj';

	/** @var Shuffles_SSJ_Settings */
	private $settings;

	/** @var Shuffles_SSJ_Integrations */
	private $integrations;

	public function __construct( $settings, $integrations ) {
		$this->settings     = $settings;
		$this->integrations = $integrations;
	}

	const REPO_URL = 'https://github.com/Shuffles66/shuffles-social-services-jobs';
	const SITE_URL = 'https://shuffles.com.au';

	public function register() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . SHUFFLES_SSJ_BASENAME, array( $this, 'action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'row_meta' ), 10, 2 );
		add_action( 'wp_ajax_sssj_create_page', array( $this, 'ajax_create_page' ) );
		add_action( 'admin_post_sssj_license_activate', array( $this, 'handle_license' ) );
		add_action( 'admin_post_sssj_license_deactivate', array( $this, 'handle_license' ) );
		add_action( 'admin_post_sssj_review_moderate', array( $this, 'handle_review_moderate' ) );
		add_action( 'admin_post_sssj_testimonial_moderate', array( $this, 'handle_testimonial_moderate' ) );
		add_action( 'admin_post_sssj_ban_add', array( $this, 'handle_ban_add' ) );
		add_action( 'admin_post_sssj_ban_delete', array( $this, 'handle_ban_delete' ) );
		add_action( 'admin_post_sssj_ban_import', array( $this, 'handle_ban_import' ) );
		add_action( 'admin_post_sssj_ban_rescan', array( $this, 'handle_ban_rescan' ) );
		add_action( 'admin_post_sssj_ban_clear', array( $this, 'handle_ban_clear' ) );
	}

	/**
	 * Approve / reject / delete a review from the moderation queue (admin only).
	 */
	public function handle_review_moderate() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_review_moderate' );
		$id = isset( $_POST['review_id'] ) ? absint( $_POST['review_id'] ) : 0;
		$op = isset( $_POST['op'] ) ? sanitize_key( wp_unslash( $_POST['op'] ) ) : '';
		if ( $id && class_exists( 'Shuffles_SSJ_Reviews' ) ) {
			$map = array( 'approve' => 'approved', 'reject' => 'rejected', 'pending' => 'pending' );
			if ( isset( $map[ $op ] ) ) {
				Shuffles_SSJ_Reviews::set_status( $id, $map[ $op ], get_current_user_id() );
			}
		}
		$back = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=shuffles-ssj&tab=reviews' );
		wp_safe_redirect( $back );
		exit;
	}

	/** Approve / reject a testimonial (admin moderation). */
	public function handle_testimonial_moderate() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_testimonial_moderate' );
		$id = isset( $_POST['testi_id'] ) ? absint( $_POST['testi_id'] ) : 0;
		$op = isset( $_POST['op'] ) ? sanitize_key( wp_unslash( $_POST['op'] ) ) : '';
		if ( $id && class_exists( 'Shuffles_SSJ_Testimonials' ) ) {
			$map = array( 'approve' => 'approved', 'reject' => 'rejected', 'pending' => 'pending' );
			if ( isset( $map[ $op ] ) ) {
				Shuffles_SSJ_Testimonials::set_status( $id, $map[ $op ], get_current_user_id() );
			} elseif ( 'delete' === $op ) {
				Shuffles_SSJ_Testimonials::delete( $id, get_current_user_id() );
			}
		}
		$back = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=shuffles-ssj&tab=testimonials' );
		wp_safe_redirect( $back );
		exit;
	}

	/* ------------------------------------------------------- Banned-provider register (safety) */

	private function ban_back( $flag = '' ) {
		$back = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=shuffles-ssj&tab=safety' );
		return $flag ? add_query_arg( 'sssj_ban', $flag, remove_query_arg( 'sssj_ban', $back ) ) : $back;
	}

	/** Add a single entry to the banned register. */
	public function handle_ban_add() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_ban_add' );
		$res = class_exists( 'Shuffles_SSJ_Ban_Register' ) ? Shuffles_SSJ_Ban_Register::add( array(
			'abn'            => isset( $_POST['abn'] ) ? wp_unslash( $_POST['abn'] ) : '',
			'provider_name'  => isset( $_POST['provider_name'] ) ? wp_unslash( $_POST['provider_name'] ) : '',
			'action_type'    => isset( $_POST['action_type'] ) ? wp_unslash( $_POST['action_type'] ) : '',
			'details'        => isset( $_POST['details'] ) ? wp_unslash( $_POST['details'] ) : '',
			'source'         => isset( $_POST['source'] ) ? wp_unslash( $_POST['source'] ) : 'Manual',
			'reference'      => isset( $_POST['reference'] ) ? wp_unslash( $_POST['reference'] ) : '',
			'effective_date' => isset( $_POST['effective_date'] ) ? wp_unslash( $_POST['effective_date'] ) : '',
			'expiry_date'    => isset( $_POST['expiry_date'] ) ? wp_unslash( $_POST['expiry_date'] ) : '',
		) ) : false;
		// Re-scan so an existing matching listing gets flagged straight away.
		if ( $res && class_exists( 'Shuffles_SSJ_Ban_Register' ) ) {
			Shuffles_SSJ_Ban_Register::rescan_all();
		}
		wp_safe_redirect( $this->ban_back( false === $res ? 'badabn' : ( 0 === $res ? 'dupe' : 'added' ) ) );
		exit;
	}

	public function handle_ban_delete() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_ban_delete' );
		$id = isset( $_POST['ban_id'] ) ? absint( $_POST['ban_id'] ) : 0;
		if ( $id && class_exists( 'Shuffles_SSJ_Ban_Register' ) ) {
			Shuffles_SSJ_Ban_Register::delete( $id );
			Shuffles_SSJ_Ban_Register::rescan_all(); // clears any flag that no longer matches
		}
		wp_safe_redirect( $this->ban_back( 'deleted' ) );
		exit;
	}

	/** Import the NDIS Commission compliance CSV into the register. */
	public function handle_ban_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_ban_import' );
		if ( ! class_exists( 'Shuffles_SSJ_Ban_Register' ) || empty( $_FILES['csv']['tmp_name'] ) || ! is_uploaded_file( $_FILES['csv']['tmp_name'] ) ) {
			wp_safe_redirect( $this->ban_back( 'nofile' ) );
			exit;
		}
		$name = isset( $_FILES['csv']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['csv']['name'] ) ) : '';
		$ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, array( 'csv', 'txt' ), true ) ) {
			wp_safe_redirect( $this->ban_back( 'badfile' ) );
			exit;
		}
		$src    = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : 'NDIS Commission';
		$report = Shuffles_SSJ_Ban_Register::import_csv( $_FILES['csv']['tmp_name'], '' !== $src ? $src : 'NDIS Commission' );
		set_transient( 'sssj_ban_import_' . get_current_user_id(), $report, 5 * MINUTE_IN_SECONDS );
		if ( empty( $report['error'] ) ) {
			Shuffles_SSJ_Ban_Register::rescan_all();
		}
		wp_safe_redirect( $this->ban_back( empty( $report['error'] ) ? 'imported' : 'importerr' ) );
		exit;
	}

	public function handle_ban_rescan() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_ban_rescan' );
		if ( class_exists( 'Shuffles_SSJ_Ban_Register' ) ) {
			list( $scanned, $flagged ) = Shuffles_SSJ_Ban_Register::rescan_all();
			set_transient( 'sssj_ban_rescan_' . get_current_user_id(), array( 'scanned' => $scanned, 'flagged' => $flagged ), 5 * MINUTE_IN_SECONDS );
		}
		wp_safe_redirect( $this->ban_back( 'rescanned' ) );
		exit;
	}

	/** Dismiss a safety flag on a specific listing (admin reviewed it). */
	public function handle_ban_clear() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_ban_clear' );
		$eid = isset( $_POST['entity_id'] ) ? absint( $_POST['entity_id'] ) : 0;
		if ( $eid && class_exists( 'Shuffles_SSJ_Ban_Register' ) ) {
			Shuffles_SSJ_Ban_Register::clear_flag( $eid );
		}
		wp_safe_redirect( $this->ban_back( 'cleared' ) );
		exit;
	}

	/**
	 * Activate / deactivate the resale licence (uses the saved key + product ID).
	 */
	public function handle_license() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_license' );
		$action = isset( $_POST['action'] ) ? sanitize_key( wp_unslash( $_POST['action'] ) ) : '';
		if ( 'sssj_license_deactivate' === $action ) {
			Shuffles_SSJ_License::deactivate();
		} else {
			Shuffles_SSJ_License::activate();
		}
		wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE_SLUG, 'tab' => 'licensing', 'sssj_lic' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Left-side action links (next to Activate | Delete): add Settings.
	 *
	 * @param string[] $links Existing links.
	 * @return string[]
	 */
	public function action_links( $links ) {
		$settings = '<a href="' . esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ) . '">' . esc_html__( 'Settings', 'shuffles-social-services-jobs' ) . '</a>';
		array_unshift( $links, $settings );
		return $links;
	}

	/**
	 * Right-side meta links: Shuffles website, Documentation, View details (→ GitHub).
	 *
	 * @param string[] $links Existing meta links.
	 * @param string   $file  Plugin file being rendered.
	 * @return string[]
	 */
	public function row_meta( $links, $file ) {
		if ( SHUFFLES_SSJ_BASENAME !== $file ) {
			return $links;
		}
		$brand = (string) $this->settings->get( 'brand_url', '' );
		if ( '' === $brand ) {
			$brand = self::SITE_URL;
		}
		$links[] = '<a href="' . esc_url( $brand ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Shuffles website', 'shuffles-social-services-jobs' ) . '</a>';
		$links[] = '<a href="' . esc_url( self::REPO_URL . '/tree/main/docs' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Documentation', 'shuffles-social-services-jobs' ) . '</a>';
		$links[] = '<a href="' . esc_url( self::REPO_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'View details', 'shuffles-social-services-jobs' ) . '</a>';
		return $links;
	}

	public function register_menu() {
		add_menu_page(
			__( 'Jobs & Engagements', 'shuffles-social-services-jobs' ),
			__( 'Jobs & Engagements', 'shuffles-social-services-jobs' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' ),
			'dashicons-businessperson',
			56
		);
		add_submenu_page(
			self::PAGE_SLUG,
			__( 'Settings', 'shuffles-social-services-jobs' ),
			__( 'Settings', 'shuffles-social-services-jobs' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);

		$pending = class_exists( 'Shuffles_SSJ_Credentials' ) ? Shuffles_SSJ_Credentials::count_by_status( 'pending' ) : 0;
		$badge   = $pending ? ' <span class="awaiting-mod count-' . (int) $pending . '"><span class="pending-count">' . (int) $pending . '</span></span>' : '';
		add_submenu_page(
			self::PAGE_SLUG,
			__( 'Verification', 'shuffles-social-services-jobs' ),
			__( 'Verification', 'shuffles-social-services-jobs' ) . $badge, // phpcs:ignore WordPress.WP.I18n
			'manage_options',
			'shuffles-ssj-verify',
			array( $this, 'render_verification' )
		);
	}

	public function register_setting() {
		register_setting(
			Shuffles_SSJ_Settings::SETTINGS_GROUP,
			Shuffles_SSJ_Settings::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this->settings, 'sanitize' ),
				'default'           => array(),
			)
		);
	}

	public function enqueue_assets( $hook ) {
		if ( 'toplevel_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style( 'sssj-admin', SHUFFLES_SSJ_URL . 'admin/assets/css/sssj-admin.css', array(), SHUFFLES_SSJ_VERSION );
		wp_enqueue_script( 'sssj-admin', SHUFFLES_SSJ_URL . 'admin/assets/js/sssj-admin.js', array(), SHUFFLES_SSJ_VERSION, true );

		// Appearance tab: load the front-end styles (so the preview looks real) + the Style Studio.
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'appearance' === $tab ) {
			wp_enqueue_style( 'sssj', SHUFFLES_SSJ_URL . 'public/assets/css/sssj.css', array(), SHUFFLES_SSJ_VERSION );
			wp_enqueue_script( 'sssj-studio', SHUFFLES_SSJ_URL . 'admin/assets/js/sssj-studio.js', array(), SHUFFLES_SSJ_VERSION, true );
		}
		if ( 'testing' === $tab ) {
			wp_enqueue_style( 'sssj', SHUFFLES_SSJ_URL . 'public/assets/css/sssj.css', array(), SHUFFLES_SSJ_VERSION );
			wp_enqueue_script( 'sssj-tests', SHUFFLES_SSJ_URL . 'public/assets/js/sssj-tests.js', array(), SHUFFLES_SSJ_VERSION, true );
		}
		if ( 'guides' === $tab || 'workflows' === $tab || 'policies' === $tab ) {
			wp_enqueue_style( 'sssj', SHUFFLES_SSJ_URL . 'public/assets/css/sssj.css', array(), SHUFFLES_SSJ_VERSION );
			wp_enqueue_script( 'sssj-guides', SHUFFLES_SSJ_URL . 'public/assets/js/sssj-guides.js', array(), SHUFFLES_SSJ_VERSION, true );
		}
		if ( 'reviews' === $tab || 'marketing' === $tab ) {
			wp_enqueue_style( 'sssj', SHUFFLES_SSJ_URL . 'public/assets/css/sssj.css', array(), SHUFFLES_SSJ_VERSION );
		}
		if ( 'crm' === $tab || 'fields' === $tab ) {
			// Searchable pill pickers for the tag/list + field-option selects (design directive).
			wp_enqueue_style( 'sssj', SHUFFLES_SSJ_URL . 'public/assets/css/sssj.css', array(), SHUFFLES_SSJ_VERSION );
			wp_enqueue_style( 'sssj-tomselect', SHUFFLES_SSJ_URL . 'public/assets/vendor/tom-select/tom-select.min.css', array(), '2.3.1' );
			wp_enqueue_script( 'sssj-tomselect', SHUFFLES_SSJ_URL . 'public/assets/vendor/tom-select/tom-select.complete.min.js', array(), '2.3.1', true );
			wp_enqueue_script( 'sssj-select', SHUFFLES_SSJ_URL . 'public/assets/js/sssj-select.js', array( 'sssj-tomselect' ), SHUFFLES_SSJ_VERSION, true );
		}
		wp_localize_script(
			'sssj-admin',
			'SSJ_Admin',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'sssj_admin' ),
				'creating'    => __( 'Creating…', 'shuffles-social-services-jobs' ),
				'createLabel' => __( 'Create page', 'shuffles-social-services-jobs' ),
				'error'       => __( 'Could not create the page. Please try again.', 'shuffles-social-services-jobs' ),
			)
		);
	}

	/* ----- Tab model ----- */

	/**
	 * Tabs: slug => [ code, label, domain ].
	 *
	 * @return array
	 */
	public function tabs() {
		return array(
			'general'      => array( 'T1', __( 'General', 'shuffles-social-services-jobs' ), 'slate' ),
			'shortcodes'   => array( 'T17', __( 'Shortcodes', 'shuffles-social-services-jobs' ), 'orange' ),
			'appearance'   => array( 'T16', __( 'Appearance', 'shuffles-social-services-jobs' ), 'orange' ),
			'boards'       => array( 'T2', __( 'Boards & Segregation', 'shuffles-social-services-jobs' ), 'indigo' ),
			'pages'        => array( 'T18', __( 'Pages', 'shuffles-social-services-jobs' ), 'orange' ),
			'taxonomies'   => array( 'T3', __( 'Taxonomies & Seeds', 'shuffles-social-services-jobs' ), 'indigo' ),
			'compliance'   => array( 'T4', __( 'Compliance & Credentials', 'shuffles-social-services-jobs' ), 'amber' ),
			'funding'      => array( 'T5', __( 'Funding Sources', 'shuffles-social-services-jobs' ), 'amber' ),
			'cald'         => array( 'T6', __( 'CALD & Access', 'shuffles-social-services-jobs' ), 'teal' ),
			'seo'          => array( 'T7', __( 'SEO', 'shuffles-social-services-jobs' ), 'slate' ),
			'maps'         => array( 'T8', __( 'Maps & Location', 'shuffles-social-services-jobs' ), 'slate' ),
			'matching'     => array( 'T9', __( 'Matching', 'shuffles-social-services-jobs' ), 'blue' ),
			'monetisation' => array( 'T10', __( 'Monetisation', 'shuffles-social-services-jobs' ), 'orange' ),
			'ads'          => array( 'T31', __( 'Ads (Advanced Ads)', 'shuffles-social-services-jobs' ), 'orange' ),
			'licensing'    => array( 'T11', __( 'Licensing', 'shuffles-social-services-jobs' ), 'orange' ),
			'integrations' => array( 'T12', __( 'Integrations', 'shuffles-social-services-jobs' ), 'blue' ),
			'rendering'    => array( 'T34', __( 'Asset Rendering', 'shuffles-social-services-jobs' ), 'blue' ),
			'profilecard'  => array( 'T37', __( 'AI Profile Card', 'shuffles-social-services-jobs' ), 'teal' ),
			'fields'       => array( 'T21', __( 'Profile Fields', 'shuffles-social-services-jobs' ), 'indigo' ),
			'crm'          => array( 'T22', __( 'CRM Sync', 'shuffles-social-services-jobs' ), 'blue' ),
			'alerts'       => array( 'T23', __( 'Email Alerts', 'shuffles-social-services-jobs' ), 'orange' ),
			'privacy'      => array( 'T13', __( 'Privacy & Moderation', 'shuffles-social-services-jobs' ), 'amber' ),
			'reviews'      => array( 'T29', __( 'Reviews & Ratings', 'shuffles-social-services-jobs' ), 'amber' ),
			'testimonials' => array( 'T35', __( 'Testimonials', 'shuffles-social-services-jobs' ), 'amber' ),
			'safety'       => array( 'T36', __( 'Safety Register', 'shuffles-social-services-jobs' ), 'amber' ),
			'guides'       => array( 'T20', __( 'Guides', 'shuffles-social-services-jobs' ), 'orange' ),
			'workflows'    => array( 'T28', __( 'How-to Workflows', 'shuffles-social-services-jobs' ), 'orange' ),
			'policies'     => array( 'T30', __( 'Policies', 'shuffles-social-services-jobs' ), 'amber' ),
			'marketing'    => array( 'T33', __( 'Marketing', 'shuffles-social-services-jobs' ), 'orange' ),
			'logic'        => array( 'T25', __( 'Business Logic', 'shuffles-social-services-jobs' ), 'slate' ),
			'testing'      => array( 'T19', __( 'Testing', 'shuffles-social-services-jobs' ), 'slate' ),
			'demo'         => array( 'T27', __( 'Demo Users', 'shuffles-social-services-jobs' ), 'teal' ),
			'cron'         => array( 'T24', __( 'Cron Job List & Status', 'shuffles-social-services-jobs' ), 'slate' ),
			'import'       => array( 'T26', __( 'Provider Import (beta)', 'shuffles-social-services-jobs' ), 'indigo' ),
			'diagnostics'  => array( 'T14', __( 'Diagnostics', 'shuffles-social-services-jobs' ), 'slate' ),
			'changelog'    => array( 'T15', __( 'Changelog', 'shuffles-social-services-jobs' ), 'slate' ),
			'pm'           => array( 'PM', __( 'Project Management', 'shuffles-social-services-jobs' ), 'slate' ),
		);
	}

	/**
	 * Tabs in the admin's chosen order: 'grouped' (curated default), 'tnum' (by the T-number),
	 * or 'alpha' (alphabetical by label).
	 */
	public function sorted_tabs() {
		$tabs  = $this->tabs();
		$order = (string) $this->settings->get( 'admin_tab_order', 'grouped' );
		if ( 'tnum' === $order ) {
			uasort( $tabs, function ( $a, $b ) {
				$na = is_numeric( substr( (string) $a[0], 1 ) ) ? (int) substr( (string) $a[0], 1 ) : 9999;
				$nb = is_numeric( substr( (string) $b[0], 1 ) ) ? (int) substr( (string) $b[0], 1 ) : 9999;
				return $na <=> $nb;
			} );
		} elseif ( 'alpha' === $order ) {
			uasort( $tabs, function ( $a, $b ) {
				return strcasecmp( (string) $a[1], (string) $b[1] );
			} );
		}
		return $tabs;
	}

	public function dot_colour( $domain ) {
		$map = array(
			'slate'  => '#64748b',
			'indigo' => '#6366f1',
			'amber'  => '#b45309',
			'teal'   => '#0d9488',
			'blue'   => '#2563eb',
			'orange' => '#ea580c',
		);
		return isset( $map[ $domain ] ) ? $map[ $domain ] : '#64748b';
	}

	public function current_tab() {
		$tabs = $this->tabs();
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( (string) $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $tabs[ $tab ] ) ? $tab : 'general';
	}

	public function tab_url( $slug ) {
		return add_query_arg(
			array(
				'page' => self::PAGE_SLUG,
				'tab'  => $slug,
			),
			admin_url( 'admin.php' )
		);
	}

	/* ----- Accessors for the view ----- */

	public function settings() {
		return $this->settings;
	}

	public function integrations() {
		return $this->integrations;
	}

	/* ----- Field helpers (field name = shuffles_ssj_settings[key]) ----- */

	public function field_name( $key ) {
		return Shuffles_SSJ_Settings::OPTION_KEY . '[' . $key . ']';
	}

	/**
	 * Text field. $opts: 'placeholder' (string), 'suggest' (string[] → a <datalist> of suggested values).
	 */
	public function text_field( $key, $label, $help = '', $type = 'text', $opts = array() ) {
		$val         = (string) $this->settings->get( $key, '' );
		$placeholder = isset( $opts['placeholder'] ) ? (string) $opts['placeholder'] : '';
		$suggest     = ( isset( $opts['suggest'] ) && is_array( $opts['suggest'] ) ) ? $opts['suggest'] : array();
		$list_id     = 'sssj-' . $key . '-list';
		echo '<tr><th scope="row"><label for="sssj-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
		echo '<input type="' . esc_attr( $type ) . '" class="regular-text" id="sssj-' . esc_attr( $key ) . '" name="' . esc_attr( $this->field_name( $key ) ) . '" value="' . esc_attr( $val ) . '"';
		if ( '' !== $placeholder ) {
			echo ' placeholder="' . esc_attr( $placeholder ) . '"';
		}
		if ( ! empty( $suggest ) ) {
			echo ' list="' . esc_attr( $list_id ) . '"';
		}
		echo ' />';
		if ( ! empty( $suggest ) ) {
			echo '<datalist id="' . esc_attr( $list_id ) . '">';
			foreach ( $suggest as $s ) {
				echo '<option value="' . esc_attr( $s ) . '"></option>';
			}
			echo '</datalist>';
		}
		if ( $help ) {
			echo '<p class="description">' . esc_html( $help ) . '</p>';
		}
		echo '</td></tr>';
	}

	public function number_field( $key, $label, $help = '', $min = 0, $max = '' ) {
		$val = (string) $this->settings->get( $key, '' );
		echo '<tr><th scope="row"><label for="sssj-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
		echo '<input type="number" min="' . esc_attr( (string) $min ) . '"' . ( '' !== $max ? ' max="' . esc_attr( (string) $max ) . '"' : '' ) . ' id="sssj-' . esc_attr( $key ) . '" name="' . esc_attr( $this->field_name( $key ) ) . '" value="' . esc_attr( $val ) . '" />';
		if ( $help ) {
			echo '<p class="description">' . esc_html( $help ) . '</p>';
		}
		echo '</td></tr>';
	}

	public function checkbox_field( $key, $label, $help = '' ) {
		$on = '1' === (string) $this->settings->get( $key, '0' );
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
		echo '<label><input type="checkbox" name="' . esc_attr( $this->field_name( $key ) ) . '" value="1" ' . checked( $on, true, false ) . ' /> ' . esc_html__( 'Enabled', 'shuffles-social-services-jobs' ) . '</label>';
		if ( $help ) {
			echo '<p class="description">' . esc_html( $help ) . '</p>';
		}
		echo '</td></tr>';
	}

	/**
	 * Generic <select> field. $options = array( value => label ).
	 */
	public function select_field( $key, $label, $options, $help = '', $default = '' ) {
		$val = (string) $this->settings->get( $key, $default );
		echo '<tr><th scope="row"><label for="sssj-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
		echo '<select id="sssj-' . esc_attr( $key ) . '" name="' . esc_attr( $this->field_name( $key ) ) . '">';
		foreach ( (array) $options as $ov => $ol ) {
			echo '<option value="' . esc_attr( $ov ) . '" ' . selected( $val, (string) $ov, false ) . '>' . esc_html( $ol ) . '</option>';
		}
		echo '</select>';
		if ( $help ) {
			echo '<p class="description">' . wp_kses_post( $help ) . '</p>';
		}
		echo '</td></tr>';
	}

	/**
	 * Multi-line textarea field. $help may contain inline markup (kses-filtered).
	 */
	public function textarea_field( $key, $label, $help = '', $rows = 4, $placeholder = '' ) {
		$val = (string) $this->settings->get( $key, '' );
		echo '<tr><th scope="row"><label for="sssj-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
		echo '<textarea class="large-text code" rows="' . esc_attr( (string) (int) $rows ) . '" id="sssj-' . esc_attr( $key ) . '" name="' . esc_attr( $this->field_name( $key ) ) . '" placeholder="' . esc_attr( $placeholder ) . '">' . esc_textarea( $val ) . '</textarea>';
		if ( $help ) {
			echo '<p class="description">' . wp_kses_post( $help ) . '</p>';
		}
		echo '</td></tr>';
	}

	/** Representative mock-board markup for the Style Studio live preview (admin-only sample). */
	public function studio_preview_html() {
		ob_start();
		?>
		<nav class="sssj sssj-nav"><span class="sssj-nav__brand">Jobs</span>
			<ul class="sssj-nav__list">
				<li class="sssj-nav__item"><a href="#" onclick="return false">Jobs</a></li>
				<li class="sssj-nav__item"><a href="#" onclick="return false" aria-current="page">Find a worker</a></li>
				<li class="sssj-nav__item"><a href="#" onclick="return false">Organisations</a></li>
				<li class="sssj-nav__item sssj-nav__item--cta"><a href="#" onclick="return false">Register</a></li>
			</ul>
		</nav>
		<div class="sssj-panel" style="margin:12px 0">
			<div class="sssj-row">
				<input class="sssj-input" placeholder="Search jobs…" style="flex:1;min-width:150px" />
				<select class="sssj-select"><option>All categories</option></select>
				<button type="button" class="sssj-btn sssj-btn--primary">Filter</button>
			</div>
		</div>
		<div class="sssj-grid">
			<article class="sssj-card sssj-card--abn">
				<h3 style="margin-top:0"><a href="#" onclick="return false">Support Worker, Disability</a></h3>
				<div class="sssj-row"><span class="sssj-badge sssj-badge--abn">ABN (contractor)</span> <span class="sssj-badge">Ongoing</span></div>
				<p>📍 Parramatta NSW</p><p>💲 45 – 60 / hour</p>
				<a class="sssj-btn sssj-btn--secondary sssj-btn--sm" href="#" onclick="return false">View job</a>
			</article>
			<article class="sssj-card sssj-card--tfn sssj-card--featured">
				<h3 style="margin-top:0"><a href="#" onclick="return false">Aged Care Assistant</a></h3>
				<div class="sssj-row"><span class="sssj-badge sssj-badge--featured">★ Featured</span> <span class="sssj-badge sssj-badge--tfn">TFN (employee)</span></div>
				<p>📍 Geelong VIC</p><p>💲 32 / hour</p>
				<a class="sssj-btn sssj-btn--secondary sssj-btn--sm" href="#" onclick="return false">View job</a>
			</article>
			<article class="sssj-card sssj-card--need">
				<h3 style="margin-top:0">Participant request</h3>
				<div class="sssj-row"><span class="sssj-badge sssj-badge--need">Support</span> <span class="sssj-badge sssj-badge--verified">✓ Verified preferred</span></div>
				<p>📍 Armidale NSW</p>
				<a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="#" onclick="return false">Respond</a>
			</article>
		</div>
		<?php
		return ob_get_clean();
	}

	public function compliance_select() {
		$current = (string) $this->settings->get( 'compliance_profile', '' );
		$terms   = get_terms(
			array(
				'taxonomy'   => 'sssjt_compliance_profile',
				'hide_empty' => false,
			)
		);
		echo '<tr><th scope="row"><label for="sssj-compliance_profile">' . esc_html__( 'Default compliance profile', 'shuffles-social-services-jobs' ) . '</label></th><td>';
		echo '<select id="sssj-compliance_profile" name="' . esc_attr( $this->field_name( 'compliance_profile' ) ) . '">';
		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				echo '<option value="' . esc_attr( $term->name ) . '" ' . selected( $current, $term->name, false ) . '>' . esc_html( $term->name ) . '</option>';
			}
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Drives which credentials are required for the sector this deployment serves.', 'shuffles-social-services-jobs' ) . '</p>';
		echo '</td></tr>';
	}

	/**
	 * Masked API/secret-key field with how-to + what-it-does instructions (standing rule).
	 * Renders empty (never echoes the secret); a blank submission keeps the stored value.
	 *
	 * @param string $key      Setting key.
	 * @param string $label    Field label.
	 * @param string $how_html "How to get it" help (limited HTML allowed).
	 * @param string $what     "What it does" help (plain text).
	 */
	public function key_field( $key, $label, $how_html, $what ) {
		$stored = (string) $this->settings->get( $key, '' );
		$last4  = '' !== $stored ? substr( $stored, -4 ) : '';
		$ph     = '' !== $stored
			? sprintf( '•••• •••• %s, %s', $last4, __( 'leave blank to keep', 'shuffles-social-services-jobs' ) )
			: __( 'Enter key', 'shuffles-social-services-jobs' );
		$allowed = array(
			'a'      => array( 'href' => array(), 'target' => array(), 'rel' => array() ),
			'strong' => array(),
			'em'     => array(),
			'code'   => array(),
			'br'     => array(),
			'ul'     => array(),
			'li'     => array(),
		);
		echo '<tr><th scope="row"><label for="sssj-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
		echo '<input type="text" autocomplete="off" spellcheck="false" class="regular-text" id="sssj-' . esc_attr( $key ) . '" name="' . esc_attr( $this->field_name( $key ) ) . '" value="" placeholder="' . esc_attr( $ph ) . '" />';
		echo '<p class="description"><strong>' . esc_html__( 'What it does:', 'shuffles-social-services-jobs' ) . '</strong> ' . esc_html( $what ) . '</p>';
		echo '<p class="description"><strong>' . esc_html__( 'How to get it:', 'shuffles-social-services-jobs' ) . '</strong> ' . wp_kses( $how_html, $allowed ) . '</p>';
		echo '</td></tr>';
	}

	/**
	 * Page-picker field (standing rule): lookup existing + create-with-shortcode + edit/view links.
	 *
	 * @param string $key       Setting key (stores the page ID).
	 * @param string $label     Field label.
	 * @param string $shortcode Shortcode to insert when creating the page.
	 * @param string $help      Extra help text.
	 */
	public function page_picker_field( $key, $label, $shortcode, $help = '' ) {
		$val = (int) $this->settings->get( $key, 0 );
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
		echo '<div class="sssj-page-picker" data-key="' . esc_attr( $key ) . '" data-shortcode="' . esc_attr( $shortcode ) . '">';
		wp_dropdown_pages(
			array(
				'name'              => $this->field_name( $key ),
				'id'                => 'sssj-' . $key,
				'selected'          => $val,
				'show_option_none'  => __( '- Select a page -', 'shuffles-social-services-jobs' ),
				'option_none_value' => '0',
				'class'             => 'sssj-page-select',
			)
		);
		echo ' <button type="button" class="button sssj-create-page" data-title="' . esc_attr( $label ) . '">' . esc_html__( 'Create page', 'shuffles-social-services-jobs' ) . '</button>';
		echo ' <span class="sssj-page-links">';
		if ( $val && get_post( $val ) ) {
			echo '<a href="' . esc_url( (string) get_edit_post_link( $val ) ) . '">' . esc_html__( 'Edit', 'shuffles-social-services-jobs' ) . '</a> | <a href="' . esc_url( (string) get_permalink( $val ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View', 'shuffles-social-services-jobs' ) . '</a>';
		}
		echo '</span>';
		echo '<p class="description">' . esc_html( $help ) . ' ' . sprintf(
			/* translators: %s: shortcode in a <code> tag */
			esc_html__( 'Shortcode: %s', 'shuffles-social-services-jobs' ),
			'<code>' . esc_html( $shortcode ) . '</code>'
		) . '</p>';
		echo '</div></td></tr>';
	}

	/**
	 * AJAX: create a page containing the given shortcode and link it to the setting.
	 */
	public function ajax_create_page() {
		check_ajax_referer( 'sssj_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'msg' => 'forbidden' ), 403 );
		}
		$key       = isset( $_POST['key'] ) ? sanitize_key( wp_unslash( $_POST['key'] ) ) : '';
		$shortcode = isset( $_POST['shortcode'] ) ? sanitize_text_field( wp_unslash( $_POST['shortcode'] ) ) : '';
		$title     = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : 'Jobs';

		$allowed = array(
			'page_job_board'   => '[sssj_job_board]',
			'page_tfn_board'   => '[sssj_tfn_board]',
			'page_abn_board'   => '[sssj_abn_board]',
			'page_volunteer_board' => '[sssj_volunteer_board]',
			'page_post_job'    => '[sssj_post_job]',
			'page_my_listings' => '[sssj_my_listings]',
			'page_messages'    => '[sssj_messages]',
			'page_org_directory' => '[sssj_org_directory]',
			'page_post_org'    => '[sssj_post_org]',
			'page_worker_directory' => '[sssj_worker_directory]',
			'page_post_worker' => '[sssj_post_worker]',
			'page_need_board'  => '[sssj_need_board]',
			'page_post_need'   => '[sssj_post_need]',
			'page_credentials' => '[sssj_credentials]',
			'page_onboard'     => '[sssj_onboard]',
			'page_dashboard'   => '[sssj_dashboard]',
			'page_swipe'       => '[sssj_swipe]',
			'page_tests'       => '[sssj_tests]',
			'page_why_us'      => '[sssj_why_us]',
			'page_join'        => '[sssj_join]',
			'page_workflows'   => '[sssj_workflows]',
			'page_policies'    => '[sssj_policies]',
			'page_marketing'   => '[sssj_marketing]',
			'page_create_asset' => '[sssj_create_asset]',
			'page_promote'     => '[sssj_promo]',
			'page_profile_card' => '[sssj_profile_card]',
			'page_login'       => '[sssj_login]',
			'page_register'    => '[sssj_register]',
			'page_demo_tour'   => '[sssj_demo_tour]',
		);
		if ( ! isset( $allowed[ $key ] ) || $allowed[ $key ] !== $shortcode ) {
			wp_send_json_error( array( 'msg' => 'bad request' ), 400 );
		}

		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_content' => $shortcode,
			),
			true
		);
		if ( is_wp_error( $page_id ) ) {
			wp_send_json_error( array( 'msg' => $page_id->get_error_message() ) );
		}

		$opts         = $this->settings->all();
		$opts[ $key ] = (int) $page_id;
		update_option( Shuffles_SSJ_Settings::OPTION_KEY, $opts );

		wp_send_json_success(
			array(
				'id'    => (int) $page_id,
				'title' => get_the_title( $page_id ),
				'edit'  => get_edit_post_link( $page_id, 'raw' ),
				'view'  => get_permalink( $page_id ),
			)
		);
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		include SHUFFLES_SSJ_DIR . 'admin/views/settings.php';
	}

	/**
	 * Admin verification queue, review credential evidence and approve / reject.
	 * The ✓ Verified badge is set ONLY here (never from user input).
	 */
	public function render_verification() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$filter  = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : 'pending'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$allowed = array( 'pending', 'verified', 'rejected', 'expired', 'all' );
		if ( ! in_array( $filter, $allowed, true ) ) {
			$filter = 'pending';
		}
		$rows = Shuffles_SSJ_Credentials::query( $filter, 300 );
		$back = admin_url( 'admin.php?page=shuffles-ssj-verify&status=' . $filter );
		$post = esc_url( admin_url( 'admin-post.php' ) );

		echo '<div class="wrap"><h1>' . esc_html__( 'Credential verification', 'shuffles-social-services-jobs' ) . '</h1>';
		echo '<p class="description">' . esc_html__( 'Review each worker’s uploaded evidence, then approve or reject. Approving a current (non-expired) credential gives that worker the ✓ Verified badge on their profile.', 'shuffles-social-services-jobs' ) . '</p>';

		// Status filter tabs.
		echo '<ul class="subsubsub">';
		$labels = array(
			'pending'  => __( 'Pending', 'shuffles-social-services-jobs' ),
			'verified' => __( 'Verified', 'shuffles-social-services-jobs' ),
			'rejected' => __( 'Rejected', 'shuffles-social-services-jobs' ),
			'expired'  => __( 'Expired', 'shuffles-social-services-jobs' ),
			'all'      => __( 'All', 'shuffles-social-services-jobs' ),
		);
		$i = 0;
		foreach ( $labels as $k => $label ) {
			$count = ( 'all' === $k ) ? '' : ' (' . (int) Shuffles_SSJ_Credentials::count_by_status( $k ) . ')';
			$url   = admin_url( 'admin.php?page=shuffles-ssj-verify&status=' . $k );
			$sep   = ( ++$i < count( $labels ) ) ? ' | ' : '';
			echo '<li><a href="' . esc_url( $url ) . '" ' . ( $filter === $k ? 'class="current"' : '' ) . '>' . esc_html( $label . $count ) . '</a>' . esc_html( $sep ) . '</li>';
		}
		echo '</ul><table class="wp-list-table widefat fixed striped" style="margin-top:8px"><thead><tr>';
		foreach ( array( __( 'Worker', 'shuffles-social-services-jobs' ), __( 'Credential', 'shuffles-social-services-jobs' ), __( 'Reference', 'shuffles-social-services-jobs' ), __( 'Expires', 'shuffles-social-services-jobs' ), __( 'Evidence', 'shuffles-social-services-jobs' ), __( 'Status', 'shuffles-social-services-jobs' ), __( 'Action', 'shuffles-social-services-jobs' ) ) as $h ) {
			echo '<th>' . esc_html( $h ) . '</th>';
		}
		echo '</tr></thead><tbody>';

		if ( empty( $rows ) ) {
			echo '<tr><td colspan="7">' . esc_html__( 'Nothing here.', 'shuffles-social-services-jobs' ) . '</td></tr>';
		}
		foreach ( (array) $rows as $r ) {
			$u                     = get_userdata( (int) $r->worker_id );
			list( $bcls, $blabel ) = Shuffles_SSJ_Credentials::status_badge( $r->status );
			$expired_soon          = ( $r->expires_date && $r->expires_date < current_time( 'Y-m-d' ) );
			echo '<tr>';
			echo '<td><strong>' . esc_html( $u ? $u->display_name : '#' . (int) $r->worker_id ) . '</strong><br><span class="description">' . esc_html( $u ? $u->user_email : '' ) . '</span></td>';
			echo '<td>' . esc_html( Shuffles_SSJ_Credentials::kind_label( $r->kind ) ) . '</td>';
			echo '<td>' . esc_html( $r->number ? $r->number : '-' ) . '</td>';
			echo '<td>' . esc_html( $r->expires_date ? $r->expires_date : '-' ) . ( $expired_soon ? ' <span style="color:#b91c1c">(' . esc_html__( 'past', 'shuffles-social-services-jobs' ) . ')</span>' : '' ) . '</td>';
			echo '<td>' . ( ! empty( $r->has_evidence ) ? '<a href="' . esc_url( Shuffles_SSJ_Credentials::file_url( $r->id ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Open', 'shuffles-social-services-jobs' ) . '</a>' : '<span class="description">' . esc_html__( 'none', 'shuffles-social-services-jobs' ) . '</span>' ) . '</td>';
			echo '<td><span class="sssj-badge ' . esc_attr( $bcls ) . '" style="padding:2px 8px;border-radius:10px">' . esc_html( $blabel ) . '</span></td>';
			echo '<td><form method="post" action="' . $post . '" style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">'; // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<input type="hidden" name="action" value="sssj_cred_status" />';
			echo '<input type="hidden" name="id" value="' . esc_attr( $r->id ) . '" />';
			echo '<input type="hidden" name="_back" value="' . esc_attr( $back ) . '" />';
			wp_nonce_field( 'sssj_cred_status' );
			echo '<input type="text" name="note" placeholder="' . esc_attr__( 'note (for rejections)', 'shuffles-social-services-jobs' ) . '" style="width:130px" />';
			echo '<button class="button button-primary" name="status" value="verified">' . esc_html__( 'Approve', 'shuffles-social-services-jobs' ) . '</button>';
			echo '<button class="button" name="status" value="rejected">' . esc_html__( 'Reject', 'shuffles-social-services-jobs' ) . '</button>';
			echo '</form></td>';
			echo '</tr>';
		}
		echo '</tbody></table></div>';
	}
}
