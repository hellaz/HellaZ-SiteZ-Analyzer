<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

/**
 * Handles bulk processing of multiple URLs,
 * status tracking, and admin reporting.
 */
class BulkProcessor {

    // Add bulk operation: returns batch_id
    public function start_bulk($urls, $params = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'hsz_bulk_batches';
        $batch_id = uniqid('bulk_', true);

        $wpdb->insert($table, [
            'batch_id'      => $batch_id,
            'user_id'       => get_current_user_id(),
            'name'          => !empty($params['name']) ? sanitize_text_field($params['name']) : 'Bulk Batch',
            'status'        => 'pending',
            'total_urls'    => count($urls),
            'processed_urls'=> 0,
            'successful_urls'=> 0,
            'failed_urls'   => 0,
            'settings'      => wp_json_encode($params),
            'created_at'    => current_time('mysql'),
        ]);

        // Store URLs for processing
        foreach ($urls as $url) {
            $wpdb->insert($wpdb->prefix . 'hsz_bulk_results', [
                'batch_id'   => $batch_id,
                'url'        => esc_url_raw($url),
                'status'     => 'pending',
                'created_at' => current_time('mysql'),
            ]);
        }

        return $batch_id;
    }

    // Retrieve recent batches for the admin
    public static function get_admin_bulk_report($limit = 10) {
        global $wpdb;
        $table = $wpdb->prefix . 'hsz_bulk_batches';
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d", $limit
        ));

        if (!$rows) return '';

        ob_start();
        ?>
        <h3><?php esc_html_e('Recent Bulk Operations', 'hellaz-sitez-analyzer'); ?></h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Batch', 'hellaz-sitez-analyzer'); ?></th>
                    <th><?php _e('Status', 'hellaz-sitez-analyzer'); ?></th>
                    <th><?php _e('Total', 'hellaz-sitez-analyzer'); ?></th>
                    <th><?php _e('Processed', 'hellaz-sitez-analyzer'); ?></th>
                    <th><?php _e('Success', 'hellaz-sitez-analyzer'); ?></th>
                    <th><?php _e('Failed', 'hellaz-sitez-analyzer'); ?></th>
                    <th><?php _e('Created', 'hellaz-sitez-analyzer'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?php echo esc_html("{$row->name} (#{$row->batch_id})"); ?></td>
                    <td><?php echo esc_html($row->status); ?></td>
                    <td><?php echo esc_html($row->total_urls); ?></td>
                    <td><?php echo esc_html($row->processed_urls); ?></td>
                    <td><?php echo esc_html($row->successful_urls); ?></td>
                    <td><?php echo esc_html($row->failed_urls); ?></td>
                    <td><?php echo esc_html($row->created_at); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }

    // Admin method: check batch status
    public static function get_batch_status($batch_id) {
        global $wpdb;
        $batch = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}hsz_bulk_batches WHERE batch_id = %s", $batch_id
        ));
        if (!$batch) return null;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}hsz_bulk_results WHERE batch_id = %s ORDER BY id ASC", $batch_id
        ));
        return [
            'batch'   => $batch,
            'results' => $results,
        ];
    }

    // Optionally: process next URL in batch (provision for cron/AJAX batch processing)
    public function process_next($batch_id) {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}hsz_bulk_results WHERE batch_id = %s AND status = 'pending' LIMIT 1", $batch_id
        ));
        if (!$row) return false;
        // Process this URL with metadata extractor, store result, update status
        try {
            $meta = (new \HSZ\Metadata())->extract_metadata($row->url);
            $data = wp_json_encode($meta);

            $wpdb->update(
                $wpdb->prefix . 'hsz_bulk_results',
                [
                    'status'          => isset($meta['error']) ? 'failed' : 'completed',
                    'metadata'        => $data,
                    'processed_at'    => current_time('mysql'),
                    'error_message'   => isset($meta['error']) ? $meta['error'] : '',
                ],
                ['id' => $row->id]
            );

            // Update batch counters
            $update_counts = [
                'processed_urls'   => new \wpdb\Expression('processed_urls + 1')
            ];
            if (!isset($meta['error'])) {
                $update_counts['successful_urls'] = new \wpdb\Expression('successful_urls + 1');
            } else {
                $update_counts['failed_urls'] = new \wpdb\Expression('failed_urls + 1');
            }
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}hsz_bulk_batches 
                     SET processed_urls = processed_urls + 1, 
                         successful_urls = successful_urls + %d, 
                         failed_urls = failed_urls + %d
                     WHERE batch_id = %s",
                    !isset($meta['error']) ? 1 : 0,
                    isset($meta['error']) ? 1 : 0,
                    $batch_id
                )
            );
            return true;
        } catch (\Exception $e) {
            $wpdb->update(
                $wpdb->prefix . 'hsz_bulk_results',
                [
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                    'processed_at'  => current_time('mysql'),
                ],
                ['id' => $row->id]
            );
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}hsz_bulk_batches SET processed_urls = processed_urls + 1, failed_urls = failed_urls + 1 WHERE batch_id = %s",
                    $batch_id
                )
            );
            return false;
        }
    }
}
