<?php
/**
 * Enhanced social media profile detection and analysis for HellaZ SiteZ Analyzer.
 *
 * This class provides comprehensive social media analysis including profile detection,
 * validation, metrics extraction, content analysis, and social SEO assessment
 * with Phase 1 enhancements.
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
 * Class SocialMedia
 *
 * Handles social media profile detection and analysis with enhanced capabilities.
 */
class SocialMedia {

	/**
	 * Enhanced social media platforms with improved patterns and metadata
	 */
	private const PLATFORMS = [
		// Major Social Networks
		'facebook' => [
			'pattern' => '~https?://(?:www\.)?facebook\.com/([a-zA-Z0-9\.]+)~i',
			'api_available' => true,
			'metrics_supported' => true,
			'category' => 'social_network',
			'priority' => 10
		],
		'twitter' => [
			'pattern' => '~https?://(?:www\.)?(?:twitter|x)\.com/([a-zA-Z0-9_]+)~i',
			'api_available' => true,
			'metrics_supported' => true,
			'category' => 'social_network',
			'priority' => 10
		],
		'instagram' => [
			'pattern' => '~https?://(?:www\.)?instagram\.com/([a-zA-Z0-9_.]+)~i',
			'api_available' => true,
			'metrics_supported' => true,
			'category' => 'social_network',
			'priority' => 9
		],
		'linkedin' => [
			'pattern' => '~https?://(?:www\.)?linkedin\.com/(?:in|company)/([a-zA-Z0-9\-\_]+)~i',
			'api_available' => true,
			'metrics_supported' => true,
			'category' => 'professional',
			'priority' => 9
		],
		'youtube' => [
			'pattern' => '~https?://(?:www\.)?youtube\.com/(?:channel|user|c)/([a-zA-Z0-9_\-]+)~i',
			'api_available' => true,
			'metrics_supported' => true,
			'category' => 'video',
			'priority' => 8
		],
		'tiktok' => [
			'pattern' => '~https?://(?:www\.)?tiktok\.com/@([a-zA-Z0-9._]+)~i',
			'api_available' => false,
			'metrics_supported' => false,
			'category' => 'video',
			'priority' => 8
		],
		'threads' => [
			'pattern' => '~https?://(?:www\.)?threads\.net/@([a-zA-Z0-9_.]+)~i',
			'api_available' => false,
			'metrics_supported' => false,
			'category' => 'social_network',
			'priority' => 7
		],

		// Professional Networks
		'github' => [
			'pattern' => '~https?://(?:www\.)?github\.com/([a-zA-Z0-9_\-]+)~i',
			'api_available' => true,
			'metrics_supported' => true,
			'category' => 'professional',
			'priority' => 8
		],
		'gitlab' => [
			'pattern' => '~https?://(?:www\.)?gitlab\.com/([a-zA-Z0-9_\-]+)~i',
			'api_available' => true,
			'metrics_supported' => true,
			'category' => 'professional',
			'priority' => 6
		],
		'behance' => [
			'pattern' => '~https?://(?:www\.)?behance\.net/([a-zA-Z0-9\-_]+)~i',
			'api_available' => false,
			'metrics_supported' => false,
			'category' => 'creative',
			'priority' => 6
		],
		'dribbble' => [
			'pattern' => '~https?://(?:www\.)?dribbble\.com/([a-zA-Z0-9\-_]+)~i',
			'api_available' => false,
			'metrics_supported' => false,
			'category' => 'creative',
			'priority' => 6
		],

		// Media & Content
		'medium' => [
			'pattern' => '~https?://medium\.com/@?([a-zA-Z0-9\-_]+)~i',
			'api_available' => false,
			'metrics_supported' => false,
			'category' => 'publishing',
			'priority' => 7
		],
		'substack' => [
			'pattern' => '~https?://([a-zA-Z0-9\-_]+)\.substack\.com~i',
			'api_available' => false,
			'metrics_supported' => false,
			'category' => 'publishing',
			'priority' => 6
		],
		'spotify' => [
			'pattern' => '~https?://open\.spotify\.com/(?:user|artist)/([a-zA-Z0-9]+)~i',
			'api_available' => true,
			'metrics_supported' => true,
			'category' => 'music',
			'priority' => 6
		],
		'soundcloud' => [
			'pattern' => '~https?://(?:www\.)?soundcloud\.com/([a-zA-Z0-9_\-]+)~i',
			'api_available' => true,
			'metrics_supported' => true,
			'category' => 'music',
			'priority' => 6
		],
		'vimeo' => [
			'pattern' => '~https?://(?:www\.)?vimeo\.com/([a-zA-Z0-9_\-]+)~i',
			'api_available' => true,
			'metrics_supported' => true,
			'category' => 'video',
			'priority' => 5
		],

		// Alternative & Emerging Platforms
		'mastodon' => [
			'pattern' => '~https?://(?:[a-z0-9\-]+\.)?mastodon\.[a-z]+/@?([a-zA-Z0-9_\-]+)~i',
			'api_available' => true,
			'metrics_supported' => true,
			'category' => 'alternative',
			'priority' => 6
		],
		'bluesky' => [
			'pattern' => '~https?://(?:www\.)?bsky\.app/profile/([a-zA-Z0-9_\-\.]+)~i',
			'api_available' => false,
			'metrics_supported' => false,
			'category' => 'alternative',
			'priority' => 7
		],
		'nostr' => [
			'pattern' => '~https?://(?:www\.)?(?:primal\.net|iris\.to|damus\.io)/([a-zA-Z0-9_\-]+)~i',
			'api_available' => false,
			'metrics_supported' => false,
			'category' => 'alternative',
			'priority' => 4
		],

		// Messaging & Communication
		'telegram' => [
			'pattern' => '~https?://(?:t\.me|telegram\.me)/([a-zA-Z0-9_]+)~i',
			'api_available' => true,
			'metrics_supported' => false,
			'category' => 'messaging',
			'priority' => 6
		],
		'discord' => [
			'pattern' => '~https?://(?:www\.)?discord(?:app)?\.com/(?:invite|users)/([a-zA-Z0-9]+)~i',
			'api_available' => false,
			'metrics_supported' => false,
			'category' => 'messaging',
			'priority' => 5
		],
		'whatsapp' => [
			'pattern' => '~https?://(?:wa\.me|api\.whatsapp\.com)/([0-9]+)~i',
			'api_available' => false,
			'metrics_supported' => false,
			'category' => 'messaging',
			'priority' => 7
		],
		'signal' => [
			'pattern' => '~https?://signal\.me/#p/([+0-9]+)~i',
			'api_available' => false,
			'metrics_supported' => false,
			'category' => 'messaging',
			'priority' => 4
		],

		// Gaming & Streaming
		'twitch' => [
			'pattern' => '~https?://(?:www\.)?twitch\.tv/([a-zA-Z0-9_]+)~i',
			'api_available' => true,
			'metrics_supported' => true,
			'category' => 'gaming',
			'priority' => 7
		],
		'steam' => [
			'pattern' => '~https?://steamcommunity\.com/(?:id|profiles)/([a-zA-Z0-9_\-]+)~i',
			'api_available' => true,
			'metrics_supported' => false,
			'category' => 'gaming',
			'priority' => 5
		],

		// E-commerce & Marketplaces
		'etsy' => [
			'pattern' => '~https?://(?:www\.)?etsy\.com/shop/([a-zA-Z0-9\-_]+)~i',
			'api_available' => false,
			'metrics_supported' => false,
			'category' => 'ecommerce',
			'priority' => 5
		],
		'amazon' => [
			'pattern' => '~https?://(?:www\.)?amazon\.[a-z.]+/(?:stores|seller)/([a-zA-Z0-9\-_]+)~i',
			'api_available' => false,
			'metrics_supported' => false,
			'category' => 'ecommerce',
			'priority' => 6
		],

		// Creator Economy
		'patreon' => [
			'pattern' => '~https?://(?:www\.)?patreon\.com/([a-zA-Z0-9\-_]+)~i',
			'api_available' => true,
			'metrics_supported' => true,
			'category' => 'creator',
			'priority' => 6
		],
		'onlyfans' => [
			'pattern' => '~https?://(?:www\.)?onlyfans\.com/([a-zA-Z0-9\-_]+)~i',
			'api_available' => false,
			'metrics_supported' => false,
			'category' => 'creator',
			'priority' => 5
		],
		'ko_fi' => [
			'pattern' => '~https?://ko-fi\.com/([a-zA-Z0-9\-_]+)~i',
			'api_available' => false,
			'metrics_supported' => false,
			'category' => 'creator',
			'priority' => 4
		],

		// Older/Legacy Platforms
		'tumblr' => [
			'pattern' => '~https?://([a-zA-Z0-9\-]+)\.tumblr\.com~i',
			'api_available' => true,
			'metrics_supported' => true,
			'category' => 'legacy',
			'priority' => 4
		],
		'flickr' => [
			'pattern' => '~https?://(?:www\.)?flickr\.com/people/([a-zA-Z0-9@_\-]+)~i',
			'api_available' => true,
			'metrics_supported' => true,
			'category' => 'legacy',
			'priority' => 3
		],
		'myspace' => [
			'pattern' => '~https?://(?:www\.)?myspace\.com/([a-zA-Z0-9\-_]+)~i',
			'api_available' => false,
			'metrics_supported' => false,
			'category' => 'legacy',
			'priority' => 2
		],

		// Regional Platforms
		'weibo' => [
			'pattern' => '~https?://(?:www\.)?weibo\.com/([a-zA-Z0-9_]+)~i',
			'api_available' => true,
			'metrics_supported' => true,
			'category' => 'regional',
			'priority' => 6
		],
		'wechat' => [
			'pattern' => '~https?://(?:www\.)?wechat\.com/([a-zA-Z0-9_\-]+)~i',
			'api_available' => false,
			'metrics_supported' => false,
			'category' => 'regional',
			'priority' => 5
		],
		'line' => [
			'pattern' => '~https?://line\.me/([a-zA-Z0-9_\-]+)~i',
			'api_available' => false,
			'metrics_supported' => false,
			'category' => 'regional',
			'priority' => 4
		],
		'vk' => [
			'pattern' => '~https?://(?:www\.)?vk\.com/([a-zA-Z0-9_\-]+)~i',
			'api_available' => true,
			'metrics_supported' => true,
			'category' => 'regional',
			'priority' => 5
		],

		// Business & Analytics
		'crunchbase' => [
			'pattern' => '~https?://(?:www\.)?crunchbase\.com/organization/([\w\-]+)~i',
			'api_available' => true,
			'metrics_supported' => true,
			'category' => 'business',
			'priority' => 6
		],
		'glassdoor' => [
			'pattern' => '~https?://(?:www\.)?glassdoor\.com/Overview/Working-at-([a-zA-Z0-9\-]+)~i',
			'api_available' => false,
			'metrics_supported' => false,
			'category' => 'business',
			'priority' => 4
		]
	];

