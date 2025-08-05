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

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin
 *
 * Creates the settings page, registers settings, and handles form submissions.
 */
class Admin {

	/**
	 * The option group for the settings page.
	 * @var string
	 */
	private $settings_group = 'hsz_settings_group';

	/**
	 * The slug for the plugin's settings page.
	 * @var string
	 */
	private $page_slug = 'hellaz-sitez-analyzer-settings';

	/**
	 * Admin constructor.
	 *
	 * Registers the necessary actions for the admin dashboard.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_init', [ $this, 'handle_cache_clearing' ] );
		add_action( 'admin_init', [ $this, 'handle_enhanced_actions' ] );
		add_action( 'admin_notices', [ $this, 'show_admin_notices' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
	}

	/**
	 * Adds the main settings page to the WordPress admin menu.
	 */
	public function add_settings_page(): void {
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
			HSZ_ASSETS_URL . 'css/admin-enhanced.css',
			[],
			HSZ_VERSION
		);

		wp_enqueue_script(
			'hsz-admin-enhanced',
			HSZ_ASSETS_URL . 'js/admin-enhanced.js',
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
				$message = sprintf( _n( '%d cache entry was successfully deleted.', '%d cache entries were successfully deleted.', $deleted_rows, 'hellaz-sitez-analyzer' ), $deleted_rows );
				printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $message ) );
			});
		}
	}

	/**
	 * Handle enhanced feature actions
	 */
	public function handle_enhanced_actions(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Test API connections
		if ( isset( $_POST['hsz_action'] ) && $_POST['hsz_action'] === 'test_api' ) {
			if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'hsz_test_api_nonce' ) ) {
				wp_die( __( 'Security check failed.', 'hellaz-sitez-analyzer' ) );
			}

			$service = sanitize_key( $_POST['service'] ?? '' );
			$this->test_api_connection( $service );
		}

		// Reset settings to defaults
		if ( isset( $_POST['hsz_action'] ) && $_POST['hsz_action'] === 'reset_settings' ) {
			if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'hsz_reset_settings_nonce' ) ) {
				wp_die( __( 'Security check failed.', 'hellaz-sitez-analyzer' ) );
			}

			$this->reset_settings_to_defaults();
		}

		// Clean up old files
		if ( isset( $_POST['hsz_action'] ) && $_POST['hsz_action'] === 'cleanup_files' ) {
			if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'hsz_cleanup_files_nonce' ) ) {
				wp_die( __( 'Security check failed.', 'hellaz-sitez-analyzer' ) );
			}

			$this->cleanup_old_files();
		}
	}

	/**
	 * Test API connection for a service
	 */
	private function test_api_connection( $service ) {
		$api_config = hsz_get_api_config( $service );
		
		if ( ! $api_config['enabled'] || empty( $api_config['api_key'] ) ) {
			add_action( 'admin_notices', function () use ( $service ) {
				printf( 
					'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
					sprintf( __( '%s API is not configured.', 'hellaz-sitez-analyzer' ), ucfirst( $service ) )
				);
			});
			return;
		}

		// Test the API connection based on service
		$success = false;
		switch ( $service ) {
			case 'pagespeed':
				$success = $this->test_pagespeed_api( $api_config['api_key'] );
				break;
			case 'virustotal':
				$success = $this->test_virustotal_api( $api_config['api_key'] );
				break;
			// Add more services as needed
		}

		add_action( 'admin_notices', function () use ( $service, $success ) {
			$class = $success ? 'notice-success' : 'notice-error';
			$message = $success 
				? sprintf( __( '%s API connection successful!', 'hellaz-sitez-analyzer' ), ucfirst( $service ) )
				: sprintf( __( '%s API connection failed. Please check your API key.', 'hellaz-sitez-analyzer' ), ucfirst( $service ) );
			
			printf( '<div class="notice %s is-dismissible"><p>%s</p></div>', $class, esc_html( $message ) );
		});
	}

	/**
	 * Test PageSpeed Insights API
	 */
	private function test_pagespeed_api( $api_key ) {
		$test_url = 'https://www.google.com';
		$api_url = "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=" . urlencode( $test_url ) . "&key=" . $api_key;
		
		$response = wp_remote_get( $api_url, [ 'timeout' => 10 ] );
		
		if ( is_wp_error( $response ) ) {
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		return $response_code === 200;
	}

	/**
	 * Test VirusTotal API
	 */
	private function test_virustotal_api( $api_key ) {
		$api_url = "https://www.virustotal.com/vtapi/v2/url/report?apikey={$api_key}&resource=http://www.google.com";
		
		$response = wp_remote_get( $api_url, [ 'timeout' => 10 ] );
		
		if ( is_wp_error( $response ) ) {
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		return $response_code === 200;
	}

	/**
	 * Reset settings to defaults
	 */
	private function reset_settings_to_defaults() {
		$default_options = [
			// Existing defaults
			'hsz_fallback_image' => '',
			'hsz_fallback_title' => '',
			'hsz_fallback_description' => '',
			'hsz_cache_duration' => DAY_IN_SECONDS,
			'hsz_template_mode' => 'classic',

			// Enhanced defaults
			'hsz_performance_analysis_enabled' => true,
			'hsz_security_analysis_enabled' => true,
			'hsz_preview_generation_enabled' => true,
			'hsz_grading_system_enabled' => true,
			'hsz_screenshot_width' => 1366,
			'hsz_screenshot_height' => 768,
			'hsz_api_timeout' => 30,
		];

		foreach ( $default_options as $option => $value ) {
			update_option( $option, $value );
		}

		add_action( 'admin_notices', function () {
			printf( 
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				__( 'Settings have been reset to defaults.', 'hellaz-sitez-analyzer' )
			);
		});
	}

	/**
	 * Clean up old files
	 */
	private function cleanup_old_files() {
		$deleted_files = 0;
		
		// Clean up screenshots older than 30 days
		$screenshots_dir = HSZ_UPLOAD_DIR . 'screenshots/';
		if ( is_dir( $screenshots_dir ) ) {
			$files = glob( $screenshots_dir . '*' );
			$cutoff_time = time() - ( 30 * DAY_IN_SECONDS );
			
			foreach ( $files as $file ) {
				if ( is_file( $file ) && filemtime( $file ) < $cutoff_time ) {
					if ( unlink( $file ) ) {
						$deleted_files++;
					}
				}
			}
		}

		// Clean up old reports
		$reports_dir = HSZ_UPLOAD_DIR . 'reports/';
		if ( is_dir( $reports_dir ) ) {
			$files = glob( $reports_dir . '*' );
			$cutoff_time = time() - ( 60 * DAY_IN_SECONDS );
			
			foreach ( $files as $file ) {
				if ( is_file( $file ) && filemtime( $file ) < $cutoff_time ) {
					if ( unlink( $file ) ) {
						$deleted_files++;
					}
				}
			}
		}

		add_action( 'admin_notices', function () use ( $deleted_files ) {
			printf( 
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				sprintf( __( 'Cleaned up %d old files.', 'hellaz-sitez-analyzer' ), $deleted_files )
			);
		});
	}

	/**
	 * Show admin notices
	 */
	public function show_admin_notices() {
		// Check if encryption is configured
		if ( ! Utils::is_encryption_configured() ) {
			echo '<div class="notice notice-warning"><p>';
			echo '<strong>' . esc_html__( 'Security Warning:', 'hellaz-sitez-analyzer' ) . '</strong> ';
			echo esc_html__( 'The encryption key is not defined in your wp-config.php file. API keys and other sensitive settings will be saved, but they will not be encrypted. Please define the HSZ_ENCRYPTION_KEY constant for full security.', 'hellaz-sitez-analyzer' );
			echo '</p></div>';
		}

		// Check for missing required extensions
		$missing_extensions = [];
		$required_extensions = [ 'curl', 'json', 'gd' ];
		
		foreach ( $required_extensions as $extension ) {
			if ( ! extension_loaded( $extension ) ) {
				$missing_extensions[] = $extension;
			}
		}

		if ( ! empty( $missing_extensions ) ) {
			echo '<div class="notice notice-error"><p>';
			echo '<strong>' . esc_html__( 'Missing Extensions:', 'hellaz-sitez-analyzer' ) . '</strong> ';
			echo sprintf( __( 'The following PHP extensions are recommended for full functionality: %s', 'hellaz-sitez-analyzer' ), implode( ', ', $missing_extensions ) );
			echo '</p></div>';
		}
	}

	/**
	 * Registers all settings for the plugin across all tabs.
	 */
	public function register_settings(): void {
		// EXISTING SETTINGS - Maintain all functionality
		
		// General Tab
		register_setting( $this->settings_group, 'hsz_fallback_image', [ 'sanitize_callback' => [ 'HSZ\\Utils', 'sanitize_and_encrypt' ] ] );
		register_setting( $this->settings_group, 'hsz_fallback_title', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( $this->settings_group, 'hsz_fallback_description', [ 'sanitize_callback' => 'sanitize_textarea_field' ] );
		register_setting( $this->settings_group, 'hsz_disclaimer_enabled', [ 'sanitize_callback' => 'absint' ] );
		register_setting( $this->settings_group, 'hsz_disclaimer_message', [ 'sanitize_callback' => 'wp_kses_post' ] );
		register_setting( $this->settings_group, 'hsz_auto_analyze_content', [ 'sanitize_callback' => 'absint' ] );
		register_setting( $this->settings_group, 'hsz_disable_ssl_verify', [ 'sanitize_callback' => 'absint' ] );

		// API Keys Tab - Existing
		$apis = [ 'virustotal', 'builtwith', 'urlscan' ];
		foreach ( $apis as $api ) {
			register_setting( $this->settings_group, "hsz_{$api}_enabled", [ 'sanitize_callback' => 'absint' ] );
			register_setting( $this->settings_group, "hsz_{$api}_api_key", [ 'sanitize_callback' => [ 'HSZ\\Utils', 'sanitize_and_encrypt' ] ] );
		}

		// Cache Tab - Existing
		register_setting( $this->settings_group, 'hsz_cache_duration', [ 'sanitize_callback' => 'absint' ] );
		register_setting( $this->settings_group, 'hsz_cache_debug', [ 'sanitize_callback' => 'absint' ] );

		// Templates Tab - Existing
		register_setting( $this->settings_group, 'hsz_template_mode', [ 'sanitize_callback' => 'sanitize_key' ] );

		// ENHANCED SETTINGS - Phase 1 New Features

		// Performance Analysis Settings
		register_setting( $this->settings_group, 'hsz_performance_analysis_enabled', [ 'sanitize_callback' => 'absint' ] );
		register_setting( $this->settings_group, 'hsz_pagespeed_enabled', [ 'sanitize_callback' => 'absint' ] );
		register_setting( $this->settings_group, 'hsz_pagespeed_api_key', [ 'sanitize_callback' => [ 'HSZ\\Utils', 'sanitize_and_encrypt' ] ] );
		register_setting( $this->settings_group, 'hsz_webpagetest_enabled', [ 'sanitize_callback' => 'absint' ] );
		register_setting( $this->settings_group, 'hsz_webpagetest_api_key', [ 'sanitize_callback' => [ 'HSZ\\Utils', 'sanitize_and_encrypt' ] ] );
		register_setting( $this->settings_group, 'hsz_performance_cache_duration', [ 'sanitize_callback' => 'absint' ] );

		// Security Analysis Settings
		register_setting( $this->settings_group, 'hsz_security_analysis_enabled', [ 'sanitize_callback' => 'absint' ] );
		register_setting( $this->settings_group, 'hsz_ssl_analysis_enabled', [ 'sanitize_callback' => 'absint' ] );
		register_setting( $this->settings_group, 'hsz_ssl_labs_enabled', [ 'sanitize_callback' => 'absint' ] );
		register_setting( $this->settings_group, 'hsz_security_headers_check', [ 'sanitize_callback' => 'absint' ] );
		register_setting( $this->settings_group, 'hsz_vulnerability_scan_enabled', [ 'sanitize_callback' => 'absint' ] );
		register_setting( $this->settings_group, 'hsz_security_cache_duration', [ 'sanitize_callback' => 'absint' ] );

		// Preview Generation Settings
		register_setting( $this->settings_group, 'hsz_preview_generation_enabled', [ 'sanitize_callback' => 'absint' ] );
		register_setting( $this->settings_group, 'hsz_screenshot_service', [ 'sanitize_callback' => 'sanitize_key' ] );
		register_setting( $this->settings_group, 'hsz_screenshot_width', [ 'sanitize_callback' => 'absint' ] );
		register_setting( $this->settings_group, 'hsz_screenshot_height', [ 'sanitize_callback' => 'absint' ] );
		register_setting( $this->settings_group, 'hsz_preview_cache_duration', [ 'sanitize_callback' => 'absint' ] );

		// Screenshot Service API Keys
		$screenshot_services = [ 'htmlcsstoimage', 'screenshotapi', 'urlbox', 'scrapfly' ];
		foreach ( $screenshot_services as $service ) {
			register_setting( $this->settings_group, "hsz_{$service}_enabled", [ 'sanitize_callback' => 'absint' ] );
			register_setting( $this->settings_group, "hsz_{$service}_api_key", [ 'sanitize_callback' => [ 'HSZ\\Utils', 'sanitize_and_encrypt' ] ] );
		}

		// Grading System Settings
		register_setting( $this->settings_group, 'hsz_grading_system_enabled', [ 'sanitize_callback' => 'absint' ] );
		register_setting( $this->settings_group, 'hsz_overall_grade_display', [ 'sanitize_callback' => 'absint' ] );
		register_setting( $this->settings_group, 'hsz_performance_weight', [ 'sanitize_callback' => 'absint' ] );
		register_setting( $this->settings_group, 'hsz_security_weight', [ 'sanitize_callback' => 'absint' ] );
		register_setting( $this->settings_group, 'hsz_content_weight', [ 'sanitize_callback' => 'absint' ] );
		register_setting( $this->settings_group, 'hsz_usability_weight', [ 'sanitize_callback' => 'absint' ] );

		// API Management Settings
		register_setting( $this->settings_group, 'hsz_api_timeout', [ 'sanitize_callback' => 'absint' ] );
		register_setting( $this->settings_group, 'hsz_api_rate_limit', [ 'sanitize_callback' => 'absint' ] );
		register_setting( $this->settings_group, 'hsz_api_retry_attempts', [ 'sanitize_callback' => 'absint' ] );
	}

	/**
	 * Renders the main container and navigation for the settings page.
	 */
	public function render_settings_page(): void {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'HellaZ SiteZ Analyzer Settings', 'hellaz-sitez-analyzer' ); ?></h1>

			<?php $this->render_tab_navigation( $tab ); ?>

			<?php
			switch ( $tab ) {
				case 'api_keys':
					$this->render_api_keys_tab();
					break;
				case 'performance':
					$this->render_performance_tab();
					break;
				case 'security':
					$this->render_security_tab();
					break;
				case 'previews':
					$this->render_previews_tab();
					break;
				case 'grading':
					$this->render_grading_tab();
					break;
				case 'templates':
					$this->render_templates_tab();
					break;
				case 'bulk':
					$this->render_bulk_tab();
					break;
				case 'cache':
					$this->render_cache_tab();
					break;
				case 'system':
					$this->render_system_tab();
					break;
				case 'about':
					$this->render_about_tab();
					break;
				case 'general':
				default:
					$this->render_general_tab();
					break;
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render tab navigation
	 */
	private function render_tab_navigation( $current_tab ) {
		$tabs = [
			'general' => __( 'General', 'hellaz-sitez-analyzer' ),
			'api_keys' => __( 'API Keys', 'hellaz-sitez-analyzer' ),
			'performance' => __( 'Performance', 'hellaz-sitez-analyzer' ),
			'security' => __( 'Security', 'hellaz-sitez-analyzer' ),
			'previews' => __( 'Previews', 'hellaz-sitez-analyzer' ),
			'grading' => __( 'Grading', 'hellaz-sitez-analyzer' ),
			'templates' => __( 'Templates', 'hellaz-sitez-analyzer' ),
			'bulk' => __( 'Bulk Analysis', 'hellaz-sitez-analyzer' ),
			'cache' => __( 'Cache', 'hellaz-sitez-analyzer' ),
			'system' => __( 'System Info', 'hellaz-sitez-analyzer' ),
			'about' => __( 'About', 'hellaz-sitez-analyzer' )
		];

		echo '<nav class="nav-tab-wrapper">';
		foreach ( $tabs as $tab_key => $tab_name ) {
			$active = $current_tab === $tab_key ? ' nav-tab-active' : '';
			$url = add_query_arg( 'tab', $tab_key, admin_url( 'admin.php?page=' . $this->page_slug ) );
			printf( 
				'<a href="%s" class="nav-tab%s">%s</a>',
				esc_url( $url ),
				$active,
				esc_html( $tab_name )
			);
		}
		echo '</nav>';
	}

	/**
	 * Render General Settings Tab - EXISTING FUNCTIONALITY MAINTAINED
	 */
	private function render_general_tab(): void {
		?>
		<form action="options.php" method="post">
			<?php settings_fields( $this->settings_group ); ?>
			
			<h3><?php esc_html_e( 'General Settings', 'hellaz-sitez-analyzer' ); ?></h3>
			
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
						<p class="description"><?php esc_html_e( 'If a page has no image, this will be used.', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Fallback Title', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<input type="text" name="hsz_fallback_title" value="<?php echo esc_attr( get_option( 'hsz_fallback_title', '' ) ); ?>" class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Fallback Description', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<textarea name="hsz_fallback_description" rows="3" class="large-text"><?php echo esc_textarea( get_option( 'hsz_fallback_description', '' ) ); ?></textarea>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Auto-Analyze Content', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<input type="checkbox" name="hsz_auto_analyze_content" value="1" <?php checked( get_option( 'hsz_auto_analyze_content' ), 1 ); ?> />
						<p class="description"><?php esc_html_e( 'Automatically add analysis attributes to external links in post content.', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Disclaimer', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<input type="checkbox" name="hsz_disclaimer_enabled" value="1" <?php checked( get_option( 'hsz_disclaimer_enabled' ), 1 ); ?> />
						<p class="description"><?php esc_html_e( 'Show a disclaimer message at the bottom of analyzed content.', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Disclaimer Message', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<textarea name="hsz_disclaimer_message" rows="3" class="large-text"><?php echo esc_textarea( get_option( 'hsz_disclaimer_message', '' ) ); ?></textarea>
					</td>
				</tr>
				<tr>
					<th colspan="2"><h4><?php esc_html_e( 'Advanced Settings', 'hellaz-sitez-analyzer' ); ?></h4></th>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Disable SSL Verification', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<input type="checkbox" name="hsz_disable_ssl_verify" value="1" <?php checked( get_option( 'hsz_disable_ssl_verify' ), 1 ); ?> />
						<p class="description">
							<strong><?php esc_html_e( 'Warning:', 'hellaz-sitez-analyzer' ); ?></strong>
							<?php esc_html_e( 'This is a security risk and should only be enabled if you are experiencing "cURL error 60" due to a server configuration issue. Enabling this makes your site vulnerable to man-in-the-middle attacks.', 'hellaz-sitez-analyzer' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
		<?php
	}

	/**
	 * Render API Keys Tab - ENHANCED WITH NEW SERVICES
	 */
	private function render_api_keys_tab(): void {
		?>
		<form action="options.php" method="post">
			<?php settings_fields( $this->settings_group ); ?>
			
			<h3><?php esc_html_e( 'Third-Party API Keys', 'hellaz-sitez-analyzer' ); ?></h3>
			<p><?php esc_html_e( 'Enable and configure third-party services to enhance analysis capabilities.', 'hellaz-sitez-analyzer' ); ?></p>

			<h4><?php esc_html_e( 'Existing Services', 'hellaz-sitez-analyzer' ); ?></h4>
			<table class="form-table">
				<?php
				$existing_apis = [ 
					'virustotal' => 'VirusTotal', 
					'builtwith' => 'BuiltWith', 
					'urlscan' => 'urlscan.io' 
				];
				
				foreach ( $existing_apis as $slug => $name ) :
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
					<td>
						<input type="checkbox" name="<?php echo esc_attr( $enabled_option ); ?>" value="1" <?php checked( get_option( $enabled_option ), 1 ); ?> />
						<label for="<?php echo esc_attr( $enabled_option ); ?>"><?php echo esc_html( $name ); ?> &gt; <?php esc_html_e( 'Enable', 'hellaz-sitez-analyzer' ); ?></label><br>
						<input type="text" name="<?php echo esc_attr( $key_option ); ?>" value="<?php echo esc_attr( $decrypted_key ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'API Key', 'hellaz-sitez-analyzer' ); ?>" />
						<button type="button" class="button test-api-btn" data-service="<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Test Connection', 'hellaz-sitez-analyzer' ); ?></button>
					</td>
				</tr>
				<?php endforeach; ?>
			</table>

			<h4><?php esc_html_e( 'Performance Analysis Services', 'hellaz-sitez-analyzer' ); ?></h4>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Google PageSpeed Insights', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<input type="checkbox" name="hsz_pagespeed_enabled" value="1" <?php checked( get_option( 'hsz_pagespeed_enabled' ), 1 ); ?> />
						<label><?php esc_html_e( 'Enable PageSpeed Insights API', 'hellaz-sitez-analyzer' ); ?></label><br>
						<?php 
						$pagespeed_key = get_option( 'hsz_pagespeed_api_key', '' );
						$decrypted_pagespeed_key = $pagespeed_key ? Utils::decrypt( $pagespeed_key ) : '';
						if ( false === $decrypted_pagespeed_key ) { 
							$decrypted_pagespeed_key = $pagespeed_key; 
						}
						?>
						<input type="text" name="hsz_pagespeed_api_key" value="<?php echo esc_attr( $decrypted_pagespeed_key ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Google API Key', 'hellaz-sitez-analyzer' ); ?>" />
						<button type="button" class="button test-api-btn" data-service="pagespeed"><?php esc_html_e( 'Test Connection', 'hellaz-sitez-analyzer' ); ?></button>
						<p class="description"><?php esc_html_e( 'Get your API key from Google Cloud Console. Enables Core Web Vitals and performance analysis.', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'WebPageTest', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<input type="checkbox" name="hsz_webpagetest_enabled" value="1" <?php checked( get_option( 'hsz_webpagetest_enabled' ), 1 ); ?> />
						<label><?php esc_html_e( 'Enable WebPageTest API', 'hellaz-sitez-analyzer' ); ?></label><br>
						<?php 
						$webpagetest_key = get_option( 'hsz_webpagetest_api_key', '' );
						$decrypted_webpagetest_key = $webpagetest_key ? Utils::decrypt( $webpagetest_key ) : '';
						if ( false === $decrypted_webpagetest_key ) { 
							$decrypted_webpagetest_key = $webpagetest_key; 
						}
						?>
						<input type="text" name="hsz_webpagetest_api_key" value="<?php echo esc_attr( $decrypted_webpagetest_key ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'WebPageTest API Key', 'hellaz-sitez-analyzer' ); ?>" />
						<p class="description"><?php esc_html_e( 'Advanced performance testing with detailed waterfall charts and metrics.', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
			</table>

			<h4><?php esc_html_e( 'Screenshot Services', 'hellaz-sitez-analyzer' ); ?></h4>
			<table class="form-table">
				<?php
				$screenshot_services = [
					'htmlcsstoimage' => 'HTML/CSS to Image',
					'screenshotapi' => 'ScreenshotAPI',
					'urlbox' => 'Urlbox',
					'scrapfly' => 'Scrapfly'
				];
				
				foreach ( $screenshot_services as $slug => $name ) :
					$enabled_option = "hsz_{$slug}_enabled";
					$key_option = "hsz_{$slug}_api_key";
					$encrypted_key = get_option( $key_option, '' );
					$decrypted_key = $encrypted_key ? Utils::decrypt( $encrypted_key ) : '';
					if ( false === $decrypted_key ) { 
						$decrypted_key = $encrypted_key; 
					}
				?>
				<tr>
					<th scope="row"><?php echo esc_html( $name ); ?></th>
					<td>
						<input type="checkbox" name="<?php echo esc_attr( $enabled_option ); ?>" value="1" <?php checked( get_option( $enabled_option ), 1 ); ?> />
						<label><?php esc_html_e( 'Enable', 'hellaz-sitez-analyzer' ); ?> <?php echo esc_html( $name ); ?></label><br>
						<input type="text" name="<?php echo esc_attr( $key_option ); ?>" value="<?php echo esc_attr( $decrypted_key ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'API Key', 'hellaz-sitez-analyzer' ); ?>" />
						<button type="button" class="button test-api-btn" data-service="<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Test Connection', 'hellaz-sitez-analyzer' ); ?></button>
					</td>
				</tr>
				<?php endforeach; ?>
			</table>

			<?php submit_button(); ?>
		</form>
		<?php
	}

	/**
	 * Render Performance Settings Tab - NEW ENHANCED FEATURE
	 */
	private function render_performance_tab(): void {
		?>
		<form action="options.php" method="post">
			<?php settings_fields( $this->settings_group ); ?>
			
			<h3><?php esc_html_e( 'Performance Analysis Settings', 'hellaz-sitez-analyzer' ); ?></h3>
			
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Performance Analysis', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<input type="checkbox" name="hsz_performance_analysis_enabled" value="1" <?php checked( get_option( 'hsz_performance_analysis_enabled', 1 ), 1 ); ?> />
						<p class="description"><?php esc_html_e( 'Enable comprehensive performance analysis including Core Web Vitals.', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Performance Cache Duration', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<select name="hsz_performance_cache_duration">
							<option value="<?php echo HOUR_IN_SECONDS; ?>" <?php selected( get_option( 'hsz_performance_cache_duration', HOUR_IN_SECONDS * 6 ), HOUR_IN_SECONDS ); ?>><?php esc_html_e( '1 Hour', 'hellaz-sitez-analyzer' ); ?></option>
							<option value="<?php echo HOUR_IN_SECONDS * 6; ?>" <?php selected( get_option( 'hsz_performance_cache_duration', HOUR_IN_SECONDS * 6 ), HOUR_IN_SECONDS * 6 ); ?>><?php esc_html_e( '6 Hours', 'hellaz-sitez-analyzer' ); ?></option>
							<option value="<?php echo HOUR_IN_SECONDS * 12; ?>" <?php selected( get_option( 'hsz_performance_cache_duration', HOUR_IN_SECONDS * 6 ), HOUR_IN_SECONDS * 12 ); ?>><?php esc_html_e( '12 Hours', 'hellaz-sitez-analyzer' ); ?></option>
							<option value="<?php echo DAY_IN_SECONDS; ?>" <?php selected( get_option( 'hsz_performance_cache_duration', HOUR_IN_SECONDS * 6 ), DAY_IN_SECONDS ); ?>><?php esc_html_e( '1 Day', 'hellaz-sitez-analyzer' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'How long to cache performance analysis results.', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
		<?php
	}

	/**
	 * Render Security Settings Tab - NEW ENHANCED FEATURE
	 */
	private function render_security_tab(): void {
		?>
		<form action="options.php" method="post">
			<?php settings_fields( $this->settings_group ); ?>
			
			<h3><?php esc_html_e( 'Security Analysis Settings', 'hellaz-sitez-analyzer' ); ?></h3>
			
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Security Analysis', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<input type="checkbox" name="hsz_security_analysis_enabled" value="1" <?php checked( get_option( 'hsz_security_analysis_enabled', 1 ), 1 ); ?> />
						<p class="description"><?php esc_html_e( 'Enable comprehensive security analysis including SSL, headers, and vulnerability scanning.', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'SSL/TLS Analysis', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<input type="checkbox" name="hsz_ssl_analysis_enabled" value="1" <?php checked( get_option( 'hsz_ssl_analysis_enabled', 1 ), 1 ); ?> /><br>
						<input type="checkbox" name="hsz_ssl_labs_enabled" value="1" <?php checked( get_option( 'hsz_ssl_labs_enabled' ), 1 ); ?> />
						<label><?php esc_html_e( 'Use SSL Labs for detailed SSL analysis (free service)', 'hellaz-sitez-analyzer' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Security Headers Check', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<input type="checkbox" name="hsz_security_headers_check" value="1" <?php checked( get_option( 'hsz_security_headers_check', 1 ), 1 ); ?> />
						<p class="description"><?php esc_html_e( 'Check for security headers like HSTS, CSP, X-Frame-Options, etc.', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Vulnerability Scanning', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<input type="checkbox" name="hsz_vulnerability_scan_enabled" value="1" <?php checked( get_option( 'hsz_vulnerability_scan_enabled', 1 ), 1 ); ?> />
						<p class="description"><?php esc_html_e( 'Scan for common vulnerabilities and exposed sensitive files.', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Security Cache Duration', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<select name="hsz_security_cache_duration">
							<option value="<?php echo HOUR_IN_SECONDS * 6; ?>" <?php selected( get_option( 'hsz_security_cache_duration', HOUR_IN_SECONDS * 12 ), HOUR_IN_SECONDS * 6 ); ?>><?php esc_html_e( '6 Hours', 'hellaz-sitez-analyzer' ); ?></option>
							<option value="<?php echo HOUR_IN_SECONDS * 12; ?>" <?php selected( get_option( 'hsz_security_cache_duration', HOUR_IN_SECONDS * 12 ), HOUR_IN_SECONDS * 12 ); ?>><?php esc_html_e( '12 Hours', 'hellaz-sitez-analyzer' ); ?></option>
							<option value="<?php echo DAY_IN_SECONDS; ?>" <?php selected( get_option( 'hsz_security_cache_duration', HOUR_IN_SECONDS * 12 ), DAY_IN_SECONDS ); ?>><?php esc_html_e( '1 Day', 'hellaz-sitez-analyzer' ); ?></option>
							<option value="<?php echo DAY_IN_SECONDS * 7; ?>" <?php selected( get_option( 'hsz_security_cache_duration', HOUR_IN_SECONDS * 12 ), DAY_IN_SECONDS * 7 ); ?>><?php esc_html_e( '1 Week', 'hellaz-sitez-analyzer' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'How long to cache security analysis results.', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
		<?php
	}

	/**
	 * Render Previews Settings Tab - NEW ENHANCED FEATURE
	 */
	private function render_previews_tab(): void {
		?>
		<form action="options.php" method="post">
			<?php settings_fields( $this->settings_group ); ?>
			
			<h3><?php esc_html_e( 'Website Preview Settings', 'hellaz-sitez-analyzer' ); ?></h3>
			
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Website Previews', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<input type="checkbox" name="hsz_preview_generation_enabled" value="1" <?php checked( get_option( 'hsz_preview_generation_enabled', 1 ), 1 ); ?> />
						<p class="description"><?php esc_html_e( 'Generate website screenshots for visual preview.', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Default Screenshot Service', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<select name="hsz_screenshot_service">
							<option value="thum_io" <?php selected( get_option( 'hsz_screenshot_service', 'thum_io' ), 'thum_io' ); ?>><?php esc_html_e( 'Thum.io (Free)', 'hellaz-sitez-analyzer' ); ?></option>
							<option value="htmlcsstoimage" <?php selected( get_option( 'hsz_screenshot_service', 'thum_io' ), 'htmlcsstoimage' ); ?>><?php esc_html_e( 'HTML/CSS to Image', 'hellaz-sitez-analyzer' ); ?></option>
							<option value="screenshotapi" <?php selected( get_option( 'hsz_screenshot_service', 'thum_io' ), 'screenshotapi' ); ?>><?php esc_html_e( 'ScreenshotAPI', 'hellaz-sitez-analyzer' ); ?></option>
							<option value="urlbox" <?php selected( get_option( 'hsz_screenshot_service', 'thum_io' ), 'urlbox' ); ?>><?php esc_html_e( 'Urlbox', 'hellaz-sitez-analyzer' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Choose the primary service for screenshot generation. Falls back to other services if the primary fails.', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Screenshot Dimensions', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<input type="number" name="hsz_screenshot_width" value="<?php echo esc_attr( get_option( 'hsz_screenshot_width', 1366 ) ); ?>" min="800" max="1920" /> x 
						<input type="number" name="hsz_screenshot_height" value="<?php echo esc_attr( get_option( 'hsz_screenshot_height', 768 ) ); ?>" min="600" max="1200" />
						<p class="description"><?php esc_html_e( 'Default screenshot dimensions in pixels (width x height).', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Preview Cache Duration', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<select name="hsz_preview_cache_duration">
							<option value="<?php echo DAY_IN_SECONDS; ?>" <?php selected( get_option( 'hsz_preview_cache_duration', DAY_IN_SECONDS * 7 ), DAY_IN_SECONDS ); ?>><?php esc_html_e( '1 Day', 'hellaz-sitez-analyzer' ); ?></option>
							<option value="<?php echo DAY_IN_SECONDS * 7; ?>" <?php selected( get_option( 'hsz_preview_cache_duration', DAY_IN_SECONDS * 7 ), DAY_IN_SECONDS * 7 ); ?>><?php esc_html_e( '1 Week', 'hellaz-sitez-analyzer' ); ?></option>
							<option value="<?php echo DAY_IN_SECONDS * 30; ?>" <?php selected( get_option( 'hsz_preview_cache_duration', DAY_IN_SECONDS * 7 ), DAY_IN_SECONDS * 30 ); ?>><?php esc_html_e( '1 Month', 'hellaz-sitez-analyzer' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'How long to keep generated screenshots cached.', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
		<?php
	}

	/**
	 * Render Grading Settings Tab - NEW ENHANCED FEATURE
	 */
	private function render_grading_tab(): void {
		?>
		<form action="options.php" method="post">
			<?php settings_fields( $this->settings_group ); ?>
			
			<h3><?php esc_html_e( 'Website Grading System', 'hellaz-sitez-analyzer' ); ?></h3>
			
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enable Grading System', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<input type="checkbox" name="hsz_grading_system_enabled" value="1" <?php checked( get_option( 'hsz_grading_system_enabled', 1 ), 1 ); ?> />
						<p class="description"><?php esc_html_e( 'Provide overall letter grades (A-F) for analyzed websites.', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Display Overall Grade', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<input type="checkbox" name="hsz_overall_grade_display" value="1" <?php checked( get_option( 'hsz_overall_grade_display', 1 ), 1 ); ?> />
						<p class="description"><?php esc_html_e( 'Show the overall letter grade prominently in analysis results.', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
				<tr>
					<th colspan="2"><h4><?php esc_html_e( 'Grade Weight Distribution (Total must equal 100%)', 'hellaz-sitez-analyzer' ); ?></h4></th>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Performance Weight', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<input type="number" name="hsz_performance_weight" value="<?php echo esc_attr( get_option( 'hsz_performance_weight', 30 ) ); ?>" min="0" max="100" />%
						<p class="description"><?php esc_html_e( 'Weight given to performance metrics (Core Web Vitals, PageSpeed).', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Security Weight', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<input type="number" name="hsz_security_weight" value="<?php echo esc_attr( get_option( 'hsz_security_weight', 30 ) ); ?>" min="0" max="100" />%
						<p class="description"><?php esc_html_e( 'Weight given to security factors (SSL, headers, vulnerabilities).', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Content Weight', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<input type="number" name="hsz_content_weight" value="<?php echo esc_attr( get_option( 'hsz_content_weight', 20 ) ); ?>" min="0" max="100" />%
						<p class="description"><?php esc_html_e( 'Weight given to content quality (metadata, social profiles).', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Usability Weight', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<input type="number" name="hsz_usability_weight" value="<?php echo esc_attr( get_option( 'hsz_usability_weight', 20 ) ); ?>" min="0" max="100" />%
						<p class="description"><?php esc_html_e( 'Weight given to usability factors (mobile-friendly, accessibility).', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
			</table>

			<p class="description">
				<strong><?php esc_html_e( 'Current Total:', 'hellaz-sitez-analyzer' ); ?></strong> 
				<span id="grade-weight-total">
					<?php 
					echo esc_html( get_option( 'hsz_performance_weight', 30 ) + get_option( 'hsz_security_weight', 30 ) + get_option( 'hsz_content_weight', 20 ) + get_option( 'hsz_usability_weight', 20 ) ); 
					?>%
				</span>
			</p>

			<?php submit_button(); ?>
		</form>

		<script>
		jQuery(document).ready(function($) {
			function updateWeightTotal() {
				var total = 0;
				$('input[name$="_weight"]').each(function() {
					total += parseInt($(this).val()) || 0;
				});
				$('#grade-weight-total').text(total + '%');
				
				if (total !== 100) {
					$('#grade-weight-total').css('color', 'red');
				} else {
					$('#grade-weight-total').css('color', 'green');
				}
			}
			
			$('input[name$="_weight"]').on('input', updateWeightTotal);
			updateWeightTotal();
		});
		</script>
		<?php
	}

	/**
	 * Render Templates Tab - EXISTING FUNCTIONALITY MAINTAINED
	 */
	private function render_templates_tab(): void {
		?>
		<form action="options.php" method="post">
			<?php settings_fields( $this->settings_group ); ?>
			
			<h3><?php esc_html_e( 'Template Settings', 'hellaz-sitez-analyzer' ); ?></h3>
			
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Display Template', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<select name="hsz_template_mode">
							<option value="classic" <?php selected( get_option( 'hsz_template_mode', 'classic' ), 'classic' ); ?>><?php esc_html_e( 'Classic', 'hellaz-sitez-analyzer' ); ?></option>
							<option value="modern" <?php selected( get_option( 'hsz_template_mode', 'classic' ), 'modern' ); ?>><?php esc_html_e( 'Modern', 'hellaz-sitez-analyzer' ); ?></option>
							<option value="compact" <?php selected( get_option( 'hsz_template_mode', 'classic' ), 'compact' ); ?>><?php esc_html_e( 'Compact', 'hellaz-sitez-analyzer' ); ?></option>
							<option value="enhanced" <?php selected( get_option( 'hsz_template_mode', 'classic' ), 'enhanced' ); ?>><?php esc_html_e( 'Enhanced (with grades)', 'hellaz-sitez-analyzer' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Choose the default display style for the analysis output.', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
		<?php
	}

	/**
	 * Render Bulk Analysis Tab - EXISTING FUNCTIONALITY MAINTAINED
	 */
	private function render_bulk_tab(): void {
		?>
		<h3><?php esc_html_e( 'Bulk URL Analyzer', 'hellaz-sitez-analyzer' ); ?></h3>
		<p><?php esc_html_e( 'Submit a list of URLs to be processed in the background.', 'hellaz-sitez-analyzer' ); ?></p>

		<form method="post" action="">
			<?php wp_nonce_field( 'hsz_bulk_nonce', 'hsz_bulk_nonce' ); ?>
			<input type="hidden" name="hsz_action" value="start_bulk" />
			
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Batch Name', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<input type="text" name="batch_name" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Give this batch a descriptive name (optional).', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'URLs to Process', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<textarea name="urls" rows="10" class="large-text" placeholder="https://example1.com&#10;https://example2.com&#10;https://example3.com"></textarea>
						<p class="description"><?php esc_html_e( 'Enter one URL per line.', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Start Bulk Process', 'hellaz-sitez-analyzer' ) ); ?>
		</form>

		<hr />

		<h3><?php esc_html_e( 'Recent Bulk Batches', 'hellaz-sitez-analyzer' ); ?></h3>

		<?php
		if ( class_exists( 'HSZ\\BulkProcessor' ) ) {
			echo BulkProcessor::get_admin_report();
		} else {
			echo '<p>' . esc_html__( 'Bulk processing report is currently unavailable.', 'hellaz-sitez-analyzer' ) . '</p>';
		}
		?>
		<?php
	}

	/**
	 * Render Cache Tab - ENHANCED WITH NEW CACHE MANAGEMENT
	 */
	private function render_cache_tab(): void {
		?>
		<form action="options.php" method="post">
			<?php settings_fields( $this->settings_group ); ?>
			
			<h3><?php esc_html_e( 'Cache Settings', 'hellaz-sitez-analyzer' ); ?></h3>
			
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Cache Duration', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<input type="number" name="hsz_cache_duration" value="<?php echo esc_attr( get_option( 'hsz_cache_duration', DAY_IN_SECONDS ) ); ?>" min="3600" max="2592000" />
						<p class="description"><?php esc_html_e( 'Duration in seconds for which analysis results are cached.', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Cache Debug', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<input type="checkbox" name="hsz_cache_debug" value="1" <?php checked( get_option( 'hsz_cache_debug' ), 1 ); ?> />
						<p class="description"><?php esc_html_e( 'Enable cache debugging (adds comments to output).', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>

		<hr />

		<h3><?php esc_html_e( 'Clear Plugin Cache', 'hellaz-sitez-analyzer' ); ?></h3>
		<p><?php esc_html_e( 'This will immediately delete all cached analysis data from the database.', 'hellaz-sitez-analyzer' ); ?></p>

		<form method="post" action="">
			<input type="hidden" name="hsz_action" value="clear_cache" />
			<?php wp_nonce_field( 'hsz_clear_cache_nonce' ); ?>
			<?php submit_button( __( 'Clear All Cache Now', 'hellaz-sitez-analyzer' ), 'delete', 'hsz-clear-cache-button' ); ?>
		</form>

		<?php if ( class_exists( 'HSZ\\Database' ) ) : ?>
		<hr />
		<h3><?php esc_html_e( 'Cache Statistics', 'hellaz-sitez-analyzer' ); ?></h3>
		<?php
		$stats = Database::get_database_stats();
		if ( ! empty( $stats['tables'] ) ) :
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Cache Type', 'hellaz-sitez-analyzer' ); ?></th>
					<th><?php esc_html_e( 'Entries', 'hellaz-sitez-analyzer' ); ?></th>
					<th><?php esc_html_e( 'Size (MB)', 'hellaz-sitez-analyzer' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $stats['tables'] as $table => $data ) : ?>
				<tr>
					<td><?php echo esc_html( ucwords( str_replace( '_', ' ', $table ) ) ); ?></td>
					<td><?php echo esc_html( number_format( $data['rows'] ) ); ?></td>
					<td><?php echo esc_html( number_format( $data['size_mb'], 2 ) ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr>
					<th><strong><?php esc_html_e( 'Total', 'hellaz-sitez-analyzer' ); ?></strong></th>
					<th><strong><?php echo esc_html( number_format( $stats['total_rows'] ) ); ?></strong></th>
					<th><strong><?php echo esc_html( number_format( $stats['total_size'], 2 ) ); ?></strong></th>
				</tr>
			</tfoot>
		</table>
		<?php endif; ?>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render System Information Tab - NEW ENHANCED FEATURE
	 */
	private function render_system_tab(): void {
		?>
		<h3><?php esc_html_e( 'System Information', 'hellaz-sitez-analyzer' ); ?></h3>
		
		<h4><?php esc_html_e( 'Plugin Information', 'hellaz-sitez-analyzer' ); ?></h4>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Plugin Version', 'hellaz-sitez-analyzer' ); ?></th>
				<td><?php echo esc_html( HSZ_VERSION ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Database Version', 'hellaz-sitez-analyzer' ); ?></th>
				<td><?php echo esc_html( get_option( 'hsz_db_version', 'Unknown' ) ); ?></td>
			</tr>
		</table>

		<h4><?php esc_html_e( 'Server Information', 'hellaz-sitez-analyzer' ); ?></h4>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'PHP Version', 'hellaz-sitez-analyzer' ); ?></th>
				<td><?php echo esc_html( PHP_VERSION ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'WordPress Version', 'hellaz-sitez-analyzer' ); ?></th>
				<td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'cURL Extension', 'hellaz-sitez-analyzer' ); ?></th>
				<td><?php echo extension_loaded( 'curl' ) ? '<span style="color: green;">✓ ' . esc_html__( 'Available', 'hellaz-sitez-analyzer' ) . '</span>' : '<span style="color: red;">✗ ' . esc_html__( 'Not Available', 'hellaz-sitez-analyzer' ) . '</span>'; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'JSON Extension', 'hellaz-sitez-analyzer' ); ?></th>
				<td><?php echo extension_loaded( 'json' ) ? '<span style="color: green;">✓ ' . esc_html__( 'Available', 'hellaz-sitez-analyzer' ) . '</span>' : '<span style="color: red;">✗ ' . esc_html__( 'Not Available', 'hellaz-sitez-analyzer' ) . '</span>'; ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'GD Extension', 'hellaz-sitez-analyzer' ); ?></th>
				<td><?php echo extension_loaded( 'gd' ) ? '<span style="color: green;">✓ ' . esc_html__( 'Available', 'hellaz-sitez-analyzer' ) . '</span>' : '<span style="color: red;">✗ ' . esc_html__( 'Not Available', 'hellaz-sitez-analyzer' ) . '</span>'; ?></td>
			</tr>
		</table>

		<h4><?php esc_html_e( 'Directory Permissions', 'hellaz-sitez-analyzer' ); ?></h4>
		<table class="form-table">
			<?php
			$directories = [
				'Upload Directory' => HSZ_UPLOAD_DIR,
				'Screenshots Directory' => HSZ_UPLOAD_DIR . 'screenshots/',
				'Reports Directory' => HSZ_UPLOAD_DIR . 'reports/',
				'Cache Directory' => HSZ_UPLOAD_DIR . 'cache/',
			];

			foreach ( $directories as $name => $path ) :
				$writable = is_writable( $path );
			?>
			<tr>
				<th scope="row"><?php echo esc_html( $name ); ?></th>
				<td>
					<?php echo $writable ? '<span style="color: green;">✓ ' . esc_html__( 'Writable', 'hellaz-sitez-analyzer' ) . '</span>' : '<span style="color: red;">✗ ' . esc_html__( 'Not Writable', 'hellaz-sitez-analyzer' ) . '</span>'; ?>
					<br><code><?php echo esc_html( $path ); ?></code>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>

		<h4><?php esc_html_e( 'Actions', 'hellaz-sitez-analyzer' ); ?></h4>
		<form method="post" action="">
			<?php wp_nonce_field( 'hsz_cleanup_files_nonce' ); ?>
			<input type="hidden" name="hsz_action" value="cleanup_files" />
			<?php submit_button( __( 'Clean Up Old Files', 'hellaz-sitez-analyzer' ), 'secondary' ); ?>
		</form>

		<form method="post" action="" style="margin-top: 10px;">
			<?php wp_nonce_field( 'hsz_reset_settings_nonce' ); ?>
			<input type="hidden" name="hsz_action" value="reset_settings" />
			<?php submit_button( __( 'Reset All Settings to Defaults', 'hellaz-sitez-analyzer' ), 'delete', 'reset-settings', true, [ 'onclick' => 'return confirm("' . esc_js( __( 'Are you sure you want to reset all settings to defaults?', 'hellaz-sitez-analyzer' ) ) . '");' ] ); ?>
		</form>
		<?php
	}

	/**
	 * Render About Tab - ENHANCED WITH NEW FEATURES
	 */
	private function render_about_tab(): void {
		?>
		<h3><?php esc_html_e( 'About HellaZ SiteZ Analyzer', 'hellaz-sitez-analyzer' ); ?></h3>

		<p><?php esc_html_e( 'HellaZ SiteZ Analyzer is a comprehensive website analysis tool that provides detailed insights into website performance, security, content quality, and usability.', 'hellaz-sitez-analyzer' ); ?></p>

		<h4><?php esc_html_e( 'Enhanced Features', 'hellaz-sitez-analyzer' ); ?></h4>
		<ul>
			<li><?php esc_html_e( 'Comprehensive metadata extraction (title, description, favicons, Open Graph, Twitter Cards).', 'hellaz-sitez-analyzer' ); ?></li>
			<li><?php esc_html_e( 'Performance analysis with Core Web Vitals and PageSpeed Insights integration.', 'hellaz-sitez-analyzer' ); ?></li>
			<li><?php esc_html_e( 'Advanced security analysis including SSL/TLS evaluation and vulnerability scanning.', 'hellaz-sitez-analyzer' ); ?></li>
			<li><?php esc_html_e( 'Website preview generation with multiple screenshot service integrations.', 'hellaz-sitez-analyzer' ); ?></li>
			<li><?php esc_html_e( 'Comprehensive grading system with customizable weight distribution.', 'hellaz-sitez-analyzer' ); ?></li>
			<li><?php esc_html_e( 'Social media profile discovery across 30+ platforms.', 'hellaz-sitez-analyzer' ); ?></li>
			<li><?php esc_html_e( 'Secure API key storage with encryption support.', 'hellaz-sitez-analyzer' ); ?></li>
			<li><?php esc_html_e( 'Efficient background bulk processing for large URL lists.', 'hellaz-sitez-analyzer' ); ?></li>
			<li><?php esc_html_e( 'Flexible frontend display via templates, widgets, and Gutenberg blocks.', 'hellaz-sitez-analyzer' ); ?></li>
		</ul>

		<h4><?php esc_html_e( 'Author & Source Code', 'hellaz-sitez-analyzer' ); ?></h4>
		<p>
			<?php esc_html_e( 'This plugin was created by HellaZ.', 'hellaz-sitez-analyzer' ); ?>
			<a href="https://www.hellaz.net/" target="_blank"><?php esc_html_e( 'Visit the author\'s website.', 'hellaz-sitez-analyzer' ); ?></a>
		</p>

		<p>
			<?php esc_html_e( 'The full source code is available on GitHub. We welcome contributions and bug reports.', 'hellaz-sitez-analyzer' ); ?>
			<a href="https://github.com/hellaz/HellaZ-SiteZ-Analyzer" target="_blank"><?php esc_html_e( 'View on GitHub.', 'hellaz-sitez-analyzer' ); ?></a>
		</p>

		<p><em><?php printf( esc_html__( 'Version %s - Enhanced Edition', 'hellaz-sitez-analyzer' ), HSZ_VERSION ); ?></em></p>
		<?php
	}

	/**
	 * Render Performance Settings Page
	 */
	public function render_performance_page(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Performance Analysis Settings', 'hellaz-sitez-analyzer' ); ?></h1>
			<?php $this->render_performance_tab(); ?>
		</div>
		<?php
	}

	/**
	 * Render Security Settings Page
	 */
	public function render_security_page(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Security Analysis Settings', 'hellaz-sitez-analyzer' ); ?></h1>
			<?php $this->render_security_tab(); ?>
		</div>
		<?php
	}

	/**
	 * Render Previews Settings Page
	 */
	public function render_previews_page(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Website Preview Settings', 'hellaz-sitez-analyzer' ); ?></h1>
			<?php $this->render_previews_tab(); ?>
		</div>
		<?php
	}
}
