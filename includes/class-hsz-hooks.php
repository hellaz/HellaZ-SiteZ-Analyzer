<?php
namespace HSZ;

if (!defined('ABSPATH')) {
    exit;
}

class Hooks {
    
    public function __construct() {
        // Register custom hooks and filters
        add_action('init', [$this, 'register_custom_hooks']);
        
        // Hook into plugin lifecycle events
        add_action('hsz_before_analysis', [$this, 'before_analysis_hook']);
        add_action('hsz_after_analysis', [$this, 'after_analysis_hook']);
        add_filter('hsz_modify_output', [$this, 'modify_output']);
        
        // WordPress integration hooks
        add_action('wp_head', [$this, 'add_meta_tags']);
        add_filter('the_content', [$this, 'auto_analyze_links'], 10);
    }
    
    /**
     * Register custom hooks for third-party integration
     */
    public function register_custom_hooks() {
        // Allow other plugins to extend functionality
        do_action('hsz_hooks_registered');
    }

    /**
     * Execute the 'hsz_before_analysis' hook.
     *
     * @param string $url The URL being analyzed.
     */
    public function before_analysis_hook($url) {
        // Validate the URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            error_log('[HellaZ SiteZ Analyzer] Invalid URL provided in before_analysis_hook: ' . esc_html($url));
            return;
        }

        // Log the hook execution (optional)
        error_log('[HellaZ SiteZ Analyzer] Executing hsz_before_analysis hook for URL: ' . esc_url($url));

        // Allow other plugins to hook into this event
        do_action('hsz_before_analysis', $url);
    }

    /**
     * Execute the 'hsz_after_analysis' hook.
     *
     * @param array $data The analysis data.
     */
    public function after_analysis_hook($data) {
        // Validate data
        if (!is_array($data)) {
            error_log('[HellaZ SiteZ Analyzer] Invalid data provided in after_analysis_hook');
            return;
        }

        // Log the hook execution (optional)
        error_log('[HellaZ SiteZ Analyzer] Executing hsz_after_analysis hook.');

        // Allow other plugins to process the analysis data
        do_action('hsz_after_analysis', $data);
    }

    /**
     * Apply the 'hsz_modify_output' filter.
     *
     * @param mixed $output The output to modify.
     * @return mixed The modified output.
     */
    public function modify_output($output) {
        // Log the filter execution (optional)
        error_log('[HellaZ SiteZ Analyzer] Applying hsz_modify_output filter.');

        // Allow other plugins to modify the output
        return apply_filters('hsz_modify_output', $output);
    }
    
    /**
     * Add meta tags for SEO enhancement
     */
    public function add_meta_tags() {
        if (is_single() || is_page()) {
            // Add plugin identifier meta tag
            echo '<meta name="hsz-analyzer-enabled" content="true" />' . "\n";
        }
    }
    
    /**
     * Auto-analyze links in content (if enabled in settings)
     */
    public function auto_analyze_links($content) {
        // Check if auto-analysis is enabled
        if (!get_option('hsz_auto_analyze_content', false)) {
            return $content;
        }
        
        // Find external links and add analysis data
        $pattern = '/<a\s+(?:[^>]*?\s+)?href="([^"]*)"[^>]*>(.*?)<\/a>/i';
        
        return preg_replace_callback($pattern, function($matches) {
            $url = $matches[1];
            $link_text = $matches[2];
            
            // Only process external links
            if (strpos($url, home_url()) === false && filter_var($url, FILTER_VALIDATE_URL)) {
                // Add data attributes for potential JavaScript enhancement
                $enhanced_link = str_replace('<a ', '<a data-hsz-analyzed="true" ', $matches[0]);
                return apply_filters('hsz_enhanced_link', $enhanced_link, $url);
            }
            
            return $matches[0];
        }, $content);
    }
}
