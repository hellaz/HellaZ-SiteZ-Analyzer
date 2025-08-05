<?php
/**
 * Plugin Name: HellaZ SiteZ Analyzer
 * Plugin URI: https://www.hellaz.net/
 * Description: Comprehensive website analysis tool with metadata extraction, performance analysis, security scanning, and website preview capabilities.
 * Version: 1.0.2
 * Author: HellaZ
 * Author URI: https://www.hellaz.net/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: hellaz-sitez-analyzer
 * Domain Path: /languages
 *
 * @package HellaZ_SiteZ_Analyzer
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants for version, path, and URL.
define( 'HSZ_VERSION', '1.0.2' );
define( 'HSZ_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'HSZ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Enhanced constants for Phase 1 features
define( 'HSZ_PLUGIN_FILE', __FILE__ );
define( 'HSZ_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'HSZ_INCLUDES_PATH', HSZ_PLUGIN_PATH . 'includes/' );
define( 'HSZ_ASSETS_URL', HSZ_PLUGIN_URL . 'assets/' );
define( 'HSZ_TEMPLATES_PATH', HSZ_PLUGIN_PATH . 'templates/' );

// Upload directory constants for screenshots and reports
$upload_dir = wp_upload_dir();
define( 'HSZ_UPLOAD_DIR', $upload_dir['basedir'] . '/hellaz-sitez-analyzer/' );
define( 'HSZ_UPLOAD_URL', $upload_dir['baseurl'] . '/hellaz-sitez-analyzer/' );

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
		$prefix = 'HSZ\\';
		$base_dir = __DIR__ . '/includes/';
		$len = strlen( $prefix );

		// Check if the class uses the namespace prefix.
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return; // Not a class from this plugin.
		}

		$relative_class = substr( $class, $len );

		// **ENHANCED**: Convert CamelCase to kebab-case for the filename.
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
 * Check plugin requirements before initialization
 *
 * @return bool True if requirements are met
 */
function hsz_check_requirements() {
	$requirements = [
		'php_version' => '7.4',
		'wp_version' => '5.0',
		'extensions' => ['curl', 'json']
	];

	// Check PHP version
	if ( version_compare( PHP_VERSION, $requirements['php_version'], '<' ) ) {
		add_action( 'admin_notices', 'hsz_php_version_notice' );
		return false;
	}

	// Check WordPress version
	global $wp_version;
	if ( version_compare( $wp_version, $requirements['wp_version'], '<' ) ) {
		add_action( 'admin_notices', 'hsz_wp_version_notice' );
		return false;
	}

	// Check required PHP extensions
	foreach ( $requirements['extensions'] as $extension ) {
		if ( ! extension_loaded( $extension ) ) {
			add_action( 'admin_notices', 'hsz_extensions_notice' );
			return false;
		}
	}

	return true;
}

/**
 * Display admin notice for PHP version requirement
 */
function hsz_php_version_notice() {
	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'HellaZ SiteZ Analyzer requires PHP 7.4 or higher. Please update your PHP version.', 'hellaz-sitez-analyzer' );
	echo '</p></div>';
}

/**
 * Display admin notice for WordPress version requirement
 */
function hsz_wp_version_notice() {
	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'HellaZ SiteZ Analyzer requires WordPress 5.0 or higher. Please update your WordPress installation.', 'hellaz-sitez-analyzer' );
	echo '</p></div>';
}

/**
 * Display admin notice for PHP extensions requirement
 */
function hsz_extensions_notice() {
	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'HellaZ SiteZ Analyzer requires cURL and JSON PHP extensions. Please contact your hosting provider.', 'hellaz-sitez-analyzer' );
	echo '</p></div>';
}

/**
 * Initialize plugin upload directories
 */
function hsz_init_upload_directories() {
	$directories = [
		HSZ_UPLOAD_DIR,
		HSZ_UPLOAD_DIR . 'screenshots/',
		HSZ_UPLOAD_DIR . 'reports/',
		HSZ_UPLOAD_DIR . 'cache/',
		HSZ_UPLOAD_DIR . 'logs/'
	];

	foreach ( $directories as $dir ) {
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
			
			// Add security files
			$htaccess_content = "Order deny,allow\nDeny from all\n";
			if ( $dir !== HSZ_UPLOAD_DIR . 'screenshots/' ) {
				file_put_contents( $dir . '.htaccess', $htaccess_content );
			}
			
			// Add index.php for security
			file_put_contents( $dir . 'index.php', '<?php // Silence is golden' );
		}
	}
}

// Initialize upload directories early
add_action( 'init', 'hsz_init_upload_directories' );

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
	// Check requirements before running
	if ( ! hsz_check_requirements() ) {
		return;
	}

	$plugin = HSZ\Core::instance();
	$plugin->run();
}

// Register the activation and deactivation hooks.
register_activation_hook( __FILE__, [ 'HSZ\\Core', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'HSZ\\Core', 'deactivate' ] );

// Finally, run the plugin.
run_hellaz_sitez_analyzer();

/**
 * Global helper functions for enhanced features
 */

/**
 * Get plugin instance
 *
 * @return HSZ\Core|null
 */
function hsz_get_instance() {
	return HSZ\Core::instance();
}

/**
 * Log debug information
 *
 * @param string $message Log message
 * @param string $level Log level (info, warning, error)
 * @param array $context Additional context
 */
function hsz_log( $message, $level = 'info', $context = [] ) {
	if ( class_exists( 'HSZ\\Utils' ) ) {
		HSZ\Utils::log_error( $message, __FILE__, __LINE__ );
	}
}

/**
 * Check if specific feature is enabled
 *
 * @param string $feature Feature name
 * @return bool
 */
function hsz_is_feature_enabled( $feature ) {
	$enabled_features = [
		'performance_analysis' => get_option( 'hsz_performance_analysis_enabled', true ),
		'security_analysis' => get_option( 'hsz_security_analysis_enabled', true ),
		'preview_generation' => get_option( 'hsz_preview_generation_enabled', true ),
		'grading_system' => get_option( 'hsz_grading_system_enabled', true ),
	];

	return isset( $enabled_features[ $feature ] ) ? $enabled_features[ $feature ] : false;
}

/**
 * Get API configuration for a service
 *
 * @param string $service Service name
 * @return array API configuration
 */
function hsz_get_api_config( $service ) {
	$config = [
		'enabled' => get_option( "hsz_{$service}_enabled", false ),
		'api_key' => get_option( "hsz_{$service}_api_key", '' ),
	];

	// Decrypt API key if encryption is available
	if ( ! empty( $config['api_key'] ) && class_exists( 'HSZ\\Utils' ) ) {
		$decrypted_key = HSZ\Utils::decrypt( $config['api_key'] );
		if ( $decrypted_key !== false ) {
			$config['api_key'] = $decrypted_key;
		}
	}

	return $config;
}
