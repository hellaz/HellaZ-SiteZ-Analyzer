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

        // Validate the URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '<p>' . __('Please provide a valid URL.', 'hellaz-sitez-analyzer') . '</p>';
        }

        // Check if the template file exists
        $template_path = HSZ_PLUGIN_PATH . 'templates/metadata-template.php';
        if (!file_exists($template_path)) {
            return '<p>' . __('Template file is missing.', 'hellaz-sitez-analyzer') . '</p>';
        }

        try {
            // Extract metadata
            $metadata = (new Metadata())->extract_metadata($url);

            // Start output buffering and include the template file
            ob_start();
            include $template_path;
            return ob_get_clean();
        } catch (\Exception $e) {
            error_log('[HellaZ SiteZ Analyzer] Failed to extract metadata for URL: ' . esc_url($url));
            return '<p>' . __('An error occurred while processing the URL.', 'hellaz-sitez-analyzer') . '</p>';
        }
    }
}
