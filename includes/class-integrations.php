<?php
/**
 * Runtime integration registry.
 *
 * Standalone-first: features ask this registry whether an optional integration is present,
 * never the third-party plugins directly. Every "no" has a documented standalone fallback.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Integrations {

	/** @var Shuffles_SSJ_Settings|null */
	private $settings;

	public function __construct( $settings = null ) {
		$this->settings = $settings;
	}

	/**
	 * Detection map: key => bool.
	 *
	 * @return array
	 */
	public function map() {
		$has_maps_key = $this->settings && '' !== (string) $this->settings->get( 'google_maps_api_key', '' );

		return array(
			'buddyboss'       => function_exists( 'bp_is_active' ) || class_exists( 'BuddyPress' ),
			'geomywp'         => class_exists( 'GMW_Forms_Helper' ) || function_exists( 'gmw_get_search_form' ) || defined( 'GMW_VERSION' ),
			'google_maps'     => (bool) $has_maps_key,
			'pmpro'           => function_exists( 'pmpro_hasMembershipLevel' ),
			'fluentcart'      => defined( 'FLUENT_CART_VERSION' ) || class_exists( 'FluentCart\\App\\Application' ),
			'fluentcrm'       => defined( 'FLUENTCRM' ) || function_exists( 'FluentCrmApi' ),
			'fluentboards'    => defined( 'FLUENT_BOARDS_VERSION' ) || class_exists( 'FluentBoards\\App\\Application' ),
			'fluentforms'     => defined( 'FLUENTFORM' ) || function_exists( 'wpFluentForm' ),
			'acf'             => class_exists( 'ACF' ) || function_exists( 'get_field' ),
			'shuffles_growth' => defined( 'SHUFFLES_GROWTH_VERSION' ),
		);
	}

	/**
	 * Is an integration available?
	 *
	 * @param string $key Integration key.
	 * @return bool
	 */
	public function has( $key ) {
		$map = $this->map();
		return ! empty( $map[ $key ] );
	}

	/**
	 * Human labels + standalone fallback descriptions for the admin status panel.
	 *
	 * @return array key => [ label, fallback ]
	 */
	public function descriptors() {
		return array(
			'buddyboss'       => array( 'BuddyBoss', __( 'Worker profiles link to plain WP users.', 'shuffles-social-services-jobs' ) ),
			'geomywp'         => array( 'Geo my WP (not required)', __( 'Geo is self-hosted — own location meta + Haversine radius + own geocoder. Geo my WP is never called.', 'shuffles-social-services-jobs' ) ),
			'google_maps'     => array( 'Google Maps / Places (optional)', __( 'Keyless fallback: own OpenStreetMap geocoder + radius + list view. A key only adds autocomplete + the Google map.', 'shuffles-social-services-jobs' ) ),
			'pmpro'           => array( 'Paid Memberships Pro', __( 'Own capability + per-role limits.', 'shuffles-social-services-jobs' ) ),
			'fluentcart'      => array( 'FluentCart', __( 'Admin-set entitlements; billing notice.', 'shuffles-social-services-jobs' ) ),
			'fluentcrm'       => array( 'FluentCRM', __( 'wp_mail reminders/notifications.', 'shuffles-social-services-jobs' ) ),
			'fluentboards'    => array( 'Fluent Boards', __( 'In-plugin application pipeline.', 'shuffles-social-services-jobs' ) ),
			'fluentforms'     => array( 'Fluent Forms', __( 'Own native posting forms.', 'shuffles-social-services-jobs' ) ),
			'acf'             => array( 'ACF', __( 'Own metaboxes.', 'shuffles-social-services-jobs' ) ),
			'shuffles_growth' => array( 'Shuffles Growth', __( 'AI referral bridge inactive; board still works.', 'shuffles-social-services-jobs' ) ),
		);
	}

	/**
	 * Rows for the admin Integrations table.
	 *
	 * @return array of [ key, label, present(bool), fallback ]
	 */
	public function status_rows() {
		$map  = $this->map();
		$rows = array();
		foreach ( $this->descriptors() as $key => $d ) {
			$rows[] = array(
				'key'      => $key,
				'label'    => $d[0],
				'present'  => ! empty( $map[ $key ] ),
				'fallback' => $d[1],
			);
		}
		return $rows;
	}
}
