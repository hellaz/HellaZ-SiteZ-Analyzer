<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

class Database {
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_batches = $wpdb->prefix . 'hsz_bulk_batches';
        $sql_batches = "CREATE TABLE $table_batches (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            batch_id varchar(100) NOT NULL,
            user_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            status enum('pending','processing','completed','failed','cancelled') DEFAULT 'pending',
            total_urls int(11) DEFAULT 0,
            processed_urls int(11) DEFAULT 0,
            successful_urls int(11) DEFAULT 0,
            failed_urls int(11) DEFAULT 0,
            settings longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            started_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY batch_id (batch_id)
        ) $charset_collate;";
        $table_results = $wpdb->prefix . 'hsz_bulk_results';
        $sql_results = "CREATE TABLE $table_results (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            batch_id varchar(100) NOT NULL,
            url varchar(2048) NOT NULL,
            status enum('pending','processing','completed','failed') DEFAULT 'pending',
            metadata longtext,
            social_media longtext,
            security_info longtext,
            error_message text,
            processing_time float DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            processed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY batch_id (batch_id)
        ) $charset_collate;";
        $table_cache = $wpdb->prefix . 'hsz_analysis_cache';
        $sql_cache = "CREATE TABLE $table_cache (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            url_hash varchar(64) NOT NULL,
            url varchar(2048) NOT NULL,
            metadata longtext,
            social_media longtext,
            security_info longtext,
            expires_at datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY url_hash (url_hash)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_batches);
        dbDelta($sql_results);
        dbDelta($sql_cache);
        update_option('hsz_db_version', HSZ_DB_VERSION);
    }
    public static function create_tables_new_site($blog_id) {
        if (is_plugin_active_for_network(HSZ_PLUGIN_BASENAME)) {
            switch_to_blog($blog_id);
            self::create_tables();
            restore_current_blog();
        }
    }
    public static function check_db_version() {
        $current_version = get_option('hsz_db_version', '1.0.0');
        if (version_compare($current_version, HSZ_DB_VERSION, '<')) {
            self::create_tables();
        }
    }
}
