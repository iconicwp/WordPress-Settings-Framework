<?php
/*
Plugin Name: WP Settings Framework Example
Description: An example of the WP Settings Framework in action.
Version: 1.1
Author: Gilbert Pellegrom
Author URI: http://dev7studios.com
*/

class WPSFTest {

    private $plugin_path;
    private $plugin_url;
    private $l10n;
    private $wpsf;

    function __construct() 
    {	
        $this->plugin_path = plugin_dir_path( __FILE__ );
        $this->plugin_url = plugin_dir_url( __FILE__ );
        $this->l10n = 'wp-settings-framework';
        add_action( 'admin_menu', array(&$this, 'admin_menu'), 99 );
        
        // Include and create a new WordPressSettingsFramework
        require_once( $this->plugin_path .'wp-settings-framework.php' );
        $this->wpsf = new WordPressSettingsFramework( $this->plugin_path .'settings/settings-general.php' );
        // Add an optional settings validation filter (recommended)
        add_filter( $this->wpsf->get_option_group() .'_settings_validate', array(&$this, 'validate_settings') );
    }
    
    function admin_menu()
    {
        $page_hook = add_menu_page( __( 'WPSF', $this->l10n ), __( 'WPSF', $this->l10n ), 'update_core', 'wpsf', array(&$this, 'settings_page') );
        add_submenu_page( 'wpsf', __( 'Settings', $this->l10n ), __( 'Settings', $this->l10n ), 'update_core', 'wpsf', array(&$this, 'settings_page') );
    }
    
    function settings_page()
	{
	    // Your settings page
	    ?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"></div>
			<h2>WP Settings Framework Example</h2>
			<?php 
			// Output your settings form
			$this->wpsf->settings(); 
			?>
		</div>
		<?php
		
		// Get settings
		//$settings = wpsf_get_settings( $this->plugin_path .'settings/settings-general.php' );
		//echo '<pre>'.print_r($settings,true).'</pre>';
		
		// Get individual setting
		//$setting = wpsf_get_setting( wpsf_get_option_group( $this->plugin_path .'settings/settings-general.php' ), 'general', 'text' );
		//var_dump($setting);
	}
	
	function validate_settings( $input )
	{
	    // Do your settings validation here
	    // Same as $sanitize_callback from http://codex.wordpress.org/Function_Reference/register_setting
    	return $input;
	}

}
new WPSFTest();

?>