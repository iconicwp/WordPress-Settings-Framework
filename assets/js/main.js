(function( $, document ) {

	var wpsf = {

		cache: function() {
			wpsf.els = {};
			wpsf.vars = {};

			wpsf.els.tab_links = $('.wpsf-nav__item-link');
			wpsf.els.submit_button = $( '.wpsf-button-submit' );
		},

		on_ready: function() {

			// on ready stuff here
			wpsf.cache();
			wpsf.trigger_dynamic_fields();
			wpsf.setup_groups();
			wpsf.tabs.watch();
			wpsf.watch_submit();
			wpsf.control_groups();
			wpsf.setup_visual_radio_checkbox_field();
			wpsf.importer.init();

			$( document.body ).on( 
				'change',
				'input, select, textarea, .wpsf-visual-field input[type="radio"], .wpsf-visual-field input[type="checkbox"]', 
				wpsf.control_groups
			);
		},

		/**
		 * Trigger dynamic fields
		 */
		trigger_dynamic_fields: function() {

			wpsf.setup_timepickers();
			wpsf.setup_datepickers();

		},

		/**
		 * Setup the main tabs for the settings page
		 */
		tabs: {
			/**
			 * Watch for tab clicks.
			 */
			watch: function() {
				var tab_id = wpsf.tabs.get_tab_id();

				if ( tab_id ) {
					wpsf.tabs.set_active_tab( tab_id );
				}

				wpsf.els.tab_links.on( 'click', function( e ) {
					// Show tab
					var tab_id = $( this ).attr( 'href' );

					wpsf.tabs.set_active_tab( tab_id );

					e.preventDefault();
				} );

				$( '.wsf-internal-link' ).click( wpsf.tabs.follow_link );
			},

			/**
			 * Is storage available.
			 */
			has_storage: 'undefined' !== typeof ( Storage ),
			
			/**
			 * Handle click on the Internal link.
			 * 
			 * Format of link is #tab-id|field-id. Field-id can be skipped.
			 * 
			 * @param {*} e
			 * @returns
			 */
			follow_link: function ( e ) {
				e.preventDefault();
				var href = $( this ).attr( 'href' );
				var tab_id, parts, element_id;

				if ( href.indexOf( '#tab-' ) != 0 ) {
					return;
				}

				// has "|" i.e. element ID.
				if ( href.indexOf( '|' ) > 0 ) {
					parts = href.split( '|' );
					tab_id = parts[ 0 ];
					element_id = parts[ 1 ];
				} else {
					tab_id = href;
				}

				wpsf.tabs.set_active_tab( tab_id );

				if ( element_id ) {
					$('html, body').animate({scrollTop: $(`#${element_id}`).offset().top - 100 }, 'fast');
				}
			},

			/**
			 * Store tab ID.
			 *
			 * @param tab_id
			 */
			set_tab_id: function( tab_id ) {
				if ( !wpsf.tabs.has_storage ) {
					return;
				}

				localStorage.setItem( wpsf.tabs.get_option_page() + '_wpsf_tab_id', tab_id );
			},

			/**
			 * Get tab ID.
			 *
			 * @returns {boolean}
			 */
			get_tab_id: function() {
				// If the tab id is specified in the URL hash, use that.
				if ( window.location.hash ) {
					// Check if hash is a tab.
					if ( $( `.wpsf-nav a[href="${window.location.hash}"]` ).length ) {
						return window.location.hash;
					}
				}

				if ( !wpsf.tabs.has_storage ) {
					return false;
				}

				return localStorage.getItem( wpsf.tabs.get_option_page() + '_wpsf_tab_id' );
			},

			/**
			 * Set active tab.
			 *
			 * @param tab_id
			 */
			set_active_tab: function( tab_id ) {
				var $tab = $( tab_id ),
					$tab_link = $( '.wpsf-nav__item-link[href="' + tab_id + '"]' );

				if ( $tab.length <= 0 || $tab_link.length <= 0 ) {
					// Reset to first available tab.
					$tab_link = $( '.wpsf-nav__item-link' ).first();
					tab_id = $tab_link.attr( 'href' );
					$tab = $( tab_id );
				}

				// Set tab link active class
				wpsf.els.tab_links.parent().removeClass( 'wpsf-nav__item--active' );
				$( 'a[href="' + tab_id + '"]' ).parent().addClass( 'wpsf-nav__item--active' );

				// Show tab
				$( '.wpsf-tab' ).removeClass( 'wpsf-tab--active' );
				$tab.addClass( 'wpsf-tab--active' );

				wpsf.tabs.set_tab_id( tab_id );
			},

			/**
			 * Get unique option page name.
			 *
			 * @returns {jQuery|string|undefined}
			 */
			get_option_page: function() {
				return $( 'input[name="option_page"]' ).val();
			}
		},

		/**
		 * Set up timepickers
		 */
		setup_timepickers: function() {

			$( '.timepicker' ).not( '.hasTimepicker' ).each( function() {

				var timepicker_args = $( this ).data( 'timepicker' );

				// It throws an error if empty string is passed.
				if ( '' === timepicker_args ) {
					timepicker_args = {};
				}

				$( this ).timepicker( timepicker_args );

			} );

		},

		/**
		 * Set up timepickers
		 */
		setup_datepickers: function() {
			$( document ).on( 'focus',  '.datepicker:not(.hasTimepicker)', function() {
				var datepicker_args = $( this ).data( 'datepicker' );

				$( this ).datepicker( datepicker_args );
			} );

			// Empty altField if datepicker field is emptied.
			$( document ).on( 'change', '.datepicker', function(){
				var datepicker = $( this ).data( 'datepicker' );

				if ( ! $( this ).val() && datepicker.settings && datepicker.settings.altField ) {
					$( datepicker.settings.altField ).val( '' );
				}
			});
		},

		/**
		 * Setup repeatable groups
		 */
		setup_groups: function() {
			wpsf.reindex_groups();

			// add row

			$( document ).on( 'click', '.wpsf-group__row-add', function() {

				var $group = $( this ).closest( '.wpsf-group' ),
					$row = $( this ).closest( '.wpsf-group__row' ),
					template_name = $( this ).data( 'template' ),
					$template = $( $( '#' + template_name ).html() );

				$template.find( '.wpsf-group__row-id' ).val( wpsf.generate_random_id() );

				$row.after( $template );

				wpsf.reindex_group( $group );

				wpsf.trigger_dynamic_fields();

				return false;

			} );

			// remove row

			$( document ).on( 'click', '.wpsf-group__row-remove', function() {

				var $group = jQuery( this ).closest( '.wpsf-group' ),
					$row = jQuery( this ).closest( '.wpsf-group__row' );

				$row.remove();

				wpsf.reindex_group( $group );

				return false;

			} );

		},

		/**
		 * Generate random ID.
		 *
		 * @returns {string}
		 */
		generate_random_id: function() {
			return (
				Number( String( Math.random() ).slice( 2 ) ) +
				Date.now() +
				Math.round( performance.now() )
			).toString( 36 );
		},

		/**
		 * Reindex all groups.
		 */
		reindex_groups: function() {
			var $groups = jQuery( '.wpsf-group' );

			if ( $groups.length <= 0 ) {
				return;
			}

			$groups.each( function( index, group ) {
				wpsf.reindex_group( jQuery( group ) );
			} );
		},

		/**
		 * Reindex a group of repeatable rows
		 *
		 * @param arr $group
		 */
		reindex_group: function( $group ) {
			var reindex_attributes = [ 'class', 'id', 'name', 'data-datepicker' ];
			
			if ( 1 === $group.find( ".wpsf-group__row" ).length ) {
				$group.find( ".wpsf-group__row-remove" ).hide();
			} else {
				$group.find( ".wpsf-group__row-remove" ).show();
			}

			$group.find( ".wpsf-group__row" ).each( function( index ) {

				$( this ).removeClass( 'alternate' );

				if ( index % 2 == 0 ) {
					$( this ).addClass( 'alternate' );
				}

				$( this ).find( "input" ).each( function() {
					var this_input = this,
						name = jQuery( this ).attr( 'name' );

					if ( typeof name !== typeof undefined && name !== false ) {
						$( this_input ).attr( 'name', name.replace( /\[\d+\]/, '[' + index + ']' ) );
					}

					$.each( this_input.attributes, function() {
						if ( this.name && this_input && $.inArray( this.name, reindex_attributes ) > -1 ) {
							$( this_input ).attr( this.name, this.value.replace( /\_\d+\_/, '_' + index + '_' ) );
						}
					} );
				} );

				$( this ).find( '.wpsf-group__row-index span' ).html( index );

			} );
		},

		/**
		 * Watch submit click.
		 */
		watch_submit: function() {
			wpsf.els.submit_button.on( 'click', function() {
				var $button = $( this ),
					$wrapper = $button.closest( '.wpsf-settings' ),
					$form = $wrapper.find( 'form' ).first();

				$form.submit();
			} );
		},

		/**
		 * Dynamic control groups.
		 */
		control_groups: function() {
			// If show if, hide by default.
			$( '.show-if' ).each( function( index ) {
				var element = $( this ),
				    parent_tag = element.parent().prop( 'nodeName' ).toLowerCase();
				

				// Field.
				if ( 'td' === parent_tag || 'label' === parent_tag || wpsf.is_visual_field( element ) ) {
					element.closest( 'tr' ).hide();

					wpsf.maybe_show_element( element, function() {
						element.closest( 'tr' ).show();
					} );
				}

				// Tab.
				if ( 'li' === parent_tag ) {
					element.closest( 'li' ).hide();

					wpsf.maybe_show_element( element, function() {
						element.closest( 'li' ).show();
					} );
				}

				// Section.
				if ( 'div' === parent_tag && ! wpsf.is_visual_field( element ) ) {
					element.prev().hide();
					element.next().hide();
					if ( element.next().hasClass( 'wpsf-section-description' ) ) {
						element.next().next().hide();
					}

					wpsf.maybe_show_element( element, function() {
						element.prev().show();
						element.next().show();
						if ( element.next().hasClass( 'wpsf-section-description' ) ) {
							element.next().next().show();
						}
					} );
				}
			} );

			// If hide if, show by default.
			$( '.hide-if' ).each( function( index ) {
				var element = $( this ),
				    parent_tag = element.parent().prop( 'nodeName' ).toLowerCase();

				// Field.
				if ( 'td' === parent_tag || 'label' === parent_tag || wpsf.is_visual_field( element ) ) {
					element.closest( 'tr' ).show();

					wpsf.maybe_hide_element( element, function() {
						element.closest( 'tr' ).hide();
					} );
				}

				// Tab.
				if ( 'li' === parent_tag ) {
					element.closest( 'li' ).show();

					wpsf.maybe_hide_element( element, function() {
						element.closest( 'li' ).hide();
					} );
				}

				// Section.
				if ( 'div' === parent_tag && ! wpsf.is_visual_field( element ) ) {
					element.prev().show();
					element.next().show();
					if ( element.next().hasClass( 'wpsf-section-description' ) ) {
						element.next().next().show();
					}

					wpsf.maybe_hide_element( element, function() {
						element.prev().hide();
						element.next().hide();
						if ( element.next().hasClass( 'wpsf-section-description' ) ) {
							element.next().next().hide();
						}
					} );
				}
			} );
		},
		
		/**
		 * Is the element part of a visual field?
		 * 
		 * @param {object} element Element.
		 */
		is_visual_field: function( element ) {
			return element.parent().hasClass( 'wpsf-visual-field__item-footer' );
		},

		/**
		 * Maybe Show Element.
		 * 
		 * @param {object} element Element.
		 * @param {function} callback Callback.
		 */
		maybe_show_element: function( element, callback ) {
			var classes = element.attr( 'class' ).split( /\s+/ );
			var controllers = classes.filter( function( item ) {
				return item.includes( 'show-if--' );
			});

			Array.from( controllers ).forEach( function( control_group ) {
				var item = control_group.replace( 'show-if--', '' );
				if ( item.includes( '&&' ) ) {
					var and_group = item.split( '&&' );
					var show_item = true;
					Array.from( and_group ).forEach( function( and_item ) {
						if ( ! wpsf.get_show_item_bool( show_item, and_item ) ) {
							show_item = false;
						}
					});

					if ( show_item ) {
						callback();
						return;
					}
				} else {
					var show_item = true;
					show_item = wpsf.get_show_item_bool( show_item, item );

					if ( show_item ) {
						callback();
						return;
					}
				}
			});
		},

		/**
		 * Maybe Hide Element.
		 * 
		 * @param {object} element Element.
		 * @param {function} callback Callback.
		 */
		maybe_hide_element: function( element, callback ) {
			var classes = element.attr( 'class' ).split( /\s+/ );
			var controllers = classes.filter( function( item ) {
				return item.includes( 'hide-if--' );
			});

			Array.from( controllers ).forEach( function( control_group ) {
				var item = control_group.replace( 'hide-if--', '' );
				if ( item.includes( '&&' ) ) {
					var and_group = item.split( '&&' );
					var hide_item = true;
					Array.from( and_group ).forEach( function( and_item ) {
						if ( ! wpsf.get_show_item_bool( hide_item, and_item ) ) {
							hide_item = false;
						}
					});

					if ( hide_item ) {
						callback();
						return;
					}
				} else {
					var hide_item = true;
					hide_item = wpsf.get_show_item_bool( hide_item, item );

					if ( hide_item ) {
						callback();
						return;
					}
				}
			});
		},

		/**
		 * Get Show Item Bool.
		 * 
		 * @param {bool} show Boolean.
		 * @param {object} item Element.
		 * @returns {bool}
		 */
		get_show_item_bool: function( show = true, item ) {
			var split = item.split( '===' );
			var control = split[0];
			var values = split[1].split( '||' );
			var control_value = wpsf.get_controller_value( control, values );

			if ( ! values.includes( control_value ) ) {
				show = ! show;
			}

			return show;
		},

		/** 
		 * Return the control value.
		 */
		get_controller_value: function( id, values ) {
			var control = $( '#' + id );

			// This may be an image_radio field.
			if ( ! control.length && values.length ) {
				control = $( '#' + id + '_' + values[0] );
			}

			if ( control.length && ( 'checkbox' === control.attr( 'type' ) || 'radio' === control.attr( 'type' ) ) ) {
				control = ( control.is( ':checked' ) ) ? control : false;
			}

			var value = ( control.length ) ? control.val() : 'undefined';

			if ( typeof value === 'undefined' ) {
				value = '';
			}

			return value.toString();
		},

		/**
		 * Add checked class when radio button changes.
		 */
		setup_visual_radio_checkbox_field: function() {
			var checked_class = 'wpsf-visual-field__item--checked';

			$( document ).on( 'change', '.wpsf-visual-field input[type="radio"], .wpsf-visual-field input[type="checkbox"]', function() {
				var $this = $( this ),
					$list = $this.closest( '.wpsf-visual-field' ),
					$list_item = $this.closest( '.wpsf-visual-field__item' ),
					$checked = $list.find( '.' + checked_class ),
					is_multi_select = $list.hasClass( 'wpsf-visual-field--image-checkboxes' );

				if ( is_multi_select ) {
					if ( $this.prop( 'checked' ) ) {
						$list_item.addClass( checked_class );
					} else {
						$list_item.removeClass( checked_class );
					}
				} else {
					$checked.removeClass( checked_class );
					$list_item.addClass( checked_class );
				}

			} );
		},

		/**
		 * Import related functions.
		 */
		importer: {
			init: function () {

				$( '.wpsf-import__button' ).click( function () {
					$( this ).parent().find( '.wpsf-import__file_field' ).trigger( 'click' );
				} );

				$( ".wpsf-import__file_field" ).change( function ( e ) {
					$this = $( this );
					$td = $this.closest( 'td' );

					var file_field = $this.get( 0 ),
						settings = "",
						wpsf_import_nonce = $td.find( '.wpsf_import_nonce' ).val();
						wpsf_import_option_group = $td.find( '.wpsf_import_option_group' ).val();
					
					
					if ( 'undefined' === typeof file_field.files[ 0 ] ) {
						alert( wpsf_vars.select_file );
						return;
					}

					if ( ! confirm( 'Are you sure you want to overrid existing setting?' ) ) {
						return;
					}

					wpsf.importer.read_file_text( file_field.files[ 0 ], function ( content ) {
						try {
							JSON.parse( content );
							settings = content;
						} catch {
							settings = false;
							alert( wpsf_vars.invalid_file );
						}

						if ( !settings ) {
							return;
						}
						
						$td.find( '.spinner' ).addClass( 'is-active' );
						// Run an ajax call to save settings.
						$.ajax( {
							url: 'admin-ajax.php',
							type: 'POST',
							data: {
								action: 'wpsf_import_settings',
								settings: settings,
								option_group: wpsf_import_option_group,
								_wpnonce: wpsf_import_nonce
							},
							success: function ( response ) {
								if ( response.success ) {
									location.reload();
								} else {
									alert( wpsf_vars.something_went_wrong );
								}

								$td.find( '.spinner' ).removeClass( 'is-active' );
							}
						} );
					} );
				} );
			},

			/**
			 * Read File text.
			 *
			 * @param string   File input. 
			 * @param finction Callback function. 
			 */
			read_file_text( file, callback ) {
				const reader = new FileReader();
				reader.readAsText(file);
				reader.onload = () => {
				  callback(reader.result);
				};
			}
		}
	};

	$( document ).ready( wpsf.on_ready );

}( jQuery, document ));
