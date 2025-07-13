<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

class Utils {
    private static $log_levels = array(
        'ERROR' => 1,
        'WARNING' => 2,
        'INFO' => 3,
        'DEBUG' => 4
    );
    
    /**
     * Enhanced error logging with context and levels
     */
    public static function log_error($message, $context = array(), $level = 'ERROR') {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $timestamp = current_time('Y-m-d H:i:s');
        $user_id = get_current_user_id();
        $ip = self::get_client_ip();
        
        $log_entry = array(
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
            'user_id' => $user_id,
            'ip' => $ip,
            'context' => $context,
            'memory_usage' => self::get_memory_usage(),
            'url' => $_SERVER['REQUEST_URI'] ?? 'CLI'
        );
        
        $formatted_message = sprintf(
            '[%s] [%s] [HellaZ SiteZ Analyzer] %s | User: %d | IP: %s | Memory: %s',
            $timestamp,
            $level,
            $message,
            $user_id,
            $ip,
            $log_entry['memory_usage']
        );
        
        if (!empty($context)) {
            $formatted_message .= ' | Context: ' . json_encode($context);
        }
        
        error_log($formatted_message);
        
        // Store critical errors in database for admin review
        if ($level === 'ERROR') {
            self::store_error_in_database($log_entry);
        }
    }
    
    /**
     * Store critical errors in database
     */
    private static function store_error_in_database($log_entry) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hsz_error_log';
        
        // Create table if it doesn't exist
        $wpdb->query("CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            level varchar(10) NOT NULL,
            message text NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            ip varchar(45) NOT NULL,
            context longtext,
            memory_usage varchar(20),
            url varchar(500),
            PRIMARY KEY (id),
            KEY timestamp (timestamp),
            KEY level (level)
        ) {$wpdb->get_charset_collate()}");
        
