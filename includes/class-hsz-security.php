<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

class Security {
    public function validate_url($url) {
        $url = trim($url);
        if (!filter_var($url, FILTER_VALIDATE_URL)) throw new \Exception(__('Invalid URL format', 'hellaz-sitez-analyzer'));
        return esc_url_raw($url);
    }
    public function sanitize_bulk_urls($urls_input) {
        $urls = array();
        if (is_string($urls_input)) {
            $raw_urls = preg_split('/[\r\n,]+/', $urls_input);
        } elseif (is_array($urls_input)) {
            $raw_urls = $urls_input;
        } else {
            throw new \Exception(__('Invalid URL input format', 'hellaz-sitez-analyzer'));
        }
        foreach ($raw_urls as $url) {
            $url = trim($url);
            if (!empty($url)) {
                try {
                    $urls[] = $this->validate_url($url);
                } catch (\Exception $e) {}
            }
        }
        return $urls;
    }
}
