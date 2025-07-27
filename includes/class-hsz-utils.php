<?php
namespace HSZ;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Utility helper class for the SiteZ Analyzer plugin.
 * Provides caching, error logging, encryption, performance monitoring, and sanitization functions.
 */
class Utils
{
    /**
     * Enhanced error logging with context and log levels.
     *
     * @param string $message Error message
     * @param array $context Optional context data
     * @param string $level Log level ('ERROR', 'WARNING', 'INFO', 'DEBUG')
     *
     * Logs errors to PHP error log when WP_DEBUG is enabled and stores critical errors in DB.
     */
    public static function log_error(string $message, array $context = [], string $level = 'ERROR'): void
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $timestamp = current_time('Y-m-d H:i:s');
        $user_id = get_current_user_id();
        $ip = self::get_client_ip();

        $log_entry = [
            'timestamp' => $timestamp,
            'level'     => $level,
            'message'   => $message,
            'user_id'   => $user_id,
            'ip'        => $ip,
            'context'   => $context,
            'memory'    => self::format_bytes(memory_get_usage()),
            'url'       => $_SERVER['REQUEST_URI'] ?? 'CLI',
        ];

        $log_line = sprintf(
            '[%s] [%s] [HellaZ SiteZ Analyzer] %s | User: %d | IP: %s | Memory: %s',
            $log_entry['timestamp'],
            $log_entry['level'],
            $log_entry['message'],
            $log_entry['user_id'],
            $log_entry['ip'],
            $log_entry['memory']
        );

        if (!empty($context)) {
            $log_line .= ' | Context: ' . wp_json_encode($context);
        }

        error_log($log_line);

