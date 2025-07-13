<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

class Utils {
    private static $security_instance = null;
    
    public static function encrypt_api_key($key) {
        if (self::$security_instance === null) {
            self::$security_instance = new Security();
        }
        return self::$security_instance->encrypt_api_key($key);
    }
    
    public static function decrypt_api_key($encrypted_key) {
        if (self::$security_instance === null) {
            self::$security_instance = new Security();
        }
        return self::$security_instance->decrypt_api_key($encrypted_key);
    }
    
    public static function get_cached_data($key, $default = null) {
        return get_transient("hsz_cache_{$key}") ?: $default;
    }
    
    public static function set_cached_data($key, $data, $expiration = null) {
        if ($expiration === null) {
            $expiration = get_option('hsz_cache_duration', DAY_IN_SECONDS);
        }
        return set_transient("hsz_cache_{$key}", $data, $expiration);
    }
    
    public static function clear_cache($pattern = null) {
        global $wpdb;
        
        if ($pattern) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_hsz_cache_' . $pattern . '%'
            ));
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_hsz_cache_' . $pattern . '%'
            ));
        } else {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_hsz_cache_%'
            ));
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_hsz_cache_%'
            ));
        }
    }
    
    public static function log_error($message, $context = array()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_message = '[HellaZ SiteZ Analyzer] ' . $message;
            if (!empty($context)) {
                $log_message .= ' | Context: ' . json_encode($context);
            }
            error_log($log_message);
        }
    }
    
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
            'h5' => array('class' => array()),
            'h6' => array('class' => array()),
            'ul' => array('class' => array()),
            'ol' => array('class' => array()),
            'li' => array('class' => array()),
            'strong' => array(),
            'em' => array(),
            'small' => array('class' => array())
        );
        
        return wp_kses($content, $allowed_tags);
    }
    
    public static function format_url_for_display($url, $max_length = 50) {
        if (strlen($url) <= $max_length) {
            return $url;
        }
        
        $parsed = parse_url($url);
        $domain = $parsed['host'] ?? '';
        $path = $parsed['path'] ?? '';
        
        if (strlen($domain) > $max_length - 3) {
            return substr($domain, 0, $max_length - 3) . '...';
        }
        
        $available_length = $max_length - strlen($domain) - 3;
        if (strlen($path) > $available_length) {
            return $domain . substr($path, 0, $available_length) . '...';
        }
        
        return $domain . $path;
    }
    
    public static function is_valid_image_url($url) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        $image_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico');
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        
        return in_array($extension, $image_extensions);
    }
    
    public static function get_default_settings() {
        return array(
            'hsz_fallback_image' => HSZ_PLUGIN_URL . 'assets/images/default-favicon.png',
            'hsz_enable_disclaimer' => false,
            'hsz_disclaimer_message' => __('This information is automatically extracted and may not be accurate.', 'hellaz-sitez-analyzer'),
            'hsz_cache_duration' => DAY_IN_SECONDS,
            'hsz_link_target' => '_blank',
            'hsz_icon_library' => 'fontawesome',
            'hsz_custom_icon_path' => '',
            'hsz_virustotal_api_key' => '',
            'hsz_builtwith_api_key' => '',
            'hsz_urlscan_io_api_key' => ''
        );
    }
}
