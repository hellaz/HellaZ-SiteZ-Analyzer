# Developer Guide – HellaZ SiteZ Analyzer

## Table of Contents

- Overview
- Architecture
- Core Classes
- Data Flow
- Adding New Features
- Hooks and Filters
- Translation and i18n
- Performance and Caching
- Security Considerations
- Templates and Theming
- Bulk Processing
- API Integrations
- Testing and Debugging

## Overview

HellaZ SiteZ Analyzer is modular WordPress plugin designed to analyze websites by fetching metadata, social links, security data, and technology info. It provides multiple UI entry points (shortcode, widgets, Gutenberg blocks) and admin tooling for configuration, bulk processing, and logs.

## Architecture

- **Bootstrap:** `hellaz-sitez-analyzer.php` initializes the plugin, defines constants and autoloading, and triggers `HSZ\Core::init()`.
- **Core Orchestration:** `class-hsz-core.php` loads all components, registers hooks, and handles activation/deactivation tasks.
- **Settings:** Provides admin UI and persistent options with tabbed organization.
- **Metadata:** Extracts and parses metadata from fetched HTML and APIs.
- **Social Media:** Parses social profile URLs from HTML and schema data.
- **Bulk Processing:** Handles queued multiple URL analyses with database tracking.
- **AJAX:** Supports asynchronous requests via secure handlers.
- **Templates:** PHP partials provide flexible display of analysis results.
- **Utilities:** Helpers for caching, encryption, logging, and performance measurement.

## Core Classes

- `Core` – Main plugin bootstrap.
- `Settings` – Admin settings and forms.
- `Metadata` – Metadata extraction logic.
- `SocialMedia` – Social profile detection.
- `BulkProcessor` – Batch job management.
- `AjaxHandler` – Secure AJAX endpoints.
- `AdminLogs` – Error and event log management.
- `Utils` – Helper methods.
- `Widget` – sidebar widget.
- `Shortcode` – shortcode handler.
- `Gutenberg` – block registration and rendering.

## Data Flow

1. User triggers analysis (shortcode, widget, block, or bulk).
2. `Metadata` class fetches URL content, extracts metadata, caches results.
3. `SocialMedia` parses social links from content.
4. Output rendered per selected template.
5. Bulk processing allows queued URL analysis with progress stored in DB tables.

## Adding Features

- Place new classes in `includes/`.
- Register in `Core->load_dependencies()`.
- Add new admin UI tabs in `Settings`.
- Utilize caching via `Utils`.
- Follow WordPress best practices for hooks, sanitization, and security.

## Hooks and Filters

- `init` for shortcodes, blocks, REST registering.
- `widgets_init` for widget registration.
- `admin_menu` for settings UI.
- AJAX actions protected by nonce (`hsz_analyze_nonce`) and capability checks.

## Translation and i18n

- All user-facing strings are wrapped in `__()`, `_e()`, `_x()`, etc.
- POT file located in `/languages/hellaz-sitez-analyzer.pot`.
- Use `load_plugin_textdomain()` on plugin init.

## Performance and Caching

- Uses WP transients for result caching.
- `Utils` provides cache get/set and clearing logic.
- Cache duration controlled via admin.
- Cache inspector displays current cache keys to admins.

## Security Considerations

- All inputs sanitized via `sanitize_text_field()`, `esc_url_raw()`, etc.
- All outputs escaped with `esc_html()`, `esc_url()`.
- AJAX endpoints validate nonces and user capabilities.
- Sensitive data (API keys) encrypted at rest.

## Templates and Theming

- Multiple output templates (classic, modern, compact).
- Templates located in `/templates/`.
- Templates receive sanitized data; can be overridden by developers.

## Bulk Processing

- Uses dedicated DB tables for batch and URL tracking.
- Progress and errors recorded for admin status display.
- Can be extended with WP Cron or manual triggers.

## API Integrations

- VirusTotal, BuiltWith, URLScan.io keys saved securely.
- Extend `Metadata` class to add API-enhanced data fetching.

## Testing and Debugging

- Use WP_DEBUG and log monitoring.
- Use browser console and network inspector for AJAX.
- Manual and unit testing recommended for new features.
- Use `Utils::log_error()` for standardized logging.

---

For detailed code examples and development best practices, refer to `/docs` folder and inline code comments.
