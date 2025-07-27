<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

/**
 * Core plugin loader and initializer.
 */
class Core {

    /**
     * Singleton instance.
     *
     * @var Core|null
     */
    private static $instance = null;

    /**
     * Settings instance.
     *
     * @var Settings
     */
    private $settings;

    /**
     * BulkProcessor instance.
     *
     * @var BulkProcessor|null
     */
    private $bulk_processor;

    /**
     * AjaxHandler instance.
     *
     * @var AjaxHandler|null
     */
    private $ajax_handler;

    /**
     * AdminLogs instance.
     *
     * @var AdminLogs|null
     */
    private $admin_logs;

    /**
     * Initialize plugin via singleton.
     *
     * @return Core
     */
    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor loads dependencies and sets up hooks.
     */
    private function __construct() {
        $this->load_dependencies();

        $this->settings = new Settings();
        $this->bulk_processor = class_exists('\HSZ\BulkProcessor') ? new BulkProcessor() : null;
        $this->ajax_handler = class_exists('\HSZ\AjaxHandler') ? new AjaxHandler() : null;
        $this->admin_logs = class_exists('\HSZ\AdminLogs') ? new AdminLogs() : null;

        $this->init_hooks();
    }

    /**
     * Loads required class dependencies.
     */
    private function load_dependencies() {
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-settings.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-fallbacks.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-gutenberg.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-shortcode.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-admin.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-security.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-utils.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-metadata.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-social-media.php';

        if (file_exists(HSZ_PLUGIN_PATH . 'includes/class-hsz-bulk-processor.php')) {
            require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-bulk-processor.php';
        }

        if (file_exists(HSZ_PLUGIN_PATH . 'includes/class-hsz-widget.php')) {
            require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-widget.php';
        }

        if (file_exists(HSZ_PLUGIN_PATH . 'includes/class-hsz-ajax.php')) {
            require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-ajax.php';
        }

        if (file_exists(HSZ_PLUGIN_PATH . 'includes/class-hsz-admin-logs.php')) {
            require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-admin-logs.php';
        }
    }

    /**
     * Initialize and register WordPress hooks and filters.
     */
    private function init_hooks() {
        // Settings menu and page initialization are handled inside Settings constructor.

        add_action('init', [ 'HSZ\\Shortcode', 'register' ]);
        add_action('widgets_init', [ 'HSZ\\Widget', 'register_widget' ]);
        add_action('init', [ 'HSZ\\Gutenberg', 'get_instance' ]);
        // AjaxHandler and AdminLogs set up their own hooks in their constructors.
    }

    /**
     * Plugin activation routine.
     */
    public static function activate() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        // Error log table
        $sql1 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hsz_error_log (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            timestamp datetime NOT NULL,
            level varchar(10) NOT NULL,
            message text NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            ip varchar(45) NOT NULL,
            context longtext,
            memory varchar(20),
            url varchar(255),
            PRIMARY KEY (id),
            KEY timestamp (timestamp),
            KEY level (level)
        ) $charset_collate;";

        // Bulk batches table
        $sql2 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hsz_bulk_batches (
            id int UNSIGNED NOT NULL AUTO_INCREMENT,
            batch_id varchar(64) NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            name varchar(255) NOT NULL,
            status varchar(32) NOT NULL,
            total_urls int NOT NULL,
            processed_urls int NOT NULL,
            successful_urls int NOT NULL,
            failed_urls int NOT NULL,
            settings longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime,
            PRIMARY KEY (id),
            KEY batch_id (batch_id)
        ) $charset_collate;";

        // Bulk results table
        $sql3 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}hsz_bulk_results (
            id int UNSIGNED NOT NULL AUTO_INCREMENT,
            batch_id varchar(64) NOT NULL,
            url text NOT NULL,
            status varchar(32) NOT NULL,
            error_message text,
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            processed_at datetime,
            PRIMARY KEY (id),
            KEY batch_id (batch_id)
        ) $charset_collate;";

        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);

        // Set default cache duration if not set
        if (!get_option('hsz_cache_duration')) {
            update_option('hsz_cache_duration', DAY_IN_SECONDS);
        }
    }

    /**
     * Plugin deactivation routine.
     */
    public static function deactivate() {
        // Cleanup tasks if necessary on plugin deactivation.
    }
}
