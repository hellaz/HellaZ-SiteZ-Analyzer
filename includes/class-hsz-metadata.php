<?php
/**
 * Metadata extraction for HellaZ SiteZ Analyzer.
 *
 * Handles comprehensive metadata extraction and analysis.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.1.0
 */

namespace HSZ;

defined( 'ABSPATH' ) || exit;

class Metadata {

	/**
	 * URL being analyzed
	 *
	 * @var string
	 */
	private $url;

	/**
	 * HTML content
	 *
	 * @var string
	 */
	private $html;

	/**
	 * DOM document
	 *
	 * @var \DOMDocument
	 */
	private $dom;

	/**
	 * XPath instance
	 *
	 * @var \DOMXPath
	 */
	private $xpath;

	/**
	 * Extracted metadata
	 *
	 * @var array
	 */
	private $data = [];

	/**
	 * Extract comprehensive metadata from HTML content
	 *
	 * @param string $url Website URL.
	 * @param string $html HTML content.
	 * @param array $options Extraction options.
	 * @return array Extracted metadata or error.
	 */
	public function extract_metadata( string $url, string $html, array $options = [] ): array {
		if ( empty( $html ) ) {
			return [ 'error' => __( 'HTML content is empty and cannot be analyzed.', 'hellaz-sitez-analyzer' ) ];
		}

		$this->url = $url;
		$this->html = $html;

		// Set default options
		$options = wp_parse_args( $options, [
			'extract_images' => true,
			'extract_links' => true,
			'extract_structured_data' => true,
			'extract_feeds' => true,
			'extract_languages' => true,
			'analyze_content' => true,
			'extract_performance_hints' => true
		]);

		// Initialize DOM and XPath
		if ( ! $this->init_dom() ) {
			return [ 'error' => __( 'Failed to parse HTML content.', 'hellaz-sitez-analyzer' ) ];
		}

		try {
			// EXISTING FUNCTIONALITY - Maintain all
			$this->data['title'] = $this->get_title();
			$this->data['description'] = $this->get_description();
			$this->data['favicon'] = $this->get_favicon();
			$this->data['og'] = $this->get_og_tags();
			$this->data['twitter'] = $this->get_twitter_tags();

			// ENHANCED PHASE 1 EXTRACTIONS
			$this->data['meta_tags'] = $this->get_meta_tags();
			$this->data['canonical_url'] = $this->get_canonical_url();
			$this->data['robots'] = $this->get_robots_directives();

			if ( $options['extract_structured_data'] ) {
				$this->data['structured_data'] = $this->extract_structured_data();
			}

			if ( $options['extract_images'] ) {
				$this->data['images'] = $this->extract_images();
			}

			if ( $options['extract_links'] ) {
				$this->data['links'] = $this->extract_links();
			}

			if ( $options['extract_feeds'] ) {
				$this->data['feeds'] = $this->extract_feeds();
			}

			if ( $options['extract_languages'] ) {
				$this->data['languages'] = $this->extract_language_info();
			}

			if ( $options['analyze_content'] ) {
				$this->data['content_analysis'] = $this->analyze_content();
			}

			if ( $options['extract_performance_hints'] ) {
				$this->data['performance_hints'] = $this->extract_performance_hints();
			}

			// Enhanced metadata quality score
			$this->data['metadata_quality'] = $this->calculate_metadata_quality();

		} catch ( \Throwable $e ) {
			Utils::log_error( 'Metadata extraction error for ' . $url . ': ' . $e->getMessage(), __FILE__, __LINE__ );
			return [ 'error' => __( 'An unexpected error occurred during metadata extraction.', 'hellaz-sitez-analyzer' ) ];
		}

		return $this->data;
	}

	/**
	 * Initialize DOM and XPath objects
	 *
	 * @return bool Success status.
	 */
	private function init_dom(): bool {
		$this->dom = new \DOMDocument();
		
		// Suppress warnings for malformed HTML
		$old_setting = libxml_use_internal_errors( true );
		libxml_clear_errors();
		
		$success = @$this->dom->loadHTML( '<?xml encoding="UTF-8">' . $this->html );
		
		// Restore error reporting
		libxml_use_internal_errors( $old_setting );

		if ( $success ) {
			$this->xpath = new \DOMXPath( $this->dom );
			return true;
		}

		return false;
	}

