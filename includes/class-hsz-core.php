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
//        new Gutenberg();
        new Settings();
        new Hooks();
        new Fallbacks();
        new Widget();

        // Load text domain for translations
        load_plugin_textdomain('hellaz-sitez-analyzer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
}
