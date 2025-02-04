<?php
namespace HSZ;

class Metadata {
    public function extract_metadata($url) {
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['error' => __('Invalid URL.', 'hellaz-sitez-analyzer')];
        }

        // Fetch remote content
        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            return ['error' => __('Failed to fetch remote content.', 'hellaz-sitez-analyzer')];
        }

        // Check HTTP response code
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return ['error' => sprintf(__('HTTP Error: %d', 'hellaz-sitez-analyzer'), $response_code)];
        }

        // Parse HTML content
        $html = wp_remote_retrieve_body($response);

        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        libxml_clear_errors();

        // Extract headers
        $headers = wp_remote_retrieve_headers($response);

        // Initialize Security class
        $security = new Security();

        // Extract standard metadata
        $metadata = [
            'title' => $this->get_tag_content($dom, 'title'),
            'description' => $this->get_meta_tag($dom, 'description'),
            'keywords' => $this->get_meta_tag($dom, 'keywords'),
            'author' => $this->get_meta_tag($dom, 'author'),
            'referrer' => $this->get_meta_tag($dom, 'referrer'),
            'language' => $this->get_language($dom),
            'og:title' => $this->get_meta_tag($dom, 'og:title'),
            'twitter:title' => $this->get_meta_tag($dom, 'twitter:title'),
            'canonical_url' => $this->get_canonical_url($dom),
            'favicon' => $this->get_favicon($dom, $url),
            'emails' => $this->get_emails($html),
            'contact_forms' => $this->get_contact_forms($html),
            'rss_feeds' => (new RSS())->detect_rss_feeds($html),
            'social_media' => (new SocialMedia())->detect_social_media_links($html),
            'ssl_info' => $security->get_ssl_info($url), // Ensure this method exists
        ];

        // Free APIs
        $metadata['server_location'] = $this->get_server_location($url); // IP-API

        // Premium APIs
        $virustotal_api_key = get_option('hsz_virustotal_api_key', '');
        if (!empty($virustotal_api_key)) {
            $metadata['security_analysis'] = $security->get_security_analysis($url, $virustotal_api_key);
        }

        $urlscan_api_key = get_option('hsz_urlscan_api_key', '');
        if (!empty($urlscan_api_key)) {
            $metadata['urlscan_analysis'] = $this->get_urlscan_analysis($url, $urlscan_api_key);
        }

        $builtwith_api_key = get_option('hsz_builtwith_api_key', '');
        if (!empty($builtwith_api_key)) {
            $metadata['technology_stack'] = $security->get_technology_stack($url, $builtwith_api_key);
        }

        return $metadata;
    }

    private function get_tag_content($dom, $tag) {
        $element = $dom->getElementsByTagName($tag)->item(0);
        return $element ? trim($element->textContent) : '';
    }

    private function get_meta_tag($dom, $name) {
        $meta_tags = $dom->getElementsByTagName('meta');
        foreach ($meta_tags as $meta) {
            if ($meta->getAttribute('name') === $name || $meta->getAttribute('property') === $name) {
                return trim($meta->getAttribute('content'));
            }
        }
        return '';
    }

    private function get_language($dom) {
        $html_tag = $dom->getElementsByTagName('html')->item(0);
        return $html_tag ? $html_tag->getAttribute('lang') : '';
    }

    private function get_canonical_url($dom) {
        $links = $dom->getElementsByTagName('link');
        foreach ($links as $link) {
            if ($link->getAttribute('rel') === 'canonical') {
                return $link->getAttribute('href');
            }
        }
        return '';
    }

    private function get_favicon($dom, $url) {
        $icons = $dom->getElementsByTagName('link');
        foreach ($icons as $icon) {
            if (in_array($icon->getAttribute('rel'), ['icon', 'shortcut icon'])) {
                return $this->resolve_url($icon->getAttribute('href'), $url);
            }
        }
        return '';
    }

    private function resolve_url($relative_url, $base_url) {
        return filter_var($relative_url, FILTER_VALIDATE_URL) ? $relative_url : rtrim($base_url, '/') . '/' . ltrim($relative_url, '/');
    }

    private function get_emails($html, $dom) {
        $emails = [];

        // Extract emails from plain text
        preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $html, $matches);
        $emails = array_unique(array_merge($emails, $matches[0]));

        // Extract emails from mailto: links
        $links = $dom->getElementsByTagName('a');
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if (strpos($href, 'mailto:') === 0) {
                $email = substr($href, 7);
                $emails[] = $email;
            }
        }

        return array_unique($emails);
    }

    private function get_contact_forms($html, $dom) {
        $forms = [];
        $keywords = ['contact', 'kontakt', 'contato', '聯絡', '連絡', 'contacto']; // Add more languages as needed

        // Extract forms by <form> tags
        $form_tags = $dom->getElementsByTagName('form');
        foreach ($form_tags as $form) {
            $action = $form->getAttribute('action');
            if (!empty($action)) {
                $forms[] = $action;
            }
        }

        // Extract links with contact-related keywords
        $links = $dom->getElementsByTagName('a');
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $text = strtolower(trim($link->textContent));
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false && filter_var($href, FILTER_VALIDATE_URL)) {
                    $forms[] = $href;
                }
            }
        }

        return array_unique($forms);
    }

    private function get_address($dom) {
        $address = '';

        // Extract address from JSON-LD structured data
        $scripts = $dom->getElementsByTagName('script');
        foreach ($scripts as $script) {
            if ($script->getAttribute('type') === 'application/ld+json') {
                $json = json_decode($script->textContent, true);
                if (isset($json['@type']) && $json['@type'] === 'Organization' && isset($json['address'])) {
                    $address = is_array($json['address']) ? implode(', ', $json['address']) : $json['address'];
                    break;
                }
            }
        }

        // Fallback: Extract address-like patterns
        if (empty($address)) {
            $body = $dom->getElementsByTagName('body')->item(0)->textContent;
            $patterns = [
                '/\b\d{1,5}\s+\w+\s+(?:street|st|avenue|ave|road|rd|boulevard|blvd)\b/i',
                '/\b(?:city|town|village)\b\s*:\s*\w+/i',
                '/\bzip\s*:\s*\d{5}/i',
                '/\bcountry\s*:\s*\w+/i',
            ];
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $body, $match)) {
                    $address .= $match[0] . ', ';
                }
            }
            $address = rtrim($address, ', ');
        }

        return $address;
    }

    private function get_server_location($url) {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return '';
        }

        $response = wp_remote_get("http://ip-api.com/json/$host");
        if (is_wp_error($response)) {
            return '';
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return isset($body['city'], $body['country']) ? "{$body['city']}, {$body['country']}" : '';
    }

    private function get_urlscan_analysis($url, $api_key) {
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
            return '';
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return isset($body['result']) ? $body['result'] : '';
    }
}
