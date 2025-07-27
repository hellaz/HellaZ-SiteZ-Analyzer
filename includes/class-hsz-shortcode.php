<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

class Shortcode {

    /**
     * Registers the shortcode on init.
     */
    public static function register() {
        add_shortcode('hsz_analyzer', [__CLASS__, 'render']);
    }

    /**
     * Shortcode render callback.
     *
     * @param array $atts Shortcode attributes, expects 'url'.
     * @return string Sanitized HTML content for the shortcode display.
     */
    public static function render($atts) {
        $atts = shortcode_atts([
            'url' => '',
        ], $atts);

        $url = trim($atts['url']);

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '<p>' . esc_html__('Please provide a valid URL.', 'hellaz-sitez-analyzer') . '</p>';
        }

        // Attempt to extract metadata and social data
        try {
            $metadata = (new Metadata())->extract_metadata($url);
            $social = (new SocialMedia())->extract_social_profiles(@file_get_contents($url), $url);

            $title = $metadata['title'] ?? Fallbacks::get_fallback_title();
            $description = $metadata['description'] ?? Fallbacks::get_fallback_description();
            $favicon = $metadata['favicon'] ?? Fallbacks::get_fallback_image();
            $disclaimer = Fallbacks::get_disclaimer();

            $template_mode = get_option('hsz_template_mode', 'classic');

            // Render output based on the chosen template mode
            switch ($template_mode) {
                case 'modern':
                    return self::render_modern_template($url, $title, $description, $favicon, $social, $disclaimer);
                case 'compact':
                    return self::render_compact_template($url, $title, $description, $favicon, $social, $disclaimer);
                case 'classic':
                default:
                    return self::render_classic_template($url, $title, $description, $favicon, $social, $disclaimer);
            }

        } catch (\Exception $e) {
            // Log errors for admin but show user-friendly message
            Utils::log_error('Shortcode metadata extraction failed: ' . $e->getMessage(), ['url' => $url]);
            return '<p>' . esc_html__('An error occurred while processing the URL.', 'hellaz-sitez-analyzer') . '</p>';
        }
    }

    /**
     * Render classic table-style template.
     */
    private static function render_classic_template($url, $title, $description, $favicon, $social, $disclaimer) {
        ob_start();
        ?>
        <table class="hsz-classic-template" style="border-collapse: collapse; width: 100%; max-width: 600px;">
            <tr>
                <td style="width: 48px; vertical-align: middle;">
                    <img src="<?php echo esc_url($favicon); ?>" alt="" style="width: 48px; height: 48px; border-radius: 6px;" loading="lazy">
                </td>
                <td style="padding-left: 12px; vertical-align: middle;">
                    <h3 style="margin: 0;"><?php echo esc_html($title); ?></h3>
                    <p style="margin: 4px 0 0 0; color: #666;"><?php echo esc_html($description); ?></p>
                    <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" style="font-size: 0.85em;"><?php echo esc_html($url); ?></a>
                </td>
            </tr>
            <?php if (!empty($social) && is_array($social)): ?>
            <tr>
                <td colspan="2" style="padding-top: 8px;">
                    <div class="hsz-social-links" style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <?php foreach ($social as $platform => $data): ?>
                            <?php if (!empty($data['url'])): ?>
                                <a href="<?php echo esc_url($data['url']); ?>" target="_blank" rel="noopener noreferrer" 
                                   class="hsz-social-link hsz-<?php echo esc_attr($platform); ?>"
                                   style="padding: 6px 10px; background: #f3f3f3; border-radius: 4px; font-size: 0.85em; text-decoration: none;">
                                    <?php echo esc_html(ucfirst($platform)); ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($disclaimer)): ?>
            <tr>
                <td colspan="2" style="padding-top: 8px; font-size: 0.75em; color: #888;">
                    <?php echo esc_html($disclaimer); ?>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a modern card template.
     */
    private static function render_modern_template($url, $title, $description, $favicon, $social, $disclaimer) {
        ob_start();
        ?>
        <div class="hsz-modern-card" style="max-width: 600px; padding: 16px; border-radius: 12px; box-shadow: 0 0 8px rgba(0,0,0,0.1); font-family: Arial, sans-serif; background:#fff;">
            <div style="display: flex; align-items: center;">
                <img src="<?php echo esc_url($favicon); ?>" alt="" style="width: 60px; height: 60px; border-radius: 8px; flex-shrink: 0;" loading="lazy" />
                <div style="margin-left: 16px; flex-grow: 1;">
                    <h2 style="margin: 0 0 6px 0;"><?php echo esc_html($title); ?></h2>
                    <p style="margin:0; color: #444;"><?php echo esc_html($description); ?></p>
                    <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" 
                       style="display: inline-block; margin-top: 8px; font-size: 0.85em; color: #0073aa;"><?php echo esc_html($url); ?></a>
                </div>
            </div>
            <?php if (!empty($social) && is_array($social)): ?>
                <div class="hsz-social-links" style="margin-top: 12px; display: flex; flex-wrap: wrap; gap: 10px;">
                    <?php foreach ($social as $platform => $data): ?>
                        <?php if (!empty($data['url'])): ?>
                            <a href="<?php echo esc_url($data['url']); ?>" target="_blank" rel="noopener noreferrer"
                               class="hsz-social-link hsz-<?php echo esc_attr($platform); ?>"
                               style="padding: 6px 12px; background: #eee; border-radius: 20px; font-size: 0.9em; text-decoration: none; color: #333;">
                                <?php echo esc_html(ucfirst($platform)); ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($disclaimer)): ?>
                <div style="margin-top: 14px; font-size: 0.75em; color: #999;">
                    <?php echo esc_html($disclaimer); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a compact minimal template.
     */
    private static function render_compact_template($url, $title, $description, $favicon, $social, $disclaimer) {
        ob_start();
        ?>
        <div class="hsz-compact-template" style="max-width: 600px; font-family: Arial, sans-serif; font-size: 14px; color: #222;">
            <h3 style="margin: 0 0 4px 0;"><?php echo esc_html($title); ?></h3>
            <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" style="font-size: 0.85em; color:#0073aa; text-decoration:none;"><?php echo esc_html($url); ?></a>
            <?php if (!empty($disclaimer)): ?>
                <p style="font-size: 0.75em; color:#999; margin-top: 6px;"><?php echo esc_html($disclaimer); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
