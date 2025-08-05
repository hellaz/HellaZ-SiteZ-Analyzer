<?php
/**
 * Enhanced fallback values for HellaZ SiteZ Analyzer.
 *
 * This class provides fallback values when metadata extraction fails,
 * with enhanced security and performance fallbacks for Phase 1 features.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.0.2
 */

namespace HSZ;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Fallbacks
 *
 * Provides fallback values for various analysis components with enhanced capabilities.
 */
class Fallbacks {

	/**
	 * Get fallback title with enhanced logic.
	 *
	 * @param string $url Optional URL for context-based fallbacks.
	 * @return string The fallback title.
	 */
	public static function get_fallback_title( string $url = '' ): string {
		// Check for user-defined fallback first
		$custom_fallback = get_option( 'hsz_fallback_title', '' );
		if ( ! empty( $custom_fallback ) ) {
			return sanitize_text_field( $custom_fallback );
		}

		// Generate contextual fallbacks based on URL
		if ( ! empty( $url ) ) {
			$parsed_url = parse_url( $url );
			$host = $parsed_url['host'] ?? '';
			
			if ( $host ) {
				// Remove common prefixes
				$clean_host = preg_replace( '/^www\./', '', $host );
				$domain_parts = explode( '.', $clean_host );
				$domain_name = ucfirst( $domain_parts[0] );
				
				return sprintf( 
					/* translators: %s: Domain name */
					__( '%s - Website Analysis', 'hellaz-sitez-analyzer' ),
					$domain_name
				);
			}
		}

		// Default fallbacks with enhanced options
		$fallback_titles = [
			__( 'Website Analysis Report', 'hellaz-sitez-analyzer' ),
			__( 'Site Metadata Analysis', 'hellaz-sitez-analyzer' ),
			__( 'Web Content Analysis', 'hellaz-sitez-analyzer' ),
			__( 'Website Information', 'hellaz-sitez-analyzer' ),
			__( 'Site Analysis Results', 'hellaz-sitez-analyzer' )
		];

		// Return a random fallback for variety
		return $fallback_titles[ array_rand( $fallback_titles ) ];
	}

	/**
	 * Get fallback description with enhanced context awareness.
	 *
	 * @param string $url Optional URL for context-based fallbacks.
	 * @param array $context Optional context information.
	 * @return string The fallback description.
	 */
	public static function get_fallback_description( string $url = '', array $context = [] ): string {
		// Check for user-defined fallback first
		$custom_fallback = get_option( 'hsz_fallback_description', '' );
		if ( ! empty( $custom_fallback ) ) {
			return sanitize_textarea_field( $custom_fallback );
		}

		// Generate contextual descriptions based on analysis results
		if ( ! empty( $context ) ) {
			$description_parts = [];
			
			// Add security information
			if ( isset( $context['security_grade'] ) ) {
				$grade = $context['security_grade'];
				$description_parts[] = sprintf( 
					__( 'Security Grade: %s', 'hellaz-sitez-analyzer' ),
					$grade
				);
			}

			// Add performance information
			if ( isset( $context['performance_score'] ) ) {
				$score = $context['performance_score'];
				$description_parts[] = sprintf( 
					__( 'Performance Score: %d/100', 'hellaz-sitez-analyzer' ),
					$score
				);
			}

			// Add HTTPS status
			if ( isset( $context['https_enabled'] ) ) {
				$https_status = $context['https_enabled'] 
					? __( 'HTTPS enabled', 'hellaz-sitez-analyzer' )
					: __( 'HTTPS not detected', 'hellaz-sitez-analyzer' );
				$description_parts[] = $https_status;
			}

			if ( ! empty( $description_parts ) ) {
				return __( 'Comprehensive website analysis including: ', 'hellaz-sitez-analyzer' ) . implode( ', ', $description_parts ) . '.';
			}
		}

		// URL-based contextual descriptions
		if ( ! empty( $url ) ) {
			$parsed_url = parse_url( $url );
			$host = $parsed_url['host'] ?? '';
			
			if ( $host ) {
				return sprintf( 
					/* translators: %s: Domain name */
					__( 'Detailed analysis and security assessment of %s including metadata extraction, performance metrics, and security evaluation.', 'hellaz-sitez-analyzer' ),
					$host
				);
			}
		}

		// Enhanced default fallbacks
		$fallback_descriptions = [
			__( 'Comprehensive website analysis including metadata extraction, security assessment, performance evaluation, and content quality analysis.', 'hellaz-sitez-analyzer' ),
			__( 'Detailed security and performance analysis with metadata extraction and social media profile discovery.', 'hellaz-sitez-analyzer' ),
			__( 'In-depth website evaluation covering security headers, SSL configuration, performance metrics, and SEO factors.', 'hellaz-sitez-analyzer' ),
			__( 'Complete website analysis featuring security scanning, performance testing, and comprehensive metadata extraction.', 'hellaz-sitez-analyzer' ),
			__( 'Professional website assessment including security analysis, performance optimization insights, and content evaluation.', 'hellaz-sitez-analyzer' )
		];

		return $fallback_descriptions[ array_rand( $fallback_descriptions ) ];
	}

