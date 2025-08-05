<?php
/**
 * Enhanced API manager for HellaZ SiteZ Analyzer.
 *
 * This class handles all external API requests with advanced features including
 * rate limiting, retry logic, caching, error handling, and performance monitoring
 * with Phase 1 enhancements.
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
 * Class APIManager
 *
 * Manages all external API communications with enhanced capabilities.
 */
class APIManager {

	/**
	 * API rate limits (requests per hour unless specified)
	 */
	private const RATE_LIMITS = [
		'ssl_labs' => 25,
		'virustotal' => 4, // per minute
		'builtwith' => 200, // per month
		'urlscan' => 100, // per day
		'pagespeed' => 25000, // per day
		'webpagetest' => 200, // per day
		'default' => 100
	];

	/**
	 * Default retry configuration
	 */
	private const RETRY_CONFIG = [
		'max_attempts' => 3,
		'base_delay' => 1, // seconds
		'max_delay' => 30, // seconds
		'backoff_multiplier' => 2
	];

	/**
	 * Request statistics
	 *
	 * @var array
	 */
	private static $request_stats = [];

	/**
	 * Enhanced API request with comprehensive error handling and monitoring
	 *
	 * @param string $url API endpoint URL.
	 * @param array $args Request arguments.
	 * @param string $cache_key Cache key for storing results.
	 * @param int $cache_duration Cache duration in seconds.
	 * @param array $options Additional options.
	 * @return array API response data or empty array on failure.
	 */
	public function make_api_request( string $url, array $args = [], string $cache_key = '', int $cache_duration = HOUR_IN_SECONDS, array $options = [] ): array {
		// Parse options
		$options = wp_parse_args( $options, [
			'service' => $this->detect_service_from_url( $url ),
			'retry' => true,
			'rate_limit_check' => true,
			'cache_enabled' => true,
			'timeout_override' => null
		]);

		// Check cache first if enabled
		if ( $options['cache_enabled'] && ! empty( $cache_key ) ) {
			$cached_data = Cache::get( $cache_key, 'api' );
			if ( $cached_data !== false ) {
				$this->record_request_stat( $options['service'], 'cache_hit', 0, true );
				return $cached_data;
			}
		}

		// Rate limiting check
		if ( $options['rate_limit_check'] && ! $this->check_rate_limit( $options['service'] ) ) {
			Utils::log_error( "Rate limit exceeded for service: {$options['service']}", __FILE__, __LINE__ );
			$this->record_request_stat( $options['service'], 'rate_limited', 0, false );
			return [ 'error' => __( 'API rate limit exceeded. Please try again later.', 'hellaz-sitez-analyzer' ) ];
		}

		// Make the request with retry logic
		if ( $options['retry'] ) {
			$response = $this->make_request_with_retry( $url, $args, $options );
		} else {
			$response = $this->make_single_request( $url, $args, $options );
		}

		// Handle response
		if ( is_wp_error( $response ) ) {
			$this->record_request_stat( $options['service'], 'error', 0, false );
			Utils::log_error( "API request failed for {$url}: " . $response->get_error_message(), __FILE__, __LINE__ );
			return [ 'error' => $response->get_error_message() ];
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_time = $response['response_time'] ?? 0;

		if ( $response_code !== 200 ) {
			$this->record_request_stat( $options['service'], 'http_error', $response_time, false );
			$error_message = sprintf( __( 'API returned HTTP %d', 'hellaz-sitez-analyzer' ), $response_code );
			Utils::log_error( "API request failed for {$url}: {$error_message}", __FILE__, __LINE__ );
			return [ 'error' => $error_message ];
		}

		// Parse response body
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE && ! empty( $body ) ) {
			$this->record_request_stat( $options['service'], 'parse_error', $response_time, false );
			Utils::log_error( "JSON parse error for {$url}: " . json_last_error_msg(), __FILE__, __LINE__ );
			return [ 'error' => __( 'Invalid JSON response from API', 'hellaz-sitez-analyzer' ) ];
		}

		$data = $data ?? [];
		$this->record_request_stat( $options['service'], 'success', $response_time, true );

		// Cache successful response
		if ( $options['cache_enabled'] && ! empty( $cache_key ) && ! empty( $data ) ) {
			Cache::set( $cache_key, $data, $cache_duration, 'api' );
		}

		// Record API usage statistics
		Utils::record_api_usage( $options['service'], $this->extract_endpoint_from_url( $url ), $response_time, true );

		return $data;
	}

