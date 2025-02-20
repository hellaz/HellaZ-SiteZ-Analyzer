<?php
/**
 * Plugin uninstallation script for HellaZ SiteZ Analyzer.
 // Cleanup script for uninstallation
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit; // Exit if accessed directly
}

global $wpdb;

// Delete plugin options
$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", 'hsz_%'));

// Delete transients
$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_hsz_%'));
$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_hsz_%'));

// Delete custom post meta
$posts = get_posts([
    'post_type' => 'any',
    'meta_key'  => '_hsz_metadata',
    'numberposts' => -1,
]);

foreach ($posts as $post) {
    delete_post_meta($post->ID, '_hsz_metadata');
}
