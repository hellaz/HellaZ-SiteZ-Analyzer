<?php
/**
 * Enhanced RSS/Atom feed detection and analysis for HellaZ SiteZ Analyzer.
 *
 * This class provides comprehensive feed detection, validation, content analysis,
 * and feed quality assessment with Phase 1 enhancements.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.0.2
 */

namespace HSZ;

use DOMDocument;
use DOMXPath;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RSS
 *
 * Handles RSS/Atom feed detection and analysis with enhanced capabilities.
 */
class RSS {

	/**
	 * Feed types and their MIME types
	 */
	private const FEED_TYPES = [
		'application/rss+xml' => 'RSS',
		'application/atom+xml' => 'Atom',
		'application/feed+json' => 'JSON Feed',
		'text/xml' => 'XML Feed',
		'application/xml' => 'XML Feed'
	];

	/**
	 * Common feed discovery patterns
	 */
	private const FEED_PATTERNS = [
		'~href=["\']([^"\']*(?:rss|atom|feed)[^"\']*)["\']~i',
		'~href=["\']([^"\']*\.(?:rss|atom|xml))["\']~i',
		'~href=["\']([^"\']*feed[^"\']*)["\']~i'
	];

	/**
	 * Comprehensive feed analysis
	 *
	 * @param string $html Website HTML content.
	 * @param string $base_url Website base URL.
	 * @param array $options Analysis options.
	 * @return array Comprehensive feed analysis results.
	 */
	public function analyze_feeds( string $html, string $base_url = '', array $options = [] ): array {
		// Set default options
		$options = wp_parse_args( $options, [
			'validate_feeds' => true,
			'analyze_content' => true,
			'check_quality' => true,
			'extract_metadata' => true,
			'force_refresh' => false
		]);

		// Check cache first
		$cache_key = 'feed_analysis_' . md5( $base_url . serialize( $options ) );
		$cached_data = Cache::get( $cache_key, 'social' );

		if ( $cached_data && ! $options['force_refresh'] ) {
			return $cached_data;
		}

		Utils::start_timer( 'feed_analysis' );

		$analysis_data = [
			'url' => $base_url,
			'timestamp' => current_time( 'mysql', true ),
			'feeds' => [],
			'feed_count' => 0,
			'feed_types' => [],
			'valid_feeds' => 0,
			'invalid_feeds' => 0,
			'feed_quality' => [],
			'content_analysis' => [],
			'recommendations' => [],
			'feed_score' => 0,
			'feed_grade' => 'F',
			'analysis_time' => 0
		];

		// Extract feeds from HTML
		$analysis_data['feeds'] = $this->extract_rss_feeds( $html, $base_url );
		$analysis_data['feed_count'] = count( $analysis_data['feeds'] );
		$analysis_data['feed_types'] = $this->get_feed_types( $analysis_data['feeds'] );

		// Validate feeds if enabled
		if ( $options['validate_feeds'] && ! empty( $analysis_data['feeds'] ) ) {
			$validation_results = $this->validate_feeds( $analysis_data['feeds'] );
			$analysis_data['valid_feeds'] = $validation_results['valid_count'];
			$analysis_data['invalid_feeds'] = $validation_results['invalid_count'];
			
			// Update feeds with validation results
			foreach ( $analysis_data['feeds'] as $index => $feed ) {
				if ( isset( $validation_results['feed_details'][ $feed['url'] ] ) ) {
					$analysis_data['feeds'][ $index ] = array_merge( 
						$feed, 
						$validation_results['feed_details'][ $feed['url'] ] 
					);
				}
			}
		}

		// Analyze feed content if enabled
		if ( $options['analyze_content'] && $analysis_data['valid_feeds'] > 0 ) {
			$analysis_data['content_analysis'] = $this->analyze_feed_content( $analysis_data['feeds'] );
		}

		// Check feed quality if enabled
		if ( $options['check_quality'] && ! empty( $analysis_data['feeds'] ) ) {
			$analysis_data['feed_quality'] = $this->assess_feed_quality( $analysis_data['feeds'] );
		}

		// Calculate scores and grades
		$analysis_data = $this->calculate_feed_scores( $analysis_data );

		// Generate recommendations
		$analysis_data['recommendations'] = $this->generate_feed_recommendations( $analysis_data );

		$analysis_data['analysis_time'] = Utils::stop_timer( 'feed_analysis' );

		// Cache the results
		Cache::set( $cache_key, $analysis_data, DAY_IN_SECONDS, 'social' );

		return $analysis_data;
	}

