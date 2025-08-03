<?php
/**
 * Handles all AJAX requests for the HellaZ SiteZ Analyzer plugin.
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
 * Class Ajax
 *
 * Manages AJAX endpoints, ensuring requests are secure and valid.
 *
 * @package HSZ
 */
class Ajax {

	/**
	 * Ajax constructor.
	 *
	 * Registers the WordPress AJAX hooks.
	 */
	public function __construct() {
		add_action( 'wp_ajax_hsz_analyze_url', [ $this, 'analyze_url' ] );
		add_action( 'wp_ajax_nopriv_hsz_analyze_url', [ $this, 'analyze_url' ] );
	}

	/**
	 * Handles the AJAX request to analyze a URL.
	 *
	 * This method verifies the request's validity, checks user permissions,
	 * sanitizes the input URL, and triggers the metadata extraction process.
	 */
	public function analyze_url() {
		// 1. Nonce verification
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'hsz_analyze_url_nonce' ) ) {
			wp_send_json_error( [
				'message' => __( 'Nonce verification failed. Please reload the page and try again.', 'hellaz-sitez-analyzer' )
			], 403 );
		}
		// 2. Permission check for logged-in users
		if ( is_user_logged_in() && ! current_user_can( 'read' ) ) {
			wp_send_json_error( [
				'message' => __( 'You do not have sufficient permissions to perform this action.', 'hellaz-sitez-analyzer' )
			], 403 );
		}
		// 3. Sanitize and validate the input URL
		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			wp_send_json_error( [
				'message' => __( 'The provided URL is not valid.', 'hellaz-sitez-analyzer' )
			], 400 );
		}
		try {
			$metadata_extractor = new Metadata();
			$social_extractor   = new SocialMedia();
			$html = Utils::get_html( $url );
			if ( is_wp_error( $html ) ) {
				wp_send_json_error( [
					'message' => $html->get_error_message()
				], 500 );
			}
			$metadata = $metadata_extractor->extract_metadata( $url, $html );
			$social   = $social_extractor->extract_social_profiles( $html, $url );
			if ( isset( $metadata['error'] ) ) {
				wp_send_json_error( [
					'message' => $metadata['error']
				], 500 );
			}
			wp_send_json_success( [
				'metadata' => $metadata,
				'social'   => $social
			] );
		} catch ( \Throwable $e ) {
			Utils::log_error( 'AJAX analysis failed: ' . $e->getMessage() );
			wp_send_json_error( [
				'message' => __( 'An unexpected error occurred during analysis.', 'hellaz-sitez-analyzer' )
			], 500 );
		}
	}
}
