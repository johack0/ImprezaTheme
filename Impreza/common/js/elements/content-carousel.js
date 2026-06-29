/**
 * UpSolution Element: Content Carousel
 */
! function( $, undefined ) {
	"use strict";

	window.$us = window.$us || {};

	/**
	 * @param {String} container.
	 */
	function usContentCarousel( container ) {
		const self = this;

		// Elements
		const $carouselContainer = $( '.owl-carousel', container );

		// Predefined options suitable for content carousel
		// https://owlcarousel2.github.io/OwlCarousel2/docs/api-options.html
		self.options = {
			navElement: 'button',
			navText: [ '', '' ],
			responsiveRefreshRate: 100,
		}

		// Bindable events
		self._events = {
			continualRotationCssResize: self.continualRotationCssResize.bind( self ),
			controlItemAnimation: self.controlItemAnimation.bind( self ),
		};

		if ( $carouselContainer.is( '[onclick]' ) ) {
			$.extend( self.options, $carouselContainer[0].onclick() || {} );
			if ( ! $us.usbPreview() ) {
				$carouselContainer.removeAttr( 'onclick' );
			}
		}

		// To prevent scroll blocking on mobiles
		if ( $us.$html.hasClass( 'touch' ) || $us.$html.hasClass( 'ios-touch' ) ) {
			$.extend( self.options, {
				mouseDrag: false,
			} );
		}

		// Disable autoWidth for [vc_row_inner] items
		if ( self.options.slideBy == 'page' ) {
			if ( $( '.wpb_row:first', $carouselContainer ).length ) {
				$.each( self.options.responsive, ( _, options ) => {
					$.extend( options, {
						items:1,
						autoWidth: false
					} );
				} );
			}
		}

		// Override specific options for proper operation in Live Builder
		if ( $us.usbPreview() ) {
			$.extend( self.options, {
				autoplayHoverPause: true,
				mouseDrag: false,
				touchDrag: false,
				loop: false,
			} );
			// TODO: Find a more elegant solution to work correctly in Live Builder!
			$carouselContainer.one( 'initialized.owl.carousel', () => {
				// ../plugins/us-core/builder/assets/js/builder-preview.js#L2734
				$( '.owl-item', $carouselContainer ).each( ( _, node ) => {
					var $node = $( node ),
						$element = $( '> *', node ),
						usbid = $element.data( 'usbid' ) || $element.data( 'usbid2' );
					$node.attr( 'data-usbid', usbid );
					$element.data( 'usbid2', usbid ).removeAttr( 'data-usbid' );
				} );
				$ush.timeout( () => {
					$( '.owl-dots *, .owl-prev, .owl-next', $carouselContainer ).addClass( 'usb_skip_elmSelected' );
				}, 1 );
			} );
			$( 'style[data-for]', $carouselContainer ).each( ( _, node ) => {
				$( node ).next().prepend( node );
			} );
		}

		// Re-init for "Show More" link after carousel init to set correct height.
		$carouselContainer.one( 'initialized.owl.carousel', () => {
			$( '[data-content-height]', $carouselContainer ).each( ( _, node ) => {
				var $node = $( node ),
					usCollapsibleContent = $node.data( 'usCollapsibleContent' );
				// Init for nodes that are cloned
				if ( $ush.isUndefined( usCollapsibleContent ) ) {
					usCollapsibleContent = $node.usCollapsibleContent().data( 'usCollapsibleContent' );
				}
				usCollapsibleContent.setHeight();
				$ush.timeout( () => {
					$carouselContainer.trigger( 'refresh.owl.carousel' );
				}, 1 );
			} );
			// Updates the carousel height to expanded and collapsed text
			if ( self.options.autoHeight ) {
				$( '[data-content-height]', $carouselContainer ).on( 'showContent', () => {
					$list.trigger( 'refresh.owl.carousel' );
				} );
			}
		});

		if ( self.options.autoplayContinual ) {
			self.options.slideTransition = 'linear';
			self.options.autoplaySpeed = self.options.autoplayTimeout;
			self.options.smartSpeed = self.options.autoplayTimeout;
			if ( ! self.options.autoWidth ) {
				self.options.slideBy = 1;
			}
		}

		if ( $carouselContainer.data( 'owl.carousel' )) {
			$carouselContainer.trigger( 'destroy.owl.carousel' );
		}

		// Init Owl Carousel
		self.owlCarousel = $carouselContainer.owlCarousel( self.options ).data( 'owl.carousel' );

		// Control animation appearance (USAnimate) in continual autoplay
		if (
			$( '.us_animate_this:first', $carouselContainer ).length
		) {
			if ( self.options.autoplayContinual ) {
				$carouselContainer.on( 'translate.owl.carousel', self._events.controlItemAnimation );
			} else {
				$( '.owl-item.active', self.owlCarousel.$stage ).addClass( 'animation-start' );
				$carouselContainer.on( 'translated.owl.carousel', self._events.controlItemAnimation );
			}
		}

		// Trigger continual autoplay
		if ( $carouselContainer && self.options.autoplayContinual ) {
			$carouselContainer.trigger( 'next.owl.carousel' );
		}

		// Set aria labels for navigation arrows
		if (
			$carouselContainer
			&& self.options.aria_labels.prev
			&& self.options.aria_labels.next
		) {
			$( '.owl-prev', $carouselContainer ).attr( 'aria-label', self.options.aria_labels.prev );
			$( '.owl-next', $carouselContainer ).attr( 'aria-label', self.options.aria_labels.next );
		}

		const screenSize = $carouselContainer.attr( 'class' ).match( /owl-responsive-(\d+)/ )[1];
		const currentOptionsResponsive = ( self.options.responsive || {} )[ screenSize ] || {};

		// Toggle classes for responsive
		if ( currentOptionsResponsive ) {
			// 'autoheight' class
			if ( currentOptionsResponsive.items === 1 ) {
				$carouselContainer.toggleClass( 'autoheight', currentOptionsResponsive.autoHeight );
			}
			// 'with_dots' class
			$carouselContainer.toggleClass( 'with_dots', currentOptionsResponsive.dots );
			// 'autoplay_continual_css' class
			$carouselContainer.toggleClass( 'autoplay_continual_css', currentOptionsResponsive.autoplayContinualCss );
		}

		// Init continual rotation via CSS
		if ( self.owlCarousel && self.options.autoplayContinualCss ) {
			self.continualRotationCss();

			// Responsive continual rotation via CSS
			$carouselContainer.on( 'resized.owl.carousel', self._events.continualRotationCssResize );
		}
	}

	// Content Carousel API
	$.extend( usContentCarousel.prototype, {

		/**
		 * Continual rotation via CSS
		 * Duplicate items to make width higher than container for infinite rotation
		 */
		continualRotationCss: function( isResizeUse = false ) {
			const self = this;

			if ( ! self.owlCarousel.$element.hasClass( 'autoplay_continual_css' ) ) {
				return;
			}

			const $stage = self.owlCarousel.$stage;
			const $items = $stage.children();

			// Do not duplicate items if they are already duplicated
			if (
				isResizeUse
				&& self.$originalItems
				&& ( self.$originalItems.length !== $items.length )
			) {
				return;
			}

			$stage.removeAttr( 'style' );

			if ( ! self.$originalItems ) {
				self.$originalItems = $items.clone();
			}

			var iteration = 0;

			// Fill the stage if needed
			while ( $stage[0].scrollWidth < self.owlCarousel.$element.outerWidth() ) {
				if ( iteration++ > 200 ) {
					break;
				}
				$stage.append( self.$originalItems.clone() );
			}

			// Duplicate stage content for infinite rotation
			$stage.append( self.owlCarousel.$stage.html() );
		},
		/**
		 * Toggle continual rotation via CSS based on responsive values
		 */
		continualRotationCssResize: function() {
			const self = this;
			const $element = self.owlCarousel.$element;

			self.owlCarousel.$stage.removeAttr( 'style' );

			const screenSize = ( String( $element[0].className ).match( /owl-responsive-(\d+)/ ) || [] )[1];
			const carouselResponsive = ( self.options.responsive || {} )[ screenSize ] || {};

			if ( ! carouselResponsive.autoplayContinualCss ) {
				$element.removeClass( 'autoplay_continual_css' );
				self.owlCarousel.$stage.html( self.$originalItems );
				$element.trigger( 'refresh.owl.carousel' );
			} else {
				$element.addClass( 'autoplay_continual_css' );
				self.continualRotationCss( true );
			}
		},
		/*
		 * Control animation appearance (USAnimate)
		 */
		controlItemAnimation: function( e ) {
			const self = this;
			if (
				! e.item
				|| ! e.item.index
				|| ! e.type
			) {
				return;
			}

			const $stage = self.owlCarousel.$stage;

			// Prevent hide item when it is visible for autoplayContinual
			if ( e.type === 'translate' && self.options.autoplayContinual ) {
				// Select item that will be translated
				const $translatedOwlItem = $( '.owl-item', $stage ).eq( e.item.index - 1 );
				if ( $translatedOwlItem.length ) {
					$translatedOwlItem.addClass( 'translated' );
					$ush.timeout( () => {
						$translatedOwlItem.removeClass( 'translated' );
					}, self.options.autoplaySpeed );
				}
			}

			// Control start animation class of items after carousel is translated
			if ( e.type === 'translated' ) {
				$stage.find( '.owl-item:not(.active)' ).removeClass( 'animation-start' );
				$stage.find( '.owl-item.active' ).addClass( 'animation-start' );
			}
		},
	} );

	$.fn.usContentCarousel = function( options ) {
		return this.each( function() {
			$( this ).data( 'usContentCarousel', new usContentCarousel( this, options ) );
		} );
	};

	$( () => {
		$( '.w-content-carousel' ).usContentCarousel();
	} );

}( jQuery );
