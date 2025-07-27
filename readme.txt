=== HellaZ SiteZ Analyzer ===
Contributors: hellaz
Tags: website analysis, metadata, security, social profiles, feeds
Requires at least: 5.0
Tested up to: 6.3
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

HellaZ SiteZ Analyzer is a comprehensive WordPress plugin that extracts and displays detailed website metadata, social media profiles, security info, and technology stack analysis.

== Description ==

With HellaZ SiteZ Analyzer, easily analyze any website URL directly from your WordPress site. Extract page titles, descriptions, favicons, social profiles, RSS feeds, technology stack details, and security info from services like VirusTotal and BuiltWith.

Features include:

* Metadata extraction (title, description, favicon)
* Social media detection across 30+ platforms
* Bulk processing with progress tracking
* Fully configurable admin settings with tabs
* Caching and cache management
* Multiple output templates for shortcodes, widgets, and blocks
* Secure AJAX and REST endpoints
* Error logging and admin log viewer
* Multisite network compatibility

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin via the Plugins menu in WordPress.
3. Configure your API keys and settings under Settings > SiteZ Analyzer.
4. Use the shortcode `[hsz_analyzer url="https://example.com"]`, widget, or block to display analysis.

== Frequently Asked Questions ==

= Do I need API keys? =

API keys are optional but required to access enhanced services like VirusTotal, BuiltWith, and URLScan.io.

= Is caching enabled? =

Yes, caching is enabled by default with configurable duration and manual clearing.

= Can I analyze multiple URLs in bulk? =

Yes, the Bulk tab allows queueing multiple URLs and tracking progress.

== Changelog ==

= 1.0.0 =
* Complete restoration and enhancement of plugin features.
* Added support for 30+ social platforms and comprehensive metadata extraction.
* Improved caching, bulk processing, AJAX, and admin interfaces.

== Upgrade Notice ==

Upgrade only after backing up your site and database.

== Screenshots ==

1. Settings page with tabbed interface  
2. SiteZ Analyzer block in Gutenberg  
3. SiteZ Analyzer widget in sidebar  
4. Bulk processing status tab  
5. Admin error log viewer

== License ==

This plugin is licensed under the GPLv2 or later.
