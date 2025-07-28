<?php
/**
 * Extracts social media profile links from HTML, including JSON-LD.
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
 * Class SocialMedia
 *
 * Finds links to a comprehensive list of social media platforms.
 */
class SocialMedia {

	/**
	 * An extensive list of platforms and their regex patterns for detection.
	 * Preserved from the original file to ensure maximum data extraction.
	 *
	 * @var array
	 */
	private const PLATFORMS = [
		'facebook'   => '~https?://(?:www\.)?facebook\.com/([a-zA-Z0-9\.]+)~i',
		'twitter'    => '~https?://(?:www\.)?(?:twitter|x)\.com/([a-zA-Z0-9_]+)~i',
		'instagram'  => '~https?://(?:www\.)?instagram\.com/([a-zA-Z0-9_.]+)~i',
		'linkedin'   => '~https?://(?:www\.)?linkedin\.com/(?:in|company)/([a-zA-Z0-9\-\_]+)~i',
		'youtube'    => '~https?://(?:www\.)?youtube\.com/(?:channel|user|c)/([a-zA-Z0-9_\-]+)~i',
		'github'     => '~https?://(?:www\.)?github\.com/([a-zA-Z0-9_\-]+)~i',
		'gitlab'     => '~https?://(?:www\.)?gitlab\.com/([a-zA-Z0-9_\-]+)~i',
		'spotify'    => '~https?://open\.spotify\.com/user/([a-zA-Z0-9]+)~i',
		'medium'     => '~https?://medium\.com/@?([a-zA-Z0-9\-_]+)~i',
		'tumblr'     => '~https?://([a-zA-Z0-9\-]+)\.tumblr\.com~i',
		'flickr'     => '~https?://(?:www\.)?flickr\.com/people/([a-zA-Z0-9@_\-]+)~i',
		'behance'    => '~https?://(?:www\.)?behance\.net/([a-zA-Z0-9\-_]+)~i',
		'dribbble'   => '~https?://(?:www\.)?dribbble\.com/([a-zA-Z0-9\-_]+)~i',
		'slack'      => '~https?://([a-zA-Z0-9_-]+)\.slack\.com~i',
		'patreon'    => '~https?://(?:www\.)?patreon\.com/([a-zA-Z0-9\-_]+)~i',
		'etsy'       => '~https?://(?:www\.)?etsy\.com/shop/([a-zA-Z0-9\-_]+)~i',
		'onlyfans'   => '~https?://(?:www\.)?onlyfans\.com/([a-zA-Z0-9\-_]+)~i',
		'amazon'     => '~https?://(?:www\.)?amazon\.[a-z.]+/([a-zA-Z0-9\-_]+)~i',
		'ebay'       => '~https?://(?:www\.)?ebay\.[a-z.]+/usr/([a-zA-Z0-9\-_]+)~i',
		'bluesky'    => '~https?://(?:www\.)?bsky\.app/profile/([a-zA-Z0-9_\-\.]+)~i',
		'tiktok'     => '~https?://(?:www\.)?tiktok\.com/@([a-zA-Z0-9._]+)~i',
		'threads'    => '~https?://(?:www\.)?threads\.net/@([a-zA-Z0-9_.]+)~i',
		'mastodon'   => '~https?://(?:[a-z0-9\-]+\.)?mastodon\.[a-z]+/@?([a-zA-Z0-9_\-]+)~i',
		'reddit'     => '~https?://(?:www\.)?reddit\.com/user/([a-zA-Z0-9_\-]+)~i',
		'telegram'   => '~https?://(?:t\.me|telegram\.me)/([a-zA-Z0-9_]+)~i',
		'snapchat'   => '~https?://(?:www\.)?snapchat\.com/add/([a-zA-Z0-9._]+)~i',
		'whatsapp'   => '~https?://(?:wa\.me|api\.whatsapp\.com)/([0-9]+)~i',
		'vimeo'      => '~https?://(?:www\.)?vimeo\.com/([a-zA-Z0-9_\-]+)~i',
		'soundcloud' => '~https?://(?:www\.)?soundcloud\.com/([a-zA-Z0-9_\-]+)~i',
		'discord'    => '~https?://(?:www\.)?discord(?:app)?\.com/invite/([a-zA-Z0-9]+)~i',
		'twitch'     => '~https?://(?:www\.)?twitch\.tv/([a-zA-Z0-9_]+)~i',
		'similarweb' => '~https?://www\.similarweb\.com/website/([\w\.\-]+)~i',
		'alexa'      => '~https?://www\.alexa\.com/siteinfo/([\w\.\-]+)~i',
		'crunchbase' => '~https?://www\.crunchbase\.com/organization/([\w\-]+)~i',
		'statista'   => '~https?://www\.statista\.com/chart/(\d+)~i',
		'dmoz'       => '~https?://www\.dmoz\.org/([^/]+)/([^/]+)/([^/]+)/~i',
	];

	/**
	 * Extract social profiles from HTML, including hrefs and JSON-LD data.
	 *
	 * @param string $html The raw HTML of the site.
	 * @param string $base_url The base URL for context and caching.
	 * @return array An array of found social profile URLs.
	 */
	public function extract_social_profiles( string $html, string $base_url = '' ): array {
		$cache_key   = 'social_profiles_' . md5( $base_url );
		$cached_data = Cache::get_cache( $cache_key );

		if ( is_array( $cached_data ) ) {
			return $cached_data;
		}

		$found_urls = [];

		// 1. Scrape all anchor hrefs from HTML
		$all_links = Utils::get_all_links( $html, $base_url );
		foreach ( $all_links as $link ) {
			foreach ( self::PLATFORMS as $pattern ) {
				if ( preg_match( $pattern, $link ) ) {
					$found_urls[] = $link;
					break; // Move to the next link once a platform is matched.
				}
			}
		}

		// 2. Scan JSON-LD for "sameAs" arrays
		if ( preg_match_all( '~<script type="application/ld\+json"[^>]*>(.*?)</script>~is', $html, $json_matches ) ) {
			foreach ( $json_matches[1] as $json_string ) {
				$json_data = json_decode( $json_string, true );
				if ( ! empty( $json_data['sameAs'] ) && is_array( $json_data['sameAs'] ) ) {
					foreach ( $json_data['sameAs'] as $same_as_url ) {
						if ( is_string( $same_as_url ) && filter_var( $same_as_url, FILTER_VALIDATE_URL ) ) {
							$found_urls[] = $same_as_url;
						}
					}
				}
			}
		}

		$unique_urls = array_unique( $found_urls );
		Cache::set_cache( $cache_key, $unique_urls );

		return $unique_urls;
	}
}
