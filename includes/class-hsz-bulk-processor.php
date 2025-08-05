<?php
/**
 * Bulk URL analysis processor for HellaZ SiteZ Analyzer.
 *
 * Handles batch management, result insertion, progress tracking,
 * and admin reporting, with improved security and sanitization.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.1.0
 */

namespace HSZ;

defined( 'ABSPATH' ) || exit;

class BulkProcessor {

	/**
	 * Creates a new bulk batch and inserts URLs.
	 *
	 * @param array $urls List of URLs to analyze.
	 * @param array $params Additional parameters e.g. batch name.
	 * @return string|false Batch ID on success, false on failure.
	 */
	public static function create_batch( array $urls, array $params = [] ) {
		global $wpdb;

		if ( empty( $urls ) ) {
			return false;
		}

		$table_batches = $wpdb->prefix . 'hsz_bulk_batches';
		$table_results = $wpdb->prefix . 'hsz_bulk_results';

		$batch_id = uniqid( 'hsz_bulk_', true );
		$user_id = get_current_user_id();
		$name = isset( $params['name'] ) ? sanitize_text_field( $params['name'] ) : 'Bulk Batch ' . gmdate( 'Y-m-d H:i:s' );

		$inserted = $wpdb->insert(
			$table_batches,
			[
				'batch_id' => $batch_id,
				'user_id' => $user_id,
				'name' => $name,
				'status' => 'pending',
				'total_urls' => count( $urls ),
				'processed_urls' => 0,
				'successful_urls' => 0,
				'failed_urls' => 0,
				'settings' => wp_json_encode( $params ),
				'created_at' => current_time( 'mysql', true ),
			],
			[ '%s', '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s' ]
		);

		if ( false === $inserted ) {
			return false;
		}

		foreach ( $urls as $url ) {
			if ( filter_var( $url, FILTER_VALIDATE_URL ) ) {
				$wpdb->insert(
					$table_results,
					[
						'batch_id' => $batch_id,
						'url' => esc_url_raw( $url ),
						'status' => 'pending',
						'created_at' => current_time( 'mysql', true )
					],
					[ '%s', '%s', '%s', '%s' ]
				);
			}
		}

		return $batch_id;
	}

	/**
	 * Retrieves recent bulk batches for admin report.
	 *
	 * @param int $limit Number of batches to retrieve.
	 * @return string HTML table of bulk batches and their statuses.
	 */
	public static function get_admin_report( int $limit = 10 ): string {
		global $wpdb;

		$table = $wpdb->prefix . 'hsz_bulk_batches';

		$batches = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `$table` ORDER BY created_at DESC LIMIT %d",
				absint( $limit )
			),
			ARRAY_A
		);

		if ( empty( $batches ) ) {
			return '<p>' . esc_html__( 'No recent bulk operations found.', 'hellaz-sitez-analyzer' ) . '</p>';
		}

		ob_start();
		?>
		<table class="wp-list-table widefat fixed striped hsz-bulk-report">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Batch ID', 'hellaz-sitez-analyzer' ); ?></th>
					<th><?php esc_html_e( 'Name', 'hellaz-sitez-analyzer' ); ?></th>
					<th><?php esc_html_e( 'Status', 'hellaz-sitez-analyzer' ); ?></th>
					<th><?php esc_html_e( 'Total URLs', 'hellaz-sitez-analyzer' ); ?></th>
					<th><?php esc_html_e( 'Processed', 'hellaz-sitez-analyzer' ); ?></th>
					<th><?php esc_html_e( 'Successful', 'hellaz-sitez-analyzer' ); ?></th>
					<th><?php esc_html_e( 'Failed', 'hellaz-sitez-analyzer' ); ?></th>
					<th><?php esc_html_e( 'Created At', 'hellaz-sitez-analyzer' ); ?></th>
					<th><?php esc_html_e( 'Updated At', 'hellaz-sitez-analyzer' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $batches as $batch ) : ?>
					<tr>
						<td><code><?php echo esc_html( $batch['batch_id'] ); ?></code></td>
						<td><?php echo esc_html( $batch['name'] ); ?></td>
						<td><?php echo esc_html( ucfirst( $batch['status'] ) ); ?></td>
						<td><?php echo esc_html( $batch['total_urls'] ); ?></td>
						<td><?php echo esc_html( $batch['processed_urls'] ); ?></td>
						<td><?php echo esc_html( $batch['successful_urls'] ); ?></td>
						<td><?php echo esc_html( $batch['failed_urls'] ); ?></td>
						<td><?php echo esc_html( $batch['created_at'] ); ?></td>
						<td><?php echo esc_html( $batch['updated_at'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		return ob_get_clean();
	}
}
