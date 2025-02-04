<?php
namespace HSZ;

class Security {
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
        $security_analysis = [];

        // Validate API key
        if (empty($virustotal_api_key)) {
            return ['error' => __('VirusTotal API key is missing.', 'hellaz-sitez-analyzer')];
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
            return ['error' => __('Failed to connect to VirusTotal API.', 'hellaz-sitez-analyzer')];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['error'])) {
            return ['error' => sprintf(__('VirusTotal API Error: %s', 'hellaz-sitez-analyzer'), $body['error']['message'])];
        }

        // Parse response
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

        return $security_analysis;
    }

    public function get_technology_stack($url, $builtwith_api_key) {
        $technology_stack = [];

        // Validate API key
        if (empty($builtwith_api_key)) {
            return ['error' => __('BuiltWith API key is missing.', 'hellaz-sitez-analyzer')];
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
            return ['error' => __('Failed to connect to BuiltWith API.', 'hellaz-sitez-analyzer')];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['Errors'])) {
            return ['error' => sprintf(__('BuiltWith API Error: %s', 'hellaz-sitez-analyzer'), $body['Errors'][0]['Message'])];
        }

        // Parse response
        if (isset($body['Results'][0]['Result']['Paths'])) {
            foreach ($body['Results'][0]['Result']['Paths'] as $path) {
                if (isset($path['Technologies'])) {
                    foreach ($path['Technologies'] as $tech) {
                        $technology_stack[] = $tech['Name'];
                    }
                }
            }
        }

        return $technology_stack;
    }
}
