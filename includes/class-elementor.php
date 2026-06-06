<?php
/**
 * Elementor integration (standalone-first). Registers a "Shuffles Jobs" widget category and a pack
 * of drag-and-drop widgets that wrap the [sssj_*] display shortcodes. The hooks used here only ever
 * fire when Elementor is active, so there is no hard dependency and nothing loads otherwise.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Elementor {

	public function register() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'add_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
	}

	/** Add a dedicated category so the widgets group together in the Elementor panel. */
	public function add_category( $elements_manager ) {
		$elements_manager->add_category(
			'shuffles-jobs',
			array(
				'title' => __( 'Shuffles Jobs', 'shuffles-social-services-jobs' ),
				'icon'  => 'eicon-products',
			)
		);
	}

	/** Register each widget (Elementor 3.5+ uses register(); older uses register_widget_type()). */
	public function register_widgets( $widgets_manager ) {
		require_once SHUFFLES_SSJ_DIR . 'includes/elementor-widgets.php';
		$classes = array(
			'Shuffles_SSJ_EW_Hero',
			'Shuffles_SSJ_EW_Stats',
			'Shuffles_SSJ_EW_Featured',
			'Shuffles_SSJ_EW_Recent',
			'Shuffles_SSJ_EW_Menu',
		);
		foreach ( $classes as $class ) {
			if ( ! class_exists( $class ) ) {
				continue;
			}
			$widget = new $class();
			if ( method_exists( $widgets_manager, 'register' ) ) {
				$widgets_manager->register( $widget );
			} elseif ( method_exists( $widgets_manager, 'register_widget_type' ) ) {
				$widgets_manager->register_widget_type( $widget );
			}
		}
	}
}
