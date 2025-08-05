<?php
/**
 * Enhanced Admin functionality for HellaZ SiteZ Analyzer
 *
 * Handles comprehensive admin interface including settings pages, API management,
 * performance settings, security configurations, and contact extraction options.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.0.0
 */

namespace HSZ;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Admin
 *
 * Manages all admin functionality including settings, pages, and AJAX handlers.
 */
class Admin {

    /**
     * Settings group identifier
     *
     * @var string
     */
    private $settings_group = 'hsz_settings_group';

    /**
     * Main page slug
     *
     * @var string
     */
    private $page_slug = 'hellaz-sitez-analyzer-settings';

    /**
     * Constructor - Initialize admin functionality
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_init', [ $this, 'handle_admin_actions' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
        
        // AJAX handlers
        add_action( 'wp_ajax_hsz_test_api', [ $this, 'ajax_test_api' ] );
        add_action( 'wp_ajax_hsz_clear_cache', [ $this, 'ajax_clear_cache' ] );
        add_action( 'wp_ajax_hsz_reset_settings', [ $this, 'ajax_reset_settings' ] );
    }

    /**
     * Add admin menu items
     */
    public function add_admin_menu(): void {
        // Main settings page
        add_menu_page(
            __( 'HellaZ SiteZ Analyzer', 'hellaz-sitez-analyzer' ),
            __( 'SiteZ Analyzer', 'hellaz-sitez-analyzer' ),
            'manage_options',
            $this->page_slug,
            [ $this, 'render_settings_page' ],
            'dashicons-analytics',
            85
        );

        // Add submenu pages for enhanced features
        add_submenu_page(
            $this->page_slug,
            __( 'Performance Settings', 'hellaz-sitez-analyzer' ),
            __( 'Performance', 'hellaz-sitez-analyzer' ),
            'manage_options',
            $this->page_slug . '-performance',
            [ $this, 'render_performance_page' ]
        );

        add_submenu_page(
            $this->page_slug,
            __( 'Security Settings', 'hellaz-sitez-analyzer' ),
            __( 'Security', 'hellaz-sitez-analyzer' ),
            'manage_options',
            $this->page_slug . '-security',
            [ $this, 'render_security_page' ]
        );

        add_submenu_page(
            $this->page_slug,
            __( 'Preview Settings', 'hellaz-sitez-analyzer' ),
            __( 'Previews', 'hellaz-sitez-analyzer' ),
            'manage_options',
            $this->page_slug . '-previews',
            [ $this, 'render_previews_page' ]
        );

        add_submenu_page(
            $this->page_slug,
            __( 'Contact Settings', 'hellaz-sitez-analyzer' ),
            __( 'Contact Info', 'hellaz-sitez-analyzer' ),
            'manage_options',
            $this->page_slug . '-contact',
            [ $this, 'render_contact_page' ]
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( strpos( $hook, 'hellaz-sitez-analyzer' ) === false ) {
            return;
        }

        wp_enqueue_style( 'hsz-admin-enhanced', HSZ_ASSETS_URL . 'css/hsz-admin.css', [], HSZ_VERSION );
        wp_enqueue_script( 'hsz-admin-enhanced', HSZ_ASSETS_URL . 'js/hsz-admin.js', [ 'jquery', 'wp-util' ], HSZ_VERSION, true );

        wp_localize_script( 'hsz-admin-enhanced', 'hszAdminEnhanced', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'hsz_admin_enhanced_nonce' ),
            'i18n' => [
                'testing_api' => __( 'Testing API connection...', 'hellaz-sitez-analyzer' ),
                'api_success' => __( 'API connection successful!', 'hellaz-sitez-analyzer' ),
                'api_failed' => __( 'API connection failed. Please check your settings.', 'hellaz-sitez-analyzer' ),
                'clearing_cache' => __( 'Clearing cache...', 'hellaz-sitez-analyzer' ),
                'cache_cleared' => __( 'Cache cleared successfully!', 'hellaz-sitez-analyzer' ),
                'confirm_reset' => __( 'Are you sure you want to reset all settings to defaults?', 'hellaz-sitez-analyzer' )
            ]
        ]);
    }

    /**
     * Handles the POST request to clear the plugin's cache.
     */
    public function handle_cache_clearing(): void {
        if ( isset( $_POST['hsz_action'] ) && $_POST['hsz_action'] === 'clear_cache' ) {
            if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'hsz_clear_cache_nonce' ) ) {
                wp_die( __( 'Security check failed. Please try again.', 'hellaz-sitez-analyzer' ) );
            }

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( __( 'You do not have permission to perform this action.', 'hellaz-sitez-analyzer' ) );
            }

            $deleted_rows = Cache::clear_all_hsz_transients();

            // Also clear enhanced cache tables
            if ( class_exists( 'HSZ\\Database' ) ) {
                $deleted_rows += Database::cleanup_expired_cache();
            }

            add_action( 'admin_notices', function () use ( $deleted_rows ) {
                $message = sprintf(
                    _n(
                        '%d cache entry was successfully deleted.',
                        '%d cache entries were successfully deleted.',
                        $deleted_rows,
                        'hellaz-sitez-analyzer'
                    ),
                    $deleted_rows
                );
                printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $message ) );
            });
        }
    }

    /**
     * Register all plugin settings
     */
    public function register_settings(): void {
        // General Settings
        register_setting( $this->settings_group, 'hsz_fallback_image', [ 'sanitize_callback' => [ 'HSZ\\Utils', 'sanitize_and_encrypt' ] ] );
        register_setting( $this->settings_group, 'hsz_fallback_title', [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( $this->settings_group, 'hsz_fallback_description', [ 'sanitize_callback' => 'sanitize_textarea_field' ] );
        register_setting( $this->settings_group, 'hsz_disclaimer_enabled', [ 'sanitize_callback' => 'absint' ] );
        register_setting( $this->settings_group, 'hsz_disclaimer_message', [ 'sanitize_callback' => 'wp_kses_post' ] );
        register_setting( $this->settings_group, 'hsz_auto_analyze_content', [ 'sanitize_callback' => 'absint' ] );

        // API Keys and Toggles
        $apis = [ 'virustotal', 'builtwith', 'urlscan', 'pagespeed', 'webpagetest' ];
        foreach ( $apis as $api ) {
            register_setting( $this->settings_group, "hsz_{$api}_enabled", [ 'sanitize_callback' => 'absint' ] );
            register_setting( $this->settings_group, "hsz_{$api}_api_key", [ 'sanitize_callback' => [ 'HSZ\\Utils', 'sanitize_and_encrypt' ] ] );
        }

        // Template Settings
        register_setting( $this->settings_group, 'hsz_template_mode', [ 'sanitize_callback' => 'sanitize_key' ] );
        register_setting( $this->settings_group, 'hsz_custom_css', [ 'sanitize_callback' => 'wp_strip_all_tags' ] );
        register_setting( $this->settings_group, 'hsz_show_powered_by', [ 'sanitize_callback' => 'absint' ] );

        // Cache Settings
        register_setting( $this->settings_group, 'hsz_cache_duration', [ 'sanitize_callback' => 'absint' ] );
        register_setting( $this->settings_group, 'hsz_cache_debug', [ 'sanitize_callback' => 'absint' ] );
        register_setting( $this->settings_group, 'hsz_cache_compression', [ 'sanitize_callback' => 'absint' ] );

        // Performance Settings
        register_setting( $this->settings_group, 'hsz_performance_analysis_enabled', [ 'sanitize_callback' => 'absint' ] );
        register_setting( $this->settings_group, 'hsz_performance_threshold_good', [ 'sanitize_callback' => 'absint' ] );
        register_setting( $this->settings_group, 'hsz_performance_threshold_poor', [ 'sanitize_callback' => 'absint' ] );
        register_setting( $this->settings_group, 'hsz_performance_include_mobile', [ 'sanitize_callback' => 'absint' ] );

        // Security Settings
        register_setting( $this->settings_group, 'hsz_security_analysis_enabled', [ 'sanitize_callback' => 'absint' ] );
        register_setting( $this->settings_group, 'hsz_ssl_analysis_enabled', [ 'sanitize_callback' => 'absint' ] );
        register_setting( $this->settings_group, 'hsz_security_scan_depth', [ 'sanitize_callback' => 'sanitize_key' ] );

        // Preview Settings
        register_setting( $this->settings_group, 'hsz_preview_generation_enabled', [ 'sanitize_callback' => 'absint' ] );
        register_setting( $this->settings_group, 'hsz_preview_width', [ 'sanitize_callback' => 'absint' ] );
        register_setting( $this->settings_group, 'hsz_preview_height', [ 'sanitize_callback' => 'absint' ] );
        register_setting( $this->settings_group, 'hsz_preview_quality', [ 'sanitize_callback' => 'absint' ] );

        // Contact Information Settings - NEW
        register_setting( $this->settings_group, 'hsz_contact_extract_emails', [ 'sanitize_callback' => 'absint' ] );
        register_setting( $this->settings_group, 'hsz_contact_extract_phones', [ 'sanitize_callback' => 'absint' ] );
        register_setting( $this->settings_group, 'hsz_contact_extract_addresses', [ 'sanitize_callback' => 'absint' ] );
        register_setting( $this->settings_group, 'hsz_contact_extract_forms', [ 'sanitize_callback' => 'absint' ] );
        register_setting( $this->settings_group, 'hsz_contact_extract_social', [ 'sanitize_callback' => 'absint' ] );
        register_setting( $this->settings_group, 'hsz_contact_extract_hours', [ 'sanitize_callback' => 'absint' ] );
        register_setting( $this->settings_group, 'hsz_contact_validate', [ 'sanitize_callback' => 'absint' ] );
        register_setting( $this->settings_group, 'hsz_contact_deep_analysis', [ 'sanitize_callback' => 'absint' ] );

        // Grading System Settings
        register_setting( $this->settings_group, 'hsz_grading_system_enabled', [ 'sanitize_callback' => 'absint' ] );
        register_setting( $this->settings_group, 'hsz_grading_weights', [ 'sanitize_callback' => [ $this, 'sanitize_grading_weights' ] ] );

        // Advanced Settings
        register_setting( $this->settings_group, 'hsz_api_timeout', [ 'sanitize_callback' => 'absint' ] );
        register_setting( $this->settings_group, 'hsz_disable_ssl_verify', [ 'sanitize_callback' => 'absint' ] );
        register_setting( $this->settings_group, 'hsz_rate_limit_requests', [ 'sanitize_callback' => 'absint' ] );
    }

    /**
     * Sanitize grading weights
     *
     * @param array $weights Grading weights
     * @return array Sanitized weights
     */
    public function sanitize_grading_weights( $weights ): array {
        if ( ! is_array( $weights ) ) {
            return [];
        }

        $sanitized = [];
        $allowed_keys = [ 'metadata', 'social', 'contact', 'performance', 'security' ];

        foreach ( $allowed_keys as $key ) {
            if ( isset( $weights[$key] ) ) {
                $sanitized[$key] = max( 0, min( 100, absint( $weights[$key] ) ) );
            }
        }

        return $sanitized;
    }

    /**
     * Display section description
     */
    public function display_section_description(): void {
        echo '<p>' . esc_html__( 'Choose how analysis results are displayed to your users. These settings affect the default appearance of shortcodes, widgets, and blocks.', 'hellaz-sitez-analyzer' ) . '</p>';
    }

    /**
     * Template mode selection callback with visual previews
     */
    public function template_mode_callback() {
        $template_mode = get_option( 'hsz_template_mode', 'classic' );
        ?>
        <fieldset>
            <legend class="screen-reader-text"><?php esc_html_e( 'Template Mode', 'hellaz-sitez-analyzer' ); ?></legend>
            <div class="hsz-template-modes">
                <label>
                    <input type="radio" name="hsz_template_mode" value="classic" <?php checked( $template_mode, 'classic' ); ?> />
                    <span class="template-preview classic">
                        <strong><?php esc_html_e( 'Classic', 'hellaz-sitez-analyzer' ); ?></strong>
                        <small><?php esc_html_e( 'Traditional layout with full details', 'hellaz-sitez-analyzer' ); ?></small>
                    </span>
                </label>
                <label>
                    <input type="radio" name="hsz_template_mode" value="modern" <?php checked( $template_mode, 'modern' ); ?> />
                    <span class="template-preview modern">
                        <strong><?php esc_html_e( 'Modern', 'hellaz-sitez-analyzer' ); ?></strong>
                        <small><?php esc_html_e( 'Clean, card-based design', 'hellaz-sitez-analyzer' ); ?></small>
                    </span>
                </label>
                <label>
                    <input type="radio" name="hsz_template_mode" value="compact" <?php checked( $template_mode, 'compact' ); ?> />
                    <span class="template-preview compact">
                        <strong><?php esc_html_e( 'Compact', 'hellaz-sitez-analyzer' ); ?></strong>
                        <small><?php esc_html_e( 'Minimal space usage', 'hellaz-sitez-analyzer' ); ?></small>
                    </span>
                </label>
            </div>
        </fieldset>
        <?php
    }

    /**
     * API Keys section description
     */
    public function api_keys_section_description(): void {
        echo '<p>' . esc_html__( 'Configure third-party API keys for enhanced analysis capabilities.', 'hellaz-sitez-analyzer' ) . '</p>';
        echo '<div class="hsz-api-status-grid">';
        
        $apis = [
            'virustotal' => __( 'VirusTotal', 'hellaz-sitez-analyzer' ),
            'builtwith' => __( 'BuiltWith', 'hellaz-sitez-analyzer' ),
            'urlscan' => __( 'URLScan.io', 'hellaz-sitez-analyzer' )
        ];

        foreach ( $apis as $api => $name ) {
            $enabled = get_option( "hsz_{$api}_enabled" );
            $has_key = ! empty( get_option( "hsz_{$api}_api_key" ) );
            $status_class = $enabled && $has_key ? 'enabled' : 'disabled';
            echo '<div class="api-status-card ' . esc_attr( $status_class ) . '">';
            echo '<h4>' . esc_html( $name ) . '</h4>';
            echo '<span class="status">' . ( $enabled && $has_key ? '✓ ' . esc_html__( 'Active', 'hellaz-sitez-analyzer' ) : '○ ' . esc_html__( 'Inactive', 'hellaz-sitez-analyzer' ) ) . '</span>';
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * Cache settings section description
     */
    public function cache_section_description(): void {
        echo '<p>' . esc_html__( 'Configure caching settings to optimize performance and reduce API usage.', 'hellaz-sitez-analyzer' ) . '</p>';
    }

    /**
     * Cache duration callback
     */
    public function cache_duration_callback() {
        $cache_duration = get_option( 'hsz_cache_duration', 24 );
        ?>
        <select name="hsz_cache_duration">
            <option value="1" <?php selected( $cache_duration, 1 ); ?>><?php esc_html_e( '1 hour', 'hellaz-sitez-analyzer' ); ?></option>
            <option value="6" <?php selected( $cache_duration, 6 ); ?>><?php esc_html_e( '6 hours', 'hellaz-sitez-analyzer' ); ?></option>
            <option value="12" <?php selected( $cache_duration, 12 ); ?>><?php esc_html_e( '12 hours', 'hellaz-sitez-analyzer' ); ?></option>
            <option value="24" <?php selected( $cache_duration, 24 ); ?>><?php esc_html_e( '24 hours', 'hellaz-sitez-analyzer' ); ?></option>
            <option value="48" <?php selected( $cache_duration, 48 ); ?>><?php esc_html_e( '48 hours', 'hellaz-sitez-analyzer' ); ?></option>
            <option value="168" <?php selected( $cache_duration, 168 ); ?>><?php esc_html_e( '1 week', 'hellaz-sitez-analyzer' ); ?></option>
        </select>
        <p class="description"><?php esc_html_e( 'How long to cache analysis results before refreshing.', 'hellaz-sitez-analyzer' ); ?></p>
        <?php
    }

    /**
     * Handle admin actions
     */
    public function handle_admin_actions(): void {
        if ( ! isset( $_POST['hsz_action'] ) || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $action = sanitize_key( $_POST['hsz_action'] );

        switch ( $action ) {
            case 'clear_cache':
                $this->handle_cache_clearing();
                break;
            case 'reset_settings':
                $this->handle_settings_reset();
                break;
        }
    }

    /**
     * Ajax handler for testing API connections
     */
    public function ajax_test_api() {
        check_ajax_referer( 'hsz_admin_enhanced_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied', 'hellaz-sitez-analyzer' ) );
        }

        $api_type = sanitize_text_field( $_POST['api_type'] ?? '' );
        $api_key = sanitize_text_field( $_POST['api_key'] ?? '' );

        if ( empty( $api_key ) ) {
            wp_send_json_error( __( 'API key is required', 'hellaz-sitez-analyzer' ) );
        }

        $result = $this->test_api_connection( $api_type, $api_key );

        if ( $result['success'] ) {
            wp_send_json_success( $result['message'] );
        } else {
            wp_send_json_error( $result['message'] );
        }
    }

    /**
     * Test API connection
     */
    private function test_api_connection( $api_type, $api_key ) {
        switch ( $api_type ) {
            case 'virustotal':
                return $this->test_virustotal_api( $api_key );
            case 'builtwith':
                return $this->test_builtwith_api( $api_key );
            case 'urlscan':
                return $this->test_urlscan_api( $api_key );
            default:
                return [
                    'success' => false,
                    'message' => __( 'Unknown API type', 'hellaz-sitez-analyzer' )
                ];
        }
    }

    /**
     * Test VirusTotal API
     */
    private function test_virustotal_api( $api_key ) {
        $response = wp_remote_get( 'https://www.virustotal.com/vtapi/v2/url/report', [
            'headers' => [
                'apikey' => $api_key
            ],
            'body' => [
                'resource' => 'google.com'
            ],
            'timeout' => 10
        ]);

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => __( 'Connection failed: ', 'hellaz-sitez-analyzer' ) . $response->get_error_message()
            ];
        }

        $status_code = wp_remote_retrieve_response_code( $response );

        if ( $status_code === 200 ) {
            return [
                'success' => true,
                'message' => __( 'VirusTotal API connection successful!', 'hellaz-sitez-analyzer' )
            ];
        } else {
            return [
                'success' => false,
                'message' => sprintf( __( 'API returned status code: %d', 'hellaz-sitez-analyzer' ), $status_code )
            ];
        }
    }

    /**
     * Test BuiltWith API
     */
    private function test_builtwith_api( $api_key ) {
        $response = wp_remote_get( 'https://api.builtwith.com/free1/api.json', [
            'body' => [
                'KEY' => $api_key,
                'LOOKUP' => 'google.com'
            ],
            'timeout' => 10
        ]);

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => __( 'Connection failed: ', 'hellaz-sitez-analyzer' ) . $response->get_error_message()
            ];
        }

        $status_code = wp_remote_retrieve_response_code( $response );

        if ( $status_code === 200 ) {
            return [
                'success' => true,
                'message' => __( 'BuiltWith API connection successful!', 'hellaz-sitez-analyzer' )
            ];
        } else {
            return [
                'success' => false,
                'message' => sprintf( __( 'API returned status code: %d', 'hellaz-sitez-analyzer' ), $status_code )
            ];
        }
    }

    /**
     * Test URLScan.io API
     */
    private function test_urlscan_api( $api_key ) {
        $response = wp_remote_get( 'https://urlscan.io/api/v1/search/', [
            'headers' => [
                'API-Key' => $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'q' => 'domain:google.com',
                'size' => 1
            ]),
            'timeout' => 10
        ]);

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => __( 'Connection failed: ', 'hellaz-sitez-analyzer' ) . $response->get_error_message()
            ];
        }

        $status_code = wp_remote_retrieve_response_code( $response );

        if ( $status_code === 200 ) {
            return [
                'success' => true,
                'message' => __( 'URLScan.io API connection successful!', 'hellaz-sitez-analyzer' )
            ];
        } else {
            return [
                'success' => false,
                'message' => sprintf( __( 'API returned status code: %d', 'hellaz-sitez-analyzer' ), $status_code )
            ];
        }
    }

    /**
     * Ajax handler for clearing cache
     */
    public function ajax_clear_cache() {
        check_ajax_referer( 'hsz_admin_enhanced_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied', 'hellaz-sitez-analyzer' ) );
        }

        $deleted_rows = Cache::clear_all_hsz_transients();

        // Also clear enhanced cache tables
        if ( class_exists( 'HSZ\\Database' ) ) {
            $deleted_rows += Database::cleanup_expired_cache();
        }

        $message = sprintf(
            _n(
                '%d cache entry was successfully deleted.',
                '%d cache entries were successfully deleted.',
                $deleted_rows,
                'hellaz-sitez-analyzer'
            ),
            $deleted_rows
        );

        wp_send_json_success( $message );
    }

    /**
     * Ajax handler for resetting settings
     */
    public function ajax_reset_settings() {
        check_ajax_referer( 'hsz_admin_enhanced_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied', 'hellaz-sitez-analyzer' ) );
        }

        // Reset all plugin settings to defaults
        $settings_to_reset = [
            'hsz_virustotal_api_key', 'hsz_builtwith_api_key', 'hsz_urlscan_api_key',
            'hsz_cache_duration', 'hsz_template_mode', 'hsz_performance_analysis_enabled',
            'hsz_security_analysis_enabled', 'hsz_preview_generation_enabled',
            'hsz_contact_extract_emails', 'hsz_contact_extract_phones',
            'hsz_contact_extract_addresses', 'hsz_contact_extract_forms'
        ];

        foreach ( $settings_to_reset as $setting ) {
            delete_option( $setting );
        }

        wp_send_json_success( __( 'All settings have been reset to defaults.', 'hellaz-sitez-analyzer' ) );
    }

    /**
     * Handle settings reset
     */
    private function handle_settings_reset(): void {
        if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'hsz_reset_settings_nonce' ) ) {
            wp_die( __( 'Security check failed.', 'hellaz-sitez-analyzer' ) );
        }

        // Reset logic here (same as AJAX handler)
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings have been reset to defaults.', 'hellaz-sitez-analyzer' ) . '</p></div>';
        });
    }

    /**
     * Render main settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap hsz-admin-wrap">
            <h1><?php esc_html_e( 'HellaZ SiteZ Analyzer Settings', 'hellaz-sitez-analyzer' ); ?></h1>
            
            <div class="hsz-admin-header">
                <div class="hsz-version-info">
                    <span class="version"><?php echo esc_html( 'Version ' . HSZ_VERSION ); ?></span>
                    <span class="separator">|</span>
                    <a href="https://hellaz.net" target="_blank"><?php esc_html_e( 'Support', 'hellaz-sitez-analyzer' ); ?></a>
                </div>
            </div>

            <div class="hsz-admin-content">
                <form method="post" action="options.php" class="hsz-settings-form">
                    <?php
                    settings_fields( $this->settings_group );
                    do_settings_sections( $this->settings_group );
                    ?>

                    <div class="hsz-settings-sections">
                        <!-- General Settings -->
                        <div class="hsz-settings-section">
                            <h2><?php esc_html_e( 'General Settings', 'hellaz-sitez-analyzer' ); ?></h2>
                            <?php $this->render_general_settings(); ?>
                        </div>

                        <!-- Template Settings -->
                        <div class="hsz-settings-section">
                            <h2><?php esc_html_e( 'Display Templates', 'hellaz-sitez-analyzer' ); ?></h2>
                            <?php $this->render_template_settings(); ?>
                        </div>

                        <!-- API Settings -->
                        <div class="hsz-settings-section">
                            <h2><?php esc_html_e( 'API Configuration', 'hellaz-sitez-analyzer' ); ?></h2>
                            <?php $this->render_api_settings(); ?>
                        </div>

                        <!-- Contact Settings -->
                        <div class="hsz-settings-section">
                            <h2><?php esc_html_e( 'Contact Information Extraction', 'hellaz-sitez-analyzer' ); ?></h2>
                            <?php $this->render_contact_settings(); ?>
                        </div>

                        <!-- Cache Settings -->
                        <div class="hsz-settings-section">
                            <h2><?php esc_html_e( 'Cache Settings', 'hellaz-sitez-analyzer' ); ?></h2>
                            <?php $this->render_cache_settings(); ?>
                        </div>
                    </div>

                    <?php submit_button( __( 'Save All Settings', 'hellaz-sitez-analyzer' ), 'primary large' ); ?>
                </form>

                <!-- Quick Actions -->
                <div class="hsz-quick-actions">
                    <h3><?php esc_html_e( 'Quick Actions', 'hellaz-sitez-analyzer' ); ?></h3>
                    <div class="hsz-action-buttons">
                        <button type="button" id="hsz-clear-cache" class="button"><?php esc_html_e( 'Clear Cache', 'hellaz-sitez-analyzer' ); ?></button>
                        <button type="button" id="hsz-reset-settings" class="button button-secondary"><?php esc_html_e( 'Reset Settings', 'hellaz-sitez-analyzer' ); ?></button>
                    </div>
                </div>

                <!-- Usage Examples -->
                <div class="hsz-usage-examples">
                    <h3><?php esc_html_e( 'Usage Examples', 'hellaz-sitez-analyzer' ); ?></h3>
                    <div class="usage-grid">
                        <div class="usage-card">
                            <h4><?php esc_html_e( 'Basic Shortcode', 'hellaz-sitez-analyzer' ); ?></h4>
                            <code>[hsz_analyzer url="https://example.com"]</code>
                        </div>
                        <div class="usage-card">
                            <h4><?php esc_html_e( 'Compact Display', 'hellaz-sitez-analyzer' ); ?></h4>
                            <code>[hsz_analyzer url="https://example.com" display_type="compact"]</code>
                        </div>
                    </div>
                </div>

                <!-- System Status -->
                <div class="hsz-system-status">
                    <h3><?php esc_html_e( 'System Status', 'hellaz-sitez-analyzer' ); ?></h3>
                    <table class="widefat">
                        <tbody>
                            <tr>
                                <td><?php esc_html_e( 'PHP Version', 'hellaz-sitez-analyzer' ); ?></td>
                                <td><?php echo version_compare( PHP_VERSION, '7.4', '>=' ) ? '<span class="hsz-status-good">✓ ' . esc_html__( 'Available', 'hellaz-sitez-analyzer' ) . '</span>' : '<span class="hsz-status-bad">✗ ' . esc_html__( 'Not Available', 'hellaz-sitez-analyzer' ) . '</span>'; ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e( 'cURL Extension', 'hellaz-sitez-analyzer' ); ?></td>
                                <td><?php echo extension_loaded( 'curl' ) ? '<span class="hsz-status-good">✓ ' . esc_html__( 'Available', 'hellaz-sitez-analyzer' ) . '</span>' : '<span class="hsz-status-bad">✗ ' . esc_html__( 'Not Available', 'hellaz-sitez-analyzer' ) . '</span>'; ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e( 'JSON Extension', 'hellaz-sitez-analyzer' ); ?></td>
                                <td><?php echo extension_loaded( 'json' ) ? '<span class="hsz-status-good">✓ ' . esc_html__( 'Available', 'hellaz-sitez-analyzer' ) . '</span>' : '<span class="hsz-status-bad">✗ ' . esc_html__( 'Not Available', 'hellaz-sitez-analyzer' ) . '</span>'; ?></td>
                            </tr>
                            <tr>
                                <td><?php esc_html_e( 'Upload Directory', 'hellaz-sitez-analyzer' ); ?></td>
                                <td><?php echo is_writable( HSZ_UPLOAD_DIR ) ? '<span class="hsz-status-good">✓ ' . esc_html__( 'Writable', 'hellaz-sitez-analyzer' ) . '</span>' : '<span class="hsz-status-bad">✗ ' . esc_html__( 'Not Writable', 'hellaz-sitez-analyzer' ) . '</span>'; ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render general settings section
     */
    private function render_general_settings(): void {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Fallback Image URL', 'hellaz-sitez-analyzer' ); ?></th>
                <td>
                    <?php
                    $encrypted_url = get_option( 'hsz_fallback_image' );
                    $decrypted_url = ( $encrypted_url && is_string( $encrypted_url ) ) ? Utils::decrypt( $encrypted_url ) : '';
                    if ( false === $decrypted_url ) {
                        $decrypted_url = $encrypted_url;
                    }
                    ?>
                    <input type="url" name="hsz_fallback_image" value="<?php echo esc_attr( $decrypted_url ); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e( 'Default image when no favicon or preview is available.', 'hellaz-sitez-analyzer' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Fallback Title', 'hellaz-sitez-analyzer' ); ?></th>
                <td>
                    <input type="text" name="hsz_fallback_title" value="<?php echo esc_attr( get_option( 'hsz_fallback_title', '' ) ); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e( 'Default title when page title cannot be extracted.', 'hellaz-sitez-analyzer' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Auto-Analyze Content', 'hellaz-sitez-analyzer' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="hsz_auto_analyze_content" value="1" <?php checked( get_option( 'hsz_auto_analyze_content', 0 ) ); ?> />
                        <?php esc_html_e( 'Automatically analyze external links in post content', 'hellaz-sitez-analyzer' ); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render template settings section
     */
    private function render_template_settings(): void {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Template Mode', 'hellaz-sitez-analyzer' ); ?></th>
                <td><?php $this->template_mode_callback(); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Show Powered By', 'hellaz-sitez-analyzer' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="hsz_show_powered_by" value="1" <?php checked( get_option( 'hsz_show_powered_by', 1 ) ); ?> />
                        <?php esc_html_e( 'Display "Powered by HellaZ SiteZ Analyzer" link', 'hellaz-sitez-analyzer' ); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render API settings section
     */
    private function render_api_settings(): void {
        ?>
        <div class="hsz-api-settings">
            <?php $this->api_keys_section_description(); ?>
            
            <table class="form-table">
                <?php
                $apis = [
                    'virustotal' => 'VirusTotal',
                    'builtwith' => 'BuiltWith',
                    'urlscan' => 'URLScan.io'
                ];
                
                foreach ( $apis as $slug => $name ) :
                    $enabled_option = "hsz_{$slug}_enabled";
                    $key_option = "hsz_{$slug}_api_key";
                    $encrypted_key = get_option( $key_option, '' );
                    $decrypted_key = ( $encrypted_key && is_string( $encrypted_key ) ) ? Utils::decrypt( $encrypted_key ) : '';
                    if ( false === $decrypted_key ) {
                        $decrypted_key = $encrypted_key;
                    }
                ?>
                <tr>
                    <th scope="row"><?php echo esc_html( $name ); ?></th>
                    <td class="hsz-api-field">
                        <div class="api-toggle">
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( $enabled_option ); ?>" value="1" <?php checked( get_option( $enabled_option ) ); ?> />
                                <?php esc_html_e( 'Enable', 'hellaz-sitez-analyzer' ); ?>
                            </label>
                        </div>
                        <div class="api-key-field">
                            <input type="password" name="<?php echo esc_attr( $key_option ); ?>" value="<?php echo esc_attr( $decrypted_key ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Enter API key...', 'hellaz-sitez-analyzer' ); ?>" />
                            <button type="button" class="button test-api-btn" data-api="<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Test', 'hellaz-sitez-analyzer' ); ?></button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php
    }

    /**
     * Render contact settings section - NEW
     */
    private function render_contact_settings(): void {
        ?>
        <div class="hsz-contact-settings">
            <p class="description"><?php esc_html_e( 'Configure which contact information to extract from analyzed websites.', 'hellaz-sitez-analyzer' ); ?></p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Email Extraction', 'hellaz-sitez-analyzer' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="hsz_contact_extract_emails" value="1" <?php checked( get_option( 'hsz_contact_extract_emails', 1 ) ); ?> />
                            <?php esc_html_e( 'Extract email addresses from content and mailto links', 'hellaz-sitez-analyzer' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Phone Number Extraction', 'hellaz-sitez-analyzer' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="hsz_contact_extract_phones" value="1" <?php checked( get_option( 'hsz_contact_extract_phones', 1 ) ); ?> />
                            <?php esc_html_e( 'Extract phone numbers from content and tel links', 'hellaz-sitez-analyzer' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Address Extraction', 'hellaz-sitez-analyzer' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="hsz_contact_extract_addresses" value="1" <?php checked( get_option( 'hsz_contact_extract_addresses', 1 ) ); ?> />
                            <?php esc_html_e( 'Extract physical addresses and location information', 'hellaz-sitez-analyzer' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Contact Form Detection', 'hellaz-sitez-analyzer' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="hsz_contact_extract_forms" value="1" <?php checked( get_option( 'hsz_contact_extract_forms', 1 ) ); ?> />
                            <?php esc_html_e( 'Detect and analyze contact forms on the website', 'hellaz-sitez-analyzer' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Social Contact Info', 'hellaz-sitez-analyzer' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="hsz_contact_extract_social" value="1" <?php checked( get_option( 'hsz_contact_extract_social', 1 ) ); ?> />
                            <?php esc_html_e( 'Extract social media profiles for contact purposes', 'hellaz-sitez-analyzer' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Business Hours', 'hellaz-sitez-analyzer' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="hsz_contact_extract_hours" value="1" <?php checked( get_option( 'hsz_contact_extract_hours', 1 ) ); ?> />
                            <?php esc_html_e( 'Extract business hours and operating times', 'hellaz-sitez-analyzer' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Validation Options', 'hellaz-sitez-analyzer' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="hsz_contact_validate" value="1" <?php checked( get_option( 'hsz_contact_validate', 0 ) ); ?> />
                            <?php esc_html_e( 'Validate contact information (may slow down analysis)', 'hellaz-sitez-analyzer' ); ?>
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" name="hsz_contact_deep_analysis" value="1" <?php checked( get_option( 'hsz_contact_deep_analysis', 0 ) ); ?> />
                            <?php esc_html_e( 'Enable deep analysis for better accuracy', 'hellaz-sitez-analyzer' ); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Render cache settings section
     */
    private function render_cache_settings(): void {
        ?>
        <div class="hsz-cache-settings">
            <?php $this->cache_section_description(); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Cache Duration', 'hellaz-sitez-analyzer' ); ?></th>
                    <td><?php $this->cache_duration_callback(); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Cache Debug', 'hellaz-sitez-analyzer' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="hsz_cache_debug" value="1" <?php checked( get_option( 'hsz_cache_debug', 0 ) ); ?> />
                            <?php esc_html_e( 'Add debug comments to output showing cache status', 'hellaz-sitez-analyzer' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Cache Compression', 'hellaz-sitez-analyzer' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="hsz_cache_compression" value="1" <?php checked( get_option( 'hsz_cache_compression', 1 ) ); ?> />
                            <?php esc_html_e( 'Compress cached data to save database space', 'hellaz-sitez-analyzer' ); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Render performance page
     */
    public function render_performance_page(): void {
        ?>
        <div class="wrap hsz-admin-wrap">
            <h1><?php esc_html_e( 'Performance Settings', 'hellaz-sitez-analyzer' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( $this->settings_group );
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Performance Analysis', 'hellaz-sitez-analyzer' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="hsz_performance_analysis_enabled" value="1" <?php checked( get_option( 'hsz_performance_analysis_enabled', 1 ) ); ?> />
                                <?php esc_html_e( 'Enable performance analysis for analyzed websites', 'hellaz-sitez-analyzer' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Include Mobile Analysis', 'hellaz-sitez-analyzer' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="hsz_performance_include_mobile" value="1" <?php checked( get_option( 'hsz_performance_include_mobile', 1 ) ); ?> />
                                <?php esc_html_e( 'Include mobile performance metrics', 'hellaz-sitez-analyzer' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render security page
     */
    public function render_security_page(): void {
        ?>
        <div class="wrap hsz-admin-wrap">
            <h1><?php esc_html_e( 'Security Settings', 'hellaz-sitez-analyzer' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( $this->settings_group );
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Security Analysis', 'hellaz-sitez-analyzer' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="hsz_security_analysis_enabled" value="1" <?php checked( get_option( 'hsz_security_analysis_enabled', 1 ) ); ?> />
                                <?php esc_html_e( 'Enable security analysis for analyzed websites', 'hellaz-sitez-analyzer' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'SSL Analysis', 'hellaz-sitez-analyzer' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="hsz_ssl_analysis_enabled" value="1" <?php checked( get_option( 'hsz_ssl_analysis_enabled', 1 ) ); ?> />
                                <?php esc_html_e( 'Analyze SSL certificates and security', 'hellaz-sitez-analyzer' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render previews page
     */
    public function render_previews_page(): void {
        ?>
        <div class="wrap hsz-admin-wrap">
            <h1><?php esc_html_e( 'Preview Settings', 'hellaz-sitez-analyzer' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( $this->settings_group );
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Preview Generation', 'hellaz-sitez-analyzer' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="hsz_preview_generation_enabled" value="1" <?php checked( get_option( 'hsz_preview_generation_enabled', 1 ) ); ?> />
                                <?php esc_html_e( 'Generate website previews/screenshots', 'hellaz-sitez-analyzer' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Preview Dimensions', 'hellaz-sitez-analyzer' ); ?></th>
                        <td>
                            <input type="number" name="hsz_preview_width" value="<?php echo esc_attr( get_option( 'hsz_preview_width', 1200 ) ); ?>" min="400" max="1920" /> 
                            × 
                            <input type="number" name="hsz_preview_height" value="<?php echo esc_attr( get_option( 'hsz_preview_height', 800 ) ); ?>" min="300" max="1200" />
                            <p class="description"><?php esc_html_e( 'Width × Height in pixels', 'hellaz-sitez-analyzer' ); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render contact page - NEW
     */
    public function render_contact_page(): void {
        ?>
        <div class="wrap hsz-admin-wrap">
            <h1><?php esc_html_e( 'Contact Information Settings', 'hellaz-sitez-analyzer' ); ?></h1>
            
            <div class="hsz-contact-info">
                <p><?php esc_html_e( 'Configure how the plugin extracts and displays contact information from analyzed websites.', 'hellaz-sitez-analyzer' ); ?></p>
            </div>

            <form method="post" action="options.php">
                <?php
                settings_fields( $this->settings_group );
                $this->render_contact_settings();
                submit_button();
                ?>
            </form>

            <div class="hsz-contact-preview">
                <h3><?php esc_html_e( 'Contact Information Preview', 'hellaz-sitez-analyzer' ); ?></h3>
                <p><?php esc_html_e( 'When enabled, extracted contact information will be displayed like this:', 'hellaz-sitez-analyzer' ); ?></p>
                
                <div class="contact-preview-example">
                    <div class="contact-item email">
                        <strong><?php esc_html_e( 'Email:', 'hellaz-sitez-analyzer' ); ?></strong> contact@example.com
                    </div>
                    <div class="contact-item phone">
                        <strong><?php esc_html_e( 'Phone:', 'hellaz-sitez-analyzer' ); ?></strong> (555) 123-4567
                    </div>
                    <div class="contact-item address">
                        <strong><?php esc_html_e( 'Address:', 'hellaz-sitez-analyzer' ); ?></strong> 123 Main St, City, ST 12345
                    </div>
                    <div class="contact-item hours">
                        <strong><?php esc_html_e( 'Hours:', 'hellaz-sitez-analyzer' ); ?></strong> Mon-Fri 9AM-5PM
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
