<?php
/**
 * Deactivation: tidy up rewrites + scheduled events. Never destroys data.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Deactivator {

	public static function deactivate() {
		wp_clear_scheduled_hook( 'shuffles_ssj_daily' );
		flush_rewrite_rules();
	}
}
