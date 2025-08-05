<?php
/**
 * Contact Information Extraction for HellaZ SiteZ Analyzer
 *
 * This class handles comprehensive contact information extraction including
 * emails, phone numbers, physical addresses, contact forms, and business
 * information from website HTML content.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.0.2
 */

namespace HSZ;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Contact
 *
 * Extracts comprehensive contact information from website HTML.
 */
class Contact {

    /**
     * URL being analyzed
     *
     * @var string
     */
    private $url;

    /**
     * HTML content
     *
     * @var string
     */
    private $html;

    /**
     * DOM document
     *
     * @var \DOMDocument
     */
    private $dom;

    /**
     * XPath instance
     *
     * @var \DOMXPath
     */
    private $xpath;

    /**
     * Contact extraction patterns
     *
     * @var array
     */
    private $patterns = [];

    /**
     * Constructor - Initialize patterns and settings
     */
    public function __construct() {
        $this->init_patterns();
    }

    /**
     * Initialize extraction patterns
     */
    private function init_patterns(): void {
        $this->patterns = [
            'email' => [
                'basic' => '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/',
                'obfuscated' => '/[a-zA-Z0-9._%+-]+\s*\[?at\]?\s*[a-zA-Z0-9.-]+\s*\[?dot\]?\s*[a-zA-Z]{2,}/i',
                'encoded' => '/mailto:([^"\'>\s]+)/',
            ],
            'phone' => [
                'us_international' => '/(\+?1[-.\s]?)?\(?([0-9]{3})\)?[-.\s]?([0-9]{3})[-.\s]?([0-9]{4})/',
                'international' => '/\+?[1-9]\d{0,3}[-.\s]?\d{1,4}[-.\s]?\d{1,4}[-.\s]?\d{1,9}/',
                'extensions' => '/(ext\.?|extension|x)\s*:?\s*(\d+)/i',
            ],
            'address' => [
                'us_format' => '/\d+\s+[A-Za-z0-9\s,.-]+\s+(Street|St|Avenue|Ave|Road|Rd|Boulevard|Blvd|Lane|Ln|Drive|Dr|Court|Ct|Place|Pl|Way|Circle|Cir|Parkway|Pkwy)\s*,?\s*[A-Za-z\s]+,\s*[A-Z]{2}\s*\d{5}(-\d{4})?/i',
                'generic' => '/\d+\s+[A-Za-z0-9\s,.-]+\s+(Street|St|Avenue|Ave|Road|Rd|Boulevard|Blvd|Lane|Ln|Drive|Dr|Court|Ct|Place|Pl|Way|Circle|Cir|Parkway|Pkwy|Close|Crescent|Square)/i',
                'po_box' => '/P\.?O\.?\s*Box\s*\d+/i',
            ],
            'business_hours' => [
                'standard' => '/(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday|Mon|Tue|Wed|Thu|Fri|Sat|Sun)\s*:?\s*(\d{1,2}:\d{2}\s*(AM|PM)?|\d{1,2}\s*(AM|PM))\s*-\s*(\d{1,2}:\d{2}\s*(AM|PM)?|\d{1,2}\s*(AM|PM))/i',
                'closed' => '/(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday|Mon|Tue|Wed|Thu|Fri|Sat|Sun)\s*:?\s*(Closed|CLOSED)/i',
            ]
        ];
    }

