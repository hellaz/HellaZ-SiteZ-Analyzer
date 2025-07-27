<?php
namespace HSZ;

use WP_Widget;

if (!defined('ABSPATH')) exit;

/**
 * SiteZ Analyzer Widget
 */
class Widget extends WP_Widget
{
    public static function register_widget()
    {
        register_widget(__CLASS__);
    }

    public function __construct()
    {
        parent::__construct(
            'hsz_widget',
            __('SiteZ Analyzer', 'hellaz-sitezalyzer'),
            ['description' => __('Displays website metadata and social profiles', 'hellaz-sitezalyzer')]
        );
    }

    public function widget($args, $instance)
    {
        echo $args['before_widget'];

        $title = !empty($instance['title']) ? apply_filters('widget_title', $instance['title']) : '';
        $url = !empty($instance['url']) ? esc_url($instance['url']) : '';

        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            echo '<p>' . __('Please provide a valid URL.', 'hellaz-sitezalyzer') . '</p>';
            echo $args['after_widget'];
            return;
        }

        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        try {
            // Use Metadata and SocialMedia classes to fetch data
            $metadata = (new Metadata())->extract_metadata($url);
            if (isset($metadata['error'])) {
                echo '<p>' . esc_html($metadata['error']) . '</p>';
                echo $args['after_widget'];
                return;
            }
            $social = (new SocialMedia())->extract_social_profiles(@file_get_contents($url), $url);

            $title = $metadata['title'] ?? Fallbacks::get('title');
            $description = $metadata['description'] ?? Fallbacks::get('description');
            $favicon = $metadata['favicon'] ?? Fallbacks::get('favicon');
            $disclaimer = Fallbacks::get('disclaimer');

            $template = get_option('hsz_template_mode', 'classic');
            $template_path = plugin_dir_path(__DIR__) . "templates/metadata-{$template}.php";

            if (file_exists($template_path)) {
                include $template_path;
            } else {
                include plugin_dir_path(__DIR__) . 'templates/metadata-classic.php';
            }
        } catch (\Throwable $e) {
            if (current_user_can('manage_options')) {
                echo '<p>' . esc_html($e->getMessage()) . '</p>';
            } else {
                echo '<p>' . esc_html__('An error occurred.', 'hellaz-sitezalyzer') . '</p>';
            }
        }

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $url = !empty($instance['url']) ? $instance['url'] : '';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'hellaz-sitezalyzer'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
             name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
             value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('url')); ?>"><?php _e('Website URL:', 'hellaz-sitezalyzer'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('url')); ?>"
             name="<?php echo esc_attr($this->get_field_name('url')); ?>" type="url"
             value="<?php echo esc_attr($url); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['url'] = (!empty($new_instance['url'])) ? esc_url_raw($new_instance['url']) : '';
        return $instance;
    }
}
