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
		if ( $basis ) { echo '<span class="sssj-badge sssj-badge--' . esc_attr( 'tfn' === $basis ? 'tfn' : 'abn' ) . '">' . esc_html( 'tfn' === $basis ? __( 'TFN', 'shuffles-social-services-jobs' ) : __( 'ABN', 'shuffles-social-services-jobs' ) ) . '</span>'; }
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
