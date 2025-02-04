<?php
namespace HSZ;

class API_Manager {
    public static function register_api($name, $callback) {
        add_filter('hsz_registered_apis', function ($apis) use ($name, $callback) {
            $apis[$name] = $callback;
            return $apis;
        });
    }

    public static function get_api_data($name, $url, $api_key, $expiry = 86400) {
        if (empty($api_key)) {
            return [];
        }

        $apis = apply_filters('hsz_registered_apis', []);
        if (isset($apis[$name])) {
            $callback = $apis[$name];
            return $callback($url, $api_key, $expiry);
        }
        return [];
    }

    private function log_admin_notice($message) {
        // Add an admin notice
        add_action('admin_notices', function () use ($message) {
            echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
        });
    
        // Optionally log the error to the debug log
        error_log('[HellaZ SiteZ Analyzer] ' . $message);
    }
}
