<?php
/**
 * Extracts and organizes comprehensive metadata from HTML content.
 *
 * This class acts as a high-level orchestrator, using its own detailed parsing methods
 * and the centralized Cache class to extract, cache, and return a rich set of metadata.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.0.0
 */

namespace HSZ;

use DOMDocument;
use DOMXPath;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Metadata
 *
 * Extracts and structures a comprehensive set of metadata from a web page.
 */
class Metadata {

	/**
	 * Extract comprehensive metadata from a given URL.
	 *
	 * This is the main orchestrator method that retrieves content, calls specific
	 * extraction helpers, applies fallbacks, and manages caching.
	 *
	 * @param string $url The URL to analyze.
	 * @param string $html The HTML content of the page.
	 * @return array An array of all extracted metadata.
	 */
	public function extract_metadata( string $url, string $html ): array {
		$cache_key   = 'metadata_' . md5( $url );
		$cached_data = Cache::get_cache( $cache_key );

		if ( is_array( $cached_data ) && ! empty( $cached_data ) ) {
			return $cached_data;
		}

		if ( empty( $html ) ) {
			return [ 'error' => __( 'HTML content is empty and cannot be analyzed.', 'hellaz-sitez-analyzer' ) ];
		}

		try {
			$dom = new DOMDocument();
			libxml_use_internal_errors( true );
			$dom->loadHTML( $html );
			libxml_clear_errors();
			$xpath = new DOMXPath( $dom );

			$result = [
				'title'         => $this->get_title( $dom ),
				'description'   => $this->get_meta_tag( $xpath, 'description' ),
				'favicon'       => $this->get_favicon( $xpath, $url ),
				'language'      => $this->get_language( $dom ),
				'og'            => $this->get_open_graph_tags( $xpath ),
				'twitter'       => $this->get_twitter_card_tags( $xpath ),
				'canonical'     => $this->get_canonical( $xpath, $url ),
				'robots'        => $this->get_meta_tag( $xpath, 'robots' ),
				'theme_color'   => $this->get_meta_tag( $xpath, 'theme-color' ),
				'feeds'         => $this->get_rss_feeds( $xpath, $url ),
				'contact_email' => $this->find_contact_email( $html ),
				'phone_numbers' => $this->find_phone_numbers( $html ),
			];

			// --- Enhanced Fallback Logic ---
			// 1. Prioritize social meta tags for title and description.
			$result['title']       = $result['title'] ?: ( $result['og']['title'] ?? $result['twitter']['title'] ?? '' );
			$result['description'] = $result['description'] ?: ( $result['og']['description'] ?? $result['twitter']['description'] ?? '' );
			
			// 2. Use global plugin settings as the final fallback.
			$result['title']       = $result['title'] ?: Fallbacks::get_fallback_title();
			$result['description'] = $result['description'] ?: Fallbacks::get_fallback_description();
			$result['favicon']     = $result['favicon'] ?: Fallbacks::get_fallback_image();

			Cache::set_cache( $cache_key, $result );

			return $result;

		} catch ( \Throwable $e ) {
			Utils::log_error( 'Metadata extraction error for ' . $url . ': ' . $e->getMessage() );
			return [ 'error' => __( 'An unexpected error occurred during metadata extraction.', 'hellaz-sitez-analyzer' ) ];
		}
	}

	private function get_title( DOMDocument $dom ): string {
		$node = $dom->getElementsByTagName( 'title' );
		return $node->length > 0 ? trim( $node->item( 0 )->nodeValue ) : '';
	}

	private function get_meta_tag( DOMXPath $xpath, string $name ): string {
		$nodes = $xpath->query( "//meta[@name='{$name}']" );
		return $nodes->length > 0 ? trim( $nodes->item( 0 )->getAttribute( 'content' ) ) : '';
	}

	private function get_favicon( DOMXPath $xpath, string $base_url ): string {
		$queries = [
			"//link[@rel='icon']",
			"//link[@rel='shortcut icon']",
			"//link[@rel='apple-touch-icon']",
		];
		foreach ( $queries as $query ) {
			$nodes = $xpath->query( $query );
			if ( $nodes->length > 0 ) {
				$href = $nodes->item( 0 )->getAttribute( 'href' );
				if ( $href ) {
					return Utils::resolve_url( $href, $base_url );
				}
			}
		}
		return '';
	}
    
	private function get_language( DOMDocument $dom ): string {
		$html_node = $dom->getElementsByTagName( 'html' );
		return $html_node->length > 0 ? $html_node->item( 0 )->getAttribute( 'lang' ) : '';
	}

	private function get_open_graph_tags( DOMXPath $xpath ): array {
		$tags  = [];
		$nodes = $xpath->query( "//meta[starts-with(@property, 'og:')]" );
		foreach ( $nodes as $node ) {
			$property         = substr( $node->getAttribute( 'property' ), 3 );
			$tags[ $property ] = $node->getAttribute( 'content' );
		}
		return $tags;
	}

	private function get_twitter_card_tags( DOMXPath $xpath ): array {
		$tags  = [];
		$nodes = $xpath->query( "//meta[starts-with(@name, 'twitter:')]" );
		foreach ( $nodes as $node ) {
			$name             = substr( $node->getAttribute( 'name' ), 8 );
			$tags[ $name ] = $node->getAttribute( 'content' );
		}
		return $tags;
	}

	private function get_canonical( DOMXPath $xpath, string $base_url ): string {
		$nodes = $xpath->query( "//link[@rel='canonical']" );
		if ( $nodes->length > 0 ) {
			$href = $nodes->item( 0 )->getAttribute( 'href' );
			return Utils::resolve_url( $href, $base_url );
		}
		return '';
	}

	private function get_rss_feeds( DOMXPath $xpath, string $base_url ): array {
		$feeds = [];
		$types = [ 'application/rss+xml', 'application/atom+xml' ];
		foreach ( $types as $type ) {
			$nodes = $xpath->query( "//link[@type='{$type}']" );
			foreach ( $nodes as $node ) {
				$href = $node->getAttribute( 'href' );
				if ( $href ) {
					$feeds[] = Utils::resolve_url( $href, $base_url );
				}
			}
		}
		return array_unique( $feeds );
	}
    
	private function find_contact_email( string $html ): string {
		// A simple regex to find potential email addresses.
		if ( preg_match( '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $html, $matches ) ) {
			return $matches[0];
		}
		return '';
	}

	private function find_phone_numbers( string $html ): array {
		// A simple regex to find potential phone numbers.
		$numbers = [];
		if ( preg_match_all( '/(\+\d{1,3}\s?)?(\(\d{1,4}\)|\d{1,4})[\s.-]?\d{3,4}[\s.-]?\d{3,4}/', $html, $matches ) ) {
			$numbers = $matches[0];
		}
		return array_unique( $numbers );
	}
}
