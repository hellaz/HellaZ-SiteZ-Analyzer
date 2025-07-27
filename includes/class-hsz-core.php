<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

class Core {
    private static $instance = null;
    private $settings;

    /**
     * Main plugin bootstrapper. Ensures singleton pattern.
     */
    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Loads all dependencies and hooks core integrations.
     */
    private function __construct() {
        $this->load_dependencies();
        $this->settings = new \HSZ\Settings();
        $this->init_hooks();
    }

    /**
     * Loads all required plugin classes.
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
        if (file_exists(HSZ_PLUGIN_PATH . 'includes/class-hsz-widget.php')) {
            require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-widget.php';
        }
    }

    /**
     * Registers all plugin hooks and filters.
     */
    private function init_hooks() {
        add_action('admin_menu', array('HSZ\\Admin', 'add_admin_menu'));
        add_action('init', array('HSZ\\Shortcode', 'register'));
        add_action('widgets_init', array('HSZ\\Widget', 'register_widget'));
        add_action('init', array('HSZ\\Gutenberg', 'get_instance'));
        // There is NO line like the following (and none should exist):
        // add_action('admin_init', array('HSZ\\Settings', '__construct'));
    }

    /**
     * Plugin activation hook logic (add database schema, etc.).
     */
    public static function activate() {
        // Optionally place DB table setup, default settings, etc.
    }

    /**
     * Plugin deactivation hook logic (clear scheduled events, etc.).
     */
    public static function deactivate() {
        // Optionally remove scheduled events, transient cache, etc.
    }
}