        // Store only critical errors in DB for admin review
        if ($level === 'ERROR') {
            self::store_error_in_db($log_entry);
        }
    }

    /**
     * Store error log entry into custom database table.
     *
     * @param array $log_entry Entry data
     */
    protected static function store_error_in_db(array $log_entry): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'hsz_error_log';

        // Create table if not exists (should ideally be in install routine)
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            timestamp datetime NOT NULL,
            level varchar(10) NOT NULL,
            message text NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            ip varchar(45) NOT NULL,
            context longtext,
            memory varchar(20),
            url varchar(255),
            PRIMARY KEY (id),
            KEY timestamp (timestamp),
            KEY level (level)
        ) $charset_collate";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        $wpdb->insert(
            $table,
            [
                'timestamp' => $log_entry['timestamp'],
                'level'     => $log_entry['level'],
                'message'   => $log_entry['message'],
                'user_id'   => $log_entry['user_id'],
                'ip'        => $log_entry['ip'],
                'context'   => wp_json_encode($log_entry['context']),
                'memory'    => $log_entry['memory'],
                'url'       => $log_entry['url'],
            ],
            [
                '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s'
            ]
        );
    }

    /**
     * Returns the client's IP address.
     *
     * @return string IP address
     */
    public static function get_client_ip(): string
    {
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ipList = explode(',', $_SERVER[$header]);
                $ip = trim(current($ipList));
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return 'UNKNOWN';
    }

    /**
     * Caching helper: get data from transient cache.
     *
     * @param string $key Cache key
     * @param mixed $default Default value if no cache
     * @return mixed Cached data or default
     */
    public static function get_cached_data(string $key, $default = false)
    {
        $cache_key = 'hsz_cache_' . $key;
        $value = get_transient($cache_key);

        if ($value !== false) {
            self::log_error("Cache HIT for key: $cache_key", [], 'DEBUG');
            return $value;
        }

        self::log_error("Cache MISS for key: $cache_key", [], 'DEBUG');
        return $default;
    }

    /**
     * Caching helper: set data into transient cache.
     *
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int|null $expiration Cache duration in seconds, defaults to plugin setting
     * @return bool True on success
     */
    public static function set_cached_data(string $key, $data, ?int $expiration = null): bool
    {
        if ($expiration === null) {
            $expiration = max(0, (int) get_option('hsz_cache_duration', DAY_IN_SECONDS));
        }

        $cache_key = 'hsz_cache_' . $key;
        $success = set_transient($cache_key, $data, $expiration);

        if ($success) {
            self::log_error("Cache SET for key: $cache_key with expiration: $expiration", [], 'DEBUG');
        } else {
            self::log_error("Cache SET FAILED for key: $cache_key", [], 'WARNING');
        }

        return $success;
    }

    /**
     * Delete cached entries optionally filtered by prefix pattern.
     *
     * @param string|null $pattern If specified, only remove keys containing this pattern.
     * @return int Number of entries deleted
     */
    public static function clear_cache(?string $pattern = null): int
    {
        global $wpdb;
        $count = 0;
        $option_table = $wpdb->options;

        if ($pattern) {
            $like = '%hsz_cache_' . $wpdb->esc_like($pattern) . '%';
        } else {
            $like = '%hsz_cache_%';
        }

        // Remove transients and their timeouts
        $count += $wpdb->query($wpdb->prepare("DELETE FROM $option_table WHERE option_name LIKE %s", '_transient_' . $like));
        $count += $wpdb->query($wpdb->prepare("DELETE FROM $option_table WHERE option_name LIKE %s", '_transient_timeout_' . $like));

        self::log_error("Cache cleared with pattern: $pattern, entries removed: $count", [], 'INFO');

        return $count;
    }

    /**
     * Formats bytes into a human-readable format.
     *
     * @param int $bytes Number of bytes
     * @return string Formatted string
     */
    public static function format_bytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);
        $value = $bytes / pow(1024, $power);

        return round($value, 2) . ' ' . $units[(int) $power];
    }

    /**
     * Encrypt API key for storage.
     *
     * @param string $key Plain API key
     * @return string Encrypted key (base64) or plain base64 if encryption unavailable
     */
    public static function encrypt(string $key): string
    {
        if (empty($key)) {
            return '';
        }

        if (!function_exists('openssl_encrypt')) {
            // Fallback to base64
            return base64_encode($key);
        }

        $encryption_key = self::get_encryption_key();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($key, 'aes-256-cbc', $encryption_key, 0, $iv);

        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt stored API key.
     *
     * @param string $encrypted Encrypted or base64 API key
     * @return string Decrypted plain key
     */
    public static function decrypt(string $encrypted): string
    {
        if (empty($encrypted)) {
            return '';
        }

        if (!function_exists('openssl_decrypt')) {
            return base64_decode($encrypted);
        }

        $data = base64_decode($encrypted);
        $iv_len = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $iv_len);
        $ciphertext = substr($data, $iv_len);
        $encryption_key = self::get_encryption_key();

        return openssl_decrypt($ciphertext, 'aes-256-cbc', $encryption_key, 0, $iv) ?: '';
    }

    /**
     * Retrieves encryption key from options or generates a new one.
     *
     * @return string Encryption key
     */
    protected static function get_encryption_key(): string
    {
        $key = get_option('hsz_encryption_key', '');
        if (empty($key)) {
            $key = wp_generate_password(32, false);
            update_option('hsz_encryption_key', $key);
        }
        return $key;
    }

    /**
     * Display an inspector table for admins showing live cache status.
     */
    public static function show_cache_inspector(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        global $wpdb;

        $entries = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE '_transient_hsz_cache_%' LIMIT 50"
        );

        if (!$entries) {
            echo '<p>No active SiteZ Analyzer cache entries found.</p>';
            return;
        }

        echo '<h4>Active SiteZ Analyzer Cache Entries (Showing up to 50)</h4>';
        echo '<table class="widefat" style="max-width: 90%"><thead><tr><th>Cache Key</th><th>Preview (First 100 chars)</th></tr></thead><tbody>';

        foreach ($entries as $entry) {
            $key = esc_html(str_replace('_transient_', '', $entry->option_name));
            $value_preview = esc_html(mb_substr(serialize($entry->option_value), 0, 100));
            echo "<tr><td>{$key}</td><td><code>{$value_preview}</code></td></tr>";
        }

        echo '</tbody></table>';
    }

    /**
     * Starts a performance timer for debugging.
     *
     * @param string $label Timer label
     * @return float Start timestamp
     */
    public static function start_timer(string $label): float
    {
        $start = microtime(true);
        set_transient("hsz_perf_timer_{$label}", $start, 300);
        return $start;
    }

    /**
     * Ends a performance timer and logs the elapsed time.
     *
     * @param string $label Timer label
     * @return float|null Elapsed time in seconds or null if no start time found
     */
    public static function end_timer(string $label): ?float
    {
        $start = get_transient("hsz_perf_timer_{$label}");
        if (!$start) {
            return null;
        }
        delete_transient("hsz_perf_timer_{$label}");
        $elapsed = microtime(true) - $start;

        self::log_error("Performance timer '{$label}' took {$elapsed} seconds", [], 'INFO');

        return $elapsed;
    }
}
