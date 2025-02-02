<?php
namespace HSZ;

class Gutenberg {
    public function __construct() {
        add_action('init', [$this, 'register_block']);
    }

    public function register_block() {
        // Register block script
        wp_register_script(
            'hsz-gutenberg-block',
            HSZ_PLUGIN_URL . 'assets/js/scripts.js',
            ['wp-blocks', 'wp-element', 'wp-editor'],
            '1.0.0',
            true
        );

        // Register the block
        register_block_type('hsz/metadata-block', [
            'attributes' => [
                'url' => [
                    'type' => 'string',
                    'default' => '',
                ],
            ],
            'editor_script' => 'hsz-gutenberg-block',
            'render_callback' => [$this, 'render_block'],
        ]);
    }

    public function register_block() {
        error_log('Registering Gutenberg block...'); // Debugging

        // Register block script
        wp_register_script(
            'hsz-gutenberg-block',
            HSZ_PLUGIN_URL . 'assets/js/scripts.js', // Ensure this path is correct
            ['wp-blocks', 'wp-element', 'wp-editor'],
            '1.0.0',
            true
        );
    
        // Register the block
        register_block_type('hsz/metadata-block', [
            'attributes' => [
                'url' => [
                    'type' => 'string',
                    'default' => '',
                ],
            ],
            'editor_script' => 'hsz-gutenberg-block',
            'render_callback' => [$this, 'render_block'],
        ]);

        error_log('Gutenberg block registered.'); // Debugging
    }
}
