/**
 * UpSolution Element: Counter
 */
! function( $, _undefined ) {

	/**
	 * Counter Number part animations.
	 *
	 * @param container
	 * @constructor
	 */
	function usCounterNumber( container ) {
		const self = this;

		self.$container = $( container );
		self.initialString = $ush.toString( self.$container.html() );
		self.finalString = $ush.toString( self.$container.data( 'final' ) );
		self.format = self.getFormat( self.initialString, self.finalString );
		self.isNoAnimation = self.$container.closest( '.w-counter.animation_none' ).length;
		if ( self.format.decMark ) {
			const pattern = new RegExp( '[^0-9\/' + self.format.decMark + ']+', 'g' );
			self.initial = parseFloat( self.initialString.replace( pattern, '' ).replace( self.format.decMark, '.' ) );
			self.final = parseFloat( self.finalString.replace( pattern, '' ).replace( self.format.decMark, '.' ) );
		} else {
			self.initial = parseInt( self.initialString.replace( /[^0-9]+/g, '' ) );
			self.final = parseInt( self.finalString.replace( /[^0-9]+/g, '' ) );
		}
		if ( self.format.accounting ) {
			if ( self.initialString.length > 0 && self.initialString[0] == '(' ) {
				self.initial = -self.initial;
			}
			if ( self.finalString.length > 0 && self.finalString[0] == '(' ) {
				self.final = -self.final;
			}
		}

		if ( ! self.isNoAnimation ) {
			self.initSlideAnimation();
		}
	}

	/**
	 * Export API
	 */
	usCounterNumber.prototype = {

		/**
		 * Function to be called at each animation frame.
		 * @param now float Relative state between 0 and 1
		 */
		step: function( now ) {
			const self = this;
			var value = ( 1 - now ) * self.initial + self.final * now,
				intPart = Math[ self.format.decMark ? 'floor' : 'round' ]( value ).toString(),
				result = '';
			if ( self.format.zerofill ) {
				// Check how many zeros we need to add to this step value
				var amountOfZeros = ( self.format.intDigits - intPart.length );
				if ( amountOfZeros > 0 ) {
					intPart = '0'.repeat( amountOfZeros ) + intPart;
				}
			}
			if ( self.format.groupMark ) {
				if ( self.format.indian ) {
					result += intPart.replace( /(\d)(?=(\d\d)+\d$)/g, '$1' + self.format.groupMark );
				} else {
					result += intPart.replace( /\B(?=(\d{3})+(?!\d))/g, self.format.groupMark );
				}
			} else {
				result += intPart;
			}
			if ( self.format.decMark ) {
				var decimalPart = ( value % 1 ).toFixed( self.format.decDigits ).substring(2);
				result += self.format.decMark + decimalPart;
			}
			if ( self.format.accounting && result.length > 0 && result[0] === '-' ) {
				result = '(' + result.substring( 1 ) + ')';
			}

			// No Animation
			if ( self.isNoAnimation ) {
				self.$container.html( result );
			} else { // Slide Animation
				const digitsOnly = result.replace( /\D/g, '' );

				// Start animate from the last digit
				var digitIndex = digitsOnly.length - 1;

				for ( var slotIndex = self.slots.length - 1; slotIndex >= 0; slotIndex-- ) {

					const slot = self.slots[ slotIndex ];

					if ( slot.type !== 'digit' ) {
						continue;
					}

					const num = $ush.parseInt( digitsOnly[ digitIndex ] );
					const offset = num * slot.el.find( 'li' ).innerHeight();

					slot.el.find( 'ul' )
						.css( 'transform', 'translateY(-' + offset + 'px)' );

					// Hide non-active digits after animation (fonts with different num heights case)
					$ush.timeout( () => {
						slot.el.find( 'li:nth-child(' + num + ')' ).addClass( 'pre-active' );
					}, ( self.$container.closest( '.w-counter' ).data( 'duration' ) || 2 ) * 1000 );

					digitIndex--;
				}
			}
		},

		/**
		 * Get number format based on initial and final number strings.
		 * @param initial string
		 * @param final string
		 * @returns {{groupMark, decMark, accounting, zerofill, indian}}
		 */
		getFormat: function( initial, final ) {
			const self = this;
			var iFormat = self._getFormat( initial ),
				fFormat = self._getFormat( final ),
				// Final format has more priority
				format = $.extend( {}, iFormat, fFormat );
			// Group marks detector is more precise, so using it in controversial cases
			if ( format.groupMark == format.decMark ) {
				delete format.groupMark;
			}
			return format;
		},

		/**
		 * Get number format based on a single number string.
		 * @param str string
		 * @returns {{groupMark, decMark, accounting, zerofill, indian}}
		 */
		_getFormat: function( str ) {
			var marks = str.replace( /[0-9\(\)\-]+/g, '' ),
				format = {};
			if ( str.charAt(0) == '(' ) {
				format.accounting = true;
			}
			if ( /^0[0-9]/.test( str ) ) {
				format.zerofill = true;
			}
			str = str.replace( /[\(\)\-]/g, '' );
			if ( marks.length != 0 ) {
				if ( marks.length > 1 ) {
					format.groupMark = marks.charAt(0);
					if ( marks.charAt(0) != marks.charAt( marks.length - 1 ) ) {
						format.decMark = marks.charAt( marks.length - 1 );
					}
					if ( str.split( format.groupMark ).length > 2 && str.split( format.groupMark )[1].length == 2 ) {
						format.indian = true;
					}
				} else/*if (marks.length == 1)*/ {
					format[ ( ( ( str.length - 1 ) - str.indexOf( marks ) ) == 3 && marks !== '.' ) ? 'groupMark' : 'decMark' ] = marks;
				}
				if ( format.decMark ) {
					format.decDigits = str.length - str.indexOf( format.decMark ) - 1;
				}
			}
			if ( format.zerofill ) {
				format.intDigits = str.replace( /[^\d]+/g, '' ).length - ( format.decDigits || 0 );
			}

			return format;
		},

		/**
		 * Create slots(digits and chars) for each character in final number string
		 * Fill DOM with slots
		 * Needed for slide animation
		 */
		initSlideAnimation: function() {
			const self = this;

			self.$container.empty();
			self.slots = []; // Numbers and chars

			const chars = self.finalString.split( '' );

			chars.forEach( char => {
				if ( /\d/.test( char ) ) { // Digits
					const $mainDigit = $( '<span class="w-counter-value-digit"></span>' );
					const $digits = $( '<div class="w-counter-digits"></div>' );

					$digits.append( '<ul></ul>' );

					for ( let i = 0; i < 10; i++ ) {
						$( 'ul', $digits ).append( '<li>' + i + '</li>' );
					}

					$mainDigit
						.append( '<span>' + char + '</span>' )
						.append( $digits );

					self.$container.append( $mainDigit );

					self.slots.push( { type: 'digit', el: $digits } );

				} else { // Chars like '.' or ','
					const $char = $( '<span class="w-counter-value-delimeter">' + char + '</span>' );
					self.$container.append( $char );

					self.slots.push( { type: 'char', el: $char } );
				}
			} );
		}
	};

	/**
	 * Counter Number part animations.
	 *
	 * @param container
	 * @constructor
	 */
	function usCounterText( container ) {
		const self = this;
		self.$container = $( container );
		self.initial = $ush.toString( self.$container.text() );
		self.final = $ush.toString( self.$container.data( 'final' ) );
		self.partsStates = self.getStates( self.initial, self.final );
		self.len = 1 / ( self.partsStates.length - 1 );
		// The text value won’t change on every frame, so we’ll update it only when needed.
		self.curState = 0;
	}
	/**
	 * Export API
	 */
	usCounterText.prototype = {

		/**
		 * Function to be called at each animation frame.
		 *
		 * @param now float Relative state between 0 and 1
		 */
		step: function( now ) {
			const self = this;
			const state = Math.round( Math.max( 0, now / self.len ) );
			if ( state == self.curState ) {
				return;
			}
			self.$container.html( self.partsStates[ state ] );
			self.curState = state;
		},

		/**
		 * Slightly modified Wagner-Fischer algorithm to obtain the shortest edit distance with intermediate states.
		 *
		 * @param initial string The initial string
		 * @param final string The final string
		 * @returns {[]}
		 */
		getStates: function( initial, final ) {
			var min = Math.min,
				dist = [],
				i, j;
			for ( i = 0; i <= initial.length; i ++ ) {
				dist[ i ] = [ i ];
			}
			for ( j = 1; j <= final.length; j ++ ) {
				dist[ 0 ][ j ] = j;
				for ( i = 1; i <= initial.length; i ++ ) {
					dist[ i ][ j ] = ( initial[ i - 1 ] === final[ j - 1 ] ) ? dist[ i - 1 ][ j - 1 ] : ( Math.min( dist[ i - 1 ][ j ], dist[ i ][ j - 1 ], dist[ i - 1 ][ j - 1 ] ) + 1 );
				}
			}
			// Obtaining the intermediate states
			var states = [ final ];
			for ( i = initial.length, j = final.length; i > 0 || j > 0; i --, j -- ) {
				var min = dist[ i ][ j ];
				if ( i > 0 ) {
					min = Math.min( min, dist[ i - 1 ][ j ], ( j > 0 ) ? dist[ i - 1 ][ j - 1 ] : min );
				}
				if ( j > 0 ) {
					min = Math.min( min, dist[ i ][ j - 1 ] );
				}
				if ( min >= dist[ i ][ j ] ) {
					continue;
				}
				if ( min == dist[ i ][ j - 1 ] ) {
					// Remove
					states.unshift( states[0].substring( 0, j - 1 ) + states[0].substring( j ) );
					i ++;
				} else if ( min == dist[ i - 1 ][ j - 1 ] ) {
					// Modify
					states.unshift( states[0].substring( 0, j - 1 ) + initial[ i - 1 ] + states[0].substring( j ) );
				} else if ( min == dist[ i - 1 ][ j ] ) {
					// Insert
					states.unshift( states[0].substring( 0, j ) + initial[ i - 1 ] + states[0].substring( j ) );
					j ++;
				}
			}
			return states;
		}
	};

	/**
	 * @param container
	 * @constructor
	 */
	function usCounter( container ) {
		const self = this;

		// Commonly used DOM elements
		self.$container = $( container );
		self.parts = [];
		self.duration = parseFloat( self.$container.data( 'duration' ) || 2 ) * 1000;

		// Hide final values on init
		$( '.w-counter-value-part', self.$container ).each( ( _, part ) => {
			const $part = $( part );
			if ( $part.hasClass( 'final' ) ) {
				$part.remove();
			} else {
				$part.removeClass( 'hidden' );
			}
		} );

		$( '.w-counter-value-part', self.$container ).each( ( _, part ) => {
			const $part = $( part );
			// Skip the ones that won't be changed
			if ( $ush.toString( $part.html() ) === $ush.toString( $part.data( 'final' ) ) ) {
				return;
			}
			if ( $part.usMod( 'type' ) === 'number' ) {
				self.parts.push( new usCounterNumber( $part ) );
			} else {
				self.parts.push( new usCounterText( $part ) );
			}
		} );
		if ( window.$us !== _undefined && window.$us.scroll !== _undefined ) {
			// Animate element when it becomes visible
			$us.waypoints.add( self.$container, /* offset */'15%', self.animate.bind( self ) );
		} else {
			// No waypoints available: animate right from the start
			self.animate();
		}
		self.$container.one( 'animation_start', () => self.animate() );
	}
	/**
	 * Export API
	 */
	usCounter.prototype = {
		animate: function() {
			const self = this;
			if ( self.$container.hasClass( 'animation_none' ) ) {
				self.$container.css( 'w-counter', 0 ).animate( { 'w-counter': 1 }, {
					duration: self.duration,
					step: self.step.bind( self ),
				} );
			} else {
				self.step( 1 );
			}
		},

		/**
		 * Function to be called at each animation frame.
		 *
		 * @param now float Relative state between 0 and 1
		 */
		step: function( now ) {
			const self = this;
			for ( var i = 0; i < self.parts.length; i++ ) {
				self.parts[ i ].step( now );
			}
		}
	};

	$.fn.usCounter = function( options ) {
		return this.each( function() {
			$( this ).data( 'usCounter', new usCounter( this, options ) );
		} );
	};

	$( () => $( '.w-counter' ).usCounter() );

}( jQuery );
