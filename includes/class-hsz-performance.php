<?php
/**
 * Performance analysis for HellaZ SiteZ Analyzer.
 *
 * Handles website performance testing and Core Web Vitals analysis.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.1.0
 */

namespace HSZ;

defined( 'ABSPATH' ) || exit;

class Performance {

	/**
	 * Performance scoring weights
	 */
	private const PERFORMANCE_WEIGHTS = [
		'core_web_vitals' => 0.40,
		'lighthouse_metrics' => 0.30,
		'server_response' => 0.20,
		'resource_optimization' => 0.10
	];

	/**
	 * Core Web Vitals thresholds
	 */
	private const CWV_THRESHOLDS = [
		'lcp' => ['good' => 2.5, 'needs_improvement' => 4.0], // seconds
		'fid' => ['good' => 100, 'needs_improvement' => 300], // milliseconds
		'cls' => ['good' => 0.1, 'needs_improvement' => 0.25], // unitless
		'fcp' => ['good' => 1.8, 'needs_improvement' => 3.0], // seconds
		'ttfb' => ['good' => 0.8, 'needs_improvement' => 1.8] // seconds
	];

	/**
	 * Perform comprehensive performance analysis
	 *
	 * @param string $url Website URL to analyze.
	 * @param array $options Analysis options.
	 * @return array Performance analysis results.
	 */
	public function analyze_performance( string $url, array $options = [] ): array {
		// Validate URL
		$url_validation = Utils::validate_url( $url );
		if ( is_wp_error( $url_validation ) ) {
			return ['error' => $url_validation->get_error_message()];
		}

		// Set default options
		$options = wp_parse_args( $options, [
			'pagespeed_analysis' => get_option( 'hsz_pagespeed_enabled', false ),
			'lighthouse_analysis' => get_option( 'hsz_lighthouse_enabled', false ),
			'webpagetest_analysis' => get_option( 'hsz_webpagetest_enabled', false ),
			'server_timing' => get_option( 'hsz_server_timing_enabled', true ),
			'resource_analysis' => get_option( 'hsz_resource_analysis_enabled', true ),
			'force_refresh' => false,
			'strategy' => 'mobile' // mobile or desktop
		]);

		// Check cache first
		$cache_key = 'performance_' . Utils::generate_url_hash( $url ) . '_' . md5( serialize( $options ) );
		$cached_data = Cache::get( $cache_key, 'performance' );
		if ( $cached_data && ! $options['force_refresh'] ) {
			return $cached_data;
		}

		Utils::start_timer( 'performance_analysis' );

		$performance_data = [
			'url' => $url,
			'timestamp' => current_time( 'mysql', true ),
			'strategy' => $options['strategy'],
			'core_web_vitals' => [],
			'lighthouse_metrics' => [],
			'pagespeed_data' => [],
			'webpagetest_data' => [],
			'server_metrics' => [],
			'resource_analysis' => [],
			'performance_score' => 0,
			'performance_grade' => 'F',
			'opportunities' => [],
			'diagnostics' => [],
			'recommendations' => [],
			'overall_status' => 'unknown',
			'analysis_time' => 0
		];

		// PageSpeed Insights Analysis
		if ( $options['pagespeed_analysis'] ) {
			$performance_data['pagespeed_data'] = $this->run_pagespeed_analysis( $url, $options['strategy'] );
			if ( ! isset( $performance_data['pagespeed_data']['error'] ) ) {
				$performance_data['core_web_vitals'] = $this->extract_core_web_vitals( $performance_data['pagespeed_data'] );
				$performance_data['lighthouse_metrics'] = $this->extract_lighthouse_metrics( $performance_data['pagespeed_data'] );
				$performance_data['opportunities'] = $this->extract_opportunities( $performance_data['pagespeed_data'] );
				$performance_data['diagnostics'] = $this->extract_diagnostics( $performance_data['pagespeed_data'] );
			}
		}

		// WebPageTest Analysis
		if ( $options['webpagetest_analysis'] ) {
			$performance_data['webpagetest_data'] = $this->run_webpagetest_analysis( $url, $options );
		}

		// Server Timing Analysis
		if ( $options['server_timing'] ) {
			$performance_data['server_metrics'] = $this->analyze_server_timing( $url );
		}

		// Resource Analysis
		if ( $options['resource_analysis'] ) {
			$performance_data['resource_analysis'] = $this->analyze_resources( $url );
		}

		// Calculate performance metrics
		$performance_data = $this->calculate_performance_metrics( $performance_data );

		// Generate recommendations
		$performance_data['recommendations'] = $this->generate_performance_recommendations( $performance_data );

		$performance_data['analysis_time'] = Utils::stop_timer( 'performance_analysis' );

		// Cache the results
		$cache_duration = get_option( 'hsz_performance_cache_duration', HOUR_IN_SECONDS * 6 );
		Cache::set( $cache_key, $performance_data, $cache_duration, 'performance' );

		// Store in enhanced database
		$this->store_performance_results( $url, $performance_data );

		return $performance_data;
	}

