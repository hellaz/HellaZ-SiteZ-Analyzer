<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

class Settings {
    private $settings_group = 'hsz-settings-group';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_hsz_clear_cache', [$this, 'handle_clear_cache']);
        add_action('admin_post_hsz_toggle_api', [$this, 'handle_api_toggle']);
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
        // General Settings
        register_setting($this->settings_group, 'hsz_fallback_image', ['sanitize_callback' => 'esc_url_raw']);
        register_setting($this->settings_group, 'hsz_fallback_title', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting($this->settings_group, 'hsz_fallback_description', ['sanitize_callback' => 'sanitize_textarea_field']);
        register_setting($this->settings_group, 'hsz_disclaimer_enabled', ['sanitize_callback' => 'absint']);
        register_setting($this->settings_group, 'hsz_disclaimer_message', ['sanitize_callback' => 'sanitize_textarea_field']);

        // API Toggles and Keys (APIs tab)
        $apis = ['virustotal', 'builtwith', 'urlscan'];
        foreach ($apis as $api) {
            register_setting($this->settings_group, "hsz_{$api}_enabled", ['sanitize_callback' => 'absint']);
            register_setting($this->settings_group, "hsz_{$api}_api_key", ['sanitize_callback' => ['HSZ\Utils', 'encrypt_api_key']]);
        }

        // PERFORMANCE (Advanced tab)
        register_setting($this->settings_group, 'hsz_cache_duration', ['sanitize_callback' => 'absint']);
        register_setting($this->settings_group, 'hsz_cache_debug', ['sanitize_callback' => 'absint']);

        // Output Template (Templates tab)
        register_setting($this->settings_group, 'hsz_template_mode', ['sanitize_callback' => 'sanitize_text_field']);
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) wp_die(__('You do not have permission to access this page.', 'hellaz-sitez-analyzer'));
        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('SiteZ Analyzer Settings', 'hellaz-sitez-analyzer'); ?></h1>
            <h2 class="nav-tab-wrapper">
                <?php
                $tabs = [
                    'general'   => __('General', 'hellaz-sitez-analyzer'),
                    'apis'      => __('APIs', 'hellaz-sitez-analyzer'),
                    'bulk'      => __('Bulk', 'hellaz-sitez-analyzer'),
                    'cache'     => __('Cache', 'hellaz-sitez-analyzer'),
                    'templates' => __('Templates', 'hellaz-sitez-analyzer'),
                    'about'     => __('About', 'hellaz-sitez-analyzer'),
                ];
                foreach ($tabs as $id => $label) {
                    printf(
                        '<a href="%s" class="nav-tab%s">%s</a>',
                        esc_url(admin_url('options-general.php?page=hsz-settings&tab=' . $id)),
                        $tab === $id ? ' nav-tab-active' : '',
                        esc_html($label)
                    );
                }
                ?>
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
                if ($tab !== 'about' && $tab !== 'bulk') submit_button();
                ?>
            </form>
            <?php if ($tab === 'cache'): ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('hsz_clear_cache'); ?>
                <input type="hidden" name="action" value="hsz_clear_cache" />
                <?php submit_button(__('Clear All Caches', 'hellaz-sitez-analyzer'), 'secondary'); ?>
            </form>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_general_tab() {
        ?>
        <table class="form-table">
            <tr>
                <th><?php _e('Fallback Image URL', 'hellaz-sitez-analyzer'); ?></th>
                <td><input type="url" name="hsz_fallback_image" value="<?php echo esc_attr(get_option('hsz_fallback_image', '')); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><?php _e('Fallback Title', 'hellaz-sitez-analyzer'); ?></th>
                <td><input type="text" name="hsz_fallback_title" value="<?php echo esc_attr(get_option('hsz_fallback_title', '')); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><?php _e('Fallback Description', 'hellaz-sitez-analyzer'); ?></th>
                <td><textarea name="hsz_fallback_description" rows="3" cols="50" class="large-text code"><?php echo esc_textarea(get_option('hsz_fallback_description', '')); ?></textarea></td>
            </tr>
            <tr>
                <th><?php _e('Enable Disclaimer', 'hellaz-sitez-analyzer'); ?></th>
                <td><input type="checkbox" name="hsz_disclaimer_enabled" value="1" <?php checked(get_option('hsz_disclaimer_enabled'), 1); ?> /></td>
            </tr>
            <tr>
                <th><?php _e('Disclaimer Message', 'hellaz-sitez-analyzer'); ?></th>
                <td><textarea name="hsz_disclaimer_message" rows="3" cols="50" class="large-text"><?php echo esc_textarea(get_option('hsz_disclaimer_message', '')); ?></textarea></td>
            </tr>
        </table>
        <?php
    }

