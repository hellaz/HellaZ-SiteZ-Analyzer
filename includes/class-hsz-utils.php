<?php
namespace HSZ;

class Utils {
    /**
     * Log an admin notice and write to the debug log.
     *
     * @param string $message The error message to log.
     */
    public static function log_admin_notice($message) {
        // Add an admin notice
        add_action('admin_notices', function () use ($message) {
            echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
        });

        // Log the error to the debug log
        error_log('[HellaZ SiteZ Analyzer] ' . $message);
    }
}
