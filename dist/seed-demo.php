<?php
/**
 * Demo seeder — one user per functional requirement (hat) + a representative listing each.
 *
 * Creates demo accounts so every side of the marketplace has a working example. Idempotent:
 * re-running updates rather than duplicating (matched by username). Everything created is tagged
 * with meta `_sssj_demo = 1` so it can be removed in one pass (see the cleanup snippet at the end).
 *
 * RUN (recommended — WP-CLI, from the plugin folder on the server):
 *     wp eval-file dist/seed-demo.php
 *
 * Or drop a tiny loader next to wp-load.php that require()s this file. Best run on staging/local
 * first. Generated passwords are printed once — copy them somewhere safe.
 *
 * NOTE: this is a developer tool, not part of the plugin runtime. It is not loaded by the plugin.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run via: wp eval-file dist/seed-demo.php\n" );
	return;
}
if ( ! function_exists( 'wp_insert_user' ) || ! class_exists( 'Shuffles_SSJ_Roles' ) ) {
	echo "Shuffles SSJ plugin must be active.\n";
	return;
}

/** Create (or fetch) a demo user with the given hats. Returns user ID. */
$mk_user = function ( $login, $email, $name, $hats ) {
	$u = get_user_by( 'login', $login );
	if ( ! $u ) {
		$pass = wp_generate_password( 16, true );
		$uid  = wp_insert_user( array(
			'user_login'   => $login,
			'user_email'   => $email,
			'user_pass'    => $pass,
			'display_name' => $name,
			'first_name'   => $name,
			'role'         => 'subscriber',
		) );
		if ( is_wp_error( $uid ) ) {
			echo "  ! {$login}: " . $uid->get_error_message() . "\n";
			return 0;
		}
		update_user_meta( $uid, '_sssj_demo', 1 );
		update_user_meta( $uid, '_sssj_demo_pass', $pass ); // shown in Settings → Demo Users (test only)
		echo "  + user {$login}  (pass: {$pass})\n";
	} else {
		$uid = (int) $u->ID;
		echo "  = user {$login} exists (#{$uid})\n";
	}
	Shuffles_SSJ_Roles::set_member_roles( $uid, $hats );
	return (int) $uid;
};

/** Create a demo post once (matched by title + type + author). Returns post ID. */
$mk_post = function ( $type, $title, $author, $content, $meta ) {
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
		echo "    = {$type} \"{$title}\" exists (#{$found[0]})\n";
		return (int) $found[0];
	}
	$status = ( 'sssj_need' === $type ) ? 'pending' : 'publish'; // needs are always moderated
	$pid = wp_insert_post( array(
		'post_type'    => $type,
		'post_status'  => $status,
		'post_title'   => $title,
		'post_content' => $content,
		'post_author'  => $author,
	), true );
	if ( is_wp_error( $pid ) ) {
		echo "    ! {$type} \"{$title}\": " . $pid->get_error_message() . "\n";
		return 0;
	}
	$meta['_sssj_demo'] = 1;
	foreach ( $meta as $k => $v ) {
		update_post_meta( $pid, $k, $v );
	}
	echo "    + {$type} \"{$title}\" (#{$pid})\n";
	return (int) $pid;
};

echo "Seeding Shuffles SSJ demo data…\n";

// 1) Employer — org + TFN, ABN, anonymous and volunteer jobs.
$emp = $mk_user( 'demo_employer', 'demo_employer@example.com', 'Demo Employer Co', array( 'employer' ) );
if ( $emp ) {
	$org = $mk_post( 'sssj_org', 'Demo Employer Co', $emp, 'A demo employer organisation.', array(
		'org_user_id' => $emp, 'org_abn' => '51824753556', 'org_phone' => '0400 000 000',
		'org_website' => 'https://example.com', 'org_type' => 'employer', 'org_category' => 'support',
		'org_size' => 'medium', 'location_suburb' => 'Parramatta', 'location_state' => 'NSW',
	) );
	$mk_post( 'sssj_job', 'Support Worker (employee) — Parramatta', $emp, 'A demo TFN employee role.', array(
		'engagement_basis' => 'tfn', 'organisation_id' => $org, 'location_suburb' => 'Parramatta',
		'location_state' => 'NSW', 'rate_min' => 35, 'rate_unit' => 'hour', 'application_mode' => 'full',
		'screening_questions' => array( 'Do you hold a current WWCC?', 'Years of experience?' ),
		'offers_sponsorship' => '1',
	) );
	$mk_post( 'sssj_job', 'Cleaning contractor (ABN) — Parramatta', $emp, 'A demo ABN contractor engagement.', array(
		'engagement_basis' => 'abn', 'advertiser_abn' => '51824753556', 'organisation_id' => $org,
		'location_suburb' => 'Parramatta', 'location_state' => 'NSW', 'rate_min' => 45, 'rate_unit' => 'hour',
	) );
	$mk_post( 'sssj_job', 'Confidential role — Western Sydney', $emp, 'A demo anonymous job ad.', array(
		'engagement_basis' => 'tfn', 'organisation_id' => $org, 'location_suburb' => 'Blacktown',
		'location_state' => 'NSW', 'is_anonymous' => '1',
	) );
	$mk_post( 'sssj_job', 'Community outing volunteer — Parramatta', $emp, 'A demo volunteer opportunity.', array(
		'engagement_basis' => 'vol', 'organisation_id' => $org, 'location_suburb' => 'Parramatta',
		'location_state' => 'NSW',
	) );
}

