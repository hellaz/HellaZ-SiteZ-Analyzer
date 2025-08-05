<?php
/**
 * Enhanced cache management for HellaZ SiteZ Analyzer.
 *
 * This class provides comprehensive caching functionality including
 * transient-based caching, database caching, and cache statistics
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
 * Class Cache
 *
 * Handles all caching operations with enhanced capabilities.
 */
class Cache {

	/** The prefix for all cache keys */
	private const CACHE_PREFIX = 'hsz_';

	/** Enhanced cache types */
	private const CACHE_TYPES = [
		'metadata' => 'hsz_metadata_',
		'security' => 'hsz_security_',
		'performance' => 'hsz_performance_',
		'preview' => 'hsz_preview_',
		'social' => 'hsz_social_',
		'analysis' => 'hsz_analysis_'
	];

	/**
	 * Sets a cache entry with enhanced features.
	 *
	 * @param string $key The cache key.
	 * @param mixed $data The data to cache.
	 * @param int $expiration The expiration time in seconds.
	 * @param string $type Cache type for categorization.
	 * @return bool True on success, false on failure.
	 */
	public static function set( string $key, $data, int $expiration = HOUR_IN_SECONDS, string $type = 'general' ): bool {
		$full_key = self::get_full_key( $key, $type );
		
		// Set transient cache
		$transient_result = set_transient( $full_key, $data, $expiration );
		
		// Also store in database cache if available
		self::set_database_cache( $full_key, $data, $expiration, $type );
		
		// Record cache statistics
		self::record_cache_stats( 'set', $type, strlen( serialize( $data ) ) );
		
		return $transient_result;
	}

	/**
	 * Gets a cache entry with fallback support.
	 *
	 * @param string $key The cache key.
	 * @param string $type Cache type.
	 * @return mixed The cached data or false if not found.
	 */
	public static function get( string $key, string $type = 'general' ) {
		$full_key = self::get_full_key( $key, $type );
		
		// Try transient cache first
		$data = get_transient( $full_key );
		
		if ( $data !== false ) {
			self::record_cache_stats( 'hit', $type );
			return $data;
		}
		
		// Try database cache as fallback
		$data = self::get_database_cache( $full_key );
		
		if ( $data !== false ) {
			self::record_cache_stats( 'hit', $type );
			// Restore to transient cache
			set_transient( $full_key, $data, HOUR_IN_SECONDS );
			return $data;
		}
		
		self::record_cache_stats( 'miss', $type );
		return false;
	}

	/**
	 * Deletes a cache entry.
	 *
	 * @param string $key The cache key.
	 * @param string $type Cache type.
	 * @return bool True on success, false on failure.
	 */
	public static function delete( string $key, string $type = 'general' ): bool {
		$full_key = self::get_full_key( $key, $type );
		
		// Delete from transients
		$transient_result = delete_transient( $full_key );
		
		// Delete from database cache
		self::delete_database_cache( $full_key );
		
		self::record_cache_stats( 'delete', $type );
		
		return $transient_result;
	}

	/**
	 * Get full cache key with type prefix
	 *
	 * @param string $key Base key.
	 * @param string $type Cache type.
	 * @return string Full cache key.
	 */
	private static function get_full_key( string $key, string $type ): string {
		$prefix = self::CACHE_TYPES[ $type ] ?? self::CACHE_PREFIX;
		return $prefix . $key;
	}