        $wpdb->insert(
            $table_name,
            array(
                'timestamp' => $log_entry['timestamp'],
                'level' => $log_entry['level'],
                'message' => $log_entry['message'],
                'user_id' => $log_entry['user_id'],
                'ip' => $log_entry['ip'],
                'context' => json_encode($log_entry['context']),
                'memory_usage' => $log_entry['memory_usage'],
                'url' => $log_entry['url']
            ),
            array('%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        $ip_headers = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Get current memory usage
     */
    private static function get_memory_usage() {
        $bytes = memory_get_usage(true);
        $units = array('B', 'KB', 'MB', 'GB');
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Enhanced caching methods
     */
    public static function get_cached_data($key, $default = null) {
        $cache_key = "hsz_cache_{$key}";
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            self::log_error("Cache HIT for key: {$key}", array(), 'DEBUG');
            return $cached_data;
        }
        
        self::log_error("Cache MISS for key: {$key}", array(), 'DEBUG');
        return $default;
    }
    
    public static function set_cached_data($key, $data, $expiration = null) {
        if ($expiration === null) {
            $expiration = get_option('hsz_cache_duration', DAY_IN_SECONDS);
        }
        
        $cache_key = "hsz_cache_{$key}";
        $result = set_transient($cache_key, $data, $expiration);
        
        if ($result) {
            self::log_error("Cache SET for key: {$key}, expires in: {$expiration}s", array(), 'DEBUG');
        } else {
            self::log_error("Cache SET FAILED for key: {$key}", array(), 'WARNING');
        }
        
        return $result;
    }
    
    public static function clear_cache($pattern = null) {
        global $wpdb;
        
        $deleted_count = 0;
        
        if ($pattern) {
            $like_pattern = '_transient_hsz_cache_' . $pattern . '%';
            $timeout_pattern = '_transient_timeout_hsz_cache_' . $pattern . '%';
        } else {
            $like_pattern = '_transient_hsz_cache_%';
            $timeout_pattern = '_transient_timeout_hsz_cache_%';
        }
        
        // Delete transients
        $deleted_count += $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $like_pattern
        ));
        
        // Delete timeout transients
        $deleted_count += $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $timeout_pattern
        ));
        
        self::log_error("Cleared {$deleted_count} cache entries with pattern: " . ($pattern ?? 'all'));
        
        return $deleted_count;
    }
    
    /**
     * Database optimization methods
     */
    public static function optimize_database_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'hsz_bulk_batches',
            $wpdb->prefix . 'hsz_bulk_results',
            $wpdb->prefix . 'hsz_analysis_cache',
            $wpdb->prefix . 'hsz_error_log'
        );
        
        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
                $wpdb->query("OPTIMIZE TABLE $table");
                self::log_error("Optimized database table: {$table}");
            }
        }
    }
    
    /**
     * Clean up old data
     */
    public static function cleanup_old_data($days = 30) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Clean up old bulk processing data
        $deleted_batches = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}hsz_bulk_batches WHERE created_at < %s AND status IN ('completed', 'failed', 'cancelled')",
            $cutoff_date
        ));
        
        // Clean up old error logs
        $deleted_errors = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}hsz_error_log WHERE timestamp < %s",
            $cutoff_date
        ));
        
        self::log_error("Cleaned up old data: {$deleted_batches} batches, {$deleted_errors} error logs");
        
        return $deleted_batches + $deleted_errors;
    }
    
    /**
     * Security utilities
     */
    public static function encrypt_api_key($key) {
        if (empty($key)) return '';
        
        if (!function_exists('openssl_encrypt')) {
            return base64_encode($key); // Fallback
        }
        
        $encryption_key = self::get_encryption_key();
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($key, 'AES-256-CBC', $encryption_key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    public static function decrypt_api_key($encrypted_key) {
        if (empty($encrypted_key)) return '';
        
        if (!function_exists('openssl_decrypt')) {
            return base64_decode($encrypted_key); // Fallback
        }
        
        $data = base64_decode($encrypted_key);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        $encryption_key = self::get_encryption_key();
        
        return openssl_decrypt($encrypted, 'AES-256-CBC', $encryption_key, 0, $iv);
    }
    
    private static function get_encryption_key() {
        $key = get_option('hsz_encryption_key');
        if (!$key) {
            $key = wp_generate_password(32, false);
            update_option('hsz_encryption_key', $key);
        }
        return $key;
    }
    
    /**
     * Performance monitoring
     */
    public static function start_performance_timer($operation) {
        $start_time = microtime(true);
        set_transient("hsz_perf_timer_{$operation}", $start_time, 300); // 5 min expiry
        return $start_time;
    }
    
    public static function end_performance_timer($operation) {
        $start_time = get_transient("hsz_perf_timer_{$operation}");
        if (!$start_time) return null;
        
        $end_time = microtime(true);
        $duration = $end_time - $start_time;
        
        delete_transient("hsz_perf_timer_{$operation}");
        
        self::log_error("Performance: {$operation} took {$duration} seconds", array(
            'operation' => $operation,
            'duration' => $duration,
            'start_time' => $start_time,
            'end_time' => $end_time
        ), 'INFO');
        
        return $duration;
    }
    
    /**
     * Sanitization helpers
     */
    public static function sanitize_html_output($content) {
        $allowed_tags = array(
            'div' => array('class' => array(), 'id' => array()),
            'span' => array('class' => array()),
            'p' => array('class' => array()),
            'a' => array('href' => array(), 'class' => array(), 'target' => array(), 'rel' => array()),
            'img' => array('src' => array(), 'alt' => array(), 'class' => array(), 'width' => array(), 'height' => array()),
            'h1' => array('class' => array()),
            'h2' => array('class' => array()),
            'h3' => array('class' => array()),
            'h4' => array('class' => array()),
            'ul' => array('class' => array()),
            'ol' => array('class' => array()),
            'li' => array('class' => array()),
            'strong' => array(),
            'em' => array(),
            'small' => array('class' => array())
        );
        
        return wp_kses($content, $allowed_tags);
    }
}
