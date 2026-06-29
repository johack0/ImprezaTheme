/**
 * UpSolution Shortcode: us_cform.
 */
! function( $, _undefined ) {

	window.$us = window.$us || {};

	function usForm( container ) {
		const self = this;

		// Elements
		self.$form = $( container );
		if ( ! self.$form.hasClass( 'for_cform' ) ) {
			self.$form = $( '.w-form.for_cform', container );
		}
		self.$formH = $( '.w-form-h', self.$form );
		self.$dateFields = $( '.w-form-row.for_date input', self.$form );
		self.$message = $( '.w-form-message', self.$form );
		self.$reusableBlock = $( '.w-form-reusable-block', self.$form );
		self.$submit = $( '.w-btn', self.$form );

		// Private "Variables"
		self.opts = {};
		self.isFileValid = true;
		self.datepickerOpts = {};

		// Get form options
		var $formJson = $( '.w-form-json', self.$form );
		if ( $formJson.is('[onclick]') ) {
			self.opts = $formJson[0].onclick() || {};
			if ( ! $us.usbPreview() ) {
				$formJson.remove();
			}
		}

		// Init date pickers.
		if ( self.$dateFields.length ) {
			$( () => self.initDateField() );
		}

		// Add not-empty class when filling form fields.
		$( [
			'input[type=text]',
			'input[type=email]',
			'input[type=tel]',
			'input[type=number]',
			'input[type=date]',
			'input[type=search]',
			'input[type=url]',
			'input[type=password]',
			'textarea'
		].join(), self.$form ).each( ( _, input ) => {
			const $input = $( input );
			const $row = $input.closest( '.w-form-row' );
			if ( $input.attr( 'type' ) === 'hidden' ) {
				return;
			}
			$row.toggleClass( 'not-empty', input.value != '' );
			$input.on( 'input change', () => {
				$row.toggleClass( 'not-empty', input.value != '' );
			} );
		} );

		// Bindable events.
		self._events = {
			onChangeFile: self.onChangeFile.bind( self ),
			onSubmit: self.onSubmit.bind( self ),
		};

		// Events
		self.$form
			.on( 'change' , 'input[type=file]:visible', self._events.onChangeFile )
			.on( 'submit', self._events.onSubmit );
	};

	// Export API
	$.extend( usForm.prototype, {

		/**
		 * Get the file extension.
		 *
		 * @param {String} name The file name.
		 * @return {String} The file extension.
		 */
		getExtension: function( name ) {
			return $ush.toString( name ).split( '.' ).pop();
		},

		/**
		 * File extension validation
		 *
		 * @param {File} file The file object
		 * @param {String} accepts
		 * @param {Boolean} Returns true on success, false otherwise.
		 */
		_validExtension: function( file, accepts ) {
			const self = this;
			// If accepts are not set, then all files are validated.
			if ( ! accepts ) {
				return true;
			}
			accepts = $ush.toString( accepts ).split( ',' );
			for ( var i in accepts ) {
				var accept = $ush.toString( accepts[ i ] ).trim();
				if ( ! accept ) {
					continue;
				}
				// @link https://mimesniff.spec.whatwg.org
				if ( accept.indexOf( '/' ) > -1 ) {
					var acceptMatches = accept.split( '/' );
					if (
						file.type === accept
						|| (
							acceptMatches[1] === '*'
							&& $ush.toString( file.type ).indexOf( acceptMatches[0] ) === 0
						)
					) {
						return true;
					}
				} else if ( self.getExtension( file.name ) === accept.replace( /[^A-z\d]+/, '' ) ) {
					return true;
				}
			}
			return false;
		},

		/**
		 * Validation of required fields.
		 *
		 * @return {Boolean} Returns true on success, false otherwise.
		 */
		_requiredValidation: function() {
			const self = this;
			var errors = 0;
			$( '[data-required=true]', self.$form ).each( function( _, input ) {
				var $input = $( input ),
					isEmpty = $input.is( '[type=checkbox]' )
						? ! $input.is( ':checked' )
						: $input.val() == '',
					$row = $input.closest( '.w-form-row' );
				// Skip checkboxes and radio buttons
				if ( $row.hasClass( 'for_checkboxes' ) || $row.hasClass( 'for_radio' ) ) {
					return true;
				}
				// Check file fields validation
				if ( input.type === 'file' ) {
					isEmpty = isEmpty || ! self.isFileValid;
				}
				// Select validation
				if ( input.type === 'select-one' ) {
					isEmpty = $input.val() === $( 'option:first-child', $input ).val();
				}
				$row.toggleClass( 'check_wrong', isEmpty );
				if ( isEmpty ) {
					errors ++;
				}
			} );
			// Count required checkboxes separately
			$( '.for_checkboxes.required', self.$form ).each( ( _, node ) => {
				var $input = $( 'input[type=checkbox]', node ),
					isEmpty = ! $input.is( ':checked' );
				$input.closest( '.w-form-row' ).toggleClass( 'check_wrong', isEmpty );
				if ( isEmpty ) {
					errors ++;
				}
			} );
			// Show error message when the first (disabled) radio button is selected.
			$( '.for_radio.required', self.$form ).each( ( _, node ) => {
				var $input, isEmpty;
				if ( node.className.includes( 'pre_select_first_value' ) ) {
					$input = $( 'input[type=radio]:first', node );
					isEmpty = $input.is( ':checked' );
				} else {
					$input = $( 'input[type=radio]', node );
					isEmpty = ! $input.is( ':checked' );
				}
				$input.closest( '.w-form-row' ).toggleClass( 'check_wrong', isEmpty );
				if ( isEmpty ) {
					errors ++;
				}
			} );
			return ! errors;
		},

		/**
		 * Init date pickers.
		 */
		initDateField: function() {
			const self = this;
			$.each( self.$dateFields, ( _, input ) => {
				const $input = $( input );
				self.datepickerOpts.dateFormat = $input.data( 'date-format' );

				// Note: Check for the presence of the script, since the script may not always be
				// loaded when adding an element on the preview page, which is not critical
				try {
					$input.datepicker( self.datepickerOpts );

					// Show datepicker in popup
					if ( $input.closest( '.w-popup-wrap' ).length ) {
						$input.on( 'click', ( e ) => {
							var $datepicker = $( '#ui-datepicker-div' ),
								datepickerHeight = $datepicker.outerHeight(),
								inputBounds = e.currentTarget.getBoundingClientRect();

							// First try to place the datepicker below the field
							// then (if there is not enough space below) - above the field
							if ( window.innerHeight - ( inputBounds.bottom + datepickerHeight ) > 0 ) {
								$datepicker.css( {
									position: 'fixed',
									left: inputBounds.left,
									top: ( inputBounds.top + inputBounds.height )
								} );
							} else {
								$datepicker.css( {
									position: 'fixed',
									left: inputBounds.left,
									top: ( inputBounds.top - datepickerHeight ),
								} );
							}

						} );
					}

				} catch( e ) {}
			} );
		},

		/**
		 * File field change handler.
		 *
		 * @event handler
		 * @param {Event} e
		 */
		onChangeFile: function( e ) {
			const self = this;
			var errMessage = '',
				input = e.target,
				$input = $( input ),
				accept = $input.attr( 'accept' ) || '',
				maxSize = $input.data( 'max_size' ) || $input.data( 'std' ) || 0;

			// Checking the list of uploaded files
			if ( input.files.length ) {
				for ( var i in input.files ) {
					if ( errMessage ) {
						break;
					}
					// Get a file object from a list.
					var file = input.files[ i ];
					if ( ! ( file instanceof File ) ) {
						continue;
					}
					// File extension validation.
					if ( ! self._validExtension( file, accept ) ) {
						errMessage = ( self.opts.messages.err_extension || '' )
							.replace( '%s', self.getExtension( file.name ) );
					}
					// File size validation.
					if ( ! errMessage && file.size > ( parseFloat( maxSize ) * 1048576/* MB to KB */ ) ) {
						errMessage = ( self.opts.messages.err_size || '' )
							.replace( '%s', maxSize );
					}
				}
			}
			$input
				.closest( '.for_file' )
				.toggleClass( 'check_wrong', ! ( self.isFileValid = ! errMessage ) )
				.find( '.w-form-row-state' )
				.html( errMessage || self.opts.messages.err_empty );
		},

		/**
		 * Form submission handler.
		 *
		 * @event handler
		 * @param {Event} e
		 */
		onSubmit: function( e ) {
			const self = this;

			e.preventDefault();

			// Clear form messages
			self.$message
				.usMod( 'type', false )
				.html( '' );

			if (
				// Prevent double-sending.
				self.$submit.hasClass( 'loading' )
				// If not all required fields are filled.
				|| ! self._requiredValidation()
				// If the data is not valid.
				|| ! self.isFileValid
			) {
				return;
			}

			// Show data upload preloader.
			self.$submit.addClass( 'loading' );

			const formData = window.FormData ? new FormData( self.$form[0] ) : self.$form.serialize();

			// reCAPTCHA validation
			if ( self.$form.hasClass( 'validate_by_recaptcha' ) ) {
				grecaptcha.ready( () => {
					try {
						grecaptcha.execute( self.opts.recaptcha_site_key, { action: 'submit' } ).then( ( token ) => {
							formData.append( 'g-recaptcha-response', token );
							sendAjaxRequest();
						} );
					} catch ( e ) {
						self.$message
							.usMod( 'type', 'error' )
							.html( self.opts.messages.err_recaptcha_keys );
						self.$submit
							.removeClass( 'loading' );
					}
				} );
			} else {
				sendAjaxRequest();
			}

			// Send form data to the server.
			function sendAjaxRequest() {
				$.ajax( {
					type: 'POST',
					url: self.opts.ajaxurl,
					data: formData,
					cache: false,
					processData: false,
					contentType: false,
					dataType: 'json',
					success: function( res ) {
						$( '.w-form-row.check_wrong', self.$form )
							.removeClass( 'check_wrong' );
						if ( res.success ) {

							// Redirect to specified URL
							if ( res.data.redirect_url ) {
								window.location.replace( res.data.redirect_url );
								return;
							}

							// Open popup by selector
							if ( res.data.popup_selector ) {
								const $popupTrigger = $( res.data.popup_selector ).find( '.w-popup-trigger' );
								if ( $popupTrigger.length ) {
									$popupTrigger.trigger( 'click' );
								}
							}

							// Show success message
							if ( res.data.message ) {
								self.$message
									.usMod( 'type', 'success' )
									.html( res.data.message );
							}

							// Show reusable block
							if ( self.$reusableBlock.length ) {
								self.$reusableBlock.slideDown( 400 );
							}

							// Close popup
							if ( self.opts.close_popup_after_sending ) {
								const $popupCloser = self.$form.closest( '.w-popup-wrap' ).find( '.w-popup-closer' );
								if ( $popupCloser.length ) {
									$popupCloser.trigger( 'click' );
								}
							}

							// Hide form
							if ( self.opts.hide_form_after_sending ) {

								// Scroll to the top of the form if the form outside the viewport
								const formPos = self.$form.offset().top;
								const scrollTop = $us.$window.scrollTop();
								if (
									! $ush.isNodeInViewport( self.$form[0] )
									|| formPos >= ( scrollTop + window.innerHeight )
									|| scrollTop >= formPos
								) {
									$us.$htmlBody.animate( { scrollTop: formPos - $us.header.getInitHeight() }, 400 );
								}

								self.$formH.slideUp( 400 );
							}

							// Clear form
							$( '.w-form-row.not-empty', self.$form )
								.removeClass( 'not-empty' );
							$( 'input[type=text], input[type=email], textarea', self.$form )
								.val( '' );
							self.$form
								.trigger( 'usCformSuccess', res )
								.get( 0 )
								.reset();

							// Failed sending
						} else {
							if ( $.isPlainObject( res.data ) ) {
								for ( const fieldName in res.data ) {
									if ( fieldName === 'empty_message' ) {
										$resultField.usMod( 'type', 'error' );
										continue;
									}
									if ( fieldName === 'reCAPTCHA' && res.data[ fieldName ].error_message ) {
										self.$message
											.usMod( 'type', 'error' )
											.html( res.data.reCAPTCHA.error_message );
									}
									$( `[name="${fieldName}"]`, self.$form )
										.closest( '.w-form-row' )
										.addClass( 'check_wrong' )
										.find( '.w-form-row-state' )
										.html( res.data[ fieldName ][ 'error_message' ] || '' );
								}
							} else {
								self.$message
									.usMod( 'type', 'error' )
									.html( res.data );
							}
						}
					},
					complete: () => {
						self.$submit.removeClass( 'loading' );
					}
				} );
			}
		}

	} );

	$.fn.usForm = function() {
		return this.each( function() {
			$( this ).data( 'usForm', new usForm( this ) );
		} );
	};

	$( () => $( '.w-form.for_cform' ).usForm() );

}( jQuery );
