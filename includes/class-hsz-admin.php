<?php
/**
 * Handles the admin-facing functionality of the plugin.
 *
 * This class is responsible for creating the settings page, registering
 * the settings, and rendering the fields for the admin dashboard.
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
 * Class Admin
 *
 * Manages the WordPress admin area settings and pages for the plugin.
 *
 * @package HSZ
 */
class Admin {

	/**
	 * The option group for the settings page.
	 *
	 * @var string
	 */
	private const OPTION_GROUP = 'hsz_settings_group';

	/**
	 * The option name for the fallback image setting.
	 *
	 * @var string
	 */
	private const OPTION_NAME_FALLBACK_IMAGE = 'hsz_setting_fallback_image';
    
    /**
	 * The slug for the plugin's settings page.
	 *
	 * @var string
	 */
	private const PAGE_SLUG = 'hellaz-sitez-analyzer';

	/**
	 * Admin constructor.
	 *
	 * Hooks into WordPress to initialize the admin functionality.
	 */
	public function __construct() {
		add_action( 'admin_init', [ $this, 'register_plugin_settings' ] );
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
	}

    /**
	 * Adds the plugin's page to the WordPress admin menu under 'Settings'.
	 */
    public function add_admin_menu(): void {
        add_options_page(
            'HellaZ SiteZ Analyzer',
            'SiteZ Analyzer',
            'manage_options',
            self::PAGE_SLUG,
            [ $this, 'render_settings_page' ]
        );
    }

	/**
	 * Registers the plugin's settings, sections, and fields with WordPress.
	 *
	 * This method is hooked into 'admin_init'.
	 */
	public function register_plugin_settings(): void {
		// Register the setting.
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME_FALLBACK_IMAGE,
			[
				'type'              => 'string',
				'description'       => 'The URL for the fallback image used in analysis.',
				'sanitize_callback' => [ 'HSZ\\Utils', 'sanitize_and_encrypt' ],
				'default'           => '',
				'show_in_rest'      => false,
			]
		);

		// Add a settings section to our custom page.
		add_settings_section(
			'hsz_settings_section',
			'HellaZ SiteZ Analyzer Settings',
			[ $this, 'render_section_header' ],
			self::PAGE_SLUG
		);

		// Add the settings field for the fallback image.
		add_settings_field(
			self::OPTION_NAME_FALLBACK_IMAGE,
			'Fallback Image URL',
			[ $this, 'render_fallback_image_field' ],
			self::PAGE_SLUG,
			'hsz_settings_section'
		);
	}

    /**
     * Renders the main container for the settings page.
     */
    public function render_settings_page(): void {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( self::OPTION_GROUP );
                do_settings_sections( self::PAGE_SLUG );
                submit_button( 'Save Settings' );
                ?>
            </form>
        </div>
        <?php
    }

	/**
	 * Renders the header text for the settings section.
	 */
	public function render_section_header(): void {
		echo '<p>Configure the default settings for the Site Analyzer.</p>';

		// Add a persistent warning if encryption is not configured.
		if ( ! Utils::is_encryption_configured() ) {
			echo '<div class="notice notice-warning is-dismissible"><p><strong>Security Warning:</strong> The encryption key is not defined in your <code>wp-config.php</code> file. Settings will be saved, but they will <strong>not be encrypted</strong>. Please define the <code>HSZ_ENCRYPTION_KEY</code> constant in <code>wp-config.php</code> for full security.</p></div>';
		}
	}

	/**
	 * Renders the HTML input field for the fallback image URL setting.
	 */
	public function render_fallback_image_field(): void {
		$option_value = get_option( self::OPTION_NAME_FALLBACK_IMAGE );
		$decrypted_value = '';

		if ( ! empty( $option_value ) && is_string( $option_value ) ) {
			// Attempt to decrypt. If it fails, assume it's a raw value.
			$decrypted = Utils::decrypt( $option_value );
			$decrypted_value = ( false !== $decrypted ) ? $decrypted : $option_value;
		}

		printf(
			'<input type="url" id="%1$s" name="%1$s" value="%2$s" class="regular-text" placeholder="https://example.com/fallback.jpg" />',
			esc_attr( self::OPTION_NAME_FALLBACK_IMAGE ),
			esc_attr( $decrypted_value )
		);

		echo '<p class="description">Enter the full URL of the image to use as a fallback when no other image can be found.</p>';
	}
}
