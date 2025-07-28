<?php
/**
 * Extracts RSS feed URLs from a web page.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.0.0
 */

namespace HSZ;

use DOMDocument;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RSS
 *
 * Provides functionality to discover RSS and Atom feeds.
 */
class RSS {

	/**
	 * Extracts all RSS and Atom feed URLs from a given HTML content.
	 *
	 * @param string $html     The HTML content of the page.
	 * @param string $base_url The base URL of the page, used for caching key generation.
	 * @return array An array of found feed URLs.
	 */
	public static function extract_feeds( string $html, string $base_url ): array {
		if ( empty( $html ) ) {
			return [];
		}

		$cache_key = 'hsz_feeds_' . md5( $base_url );
		$cached_feeds = get_transient( $cache_key );

		if ( is_array( $cached_feeds ) ) {
			return $cached_feeds;
		}

		$feeds = [];
		$dom   = new DOMDocument();

		// Suppress warnings from malformed HTML.
		if ( ! @$dom->loadHTML( $html ) ) {
			Utils::log_error( 'Failed to parse HTML for RSS feed extraction.' );
			return [];
		}

		// Extract RSS/Atom feeds from <link> tags.
		$links = $dom->getElementsByTagName( 'link' );
		foreach ( $links as $link ) {
			$type = $link->getAttribute( 'type' );
			if ( in_array( $type, [ 'application/rss+xml', 'application/atom+xml' ], true ) ) {
				$href = $link->getAttribute( 'href' );

				if ( ! empty( $href ) ) {
					// Resolve the URL in case it's relative.
					$feed_url = Utils::resolve_url( $href, $base_url );
					if ( filter_var( $feed_url, FILTER_VALIDATE_URL ) ) {
						$feeds[] = $feed_url;
					}
				}
			}
		}
		
		$feeds = array_unique( $feeds );

		// Cache the results for 24 hours.
		set_transient( $cache_key, $feeds, DAY_IN_SECONDS );

		return $feeds;
	}
}
