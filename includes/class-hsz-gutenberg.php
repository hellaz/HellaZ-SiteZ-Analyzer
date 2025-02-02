// Gutenberg block integration
namespace HSZ;

class Gutenberg {
    public function __construct() {
        add_action('init', [$this, 'register_block']);
    }

    public function register_block() {
        wp_register_script(
            'hsz-gutenberg-block',
            HSZ_PLUGIN_URL . 'assets/js/scripts.js',
            ['wp-blocks', 'wp-element', 'wp-editor'],
            '1.0.0',
            true
        );

        register_block_type('hsz/metadata-block', [
            'editor_script' => 'hsz-gutenberg-block',
            'render_callback' => [$this, 'render_block'],
        ]);
    }

    public function render_block($attributes) {
        $url = isset($attributes['url']) ? esc_url($attributes['url']) : '';
        $metadata = Metadata::extract_metadata($url);

        ob_start();
        include HSZ_PLUGIN_PATH . 'templates/metadata-template.php';
        return ob_get_clean();
    }
}