	/**
	 * Enhanced RSS feed extraction with improved detection
	 *
	 * @param string $html The HTML content to search.
	 * @param string $base_url The base URL for resolving relative URLs.
	 * @return array An array of feed information.
	 */
	public function extract_rss_feeds( string $html, string $base_url = '' ): array {
		$cache_key = 'rss_feeds_' . md5( $base_url );
		$cached_feeds = Cache::get( $cache_key, 'social' );

		if ( is_array( $cached_feeds ) ) {
			return $cached_feeds;
		}

		$feeds = [];
		$found_urls = [];

		// Initialize DOM
		$dom = new DOMDocument();
		if ( ! @$dom->loadHTML( '<?xml encoding="UTF-8">' . $html ) ) {
			Utils::log_error( 'Failed to parse HTML for RSS feed extraction.' );
			return [];
		}

		$xpath = new DOMXPath( $dom );

		// 1. Extract from <link> tags
		$feed_links = $xpath->query( "//link[@type and @href]" );
		foreach ( $feed_links as $link ) {
			$type = $link->getAttribute( 'type' );
			$href = $link->getAttribute( 'href' );
			$title = $link->getAttribute( 'title' );
			$rel = $link->getAttribute( 'rel' );

			if ( isset( self::FEED_TYPES[ $type ] ) && ! empty( $href ) ) {
				$feed_url = Utils::resolve_url( $href, $base_url );
				
				if ( filter_var( $feed_url, FILTER_VALIDATE_URL ) && ! in_array( $feed_url, $found_urls, true ) ) {
					$feeds[] = [
						'url' => $feed_url,
						'type' => self::FEED_TYPES[ $type ],
						'mime_type' => $type,
						'title' => $title ?: self::FEED_TYPES[ $type ] . ' Feed',
						'rel' => $rel,
						'detection_method' => 'link_tag',
						'autodiscovery' => ( $rel === 'alternate' )
					];
					$found_urls[] = $feed_url;
				}
			}
		}

		// 2. Search for feeds in href attributes using patterns
		foreach ( self::FEED_PATTERNS as $pattern ) {
			if ( preg_match_all( $pattern, $html, $matches ) ) {
				foreach ( $matches[1] as $potential_feed ) {
					$feed_url = Utils::resolve_url( $potential_feed, $base_url );
					
					if ( filter_var( $feed_url, FILTER_VALIDATE_URL ) && ! in_array( $feed_url, $found_urls, true ) ) {
						// Determine feed type from URL
						$feed_type = $this->guess_feed_type_from_url( $feed_url );
						
						$feeds[] = [
							'url' => $feed_url,
							'type' => $feed_type,
							'mime_type' => $this->get_mime_type_for_feed_type( $feed_type ),
							'title' => $feed_type . ' Feed',
							'rel' => '',
							'detection_method' => 'url_pattern',
							'autodiscovery' => false
						];
						$found_urls[] = $feed_url;
					}
				}
			}
		}

		// 3. Check common feed locations
		$common_feed_paths = [
			'/feed/',
			'/feed.xml',
			'/rss.xml',
			'/atom.xml',
			'/feeds/all.atom.xml',
			'/index.xml',
			'/blog/feed/',
			'/news/feed/',
			'/.rss'
		];

		$parsed_url = parse_url( $base_url );
		$base_domain = $parsed_url['scheme'] . '://' . $parsed_url['host'];

		foreach ( $common_feed_paths as $path ) {
			$feed_url = $base_domain . $path;
			
			if ( ! in_array( $feed_url, $found_urls, true ) ) {
				// Quick check if feed exists
				$status = Utils::get_http_status( $feed_url );
				if ( ! is_wp_error( $status ) && $status >= 200 && $status < 400 ) {
					$feed_type = $this->guess_feed_type_from_url( $feed_url );
					
					$feeds[] = [
						'url' => $feed_url,
						'type' => $feed_type,
						'mime_type' => $this->get_mime_type_for_feed_type( $feed_type ),
						'title' => $feed_type . ' Feed',
						'rel' => '',
						'detection_method' => 'common_path',
						'autodiscovery' => false,
						'http_status' => $status
					];
					$found_urls[] = $feed_url;
				}
			}
		}

		// 4. Look for WordPress-specific feeds
		if ( $this->is_wordpress_site( $html ) ) {
			$wp_feeds = $this->extract_wordpress_feeds( $base_url, $found_urls );
			$feeds = array_merge( $feeds, $wp_feeds );
		}

		// Remove duplicates and sort by priority
		$feeds = $this->prioritize_feeds( $feeds );

		Cache::set( $cache_key, $feeds, DAY_IN_SECONDS, 'social' );
		return $feeds;
	}

