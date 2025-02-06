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

    public function register_settings() {
        // Register API key settings
        register_setting('hsz-settings-group', 'hsz_virustotal_api_key');
        register_setting('hsz-settings-group', 'hsz_urlscan_api_key');
        register_setting('hsz-settings-group', 'hsz_builtwith_api_key');

        // Register disclaimer settings
        register_setting('hsz-settings-group', 'hsz_enable_disclaimer');
        register_setting('hsz-settings-group', 'hsz_disclaimer_message');

        // Register fallback image setting with custom sanitization
        register_setting('hsz-settings-group', 'hsz_fallback_image', [
            'sanitize_callback' => function ($value) {
                return esc_url_raw($value);
            },
        ]);

        // Register link target setting
        register_setting('hsz-settings-group', 'hsz_link_target');
    }

    public function render_settings_page() {
        // Verify nonce for form submission.
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hsz_nonce'])) {
            check_admin_referer('hsz_settings_nonce', 'hsz_nonce');
        }
        ?>
        <div class="wrap">
            <h1><?php _e('HellaZ SiteZ Analyzer Settings', 'hellaz-sitez-analyzer'); ?></h1>
            <form method="post" action="options.php">
                <?php
                // Add nonce field for security.
                wp_nonce_field('hsz_settings_nonce', 'hsz_nonce');

                // Output settings fields and sections.
                settings_fields('hsz-settings-group');
                do_settings_sections('hsz-settings-group');
                ?>
                <table class="form-table">
                    <!-- VirusTotal API Key -->
                    <tr>
                        <th scope="row"><label for="hsz_virustotal_api_key"><?php _e('VirusTotal API Key', 'hellaz-sitez-analyzer'); ?></label></th>
                        <td>
                            <input type="text" id="hsz_virustotal_api_key" name="hsz_virustotal_api_key" value="<?php echo esc_attr(get_option('hsz_virustotal_api_key')); ?>" class="regular-text">
                            <p class="description"><?php _e('Enter your VirusTotal API key.', 'hellaz-sitez-analyzer'); ?></p>
                        </td>
                    </tr>
                    <!-- URLScan.io API Key -->
                    <tr>
                        <th scope="row"><label for="hsz_urlscan_api_key"><?php _e('URLScan.io API Key', 'hellaz-sitez-analyzer'); ?></label></th>
                        <td>
                            <input type="text" id="hsz_urlscan_api_key" name="hsz_urlscan_api_key" value="<?php echo esc_attr(get_option('hsz_urlscan_api_key')); ?>" class="regular-text">
                            <p class="description"><?php _e('Enter your URLScan.io API key.', 'hellaz-sitez-analyzer'); ?></p>
                        </td>
                    </tr>
                    <!-- BuiltWith API Key -->
                    <tr>
                        <th scope="row"><label for="hsz_builtwith_api_key"><?php _e('BuiltWith API Key', 'hellaz-sitez-analyzer'); ?></label></th>
                        <td>
                            <input type="text" id="hsz_builtwith_api_key" name="hsz_builtwith_api_key" value="<?php echo esc_attr(get_option('hsz_builtwith_api_key')); ?>" class="regular-text">
                            <p class="description"><?php _e('Enter your BuiltWith API key.', 'hellaz-sitez-analyzer'); ?></p>
                        </td>
                    </tr>
                    <!-- Fallback Image -->
                    <tr>
                        <th scope="row"><label for="hsz_fallback_image"><?php _e('Fallback Image URL', 'hellaz-sitez-analyzer'); ?></label></th>
                        <td>
                            <input type="text" id="hsz_fallback_image" name="hsz_fallback_image" value="<?php echo esc_attr(get_option('hsz_fallback_image')); ?>" class="regular-text">
                            <p class="description"><?php _e('Enter the URL of the fallback image to use when no favicon is found.', 'hellaz-sitez-analyzer'); ?></p>
                        </td>
                    </tr>
                    <!-- Enable Disclaimer -->
                    <tr>
                        <th scope="row"><label for="hsz_enable_disclaimer"><?php _e('Enable Disclaimer', 'hellaz-sitez-analyzer'); ?></label></th>
                        <td>
                            <input type="checkbox" id="hsz_enable_disclaimer" name="hsz_enable_disclaimer" value="1" <?php checked(get_option('hsz_enable_disclaimer'), 1); ?>>
                            <p class="description"><?php _e('Check to enable the disclaimer message.', 'hellaz-sitez-analyzer'); ?></p>
                        </td>
                    </tr>
                    <!-- Disclaimer Message -->
                    <tr>
                        <th scope="row"><label for="hsz_disclaimer_message"><?php _e('Disclaimer Message', 'hellaz-sitez-analyzer'); ?></label></th>
                        <td>
                            <textarea id="hsz_disclaimer_message" name="hsz_disclaimer_message" rows="5" class="large-text"><?php echo esc_textarea(get_option('hsz_disclaimer_message')); ?></textarea>
                            <p class="description"><?php _e('Enter the disclaimer message to display.', 'hellaz-sitez-analyzer'); ?></p>
                        </td>
                    </tr>
                    <!-- Link Target -->
                    <tr>
                        <th scope="row"><label for="hsz_link_target"><?php _e('Link Target', 'hellaz-sitez-analyzer'); ?></label></th>
                        <td>
                            <select id="hsz_link_target" name="hsz_link_target">
                                <option value="_blank" <?php selected(get_option('hsz_link_target'), '_blank'); ?>><?php _e('Open in New Window', 'hellaz-sitez-analyzer'); ?></option>
                                <option value="_top" <?php selected(get_option('hsz_link_target'), '_top'); ?>><?php _e('Open in Same Window', 'hellaz-sitez-analyzer'); ?></option>
                            </select>
                            <p class="description"><?php _e('Choose how links should open.', 'hellaz-sitez-analyzer'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
