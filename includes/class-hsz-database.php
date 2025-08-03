<?php
/**
 * Database management class for HellaZ SiteZ Analyzer.
 *
 * Handles table creation and database versioning.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.0.0
 */

namespace HSZ;

use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Database {

	/** Plugin's database version. Increment on schema updates. */
	const DB_VERSION = '1.0.2';

	/** Table prefix used by this plugin */
	const TABLE_PREFIX = 'hsz_';

	/**
	 * Creates or updates the plugin database tables.
	 *
	 * Uses dbDelta which can create or update tables appropriately.
	 */
	public static function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_prefix    = $wpdb->prefix . self::TABLE_PREFIX;

		// --- Bulk Analysis Batches Table ---
		$sql_batches = "CREATE TABLE {$table_prefix}bulk_batches (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			batch_id varchar(255) NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			name varchar(255) DEFAULT '' NOT NULL,
			status varchar(20) DEFAULT 'pending' NOT NULL,
			total_urls int(11) unsigned DEFAULT 0 NOT NULL,
			processed_urls int(11) unsigned DEFAULT 0 NOT NULL,
			successful_urls int(11) unsigned DEFAULT 0 NOT NULL,
			failed_urls int(11) unsigned DEFAULT 0 NOT NULL,
			settings text,
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY batch_id (batch_id),
			KEY user_id (user_id)
		) $charset_collate;";

		// --- Bulk Analysis Results Table ---
		$sql_results = "CREATE TABLE {$table_prefix}bulk_results (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			batch_id varchar(255) NOT NULL,
			url text NOT NULL,
			status varchar(20) DEFAULT 'pending' NOT NULL,
			result longtext,
			processed_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY (id),
			KEY batch_id (batch_id)
		) $charset_collate;";

		// --- Analysis Cache Table ---
		$sql_cache = "CREATE TABLE {$table_prefix}analysis_cache (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			cache_key varchar(191) NOT NULL,
			url text NOT NULL,
			data longtext NOT NULL,
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			expires_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY cache_key (cache_key)
		) $charset_collate;";

		// --- Error Log Table ---
		// Note: Uses 'created_at' column as timestamp for logging instead of 'timestamp' to align with plugin queries.
		$sql_log = "CREATE TABLE {$table_prefix}error_log (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			error_code varchar(50) DEFAULT '' NOT NULL,
			message text NOT NULL,
			context text,
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY (id),
			KEY error_code (error_code)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $sql_batches );
		dbDelta( $sql_results );
		dbDelta( $sql_cache );
		dbDelta( $sql_log );

		// Update the stored database version option
		update_option( 'hsz_db_version', self::DB_VERSION );
	}

	/**
	 * Checks if the database version stored in the options matches the plugin version.
	 * Runs upgrade routines if needed.
	 */
	public static function check_db_version(): void {
		$installed_version = get_option( 'hsz_db_version' );

		if ( $installed_version !== self::DB_VERSION ) {
			self::create_tables();
		}
	}

	/**
	 * Activation hook handler for new sites in multisite networks.
	 *
	 * @param int $blog_id The site ID.
	 */
	public static function create_tables_new_site( int $blog_id ): void {
		if ( is_plugin_active_for_network( plugin_basename( HSZ_PLUGIN_PATH . 'hellaz-sitez-analyzer.php' ) ) ) {
			switch_to_blog( $blog_id );
			self::create_tables();
			restore_current_blog();
		}
	}
}
