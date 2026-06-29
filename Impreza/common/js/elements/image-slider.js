/**
 * UpSolution Element: [us_image_slider].
 */
! function( $, _undefined ) {
	"use strict";

	/**
	 * @class usImageSlider
	 * @param {String} container The container
	 */
	function usImageSlider( container ) {
		const self = this;
		const $container = $( container );
		const $frame = $( '.w-slider-h', container );
		const $royalSlider = $( '.royalSlider', container );
		const options = {};

		if ( ! $.fn.royalSlider || $container.data( 'usImageSlider' ) ) {
			return;
		}

		// Gets options
		const $jsonData = $( '.w-slider-json', container );
		if ( $jsonData.length ) {
			$.extend( options, $jsonData[0].onclick() || {} );
		}
		$jsonData.remove();

		// Always apply certain fit option for grid listing slider
		if ( $container.parent().hasClass( 'w-post-elm' ) ) {
			options[ 'imageScaleMode' ] = 'fill';
		}
		options[ 'usePreloader' ] = false;

		// https://dimsemenov.com/plugins/royal-slider/documentation/
		$royalSlider.royalSlider( options );
		const royalSlider = $royalSlider.data( 'royalSlider' );
		if ( options.fullscreen && options.fullscreen.enabled ) {
			// Moving royal slider to the very end of body element to allow a proper fullscreen
			const rsEnterFullscreen = function() {
				$royalSlider.appendTo( $us.$body );
				royalSlider.ev.off( 'rsEnterFullscreen', rsEnterFullscreen );
				royalSlider.ev.on( 'rsExitFullscreen', rsExitFullscreen );
				royalSlider.updateSliderSize();
			};
			royalSlider.ev.on( 'rsEnterFullscreen', rsEnterFullscreen );
			const rsExitFullscreen = function() {
				$royalSlider.prependTo( $frame );
				royalSlider.ev.off( 'rsExitFullscreen', rsExitFullscreen );
				royalSlider.ev.on( 'rsEnterFullscreen', rsEnterFullscreen );
			};
		}

		royalSlider.ev.on( 'rsAfterContentSet', function() {
			royalSlider.slides.forEach( function( slide ) {
				$( slide.content.find( 'img' )[0] ).attr( 'alt', slide.caption.attr( 'data-alt' ) );
			} );
		} );

		$us.$canvas.on( 'contentChange', function() {
			$royalSlider.parent().imagesLoaded( function() {
				royalSlider.updateSliderSize();
			} );
		} );

		self.royalSlider = royalSlider;
	}

	$.fn.usImageSlider = function() {
		return this.each( function() {
			$( this ).data( 'usImageSlider', new usImageSlider( this ) );
		} );
	};

	$( () => {
		$( '.w-slider' ).usImageSlider();
	} );

	// Init in Grid Layout context
	$us.$document.on( 'usPostList.itemsLoaded usGrid.itemsLoaded', ( _, $items ) => {
		$( '.w-slider', $items ).usImageSlider();
	} );

}( jQuery );
