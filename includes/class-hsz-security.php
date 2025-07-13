<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

class Security {
    private $rate_limits = array();
    
    public function __construct() {
        $this->rate_limits = array(
            'metadata_extraction' => array('limit' => 60, 'window' => 3600), // 60 requests per hour
            'api_requests' => array('limit' => 100, 'window' => 3600), // 100 requests per hour
            'cache_clear' => array('limit' => 10, 'window' => 3600) // 10 cache clears per hour
        );
        
        add_action('init', array($this, 'security_headers'));
        add_action('wp_ajax_hsz_analyze_url', array($this, 'handle_ajax_analyze'));
        add_action('wp_ajax_nopriv_hsz_analyze_url', array($this, 'handle_ajax_analyze'));
    }
    
    public function security_headers() {
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
    }
    
    public function validate_url($url) {
        $url = trim($url);
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \Exception(__('Invalid URL format', 'hellaz-sitez-analyzer'));
        }
        
        // Check for malicious patterns
        $malicious_patterns = array(
            'javascript:',
            'data:',
            'vbscript:',
            'file://',
            'ftp://'
        );
        
        foreach ($malicious_patterns as $pattern) {
            if (stripos($url, $pattern) !== false) {
                throw new \Exception(__('Potentially malicious URL detected', 'hellaz-sitez-analyzer'));
            }
        }
        
        // Check for localhost/internal IPs in production
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            $parsed = parse_url($url);
            if (isset($parsed['host'])) {
                $host = $parsed['host'];
                if (in_array($host, array('localhost', '127.0.0.1', '::1')) || 
                    filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                    throw new \Exception(__('Local or private URLs are not allowed', 'hellaz-sitez-analyzer'));
                }
            }
        }
        
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
        
        $max_urls = apply_filters('hsz_max_bulk_urls', 100);
        if (count($raw_urls) > $max_urls) {
            throw new \Exception(sprintf(__('Maximum %d URLs allowed', 'hellaz-sitez-analyzer'), $max_urls));
        }
        
        foreach ($raw_urls as $url) {
            $url = trim($url);
            if (!empty($url)) {
                try {
                    $urls[] = $this->validate_url($url);
                } catch (\Exception $e) {
                    error_log('[HellaZ SiteZ Analyzer] Invalid URL skipped: ' . $url . ' - ' . $e->getMessage());
                }
            }
        }
        
        return $urls;
    }
    
    public function check_rate_limit($action, $user_id = null) {
        if (!isset($this->rate_limits[$action])) {
            return true;
        }
        
        $user_id = $user_id ?: get_current_user_id();
        $ip = $this->get_client_ip();
        $key = "hsz_rate_limit_{$action}_{$user_id}_{$ip}";
        
        $current_count = get_transient($key) ?: 0;
        $limit = $this->rate_limits[$action]['limit'];
        
        if ($current_count >= $limit) {
            return false;
        }
        
        set_transient($key, $current_count + 1, $this->rate_limits[$action]['window']);
        return true;
    }
    
    public function verify_nonce($action, $nonce = null) {
        if ($nonce === null) {
            $nonce = $_POST['nonce'] ?? $_GET['nonce'] ?? '';
        }
        
        if (!wp_verify_nonce($nonce, $action)) {
            throw new \Exception(__('Security verification failed', 'hellaz-sitez-analyzer'));
        }
        
        return true;
    }
    
    public function sanitize_settings($settings) {
        $sanitized = array();
        
        foreach ($settings as $key => $value) {
            switch ($key) {
                case 'hsz_fallback_image':
                    $sanitized[$key] = esc_url_raw($value);
                    break;
                case 'hsz_enable_disclaimer':
                    $sanitized[$key] = (bool) $value;
                    break;
                case 'hsz_disclaimer_message':
                    $sanitized[$key] = sanitize_textarea_field($value);
                    break;
                case 'hsz_cache_duration':
                    $sanitized[$key] = max(0, intval($value));
                    break;
                case 'hsz_link_target':
                    $sanitized[$key] = in_array($value, array('_blank', '_self')) ? $value : '_blank';
                    break;
                case 'hsz_icon_library':
                    $allowed_libraries = array('fontawesome', 'material', 'bootstrap', 'custom');
                    $sanitized[$key] = in_array($value, $allowed_libraries) ? $value : 'fontawesome';
                    break;
                default:
                    if (strpos($key, '_api_key') !== false) {
                        $sanitized[$key] = $this->encrypt_api_key($value);
                    } else {
                        $sanitized[$key] = sanitize_text_field($value);
                    }
            }
        }
        
        return $sanitized;
    }
    
    public function encrypt_api_key($key) {
        if (empty($key)) {
            return '';
        }
        
        $encryption_key = $this->get_encryption_key();
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($key, 'AES-256-CBC', $encryption_key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    public function decrypt_api_key($encrypted_key) {
        if (empty($encrypted_key)) {
            return '';
        }
        
        $data = base64_decode($encrypted_key);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        $encryption_key = $this->get_encryption_key();
        
        return openssl_decrypt($encrypted, 'AES-256-CBC', $encryption_key, 0, $iv);
    }
    
    private function get_encryption_key() {
        $key = get_option('hsz_encryption_key');
        if (!$key) {
            $key = wp_generate_password(32, false);
            update_option('hsz_encryption_key', $key);
        }
        return $key;
    }
    
    private function get_client_ip() {
        $ip_headers = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    public function handle_ajax_analyze() {
        try {
            $this->verify_nonce('hsz_analyze_nonce');
            
            if (!$this->check_rate_limit('metadata_extraction')) {
                throw new \Exception(__('Rate limit exceeded. Please try again later.', 'hellaz-sitez-analyzer'));
            }
            
            $url = sanitize_text_field($_POST['url'] ?? '');
            $validated_url = $this->validate_url($url);
            
            $metadata = (new Metadata())->extract_metadata($validated_url);
            
            wp_send_json_success($metadata);
            
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
}
