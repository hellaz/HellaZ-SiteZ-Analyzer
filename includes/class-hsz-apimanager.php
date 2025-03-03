<?php
namespace HSZ;

class APIManager {
    private function log_admin_notice($message) {
        // Add an admin notice
        add_action('admin_notices', function () use ($message) {
            echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
        });
        // Log the error to the debug log
        error_log('[HellaZ SiteZ Analyzer] ' . $message);
    }

    public function make_api_request($url, $args = [], $cache_key = '', $cache_duration = HOUR_IN_SECONDS) {
        // Validate the URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->log_admin_notice(__('Invalid URL provided.', 'hellaz-sitez-analyzer'));
            return !empty($cache_key) ? get_transient($cache_key) : [];
        }

        // Check if cached data is available
        if (!empty($cache_key)) {
            $cached_data = get_transient($cache_key);
            if ($cached_data) {
                return $cached_data;
            }
        }

        // Default arguments
        $default_args = [
            'timeout' => 5, // Add a 5-second timeout
            'headers' => [],
        ];
        $args = array_merge($default_args, $args);

        // Make the API request
        $response = wp_remote_get($url, $args);
        if (is_wp_error($response)) {
            $this->log_admin_notice(sprintf(
                __('API Request Failed: %s (%s)', 'hellaz-sitez-analyzer'),
                $response->get_error_message(),
                esc_url($url)
            ));
            return !empty($cache_key) ? get_transient($cache_key) : [];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $this->log_admin_notice(sprintf(
                __('API HTTP Error: %d (%s)', 'hellaz-sitez-analyzer'),
                $response_code,
                esc_url($url)
            ));
            return !empty($cache_key) ? get_transient($cache_key) : [];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        // Cache the results if a cache key is provided
        if (!empty($cache_key)) {
            set_transient($cache_key, $body, $cache_duration);
        }

        return $body;
    }
}
