/**
 * Filter Cache Panel.
 */
! function( $, _undefined ) {
	"use strict";

	/**
	 * @class FilterCachePanel
	 */
	function FilterCachePanel() {
		const self = this;

		// Private "Variables"
		self.opts = {
			ajaxData: {},
		};
		self.xhr;

		// Bindable events
		self._events = {
			clearCacheEvent: self.clearCacheEvent.bind( self ),
		};

		// Elements
		self.$container = $( '.usof-form-row.type_filter_cache_panel' );
		self.$clearCacheButton = $( '.for_clear_cache', self.$container );
		self.$message = $( '.usof-message', self.$container );
		self.$numOfRows = $( '.for_num_of_rows > span', self.$container );

		if ( self.$clearCacheButton.is( '[onclick]' ) ) {
			$.extend( self.opts, self.$clearCacheButton[0].onclick() || {} );
		}

		// Events
		self.$container
			.on( 'click', '.for_clear_cache', self._events.clearCacheEvent );
	}

	// FilterCachePanel API
	$.extend( FilterCachePanel.prototype, {

		/**
		 * Show the message.
		 *
		 * @param {*} content
		 */
		showMessage: function( content ) {
			content = $ush.toString( content );
			this.$message
				.toggleClass( 'hidden', content === '' )
				.html( content );
		},

		/**
		 * Called on clear cache.
		 */
		clearCacheEvent: function() {
			const self = this;

			if ( self.$clearCacheButton.hasClass( 'loading' ) ) {
				return;
			}

			self.$clearCacheButton.addClass( 'loading' );
			self.showMessage( '' );

			self.xhr = $.ajax(
				{
					type: 'POST',
					url: $usof.ajaxUrl,
					dataType: 'json',
					cache: false,
					data: self.opts.ajaxData,
					success: ( res ) => {
						if ( res.data.message ) {
							self.showMessage( res.data.message );
						}
						self.$numOfRows.text(0);
					},
					complete: () => {
						self.$clearCacheButton.removeClass( 'loading' );
					},
				}
			);
		},
	} );

	$( () => new FilterCachePanel() );

} (jQuery);
