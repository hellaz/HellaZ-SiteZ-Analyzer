<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

/**
 * Handles bulk processing of multiple URLs,
 * tracks status and logs results for admin reporting.
 */
class BulkProcessor {

    /**
     * Start a new bulk processing batch.
     * @param array $urls List of URLs to process.
     * @param array $params Additional params like batch name.
     * @return string Unique batch ID.
     */
    public function start(array $urls, array $params = []) {
        global $wpdb;
        $table_batches = $wpdb->prefix . 'hsz_bulk_batches';
        $table_results = $wpdb->prefix . 'hsz_bulk_results';

        $batch_id = uniqid('bulk_', true);
        $user_id = get_current_user_id();
        $name = isset($params['name']) ? sanitize_text_field($params['name']) : 'Bulk Batch';

        $wpdb->insert($table_batches, [
            'batch_id'       => $batch_id,
            'user_id'        => $user_id,
            'name'           => $name,
            'status'         => 'pending',
            'total_urls'     => count($urls),
            'processed_urls' => 0,
            'successful_urls'=> 0,
            'failed_urls'    => 0,
            'settings'       => wp_json_encode($params),
            'created_at'     => current_time('mysql'),
        ]);

        foreach ($urls as $url) {
            $wpdb->insert($table_results, [
                'batch_id'      => $batch_id,
                'url'           => esc_url_raw($url),
                'status'        => 'pending',
                'created_at'    => current_time('mysql'),
            ]);
        }

        return $batch_id;
    }

    /**
     * Get recent bulk operation reports for admin UI.
     * @param int $limit Number of recent batches to retrieve.
     * @return string HTML table of batches and status.
     */
    public static function get_admin_report($limit = 10) {
        global $wpdb;
        $table = $wpdb->prefix . 'hsz_bulk_batches';

        $batches = $wpdb->get_results(
            "SELECT * FROM $table ORDER BY created_at DESC LIMIT $limit",
            ARRAY_A
        );

        if (!$batches) {
            return '<p>' . esc_html__('No recent bulk operations found.', 'hellaz-sitez-analyzer') . '</p>';
        }

        ob_start();
        ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e('Batch Name', 'hellaz-sitez-analyzer'); ?></th>
                    <th><?php esc_html_e('Batch ID', 'hellaz-sitez-analyzer'); ?></th>
                    <th><?php esc_html_e('Status', 'hellaz-sitez-analyzer'); ?></th>
                    <th><?php esc_html_e('Total URLs', 'hellaz-sitez-analyzer'); ?></th>
                    <th><?php esc_html_e('Processed', 'hellaz-sitez-analyzer'); ?></th>
                    <th><?php esc_html_e('Success', 'hellaz-sitez-analyzer'); ?></th>
                    <th><?php esc_html_e('Failed', 'hellaz-sitez-analyzer'); ?></th>
                    <th><?php esc_html_e('Created At', 'hellaz-sitez-analyzer'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($batches as $batch): ?>
                    <tr>
                        <td><?php echo esc_html($batch['name']); ?></td>
                        <td><?php echo esc_html($batch['batch_id']); ?></td>
                        <td><?php echo esc_html($batch['status']); ?></td>
                        <td><?php echo esc_html($batch['total_urls']); ?></td>
                        <td><?php echo esc_html($batch['processed_urls']); ?></td>
                        <td><?php echo esc_html($batch['successful_urls']); ?></td>
                        <td><?php echo esc_html($batch['failed_urls']); ?></td>
                        <td><?php echo esc_html($batch['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }

    /**
     * Retrieve status and results for a specific batch.
     * @param string $batch_id Unique batch identifier.
     * @return array|null Batch and results data or null if not found.
     */
    public static function get_batch_status($batch_id) {
        global $wpdb;
        $table_batches = $wpdb->prefix . 'hsz_bulk_batches';
        $table_results = $wpdb->prefix . 'hsz_bulk_results';

        $batch = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_batches WHERE batch_id = %s", $batch_id), ARRAY_A);
        if (!$batch) {
            return null;
        }

        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_results WHERE batch_id = %s ORDER BY id ASC", $batch_id), ARRAY_A);

        return [
            'batch' => $batch,
            'results' => $results,
        ];
    }

    /**
     * Process the next pending URL in a batch.
     * (Example skeleton for batch processing - can be invoked via cron or AJAX.)
     * @param string $batch_id
     * @return bool True if a URL was processed; false otherwise.
     */
    public function process_next_url($batch_id) {
        global $wpdb;
        $table_results = $wpdb->prefix . 'hsz_bulk_results';
        $table_batches = $wpdb->prefix . 'hsz_bulk_batches';

        $pending = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_results WHERE batch_id = %s AND status = 'pending' ORDER BY id ASC LIMIT 1",
            $batch_id
        ));

        if (!$pending) {
            // No pending URLs
            return false;
        }

        try {
            $metadata = (new \HSZ\Metadata())->extract_metadata($pending->url);
            $status = isset($metadata['error']) ? 'failed' : 'completed';
            $error_message = $metadata['error'] ?? '';

            $wpdb->update($table_results, [
                'status'         => $status,
                'metadata'       => wp_json_encode($metadata),
                'error_message'  => $error_message,
                'processed_at'   => current_time('mysql'),
            ], ['id' => $pending->id]);

            $wpdb->query($wpdb->prepare(
                "UPDATE $table_batches SET processed_urls = processed_urls + 1, successful_urls = successful_urls + %d, failed_urls = failed_urls + %d WHERE batch_id = %s",
                $status === 'completed' ? 1 : 0,
                $status === 'failed' ? 1 : 0,
                $batch_id
            ));

            return true;

        } catch (\Exception $e) {
            $wpdb->update($table_results, [
                'status'         => 'failed',
                'error_message'  => $e->getMessage(),
                'processed_at'   => current_time('mysql'),
            ], ['id' => $pending->id]);

            $wpdb->query($wpdb->prepare(
                "UPDATE $table_batches SET processed_urls = processed_urls + 1, failed_urls = failed_urls + 1 WHERE batch_id = %s",
                $batch_id
            ));

            return false;
        }
    }

    /**
     * Cancel a bulk batch operation.
     * @param string $batch_id
     * @return bool Success.
     */
    public function cancel_batch($batch_id) {
        global $wpdb;
        $table_batches = $wpdb->prefix . 'hsz_bulk_batches';

        $updated = $wpdb->update($table_batches, [
            'status' => 'cancelled',
            'completed_at' => current_time('mysql'),
        ], ['batch_id' => $batch_id]);

        return $updated !== false;
    }
}
