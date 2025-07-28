<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique plugin identifier and the current version.
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
 * The core plugin class.
 */
class Core {

	/**
	 * The unique instance of the class.
	 *
	 * @var Core
	 */
	private static $instance;

	/**
	 * The plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Get the singleton instance of the class.
	 *
	 * @return Core
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin version and initialize hooks.
	 */
	private function __construct() {
		$this->version = HSZ_VERSION;
		$this->init_classes();
		$this->init_hooks();
	}

	/**
	 * Initializes the necessary classes for the plugin.
	 * Relies on the autoloader.
	 */
	private function init_classes() {
		// These classes instantiate themselves and register their own hooks.
		new Admin();
		new Ajax();
		new AdminLogs();
		new Shortcode();
		new Gutenberg();
		new Widget();
	}

	/**
	 * Initialize and register WordPress hooks and filters.
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', [ $this, 'load_plugin_textdomain' ] );
		add_action( 'wpmu_new_blog', [ 'HSZ\\Database', 'create_tables_new_site' ] );
		add_action( 'init', [ 'HSZ\\Database', 'check_db_version' ] );
	}

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'hellaz-sitez-analyzer',
			false,
			dirname( plugin_basename( HSZ_PLUGIN_PATH ) ) . '/languages/'
		);
	}

	/**
	 * The main execution function for the plugin.
	 */
	public function run() {
		// All functionality is initialized via hooks in the constructor.
	}

	/**
	 * Plugin activation routine.
	 *
	 * Creates database tables and sets default options.
	 */
	public static function activate() {
		// **CRITICAL FIX**: Explicitly require the Database class file here to
		// prevent a fatal error on activation if the autoloader is not yet available.
		require_once HSZ_PLUGIN_PATH . 'includes/class-hsz-database.php';
		
		// Delegate table creation to the centralized Database class.
		if ( class_exists( 'HSZ\\Database' ) ) {
			Database::create_tables();
		}

		// Set default cache duration if not set.
		if ( false === get_option( 'hsz_cache_duration' ) ) {
			update_option( 'hsz_cache_duration', DAY_IN_SECONDS );
		}

		// Flush rewrite rules to apply any new rules.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation routine.
	 *
	 * Cleanup tasks if necessary on plugin deactivation.
	 */
	public static function deactivate() {
		// Example: Unschedule cron jobs if needed.
		// if ( wp_next_scheduled( 'hsz_database_maintenance' ) ) {
		//  wp_clear_scheduled_hook( 'hsz_database_maintenance' );
		// }
	}

	/**
	 * Get the plugin version.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}
}
