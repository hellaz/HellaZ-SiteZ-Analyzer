<?php
/**
 * Core plugin class that orchestrates all functionality
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.0.0
 */

namespace HSZ;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Core {

    /**
     * Plugin instance
     *
     * @var Core|null
     */
    private static $instance = null;

    /**
     * Plugin components
     *
     * @var array
     */
    private $components = [];

    /**
     * Get plugin instance (Singleton pattern)
     *
     * @return Core
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        // Constructor is private for singleton
    }

    /**
     * Initialize the plugin
     */
    public function run() {
        add_action( 'init', [ $this, 'init' ] );
        add_action( 'plugins_loaded', [ $this, 'load_textdomain' ], 10 );

        // Initialize components
        $this->init_components();
    }

    /**
     * Initialize plugin functionality
     */
    public function init() {
        // Hook into WordPress initialization
        if ( is_admin() ) {
            $this->init_admin();
        }

        // Initialize frontend functionality
        $this->init_frontend();

        // Initialize AJAX handlers
        $this->init_ajax();
    }

    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Load admin functionality
        if ( is_admin() ) {
            $this->components['admin'] = new Admin();
        }

        // Load shortcode functionality
        $this->components['shortcode'] = new Shortcode();

        // Load widget functionality  
        add_action( 'widgets_init', [ $this, 'register_widgets' ] );