    /**
     * Extract comprehensive contact information from HTML content
     *
     * @param string $html HTML content
     * @param string $url Website URL
     * @param array $options Extraction options
     * @return array Contact information results
     */
    public function extract_contact_info( string $html, string $url, array $options = [] ): array {
        if ( empty( $html ) ) {
            return [ 'error' => __( 'HTML content is empty and cannot be analyzed for contact information.', 'hellaz-sitez-analyzer' ) ];
        }

        $this->url = $url;
        $this->html = $html;

        // Set default options
        $options = wp_parse_args( $options, [
            'extract_emails' => get_option( 'hsz_contact_extract_emails', true ),
            'extract_phones' => get_option( 'hsz_contact_extract_phones', true ),
            'extract_addresses' => get_option( 'hsz_contact_extract_addresses', true ),
            'extract_forms' => get_option( 'hsz_contact_extract_forms', true ),
            'extract_social' => get_option( 'hsz_contact_extract_social', true ),
            'extract_business_hours' => get_option( 'hsz_contact_extract_hours', true ),
            'validate_info' => get_option( 'hsz_contact_validate', true ),
            'deep_analysis' => get_option( 'hsz_contact_deep_analysis', false ),
        ]);

        // Initialize DOM and XPath
        if ( ! $this->init_dom() ) {
            return [ 'error' => __( 'Failed to parse HTML content for contact extraction.', 'hellaz-sitez-analyzer' ) ];
        }

        Utils::start_timer( 'contact_extraction' );

        $contact_info = [
            'emails' => [],
            'phones' => [],
            'addresses' => [],
            'contact_forms' => [],
            'social_profiles' => [],
            'business_hours' => [],
            'location_info' => [],
            'contact_score' => 0,
            'contact_grade' => 'F',
            'recommendations' => [],
            'extraction_time' => 0
        ];

        try {
            // Extract email addresses
            if ( $options['extract_emails'] ) {
                $contact_info['emails'] = $this->extract_emails( $options );
            }

            // Extract phone numbers
            if ( $options['extract_phones'] ) {
                $contact_info['phones'] = $this->extract_phones( $options );
            }

            // Extract physical addresses
            if ( $options['extract_addresses'] ) {
                $contact_info['addresses'] = $this->extract_addresses( $options );
            }

            // Extract contact forms
            if ( $options['extract_forms'] ) {
                $contact_info['contact_forms'] = $this->extract_contact_forms( $options );
            }

            // Extract social media contact info
            if ( $options['extract_social'] ) {
                $contact_info['social_profiles'] = $this->extract_social_contact_info( $options );
            }

            // Extract business hours
            if ( $options['extract_business_hours'] ) {
                $contact_info['business_hours'] = $this->extract_business_hours( $options );
            }

            // Extract location/geographic information
            $contact_info['location_info'] = $this->extract_location_info( $options );

            // Calculate contact score and generate recommendations
            $contact_info = $this->calculate_contact_score( $contact_info );
            $contact_info['recommendations'] = $this->generate_contact_recommendations( $contact_info );

        } catch ( \Exception $e ) {
            Utils::log_error( 'Contact extraction error for ' . $url . ': ' . $e->getMessage(), __FILE__, __LINE__ );
            $contact_info['error'] = __( 'An error occurred during contact information extraction.', 'hellaz-sitez-analyzer' );
        }

        $contact_info['extraction_time'] = Utils::stop_timer( 'contact_extraction' );

        return $contact_info;
    }

    /**
     * Initialize DOM and XPath objects
     *
     * @return bool Success status
     */
    private function init_dom(): bool {
        $this->dom = new \DOMDocument();
        
        // Suppress warnings for malformed HTML
        $old_setting = libxml_use_internal_errors( true );
        libxml_clear_errors();
        
        $success = @$this->dom->loadHTML( '<?xml encoding="UTF-8">' . $this->html );
        
        // Restore error reporting
        libxml_use_internal_errors( $old_setting );

        if ( $success ) {
            $this->xpath = new \DOMXPath( $this->dom );
            return true;
        }

        return false;
    }

