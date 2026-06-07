<?php
/**
 * Front-page display shortcodes — animated, marketing-friendly building blocks you can drop on a
 * home page or in a sidebar: a hero banner, animated stat counters, a featured-jobs strip and a
 * "recent items" grid/list (jobs / workers / organisations / participant requests).
 *
 * All animation is CSS + a tiny IntersectionObserver (sssj-display.js); $0 to run, no dependencies.
 * Respects prefers-reduced-motion. Privacy: participant requests are logged-in-only and pseudonymous.
 *
 * Shortcodes:
 *   [sssj_hero title="" subtitle="" button_text="" button_url="" button2_text="" button2_url="" button3_text="" button3_url="" button4_text="" button4_url=""]
 *   [sssj_stats show="jobs,workers,orgs,placed" title=""]
 *   [sssj_featured count="3" title="Featured roles"]
 *   [sssj_recent type="jobs|workers|orgs|needs" count="6" layout="grid|list" title=""]
 *   [sssj_why_us title="" intro="" layout="grid|carousel" per_row="3" rows="2" font="theme|brand" autoscroll="on|off|4000"]
 *   [sssj_join title="" intro=""]
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Display {

	/** @var Shuffles_SSJ_Settings */
	private $settings;

	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	public function register() {
		add_shortcode( 'sssj_hero', array( $this, 'hero' ) );
		add_shortcode( 'sssj_stats', array( $this, 'stats' ) );
		add_shortcode( 'sssj_featured', array( $this, 'featured' ) );
		add_shortcode( 'sssj_recent', array( $this, 'recent' ) );
		add_shortcode( 'sssj_why_us', array( $this, 'why_us' ) );
		add_shortcode( 'sssj_join', array( $this, 'join' ) );
		// Stop the editor's "smart quotes" (wptexturize) from mangling attribute quotes inside our
		// shortcodes — otherwise multi-attribute tags like [sssj_hero button2_text="…"] lose all but
		// the first button. This keeps straight quotes intact so every attribute parses.
		add_filter( 'no_texturize_shortcodes', array( $this, 'no_texturize_shortcodes' ) );
	}

	/** Exempt the plugin's attribute-bearing shortcodes from wptexturize quote-curling. */
	public function no_texturize_shortcodes( $list ) {
		return array_merge(
			(array) $list,
			array(
				'sssj_hero', 'sssj_stats', 'sssj_featured', 'sssj_recent', 'sssj_why_us',
				'sssj_menu', 'sssj_job_board', 'sssj_tfn_board', 'sssj_abn_board',
				'sssj_worker_directory', 'sssj_org_directory', 'sssj_need_board', 'sssj_guides',
			)
		);
	}

	/**
	 * The "Why us" benefits shown by [sssj_why_us] (icon + heading + blurb).
	 * Reads the admin-editable points (Settings → Pages → "Why us — benefit points"); when that
	 * is empty it falls back to the built-in defaults. Still filterable as one source of truth.
	 *
	 * @return array[]
	 */
	public static function why_us_benefits() {
		$opts = get_option( 'shuffles_ssj_settings', array() );
		$text = ( is_array( $opts ) && ! empty( $opts['why_us_points'] ) ) ? (string) $opts['why_us_points'] : '';
		$list = ( '' !== trim( $text ) ) ? self::parse_points( $text ) : self::why_us_default_benefits();
		return apply_filters( 'shuffles_ssj_why_us', $list );
	}

	/**
	 * Parse the admin-entered points text into benefit rows. One benefit per line, pipe-separated:
	 *   icon | Heading | Blurb        (icon optional — a two-part line is Heading | Blurb)
	 *
	 * @param string $text
	 * @return array[]
	 */
	public static function parse_points( $text ) {
		$out = array();
		foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			$parts = array_map( 'trim', explode( '|', $line ) );
			if ( count( $parts ) >= 3 ) {
				$out[] = array( 'icon' => $parts[0], 'h' => $parts[1], 'p' => implode( ' | ', array_slice( $parts, 2 ) ) );
			} elseif ( 2 === count( $parts ) ) {
				$out[] = array( 'icon' => '✔', 'h' => $parts[0], 'p' => $parts[1] );
			} else {
				$out[] = array( 'icon' => '✔', 'h' => $parts[0], 'p' => '' );
			}
		}
		return $out;
	}

	/** The default benefits as editable text (icon | Heading | Blurb per line) — seeds the admin field. */
	public static function why_us_points_text() {
		$lines = array();
		foreach ( self::why_us_default_benefits() as $b ) {
			$lines[] = ( isset( $b['icon'] ) ? $b['icon'] : '✔' ) . ' | ' . $b['h'] . ' | ' . $b['p'];
		}
		return implode( "\n", $lines );
	}

	/**
	 * Built-in default "Why us" benefits (used when the admin field is empty).
	 *
	 * @return array[]
	 */
	public static function why_us_default_benefits() {
		return array(
				array(
					'icon' => '🔗',
					'h'    => __( 'Everything connected in one place', 'shuffles-social-services-jobs' ),
					'p'    => __( 'Jobs, workers, providers, participants and suppliers all live in one connected marketplace — so the right people actually find each other, instead of being scattered across a dozen sites and inboxes.', 'shuffles-social-services-jobs' ),
				),
				array(
					'icon' => '👥',
					'h'    => __( 'Plugged into the community groups', 'shuffles-social-services-jobs' ),
					'p'    => __( 'We connect with the established Shuffles Facebook groups where the sector already gathers, so your listing reaches real, engaged people in the community — not a cold, anonymous job board.', 'shuffles-social-services-jobs' ),
				),
				array(
					'icon' => '🎯',
					'h'    => __( 'Purpose-built for social services', 'shuffles-social-services-jobs' ),
					'p'    => __( 'Made specifically for NDIS, aged care and social services — contractor (ABN) and employee (TFN) work kept properly apart, funding-aware matching, and participant safety built in. Not a generic board bent to fit.', 'shuffles-social-services-jobs' ),
				),
				array(
					'icon' => '💼',
					'h'    => __( 'Employment and contracting, side by side', 'shuffles-social-services-jobs' ),
					'p'    => __( 'Looking for a PAYG job or wanting to contract under your ABN? Both are first-class here — clearly separated boards, the right rules for each, all from one account.', 'shuffles-social-services-jobs' ),
				),
				array(
					'icon' => '📄',
					'h'    => __( 'Résumé & flyer creation', 'shuffles-social-services-jobs' ),
					'p'    => __( 'Turn your profile into a polished résumé, a service flyer and a shareable post in a few clicks — on-brand, accessible, and with sector best-practice built in. No design skills needed.', 'shuffles-social-services-jobs' ),
				),
				array(
					'icon' => '🔒',
					'h'    => __( 'You control your privacy — hide your profile', 'shuffles-social-services-jobs' ),
					'p'    => __( 'Show your profile to everyone, to members only, or hide it entirely. Mask individual details like your rate or phone, and rest easy that participants stay private and first contact runs through a safe relay.', 'shuffles-social-services-jobs' ),
				),
				array(
					'icon' => '📣',
					'h'    => __( 'High visibility', 'shuffles-social-services-jobs' ),
					'p'    => __( 'Strong SEO (your jobs can appear in Google for Jobs), smart synonym search, map and radius matching, and featured placement mean your roles and profile get seen by the right people.', 'shuffles-social-services-jobs' ),
				),
				array(
					'icon' => '🤝',
					'h'    => __( 'Giving back, fair pricing', 'shuffles-social-services-jobs' ),
					'p'    => __( 'Built for the community we all belong to. We keep it affordable and never charge unnecessary amounts — participants post for free — because we want to set an example for how this sector should be served.', 'shuffles-social-services-jobs' ),
				),
		);
	}

	/** Single source of truth for the display shortcodes — drives the Settings → Shortcodes tab. */
	public static function reference() {
		return array(
			array(
				'tag'    => 'sssj_hero',
				'title'  => __( 'Hero banner', 'shuffles-social-services-jobs' ),
				'what'   => __( 'A bold, animated hero banner with a headline, sub-text and up to four call-to-action buttons (the first is the primary button, the rest are outline buttons), plus a “Safety, built in” strip that lists the platform’s privacy & verification guardrails. Great at the very top of the home page.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'Top of the front page.', 'shuffles-social-services-jobs' ),
				'access' => 'public',
				'group'  => __( 'Front-page display', 'shuffles-social-services-jobs' ),
				'atts'   => array(
					'title="…"'        => __( 'Headline.', 'shuffles-social-services-jobs' ),
					'subtitle="…"'     => __( 'Sub-text under the headline.', 'shuffles-social-services-jobs' ),
					'button_text="…"'  => __( 'Primary button label.', 'shuffles-social-services-jobs' ),
					'button_url="…"'   => __( 'Primary button link.', 'shuffles-social-services-jobs' ),
					'button2_text="…"' => __( 'Optional second button label.', 'shuffles-social-services-jobs' ),
					'button2_url="…"'  => __( 'Optional second button link.', 'shuffles-social-services-jobs' ),
					'button3_text="…"' => __( 'Optional third button label.', 'shuffles-social-services-jobs' ),
					'button3_url="…"'  => __( 'Optional third button link.', 'shuffles-social-services-jobs' ),
					'button4_text="…"' => __( 'Optional fourth button label.', 'shuffles-social-services-jobs' ),
					'button4_url="…"'  => __( 'Optional fourth button link.', 'shuffles-social-services-jobs' ),
					'safety="on|off"'  => __( 'Show the “Safety, built in” guardrails strip (default on).', 'shuffles-social-services-jobs' ),
				),
			),
			array(
				'tag'    => 'sssj_stats',
				'title'  => __( 'Animated counters', 'shuffles-social-services-jobs' ),
				'what'   => __( 'Live counters that count up when they scroll into view: open jobs, available workers, organisations and people placed.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'A "by the numbers" strip on the home page.', 'shuffles-social-services-jobs' ),
				'access' => 'public',
				'group'  => __( 'Front-page display', 'shuffles-social-services-jobs' ),
				'atts'   => array(
					'show="…"'  => __( 'Comma list of any of: jobs, workers, orgs, placed (default all four).', 'shuffles-social-services-jobs' ),
					'title="…"' => __( 'Optional heading.', 'shuffles-social-services-jobs' ),
					'min="25"'  => __( 'Hide any counter whose number is below this — so small/unimpressive totals stay hidden until the marketplace grows (default 0 = always show).', 'shuffles-social-services-jobs' ),
				),
			),
			array(
				'tag'    => 'sssj_featured',
				'title'  => __( 'Featured jobs strip', 'shuffles-social-services-jobs' ),
				'what'   => __( 'A highlighted strip of featured (promoted) roles, with a subtle shine animation and a short teaser from each role’s description. Falls back to the newest jobs if none are promoted.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'Home page, below the hero.', 'shuffles-social-services-jobs' ),
				'access' => 'public',
				'group'  => __( 'Front-page display', 'shuffles-social-services-jobs' ),
				'atts'   => array(
					'count="…"' => __( 'How many to show (default 3).', 'shuffles-social-services-jobs' ),
					'title="…"' => __( 'Optional heading.', 'shuffles-social-services-jobs' ),
				),
			),
			array(
				'tag'    => 'sssj_why_us',
					'title'  => __( 'Why us (benefits)', 'shuffles-social-services-jobs' ),
					'what'   => __( 'A point-form list of the platform’s benefits, each with a short blurb — interconnectivity, the community/Facebook-group connection, purpose-built for the sector, employment + contracting, résumé/flyer creation, privacy (hide your profile), high visibility, and giving back with fair pricing. Edit the points in Settings → Pages → “Why us — benefit points” (one “icon | Heading | Blurb” per line).', 'shuffles-social-services-jobs' ),
					'where'  => __( 'A "Why us" / "About" page, or a section on the home page.', 'shuffles-social-services-jobs' ),
					'access' => 'public',
					'group'  => __( 'Front-page display', 'shuffles-social-services-jobs' ),
					'atts'   => array(
						'title="…"'           => __( 'Heading (default: “Why ” + your site name).', 'shuffles-social-services-jobs' ),
						'intro="…"'           => __( 'Optional sub-text under the heading.', 'shuffles-social-services-jobs' ),
						'layout="grid|carousel"' => __( 'grid (default) or a horizontal auto-scrolling carousel.', 'shuffles-social-services-jobs' ),
						'per_row="3"'         => __( 'Columns per row (grid) or cards in view (carousel). Leave 0/blank for responsive auto.', 'shuffles-social-services-jobs' ),
						'rows="2"'            => __( 'Show only this many rows — caps to per_row × rows items. 0/blank shows all.', 'shuffles-social-services-jobs' ),
						'font="theme|brand"' => __( 'theme (default — match the page/theme font) or brand (the plugin’s configured font).', 'shuffles-social-services-jobs' ),
						'autoscroll="on|off|4000"' => __( 'Carousel only: auto-scroll on (default), off, or a number of milliseconds between steps. Pauses on hover/touch.', 'shuffles-social-services-jobs' ),
					),
				),
				array(
					'tag'    => 'sssj_join',
					'title'  => __( 'Join page', 'shuffles-social-services-jobs' ),
					'what'   => __( 'A friendly “Join” landing page that welcomes new people and funnels them into onboarding, with Create-account / Log-in and quick links to browse jobs, find a worker or browse organisations.', 'shuffles-social-services-jobs' ),
					'where'  => __( 'A public "Join" page; link to it from your menu and calls to action.', 'shuffles-social-services-jobs' ),
					'access' => 'public',
					'group'  => __( 'Member account', 'shuffles-social-services-jobs' ),
					'atts'   => array(
						'title="…"' => __( 'Heading (default “Join Shuffles”).', 'shuffles-social-services-jobs' ),
						'intro="…"' => __( 'Optional sub-text under the heading.', 'shuffles-social-services-jobs' ),
					),
				),
				array(
					'tag'    => 'sssj_recent',
				'title'  => __( 'Recent items', 'shuffles-social-services-jobs' ),
				'what'   => __( 'The latest jobs, worker profiles, organisations or participant requests, revealed with a staggered fade-in. Use layout="list" for a compact sidebar widget.', 'shuffles-social-services-jobs' ),
				'where'  => __( 'Home page sections or a sidebar.', 'shuffles-social-services-jobs' ),
				'access' => 'public',
				'group'  => __( 'Front-page display', 'shuffles-social-services-jobs' ),
				'atts'   => array(
					'type="…"'   => __( 'jobs (default), workers, orgs or needs (participant requests — logged-in only).', 'shuffles-social-services-jobs' ),
					'count="…"'  => __( 'How many to show (default 6).', 'shuffles-social-services-jobs' ),
					'layout="…"' => __( 'grid (default) or list (compact, ideal for sidebars).', 'shuffles-social-services-jobs' ),
					'title="…"'  => __( 'Optional heading.', 'shuffles-social-services-jobs' ),
				),
			),
		);
	}

	/** Enqueue the front-end CSS + the reveal/count-up script. */
	private function enqueue() {
		wp_enqueue_style( 'sssj' );
		if ( ! wp_script_is( 'sssj-display', 'registered' ) ) {
			wp_register_script( 'sssj-display', SHUFFLES_SSJ_URL . 'public/assets/js/sssj-display.js', array(), SHUFFLES_SSJ_VERSION, true );
		}
		wp_enqueue_script( 'sssj-display' );
	}

	/* ---------------------------------------------------------------- Hero */

	public function hero( $atts ) {
		$a = shortcode_atts(
			array(
				'title'        => __( 'Find the right support work — and the right people', 'shuffles-social-services-jobs' ),
				'subtitle'     => '',
				'button_text'  => __( 'Browse jobs', 'shuffles-social-services-jobs' ),
				'button_url'   => '',
				'button2_text' => '',
				'button2_url'  => '',
				'button3_text' => '',
				'button3_url'  => '',
				'button4_text' => '',
				'button4_url'  => '',
				'safety'       => 'on',
			),
			is_array( $atts ) ? $atts : array(),
			'sssj_hero'
		);
		// Default the sub-text from the configured focus programs (branding), else a generic line.
		if ( '' === $a['subtitle'] ) {
			$programs = trim( (string) $this->settings->get( 'focus_programs', '' ) );
			$a['subtitle'] = '' !== $programs
				/* translators: %s: comma list of funding programs, e.g. NDIS, Aged Care, DVA. */
				? sprintf( __( 'A safe, accessible marketplace for %s and social-services work.', 'shuffles-social-services-jobs' ), $programs )
				: __( 'A safe, accessible marketplace for disability, aged care and social-services work.', 'shuffles-social-services-jobs' );
		}
		$this->enqueue();

		ob_start();
		?>
		<div class="sssj sssj--display">
			<section class="sssj-hero sssj-reveal" data-sssj-reveal>
				<div class="sssj-hero__inner">
					<h1 class="sssj-hero__title"><?php echo esc_html( $a['title'] ); ?></h1>
					<?php if ( '' !== $a['subtitle'] ) : ?>
						<p class="sssj-hero__subtitle"><?php echo esc_html( $a['subtitle'] ); ?></p>
					<?php endif; ?>
					<?php
					// Collect up to four call-to-action buttons (first = primary, rest = ghost).
					$sssj_btns = array();
					foreach ( array( '', '2', '3', '4' ) as $sssj_n ) {
						$sssj_bt = (string) $a[ 'button' . $sssj_n . '_text' ];
						$sssj_bu = (string) $a[ 'button' . $sssj_n . '_url' ];
						if ( '' !== $sssj_bt && '' !== $sssj_bu ) {
							$sssj_btns[] = array( $sssj_bt, $sssj_bu );
						}
					}
					?>
					<?php if ( $sssj_btns ) : ?>
						<div class="sssj-hero__cta">
							<?php foreach ( $sssj_btns as $sssj_idx => $sssj_b ) : ?>
								<a class="sssj-btn sssj-btn--lg <?php echo 0 === $sssj_idx ? 'sssj-btn--primary' : 'sssj-btn--ghost sssj-hero__btn2'; ?>" href="<?php echo esc_url( $sssj_b[1] ); ?>"><?php echo esc_html( $sssj_b[0] ); ?></a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php
					if ( 'off' !== $a['safety'] ) {
						$sssj_guards = self::safety_guardrails();
						if ( $sssj_guards ) {
							echo '<div class="sssj-hero__inner sssj-hero__safety"><h2 class="sssj-hero__safety-title">🛡️ ' . esc_html__( 'Safety, built in', 'shuffles-social-services-jobs' ) . '</h2><ul class="sssj-hero__guards">';
							foreach ( $sssj_guards as $sssj_g ) {
								echo '<li>' . esc_html( $sssj_g ) . '</li>';
							}
							echo '</ul></div>';
						}
					}
					?>
				</section>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * The platform's safety/trust guardrails (member-safe wording — never names a vendor).
	 * Single source for the hero "Safety, built in" strip; mirrored in docs/SAFETY-GUARDRAILS.md.
	 *
	 * @return string[]
	 */
	public static function safety_guardrails() {
		return apply_filters(
			'shuffles_ssj_hero_guardrails',
			array(
				__( 'Participant privacy is structural — listings are pseudonymous and contact runs through a safe internal relay.', 'shuffles-social-services-jobs' ),
				__( 'The ✓ Verified badge is granted only after an administrator checks the evidence — never self-claimed.', 'shuffles-social-services-jobs' ),
				__( 'NDIS provider registration is read live from the NDIS Commission’s public register (status, groups, ABN, outlets, phone) and re-checked monthly — shown as read-only register data, never self-entered.', 'shuffles-social-services-jobs' ),
				__( 'Credential documents are stored privately and shown only to you and our team — never on a public page.', 'shuffles-social-services-jobs' ),
				__( 'Worker screening, WWCC, police checks and insurances are tracked with expiry reminders.', 'shuffles-social-services-jobs' ),
			)
		);
	}

	/* --------------------------------------------------------------- Stats */

	public function stats( $atts ) {
		$a = shortcode_atts(
			array(
				'show'  => 'jobs,workers,orgs,placed',
				'title' => '',
				'min'   => 0,
			),
			is_array( $atts ) ? $atts : array(),
			'sssj_stats'
		);
		$this->enqueue();
		$min   = max( 0, (int) $a['min'] );
		$want  = array_filter( array_map( 'trim', explode( ',', (string) $a['show'] ) ) );
		$all   = $this->counts();
		$meta  = array(
			'jobs'    => array( '💼', __( 'Open jobs', 'shuffles-social-services-jobs' ) ),
			'workers' => array( '🧑‍⚕️', __( 'Available workers', 'shuffles-social-services-jobs' ) ),
			'orgs'    => array( '🏢', __( 'Organisations', 'shuffles-social-services-jobs' ) ),
			'placed'  => array( '🤝', __( 'People placed', 'shuffles-social-services-jobs' ) ),
		);

		ob_start();
		?>
		<div class="sssj sssj--display">
			<?php if ( '' !== $a['title'] ) : ?><h2 class="sssj-display__title"><?php echo esc_html( $a['title'] ); ?></h2><?php endif; ?>
			<div class="sssj-stats" data-sssj-reveal>
				<?php
				$i = 0;
				foreach ( $want as $key ) :
					if ( ! isset( $meta[ $key ] ) ) { continue; }
					$val = isset( $all[ $key ] ) ? (int) $all[ $key ] : 0;
					if ( $val < $min ) { continue; } // hide counters until the number is sizeable
					?>
					<div class="sssj-stat sssj-reveal" style="transition-delay:<?php echo esc_attr( ( $i * 90 ) . 'ms' ); ?>">
						<span class="sssj-stat__icon" aria-hidden="true"><?php echo esc_html( $meta[ $key ][0] ); ?></span>
						<span class="sssj-stat__num" data-sssj-count="<?php echo esc_attr( $val ); ?>">0</span>
						<span class="sssj-stat__label"><?php echo esc_html( $meta[ $key ][1] ); ?></span>
					</div>
					<?php
					$i++;
				endforeach;
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/** Site-wide totals used by [sssj_stats]. */
	private function counts() {
		global $wpdb;
		$jobs = (int) ( wp_count_posts( 'sssj_job' )->publish ?? 0 );

		$workers = (int) ( new WP_Query( array(
			'post_type'      => 'sssj_worker',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'no_found_rows'  => false,
			'meta_query'     => array(
				'relation' => 'AND',
				array( 'key' => 'is_available', 'value' => '1' ),
				array( 'key' => 'visibility', 'value' => array( 'public', 'logged_in' ), 'compare' => 'IN' ),
			),
		) ) )->found_posts;

		$orgs = (int) ( new WP_Query( array(
			'post_type'      => 'sssj_org',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'no_found_rows'  => false,
			'meta_query'     => array(
				'relation' => 'OR',
				array( 'key' => 'org_hidden', 'compare' => 'NOT EXISTS' ),
				array( 'key' => 'org_hidden', 'value' => '1', 'compare' => '!=' ),
			),
		) ) )->found_posts;

		$placed = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sssj_application WHERE status = 'offer'" ); // phpcs:ignore WordPress.DB

		return array( 'jobs' => $jobs, 'workers' => $workers, 'orgs' => $orgs, 'placed' => $placed );
	}

	/* ------------------------------------------------------------ Featured */

	public function featured( $atts ) {
		$a = shortcode_atts(
			array(
				'count' => 3,
				'title' => __( 'Featured roles', 'shuffles-social-services-jobs' ),
			),
			is_array( $atts ) ? $atts : array(),
			'sssj_featured'
		);
		$this->enqueue();
		$count = max( 1, (int) $a['count'] );

		$q = new WP_Query( array(
			'post_type'      => 'sssj_job',
			'post_status'    => 'publish',
			'posts_per_page' => $count,
			'no_found_rows'  => true,
			'meta_query'     => array( array( 'key' => 'is_promoted', 'value' => '1' ) ),
		) );
		if ( ! $q->have_posts() ) {
			$q = new WP_Query( array(
				'post_type'      => 'sssj_job',
				'post_status'    => 'publish',
				'posts_per_page' => $count,
				'no_found_rows'  => true,
			) );
		}

		ob_start();
		echo '<div class="sssj sssj--display">';
		if ( '' !== $a['title'] ) { echo '<h2 class="sssj-display__title">' . esc_html( $a['title'] ) . '</h2>'; }
		if ( $q->have_posts() ) {
			echo '<div class="sssj-grid" data-sssj-reveal>';
			$i = 0;
			while ( $q->have_posts() ) {
				$q->the_post();
				$this->job_card( get_the_ID(), $i, true );
				$i++;
			}
			echo '</div>';
			wp_reset_postdata();
		} else {
			echo '<div class="sssj-panel"><p>' . esc_html__( 'No roles to show yet.', 'shuffles-social-services-jobs' ) . '</p></div>';
		}
		echo '</div>';
		return ob_get_clean();
	}

	/* -------------------------------------------------------------- Recent */

	public function recent( $atts ) {
		$a = shortcode_atts(
			array(
				'type'   => 'jobs',
				'count'  => 6,
				'layout' => 'grid',
				'title'  => '',
			),
			is_array( $atts ) ? $atts : array(),
			'sssj_recent'
		);
		$this->enqueue();
		$type   = sanitize_key( $a['type'] );
		$count  = max( 1, min( 30, (int) $a['count'] ) );
		$list   = ( 'list' === $a['layout'] );

		// Participant requests are private — only render for logged-in members.
		if ( 'needs' === $type && ! is_user_logged_in() ) {
			return '';
		}

		$pt_map = array( 'jobs' => 'sssj_job', 'workers' => 'sssj_worker', 'orgs' => 'sssj_org', 'needs' => 'sssj_need' );
		if ( ! isset( $pt_map[ $type ] ) ) { $type = 'jobs'; }

		$args = array(
			'post_type'      => $pt_map[ $type ],
			'post_status'    => 'publish',
			'posts_per_page' => $count,
			'no_found_rows'  => true,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		// Honour visibility / hidden flags.
		if ( 'workers' === $type ) {
			$vis = is_user_logged_in() ? array( 'public', 'logged_in' ) : array( 'public' );
			$args['meta_query'] = array( array( 'key' => 'visibility', 'value' => $vis, 'compare' => 'IN' ) );
		} elseif ( 'orgs' === $type ) {
			$args['meta_query'] = array(
				'relation' => 'OR',
				array( 'key' => 'org_hidden', 'compare' => 'NOT EXISTS' ),
				array( 'key' => 'org_hidden', 'value' => '1', 'compare' => '!=' ),
			);
		}
		$q = new WP_Query( $args );

		ob_start();
		echo '<div class="sssj sssj--display">';
		if ( '' !== $a['title'] ) { echo '<h2 class="sssj-display__title">' . esc_html( $a['title'] ) . '</h2>'; }
		if ( $q->have_posts() ) {
			echo '<div class="' . ( $list ? 'sssj-recent-list' : 'sssj-grid' ) . '" data-sssj-reveal>';
			$i = 0;
			while ( $q->have_posts() ) {
				$q->the_post();
				$id = get_the_ID();
				if ( $list ) {
					$this->list_row( $id, $type, $i );
				} else {
					switch ( $type ) {
						case 'workers': $this->worker_card( $id, $i ); break;
						case 'orgs':    $this->org_card( $id, $i ); break;
						case 'needs':   $this->need_card( $id, $i ); break;
						default:        $this->job_card( $id, $i, false ); break;
					}
				}
				$i++;
			}
			echo '</div>';
			wp_reset_postdata();
		} else {
			echo '<div class="sssj-panel"><p>' . esc_html__( 'Nothing to show yet.', 'shuffles-social-services-jobs' ) . '</p></div>';
		}
		echo '</div>';
		return ob_get_clean();
	}

	/**
	 * "Why us" benefits block — point-form benefits, each with a short blurb.
	 * Atts: title (heading), intro (sub-text under the heading).
	 */
	public function why_us( $atts ) {
		$a = shortcode_atts(
			array(
				'title'      => '', // defaults to "Why <site name>" below
				'intro'      => '',
				'layout'     => 'grid',   // grid | carousel
				'per_row'    => 0,        // columns (grid) / cards-in-view (carousel); 0 = responsive auto
				'rows'       => 0,        // cap to per_row × rows items; 0 = show all
				'font'       => 'theme',  // theme (inherit the page/theme font) | brand (the plugin font)
				'autoscroll' => 'on',     // carousel only: on | off | <milliseconds>
			),
			is_array( $atts ) ? $atts : array(),
			'sssj_why_us'
		);
		if ( '' === trim( (string) $a['title'] ) ) {
			/* translators: %s: site name. */
			$a['title'] = sprintf( __( 'Why %s', 'shuffles-social-services-jobs' ), get_bloginfo( 'name' ) );
		}
		$this->enqueue();

		$layout  = ( 'carousel' === $a['layout'] ) ? 'carousel' : 'grid';
		$per_row = max( 0, (int) $a['per_row'] );
		$rows    = max( 0, (int) $a['rows'] );
		$font    = ( 'brand' === $a['font'] ) ? 'brand' : 'theme';

		$items = self::why_us_benefits();
		if ( $per_row > 0 && $rows > 0 ) {
			$items = array_slice( $items, 0, $per_row * $rows );
		}

		$classes = 'sssj-whyus sssj-whyus--' . $layout . ' sssj-whyus--' . $font . 'font';
		$style   = '';
		if ( $per_row > 0 ) {
			$style = ( 'grid' === $layout )
				? 'grid-template-columns:repeat(' . $per_row . ',minmax(0,1fr))'
				: '--sssj-whyus-per:' . $per_row;
		}
		// Carousel auto-scroll (default on; a number sets the step interval in ms).
		$autoscroll = ( 'carousel' === $layout && 'off' !== (string) $a['autoscroll'] );
		$ascroll_ms = ctype_digit( (string) $a['autoscroll'] ) ? max( 1500, (int) $a['autoscroll'] ) : 4000;
		$attrs      = ' data-sssj-reveal';
		if ( '' !== $style ) {
			$attrs .= ' style="' . esc_attr( $style ) . '"';
		}
		if ( $autoscroll ) {
			$attrs .= ' data-sssj-autoscroll="' . esc_attr( $ascroll_ms ) . '"';
		}

		ob_start();
		echo '<div class="sssj sssj--display">';
		if ( '' !== $a['title'] ) {
			echo '<h2 class="sssj-display__title">' . esc_html( $a['title'] ) . '</h2>';
		}
		if ( '' !== $a['intro'] ) {
			echo '<p class="sssj-whyus__intro">' . esc_html( $a['intro'] ) . '</p>';
		}
		echo '<div class="' . esc_attr( $classes ) . '"' . $attrs . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
		$i = 0;
		foreach ( $items as $b ) {
			echo '<article class="sssj-whyus__item sssj-reveal" ' . $this->delay( $i ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<div class="sssj-whyus__icon" aria-hidden="true">' . esc_html( isset( $b['icon'] ) ? $b['icon'] : '✔' ) . '</div>';
			echo '<div class="sssj-whyus__body"><h3 class="sssj-whyus__h">' . esc_html( $b['h'] ) . '</h3>';
			echo '<p class="sssj-whyus__p">' . esc_html( $b['p'] ) . '</p></div>';
			echo '</article>';
			$i++;
		}
		echo '</div></div>';
		return ob_get_clean();
	}

	/**
	 * Custom "Join" landing page — a friendly welcome that funnels people into onboarding,
	 * with sign-up / log-in and the main entry points. Atts: title, intro.
	 */
	public function join( $atts ) {
		$a = shortcode_atts(
			array(
				'title' => __( 'Join Shuffles', 'shuffles-social-services-jobs' ),
				'intro' => __( 'Whether you’re looking for work, hiring, providing services, or seeking support — you’re in the right place. It takes a minute to get set up.', 'shuffles-social-services-jobs' ),
			),
			is_array( $atts ) ? $atts : array(),
			'sssj_join'
		);
		$this->enqueue();

		$pl        = array( 'Shuffles_SSJ_Shortcodes', 'page_link' );
		$onboard   = is_callable( $pl ) ? call_user_func( $pl, 'page_onboard', '[sssj_onboard]' ) : '';
		$jobs      = is_callable( $pl ) ? call_user_func( $pl, 'page_job_board', '[sssj_job_board]' ) : '';
		$workers   = is_callable( $pl ) ? call_user_func( $pl, 'page_worker_directory', '[sssj_worker_directory]' ) : '';
		$orgs      = is_callable( $pl ) ? call_user_func( $pl, 'page_org_directory', '[sssj_org_directory]' ) : '';
		$logged_in = is_user_logged_in();
		$can_reg   = (bool) get_option( 'users_can_register' );

		ob_start();
		echo '<div class="sssj sssj--display"><div class="sssj-join sssj-reveal" data-sssj-reveal>';
		if ( '' !== $a['title'] ) { echo '<h2 class="sssj-display__title">' . esc_html( $a['title'] ) . '</h2>'; }
		if ( '' !== $a['intro'] ) { echo '<p class="sssj-join__intro">' . esc_html( $a['intro'] ) . '</p>'; }

		echo '<div class="sssj-join__primary">';
		if ( $onboard ) {
			echo '<a class="sssj-btn sssj-btn--primary sssj-btn--lg" href="' . esc_url( $onboard ) . '">' . esc_html__( 'Let’s get you onboarded', 'shuffles-social-services-jobs' ) . '</a>';
		}
		if ( ! $logged_in ) {
			if ( $can_reg ) {
				echo '<a class="sssj-btn sssj-btn--secondary sssj-btn--lg" href="' . esc_url( wp_registration_url() ) . '">' . esc_html__( 'Create an account', 'shuffles-social-services-jobs' ) . '</a>';
			}
			echo '<a class="sssj-btn sssj-btn--ghost sssj-btn--lg" href="' . esc_url( wp_login_url( $onboard ? $onboard : home_url( '/' ) ) ) . '">' . esc_html__( 'Log in', 'shuffles-social-services-jobs' ) . '</a>';
		}
		echo '</div>';

		echo '<p class="sssj-join__steps">' . esc_html__( 'New here? Create your account, then “Get started” walks you through choosing what you’re here to do — and sets up the right profile for you.', 'shuffles-social-services-jobs' ) . '</p>';

		// Quick entry points.
		$links = array();
		if ( $jobs ) { $links[] = array( $jobs, __( 'Browse jobs', 'shuffles-social-services-jobs' ) ); }
		if ( $workers ) { $links[] = array( $workers, __( 'Find a worker', 'shuffles-social-services-jobs' ) ); }
		if ( $orgs ) { $links[] = array( $orgs, __( 'Browse organisations', 'shuffles-social-services-jobs' ) ); }
		if ( $links ) {
			echo '<div class="sssj-join__links"><span class="sssj-join__or">' . esc_html__( 'Or jump straight in:', 'shuffles-social-services-jobs' ) . '</span> ';
			foreach ( $links as $l ) {
				echo '<a class="sssj-btn sssj-btn--ghost sssj-btn--sm" href="' . esc_url( $l[0] ) . '">' . esc_html( $l[1] ) . '</a> ';
			}
			echo '</div>';
		}
		echo '</div></div>';
		return ob_get_clean();
	}

	/* --------------------------------------------------------------- Cards */

	private function delay( $i ) {
		return 'style="transition-delay:' . esc_attr( ( $i * 80 ) . 'ms' ) . '"';
	}

	private function job_card( $id, $i, $featured = false ) {
		$suburb = (string) get_post_meta( $id, 'location_suburb', true );
		$state  = (string) get_post_meta( $id, 'location_state', true );
		$basis  = (string) get_post_meta( $id, 'engagement_basis', true );
		$rmin   = (float) get_post_meta( $id, 'rate_min', true );
		$runit  = (string) get_post_meta( $id, 'rate_unit', true );
		$promoted = $featured || (bool) get_post_meta( $id, 'is_promoted', true );
		$mod    = 'tfn' === $basis ? 'sssj-card--tfn' : ( 'abn' === $basis ? 'sssj-card--abn' : '' );
		if ( $promoted ) { $mod .= ' sssj-card--featured sssj-card--shine'; }
		echo '<article class="sssj-card sssj-reveal ' . esc_attr( $mod ) . '" ' . $this->delay( $i ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<h3 style="margin-top:0"><a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a></h3>';
		echo '<div class="sssj-row">';
		if ( $promoted ) { echo '<span class="sssj-badge sssj-badge--featured">' . esc_html__( '★ Featured', 'shuffles-social-services-jobs' ) . '</span>'; }
		if ( $basis ) { echo '<span class="sssj-badge sssj-badge--' . esc_attr( Shuffles_SSJ_Query::basis_class( $basis ) ) . '">' . esc_html( Shuffles_SSJ_Query::basis_label( $basis, true ) ) . '</span>'; }
		echo Shuffles_SSJ_Shortcodes::openness_badges( $id ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</div>';
		if ( $suburb || $state ) { echo '<p>📍 ' . esc_html( trim( $suburb . ' ' . $state ) ) . '</p>'; }
		if ( $rmin > 0 ) { echo '<p>💲 ' . esc_html( __( 'from', 'shuffles-social-services-jobs' ) . ' ' . number_format_i18n( $rmin ) . ' / ' . ( $runit ? $runit : 'hour' ) ) . '</p>'; }
		// A short teaser (~40 chars) from the advertised position's description.
		$raw  = get_post_field( 'post_excerpt', $id );
		if ( '' === trim( (string) $raw ) ) { $raw = get_post_field( 'post_content', $id ); }
		$desc = trim( wp_strip_all_tags( strip_shortcodes( (string) $raw ) ) );
		if ( '' !== $desc ) {
			$snippet = function_exists( 'mb_substr' ) ? mb_substr( $desc, 0, 40 ) : substr( $desc, 0, 40 );
			$longer  = ( function_exists( 'mb_strlen' ) ? mb_strlen( $desc ) : strlen( $desc ) ) > 40;
			echo '<p class="sssj-card__desc">' . esc_html( rtrim( $snippet ) . ( $longer ? '…' : '' ) ) . '</p>';
		}
		echo '<a class="sssj-btn sssj-btn--secondary sssj-btn--sm" href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html__( 'View job', 'shuffles-social-services-jobs' ) . '</a>';
		echo '</article>';
	}

	private function worker_card( $id, $i ) {
		$avail = '1' === (string) get_post_meta( $id, 'is_available', true );
		$verified = (string) get_post_meta( $id, 'verified_at', true );
		$svcs  = wp_get_post_terms( $id, 'sssjt_category', array( 'fields' => 'names' ) );
		echo '<article class="sssj-card sssj-reveal" ' . $this->delay( $i ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<h3 style="margin-top:0"><a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a></h3>';
		echo '<div class="sssj-row">';
		if ( $avail ) { echo '<span class="sssj-badge sssj-badge--verified">' . esc_html__( 'Available', 'shuffles-social-services-jobs' ) . '</span>'; }
		if ( $verified ) { echo '<span class="sssj-badge sssj-badge--verified">✓ ' . esc_html__( 'Verified', 'shuffles-social-services-jobs' ) . '</span>'; }
		echo '</div>';
		if ( ! is_wp_error( $svcs ) && $svcs ) { echo '<p>' . esc_html( implode( ', ', array_slice( $svcs, 0, 3 ) ) ) . '</p>'; }
		echo '<a class="sssj-btn sssj-btn--secondary sssj-btn--sm" href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html__( 'View profile', 'shuffles-social-services-jobs' ) . '</a>';
		echo '</article>';
	}

	private function org_card( $id, $i ) {
		$sub   = (string) get_post_meta( $id, 'location_suburb', true );
		$state = (string) get_post_meta( $id, 'location_state', true );
		$logo  = class_exists( 'Shuffles_SSJ_Org' ) ? Shuffles_SSJ_Org::logo_url( $id, 'thumbnail' ) : '';
		$stats = class_exists( 'Shuffles_SSJ_Org' ) ? Shuffles_SSJ_Org::stats( $id ) : array( 'open' => 0, 'placed' => 0 );
		echo '<article class="sssj-card sssj-reveal" ' . $this->delay( $i ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<div class="sssj-row" style="gap:10px;flex-wrap:nowrap;align-items:flex-start">';
		if ( $logo ) { echo '<img class="sssj-org-logo" src="' . esc_url( $logo ) . '" alt="" />'; }
		echo '<h3 style="margin:0"><a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a></h3>';
		echo '</div>';
		echo '<div class="sssj-row">';
		echo '<span class="sssj-badge sssj-badge--verified">' . esc_html( sprintf( _n( '%d open job', '%d open jobs', (int) $stats['open'], 'shuffles-social-services-jobs' ), (int) $stats['open'] ) ) . '</span>';
		echo '</div>';
		if ( $sub || $state ) { echo '<p>📍 ' . esc_html( trim( $sub . ' ' . $state ) ) . '</p>'; }
		echo '<a class="sssj-btn sssj-btn--secondary sssj-btn--sm" href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html__( 'View profile & jobs', 'shuffles-social-services-jobs' ) . '</a>';
		echo '</article>';
	}

	private function need_card( $id, $i ) {
		$ref    = (string) get_post_meta( $id, 'participant_ref', true );
		$suburb = (string) get_post_meta( $id, 'location_suburb', true );
		$state  = (string) get_post_meta( $id, 'location_state', true );
		echo '<article class="sssj-card sssj-card--need sssj-reveal" ' . $this->delay( $i ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<div class="sssj-row"><span class="sssj-badge sssj-badge--need">' . esc_html( $ref ? $ref : __( 'Participant', 'shuffles-social-services-jobs' ) ) . '</span></div>';
		echo '<h3 style="margin:8px 0">' . esc_html( get_the_title( $id ) ) . '</h3>';
		if ( $suburb || $state ) { echo '<p>📍 ' . esc_html( trim( $suburb . ' ' . $state ) ) . '</p>'; }
		echo '</article>';
	}

	/** Compact one-line row for sidebar list layout. */
	private function list_row( $id, $type, $i ) {
		$meta = '';
		if ( 'jobs' === $type ) {
			$sub = (string) get_post_meta( $id, 'location_suburb', true );
			$meta = $sub ? '📍 ' . $sub : '';
		} elseif ( 'orgs' === $type && class_exists( 'Shuffles_SSJ_Org' ) ) {
			$s = Shuffles_SSJ_Org::stats( $id );
			$meta = sprintf( _n( '%d open job', '%d open jobs', (int) $s['open'], 'shuffles-social-services-jobs' ), (int) $s['open'] );
		} elseif ( 'needs' === $type ) {
			$meta = (string) get_post_meta( $id, 'location_suburb', true );
		}
		$title = ( 'needs' === $type ) ? get_the_title( $id ) : get_the_title( $id );
		echo '<a class="sssj-recent-row sssj-reveal" href="' . esc_url( get_permalink( $id ) ) . '" ' . $this->delay( $i ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<span class="sssj-recent-row__title">' . esc_html( $title ) . '</span>';
		if ( $meta ) { echo '<span class="sssj-recent-row__meta">' . esc_html( $meta ) . '</span>'; }
		echo '</a>';
	}
}
