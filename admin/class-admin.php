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
		$links[] = '<a href="' . esc_url( self::SITE_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Shuffles website', 'shuffles-social-services-jobs' ) . '</a>';
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
			'boards'       => array( 'T2', __( 'Boards & Segregation', 'shuffles-social-services-jobs' ), 'indigo' ),
			'taxonomies'   => array( 'T3', __( 'Taxonomies & Seeds', 'shuffles-social-services-jobs' ), 'indigo' ),
			'compliance'   => array( 'T4', __( 'Compliance & Credentials', 'shuffles-social-services-jobs' ), 'amber' ),
			'funding'      => array( 'T5', __( 'Funding Sources', 'shuffles-social-services-jobs' ), 'amber' ),
			'cald'         => array( 'T6', __( 'CALD & Access', 'shuffles-social-services-jobs' ), 'teal' ),
			'seo'          => array( 'T7', __( 'SEO', 'shuffles-social-services-jobs' ), 'slate' ),
			'maps'         => array( 'T8', __( 'Maps & Location', 'shuffles-social-services-jobs' ), 'slate' ),
			'matching'     => array( 'T9', __( 'Matching', 'shuffles-social-services-jobs' ), 'blue' ),
			'monetisation' => array( 'T10', __( 'Monetisation', 'shuffles-social-services-jobs' ), 'orange' ),
			'licensing'    => array( 'T11', __( 'Licensing', 'shuffles-social-services-jobs' ), 'orange' ),
			'integrations' => array( 'T12', __( 'Integrations', 'shuffles-social-services-jobs' ), 'blue' ),
			'privacy'      => array( 'T13', __( 'Privacy & Moderation', 'shuffles-social-services-jobs' ), 'amber' ),
			'diagnostics'  => array( 'T14', __( 'Diagnostics', 'shuffles-social-services-jobs' ), 'slate' ),
			'changelog'    => array( 'T15', __( 'Changelog', 'shuffles-social-services-jobs' ), 'slate' ),
			'pm'           => array( 'PM', __( 'Project Management', 'shuffles-social-services-jobs' ), 'slate' ),
		);
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

	public function text_field( $key, $label, $help = '', $type = 'text' ) {
		$val = (string) $this->settings->get( $key, '' );
		echo '<tr><th scope="row"><label for="sssj-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
		echo '<input type="' . esc_attr( $type ) . '" class="regular-text" id="sssj-' . esc_attr( $key ) . '" name="' . esc_attr( $this->field_name( $key ) ) . '" value="' . esc_attr( $val ) . '" />';
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

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		include SHUFFLES_SSJ_DIR . 'admin/views/settings.php';
	}
}
