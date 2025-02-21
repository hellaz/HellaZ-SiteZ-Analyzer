<?php
namespace HSZ;

class Settings {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_hsz_clear_cache', [$this, 'clear_cache_ajax']);
    }

    /**
     * Add the settings page to the WordPress admin menu.
     */
    public function add_settings_page() {
        add_options_page(
            __('HellaZ SiteZ Analyzer Settings', 'hellaz-sitez-analyzer'),
            __('SiteZ Analyzer', 'hellaz-sitez-analyzer'),
            'manage_options',
            'hsz-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings for the plugin.
     */
    public function register_settings() {
        // Register API key settings dynamically
        $api_services = [
            'Server Information' => ['URLScan.io'],
            'Security Analysis' => ['VirusTotal'],
            'Technology Information' => ['BuiltWith'],
            'Social Info' => [],
            'Content Analysis' => [],
            'Other' => [],
        ];

        foreach ($api_services as $category => $services) {
            foreach ($services as $service) {
                register_setting('hsz-settings-group', 'hsz_' . strtolower(str_replace('.', '_', $service)) . '_api_key', [
                    'sanitize_callback' => [$this, 'encrypt_api_key'], // Encrypt API keys before saving
                ]);
            }
        }

        // Register general settings
        register_setting('hsz-settings-group', 'hsz_enable_disclaimer');
        register_setting('hsz-settings-group', 'hsz_disclaimer_message');
        register_setting('hsz-settings-group', 'hsz_fallback_image', [
            'sanitize_callback' => function ($value) {
                return esc_url_raw($value);
            },
        ]);
        register_setting('hsz-settings-group', 'hsz_link_target');
        register_setting('hsz-settings-group', 'hsz_icon_library');
        register_setting('hsz-settings-group', 'hsz_custom_icon_path', [
            'sanitize_callback' => function ($value) {
                return esc_url_raw($value);
            },
        ]);
    }

    /**
     * Render the settings page in the WordPress admin with tabs.
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('HellaZ SiteZ Analyzer Settings', 'hellaz-sitez-analyzer'); ?></h1>

            <!-- Tab Navigation -->
            <nav class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'hellaz-sitez-analyzer'); ?></a>
                <a href="#apis" class="nav-tab"><?php _e('API Keys', 'hellaz-sitez-analyzer'); ?></a>
                <a href="#about" class="nav-tab"><?php _e('About Hellaz.Team', 'hellaz-sitez-analyzer'); ?></a>
            </nav>

            <!-- General Settings Tab -->
            <div id="general" class="tab-content tab-content-active">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('hsz-settings-group');
                    do_settings_sections('hsz-settings-group');
                    ?>
                    <table class="form-table">
                        <!-- Fallback Image -->
                        <tr>
                            <th scope="row"><label for="hsz_fallback_image"><?php _e('Fallback Image URL', 'hellaz-sitez-analyzer'); ?></label></th>
                            <td>
                                <input type="text" id="hsz_fallback_image" name="hsz_fallback_image"
                                       value="<?php echo esc_attr(get_option('hsz_fallback_image')); ?>" class="regular-text">
                                <p class="description"><?php _e('Enter the URL of the fallback image to use when no favicon is found.', 'hellaz-sitez-analyzer'); ?></p>
                            </td>
                        </tr>

                        <!-- Enable Disclaimer -->
                        <tr>
                            <th scope="row"><label for="hsz_enable_disclaimer"><?php _e('Enable Disclaimer', 'hellaz-sitez-analyzer'); ?></label></th>
                            <td>
                                <input type="checkbox" id="hsz_enable_disclaimer" name="hsz_enable_disclaimer" value="1"
                                       <?php checked(get_option('hsz_enable_disclaimer'), 1); ?>>
                                <p class="description"><?php _e('Check to enable the disclaimer message.', 'hellaz-sitez-analyzer'); ?></p>
                            </td>
                        </tr>

                        <!-- Disclaimer Message -->
                        <tr>
                            <th scope="row"><label for="hsz_disclaimer_message"><?php _e('Disclaimer Message', 'hellaz-sitez-analyzer'); ?></label></th>
                            <td>
                                <textarea id="hsz_disclaimer_message" name="hsz_disclaimer_message" rows="5" class="large-text">
                                    <?php echo esc_textarea(get_option('hsz_disclaimer_message')); ?>
                                </textarea>
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
                                    <option value="custom-icons" <?php selected(get_option('hsz_icon_library'), 'custom-icons'); ?>><?php _e('Custom Icons', 'hellaz-sitez-analyzer'); ?></option>
                                </select>
                                <p class="description"><?php _e('Select the icon library to use for social media icons.', 'hellaz-sitez-analyzer'); ?></p>

                                <!-- Custom Icon Path Field -->
                                <div id="hsz-custom-icon-path-field" style="display: <?php echo get_option('hsz_icon_library') === 'custom-icons' ? 'block' : 'none'; ?>;">
                                    <input type="text" id="hsz_custom_icon_path" name="hsz_custom_icon_path"
                                           value="<?php echo esc_attr(get_option('hsz_custom_icon_path')); ?>" class="regular-text">
                                    <p class="description"><?php _e('Enter the URL or file path to your custom icon set.', 'hellaz-sitez-analyzer'); ?></p>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>

            <!-- API Keys Tab -->
            <div id="apis" class="tab-content">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('hsz-settings-group');
                    do_settings_sections('hsz-settings-group');
                    ?>
                    <table class="form-table">
                        <!-- Server Information -->
                        <tr>
                            <th scope="row"><strong><?php _e('Server Information', 'hellaz-sitez-analyzer'); ?></strong></th>
                            <td></td>
                        </tr>
                        <?php
                        $server_info_apis = ['URLScan.io'];
                        foreach ($server_info_apis as $service) :
                            $option_name = 'hsz_' . strtolower(str_replace('.', '_', $service)) . '_api_key';
                            ?>
                            <tr>
                                <th scope="row">
                                    <label for="<?php echo esc_attr($option_name); ?>">
                                        <?php echo sprintf(__('%s API Key', 'hellaz-sitez-analyzer'), $service); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="text" id="<?php echo esc_attr($option_name); ?>" name="<?php echo esc_attr($option_name); ?>"
                                           value="<?php echo esc_attr($this->decrypt_api_key(get_option($option_name))); ?>" class="regular-text">
                                    <p class="description">
                                        <?php echo sprintf(__('Enter your %s API key.', 'hellaz-sitez-analyzer'), $service); ?>
                                    </p>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <!-- Security Analysis -->
                        <tr>
                            <th scope="row"><strong><?php _e('Security Analysis', 'hellaz-sitez-analyzer'); ?></strong></th>
                            <td></td>
                        </tr>
                        <?php
                        $security_analysis_apis = ['VirusTotal'];
                        foreach ($security_analysis_apis as $service) :
                            $option_name = 'hsz_' . strtolower(str_replace('.', '_', $service)) . '_api_key';
                            ?>
                            <tr>
                                <th scope="row">
                                    <label for="<?php echo esc_attr($option_name); ?>">
                                        <?php echo sprintf(__('%s API Key', 'hellaz-sitez-analyzer'), $service); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="text" id="<?php echo esc_attr($option_name); ?>" name="<?php echo esc_attr($option_name); ?>"
                                           value="<?php echo esc_attr($this->decrypt_api_key(get_option($option_name))); ?>" class="regular-text">
                                    <p class="description">
                                        <?php echo sprintf(__('Enter your %s API key.', 'hellaz-sitez-analyzer'), $service); ?>
                                    </p>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <!-- Technology Information -->
                        <tr>
                            <th scope="row"><strong><?php _e('Technology Information', 'hellaz-sitez-analyzer'); ?></strong></th>
                            <td></td>
                        </tr>
                        <?php
                        $technology_info_apis = ['BuiltWith'];
                        foreach ($technology_info_apis as $service) :
                            $option_name = 'hsz_' . strtolower(str_replace('.', '_', $service)) . '_api_key';
                            ?>
                            <tr>
                                <th scope="row">
                                    <label for="<?php echo esc_attr($option_name); ?>">
                                        <?php echo sprintf(__('%s API Key', 'hellaz-sitez-analyzer'), $service); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="text" id="<?php echo esc_attr($option_name); ?>" name="<?php echo esc_attr($option_name); ?>"
                                           value="<?php echo esc_attr($this->decrypt_api_key(get_option($option_name))); ?>" class="regular-text">
                                    <p class="description">
                                        <?php echo sprintf(__('Enter your %s API key.', 'hellaz-sitez-analyzer'), $service); ?>
                                    </p>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <!-- Social Info -->
                        <tr>
                            <th scope="row"><strong><?php _e('Social Info', 'hellaz-sitez-analyzer'); ?></strong></th>
                            <td></td>
                        </tr>

                        <!-- Content Analysis -->
                        <tr>
                            <th scope="row"><strong><?php _e('Content Analysis', 'hellaz-sitez-analyzer'); ?></strong></th>
                            <td></td>
                        </tr>

                        <!-- Other -->
                        <tr>
                            <th scope="row"><strong><?php _e('Other', 'hellaz-sitez-analyzer'); ?></strong></th>
                            <td></td>
                        </tr>

                        <!-- Clear Cache Section -->
                        <tr>
                            <th scope="row"><strong><?php _e('Clear Cache', 'hellaz-sitez-analyzer'); ?></strong></th>
                            <td>
                                <button id="hsz-clear-cache-button" class="button button-primary"><?php _e('Clear Cache', 'hellaz-sitez-analyzer'); ?></button>
                                <div id="hsz-clear-cache-message" style="display: none;"></div>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>

            <!-- About Hellaz.Team Tab -->
            <div id="about" class="tab-content">
                <div class="about-section">
                    <h2><?php _e('About Hellaz.Team', 'hellaz-sitez-analyzer'); ?></h2>
                    <p><?php _e('HellaZ.SiteZ.Analyzer is a powerful WordPress plugin designed to analyze websites for metadata, security, technology stack, and more.', 'hellaz-sitez-analyzer'); ?></p>
                    <p><?php _e('For more information, visit our website:', 'hellaz-sitez-analyzer'); ?> <a href="https://hellaz.team" target="_blank">https://hellaz.team</a></p>
                    <p><?php _e('Follow us on social media:', 'hellaz-sitez-analyzer'); ?></p>
                    <ul>
                        <li><a href="https://twitter.com/hellazteam" target="_blank">Twitter</a></li>
                        <li><a href="https://github.com/hellaz" target="_blank">GitHub</a></li>
                    </ul>
                </div>
            </div>

            <!-- JavaScript for Tabs -->
            <script>
                jQuery(document).ready(function($) {
                    const tabs = $('.nav-tab');
                    const tabContents = $('.tab-content');

                    // Handle tab switching
                    tabs.on('click', function(e) {
                        e.preventDefault();

                        const targetTab = $(this).attr('href').substring(1);

                        tabs.removeClass('nav-tab-active');
                        tabContents.removeClass('tab-content-active');

                        $(this).addClass('nav-tab-active');
                        $('#' + targetTab).addClass('tab-content-active');
                    });

                    // Handle cache clearing via AJAX
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
        <?php
    }

    /**
     * Encrypt an API key before storing it in the database.
     *
     * @param string $api_key The API key to encrypt.
     * @return string The encrypted API key.
     */
    public function encrypt_api_key($api_key) {
        if (empty($api_key)) {
            return '';
        }
        $method = 'AES-256-CBC';
        $key = hash('sha256', AUTH_KEY); // Use WordPress AUTH_KEY for encryption
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
        $encrypted = openssl_encrypt($api_key, $method, $key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    /**
     * Decrypt an API key stored in the database.
     *
     * @param string $encrypted_api_key The encrypted API key.
     * @return string The decrypted API key.
     */
    public function decrypt_api_key($encrypted_api_key) {
        if (empty($encrypted_api_key)) {
            return '';
        }
        $method = 'AES-256-CBC';
        $key = hash('sha256', AUTH_KEY); // Use WordPress AUTH_KEY for decryption
        list($encrypted_data, $iv) = explode('::', base64_decode($encrypted_api_key), 2);
        return openssl_decrypt($encrypted_data, $method, $key, 0, $iv);
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