	/**
	 * Gets the page title, prioritizing Open Graph, then Twitter, then HTML title
	 *
	 * @return string Page title.
	 */
	private function get_title(): string {
		// Try Open Graph title first
		$og_title = $this->xpath->query( '//meta[@property="og:title"]/@content' );
		if ( $og_title->length > 0 ) {
			return trim( $og_title->item(0)->nodeValue );
		}

		// Try Twitter title
		$twitter_title = $this->xpath->query( '//meta[@name="twitter:title"]/@content' );
		if ( $twitter_title->length > 0 ) {
			return trim( $twitter_title->item(0)->nodeValue );
		}

		// Fall back to HTML title
		$html_title = $this->xpath->query( '//title' );
		if ( $html_title->length > 0 ) {
			return trim( $html_title->item(0)->nodeValue );
		}

		return '';
	}

	/**
	 * Gets the page description
	 *
	 * @return string Page description.
	 */
	private function get_description(): string {
		// Try Open Graph description first
		$og_desc = $this->xpath->query( '//meta[@property="og:description"]/@content' );
		if ( $og_desc->length > 0 ) {
			return trim( $og_desc->item(0)->nodeValue );
		}

		// Try Twitter description
		$twitter_desc = $this->xpath->query( '//meta[@name="twitter:description"]/@content' );
		if ( $twitter_desc->length > 0 ) {
			return trim( $twitter_desc->item(0)->nodeValue );
		}

		// Fall back to meta description
		$meta_desc = $this->xpath->query( '//meta[@name="description"]/@content' );
		if ( $meta_desc->length > 0 ) {
			return trim( $meta_desc->item(0)->nodeValue );
		}

		return '';
	}

	/**
	 * Gets the favicon URL
	 *
	 * @return string Favicon URL.
	 */
	private function get_favicon(): string {
		$favicon_selectors = [
			'//link[@rel="icon"]/@href',
			'//link[@rel="shortcut icon"]/@href',
			'//link[@rel="apple-touch-icon"]/@href'
		];

		foreach ( $favicon_selectors as $selector ) {
			$favicon = $this->xpath->query( $selector );
			if ( $favicon->length > 0 ) {
				$href = trim( $favicon->item(0)->nodeValue );
				return $this->resolve_url( $href );
			}
		}

		// Default favicon location
		$parsed_url = parse_url( $this->url );
		return $parsed_url['scheme'] . '://' . $parsed_url['host'] . '/favicon.ico';
	}

	/**
	 * Extract Open Graph tags
	 *
	 * @return array Open Graph data.
	 */
	private function get_og_tags(): array {
		$og_data = [];
		$og_tags = $this->xpath->query( '//meta[starts-with(@property, "og:")]' );

		foreach ( $og_tags as $tag ) {
			$property = $tag->getAttribute( 'property' );
			$content = $tag->getAttribute( 'content' );
			
			if ( $property && $content ) {
				$key = substr( $property, 3 ); // Remove 'og:' prefix
				$og_data[ $key ] = $content;
			}
		}

		// Resolve relative URLs for images
		if ( isset( $og_data['image'] ) ) {
			$og_data['image'] = $this->resolve_url( $og_data['image'] );
		}

		return $og_data;
	}

	/**
	 * Extract Twitter Card tags
	 *
	 * @return array Twitter Card data.
	 */
	private function get_twitter_tags(): array {
		$twitter_data = [];
		$twitter_tags = $this->xpath->query( '//meta[starts-with(@name, "twitter:")]' );

		foreach ( $twitter_tags as $tag ) {
			$name = $tag->getAttribute( 'name' );
			$content = $tag->getAttribute( 'content' );
			
			if ( $name && $content ) {
				$key = substr( $name, 8 ); // Remove 'twitter:' prefix
				$twitter_data[ $key ] = $content;
			}
		}

		// Resolve relative URLs for images
		if ( isset( $twitter_data['image'] ) ) {
			$twitter_data['image'] = $this->resolve_url( $twitter_data['image'] );
		}

		return $twitter_data;
	}

	/**
	 * Extract all meta tags
	 *
	 * @return array All meta tags.
	 */
	private function get_meta_tags(): array {
		$meta_tags = [];
		$tags = $this->xpath->query( '//meta' );

		foreach ( $tags as $tag ) {
			$name = $tag->getAttribute( 'name' ) ?: $tag->getAttribute( 'property' ) ?: $tag->getAttribute( 'http-equiv' );
			$content = $tag->getAttribute( 'content' );
			
			if ( $name && $content ) {
				$meta_tags[ $name ] = $content;
			}
		}

		return $meta_tags;
	}

