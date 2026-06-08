<?php
/**
 * Plugin Name:       Shuffles Social Services Jobs and Engagements
 * Description:       A four-sided work marketplace for disability, aged care and social services — ABN & TFN engagements, participant-safe, accessible. Phase 0 scaffold.
 * Version:           1.5.0
 * Author:            Shuffles
 * Author URI:        https://shuffles.com.au
 * Plugin URI:        https://github.com/Shuffles66/shuffles-social-services-jobs
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       shuffles-social-services-jobs
 * Domain Path:       /languages
 * Requires at least: 6.4
 * Requires PHP:      8.1
 *
 * Standalone-first: declares NO `Requires Plugins`. Every integration is optional and runtime-detected.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SHUFFLES_SSJ_VERSION', '1.5.0' );
define( 'SHUFFLES_SSJ_FILE', __FILE__ );
define( 'SHUFFLES_SSJ_DIR', plugin_dir_path( __FILE__ ) );
define( 'SHUFFLES_SSJ_URL', plugin_dir_url( __FILE__ ) );
define( 'SHUFFLES_SSJ_SLUG', 'shuffles-social-services-jobs' );
define( 'SHUFFLES_SSJ_BASENAME', plugin_basename( __FILE__ ) );

// --- Includes (manual, dependency order) ---
require_once SHUFFLES_SSJ_DIR . 'includes/class-settings.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-integrations.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-abn.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-org.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-org-team.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-ndis-register.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-roles.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-privacy-mask.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-taxonomy-registrar.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-cpt-registrar.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-taxonomy-seeder.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-activator.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-deactivator.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-geo.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-query.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-search.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-applications.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-i18n.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-license.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-messaging.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-credentials.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-resumes.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-reviews.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-verification.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-tests.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-guides.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-workflows.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-policies.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-ads.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-affiliate.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-support.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-spotlight.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-media.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-marketing.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-assets.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-promo.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-cache.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-business-rules.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-provider-import.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-nav-sync.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-field-registry.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-crm-sync.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-monetisation.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-seo.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-cron.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-cron-monitor.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-frontend-forms.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-matcher.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-alerts.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-shortcodes.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-display.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-elementor.php';
require_once SHUFFLES_SSJ_DIR . 'includes/class-plugin.php';

if ( is_admin() ) {
	require_once SHUFFLES_SSJ_DIR . 'admin/class-admin.php';
}

register_activation_hook( __FILE__, array( 'Shuffles_SSJ_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Shuffles_SSJ_Deactivator', 'deactivate' ) );

/**
 * Boot the plugin.
 *
 * @return Shuffles_SSJ_Plugin
 */
function shuffles_ssj() {
	return Shuffles_SSJ_Plugin::instance();
}

shuffles_ssj();

// Public REST route for the cache-immune feature spotlight (today's feature, served fresh).
add_action( 'rest_api_init', array( 'Shuffles_SSJ_Spotlight', 'register_rest' ) );

// Keep the Marketing master page out of search engines (members-only content).
add_filter( 'wp_robots', array( 'Shuffles_SSJ_Marketing', 'noindex_marketing' ) );

// Bust page caches when a job/worker/org/need is created or updated, so changes show immediately.
Shuffles_SSJ_Cache::register();
