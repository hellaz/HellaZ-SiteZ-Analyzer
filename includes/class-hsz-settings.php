<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

/**
 * Handles the admin settings page, registration, and sanitization.
 */
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

    public function register_settings() {
        register_setting('hsz-settings-group', 'hsz_fallback_image', ['sanitize_callback' => 'esc_url_raw']);
        register_setting('hsz-settings-group', 'hsz_fallback_title', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('hsz-settings-group', 'hsz_fallback_description', ['sanitize_callback' => 'sanitize_textarea_field']);
        register_setting('hsz-settings-group', 'hsz_disclaimer_enabled', ['sanitize_callback' => 'rest_sanitize_boolean']);
        register_setting('hsz-settings-group', 'hsz_disclaimer_message', ['sanitize_callback' => 'sanitize_textarea_field']);
        register_setting('hsz-settings-group', 'hsz_virustotal_api_key', ['sanitize_callback' => ['HSZ\Utils', 'encrypt_api_key']]);
        register_setting('hsz-settings-group', 'hsz_builtwith_api_key', ['sanitize_callback' => ['HSZ\Utils', 'encrypt_api_key']]);
        register_setting('hsz-settings-group', 'hsz_urlscan_api_key', ['sanitize_callback' => ['HSZ\Utils', 'encrypt_api_key']]);
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('HellaZ SiteZ Analyzer Settings', 'hellaz-sitez-analyzer'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('hsz-settings-group');
                do_settings_sections('hsz-settings-group');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Fallback Image URL', 'hellaz-sitez-analyzer'); ?></th>
                        <td>
                            <input type="url" name="hsz_fallback_image" value="<?php echo esc_attr(get_option('hsz_fallback_image', '')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Used if no favicon or image is found.', 'hellaz-sitez-analyzer'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Fallback Title', 'hellaz-sitez-analyzer'); ?></th>
                        <td>
                            <input type="text" name="hsz_fallback_title" value="<?php echo esc_attr(get_option('hsz_fallback_title', __('No Title', 'hellaz-sitez-analyzer'))); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Fallback Description', 'hellaz-sitez-analyzer'); ?></th>
                        <td>
                            <textarea name="hsz_fallback_description" rows="2" cols="50"><?php echo esc_textarea(get_option('hsz_fallback_description', __('No description available.', 'hellaz-sitez-analyzer'))); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Enable Disclaimer', 'hellaz-sitez-analyzer'); ?></th>
                        <td>
                            <input type="checkbox" name="hsz_disclaimer_enabled" value="1" <?php checked(get_option('hsz_disclaimer_enabled'), 1); ?> />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Disclaimer Message', 'hellaz-sitez-analyzer'); ?></th>
                        <td>
                            <textarea name="hsz_disclaimer_message" rows="2" cols="50"><?php echo esc_textarea(get_option('hsz_disclaimer_message', __('Information is for reference only.', 'hellaz-sitez-analyzer'))); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('VirusTotal API Key', 'hellaz-sitez-analyzer'); ?></th>
                        <td>
                            <input type="password" name="hsz_virustotal_api_key" value="" class="regular-text" autocomplete="off" />
                            <p class="description"><?php _e('Leave blank to keep existing.', 'hellaz-sitez-analyzer'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('BuiltWith API Key', 'hellaz-sitez-analyzer'); ?></th>
                        <td>
                            <input type="password" name="hsz_builtwith_api_key" value="" class="regular-text" autocomplete="off" />
                            <p class="description"><?php _e('Leave blank to keep existing.', 'hellaz-sitez-analyzer'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('URLScan.io API Key', 'hellaz-sitez-analyzer'); ?></th>
                        <td>
                            <input type="password" name="hsz_urlscan_api_key" value="" class="regular-text" autocomplete="off" />
                            <p class="description"><?php _e('Leave blank to keep existing.', 'hellaz-sitez-analyzer'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
