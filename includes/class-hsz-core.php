<?php
/**
 * Core class for HellaZ SiteZ Analyzer.
 *
 * Manages plugin initialization, component loading, and enhanced features.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.1.0
 */

namespace HSZ;

defined( 'ABSPATH' ) || exit;

class Core {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Plugin components.
	 *
	 * @var array
	 */
	private $components = [];

	/**
	 * Constructor - Initialize the plugin.
	 */
	public function __construct() {
		$this->version = HSZ_VERSION;

		$this->init_classes();
		$this->init_hooks();
		$this->init_enhanced_features();
	}

	/**
	 * Initializes the necessary classes for the plugin.
	 * Relies on the autoloader.
	 */
	private function init_classes() {
		// Existing core classes - maintain all functionality
		$this->components['admin'] = new Admin();
		$this->components['ajax'] = new Ajax();
		$this->components['admin_logs'] = new AdminLogs();
		$this->components['shortcode'] = new Shortcode();
		$this->components['gutenberg'] = new Gutenberg();
		$this->components['widget'] = new Widget();

		// Enhanced Phase 1 classes - initialize only if they exist
		$this->init_enhanced_classes();
	}

	/**
	 * Initialize enhanced Phase 1 classes
	 */
	private function init_enhanced_classes() {
		// Define enhanced classes to be loaded
		// This allows for easy addition of new classes in the future.
		// Each class should be defined in its own file under the includes/ directory.
		$enhanced_classes = [
			'grading'     => 'Grading',     // includes/class-hsz-grading.php
			'preview'     => 'Preview',     // includes/class-hsz-preview.php
			'performance' => 'Performance', // includes/class-hsz-performance.php
			'security'    => 'Security',    // includes/class-hsz-security.php
			'metadata'    => 'Metadata',    // includes/class-hsz-metadata.php
			'api_manager' => 'ApiManager',  // includes/class-hsz-apimanager.php
			'social_media'=> 'SocialMedia', // includes/class-hsz-social-media.php
			'hooks'       => 'Hooks',       // includes/class-hsz-hooks.php
			// Ensure these classes are loaded only if they exist
			// This prevents fatal errors if the files are not present.
		];

		foreach ( $enhanced_classes as $key => $class_name ) {
			$full_class_name = "HSZ\\{$class_name}";

			if ( class_exists( $full_class_name ) ) {
				$this->components[ $key ] = new $full_class_name();
			}
		}
	}

	/**
	 * Initialize and register WordPress hooks and filters.
	 */
	private function init_hooks() {
		// Existing hooks - maintain all functionality
		add_action( 'plugins_loaded', [ $this, 'load_plugin_textdomain' ] );
		add_action( 'wpmu_new_blog', [ 'HSZ\\Database', 'create_tables_new_site' ] );
		add_action( 'init', [ 'HSZ\\Database', 'check_db_version' ] );

		// Enhanced hooks for Phase 1 features
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'init', [ $this, 'init_cron_jobs' ] );

