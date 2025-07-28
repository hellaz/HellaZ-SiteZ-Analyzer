<?php
namespace HSZ;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode handler for [hsz_analyzer]
 */
class Shortcode
{
    /**
     * Register shortcode with WordPress.
     */
    public static function register()
    {
        add_shortcode('hsz_analyzer', [__CLASS__, 'render']);
    }

    /**
     * Shortcode render callback.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public static function render($atts)
    {
        $atts = shortcode_atts([
            'url'         => '',
            'displayType' => 'full',
        ], $atts);

        $url = trim($atts['url']);
        $displayType = isset($atts['displayType']) ? sanitize_text_field($atts['displayType']) : 'full';

        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '<p>' . esc_html__('Please provide a valid URL.', 'hellaz-sitez-analyzer') . '</p>';
        }

        try {
            $metadata = (new Metadata())->extract_metadata($url);

            // FIXED: Ensure that extract_metadata ALWAYS returns an array:
            if (!is_array($metadata)) {
                return '<p>' . esc_html__('Failed to retrieve metadata.', 'hellaz-sitez-analyzer') . '</p>';
            }

            if (isset($metadata['error'])) {
                return '<p class="hsz-error">' . esc_html($metadata['error']) . '</p>';
            }

            // Use WordPress HTTP API for fetching HTML safely
            $response = wp_remote_get($url, [
                'timeout'    => 12,
                'user-agent' => 'Mozilla/5.0 (compatible; SiteZ Analyzer Bot)',
            ]);

            $html = (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200)
                ? wp_remote_retrieve_body($response)
                : '';

            $social = (new SocialMedia())->extract_social_profiles($html, $url);

            // Initialize ALL variables used in the rendering templates, with fallbacks
            $title       = $metadata['title'] ?? Fallbacks::get_fallback_title();
            $description = $metadata['description'] ?? Fallbacks::get_fallback_description();
            $favicon     = $metadata['favicon'] ?? Fallbacks::get_fallback_image();
            $disclaimer  = Fallbacks::get_disclaimer();

            // Select template mode from settings
            $template = get_option('hsz_template_mode', 'classic');

            $template_path = plugin_dir_path(__DIR__) . "templates/metadata-{$template}.php";

            // Variables in scope for templates:
            // $url, $title, $description, $favicon, $social, $disclaimer, $displayType

            if (file_exists($template_path)) {
                ob_start();
                include $template_path;
                return ob_get_clean();
            }

            // Fallback to minimal error-free output
            return '<div>' . esc_html($title) . ' - ' . esc_html($url) . '</div>';
        } catch (\Throwable $e) {
            Utils::log_error('Shortcode error: ' . $e->getMessage(), ['url' => $url]);

            if (current_user_can('manage_options')) {
                return '<p class="hsz-error">' . esc_html($e->getMessage()) . '</p>';
            }

            return '<p class="hsz-error">' . esc_html__('An error occurred while processing the URL.', 'hellaz-sitez-analyzer') . '</p>';
        }
    }
}
