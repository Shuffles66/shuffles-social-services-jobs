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
		add_shortcode( 'sssj_job_board', array( $this, 'board' ) );
		add_shortcode( 'sssj_tfn_board', array( $this, 'tfn_board' ) );
		add_shortcode( 'sssj_abn_board', array( $this, 'abn_board' ) );
		add_shortcode( 'sssj_post_job', array( $this, 'post_job_form' ) );
		add_shortcode( 'sssj_worker_directory', array( $this, 'worker_directory' ) );
		add_shortcode( 'sssj_post_worker', array( $this, 'post_worker_form' ) );
		add_shortcode( 'sssj_need_board', array( $this, 'need_board' ) );
		add_shortcode( 'sssj_post_need', array( $this, 'post_need_form' ) );
		add_shortcode( 'sssj_my_listings', array( $this, 'my_listings' ) );
		add_filter( 'the_content', array( $this, 'maybe_apply_panel' ) );
	}

	public function register_assets() {
		if ( ! wp_style_is( 'sssj', 'registered' ) ) {
			wp_register_style( 'sssj', SHUFFLES_SSJ_URL . 'public/assets/css/sssj.css', array(), SHUFFLES_SSJ_VERSION );
		}
		// Accessibility / CALD toolbar — master-gated. Loaded once; the JS no-ops if no .sssj surface is present.
		if ( '1' === (string) $this->settings->get( 'cald_enabled', '1' ) ) {
			wp_enqueue_script( 'sssj-a11y', SHUFFLES_SSJ_URL . 'public/assets/js/sssj-a11y.js', array(), SHUFFLES_SSJ_VERSION, true );
			wp_localize_script(
				'sssj-a11y',
				'SSJ_A11y',
				array(
					'lang'   => str_replace( '_', '-', get_locale() ),
					'langs'  => Shuffles_SSJ_I18n::langs(),
					'i18n'   => Shuffles_SSJ_I18n::map(),
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
			$extra['category'] = sanitize_title( wp_unslash( $_GET['sssj_cat'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( ! empty( $_GET['sssj_q'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$extra['s'] = sanitize_text_field( wp_unslash( $_GET['sssj_q'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		$this->read_radius( $extra );

		$query  = new WP_Query( Shuffles_SSJ_Query::base_args( $basis, $extra ) );
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
		if ( ! empty( $_GET['sssj_lat'] ) && ! empty( $_GET['sssj_lng'] ) && ! empty( $_GET['sssj_radius'] ) ) {
			$extra['lat']    = (float) $_GET['sssj_lat'];
			$extra['lng']    = (float) $_GET['sssj_lng'];
			$extra['radius'] = (float) $_GET['sssj_radius'];
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
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
			$extra['category'] = sanitize_title( wp_unslash( $_GET['sssj_cat'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( ! empty( $_GET['sssj_q'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$extra['s'] = sanitize_text_field( wp_unslash( $_GET['sssj_q'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( ! empty( $_GET['sssj_avail'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$extra['available'] = 1;
		}

		$query = new WP_Query( Shuffles_SSJ_Query::worker_args( $extra ) );
		ob_start();
		$this->load_template( 'worker-directory.php', array( 'query' => $query, 'atts' => $atts ) );
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

		$query   = new WP_Query( Shuffles_SSJ_Query::need_args( $extra ) );
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