    /**
     * Extract email addresses from HTML
     *
     * @param array $options Extraction options
     * @return array Extracted email addresses
     */
    private function extract_emails( array $options ): array {
        $emails = [];
        $found_emails = [];

        // Extract from mailto links
        $mailto_links = $this->xpath->query( '//a[starts-with(@href, "mailto:")]' );
        foreach ( $mailto_links as $link ) {
            $href = $link->getAttribute( 'href' );
            if ( preg_match( '/mailto:([^?]+)/', $href, $matches ) ) {
                $email = trim( $matches[1] );
                if ( $this->is_valid_email( $email ) ) {
                    $found_emails[$email] = [
                        'email' => $email,
                        'source' => 'mailto_link',
                        'context' => trim( $link->nodeValue ),
                        'validated' => true
                    ];
                }
            }
        }

        // Extract from text content using patterns
        foreach ( $this->patterns['email'] as $pattern_name => $pattern ) {
            if ( preg_match_all( $pattern, $this->html, $matches, PREG_SET_ORDER ) ) {
                foreach ( $matches as $match ) {
                    $email = $this->clean_email( $match[0] );
                    if ( $this->is_valid_email( $email ) && ! isset( $found_emails[$email] ) ) {
                        $found_emails[$email] = [
                            'email' => $email,
                            'source' => 'content_' . $pattern_name,
                            'context' => $this->get_email_context( $match[0] ),
                            'validated' => $options['validate_info'] ? $this->validate_email_domain( $email ) : false
                        ];
                    }
                }
            }
        }

        // Extract from structured data
        $structured_emails = $this->extract_emails_from_structured_data();
        foreach ( $structured_emails as $email ) {
            if ( ! isset( $found_emails[$email['email']] ) ) {
                $found_emails[$email['email']] = $email;
            }
        }

        return array_values( $found_emails );
    }

    /**
     * Extract phone numbers from HTML
     *
     * @param array $options Extraction options
     * @return array Extracted phone numbers
     */
    private function extract_phones( array $options ): array {
        $phones = [];
        $found_phones = [];

        // Extract from tel: links
        $tel_links = $this->xpath->query( '//a[starts-with(@href, "tel:")]' );
        foreach ( $tel_links as $link ) {
            $href = $link->getAttribute( 'href' );
            if ( preg_match( '/tel:([^?]+)/', $href, $matches ) ) {
                $phone = $this->clean_phone( $matches[1] );
                $formatted = $this->format_phone( $phone );
                if ( $formatted ) {
                    $found_phones[$phone] = [
                        'phone' => $phone,
                        'formatted' => $formatted,
                        'source' => 'tel_link',
                        'context' => trim( $link->nodeValue ),
                        'type' => $this->determine_phone_type( $phone )
                    ];
                }
            }
        }

        // Extract from text content using patterns
        foreach ( $this->patterns['phone'] as $pattern_name => $pattern ) {
            if ( preg_match_all( $pattern, $this->html, $matches, PREG_SET_ORDER ) ) {
                foreach ( $matches as $match ) {
                    $phone = $this->clean_phone( $match[0] );
                    $formatted = $this->format_phone( $phone );
                    if ( $formatted && ! isset( $found_phones[$phone] ) ) {
                        $found_phones[$phone] = [
                            'phone' => $phone,
                            'formatted' => $formatted,
                            'source' => 'content_' . $pattern_name,
                            'context' => $this->get_phone_context( $match[0] ),
                            'type' => $this->determine_phone_type( $phone )
                        ];
                    }
                }
            }
        }

        return array_values( $found_phones );
    }

