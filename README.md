# HellaZ SiteZ Analyzer

**Contributors:** Hellaz  
**Tags:** website analysis, metadata, security, social profiles, feeds, WordPress plugin  
**Requires at least:** 5.0  
**Tested up to:** 6.3  
**Requires PHP:** 7.4  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

## Description

HellaZ SiteZ Analyzer is a powerful WordPress plugin designed to fetch, analyze, and display detailed metadata and insights about any given website URL. It extracts page titles, descriptions, favicons, social profiles, RSS/Atom feeds, technology stacks, security indicators, and more. The plugin supports single URL lookups via shortcode, widgets, or Gutenberg blocks as well as batch (bulk) analyses with detailed progress monitoring.

**Key Features:**

- Extracts rich metadata (titles, descriptions, favicons) reliably.
- **Gutenberg Block**: Add a block to analyze metadata for any URL.
- **Shortcode Support**: Use `[hsz_metadata url="https://example.com"]` for backward compatibility.
- **Widget Support**: Display metadata in sidebars or widgetized areas.
- Detects social media profiles across 30+ major platforms.
- Integrates with VirusTotal, BuiltWith, URLScan.io via APIs for security and technology data.
- **Metadata Detection**:  Extracts metadata, title, favicon, keywords, author, open graph tags from remote website.
- **Social Media Detection**: Extracts links for Facebook, Twitter/X, LinkedIn, YouTube, Instagram, WhatsApp, TikTok, Pinterest, Reddit, and Telegram.
- **Contact Info Detection**: Extracts emails, contact forms, address from target website
- **Feed Detection**: Retrieves RSS/Atom Feeds
- **Server & Security Info**: Retrieves Server location, Security & SSL details using APIs or direct parsing.
- Supports caching with customizable expiration and manual clearing. Reduces server load by caching API responses.
- Flexible, multi-layout output: classic, modern, or minimal views.
- Provides comprehensive bulk URL processing with progress tracking.
- Full WordPress admin settings with organized tabbed interface.
- GDPR friendly, fully translatable, and compatible with multisite.
- Admin tool for reviewing error and event logs.
- REST and AJAX endpoints secured by nonces and capability checks.
- Supports theme and plugin developers by allowing template customization.

## Installation
1. Upload the plugin to the `/wp-content/plugins/` directory. Extract to Create `/wp-content/plugins/hellaz-sitez-analyzer` directory, or install via the WordPress plugin admin.
2. Activate the plugin through the 'Plugins' menu.
3. Configure API keys and settings under **Settings > SiteZ Analyzer**.
4. Use shortcodes `[hsz_analyzer url="https://example.com"]`, widgets, or blocks to display site analyses.

## Usage

- **Shortcode:** `[hsz_analyzer url="https://example.com"]`
- **Widget:** Add the "SiteZ Analyzer" widget to any widgetized area.
- **Gutenberg Block:** Use the "SiteZ Analyzer" block in posts or pages.
- **Bulk Analysis:** Upload URLs from the admin interface and monitor progress in the Bulk tab.
- **Cache:** Control and clear cached results from the Cache tab.
- **Logs:** Review runtime errors and events under Tools > SiteZ Logs.

## Screenshots

1. Plugin settings with tabbed interface  
2. Site analysis block on post editor  
3. Widget displaying site metadata and social links  
4. Bulk analysis status page  
5. Logs viewer interface  

## Frequently Asked Questions

**Q:** Do I need API keys to use the plugin?  
**A:** Basic metadata extraction is free and requires no keys. API keys enable extended features like VirusTotal or BuiltWith data.

**Q:** How does caching work?  
**A:** The plugin caches analysis results via WordPress transients, configurable in the admin with manual purge option.

**Q:** Is this plugin multisite compatible?  
**A:** Yes, it supports network activation and per-site configuration.

## Changelog

### 1.0.0 – July 2025

- Complete restoration and enhancement of plugin system.
- Added support for 30+ social media platforms.
- Bulk processing with database-driven queue and progress tracking.
- Fully featured tabbed settings UI with API toggles and cache management.
- Enhanced shortcode, widget, and block output with customizable templates.
- Comprehensive error logging and admin interface improvements.
- Secure AJAX and REST endpoints implemented.
- Optimized caching and performance controls.



### Support
For support, please visit the [GitHub repository](https://github.com/hellaz/HellaZ-SiteZ-Analyzer/) .

### Contributing
Contributions are welcome! If you'd like to contribute, please follow these steps:

1. Fork the repository. 
2. Create a new branch for your feature or bug fix.
3. Submit a pull request with a detailed description of your changes.

### License
This plugin is released under the GPLv2 or later license.
[Hellaz.Team](https://hellaz.net)  


