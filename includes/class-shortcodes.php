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

	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_select_assets' ), 20 );
		add_shortcode( 'sssj_job_board', array( $this, 'board' ) );
		add_shortcode( 'sssj_tfn_board', array( $this, 'tfn_board' ) );
		add_shortcode( 'sssj_abn_board', array( $this, 'abn_board' ) );
		add_shortcode( 'sssj_post_job', array( $this, 'post_job_form' ) );
		add_shortcode( 'sssj_worker_directory', array( $this, 'worker_directory' ) );
		add_shortcode( 'sssj_post_worker', array( $this, 'post_worker_form' ) );
		add_shortcode( 'sssj_need_board', array( $this, 'need_board' ) );
		add_shortcode( 'sssj_post_need', array( $this, 'post_need_form' ) );
		add_shortcode( 'sssj_my_listings', array( $this, 'my_listings' ) );
		add_shortcode( 'sssj_messages', array( $this, 'messages' ) );
		add_shortcode( 'sssj_org_directory', array( $this, 'org_directory' ) );
		add_shortcode( 'sssj_post_org', array( $this, 'post_org_form' ) );
		add_shortcode( 'sssj_credentials', array( $this, 'credentials_panel' ) );
		add_shortcode( 'sssj_menu', array( $this, 'menu' ) );
		add_shortcode( 'sssj_tests', array( $this, 'tests_panel' ) );
		add_filter( 'the_content', array( $this, 'maybe_apply_panel' ) );
		add_filter( 'the_content', array( $this, 'maybe_org_panel' ) );
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
		return array(
			array(
				'tag'    => 'sssj_menu',
				'title'  => __( 'Navigation menu (login-aware)', 'shuffles-social-services-jobs' ),
				'what'   => __( 'A ready-made navigation bar that adapts to the visitor. Logged-OUT: Jobs, Find a worker, Organisations, Log in (and Register if open). Logged-IN: Participant requests, Messages, My dashboard, Log out — plus Post a job / My credentials / Request support shown only when the account can use them. Links resolve automatically from your configured pages (Boards tab), or by finding the page that contains each shortcode.', 'shuffles-social-services-jobs' ),
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
				'tag'    => 'sssj_post_org',
				'title'  => __( 'Create / edit organisation profile', 'shuffles-social-services-jobs' ),
				'what'   => __( 'Lets an advertiser create or update their organisation profile (one per user): name, description, ABN, website, phone, type, primary location and additional locations.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A "Create your organisation profile" page.', 'shuffles-social-services-jobs' ),
				'access' => 'advertisers',
				'group'  => __( 'Organisations', 'shuffles-social-services-jobs' ),
				'atts'   => array(),
			),
			array(
				'tag'    => 'sssj_my_listings',
				'title'  => __( 'Member dashboard', 'shuffles-social-services-jobs' ),
				'what'   => __( 'A personal dashboard: the member’s own applications, their job ads with applicants (and a status control), and their participant requests with responses.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A "My dashboard" / "My listings" page.', 'shuffles-social-services-jobs' ),
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
				'tag'    => 'sssj_tests',
				'title'  => __( 'Testing worksheet', 'shuffles-social-services-jobs' ),
				'what'   => __( 'A tester checklist covering every feature — work through each case and mark Pass/Fail (progress saved in the browser; printable). Same content as Settings → Testing.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A private/internal page for testers (or just use the Settings → Testing tab).', 'shuffles-social-services-jobs' ),
				'access' => 'public',
				'group'  => __( 'Admin & testing', 'shuffles-social-services-jobs' ),
				'atts'   => array( 'title="…"' => __( 'Optional heading.', 'shuffles-social-services-jobs' ) ),
			),
		);
	}

	public function register_assets() {
		if ( ! wp_style_is( 'sssj', 'registered' ) ) {
			wp_register_style( 'sssj', SHUFFLES_SSJ_URL . 'public/assets/css/sssj.css', array(), SHUFFLES_SSJ_VERSION );
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

		$basis = sanitize_key( (string) $atts['basis'] );
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
		$this->read_radius( $extra );

		$query  = $this->build_board_query( 'job', $basis, $extra, (int) $atts['per_page'] );
		$points = $this->points_from_query( $query );
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
			)
		);
		wp_reset_postdata();
		return ob_get_clean();
	}

	/* --- Maps helpers --- */

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
				$points[] = array(
					'id'    => (int) $p->ID,
					'title' => get_the_title( $p ),
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
		$maps   = $this->enqueue_maps( $points );
		ob_start();
		$this->load_template( 'worker-directory.php', array( 'query' => $query, 'atts' => $atts, 'maps' => $maps, 'has_points' => ! empty( $points ) ) );
		wp_reset_postdata();
		return ob_get_clean();
	}

	public function post_worker_form( $atts ) {
		wp_enqueue_style( 'sssj' );
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
		$this->load_template( 'need-board.php', array( 'query' => $query, 'atts' => $atts, 'has_map' => $has_map ) );
		wp_reset_postdata();
		return ob_get_clean();
	}

	public function post_need_form( $atts ) {
		wp_enqueue_style( 'sssj' );
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

	public function messages( $atts ) {
		wp_enqueue_style( 'sssj' );
		ob_start();
		$this->load_template( 'messages.php', array() );
		return ob_get_clean();
	}

	/* --- Organisation profiles --- */

	public function org_directory( $atts ) {
		wp_enqueue_style( 'sssj' );
		$atts  = shortcode_atts( array( 'per_page' => 12 ), is_array( $atts ) ? $atts : array(), 'sssj_org_directory' );
		$per   = max( 1, (int) $atts['per_page'] );
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$paged = isset( $_GET['sssj_paged'] ) ? max( 1, (int) $_GET['sssj_paged'] ) : 1;
		$q     = ! empty( $_GET['sssj_q'] ) ? sanitize_text_field( wp_unslash( $_GET['sssj_q'] ) ) : '';

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
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( $radius > 0 && $clat && $clng ) {
			// Match an org if ANY of its locations is within the radius; order by nearest.
			$cand = get_posts( array_merge( array( 'post_type' => 'sssj_org', 'post_status' => 'publish', 'posts_per_page' => 500, 'fields' => 'ids', 's' => $q, 'no_found_rows' => true ), $base_filter ) );
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
			$query = new WP_Query( array_merge( array( 'post_type' => 'sssj_org', 'post_status' => 'publish', 'posts_per_page' => $per, 'paged' => $paged, 's' => $q ), $base_filter ) );
		}

		// Map points = every location of the orgs on this page.
		$points = array();
		foreach ( $query->posts as $p ) {
			foreach ( Shuffles_SSJ_Org::location_points( $p->ID ) as $pt ) {
				$points[] = array( 'id' => (int) $p->ID, 'title' => get_the_title( $p ), 'lat' => $pt['lat'], 'lng' => $pt['lng'], 'url' => get_permalink( $p ) );
			}
		}
		$maps = $this->enqueue_maps( $points );

		ob_start();
		$this->load_template( 'org-directory.php', array( 'query' => $query, 'maps' => $maps, 'has_points' => ! empty( $points ) ) );
		wp_reset_postdata();
		return ob_get_clean();
	}

	public function post_org_form( $atts ) {
		wp_enqueue_style( 'sssj' );
		$this->enqueue_maps();
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

	/** Testing worksheet (the tester checklist). */
	public function tests_panel( $atts ) {
		wp_enqueue_style( 'sssj' );
		wp_enqueue_script( 'sssj-tests', SHUFFLES_SSJ_URL . 'public/assets/js/sssj-tests.js', array(), SHUFFLES_SSJ_VERSION, true );
		return Shuffles_SSJ_Tests::render( is_array( $atts ) ? $atts : array() );
	}

	/* --- Navigation menu (login-aware) --- */

	/** Resolve a page URL from its setting; fall back to finding the shortcode's page (cached). */
	private function resolve_page( $key, $shortcode ) {
		$id = (int) $this->settings->get( $key, 0 );
		if ( $id && 'publish' === get_post_status( $id ) ) {
			return (string) get_permalink( $id );
		}
		$tag   = trim( $shortcode, '[]' );
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
		return $cached ? (string) get_permalink( $cached ) : '';
	}

	private function add_nav_item( &$items, $label, $url, $cta = false ) {
		if ( '' !== (string) $url ) {
			$items[] = array( 'label' => $label, 'url' => $url, 'cta' => (bool) $cta );
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
		$this->add_nav_item( $items, __( 'Jobs', 'shuffles-social-services-jobs' ), $this->resolve_page( 'page_job_board', '[sssj_job_board]' ) );
		$this->add_nav_item( $items, __( 'Find a worker', 'shuffles-social-services-jobs' ), $this->resolve_page( 'page_worker_directory', '[sssj_worker_directory]' ) );
		$this->add_nav_item( $items, __( 'Organisations', 'shuffles-social-services-jobs' ), $this->resolve_page( 'page_org_directory', '[sssj_org_directory]' ) );

		if ( $logged_in ) {
			$this->add_nav_item( $items, __( 'Participant requests', 'shuffles-social-services-jobs' ), $this->resolve_page( 'page_need_board', '[sssj_need_board]' ) );
			if ( current_user_can( 'sssj_post_job' ) || current_user_can( 'manage_options' ) ) {
				$this->add_nav_item( $items, __( 'Post a job', 'shuffles-social-services-jobs' ), $this->resolve_page( 'page_post_job', '[sssj_post_job]' ) );
			}
			if ( current_user_can( 'sssj_post_worker' ) || current_user_can( 'manage_options' ) ) {
				$this->add_nav_item( $items, __( 'My credentials', 'shuffles-social-services-jobs' ), $this->resolve_page( 'page_credentials', '[sssj_credentials]' ) );
			}
			if ( current_user_can( 'sssj_post_need' ) || current_user_can( 'manage_options' ) ) {
				$this->add_nav_item( $items, __( 'Request support', 'shuffles-social-services-jobs' ), $this->resolve_page( 'page_post_need', '[sssj_post_need]' ) );
			}
			$this->add_nav_item( $items, __( 'Messages', 'shuffles-social-services-jobs' ), $this->resolve_page( 'page_messages', '[sssj_messages]' ) );
			$this->add_nav_item( $items, __( 'My dashboard', 'shuffles-social-services-jobs' ), $this->resolve_page( 'page_my_listings', '[sssj_my_listings]' ) );
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
		$cur = untrailingslashit( (string) home_url( add_query_arg( array() ) ) );
		ob_start();
		echo '<nav class="sssj sssj-nav ' . esc_attr( $atts['class'] ) . '" aria-label="' . esc_attr__( 'Jobs and engagements navigation', 'shuffles-social-services-jobs' ) . '">';
		if ( '' !== $atts['title'] ) {
			echo '<span class="sssj-nav__brand">' . esc_html( $atts['title'] ) . '</span>';
		}
		echo '<ul class="sssj-nav__list">';
		foreach ( $items as $it ) {
			$is_cur = ( untrailingslashit( (string) $it['url'] ) === $cur );
			$cls    = 'sssj-nav__item' . ( ! empty( $it['cta'] ) ? ' sssj-nav__item--cta' : '' );
			echo '<li class="' . esc_attr( $cls ) . '"><a href="' . esc_url( $it['url'] ) . '"' . ( $is_cur ? ' aria-current="page"' : '' ) . '>' . esc_html( $it['label'] ) . '</a></li>';
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

	private function load_template( $file, $ctx = array() ) {
		$override = locate_template( 'shuffles-jobs/' . $file );
		$path     = $override ? $override : SHUFFLES_SSJ_DIR . 'templates/' . $file;
		if ( file_exists( $path ) ) {
			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- controlled, internal context only.
			extract( $ctx, EXTR_SKIP );
			include $path;
		}
	}
}
