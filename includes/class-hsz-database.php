<?php
/**
 * Database functionality for HellaZ SiteZ Analyzer.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.0.0
 */

namespace HSZ;

defined( 'ABSPATH' ) || exit;

class Database {

    /**
     * Database version for migrations
     */
    const DB_VERSION = '2.1.0';

    /**
     * Table prefix for all plugin tables
     */
    const TABLE_PREFIX = 'hsz_';

    /**
     * Creates all necessary database tables for the plugin.
     */
    public static function create_tables(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_prefix = $wpdb->prefix . self::TABLE_PREFIX;

        // Existing Tables
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

        $sql_log = "CREATE TABLE {$table_prefix}error_log (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            error_code varchar(50) DEFAULT '' NOT NULL,
            message text NOT NULL,
            context text,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY (id),
            KEY error_code (error_code)
        ) $charset_collate;";

        // Enhanced Phase 1 Tables
        $sql_performance = "CREATE TABLE {$table_prefix}performance_results (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            url_hash varchar(32) NOT NULL,
            url text NOT NULL,
            overall_score int(3) unsigned DEFAULT 0,
            performance_score int(3) unsigned DEFAULT 0,
            accessibility_score int(3) unsigned DEFAULT 0,
            best_practices_score int(3) unsigned DEFAULT 0,
            seo_score int(3) unsigned DEFAULT 0,
            core_web_vitals longtext,
            lighthouse_data longtext,
            pagespeed_data longtext,
            recommendations longtext,
            analysis_time float DEFAULT 0,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            expires_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY url_hash (url_hash),
            KEY expires_at (expires_at)
        ) $charset_collate;";

        $sql_security = "CREATE TABLE {$table_prefix}security_results (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            url_hash varchar(32) NOT NULL,
            url text NOT NULL,
            security_score int(3) unsigned DEFAULT 0,
            ssl_grade varchar(5) DEFAULT '',
            ssl_enabled tinyint(1) DEFAULT 0,
            security_headers longtext,
            malware_scan_results longtext,
            vulnerability_scan longtext,
            blacklist_status longtext,
            certificate_info longtext,
            security_issues longtext,
            recommendations longtext,
            analysis_time float DEFAULT 0,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            expires_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY url_hash (url_hash),
            KEY security_score (security_score),
            KEY expires_at (expires_at)
        ) $charset_collate;";

        $sql_previews = "CREATE TABLE {$table_prefix}website_previews (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            url_hash varchar(32) NOT NULL,
            url text NOT NULL,
            screenshot_url varchar(500) DEFAULT '',
            thumbnail_url varchar(500) DEFAULT '',
            local_path varchar(500) DEFAULT '',
            file_size int(11) unsigned DEFAULT 0,
            dimensions varchar(20) DEFAULT '',
            service_used varchar(50) DEFAULT '',
            generation_time float DEFAULT 0,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            expires_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY url_hash (url_hash),
            KEY status (status),
            KEY expires_at (expires_at)
        ) $charset_collate;";

        $sql_grades = "CREATE TABLE {$table_prefix}website_grades (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            url_hash varchar(32) NOT NULL,
            url text NOT NULL,
            overall_grade varchar(2) DEFAULT 'F',
            overall_score int(3) unsigned DEFAULT 0,
            performance_grade varchar(2) DEFAULT 'F',
            performance_score int(3) unsigned DEFAULT 0,
            security_grade varchar(2) DEFAULT 'F',
            security_score int(3) unsigned DEFAULT 0,
            content_grade varchar(2) DEFAULT 'F',
            content_score int(3) unsigned DEFAULT 0,
            usability_grade varchar(2) DEFAULT 'F',
            usability_score int(3) unsigned DEFAULT 0,
            grade_factors longtext,
            recommendations longtext,
            analysis_metadata longtext,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            expires_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY url_hash (url_hash),
            KEY overall_grade (overall_grade),
            KEY overall_score (overall_score),
            KEY expires_at (expires_at)
        ) $charset_collate;";

        $sql_api_usage = "CREATE TABLE {$table_prefix}api_usage (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            service varchar(50) NOT NULL,
            endpoint varchar(100) DEFAULT '',
            requests_count int(11) unsigned DEFAULT 1,
            success_count int(11) unsigned DEFAULT 0,
            error_count int(11) unsigned DEFAULT 0,
            total_response_time float DEFAULT 0,
            avg_response_time float DEFAULT 0,
            last_request_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            date_created date NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY service_date (service, date_created),
            KEY service (service),
            KEY date_created (date_created)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta( $sql_batches );
        dbDelta( $sql_results );
        dbDelta( $sql_cache );
        dbDelta( $sql_log );
        dbDelta( $sql_performance );
        dbDelta( $sql_security );
        dbDelta( $sql_previews );
        dbDelta( $sql_grades );
        dbDelta( $sql_api_usage );

        update_option( 'hsz_db_version', self::DB_VERSION );
        self::run_data_migrations();
    }

    /**
     * CRITICAL FIX: Repair broken analysis_cache table structure
     * This fixes the "Unknown column 'data' in 'field list'" error
     *
     * @return bool True if repair was successful
     */
    public static function repair_analysis_cache_table(): bool {
        global $wpdb;
        
        $table_name = self::get_table_name('analysis_cache');
        $charset_collate = $wpdb->get_charset_collate();
        
        // Log the repair attempt
        if (class_exists('HSZ\\Utils')) {
            Utils::log_error('Attempting to repair analysis_cache table', __FILE__, __LINE__);
        }
        
        // Drop the broken table completely
        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
        
        // Recreate with correct schema
        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            cache_key varchar(191) NOT NULL,
            url text NOT NULL,
            data longtext NOT NULL,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            expires_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY cache_key (cache_key),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $result = dbDelta($sql);
        
        // Verify table was created correctly
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        
        if ($table_exists) {
            // Verify columns exist
            $columns = $wpdb->get_results("DESCRIBE {$table_name}");
            $column_names = wp_list_pluck($columns, 'Field');
            
            $required_columns = ['id', 'cache_key', 'url', 'data', 'created_at', 'expires_at'];
            $missing_columns = array_diff($required_columns, $column_names);
            
            if (empty($missing_columns)) {
                if (class_exists('HSZ\\Utils')) {
                    Utils::log_error('Analysis cache table repaired successfully', __FILE__, __LINE__);
                }
                return true;
            } else {
                if (class_exists('HSZ\\Utils')) {
                    Utils::log_error('Table repair failed - missing columns: ' . implode(', ', $missing_columns), __FILE__, __LINE__);
                }
                return false;
            }
        }
        
        if (class_exists('HSZ\\Utils')) {
            Utils::log_error('Table repair failed - table was not created', __FILE__, __LINE__);
        }
        return false;
    }

    /**
     * Runs necessary data migrations for version upgrades.
     */
    private static function run_data_migrations(): void {
        global $wpdb;

        $current_version = get_option( 'hsz_db_version', '1.0.0' );

        if ( version_compare( $current_version, '1.1.0', '<' ) ) {
            self::migrate_to_v1_1_0();
        }
    }

    /**
     * Migration routine to version 1.1.0
     */
    private static function migrate_to_v1_1_0(): void {
        global $wpdb;

        $table_prefix = $wpdb->prefix . self::TABLE_PREFIX;

        // Add index on analysis_cache.expires_at
        $wpdb->query( "CREATE INDEX IF NOT EXISTS idx_expires_at ON {$table_prefix}analysis_cache (expires_at)" );

        // Add index on bulk_results.status
        $wpdb->query( "CREATE INDEX IF NOT EXISTS idx_status ON {$table_prefix}bulk_results (status)" );

        if ( class_exists( 'HSZ\\Utils' ) ) {
            Utils::log_error( 'Database migrated to version 1.1.0 with enhanced features', __FILE__, __LINE__ );
        }

        update_option( 'hsz_db_version', self::DB_VERSION );
    }

    /**
     * Checks if the specified database table exists.
     *
     * @param string $table Table name without prefix.
     * @return bool True if the table exists.
     */
    public static function table_exists( string $table ): bool {
        global $wpdb;

        $table_name = self::get_table_name( $table );
        $result = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );

        return $result === $table_name;
    }

    /**
     * Gets the full table name including prefix.
     *
     * @param string $table Table name without prefix.
     * @return string Full table name with prefix.
     */
    public static function get_table_name( string $table ): string {
        global $wpdb;

        return $wpdb->prefix . self::TABLE_PREFIX . $table;
    }

    /**
     * Cleans up expired cache entries from all relevant tables.
     *
     * @return int Total number of database rows deleted.
     */
    public static function cleanup_expired_cache(): int {
        global $wpdb;

        $tables_to_clean = [
            'analysis_cache',
            'performance_results',
            'security_results',
            'website_previews',
            'website_grades'
        ];

        $total_cleaned = 0;

        foreach ( $tables_to_clean as $table ) {
            if ( self::table_exists( $table ) ) {
                $table_name = self::get_table_name( $table );

                $cleaned = $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM {$table_name} WHERE expires_at < %s AND expires_at != '0000-00-00 00:00:00'",
                        current_time( 'mysql', true )
                    )
                );

                $total_cleaned += $cleaned;
            }
        }

        return $total_cleaned;
    }

    /**
     * Gets statistics about the database.
     *
     * @return array Database statistics including rows and sizes.
     */
    public static function get_database_stats(): array {
        global $wpdb;

        $tables = [
            'bulk_batches',
            'bulk_results',
            'analysis_cache',
            'error_log',
            'performance_results',
            'security_results',
            'website_previews',
            'website_grades',
            'api_usage'
        ];

        $stats = [
            'tables' => [],
            'total_size' => 0,
            'total_rows' => 0
        ];

        foreach ( $tables as $table ) {
            if ( self::table_exists( $table ) ) {
                $table_name = self::get_table_name( $table );

                $table_stats = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT COUNT(*) as rows, ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb
                        FROM information_schema.TABLES
                        WHERE table_schema = %s AND table_name = %s",
                        DB_NAME,
                        $table_name
                    )
                );

                if ( $table_stats ) {
                    $stats['tables'][ $table ] = [
                        'rows' => (int) $table_stats->rows,
                        'size_mb' => (float) $table_stats->size_mb
                    ];

                    $stats['total_rows'] += $table_stats->rows;
                    $stats['total_size'] += $table_stats->size_mb;
                }
            }
        }

        return $stats;
    }

    /**
     * Optimizes all analysis-related tables.
     *
     * @return array Optimization results per table.
     */
    public static function optimize_tables(): array {
        global $wpdb;

        $tables = [
            'bulk_batches',
            'bulk_results',
            'analysis_cache',
            'error_log',
            'performance_results',
            'security_results',
            'website_previews',
            'website_grades',
            'api_usage'
        ];

        $results = [];

        foreach ( $tables as $table ) {
            if ( self::table_exists( $table ) ) {
                $table_name = self::get_table_name( $table );
                $result = $wpdb->query( "OPTIMIZE TABLE {$table_name}" );
                $results[ $table ] = $result !== false;
            } else {
                $results[ $table ] = false;
            }
        }

        return $results;
    }
}
