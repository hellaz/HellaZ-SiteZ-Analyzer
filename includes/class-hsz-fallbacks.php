<?php
namespace HSZ;

class Fallbacks {
    public function __construct() {
        add_filter('hsz_fallback_image', [$this, 'get_fallback_image']);
        add_filter('hsz_fallback_description', [$this, 'get_fallback_description']);
    }

    /**
     * Get the fallback image URL.
     *
     * @return string The fallback image URL or an empty string if the image is missing.
     */
    public function get_fallback_image() {
        // Construct the fallback image path
        $fallback_image_path = HSZ_PLUGIN_DIR . 'assets/images/fallback-image.png';

        // Check if the fallback image exists
        if (file_exists($fallback_image_path)) {
            return HSZ_PLUGIN_URL . 'assets/images/fallback-image.png';
        }

        // Log a debug message if the fallback image is missing
        error_log('[HellaZ SiteZ Analyzer] Fallback image not found at ' . esc_html($fallback_image_path));
        return '';
    }

    /**
     * Get the fallback description.
     *
     * @return string The fallback description.
     */
    public function get_fallback_description() {
        return __('No description available.', 'hellaz-sitez-analyzer');
    }
}
