<?php
namespace HSZ;

class Metadata {
    private $apimanager;

    public function __construct() {
        $this->apimanager = new APImanager();
    }

    /**
     * Extract metadata from a given URL.
     *
     * @param string $url The URL to analyze.
     * @return array Extracted metadata or an error message.
     */
    public function extract_metadata($url) {
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            Utils::log_admin_notice(__('Invalid URL:', 'hellaz-sitez-analyzer') . ' ' . $url);
            return ['error' => __('Invalid URL.', 'hellaz-sitez-analyzer')];
        }

        // Cache key for storing metadata
        $cache_key = 'hsz_metadata_' . md5($url);
        $cached_data = get_transient($cache_key);

        // Return cached data if available
        if ($cached_data) {
            return $cached_data;
        }

        // Fetch remote content
        $response = wp_remote_get($url, ['timeout' => 5]);
        if (is_wp_error($response)) {
            Utils::log_admin_notice(__('Failed to fetch remote content:', 'hellaz-sitez-analyzer') . ' ' . $response->get_error_message());
            return $cached_data ?: [];
        }

        // Check HTTP response code
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            Utils::log_admin_notice(sprintf(__('HTTP Error: %d', 'hellaz-sitez-analyzer'), $response_code));
            return $cached_data ?: [];
        }

        // Parse HTML content
        $html = wp_remote_retrieve_body($response);
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        libxml_clear_errors();

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
            'emails' => $this->get_emails($html, $dom),
            'contact_forms' => $this->get_contact_forms($url, $html, $dom),
            'address' => $this->get_address($dom, $url),
            'rss_feeds' => (new RSS())->detect_rss_feeds($html),
            'social_media' => (new SocialMedia())->detect_social_media_links($html),
            'ssl_info' => (new Security())->get_ssl_info($url),
        ];

        // Free APIs
        $metadata['server_location'] = $this->get_server_location($url); // IP-API

        // Premium APIs
        $virustotal_api_key = get_option('hsz_virustotal_api_key', '');
        if (!empty($virustotal_api_key)) {
            $metadata['security_analysis'] = (new Security())->get_security_analysis($url, $virustotal_api_key);
        }

        $urlscan_api_key = get_option('hsz_urlscan_api_key', '');
        if (!empty($urlscan_api_key)) {
            $metadata['urlscan_analysis'] = (new Security())->get_urlscan_analysis($url, $urlscan_api_key);
        }

        $builtwith_api_key = get_option('hsz_builtwith_api_key', '');
        if (!empty($builtwith_api_key)) {
            $metadata['technology_stack'] = (new Security())->get_technology_stack($url, $builtwith_api_key);
        }

        // Cache the results for 24 hours
        set_transient($cache_key, $metadata, DAY_IN_SECONDS);

        return $metadata;
    }

    /**
     * Get the content of a specific tag.
     *
     * @param \DOMDocument $dom The DOMDocument object.
     * @param string $tag The tag name.
     * @return string The tag content.
     */
    private function get_tag_content($dom, $tag) {
        $element = $dom->getElementsByTagName($tag)->item(0);
        return $element ? trim($element->textContent) : '';
    }

    /**
     * Get the value of a specific meta tag.
     *
     * @param \DOMDocument $dom The DOMDocument object.
     * @param string $name The meta tag name.
     * @return string The meta tag value.
     */
    private function get_meta_tag($dom, $name) {
        $meta_tags = $dom->getElementsByTagName('meta');
        foreach ($meta_tags as $meta) {
            if ($meta->getAttribute('name') === $name || $meta->getAttribute('property') === $name) {
                return trim($meta->getAttribute('content'));
            }
        }
        return '';
    }

    /**
     * Get the language of the document.
     *
     * @param \DOMDocument $dom The DOMDocument object.
     * @return string The document language.
     */
    private function get_language($dom) {
        $html_tag = $dom->getElementsByTagName('html')->item(0);
        return $html_tag ? $html_tag->getAttribute('lang') : '';
    }

    /**
     * Get the canonical URL.
     *
     * @param \DOMDocument $dom The DOMDocument object.
     * @return string The canonical URL.
     */
    private function get_canonical_url($dom) {
        $links = $dom->getElementsByTagName('link');
        foreach ($links as $link) {
            if ($link->getAttribute('rel') === 'canonical') {
                return $link->getAttribute('href');
            }
        }
        return '';
    }

    /**
     * Get the favicon URL.
     *
     * @param \DOMDocument $dom The DOMDocument object.
     * @param string $base_url The base URL.
     * @return string The favicon URL.
     */
    private function get_favicon($dom, $base_url) {
        $icons = $dom->getElementsByTagName('link');
        foreach ($icons as $icon) {
            if (in_array($icon->getAttribute('rel'), ['icon', 'shortcut icon'])) {
                return $this->resolve_url($icon->getAttribute('href'), $base_url);
            }
        }
        return '';
    }

    /**
     * Resolve a relative URL to an absolute URL.
     *
     * @param string $relative_url The relative URL.
     * @param string $base_url The base URL.
     * @return string The resolved URL.
     */
    private function resolve_url($relative_url, $base_url) {
        return filter_var($relative_url, FILTER_VALIDATE_URL) ? $relative_url : rtrim($base_url, '/') . '/' . ltrim($relative_url, '/');
    }

    /**
     * Extract emails from the HTML content.
     *
     * @param string $html The HTML content.
     * @param \DOMDocument $dom The DOMDocument object.
     * @return array Extracted emails.
     */
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

    /**
     * Extract contact forms from the HTML content.
     *
     * @param string $url The base URL.
     * @param string $html The HTML content.
     * @param \DOMDocument $dom The DOMDocument object.
     * @return array Extracted contact form URLs.
     */
    private function get_contact_forms($url, $html, $dom) {
        $forms = [];
        $contact_keywords = ['contact', 'kontakt', 'contato', '聯絡', '連絡', 'contacto']; // Add more languages as needed

        // Cache key for storing contact form URLs
        $cache_key = 'hsz_contact_forms_' . md5($url);
        $cached_forms = get_transient($cache_key);

        if ($cached_forms) {
            return $cached_forms; // Return cached results if available
        }

        // Extract forms by <form> tags on the main page
        $form_tags = $dom->getElementsByTagName('form');
        foreach ($form_tags as $form) {
            $action = $form->getAttribute('action');
            if (!empty($action)) {
                $resolved_url = $this->normalize_and_validate_url($action, $url);
                if ($resolved_url && $this->is_valid_contact_form_url($resolved_url)) {
                    $forms[] = $resolved_url;
                }
            }
        }

        // Extract links with contact-related keywords
        $links = $dom->getElementsByTagName('a');
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $text = strtolower(trim($link->textContent));
            foreach ($contact_keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    $resolved_url = $this->normalize_and_validate_url($href, $url);
                    if ($resolved_url && $this->is_valid_contact_form_url($resolved_url)) {
                        $forms[] = $resolved_url;
                    }
                }
            }
        }

        // Follow common contact page URLs and extract forms (limited to 3 attempts)
        $common_contact_paths = ['/contact/', '/kontakt/', '/contato/', '/contact-us/', '/contacto/'];
        $max_attempts = 3; // Limit the number of external requests
        $attempts = 0;

        foreach ($common_contact_paths as $path) {
            if ($attempts >= $max_attempts) {
                break; // Stop after reaching the limit
            }

            $contact_url = rtrim($url, '/') . $path;
            $contact_response = wp_remote_get($contact_url);

            if (!is_wp_error($contact_response) && wp_remote_retrieve_response_code($contact_response) === 200) {
                $attempts++;
                $contact_html = wp_remote_retrieve_body($contact_response);
                libxml_use_internal_errors(true);
                $contact_dom = new \DOMDocument();
                @$contact_dom->loadHTML($contact_html);
                libxml_clear_errors();

                $contact_forms = $contact_dom->getElementsByTagName('form');
                foreach ($contact_forms as $form) {
                    $action = $form->getAttribute('action');
                    if (!empty($action)) {
                        $resolved_url = $this->normalize_and_validate_url($action, $contact_url);
                        if ($resolved_url && $this->is_valid_contact_form_url($resolved_url)) {
                            $forms[] = $resolved_url;
                        }
                    }
                }
            }
        }

        // Remove duplicates and cache the results for 24 hours
        $forms = array_unique($forms);
        set_transient($cache_key, $forms, DAY_IN_SECONDS);

        return $forms;
    }

    /**
     * Normalize and validate a URL.
     *
     * @param string $url The URL to normalize.
     * @param string $base_url The base URL.
     * @return string The normalized URL.
     */
    private function normalize_and_validate_url($url, $base_url) {
        // Resolve relative URLs
        $resolved_url = $this->resolve_url($url, $base_url);

        // Remove redundant hostnames (e.g., https://example.com/example.com)
        $parsed_base = parse_url($base_url);
        $parsed_resolved = parse_url($resolved_url);
        if (isset($parsed_base['host']) && isset($parsed_resolved['host'])) {
            if (strpos($resolved_url, $parsed_base['host'] . '/' . $parsed_base['host']) !== false) {
                $resolved_url = str_replace($parsed_base['host'] . '/' . $parsed_base['host'], $parsed_base['host'], $resolved_url);
            }
        }

        // Validate the final URL
        return filter_var($resolved_url, FILTER_VALIDATE_URL) ? $resolved_url : null;
    }

    /**
     * Check if a URL is a valid contact form URL.
     *
     * @param string $url The URL to check.
     * @return bool Whether the URL is valid.
     */
    private function is_valid_contact_form_url($url) {
        // Exclude URLs with query strings that are unlikely to be contact forms
        $excluded_keywords = ['search', 'login', 'logout', 'register', 'cart', 'checkout'];
        foreach ($excluded_keywords as $keyword) {
            if (stripos($url, $keyword) !== false) {
                return false;
            }
        }

        // Ensure the URL points to a valid contact form path
        $valid_paths = ['/contact', '/kontakt', '/contato', '/contact-us', '/contacto'];
        $parsed_url = parse_url($url);
        if (isset($parsed_url['path'])) {
            foreach ($valid_paths as $path) {
                if (stripos($parsed_url['path'], $path) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Extract the server location using IP-API.
     *
     * @param string $url The URL to analyze.
     * @return string The server location.
     */
    private function get_server_location($url) {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return '';
        }

        $response = wp_remote_get("http://ip-api.com/json/$host");
        if (is_wp_error($response)) {
            Utils::log_admin_notice(__('Failed to fetch server location:', 'hellaz-sitez-analyzer') . ' ' . $response->get_error_message());
            return '';
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return isset($body['city'], $body['country']) ? "{$body['city']}, {$body['country']}" : '';
    }
}
