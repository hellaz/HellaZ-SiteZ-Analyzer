<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

/**
 * Handles the admin settings page, organized into tabs, with all existing settings,
 * proper sanitization, and a clear cache button.
 */
class Settings {
    private $settings_group = 'hsz-settings-group';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_hsz_clear_cache', [$this, 'handle_clear_cache']);
        add_action('admin_notices', [$this, 'admin_notices']);
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
        // General Tab
        register_setting($this->settings_group, 'hsz_fallback_image', ['sanitize_callback' => 'esc_url_raw']);
        register_setting($this->settings_group, 'hsz_fallback_title', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting($this->settings_group, 'hsz_fallback_description', ['sanitize_callback' => 'sanitize_textarea_field']);
        register_setting($this->settings_group, 'hsz_disclaimer_enabled', ['sanitize_callback' => 'absint']);
        register_setting($this->settings_group, 'hsz_disclaimer_message', ['sanitize_callback' => 'sanitize_textarea_field']);

        // API Keys Tab
        register_setting($this->settings_group, 'hsz_virustotal_api_key', ['sanitize_callback' => ['HSZ\Utils', 'encrypt_api_key']]);
        register_setting($this->settings_group, 'hsz_builtwith_api_key', ['sanitize_callback' => ['HSZ\Utils', 'encrypt_api_key']]);
        register_setting($this->settings_group, 'hsz_urlscan_api_key', ['sanitize_callback' => ['HSZ\Utils', 'encrypt_api_key']]);

        // Advanced Tab — add more settings here if needed
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'hellaz-sitez-analyzer'));
        }

        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('SiteZ Analyzer Settings', 'hellaz-sitez-analyzer'); ?></h1>

            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_url(admin_url('options-general.php?page=hsz-settings&tab=general')); ?>" class="nav-tab <?php echo $tab === 'general' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('General', 'hellaz-sitez-analyzer'); ?></a>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=hsz-settings&tab=apis')); ?>" class="nav-tab <?php echo $tab === 'apis' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('APIs', 'hellaz-sitez-analyzer'); ?></a>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=hsz-settings&tab=advanced')); ?>" class="nav-tab <?php echo $tab === 'advanced' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Advanced', 'hellaz-sitez-analyzer'); ?></a>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=hsz-settings&tab=about')); ?>" class="nav-tab <?php echo $tab === 'about' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('About', 'hellaz-sitez-analyzer'); ?></a>
            </h2>

            <form method="post" action="options.php">
                <?php
                settings_fields($this->settings_group);

                switch ($tab) {
                    case 'general':
                        $this->render_general_tab();
                        break;
                    case 'apis':
                        $this->render_apis_tab();
                        break;
                    case 'advanced':
                        $this->render_advanced_tab();
                        break;
                    case 'about':
                        $this->render_about_tab();
                        break;
                    default:
                        $this->render_general_tab();
                }

                if ($tab !== 'about') {
                    submit_button();
                }
                ?>
            </form>

            <?php if ($tab !== 'about'): ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:40px;">
                <?php wp_nonce_field('hsz_clear_cache'); ?>
                <input type="hidden" name="action" value="hsz_clear_cache" />
                <?php submit_button(__('Clear Cache', 'hellaz-sitez-analyzer'), 'secondary'); ?>
            </form>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_general_tab() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Fallback Image URL', 'hellaz-sitez-analyzer'); ?></th>
                <td>
                    <input type="url" name="hsz_fallback_image" value="<?php echo esc_attr(get_option('hsz_fallback_image', '')); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e('URL to the image used if no favicon is found.', 'hellaz-sitez-analyzer'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Fallback Title', 'hellaz-sitez-analyzer'); ?></th>
                <td>
                    <input type="text" name="hsz_fallback_title" value="<?php echo esc_attr(get_option('hsz_fallback_title', '')); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Fallback Description', 'hellaz-sitez-analyzer'); ?></th>
                <td>
                    <textarea name="hsz_fallback_description" rows="3" cols="50" class="large-text code"><?php echo esc_textarea(get_option('hsz_fallback_description', '')); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Enable Disclaimer', 'hellaz-sitez-analyzer'); ?></th>
                <td>
                    <input type="checkbox" name="hsz_disclaimer_enabled" value="1" <?php checked(get_option('hsz_disclaimer_enabled'), 1); ?> />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Disclaimer Message', 'hellaz-sitez-analyzer'); ?></th>
                <td>
                    <textarea name="hsz_disclaimer_message" rows="3" cols="50" class="large-text"><?php echo esc_textarea(get_option('hsz_disclaimer_message', '')); ?></textarea>
                </td>
            </tr>
        </table>
        <?php
    }

    private function render_apis_tab() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('VirusTotal API Key', 'hellaz-sitez-analyzer'); ?></th>
                <td>
                    <input type="password" name="hsz_virustotal_api_key" value="" class="regular-text" autocomplete="off" />
                    <p class="description"><?php esc_html_e('Provide your VirusTotal API key here (leave blank to keep existing).', 'hellaz-sitez-analyzer'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('BuiltWith API Key', 'hellaz-sitez-analyzer'); ?></th>
                <td>
                    <input type="password" name="hsz_builtwith_api_key" value="" class="regular-text" autocomplete="off" />
                    <p class="description"><?php esc_html_e('Provide your BuiltWith API key here (leave blank to keep existing).', 'hellaz-sitez-analyzer'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('URLScan.io API Key', 'hellaz-sitez-analyzer'); ?></th>
                <td>
                    <input type="password" name="hsz_urlscan_api_key" value="" class="regular-text" autocomplete="off" />
                    <p class="description"><?php esc_html_e('Provide your URLScan.io API key here (leave blank to keep existing).', 'hellaz-sitez-analyzer'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    private function render_advanced_tab() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Cache Duration (seconds)', 'hellaz-sitez-analyzer'); ?></th>
                <td>
                    <input type="number" name="hsz_cache_duration" value="<?php echo esc_attr( get_option('hsz_cache_duration', DAY_IN_SECONDS) ); ?>" class="small-text" min="0" />
                    <p class="description"><?php esc_html_e('Duration in seconds for caching metadata results (0 = no caching).', 'hellaz-sitez-analyzer'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    private function render_about_tab() {
        ?>
        <h2><?php esc_html_e( 'About SiteZ Analyzer', 'hellaz-sitez-analyzer' ); ?></h2>
        <p><?php esc_html_e( 'SiteZ Analyzer is a WordPress plugin by Hellaz that analyzes websites to extract metadata, security info, social links, and much more.', 'hellaz-sitez-analyzer' ); ?></p>
        <p>
            <?php esc_html_e( 'Version:', 'hellaz-sitez-analyzer' ); ?>
            <strong><?php echo esc_html( defined( 'HSZ_PLUGIN_VERSION' ) ? HSZ_PLUGIN_VERSION : 'Unknown' ); ?></strong>
        </p>
        <p>
            <?php esc_html_e( 'Visit our website:', 'hellaz-sitez-analyzer' ); ?>
            <a href="https://hellaz.net" target="_blank" rel="noopener noreferrer">hellaz.net</a>
        </p>
        <?php
    }

    /**
     * Handle cache clearing when Clear Cache button is clicked.
     */
    public function handle_clear_cache() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'hellaz-sitez-analyzer'));
        }
        check_admin_referer('hsz_clear_cache');

        global $wpdb;
        $transients_deleted = 0;
        $options = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_hsz_%'
            )
        );

        foreach ($options as $option_name) {
            if (strpos($option_name, '_transient_') === 0) {
                $transient_key = str_replace('_transient_', '', $option_name);
                delete_transient($transient_key);
                $transients_deleted++;
            }
        }

        add_settings_error('hsz_messages', 'cache_cleared', sprintf(_n('%d transient cache cleared.', '%d transient caches cleared.', $transients_deleted, 'hellaz-sitez-analyzer'), $transients_deleted), 'success');
        set_transient('settings_errors', get_settings_errors(), 30);

        wp_redirect(wp_get_referer());
        exit;
    }

    /**
     * Show admin notices for cache clearing or other settings messages.
     */
    public function admin_notices() {
        if ($messages = get_transient('settings_errors')) {
            foreach ($messages as $message) {
                ?>
                <div class="notice notice-<?php echo esc_attr($message['type']); ?> is-dismissible">
                    <p><?php echo esc_html($message['message']); ?></p>
                </div>
                <?php
            }
            delete_transient('settings_errors');
        }
    }
}
