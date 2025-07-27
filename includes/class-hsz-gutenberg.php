<?php
namespace HSZ;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Gutenberg
 *
 * Handles the Gutenberg block registration, asset enqueue,
 * and block rendering for SiteZ Analyzer.
 */
class Gutenberg
{
    private static $instance = null;

    /**
     * Singleton accessor.
     *
     * @return self
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to initialize hooks.
     */
    private function __construct()
    {
        add_action('init', [$this, 'register_block']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_block_assets']);
    }

    /**
     * Registers the Gutenberg block and its attributes.
     */
    public function register_block()
    {
        if (!function_exists('register_block_type')) {
            return;
        }

        register_block_type('hsz/analyzer', [
            'editor_script'   => 'hsz-block-editor',
            'editor_style'    => 'hsz-block-editor-style',
            'style'           => 'hsz-block-style',
            'render_callback' => [$this, 'render_block'],
            'attributes'      => [
                'url' => [
                    'type'    => 'string',
                    'default' => '',
                ],
                'displayType' => [
                    'type'    => 'string',
                    'default' => 'full',
                ],
            ],
        ]);
    }

    /**
     * Enqueues block editor scripts and styles, and localizes nonce.
     */
    public function enqueue_block_assets()
    {
        wp_enqueue_script(
            'hsz-block-editor',
            HSZ_PLUGIN_URL . 'assets/js/hsz-block.js',
            [
                'wp-blocks',
                'wp-element',
                'wp-editor',
                'wp-components',
                'wp-i18n'
            ],
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
            'nonce'    => wp_create_nonce('hsz_analyze_nonce'),
        ]);
    }

    /**
     * Renders the block content on frontend and editor preview.
     *
     * @param array $attributes Block attributes.
     *
     * @return string Rendered HTML.
     */
    public function render_block($attributes)
    {
        $url         = isset($attributes['url']) ? esc_url_raw($attributes['url']) : '';
        $displayType = isset($attributes['displayType']) ? sanitize_text_field($attributes['displayType']) : 'full';

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '<div class="hsz-error">' . esc_html__('Please provide a valid URL.', 'hellaz-sitezaner') . '</div>';
        }

