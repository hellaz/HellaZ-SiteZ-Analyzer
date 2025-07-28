<?php
/**
 * Manages the plugin's settings page and all admin-facing functionality.
 *
 * This class is the central hub for the admin dashboard, creating the settings
 * page, registering all options, and rendering all fields and tabs.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.0.2
 */

namespace HSZ;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin
 *
 * Creates the settings page, registers settings, and handles form submissions.
 */
class Admin {

	/**
	 * The option group for the settings page.
	 * @var string
	 */
	private $settings_group = 'hsz_settings_group';

	/**
	 * The slug for the plugin's settings page.
	 * @var string
	 */
	private $page_slug = 'hellaz-sitez-analyzer-settings';

	/**
	 * Admin constructor.
	 *
	 * Registers the necessary actions for the admin dashboard.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_init', [ $this, 'handle_cache_clearing' ] );
	}

	/**
	 * Adds the main settings page to the WordPress admin menu.
	 */
	public function add_settings_page(): void {
		add_menu_page(
			__( 'HellaZ SiteZ Analyzer', 'hellaz-sitez-analyzer' ),
			__( 'SiteZ Analyzer', 'hellaz-sitez-analyzer' ),
			'manage_options',
			$this->page_slug,
			[ $this, 'render_settings_page' ],
			'dashicons-analytics',
			85
		);
	}

	/**
	 * Handles the POST request to clear the plugin's cache.
	 */
	public function handle_cache_clearing(): void {
		if ( isset( $_POST['hsz_action'] ) && $_POST['hsz_action'] === 'clear_cache' ) {
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'hsz_clear_cache_nonce' ) ) {
				wp_die( __( 'Security check failed. Please try again.', 'hellaz-sitez-analyzer' ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'hellaz-sitez-analyzer' ) );
			}

			$deleted_rows = Cache::clear_all_hsz_transients();