	/**
	 * Guess feed type from URL
	 *
	 * @param string $url Feed URL.
	 * @return string Feed type.
	 */
	private function guess_feed_type_from_url( string $url ): string {
		$url_lower = strtolower( $url );
		
		if ( strpos( $url_lower, 'atom' ) !== false ) {
			return 'Atom';
		} elseif ( strpos( $url_lower, 'rss' ) !== false ) {
			return 'RSS';
		} elseif ( strpos( $url_lower, '.json' ) !== false ) {
			return 'JSON Feed';
		} elseif ( strpos( $url_lower, '.xml' ) !== false ) {
			return 'XML Feed';
		}
		
		return 'RSS'; // Default assumption
	}

	/**
	 * Get MIME type for feed type
	 *
	 * @param string $feed_type Feed type.
	 * @return string MIME type.
	 */
	private function get_mime_type_for_feed_type( string $feed_type ): string {
		$mime_map = [
			'RSS' => 'application/rss+xml',
			'Atom' => 'application/atom+xml',
			'JSON Feed' => 'application/feed+json',
			'XML Feed' => 'application/xml'
		];

		return $mime_map[ $feed_type ] ?? 'application/rss+xml';
	}

	/**
	 * Check if website is WordPress
	 *
	 * @param string $html HTML content.
	 * @return bool True if WordPress.
	 */
	private function is_wordpress_site( string $html ): bool {
		return strpos( $html, 'wp-content' ) !== false || 
			   strpos( $html, 'wordpress' ) !== false ||
			   preg_match( '/wp-json|wp_enqueue|wp-includes/i', $html );
	}

	/**
	 * Extract WordPress-specific feeds
	 *
	 * @param string $base_url Base URL.
	 * @param array $found_urls Already found URLs.
	 * @return array WordPress feeds.
	 */
	private function extract_wordpress_feeds( string $base_url, array $found_urls ): array {
		$wp_feeds = [];
		$parsed_url = parse_url( $base_url );
		$base_domain = $parsed_url['scheme'] . '://' . $parsed_url['host'];

		$wp_feed_paths = [
			'/feed/' => 'Main RSS Feed',
			'/comments/feed/' => 'Comments RSS Feed',  
			'/category/*/feed/' => 'Category Feed',
			'/tag/*/feed/' => 'Tag Feed',
			'/author/*/feed/' => 'Author Feed',
			'/?feed=rss2' => 'RSS2 Feed',
			'/?feed=atom' => 'Atom Feed',
			'/?feed=rdf' => 'RDF Feed'
		];

		foreach ( $wp_feed_paths as $path => $title ) {
			if ( strpos( $path, '*' ) === false ) {
				$feed_url = $base_domain . $path;
				
				if ( ! in_array( $feed_url, $found_urls, true ) ) {
					$wp_feeds[] = [
						'url' => $feed_url,
						'type' => 'RSS',
						'mime_type' => 'application/rss+xml',
						'title' => $title,
						'rel' => '',
						'detection_method' => 'wordpress_specific',
						'autodiscovery' => false,
						'platform' => 'WordPress'
					];
				}
			}
		}

		return $wp_feeds;
	}

	/**
	 * Prioritize feeds by importance
	 *
	 * @param array $feeds Feed list.
	 * @return array Prioritized feeds.
	 */
	private function prioritize_feeds( array $feeds ): array {
		$priority_map = [
			'link_tag' => 3,
			'wordpress_specific' => 2,
			'common_path' => 1,
			'url_pattern' => 0
		];

		usort( $feeds, function( $a, $b ) use ( $priority_map ) {
			$priority_a = $priority_map[ $a['detection_method'] ] ?? 0;
			$priority_b = $priority_map[ $b['detection_method'] ] ?? 0;
			
			if ( $priority_a === $priority_b ) {
				// Secondary sort by autodiscovery
				return $b['autodiscovery'] <=> $a['autodiscovery'];
			}
			
			return $priority_b <=> $priority_a;
		});

		return $feeds;
	}

