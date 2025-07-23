<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

class Core {
    public static function init() {
        static $inst;
        if (!$inst) $inst = new self();
        return $inst;
    }
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    private function load_dependencies() {
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-settings.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-fallbacks.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-gutenberg.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-shortcode.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-admin.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-security.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-utils.php';
        // add other actual logic classes here as needed (e.g., Metadata, SocialMedia)
    }
    private function init_hooks() {
        add_action('admin_menu', array('HSZ\\Admin', 'add_admin_menu'));
        add_action('init', array('HSZ\\Shortcode', 'register'));
        add_action('widgets_init', array('HSZ\\Widget', 'register_widget'));
        add_action('init', array('HSZ\\Gutenberg', 'get_instance'));
        add_action('admin_init', array('HSZ\\Settings', '__construct'));
    }
    public static function activate() { /* add install logic e.g. DB */ }
    public static function deactivate() { /* add uninstall/cleanup logic */ }
}
