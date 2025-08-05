<?php
/**
 * Gutenberg block functionality for HellaZ SiteZ Analyzer.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.1.0
 */

namespace HSZ;

defined( 'ABSPATH' ) || exit;

class Gutenberg {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_block' ] );
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_assets' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
		add_action( 'wp_ajax_hsz_block_preview', [ $this, 'ajax_block_preview' ] );
	}

	/**
	 * Register the Gutenberg block
	 */
	public function register_block(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type( 'hsz/analyzer', [
			'attributes' => [
				'url' => [
					'type' => 'string',
					'default' => '',
				],
				'displayType' => [
					'type' => 'string',
					'default' => 'full',
				],
				'showSocial' => [
					'type' => 'boolean',
					'default' => true,
				],
				'showPerformance' => [
					'type' => 'boolean',
					'default' => false,
				],
				'showSecurity' => [
					'type' => 'boolean',
					'default' => false,
				],
				'customClass' => [
					'type' => 'string',
					'default' => '',
				],
			],
			'render_callback' => [ $this, 'render_block' ],
		]);
	}

	/**
	 * Enqueue frontend block styles/scripts
	 */
	public function enqueue_block_assets(): void {
		wp_enqueue_style( 
			'hsz-block-style', 
			HSZ_PLUGIN_URL . 'assets/css/hsz-block.css', 
			[], 
			HSZ_VERSION 
		);
	}

	/**
	 * Enqueue block editor styles/scripts
	 */
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
				'wp-block-editor'
			], 
			HSZ_VERSION, 
			true 
		);

		wp_enqueue_style(
			'hsz-block-editor-style',
			HSZ_PLUGIN_URL . 'assets/css/hsz-block-editor.css',
			[ 'wp-edit-blocks' ],
			HSZ_VERSION
		);

		wp_localize_script( 'hsz-block-script', 'hszBlock', [
			'nonce' => wp_create_nonce( 'hsz_block_nonce' ),
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'strings' => [
				'title' => __( 'HellaZ SiteZ Analyzer', 'hellaz-sitez-analyzer' ),
				'description' => __( 'Analyze and display website metadata, social profiles, and more.', 'hellaz-sitez-analyzer' ),
				'urlLabel' => __( 'Website URL', 'hellaz-sitez-analyzer' ),
				'urlPlaceholder' => __( 'Enter website URL...', 'hellaz-sitez-analyzer' ),
				'displayType' => __( 'Display Type', 'hellaz-sitez-analyzer' ),
				'full' => __( 'Full', 'hellaz-sitez-analyzer' ),
				'compact' => __( 'Compact', 'hellaz-sitez-analyzer' ),
				'minimal' => __( 'Minimal', 'hellaz-sitez-analyzer' ),
				'showSocial' => __( 'Show Social Profiles', 'hellaz-sitez-analyzer' ),
				'showPerformance' => __( 'Show Performance Data', 'hellaz-sitez-analyzer' ),
				'showSecurity' => __( 'Show Security Analysis', 'hellaz-sitez-analyzer' ),
				'customClass' => __( 'Custom CSS Class', 'hellaz-sitez-analyzer' ),
				'analyzing' => __( 'Analyzing website...', 'hellaz-sitez-analyzer' ),
				'preview' => __( 'Preview', 'hellaz-sitez-analyzer' ),
				'refresh' => __( 'Refresh Analysis', 'hellaz-sitez-analyzer' ),
				'error' => __( 'Error analyzing website. Please check the URL and try again.', 'hellaz-sitez-analyzer' ),
				'invalidUrl' => __( 'Please enter a valid URL.', 'hellaz-sitez-analyzer' ),
				'settings' => __( 'Settings', 'hellaz-sitez-analyzer' ),
				'advanced' => __( 'Advanced Options', 'hellaz-sitez-analyzer' )
			],
			'features' => [
				'performance' => class_exists( 'HSZ\\Performance' ),
				'security' => class_exists( 'HSZ\\Security' ),
				'grading' => class_exists( 'HSZ\\Grading' )
			]
		]);
	}

	/**
	 * Render the block server-side
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	public function render_block( array $attributes ): string {
		$url = isset( $attributes['url'] ) ? sanitize_text_field( $attributes['url'] ) : '';
		$display_type = isset( $attributes['displayType'] ) ? sanitize_text_field( $attributes['displayType'] ) : 'full';
		$show_social = isset( $attributes['showSocial'] ) ? (bool) $attributes['showSocial'] : true;
		$show_performance = isset( $attributes['showPerformance'] ) ? (bool) $attributes['showPerformance'] : false;
		$show_security = isset( $attributes['showSecurity'] ) ? (bool) $attributes['showSecurity'] : false;
		$custom_class = isset( $attributes['customClass'] ) ? sanitize_html_class( $attributes['customClass'] ) : '';

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return '<div class="hsz-block-error"><p>' . esc_html__( 'Please provide a valid URL in the block settings.', 'hellaz-sitez-analyzer' ) . '</p></div>';
		}

		try {
			// Use shortcode class for consistency if available
			if ( class_exists( 'HSZ\\Shortcode' ) ) {
				$shortcode = new Shortcode();
				return $shortcode->render_analyzer_shortcode( [
					'url' => $url,
					'display_type' => $display_type,
					'show_social' => $show_social ? 'true' : 'false',
					'show_performance' => $show_performance ? 'true' : 'false',
					'show_security' => $show_security ? 'true' : 'false',
					'class' => 'hsz-gutenberg-block ' . $custom_class
				]);
			}

			// Fallback rendering if shortcode class not available
			return $this->render_fallback_block( $url, $display_type, $show_social, $custom_class );

		} catch ( \Exception $e ) {
			Utils::log_error( 'Gutenberg block rendering error: ' . $e->getMessage(), __FILE__, __LINE__ );
			return '<div class="hsz-block-error"><p>' . esc_html__( 'Error rendering analyzer block. Please try again.', 'hellaz-sitez-analyzer' ) . '</p></div>';
		}
	}

	/**
	 * Fallback block rendering
	 *
	 * @param string $url Website URL.
	 * @param string $display_type Display type.
	 * @param bool $show_social Show social profiles.
	 * @param string $custom_class Custom CSS class.
	 * @return string Rendered HTML.
	 */
	private function render_fallback_block( string $url, string $display_type, bool $show_social, string $custom_class ): string {
		// Check cache first
		$cache_key = 'block_' . md5( $url . $display_type . (int) $show_social );
		$cached_content = Cache::get( $cache_key, 'gutenberg' );
		
		if ( $cached_content !== false ) {
			return $cached_content;
		}

		$html = Utils::get_html( $url );
		if ( is_wp_error( $html ) ) {
			return '<div class="hsz-block-error"><p>' . esc_html__( 'Failed to retrieve website content.', 'hellaz-sitez-analyzer' ) . '</p></div>';
		}

		$metadata_obj = new Metadata();
		$metadata = $metadata_obj->extract_metadata( $url, $html );

		if ( isset( $metadata['error'] ) ) {
			return '<div class="hsz-block-error"><p>' . esc_html__( 'Error analyzing metadata.', 'hellaz-sitez-analyzer' ) . '</p></div>';
		}

		$social = [];
		if ( $show_social ) {
			$social_obj = new SocialMedia();
			$social = $social_obj->extract_social_profiles( $html, $url );
		}

		$wrapper_class = 'hsz-gutenberg-block hsz-' . $display_type;
		if ( $custom_class ) {
			$wrapper_class .= ' ' . $custom_class;
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( $wrapper_class ); ?>">
			<?php
			$template_mode = get_option( 'hsz_template_mode', 'classic' );
			$template_path = HSZ_PLUGIN_PATH . "templates/block-{$display_type}.php";
			
			if ( ! file_exists( $template_path ) ) {
				if ( $display_type === 'compact' ) {
					$template_path = HSZ_PLUGIN_PATH . "templates/metadata-compact.php";
				} else {
					$template_path = HSZ_PLUGIN_PATH . "templates/metadata-{$template_mode}.php";
				}
			}
			
			if ( ! file_exists( $template_path ) ) {
				$template_path = HSZ_PLUGIN_PATH . 'templates/metadata-classic.php';
			}

			if ( file_exists( $template_path ) ) {
				include $template_path;
			} else {
				$this->render_simple_fallback( $url, $metadata, $social );
			}
			?>
		</div>
		<?php
		$result = ob_get_clean();

		// Cache the result
		Cache::set( $cache_key, $result, HOUR_IN_SECONDS * 6, 'gutenberg' );

		return $result;
	}

	/**
	 * Render simple fallback when no templates are available
	 *
	 * @param string $url Website URL.
	 * @param array $metadata Metadata.
	 * @param array $social Social profiles.
	 */
	private function render_simple_fallback( string $url, array $metadata, array $social ): void {
		$title = $metadata['title'] ?? parse_url( $url, PHP_URL_HOST );
		$description = $metadata['description'] ?? '';
		$favicon = $metadata['favicon'] ?? '';
		?>
		<div class="hsz-simple-fallback">
			<?php if ( $favicon ): ?>
				<img src="<?php echo esc_url( $favicon ); ?>" alt="Favicon" width="16" height="16" style="display: inline; margin-right: 8px;">
			<?php endif; ?>
			
			<h4 style="margin: 0; display: inline;">
				<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">
					<?php echo esc_html( $title ); ?>
				</a>
			</h4>
			
			<?php if ( $description ): ?>
				<p style="margin: 8px 0; color: #666;">
					<?php echo esc_html( wp_trim_words( $description, 20 ) ); ?>
				</p>
			<?php endif; ?>
			
			<?php if ( ! empty( $social ) ): ?>
				<div style="margin-top: 8px;">
					<strong><?php esc_html_e( 'Social:', 'hellaz-sitez-analyzer' ); ?></strong>
					<?php
					$social_links = [];
					foreach ( array_slice( $social, 0, 3 ) as $profile ) {
						$social_links[] = '<a href="' . esc_url( $profile['url'] ) . '" target="_blank" rel="noopener">' . esc_html( ucfirst( $profile['platform'] ) ) . '</a>';
					}
					echo implode( ', ', $social_links );
					?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * AJAX handler for block preview
	 */
	public function ajax_block_preview(): void {
		check_ajax_referer( 'hsz_block_nonce', 'nonce' );

		$url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '';
		$display_type = isset( $_POST['displayType'] ) ? sanitize_key( $_POST['displayType'] ) : 'full';
		$show_social = isset( $_POST['showSocial'] ) ? (bool) $_POST['showSocial'] : true;

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid URL provided.', 'hellaz-sitez-analyzer' ) ] );
		}

		try {
			$html = $this->render_block( [
				'url' => $url,
				'displayType' => $display_type,
				'showSocial' => $show_social
			]);

			wp_send_json_success( [ 'html' => $html ] );

		} catch ( \Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}
}
