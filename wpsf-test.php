<?php
/*
Plugin Name: WP Settings Framework Example
Description: An example of the WP Settings Framework in action.
Version: 1.6.0
Author: Gilbert Pellegrom
Author URI: http://dev7studios.com
*/

class WPSFTest {

    private $plugin_path;
    private $wpsf;

    function __construct()
    {
        $this->plugin_path = plugin_dir_path( __FILE__ );
        add_action( 'admin_menu', array( $this, 'init_settings' ), 99 );

        // Include and create a new WordPressSettingsFramework
        require_once( $this->plugin_path .'wp-settings-framework.php' );
        $this->wpsf = new WordPressSettingsFramework( $this->plugin_path .'settings/example-settings.php', 'my_example_settings' );
        
        // Add an optional settings validation filter (recommended)
        add_filter( $this->wpsf->get_option_group() .'_settings_validate', array(&$this, 'validate_settings') );
    }

    public function init_settings() {
        
        $this->wpsf->add_settings_page( array(
            'parent_slug' => 'options-general.php',
            'page_title'  => __( 'WPSF Settings' ),
            'menu_title'  => __( 'WPSF' )
        ) );
        
    }

    function validate_settings( $input ) {
    	// Do your settings validation here
	// Same as $sanitize_callback from http://codex.wordpress.org/Function_Reference/register_setting
    	return $input;
    }

}

$wpsf_test = new WPSFTest();
