<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

class Admin {
    public static function add_admin_menu() {
        add_menu_page(
            __('HellaZ SiteZ Analyzer', 'hellaz-sitez-analyzer'),
            __('SiteZ Analyzer', 'hellaz-sitez-analyzer'),
            'edit_posts',
            'hsz-analyzer',
            array(__CLASS__, 'display_single_analyzer'),
            'dashicons-search',
            30
        );
        add_submenu_page(
            'hsz-analyzer',
            __('Bulk Processing', 'hellaz-sitez-analyzer'),
            __('Bulk Processing', 'hellaz-sitez-analyzer'),
            'edit_posts',
            'hsz-bulk-processing',
            array(__CLASS__, 'display_bulk_processing')
        );
    }

    public static function display_single_analyzer() {
        ?>
        <div class="hsz-admin-container">
            <h1><?php _e('HellaZ SiteZ Analyzer', 'hellaz-sitez-analyzer'); ?></h1>
            <form id="hsz-analyze-form" method="post">
                <input type="text" name="url" id="hsz-url" placeholder="<?php esc_attr_e('Enter URL', 'hellaz-sitez-analyzer'); ?>" required>
                <input type="submit" value="<?php esc_attr_e('Analyze URL', 'hellaz-sitez-analyzer'); ?>">
                <?php wp_nonce_field('hsz_security_nonce', 'hsz_nonce'); ?>
            </form>
            <div id="hsz-analysis-result"></div>
        </div>
        <?php
    }

    public static function display_bulk_processing() {
        ?>
        <div class="hsz-admin-container">
            <h1><?php _e('Bulk Processing', 'hellaz-sitez-analyzer'); ?></h1>
            <form id="hsz-bulk-form" method="post">
                <input type="text" name="batch_name" id="hsz-batch-name" placeholder="<?php esc_attr_e('Batch Name', 'hellaz-sitez-analyzer'); ?>" required>
                <textarea name="urls" id="hsz-urls" placeholder="<?php esc_attr_e('Enter one URL per line', 'hellaz-sitez-analyzer'); ?>" required></textarea>
                <input type="submit" value="<?php esc_attr_e('Start Bulk Processing', 'hellaz-sitez-analyzer'); ?>">
                <?php wp_nonce_field('hsz_bulk_security_nonce', 'hsz_bulk_nonce'); ?>
            </form>
            <div id="hsz-bulk-status"></div>
        </div>
        <?php
    }
}
