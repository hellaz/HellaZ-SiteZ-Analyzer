<?php
/**
 * Plugin Name: HellaZ SiteZ Analyzer
 * Description: Analyze websites for metadata, open graph, server, security, technology stack, social media, rss fees, contact information and more.
 * Version: 1.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: Hellaz
 * Author URI: https://github.com/hellaz
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hellaz-sitez-analyzer
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('HSZ_PLUGIN_PATH', plugin_dir_path(__FILE__)); // Absolute server path to the plugin directory
define('HSZ_PLUGIN_URL', plugin_dir_url(__FILE__));   // URL to the plugin directory
define('HSZ_PLUGIN_DIR', HSZ_PLUGIN_PATH);           // Alias for HSZ_PLUGIN_PATH (for backward compatibility)

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'HSZ\\';
    $base_dir = HSZ_PLUGIN_PATH . 'includes/';
    $len = strlen($prefix);

    // Check if the class belongs to the HSZ namespace
    if (strncmp($prefix, $class, $len) !== 0) {
        return; // Not a class in the HSZ namespace
    }

    // Remove the namespace prefix
    $relative_class = substr($class, $len);

    // Convert camelCase to kebab-case (e.g., SocialMedia → social-media)
    $file_name = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $relative_class));

    // Prepend "class-hsz-" to the file name
    $file = $base_dir . 'class-hsz-' . $file_name . '.php';

    // If the file exists, require it
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

add_action('enqueue_block_editor_assets', function () {
    wp_enqueue_script(
        'hsz-gutenberg-block',
        plugins_url('assets/js/scripts.js', __FILE__),
        ['wp-blocks', 'wp-components', 'wp-element', 'wp-editor', 'wp-i18n'],
        filemtime(plugin_dir_path(__FILE__) . 'assets/js/scripts.js'),
        true
    );

    // Add script translation support
    wp_set_script_translations('hsz-gutenberg-block', 'hellaz-sitez-analyzer');
});
