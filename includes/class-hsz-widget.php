<?php
namespace HSZ;

class Widget extends \WP_Widget {
    public function __construct() {
        parent::__construct(
            'hsz_widget',
            __('HellaZ SiteZ Analyzer', 'hellaz-sitez-analyzer'),
            ['description' => __('Displays metadata for a given URL.', 'hellaz-sitez-analyzer')]
        );
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];

        $title = !empty($instance['title']) ? apply_filters('widget_title', $instance['title']) : '';
        $url = !empty($instance['url']) ? esc_url($instance['url']) : '';

        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        if (empty($url)) {
            echo '<p>' . __('Please provide a valid URL.', 'hellaz-sitez-analyzer') . '</p>';
        } else {
            $metadata = (new Metadata())->extract_metadata($url);
            ob_start();
            include HSZ_PLUGIN_PATH . 'templates/metadata-template.php';
            echo ob_get_clean();
        }

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $url = !empty($instance['url']) ? $instance['url'] : '';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'hellaz-sitez-analyzer'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('url')); ?>"><?php _e('Website URL:', 'hellaz-sitez-analyzer'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('url')); ?>" name="<?php echo esc_attr($this->get_field_name('url')); ?>" type="text" value="<?php echo esc_attr($url); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        $instance['url'] = !empty($new_instance['url']) ? esc_url_raw($new_instance['url']) : '';
        return $instance;
    }
}
