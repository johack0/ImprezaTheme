/**
 * US Helper Library
 * @requires jQuery
 */
! function( $, _undefined ) {
	"use strict";

	const _window = window;
	const _document = document;
	const _navigator = _window.navigator;
	const _location = _window.location;

	// Math API
	const max = Math.max;
	const min = Math.min;
	const pow = Math.pow;

	// If the object exists, then exit
	// Note: this is important for iframe pages, e.g. Live Builder
	if ( $.isPlainObject( _window.$ush ) ) {
		return;
	}

	// Export API
	_window.$ush = {};

	// Key codes used
	$ush.TAB_KEYCODE = 9;
	$ush.ENTER_KEYCODE = 13;
	$ush.ESC_KEYCODE = 27;
	$ush.SPACE_KEYCODE = 32;

	// Get the current userAgent
	const ua = _navigator.userAgent.toLowerCase();

	// Characters to encode and decode a string base64
	const base64Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';

	// The method returns a string created from the specified sequence of UTF-16 code units
	const fromCharCode = String.fromCharCode;

	/**
	 * Return the current user agent.
	 *
	 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/User-Agent
	 * @return {String} Current user agent
	 */
	$ush.ua = ua;

	/**
	 * Detect macOS.
	 *
	 * @return {Boolean} true if macOS, otherwise false
	 */
	$ush.isMacOS = /(Mac|iPhone|iPod|iPad)/i.test( _navigator.platform );

	/**
	 * Detect Firefox.
	 *
	 * @return {Boolean} true if Firefox, otherwise false
	 */
	$ush.isFirefox = ua.includes( 'firefox' );

	/**
	 * Detect Safari.
	 *
	 * @return {Boolean} true if Safari, otherwise false
	 */
	$ush.isSafari = /^((?!chrome|android).)*safari/i.test( ua );

	/**
	 * Check if touch events are supported.
	 *
	 * @return {Boolean} true if supported, otherwise false
	 */
	$ush.isTouchend = ( 'ontouchend' in _document );

	/**
	 * Return Safari version.
	 *
	 * @return {Number} Safari version, or 0 if not Safari
	 */
	$ush.safariVersion = function() {
		const self = this;
		if ( self.isSafari ) {
			return self.parseInt( ( ua.match( /version\/([\d]+)/i ) || [] )[1] );
		}
		return 0;
	}

	/**
	 * Wrap a function for use with debounce or throttle.
	 *
	 * @param {Function} fn Function to execute
	 */
	$ush.fn = function( fn ) {
		if ( typeof fn === 'function' ) {
			fn();
		}
	};

	/**
	 * Check whether the value is undefined.
	 *
	 * @param {*} value Value to check
	 * @return {Boolean} true if value is undefined, otherwise false
	 */
	$ush.isUndefined = function( value ) {
		return ( typeof value === 'undefined' || value === 'undefined' );
	};

	/**
	 * Check whether the value is numeric.
	 *
	 * @param {*} value Value to check
	 * @return {Boolean} true if numeric, otherwise false
	 */
	$ush.isNumeric = function( value ) {
		const type = typeof value;
		return ( type === "number" || type === "string" ) && ! isNaN( value - parseFloat( value ) );
	};

	/**
	 * Check if layout is RTL (right-to-left).
	 *
	 * @return {Boolean} true if RTL, otherwise false
	 */
	$ush.isRtl = function() {
		return this.toString( _document.body.className ).split( /\p{Zs}/u ).includes( 'rtl' );
	};

	/**
	 * Check whether the specified element is a node.
	 *
	 * @param {Node} node Node from the document
	 * @return {Boolean} true if node, otherwise false
	 */
	$ush.isNode = function( node ) {
		return !! ( node && node.nodeType );
	};

	/**
	 * Check whether the element is in the viewport.
	 *
	 * @param {Node} node DOM node
	 * @return {Boolean} true if in viewport, otherwise false
	 */
	$ush.isNodeInViewport = function( node ) {
		const self = this;
		const rect = $ush.$rect( node );
		const nearestTop = rect.top - _window.innerHeight;

		return nearestTop <= 0 && ( rect.top + rect.height ) >= 0;
	};

	/**
	 * Generate a unique ID with the specified length.
	 * Note: This does not guarantee uniqueness.
	 *
	 * @param {String} prefix Prefix added to the beginning of the ID
	 * @return {String} Generated unique ID
	 */
	$ush.uniqid = function( prefix ) {
		return ( prefix || '' ) + Math.random().toString( 36 ).substr( 2, 9 );
	};

	/**
	 * Convert a UTF-8 string to ISO-8859-1, replacing unsupported characters.
	 *
	 * @param {String} str UTF-8 encoded string
	 * @return {String} ISO-8859-1 encoded string
	 */
	$ush.utf8Decode = function( data ) {
		var tmp_arr = [], i = 0, ac = 0, c1 = 0, c2 = 0, c3 = 0;
		data += '';
		while ( i < data.length ) {
			c1 = data.charCodeAt( i );
			if ( c1 < 128 ) {
				tmp_arr[ ac ++ ] = fromCharCode( c1 );
				i ++;
			} else if ( c1 > 191 && c1 < 224 ) {
				c2 = data.charCodeAt( i + 1 );
				tmp_arr[ ac ++ ] = fromCharCode( ( ( c1 & 31 ) << 6 ) | ( c2 & 63 ) );
				i += 2;
			} else {
				c2 = data.charCodeAt( i + 1 );
				c3 = data.charCodeAt( i + 2 );
				tmp_arr[ ac ++ ] = fromCharCode( ( ( c1 & 15 ) << 12 ) | ( ( c2 & 63 ) << 6 ) | ( c3 & 63 ) );
				i += 3;
			}
		}
		return tmp_arr.join( '' );
	};

	/**
	 * Convert a string from ISO-8859-1 to UTF-8.
	 *
	 * @param {String} str ISO-8859-1 encoded string
	 * @return {String} UTF-8 encoded string
	 */
	$ush.utf8Encode = function( data ) {
		if ( data === null || this.isUndefined( data ) ) {
			return '';
		}
		var string = ( '' + data ), utftext = '', start, end, stringl = 0;
		start = end = 0;
		stringl = string.length;
		for ( var n = 0; n < stringl; n ++ ) {
			var c1 = string.charCodeAt( n );
			var enc = null;
			if ( c1 < 128 ) {
				end ++;
			} else if ( c1 > 127 && c1 < 2048 ) {
				enc = fromCharCode( ( c1 >> 6 ) | 192 ) + fromCharCode( ( c1 & 63 ) | 128 );
			} else {
				enc = fromCharCode( ( c1 >> 12 ) | 224 ) + fromCharCode( ( ( c1 >> 6 ) & 63 ) | 128 ) + fromCharCode( ( c1 & 63 ) | 128 );
			}
			if ( enc !== null ) {
				if ( end > start ) {
					utftext += string.slice( start, end );
				}
				utftext += enc;
				start = end = n + 1;
			}
		}
		if ( end > start ) {
			utftext += string.slice( start, stringl );
		}
		return utftext;
	};

	/**
	 * Decode MIME base64 encoded data.
	 *
	 * @param {String} data Encoded data
	 * @return {String} Decoded data, or empty string on failure
	 */
	$ush.base64Decode = function( data ) {
		var o1, o2, o3, h1, h2, h3, h4, bits, i = 0, ac = 0, dec = '', tmp_arr = [], self = this;
		if ( ! data ) {
			return data;
		}
		data += '';
		do {
			h1 = base64Chars.indexOf( data.charAt( i ++ ) );
			h2 = base64Chars.indexOf( data.charAt( i ++ ) );
			h3 = base64Chars.indexOf( data.charAt( i ++ ) );
			h4 = base64Chars.indexOf( data.charAt( i ++ ) );
			bits = h1 << 18 | h2 << 12 | h3 << 6 | h4;
			o1 = bits >> 16 & 0xff;
			o2 = bits >> 8 & 0xff;
			o3 = bits & 0xff;
			if ( h3 == 64 ) {
				tmp_arr[ ac ++ ] = fromCharCode( o1 );
			} else if ( h4 == 64 ) {
				tmp_arr[ ac ++ ] = fromCharCode( o1, o2 );
			} else {
				tmp_arr[ ac ++ ] = fromCharCode( o1, o2, o3 );
			}
		} while ( i < data.length );
		return self.utf8Decode( tmp_arr.join( '' ) );
	};

	/**
	 * Encode data using MIME base64.
	 *
	 * @param {String} data Data to encode
	 * @return {String} Base64 encoded string
	 */
	$ush.base64Encode = function( data ) {
		var o1, o2, o3, h1, h2, h3, h4, bits, i = 0, ac = 0, enc = '', tmp_arr = [], self = this;
		if ( ! data ) {
			return data;
		}
		data = self.utf8Encode( '' + data );
		do {
			o1 = data.charCodeAt( i ++ );
			o2 = data.charCodeAt( i ++ );
			o3 = data.charCodeAt( i ++ );
			bits = o1 << 16 | o2 << 8 | o3;
			h1 = bits >> 18 & 0x3f;
			h2 = bits >> 12 & 0x3f;
			h3 = bits >> 6 & 0x3f;
			h4 = bits & 0x3f;
			tmp_arr[ ac ++ ] = base64Chars.charAt( h1 ) + base64Chars.charAt( h2 ) + base64Chars.charAt( h3 ) + base64Chars.charAt( h4 );
		} while ( i < data.length );
		enc = tmp_arr.join( '' );
		const r = data.length % 3;
		return ( r ? enc.slice( 0, r - 3 ) : enc ) + '==='.slice( r || 3 );
	};

	/**
	 * Remove HTML and PHP tags from a string.
	 *
	 * @param {String} input Input string
	 * @return {String} String without tags
	 */
	$ush.stripTags = function( input ) {
		return $ush.toString( input )
			.replace( /(<([^>]+)>)/ig, '' )
			.replace( '"', '&quot;' );
	};

	/**
	 * Decode a URL-encoded string.
	 *
	 * @param {String} str URL-encoded string
	 * @return {String} Decoded URL string
	 */
	$ush.rawurldecode = function( str ) {
		return decodeURIComponent( this.toString( str ) )
	};

	/**
	 * Encode a URL according to RFC 3986.
	 *
	 * @param {String} str URL to encode
	 * @return {String} RFC 3986 encoded string
	 */
	$ush.rawurlencode = function( str ) {
		return encodeURIComponent( this.toString( str ) )
			.replace( /!/g, '%21' )
			.replace( /'/g, '%27' )
			.replace( /\(/g, '%28' )
			.replace( /\)/g, '%29' )
			.replace( /\*/g, '%2A' );
	};

	/**
	 * setTimeout alternative using requestAnimationFrame when possible.
	 *
	 * @param {Function} fn Callback function
	 * @param {Number} delay Delay in milliseconds
	 */
	$ush.timeout = function( fn, delay ) {
		var handle = {},
			start = new Date().getTime(),
			requestAnimationFrame = _window.requestAnimationFrame;
		function loop() {
			var current = new Date().getTime(),
				delta = current - start;
			delta >= $ush.parseFloat( delay )
				? fn.call()
				: handle.value = requestAnimationFrame( loop );
		}
		handle.value = requestAnimationFrame( loop );
		return handle;
	};

	/**
	 * clearTimeout alternative using requestAnimationFrame when possible.
	 *
	 * @param {Number|{}} fn Timer identifier or callback reference
	 */
	$ush.clearTimeout = function( handle ) {
		if ( $.isPlainObject( handle ) ) {
			handle = handle.value;
		}
		if ( typeof handle === 'number' ) {
			_window.cancelAnimationFrame( handle );
		}
	};

	/**
	 * Returns a new function that, when invoked, invokes `fn` at most once per `wait` milliseconds.
	 *
	 * @param {Function} fn Function to wrap
	 * @param {Number} wait Timeout in ms (`100`)
	 * @param {Boolean} no_trailing Optional, defaults to false.
	 *		If no_trailing is true, `fn` will only execute every `wait` milliseconds while the
	 *		throttled-function is being called. If no_trailing is false or unspecified,
	 *		`fn` will be executed one final time after the last throttled-function call.
	 *		(After the throttled-function has not been called for `wait` milliseconds, the internal counter is reset)
	 *
	 * In this visualization, | is a throttled-function call and X is the actual
	 * callback execution:
	 *
	 * > Throttled with `no_trailing` specified as False or unspecified:
	 *	||||||||||||||||||||||||| (pause) |||||||||||||||||||||||||
	 *	X    X    X    X    X    X        X    X    X    X    X    X
	 *
	 * > Throttled with `no_trailing` specified as True:
	 *	||||||||||||||||||||||||| (pause) |||||||||||||||||||||||||
	 *	X    X    X    X    X             X    X    X    X    X
	 *
	 * @return (Function) A new, throttled, function.
	 */
	$ush.throttle = function( fn, wait, no_trailing, debounce_mode ) {
		const self = this;
		if ( typeof fn !== 'function' ) {
			return $.noop;
		}
		if ( typeof wait !== 'number' ) {
			wait = 0; // default
		}
		if ( typeof no_trailing !== 'boolean' ) {
			no_trailing = _undefined;
		}

		var last_exec = 0, timeout, context, args;
		return function () {
			context = this;
			args = arguments;
			var elapsed = +new Date() - last_exec;
			function exec() {
				last_exec = +new Date();
				fn.apply( context, args );
			}
			function clear() {
				timeout = _undefined;
			}
			if ( debounce_mode && ! timeout ) {
				exec();
			}
			timeout && self.clearTimeout( timeout );
			if ( self.isUndefined( debounce_mode ) && elapsed > wait ) {
				exec();
			} else if ( no_trailing !== true ) {
				timeout = self.timeout(
					debounce_mode
						? clear
						: exec,
					self.isUndefined( debounce_mode )
						? wait - elapsed
						: wait
				);
			}
		};
	};

	/**
	 * Returns a function, that, as long as it continues to be invoked, will not
	 * be triggered. The functionwill be called after it stops being called for
	 * N milliseconds. If `immediate` is passed, trigger the functionon the
	 * leading edge, instead of the trailing. The functionalso has a property 'clear'
	 * that is a functionwhich will clear the timer to prevent previously scheduled executions.
	 *
	 * @param {Function} fn Function to wrap
	 * @param {Number} wait Timeout in ms (`100`)
	 * @param {Boolean} at_begin Optional, defaults to false.
	 *		If at_begin is false or unspecified, `fn` will only be executed `wait` milliseconds after
	 *		the last debounced-function call. If at_begin is true, `fn` will be executed only at the
	 *		first debounced-function call. (After the throttled-function has not been called for `wait`
	 *		milliseconds, the internal counter is reset)
	 *
	 * In this visualization, | is a throttled-function call and X is the actual
	 * callback execution:
	 *
	 * > Debounced with `at_begin` specified as False or unspecified:
	 *	||||||||||||||||||||||||| (pause) |||||||||||||||||||||||||
	 *	                         X                                 X
	 *
	 * > Debounced with `at_begin` specified as True:
	 *	||||||||||||||||||||||||| (pause) |||||||||||||||||||||||||
	 *	X                                 X
	 *
	 * @return {Function} A new, debounced, function
	 */
	$ush.debounce = function( fn, wait, at_begin ) {
		const self = this;
		return self.isUndefined( at_begin )
			? self.throttle( fn, wait, _undefined, false )
			: self.throttle( fn, wait, at_begin !== false );
	};

	/**
	 * Execute a function after a 1ms delay.
	 *
	 * @debounced
	 * @param {Function} fn Function to wrap
	 */
	$ush.debounce_fn_1ms = $ush.debounce( $ush.fn, /*wait*/1 );

	/**
	 * Execute a function after a 10ms delay.
	 *
	 * @debounced
	 * @param {Function} fn Function to wrap
	 */
	$ush.debounce_fn_10ms = $ush.debounce( $ush.fn, /*wait*/10 );

	/**
	 * Parse a string and returns an integer of the specified radix.
	 *
	 * @param {String} value Input value
	 * @return {Number} Parsed integer, or 0 if NaN
	 */
	$ush.parseInt = function( value ) {
		value = parseInt( value, 10 );
		return ! isNaN( value ) ? value : 0;
	};

	/**
	 * Parse a value and returns a floating point number.
	 * Note: IEEE 754 standard (https://en.wikipedia.org/wiki/Signed_zero)
	 *
	 * @param {*} value Value to parse
	 * @return {Number} Parsed floating point number
	 */
	$ush.parseFloat = function( value ) {
		value = parseFloat( value );
		return ! isNaN( value ) ? value : 0;
	};

	/**
	 * Clamp a value within a specified range.
	 *
	 * @param {Number} value Current value
	 * @param {Number} minValue Minimum allowed value
	 * @param {Number} maxValue Maximum allowed value
	 * @return {Number} Value constrained within the range
	 */
	$ush.limitValueByRange = function( value, minValue, maxValue ) {
		return $ush.parseFloat( min( maxValue, max( minValue, value ) ) );
	};

	/**
	 * Convert array-like objects into a real array (e.g. Arguments, HTMLCollection).
	 *
	 * @param {{}} data Array-like object
	 * @return {[]} Converted array
	 */
	$ush.toArray = function( data ) {
		if ( [ 'string', 'number', 'bigint', 'boolean', 'symbol', 'function' ].includes( typeof data ) ) {
			return [ data ];
		}
		try {
			data = [].slice.call( data || [] );
		} catch ( err ) {
			console.error( err );
			data = [];
		}
		return data;
	};

	/**
	 * Convert a JS value to a string.
	 *
	 * @param {*} value Value to convert
	 * @return {String} String representation, or empty string on failure
	 */
	$ush.toString = function( value ) {
		const self = this;
		if ( self.isUndefined( value ) || value === null ) {
			return '';
		}
		else if ( $.isPlainObject( value ) || Array.isArray( value ) ) {
			return self.rawurlencode( JSON.stringify( value ) );
		}
		return String( value );
	};

	/**
	 * Convert a string representation into a plain object.
	 *
	 * @param {String} value Input string
	 * @return {{}} Parsed object
	 */
	$ush.toPlainObject = function( value ) {
		const self = this;
		try {
			value = JSON.parse( self.rawurldecode( value ) || '{}' );
		} catch ( err ) {}
		if ( ! $.isPlainObject( value ) ) {
			value = {};
		}
		return value;
	};

	/**
	 * Convert a string to lowercase.
	 *
	 * @param {String} value Input string
	 * @return {String} Lowercase string
	 */
	$ush.toLowerCase = function( value ) {
		return $ush.toString( value ).toLowerCase();
	};

	/**
	 * Create a deep copy of an object.
	 *
	 * @param {{}} _object Source object
	 * @param {{}} _default Default object
	 * @return {{}} Cloned object
	 */
	$ush.clone = function( _object, _default ) {
		return $.extend( true, {}, _default || {}, _object || {} );
	};

	/**
	 * Escape special characters for PCRE (Perl Compatible Regular Expressions).
	 *
	 * @param {String} value Input string
	 * @return {String} Escaped string
	 */
	$ush.escapePcre = function( value ) {
		return $ush.toString( value ).replace( /[.*+?^${}()|\:[\]\\]/g, '\\$&' ); // $& means the whole matched string
	};

	/**
	 * Remove all spaces and tabs from a string.
	 *
	 * @param {String} text Input text
	 * @return {String} String without whitespace
	 */
	$ush.removeSpaces = function( text ) {
		return $ush.toString( text ).replace( /\p{Zs}/gu, '' );
	};

	/**
	 * Replace hexadecimal codes with Unicode characters.
	 *
	 * @param {String} text Text containing hex codes (e.g. "&#x...;")
	 * @return {String} String with decoded Unicode characters
	 */
	$ush.fromCharCode = function( text ) {
		return $ush.toString( text ).replace( /&#(\d+);/g, ( _, num ) => fromCharCode( num ) );
	};

	/**
	 * Compare two plain objects for equality.
	 *
	 * @param {{}} firstObject First object
	 * @param {{}} secondObject Second object
	 * @return {Boolean} true if equal, otherwise false
	 */
	$ush.comparePlainObject = function() {
		const args = arguments;
		for ( var i = 1; i > -1; i-- ) {
			if ( ! $.isPlainObject( args[ i ] ) ) {
				return false;
			}
		}
		return JSON.stringify( args[0] ) === JSON.stringify( args[1] );
	};

	/**
	 * Generate a checksum from a value.
	 *
	 * @param {*} value Input value
	 * @return {Number} Numeric checksum
	 */
	$ush.checksum = function( value ) {
		if ( typeof value !== 'string' ) {
			value = JSON.stringify( value );
		}
		if ( value.length ) {
			return value.split( '' ).reduce( ( acc, val ) => ( acc = ( acc << 5 ) - acc + val.charCodeAt(0) ) & acc );
		}
		return 0;
	};

	/**
	 * Get element size and position relative to the viewport.
	 *
	 * @param {Node} node DOM node
	 * @return {{}} Element metrics (size and position)
	 */
	$ush.$rect = function( node ) {
		return this.isNode( node )
			? node.getBoundingClientRect()
			: {};
	};

	/**
	 * Set the caret position in a node.
	 *
	 * @param {Node} node DOM node
	 * @param {Number} position Caret position
	 */
	$ush.setCaretPosition = function( node, position ) {
		const self = this;
		if ( ! self.isNode( node ) ) {
			return;
		}
		// Set caret to end by default
		if ( self.isUndefined( position ) ) {
			position = node.value.length;
		}
		if ( node.createTextRange ) {
			const range = node.createTextRange();
			range.move( 'character', position );
			range.select();
		} else {
			if ( node.selectionStart ) {
				node.focus();
				node.setSelectionRange( position, position );
			} else {
				node.focus();
			}
		}
	};

	/**
	 * Copie text to the clipboard.
	 *
	 * @param {String} text Text to copy
	 * @return {Boolean} True if successful, otherwise false
	 */
	$ush.copyTextToClipboard = function( text ) {
		const self = this;
		try {
			const textarea = _document.createElement( 'textarea' );
			textarea.value = self.toString( text );
			textarea.setAttribute( 'readonly', '' );
			textarea.setAttribute( 'css', 'position:absolute;top:-9999px;left:-9999px' );
			_document.body.append( textarea );
			textarea.select();
			_document.execCommand( 'copy' );
			if ( _window.getSelection ) {
				_window.getSelection().removeAllRanges();
			} else if ( _document.selection ) {
				_document.selection.empty();
			}
			textarea.remove();
			return true;
		} catch ( err ) {
			return false;
		}
	};

	/**
	 * Get a dedicated storage instance.
	 *
	 * Note: User agents may restrict access to localStorage depending on origin and iframe context.
	 *
	 * @param {String} namespace Unique namespace
	 * @return {{}} Storage API object
	 */
	$ush.storage = function( namespace ) {
		if ( namespace = $ush.toString( namespace ) ) {
			namespace += '_'; // separator
		}
		const _localStorage = _window.localStorage;
		return {
			set: function( key, value ) {
				_localStorage.setItem( namespace + key, value );
			},
			get: function( key ) {
				return _localStorage.getItem( namespace + key );
			},
			remove: function( key ) {
				_localStorage.removeItem( namespace + key );
			}
		}
	};

	/**
	 * Set a cookie.
	 *
	 * @param {String} name Cookie name
	 * @param {String} value Cookie value
	 * @param {Number} expiry Expiry in days
	 */
	$ush.setCookie = function ( name, value, expiry ) {
		const date = new Date()
		date.setTime( date.getTime() + ( expiry * /* 24 * 60 * 60 * 1000 */86400000 ) );
		// Cookies cannot exceed 4096 bytes, as specified in RFC 2109 (No. 6.3), RFC 2965 (No. 5.3) and RFC 6265.
		_document.cookie = name + '=' + value + ';expires=' + date.toUTCString() + ';path=/';
	};

	/**
	 * Get a cookie value.
	 *
	 * @param {String} name Cookie name
	 * @return {String|null} Cookie value, or null if not found
	 */
	$ush.getCookie = function( name ) {
		name += '='
		const decodedCookie = decodeURIComponent( _document.cookie );
		const cookies = decodedCookie.split( ';' );
		for ( var i = 0; i < cookies.length; i++ ) {
			var cookie = cookies[i];
			while ( cookie.charAt(0) == ' ' ) {
				cookie = cookie.substring(1);
			}
			if ( cookie.indexOf( name ) == 0 ) {
				return cookie.substring( name.length, cookie.length );
			}
		}
		return null;
	};

	/**
	 * Remove a cookie.
	 * Note: Method not used.
	 *
	 * @param {String} name Cookie name
	 */
	$ush.removeCookie = function( name ) {
		const self = this;
		if ( self.getCookie( name ) !== null ) {
			self.setCookie( name, 1, /*days*/-1 );
		}
	};

	/**
	 * Download data as a file.
	 *
	 * @param {*} data File content
	 * @param {String} fileName Name of the file
	 * @param {String} type MIME type
	 */
	$ush.download = function( data, fileName, type ) {
		const fileBlob = new Blob( [ String( data ) ], { type: type } );
		if ( _navigator.msSaveOrOpenBlob ) { // IE10+
			_navigator.msSaveOrOpenBlob( fileBlob, fileName );
		} else { // Others
			const url = _window.URL.createObjectURL( fileBlob );
			const anchorElement = _document.createElement( 'a' );
			anchorElement.href = url;
			anchorElement.download = fileName;
			_document.body.appendChild( anchorElement );
			anchorElement.click();
			$ush.timeout( () => {
				_document.body.removeChild( anchorElement );
				_window.URL.revokeObjectURL( url );
			} );
		}
	};

	/**
	 * Returns the current time.
	 *
	 * @return {Number} Current timestamp (e.g. milliseconds since epoch)
	 */
	$ush.time = function() {
		return new Date().getTime();
	};

	/**
	 * Event methods for importing into an object.
	 */
	$ush.mixinEvents = {
		/**
		 * Attache an event handler to the class instance.
		 *
		 * @param {String} eventType Event type
		 * @param {Function} handler Event handler function
		 * @param {Boolean} one If true, handler is executed only once
		 * @return self
		 */
		on: function( eventType, handler, one ) {
			const self = this;
			if ( self.$$events === _undefined ) {
				self.$$events = {};
			}
			if ( self.$$events[ eventType ] === _undefined ) {
				self.$$events[ eventType ] = [];
			}
			self.$$events[ eventType ].push( {
				handler: handler,
				one: !! one,
			} );
			return self;
		},

		/**
		 * Attache an event handler to the class instance. The handler is executed at most once.
		 *
		 * @param {String} eventType Event type
		 * @param {Function} handler Event handler function
		 * @return self
		 */
		one: function( eventType, handler ) {
			return this.on( eventType, handler, /*one*/true );
		},

		/**
		 * Remove a previously attached event handler from the class instance.
		 *
		 * @chainable
		 * @param {String} eventType Event type
		 * @param {Function} [handler] Handler to remove
		 * @return self
		 */
		off: function( eventType, handler ) {
			const self = this;
			if (
				self.$$events === _undefined
				|| self.$$events[ eventType ] === _undefined
			) {
				return self;
			}
			if ( handler !== _undefined ) {
				for ( const handlerPos in self.$$events[ eventType ] ) {
					if ( handler === self.$$events[ eventType ][ handlerPos ].handler ) {
						self.$$events[ eventType ].splice( handlerPos, 1 );
					}
				}
			} else {
				self.$$events[ eventType ] = [];
			}
			return self;
		},

		/**
		 * Execute all handlers and behaviors attached to the class instance for the given event type.
		 *
		 * @chainable
		 * @param {String} eventType Event type
		 * @param {[]} extraParams Additional parameters passed to event handlers
		 * @return self
		 */
		trigger: function( eventType, extraParams ) {
			const self = this;
			if (
				self.$$events === _undefined
				|| self.$$events[ eventType ] === _undefined
				|| self.$$events[ eventType ].length === 0
			) {
				return self;
			}
			const args = arguments;
			const params = ( args.length > 2 || ! Array.isArray( extraParams ) )
				? [].slice.call( args, 1 )
				: extraParams;
			for ( var i = 0; i < self.$$events[ eventType ].length; i++ ) {
				const event = self.$$events[ eventType ][ i ];
				event.handler.apply( event.handler, params );
				if ( !! event.one ) {
					self.off( eventType, event.handler );
				}
			}
			return self;
		}
	};

	/**
	 * URL Manager.
	 *
	 * @param {String} url Optional URL
	 * @return {{}} API object
	 */
	$ush.urlManager = function( url ) {

		const $window = $( _window );
		const events = $ush.clone( $ush.mixinEvents );

		var _url = new URL( $ush.isUndefined( url ) ? _location.href : url ),
			lastUrl = _url.toString();

		if ( $ush.isUndefined( url ) ) {
			function refresh() {
				_url = new URL( lastUrl = _location.href );
			}
			$window
				.on( 'pushstate', refresh )
				.on( 'popstate', ( e ) => {
					refresh();
					events.trigger( 'popstate', e.originalEvent );
				} );
		}

		// URL Manager API
		return $.extend( events, {

			/**
			 * Check whether a change has occurred.
			 *
			 * @return {Boolean} true if changed, otherwise false
			 */
			isChanged: function() {
				return this.toString() !== _location.href;
			},

			/**
			 * Check whether a parameter exists and optionally matches a value.
			 *
			 * @param {String} key The key
			 * @param {String|Number} [value] Optional value to compare
			 * @return {Boolean} true if parameter exists (and matches value if provided), otherwise false
			 */
			has: function( key, value ) {
				if ( typeof key === 'string' ) {
					const hasKey = _url.searchParams.has( key );
					if ( ! value ) {
						return hasKey;
					}
					return hasKey && _url.searchParams.get( key ) === value;
				}
				return false;
			},

			/**
			 * Set parameters.
			 *
			 * @param {String|{}} key Key name or object of key-value pairs
			 * @param {String} [value] Optional value (undefined removes the parameter)
			 * @return self
			 */
			set: function( key, value ) {
				const setParam = ( key, value ) => {
					if ( $ush.isUndefined( value ) || value === null ) {
						_url.searchParams.delete( key );
					} else {
						_url.searchParams.set( key, $ush.toString( value ) );
					}
				};
				if ( $.isPlainObject( key ) ) {
					for ( const k in key ) {
						setParam( k, key[ k ] );
					}
				} else {
					setParam( key, value );
				}
				return this;
			},

			/**
			 * Get parameter values.
			 *
			 * @param {String} key Key or keys
			 * @return {*} Value if found, otherwise undefined
			 */
			get: function() {
				const args = $ush.toArray( arguments );
				const result = {};
				for ( const key of args ) {
					if ( this.has( key ) ) {
						result[ key ] = _url.searchParams.get( key );
					} else {
						result[ key ] = _undefined;
					}
				}
				if ( args.length === 1 ) {
					return Object.values( result )[0];
				}
				return result;
			},

			/**
			 * Remove parameters.
			 *
			 * @param {String} key Parameter key or keys
			 * @return self
			 */
			remove: function() {
				const self = this;
				const args = $ush.toArray( arguments );
				for ( const key of args ) if ( self.has( key ) ) {
					_url.searchParams.delete( key );
				}
				return self;
			},

			/**
			 * Get the URL string.
			 *
			 * @return {String} URL string
			 */
			toString: function( urldecode ) {
				return _url.toString();
			},

			/**
			 * Get all parameters in JSON format.
			 *
			 * @param {Boolean} toString If true, returns JSON string
			 * @return {{}|String} Object or JSON string, or empty object/string
			 */
			toJson: function( toString ) {
				var result = {};
				_url.searchParams.forEach( ( _, key, searchParams ) => {
					var values = searchParams.getAll( key );
					if ( values.length < 2 ) {
						values = values[0];
					}
					result[ key ] = $ush.isUndefined( values ) ? '' : values;
				} );
				if ( toString ) {
					result = JSON.stringify( result );
					if ( result === '{}' ) {
						result = '';
					}
				}
				return result;
			},

			/**
			 * Parameters ignored when detecting modified parameters.
			 */
			ignoreParams: [],

			/**
			 * Get changed parameters.
			 *
			 * @return {{}} Changed data
			 */
			getChangedParams: function() {
				const self = this;
				const data = {
					setParams: {}, // set params
					oldParams: {} // old params that have been changed or deleted
				};
				if ( ! self.isChanged() ) {
					return data;
				}
				const ignoreParams = $ush.toArray( self.ignoreParams );
				// Sets old params
				( new URL( lastUrl ) ).searchParams.forEach( ( value, key ) => {
					if ( ! ignoreParams.includes( key ) && ! self.has( key, value ) ) {
						data.oldParams[ key ] = value;
					}
				} );
				// Sets new params
				_url.searchParams.forEach( ( value, key ) => {
					if (
						! ignoreParams.includes( key )
						|| (
							! $ush.isUndefined( data.oldParams[ key ] )
							&& data.oldParams[ key ] !== value
						)
					) {
						data.setParams[ key ] = value;
					}
				} );
				return $ush.clone( data );
			},

			/**
			 * Push an entry into the browser session history stack.
			 *
			 * @param {{}} [state] Optional state object
			 * @return self
			 */
			push: function ( state, urldecode ) {
				const self = this;
				if ( ! self.isChanged() ) {
					return;
				}
				if ( ! $.isPlainObject( state ) ) {
					state = {};
				}
				history.pushState( $.extend( state, self.getChangedParams() ), '', lastUrl = self.toString() );

				$window.trigger( 'pushstate' );

				return self;
			}

		} );
	};

} ( jQuery );