	/**
	 * Get feed types summary
	 *
	 * @param array $feeds Feed list.
	 * @return array Feed types breakdown.
	 */
	private function get_feed_types( array $feeds ): array {
		$types = [];
		
		foreach ( $feeds as $feed ) {
			$type = $feed['type'];
			if ( ! isset( $types[ $type ] ) ) {
				$types[ $type ] = 0;
			}
			$types[ $type ]++;
		}

		return $types;
	}

	/**
	 * Validate feeds
	 *
	 * @param array $feeds Feed list.
	 * @return array Validation results.
	 */
	private function validate_feeds( array $feeds ): array {
		$validation_results = [
			'valid_count' => 0,
			'invalid_count' => 0,
			'unreachable_count' => 0,
			'feed_details' => []
		];

		foreach ( $feeds as $feed ) {
			$validation = $this->validate_single_feed( $feed );
			$validation_results['feed_details'][ $feed['url'] ] = $validation;
			
			switch ( $validation['status'] ) {
				case 'valid':
					$validation_results['valid_count']++;
					break;
				case 'invalid':
					$validation_results['invalid_count']++;
					break;
				case 'unreachable':
					$validation_results['unreachable_count']++;
					break;
			}
		}

		return $validation_results;
	}

	/**
	 * Validate a single feed
	 *
	 * @param array $feed Feed data.
	 * @return array Validation result.
	 */
	private function validate_single_feed( array $feed ): array {
		$validation = [
			'status' => 'unknown',
			'http_status' => 0,
			'content_type' => '',
			'is_valid_xml' => false,
			'has_items' => false,
			'item_count' => 0,
			'last_updated' => '',
			'errors' => []
		];

		Utils::start_timer( 'feed_validation_' . md5( $feed['url'] ) );

		$response = wp_remote_get( $feed['url'], [
			'timeout' => 15,
			'user-agent' => 'HellaZ SiteZ Analyzer/' . HSZ_VERSION,
			'redirection' => 3
		]);

		$validation['response_time'] = Utils::stop_timer( 'feed_validation_' . md5( $feed['url'] ) );

		if ( is_wp_error( $response ) ) {
			$validation['status'] = 'unreachable';
			$validation['errors'][] = $response->get_error_message();
			return $validation;
		}

		$http_status = wp_remote_retrieve_response_code( $response );
		$validation['http_status'] = $http_status;

		if ( $http_status < 200 || $http_status >= 400 ) {
			$validation['status'] = 'unreachable';
			$validation['errors'][] = sprintf( __( 'HTTP %d error', 'hellaz-sitez-analyzer' ), $http_status );
			return $validation;
		}

		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		$validation['content_type'] = $content_type;

		$body = wp_remote_retrieve_body( $response );
		
		if ( empty( $body ) ) {
			$validation['status'] = 'invalid';
			$validation['errors'][] = __( 'Empty feed content', 'hellaz-sitez-analyzer' );
			return $validation;
		}

		// Validate XML structure
		$dom = new DOMDocument();
		$old_setting = libxml_use_internal_errors( true );
		libxml_clear_errors();
		
		$is_valid_xml = @$dom->loadXML( $body );
		$xml_errors = libxml_get_errors();
		
		libxml_use_internal_errors( $old_setting );

		$validation['is_valid_xml'] = $is_valid_xml && empty( $xml_errors );

		if ( ! $validation['is_valid_xml'] ) {
			$validation['status'] = 'invalid';
			$validation['errors'][] = __( 'Invalid XML structure', 'hellaz-sitez-analyzer' );
			
			foreach ( $xml_errors as $error ) {
				$validation['errors'][] = trim( $error->message );
			}
			
			return $validation;
		}

		// Check for feed-specific elements
		$xpath = new DOMXPath( $dom );
		$feed_analysis = $this->analyze_feed_structure( $dom, $xpath );
		
		$validation = array_merge( $validation, $feed_analysis );

		if ( $validation['item_count'] > 0 ) {
			$validation['status'] = 'valid';
			$validation['has_items'] = true;
		} else {
			$validation['status'] = 'invalid';
			$validation['errors'][] = __( 'No feed items found', 'hellaz-sitez-analyzer' );
		}

		return $validation;
	}

