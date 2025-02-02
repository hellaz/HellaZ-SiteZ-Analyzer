<?php
namespace HSZ;

class Fallbacks {
    public function __construct() {
        add_filter('hsz_fallback_image', [$this, 'get_fallback_image']);
        add_filter('hsz_fallback_description', [$this, 'get_fallback_description']);
    }

    public function get_fallback_image() {
        return HSZ_PLUGIN_URL . 'assets/images/fallback-image.png';
    }

    public function get_fallback_description() {
        return __('No description available.', 'hellaz-sitez-analyzer');
    }
}
