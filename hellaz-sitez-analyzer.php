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
define('HSZ_VERSION', '1.0.0'); // Version constant

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'HSZ\\';
    $base_dir = HSZ_PLUGIN_PATH . 'includes/';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return; // Not a class in the HSZ namespace
    }

    // Remove the namespace prefix and prepend "class-hsz-"
    $relative_class = substr($class, $len);
    $file = $base_dir . 'class-hsz-' . str_replace('\\', '-', strtolower($relative_class)) . '.php';

    if (file_exists($file)) {
        require_once $file;
    } else {
        error_log("Class file not found: $file"); // Debugging
    }
});

// Initialize the plugin
add_action('plugins_loaded', function () {
    try {
        \HSZ\Core::init();
    } catch (\Exception $e) {
        error_log('HellaZ SiteZ Analyzer initialization failed: ' . $e->getMessage());
        add_action('admin_notices', function () use ($e) {
            ?>
            <div class="notice notice-error">
                <p>
                    <?php echo esc_html__('HellaZ SiteZ Analyzer failed to initialize. Error: ', 'hellaz-sitez-analyzer') . esc_html($e->getMessage()); ?>
                </p>
            </div>
            <?php
        });
    }
});
