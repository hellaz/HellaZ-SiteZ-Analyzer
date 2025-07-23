<?php
/**
 * Plugin Name: HellaZ SiteZ Analyzer
 * Description: Analyze websites for metadata, social profiles, feeds, technology, and more.
 * Version: 1.0.0
 * Author: HellaZ
 * License: GPL2+
 * Text Domain: hellaz-sitez-analyzer
 */

if (!defined('ABSPATH')) exit;

define('HSZ_PLUGIN_VERSION', '1.0.0');
define('HSZ_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('HSZ_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HSZ_PLUGIN_BASENAME', plugin_basename(__FILE__));

spl_autoload_register(function ($class) {
    $prefix = 'HSZ\\';
    $base_dir = HSZ_PLUGIN_PATH . 'includes/';
    if (strpos($class, $prefix) === 0) {
        $relative = strtolower(str_replace('\\', '-', substr($class, strlen($prefix))));
        $file = $base_dir . 'class-hsz-' . $relative . '.php';
        if (file_exists($file)) require_once $file;
    }
});

register_activation_hook(__FILE__, array('HSZ\\Core', 'activate'));
register_deactivation_hook(__FILE__, array('HSZ\\Core', 'deactivate'));

// Boot plugin
add_action('plugins_loaded', function() {
    HSZ\Core::init();
});
