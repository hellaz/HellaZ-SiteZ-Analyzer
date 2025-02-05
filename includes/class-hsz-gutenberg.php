<?php
namespace HSZ;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Gutenberg {
    private static $instance = null; // Singleton pattern

    /**
     * Get the singleton instance of the class.
     *
     * @return Gutenberg
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Hook into WordPress initialization to register the block
        add_action('init', [$this, 'register_block']);
    }

    /**
     * Register the Gutenberg block.
     */
    public function register_block() {
        if (function_exists('register_block_type') && !WP_Block_Type_Registry::get_instance()->is_registered('hsz/metadata-block')) {
            error_log('Attempting to register Gutenberg block...');

            register_block_type('hsz/metadata-block', [
                'render_callback' => [$this, 'render_block'], // Ensure this points to the render_block method
                'attributes' => [
                    'url' => [
                        'type' => 'string',
                        'default' => '',
                    ],
                ],
            ]);

            error_log('Gutenberg block registered.');
        } else {
            error_log('Block "hsz/metadata-block" is already registered.');
        }
    }

    /**
     * Render the block on the frontend.
     *
     * @param array $attributes The block attributes.
     * @return string The rendered block content.
     */
    public function render_block($attributes) {
        error_log('Rendering block with attributes: ' . print_r($attributes, true));

        $url = isset($attributes['url']) ? esc_url_raw($attributes['url']) : '';
        if (empty($url)) {
            error_log('No URL provided in block attributes.');
            return '<p>' . __('Please enter a valid URL.', 'hellaz-sitez-analyzer') . '</p>';
        }

        try {
            // Extract metadata using the Metadata class
            $metadata = (new Metadata())->extract_metadata($url);

            if (isset($metadata['error'])) {
                error_log('Error extracting metadata: ' . $metadata['error']);
                return '<p>' . esc_html($metadata['error']) . '</p>';
            }

            // Start output buffering and include the template file
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

// Ensure the file is included only once
if (!function_exists('hsz_register_gutenberg_block')) {
    function hsz_register_gutenberg_block() {
        Gutenberg::get_instance();
    }
    add_action('plugins_loaded', 'hsz_register_gutenberg_block');
}
