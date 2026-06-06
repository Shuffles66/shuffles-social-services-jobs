<?php
/**
 * Registers the plugin taxonomies.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Taxonomy_Registrar {

	public function register() {
		add_action( 'init', array( $this, 'register_taxonomies' ), 4 );
	}

	/**
	 * Taxonomy definitions: tax key => [ singular, plural, object types, rewrite slug ].
	 *
	 * @return array
	 */
	public function definitions() {
		return array(
			'sssjt_category'           => array( __( 'Service Category', 'shuffles-social-services-jobs' ), __( 'Service Categories', 'shuffles-social-services-jobs' ), array( 'sssj_job', 'sssj_worker', 'sssj_need', 'sssj_org' ), 'job-category' ),
			'sssjt_role'               => array( __( 'Role', 'shuffles-social-services-jobs' ), __( 'Roles', 'shuffles-social-services-jobs' ), array( 'sssj_job', 'sssj_worker' ), 'job-role' ),
			'sssjt_qualification'      => array( __( 'Qualification', 'shuffles-social-services-jobs' ), __( 'Qualifications', 'shuffles-social-services-jobs' ), array( 'sssj_job', 'sssj_worker' ), 'qualification' ),
			'sssjt_compliance_profile' => array( __( 'Compliance Profile', 'shuffles-social-services-jobs' ), __( 'Compliance Profiles', 'shuffles-social-services-jobs' ), array( 'sssj_job', 'sssj_worker' ), 'compliance-profile' ),
			'sssjt_mode'               => array( __( 'Work Mode', 'shuffles-social-services-jobs' ), __( 'Work Modes', 'shuffles-social-services-jobs' ), array( 'sssj_job', 'sssj_worker' ), 'work-mode' ),
			'sssjt_employment_type'    => array( __( 'Employment Type', 'shuffles-social-services-jobs' ), __( 'Employment Types', 'shuffles-social-services-jobs' ), array( 'sssj_job' ), 'employment-type' ),
			'sssjt_support_category'   => array( __( 'Support Category', 'shuffles-social-services-jobs' ), __( 'Support Categories', 'shuffles-social-services-jobs' ), array( 'sssj_need' ), 'support-category' ),
			'sssjt_funding_source'     => array( __( 'Funding Source', 'shuffles-social-services-jobs' ), __( 'Funding Sources', 'shuffles-social-services-jobs' ), array( 'sssj_need', 'sssj_org', 'sssj_job' ), 'funding-source' ),
			'sssjt_culture'            => array( __( 'Cultural / Community Focus', 'shuffles-social-services-jobs' ), __( 'Cultural / Community Focus', 'shuffles-social-services-jobs' ), array( 'sssj_job', 'sssj_worker', 'sssj_need' ), 'culture' ),
			'sssjt_language'           => array( __( 'Language', 'shuffles-social-services-jobs' ), __( 'Languages', 'shuffles-social-services-jobs' ), array( 'sssj_job', 'sssj_worker', 'sssj_need' ), 'language' ),
		);
	}

	public function register_taxonomies() {
		foreach ( $this->definitions() as $tax => $def ) {
			list( $singular, $plural, $objects, $slug ) = $def;

			$labels = array(
				'name'          => $plural,
				'singular_name' => $singular,
				'menu_name'     => $plural,
				/* translators: %s: taxonomy plural name */
				'all_items'     => sprintf( __( 'All %s', 'shuffles-social-services-jobs' ), $plural ),
				/* translators: %s: taxonomy singular name */
				'edit_item'     => sprintf( __( 'Edit %s', 'shuffles-social-services-jobs' ), $singular ),
				/* translators: %s: taxonomy singular name */
				'add_new_item'  => sprintf( __( 'Add New %s', 'shuffles-social-services-jobs' ), $singular ),
				/* translators: %s: taxonomy plural name */
				'search_items'  => sprintf( __( 'Search %s', 'shuffles-social-services-jobs' ), $plural ),
			);

			register_taxonomy(
				$tax,
				$objects,
				array(
					'labels'            => $labels,
					'hierarchical'      => true,
					'public'            => true,
					'show_ui'           => true,
					'show_admin_column' => true,
					'show_in_rest'      => true,
					'query_var'         => true,
					'rewrite'           => array( 'slug' => $slug ),
				)
			);
		}
	}
}
