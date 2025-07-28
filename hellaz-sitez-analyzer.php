<?php
/**
 * Plugin Name:       HellaZ SiteZ Analyzer
 * Plugin URI:        https://www.hellaz.net/
 * Description:       Analyzes a website's on-page SEO and metadata.
 * Version:           1.0.2
 * Author:            HellaZ
 * Author URI:        https://www.hellaz.net/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       hellaz-sitez-analyzer
 * Domain Path:       /languages
 *
 * @package           HellaZ_SiteZ_Analyzer
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants for version, path, and URL.
define( 'HSZ_VERSION', '1.0.2' );
define( 'HSZ_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'HSZ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Robust Autoloader for the plugin's classes.
 *
 * This PSR-4 autoloader dynamically loads classes as they are needed.
 * It correctly converts CamelCase class names into kebab-case file names,
 * which is the WordPress standard (e.g., `HSZ\AdminLogs` becomes `class-hsz-admin-logs.php`).
 *
 * @param string $class The fully-qualified class name.
 */
spl_autoload_register(
	function ( $class ) {
		$prefix   = 'HSZ\\';
		$base_dir = __DIR__ . '/includes/';
		$len      = strlen( $prefix );

		// Check if the class uses the namespace prefix.
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return; // Not a class from this plugin.
		}

		$relative_class = substr( $class, $len );

		// **CRITICAL FIX**: Convert CamelCase to kebab-case for the filename.
		// 1. Add a hyphen before capital letters: `AdminLogs` -> `Admin-Logs`
		// 2. Convert to lowercase: `admin-logs`
		$kebab_case = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $relative_class ) );
		
		$file = $base_dir . 'class-hsz-' . $kebab_case . '.php';

		// If the file exists, require it.
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

/**
 * The core plugin class file. We require it directly as it's the main entry point.
 */
require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-core.php';

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 */
function run_hellaz_sitez_analyzer() {
	$plugin = HSZ\Core::instance();
	$plugin->run();
}

// Register the activation and deactivation hooks.
register_activation_hook( __FILE__, [ 'HSZ\\Core', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'HSZ\\Core', 'deactivate' ] );

// Finally, run the plugin.
run_hellaz_sitez_analyzer();
