<?php
/**
 * Plugin Name: HellaZ SiteZ Analyzer
 * Description: Analyze websites for metadata, open graph, server, security, technology stack, social media, rss feeds, contact information and more.
 * Version: 1.0.1
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: Hellaz
 * Author URI: https://github.com/hellaz
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hellaz-sitez-analyzer
 * Domain Path: /languages
 * Network: true
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('HSZ_PLUGIN_VERSION', '1.0.1');
define('HSZ_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('HSZ_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HSZ_PLUGIN_DIR', HSZ_PLUGIN_PATH);
define('HSZ_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'HSZ\\';
    $base_dir = HSZ_PLUGIN_PATH . 'includes/';
    $len = strlen($prefix);

    // Check if the class belongs to the HSZ namespace
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
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
                <p><?php echo esc_html__('HellaZ SiteZ Analyzer failed to initialize. Error: ', 'hellaz-sitez-analyzer') . esc_html($e->getMessage()); ?></p>
            </div>
            <?php
        });
    }
});

// Activation hook
register_activation_hook(__FILE__, function() {
    // Create any necessary database tables or options
    add_option('hsz_plugin_version', HSZ_PLUGIN_VERSION);
    
    // Flush rewrite rules
    flush_rewrite_rules();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Clean up temporary data
    wp_cache_flush();
});

// Enqueue block editor assets
add_action('enqueue_block_editor_assets', function () {
    wp_enqueue_script(
        'hsz-gutenberg-block',
        plugins_url('assets/js/scripts.js', __FILE__),
        ['wp-blocks', 'wp-components', 'wp-element', 'wp-editor', 'wp-i18n'],
        HSZ_PLUGIN_VERSION,
        true
    );
    // Add script translation support
    wp_set_script_translations('hsz-gutenberg-block', 'hellaz-sitez-analyzer');
});

// Enqueue admin styles
add_action('admin_enqueue_scripts', function () {
    wp_enqueue_style(
        'hsz-admin-styles',
        plugins_url('assets/css/admin-styles.css', __FILE__),
        [],
        HSZ_PLUGIN_VERSION
    );
});

// Enqueue frontend styles
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'hsz-frontend-styles',
        plugins_url('assets/css/styles.css', __FILE__),
        [],
        HSZ_PLUGIN_VERSION
    );
});