	/**
	 * Get canonical URL
	 *
	 * @return string Canonical URL.
	 */
	private function get_canonical_url(): string {
		$canonical = $this->xpath->query( '//link[@rel="canonical"]/@href' );
		if ( $canonical->length > 0 ) {
			return $this->resolve_url( trim( $canonical->item(0)->nodeValue ) );
		}

		return '';
	}

	/**
	 * Get robots directives
	 *
	 * @return array Robots directives.
	 */
	private function get_robots_directives(): array {
		$robots_meta = $this->xpath->query( '//meta[@name="robots"]/@content' );
		if ( $robots_meta->length > 0 ) {
			$content = strtolower( trim( $robots_meta->item(0)->nodeValue ) );
			return array_map( 'trim', explode( ',', $content ) );
		}

		return [];
	}

	/**
	 * Extract structured data (JSON-LD, Microdata)
	 *
	 * @return array Structured data.
	 */
	private function extract_structured_data(): array {
		$structured_data = [];

		// Extract JSON-LD
		$json_ld_scripts = $this->xpath->query( '//script[@type="application/ld+json"]' );
		foreach ( $json_ld_scripts as $script ) {
			$json_content = trim( $script->nodeValue );
			if ( $json_content ) {
				$decoded = json_decode( $json_content, true );
				if ( json_last_error() === JSON_ERROR_NONE ) {
					$structured_data[] = [
						'type' => 'json-ld',
						'data' => $decoded
					];
				}
			}
		}

		// Extract basic Microdata
		$microdata_items = $this->xpath->query( '//*[@itemscope]' );
		foreach ( $microdata_items as $item ) {
			$itemtype = $item->getAttribute( 'itemtype' );
			if ( $itemtype ) {
				$structured_data[] = [
					'type' => 'microdata',
					'itemtype' => $itemtype,
					'data' => $this->extract_microdata_properties( $item )
				];
			}
		}

		return $structured_data;
	}

	/**
	 * Extract microdata properties from an element
	 *
	 * @param \DOMElement $element Element with microdata.
	 * @return array Microdata properties.
	 */
	private function extract_microdata_properties( \DOMElement $element ): array {
		$properties = [];
		$property_elements = $this->xpath->query( './/*[@itemprop]', $element );

		foreach ( $property_elements as $prop_element ) {
			$prop_name = $prop_element->getAttribute( 'itemprop' );
			$prop_value = $this->get_microdata_value( $prop_element );
			
			if ( $prop_name && $prop_value ) {
				$properties[ $prop_name ] = $prop_value;
			}
		}

		return $properties;
	}

	/**
	 * Get microdata value from element
	 *
	 * @param \DOMElement $element Element with itemprop.
	 * @return string Property value.
	 */
	private function get_microdata_value( \DOMElement $element ): string {
		$tag_name = strtolower( $element->tagName );
		
		switch ( $tag_name ) {
			case 'meta':
				return $element->getAttribute( 'content' );
			case 'img':
			case 'audio':
			case 'embed':
			case 'iframe':
			case 'source':
			case 'track':
			case 'video':
				return $element->getAttribute( 'src' );
			case 'a':
			case 'area':
			case 'link':
				return $element->getAttribute( 'href' );
			case 'object':
				return $element->getAttribute( 'data' );
			case 'data':
			case 'meter':
				return $element->getAttribute( 'value' );
			case 'time':
				return $element->getAttribute( 'datetime' ) ?: $element->nodeValue;
			default:
				return trim( $element->nodeValue );
		}
	}

	/**
	 * Extract images from the page
	 *
	 * @return array Image data.
	 */
	private function extract_images(): array {
		$images = [];
		$img_elements = $this->xpath->query( '//img[@src]' );

		foreach ( $img_elements as $img ) {
			$src = $img->getAttribute( 'src' );
			$alt = $img->getAttribute( 'alt' );
			$title = $img->getAttribute( 'title' );
			
			if ( $src ) {
				$images[] = [
					'src' => $this->resolve_url( $src ),
					'alt' => $alt,
					'title' => $title,
					'width' => $img->getAttribute( 'width' ),
					'height' => $img->getAttribute( 'height' )
				];
			}
		}

		return array_slice( $images, 0, 50 ); // Limit to 50 images
	}

