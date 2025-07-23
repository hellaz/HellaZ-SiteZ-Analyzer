<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

class Shortcode {
    public static function register() {
        add_shortcode('hsz_analyzer', [__CLASS__, 'render']);
    }
    public static function render($atts) {
        $atts = shortcode_atts(['url' => ''], $atts);
        $url = esc_url($atts['url']);
        if (!$url) return '<p>' . esc_html__('Please provide a URL.', 'hellaz-sitez-analyzer') . '</p>';
        $title = Fallbacks::get_fallback_title(); // replace by actual logic if available
        $desc = Fallbacks::get_fallback_description();
        $favicon = Fallbacks::get_fallback_image();
        ob_start(); ?>
        <div class="hsz-analyzer-shortcode">
            <strong>URL:</strong> <?php echo esc_html($url); ?>
            <br>
            <strong><?php echo esc_html($title); ?></strong><br>
            <span><?php echo esc_html($desc); ?></span>
            <img src="<?php echo esc_url($favicon); ?>" alt="Favicon" class="hsz-favicon" />
        </div>
        <?php return ob_get_clean();
    }
}