	/**
	 * Analyze feed structure
	 *
	 * @param DOMDocument $dom DOM document.
	 * @param DOMXPath $xpath XPath object.
	 * @return array Feed structure analysis.
	 */
	private function analyze_feed_structure( DOMDocument $dom, DOMXPath $xpath ): array {
		$analysis = [
			'feed_format' => 'unknown',
			'version' => '',
			'title' => '',
			'description' => '',
			'link' => '',
			'language' => '',
			'item_count' => 0,
			'last_build_date' => '',
			'generator' => ''
		];

		// Detect feed format
		$rss_channel = $xpath->query( '//rss/channel | //channel' );
		$atom_feed = $xpath->query( '//feed[@xmlns="http://www.w3.org/2005/Atom"] | //atom:feed' );
		$rdf_channel = $xpath->query( '//rdf:RDF/rss:channel | //rdf:RDF/channel' );

		if ( $rss_channel->length > 0 ) {
			$analysis['feed_format'] = 'RSS';
			$channel = $rss_channel->item( 0 );
			
			// Get RSS version
			$rss_element = $xpath->query( '//rss' )->item( 0 );
			if ( $rss_element ) {
				$analysis['version'] = $rss_element->getAttribute( 'version' ) ?: '2.0';
			}
			
			$analysis = $this->analyze_rss_channel( $channel, $xpath, $analysis );
			
		} elseif ( $atom_feed->length > 0 ) {
			$analysis['feed_format'] = 'Atom';
			$analysis['version'] = '1.0';
			$feed = $atom_feed->item( 0 );
			
			$analysis = $this->analyze_atom_feed( $feed, $xpath, $analysis );
			
		} elseif ( $rdf_channel->length > 0 ) {
			$analysis['feed_format'] = 'RDF';
			$analysis['version'] = '1.0';
			$channel = $rdf_channel->item( 0 );
			
			$analysis = $this->analyze_rdf_channel( $channel, $xpath, $analysis );
		}

		return $analysis;
	}

	/**
	 * Analyze RSS channel
	 *
	 * @param \DOMElement $channel Channel element.
	 * @param DOMXPath $xpath XPath object.
	 * @param array $analysis Current analysis.
	 * @return array Updated analysis.
	 */
	private function analyze_rss_channel( \DOMElement $channel, DOMXPath $xpath, array $analysis ): array {
		// Extract channel metadata
		$title_node = $xpath->query( 'title', $channel )->item( 0 );
		$analysis['title'] = $title_node ? trim( $title_node->nodeValue ) : '';

		$description_node = $xpath->query( 'description', $channel )->item( 0 );
		$analysis['description'] = $description_node ? trim( $description_node->nodeValue ) : '';

		$link_node = $xpath->query( 'link', $channel )->item( 0 );
		$analysis['link'] = $link_node ? trim( $link_node->nodeValue ) : '';

		$language_node = $xpath->query( 'language', $channel )->item( 0 );
		$analysis['language'] = $language_node ? trim( $language_node->nodeValue ) : '';

		$generator_node = $xpath->query( 'generator', $channel )->item( 0 );
		$analysis['generator'] = $generator_node ? trim( $generator_node->nodeValue ) : '';

		$last_build_date_node = $xpath->query( 'lastBuildDate', $channel )->item( 0 );
		$analysis['last_build_date'] = $last_build_date_node ? trim( $last_build_date_node->nodeValue ) : '';

		// Count items
		$items = $xpath->query( 'item', $channel );
		$analysis['item_count'] = $items->length;

		return $analysis;
	}

	/**
	 * Analyze Atom feed
	 *
	 * @param \DOMElement $feed Feed element.
	 * @param DOMXPath $xpath XPath object.
	 * @param array $analysis Current analysis.
	 * @return array Updated analysis.
	 */
	private function analyze_atom_feed( \DOMElement $feed, DOMXPath $xpath, array $analysis ): array {
		// Register Atom namespace
		$xpath->registerNamespace( 'atom', 'http://www.w3.org/2005/Atom' );

		$title_node = $xpath->query( 'atom:title', $feed )->item( 0 );
		$analysis['title'] = $title_node ? trim( $title_node->nodeValue ) : '';

		$subtitle_node = $xpath->query( 'atom:subtitle', $feed )->item( 0 );
		$analysis['description'] = $subtitle_node ? trim( $subtitle_node->nodeValue ) : '';

		$link_node = $xpath->query( 'atom:link[@rel="alternate" or not(@rel)]', $feed )->item( 0 );
		$analysis['link'] = $link_node ? $link_node->getAttribute( 'href' ) : '';

		$generator_node = $xpath->query( 'atom:generator', $feed )->item( 0 );
		$analysis['generator'] = $generator_node ? trim( $generator_node->nodeValue ) : '';

		$updated_node = $xpath->query( 'atom:updated', $feed )->item( 0 );
		$analysis['last_build_date'] = $updated_node ? trim( $updated_node->nodeValue ) : '';

		// Count entries
		$entries = $xpath->query( 'atom:entry', $feed );
		$analysis['item_count'] = $entries->length;

		return $analysis;
	}

