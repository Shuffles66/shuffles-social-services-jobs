<?php
/**
 * "Test by Hat Type" demo showcase ([sssj_demo_tour]).
 *
 * A public, marketing-style tour: one fictional persona per member hat (role), with a short backstory,
 * what they offer or need, and animated "how they use Just Tasks" feature callouts (language &
 * accessibility, support, email alerts, maps, privacy, messaging, etc.). Reveal-on-scroll reuses the
 * existing display animations ([data-sssj-reveal] + .sssj-reveal). All content here is fictional and
 * safe for public display, it never uses real member or participant data.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Demo_Tour {

	public static function register() {
		add_shortcode( 'sssj_demo_tour', array( __CLASS__, 'shortcode' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_autocreate_page' ) );
	}

	/**
	 * Create the tour page once, automatically, so the admin does not have to. Idempotent: skips if a
	 * valid tour page is already mapped, and never recreates one (so deleting it stays deleted).
	 */
	public static function maybe_autocreate_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$opts   = get_option( 'shuffles_ssj_settings', array() );
		$mapped = is_array( $opts ) && ! empty( $opts['page_demo_tour'] ) ? (int) $opts['page_demo_tour'] : 0;
		if ( $mapped && 'publish' === get_post_status( $mapped ) ) {
			return; // already have a live tour page
		}
		if ( get_option( 'sssj_demo_tour_autocreated' ) ) {
			return; // created once before; respect a later deletion
		}
		$pid = wp_insert_post( array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => __( 'Take a tour', 'shuffles-social-services-jobs' ),
			'post_content' => '[sssj_demo_tour]',
		), true );
		if ( is_wp_error( $pid ) || ! $pid ) {
			return;
		}
		if ( ! is_array( $opts ) ) {
			$opts = array();
		}
		$opts['page_demo_tour'] = (int) $pid;
		update_option( 'shuffles_ssj_settings', $opts );
		update_option( 'sssj_demo_tour_autocreated', (int) $pid, false );
	}

	/** Reusable feature callouts (icon + name + plain note), referenced by key in each persona. */
	private static function feature( $key ) {
		$f = array(
			'language'  => array( '🌐', __( 'Reads in their language', 'shuffles-social-services-jobs' ), __( 'Switches the site to their language and turns on read-aloud, larger text or high contrast.', 'shuffles-social-services-jobs' ) ),
			'maps'      => array( '🗺️', __( 'Finds work nearby on the map', 'shuffles-social-services-jobs' ), __( 'Searches by suburb with a travel-radius slider, so only realistic distances show.', 'shuffles-social-services-jobs' ) ),
			'maps_support' => array( '🗺️', __( 'Finds support nearby on the map', 'shuffles-social-services-jobs' ), __( 'Looks for workers and providers close to home, by suburb and travel distance.', 'shuffles-social-services-jobs' ) ),
			'alerts'    => array( '🔔', __( 'Email alerts for new matches', 'shuffles-social-services-jobs' ), __( 'Saves a search and is emailed the moment a new, matching listing appears.', 'shuffles-social-services-jobs' ) ),
			'alerts_cand' => array( '🔔', __( 'Alerts for new candidates', 'shuffles-social-services-jobs' ), __( 'Gets emailed when new worker profiles match a role they have advertised.', 'shuffles-social-services-jobs' ) ),
			'support'   => array( '🛟', __( 'Help when they need it', 'shuffles-social-services-jobs' ), __( 'Reaches the support team from inside their dashboard, with plain-English guides on tap.', 'shuffles-social-services-jobs' ) ),
			'messages'  => array( '💬', __( 'Safe, private messaging', 'shuffles-social-services-jobs' ), __( 'Talks through the built-in relay, no email address or phone number is ever exposed.', 'shuffles-social-services-jobs' ) ),
			'privacy'   => array( '🔒', __( 'Private by design', 'shuffles-social-services-jobs' ), __( 'Listed under a pseudonym, suburb-level only, and every first contact runs through the safe relay.', 'shuffles-social-services-jobs' ) ),
			'verified'  => array( '✅', __( 'Verified checks shown', 'shuffles-social-services-jobs' ), __( 'WWCC, NDIS Worker Screening and police checks carry an admin-verified tick, never self-claimed.', 'shuffles-social-services-jobs' ) ),
			'matches'   => array( '🧩', __( 'Roles matched to them', 'shuffles-social-services-jobs' ), __( 'Sees jobs matched to their services, area and availability, with a reason for each match.', 'shuffles-social-services-jobs' ) ),
			'resume'    => array( '📄', __( 'A polished résumé in a click', 'shuffles-social-services-jobs' ), __( 'Turns their profile into a clean, shareable résumé or flyer, no design skills needed.', 'shuffles-social-services-jobs' ) ),
			'abntfn'    => array( '🪪', __( 'Right board, right basis', 'shuffles-social-services-jobs' ), __( 'Employee (TFN) and contractor (ABN) work stay on separate boards, so the rules are always clear.', 'shuffles-social-services-jobs' ) ),
			'applicants' => array( '📥', __( 'Applicants in one place', 'shuffles-social-services-jobs' ), __( 'Reviews everyone who applied, with their screening answers and résumé, from the dashboard.', 'shuffles-social-services-jobs' ) ),
			'card'      => array( '🎨', __( 'A shareable profile card', 'shuffles-social-services-jobs' ), __( 'Generates a styled square image of their profile for social media in seconds.', 'shuffles-social-services-jobs' ) ),
			'directory' => array( '🏷️', __( 'Listed in the directory', 'shuffles-social-services-jobs' ), __( 'Appears in the providers / suppliers directory so the right people can find them.', 'shuffles-social-services-jobs' ) ),
			'free'      => array( '💸', __( 'Free for participants', 'shuffles-social-services-jobs' ), __( 'Participants and their nominees post and connect at no cost.', 'shuffles-social-services-jobs' ) ),
		);
		return isset( $f[ $key ] ) ? $f[ $key ] : array( '•', $key, '' );
	}

	/** The seven personas (fictional). icon + accent + story + what they offer/need + feature keys. */
	public static function personas() {
		return array(
			'participant' => array(
				'name'    => __( 'Aria', 'shuffles-social-services-jobs' ),
				'role'    => __( 'Participant', 'shuffles-social-services-jobs' ),
				'loc'     => __( 'Perth, WA', 'shuffles-social-services-jobs' ),
				'icon'    => '🌸', 'accent' => '#db2777',
				'query'   => 'support,companionship,home',
				'tagline' => __( 'Looking for a weekend support worker who shares her love of the outdoors.', 'shuffles-social-services-jobs' ),
				'story'   => __( 'Aria is 27 and lives independently with a little help. She wants a support worker for weekend community access, someone patient who enjoys the beach and markets. She would rather not share her details publicly, so she posts what she needs under a nickname and lets the right workers come to her.', 'shuffles-social-services-jobs' ),
				'lead'    => __( 'What Aria is looking for', 'shuffles-social-services-jobs' ),
				'items'   => array( __( 'Weekend community access', 'shuffles-social-services-jobs' ), __( 'Outdoor & social activities', 'shuffles-social-services-jobs' ), __( 'A regular, friendly face', 'shuffles-social-services-jobs' ) ),
				'features' => array( 'privacy', 'maps_support', 'messages', 'free', 'language' ),
			),
			'representative' => array(
				'name'    => __( 'Tom', 'shuffles-social-services-jobs' ),
				'role'    => __( 'Participant representative / nominee', 'shuffles-social-services-jobs' ),
				'loc'     => __( 'Hobart, TAS', 'shuffles-social-services-jobs' ),
				'icon'    => '🤝', 'accent' => '#be185d',
				'query'   => 'family,care,teenager',
				'tagline' => __( 'Arranging after-school support for his daughter, on her behalf.', 'shuffles-social-services-jobs' ),
				'story'   => __( 'Tom is a nominee for his teenage daughter. Between work and appointments he needs a dependable after-school support worker. He posts the request for her, keeps her identity protected, and reviews responses in the evening when he has a moment.', 'shuffles-social-services-jobs' ),
				'lead'    => __( 'What Tom is arranging', 'shuffles-social-services-jobs' ),
				'items'   => array( __( 'After-school support', 'shuffles-social-services-jobs' ), __( 'Reliable weekday hours', 'shuffles-social-services-jobs' ), __( 'A worker with a current WWCC', 'shuffles-social-services-jobs' ) ),
				'features' => array( 'privacy', 'verified', 'alerts', 'messages', 'support' ),
			),
			'candidate' => array(
				'name'    => __( 'Priya', 'shuffles-social-services-jobs' ),
				'role'    => __( 'Looking for employee work (PAYG / TFN)', 'shuffles-social-services-jobs' ),
				'loc'     => __( 'Adelaide, SA', 'shuffles-social-services-jobs' ),
				'icon'    => '💚', 'accent' => '#0e7490',
				'query'   => 'support worker,care,smiling',
				'tagline' => __( 'A Cert III support worker wanting a stable part-time employee role.', 'shuffles-social-services-jobs' ),
				'story'   => __( 'Priya has three years of experience and a Cert III. She is not a contractor and does not want an ABN, she wants to be employed part-time, close to home. She builds a profile, uploads her checks for verification, and lets matching roles find her.', 'shuffles-social-services-jobs' ),
				'lead'    => __( 'What Priya offers', 'shuffles-social-services-jobs' ),
				'items'   => array( __( 'Personal care & daily living', 'shuffles-social-services-jobs' ), __( 'Cert III, First Aid, WWCC', 'shuffles-social-services-jobs' ), __( 'Available weekday mornings', 'shuffles-social-services-jobs' ) ),
				'features' => array( 'matches', 'alerts', 'verified', 'resume', 'maps', 'abntfn' ),
			),
			'contractor' => array(
				'name'    => __( 'Jordan', 'shuffles-social-services-jobs' ),
				'role'    => __( 'Available for contracting (sole trader / ABN)', 'shuffles-social-services-jobs' ),
				'loc'     => __( 'Brisbane, QLD', 'shuffles-social-services-jobs' ),
				'icon'    => '🧰', 'accent' => '#7c3aed',
				'query'   => 'support worker,community,outdoors',
				'tagline' => __( 'A sole-trader support worker building a book of regular clients.', 'shuffles-social-services-jobs' ),
				'story'   => __( 'Jordan works under an ABN, invoicing for fee-for-service support including direct NDIS participant work. He lists himself as a sole trader, picks up contract engagements, and uses a one-click flyer to promote his services locally.', 'shuffles-social-services-jobs' ),
				'lead'    => __( 'What Jordan offers', 'shuffles-social-services-jobs' ),
				'items'   => array( __( 'Community access & transport', 'shuffles-social-services-jobs' ), __( 'ABN, insured, NDIS screened', 'shuffles-social-services-jobs' ), __( 'Travels up to 45 km', 'shuffles-social-services-jobs' ) ),
				'features' => array( 'abntfn', 'matches', 'directory', 'card', 'maps', 'verified' ),
			),
			'employer' => array(
				'name'    => __( 'Riverview Community Care', 'shuffles-social-services-jobs' ),
				'role'    => __( 'Employer / company', 'shuffles-social-services-jobs' ),
				'loc'     => __( 'Parramatta, NSW', 'shuffles-social-services-jobs' ),
				'icon'    => '🏢', 'accent' => '#1e5d9c',
				'query'   => 'care team,office,meeting',
				'tagline' => __( 'A growing provider hiring both employees and contractors.', 'shuffles-social-services-jobs' ),
				'story'   => __( 'Riverview is short-staffed for the school holidays. Their team lead posts a PAYG support-worker role and a separate ABN cleaning engagement, plus a volunteer outing, then reviews applicants and their screening answers in one place.', 'shuffles-social-services-jobs' ),
				'lead'    => __( 'What Riverview posts', 'shuffles-social-services-jobs' ),
				'items'   => array( __( 'Employee (TFN) support roles', 'shuffles-social-services-jobs' ), __( 'Contractor (ABN) engagements', 'shuffles-social-services-jobs' ), __( 'Volunteer opportunities', 'shuffles-social-services-jobs' ) ),
				'features' => array( 'abntfn', 'applicants', 'alerts_cand', 'card', 'support' ),
			),
			'provider' => array(
				'name'    => __( 'Coastal Allied Health', 'shuffles-social-services-jobs' ),
				'role'    => __( 'NDIS / service provider', 'shuffles-social-services-jobs' ),
				'loc'     => __( 'Newcastle, NSW', 'shuffles-social-services-jobs' ),
				'icon'    => '🏥', 'accent' => '#0f766e',
				'query'   => 'allied health,therapy,clinic',
				'tagline' => __( 'A registered provider showcasing services and finding the right staff.', 'shuffles-social-services-jobs' ),
				'story'   => __( 'Coastal is NDIS registered. They keep an organisation profile that shows their registration status (checked live against the public register), welcome work placements, and advertise allied-health roles to a sector-specific audience.', 'shuffles-social-services-jobs' ),
				'lead'    => __( 'What Coastal offers', 'shuffles-social-services-jobs' ),
				'items'   => array( __( 'Allied health & therapy', 'shuffles-social-services-jobs' ), __( 'NDIS registered (verified)', 'shuffles-social-services-jobs' ), __( 'Welcomes student placements', 'shuffles-social-services-jobs' ) ),
				'features' => array( 'directory', 'verified', 'applicants', 'alerts_cand', 'card' ),
			),
			'supplier' => array(
				'name'    => __( 'AdaptWell Equipment', 'shuffles-social-services-jobs' ),
				'role'    => __( 'Supplier to the sector', 'shuffles-social-services-jobs' ),
				'loc'     => __( 'Melbourne, VIC', 'shuffles-social-services-jobs' ),
				'icon'    => '📦', 'accent' => '#b45309',
				'query'   => 'wheelchair,adaptive equipment,mobility',
				'tagline' => __( 'Supplying adaptive equipment and wear to providers and members.', 'shuffles-social-services-jobs' ),
				'story'   => __( 'AdaptWell sells adaptive clothing and mobility equipment. They list in the directory so providers, participants and families can discover them, and keep their profile current with their range and contact options.', 'shuffles-social-services-jobs' ),
				'lead'    => __( 'What AdaptWell offers', 'shuffles-social-services-jobs' ),
				'items'   => array( __( 'Adaptive wear & equipment', 'shuffles-social-services-jobs' ), __( 'Delivery Australia-wide', 'shuffles-social-services-jobs' ), __( 'Trade pricing for providers', 'shuffles-social-services-jobs' ) ),
				'features' => array( 'directory', 'card', 'language', 'support' ),
			),
		);
	}

	public static function shortcode( $atts ) {
		wp_enqueue_style( 'sssj' );
		if ( ! wp_script_is( 'sssj-display', 'registered' ) ) {
			wp_register_script( 'sssj-display', SHUFFLES_SSJ_URL . 'public/assets/js/sssj-display.js', array(), SHUFFLES_SSJ_VERSION, true );
		}
		wp_enqueue_script( 'sssj-display' );

		$raw   = is_array( $atts ) ? $atts : array();
		$kiosk = in_array( 'autoplay', $raw, true ) || ( ! empty( $raw['autoplay'] ) && 'off' !== $raw['autoplay'] ) || isset( $_GET['sssj_kiosk'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $kiosk ) {
			$secs = 8;
			if ( ! empty( $raw['autoplay'] ) && is_numeric( $raw['autoplay'] ) ) { $secs = max( 3, (int) $raw['autoplay'] ); }
			if ( ! empty( $raw['seconds'] ) && is_numeric( $raw['seconds'] ) ) { $secs = max( 3, (int) $raw['seconds'] ); }
			return self::kiosk( $secs );
		}

		$people  = self::personas();
		$onboard = class_exists( 'Shuffles_SSJ_Shortcodes' ) ? Shuffles_SSJ_Shortcodes::page_link( 'page_onboard', '[sssj_onboard]' ) : '';

		// Which hat-group each persona belongs to (for the "Which are you?" filter).
		$group_of = array(
			'participant' => 'support', 'representative' => 'support',
			'candidate' => 'seek', 'contractor' => 'seek',
			'employer' => 'offer', 'provider' => 'offer', 'supplier' => 'offer',
		);
		// Role-appropriate primary call to action per persona (page key + shortcode to resolve).
		$cta_of = array(
			'participant'    => array( __( 'Request support', 'shuffles-social-services-jobs' ), 'page_post_need', '[sssj_post_need]' ),
			'representative' => array( __( 'Request support on their behalf', 'shuffles-social-services-jobs' ), 'page_post_need', '[sssj_post_need]' ),
			'candidate'      => array( __( 'Create my profile', 'shuffles-social-services-jobs' ), 'page_post_worker', '[sssj_post_worker]' ),
			'contractor'     => array( __( 'List myself as available', 'shuffles-social-services-jobs' ), 'page_post_worker', '[sssj_post_worker]' ),
			'employer'       => array( __( 'Post a job', 'shuffles-social-services-jobs' ), 'page_post_job', '[sssj_post_job]' ),
			'provider'       => array( __( 'Create our organisation', 'shuffles-social-services-jobs' ), 'page_post_org', '[sssj_post_org]' ),
			'supplier'       => array( __( 'List our organisation', 'shuffles-social-services-jobs' ), 'page_post_org', '[sssj_post_org]' ),
		);
		// Phase 2: "Explore as this persona" deep-link to the most relevant board, pre-filtered to their area.
		$explore_of = array(
			'participant'    => array( __( 'See support workers near %s', 'shuffles-social-services-jobs' ), 'page_worker_directory', '[sssj_worker_directory]' ),
			'representative' => array( __( 'See support workers near %s', 'shuffles-social-services-jobs' ), 'page_worker_directory', '[sssj_worker_directory]' ),
			'candidate'      => array( __( 'See jobs near %s', 'shuffles-social-services-jobs' ), 'page_job_board', '[sssj_job_board]' ),
			'contractor'     => array( __( 'See contract work near %s', 'shuffles-social-services-jobs' ), 'page_abn_board', '[sssj_abn_board]' ),
			'employer'       => array( __( 'See available workers near %s', 'shuffles-social-services-jobs' ), 'page_worker_directory', '[sssj_worker_directory]' ),
			'provider'       => array( __( 'See available workers near %s', 'shuffles-social-services-jobs' ), 'page_worker_directory', '[sssj_worker_directory]' ),
			'supplier'       => array( __( 'See organisations near %s', 'shuffles-social-services-jobs' ), 'page_org_directory', '[sssj_org_directory]' ),
		);
		$filter_groups = class_exists( 'Shuffles_SSJ_Roles' ) ? Shuffles_SSJ_Roles::hat_groups() : array(
			'support' => __( 'I need support', 'shuffles-social-services-jobs' ),
			'seek'    => __( 'I am looking for work', 'shuffles-social-services-jobs' ),
			'offer'   => __( 'I offer work or services', 'shuffles-social-services-jobs' ),
		);

		ob_start();
		echo '<div class="sssj sssj--demo">';

		// Intro.
		echo '<section class="sssj-demo-intro sssj-reveal" data-sssj-reveal><h1 class="sssj-demo-intro__title">' . esc_html__( 'Take Just Tasks for a test drive', 'shuffles-social-services-jobs' ) . '</h1>';
		echo '<p class="sssj-demo-intro__sub">' . esc_html__( 'See the marketplace through the people who use it. Pick a role to explore their story and the features they rely on. Every example below is fictional.', 'shuffles-social-services-jobs' ) . '</p>';
		echo '<p class="sssj-demo-intro__kiosklink"><a href="' . esc_url( add_query_arg( 'sssj_kiosk', '1' ) ) . '">&#9654; ' . esc_html__( 'Play as a slideshow', 'shuffles-social-services-jobs' ) . '</a></p></section>';

		// Phase 3: trust + on-brand band: safety guardrails, accessibility note, and live stats.
		echo '<section class="sssj-demo-trust sssj-reveal" data-sssj-reveal>';
		echo '<div class="sssj-demo-trust__safety"><h2 class="sssj-demo-trust__title">🛡️ ' . esc_html__( 'Safety, built in', 'shuffles-social-services-jobs' ) . '</h2><ul class="sssj-demo-trust__list">';
		if ( class_exists( 'Shuffles_SSJ_Display' ) ) {
			foreach ( Shuffles_SSJ_Display::safety_guardrails() as $sssj_g ) {
				echo '<li>' . esc_html( $sssj_g ) . '</li>';
			}
		}
		echo '</ul>';
		echo '<p class="sssj-demo-trust__a11y">🌐 ' . esc_html__( 'This whole tour is multilingual and accessible: use the bar to switch language, read the page aloud, enlarge the text or turn on high contrast. Nothing extra is sent anywhere to make it work.', 'shuffles-social-services-jobs' ) . '</p></div>';
		echo '<div class="sssj-demo-trust__stats">' . do_shortcode( '[sssj_stats min="10"]' ) . '</div>';
		echo '</section>';

		echo '<div class="sssj-demo-filter" data-demo-filterbar>';
			echo '<span class="sssj-demo-filter__lbl">' . esc_html__( 'Which are you?', 'shuffles-social-services-jobs' ) . '</span>';
			echo '<button type="button" class="sssj-btn sssj-btn--sm sssj-btn--primary is-active" data-demo-filter="all">' . esc_html__( 'Everyone', 'shuffles-social-services-jobs' ) . '</button>';
			foreach ( $filter_groups as $gk => $glabel ) {
				echo '<button type="button" class="sssj-btn sssj-btn--sm sssj-btn--ghost" data-demo-filter="' . esc_attr( $gk ) . '">' . esc_html( $glabel ) . '</button>';
			}
			echo '</div>';

			// Gallery of persona cards.
		echo '<div class="sssj-demo-gallery" data-sssj-reveal>';
		foreach ( $people as $key => $p ) {
			echo '<a class="sssj-demo-card sssj-reveal" data-demo-group="' . esc_attr( isset( $group_of[ $key ] ) ? $group_of[ $key ] : 'offer' ) . '" href="#sssj-demo-' . esc_attr( $key ) . '" style="--demo-ac:' . esc_attr( $p['accent'] ) . '">';
			echo '<span class="sssj-demo-card__icon" aria-hidden="true">' . esc_html( $p['icon'] ) . '</span>';
			echo '<span class="sssj-demo-card__name">' . esc_html( $p['name'] ) . '</span>';
			echo '<span class="sssj-demo-card__role">' . esc_html( $p['role'] ) . '</span>';
			echo '<span class="sssj-demo-card__tag">' . esc_html( $p['tagline'] ) . '</span>';
			echo '<span class="sssj-demo-card__go">' . esc_html__( 'Explore', 'shuffles-social-services-jobs' ) . ' &rarr;</span>';
			echo '</a>';
		}
		echo '</div>';

		// Phase 3: sticky persona nav (scroll-spy highlights the persona in view; respects the filter).
		echo '<nav class="sssj-demo-stickynav" data-demo-stickynav aria-label="' . esc_attr__( 'Jump to a persona', 'shuffles-social-services-jobs' ) . '">';
		foreach ( $people as $key => $p ) {
			echo '<a class="sssj-demo-stickynav__item" data-demo-group="' . esc_attr( isset( $group_of[ $key ] ) ? $group_of[ $key ] : 'offer' ) . '" data-demo-target="sssj-demo-' . esc_attr( $key ) . '" href="#sssj-demo-' . esc_attr( $key ) . '"><span aria-hidden="true">' . esc_html( $p['icon'] ) . '</span> ' . esc_html( $p['name'] ) . '</a>';
		}
		echo '</nav>';

		// One story section per persona.
		foreach ( $people as $key => $p ) {
			echo '<section class="sssj-demo-story" data-demo-group="' . esc_attr( isset( $group_of[ $key ] ) ? $group_of[ $key ] : 'offer' ) . '" id="sssj-demo-' . esc_attr( $key ) . '" data-sssj-reveal style="--demo-ac:' . esc_attr( $p['accent'] ) . '">';
			$img = class_exists( 'Shuffles_SSJ_Stock' ) ? Shuffles_SSJ_Stock::demo_image( $key ) : null;
			$banner_cls = 'sssj-demo-story__banner' . ( $img ? ' sssj-demo-story__banner--photo' : '' );
			$banner_style = '';
			if ( $img ) {
				$banner_style = ' style="--demo-photo:url(\'' . esc_url( $img['url'] ) . '\')"';
			}
			echo '<div class="' . esc_attr( $banner_cls ) . '"' . $banner_style . '><span class="sssj-demo-story__icon" aria-hidden="true">' . esc_html( $p['icon'] ) . '</span>';
			echo '<div><span class="sssj-demo-story__role">' . esc_html( $p['role'] ) . '</span>';
			echo '<h2 class="sssj-demo-story__name">' . esc_html( $p['name'] ) . '</h2>';
			echo '<span class="sssj-demo-story__loc">📍 ' . esc_html( $p['loc'] ) . '</span></div>';
			if ( $img ) {
				echo '<a class="sssj-demo-story__credit" href="' . esc_url( $img['credit_url'] ) . '" target="_blank" rel="noopener">' . esc_html( sprintf( __( 'Photo: %s / Unsplash', 'shuffles-social-services-jobs' ), $img['credit_name'] ) ) . '</a>';
			}
			echo '</div>';

			echo '<div class="sssj-demo-story__body">';
			echo '<p class="sssj-demo-story__lead">' . esc_html( $p['tagline'] ) . '</p>';
			echo '<p>' . esc_html( $p['story'] ) . '</p>';

			echo '<h3 class="sssj-demo-h">' . esc_html( $p['lead'] ) . '</h3><ul class="sssj-demo-chips">';
			foreach ( $p['items'] as $it ) {
				echo '<li>' . esc_html( $it ) . '</li>';
			}
			echo '</ul>';

			echo '<h3 class="sssj-demo-h">' . esc_html__( 'How they use Just Tasks', 'shuffles-social-services-jobs' ) . '</h3>';
			echo '<div class="sssj-demo-features">';
			foreach ( $p['features'] as $fk ) {
				$f = self::feature( $fk );
				echo '<div class="sssj-demo-feature sssj-reveal"><span class="sssj-demo-feature__badge" aria-hidden="true">' . esc_html( $f[0] ) . '</span>';
				echo '<span class="sssj-demo-feature__name">' . esc_html( $f[1] ) . '</span>';
				echo '<span class="sssj-demo-feature__note">' . esc_html( $f[2] ) . '</span></div>';
			}
			echo '</div>';

			echo self::live_card( $key );

			if ( $onboard ) {
				$dt_cta = isset( $cta_of[ $key ] ) ? $cta_of[ $key ] : null; $dt_url = ( $dt_cta && class_exists( 'Shuffles_SSJ_Shortcodes' ) ) ? Shuffles_SSJ_Shortcodes::page_link( $dt_cta[1], $dt_cta[2] ) : ''; $dt_url = $dt_url ? $dt_url : $onboard; $dt_label = $dt_cta ? $dt_cta[0] : __( 'Try this yourself', 'shuffles-social-services-jobs' ); $dt_exp = isset( $explore_of[ $key ] ) ? $explore_of[ $key ] : null; $dt_exp_url = ( $dt_exp && class_exists( 'Shuffles_SSJ_Shortcodes' ) ) ? Shuffles_SSJ_Shortcodes::page_link( $dt_exp[1], $dt_exp[2] ) : ''; $dt_exp_btn = ''; if ( $dt_exp_url ) { $dt_exp_url = add_query_arg( array( 'sssj_loc' => $p['loc'], 'sssj_radius' => 50 ), $dt_exp_url ); $dt_exp_btn = '<a class="sssj-btn sssj-btn--ghost" href="' . esc_url( $dt_exp_url ) . '">' . esc_html( sprintf( $dt_exp[0], $p['loc'] ) ) . '</a> '; } echo '<p style="margin-top:16px"><a class="sssj-btn sssj-btn--primary" href="' . esc_url( $dt_url ) . '">' . esc_html( $dt_label ) . '</a> ' . $dt_exp_btn . '<a class="sssj-demo-top" href="#">' . esc_html__( 'Back to top', 'shuffles-social-services-jobs' ) . '</a></p>';
			}
			echo '</div></section>';
		}

		echo '</div>';
		return ob_get_clean();
	}

	/**
	 * Phase 2: a real, public listing relevant to this persona, shown inline ("show, don't tell").
	 * Privacy-safe: only PUBLIC worker profiles, published jobs and non-hidden organisations are ever
	 * shown here; participant needs are logged-in-only and are NEVER rendered on this public page.
	 *
	 * @param string $key Persona key.
	 * @return string HTML (empty when there is nothing safe to show).
	 */
	private static function live_card( $key ) {
		$map = array(
			'candidate'  => 'sssj_worker',
			'contractor' => 'sssj_worker',
			'employer'   => 'sssj_job',
			'provider'   => 'sssj_org',
			'supplier'   => 'sssj_org',
		);
		if ( ! isset( $map[ $key ] ) ) {
			return '';
		}
		$pt   = $map[ $key ];
		$args = array(
			'post_type'           => $pt,
			'post_status'         => 'publish',
			'posts_per_page'      => 1,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		);
		if ( 'sssj_worker' === $pt ) {
			$args['meta_query'] = array( array( 'key' => 'visibility', 'value' => 'public', 'compare' => '=' ) );
		} elseif ( 'sssj_org' === $pt ) {
			$args['meta_query'] = array(
				'relation' => 'OR',
				array( 'key' => 'org_hidden', 'compare' => 'NOT EXISTS' ),
				array( 'key' => 'org_hidden', 'value' => '1', 'compare' => '!=' ),
			);
		}
		$q = new WP_Query( $args );
		if ( empty( $q->posts ) ) {
			return '';
		}
		$post  = $q->posts[0];
		$title = trim( wp_strip_all_tags( get_the_title( $post ) ) );
		$link  = get_permalink( $post );
		if ( '' === $title && ! empty( $post->post_name ) ) {
			$title = ucwords( str_replace( array( '-', '_' ), ' ', $post->post_name ) ); // some listings keep their name in meta; humanise the slug
		}
		if ( '' === $title || ! $link ) {
			return '';
		}
		$labels = array(
			'sssj_worker' => __( 'A real worker profile on Just Tasks', 'shuffles-social-services-jobs' ),
			'sssj_job'    => __( 'A live job on Just Tasks', 'shuffles-social-services-jobs' ),
			'sssj_org'    => __( 'A real organisation on Just Tasks', 'shuffles-social-services-jobs' ),
		);
		$tag  = isset( $labels[ $pt ] ) ? $labels[ $pt ] : __( 'Live on Just Tasks', 'shuffles-social-services-jobs' );
		$out  = '<div class="sssj-demo-live">';
		$out .= '<span class="sssj-demo-live__tag">' . esc_html( $tag ) . '</span>';
		$out .= '<a class="sssj-demo-live__card" href="' . esc_url( $link ) . '"><strong>' . esc_html( $title ) . '</strong><span class="sssj-demo-live__go">' . esc_html__( 'View', 'shuffles-social-services-jobs' ) . ' &rarr;</span></a>';
		$out .= '</div>';
		return $out;
	}

	/**
	 * Phase 4: an autoplay "kiosk" slideshow of the personas in clean 16:9 framing, for screen-recording
	 * snappy promos or looping on a reception screen. Triggered by [sssj_demo_tour autoplay] (optionally
	 * autoplay="6" for seconds per slide) or ?sssj_kiosk. Controls: play/pause, prev/next, dots, arrow keys.
	 *
	 * @param int $secs Seconds per slide.
	 * @return string
	 */
	private static function kiosk( $secs ) {
		$people = self::personas();
		$site   = get_bloginfo( 'name' );
		$total  = count( $people );
		ob_start();
		echo '<div class="sssj sssj--demo sssj-kiosk" data-sssj-kiosk data-interval="' . esc_attr( (int) $secs * 1000 ) . '">';
		echo '<div class="sssj-kiosk__stage">';
		$i = 0;
		foreach ( $people as $key => $p ) {
			$img   = class_exists( 'Shuffles_SSJ_Stock' ) ? Shuffles_SSJ_Stock::demo_image( $key ) : null;
			$style = '--demo-ac:' . $p['accent'];
			if ( $img ) { $style .= ";--demo-photo:url('" . esc_url( $img['url'] ) . "')"; }
			echo '<section class="sssj-kiosk__slide' . ( 0 === $i ? ' is-active' : '' ) . ( $img ? ' has-photo' : '' ) . '" style="' . esc_attr( $style ) . '" aria-hidden="' . ( 0 === $i ? 'false' : 'true' ) . '">';
			echo '<div class="sssj-kiosk__inner">';
			echo '<span class="sssj-kiosk__icon" aria-hidden="true">' . esc_html( $p['icon'] ) . '</span>';
			echo '<span class="sssj-kiosk__role">' . esc_html( $p['role'] ) . '</span>';
			echo '<h2 class="sssj-kiosk__name">' . esc_html( $p['name'] ) . ' &middot; ' . esc_html( $p['loc'] ) . '</h2>';
			echo '<p class="sssj-kiosk__tag">' . esc_html( $p['tagline'] ) . '</p>';
			echo '<ul class="sssj-kiosk__points">';
			foreach ( array_slice( (array) $p['items'], 0, 3 ) as $it ) {
				echo '<li>' . esc_html( $it ) . '</li>';
			}
			echo '</ul></div></section>';
			$i++;
		}
		echo '</div>';
		echo '<div class="sssj-kiosk__bar">';
		echo '<button type="button" class="sssj-kiosk__btn" data-kiosk-prev aria-label="' . esc_attr__( 'Previous', 'shuffles-social-services-jobs' ) . '">&lsaquo;</button>';
		echo '<div class="sssj-kiosk__dots">';
		for ( $d = 0; $d < $total; $d++ ) {
			echo '<button type="button" class="sssj-kiosk__dot' . ( 0 === $d ? ' is-active' : '' ) . '" data-kiosk-dot="' . (int) $d . '" aria-label="' . esc_attr( sprintf( __( 'Go to slide %d', 'shuffles-social-services-jobs' ), $d + 1 ) ) . '"></button>';
		}
		echo '</div>';
		echo '<button type="button" class="sssj-kiosk__btn" data-kiosk-playpause aria-label="' . esc_attr__( 'Pause', 'shuffles-social-services-jobs' ) . '">&#10074;&#10074;</button>';
		echo '<button type="button" class="sssj-kiosk__btn" data-kiosk-next aria-label="' . esc_attr__( 'Next', 'shuffles-social-services-jobs' ) . '">&rsaquo;</button>';
		echo '</div>';
		echo '<div class="sssj-kiosk__brand">' . esc_html( $site ) . '</div>';
		echo '</div>';
		return ob_get_clean();
	}
}