	/**
	 * Extract links from the page
	 *
	 * @return array Link data.
	 */
	private function extract_links(): array {
		$links = [];
		$link_elements = $this->xpath->query( '//a[@href]' );
		$base_domain = parse_url( $this->url, PHP_URL_HOST );

		foreach ( $link_elements as $link ) {
			$href = $link->getAttribute( 'href' );
			$text = trim( $link->nodeValue );
			$title = $link->getAttribute( 'title' );
			
			if ( $href && $href !== '#' ) {
				$resolved_url = $this->resolve_url( $href );
				$link_domain = parse_url( $resolved_url, PHP_URL_HOST );
				
				$links[] = [
					'href' => $resolved_url,
					'text' => $text,
					'title' => $title,
					'type' => ( $link_domain === $base_domain ) ? 'internal' : 'external',
					'rel' => $link->getAttribute( 'rel' )
				];
			}
		}

		return array_slice( $links, 0, 100 ); // Limit to 100 links
	}

	/**
	 * Extract RSS/Atom feeds
	 *
	 * @return array Feed data.
	 */
	private function extract_feeds(): array {
		$feeds = [];
		$feed_links = $this->xpath->query( '//link[@rel="alternate"][@type="application/rss+xml" or @type="application/atom+xml"]' );

		foreach ( $feed_links as $feed ) {
			$href = $feed->getAttribute( 'href' );
			$type = $feed->getAttribute( 'type' );
			$title = $feed->getAttribute( 'title' );
			
			if ( $href ) {
				$feeds[] = [
					'url' => $this->resolve_url( $href ),
					'type' => $type,
					'title' => $title ?: 'RSS Feed'
				];
			}
		}

		return $feeds;
	}

	/**
	 * Extract language information
	 *
	 * @return array Language data.
	 */
	private function extract_language_info(): array {
		$languages = [];

		// HTML lang attribute
		$html_lang = $this->xpath->query( '//html/@lang' );
		if ( $html_lang->length > 0 ) {
			$languages['html_lang'] = trim( $html_lang->item(0)->nodeValue );
		}

		// Meta content-language
		$content_lang = $this->xpath->query( '//meta[@http-equiv="content-language"]/@content' );
		if ( $content_lang->length > 0 ) {
			$languages['content_language'] = trim( $content_lang->item(0)->nodeValue );
		}

		// Hreflang links
		$hreflang_links = $this->xpath->query( '//link[@rel="alternate"][@hreflang]' );
		$hreflangs = [];
		foreach ( $hreflang_links as $link ) {
			$hreflang = $link->getAttribute( 'hreflang' );
			$href = $link->getAttribute( 'href' );
			if ( $hreflang && $href ) {
				$hreflangs[ $hreflang ] = $this->resolve_url( $href );
			}
		}
		if ( ! empty( $hreflangs ) ) {
			$languages['hreflang'] = $hreflangs;
		}

		return $languages;
	}

	/**
	 * Analyze content structure and quality
	 *
	 * @return array Content analysis.
	 */
	private function analyze_content(): array {
		$analysis = [
			'word_count' => 0,
			'heading_structure' => [],
			'paragraph_count' => 0,
			'list_count' => 0,
			'table_count' => 0,
			'readability_score' => 0
		];

		// Get text content
		$body = $this->xpath->query( '//body' );
		if ( $body->length > 0 ) {
			$text_content = $this->get_text_content( $body->item(0) );
			$analysis['word_count'] = str_word_count( $text_content );
			$analysis['readability_score'] = $this->calculate_readability_score( $text_content );
		}

		// Analyze heading structure
		for ( $i = 1; $i <= 6; $i++ ) {
			$headings = $this->xpath->query( "//h{$i}" );
			if ( $headings->length > 0 ) {
				$analysis['heading_structure'][ "h{$i}" ] = $headings->length;
			}
		}

		// Count content elements
		$analysis['paragraph_count'] = $this->xpath->query( '//p' )->length;
		$analysis['list_count'] = $this->xpath->query( '//ul | //ol' )->length;
		$analysis['table_count'] = $this->xpath->query( '//table' )->length;

		return $analysis;
	}

