<?php
/**
 * Custom capabilities for posting actions (front-end forms arrive in later phases).
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Roles {

	/**
	 * The plugin's custom capabilities.
	 *
	 * @return string[]
	 */
	public static function caps() {
		return array( 'sssj_post_job', 'sssj_post_worker', 'sssj_post_need', 'sssj_post_org', 'sssj_manage' );
	}

	/**
	 * Grant capabilities on activation.
	 */
	public static function add_caps() {
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( self::caps() as $cap ) {
				$admin->add_cap( $cap );
			}
		}

		// Logged-in members may create a worker profile by default.
		$subscriber = get_role( 'subscriber' );
		if ( $subscriber ) {
			$subscriber->add_cap( 'sssj_post_worker' );
		}
	}

	/**
	 * Remove capabilities (used if data deletion is opted into; not on plain deactivate).
	 */
	public static function remove_caps() {
		foreach ( array( 'administrator', 'subscriber' ) as $role_name ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}
			foreach ( self::caps() as $cap ) {
				$role->remove_cap( $cap );
			}
		}
	}
}
