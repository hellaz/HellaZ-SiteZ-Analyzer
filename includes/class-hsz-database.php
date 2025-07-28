<?php
/**
 * Manages all database operations, including table creation and updates.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.0.0
 */

namespace HSZ;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Database
 *
 * Handles the creation and maintenance of custom database tables.
 */
class Database {

	/**
	 * The current database version.
	 *
	 * This constant is the single source of truth for the database schema version.
	 * It is used to track whether the database needs to be updated.
	 *
	 * @var string
	 */
	private const DB_VERSION = '1.0';

	/**
	 * The prefix for all custom tables created by this plugin.
	 *
	 * @var string
	 */
	private const TABLE_PREFIX = 'hsz_';

	/**
	 * Creates all necessary database tables for the plugin.
	 *
	 * This method is called upon plugin activation. It uses the dbDelta function
	 * to create or update tables to match the defined schema.
	 */
	public static function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_prefix    = $wpdb->prefix . self::TABLE_PREFIX;

		// SQL for the bulk analysis batches table.
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

		// SQL for the bulk analysis results table.
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

		// SQL for the analysis cache table.
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

		// SQL for the error log table.
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

		// CRITICAL FIX: Use the internal class constant instead of a global one.
		update_option( 'hsz_db_version', self::DB_VERSION );
	}

	/**
	 * Checks if the database is up to date and runs upgrades if necessary.
	 *
	 * This method compares the stored database version with the plugin's current
	 * database version constant and triggers an update if they do not match.
	 */
	public static function check_db_version(): void {
		$installed_version = get_option( 'hsz_db_version' );

		// If the stored version does not match the current version, re-run table creation.
		if ( $installed_version !== self::DB_VERSION ) {
			self::create_tables();
		}
	}

	/**
	 * Activation hook for new sites in a multisite network.
	 *
	 * @param int $blog_id The ID of the new site.
	 */
	public static function create_tables_new_site( int $blog_id ): void {
		if ( is_plugin_active_for_network( plugin_basename( HSZ_PLUGIN_PATH . 'hellaz-sitez-analyzer.php' ) ) ) {
			switch_to_blog( $blog_id );
			self::create_tables();
			restore_current_blog();
		}
	}
}