	/**
	 * Make request with retry logic
	 *
	 * @param string $url Request URL.
	 * @param array $args Request arguments.
	 * @param array $options Request options.
	 * @return array|\WP_Error Response or error.
	 */
	private function make_request_with_retry( string $url, array $args, array $options ) {
		$max_attempts = self::RETRY_CONFIG['max_attempts'];
		$base_delay = self::RETRY_CONFIG['base_delay'];
		$max_delay = self::RETRY_CONFIG['max_delay'];
		$backoff_multiplier = self::RETRY_CONFIG['backoff_multiplier'];

		$last_error = null;

		for ( $attempt = 1; $attempt <= $max_attempts; $attempt++ ) {
			$response = $this->make_single_request( $url, $args, $options );

			// Success
			if ( ! is_wp_error( $response ) ) {
				$response_code = wp_remote_retrieve_response_code( $response );
				if ( $response_code === 200 ) {
					return $response;
				}
				
				// Don't retry client errors (4xx)
				if ( $response_code >= 400 && $response_code < 500 ) {
					return $response;
				}
			}

			$last_error = $response;

			// Don't sleep on the last attempt
			if ( $attempt < $max_attempts ) {
				$delay = min( $base_delay * pow( $backoff_multiplier, $attempt - 1 ), $max_delay );
				sleep( $delay );
			}
		}

		return $last_error;
	}

	/**
	 * Make a single API request
	 *
	 * @param string $url Request URL.
	 * @param array $args Request arguments.
	 * @param array $options Request options.
	 * @return array|\WP_Error Response or error.
	 */
	private function make_single_request( string $url, array $args, array $options ) {
		// Default arguments with enhanced settings
		$default_args = [
			'timeout' => $options['timeout_override'] ?? get_option( 'hsz_api_timeout', 30 ),
			'redirection' => 3,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => [
				'Accept' => 'application/json',
				'Accept-Encoding' => 'gzip, deflate',
				'User-Agent' => 'HellaZ SiteZ Analyzer/' . HSZ_VERSION . ' (+' . home_url() . ')',
				'Cache-Control' => 'no-cache'
			]
		];

		$args = wp_parse_args( $args, $default_args );

		// Handle SSL verification setting
		if ( get_option( 'hsz_disable_ssl_verify', 0 ) ) {
			$args['sslverify'] = false;
		}

		// Record start time for performance monitoring
		$start_time = microtime( true );

		// Make the request
		$response = wp_remote_get( esc_url_raw( $url ), $args );

		// Calculate response time
		$response_time = microtime( true ) - $start_time;

		// Add response time to response for tracking
		if ( ! is_wp_error( $response ) ) {
			$response['response_time'] = $response_time;
		}

		return $response;
	}

	/**
	 * Check rate limit for a service
	 *
	 * @param string $service Service name.
	 * @return bool True if within rate limit.
	 */
	private function check_rate_limit( string $service ): bool {
		$limit = self::RATE_LIMITS[ $service ] ?? self::RATE_LIMITS['default'];
		
		// Special handling for services with different time windows
		$time_window = HOUR_IN_SECONDS;
		$current_period = date( 'Y-m-d-H' );

		if ( $service === 'virustotal' ) {
			$time_window = MINUTE_IN_SECONDS;
			$current_period = date( 'Y-m-d-H-i' );
		} elseif ( $service === 'builtwith' ) {
			$time_window = MONTH_IN_SECONDS;
			$current_period = date( 'Y-m' );
		} elseif ( in_array( $service, [ 'urlscan', 'pagespeed', 'webpagetest' ], true ) ) {
			$time_window = DAY_IN_SECONDS;
			$current_period = date( 'Y-m-d' );
		}

		return Utils::check_rate_limit( $service . '_' . $current_period, $limit );
	}

	/**
	 * Detect service from URL
	 *
	 * @param string $url API URL.
	 * @return string Service name.
	 */
	private function detect_service_from_url( string $url ): string {
		$service_patterns = [
			'ssl_labs' => 'api.ssllabs.com',
			'virustotal' => 'virustotal.com',
			'builtwith' => 'api.builtwith.com',
			'urlscan' => 'urlscan.io',
			'pagespeed' => 'googleapis.com/pagespeedonline',
			'webpagetest' => 'webpagetest.org'
		];

		foreach ( $service_patterns as $service => $pattern ) {
			if ( strpos( $url, $pattern ) !== false ) {
				return $service;
			}
		}

		return 'unknown';
	}

