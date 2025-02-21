<?php
namespace HSZ;

class Settings {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_notices', [$this, 'display_notices']);
        add_action('admin_post_hsz_clear_cache', [$this, 'handle_clear_cache']);
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
     * Register settings and fields for the plugin.
     */
    public function register_settings() {
        // General Settings
        register_setting('hsz-settings-group', 'hsz_fallback_image', ['sanitize_callback' => 'esc_url_raw']);
        register_setting('hsz-settings-group', 'hsz_enable_disclaimer', ['sanitize_callback' => 'boolval']);
        register_setting('hsz-settings-group', 'hsz_disclaimer_message', ['sanitize_callback' => 'sanitize_text_field']);

        // API Keys
        $api_services = [
            'Security Analysis' => ['VirusTotal'],
            'Technology Information' => ['BuiltWith'],
            'URL Scanning' => ['URLScan.io'],
        ];

        foreach ($api_services as $category => $services) {
            foreach ($services as $service) {
                register_setting('hsz-settings-group', 'hsz_' . strtolower(str_replace('.', '_', $service)) . '_api_key', [
                    'sanitize_callback' => [$this, 'encrypt_api_key'], // Encrypt API keys before saving
                ]);
            }
        }

        // Cache Settings
        register_setting('hsz-settings-group', 'hsz_cache_duration', ['sanitize_callback' => 'absint']);
    }

    /**
     * Encrypt API keys before saving.
     *
     * @param string $api_key The API key to encrypt.
     * @return string Encrypted API key.
     */
    public function encrypt_api_key($api_key) {
        if (!empty($api_key)) {
            return base64_encode(openssl_encrypt($api_key, 'AES-256-CBC', AUTH_KEY, 0, substr(AUTH_SALT, 0, 16)));
        }
        return '';
    }

    /**
     * Decrypt API keys when retrieving.
     *
     * @param string $encrypted_key The encrypted API key.
     * @return string Decrypted API key.
     */
    public function decrypt_api_key($encrypted_key) {
        if (!empty($encrypted_key)) {
            return openssl_decrypt(base64_decode($encrypted_key), 'AES-256-CBC', AUTH_KEY, 0, substr(AUTH_SALT, 0, 16));
        }
        return '';
    }

    /**
     * Render the settings page with a tabbed interface.
     */
    public function render_settings_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        ?>
        <div class="wrap">
            <h1><?php _e('HellaZ SiteZ Analyzer Settings', 'hellaz-sitez-analyzer'); ?></h1>
            <div class="nav-tab-wrapper">
                <a href="?page=hsz-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('General', 'hellaz-sitez-analyzer'); ?>
                </a>
                <a href="?page=hsz-settings&tab=api" class="nav-tab <?php echo $active_tab === 'api' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('API Keys', 'hellaz-sitez-analyzer'); ?>
                </a>
                <a href="?page=hsz-settings&tab=advanced" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Advanced', 'hellaz-sitez-analyzer'); ?>
                </a>
                <a href="?page=hsz-settings&tab=about" class="nav-tab <?php echo $active_tab === 'about' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('About', 'hellaz-sitez-analyzer'); ?>
                </a>
            </div>

            <form method="post" action="options.php">
                <?php
                settings_fields('hsz-settings-group');
                do_settings_sections('hsz-settings-group');

