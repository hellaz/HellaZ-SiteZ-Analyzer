<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * This script removes all data created by the HellaZ SiteZ Analyzer plugin,
 * including options, transients, and custom database tables, ensuring a clean uninstall.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.0.0
 */

// If uninstall is not called from WordPress, exit immediately.
// This is a security measure to prevent direct access to the file.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// 1. Delete plugin options from the wp_options table.
$option_names = [
	'hsz_setting_fallback_image',
	// Add other plugin option names here if any are created in the future.
];

foreach ( $option_names as $option_name ) {
	delete_option( $option_name );
}

// 2. Clean up all transients associated with the plugin.
// The '\' escapes the '_' which is a wildcard character in SQL LIKE clauses.
// This ensures we only delete transients with the 'hsz_' prefix.
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_hsz_%' OR option_name LIKE '_transient_timeout_hsz_%'"
);
// 3. Drop custom database tables.
$tables = [
	$wpdb->prefix . 'hsz_bulk_batches',
	$wpdb->prefix . 'hsz_bulk_results',
	$wpdb->prefix . 'hsz_analysis_cache',
	$wpdb->prefix . 'hsz_error_log',
];

foreach ( $tables as $table ) {
	// The variable $table is safe as it's from a hardcoded array.
	// Wrapping table name in backticks is a best practice.
	$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
}
