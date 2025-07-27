<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

class AdminLogs
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_logs_page']);
    }

    public function add_logs_page()
    {
        add_submenu_page(
            'tools.php',
            __('SiteZ Analyzer Logs', 'hellaz-sitezalyzer'),
            __('SiteZ Logs', 'hellaz-sitezalyzer'),
            'manage_options',
            'hsz_logs',
            [$this, 'render_logs']
        );
    }

    public function render_logs()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'hellaz-sitezalyzer'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'hsz_error_log';

        $entries = $wpdb->get_results("SELECT * FROM $table ORDER BY timestamp DESC LIMIT 100");

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('SiteZ Analyzer Error Logs', 'hellaz-sitezalyzer') . '</h1>';
        if (empty($entries)) {
            echo '<p>' . esc_html__('No error logs found.', 'hellaz-sitezalyzer') . '</p>';
        } else {
            echo '<table class="widefat fixed striped">';
            echo '<thead><tr><th>' . esc_html__('Time', 'hellaz-sitezalyzer') . '</th><th>' . esc_html__('Level', 'hellaz-sitezalyzer') . '</th><th>' . esc_html__('Message', 'hellaz-sitezalyzer') . '</th><th>' . esc_html__('User', 'hellaz-sitezalyzer') . '</th><th>' . esc_html__('IP', 'hellaz-sitezalyzer') . '</th></tr></thead>';
            echo '<tbody>';
            foreach ($entries as $entry) {
                $user = $entry->user_id ? get_userdata($entry->user_id) : null;
                echo '<tr>';
                echo '<td>' . esc_html($entry->timestamp) . '</td>';
                echo '<td>' . esc_html($entry->level) . '</td>';
                echo '<td>' . esc_html($entry->message) . '</td>';
                echo '<td>' . esc_html($user ? $user->user_login : '—') . '</td>';
                echo '<td>' . esc_html($entry->ip) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        echo '</div>';
    }
}
