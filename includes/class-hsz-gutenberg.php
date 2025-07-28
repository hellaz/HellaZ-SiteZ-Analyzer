<?php
/**
 * Manages the Gutenberg block for the plugin.
 *
 * This class registers the custom Gutenberg block, its assets, and defines
 * the server-side rendering callback to display a full analysis.
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
 * Class Gutenberg
 *
 * Handles all functionalities related to the Gutenberg editor block.
 */
class Gutenberg {

	/**
	 * Gutenberg constructor.
	 *
	 * Registers the necessary hooks for the Gutenberg block.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_block' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_block_editor_assets' ] );
	}

	/**
	 * Registers the Gutenberg block on the server.
	 *
	 * CORRECTED: This now explicitly registers the block with a lowercase name
	 * and its attributes to comply with WordPress standards and fix the PHP notice.
	 */
	public function register_block(): void {
		register_block_type(
			'hsz/analyzer-block', // The block name must be all lowercase.
			[
				// Register attributes on the server to make them available to the render_callback.
				'attributes'      => [
					'url'         => [
						'type'    => 'string',
						'default' => '',
					],
					'displayType' => [
						'type'    => 'string',
						'default' => 'full',
					],
				],
				// Define the server-side rendering callback for this dynamic block.
				'render_callback' => [ $this, 'render_block' ],
			]
		);
	}

	/**
	 * Renders the block on the server.
	 *
	 * This callback gathers all analysis data (metadata, social, security, etc.)
	 * and passes it to the correct frontend template for rendering.
	 *
	 * @param array $attributes The block attributes.
	 * @return string The HTML to render.
	 */
	public function render_block( array $attributes ): string {
		$url         = $attributes['url'] ?? '';
		$displayType = $attributes['displayType'] ?? 'full';

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return '<div class="hsz-error">' . esc_html__( 'Please provide a valid URL.', 'hellaz-sitez-analyzer' ) . '</div>';
		}

		// Instantiate all necessary analysis classes.
		$metadata_extractor = new Metadata();
		$social_extractor   = new SocialMedia();
		$rss_extractor      = new RSS();

		try {
			// Step 1: Securely fetch the HTML content.
			$html = Utils::get_html( $url );
			if ( is_wp_error( $html ) ) {
				throw new \Exception( 'Failed to retrieve URL content: ' . $html->get_error_message() );
			}

			// Step 2: Extract all data points.
			$metadata       = $metadata_extractor->extract_metadata( $url, $html );
			$social         = $social_extractor->extract_social_profiles( $html, $url );
			$rss_feeds      = $rss_extractor->extract_feeds( $html, $url );
			$security_check = []; // Placeholder for future security analysis results.

			// Check for errors from the metadata extraction.
			if ( isset( $metadata['error'] ) ) {
				throw new \Exception( $metadata['error'] );
			}

			// Step 3: Prepare variables for the template, ensuring all have fallbacks.
			$display_title       = $metadata['title'] ?? Fallbacks::get_fallback_title();
			$display_description = $metadata['description'] ?? Fallbacks::get_fallback_description();
			$favicon             = $metadata['favicon'] ?? Fallbacks::get_fallback_image();
			$og                  = $metadata['og'] ?? [];
			$twitter             = $metadata['twitter'] ?? [];

			// Step 4: Select the correct template based on settings.
			$template_mode = get_option( 'hsz_template_mode', 'classic' );
			$template_path = HSZ_PLUGIN_PATH . "templates/metadata-{$template_mode}.php";

			// Step 5: Render the output using an output buffer.
			ob_start();
			if ( file_exists( $template_path ) ) {
				// Make all extracted data available to the included template.
				include $template_path;
			} else {
				// Fallback in case the selected template is missing.
				echo '<div class="hsz-error">' . esc_html__( 'Rendering template not found.', 'hellaz-sitez-analyzer' ) . '</div>';
			}
			return ob_get_clean();

		} catch ( \Throwable $e ) {
			Utils::log_error( 'Gutenberg block render failed for ' . $url . ': ' . $e->getMessage() );
			return '<div class="hsz-error">' . esc_html__( 'An error occurred while rendering the block.', 'hellaz-sitez-analyzer' ) . '</div>';
		}
	}

	/**
	 * Enqueues assets specifically for the block editor.
	 */
	public function enqueue_block_editor_assets(): void {
		$screen = get_current_screen();
		if ( $screen && $screen->is_block_editor() ) {
			wp_enqueue_script(
				'hsz-block-editor-script',
				HSZ_PLUGIN_URL . 'assets/js/hsz-block.js',
				[ 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n' ],
				HSZ_VERSION,
				true
			);

			// Pass a nonce and other data to the block script for secure AJAX.
			wp_localize_script(
				'hsz-block-editor-script',
				'hsz_block_params',
				[
					'nonce' => wp_create_nonce( 'hsz_analyze_url_nonce' ),
				]
			);
		}
	}
}
