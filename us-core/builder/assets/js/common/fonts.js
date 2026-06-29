/**
 * Available spaces:
 *
 * _window.$usb - Basic object for mounting and initializing all extensions of the builder
 * _window.$usbcore - Auxiliary functions for the builder and its extensions
 * _window.$ush - US Helper Library
 *
 */
! function( $, _undefined ) {

	const _window = window;

	if ( ! _window.$usb ) {
		return;
	}

	_window.$ush = _window.$ush || {};

	/**
	 * @class Fonts - Functionality for working with font settings
	 */
	function Fonts() {}

	// Fonts API
	$.extend( Fonts.prototype, {

		/**
		 * Set the google fonts
		 *
		 * @param {{}} themeOptions The theme options
		 */
		setGoogleFonts: function( themeOptions ) {
			const self = this;

			if ( ! $usb.iframeIsReady ) {
				return;
			}

			const href = self._getGoogleEndpoint( themeOptions );
			const $node = $( 'link[id=' + $usb.config( 'typography.fonts_id' ) + ']', $usb.iframe.contentDocument );

			if ( $node.length ) {
				$node.attr( 'href', href );

			} else {
				$( 'head', $usb.iframe.contentDocument ).append(
					'<link id="' + $usb.config( 'typography.fonts_id' )
					+ '" rel="stylesheet" href="'
					+ href
					+ '" media="all">'
				);
			}
		},

		/**
		 * Get the Google endpoint (CSS2 API, handles both static fonts and Variable Fonts)
		 *
		 * @param {{}} themeOptions The theme options
		 * @return {String} Returns the endpoint for connecting Google Fonts
		 */
		_getGoogleEndpoint: function( themeOptions ) {
			const self = this;

			var config = $usb.config( 'typography', {} ),
				axesMap = config.googleFontsAxes || {},
				usedFonts = self._getUsedFonts( themeOptions ),
				families = [];

			for ( const fontFamily in usedFonts ) {
				families.push( self._buildFamily( fontFamily, usedFonts[ fontFamily ], axesMap[ fontFamily ] ) );
			}

			// Create endpoint to connect Google Fonts via the CSS2 API
			// see https://developers.google.com/fonts/docs/css2
			if ( families.length ) {
				return config.googleapis_v2 + '?' + families.join( '&' ) + '&display=' + config.font_display;
			}
			return '';
		},

		/**
		 * Collect the Google fonts used in the typography settings (keyed by plain family name).
		 *
		 * @param {{}} themeOptions The theme options
		 * @return {{}} Map of font family => array of weight variants
		 */
		_getUsedFonts: function( themeOptions ) {
			var config = $usb.config( 'typography', {} ),
				googleFonts = config.googleFonts || {},
				additionalGoogleFonts = config.usedGoogleFonts || {},
				usedFonts = {};

			var tags = config.tags || []; // tags for typography
			for ( const i in tags ) {
				var tagProps = themeOptions[ tags[ i ] ];
				if ( ! $.isPlainObject( tagProps ) ) {
					continue;
				}
				// Get font family
				var fontFamily = tagProps[ 'font-family' ];
				if ( $ush.isUndefined( fontFamily ) || fontFamily === 'inherit' ) {
					continue;
				}
				// Check if the name is in the list of Google fonts
				if ( $ush.isUndefined( googleFonts[ fontFamily ] ) ) {
					continue;
				}
				if ( $ush.isUndefined( usedFonts[ fontFamily ] ) ) {
					usedFonts[ fontFamily ] = $ush.toString( googleFonts[ fontFamily ] ).split( ',' );
				}
			}

			// Include Additional Google Fonts as they might be used in the Design settings of the elements
			for ( const googleFont in additionalGoogleFonts ) {
				if ( $ush.isUndefined( usedFonts[ googleFont ] ) ) {
					usedFonts[ googleFont ] = $ush.toString( additionalGoogleFonts[ googleFont ] ).split( ',' );
				}
			}

			return usedFonts;
		},

		/**
		 * Build a single CSS2 "family=..." spec for one font
		 * Variable Fonts use axis ranges, static fonts use their exact available variants
		 * CSS2 requires axis tags sorted: lowercase tags first (alphabetically), then uppercase
		 *
		 * @param {String} fontFamily The font family name
		 * @param {Array} variants The available weight variants (e.g. [ '400', '700italic' ])
		 * @param {{}} axes The axes ranges for Variable Fonts (undefined for static fonts)
		 * @return {String} The "family=Name:tags@tuples" spec
		 */
		_buildFamily: function( fontFamily, variants, axes ) {
			variants = variants || [];

			var encodedFamily = fontFamily.split( ' ' ).join( '+' );
			var hasItalic = ( '' + variants.join( ',' ) ).indexOf( 'italic' ) > -1;
			var tagList, tuples;

			if ( $.isPlainObject( axes ) ) {

				// Variable Font: request the full range of every axis the font provides
				var tags = Object.keys( axes ).sort( function( a, b ) {
					var aLower = a.charAt( 0 ) >= 'a' && a.charAt( 0 ) <= 'z';
					var bLower = b.charAt( 0 ) >= 'a' && b.charAt( 0 ) <= 'z';
					if ( aLower !== bLower ) {
						return aLower ? -1 : 1;
					}
					return a < b ? -1 : ( a > b ? 1 : 0 );
				} );
				var rangeRow = tags.map( function( tag ) {
					return axes[ tag ].min + '..' + axes[ tag ].max;
				} );

				tagList = hasItalic ? [ 'ital' ].concat( tags ) : tags;
				tuples = hasItalic
					? [ '0,' + rangeRow.join( ',' ), '1,' + rangeRow.join( ',' ) ]
					: [ rangeRow.join( ',' ) ];

			} else if ( hasItalic ) {

				// Static font with italics: use the EXACT (ital,wght) variant pairs so we never
				// request a non-existent combination (CSS2 returns 400 for invalid tuples)
				tagList = [ 'ital', 'wght' ];
				var pairs = {};
				variants.forEach( function( variant ) {
					var ital = ( '' + variant ).indexOf( 'italic' ) > -1 ? 1 : 0;
					var weight = $ush.parseInt( variant ) || 400;
					pairs[ ital + ',' + weight ] = [ ital, weight ];
				} );
				tuples = Object.keys( pairs )
					.map( function( key ) { return pairs[ key ]; } )
					.sort( function( a, b ) { return a[0] - b[0] || a[1] - b[1]; } )
					.map( function( pair ) { return pair[0] + ',' + pair[1]; } );

			} else {

				// Static font without italics: a simple list of weights
				tagList = [ 'wght' ];
				var seen = {}, weights = [];
				variants.forEach( function( variant ) {
					var weight = $ush.parseInt( variant ) || 400;
					if ( ! seen[ weight ] ) {
						seen[ weight ] = true;
						weights.push( weight );
					}
				} );
				weights.sort( function( a, b ) { return a - b; } );
				if ( ! weights.length ) {
					weights = [ 400 ];
				}
				tuples = weights.map( String );
			}

			return 'family=' + encodedFamily + ':' + tagList.join( ',' ) + '@' + tuples.join( ';' );
		}

	} );

	// Export API
	$usb.fonts = new Fonts;

}( jQuery );
