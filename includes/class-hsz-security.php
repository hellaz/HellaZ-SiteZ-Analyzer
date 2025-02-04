<?php
namespace HSZ;

class Security {
    private $cache_key_prefix = 'hsz_api_cache_';

    public function get_ssl_info($url) {
        $ssl_info = [];

        // Extract hostname from URL
        $host = parse_url($url, PHP_URL_HOST);

        if ($host) {
            $context = stream_context_create(["ssl" => ["capture_peer_cert" => true]]);
            $stream = @stream_socket_client("ssl://$host:443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);

            if ($stream) {
                $params = stream_context_get_params($stream);
                $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);

                if ($cert) {
                    $ssl_info['valid_from'] = date('Y-m-d H:i:s', $cert['validFrom_time_t']);
                    $ssl_info['valid_to'] = date('Y-m-d H:i:s', $cert['validTo_time_t']);
                    $ssl_info['issuer'] = isset($cert['issuer']['O']) ? $cert['issuer']['O'] : __('Unknown', 'hellaz-sitez-analyzer');
                }

                fclose($stream);
            }
        }

        return $ssl_info;
    }

    public function get_security_analysis($url, $virustotal_api_key) {
        $cache_key = $this->cache_key_prefix . 'virustotal_' . md5($url);
        $cached_data = get_transient($cache_key);

        if ($cached_data) {
            return $cached_data; // Return cached data if available
        }

        if (empty($virustotal_api_key)) {
            $this->add_admin_notice(__('VirusTotal API key is missing.', 'hellaz-sitez-analyzer'));
            return ['error' => __('Security analysis unavailable.', 'hellaz-sitez-analyzer')];
        }

        // Extract hostname from URL
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return ['error' => __('Invalid URL.', 'hellaz-sitez-analyzer')];
        }

        // Make API request to VirusTotal
        $response = wp_remote_get(
            "https://www.virustotal.com/api/v3/domains/$host",
            [
                'headers' => [
                    'x-apikey' => $virustotal_api_key,
                ],
            ]
        );

        if (is_wp_error($response)) {
            $this->add_admin_notice(__('Failed to connect to VirusTotal API.', 'hellaz-sitez-analyzer'));
            return ['error' => __('Security analysis unavailable.', 'hellaz-sitez-analyzer')];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['error'])) {
            $this->add_admin_notice(sprintf(__('VirusTotal API Error: %s', 'hellaz-sitez-analyzer'), $body['error']['message']));
            return ['error' => __('Security analysis unavailable.', 'hellaz-sitez-analyzer')];
        }

        // Parse response
        $security_analysis = [];
        if (isset($body['data']['attributes'])) {
            $attributes = $body['data']['attributes'];
            $security_analysis['malicious'] = isset($attributes['last_analysis_stats']['malicious'])
                ? $attributes['last_analysis_stats']['malicious']
                : 0;
            $security_analysis['suspicious'] = isset($attributes['last_analysis_stats']['suspicious'])
                ? $attributes['last_analysis_stats']['suspicious']
                : 0;
            $security_analysis['harmless'] = isset($attributes['last_analysis_stats']['harmless'])
                ? $attributes['last_analysis_stats']['harmless']
                : 0;
        }

        // Cache the response for 24 hours
        set_transient($cache_key, $security_analysis, DAY_IN_SECONDS);

        return $security_analysis;
    }

    public function get_technology_stack($url, $builtwith_api_key) {
        $cache_key = $this->cache_key_prefix . 'builtwith_' . md5($url);
        $cached_data = get_transient($cache_key);

        if ($cached_data) {
            return $cached_data; // Return cached data if available
        }

        if (empty($builtwith_api_key)) {
            $this->add_admin_notice(__('BuiltWith API key is missing.', 'hellaz-sitez-analyzer'));
            return ['error' => __('Technology stack detection unavailable.', 'hellaz-sitez-analyzer')];
        }

        // Extract hostname from URL
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return ['error' => __('Invalid URL.', 'hellaz-sitez-analyzer')];
        }

        // Make API request to BuiltWith
        $response = wp_remote_get(
            "https://api.builtwith.com/v19/api.json?KEY=$builtwith_api_key&LOOKUP=$host"
        );

        if (is_wp_error($response)) {
            $this->add_admin_notice(__('Failed to connect to BuiltWith API.', 'hellaz-sitez-analyzer'));
            return ['error' => __('Technology stack detection unavailable.', 'hellaz-sitez-analyzer')];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['Errors'])) {
            $this->add_admin_notice(sprintf(__('BuiltWith API Error: %s', 'hellaz-sitez-analyzer'), $body['Errors'][0]['Message']));
            return ['error' => __('Technology stack detection unavailable.', 'hellaz-sitez-analyzer')];
        }

        // Parse response
        $technology_stack = [];
        if (isset($body['Results'][0]['Result']['Paths'])) {
            foreach ($body['Results'][0]['Result']['Paths'] as $path) {
                if (isset($path['Technologies'])) {
                    foreach ($path['Technologies'] as $tech) {
                        $technology_stack[] = $tech['Name'];
                    }
                }
            }
        }

        // Cache the response for 24 hours
        set_transient($cache_key, $technology_stack, DAY_IN_SECONDS);

        return $technology_stack ?: ['error' => __('No technology stack detected.', 'hellaz-sitez-analyzer')];
    }

    public function get_urlscan_analysis($url, $api_key) {
        $cache_key = $this->cache_key_prefix . 'urlscan_' . md5($url);
        $cached_data = get_transient($cache_key);
    
        if ($cached_data) {
            return $cached_data; // Return cached data if available
        }
    
        if (empty($api_key)) {
            $this->add_admin_notice(__('URLScan.io API key is missing.', 'hellaz-sitez-analyzer'));
            return ['error' => __('URLScan.io analysis unavailable.', 'hellaz-sitez-analyzer')];
        }
    
        // Make API request to URLScan.io
        $response = wp_remote_post(
            'https://urlscan.io/api/v1/scan/',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'API-Key' => $api_key,
                ],
                'body' => json_encode(['url' => $url]),
            ]
        );
    
        if (is_wp_error($response)) {
            $this->add_admin_notice(__('Failed to connect to URLScan.io API.', 'hellaz-sitez-analyzer'));
            return ['error' => __('URLScan.io analysis unavailable.', 'hellaz-sitez-analyzer')];
        }
    
        $body = json_decode(wp_remote_retrieve_body($response), true);
    
        if (isset($body['message'])) {
            $this->add_admin_notice(sprintf(__('URLScan.io API Error: %s', 'hellaz-sitez-analyzer'), $body['message']));
            return ['error' => __('URLScan.io analysis unavailable.', 'hellaz-sitez-analyzer')];
        }
    
        // Parse response
        $result_url = isset($body['result']) ? $body['result'] : '';
    
        // Cache the response for 24 hours
        set_transient($cache_key, $result_url, DAY_IN_SECONDS);
    
        return $result_url;
    }
    
    private function add_admin_notice($message) {
        add_action('admin_notices', function () use ($message) {
            echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
        });
    }
}
