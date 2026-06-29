/**
 * UpSolution Element: List Order
 */
! function( $, _undefined ) {
	"use strict";

	const DELETE_FILTER = null;
	const urlManager = $ush.urlManager();

	/**
	 * @param {Node} container.
	 */
	function usListOrder( container ) {
		const self = this;

		// Bindable events
		self._events = {
			selectChanged: self._selectChanged.bind( self ),
		};

		// Elements
		self.$container = $( container );
		self.$pageContent = $( 'main#page-content' );

		// Sets value from URL
		if ( self.changeURLParams() ) {
			var urlValue = urlManager.get( '_orderby' );
			if ( ! $ush.isUndefined( urlValue ) ) {
				$( 'select', container ).val( urlValue );
			}
		}

		// Events
		self.$container.on( 'change', 'select', self._events.selectChanged );
	}

	// List Order API
	$.extend( usListOrder.prototype, {

		/**
		 * Determines if enabled URL.
		 *
		 * @return {Boolean} True if enabled url, False otherwise.
		 */
		changeURLParams: function() {
			return this.$container.hasClass( 'change_url_params' );
		},

		/**
		 * @event handler
		 * @param {Event} e The Event interface represents an event which takes place in the DOM.
		 */
		_selectChanged: function( e ) {
			const self = this;

			const $firstList = $( `
				.w-grid.us_post_list:visible,
				.w-grid.us_product_list:visible,
				.w-grid-none:visible
			`, self.$pageContent ).first();

			if ( $firstList.hasClass( 'w-grid' ) ) {
				$firstList.addClass( 'used_by_list_order' );
			}

			var value = e.target.value;
			if ( value === '' ) {
				value = DELETE_FILTER;
			}
			if ( value === self.lastValue ) {
				return;
			}
			self.lastValue = value;

			if ( self.changeURLParams() ) {
				urlManager.set( '_orderby', value ).push();
			}

			$firstList.trigger( 'usListOrder', [
				{
					'scroll_to_list': self.$container.hasClass( 'scroll_to_list' ),
					'_orderby': value,
				}
			] );
		}
	} );

	$.fn.usListOrder = function() {
		return this.each( function() {
			$( this ).data( 'usListOrder', new usListOrder( this ) );
		} );
	};

	$( () => $( '.w-order.for_list' ).usListOrder() );

}( jQuery );
