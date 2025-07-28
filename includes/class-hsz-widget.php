<?php
/**
 * Adds a widget to display analysis results.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.0.0
 */

namespace HSZ;

use WP_Widget;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Widget
 *
 * Creates the SiteZ Analyzer widget.
 */
class Widget extends WP_Widget {

	/**
	 * Widget constructor.
	 *
	 * Sets up the widget name and description.
	 */
	public function __construct() {
		parent::__construct(
			'hsz_site_analyzer_widget',
			__( 'SiteZ Analyzer', 'hellaz-sitez-analyzer' ),
			[
				'description' => __( 'Displays website metadata and social profiles for a given URL.', 'hellaz-sitez-analyzer' ),
			]
		);
	}

	/**
	 * Outputs the content of the widget.
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from the database.
	 */
	public function widget( $args, $instance ) {
		echo wp_kses_post( $args['before_widget'] );

		$title = ! empty( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : '';
		$url   = ! empty( $instance['url'] ) ? esc_url_raw( $instance['url'] ) : '';

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			echo '<p>' . esc_html__( 'Please provide a valid URL in the widget settings.', 'hellaz-sitez-analyzer' ) . '</p>';
			echo wp_kses_post( $args['after_widget'] );
			return;
		}

		if ( $title ) {
			echo wp_kses_post( $args['before_title'] . $title . $args['after_title'] );
		}

		try {
			// Use WordPress HTTP API for fetching HTML safely via our Utils class.
			$html = Utils::get_html( $url );
			if ( is_wp_error( $html ) ) {
				throw new \Exception( $html->get_error_message() );
			}

			// Use Metadata and SocialMedia classes to fetch data.
			$metadata_extractor = new Metadata();
			$social_extractor   = new SocialMedia();

			$metadata = $metadata_extractor->extract_metadata( $url, $html );
			$social   = $social_extractor->extract_social_profiles( $html, $url );

			if ( isset( $metadata['error'] ) ) {
				throw new \Exception( $metadata['error'] );
			}

			// CORRECTED: Use the specific, correct methods from the Fallbacks class.
			$display_title       = $metadata['title'] ?? Fallbacks::get_fallback_title();
			$display_description = $metadata['description'] ?? Fallbacks::get_fallback_description();
			$favicon             = $metadata['favicon'] ?? Fallbacks::get_fallback_image();

			// Load the template for rendering.
			$template_mode = get_option( 'hsz_template_mode', 'classic' );
			$template_path = HSZ_PLUGIN_PATH . "templates/metadata-{$template_mode}.php";

			if ( file_exists( $template_path ) ) {
				// Make variables available to the template.
				include $template_path;
			} else {
				// Fallback to the classic template if the selected one doesn't exist.
				include HSZ_PLUGIN_PATH . 'templates/metadata-classic.php';
			}
		} catch ( \Throwable $e ) {
			if ( current_user_can( 'manage_options' ) ) {
				echo '<p class="hsz-error">' . esc_html( 'Admin Error: ' . $e->getMessage() ) . '</p>';
			} else {
				echo '<p class="hsz-error">' . esc_html__( 'An error occurred while analyzing the URL.', 'hellaz-sitez-analyzer' ) . '</p>';
			}
		}

		echo wp_kses_post( $args['after_widget'] );
	}

	/**
	 * Outputs the options form on the admin.
	 *
	 * @param array $instance The widget options.
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$url   = ! empty( $instance['url'] ) ? $instance['url'] : '';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'hellaz-sitez-analyzer' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'url' ) ); ?>"><?php esc_html_e( 'URL to Analyze:', 'hellaz-sitez-analyzer' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'url' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'url' ) ); ?>" type="url" value="<?php echo esc_attr( $url ); ?>">
		</p>
		<?php
	}

	/**
	 * Processing widget options on save.
	 *
	 * @param array $new_instance The new options.
	 * @param array $old_instance The previous options.
	 * @return array The updated options.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance          = [];
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['url']   = ( ! empty( $new_instance['url'] ) ) ? esc_url_raw( $new_instance['url'] ) : '';
		return $instance;
	}
}
