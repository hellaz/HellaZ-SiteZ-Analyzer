<?php
/**
 * Manages the plugin's settings page and all admin-facing functionality.
 *
 * This class is the central hub for the admin dashboard, creating the settings
 * page, registering all options, and rendering all fields and tabs.
 *
 * Enhanced with Phase 1 features: Performance Analysis, Security Analysis,
 * Preview Generation, and Grading System configuration.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.0.2
 */

namespace HSZ;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin {

    /**
     * Admin page slug
     *
     * @var string
     */
    private $page_slug = 'hellaz-sitez-analyzer';

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_init', [ $this, 'register_template_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
        add_action( 'admin_post_hsz_admin_action', [ $this, 'handle_admin_actions' ] );
        add_action( 'admin_init', [ $this, 'handle_cache_clearing' ] );
        add_action( 'wp_ajax_hsz_test_api', [ $this, 'ajax_test_api' ] );
        add_action( 'wp_ajax_hsz_clear_cache_ajax', [ $this, 'ajax_clear_cache' ] );
        add_action( 'wp_ajax_hsz_reset_settings', [ $this, 'ajax_reset_settings' ] );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu page
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
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( strpos( $hook, 'hellaz-sitez-analyzer' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'hsz-admin-enhanced',
            HSZ_ASSETS_URL . 'css/hsz-admin.css',
            [],
            HSZ_VERSION
        );

        wp_enqueue_script(
            'hsz-admin-enhanced',
            HSZ_ASSETS_URL . 'js/hsz-admin.js',
            [ 'jquery', 'wp-util' ],
            HSZ_VERSION,
            true
        );

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

                printf(
                    '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                    esc_html( $message )
                );
            });
        }
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // API Settings Section
        add_settings_section(
            'hsz_api_section',
            __( 'API Settings', 'hellaz-sitez-analyzer' ),
            [ $this, 'api_section_callback' ],
            'hsz_settings'
        );

        // VirusTotal API Key
        add_settings_field(
            'hsz_virustotal_api_key',
            __( 'VirusTotal API Key', 'hellaz-sitez-analyzer' ),
            [ $this, 'virustotal_api_key_callback' ],
            'hsz_settings',
            'hsz_api_section'
        );

        // BuiltWith API Key
        add_settings_field(
            'hsz_builtwith_api_key',
            __( 'BuiltWith API Key', 'hellaz-sitez-analyzer' ),
            [ $this, 'builtwith_api_key_callback' ],
            'hsz_settings',
            'hsz_api_section'
        );

        // URLScan.io API Key
        add_settings_field(
            'hsz_urlscan_api_key',
            __( 'URLScan.io API Key', 'hellaz-sitez-analyzer' ),
            [ $this, 'urlscan_api_key_callback' ],
            'hsz_settings',
            'hsz_api_section'
        );

        // Cache Settings Section
        add_settings_section(
            'hsz_cache_section',
            __( 'Cache Settings', 'hellaz-sitez-analyzer' ),
            [ $this, 'cache_section_callback' ],
            'hsz_settings'
        );

        // Cache Duration
        add_settings_field(
            'hsz_cache_duration',
            __( 'Cache Duration (hours)', 'hellaz-sitez-analyzer' ),
            [ $this, 'cache_duration_callback' ],
            'hsz_settings',
            'hsz_cache_section'
        );

        // Register all settings
        register_setting( 'hsz_settings', 'hsz_virustotal_api_key' );
        register_setting( 'hsz_settings', 'hsz_builtwith_api_key' );
        register_setting( 'hsz_settings', 'hsz_urlscan_api_key' );
        register_setting( 'hsz_settings', 'hsz_cache_duration' );
    }

    /**
     * Register template settings - Enhanced template selection
     */
    public function register_template_settings() {
        add_settings_section(
            'hsz_display_section',
            __( 'Display & Template Settings', 'hellaz-sitez-analyzer' ),
            [ $this, 'display_section_callback' ],
            'hsz_settings'
        );

        add_settings_field(
            'hsz_template_mode',
            __( 'Template Style', 'hellaz-sitez-analyzer' ),
            [ $this, 'template_mode_callback' ],
            'hsz_settings',
            'hsz_display_section'
        );

        register_setting( 'hsz_settings', 'hsz_template_mode' );
    }

    /**
     * Display section description
     */
    public function display_section_callback() {
        echo '<p>' . esc_html__( 'Choose how analysis results are displayed to your users. These settings affect the default appearance of shortcodes, widgets, and blocks.', 'hellaz-sitez-analyzer' ) . '</p>';
    }

    /**
     * Template mode selection callback with visual previews
     */