	/**
	 * Social media integration patterns
	 */
	private const INTEGRATION_PATTERNS = [
		'facebook_sdk' => '/facebook\.net\/[^\/]*\/sdk\.js/',
		'twitter_widgets' => '/platform\.twitter\.com\/widgets\.js/',
		'instagram_embed' => '/instagram\.com\/embed\.js/',
		'linkedin_sdk' => '/platform\.linkedin\.com\/in\.js/',
		'youtube_api' => '/youtube\.com\/iframe_api/',
		'social_login' => '/oauth|social.?login|sign.?in.?with/i',
		'share_buttons' => '/addthis|sharethis|sharebutton/i',
		'social_feeds' => '/social.?feed|twitter.?timeline|facebook.?feed/i'
	];

	/**
	 * Comprehensive social media analysis
	 *
	 * @param string $html Website HTML content.
	 * @param string $base_url Website base URL.
	 * @param array $options Analysis options.
	 * @return array Comprehensive social media analysis results.
	 */
	public function analyze_social_media( string $html, string $base_url = '', array $options = [] ): array {
		// Set default options
		$options = wp_parse_args( $options, [
			'validate_profiles' => true,
			'extract_metrics' => true,
			'analyze_content' => true,
			'check_integrations' => true,
			'social_seo_analysis' => true,
			'brand_consistency' => true,
			'force_refresh' => false
		]);

		// Check cache first
		$cache_key = 'social_analysis_' . md5( $base_url . serialize( $options ) );
		$cached_data = Cache::get( $cache_key, 'social' );

		if ( $cached_data && ! $options['force_refresh'] ) {
			return $cached_data;
		}

		Utils::start_timer( 'social_analysis' );

		$analysis_data = [
			'url' => $base_url,
			'timestamp' => current_time( 'mysql', true ),
			'profiles' => [],
			'profile_count' => 0,
			'platforms_detected' => [],
			'category_breakdown' => [],
			'priority_profiles' => [],
			'validation_results' => [],
			'metrics' => [],
			'integrations' => [],
			'social_seo' => [],
			'brand_analysis' => [],
			'recommendations' => [],
			'social_score' => 0,
			'social_grade' => 'F',
			'influence_score' => 0,
			'analysis_time' => 0
		];

		// Extract social profiles
		$analysis_data['profiles'] = $this->extract_social_profiles( $html, $base_url );
		$analysis_data['profile_count'] = count( $analysis_data['profiles'] );
		$analysis_data['platforms_detected'] = $this->get_detected_platforms( $analysis_data['profiles'] );
		$analysis_data['category_breakdown'] = $this->categorize_platforms( $analysis_data['platforms_detected'] );
		$analysis_data['priority_profiles'] = $this->get_priority_profiles( $analysis_data['profiles'] );

		// Validate profiles if enabled
		if ( $options['validate_profiles'] && ! empty( $analysis_data['profiles'] ) ) {
			$analysis_data['validation_results'] = $this->validate_social_profiles( $analysis_data['profiles'] );
		}

		// Extract metrics if enabled
		if ( $options['extract_metrics'] && ! empty( $analysis_data['profiles'] ) ) {
			$analysis_data['metrics'] = $this->extract_social_metrics( $analysis_data['profiles'] );
		}

		// Check integrations if enabled
		if ( $options['check_integrations'] ) {
			$analysis_data['integrations'] = $this->analyze_social_integrations( $html );
		}

		// Social SEO analysis if enabled
		if ( $options['social_seo_analysis'] ) {
			$analysis_data['social_seo'] = $this->analyze_social_seo( $html );
		}

		// Brand consistency analysis if enabled
		if ( $options['brand_consistency'] && ! empty( $analysis_data['profiles'] ) ) {
			$analysis_data['brand_analysis'] = $this->analyze_brand_consistency( $analysis_data['profiles'] );
		}

		// Calculate scores and grades
		$analysis_data = $this->calculate_social_scores( $analysis_data );

		// Generate recommendations
		$analysis_data['recommendations'] = $this->generate_social_recommendations( $analysis_data );

		$analysis_data['analysis_time'] = Utils::stop_timer( 'social_analysis' );

		// Cache the results
		Cache::set( $cache_key, $analysis_data, DAY_IN_SECONDS, 'social' );

		// Store in enhanced database if available
		$this->store_social_results( $base_url, $analysis_data );

		return $analysis_data;
	}

