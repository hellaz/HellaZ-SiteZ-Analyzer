// Metadata extraction logic
namespace HSZ;

class Metadata {
    public function __construct() {
        add_action('init', [$this, 'extract_metadata']);
    }

    public function extract_metadata($url) {
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Fetch metadata
        $html = wp_remote_get($url);
        if (is_wp_error($html)) {
            return false;
        }

        $dom = new \DOMDocument();
        @$dom->loadHTML(wp_remote_retrieve_body($html));

        // Extract standard metadata
        $metadata = [
            'title' => $this->get_tag_content($dom, 'title'),
            'description' => $this->get_meta_tag($dom, 'description'),
            'keywords' => $this->get_meta_tag($dom, 'keywords'),
            'og:title' => $this->get_meta_tag($dom, 'og:title'),
            'twitter:title' => $this->get_meta_tag($dom, 'twitter:title'),
            // Add more fields as needed
        ];

        return $metadata;
    }

    private function get_tag_content($dom, $tag) {
        $elements = $dom->getElementsByTagName($tag);
        return $elements->length > 0 ? $elements->item(0)->textContent : '';
    }

    private function get_meta_tag($dom, $name) {
        $metas = $dom->getElementsByTagName('meta');
        foreach ($metas as $meta) {
            if ($meta->getAttribute('name') === $name || $meta->getAttribute('property') === $name) {
                return $meta->getAttribute('content');
            }
        }
        return '';
    }
}