        try {
            /** @var Metadata $metadata_obj */
            $metadata_obj = new Metadata();
            $metadata     = $metadata_obj->extract_metadata($url);

            if (isset($metadata['error'])) {
                return '<div class="hsz-error">' . esc_html($metadata['error']) . '</div>';
            }

            /** @var SocialMedia $social_obj */
            $social_obj = new SocialMedia();
            $social     = $social_obj->extract_social_profiles(@file_get_contents($url), $url);

            $title      = $metadata['title'] ?? Fallbacks::get_default_title();
            $description= $metadata['description'] ?? Fallbacks::get_default_description();
            $favicon    = $metadata['favicon'] ?? Fallbacks::get_default_favicon();
            $disclaimer = Fallbacks::get_disclaimer();

            $template = get_option('hsz_template_mode', 'classic');

            // Render based on template mode for consistency with shortcode and widget
            switch ($template) {
                case 'modern':
                    return $this->render_modern_template($url, $title, $description, $favicon, $social, $disclaimer, $displayType);
                case 'compact':
                    return $this->render_compact_template($url, $title, $description, $favicon, $social, $disclaimer, $displayType);
                case 'classic':
                default:
                    return $this->render_classic_template($url, $title, $description, $favicon, $social, $disclaimer, $displayType);
            }
        } catch (\Exception $ex) {
            Utils::log_error('Gutenberg block rendering failed: ' . $ex->getMessage(), ['url' => $url]);
            return '<div class="hsz-error">' . esc_html__('An error occurred while extracting metadata.', 'hellaz-sitezaner') . '</div>';
        }
    }

    /**
     * Render classic style output template.
     */
    private function render_classic_template($url, $title, $description, $favicon, $social, $disclaimer, $displayType)
    {
        ob_start();
        ?>
        <table class="hsz-classic-template" style="width:100%; max-width:600px; border-collapse:collapse;">
            <tr>
                <td style="width:50px; vertical-align:middle;">
                    <img src="<?php echo esc_url($favicon); ?>" alt="<?php echo esc_attr($title); ?>" style="width:48px; height:48px; border-radius:6px;">
                </td>
                <td style="padding-left:12px; vertical-align:middle;">
                    <h3 style="margin:0;"><?php echo esc_html($title); ?></h3>
                    <p style="margin:4px 0 0; color:#666;"><?php echo esc_html($description); ?></p>
                    <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" style="font-size:0.9em;">
                        <?php echo esc_html($url); ?>
                    </a>
                </td>
            </tr>
            <?php if (in_array($displayType, ['full','social']) && !empty($social)): ?>
            <tr>
                <td colspan="2" style="padding-top:12px;">
                    <div class="hsz-social-links" style="display:flex; flex-wrap:wrap; gap:8px;">
                        <?php foreach ($social as $platform => $data): ?>
                            <?php if (!empty($data['url'])): ?>
                                <a href="<?php echo esc_url($data['url']); ?>" class="hsz-social-link hsz-<?php echo esc_attr($platform); ?>" target="_blank" rel="noopener noreferrer" style="padding:6px 10px; background:#f0f0f0; border-radius:4px; text-decoration:none;">
                                    <?php echo esc_html(ucfirst($platform)); ?><?php echo isset($data['username']) ? ': ' . esc_html($data['username']) : ''; ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($disclaimer)): ?>
            <tr>
                <td colspan="2" style="padding-top:12px;font-size:0.8em;color:#999;">
                    <?php echo esc_html($disclaimer); ?>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        <?php
        return ob_get_clean();
    }

    /**
     * Render modern style output template.
     */
    private function render_modern_template($url, $title, $description, $favicon, $social, $disclaimer, $displayType)
    {
        ob_start();
        ?>
        <div class="hsz-modern-card" style="max-width:600px; background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.1); padding:20px; font-family:Arial,sans-serif;">
            <div style="display:flex; align-items:center;">
                <img src="<?php echo esc_url($favicon); ?>" alt="<?php echo esc_attr($title); ?>" style="width:60px; height:60px; border-radius:8px; margin-right:15px;">
                <div>
                    <h2 style="margin:0 0 5px;"><?php echo esc_html($title); ?></h2>
                    <p style="margin:0 0 8px; color:#555;"><?php echo esc_html($description); ?></p>
                    <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" style="color:#0073aa; font-size:0.9em; text-decoration:none;"><?php echo esc_html($url); ?></a>
                </div>
            </div>
            <?php if (in_array($displayType, ['full','social']) && !empty($social)): ?>
                <div class="hsz-social-links" style="margin-top:15px; display:flex; flex-wrap:wrap; gap:10px;">
                    <?php foreach ($social as $platform => $data): ?>
                        <?php if (!empty($data['url'])): ?>
                            <a href="<?php echo esc_url($data['url']); ?>" class="hsz-social-link hsz-<?php echo esc_attr($platform); ?>" target="_blank" rel="noopener noreferrer" style="padding:8px 14px; background:#eee; border-radius:20px; color:#444; font-size:0.9em; text-decoration:none;">
                                <?php echo esc_html(ucfirst($platform)); ?><?php echo isset($data['username']) ? ': ' . esc_html($data['username']) : ''; ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($disclaimer)): ?>
            <div style="margin-top:18px; font-size:0.8em; color:#888;">
                <?php echo esc_html($disclaimer); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render compact minimal output template.
     */
    private function render_compact_template($url, $title, $description, $favicon, $social, $disclaimer, $displayType)
    {
        ob_start();
        ?>
        <div class="hsz-compact" style="max-width:600px; font-family:Arial,sans-serif; font-size:14px; color:#222;">
            <h3 style="margin:0 0 4px;"><?php echo esc_html($title); ?></h3>
            <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" style="font-size:0.9em; color:#0073aa;"><?php echo esc_html($url); ?></a>
            <?php if (!empty($disclaimer)): ?>
            <p style="margin-top:6px; font-size:0.8em; color:#999;"><?php echo esc_html($disclaimer); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
