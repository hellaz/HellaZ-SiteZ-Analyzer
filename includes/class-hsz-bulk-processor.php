<?php
/**
 * Handles bulk analysis operations.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.0.0
 */

namespace HSZ;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BulkProcessor
 *
 * Manages the creation and reporting of bulk analysis batches.
 */
class BulkProcessor {

	/**
	 * Create a new bulk processing batch.
	 *
	 * @param array $urls An array of URLs to be processed.
	 * @param array $params Additional settings for the batch.
	 * @return string The unique ID of the created batch.
	 */
	public static function create_batch( array $urls, array $params = [] ): string {
		global $wpdb;

		$table_batches = $wpdb->prefix . 'hsz_bulk_batches';
		$table_results = $wpdb->prefix . 'hsz_bulk_results';
		$batch_id      = uniqid( 'hsz_bulk_', true );
		$user_id       = get_current_user_id();
		$name          = isset( $params['name'] ) ? sanitize_text_field( $params['name'] ) : 'Bulk Batch ' . gmdate( 'Y-m-d H:i:s' );

		$wpdb->insert(
			$table_batches,
			[
				'batch_id'        => $batch_id,
				'user_id'         => $user_id,
				'name'            => $name,
				'status'          => 'pending',
				'total_urls'      => count( $urls ),
				'processed_urls'  => 0,
				'successful_urls' => 0,
				'failed_urls'     => 0,
				'settings'        => wp_json_encode( $params ),
				'created_at'      => current_time( 'mysql', true ),
			]
		);

		foreach ( $urls as $url ) {
			if ( filter_var( $url, FILTER_VALIDATE_URL ) ) {
				$wpdb->insert(
					$table_results,
					[
						'batch_id'   => $batch_id,
						'url'        => esc_url_raw( $url ),
						'status'     => 'pending',
						'created_at' => current_time( 'mysql', true ),
					]
				);
			}
		}

		return $batch_id;
	}

	/**
	 * Get recent bulk operation reports for the admin UI.
	 *
	 * @param int $limit Number of recent batches to retrieve.
	 * @return string HTML table of batches and their status.
	 */
	public static function get_admin_report( int $limit = 10 ): string {
		global $wpdb;

		$table = $wpdb->prefix . 'hsz_bulk_batches';

		// Use $wpdb->prepare for security.
		$batches = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `$table` ORDER BY created_at DESC LIMIT %d",
				absint( $limit )
			),
			ARRAY_A
		);

		if ( ! $batches ) {
			return '<p>' . esc_html__( 'No recent bulk operations found.', 'hellaz-sitez-analyzer' ) . '</p>';
		}

		ob_start();
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Batch Name', 'hellaz-sitez-analyzer' ); ?></th>
					<th><?php esc_html_e( 'Status', 'hellaz-sitez-analyzer' ); ?></th>
					<th><?php esc_html_e( 'Progress', 'hellaz-sitez-analyzer' ); ?></th>
					<th><?php esc_html_e( 'Created At', 'hellaz-sitez-analyzer' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $batches as $batch ) : ?>
					<tr>
						<td><?php echo esc_html( $batch['name'] ); ?></td>
						<td><span class="hsz-status-<?php echo esc_attr( $batch['status'] ); ?>"><?php echo esc_html( ucfirst( $batch['status'] ) ); ?></span></td>
						<td><?php printf( '%d / %d', (int) $batch['processed_urls'], (int) $batch['total_urls'] ); ?></td>
						<td><?php echo esc_html( get_date_from_gmt( $batch['created_at'], 'Y-m-d H:i:s' ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		return ob_get_clean();
	}
}

