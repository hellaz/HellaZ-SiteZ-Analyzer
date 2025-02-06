// Plugin documentation
=== HellaZ SiteZ Analyzer ===
Contributors: hellaz
Tags: metadata, analysis, seo, social media, rss, security
Requires at least: 5.6
Tested up to: 6.3
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive remote website analysis plugin for WordPress.

== Description ==

HellaZ SiteZ Analyzer extracts metadata, detects social media profiles, RSS feeds, emails, contact forms, technology stack, and security information from remote websites. It ensures performance, security, and extensibility while adhering to WordPress best practices. Its a WordPress plugin to analyze website metadata, including social media links, SSL certificate information, and Open Graph tags. It provides a Gutenberg block and shortcode for seamless integration.

== Features ==
- Gutenberg Block : Add a block to analyze metadata for any URL.
- Shortcode : Use [hsz_metadata url="https://example.com"] for backward compatibility.
- Social Media Detection : Extracts links for Facebook, Twitter/X, Instagram, and WhatsApp.
- SSL Certificate Info : Retrieves SSL details using APIs or direct parsing.
- Caching : Reduces server load by caching API responses.
- Localization : Supports multilingual websites.

== Installation == 
Upload the plugin to the /wp-content/plugins/ directory.
Activate the plugin through the "Plugins" menu in WordPress.
Use the Gutenberg block or shortcode to analyze metadata.

== Usage ==
Gutenberg Block :
Add the "HellaZ SiteZ Analyzer" block to your post.
Enter a valid URL and publish the post.
Shortcode :
Use [hsz_metadata url="https://example.com"] in your posts or pages.

== Frequently Asked Questions ==

= How do I use the Gutenberg block? =
Add the "HellaZ SiteZ Analyzer" block to your post or page and enter the URL of the website you want to analyze.

= Can I customize the fallback image? =
Yes, you can upload a custom fallback image via the plugin settings page.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release. No upgrade notes available.
