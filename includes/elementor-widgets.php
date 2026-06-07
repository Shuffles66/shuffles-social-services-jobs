<?php
/**
 * Elementor widget classes for the Shuffles Jobs display shortcodes. This file is required ONLY
 * from Shuffles_SSJ_Elementor::register_widgets(), i.e. only when Elementor is loaded — so the
 * \Elementor\Widget_Base base class is guaranteed to exist. Each widget is a thin wrapper that
 * maps visual controls onto the matching [sssj_*] shortcode (the single source of truth for output).
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

abstract class Shuffles_SSJ_Elementor_Widget extends \Elementor\Widget_Base {

	public function get_categories() {
		return array( 'shuffles-jobs' );
	}

	public function get_icon() {
		return 'eicon-products';
	}

	/** Build a safe shortcode attribute (strips characters that would break the shortcode). */
	protected function sc_att( $key, $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}
		$value = str_replace( array( '"', '[', ']' ), '', $value );
		return ' ' . $key . '="' . $value . '"';
	}

	protected function url_val( $setting ) {
		if ( is_array( $setting ) && isset( $setting['url'] ) ) {
			return (string) $setting['url'];
		}
		return (string) $setting;
	}
}

class Shuffles_SSJ_EW_Hero extends Shuffles_SSJ_Elementor_Widget {
	public function get_name() {
		return 'sssj_hero';
	}
	public function get_title() {
		return __( 'Jobs: Hero banner', 'shuffles-social-services-jobs' );
	}
	public function get_icon() {
		return 'eicon-banner';
	}
	protected function register_controls() {
		$this->start_controls_section( 'sec', array( 'label' => __( 'Hero', 'shuffles-social-services-jobs' ) ) );
		$this->add_control( 'title', array( 'label' => __( 'Headline', 'shuffles-social-services-jobs' ), 'type' => \Elementor\Controls_Manager::TEXT, 'label_block' => true ) );
		$this->add_control( 'subtitle', array( 'label' => __( 'Sub-text', 'shuffles-social-services-jobs' ), 'type' => \Elementor\Controls_Manager::TEXTAREA ) );
		$this->add_control( 'button_text', array( 'label' => __( 'Button text', 'shuffles-social-services-jobs' ), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __( 'Browse jobs', 'shuffles-social-services-jobs' ) ) );
		$this->add_control( 'button_url', array( 'label' => __( 'Button link', 'shuffles-social-services-jobs' ), 'type' => \Elementor\Controls_Manager::URL, 'default' => array( 'url' => '' ) ) );
		$this->add_control( 'button2_text', array( 'label' => __( 'Second button text', 'shuffles-social-services-jobs' ), 'type' => \Elementor\Controls_Manager::TEXT ) );
		$this->add_control( 'button2_url', array( 'label' => __( 'Second button link', 'shuffles-social-services-jobs' ), 'type' => \Elementor\Controls_Manager::URL, 'default' => array( 'url' => '' ) ) );
		$this->end_controls_section();
	}
	protected function render() {
		$s  = $this->get_settings_for_display();
		$sc = '[sssj_hero'
			. $this->sc_att( 'title', $s['title'] )
			. $this->sc_att( 'subtitle', $s['subtitle'] )
			. $this->sc_att( 'button_text', $s['button_text'] )
			. $this->sc_att( 'button_url', $this->url_val( $s['button_url'] ) )
			. $this->sc_att( 'button2_text', $s['button2_text'] )
			. $this->sc_att( 'button2_url', $this->url_val( $s['button2_url'] ) )
			. ']';
		echo do_shortcode( $sc ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

class Shuffles_SSJ_EW_Stats extends Shuffles_SSJ_Elementor_Widget {
	public function get_name() {
		return 'sssj_stats';
	}
	public function get_title() {
		return __( 'Jobs: Animated stats', 'shuffles-social-services-jobs' );
	}
	public function get_icon() {
		return 'eicon-counter';
	}
	protected function register_controls() {
		$this->start_controls_section( 'sec', array( 'label' => __( 'Counters', 'shuffles-social-services-jobs' ) ) );
		$this->add_control( 'title', array( 'label' => __( 'Heading', 'shuffles-social-services-jobs' ), 'type' => \Elementor\Controls_Manager::TEXT ) );
		foreach ( array( 'jobs' => __( 'Open jobs', 'shuffles-social-services-jobs' ), 'workers' => __( 'Available workers', 'shuffles-social-services-jobs' ), 'orgs' => __( 'Organisations', 'shuffles-social-services-jobs' ), 'placed' => __( 'People placed', 'shuffles-social-services-jobs' ) ) as $k => $l ) {
			$this->add_control( 'show_' . $k, array( 'label' => $l, 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		}
		$this->end_controls_section();
	}
	protected function render() {
		$s    = $this->get_settings_for_display();
		$show = array();
		foreach ( array( 'jobs', 'workers', 'orgs', 'placed' ) as $k ) {
			if ( 'yes' === ( $s[ 'show_' . $k ] ?? '' ) ) {
				$show[] = $k;
			}
		}
		$sc = '[sssj_stats' . $this->sc_att( 'show', implode( ',', $show ) ) . $this->sc_att( 'title', $s['title'] ) . ']';
		echo do_shortcode( $sc ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

class Shuffles_SSJ_EW_Featured extends Shuffles_SSJ_Elementor_Widget {
	public function get_name() {
		return 'sssj_featured';
	}
	public function get_title() {
		return __( 'Jobs: Featured roles', 'shuffles-social-services-jobs' );
	}
	public function get_icon() {
		return 'eicon-star';
	}
	protected function register_controls() {
		$this->start_controls_section( 'sec', array( 'label' => __( 'Featured roles', 'shuffles-social-services-jobs' ) ) );
		$this->add_control( 'title', array( 'label' => __( 'Heading', 'shuffles-social-services-jobs' ), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __( 'Featured roles', 'shuffles-social-services-jobs' ) ) );
		$this->add_control( 'count', array( 'label' => __( 'How many', 'shuffles-social-services-jobs' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 3, 'min' => 1, 'max' => 12 ) );
		$this->end_controls_section();
	}
	protected function render() {
		$s  = $this->get_settings_for_display();
		$sc = '[sssj_featured' . $this->sc_att( 'count', (string) (int) $s['count'] ) . $this->sc_att( 'title', $s['title'] ) . ']';
		echo do_shortcode( $sc ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

class Shuffles_SSJ_EW_Recent extends Shuffles_SSJ_Elementor_Widget {
	public function get_name() {
		return 'sssj_recent';
	}
	public function get_title() {
		return __( 'Jobs: Recent items', 'shuffles-social-services-jobs' );
	}
	public function get_icon() {
		return 'eicon-post-list';
	}
	protected function register_controls() {
		$this->start_controls_section( 'sec', array( 'label' => __( 'Recent items', 'shuffles-social-services-jobs' ) ) );
		$this->add_control( 'title', array( 'label' => __( 'Heading', 'shuffles-social-services-jobs' ), 'type' => \Elementor\Controls_Manager::TEXT ) );
		$this->add_control( 'type', array(
			'label'   => __( 'Show', 'shuffles-social-services-jobs' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'jobs',
			'options' => array(
				'jobs'    => __( 'Jobs', 'shuffles-social-services-jobs' ),
				'workers' => __( 'Workers', 'shuffles-social-services-jobs' ),
				'orgs'    => __( 'Organisations', 'shuffles-social-services-jobs' ),
				'needs'   => __( 'Participant requests (logged-in)', 'shuffles-social-services-jobs' ),
			),
		) );
		$this->add_control( 'layout', array(
			'label'   => __( 'Layout', 'shuffles-social-services-jobs' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'grid',
			'options' => array( 'grid' => __( 'Grid (cards)', 'shuffles-social-services-jobs' ), 'list' => __( 'List (compact / sidebar)', 'shuffles-social-services-jobs' ) ),
		) );
		$this->add_control( 'count', array( 'label' => __( 'How many', 'shuffles-social-services-jobs' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 6, 'min' => 1, 'max' => 30 ) );
		$this->end_controls_section();
	}
	protected function render() {
		$s  = $this->get_settings_for_display();
		$sc = '[sssj_recent'
			. $this->sc_att( 'type', $s['type'] )
			. $this->sc_att( 'layout', $s['layout'] )
			. $this->sc_att( 'count', (string) (int) $s['count'] )
			. $this->sc_att( 'title', $s['title'] )
			. ']';
		echo do_shortcode( $sc ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

class Shuffles_SSJ_EW_Spotlight extends Shuffles_SSJ_Elementor_Widget {
	public function get_name() {
		return 'sssj_feature_today';
	}
	public function get_title() {
		return __( 'Jobs: Today’s feature spotlight', 'shuffles-social-services-jobs' );
	}
	public function get_icon() {
		return 'eicon-flash';
	}
	protected function register_controls() {
		$this->start_controls_section( 'sec', array( 'label' => __( 'Feature spotlight', 'shuffles-social-services-jobs' ) ) );
		$this->add_control(
			'about',
			array(
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw'  => esc_html__( 'Shows one built-in feature per day, rotating daily, in a full-width tile with a brief rainbow tracing border and a “Learn more” link.', 'shuffles-social-services-jobs' ),
			)
		);
		$this->add_control( 'title', array( 'label' => __( 'Heading (optional)', 'shuffles-social-services-jobs' ), 'type' => \Elementor\Controls_Manager::TEXT, 'label_block' => true, 'placeholder' => __( 'Today’s Highlighted Site Feature', 'shuffles-social-services-jobs' ) ) );
		$this->end_controls_section();
	}
	protected function render() {
		$s  = $this->get_settings_for_display();
		$sc = '[sssj_feature_today' . $this->sc_att( 'title', $s['title'] ) . ']';
		echo do_shortcode( $sc ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

class Shuffles_SSJ_EW_Menu extends Shuffles_SSJ_Elementor_Widget {
	public function get_name() {
		return 'sssj_menu';
	}
	public function get_title() {
		return __( 'Jobs: Navigation menu', 'shuffles-social-services-jobs' );
	}
	public function get_icon() {
		return 'eicon-nav-menu';
	}
	protected function register_controls() {
		$this->start_controls_section( 'sec', array( 'label' => __( 'Navigation', 'shuffles-social-services-jobs' ) ) );
		$this->add_control( 'title', array( 'label' => __( 'Brand text (optional)', 'shuffles-social-services-jobs' ), 'type' => \Elementor\Controls_Manager::TEXT ) );
		$this->end_controls_section();
	}
	protected function render() {
		$s  = $this->get_settings_for_display();
		$sc = '[sssj_menu' . $this->sc_att( 'title', $s['title'] ) . ']';
		echo do_shortcode( $sc ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
