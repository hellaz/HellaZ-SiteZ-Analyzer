<?php
/**
 * Plugin Name: HellaZ SiteZ Analyzer
 * Description: Analyze websites for metadata, security, and technology stack.
 * Version: 1.0.0
 * Author: Hellaz
 * License: GPL-2.0+
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('HSZ_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('HSZ_PLUGIN_URL', plugin_dir_url(__FILE__));

// Explicitly include critical files
require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-api-manager.php';
require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-utils.php';

// Autoloader for classes in the HSZ namespace
spl_autoload_register(function ($class) {
    $prefix = 'HSZ\\';
    $base_dir = HSZ_PLUGIN_PATH . 'includes/';

    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Convert camelCase to kebab-case (e.g., SocialMedia → social-media)
    $file_name = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $relative_class));

    // Prepend "class-hsz-" to the file name
    $file = $base_dir . 'class-hsz-' . $file_name . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require_once $file;
    } else {
        error_log("Autoloader could not find file for class: $class");
    }
});

// Initialize the plugin
add_action('plugins_loaded', function () {
    if (!class_exists('HSZ\Core')) {
        return;
    }
    \HSZ\Core::init();
});
