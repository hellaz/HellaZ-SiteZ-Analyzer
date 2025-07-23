<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

/**
 * Provides fallback/default values for metadata, images, and output.
 */
class Fallbacks {
    public static function get_fallback_image() {
        return get_option('hsz_fallback_image', HSZ_PLUGIN_URL . 'assets/images/default-favicon.png');
    }

    public static function get_fallback_title() {
        return get_option('hsz_fallback_title', __('No Title', 'hellaz-sitez-analyzer'));
    }

    public static function get_fallback_description() {
        return get_option('hsz_fallback_description', __('No description available.', 'hellaz-sitez-analyzer'));
    }

    public static function get_disclaimer() {
        if (get_option('hsz_disclaimer_enabled')) {
            return get_option('hsz_disclaimer_message', __('Information is for reference only.', 'hellaz-sitez-analyzer'));
        }
        return '';
    }
}
