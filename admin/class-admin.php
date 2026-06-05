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
			'appearance'   => array( 'T16', __( 'Appearance', 'shuffles-social-services-jobs' ), 'orange' ),
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
			? sprintf( '•••• •••• %s — %s', $last4, __( 'leave blank to keep', 'shuffles-social-services-jobs' ) )
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
				'show_option_none'  => __( '— Select a page —', 'shuffles-social-services-jobs' ),
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
			'page_post_job'    => '[sssj_post_job]',
			'page_my_listings' => '[sssj_my_listings]',
			'page_messages'    => '[sssj_messages]',
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
}
