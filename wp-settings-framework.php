<?php
/**
 * WordPress Settings Framework
 *
 * @link    https://github.com/gilbitron/WordPress-Settings-Framework
 * @version 1.6.11
 *
 * @package wordpress-settings-framework
 */

if ( ! class_exists( 'WordPressSettingsFramework' ) ) {
	/**
	 * WordPressSettingsFramework class
	 */
	class WordPressSettingsFramework {
		/**
		 * Settings wrapper.
		 *
		 * @var array
		 */
		private $settings_wrapper;

		/**
		 * Settings.
		 *
		 * @var array
		 */
		private $settings;

		/**
		 * Tabs.
		 *
		 * @var array
		 */
		private $tabs;

		/**
		 * Option group.
		 *
		 * @var string
		 */
		private $option_group;

		/**
		 * Settings page.
		 *
		 * @var array
		 */
		public $settings_page = array();

		/**
		 * Options path.
		 *
		 * @var string
		 */
		private $options_path;

		/**
		 * Options URL.
		 *
		 * @var string
		 */
		private $options_url;

		/**
		 * Setting defaults.
		 *
		 * @var array
		 */
		protected $setting_defaults = array(
			'id'           => 'default_field',
			'title'        => 'Default Field',
			'desc'         => '',
			'std'          => '',
			'type'         => 'text',
			'placeholder'  => '',
			'choices'      => array(),
			'class'        => '',
			'subfields'    => array(),
			'autocomplete' => '',
		);

		/**
		 * WordPressSettingsFramework constructor.
		 *
		 * @param null|string $settings_file Path to a settings file, or null if you pass the option_group manually and construct your settings with a filter.
		 * @param bool|string $option_group  Option group name, usually a short slug.
		 */
		public function __construct( $settings_file = null, $option_group = false ) {
			$this->option_group = $option_group;

			if ( $settings_file ) {
				if ( ! is_file( $settings_file ) ) {
					return;
				}

				require_once $settings_file;

				if ( ! $this->option_group ) {
					$this->option_group = preg_replace( '/[^a-z0-9]+/i', '', basename( $settings_file, '.php' ) );
				}
			}

			if ( empty( $this->option_group ) ) {
				return;
			}

			$this->options_path = plugin_dir_path( __FILE__ );
			$this->options_url  = plugin_dir_url( __FILE__ );

			$this->construct_settings();

			if ( is_admin() ) {
				global $pagenow;

				add_action( 'admin_init', array( $this, 'admin_init' ) );
				add_action( 'wpsf_do_settings_sections_' . $this->option_group, array( $this, 'do_tabless_settings_sections' ), 10 );

				if ( filter_input( INPUT_GET, 'page' ) && filter_input( INPUT_GET, 'page' ) === $this->settings_page['slug'] ) {
					if ( 'options-general.php' !== $pagenow ) {
						add_action( 'admin_notices', array( $this, 'admin_notices' ) );
					}
					add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
				}

				if ( $this->has_tabs() ) {
					add_action( 'wpsf_before_settings_' . $this->option_group, array( $this, 'tab_links' ) );

					remove_action( 'wpsf_do_settings_sections_' . $this->option_group, array( $this, 'do_tabless_settings_sections' ), 10 );
					add_action( 'wpsf_do_settings_sections_' . $this->option_group, array( $this, 'do_tabbed_settings_sections' ), 10 );
				}

				add_action( 'wp_ajax_wpsf_export_settings', array( $this, 'export_settings' ) );
				add_action( 'wp_ajax_wpsf_import_settings', array( $this, 'import_settings' ) );
			}
		}

		/**
		 * Construct Settings.
		 */
		public function construct_settings() {
			/**
			 * Filter: modify settings for a given option group.
			 *
			 * @filter wpsf_register_settings_<option_group>
			 * @since 1.6.9
			 * @param array
			 */
			$this->settings_wrapper = apply_filters( 'wpsf_register_settings_' . $this->option_group, array() );

			if ( ! is_array( $this->settings_wrapper ) ) {
				return new WP_Error( 'broke', esc_html__( 'WPSF settings must be an array', 'wpsf' ) );
			}

			// If "sections" is set, this settings group probably has tabs.
			if ( isset( $this->settings_wrapper['sections'] ) ) {
				$this->tabs     = ( isset( $this->settings_wrapper['tabs'] ) ) ? $this->settings_wrapper['tabs'] : array();
				$this->settings = $this->settings_wrapper['sections'];
				// If not, it's probably just an array of settings.
			} else {
				$this->settings = $this->settings_wrapper;
			}

			$this->settings_page['slug'] = sprintf( '%s-settings', str_replace( '_', '-', $this->option_group ) );
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
			register_setting( $this->option_group, $this->option_group . '_settings', array( $this, 'settings_validate' ) );
			$this->process_settings();
		}

		/**
		 * Add Settings Page
		 *
		 * @param array $args Settings page arguments.
		 */
		public function add_settings_page( $args ) {
			if ( ! $this->settings_page ) {
				return;
			}
			
			$defaults = array(
				'parent_slug' => false,
				'page_slug'   => '',
				'page_title'  => '',
				'menu_title'  => '',
				'capability'  => 'manage_options',
			);

			$args = wp_parse_args( $args, $defaults );

			$this->settings_page['title']      = $args['page_title'];
			$this->settings_page['capability'] = $args['capability'];

			if ( $args['parent_slug'] ) {
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
					array( $this, 'settings_page_content' ),
					/**
					 * Filter: modify icon URL for a given option group.
					 *
					 * @filter wpsf_menu_icon_url_<option_group>
					 * @since 1.6.9
					 * @param string
					 */
					apply_filters( 'wpsf_menu_icon_url_' . $this->option_group, '' ),
					/**
					 * Filter: modify menu position for a given option group.
					 *
					 * @filter wpsf_menu_position_<option_group>
					 * @since 1.6.9
					 * @param int|null
					 */
					apply_filters( 'wpsf_menu_position_' . $this->option_group, null )
				);
			}
		}

		/**
		 * Settings Page Content
		 */
		public function settings_page_content() {
			if ( ! current_user_can( $this->settings_page['capability'] ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wpsf' ) );
			}
			?>
			<div class="wpsf-settings wpsf-settings--<?php echo esc_attr( $this->option_group ); ?>">
				<?php $this->settings_header(); ?>
				<div class="wpsf-settings__content">
					<?php $this->settings(); ?>
				</div>
			</div>
			<?php
		}

		/**
		 * Settings Header.
		 */
		public function settings_header() {
			?>
			<div class="wpsf-settings__header">
				<h2>
					<?php
					global $allowedposttags;
					$protocols   = wp_allowed_protocols();
					$protocols[] = 'data';

					echo wp_kses(
						/**
						 * Filter: modify title for a given option group.
						 *
						 * @filter wpsf_title_<option_group>
						 * @since 1.6.9
						 * @param string $title Title for the group settings header.
						 */
						apply_filters( 'wpsf_title_' . $this->option_group, $this->settings_page['title'] ),
						$allowedposttags,
						$protocols
					);
					?>
				</h2>
				<?php
				/**
				 * Hook: execute a callback after the option group title.
				 *
				 * @hook wpsf_after_title_<option_group>
				 * @since 1.6.9
				 */
				do_action( 'wpsf_after_title_' . $this->option_group );
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
			// Scripts.
			$jqtimepicker_js_path = 'assets/vendor/jquery-timepicker/jquery.ui.timepicker.js';
			wp_register_script(
				'jquery-ui-timepicker',
				$this->options_url . $jqtimepicker_js_path,
				array( 'jquery', 'jquery-ui-core' ),
				filemtime( $this->options_path . $jqtimepicker_js_path ),
				true
			);

			$wpsf_js_path = 'assets/js/main.js';
			wp_register_script(
				'wpsf',
				$this->options_url . $wpsf_js_path,
				array( 'jquery' ),
				filemtime( $this->options_path . $wpsf_js_path ),
				true
			);

			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'farbtastic' );
			wp_enqueue_media();
			wp_enqueue_script( 'media-upload' );
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_script( 'jquery-ui-core' );
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_script( 'jquery-ui-timepicker' );
			wp_enqueue_script( 'wpsf' );

			$data = array(
				'select_file'          => esc_html__( 'Please select a file to import', 'wpsf' ),
				'invalid_file'         => esc_html__( 'Invalid file', 'wpsf' ),
				'something_went_wrong' => esc_html__( 'Something went wrong', 'wpsf' ),
			);
			wp_localize_script( 'wpsf', 'wpsf_vars', $data );

			// Styles.
			$jqtimepicker_css_path = 'assets/vendor/jquery-timepicker/jquery.ui.timepicker.css';
			wp_register_style(
				'jquery-ui-timepicker',
				$this->options_url . $jqtimepicker_css_path,
				array(),
				filemtime( $this->options_path . $jqtimepicker_css_path )
			);

			$wpsf_css_path = 'assets/css/main.css';
			wp_register_style(
				'wpsf',
				$this->options_url . $wpsf_css_path,
				array(),
				filemtime( $this->options_path . $wpsf_css_path )
			);

			$jqui_css_path = 'assets/vendor/jquery-ui/jquery-ui.css';
			wp_register_style(
				'jquery-ui-css',
				$this->options_url . $jqui_css_path,
				array(),
				filemtime( $this->options_path . $jqui_css_path )
			);

			wp_enqueue_style( 'farbtastic' );
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_style( 'jquery-ui-timepicker' );
			wp_enqueue_style( 'jquery-ui-css' );
			wp_enqueue_style( 'wpsf' );
		}

		/**
		 * Adds a filter for settings validation.
		 *
		 * @param mixed $input Input data.
		 *
		 * @return array
		 */
		public function settings_validate( $input ) {
			/**
			 * Filter: validate field input for a given option group.
			 *
			 * @filter <option_group>_settings_validate
			 * @since 1.6.9
			 * @param mixed
			 */
			return apply_filters( $this->option_group . '_settings_validate', $input );
		}

		/**
		 * Displays the "section_description" if specified in $this->settings
		 *
		 * @param array $args callback args from add_settings_section().
		 */
		public function section_intro( $args ) {
			if ( ! empty( $this->settings ) ) {
				foreach ( $this->settings as $section ) {
					if ( $section['section_id'] === $args['id'] ) {
						$render_class = '';

						$render_class .= self::add_show_hide_classes( $section );

						if ( $render_class ) {
							echo '<span class="' . esc_attr( $render_class ) . '"></span>';
						}
						if ( isset( $section['section_description'] ) && $section['section_description'] ) {
							echo '<div class="wpsf-section-description wpsf-section-description--' . esc_attr( $section['section_id'] ) . '">' . wp_kses_post( $section['section_description'] ) . '</div>';
						}
						break;
					}
				}
			}
		}

		/**
		 * Processes $this->settings and adds the sections and fields via the WordPress settings API
		 */
		private function process_settings() {
			if ( ! empty( $this->settings ) ) {
				usort( $this->settings, array( $this, 'sort_array' ) );

				foreach ( $this->settings as $section ) {
					if ( isset( $section['section_id'] ) && $section['section_id'] && isset( $section['section_title'] ) ) {
						$page_name = ( $this->has_tabs() ) ? sprintf( '%s_%s', $this->option_group, $section['tab_id'] ) : $this->option_group;

						add_settings_section( $section['section_id'], $section['section_title'], array( $this, 'section_intro' ), $page_name );

						if ( isset( $section['fields'] ) && is_array( $section['fields'] ) && ! empty( $section['fields'] ) ) {
							foreach ( $section['fields'] as $field ) {
								if ( isset( $field['id'] ) && $field['id'] && isset( $field['title'] ) ) {
									$tooltip = '';

									if ( isset( $field['link'] ) && is_array( $field['link'] ) ) {
										$link_url      = ( isset( $field['link']['url'] ) ) ? esc_html( $field['link']['url'] ) : '';
										$link_text     = ( isset( $field['link']['text'] ) ) ? esc_html( $field['link']['text'] ) : esc_html__( 'Learn More', 'wpsf' );
										$link_external = ( isset( $field['link']['external'] ) ) ? (bool) $field['link']['external'] : true;
										$link_type     = ( isset( $field['link']['type'] ) ) ? esc_attr( $field['link']['type'] ) : 'tooltip';
										$link_target   = ( $link_external ) ? ' target="_blank"' : '';

										if ( 'tooltip' === $link_type ) {
											$link_text = sprintf( '<i class="dashicons dashicons-info wpsf-link-icon" title="%s"><span class="screen-reader-text">%s</span></i>', $link_text, $link_text );
										}

										$link = ( $link_url ) ? sprintf( '<a class="wpsf-label__link" href="%s"%s>%s</a>', $link_url, $link_target, $link_text ) : '';

										if ( $link && 'tooltip' === $link_type ) {
											$tooltip = $link;
										} elseif ( $link ) {
											$field['subtitle'] .= ( empty( $field['subtitle'] ) ) ? $link : sprintf( '<br/><br/>%s', $link );
										}
									}

									$title = sprintf( '<span class="wpsf-label">%s %s</span>', $field['title'], $tooltip );

									if ( ! empty( $field['subtitle'] ) ) {
										$title .= sprintf( '<span class="wpsf-subtitle">%s</span>', $field['subtitle'] );
									}

									add_settings_field(
										$field['id'],
										$title,
										array( $this, 'generate_setting' ),
										$page_name,
										$section['section_id'],
										array(
											'section' => $section,
											'field'   => $field,
										)
									);
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
		 * @param array $a Sortable Array.
		 * @param array $b Sortable Array.
		 *
		 * @return array
		 */
		public function sort_array( $a, $b ) {
			if ( ! isset( $a['section_order'] ) ) {
				return 0;
			}

			return ( $a['section_order'] > $b['section_order'] ) ? 1 : 0;
		}

		/**
		 * Generates the HTML output of the settings fields
		 *
		 * @param array $args callback args from add_settings_field().
		 */
		public function generate_setting( $args ) {
			$section = $args['section'];
			/**
			 * Filter: filter the default setting values for a given option group.
			 *
			 * @filter wpsf_defaults_<option_group>
			 * @since 1.6.9
			 * @param mixed $setting_defaults Default values for settings.
			 */
			$this->setting_defaults = apply_filters( 'wpsf_defaults_' . $this->option_group, $this->setting_defaults );

			$args = wp_parse_args( $args['field'], $this->setting_defaults );

			$options = get_option( $this->option_group . '_settings' );

			$args['id']    = ( $this->has_tabs() ) ? sprintf( '%s_%s_%s', $section['tab_id'], $section['section_id'], $args['id'] ) : sprintf( '%s_%s', $section['section_id'], $args['id'] );
			$field_name    = isset( $args['name'] ) ? $args['name'] : $args['id'];
			$args['name']  = $this->generate_field_name( $field_name );
			$args['value'] = ( isset( $options[ $field_name ] ) ) ? $options[ $field_name ] : ( isset( $args['default'] ) ? $args['default'] : '' );

			$args['class'] .= self::add_show_hide_classes( $args );

			/**
			 * Hook: execute callback before a given group.
			 *
			 * @hook wpsf_before_field_<option_group>
			 * @since 1.6.9
			 */
			do_action( 'wpsf_before_field_' . $this->option_group );

			/**
			 * Hook: execute callback before a specific field in a given group.
			 *
			 * @hook wpsf_before_field_<option_group>_<field_id>
			 * @since 1.6.9
			 */
			do_action( 'wpsf_before_field_' . $this->option_group . '_' . $args['id'] );

			$this->do_field_method( $args );

			/**
			 * Hook: execute callback after a given group.
			 *
			 * @hook wpsf_after_field_<option_group>
			 * @since 1.6.9
			 */
			do_action( 'wpsf_after_field_' . $this->option_group );

			/**
			 * Hook: execute callback after a specific field in a given group.
			 *
			 * @hook wpsf_after_field_<option_group>_<field_id>
			 * @since 1.6.9
			 */
			do_action( 'wpsf_after_field_' . $this->option_group . '_' . $args['id'] );
		}

		/**
		 * Do field method, if it exists
		 *
		 * @param array $args Field arguments.
		 */
		public function do_field_method( $args ) {
			$generate_field_method = sprintf( 'generate_%s_field', $args['type'] );

			if ( method_exists( $this, $generate_field_method ) ) {
				$this->$generate_field_method( $args );
			}
		}

		/**
		 * Generate: Text field
		 *
		 * @param array $args Field arguments.
		 */
		public function generate_text_field( $args ) {
			$args['value'] = esc_attr( stripslashes( $args['value'] ) );

			echo '<input type="text" name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( $args['id'] ) . '" value="' . esc_attr( $args['value'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '" class="regular-text ' . esc_attr( $args['class'] ) . '" />';

			$this->generate_description( $args );
		}

		/**
		 * Generate: Hidden field.
		 *
		 * @param array $args Field arguments.
		 */
		public function generate_hidden_field( $args ) {
			$args['value'] = esc_attr( stripslashes( $args['value'] ) );

			echo '<input type="hidden" name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( $args['id'] ) . '" value="' . esc_attr( $args['value'] ) . '"  class="hidden-field ' . esc_attr( $args['class'] ) . '" />';
		}

		/**
		 * Generate: Number field
		 *
		 * @param array $args Field arguments.
		 */
		public function generate_number_field( $args ) {
			$args['value'] = esc_attr( stripslashes( $args['value'] ) );

			echo '<input type="number" name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( $args['id'] ) . '" value="' . esc_attr( $args['value'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '" class="regular-text ' . esc_attr( $args['class'] ) . '" />';

			$this->generate_description( $args );
		}

		/**
		 * Generate: Time field
		 *
		 * @param array $args Field arguments.
		 */
		public function generate_time_field( $args ) {
			$args['value'] = esc_attr( stripslashes( $args['value'] ) );

			$timepicker = ( ! empty( $args['timepicker'] ) ) ? htmlentities( wp_json_encode( $args['timepicker'] ) ) : null;

			echo '<input type="text" name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( $args['id'] ) . '" value="' . esc_attr( $args['value'] ) . '" class="timepicker regular-text ' . esc_attr( $args['class'] ) . '" data-timepicker="' . esc_attr( $timepicker ) . '" />';

			$this->generate_description( $args );
		}

		/**
		 * Generate: Date field
		 *
		 * @param array $args Field arguments.
		 */
		public function generate_date_field( $args ) {
			$args['value'] = esc_attr( stripslashes( $args['value'] ) );

			$datepicker = ( ! empty( $args['datepicker'] ) ) ? htmlentities( wp_json_encode( $args['datepicker'] ) ) : null;

			echo '<input type="text" name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( $args['id'] ) . '" value="' . esc_attr( $args['value'] ) . '" class="datepicker regular-text ' . esc_attr( $args['class'] ) . '" data-datepicker="' . esc_attr( $datepicker ) . '" />';

			$this->generate_description( $args );
		}

		/**
		 * Generate Export Field.
		 *
		 * @param array $args Field arguments.
		 */
		public function generate_export_field( $args ) {
			$args['value'] = esc_attr( stripslashes( $args['value'] ) );
			$args['value'] = empty( $args['value'] ) ? esc_html__( 'Export Settings', 'wpsf' ) : $args['value'];
			$option_group  = $this->option_group;
			$export_url    = site_url() . '/wp-admin/admin-ajax.php?action=wpsf_export_settings&_wpnonce=' . wp_create_nonce( 'wpsf_export_settings' ) . '&option_group=' . $option_group;

			echo '<a target=_blank href="' . esc_url( $export_url ) . '" class="button" name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( $args['id'] ) . '">' . esc_html( $args['value'] ) . '</a>';

			$options = get_option( $option_group . '_settings' );
			$this->generate_description( $args );
		}

		/**
		 * Generate Import Field.
		 *
		 * @param array $args Field rguments.
		 */
		public function generate_import_field( $args ) {
			$args['value'] = esc_attr( stripslashes( $args['value'] ) );
			$args['value'] = empty( $args['value'] ) ? esc_html__( 'Import Settings', 'wpsf' ) : $args['value'];
			$option_group  = $this->option_group;

			echo sprintf(
				'
				<div class="wpsf-import">
					<div class="wpsf-import__false_btn">
						<input type="file" name="wpsf-import-field" class="wpsf-import__file_field" id="%s" accept=".json"/>
						<button type="button" name="wpsf_import_button" class="button wpsf-import__button" id="%s">%s</button>
						<input type="hidden" class="wpsf_import_nonce" value="%s"></input>
						<input type="hidden" class="wpsf_import_option_group" value="%s"></input>
					</div>
					<span class="spinner"></span>
				</div>',
				esc_attr( $args['id'] ),
				esc_attr( $args['id'] ),
				esc_attr( $args['value'] ),
				esc_attr( wp_create_nonce( 'wpsf_import_settings' ) ),
				esc_attr( $this->option_group )
			);

			$this->generate_description( $args );
		}

		/**
		 * Generate: Group field
		 *
		 * Generates a table of subfields, and a javascript template for create new repeatable rows
		 *
		 * @param array $args Field arguments.
		 */
		public function generate_group_field( $args ) {
			$value     = (array) $args['value'];
			$row_count = ( ! empty( $value ) ) ? count( $value ) : 1;

			echo '<table class="widefat wpsf-group" cellspacing="0">';

			echo '<tbody>';

			for ( $row = 0; $row < $row_count; $row ++ ) {
				// @codingStandardsIgnoreStart
				echo $this->generate_group_row_template( $args, false, $row );
				// @codingStandardsIgnoreEnd
			}

			echo '</tbody>';

			echo '</table>';

			printf(
				'<script type="text/html" id="%s_template">%s</script>',
				esc_attr( $args['id'] ),
				// @codingStandardsIgnoreStart
				$this->generate_group_row_template( $args, true )
				// @codingStandardsIgnoreEnd
			);

			$this->generate_description( $args );
		}


		/**
		 * Generate Image Checkboxes.
		 *
		 * @param array $args Field arguments.
		 *
		 * @return void
		 */
		public function generate_image_checkboxes_field( $args ) {

			echo '<input type="hidden" name="' . esc_attr( $args['name'] ) . '" value="0" />';

			echo '<ul class="wpsf-visual-field wpsf-visual-field--image-checkboxes wpsf-visual-field--grid wpsf-visual-field--cols">';

			foreach ( $args['choices'] as $value => $choice ) {
				$field_id      = sprintf( '%s_%s', $args['id'], $value );
				$is_checked    = is_array( $args['value'] ) && in_array( $value, $args['value'], true );
				$checked_class = $is_checked ? 'wpsf-visual-field__item--checked' : '';

				echo sprintf(
					'<li class="wpsf-visual-field__item %s">
						<label>
							<div class="wpsf-visual-field-image-radio__img_wrap">
								<img src="%s">
							</div>
							<div class="wpsf-visual-field__item-footer">
								<input type="checkbox" name="%s[]" id="%s" value="%s" class="%s" %s>
								<span class="wpsf-visual-field__item-text">%s</span>
							</div>
						</label>
					</li>',
					esc_attr( $checked_class ),
					esc_url( $choice['image'] ),
					esc_attr( $args['name'] ),
					esc_attr( $field_id ),
					esc_attr( $value ),
					esc_attr( $args['class'] ),
					checked( true, $is_checked, false ),
					esc_attr( $choice['text'] )
				);
			}

			echo '</ul>';

			$this->generate_description( $args );
		}

		/**
		 * Generate: Image Radio field
		 *
		 * @param array $args Field arguments.
		 */
		public function generate_image_radio_field( $args ) {
			$args['value'] = esc_html( esc_attr( $args['value'] ) );
			$count         = count( $args['choices'] );

			echo sprintf( '<ul class="wpsf-visual-field wpsf-visual-field--image-radio wpsf-visual-field--grid wpsf-visual-field--cols wpsf-visual-field--col-%s ">', esc_attr( $count ) );

			foreach ( $args['choices'] as $value => $choice ) {
				$field_id = sprintf( '%s_%s', $args['id'], $value );
				$checked  = $value === $args['value'] ? 'checked="checked"' : '';

				echo sprintf(
					'<li class="wpsf-visual-field__item %s">				
						<label>
							<div class="wpsf-visual-field-image-radio__img_wrap">
								<img src="%s">
							</div>
							<div class="wpsf-visual-field__item-footer">
								<input type="radio" name="%s" id="%s" value="%s" class="%s" %s>
								<span class="wpsf-visual-field__item-text">%s</span>
							</div>
						</label>
					</li>',
					( $checked ? 'wpsf-visual-field__item--checked' : '' ),
					esc_attr( $choice['image'] ),
					esc_attr( $args['name'] ),
					esc_attr( $field_id ),
					esc_attr( $value ),
					esc_attr( $args['class'] ),
					esc_html( $checked ),
					esc_attr( $choice['text'] )
				);
			}
			echo '</ul>';

			$this->generate_description( $args );
		}

		/**
		 * Generate group row template
		 *
		 * @param array $args  Field arguments.
		 * @param bool  $blank Blank values.
		 * @param int   $row   Iterator.
		 *
		 * @return string|bool
		 */
		public function generate_group_row_template( $args, $blank = false, $row = 0 ) {
			$row_template = false;
			$row_id       = ( ! empty( $args['value'][ $row ]['row_id'] ) ) ? $args['value'][ $row ]['row_id'] : $row;
			$row_id_value = ( $blank ) ? '' : $row_id;

			if ( $args['subfields'] ) {
				$row_class = ( 0 === $row % 2 ) ? 'alternate' : '';

				$row_template .= sprintf( '<tr class="wpsf-group__row %s">', $row_class );

				$row_template .= sprintf( '<td class="wpsf-group__row-index"><span>%d</span></td>', $row );

				$row_template .= '<td class="wpsf-group__row-fields">';

				$row_template .= '<input type="hidden" class="wpsf-group__row-id" name="' . sprintf( '%s[%d][row_id]', esc_attr( $args['name'] ), esc_attr( $row ) ) . '" value="' . esc_attr( $row_id_value ) . '" />';

				foreach ( $args['subfields'] as $subfield ) {
					$subfield = wp_parse_args( $subfield, $this->setting_defaults );

					$subfield['value'] = ( $blank ) ? '' : ( isset( $args['value'][ $row ][ $subfield['id'] ] ) ? $args['value'][ $row ][ $subfield['id'] ] : '' );
					$subfield['name']  = sprintf( '%s[%d][%s]', $args['name'], $row, $subfield['id'] );
					$subfield['id']    = sprintf( '%s_%d_%s', $args['id'], $row, $subfield['id'] );

					$class = sprintf( 'wpsf-group__field-wrapper--%s', $subfield['type'] );

					$row_template .= sprintf( '<div class="wpsf-group__field-wrapper %s">', $class );
					$row_template .= sprintf( '<label for="%s" class="wpsf-group__field-label">%s</label>', $subfield['id'], $subfield['title'] );

					ob_start();
					$this->do_field_method( $subfield );
					$row_template .= ob_get_clean();

					$row_template .= '</div>';
				}

				$row_template .= '</td>';

				$row_template .= '<td class="wpsf-group__row-actions">';

				$row_template .= sprintf( '<a href="javascript: void(0);" class="wpsf-group__row-add" data-template="%s_template"><span class="dashicons dashicons-plus-alt"></span></a>', $args['id'] );
				$row_template .= '<a href="javascript: void(0);" class="wpsf-group__row-remove"><span class="dashicons dashicons-trash"></span></a>';

				$row_template .= '</td>';

				$row_template .= '</tr>';
			}

			return $row_template;
		}

		/**
		 * Generate: Select field
		 *
		 * @param array $args Field rguments.
		 */
		public function generate_select_field( $args ) {
			$is_multiple = isset( $args['multiple'] ) && filter_var( $args['multiple'], FILTER_VALIDATE_BOOLEAN );
			$multiple    = $is_multiple ? ' multiple="true" ' : ' ';

			if ( $is_multiple ) {
				$args['name'] .= '[]';
			}

			$values = (array) $args['value'];
			$values = array_map( 'strval', $values );

			echo '<select ' . esc_html( $multiple ) . ' name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( $args['id'] ) . '" class="' . esc_attr( $args['class'] ) . '" >';

			foreach ( $args['choices'] as $value => $text ) {
				if ( is_array( $text ) ) {
					echo sprintf( '<optgroup label="%s">', esc_html( $value ) );
					foreach ( $text as $group_value => $group_text ) {
						$selected = in_array( (string) $group_value, $values, true ) ? ' selected="selected" ' : '';
						echo sprintf( '<option value="%s" %s>%s</option>', esc_attr( $group_value ), esc_html( $selected ), esc_html( $group_text ) );
					}
					echo '</optgroup>';
					continue;
				}

				$selected = in_array( (string) $value, $values, true ) ? ' selected="selected" ' : '';
				echo sprintf( '<option value="%s" %s>%s</option>', esc_attr( $value ), esc_html( $selected ), esc_html( $text ) );
			}

			echo '</select>';

			$this->generate_description( $args );
		}

		/**
		 * Generate: Password field
		 *
		 * @param array $args Field arguments.
		 */
		public function generate_password_field( $args ) {
			$args['value'] = esc_attr( stripslashes( $args['value'] ) );

			echo '<input type="password" name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( $args['id'] ) . '" value="' . esc_attr( $args['value'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '" class="regular-text ' . esc_attr( $args['class'] ) . '" autocomplete="' . esc_attr( $args['autocomplete'] ) . '"/>';

			$this->generate_description( $args );
		}

		/**
		 * Generate: Textarea field
		 *
		 * @param array $args Field arguments.
		 */
		public function generate_textarea_field( $args ) {
			$args['value'] = esc_html( esc_attr( $args['value'] ) );

			echo '<textarea name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( $args['id'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '" rows="5" cols="60" class="' . esc_attr( $args['class'] ) . '">' . esc_html( $args['value'] ) . '</textarea>';

			$this->generate_description( $args );
		}

		/**
		 * Generate: Radio field
		 *
		 * @param array $args Field arguments.
		 */
		public function generate_radio_field( $args ) {
			$args['value'] = esc_html( esc_attr( $args['value'] ) );

			foreach ( $args['choices'] as $value => $text ) {
				$field_id = sprintf( '%s_%s', $args['id'], $value );
				$checked  = ( $value === $args['value'] ) ? 'checked="checked"' : '';

				echo sprintf( '<label><input type="radio" name="%s" id="%s" value="%s" class="%s" %s> %s</label><br />', esc_attr( $args['name'] ), esc_attr( $field_id ), esc_html( $value ), esc_attr( $args['class'] ), esc_html( $checked ), esc_html( $text ) );
			}

			$this->generate_description( $args );
		}

		/**
		 * Generate: Checkbox field
		 *
		 * @param array $args Field arguments.
		 */
		public function generate_checkbox_field( $args ) {
			$args['value'] = esc_attr( stripslashes( $args['value'] ) );
			$checked       = ( $args['value'] ) ? 'checked="checked"' : '';

			echo '<input type="hidden" name="' . esc_attr( $args['name'] ) . '" value="0" />';
			echo '<label><input type="checkbox" name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( $args['id'] ) . '" value="1" class="' . esc_attr( $args['class'] ) . '" ' . esc_html( $checked ) . '> ' . esc_attr( $args['desc'] ) . '</label>';
		}

		/**
		 * Generate: Toggle field
		 *
		 * @param array $args Field arguments.
		 */
		public function generate_toggle_field( $args ) {
			$args['value'] = esc_attr( stripslashes( $args['value'] ) );
			$checked       = ( $args['value'] ) ? 'checked="checked"' : '';

			echo '<input type="hidden" name="' . esc_attr( $args['name'] ) . '" value="0" />';
			echo '<label class="switch"><input type="checkbox" name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( $args['id'] ) . '" value="1" class="' . esc_attr( $args['class'] ) . '" ' . esc_html( $checked ) . '> ' . esc_html( $args['desc'] ) . '<span class="slider"></span></label>';
		}

		/**
		 * Generate: Checkboxes field
		 *
		 * @param array $args Field arguments.
		 */
		public function generate_checkboxes_field( $args ) {
			echo '<input type="hidden" name="' . esc_attr( $args['name'] ) . '" value="0" />';

			echo '<ul class="wpsf-list wpsf-list--checkboxes">';

			foreach ( $args['choices'] as $value => $text ) {
				$checked  = ( is_array( $args['value'] ) && in_array( strval( $value ), array_map( 'strval', $args['value'] ), true ) ) ? 'checked="checked"' : '';
				$field_id = sprintf( '%s_%s', $args['id'], $value );

				echo sprintf( '<li><label><input type="checkbox" name="%s[]" id="%s" value="%s" class="%s" %s> %s</label></li>', esc_attr( $args['name'] ), esc_attr( $field_id ), esc_html( $value ), esc_attr( $args['class'] ), esc_html( $checked ), esc_html( $text ) );
			}

			echo '</ul>';

			$this->generate_description( $args );
		}

		/**
		 * Generate: Color field
		 *
		 * @param array $args Field arguments.
		 */
		public function generate_color_field( $args ) {
			$color_picker_id = sprintf( '%s_cp', $args['id'] );
			$args['value']   = esc_attr( stripslashes( $args['value'] ) );

			echo '<div style="position:relative;">';

			echo sprintf( '<input type="text" name="%s" id="%s" value="%s" class="%s">', esc_attr( $args['name'] ), esc_attr( $args['id'] ), esc_attr( $args['value'] ), esc_attr( $args['class'] ) );

			echo sprintf( '<div id="%s" style="position:absolute;top:0;left:190px;background:#fff;z-index:9999;"></div>', esc_attr( $color_picker_id ) );

			$this->generate_description( $args );

			echo '<script type="text/javascript">
                jQuery(document).ready(function($){
                    var colorPicker = $("#' . esc_attr( $color_picker_id ) . '");
                    colorPicker.farbtastic("#' . esc_attr( $args['id'] ) . '");
                    colorPicker.hide();
                    $("#' . esc_attr( $args['id'] ) . '").on("focus", function(){
                        colorPicker.show();
                    });
                    $("#' . esc_attr( $args['id'] ) . '").on("blur", function(){
                        colorPicker.hide();
                        if($(this).val() == "") $(this).val("#");
                    });
                });
                </script>';

			echo '</div>';
		}

		/**
		 * Generate: File field
		 *
		 * @param array $args Field arguments.
		 */
		public function generate_file_field( $args ) {
			$args['value'] = esc_attr( $args['value'] );
			$button_id     = sprintf( '%s_button', $args['id'] );

			echo sprintf( '<input type="text" name="%s" id="%s" value="%s" class="regular-text %s"> ', esc_attr( $args['name'] ), esc_attr( $args['id'] ), esc_html( $args['value'] ), esc_attr( $args['class'] ) );

			echo sprintf( '<input type="button" class="button wpsf-browse" id="%s" value="%s" />', esc_attr( $button_id ), esc_html__( 'Browse', 'wpsf' ) );
			?>
			<script type='text/javascript'>
				jQuery( document ).ready( function( $ ) {

					// Uploading files
					var file_frame;
					var wp_media_post_id = wp.media.model.settings.post.id; // Store the old id.
					var set_to_post_id = 0;

					jQuery( document.body ).on('click', '#<?php echo esc_attr( $button_id ); ?>', function( event ){

						event.preventDefault();

						// If the media frame already exists, reopen it.
						if ( file_frame ) {
							// Set the post ID to what we want
							file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
							// Open frame
							file_frame.open();
							return;
						} else {
							// Set the wp.media post id so the uploader grabs the ID we want when initialised.
							wp.media.model.settings.post.id = set_to_post_id;
						}

						// Create the media frame.
						file_frame = wp.media.frames.file_frame = wp.media({
							title: '<?php echo esc_html__( 'Select a image to upload', 'wpsf' ); ?>',
							button: {
								text: '<?php echo esc_html__( 'Use this image', 'wpsf' ); ?>',
							},
							multiple: false	// Set to true to allow multiple files to be selected
						});

						// When an image is selected, run a callback.
						file_frame.on( 'select', function() {
							// We set multiple to false so only get one image from the uploader
							attachment = file_frame.state().get('selection').first().toJSON();

							// Do something with attachment.id and/or attachment.url here
							$( '#image-preview' ).attr( 'src', attachment.url ).css( 'width', 'auto' );
							$( '#image_attachment_id' ).val( attachment.id );
							$( '#<?php echo esc_attr( $args['id'] ); ?>' ).val( attachment.url );

							// Restore the main post ID
							wp.media.model.settings.post.id = wp_media_post_id;
						});

						// Finally, open the modal
						file_frame.open();
					});

					// Restore the main ID when the add media button is pressed
					jQuery( 'a.add_media' ).on( 'click', function() {
						wp.media.model.settings.post.id = wp_media_post_id;
					});
				});
				</script>
			<?php
		}

		/**
		 * Generate: Editor field
		 *
		 * @param array $args Field arguments.
		 */
		public function generate_editor_field( $args ) {
			$settings                  = ( isset( $args['editor_settings'] ) && is_array( $args['editor_settings'] ) ) ? $args['editor_settings'] : array();
			$settings['textarea_name'] = $args['name'];

			wp_editor( $args['value'], $args['id'], $settings );

			$this->generate_description( $args );
		}

		/**
		 * Generate: Code editor field
		 *
		 * @param array $args Field arguments.
		 */
		public function generate_code_editor_field( $args ) {
			printf(
				'<textarea
					name="%s"
					id="%s"
					placeholder="%s"
					rows="5"
					cols="60"
					class="%s"
				>%s</textarea>',
				esc_attr( $args['name'] ),
				esc_attr( $args['id'] ),
				esc_attr( $args['placeholder'] ),
				esc_attr( $args['class'] ),
				esc_html( $args['value'] )
			);

			$settings = wp_enqueue_code_editor( array( 'type' => esc_attr( $args['mimetype'] ) ) );

			wp_add_inline_script(
				'code-editor',
				sprintf(
					'jQuery( function() { wp.codeEditor.initialize( "%s", %s ); } );',
					esc_attr( $args['id'] ),
					wp_json_encode( $settings )
				)
			);

			$this->generate_description( $args );
		}

		/**
		 * Generate: Custom field
		 *
		 * @param array $args Field arguments.
		 */
		public function generate_custom_field( $args ) {
			if ( isset( $args['output'] ) && is_callable( $args['output'] ) ) {
				call_user_func( $args['output'], $args );
				return;
			}

			// @codingStandardsIgnoreStart
			echo ( isset( $args['output'] ) ) ? $args['output'] : $args['default']; // This output isn't easily escaped.
			// @codingStandardsIgnoreEnd
		}

		/**
		 * Generate: Multi Inputs field
		 *
		 * @param array $args Field arguments.
		 */
		public function generate_multiinputs_field( $args ) {
			$field_titles = array_keys( $args['default'] );
			$values       = array_values( $args['value'] );

			echo '<div class="wpsf-multifields">';

			$i = 0;
			$c = count( $values );
			while ( $i < $c ) :

				$field_id = sprintf( '%s_%s', $args['id'], $i );
				$value    = esc_attr( stripslashes( $values[ $i ] ) );

				echo '<div class="wpsf-multifields__field">';
				echo '<input type="text" name="' . esc_attr( $args['name'] ) . '[]" id="' . esc_attr( $field_id ) . '" value="' . esc_attr( $value ) . '" class="regular-text ' . esc_attr( $args['class'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '" />';
				echo '<br><span>' . esc_html( $field_titles[ $i ] ) . '</span>';
				echo '</div>';

				$i ++;
endwhile;

			echo '</div>';

			$this->generate_description( $args );
		}

		/**
		 * Generate: Field ID
		 *
		 * @param mixed $id Field ID.
		 *
		 * @return string
		 */
		public function generate_field_name( $id ) {
			return sprintf( '%s_settings[%s]', $this->option_group, $id );
		}

/**
		 * Generate: Description
		 *
		 * @param mixed $description Field description.
		 */
		public function generate_description( $args ) {
			$classes      = 'wpsf-description';
			$description  = ( ! empty( $args['desc'] ) ) ? $args['desc'] : false;
			$descriptions = array();

			// Add the main description.
			if ( $description ) {
				$descriptions[] = array(
					'classes'     => $classes,
					'value'       => $args['value'],
					'description' => $description,
				);
			}

			// Output any conditional descriptions that exist.
			if ( 'select' === $args['type'] && ! empty( $args['conditional_desc'] ) && is_array( $args['conditional_desc'] ) ) {

				foreach ( $args['conditional_desc'] as $value => $conditional_description ) {

					if ( $conditional_description ) {
						// Add a class to hide descriptions for other values.
						if ( $args['value'] !== $value ) {
							$classes .= ' wpsf-hide-description';
						}

						$descriptions[] = array(
							'classes'     => $classes,
							'value'       => $value,
							'description' => $conditional_description,
						);
					}
				}
			}

			// Output all descriptions.
			foreach ( $descriptions as $description_data ) {
				printf(
					'<p class="%s" data-value="%s">%s</p>',
					esc_attr( $description_data['classes'] ),
					esc_attr( $description_data['value'] ),
					wp_kses_post( $description_data['description'] )
				);
			}
		}

		/**
		 * Output the settings form
		 */
		public function settings() {
			/**
			 * Hook: execute callback before the settings form for a given group.
			 *
			 * @hook wpsf_before_settings_<option_group>
			 * @since 1.6.9
			 */
			do_action( 'wpsf_before_settings_' . $this->option_group );
			?>
			<form action="options.php" method="post" novalidate enctype="multipart/form-data">
				<?php
				/**
				 * Hook: execute callback before the settings fields for a given group.
				 *
				 * @hook wpsf_before_settings_fields_<option_group>
				 * @since 1.6.9
				 */
				do_action( 'wpsf_before_settings_fields_' . $this->option_group );
				?>
				<?php settings_fields( $this->option_group ); ?>

				<?php
				/**
				 * Hook: execute callback to output the settings sections for a given group.
				 *
				 * @hook wpsf_do_settings_sections_<option_group>
				 * @since 1.6.9
				 */
				do_action( 'wpsf_do_settings_sections_' . $this->option_group );
				?>

				<?php
				/**
				 * Filter: control whether the save changes button should be visible or not for a given option group.
				 *
				 * @filter wpsf_show_save_changes_button_<option_group>
				 * @since 1.6.9
				 * @param boolean
				 */
				if ( apply_filters( 'wpsf_show_save_changes_button_' . $this->option_group, true ) ) {
					?>
					<p class="submit">
						<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
					</p>
				<?php } ?>
			</form>
			<?php
			/**
			 * Hook: execute callback after the settings form for a given group.
			 *
			 * @hook wpsf_after_settings_<option_group>
			 * @since 1.6.9
			 */
			do_action( 'wpsf_after_settings_' . $this->option_group );
		}

		/**
		 * Helper: Get Settings
		 *
		 * @return array
		 */
		public function get_settings() {
			$settings_name = $this->option_group . '_settings';

			static $settings = array();

			if ( isset( $settings[ $settings_name ] ) ) {
				return $settings[ $settings_name ];
			}

			$saved_settings             = get_option( $this->option_group . '_settings' );
			$settings[ $settings_name ] = array();

			if ( ! $this->settings ) {
				return $settings[ $settings_name ];
			}
			
			foreach ( $this->settings as $section ) {
				if ( empty( $section['fields'] ) ) {
					continue;
				}

				foreach ( $section['fields'] as $field ) {
					if ( ! empty( $field['default'] ) && is_array( $field['default'] ) ) {
						$field['default'] = array_values( $field['default'] );
					}

					// If a field name override has been provided, use it.
					if ( ! empty( $field['name'] ) ) {
						$setting_key = $field['name'];
					} else {
						$setting_key = ( $this->has_tabs() ) ? sprintf( '%s_%s_%s', $section['tab_id'], $section['section_id'], $field['id'] ) : sprintf( '%s_%s', $section['section_id'], $field['id'] );
					}
					
					if ( isset( $saved_settings[ $setting_key ] ) ) {
						$settings[ $settings_name ][ $setting_key ] = $saved_settings[ $setting_key ];
					} else {
						$settings[ $settings_name ][ $setting_key ] = ( isset( $field['default'] ) ) ? $field['default'] : false;
					}
				}
			}

			return $settings[ $settings_name ];
		}

		/**
		 * Tabless Settings sections
		 */
		public function do_tabless_settings_sections() {
			?>
			<div class="wpsf-section wpsf-tabless">
				<?php do_settings_sections( $this->option_group ); ?>
			</div>
			<?php
		}

		/**
		 * Tabbed Settings sections
		 */
		public function do_tabbed_settings_sections() {
			$i = 0;
			foreach ( $this->tabs as $tab_data ) {
				?>
				<div id="tab-<?php echo esc_attr( $tab_data['id'] ); ?>" class="wpsf-section wpsf-tab wpsf-tab--<?php echo esc_attr( $tab_data['id'] ); ?> <?php
				if ( 0 === $i ) {
					echo 'wpsf-tab--active';
				}
				?>
				">
					<div class="postbox">
						<?php do_settings_sections( sprintf( '%s_%s', $this->option_group, $tab_data['id'] ) ); ?>
					</div>
				</div>
				<?php
				$i ++;
			}
		}

		/**
		 * Output the tab links
		 */
		public function tab_links() {
			/**
			 * Filter: control whether the tab links should be visible or not for a given option group.
			 *
			 * @filter wpsf_show_tab_links_<option_group>
			 * @since 1.6.9
			 * @param boolean
			 */
			if ( ! apply_filters( 'wpsf_show_tab_links_' . $this->option_group, true ) ) {
				return;
			}

			/**
			 * Hook: execute callback before the tab links for a given option group.
			 *
			 * @hook wpsf_before_tab_links_<option_group>
			 * @since 1.6.9
			 */
			do_action( 'wpsf_before_tab_links_' . $this->option_group );
			?>
			<ul class="wpsf-nav">
				<?php
				$i = 0;
				foreach ( $this->tabs as $tab_data ) {
					if ( ! $this->tab_has_settings( $tab_data['id'] ) ) {
						continue;
					}

					if ( ! isset( $tab_data['class'] ) ) {
						$tab_data['class'] = '';
					}

					$tab_data['class'] .= self::add_show_hide_classes( $tab_data );

					$active = ( 0 === $i ) ? 'wpsf-nav__item--active' : '';
					?>
					<li class="wpsf-nav__item <?php echo esc_attr( $active ); ?>">
						<a class="wpsf-nav__item-link <?php echo esc_attr( $tab_data['class'] ); ?>" href="#tab-<?php echo esc_attr( $tab_data['id'] ); ?>"><?php echo wp_kses_post( $tab_data['title'] ); ?></a>
					</li>
					<?php
					$i ++;
				}
				?>
				<li class="wpsf-nav__item wpsf-nav__item--last">
					<input type="submit" class="button-primary wpsf-button-submit" value="<?php esc_attr_e( 'Save Changes' ); ?>">
				</li>
			</ul>

			<?php // Add this here so notices are moved. ?>
			<div class="wrap wpsf-notices"><h2>&nbsp;</h2></div>
			<?php
			/**
			 * Hook: execute callback after the tab links for a given option group.
			 *
			 * @hook wpsf_after_tab_links_<option_group>
			 * @since 1.6.9
			 */
			do_action( 'wpsf_after_tab_links_' . $this->option_group );
		}

		/**
		 * Does this tab have settings?
		 *
		 * @param string $tab_id Tab ID.
		 *
		 * @return bool
		 */
		public function tab_has_settings( $tab_id ) {
			if ( empty( $this->settings ) ) {
				return false;
			}

			foreach ( $this->settings as $settings_section ) {
				if ( $tab_id !== $settings_section['tab_id'] ) {
					continue;
				}

				return true;
			}

			return false;
		}

		/**
		 * Check if this settings instance has tabs
		 */
		public function has_tabs() {
			if ( ! empty( $this->tabs ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Add Show Hide Classes.
		 *
		 * @param array  $args Field arguments.
		 * @param string $type Type.
		 */
		public static function add_show_hide_classes( $args, $type = 'show_if' ) {
			$class = '';
			$slug  = ' ' . str_replace( '_', '-', $type );
			if ( isset( $args[ $type ] ) && is_array( $args[ $type ] ) ) {
				$class .= $slug;
				foreach ( $args[ $type ] as $condition ) {
					if ( isset( $condition['field'] ) && $condition['value'] ) {
						$value_string = '';
						foreach ( $condition['value'] as $value ) {
							if ( ! empty( $value_string ) ) {
								$value_string .= '||';
							}
							$value_string .= $value;
						}

						if ( ! empty( $value_string ) ) {
							$class .= $slug . '--' . $condition['field'] . '===' . $value_string;
						}
					} else {
						$and_string = '';
						foreach ( $condition as $and_condition ) {
							if ( ! isset( $and_condition['field'] ) || ! isset( $and_condition['value'] ) ) {
								continue;
							}

							if ( ! empty( $and_string ) ) {
								$and_string .= '&&';
							}

							$value_string = '';
							foreach ( $and_condition['value'] as $value ) {
								if ( ! empty( $value_string ) ) {
									$value_string .= '||';
								}
								$value_string .= $value;
							}

							if ( ! empty( $value_string ) ) {
								$and_string .= $and_condition['field'] . '===' . $value_string;
							}
						}

						if ( ! empty( $and_string ) ) {
							$class .= $slug . '--' . $and_string;
						}
					}
				}
			}

			// Run the function again with hide if.
			if ( 'hide_if' !== $type ) {
				$class .= self::add_show_hide_classes( $args, 'hide_if' );
			}

			return $class;
		}

		/**
		 * Handle export settings action.
		 */
		public static function export_settings() {
			$_wpnonce     = filter_input( INPUT_GET, '_wpnonce' );
			$option_group = filter_input( INPUT_GET, 'option_group' );

			if ( empty( $_wpnonce ) || ! wp_verify_nonce( $_wpnonce, 'wpsf_export_settings' ) ) {
				wp_die( esc_html__( 'Action failed.', 'wpsf' ) );
			}

			if ( empty( $option_group ) ) {
				wp_die( esc_html__( 'No option group specified.', 'wpsf' ) );
			}

			$options = get_option( $option_group . '_settings' );

			header( 'Content-Disposition: attachment; filename=wpsf-settings-' . $option_group . '.json' );

			wp_send_json( $options );
		}

		/**
		 * Import settings.
		 */
		public function import_settings() {
			$_wpnonce     = filter_input( INPUT_POST, '_wpnonce' );
			$option_group = filter_input( INPUT_POST, 'option_group' );
			$settings     = filter_input( INPUT_POST, 'settings' );

			if ( $option_group !== $this->option_group ) {
				return;
			}

			// verify nonce.
			if ( empty( $_wpnonce ) || ! wp_verify_nonce( $_wpnonce, 'wpsf_import_settings' ) ) {
				wp_send_json_error();
			}

			// check if $settings is a valid json.
			if ( ! is_string( $settings ) || ! is_array( json_decode( $settings, true ) ) ) {
				wp_send_json_error();
			}

			$settings_data = json_decode( $settings, true );
			update_option( $option_group . '_settings', $settings_data );

			wp_send_json_success();
		}
	}
}

if ( ! function_exists( 'wpsf_get_setting' ) ) {
	/**
	 * Get a setting from an option group
	 *
	 * @param string $option_group Option group.
	 * @param string $section_id   May also be prefixed with tab ID.
	 * @param string $field_id     Field ID.
	 *
	 * @return mixed
	 */
	function wpsf_get_setting( $option_group, $section_id, $field_id ) {
		$options = get_option( $option_group . '_settings' );
		if ( isset( $options[ $section_id . '_' . $field_id ] ) ) {
			return $options[ $section_id . '_' . $field_id ];
		}

		return false;
	}
}

if ( ! function_exists( 'wpsf_delete_settings' ) ) {
	/**
	 * Delete all the saved settings from a settings file/option group
	 *
	 * @param string $option_group Option group.
	 */
	function wpsf_delete_settings( $option_group ) {
		delete_option( $option_group . '_settings' );
	}
}
