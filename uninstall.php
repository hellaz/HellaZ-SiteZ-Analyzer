<?php
/**
 * Uninstall script for HellaZ SiteZ Analyzer.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Remove plugin options
delete_option('hsz_fallback_image');
delete_option('hsz_fallback_title');
delete_option('hsz_fallback_description');
delete_option('hsz_disclaimer_enabled');
delete_option('hsz_disclaimer_message');
delete_option('hsz_virustotal_api_key');
delete_option('hsz_builtwith_api_key');
delete_option('hsz_urlscan_api_key');
delete_option('hsz_encryption_key');
delete_option('hsz_plugin_version');

// Remove transients
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_hsz_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_hsz_%'");

// Remove custom tables
$tables = [
    $wpdb->prefix . 'hsz_bulk_batches',
    $wpdb->prefix . 'hsz_bulk_results',
    $wpdb->prefix . 'hsz_analysis_cache',
    $wpdb->prefix . 'hsz_error_log'
];
foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}
