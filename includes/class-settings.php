<?php
/**
 * Settings storage: defaults, accessors, sanitize (merge-on-save so partial tab saves
 * never wipe other tabs).
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Settings {

	const OPTION_KEY     = 'shuffles_ssj_settings';
	const SETTINGS_GROUP = 'shuffles_ssj_settings_group';

	/**
	 * Default option values.
	 *
	 * @return array
	 */
	public function defaults() {
		return array(
			'compliance_profile'        => 'NDIS Worker',
			'credential_reminder_days'  => 30,
			'ndis_scan_enabled'         => '1',
			'ndis_alert_email'          => '',
			'google_maps_api_key'       => '',
			'abr_guid'                  => '',
			'geocoder_provider'         => 'osm',
			'default_radius_km'         => 25,
			'auto_header_menu'          => '0',
			'focus_programs'            => 'NDIS, Aged Care, DVA, Foundational Supports, Thriving Kids',
			'cald_enabled'              => '1',
			'seo_enabled'               => '1',
			'crm_sync_enabled'          => '0',
			'crm_create_contact'        => '1',
			'alerts_enabled'            => '1',
			'reviews_enabled'           => '1',
			'testimonials_enabled'      => '1',
			'ads_enabled'               => '1',
			'affiliate_enabled'         => '1',
			'monetisation_enabled'      => '0',
			'free_active_listings'      => 1,
			'gating_provider'           => 'pmpro',
			'advertiser_pmpro_level'    => 0,
			'provider_pmpro_level'      => 0,
			'advertiser_fc_product'     => 0,
			'provider_fc_product'       => 0,
			'cald_languages'            => '',
			'cald_custom_langs'         => '',
			'cald_lang_overrides'       => '',
			'why_us_points'             => '',
			'asset_render_mode'         => 'browser',
			'asset_render_endpoint'     => '',
			'asset_render_self_hosted'  => '0',
			'licence_key'               => '',
			'license_item_id'           => '',
			'vendor_url'                => 'https://shuffles.com.au',
			'brand_url'                 => 'https://shuffles.com.au',
			'color_primary'             => '#3897e0',
			'color_primary_deep'        => '#1e5d9c',
			'color_ink'                 => '#0f172a',
			'color_text'                => '#1f2937',
			'color_line'                => '#e2e8f0',
			'color_bg'                  => '#ffffff',
			'color_bg_soft'             => '#f8fafc',
			'color_abn'                 => '#7c3aed',
			'color_tfn'                 => '#0e7490',
			'color_need'                => '#db2777',
			'ui_radius'                 => 10,
			'font_family'               => '',
			'font_size'                 => '',
			'heading_weight'            => '',
			'ui_density'                => 'normal',
			'appearance_themes'         => '',
			'custom_css'                => '',
			'delete_data_on_uninstall'  => '0',
			'page_job_board'            => 0,
			'page_tfn_board'            => 0,
			'page_abn_board'            => 0,
			'page_post_job'             => 0,
			'page_my_listings'          => 0,
			'page_messages'             => 0,
			'page_org_directory'        => 0,
			'page_post_org'             => 0,
			'page_worker_directory'     => 0,
			'page_post_worker'          => 0,
			'page_need_board'           => 0,
			'page_post_need'            => 0,
			'page_credentials'          => 0,
		);
	}

	/**
	 * All settings merged over defaults.
	 *
	 * @return array
	 */
	public function all() {
		$saved = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return array_merge( $this->defaults(), $saved );
	}

	/**
	 * Get a single setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	public function get( $key, $default = '' ) {
		$all = $this->all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : $default;
	}

	/**
	 * Write defaults on first install if the option is absent.
	 */
	public function ensure_defaults() {
		if ( null === get_option( self::OPTION_KEY, null ) ) {
			update_option( self::OPTION_KEY, $this->defaults() );
		}
	}

	/**
	 * Sanitize callback for register_setting. Merges into current to preserve other tabs.
	 *
	 * @param mixed $input Submitted values.
	 * @return array
	 */
	public function sanitize( $input ) {
		$out = $this->all();
		if ( ! is_array( $input ) ) {
			return $out;
		}

		$text_keys   = array( 'compliance_profile', 'license_item_id', 'font_family', 'font_size', 'heading_weight', 'focus_programs', 'ad_slot_board_top', 'ad_slot_board_bottom', 'ad_slot_single', 'affiliate_url', 'hero_heading', 'hero_blurb' );
		$secret_keys = array( 'google_maps_api_key', 'licence_key', 'abr_guid' );
		$toggle_keys = array( 'cald_enabled', 'seo_enabled', 'monetisation_enabled', 'delete_data_on_uninstall', 'auto_header_menu', 'crm_sync_enabled', 'crm_create_contact', 'alerts_enabled', 'ndis_scan_enabled', 'reviews_enabled', 'testimonials_enabled', 'ads_enabled', 'affiliate_enabled', 'asset_render_self_hosted' );
		$int_keys    = array( 'default_radius_km', 'free_active_listings', 'page_job_board', 'page_tfn_board', 'page_abn_board', 'page_volunteer_board', 'page_post_job', 'page_my_listings', 'page_messages', 'page_org_directory', 'page_post_org', 'page_worker_directory', 'page_post_worker', 'page_need_board', 'page_post_need', 'page_credentials', 'page_onboard', 'page_dashboard', 'page_swipe', 'page_tests', 'page_why_us', 'page_join', 'page_workflows', 'page_policies', 'page_marketing', 'page_create_asset', 'ui_radius', 'advertiser_pmpro_level', 'provider_pmpro_level', 'advertiser_fc_product', 'provider_fc_product', 'credential_reminder_days' );

		foreach ( $text_keys as $k ) {
			if ( isset( $input[ $k ] ) ) {
				$out[ $k ] = sanitize_text_field( wp_unslash( (string) $input[ $k ] ) );
			}
		}
		// Secret keys: a blank submission keeps the existing value (masked fields render empty).
		foreach ( $secret_keys as $k ) {
			if ( isset( $input[ $k ] ) ) {
				$v = sanitize_text_field( wp_unslash( (string) $input[ $k ] ) );
				if ( '' !== trim( $v ) ) {
					$out[ $k ] = $v;
				}
			}
		}
		foreach ( $toggle_keys as $k ) {
			// Checkboxes only submit when checked; presence in $input means a tab containing
			// this field was saved, so treat absence-within-that-tab as "0".
			if ( array_key_exists( '_tab', $input ) && self::tab_owns( $input['_tab'], $k ) ) {
				$out[ $k ] = ! empty( $input[ $k ] ) ? '1' : '0';
			} elseif ( isset( $input[ $k ] ) ) {
				$out[ $k ] = ! empty( $input[ $k ] ) ? '1' : '0';
			}
		}
		foreach ( $int_keys as $k ) {
			if ( isset( $input[ $k ] ) ) {
				$out[ $k ] = absint( $input[ $k ] );
			}
		}
		foreach ( array( 'vendor_url', 'brand_url', 'asset_render_endpoint' ) as $k ) {
			if ( isset( $input[ $k ] ) ) {
				$out[ $k ] = esc_url_raw( trim( (string) wp_unslash( $input[ $k ] ) ) );
			}
		}
		if ( isset( $input['asset_render_mode'] ) ) {
			$m = sanitize_key( wp_unslash( (string) $input['asset_render_mode'] ) );
			$out['asset_render_mode'] = in_array( $m, array( 'browser', 'server' ), true ) ? $m : 'browser';
		}
		foreach ( array( 'color_primary', 'color_primary_deep', 'color_ink', 'color_text', 'color_line', 'color_bg', 'color_bg_soft', 'color_abn', 'color_tfn', 'color_need' ) as $k ) {
			if ( isset( $input[ $k ] ) ) {
				$c = sanitize_hex_color( wp_unslash( $input[ $k ] ) );
				if ( $c ) {
					$out[ $k ] = $c;
				}
			}
		}
		if ( isset( $input['custom_css'] ) ) {
			// Allow CSS but never let it break out of the <style> tag.
			$out['custom_css'] = str_replace( array( '<', '>' ), '', (string) wp_unslash( $input['custom_css'] ) );
		}

		// Density — whitelist.
		if ( isset( $input['ui_density'] ) ) {
			$d = sanitize_key( wp_unslash( (string) $input['ui_density'] ) );
			$out['ui_density'] = in_array( $d, array( 'compact', 'normal', 'comfortable' ), true ) ? $d : 'normal';
		}
		// Saved style themes — JSON, angle brackets stripped.
		if ( isset( $input['appearance_themes'] ) ) {
			$out['appearance_themes'] = str_replace( array( '<', '>' ), '', (string) wp_unslash( $input['appearance_themes'] ) );
		}

		// Gating provider — whitelist.
		if ( isset( $input['gating_provider'] ) ) {
			$gp = sanitize_key( wp_unslash( (string) $input['gating_provider'] ) );
			$out['gating_provider'] = in_array( $gp, array( 'pmpro', 'fluentcart' ), true ) ? $gp : 'pmpro';
		}

		// Server-side geocoder — whitelist.
		if ( isset( $input['geocoder_provider'] ) ) {
			$gc = sanitize_key( wp_unslash( (string) $input['geocoder_provider'] ) );
			$out['geocoder_provider'] = in_array( $gc, array( 'osm', 'off' ), true ) ? $gc : 'osm';
		}

		// NDIS register alert recipient — email (blank = fall back to admin_email).
		if ( isset( $input['ndis_alert_email'] ) ) {
			$em = sanitize_email( wp_unslash( (string) $input['ndis_alert_email'] ) );
			$out['ndis_alert_email'] = $em ? $em : '';
		}

		// CALD: enabled language codes — normalise to a comma-separated list of sanitized keys.
		if ( isset( $input['cald_languages'] ) ) {
			$codes = array_filter( array_map( 'sanitize_key', preg_split( '/[\s,]+/', (string) wp_unslash( $input['cald_languages'] ) ) ) );
			$out['cald_languages'] = implode( ',', array_unique( $codes ) );
		}
		// CALD: custom language definitions + JSON overrides — keep multiline text, strip angle brackets.
		foreach ( array( 'cald_custom_langs', 'cald_lang_overrides', 'why_us_points' ) as $k ) {
			if ( isset( $input[ $k ] ) ) {
				$out[ $k ] = str_replace( array( '<', '>' ), '', (string) wp_unslash( $input[ $k ] ) );
			}
		}

		return $out;
	}

	/**
	 * Which settings tab owns a given checkbox key (so unchecked boxes save as 0).
	 *
	 * @param string $tab Submitted tab slug.
	 * @param string $key Setting key.
	 * @return bool
	 */
	private static function tab_owns( $tab, $key ) {
		$owner = array(
			'cald_enabled'             => 'cald',
			'testimonials_enabled'     => 'testimonials',
			'asset_render_self_hosted' => 'rendering',
			'seo_enabled'              => 'seo',
			'monetisation_enabled'     => 'monetisation',
			'delete_data_on_uninstall' => 'privacy',
			'auto_header_menu'         => 'general',
			'crm_sync_enabled'         => 'crm',
			'crm_create_contact'       => 'crm',
			'alerts_enabled'           => 'alerts',
			'ndis_scan_enabled'        => 'compliance',
			'reviews_enabled'          => 'reviews',
			'ads_enabled'              => 'ads',
			'affiliate_enabled'        => 'monetisation',
		);
		return isset( $owner[ $key ] ) && sanitize_key( (string) $tab ) === $owner[ $key ];
	}
}
