<?php
namespace HSZ;

if (!defined('ABSPATH')) {
    exit;
}

class Core {
    private static $instance = null;
    
    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        // Load required classes
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-metadata.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-social-media.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-rss.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-apianalysis.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-cache.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-shortcode.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-settings.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-hooks.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-fallbacks.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-widget.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-gutenberg.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-utils.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-apimanager.php';
    }
    
    private function init_hooks() {
        // Initialize admin menu
        add_action('admin_menu', array($this, 'register_admin_menu'));
        
        // Initialize widgets
        add_action('widgets_init', array($this, 'register_widgets'));
        
        // Initialize Gutenberg blocks
        add_action('init', array($this, 'register_blocks'));
        
        // Initialize shortcodes
        add_action('init', array($this, 'register_shortcodes'));
        
        // Load text domain for translations
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }
    
    public function register_admin_menu() {
        // Initialize settings page
        new Settings();
    }
    
    public function register_widgets() {
        register_widget('HSZ\\Widget');
    }
    
    public function register_blocks() {
        // Initialize Gutenberg block using the singleton pattern
        Gutenberg::get_instance();
    }
    
    public function register_shortcodes() {
        new Shortcode();
    }
    
    public function load_textdomain() {
        load_plugin_textdomain(
            'hellaz-sitez-analyzer', 
            false, 
            dirname(plugin_basename(__FILE__)) . '/../languages/'
        );
    }
    
    public function activate() {
        // Initialize all plugin components
        new Metadata();
        new SocialMedia();
        new RSS();
        new APIAnalysis();
        new Cache();
        new Hooks();
        new Fallbacks();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Clean up on deactivation
        wp_cache_flush();
    }
}
