<?php
/**
 * Shortcode functionality for HellaZ SiteZ Analyzer.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.1.0
 */

namespace HSZ;

defined( 'ABSPATH' ) || exit;

class Shortcode {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_shortcodes' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_shortcode_assets' ] );
		add_action( 'wp_ajax_hsz_shortcode_refresh', [ $this, 'ajax_refresh_shortcode' ] );
		add_action( 'wp_ajax_nopriv_hsz_shortcode_refresh', [ $this, 'ajax_refresh_shortcode' ] );
	}

	/**
	 * Register shortcodes
	 */
	public function register_shortcodes(): void {
		add_shortcode( 'sitez_analyzer', [ $this, 'render_analyzer_shortcode' ] );
		add_shortcode( 'hsz_analyzer', [ $this, 'render_analyzer_shortcode' ] );
	}

	/**
	 * Enqueue shortcode assets
	 */
	public function enqueue_shortcode_assets(): void {
		global $post;
		
		// Only enqueue if shortcode is present
		if ( $post && ( 
			has_shortcode( $post->post_content, 'sitez_analyzer' ) || 
			has_shortcode( $post->post_content, 'hsz_analyzer' ) 
		)) {
			wp_enqueue_style( 
				'hsz-shortcode', 
				HSZ_PLUGIN_URL . 'assets/css/hsz-shortcode.css', 
				[], 
				HSZ_VERSION 
			);

			wp_enqueue_script( 
				'hsz-shortcode', 
				HSZ_PLUGIN_URL . 'assets/js/hsz-shortcode.js', 
				[ 'jquery' ], 
				HSZ_VERSION, 
				true 
			);

			wp_localize_script( 'hsz-shortcode', 'hszShortcode', [
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'hsz_shortcode_nonce' ),
				'strings' => [
					'refreshing' => __( 'Refreshing analysis...', 'hellaz-sitez-analyzer' ),
					'error' => __( 'Failed to refresh analysis. Please try again.', 'hellaz-sitez-analyzer' ),
					'refresh' => __( 'Refresh Analysis', 'hellaz-sitez-analyzer' ),
					'lastUpdated' => __( 'Last updated:', 'hellaz-sitez-analyzer' )
				]
			]);
		}
	}

	/**
	 * Render the analyzer shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @param string $tag Shortcode tag.
	 * @return string Rendered HTML.
	 */
	public function render_analyzer_shortcode( $atts, $content = '', $tag = '' ): string {
		// Parse attributes with defaults
		$atts = shortcode_atts( [
			'url' => '',
			'display_type' => 'full', // full, compact, minimal
			'show_social' => 'true',
			'show_metadata' => 'true',
			'show_refresh' => 'false',
			'cache_duration' => '6',
			'template' => '',
			'class' => ''
		], $atts, $tag );

		// Sanitize attributes
		$url = trim( $atts['url'] );
		$display_type = sanitize_key( $atts['display_type'] );
		$show_social = filter_var( $atts['show_social'], FILTER_VALIDATE_BOOLEAN );
		$show_metadata = filter_var( $atts['show_metadata'], FILTER_VALIDATE_BOOLEAN );
		$show_refresh = filter_var( $atts['show_refresh'], FILTER_VALIDATE_BOOLEAN );
		$cache_duration = max( 1, min( 168, absint( $atts['cache_duration'] ) ) );
		$template = sanitize_file_name( $atts['template'] );
		$css_class = sanitize_html_class( $atts['class'] );

		// Validate URL
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return $this->render_error_message( 
				__( 'Please provide a valid URL.', 'hellaz-sitez-analyzer' ),
				$css_class
			);
		}

		// Check rate limiting per IP for shortcode usage
		$client_ip = Utils::get_client_ip();
		$rate_limit_key = 'shortcode_' . md5( $client_ip );
		if ( ! Utils::check_rate_limit( $rate_limit_key, 20, HOUR_IN_SECONDS ) ) {
			return $this->render_error_message( 
				__( 'Rate limit exceeded. Please try again later.', 'hellaz-sitez-analyzer' ),
				$css_class
			);
		}

		try {
			// Check cache first
			$cache_key = 'shortcode_' . md5( $url . $display_type . (int) $show_social . (int) $show_metadata . $template );
			$cached_data = Cache::get( $cache_key, 'shortcode' );
			
			if ( $cached_data === false ) {
				// Fetch and analyze website
				$html = Utils::get_html( $url );
				if ( is_wp_error( $html ) ) {
					throw new \Exception( $html->get_error_message() );
				}

				// Initialize analysis data array
				$analysis_data = [
					'url' => $url,
					'timestamp' => current_time( 'mysql', true )
				];

				// Extract metadata if enabled
				if ( $show_metadata ) {
					$metadata_obj = new Metadata();
					$analysis_data['metadata'] = $metadata_obj->extract_metadata( $url, $html );
					
					if ( isset( $analysis_data['metadata']['error'] ) ) {
						throw new \Exception( $analysis_data['metadata']['error'] );
					}
				}

				// Extract social media profiles if enabled
				if ( $show_social ) {
					$social_obj = new SocialMedia();
					$analysis_data['social'] = $social_obj->extract_social_profiles( $html, $url );
				}

				// Cache the data
				$cache_time = $cache_duration * HOUR_IN_SECONDS;
				Cache::set( $cache_key, $analysis_data, $cache_time, 'shortcode' );
				$cached_data = $analysis_data;
			}

			// Render the analysis results
			return $this->render_analysis_results( $cached_data, $display_type, $template, $css_class, $show_refresh, $cache_key );

		} catch ( \Exception $e ) {
			Utils::log_error( 'Shortcode analysis error: ' . $e->getMessage(), __FILE__, __LINE__ );
			return $this->render_error_message( 
				__( 'Error analyzing website. Please try again later.', 'hellaz-sitez-analyzer' ),
				$css_class
			);
		}
	}

	/**
	 * Render analysis results
	 *
	 * @param array $analysis_data Analysis data.
	 * @param string $display_type Display type.
	 * @param string $template Custom template.
	 * @param string $css_class CSS class.
	 * @param bool $show_refresh Show refresh button.
	 * @param string $cache_key Cache key for refresh.
	 * @return string Rendered HTML.
	 */
	private function render_analysis_results( array $analysis_data, string $display_type, string $template, string $css_class, bool $show_refresh, string $cache_key ): string {
		ob_start();
		
		// Determine template path
		$template_path = $this->get_template_path( $display_type, $template );
		
		// Add wrapper class
		$wrapper_class = 'hsz-shortcode-wrapper hsz-' . $display_type;
		if ( $css_class ) {
			$wrapper_class .= ' ' . $css_class;
		}

		$shortcode_id = 'hsz-shortcode-' . uniqid();

		echo '<div class="' . esc_attr( $wrapper_class ) . '" id="' . esc_attr( $shortcode_id ) . '">';
		
		// Show refresh button if enabled
		if ( $show_refresh ) {
			echo '<div class="hsz-shortcode-controls">';
			echo '<button type="button" class="hsz-refresh-btn" data-cache-key="' . esc_attr( $cache_key ) . '" data-target="' . esc_attr( $shortcode_id ) . '">';
			echo esc_html__( 'Refresh Analysis', 'hellaz-sitez-analyzer' );
			echo '</button>';
			echo '<span class="hsz-last-updated">';
			printf( 
				esc_html__( 'Last updated: %s', 'hellaz-sitez-analyzer' ), 
				esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $analysis_data['timestamp'] ) )
			);
			echo '</span>';
			echo '</div>';
		}

		echo '<div class="hsz-shortcode-content">';
		
		if ( file_exists( $template_path ) ) {
			// Extract data for template use
			$metadata = $analysis_data['metadata'] ?? [];
			$social = $analysis_data['social'] ?? [];
			$url = $analysis_data['url'];
			
			include $template_path;
		} else {
			// Fallback rendering
			echo $this->render_fallback_display( $analysis_data, $display_type );
		}
		
		echo '</div>'; // .hsz-shortcode-content
		echo '</div>'; // .hsz-shortcode-wrapper
		
		return ob_get_clean();
	}

	/**
	 * Get template path for rendering
	 *
	 * @param string $display_type Display type.
	 * @param string $custom_template Custom template name.
	 * @return string Template file path.
	 */
	private function get_template_path( string $display_type, string $custom_template ): string {
		// Custom template takes precedence
		if ( $custom_template ) {
			$custom_path = HSZ_PLUGIN_PATH . 'templates/shortcode-' . $custom_template . '.php';
			if ( file_exists( $custom_path ) ) {
				return $custom_path;
			}
		}

		// Theme template override
		$theme_template = get_template_directory() . '/hsz-templates/shortcode-' . $display_type . '.php';
		if ( file_exists( $theme_template ) ) {
			return $theme_template;
		}

		// Plugin template
		$plugin_template = HSZ_PLUGIN_PATH . 'templates/shortcode-' . $display_type . '.php';
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}

		// Fallback to metadata templates
		$template_mode = get_option( 'hsz_template_mode', 'classic' );
		if ( $display_type === 'compact' ) {
			$fallback_path = HSZ_PLUGIN_PATH . "templates/metadata-compact.php";
		} else {
			$fallback_path = HSZ_PLUGIN_PATH . "templates/metadata-{$template_mode}.php";
		}

		if ( file_exists( $fallback_path ) ) {
			return $fallback_path;
		}

		// Final fallback
		return HSZ_PLUGIN_PATH . 'templates/metadata-classic.php';
	}

	/**
	 * Render fallback display when template is not found
	 *
	 * @param array $analysis_data Analysis data.
	 * @param string $display_type Display type.
	 * @return string Fallback HTML.
	 */
	private function render_fallback_display( array $analysis_data, string $display_type ): string {
		$metadata = $analysis_data['metadata'] ?? [];
		$social = $analysis_data['social'] ?? [];
		$url = $analysis_data['url'];

		$output = '<div class="hsz-fallback-display">';
		
		// Basic metadata display
		if ( ! empty( $metadata ) ) {
			$title = $metadata['title'] ?? parse_url( $url, PHP_URL_HOST );
			$description = $metadata['description'] ?? '';
			$favicon = $metadata['favicon'] ?? '';

			$output .= '<div class="hsz-basic-info">';
			
			if ( $favicon ) {
				$output .= '<img src="' . esc_url( $favicon ) . '" alt="Favicon" class="hsz-favicon" width="16" height="16" loading="lazy">';
			}
			
			$output .= '<h4><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $title ) . '</a></h4>';
			
			if ( $description ) {
				$word_limit = $display_type === 'minimal' ? 15 : ( $display_type === 'compact' ? 20 : 30 );
				$output .= '<p class="hsz-description">' . esc_html( wp_trim_words( $description, $word_limit ) ) . '</p>';
			}
			
			$output .= '</div>';
		}

		// Social profiles (compact view)
		if ( ! empty( $social ) && $display_type !== 'minimal' ) {
			$output .= '<div class="hsz-social-profiles">';
			$output .= '<strong>' . __( 'Social Profiles:', 'hellaz-sitez-analyzer' ) . '</strong> ';
			
			$profile_links = [];
			$max_profiles = $display_type === 'compact' ? 3 : 5;
			foreach ( array_slice( $social, 0, $max_profiles ) as $profile ) {
				$platform = ucfirst( $profile['platform'] ?? 'Unknown' );
				$profile_url = $profile['url'] ?? '#';
				$profile_links[] = '<a href="' . esc_url( $profile_url ) . '" target="_blank" rel="noopener" class="hsz-social-link hsz-' . esc_attr( strtolower( $profile['platform'] ?? 'unknown' ) ) . '">' . esc_html( $platform ) . '</a>';
			}
			
			$output .= implode( ', ', $profile_links );
			$output .= '</div>';
		}

		// Metadata quality info for full display
		if ( $display_type === 'full' && isset( $metadata['metadata_quality'] ) ) {
			$quality = $metadata['metadata_quality'];
			$output .= '<div class="hsz-quality-info">';
			$output .= '<strong>' . __( 'SEO Score:', 'hellaz-sitez-analyzer' ) . '</strong> ';
			$output .= '<span class="hsz-score hsz-grade-' . esc_attr( strtolower( $quality['grade'] ) ) . '">';
			$output .= esc_html( $quality['score'] ) . '/100 (' . esc_html( $quality['grade'] ) . ')';
			$output .= '</span>';
			$output .= '</div>';
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Render error message
	 *
	 * @param string $message Error message.
	 * @param string $css_class Additional CSS class.
	 * @return string Error HTML.
	 */
	private function render_error_message( string $message, string $css_class = '' ): string {
		$wrapper_class = 'hsz-shortcode-error';
		if ( $css_class ) {
			$wrapper_class .= ' ' . $css_class;
		}

		return '<div class="' . esc_attr( $wrapper_class ) . '"><p class="hsz-error-text">' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * AJAX handler for refreshing shortcode content
	 */
	public function ajax_refresh_shortcode(): void {
		check_ajax_referer( 'hsz_shortcode_nonce', 'nonce' );

		$cache_key = isset( $_POST['cache_key'] ) ? sanitize_text_field( $_POST['cache_key'] ) : '';

		if ( empty( $cache_key ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid cache key.', 'hellaz-sitez-analyzer' ) ] );
		}

		// Clear the cache entry
		Cache::delete( $cache_key, 'shortcode' );

		wp_send_json_success( [ 
			'message' => __( 'Analysis refreshed successfully.', 'hellaz-sitez-analyzer' ),
			'timestamp' => current_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) )
		] );
	}
}
