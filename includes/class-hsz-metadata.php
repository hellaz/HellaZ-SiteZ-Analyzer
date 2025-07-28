<?php
/**
 * Responsible for extracting all metadata from a given URL's HTML content.
 *
 * This class fetches HTML and parses it to find title, description,
 * Open Graph data, Twitter cards, favicons, and other relevant metadata.
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
 * Class Metadata
 *
 * Extracts and processes metadata from HTML content.
 */
class Metadata {

	private string $url;
	private string $html;
	private array $data = [];

	/**
	 * Extracts all relevant metadata from the given HTML content.
	 *
	 * @param string $url  The URL of the content being analyzed.
	 * @param string $html The HTML content of the page.
	 * @return array An associative array containing all extracted metadata.
	 */
	public function extract_metadata( string $url, string $html ): array {
		if ( empty( $html ) ) {
			return [ 'error' => __( 'HTML content is empty and cannot be analyzed.', 'hellaz-sitez-analyzer' ) ];
		}

		$this->url  = $url;
		$this->html = $html;

		try {
			$this->data['title']       = $this->get_title();
			$this->data['description'] = $this->get_description();
			$this->data['favicon']     = $this->get_favicon();
			$this->data['og']          = $this->get_og_tags();
			$this->data['twitter']     = $this->get_twitter_tags();

		} catch ( \Throwable $e ) {
			Utils::log_error( 'Metadata extraction error for ' . $url . ': ' . $e->getMessage(), __FILE__, __LINE__ );
			return [ 'error' => __( 'An unexpected error occurred during metadata extraction.', 'hellaz-sitez-analyzer' ) ];
		}

		return $this->data;
	}

	/**
	 * Gets the page title, prioritizing Open Graph, then Twitter, then <title> tag.
	 */
	private function get_title(): string {
		$og_tags = $this->get_og_tags();
		if ( ! empty( $og_tags['title'] ) ) {
			return $og_tags['title'];
		}

		$twitter_tags = $this->get_twitter_tags();
		if ( ! empty( $twitter_tags['title'] ) ) {
			return $twitter_tags['title'];
		}

		return $this->get_title_tag();
	}

	/**
	 * Gets the page description, prioritizing Open Graph, then Twitter, then meta description.
	 */
	private function get_description(): string {
		$og_tags = $this->get_og_tags();
		if ( ! empty( $og_tags['description'] ) ) {
			return $og_tags['description'];
		}

		$twitter_tags = $this->get_twitter_tags();
		if ( ! empty( $twitter_tags['description'] ) ) {
			return $twitter_tags['description'];
		}

		$meta_tags = $this->get_meta_tags();
		return $meta_tags['description'] ?? '';
	}

	// --- Private Helper Methods (Moved from Utils class) ---

	private function get_meta_tags(): array {
		if ( empty( $this->html ) ) {
			return [];
		}
		$dom = new DOMDocument();
		@$dom->loadHTML( $this->html );
		$tags      = $dom->getElementsByTagName( 'meta' );
		$meta_data = [];
		foreach ( $tags as $tag ) {
			$name = $tag->getAttribute( 'name' );
			if ( $name ) {
				$meta_data[ $name ] = $tag->getAttribute( 'content' );
			}
		}
		return $meta_data;
	}
	
	private function get_og_tags(): array {
		if ( empty( $this->html ) ) {
			return [];
		}
		$dom = new DOMDocument();
		@$dom->loadHTML( $this->html );
		$tags    = $dom->getElementsByTagName( 'meta' );
		$og_data = [];
		foreach ( $tags as $tag ) {
			$property = $tag->getAttribute( 'property' );
			if ( strpos( $property, 'og:' ) === 0 ) {
				$og_data[ substr( $property, 3 ) ] = $tag->getAttribute( 'content' );
			}
		}
		return $og_data;
	}
	
	private function get_twitter_tags(): array {
		if ( empty( $this->html ) ) {
			return [];
		}
		$dom = new DOMDocument();
		@$dom->loadHTML( $this->html );
		$tags         = $dom->getElementsByTagName( 'meta' );
		$twitter_data = [];
		foreach ( $tags as $tag ) {
			$name = $tag->getAttribute( 'name' );
			if ( strpos( $name, 'twitter:' ) === 0 ) {
				$twitter_data[ substr( $name, 8 ) ] = $tag->getAttribute( 'content' );
			}
		}
		return $twitter_data;
	}
	
	private function get_title_tag(): string {
		if ( empty( $this->html ) ) {
			return '';
		}
		$dom = new DOMDocument();
		@$dom->loadHTML( $this->html );
		$title_node = $dom->getElementsByTagName( 'title' );
		return $title_node->length > 0 ? trim( $title_node->item( 0 )->nodeValue ) : '';
	}
	
	private function get_favicon() {
		if ( empty( $this->html ) ) {
			return false;
		}
		$dom = new DOMDocument();
		@$dom->loadHTML( $this->html );
		$xpath = new DOMXPath( $dom );
		$links = $xpath->query( "//link[contains(@rel, 'icon') or contains(@rel, 'shortcut icon')]" );
		if ( $links->length > 0 ) {
			$href = $links->item( 0 )->getAttribute( 'href' );
			return $this->resolve_url( $href );
		}
		return $this->resolve_url( '/favicon.ico' );
	}
	
	private function resolve_url( string $path ): string {
		if ( strpos( $path, '//' ) === 0 ) {
			$base_parts = parse_url( $this->url );
			return ( $base_parts['scheme'] ?? 'http' ) . ':' . $path;
		}
		if ( parse_url( $path, PHP_URL_SCHEME ) !== null ) {
			return $path;
		}
		$base_parts = parse_url( $this->url );
		$base_root  = ( $base_parts['scheme'] ?? 'http' ) . '://' . ( $base_parts['host'] ?? '' );
		if ( strpos( $path, '/' ) === 0 ) {
			return $base_root . $path;
		}
		$current_path = dirname( $base_parts['path'] ?? '' );
		return $base_root . ( $current_path === '/' ? '' : $current_path ) . '/' . $path;
	}
}