	/**
	 * Analyze RDF channel
	 *
	 * @param \DOMElement $channel Channel element.
	 * @param DOMXPath $xpath XPath object.
	 * @param array $analysis Current analysis.
	 * @return array Updated analysis.
	 */
	private function analyze_rdf_channel( \DOMElement $channel, DOMXPath $xpath, array $analysis ): array {
		// Register RDF namespaces
		$xpath->registerNamespace( 'rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' );
		$xpath->registerNamespace( 'rss', 'http://purl.org/rss/1.0/' );

		$title_node = $xpath->query( 'rss:title', $channel )->item( 0 );
		$analysis['title'] = $title_node ? trim( $title_node->nodeValue ) : '';

		$description_node = $xpath->query( 'rss:description', $channel )->item( 0 );
		$analysis['description'] = $description_node ? trim( $description_node->nodeValue ) : '';

		$link_node = $xpath->query( 'rss:link', $channel )->item( 0 );
		$analysis['link'] = $link_node ? trim( $link_node->nodeValue ) : '';

		// Count items
		$items = $xpath->query( '//rss:item' );
		$analysis['item_count'] = $items->length;

		return $analysis;
	}

	/**
	 * Analyze feed content
	 *
	 * @param array $feeds Valid feeds.
	 * @return array Content analysis.
	 */
	private function analyze_feed_content( array $feeds ): array {
		$content_analysis = [
			'total_items' => 0,
			'average_items_per_feed' => 0,
			'content_types' => [],
			'languages' => [],
			'update_frequency' => 'unknown',
			'content_quality' => []
		];

		$valid_feeds = array_filter( $feeds, function( $feed ) {
			return isset( $feed['status'] ) && $feed['status'] === 'valid';
		});

		if ( empty( $valid_feeds ) ) {
			return $content_analysis;
		}

		$total_items = 0;
		$languages = [];

		foreach ( $valid_feeds as $feed ) {
			$item_count = $feed['item_count'] ?? 0;
			$total_items += $item_count;

			if ( ! empty( $feed['language'] ) ) {
				$lang = $feed['language'];
				if ( ! isset( $languages[ $lang ] ) ) {
					$languages[ $lang ] = 0;
				}
				$languages[ $lang ]++;
			}
		}

		$content_analysis['total_items'] = $total_items;
		$content_analysis['average_items_per_feed'] = count( $valid_feeds ) > 0 ? round( $total_items / count( $valid_feeds ), 1 ) : 0;
		$content_analysis['languages'] = $languages;

		return $content_analysis;
	}

	/**
	 * Assess feed quality
	 *
	 * @param array $feeds Feed list.
	 * @return array Quality assessment.
	 */
	private function assess_feed_quality( array $feeds ): array {
		$quality_assessment = [
			'overall_score' => 0,
			'overall_grade' => 'F',
			'feed_scores' => [],
			'quality_factors' => [],
			'issues' => [],
			'recommendations' => []
		];

		$total_score = 0;
		$scored_feeds = 0;

		foreach ( $feeds as $feed ) {
			if ( isset( $feed['status'] ) && $feed['status'] === 'valid' ) {
				$feed_score = $this->calculate_feed_quality_score( $feed );
				$quality_assessment['feed_scores'][ $feed['url'] ] = $feed_score;
				$total_score += $feed_score['score'];
				$scored_feeds++;
			}
		}

		if ( $scored_feeds > 0 ) {
			$quality_assessment['overall_score'] = round( $total_score / $scored_feeds );
			$quality_assessment['overall_grade'] = Utils::sanitize_grade( $quality_assessment['overall_score'] );
		}

		// Analyze quality factors
		$quality_assessment['quality_factors'] = $this->analyze_quality_factors( $feeds );

		// Generate issues and recommendations
		if ( $quality_assessment['overall_score'] < 70 ) {
			$quality_assessment['issues'][] = __( 'Feed quality could be improved', 'hellaz-sitez-analyzer' );
			$quality_assessment['recommendations'][] = __( 'Ensure feeds have proper metadata and regular updates', 'hellaz-sitez-analyzer' );
		}

		return $quality_assessment;
	}

