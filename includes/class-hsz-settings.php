<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

/**
 * Handles the admin settings page with tabs, grouped options, bulk status,
 * cache controls, API toggles, templates selection, and about page.
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

        // API Toggles and Keys
        $apis = ['virustotal', 'builtwith', 'urlscan'];
        foreach ($apis as $api) {
            register_setting($this->settings_group, "hsz_{$api}_enabled", ['sanitize_callback' => 'absint']);
            register_setting($this->settings_group, "hsz_{$api}_api_key", ['sanitize_callback' => ['HSZ\Utils', 'encrypt']]);
        }

        // Bulk tab: No settings registrations here (static report only, unless expanded)

        // Cache Tab
        register_setting($this->settings_group, 'hsz_cache_duration', ['sanitize_callback' => 'absint']);
        register_setting($this->settings_group, 'hsz_cache_debug', ['sanitize_callback' => 'absint']);

        // Templates Tab
        register_setting($this->settings_group, 'hsz_template_mode', ['sanitize_callback' => 'sanitize_text_field']);
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
                <a href="<?php echo esc_url(admin_url('options-general.php?page=hsz-settings&tab=general')); ?>"
                   class="nav-tab <?php echo $tab === 'general' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('General', 'hellaz-sitez-analyzer'); ?></a>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=hsz-settings&tab=apis')); ?>"
                   class="nav-tab <?php echo $tab === 'apis' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('APIs', 'hellaz-sitez-analyzer'); ?></a>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=hsz-settings&tab=bulk')); ?>"
                   class="nav-tab <?php echo $tab === 'bulk' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Bulk', 'hellaz-sitez-analyzer'); ?></a>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=hsz-settings&tab=cache')); ?>"
                   class="nav-tab <?php echo $tab === 'cache' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Cache', 'hellaz-sitez-analyzer'); ?></a>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=hsz-settings&tab=templates')); ?>"
                   class="nav-tab <?php echo $tab === 'templates' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Templates', 'hellaz-sitez-analyzer'); ?></a>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=hsz-settings&tab=about')); ?>"
                   class="nav-tab <?php echo $tab === 'about' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('About', 'hellaz-sitez-analyzer'); ?></a>
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
                    case 'bulk':
                        $this->render_bulk_tab();
                        break;
                    case 'cache':
                        $this->render_cache_tab();
                        break;
                    case 'templates':
                        $this->render_templates_tab();
                        break;
                    case 'about':
                        $this->render_about_tab();
                        break;
                    default:
                        $this->render_general_tab();
                }

                if ($tab !== 'about' && $tab !== 'bulk') {
                    submit_button();
                }
                ?>
            </form>

            <?php if ($tab === 'cache'): ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:30px;">
                <?php wp_nonce_field('hsz_clear_cache'); ?>
                <input type="hidden" name="action" value="hsz_clear_cache">
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
                    <input type="url" name="hsz_fallback_image" 
                        value="<?php echo esc_attr(get_option('hsz_fallback_image', '')); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e('Used if no favicon is found.', 'hellaz-sitez-analyzer'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Fallback Title', 'hellaz-sitez-analyzer'); ?></th>
                <td>
                    <input type="text" name="hsz_fallback_title" 
                        value="<?php echo esc_attr(get_option('hsz_fallback_title', '')); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Fallback Description', 'hellaz-sitez-analyzer'); ?></th>
                <td>
                    <textarea name="hsz_fallback_description" rows="3" cols="50" 
                        class="large-text code"><?php echo esc_textarea(get_option('hsz_fallback_description', '')); ?></textarea>
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
                    <textarea name="hsz_disclaimer_message" rows="3" cols="50" 
                        class="large-text"><?php echo esc_textarea(get_option('hsz_disclaimer_message', '')); ?></textarea>
                </td>
            </tr>
        </table>
        <?php
    }

    private function render_apis_tab() {
        $apis = [
            'virustotal' => __('VirusTotal', 'hellaz-sitez-analyzer'),
            'builtwith'  => __('BuiltWith', 'hellaz-sitez-analyzer'),
            'urlscan'    => __('URLScan.io', 'hellaz-sitez-analyzer')
        ];
        ?>
        <table class="form-table">
            <?php foreach ($apis as $api => $label): ?>
            <tr>
                <th scope="row"><?php echo esc_html($label); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="hsz_<?php echo esc_attr($api); ?>_enabled" value="1" 
                            <?php checked(get_option("hsz_{$api}_enabled"), 1); ?> />
                        <?php printf(esc_html__('Enable %s API', 'hellaz-sitez-analyzer'), $label); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php printf(esc_html__('API Key for %s', 'hellaz-sitez-analyzer'), $label); ?></th>
                <td>
                    <input type="password" name="hsz_<?php echo esc_attr($api); ?>_api_key" value="" class="regular-text" autocomplete="off" />
                    <p class="description"><?php esc_html_e('Leave blank to keep existing key.', 'hellaz-sitez-analyzer'); ?></p>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php
    }

    private function render_bulk_tab() {
        if (class_exists('\HSZ\BulkProcessor')) {
            $bulk_report = \HSZ\BulkProcessor::get_admin_report();
            echo '<h3>' . esc_html__('Bulk Operations Status', 'hellaz-sitez-analyzer') . '</h3>';
            echo $bulk_report;
        } else {
            echo '<p>' . esc_html__('Bulk processing functionality is not installed.', 'hellaz-sitez-analyzer') . '</p>';
        }
    }

    private function render_cache_tab() {
        $cache_duration = get_option('hsz_cache_duration', DAY_IN_SECONDS);
        $cache_debug = get_option('hsz_cache_debug', 0);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Cache Duration (seconds)', 'hellaz-sitez-analyzer'); ?></th>
                <td>
                    <input type="number" name="hsz_cache_duration" value="<?php echo esc_attr($cache_duration); ?>" min="0" 
                        class="small-text" />
                    <p class="description"><?php esc_html_e('Duration to cache metadata (0 = no cache).', 'hellaz-sitez-analyzer'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Enable Cache Debug', 'hellaz-sitez-analyzer'); ?></th>
                <td>
                    <input type="checkbox" name="hsz_cache_debug" value="1" <?php checked($cache_debug, 1); ?> />
                    <p class="description"><?php esc_html_e('Show cache info in admin pages.', 'hellaz-sitez-analyzer'); ?></p>
                </td>
            </tr>
        </table>
        <?php
        if ($cache_debug) {
            \HSZ\Utils::show_cache_inspector();
        }
    }

    private function render_templates_tab() {
        $current = get_option('hsz_template_mode', 'classic');
        $templates = [
            'classic' => __('Classic Table', 'hellaz-sitez-analyzer'),
            'modern' => __('Modern Card', 'hellaz-sitez-analyzer'),
            'compact' => __('Compact (Minimal)', 'hellaz-sitez-analyzer'),
        ];
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Default Output Template', 'hellaz-sitez-analyzer'); ?></th>
                <td>
                    <select name="hsz_template_mode">
                        <?php foreach ($templates as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($current, $key); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e('Select the default output style for blocks, widgets, and shortcodes.', 'hellaz-sitez-analyzer'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    private function render_about_tab() {
        ?>
        <h2><?php esc_html_e('About SiteZ Analyzer', 'hellaz-sitez-analyzer'); ?></h2>
        <p><?php esc_html_e('SiteZ Analyzer is a WordPress plugin by Hellaz that extracts and displays website metadata, social profiles, tech stack, and security info.', 'hellaz-sitez-analyzer'); ?></p>
        <p>
            <strong><?php esc_html_e('Version:', 'hellaz-sitez-analyzer'); ?></strong>
            <?php echo esc_html(defined('HSZ_PLUGIN_VERSION') ? HSZ_PLUGIN_VERSION : 'Unknown'); ?>
        </p>
        <p>
            <strong><?php esc_html_e('Plugin URL:', 'hellaz-sitez-analyzer'); ?></strong>
            <a href="https://hellaz.net" target="_blank" rel="noopener noreferrer">hellaz.net</a>
        </p>
        <?php
    }

    public function handle_clear_cache() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'hellaz-sitez-analyzer'));
        }
        check_admin_referer('hsz_clear_cache');

        $deleted_count = \HSZ\Utils::clear_cache();

        add_settings_error('hsz_messages', 'cache_cleared', sprintf(
            _n(
                '%d cached item cleared.',
                '%d cached items cleared.',
                $deleted_count,
                'hellaz-sitez-analyzer'
            ),
            $deleted_count
        ), 'success');

        set_transient('settings_errors', get_settings_errors(), 30);

        wp_redirect(wp_get_referer());

        exit;
    }

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