		// Initialize Hooks class if available
		if ( isset( $this->components['hooks'] ) ) {
			Hooks::init();
		}
	}

	/**
	 * Initialize enhanced features
	 */
	private function init_enhanced_features() {
		// Set default options for enhanced features
		$this->set_enhanced_defaults();

		// Initialize cleanup cron jobs
		$this->schedule_cleanup_jobs();
	}

	/**
	 * Set default options for enhanced features
	 */
	private function set_enhanced_defaults() {
		$defaults = [
			// Performance Analysis
			'hsz_performance_analysis_enabled' => true,
			'hsz_pagespeed_enabled' => false,
			'hsz_pagespeed_api_key' => '',
			'hsz_webpagetest_enabled' => false,
			'hsz_webpagetest_api_key' => '',

			// Security Analysis
			'hsz_security_analysis_enabled' => true,
			'hsz_ssl_analysis_enabled' => true,
			'hsz_security_headers_check' => true,
			'hsz_vulnerability_scan_enabled' => true,

			// Preview Generation
			'hsz_preview_generation_enabled' => true,
			'hsz_screenshot_service' => 'thum_io', // Free service as default
			'hsz_screenshot_width' => 1366,
			'hsz_screenshot_height' => 768,

			// Grading System
			'hsz_grading_system_enabled' => true,
			'hsz_overall_grade_display' => true,
			'hsz_performance_weight' => 30,
			'hsz_security_weight' => 30,
			'hsz_content_weight' => 20,
			'hsz_usability_weight' => 20,

			// API Management
			'hsz_api_timeout' => 30,
			'hsz_api_rate_limit' => 100,
			'hsz_api_retry_attempts' => 3,

			// Caching for enhanced features
			'hsz_performance_cache_duration' => HOUR_IN_SECONDS * 6,
			'hsz_security_cache_duration' => HOUR_IN_SECONDS * 12,
			'hsz_preview_cache_duration' => DAY_IN_SECONDS * 7,

			// Hooks settings
			'hsz_auto_analyze_links' => false,
			'hsz_add_security_attributes' => true,
			'hsz_validate_redirects' => true,
			'hsz_add_security_headers' => false,
			'hsz_optimize_assets' => false,
			'hsz_add_resource_hints' => false,
			'hsz_enable_analytics' => false,
		];

		foreach ( $defaults as $option => $value ) {
			if ( false === get_option( $option ) ) {
				add_option( $option, $value );
			}
		}
	}

	/**
	 * Schedule cleanup cron jobs
	 */
	private function schedule_cleanup_jobs() {
		// Existing cache cleanup
		if ( ! wp_next_scheduled( 'hsz_cleanup_cache' ) ) {
			wp_schedule_event( time(), 'daily', 'hsz_cleanup_cache' );
		}

		// Enhanced cleanup jobs
		if ( ! wp_next_scheduled( 'hsz_cleanup_screenshots' ) ) {
			wp_schedule_event( time(), 'weekly', 'hsz_cleanup_screenshots' );
		}

		if ( ! wp_next_scheduled( 'hsz_cleanup_reports' ) ) {
			wp_schedule_event( time(), 'weekly', 'hsz_cleanup_reports' );
		}

		// Add cron actions
		add_action( 'hsz_cleanup_screenshots', [ $this, 'cleanup_old_screenshots' ] );
		add_action( 'hsz_cleanup_reports', [ $this, 'cleanup_old_reports' ] );
	}

	/**
	 * Initialize cron jobs for enhanced features
	 */
	public function init_cron_jobs() {
		// Register custom cron intervals
		add_filter( 'cron_schedules', [ $this, 'add_cron_intervals' ] );
	}

	/**
	 * Add custom cron intervals
	 *
	 * @param array $schedules Existing schedules
	 * @return array Modified schedules
	 */
	public function add_cron_intervals( $schedules ) {
		$schedules['weekly'] = [
			'interval' => WEEK_IN_SECONDS,
			'display' => __( 'Weekly', 'hellaz-sitez-analyzer' )
		];
		return $schedules;
	}

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'hellaz-sitez-analyzer',
			false,
			dirname( plugin_basename( HSZ_PLUGIN_PATH ) ) . '/languages/'
		);
	}

	/**
	 * Enqueue frontend assets for enhanced features
	 */
	public function enqueue_frontend_assets() {
		// Only enqueue if enhanced features are being used on this page
		if ( $this->should_load_frontend_assets() ) {
			wp_enqueue_style(
				'hsz-enhanced-frontend',
				HSZ_ASSETS_URL . 'css/enhanced-frontend.css',
				[ 'hsz-frontend' ],
				HSZ_VERSION
			);

			wp_enqueue_script(
				'hsz-enhanced-frontend',
				HSZ_ASSETS_URL . 'js/enhanced-frontend.js',
				[ 'jquery' ],
				HSZ_VERSION,
				true
			);

			wp_localize_script( 'hsz-enhanced-frontend', 'hszEnhanced', [
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'hsz_enhanced_nonce' ),
				'i18n' => [
					'analyzing' => __( 'Analyzing website...', 'hellaz-sitez-analyzer' ),
					'grading' => __( 'Calculating grade...', 'hellaz-sitez-analyzer' ),
					'screenshot' => __( 'Generating preview...', 'hellaz-sitez-analyzer' ),
					'error' => __( 'Analysis failed. Please try again.', 'hellaz-sitez-analyzer' )
				]
			]);

			// Enqueue Hooks scripts if enabled
			if ( isset( $this->components['hooks'] ) ) {
				Hooks::enqueue_link_scripts();
			}
		}
	}

	/**
	 * Enqueue admin assets for enhanced features
	 */
	public function enqueue_admin_assets( $hook ) {
		// Load on plugin settings pages
		if ( strpos( $hook, 'hellaz-sitez-analyzer' ) !== false ) {
			wp_enqueue_script(
				'hsz-enhanced-admin',
				HSZ_ASSETS_URL . 'js/enhanced-admin.js',
				[ 'jquery', 'wp-util' ],
				HSZ_VERSION,
				true
			);

			wp_localize_script( 'hsz-enhanced-admin', 'hszEnhancedAdmin', [
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'hsz_enhanced_admin_nonce' ),
				'features' => [
					'performance' => $this->is_feature_enabled( 'performance_analysis' ),
					'security' => $this->is_feature_enabled( 'security_analysis' ),
					'preview' => $this->is_feature_enabled( 'preview_generation' ),
					'grading' => $this->is_feature_enabled( 'grading_system' )
				]
			]);
		}
	}

	/**
	 * Check if a feature is enabled
	 *
	 * @param string $feature Feature name
	 * @return bool
	 */
	private function is_feature_enabled( $feature ) {
		return (bool) get_option( "hsz_{$feature}_enabled", false );
	}

	/**
	 * Check if frontend assets should be loaded
	 *
	 * @return bool
	 */
	private function should_load_frontend_assets() {
		global $post;

		// Check if current page/post contains plugin shortcodes or blocks
		if ( $post && (
			has_shortcode( $post->post_content, 'sitez_analyzer' ) ||
			has_shortcode( $post->post_content, 'hsz_analyzer' ) ||
			has_block( 'hsz/analyzer', $post->post_content ) ||
			has_block( 'hsz/analyzer-block', $post->post_content )
		)) {
			return true;
		}

		// Check if widgets are active
		if ( is_active_widget( false, false, 'hsz_site_analyzer_widget' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * The main execution function for the plugin.
	 */
	public function run() {
		// All functionality is initialized via hooks in the private constructor.
		// This public method exists as a clean entry point.
	}

	/**
	 * Plugin activation routine.
	 *
	 * Creates database tables and sets default options.
	 */
	public static function activate() {
		// **CRITICAL FIX**: Explicitly require the Database class file here to
		// prevent a fatal error on activation if the autoloader is not yet available.
		require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-database.php';

		// Delegate table creation to the centralized Database class.
		if ( class_exists( 'HSZ\\Database' ) ) {
			Database::create_tables();
		}

		// Set default cache duration if not set.
		if ( false === get_option( 'hsz_cache_duration' ) ) {
			update_option( 'hsz_cache_duration', DAY_IN_SECONDS );
		}

		// Initialize upload directories
		self::init_upload_directories();

		// Schedule enhanced cron jobs
		wp_schedule_event( time(), 'daily', 'hsz_cleanup_cache' );
		wp_schedule_event( time(), 'weekly', 'hsz_cleanup_screenshots' );
		wp_schedule_event( time(), 'weekly', 'hsz_cleanup_reports' );

		// Flush rewrite rules to apply any new rules.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation routine.
	 *
	 * Cleanup tasks if necessary on plugin deactivation.
	 */
	public static function deactivate() {
		// Clear scheduled cron jobs
		wp_clear_scheduled_hook( 'hsz_cleanup_cache' );
		wp_clear_scheduled_hook( 'hsz_cleanup_screenshots' );
		wp_clear_scheduled_hook( 'hsz_cleanup_reports' );

		// Clear any temporary data
		delete_transient( 'hsz_api_rate_limits' );

		// Remove hooks if they were registered
		if ( class_exists( 'HSZ\\Hooks' ) ) {
			Hooks::remove_all_hooks();
		}
	}

	/**
	 * Initialize upload directories
	 */
	private static function init_upload_directories() {
		$upload_dir = wp_upload_dir();
		$hsz_dir = $upload_dir['basedir'] . '/hsz-analyzer/';
		
		$directories = [
			$hsz_dir,
			$hsz_dir . 'screenshots/',
			$hsz_dir . 'reports/',
			$hsz_dir . 'cache/'
		];

		foreach ( $directories as $dir ) {
			if ( ! file_exists( $dir ) ) {
				wp_mkdir_p( $dir );
				// Add .htaccess for security
				file_put_contents( $dir . '.htaccess', 'deny from all' );
			}
		}
	}

	/**
	 * Cleanup old screenshots
	 */
	public function cleanup_old_screenshots() {
		if ( isset( $this->components['preview'] ) ) {
			$deleted = $this->components['preview']->cleanup_old_screenshots( 30 ); // 30 days
			if ( $deleted > 0 ) {
				Utils::log_error( "Cleaned up {$deleted} old screenshots", __FILE__, __LINE__ );
			}
		}
	}

	/**
	 * Cleanup old reports
	 */
	public function cleanup_old_reports() {
		$upload_dir = wp_upload_dir();
		$reports_dir = $upload_dir['basedir'] . '/hsz-analyzer/reports/';
		
		if ( is_dir( $reports_dir ) ) {
			$files = glob( $reports_dir . '*' );
			$cutoff_time = time() - ( 60 * DAY_IN_SECONDS ); // 60 days
			$deleted = 0;

			foreach ( $files as $file ) {
				if ( is_file( $file ) && filemtime( $file ) < $cutoff_time ) {
					if ( unlink( $file ) ) {
						$deleted++;
					}
				}
			}

			if ( $deleted > 0 ) {
				Utils::log_error( "Cleaned up {$deleted} old reports", __FILE__, __LINE__ );
			}
		}
	}

	/**
	 * Get plugin version.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Get component instance
	 *
	 * @param string $component Component name
	 * @return object|null
	 */
	public function get_component( $component ) {
		return isset( $this->components[ $component ] ) ? $this->components[ $component ] : null;
	}

	/**
	 * Check if component exists
	 *
	 * @param string $component Component name
	 * @return bool
	 */
	public function has_component( $component ) {
		return isset( $this->components[ $component ] );
	}

	/**
	 * Get all available components
	 *
	 * @return array
	 */
	public function get_components() {
		return $this->components;
	}
}
