<?php
namespace HSZ;

class Cache {
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_menu', [$this, 'add_cache_clear_page']);
        add_action('wp_ajax_hsz_clear_cache', [$this, 'clear_cache_ajax']);
    }

    /**
     * Register settings for cache clearing.
     */
    public function register_settings() {
        register_setting('hsz_settings_group', 'hsz_cache_cleared');
    }

    /**
     * Add a submenu page for cache clearing.
     */
    public function add_cache_clear_page() {
        add_submenu_page(
            'tools.php',
            __('Clear Cache', 'hellaz-sitez-analyzer'),
            __('Clear Cache', 'hellaz-sitez-analyzer'),
            'manage_options',
            'hsz-clear-cache',
            [$this, 'render_cache_clear_page']
        );
    }

    /**
     * Render the cache-clearing page.
     */
    public function render_cache_clear_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('HellaZ SiteZ Analyzer - Clear Cache', 'hellaz-sitez-analyzer'); ?></h1>
            <p><?php _e('Click the button below to clear all cached data.', 'hellaz-sitez-analyzer'); ?></p>
            <form method="post" id="hsz-clear-cache-form">
                <?php submit_button(__('Clear Cache', 'hellaz-sitez-analyzer'), 'primary', 'hsz_clear_cache'); ?>
            </form>
            <div id="hsz-clear-cache-message" style="display: none;"></div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                $('#hsz-clear-cache-form').on('submit', function(e) {
                    e.preventDefault();
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'hsz_clear_cache'
                        },
                        success: function(response) {
                            $('#hsz-clear-cache-message').html('<div class="notice notice-success"><p>' + response.message + '</p></div>').show();
                        },
                        error: function() {
                            $('#hsz-clear-cache-message').html('<div class="notice notice-error"><p><?php _e('An error occurred while clearing the cache.', 'hellaz-sitez-analyzer'); ?></p></div>').show();
                        }
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * Clear all transients via AJAX.
     */
    public function clear_cache_ajax() {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_hsz_%'");
        wp_send_json_success(['message' => __('Cache cleared successfully.', 'hellaz-sitez-analyzer')]);
    }

    /**
     * Get cached data or fetch new data if not cached.
     *
     * @param string $url The URL to analyze.
     * @return array Cached data or newly fetched data.
     */
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

    /**
     * Fetch data from an external source.
     *
     * @param string $url The URL to analyze.
     * @return array Fetched data.
     */

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
