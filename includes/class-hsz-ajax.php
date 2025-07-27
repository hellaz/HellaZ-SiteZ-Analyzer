<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

class AjaxHandler
{
    public function __construct()
    {
        add_action('wp_ajax_hsz_analyze_url', [$this, 'analyze_url']);
    }

    public function analyze_url()
    {
        check_ajax_referer('hsz_analyze_nonce', 'nonce');

        if (!current_user_can('read')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'hellaz-sitezalyzer')], 403);
        }

        $url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';

        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            wp_send_json_error(['message' => __('Invalid URL.', 'hellaz-sitezalyzer')], 400);
        }

        try {
            $metadata = (new Metadata())->extract_metadata($url);
            $social = (new SocialMedia())->extract_social_profiles(@file_get_contents($url), $url);

            if (isset($metadata['error'])) {
                wp_send_json_error(['message' => $metadata['error']], 500);
            }

            wp_send_json_success(['metadata' => $metadata, 'social' => $social]);
        } catch (\Throwable $e) {
            wp_send_json_error(['message' => $e->getMessage()], 500);
        }
    }
}
