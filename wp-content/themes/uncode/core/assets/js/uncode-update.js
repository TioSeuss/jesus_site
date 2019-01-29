(function($) {
	"use strict";
	/* global jQuery, UncodeUpdateParameters */

	$(document).on('ready', function() {
		var update_info = '<p><a href="' + UncodeUpdateParameters.update_instructions_url + '" target="_blank" rel="noopener noreferrer">' + UncodeUpdateParameters.update_instructions_text + '</a><span style="padding:0 10px;">|</span><a href="' + UncodeUpdateParameters.changelog_url + '" target="_blank" rel="noopener noreferrer">' + UncodeUpdateParameters.changelog_text + '</a></p>';

		$( '#update-themes-table .plugin-title > p > strong' ).each( function() {
			if ( 'Uncode' == $( this ).html() ) {
				$(this ).parent().append( update_info );
			}
		});

		$( '.theme-browser .themes .theme' ).click( function() {
			setTimeout( function() {
				if ( $( '.theme-overlay .theme-name' ).length ) {
					if ( -1 < $( '.theme-overlay .theme-name' ).text().indexOf( 'Uncode' ) ) {
						$( '.theme-overlay .theme-info .notice > p > strong' ).append( update_info );
						$( '.theme-overlay' ).find('.thickbox.open-plugin-details-modal').removeClass().attr( 'href', UncodeUpdateParameters.changelog_url );
					}
				}
			}, 10 );
		});
	});
})(jQuery);
