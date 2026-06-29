/**
 * USOF Field: Typography Options.
 */
! function( $, undefined ) {

	$usof.field[ 'typography_options' ] = {

		/**
		 * Field initialization.
		 *
		 * @param {{}} options [optional]
		 */
		init: function( options ) {
			var self = this;

			/**
			 * @var {{}} Bindable events
			 */
			self._events = {
				changePropField: $ush.debounce( self._changePropField.bind( self ), 1 ),
				switchSection: self._switchSection.bind( self ),
			};

			// Variables
			self.fields = {}; // font properties
			self._usbParams = self.$row.data( 'usb-params' ) || {};

			// Elements
			self.$head = $( 'head' );
			self.$title = $( '> .usof-form-row-title', self.$row );
			self.$typographyVars = $( '#us-typography-vars' );

			// Get all fields and subscribe to changes
			$( '.usof-form-row-control:first [data-name]', self.$row ).each( function( _, node ) {
				var $node = $( node ),
					usofField = $node.data( 'usofField' );
				if ( $ush.isUndefined( usofField ) ) {
					usofField = $node.usofField();
				}
				if ( usofField instanceof $usof.field ) {
					var name = $node.data( 'name' );
					usofField
						.on( 'change', self._events.changePropField )
						.on( 'syncResponsiveState', function( field, screenName ) {
							// TODO: Fix verification via `$usb` object
							// Set a responsive screen from $usof field
							if ( self.isLiveBuilder() && $usb.find( 'preview' ) ) {
								$usb.preview.fieldSetResponsiveScreen( screenName );
							}
							// Specific functionality for the "Theme Options - Typography" page
							if ( ! self.isLiveBuilder() ) {
								// Render typography options in the preview field
								$ush.debounce_fn_1ms( self._renderFontPreview.bind( self ) );
								// Sync responsive fields within one typography control
								$.each( self.fields, function( name, _field ) {
									if ( field.name !== name ) {
										_field._usbSyncResponsiveState( _field, screenName );
									}
								} );
							}
						} );
					self.fields[ name ] = usofField;
				}
			} );

			// Handler for accordion section switch
			self.$row.on( 'click', '> .usof-form-row-title', self._events.switchSection );

			// TODO: Bring responsive values to a unified format and debug events!
			// Remove the code below after fixing it.
			if ( ! self.isLiveBuilder() ) {
				self.on( 'afterShow', () => {
					self._setFontProperties( self.getValue() );
				} );
			}
			// Render font preview after import
			$( window ).on( 'usof_typography_import', () => {
				self._renderFontPreview();
			} );

			// Populate "Available values" hints for the initially selected font on page load
			if ( self.fields['font-family'] ) {
				$ush.timeout( function() {
					self._syncAvailableValues( self.fields['font-family'].getCurrentValue() );
				}, 0 );
			}
		},

		/**
		 * Switch accordion section.
		 *
		 * @event handler
		 * @param {Event} e The Event interface represents an event which takes place in the DOM.
		 */
		_switchSection: function( e ) {
			var self = this;
			self.$row // expand or collapse accordion
				.toggleClass( 'expand', ! self.$row.hasClass( 'expand' ) )
				.siblings()
				.removeClass( 'expand' );
		},

		/**
		 * Update the clickable "Available values" hints in the description of the Font Weight
		 * and Font Stretch fields based on the currently selected font
		 *
		 * @param {String} fontFamily The font family.
		 */
		_syncAvailableValues: function( fontFamily ) {
			var self = this;

			// Resolve inherited fonts: "As in Global Text" / "As in Heading 1"
			fontFamily = self._resolveFontFamily( fontFamily );

			var googleFonts = $usof.getData( 'googleFonts' );
			var googleFontsAxes = $usof.getData( 'googleFontsAxes' );
			var uploadedFonts = $usof.getData( 'uploadedFonts' );
			if ( ! $.isPlainObject( googleFonts ) ) {
				googleFonts = {};
			}
			if ( ! $.isPlainObject( googleFontsAxes ) ) {
				googleFontsAxes = {};
			}
			if ( ! $.isPlainObject( uploadedFonts ) ) {
				uploadedFonts = {};
			}

			var uploadedFont = $.isPlainObject( uploadedFonts[ fontFamily ] ) ? uploadedFonts[ fontFamily ] : {};

			// Variable Font axes ranges
			var axes = {};
			if ( $.isPlainObject( googleFontsAxes[ fontFamily ] ) ) {
				axes = googleFontsAxes[ fontFamily ];
			} else if ( $.isPlainObject( uploadedFont.axes ) ) {
				axes = uploadedFont.axes;
			}

			// Font Weight values: a continuous range for Variable Fonts (min - max), or a
			// discrete list of available weights for static fonts
			var weightValues = [];
			var weightIsRange = false;
			if ( $.isPlainObject( axes.wght ) ) {
				// Variable Font: show the "wght" axis range
				weightValues = [ axes.wght.min, axes.wght.max ];
				weightIsRange = true;
			} else if ( googleFonts[ fontFamily ] ) {
				// Static Google font: its available numeric weight variants
				var seen = {};
				( '' + googleFonts[ fontFamily ] ).split( ',' ).forEach( function( variant ) {
					var weight = $ush.parseInt( variant );
					if ( weight && ! seen[ weight ] ) {
						seen[ weight ] = true;
						weightValues.push( weight );
					}
				} );
				weightValues.sort( function( a, b ) { return a - b; } );
			} else if ( uploadedFont.weight ) {
				// Static Uploaded font: its single configured weight
				var uploadedWeight = $ush.parseInt( uploadedFont.weight );
				if ( uploadedWeight ) {
					weightValues.push( uploadedWeight );
				}
			}

			// Font Stretch values - only Variable Fonts that expose a width "wdth" axis
			var stretchValues = [];
			var stretchIsRange = false;
			if ( $.isPlainObject( axes.wdth ) ) {
				stretchValues = [ axes.wdth.min + '%', axes.wdth.max + '%' ];
				stretchIsRange = true;
			}

			self._setAxisExamples( self.fields['font-weight'], 'wght', weightValues, weightIsRange );
			self._setAxisExamples( self.fields['bold-font-weight'], 'wght', weightValues, weightIsRange );
			self._setAxisExamples( self.fields['font-stretch'], 'wdth', stretchValues, stretchIsRange );

			// Hide Font Stretch if "wdth" is missing
			if ( ! $ush.isUndefined( self.fields['font-stretch'] ) ) {
				self.fields['font-stretch'].$row.toggleClass( 'hidden', ! stretchIsRange );
			}
		},

		/**
		 * Resolve an inherited font-family value to the actual font name
		 * "var(--h1-font-family)" (As in Heading 1) -> Heading 1 font
		 * "inherit" (As in Global Text) -> Global Text font
		 *
		 * @param {String} fontFamily The (possibly inherited) font family value
		 * @return {String} The resolved font family name
		 */
		_resolveFontFamily: function( fontFamily ) {
			var self = this;
			var parent = self.getParent();
			var parentValues = ( ! $ush.isUndefined( parent ) && typeof parent.getCurrentValues === 'function' )
				? parent.getCurrentValues()
				: {};

			// As in Heading 1 (H1 itself may further inherit from Global Text - handled below)
			if ( fontFamily === 'var(--h1-font-family)' ) {
				fontFamily = $ush.toString( ( parentValues.h1 || {} )['font-family'] || '' );
			}
			// As in Global Text
			if ( fontFamily === 'inherit' ) {
				fontFamily = $ush.toString( ( parentValues.body || {} )['font-family'] || '' );
			}
			return fontFamily;
		},

		/**
		 * Fill the "Available values" holder in a field description with clickable examples
		 *
		 * @param {$usof.field} field The target field (Font Weight / Bold / Font Stretch)
		 * @param {String} axis The axis tag ("wght" / "wdth")
		 * @param {Array} values The list of clickable values
		 * @param {Boolean} isRange Render the values as a continuous range "min - max" (Variable Fonts)
		 */
		_setAxisExamples: function( field, axis, values, isRange ) {
			if ( $ush.isUndefined( field ) ) {
				return;
			}
			var $holder = $( '.us-typography-axis[data-axis="' + axis + '"]', field.$row );
			if ( ! $holder.length ) {
				return;
			}
			var chips = ( values || [] ).map( function( value ) {
				return '<span class="usof-example">' + value + '</span>';
			} ).join( isRange ? ' &ndash; ' : ', ' );

			// Add a separator if a static example (e.g. the "var(--h1-...)" chip) precedes the holder
			if ( chips && $holder.prevAll( '.usof-example' ).length ) {
				chips = ', ' + chips;
			}
			$holder.html( chips );

			// Hide the whole description block when there are no clickable values at all
			var $desc = $holder.closest( '.usof-form-row-desc' );
			$desc.toggleClass( 'hidden', $desc.find( '.usof-example' ).length === 0 );
		},

		/**
		 * Set font properties.
		 *
		 * @param {{}} values The value '{name:value}', if the params is absent, the default will be set.
		 * @param {Boolean} quiet The quiet.
		 */
		_setFontProperties: function( values, quiet ) {
			var self = this;

			if ( self.isObjectValue( values ) ) {
				values = $ush.toPlainObject( values );
			}
			if ( ! $.isPlainObject( values ) ) {
				values = {};
			}

			for ( var name in self.fields ) {
				var usofField = self.fields[ name ];
				if ( usofField instanceof $usof.field ) {
					var value = $ush.isUndefined( values[ name ] )
						? usofField.getDefaultValue()
						: values[ name ];
					usofField.off( 'change' );
					usofField.setValue( value, quiet );
					usofField.on( 'change', self._events.changePropField );
				}
			}

			// Update available values hints for Font Weight / Font Stretch
			self._syncAvailableValues( values['font-family'] );
		},

		/**
		 * Property changes.
		 *
		 * @event handler
		 */
		_changePropField: function( usofField, value ) {
			var self = this;
			// Get font properties
			var props = {};
			for ( var name in self.fields ) {
				props[ name ] = self.fields[ name ].getValue();
			}

			// Forwarding preview parameters from an editable field, this is necessary for
			// the correct application of changes and is specific to the current field type
			var $usbField = usofField[ usofField instanceof $usof.field ? '$row' : '$field' ];
			self.$row.data( 'usbParams', $usbField.data( 'usb-params' ) || /* default */self._usbParams );

			// Set current value
			self.setCurrentValue( ! $.isEmptyObject( props ) ? props : '' );

			// Update available values hints for Font Weight / Font Stretch
			self._syncAvailableValues( props['font-family'] );

			// Render typography options in the preview field
			if ( ! self.isLiveBuilder() ) {
				$ush.debounce_fn_1ms( self._renderFontPreview.bind( self ) );
			}
		},

		/**
		 * Gets the current value.
		 *
		 * @return {{}} Returns the current value given the selected response state, if any.
		 */
		getCurrentValue: function() {
			var self = this, result = {};
			for ( var name in self.fields ) {
				result[ name ] = self.fields[ name ].getCurrentValue();
			}
			return result;
		},

		/**
		 * Set the value.
		 *
		 * @param {String} value The value to be selected.
		 * @param {Boolean} quiet Sets in quiet mode without events.
		 */
		setValue: function( value, quiet ) {
			var self = this;
			if ( $.isPlainObject( value ) ) {
				value = $ush.toString( value );
			}
			// Set font properties
			self._setFontProperties( value );
			// Set parent value
			self.parentSetValue( value );
			if ( quiet ) {
				self.trigger( 'change', [ value ] );
			}
		},

		/**
		 * Render typography options in the preview field.
		 * Note: Specific method is intended for output on page "Theme Options — Typography".
		 */
		_renderFontPreview: function() {
			var self = this;

			// Check the presence of a required field
			if ( ! self.fields['font-family'] ) {
				return;
			}

			// Get font properties
			var props = {};
			for ( var name in self.fields ) {
				props[ name ] = self.fields[ name ].getCurrentValue();
			}

			/**
			 * Get normalized font family.
			 *
			 * @param {String} value The value
			 * @return {String} Returns the correct font name (in quotes if necessary).
			 */
			function _getFontFamily( value ) {
				if ( value === 'none' || value === 'inherit' ) {
					return '';
				}
				if ( /* isWebsafe */value.indexOf( ',' ) === -1 && value.indexOf( ' ' ) > -1 ) {
					return '"' + value + '"';
				}
				return value;
			}

			// Get font family
			var origFontFamily = props['font-family'];
			props['font-family'] = _getFontFamily( origFontFamily );
			// Apply all options in preview
			var cssProp = $ush.clone( props ),
				$textPreview = $( '.usof-text-preview', self.fields['font-family'].$row );
			// Set font-weight for strong text
			$( 'strong', $textPreview ).css( 'font-weight', props[ 'bold-font-weight' ] );
			// Set font options in preview
			if ( ! $ush.isUndefined( cssProp['bold-font-weight'] ) ) {
				delete cssProp['bold-font-weight'];
			}
			// Remove property from preview if values are `none` or `inherit`
			if ( [ 'none', 'inherit' ].indexOf( origFontFamily ) > -1 ) {
				cssProp[ 'font-family' ] = '';
			}
			$textPreview.css( cssProp );

			// Set css variables for Typography
			if ( [ 'body', 'h1' ].indexOf( self.name ) > -1 ) {
				var parent = self.getParent();
				if ( ! $ush.isUndefined( parent ) ) {
					var parentValues = parent.getCurrentValues(),
						cssVars = [];
					// Get "Font family" for body
					if ( ( parentValues.body || {} )['font-family'] ) {
						cssVars.push( '--body-font-family:' + _getFontFamily( parentValues.body['font-family'] ) );
					}
					// Get H1 props
					if ( $.isPlainObject( parentValues.h1 ) ) {
						[ 'font-family', 'font-weight', 'bold-font-weight', 'font-stretch', 'font-style', 'text-transform' ].map( function( name ) {
							if ( ! $ush.isUndefined( parentValues.h1[ name ] ) ) {
								var value = parentValues.h1[ name ];
								// Responsive values may arrive as URL-encoded JSON or an already
								// decoded plain object, in both cases use the `default` state value
								if ( self.isObjectValue( value ) ) {
									value = $ush.toPlainObject( value );
								}
								if ( $.isPlainObject( value ) ) {
									value = value['default'] || '';
								}
								if ( name === 'font-family' ) {
									value = _getFontFamily( value );
								}
								if ( value ) {
									cssVars.push( '--h1-' + name + ':' + value );
								}
							 }
						} );
					}
					self.$typographyVars.text( ':root{' + cssVars.join( ';' ) + '}' );
				}
			}

			// Import the selected Google font for the preview
			if ( /* isWebsafe */origFontFamily.indexOf( ',' ) === -1 && origFontFamily.indexOf( 'var(--' ) === -1 ) {
				var googleFonts = $usof.getData( 'googleFonts' ) || {};
				var googleFontsAxes = $usof.getData( 'googleFontsAxes' ) || {};
				var fontHref = self._buildFontHref( origFontFamily, googleFonts, googleFontsAxes );
				if ( fontHref ) {
					var $linkToResource = $( 'link[data-font-for="' + self.name + '"]', self.$head );
					if ( ! $linkToResource.length ) {
						$linkToResource = $( '<link>', { rel: 'stylesheet', 'data-font-for': self.name, 'href': '' } );
						self.$head.append( $linkToResource );
					}
					if ( $linkToResource.attr( 'href' ) !== fontHref ) {
						$linkToResource.attr( 'href', fontHref );
					}
				}
			}
		},

		/**
		 * Build a stylesheet URL to load a selected Google font for the preview
		 * Variable Fonts are loaded via the CSS2 API with full axis ranges
		 * Static fonts via the legacy CSS API with their discrete variants
		 *
		 * @param {String} fontFamily The font family name.
		 * @param {{}} googleFonts Map of font family => comma-separated variants.
		 * @param {{}} googleFontsAxes Map of Variable Font family => axes ranges.
		 * @return {String} The stylesheet URL, or an empty string if the font is not loadable.
		 */
		_buildFontHref: function( fontFamily, googleFonts, googleFontsAxes ) {
			var encodedFamily = fontFamily.split( ' ' ).join( '+' );
			var axes = googleFontsAxes[ fontFamily ];

			// Variable Font: CSS2 with every axis range (CSS2 requires lowercase tags first, then uppercase)
			if ( $.isPlainObject( axes ) ) {
				var tags = Object.keys( axes );
				tags.sort( function( a, b ) {
					var aLower = a.charAt( 0 ) >= 'a' && a.charAt( 0 ) <= 'z';
					var bLower = b.charAt( 0 ) >= 'a' && b.charAt( 0 ) <= 'z';
					if ( aLower !== bLower ) {
						return aLower ? -1 : 1;
					}
					return a < b ? -1 : ( a > b ? 1 : 0 );
				} );
				var ranges = tags.map( function( tag ) {
					return axes[ tag ].min + '..' + axes[ tag ].max;
				} );

				var hasItalic = ( '' + ( googleFonts[ fontFamily ] || '' ) ).indexOf( 'italic' ) > -1;
				var tagList = hasItalic ? [ 'ital' ].concat( tags ) : tags;
				var tuples = hasItalic
					? [ '0,' + ranges.join( ',' ), '1,' + ranges.join( ',' ) ]
					: [ ranges.join( ',' ) ];

				return 'https://fonts.googleapis.com/css2?family=' + encodedFamily
					+ ':' + tagList.join( ',' ) + '@' + tuples.join( ';' ) + '&display=swap';
			}

			// Static font: legacy CSS API with discrete variants
			if ( googleFonts[ fontFamily ] && $usof.googlefontEndpoint ) {
				var urlManager = new URL( $usof.googlefontEndpoint );
				urlManager.searchParams.set( 'family', fontFamily + ':' + googleFonts[ fontFamily ] );
				return urlManager.toString();
			}

			return '';
		}
	};
}( jQuery );
