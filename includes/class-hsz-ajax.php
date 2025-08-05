<?php
/**
 * AJAX handlers for HellaZ SiteZ Analyzer.
 *
 * Manages secure AJAX requests with nonce and permission validation,
 * metadata extraction, social media extraction, and proper error handling.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.1.0
 */

namespace HSZ;

defined( 'ABSPATH' ) || exit;

class Ajax {

	/**
	 * Registers necessary AJAX hooks.
	 */
	public static function init(): void {
		add_action( 'wp_ajax_hsz_extract_metadata', [ __CLASS__, 'ajax_extract_metadata' ] );
		add_action( 'wp_ajax_nopriv_hsz_extract_metadata', [ __CLASS__, 'ajax_extract_metadata' ] );

		add_action( 'wp_ajax_hsz_extract_social', [ __CLASS__, 'ajax_extract_social' ] );
		add_action( 'wp_ajax_nopriv_hsz_extract_social', [ __CLASS__, 'ajax_extract_social' ] );
	}

	/**
	 * AJAX handler for metadata extraction.
	 */
	public static function ajax_extract_metadata(): void {
		check_ajax_referer( 'hsz_ajax_nonce', 'nonce' );

		if ( is_user_logged_in() && ! current_user_can( 'read' ) ) {
			wp_send_json_error(
				[ 'message' => __( 'Insufficient permissions.', 'hsz' ) ],
				403
			);
		}

		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			wp_send_json_error(
				[ 'message' => __( 'Invalid URL.', 'hsz' ) ],
				400
			);
		}

		try {
			$html = Utils::get_html( $url );
			if ( is_wp_error( $html ) ) {
				throw new \Exception( $html->get_error_message() );
			}

			$metadata = ( new Metadata() )->extract_metadata( $url, $html );

			if ( ! $metadata ) {
				throw new \Exception( __( 'Failed to retrieve metadata.', 'hsz' ) );
			}

			wp_send_json_success(
				[ 'metadata' => $metadata ]
			);
		} catch ( \Throwable $e ) {
			Utils::log_error( 'AJAX metadata extraction error: ' . $e->getMessage() );
			wp_send_json_error(
				[ 'message' => __( 'Metadata extraction failed.', 'hsz' ) ],
				500
			);
		}
	}

	/**
	 * AJAX handler for social media extraction.
	 */
	public static function ajax_extract_social(): void {
		check_ajax_referer( 'hsz_ajax_nonce', 'nonce' );

		if ( is_user_logged_in() && ! current_user_can( 'read' ) ) {
			wp_send_json_error(
				[ 'message' => __( 'Insufficient permissions.', 'hsz' ) ],
				403
			);
		}

		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			wp_send_json_error(
				[ 'message' => __( 'Invalid URL.', 'hsz' ) ],
				400
			);
		}

		try {
			$html = Utils::get_html( $url );
			if ( is_wp_error( $html ) ) {
				throw new \Exception( $html->get_error_message() );
			}

			$social = ( new SocialMedia() )->extract_social_profiles( $html, $url );

			// Could return enriched social media data here if needed

			wp_send_json_success(
				[ 'social' => $social ]
			);
		} catch ( \Throwable $e ) {
			Utils::log_error( 'AJAX social extraction error: ' . $e->getMessage() );
			wp_send_json_error(
				[ 'message' => __( 'Social extraction failed.', 'hsz' ) ],
				500
			);
		}
	}
}
