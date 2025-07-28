<?php
/**
 * Plugin Name:       HellaZ SiteZ Analyzer
 * Plugin URI:        https://www.hellaz.net/
 * Description:       Analyzes a website's on-page SEO and metadata.
 * Version:           1.0.1
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
define( 'HSZ_VERSION', '1.0.1' );
define( 'HSZ_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'HSZ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoloader for the plugin's classes.
 *
 * This PSR-4 autoloader dynamically loads classes as they are needed,
 * improving performance and maintainability. It follows WordPress naming conventions.
 *
 * @param string $class The fully-qualified class name.
 */
spl_autoload_register(
	function ( $class ) {
		// The namespace prefix for this plugin.
		$prefix = 'HSZ\\';

		// The base directory for the namespace prefix.
		$base_dir = __DIR__ . '/includes/';

		// Check if the class uses the namespace prefix.
		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			// Not a class from this plugin, move to the next registered autoloader.
			return;
		}

		// Get the relative class name.
		$relative_class = substr( $class, $len );

		// Replace the namespace prefix with the base directory, replace namespace
		// separators with directory separators, and append with .php.
		// The file names are in the format: class-hsz-classname.php
		$file = $base_dir . 'class-hsz-' . str_replace( '_', '-', strtolower( $relative_class ) ) . '.php';

		// If the file exists, require it.
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 * We must require this file directly as it is the entry point.
 */
require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-core.php';

/**
 * Begins execution of the plugin.
 *
 * This function is the primary entry point for the plugin's execution.
 * It ensures that the Core class is instantiated and its run method is called.
 *
 * @since 1.0.0
 */
function run_hellaz_sitez_analyzer() {
	$plugin = HSZ\Core::instance();
	// **CRITICAL FIX**: Calls the correct `run()` method.
	$plugin->run();
}

// Register the activation and deactivation hooks.
// These hooks point to static methods within the Core class.
register_activation_hook( __FILE__, [ 'HSZ\\Core', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'HSZ\\Core', 'deactivate' ] );

// Finally, run the plugin.
run_hellaz_sitez_analyzer();
