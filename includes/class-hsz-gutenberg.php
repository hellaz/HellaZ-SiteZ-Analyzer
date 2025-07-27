<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

/**
 * Gutenberg block integration for HellaZ SiteZ Analyzer.
 */
class Gutenberg {
    private static $instance = null;

    /**
     * Get the singleton instance.
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor. Registers hooks for block registration and asset enqueuing.
     */
    private function __construct() {
        add_action('init', [$this, 'register_block']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_block_assets']);
    }

    /**
     * Registers the Gutenberg block and its attributes.
     */
    public function register_block() {
        if (!function_exists('register_block_type')) return;

        register_block_type('hsz/analyzer-block', [
            'editor_script'   => 'hsz-block-editor',
            'editor_style'    => 'hsz-block-editor-style',
            'style'           => 'hsz-block-style',
            'render_callback' => [$this, 'render_block'],
            'attributes'      => [
                'url'         => ['type' => 'string', 'default' => ''],
                'displayType' => ['type' => 'string', 'default' => 'full'],
            ]
        ]);
    }

    /**
     * Enqueues block editor (Gutenberg) assets and localizes AJAX/nonce for JS.
     */
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
            'nonce'    => wp_create_nonce('hsz_analyze_nonce')
        ]);
    }

    /**
     * Renders the block output on the frontend and in dynamic preview.
     *
     * @param array $attributes Block attributes
     * @return string Rendered HTML for the block
     */
    public function render_block($attributes) {
        $url = isset($attributes['url']) ? esc_url($attributes['url']) : '';
        $display_type = isset($attributes['displayType']) ? sanitize_text_field($attributes['displayType']) : 'full';

        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '<div class="hsz-error">' . __('Please provide a valid URL.', 'hellaz-sitez-analyzer') . '</div>';
        }

        // Assume Metadata, Fallbacks, and SocialMedia classes are available and loaded.
        $metadata   = class_exists('\HSZ\Metadata') ? (new \HSZ\Metadata())->extract_metadata($url) : [];
        $socialData = class_exists('\HSZ\SocialMedia') ? (new \HSZ\SocialMedia())->extract_social_profiles(@file_get_contents($url), $url) : [];

        $title = $metadata['title'] ?? (\HSZ\Fallbacks::get_fallback_title());
        $desc  = $metadata['description'] ?? (\HSZ\Fallbacks::get_fallback_description());
        $favicon = $metadata['favicon'] ?? (\HSZ\Fallbacks::get_fallback_image());
        $disclaimer = \HSZ\Fallbacks::get_disclaimer();

        ob_start();
        ?>
        <div class="hsz-analyzer-block" data-url="<?php echo esc_attr($url); ?>">
            <div class="hsz-block-content">
                <?php if ($display_type === 'full' || $display_type === 'metadata'): ?>
                    <div class="hsz-metadata">
                        <h4><?php echo esc_html($title); ?></h4>
                        <p><?php echo esc_html($desc); ?></p>
                        <img src="<?php echo esc_url($favicon); ?>" alt="Favicon" class="hsz-favicon" />
                    </div>
                <?php endif; ?>
                <?php if (($display_type === 'full' || $display_type === 'social') && is_array($socialData) && count($socialData)): ?>
                    <div class="hsz-social-media">
                        <div class="hsz-social-links">
                        <?php
                        foreach ($socialData as $platform => $data) {
                            if (!empty($data['url']) && !empty($data['username'])) {
                                printf(
                                    '<a href="%s" class="hsz-social-link hsz-%s" rel="noopener noreferrer" target="_blank">%s <span class="hsz-social-username">%s</span></a> ',
                                    esc_url($data['url']),
                                    esc_attr($platform),
                                    ucfirst($platform),
                                    esc_html($data['username'])
                                );
                            }
                        }
                        ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($disclaimer): ?>
                    <div class="hsz-disclaimer"><small><?php echo esc_html($disclaimer); ?></small></div>
                <?php endif; ?>
                <div class="hsz-block-footer">
                    <small>
                        <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($url); ?></a>
                    </small>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