			add_action( 'admin_notices', function () use ( $deleted_rows ) {
				$message = sprintf( _n( '%d cache entry was successfully deleted.', '%d cache entries were successfully deleted.', $deleted_rows, 'hellaz-sitez-analyzer' ), $deleted_rows );
				printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $message ) );
			});
		}
	}

	/**
	 * Registers all settings for the plugin across all tabs.
	 */
	public function register_settings(): void {
		// General Tab
		register_setting( $this->settings_group, 'hsz_fallback_image', [ 'sanitize_callback' => [ 'HSZ\\Utils', 'sanitize_and_encrypt' ] ] );
		register_setting( $this->settings_group, 'hsz_fallback_title', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( $this->settings_group, 'hsz_fallback_description', [ 'sanitize_callback' => 'sanitize_textarea_field' ] );
		register_setting( $this->settings_group, 'hsz_disclaimer_enabled', [ 'sanitize_callback' => 'absint' ] );
		register_setting( $this->settings_group, 'hsz_disclaimer_message', [ 'sanitize_callback' => 'wp_kses_post' ] );
		register_setting( $this->settings_group, 'hsz_auto_analyze_content', [ 'sanitize_callback' => 'absint' ] );
		register_setting( $this->settings_group, 'hsz_disable_ssl_verify', [ 'sanitize_callback' => 'absint' ] );

		// API Keys Tab
		$apis = [ 'virustotal', 'builtwith', 'urlscan' ];
		foreach ( $apis as $api ) {
			register_setting( $this->settings_group, "hsz_{$api}_enabled", [ 'sanitize_callback' => 'absint' ] );
			register_setting( $this->settings_group, "hsz_{$api}_api_key", [ 'sanitize_callback' => [ 'HSZ\\Utils', 'sanitize_and_encrypt' ] ] );
		}

		// Cache Tab
		register_setting( $this->settings_group, 'hsz_cache_duration', [ 'sanitize_callback' => 'absint' ] );
		register_setting( $this->settings_group, 'hsz_cache_debug', [ 'sanitize_callback' => 'absint' ] );

		// Templates Tab
		register_setting( $this->settings_group, 'hsz_template_mode', [ 'sanitize_callback' => 'sanitize_key' ] );
	}

	/**
	 * Renders the main container and navigation for the settings page.
	 */
	public function render_settings_page(): void {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
		?>
		<div class="wrap hsz-settings-wrap">
			<h1><?php esc_html_e( 'HellaZ SiteZ Analyzer Settings', 'hellaz-sitez-analyzer' ); ?></h1>
			
			<?php if ( ! Utils::is_encryption_configured() ) : ?>
				<div class="notice notice-warning is-dismissible"><p><strong><?php esc_html_e( 'Security Warning:', 'hellaz-sitez-analyzer' ); ?></strong> <?php esc_html_e( 'The encryption key is not defined in your wp-config.php file. API keys and other sensitive settings will be saved, but they will not be encrypted. Please define the HSZ_ENCRYPTION_KEY constant for full security.', 'hellaz-sitez-analyzer' ); ?></p></div>
			<?php endif; ?>

			<nav class="nav-tab-wrapper">
				<a href="?page=<?php echo esc_attr( $this->page_slug ); ?>&tab=general" class="nav-tab <?php echo 'general' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'General', 'hellaz-sitez-analyzer' ); ?></a>
				<a href="?page=<?php echo esc_attr( $this->page_slug ); ?>&tab=api_keys" class="nav-tab <?php echo 'api_keys' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'API Keys', 'hellaz-sitez-analyzer' ); ?></a>
				<a href="?page=<?php echo esc_attr( $this->page_slug ); ?>&tab=bulk" class="nav-tab <?php echo 'bulk' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Bulk Processing', 'hellaz-sitez-analyzer' ); ?></a>
				<a href="?page=<?php echo esc_attr( $this->page_slug ); ?>&tab=cache" class="nav-tab <?php echo 'cache' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Cache', 'hellaz-sitez-analyzer' ); ?></a>
				<a href="?page=<?php echo esc_attr( $this->page_slug ); ?>&tab=templates" class="nav-tab <?php echo 'templates' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Templates', 'hellaz-sitez-analyzer' ); ?></a>
				<a href="?page=<?php echo esc_attr( $this->page_slug ); ?>&tab=about" class="nav-tab <?php echo 'about' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'About', 'hellaz-sitez-analyzer' ); ?></a>
			</nav>

			<?php
			switch ( $tab ) {
				case 'api_keys':
				case 'templates':
				case 'general':
				default:
					echo '<form action="options.php" method="post">';
					settings_fields( $this->settings_group );
					if ('api_keys' === $tab) $this->render_api_keys_tab();
					elseif ('templates' === $tab) $this->render_templates_tab();
					else $this->render_general_tab();
					submit_button();
					echo '</form>';
					break;
				case 'bulk':
					$this->render_bulk_tab();
					break;
				case 'cache':
					$this->render_cache_tab();
					break;
				case 'about':
					$this->render_about_tab();
					break;
			}
			?>
		</div>
		<?php
	}

	private function render_general_tab(): void {
		?>
		<h2><?php esc_html_e( 'General Settings', 'hellaz-sitez-analyzer' ); ?></h2>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="hsz_fallback_image"><?php esc_html_e( 'Fallback Image URL', 'hellaz-sitez-analyzer' ); ?></label></th>
				<td>
					<?php
						$encrypted_url = get_option( 'hsz_fallback_image' );
						$decrypted_url = ( $encrypted_url && is_string( $encrypted_url ) ) ? Utils::decrypt( $encrypted_url ) : '';
						if ( false === $decrypted_url ) { $decrypted_url = $encrypted_url; }
					?>
					<input id="hsz_fallback_image" type="url" name="hsz_fallback_image" value="<?php echo esc_attr( $decrypted_url ); ?>" class="regular-text"/>
					<p class="description"><?php esc_html_e( 'If a page has no image, this will be used.', 'hellaz-sitez-analyzer' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="hsz_fallback_title"><?php esc_html_e( 'Fallback Title', 'hellaz-sitez-analyzer' ); ?></label></th>
				<td>
					<input id="hsz_fallback_title" type="text" name="hsz_fallback_title" value="<?php echo esc_attr( get_option( 'hsz_fallback_title', '' ) ); ?>" class="regular-text"/>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="hsz_fallback_description"><?php esc_html_e( 'Fallback Description', 'hellaz-sitez-analyzer' ); ?></label></th>
				<td>
					<textarea id="hsz_fallback_description" name="hsz_fallback_description" rows="5" class="large-text"><?php echo esc_textarea( get_option( 'hsz_fallback_description', '' ) ); ?></textarea>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Auto-Analyze Content', 'hellaz-sitez-analyzer' ); ?></th>
				<td>
					<label><input type="checkbox" name="hsz_auto_analyze_content" value="1" <?php checked( 1, get_option( 'hsz_auto_analyze_content', 0 ) ); ?> /> <?php esc_html_e( 'Automatically add analysis attributes to external links in post content.', 'hellaz-sitez-analyzer' ); ?></label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Enable Disclaimer', 'hellaz-sitez-analyzer' ); ?></th>
				<td>
					<label><input type="checkbox" name="hsz_disclaimer_enabled" value="1" <?php checked( 1, get_option( 'hsz_disclaimer_enabled', 0 ) ); ?> /> <?php esc_html_e( 'Show a disclaimer message at the bottom of analyzed content.', 'hellaz-sitez-analyzer' ); ?></label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="hsz_disclaimer_message"><?php esc_html_e( 'Disclaimer Message', 'hellaz-sitez-analyzer' ); ?></label></th>
				<td>
					<textarea id="hsz_disclaimer_message" name="hsz_disclaimer_message" rows="5" class="large-text"><?php echo esc_textarea( get_option( 'hsz_disclaimer_message', '' ) ); ?></textarea>
				</td>
			</tr>
			<tr valign="top"><th scope="row" colspan="2"><hr><h3><?php esc_html_e( 'Advanced Settings', 'hellaz-sitez-analyzer' ); ?></h3></th></tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Disable SSL Verification', 'hellaz-sitez-analyzer' ); ?></th>
				<td>
					<label><input type="checkbox" name="hsz_disable_ssl_verify" value="1" <?php checked( 1, get_option( 'hsz_disable_ssl_verify', 0 ) ); ?> /> <?php esc_html_e( 'Disable SSL certificate verification for all outbound requests.', 'hellaz-sitez-analyzer' ); ?></label>
					<p class="description" style="color: #c00;">
						<strong><?php esc_html_e( 'Warning:', 'hellaz-sitez-analyzer' ); ?></strong>
						<?php esc_html_e( 'This is a security risk and should only be enabled if you are experiencing "cURL error 60" due to a server configuration issue. Enabling this makes your site vulnerable to man-in-the-middle attacks.', 'hellaz-sitez-analyzer' ); ?>
					</p>
				</td>
			</tr>

		</table>
		<?php
	}

	private function render_api_keys_tab(): void {
		?>
		<h2><?php esc_html_e( 'Third-Party API Keys', 'hellaz-sitez-analyzer' ); ?></h2>
		<p><?php esc_html_e( 'Enable and configure third-party services to enhance analysis.', 'hellaz-sitez-analyzer' ); ?></p>
		<table class="form-table">
			<?php
			$apis = [ 'virustotal' => 'VirusTotal', 'builtwith'  => 'BuiltWith', 'urlscan' => 'urlscan.io' ];
			foreach ( $apis as $slug => $name ) :
				$enabled_option = "hsz_{$slug}_enabled";
				$key_option     = "hsz_{$slug}_api_key";
				$encrypted_key  = get_option( $key_option, '' );
				$decrypted_key  = ( $encrypted_key && is_string( $encrypted_key ) ) ? Utils::decrypt( $encrypted_key ) : '';
				if ( false === $decrypted_key ) { $decrypted_key = $encrypted_key; }
				?>
				<tr valign="top">
					<th scope="row"><?php echo esc_html( $name ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span><?php echo esc_html( $name ); ?></span></legend>
							<label><input type="checkbox" name="<?php echo esc_attr( $enabled_option ); ?>" value="1" <?php checked( 1, get_option( $enabled_option, 0 ) ); ?>> <?php esc_html_e( 'Enable', 'hellaz-sitez-analyzer' ); ?></label>
							<br>
							<input type="password" name="<?php echo esc_attr( $key_option ); ?>" value="<?php echo esc_attr( $decrypted_key ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Enter API Key', 'hellaz-sitez-analyzer' ); ?>"/>
						</fieldset>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php
	}
	
	private function render_bulk_tab(): void {
		?>
		<h2><?php esc_html_e( 'Bulk URL Analyzer', 'hellaz-sitez-analyzer' ); ?></h2>
		<p><?php esc_html_e( 'Submit a list of URLs to be processed in the background.', 'hellaz-sitez-analyzer' ); ?></p>
		
		<div id="hsz-bulk-status"></div>
		
		<form id="hsz-bulk-form" method="post">
			<input type="hidden" id="hsz_bulk_nonce" value="<?php echo esc_attr( wp_create_nonce( 'hsz_start_bulk_processing' ) ); ?>">
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="hsz-batch-name"><?php esc_html_e( 'Batch Name', 'hellaz-sitez-analyzer' ); ?></label></th>
					<td>
						<input type="text" id="hsz-batch-name" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Competitor Analysis', 'hellaz-sitez-analyzer' ); ?>" />
						<p class="description"><?php esc_html_e( 'Give this batch a descriptive name (optional).', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="hsz-urls"><?php esc_html_e( 'URLs to Process', 'hellaz-sitez-analyzer' ); ?></label></th>
					<td>
						<textarea id="hsz-urls" rows="10" class="large-text" placeholder="https://example.com/page1&#10;https://example.org/another-page"></textarea>
						<p class="description"><?php esc_html_e( 'Enter one URL per line.', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Start Bulk Process', 'hellaz-sitez-analyzer' ) ); ?>
		</form>
		
		<hr/>
		
		<h3><?php esc_html_e( 'Recent Bulk Batches', 'hellaz-sitez-analyzer' ); ?></h3>
		<?php
		if ( class_exists( 'HSZ\\BulkProcessor' ) ) {
			echo BulkProcessor::get_admin_report();
		} else {
			echo '<p>' . esc_html__( 'Bulk processing report is currently unavailable.', 'hellaz-sitez-analyzer' ) . '</p>';
		}
	}
	
	private function render_cache_tab(): void {
		?>
		<form action="options.php" method="post">
			<?php settings_fields( $this->settings_group ); ?>
			<h2><?php esc_html_e( 'Cache Settings', 'hellaz-sitez-analyzer' ); ?></h2>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="hsz_cache_duration"><?php esc_html_e( 'Cache Duration', 'hellaz-sitez-analyzer' ); ?></label></th>
					<td>
						<input id="hsz_cache_duration" type="number" name="hsz_cache_duration" value="<?php echo esc_attr( get_option( 'hsz_cache_duration', HOUR_IN_SECONDS ) ); ?>" class="small-text" min="0" step="1"/>
						<p class="description"><?php esc_html_e( 'Duration in seconds for which analysis results are cached.', 'hellaz-sitez-analyzer' ); ?></p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Cache Debug', 'hellaz-sitez-analyzer' ); ?></th>
					<td>
						<label><input type="checkbox" name="hsz_cache_debug" value="1" <?php checked( 1, get_option( 'hsz_cache_debug', 0 ) ); ?> /> <?php esc_html_e( 'Enable cache debugging (adds comments to output).', 'hellaz-sitez-analyzer' ); ?></label>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
		
		<hr/>
		<h3><?php esc_html_e( 'Clear Plugin Cache', 'hellaz-sitez-analyzer' ); ?></h3>
		<p><?php esc_html_e( 'This will immediately delete all cached analysis data from the database.', 'hellaz-sitez-analyzer' ); ?></p>
		
		<form method="post" action="">
			<input type="hidden" name="hsz_action" value="clear_cache">
			<?php wp_nonce_field( 'hsz_clear_cache_nonce' ); ?>
			<?php submit_button( __( 'Clear All Cache Now', 'hellaz-sitez-analyzer' ), 'delete', 'hsz-clear-cache-button' ); ?>
		</form>
		<?php
	}
	
	private function render_templates_tab(): void {
		?>
		<h2><?php esc_html_e( 'Template Settings', 'hellaz-sitez-analyzer' ); ?></h2>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="hsz_template_mode"><?php esc_html_e( 'Display Template', 'hellaz-sitez-analyzer' ); ?></label></th>
				<td>
					<select id="hsz_template_mode" name="hsz_template_mode">
						<option value="classic" <?php selected( get_option( 'hsz_template_mode' ), 'classic' ); ?>><?php esc_html_e( 'Classic', 'hellaz-sitez-analyzer' ); ?></option>
						<option value="modern" <?php selected( get_option( 'hsz_template_mode' ), 'modern' ); ?>><?php esc_html_e( 'Modern', 'hellaz-sitez-analyzer' ); ?></option>
						<option value="compact" <?php selected( get_option( 'hsz_template_mode' ), 'compact' ); ?>><?php esc_html_e( 'Compact', 'hellaz-sitez-analyzer' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Choose the default display style for the analysis output.', 'hellaz-sitez-analyzer' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	private function render_about_tab(): void {
		?>
		<h2><?php esc_html_e( 'About HellaZ SiteZ Analyzer', 'hellaz-sitez-analyzer' ); ?></h2>
		<div class="about-section">
			<p><?php esc_html_e( 'HellaZ SiteZ Analyzer is a powerful tool designed to help you analyze website metadata, social profiles, and technical details quickly and efficiently.', 'hellaz-sitez-analyzer' ); ?></p>
			
			<h3><?php esc_html_e( 'Features', 'hellaz-sitez-analyzer' ); ?></h3>
			<ul>
				<li><?php esc_html_e( 'Comprehensive metadata extraction (title, description, favicons).', 'hellaz-sitez-analyzer' ); ?></li>
				<li><?php esc_html_e( 'Social media profile discovery (Facebook, Twitter, LinkedIn, etc.).', 'hellaz-sitez-analyzer' ); ?></li>
				<li><?php esc_html_e( 'Secure API key storage with fallback for non-technical users.', 'hellaz-sitez-analyzer' ); ?></li>
				<li><?php esc_html_e( 'Efficient background bulk processing for large lists of URLs.', 'hellaz-sitez-analyzer' ); ?></li>
				<li><?php esc_html_e( 'Flexible frontend display via templates, widgets, and a Gutenberg block.', 'hellaz-sitez-analyzer' ); ?></li>
			</ul>

			<h3><?php esc_html_e( 'Author & Source Code', 'hellaz-sitez-analyzer' ); ?></h3>
			<p>
				<?php esc_html_e( 'This plugin was created by HellaZ.', 'hellaz-sitez-analyzer' ); ?>
				<a href="https://www.hellaz.net/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Visit the author\'s website.', 'hellaz-sitez-analyzer' ); ?></a>
			</p>
			<p>
				<?php esc_html_e( 'The full source code is available on GitHub. We welcome contributions and bug reports.', 'hellaz-sitez-analyzer' ); ?>
				<a href="https://github.com/hellaz/HellaZ-SiteZ-Analyzer/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View on GitHub.', 'hellaz-sitez-analyzer' ); ?></a>
			</p>

			<p><em><?php printf( esc_html__( 'Version %s', 'hellaz-sitez-analyzer' ), HSZ_VERSION ); ?></em></p>
		</div>
		<?php
	}
}
