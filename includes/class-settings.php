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
			'google_maps_api_key'       => '',
			'default_radius_km'         => 25,
			'cald_enabled'              => '1',
			'seo_enabled'               => '1',
			'monetisation_enabled'      => '0',
			'free_active_listings'      => 1,
			'licence_key'               => '',
			'delete_data_on_uninstall'  => '0',
			'page_job_board'            => 0,
			'page_tfn_board'            => 0,
			'page_abn_board'            => 0,
			'page_post_job'             => 0,
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

		$text_keys   = array( 'compliance_profile' );
		$secret_keys = array( 'google_maps_api_key', 'licence_key' );
		$toggle_keys = array( 'cald_enabled', 'seo_enabled', 'monetisation_enabled', 'delete_data_on_uninstall' );
		$int_keys    = array( 'default_radius_km', 'free_active_listings', 'page_job_board', 'page_tfn_board', 'page_abn_board', 'page_post_job' );

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
			'seo_enabled'              => 'seo',
			'monetisation_enabled'     => 'monetisation',
			'delete_data_on_uninstall' => 'privacy',
		);
		return isset( $owner[ $key ] ) && sanitize_key( (string) $tab ) === $owner[ $key ];
	}
}
