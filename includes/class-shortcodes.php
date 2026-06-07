<?php
/**
 * Front-end shortcodes. Phase 1: job boards (ABN/TFN segregated) + advertiser posting form.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Shortcodes {

	/** @var Shuffles_SSJ_Settings */
	private $settings;

	/** @var array Map points from the most recent board/directory render (for the AJAX marker refresh). */
	private $last_points = array();

	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_select_assets' ), 20 );
		add_shortcode( 'sssj_job_board', array( $this, 'board' ) );
		add_shortcode( 'sssj_tfn_board', array( $this, 'tfn_board' ) );
		add_shortcode( 'sssj_abn_board', array( $this, 'abn_board' ) );
		add_shortcode( 'sssj_volunteer_board', array( $this, 'volunteer_board' ) );
		add_shortcode( 'sssj_post_job', array( $this, 'post_job_form' ) );
		add_shortcode( 'sssj_worker_directory', array( $this, 'worker_directory' ) );
		add_shortcode( 'sssj_post_worker', array( $this, 'post_worker_form' ) );
		add_shortcode( 'sssj_need_board', array( $this, 'need_board' ) );
		add_shortcode( 'sssj_post_need', array( $this, 'post_need_form' ) );
		add_shortcode( 'sssj_my_listings', array( $this, 'my_listings' ) );
		add_shortcode( 'sssj_dashboard', array( $this, 'dashboard' ) );
		add_shortcode( 'sssj_roles', array( $this, 'roles_panel' ) );
		add_shortcode( 'sssj_onboard', array( $this, 'onboard' ) );
		add_shortcode( 'sssj_messages', array( $this, 'messages' ) );
		add_shortcode( 'sssj_org_directory', array( $this, 'org_directory' ) );
		add_shortcode( 'sssj_swipe', array( $this, 'provider_swipe' ) );
		add_shortcode( 'sssj_post_org', array( $this, 'post_org_form' ) );
		add_shortcode( 'sssj_org_team', array( $this, 'org_team' ) );
		add_action( 'wp_ajax_sssj_swipe_save', array( $this, 'ajax_swipe_save' ) );
		add_action( 'wp_ajax_sssj_filter', array( $this, 'ajax_filter' ) );
		add_action( 'wp_ajax_nopriv_sssj_filter', array( $this, 'ajax_filter' ) );
		add_shortcode( 'sssj_credentials', array( $this, 'credentials_panel' ) );
		add_shortcode( 'sssj_resumes', array( $this, 'resumes_panel' ) );
		add_shortcode( 'sssj_menu', array( $this, 'menu' ) );
		add_shortcode( 'sssj_tests', array( $this, 'tests_panel' ) );
		add_shortcode( 'sssj_guides', array( $this, 'guides_panel' ) );
		add_shortcode( 'sssj_workflows', array( $this, 'workflows_panel' ) );
		add_shortcode( 'sssj_policies', array( $this, 'policies_panel' ) );
		add_shortcode( 'sssj_ad', array( 'Shuffles_SSJ_Ads', 'shortcode' ) );
		add_shortcode( 'sssj_affiliate', array( 'Shuffles_SSJ_Affiliate', 'shortcode' ) );
		add_shortcode( 'sssj_feature_today', array( 'Shuffles_SSJ_Spotlight', 'shortcode' ) );
		add_shortcode( 'sssj_marketing', array( 'Shuffles_SSJ_Marketing', 'shortcode' ) );
		add_shortcode( 'sssj_create_asset', array( $this, 'create_asset' ) );
		add_shortcode( 'sssj_matches', array( $this, 'matches_panel' ) );
		add_filter( 'the_content', array( $this, 'maybe_job_map' ) );
		add_filter( 'the_content', array( $this, 'maybe_apply_panel' ) );
		add_filter( 'the_content', array( $this, 'maybe_org_panel' ) );
		add_filter( 'the_content', array( $this, 'maybe_worker_panel' ) );
		add_filter( 'the_content', array( $this, 'maybe_job_matches' ) );
		add_filter( 'the_content', array( $this, 'maybe_worker_matches' ) );
		add_filter( 'the_content', array( $this, 'maybe_worker_reviews' ) );
		add_filter( 'the_content', array( $this, 'maybe_org_reviews' ) );
		add_filter( 'the_content', array( $this, 'maybe_listing_video' ) );
		add_filter( 'the_content', array( $this, 'maybe_listing_ad' ) );

		// Optional: auto-output the navigation menu at the top of every page (testing aid).
		if ( '1' === (string) $this->settings->get( 'auto_header_menu', '0' ) ) {
			add_action( 'wp_body_open', array( $this, 'auto_header_menu' ), 5 );
		}
	}

	/** Echo the [sssj_menu] bar at the top of the page when the "auto header menu" option is on. */
	public function auto_header_menu() {
		static $done = false;
		// Some themes fire wp_body_open more than once — only ever render the auto menu a single time.
		if ( is_admin() || $done ) {
			return;
		}
		$done = true;
		echo '<div class="sssj-auto-menu">' . do_shortcode( '[sssj_menu]' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * Single source of truth for the public shortcodes — drives the Settings → Shortcodes tab.
	 * When you ADD a shortcode, add an entry here so it is documented automatically.
	 *
	 * Each entry: tag, title, what (description), where (page + audience), atts (array tag=>desc),
	 * access ('public' | 'members' | 'advertisers' | 'workers' | 'participants'), group.
	 *
	 * @return array
	 */
	public static function reference() {
		$items = array(
			array(
				'tag'    => 'sssj_menu',
				'title'  => __( 'Navigation menu (login-aware)', 'shuffles-social-services-jobs' ),
				'what'   => __( 'A ready-made navigation bar that adapts to the visitor. Logged-OUT: Jobs, Find a worker, Organisations, Log in (and Register if open). Logged-IN: Participant requests, Edit my profile, Messages, My dashboard, Log out — plus Post a job / My credentials / Request support shown only when the account can use them. Admins also get a “Settings” sub-item nested under “My dashboard”. Links resolve automatically from your configured pages (Boards tab), or by finding the page that contains each shortcode.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'Your site header, a navigation/widget area, or the top of key pages. It maintains itself — no separate menu to edit.', 'shuffles-social-services-jobs' ),
				'access' => 'public',
				'group'  => __( 'Navigation', 'shuffles-social-services-jobs' ),
				'atts'   => array(
					'title="…"' => __( 'Optional brand text shown at the left of the bar.', 'shuffles-social-services-jobs' ),
					'class="…"' => __( 'Optional extra CSS class for styling.', 'shuffles-social-services-jobs' ),
				),
			),
			array(
				'tag'    => 'sssj_job_board',
				'title'  => __( 'Job board (all engagements)', 'shuffles-social-services-jobs' ),
				'what'   => __( 'The main jobs board: every published job ad, with search, category and location/radius filters plus a results map. Shows both ABN (contractor) and TFN (employee) roles unless restricted.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A public "Jobs" page.', 'shuffles-social-services-jobs' ),
				'access' => 'public',
				'group'  => __( 'Job ads', 'shuffles-social-services-jobs' ),
				'atts'   => array(
					'basis="abn|tfn"' => __( 'Optional — restrict to one engagement basis (omit to show both).', 'shuffles-social-services-jobs' ),
					'title="Jobs"'    => __( 'Optional heading shown above the board.', 'shuffles-social-services-jobs' ),
					'per_page="12"'   => __( 'Optional — results per page (default 12).', 'shuffles-social-services-jobs' ),
				),
			),
			array(
				'tag'    => 'sssj_tfn_board',
				'title'  => __( 'Employee jobs board (TFN only)', 'shuffles-social-services-jobs' ),
				'what'   => __( 'Shows ONLY TFN employee positions (wages, tax withheld). Never shows ABN listings or participant requests — segregation is enforced in the query layer.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A public "Employee jobs" page, if you want the two engagement types on separate pages.', 'shuffles-social-services-jobs' ),
				'access' => 'public',
				'group'  => __( 'Job ads', 'shuffles-social-services-jobs' ),
				'atts'   => array( 'title="…"' => __( 'Optional heading.', 'shuffles-social-services-jobs' ), 'per_page="12"' => __( 'Optional — results per page.', 'shuffles-social-services-jobs' ) ),
			),
			array(
				'tag'    => 'sssj_abn_board',
				'title'  => __( 'Contractor jobs board (ABN only)', 'shuffles-social-services-jobs' ),
				'what'   => __( 'Shows ONLY ABN contractor / sole-trader engagements (worker invoices via an ABN). Never shows TFN listings.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A public "Contractor & ABN engagements" page.', 'shuffles-social-services-jobs' ),
				'access' => 'public',
				'group'  => __( 'Job ads', 'shuffles-social-services-jobs' ),
				'atts'   => array( 'title="…"' => __( 'Optional heading.', 'shuffles-social-services-jobs' ), 'per_page="12"' => __( 'Optional — results per page.', 'shuffles-social-services-jobs' ) ),
			),
			array(
				'tag'    => 'sssj_volunteer_board',
				'title'  => __( 'Volunteer opportunities board', 'shuffles-social-services-jobs' ),
				'what'   => __( 'Shows ONLY volunteer (unpaid) roles. Never shows paid TFN/ABN listings — segregation is enforced in the query layer, just like the other boards.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A public "Volunteer opportunities" page.', 'shuffles-social-services-jobs' ),
				'access' => 'public',
				'group'  => __( 'Job ads', 'shuffles-social-services-jobs' ),
				'atts'   => array( 'title="…"' => __( 'Optional heading.', 'shuffles-social-services-jobs' ), 'per_page="12"' => __( 'Optional — results per page.', 'shuffles-social-services-jobs' ) ),
			),
			array(
				'tag'    => 'sssj_post_job',
				'title'  => __( 'Post a job (advertiser form)', 'shuffles-social-services-jobs' ),
				'what'   => __( 'The job-posting form: title, description, ABN/TFN basis (with ABN validation), category, location autocomplete, rate and expiry, and an optional link to the advertiser’s organisation profile. Enforces the advertising subscription / free-listing cap when monetisation is on.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A "Post a job" page for advertisers.', 'shuffles-social-services-jobs' ),
				'access' => 'advertisers',
				'group'  => __( 'Job ads', 'shuffles-social-services-jobs' ),
				'atts'   => array(),
			),
			array(
				'tag'    => 'sssj_worker_directory',
				'title'  => __( 'Worker directory', 'shuffles-social-services-jobs' ),
				'what'   => __( 'Browse worker / support-worker profiles with search, service category and "available now" filters. Verified workers show the ✓ Verified badge and which checks they hold. Visibility is enforced in the query layer (guests see public profiles; members also see logged-in-only ones).', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A public "Find a worker" page.', 'shuffles-social-services-jobs' ),
				'access' => 'public',
				'group'  => __( 'Workers', 'shuffles-social-services-jobs' ),
				'atts'   => array( 'title="…"' => __( 'Optional heading.', 'shuffles-social-services-jobs' ), 'per_page="12"' => __( 'Optional — results per page.', 'shuffles-social-services-jobs' ) ),
			),
			array(
				'tag'    => 'sssj_post_worker',
				'title'  => __( 'Create / edit worker profile', 'shuffles-social-services-jobs' ),
				'what'   => __( 'Lets a logged-in worker create or update their own profile (one per user): name, bio, services, availability, employment status, rate, optional ABN and visibility (public / members-only).', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A "Create your worker profile" page.', 'shuffles-social-services-jobs' ),
				'access' => 'workers',
				'group'  => __( 'Workers', 'shuffles-social-services-jobs' ),
				'atts'   => array(),
			),
			array(
				'tag'    => 'sssj_credentials',
				'title'  => __( 'My credentials (verification)', 'shuffles-social-services-jobs' ),
				'what'   => __( 'A worker manages their checks here: add NDIS Worker Screening, WWCC, police check, First Aid, qualifications, insurance, with an evidence file. An admin reviews each one; only admin-approved credentials earn the ✓ Verified badge. Documents are stored privately and never shown publicly.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A "My credentials" page (best near the worker profile / dashboard).', 'shuffles-social-services-jobs' ),
				'access' => 'workers',
				'group'  => __( 'Workers', 'shuffles-social-services-jobs' ),
				'atts'   => array(),
			),
			array(
				'tag'    => 'sssj_resumes',
				'title'  => __( 'My résumés', 'shuffles-social-services-jobs' ),
				'what'   => __( 'Candidates store one or more named résumé files (PDF / Word / RTF / ODT), set a default, and remove old ones. Files are private — served only to the owner, admins, and (when applying) the employer. Pick which résumé to send when applying for an employee (TFN) job.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A "My résumés" page, or the “My résumés” tab in the member dashboard.', 'shuffles-social-services-jobs' ),
				'access' => 'workers',
				'group'  => __( 'Workers', 'shuffles-social-services-jobs' ),
				'atts'   => array(),
			),
			array(
				'tag'    => 'sssj_need_board',
				'title'  => __( 'Participant requests board', 'shuffles-social-services-jobs' ),
				'what'   => __( 'Lists participant support requests (always ABN). Participants are shown only by a pseudonym and suburb — never a name or contact detail. First contact goes through the internal relay.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A "Participant requests" page. LOGIN-GATED — guests are blocked and prompted to log in.', 'shuffles-social-services-jobs' ),
				'access' => 'members',
				'group'  => __( 'Participants', 'shuffles-social-services-jobs' ),
				'atts'   => array( 'title="…"' => __( 'Optional heading.', 'shuffles-social-services-jobs' ), 'per_page="12"' => __( 'Optional — results per page.', 'shuffles-social-services-jobs' ) ),
			),
			array(
				'tag'    => 'sssj_post_need',
				'title'  => __( 'Post a support request (participant)', 'shuffles-social-services-jobs' ),
				'what'   => __( 'A participant or their nominee posts a support need. The plugin generates a pseudonym (never stores a name on the listing), records suburb-level location only, and sends the post to admin moderation before it appears.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A "Request support" page for participants / nominees.', 'shuffles-social-services-jobs' ),
				'access' => 'participants',
				'group'  => __( 'Participants', 'shuffles-social-services-jobs' ),
				'atts'   => array(),
			),
			array(
				'tag'    => 'sssj_org_directory',
				'title'  => __( 'Organisation directory', 'shuffles-social-services-jobs' ),
				'what'   => __( 'Browse employer / provider organisation profiles. Each public org page (Organization structured data for SEO) lists the company’s locations and its open positions — i.e. browse jobs by company.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A public "Organisations" / "Providers" page.', 'shuffles-social-services-jobs' ),
				'access' => 'public',
				'group'  => __( 'Organisations', 'shuffles-social-services-jobs' ),
				'atts'   => array( 'per_page="12"' => __( 'Optional — results per page.', 'shuffles-social-services-jobs' ) ),
			),
			array(
				'tag'    => 'sssj_swipe',
				'title'  => __( 'Provider swipe deck', 'shuffles-social-services-jobs' ),
				'what'   => __( 'A Tinder-style way to browse providers one card at a time: swipe right (♥ / → / drag) to save a provider to your shortlist, left (✕ / ← / drag) to skip; tap a card to view the full profile. Works on touch, mouse and keyboard. Saving requires login.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A "Discover providers" page, or alongside the Organisations directory as a fun browse mode.', 'shuffles-social-services-jobs' ),
				'access' => 'public',
				'group'  => __( 'Organisations', 'shuffles-social-services-jobs' ),
				'atts'   => array(
					'count="24"' => __( 'Optional — how many providers to load into the deck (max 60).', 'shuffles-social-services-jobs' ),
					'title="…"'  => __( 'Optional heading.', 'shuffles-social-services-jobs' ),
				),
			),
			array(
				'tag'    => 'sssj_post_org',
				'title'  => __( 'Create / edit organisation profile', 'shuffles-social-services-jobs' ),
				'what'   => __( 'Lets an advertiser create or update their organisation profile (one per user): name, description, ABN, website, phone, type, primary location and additional locations. Phone and website can be set "members only" in the Privacy section.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A "Create your organisation profile" page.', 'shuffles-social-services-jobs' ),
				'access' => 'advertisers',
				'group'  => __( 'Organisations', 'shuffles-social-services-jobs' ),
				'atts'   => array(),
			),
			array(
				'tag'    => 'sssj_org_team',
				'title'  => __( 'Organisation team', 'shuffles-social-services-jobs' ),
				'what'   => __( 'Lets several people belong to one organisation. An organisation admin (the owner, or any member promoted to admin) can add an existing member by email/username, change a member’s role, or remove someone. Accounts are never created here — the person must already be registered. Also appears as a “Team” tab inside the member dashboard.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A "Team" page for organisations, or rely on the Team tab in [sssj_dashboard].', 'shuffles-social-services-jobs' ),
				'access' => 'advertisers',
				'group'  => __( 'Organisations', 'shuffles-social-services-jobs' ),
				'atts'   => array(),
			),
			array(
				'tag'    => 'sssj_onboard',
				'title'  => __( 'Get started (onboarding)', 'shuffles-social-services-jobs' ),
				'what'   => __( 'A guided first-run page: the member ticks the “hats” that apply (employer, provider, sole-trader contractor, employee, participant, …), then sees tailored next-step buttons (set up profile, create organisation/provider listing, post a job, request support, go to dashboard).', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A "Get started" page to send new members to after they register.', 'shuffles-social-services-jobs' ),
				'access' => 'members',
				'group'  => __( 'Member account', 'shuffles-social-services-jobs' ),
				'atts'   => array(),
			),
			array(
				'tag'    => 'sssj_dashboard',
				'title'  => __( 'Member dashboard (all-in-one)', 'shuffles-social-services-jobs' ),
				'what'   => __( 'One tabbed hub that ties everything together for the logged-in member: an Overview with quick stats + actions, My listings & applicants (advertisers), Matched jobs and My credentials (workers), Saved searches, and Messages. Each tab shows only if it applies to that member.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'Your main "My account" / "Dashboard" page (the single page to send logged-in members to).', 'shuffles-social-services-jobs' ),
				'access' => 'members',
				'group'  => __( 'Member account', 'shuffles-social-services-jobs' ),
				'atts'   => array(),
			),
			array(
				'tag'    => 'sssj_my_listings',
				'title'  => __( 'My listings & applicants', 'shuffles-social-services-jobs' ),
				'what'   => __( 'The member’s own applications, their job ads with applicants (and a status control), and their participant requests with responses. (Also shown inside the all-in-one dashboard.)', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A "My listings" page (or just use [sssj_dashboard]).', 'shuffles-social-services-jobs' ),
				'access' => 'members',
				'group'  => __( 'Member account', 'shuffles-social-services-jobs' ),
				'atts'   => array(),
			),
			array(
				'tag'    => 'sssj_messages',
				'title'  => __( 'Messages (internal relay inbox)', 'shuffles-social-services-jobs' ),
				'what'   => __( 'The private messaging inbox: thread list, thread view and reply. Applying or responding starts a thread to the listing owner. Relay-only — email addresses are never exposed, and participants appear as their pseudonym.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A "Messages" page.', 'shuffles-social-services-jobs' ),
				'access' => 'members',
				'group'  => __( 'Member account', 'shuffles-social-services-jobs' ),
				'atts'   => array(),
			),
			array(
				'tag'    => 'sssj_roles',
				'title'  => __( 'My roles (declare how you use the marketplace)', 'shuffles-social-services-jobs' ),
				'what'   => __( 'Lets a logged-in member tick the role(s) that apply to them — worker, candidate, participant, sole-trader provider, provider representative, or supplier. This sets what they can post (e.g. participants post needs and direct jobs free; providers can advertise and list) and tailors their dashboard. Members can change it any time. (Also shown as the "My roles" tab inside the all-in-one dashboard.)', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A "My roles" / onboarding page, or just use [sssj_dashboard].', 'shuffles-social-services-jobs' ),
				'access' => 'members',
				'group'  => __( 'Member account', 'shuffles-social-services-jobs' ),
				'atts'   => array(),
			),
			array(
				'tag'    => 'sssj_guides',
				'title'  => __( 'Guides', 'shuffles-social-services-jobs' ),
				'what'   => __( 'Plain-language how-to guides for each side of the marketplace: writing a successful job post, responding to a job, working as an ABN contractor, and building a standing profile. Collapsible panels. Same content as Settings → Guides.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A public "Guides" / "Help" page.', 'shuffles-social-services-jobs' ),
				'access' => 'public',
				'group'  => __( 'Help & content', 'shuffles-social-services-jobs' ),
				'atts'   => array(
					'title="…"' => __( 'Optional heading.', 'shuffles-social-services-jobs' ),
					'only="…"'  => __( 'Optional comma-separated guide ids to show only some (write-job-post, respond-to-job, abn-contractor-work, standing-profile).', 'shuffles-social-services-jobs' ),
				),
			),
			array(
				'tag'    => 'sssj_workflows',
				'title'  => __( 'How it works (step-by-step workflows)', 'shuffles-social-services-jobs' ),
				'what'   => __( 'Plain-language, step-by-step walkthroughs that show end users the exact path through the app to finish a task: setting up, advertising a role, applying for an employee job, quoting for contractor work, managing applicants, requesting support as a participant, storing a résumé, joining an organisation, saving alerts, volunteering, and staying safe. Collapsible numbered steps with a “Start here” button; for logged-in members, workflows for their primary role float to the top with a “For you” marker. Same content as Settings → How-to Workflows.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A public “How it works” / “Help” page (and link it from the menu).', 'shuffles-social-services-jobs' ),
				'access' => 'public',
				'group'  => __( 'Help & content', 'shuffles-social-services-jobs' ),
				'atts'   => array(
					'title="…"' => __( 'Optional heading.', 'shuffles-social-services-jobs' ),
					'only="…"'  => __( 'Optional comma-separated workflow ids (get-started, post-job, apply-tfn, respond-abn, manage-applicants, post-need, build-resume, join-org, save-alerts, volunteer, stay-safe).', 'shuffles-social-services-jobs' ),
					'roles="…"' => __( 'Optional comma-separated role keys to show only workflows for those roles (employer, provider, contractor, candidate, participant, representative, supplier).', 'shuffles-social-services-jobs' ),
				),
			),
			array(
				'tag'    => 'sssj_policies',
				'title'  => __( 'Policies (plain-English, member-facing)', 'shuffles-social-services-jobs' ),
				'what'   => __( 'Easy-read summaries of the platform’s policies — Complaints, Privacy, NDIS Code of Conduct, Incident Management, Safeguarding, Terms/Acceptable Use, Worker Screening & Verification, Data Retention, Cookies & Consent, and Anti-Discrimination & Inclusion — each with the key points and the NDIS/OAIC/AHRC + interpreter contacts. Collapsible panels. Same content as Settings → Policies. (The full, formal templates live in /docs and must be adopted before relying on them.)', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A public “Policies” / “Safety & policies” page (link it in the footer and menu).', 'shuffles-social-services-jobs' ),
				'access' => 'public',
				'group'  => __( 'Help & content', 'shuffles-social-services-jobs' ),
				'atts'   => array(
					'title="…"' => __( 'Optional heading.', 'shuffles-social-services-jobs' ),
					'only="…"'  => __( 'Optional comma-separated policy ids (complaints, privacy, code-of-conduct, incident, safeguarding, terms, screening, retention, cookies, inclusion).', 'shuffles-social-services-jobs' ),
				),
			),
			array(
				'tag'    => 'sssj_marketing',
				'title'  => __( 'Marketing master (members only)', 'shuffles-social-services-jobs' ),
				'what'   => __( 'Publishes the living marketing and product master document as a readable page: the positioning, the business logic, the full functional spec, and the out-of-the-box audience analysis. Logged-out visitors see a log-in prompt instead, and the page is kept out of search engines (noindex), so it is not readable or findable by non-members. Same content previews in Settings to Marketing.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A “Marketing” / “About the platform” page (partner-facing or internal, members only).', 'shuffles-social-services-jobs' ),
				'access' => 'members',
				'group'  => __( 'Help & content', 'shuffles-social-services-jobs' ),
				'atts'   => array(),
			),
			array(
				'tag'    => 'sssj_create_asset',
				'title'  => __( 'Create an asset (résumé builder)', 'shuffles-social-services-jobs' ),
				'what'   => __( 'A guided builder that turns a member’s worker profile into a clean, readable, one-page résumé in the locked house style (location leads, large text, verified-checks row, one call to action). Live preview, a readability check, and one-click Download PDF, Save image and Copy caption (the $0 browser path). Members polish only the wording; everything else comes from their profile. Phase 1 covers the worker / sole-trader résumé; flyers and the social graphic follow.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A “Create my résumé” page, and the “Create an asset” tab in My dashboard.', 'shuffles-social-services-jobs' ),
				'access' => 'members',
				'group'  => __( 'Member account', 'shuffles-social-services-jobs' ),
				'atts'   => array(),
			),
			array(
				'tag'    => 'sssj_ad',
				'title'  => __( 'Advertisement (Advanced Ads)', 'shuffles-social-services-jobs' ),
				'what'   => __( 'Displays an Advanced Ads banner anywhere you place it — by placement slug ([sssj_ad placement="sidebar"]), by ad id ([sssj_ad id="123"]) or by group ([sssj_ad group="4"]). Requires the Advanced Ads plugin to be active; renders nothing if it is not, or if marketplace ads are switched off (Settings → Ads). The boards and single-listing pages can also show ads automatically via mapped slots — no shortcode needed.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'Anywhere — a sidebar widget/block, inside content, or on a board/landing page.', 'shuffles-social-services-jobs' ),
				'access' => 'public',
				'group'  => __( 'Monetisation', 'shuffles-social-services-jobs' ),
				'atts'   => array(
					'placement="…"' => __( 'An Advanced Ads placement slug.', 'shuffles-social-services-jobs' ),
					'id="…"'        => __( 'A specific Advanced Ads ad id.', 'shuffles-social-services-jobs' ),
					'group="…"'     => __( 'An Advanced Ads group id.', 'shuffles-social-services-jobs' ),
				),
			),
			array(
				'tag'    => 'sssj_affiliate',
				'title'  => __( 'Earn by referring (FluentAffiliate)', 'shuffles-social-services-jobs' ),
				'what'   => __( 'A friendly “earn money by referring others” promo card that links members to the affiliate sign-up. It clearly states a PayPal account is needed to be paid, and that it can be set up later. Shown automatically in onboarding (with extra encouragement for participants); use this shortcode to place it elsewhere too, e.g. on the dashboard. Requires the FluentAffiliate plugin (or a configured affiliate URL); renders nothing otherwise. Configure in Settings → Monetisation.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A member dashboard / “Earn” page (it also appears in onboarding automatically).', 'shuffles-social-services-jobs' ),
				'access' => 'public',
				'group'  => __( 'Monetisation', 'shuffles-social-services-jobs' ),
				'atts'   => array(),
			),
			array(
				'tag'    => 'sssj_saved_searches',
				'title'  => __( 'Saved searches', 'shuffles-social-services-jobs' ),
				'what'   => __( 'Lets a logged-in member manage the searches they saved (via the “Save & alert me” button on the directories) and get a daily email when new listings match. Remove searches here.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A member dashboard / “My alerts” page.', 'shuffles-social-services-jobs' ),
				'access' => 'members',
				'group'  => __( 'Member account', 'shuffles-social-services-jobs' ),
				'atts'   => array(),
			),
			array(
				'tag'    => 'sssj_matches',
				'title'  => __( 'Matched jobs (for the logged-in worker)', 'shuffles-social-services-jobs' ),
				'what'   => __( 'Shows “Jobs matched to you” for the logged-in member’s worker profile — ranked on shared services, location, availability, engagement basis (ABN/TFN), rate and trust. (Job pages and worker profiles also show matches automatically.)', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A member dashboard / “My matches” page.', 'shuffles-social-services-jobs' ),
				'access' => 'members',
				'group'  => __( 'Member account', 'shuffles-social-services-jobs' ),
				'atts'   => array(),
			),
			array(
				'tag'    => 'sssj_tests',
				'title'  => __( 'Testing worksheet', 'shuffles-social-services-jobs' ),
				'what'   => __( 'A tester checklist covering every feature — work through each case and mark Pass/Fail (progress saved in the browser; printable). Same content as Settings → Testing.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A private/internal page for testers (or just use the Settings → Testing tab).', 'shuffles-social-services-jobs' ),
				'access' => 'public',
				'group'  => __( 'Admin & testing', 'shuffles-social-services-jobs' ),
				'atts'   => array( 'title="…"' => __( 'Optional heading.', 'shuffles-social-services-jobs' ) ),
			),
		);
		// Fold in the animated front-page display shortcodes (single source of truth in class-display.php).
		if ( class_exists( 'Shuffles_SSJ_Display' ) ) {
			$items = array_merge( $items, Shuffles_SSJ_Display::reference() );
		}
		return $items;
	}

	public function register_assets() {
		if ( ! wp_style_is( 'sssj', 'registered' ) ) {
			wp_register_style( 'sssj', SHUFFLES_SSJ_URL . 'public/assets/css/sssj.css', array(), SHUFFLES_SSJ_VERSION );
		}
		if ( ! wp_script_is( 'sssj-filters', 'registered' ) ) {
			wp_register_script( 'sssj-filters', SHUFFLES_SSJ_URL . 'public/assets/js/sssj-filters.js', array( 'sssj-spinner' ), SHUFFLES_SSJ_VERSION, true );
			wp_localize_script( 'sssj-filters', 'SSSJ_Filter', array( 'ajax' => admin_url( 'admin-ajax.php' ) ) );
		}
		// Shuffles spinner — branded busy state for lookups/queries (form submits + AJAX).
		if ( ! wp_script_is( 'sssj-spinner', 'registered' ) ) {
			wp_register_script( 'sssj-spinner', SHUFFLES_SSJ_URL . 'public/assets/js/sssj-spinner.js', array(), SHUFFLES_SSJ_VERSION, true );
			wp_localize_script( 'sssj-spinner', 'SSSJ_Spinner', array( 'logo' => self::site_logo_url() ) );
		}
		// Profile-form enhancements (section cards, completeness, toggle, sticky bar, toast).
		if ( ! wp_script_is( 'sssj-form-enhance', 'registered' ) ) {
			wp_register_script( 'sssj-form-enhance', SHUFFLES_SSJ_URL . 'public/assets/js/sssj-form-enhance.js', array(), SHUFFLES_SSJ_VERSION, true );
		}
		// Provider swipe deck (Tinder-style browse).
		if ( ! wp_script_is( 'sssj-swipe', 'registered' ) ) {
			wp_register_script( 'sssj-swipe', SHUFFLES_SSJ_URL . 'public/assets/js/sssj-swipe.js', array( 'sssj-form-enhance' ), SHUFFLES_SSJ_VERSION, true );
			wp_localize_script( 'sssj-swipe', 'SSSJ_Swipe', array(
				'ajax'       => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'sssj_swipe' ),
				'logged_in'  => is_user_logged_in() ? 1 : 0,
				'login_url'  => wp_login_url(),
			) );
		}
		// NDIS "Scan now" preview (org + worker forms).
		if ( ! wp_script_is( 'sssj-ndis-scan', 'registered' ) ) {
			wp_register_script( 'sssj-ndis-scan', SHUFFLES_SSJ_URL . 'public/assets/js/sssj-ndis-scan.js', array( 'sssj-spinner' ), SHUFFLES_SSJ_VERSION, true );
			wp_localize_script(
				'sssj-ndis-scan',
				'SSSJ_NDIS',
				array(
					'ajax'         => admin_url( 'admin-ajax.php' ),
					'nonce'        => wp_create_nonce( 'sssj_ndis_scan' ),
					'i18n_status'  => __( 'Registration status', 'shuffles-social-services-jobs' ),
					'i18n_inforce' => __( 'In force until', 'shuffles-social-services-jobs' ),
					'i18n_groups'  => __( 'Approved registration groups', 'shuffles-social-services-jobs' ),
					'i18n_abn'     => __( 'ABN (register)', 'shuffles-social-services-jobs' ),
					'i18n_addr'    => __( 'Head office (register)', 'shuffles-social-services-jobs' ),
					'i18n_web'     => __( 'Website (register)', 'shuffles-social-services-jobs' ),
					'i18n_phone'   => __( 'Phone (register)', 'shuffles-social-services-jobs' ),
					'i18n_outlets' => __( 'Outlets', 'shuffles-social-services-jobs' ),
					/* translators: %s: the ABN the member typed on the form. */
					'i18n_abnwarn' => __( '⚠ This differs from the ABN you entered (%s) — please check.', 'shuffles-social-services-jobs' ),
					'i18n_empty'   => __( 'Enter your NDIS Registration No first.', 'shuffles-social-services-jobs' ),
					'i18n_loading' => __( 'Checking the NDIS register…', 'shuffles-social-services-jobs' ),
				)
			);
		}
		// Auto header menu prints at wp_body_open (too late to enqueue its own CSS) — load it here.
		if ( '1' === (string) $this->settings->get( 'auto_header_menu', '0' ) ) {
			wp_enqueue_style( 'sssj' );
		}
		// Per-install re-skin: push the configured design tokens + custom CSS as an inline override.
		$inline = $this->appearance_css();
		if ( '' !== $inline ) {
			wp_add_inline_style( 'sssj', $inline );
		}
		// Accessibility / CALD toolbar — master-gated. Loaded site-wide so the language choice
		// and toolbar are available on every page (a floating bar appears where there's no board).
		if ( '1' === (string) $this->settings->get( 'cald_enabled', '1' ) ) {
			wp_enqueue_style( 'sssj' );
			wp_enqueue_script( 'sssj-a11y', SHUFFLES_SSJ_URL . 'public/assets/js/sssj-a11y.js', array(), SHUFFLES_SSJ_VERSION, true );
			wp_localize_script(
				'sssj-a11y',
				'SSJ_A11y',
				array(
					'lang'   => str_replace( '_', '-', get_locale() ),
					'langs'  => Shuffles_SSJ_I18n::langs(),
					'i18n'   => Shuffles_SSJ_I18n::map(),
					'rtl'    => Shuffles_SSJ_I18n::rtl_langs(),
					'labels' => array(
						'region'   => __( 'Accessibility tools', 'shuffles-social-services-jobs' ),
						'bigger'   => __( 'Larger text', 'shuffles-social-services-jobs' ),
						'contrast' => __( 'High contrast', 'shuffles-social-services-jobs' ),
						'mono'     => __( 'No colour', 'shuffles-social-services-jobs' ),
						'easyread' => __( 'Easy read', 'shuffles-social-services-jobs' ),
						'read'     => __( 'Read aloud', 'shuffles-social-services-jobs' ),
						'reset'    => __( 'Reset', 'shuffles-social-services-jobs' ),
						'voice'    => __( 'Voice input', 'shuffles-social-services-jobs' ),
						'language' => __( 'Language', 'shuffles-social-services-jobs' ),
					),
				)
			);
		}
	}

	/** Site logo URL for the branded spinner (custom_logo, else site icon), or '' if none. */
	public static function site_logo_url() {
		$lid = (int) get_theme_mod( 'custom_logo' );
		if ( $lid ) {
			$src = wp_get_attachment_image_src( $lid, 'medium' );
			if ( $src && ! empty( $src[0] ) ) {
				return (string) $src[0];
			}
		}
		$icon = function_exists( 'get_site_icon_url' ) ? get_site_icon_url( 120 ) : '';
		return $icon ? (string) $icon : '';
	}

	/**
	 * Per-install appearance overrides — design-system CSS variables + Custom CSS, scoped to .sssj.
	 */
	private function appearance_css() {
		$s    = $this->settings;
		$vars = array(
			'--sssj-blue'      => $s->get( 'color_primary', '' ),
			'--sssj-blue-deep' => $s->get( 'color_primary_deep', '' ),
			'--sssj-ink'       => $s->get( 'color_ink', '' ),
			'--sssj-text'      => $s->get( 'color_text', '' ),
			'--sssj-line'      => $s->get( 'color_line', '' ),
			'--sssj-bg'        => $s->get( 'color_bg', '' ),
			'--sssj-bg-soft'   => $s->get( 'color_bg_soft', '' ),
			'--sssj-abn'       => $s->get( 'color_abn', '' ),
			'--sssj-tfn'       => $s->get( 'color_tfn', '' ),
			'--sssj-need'      => $s->get( 'color_need', '' ),
		);
		$radius = (int) $s->get( 'ui_radius', 0 );
		if ( $radius > 0 ) {
			$vars['--sssj-radius'] = $radius . 'px';
		}
		$font = trim( (string) $s->get( 'font_family', '' ) );
		if ( '' !== $font ) {
			$vars['--sssj-font'] = $font;
		}
		$fsize = trim( (string) $s->get( 'font_size', '' ) );
		if ( '' !== $fsize ) {
			$vars['--sssj-fs'] = preg_replace( '/[^0-9.a-z%]/i', '', $fsize );
		}
		$hw = trim( (string) $s->get( 'heading_weight', '' ) );
		if ( '' !== $hw && ctype_digit( $hw ) ) {
			$vars['--sssj-weight-heading'] = (int) $hw;
		}
		// Density scales the spacing tokens used for padding + gaps.
		$density = (string) $s->get( 'ui_density', 'normal' );
		$scale   = array( 'compact' => 0.75, 'comfortable' => 1.3 );
		if ( isset( $scale[ $density ] ) ) {
			foreach ( array( '--sssj-s2' => 8, '--sssj-s3' => 12, '--sssj-s4' => 16, '--sssj-s5' => 24 ) as $tk => $px ) {
				$vars[ $tk ] = (int) round( $px * $scale[ $density ] ) . 'px';
			}
		}

		$decl = '';
		foreach ( $vars as $k => $v ) {
			$v = str_replace( array( '{', '}', '<', '>' ), '', trim( (string) $v ) );
			if ( '' !== $v ) {
				$decl .= $k . ':' . $v . ';';
			}
		}
		$css = '' !== $decl ? '.sssj{' . $decl . '}' : '';

		$custom = trim( (string) $s->get( 'custom_css', '' ) );
		if ( '' !== $custom ) {
			$css .= "\n" . $custom;
		}
		return $css;
	}

	/* --- Boards --- */

	public function tfn_board( $atts ) {
		$atts = is_array( $atts ) ? $atts : array();
		$atts['basis'] = 'tfn';
		return $this->board( $atts );
	}

	public function abn_board( $atts ) {
		$atts = is_array( $atts ) ? $atts : array();
		$atts['basis'] = 'abn';
		return $this->board( $atts );
	}

	public function volunteer_board( $atts ) {
		$atts = is_array( $atts ) ? $atts : array();
		$atts['basis'] = 'vol';
		if ( empty( $atts['title'] ) ) {
			$atts['title'] = __( 'Volunteer opportunities', 'shuffles-social-services-jobs' );
		}
		return $this->board( $atts );
	}

	public function board( $atts ) {
		$atts = shortcode_atts(
			array(
				'basis'    => '',
				'title'    => '',
				'per_page' => 12,
			),
			is_array( $atts ) ? $atts : array(),
			'sssj_job_board'
		);
		wp_enqueue_style( 'sssj' );
		wp_enqueue_script( 'sssj-filters' );

		$basis = sanitize_key( (string) $atts['basis'] );
		$extra = array(
			'paged'          => isset( $_GET['sssj_paged'] ) ? max( 1, (int) $_GET['sssj_paged'] ) : 1, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'posts_per_page' => (int) $atts['per_page'],
		);
		if ( ! empty( $_GET['sssj_cat'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$extra['category'] = array_filter( array_map( 'sanitize_title', (array) wp_unslash( $_GET['sssj_cat'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( ! empty( $_GET['sssj_funding'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$extra['funding'] = array_filter( array_map( 'sanitize_title', (array) wp_unslash( $_GET['sssj_funding'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( ! empty( $_GET['sssj_q'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$extra['s'] = sanitize_text_field( wp_unslash( $_GET['sssj_q'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		$this->read_radius( $extra );

		$query  = $this->build_board_query( 'job', $basis, $extra, (int) $atts['per_page'] );
		$points = $this->points_from_query( $query );
		$this->last_points = $points;
		$maps   = $this->enqueue_maps( $points );

		ob_start();
		$this->load_template(
			'job-board.php',
			array(
				'query'      => $query,
				'basis'      => $basis,
				'atts'       => $atts,
				'maps'       => $maps,
				'has_points' => ! empty( $points ),
				'center'     => $this->resolve_center(),
			)
		);
		wp_reset_postdata();
		$out = ob_get_clean();
		// Optional Advanced Ads slots above/below the board (empty unless Advanced Ads is active + mapped).
		if ( class_exists( 'Shuffles_SSJ_Ads' ) ) {
			$out = Shuffles_SSJ_Ads::slot( 'board_top' ) . $out . Shuffles_SSJ_Ads::slot( 'board_bottom' );
		}
		return $out;
	}

	/* --- Maps helpers --- */

	/**
	 * Resolve a search centre (lat/lng) from the request for distance display — independent of radius,
	 * so cards can show "X km away" whenever a location is in the search. Returns array|null.
	 */
	private function resolve_center() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['sssj_lat'] ) && ! empty( $_GET['sssj_lng'] ) ) {
			return array( 'lat' => (float) $_GET['sssj_lat'], 'lng' => (float) $_GET['sssj_lng'] );
		}
		if ( ! empty( $_GET['sssj_loc'] ) && class_exists( 'Shuffles_SSJ_Geo' ) ) {
			$hit = Shuffles_SSJ_Geo::geocode( sanitize_text_field( wp_unslash( $_GET['sssj_loc'] ) ) );
			if ( $hit ) {
				return array( 'lat' => (float) $hit['lat'], 'lng' => (float) $hit['lng'] );
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		return null;
	}

	/** Read a geocoded centre + radius from the request into $extra. */
	private function read_radius( &$extra ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$radius = isset( $_GET['sssj_radius'] ) ? (float) $_GET['sssj_radius'] : 0;
		if ( $radius <= 0 ) {
			return;
		}
		if ( ! empty( $_GET['sssj_lat'] ) && ! empty( $_GET['sssj_lng'] ) ) {
			// Client-side coordinates (Google autocomplete).
			$extra['lat']    = (float) $_GET['sssj_lat'];
			$extra['lng']    = (float) $_GET['sssj_lng'];
			$extra['radius'] = $radius;
		} elseif ( ! empty( $_GET['sssj_loc'] ) ) {
			// No client coordinates (e.g. no Google key) — geocode the typed place ourselves.
			$hit = Shuffles_SSJ_Geo::geocode( sanitize_text_field( wp_unslash( $_GET['sssj_loc'] ) ) );
			if ( $hit ) {
				$extra['lat']    = (float) $hit['lat'];
				$extra['lng']    = (float) $hit['lng'];
				$extra['radius'] = $radius;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Build a board query. When a radius centre is present, the bounding box pre-filters
	 * cheaply in the query layer, then we refine to the exact great-circle radius and order
	 * nearest-first (pagination reflects the full in-radius set).
	 *
	 * @param string $which 'job' | 'need'
	 */
	private function build_board_query( $which, $basis, $extra, $per_page ) {
		$per_page   = max( 1, (int) $per_page );
		$has_centre = ! empty( $extra['lat'] ) && ! empty( $extra['lng'] ) && ! empty( $extra['radius'] );
		if ( ! $has_centre ) {
			$args = ( 'need' === $which ) ? Shuffles_SSJ_Query::need_args( $extra ) : ( ( 'worker' === $which ) ? Shuffles_SSJ_Query::worker_args( $extra ) : Shuffles_SSJ_Query::base_args( $basis, $extra ) );
			return new WP_Query( $args );
		}

		// 1) Cheap bounding-box candidate pass (ids only).
		$cand                   = $extra;
		$cand['posts_per_page'] = 500;
		$cand['paged']          = 1;
		$cand_args              = ( 'need' === $which ) ? Shuffles_SSJ_Query::need_args( $cand ) : ( ( 'worker' === $which ) ? Shuffles_SSJ_Query::worker_args( $cand ) : Shuffles_SSJ_Query::base_args( $basis, $cand ) );
		$cand_args['fields']         = 'ids';
		$cand_args['posts_per_page'] = 500;
		$cand_args['paged']          = 1;
		$cand_args['no_found_rows']  = true;
		$cand_ids = ( new WP_Query( $cand_args ) )->posts;

		// 2) Exact radius + nearest-first ordering.
		$ordered  = Shuffles_SSJ_Geo::order_ids_by_distance( $cand_ids, (float) $extra['lat'], (float) $extra['lng'], (float) $extra['radius'] );
		$total    = count( $ordered );
		$paged    = max( 1, (int) ( isset( $extra['paged'] ) ? $extra['paged'] : 1 ) );
		$page_ids = array_slice( $ordered, ( $paged - 1 ) * $per_page, $per_page );

		$post_type = ( 'need' === $which ) ? 'sssj_need' : ( ( 'worker' === $which ) ? 'sssj_worker' : 'sssj_job' );
		if ( empty( $page_ids ) ) {
			return new WP_Query( array( 'post_type' => $post_type, 'post__in' => array( 0 ), 'posts_per_page' => $per_page ) );
		}
		$q = new WP_Query(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'post__in'       => $page_ids,
				'orderby'        => 'post__in',
				'posts_per_page' => $per_page,
				'no_found_rows'  => true,
			)
		);
		$q->found_posts   = $total;
		$q->max_num_pages = (int) ceil( $total / $per_page );
		return $q;
	}

	/** Build map marker points from a query's results that carry coordinates. */
	private function points_from_query( $query ) {
		$points = array();
		foreach ( $query->posts as $p ) {
			$lat = (float) get_post_meta( $p->ID, 'location_lat', true );
			$lng = (float) get_post_meta( $p->ID, 'location_lng', true );
			if ( $lat && $lng ) {
				$sub   = (string) get_post_meta( $p->ID, 'location_suburb', true );
				$state = (string) get_post_meta( $p->ID, 'location_state', true );
				$points[] = array(
					'id'    => (int) $p->ID,
					'title' => get_the_title( $p ),
					'sub'   => trim( $sub . ' ' . $state ),
					'lat'   => $lat,
					'lng'   => $lng,
					'url'   => get_permalink( $p ),
				);
			}
		}
		return $points;
	}

	/**
	 * Enqueue the maps script + Google Maps JS when a key is set. Returns true if maps are active.
	 * De-dupes the Google loader so a second async maps/api/js can't break Places/Geocoder.
	 */
	private function enqueue_maps( $points = array() ) {
		$key = (string) $this->settings->get( 'google_maps_api_key', '' );
		if ( '' === $key ) {
			return false;
		}
		wp_enqueue_script( 'sssj-maps', SHUFFLES_SSJ_URL . 'public/assets/js/sssj-maps.js', array(), SHUFFLES_SSJ_VERSION, true );
		wp_localize_script( 'sssj-maps', 'SSJ_Maps', array( 'points' => array_values( $points ) ) );
		if ( ! wp_script_is( 'sssj-gmaps', 'enqueued' ) ) {
			$src = add_query_arg(
				array(
					'key'       => $key,
					'libraries' => 'places',
					'loading'   => 'async',
					'callback'  => 'sssjInitMaps',
				),
				'https://maps.googleapis.com/maps/api/js'
			);
			wp_enqueue_script( 'sssj-gmaps', $src, array( 'sssj-maps' ), null, true );
		}
		return true;
	}

	/* --- Posting form --- */

	public function post_job_form( $atts ) {
		wp_enqueue_style( 'sssj' );
		$this->enqueue_maps();
		ob_start();
		$this->load_template( 'post-job-form.php', array( 'settings' => $this->settings ) );
		return ob_get_clean();
	}

	/* --- Workers --- */

	public function worker_directory( $atts ) {
		$atts = shortcode_atts(
			array(
				'title'    => '',
				'per_page' => 12,
			),
			is_array( $atts ) ? $atts : array(),
			'sssj_worker_directory'
		);
		wp_enqueue_style( 'sssj' );
		wp_enqueue_script( 'sssj-filters' );

		$extra = array(
			'paged'          => isset( $_GET['sssj_paged'] ) ? max( 1, (int) $_GET['sssj_paged'] ) : 1, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'posts_per_page' => (int) $atts['per_page'],
		);
		if ( ! empty( $_GET['sssj_cat'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$extra['category'] = array_filter( array_map( 'sanitize_title', (array) wp_unslash( $_GET['sssj_cat'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( ! empty( $_GET['sssj_q'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$extra['s'] = sanitize_text_field( wp_unslash( $_GET['sssj_q'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( ! empty( $_GET['sssj_avail'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$extra['available'] = 1;
		}
		$this->read_radius( $extra );

		$query  = $this->build_board_query( 'worker', '', $extra, (int) $atts['per_page'] );
		$points = $this->points_from_query( $query );
		$this->last_points = $points;
		$maps   = $this->enqueue_maps( $points );
		ob_start();
		$this->load_template( 'worker-directory.php', array( 'query' => $query, 'atts' => $atts, 'maps' => $maps, 'has_points' => ! empty( $points ), 'center' => $this->resolve_center() ) );
		wp_reset_postdata();
		return ob_get_clean();
	}

	public function post_worker_form( $atts ) {
		wp_enqueue_style( 'sssj' );
		wp_enqueue_script( 'sssj-spinner' );
		wp_enqueue_script( 'sssj-ndis-scan' );
		wp_enqueue_script( 'sssj-form-enhance' );
		$this->enqueue_maps(); // place autocomplete on the location field → fills suburb/state/postcode + lat/lng
		ob_start();
		$this->load_template( 'post-worker-form.php', array( 'settings' => $this->settings ) );
		return ob_get_clean();
	}

	/* --- Participant needs (gated, pseudonymous) --- */

	public function need_board( $atts ) {
		$atts = shortcode_atts(
			array(
				'title'    => '',
				'per_page' => 12,
			),
			is_array( $atts ) ? $atts : array(),
			'sssj_need_board'
		);
		wp_enqueue_style( 'sssj' );
		wp_enqueue_script( 'sssj-filters' );

		// Needs are never public — require login before querying.
		if ( ! is_user_logged_in() ) {
			return '<div class="sssj sssj--needs"><div class="sssj-panel"><p>'
				. esc_html__( 'Participant requests are visible to logged-in members only.', 'shuffles-social-services-jobs' )
				. ' <a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Log in', 'shuffles-social-services-jobs' ) . '</a></p></div></div>';
		}

		$extra = array(
			'paged'          => isset( $_GET['sssj_paged'] ) ? max( 1, (int) $_GET['sssj_paged'] ) : 1, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'posts_per_page' => (int) $atts['per_page'],
		);
		if ( ! empty( $_GET['sssj_support'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$extra['support'] = sanitize_title( wp_unslash( $_GET['sssj_support'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( ! empty( $_GET['sssj_funding'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$extra['funding'] = sanitize_title( wp_unslash( $_GET['sssj_funding'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		$this->read_radius( $extra );

		$query   = $this->build_board_query( 'need', '', $extra, (int) $atts['per_page'] );
		$has_map = $this->enqueue_maps(); // autocomplete for the centre field only — needs are not plotted (privacy)
		ob_start();
		$this->load_template( 'need-board.php', array( 'query' => $query, 'atts' => $atts, 'has_map' => $has_map, 'center' => $this->resolve_center() ) );
		wp_reset_postdata();
		return ob_get_clean();
	}

	public function post_need_form( $atts ) {
		wp_enqueue_style( 'sssj' );
		wp_enqueue_script( 'sssj-spinner' );
		wp_enqueue_script( 'sssj-form-enhance' );
		$this->enqueue_maps();
		ob_start();
		$this->load_template( 'post-need-form.php', array( 'settings' => $this->settings ) );
		return ob_get_clean();
	}

	/* --- Apply flow + dashboard --- */

	/**
	 * Append the apply panel to a single job page.
	 */
	public function maybe_apply_panel( $content ) {
		if ( is_singular( 'sssj_job' ) && in_the_loop() && is_main_query() ) {
			wp_enqueue_style( 'sssj' );
			ob_start();
			$this->load_template( 'apply-panel.php', array( 'job_id' => get_the_ID() ) );
			return $content . ob_get_clean();
		}
		return $content;
	}

	public function my_listings( $atts ) {
		wp_enqueue_style( 'sssj' );
		ob_start();
		$this->load_template( 'my-listings.php', array() );
		return ob_get_clean();
	}

	/** [sssj_roles] — let a logged-in member declare their role(s); grants the matching capabilities. */
	public function roles_panel( $atts ) {
		wp_enqueue_style( 'sssj' );
		if ( ! is_user_logged_in() ) {
			return '<div class="sssj"><div class="sssj-panel"><p>' . esc_html__( 'Please log in to set your roles.', 'shuffles-social-services-jobs' )
				. ' <a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Log in', 'shuffles-social-services-jobs' ) . '</a></p></div></div>';
		}
		$current = Shuffles_SSJ_Roles::member_roles( get_current_user_id() );
		$saved   = isset( $_GET['sssj_roles'] ) && '1' === sanitize_key( wp_unslash( $_GET['sssj_roles'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		ob_start();
		echo '<div class="sssj sssj--roles"><div class="sssj-panel">';
		if ( $saved ) {
			echo '<p class="sssj-badge sssj-badge--verified">' . esc_html__( 'Your roles were saved.', 'shuffles-social-services-jobs' ) . '</p>';
		}
		echo '<h2 style="margin-top:0">' . esc_html__( 'How do you use the marketplace?', 'shuffles-social-services-jobs' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Tick all that apply — one account can wear several hats. This sets what you can post and tailors your dashboard so you only see what’s relevant. You can change it any time. Participants post free; employers and providers may need a subscription to advertise or list.', 'shuffles-social-services-jobs' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="sssj-stack">';
		echo '<input type="hidden" name="action" value="sssj_save_roles" />';
		wp_nonce_field( 'sssj_save_roles', 'sssj_roles_nonce' );
		$hats   = Shuffles_SSJ_Roles::hats();
		$groups = Shuffles_SSJ_Roles::hat_groups();
		foreach ( $groups as $gkey => $glabel ) {
			echo '<h3 class="sssj-hats__group">' . esc_html( $glabel ) . '</h3>';
			echo '<div class="sssj-hats">';
			foreach ( $hats as $key => $h ) {
				if ( $h['group'] !== $gkey ) {
					continue;
				}
				$on = in_array( $key, $current, true );
				echo '<label class="sssj-hat' . ( $on ? ' is-on' : '' ) . '">';
				echo '<input type="checkbox" name="sssj_roles[]" value="' . esc_attr( $key ) . '" ' . checked( $on, true, false ) . ' />';
				echo '<span class="sssj-hat__body"><span class="sssj-hat__label">' . esc_html( $h['label'] ) . '</span><span class="sssj-hat__desc">' . esc_html( $h['desc'] ) . '</span></span>';
				echo '</label>';
			}
			echo '</div>';
		}
		// Primary (focus) role — tailors the default dashboard view + menu; everything stays reachable via "See all".
		$primary = Shuffles_SSJ_Roles::primary_role( get_current_user_id() );
		echo '<h3 class="sssj-hats__group">' . esc_html__( 'Primary role (optional)', 'shuffles-social-services-jobs' ) . '</h3>';
		echo '<p class="description">' . esc_html__( 'Pick the hat you use most. Your dashboard opens to it and your menu focuses on it — the rest stays one click away under “See all”. Leave on “No preference” to see everything.', 'shuffles-social-services-jobs' ) . '</p>';
		echo '<select class="sssj-select" name="sssj_primary_role">';
		echo '<option value="">' . esc_html__( 'No preference — show everything', 'shuffles-social-services-jobs' ) . '</option>';
		foreach ( $hats as $key => $h ) {
			$sel = ( $key === $primary && in_array( $key, $current, true ) ) ? ' selected' : '';
			echo '<option value="' . esc_attr( $key ) . '"' . $sel . '>' . esc_html( $h['label'] ) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Tip: the primary role only takes effect once you’ve ticked it as one of your hats above.', 'shuffles-social-services-jobs' ) . '</p>';

		echo '<div style="margin-top:14px"><button class="sssj-btn sssj-btn--primary" type="submit">' . esc_html__( 'Save my hats', 'shuffles-social-services-jobs' ) . '</button></div>';
		echo '</form></div></div>';
		return ob_get_clean();
	}

	/** [sssj_onboard] — a guided first-run: pick your hats, then see tailored next steps. */
	public function onboard( $atts ) {
		wp_enqueue_style( 'sssj' );
		if ( ! is_user_logged_in() ) {
			return '<div class="sssj"><div class="sssj-panel"><p>' . esc_html__( 'Please log in to get started.', 'shuffles-social-services-jobs' )
				. ' <a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Log in', 'shuffles-social-services-jobs' ) . '</a></p></div></div>';
		}
		$uid     = get_current_user_id();
		$current = Shuffles_SSJ_Roles::member_roles( $uid );
		$saved   = isset( $_GET['sssj_roles'] ) && '1' === sanitize_key( wp_unslash( $_GET['sssj_roles'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$hats    = Shuffles_SSJ_Roles::hats();
		$groups  = Shuffles_SSJ_Roles::hat_groups();

		ob_start();
		echo '<div class="sssj sssj--onboard"><div class="sssj-panel">';
		echo '<h2 style="margin-top:0">' . esc_html__( 'Welcome — let’s set you up', 'shuffles-social-services-jobs' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Tell us how you’ll use the marketplace — tick everything that applies (one account can wear several hats). We’ll tailor your dashboard and show the right next steps. You can change this any time.', 'shuffles-social-services-jobs' ) . '</p>';
		if ( $saved ) {
			echo '<p class="sssj-badge sssj-badge--verified">' . esc_html__( 'Saved — your next steps are below.', 'shuffles-social-services-jobs' ) . '</p>';
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="sssj-stack">';
		echo '<input type="hidden" name="action" value="sssj_save_roles" />';
		wp_nonce_field( 'sssj_save_roles', 'sssj_roles_nonce' );
		foreach ( $groups as $gkey => $glabel ) {
			echo '<h3 class="sssj-hats__group">' . esc_html( $glabel ) . '</h3><div class="sssj-hats">';
			foreach ( $hats as $key => $h ) {
				if ( $h['group'] !== $gkey ) { continue; }
				$on = in_array( $key, $current, true );
				echo '<label class="sssj-hat' . ( $on ? ' is-on' : '' ) . '"><input type="checkbox" name="sssj_roles[]" value="' . esc_attr( $key ) . '" ' . checked( $on, true, false ) . ' /><span class="sssj-hat__body"><span class="sssj-hat__label">' . esc_html( $h['label'] ) . '</span><span class="sssj-hat__desc">' . esc_html( $h['desc'] ) . '</span></span></label>';
			}
			echo '</div>';
		}
		echo '<div style="margin-top:14px"><button class="sssj-btn sssj-btn--primary" type="submit">' . esc_html( $current ? __( 'Update & see next steps', 'shuffles-social-services-jobs' ) : __( 'Continue', 'shuffles-social-services-jobs' ) ) . '</button></div>';
		echo '</form>';

		if ( $current ) {
			$rev = Shuffles_SSJ_Roles::reveals_for( $uid );
			echo '<hr /><h3>' . esc_html__( 'Your next steps', 'shuffles-social-services-jobs' ) . '</h3><div class="sssj-row" style="flex-wrap:wrap;gap:8px">';
			if ( in_array( 'profile', $rev, true ) ) {
				echo '<a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' . esc_url( $this->resolve_page( 'page_post_worker', '[sssj_post_worker]' ) ) . '">' . esc_html__( 'Set up my profile', 'shuffles-social-services-jobs' ) . '</a>';
			}
			if ( in_array( 'org', $rev, true ) ) {
				echo '<a class="sssj-btn sssj-btn--secondary sssj-btn--sm" href="' . esc_url( $this->resolve_page( 'page_post_org', '[sssj_post_org]' ) ) . '">' . esc_html__( 'Create my organisation / provider listing', 'shuffles-social-services-jobs' ) . '</a>';
			}
			if ( in_array( 'listings', $rev, true ) ) {
				echo '<a class="sssj-btn sssj-btn--ghost sssj-btn--sm" href="' . esc_url( $this->resolve_page( 'page_post_job', '[sssj_post_job]' ) ) . '">' . esc_html__( 'Post a job', 'shuffles-social-services-jobs' ) . '</a>';
			}
			if ( in_array( 'needs', $rev, true ) ) {
				echo '<a class="sssj-btn sssj-btn--ghost sssj-btn--sm" href="' . esc_url( $this->resolve_page( 'page_post_need', '[sssj_post_need]' ) ) . '">' . esc_html__( 'Request support', 'shuffles-social-services-jobs' ) . '</a>';
			}
			$dash = $this->resolve_page( 'page_my_listings', '[sssj_dashboard]' );
			if ( $dash ) {
				echo '<a class="sssj-btn sssj-btn--ghost sssj-btn--sm" href="' . esc_url( $dash ) . '">' . esc_html__( 'Go to my dashboard', 'shuffles-social-services-jobs' ) . '</a>';
			}
			echo '</div>';
		}
		echo '</div>'; // close the first panel
		// Optional: invite members (especially participants) to earn via referrals — never blocks onboarding.
		if ( class_exists( 'Shuffles_SSJ_Affiliate' ) ) {
			echo Shuffles_SSJ_Affiliate::render_card( $uid, $current, 'onboard' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</div>'; // close the outer wrapper
		return ob_get_clean();
	}


	/**
	 * [sssj_dashboard] — a single, tabbed member hub that pulls the member-facing pieces together.
	 * Capability-aware: shows worker sections for workers, advertiser sections for advertisers.
	 * Sections compose the existing shortcodes so there is one source of truth per feature.
	 */
	public function dashboard( $atts ) {
		wp_enqueue_style( 'sssj' );
		if ( ! is_user_logged_in() ) {
			return '<div class="sssj"><div class="sssj-panel"><p>' . esc_html__( 'Please log in to view your dashboard.', 'shuffles-social-services-jobs' )
				. ' <a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Log in', 'shuffles-social-services-jobs' ) . '</a></p></div></div>';
		}
		wp_enqueue_script( 'sssj-dashboard', SHUFFLES_SSJ_URL . 'public/assets/js/sssj-dashboard.js', array(), SHUFFLES_SSJ_VERSION, true );

		$uid = get_current_user_id();
		$has_worker = (bool) get_posts( array( 'post_type' => 'sssj_worker', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => true, 'meta_key' => 'worker_user_id', 'meta_value' => $uid ) );
		$has_org    = (bool) get_posts( array( 'post_type' => 'sssj_org', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => true, 'meta_key' => 'org_user_id', 'meta_value' => $uid ) );
		$is_adv     = current_user_can( 'sssj_post_job' ) || $has_org || (bool) get_posts( array( 'post_type' => 'sssj_job', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => true, 'author' => $uid ) );
		$is_worker  = $has_worker || current_user_can( 'sssj_post_worker' );

		// Hat-driven reveal: if the member has declared hats, show only the matching sections;
		// otherwise (legacy / not yet onboarded) fall back to capability detection so nothing vanishes.
		$has_hats      = class_exists( 'Shuffles_SSJ_Roles' ) && Shuffles_SSJ_Roles::has_any_hat( $uid );
		$rev           = $has_hats ? Shuffles_SSJ_Roles::reveals_for( $uid ) : array();
		$want_listings = $has_hats ? in_array( 'listings', $rev, true ) : $is_adv;
		$want_worker   = $has_hats ? in_array( 'profile', $rev, true ) : $is_worker;
		$want_matches  = $has_hats ? in_array( 'matches', $rev, true ) : $is_worker;
		$want_creds    = $has_hats ? in_array( 'credentials', $rev, true ) : $is_worker;
		$want_org      = $has_hats ? in_array( 'org', $rev, true ) : ( $is_adv || $has_org );
		$want_needs    = $has_hats ? in_array( 'needs', $rev, true ) : current_user_can( 'sssj_post_need' );
		// Team (D): show the team manager to anyone who administers at least one organisation.
		$admin_orgs    = class_exists( 'Shuffles_SSJ_Org_Team' ) ? Shuffles_SSJ_Org_Team::orgs_administered_by( $uid ) : array();
		$want_team     = ! empty( $admin_orgs );

		// Quick counts.
		$n_apps = class_exists( 'Shuffles_SSJ_Applications' ) ? count( (array) Shuffles_SSJ_Applications::for_applicant( $uid ) ) : 0;
		$n_jobs = (int) count( get_posts( array( 'post_type' => 'sssj_job', 'post_status' => 'publish', 'posts_per_page' => 100, 'fields' => 'ids', 'no_found_rows' => true, 'author' => $uid ) ) );
		$saved  = get_user_meta( $uid, '_sssj_saved_searches', true );
		$n_saved = is_array( $saved ) ? count( $saved ) : 0;

		// Tabs (slug => label), revealed by the member's hats (cap fallback for legacy users).
		$tabs = array( 'overview' => __( 'Overview', 'shuffles-social-services-jobs' ) );
		if ( $want_worker ) { $tabs['profile'] = __( 'My profile', 'shuffles-social-services-jobs' ); }
		if ( $want_listings ) { $tabs['listings'] = __( 'My listings & applicants', 'shuffles-social-services-jobs' ); }
		if ( $want_team ) {
			$team_reqs = 0;
			foreach ( $admin_orgs as $aoid ) { $team_reqs += (int) Shuffles_SSJ_Org_Team::request_count( $aoid ); }
			$tabs['team'] = $team_reqs ? sprintf( __( 'Team (%d)', 'shuffles-social-services-jobs' ), $team_reqs ) : __( 'Team', 'shuffles-social-services-jobs' );
		}
		if ( $want_matches ) { $tabs['matches'] = __( 'Matched jobs', 'shuffles-social-services-jobs' ); }
		if ( $want_creds ) { $tabs['credentials'] = __( 'My credentials', 'shuffles-social-services-jobs' ); }
		if ( $want_worker ) { $tabs['resumes'] = __( 'My résumés', 'shuffles-social-services-jobs' ); }
		if ( ( $want_worker || $want_listings ) && class_exists( 'Shuffles_SSJ_Assets' ) && Shuffles_SSJ_Assets::enabled() ) { $tabs['create-asset'] = __( 'Create an asset', 'shuffles-social-services-jobs' ); }
		$tabs['saved']    = __( 'Saved searches', 'shuffles-social-services-jobs' );
		$tabs['messages'] = __( 'Messages', 'shuffles-social-services-jobs' );
		// Earn (referrals) + Support tabs appear only when those capabilities are available.
		$want_earn    = class_exists( 'Shuffles_SSJ_Affiliate' ) && Shuffles_SSJ_Affiliate::enabled();
		$want_support = class_exists( 'Shuffles_SSJ_Support' ) && Shuffles_SSJ_Support::enabled();
		if ( $want_earn ) { $tabs['earn'] = __( 'Earn', 'shuffles-social-services-jobs' ); }
		if ( $want_support ) { $tabs['support'] = __( 'Support', 'shuffles-social-services-jobs' ); }
		$tabs['roles']    = __( 'My roles', 'shuffles-social-services-jobs' );

		$current = wp_get_current_user();
		$name    = $current->display_name ? $current->display_name : $current->user_login;

		// Open the dashboard on the member's primary-role tab (everything else stays one click away).
		$primary_role = $has_hats ? Shuffles_SSJ_Roles::primary_role( $uid ) : '';
		$tab_map      = array(
			'employer' => 'listings', 'provider' => 'listings', 'supplier' => 'listings',
			'contractor' => 'profile', 'candidate' => 'profile',
			'participant' => 'overview', 'representative' => 'overview',
		);
		$default_tab = ( $primary_role && isset( $tab_map[ $primary_role ], $tabs[ $tab_map[ $primary_role ] ] ) ) ? $tab_map[ $primary_role ] : 'overview';

		ob_start();
		echo '<div class="sssj sssj--dash" data-sssj-dash data-sssj-dash-default="' . esc_attr( $default_tab ) . '">';
		echo '<div class="sssj-panel sssj-dash__head"><h2 style="margin-top:0">' . esc_html( sprintf( __( 'Welcome, %s', 'shuffles-social-services-jobs' ), $name ) ) . '</h2>';
		echo '<nav class="sssj-dash__tabs" role="tablist">';
		$first = true;
		foreach ( $tabs as $slug => $label ) {
			echo '<button type="button" class="sssj-btn sssj-btn--ghost sssj-btn--sm sssj-dash__tab' . ( $first ? ' is-active' : '' ) . '" role="tab" data-dash-tab="' . esc_attr( $slug ) . '">' . esc_html( $label ) . '</button>';
			$first = false;
		}
		echo '</nav></div>';

		// Overview panel.
		echo '<section class="sssj-dash__panel is-active" data-dash-panel="overview">';
		echo '<div class="sssj-panel"><div class="sssj-dash__stats">';
		$tiles = array();
		if ( $want_worker ) { $tiles[] = array( $n_apps, __( 'Applications sent', 'shuffles-social-services-jobs' ) ); }
		if ( $want_listings ) { $tiles[] = array( $n_jobs, __( 'Active job listings', 'shuffles-social-services-jobs' ) ); }
		$tiles[] = array( $n_saved, __( 'Saved searches', 'shuffles-social-services-jobs' ) );
		foreach ( $tiles as $t ) {
			echo '<div class="sssj-dash__stat"><span class="sssj-dash__num">' . esc_html( (string) $t[0] ) . '</span><span class="sssj-dash__lbl">' . esc_html( $t[1] ) . '</span></div>';
		}
		echo '</div><div class="sssj-row" style="margin-top:14px;flex-wrap:wrap">';
		if ( $want_listings ) { echo '<a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' . esc_url( $this->resolve_page( 'page_post_job', '[sssj_post_job]' ) ) . '">' . esc_html__( 'Post a job', 'shuffles-social-services-jobs' ) . '</a>'; }
		if ( $want_worker ) { echo '<a class="sssj-btn sssj-btn--secondary sssj-btn--sm" href="' . esc_url( $this->resolve_page( 'page_post_worker', '[sssj_post_worker]' ) ) . '">' . esc_html( $has_worker ? __( 'Edit my profile', 'shuffles-social-services-jobs' ) : __( 'Create my profile', 'shuffles-social-services-jobs' ) ) . '</a>'; }
		if ( $want_org ) { echo '<a class="sssj-btn sssj-btn--ghost sssj-btn--sm" href="' . esc_url( $this->resolve_page( 'page_post_org', '[sssj_post_org]' ) ) . '">' . esc_html( $has_org ? __( 'Edit organisation', 'shuffles-social-services-jobs' ) : __( 'Create organisation', 'shuffles-social-services-jobs' ) ) . '</a>'; }
		if ( $want_needs ) { echo '<a class="sssj-btn sssj-btn--ghost sssj-btn--sm" href="' . esc_url( $this->resolve_page( 'page_post_need', '[sssj_post_need]' ) ) . '">' . esc_html__( 'Request support', 'shuffles-social-services-jobs' ) . '</a>'; }
		echo '</div><p class="description" style="margin:8px 0 0">' . esc_html__( 'Use the “My roles” tab to add or change your hats — your dashboard updates to match.', 'shuffles-social-services-jobs' ) . '</p></div></section>';

		// My profile — edit the personal (worker/contractor) profile inline. Only for those hats.
		if ( $want_worker ) {
			echo '<section class="sssj-dash__panel" data-dash-panel="profile">';
			echo '<div class="sssj-panel"><h3 style="margin-top:0">' . esc_html__( 'My profile', 'shuffles-social-services-jobs' ) . '</h3>';
			echo '<p class="description">' . esc_html__( 'Edit your personal worker / contractor profile here.', 'shuffles-social-services-jobs' ) . '</p>';
			if ( $want_org || $want_needs ) {
				echo '<div class="sssj-row" style="flex-wrap:wrap;margin-bottom:10px">';
				if ( $want_org ) { echo '<a class="sssj-btn sssj-btn--ghost sssj-btn--sm" href="' . esc_url( $this->resolve_page( 'page_post_org', '[sssj_post_org]' ) ) . '">' . esc_html( $has_org ? __( 'Edit organisation', 'shuffles-social-services-jobs' ) : __( 'Create organisation', 'shuffles-social-services-jobs' ) ) . '</a>'; }
				if ( $want_needs ) { echo '<a class="sssj-btn sssj-btn--ghost sssj-btn--sm" href="' . esc_url( $this->resolve_page( 'page_post_need', '[sssj_post_need]' ) ) . '">' . esc_html__( 'Participant support request', 'shuffles-social-services-jobs' ) . '</a>'; }
				echo '</div>';
			}
			echo '</div>';
			echo do_shortcode( '[sssj_post_worker]' );
			echo '</section>';
		}

		// Composed sections.
		if ( $want_listings ) {
			echo '<section class="sssj-dash__panel" data-dash-panel="listings">' . do_shortcode( '[sssj_my_listings]' ) . '</section>';
		}
		if ( $want_team ) {
			echo '<section class="sssj-dash__panel" data-dash-panel="team">' . do_shortcode( '[sssj_org_team]' ) . '</section>';
		}
		if ( $want_matches ) {
			echo '<section class="sssj-dash__panel" data-dash-panel="matches">' . do_shortcode( '[sssj_matches]' ) . '</section>';
		}
		if ( $want_creds ) {
			echo '<section class="sssj-dash__panel" data-dash-panel="credentials">' . do_shortcode( '[sssj_credentials]' ) . '</section>';
		}
		if ( $want_worker ) {
			echo '<section class="sssj-dash__panel" data-dash-panel="resumes">' . do_shortcode( '[sssj_resumes]' ) . '</section>';
		}
		if ( ( $want_worker || $want_listings ) && class_exists( 'Shuffles_SSJ_Assets' ) && Shuffles_SSJ_Assets::enabled() ) {
			echo '<section class="sssj-dash__panel" data-dash-panel="create-asset">' . do_shortcode( '[sssj_create_asset]' ) . '</section>';
		}
		echo '<section class="sssj-dash__panel" data-dash-panel="saved">' . do_shortcode( '[sssj_saved_searches]' ) . '</section>';
		echo '<section class="sssj-dash__panel" data-dash-panel="messages">' . do_shortcode( '[sssj_messages]' ) . '</section>';
		if ( $want_earn ) {
			echo '<section class="sssj-dash__panel" data-dash-panel="earn">' . Shuffles_SSJ_Affiliate::render_dashboard( $uid ) . '</section>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		if ( $want_support ) {
			echo '<section class="sssj-dash__panel" data-dash-panel="support">' . Shuffles_SSJ_Support::render() . '</section>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '<section class="sssj-dash__panel" data-dash-panel="roles">' . do_shortcode( '[sssj_roles]' ) . '</section>';

		echo '</div>';
		return ob_get_clean();
	}

	/**
	 * Org team manager (D). Shows the roster of the organisation the current user manages; org
	 * admins can add an existing member, change a role, or remove someone. With no manageable org
	 * it nudges the user to create one. Accepts an optional org id via ?sssj_org_id= when a user
	 * manages more than one.
	 */
	public function org_team( $atts ) {
		wp_enqueue_style( 'sssj' );
		if ( ! is_user_logged_in() ) {
			return '<div class="sssj"><div class="sssj-panel"><p>' . esc_html__( 'Please log in to manage your organisation’s team.', 'shuffles-social-services-jobs' )
				. ' <a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Log in', 'shuffles-social-services-jobs' ) . '</a></p></div></div>';
		}
		$uid     = get_current_user_id();
		$managed = class_exists( 'Shuffles_SSJ_Org_Team' ) ? Shuffles_SSJ_Org_Team::orgs_administered_by( $uid ) : array();
		if ( empty( $managed ) ) {
			return '<div class="sssj"><div class="sssj-panel"><h3 style="margin-top:0">' . esc_html__( 'Your team', 'shuffles-social-services-jobs' ) . '</h3><p>'
				. esc_html__( 'You don’t manage an organisation yet. Create your organisation profile first, then you can invite team members here.', 'shuffles-social-services-jobs' )
				. '</p><a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' . esc_url( $this->resolve_page( 'page_post_org', '[sssj_post_org]' ) ) . '">' . esc_html__( 'Create organisation', 'shuffles-social-services-jobs' ) . '</a></div></div>';
		}
		$want = isset( $_GET['sssj_org_id'] ) ? (int) $_GET['sssj_org_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$org_id = ( $want && in_array( $want, array_map( 'intval', $managed ), true ) ) ? $want : (int) $managed[0];

		ob_start();
		$this->load_template(
			'org-team.php',
			array(
				'org_id'  => $org_id,
				'managed' => array_map( 'intval', $managed ),
			)
		);
		return ob_get_clean();
	}

	public function messages( $atts ) {
		wp_enqueue_style( 'sssj' );
		ob_start();
		$this->load_template( 'messages.php', array() );
		return ob_get_clean();
	}

	/* --- Organisation profiles --- */

	public function org_directory( $atts ) {
		wp_enqueue_style( 'sssj' );
		wp_enqueue_script( 'sssj-filters' );
		$atts  = shortcode_atts( array( 'per_page' => 12 ), is_array( $atts ) ? $atts : array(), 'sssj_org_directory' );
		$per   = max( 1, (int) $atts['per_page'] );
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$paged = isset( $_GET['sssj_paged'] ) ? max( 1, (int) $_GET['sssj_paged'] ) : 1;
		$q     = ! empty( $_GET['sssj_q'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_q'] ) ) : '';
		// Smart synonym-aware search (C5) needs the posts_search filter, which get_posts() suppresses
		// by default — so opt in via the smart var + suppress_filters=false when there's a query.
		$search_args = ( '' !== $q && class_exists( 'Shuffles_SSJ_Search' ) )
			? array( Shuffles_SSJ_Search::QV => $q, 'suppress_filters' => false )
			: array( 's' => $q );

		// Resolve a radius centre (client coords, else keyless server geocode of the typed place).
		$radius = isset( $_GET['sssj_radius'] ) ? (float) $_GET['sssj_radius'] : 0;
		$clat   = 0;
		$clng   = 0;
		if ( $radius > 0 ) {
			if ( ! empty( $_GET['sssj_lat'] ) && ! empty( $_GET['sssj_lng'] ) ) {
				$clat = (float) $_GET['sssj_lat'];
				$clng = (float) $_GET['sssj_lng'];
			} elseif ( ! empty( $_GET['sssj_loc'] ) ) {
				$hit = Shuffles_SSJ_Geo::geocode( sanitize_text_field( wp_unslash( $_GET['sssj_loc'] ) ) );
				if ( $hit ) {
					$clat = (float) $hit['lat'];
					$clng = (float) $hit['lng'];
				}
			}
		}
		// Sector / funding / open-placements filters (applied to both the radius and plain paths).
		$tax_query = array();
		if ( ! empty( $_GET['sssj_sector'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$tax_query[] = array( 'taxonomy' => 'sssjt_category', 'field' => 'slug', 'terms' => sanitize_title( wp_unslash( $_GET['sssj_sector'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( ! empty( $_GET['sssj_funding'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$tax_query[] = array( 'taxonomy' => 'sssjt_funding_source', 'field' => 'slug', 'terms' => sanitize_title( wp_unslash( $_GET['sssj_funding'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}
		$open_ids = null;
		if ( ! empty( $_GET['sssj_open'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$open_ids = array();
			$jobs = get_posts( array( 'post_type' => 'sssj_job', 'post_status' => 'publish', 'posts_per_page' => 1000, 'fields' => 'ids', 'no_found_rows' => true, 'meta_query' => array( array( 'key' => 'organisation_id', 'value' => 0, 'compare' => '>' ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
			foreach ( $jobs as $jid ) {
				$o = (int) get_post_meta( $jid, 'organisation_id', true );
				if ( $o ) {
					$open_ids[ $o ] = true;
				}
			}
			$open_ids = array_keys( $open_ids );
			if ( empty( $open_ids ) ) {
				$open_ids = array( 0 ); // no open placements anywhere → match nothing
			}
		}
		$base_filter = array();
		if ( $tax_query ) {
			$base_filter['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery
		}
		if ( null !== $open_ids ) {
			$base_filter['post__in'] = $open_ids;
		}
		// Never list orgs whose owner flagged "Do not display".
		$org_hidden_group = array(
			'relation' => 'OR',
			array( 'key' => 'org_hidden', 'compare' => 'NOT EXISTS' ),
			array( 'key' => 'org_hidden', 'value' => '1', 'compare' => '!=' ),
		);
		// Extra meta filters: organisation category, the directory-listing fee gate, and custom fields.
		$extra_clauses = array();
		if ( ! empty( $_GET['sssj_orgcat'] ) ) {
			$extra_clauses[] = array( 'key' => 'org_category', 'value' => sanitize_key( wp_unslash( $_GET['sssj_orgcat'] ) ) );
		}
		if ( ! empty( $_GET['sssj_size'] ) ) {
			$extra_clauses[] = array( 'key' => 'org_size', 'value' => sanitize_key( wp_unslash( $_GET['sssj_size'] ) ) );
		}
		if ( ! empty( $_GET['sssj_structure'] ) ) {
			$extra_clauses[] = array( 'key' => 'org_structure', 'value' => sanitize_key( wp_unslash( $_GET['sssj_structure'] ) ) );
		}
		// Providers pay to be listed — when monetisation is on, only org_listed orgs appear.
		if ( class_exists( 'Shuffles_SSJ_Monetisation' ) && Shuffles_SSJ_Monetisation::enabled() ) {
			$extra_clauses[] = array( 'key' => 'org_listed', 'value' => '1' );
		}
		// Custom "show on banner filters" fields (organisations use a bespoke query, so merge here).
		if ( class_exists( 'Shuffles_SSJ_Field_Registry' ) ) {
			$extra_clauses = array_merge( $extra_clauses, Shuffles_SSJ_Field_Registry::filter_clauses( 'org' ) );
		}
		if ( $extra_clauses ) {
			$mq = array( 'relation' => 'AND', $org_hidden_group );
			foreach ( $extra_clauses as $c ) {
				$mq[] = $c;
			}
			$base_filter['meta_query'] = $mq; // phpcs:ignore WordPress.DB.SlowDBQuery
		} else {
			$base_filter['meta_query'] = $org_hidden_group; // phpcs:ignore WordPress.DB.SlowDBQuery
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( $radius > 0 && $clat && $clng ) {
			// Match an org if ANY of its locations is within the radius; order by nearest.
			$cand = get_posts( array_merge( array( 'post_type' => 'sssj_org', 'post_status' => 'publish', 'posts_per_page' => 500, 'fields' => 'ids', 'no_found_rows' => true ), $search_args, $base_filter ) );
			$dist = array();
			foreach ( $cand as $id ) {
				$d = Shuffles_SSJ_Org::nearest_km( $id, $clat, $clng );
				if ( null !== $d && $d <= $radius ) {
					$dist[ $id ] = $d;
				}
			}
			asort( $dist );
			$ids      = array_keys( $dist );
			$total    = count( $ids );
			$page_ids = array_slice( $ids, ( $paged - 1 ) * $per, $per );
			if ( empty( $page_ids ) ) {
				$query = new WP_Query( array( 'post_type' => 'sssj_org', 'post__in' => array( 0 ), 'posts_per_page' => $per ) );
			} else {
				$query = new WP_Query( array( 'post_type' => 'sssj_org', 'post_status' => 'publish', 'post__in' => $page_ids, 'orderby' => 'post__in', 'posts_per_page' => $per, 'no_found_rows' => true ) );
				$query->found_posts   = $total;
				$query->max_num_pages = (int) ceil( $total / $per );
			}
		} else {
			// Sponsored orgs first, then newest — partitioned in PHP so orgs without the meta are never excluded
			// and pagination stays correct (ordering by an optional meta inside a nested clause is unreliable in WP_Query).
			$all_ids = get_posts( array_merge( array( 'post_type' => 'sssj_org', 'post_status' => 'publish', 'posts_per_page' => 500, 'fields' => 'ids', 'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => true ), $search_args, $base_filter ) );
			$spon = array();
			$rest = array();
			foreach ( $all_ids as $id ) {
				if ( Shuffles_SSJ_Org::is_sponsored( $id ) ) {
					$spon[] = $id;
				} else {
					$rest[] = $id;
				}
			}
			$ids      = array_merge( $spon, $rest );
			$total    = count( $ids );
			$page_ids = array_slice( $ids, ( $paged - 1 ) * $per, $per );
			if ( empty( $page_ids ) ) {
				$query = new WP_Query( array( 'post_type' => 'sssj_org', 'post__in' => array( 0 ), 'posts_per_page' => $per ) );
			} else {
				$query = new WP_Query( array( 'post_type' => 'sssj_org', 'post_status' => 'publish', 'post__in' => $page_ids, 'orderby' => 'post__in', 'posts_per_page' => $per, 'no_found_rows' => true ) );
				$query->found_posts   = $total;
				$query->max_num_pages = (int) ceil( $total / $per );
			}
		}

		// Map points = every location of the orgs on this page.
		$points = array();
		foreach ( $query->posts as $p ) {
			foreach ( Shuffles_SSJ_Org::location_points( $p->ID ) as $pt ) {
				$points[] = array( 'id' => (int) $p->ID, 'title' => get_the_title( $p ), 'sub' => ( isset( $pt['label'] ) && $pt['label'] ) ? (string) $pt['label'] : (string) get_post_meta( $p->ID, 'location_suburb', true ), 'lat' => $pt['lat'], 'lng' => $pt['lng'], 'url' => get_permalink( $p ) );
			}
		}
		$maps = $this->enqueue_maps( $points );

		ob_start();
		$this->load_template( 'org-directory.php', array( 'query' => $query, 'maps' => $maps, 'has_points' => ! empty( $points ), 'center' => ( $clat && $clng ) ? array( 'lat' => $clat, 'lng' => $clng ) : $this->resolve_center() ) );
		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * [sssj_swipe] — a Tinder-style swipe deck for browsing providers (organisations).
	 * Swipe/keys/buttons: right (or ♥ / →) saves to the member's shortlist, left (or ✕ / ←) skips.
	 */
	public function provider_swipe( $atts ) {
		wp_enqueue_style( 'sssj' );
		wp_enqueue_script( 'sssj-swipe' );
		$a   = shortcode_atts( array( 'count' => 24, 'title' => __( 'Browse providers', 'shuffles-social-services-jobs' ) ), is_array( $atts ) ? $atts : array(), 'sssj_swipe' );
		$per = max( 1, min( 60, (int) $a['count'] ) );

		$hidden_or = array( 'relation' => 'OR', array( 'key' => 'org_hidden', 'compare' => 'NOT EXISTS' ), array( 'key' => 'org_hidden', 'value' => '1', 'compare' => '!=' ) );
		$mq        = array( $hidden_or );
		if ( class_exists( 'Shuffles_SSJ_Monetisation' ) && Shuffles_SSJ_Monetisation::enabled() ) {
			$mq = array( 'relation' => 'AND', $hidden_or, array( 'key' => 'org_listed', 'value' => '1' ) );
		}
		$q = new WP_Query( array( 'post_type' => 'sssj_org', 'post_status' => 'publish', 'posts_per_page' => $per, 'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => true, 'meta_query' => $mq ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		$saved = is_user_logged_in() ? array_map( 'intval', (array) get_user_meta( get_current_user_id(), '_sssj_saved_orgs', true ) ) : array();

		ob_start();
		echo '<div class="sssj sssj--swipe"><div class="sssj-panel">';
		echo '<h2 style="margin-top:0">' . esc_html( $a['title'] ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Swipe right (♥ or →) to save a provider to your shortlist, left (✕ or ←) to skip. Tap a card to view the full profile.', 'shuffles-social-services-jobs' ) . '</p>';
		echo '<div class="sssj-swipe" data-sssj-swipe>';
		if ( $q->have_posts() ) {
			echo '<div class="sssj-swipe__deck">';
			while ( $q->have_posts() ) {
				$q->the_post();
				$oid   = get_the_ID();
				$logo  = Shuffles_SSJ_Org::logo_url( $oid, 'medium' );
				$cat   = Shuffles_SSJ_Org::category_label( get_post_meta( $oid, 'org_category', true ) );
				$sub   = trim( (string) get_post_meta( $oid, 'location_suburb', true ) . ' ' . (string) get_post_meta( $oid, 'location_state', true ) );
				$is_s  = in_array( (int) $oid, $saved, true );
				echo '<article class="sssj-swipe__card' . ( $is_s ? ' is-saved' : '' ) . '" data-id="' . esc_attr( $oid ) . '" data-url="' . esc_url( get_permalink() ) . '" data-name="' . esc_attr( get_the_title() ) . '">';
				echo '<div class="sssj-swipe__stamp sssj-swipe__stamp--like">' . esc_html__( 'SAVED', 'shuffles-social-services-jobs' ) . '</div><div class="sssj-swipe__stamp sssj-swipe__stamp--nope">' . esc_html__( 'SKIP', 'shuffles-social-services-jobs' ) . '</div>';
				if ( $logo ) {
					echo '<div class="sssj-swipe__media" style="background-image:url(' . esc_url( $logo ) . ')"></div>';
				} else {
					echo '<div class="sssj-swipe__media sssj-swipe__media--initial"><span>' . esc_html( mb_substr( wp_strip_all_tags( get_the_title() ), 0, 1 ) ) . '</span></div>';
				}
				echo '<div class="sssj-swipe__body">';
				echo '<h3 class="sssj-swipe__name">' . esc_html( get_the_title() ) . ' ' . Shuffles_SSJ_Verification::tick_html( $oid, false ) . '</h3>'; // phpcs:ignore WordPress.Security.EscapeOutput
				echo '<div class="sssj-row" style="gap:6px;flex-wrap:wrap">';
				if ( $cat ) { echo '<span class="sssj-badge">' . esc_html( $cat ) . '</span>'; }
				echo Shuffles_SSJ_Org::ndis_badge_html( $oid ); // phpcs:ignore WordPress.Security.EscapeOutput
				if ( '' !== $sub ) { echo '<span class="sssj-badge">📍 ' . esc_html( $sub ) . '</span>'; }
				echo '</div>';
				echo '<p class="sssj-swipe__excerpt">' . esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 28 ) ) . '</p>';
				echo '<a class="sssj-btn sssj-btn--secondary sssj-btn--sm sssj-swipe__view" href="' . esc_url( get_permalink() ) . '" target="_blank" rel="noopener">' . esc_html__( 'View profile', 'shuffles-social-services-jobs' ) . '</a>';
				echo '</div></article>';
			}
			echo '</div>'; // deck
			echo '<div class="sssj-swipe__controls">'
				. '<button type="button" class="sssj-swipe__btn sssj-swipe__btn--skip" data-swipe="left" aria-label="' . esc_attr__( 'Skip', 'shuffles-social-services-jobs' ) . '">✕</button>'
				. '<button type="button" class="sssj-swipe__btn sssj-swipe__btn--undo" data-swipe="undo" aria-label="' . esc_attr__( 'Undo', 'shuffles-social-services-jobs' ) . '">↺</button>'
				. '<button type="button" class="sssj-swipe__btn sssj-swipe__btn--save" data-swipe="right" aria-label="' . esc_attr__( 'Save', 'shuffles-social-services-jobs' ) . '">♥</button>'
				. '</div>';
			echo '<div class="sssj-swipe__counter" data-swipe-counter></div>';
			echo '<div class="sssj-swipe__end" data-swipe-end hidden><h3>' . esc_html__( 'That’s everyone for now', 'shuffles-social-services-jobs' ) . '</h3><div class="sssj-swipe__shortlist" data-swipe-shortlist></div></div>';
		} else {
			echo '<p>' . esc_html__( 'No providers to show yet.', 'shuffles-social-services-jobs' ) . '</p>';
		}
		echo '</div></div></div>';
		wp_reset_postdata();
		return ob_get_clean();
	}

	/** AJAX: save a provider to the member's shortlist (user meta _sssj_saved_orgs). */
	public function ajax_swipe_save() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'msg' => __( 'Please log in to save providers to your shortlist.', 'shuffles-social-services-jobs' ) ) );
		}
		check_ajax_referer( 'sssj_swipe', 'nonce' );
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		if ( ! $id || 'sssj_org' !== get_post_type( $id ) ) {
			wp_send_json_error( array( 'msg' => 'bad_id' ) );
		}
		$uid   = get_current_user_id();
		$saved = array_values( array_unique( array_filter( array_map( 'intval', (array) get_user_meta( $uid, '_sssj_saved_orgs', true ) ) ) ) );
		if ( ! in_array( $id, $saved, true ) ) {
			$saved[] = $id;
			update_user_meta( $uid, '_sssj_saved_orgs', $saved );
		}
		wp_send_json_success( array( 'count' => count( $saved ) ) );
	}

	/**
	 * AJAX: re-run a board/directory query with the submitted filters and return just the results
	 * fragment (the [data-sssj-results] region), so the front end can swap the tiles without a reload.
	 */
	public function ajax_filter() {
		$this->last_points = array(); // reset so a board with no map returns no markers
		$board = isset( $_POST['board'] ) ? sanitize_key( wp_unslash( $_POST['board'] ) ) : 'job'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		// Mirror the submitted filter values into $_GET so the board methods (which read $_GET) work.
		$keys = array( 'sssj_q', 'sssj_loc', 'sssj_lat', 'sssj_lng', 'sssj_radius', 'sssj_paged', 'sssj_funding', 'sssj_sector', 'sssj_orgcat', 'sssj_size', 'sssj_structure', 'sssj_open', 'sssj_cat' );
		foreach ( $keys as $k ) {
			if ( isset( $_POST[ $k ] ) ) {
				$_GET[ $k ] = wp_unslash( $_POST[ $k ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput
			} else {
				unset( $_GET[ $k ] );
			}
		}
		switch ( $board ) {
			case 'tfn':    $html = $this->tfn_board( array() ); break;
			case 'abn':    $html = $this->abn_board( array() ); break;
			case 'vol':    $html = $this->volunteer_board( array() ); break;
			case 'worker': $html = $this->worker_directory( array() ); break;
			case 'org':    $html = $this->org_directory( array() ); break;
			case 'need':   $html = $this->need_board( array() ); break;
			case 'job':
			default:       $html = $this->board( array() ); break;
		}
		// Slice out the results region.
		$frag = '';
		if ( '' !== trim( (string) $html ) ) {
			$prev = libxml_use_internal_errors( true );
			$dom  = new DOMDocument();
			$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
			libxml_clear_errors();
			libxml_use_internal_errors( $prev );
			$xp   = new DOMXPath( $dom );
			$node = $xp->query( '//*[@data-sssj-results]' )->item( 0 );
			if ( $node ) {
				foreach ( $node->childNodes as $c ) {
					$frag .= $dom->saveHTML( $c );
				}
			}
		}
		wp_send_json_success( array( 'html' => $frag, 'points' => array_values( $this->last_points ) ) );
	}

	public function post_org_form( $atts ) {
		wp_enqueue_style( 'sssj' );
		wp_enqueue_script( 'sssj-spinner' );
		wp_enqueue_script( 'sssj-ndis-scan' );
		wp_enqueue_script( 'sssj-form-enhance' );
		$this->enqueue_maps();
		wp_enqueue_script( 'sssj-autofill', SHUFFLES_SSJ_URL . 'public/assets/js/sssj-autofill.js', array( 'sssj-spinner' ), SHUFFLES_SSJ_VERSION, true );
		wp_localize_script( 'sssj-autofill', 'SSJ_Autofill', array( 'ajax' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'sssj_autofill' ) ) );
		ob_start();
		$this->load_template( 'post-org-form.php', array() );
		return ob_get_clean();
	}

	/** Worker credential manager: list + add (with secure evidence upload) + delete. */
	public function credentials_panel( $atts ) {
		wp_enqueue_style( 'sssj' );
		ob_start();
		$this->load_template( 'credentials-panel.php', array() );
		return ob_get_clean();
	}

	public function resumes_panel( $atts ) {
		wp_enqueue_style( 'sssj' );
		wp_enqueue_script( 'sssj-spinner' );
		ob_start();
		$this->load_template( 'resumes-panel.php', array() );
		return ob_get_clean();
	}

	/**
	 * Shared Culture + Language multi-select fields (typeahead pills). Used by job/worker/need forms.
	 *
	 * @param array $culture_ids  Pre-selected sssjt_culture term ids.
	 * @param array $language_ids Pre-selected sssjt_language term ids.
	 */
	public static function culture_language_fields( $culture_ids = array(), $language_ids = array() ) {
		$culture_ids  = array_map( 'intval', (array) $culture_ids );
		$language_ids = array_map( 'intval', (array) $language_ids );
		$render = function ( $tax, $name, $label, $selected, $ph ) {
			$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false ) );
			echo '<div class="sssj-field"><label for="sssj-f-' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label>';
			echo '<select class="sssj-select" id="sssj-f-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '[]" multiple data-placeholder="' . esc_attr( $ph ) . '">';
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $t ) {
					echo '<option value="' . esc_attr( $t->term_id ) . '" ' . ( in_array( (int) $t->term_id, $selected, true ) ? 'selected' : '' ) . '>' . esc_html( $t->name ) . '</option>';
				}
			}
			echo '</select></div>';
		};
		echo '<div class="sssj-row">';
		$render( 'sssjt_culture', 'culture', __( 'Cultural / community focus', 'shuffles-social-services-jobs' ), $culture_ids, __( 'Search and add…', 'shuffles-social-services-jobs' ) );
		$render( 'sssjt_language', 'language', __( 'Languages spoken', 'shuffles-social-services-jobs' ), $language_ids, __( 'Search and add…', 'shuffles-social-services-jobs' ) );
		echo '</div>';
	}

	/**
	 * "Use my location" button — browser geolocation → radius search. Placed next to the location field.
	 * Carries its own translated status strings as data-attributes for sssj-filters.js.
	 */
	public static function location_button() {
		printf(
			'<button type="button" class="sssj-btn sssj-btn--ghost sssj-btn--sm sssj-here" data-sssj-here data-i18n="use_my_location" data-locating="%s" data-denied="%s" data-unsupported="%s" data-mylocation="%s">%s</button>',
			esc_attr__( 'Locating…', 'shuffles-social-services-jobs' ),
			esc_attr__( 'Could not get your location. Please allow location access or type a suburb.', 'shuffles-social-services-jobs' ),
			esc_attr__( 'Location is not available in this browser.', 'shuffles-social-services-jobs' ),
			esc_attr__( '📍 My location', 'shuffles-social-services-jobs' ),
			esc_html__( '📍 Use my location', 'shuffles-social-services-jobs' )
		);
	}

	/**
	 * Funding tick-box filter chips (NDIS / Aged Care / DVA / …) for the jobs board.
	 * Data-driven from the Funding Sources taxonomy; OR within the ticked set.
	 */
	public static function funding_chips() {
		$terms = get_terms( array( 'taxonomy' => 'sssjt_funding_source', 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) || ! $terms ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$cur = isset( $_GET['sssj_funding'] ) ? array_map( 'sanitize_title', (array) wp_unslash( $_GET['sssj_funding'] ) ) : array();
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		echo '<span class="sssj-fundingchips" role="group" aria-label="' . esc_attr__( 'Funding', 'shuffles-social-services-jobs' ) . '">';
		foreach ( $terms as $t ) {
			$on = in_array( $t->slug, $cur, true );
			echo '<label class="sssj-chip sssj-chip--funding ' . ( $on ? 'is-on' : '' ) . '"><input type="checkbox" name="sssj_funding[]" value="' . esc_attr( $t->slug ) . '" ' . checked( $on, true, false ) . ' /> ' . esc_html( $t->name ) . '</label>';
		}
		echo '</span>';
	}

	/**
	 * Filter actions: a "Clear all" button + a no-JS "Filter" submit fallback.
	 * With dynamic filters the form auto-applies, so no Filter button is needed when JS runs.
	 */
	public static function filter_actions() {
		echo '<button type="button" class="sssj-btn sssj-btn--ghost sssj-btn--sm sssj-clear" data-sssj-clear data-i18n="clear_all">' . esc_html__( 'Clear all', 'shuffles-social-services-jobs' ) . '</button>';
		echo '<noscript><button class="sssj-btn sssj-btn--primary" type="submit">' . esc_html__( 'Filter', 'shuffles-social-services-jobs' ) . '</button></noscript>';
	}

	/**
	 * A highlighted "X km away" pill for a result card, when the search has a centre. '' otherwise.
	 * For orgs, pass $is_org=true to use the nearest of the org's locations.
	 *
	 * @param int        $post_id Result post.
	 * @param array|null $center  [ 'lat' => float, 'lng' => float ] | null.
	 * @param bool       $is_org  Use Org::nearest_km (multi-location) instead of primary coords.
	 */
	public static function distance_pill( $post_id, $center, $is_org = false ) {
		if ( empty( $center['lat'] ) || empty( $center['lng'] ) || ! class_exists( 'Shuffles_SSJ_Geo' ) ) {
			return '';
		}
		if ( $is_org && class_exists( 'Shuffles_SSJ_Org' ) ) {
			$d = Shuffles_SSJ_Org::nearest_km( $post_id, (float) $center['lat'], (float) $center['lng'] );
		} else {
			$lat = (float) get_post_meta( $post_id, 'location_lat', true );
			$lng = (float) get_post_meta( $post_id, 'location_lng', true );
			$d   = ( $lat && $lng ) ? Shuffles_SSJ_Geo::distance_km( (float) $center['lat'], (float) $center['lng'], $lat, $lng ) : null;
		}
		if ( null === $d ) {
			return '';
		}
		$km = max( 1, (int) round( $d ) );
		/* translators: %d: distance in km */
		return '<span class="sssj-badge sssj-dist">📍 ' . esc_html( sprintf( _n( '%d km away', '%d km away', $km, 'shuffles-social-services-jobs' ), $km ) ) . '</span>';
	}

	/**
	 * "Things to know" content per directory type — single source of truth for the read-me panels.
	 *
	 * @return array { title, intro, points[] } | null
	 */
	public static function readme( $type ) {
		$map = array(
			'jobs' => array(
				'title'  => __( 'About this jobs board', 'shuffles-social-services-jobs' ),
				'intro'  => __( 'Roles posted by organisations and sole traders across disability, aged care and social services.', 'shuffles-social-services-jobs' ),
				'points' => array(
					__( 'TFN roles are employee positions (wages, tax withheld). ABN roles are contractor / sole-trader work you invoice for.', 'shuffles-social-services-jobs' ),
					__( 'Use the search, category, location and radius filters — results update as you change them.', 'shuffles-social-services-jobs' ),
					__( 'Open a job to see full details and apply. First contact is made through the site.', 'shuffles-social-services-jobs' ),
				),
			),
			'workers' => array(
				'title'  => __( 'About finding workers', 'shuffles-social-services-jobs' ),
				'intro'  => __( 'Available workers and contractors who have opted in to be found.', 'shuffles-social-services-jobs' ),
				'points' => array(
					__( 'Filter by service, “available now”, and location / radius. Tick “Use my location” to find people near you.', 'shuffles-social-services-jobs' ),
					__( 'A ✓ Verified badge means an admin has confirmed that worker’s credentials.', 'shuffles-social-services-jobs' ),
					__( 'Some profiles are visible to logged-in members only — log in to see everyone.', 'shuffles-social-services-jobs' ),
				),
			),
			'orgs' => array(
				'title'  => __( 'About organisations', 'shuffles-social-services-jobs' ),
				'intro'  => __( 'Provider and employer profiles — browse open roles by company.', 'shuffles-social-services-jobs' ),
				'points' => array(
					__( 'Filter by sector, funding, location / radius, and “only with open placements”.', 'shuffles-social-services-jobs' ),
					__( 'Each card shows current open jobs and people placed all-time.', 'shuffles-social-services-jobs' ),
					__( 'Open a profile to see all locations and its open positions.', 'shuffles-social-services-jobs' ),
				),
			),
			'needs' => array(
				'title'  => __( 'About participant requests', 'shuffles-social-services-jobs' ),
				'intro'  => __( 'Support requests from participants or their nominees. Privacy is protected at all times.', 'shuffles-social-services-jobs' ),
				'points' => array(
					__( 'Requests show a pseudonym and a suburb only — never a name or contact details.', 'shuffles-social-services-jobs' ),
					__( 'You need a recorded ABN to respond. First contact is made through the site.', 'shuffles-social-services-jobs' ),
					__( 'Be respectful and professional — these are vulnerable members of the community.', 'shuffles-social-services-jobs' ),
				),
			),
		);
		return isset( $map[ $type ] ) ? $map[ $type ] : null;
	}

	/** The logged-in member's "willing to travel" radius (from their worker profile), 0 if none. */
	public static function default_travel_radius() {
		if ( ! is_user_logged_in() ) {
			return 0;
		}
		$w = get_posts( array( 'post_type' => 'sssj_worker', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => true, 'meta_key' => 'worker_user_id', 'meta_value' => get_current_user_id() ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		return $w ? (int) get_post_meta( $w[0], 'travel_radius_km', true ) : 0;
	}

	/** Render the collapsible "Things to know" read-me for a directory (native <details>, no JS). */
	public static function render_readme( $type ) {
		$r = self::readme( $type );
		if ( ! $r ) {
			return;
		}
		echo '<details class="sssj-readme"><summary class="sssj-readme__summary"><span class="sssj-readme__icon" aria-hidden="true">ℹ︎</span> ' . esc_html__( 'Read me — things to know', 'shuffles-social-services-jobs' ) . '</summary>';
		echo '<div class="sssj-readme__body"><strong>' . esc_html( $r['title'] ) . '</strong>';
		echo '<p>' . esc_html( $r['intro'] ) . '</p><ul>';
		foreach ( $r['points'] as $p ) {
			echo '<li>' . esc_html( $p ) . '</li>';
		}
		echo '</ul></div></details>';
	}

	/** Testing worksheet (the tester checklist). */
	public function tests_panel( $atts ) {
		wp_enqueue_style( 'sssj' );
		wp_enqueue_script( 'sssj-tests', SHUFFLES_SSJ_URL . 'public/assets/js/sssj-tests.js', array(), SHUFFLES_SSJ_VERSION, true );
		return Shuffles_SSJ_Tests::render( is_array( $atts ) ? $atts : array() );
	}

	/** Guides (collapsible how-to panels). */
	public function guides_panel( $atts ) {
		wp_enqueue_style( 'sssj' );
		wp_enqueue_script( 'sssj-guides', SHUFFLES_SSJ_URL . 'public/assets/js/sssj-guides.js', array(), SHUFFLES_SSJ_VERSION, true );
		return Shuffles_SSJ_Guides::render( is_array( $atts ) ? $atts : array() );
	}

	public function workflows_panel( $atts ) {
		wp_enqueue_style( 'sssj' );
		// Reuses the Guides collapse JS (same data-sssj-guides / data-guide-toggle hooks).
		wp_enqueue_script( 'sssj-guides', SHUFFLES_SSJ_URL . 'public/assets/js/sssj-guides.js', array(), SHUFFLES_SSJ_VERSION, true );
		return Shuffles_SSJ_Workflows::render( is_array( $atts ) ? $atts : array() );
	}

	public function policies_panel( $atts ) {
		wp_enqueue_style( 'sssj' );
		// Reuses the Guides collapse JS (same data-sssj-guides / data-guide-toggle hooks).
		wp_enqueue_script( 'sssj-guides', SHUFFLES_SSJ_URL . 'public/assets/js/sssj-guides.js', array(), SHUFFLES_SSJ_VERSION, true );
		return Shuffles_SSJ_Policies::render( is_array( $atts ) ? $atts : array() );
	}

	/** [sssj_create_asset] — the shareable-asset wizard (Phase 1: worker / sole-trader résumé). */
	public function create_asset( $atts ) {
		if ( class_exists( 'Shuffles_SSJ_Assets' ) && ! Shuffles_SSJ_Assets::enabled() ) {
			return '';
		}
		wp_enqueue_style( 'sssj' );
		wp_enqueue_style( 'sssj-assets', SHUFFLES_SSJ_URL . 'public/assets/css/sssj-assets.css', array( 'sssj' ), SHUFFLES_SSJ_VERSION );
		wp_enqueue_script( 'sssj-assets', SHUFFLES_SSJ_URL . 'public/assets/js/sssj-assets.js', array(), SHUFFLES_SSJ_VERSION, true );
		$a = shortcode_atts( array( 'asset' => '' ), is_array( $atts ) ? $atts : array(), 'sssj_create_asset' );
		ob_start();
		$this->load_template( 'create-asset.php', array( 'atts' => $a ) );
		return ob_get_clean();
	}

	/* --- Navigation menu (login-aware) --- */

	/** Resolve a page URL from its setting; fall back to finding the shortcode's page (cached). */
	/**
	 * Resolve a feature page URL ('' if none). Order of preference:
	 *   1. The configured page (settings key) IF it actually contains the shortcode (or none given).
	 *   2. Otherwise the page that DOES contain the shortcode — this self-heals a stale mapping, e.g.
	 *      a "Jobs" item still pointing at a legacy board page, or "My dashboard" pointing at the old
	 *      my-listings page, repoints automatically to the page running our shortcode.
	 *   3. As a last resort, the configured page even without the literal shortcode (e.g. an Elementor
	 *      widget renders it), so an intentionally-mapped builder page still works.
	 * Static so templates can call it without an instance.
	 */
	public static function page_link( $key, $shortcode ) {
		$opts = get_option( 'shuffles_ssj_settings', array() );
		$id   = is_array( $opts ) && isset( $opts[ $key ] ) ? (int) $opts[ $key ] : 0;
		$tag  = trim( (string) $shortcode, '[]' );

		$cfg_ok = ( $id && 'publish' === get_post_status( $id ) );
		if ( $cfg_ok && '' === $tag ) {
			return (string) get_permalink( $id );
		}
		if ( $cfg_ok ) {
			$cfg = get_post( $id );
			if ( $cfg && has_shortcode( (string) $cfg->post_content, $tag ) ) {
				return (string) get_permalink( $id ); // configured page is valid for this shortcode
			}
		}

		// Find the page that actually contains the shortcode (cached).
		$found = 0;
		if ( '' !== $tag ) {
			$cached = get_transient( 'sssj_menu_pg_' . $tag );
			if ( false === $cached ) {
				$cached = 0;
				$pages  = get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => 50, 'fields' => 'ids', 's' => $tag, 'no_found_rows' => true ) );
				foreach ( $pages as $pid ) {
					$p = get_post( $pid );
					if ( $p && has_shortcode( (string) $p->post_content, $tag ) ) {
						$cached = (int) $pid;
						break;
					}
				}
				set_transient( 'sssj_menu_pg_' . $tag, $cached, HOUR_IN_SECONDS );
			}
			$found = (int) $cached;
		}
		if ( $found ) {
			return (string) get_permalink( $found );
		}

		// Last resort: the configured page even if the literal tag isn't in its content.
		return $cfg_ok ? (string) get_permalink( $id ) : '';
	}

	public function resolve_page( $key, $shortcode ) {
		return self::page_link( $key, $shortcode );
	}

	/**
	 * "Open to…" badges for a job or organisation — visa sponsorship, work placements, volunteers.
	 * Reads the offers_sponsorship / accepts_placements / welcomes_volunteers meta flags. '' if none.
	 */
	public static function openness_badges( $id ) {
		$id  = (int) $id;
		$out = '';
		if ( get_post_meta( $id, 'offers_sponsorship', true ) ) {
			$out .= '<span class="sssj-badge sssj-badge--open" title="' . esc_attr__( 'Open to overseas applicants — visa sponsorship available', 'shuffles-social-services-jobs' ) . '">✈️ ' . esc_html__( 'Visa sponsorship', 'shuffles-social-services-jobs' ) . '</span> ';
		}
		if ( get_post_meta( $id, 'accepts_placements', true ) ) {
			$out .= '<span class="sssj-badge sssj-badge--open" title="' . esc_attr__( 'Accepts work-placement / student-placement enquiries', 'shuffles-social-services-jobs' ) . '">🎓 ' . esc_html__( 'Work placements', 'shuffles-social-services-jobs' ) . '</span> ';
		}
		if ( get_post_meta( $id, 'welcomes_volunteers', true ) ) {
			$out .= '<span class="sssj-badge sssj-badge--open" title="' . esc_attr__( 'Welcomes volunteer enquiries', 'shuffles-social-services-jobs' ) . '">🤝 ' . esc_html__( 'Volunteers welcome', 'shuffles-social-services-jobs' ) . '</span> ';
		}
		return $out;
	}

	private function add_nav_item( &$items, $label, $url, $cta = false, $roles = array() ) {
		if ( '' !== (string) $url ) {
			$items[] = array( 'label' => $label, 'url' => $url, 'cta' => (bool) $cta, 'roles' => (array) $roles );
		}
	}

	/**
	 * Build the navigation items for the current visitor (login + capability aware). Filterable
	 * via `shuffles_ssj_menu_items`. Items whose destination page is not configured are omitted.
	 *
	 * @return array of [ label, url, cta ]
	 */
	public function menu_items() {
		$logged_in = is_user_logged_in();
		$items     = array();

		// Browse — everyone.
		$this->add_nav_item( $items, __( 'Home', 'shuffles-social-services-jobs' ), home_url( '/' ) );
		$this->add_nav_item( $items, __( 'Jobs', 'shuffles-social-services-jobs' ), $this->resolve_page( 'page_job_board', '[sssj_job_board]' ) );
		$this->add_nav_item( $items, __( 'Find a worker', 'shuffles-social-services-jobs' ), $this->resolve_page( 'page_worker_directory', '[sssj_worker_directory]' ) );
		$this->add_nav_item( $items, __( 'Organisations', 'shuffles-social-services-jobs' ), $this->resolve_page( 'page_org_directory', '[sssj_org_directory]' ) );
		$this->add_nav_item( $items, __( 'How it works', 'shuffles-social-services-jobs' ), $this->resolve_page( 'page_workflows', '[sssj_workflows]' ) );
		$this->add_nav_item( $items, __( 'Policies', 'shuffles-social-services-jobs' ), $this->resolve_page( 'page_policies', '[sssj_policies]' ) );

		if ( $logged_in ) {
			$this->add_nav_item( $items, __( 'Participants seeking workers', 'shuffles-social-services-jobs' ), $this->resolve_page( 'page_need_board', '[sssj_need_board]' ), false, array( 'contractor', 'provider' ) );
			if ( current_user_can( 'sssj_post_job' ) || current_user_can( 'manage_options' ) ) {
				$this->add_nav_item( $items, __( 'Post a job', 'shuffles-social-services-jobs' ), $this->resolve_page( 'page_post_job', '[sssj_post_job]' ), false, array( 'employer', 'provider', 'supplier', 'participant', 'representative' ) );
			}
			if ( current_user_can( 'sssj_post_worker' ) || current_user_can( 'manage_options' ) ) {
				$this->add_nav_item( $items, __( 'My credentials', 'shuffles-social-services-jobs' ), $this->resolve_page( 'page_credentials', '[sssj_credentials]' ), false, array( 'contractor', 'candidate' ) );
			}
			if ( current_user_can( 'sssj_post_need' ) || current_user_can( 'manage_options' ) ) {
				$this->add_nav_item( $items, __( 'Request support', 'shuffles-social-services-jobs' ), $this->resolve_page( 'page_post_need', '[sssj_post_need]' ), false, array( 'participant', 'representative' ) );
			}
			$this->add_nav_item( $items, __( 'Edit my profile', 'shuffles-social-services-jobs' ), $this->resolve_page( 'page_post_worker', '[sssj_post_worker]' ), false, array( 'contractor', 'candidate' ) );
			$this->add_nav_item( $items, __( 'Messages', 'shuffles-social-services-jobs' ), $this->resolve_page( 'page_messages', '[sssj_messages]' ) );
			$dash = $this->resolve_page( 'page_my_listings', '[sssj_dashboard]' );
			$dash = $dash ? $dash : $this->resolve_page( 'page_my_listings', '[sssj_my_listings]' );
			if ( '' !== (string) $dash ) {
				$dash_item = array( 'label' => __( 'My dashboard', 'shuffles-social-services-jobs' ), 'url' => $dash, 'cta' => false );
				// Admin-only sub-level: jump to the plugin settings (uses the literal page slug —
				// the admin class isn't loaded on the front end where this menu renders).
				if ( current_user_can( 'manage_options' ) ) {
					$dash_item['children'] = array(
						array( 'label' => __( 'Settings', 'shuffles-social-services-jobs' ), 'url' => admin_url( 'admin.php?page=shuffles-ssj' ), 'cta' => false ),
					);
				}
				$items[] = $dash_item;
			}
			$items[] = array( 'label' => __( 'Log out', 'shuffles-social-services-jobs' ), 'url' => wp_logout_url( home_url( '/' ) ), 'cta' => false );
		} else {
			$here    = esc_url_raw( home_url( add_query_arg( array() ) ) );
			$items[] = array( 'label' => __( 'Log in', 'shuffles-social-services-jobs' ), 'url' => wp_login_url( $here ), 'cta' => false );
			if ( get_option( 'users_can_register' ) ) {
				$items[] = array( 'label' => __( 'Register', 'shuffles-social-services-jobs' ), 'url' => wp_registration_url(), 'cta' => true );
			}
		}

		return apply_filters( 'shuffles_ssj_menu_items', $items, $logged_in );
	}

	/**
	 * [sssj_menu] — a responsive navigation bar that adapts to logged-in vs logged-out visitors.
	 * Atts: title (optional brand text), class (optional extra CSS class).
	 */
	public function menu( $atts ) {
		$atts = shortcode_atts( array( 'title' => '', 'class' => '' ), is_array( $atts ) ? $atts : array(), 'sssj_menu' );
		wp_enqueue_style( 'sssj' );
		$items = $this->menu_items();
		if ( empty( $items ) ) {
			return '';
		}
		// Focus by the member's primary role: items for other roles move under a "See all" dropdown.
		$primary = ( is_user_logged_in() && class_exists( 'Shuffles_SSJ_Roles' ) ) ? Shuffles_SSJ_Roles::primary_role( get_current_user_id() ) : '';
		if ( $primary ) {
			$shown = array();
			$more  = array();
			foreach ( $items as $it ) {
				$r = ! empty( $it['roles'] ) ? (array) $it['roles'] : array();
				if ( $r && ! in_array( $primary, $r, true ) ) {
					$more[] = $it;
				} else {
					$shown[] = $it;
				}
			}
			if ( $more ) {
				$shown[] = array( 'label' => __( 'See all', 'shuffles-social-services-jobs' ), 'url' => '#', 'cta' => false, 'children' => $more );
			}
			$items = $shown;
		}
		$cur = untrailingslashit( (string) home_url( add_query_arg( array() ) ) );
		ob_start();
		echo '<nav class="sssj sssj-nav ' . esc_attr( $atts['class'] ) . '" aria-label="' . esc_attr__( 'Jobs and engagements navigation', 'shuffles-social-services-jobs' ) . '">';
		if ( '' !== $atts['title'] ) {
			echo '<span class="sssj-nav__brand">' . esc_html( $atts['title'] ) . '</span>';
		}
		echo '<ul class="sssj-nav__list">';
		foreach ( $items as $it ) {
			$is_cur   = ( untrailingslashit( (string) $it['url'] ) === $cur );
			$children = ( ! empty( $it['children'] ) && is_array( $it['children'] ) ) ? $it['children'] : array();
			$cls      = 'sssj-nav__item' . ( ! empty( $it['cta'] ) ? ' sssj-nav__item--cta' : '' ) . ( $children ? ' sssj-nav__item--has-sub' : '' );
			echo '<li class="' . esc_attr( $cls ) . '">';
			echo '<a href="' . esc_url( $it['url'] ) . '"' . ( $is_cur ? ' aria-current="page"' : '' ) . ( $children ? ' aria-haspopup="true"' : '' ) . '>' . esc_html( $it['label'] ) . ( $children ? ' <span class="sssj-nav__caret" aria-hidden="true">▾</span>' : '' ) . '</a>';
			if ( $children ) {
				echo '<ul class="sssj-nav__sub">';
				foreach ( $children as $child ) {
					echo '<li class="sssj-nav__subitem"><a href="' . esc_url( $child['url'] ) . '">' . esc_html( $child['label'] ) . '</a></li>';
				}
				echo '</ul>';
			}
			echo '</li>';
		}
		echo '</ul></nav>';
		return ob_get_clean();
	}

	/** Append the org's locations + its open jobs ("browse by company") to a single org page. */
	public function maybe_org_panel( $content ) {
		if ( is_singular( 'sssj_org' ) && in_the_loop() && is_main_query() ) {
			wp_enqueue_style( 'sssj' );
			ob_start();
			$this->load_template( 'org-single-panel.php', array( 'org_id' => get_the_ID() ) );
			return $content . ob_get_clean();
		}
		return $content;
	}

	/**
	 * Append a Google map of the job's suburb/town on a single job page (keyless Google Maps embed,
	 * so it works without an API key). Suburb-level only — no exact address is shown.
	 */
	public function maybe_job_map( $content ) {
		if ( ! ( is_singular( 'sssj_job' ) && in_the_loop() && is_main_query() ) ) {
			return $content;
		}
		$id    = get_the_ID();
		$sub   = (string) get_post_meta( $id, 'location_suburb', true );
		$state = (string) get_post_meta( $id, 'location_state', true );
		$lat   = (float) get_post_meta( $id, 'location_lat', true );
		$lng   = (float) get_post_meta( $id, 'location_lng', true );
		if ( '' === $sub && ! ( $lat && $lng ) ) {
			return $content; // no location to show
		}
		$query = ( $lat && $lng ) ? ( $lat . ',' . $lng ) : trim( $sub . ' ' . $state . ' Australia' );
		$src   = 'https://maps.google.com/maps?q=' . rawurlencode( $query ) . '&z=12&output=embed';
		$label = trim( $sub . ' ' . $state );

		wp_enqueue_style( 'sssj' );
		ob_start();
		?>
		<div class="sssj sssj--jobmap">
			<div class="sssj-panel">
				<h3 style="margin-top:0">📍 <?php echo esc_html( '' !== $label ? $label : __( 'Location', 'shuffles-social-services-jobs' ) ); ?></h3>
				<div class="sssj-jobmap">
					<iframe title="<?php echo esc_attr( sprintf( __( 'Map of %s', 'shuffles-social-services-jobs' ), $label ) ); ?>" src="<?php echo esc_url( $src ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
				</div>
				<p class="description"><?php esc_html_e( 'Approximate location — shown at suburb level.', 'shuffles-social-services-jobs' ); ?></p>
			</div>
		</div>
		<?php
		return $content . ob_get_clean();
	}

	/** Best-matched workers appended to a single job page. */
	public function maybe_job_matches( $content ) {
		if ( is_singular( 'sssj_job' ) && in_the_loop() && is_main_query() && class_exists( 'Shuffles_SSJ_Matcher' ) ) {
			wp_enqueue_style( 'sssj' );
			$html = Shuffles_SSJ_Matcher::render_worker_matches( get_the_ID() );
			if ( $html ) {
				return $content . $html;
			}
		}
		return $content;
	}

	/** Best-matched jobs appended to a single worker profile. */
	public function maybe_worker_matches( $content ) {
		if ( is_singular( 'sssj_worker' ) && in_the_loop() && is_main_query() && class_exists( 'Shuffles_SSJ_Matcher' ) ) {
			wp_enqueue_style( 'sssj' );
			$html = Shuffles_SSJ_Matcher::render_job_matches( get_the_ID() );
			if ( $html ) {
				return $content . $html;
			}
		}
		return $content;
	}

	/** [sssj_matches] — jobs matched to the logged-in member's own worker profile (dashboard widget). */
	public function matches_panel( $atts ) {
		wp_enqueue_style( 'sssj' );
		if ( ! is_user_logged_in() ) {
			return '<div class="sssj"><div class="sssj-panel"><p>' . esc_html__( 'Log in to see jobs matched to your profile.', 'shuffles-social-services-jobs' ) . '</p></div></div>';
		}
		$found = get_posts( array(
			'post_type'      => 'sssj_worker',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_key'       => 'worker_user_id',
			'meta_value'     => get_current_user_id(),
		) );
		if ( ! $found ) {
			return '<div class="sssj"><div class="sssj-panel"><p>' . esc_html__( 'Create your worker profile to see matched jobs.', 'shuffles-social-services-jobs' ) . '</p></div></div>';
		}
		$html = class_exists( 'Shuffles_SSJ_Matcher' ) ? Shuffles_SSJ_Matcher::render_job_matches( (int) $found[0], __( 'Jobs matched to you', 'shuffles-social-services-jobs' ) ) : '';
		return $html ? $html : '<div class="sssj"><div class="sssj-panel"><p>' . esc_html__( 'No matched jobs yet — add services and a location to your profile.', 'shuffles-social-services-jobs' ) . '</p></div></div>';
	}

	/** Render the worker's details (services, rate, location, credentials, photos) on their profile page. */
	public function maybe_worker_panel( $content ) {
		if ( is_singular( 'sssj_worker' ) && in_the_loop() && is_main_query() ) {
			wp_enqueue_style( 'sssj' );
			ob_start();
			$this->load_template( 'worker-single-panel.php', array( 'worker_id' => get_the_ID() ) );
			return $content . ob_get_clean();
		}
		return $content;
	}

	/** Append ratings & reviews to a single worker (contractor) profile. */
	public function maybe_worker_reviews( $content ) {
		if ( is_singular( 'sssj_worker' ) && in_the_loop() && is_main_query() && class_exists( 'Shuffles_SSJ_Reviews' ) ) {
			wp_enqueue_style( 'sssj' );
			$html = Shuffles_SSJ_Reviews::render_for( 'worker', get_the_ID() );
			if ( $html ) {
				return $content . $html;
			}
		}
		return $content;
	}

	/** Append ratings & reviews to a single organisation (provider) profile. */
	public function maybe_org_reviews( $content ) {
		if ( is_singular( 'sssj_org' ) && in_the_loop() && is_main_query() && class_exists( 'Shuffles_SSJ_Reviews' ) ) {
			wp_enqueue_style( 'sssj' );
			$html = Shuffles_SSJ_Reviews::render_for( 'org', get_the_ID() );
			if ( $html ) {
				return $content . $html;
			}
		}
		return $content;
	}

	/** Append a promotional video (YouTube / Vimeo) to a single worker, org or job when one is set. */
	public function maybe_listing_video( $content ) {
		if ( is_singular( array( 'sssj_worker', 'sssj_org', 'sssj_job' ) ) && in_the_loop() && is_main_query() && class_exists( 'Shuffles_SSJ_Media' ) ) {
			$embed = Shuffles_SSJ_Media::video_embed( (string) get_post_meta( get_the_ID(), 'video_url', true ) );
			if ( $embed ) {
				wp_enqueue_style( 'sssj' );
				$label = is_singular( 'sssj_org' ) ? __( 'Watch: about us', 'shuffles-social-services-jobs' ) : __( 'Watch', 'shuffles-social-services-jobs' );
				return $content . '<div class="sssj sssj--videoblock"><div class="sssj-panel"><h3 style="margin-top:0">🎬 ' . esc_html( $label ) . '</h3>' . $embed . '</div></div>';
			}
		}
		return $content;
	}

	/** Optional Advanced Ads slot below a single job/worker/org listing (empty unless active + mapped). */
	public function maybe_listing_ad( $content ) {
		if ( is_singular( array( 'sssj_job', 'sssj_worker', 'sssj_org' ) ) && in_the_loop() && is_main_query() && class_exists( 'Shuffles_SSJ_Ads' ) ) {
			$ad = Shuffles_SSJ_Ads::slot( 'single' );
			if ( $ad ) {
				return $content . '<div class="sssj sssj--ad-slot">' . $ad . '</div>';
			}
		}
		return $content;
	}

	/* --- Select2-style pill pickers (Tom Select), site-wide where plugin content renders --- */

	/** Heuristic: does this request render plugin content (a plugin CPT singular or an sssj_ shortcode)? */
	public function has_plugin_content() {
		if ( is_singular( array( 'sssj_job', 'sssj_worker', 'sssj_need', 'sssj_org' ) ) ) {
			return true;
		}
		$post = get_post();
		$hit  = ( $post instanceof WP_Post && false !== strpos( (string) $post->post_content, '[sssj_' ) );
		return (bool) apply_filters( 'shuffles_ssj_load_select_enhancer', $hit );
	}

	/** Enqueue Tom Select + the initializer so every plugin <select> becomes a searchable pill picker. */
	public function enqueue_select_assets() {
		if ( ! $this->has_plugin_content() ) {
			return;
		}
		wp_enqueue_style( 'sssj' );
		wp_enqueue_style( 'sssj-tomselect', SHUFFLES_SSJ_URL . 'public/assets/vendor/tom-select/tom-select.min.css', array(), '2.3.1' );
		wp_enqueue_script( 'sssj-tomselect', SHUFFLES_SSJ_URL . 'public/assets/vendor/tom-select/tom-select.complete.min.js', array(), '2.3.1', true );
		wp_enqueue_script( 'sssj-select', SHUFFLES_SSJ_URL . 'public/assets/js/sssj-select.js', array( 'sssj-tomselect' ), SHUFFLES_SSJ_VERSION, true );
	}

	/* --- Template loader (theme-overridable) --- */

	public function load_template( $file, $ctx = array() ) {
		$override = locate_template( 'shuffles-jobs/' . $file );
		$path     = $override ? $override : SHUFFLES_SSJ_DIR . 'templates/' . $file;
		if ( file_exists( $path ) ) {
			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- controlled, internal context only.
			extract( $ctx, EXTR_SKIP );
			include $path;
		}
	}
}
