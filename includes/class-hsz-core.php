<?php
namespace HSZ;

class Core {
    public static function init() {
        // Initialize Gutenberg block using the singleton pattern
        Gutenberg::get_instance();

        // Initialize all plugin components
        new Metadata();
        new SocialMedia();
        new RSS();
        new Security();
        new Cache();
        new Shortcode();
        new Settings();
        new Hooks();
        new Fallbacks();
        new Widget();

        // Load text domain for translations
        load_plugin_textdomain('hellaz-sitez-analyzer', false, dirname(plugin_basename(__FILE__)) . '/languages/');

        // Register REST API endpoint
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Register REST API routes.
     */
    public function register_rest_routes() {
        register_rest_route('hsz/v1', '/metadata/(?P<url>.+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_metadata_for_url'],
            'permission_callback' => '__return_true', // Publicly accessible
            'args'                => [
                'url' => [
                    'required'          => true,
                    'validate_callback' => function ($param) {
                        return filter_var($param, FILTER_VALIDATE_URL);
                    },
                    'sanitize_callback' => 'esc_url_raw',
                ],
            ],
        ]);
    }

    /**
     * Callback function to handle REST API requests.
     *
     * @param \WP_REST_Request $request The REST API request object.
     * @return \WP_REST_Response The REST API response.
     */
    public function get_metadata_for_url(\WP_REST_Request $request) {
        $url = $request->get_param('url');

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return new \WP_REST_Response([
                'error'   => __('Invalid URL.', 'hellaz-sitez-analyzer'),
                'success' => false,
            ], 400);
        }

        try {
            // Extract metadata using the Metadata class
            $metadata = (new Metadata())->extract_metadata($url);

            if (isset($metadata['error'])) {
                return new \WP_REST_Response([
                    'error'   => $metadata['error'],
                    'success' => false,
                ], 400);
            }

            return new \WP_REST_Response([
                'data'    => $metadata,
                'success' => true,
            ], 200);
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'error'   => __('An error occurred while processing the URL.', 'hellaz-sitez-analyzer'),
                'details' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }
}
