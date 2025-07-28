<?php
/**
 * Manages all external API interactions.
 *
 * This class provides a centralized method for making API requests,
 * handling caching, and logging errors securely.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.0.0
 */

namespace HSZ;

use WP_Error;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class APIManager
 *
 * Handles API requests, caching, and error handling.
 */
class APIManager {

	/**
	 * Makes a request to an external API, with support for caching.
	 *
	 * @param string $url            The API endpoint URL.
	 * @param array  $args           Optional. Arguments for wp_remote_get().
	 * @param string $cache_key      Optional. The transient key for caching the result.
	 * @param int    $cache_duration Optional. The cache duration in seconds.
	 * @return array|mixed The decoded JSON response, or an empty array on failure.
	 */
	public function make_api_request( string $url, array $args = [], string $cache_key = '', int $cache_duration = HOUR_IN_SECONDS ) {
		// Validate the URL before proceeding.
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			Utils::log_error( 'Invalid URL provided to APIManager: ' . $url, __FILE__, __LINE__ );
			return [];
		}

		// Check for and return cached data if available and a key is provided.
		if ( ! empty( $cache_key ) ) {
			$cached_data = get_transient( $cache_key );
			if ( false !== $cached_data ) {
				return $cached_data;
			}
		}

		// Set default arguments for the request.
		$default_args = [
			'timeout' => 10, // Increased timeout for potentially slow APIs.
			'headers' => [
				'Accept'     => 'application/json',
				'User-Agent' => 'HellaZ SiteZ Analyzer/' . HSZ_VERSION . '; ' . home_url(),
			],
		];
		$args         = wp_parse_args( $args, $default_args );

		// Make the API request.
		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			$error_message = sprintf(
				'API Request Failed for %s: %s',
				esc_url( $url ),
				$response->get_error_message()
			);
			Utils::log_error( $error_message, __FILE__, __LINE__ );
			return [];
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code !== 200 ) {
			$error_message = sprintf(
				'API returned non-200 HTTP code for %s: %d',
				esc_url( $url ),
				$response_code
			);
			Utils::log_error( $error_message, __FILE__, __LINE__ );
			return [];
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Cache the results if a cache key is provided.
		if ( ! empty( $cache_key ) && is_array( $body ) ) {
			set_transient( $cache_key, $body, $cache_duration );
		}

		return $body ?? [];
	}
}
