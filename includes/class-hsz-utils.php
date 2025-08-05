<?php
/**
 * Utility class for various helper functions.
 *
 * This class provides static methods for common tasks such as making HTTP requests,
 * parsing HTML content, handling data encryption/decryption, caching, logging,
 * performance monitoring, and enhanced Phase 1 utilities.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.0.2
 */

namespace HSZ;

use DOMDocument;
use DOMXPath;
use WP_Error;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Utils
 *
 * Provides a suite of static utility methods for the plugin.
 */
class Utils {

	private const CIPHER = 'aes-256-cbc';
	private static array $timers = [];
	private static array $api_rate_limits = [];

	// EXISTING ENCRYPTION METHODS - Maintain all functionality

	public static function is_encryption_configured(): bool {
		return defined( 'HSZ_ENCRYPTION_KEY' ) && is_string( HSZ_ENCRYPTION_KEY ) && ! empty( HSZ_ENCRYPTION_KEY );
	}

	public static function sanitize_and_encrypt( $value ) {
		if ( ! is_string( $value ) || empty( $value ) ) {
			return $value;
		}

		$sanitized_value = esc_url_raw( $value );

		if ( ! self::is_encryption_configured() ) {
			return $sanitized_value;
		}

		return self::encrypt( $sanitized_value );
	}

	public static function encrypt( string $data ) {
		if ( ! self::is_encryption_configured() ) {
			return false;
		}

		$key = HSZ_ENCRYPTION_KEY;
		$iv_length = openssl_cipher_iv_length( self::CIPHER );

		if ( false === $iv_length ) {
			return false;
		}

		$iv = openssl_random_pseudo_bytes( $iv_length );
		$encrypted = openssl_encrypt( $data, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv );

		if ( false === $encrypted ) {
			return false;
		}

		return base64_encode( $iv . $encrypted );
	}

	public static function decrypt( string $data ) {
		if ( ! self::is_encryption_configured() || empty( $data ) ) {
			return false;
		}

		$key = HSZ_ENCRYPTION_KEY;
		$data = base64_decode( $data, true );

		if ( false === $data ) {
			return false;
		}

		$iv_length = openssl_cipher_iv_length( self::CIPHER );
		if ( false === $iv_length || mb_strlen( $data, '8bit' ) < $iv_length ) {
			return false;
		}

		$iv = mb_substr( $data, 0, $iv_length, '8bit' );
		$ciphertext = mb_substr( $data, $iv_length, null, '8bit' );

		return openssl_decrypt( $ciphertext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv );
	}

	// EXISTING HTTP METHODS - Enhanced with better error handling

