<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

class Shortcode {
    public static function register() {
        add_shortcode('hsz_analyzer', array(__CLASS__, 'render'));
    }

    public static function render($atts) {
        $atts = shortcode_atts(['url' => ''], $atts);
        $url = esc_url($atts['url']);
        if (!$url) return '<p>' . esc_html__('Please provide a URL.', 'hellaz-sitez-analyzer') . '</p>';
        ob_start();
        ?>
        <div class="hsz-analyzer-shortcode">
            <strong>URL:</strong> <?php echo esc_html($url); ?>
            <!-- Render sanitized analysis results here -->
        </div>
        <?php
        return ob_get_clean();
    }
}
