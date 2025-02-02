// Core plugin logic
namespace HSZ;

class Core {
    public static function init() {
        // Load dependencies
        new Metadata();
        new SocialMedia();
        new RSS();
        new Security();
        new Cache();
        new Shortcode();
        new Gutenberg();
        new Settings();
        new Hooks();
        new Fallbacks();

        // Load text domain for translations
        load_plugin_textdomain('hellaz-sitez-analyzer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
}
