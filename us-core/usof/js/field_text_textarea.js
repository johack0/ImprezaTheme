/**
 * USOF Field: Text / Textarea
 */
! function( $, _undefined ) {
	"use strict";

	if ( $ush.isUndefined( window.$usof ) ) {
		return;
	}

	$usof.field[ 'text' ] = {
		/**
		 * Initializes the object.
		 */
		init: function() {

			const self = this;

			// Private "Variables"
			self._dynamicLabels = {};

			// Elements
			self.$text = $( 'input[type=text]', self.$row );

			// Bindable events
			self._events = {
				blurField: self._blurField.bind( self ),
				// NOTE: debounce is used to get the correct value when paste text.
				changeField: $ush.debounce( self.changeField.bind( self ), 1 ),
				setExampleValue: self.setExampleValue.bind( self ),
				syncCurrentValue: self.syncCurrentValue.bind( self ),
			};

			self.initDynamicValue();

			// Events
			self.$row
				.on( 'click', '.usof-example', self._events.setExampleValue )
				.on( 'change paste keyup', 'input[type=text]', self._events.changeField )
				.on( 'blur', 'input[type=text]', self._events.blurField );

			if ( self.hasResponsive() ) {
				self.on( 'setResponsiveState', self._events.syncCurrentValue );
			}
		},

		/**
		 * Handler for set the value from the example.
		 *
		 * @event handler
		 * @param {Event} e
		 */
		setExampleValue: function( e ) {
			const self = this;
			const value = $( e.target ).closest( '.usof-example' ).html() || '';

			self.$text.val( value );
			self.setCurrentValue( value );
		},

		/**
		 * Handler for changes in the current text field.
		 *
		 * @event handler
		 * @param {Event} e
		 */
		changeField: function( e ) {
			this.setCurrentValue( e.target.value );
		},

		/**
		 * Handler for blur in the current text field.
		 *
		 * @event handler
		 * @param {Event} e
		 */
		_blurField: function( e ) {
			this.trigger( 'blur', this.getCurrentValue() );
		},

		/**
		 * Sync the value for the current screen.
		 *
		 * @event handler
		 */
		syncCurrentValue: function() {
			this.$text.val( this.getCurrentValue() );
		},

		/**
		 * Set the value.
		 *
		 * @param {String} value The value to be selected.
		 * @param {Boolean} quiet Sets in quiet mode without events.
		 */
		setValue: function( value, quiet ) {
			const self = this;

			self.parentSetValue( value, quiet );
			self.syncCurrentValue();

			if ( self.popupId ) {
				self.setDynamicValue( value );
			}
		}
	};

	// TODO: Add support for responsive values
	$usof.field[ 'textarea' ] = {
		/**
		 * Initializes the object.
		 */
		init: function() {
			const self = this;

			// Private "Variables"
			self._dynamicLabels = {};

			// This var is needed for dynamic values functionality
			self.$text = self.$input;

			// Bindable events
			self._events = {
				setExampleValue: self.setExampleValue.bind( self ),
			};

			// Events
			self.$row.on( 'click', '.usof-example', self._events.setExampleValue );

			// Note: debounce is used to get the correct value when paste text
			self.$input.on( 'change paste keyup', $ush.debounce( () => {
				self.trigger( 'change', [ self.getValue() ] );
			} ) );

			self.initDynamicValue();
		},

		/**
		 * Set the value.
		 *
		 * @param {String} value The value to be selected.
		 * @param {Boolean} quiet Sets in quiet mode without events.
		 */
		setValue: function( value, quiet ) {
			const self = this;

			self.parentSetValue( value, quiet );

			if ( self.popupId ) {
				self.setDynamicValue( value );
			}
		},

		/**
		 * Set example value.
		 *
		 * @event handler
		 * @param {Event} e The Event interface represents an event which takes place in the DOM
		 */
		setExampleValue: function( e ) {
			this.setValue( $( e.target ).closest( '.usof-example' ).html() || '' );
		}
	};

	// Dynamic values functionality
	const dynamicValues = {

		/**
		 * Initializes the dynamic value.
		 */
		initDynamicValue: function() {
			const self = this;

			// Elements
			self.$valueSelected = $( '.usof-form-input-dynamic-value', self.$row );
			self.$inputGroup = $( '.usof-form-input-group', self.$row );

			// Change unique id if field inside groups to avoid id duplicates
			if ( self.$row.parents( '.usof-form-group-item' ).length ) {

				self.popupId = $ush.uniqid();
				$( '[data-popup-id]', self.$row ).attr( 'data-popup-id', self.popupId );
				$( '[data-popup-show]', self.$row ).attr( 'data-popup-show', self.popupId );

			} else {
				self.popupId = $( '[data-popup-show]:first', self.$row ).data( 'popup-show' );
			}

			// Bindable events.
			self._events.selectDynamicVariable = self.selectDynamicValue.bind( self );
			self._events.removeDynamicValue = self.removeDynamicValue.bind( self );

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
						var value = $ush.toString( self.$text.val() );
						if ( self.isDynamicVariable( value ) ) {
							$( `[data-dynamic-value="${value}"]`, /*popup*/this.$container ).addClass( 'active' );
						}
					}
				} );

				// Check the initialization of the popup
				if ( $.isEmptyObject( self.popup ) ) {
					return;
				}
			}

			// Handler for remove the dynamic value
			self.$row.on( 'click', '.action_remove_dynamic_value', self._events.removeDynamicValue );

			if ( self.isWPBakeryParamValue() ) {
				self.setValue( self.$input.val() );
			}
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
		 * Set or unset a dynamic value.
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
				self.$text.removeClass( 'hidden' );
				self.$inputGroup.removeClass( 'active' );
				self.$text[0].focus();

				// Show dynamic value
			} else {
				const title = self._dynamicLabels[ value ] || value;
				self.$valueSelected
					.removeClass( 'hidden' )
					.find( '.usof-form-input-dynamic-value-title' )
					.attr( 'title', title )
					.text( title );

				self.$text.addClass( 'hidden' );
				self.$inputGroup.addClass( 'active' );
			}

			self.$text.val( value ).trigger( 'change' );
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
	};

	$.extend( $usof.field[ 'text' ], dynamicValues );
	$.extend( $usof.field[ 'textarea' ], dynamicValues );

}( jQuery );
