<?php
namespace HSZ;

class Cache {
    public function get_cached_data($url) {
        // Validate input
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return [];
        }

        // Cache key for storing data
        $cache_key = 'hsz_' . md5($url);
        $data = get_transient($cache_key);

        if ($data === false) {
            $data = $this->fetch_data($url);
            set_transient($cache_key, $data, DAY_IN_SECONDS);
        }

        return $data;
    }

    private function fetch_data($url) {
        // Fetch and process data here
        // Log errors if fetching fails (optional)
        error_log('[HellaZ SiteZ Analyzer] Failed to fetch data for URL: ' . esc_url($url));
        return [];
    }

    public function clear_cache() {
        if (isset($_POST['hsz_clear_cache'])) {
            check_admin_referer('hsz_clear_cache_nonce', 'hsz_nonce'); // Verify nonce
            delete_transient('hsz_metadata_cache');
            add_settings_error('hsz_messages', 'cache_cleared', __('Cache cleared successfully.', 'hellaz-sitez-analyzer'), 'success');
        }
    }

    public function render_cache_clear_button() {
        echo '<form method="post">';
        wp_nonce_field('hsz_clear_cache_nonce', 'hsz_nonce'); // Add nonce field
        submit_button(__('Clear Cache', 'hellaz-sitez-analyzer'), 'secondary', 'hsz_clear_cache');
        echo '</form>';
    }
}
