(function($, document) {

    var wpsf = {

        cache: function() {
            wpsf.els = {};
            wpsf.vars = {};
        },

        on_ready: function() {

            // on ready stuff here
            wpsf.cache();
            wpsf.trigger_dynamic_fields();
            wpsf.setup_groups();

        },

        /**
         * Trigger dynamic fields
         */
        trigger_dynamic_fields: function() {

            wpsf.setup_timepickers();
            wpsf.setup_datepickers();

        },

        /**
         * Set up timepickers
         */
        setup_timepickers: function() {

            $('.timepicker').not('.hasTimepicker').each(function(){

                var timepicker_args = $(this).data('timepicker');

                $(this).timepicker( timepicker_args );

            });

        },

        /**
         * Set up timepickers
         */
        setup_datepickers: function() {

            $('.datepicker').not('.hasTimepicker').each(function(){

                var datepicker_args = $(this).data('datepicker');

                $(this).datepicker( datepicker_args );

            });

        },

        /**
         * Setup repeatable groups
         */
        setup_groups: function() {

            // add row

            $(document).on('click', '.wpsf-group__row-add', function(){

                var $group = $(this).closest('.wpsf-group'),
                    $row = $(this).closest('.wpsf-group__row'),
                    template_name = $(this).data('template'),
                    $template = $('#'+template_name).html();

                $row.after( $template );

                wpsf.reindex_group( $group );

                wpsf.trigger_dynamic_fields();

                return false;

            });

            // remove row

            $(document).on('click', '.wpsf-group__row-remove', function(){

                var $group = jQuery(this).closest('.wpsf-group'),
                    $row = jQuery(this).closest('.wpsf-group__row');

                $row.remove();

                wpsf.reindex_group( $group );

                return false;

            });

        },

        /**
         * Reindex a group of repeatable rows
         *
         * @param arr $group
         */
        reindex_group: function( $group ) {

            if( $group.find(".wpsf-group__row").length == 1 ) {
                $group.find(".wpsf-group__row-remove").hide();
            } else {
                $group.find(".wpsf-group__row-remove").show();
            }

            $group.find(".wpsf-group__row").each(function(index) {

                $(this).removeClass('alternate');

                if(index%2 == 0)
                    $(this).addClass('alternate');

                $(this).find("input").each(function() {
                    var name = jQuery(this).attr('name'),
                        id = jQuery(this).attr('id');

                    if(typeof name !== typeof undefined && name !== false)
                        $(this).attr('name', name.replace(/\[\d+\]/, '['+index+']'));

                    if(typeof id !== typeof undefined && id !== false)
                        $(this).attr('id', id.replace(/\_\d+\_/, '_'+index+'_'));

                });

                $(this).find('.wpsf-group__row-index span').html( index );

            });

        }

    };

	$(document).ready( wpsf.on_ready() );

}(jQuery, document));