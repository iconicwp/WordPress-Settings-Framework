<?php
/**
 * WordPress Settings Framework
 *
 * @author Gilbert Pellegrom, James Kemp
 * @link https://github.com/gilbitron/WordPress-Settings-Framework
 * @version 1.6.1
 * @license MIT
 */

if( !class_exists('WordPressSettingsFramework') ){
    /**
     * WordPressSettingsFramework class
     */
    class WordPressSettingsFramework {

        /**
         * @access private
         * @var array
         */
        private $settings_wrapper;
        
        /**
         * @access private
         * @var array
         */
        private $settings;
        
        /**
         * @access private
         * @var array
         */
        private $tabs;

        /**
         * @access private
         * @var string
         */
        private $option_group;
        
        /**
         * @access private
         * @var array
         */
        private $settings_page = array();

        /**
         * @access protected
         * @var array
         */
        protected $setting_defaults = array(
            'id'     	  => 'default_field',
            'title'  	  => 'Default Field',
            'desc'  	  => '',
            'std'    	  => '',
            'type'   	  => 'text',
            'placeholder' => '',
            'choices'     => array(),
            'class'       => ''
        );

        /**
         * Constructor
         *
         * @param string path to settings file
         * @param string optional "option_group" override
         */
        public function __construct( $settings_file, $option_group = '' ) {
            
            if( is_admin() ) {
                
                global $pagenow;
                
                if( !is_file($settings_file) ) return;
                require_once( $settings_file );
    
                $this->option_group = preg_replace("/[^a-z0-9]+/i", "", basename($settings_file, '.php'));
                if( $option_group ) $this->option_group = $option_group;
                
                $this->construct_settings();
                
                add_action( 'admin_init',                                     array( $this, 'admin_init') );                
                add_action( 'wpsf_do_settings_sections_'.$this->option_group, array( $this, 'do_tabless_settings_sections'), 10 );
                
                if( isset( $_GET['page'] ) && $_GET['page'] === $this->settings_page['slug'] ) {
                    
                    if( $pagenow !== "options-general.php" ) add_action( 'admin_notices', array( $this, 'admin_notices') );
                    add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts') );
                
                }
                
                if( $this->has_tabs() ) {
                    
                    add_action( 'wpsf_before_settings_'.$this->option_group,         array( $this, 'tab_links' ) );
                    add_action( 'wpsf_before_settings_'.$this->option_group,         array( $this, 'tab_styles' ) );
                    add_action( 'wpsf_before_settings_'.$this->option_group,         array( $this, 'tab_scripts' ) );
                    
                    remove_action( 'wpsf_do_settings_sections_'.$this->option_group, array( $this, 'do_tabless_settings_sections'), 10 );
                    add_action( 'wpsf_do_settings_sections_'.$this->option_group,    array( $this, 'do_tabbed_settings_sections'), 10 );
                    
                }
            
            }
            
        }
        
        /**
         * Construct Settings
         *
         * @return array
         */
        public function construct_settings() {
            
            $this->settings_wrapper = array();
            $this->settings_wrapper = apply_filters( 'wpsf_register_settings_'.$this->option_group, $this->settings_wrapper );
            
            if( !is_array($this->settings_wrapper) ){
                return new WP_Error( 'broke', __( 'WPSF settings must be an array' ) );
            }
            
            // If "sections" is set, this settings group probably has tabs
            if( isset( $this->settings_wrapper['sections'] ) ) {
            
                $this->tabs = (isset( $this->settings_wrapper['tabs'] )) ? $this->settings_wrapper['tabs'] : array();
                $this->settings = $this->settings_wrapper['sections'];
            
            // If not, it's probably just an array of settings
            } else {
                
                $this->settings = $this->settings_wrapper;
                
            }
            
            $this->settings_page['slug'] = sprintf( '%s-settings', str_replace('_', '-', $this->option_group ) );
            
        }

        /**
         * Get the option group for this instance
         *
         * @return string the "option_group"
         */
        public function get_option_group() {
            
            return $this->option_group;
            
        }

        /**
         * Registers the internal WordPress settings
         */
        public function admin_init() {
            
    		register_setting( $this->option_group, $this->option_group .'_settings', array( $this, 'settings_validate') );
    		$this->process_settings();
    		
    	}
    	
    	/**
    	 * Add Settings Page
    	 *
    	 * @param array $args
    	 */
    	 
        public function add_settings_page( $args ) {
            
            $defaults = array(
                'parent_slug' => false,
                'page_slug'   => "",
                'page_title'  => "",
                'menu_title'  => "",
                'capability'  => 'manage_options'
            );
            
            $args = wp_parse_args( $args, $defaults );
            
            $this->settings_page['title'] = $args['page_title'];
            
            if( $args['parent_slug'] ) {
            
                add_submenu_page( 
                    $args['parent_slug'], 
                    $this->settings_page['title'], 
                    $args['menu_title'], 
                    $args['capability'], 
                    $this->settings_page['slug'], 
                    array( $this, 'settings_page_content' ) 
                );
            
            } else {
                
                add_menu_page(
                    $this->settings_page['title'], 
                    $args['menu_title'], 
                    $args['capability'], 
                    $this->settings_page['slug'], 
                    array( $this, 'settings_page_content' ) 
                );
                
            }
            
        }
        
        /**
         * Settings Page Content
         */
         
        public function settings_page_content() {            
            ?>
    		<div class="wrap">
    			<div id="icon-options-general" class="icon32"></div>
    			<h2><?php echo $this->settings_page['title']; ?></h2>
    			<?php    			
    			// Output your settings form
    			$this->settings();
    			?>
    		</div>
    		<?php
            
        }

        /**
         * Displays any errors from the WordPress settings API
         */
        public function admin_notices() {
            
        	settings_errors();
        	
    	}

    	/**
         * Enqueue scripts and styles
         */
    	public function admin_enqueue_scripts() {
        	
            wp_enqueue_style('farbtastic');
            wp_enqueue_style('thickbox');

            wp_enqueue_script('jquery');
            wp_enqueue_script('farbtastic');
            wp_enqueue_script('media-upload');
            wp_enqueue_script('thickbox');
            
    	}

    	/**
         * Adds a filter for settings validation
         *
         * @param array the un-validated settings
         * @return array the validated settings
         */
    	public function settings_validate( $input ) {
        	
    		return apply_filters( $this->option_group .'_settings_validate', $input );
    		
    	}

    	/**
         * Displays the "section_description" if specified in $this->settings
         *
         * @param array callback args from add_settings_section()
         */
    	public function section_intro( $args ) {
        	
        	if(!empty($this->settings)){
            	
        		foreach($this->settings as $section){
            		
                    if($section['section_id'] == $args['id']){
                        
                        if(isset($section['section_description']) && $section['section_description']) echo '<p class="wpsf-section-description">'. $section['section_description'] .'</p>';
                        break;
                        
                    }
                    
        		}
        		
            }
            
    	}

    	/**
         * Processes $this->settings and adds the sections and fields via the WordPress settings API
         */
    	private function process_settings() {
        	
        	if( !empty($this->settings) ){
            	
        	    usort($this->settings, array( $this, 'sort_array'));
        	    
        		foreach( $this->settings as $section ){
            		
            		if( isset($section['section_id']) && $section['section_id'] && isset($section['section_title']) ){
                		
                		$page_name = ( $this->has_tabs() ) ? sprintf( '%s_%s', $this->option_group, $section['tab_id'] ) : $this->option_group;
                		
                		add_settings_section( $section['section_id'], $section['section_title'], array( $this, 'section_intro'), $page_name );
                		
                		if( isset($section['fields']) && is_array($section['fields']) && !empty($section['fields']) ){
                    		
                    		foreach( $section['fields'] as $field ){
                        		
                        		if( isset($field['id']) && $field['id'] && isset($field['title']) ){
                            		                            		
                        		    add_settings_field( $field['id'], $field['title'], array( $this, 'generate_setting'), $page_name, $section['section_id'], array('section' => $section, 'field' => $field) );
                        		    
                        		}
                    		}
                    		
                		}
                		
            		}
            		
        		}
        		
    		}
    		
    	}

    	/**
         * Usort callback. Sorts $this->settings by "section_order"
         *
         * @param mixed section order a
         * @param mixed section order b
         * @return int order
         */
    	public function sort_array( $a, $b ) {
        	
        	return $a['section_order'] > $b['section_order'];
        	
    	}

    	/**
         * Generates the HTML output of the settings fields
         *
         * @param array callback args from add_settings_field()
         */
    	public function generate_setting( $args ) {
        	
    	    $section = $args['section'];
        	$this->setting_defaults = apply_filters( 'wpsf_defaults_'.$this->option_group, $this->setting_defaults );
        	extract( wp_parse_args( $args['field'], $this->setting_defaults ) );

        	$options = get_option( $this->option_group .'_settings' );
        	$el_id = sprintf( '%s_%s', $section['section_id'], $id );
        	$val = (isset($options[$el_id])) ? $options[$el_id] : $std;

        	do_action( 'wpsf_before_field_'.$this->option_group );
        	do_action( 'wpsf_before_field__'.$this->option_group. $el_id );
    		switch( $type ){
    		    case 'text':
    		        $val = esc_attr(stripslashes($val));
    		        echo '<input type="text" name="'. $this->option_group .'_settings['. $el_id .']" id="'. $el_id .'" value="'. $val .'" placeholder="'. $placeholder .'" class="regular-text '. $class .'" />';
    		        if($desc)  echo '<p class="description">'. $desc .'</p>';
    		        break;
                case 'password':
                    $val = esc_attr(stripslashes($val));
                    echo '<input type="password" name="'. $this->option_group .'_settings['. $el_id .']" id="'. $el_id .'" value="'. $val .'" placeholder="'. $placeholder .'" class="regular-text '. $class .'" />';
                    if($desc)  echo '<p class="description">'. $desc .'</p>';
                    break;
    		    case 'textarea':
    		        $val = esc_html(stripslashes($val));
    		        echo '<textarea name="'. $this->option_group .'_settings['. $el_id .']" id="'. $el_id .'" placeholder="'. $placeholder .'" rows="5" cols="60" class="'. $class .'">'. $val .'</textarea>';
    		        if($desc)  echo '<p class="description">'. $desc .'</p>';
    		        break;
    		    case 'select':
    		        $val = esc_html(esc_attr($val));
    		        echo '<select name="'. $this->option_group .'_settings['. $el_id .']" id="'. $el_id .'" class="'. $class .'">';
    		        foreach($choices as $ckey=>$cval){
        		        echo '<option value="'. $ckey .'"'. (($ckey == $val) ? ' selected="selected"' : '') .'>'. $cval .'</option>';
    		        }
    		        echo '</select>';
    		        if($desc)  echo '<p class="description">'. $desc .'</p>';
    		        break;
    		    case 'radio':
    		        $val = esc_html(esc_attr($val));
    		        foreach($choices as $ckey=>$cval){
        		        echo '<label><input type="radio" name="'. $this->option_group .'_settings['. $el_id .']" id="'. $el_id .'_'. $ckey .'" value="'. $ckey .'" class="'. $class .'"'. (($ckey == $val) ? ' checked="checked"' : '') .' /> '. $cval .'</label><br />';
    		        }
    		        if($desc)  echo '<p class="description">'. $desc .'</p>';
    		        break;
    		    case 'checkbox':
    		        $val = esc_attr(stripslashes($val));
    		        echo '<input type="hidden" name="'. $this->option_group .'_settings['. $el_id .']" value="0" />';
    		        echo '<label><input type="checkbox" name="'. $this->option_group .'_settings['. $el_id .']" id="'. $el_id .'" value="1" class="'. $class .'"'. (($val) ? ' checked="checked"' : '') .' /> '. $desc .'</label>';
    		        break;
    		    case 'checkboxes':
    		        foreach($choices as $ckey=>$cval){
    		            $val = '';
    		            if(isset($options[$el_id .'_'. $ckey])) $val = $options[$el_id .'_'. $ckey];
    		            elseif(is_array($std) && in_array($ckey, $std)) $val = $ckey;
    		            $val = esc_html(esc_attr($val));
        		        echo '<input type="hidden" name="'. $this->option_group .'_settings['. $el_id .'_'. $ckey .']" value="0" />';
        		        echo '<label><input type="checkbox" name="'. $this->option_group .'_settings['. $el_id .'_'. $ckey .']" id="'. $el_id .'_'. $ckey .'" value="'. $ckey .'" class="'. $class .'"'. (($ckey == $val) ? ' checked="checked"' : '') .' /> '. $cval .'</label><br />';
    		        }
    		        if($desc)  echo '<p class="description">'. $desc .'</p>';
    		        break;
    		    case 'color':
                    $val = esc_attr(stripslashes($val));
                    echo '<div style="position:relative;">';
    		        echo '<input type="text" name="'. $this->option_group .'_settings['. $el_id .']" id="'. $el_id .'" value="'. $val .'" class="'. $class .'" />';
    		        echo '<div id="'. $el_id .'_cp" style="position:absolute;top:0;left:190px;background:#fff;z-index:9999;"></div>';
    		        if($desc)  echo '<p class="description">'. $desc .'</p>';
    		        echo '<script type="text/javascript">
    		        jQuery(document).ready(function($){
                        var colorPicker = $("#'. $el_id .'_cp");
                        colorPicker.farbtastic("#'. $el_id .'");
                        colorPicker.hide();
                        $("#'. $el_id .'").live("focus", function(){
                            colorPicker.show();
                        });
                        $("#'. $el_id .'").live("blur", function(){
                            colorPicker.hide();
                            if($(this).val() == "") $(this).val("#");
                        });
                    });
                    </script></div>';
    		        break;
    		    case 'file':
                    $val = esc_attr($val);
    		        echo '<input type="text" name="'. $this->option_group .'_settings['. $el_id .']" id="'. $el_id .'" value="'. $val .'" class="regular-text '. $class .'" /> ';
                    echo '<input type="button" class="button wpsf-browse" id="'. $el_id .'_button" value="Browse" />';
                    echo '<script type="text/javascript">
                    jQuery(document).ready(function($){
                		$("#'. $el_id .'_button").click(function() {
                			tb_show("", "media-upload.php?post_id=0&amp;type=image&amp;TB_iframe=true");
                			window.original_send_to_editor = window.send_to_editor;
                        	window.send_to_editor = function(html) {
                        		var imgurl = $("img",html).attr("src");
                        		$("#'. $el_id .'").val(imgurl);
                        		tb_remove();
                        		window.send_to_editor = window.original_send_to_editor;
                        	};
                			return false;
                		});
                    });
                    </script>';
                    break;
                case 'editor':
    		        wp_editor( $val, $el_id, array( 'textarea_name' => $this->option_group .'_settings['. $el_id .']' ) );
    		        if($desc)  echo '<p class="description">'. $desc .'</p>';
    		        break;
    		    case 'custom':
    		        echo $std;
    		        break;
        		default:
        		    break;
    		}
    		do_action( 'wpsf_after_field_'.$this->option_group );
        	do_action( 'wpsf_after_field__'.$this->option_group. $el_id );
        	
    	}

    	/**
         * Output the settings form
         */
        public function settings() {
            
            do_action( 'wpsf_before_settings_'.$this->option_group );
            ?>
            <form action="options.php" method="post">
                <?php do_action( 'wpsf_before_settings_fields_'.$this->option_group ); ?>
                <?php settings_fields( $this->option_group ); ?>
                
                <?php do_action( 'wpsf_do_settings_sections_'.$this->option_group ); ?>
                
        		<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ); ?>" /></p>
			</form>
    		<?php
    		do_action( 'wpsf_after_settings_'.$this->option_group );
    		
        }
        
        /**
         * Tabless Settings sections
         */
         
        public function do_tabless_settings_sections() {
            
            do_settings_sections( $this->option_group );
            
        }
        
        /**
         * Tabbed Settings sections
         */
         
        public function do_tabbed_settings_sections() {
            
            $i = 0; 
            foreach ( $this->tabs as $tab_data ) {
                ?>
            	<div id="tab-<?php echo $tab_data['id']; ?>" class="wpsf-tab wpsf-tab--<?php echo $tab_data['id']; ?> <?php if($i == 0) echo 'wpsf-tab--active'; ?>">
            		<div class="postbox">
            			<?php do_settings_sections( sprintf( '%s_%s', $this->option_group, $tab_data['id'] ) ); ?>
            		</div>
            	</div>
            	<?php 
                $i++; 
            }
                        
        }
        
        /**
         * Output the tab links
         */
        public function tab_links() {
            
            do_action( 'wpsf_before_tab_links_'.$this->option_group );
		
		    screen_icon();
		    ?>
		    <h2 class="nav-tab-wrapper">
    		    <?php
                $i = 0; 
                foreach ( $this->tabs as $tab_data ) {
    		        $active = $i == 0 ? 'nav-tab-active' : ''; 
    		        ?>
    		        <a class="nav-tab wpsf-tab-link <?php echo $active; ?>" href="#tab-<?php echo $tab_data['id']; ?>"><?php echo $tab_data['title']; ?></a>
                    <?php 
                $i++;
                }
    		    ?>
		    </h2>            
            <?php 
            do_action( 'wpsf_after_tab_links_'.$this->option_group );
            
        }
        
        /**
         * Output Tab Styles
         */
        public function tab_styles() {
            ?>
            <style type="text/css">
                
                .nav-tab-wrapper {
                    min-height: 35px;
                }
                
                .wpsf-tab {
                    display: none;
                }
                
                .wpsf-tab--active {
                    display: block;
                }
                
                    .wpsf-tab .postbox {
                        margin: 20px 0;
                    }
                    
                    .wpsf-tab .postbox h3 {
                        padding: 8px 2%;
                        border: none;
                        margin-top: 25px;
                        background: #333333;
                        color: #ffffff;
                        -webkit-font-smoothing: antialiased;
                        -moz-font-smoothing: antialiased;
                        -o-font-smoothing: antialiased;
                        font-smoothing: antialiased;
                        font-size: 1.25em;
                    }
                    
                    .wpsf-tab .postbox h3:first-child {
                        margin-top: 0;
                    }
                    
                    .js .wpsf-tab .postbox h3 {
                        cursor: default;
                    }
                    
                    .wpsf-tab .postbox table.form-table,
                    .wpsf-tab .wpsf-section-description {
                        margin: 0 2%;
                        width: 96%;
                    }
                    
                    .wpsf-tab .postbox table.form-table {
                        margin-bottom: 20px;
                    }
                    
                    .wpsf-tab .wpsf-section-description {
                        margin-top: 20px;
                        margin-bottom: 20px;
                        padding-bottom: 20px;
                        border-bottom: 1px solid #eeeeee;
                    }
                
            </style>
            <?php
        }
        
        /**
         * Output Tab Scripts
         */
        public function tab_scripts() {
            ?>
            <script>
                (function($, document) {
    
                    var wpsf = {
                        
                        cache: function() {
                            wpsf.els = {};
                            wpsf.vars = {};
                            
                            // common elements
                            wpsf.els.tab_links = $('.wpsf-tab-link');
                            
                        },
                 
                        on_ready: function() {
                            
                            // on ready stuff here
                            wpsf.cache();
                            wpsf.setup_tabs();
                            
                        },
                     
                        setup_tabs: function() {
                            
                            wpsf.els.tab_links.on('click', function(){
		
                        		// Set tab link active class
                        		wpsf.els.tab_links.removeClass('nav-tab-active');
                        		$(this).addClass('nav-tab-active');
                        		
                        		// Show tab
                        		var tab_id = $(this).attr('href');
                        		
                        		$('.wpsf-tab').removeClass('wpsf-tab--active');
                        		$(tab_id).addClass('wpsf-tab--active');
                        		
                            	return false;
                            	
                        	});
                            
                        }
                     
                    };
                    
                	$(document).ready( wpsf.on_ready() );
                
                }(jQuery, document));
            </script>
            <?php
        }
        
        /**
         * Output the opening tab wrapper
         */        
        public function open_tab_wrapper( $section ) {
            echo '<pre>'; print_r($section['tab_id']); echo '</pre>'; 
        }
        
        /**
         * Check if this settings instance has tabs
         */
        public function has_tabs() {
            
            if( !empty( $this->tabs ) )
                return true;
                
            return false;
            
        }

    }
}

if( !function_exists('wpsf_get_settings') ){
    
    /**
     * Get the settings from a settings file/option group
     *
     * @param string option group id
     * @return array settings
     */
    function wpsf_get_settings( $option_group ){
        return get_option( $option_group .'_settings' );
    }
    
}

if( !function_exists('wpsf_get_setting') ){
    
    /**
     * Get a setting from an option group
     *
     * @param string option group id
     * @param string section id
     * @param string field id
     * @return mixed setting or false if no setting exists
     */
    function wpsf_get_setting( $option_group, $section_id, $field_id ){
        $options = get_option( $option_group .'_settings' );
        if(isset($options[$option_group .'_'. $section_id .'_'. $field_id])){
            return $options[$option_group .'_'. $section_id .'_'. $field_id];
        }
        return false;
    }
    
}

if( !function_exists('wpsf_delete_settings') ){
    
    /**
     * Delete all the saved settings from a settings file/option group
     *
     * @param string option group id
     */
    function wpsf_delete_settings( $option_group ){
        delete_option( $option_group .'_settings' );
    }
    
}
