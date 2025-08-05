<?php
/**
 * Admin logs management for HellaZ SiteZ Analyzer.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.1.0
 */

namespace HSZ;

defined( 'ABSPATH' ) || exit;

class AdminLogs {

	/**
	 * Maximum number of log entries to display
	 */
	const MAX_LOG_ENTRIES = 1000;

	/**
	 * Log levels
	 */
	const LOG_LEVELS = [
		'error' => 'Error',
		'warning' => 'Warning',
		'info' => 'Info',
		'debug' => 'Debug'
	];

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', [ $this, 'handle_log_actions' ] );
		add_action( 'wp_ajax_hsz_export_logs', [ $this, 'ajax_export_logs' ] );
		add_action( 'wp_ajax_hsz_clear_logs', [ $this, 'ajax_clear_logs' ] );
	}

	/**
	 * Handle log management actions
	 */
	public function handle_log_actions(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle log clearing
		if ( isset( $_POST['hsz_clear_logs'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'hsz_clear_logs' ) ) {
			$this->clear_logs();
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-success is-dismissible"><p>' . 
					 esc_html__( 'Logs cleared successfully.', 'hellaz-sitez-analyzer' ) . 
					 '</p></div>';
			});
		}
	}

	/**
	 * Display admin logs page
	 */
	public function display_logs_page(): void {
		$level_filter = isset( $_GET['level'] ) ? sanitize_key( $_GET['level'] ) : 'all';
		$search_query = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';
		$paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
		$per_page = 50;

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'HellaZ SiteZ Analyzer - Error Logs', 'hellaz-sitez-analyzer' ); ?></h1>

			<?php $this->display_log_stats(); ?>

			<div class="hsz-logs-controls">
				<?php $this->display_log_filters( $level_filter, $search_query ); ?>
				<?php $this->display_log_actions(); ?>
			</div>

			<?php
			$logs = $this->get_logs( $level_filter, $search_query, $per_page, ( $paged - 1 ) * $per_page );
			$total_logs = $this->get_logs_count( $level_filter, $search_query );
			
			if ( empty( $logs ) ) {
				$this->display_no_logs_message( $level_filter, $search_query );
			} else {
				$this->display_logs_table( $logs );
				$this->display_pagination( $total_logs, $per_page, $paged );
			}
			?>
		</div>

		<style>
		.hsz-logs-controls {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin: 20px 0;
			padding: 15px;
			background: #fff;
			border: 1px solid #ccd0d4;
		}
		
		.hsz-log-filters {
			display: flex;
			gap: 10px;
			align-items: center;
		}
		
		.hsz-log-actions {
			display: flex;
			gap: 10px;
		}
		
		.hsz-log-level {
			padding: 2px 8px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: bold;
			text-transform: uppercase;
		}
		
		.hsz-log-level-error { background: #dc3232; color: white; }
		.hsz-log-level-warning { background: #ffb900; color: black; }
		.hsz-log-level-info { background: #00a0d2; color: white; }
		.hsz-log-level-debug { background: #666; color: white; }
		</style>
		<?php
	}

	/**
	 * Display log statistics
	 */
	private function display_log_stats(): void {
		$stats = $this->get_log_statistics();
		?>
		<div class="hsz-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
			<div class="hsz-stat-card" style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; text-align: center;">
				<div style="font-size: 24px; font-weight: bold; color: #0073aa;"><?php echo esc_html( number_format( $stats['total'] ) ); ?></div>
				<div style="font-size: 12px; color: #666; text-transform: uppercase;"><?php esc_html_e( 'Total Logs', 'hellaz-sitez-analyzer' ); ?></div>
			</div>
			<div class="hsz-stat-card" style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; text-align: center;">
				<div style="font-size: 24px; font-weight: bold; color: #0073aa;"><?php echo esc_html( number_format( $stats['errors'] ) ); ?></div>
				<div style="font-size: 12px; color: #666; text-transform: uppercase;"><?php esc_html_e( 'Errors', 'hellaz-sitez-analyzer' ); ?></div>
			</div>
			<div class="hsz-stat-card" style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; text-align: center;">
				<div style="font-size: 24px; font-weight: bold; color: #0073aa;"><?php echo esc_html( number_format( $stats['warnings'] ) ); ?></div>
				<div style="font-size: 12px; color: #666; text-transform: uppercase;"><?php esc_html_e( 'Warnings', 'hellaz-sitez-analyzer' ); ?></div>
			</div>
			<div class="hsz-stat-card" style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; text-align: center;">
				<div style="font-size: 24px; font-weight: bold; color: #0073aa;"><?php echo esc_html( $stats['last_24h'] ); ?></div>
				<div style="font-size: 12px; color: #666; text-transform: uppercase;"><?php esc_html_e( 'Last 24 Hours', 'hellaz-sitez-analyzer' ); ?></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Display log filters
	 */
	private function display_log_filters( string $level_filter, string $search_query ): void {
		?>
		<div class="hsz-log-filters">
			<form method="get" style="display: inline-flex; gap: 10px; align-items: center;">
				<input type="hidden" name="page" value="<?php echo esc_attr( $_GET['page'] ?? '' ); ?>">
				
				<label for="level"><?php esc_html_e( 'Level:', 'hellaz-sitez-analyzer' ); ?></label>
				<select name="level" id="level">
					<option value="all" <?php selected( $level_filter, 'all' ); ?>><?php esc_html_e( 'All Levels', 'hellaz-sitez-analyzer' ); ?></option>
					<?php foreach ( self::LOG_LEVELS as $level => $label ): ?>
						<option value="<?php echo esc_attr( $level ); ?>" <?php selected( $level_filter, $level ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				
				<label for="search"><?php esc_html_e( 'Search:', 'hellaz-sitez-analyzer' ); ?></label>
				<input type="text" name="search" id="search" value="<?php echo esc_attr( $search_query ); ?>" placeholder="<?php esc_attr_e( 'Search messages...', 'hellaz-sitez-analyzer' ); ?>">
				<button type="submit" class="button"><?php esc_html_e( 'Filter', 'hellaz-sitez-analyzer' ); ?></button>
			</form>
		</div>
		<?php
	}

	/**
	 * Display log actions
	 */
	private function display_log_actions(): void {
		?>
		<div class="hsz-log-actions">
			<button type="button" class="button" onclick="location.reload();">
				<?php esc_html_e( 'Refresh', 'hellaz-sitez-analyzer' ); ?>
			</button>
			
			<form method="post" style="display: inline;" onsubmit="return confirm('<?php esc_attr_e( 'Are you sure you want to clear all logs?', 'hellaz-sitez-analyzer' ); ?>');">
				<?php wp_nonce_field( 'hsz_clear_logs' ); ?>
				<button type="submit" name="hsz_clear_logs" class="button button-secondary">
					<?php esc_html_e( 'Clear Logs', 'hellaz-sitez-analyzer' ); ?>
				</button>
			</form>
		</div>
		<?php
	}

	/**
	 * Display logs table
	 */
	private function display_logs_table( array $logs ): void {
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col" style="width: 140px;"><?php esc_html_e( 'Time', 'hellaz-sitez-analyzer' ); ?></th>
					<th scope="col" style="width: 80px;"><?php esc_html_e( 'Level', 'hellaz-sitez-analyzer' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Message', 'hellaz-sitez-analyzer' ); ?></th>
					<th scope="col" style="width: 100px;"><?php esc_html_e( 'User', 'hellaz-sitez-analyzer' ); ?></th>
					<th scope="col" style="width: 120px;"><?php esc_html_e( 'IP Address', 'hellaz-sitez-analyzer' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $logs as $entry ): ?>
					<?php $this->display_log_row( $entry ); ?>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Display single log row
	 */
	private function display_log_row( object $entry ): void {
		$user = $entry->user_id ? get_user_by( 'id', $entry->user_id ) : null;
		$level_class = 'hsz-log-level hsz-log-level-' . esc_attr( $entry->level );
		?>
		<tr>
			<td><?php echo esc_html( mysql2date( 'Y-m-d H:i:s', $entry->created_at ?? $entry->timestamp ) ); ?></td>
			<td><span class="<?php echo esc_attr( $level_class ); ?>"><?php echo esc_html( strtoupper( $entry->level ) ); ?></span></td>
			<td><?php echo esc_html( $entry->message ); ?></td>
			<td><?php echo esc_html( $user ? $user->user_login : '—' ); ?></td>
			<td><?php echo esc_html( $entry->ip_address ?? $entry->ip ?? '—' ); ?></td>
		</tr>
		<?php
	}

	/**
	 * Display no logs message
	 */
	private function display_no_logs_message( string $level_filter, string $search_query ): void {
		?>
		<div class="notice notice-info">
			<p>
				<?php if ( $search_query ): ?>
					<?php printf( 
						esc_html__( 'No log entries found matching "%s".', 'hellaz-sitez-analyzer' ), 
						esc_html( $search_query ) 
					); ?>
				<?php elseif ( $level_filter !== 'all' ): ?>
					<?php printf( 
						esc_html__( 'No %s level log entries found.', 'hellaz-sitez-analyzer' ), 
						esc_html( $level_filter ) 
					); ?>
				<?php else: ?>
					<?php esc_html_e( 'No log entries found.', 'hellaz-sitez-analyzer' ); ?>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Display pagination
	 */
	private function display_pagination( int $total_logs, int $per_page, int $paged ): void {
		$total_pages = ceil( $total_logs / $per_page );
		
		if ( $total_pages <= 1 ) {
			return;
		}

		$pagination_args = [
			'base' => add_query_arg( 'paged', '%#%' ),
			'format' => '',
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'total' => $total_pages,
			'current' => $paged
		];

		?>
		<div class="tablenav">
			<div class="tablenav-pages">
				<?php echo paginate_links( $pagination_args ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Get logs from database
	 */
	private function get_logs( string $level_filter = 'all', string $search_query = '', int $limit = 50, int $offset = 0 ): array {
		global $wpdb;
		$table_name = $wpdb->prefix . 'hsz_error_log';

		$where_conditions = [];
		$where_values = [];

		if ( $level_filter !== 'all' ) {
			$where_conditions[] = 'level = %s';
			$where_values[] = $level_filter;
		}

		if ( ! empty( $search_query ) ) {
			$where_conditions[] = 'message LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( $search_query ) . '%';
		}

		$where_clause = ! empty( $where_conditions ) ? 'WHERE ' . implode( ' AND ', $where_conditions ) : '';
		
		$sql = "SELECT * FROM {$table_name} {$where_clause} ORDER BY created_at DESC, timestamp DESC LIMIT %d OFFSET %d";
		$where_values[] = $limit;
		$where_values[] = $offset;

		return $wpdb->get_results( $wpdb->prepare( $sql, $where_values ) );
	}

	/**
	 * Get logs count
	 */
	private function get_logs_count( string $level_filter = 'all', string $search_query = '' ): int {
		global $wpdb;
		$table_name = $wpdb->prefix . 'hsz_error_log';

		$where_conditions = [];
		$where_values = [];

		if ( $level_filter !== 'all' ) {
			$where_conditions[] = 'level = %s';
			$where_values[] = $level_filter;
		}

		if ( ! empty( $search_query ) ) {
			$where_conditions[] = 'message LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( $search_query ) . '%';
		}

		$where_clause = ! empty( $where_conditions ) ? 'WHERE ' . implode( ' AND ', $where_conditions ) : '';
		
		$sql = "SELECT COUNT(*) FROM {$table_name} {$where_clause}";
		
		if ( ! empty( $where_values ) ) {
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, $where_values ) );
		} else {
			return (int) $wpdb->get_var( $sql );
		}
	}

	/**
	 * Get log statistics
	 */
	private function get_log_statistics(): array {
		global $wpdb;
		$table_name = $wpdb->prefix . 'hsz_error_log';

		$stats = [
			'total' => 0,
			'errors' => 0,
			'warnings' => 0,
			'last_24h' => 0
		];

		// Check if table exists
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			return $stats;
		}

		// Total logs
		$stats['total'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );

		// Errors
		$stats['errors'] = (int) $wpdb->get_var( $wpdb->prepare( 
			"SELECT COUNT(*) FROM {$table_name} WHERE level = %s", 
			'error' 
		));

		// Warnings
		$stats['warnings'] = (int) $wpdb->get_var( $wpdb->prepare( 
			"SELECT COUNT(*) FROM {$table_name} WHERE level = %s", 
			'warning' 
		));

		// Last 24 hours - handle both created_at and timestamp columns
		$date_column = $wpdb->get_var( "SHOW COLUMNS FROM {$table_name} LIKE 'created_at'" ) ? 'created_at' : 'timestamp';
		$stats['last_24h'] = (int) $wpdb->get_var( $wpdb->prepare( 
			"SELECT COUNT(*) FROM {$table_name} WHERE {$date_column} >= %s", 
			date( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS )
		));

		return $stats;
	}

	/**
	 * Clear all logs
	 */
	private function clear_logs(): bool {
		global $wpdb;
		$table_name = $wpdb->prefix . 'hsz_error_log';
		
		return $wpdb->query( "TRUNCATE TABLE {$table_name}" ) !== false;
	}

	/**
	 * AJAX handler for exporting logs
	 */
	public function ajax_export_logs(): void {
		check_ajax_referer( 'hsz_export_logs' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Insufficient permissions.', 'hellaz-sitez-analyzer' ) );
		}

		$logs = $this->get_logs( 'all', '', self::MAX_LOG_ENTRIES, 0 );
		
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=hsz-logs-' . date( 'Y-m-d' ) . '.csv' );
		
		$output = fopen( 'php://output', 'w' );
		
		// CSV header
		fputcsv( $output, [
			'Timestamp',
			'Level', 
			'Message',
			'User ID',
			'IP Address'
		]);
		
		// CSV data
		foreach ( $logs as $log ) {
			fputcsv( $output, [
				$log->created_at ?? $log->timestamp,
				$log->level,
				$log->message,
				$log->user_id ?: '',
				$log->ip_address ?? $log->ip ?? ''
			]);
		}
		
		fclose( $output );
		exit;
	}

	/**
	 * Log an entry to the database
	 *
	 * @param string $level Log level.
	 * @param string $message Log message.
	 * @param array $context Additional context.
	 * @return bool Success status.
	 */
	public static function log_entry( string $level, string $message, array $context = [] ): bool {
		global $wpdb;
		$table_name = $wpdb->prefix . 'hsz_error_log';

		$data = [
			'level' => $level,
			'message' => $message,
			'context' => ! empty( $context ) ? wp_json_encode( $context ) : null,
			'user_id' => get_current_user_id() ?: null,
			'ip_address' => Utils::get_client_ip(),
			'created_at' => current_time( 'mysql', true )
		];

		return $wpdb->insert( $table_name, $data, [ '%s', '%s', '%s', '%d', '%s', '%s' ] ) !== false;
	}
}
