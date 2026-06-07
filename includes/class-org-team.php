<?php
/**
 * Multi-user organisations (D) — org teams.
 *
 * An organisation (`sssj_org`) has one owner (the creator, stored in `org_user_id`) and any
 * number of additional team members, stored as `_sssj_org_members` = [ user_id => role ] where
 * role is 'admin' or 'member'. The owner is always an implicit admin and can never be removed
 * or demoted. Org admins (owner + 'admin' members, plus site admins) can add existing members,
 * change a member's role, and remove members.
 *
 * Privacy / safety: this never creates user accounts (a person must already have an account —
 * we look them up by email or username). It links existing users to an org; it does not change
 * any WordPress role or site-wide capability. Removing a member only unlinks them from the org.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Org_Team {

	const OWNER_META   = 'org_user_id';
	const MEMBERS_META = '_sssj_org_members';

	/** The owner (creator) user id of an org. */
	public static function owner_id( $org_id ) {
		return (int) get_post_meta( (int) $org_id, self::OWNER_META, true );
	}

	/** Raw members map: [ user_id (int) => role ('admin'|'member') ], excluding the owner. */
	public static function members_raw( $org_id ) {
		$m   = get_post_meta( (int) $org_id, self::MEMBERS_META, true );
		$out = array();
		if ( is_array( $m ) ) {
			foreach ( $m as $uid => $role ) {
				$uid = (int) $uid;
				if ( $uid > 0 ) {
					$out[ $uid ] = ( 'admin' === $role ) ? 'admin' : 'member';
				}
			}
		}
		return $out;
	}

	/**
	 * Normalised roster including the owner first. Each entry:
	 *   [ 'user_id', 'role' ('owner'|'admin'|'member'), 'name', 'email' ].
	 *
	 * @param int $org_id
	 * @return array[]
	 */
	public static function roster( $org_id ) {
		$rows  = array();
		$owner = self::owner_id( $org_id );
		if ( $owner ) {
			$rows[] = self::row( $owner, 'owner' );
		}
		foreach ( self::members_raw( $org_id ) as $uid => $role ) {
			if ( $uid === $owner ) {
				continue; // owner is shown once, as owner
			}
			$rows[] = self::row( $uid, $role );
		}
		return array_values( array_filter( $rows ) );
	}

	/** Build one roster row from a user id. */
	private static function row( $uid, $role ) {
		$u = get_user_by( 'id', (int) $uid );
		if ( ! $u ) {
			return null;
		}
		return array(
			'user_id' => (int) $uid,
			'role'    => $role,
			'name'    => $u->display_name ? $u->display_name : $u->user_login,
			'email'   => $u->user_email,
		);
	}

	/** Is the user the owner or any kind of member of the org? */
	public static function is_member( $org_id, $user_id ) {
		$user_id = (int) $user_id;
		if ( ! $user_id ) {
			return false;
		}
		if ( $user_id === self::owner_id( $org_id ) ) {
			return true;
		}
		return array_key_exists( $user_id, self::members_raw( $org_id ) );
	}

	/** Can the user manage the team (owner, an 'admin' member, or a site admin)? */
	public static function is_admin( $org_id, $user_id ) {
		$user_id = (int) $user_id;
		if ( ! $user_id ) {
			return false;
		}
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}
		if ( $user_id === self::owner_id( $org_id ) ) {
			return true;
		}
		$m = self::members_raw( $org_id );
		return isset( $m[ $user_id ] ) && 'admin' === $m[ $user_id ];
	}

	/** Org ids the user belongs to (owner or member). */
	public static function orgs_for_user( $user_id ) {
		$user_id = (int) $user_id;
		if ( ! $user_id ) {
			return array();
		}
		$owned = get_posts(
			array(
				'post_type'      => 'sssj_org',
				'post_status'    => 'any',
				'posts_per_page' => 50,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => self::OWNER_META,
				'meta_value'     => $user_id,
			)
		); // phpcs:ignore WordPress.DB.SlowDBQuery
		// Membership is stored serialized, so match the "i:<uid>;" needle within the meta value.
		$member = get_posts(
			array(
				'post_type'      => 'sssj_org',
				'post_status'    => 'any',
				'posts_per_page' => 50,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array(
					array(
						'key'     => self::MEMBERS_META,
						'value'   => 'i:' . $user_id . ';',
						'compare' => 'LIKE',
					),
				),
			)
		); // phpcs:ignore WordPress.DB.SlowDBQuery
		return array_values( array_unique( array_map( 'intval', array_merge( (array) $owned, (array) $member ) ) ) );
	}

	/** Org ids the user can manage (owner or 'admin' member). */
	public static function orgs_administered_by( $user_id ) {
		$out = array();
		foreach ( self::orgs_for_user( $user_id ) as $oid ) {
			if ( self::is_admin( $oid, $user_id ) ) {
				$out[] = $oid;
			}
		}
		return $out;
	}

	/**
	 * Add an existing user to the org team. Never creates an account.
	 *
	 * @return true|WP_Error
	 */
	public static function add_member( $org_id, $user_id, $role = 'member' ) {
		$org_id  = (int) $org_id;
		$user_id = (int) $user_id;
		if ( ! get_user_by( 'id', $user_id ) ) {
			return new WP_Error( 'no_user', __( 'That person does not have an account yet.', 'shuffles-social-services-jobs' ) );
		}
		if ( $user_id === self::owner_id( $org_id ) ) {
			return new WP_Error( 'is_owner', __( 'That person already owns this organisation.', 'shuffles-social-services-jobs' ) );
		}
		$m              = self::members_raw( $org_id );
		$m[ $user_id ]  = ( 'admin' === $role ) ? 'admin' : 'member';
		update_post_meta( $org_id, self::MEMBERS_META, $m );
		// Grant the posting capability so the member can act for the org (additive, never revoked).
		$u = get_user_by( 'id', $user_id );
		if ( $u && ! $u->has_cap( 'sssj_post_job' ) ) {
			$u->add_cap( 'sssj_post_job' );
		}
		return true;
	}

	/** Look up an existing user by email or login. Returns user id or 0. */
	public static function find_user( $needle ) {
		$needle = trim( (string) $needle );
		if ( '' === $needle ) {
			return 0;
		}
		$u = is_email( $needle ) ? get_user_by( 'email', $needle ) : get_user_by( 'login', $needle );
		if ( ! $u && ! is_email( $needle ) ) {
			$u = get_user_by( 'email', $needle ); // last try
		}
		return $u ? (int) $u->ID : 0;
	}

	/** Change a member's role ('admin'|'member'). Owner is immutable. */
	public static function set_role( $org_id, $user_id, $role ) {
		$org_id  = (int) $org_id;
		$user_id = (int) $user_id;
		if ( $user_id === self::owner_id( $org_id ) ) {
			return new WP_Error( 'is_owner', __( 'The owner’s role cannot be changed.', 'shuffles-social-services-jobs' ) );
		}
		$m = self::members_raw( $org_id );
		if ( ! isset( $m[ $user_id ] ) ) {
			return new WP_Error( 'not_member', __( 'That person is not on this team.', 'shuffles-social-services-jobs' ) );
		}
		$m[ $user_id ] = ( 'admin' === $role ) ? 'admin' : 'member';
		update_post_meta( $org_id, self::MEMBERS_META, $m );
		return true;
	}

	/** Remove a member from the team (unlink only). Owner cannot be removed. */
	public static function remove_member( $org_id, $user_id ) {
		$org_id  = (int) $org_id;
		$user_id = (int) $user_id;
		if ( $user_id === self::owner_id( $org_id ) ) {
			return new WP_Error( 'is_owner', __( 'The owner cannot be removed.', 'shuffles-social-services-jobs' ) );
		}
		$m = self::members_raw( $org_id );
		if ( isset( $m[ $user_id ] ) ) {
			unset( $m[ $user_id ] );
			update_post_meta( $org_id, self::MEMBERS_META, $m );
		}
		return true;
	}

	/** Human label for a role key. */
	public static function role_label( $role ) {
		$map = array(
			'owner'  => __( 'Owner', 'shuffles-social-services-jobs' ),
			'admin'  => __( 'Admin', 'shuffles-social-services-jobs' ),
			'member' => __( 'Member', 'shuffles-social-services-jobs' ),
		);
		return isset( $map[ $role ] ) ? $map[ $role ] : ucfirst( (string) $role );
	}
}