    /**
     * Extract physical addresses from HTML
     *
     * @param array $options Extraction options
     * @return array Extracted addresses
     */
    private function extract_addresses( array $options ): array {
        $addresses = [];
        $found_addresses = [];

        // Extract from structured data first (most reliable)
        $structured_addresses = $this->extract_addresses_from_structured_data();
        foreach ( $structured_addresses as $addr ) {
            $found_addresses[$addr['raw']] = $addr;
        }

        // Extract using address patterns
        foreach ( $this->patterns['address'] as $pattern_name => $pattern ) {
            if ( preg_match_all( $pattern, $this->html, $matches, PREG_SET_ORDER ) ) {
                foreach ( $matches as $match ) {
                    $address = trim( $match[0] );
                    if ( ! isset( $found_addresses[$address] ) ) {
                        $found_addresses[$address] = [
                            'raw' => $address,
                            'formatted' => $this->format_address( $address ),
                            'source' => 'content_' . $pattern_name,
                            'type' => $this->determine_address_type( $address ),
                            'components' => $this->parse_address_components( $address )
                        ];
                    }
                }
            }
        }

        // Look for address-related elements
        $address_elements = $this->xpath->query( '//address | //*[contains(@class, "address") or contains(@class, "location")]' );
        foreach ( $address_elements as $element ) {
            $address_text = trim( $element->nodeValue );
            if ( strlen( $address_text ) > 10 && ! isset( $found_addresses[$address_text] ) ) {
                $found_addresses[$address_text] = [
                    'raw' => $address_text,
                    'formatted' => $this->format_address( $address_text ),
                    'source' => 'address_element',
                    'type' => 'full',
                    'components' => $this->parse_address_components( $address_text )
                ];
            }
        }

        return array_values( $found_addresses );
    }

    /**
     * Extract contact forms from HTML
     *
     * @param array $options Extraction options
     * @return array Found contact forms
     */
    private function extract_contact_forms( array $options ): array {
        $forms = [];
        
        $form_elements = $this->xpath->query( '//form' );
        foreach ( $form_elements as $form ) {
            $form_html = $this->dom->saveHTML( $form );
            
            // Check if this looks like a contact form
            if ( $this->is_contact_form( $form_html ) ) {
                $form_info = [
                    'type' => 'contact_form',
                    'method' => strtoupper( $form->getAttribute( 'method' ) ?: 'GET' ),
                    'action' => $form->getAttribute( 'action' ),
                    'fields' => $this->analyze_form_fields( $form ),
                    'has_validation' => $this->has_form_validation( $form_html ),
                    'has_captcha' => $this->has_captcha( $form_html ),
                    'form_score' => 0
                ];

                // Calculate form quality score
                $form_info['form_score'] = $this->calculate_form_score( $form_info );
                $forms[] = $form_info;
            }
        }

        return $forms;
    }

    /**
     * Extract social media contact information
     *
     * @param array $options Extraction options
     * @return array Social contact information
     */
    private function extract_social_contact_info( array $options ): array {
        $social_contact = [];
        
        // Look for social media links that could be used for contact
        $contact_social_patterns = [
            'facebook' => '/facebook\.com\/([^\/\s"\'<>]+)/i',
            'twitter' => '/twitter\.com\/([^\/\s"\'<>]+)/i',
            'instagram' => '/instagram\.com\/([^\/\s"\'<>]+)/i',
            'linkedin' => '/linkedin\.com\/(in|company)\/([^\/\s"\'<>]+)/i',
            'whatsapp' => '/wa\.me\/([0-9]+)/i',
        ];

        foreach ( $contact_social_patterns as $platform => $pattern ) {
            if ( preg_match_all( $pattern, $this->html, $matches, PREG_SET_ORDER ) ) {
                foreach ( $matches as $match ) {
                    $social_contact[] = [
                        'platform' => $platform,
                        'url' => $match[0],
                        'username' => $match[1] ?? '',
                        'context' => 'contact_purpose'
                    ];
                }
            }
        }

        return $social_contact;
    }

    /**
     * Extract business hours from HTML
     *
     * @param array $options Extraction options
     * @return array Business hours information
     */
    private function extract_business_hours( array $options ): array {
        $hours = [];
        
        foreach ( $this->patterns['business_hours'] as $pattern_name => $pattern ) {
            if ( preg_match_all( $pattern, $this->html, $matches, PREG_SET_ORDER ) ) {
                foreach ( $matches as $match ) {
                    $day = $match[1];
                    $time_info = $pattern_name === 'closed' ? 'Closed' : $match[2] . '-' . $match[5];
                    
                    $hours[] = [
                        'day' => $this->normalize_day_name( $day ),
                        'hours' => $time_info,
                        'raw' => $match[0],
                        'source' => $pattern_name
                    ];
                }
            }
        }

        // Look for structured data business hours
        $structured_hours = $this->extract_hours_from_structured_data();
        $hours = array_merge( $hours, $structured_hours );

        return $hours;
    }

