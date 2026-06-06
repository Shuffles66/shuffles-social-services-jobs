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

	/* --------------------------------------------------------------- Member roles (self-declared) */

	/** Selectable member roles → label. A member may hold several. */
	public static function member_role_options() {
		return array(
			'worker'         => __( 'Support worker / contractor', 'shuffles-social-services-jobs' ),
			'candidate'      => __( 'Job seeker (employee roles)', 'shuffles-social-services-jobs' ),
			'participant'    => __( 'Participant (or nominee)', 'shuffles-social-services-jobs' ),
			'provider'       => __( 'Provider / sole trader', 'shuffles-social-services-jobs' ),
			'representative' => __( 'Representative of an organisation', 'shuffles-social-services-jobs' ),
			'supplier'       => __( 'Supplier / services to the sector', 'shuffles-social-services-jobs' ),
		);
	}

	/** Which plugin capabilities each member role grants. */
	public static function role_caps() {
		return array(
			'worker'         => array( 'sssj_post_worker' ),
			'candidate'      => array( 'sssj_post_worker' ),
			'participant'    => array( 'sssj_post_need', 'sssj_post_job' ), // direct-employment posting is free (see Monetisation)
			'provider'       => array( 'sssj_post_job', 'sssj_post_org' ),
			'representative' => array( 'sssj_post_job', 'sssj_post_org' ),
			'supplier'       => array( 'sssj_post_org' ),
		);
	}

	/** A user's declared member roles (array of keys). */
	public static function member_roles( $user_id ) {
		$r = get_user_meta( (int) $user_id, '_sssj_roles', true );
		return is_array( $r ) ? array_values( array_intersect( $r, array_keys( self::member_role_options() ) ) ) : array();
	}

	/** Save a user's declared roles and grant the matching capabilities (additive — never revokes). */
	public static function set_member_roles( $user_id, $roles ) {
		$user_id = (int) $user_id;
		$valid   = array_values( array_intersect( (array) $roles, array_keys( self::member_role_options() ) ) );
		update_user_meta( $user_id, '_sssj_roles', $valid );
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return;
		}
		$caps = self::role_caps();
		foreach ( $valid as $role ) {
			foreach ( ( $caps[ $role ] ?? array() ) as $cap ) {
				if ( ! $user->has_cap( $cap ) ) {
					$user->add_cap( $cap );
				}
			}
		}
	}

	/** Is this member a participant (consumer side — free to post)? */
	public static function is_participant( $user_id ) {
		return in_array( 'participant', self::member_roles( $user_id ), true ) || user_can( (int) $user_id, 'sssj_post_need' );
	}

	/** Is this member a provider / business (paid side)? */
	public static function is_provider( $user_id ) {
		$roles = self::member_roles( $user_id );
		if ( array_intersect( $roles, array( 'provider', 'representative', 'supplier' ) ) ) {
			return true;
		}
		return (bool) get_posts( array( 'post_type' => 'sssj_org', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => true, 'meta_key' => 'org_user_id', 'meta_value' => (int) $user_id ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
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
