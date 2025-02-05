<?php
namespace HSZ;

class Gutenberg {
    public function register_block() {
        error_log('Registering HSZ Gutenberg block...');
        register_block_type('hsz/metadata-block', [
            'render_callback' => [$this, 'render_block'],
            'attributes' => [
                'url' => [
                    'type' => 'string',
                    'default' => '',
                ],
            ],
        ]);
        error_log('Gutenberg block registered.');
    }

    public function render_block($attributes) {
        error_log('Rendering block with attributes: ' . print_r($attributes, true));

        $url = isset($attributes['url']) ? esc_url_raw($attributes['url']) : '';
        if (empty($url)) {
            error_log('No URL provided in block attributes.');
            return '<p>' . __('Please enter a valid URL.', 'hellaz-sitez-analyzer') . '</p>';
        }

        try {
            $metadata = (new Metadata())->extract_metadata($url);
            if (isset($metadata['error'])) {
                error_log('Error extracting metadata: ' . $metadata['error']);
                return '<p>' . esc_html($metadata['error']) . '</p>';
            }

            ob_start();
            include plugin_dir_path(__FILE__) . '../templates/metadata-template.php';
            $output = ob_get_clean();
            error_log('Block rendered successfully.');
            return $output;
        } catch (\Exception $e) {
            error_log('Error rendering block: ' . $e->getMessage());
            return '<p>' . __('An error occurred while processing the URL.', 'hellaz-sitez-analyzer') . '</p>';
        }
    }
}