    /**
     * Extract location/geographic information
     *
     * @param array $options Extraction options
     * @return array Location information
     */
    private function extract_location_info( array $options ): array {
        $location = [];

        // Look for coordinates in meta tags or structured data
        $geo_meta = $this->xpath->query( '//meta[@name="geo.position" or @name="ICBM" or @name="geo.region" or @name="geo.placename"]' );
        foreach ( $geo_meta as $meta ) {
            $name = $meta->getAttribute( 'name' );
            $content = $meta->getAttribute( 'content' );
            $location['meta_' . str_replace( '.', '_', $name )] = $content;
        }

        // Look for Google Maps embeds
        $map_iframes = $this->xpath->query( '//iframe[contains(@src, "google.com/maps") or contains(@src, "maps.google.com")]' );
        if ( $map_iframes->length > 0 ) {
            $location['has_google_maps'] = true;
            $location['map_embeds'] = [];
            foreach ( $map_iframes as $iframe ) {
                $location['map_embeds'][] = $iframe->getAttribute( 'src' );
            }
        }

        return $location;
    }

    /**
     * Helper method to validate email address
     *
     * @param string $email Email address to validate
     * @return bool True if email is valid
     */
    private function is_valid_email( string $email ): bool {
        return filter_var( $email, FILTER_VALIDATE_EMAIL ) !== false;
    }

    /**
     * Clean and normalize email address
     *
     * @param string $email Raw email string
     * @return string Cleaned email
     */
    private function clean_email( string $email ): string {
        // Handle obfuscated emails
        $email = str_replace( [' at ', '[at]', ' dot ', '[dot]'], ['@', '@', '.', '.'], $email );
        $email = preg_replace( '/\s+/', '', $email );
        return strtolower( trim( $email ) );
    }

    /**
     * Get context around email for better understanding
     *
     * @param string $email_match Email match
     * @return string Context information
     */
    private function get_email_context( string $email_match ): string {
        $position = strpos( $this->html, $email_match );
        if ( $position === false ) {
            return '';
        }

        $start = max( 0, $position - 50 );
        $length = min( 100, strlen( $this->html ) - $start );
        $context = substr( $this->html, $start, $length );
        
        return strip_tags( $context );
    }

    /**
     * Clean and normalize phone number
     *
     * @param string $phone Raw phone string
     * @return string Cleaned phone number
     */
    private function clean_phone( string $phone ): string {
        return preg_replace( '/[^\d+]/', '', $phone );
    }

    /**
     * Format phone number for display
     *
     * @param string $phone Cleaned phone number
     * @return string|false Formatted phone or false if invalid
     */
    private function format_phone( string $phone ): string {
        // Basic US phone formatting
        if ( preg_match( '/^1?(\d{3})(\d{3})(\d{4})$/', $phone, $matches ) ) {
            return sprintf( '(%s) %s-%s', $matches[1], $matches[2], $matches[3] );
        }
        
        // International format
        if ( strlen( $phone ) >= 10 ) {
            return $phone;
        }
        
        return '';
    }

    /**
     * Determine phone type (mobile, landline, toll-free, etc.)
     *
     * @param string $phone Phone number
     * @return string Phone type
     */
    private function determine_phone_type( string $phone ): string {
        // US toll-free numbers
        if ( preg_match( '/^1?(800|888|877|866|855|844|833|822)/', $phone ) ) {
            return 'toll_free';
        }
        
        // This would need more sophisticated logic for accurate detection
        return 'unknown';
    }

