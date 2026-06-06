<?php
/**
 * Registers the three core CPTs and their structured meta.
 *
 * Privacy note: sssj_need is NOT public (no archive, excluded from search, REST off for
 * sensitive meta) — participant anonymity is baked in from registration, per the design.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_CPT_Registrar {

	public function register() {
		add_action( 'init', array( $this, 'register_post_types' ), 5 );
		add_action( 'init', array( $this, 'register_meta' ), 6 );
	}

	public function register_post_types() {

		register_post_type(
			'sssj_job',
			array(
				'labels'       => $this->labels( __( 'Job', 'shuffles-social-services-jobs' ), __( 'Jobs', 'shuffles-social-services-jobs' ) ),
				'public'       => true,
				'has_archive'  => true,
				'show_in_rest' => true,
				'show_in_menu' => 'shuffles-ssj',
				'menu_icon'    => 'dashicons-portfolio',
				'supports'     => array( 'title', 'editor', 'author', 'thumbnail', 'custom-fields' ),
				'rewrite'      => array( 'slug' => 'jobs', 'with_front' => false ),
			)
		);

		register_post_type(
			'sssj_worker',
			array(
				'labels'       => $this->labels( __( 'Worker Profile', 'shuffles-social-services-jobs' ), __( 'Worker Profiles', 'shuffles-social-services-jobs' ) ),
				'public'       => true,
				'has_archive'  => true,
				'show_in_rest' => true,
				'show_in_menu' => 'shuffles-ssj',
				'menu_icon'    => 'dashicons-id',
				'supports'     => array( 'title', 'editor', 'author', 'thumbnail', 'custom-fields' ),
				'rewrite'      => array( 'slug' => 'workers', 'with_front' => false ),
			)
		);

		register_post_type(
			'sssj_need',
			array(
				'labels'              => $this->labels( __( 'Participant Need', 'shuffles-social-services-jobs' ), __( 'Participant Needs', 'shuffles-social-services-jobs' ) ),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'shuffles-ssj',
				'show_in_rest'        => true,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'menu_icon'           => 'dashicons-heart',
				'supports'            => array( 'title', 'editor', 'custom-fields' ),
				'rewrite'             => false,
			)
		);

		// Organisation / Employer profile — public + SEO-able (named businesses, not participants).
		register_post_type(
			'sssj_org',
			array(
				'labels'       => $this->labels( __( 'Organisation', 'shuffles-social-services-jobs' ), __( 'Organisations', 'shuffles-social-services-jobs' ) ),
				'public'       => true,
				'has_archive'  => true,
				'show_in_rest' => true,
				'show_in_menu' => 'shuffles-ssj',
				'menu_icon'    => 'dashicons-building',
				'supports'     => array( 'title', 'editor', 'author', 'thumbnail', 'custom-fields' ),
				'rewrite'      => array( 'slug' => 'organisations', 'with_front' => false ),
			)
		);
	}

	/**
	 * Standard CPT label array.
	 */
	private function labels( $singular, $plural ) {
		return array(
			'name'               => $plural,
			'singular_name'      => $singular,
			'menu_name'          => $plural,
			/* translators: %s: post type singular name */
			'add_new_item'       => sprintf( __( 'Add New %s', 'shuffles-social-services-jobs' ), $singular ),
			/* translators: %s: post type singular name */
			'edit_item'          => sprintf( __( 'Edit %s', 'shuffles-social-services-jobs' ), $singular ),
			/* translators: %s: post type singular name */
			'new_item'           => sprintf( __( 'New %s', 'shuffles-social-services-jobs' ), $singular ),
			/* translators: %s: post type plural name */
			'all_items'          => sprintf( __( 'All %s', 'shuffles-social-services-jobs' ), $plural ),
			/* translators: %s: post type plural name */
			'search_items'       => sprintf( __( 'Search %s', 'shuffles-social-services-jobs' ), $plural ),
			/* translators: %s: post type plural name */
			'not_found'          => sprintf( __( 'No %s found', 'shuffles-social-services-jobs' ), $plural ),
		);
	}

	/**
	 * Meta definitions per post type.
	 * Each entry: key => [ rest_type, show_in_rest, sanitize_kind ]
	 * sanitize_kind: text | int | float | bool | abn | key
	 */
	private function meta_defs() {
		return array(
			'sssj_job' => array(
				'engagement_basis'  => array( 'string', true, 'key' ),   // abn | tfn
				'engagement_type'   => array( 'string', true, 'key' ),   // one-off | ongoing
				'organisation_type' => array( 'string', true, 'key' ),   // employer | agency | sole-trader
				'advertiser_abn'    => array( 'string', true, 'abn' ),
				'location_suburb'   => array( 'string', true, 'text' ),
				'location_state'    => array( 'string', true, 'text' ),
				'location_postcode' => array( 'string', true, 'text' ),
				'location_lat'      => array( 'number', true, 'float' ),
				'location_lng'      => array( 'number', true, 'float' ),
				'work_mode'         => array( 'string', true, 'text' ),
				'rate_min'          => array( 'number', true, 'float' ),
				'rate_max'          => array( 'number', true, 'float' ),
				'rate_unit'         => array( 'string', true, 'key' ),
				'start_date'        => array( 'string', true, 'text' ),
				'expires_at'        => array( 'string', true, 'text' ),
				'is_promoted'       => array( 'boolean', true, 'bool' ),
				'view_count'        => array( 'integer', true, 'int' ),
				'apply_count'       => array( 'integer', true, 'int' ),
				'organisation_id'   => array( 'integer', true, 'int' ),
			),
			'sssj_worker' => array(
				'worker_user_id'          => array( 'integer', true, 'int' ),
				'is_available'            => array( 'boolean', true, 'bool' ),
				'employment_status'       => array( 'string', true, 'key' ), // seeking | employed-open-to-more | not-looking
				'worker_abn'              => array( 'string', false, 'abn' ),
				'years_experience'        => array( 'integer', true, 'int' ),
				'rate_min'                => array( 'number', true, 'float' ),
				'rate_max'                => array( 'number', true, 'float' ),
				'rate_unit'               => array( 'string', true, 'key' ),
				'gender_identity'         => array( 'string', false, 'text' ),
				'gender_match_preference' => array( 'string', true, 'key' ),
				'verified_at'             => array( 'string', true, 'text' ),
				'verified_by_admin_id'    => array( 'integer', false, 'int' ),
				'visibility'              => array( 'string', true, 'key' ), // public | logged_in | verified_only
				'location_suburb'         => array( 'string', true, 'text' ),
				'location_state'          => array( 'string', true, 'text' ),
				'location_postcode'       => array( 'string', true, 'text' ),
				'location_lat'            => array( 'number', true, 'float' ),
				'location_lng'            => array( 'number', true, 'float' ),
			),
			// Participant Need: sensitive — REST off for everything except non-identifying flags.
			'sssj_need' => array(
				'participant_ref'    => array( 'string', false, 'text' ),
				'nominee_user_id'    => array( 'integer', false, 'int' ),
				'location_suburb'    => array( 'string', false, 'text' ),
				'location_state'     => array( 'string', false, 'text' ),
				'location_lat'       => array( 'number', false, 'float' ),
				'location_lng'       => array( 'number', false, 'float' ),
				'schedule_pattern'   => array( 'string', false, 'key' ),
				'ongoing_or_temp'    => array( 'string', false, 'key' ),
				'funding_management' => array( 'string', false, 'key' ),
				'gender_preference'  => array( 'string', false, 'key' ),
				'contact_mode'       => array( 'string', false, 'key' ),
				'visibility'         => array( 'string', false, 'key' ),
			),
			'sssj_org' => array(
				'org_user_id'     => array( 'integer', true, 'int' ),
				'org_abn'         => array( 'string', true, 'abn' ),
				'org_website'     => array( 'string', true, 'text' ),
				'org_type'        => array( 'string', true, 'key' ),
				'org_phone'       => array( 'string', true, 'text' ),
				'org_facebook'    => array( 'string', true, 'text' ),
				'org_linkedin'    => array( 'string', true, 'text' ),
				'org_instagram'   => array( 'string', true, 'text' ),
				'org_twitter'     => array( 'string', true, 'text' ),
				'org_youtube'     => array( 'string', true, 'text' ),
				'org_shuffles'    => array( 'string', true, 'text' ),
				'locations'       => array( 'string', false, 'text' ), // JSON array of extra locations
				'location_suburb' => array( 'string', true, 'text' ),
				'location_state'  => array( 'string', true, 'text' ),
				'location_postcode' => array( 'string', true, 'text' ),
				'location_lat'    => array( 'number', true, 'float' ),
				'location_lng'    => array( 'number', true, 'float' ),
			),
		);
	}

	public function register_meta() {
		foreach ( $this->meta_defs() as $post_type => $metas ) {
			foreach ( $metas as $key => $def ) {
				list( $rest_type, $show_in_rest, $kind ) = $def;
				register_post_meta(
					$post_type,
					$key,
					array(
						'type'              => $rest_type,
						'single'            => true,
						'show_in_rest'      => $show_in_rest,
						'sanitize_callback' => self::sanitizer( $kind ),
						'auth_callback'     => function () {
							return current_user_can( 'edit_posts' );
						},
					)
				);
			}
		}
	}

	/**
	 * Return a sanitize callback for the given kind.
	 *
	 * @param string $kind text|int|float|bool|abn|key
	 * @return callable
	 */
	private static function sanitizer( $kind ) {
		switch ( $kind ) {
			case 'int':
				return 'absint';
			case 'float':
				return function ( $v ) {
					return (float) $v;
				};
			case 'bool':
				return function ( $v ) {
					return ( $v && 'false' !== $v && '0' !== (string) $v ) ? 1 : 0;
				};
			case 'abn':
				return function ( $v ) {
					return preg_replace( '/\D+/', '', (string) $v );
				};
			case 'key':
				return function ( $v ) {
					return sanitize_key( (string) $v );
				};
			case 'text':
			default:
				return 'sanitize_text_field';
		}
	}
}
