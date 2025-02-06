<?php
namespace HSZ;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use WP_Block_Type_Registry; // Import the core WP_Block_Type_Registry class

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

    /**
     * Private constructor to enforce singleton pattern.
     */
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

        // Validate the URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            error_log('Invalid URL provided: ' . esc_html($url));
            return '<p>' . __('Please enter a valid URL.', 'hellaz-sitez-analyzer') . '</p>';
        }

        try {
            // Extract metadata using the Metadata class
            $metadata = (new Metadata())->extract_metadata($url);
            if (isset($metadata['error'])) {
                error_log('Error extracting metadata: ' . $metadata['error']);
                return '<p>' . esc_html($metadata['error']) . '</p>';
            }

            // Check if the template file exists
            $template_path = plugin_dir_path(__FILE__) . '../templates/metadata-template.php';
            if (!file_exists($template_path)) {
                error_log('Template file not found: ' . esc_html($template_path));
                return '<p>' . __('Template file is missing.', 'hellaz-sitez-analyzer') . '</p>';
            }

            // Start output buffering and include the template file
            ob_start();
            include $template_path;
            $output = ob_get_clean();

            error_log('Block rendered successfully.');
            return $output;
        } catch (\Exception $e) {
            error_log('Error rendering block: ' . $e->getMessage());
            return '<p>' . __('An error occurred while processing the URL.', 'hellaz-sitez-analyzer') . '</p>';
        }
    }
}