    /**
     * Get context around phone number
     *
     * @param string $phone_match Phone match
     * @return string Context information
     */
    private function get_phone_context( string $phone_match ): string {
        return $this->get_email_context( $phone_match ); // Reuse email context logic
    }

    /**
     * Format address for better display
     *
     * @param string $address Raw address
     * @return string Formatted address
     */
    private function format_address( string $address ): string {
        // Basic address formatting - remove extra whitespace
        return preg_replace( '/\s+/', ' ', trim( $address ) );
    }

    /**
     * Determine address type
     *
     * @param string $address Address string
     * @return string Address type
     */
    private function determine_address_type( string $address ): string {
        if ( preg_match( '/P\.?O\.?\s*Box/i', $address ) ) {
            return 'po_box';
        }
        
        if ( preg_match( '/\d+\s+[A-Za-z]/', $address ) ) {
            return 'street';
        }
        
        return 'general';
    }

    /**
     * Parse address into components
     *
     * @param string $address Address string
     * @return array Address components
     */
    private function parse_address_components( string $address ): array {
        // This would be a complex parser - basic implementation
        $components = [];
        
        // Extract ZIP code
        if ( preg_match( '/\b\d{5}(-\d{4})?\b/', $address, $zip_match ) ) {
            $components['zip'] = $zip_match[0];
        }
        
        // Extract state (US format)
        if ( preg_match( '/\b[A-Z]{2}\b/', $address, $state_match ) ) {
            $components['state'] = $state_match[0];
        }
        
        return $components;
    }

    /**
     * Check if form is likely a contact form
     *
     * @param string $form_html Form HTML
     * @return bool True if contact form
     */
    private function is_contact_form( string $form_html ): bool {
        $contact_indicators = [
            'contact', 'inquiry', 'message', 'feedback', 'support',
            'get in touch', 'reach out', 'email us', 'send message'
        ];
        
        $form_text = strtolower( $form_html );
        foreach ( $contact_indicators as $indicator ) {
            if ( strpos( $form_text, $indicator ) !== false ) {
                return true;
            }
        }
        
        // Check for typical contact form fields
        $contact_fields = ['email', 'name', 'message', 'subject', 'phone'];
        $field_count = 0;
        foreach ( $contact_fields as $field ) {
            if ( strpos( $form_text, $field ) !== false ) {
                $field_count++;
            }
        }
        
        return $field_count >= 2;
    }

    /**
     * Analyze form fields
     *
     * @param \DOMElement $form Form element
     * @return array Form field analysis
     */
    private function analyze_form_fields( \DOMElement $form ): array {
        $fields = [];
        
        $input_elements = $this->xpath->query( './/input | .//textarea | .//select', $form );
        foreach ( $input_elements as $input ) {
            $type = $input->getAttribute( 'type' ) ?: $input->tagName;
            $name = $input->getAttribute( 'name' );
            $required = $input->hasAttribute( 'required' );
            
            $fields[] = [
                'type' => $type,
                'name' => $name,
                'required' => $required,
                'placeholder' => $input->getAttribute( 'placeholder' )
            ];
        }
        
        return $fields;
    }

    /**
     * Check if form has validation
     *
     * @param string $form_html Form HTML
     * @return bool True if has validation
     */
    private function has_form_validation( string $form_html ): bool {
        return strpos( $form_html, 'required' ) !== false || 
               strpos( $form_html, 'validate' ) !== false;
    }