// 2) NDIS / service provider — registered org.
$prov = $mk_user( 'demo_provider', 'demo_provider@example.com', 'Demo Care Provider', array( 'provider' ) );
if ( $prov ) {
	$mk_post( 'sssj_org', 'Demo Care Provider', $prov, 'A demo NDIS-registered provider.', array(
		'org_user_id' => $prov, 'org_abn' => '83914571673', 'org_type' => 'provider', 'org_category' => 'support',
		'org_size' => 'large', 'ndis_registered' => '1', 'location_suburb' => 'Newcastle', 'location_state' => 'NSW',
		'welcomes_volunteers' => '1', 'accepts_placements' => '1',
	) );
}

// 3) Supplier to the sector.
$sup = $mk_user( 'demo_supplier', 'demo_supplier@example.com', 'Demo Supplies', array( 'supplier' ) );
if ( $sup ) {
	$mk_post( 'sssj_org', 'Demo Supplies', $sup, 'A demo supplier (equipment / adaptive wear).', array(
		'org_user_id' => $sup, 'org_abn' => '24138714650', 'org_type' => 'supplier', 'org_category' => 'supplier',
		'org_size' => 'small', 'location_suburb' => 'Melbourne', 'location_state' => 'VIC',
	) );
}

// 4) Contractor (sole trader / ABN) — worker profile + sole-trader org listing.
$con = $mk_user( 'demo_contractor', 'demo_contractor@example.com', 'Demo Contractor', array( 'contractor' ) );
if ( $con ) {
	$mk_post( 'sssj_worker', 'Demo Contractor', $con, 'A demo ABN sole-trader support worker.', array(
		'worker_user_id' => $con, 'is_available' => '1', 'worker_abn' => '53004085616', 'years_experience' => 6,
		'rate_min' => 50, 'rate_unit' => 'hour', 'visibility' => 'public', 'location_suburb' => 'Brisbane',
		'location_state' => 'QLD', 'employment_status' => 'seeking',
	) );
}

// 5) Candidate (employee / TFN seeker) — worker profile, no ABN.
$cand = $mk_user( 'demo_candidate', 'demo_candidate@example.com', 'Demo Candidate', array( 'candidate' ) );
if ( $cand ) {
	$mk_post( 'sssj_worker', 'Demo Candidate', $cand, 'A demo employee job-seeker (TFN).', array(
		'worker_user_id' => $cand, 'is_available' => '1', 'years_experience' => 3, 'rate_min' => 34,
		'rate_unit' => 'hour', 'visibility' => 'public', 'location_suburb' => 'Adelaide', 'location_state' => 'SA',
		'employment_status' => 'seeking',
	) );
}

// 6) Participant — a support request (pseudonymous, moderated).
$par = $mk_user( 'demo_participant', 'demo_participant@example.com', 'Demo Participant', array( 'participant' ) );
if ( $par ) {
	$mk_post( 'sssj_need', 'Weekend community support', $par, 'A demo participant support request.', array(
		'participant_ref' => 'P-DEMO01', 'nominee_user_id' => $par, 'location_suburb' => 'Perth',
		'location_state' => 'WA', 'seeking_type' => 'support', 'contact_mode' => 'internal-only', 'visibility' => 'logged_in',
	) );
}

// 7) Participant representative / nominee — a request on behalf.
$rep = $mk_user( 'demo_representative', 'demo_representative@example.com', 'Demo Representative', array( 'representative' ) );
if ( $rep ) {
	$mk_post( 'sssj_need', 'After-school support (via nominee)', $rep, 'A demo request from a representative.', array(
		'participant_ref' => 'P-DEMO02', 'nominee_user_id' => $rep, 'location_suburb' => 'Hobart',
		'location_state' => 'TAS', 'seeking_type' => 'support', 'contact_mode' => 'internal-only', 'visibility' => 'logged_in',
	) );
}

echo "Done. (Participant requests are created as 'pending' — approve them in wp-admin to publish.)\n";

/*
 * CLEANUP — remove everything this seeder created:
 *
 * wp eval '
 *   foreach ( get_users( array( "meta_key" => "_sssj_demo", "fields" => "ids" ) ) as $u ) { wp_delete_user( $u ); }
 *   foreach ( array("sssj_job","sssj_worker","sssj_org","sssj_need") as $t ) {
 *     foreach ( get_posts(array("post_type"=>$t,"post_status"=>"any","posts_per_page"=>-1,"fields"=>"ids","meta_key"=>"_sssj_demo")) as $p ) { wp_delete_post($p, true); }
 *   }
 *   echo "removed demo data\n";'
 */
