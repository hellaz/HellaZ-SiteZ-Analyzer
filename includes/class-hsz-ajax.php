<?php
/**
 * AJAX endpoints for SiteZ Analyzer.
 *
 * @package HellaZ_SiteZ_Analyzer
 */

namespace HSZ;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ajax {
	public function __construct() {
		add_action( 'wp_ajax_hsz_analyze', [ $this, 'analyze' ] );
		add_action( 'wp_ajax_nopriv_hsz_analyze', [ $this, 'analyze' ] );
	}

	public function analyze() {
		// Nonce check uses the same action as localized script
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'hsz_analyze_nonce' ) ) {
			wp_send_json_error( [
				'message' => __( 'Nonce verification failed. Please reload and try again.', 'hsz' ),
			], 403 );
		}

		if ( is_user_logged_in() && ! current_user_can( 'read' ) ) {
			wp_send_json_error( [
				'message' => __( 'Insufficient permissions.', 'hsz' ),
			], 403 );
		}

		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			wp_send_json_error( [
				'message' => __( 'Invalid URL.', 'hsz' ),
			], 400 );
		}

		try {
			$metadata = ( new Metadata() )->extract_metadata( $url, Utils::get_html( $url ) );
			$social = ( new SocialMedia() )->extract( Utils::get_html( $url ), $url );

			if ( ! $metadata ) {
				throw new \Exception( 'Failed to retrieve metadata.' );
			}

			wp_send_json_success( [ 'metadata' => $metadata, 'social' => $social ] );
		} catch ( \Throwable $e ) {
			Utils::log_error( 'AJAX analysis error: ' . $e->getMessage() );
			wp_send_json_error( [ 'message' => __( 'Analysis failed.', 'hsz' ) ], 500 );
		}
	}
}