    /**
     * Check if form has CAPTCHA
     *
     * @param string $form_html Form HTML
     * @return bool True if has CAPTCHA
     */
    private function has_captcha( string $form_html ): bool {
        $captcha_indicators = ['recaptcha', 'captcha', 'hcaptcha'];
        $form_text = strtolower( $form_html );
        
        foreach ( $captcha_indicators as $indicator ) {
            if ( strpos( $form_text, $indicator ) !== false ) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Calculate form quality score
     *
     * @param array $form_info Form information
     * @return int Form score (0-100)
     */
    private function calculate_form_score( array $form_info ): int {
        $score = 0;
        
        // Has essential fields
        $field_names = array_column( $form_info['fields'], 'name' );
        $essential_fields = ['name', 'email', 'message'];
        foreach ( $essential_fields as $field ) {
            if ( in_array( $field, $field_names ) ) {
                $score += 20;
            }
        }
        
        // Has validation
        if ( $form_info['has_validation'] ) {
            $score += 20;
        }
        
        // Has CAPTCHA (anti-spam)
        if ( $form_info['has_captcha'] ) {
            $score += 20;
        }
        
        return min( 100, $score );
    }

    /**
     * Normalize day name
     *
     * @param string $day Day name
     * @return string Normalized day name
     */
    private function normalize_day_name( string $day ): string {
        $day_map = [
            'mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday',
            'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday'
        ];
        
        $day_lower = strtolower( substr( $day, 0, 3 ) );
        return $day_map[$day_lower] ?? ucfirst( $day );
    }

    /**
     * Extract emails from structured data (JSON-LD, microdata)
     *
     * @return array Structured data emails
     */
    private function extract_emails_from_structured_data(): array {
        $emails = [];
        
        // Look for JSON-LD structured data
        $json_scripts = $this->xpath->query( '//script[@type="application/ld+json"]' );
        foreach ( $json_scripts as $script ) {
            $json_content = trim( $script->nodeValue );
            $data = json_decode( $json_content, true );
            
            if ( json_last_error() === JSON_ERROR_NONE ) {
                $emails = array_merge( $emails, $this->extract_emails_from_json_ld( $data ) );
            }
        }
        
        return $emails;
    }

    /**
     * Extract emails from JSON-LD data
     *
     * @param array $data JSON-LD data
     * @return array Found emails
     */
    private function extract_emails_from_json_ld( array $data ): array {
        $emails = [];
        
        // Recursive search for email fields
        if ( is_array( $data ) ) {
            foreach ( $data as $key => $value ) {
                if ( is_string( $key ) && ( $key === 'email' || strpos( $key, 'email' ) !== false ) && is_string( $value ) ) {
                    if ( $this->is_valid_email( $value ) ) {
                        $emails[] = [
                            'email' => $value,
                            'source' => 'structured_data',
                            'context' => 'JSON-LD',
                            'validated' => true
                        ];
                    }
                } elseif ( is_array( $value ) ) {
                    $emails = array_merge( $emails, $this->extract_emails_from_json_ld( $value ) );
                }
            }
        }
        
        return $emails;
    }

    /**
     * Extract addresses from structured data
     *
     * @return array Structured data addresses
     */
    private function extract_addresses_from_structured_data(): array {
        $addresses = [];
        
        // Look for address information in structured data
        $json_scripts = $this->xpath->query( '//script[@type="application/ld+json"]' );
        foreach ( $json_scripts as $script ) {
            $json_content = trim( $script->nodeValue );
            $data = json_decode( $json_content, true );
            
            if ( json_last_error() === JSON_ERROR_NONE ) {
                $addresses = array_merge( $addresses, $this->extract_addresses_from_json_ld( $data ) );
            }
        }
        
        return $addresses;
    }

    /**
     * Extract addresses from JSON-LD data
     *
     * @param array $data JSON-LD data
     * @return array Found addresses
     */
    private function extract_addresses_from_json_ld( array $data ): array {
        $addresses = [];
        
        // Look for address objects
        if ( isset( $data['address'] ) ) {
            $address_data = $data['address'];
            if ( is_array( $address_data ) ) {
                $address_string = $this->build_address_from_components( $address_data );
                if ( $address_string ) {
                    $addresses[] = [
                        'raw' => $address_string,
                        'formatted' => $address_string,
                        'source' => 'structured_data',
                        'type' => 'business',
                        'components' => $address_data
                    ];
                }
            }
        }
        
        return $addresses;
    }

    /**
     * Build address string from components
     *
     * @param array $components Address components
     * @return string Address string
     */
    private function build_address_from_components( array $components ): string {
        $parts = [];
        
        $address_fields = ['streetAddress', 'addressLocality', 'addressRegion', 'postalCode', 'addressCountry'];
        foreach ( $address_fields as $field ) {
            if ( isset( $components[$field] ) && ! empty( $components[$field] ) ) {
                $parts[] = $components[$field];
            }
        }
        
        return implode( ', ', $parts );
    }

    /**
     * Extract business hours from structured data
     *
     * @return array Structured data hours
     */
    private function extract_hours_from_structured_data(): array {
        $hours = [];
        
        // This would parse openingHours from structured data
        // Implementation would be similar to address extraction
        
        return $hours;
    }

    /**
     * Validate email domain
     *
     * @param string $email Email address
     * @return bool True if domain is valid
     */
    private function validate_email_domain( string $email ): bool {
        $domain = substr( strrchr( $email, '@' ), 1 );
        if ( ! $domain ) {
            return false;
        }
        
        // Check if domain has MX record
        return checkdnsrr( $domain, 'MX' ) || checkdnsrr( $domain, 'A' );
    }

    /**
     * Calculate overall contact score
     *
     * @param array $contact_info Contact information
     * @return array Updated contact information with score
     */
    private function calculate_contact_score( array $contact_info ): array {
        $score = 0;
        $max_score = 100;
        
        // Email addresses (30 points max)
        $email_count = count( $contact_info['emails'] );
        $score += min( 30, $email_count * 15 );
        
        // Phone numbers (25 points max)
        $phone_count = count( $contact_info['phones'] );
        $score += min( 25, $phone_count * 12 );
        
        // Physical addresses (20 points max)
        $address_count = count( $contact_info['addresses'] );
        $score += min( 20, $address_count * 10 );
        
        // Contact forms (15 points max)
        $form_count = count( $contact_info['contact_forms'] );
        $score += min( 15, $form_count * 15 );
        
        // Business hours (10 points max)
        if ( ! empty( $contact_info['business_hours'] ) ) {
            $score += 10;
        }
        
        $contact_info['contact_score'] = min( $max_score, $score );
        $contact_info['contact_grade'] = Utils::score_to_grade( $contact_info['contact_score'] );
        
        return $contact_info;
    }

    /**
     * Generate contact recommendations
     *
     * @param array $contact_info Contact information
     * @return array Recommendations
     */
    private function generate_contact_recommendations( array $contact_info ): array {
        $recommendations = [];
        
        if ( empty( $contact_info['emails'] ) ) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'email',
                'title' => __( 'Add email contact information', 'hellaz-sitez-analyzer' ),
                'description' => __( 'Provide at least one email address for customer inquiries.', 'hellaz-sitez-analyzer' )
            ];
        }
        
        if ( empty( $contact_info['phones'] ) ) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'phone',
                'title' => __( 'Add phone contact information', 'hellaz-sitez-analyzer' ),
                'description' => __( 'Include a phone number for direct customer contact.', 'hellaz-sitez-analyzer' )
            ];
        }
        
        if ( empty( $contact_info['contact_forms'] ) ) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'forms',
                'title' => __( 'Add a contact form', 'hellaz-sitez-analyzer' ),
                'description' => __( 'Provide a contact form for easy customer inquiries.', 'hellaz-sitez-analyzer' )
            ];
        }
        
        if ( empty( $contact_info['addresses'] ) ) {
            $recommendations[] = [
                'priority' => 'low',
                'category' => 'address',
                'title' => __( 'Consider adding physical address', 'hellaz-sitez-analyzer' ),
                'description' => __( 'If applicable, include your business address for local customers.', 'hellaz-sitez-analyzer' )
            ];
        }
        
        return $recommendations;
    }
}
