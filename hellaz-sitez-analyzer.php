<?php
/**
 * Plugin Name: HellaZ SiteZ Analyzer
 * Description: Analyze websites for metadata, security, and more.
 * Version: 1.0
 * Author: Your Name
 * Text Domain: hellaz-sitez-analyzer
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('HSZ_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('HSZ_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload classes
spl_autoload_register(function ($class) {
    $namespace = 'HSZ\\';
    if (strpos($class, $namespace) === 0) {
        $class_name = str_replace($namespace, '', $class);
        $file = HSZ_PLUGIN_PATH . 'includes/' . strtolower($class_name) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Initialize the plugin
add_action('plugins_loaded', function () {
    // Load text domain for translations
    load_plugin_textdomain('hellaz-sitez-analyzer', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Initialize Gutenberg block
    if (class_exists('HSZ\Gutenberg')) {
        new \HSZ\Gutenberg();
    }

    // Initialize shortcode
    if (class_exists('HSZ\Shortcode')) {
        new \HSZ\Shortcode();
    }

    // Initialize settings page
    if (class_exists('HSZ\Settings')) {
        new \HSZ\Settings();
    }
});

// Enqueue styles and scripts
add_action('wp_enqueue_scripts', function () {
    // Enqueue Font Awesome
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css', [], '6.0.0');

    // Enqueue plugin-specific styles
    wp_enqueue_style('hsz-styles', HSZ_PLUGIN_URL . 'assets/css/style.css', [], '1.0');
});