	/**
	 * Retrieves the HTML content of a given URL.
	 * 
	 * @param string $url The URL to fetch.
	 * @param array $args Additional arguments for the HTTP request.
	 * @return string|WP_Error The HTML content as a string or a WP_Error on failure.
	 */
	public static function get_html( string $url, array $args = [] ) {
		// Enhanced default arguments
		$default_args = [
			'timeout' => get_option( 'hsz_api_timeout', 30 ),
			'user-agent' => 'HellaZ SiteZ Analyzer/' . HSZ_VERSION . ' (+https://hellaz.net)',
			'headers' => [
				'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
				'Accept-Language' => 'en-US,en;q=0.5',
				'Accept-Encoding' => 'gzip, deflate',
				'Cache-Control' => 'no-cache',
			],
			'redirection' => 5,
			'httpversion' => '1.1',
		];

		$args = wp_parse_args( $args, $default_args );

		if ( get_option( 'hsz_disable_ssl_verify', 0 ) ) {
			$args['sslverify'] = false;
		}

		// Check rate limiting
		if ( ! self::check_rate_limit( 'http_requests' ) ) {
			return new WP_Error( 'rate_limit_exceeded', __( 'Rate limit exceeded. Please try again later.', 'hellaz-sitez-analyzer' ) );
		}

		$response = wp_remote_get( esc_url_raw( $url ), $args );

		if ( is_wp_error( $response ) ) {
			self::log_error( 'Failed to fetch URL: ' . $url . ' - ' . $response->get_error_message(), __FILE__, __LINE__ );
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code < 200 || $response_code >= 400 ) {
			$error_message = sprintf( __( 'HTTP request failed with status code: %d', 'hellaz-sitez-analyzer' ), $response_code );
			self::log_error( $error_message . ' for URL: ' . $url, __FILE__, __LINE__ );
			return new WP_Error( 'http_request_failed', $error_message );
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Gets the HTTP status code for a given URL.
	 *
	 * @param string $url The URL to check.
	 * @param array $args Additional arguments for the HTTP request.
	 * @return int|WP_Error The HTTP status code or a WP_Error on failure.
	 */
	public static function get_http_status( string $url, array $args = [] ) {
		$default_args = [
			'timeout' => get_option( 'hsz_api_timeout', 30 ),
			'user-agent' => 'HellaZ SiteZ Analyzer/' . HSZ_VERSION,
		];

		$args = wp_parse_args( $args, $default_args );

		if ( get_option( 'hsz_disable_ssl_verify', 0 ) ) {
			$args['sslverify'] = false;
		}

		$response = wp_remote_head( esc_url_raw( $url ), $args );

		if ( is_wp_error( $response ) ) {
			self::log_error( 'Failed to get HTTP status for URL: ' . $url . ' - ' . $response->get_error_message(), __FILE__, __LINE__ );
			return $response;
		}

		return wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Enhanced HTTP POST request with better error handling
	 *
	 * @param string $url The URL to POST to.
	 * @param array $data The data to POST.
	 * @param array $args Additional arguments.
	 * @return array|WP_Error Response array or WP_Error on failure.
	 */
	public static function post_request( string $url, array $data = [], array $args = [] ) {
		$default_args = [
			'timeout' => get_option( 'hsz_api_timeout', 30 ),
			'user-agent' => 'HellaZ SiteZ Analyzer/' . HSZ_VERSION,
			'body' => $data,
		];

		$args = wp_parse_args( $args, $default_args );

		if ( get_option( 'hsz_disable_ssl_verify', 0 ) ) {
			$args['sslverify'] = false;
		}

		// Check rate limiting
		if ( ! self::check_rate_limit( 'http_requests' ) ) {
			return new WP_Error( 'rate_limit_exceeded', __( 'Rate limit exceeded. Please try again later.', 'hellaz-sitez-analyzer' ) );
		}

		$response = wp_remote_post( esc_url_raw( $url ), $args );

		if ( is_wp_error( $response ) ) {
			self::log_error( 'POST request failed for URL: ' . $url . ' - ' . $response->get_error_message(), __FILE__, __LINE__ );
			return $response;
		}

		return $response;
	}

	// EXISTING HTML PARSING METHODS - Maintain all functionality

	/**
	 * Parses HTML to extract meta tags.
	 *
	 * @param string $html The HTML content.
	 * @return array An associative array of meta tags.
	 */
	public static function get_meta_tags( string $html ): array {
		if ( empty( $html ) ) {
			return [];
		}
		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$tags = $dom->getElementsByTagName( 'meta' );
		$meta_data = [];
		foreach ( $tags as $tag ) {
			$name = $tag->getAttribute( 'name' );
			if ( $name ) {
				$meta_data[ $name ] = $tag->getAttribute( 'content' );
			}
		}
		return $meta_data;
	}
	
	/**
	 * Extracts Open Graph (OG) tags from HTML.
	 *
	 * @param string $html The HTML content.
	 * @return array An associative array of OG tags.
	 */
	public static function get_og_tags( string $html ): array {
		if ( empty( $html ) ) {
			return [];
		}
		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$tags = $dom->getElementsByTagName( 'meta' );
		$og_data = [];
		foreach ( $tags as $tag ) {
			$property = $tag->getAttribute( 'property' );
			if ( strpos( $property, 'og:' ) === 0 ) {
				$og_data[ substr( $property, 3 ) ] = $tag->getAttribute( 'content' );
			}
		}
		return $og_data;
	}
	
	/**
	 * Extracts Twitter Card tags from HTML.
	 *
	 * @param string $html The HTML content.
	 * @return array An associative array of Twitter tags.
	 */
	public static function get_twitter_tags( string $html ): array {
		if ( empty( $html ) ) {
			return [];
		}
		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$tags = $dom->getElementsByTagName( 'meta' );
		$twitter_data = [];
		foreach ( $tags as $tag ) {
			$name = $tag->getAttribute( 'name' );
			if ( strpos( $name, 'twitter:' ) === 0 ) {
				$twitter_data[ substr( $name, 8 ) ] = $tag->getAttribute( 'content' );
			}
		}
		return $twitter_data;
	}
	
	/**
	 * Extracts the <title> tag from HTML.
	 *
	 * @param string $html The HTML content.
	 * @return string The title content.
	 */
	public static function get_title_tag( string $html ): string {
		if ( empty( $html ) ) {
			return '';
		}
		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$title_node = $dom->getElementsByTagName( 'title' );
		return $title_node->length > 0 ? trim( $title_node->item( 0 )->nodeValue ) : '';
	}
	
	/**
	 * Extracts the favicon URL from HTML.
	 *
	 * @param string $html The HTML content.
	 * @param string $base_url The base URL to resolve relative URLs.
	 * @return string|false The favicon URL or false if not found.
	 */
	public static function get_favicon( string $html, string $base_url ) {
		if ( empty( $html ) ) {
			return false;
		}
		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$xpath = new DOMXPath( $dom );
		$links = $xpath->query( "//link[contains(@rel, 'icon') or contains(@rel, 'shortcut icon')]" );
		if ( $links->length > 0 ) {
			$href = $links->item( 0 )->getAttribute( 'href' );
			return self::resolve_url( $href, $base_url );
		}
		return self::resolve_url( '/favicon.ico', $base_url );
	}
	
	/**
	 * Extracts all image URLs from HTML.
	 *
	 * @param string $html The HTML content.
	 * @param string $base_url The base URL to resolve relative URLs.
	 * @return array An array of image URLs.
	 */
	public static function get_images_from_html( string $html, string $base_url ): array {
		if ( empty( $html ) ) {
			return [];
		}
		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$images = $dom->getElementsByTagName( 'img' );
		$image_urls = [];
		foreach ( $images as $image ) {
			$src = $image->getAttribute( 'src' );
			if ( $src ) {
				$image_urls[] = self::resolve_url( $src, $base_url );
			}
		}
		return $image_urls;
	}
	
	/**
	 * Extracts all hyperlink URLs from HTML.
	 *
	 * @param string $html The HTML content.
	 * @param string $base_url The base URL to resolve relative URLs.
	 * @return array An array of link URLs.
	 */
	public static function get_all_links( string $html, string $base_url ): array {
		if ( empty( $html ) ) {
			return [];
		}
		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$links = $dom->getElementsByTagName( 'a' );
		$link_urls = [];
		foreach ( $links as $link ) {
			$href = $link->getAttribute( 'href' );
			if ( $href ) {
				$link_urls[] = self::resolve_url( $href, $base_url );
			}
		}
		return $link_urls;
	}
	
	/**
	 * Gets the dimensions of an image.
	 *
	 * @param string $image_url The URL of the image.
	 * @return array|false The image dimensions or false on failure.
	 */
	public static function get_image_dimensions( string $image_url ) {
		$size = @getimagesize( esc_url_raw( $image_url ) );
		if ( false === $size ) {
			return false;
		}
		return [
			'width' => $size[0],
			'height' => $size[1],
		];
	}
	
	/**
	 * Resolves a URL, converting relative URLs to absolute.
	 *
	 * @param string $url The URL to resolve.
	 * @param string $base_url The base URL for context.
	 * @return string The resolved, absolute URL.
	 */
	public static function resolve_url( string $url, string $base_url ): string {
		if ( strpos( $url, '//' ) === 0 ) {
			$base_parts = parse_url( $base_url );
			return ( $base_parts['scheme'] ?? 'http' ) . ':' . $url;
		}
		if ( parse_url( $url, PHP_URL_SCHEME ) !== null ) {
			return $url;
		}
		$base_parts = parse_url( $base_url );
		$base_root = ( $base_parts['scheme'] ?? 'http' ) . '://' . ( $base_parts['host'] ?? '' );
		if ( strpos( $url, '/' ) === 0 ) {
			return $base_root . $url;
		}
		$path = dirname( $base_parts['path'] ?? '' );
		return $base_root . ( $path === '/' ? '' : $path ) . '/' . $url;
	}

	// EXISTING LOGGING METHODS - Enhanced with better formatting

	/**
	 * Logs an error message to the WordPress debug log and plugin database.
	 * 
	 * @param string $message The error message.
	 * @param string $file The file where the error occurred.
	 * @param int $line The line number where the error occurred.
	 * @param string $level The log level (error, warning, info, debug).
	 */
	public static function log_error( string $message, string $file = __FILE__, int $line = __LINE__, string $level = 'error' ): void {
		// Enhanced logging with more context
		$log_message = sprintf( 
			'[HellaZ SiteZ Analyzer] [%s] %s in %s on line %d', 
			strtoupper( $level ), 
			$message, 
			basename( $file ), 
			$line 
		);

		// Log to WordPress debug log if enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( $log_message );
		}

		// Log to plugin database table if available
		self::log_to_database( $message, $level, [
			'file' => basename( $file ),
			'line' => $line,
			'timestamp' => current_time( 'mysql', true ),
			'user_id' => get_current_user_id(),
			'url' => $_SERVER['REQUEST_URI'] ?? '',
		]);
	}

	/**
	 * Log message to plugin database table
	 *
	 * @param string $message Log message.
	 * @param string $level Log level.
	 * @param array $context Additional context.
	 */
	private static function log_to_database( string $message, string $level, array $context = [] ): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'hsz_error_log';
		
		// Check if table exists
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			return;
		}

		$wpdb->insert( 
			$table_name,
			[
				'error_code' => $level,
				'message' => $message,
				'context' => wp_json_encode( $context ),
				'created_at' => current_time( 'mysql', true )
			],
			[ '%s', '%s', '%s', '%s' ]
		);
	}

