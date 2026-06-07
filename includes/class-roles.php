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

	/* --------------------------------------------------------------- Member "hats" (self-declared) */

	/**
	 * Selectable member hats. One account can wear several. Each hat declares:
	 *   label, desc (plain English), group (picker grouping), caps (granted),
	 *   reveals (dashboard section slugs surfaced for that hat).
	 *
	 * This is the single source for the [sssj_roles] hat picker AND the dashboard reveal.
	 *
	 * @return array
	 */
	public static function hats() {
		return array(
			'employer'       => array(
				'label'   => __( 'Employer / company', 'shuffles-social-services-jobs' ),
				'desc'    => __( 'I hire staff or post roles for a business — PAYG employment and/or ABN engagements.', 'shuffles-social-services-jobs' ),
				'group'   => 'offer',
				'caps'    => array( 'sssj_post_job', 'sssj_post_org' ),
				'reveals' => array( 'org', 'listings' ),
			),
			'provider'       => array(
				'label'   => __( 'NDIS / service provider', 'shuffles-social-services-jobs' ),
				'desc'    => __( 'I’m a disability / care provider (registered or not) with an organisation profile.', 'shuffles-social-services-jobs' ),
				'group'   => 'offer',
				'caps'    => array( 'sssj_post_job', 'sssj_post_org' ),
				'reveals' => array( 'org', 'listings' ),
			),
			'supplier'       => array(
				'label'   => __( 'Supplier to the sector', 'shuffles-social-services-jobs' ),
				'desc'    => __( 'I supply goods/services to providers or members (insurance, equipment, adaptive wear, SDA / real estate).', 'shuffles-social-services-jobs' ),
				'group'   => 'offer',
				'caps'    => array( 'sssj_post_org' ),
				'reveals' => array( 'org' ),
			),
			'contractor'     => array(
				'label'   => __( 'Available for contracting (sole trader / ABN)', 'shuffles-social-services-jobs' ),
				'desc'    => __( 'I work for myself under an ABN — sub-contracting / fee-for-service, including direct NDIS participant work. You can also list yourself in the providers directory as a sole trader.', 'shuffles-social-services-jobs' ),
				'group'   => 'seek',
				'caps'    => array( 'sssj_post_worker', 'sssj_post_org' ),
				'reveals' => array( 'profile', 'matches', 'credentials', 'org' ),
			),
			'candidate'      => array(
				'label'   => __( 'Looking for employee work (PAYG / TFN)', 'shuffles-social-services-jobs' ),
				'desc'    => __( 'I want to be hired as an employee — casual, part-time or full-time. No ABN needed.', 'shuffles-social-services-jobs' ),
				'group'   => 'seek',
				'caps'    => array( 'sssj_post_worker' ),
				'reveals' => array( 'profile', 'matches', 'credentials' ),
			),
			'participant'    => array(
				'label'   => __( 'Participant', 'shuffles-social-services-jobs' ),
				'desc'    => __( 'I’m a participant seeking support — request workers/providers, or post my own roles (free).', 'shuffles-social-services-jobs' ),
				'group'   => 'support',
				'caps'    => array( 'sssj_post_need', 'sssj_post_job' ),
				'reveals' => array( 'needs' ),
			),
			'representative' => array(
				'label'   => __( 'Participant representative / nominee', 'shuffles-social-services-jobs' ),
				'desc'    => __( 'I act for a participant — family member, nominee or support coordinator.', 'shuffles-social-services-jobs' ),
				'group'   => 'support',
				'caps'    => array( 'sssj_post_need' ),
				'reveals' => array( 'needs' ),
			),
		);
	}

	/** Group headings for the hat picker (group key => label). Order = display order. */
	public static function hat_groups() {
		return array(
			'support' => __( 'I need support', 'shuffles-social-services-jobs' ),
			'seek'    => __( 'I’m looking for work', 'shuffles-social-services-jobs' ),
			'offer'   => __( 'I offer work or services', 'shuffles-social-services-jobs' ),
		);
	}

	/** Pickable hat keys + recognised legacy keys (so older saved data still grants caps/reveals). */
	public static function all_role_keys() {
		return array_merge( array_keys( self::hats() ), array( 'worker' ) );
	}

	/** Back-compat: key => label (hats + the legacy 'worker'). */
	public static function member_role_options() {
		$out = array();
		foreach ( self::hats() as $k => $h ) {
			$out[ $k ] = $h['label'];
		}
		$out['worker'] = __( 'Support worker / contractor', 'shuffles-social-services-jobs' );
		return $out;
	}

	/** Which plugin capabilities each hat/role grants. */
	public static function role_caps() {
		$out = array();
		foreach ( self::hats() as $k => $h ) {
			$out[ $k ] = $h['caps'];
		}
		$out['worker'] = array( 'sssj_post_worker' ); // legacy
		return $out;
	}

	/** A user's declared hats (array of keys). */
	public static function member_roles( $user_id ) {
		$r = get_user_meta( (int) $user_id, '_sssj_roles', true );
		return is_array( $r ) ? array_values( array_intersect( $r, self::all_role_keys() ) ) : array();
	}

	/** Save declared hats + grant matching caps (additive — never revokes). */
	public static function set_member_roles( $user_id, $roles ) {
		$user_id = (int) $user_id;
		$valid   = array_values( array_intersect( (array) $roles, array_keys( self::hats() ) ) );
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

	/** Dashboard section slugs the member's hats reveal (empty → caller falls back to caps). */
	public static function reveals_for( $user_id ) {
		$hats = self::hats();
		$out  = array();
		foreach ( self::member_roles( $user_id ) as $r ) {
			if ( isset( $hats[ $r ]['reveals'] ) ) {
				$out = array_merge( $out, $hats[ $r ]['reveals'] );
			} elseif ( 'worker' === $r ) {
				$out = array_merge( $out, array( 'profile', 'matches', 'credentials' ) );
			}
		}
		return array_values( array_unique( $out ) );
	}

	public static function has_hat( $user_id, $key ) {
		return in_array( $key, self::member_roles( $user_id ), true );
	}

	/** Has the member declared any hats at all? (false → legacy/no-onboarding: fall back to caps.) */
	public static function has_any_hat( $user_id ) {
		return (bool) self::member_roles( $user_id );
	}

	/** Employer / business side. */
	public static function is_employer( $user_id ) {
		return (bool) array_intersect( self::member_roles( $user_id ), array( 'employer', 'provider', 'supplier' ) );
	}

	/** Available for ABN contracting. */
	public static function is_contractor( $user_id ) {
		return self::has_hat( $user_id, 'contractor' );
	}

	/** Is this member a participant (consumer side — free to post)? Includes their nominee/rep. */
	public static function is_participant( $user_id ) {
		return (bool) array_intersect( self::member_roles( $user_id ), array( 'participant', 'representative' ) ) || user_can( (int) $user_id, 'sssj_post_need' );
	}

	/** Is this member a provider / business (paid side)? */
	public static function is_provider( $user_id ) {
		if ( array_intersect( self::member_roles( $user_id ), array( 'employer', 'provider', 'supplier' ) ) ) {
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
