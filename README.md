# HellaZ-SiteZ-Analyzer

Plugin Name : HellaZ SiteZ Analyzer
Description
A WordPress plugin to analyze website metadata, including social media links, SSL certificate information, and Open Graph tags. It provides a Gutenberg block and shortcode for seamless integration.

## Features

- **Gutenberg Block**: Add a block to analyze metadata for any URL.
- **Shortcode Support**: Use `[hsz_metadata url="https://example.com"]` for backward compatibility.
- **Social Media Detection**: Extracts links for Facebook, Twitter/X, LinkedIn, YouTube, Instagram, WhatsApp, TikTok, Pinterest, Reddit, and Telegram.
- **SSL Certificate Info**: Retrieves SSL details using APIs or direct parsing.
- **Caching**: Reduces server load by caching API responses.
- **Localization**: Supports multilingual websites with `.pot` files and translation functions.
- **Admin Settings Page**: Configure plugin options like API keys and caching duration.
- **Widget Support**: Display metadata in sidebars or widgetized areas.

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

### Advanced Features
#### Admin Settings Page
The admin settings page allows you to configure advanced options, such as:
API keys for external services.
Caching duration for metadata extraction.
Export/import settings for easy migration.

#### REST API Endpoint
The plugin exposes a REST API endpoint for external integrations:
Example:
curl -X GET "https://example.com/wp-json/hsz/v1/metadata/https://wordpress.org"

### Support
For support, please visit the GitHub repository .

### Contributing
Contributions are welcome! If you'd like to contribute, please follow these steps:

### Fork the repository.
Create a new branch for your feature or bug fix.
Submit a pull request with a detailed description of your changes.

### License
This plugin is released under the GPLv2 or later license.
