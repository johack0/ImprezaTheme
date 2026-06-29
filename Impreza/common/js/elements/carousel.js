/**
 * UpSolution Element: Carousel
 */
! function( $, _undefined ) {
	"use strict";

	/**
	 * @param {Node} container.
	 */
	function usCarousel( container ) {
		const self = this;

		// Elements
		self.$container = $( container );
		self.$carousel = $( '.w-grid-list.owl-carousel', self.$container );

		// https://owlcarousel2.github.io/OwlCarousel2/docs/api-options.html
		self.options = {
			navElement: 'button',
			navText: ['', ''],
			responsiveRefreshRate: 100,
		};

		// Bindable events
		self._events = {
			initializedOwlCarousel: self.initializedOwlCarousel.bind( self ),
			mousedownOwlCore: self.mousedownOwlCore.bind( self ),
			continualRotationCssResize: self.continualRotationCssResize.bind( self ),
			controlItemAnimation: self.controlItemAnimation.bind( self ),
		};

		// Load Owl Options
		const $opts = $( '.w-grid-carousel-json', self.$container );
		if ( $opts.is( '[onclick]' ) ) {
			$.extend( self.options, ( $opts[0].onclick() || {} ).carousel_settings || {} );
		}
		$opts.remove();

		// To prevent scroll blocking on mobiles
		if ( $us.$html.hasClass( 'touch' ) || $us.$html.hasClass( 'ios-touch' ) ) {
			self.options.mouseDrag = false;
		}

		// Override specific options for proper operation in Live Builder
		if ( $us.usbPreview() ) {
			$.extend( self.options, {
				autoplayHoverPause: true,
				mouseDrag: false,
				touchDrag: false,
				loop: false,
			} );
		}

		if ( self.options.autoplayContinual ) {
			$.extend( self.options, {
				slideTransition: 'linear',
				autoplaySpeed: self.options.autoplayTimeout,
				smartSpeed: self.options.autoplayTimeout,
			} );
			if ( ! self.options.autoWidth ) {
				self.options.slideBy = 1;
			}
		}

		// Events
		self.$carousel
			.on( 'initialized.owl.carousel', self._events.initializedOwlCarousel )
			.on( 'mousedown.owl.core', self._events.mousedownOwlCore );

		// Init Owl Carousel
		self.owlCarousel = self.$carousel.owlCarousel( self.options ).data( 'owl.carousel' );

		// Control animation appearance (USAnimate) in continual autoplay
		if (
			$( '.us_animate_this:first', self.$carousel ).length
		) {
			if ( self.options.autoplayContinual ) {
				self.$carousel.on( 'translate.owl.carousel', self._events.controlItemAnimation );
			} else {
				$( '.owl-item.active', self.owlCarousel.$stage ).addClass( 'animation-start' );
				self.$carousel.on( 'translated.owl.carousel', self._events.controlItemAnimation );
			}
		}

		// Trigger continual autoplay
		if ( self.owlCarousel && self.options.autoplayContinual ) {
			self.$carousel.trigger( 'next.owl.carousel' );
		}

		// Set aria labels for navigation
		if (
			self.owlCarousel
			&& self.options.aria_labels.prev
			&& self.options.aria_labels.next
		) {
			$( '.owl-prev', self.$carousel ).attr( 'aria-label', self.options.aria_labels.prev );
			$( '.owl-next', self.$carousel ).attr( 'aria-label', self.options.aria_labels.next );
		}

		const carouselResponsive = ( self.options.responsive || {} )[ self.getScreenSize() ] || {};

		// Toggle classes for responsive
		if ( carouselResponsive ) {
			// 'autoheight' class
			if ( carouselResponsive.items === 1 ) {
				self.$carousel.toggleClass( 'autoheight', carouselResponsive.autoHeight );
			}
			// 'with_dots' class
			self.$carousel.toggleClass( 'with_dots', carouselResponsive.dots );
			// 'autoplay_continual_css' class
			self.$carousel.toggleClass( 'autoplay_continual_css', carouselResponsive.autoplayContinualCss );
		}

		// Init continual rotation via CSS
		if ( self.owlCarousel && self.options.autoplayContinualCss ) {
			self.continualRotationCss();

			// Responsive continual rotation via CSS
			self.$carousel.on( 'resized.owl.carousel', self._events.continualRotationCssResize );
		}

		// Open Post Image in a Popup
		if ( $( '[ref=magnificPopupList]:first', self.$carousel ).length ) {
			$ush.timeout( self.initMagnificPopup.bind( self ), 1 );
		}

		// Open posts in popup
		if ( self.$container.hasClass( 'open_items_in_popup' ) ) {
			new $us.usPopup().popupPost( self.$container );
		}

		// Control the carousel navigation from a keyboard
		self.initKeyboardNav( carouselResponsive );
	}

	// Carousel API
	$.extend( usCarousel.prototype, {

		/**
		 * Control the carousel navigation from a keyboard
		 *
		 * @param {Object} carouselResponsive responsive options
		 *
		 */
		initKeyboardNav: function( carouselResponsive ) {
			const self = this;

			const focusableSelectors = [
				'a[href]', 'area[href]',
				'input:not([disabled])',
				'select:not([disabled])',
				'textarea:not([disabled])',
				'button:not([disabled])',
				'iframe', 'object', 'embed',
				'[tabindex]:not([tabindex="-1"])',
				'[contenteditable]', 'video[controls] source'
			].join();

			// Disallow focus on cloned
			$ush.timeout( () => {
				self.$carousel
					.find( '.owl-item.cloned' )
					.find( focusableSelectors )
					.attr( 'tabindex', -1 );
			}, 100 );

			if ( carouselResponsive.autoplay ) {

				// If autoplay is enabled and the focus is not on the active item set the focus on the active item
				var lastFocused = null;

				self.$carousel.off( 'focusin.carouselKeyboardNav' ).on( 'focusin.carouselKeyboardNav', ( e ) => {
					self.$carousel.trigger( 'stop.owl.autoplay' );

					const $allItems = $( '.owl-item:not(.cloned)', self.$carousel );
					const $mainActive = $( '.owl-item.active:not(.cloned)', self.$carousel );
					const $first = $( focusableSelectors, $mainActive ).first();

					if ( ! $first.length ) {
						return;
					}
					if ( ! $allItems.has( e.target ).length ) {
						return;
					}
					if ( $first[0] === e.target || $first[0] === lastFocused ) {
						return;
					}
					if ( ! $mainActive.has( e.target ).length ) {
						$first.focus();
						lastFocused = $first[0];
					}
				} );

				// Enable autoplay on focusout
				self.$carousel.off( 'focusout.carouselKeyboardNav' ).on( 'focusout.carouselKeyboardNav', () => {
					self.$carousel.trigger( 'play.owl.autoplay' );
				} );
			}

			// Slide carousel when tabbing
			if ( carouselResponsive.items === 1 && ! carouselResponsive.loop ) {
				self.$carousel.off( 'keyup.carouselKeyboardNav' ).on( 'keyup.carouselKeyboardNav', ( e ) => {
					if ( e.keyCode !== $ush.TAB_KEYCODE ) {
						return;
					}
					const $owlItem = $( e.target ).closest( '.owl-item' );
					if ( ! $owlItem.length ) {
						return;
					}
					if ( e.shiftKey ) {
						self.$carousel.trigger( 'to.owl.carousel', [ $owlItem.index() ] );
					} else {
						self.$carousel.trigger( 'to.owl.carousel', [ $owlItem.index(), 0 ] );
					}
				} );
			}

			self.$carousel.off( 'keydown.carouselKeyboardNav' ).on( 'keydown.carouselKeyboardNav', ( e ) => {
				if ( e.keyCode !== $ush.TAB_KEYCODE || carouselResponsive.items === 1 ) {
					return;
				}

				if ( self.options.slideBy === 'page' ) {
					const $activeItems = $( '.owl-item.active:not(.cloned)', self.$carousel );
					const $focusables = $( focusableSelectors, $activeItems ).filter( ':visible' );

					const index = $focusables.index( e.target );
					if ( index < 0 ) {
						return;
					}

					if ( ! e.shiftKey && index === $focusables.length - 1 ) {
						self.$carousel.trigger( 'stop.owl.autoplay' );
						self.$carousel.trigger( 'next.owl.carousel', [0] );
					}

					if ( e.shiftKey && index === 0 ) {
						self.$carousel.trigger( 'stop.owl.autoplay' );
						self.$carousel.trigger( 'prev.owl.carousel', [0] );
					}

				} else {
					const $owlItem = $( e.target ).closest( '.owl-item' );
					if ( ! $owlItem.length ) {
						return;
					}

					const $focusables = $( focusableSelectors, $owlItem ).filter( ':visible' );
					const index = $focusables.index( e.target );

					if ( e.shiftKey && index === 0 ) {
						self.$carousel.trigger( 'prev.owl.carousel', carouselResponsive.items === 1 ? [0] : null );
					}
					if ( ! e.shiftKey && index === $focusables.length - 1 ) {
						self.$carousel.trigger( 'next.owl.carousel', carouselResponsive.items === 1 ? [0] : null );
					}
				}
			} );

			// Slide carousel with arrow keys when focused
			self.$carousel.on( 'keydown.carouselArrowsNav', focusableSelectors, ( e ) => {
				switch ( e.keyCode ) {
					case 37: // ArrowLeft
						e.preventDefault();
						self.$carousel.trigger( 'prev.owl.carousel' );
						break;
					case 39: // ArrowRight
						e.preventDefault();
						self.$carousel.trigger( 'next.owl.carousel' );
						break;
				}
			} );
		},

		/**
		 * Re-init for "Show More" link after carousel init to set correct height.
		 *
		 * @param {Event} e
		 */
		initializedOwlCarousel: function( e ) {
			const self = this;
			const $toggleLinks = $( '[data-content-height]', e.currentTarget );

			// Refresh for toggle links
			$toggleLinks.each( ( _, node ) => {
				const $node = $( node );
				var usCollapsibleContent = $node.data( 'usCollapsibleContent' );
				// Init for nodes that are cloned
				if ( $ush.isUndefined( usCollapsibleContent ) ) {
					usCollapsibleContent = $node.usCollapsibleContent().data( 'usCollapsibleContent' );
				}
				usCollapsibleContent.setHeight();
				$ush.timeout( () => {
					self.$carousel.trigger( 'refresh.owl.carousel' );
				}, 1 );
			} );
			// Refresh for active tabs
			if ( $.isMobile && self.$carousel.closest( '.w-tabs-section.active' ).length > 0 ) {
				$ush.timeout( () => {
					self.$carousel.trigger( 'refresh.owl.carousel' );
				}, 50 );
			}
			// Updates the carousel height when expanding or collapsing text
			if ( self.options.autoHeight ) {
				$toggleLinks.on( 'showContent', () => {
					self.$carousel.trigger( 'refresh.owl.carousel' );
				} );
			}
		},

		/**
		 * Due to the carousel’s behavior, we handle movement completion manually,
		 * but sometimes errors occur when clicking on third-party elements.
		 *
		 * @param {Event} e
		 */
		mousedownOwlCore: function( e ) {
			const self = this;
			if ( ! String( e.target.className ).includes( 'collapsible-content-' ) ) {
				return;
			}
			if ( self.owlCarousel.settings.mouseDrag ) {
				self.owlCarousel.$stage.trigger( 'mouseup.owl.core' );
			}
			if ( self.owlCarousel.settings.touchDrag ) {
				self.owlCarousel.$stage.trigger( 'touchcancel.owl.core' );
			}
		},

		/**
		 * Get current screen size.
		 *
		 * @return {String} The screen size.
		 */
		getScreenSize: function() {
			return ( String( this.$carousel[0].className ).match( /owl-responsive-(\d+)/ ) || [] )[1];
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

	// Rotation via CSS
	$.extend( usCarousel.prototype, {

		/**
		 * Continual rotation via CSS
		 * Duplicate items to make width higher than container for infinite rotation
		 */
		continualRotationCss: function( isResizeUse = false ) {
			const self = this;

			if ( ! self.$carousel.hasClass( 'autoplay_continual_css' ) ) {
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
			$stage.append( $stage.html() );
		},
		/**
		 * Toggle continual rotation via CSS based on responsive values
		 */
		continualRotationCssResize: function() {
			const self = this;

			self.owlCarousel.$stage.removeAttr( 'style' );

			const carouselResponsive = ( self.options.responsive || {} )[ self.getScreenSize() ] || {};

			if ( ! carouselResponsive.autoplayContinualCss ) {
				self.$carousel.removeClass( 'autoplay_continual_css' );
				self.owlCarousel.$stage.html( self.$originalItems );
				self.$carousel.trigger( 'refresh.owl.carousel' );
			} else {
				self.$carousel.addClass( 'autoplay_continual_css' );
				self.continualRotationCss( true );
			}
		}
	} );

	// Popup window functionality
	$.extend( usCarousel.prototype, {

		/**
		 * Open Post Image in a Popup
		 */
		initMagnificPopup: function() {
			const self = this;
			const globalOpts = $us.langOptions.magnificPopup || {};

			self.$carousel.magnificPopup( {
				type: 'image',
				delegate: '.owl-item a[ref=magnificPopupList]',
				gallery: {
					enabled: true,
					navigateByImgClick: true,
					preload: [0, 1],
					tPrev: globalOpts.tPrev, // Alt text on left arrow
					tNext: globalOpts.tNext, // Alt text on right arrow
					tCounter: globalOpts.tCounter // Markup for "1 of 7" counter
				},
				image: {
					titleSrc: 'aria-label'
				},
				removalDelay: 300,
				mainClass: 'mfp-fade',
				fixedContentPos: true,
				callbacks: {
					beforeOpen: function() {
						if ( self.owlCarousel && self.owlCarousel.settings.autoplay ) {
							self.$carousel.trigger( 'stop.owl.autoplay' );
						}
					},
					beforeClose: function() {
						if ( self.owlCarousel && self.owlCarousel.settings.autoplay ) {
							self.$carousel.trigger( 'play.owl.autoplay' );
						}
					}
				}
			} );

			self.$carousel.on( 'initialized.owl.carousel', ( e ) => {
				const items = {};
				const $list = $( e.currentTarget );
				$( '.owl-item:not(.cloned)', $list ).each( ( _, owlItem ) => {
					const $owlItem = $( owlItem );
					const id = $( '[data-id]', $owlItem ).data( 'id' );
					if ( ! items[ id ] ) {
						items[ id ] = $owlItem;
					}
				} );
				$list.on( 'click', '.owl-item.cloned', ( e ) => {
					e.preventDefault();
					e.stopPropagation();
					const id = $( '[data-id]', e.currentTarget ).data( 'id' );
					if ( items[ id ] ) {
						$( 'a[ref=magnificPopupList]', items[ id ] ).trigger( 'click' );
					}
				} );
			} );
		},
	} );

	$.fn.usCarousel = function() {
		return this.each( function() {
			$( this ).data( 'usCarousel', new usCarousel( this ) );
		} );
	};

	$( () => $( '.w-grid.type_carousel' ).usCarousel() );

} ( jQuery );
