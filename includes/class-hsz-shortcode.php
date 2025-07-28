<?php
/**
 * Manages the [sitez_analyzer] shortcode.
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
 * Class Shortcode
 *
 * Registers and renders the shortcode.
 */
class Shortcode {

	/**
	 * Shortcode constructor.
	 *
	 * Registers the shortcode with WordPress.
	 */
	public function __construct() {
		add_shortcode( 'sitez_analyzer', [ $this, 'render_shortcode' ] );
	}

	/**
	 * Renders the shortcode output.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string The rendered HTML.
	 */
	public function render_shortcode( $atts ): string {
		$atts = shortcode_atts(
			[
				'url'         => '',
				'displayType' => 'full',
			],
			$atts,
			'sitez_analyzer'
		);

		$url         = trim( $atts['url'] );
		$displayType = sanitize_key( $atts['displayType'] );

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return '<div class="hsz-error">' . esc_html__( 'Please provide a valid URL.', 'hellaz-sitez-analyzer' ) . '</div>';
		}

		try {
			// Use WordPress HTTP API for fetching HTML safely via our Utils class.
			$html = Utils::get_html( $url );
			if ( is_wp_error( $html ) ) {
				throw new \Exception( $html->get_error_message() );
			}

			$metadata_extractor = new Metadata();
			$social_extractor   = new SocialMedia();

			$metadata = $metadata_extractor->extract_metadata( $url, $html );
			$social   = $social_extractor->extract_social_profiles( $html, $url );

			if ( isset( $metadata['error'] ) ) {
				throw new \Exception( $metadata['error'] );
			}

			// Initialize ALL variables used in the rendering templates, with fallbacks.
			$display_title       = $metadata['title'] ?? Fallbacks::get_fallback_title();
			$display_description = $metadata['description'] ?? Fallbacks::get_fallback_description();
			$favicon             = $metadata['favicon'] ?? Fallbacks::get_fallback_image();

			// Select template mode from settings.
			$template_mode = get_option( 'hsz_template_mode', 'classic' );
			$template_path = HSZ_PLUGIN_PATH . "templates/metadata-{$template_mode}.php";

			if ( file_exists( $template_path ) ) {
				ob_start();
				// Variables in scope for templates:
				// $url, $display_title, $display_description, $favicon, $social, $displayType
				include $template_path;
				return ob_get_clean();
			}

			// Fallback if template file is missing.
			return '<div class="hsz-error">' . esc_html__( 'Rendering template not found.', 'hellaz-sitez-analyzer' ) . '</div>';

		} catch ( \Throwable $e ) {
			Utils::log_error( 'Shortcode failed for URL: ' . $url . ' - ' . $e->getMessage() );
			return '<div class="hsz-error">' . esc_html__( 'An error occurred while processing the URL.', 'hellaz-sitez-analyzer' ) . '</div>';
		}
	}
}
