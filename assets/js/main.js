(function($, document) {

    var wpsf = {

        cache: function() {
            wpsf.els = {};
            wpsf.vars = {};
        },

        on_ready: function() {

            // on ready stuff here
            wpsf.cache();
            wpsf.setup_timepickers();

        },

        setup_timepickers: function() {

            $('.timepicker').timepicker();

        }

    };

	$(document).ready( wpsf.on_ready() );

}(jQuery, document));