/**
 * UpSolution Element: Interactive Text
 */
!function( $ ) {
	"use strict";

	// Regular expression for searching for a space.
	const REGEXP_SPACE = /\p{Zs}/ug;

	/**
	 * @class usItext
	 * @param {string|node} container
	 */
	function usItext( container ) {
		const self = this;

		// Elements
		self.$container = $( container );
		self.$parts = $( '.w-itext-part', self.$container );
		if ( ! self.$parts.length ) {
			return;
		}

		// Options
		self.opts = {};
		$.extend( self.opts, self.$container[0].onclick() || {} );
		if ( ! $us.usbPreview() ) {
			self.$container.removeAttr( 'onclick' );
		}

		// Private "Variables"
		self.partsData = [];
		self.type = self.$container.usMod( 'type' ) || 'fadeIn';
		self.isCharsAnimate = self.type.includes( 'Chars' );
		self.isDisablePartAnimation = self.$container.hasClass( 'disable_part_animation' );
		self.spaceChar = String.fromCharCode( 32 ); // ' '

		// Fill data for each part
		self.$parts.each( ( _, node ) => {

			const $part = $( node );
			const $states = $( '.w-itext-state', $part );

			// Fill widths for each state
			const widths = [];
			$states.each( ( _, node ) => {
				widths.push( node.offsetWidth );
			} );

			// Fill text value for chars animation
			const statesText = [];
			$states.each( ( _, node ) => {
				statesText.push( $( node ).text() );
			} );

			self.partsData.push( {
				$part,
				widths,
				$states,
				statesText,
				index: 0
			} );

			if ( self.isCharsAnimate ) {
				$part.html( String( statesText[0] ).replace( REGEXP_SPACE, self.spaceChar ) );
			}

			// Needed for 1st animation
			if ( ! self.isDisablePartAnimation && ! self.isTypingChars() ) {
				$part.width( widths[0] );
			}
		} );

		// Different logic for chars and simple animation
		self.partsData.forEach( ( part ) => {
			if ( self.isCharsAnimate ) {
				self.startCharsAnimate( part );
			} else {
				part.loopTimer = $ush.timeout( () => self.startSimpleAnimate( part ), self.opts.delay + self.opts.duration );
			}
		} );
	};

	$.extend( usItext.prototype, {

		/**
		 * Determines if typing characters.
		 *
		 * @return {Boolean} True if typing characters, False otherwise.
		 */
		isTypingChars: function() {
			return this.type === 'typingChars';
		},

		/**
		 * Starts a simple animation for a part
		 *
		 * @param {{}} part - Part data object
		 */
		startSimpleAnimate: function( part ) {
			const self = this;

			if ( ! self.$container.hasClass( 'animation_started' ) ) {
				self.$container.addClass( 'animation_started' );
			}

			const nextIndex = ( part.index + 1 ) % part.$states.length;
			const $currentEl = part.$states.eq( part.index );
			const $nextEl = part.$states.eq( nextIndex );

			// Out
			$currentEl.removeClass( 'is-active' );

			// In
			$nextEl.addClass( 'is-active' );

			if ( ! self.isDisablePartAnimation && ! self.isTypingChars() ) {
				part.$part.css( 'width', part.widths[ nextIndex ] );
			}

			part.index = nextIndex;

			$ush.clearTimeout( part.loopTimer );

			part.loopTimer = $ush.timeout( () => self.startSimpleAnimate( part ), self.opts.delay + self.opts.duration );
		},

		/**
		 * Starts a chars animation for a part
		 *
		 * @param {{}} part - Part data object
		 */
		startCharsAnimate: function( part ) {
			$ush.timeout( () => this.charsNext( part ), this.opts.delay );
		},

		/**
		 * Set index of next item and start render chars
		 *
		 * @param {{}} part - Part data object
		 */
		charsNext: function( part ) {
			part.index = ( part.index + 1 ) % part.statesText.length;
			this.renderChars( part );
		},

		/**
		 * Render a chars one by one with animation
		 *
		 * @param {{}} part - Part data object
		 */
		renderChars: function( part ) {
			const self = this;
			const nextValue = part.statesText[ part.index ];
			const $node = part.$part;
			const duration = $ush.parseInt( self.opts.duration ) || 1000;
			var curDuration = duration;
			var startDelay = 0;

			const $curSpan = $node.wrapInner( '<span></span>' ).children( 'span' );
			const $nextSpan = $( '<span class="measure"></span>' )
				.html( nextValue.replace( REGEXP_SPACE, self.spaceChar ) )
				.appendTo( $node );

			const nextWidth = $nextSpan.width();

			// Typing animation - erase chars
			if ( self.isTypingChars() ) {
				const oldValue = $curSpan.text().trim() + ' ';
				const removeDuration = Math.floor( curDuration / 3 );
				startDelay = removeDuration * oldValue.length;

				for ( var i = 0; i < oldValue.length; i ++ ) {
					$ush.timeout( () => {
						const t = $curSpan.text();
						$curSpan.text( t.substring( 0, t.length - 1 ) );
					}, removeDuration * i );
				}
			}

			$ush.timeout( () => {

				$node.addClass( 'notransition' );
				if ( ! self.isDisablePartAnimation && ! self.isTypingChars() ) {
					$node.css( 'width', $node.width() );
				}

				$ush.timeout( () => {
					$node.removeClass( 'notransition' );
					if ( ! self.isDisablePartAnimation && ! self.isTypingChars() ) {
						$node.css( 'width', nextWidth );
					}
				}, 25 );

				if ( ! self.isTypingChars() ) {
					$curSpan.addClass( 'hidden-state' ).css( 'width', nextWidth );
				}

				$nextSpan.removeClass( 'measure' ).prependTo( $node ).empty();

				const stagger = 60; // Delay between chars (zoomInChars only)

				for ( var i = 0; i < nextValue.length; i++ ) {

					const char = nextValue[ i ];

					if ( ! self.isTypingChars() ) {
						const $char = $( '<span class="w-itext-char">' + char + '</span>' )
							.css( 'animation-delay', ( i * stagger ) + 'ms' )
							.appendTo( $nextSpan );

						$char.addClass( 'is-active' );
						// Needed to prevent char line break
						$char[0].onanimationend = () => {
							$char.css( 'display', 'inline' );
						}
					} else {
						$ush.timeout( () => {
							$nextSpan.html( $nextSpan.html() + char );
						}, curDuration * i );
					}
				}

				if ( self.isTypingChars() ) {
					curDuration *= ( nextValue.length + 1 );
				} else {
					curDuration = duration + ( nextValue.length * stagger );
				}

				$ush.timeout( () => {
					self.clearChars( part, nextValue );
				}, curDuration + Math.floor( self.opts.delay / 3 ) );

			}, startDelay );
		},

		clearChars: function( part, text ) {
			const self = this;

			part.$part.html( text.replace( REGEXP_SPACE, self.spaceChar ) );

			$ush.timeout( () => self.charsNext( part ), self.opts.delay );
		}
	} );


	$.fn.usItext = function() {
		return this.each( function() {
			$( this ).data( 'usItext', new usItext( this ) );
		} );
	};

	$( () => $( '.w-itext' ).usItext() );

}( jQuery );
