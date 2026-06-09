<?php
/**
 * Demo / test user seeder, runnable from Settings, Demo Users with a "Run now" button.
 *
 * Creates one demo account per functional role (hat) plus a representative listing each, so every side
 * of the marketplace has a working example. Idempotent (matched by username), everything is tagged
 * `_sssj_demo = 1` so it can be removed in one pass. FOR TESTING ONLY: the initial passwords are stored
 * so they can be shown in the Demo Users tab. Remove the demo accounts before going to production.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Demo_Seeder {

	public static function register() {
		add_action( 'admin_post_sssj_seed_demo', array( __CLASS__, 'handle_seed' ) );
		add_action( 'admin_post_sssj_purge_demo', array( __CLASS__, 'handle_purge' ) );
	}

	/** Create (or fetch) a demo user with the given hats. */
	private static function mk_user( $login, $email, $name, $hats, &$r ) {
		$u = get_user_by( 'login', $login );
		if ( ! $u ) {
			$pass = wp_generate_password( 14, true );
			$uid  = wp_insert_user( array(
				'user_login'   => $login,
				'user_email'   => $email,
				'user_pass'    => $pass,
				'display_name' => $name,
				'first_name'   => $name,
				'role'         => 'subscriber',
			) );
			if ( is_wp_error( $uid ) ) {
				$r['errors'][] = $login . ': ' . $uid->get_error_message();
				return 0;
			}
			update_user_meta( $uid, '_sssj_demo', 1 );
			update_user_meta( $uid, '_sssj_demo_pass', $pass );
			$r['users_created']++;
		} else {
			$uid = (int) $u->ID;
			$r['users_existing']++;
		}
		if ( class_exists( 'Shuffles_SSJ_Roles' ) ) {
			Shuffles_SSJ_Roles::set_member_roles( $uid, $hats );
		}
		return (int) $uid;
	}

	/** Create a demo post once (matched by title + type + author). */
	private static function mk_post( $type, $title, $author, $content, $meta, &$r ) {
		$found = get_posts( array(
			'post_type'      => $type,
			'post_status'    => 'any',
			'author'         => $author,
			'title'          => $title,
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );
		if ( $found ) {
			return (int) $found[0];
		}
		$status = ( 'sssj_need' === $type ) ? 'pending' : 'publish';
		$pid    = wp_insert_post( array(
			'post_type'    => $type,
			'post_status'  => $status,
			'post_title'   => $title,
			'post_content' => $content,
			'post_author'  => $author,
		), true );
		if ( is_wp_error( $pid ) ) {
			$r['errors'][] = $type . ' "' . $title . '": ' . $pid->get_error_message();
			return 0;
		}
		$meta['_sssj_demo'] = 1;
		$meta['_sssj_demo_key'] = sanitize_title( $type . '-' . $title );
		foreach ( $meta as $k => $v ) {
			update_post_meta( $pid, $k, $v );
		}
		$r['posts_created']++;
		return (int) $pid;
	}

	/**
	 * Seed the demo users + listings. Idempotent.
	 *
	 * @return array { users_created, users_existing, posts_created, errors[] }
	 */
	public static function run() {
		$r = array( 'users_created' => 0, 'users_existing' => 0, 'posts_created' => 0, 'errors' => array() );

		// 1) Employer, org + TFN, ABN, anonymous and volunteer jobs.
		$emp = self::mk_user( 'demo_employer', 'demo_employer@example.com', 'Demo Employer Co', array( 'employer' ), $r );
		if ( $emp ) {
			$org = self::mk_post( 'sssj_org', 'Demo Employer Co', $emp, 'A demo employer organisation.', array(
				'org_user_id' => $emp, 'org_abn' => '51824753556', 'org_phone' => '0400 000 000',
				'org_website' => 'https://example.com', 'org_type' => 'employer', 'org_category' => 'support',
				'org_size' => 'medium', 'location_suburb' => 'Parramatta', 'location_state' => 'NSW',
			), $r );
			self::mk_post( 'sssj_job', 'Support Worker (employee), Parramatta', $emp, 'A demo TFN employee role.', array(
				'engagement_basis' => 'tfn', 'organisation_id' => $org, 'location_suburb' => 'Parramatta',
				'location_state' => 'NSW', 'rate_min' => 35, 'rate_unit' => 'hour', 'application_mode' => 'full',
				'screening_questions' => array( 'Do you hold a current WWCC?', 'Years of experience?' ),
				'offers_sponsorship' => '1',
			), $r );
			self::mk_post( 'sssj_job', 'Cleaning contractor (ABN), Parramatta', $emp, 'A demo ABN contractor engagement.', array(
				'engagement_basis' => 'abn', 'advertiser_abn' => '51824753556', 'organisation_id' => $org,
				'location_suburb' => 'Parramatta', 'location_state' => 'NSW', 'rate_min' => 45, 'rate_unit' => 'hour',
			), $r );
			self::mk_post( 'sssj_job', 'Confidential role, Western Sydney', $emp, 'A demo anonymous job ad.', array(
				'engagement_basis' => 'tfn', 'organisation_id' => $org, 'location_suburb' => 'Blacktown',
				'location_state' => 'NSW', 'is_anonymous' => '1',
			), $r );
			self::mk_post( 'sssj_job', 'Community outing volunteer, Parramatta', $emp, 'A demo volunteer opportunity.', array(
				'engagement_basis' => 'vol', 'organisation_id' => $org, 'location_suburb' => 'Parramatta',
				'location_state' => 'NSW',
			), $r );
		}

		// 2) NDIS / service provider.
		$prov = self::mk_user( 'demo_provider', 'demo_provider@example.com', 'Demo Care Provider', array( 'provider' ), $r );
		if ( $prov ) {
			self::mk_post( 'sssj_org', 'Demo Care Provider', $prov, 'A demo NDIS-registered provider.', array(
				'org_user_id' => $prov, 'org_abn' => '83914571673', 'org_type' => 'provider', 'org_category' => 'support',
				'org_size' => 'large', 'ndis_registered' => '1', 'location_suburb' => 'Newcastle', 'location_state' => 'NSW',
				'welcomes_volunteers' => '1', 'accepts_placements' => '1',
			), $r );
		}

		// 3) Supplier.
		$sup = self::mk_user( 'demo_supplier', 'demo_supplier@example.com', 'Demo Supplies', array( 'supplier' ), $r );
		if ( $sup ) {
			self::mk_post( 'sssj_org', 'Demo Supplies', $sup, 'A demo supplier (equipment / adaptive wear).', array(
				'org_user_id' => $sup, 'org_abn' => '24138714650', 'org_type' => 'supplier', 'org_category' => 'supplier',
				'org_size' => 'small', 'location_suburb' => 'Melbourne', 'location_state' => 'VIC',
			), $r );
		}

		// 4) Contractor (sole trader / ABN).
		$con = self::mk_user( 'demo_contractor', 'demo_contractor@example.com', 'Demo Contractor', array( 'contractor' ), $r );
		if ( $con ) {
			self::mk_post( 'sssj_worker', 'Demo Contractor', $con, 'A demo ABN sole-trader support worker.', array(
				'worker_user_id' => $con, 'is_available' => '1', 'worker_abn' => '53004085616', 'years_experience' => 6,
				'rate_min' => 50, 'rate_unit' => 'hour', 'visibility' => 'public', 'location_suburb' => 'Brisbane',
				'location_state' => 'QLD', 'employment_status' => 'seeking',
			), $r );
		}

		// 5) Candidate (employee / TFN seeker).
		$cand = self::mk_user( 'demo_candidate', 'demo_candidate@example.com', 'Demo Candidate', array( 'candidate' ), $r );
		if ( $cand ) {
			self::mk_post( 'sssj_worker', 'Demo Candidate', $cand, 'A demo employee job-seeker (TFN).', array(
				'worker_user_id' => $cand, 'is_available' => '1', 'years_experience' => 3, 'rate_min' => 34,
				'rate_unit' => 'hour', 'visibility' => 'public', 'location_suburb' => 'Adelaide', 'location_state' => 'SA',
				'employment_status' => 'seeking',
			), $r );
		}

		// 6) Participant (pseudonymous, moderated).
		$par = self::mk_user( 'demo_participant', 'demo_participant@example.com', 'Demo Participant', array( 'participant' ), $r );
		if ( $par ) {
			self::mk_post( 'sssj_need', 'Weekend community support', $par, 'A demo participant support request.', array(
				'participant_ref' => 'P-DEMO01', 'nominee_user_id' => $par, 'location_suburb' => 'Perth',
				'location_state' => 'WA', 'seeking_type' => 'support', 'contact_mode' => 'internal-only', 'visibility' => 'logged_in',
			), $r );
		}

		// 7) Participant representative / nominee.
		$rep = self::mk_user( 'demo_representative', 'demo_representative@example.com', 'Demo Representative', array( 'representative' ), $r );
		if ( $rep ) {
			self::mk_post( 'sssj_need', 'After-school support (via nominee)', $rep, 'A demo request from a representative.', array(
				'participant_ref' => 'P-DEMO02', 'nominee_user_id' => $rep, 'location_suburb' => 'Hobart',
				'location_state' => 'TAS', 'seeking_type' => 'support', 'contact_mode' => 'internal-only', 'visibility' => 'logged_in',
			), $r );
		}

		// Attach Australian stock imagery to demo orgs + workers (idempotent: skips any that already have
		// an image, and is a no-op when no stock-photo key is configured). Jobs inherit their org logo;
		// participant needs are never given an image (privacy).
		if ( class_exists( 'Shuffles_SSJ_Stock' ) && Shuffles_SSJ_Stock::enabled() ) {
			foreach ( get_posts( array( 'post_type' => 'sssj_org', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_sssj_demo' ) ) as $oid ) {
				$cat = (string) get_post_meta( (int) $oid, 'org_type', true );
				$q   = ( 'supplier' === $cat ) ? 'medical mobility equipment' : ( 'provider' === $cat ? 'aged care support worker' : 'disability support team' );
				Shuffles_SSJ_Stock::attach_to_post( (int) $oid, $q );
			}
			foreach ( get_posts( array( 'post_type' => 'sssj_worker', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_sssj_demo' ) ) as $wid ) {
				Shuffles_SSJ_Stock::attach_to_post( (int) $wid, 'support worker portrait' );
			}
		}
		return $r;
	}

	/** Remove every demo user + demo listing this seeder created. Returns counts. */
	public static function purge() {
		$r = array( 'users_deleted' => 0, 'posts_deleted' => 0 );
		require_once ABSPATH . 'wp-admin/includes/user.php';
		foreach ( get_users( array( 'meta_key' => '_sssj_demo', 'fields' => 'ids' ) ) as $uid ) {
			if ( wp_delete_user( (int) $uid ) ) {
				$r['users_deleted']++;
			}
		}
		foreach ( array( 'sssj_job', 'sssj_worker', 'sssj_org', 'sssj_need', 'attachment' ) as $type ) {
			$ids = get_posts( array( 'post_type' => $type, 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_sssj_demo' ) );
			foreach ( $ids as $pid ) {
				if ( wp_delete_post( (int) $pid, true ) ) {
					$r['posts_deleted']++;
				}
			}
		}
		return $r;
	}

	/* --------------------------------------------------------------- admin-post handlers */

	public static function handle_seed() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_seed_demo' );
		$res = self::run();
		set_transient( 'sssj_demo_seed_result', $res, 120 );
		wp_safe_redirect( add_query_arg( array( 'page' => 'shuffles-ssj', 'tab' => 'demo', 'sssj_demo' => 'seeded' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function handle_purge() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'shuffles-social-services-jobs' ) );
		}
		check_admin_referer( 'sssj_purge_demo' );
		$res = self::purge();
		set_transient( 'sssj_demo_seed_result', $res, 120 );
		wp_safe_redirect( add_query_arg( array( 'page' => 'shuffles-ssj', 'tab' => 'demo', 'sssj_demo' => 'purged' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
