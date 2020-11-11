(function( $, document ) {

	var wpsf = {

		cache: function() {
			wpsf.els = {};
			wpsf.vars = {};

			wpsf.els.tab_links = $( '.wpsf-tab-link' );
		},

		on_ready: function() {

			// on ready stuff here
			wpsf.cache();
			wpsf.trigger_dynamic_fields();
			wpsf.setup_groups();
			wpsf.tabs.watch();

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
				wpsf.els.tab_links.removeClass( 'nav-tab-active' );
				$( 'a[href="' + tab_id + '"]' ).addClass( 'nav-tab-active' );

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
		}

	};

	$( document ).ready( wpsf.on_ready() );

}( jQuery, document ));
