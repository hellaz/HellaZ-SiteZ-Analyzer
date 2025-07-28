<?php
/**
 * Utility class for various helper functions.
 *
 * This class provides static methods for common tasks such as making HTTP requests,
 * parsing HTML content, handling data encryption/decryption, caching, logging,
 * and performance monitoring.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.0.0
 */

namespace HSZ;

use DOMDocument;
use DOMXPath;
use WP_Error;
use WP_Transient_Cache;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Utils
 *
 * Provides a suite of static utility methods for the plugin.
 *
 * @package HSZ
 */
class Utils {

	/**
	 * The encryption cipher.
	 *
	 * @var string
	 */
	private const CIPHER = 'aes-256-cbc';

	/**
	 * A container for performance timers.
	 *
	 * @var array
	 */
	private static array $timers = [];

	/**
	 * Checks if the encryption functionality is properly configured.
	 *
	 * @since 1.0.1
	 *
	 * @return bool True if the encryption key is defined as a non-empty string, false otherwise.
	 */
	public static function is_encryption_configured(): bool {
		return defined( 'HSZ_ENCRYPTION_KEY' ) && is_string( HSZ_ENCRYPTION_KEY ) && ! empty( HSZ_ENCRYPTION_KEY );
	}

	/**
	 * Sanitizes and encrypts an option value for safe storage.
	 *
	 * This method is the designated callback for the `sanitize_option_*` filter.
	 * It ensures that encryption only runs when correctly configured, preventing fatal errors.
	 * If encryption is not configured, it returns the raw, sanitized value.
	 *
	 * @since 1.0.1
	 *
	 * @param mixed $value The value to sanitize and encrypt.
	 * @return mixed The encrypted string, or the original value if encryption is not configured or fails.
	 */
	public static function sanitize_and_encrypt( $value ) {
		if ( ! is_string( $value ) || empty( $value ) ) {
			return $value;
		}

		// Always sanitize the value.
		$sanitized_value = esc_url_raw( $value );

		// Only encrypt if the key is properly configured.
		if ( ! self::is_encryption_configured() ) {
			// A warning should be displayed in the admin to inform the user.
			return $sanitized_value;
		}

		return self::encrypt( $sanitized_value );
	}

	/**
	 * Encrypts a string using the HSZ_ENCRYPTION_KEY from wp-config.php.
	 *
	 * This method is modified to be self-contained, generating its own IV and
	 * relying on the defined constant for the key.
	 *
	 * @since 1.0.0
	 * @version 1.0.1
	 *
	 * @param string $data The plaintext data to encrypt.
	 * @return string|false The base64-encoded encrypted string (IV prepended) or false on failure.
	 */
	public static function encrypt( string $data ) {
		if ( ! self::is_encryption_configured() ) {
			return false;
		}

		$key       = HSZ_ENCRYPTION_KEY;
		$iv_length = openssl_cipher_iv_length( self::CIPHER );

		if ( false === $iv_length ) {
			return false;
		}

		$iv        = openssl_random_pseudo_bytes( $iv_length );
		$encrypted = openssl_encrypt( $data, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv );

		if ( false === $encrypted ) {
			return false;
		}

		// Prepend the IV to the ciphertext for use in decryption.
		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Decrypts a string using the HSZ_ENCRYPTION_KEY from wp-config.php.
	 *
	 * This method is modified to handle a base64-encoded string that contains
	 * both the IV and the ciphertext.
	 *
	 * @since 1.0.0
	 * @version 1.0.1
	 *
	 * @param string $data The base64-encoded data to decrypt.
	 * @return string|false The decrypted plaintext string or false on failure.
	 */
	public static function decrypt( string $data ) {
		if ( ! self::is_encryption_configured() || empty( $data ) ) {
			return false;
		}

		$key  = HSZ_ENCRYPTION_KEY;
		$data = base64_decode( $data, true );

		if ( false === $data ) {
			return false;
		}

		$iv_length = openssl_cipher_iv_length( self::CIPHER );
		if ( false === $iv_length || mb_strlen( $data, '8bit' ) < $iv_length ) {
			return false;
		}

		$iv         = mb_substr( $data, 0, $iv_length, '8bit' );
		$ciphertext = mb_substr( $data, $iv_length, null, '8bit' );

		return openssl_decrypt( $ciphertext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv );
	}

	/**
	 * Retrieves the HTML content of a given URL using WordPress HTTP API.
	 *
	 * @param string $url The URL to fetch.
	 * @return string|WP_Error The HTML content as a string or a WP_Error on failure.
	 */
	public static function get_html( string $url ) {
		$args = [];
		if ( get_option( 'hsz_disable_ssl_verify', 0 ) ) {
			$args['sslverify'] = false;
		}
		$response = wp_remote_get( esc_url_raw( $url ), $args );
		if ( is_wp_error( $response ) ) {
			self::log_error( 'Failed to fetch URL: ' . $url . ' - ' . $response->get_error_message() );
			return $response;
		}
		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Gets the HTTP status code for a given URL.
	 *
	 * @param string $url The URL to check.
	 * @return int|WP_Error The HTTP status code or a WP_Error on failure.
	 */
	public static function get_http_status( string $url ) {
    $args = [];
    if ( get_option( 'hsz_disable_ssl_verify', 0 ) ) {
        $args['sslverify'] = false;
    }
    $response = wp_remote_head( esc_url_raw( $url ), $args );
    if ( is_wp_error( $response ) ) {
        self::log_error( 'Failed to get HTTP status for URL: ' . $url . ' - ' . $response->get_error_message() );
        return $response;
    }
    return wp_remote_retrieve_response_code( $response );
}
	/**
	 * Logs an error message to the WordPress debug log.
	 *
	 * @param string $message The error message.
	 * @param string $file The file where the error occurred.
	 * @param int    $line The line where the error occurred.
	 */
	public static function log_error( string $message, string $file = __FILE__, int $line = __LINE__ ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( '[HellaZ SiteZ Analyzer] %s in %s on line %d', $message, $file, $line ) );
		}
	}

	/**
	 * Sets data in the WordPress transient cache.
	 *
	 * @param string $key The cache key.
	 * @param mixed  $data The data to cache.
	 * @param int    $expiration The cache lifetime in seconds.
	 * @return bool True on success, false on failure.
	 */
	public static function set_cache( string $key, $data, int $expiration = HOUR_IN_SECONDS ): bool {
		return set_transient( 'hsz_cache_' . $key, $data, $expiration );
	}

	/**
	 * Gets data from the WordPress transient cache.
	 *
	 * @param string $key The cache key.
	 * @return mixed The cached data or false if not found.
	 */
	public static function get_cache( string $key ) {
		return get_transient( 'hsz_cache_' . $key );
	}

	/**
	 * Deletes data from the WordPress transient cache.
	 *
	 * @param string $key The cache key.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_cache( string $key ): bool {
		return delete_transient( 'hsz_cache_' . $key );
	}

	/**
	 * Starts a performance timer.
	 *
	 * @param string $key A unique identifier for the timer.
	 */
	public static function start_timer( string $key ): void {
		self::$timers[ $key ] = microtime( true );
	}

	/**
	 * Stops a performance timer and returns the elapsed time.
	 *
	 * @param string $key The identifier for the timer.
	 * @return float|false The elapsed time in seconds, or false if the timer wasn't started.
	 */
	public static function stop_timer( string $key ) {
		if ( isset( self::$timers[ $key ] ) ) {
			$elapsed = microtime( true ) - self::$timers[ $key ];
			unset( self::$timers[ $key ] );
			return $elapsed;
		}
		return false;
	}
}
