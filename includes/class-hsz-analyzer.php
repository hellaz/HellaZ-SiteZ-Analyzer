<?php
/**
 * Main Analysis Coordinator for HellaZ SiteZ Analyzer
 *
 * This class orchestrates all analysis components and provides the main
 * analyze_url() method that coordinates metadata extraction, social media
 * detection, API analysis, and other analysis functions.
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
 * Class Analyzer
 *
 * Main analysis coordinator that orchestrates all analysis components.
 */
class Analyzer {

    /**
     * Analysis options
     *
     * @var array
     */
    private $options = [];

    /**
     * Analysis results
     *
     * @var array
     */
    private $results = [];

    /**
     * Constructor
     *
     * @param array $options Analysis options
     */
    public function __construct( array $options = [] ) {
        $this->options = wp_parse_args( $options, [
            'include_metadata' => true,
            'include_social' => true,
            'include_api_analysis' => true,
            'include_contact' => true,
            'include_performance' => true,
            'include_security' => true,
            'include_feeds' => true,
            'use_cache' => true,
            'cache_duration' => HOUR_IN_SECONDS * 6,
            'timeout' => 30,
            'user_agent' => 'HellaZ SiteZ Analyzer/' . HSZ_VERSION
        ]);
    }

    /**
     * Main URL analysis method
     *
     * Coordinates all analysis components to provide comprehensive website analysis.
     *
     * @param string $url Website URL to analyze
     * @param array $custom_options Custom options for this analysis
     * @return array Complete analysis results
     */
    public function analyze_url( string $url, array $custom_options = [] ): array {
        // Validate URL
        if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
            return [
                'error' => __( 'Invalid URL provided for analysis.', 'hellaz-sitez-analyzer' ),
                'url' => $url,
                'timestamp' => current_time( 'mysql', true )
            ];
        }

        // Merge custom options
        $options = wp_parse_args( $custom_options, $this->options );

        // Check cache first
        $cache_key = 'hsz_analysis_' . md5( $url . serialize( $options ) );
        if ( $options['use_cache'] ) {
            $cached_results = Cache::get( $cache_key, 'analysis' );
            if ( $cached_results !== false ) {
                return $cached_results;
            }
        }

        Utils::start_timer( 'complete_analysis' );

        // Initialize results structure
        $this->results = [
            'url' => $url,
            'timestamp' => current_time( 'mysql', true ),
            'analysis_version' => HSZ_VERSION,
            'metadata' => [],
            'social' => [],
            'contact' => [],
            'api_analysis' => [],
            'performance' => [],
            'security' => [],
            'feeds' => [],
            'overall_score' => 0,
            'overall_grade' => 'F',
            'recommendations' => [],
            'analysis_time' => 0,
            'errors' => []
        ];

        try {
            // Get HTML content
            $html = $this->fetch_html_content( $url, $options );
            if ( is_wp_error( $html ) ) {
                $this->results['errors'][] = $html->get_error_message();
                return $this->finalize_results();
            }

            // 1. Metadata Analysis (Core functionality)
            if ( $options['include_metadata'] ) {
                $this->results['metadata'] = $this->analyze_metadata( $url, $html, $options );
            }

            // 2. Social Media Analysis
            if ( $options['include_social'] ) {
                $this->results['social'] = $this->analyze_social_media( $url, $html, $options );
            }

            // 3. Contact Information Analysis
            if ( $options['include_contact'] ) {
                $this->results['contact'] = $this->analyze_contact_info( $url, $html, $options );
            }

            // 4. Feed Analysis
            if ( $options['include_feeds'] ) {
                $this->results['feeds'] = $this->analyze_feeds( $url, $html, $options );
            }

            // 5. API-based Analysis (Security, Performance, Technology)
            if ( $options['include_api_analysis'] ) {
                $this->results['api_analysis'] = $this->perform_api_analysis( $url, $options );
            }

            // 6. Calculate overall scores and recommendations
            $this->calculate_overall_scores();
            $this->generate_recommendations();

        } catch ( Exception $e ) {
            Utils::log_error( 'Analysis error for ' . $url . ': ' . $e->getMessage(), __FILE__, __LINE__ );
            $this->results['errors'][] = __( 'An unexpected error occurred during analysis.', 'hellaz-sitez-analyzer' );
        }

