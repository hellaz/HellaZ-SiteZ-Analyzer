<?php
namespace HSZ;

class Security {
    public function get_ssl_info($url) {
        $parsed_url = parse_url($url);
        $host = $parsed_url['host'];
        $ssl_info = [];

        // Check SSL certificate
        $context = stream_context_create(["ssl" => ["capture_peer_cert" => true]]);
        $stream = stream_socket_client("ssl://$host:443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
        if ($stream) {
            $params = stream_context_get_params($stream);
            $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
            $ssl_info['valid_from'] = date('Y-m-d', $cert['validFrom_time_t']);
            $ssl_info['valid_to'] = date('Y-m-d', $cert['validTo_time_t']);
            fclose($stream);
        }

        return $ssl_info;
    }
}
