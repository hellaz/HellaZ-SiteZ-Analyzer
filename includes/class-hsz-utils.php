<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

class Utils {
    public static function encrypt_api_key($key) {
        if (empty($key)) return '';
        if (!function_exists('openssl_encrypt')) return base64_encode($key);
        $encryption_key = self::get_encryption_key();
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($key, 'AES-256-CBC', $encryption_key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    public static function decrypt_api_key($encrypted_key) {
        if (empty($encrypted_key)) return '';
        if (!function_exists('openssl_decrypt')) return base64_decode($encrypted_key);
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

    public static function log_error($message, $context = [], $level = 'ERROR') {
        if (!defined('WP_DEBUG') || !WP_DEBUG) return;
        $timestamp = current_time('Y-m-d H:i:s');
        $user_id = get_current_user_id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $log_entry = sprintf('[%s] [%s] [HellaZ SiteZ Analyzer] %s | User: %d | IP: %s', $timestamp, $level, $message, $user_id, $ip);
        if (!empty($context)) $log_entry .= ' | Context: ' . json_encode($context);
        error_log($log_entry);
    }
}
