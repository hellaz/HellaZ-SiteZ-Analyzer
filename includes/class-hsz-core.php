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
        $this->register_ajax_handlers();
    }
    
    private function load_dependencies() {
        // Core functionality classes
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-metadata.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-social-media.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-rss.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-apianalysis.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-cache.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-security.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-utils.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-apimanager.php';
        
        // UI and integration classes
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-admin.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-shortcode.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-settings.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-widget.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-gutenberg.php';
        require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-hooks.php';
    }
    
    private function init_hooks() {
        // WordPress integration hooks
        add_action('admin_menu', array('HSZ\\Admin', 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('widgets_init', array($this, 'register_widgets'));
        add_action('init', array($this, 'register_blocks'));
        add_action('init', array($this, 'register_shortcodes'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Performance optimization hooks
        add_action('wp_footer', array($this, 'cleanup_transients'), 999);
        add_action('hsz_cleanup_expired_cache', array($this, 'cleanup_expired_cache'));
        
        // Security hooks
        add_action('init', array($this, 'security_headers'));
    }
    
    private function register_ajax_handlers() {
        // Public AJAX handlers
        add_action('wp_ajax_hsz_analyze_url', array($this, 'handle_analyze_url'));
        add_action('wp_ajax_nopriv_hsz_analyze_url', array($this, 'handle_analyze_url'));
        
        // Admin AJAX handlers
        add_action('wp_ajax_hsz_process_single_url', array($this, 'handle_single_url_ajax'));
        add_action('wp_ajax_hsz_start_bulk_processing', array($this, 'handle_bulk_processing_ajax'));
        add_action('wp_ajax_hsz_get_bulk_status', array($this, 'handle_bulk_status_ajax'));
        add_action('wp_ajax_hsz_cancel_bulk_processing', array($this, 'handle_cancel_bulk_ajax'));
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'hsz') === false) return;
        
        wp_enqueue_script(
            'hsz-admin',
            HSZ_PLUGIN_URL . 'assets/js/hsz-admin.js',
            array('jquery'),
            HSZ_PLUGIN_VERSION,
            true
        );
        
        wp_localize_script('hsz-admin', 'hsz_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hsz_security_nonce'),
            'bulk_nonce' => wp_create_nonce('hsz_bulk_security_nonce'),
            'strings' => array(
                'processing' => __('Processing...', 'hellaz-sitez-analyzer'),
                'error' => __('An error occurred', 'hellaz-sitez-analyzer'),
                'invalid_url' => __('Please enter a valid URL', 'hellaz-sitez-analyzer')
            )
        ));
        
        wp_enqueue_style(
            'hsz-admin',
            HSZ_PLUGIN_URL . 'assets/css/hsz-admin.css',
            array(),
            HSZ_PLUGIN_VERSION
        );
    }
    
    public function register_widgets() {
        register_widget('HSZ\\Widget');
    }
    
    public function register_blocks() {
        if (class_exists('HSZ\\Gutenberg')) {
            Gutenberg::get_instance();
        }
    }
    
    public function register_shortcodes() {
        if (class_exists('HSZ\\Shortcode')) {
            new Shortcode();
        }
    }
    
    public function load_textdomain() {
        load_plugin_textdomain(
            'hellaz-sitez-analyzer', 
            false, 
            dirname(plugin_basename(HSZ_PLUGIN_PATH)) . '/languages/'
        );
    }
    
    public function security_headers() {
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
    }
    
    // AJAX Handlers
    public function handle_analyze_url() {
        try {
            $security = new Security();
            $security->verify_nonce('hsz_analyze_nonce');
            
            $url = sanitize_text_field($_POST['url'] ?? '');
            $validated_url = $security->validate_url($url);
            
            if (!$security->check_rate_limit('analyze_url')) {
                throw new \Exception(__('Rate limit exceeded. Please try again later.', 'hellaz-sitez-analyzer'));
            }
            
            $metadata = new Metadata();
            $result = $metadata->extract_metadata($validated_url);
            
            wp_send_json_success($result);
            
        } catch (\Exception $e) {
            Utils::log_error('AJAX analyze URL failed: ' . $e->getMessage(), array('url' => $url ?? ''));
            wp_send_json_error($e->getMessage());
        }
    }
    
    public function handle_single_url_ajax() {
        try {
            check_ajax_referer('hsz_security_nonce', 'nonce');
            
            if (!current_user_can('edit_posts')) {
                throw new \Exception(__('Insufficient permissions', 'hellaz-sitez-analyzer'));
            }
            
            $security = new Security();
            $url = sanitize_text_field($_POST['url'] ?? '');
            $validated_url = $security->validate_url($url);
            
            $metadata = new Metadata();
            $result = $metadata->extract_metadata($validated_url);
            
            wp_send_json_success($result);
            
        } catch (\Exception $e) {
            Utils::log_error('Single URL analysis failed: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }
    
    public function handle_bulk_processing_ajax() {
        try {
            check_ajax_referer('hsz_bulk_security_nonce', 'nonce');
            
            if (!current_user_can('edit_posts')) {
                throw new \Exception(__('Insufficient permissions', 'hellaz-sitez-analyzer'));
            }
            
            $security = new Security();
            $urls = $security->sanitize_bulk_urls($_POST['urls'] ?? array());
            $batch_name = sanitize_text_field($_POST['batch_name'] ?? '');
            
            if (empty($urls)) {
                throw new \Exception(__('No valid URLs provided', 'hellaz-sitez-analyzer'));
            }
            
            $processor = new BulkProcessor();
            $batch_id = $processor->start_bulk_processing($urls, $batch_name);
            
            wp_send_json_success(array('batch_id' => $batch_id));
            
        } catch (\Exception $e) {
            Utils::log_error('Bulk processing start failed: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }
    
    public function handle_bulk_status_ajax() {
        try {
            check_ajax_referer('hsz_bulk_security_nonce', 'nonce');
            
            $batch_id = sanitize_text_field($_POST['batch_id'] ?? '');
            if (empty($batch_id)) {
                throw new \Exception(__('Batch ID required', 'hellaz-sitez-analyzer'));
            }
            
            $processor = new BulkProcessor();
            $status = $processor->get_batch_status($batch_id);
            
            wp_send_json_success($status);
            
        } catch (\Exception $e) {
            Utils::log_error('Bulk status check failed: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }
    
    public function handle_cancel_bulk_ajax() {
        try {
            check_ajax_referer('hsz_bulk_security_nonce', 'nonce');
            
            if (!current_user_can('edit_posts')) {
                throw new \Exception(__('Insufficient permissions', 'hellaz-sitez-analyzer'));
            }
            
            $batch_id = sanitize_text_field($_POST['batch_id'] ?? '');
            if (empty($batch_id)) {
                throw new \Exception(__('Batch ID required', 'hellaz-sitez-analyzer'));
            }
            
            $processor = new BulkProcessor();
            $result = $processor->cancel_batch($batch_id);
            
            wp_send_json_success($result);
            
        } catch (\Exception $e) {
            Utils::log_error('Bulk cancellation failed: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }
    
    // Performance optimization methods
    public function cleanup_transients() {
        if (wp_doing_ajax() || wp_doing_cron()) return;
        
        if (rand(1, 100) <= 5) { // 5% chance to run cleanup
            $this->cleanup_expired_cache();
        }
    }
    
    public function cleanup_expired_cache() {
        global $wpdb;
        
        $expired_transients = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_timeout_hsz_%' 
             AND option_value < UNIX_TIMESTAMP()"
        );
        
        foreach ($expired_transients as $transient) {
            $key = str_replace('_transient_timeout_', '', $transient);
            delete_transient($key);
        }
        
        Utils::log_error('Cleaned up ' . count($expired_transients) . ' expired cache entries');
    }
    
    public function activate() {
        // Create database tables
        Database::create_tables();
        
        // Schedule cleanup cron job
        if (!wp_next_scheduled('hsz_cleanup_expired_cache')) {
            wp_schedule_event(time(), 'daily', 'hsz_cleanup_expired_cache');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        Utils::log_error('Plugin activated successfully');
    }
    
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('hsz_cleanup_expired_cache');
        
        // Clean up temporary data
        wp_cache_flush();
        
        Utils::log_error('Plugin deactivated');
    }
}
