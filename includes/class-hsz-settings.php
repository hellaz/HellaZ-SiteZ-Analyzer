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
            __('SiteZ Analyzer', 'hellaz-sitez-analyzer'),
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
                settings_fields('hsz-settings');
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

        // Disclaimer Enable/Disable
        add_settings_field(
            'hsz_enable_disclaimer',
            __('Enable Disclaimer', 'hellaz-sitez-analyzer'),
            [$this, 'render_enable_disclaimer_field'],
            'hsz-settings',
            'hsz_general_settings'
        );
        register_setting('hsz-settings', 'hsz_enable_disclaimer', [
            'type' => 'boolean',
            'default' => false,
        ]);

        // Disclaimer Message
        add_settings_field(
            'hsz_disclaimer_message',
            __('Disclaimer Message', 'hellaz-sitez-analyzer'),
            [$this, 'render_disclaimer_message_field'],
            'hsz-settings',
            'hsz_general_settings'
        );
        register_setting('hsz-settings', 'hsz_disclaimer_message');

        // External API Settings
        add_settings_section(
            'hsz_api_settings',
            __('External API Settings', 'hellaz-sitez-analyzer'),
            [$this, 'render_api_settings_description'],
            'hsz-settings'
        );

        // VirusTotal API Key
        add_settings_field(
            'hsz_virustotal_api_key',
            __('VirusTotal API Key', 'hellaz-sitez-analyzer'),
            [$this, 'render_virustotal_api_key_field'],
            'hsz-settings',
            'hsz_api_settings'
        );
        register_setting('hsz-settings', 'hsz_virustotal_api_key');

        // URLScan.io API Key
        add_settings_field(
            'hsz_urlscan_api_key',
            __('URLScan.io API Key', 'hellaz-sitez-analyzer'),
            [$this, 'render_urlscan_api_key_field'],
            'hsz-settings',
            'hsz_api_settings'
        );
        register_setting('hsz-settings', 'hsz_urlscan_api_key');

        // BuiltWith API Key
        add_settings_field(
            'hsz_builtwith_api_key',
            __('BuiltWith API Key', 'hellaz-sitez-analyzer'),
            [$this, 'render_builtwith_api_key_field'],
            'hsz-settings',
            'hsz_api_settings'
        );
        register_setting('hsz-settings', 'hsz_builtwith_api_key');
    }

    public function render_general_settings_description() {
        echo '<p>' . __('Configure general settings for the HellaZ SiteZ Analyzer plugin.', 'hellaz-sitez-analyzer') . '</p>';
    }

    public function render_fallback_image_field() {
        $fallback_image = get_option('hsz_fallback_image', '');
        echo '<input type="text" name="hsz_fallback_image" value="' . esc_attr($fallback_image) . '" class="regular-text">';
    }

    public function render_enable_disclaimer_field() {
        $enable_disclaimer = get_option('hsz_enable_disclaimer', false);
        echo '<input type="checkbox" name="hsz_enable_disclaimer" value="1"' . checked(1, $enable_disclaimer, false) . '>';
    }

    public function render_disclaimer_message_field() {
        $disclaimer_message = get_option('hsz_disclaimer_message', __('This is a default disclaimer message.', 'hellaz-sitez-analyzer'));
        echo '<textarea name="hsz_disclaimer_message" rows="5" class="large-text">' . esc_textarea($disclaimer_message) . '</textarea>';
    }

    public function render_api_settings_description() {
        echo '<p>' . __('Configure API keys for external services. Leave blank if you do not wish to use these services.', 'hellaz-sitez-analyzer') . '</p>';
    }

    public function render_virustotal_api_key_field() {
        $api_key = get_option('hsz_virustotal_api_key', '');
        echo '<input type="text" name="hsz_virustotal_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
        echo '<p class="description">' . __('Get your API key from <a href="https://www.virustotal.com/" target="_blank">VirusTotal</a>.', 'hellaz-sitez-analyzer') . '</p>';
    }

    public function render_urlscan_api_key_field() {
        $api_key = get_option('hsz_urlscan_api_key', '');
        echo '<input type="text" name="hsz_urlscan_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
        echo '<p class="description">' . __('Get your API key from <a href="https://urlscan.io/" target="_blank">URLScan.io</a>.', 'hellaz-sitez-analyzer') . '</p>';
    }

    public function render_builtwith_api_key_field() {
        $api_key = get_option('hsz_builtwith_api_key', '');
        echo '<input type="text" name="hsz_builtwith_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
        echo '<p class="description">' . __('Get your API key from <a href="https://builtwith.com/" target="_blank">BuiltWith</a>.', 'hellaz-sitez-analyzer') . '</p>';
    }
}