	/**
	 * Run PageSpeed Insights analysis
	 *
	 * @param string $url Website URL.
	 * @param string $strategy Analysis strategy (mobile/desktop).
	 * @return array PageSpeed results.
	 */
	private function run_pagespeed_analysis( string $url, string $strategy = 'mobile' ): array {
		$api_key = Utils::decrypt( get_option( 'hsz_pagespeed_api_key', '' ) );
		if ( empty( $api_key ) ) {
			return ['error' => __( 'PageSpeed Insights API key not configured.', 'hellaz-sitez-analyzer' )];
		}

		$api_url = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed?' . http_build_query([
			'url' => $url,
			'key' => $api_key,
			'strategy' => $strategy,
			'category' => 'performance'
		]);

		$start_time = microtime( true );
		$response = wp_remote_get( $api_url, [
			'timeout' => 60,
			'user-agent' => 'HellaZ SiteZ Analyzer/' . HSZ_VERSION
		]);
		$response_time = microtime( true ) - $start_time;
		$success = ! is_wp_error( $response );

		Utils::record_api_usage( 'pagespeed', 'runPagespeed', $response_time, $success );

		if ( is_wp_error( $response ) ) {
			return ['error' => $response->get_error_message()];
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code !== 200 ) {
			return ['error' => 'HTTP error ' . $response_code];
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return ['error' => 'Invalid JSON response'];
		}

		return $data ?? [];
	}

	/**
	 * Extract Core Web Vitals from PageSpeed data
	 *
	 * @param array $pagespeed_data PageSpeed Insights data.
	 * @return array Core Web Vitals metrics.
	 */
	private function extract_core_web_vitals( array $pagespeed_data ): array {
		$cwv_metrics = [];
		$lighthouse_result = $pagespeed_data['lighthouseResult'] ?? [];
		$audits = $lighthouse_result['audits'] ?? [];

		$cwv_mapping = [
			'largest-contentful-paint' => 'lcp',
			'first-input-delay' => 'fid',
			'cumulative-layout-shift' => 'cls',
			'first-contentful-paint' => 'fcp',
			'server-response-time' => 'ttfb'
		];

		foreach ( $cwv_mapping as $audit_key => $metric_key ) {
			if ( isset( $audits[ $audit_key ] ) ) {
				$audit = $audits[ $audit_key ];
				$value = $audit['numericValue'] ?? null;
				$display_value = $audit['displayValue'] ?? '';
				$score = $audit['score'] ?? null;

				// Convert values to appropriate units
				if ( $metric_key === 'lcp' || $metric_key === 'fcp' || $metric_key === 'ttfb' ) {
					$value = $value ? $value / 1000 : null; // Convert ms to seconds
				} elseif ( $metric_key === 'fid' ) {
					// FID value is already in milliseconds
				}

				$cwv_metrics[ $metric_key ] = [
					'value' => $value,
					'display_value' => $display_value,
					'score' => $score,
					'rating' => $this->get_cwv_rating( $metric_key, $value ),
					'description' => $this->get_cwv_description( $metric_key )
				];
			}
		}

		return $cwv_metrics;
	}

	/**
	 * Get Core Web Vitals rating
	 *
	 * @param string $metric Metric name.
	 * @param float|null $value Metric value.
	 * @return string Rating (good, needs_improvement, poor).
	 */
	private function get_cwv_rating( string $metric, $value ): string {
		if ( $value === null ) {
			return 'unknown';
		}

		$thresholds = self::CWV_THRESHOLDS[ $metric ] ?? null;
		if ( ! $thresholds ) {
			return 'unknown';
		}

		if ( $value <= $thresholds['good'] ) {
			return 'good';
		} elseif ( $value <= $thresholds['needs_improvement'] ) {
			return 'needs_improvement';
		} else {
			return 'poor';
		}
	}

	/**
	 * Get Core Web Vitals description
	 *
	 * @param string $metric Metric name.
	 * @return string Description.
	 */
	private function get_cwv_description( string $metric ): string {
		$descriptions = [
			'lcp' => __( 'Largest Contentful Paint measures loading performance', 'hellaz-sitez-analyzer' ),
			'fid' => __( 'First Input Delay measures interactivity', 'hellaz-sitez-analyzer' ),
			'cls' => __( 'Cumulative Layout Shift measures visual stability', 'hellaz-sitez-analyzer' ),
			'fcp' => __( 'First Contentful Paint measures loading performance', 'hellaz-sitez-analyzer' ),
			'ttfb' => __( 'Time to First Byte measures server responsiveness', 'hellaz-sitez-analyzer' )
		];

		return $descriptions[ $metric ] ?? '';
	}

	/**
	 * Extract Lighthouse metrics
	 *
	 * @param array $pagespeed_data PageSpeed Insights data.
	 * @return array Lighthouse metrics.
	 */
	private function extract_lighthouse_metrics( array $pagespeed_data ): array {
		$lighthouse_result = $pagespeed_data['lighthouseResult'] ?? [];
		$categories = $lighthouse_result['categories'] ?? [];
		$audits = $lighthouse_result['audits'] ?? [];

		$metrics = [
			'performance_score' => isset( $categories['performance']['score'] ) ? 
				round( $categories['performance']['score'] * 100 ) : 0,
			'accessibility_score' => isset( $categories['accessibility']['score'] ) ? 
				round( $categories['accessibility']['score'] * 100 ) : 0,
			'best_practices_score' => isset( $categories['best-practices']['score'] ) ? 
				round( $categories['best-practices']['score'] * 100 ) : 0,
			'seo_score' => isset( $categories['seo']['score'] ) ? 
				round( $categories['seo']['score'] * 100 ) : 0,
		];

		// Extract additional timing metrics
		$timing_metrics = [
			'speed-index' => 'Speed Index',
			'total-blocking-time' => 'Total Blocking Time',
			'interactive' => 'Time to Interactive'
		];

		foreach ( $timing_metrics as $audit_key => $metric_name ) {
			if ( isset( $audits[ $audit_key ] ) ) {
				$metrics[ str_replace( '-', '_', $audit_key ) ] = [
					'value' => $audits[ $audit_key ]['numericValue'] ?? null,
					'display_value' => $audits[ $audit_key ]['displayValue'] ?? '',
					'score' => $audits[ $audit_key ]['score'] ?? null,
					'name' => $metric_name
				];
			}
		}

		return $metrics;
	}

	/**
	 * Extract performance opportunities
	 *
	 * @param array $pagespeed_data PageSpeed Insights data.
	 * @return array Performance opportunities.
	 */
	private function extract_opportunities( array $pagespeed_data ): array {
		$lighthouse_result = $pagespeed_data['lighthouseResult'] ?? [];
		$audits = $lighthouse_result['audits'] ?? [];
		$opportunities = [];

		$opportunity_audits = [
			'render-blocking-resources',
			'unused-css-rules',
			'unused-javascript',
			'modern-image-formats',
			'efficiently-encode-images',
			'offscreen-images',
			'unminified-css',
			'unminified-javascript',
			'uses-text-compression',
			'uses-responsive-images'
		];

		foreach ( $opportunity_audits as $audit_key ) {
			if ( isset( $audits[ $audit_key ] ) && isset( $audits[ $audit_key ]['details']['overallSavingsMs'] ) ) {
				$audit = $audits[ $audit_key ];
				$opportunities[] = [
					'id' => $audit_key,
					'title' => $audit['title'] ?? '',
					'description' => $audit['description'] ?? '',
					'savings_ms' => $audit['details']['overallSavingsMs'] ?? 0,
					'savings_bytes' => $audit['details']['overallSavingsBytes'] ?? 0,
					'score' => $audit['score'] ?? null,
					'display_value' => $audit['displayValue'] ?? ''
				];
			}
		}

		// Sort by potential savings
		usort( $opportunities, function( $a, $b ) {
			return $b['savings_ms'] - $a['savings_ms'];
		});

		return $opportunities;
	}

	/**
	 * Extract performance diagnostics
	 *
	 * @param array $pagespeed_data PageSpeed Insights data.
	 * @return array Performance diagnostics.
	 */
	private function extract_diagnostics( array $pagespeed_data ): array {
		$lighthouse_result = $pagespeed_data['lighthouseResult'] ?? [];
		$audits = $lighthouse_result['audits'] ?? [];
		$diagnostics = [];

		$diagnostic_audits = [
			'mainthread-work-breakdown',
			'bootup-time',
			'uses-rel-preload',
			'uses-rel-preconnect',
			'font-display',
			'third-party-summary',
			'dom-size',
			'critical-request-chains'
		];

		foreach ( $diagnostic_audits as $audit_key ) {
			if ( isset( $audits[ $audit_key ] ) && ( $audits[ $audit_key ]['score'] ?? 1 < 1 ) ) {
				$audit = $audits[ $audit_key ];
				$diagnostics[] = [
					'id' => $audit_key,
					'title' => $audit['title'] ?? '',
					'description' => $audit['description'] ?? '',
					'score' => $audit['score'] ?? null,
					'display_value' => $audit['displayValue'] ?? ''
				];
			}
		}

		return $diagnostics;
	}

	/**
	 * Analyze server timing
	 *
	 * @param string $url Website URL.
	 * @return array Server timing metrics.
	 */
	private function analyze_server_timing( string $url ): array {
		$start_time = microtime( true );
		$response = wp_remote_head( $url, [
			'timeout' => 30,
			'user-agent' => 'HellaZ SiteZ Analyzer/' . HSZ_VERSION
		]);
		$total_time = microtime( true ) - $start_time;

		if ( is_wp_error( $response ) ) {
			return ['error' => $response->get_error_message()];
		}

		$headers = wp_remote_retrieve_headers( $response );
		$response_code = wp_remote_retrieve_response_code( $response );

		$server_metrics = [
			'response_time' => round( $total_time * 1000, 2 ), // Convert to milliseconds
			'http_status' => $response_code,
			'server' => $headers['server'] ?? 'Unknown',
			'content_type' => $headers['content-type'] ?? '',
			'content_encoding' => $headers['content-encoding'] ?? '',
			'cache_control' => $headers['cache-control'] ?? '',
			'expires' => $headers['expires'] ?? '',
			'last_modified' => $headers['last-modified'] ?? '',
			'etag' => $headers['etag'] ?? ''
		];

		// Parse Server-Timing header if present
		$server_timing = $headers['server-timing'] ?? '';
		if ( $server_timing ) {
			$server_metrics['server_timing'] = $this->parse_server_timing( $server_timing );
		}

		// Rate response time
		if ( $server_metrics['response_time'] <= 200 ) {
			$server_metrics['response_time_rating'] = 'excellent';
		} elseif ( $server_metrics['response_time'] <= 500 ) {
			$server_metrics['response_time_rating'] = 'good';
		} elseif ( $server_metrics['response_time'] <= 1000 ) {
			$server_metrics['response_time_rating'] = 'fair';
		} else {
			$server_metrics['response_time_rating'] = 'poor';
		}

		return $server_metrics;
	}

	/**
	 * Parse Server-Timing header
	 *
	 * @param string $server_timing Server-Timing header value.
	 * @return array Parsed timing data.
	 */
	private function parse_server_timing( string $server_timing ): array {
		$timings = [];
		$entries = explode( ',', $server_timing );

		foreach ( $entries as $entry ) {
			$entry = trim( $entry );
			$parts = explode( ';', $entry );
			$name = trim( $parts[0] );
			
			$timing = ['name' => $name];
			
			for ( $i = 1; $i < count( $parts ); $i++ ) {
				$part = trim( $parts[ $i ] );
				if ( strpos( $part, 'dur=' ) === 0 ) {
					$timing['duration'] = floatval( substr( $part, 4 ) );
				} elseif ( strpos( $part, 'desc=' ) === 0 ) {
					$timing['description'] = trim( substr( $part, 5 ), '"' );
				}
			}
			
			$timings[] = $timing;
		}

		return $timings;
	}

	/**
	 * Analyze resource optimization
	 *
	 * @param string $url Website URL.
	 * @return array Resource analysis.
	 */
	private function analyze_resources( string $url ): array {
		$html = Utils::get_html( $url );
		if ( is_wp_error( $html ) ) {
			return ['error' => $html->get_error_message()];
		}

		$resource_analysis = [
			'total_requests' => 0,
			'total_size' => 0,
			'resources_by_type' => [],
			'compression_opportunities' => [],
			'caching_issues' => [],
			'cdn_usage' => false,
			'http2_usage' => false
		];

		// Parse HTML to find resources
		$dom = new \DOMDocument();
		@$dom->loadHTML( $html );
		$xpath = new \DOMXPath( $dom );

		// Analyze CSS files
		$css_nodes = $xpath->query( '//link[@rel="stylesheet"]' );
		$css_count = 0;
		foreach ( $css_nodes as $node ) {
			$href = $node->getAttribute( 'href' );
			if ( $href ) {
				$css_count++;
				// Could analyze each CSS file for size, minification, etc.
			}
		}

		// Analyze JavaScript files
		$js_nodes = $xpath->query( '//script[@src]' );
		$js_count = 0;
		foreach ( $js_nodes as $node ) {
			$src = $node->getAttribute( 'src' );
			if ( $src ) {
				$js_count++;
				// Could analyze each JS file
			}
		}

		// Analyze images
		$img_nodes = $xpath->query( '//img[@src]' );
		$img_count = $img_nodes->length;

		$resource_analysis['resources_by_type'] = [
			'css' => $css_count,
			'javascript' => $js_count,
			'images' => $img_count
		];

		$resource_analysis['total_requests'] = $css_count + $js_count + $img_count;

		return $resource_analysis;
	}

	/**
	 * Calculate performance metrics
	 *
	 * @param array $performance_data Performance data.
	 * @return array Updated performance data.
	 */
	private function calculate_performance_metrics( array $performance_data ): array {
		$scores = [];
		$total_weight = 0;

		// Core Web Vitals Score
		if ( ! empty( $performance_data['core_web_vitals'] ) ) {
			$cwv_score = $this->calculate_cwv_score( $performance_data['core_web_vitals'] );
			$scores['core_web_vitals'] = $cwv_score;
			$total_weight += self::PERFORMANCE_WEIGHTS['core_web_vitals'];
		}

		// Lighthouse Score
		if ( ! empty( $performance_data['lighthouse_metrics']['performance_score'] ) ) {
			$lighthouse_score = $performance_data['lighthouse_metrics']['performance_score'];
			$scores['lighthouse_metrics'] = $lighthouse_score;
			$total_weight += self::PERFORMANCE_WEIGHTS['lighthouse_metrics'];
		}

		// Server Response Score
		if ( ! empty( $performance_data['server_metrics']['response_time'] ) ) {
			$server_score = $this->calculate_server_score( $performance_data['server_metrics'] );
			$scores['server_response'] = $server_score;
			$total_weight += self::PERFORMANCE_WEIGHTS['server_response'];
		}

		// Resource Optimization Score
		if ( ! empty( $performance_data['resource_analysis'] ) ) {
			$resource_score = $this->calculate_resource_score( $performance_data['resource_analysis'] );
			$scores['resource_optimization'] = $resource_score;
			$total_weight += self::PERFORMANCE_WEIGHTS['resource_optimization'];
		}

		// Calculate weighted overall score
		$weighted_sum = 0;
		foreach ( $scores as $category => $score ) {
			$weight = self::PERFORMANCE_WEIGHTS[ $category ] ?? 0;
			$weighted_sum += $score * $weight;
		}

		$performance_data['performance_score'] = $total_weight > 0 ? round( $weighted_sum / $total_weight ) : 0;
		$performance_data['performance_grade'] = Utils::score_to_grade( $performance_data['performance_score'] );

		// Determine overall status
		$score = $performance_data['performance_score'];
		if ( $score >= 90 ) {
			$performance_data['overall_status'] = 'excellent';
		} elseif ( $score >= 80 ) {
			$performance_data['overall_status'] = 'good';
		} elseif ( $score >= 70 ) {
			$performance_data['overall_status'] = 'fair';
		} elseif ( $score >= 60 ) {
			$performance_data['overall_status'] = 'poor';
		} else {
			$performance_data['overall_status'] = 'critical';
		}

		return $performance_data;
	}

	/**
	 * Calculate Core Web Vitals score
	 *
	 * @param array $cwv_data Core Web Vitals data.
	 * @return int CWV score (0-100).
	 */
	private function calculate_cwv_score( array $cwv_data ): int {
		$scores = [];
		$weights = ['lcp' => 0.3, 'fid' => 0.3, 'cls' => 0.3, 'fcp' => 0.1];

		foreach ( $cwv_data as $metric => $data ) {
			$rating = $data['rating'] ?? 'unknown';
			$weight = $weights[ $metric ] ?? 0;

			if ( $weight > 0 ) {
				switch ( $rating ) {
					case 'good':
						$scores[ $metric ] = 100;
						break;
					case 'needs_improvement':
						$scores[ $metric ] = 60;
						break;
					case 'poor':
						$scores[ $metric ] = 20;
						break;
					default:
						$scores[ $metric ] = 0;
				}
			}
		}

		$weighted_sum = 0;
		$total_weight = 0;
		foreach ( $scores as $metric => $score ) {
			$weight = $weights[ $metric ] ?? 0;
			$weighted_sum += $score * $weight;
			$total_weight += $weight;
		}

		return $total_weight > 0 ? round( $weighted_sum / $total_weight ) : 0;
	}

	/**
	 * Calculate server response score
	 *
	 * @param array $server_data Server metrics data.
	 * @return int Server score (0-100).
	 */
	private function calculate_server_score( array $server_data ): int {
		$response_time = $server_data['response_time'] ?? 1000;
		
		if ( $response_time <= 200 ) {
			return 100;
		} elseif ( $response_time <= 500 ) {
			return 80;
		} elseif ( $response_time <= 1000 ) {
			return 60;
		} elseif ( $response_time <= 2000 ) {
			return 40;
		} else {
			return 20;
		}
	}

	/**
	 * Calculate resource optimization score
	 *
	 * @param array $resource_data Resource analysis data.
	 * @return int Resource score (0-100).
	 */
	private function calculate_resource_score( array $resource_data ): int {
		$score = 100;
		
		$total_requests = $resource_data['total_requests'] ?? 0;
		
		// Penalty for too many requests
		if ( $total_requests > 100 ) {
			$score -= 30;
		} elseif ( $total_requests > 50 ) {
			$score -= 15;
		}

		// Could add more resource optimization checks here
		
		return max( 0, $score );
	}

	/**
	 * Generate performance recommendations
	 *
	 * @param array $performance_data Performance analysis data.
	 * @return array Performance recommendations.
	 */
	private function generate_performance_recommendations( array $performance_data ): array {
		$recommendations = [];

		// Core Web Vitals recommendations
		if ( ! empty( $performance_data['core_web_vitals'] ) ) {
			foreach ( $performance_data['core_web_vitals'] as $metric => $data ) {
				if ( $data['rating'] === 'poor' || $data['rating'] === 'needs_improvement' ) {
					$recommendations[] = [
						'category' => 'core_web_vitals',
						'priority' => $data['rating'] === 'poor' ? 'high' : 'medium',
						'title' => sprintf( __( 'Improve %s', 'hellaz-sitez-analyzer' ), strtoupper( $metric ) ),
						'description' => $this->get_cwv_recommendation( $metric ),
						'metric' => $metric,
						'current_value' => $data['display_value'] ?? '',
						'target' => $this->get_cwv_target( $metric )
					];
				}
			}
		}

		// Opportunities from PageSpeed
		if ( ! empty( $performance_data['opportunities'] ) ) {
			foreach ( array_slice( $performance_data['opportunities'], 0, 5 ) as $opportunity ) {
				$recommendations[] = [
					'category' => 'optimization',
					'priority' => $opportunity['savings_ms'] > 1000 ? 'high' : 'medium',
					'title' => $opportunity['title'],
					'description' => $opportunity['description'],
					'potential_savings' => $opportunity['display_value'],
					'audit_id' => $opportunity['id']
				];
			}
		}

		// Server response recommendations
		if ( ! empty( $performance_data['server_metrics'] ) ) {
			$response_time = $performance_data['server_metrics']['response_time'] ?? 0;
			if ( $response_time > 1000 ) {
				$recommendations[] = [
					'category' => 'server',
					'priority' => 'high',
					'title' => __( 'Improve Server Response Time', 'hellaz-sitez-analyzer' ),
					'description' => __( 'Server response time is slow. Consider upgrading hosting, optimizing database queries, or implementing caching.', 'hellaz-sitez-analyzer' ),
					'current_time' => round( $response_time ) . 'ms',
					'target' => '< 200ms'
				];
			}
		}

		return $recommendations;
	}

	/**
	 * Get CWV recommendation text
	 *
	 * @param string $metric Metric name.
	 * @return string Recommendation text.
	 */
	private function get_cwv_recommendation( string $metric ): string {
		$recommendations = [
			'lcp' => __( 'Optimize images, implement lazy loading, improve server response times, and remove render-blocking resources.', 'hellaz-sitez-analyzer' ),
			'fid' => __( 'Minimize JavaScript execution time, remove unused code, break up long tasks, and optimize third-party scripts.', 'hellaz-sitez-analyzer' ),
			'cls' => __( 'Set size attributes on images and videos, avoid inserting content above existing content, and use CSS transforms.', 'hellaz-sitez-analyzer' ),
			'fcp' => __( 'Eliminate render-blocking resources, minify CSS, remove unused CSS, and optimize web fonts.', 'hellaz-sitez-analyzer' ),
			'ttfb' => __( 'Optimize server configuration, use a CDN, enable caching, and optimize database queries.', 'hellaz-sitez-analyzer' )
		];

		return $recommendations[ $metric ] ?? '';
	}

	/**
	 * Get CWV target value
	 *
	 * @param string $metric Metric name.
	 * @return string Target value.
	 */
	private function get_cwv_target( string $metric ): string {
		$targets = [
			'lcp' => '< 2.5s',
			'fid' => '< 100ms',
			'cls' => '< 0.1',
			'fcp' => '< 1.8s',
			'ttfb' => '< 800ms'
		];

		return $targets[ $metric ] ?? '';
	}

	/**
	 * Run WebPageTest analysis (placeholder for future implementation)
	 *
	 * @param string $url Website URL.
	 * @param array $options Analysis options.
	 * @return array WebPageTest results.
	 */
	private function run_webpagetest_analysis( string $url, array $options = [] ): array {
		// This would require WebPageTest API implementation
		return [
			'status' => 'not_implemented',
			'message' => 'WebPageTest integration coming soon'
		];
	}

	/**
	 * Store performance results in enhanced database
	 *
	 * @param string $url Website URL.
	 * @param array $performance_data Performance analysis results.
	 */
	private function store_performance_results( string $url, array $performance_data ): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'hsz_performance_results';

		// Check if table exists
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			return;
		}

		$url_hash = Utils::generate_url_hash( $url );
		$expires_at = date( 'Y-m-d H:i:s', time() + get_option( 'hsz_performance_cache_duration', HOUR_IN_SECONDS * 6 ) );

		$wpdb->replace(
			$table_name,
			[
				'url_hash' => $url_hash,
				'url' => $url,
				'overall_score' => $performance_data['performance_score'],
				'performance_score' => $performance_data['lighthouse_metrics']['performance_score'] ?? 0,
				'accessibility_score' => $performance_data['lighthouse_metrics']['accessibility_score'] ?? 0,
				'best_practices_score' => $performance_data['lighthouse_metrics']['best_practices_score'] ?? 0,
				'seo_score' => $performance_data['lighthouse_metrics']['seo_score'] ?? 0,
				'core_web_vitals' => wp_json_encode( $performance_data['core_web_vitals'] ),
				'lighthouse_data' => wp_json_encode( $performance_data['lighthouse_metrics'] ),
				'pagespeed_data' => wp_json_encode( $performance_data['pagespeed_data'] ),
				'recommendations' => wp_json_encode( $performance_data['recommendations'] ),
				'analysis_time' => $performance_data['analysis_time'] ?? 0,
				'created_at' => current_time( 'mysql', true ),
				'expires_at' => $expires_at
			],
			[
				'%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%f', '%s', '%s'
			]
		);
	}

	/**
	 * Get performance summary
	 *
	 * @param array $performance_data Performance analysis data.
	 * @return array Performance summary.
	 */
	public static function get_performance_summary( array $performance_data ): array {
		return [
			'overall_score' => $performance_data['performance_score'] ?? 0,
			'overall_grade' => $performance_data['performance_grade'] ?? 'F',
			'core_web_vitals_passed' => count( array_filter( $performance_data['core_web_vitals'] ?? [], function( $cwv ) {
				return $cwv['rating'] === 'good';
			})),
			'opportunities_count' => count( $performance_data['opportunities'] ?? [] ),
			'server_response_time' => $performance_data['server_metrics']['response_time'] ?? 0,
			'status' => $performance_data['overall_status'] ?? 'unknown'
		];
	}
}
