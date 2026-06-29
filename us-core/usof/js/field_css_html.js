/**
 * USOF Field: CSS / HTML
 */
! function( $, _undefined ) {

	window.$ush = window.$ush || {};

	if ( $ush.isUndefined( window.$usof ) ) {
		return;
	}

	$usof.field[ 'css' ] = $usof.field[ 'html' ] = {

		/**
		 * Initializes the object.
		 */
		init: function() {
			const self = this;

			// Private "Variables"
			self.editor = null;
			self.editorDoc = null;
			self._params = {};
			self._dynamicLabels = {};

			// Bindable events
			self._events = {
				editorChange: self._editorChange.bind( self ),
				editorFocused: self._editorFocused.bind( self ),
			};

			// Init CodeEditor
			if ( wp.hasOwnProperty( 'codeEditor' ) ) {
				var $params = $( '.usof-form-row-control-params', self.$row );
				if ( $params.is( '[onclick]' ) ) {
					self._params = $params[0].onclick() || {};
					$params.removeAttr( 'onclick' );
				}
				if ( self._params.editor !== false ) {
					self._params.editor.codemirror.lint = false;
					self.editor = wp.codeEditor.initialize( self.$input[0], self._params.editor || {} );
					self.editorDoc = self.editor.codemirror.getDoc();
					self.setValue( self.$input.val() );
					// Events
					self.editor.codemirror.on( 'focus', self._events.editorFocused );
					self.editor.codemirror.on( 'blur', self._events.editorFocused );
				}

			} else {
				self.$input.on( 'keyup', () => {
					self.parentSetValue( self.getValue() );
					self.setValue( self.$input.val() );
				} );
			}

			// Dynamic values for HTML
			if ( self.type === 'html' ) {
				self.initDynamicValue();
			}
		},

		/**
		 * Editor change.
		 *
		 * @event handler
		 */
		_editorChange: function() {
			this.parentSetValue( this.getValue() );
		},

		/**
		 * Focus state class delegation.
		 *
		 * @event handler
		 * @param {{}} _ The editor's internal object.
		 * @param {FocusEvent} e
		 */
		_editorFocused: function( _, e ) {
			this.$row.toggleClass( 'focused', ( e instanceof FocusEvent ) );
		},

		/**
		 * Determines if content encoded.
		 *
		 * @return {Boolean} True if content encoded, False otherwise.
		 */
		isContentEncoded: function() {
			return ( this._params || {} ).encoded || false;
		},

		/**
		 * Set the value
		 *
		 * @param {String} value The value
		 * @param {Boolean} isDynamicValueUse
		 */
		setValue: function( value, isDynamicValueUse = false ) {
			const self = this;
			if ( self.isContentEncoded() && ! isDynamicValueUse ) {
				value = $ush.rawurldecode( $ush.base64Decode( value ) );
			}
			if ( ! $ush.isUndefined( self.editor ) && wp.hasOwnProperty( 'codeEditor' ) ) {
				self.editorDoc.off( 'change', self._events.editorChange );
				self.editorDoc.setValue( value );
				self.editorDoc.on( 'change', self._events.editorChange );

				// Note: CodeMirror on the JS side calculates positions and line numbers,
				// so each time the editor is initialized or a value is set, you need
				// to call refresh to display it correctly.
				$ush.timeout( () => { self.editorDoc.cm.refresh() }, 50 );

				if ( self.popupId && ! isDynamicValueUse ) {
					self.setDynamicValue( value );
				}
			}
		},

		/**
		 * Get the value.
		 *
		 * @return {String} The value.
		 */
		getValue: function() {
			const self = this;
			const value = ( ! $ush.isUndefined( self.editor ) && wp.hasOwnProperty( 'codeEditor' ) )
					? self.editorDoc.getValue()
					: self.$input.val();
			return (
				self.isContentEncoded()
					? $ush.base64Encode( $ush.rawurlencode( value ) )
					: value
			);
		}
	};

	// Dynamic values functionality
	$.extend( $usof.field[ 'html' ], {

		/**
		 * Initializes the dynamic value.
		 */
		initDynamicValue: function() {
			const self = this;

			// Elements
			self.$valueSelected = $( '.usof-form-input-dynamic-value', self.$row );
			self.$inputGroup = $( '.usof-form-input-group', self.$row );
			self.$codeMirror = $( '.CodeMirror', self.$row );

			// Private "Variables"
			self.popupId = $( '[data-popup-show]:first', self.$row ).data( 'popup-show' );

			// Bindable events.
			$.extend( self._events, {
				selectDynamicVariable: self.selectDynamicValue.bind( self ),
				removeDynamicValue: self.removeDynamicValue.bind( self ),
			} );

			// Create a new popup support dynamic variables
			if ( self.popupId ) {
				self.popup = new $usof.popup( self.popupId, {
					closeOnEsc: true, // close the popup by pressing Escape
					closeOnBgClick: true, // close the popup when user clicks on the dark overlay
					// Fires after first initialization
					init: function() {
						/*popup*/this.$container
							.off( 'click' )
							.on( 'click', '[data-dynamic-value]', self._events.selectDynamicVariable )
							.find( '[data-dynamic-value]' )
							.each( ( _, node ) => {
								const $node = $( node );
								if ( $node.data( 'dynamic-label' ) ) {
									self._dynamicLabels[ $node.data( 'dynamic-value' ) ] = $node.data( 'dynamic-label' );
									$node.removeAttr( 'data-dynamic-label' );
								}
							} );
					},
					// Handler is called before the popup show
					beforeShow: function() {
						// Set or remove active class
						$( '[data-dynamic-value]', /*popup*/this.$container ).removeClass( 'active' );
						var value = $ush.rawurldecode( $ush.base64Decode( self.getValue() ) );
						if ( self.isDynamicVariable( value ) ) {
							$( `[data-dynamic-value="${value}"]`, /*popup*/this.$container ).addClass( 'active' );
						}
					}
				} );

				// Check the initialization of the popup
				if ( $.isEmptyObject( self.popup ) ) {
					console.error( 'Failed to initialize popup' );
				}
			}

			// Handler for remove the dynamic value
			self.$row.on( 'click', '.action_remove_dynamic_value', self._events.removeDynamicValue );
		},

		/**
		 * Handler for select a dynamic value in a popup.
		 *
		 * @event handler
		 * @param {Event} e
		 */
		selectDynamicValue: function( e ) {
			const self = this;

			e.preventDefault();

			self.setDynamicValue( $( e.target ).data( 'dynamic-value' ) );

			if ( self.popupId ) {
				$usof.hidePopup( $ush.toString( self.popupId ) );
			}
		},

		/**
		 * Set dynamic value.
		 *
		 * @param {String} value The dynamic value.
		 */
		setDynamicValue: function( value ) {
			const self = this;

			value = $ush.toString( value );

			// Hide dynamic value
			if (
				! self.isDynamicVariable( value )
				|| ! self._dynamicLabels[ value ]
			) {
				self.$valueSelected.addClass( 'hidden' );
				self.$inputGroup.removeClass( 'active' );
				self.$codeMirror.removeClass( 'hidden' );
				self.editor.codemirror.focus();

				// Show dynamic value
			} else {
				const title = self._dynamicLabels[ value ] || value;
				self.$valueSelected
					.removeClass( 'hidden' )
					.find( '.usof-form-input-dynamic-value-title' )
					.attr( 'title', title )
					.text( title );
				self.$inputGroup.addClass( 'active' );
				self.$codeMirror.addClass( 'hidden' );
			}

			self.setValue( value, true );
			self._editorChange();
		},

		/**
		 * Handler for remove the dynamic value.
		 *
		 * @event handler
		 * @param {Event} e
		 */
		removeDynamicValue: function( e ) {
			e.preventDefault();
			e.stopPropagation();
			this.setDynamicValue( '' );
		}

	} );
}( jQuery );
