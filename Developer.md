# Developer Documentation for HellaZ SiteZ Analyzer

This document provides technical details for developers who want to extend or customize the HellaZ SiteZ Analyzer plugin.

## Architecture

The plugin is modular, with each class responsible for a specific feature or functionality. Dependencies are managed through the `Core` class (`class-hsz-core.php`), which initializes all components.

### Key Files and Their Roles
- **`hellaz-sitez-analyzer.php`**: Main plugin file. Initializes the plugin and enqueues assets.
- **`uninstall.php`**: Cleans up plugin data (e.g., transients) upon uninstallation.
- **`includes/class-hsz-core.php`**: Initializes all plugin components.
- **`includes/class-hsz-gutenberg.php`**: Defines the Gutenberg block (`hsz/metadata-block`).
- **`includes/class-hsz-metadata.php`**: Extracts metadata from URLs (e.g., Open Graph tags, social media links, SSL info).
- **`includes/class-hsz-security.php`**: Retrieves SSL certificate information using APIs or direct parsing.
- **`includes/class-hsz-apimanager.php`**: Manages API requests and caching.
- **`includes/class-hsz-shortcode.php`**: Implements the `[hsz_metadata]` shortcode for backward compatibility.
- **`includes/class-hsz-social-media.php`**: Detects and extracts social media links (e.g., Facebook, Twitter/X, LinkedIn, YouTube).
- **`includes/class-hsz-settings.php`**: Provides an admin settings page for configuring plugin options.
- **`includes/class-hsz-widget.php`**: Implements a widget for displaying metadata in sidebars.
- **`templates/metadata-template.php`**: Renders metadata in a structured format for frontend display.

## Hooks and Filters

### Actions
- `hsz_before_render_metadata`: Fires before rendering metadata.
- `hsz_after_render_metadata`: Fires after rendering metadata.

### Filters
- `hsz_extracted_metadata`: Allows modifying extracted metadata before rendering.
  ```php
  add_filter('hsz_extracted_metadata', function ($metadata) {
      $metadata['custom_field'] = 'Custom Value';
      return $metadata;
  });

## Extending Functionality ##
Adding New Platforms
To add support for a new social media platform, update the detect_social_media_links() method in class-hsz-social-media.php:

$tiktok_links = $xpath->query('//a[contains(@href, "tiktok.com")]');
Customizing Templates
To customize the frontend output, copy templates/metadata-template.php to your theme directory and modify it as needed. For example:

Copy the file to wp-content/themes/your-theme/hsz/metadata-template.php.
Modify the template to suit your needs.
Overriding Default Settings
You can override default settings by filtering the hsz_default_settings hook:

add_filter('hsz_default_settings', function ($defaults) {
    $defaults['cache_duration'] = 3600; // Change cache duration to 1 hour
    return $defaults;
});

REST API Endpoint
The plugin exposes a REST API endpoint for external integrations:

GET /wp-json/hsz/v1/metadata/{url}
Example:

curl -X GET "https://example.com/wp-json/hsz/v1/metadata/https://wordpress.org"
Support
For support, please visit the GitHub repository .

Contributing
Contributions are welcome! If you'd like to contribute, please follow these steps:

Fork the repository.
Create a new branch for your feature or bug fix.
Submit a pull request with a detailed description of your changes.
License
This plugin is released under the GPLv2 or later license
