// Main plugin file (entry point)

<?php
/**
 * Plugin Name: HellaZ SiteZ Analyzer
 * Description: A comprehensive remote website analysis plugin for WordPress.
 * Version: 1.0.0
 * Author: HellaZ Team
 * GitHub URI: https://github.com/hellaz/HellaZ-SiteZ-Analyzer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define constants
define('HSZ_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('HSZ_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'HSZ\\';
    $base_dir = HSZ_PLUGIN_PATH . 'includes/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize the plugin
add_action('plugins_loaded', function () {
    \HSZ\Core::init();
});
