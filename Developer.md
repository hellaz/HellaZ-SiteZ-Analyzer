# HellaZ SiteZ Analyzer — Developer Guide

**Version:** 2.1.0  
**Repository:** https://github.com/hellaz/HellaZ-SiteZAnalyzer

## Table of Contents

1. [Introduction](#introduction)  
2. [Plugin Architecture Overview](#plugin-architecture-overview)  
3. [Core Classes & Responsibilities](#core-classes--responsibilities)  
4. [Analysis Workflow & The Analyzer Class](#analysis-workflow--the-analyzer-class)  
5. [Contact Information Extraction](#contact-information-extraction)  
6. [Template System & Rendering](#template-system--rendering)  
7. [Admin Interface & Settings](#admin-interface--settings)  
8. [API Integrations](#api-integrations)  
9. [Caching & Performance](#caching--performance)  
10. [Security & Best Practices](#security--best-practices)  
11. [Development & Contribution Guidelines](#development--contribution-guidelines)  
12. [Testing & Debugging](#testing--debugging)  
13. [Changelog & Versioning](#changelog--versioning)  
14. [Resources & References](#resources--references)

## Introduction

HellaZ SiteZ Analyzer is a powerful, comprehensive WordPress plugin that analyzes any publicly accessible website URL to extract detailed metadata, SEO quality, social media profiles, performance metrics, security checks, technology stacks, and—most recently—rich **contact information**, such as emails, phone numbers, physical addresses, business hours, and contact form presence.

Since version **2.1.0**, the plugin’s architecture has become fully modular and extensible, with a focus on security, clean code standards, and performance optimizations. It integrates multiple third-party APIs, offers several customizable display templates, and provides a rich admin interface for controlling every aspect of analysis and presentation.

## Plugin Architecture Overview

The plugin follows a modular, namespace-based design with clear separation of concerns:

```
HellaZ SiteZ Analyzer
├── hellaz-sitez-analyzer.php (plugin bootstrap entry)
├── includes/
│   ├── class-hsz-core.php (plugin main orchestrator)
│   ├── class-hsz-analyzer.php (central analysis coordinator)
│   ├── class-hsz-metadata.php (metadata extraction)
│   ├── class-hsz-social-media.php (social profile detection)
│   ├── class-hsz-contact.php (contact info extraction)
│   ├── class-hsz-apianalysis.php (third-party API integrations)
│   ├── class-hsz-cache.php (caching infrastructure)
│   ├── class-hsz-util.php (utility functions)
│   ├── class-hsz-shortcode.php (shortcodes & widgets)
│   ├── class-hsz-admin.php (admin UI, AJAX handlers)
│   └── ... utility classes & helpers ...
├── templates/
│   ├── metadata-classic.php
│   ├── metadata-modern.php
│   ├── metadata-compact.php
│   └── partials/ (reusable UI sections)
├── assets/
│   ├── css/
│   ├── js/
│   └── images/ (template preview thumbnails)
└── languages/
    └── *.pot/mo files (translations)
```

This structure enables scalable development, easy maintenance, and enhanced testability.

## Core Classes & Responsibilities

| Class                   | Responsibility                                         |
| ----------------------- | ----------------------------------------------------- |
| `HSZ\Core`              | Bootstrap, hooks, resource loading, activation/deactivation logic |
| `HSZ\Analyzer`          | Central unified API for running all kinds of analysis; returns aggregated results |
| `HSZ\Metadata`          | Extracts titles, descriptions, images, structured data, feeds, etc. |
| `HSZ\SocialMedia`       | Detects and validates social media profiles across 30+ platforms |
| `HSZ\Contact`           | Extracts emails, phone numbers, physical addresses, business hours, contact forms, social contact links |
| `HSZ\APIAnalysis`       | Connects with third-party APIs such as VirusTotal, BuiltWith, URLScan.io to enhance analysis |
| `HSZ\Cache`             | Manages caching of analysis results for efficiency |
| `HSZ\Utils`             | General utility and helper functions (e.g., encryption, sanitization) |
| `HSZ\Shortcode`         | Provides shortcode and widget implementations |
| `HSZ\Admin`             | Builds and manages all admin pages and settings; handles AJAX requests |

## Analysis Workflow & The Analyzer Class

- The **`HSZ\Analyzer`** class is the unified processing engine. Its primary method `analyze(url, options)` triggers the various modules, conditionally based on settings and context.
- It gathers **metadata**, **social media links**, **contact info**, **performance metrics**, **security checks**, and **technology data**, and aggregates this into consistent arrays suitable for frontend and API consumption.
- The Analyzer respects settings such as enabling/disabling specific modules, timeout values, caching duration, and user-agent strings for HTTP requests.
- It supports error handling, logging, and result caching for performance.

## Contact Information Extraction

- HellaZ SiteZ Analyzer now features a **dedicated `HSZ\Contact`** class responsible for extracting website contact details.
- It identifies:
  - **Email addresses** across plain, obfuscated, mailto, and structured data formats.
  - **Phone numbers**, including country codes and common formatting patterns.
  - **Physical addresses** using regex-based heuristics and structured data parsing.
  - **Business hours** from textual and structured sources.
  - **Contact forms** presence with field type and validation analysis.
  - **Social contact profiles** for direct communication.
- Validation occurs through domain verification, pattern matching, and optional deep analysis.
- The extracted data become part of the aggregate analysis results supplied to templates.

## Template System & Rendering

- Output is generated using three professional templates: **Classic**, **Modern**, and **Compact**, selectable globally or overridden via shortcode/widget parameters.
- Templates employ partials under `/templates/partials/` to modularize UI components such as SEO info, feed lists, performance graphs, security statuses, and the new contact info blocks.
- All templates use WordPress recommended escaping functions (`esc_html()`, `esc_url()`, etc.) and follow accessibility and HTML standards.
- Template enhancements include visually rich layouts, dynamic score graphs, and direct links to social/contact profiles.
- The admin UI allows previewing templates with live thumbnails before selection.

## Admin Interface & Settings

- The plugin’s admin panel is organized into multiple tabs covering:
  - General settings (fallbacks, disclaimers)
  - API management (keys, toggles for multiple services)
  - Template selection with live previews
  - Caching controls and debug options
  - Performance and security settings
  - **Contact info extraction options** — granular toggles for each data type
- AJAX-powered controls include:
  - API key validation with real-time feedback
  - Cache clearing
  - Settings reset with nonce verification
- User inputs are sanitized and securely encrypted/decrypted as needed.

## API Integrations

- Out-of-the-box API connections include:
  - VirusTotal — for security threat scanning
  - BuiltWith — technology stack identification
  - URLScan.io — detailed security and reputation insights
  - Google Pagespeed and WebPageTest — for performance metrics
- APIs respect rate limits; caching minimizes unnecessary calls.
- API keys are stored encrypted in the database; users can enable/disable any service individually.

## Caching & Performance

- Results are cached intelligently to balance freshness and efficiency.
- Supports both **transient API caching** and **dedicated database tables** for large-scale datasets.
- Admin interface provides cache duration controls and debug toggling.
- Batch processing (bulk analysis) module supports deferred scanning of multiple URLs and manages workload.
- Compression options reduce storage footprint.

## Security & Best Practices

- All user inputs are rigorously sanitized using WordPress core functions.
- Outputs are escaped to prevent XSS and related vulnerabilities.
- Nonce verification is enforced on all form submissions and AJAX endpoints.
- Direct file access is blocked via defined constant checks.
- The plugin follows WordPress PHP coding standards for readability and maintainability.
- Compatible with PHP 7.4 and 8.0+.
- Logs errors responsibly without exposing sensitive information.

## Development & Contribution Guidelines

- Follow WordPress [Coding Standards](https://developer.wordpress.org/coding-standards/).
- Use namespaces, class autoloading (PSR-4), and dependency injection where possible.
- Write descriptions and docblocks for all new classes and methods.
- Ensure user inputs and outputs are always validated and escaped.
- Submit pull requests branched from master with descriptive commit messages.
- Write unit tests for new features and maintain overall test coverage.
- Document new settings, shortcodes, and user-facing features.

## Testing & Debugging

- Use sandboxed WordPress instances such as [TasteWP](https://tastewp.com/) or [InstaWP](https://instawp.io/) for safe testing.
- Test all admin features, especially AJAX-driven ones like API key validation and cache clearing.
- Use browser devtools and monitor console/network for JavaScript errors.
- Enable debug logging (`WP_DEBUG`) and check plugin-generated logs for errors.
- Validate front-end rendering against all three templates on desktop and mobile viewports.
- Conduct security reviews using tools like WPScan or third-party audits against exposed endpoints.

## Changelog & Versioning

**Versioning follows [Semantic Versioning](https://semver.org/):**

- **Major releases**: breaking changes, architecture overhaul.
- **Minor releases**: new features such as contact extraction, new templates.
- **Patch releases**: bug fixes, performance improvements.

### Recent notable versions:

- **v2.1.0**  
  Major new feature: contact information extraction, admin UI enhancements, unified analyzer core, API integration improvements, enhanced templates.

- **v2.0.0**  
  Ground-up rewrite with modular design, multi-API support, improved caching and batch processing, comprehensive performance and security insights.

## Resources & References

- [WordPress Plugin Developer Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Security Guidelines](https://developer.wordpress.org/plugins/security/)
- [WordPress REST API Handbook](https://developer.wordpress.org/rest-api/)
- [WordPress Coding Standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/)
- [Internationalization in Plugins](https://developer.wordpress.org/plugins/internationalization/)

*Thank you for contributing to the HellaZ SiteZ Analyzer project!*

**Note:** This Developer Guide is updated alongside each plugin release. Please consult it regularly for the latest development practices and insights.

[1] https://github.com/hellaz/HellaZ-SiteZ-Analyzer/blob/main/Developer.md