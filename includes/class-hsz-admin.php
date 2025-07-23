<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

class Admin {
    public static function add_admin_menu() {
        add_menu_page(
            __('HellaZ SiteZ Analyzer', 'hellaz-sitez-analyzer'),
            __('SiteZ Analyzer', 'hellaz-sitez-analyzer'),
            'manage_options',
            'hsz-settings',
            [__CLASS__, 'redirect_to_settings'],
            'dashicons-search',
            30
        );
    }
    public static function redirect_to_settings() {
        wp_safe_redirect(admin_url('options-general.php?page=hsz-settings'));
        exit;
    }
}
