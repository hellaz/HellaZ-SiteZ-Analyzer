<?php
namespace HSZ;

if (!defined('ABSPATH')) {
    exit;
}

class Metadata {
    private $cache_expiration;

    public function __construct() {
        $this->cache_expiration = get_option('hsz_cache_duration', DAY_IN_SECONDS);
    }

    /**
     * Extract comprehensive metadata from a given URL.
     *
     * @param string $url URL to analyze.
     * @return array Metadata including title, description, favicon, social media, feeds, robots, etc.
     */
    public function extract_metadata(string $url): array {
        // Attempt cached data first
        $cache_key = 'metadata_' . md5($url);
        $cached = Utils::get_cached_data($cache_key);

        // Ensure only array is returned (minimal fix)
        if (is_array($cached)) {
            return $cached;
        }

        $result = [];

        try {
            $content = $this->fetch_content($url);
            if (!$content) {
                return ['error' => __('Failed to fetch content from URL.', 'hellaz-sitez-analyzer')];
            }

            // Load HTML into DOMDocument
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML($content);
            libxml_clear_errors();

            $xpath = new \DOMXPath($dom);

            // Extract standard meta tags
            $result['title'] = $this->get_title($dom);
            $result['description'] = $this->get_meta_tag($xpath, 'description');
            $result['favicon'] = $this->get_favicon($xpath, $url);
            $result['language'] = $this->get_language($dom);

            // Extract Open Graph tags
            $result['og'] = $this->get_open_graph_tags($xpath);

            // Extract Twitter Card tags
            $result['twitter'] = $this->get_twitter_card_tags($xpath);

            // Extract canonical url
            $result['canonical'] = $this->get_canonical($xpath, $url);

            // Extract robots meta
            $result['robots'] = $this->get_meta_tag($xpath, 'robots');

            // Extract theme color
            $result['theme_color'] = $this->get_meta_tag($xpath, 'theme-color');

            // Extract RSS/Atom feeds
            $result['feeds'] = $this->get_rss_feeds($xpath, $url);

            // Extract contact and about info (basic, partial)
            $result['contact_email'] = $this->find_contact_email($dom);
            $result['phone_numbers'] = $this->find_phone_numbers($dom);

            // (If any API integrations are present in your current code, they remain here)

            // Fallbacks for missing critical fields
            $result['title'] = $result['title'] ?: Fallbacks::get_fallback_title();
            $result['description'] = $result['description'] ?: Fallbacks::get_fallback_description();
            $result['favicon'] = $result['favicon'] ?: Fallbacks::get_fallback_image();

            Utils::set_cached_data($cache_key, $result, $this->cache_expiration);

            return $result;

        } catch (\Throwable $e) {
            Utils::log_error('Metadata extraction error: ' . $e->getMessage(), ['url' => $url]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Fetch content of a URL with timeout & user-agent.
     */
    private function fetch_content(string $url) {
        $response = wp_remote_get($url, ['timeout' => 12, 'user-agent' => 'Mozilla/5.0 (compatible; SiteZ Analyzer Bot)']);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
            return false;
        }
        return wp_remote_retrieve_body($response);
    }

    /**
     * Get <title> tag content.
     */
    private function get_title(\DOMDocument $dom): string {
        $titles = $dom->getElementsByTagName('title');
        if ($titles->length > 0) {
            return trim($titles->item(0)->textContent);
        }
        return '';
    }

    /**
     * Get meta tag content by name.
     */
    private function get_meta_tag(\DOMXPath $xpath, string $name): string {
        $query = sprintf("//meta[@name='%s']/@content", $name);
        $entries = $xpath->query($query);
        if ($entries->length > 0) {
            return trim($entries->item(0)->nodeValue);
        }
        return '';
    }

    /**
     * Get favicon URL from <link rel> tags.
     */
    private function get_favicon(\DOMXPath $xpath, string $url): string {
        $query = "//link[contains(translate(@rel, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'icon')]";
        $nodes = $xpath->query($query);
        foreach ($nodes as $node) {
            $href = $node->getAttribute('href');
            if ($href) {
                return $this->make_absolute_url($href, $url);
            }
        }
        // Fallback to /favicon.ico
        $parsed = parse_url($url);
        if (!empty($parsed['scheme']) && !empty($parsed['host'])) {
            return $parsed['scheme'] . '://' . $parsed['host'] . '/favicon.ico';
        }
        return '';
    }

    /**
     * Get canonical URL.
     */
    private function get_canonical(\DOMXPath $xpath, string $url): string {
        $query = "//link[@rel='canonical']/@href";
        $nodes = $xpath->query($query);
        if ($nodes->length > 0) {
            return $this->make_absolute_url($nodes->item(0)->nodeValue, $url);
        }
        return $url; // fallback to original url
    }

    /**
     * Get Open Graph tags.
     */
    private function get_open_graph_tags(\DOMXPath $xpath): array {
        $ogTags = [];
        $query = "//meta[starts-with(@property, 'og:')]";
        $nodes = $xpath->query($query);
        foreach ($nodes as $node) {
            $property = $node->getAttribute('property');
            $content = $node->getAttribute('content');
            if ($property && $content) {
                $ogTags[str_replace('og:', '', $property)] = $content;
            }
        }
        return $ogTags;
    }

    /**
     * Get Twitter Card tags.
     */
    private function get_twitter_card_tags(\DOMXPath $xpath): array {
        $twTags = [];
        $query = "//meta[starts-with(@name, 'twitter:')]";
        $nodes = $xpath->query($query);
        foreach ($nodes as $node) {
            $name = $node->getAttribute('name');
            $content = $node->getAttribute('content');
            if ($name && $content) {
                $twTags[str_replace('twitter:', '', $name)] = $content;
            }
        }
        return $twTags;
    }

    /**
     * Get language from <html lang=...>.
     */
    private function get_language(\DOMDocument $dom): string {
        $htmlTags = $dom->getElementsByTagName('html');
        if ($htmlTags->length > 0) {
            $lang = $htmlTags->item(0)->getAttribute('lang');
            return $lang ?: '';
        }
        return '';
    }

    /**
     * Get RSS/Atom feed URLs.
     */
    private function get_rss_feeds(\DOMXPath $xpath, string $baseUrl): array {
        $feeds = [];
        $query = "//link[contains(@type, 'rss') or contains(@type, 'atom')]/@href";
        $nodes = $xpath->query($query);
        foreach ($nodes as $node) {
            $href = $node->nodeValue;
            if ($href) {
                $feeds[] = $this->make_absolute_url($href, $baseUrl);
            }
        }
        return array_unique($feeds);
    }

    /**
     * Finds a contact email on the page by regex searching text.
     */
    private function find_contact_email(\DOMDocument $dom): string {
        $body = $dom->getElementsByTagName('body');
        if ($body->length === 0) return '';
        $text = $body->item(0)->textContent;
        if (preg_match('/[a-z0-9_\-\+\.]+@[a-z0-9\-]+\.[a-z]{2,}/i', $text, $matches)) {
            return $matches[0];
        }
        return '';
    }

    /**
     * Finds phone numbers on the page by regex searching text.
     */
    private function find_phone_numbers(\DOMDocument $dom): array {
        $numbers = [];
        $body = $dom->getElementsByTagName('body');
        if ($body->length === 0) return $numbers;
        $text = $body->item(0)->textContent;
        if (preg_match_all('/\+?[\d\s\-\(\)]{7,}/', $text, $matches)) {
            foreach ($matches[0] as $match) {
                $num = preg_replace('/[^\+\d]/', '', $match);
                if ($num) {
                    $numbers[] = $num;
                }
            }
        }
        return array_unique($numbers);
    }

    /**
     * Make relative URLs absolute based on base URL.
     */
    private function make_absolute_url(string $url, string $base): string {
        // If URL is absolute, return as is
        if (parse_url($url, PHP_URL_SCHEME) !== null) {
            return $url;
        }

        $baseParts = parse_url($base);
        if (!$baseParts) return $url;

        $scheme = $baseParts['scheme'] ?? 'http';
        $host = $baseParts['host'] ?? '';
        $port = isset($baseParts['port']) ? ':' . $baseParts['port'] : '';
        $basePath = $baseParts['path'] ?? '/';

        if (strpos($url, '/') === 0) {
            return "$scheme://$host$port$url";
        }

        $dir = rtrim(substr($basePath, 0, strrpos($basePath, '/') + 1), '/') . '/';
        return "$scheme://$host$port$dir$url";
    }

    /**
     * Fetch additional metadata from external APIs (if enabled).
     *
     * @param string $url
     * @return array API metadata results.
     */
    private function fetch_api_enhanced_data(string $url): array {
        $apiData = [];

        // Placeholder: Implement IP info, VirusTotal, BuiltWith, URLScan etc., checks based on existing API keys.

        // Example: VirusTotal
        if (get_option('hsz_virustotal_enabled', 0) && $key = Utils::decrypt_api_key(get_option('hsz_virustotal_api_key'))) {
            // Call VirusTotal API, parse and add to $apiData
            // ...
        }

        // Similarly for other APIs...

        return $apiData;
    }
}
