<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

class Gutenberg {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'register_block']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_block_assets']);
    }

    public function register_block() {
        if (!function_exists('register_block_type')) return;
        register_block_type('hsz/analyzer-block', [
            'editor_script' => 'hsz-block-editor',
            'editor_style'  => 'hsz-block-editor-style',
            'style'         => 'hsz-block-style',
            'render_callback' => [$this, 'render_block'],
            'attributes' => [
                'url' => ['type' => 'string', 'default' => ''],
                'displayType' => ['type' => 'string', 'default' => 'full']
            ]
        ]);
    }

    public function enqueue_block_assets() {
        wp_enqueue_script(
            'hsz-block-editor',
            HSZ_PLUGIN_URL . 'assets/js/hsz-block.js',
            ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components'],
            HSZ_PLUGIN_VERSION,
            true
        );
        wp_enqueue_style(
            'hsz-block-editor-style',
            HSZ_PLUGIN_URL . 'assets/css/hsz-block-editor.css',
            ['wp-edit-blocks'],
            HSZ_PLUGIN_VERSION
        );
        wp_enqueue_style(
            'hsz-block-style',
            HSZ_PLUGIN_URL . 'assets/css/hsz-block.css',
            [],
            HSZ_PLUGIN_VERSION
        );
        wp_localize_script('hsz-block-editor', 'hsz_block_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hsz_analyze_nonce')
        ]);
    }

    public function render_block($attributes) {
        $url = isset($attributes['url']) ? esc_url($attributes['url']) : '';
        $display_type = isset($attributes['displayType']) ? sanitize_text_field($attributes['displayType']) : 'full';

        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '<div class="hsz-error">' . __('Please provide a valid URL.', 'hellaz-sitez-analyzer') . '</div>';
        }

        $metadata = (new Metadata())->extract_metadata($url);

        if (isset($metadata['error'])) {
            return '<div class="hsz-error">' . esc_html($metadata['error']) . '</div>';
        }

        ob_start();
        ?>
        <div class="hsz-analyzer-block" data-url="<?php echo esc_attr($url); ?>">
            <div class="hsz-block-content">
                <?php if ($display_type === 'full' || $display_type === 'metadata'): ?>
                    <div class="hsz-metadata">
                        <h4><?php echo esc_html($metadata['title'] ?? Fallbacks::get_fallback_title()); ?></h4>
                        <?php if (!empty($metadata['description'])): ?>
                            <p><?php echo esc_html($metadata['description']); ?></p>
                        <?php else: ?>
                            <p><?php echo esc_html(Fallbacks::get_fallback_description()); ?></p>
                        <?php endif; ?>
                        <img src="<?php echo esc_url($metadata['favicon'] ?? Fallbacks::get_fallback_image()); ?>" alt="Favicon" class="hsz-favicon" />
                    </div>
                <?php endif; ?>
                <?php if ($display_type === 'full' || $display_type === 'social'): ?>
                    <div class="hsz-social-media">
                        <?php if (!empty($metadata['social_media'])): ?>
                            <div class="hsz-social-links">
                                <?php foreach ($metadata['social_media'] as $platform => $link): ?>
                                    <a href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener noreferrer" class="hsz-social-link hsz-<?php echo esc_attr($platform); ?>">
                                        <?php echo esc_html(ucfirst($platform)); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php $disclaimer = Fallbacks::get_disclaimer(); if ($disclaimer): ?>
                    <div class="hsz-disclaimer"><small><?php echo esc_html($disclaimer); ?></small></div>
                <?php endif; ?>
                <div class="hsz-block-footer">
                    <small><a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($url); ?></a></small>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