                switch ($active_tab) {
                    case 'general':
                        $this->render_general_settings();
                        break;
                    case 'api':
                        $this->render_api_settings();
                        break;
                    case 'advanced':
                        $this->render_advanced_settings();
                        break;
                    case 'about':
                        $this->render_about_tab();
                        break;
                }
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the General Settings tab.
     */
    private function render_general_settings() {
        ?>
        <div class="tab-content-inner">
            <h2><?php _e('General Settings', 'hellaz-sitez-analyzer'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="hsz_enable_disclaimer"><?php _e('Enable Disclaimer', 'hellaz-sitez-analyzer'); ?></label></th>
                    <td>
                        <input type="checkbox" id="hsz_enable_disclaimer" name="hsz_enable_disclaimer" value="1" <?php checked(get_option('hsz_enable_disclaimer')); ?>>
                        <p class="description"><?php _e('Check to enable the disclaimer message.', 'hellaz-sitez-analyzer'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="hsz_disclaimer_message"><?php _e('Disclaimer Message', 'hellaz-sitez-analyzer'); ?></label></th>
                    <td>
                        <textarea id="hsz_disclaimer_message" name="hsz_disclaimer_message" rows="3" cols="50"><?php echo esc_textarea(get_option('hsz_disclaimer_message')); ?></textarea>
                        <p class="description"><?php _e('Enter the disclaimer message to display.', 'hellaz-sitez-analyzer'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="hsz_fallback_image"><?php _e('Fallback Image URL', 'hellaz-sitez-analyzer'); ?></label></th>
                    <td>
                        <input type="url" id="hsz_fallback_image" name="hsz_fallback_image" value="<?php echo esc_url(get_option('hsz_fallback_image')); ?>" class="regular-text">
                        <p class="description"><?php _e('Enter the URL of the fallback image to use when no favicon is found.', 'hellaz-sitez-analyzer'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="hsz_link_target"><?php _e('Link Target', 'hellaz-sitez-analyzer'); ?></label></th>
                    <td>
                        <select id="hsz_link_target" name="hsz_link_target">
                            <option value="_blank" <?php selected(get_option('hsz_link_target'), '_blank'); ?>><?php _e('Open in New Window', 'hellaz-sitez-analyzer'); ?></option>
                            <option value="_self" <?php selected(get_option('hsz_link_target'), '_self'); ?>><?php _e('Open in Same Window', 'hellaz-sitez-analyzer'); ?></option>
                        </select>
                        <p class="description"><?php _e('Choose how links should open.', 'hellaz-sitez-analyzer'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="hsz_icon_library"><?php _e('Icon Library', 'hellaz-sitez-analyzer'); ?></label></th>
                    <td>
                        <select id="hsz_icon_library" name="hsz_icon_library">
                            <option value="font-awesome" <?php selected(get_option('hsz_icon_library'), 'font-awesome'); ?>><?php _e('Font Awesome (CDN)', 'hellaz-sitez-analyzer'); ?></option>
                            <option value="material-icons" <?php selected(get_option('hsz_icon_library'), 'material-icons'); ?>><?php _e('Material Icons (CDN)', 'hellaz-sitez-analyzer'); ?></option>
                            <option value="bootstrap-icons" <?php selected(get_option('hsz_icon_library'), 'bootstrap-icons'); ?>><?php _e('Bootstrap Icons (CDN)', 'hellaz-sitez-analyzer'); ?></option>
                            <option value="custom" <?php selected(get_option('hsz_icon_library'), 'custom'); ?>><?php _e('Custom Icons', 'hellaz-sitez-analyzer'); ?></option>
                        </select>
                        <p class="description"><?php _e('Select the icon library to use for social media icons.', 'hellaz-sitez-analyzer'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="hsz_custom_icon_path"><?php _e('Custom Icon Path', 'hellaz-sitez-analyzer'); ?></label></th>
                    <td>
                        <input type="url" id="hsz_custom_icon_path" name="hsz_custom_icon_path" value="<?php echo esc_url(get_option('hsz_custom_icon_path')); ?>" class="regular-text">
                        <p class="description"><?php _e('Upload custom icons for social media platforms.', 'hellaz-sitez-analyzer'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Render the API Settings tab.
     */
    private function render_api_settings() {
        $api_services = [
            'Security Analysis' => ['VirusTotal'],
            'Technology Information' => ['BuiltWith'],
            'URL Scanning' => ['URLScan.io'],
        ];
        ?>
        <div class="tab-content-inner">
            <h2><?php _e('API Keys', 'hellaz-sitez-analyzer'); ?></h2>
            <table class="form-table">
                <?php foreach ($api_services as $category => $services): ?>
                    <tr>
                        <th scope="row"><strong><?php echo esc_html($category); ?></strong></th>
                        <td></td>
                    </tr>
                    <?php foreach ($services as $service): ?>
                        <tr>
                            <th scope="row"><label for="hsz_<?php echo strtolower(str_replace('.', '_', $service)); ?>_api_key"><?php printf(__('%s API Key', 'hellaz-sitez-analyzer'), $service); ?></label></th>
                            <td>
                                <input type="password" id="hsz_<?php echo strtolower(str_replace('.', '_', $service)); ?>_api_key" name="hsz_<?php echo strtolower(str_replace('.', '_', $service)); ?>_api_key" value="<?php echo esc_attr($this->decrypt_api_key(get_option('hsz_' . strtolower(str_replace('.', '_', $service)) . '_api_key'))); ?>" class="regular-text">
                                <p class="description"><?php printf(__('Enter your %s API key.', 'hellaz-sitez-analyzer'), $service); ?></p>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </table>
        </div>
        <?php
    }

    /**
     * Render the Advanced Settings tab.
     */
    private function render_advanced_settings() {
        ?>
        <div class="tab-content-inner">
            <h2><?php _e('Advanced Settings', 'hellaz-sitez-analyzer'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="hsz_cache_duration"><?php _e('Cache Duration (in seconds)', 'hellaz-sitez-analyzer'); ?></label></th>
                    <td>
                        <input type="number" id="hsz_cache_duration" name="hsz_cache_duration" value="<?php echo esc_attr(get_option('hsz_cache_duration', DAY_IN_SECONDS)); ?>" class="small-text">
                        <p class="description"><?php _e('Set the duration for which metadata will be cached.', 'hellaz-sitez-analyzer'); ?></p>
                    </td>
                </tr>
            </table>
            <?php $this->render_cache_clear_button(); ?>
        </div>
        <?php
    }

    /**
     * Render the About tab.
     */
    private function render_about_tab() {
        ?>
        <div class="tab-content-inner">
            <div class="about-section">
                <h2><?php _e('About Hellaz.Team', 'hellaz-sitez-analyzer'); ?></h2>
                <p><?php _e('HellaZ.SiteZ.Analyzer is a powerful WordPress plugin designed to analyze websites for metadata, security, technology stack, and more.', 'hellaz-sitez-analyzer'); ?></p>
                <p><?php _e('For more information, visit our website:', 'hellaz-sitez-analyzer'); ?> <a href="https://hellaz.team" target="_blank">https://hellaz.net</a></p>
                <p><?php _e('Follow us on social media:', 'hellaz-sitez-analyzer'); ?></p>
                <ul>
                    <li><a href="https://twitter.com/hellazteam" target="_blank">Twitter</a></li>
                    <li><a href="https://github.com/hellaz" target="_blank">GitHub</a></li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Handle clearing the cache.
     */
    public function handle_clear_cache() {
        if (isset($_POST['hsz_clear_cache'])) {
            check_admin_referer('hsz_clear_cache_nonce', 'hsz_nonce');
            global $wpdb;
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_hsz_%'));
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_hsz_%'));

            add_settings_error('hsz_messages', 'cache_cleared', __('Cache cleared successfully.', 'hellaz-sitez-analyzer'), 'success');
        }
    }

    /**
     * Render the "Clear Cache" button.
     */
    public function render_cache_clear_button() {
        ?>
        <div id="hsz-clear-cache-button">
            <form method="post" action="">
                <?php wp_nonce_field('hsz_clear_cache_nonce', 'hsz_nonce'); ?>
                <input type="hidden" name="action" value="hsz_clear_cache">
                <?php submit_button(__('Clear Cache', 'hellaz-sitez-analyzer'), 'secondary', 'hsz_clear_cache'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Display admin notices.
     */
    public function display_notices() {
        settings_errors('hsz_messages');
    }
}
