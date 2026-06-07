<?php
/**
 * Feature spotlight. Shows ONE built-in feature per day (the same one for everyone, rotating each
 * day) as a full-width, eye-pleasing tile with a brief rainbow tracing light around the border and
 * a link that explains the feature in more depth.
 *
 * Single source of truth: features(). Rendered by [sssj_feature_today] and the matching Elementor
 * widget. The daily pick is deterministic (day of the year), so it changes once per day.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Spotlight {

	/**
	 * The pool of features to highlight. Each: title, text (one or two plain sentences), and a deep
	 * link described as a page-finding shortcode (and optional settings key) so the link self-heals
	 * to whatever page actually runs that feature. Use 'special' => 'affiliate' for the referral link.
	 *
	 * Filterable via shuffles_ssj_spotlight_features.
	 *
	 * @return array
	 */
	public static function features() {
		$f = array(
			array(
				'title' => __( 'Employee, contractor and volunteer work, kept separate', 'shuffles-social-services-jobs' ),
				'text'  => __( 'Wage jobs, ABN contracting and volunteer roles each live on their own board, so you only ever see the kind of work you are actually after.', 'shuffles-social-services-jobs' ),
				'detail' => __( 'How it works: every role is tagged when it is posted as employee (TFN), contractor (ABN) or volunteer, and the boards filter strictly on that tag. The employee board never shows contractor work, the contractor board never shows wage jobs, and volunteer roles sit on their own board. You pick the board that matches what you are after, so the results are always relevant.', 'shuffles-social-services-jobs' ),
				'sc'    => '[sssj_job_board]',
				'key'   => 'page_job_board',
			),
			array(
				'title' => __( 'Participant privacy, built in', 'shuffles-social-services-jobs' ),
				'text'  => __( 'Participant requests are pseudonymous and suburb level only, and first contact runs through a safe internal relay. Names and contact details are never on show.', 'shuffles-social-services-jobs' ),
				'detail' => __( 'How it works: a participant never enters their name. The system creates a private code and shows only the suburb. When a worker responds, the message travels through an internal relay, so the worker never sees an email or phone number. Participant pages are also kept out of search engines, so the most vulnerable people are protected by design.', 'shuffles-social-services-jobs' ),
				'sc'    => '[sssj_policies]',
				'key'   => 'page_policies',
			),
			array(
				'title' => __( 'The verified badge you can trust', 'shuffles-social-services-jobs' ),
				'text'  => __( 'A green Verified badge appears only after our team checks the evidence. It is never self claimed, so it means something.', 'shuffles-social-services-jobs' ),
				'detail' => __( 'How it works: a worker uploads evidence of a check, for example NDIS Worker Screening or a Working With Children Check. It sits as pending until a member of our team reviews it. Only then does the green Verified badge appear, and only while the check is in date. Nothing is ever self-marked as verified, so the badge always means a real human checked it.', 'shuffles-social-services-jobs' ),
				'sc'    => '[sssj_policies]',
				'key'   => 'page_policies',
			),
			array(
				'title' => __( 'Store a resume, apply in seconds', 'shuffles-social-services-jobs' ),
				'text'  => __( 'Keep one or more resumes on file and pick the right one when you apply. They stay private and are shown only to an employer you apply to.', 'shuffles-social-services-jobs' ),
				'detail' => __( 'How it works: you upload one or more resume files to your profile and name them. They are stored privately, never behind a public link. When you apply for a job you choose which resume to send, and only that employer can open it. You can also build a clean, readable resume from your profile in one click.', 'shuffles-social-services-jobs' ),
				'sc'    => '[sssj_workflows]',
				'key'   => 'page_workflows',
			),
			array(
				'title' => __( 'Smart matching that finds the fit', 'shuffles-social-services-jobs' ),
				'text'  => __( 'We rank people and roles on shared services, location, availability, engagement basis, rate and trust, so the best fits surface first.', 'shuffles-social-services-jobs' ),
				'detail' => __( 'How it works: for each person and role the system scores how well they fit on shared services, distance, availability, engagement basis, rate and trust signals like verification and reviews. Higher scores appear first, so the most relevant matches are at the top instead of a random list.', 'shuffles-social-services-jobs' ),
				'sc'    => '[sssj_why_us]',
				'key'   => 'page_why_us',
			),
			array(
				'title' => __( 'Real, moderated reviews', 'shuffles-social-services-jobs' ),
				'text'  => __( 'Ratings and reviews come only from people who genuinely engaged, and every one is checked before it shows. Quality rises to the top.', 'shuffles-social-services-jobs' ),
				'detail' => __( 'How it works: only someone who has genuinely engaged with you through the platform can leave a review, and every review is held for moderation before it appears. The reviewed person can post one public reply. Approved ratings feed back into matching, so consistently good people rise.', 'shuffles-social-services-jobs' ),
				'sc'    => '[sssj_why_us]',
				'key'   => 'page_why_us',
			),
			array(
				'title' => __( 'Accessible to everyone', 'shuffles-social-services-jobs' ),
				'text'  => __( 'Read aloud, larger text, high contrast and multiple languages are built in, so the marketplace works for the widest possible audience.', 'shuffles-social-services-jobs' ),
				'detail' => __( 'How it works: built-in tools let anyone read pages aloud, increase the text size, switch to high contrast and use the site in several languages. Your preferences are remembered. It costs nothing to run and works on every board, profile and form.', 'shuffles-social-services-jobs' ),
				'sc'    => '[sssj_why_us]',
				'key'   => 'page_why_us',
			),
			array(
				'title' => __( 'Save a search, let matches come to you', 'shuffles-social-services-jobs' ),
				'text'  => __( 'Set your filters once, save the search, and get a daily email when new listings match. No need to keep checking back.', 'shuffles-social-services-jobs' ),
				'detail' => __( 'How it works: set your keywords, filters, location and radius, then save the search. Each day the system checks for new listings that match and emails you only when something new turns up, so you can stop refreshing the board.', 'shuffles-social-services-jobs' ),
				'sc'    => '[sssj_job_board]',
				'key'   => 'page_job_board',
			),
			array(
				'title' => __( 'Earn by referring others', 'shuffles-social-services-jobs' ),
				'text'  => __( 'Invite people to join and earn a referral reward. A flexible way to make a little income, open to everyone.', 'shuffles-social-services-jobs' ),
				'detail' => __( 'How it works: you get a personal referral link. When someone joins through it, you earn a referral reward. You will need a PayPal account to be paid, and you can set that up later. It is open to everyone, including participants looking for a little extra income.', 'shuffles-social-services-jobs' ),
				'special' => 'affiliate',
			),
			array(
				'title' => __( 'Find or offer volunteer work', 'shuffles-social-services-jobs' ),
				'text'  => __( 'A dedicated volunteer board keeps unpaid roles separate from paid work, so giving your time is easy to find and offer.', 'shuffles-social-services-jobs' ),
				'detail' => __( 'How it works: volunteer roles are posted with an unpaid basis, so they sit on their own board, separate from paid work. Any logged-in member can offer their time without needing an ABN, and organisations can recruit volunteers there too.', 'shuffles-social-services-jobs' ),
				'sc'    => '[sssj_volunteer_board]',
				'key'   => 'page_volunteer_board',
			),
			array(
				'title' => __( 'Step by step, every task explained', 'shuffles-social-services-jobs' ),
				'text'  => __( 'Plain English walkthroughs show you the exact path through the site for whatever you want to do, from posting to applying.', 'shuffles-social-services-jobs' ),
				'detail' => __( 'How it works: short, plain-English walkthroughs lay out the exact steps for each task, from setting up an account to posting a job or applying. Each step says what to do and where to click, with a start button, so nobody gets stuck.', 'shuffles-social-services-jobs' ),
				'sc'    => '[sssj_workflows]',
				'key'   => 'page_workflows',
			),
			array(
				'title' => __( 'Browse trusted organisations', 'shuffles-social-services-jobs' ),
				'text'  => __( 'A searchable directory of providers and organisations, with sizes, structures and verified details, so you can choose with confidence.', 'shuffles-social-services-jobs' ),
				'detail' => __( 'How it works: every organisation has a searchable profile showing its size, structure, sectors, locations and verified details, including live NDIS registration where it applies. You can filter and compare, so you choose a provider with the full picture in front of you.', 'shuffles-social-services-jobs' ),
				'sc'    => '[sssj_org_directory]',
				'key'   => 'page_org_directory',
			),
			array(
				'title' => __( 'Advertise without showing your name', 'shuffles-social-services-jobs' ),
				'text'  => __( 'Post anonymously when you need to. The listing shows as Anonymous, and your name is kept out of search engines.', 'shuffles-social-services-jobs' ),
				'detail' => __( 'How it works: tick anonymous when posting, and the listing shows as Anonymous with no name or logo, the role is hidden from your organisation’s public page, and your name is kept out of the listing’s search-engine data. Participant requests are anonymous by default.', 'shuffles-social-services-jobs' ),
				'sc'    => '[sssj_policies]',
				'key'   => 'page_policies',
			),
		);
		return apply_filters( 'shuffles_ssj_spotlight_features', $f );
	}

	/** The feature to show today (deterministic by day of the year, so it changes daily). */
	public static function today() {
		$all = self::features();
		if ( empty( $all ) ) {
			return null;
		}
		$day = (int) current_time( 'z' ); // 0..365
		$idx = $day % count( $all );
		return $all[ $idx ];
	}

	/**
	 * Resolve a feature's deep link to a real, existing page. Returns '' when nothing suitable
	 * exists yet (the caller then hides the link rather than sending people to a dead end).
	 * It deliberately never falls back to the home page, since the tile often sits on the home page.
	 */
	public static function link_for( $feature ) {
		if ( ! empty( $feature['special'] ) && 'affiliate' === $feature['special'] ) {
			return class_exists( 'Shuffles_SSJ_Affiliate' ) ? (string) Shuffles_SSJ_Affiliate::url() : '';
		}
		if ( ! class_exists( 'Shuffles_SSJ_Shortcodes' ) ) {
			return '';
		}
		// 1) The feature's own page, if it exists.
		if ( ! empty( $feature['sc'] ) ) {
			$url = Shuffles_SSJ_Shortcodes::page_link( isset( $feature['key'] ) ? $feature['key'] : '', $feature['sc'] );
			if ( $url ) {
				return (string) $url;
			}
		}
		// 2) Otherwise a rich "explains everything" page if one has been published: Marketing, then
		//    Why us, then How it works. If none exist, return '' so the link is hidden.
		foreach ( array(
			array( 'page_marketing', '[sssj_marketing]' ),
			array( 'page_why_us', '[sssj_why_us]' ),
			array( 'page_workflows', '[sssj_workflows]' ),
		) as $fallback ) {
			$url = Shuffles_SSJ_Shortcodes::page_link( $fallback[0], $fallback[1] );
			if ( $url ) {
				return (string) $url;
			}
		}
		return '';
	}

	/**
	 * Render the full-width spotlight tile.
	 *
	 * @param array $atts title (optional override of the section heading).
	 * @return string
	 */
	public static function render( $atts = array() ) {
		$atts    = is_array( $atts ) ? $atts : array();
		$heading = ! empty( $atts['title'] ) ? (string) $atts['title'] : __( 'Today\'s Highlighted Site Feature', 'shuffles-social-services-jobs' );
		$feature = self::today();
		if ( ! $feature ) {
			return '';
		}
		$url      = self::link_for( $feature );
		$endpoint = esc_url( rest_url( 'sssj/v1/spotlight' ) );

		ob_start();
		?>
		<div class="sssj sssj--spotlight">
			<div class="sssj-spotlight" data-sssj-spotlight data-spot-endpoint="<?php echo $endpoint; // phpcs:ignore WordPress.Security.EscapeOutput ?>">
				<button type="button" class="sssj-spotlight__ctrl" data-spot-ctrl aria-pressed="false" title="<?php esc_attr_e( 'Tap once to stop or start the light. Double-tap to reverse it.', 'shuffles-social-services-jobs' ); ?>"><span class="sssj-spotlight__ctrl-icon" data-spot-icon aria-hidden="true">&#9208;</span> <span data-spot-label><?php esc_html_e( 'Pause', 'shuffles-social-services-jobs' ); ?></span></button>
				<div class="sssj-spotlight__inner">
					<p class="sssj-spotlight__eyebrow">✨ <?php echo esc_html( $heading ); ?></p>
					<div class="sssj-spotlight__dyn" data-spot-content>
						<?php echo self::feature_content_html( $feature, $url ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * The day-specific inner content (title, text, learn-more link, footer line). Rendered both
	 * server-side (as a no-JS fallback) and returned by the REST endpoint so the client can refresh
	 * it on every load, which keeps the daily feature correct even behind a full-page cache.
	 *
	 * @return string
	 */
	public static function feature_content_html( $feature, $url = '' ) {
		$detail = isset( $feature['detail'] ) ? (string) $feature['detail'] : '';
		ob_start();
		?>
		<h3 class="sssj-spotlight__title"><?php echo esc_html( $feature['title'] ); ?></h3>
		<p class="sssj-spotlight__text"><?php echo esc_html( $feature['text'] ); ?></p>
		<?php if ( '' !== $detail ) : ?>
			<p class="sssj-spotlight__cta">
				<button type="button" class="sssj-btn sssj-btn--primary sssj-btn--sm" data-spot-more aria-expanded="false"><?php esc_html_e( 'Learn more about this feature', 'shuffles-social-services-jobs' ); ?></button>
			</p>
			<div class="sssj-spotlight__detail" data-spot-detail hidden>
				<p><?php echo esc_html( $detail ); ?></p>
			</div>
		<?php endif; ?>
		<p class="sssj-spotlight__more"><?php esc_html_e( 'If you want another feature to investigate, come back tomorrow.', 'shuffles-social-services-jobs' ); ?></p>
		<?php
		return ob_get_clean();
	}

	/** Register the public REST route that serves today's feature (cache-immune). */
	public static function register_rest() {
		register_rest_route(
			'sssj/v1',
			'/spotlight',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_feature' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/** REST callback: today's feature content HTML, with no-store headers so caches never freeze it. */
	public static function rest_feature() {
		$feature = self::today();
		$html    = $feature ? self::feature_content_html( $feature, self::link_for( $feature ) ) : '';
		$resp    = new WP_REST_Response( array( 'html' => $html ) );
		$resp->header( 'Cache-Control', 'no-store, max-age=0' );
		return $resp;
	}

	/** [sssj_feature_today] */
	public static function shortcode( $atts ) {
		wp_enqueue_style( 'sssj' );
		wp_enqueue_script( 'sssj-spotlight', SHUFFLES_SSJ_URL . 'public/assets/js/sssj-spotlight.js', array(), SHUFFLES_SSJ_VERSION, true );
		return self::render( is_array( $atts ) ? $atts : array() );
	}
}
