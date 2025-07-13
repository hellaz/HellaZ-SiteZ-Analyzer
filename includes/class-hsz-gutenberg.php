<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

class Gutenberg {
    public static function register_block() {
        if (!function_exists('register_block_type')) return;
        wp_register_script(
            'hsz-block-editor',
            HSZ_PLUGIN_URL . 'assets/js/hsz-block.js',
            ['wp-blocks', 'wp-element', 'wp-editor'],
            HSZ_PLUGIN_VERSION,
            true
        );
        wp_register_style(
            'hsz-block-editor-style',
            HSZ_PLUGIN_URL . 'assets/css/hsz-block-editor.css',
            ['wp-edit-blocks'],
            HSZ_PLUGIN_VERSION
        );
        wp_register_style(
            'hsz-block-style',
            HSZ_PLUGIN_URL . 'assets/css/hsz-block.css',
            [],
            HSZ_PLUGIN_VERSION
        );
        register_block_type('hsz/analyzer-block', [
            'editor_script' => 'hsz-block-editor',
            'editor_style'  => 'hsz-block-editor-style',
            'style'         => 'hsz-block-style',
            'render_callback' => array(__CLASS__, 'render'),
            'attributes' => [
                'url' => ['type' => 'string'],
            ],
        ]);
    }

    public static function render($attributes) {
        $url = isset($attributes['url']) ? esc_url($attributes['url']) : '';
        if (!$url) return '';
        ob_start();
        ?>
        <div class="hsz-analyzer-block">
            <strong>URL:</strong> <?php echo esc_html($url); ?>
            <!-- Render sanitized analysis results here -->
        </div>
        <?php
        return ob_get_clean();
    }
}
