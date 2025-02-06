<?php
namespace HSZ;

class Settings {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_hsz_clear_cache', [$this, 'clear_cache_ajax']);
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

        // Register icon library setting
        register_setting('hsz-settings-group', 'hsz_icon_library');
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

                    <!-- Icon Library -->
                    <tr>
                        <th scope="row"><label for="hsz_icon_library"><?php _e('Icon Library', 'hellaz-sitez-analyzer'); ?></label></th>
                        <td>
                            <select id="hsz_icon_library" name="hsz_icon_library">
                                <option value="font-awesome" <?php selected(get_option('hsz_icon_library'), 'font-awesome'); ?>><?php _e('Font Awesome (CDN)', 'hellaz-sitez-analyzer'); ?></option>
                                <option value="material-icons" <?php selected(get_option('hsz_icon_library'), 'material-icons'); ?>><?php _e('Material Icons (CDN)', 'hellaz-sitez-analyzer'); ?></option>
                                <option value="bootstrap-icons" <?php selected(get_option('hsz_icon_library'), 'bootstrap-icons'); ?>><?php _e('Bootstrap Icons (CDN)', 'hellaz-sitez-analyzer'); ?></option>
                                <?php
                                // Detect locally installed icon libraries
                                $local_libraries = $this->detect_local_icon_libraries();
                                foreach ($local_libraries as $library_name => $library_path) {
                                    echo '<option value="' . esc_attr($library_path) . '" ' . selected(get_option('hsz_icon_library'), $library_path, false) . '>' . esc_html($library_name . ' (Local: ' . $library_path . ')') . '</option>';
                                }
                                ?>
                                <option value="custom-icons" <?php selected(get_option('hsz_icon_library'), 'custom-icons'); ?>><?php _e('Custom Icons', 'hellaz-sitez-analyzer'); ?></option>
                            </select>
                            <p class="description"><?php _e('Select the icon library to use for social media icons.', 'hellaz-sitez-analyzer'); ?></p>

                            <!-- Custom Icon Path Field -->
                            <div id="hsz-custom-icon-path-field" style="display: <?php echo get_option('hsz_icon_library') === 'custom-icons' ? 'block' : 'none'; ?>;">
                                <input type="text" id="hsz_custom_icon_path" name="hsz_custom_icon_path" value="<?php echo esc_attr(get_option('hsz_custom_icon_path')); ?>" class="regular-text">
                                <p class="description"><?php _e('Enter the URL or file path to your custom icon set.', 'hellaz-sitez-analyzer'); ?></p>
                            </div>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <!-- Cache Clearing Section -->
            <h2><?php _e('Clear Cache', 'hellaz-sitez-analyzer'); ?></h2>
            <p><?php _e('Click the button below to clear all cached data.', 'hellaz-sitez-analyzer'); ?></p>
            <button id="hsz-clear-cache-button" class="button button-primary"><?php _e('Clear Cache', 'hellaz-sitez-analyzer'); ?></button>
            <div id="hsz-clear-cache-message" style="display: none;"></div>

            <script>
                jQuery(document).ready(function($) {
                    $('#hsz-clear-cache-button').on('click', function(e) {
                        e.preventDefault();
                        $.ajax({
                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                            type: 'POST',
                            data: {
                                action: 'hsz_clear_cache'
                            },
                            success: function(response) {
                                $('#hsz-clear-cache-message').html('<div class="notice notice-success"><p>' + response.message + '</p></div>').show();
                            },
                            error: function() {
                                $('#hsz-clear-cache-message').html('<div class="notice notice-error"><p><?php _e('An error occurred while clearing the cache.', 'hellaz-sitez-analyzer'); ?></p></div>').show();
                            }
                        });
                    });
                });
            </script>
        </div>
        <script>
            jQuery(document).ready(function($) {
                const iconLibrarySelect = $('#hsz_icon_library');
                const customIconPathField = $('#hsz-custom-icon-path-field');

                // Toggle visibility of custom icon path field
                function toggleCustomIconPathField() {
                    if (iconLibrarySelect.val() === 'custom-icons') {
                        customIconPathField.show();
                    } else {
                        customIconPathField.hide();
                    }
                }

                // Initial toggle
                toggleCustomIconPathField();

                // Listen for changes
                iconLibrarySelect.on('change', function() {
                    toggleCustomIconPathField();
                });
            });
        </script>
        <?php
    }

    /**
     * Detect locally installed icon libraries.
     *
     * @return array An associative array of library names and their paths.
     */
    private function detect_local_icon_libraries() {
        $libraries = [];
        $theme_dir = get_template_directory();
        $plugin_dir = WP_PLUGIN_DIR;

        // Check for Font Awesome
        if (file_exists($theme_dir . '/font-awesome.min.css')) {
            $libraries['Font Awesome'] = get_template_directory_uri() . '/font-awesome.min.css';
        } elseif (file_exists($plugin_dir . '/font-awesome/font-awesome.min.css')) {
            $libraries['Font Awesome'] = plugins_url('font-awesome/font-awesome.min.css');
        }

        // Check for Dashicons
        if (wp_style_is('dashicons', 'registered')) {
            $libraries['Dashicons'] = includes_url('css/dashicons.min.css');
        }

        // Add more checks for other libraries here...

        return $libraries;
    }
    
    /**
     * Clear all transients via AJAX.
     */
    public function clear_cache_ajax() {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_hsz_%'");
        wp_send_json_success(['message' => __('Cache cleared successfully.', 'hellaz-sitez-analyzer')]);
    }
}
