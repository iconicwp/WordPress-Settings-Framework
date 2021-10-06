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
			},

			/**
			 * Is storage available.
			 */
			has_storage: 'undefined' !== typeof (Storage),

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
				var $tab = $( tab_id );

				if ( $tab.length <= 0 ) {
					return;
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
			// Hide all controls that only show if a certain value is set.
			$( '.control-group__show-if' ).closest( 'tr' ).hide();
			$( '.tab-control-group__show-if' ).closest( 'li' ).hide();

			// Show all controls that only hide if a certain value is set.
			$( '.control-group__hide-if' ).closest( 'tr' ).show();
			$( '.tab-control-group__hide-if' ).closest( 'li' ).show();

			var section_control_show = $( '.section-control-group__show-if' );
			var section_control_hide = $( '.section-control-group__hide-if' );
			
			if ( section_control_show ) {
				section_control_show.prev().hide();
				section_control_show.next().hide();
				if ( section_control_show.next().hasClass( 'wpsf-section-description' ) ) {
					section_control_show.next().next().hide();
				}
			}

			if ( section_control_hide ) {
				section_control_hide.prev().show();
				section_control_hide.next().show();
				if ( section_control_hide.next().hasClass( 'wpsf-section-description' ) ) {
					section_control_hide.next().next().show();
				}
			}

			// Loop and show if the value is set.
			$( '*[class*="control-group__show-if--"]' ).each( function() {
				wpsf.show_hide( this, true );
			});

			$( '*[class*="tab-control-group__show-if--"]' ).each( function() {
				wpsf.show_hide( this, true, 'tab' );
			});

			$( '*[class*="section-control-group__show-if--"]' ).each( function() {
				wpsf.show_hide( this, true, 'section' );
			});

			// Loop and hide if the value is set.
			$( '*[class*="control-group__hide-if--"]' ).each( function() {
				wpsf.show_hide( this, false );
			});

			$( '*[class*="tab-control-group__hide-if--"]' ).each( function() {
				wpsf.show_hide( this, false, 'tab' );
			});

			$( '*[class*="section-control-group__hide-if--"]' ).each( function() {
				wpsf.show_hide( this, false, 'section' );
			});

			$( document.body ).on( 'change', '.control-group__controller, .tab-control-group__controller, .section-control-group__controller', wpsf.control_groups );
		},

		/**
		 * Show or hide control.
		 */
		show_hide: function( control, isShow, type ) {
			var prefix = type ? type + '-' : '';
			var parent = 'tab' === type ? 'li' : 'tr';
			var showHide = isShow ? 'show' : 'hide';

			var classes = control.className.split( /\s+/ );
			var controller = classes.filter( function( item ) {
				return item.includes( prefix + 'control-group--' );
			})[0];

			var values = classes.filter( function( item ) {
				return item.includes( prefix + 'control-group__' + showHide + '-if--' );
			}).map( function( item) {
				return item.replace( prefix + 'control-group__' + showHide + '-if--', '' );
			});

			var controllerValue = wpsf.get_controller_value( $( '.' + prefix + 'control-group__controller.' + controller ), controller, type );
			
			if ( values.includes( controllerValue ) ) {
				if ( 'section' === type ) {
					if ( isShow ) {
						$( control ).prev().show();
						$( control ).next().show();
						if ( $( control ).next().hasClass( 'wpsf-section-description' ) ) {
							$( control ).next().next().show();
						}
					} else {
						$( control ).prev().hide();
						$( control ).next().hide();
						if ( $( control ).next().hasClass( 'wpsf-section-description' ) ) {
							$( control ).next().next().hide();
						}
					}
				} else {
					if ( isShow ) {
						$( control ).closest( parent ).show();
					} else {
						$( control ).closest( parent ).hide();
					}
				}
			}
		},

		/** 
		 * Return the control value.
		 */
		get_controller_value: function( controllerControl, controllerId, type ) {
			var prefix = type ? type + '-' : '';

			if ( 'checkbox' === controllerControl.attr( 'type' ) || 'radio' === controllerControl.attr( 'type' ) ) {
				controllerControl = $( '.' + prefix + 'control-group__controller.' + controllerId + ':checked' );
			}

			var controllerValue = controllerControl.val();

			if ( typeof controllerValue === 'undefined' ) {
				controllerValue = '';
			}

			return controllerValue.toString();
		}
	};

	$( document ).ready( wpsf.on_ready() );

}( jQuery, document ));