	/**
	 * Extract performance hints from HTML
	 *
	 * @return array Performance hints.
	 */
	private function extract_performance_hints(): array {
		$hints = [
			'preload_links' => [],
			'prefetch_links' => [],
			'dns_prefetch' => [],
			'preconnect' => [],
			'async_scripts' => 0,
			'defer_scripts' => 0,
			'inline_styles' => 0,
			'external_styles' => 0
		];

		// Resource hints
		$preloads = $this->xpath->query( '//link[@rel="preload"]' );
		foreach ( $preloads as $preload ) {
			$hints['preload_links'][] = [
				'href' => $this->resolve_url( $preload->getAttribute( 'href' ) ),
				'as' => $preload->getAttribute( 'as' ),
				'type' => $preload->getAttribute( 'type' )
			];
		}

		$prefetches = $this->xpath->query( '//link[@rel="prefetch"]' );
		foreach ( $prefetches as $prefetch ) {
			$hints['prefetch_links'][] = $this->resolve_url( $prefetch->getAttribute( 'href' ) );
		}

		$dns_prefetches = $this->xpath->query( '//link[@rel="dns-prefetch"]' );
		foreach ( $dns_prefetches as $dns ) {
			$hints['dns_prefetch'][] = $dns->getAttribute( 'href' );
		}

		$preconnects = $this->xpath->query( '//link[@rel="preconnect"]' );
		foreach ( $preconnects as $preconnect ) {
			$hints['preconnect'][] = $preconnect->getAttribute( 'href' );
		}

		// Script loading attributes
		$hints['async_scripts'] = $this->xpath->query( '//script[@async]' )->length;
		$hints['defer_scripts'] = $this->xpath->query( '//script[@defer]' )->length;

		// Style information
		$hints['inline_styles'] = $this->xpath->query( '//style' )->length;
		$hints['external_styles'] = $this->xpath->query( '//link[@rel="stylesheet"]' )->length;

		return $hints;
	}

	/**
	 * Calculate metadata quality score
	 *
	 * @return array Metadata quality assessment.
	 */
	private function calculate_metadata_quality(): array {
		$score = 0;
		$max_score = 100;
		$issues = [];
		$recommendations = [];

		// Title assessment (25 points)
		$title = $this->data['title'] ?? '';
		if ( empty( $title ) ) {
			$issues[] = __( 'Missing page title', 'hellaz-sitez-analyzer' );
			$recommendations[] = __( 'Add a descriptive page title (30-60 characters)', 'hellaz-sitez-analyzer' );
		} elseif ( strlen( $title ) < 30 || strlen( $title ) > 60 ) {
			$score += 15;
			$issues[] = __( 'Page title length not optimal', 'hellaz-sitez-analyzer' );
			$recommendations[] = __( 'Optimize title length to 30-60 characters', 'hellaz-sitez-analyzer' );
		} else {
			$score += 25;
		}

		// Description assessment (25 points)
		$description = $this->data['description'] ?? '';
		if ( empty( $description ) ) {
			$issues[] = __( 'Missing meta description', 'hellaz-sitez-analyzer' );
			$recommendations[] = __( 'Add a meta description (120-160 characters)', 'hellaz-sitez-analyzer' );
		} elseif ( strlen( $description ) < 120 || strlen( $description ) > 160 ) {
			$score += 15;
			$issues[] = __( 'Meta description length not optimal', 'hellaz-sitez-analyzer' );
			$recommendations[] = __( 'Optimize description length to 120-160 characters', 'hellaz-sitez-analyzer' );
		} else {
			$score += 25;
		}

		// Open Graph assessment (20 points)
		$og_tags = $this->data['og'] ?? [];
		$og_score = 0;
		if ( isset( $og_tags['title'] ) ) $og_score += 5;
		if ( isset( $og_tags['description'] ) ) $og_score += 5;
		if ( isset( $og_tags['image'] ) ) $og_score += 5;
		if ( isset( $og_tags['url'] ) ) $og_score += 3;
		if ( isset( $og_tags['type'] ) ) $og_score += 2;
		
		$score += min( 20, $og_score );
		if ( $og_score < 10 ) {
			$issues[] = __( 'Missing important Open Graph tags', 'hellaz-sitez-analyzer' );
			$recommendations[] = __( 'Add Open Graph tags for better social sharing', 'hellaz-sitez-analyzer' );
		}

		// Twitter Card assessment (15 points)
		$twitter_tags = $this->data['twitter'] ?? [];
		$twitter_score = 0;
		if ( isset( $twitter_tags['card'] ) ) $twitter_score += 5;
		if ( isset( $twitter_tags['title'] ) ) $twitter_score += 4;
		if ( isset( $twitter_tags['description'] ) ) $twitter_score += 3;
		if ( isset( $twitter_tags['image'] ) ) $twitter_score += 3;
		
		$score += min( 15, $twitter_score );
		if ( $twitter_score < 8 ) {
			$issues[] = __( 'Missing Twitter Card tags', 'hellaz-sitez-analyzer' );
			$recommendations[] = __( 'Add Twitter Card tags for better Twitter sharing', 'hellaz-sitez-analyzer' );
		}

		// Structured data assessment (10 points)
		$structured_data = $this->data['structured_data'] ?? [];
		if ( ! empty( $structured_data ) ) {
			$score += min( 10, count( $structured_data ) * 3 );
		} else {
			$issues[] = __( 'No structured data found', 'hellaz-sitez-analyzer' );
			$recommendations[] = __( 'Add structured data (JSON-LD) for better SEO', 'hellaz-sitez-analyzer' );
		}

		// Canonical URL assessment (5 points)
		$canonical = $this->data['canonical_url'] ?? '';
		if ( ! empty( $canonical ) ) {
			$score += 5;
		} else {
			$issues[] = __( 'Missing canonical URL', 'hellaz-sitez-analyzer' );
			$recommendations[] = __( 'Add canonical URL to prevent duplicate content issues', 'hellaz-sitez-analyzer' );
		}

		$grade = Utils::score_to_grade( $score );

		return [
			'score' => $score,
			'grade' => $grade,
			'max_score' => $max_score,
			'completeness' => round( ( $score / $max_score ) * 100, 1 ),
			'issues' => $issues,
			'recommendations' => $recommendations
		];
	}

