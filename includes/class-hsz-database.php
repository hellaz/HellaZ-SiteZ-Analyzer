<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

class Database {
    
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Enhanced bulk batches table with proper indexing
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
            UNIQUE KEY batch_id (batch_id),
            KEY user_status (user_id, status),
            KEY created_at (created_at),
            KEY status_created (status, created_at)
        ) $charset_collate;";
        
        // Enhanced bulk results table with proper indexing
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
            KEY batch_status (batch_id, status),
            KEY url_hash (url(191)),
            KEY processed_at (processed_at),
            KEY status_created (status, created_at)
        ) $charset_collate;";
        
        // Enhanced analysis cache table with proper indexing and TTL
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
            access_count int(11) DEFAULT 1,
            last_accessed datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY url_hash (url_hash),
            KEY expires_at (expires_at),
            KEY last_accessed (last_accessed),
            KEY url_partial (url(191))
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_batches);
        dbDelta($sql_results);
        dbDelta($sql_cache);
        
        // Update database version
        update_option('hsz_db_version', HSZ_DB_VERSION);
        
        // Schedule database maintenance
        if (!wp_next_scheduled('hsz_database_maintenance')) {
            wp_schedule_event(time(), 'weekly', 'hsz_database_maintenance');
        }
        
        Utils::log_error('Database tables created/updated successfully');
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
            Utils::log_error('Database upgraded from version ' . $current_version . ' to ' . HSZ_DB_VERSION);
        }
    }
    
    /**
     * Database maintenance tasks
     */
    public static function maintenance() {
        global $wpdb;
        
        Utils::log_error('Starting database maintenance');
        
        // Clean up expired cache entries
        $cache_table = $wpdb->prefix . 'hsz_analysis_cache';
        $deleted_cache = $wpdb->query(
            "DELETE FROM $cache_table WHERE expires_at < NOW()"
        );
        
        // Clean up old completed batches (older than 30 days)
        $batch_table = $wpdb->prefix . 'hsz_bulk_batches';
        $results_table = $wpdb->prefix . 'hsz_bulk_results';
        
        $old_batches = $wpdb->get_col(
            "SELECT batch_id FROM $batch_table 
             WHERE status IN ('completed', 'failed', 'cancelled') 
             AND completed_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        
        $deleted_batches = 0;
        $deleted_results = 0;
        
        if (!empty($old_batches)) {
            $batch_ids = "'" . implode("','", array_map('esc_sql', $old_batches)) . "'";
            
            $deleted_results = $wpdb->query(
                "DELETE FROM $results_table WHERE batch_id IN ($batch_ids)"
            );
            
            $deleted_batches = $wpdb->query(
                "DELETE FROM $batch_table WHERE batch_id IN ($batch_ids)"
            );
        }
        
        // Optimize tables
        $wpdb->query("OPTIMIZE TABLE $cache_table");
        $wpdb->query("OPTIMIZE TABLE $batch_table");
        $wpdb->query("OPTIMIZE TABLE $results_table");
        
        Utils::log_error("Database maintenance completed: {$deleted_cache} cache entries, {$deleted_batches} batches, {$deleted_results} results cleaned up");
    }
}

// Hook database maintenance
add_action('hsz_database_maintenance', array('HSZ\\Database', 'maintenance'));