	/**
	 * Get fallback image with enhanced selection logic.
	 *
	 * @param string $url Optional URL for context-based fallbacks.
	 * @param array $context Optional context information.
	 * @return string The fallback image URL.
	 */
	public static function get_fallback_image( string $url = '', array $context = [] ): string {
		// Check for user-defined fallback first
		$custom_fallback = get_option( 'hsz_fallback_image', '' );
		if ( ! empty( $custom_fallback ) ) {
			// Decrypt if encrypted
			$decrypted = Utils::decrypt( $custom_fallback );
			return $decrypted !== false ? esc_url( $decrypted ) : esc_url( $custom_fallback );
		}

		// Context-based image selection
		if ( ! empty( $context ) ) {
			// Select image based on security grade
			if ( isset( $context['security_grade'] ) ) {
				$grade = strtolower( $context['security_grade'] );
				$grade_images = [
					'a' => HSZ_ASSETS_URL . 'images/security-excellent.png',
					'b' => HSZ_ASSETS_URL . 'images/security-good.png',
					'c' => HSZ_ASSETS_URL . 'images/security-fair.png',
					'd' => HSZ_ASSETS_URL . 'images/security-poor.png',
					'f' => HSZ_ASSETS_URL . 'images/security-critical.png'
				];
				
				if ( isset( $grade_images[ $grade ] ) ) {
					return $grade_images[ $grade ];
				}
			}

			// Select based on analysis type
			if ( isset( $context['analysis_type'] ) ) {
				$type_images = [
					'security' => HSZ_ASSETS_URL . 'images/security-analysis.png',
					'performance' => HSZ_ASSETS_URL . 'images/performance-analysis.png',
					'metadata' => HSZ_ASSETS_URL . 'images/metadata-analysis.png',
					'comprehensive' => HSZ_ASSETS_URL . 'images/comprehensive-analysis.png'
				];
				
				$type = $context['analysis_type'];
				if ( isset( $type_images[ $type ] ) ) {
					return $type_images[ $type ];
				}
			}
		}

		// Default fallback images
		$default_images = [
			HSZ_ASSETS_URL . 'images/website-analysis.png',
			HSZ_ASSETS_URL . 'images/site-scanner.png',
			HSZ_ASSETS_URL . 'images/web-security.png',
			HSZ_ASSETS_URL . 'images/performance-metrics.png'
		];

		return $default_images[ array_rand( $default_images ) ];
	}

	/**
	 * Get fallback security status.
	 *
	 * @param string $url Optional URL for context.
	 * @return array Fallback security information.
	 */
	public static function get_fallback_security(): array {
		return [
			'status' => 'unknown',
			'grade' => 'F',
			'score' => 0,
			'https_enabled' => false,
			'certificate_valid' => false,
			'security_headers' => 0,
			'vulnerabilities' => 0,
			'message' => __( 'Security analysis not available. Enable security scanning for detailed results.', 'hellaz-sitez-analyzer' )
		];
	}

	/**
	 * Get fallback performance metrics.
	 *
	 * @param string $url Optional URL for context.
	 * @return array Fallback performance information.
	 */
	public static function get_fallback_performance(): array {
		return [
			'score' => 0,
			'grade' => 'F',
			'core_web_vitals' => [
				'lcp' => null,
				'fid' => null,
				'cls' => null
			],
			'metrics' => [
				'ttfb' => null,
				'fcp' => null,
				'speed_index' => null
			],
			'message' => __( 'Performance analysis not available. Configure PageSpeed Insights API for detailed metrics.', 'hellaz-sitez-analyzer' )
		];
	}