	/**
	 * Get text content from DOM element
	 *
	 * @param \DOMElement $element DOM element.
	 * @return string Text content.
	 */
	private function get_text_content( \DOMElement $element ): string {
		// Remove script and style elements
		$scripts = $this->xpath->query( './/script | .//style', $element );
		foreach ( $scripts as $script ) {
			$script->parentNode->removeChild( $script );
		}

		return trim( $element->textContent );
	}

	/**
	 * Calculate basic readability score
	 *
	 * @param string $text Text content.
	 * @return int Readability score (0-100).
	 */
	private function calculate_readability_score( string $text ): int {
		if ( empty( $text ) ) {
			return 0;
		}

		$sentences = preg_split( '/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY );
		$words = str_word_count( $text );
		$syllables = $this->count_syllables( $text );

		if ( count( $sentences ) === 0 || $words === 0 ) {
			return 0;
		}

		// Simplified Flesch Reading Ease formula
		$avg_sentence_length = $words / count( $sentences );
		$avg_syllables_per_word = $syllables / $words;

		$score = 206.835 - ( 1.015 * $avg_sentence_length ) - ( 84.6 * $avg_syllables_per_word );

		return max( 0, min( 100, round( $score ) ) );
	}

	/**
	 * Count syllables in text (simplified)
	 *
	 * @param string $text Text content.
	 * @return int Syllable count.
	 */
	private function count_syllables( string $text ): int {
		$text = strtolower( $text );
		$text = preg_replace( '/[^a-z]/', ' ', $text );
		$words = array_filter( explode( ' ', $text ) );
		$syllable_count = 0;

		foreach ( $words as $word ) {
			$syllable_count += max( 1, preg_match_all( '/[aeiouy]+/', $word ) );
		}

		return $syllable_count;
	}

	/**
	 * Resolve relative URL to absolute URL
	 *
	 * @param string $url URL to resolve.
	 * @return string Absolute URL.
	 */
	private function resolve_url( string $url ): string {
		if ( empty( $url ) ) {
			return '';
		}

		// Already absolute
		if ( preg_match( '/^https?:\/\//', $url ) ) {
			return $url;
		}

		$base_parts = parse_url( $this->url );
		$base_scheme = $base_parts['scheme'] ?? 'http';
		$base_host = $base_parts['host'] ?? '';
		$base_path = rtrim( $base_parts['path'] ?? '/', '/' );

		// Protocol-relative URL
		if ( strpos( $url, '//' ) === 0 ) {
			return $base_scheme . ':' . $url;
		}

		// Absolute path
		if ( strpos( $url, '/' ) === 0 ) {
			return $base_scheme . '://' . $base_host . $url;
		}

		// Relative path
		return $base_scheme . '://' . $base_host . $base_path . '/' . $url;
	}
}
