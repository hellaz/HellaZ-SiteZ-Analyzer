<?php
namespace HSZ;

class Security {
    private $apimanager;

    public function __construct() {
        $this->apimanager = new APIManager();
    }

    /**
     * Get SSL information using either the SSL Labs API or direct certificate parsing.
     *
     * @param string $url The URL to analyze.
     * @return array SSL information or an empty array if the request fails.
     */
    public function get_ssl_info($url) {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return [];
        }

        // Option 1: Use SSL Labs API for detailed analysis
        $ssl_labs_data = $this->get_ssl_info_from_ssllabs($host);
        if (!empty($ssl_labs_data)) {
            return $ssl_labs_data;
        }

        // Option 2: Fallback to direct certificate parsing
        return $this->get_ssl_info_from_certificate($host);
    }

    /**
     * Get SSL information using the SSL Labs API.
     *
     * @param string $host The hostname to analyze.
     * @return array SSL information or an empty array if the request fails.
     */
    private function get_ssl_info_from_ssllabs($host) {
        $cache_key = 'hsz_ssl_info_' . md5($host);

        $response = $this->api_manager->make_api_request(
            "https://api.ssllabs.com/api/v3/analyze?host=$host",
            [],
            $cache_key,
            DAY_IN_SECONDS
        );

        // Check if the response contains valid SSL data
        if (isset($response['status']) && $response['status'] === 'READY') {
            return [
                'grade' => $response['endpoints'][0]['grade'] ?? __('N/A', 'hellaz-sitez-analyzer'),
                'details' => $response,
            ];
        }

        return [];
    }

    /**
     * Get SSL information by parsing the certificate directly.
     *
     * @param string $host The hostname to analyze.
     * @return array SSL information or an empty array if the request fails.
     */
    private function get_ssl_info_from_certificate($host) {
        $ssl_info = [];
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
        return $ssl_info;
    }

    /**
     * Get security analysis using VirusTotal API.
     *
     * @param string $url The URL to analyze.
     * @param string $api_key The VirusTotal API key.
     * @return array Security analysis data or an empty array if the request fails.
     */
    public function get_security_analysis($url, $api_key) {
        if (empty($api_key)) {
            return [];
        }

        $cache_key = 'hsz_virustotal_' . md5($url);

        $response = $this->api_manager->make_api_request(
            "https://www.virustotal.com/api/v3/urls/$url",
            ['headers' => ['x-apikey' => $api_key]],
            $cache_key,
            HOUR_IN_SECONDS
        );

        // Parse the VirusTotal response
        if (isset($response['data']['attributes'])) {
            $attributes = $response['data']['attributes'];
            return [
                'malicious' => $attributes['last_analysis_stats']['malicious'] ?? 0,
                'suspicious' => $attributes['last_analysis_stats']['suspicious'] ?? 0,
                'harmless' => $attributes['last_analysis_stats']['harmless'] ?? 0,
                'details' => $attributes,
            ];
        }

        return [];
    }

    /**
     * Get technology stack using BuiltWith API.
     *
     * @param string $url The URL to analyze.
     * @param string $api_key The BuiltWith API key.
     * @return array Technology stack data or an empty array if the request fails.
     */
    public function get_technology_stack($url, $api_key) {
        if (empty($api_key)) {
            return [];
        }

        $cache_key = 'hsz_builtwith_' . md5($url);

        $response = $this->api_manager->make_api_request(
            "https://api.builtwith.com/v19/api.json?KEY=$api_key&LOOKUP=$url",
            [],
            $cache_key,
            DAY_IN_SECONDS
        );

        // Parse the BuiltWith response
        if (isset($response['Results'][0]['Result']['Paths'])) {
            $technologies = [];
            foreach ($response['Results'][0]['Result']['Paths'] as $path) {
                foreach ($path['Technologies'] as $tech) {
                    $technologies[] = $tech['Name'];
                }
            }
            return array_unique($technologies);
        }

        return [];
    }

    /**
     * Get URLScan.io analysis for a domain.
     *
     * @param string $url The URL to analyze.
     * @param string $api_key The URLScan.io API key.
     * @return array URLScan.io data or an empty array if the request fails.
     */
    public function get_urlscan_analysis($url, $api_key) {
        if (empty($api_key)) {
            return [];
        }

        $cache_key = 'hsz_urlscan_' . md5($url);

        $response = $this->api_manager->make_api_request(
            "https://urlscan.io/api/v1/search/?q=domain:$url",
            ['headers' => ['API-Key' => $api_key]],
            $cache_key,
            HOUR_IN_SECONDS
        );

        // Parse the URLScan.io response
        if (isset($response['results'])) {
            $results = [];
            foreach ($response['results'] as $result) {
                $results[] = [
                    'url' => $result['page']['url'] ?? '',
                    'score' => $result['verdicts']['overall']['score'] ?? 0,
                    'malicious' => $result['verdicts']['overall']['malicious'] ?? false,
                ];
            }
            return $results;
        }

        return [];
    }
}
