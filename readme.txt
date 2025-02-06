// Plugin documentation
=== HellaZ SiteZ Analyzer ===
Contributors: hellaz
Tags: metadata, social media, ssl, open graph, gutenberg, shortcode, widget
Requires at least: 5.0
Tested up to: 6.7.1
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive remote website analysis plugin for WordPress. Analyze website metadata, including social media links, SSL info, and Open Graph tags.

== Description ==

HellaZ SiteZ Analyzer extracts metadata, detects social media profiles, RSS feeds, emails, contact forms, technology stack, and security information from remote websites. It ensures performance, security, and extensibility while adhering to WordPress best practices. Its a WordPress plugin to analyze website metadata, including social media links, SSL certificate information, and Open Graph tags. It provides a Gutenberg block and shortcode for seamless integration.

**Features:**
- **Gutenberg Block**: Add a block to analyze metadata for any URL.
- **Shortcode Support**: Use `[hsz_metadata url="https://example.com"]` for backward compatibility.
- **Social Media Detection**: Extracts links for Facebook, Twitter/X, LinkedIn, YouTube, Instagram, WhatsApp, TikTok, Pinterest, Reddit, and Telegram.
- **SSL Certificate Info**: Retrieves SSL details using APIs or direct parsing.
- **Caching**: Reduces server load by caching API responses.
- **Localization**: Supports multilingual websites with `.pot` files and translation functions.
- **Admin Settings Page**: Configure plugin options like API keys and caching duration.
- **Widget Support**: Display metadata in sidebars or widgetized areas.

== Installation ==

1. Upload the plugin to the `/wp-content/plugins/` directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Configure settings via the "SiteZ Analyzer" menu in the WordPress admin dashboard.


== Usage ==

**Gutenberg Block:**
1. Add the "HellaZ SiteZ Analyzer" block to your post or page.
2. Enter a valid URL and publish the post.

**Shortcode:**
Use the following shortcode to display metadata.
Use [hsz_metadata url="https://example.com"] in your posts or pages.

**Widget:**
Go to "Appearance > Widgets" in the WordPress admin dashboard.
Add the "HellaZ SiteZ Analyzer" widget to a sidebar.
Configure the widget settings (e.g., URL, font size, color).

== Advanced Features ==

**Admin Settings Page:**
The admin settings page allows you to configure advanced options, such as:
*API keys for external services.
*Caching duration for metadata extraction.
*Export/import settings for easy migration.
**REST API Endpoint:**
The plugin exposes a REST API endpoint for external integrations:
GET /wp-json/hsz/v1/metadata/{url}

== Support ==
For support, please visit the GitHub repository .

== Contributing ==
Contributions are welcome! If you'd like to contribute, please follow these steps:

Fork the repository.
Create a new branch for your feature or bug fix.
Submit a pull request with a detailed description of your changes.


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

== License ==
This plugin is released under the GPLv2 or later license.
