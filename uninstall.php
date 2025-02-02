<?php
/**
 * Plugin uninstallation script for HellaZ SiteZ Analyzer.
 // Cleanup script for uninstallation
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit; // Exit if accessed directly
}

// Delete plugin options
delete_option('hsz_enable_disclaimer');

// Delete transients (cached data)
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'hsz_%'");

// Delete custom post meta (if applicable)
$posts = get_posts([
    'post_type' => 'any',
    'meta_key'  => '_hsz_metadata',
    'numberposts' => -1,
]);

foreach ($posts as $post) {
    delete_post_meta($post->ID, '_hsz_metadata');
}
