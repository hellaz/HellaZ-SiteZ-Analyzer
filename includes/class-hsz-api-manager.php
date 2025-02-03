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
}
