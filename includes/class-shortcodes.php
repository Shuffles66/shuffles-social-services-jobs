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
	}

	public function register_assets() {
		if ( ! wp_style_is( 'sssj', 'registered' ) ) {
			wp_register_style( 'sssj', SHUFFLES_SSJ_URL . 'public/assets/css/sssj.css', array(), SHUFFLES_SSJ_VERSION );
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

		$query = new WP_Query( Shuffles_SSJ_Query::base_args( $basis, $extra ) );

		ob_start();
		$this->load_template(
			'job-board.php',
			array(
				'query' => $query,
				'basis' => $basis,
				'atts'  => $atts,
			)
		);
		wp_reset_postdata();
		return ob_get_clean();
	}

	/* --- Posting form --- */

	public function post_job_form( $atts ) {
		wp_enqueue_style( 'sssj' );
		ob_start();
		$this->load_template( 'post-job-form.php', array( 'settings' => $this->settings ) );
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
