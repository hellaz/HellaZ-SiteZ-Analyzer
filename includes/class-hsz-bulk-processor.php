<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

class BulkProcessor {
    public function start_bulk_processing($urls, $batch_name = '') {
        global $wpdb;
        $batch_id = uniqid('hsz_', true);
        $user_id = get_current_user_id();
        $table_batches = $wpdb->prefix . 'hsz_bulk_batches';
        $wpdb->insert($table_batches, array(
            'batch_id' => $batch_id,
            'user_id' => $user_id,
            'name' => $batch_name,
            'status' => 'pending',
            'total_urls' => count($urls),
            'created_at' => current_time('mysql')
        ));
        $table_results = $wpdb->prefix . 'hsz_bulk_results';
        foreach ($urls as $url) {
            $wpdb->insert($table_results, array(
                'batch_id' => $batch_id,
                'url' => $url,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ));
        }
        // Schedule processing (pseudo, replace with Action Scheduler if available)
        return $batch_id;
    }
    public function get_batch_status($batch_id) {
        global $wpdb;
        $table_batches = $wpdb->prefix . 'hsz_bulk_batches';
        $batch = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_batches WHERE batch_id = %s", $batch_id));
        if (!$batch) return array('error' => 'Batch not found');
        return array(
            'batch_id' => $batch->batch_id,
            'name' => $batch->name,
            'status' => $batch->status,
            'total_urls' => (int)$batch->total_urls,
            'processed_urls' => (int)$batch->processed_urls,
            'successful_urls' => (int)$batch->successful_urls,
            'failed_urls' => (int)$batch->failed_urls,
            'progress_percentage' => $batch->total_urls > 0 ? round(($batch->processed_urls / $batch->total_urls) * 100, 1) : 0
        );
    }
}
