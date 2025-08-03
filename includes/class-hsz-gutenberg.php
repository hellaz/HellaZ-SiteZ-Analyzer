<?php
/**
 * Gutenberg block registration and rendering for HellaZ SiteZ Analyzer.
 *
 * @package HZ_SiteZ_Analyzer
 * @since 1.0.0
 */

namespace HSZ;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Gutenberg {

	public function __construct() {
		add_action( 'init', [ $this, 'register_block' ] );
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_assets' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
	}

	public function register_block(): void {
		register_block_type(
			'hsz/analyzer',
			[
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
				'render_callback' => [ $this, 'render_block' ],
			]
		);
	}

	public function enqueue_block_assets(): void {
		// Enqueue frontend styles, if any
		wp_enqueue_style(
			'hsz-block-style',
			HSZ_PLUGIN_URL . 'assets/css/hsz-block.css',
			[],
			HSZ_VERSION
		);
	}

	public function enqueue_block_editor_assets(): void {
		wp_enqueue_script(
			'hsz-block-script',
			HSZ_PLUGIN_URL . 'assets/js/hsz-block.js',
			[
				'wp-blocks',
				'wp-element',
				'wp-editor',
				'wp-components',
				'wp-i18n',
				'wp-data',
				'wp-compose',
			],
			HSZ_VERSION,
			true
		);

		// Localize nonce aligned to the above script handle and uniform action key
		wp_localize_script(
			'hsz-block-script',
			'hsz_block_params',
			[
				'nonce' => wp_create_nonce( 'hsz_analyze_nonce' ),
			]
		);
	}

	public function render_block( array $attributes ): string {
		$url         = isset( $attributes['url'] ) ? sanitize_text_field( $attributes['url'] ) : '';
		$displayType = isset( $attributes['displayType'] ) ? sanitize_text_field( $attributes['displayType'] ) : 'full';

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return '<div class="hsz-error">' . esc_html__( 'Please enter a valid URL.', 'hsz' ) . '</div>';
		}

		$template_mode = get_option( 'hsz_template_mode' );

		if ( empty( $template_mode ) || ! in_array( $template_mode, [ 'classic', 'modern', 'compact' ], true ) ) {
			$template_mode = 'classic';
		}

		$template_path = HSZ_PLUGIN_PATH . "templates/metadata-{$template_mode}.php";

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[HSZ] Gutenberg block - Template path checked: ' . $template_path );
		}

		$html = Utils::get_html( $url );
		if ( is_wp_error( $html ) ) {
			$error_message = esc_html( $html->get_error_message() );
			Utils::log_error( sprintf( 'Gutenberg block failed to fetch URL %s: %s', $url, $error_message ) );
			return '<div class="hsz-error">' . esc_html__( 'Failed to retrieve content.', 'hsz' ) . '</div>';
		}

		try {
			$metadata = ( new Metadata() )->extract_metadata( $url, $html );
			$social   = ( new SocialMedia() )->extract( $html, $url );
		} catch ( \Throwable $e ) {
			Utils::log_error( 'Gutenberg block - Unexpected error: ' . $e->getMessage() );
			return '<div class="hsz-error">' . esc_html__( 'An error occurred.', 'hsz' ) . '</div>';
		}

		if ( ! file_exists( $template_path ) ) {
			return '<div class="hsz-error">' . esc_html__( 'Rendering template not found.', 'hsz' ) . '</div>';
		}

		$display_title = $metadata['title'] ?? Fallbacks::get_fallback_title();
		$display_description = $metadata['description'] ?? Fallbacks::get_fallback_description();
		$favicon = $metadata['favicon'] ?? Fallbacks::get_fallback_image();

		ob_start();
		include $template_path;
		return ob_get_clean();
	}
}