	// EXISTING CACHE METHODS - Enhanced with database caching

	public static function set_cache( string $key, $data, int $expiration = HOUR_IN_SECONDS ): bool {
		// Use transients for backward compatibility
		$transient_result = set_transient( 'hsz_cache_' . $key, $data, $expiration );

		// Also store in enhanced database cache if table exists
		self::set_database_cache( $key, $data, $expiration );

		return $transient_result;
	}
	
	public static function get_cache( string $key ) {
		// Try transients first for backward compatibility
		$transient_data = get_transient( 'hsz_cache_' . $key );
		if ( $transient_data !== false ) {
			return $transient_data;
		}

		// Try enhanced database cache
		return self::get_database_cache( $key );
	}

	public static function delete_cache( string $key ): bool {
		// Delete from transients
		$transient_result = delete_transient( 'hsz_cache_' . $key );

		// Delete from database cache
		self::delete_database_cache( $key );

		return $transient_result;
	}

	/**
	 * Enhanced database caching methods
	 */
	private static function set_database_cache( string $key, $data, int $expiration ): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'hsz_analysis_cache';
		
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			return;
		}

		$cache_key = md5( $key );
		$expires_at = date( 'Y-m-d H:i:s', time() + $expiration );

		$wpdb->replace(
			$table_name,
			[
				'cache_key' => $cache_key,
				'url' => substr( $key, 0, 500 ), // Store original key truncated
				'data' => wp_json_encode( $data ),
				'created_at' => current_time( 'mysql', true ),
				'expires_at' => $expires_at
			],
			[ '%s', '%s', '%s', '%s', '%s' ]
		);
	}

	private static function get_database_cache( string $key ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'hsz_analysis_cache';
		
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			return false;
		}

		$cache_key = md5( $key );
		$result = $wpdb->get_row( $wpdb->prepare(
			"SELECT data FROM {$table_name} WHERE cache_key = %s AND expires_at > %s",
			$cache_key,
			current_time( 'mysql', true )
		) );

		if ( $result ) {
			return json_decode( $result->data, true );
		}

		return false;
	}

	private static function delete_database_cache( string $key ): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'hsz_analysis_cache';
		
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			return;
		}

		$cache_key = md5( $key );
		$wpdb->delete( $table_name, [ 'cache_key' => $cache_key ], [ '%s' ] );
	}

	// EXISTING TIMER METHODS - Maintain all functionality

	public static function start_timer( string $key ): void {
		self::$timers[ $key ] = microtime( true );
	}

	public static function stop_timer( string $key ) {
		if ( isset( self::$timers[ $key ] ) ) {
			$elapsed = microtime( true ) - self::$timers[ $key ];
			unset( self::$timers[ $key ] );
			return $elapsed;
		}
		return false;
	}

	// ENHANCED PHASE 1 UTILITY METHODS

	/**
	 * Rate limiting for API requests
	 *
	 * @param string $service Service identifier.
	 * @param int $limit Rate limit per hour.
	 * @return bool True if within rate limit.
	 */
	public static function check_rate_limit( string $service, int $limit = 0 ): bool {
		if ( $limit === 0 ) {
			$limit = get_option( 'hsz_api_rate_limit', 100 );
		}

		$current_hour = date( 'Y-m-d-H' );
		$rate_key = $service . '_' . $current_hour;

		$current_count = get_transient( 'hsz_rate_limit_' . $rate_key );
		if ( $current_count === false ) {
			$current_count = 0;
		}

		if ( $current_count >= $limit ) {
			return false;
		}

		// Increment counter
		set_transient( 'hsz_rate_limit_' . $rate_key, $current_count + 1, HOUR_IN_SECONDS );
		
		return true;
	}

	/**
	 * Enhanced URL validation with additional checks
	 *
	 * @param string $url URL to validate.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	public static function validate_url( string $url ) {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return new WP_Error( 'invalid_url', __( 'Invalid URL format.', 'hellaz-sitez-analyzer' ) );
		}

		$parsed_url = parse_url( $url );
		
		// Check for required components
		if ( ! isset( $parsed_url['scheme'] ) || ! isset( $parsed_url['host'] ) ) {
			return new WP_Error( 'incomplete_url', __( 'URL must include scheme and host.', 'hellaz-sitez-analyzer' ) );
		}

		// Check for allowed schemes
		$allowed_schemes = [ 'http', 'https' ];
		if ( ! in_array( $parsed_url['scheme'], $allowed_schemes, true ) ) {
			return new WP_Error( 'invalid_scheme', __( 'Only HTTP and HTTPS URLs are allowed.', 'hellaz-sitez-analyzer' ) );
		}

		// Check for blocked hosts
		$blocked_hosts = [ 'localhost', '127.0.0.1', '0.0.0.0' ];
		if ( in_array( $parsed_url['host'], $blocked_hosts, true ) ) {
			return new WP_Error( 'blocked_host', __( 'Local and internal URLs are not allowed.', 'hellaz-sitez-analyzer' ) );
		}

		return true;
	}

	/**
	 * Sanitize and validate grade values
	 *
	 * @param mixed $grade Grade value to sanitize.
	 * @return string Valid grade (A-F).
	 */
	public static function sanitize_grade( $grade ): string {
		$valid_grades = [ 'A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'D-', 'F' ];
		
		if ( is_string( $grade ) && in_array( strtoupper( $grade ), $valid_grades, true ) ) {
			return strtoupper( $grade );
		}

		// Convert numeric scores to letter grades
		if ( is_numeric( $grade ) ) {
			$score = (int) $grade;
			if ( $score >= 97 ) return 'A+';
			if ( $score >= 93 ) return 'A';
			if ( $score >= 90 ) return 'A-';
			if ( $score >= 87 ) return 'B+';
			if ( $score >= 83 ) return 'B';
			if ( $score >= 80 ) return 'B-';
			if ( $score >= 77 ) return 'C+';
			if ( $score >= 73 ) return 'C';
			if ( $score >= 70 ) return 'C-';
			if ( $score >= 67 ) return 'D+';
			if ( $score >= 63 ) return 'D';
			if ( $score >= 60 ) return 'D-';
		}

		return 'F';
	}

	/**
	 * Convert letter grade to numeric score
	 *
	 * @param string $grade Letter grade.
	 * @return int Numeric score (0-100).
	 */
	public static function grade_to_score( string $grade ): int {
		$grade_scores = [
			'A+' => 100, 'A' => 95, 'A-' => 90,
			'B+' => 87, 'B' => 83, 'B-' => 80,
			'C+' => 77, 'C' => 73, 'C-' => 70,
			'D+' => 67, 'D' => 63, 'D-' => 60,
			'F' => 0
		];

		return $grade_scores[ strtoupper( $grade ) ] ?? 0;
	}

	/**
	 * Generate a unique hash for URLs for database storage
	 *
	 * @param string $url URL to hash.
	 * @return string MD5 hash of the URL.
	 */
	public static function generate_url_hash( string $url ): string {
		// Normalize URL for consistent hashing
		$normalized_url = strtolower( trim( $url ) );
		$normalized_url = rtrim( $normalized_url, '/' );
		
		return md5( $normalized_url );
	}

	/**
	 * Format file size in human readable format
	 *
	 * @param int $bytes File size in bytes.
	 * @return string Formatted file size.
	 */
	public static function format_file_size( int $bytes ): string {
		$units = [ 'B', 'KB', 'MB', 'GB', 'TB' ];
		
		for ( $i = 0; $bytes > 1024 && $i < count( $units ) - 1; $i++ ) {
			$bytes /= 1024;
		}
		
		return round( $bytes, 2 ) . ' ' . $units[ $i ];
	}

	/**
	 * Format time duration in human readable format
	 *
	 * @param float $seconds Duration in seconds.
	 * @return string Formatted duration.
	 */
	public static function format_duration( float $seconds ): string {
		if ( $seconds < 1 ) {
			return round( $seconds * 1000 ) . 'ms';
		}
		
		if ( $seconds < 60 ) {
			return round( $seconds, 2 ) . 's';
		}
		
		$minutes = floor( $seconds / 60 );
		$remaining_seconds = $seconds % 60;
		
		return $minutes . 'm ' . round( $remaining_seconds ) . 's';
	}

	/**
	 * Get domain from URL
	 *
	 * @param string $url URL to extract domain from.
	 * @return string Domain name.
	 */
	public static function get_domain( string $url ): string {
		$parsed_url = parse_url( $url );
		return $parsed_url['host'] ?? '';
	}

	/**
	 * Check if URL is HTTPS
	 *
	 * @param string $url URL to check.
	 * @return bool True if HTTPS.
	 */
	public static function is_https( string $url ): bool {
		$parsed_url = parse_url( $url );
		return isset( $parsed_url['scheme'] ) && $parsed_url['scheme'] === 'https';
	}

	/**
	 * Extract all JSON-LD structured data from HTML
	 *
	 * @param string $html HTML content.
	 * @return array Array of JSON-LD objects.
	 */
	public static function extract_json_ld( string $html ): array {
		if ( empty( $html ) ) {
			return [];
		}

		$json_ld_data = [];
		
		// Match all JSON-LD script tags
		if ( preg_match_all( '/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches ) ) {
			foreach ( $matches[1] as $json_string ) {
				$decoded = json_decode( trim( $json_string ), true );
				if ( json_last_error() === JSON_ERROR_NONE && $decoded ) {
					$json_ld_data[] = $decoded;
				}
			}
		}

		return $json_ld_data;
	}

	/**
	 * Enhanced error handling with retry logic
	 *
	 * @param callable $callback Function to execute.
	 * @param int $max_retries Maximum number of retries.
	 * @param int $delay_seconds Delay between retries in seconds.
	 * @return mixed Result of callback or WP_Error.
	 */
	public static function with_retry( callable $callback, int $max_retries = 3, int $delay_seconds = 1 ) {
		$attempts = 0;
		$last_error = null;

		while ( $attempts < $max_retries ) {
			try {
				$result = call_user_func( $callback );
				
				if ( ! is_wp_error( $result ) ) {
					return $result;
				}
				
				$last_error = $result;
			} catch ( \Throwable $e ) {
				$last_error = new WP_Error( 'callback_exception', $e->getMessage() );
			}

			$attempts++;
			
			if ( $attempts < $max_retries ) {
				sleep( $delay_seconds );
			}
		}

		self::log_error( "Failed after {$max_retries} attempts: " . ( $last_error ? $last_error->get_error_message() : 'Unknown error' ), __FILE__, __LINE__ );
		
		return $last_error ?: new WP_Error( 'max_retries_exceeded', __( 'Maximum retry attempts exceeded.', 'hellaz-sitez-analyzer' ) );
	}

	/**
	 * Clean up and validate HTML content
	 *
	 * @param string $html Raw HTML content.
	 * @return string Cleaned HTML.
	 */
	public static function clean_html( string $html ): string {
		// Remove potentially harmful content
		$html = preg_replace( '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html );
		$html = preg_replace( '/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $html );
		
		// Remove comments
		$html = preg_replace( '/<!--(.|\s)*?-->/', '', $html );
		
		// Normalize whitespace
		$html = preg_replace( '/\s+/', ' ', $html );
		
		return trim( $html );
	}

	/**
	 * Get memory usage information
	 *
	 * @return array Memory usage statistics.
	 */
	public static function get_memory_usage(): array {
		$memory_limit = ini_get( 'memory_limit' );
		$memory_usage = memory_get_usage( true );
		$memory_peak = memory_get_peak_usage( true );

		return [
			'limit' => $memory_limit,
			'usage' => self::format_file_size( $memory_usage ),
			'peak' => self::format_file_size( $memory_peak ),
			'usage_bytes' => $memory_usage,
			'peak_bytes' => $memory_peak,
		];
	}

	/**
	 * Check system requirements for enhanced features
	 *
	 * @return array System status information.
	 */
	public static function check_system_requirements(): array {
		$requirements = [
			'php_version' => [
				'required' => '7.4.0',
				'current' => PHP_VERSION,
				'status' => version_compare( PHP_VERSION, '7.4.0', '>=' )
			],
			'curl_extension' => [
				'required' => true,
				'current' => extension_loaded( 'curl' ),
				'status' => extension_loaded( 'curl' )
			],
			'json_extension' => [
				'required' => true,
				'current' => extension_loaded( 'json' ),
				'status' => extension_loaded( 'json' )
			],
			'gd_extension' => [
				'required' => false,
				'current' => extension_loaded( 'gd' ),
				'status' => extension_loaded( 'gd' )
			],
			'openssl_extension' => [
				'required' => false,
				'current' => extension_loaded( 'openssl' ),
				'status' => extension_loaded( 'openssl' )
			],
			'memory_limit' => [
				'required' => '128M',
				'current' => ini_get( 'memory_limit' ),
				'status' => true // Always true as we can't easily compare memory limits
			]
		];

		return $requirements;
	}

	/**
	 * Record API usage statistics
	 *
	 * @param string $service API service name.
	 * @param string $endpoint API endpoint.
	 * @param float $response_time Response time in seconds.
	 * @param bool $success Whether the request was successful.
	 */
	public static function record_api_usage( string $service, string $endpoint, float $response_time, bool $success ): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'hsz_api_usage';
		
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			return;
		}

		$date_created = current_time( 'Y-m-d' );

		// Try to update existing record
		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE service = %s AND date_created = %s",
			$service,
			$date_created
		) );

		if ( $existing ) {
			// Update existing record
			$wpdb->update(
				$table_name,
				[
					'requests_count' => $existing->requests_count + 1,
					'success_count' => $existing->success_count + ( $success ? 1 : 0 ),
					'error_count' => $existing->error_count + ( $success ? 0 : 1 ),
					'total_response_time' => $existing->total_response_time + $response_time,
					'avg_response_time' => ( $existing->total_response_time + $response_time ) / ( $existing->requests_count + 1 ),
					'last_request_at' => current_time( 'mysql', true )
				],
				[ 'id' => $existing->id ],
				[ '%d', '%d', '%d', '%f', '%f', '%s' ],
				[ '%d' ]
			);
		} else {
			// Insert new record
			$wpdb->insert(
				$table_name,
				[
					'service' => $service,
					'endpoint' => $endpoint,
					'requests_count' => 1,
					'success_count' => $success ? 1 : 0,
					'error_count' => $success ? 0 : 1,
					'total_response_time' => $response_time,
					'avg_response_time' => $response_time,
					'last_request_at' => current_time( 'mysql', true ),
					'date_created' => $date_created
				],
				[ '%s', '%s', '%d', '%d', '%d', '%f', '%f', '%s', '%s' ]
			);
		}
	}
}
