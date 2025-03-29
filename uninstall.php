<?php
/**
 * HellaZ SiteZ Analyzer Uninstall Script
 *
 * This script runs when the user deletes the plugin from the WordPress admin.
 * It cleans up options and transients created by the plugin.
 *
 * @package HellaZ_SiteZ_Analyzer
 */

// Exit if accessed directly or not during uninstall.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// --- Delete Plugin Options ---
// List all options that your plugin registers or uses.
$options_to_delete = [
    'hsz_fallback_image',       // From Settings -> General
    'hsz_enable_disclaimer',    // From Settings -> General
    'hsz_disclaimer_message',   // From Settings -> General
    'hsz_link_target',          // From Settings -> General
    'hsz_icon_library',         // From Settings -> General
    'hsz_custom_icon_path',     // From Settings -> General
    'hsz_virustotal_api_key',   // From Settings -> API Keys
    'hsz_builtwith_api_key',    // From Settings -> API Keys
    'hsz_urlscan_io_api_key',   // From Settings -> API Keys (Verify exact key name if service name varied)
    'hsz_cache_duration',       // From Settings -> Advanced
    // Add any other custom options your plugin might save here.
    // Example: 'hsz_some_other_setting'
];

// Loop through the options array and delete each one.
foreach ($options_to_delete as $option_name) {
    delete_option($option_name);
    // Optional: Log deletion for debugging (remove in final production if not needed)
    // error_log('[HellaZ SiteZ Analyzer Uninstall] Deleted option: ' . $option_name);
}


// --- Delete Plugin Transients ---
// Transients are temporary cached data.
global $wpdb;

// Define the patterns for plugin-specific transients.
// Note: Transients have prefixes '_transient_' and '_transient_timeout_'.
$transient_like         = '_transient_hsz_%';
$transient_timeout_like = '_transient_timeout_hsz_%';

// Prepare the SQL query to delete transients safely.
// $wpdb->options already includes the table prefix.
$sql = $wpdb->prepare(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
    $transient_like,
    $transient_timeout_like
);

// Execute the query.
$wpdb->query($sql);

// Optional: Log transient deletion for debugging (remove in final production if not needed)
// error_log('[HellaZ SiteZ Analyzer Uninstall] Attempted to delete transients matching: ' . $transient_like . ' OR ' . $transient_timeout_like);


// --- Delete Custom Post Meta (If Used) ---

 * If your plugin *does* store data in post meta, uncomment and adjust this section.
 * The original code checked for '_hsz_metadata', but this key wasn't found elsewhere
 * in the provided source. If you add post meta functionality later, re-implement this.
 */
/*
$meta_key_to_delete = '_hsz_metadata'; // Replace with your actual meta key

$posts = get_posts([
    'post_type'   => 'any', // Or specify relevant post types
    'meta_key'    => $meta_key_to_delete,
    'numberposts' => -1, // Process all relevant posts
    'fields'      => 'ids', // Only fetch IDs for efficiency
]);


if (!empty($posts)) {
    foreach ($posts as $post_id) {
        delete_post_meta($post_id, $meta_key_to_delete);
        // Optional: Log post meta deletion
        // error_log('[HellaZ SiteZ Analyzer Uninstall] Deleted post meta ' . $meta_key_to_delete . ' for post ID: ' . $post_id);
    }
}


// --- Delete Custom Database Tables (If Used) ---
/*
 * If your plugin creates custom database tables, add the code here to drop them.
 * Example:
 * global $wpdb;
 * $table_name = $wpdb->prefix . 'hsz_custom_data';
 * $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
 */

// --- Remove Scheduled Cron Jobs (If Used) ---
/*
 * If your plugin schedules cron events, unschedule them here.
 * Example:
 * $timestamp = wp_next_scheduled('hsz_daily_event_hook');
 * if ($timestamp) {
 *     wp_unschedule_event($timestamp, 'hsz_daily_event_hook');
 * }
 */

// --- Clear Any Other Plugin Data ---
/*
 * Add any other cleanup tasks specific to your plugin here.
 * For example, deleting custom files/folders (use with caution!).
 */

?>