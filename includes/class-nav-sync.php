<?php
/**
 * Header-menu sync, mirrors the plugin's [sssj_menu] into a real WordPress nav menu under
 * Appearance → Menus, and keeps it maintained over the life of the project.
 *
 * A WordPress menu is static (it can't be login/capability-aware like the [sssj_menu] shortcode),
 * so this builds the sensible PUBLIC header set (Jobs, Find a worker, Organisations, Participant
 * requests, Post a job, My dashboard, Log in, Register). The dynamic, per-user version stays
 * available via [sssj_menu]. Admin-only items (e.g. Settings) are deliberately NOT placed in the
 * static menu, they'd show to everyone, they remain in the shortcode where they're gated.
 *
 * Maintenance model:
 *   - First time: an admin clicks "Create / sync header menu" (nothing is created until then).
 *   - After that: once our menu exists, it auto-re-syncs on each plugin version change, so new
 *     pages/items appear and removed ones disappear, without touching menu items the admin added
 *     manually (only items we created, tagged with _sssj_nav_key, are managed).
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Nav_Sync {

	const META_KEY = '_sssj_nav_key';      // marks a nav item as plugin-managed
	const VER_OPT  = 'sssj_nav_version';   // last version we synced at

	public static function register() {
		add_action( 'admin_post_sssj_sync_nav', array( __CLASS__, 'handle' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_autosync' ), 20 );
	}

	/** The menu's name in Appearance → Menus. */
	public static function menu_name() {
		return apply_filters( 'shuffles_ssj_nav_menu_name', __( 'Jobs & Engagements', 'shuffles-social-services-jobs' ) );
	}

	/**
	 * Canonical item definitions (order matters). Each: key + label + either a page (settings key +
	 * shortcode to resolve) or a special URL kind. Filterable so the set can be tuned in one place.
	 */
	public static function definitions() {
		return apply_filters(
			'shuffles_ssj_nav_menu_defs',
			array(
				array( 'key' => 'home',     'label' => __( 'Home', 'shuffles-social-services-jobs' ), 'kind' => 'home' ),

				array( 'key' => 'find',     'label' => __( 'Find', 'shuffles-social-services-jobs' ), 'kind' => 'group' ),
				array( 'key' => 'jobs',     'label' => __( 'Jobs', 'shuffles-social-services-jobs' ),                 'page' => 'page_job_board',         'sc' => '[sssj_job_board]',        'parent' => 'find' ),
				array( 'key' => 'workers',  'label' => __( 'Find a worker', 'shuffles-social-services-jobs' ),        'page' => 'page_worker_directory',  'sc' => '[sssj_worker_directory]', 'parent' => 'find' ),
				array( 'key' => 'orgs',     'label' => __( 'Organisations', 'shuffles-social-services-jobs' ),        'page' => 'page_org_directory',     'sc' => '[sssj_org_directory]',    'parent' => 'find' ),
				array( 'key' => 'needs',    'label' => __( 'Participant requests', 'shuffles-social-services-jobs' ), 'page' => 'page_need_board',        'sc' => '[sssj_need_board]',       'parent' => 'find' ),

				array( 'key' => 'about',    'label' => __( 'About', 'shuffles-social-services-jobs' ), 'kind' => 'group' ),
				array( 'key' => 'howto',    'label' => __( 'How it works', 'shuffles-social-services-jobs' ),         'page' => 'page_workflows',         'sc' => '[sssj_workflows]',        'parent' => 'about' ),
				array( 'key' => 'tour',     'label' => __( 'Take a tour', 'shuffles-social-services-jobs' ),          'page' => 'page_demo_tour',         'sc' => '[sssj_demo_tour]',        'parent' => 'about' ),
				array( 'key' => 'policies', 'label' => __( 'Policies', 'shuffles-social-services-jobs' ),             'page' => 'page_policies',          'sc' => '[sssj_policies]',         'parent' => 'about' ),

				array( 'key' => 'account',  'label' => __( 'My account', 'shuffles-social-services-jobs' ), 'kind' => 'group' ),
				array( 'key' => 'post_job', 'label' => __( 'Post a job', 'shuffles-social-services-jobs' ),           'page' => 'page_post_job',          'sc' => '[sssj_post_job]',         'parent' => 'account' ),
				array( 'key' => 'dashboard','label' => __( 'My dashboard', 'shuffles-social-services-jobs' ),         'page' => 'page_my_listings',       'sc' => '[sssj_dashboard]',        'parent' => 'account' ),

				array( 'key' => 'login',    'label' => __( 'Log in', 'shuffles-social-services-jobs' ),   'kind' => 'login' ),
				array( 'key' => 'register', 'label' => __( 'Register', 'shuffles-social-services-jobs' ), 'kind' => 'register' ),
			)
		);
	}

	/** Resolve an item definition to a URL ('' = skip this item). */
	protected static function url_for( $def ) {
		if ( ! empty( $def['kind'] ) ) {
			if ( 'home' === $def['kind'] ) {
				return home_url( '/' );
			}
			if ( 'group' === $def['kind'] ) {
				return '#';
			}
			if ( 'login' === $def['kind'] ) {
				return Shuffles_SSJ_Shortcodes::login_url( home_url( '/' ) );
			}
			if ( 'register' === $def['kind'] ) {
				return Shuffles_SSJ_Shortcodes::register_url();
			}
			return '';
		}
		if ( ! empty( $def['page'] ) && class_exists( 'Shuffles_SSJ_Plugin' ) ) {
			$sc = Shuffles_SSJ_Plugin::instance()->shortcodes;
			if ( $sc && method_exists( $sc, 'resolve_page' ) ) {
				return (string) $sc->resolve_page( $def['page'], isset( $def['sc'] ) ? $def['sc'] : '' );
			}
		}
		return '';
	}

	/** Does our managed menu already exist? */
	public static function menu_exists() {
		return (bool) wp_get_nav_menu_object( self::menu_name() );
	}

	/**
	 * Create (if needed) and reconcile the menu. Only items we created (tagged META_KEY) are touched;
	 * menu items an admin added by hand are left alone.
	 *
	 * @param string $assign_location Optional theme location slug to assign the menu to.
	 * @return array report
	 */
	public static function sync( $assign_location = '' ) {
		$name = self::menu_name();
		$menu = wp_get_nav_menu_object( $name );
		if ( ! $menu ) {
			$created = wp_create_nav_menu( $name );
			if ( is_wp_error( $created ) ) {
				return array( 'error' => $created->get_error_message() );
			}
			$menu = wp_get_nav_menu_object( $created );
		}
		$menu_id  = (int) $menu->term_id;
		$existing = wp_get_nav_menu_items( $menu_id );
		$existing = is_array( $existing ) ? $existing : array();

		$managed = array();
		foreach ( $existing as $it ) {
			$k = get_post_meta( $it->ID, self::META_KEY, true );
			if ( $k ) {
				$managed[ $k ] = (int) $it->ID;
			}
		}

		$defs = self::definitions();

		// Resolve every URL up front and count resolvable children per group, so a group heading with
		// no available children is dropped rather than left as an empty dropdown.
		$resolved    = array();
		$child_count = array();
		foreach ( $defs as $def ) {
			$resolved[ $def['key'] ] = self::url_for( $def );
			if ( ! empty( $def['parent'] ) && '' !== $resolved[ $def['key'] ] ) {
				$child_count[ $def['parent'] ] = ( isset( $child_count[ $def['parent'] ] ) ? $child_count[ $def['parent'] ] : 0 ) + 1;
			}
		}

		$seen       = array();
		$ids_by_key = array();
		$pos        = 0;
		$count      = 0;
		foreach ( $defs as $def ) {
			$key      = $def['key'];
			$is_group = ( ! empty( $def['kind'] ) && 'group' === $def['kind'] );
			if ( $is_group ) {
				if ( empty( $child_count[ $key ] ) ) {
					continue;
				}
				$url = '#';
			} else {
				$url = $resolved[ $key ];
				if ( '' === $url ) {
					continue;
				}
				if ( ! empty( $def['parent'] ) && empty( $child_count[ $def['parent'] ] ) ) {
					continue;
				}
			}
			$pos++;
			$parent_id = ( ! empty( $def['parent'] ) && isset( $ids_by_key[ $def['parent'] ] ) ) ? (int) $ids_by_key[ $def['parent'] ] : 0;
			$item_id   = isset( $managed[ $key ] ) ? $managed[ $key ] : 0;
			$new_id    = wp_update_nav_menu_item(
				$menu_id,
				$item_id,
				array(
					'menu-item-title'     => $def['label'],
					'menu-item-url'       => $url,
					'menu-item-type'      => 'custom',
					'menu-item-status'    => 'publish',
					'menu-item-position'  => $pos,
					'menu-item-parent-id' => $parent_id,
				)
			);
			if ( ! is_wp_error( $new_id ) ) {
				update_post_meta( $new_id, self::META_KEY, $key );
				$seen[ $key ]       = true;
				$ids_by_key[ $key ] = (int) $new_id;
				$count++;
			}
		}

		// Remove managed items that are no longer canonical (never touches hand-added items).
		$deleted = 0;
		foreach ( $managed as $k => $id ) {
			if ( empty( $seen[ $k ] ) ) {
				wp_delete_post( $id, true );
				$deleted++;
			}
		}

		// Optional: assign to a theme location.
		if ( '' !== $assign_location ) {
			$locations = get_theme_mod( 'nav_menu_locations', array() );
			$locations = is_array( $locations ) ? $locations : array();
			$locations[ $assign_location ] = $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
		}

		update_option( self::VER_OPT, SHUFFLES_SSJ_VERSION, false );
		return array( 'menu_id' => $menu_id, 'count' => $count, 'deleted' => $deleted );
	}

	/** After the menu exists, keep it current on each plugin version change (no auto-create). */
	public static function maybe_autosync() {
		if ( ! self::menu_exists() ) {
			return;
		}
		if ( get_option( self::VER_OPT ) === SHUFFLES_SSJ_VERSION ) {
			return;
		}
		self::sync();
	}

	/** Which theme location (if any) our menu is currently assigned to. */
	public static function assigned_location() {
		$menu = wp_get_nav_menu_object( self::menu_name() );
		if ( ! $menu ) {
			return '';
		}
		$locs = get_theme_mod( 'nav_menu_locations', array() );
		if ( is_array( $locs ) ) {
			foreach ( $locs as $slug => $mid ) {
				if ( (int) $mid === (int) $menu->term_id ) {
					return (string) $slug;
				}
			}
		}
		return '';
	}

	/* ------------------------------------------------------------- Admin action */

	public static function handle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_sync_nav' );
		$loc  = isset( $_POST['assign_location'] ) ? sanitize_key( wp_unslash( $_POST['assign_location'] ) ) : '';
		$regs = array_keys( get_registered_nav_menus() );
		if ( '' !== $loc && ! in_array( $loc, $regs, true ) ) {
			$loc = '';
		}
		$res = self::sync( $loc );
		$status = isset( $res['error'] ) ? 'error' : ( 'synced:' . (int) $res['count'] );
		wp_safe_redirect( add_query_arg( array( 'page' => 'shuffles-ssj', 'tab' => 'pages', 'sssj_nav' => rawurlencode( $status ) ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