	/**
	 * Calculate quality score for a single feed
	 *
	 * @param array $feed Feed data.
	 * @return array Quality score breakdown.
	 */
	private function calculate_feed_quality_score( array $feed ): array {
		$score_breakdown = [
			'score' => 0,
			'grade' => 'F',
			'factors' => []
		];

		$score = 0;

		// Metadata completeness (40 points)
		$metadata_score = 0;
		if ( ! empty( $feed['title'] ) ) $metadata_score += 10;
		if ( ! empty( $feed['description'] ) ) $metadata_score += 10;
		if ( ! empty( $feed['link'] ) ) $metadata_score += 10;
		if ( ! empty( $feed['language'] ) ) $metadata_score += 5;
		if ( ! empty( $feed['generator'] ) ) $metadata_score += 5;

		$score += $metadata_score;
		$score_breakdown['factors']['metadata'] = $metadata_score;

		// Content availability (30 points)
		$content_score = 0;
		$item_count = $feed['item_count'] ?? 0;
		if ( $item_count >= 10 ) {
			$content_score = 30;
		} elseif ( $item_count >= 5 ) {
			$content_score = 20;
		} elseif ( $item_count >= 1 ) {
			$content_score = 10;
		}

		$score += $content_score;
		$score_breakdown['factors']['content'] = $content_score;

		// Feed format and version (20 points)
		$format_score = 0;
		$format = $feed['feed_format'] ?? 'unknown';
		switch ( $format ) {
			case 'Atom':
				$format_score = 20;
				break;
			case 'RSS':
				$version = $feed['version'] ?? '';
				if ( $version === '2.0' ) {
					$format_score = 18;
				} elseif ( in_array( $version, ['1.0', '0.92', '0.91'], true ) ) {
					$format_score = 15;
				} else {
					$format_score = 10;
				}
				break;
			case 'RDF':
				$format_score = 15;
				break;
		}

		$score += $format_score;
		$score_breakdown['factors']['format'] = $format_score;

		// Accessibility and performance (10 points)
		$access_score = 0;
		$response_time = $feed['response_time'] ?? 999;
		if ( $response_time < 2 ) {
			$access_score = 10;
		} elseif ( $response_time < 5 ) {
			$access_score = 7;
		} elseif ( $response_time < 10 ) {
			$access_score = 5;
		}

		$score += $access_score;
		$score_breakdown['factors']['accessibility'] = $access_score;

		$score_breakdown['score'] = $score;
		$score_breakdown['grade'] = Utils::sanitize_grade( $score );

		return $score_breakdown;
	}

	/**
	 * Analyze quality factors across all feeds
	 *
	 * @param array $feeds Feed list.
	 * @return array Quality factors analysis.
	 */
	private function analyze_quality_factors( array $feeds ): array {
		$factors = [
			'autodiscovery_present' => false,
			'multiple_formats' => false,
			'proper_content_types' => false,
			'fast_response_times' => true,
			'regular_updates' => 'unknown'
		];

		$has_autodiscovery = false;
		$formats = [];
		$slow_feeds = 0;
		$total_valid_feeds = 0;

		foreach ( $feeds as $feed ) {
			if ( isset( $feed['autodiscovery'] ) && $feed['autodiscovery'] ) {
				$has_autodiscovery = true;
			}

			if ( isset( $feed['feed_format'] ) ) {
				$format = $feed['feed_format'];
				if ( ! in_array( $format, $formats, true ) ) {
					$formats[] = $format;
				}
			}

			if ( isset( $feed['status'] ) && $feed['status'] === 'valid' ) {
				$total_valid_feeds++;
				
				$response_time = $feed['response_time'] ?? 0;
				if ( $response_time > 5 ) {
					$slow_feeds++;
				}
			}
		}

		$factors['autodiscovery_present'] = $has_autodiscovery;
		$factors['multiple_formats'] = count( $formats ) > 1;
		$factors['fast_response_times'] = $total_valid_feeds > 0 ? ( $slow_feeds / $total_valid_feeds ) < 0.5 : true;

		return $factors;
	}