        // Finalize and cache results
        $final_results = $this->finalize_results();
        
        if ( $options['use_cache'] && empty( $final_results['errors'] ) ) {
            Cache::set( $cache_key, $final_results, $options['cache_duration'], 'analysis' );
        }

        return $final_results;
    }

    /**
     * Fetch HTML content from URL
     *
     * @param string $url Website URL
     * @param array $options Fetch options
     * @return string|WP_Error HTML content or error
     */
    private function fetch_html_content( string $url, array $options ) {
        $args = [
            'timeout' => $options['timeout'],
            'user-agent' => $options['user_agent'],
            'headers' => [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1'
            ],
            'sslverify' => false,
            'redirection' => 5
        ];

        $response = wp_remote_get( $url, $args );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'fetch_failed', 
                sprintf( __( 'Failed to fetch URL: %s', 'hellaz-sitez-analyzer' ), $response->get_error_message() )
            );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        if ( $status_code >= 400 ) {
            return new WP_Error( 'http_error', 
                sprintf( __( 'HTTP Error %d when fetching URL', 'hellaz-sitez-analyzer' ), $status_code )
            );
        }

        $html = wp_remote_retrieve_body( $response );
        if ( empty( $html ) ) {
            return new WP_Error( 'empty_content', 
                __( 'No content received from URL', 'hellaz-sitez-analyzer' )
            );
        }

        return $html;
    }

    /**
     * Analyze metadata using the Metadata class
     *
     * @param string $url Website URL
     * @param string $html HTML content
     * @param array $options Analysis options
     * @return array Metadata analysis results
     */
    private function analyze_metadata( string $url, string $html, array $options ): array {
        if ( ! class_exists( 'HSZ\\Metadata' ) ) {
            return [ 'error' => __( 'Metadata analysis class not available', 'hellaz-sitez-analyzer' ) ];
        }

        try {
            $metadata_analyzer = new Metadata();
            $metadata_options = [
                'extract_images' => true,
                'extract_links' => true,
                'extract_structured_data' => true,
                'extract_feeds' => false, // Handled separately
                'extract_languages' => true,
                'analyze_content' => true,
                'extract_performance_hints' => true
            ];

            return $metadata_analyzer->extract_metadata( $url, $html, $metadata_options );
        } catch ( Exception $e ) {
            Utils::log_error( 'Metadata analysis error: ' . $e->getMessage(), __FILE__, __LINE__ );
            return [ 'error' => __( 'Metadata analysis failed', 'hellaz-sitez-analyzer' ) ];
        }
    }

    /**
     * Analyze social media using the SocialMedia class
     *
     * @param string $url Website URL
     * @param string $html HTML content
     * @param array $options Analysis options
     * @return array Social media analysis results
     */
    private function analyze_social_media( string $url, string $html, array $options ): array {
        if ( ! class_exists( 'HSZ\\SocialMedia' ) ) {
            return [ 'error' => __( 'Social media analysis class not available', 'hellaz-sitez-analyzer' ) ];
        }

        try {
            $social_analyzer = new SocialMedia();
            $social_options = [
                'validate_profiles' => true,
                'extract_metrics' => false, // Can be resource intensive
                'analyze_content' => true,
                'check_integrations' => true,
                'social_seo_analysis' => true,
                'brand_consistency' => true,
                'force_refresh' => false
            ];

            return $social_analyzer->analyze_social_media( $html, $url, $social_options );
        } catch ( Exception $e ) {
            Utils::log_error( 'Social media analysis error: ' . $e->getMessage(), __FILE__, __LINE__ );
            return [ 'error' => __( 'Social media analysis failed', 'hellaz-sitez-analyzer' ) ];
        }
    }

    /**
     * Analyze contact information (placeholder for future Contact class)
     *
     * @param string $url Website URL
     * @param string $html HTML content
     * @param array $options Analysis options
     * @return array Contact information analysis results
     */
    private function analyze_contact_info( string $url, string $html, array $options ): array {
        // Check if Contact class exists (will be created separately)
        if ( class_exists( 'HSZ\\Contact' ) ) {
            try {
                $contact_analyzer = new Contact();
                return $contact_analyzer->extract_contact_info( $html, $url );
            } catch ( Exception $e ) {
                Utils::log_error( 'Contact analysis error: ' . $e->getMessage(), __FILE__, __LINE__ );
                return [ 'error' => __( 'Contact information analysis failed', 'hellaz-sitez-analyzer' ) ];
            }
        }

        // Basic fallback contact detection until Contact class is implemented
        return $this->basic_contact_detection( $html );
    }

    /**
     * Basic contact information detection (fallback)
     *
     * @param string $html HTML content
     * @return array Basic contact information
     */
    private function basic_contact_detection( string $html ): array {
        $contact_info = [
            'emails' => [],
            'phones' => [],
            'addresses' => [],
            'contact_forms' => [],
            'social_links' => [],
            'contact_score' => 0
        ];

        // Basic email detection
        if ( preg_match_all( '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $html, $email_matches ) ) {
            $contact_info['emails'] = array_unique( $email_matches[0] );
        }

        // Basic phone detection (US format)
        if ( preg_match_all( '/(\+?1[-.\s]?)?\(?([0-9]{3})\)?[-.\s]?([0-9]{3})[-.\s]?([0-9]{4})/', $html, $phone_matches, PREG_SET_ORDER ) ) {
            foreach ( $phone_matches as $match ) {
                $contact_info['phones'][] = trim( $match[0] );
            }
            $contact_info['phones'] = array_unique( $contact_info['phones'] );
        }

        // Basic contact form detection
        if ( preg_match_all( '/<form[^>]*>.*?<\/form>/is', $html, $form_matches ) ) {
            foreach ( $form_matches[0] as $form ) {
                if ( preg_match( '/(contact|inquiry|message|feedback|support)/i', $form ) ) {
                    $contact_info['contact_forms'][] = [
                        'type' => 'contact_form',
                        'detected' => true
                    ];
                }
            }
        }

        // Calculate basic contact score
        $score = 0;
        $score += count( $contact_info['emails'] ) * 20;
        $score += count( $contact_info['phones'] ) * 15;
        $score += count( $contact_info['contact_forms'] ) * 25;
        
        $contact_info['contact_score'] = min( 100, $score );
        $contact_info['note'] = __( 'Basic contact detection - enhanced version requires Contact class', 'hellaz-sitez-analyzer' );

        return $contact_info;
    }

    /**
     * Analyze RSS/Atom feeds
     *
     * @param string $url Website URL
     * @param string $html HTML content
     * @param array $options Analysis options
     * @return array Feed analysis results
     */
    private function analyze_feeds( string $url, string $html, array $options ): array {
        if ( ! class_exists( 'HSZ\\RSS' ) ) {
            return [ 'error' => __( 'RSS analysis class not available', 'hellaz-sitez-analyzer' ) ];
        }

        try {
            $rss_analyzer = new RSS();
            return $rss_analyzer->analyze_feeds( $url, $html );
        } catch ( Exception $e ) {
            Utils::log_error( 'Feed analysis error: ' . $e->getMessage(), __FILE__, __LINE__ );
            return [ 'error' => __( 'Feed analysis failed', 'hellaz-sitez-analyzer' ) ];
        }
    }

    /**
     * Perform API-based analysis
     *
     * @param string $url Website URL
     * @param array $options Analysis options
     * @return array API analysis results
     */
    private function perform_api_analysis( string $url, array $options ): array {
        if ( ! class_exists( 'HSZ\\APIAnalysis' ) ) {
            return [ 'error' => __( 'API analysis class not available', 'hellaz-sitez-analyzer' ) ];
        }

        try {
            $api_analyzer = new APIAnalysis();
            $api_options = [
                'ssl_analysis' => get_option( 'hsz_ssl_analysis_enabled', true ),
                'security_scan' => get_option( 'hsz_security_analysis_enabled', true ),
                'technology_detection' => get_option( 'hsz_builtwith_enabled', false ),
                'performance_analysis' => get_option( 'hsz_performance_analysis_enabled', true ),
                'reputation_check' => get_option( 'hsz_urlscan_enabled', false ),
                'force_refresh' => false
            ];

            return $api_analyzer->comprehensive_api_analysis( $url, $api_options );
        } catch ( Exception $e ) {
            Utils::log_error( 'API analysis error: ' . $e->getMessage(), __FILE__, __LINE__ );
            return [ 'error' => __( 'API analysis failed', 'hellaz-sitez-analyzer' ) ];
        }
    }

    /**
     * Calculate overall scores from all analysis components
     */
    private function calculate_overall_scores(): void {
        $scores = [];
        $weights = [
            'metadata' => 0.30,
            'social' => 0.20,
            'contact' => 0.15,
            'api_analysis' => 0.25,
            'feeds' => 0.10
        ];

        // Extract scores from each component
        if ( isset( $this->results['metadata']['metadata_quality']['score'] ) ) {
            $scores['metadata'] = $this->results['metadata']['metadata_quality']['score'];
        }

        if ( isset( $this->results['social']['social_score'] ) ) {
            $scores['social'] = $this->results['social']['social_score'];
        }

        if ( isset( $this->results['contact']['contact_score'] ) ) {
            $scores['contact'] = $this->results['contact']['contact_score'];
        }

        if ( isset( $this->results['api_analysis']['analysis_score'] ) ) {
            $scores['api_analysis'] = $this->results['api_analysis']['analysis_score'];
        }

        if ( isset( $this->results['feeds']['feed_score'] ) ) {
            $scores['feeds'] = $this->results['feeds']['feed_score'];
        } else {
            // Basic feed scoring if RSS class doesn't provide it
            $feed_count = 0;
            if ( isset( $this->results['metadata']['feeds'] ) ) {
                $feed_count = count( $this->results['metadata']['feeds'] );
            }
            $scores['feeds'] = min( 100, $feed_count * 50 ); // 50 points per feed, max 100
        }

        // Calculate weighted average
        $weighted_sum = 0;
        $total_weight = 0;

        foreach ( $weights as $component => $weight ) {
            if ( isset( $scores[$component] ) ) {
                $weighted_sum += $scores[$component] * $weight;
                $total_weight += $weight;
            }
        }

        $this->results['overall_score'] = $total_weight > 0 ? round( $weighted_sum / $total_weight ) : 0;
        $this->results['overall_grade'] = Utils::score_to_grade( $this->results['overall_score'] );
        $this->results['component_scores'] = $scores;
        $this->results['score_weights'] = $weights;
    }

    /**
     * Generate recommendations based on analysis results
     */
    private function generate_recommendations(): void {
        $recommendations = [];

        // Metadata recommendations
        if ( isset( $this->results['metadata']['metadata_quality']['recommendations'] ) ) {
            foreach ( $this->results['metadata']['metadata_quality']['recommendations'] as $rec ) {
                $recommendations[] = [
                    'category' => 'metadata',
                    'priority' => 'high',
                    'recommendation' => $rec
                ];
            }
        }

        // Social media recommendations
        if ( isset( $this->results['social']['recommendations'] ) ) {
            foreach ( $this->results['social']['recommendations'] as $rec ) {
                $recommendations[] = [
                    'category' => 'social',
                    'priority' => $rec['priority'] ?? 'medium',
                    'recommendation' => $rec['title'] ?? $rec
                ];
            }
        }

        // API analysis recommendations
        if ( isset( $this->results['api_analysis']['recommendations'] ) ) {
            foreach ( $this->results['api_analysis']['recommendations'] as $rec ) {
                $recommendations[] = [
                    'category' => 'security',
                    'priority' => $rec['priority'] ?? 'high',
                    'recommendation' => $rec['title'] ?? $rec
                ];
            }
        }

        // Contact recommendations
        if ( isset( $this->results['contact']['contact_score'] ) && $this->results['contact']['contact_score'] < 50 ) {
            $recommendations[] = [
                'category' => 'contact',
                'priority' => 'medium',
                'recommendation' => __( 'Add more contact information (email, phone, contact form) to improve user experience', 'hellaz-sitez-analyzer' )
            ];
        }

        // Feed recommendations
        if ( empty( $this->results['metadata']['feeds'] ?? [] ) ) {
            $recommendations[] = [
                'category' => 'feeds',
                'priority' => 'low',
                'recommendation' => __( 'Consider adding RSS/Atom feeds for content syndication', 'hellaz-sitez-analyzer' )
            ];
        }

        $this->results['recommendations'] = $recommendations;
    }

    /**
     * Finalize analysis results
     *
     * @return array Final analysis results
     */
    private function finalize_results(): array {
        $this->results['analysis_time'] = Utils::stop_timer( 'complete_analysis' );
        $this->results['analysis_summary'] = $this->generate_analysis_summary();
        
        return $this->results;
    }

    /**
     * Generate analysis summary
     *
     * @return array Analysis summary
     */
    private function generate_analysis_summary(): array {
        return [
            'overall_score' => $this->results['overall_score'],
            'overall_grade' => $this->results['overall_grade'],
            'components_analyzed' => array_keys( array_filter( $this->results, function( $value, $key ) {
                return !in_array( $key, ['url', 'timestamp', 'analysis_version', 'overall_score', 'overall_grade', 'recommendations', 'errors', 'analysis_time'] ) 
                       && !empty( $value ) && !isset( $value['error'] );
            }, ARRAY_FILTER_USE_BOTH ) ),
            'total_recommendations' => count( $this->results['recommendations'] ),
            'high_priority_issues' => count( array_filter( $this->results['recommendations'], function( $rec ) {
                return ($rec['priority'] ?? '') === 'high' || ($rec['priority'] ?? '') === 'critical';
            })),
            'analysis_completeness' => $this->calculate_analysis_completeness(),
            'errors_count' => count( $this->results['errors'] )
        ];
    }

    /**
     * Calculate analysis completeness percentage
     *
     * @return int Completeness percentage
     */
    private function calculate_analysis_completeness(): int {
        $total_components = 6; // metadata, social, contact, api_analysis, feeds, performance
        $completed_components = 0;

        $components_to_check = ['metadata', 'social', 'contact', 'api_analysis', 'feeds'];
        
        foreach ( $components_to_check as $component ) {
            if ( !empty( $this->results[$component] ) && !isset( $this->results[$component]['error'] ) ) {
                $completed_components++;
            }
        }

        return round( ($completed_components / $total_components) * 100 );
    }

    /**
     * Get analysis results (public accessor)
     *
     * @return array Current analysis results
     */
    public function get_results(): array {
        return $this->results;
    }

    /**
     * Set analysis options (public accessor)
     *
     * @param array $options New options to merge
     */
    public function set_options( array $options ): void {
        $this->options = wp_parse_args( $options, $this->options );
    }

    /**
     * Quick analysis method (simplified version)
     *
     * @param string $url Website URL to analyze
     * @return array Simplified analysis results
     */
    public function quick_analyze( string $url ): array {
        $quick_options = [
            'include_metadata' => true,
            'include_social' => true,
            'include_api_analysis' => false,
            'include_contact' => true,
            'include_performance' => false,
            'include_security' => false,
            'include_feeds' => false,
            'use_cache' => true,
            'cache_duration' => HOUR_IN_SECONDS * 12,
            'timeout' => 15
        ];

        return $this->analyze_url( $url, $quick_options );
    }
}
