<?php
/**
 * Manages caching for the plugin using WordPress transients.
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
 * Class Cache
 *
 * Provides a standardized way to interact with the WordPress Caching API (Transients).
 */
class Cache {

	/**
	 * A prefix for all cache keys to avoid conflicts.
	 *
	 * @var string
	 */
	private const CACHE_PREFIX = 'hsz_cache_';

	/**
	 * Retrieves cached data.
	 *
	 * @param string $key The unique part of the cache key.
	 * @return mixed The cached data, or false if the cache is empty or expired.
	 */
	public static function get_cache( string $key ) {
		$sanitized_key = self::sanitize_key( $key );
		return get_transient( self::CACHE_PREFIX . $sanitized_key );
	}

	/**
	 * Stores data in the cache.
	 *
	 * @param string $key The unique part of the cache key.
	 * @param mixed  $data The data to be cached. Must be serializable.
	 * @param int    $expiration Optional. The time until the cache expires, in seconds. Defaults to value from settings or 1 hour.
	 * @return bool True if the data was successfully cached, false otherwise.
	 */
	public static function set_cache( string $key, $data, int $expiration = 0 ): bool {
		$sanitized_key = self::sanitize_key( $key );

		if ( 0 === $expiration ) {
			// Use the duration set in the plugin's settings, with a fallback.
			$expiration = get_option( 'hsz_cache_duration', HOUR_IN_SECONDS );
		}

		return set_transient( self::CACHE_PREFIX . $sanitized_key, $data, absint( $expiration ) );
	}

	/**
	 * Deletes a specific item from the cache.
	 *
	 * @param string $key The unique part of the cache key.
	 * @return bool True if the cache was successfully deleted, false otherwise.
	 */
	public static function delete_cache( string $key ): bool {
		$sanitized_key = self::sanitize_key( $key );
		return delete_transient( self::CACHE_PREFIX . $sanitized_key );
	}

	/**
	 * Clears all transients created by this plugin.
	 *
	 * This is a more aggressive form of cache clearing, targeting all related entries
	 * directly in the database for a complete cleanup.
	 *
	 * @return int The number of rows deleted.
	 */
	public static function clear_all_hsz_transients(): int {
		global $wpdb;

		// Correctly escape the '_' which is a wildcard character in SQL LIKE clauses.
		// This ensures we only delete transients with the exact 'hsz_cache_' prefix.
		$transient_pattern = $wpdb->esc_like( '_transient_' . self::CACHE_PREFIX ) . '%';
		$timeout_pattern   = $wpdb->esc_like( '_transient_timeout_' . self::CACHE_PREFIX ) . '%';

		$sql = $wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$transient_pattern,
			$timeout_pattern
		);

		// The query is prepared, so it is safe to execute directly.
		$deleted_rows = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Return the number of deleted database rows.
		return is_numeric( $deleted_rows ) ? (int) $deleted_rows : 0;
	}

	/**
	 * Generates a unique cache key from an array of parameters.
	 *
	 * @param array $params An array of parameters that uniquely identify the data.
	 * @return string A unique MD5 hash representing the parameters.
	 */
	public static function generate_key( array $params ): string {
		// Sort the array to ensure consistency, then serialize and hash it.
		ksort( $params );
		return md5( wp_json_encode( $params ) );
	}

	/**
	 * Sanitizes a cache key part.
	 *
	 * @param string $key The key part to sanitize.
	 * @return string The sanitized key.
	 */
	private static function sanitize_key( string $key ): string {
		// Replaces any character that is not a letter, number, or underscore with an underscore.
		return preg_replace( '/[^A-Za-z0-9_]/', '_', $key );
	}
}