	/**
	 * Enhanced database cache storage
	 *
	 * @param string $key Cache key.
	 * @param mixed $data Data to cache.
	 * @param int $expiration Expiration time.
	 * @param string $type Cache type.
	 */
	private static function set_database_cache( string $key, $data, int $expiration, string $type ): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'hsz_analysis_cache';
		
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			return;
		}

		$cache_key = md5( $key );
		$expires_at = date( 'Y-m-d H:i:s', time() + $expiration );
		$serialized_data = wp_json_encode( $data );

		$wpdb->replace(
			$table_name,
			[
				'cache_key' => $cache_key,
				'url' => substr( $key, 0, 500 ),
				'data' => $serialized_data,
				'created_at' => current_time( 'mysql', true ),
				'expires_at' => $expires_at
			],
			[ '%s', '%s', '%s', '%s', '%s' ]
		);
	}

	/**
	 * Get data from database cache
	 *
	 * @param string $key Cache key.
	 * @return mixed Cached data or false.
	 */
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

	/**
	 * Delete from database cache
	 *
	 * @param string $key Cache key.
	 */
	private static function delete_database_cache( string $key ): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'hsz_analysis_cache';
		
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			return;
		}

		$cache_key = md5( $key );
		$wpdb->delete( $table_name, [ 'cache_key' => $cache_key ], [ '%s' ] );
	}

	/**
	 * Record cache statistics
	 *
	 * @param string $operation Operation type (hit, miss, set, delete).
	 * @param string $type Cache type.
	 * @param int $size Data size in bytes (for set operations).
	 */
	private static function record_cache_stats( string $operation, string $type, int $size = 0 ): void {
		$stats_key = 'hsz_cache_stats_' . date( 'Y-m-d' );
		$stats = get_transient( $stats_key );
		
		if ( ! $stats ) {
			$stats = [
				'hits' => 0,
				'misses' => 0,
				'sets' => 0,
				'deletes' => 0,
				'total_size' => 0,
				'by_type' => []
			];
		}

		// Update overall stats
		switch ( $operation ) {
			case 'hit':
				$stats['hits']++;
				break;
			case 'miss':
				$stats['misses']++;
				break;
			case 'set':
				$stats['sets']++;
				$stats['total_size'] += $size;
				break;
			case 'delete':
				$stats['deletes']++;
				break;
		}

		// Update type-specific stats
		if ( ! isset( $stats['by_type'][ $type ] ) ) {
			$stats['by_type'][ $type ] = [
				'hits' => 0,
				'misses' => 0,
				'sets' => 0,
				'deletes' => 0,
				'size' => 0
			];
		}

		$stats['by_type'][ $type ][ $operation === 'hit' ? 'hits' : ( $operation === 'miss' ? 'misses' : ( $operation === 'set' ? 'sets' : 'deletes' ) ) ]++;
		
		if ( $operation === 'set' ) {
			$stats['by_type'][ $type ]['size'] += $size;
		}

		set_transient( $stats_key, $stats, DAY_IN_SECONDS );
	}

	/**
	 * Get cache statistics
	 *
	 * @param string $date Date in Y-m-d format (optional, defaults to today).
	 * @return array Cache statistics.
	 */
	public static function get_cache_stats( string $date = '' ): array {
		if ( empty( $date ) ) {
			$date = date( 'Y-m-d' );
		}
		
		$stats_key = 'hsz_cache_stats_' . $date;
		$stats = get_transient( $stats_key );
		
		if ( ! $stats ) {
			return [
				'hits' => 0,
				'misses' => 0,
				'sets' => 0,
				'deletes' => 0,
				'total_size' => 0,
				'hit_rate' => 0,
				'by_type' => []
			];
		}

		// Calculate hit rate
		$total_requests = $stats['hits'] + $stats['misses'];
		$stats['hit_rate'] = $total_requests > 0 ? round( ( $stats['hits'] / $total_requests ) * 100, 2 ) : 0;

		return $stats;
	}

	/**
	 * Clear all HSZ transients with enhanced cleanup.
	 *
	 * @param string $type Optional cache type to clear specific cache.
	 * @return int The number of deleted entries.
	 */
	public static function clear_all_hsz_transients( string $type = '' ): int {
		global $wpdb;

		$deleted_rows = 0;

		if ( $type && isset( self::CACHE_TYPES[ $type ] ) ) {
			// Clear specific cache type
			$prefix = self::CACHE_TYPES[ $type ];
		} else {
			// Clear all HSZ caches
			$prefix = self::CACHE_PREFIX;
		}

		// Clear transients
		$transient_pattern = $wpdb->esc_like( '_transient_' . $prefix ) . '%';
		$timeout_pattern = $wpdb->esc_like( '_transient_timeout_' . $prefix ) . '%';

		$sql = $wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$transient_pattern,
			$timeout_pattern
		);

		$deleted_rows = $wpdb->query( $sql );

		// Clear database cache
		if ( empty( $type ) ) {
			$deleted_rows += self::clear_database_cache();
		}

		// Clear cache statistics
		if ( empty( $type ) ) {
			self::clear_cache_stats();
		}

		return is_numeric( $deleted_rows ) ? (int) $deleted_rows : 0;
	}

	/**
	 * Clear database cache
	 *
	 * @return int Number of deleted entries.
	 */
	private static function clear_database_cache(): int {
		global $wpdb;

		$tables = [
			'hsz_analysis_cache',
			'hsz_performance_results',
			'hsz_security_results',
			'hsz_website_previews',
			'hsz_website_grades'
		];

		$deleted_count = 0;

		foreach ( $tables as $table_suffix ) {
			$table_name = $wpdb->prefix . $table_suffix;
			
			if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name ) {
				$result = $wpdb->query( "TRUNCATE TABLE {$table_name}" );
				if ( $result !== false ) {
					$deleted_count += $result;
				}
			}
		}

		return $deleted_count;
	}

	/**
	 * Clear cache statistics
	 */
	private static function clear_cache_stats(): void {
		global $wpdb;

		$pattern = $wpdb->esc_like( '_transient_hsz_cache_stats_' ) . '%';
		$timeout_pattern = $wpdb->esc_like( '_transient_timeout_hsz_cache_stats_' ) . '%';

		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$pattern,
			$timeout_pattern
		) );
	}

	/**
	 * Clean up expired cache entries
	 *
	 * @return int Number of cleaned entries.
	 */
	public static function cleanup_expired_cache(): int {
		global $wpdb;

		$tables = [
			'hsz_analysis_cache',
			'hsz_performance_results',
			'hsz_security_results',
			'hsz_website_previews',
			'hsz_website_grades'
		];

		$total_cleaned = 0;
		$current_time = current_time( 'mysql', true );

		foreach ( $tables as $table_suffix ) {
			$table_name = $wpdb->prefix . $table_suffix;
			
			if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name ) {
				$cleaned = $wpdb->query( $wpdb->prepare(
					"DELETE FROM {$table_name} WHERE expires_at < %s AND expires_at != '0000-00-00 00:00:00'",
					$current_time
				) );
				
				if ( $cleaned !== false ) {
					$total_cleaned += $cleaned;
				}
			}
		}

		return $total_cleaned;
	}

	/**
	 * Get cache size information
	 *
	 * @return array Cache size statistics.
	 */
	public static function get_cache_size_info(): array {
		global $wpdb;

		$size_info = [
			'transients' => [
				'count' => 0,
				'size_kb' => 0
			],
			'database' => [
				'count' => 0,
				'size_kb' => 0
			],
			'total_size_kb' => 0,
			'total_count' => 0
		];

		// Get transient cache size
		$transients = $wpdb->get_results( $wpdb->prepare(
			"SELECT option_name, LENGTH(option_value) as size_bytes 
			FROM {$wpdb->options} 
			WHERE option_name LIKE %s",
			$wpdb->esc_like( '_transient_' . self::CACHE_PREFIX ) . '%'
		) );

		if ( $transients ) {
			$size_info['transients']['count'] = count( $transients );
			$total_bytes = array_sum( array_column( $transients, 'size_bytes' ) );
			$size_info['transients']['size_kb'] = round( $total_bytes / 1024, 2 );
		}

		// Get database cache size
		$tables = [
			'hsz_analysis_cache',
			'hsz_performance_results', 
			'hsz_security_results',
			'hsz_website_previews',
			'hsz_website_grades'
		];

		foreach ( $tables as $table_suffix ) {
			$table_name = $wpdb->prefix . $table_suffix;
			
			if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name ) {
				$table_info = $wpdb->get_row( $wpdb->prepare(
					"SELECT COUNT(*) as row_count, 
					ROUND(((data_length + index_length) / 1024), 2) as size_kb
					FROM information_schema.TABLES 
					WHERE table_schema = %s AND table_name = %s",
					DB_NAME,
					$table_name
				) );

				if ( $table_info ) {
					$size_info['database']['count'] += (int) $table_info->row_count;
					$size_info['database']['size_kb'] += (float) $table_info->size_kb;
				}
			}
		}

		// Calculate totals
		$size_info['total_count'] = $size_info['transients']['count'] + $size_info['database']['count'];
		$size_info['total_size_kb'] = $size_info['transients']['size_kb'] + $size_info['database']['size_kb'];

		return $size_info;
	}

	/**
	 * Optimize cache performance
	 *
	 * @return array Optimization results.
	 */
	public static function optimize_cache(): array {
		$results = [
			'expired_cleaned' => 0,
			'duplicates_removed' => 0,
			'size_reduced_kb' => 0,
			'tables_optimized' => 0
		];

		// Clean expired entries
		$results['expired_cleaned'] = self::cleanup_expired_cache();

		// Optimize database tables
		global $wpdb;
		$tables = [
			'hsz_analysis_cache',
			'hsz_performance_results',
			'hsz_security_results', 
			'hsz_website_previews',
			'hsz_website_grades'
		];

		foreach ( $tables as $table_suffix ) {
			$table_name = $wpdb->prefix . $table_suffix;
			
			if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name ) {
				$wpdb->query( "OPTIMIZE TABLE {$table_name}" );
				$results['tables_optimized']++;
			}
		}

		return $results;
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

	/**
	 * Check if cache is healthy
	 *
	 * @return array Health check results.
	 */
	public static function health_check(): array {
		$health = [
			'status' => 'healthy',
			'issues' => [],
			'recommendations' => [],
			'stats' => self::get_cache_stats()
		];

		// Check hit rate
		if ( $health['stats']['hit_rate'] < 70 && ( $health['stats']['hits'] + $health['stats']['misses'] ) > 100 ) {
			$health['status'] = 'warning';
			$health['issues'][] = __( 'Low cache hit rate', 'hellaz-sitez-analyzer' );
			$health['recommendations'][] = __( 'Consider increasing cache duration for frequently accessed data', 'hellaz-sitez-analyzer' );
		}

		// Check cache size
		$size_info = self::get_cache_size_info();
		if ( $size_info['total_size_kb'] > 50000 ) { // 50MB
			$health['issues'][] = __( 'Large cache size detected', 'hellaz-sitez-analyzer' );
			$health['recommendations'][] = __( 'Consider cleaning up old cache entries', 'hellaz-sitez-analyzer' );
		}

		// Check for database connectivity
		global $wpdb;
		$table_name = $wpdb->prefix . 'hsz_analysis_cache';
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			$health['status'] = 'error';
			$health['issues'][] = __( 'Database cache table missing', 'hellaz-sitez-analyzer' );
			$health['recommendations'][] = __( 'Run database update to create missing tables', 'hellaz-sitez-analyzer' );
		}

		return $health;
	}
}