	/**
	 * Extract endpoint from URL for logging
	 *
	 * @param string $url API URL.
	 * @return string Endpoint name.
	 */
	private function extract_endpoint_from_url( string $url ): string {
		$parsed_url = parse_url( $url );
		$path = $parsed_url['path'] ?? '/';
		
		// Extract meaningful endpoint name
		$path_parts = array_filter( explode( '/', $path ) );
		return end( $path_parts ) ?: 'root';
	}

	/**
	 * Record request statistics
	 *
	 * @param string $service Service name.
	 * @param string $status Request status.
	 * @param float $response_time Response time in seconds.
	 * @param bool $success Whether request was successful.
	 */
	private function record_request_stat( string $service, string $status, float $response_time, bool $success ): void {
		$date = date( 'Y-m-d' );
		$stats_key = "api_stats_{$service}_{$date}";
		
		$stats = get_transient( $stats_key );
		if ( ! $stats ) {
			$stats = [
				'total_requests' => 0,
				'successful_requests' => 0,
				'failed_requests' => 0,
				'cache_hits' => 0,
				'rate_limited' => 0,
				'total_response_time' => 0,
				'average_response_time' => 0,
				'status_breakdown' => []
			];
		}

		$stats['total_requests']++;
		
		if ( $success ) {
			$stats['successful_requests']++;
		} else {
			$stats['failed_requests']++;
		}

		if ( $status === 'cache_hit' ) {
			$stats['cache_hits']++;
		} elseif ( $status === 'rate_limited' ) {
			$stats['rate_limited']++;
		}

		if ( $response_time > 0 ) {
			$stats['total_response_time'] += $response_time;
			$stats['average_response_time'] = $stats['total_response_time'] / max( 1, $stats['total_requests'] - $stats['cache_hits'] );
		}

		if ( ! isset( $stats['status_breakdown'][ $status ] ) ) {
			$stats['status_breakdown'][ $status ] = 0;
		}
		$stats['status_breakdown'][ $status ]++;

		set_transient( $stats_key, $stats, DAY_IN_SECONDS );
	}

	/**
	 * Get API statistics for a service
	 *
	 * @param string $service Service name.
	 * @param string $date Date in Y-m-d format (optional).
	 * @return array API statistics.
	 */
	public static function get_api_stats( string $service, string $date = '' ): array {
		if ( empty( $date ) ) {
			$date = date( 'Y-m-d' );
		}

		$stats_key = "api_stats_{$service}_{$date}";
		$stats = get_transient( $stats_key );

		return $stats ?: [
			'total_requests' => 0,
			'successful_requests' => 0,
			'failed_requests' => 0,
			'cache_hits' => 0,
			'rate_limited' => 0,
			'total_response_time' => 0,
			'average_response_time' => 0,
			'status_breakdown' => []
		];
	}

	/**
	 * Get comprehensive API health status
	 *
	 * @return array API health information.
	 */
	public static function get_api_health_status(): array {
		$services = [ 'ssl_labs', 'virustotal', 'builtwith', 'urlscan', 'pagespeed', 'webpagetest' ];
		$health_status = [
			'overall_status' => 'healthy',
			'services' => [],
			'total_requests_today' => 0,
			'total_errors_today' => 0,
			'average_response_time' => 0
		];

		foreach ( $services as $service ) {
			$stats = self::get_api_stats( $service );
			$health_status['services'][ $service ] = [
				'status' => $stats['failed_requests'] > $stats['successful_requests'] ? 'unhealthy' : 'healthy',
				'requests_today' => $stats['total_requests'],
				'success_rate' => $stats['total_requests'] > 0 ? round( ( $stats['successful_requests'] / $stats['total_requests'] ) * 100, 2 ) : 0,
				'average_response_time' => $stats['average_response_time'],
				'rate_limited' => $stats['rate_limited']
			];

			$health_status['total_requests_today'] += $stats['total_requests'];
			$health_status['total_errors_today'] += $stats['failed_requests'];
		}

		// Calculate overall average response time
		$total_response_time = 0;
		$total_requests_with_time = 0;
		foreach ( $health_status['services'] as $service_data ) {
			if ( $service_data['average_response_time'] > 0 ) {
				$total_response_time += $service_data['average_response_time'] * $service_data['requests_today'];
				$total_requests_with_time += $service_data['requests_today'];
			}
		}

		$health_status['average_response_time'] = $total_requests_with_time > 0 ? round( $total_response_time / $total_requests_with_time, 3 ) : 0;

		// Determine overall status
		$unhealthy_services = count( array_filter( $health_status['services'], function( $service ) {
			return $service['status'] === 'unhealthy';
		}));

		if ( $unhealthy_services > 2 ) {
			$health_status['overall_status'] = 'critical';
		} elseif ( $unhealthy_services > 0 ) {
			$health_status['overall_status'] = 'warning';
		}

		return $health_status;
	}

