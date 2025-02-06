<?php
namespace HSZ;

class Hooks {
    public function __construct() {
        add_action('hsz_before_analysis', [$this, 'before_analysis_hook']);
        add_action('hsz_after_analysis', [$this, 'after_analysis_hook']);
        add_filter('hsz_modify_output', [$this, 'modify_output']);
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

        do_action('hsz_before_analysis', $url);
    }

    /**
     * Execute the 'hsz_after_analysis' hook.
     *
     * @param array $data The analysis data.
     */
    public function after_analysis_hook($data) {
        // Log the hook execution (optional)
        error_log('[HellaZ SiteZ Analyzer] Executing hsz_after_analysis hook.');

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

        return apply_filters('hsz_modify_output', $output);
    }
}
