<?php
namespace HSZ;
if (!defined('ABSPATH')) exit;
class Security {
    public function validate_url($url) {
        $url = trim($url);
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \Exception(__('Invalid URL format', 'hellaz-sitez-analyzer'));
        }
        $bad = ['javascript:', 'data:', 'vbscript:', 'file://', 'ftp://'];
        foreach ($bad as $pattern) {
            if (stripos($url, $pattern) !== false) throw new \Exception(__('Potentially malicious URL detected', 'hellaz-sitez-analyzer'));
        }
        return esc_url_raw($url);
    }
    public function verify_nonce($action, $nonce = null) {
        if ($nonce === null) $nonce = $_POST['nonce'] ?? $_GET['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, $action)) {
            throw new \Exception(__('Security verification failed', 'hellaz-sitez-analyzer'));
        }
        return true;
    }
    public function sanitize_bulk_urls($urls_input) {
        $urls = [];
        if (is_string($urls_input)) $raw_urls = preg_split('/[\r\n,]+/', $urls_input);
        elseif (is_array($urls_input)) $raw_urls = $urls_input;
        else throw new \Exception(__('Invalid URL input format', 'hellaz-sitez-analyzer'));
        foreach ($raw_urls as $url) {
            $url = trim($url);
            if (!empty($url)) {
                try { $urls[] = $this->validate_url($url); }
                catch (\Exception $e) { Utils::log_error('Invalid URL skipped: ' . $url . ' - ' . $e->getMessage()); }
            }
        }
        return $urls;
    }
}
