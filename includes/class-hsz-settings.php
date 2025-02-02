<?php
namespace HSZ;

class Settings {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_settings_page() {
        add_options_page(
            __('HellaZ SiteZ Analyzer Settings', 'hellaz-sitez-analyzer'),
            'SiteZ Analyzer',
            'manage_options',
            'hsz-settings',
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('HellaZ SiteZ Analyzer Settings', 'hellaz-sitez-analyzer'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('hsz_settings_group');
                do_settings_sections('hsz-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        add_settings_section(
            'hsz_general_settings',
            __('General Settings', 'hellaz-sitez-analyzer'),
            [$this, 'render_general_settings_description'],
            'hsz-settings'
        );
    
        // Fallback Image
        add_settings_field(
            'hsz_fallback_image',
            __('Fallback Image', 'hellaz-sitez-analyzer'),
            [$this, 'render_fallback_image_field'],
            'hsz-settings',
            'hsz_general_settings'
        );
        register_setting('hsz-settings', 'hsz_fallback_image');
    
        // API Key
        add_settings_field(
            'hsz_api_key',
            __('API Key', 'hellaz-sitez-analyzer'),
            [$this, 'render_api_key_field'],
            'hsz-settings',
            'hsz_general_settings'
        );
        register_setting('hsz-settings', 'hsz_api_key');

        // Disclaimer
        register_setting('hsz_settings_group', 'hsz_enable_disclaimer');
        add_settings_field('hsz_enable_disclaimer', __('Enable Disclaimer Label', 'hellaz-sitez-analyzer'), [$this, 'render_disclaimer_field'], 'hsz-settings', 'hsz_main_section');
    }
    
    public function render_fallback_image_field() {
        $fallback_image = get_option('hsz_fallback_image', '');
        echo '<input type="text" name="hsz_fallback_image" value="' . esc_attr($fallback_image) . '" class="regular-text">';
    }
    
    public function render_api_key_field() {
        $api_key = get_option('hsz_api_key', '');
        echo '<input type="text" name="hsz_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
    }    


    public function render_disclaimer_field() {
        $value = get_option('hsz_enable_disclaimer', 'yes');
        echo '<input type="checkbox" name="hsz_enable_disclaimer" value="yes" ' . checked($value, 'yes', false) . ' />';
    }
}
