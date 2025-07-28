<?php
/**
 * Manages fallback data for the analysis.
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
 * Class Fallbacks
 *
 * Provides methods to retrieve default fallback values from the plugin settings.
 */
class Fallbacks {

	/**
	 * Get the fallback image URL.
	 *
	 * This function retrieves the encrypted option from the database and decrypts it.
	 *
	 * @return string The decrypted fallback image URL, or an empty string if not set.
	 */
	public static function get_fallback_image(): string {
		// This option is encrypted via the settings page.
		$encrypted_url = get_option( 'hsz_fallback_image' );

		if ( empty( $encrypted_url ) || ! is_string( $encrypted_url ) ) {
			return '';
		}

		// Attempt to decrypt the value.
		$decrypted_url = Utils::decrypt( $encrypted_url );

		// If decryption fails, it might have been saved unencrypted before the key was set.
		// In that case, return the raw value. Otherwise, return the decrypted value.
		return ( false !== $decrypted_url ) ? $decrypted_url : $encrypted_url;
	}

	/**
	 * Get the fallback title.
	 *
	 * @return string The fallback title.
	 */
	public static function get_fallback_title(): string {
		return get_option( 'hsz_fallback_title', '' );
	}

	/**
	 * Get the fallback description.
	 *
	 * @return string The fallback description.
	 */
	public static function get_fallback_description(): string {
		return get_option( 'hsz_fallback_description', '' );
	}
}