    private function render_apis_tab() {
        $apis = ['virustotal' => 'VirusTotal', 'builtwith' => 'BuiltWith', 'urlscan' => 'URLScan.io'];
        ?>
        <table class="form-table">
            <?php foreach ($apis as $api => $label): ?>
            <tr>
                <th><?php printf(esc_html__('%s Integration', 'hellaz-sitez-analyzer'), $label); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="hsz_<?php echo $api; ?>_enabled" value="1" <?php checked(get_option('hsz_'.$api.'_enabled'), 1); ?> />
                        <?php printf(esc_html__('Enable %s API', 'hellaz-sitez-analyzer'), $label); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php printf(esc_html__('%s API Key', 'hellaz-sitez-analyzer'), $label); ?></th>
                <td>
                    <input type="password" name="hsz_<?php echo $api; ?>_api_key" value="" class="regular-text" autocomplete="off" />
                    <p class="description"><?php esc_html_e('Leave blank to keep existing.', 'hellaz-sitez-analyzer'); ?></p>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php
    }

    private function render_bulk_tab() {
        // Bulk processor status, queue, errors
        if (class_exists('\HSZ\BulkProcessor')) {
            $bulk_info = \HSZ\BulkProcessor::get_admin_bulk_report();
            echo '<h3>' . esc_html__('Bulk Operations Status', 'hellaz-sitez-analyzer') . '</h3>';
            echo $bulk_info ?: '<p>' . esc_html__('No recent bulk operations found.', 'hellaz-sitez-analyzer') . '</p>';
        }
    }

    private function render_cache_tab() {
        $duration = get_option('hsz_cache_duration', DAY_IN_SECONDS);
        $debug = get_option('hsz_cache_debug', 0);
        ?>
        <table class="form-table">
            <tr>
                <th><?php _e('Cache Duration (seconds)', 'hellaz-sitez-analyzer'); ?></th>
                <td>
                    <input type="number" name="hsz_cache_duration" value="<?php echo esc_attr($duration); ?>" min="0" class="small-text" />
                    <p class="description"><?php _e('How long to cache fetched metadata. 0 = no caching.', 'hellaz-sitez-analyzer'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php _e('Enable Cache Debug', 'hellaz-sitez-analyzer'); ?></th>
                <td>
                    <input type="checkbox" name="hsz_cache_debug" value="1" <?php checked($debug, 1); ?> />
                    <p class="description"><?php _e('Show cache usage and keys in admin bulk/result pages.', 'hellaz-sitez-analyzer'); ?></p>
                </td>
            </tr>
        </table>
        <?php
        // Show cache inspection if enabled:
        if ($debug) {
            \HSZ\Utils::show_cache_inspector();
        }
    }

    private function render_templates_tab() {
        ?>
        <table class="form-table">
            <tr>
                <th><?php _e('Default Output Template', 'hellaz-sitez-analyzer'); ?></th>
                <td>
                    <select name="hsz_template_mode">
                        <?php
                        $templates = [
                            'classic' => __('Classic Table', 'hellaz-sitez-analyzer'),
                            'modern'  => __('Modern Card', 'hellaz-sitez-analyzer'),
                            'compact' => __('Compact (Minimal)', 'hellaz-sitez-analyzer')
                        ];
                        $chosen = get_option('hsz_template_mode', 'classic');
                        foreach ($templates as $key => $label) {
                            printf('<option value="%s"%s>%s</option>',
                                esc_attr($key),
                                selected($chosen, $key, false),
                                esc_html($label)
                            );
                        }
                        ?>
                    </select>
                    <p class="description"><?php _e('Choose the default output display style for blocks, widgets, shortcodes.', 'hellaz-sitez-analyzer'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    private function render_about_tab() {
        ?>
        <h2><?php _e('About SiteZ Analyzer', 'hellaz-sitez-analyzer'); ?></h2>
        <p><?php _e('SiteZ Analyzer is a WordPress plugin by Hellaz for comprehensive metadata, tech stack, security, and profile analysis.', 'hellaz-sitez-analyzer'); ?></p>
        <p>
            <?php _e('Version:', 'hellaz-sitez-analyzer'); ?> <strong><?php echo esc_html(defined('HSZ_PLUGIN_VERSION') ? HSZ_PLUGIN_VERSION : 'Unknown'); ?></strong><br>
            <?php _e('Website:', 'hellaz-sitez-analyzer'); ?> <a href="https://hellaz.net" target="_blank" rel="noopener noreferrer">hellaz.net</a>
        </p>
        <?php
    }

    public function handle_clear_cache() {
        if (!current_user_can('manage_options')) wp_die(__('Insufficient permissions.', 'hellaz-sitez-analyzer'));
        check_admin_referer('hsz_clear_cache');
        $count = \HSZ\Utils::clear_cache();
        add_settings_error('hsz_messages', 'cache_cleared', sprintf(_n('%d cache entry cleared.', '%d cache entries cleared.', $count, 'hellaz-sitez-analyzer'), $count), 'success');
        set_transient('settings_errors', get_settings_errors(), 30);
        wp_redirect(wp_get_referer());
        exit;
    }

    public function admin_notices() {
        if ($messages = get_transient('settings_errors')) {
            foreach ($messages as $message) {
                printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                    esc_attr($message['type']), esc_html($message['message'])
                );
            }
            delete_transient('settings_errors');
        }
    }
}
