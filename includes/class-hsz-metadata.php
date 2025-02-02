<?php
namespace HSZ;

class Metadata {
    public function extract_metadata($url) {
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return [];
        }

        // Fetch remote content
        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            return [];
        }

        // Check HTTP response code
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return [];
        }

        // Parse HTML content
        $html = wp_remote_retrieve_body($response);
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);

        // Extract standard metadata
        $metadata = [
            'title' => $this->get_tag_content($dom, 'title'),
            'description' => $this->get_meta_tag($dom, 'description'),
            'keywords' => $this->get_meta_tag($dom, 'keywords'),
            'og:title' => $this->get_meta_tag($dom, 'og:title'),
            'twitter:title' => $this->get_meta_tag($dom, 'twitter:title'),
            'canonical_url' => $this->get_canonical_url($dom),
            'favicon' => $this->get_favicon($dom, $url),
        ];

        return $metadata;
    }

    private function get_tag_content($dom, $tag) {
        $elements = $dom->getElementsByTagName($tag);
        return $elements->length > 0 ? esc_html($elements->item(0)->textContent) : '';
    }

    private function get_meta_tag($dom, $name) {
        $metas = $dom->getElementsByTagName('meta');
        foreach ($metas as $meta) {
            if ($meta->getAttribute('name') === $name || $meta->getAttribute('property') === $name) {
                return esc_html($meta->getAttribute('content'));
            }
        }
        return '';
    }

    private function get_canonical_url($dom) {
        $links = $dom->getElementsByTagName('link');
        foreach ($links as $link) {
            if ($link->getAttribute('rel') === 'canonical') {
                return esc_url($link->getAttribute('href'));
            }
        }
        return '';
    }

    private function get_favicon($dom, $base_url) {
        $icons = $dom->getElementsByTagName('link');
        foreach ($icons as $icon) {
            if (in_array(strtolower($icon->getAttribute('rel')), ['icon', 'shortcut icon'])) {
                $favicon = $icon->getAttribute('href');
                return $this->resolve_relative_url($favicon, $base_url);
            }
        }
        return apply_filters('hsz_fallback_image', '');
    }

    private function resolve_relative_url($url, $base_url) {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return esc_url($url);
        }
        $parsed_base = parse_url($base_url);
        $scheme = isset($parsed_base['scheme']) ? $parsed_base['scheme'] . '://' : '';
        $host = isset($parsed_base['host']) ? $parsed_base['host'] : '';
        $path = isset($parsed_base['path']) ? dirname($parsed_base['path']) : '';
        return esc_url(rtrim($scheme . $host . $path, '/') . '/' . ltrim($url, '/'));
    }
}