public function template_mode_callback() {
    $template_mode = get_option( 'hsz_template_mode', 'classic' );
    ?>
    <div class="hsz-template-selector">
        <div class="hsz-template-options">
            
            <!-- Classic Template Option -->
            <div class="hsz-template-option <?php echo $template_mode === 'classic' ? 'hsz-selected' : ''; ?>">
                <input type="radio" 
                       name="hsz_template_mode" 
                       value="classic" 
                       id="template_classic" 
                       <?php checked( $template_mode, 'classic' ); ?>>
                <label for="template_classic" class="hsz-template-label">
                    <div class="hsz-template-preview hsz-classic-preview">
                        <img src="<?php echo HSZ_ASSETS_URL . 'images/template-classic.png'; ?>" 
                             alt="<?php esc_attr_e( 'Classic Template Preview', 'hellaz-sitez-analyzer' ); ?>"
                             class="hsz-preview-image">
                    </div>
                    <div class="hsz-template-info">
                        <h4><?php esc_html_e( 'Classic Template', 'hellaz-sitez-analyzer' ); ?></h4>
                        <p><?php esc_html_e( 'Comprehensive analysis with organized, collapsible sections. Perfect for detailed reports and professional presentations.', 'hellaz-sitez-analyzer' ); ?></p>
                        <ul class="hsz-template-features">
                            <li>✅ <?php esc_html_e( 'Collapsible sections', 'hellaz-sitez-analyzer' ); ?></li>
                            <li>✅ <?php esc_html_e( 'Detailed analysis display', 'hellaz-sitez-analyzer' ); ?></li>
                            <li>✅ <?php esc_html_e( 'Mobile responsive', 'hellaz-sitez-analyzer' ); ?></li>
                            <li>✅ <?php esc_html_e( 'Fast loading', 'hellaz-sitez-analyzer' ); ?></li>
                        </ul>
                    </div>
                </label>
            </div>
            
            <!-- Compact Template Option -->
            <div class="hsz-template-option">
                <div class="hsz-template-preview hsz-compact-preview">
                    <img src="<?php echo HSZ_ASSETS_URL . 'images/template-compact.png'; ?>" 
                         alt="<?php esc_attr_e( 'Compact Template Preview', 'hellaz-sitez-analyzer' ); ?>"
                         class="hsz-preview-image">
                </div>
                <div class="hsz-template-info">
                    <h4><?php esc_html_e( 'Compact Template', 'hellaz-sitez-analyzer' ); ?></h4>
                    <p><?php esc_html_e( 'Essential metrics only, perfect for sidebars and small spaces.', 'hellaz-sitez-analyzer' ); ?></p>
                    <ul class="hsz-template-features">
                        <li>✅ <?php esc_html_e( 'Minimal space usage', 'hellaz-sitez-analyzer' ); ?></li>
                        <li>✅ <?php esc_html_e( 'Key metrics display', 'hellaz-sitez-analyzer' ); ?></li>
                        <li>✅ <?php esc_html_e( 'Sidebar friendly', 'hellaz-sitez-analyzer' ); ?></li>
                        <li>✅ <?php esc_html_e( 'Always available via parameter', 'hellaz-sitez-analyzer' ); ?></li>
                    </ul>
                </div>
            </div>
            
            <!-- Modern Template Option -->
            <div class="hsz-template-option <?php echo $template_mode === 'modern' ? 'hsz-selected' : ''; ?>">
                <input type="radio" 
                       name="hsz_template_mode" 
                       value="modern" 
                       id="template_modern" 
                       <?php checked( $template_mode, 'modern' ); ?>>
                <label for="template_modern" class="hsz-template-label">
                    <div class="hsz-template-preview hsz-modern-preview">
                        <img src="<?php echo HSZ_ASSETS_URL . 'images/template-modern.png'; ?>" 
                             alt="<?php esc_attr_e( 'Modern Template Preview', 'hellaz-sitez-analyzer' ); ?>"
                             class="hsz-preview-image">
                    </div>
                    <div class="hsz-template-info">
                        <h4><?php esc_html_e( 'Modern Template', 'hellaz-sitez-analyzer' ); ?></h4>
                        <p><?php esc_html_e( 'Visually rich design with hero section, card-based layout, and interactive elements. Ideal for impressive presentations.', 'hellaz-sitez-analyzer' ); ?></p>
                        <ul class="hsz-template-features">
                            <li>✅ <?php esc_html_e( 'Hero section with background', 'hellaz-sitez-analyzer' ); ?></li>
                            <li>✅ <?php esc_html_e( 'Card-based layout', 'hellaz-sitez-analyzer' ); ?></li>
                            <li>✅ <?php esc_html_e( 'Interactive animations', 'hellaz-sitez-analyzer' ); ?></li>
                            <li>✅ <?php esc_html_e( 'Visual metrics dashboard', 'hellaz-sitez-analyzer' ); ?></li>
                        </ul>
                    </div>
                </label>
            </div>
            
        </div>
            
            <!-- Template Selection Help -->
            <div class="hsz-template-help">
                <h4><?php esc_html_e( 'Template Selection Guide', 'hellaz-sitez-analyzer' ); ?></h4>
                <div class="hsz-help-grid">
                    <div class="hsz-help-item">
                        <strong><?php esc_html_e( 'Choose Classic if:', 'hellaz-sitez-analyzer' ); ?></strong>
                        <ul>
                            <li><?php esc_html_e( 'You want comprehensive data display', 'hellaz-sitez-analyzer' ); ?></li>
                            <li><?php esc_html_e( 'Users need to explore details', 'hellaz-sitez-analyzer' ); ?></li>
                            <li><?php esc_html_e( 'Professional/business context', 'hellaz-sitez-analyzer' ); ?></li>
                            <li><?php esc_html_e( 'Fast loading is priority', 'hellaz-sitez-analyzer' ); ?></li>
                        </ul>
                    </div>
                    <div class="hsz-help-item">
                        <strong><?php esc_html_e( 'Choose Modern if:', 'hellaz-sitez-analyzer' ); ?></strong>
                        <ul>
                            <li><?php esc_html_e( 'Visual appeal is important', 'hellaz-sitez-analyzer' ); ?></li>
                            <li><?php esc_html_e( 'You want to impress visitors', 'hellaz-sitez-analyzer' ); ?></li>
                            <li><?php esc_html_e( 'Creative/agency website', 'hellaz-sitez-analyzer' ); ?></li>
                            <li><?php esc_html_e( 'Showcasing capabilities', 'hellaz-sitez-analyzer' ); ?></li>
                        </ul>
                    </div>
                    <div class="hsz-help-item">
                        <strong><?php esc_html_e( 'Use Compact for:', 'hellaz-sitez-analyzer' ); ?></strong>
                        <ul>
                            <li><?php esc_html_e( 'Sidebars and small spaces', 'hellaz-sitez-analyzer' ); ?></li>
                            <li><?php esc_html_e( 'Quick overview display', 'hellaz-sitez-analyzer' ); ?></li>
                            <li><?php esc_html_e( 'Multiple analyses on one page', 'hellaz-sitez-analyzer' ); ?></li>
                            <li><?php esc_html_e( 'Mobile-first designs', 'hellaz-sitez-analyzer' ); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * API section callback
     */
    public function api_section_callback() {
        echo '<p>' . esc_html__( 'Enter your API keys to enable enhanced features. All keys are optional but provide additional functionality.', 'hellaz-sitez-analyzer' ) . '</p>';
    }

    /**
     * VirusTotal API key callback
     */
    public function virustotal_api_key_callback() {
        $api_key = get_option( 'hsz_virustotal_api_key', '' );
        ?>
        <input type="password" 
               name="hsz_virustotal_api_key" 
               value="<?php echo esc_attr( $api_key ); ?>" 
               class="regular-text" 
               placeholder="<?php esc_attr_e( 'Enter VirusTotal API key', 'hellaz-sitez-analyzer' ); ?>">
        <button type="button" class="button hsz-test-api" data-api="virustotal">
            <?php esc_html_e( 'Test API', 'hellaz-sitez-analyzer' ); ?>
        </button>
        <p class="description">
            <?php printf(
                esc_html__( 'Get your free API key from %s. Enables security scanning features.', 'hellaz-sitez-analyzer' ),
                '<a href="https://www.virustotal.com/gui/join-us" target="_blank">VirusTotal</a>'
            ); ?>
        </p>
        <?php
    }

    /**
     * BuiltWith API key callback
     */
    public function builtwith_api_key_callback() {
        $api_key = get_option( 'hsz_builtwith_api_key', '' );
        ?>
        <input type="password" 
               name="hsz_builtwith_api_key" 
               value="<?php echo esc_attr( $api_key ); ?>" 
               class="regular-text" 
               placeholder="<?php esc_attr_e( 'Enter BuiltWith API key', 'hellaz-sitez-analyzer' ); ?>">
        <button type="button" class="button hsz-test-api" data-api="builtwith">
            <?php esc_html_e( 'Test API', 'hellaz-sitez-analyzer' ); ?>
        </button>
        <p class="description">
            <?php printf(
                esc_html__( 'Get your API key from %s. Enables technology stack detection.', 'hellaz-sitez-analyzer' ),
                '<a href="https://builtwith.com/api" target="_blank">BuiltWith</a>'
            ); ?>
        </p>
        <?php
    }

    /**
     * URLScan.io API key callback
     */
    public function urlscan_api_key_callback() {
        $api_key = get_option( 'hsz_urlscan_api_key', '' );
        ?>
        <input type="password" 
               name="hsz_urlscan_api_key" 
               value="<?php echo esc_attr( $api_key ); ?>" 
               class="regular-text" 
               placeholder="<?php esc_attr_e( 'Enter URLScan.io API key', 'hellaz-sitez-analyzer' ); ?>">
        <button type="button" class="button hsz-test-api" data-api="urlscan">
            <?php esc_html_e( 'Test API', 'hellaz-sitez-analyzer' ); ?>
        </button>
        <p class="description">
            <?php printf(
                esc_html__( 'Get your free API key from %s. Enables website screenshots and detailed analysis.', 'hellaz-sitez-analyzer' ),
                '<a href="https://urlscan.io/user/signup" target="_blank">URLScan.io</a>'
            ); ?>
        </p>
        <?php
    }

    /**
     * Cache section callback
     */
    public function cache_section_callback() {
        echo '<p>' . esc_html__( 'Configure caching settings to optimize performance and reduce API usage.', 'hellaz-sitez-analyzer' ) . '</p>';
    }

    /**
     * Cache duration callback
     */
    public function cache_duration_callback() {
        $cache_duration = get_option( 'hsz_cache_duration', 24 );
        ?>
        <input type="number" 
               name="hsz_cache_duration" 
               value="<?php echo esc_attr( $cache_duration ); ?>" 
               min="1" 
               max="168" 
               class="small-text">
        <p class="description">
            <?php esc_html_e( 'How long to cache analysis results (1-168 hours). Longer caching reduces API usage but may show outdated data.', 'hellaz-sitez-analyzer' ); ?>
        </p>
        <?php
    }

    /**
     * Handle admin actions
     */
    public function handle_admin_actions() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have permission to perform this action.', 'hellaz-sitez-analyzer' ) );
        }

        $action = sanitize_text_field( $_POST['action'] ?? '' );

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
        delete_option( 'hsz_virustotal_api_key' );
        delete_option( 'hsz_builtwith_api_key' );
        delete_option( 'hsz_urlscan_api_key' );
        delete_option( 'hsz_cache_duration' );
        delete_option( 'hsz_template_mode' );

        wp_send_json_success( __( 'All settings have been reset to defaults.', 'hellaz-sitez-analyzer' ) );
    }

    /**
     * Render main settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap hsz-admin-wrap">
            <h1><?php esc_html_e( 'HellaZ SiteZ Analyzer Settings', 'hellaz-sitez-analyzer' ); ?></h1>
            
            <div class="hsz-admin-header">
                <div class="hsz-admin-title">
                    <h2><?php esc_html_e( 'Website Analysis & SEO Tools', 'hellaz-sitez-analyzer' ); ?></h2>
                    <p class="hsz-admin-subtitle">
                        <?php esc_html_e( 'Configure your website analysis settings, API keys, and template preferences.', 'hellaz-sitez-analyzer' ); ?>
                    </p>
                </div>
                
                <div class="hsz-admin-actions">
                    <button type="button" class="button button-secondary hsz-clear-cache-btn">
                        <?php esc_html_e( 'Clear Cache', 'hellaz-sitez-analyzer' ); ?>
                    </button>
                    <button type="button" class="button button-secondary hsz-reset-settings-btn">
                        <?php esc_html_e( 'Reset Settings', 'hellaz-sitez-analyzer' ); ?>
                    </button>
                </div>
            </div>

            <form method="post" action="options.php" class="hsz-settings-form">
                <?php
                settings_fields( 'hsz_settings' );
                do_settings_sections( 'hsz_settings' );
                submit_button( __( 'Save Settings', 'hellaz-sitez-analyzer' ), 'primary', 'submit', true, [
                    'class' => 'button-primary hsz-save-settings'
                ]);
                ?>
            </form>

            <!-- System Status -->
            <div class="hsz-system-status">
                <h3><?php esc_html_e( 'System Status', 'hellaz-sitez-analyzer' ); ?></h3>
                <?php $this->render_system_status(); ?>
            </div>

            <!-- Usage Examples -->
            <div class="hsz-usage-examples">
                <h3><?php esc_html_e( 'Usage Examples', 'hellaz-sitez-analyzer' ); ?></h3>
                <div class="hsz-examples-grid">
                    <div class="hsz-example-card">
                        <h4><?php esc_html_e( 'Shortcode Usage', 'hellaz-sitez-analyzer' ); ?></h4>
                        <code>[hsz_analyzer url="https://example.com"]</code>
                        <p><?php esc_html_e( 'Basic analysis with default template', 'hellaz-sitez-analyzer' ); ?></p>
                        
                        <code>[hsz_analyzer url="https://example.com" display_type="compact"]</code>
                        <p><?php esc_html_e( 'Compact view for sidebars', 'hellaz-sitez-analyzer' ); ?></p>
                    </div>
                    
                    <div class="hsz-example-card">
                        <h4><?php esc_html_e( 'Widget Configuration', 'hellaz-sitez-analyzer' ); ?></h4>
                        <p><?php esc_html_e( '1. Go to Appearance → Widgets', 'hellaz-sitez-analyzer' ); ?></p>
                        <p><?php esc_html_e( '2. Add "HellaZ SiteZ Analyzer" widget', 'hellaz-sitez-analyzer' ); ?></p>
                        <p><?php esc_html_e( '3. Enter URL and choose display type', 'hellaz-sitez-analyzer' ); ?></p>
                    </div>
                    
                    <div class="hsz-example-card">
                        <h4><?php esc_html_e( 'Gutenberg Block', 'hellaz-sitez-analyzer' ); ?></h4>
                        <p><?php esc_html_e( '1. Add new block in editor', 'hellaz-sitez-analyzer' ); ?></p>
                        <p><?php esc_html_e( '2. Search for "SiteZ Analyzer"', 'hellaz-sitez-analyzer' ); ?></p>
                        <p><?php esc_html_e( '3. Configure URL and options', 'hellaz-sitez-analyzer' ); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render system status
     */
    private function render_system_status() {
        $php_extensions = [
            'curl' => extension_loaded( 'curl' ),
            'json' => extension_loaded( 'json' ),
            'mbstring' => extension_loaded( 'mbstring' ),
            'openssl' => extension_loaded( 'openssl' )
        ];

        $missing_extensions = array_keys( array_filter( $php_extensions, function( $loaded ) {
            return ! $loaded;
        }));

        $wp_upload_dir = wp_upload_dir();
        $cache_writable = is_writable( $wp_upload_dir['basedir'] );
        ?>
        
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Component', 'hellaz-sitez-analyzer' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'hellaz-sitez-analyzer' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php esc_html_e( 'PHP Version', 'hellaz-sitez-analyzer' ); ?></td>
                    <td><?php echo esc_html( PHP_VERSION ); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'WordPress Version', 'hellaz-sitez-analyzer' ); ?></td>
                    <td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'cURL Extension', 'hellaz-sitez-analyzer' ); ?></td>
                    <td><?php echo $php_extensions['curl'] ? '<span style="color: green;">✓ ' . esc_html__( 'Available', 'hellaz-sitez-analyzer' ) . '</span>' : '<span style="color: red;">✗ ' . esc_html__( 'Not Available', 'hellaz-sitez-analyzer' ) . '</span>'; ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'JSON Extension', 'hellaz-sitez-analyzer' ); ?></td>
                    <td><?php echo $php_extensions['json'] ? '<span style="color: green;">✓ ' . esc_html__( 'Available', 'hellaz-sitez-analyzer' ) . '</span>' : '<span style="color: red;">✗ ' . esc_html__( 'Not Available', 'hellaz-sitez-analyzer' ) . '</span>'; ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'mbstring Extension', 'hellaz-sitez-analyzer' ); ?></td>
                    <td><?php echo $php_extensions['mbstring'] ? '<span style="color: green;">✓ ' . esc_html__( 'Available', 'hellaz-sitez-analyzer' ) . '</span>' : '<span style="color: red;">✗ ' . esc_html__( 'Not Available', 'hellaz-sitez-analyzer' ) . '</span>'; ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'Cache Directory', 'hellaz-sitez-analyzer' ); ?></td>
                    <td><?php echo $cache_writable ? '<span style="color: green;">✓ ' . esc_html__( 'Writable', 'hellaz-sitez-analyzer' ) . '</span>' : '<span style="color: red;">✗ ' . esc_html__( 'Not Writable', 'hellaz-sitez-analyzer' ) . '</span>'; ?></td>
                </tr>
            </tbody>
        </table>

        <?php if ( ! defined( 'HSZ_ENCRYPTION_KEY' ) ): ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php esc_html_e( 'Security Warning:', 'hellaz-sitez-analyzer' ); ?></strong>
                    <?php esc_html_e( 'The encryption key is not defined in your wp-config.php file. API keys and other sensitive settings will be saved, but they will not be encrypted. Please define the HSZ_ENCRYPTION_KEY constant for full security.', 'hellaz-sitez-analyzer' ); ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if ( ! empty( $missing_extensions ) ): ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php esc_html_e( 'Missing Extensions:', 'hellaz-sitez-analyzer' ); ?></strong>
                    <?php printf( __( 'The following PHP extensions are recommended for full functionality: %s', 'hellaz-sitez-analyzer' ), implode( ', ', $missing_extensions ) ); ?>
                </p>
            </div>
        <?php endif; ?>
        <?php
    }

    /**
     * Render performance settings page
     */
    public function render_performance_page() {
        ?>
        <div class="wrap hsz-admin-wrap">
            <h1><?php esc_html_e( 'Performance Settings', 'hellaz-sitez-analyzer' ); ?></h1>
            <p><?php esc_html_e( 'Configure performance analysis settings and thresholds.', 'hellaz-sitez-analyzer' ); ?></p>
            
            <!-- Performance settings form would go here -->
            <div class="notice notice-info">
                <p><?php esc_html_e( 'Performance settings panel - Coming in future update!', 'hellaz-sitez-analyzer' ); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Render security settings page  
     */
    public function render_security_page() {
        ?>
        <div class="wrap hsz-admin-wrap">
            <h1><?php esc_html_e( 'Security Settings', 'hellaz-sitez-analyzer' ); ?></h1>
            <p><?php esc_html_e( 'Configure security analysis settings and API integrations.', 'hellaz-sitez-analyzer' ); ?></p>
            
            <!-- Security settings form would go here -->
            <div class="notice notice-info">
                <p><?php esc_html_e( 'Security settings panel - Coming in future update!', 'hellaz-sitez-analyzer' ); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Render previews settings page
     */
    public function render_previews_page() {
        ?>
        <div class="wrap hsz-admin-wrap">
            <h1><?php esc_html_e( 'Preview Settings', 'hellaz-sitez-analyzer' ); ?></h1>
            <p><?php esc_html_e( 'Preview and test different template styles and configurations.', 'hellaz-sitez-analyzer' ); ?></p>
            
            <!-- Preview panel would go here -->
            <div class="notice notice-info">
                <p><?php esc_html_e( 'Preview panel - Coming in future update!', 'hellaz-sitez-analyzer' ); ?></p>
            </div>
        </div>
        <?php
    }
}
