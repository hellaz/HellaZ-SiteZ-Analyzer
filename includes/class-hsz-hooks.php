<?php
/**
 * Manages frontend hooks and filters.
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
 * Class Hooks
 *
 * Handles frontend modifications, such as enhancing content links.
 */
class Hooks {

	/**
	 * Hooks constructor.
	 *
	 * Registers the necessary hooks and filters.
	 */
	public function __construct() {
		add_filter( 'the_content', [ $this, 'auto_analyze_links' ] );
		add_filter( 'the_content', [ $this, 'add_disclaimer' ], 99 ); // Run after other filters.
	}

	/**
	 * Auto-analyzes links in content if the setting is enabled.
	 *
	 * This function uses a regular expression to find all external hyperlinks
	 * in the post content and adds data attributes to them for potential
	 * JavaScript-based enhancements or analysis.
	 *
	 * @param string $content The post content.
	 * @return string The modified post content.
	 */
	public function auto_analyze_links( $content ) {
		// Only run this if the user has enabled it in settings.
		if ( ! get_option( 'hsz_auto_analyze_content', false ) ) {
			return $content;
		}

		// A more robust regex to capture `<a>` tags.
		// It avoids capturing attributes that might contain '>'.
		$pattern = '/<a\s+(?:[^>]*?\s+)?href=(["\'])(.*?)\1[^>]*>/is';

		return preg_replace_callback(
			$pattern,
			function ( $matches ) {
				$original_tag = $matches[0]; // The entire matched opening <a> tag.
				$url          = $matches[2]; // The URL from the href attribute.

				// Only process valid, external URLs. home_url() can be slow, so check for http first.
				if ( strpos( $url, 'http' ) === 0 && strpos( $url, home_url() ) === false ) {
					// This is an external link. Add data attributes.
					$data_attributes = sprintf(
						' data-hsz-analyzed="false" data-hsz-url="%s"',
						esc_attr( $url )
					);

					// Inject the data attributes into the `<a>` tag.
					return str_replace( '<a ', '<a ' . $data_attributes, $original_tag );
				}

				// If it's an internal link or invalid URL, return the original tag unchanged.
				return $original_tag;
			},
			$content
		);
	}

	/**
	 * Adds a disclaimer message below the content if enabled.
	 *
	 * @param string $content The post content.
	 * @return string The content with the disclaimer appended.
	 */
	public function add_disclaimer( $content ) {
		if ( is_singular() && in_the_loop() && is_main_query() ) {
			if ( get_option( 'hsz_disclaimer_enabled', false ) ) {
				$disclaimer = get_option( 'hsz_disclaimer_message', '' );
				if ( ! empty( $disclaimer ) ) {
					$content .= '<div class="hsz-disclaimer">' . wp_kses_post( $disclaimer ) . '</div>';
				}
			}
		}
		return $content;
	}
}