        // Load Gutenberg block
        if ( function_exists( 'register_block_type' ) ) {
            add_action( 'init', [ $this, 'register_blocks' ] );
        }
    }

    /**
     * Initialize admin functionality
     */
    private function init_admin() {
        // Admin functionality is loaded via components
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    /**
     * Initialize frontend functionality
     */
    private function init_frontend() {
        // Enqueue frontend assets
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
    }

    /**
     * Initialize AJAX handlers
     */
    private function init_ajax() {
        // AJAX handlers for logged-in users
        add_action( 'wp_ajax_hsz_analyze_url', [ $this, 'ajax_analyze_url' ] );
        add_action( 'wp_ajax_hsz_start_bulk_processing', [ $this, 'ajax_start_bulk_processing' ] );

        // AJAX handlers for non-logged-in users (if needed)
        add_action( 'wp_ajax_nopriv_hsz_analyze_url', [ $this, 'ajax_analyze_url' ] );
    }

    /**
     * Load plugin textdomain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'hellaz-sitez-analyzer',
            false,
            dirname( HSZ_PLUGIN_BASENAME ) . '/languages/'
        );
    }

    /**
     * Register widgets
     */
    public function register_widgets() {
        if ( class_exists( 'HSZ\\Widget' ) ) {
            register_widget( 'HSZ\\Widget' );
        }
    }

    /**
     * Register Gutenberg blocks
     */
    public function register_blocks() {
        if ( class_exists( 'HSZ\\Block' ) ) {
            $block = new Block();
            $block->register();
        }
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets( $hook ) {
        // Only load on plugin pages
        if ( strpos( $hook, 'hellaz-sitez-analyzer' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'hsz-admin',
            HSZ_ASSETS_URL . 'css/hsz-admin.css',
            [],
            HSZ_VERSION
        );

        wp_enqueue_script(
            'hsz-admin',
            HSZ_ASSETS_URL . 'js/hsz-admin.js',
            [ 'jquery' ],
            HSZ_VERSION,
            true
        );

        // Localize script for AJAX
        wp_localize_script( 'hsz-admin', 'hsz_admin_params', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'hsz_admin_nonce' ),
            'error_url_empty' => __( 'Please provide a URL to analyze.', 'hellaz-sitez-analyzer' ),
            'text_analyzing' => __( 'Analyzing...', 'hellaz-sitez-analyzer' ),
            'text_loading' => __( 'Fetching data...', 'hellaz-sitez-analyzer' ),
            'text_analysis_complete' => __( 'Analysis Complete', 'hellaz-sitez-analyzer' ),
            'error_generic' => __( 'An unknown error occurred.', 'hellaz-sitez-analyzer' ),
            'error_ajax' => __( 'The request failed. Please check your connection and try again.', 'hellaz-sitez-analyzer' ),
        ]);
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Only enqueue if shortcode or widget is present
        if ( $this->should_load_frontend_assets() ) {
            wp_enqueue_style(
                'hsz-frontend',
                HSZ_ASSETS_URL . 'css/hsz-frontend.css',
                [],
                HSZ_VERSION
            );

            wp_enqueue_script(
                'hsz-frontend',
                HSZ_ASSETS_URL . 'js/hsz-frontend.js',
                [ 'jquery' ],
                HSZ_VERSION,
                true
            );
        }
    }

    /**
     * Check if frontend assets should be loaded
     */
    private function should_load_frontend_assets() {
        global $post;

        // Check if shortcode is present
        if ( $post && has_shortcode( $post->post_content, 'hsz_analyzer' ) ) {
            return true;
        }

        // Check if widget is active
        if ( is_active_widget( false, false, 'hsz_widget' ) ) {
            return true;
        }

        // Check if Gutenberg block is present
        if ( $post && has_blocks( $post->post_content ) ) {
            $blocks = parse_blocks( $post->post_content );
            foreach ( $blocks as $block ) {
                if ( $block['blockName'] === 'hsz/analyzer' ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * AJAX handler for URL analysis
     */
    public function ajax_analyze_url() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'hsz_admin_nonce' ) ) {
            wp_send_json_error( __( 'Security check failed.', 'hellaz-sitez-analyzer' ) );
        }

        $url = sanitize_url( $_POST['url'] ?? '' );

        if ( empty( $url ) ) {
            wp_send_json_error( __( 'URL is required.', 'hellaz-sitez-analyzer' ) );
        }

        try {
            // Use the analyzer class to analyze the URL
            if ( class_exists( 'HSZ\\Analyzer' ) ) {
                $analyzer = new Analyzer();
                $result = $analyzer->analyze_url( $url );
                
                wp_send_json_success( [
                    'metadata' => $result,
                    'url' => $url
                ] );
            } else {
                // Fallback basic analysis
                $result = [
                    'title' => 'Analysis Complete',
                    'description' => 'Basic analysis functionality - Analyzer class not found',
                    'url' => $url
                ];
                
                wp_send_json_success( [
                    'metadata' => $result,
                    'url' => $url
                ] );
            }

        } catch ( Exception $e ) {
            wp_send_json_error( $e->getMessage() );
        }
    }

    /**
     * AJAX handler for bulk processing
     */
    public function ajax_start_bulk_processing() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'hsz_admin_nonce' ) ) {
            wp_send_json_error( __( 'Security check failed.', 'hellaz-sitez-analyzer' ) );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'hellaz-sitez-analyzer' ) );
        }

        $batch_name = sanitize_text_field( $_POST['batch_name'] ?? '' );
        $urls = array_map( 'sanitize_url', $_POST['urls'] ?? [] );

        if ( empty( $urls ) ) {
            wp_send_json_error( __( 'No URLs provided.', 'hellaz-sitez-analyzer' ) );
        }

        // Generate batch ID
        $batch_id = 'batch_' . time() . '_' . wp_rand( 1000, 9999 );

        // Store batch data (you would implement actual bulk processing here)
        set_transient( 'hsz_bulk_' . $batch_id, [
            'name' => $batch_name,
            'urls' => $urls,
            'status' => 'pending',
            'created' => current_time( 'mysql' )
        ], DAY_IN_SECONDS );

        wp_send_json_success( [
            'batch_id' => $batch_id,
            'message' => __( 'Bulk processing started successfully.', 'hellaz-sitez-analyzer' )
        ] );
    }

    /**
     * Plugin activation hook
     */
    public static function activate() {
        // Create necessary database tables
        self::create_tables();

        // Set default options
        self::set_default_options();

        // Create upload directories
        self::create_upload_directories();

        // Flush rewrite rules if needed
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation hook
     */
    public static function deactivate() {
        // Clean up scheduled events
        wp_clear_scheduled_hook( 'hsz_cleanup_cache' );

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create necessary database tables
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Example table creation (implement based on your needs)
        $table_name = $wpdb->prefix . 'hsz_analysis_cache';

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            url varchar(500) NOT NULL,
            analysis_data longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY url (url),
            KEY expires_at (expires_at)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        add_option( 'hsz_template_mode', 'classic' );
        add_option( 'hsz_cache_duration', 24 );
        add_option( 'hsz_performance_analysis_enabled', true );
        add_option( 'hsz_security_analysis_enabled', true );
        add_option( 'hsz_preview_generation_enabled', true );
        add_option( 'hsz_grading_system_enabled', true );
    }

    /**
     * Create upload directories
     */
    private static function create_upload_directories() {
        $directories = [
            HSZ_UPLOAD_DIR,
            HSZ_UPLOAD_DIR . 'screenshots/',
            HSZ_UPLOAD_DIR . 'reports/',
            HSZ_UPLOAD_DIR . 'cache/',
            HSZ_UPLOAD_DIR . 'logs/'
        ];

        foreach ( $directories as $dir ) {
            if ( ! file_exists( $dir ) ) {
                wp_mkdir_p( $dir );
                
                // Add security files
                $htaccess_content = "Order deny,allow\nDeny from all\n";
                if ( $dir !== HSZ_UPLOAD_DIR . 'screenshots/' ) {
                    file_put_contents( $dir . '.htaccess', $htaccess_content );
                }
                
                // Add index.php for security
                file_put_contents( $dir . 'index.php', '<?php // Silence is golden' );
            }
        }
    }

    /**
     * Get component instance
     *
     * @param string $component Component name
     * @return mixed|null Component instance or null
     */
    public function get_component( $component ) {
        return $this->components[ $component ] ?? null;
    }

/**
 * Prevent cloning
 */
public function __clone() {
    throw new Exception( 'Cannot clone singleton instance' );
}

/**
 * Prevent unserialization
 */
public function __wakeup() {
    throw new Exception( 'Cannot unserialize singleton instance' );
}

}
