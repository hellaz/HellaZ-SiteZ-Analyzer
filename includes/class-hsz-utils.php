<?php
namespace HSZ;

class Utils {
    /**
     * Log an admin notice and write to the debug log.
     *
     * @param string $message The error message to log.
     */
    public static function log_admin_notice($message) {
        // Validate the message
        if (!is_string($message) || empty(trim($message))) {
            return;
        }

        // Add an admin notice
        add_action('admin_notices', function () use ($message) {
            echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
        });

        // Log the error to the debug log
        error_log('[HellaZ SiteZ Analyzer] Error: ' . $message);
    }
    
    /**
     * Encrypt API keys before saving.
     *
     * @param string $api_key The API key to encrypt.
     * @return string Encrypted API key.
     */
    public function encrypt_api_key($api_key) {
        if (!empty($api_key)) {
            return base64_encode(openssl_encrypt($api_key, 'AES-256-CBC', AUTH_KEY, 0, substr(AUTH_SALT, 0, 16)));
        }
        return '';
    }

    /**
     * Decrypt API keys when retrieving.
     *
     * @param string $encrypted_key The encrypted API key.
     * @return string Decrypted API key.
     */
    public function decrypt_api_key($encrypted_key) {
        if (!empty($encrypted_key)) {
            return openssl_decrypt(base64_decode($encrypted_key), 'AES-256-CBC', AUTH_KEY, 0, substr(AUTH_SALT, 0, 16));
        }
        return '';
    }    
}
