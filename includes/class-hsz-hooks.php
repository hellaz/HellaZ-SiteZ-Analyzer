<?php
/**
 * Hooks management for HellaZ SiteZ Analyzer.
 *
 * Handles link modification, content filtering, and hook management.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.1.0
 */

namespace HSZ;

defined( 'ABSPATH' ) || exit;

class Hooks {

	/**
	 * Hook registry
	 *
	 * @var array
	 */
	private static $registered_hooks = [];

	/**
	 * Initialize hooks
	 */
	public static function init() {
		// Content modification hooks
		add_filter( 'the_content', [ __CLASS__, 'modify_external_links' ], 20 );
		add_filter( 'comment_text', [ __CLASS__, 'modify_external_links' ], 20 );
		add_filter( 'widget_text', [ __CLASS__, 'modify_external_links' ], 20 );

		// Security hooks
		add_filter( 'wp_redirect', [ __CLASS__, 'validate_redirect_url' ], 10, 2 );
		add_action( 'wp_head', [ __CLASS__, 'add_security_headers' ] );

		// Performance hooks
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'optimize_assets' ], 999 );
		add_filter( 'wp_resource_hints', [ __CLASS__, 'add_resource_hints' ], 10, 2 );

		// Analytics hooks
		add_action( 'wp_footer', [ __CLASS__, 'add_analytics_tracking' ] );

		// Register AJAX handlers
		self::register_ajax_handlers();

		// Mark hooks as registered
		self::$registered_hooks['content'] = true;
		self::$registered_hooks['security'] = true;
		self::$registered_hooks['performance'] = true;
		self::$registered_hooks['analytics'] = true;
	}

	/**
	 * Modify external links in content to add analysis attributes
	 *
	 * @param string $content Content to process
	 * @return string Modified content
	 */
	public static function modify_external_links( $content ) {
		if ( empty( $content ) || ! get_option( 'hsz_auto_analyze_links', false ) ) {
			return $content;
		}

		// Pattern to match <a> tags with href attributes
		$pattern = '/(<a\s+(?:[^>]*?\s+)?href=(["\'])(.*?)\2[^>]*>)/is';

		return preg_replace_callback(
			$pattern,
			[ __CLASS__, 'process_link_callback' ],
			$content
		);
	}

	/**
	 * Callback function to process individual links
	 *
	 * @param array $matches Regex matches
	 * @return string Modified link HTML
	 */
	private static function process_link_callback( $matches ) {
		$original_tag = $matches[1]; // The entire matched opening tag
		$url = $matches[3]; // The URL from the href attribute

		// Only process valid, external URLs
		if ( strpos( $url, 'http' ) === 0 && strpos( $url, home_url() ) === false ) {
			// This is an external link. Add data attributes.
			$data_attributes = sprintf(
				' data-hsz-analyzed="false" data-hsz-url="%s" data-hsz-external="true"',
				esc_attr( $url )
			);

			// Add security attributes if enabled
			if ( get_option( 'hsz_add_security_attributes', true ) ) {
				$data_attributes .= ' rel="noopener noreferrer"';
			}

			// Inject the data attributes into the <a> tag
			return str_replace( '<a ', '<a' . $data_attributes . ' ', $original_tag );
		}

		return $original_tag;
	}

	/**
	 * Validate redirect URLs for security
	 *
	 * @param string $location Redirect location
	 * @param int $status Redirect status code
	 * @return string Validated location
	 */
	public static function validate_redirect_url( $location, $status ) {
		// Check if redirect validation is enabled
		if ( ! get_option( 'hsz_validate_redirects', true ) ) {
			return $location;
		}

		// Allow internal redirects
		if ( strpos( $location, home_url() ) === 0 ) {
			return $location;
		}

		// Log external redirects for analysis
		Utils::log_error( 'External redirect detected: ' . $location, __FILE__, __LINE__ );

		// Check against blacklist
		$blacklisted_domains = get_option( 'hsz_redirect_blacklist', [] );
		if ( ! empty( $blacklisted_domains ) ) {
			$parsed_url = parse_url( $location );
			$host = $parsed_url['host'] ?? '';
			
			if ( in_array( $host, $blacklisted_domains, true ) ) {
				// Block redirect to blacklisted domain
				wp_die( __( 'Redirect blocked for security reasons.', 'hellaz-sitez-analyzer' ) );
			}
		}

		return $location;
	}

	/**
	 * Add security headers
	 */
	public static function add_security_headers() {
		if ( ! get_option( 'hsz_add_security_headers', false ) ) {
			return;
		}

		// Only add headers if not already set
		if ( ! headers_sent() ) {
			header( 'X-Content-Type-Options: nosniff' );
			header( 'X-Frame-Options: SAMEORIGIN' );
			header( 'X-XSS-Protection: 1; mode=block' );
			header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		}
	}

	/**
	 * Optimize asset loading
	 */
	public static function optimize_assets() {
		if ( ! get_option( 'hsz_optimize_assets', false ) ) {
			return;
		}

		global $wp_scripts, $wp_styles;

		// Defer non-critical JavaScript
		$defer_scripts = get_option( 'hsz_defer_scripts', [] );
		if ( ! empty( $defer_scripts ) && isset( $wp_scripts->registered ) ) {
			foreach ( $defer_scripts as $handle ) {
				if ( isset( $wp_scripts->registered[ $handle ] ) ) {
					$wp_scripts->registered[ $handle ]->extra['defer'] = true;
				}
			}
		}

		// Preload critical assets
		$preload_assets = get_option( 'hsz_preload_assets', [] );
		if ( ! empty( $preload_assets ) ) {
			foreach ( $preload_assets as $asset ) {
				if ( isset( $asset['href'] ) && isset( $asset['as'] ) ) {
					echo sprintf(
						'<link rel="preload" href="%s" as="%s"%s>',
						esc_url( $asset['href'] ),
						esc_attr( $asset['as'] ),
						isset( $asset['type'] ) ? ' type="' . esc_attr( $asset['type'] ) . '"' : ''
					);
				}
			}
		}
	}

	/**
	 * Add resource hints
	 *
	 * @param array $urls Resource hint URLs
	 * @param string $relation_type Relation type
	 * @return array Modified URLs
	 */
	public static function add_resource_hints( $urls, $relation_type ) {
		if ( ! get_option( 'hsz_add_resource_hints', false ) ) {
			return $urls;
		}

		$hints = get_option( 'hsz_resource_hints', [] );
		
		if ( isset( $hints[ $relation_type ] ) && is_array( $hints[ $relation_type ] ) ) {
			$urls = array_merge( $urls, $hints[ $relation_type ] );
		}

		return $urls;
	}

	/**
	 * Add analytics tracking
	 */
	public static function add_analytics_tracking() {
		if ( ! get_option( 'hsz_enable_analytics', false ) ) {
			return;
		}

		$tracking_id = get_option( 'hsz_analytics_tracking_id', '' );
		if ( empty( $tracking_id ) ) {
			return;
		}

		// Add tracking code
		?>
		<script>
		(function() {
			var hsz_analytics = {
				track: function(event, data) {
					if (typeof gtag !== 'undefined') {
						gtag('event', event, data);
					}
				},
				trackLinkAnalysis: function(url, results) {
					this.track('link_analysis', {
						'url': url,
						'security_score': results.security_score || 0,
						'performance_score': results.performance_score || 0
					});
				}
			};
			window.hszAnalytics = hsz_analytics;
		})();
		</script>
		<?php
	}

	/**
	 * Add analysis data to links via AJAX
	 */
	public static function ajax_analyze_link() {
		check_ajax_referer( 'hsz_link_analysis', 'nonce' );

		$url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '';

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid URL', 'hellaz-sitez-analyzer' ) ] );
		}

		try {
			// Quick analysis for link preview
			$html = Utils::get_html( $url );
			if ( is_wp_error( $html ) ) {
				throw new \Exception( $html->get_error_message() );
			}

			$metadata_extractor = new Metadata();
			$metadata = $metadata_extractor->extract_metadata( $url, $html );

			// Basic security check
			$security_status = self::check_link_security( $url );

			$response = [
				'title' => $metadata['title'] ?? parse_url( $url, PHP_URL_HOST ),
				'description' => $metadata['description'] ?? '',
				'favicon' => $metadata['favicon'] ?? '',
				'security' => $security_status,
				'safe' => $security_status['safe'] ?? true,
				'timestamp' => current_time( 'mysql' )
			];

			// Cache the result
			Cache::set( 'link_analysis_' . md5( $url ), $response, HOUR_IN_SECONDS, 'analysis' );

			wp_send_json_success( $response );

		} catch ( \Exception $e ) {
			Utils::log_error( 'Link analysis error: ' . $e->getMessage(), __FILE__, __LINE__ );
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}

	/**
	 * Basic security check for links
	 *
	 * @param string $url URL to check
	 * @return array Security status
	 */
	private static function check_link_security( $url ) {
		$security = [
			'safe' => true,
			'https' => strpos( $url, 'https://' ) === 0,
			'warnings' => [],
			'score' => 100
		];

		// Check against known malicious domains
		$blacklist = get_option( 'hsz_security_blacklist', [] );
		$parsed_url = parse_url( $url );
		$host = $parsed_url['host'] ?? '';

		if ( in_array( $host, $blacklist, true ) ) {
			$security['safe'] = false;
			$security['warnings'][] = __( 'Domain is on security blacklist', 'hellaz-sitez-analyzer' );
			$security['score'] = 0;
		}

		// Check for HTTPS
		if ( ! $security['https'] ) {
			$security['warnings'][] = __( 'Not using HTTPS encryption', 'hellaz-sitez-analyzer' );
			$security['score'] -= 20;
		}

		// Check for suspicious URL patterns
		$suspicious_patterns = [
			'/bit\.ly|tinyurl|t\.co/',
			'/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', // IP addresses
			'/[a-zA-Z0-9]{20,}\.com/', // Random long domains
		];

		foreach ( $suspicious_patterns as $pattern ) {
			if ( preg_match( $pattern, $url ) ) {
				$security['warnings'][] = __( 'Suspicious URL pattern detected', 'hellaz-sitez-analyzer' );
				$security['score'] -= 10;
				break;
			}
		}

		return $security;
	}

	/**
	 * Register AJAX handlers
	 */
	public static function register_ajax_handlers() {
		add_action( 'wp_ajax_hsz_analyze_link', [ __CLASS__, 'ajax_analyze_link' ] );
		add_action( 'wp_ajax_nopriv_hsz_analyze_link', [ __CLASS__, 'ajax_analyze_link' ] );
	}

	/**
	 * Enqueue frontend scripts for link analysis
	 */
	public static function enqueue_link_scripts() {
		if ( ! get_option( 'hsz_auto_analyze_links', false ) ) {
			return;
		}

		wp_enqueue_script(
			'hsz-link-analyzer',
			HSZ_ASSETS_URL . 'js/link-analyzer.js',
			[ 'jquery' ],
			HSZ_VERSION,
			true
		);

		wp_localize_script( 'hsz-link-analyzer', 'hszLinks', [
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'hsz_link_analysis' ),
			'strings' => [
				'analyzing' => __( 'Analyzing link...', 'hellaz-sitez-analyzer' ),
				'safe' => __( 'Safe', 'hellaz-sitez-analyzer' ),
				'warning' => __( 'Warning', 'hellaz-sitez-analyzer' ),
				'error' => __( 'Error', 'hellaz-sitez-analyzer' )
			],
			'settings' => [
				'auto_analyze' => get_option( 'hsz_auto_analyze_links', false ),
				'show_tooltips' => get_option( 'hsz_show_link_tooltips', true ),
				'delay_ms' => get_option( 'hsz_analysis_delay', 1000 )
			]
		]);
	}

	/**
	 * Check if specific hooks are registered
	 *
	 * @param string $hook_type Hook type to check
	 * @return bool
	 */
	public static function is_hook_registered( $hook_type ) {
		return isset( self::$registered_hooks[ $hook_type ] ) && self::$registered_hooks[ $hook_type ];
	}

	/**
	 * Get hook statistics
	 *
	 * @return array Hook statistics
	 */
	public static function get_hook_stats() {
		return [
			'registered_hooks' => count( self::$registered_hooks ),
			'active_hooks' => array_keys( array_filter( self::$registered_hooks ) ),
			'content_filtering' => self::is_hook_registered( 'content' ),
			'security_features' => self::is_hook_registered( 'security' ),
			'performance_optimization' => self::is_hook_registered( 'performance' ),
			'analytics_tracking' => self::is_hook_registered( 'analytics' )
		];
	}

	/**
	 * Remove all registered hooks (for deactivation)
	 */
	public static function remove_all_hooks() {
		// Remove content hooks
		remove_filter( 'the_content', [ __CLASS__, 'modify_external_links' ], 20 );
		remove_filter( 'comment_text', [ __CLASS__, 'modify_external_links' ], 20 );
		remove_filter( 'widget_text', [ __CLASS__, 'modify_external_links' ], 20 );

		// Remove security hooks
		remove_filter( 'wp_redirect', [ __CLASS__, 'validate_redirect_url' ], 10 );
		remove_action( 'wp_head', [ __CLASS__, 'add_security_headers' ] );

		// Remove performance hooks
		remove_action( 'wp_enqueue_scripts', [ __CLASS__, 'optimize_assets' ], 999 );
		remove_filter( 'wp_resource_hints', [ __CLASS__, 'add_resource_hints' ], 10 );

		// Remove analytics hooks
		remove_action( 'wp_footer', [ __CLASS__, 'add_analytics_tracking' ] );

		// Clear registry
		self::$registered_hooks = [];
	}
}
