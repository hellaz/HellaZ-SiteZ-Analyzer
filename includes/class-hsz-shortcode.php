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
	 * Usage: [sitez_analyzer url="https://example.com"]
	 * @param array $atts Shortcode attributes.
	 * @return string The rendered HTML.
	 */
	public function render_shortcode( $atts ) {
		// Merge defaults with user input.
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
			return '<div class="hsz-error">' . esc_html__( 'Please provide a valid URL for analysis.', 'hellaz-sitez-analyzer' ) . '</div>';
		}

		try {
			$metadata_extractor = new Metadata();
			$social_extractor   = new SocialMedia();

			$html = Utils::get_html( $url );
			if ( is_wp_error( $html ) ) {
				throw new \Exception( $html->get_error_message() );
			}

			$metadata = $metadata_extractor->extract_metadata( $url, $html );
			$social   = $social_extractor->extract_social_profiles( $html, $url );

			if ( isset( $metadata['error'] ) ) {
				throw new \Exception( $metadata['error'] );
			}

			$display_title       = $metadata['title'] ?? Fallbacks::get_fallback_title();
			$display_description = $metadata['description'] ?? Fallbacks::get_fallback_description();
			$favicon             = $metadata['favicon'] ?? Fallbacks::get_fallback_image();

			$template_mode = get_option( 'hsz_template_mode', 'classic' );
			$template_path = HSZ_PLUGIN_PATH . "templates/metadata-{$template_mode}.php";

			ob_start();
			error_log( '[HSZ Debug] Template path checked by shortcode: ' . $template_path );

			if ( file_exists( $template_path ) ) {
				include $template_path;
			} else {
				echo '<div class="hsz-error">' . esc_html__( 'Rendering template not found.', 'hellaz-sitez-analyzer' ) . '</div>';
			}
			return ob_get_clean();

		} catch ( \Throwable $e ) {
			Utils::log_error( 'Shortcode render failed for ' . $url . ': ' . $e->getMessage() );
			return '<div class="hsz-error">' . esc_html__( 'An unexpected error occurred during analysis.', 'hellaz-sitez-analyzer' ) . '</div>';
		}
	}
}