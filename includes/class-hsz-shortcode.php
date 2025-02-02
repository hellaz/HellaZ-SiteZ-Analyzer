<?php
namespace HSZ;

class Shortcode {
    public function __construct() {
        add_shortcode('hsz_metadata', [$this, 'render_shortcode']);
    }

    public function render_shortcode($atts) {
        $atts = shortcode_atts([
            'url' => '',
        ], $atts);

        $url = esc_url($atts['url']);
        if (empty($url)) {
            return '<p>' . __('Please provide a valid URL.', 'hellaz-sitez-analyzer') . '</p>';
        }

        // Extract metadata
        $metadata = (new Metadata())->extract_metadata($url);

        ob_start();
        include HSZ_PLUGIN_PATH . 'templates/metadata-template.php';
        return ob_get_clean();
    }
}
