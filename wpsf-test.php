<?php
/*
Plugin Name: WP Settings Framework Example
Description: An example of the WP Settings Framework in action.
Version: 1.6.0
Author: Gilbert Pellegrom
Author URI: http://dev7studios.com
*/

class WPSFTest {
	/**
	 * @var string
	 */
	private $plugin_path;

	/**
	 * @var WordPressSettingsFramework
	 */
	private $wpsf;

	/**
	 * WPSFTest constructor.
	 */
	public function __construct() {
		$this->plugin_path = plugin_dir_path( __FILE__ );

		// Include and create a new WordPressSettingsFramework
		require_once( $this->plugin_path . 'wp-settings-framework.php' );
		$this->wpsf = new WordPressSettingsFramework( $this->plugin_path . 'settings/example-settings.php', 'my_example_settings' );

		// Add admin menu
		add_action( 'admin_menu', array( $this, 'add_settings_page' ), 20 );
		
		// Add an optional settings validation filter (recommended)
		add_filter( $this->wpsf->get_option_group() . '_settings_validate', array( &$this, 'validate_settings' ) );
	}

	/**
	 * Add settings page.
	 */
	public function add_settings_page() {
		$this->wpsf->add_settings_page( array(
			'parent_slug' => 'woocommerce',
			'page_title'  => __( 'Page Title', 'text-domain' ),
			'menu_title'  => __( 'menu Title', 'text-domain' ),
			'capability'  => 'manage_woocommerce',
		) );
	}

	/**
	 * Validate settings.
	 * 
	 * @param $input
	 *
	 * @return mixed
	 */
	public function validate_settings( $input ) {
		// Do your settings validation here
		// Same as $sanitize_callback from http://codex.wordpress.org/Function_Reference/register_setting
		return $input;
	}
}

$wpsf_test = new WPSFTest();