	/**
	 * Batch API requests with intelligent queuing
	 *
	 * @param array $requests Array of request configurations.
	 * @param array $options Batch options.
	 * @return array Batch results.
	 */
	public function batch_api_requests( array $requests, array $options = [] ): array {
		$options = wp_parse_args( $options, [
			'max_concurrent' => 3,
			'delay_between_batches' => 1, // seconds
			'timeout_per_request' => 30,
			'stop_on_error' => false
		]);

		$results = [];
		$batches = array_chunk( $requests, $options['max_concurrent'] );

		foreach ( $batches as $batch_index => $batch ) {
			$batch_results = [];
			
			// Process batch requests
			foreach ( $batch as $request_key => $request ) {
				$url = $request['url'] ?? '';
				$args = $request['args'] ?? [];
				$cache_key = $request['cache_key'] ?? '';
				$cache_duration = $request['cache_duration'] ?? HOUR_IN_SECONDS;
				$request_options = $request['options'] ?? [];

				if ( empty( $url ) ) {
					$batch_results[ $request_key ] = [ 'error' => 'Invalid request URL' ];
					continue;
				}

				$result = $this->make_api_request( $url, $args, $cache_key, $cache_duration, $request_options );
				$batch_results[ $request_key ] = $result;

				// Stop on error if configured
				if ( $options['stop_on_error'] && isset( $result['error'] ) ) {
					break 2; // Break out of both loops
				}
			}

			$results = array_merge( $results, $batch_results );

			// Delay between batches (except for the last batch)
			if ( $batch_index < count( $batches ) - 1 && $options['delay_between_batches'] > 0 ) {
				sleep( $options['delay_between_batches'] );
			}
		}

		return $results;
	}

	/**
	 * Test API connectivity and performance
	 *
	 * @param string $service Service to test.
	 * @return array Test results.
	 */
	public function test_api_connectivity( string $service ): array {
		$test_results = [
			'service' => $service,
			'status' => 'unknown',
			'response_time' => 0,
			'error' => '',
			'details' => []
		];

		// Define test endpoints for each service
		$test_endpoints = [
			'ssl_labs' => 'https://api.ssllabs.com/api/v3/info',
			'virustotal' => 'https://www.virustotal.com/vtapi/v2/url/report?apikey=test&resource=http://google.com',
			'builtwith' => 'https://api.builtwith.com/v19/api.json?KEY=test&LOOKUP=google.com',
			'urlscan' => 'https://urlscan.io/api/v1/search/?q=domain:google.com',
			'pagespeed' => 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=http://google.com'
		];

		$test_url = $test_endpoints[ $service ] ?? null;
		if ( ! $test_url ) {
			$test_results['status'] = 'error';
			$test_results['error'] = 'No test endpoint defined for service';
			return $test_results;
		}

		$start_time = microtime( true );
		$response = $this->make_single_request( $test_url, [], [ 'timeout_override' => 10 ] );
		$test_results['response_time'] = microtime( true ) - $start_time;

		if ( is_wp_error( $response ) ) {
			$test_results['status'] = 'error';
			$test_results['error'] = $response->get_error_message();
		} else {
			$response_code = wp_remote_retrieve_response_code( $response );
			$test_results['details']['http_status'] = $response_code;
			
			if ( $response_code === 200 || ( $service === 'virustotal' && $response_code === 403 ) ) {
				// 403 for VirusTotal means the API is responding (just invalid key)
				$test_results['status'] = 'connected';
			} else {
				$test_results['status'] = 'error';
				$test_results['error'] = "HTTP {$response_code}";
			}
		}

		return $test_results;
	}
}
