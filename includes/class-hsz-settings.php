<?php
namespace HSZ;

if (!defined('ABSPATH')) {
    exit;
}

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
        register_setting('hsz-settings-group', 'hsz_disclaimer_message', ['sanitize_callback' => 'sanitize_textarea_field']);

        // API Keys
        $api_services = [
            'Security Analysis' => ['VirusTotal'],
            'Technology Information' => ['BuiltWith'],
            'URL Scanning' => ['URLScan.io'],
        ];

        foreach ($api_services as $category => $services) {
            foreach ($services as $service) {
                register_setting(
                    'hsz-settings-group',
                    'hsz_' . strtolower(str_replace('.', '_', $service)) . '_api_key',
                    [
                        'sanitize_callback' => ['\\HSZ\\Utils', 'encrypt_api_key'],
                    ]
                );
            }
        }
        
        // Cache Settings
        register_setting('hsz-settings-group', 'hsz_cache_duration', ['sanitize_callback' => 'absint']);
        
        // Link target settings
        register_setting('hsz-settings-group', 'hsz_link_target', ['sanitize_callback' => 'sanitize_text_field']);
        
        // Icon library settings  
        register_setting('hsz-settings-group', 'hsz_icon_library', ['sanitize_callback' => 'sanitize_text_field']);
        
        // Custom icon path
        register_setting('hsz-settings-group', 'hsz_custom_icon_path', ['sanitize_callback' => 'sanitize_text_field']);
    }

    /**
     * Render the settings page with a tabbed interface.
     */
    public function render_settings_page() {
        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'hellaz-sitez-analyzer'));
        }
        
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        ?>
        <div class="wrap">
            <h1><?php _e('HellaZ SiteZ Analyzer Settings', 'hellaz-sitez-analyzer'); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=hsz-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('General', 'hellaz-sitez-analyzer'); ?></a>
                <a href="?page=hsz-settings&tab=api" class="nav-tab <?php echo $active_tab === 'api' ? 'nav-tab-active' : ''; ?>"><?php _e('API Keys', 'hellaz-sitez-analyzer'); ?></a>
                <a href="?page=hsz-settings&tab=advanced" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>"><?php _e('Advanced', 'hellaz-sitez-analyzer'); ?></a>
                <a href="?page=hsz-settings&tab=about" class="nav-tab <?php echo $active_tab === 'about' ? 'nav-tab-active' : ''; ?>"><?php _e('About', 'hellaz-sitez-analyzer'); ?></a>
            </h2>

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
                
                if ($active_tab !== 'about') {
                    submit_button();
                }
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
        <div class="hsz-settings-section">
            <h2><?php _e('General Settings', 'hellaz-sitez-analyzer'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Enable Disclaimer', 'hellaz-sitez-analyzer'); ?></th>
                    <td>
                        <input type="checkbox" name="hsz_enable_disclaimer" value="1" <?php checked(get_option('hsz_enable_disclaimer'), 1); ?> />
                        <p class="description"><?php _e('Check to enable the disclaimer message.', 'hellaz-sitez-analyzer'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Disclaimer Message', 'hellaz-sitez-analyzer'); ?></th>
                    <td>
                        <textarea name="hsz_disclaimer_message" rows="4" cols="50"><?php echo esc_textarea(get_option('hsz_disclaimer_message')); ?></textarea>
                        <p class="description"><?php _e('Enter the disclaimer message to display.', 'hellaz-sitez-analyzer'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Fallback Image URL', 'hellaz-sitez-analyzer'); ?></th>
                    <td>
                        <input type="url" name="hsz_fallback_image" value="<?php echo esc_attr(get_option('hsz_fallback_image')); ?>" class="regular-text" />
                        <p class="description"><?php _e('Enter the URL of the fallback image to use when no favicon is found.', 'hellaz-sitez-analyzer'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Link Target', 'hellaz-sitez-analyzer'); ?></th>
                    <td>
                        <input type="radio" name="hsz_link_target" value="_blank" <?php checked(get_option('hsz_link_target', '_blank'), '_blank'); ?> />
                        <label><?php _e('Open in New Window', 'hellaz-sitez-analyzer'); ?></label><br>
                        <input type="radio" name="hsz_link_target" value="_self" <?php checked(get_option('hsz_link_target'), '_self'); ?> />
                        <label><?php _e('Open in Same Window', 'hellaz-sitez-analyzer'); ?></label>
                        <p class="description"><?php _e('Choose how links should open.', 'hellaz-sitez-analyzer'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Icon Library', 'hellaz-sitez-analyzer'); ?></th>
                    <td>
                        <select name="hsz_icon_library">
                            <option value="fontawesome" <?php selected(get_option('hsz_icon_library', 'fontawesome'), 'fontawesome'); ?>><?php _e('Font Awesome (CDN)', 'hellaz-sitez-analyzer'); ?></option>
                            <option value="material" <?php selected(get_option('hsz_icon_library'), 'material'); ?>><?php _e('Material Icons (CDN)', 'hellaz-sitez-analyzer'); ?></option>
                            <option value="bootstrap" <?php selected(get_option('hsz_icon_library'), 'bootstrap'); ?>><?php _e('Bootstrap Icons (CDN)', 'hellaz-sitez-analyzer'); ?></option>
                            <option value="custom" <?php selected(get_option('hsz_icon_library'), 'custom'); ?>><?php _e('Custom Icons', 'hellaz-sitez-analyzer'); ?></option>
                        </select>
                        <p class="description"><?php _e('Select the icon library to use for social media icons.', 'hellaz-sitez-analyzer'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Custom Icon Path', 'hellaz-sitez-analyzer'); ?></th>
                    <td>
                        <input type="text" name="hsz_custom_icon_path" value="<?php echo esc_attr(get_option('hsz_custom_icon_path')); ?>" class="regular-text" />
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
        <div class="hsz-settings-section">
            <h2><?php _e('API Keys', 'hellaz-sitez-analyzer'); ?></h2>
            
            <table class="form-table">
                <?php foreach ($api_services as $category => $services): ?>
                    <tr>
                        <th colspan="2"><h3><?php echo esc_html($category); ?></h3></th>
                    </tr>
                    <?php foreach ($services as $service): ?>
                        <tr>
                            <th scope="row"><?php printf(__('%s API Key', 'hellaz-sitez-analyzer'), $service); ?></th>
                            <td>
                                <input type="password" name="hsz_<?php echo esc_attr(strtolower(str_replace('.', '_', $service))); ?>_api_key" value="<?php echo esc_attr(get_option('hsz_' . strtolower(str_replace('.', '_', $service)) . '_api_key')); ?>" class="regular-text" />
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
        <div class="hsz-settings-section">
            <h2><?php _e('Advanced Settings', 'hellaz-sitez-analyzer'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Cache Duration (in seconds)', 'hellaz-sitez-analyzer'); ?></th>
                    <td>
                        <input type="number" name="hsz_cache_duration" value="<?php echo esc_attr(get_option('hsz_cache_duration', DAY_IN_SECONDS)); ?>" min="0" class="small-text" />
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
        <div class="hsz-about-section">
            <div class="hsz-about-content">
                <h2><?php _e('About Hellaz.Team', 'hellaz-sitez-analyzer'); ?></h2>
                
                <p><?php _e('HellaZ.SiteZ.Analyzer is a powerful WordPress plugin designed to analyze websites for metadata, security, technology stack, and more.', 'hellaz-sitez-analyzer'); ?></p>
                
                <p><?php _e('For more information, visit our website:', 'hellaz-sitez-analyzer'); ?> <a href="https://hellaz.net" target="_blank">https://hellaz.net</a></p>
                
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
            // Verify nonce for security
            if (!wp_verify_nonce($_POST['hsz_nonce'], 'hsz_clear_cache_nonce')) {
                wp_die(__('Security check failed.', 'hellaz-sitez-analyzer'));
            }
            
            // Check user capabilities
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to perform this action.', 'hellaz-sitez-analyzer'));
            }
            
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
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="hsz_clear_cache" />
            <?php wp_nonce_field('hsz_clear_cache_nonce', 'hsz_nonce'); ?>
            <?php submit_button(__('Clear Cache', 'hellaz-sitez-analyzer'), 'secondary', 'hsz_clear_cache'); ?>
        </form>
        <?php
    }

    /**
     * Display admin notices.
     */
    public function display_notices() {
        settings_errors('hsz_messages');
    }
}