	/**
	 * Enhanced social profile extraction with improved detection
	 *
	 * @param string $html The raw HTML of the site.
	 * @param string $base_url The base URL for context and caching.
	 * @return array An array of found social profile URLs with metadata.
	 */
	public function extract_social_profiles( string $html, string $base_url = '' ): array {
		$cache_key = 'social_profiles_' . md5( $base_url );
		$cached_data = Cache::get( $cache_key, 'social' );

		if ( is_array( $cached_data ) ) {
			return $cached_data;
		}

		$found_profiles = [];
		$found_urls = [];

		// 1. Extract from HTML links
		$all_links = Utils::get_all_links( $html, $base_url );
		foreach ( $all_links as $link ) {
			foreach ( self::PLATFORMS as $platform => $config ) {
				if ( preg_match( $config['pattern'], $link, $matches ) ) {
					$username = $matches[1] ?? '';
					$profile_data = [
						'platform' => $platform,
						'url' => $link,
						'username' => $username,
						'detection_method' => 'html_links',
						'category' => $config['category'],
						'priority' => $config['priority'],
						'api_available' => $config['api_available'],
						'metrics_supported' => $config['metrics_supported']
					];
					
					$found_profiles[] = $profile_data;
					$found_urls[] = $link;
					break;
				}
			}
		}

		// 2. Extract from JSON-LD structured data
		if ( preg_match_all( '/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches ) ) {
			foreach ( $matches[1] as $json_string ) {
				$data = json_decode( trim( $json_string ), true );
				if ( json_last_error() === JSON_ERROR_NONE && isset( $data['sameAs'] ) ) {
					$same_as = is_array( $data['sameAs'] ) ? $data['sameAs'] : [ $data['sameAs'] ];
					
					foreach ( $same_as as $url ) {
						if ( ! in_array( $url, $found_urls, true ) ) {
							foreach ( self::PLATFORMS as $platform => $config ) {
								if ( preg_match( $config['pattern'], $url, $matches ) ) {
									$username = $matches[1] ?? '';
									$profile_data = [
										'platform' => $platform,
										'url' => $url,
										'username' => $username,
										'detection_method' => 'json_ld',
										'category' => $config['category'],
										'priority' => $config['priority'],
										'api_available' => $config['api_available'],
										'metrics_supported' => $config['metrics_supported']
									];
									
									$found_profiles[] = $profile_data;
									$found_urls[] = $url;
									break;
								}
							}
						}
					}
				}
			}
		}

		// 3. Extract from Open Graph and Twitter meta tags
		$meta_profiles = $this->extract_profiles_from_meta_tags( $html );
		foreach ( $meta_profiles as $profile ) {
			if ( ! in_array( $profile['url'], $found_urls, true ) ) {
				$found_profiles[] = $profile;
				$found_urls[] = $profile['url'];
			}
		}

		// 4. Extract from rel="me" links
		$me_profiles = $this->extract_rel_me_profiles( $html, $base_url );
		foreach ( $me_profiles as $profile ) {
			if ( ! in_array( $profile['url'], $found_urls, true ) ) {
				$found_profiles[] = $profile;
				$found_urls[] = $profile['url'];
			}
		}

		// Remove duplicates and sort by priority
		$found_profiles = $this->deduplicate_profiles( $found_profiles );
		usort( $found_profiles, function( $a, $b ) {
			return $b['priority'] <=> $a['priority'];
		});

		Cache::set( $cache_key, $found_profiles, DAY_IN_SECONDS, 'social' );
		return $found_profiles;
	}

	/**
	 * Extract social profiles from meta tags
	 *
	 * @param string $html HTML content.
	 * @return array Found profiles.
	 */
	private function extract_profiles_from_meta_tags( string $html ): array {
		$profiles = [];
		
		// Extract Twitter handle
		if ( preg_match( '/<meta[^>]*name=["\']twitter:site["\'][^>]*content=["\']@?([a-zA-Z0-9_]+)["\'][^>]*>/i', $html, $matches ) ) {
			$username = $matches[1];
			$profiles[] = [
				'platform' => 'twitter',
				'url' => "https://twitter.com/{$username}",
				'username' => $username,
				'detection_method' => 'meta_tags',
				'category' => 'social_network',
				'priority' => 10,
				'api_available' => true,
				'metrics_supported' => true
			];
		}

		// Extract Facebook app ID (indicates Facebook integration)
		if ( preg_match( '/<meta[^>]*property=["\']fb:app_id["\'][^>]*content=["\']([0-9]+)["\'][^>]*>/i', $html, $matches ) ) {
			// This doesn't give us a direct profile URL, but indicates Facebook presence
			// We could potentially use this for further analysis
		}

		return $profiles;
	}

	/**
	 * Extract profiles from rel="me" links
	 *
	 * @param string $html HTML content.
	 * @param string $base_url Base URL for resolving relative links.
	 * @return array Found profiles.
	 */
	private function extract_rel_me_profiles( string $html, string $base_url ): array {
		$profiles = [];
		
		if ( preg_match_all( '/<a[^>]*rel=["\'][^"\']*me[^"\']*["\'][^>]*href=["\']([^"\']+)["\'][^>]*>/i', $html, $matches ) ) {
			foreach ( $matches[1] as $url ) {
				$resolved_url = Utils::resolve_url( $url, $base_url );
				
				foreach ( self::PLATFORMS as $platform => $config ) {
					if ( preg_match( $config['pattern'], $resolved_url, $url_matches ) ) {
						$username = $url_matches[1] ?? '';
						$profiles[] = [
							'platform' => $platform,
							'url' => $resolved_url,
							'username' => $username,
							'detection_method' => 'rel_me',
							'category' => $config['category'],
							'priority' => $config['priority'],
							'api_available' => $config['api_available'],
							'metrics_supported' => $config['metrics_supported']
						];
						break;
					}
				}
			}
		}

		return $profiles;
	}

	/**
	 * Deduplicate profiles while preserving the best detection method
	 *
	 * @param array $profiles Array of profile data.
	 * @return array Deduplicated profiles.
	 */
	private function deduplicate_profiles( array $profiles ): array {
		$unique_profiles = [];
		$seen_urls = [];

		$method_priority = [
			'json_ld' => 4,
			'rel_me' => 3,
			'meta_tags' => 2,
			'html_links' => 1
		];

		foreach ( $profiles as $profile ) {
			$url = $profile['url'];
			
			if ( ! isset( $seen_urls[ $url ] ) ) {
				$unique_profiles[] = $profile;
				$seen_urls[ $url ] = count( $unique_profiles ) - 1;
			} else {
				// Check if this detection method is better
				$existing_index = $seen_urls[ $url ];
				$existing_priority = $method_priority[ $unique_profiles[ $existing_index ]['detection_method'] ] ?? 0;
				$current_priority = $method_priority[ $profile['detection_method'] ] ?? 0;
				
				if ( $current_priority > $existing_priority ) {
					$unique_profiles[ $existing_index ] = $profile;
				}
			}
		}

		return array_values( $unique_profiles );
	}

	/**
	 * Get detected platforms list
	 *
	 * @param array $profiles Found profiles.
	 * @return array Platform names.
	 */
	private function get_detected_platforms( array $profiles ): array {
		$platforms = [];
		foreach ( $profiles as $profile ) {
			if ( ! in_array( $profile['platform'], $platforms, true ) ) {
				$platforms[] = $profile['platform'];
			}
		}
		return $platforms;
	}

	/**
	 * Categorize platforms by type
	 *
	 * @param array $platforms Platform names.
	 * @return array Category breakdown.
	 */
	private function categorize_platforms( array $platforms ): array {
		$categories = [];
		
		foreach ( $platforms as $platform ) {
			if ( isset( self::PLATFORMS[ $platform ] ) ) {
				$category = self::PLATFORMS[ $platform ]['category'];
				if ( ! isset( $categories[ $category ] ) ) {
					$categories[ $category ] = [];
				}
				$categories[ $category ][] = $platform;
			}
		}

		return $categories;
	}

	/**
	 * Get high-priority profiles
	 *
	 * @param array $profiles Found profiles.
	 * @return array High-priority profiles.
	 */
	private function get_priority_profiles( array $profiles ): array {
		$priority_profiles = [];
		
		foreach ( $profiles as $profile ) {
			if ( $profile['priority'] >= 8 ) {
				$priority_profiles[] = $profile;
			}
		}

		return $priority_profiles;
	}

	/**
	 * Validate social profiles
	 *
	 * @param array $profiles Social profiles to validate.
	 * @return array Validation results.
	 */
	private function validate_social_profiles( array $profiles ): array {
		$validation_results = [
			'total_profiles' => count( $profiles ),
			'valid_profiles' => 0,
			'invalid_profiles' => 0,
			'unreachable_profiles' => 0,
			'profile_details' => []
		];

		foreach ( $profiles as $profile ) {
			$validation = $this->validate_single_profile( $profile );
			$validation_results['profile_details'][ $profile['platform'] ] = $validation;
			
			switch ( $validation['status'] ) {
				case 'valid':
					$validation_results['valid_profiles']++;
					break;
				case 'invalid':
					$validation_results['invalid_profiles']++;
					break;
				case 'unreachable':
					$validation_results['unreachable_profiles']++;
					break;
			}
		}

		return $validation_results;
	}

	/**
	 * Validate a single social profile
	 *
	 * @param array $profile Profile data.
	 * @return array Validation result.
	 */
	private function validate_single_profile( array $profile ): array {
		$validation = [
			'status' => 'unknown',
			'http_status' => 0,
			'response_time' => 0,
			'exists' => false,
			'accessible' => false,
			'error' => ''
		];

		Utils::start_timer( 'profile_validation_' . $profile['platform'] );

		$response = wp_remote_head( $profile['url'], [
			'timeout' => 10,
			'user-agent' => 'HellaZ SiteZ Analyzer/' . HSZ_VERSION,
			'redirection' => 3
		]);

		$validation['response_time'] = Utils::stop_timer( 'profile_validation_' . $profile['platform'] );

		if ( is_wp_error( $response ) ) {
			$validation['status'] = 'unreachable';
			$validation['error'] = $response->get_error_message();
		} else {
			$http_status = wp_remote_retrieve_response_code( $response );
			$validation['http_status'] = $http_status;
			
			if ( $http_status >= 200 && $http_status < 400 ) {
				$validation['status'] = 'valid';
				$validation['exists'] = true;
				$validation['accessible'] = true;
			} elseif ( $http_status == 404 ) {
				$validation['status'] = 'invalid';
				$validation['error'] = __( 'Profile not found', 'hellaz-sitez-analyzer' );
			} elseif ( $http_status >= 400 ) {
				$validation['status'] = 'unreachable';
				$validation['error'] = sprintf( __( 'HTTP %d error', 'hellaz-sitez-analyzer' ), $http_status );
			}
		}

		return $validation;
	}

	/**
	 * Extract social media metrics
	 *
	 * @param array $profiles Social profiles.
	 * @return array Metrics data.
	 */
	private function extract_social_metrics( array $profiles ): array {
		$metrics = [
			'total_estimated_reach' => 0,
			'platform_metrics' => [],
			'api_metrics' => [],
			'scraped_metrics' => [],
			'metrics_available' => false
		];

		foreach ( $profiles as $profile ) {
			if ( $profile['metrics_supported'] && $profile['api_available'] ) {
				$platform_metrics = $this->get_platform_metrics( $profile );
				if ( ! empty( $platform_metrics ) ) {
					$metrics['platform_metrics'][ $profile['platform'] ] = $platform_metrics;
					$metrics['metrics_available'] = true;
				}
			}
		}

		return $metrics;
	}

	/**
	 * Get metrics for a specific platform
	 *
	 * @param array $profile Profile data.
	 * @return array Platform metrics.
	 */
	private function get_platform_metrics( array $profile ): array {
		$metrics = [];

		switch ( $profile['platform'] ) {
			case 'twitter':
				$metrics = $this->get_twitter_metrics( $profile );
				break;
			case 'facebook':
				$metrics = $this->get_facebook_metrics( $profile );
				break;
			case 'instagram':
				$metrics = $this->get_instagram_metrics( $profile );
				break;
			case 'linkedin':
				$metrics = $this->get_linkedin_metrics( $profile );
				break;
			case 'youtube':
				$metrics = $this->get_youtube_metrics( $profile );
				break;
			case 'github':
				$metrics = $this->get_github_metrics( $profile );
				break;
			default:
				// For platforms without specific API integration, try basic scraping
				$metrics = $this->get_basic_profile_metrics( $profile );
				break;
		}

		return $metrics;
	}

	/**
	 * Get Twitter metrics (placeholder for API integration)
	 *
	 * @param array $profile Profile data.
	 * @return array Twitter metrics.
	 */
	private function get_twitter_metrics( array $profile ): array {
		// This would integrate with Twitter API v2
		// For now, return placeholder structure
		return [
			'followers' => 0,
			'following' => 0,
			'tweets' => 0,
			'verified' => false,
			'api_available' => false,
			'last_updated' => current_time( 'mysql', true )
		];
	}

	/**
	 * Get GitHub metrics using GitHub API
	 *
	 * @param array $profile Profile data.
	 * @return array GitHub metrics.
	 */
	private function get_github_metrics( array $profile ): array {
		$api_url = 'https://api.github.com/users/' . $profile['username'];
		
		$response = wp_remote_get( $api_url, [
			'timeout' => 10,
			'user-agent' => 'HellaZ SiteZ Analyzer/' . HSZ_VERSION,
			'headers' => [
				'Accept' => 'application/vnd.github.v3+json'
			]
		]);

		if ( is_wp_error( $response ) ) {
			return ['error' => $response->get_error_message()];
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return ['error' => 'Invalid JSON response'];
		}

		return [
			'followers' => $data['followers'] ?? 0,
			'following' => $data['following'] ?? 0,
			'public_repos' => $data['public_repos'] ?? 0,
			'public_gists' => $data['public_gists'] ?? 0,
			'created_at' => $data['created_at'] ?? '',
			'updated_at' => $data['updated_at'] ?? '',
			'bio' => $data['bio'] ?? '',
			'company' => $data['company'] ?? '',
			'location' => $data['location'] ?? '',
			'verified' => $data['site_admin'] ?? false,
			'api_available' => true,
			'last_updated' => current_time( 'mysql', true )
		];
	}

	/**
	 * Get basic profile metrics through scraping (fallback method)
	 *
	 * @param array $profile Profile data.
	 * @return array Basic metrics.
	 */
	private function get_basic_profile_metrics( array $profile ): array {
		// This is a basic fallback that attempts to scrape public information
		// Implementation would vary by platform
		return [
			'accessible' => true,
			'profile_complete' => true,
			'has_avatar' => false,
			'has_bio' => false,
			'last_activity' => 'unknown',
			'api_available' => false,
			'scraping_method' => 'basic',
			'last_updated' => current_time( 'mysql', true )
		];
	}

	/**
	 * Placeholder methods for other platform metrics
	 */
	private function get_facebook_metrics( array $profile ): array {
		return ['api_available' => false, 'requires_access_token' => true];
	}

	private function get_instagram_metrics( array $profile ): array {
		return ['api_available' => false, 'requires_business_account' => true];
	}

	private function get_linkedin_metrics( array $profile ): array {
		return ['api_available' => false, 'requires_company_page' => true];
	}

	private function get_youtube_metrics( array $profile ): array {
		return ['api_available' => false, 'requires_api_key' => true];
	}

	/**
	 * Analyze social media integrations
	 *
	 * @param string $html HTML content.
	 * @return array Integration analysis.
	 */
	private function analyze_social_integrations( string $html ): array {
		$integrations = [
			'social_login' => false,
			'share_buttons' => false,
			'social_feeds' => false,
			'social_sdks' => [],
			'integration_score' => 0,
			'detected_integrations' => []
		];

		foreach ( self::INTEGRATION_PATTERNS as $integration => $pattern ) {
			if ( preg_match( $pattern, $html ) ) {
				$integrations['detected_integrations'][] = $integration;
				
				switch ( $integration ) {
					case 'social_login':
						$integrations['social_login'] = true;
						break;
					case 'share_buttons':
						$integrations['share_buttons'] = true;
						break;
					case 'social_feeds':
						$integrations['social_feeds'] = true;
						break;
					default:
						if ( strpos( $integration, '_sdk' ) !== false ) {
							$platform = str_replace( '_sdk', '', $integration );
							$integrations['social_sdks'][] = $platform;
						}
						break;
				}
			}
		}

		// Calculate integration score
		$score = 0;
		$score += $integrations['social_login'] ? 25 : 0;
		$score += $integrations['share_buttons'] ? 20 : 0;
		$score += $integrations['social_feeds'] ? 15 : 0;
		$score += count( $integrations['social_sdks'] ) * 10;

		$integrations['integration_score'] = min( $score, 100 );

		return $integrations;
	}

	/**
	 * Analyze social SEO elements
	 *
	 * @param string $html HTML content.
	 * @return array Social SEO analysis.
	 */
	private function analyze_social_seo( string $html ): array {
		$social_seo = [
			'open_graph' => [],
			'twitter_cards' => [],
			'structured_data' => [],
			'social_seo_score' => 0,
			'issues' => [],
			'recommendations' => []
		];

		// Analyze Open Graph tags
		$og_tags = Utils::get_og_tags( $html );
		$social_seo['open_graph'] = [
			'present' => ! empty( $og_tags ),
			'tags' => $og_tags,
			'completeness' => $this->calculate_og_completeness( $og_tags )
		];

		// Analyze Twitter Cards
		$twitter_tags = Utils::get_twitter_tags( $html );
		$social_seo['twitter_cards'] = [
			'present' => ! empty( $twitter_tags ),
			'tags' => $twitter_tags,
			'completeness' => $this->calculate_twitter_completeness( $twitter_tags )
		];

		// Analyze structured data
		$structured_data = Utils::extract_json_ld( $html );
		$social_seo['structured_data'] = [
			'present' => ! empty( $structured_data ),
			'types' => $this->get_structured_data_types( $structured_data ),
			'social_relevant' => $this->has_social_structured_data( $structured_data )
		];

		// Calculate overall social SEO score
		$score = 0;
		$score += $social_seo['open_graph']['completeness'];
		$score += $social_seo['twitter_cards']['completeness'];
		$score += $social_seo['structured_data']['social_relevant'] ? 20 : 0;

		$social_seo['social_seo_score'] = round( $score / 3 );

		// Generate issues and recommendations
		if ( $social_seo['open_graph']['completeness'] < 80 ) {
			$social_seo['issues'][] = __( 'Incomplete Open Graph implementation', 'hellaz-sitez-analyzer' );
			$social_seo['recommendations'][] = __( 'Add missing Open Graph tags for better social media sharing', 'hellaz-sitez-analyzer' );
		}

		if ( $social_seo['twitter_cards']['completeness'] < 60 ) {
			$social_seo['issues'][] = __( 'Missing or incomplete Twitter Card tags', 'hellaz-sitez-analyzer' );
			$social_seo['recommendations'][] = __( 'Implement Twitter Card tags to improve Twitter sharing', 'hellaz-sitez-analyzer' );
		}

		return $social_seo;
	}

	/**
	 * Calculate Open Graph completeness score
	 *
	 * @param array $og_tags Open Graph tags.
	 * @return int Completeness score (0-100).
	 */
	private function calculate_og_completeness( array $og_tags ): int {
		$required_tags = [ 'title', 'description', 'image', 'url', 'type' ];
		$optional_tags = [ 'site_name', 'locale' ];
		
		$score = 0;
		$found_required = 0;
		$found_optional = 0;

		foreach ( $required_tags as $tag ) {
			if ( isset( $og_tags[ $tag ] ) && ! empty( $og_tags[ $tag ] ) ) {
				$found_required++;
			}
		}

		foreach ( $optional_tags as $tag ) {
			if ( isset( $og_tags[ $tag ] ) && ! empty( $og_tags[ $tag ] ) ) {
				$found_optional++;
			}
		}

		// Required tags are worth 80%, optional 20%
		$score = ( $found_required / count( $required_tags ) ) * 80;
		$score += ( $found_optional / count( $optional_tags ) ) * 20;

		return round( $score );
	}

	/**
	 * Calculate Twitter Card completeness score
	 *
	 * @param array $twitter_tags Twitter Card tags.
	 * @return int Completeness score (0-100).
	 */
	private function calculate_twitter_completeness( array $twitter_tags ): int {
		$required_tags = [ 'card' ];
		$recommended_tags = [ 'title', 'description', 'image', 'site' ];
		
		$score = 0;
		$found_required = 0;
		$found_recommended = 0;

		foreach ( $required_tags as $tag ) {
			if ( isset( $twitter_tags[ $tag ] ) && ! empty( $twitter_tags[ $tag ] ) ) {
				$found_required++;
			}
		}

		foreach ( $recommended_tags as $tag ) {
			if ( isset( $twitter_tags[ $tag ] ) && ! empty( $twitter_tags[ $tag ] ) ) {
				$found_recommended++;
			}
		}

		if ( $found_required === 0 ) {
			return 0; // No Twitter Cards at all
		}

		// Required tags are worth 40%, recommended 60%
		$score = ( $found_required / count( $required_tags ) ) * 40;
		$score += ( $found_recommended / count( $recommended_tags ) ) * 60;

		return round( $score );
	}

	/**
	 * Get structured data types
	 *
	 * @param array $structured_data Structured data array.
	 * @return array Data types found.
	 */
	private function get_structured_data_types( array $structured_data ): array {
		$types = [];
		
		foreach ( $structured_data as $data ) {
			if ( isset( $data['@type'] ) ) {
				$type = is_array( $data['@type'] ) ? $data['@type'][0] : $data['@type'];
				if ( ! in_array( $type, $types, true ) ) {
					$types[] = $type;
				}
			}
		}

		return $types;
	}

	/**
	 * Check if structured data contains social-relevant information
	 *
	 * @param array $structured_data Structured data array.
	 * @return bool True if social-relevant data found.
	 */
	private function has_social_structured_data( array $structured_data ): bool {
		$social_types = [ 'Organization', 'Person', 'WebSite', 'Article', 'BlogPosting' ];
		$social_properties = [ 'sameAs', 'url', 'author', 'publisher' ];

		foreach ( $structured_data as $data ) {
			// Check for social-relevant types
			if ( isset( $data['@type'] ) ) {
				$type = is_array( $data['@type'] ) ? $data['@type'][0] : $data['@type'];
				if ( in_array( $type, $social_types, true ) ) {
					return true;
				}
			}

			// Check for social-relevant properties
			foreach ( $social_properties as $property ) {
				if ( isset( $data[ $property ] ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Analyze brand consistency across platforms
	 *
	 * @param array $profiles Social profiles.
	 * @return array Brand consistency analysis.
	 */
	private function analyze_brand_consistency( array $profiles ): array {
		$brand_analysis = [
			'username_consistency' => 0,
			'profile_completeness' => 0,
			'consistent_usernames' => [],
			'inconsistent_usernames' => [],
			'recommendations' => []
		];

		// Analyze username consistency
		$usernames = [];
		foreach ( $profiles as $profile ) {
			$username = strtolower( $profile['username'] );
			if ( ! isset( $usernames[ $username ] ) ) {
				$usernames[ $username ] = [];
			}
			$usernames[ $username ][] = $profile['platform'];
		}

		$most_common_username = '';
		$max_count = 0;
		foreach ( $usernames as $username => $platforms ) {
			if ( count( $platforms ) > $max_count ) {
				$max_count = count( $platforms );
				$most_common_username = $username;
			}
		}

		$brand_analysis['consistent_usernames'] = $usernames[ $most_common_username ] ?? [];
		$brand_analysis['username_consistency'] = count( $profiles ) > 0 ? round( ( $max_count / count( $profiles ) ) * 100 ) : 0;

		// Find inconsistent usernames
		foreach ( $usernames as $username => $platforms ) {
			if ( $username !== $most_common_username ) {
				$brand_analysis['inconsistent_usernames'][ $username ] = $platforms;
			}
		}

		// Generate recommendations
		if ( $brand_analysis['username_consistency'] < 80 ) {
			$brand_analysis['recommendations'][] = sprintf(
				__( 'Consider using consistent username "%s" across all platforms for better brand recognition', 'hellaz-sitez-analyzer' ),
				$most_common_username
			);
		}

		return $brand_analysis;
	}

	/**
	 * Calculate social media scores
	 *
	 * @param array $analysis_data Analysis data.
	 * @return array Updated analysis data with scores.
	 */
	private function calculate_social_scores( array $analysis_data ): array {
		$score = 0;
		$max_score = 100;

		// Profile presence (40 points)
		$profile_score = min( count( $analysis_data['profiles'] ) * 8, 40 );
		$score += $profile_score;

		// Platform diversity (20 points)
		$category_count = count( $analysis_data['category_breakdown'] );
		$diversity_score = min( $category_count * 5, 20 );
		$score += $diversity_score;

		// Social SEO (20 points)
		if ( isset( $analysis_data['social_seo']['social_seo_score'] ) ) {
			$score += $analysis_data['social_seo']['social_seo_score'] * 0.2;
		}

		// Integration score (20 points)
		if ( isset( $analysis_data['integrations']['integration_score'] ) ) {
			$score += $analysis_data['integrations']['integration_score'] * 0.2;
		}

		$analysis_data['social_score'] = round( min( $score, $max_score ) );
		$analysis_data['social_grade'] = Utils::sanitize_grade( $analysis_data['social_score'] );

		// Calculate influence score (basic implementation)
		$influence_score = 0;
		$influence_score += count( $analysis_data['priority_profiles'] ) * 15;
		$influence_score += count( $analysis_data['profiles'] ) * 5;
		
		if ( isset( $analysis_data['validation_results']['valid_profiles'] ) ) {
			$influence_score += $analysis_data['validation_results']['valid_profiles'] * 10;
		}

		$analysis_data['influence_score'] = round( min( $influence_score, 100 ) );

		return $analysis_data;
	}

	/**
	 * Generate social media recommendations
	 *
	 * @param array $analysis_data Analysis data.
	 * @return array Recommendations.
	 */
	private function generate_social_recommendations( array $analysis_data ): array {
		$recommendations = [];

		// Profile recommendations
		if ( $analysis_data['profile_count'] < 3 ) {
			$recommendations[] = [
				'priority' => 'high',
				'category' => 'presence',
				'title' => __( 'Expand Social Media Presence', 'hellaz-sitez-analyzer' ),
				'description' => __( 'Create profiles on major social media platforms to increase brand visibility and reach.', 'hellaz-sitez-analyzer' )
			];
		}

		// Platform diversity recommendations
		$categories = array_keys( $analysis_data['category_breakdown'] );
		if ( ! in_array( 'social_network', $categories, true ) ) {
			$recommendations[] = [
				'priority' => 'high',
				'category' => 'diversity',
				'title' => __( 'Join Major Social Networks', 'hellaz-sitez-analyzer' ),
				'description' => __( 'Create profiles on Facebook, Twitter, or Instagram to reach broader audiences.', 'hellaz-sitez-analyzer' )
			];
		}

		// Social SEO recommendations
		if ( isset( $analysis_data['social_seo']['social_seo_score'] ) && $analysis_data['social_seo']['social_seo_score'] < 70 ) {
			$recommendations[] = [
				'priority' => 'medium',
				'category' => 'seo',
				'title' => __( 'Improve Social SEO', 'hellaz-sitez-analyzer' ),
				'description' => __( 'Add Open Graph and Twitter Card meta tags to improve social media sharing.', 'hellaz-sitez-analyzer' )
			];
		}

		// Integration recommendations
		if ( isset( $analysis_data['integrations']['integration_score'] ) && $analysis_data['integrations']['integration_score'] < 50 ) {
			$recommendations[] = [
				'priority' => 'medium',
				'category' => 'integration',
				'title' => __( 'Add Social Media Integration', 'hellaz-sitez-analyzer' ),
				'description' => __( 'Implement social login, share buttons, or social feeds to improve user engagement.', 'hellaz-sitez-analyzer' )
			];
		}

		// Brand consistency recommendations
		if ( isset( $analysis_data['brand_analysis']['username_consistency'] ) && $analysis_data['brand_analysis']['username_consistency'] < 80 ) {
			$recommendations = array_merge( $recommendations, $analysis_data['brand_analysis']['recommendations'] );
		}

		return $recommendations;
	}

	/**
	 * Store social media results in enhanced database
	 *
	 * @param string $url Website URL.
	 * @param array $social_data Social media analysis results.
	 */
	private function store_social_results( string $url, array $social_data ): void {
		// This would store results in a dedicated social media results table
		// Implementation depends on database schema
		
		$url_hash = Utils::generate_url_hash( $url );
		
		// Store in cache with structured key
		$cache_key = "social_results_{$url_hash}";
		Cache::set( $cache_key, $social_data, DAY_IN_SECONDS * 7, 'social' );
	}

	/**
	 * Get social media summary
	 *
	 * @param array $social_data Social media analysis data.
	 * @return array Social media summary.
	 */
	public static function get_social_summary( array $social_data ): array {
		return [
			'total_profiles' => $social_data['profile_count'] ?? 0,
			'major_platforms' => count( $social_data['priority_profiles'] ?? [] ),
			'social_grade' => $social_data['social_grade'] ?? 'F',
			'social_score' => $social_data['social_score'] ?? 0,
			'influence_score' => $social_data['influence_score'] ?? 0,
			'has_social_seo' => ( $social_data['social_seo']['social_seo_score'] ?? 0 ) > 60,
			'has_integrations' => ! empty( $social_data['integrations']['detected_integrations'] ?? [] ),
			'top_platforms' => array_slice( array_keys( $social_data['category_breakdown'] ?? [] ), 0, 3 )
		];
	}
}
