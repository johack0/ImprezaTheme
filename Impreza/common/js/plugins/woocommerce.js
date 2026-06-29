! ( function( $, _undefined ) {

	const $ush = window.$ush || {};

	// The last AJAX object request.
	var lastUpdateCartRequest;

	/**
	 * UpSolution WooCommerce elements.
	 * Note: All classes and key elements from WooCommerce are retained
	 *
	 * The code depends on:
	 * 	- `../plugins/woocommerce/assets/js/frontend/cart.js`
	 * 	- `../plugins/woocommerce/assets/js/frontend/checkout.js`
	 *
	 * @requires $us.$body
	 * @requires $us.$canvas
	 * @requires $ush.debounce
	 * @requires $ush.timeout
	 */
	function WooCommerce() {
		const self = this;

		// Elements
		self.$cart = $( '.w-cart' );
		self.$cartCloser = $( '.w-cart-closer', self.$cart );
		self.$cartLink = $( '.w-cart-link', self.$cart );
		self.$pageContent = $( '#page-content' );
		self.$notice = $( '.w-wc-notices.woocommerce-notices-wrapper:first', $us.$canvas );
		self.$addToCart = $( '.w-post-elm.add_to_cart', $us.$canvas );

		// Private "Variables"
		self.isCartLayoutPanel = self.$cart.hasClass( 'layout_left_panel' ) || self.$cart.hasClass( 'layout_right_panel' );
		self.isCartOpen = false;
		self.restVars = {};

		// Event handlers
		self._events = {
			ajaxAddToCart: self.ajaxAddToCart.bind( self ),
			applyCouponCode: self.applyCouponCode.bind( self ),
			changeCartQuantity: self.changeCartQuantity.bind( self ),
			couponCodeChange: self.couponCodeChange.bind( self ),
			couponDisplaySwitch: self.couponDisplaySwitch.bind( self ),
			enterCouponCode: self.enterCouponCode.bind( self ),
			showNotification: self.showNotification.bind( self ),
			loginFieldKeydown: self.loginFieldKeydown.bind( self ),
			minusCartQuantity: self.minusCartQuantity.bind( self ),
			moveNotifications: self.moveNotifications.bind( self ),
			clickOutsideCart: self.clickOutsideCart.bind( self ),
			plusCartQuantity: self.plusCartQuantity.bind( self ),
			removeCartItem: self.removeCartItem.bind( self ),
			showLoginForm: self.showLoginForm.bind( self ),
			submitLoginForm: self.submitLoginForm.bind( self ),
			updateCart: self.updateCart.bind( self ),
			updatedCartTotals: self.updatedCartTotals.bind( self ),
			showCartOnClick: self.showCartOnClick.bind( self ),
			showCartOnKeyup: self.showCartOnKeyup.bind( self ),
			hideCartOnKeyup: self.hideCartOnKeyup.bind( self ),
			redirectToCartOnMobile: self.redirectToCartOnMobile.bind( self ),
		};

		// Init cart elements
		if ( self.isCart() ) {

			self.$cartNotification = $( '.w-cart-notification', self.$cart );

			// Events
			self.$cartNotification.on( 'click', () => {
				self.$cartNotification.fadeOutCSS();
			} );

			// Cart closer button click events for both mobiles and desktops
			if ( self.isCartLayoutPanel ) {
				self.$cartCloser.on( 'click', () => self._toggleCart( false ) );
			}

			// Redirect to cart page on mobile devices.
			if ( $.isMobile ) {
				self.$cart.on( 'click', '.w-cart-link', self._events.redirectToCartOnMobile );

				// Handle click event on desktop devices with panel layouts
			} else {
				if ( self.$cart.hasClass( 'drop_on_click' ) || self.$cart.is( '[class*="_panel"]' ) ) {
					// need to separate these events because keyup event also manages focus
					self.$cartLink.on( 'keyup', self._events.showCartOnKeyup );
					self.$cartLink.on( 'click', self._events.showCartOnClick );
				}

				$us.$body.on( 'keyup', self._events.hideCartOnKeyup );

				// Cart closer button events for desktops only
				if ( self.isCartLayoutPanel ) {
					self.$cartCloser.on( 'keyup', ( e ) => {
						if ( e.keyCode === $ush.ENTER_KEYCODE ) {
							self._toggleCart( false, self.$cartLink );
						}
					} );
				}
			}

			$us.$body
				// Events of `../plugins/woocommerce/assets/js/frontend/add-to-cart.js`,
				// `../plugins/woocommerce/assets/js/frontend/cart-fragments.js`
				.on( 'wc_fragments_loaded wc_fragments_refreshed', self._events.updateCart )
				// Events of `../plugins/woocommerce/assets/js/frontend/add-to-cart.js`
				.on( 'added_to_cart', self._events.showNotification )
				.on( 'removed_from_cart', self._events.updateCart );
		}

		if ( self.isCartPage() ) {

			// REST API vars
			self.restVars = $( '.w-cart-table.woocommerce-cart-form:visible', $us.$canvas )
				.data( 'rest-vars' );

			// Events
			$us.$body
				.on( 'change', '.w-wc-coupon-form input', self._events.couponCodeChange )
				.on( 'keyup', '.w-wc-coupon-form input', self._events.enterCouponCode )
				.on( 'click', '.w-wc-coupon-form button', self._events.applyCouponCode )
				.on( 'click', 'a.remove', self._events.removeCartItem )
				// Events of `../plugins/woocommerce/assets/js/frontend/cart.js`
				.on( 'applied_coupon removed_coupon', self._events.couponDisplaySwitch )
				.on( 'updated_cart_totals', self._events.updatedCartTotals );

			// Initializing controls after the ready document
			$( 'input.qty', $us.$canvas ).trigger( 'initControls' );

			// Get the last active request for cart updates
			$.ajaxPrefilter( ( _, originalOptions, jqXHR ) => {
				const data = $ush.toString( originalOptions.data );
				if ( data.indexOf( '&update_cart' ) > -1 ) {
					lastUpdateCartRequest = jqXHR;
				}
				// Distance information updates in shortcode `[us_cart_shipping]`
				if ( data.indexOf( '&us_calc_shipping' ) > -1 ) {
					jqXHR.done( ( res ) => {
						$( '.w-cart-shipping .woocommerce-shipping-destination' )
							.html( $( '.w-cart-shipping:first .woocommerce-shipping-destination', res ).html() );
					} );
				}
			} );

			$( '.w-cart-shipping form.woocommerce-shipping-calculator', $us.$canvas )
				.append( '<input type="hidden" name="us_calc_shipping">' );
		}

		if ( self.isCheckoutPage() ) {
			// Events
			$us.$body
				.on( 'change', '.w-wc-coupon-form input', self._events.couponCodeChange )
				.on( 'keyup', '.w-wc-coupon-form input', self._events.enterCouponCode )
				.on( 'click', '.w-wc-coupon-form button', self._events.applyCouponCode )
				// Events of `../plugins/woocommerce/assets/js/frontend/checkout.js`
				.on( 'applied_coupon_in_checkout removed_coupon_in_checkout', self._events.couponDisplaySwitch )
				.on( 'applied_coupon_in_checkout removed_coupon_in_checkout checkout_error', self._events.moveNotifications )
				.on( 'click', '.w-checkout-login .showlogin', self._events.showLoginForm )
				.on( 'click', '.w-checkout-login button', self._events.submitLoginForm )
				.on( 'keydown', '.w-checkout-login input, .w-checkout-login button', self._events.loginFieldKeydown );

			// Blocks the form from being submitted if the coupon field is in focus
			// and the Enter key is pressed, this allows the coupon to be applied
			// correctly, otherwise the form will simply be submitted.
			const $couponField = $( '.w-wc-coupon-form input', $us.$canvas );
			$us.$document.on( 'keypress', ( e ) => {
				if ( e.keyCode === $ush.ENTER_KEYCODE && $couponField.is( ':focus' ) ) {
					e.preventDefault();
				}
			} );
		}

		// "Add to cart" via Ajax
		if( $us.$body.hasClass( 'us-ajax_add_to_cart' ) ) {
			$us.$body.on( 'click', '.single_add_to_cart_button:not(.disabled)', self._events.ajaxAddToCart );
		}

		// Quantity Events
		$us.$body
			.on( 'click', '.quantity input.minus', self._events.minusCartQuantity )
			.on( 'click', '.quantity input.plus', self._events.plusCartQuantity )
			.on( 'change initControls', '.quantity input.qty', self._events.changeCartQuantity );

		$us.$document.on( 'ajaxComplete', ( _, jqXHR, settings ) => {

			if (
				settings.url.includes( 'add_to_cart' )
				&& self.$cart.hasClass( 'open_on_ajax' )
				&& self.isCartLayoutPanel
			) {
				self._toggleCart( true );
				// waiting for transition - cannot focus element with visibility:hidden
				self.$cart.on( 'transitionend', () => {
					self.$cartCloser[0].focus();
					self.$cart.off( 'transitionend' );
				} );
			}

			// Skip JSON string
			if ( String( jqXHR.responseText ).charAt(0) === '{' ) {
				return;
			}

			const $fragment = $( new DocumentFragment ).append( jqXHR.responseText );

			// Updated only visible "Cart Totals" elements, bypassing WooCommerce core logic. See #3935 for details.
			if ( self.isCartPage() && $( '.cart_totals', $fragment ).length > 1 ) {
				const notices = self.$notice.html();
				self.$pageContent.html( $( '.l-main', $fragment ) );
				self.$notice = $( '.w-wc-notices.woocommerce-notices-wrapper:first', $us.$canvas );
				// Restore notifications
				if ( notices ) {
					self.$notice.html( notices );
				}
			}

			// Intercept messages after apply a coupon.
			if ( String( settings.url ).includes( 'wc-ajax=apply_coupon' ) ) {
				const $message = $( '.woocommerce-error, .woocommerce-message', $fragment );
				if ( $message.length > 0 ) {
					self.$notice.html( $message.clone() );
				} else {
					self.$notice.html( '' );
				}
			}
		} );
	}

	// Checkout/Cart Page
	$.extend( WooCommerce.prototype, {

		/**
		 * Determines if is cart.
		 *
		 * @return {boolean} True if cartesian, False otherwise.
		 */
		isCart: function() {
			return this.$cart.length > 0;
		},

		/**
		 * Determines if current cartesian page.
		 *
		 * @return {boolean} True if current cartesian page, False otherwise.
		 */
		isCartPage: function() {
			return $us.$body.hasClass( 'woocommerce-cart' );
		},

		/**
		 * Determines if current checkout page.
		 *
		 * @return {boolean} True if current checkout page, False otherwise
		 */
		isCheckoutPage: function() {
			return $us.$body.hasClass( 'woocommerce-checkout' );
		},

		/**
		 * Update cart elements.
		 */
		updateCart: function() {
			const self = this;
			$.each( self.$cart, ( _, cart ) => {
				var $cart = $( cart ),
					$cartQuantity = $( '.w-cart-quantity', $cart ),
					miniCartAmount = $( '.us_mini_cart_amount:first', $cart ).text();

				if (
					$cart.hasClass( 'opened' )
					&& ! $cart.hasClass( 'drop_on_click' )
					&& ! $cart.hasClass( 'layout_left_panel' )
					&& ! $cart.hasClass( 'layout_right_panel' )
				) {
					$cart.removeClass( 'opened' );
				}

				if ( miniCartAmount !== _undefined ) {
					miniCartAmount = String( miniCartAmount ).match( /\d+/g );
					$cartQuantity.html( miniCartAmount > 0 ? miniCartAmount : '0' );
					$cart[ miniCartAmount > 0 ? 'removeClass' : 'addClass' ]( 'empty' );
				} else {
					// fallback in case our action wasn't fired somehow
					var total = 0;
					$( '.quantity', $cart ).each( ( _, quantity ) => {
						var matches = String( quantity.innerText ).match( /\d+/g );

						if ( matches ) {
							total += parseInt( matches[0], 10 );
						}
					} );
					$cartQuantity.html( total > 0 ? total : '0' );
					$cart[ total > 0 ? 'removeClass' : 'addClass' ]( 'empty' );
				}
			} );
		},

		/**
		 * Show notifications after adding product to cart.
		 *
		 * @event handler
		 * @param {Event} e
		 * @param {{} fragments
		 * @param {node} $button
		 */
		showNotification: function( e, fragments, _, $button ) {
			if ( $ush.isUndefined( e ) ) {
				return;
			}
			const self = this;

			// Update cart element
			self.updateCart();

			const $notice = self.$cartNotification;
			// NOTE: Get product name in product page or popup when available.
			const theProductName = $button
				.closest( '.product, .w-popup-box-content' )
				.find( '.woocommerce-loop-product__title, .w-post-elm.post_title' )
				.first()
				.text();

			$( '.product-name', $notice ).html( `"${theProductName}"` );

			// Skip show message
			if ( $notice.hasClass( 'skip_message' ) ) {
				$notice.removeClass( 'skip_message' );
				return;
			}

			$notice.addClass( 'shown' );
			$notice.on( 'mouseenter', () => $notice.removeClass( 'shown' ) );

			$ush.timeout( () => {
				$notice
					.removeClass( 'shown' )
					.off( 'mouseenter' );
			}, 3000 );
		},

		/**
		 * Handler when remove a item.
		 *
		 * @event handler
		 * @param {Event} e
		 */
		removeCartItem: function( e ) {
			var $item = $( e.target )
				.closest( '.cart_item' )
				.addClass( 'change_process' );
			// If the element is the last, then delete the table for correct operation `cart.js:update_wc_div`
			if ( ! $item.siblings( '.cart_item:not(.change_process)' ).length ) {
				$( '.w-cart-table', $us.$canvas ).remove();
			}
		},

		/**
		 * Check and set quantity.
		 *
		 * @event handler
		 * @param {Event} e
		 */
		changeCartQuantity: function( e ) {

			if ( $us.usbPreview() ) {
				return;
			}

			const self = this;

			const $input = $( e.target );
			const isGroupTable = $input.closest( '.cart' ).hasClass( 'grouped_form' );
			const max = $ush.parseFloat( $input.attr( 'max' ) ) || -1;
			const min = $ush.parseFloat( $input.attr( 'min' ) ) || ( isGroupTable ? 0 : 1 );

			var value = $ush.parseFloat( $input.val() );

			if ( $input.is( ':disabled' ) ) {
				return;
			}
			if ( min >= value ) {
				value = min;
			}
			if ( max > 1 && value >= max ) {
				value = max;
			}
			if ( value != $input.val() ) {
				$input.val( value );
			}

			$input
				.siblings( 'input.plus:first' )
				.prop( 'disabled', ( max > 0 && value >= max ) );
			$input
				.siblings( 'input.minus:first' )
				.prop( 'disabled', ( value <= min ) );

			// If the event type is `initControls` then this is the
			// first init when loading the document
			if ( e.type == 'initControls' ) {
				return;
			}

			// Add a flag that there was a change in the quantity to the cart elements
			$( 'input[name=us_cart_quantity]', $us.$canvas ).val( true );

			if ( self.isCartPage() ) {
				self.restApiUpdateCartForm( $input, value );
			}
		},

		/**
		 * Update cart form (cart table, cart totals) via REST API
		 *
		 * If the REST API is allowed, it will be used
		 * Otherwise the "update cart" button click will be used (updateCartForm)
		 */
		restApiUpdateCartForm: function( $input, value ) {
			const self = this;

			const productKey = $input.closest( '.product-quantity' ).data( 'product-key' );
			const $cartItem = $input.closest( '.woocommerce-cart-form__cart-item.cart_item' );
			const $cartTotals = $( '.w-cart-totals:visible', $us.$canvas ); // Define it here because cart totals may be overridden by other requests

			// Abort previous cart update request
			if ( typeof self.lastRestRequest?.abort === 'function' ) {
				self.lastRestRequest.abort();
			}

			// Block cart totals and show spinner in WooCommerce way
			if ( typeof $.fn.block === 'function' && $cartTotals.length ) {
				$cartTotals.addClass( 'processing' ).block(
					{
						message: null,
						overlayCSS: {
							opacity: 0.6,
						},
					}
				);
			}

			// Request to store API cart update
			self.lastRestRequest = $.ajax( {
				url: `${self.restVars.siteUrl}/wp-json/wc/store/v1/cart/update-item`,
				method: 'POST',
				dataType: 'json',
				data: {
					key: productKey,
					quantity: value,
				},
				headers: {
					'Nonce': self.restVars.nonce,
				},
				success: function( data ) {

					// Find updated item in response
					const updatedItem = data.items?.find( ( item ) => ( item.key === productKey ) );

					// Update item subtotal using formatPrice
					if ( updatedItem && updatedItem.totals ) {
						const subtotalData = {
							price: updatedItem.totals.line_subtotal || '',
							precision: updatedItem.totals.currency_minor_unit || 2,
							currency_code: updatedItem.totals.currency_code || '',
							currency_symbol: updatedItem.totals.currency_symbol || '',
							currency_prefix: updatedItem.totals.currency_prefix || '',
							currency_suffix: updatedItem.totals.currency_suffix || ''
						};
						$cartItem.find( '.product-subtotal bdi' ).html( self.formatPrice( subtotalData ) );
					}

					// Update cart totals section
					if ( data.totals ) {
						const subtotalData = {
							price: data.totals.total_items || '',
							precision: data.totals.currency_minor_unit || 2,
							currency_code: data.totals.currency_code || '',
							currency_symbol: data.totals.currency_symbol || '',
							currency_prefix: data.totals.currency_prefix || '',
							currency_suffix: data.totals.currency_suffix || ''
						};

						if ( $cartTotals.length ) {
							// Update subtotal price
							$( '.cart-subtotal bdi', $cartTotals ).html( self.formatPrice( subtotalData ) );

							// Update total price
							const totalData = { ...subtotalData, price: data.totals.total_price };
							$( '.order-total bdi', $cartTotals ).html( self.formatPrice( totalData ) );

							// Update coupon total discount
							const discountData = { ...subtotalData, price: data.totals.total_discount };
							$( '.cart-discount .woocommerce-Price-amount', $cartTotals ).html( self.formatPrice( discountData ) );

							// Update tax totals
							const $taxRows = $( 'tr.tax-rate', $cartTotals );
							const taxLines = data.totals.tax_lines || [];
							// Several tax rows
							if ( $taxRows.length && taxLines.length === $taxRows.length ) {
								$taxRows.each( ( i, row ) => {
									const taxData = { ...subtotalData, price: taxLines[ i ].price };
									$( '.woocommerce-Price-amount', row ).first().html( self.formatPrice( taxData ) );
								} );
							// Single tax row
							} else if ( $taxRows.length ) {
								const taxData = { ...subtotalData, price: data.totals.total_tax };
								$( '.woocommerce-Price-amount', $taxRows.first() ).html( self.formatPrice( taxData ) );
							}

							$us.$body.trigger( 'updated_cart_totals' );
						}
					}

					// Trigger WooCommerce updated event for compatibility
					$us.$body.trigger( 'updated_wc_div' );
				},
				error: function() {
					// Fallback to traditional form update on error (if REST API is blocked)
					if ( ! $( '.w-cart-table', $us.$canvas ).hasClass( 'processing' ) ) {
						self.updateCartForm_long( self.updateCartForm.bind( self ) );
					} else {
						self.updateCartForm();
					}
				},
				complete: function( _, textStatus) {
					// Unblock cart totals
					if ( typeof $.fn.unblock === 'function' && textStatus === 'success' && $cartTotals.length ) {
						$cartTotals.removeClass( 'processing' ).unblock();
					}
				}
			} );
		},

		/**
		 * Format price with priceData from REST API response
		 * Analogous to "formatted-monetary-amount" func from WooCommerce blocks
		 *
		 * Variables: https://developer.woocommerce.com/docs/apis/store-api/extending-store-api/extend-store-api-formatters/#currencyformatter
		 *
		 * @param priceData - object containing price data to adjust format
		*/
		formatPrice: function( priceData ) {
			if ( ! priceData.price ) {
				return '';
			}

			const numericPrice = $ush.parseFloat( priceData.price ) / Math.pow( 10, priceData.precision );

			// Format only the numeric part with locale aware separators
			const formattedNumber = numericPrice.toLocaleString(
				document.documentElement.lang,
				{
					minimumFractionDigits: priceData.precision,
					maximumFractionDigits: priceData.precision,
					useGrouping: true
				}
			);

			var prefix = priceData.currency_prefix,
				suffix = priceData.currency_suffix;

			// Wrap currency symbol in WooCommerce compatible span
			if ( priceData.currency_symbol ) {
				if ( prefix.indexOf( priceData.currency_symbol ) !== -1 ) {
					prefix = prefix.split( priceData.currency_symbol ).join(
						`<span class="woocommerce-Price-currencySymbol">${priceData.currency_symbol}</span>`
					);
				}
				if ( suffix.indexOf( priceData.currency_symbol ) !== -1 ) {
					suffix = suffix.split( priceData.currency_symbol ).join(
						`<span class="woocommerce-Price-currencySymbol">${priceData.currency_symbol}</span>`
					);
				}
			}

			return prefix + formattedNumber + suffix;
		},

		/**
		 * Decreasing quantity item in cart.
		 *
		 * @event handler
		 * @param {Event} e
		 */
		minusCartQuantity: function( e ) {
			const self = this;

			var $target = $( e.target ),
				$input = $target.siblings( 'input.qty:first' );

			if ( ! $input.length ) {
				return;
			}

			const step = $ush.parseFloat( $input.attr( 'step' ) || 1 );
			$input // Update quantity
				.val( $ush.parseFloat( $input.val() ) - step )
				.trigger( 'change' );
		},

		/**
		 * Increasing quantity item in cart.
		 *
		 * @event handler
		 * @param {Event} e
		 */
		plusCartQuantity: function( e ) {
			const self = this;

			var $target = $( e.target ),
				$input = $target.siblings( 'input.qty:first' );

			if ( ! $input.length ) {
				return;
			}

			const step = $ush.parseFloat( $input.attr( 'step' ) || 1 );
			$input
				.val( $ush.parseFloat( $input.val() ) + step )
				.trigger( 'change' );
		},

		/**
		 * Update the cart form by means of WooCommerce
		 * Note: The code is moved to a separate function since `debounced`
		 * must be initialized before calling
		 *
		 * @param {function} fn The function to be executed
		 * @type debounced
		 */
		updateCartForm_long: $ush.debounce( $ush.fn, /* wait */50 ),

		/**
		 * Update the cart form by means of WooCommerce.
		 */
		updateCartForm: function() {
			const self = this;
			// Abort previous cart update request
			if ( typeof ( lastUpdateCartRequest || {} ).abort === 'function' ) {
				lastUpdateCartRequest.abort();
			}
			// Initialize cart update
			$( '.w-cart-table > button[name=update_cart]', $us.$canvas )
				.removeAttr( 'disabled' )
				.trigger( 'click' );
		},

		/**
		 * Update cart totals.
		 *
		 * @event handler
		 */
		updatedCartTotals: function() {
			const self = this;
			// Reset last active request
			if ( lastUpdateCartRequest ) {
				lastUpdateCartRequest = _undefined;
			}
			// Removing animated class if any element had it
			var wooElementClasses = [
				'w-cart-shipping',
				'w-cart-table',
				'w-cart-totals',
				'w-checkout-billing',
				'w-checkout-order-review',
				'w-checkout-payment',
				'w-wc-coupon-form',
			];
			for ( const i in wooElementClasses ) {
				$( `.${wooElementClasses[i]}.us_animate_this`, $us.$canvas ).removeClass( 'us_animate_this' );
			}

			// Shipping element sync after totals update
			const $shipping = $( '.w-cart-shipping .shipping', $us.$canvas );
			if ( ! $shipping.length ) {
				return;
			}
			$shipping.html( $( '.w-cart-totals .shipping:first', $us.$canvas ).html() );
		},

		/**
		 * Enter the coupon code in the field.
		 *
		 * @event handler
		 * @param {Event} e
		 */
		couponCodeChange: function( e ) {
			// Transit value to the cart form to add a coupon by WooCommerce logic
			$( '.w-cart-table, form.checkout_coupon:first', $us.$canvas )
				.find( 'input[name=coupon_code]' )
				.val( e.target.value );
		},

		/**
		 * Enter a coupon code.
		 *
		 * @event handler
		 * @param {Event} e
		 */
		enterCouponCode: function( e ) {
			if ( e.keyCode === $ush.ENTER_KEYCODE ) {
				$( e.target )
					.trigger( 'change' )
					.siblings( 'button:first' )
					.trigger( 'click' );
			}
		},

		/**
		 * Click on the "Apply Coupon" button.
		 *
		 * @event handler
		 * @param {Event} e
		 */
		applyCouponCode: function( e ) {
			// Stop event (Important on the checkout page)
			e.stopPropagation();
			e.preventDefault();
			// Initialize coupon additions using WooCommerce logic
			$( '.w-cart-table, form.checkout_coupon:first', $us.$canvas )
				.find( 'button[name=apply_coupon]' )
				.trigger( 'click' );
			// Clear input field
			$( e.target ).closest( '.w-wc-coupon-form' ).find( 'input:first' ).val( '' );
		},

		/**
		 * Coupon form display switch.
		 *
		 * @param {Event} e
		 */
		couponDisplaySwitch: function( e ) {
			const $coupon = $( '.w-wc-coupon-form', $us.$canvas );
			if ( ! $coupon.length ) {
				return;
			}
			// Add a class if the coupon is applied
			if ( e.type.indexOf( 'applied_coupon' ) > -1 && ! $( '.woocommerce-error', $us.$canvas ).length ) {
				$coupon.addClass( 'coupon_applied' );
			}
			// Remove a class if all coupons were removed
			if ( e.type.indexOf( 'removed_coupon' ) > -1 && $( '.woocommerce-remove-coupon', $us.$canvas ).length <= 1 ) {
				$coupon.removeClass( 'coupon_applied' );
			}
		},

		/**
		 * Move notifications to `[wc_notices...]`.
		 *
		 * @event handler
		 * @param {Event} e
		 * @param {String} err_html The value is needed for checkout_error.
		 */
		moveNotifications: function( e, err_html ) {
			const self = this;

			// Do not proceed with notices adjustment if there are no US Cart / Checkout elements on the page
			if ( ! self.$notice.length ) {
				var $cartTotals = $( '.w-cart-totals', $us.$canvas ),
					$checkoutPayment = $( '.w-checkout-payment', $us.$canvas );
				if ( ! $cartTotals.length && ! $checkoutPayment.length ) {
					return;
				}
			}

			// Get notice
			var $message;
			if ( e.type === 'checkout_error' && err_html ) {
				$message = $( err_html );
			} else {
				$message = $( '.woocommerce-error, .woocommerce-message', $us.$canvas );
			}

			// Show notification
			if ( $message.length > 0 ) {
				self.$notice.html( $message.clone() );
			}
			$message.remove();

			// Remove NoticeGroup
			if ( e.type === 'checkout_error' ) {
				$( '.woocommerce-NoticeGroup-checkout' ).remove();
			}
		},
	} );

	// Cart element
	$.extend( WooCommerce.prototype, {

		/**
		 * "Add to cart" via Ajax.
		 *
		 * @event handler
		 * @param {Event} e
		 */
		ajaxAddToCart: function( e ) {
			const self = this;

			const $button = $( e.currentTarget );
			const $form = $button.closest( 'form.cart' );

			/**
			 * Exclude specific product types from ajax as they needed additional processing
			 * Excluded product types:
			 * - External/Affiliate
			 * - Grouped
			 * - Composite
			 */
			if (
				$form.is( '[method=get]' )
				|| self.$pageContent.hasClass( 'product-type-grouped' )
				|| self.$pageContent.hasClass( 'product-type-composite' )
				|| $form.hasClass( 'grouped_form' )
				|| $form.hasClass( 'composite_form' )
			) {
				return;
			}

			e.preventDefault();

			// Add text wrapper and preloader
			if ( $( '.g-preloader', $button ).length === 0 ) {
				$button.html( `<div class="g-preloader type_1"><div></div></div><span class="w-btn-label">${$button.html()}</span>` );
			}

			var data = {};

			// Serialize Form
			const formData = new FormData( $form[0], $button[0] );

			formData.forEach( ( value, key ) => {
				if ( key.includes( '[' ) ) {
					const keys = key.split( /\[|\]/ ).filter( ( k ) => { return k; } );
					keys.forEach( ( k, index ) => {
						if ( index === keys.length -1 ) {
							if ( Array.isArray( data[ k ] ) ) {
								data[ k ].push( value );
							} else if ( data[ k ] ) {
								data[ k ] = [ data[ k ], value ];
							} else {
								data[ k ] = key.includes( '[]' ) ? [ value ] : value; // кey includes [] without a k, ie: field[].
							}
						} else {
							if ( ! data[ k ] ) {
								data[ k ] = {};
							}
							data = data[ k ];
						}
					} );
				} else {
					if ( data[ key ] ) {
						if ( Array.isArray( data[ key ] ) )  {
							data[ key ].push( value );
						} else {
							data[ key ] = [ data[ key ], value ];
						}
					} else {
						data[ key ] = value;
					}
				}
			} );

			// Check if product_id exists, if not add it with the value of add-to-cart. Use variation_id for variable products.
			if ( data['variation_id'] ) {
				data['product_id'] = data['variation_id'];
			} else if ( ! data['product_id'] && data['add-to-cart'] ) {
				data['product_id'] = data['add-to-cart'];
			}

			delete data['add-to-cart']; // need to remove this so that the form handler doesn't try to add the product to the cart again.

			// Events of `../plugins/woocommerce/assets/js/frontend/add-to-cart.js`
			$us.$body.trigger( 'adding_to_cart', $button, data );

			$.ajax( {
				type: 'POST',
				url: String( woocommerce_params.wc_ajax_url ).replace( '%%endpoint%%', 'add_to_cart' ),
				data: data,
				beforeSend: () => {
					$button
						.removeClass( 'added' )
						.addClass( 'loading' );
				},
				complete: ( jqXHR ) => {
					// Redirect to the cart page after successful addition
					if ( String( jqXHR.responseText ).includes( 'us_redirect_to_cart' ) ) {
						return;
					}
					$button
						.addClass( 'added' )
						.removeClass( 'loading' );
				},
				success: ( res ) => {
					if ( res.error && res.product_url ) {
						window.location = res.product_url;
						return;
					}

					// Redirect to the cart page after successful addition
					if ( res.fragments && res.fragments[ 'us_redirect_to_cart' ] ) {
						window.location.replace( res.fragments[ 'us_redirect_to_cart' ] );
						return;
					}

					// Skip default message
					if ( self.isCart() ) {
						self.$cartNotification.addClass( 'skip_message' );
					}

					$us.$body.trigger( 'added_to_cart', [ res.fragments, res.cart_hash, $button ] );

					// Show Cart content after clicking "Add to Cart" button
					if ( self.isCart() && self.$cart.hasClass( 'open_on_ajax' ) ) {
						self._toggleCart( /* open */true );
					}

					var message = '';

					if ( self.isCart() ) {
						message = self.$cartNotification.text();
					}

					// Get "View cart"
					var $viewCart = $button.next( '.added_to_cart.wc-forward' ).removeClass( 'added_to_cart' );
					if ( $viewCart.length ) {
						message += ' ' + $viewCart.prop( 'outerHTML' );
						$viewCart.remove();
					}

					// Show notice
					$form.next( '.woocommerce-notices-wrapper' ).remove();
					$form.after( `
						<div class="woocommerce-notices-wrapper">
							<div class="woocommerce-message" role="alert" tabindex="-1">${message}</div>
						</div>
					` );
				},
			} );
		},

		/**
		 * Handler for outside click events for both mobile and desktop devices.
		 *
		 * @event handler
		 * @param {Event} e
		 */
		clickOutsideCart: function( e ) {
			const self = this;
			if ( $.contains( self.$cart[0], e.target ) ) {
				return;
			}
			self._toggleCart( /* open */false );
		},

		/**
		 * Redirect to cart page on mobile devices.
		 *
		 * @event handler
		 * @param {Event} e
		 */
		redirectToCartOnMobile: function ( e ) {

			e.preventDefault();

			const cartUrl = String( ( window.wc_add_to_cart_params || {} ).cart_url );
			if ( cartUrl.includes( window.location.host ) ) {
				window.location.replace( cartUrl );
			}
		},

		/**
		 * Show the cartesian on click.
		 *
		 * @event handler
		 *
		 * @param {Event} e
		 */
		showCartOnClick: function( e ) {
			const self = this;

			e.preventDefault();

			// Enter press generates 'click' event firing two events in a row so need to distinguish these events
			// "e.detail" is used as fallback for old browsers (e.g. Safari <18)
			if ( [ 'mouse', 'touch', 'pen' ].includes( e.pointerType ) || e.detail > 0 ) {
				self._toggleCart( ! self.isCartOpen );
			}
		},

		/**
		 * Show the cartesian on keyup.
		 *
		 * @event handler
		 *
		 * @param {Event} e
		 */
		showCartOnKeyup: function( e ) {
			const self = this;

			e.preventDefault();

			if ( e.keyCode !== $ush.ENTER_KEYCODE ) {
				return;
			}

			if ( ! self.isCartOpen ) {
				if ( self.$cartCloser.length > 0 ) {
					// waiting for transition - cannot focus element with visibility:hidden
					self.$cart.on( 'transitionend', () => {
						self.$cartCloser[0].focus();
						self.$cart.off( 'transitionend' );
					} );
				}
			}

			self._toggleCart( ! self.isCartOpen );
		},

		/**
		 * Hide the cart on keyup.
		 *
		 * @event handler
		 *
		 * @param {Event} e
		 */
		hideCartOnKeyup: function( e ) {
			const self = this;
			if ( e.keyCode === $ush.ESC_KEYCODE ) {
				self._toggleCart( false, self.$cartLink );
			}
		},

		/**
		 * @param {Boolean} open
		 * @param {*} $elementToFocus
		 */
		_toggleCart: function( open = true, $elementToFocus = null ) {
			const self = this;

			self.$cart.toggleClass( 'opened', open );
			self.$cartLink.attr( 'aria-expanded', open );

			if ( self.isCartOpen && $elementToFocus !== null ) {
				$elementToFocus.focus();
			}

			self.isCartOpen = open;

			if ( self.$cart.hasClass( 'layout_dropdown' ) && self.$cart.hasClass( 'drop_on_click' ) ) {
				if ( open ) {
					$us.$body.on( 'mouseup touchend.noPreventDefault', self._events.clickOutsideCart );
				} else {
					$us.$body.off( 'mouseup touchend.noPreventDefault', self._events.clickOutsideCart );
				}
			}
		}
	} );

	// Login Form
	$.extend( WooCommerce.prototype, {

		/**
		 * Show the login form.
		 *
		 * @event handler
		 *
		 * @return {Boolean}
		 */
		showLoginForm: function() {
			$( '.woocommerce-form-login' ).toggleClass( 'hidden' );
			return false;
		},

		/**
		 * Submit the login form.
		 *
		 * @event handler
		 *
		 * @return {Boolean}
		 */
		submitLoginForm: function() {
			const self = this;
			// Prevent double sending
			if ( self.isSubmittingLoginForm ) {
				return false;
			}
			self.isSubmittingLoginForm = true;

			// Get the form substitute view and all its fields
			var $formView = $( '.w-checkout-login' ),
				$usernameField = $( '#us_checkout_login_username', $formView ),
				$passwordField = $( '#us_checkout_login_password', $formView ),
				$redirectField = $( '#us_checkout_login_redirect', $formView ),
				$nonceField = $( '#us_checkout_login_nonce', $formView );

			// Make sure all fields are present
			if (
				$usernameField.length == 0
				|| $passwordField.length == 0
				|| $redirectField.length == 0
				|| $nonceField.length == 0
			) {
				return false;
			}

			// Append a new form with needed fields to <body> and submit it
			var fields = {
					'login': 'Login',
					'rememberme': 'forever',
					'username': $usernameField.val(),
					'password': $passwordField.val(),
					'redirect': $redirectField.val(),
					'woocommerce-login-nonce': $nonceField.val(),
				},
				$form = $( '<form>', {
					method: 'post'
				} );
			$.each( fields, ( key, val ) => {
				$( '<input>' ).attr( {
					type: "hidden",
					name: key,
					value: val
				} ).appendTo( $form );
			} );

			$form.appendTo( 'body' ).submit();

			return false;
		},

		/**
		 * @event handler
		 * @param {Event} e
		 */
		loginFieldKeydown: function( e ) {
			if ( e.keyCode === $ush.ENTER_KEYCODE ) {
				e.stopPropagation();
				e.preventDefault();
				this.submitLoginForm();
			}
		},
	} );

	$us.woocommerce = new WooCommerce;

	/**
	 * Image Slider element
	 * Set product images for the chosen variation.
	 * Note: Overriding a default function implemented in WooCommerce logic.
	 * https://github.com/woocommerce/woocommerce/blob/d4696f043710131d5bbf51455e070791eaa12cf9/plugins/woocommerce/client/legacy/js/frontend/add-to-cart-variation.js#L646
	 *
	 * @param {{}} variation The variation.
	 */
	function us_wc_variations_image_update( variation ) {
		var $slider = $( '.w-slider.for_product_image_gallery:not(.w-grid .w-slider)', $( this ).closest( '.product' ) ),
			royalSlider = ( $slider.data( 'usImageSlider' ) || {} ).royalSlider;
		if ( $ush.isUndefined( royalSlider ) ) {
			return;
		}
		royalSlider.goTo(0);
		var $image = $( '.rsImg', royalSlider.slidesJQ[0] ),
			$thumb = $( '.rsThumb:first img', $slider );
		if ( variation === false ) {
			if ( ! $slider.data( 'orig-img' ) ) {
				var src = $image.attr( 'src' );
				$slider.data( 'orig-img', {
					src: src,
					srcset: src,
					full_src: src,
					thumb_src: $thumb.attr( 'srcset' ),
					gallery_thumbnail_src: $thumb.attr( 'src' ),
				} );
				return;
			}
			variation = {
				image: $slider.data( 'orig-img' ),
			};
		}
		if ( $.isPlainObject( variation.image ) ) {
			$image
				.attr( 'src', $ush.toString( variation.image.src ) )
				.attr( 'srcset', $ush.toString( variation.image.srcset ) );
			$thumb
				.attr( 'src', $ush.toString( variation.image.gallery_thumbnail_src ) )
				.attr( 'srcset', $ush.toString( variation.image.thumb_src ) );
			// Set bigImage for Fullscreen
			$.extend( royalSlider.currSlide, {
				bigImage: $ush.toString( variation.image.full_src ),
				image: $ush.toString( variation.image.src ),
			} );
			if ( typeof royalSlider.updateSliderSize === 'function' ) {
				royalSlider.updateSliderSize( true );
			}
		}
	}
	$( () => {
		if ( $( '.w-slider.for_product_image_gallery:not(.w-grid .w-slider.for_product_image_gallery)' ).length > 0 ) {
			$ush.timeout( () => {
				$.fn.wc_variations_image_update = us_wc_variations_image_update;
			}, 1 );
		}
	} );

	/**
	 * Extension for WC Product Gallery
	 * Thumbs Arrows functionality
	 */
	function usProductGallerySlider( $wcSlider ) {
		const self = this;

		// Elements
		self.$wcSlider = $( $wcSlider );
		self.$thumbsNav = $( '.flex-control-thumbs', self.$wcSlider );
		self.$thumbsItems = $( 'li', self.$thumbsNav );
		self.$thumbsArrowsContainer = self.$wcSlider
			.closest( '.w-post-elm.product_gallery' )
			.find( '.us-thumbs-nav' );
		self.$thumbArrowNext = $( '.us-thumb-next', self.$thumbsArrowsContainer );
		self.$thumbArrowPrev = $( '.us-thumb-prev', self.$thumbsArrowsContainer );

		// Vars
		self._isVertical = self.isVertical();
		self.scrollProp = self._isVertical ? 'scrollTop' : 'scrollLeft';
		const sizeProp = self._isVertical ? 'outerHeight' : 'outerWidth';
		self.clientSizeProp = self._isVertical ? 'clientHeight' : 'clientWidth';
		self.scrollSizeProp = self._isVertical ? 'scrollHeight' : 'scrollWidth';
		self.thumbItemSize = self.$thumbsItems.first()[ sizeProp ]( true );

		// Bindable events
		self._events = {
			updateThumbsArrowsState: self.updateThumbsArrowsState.bind( self ),
			controlThumbsArrows: self.controlThumbsArrows.bind( self ),
			syncThumbsWithMainSlider: self.syncThumbsWithMainSlider.bind( self ),
		};

		// Main Image Arrows
		if ( self.$wcSlider.hasClass( 'with_arrows' ) ) {
			// Change arrows position
			$( '.flex-direction-nav', self.$wcSlider ).appendTo( $( '.flex-viewport', self.$wcSlider ) );

			// Events
			$( '.flex-direction-nav a', self.$wcSlider ).on( 'click', self._events.syncThumbsWithMainSlider );
		}

		// Wrap Product Gallery thumbs and move arrows to it
		self.$thumbsNav.wrap( '<div class="us-thumbs-wrapper"></div>' );
		self.$thumbsArrowsContainer.appendTo( self.$thumbsNav.parent() );

		// Init thumb arrows state
		self.updateThumbsArrowsState();

		// Events
		$( 'button', self.$thumbsArrowsContainer ).on( 'click', self._events.controlThumbsArrows );

		self.$thumbsNav.on( 'scroll.noPreventDefault', self._events.updateThumbsArrowsState );

		// Sync thumbs scroll position with main slider on variation change
		$( 'form.variations_form' ).on( 'found_variation reset_data', () => {
			$ush.timeout( () => self.syncThumbsWithMainSlider(), 100 );
		} );
	}

	$.extend( usProductGallerySlider.prototype, {
		/**
		 * Scroll thumbs on thumb arrows click
		 *
		 * @param {Event} e
		 */
		controlThumbsArrows: function ( e ) {
			e.preventDefault();
			const self = this;
			const scrollObj = {};
			const isNext = $( e.currentTarget ).hasClass( 'us-thumb-next' );

			var direction;

			if ( $ush.isRtl() && ! self._isVertical ) {
				direction = isNext ? '-=' : '+=';
			} else {
				direction = isNext ? '+=' : '-=';
			}
			scrollObj[ self.scrollProp ] = direction + self.thumbItemSize;
			self.$thumbsNav.animate( scrollObj, 50, self._events.updateThumbsArrowsState );
		},
		/**
		 * Update thumbs arrows state depending on scroll position
		 */
		updateThumbsArrowsState: function () {
			const self = this;

			if ( ! self.$thumbsNav[0] ) {
				return;
			}

			const scrollPos = self.$thumbsNav[0][ self.scrollProp ];
			const maxScroll = self.$thumbsNav[0][ self.scrollSizeProp ] - self.$thumbsNav[0][ self.clientSizeProp ];

			var isPrevDisabled, isNextDisabled;

			if ( $ush.isRtl() && ! self._isVertical ) {
				isPrevDisabled = scrollPos >= -1;
				isNextDisabled = scrollPos <= -maxScroll + 1;
			} else {
				isPrevDisabled = scrollPos <= 1;
				isNextDisabled = scrollPos >= maxScroll - 1;
			}

			self.$thumbArrowPrev.toggleClass( 'hidden', isPrevDisabled );
			self.$thumbArrowNext.toggleClass( 'hidden', isNextDisabled );
		},
		/**
		 * Sync thumbs scroll position with main slider
		 */
		syncThumbsWithMainSlider: function() {
			const self = this;
			const $activeSlide = $( '.woocommerce-product-gallery__wrapper .flex-active-slide', self.$wcSlider );
			const scrollObj = {};

			if ( ! $activeSlide.length || ! self.$thumbsItems.length ) {
				return;
			}

			const index = $activeSlide.index();

			if ( index < 0 || index >= self.$thumbsItems.length ) {
				return;
			}

			var targetScroll = index * self.thumbItemSize;
			if ( $ush.isRtl() && ! self._isVertical ) {
				targetScroll = -targetScroll;
			}

			scrollObj[ self.scrollProp ] = targetScroll;

			self.$thumbsNav.stop().animate( scrollObj, 50, self._events.updateThumbsArrowsState );
		},
		/**
		 * Get isVertical flag
		 */
		isVertical: function() {
			const self = this;

			var isVertical = self.$wcSlider.hasClass( 'thumbpos_left' );

			// Responsive for thumbpos_left
			if ( window.matchMedia( '(max-width: 767px)' ).matches ) {
				self.$wcSlider.removeClass( 'thumbpos_left' ).addClass( 'thumbpos_bottom' );
				isVertical = false;
			}

			return isVertical;
		}
	} );

	$.fn.us_product_gallery = function() {
		return this.each( function() {
			$( this ).data( 'us_product_gallery', new usProductGallerySlider( this ) );
		} );
	};

	$( () => {
		const $wcSlider = $( '.woocommerce-product-gallery.type_slider' );
		if ( ! $wcSlider.length ) {
			return;
		}

		$ush.timeout( () => $wcSlider.us_product_gallery(), 200 );

		/**
		 * Third-party plugins can replace the product gallery on variation change, so we need to re-init it. See #5582
		 * 	Supported plugins:
		 * 	- wpcvi_show_selected_gallery - "WPC Additional Variation Images for WooCommerce"
		 * 	- wc_additional_variation_images_frontend_lightbox_done - "WooCommerce Additional Variation Images"
		 */
		$us.$body.on(
			'wpcvi_show_selected_gallery wc_additional_variation_images_frontend_lightbox_done',
			'form.variations_form',
			() => {
				$ush.timeout( () => {
					const $wcSliderVisible = $( '.woocommerce-product-gallery:visible' );
					if ( ! $wcSliderVisible.length || $wcSliderVisible.data( 'us_product_gallery' ) ) {
						return;
					}
					$wcSliderVisible
						.addClass( $wcSlider.first().attr( 'class' ) )
						.us_product_gallery();
				}, 0 );
			}
		);
	} );

	// Init the "Product Gallery" inside the popup content in loop elements
	$us.$document.on( 'usPopup.itemContentLoaded', ( e, usPopup ) => {
		// Functions for single product.
		// Dependency on `../plugins/woocommerce/assets/js/frontend/single-product.js`
		if ( typeof $.fn.wc_product_gallery === 'function' ) {
			const $wcProductGallery = $( '.woocommerce-product-gallery', usPopup.$content );
			$wcProductGallery.wc_product_gallery();
			if ( $wcProductGallery.hasClass( 'type_slider' ) ) {
				$ush.timeout( () => $wcProductGallery.us_product_gallery(), 200 );
			}
		}
		// Functions for which handles variation forms and attributes
		// Dependency on `../plugins/woocommerce/assets/js/frontend/add-to-cart-variations.js`
		if ( typeof $.fn.wc_variation_form === 'function' ) {
			const $variationsForm = $( '.variations_form', usPopup.$content );
			if ( $variationsForm.length > 0 ) {
				if ( $( '.product', usPopup.$content ).length == 0 ) {
					usPopup.$content.addClass( 'product' );
				}
				$variationsForm.wc_variation_form();
			}
		}
	} );

} )( jQuery );