	/**
	 * Get fallback social media information.
	 *
	 * @return array Fallback social media data.
	 */
	public static function get_fallback_social(): array {
		return [
			'profiles' => [],
			'count' => 0,
			'platforms' => [],
			'message' => __( 'No social media profiles detected or analysis not available.', 'hellaz-sitez-analyzer' )
		];
	}

	/**
	 * Get fallback preview information.
	 *
	 * @param string $url Optional URL for context.
	 * @return array Fallback preview data.
	 */
	public static function get_fallback_preview( string $url = '' ): array {
		return [
			'screenshot_url' => '',
			'thumbnail_url' => '',
			'available' => false,
			'placeholder_url' => self::get_placeholder_image( $url ),
			'message' => __( 'Website preview not available. Configure screenshot services for visual previews.', 'hellaz-sitez-analyzer' )
		];
	}

	/**
	 * Get placeholder image for missing previews.
	 *
	 * @param string $url Optional URL for context.
	 * @return string Placeholder image URL.
	 */
	public static function get_placeholder_image( string $url = '' ): string {
		// Generate a simple placeholder image URL
		$host = '';
		if ( ! empty( $url ) ) {
			$parsed_url = parse_url( $url );
			$host = $parsed_url['host'] ?? '';
		}

		// Use a service like placeholder.com or via.placeholder.com
		$placeholder_text = ! empty( $host ) ? urlencode( $host ) : 'Website';
		return "https://via.placeholder.com/600x400/cccccc/666666?text={$placeholder_text}";
	}

	/**
	 * Get fallback metadata quality assessment.
	 *
	 * @return array Fallback metadata quality data.
	 */
	public static function get_fallback_metadata_quality(): array {
		return [
			'score' => 0,
			'grade' => 'F',
			'issues' => [
				__( 'Metadata analysis not performed', 'hellaz-sitez-analyzer' )
			],
			'recommendations' => [
				__( 'Run metadata analysis to get detailed quality assessment', 'hellaz-sitez-analyzer' )
			],
			'completeness' => 0
		];
	}

	/**
	 * Get fallback grading information.
	 *
	 * @return array Fallback grading data.
	 */
	public static function get_fallback_grading(): array {
		return [
			'overall_grade' => 'F',
			'overall_score' => 0,
			'category_grades' => [
				'performance' => 'F',
				'security' => 'F',
				'content' => 'F',
				'usability' => 'F'
			],
			'category_scores' => [
				'performance' => 0,
				'security' => 0,
				'content' => 0,
				'usability' => 0
			],
			'message' => __( 'Website grading not available. Enable comprehensive analysis for detailed grading.', 'hellaz-sitez-analyzer' )
		];
	}

	/**
	 * Get contextual error messages.
	 *
	 * @param string $error_type Type of error.
	 * @param array $context Optional context information.
	 * @return string Contextual error message.
	 */
	public static function get_error_message( string $error_type, array $context = [] ): string {
		$error_messages = [
			'network_error' => __( 'Unable to connect to the website. Please check the URL and try again.', 'hellaz-sitez-analyzer' ),
			'timeout_error' => __( 'Request timed out. The website may be slow to respond or temporarily unavailable.', 'hellaz-sitez-analyzer' ),
			'invalid_url' => __( 'The provided URL is not valid. Please enter a complete URL including http:// or https://.', 'hellaz-sitez-analyzer' ),
			'api_error' => __( 'External API service is temporarily unavailable. Some analysis features may be limited.', 'hellaz-sitez-analyzer' ),
			'rate_limit' => __( 'Rate limit exceeded. Please wait before making another request.', 'hellaz-sitez-analyzer' ),
			'ssl_error' => __( 'SSL certificate verification failed. The website may have security issues.', 'hellaz-sitez-analyzer' ),
			'parsing_error' => __( 'Unable to parse website content. The page may have formatting issues.', 'hellaz-sitez-analyzer' ),
			'access_denied' => __( 'Access denied by the website. The site may be blocking analysis requests.', 'hellaz-sitez-analyzer' ),
			'not_found' => __( 'The requested page was not found (404 error).', 'hellaz-sitez-analyzer' ),
			'server_error' => __( 'The website server returned an error. Please try again later.', 'hellaz-sitez-analyzer' )
		];

		$base_message = $error_messages[ $error_type ] ?? __( 'An unexpected error occurred during analysis.', 'hellaz-sitez-analyzer' );

		// Add context-specific information
		if ( ! empty( $context ) ) {
			if ( isset( $context['http_code'] ) ) {
				$base_message .= ' ' . sprintf( __( '(HTTP %d)', 'hellaz-sitez-analyzer' ), $context['http_code'] );
			}
			
			if ( isset( $context['retry_suggestion'] ) && $context['retry_suggestion'] ) {
				$base_message .= ' ' . __( 'You may try again or contact support if the problem persists.', 'hellaz-sitez-analyzer' );
			}
		}

		return $base_message;
	}

