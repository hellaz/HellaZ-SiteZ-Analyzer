<?php
/**
 * Widget functionality for HellaZ SiteZ Analyzer.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.1.0
 */

namespace HSZ;

defined( 'ABSPATH' ) || exit;

class Widget extends \WP_Widget {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			'hsz_site_analyzer_widget',
			__( 'HellaZ SiteZ Analyzer', 'hellaz-sitez-analyzer' ),
			[
				'description' => __( 'Displays website metadata and social profiles for a given URL.', 'hellaz-sitez-analyzer' ),
				'classname' => 'hsz-analyzer-widget',
				'customize_selective_refresh' => true
			]
		);

		// Enqueue widget assets when needed
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_widget_assets' ] );
	}

	/**
	 * Enqueue widget assets
	 */
	public function enqueue_widget_assets(): void {
		if ( is_active_widget( false, false, $this->id_base ) ) {
			wp_enqueue_style( 
				'hsz-widget', 
				HSZ_PLUGIN_URL . 'assets/css/hsz-widget.css', 
				[], 
				HSZ_VERSION 
			);
		}
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		echo wp_kses_post( $args['before_widget'] );

		$title = ! empty( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : '';
		$url = ! empty( $instance['url'] ) ? esc_url_raw( $instance['url'] ) : '';
		$display_type = ! empty( $instance['display_type'] ) ? sanitize_key( $instance['display_type'] ) : 'compact';
		$show_social = isset( $instance['show_social'] ) ? (bool) $instance['show_social'] : true;
		$cache_duration = ! empty( $instance['cache_duration'] ) ? absint( $instance['cache_duration'] ) : 6;

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			echo '<div class="hsz-widget-error">';
			echo '<p>' . esc_html__( 'Please provide a valid URL in the widget settings.', 'hellaz-sitez-analyzer' ) . '</p>';
			echo '</div>';
			echo wp_kses_post( $args['after_widget'] );
			return;
		}

		if ( $title ) {
			echo wp_kses_post( $args['before_title'] . $title . $args['after_title'] );
		}

		try {
			// Check cache first
			$cache_key = 'widget_' . md5( $url . $display_type . (int) $show_social . $this->id );
			$cached_content = Cache::get( $cache_key, 'widget' );
			
			if ( $cached_content !== false ) {
				echo wp_kses_post( $cached_content );
				echo wp_kses_post( $args['after_widget'] );
				return;
			}

			// Fetch and analyze website
			$html = Utils::get_html( $url );
			if ( is_wp_error( $html ) ) {
				throw new \Exception( $html->get_error_message() );
			}

			$metadata_extractor = new Metadata();
			$metadata = $metadata_extractor->extract_metadata( $url, $html );

			if ( isset( $metadata['error'] ) ) {
				throw new \Exception( $metadata['error'] );
			}

			$social = [];
			if ( $show_social ) {
				$social_extractor = new SocialMedia();
				$social = $social_extractor->extract_social_profiles( $html, $url );
			}

			// Start output buffering to capture widget content
			ob_start();

			// Render widget content
			$this->render_widget_content( $url, $metadata, $social, $display_type );

			$widget_content = ob_get_clean();

			// Cache the content
			$cache_time = $cache_duration * HOUR_IN_SECONDS;
			Cache::set( $cache_key, $widget_content, $cache_time, 'widget' );

			echo wp_kses_post( $widget_content );

		} catch ( \Throwable $e ) {
			echo '<div class="hsz-widget-error">';
			if ( current_user_can( 'manage_options' ) ) {
				echo '<p><strong>' . esc_html__( 'Admin Error:', 'hellaz-sitez-analyzer' ) . '</strong> ' . esc_html( $e->getMessage() ) . '</p>';
			} else {
				echo '<p>' . esc_html__( 'An error occurred while analyzing the URL.', 'hellaz-sitez-analyzer' ) . '</p>';
			}
			echo '</div>';

			// Log error
			Utils::log_error( 'Widget analysis error: ' . $e->getMessage(), __FILE__, __LINE__ );
		}

		echo wp_kses_post( $args['after_widget'] );
	}

	/**
	 * Render widget content
	 *
	 * @param string $url Website URL.
	 * @param array $metadata Metadata.
	 * @param array $social Social profiles.
	 * @param string $display_type Display type.
	 */
	private function render_widget_content( string $url, array $metadata, array $social, string $display_type ): void {
		$title = $metadata['title'] ?? parse_url( $url, PHP_URL_HOST );
		$description = $metadata['description'] ?? '';
		$favicon = $metadata['favicon'] ?? '';

		?>
		<div class="hsz-widget-content hsz-<?php echo esc_attr( $display_type ); ?>">
			<div class="hsz-site-info">
				<?php if ( $favicon ): ?>
					<img src="<?php echo esc_url( $favicon ); ?>" alt="<?php esc_attr_e( 'Site favicon', 'hellaz-sitez-analyzer' ); ?>" class="hsz-favicon" width="16" height="16" loading="lazy">
				<?php endif; ?>

				<h4 class="hsz-site-title">
					<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( $title ); ?>
					</a>
				</h4>

				<?php if ( $description && $display_type !== 'minimal' ): ?>
					<p class="hsz-site-description">
						<?php echo esc_html( wp_trim_words( $description, $display_type === 'compact' ? 15 : 25 ) ); ?>
					</p>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $social ) && $display_type !== 'minimal' ): ?>
				<div class="hsz-social-links">
					<h5><?php esc_html_e( 'Social Profiles', 'hellaz-sitez-analyzer' ); ?></h5>
					<ul class="hsz-social-list">
						<?php foreach ( array_slice( $social, 0, 5 ) as $profile ): ?>
							<li>
								<a href="<?php echo esc_url( $profile['url'] ); ?>" target="_blank" rel="noopener noreferrer" class="hsz-social-link hsz-<?php echo esc_attr( strtolower( $profile['platform'] ) ); ?>">
									<span class="hsz-social-icon"></span>
									<?php echo esc_html( ucfirst( $profile['platform'] ) ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( $display_type === 'full' ): ?>
				<div class="hsz-metadata-info">
					<?php if ( ! empty( $metadata['og'] ) ): ?>
						<div class="hsz-og-info">
							<small><strong><?php esc_html_e( 'Open Graph:', 'hellaz-sitez-analyzer' ); ?></strong> <?php echo esc_html( count( $metadata['og'] ) ); ?> <?php esc_html_e( 'tags', 'hellaz-sitez-analyzer' ); ?></small>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $metadata['structured_data'] ) ): ?>
						<div class="hsz-structured-data-info">
							<small><strong><?php esc_html_e( 'Structured Data:', 'hellaz-sitez-analyzer' ); ?></strong> <?php echo esc_html( count( $metadata['structured_data'] ) ); ?> <?php esc_html_e( 'items', 'hellaz-sitez-analyzer' ); ?></small>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="hsz-widget-meta">
				<small class="hsz-analyzed-time">
					<?php printf( 
						esc_html__( 'Analyzed: %s', 'hellaz-sitez-analyzer' ), 
						current_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) 
					); ?>
				</small>
			</div>
		</div>
		<?php
	}

	/**
	 * Outputs the options form in the admin
	 *
	 * @param array $instance Current widget instance.
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$url = ! empty( $instance['url'] ) ? $instance['url'] : '';
		$display_type = ! empty( $instance['display_type'] ) ? $instance['display_type'] : 'compact';
		$show_social = isset( $instance['show_social'] ) ? (bool) $instance['show_social'] : true;
		$cache_duration = ! empty( $instance['cache_duration'] ) ? absint( $instance['cache_duration'] ) : 6;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title:', 'hellaz-sitez-analyzer' ); ?>
			</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php esc_attr_e( 'Website Analysis', 'hellaz-sitez-analyzer' ); ?>">
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'url' ) ); ?>">
				<?php esc_html_e( 'Website URL:', 'hellaz-sitez-analyzer' ); ?>
			</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'url' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'url' ) ); ?>" type="url" value="<?php echo esc_attr( $url ); ?>" placeholder="https://example.com" required>
			<small class="description"><?php esc_html_e( 'Enter the full URL including http:// or https://', 'hellaz-sitez-analyzer' ); ?></small>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'display_type' ) ); ?>">
				<?php esc_html_e( 'Display Type:', 'hellaz-sitez-analyzer' ); ?>
			</label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'display_type' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'display_type' ) ); ?>">
				<option value="compact" <?php selected( $display_type, 'compact' ); ?>><?php esc_html_e( 'Compact', 'hellaz-sitez-analyzer' ); ?></option>
				<option value="full" <?php selected( $display_type, 'full' ); ?>><?php esc_html_e( 'Full', 'hellaz-sitez-analyzer' ); ?></option>
				<option value="minimal" <?php selected( $display_type, 'minimal' ); ?>><?php esc_html_e( 'Minimal', 'hellaz-sitez-analyzer' ); ?></option>
			</select>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( $show_social ); ?> id="<?php echo esc_attr( $this->get_field_id( 'show_social' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_social' ) ); ?>" />
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_social' ) ); ?>">
				<?php esc_html_e( 'Show social profiles', 'hellaz-sitez-analyzer' ); ?>
			</label>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'cache_duration' ) ); ?>">
				<?php esc_html_e( 'Cache Duration (hours):', 'hellaz-sitez-analyzer' ); ?>
			</label>
			<input type="number" class="small-text" id="<?php echo esc_attr( $this->get_field_id( 'cache_duration' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'cache_duration' ) ); ?>" value="<?php echo esc_attr( $cache_duration ); ?>" min="1" max="168">
			<small class="description"><?php esc_html_e( 'How long to cache the analysis results (1-168 hours)', 'hellaz-sitez-analyzer' ); ?></small>
		</p>
		<?php
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance New settings for this instance.
	 * @param array $old_instance Previous settings for this instance.
	 * @return array Settings to save or bool false to cancel saving.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = [];
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['url'] = ( ! empty( $new_instance['url'] ) ) ? esc_url_raw( $new_instance['url'] ) : '';
		$instance['display_type'] = ( ! empty( $new_instance['display_type'] ) ) ? sanitize_key( $new_instance['display_type'] ) : 'compact';
		$instance['show_social'] = isset( $new_instance['show_social'] ) ? (bool) $new_instance['show_social'] : false;
		$instance['cache_duration'] = isset( $new_instance['cache_duration'] ) ? max( 1, min( 168, absint( $new_instance['cache_duration'] ) ) ) : 6;

		// Clear widget cache when settings change
		$old_cache_key = 'widget_' . md5( ( $old_instance['url'] ?? '' ) . ( $old_instance['display_type'] ?? '' ) . (int) ( $old_instance['show_social'] ?? true ) . $this->id );
		Cache::delete( $old_cache_key, 'widget' );

		return $instance;
	}
}
