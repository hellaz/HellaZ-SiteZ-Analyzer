
# HellaZ-SiteZ-Analyzer

**Plugin Name : HellaZ SiteZ Analyzer**
**Description:** A WordPress plugin to for a comprehensive website analysis of metadata and Open Graph tags, including social media links & RSS feeds, Server & SSL certificate information, Contact information and more . It provides a Gutenberg block and shortcode for seamless integration.
**Tags:** metadata, social media, ssl, open graph, gutenberg, shortcode, widget, sitez, analysis, remote, fetch, extract, render, 
Requires at least: Wordpress 5.0 - Tested up to: 6.7.1

## Features

- **Gutenberg Block**: Add a block to analyze metadata for any URL.
- **Shortcode Support**: Use `[hsz_metadata url="https://example.com"]` for backward compatibility.
- **Widget Support**: Display metadata in sidebars or widgetized areas.
-**Metadata Detection**:  Extracts metadata, title, favicon, keywords, author, open graph tags from remote website.
- **Social Media Detection**: Extracts links for Facebook, Twitter/X, LinkedIn, YouTube, Instagram, WhatsApp, TikTok, Pinterest, Reddit, and Telegram.
- **Contact Info Detection **: Extracts emails, contact forms, address from target website
- **RSS Detection **: Retrieves RSS/Atom Feeds
- **Server & Security Info**: Retrieves Server location, Security & SSL details using APIs or direct parsing.
- **Caching**: Reduces server load by caching API responses.
- **Localization**: Supports multilingual websites with `.pot` files and translation functions.
- **Admin Settings Page**: Configure plugin options like API keys and caching duration.


## Installation

1. Upload the plugin to the `/wp-content/plugins/` directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Configure settings via the "SiteZ Analyzer" menu in the WordPress admin dashboard.

## Usage

### Gutenberg Block
1. Add the "HellaZ SiteZ Analyzer" block to your post or page.
2. Enter a valid URL and publish the post.

### Shortcode
Use the following shortcode to display metadata:
[hsz_metadata url="https://example.com"]

### Widget
1. Go to "Appearance > Widgets" in the WordPress admin dashboard.
2. Add the "HellaZ SiteZ Analyzer" widget to a sidebar.
3. Configure the widget settings (e.g., URL, font size, color).

## Advanced Features
### Admin Settings Page
The admin settings page allows you to configure advanced options, such as:
API keys for external services.
Caching duration for metadata extraction.
Export/import settings for easy migration.

### REST API Endpoint
The plugin exposes a REST API endpoint for external integrations:
Example:
curl -X GET "https://example.com/wp-json/hsz/v1/metadata/https://wordpress.org"

### Support
For support, please visit the [GitHub repository](https://github.com/hellaz/HellaZ-SiteZ-Analyzer/) .

### Contributing
Contributions are welcome! If you'd like to contribute, please follow these steps:

 - Fork the repository. 
 - Create a new branch for your feature or bug fix.
 - Submit a pull request with a detailed description of your changes.

### License
This plugin is released under the GPLv2 or later license.
[Hellaz.Team](https://hellaz.net)  