	/**
	 * Get fallback recommendation based on analysis results.
	 *
	 * @param string $category Recommendation category.
	 * @param array $context Optional context information.
	 * @return array Fallback recommendation.
	 */
	public static function get_fallback_recommendation( string $category, array $context = [] ): array {
		$recommendations = [
			'security' => [
				'title' => __( 'Improve Website Security', 'hellaz-sitez-analyzer' ),
				'description' => __( 'Enable HTTPS, implement security headers, and regularly update your website software.', 'hellaz-sitez-analyzer' ),
				'priority' => 'high'
			],
			'performance' => [
				'title' => __( 'Optimize Website Performance', 'hellaz-sitez-analyzer' ),
				'description' => __( 'Optimize images, minify CSS/JS, enable caching, and use a content delivery network.', 'hellaz-sitez-analyzer' ),
				'priority' => 'medium'
			],
			'content' => [
				'title' => __( 'Enhance Content Quality', 'hellaz-sitez-analyzer' ),
				'description' => __( 'Add proper meta descriptions, optimize page titles, and include relevant structured data.', 'hellaz-sitez-analyzer' ),
				'priority' => 'medium'
			],
			'accessibility' => [
				'title' => __( 'Improve Accessibility', 'hellaz-sitez-analyzer' ),
				'description' => __( 'Add alt text to images, ensure proper heading structure, and improve color contrast.', 'hellaz-sitez-analyzer' ),
				'priority' => 'medium'
			]
		];

		return $recommendations[ $category ] ?? [
			'title' => __( 'General Website Improvement', 'hellaz-sitez-analyzer' ),
			'description' => __( 'Regular maintenance and updates help ensure optimal website performance and security.', 'hellaz-sitez-analyzer' ),
			'priority' => 'low'
		];
	}

	/**
	 * Check if fallback should be used based on settings.
	 *
	 * @param string $feature Feature name.
	 * @return bool True if fallback should be used.
	 */
	public static function should_use_fallback( string $feature ): bool {
		$fallback_settings = [
			'security' => get_option( 'hsz_security_analysis_enabled', true ),
			'performance' => get_option( 'hsz_performance_analysis_enabled', true ),
			'preview' => get_option( 'hsz_preview_generation_enabled', true ),
			'social' => true, // Always enabled
			'metadata' => true // Always enabled
		];

		return ! ( $fallback_settings[ $feature ] ?? true );
	}

	/**
	 * Get feature availability status.
	 *
	 * @return array Feature availability information.
	 */
	public static function get_feature_availability(): array {
		return [
			'security_analysis' => [
				'enabled' => get_option( 'hsz_security_analysis_enabled', true ),
				'apis_configured' => ! empty( get_option( 'hsz_virustotal_api_key' ) ) || get_option( 'hsz_ssl_labs_enabled' ),
				'status' => 'available'
			],
			'performance_analysis' => [
				'enabled' => get_option( 'hsz_performance_analysis_enabled', true ),
				'apis_configured' => ! empty( get_option( 'hsz_pagespeed_api_key' ) ),
				'status' => 'available'
			],
			'preview_generation' => [
				'enabled' => get_option( 'hsz_preview_generation_enabled', true ),
				'apis_configured' => ! empty( get_option( 'hsz_htmlcsstoimage_api_key' ) ) || get_option( 'hsz_thum_io_enabled', true ),
				'status' => 'available'
			],
			'metadata_extraction' => [
				'enabled' => true,
				'apis_configured' => true,
				'status' => 'always_available'
			],
			'social_detection' => [
				'enabled' => true,
				'apis_configured' => true,
				'status' => 'always_available'
			]
		];
	}
}
