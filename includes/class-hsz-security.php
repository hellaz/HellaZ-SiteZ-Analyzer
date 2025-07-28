<?php
/**
 * Security-related utility functions.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.0.0
 */

namespace HSZ;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Security
 *
 * Provides methods for validating and securing data, particularly URLs.
 */
class Security {

	/**
	 * Validates a URL to ensure it is safe to request.
	 *
	 * This checks for proper formatting and helps prevent Server-Side Request Forgery (SSRF)
	 * by blocking requests to local, private, or reserved network addresses.
	 *
	 * @param string $url The URL to validate.
	 * @return bool True if the URL is valid and safe, false otherwise.
	 * @throws \Exception If the URL is invalid or unsafe.
	 */
	public static function validate_url( string $url ): bool {
		// 1. Use WordPress's built-in URL validator.
		if ( ! wp_http_validate_url( $url ) ) {
			throw new \Exception( 'URL is not valid according to WordPress standards.' );
		}

		// 2. Parse the URL to get the host.
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! $host ) {
			throw new \Exception( 'Could not parse the hostname from the URL.' );
		}

		// 3. Get the IP address of the host.
		$ip = gethostbyname( $host );

		// 4. Check if the resolved IP is in a private or reserved range.
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
			throw new \Exception( 'URL resolves to a private or reserved IP address, which is not allowed.' );
		}

		return true;
	}

	/**
	 * Sanitizes an array of URLs, removing any that are unsafe.
	 *
	 * @param array $urls An array of URLs to sanitize.
	 * @return array A sanitized array of safe URLs.
	 */
	public static function sanitize_url_array( array $urls ): array {
		$safe_urls = [];
		foreach ( $urls as $url ) {
			try {
				if ( self::validate_url( $url ) ) {
					$safe_urls[] = esc_url_raw( $url );
				}
			} catch ( \Exception $e ) {
				Utils::log_error( 'Invalid URL skipped during sanitization: ' . $url . ' - ' . $e->getMessage() );
			}
		}
		return $safe_urls;
	}
}