	/**
	 * Calculate feed scores
	 *
	 * @param array $analysis_data Analysis data.
	 * @return array Updated analysis data with scores.
	 */
	private function calculate_feed_scores( array $analysis_data ): array {
		$score = 0;

		// Feed presence (40 points)
		$feed_count = $analysis_data['feed_count'];
		if ( $feed_count >= 3 ) {
			$score += 40;
		} elseif ( $feed_count >= 2 ) {
			$score += 30;
		} elseif ( $feed_count >= 1 ) {
			$score += 20;
		}

		// Feed validity (30 points)
		$valid_feeds = $analysis_data['valid_feeds'];
		if ( $feed_count > 0 ) {
			$validity_ratio = $valid_feeds / $feed_count;
			$score += round( $validity_ratio * 30 );
		}

		// Feed quality (30 points)
		if ( isset( $analysis_data['feed_quality']['overall_score'] ) ) {
			$score += round( $analysis_data['feed_quality']['overall_score'] * 0.3 );
		}

		$analysis_data['feed_score'] = min( $score, 100 );
		$analysis_data['feed_grade'] = Utils::sanitize_grade( $analysis_data['feed_score'] );

		return $analysis_data;
	}

	/**
	 * Generate feed recommendations
	 *
	 * @param array $analysis_data Analysis data.
	 * @return array Recommendations.
	 */
	private function generate_feed_recommendations( array $analysis_data ): array {
		$recommendations = [];

		// No feeds found
		if ( $analysis_data['feed_count'] === 0 ) {
			$recommendations[] = [
				'priority' => 'high',
				'category' => 'presence',
				'title' => __( 'Add RSS/Atom Feeds', 'hellaz-sitez-analyzer' ),
				'description' => __( 'Implement RSS or Atom feeds to allow users to subscribe to your content updates.', 'hellaz-sitez-analyzer' )
			];
		}

		// Invalid feeds
		if ( $analysis_data['invalid_feeds'] > 0 ) {
			$recommendations[] = [
				'priority' => 'high',
				'category' => 'validity',
				'title' => __( 'Fix Invalid Feeds', 'hellaz-sitez-analyzer' ),
				'description' => sprintf(
					__( '%d feed(s) are invalid or unreachable. Check feed URLs and content structure.', 'hellaz-sitez-analyzer' ),
					$analysis_data['invalid_feeds']
				)
			];
		}

		// Low quality feeds
		if ( isset( $analysis_data['feed_quality']['overall_score'] ) && $analysis_data['feed_quality']['overall_score'] < 70 ) {
			$recommendations[] = [
				'priority' => 'medium',
				'category' => 'quality',
				'title' => __( 'Improve Feed Quality', 'hellaz-sitez-analyzer' ),
				'description' => __( 'Add proper metadata, ensure regular updates, and optimize feed performance.', 'hellaz-sitez-analyzer' )
			];
		}

		// Missing autodiscovery
		if ( isset( $analysis_data['feed_quality']['quality_factors']['autodiscovery_present'] ) && 
			 ! $analysis_data['feed_quality']['quality_factors']['autodiscovery_present'] ) {
			$recommendations[] = [
				'priority' => 'medium',
				'category' => 'discovery',
				'title' => __( 'Implement Feed Autodiscovery', 'hellaz-sitez-analyzer' ),
				'description' => __( 'Add proper <link> tags in your HTML head to enable automatic feed discovery.', 'hellaz-sitez-analyzer' )
			];
		}

		return $recommendations;
	}

	/**
	 * Get feed summary
	 *
	 * @param array $feed_data Feed analysis data.
	 * @return array Feed summary.
	 */
	public static function get_feed_summary( array $feed_data ): array {
		return [
			'total_feeds' => $feed_data['feed_count'] ?? 0,
			'valid_feeds' => $feed_data['valid_feeds'] ?? 0,
			'feed_grade' => $feed_data['feed_grade'] ?? 'F',
			'feed_score' => $feed_data['feed_score'] ?? 0,
			'feed_types' => array_keys( $feed_data['feed_types'] ?? [] ),
			'has_autodiscovery' => $feed_data['feed_quality']['quality_factors']['autodiscovery_present'] ?? false,
			'average_items' => $feed_data['content_analysis']['average_items_per_feed'] ?? 0
		];
	}
}
