<?php
/**
 * Plugin Name: Impreza - Display Logic Device Conditions
 * Description: Aggiunge condizioni Display Logic per mostrare o non stampare elementi in base ai breakpoint responsive di Impreza.
 * Version: 1.6.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Elenco delle condizioni breakpoint aggiunte alla Display Logic di Impreza.
 *
 * @return array<string,string[]>
 */
function impreza_display_logic_device_conditions_map() {
	return array(
		'impreza_device_is_desktop' => array( 'default' ),
		'impreza_breakpoint_is_laptops' => array( 'laptops' ),
		'impreza_device_is_not_mobile_or_tablet' => array( 'default', 'laptops' ),
		'impreza_device_is_tablet' => array( 'tablets' ),
		'impreza_device_is_mobile' => array( 'mobiles' ),
		'impreza_device_is_mobile_or_tablet' => array( 'tablets', 'mobiles' ),
	);
}

/**
 * Aggiunge le condizioni breakpoint alla select "Condition" nella tab Display Logic.
 *
 * @param array $param_options Opzioni correnti.
 * @return array Opzioni aggiornate.
 */
function impreza_display_logic_add_device_condition_options( $param_options ) {
	if ( ! is_array( $param_options ) ) {
		return $param_options;
	}

	$param_options['impreza_device_is_desktop'] = __( 'Current Breakpoint is Desktop', 'us' );
	$param_options['impreza_breakpoint_is_laptops'] = __( 'Current Breakpoint is Laptops', 'us' );
	$param_options['impreza_device_is_not_mobile_or_tablet'] = __( 'Current Breakpoint is Desktop or Laptops', 'us' );
	$param_options['impreza_device_is_tablet'] = __( 'Current Breakpoint is Tablets', 'us' );
	$param_options['impreza_device_is_mobile'] = __( 'Current Breakpoint is Mobile Devices', 'us' );
	$param_options['impreza_device_is_mobile_or_tablet'] = __( 'Current Breakpoint is Tablets or Mobile Devices', 'us' );

	return $param_options;
}
add_filter( 'us_conditional_param_options', 'impreza_display_logic_add_device_condition_options', 20 );

/**
 * Aggiunge un controllo diretto nella tab Display Logic.
 *
 * Questo campo non dipende dal select principale "Always / If every / Never":
 * viene letto direttamente dagli attributi finali dell'elemento prima del render.
 *
 * @param array $conditional_options Configurazione corrente della Display Logic.
 * @return array Configurazione aggiornata.
 */
function impreza_display_logic_add_render_on_device_param( $conditional_options ) {
	if ( ! is_array( $conditional_options ) ) {
		return $conditional_options;
	}

	$render_on_device_param = array(
		'title' => __( 'Render HTML on Breakpoint', 'us' ),
		'description' => __( 'When the current Impreza breakpoint does not match, the element is not printed in the page HTML.', 'us' ),
		'type' => 'select',
		'options' => array(
			'all' => __( 'All breakpoints', 'us' ),
			'desktop' => __( 'Desktop only', 'us' ),
			'laptops' => __( 'Laptops only', 'us' ),
			'desktop_laptops' => __( 'Desktop and Laptops', 'us' ),
			'tablet' => __( 'Tablets only', 'us' ),
			'mobile' => __( 'Mobile Devices only', 'us' ),
			'mobile_tablet' => __( 'Tablets and Mobile Devices', 'us' ),
		),
		'std' => 'all',
		'group' => __( 'Display Logic', 'us' ),
		'usb_check_param_for_data_indicator' => true,
	);
	$mobile_width_param = array(
		'title' => __( 'Mobile max width', 'us' ),
		'description' => __( 'Optional pixel value. Leave empty to use the Impreza mobile breakpoint.', 'us' ),
		'type' => 'text',
		'std' => '',
		'placeholder' => '600',
		'group' => __( 'Display Logic', 'us' ),
		'show_if' => array( 'impreza_render_on_device', '!=', 'all' ),
	);
	$tablet_width_param = array(
		'title' => __( 'Tablet max width', 'us' ),
		'description' => __( 'Optional pixel value. Leave empty to use the Impreza tablet breakpoint.', 'us' ),
		'type' => 'text',
		'std' => '',
		'placeholder' => '1024',
		'group' => __( 'Display Logic', 'us' ),
		'show_if' => array( 'impreza_render_on_device', '!=', 'all' ),
	);
	$laptops_width_param = array(
		'title' => __( 'Laptops max width', 'us' ),
		'description' => __( 'Optional pixel value. Leave empty to use the Impreza laptops breakpoint.', 'us' ),
		'type' => 'text',
		'std' => '',
		'placeholder' => '1380',
		'group' => __( 'Display Logic', 'us' ),
		'show_if' => array( 'impreza_render_on_device', '!=', 'all' ),
	);

	if ( ! isset( $conditional_options['conditions_operator'] ) ) {
		return array(
			'impreza_render_on_device' => $render_on_device_param,
			'impreza_render_mobile_max_width' => $mobile_width_param,
			'impreza_render_tablet_max_width' => $tablet_width_param,
			'impreza_render_laptops_max_width' => $laptops_width_param,
		) + $conditional_options;
	}

	$updated_options = array();
	foreach ( $conditional_options as $param_name => $param_config ) {
		$updated_options[ $param_name ] = $param_config;

		if ( $param_name === 'conditions_operator' ) {
			$updated_options['impreza_render_on_device'] = $render_on_device_param;
			$updated_options['impreza_render_mobile_max_width'] = $mobile_width_param;
			$updated_options['impreza_render_tablet_max_width'] = $tablet_width_param;
			$updated_options['impreza_render_laptops_max_width'] = $laptops_width_param;
		}
	}

	return $updated_options;
}
add_filter( 'us_config_elements_conditional_options', 'impreza_display_logic_add_render_on_device_param', 20 );

/**
 * Rileva il device corrente usando larghezza viewport, cookie o User-Agent lato server.
 *
 * Nota: questa logica decide se stampare o meno l'HTML, quindi non puo basarsi
 * sulla larghezza viewport CSS come le classi responsive native di Impreza.
 *
 * @param int|null $mobile_max_width Larghezza massima mobile opzionale.
 * @param int|null $tablet_max_width Larghezza massima tablet opzionale.
 * @param int|null $laptops_max_width Larghezza massima laptop opzionale.
 * @return string desktop|tablet|mobile
 */
function impreza_display_logic_get_current_device_type( $mobile_max_width = null, $tablet_max_width = null, $laptops_max_width = null ) {
	$viewport_width = impreza_display_logic_get_viewport_width();
	if ( $viewport_width > 0 ) {
		$responsive_state = impreza_display_logic_get_responsive_state_from_width(
			$viewport_width,
			$mobile_max_width,
			$tablet_max_width,
			$laptops_max_width
		);

		return in_array( $responsive_state, array( 'default', 'laptops' ), true )
			? 'desktop'
			: rtrim( $responsive_state, 's' );
	}

	$viewport_state = isset( $_COOKIE['impreza_render_state'] )
		? sanitize_key( wp_unslash( $_COOKIE['impreza_render_state'] ) )
		: '';

	if ( in_array( $viewport_state, array( 'default', 'laptops', 'tablets', 'mobiles' ), true ) ) {
		return in_array( $viewport_state, array( 'tablets', 'mobiles' ), true )
			? rtrim( $viewport_state, 's' )
			: 'desktop';
	}

	$viewport_device = isset( $_COOKIE['impreza_render_device'] )
		? sanitize_key( wp_unslash( $_COOKIE['impreza_render_device'] ) )
		: '';

	if ( in_array( $viewport_device, array( 'desktop', 'tablet', 'mobile' ), true ) ) {
		return $viewport_device;
	}

	$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
		? strtolower( (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
		: '';
	$sec_ch_ua_mobile = isset( $_SERVER['HTTP_SEC_CH_UA_MOBILE'] )
		? trim( (string) wp_unslash( $_SERVER['HTTP_SEC_CH_UA_MOBILE'] ), " \t\n\r\0\x0B\"" )
		: '';

	if ( $user_agent === '' ) {
		return ( $sec_ch_ua_mobile === '?1' ) ? 'mobile' : 'desktop';
	}

	$tablet_patterns = array(
		'ipad',
		'tablet',
		'kindle',
		'playbook',
		'silk',
		'xoom',
		'nexus 7',
		'nexus 9',
		'nexus 10',
		'sm-t',
		'gt-p',
		'kfapwi',
		'kfsowi',
		'kftt',
		'kfthwi',
		'kfthwa',
		'kfjwi',
		'kfjwa',
		'kfsawa',
		'kfarwi',
		'kfarwa',
		'kfmawi',
		'kfmawa',
	);

	foreach ( $tablet_patterns as $pattern ) {
		if ( strpos( $user_agent, $pattern ) !== false ) {
			return 'tablet';
		}
	}

	// iPadOS puo presentarsi come Macintosh, mantenendo pero il token Mobile.
	if (
		strpos( $user_agent, 'macintosh' ) !== false
		&& strpos( $user_agent, 'mobile' ) !== false
	) {
		return 'tablet';
	}

	// Molti tablet Android non includono "mobile" nello User-Agent.
	if (
		strpos( $user_agent, 'android' ) !== false
		&& strpos( $user_agent, 'mobile' ) === false
	) {
		return 'tablet';
	}

	if ( $sec_ch_ua_mobile === '?1' ) {
		return 'mobile';
	}

	if ( function_exists( 'wp_is_mobile' ) && wp_is_mobile() ) {
		return 'mobile';
	}

	$mobile_patterns = array(
		'mobile',
		'iphone',
		'ipod',
		'android',
		'blackberry',
		'bb10',
		'windows phone',
		'opera mini',
		'palm',
	);

	foreach ( $mobile_patterns as $pattern ) {
		if ( strpos( $user_agent, $pattern ) !== false ) {
			return 'mobile';
		}
	}

	return 'desktop';
}

/**
 * Restituisce la larghezza viewport salvata dal browser.
 *
 * @return int
 */
function impreza_display_logic_get_viewport_width() {
	if ( empty( $_COOKIE['impreza_render_width'] ) ) {
		return 0;
	}

	return max( 0, absint( wp_unslash( $_COOKIE['impreza_render_width'] ) ) );
}

/**
 * Normalizza un valore pixel opzionale.
 *
 * @param mixed $value Valore impostato nell'elemento.
 * @param int $fallback Valore fallback.
 * @return int
 */
function impreza_display_logic_normalize_pixel_value( $value, $fallback ) {
	if ( is_string( $value ) ) {
		$value = preg_replace( '/[^0-9]/', '', $value );
	}

	$value = absint( $value );

	return $value > 0 ? $value : (int) $fallback;
}

/**
 * Classifica la larghezza viewport in uno stato responsive Impreza.
 *
 * @param int $width Larghezza viewport.
 * @param int|null $mobile_max_width Larghezza massima mobile opzionale.
 * @param int|null $tablet_max_width Larghezza massima tablet opzionale.
 * @param int|null $laptops_max_width Larghezza massima laptop opzionale.
 * @return string default|laptops|tablets|mobiles
 */
function impreza_display_logic_get_responsive_state_from_width( $width, $mobile_max_width = null, $tablet_max_width = null, $laptops_max_width = null ) {
	$mobile_max_width = impreza_display_logic_normalize_pixel_value(
		$mobile_max_width,
		function_exists( 'us_get_option' ) ? (int) us_get_option( 'mobiles_breakpoint', 600 ) : 600
	);
	$tablet_max_width = impreza_display_logic_normalize_pixel_value(
		$tablet_max_width,
		function_exists( 'us_get_option' ) ? (int) us_get_option( 'tablets_breakpoint', 1024 ) : 1024
	);
	$laptops_max_width = impreza_display_logic_normalize_pixel_value(
		$laptops_max_width,
		function_exists( 'us_get_option' ) ? (int) us_get_option( 'laptops_breakpoint', 1380 ) : 1380
	);

	if ( $tablet_max_width < $mobile_max_width ) {
		$tablet_max_width = $mobile_max_width;
	}
	if ( $laptops_max_width < $tablet_max_width ) {
		$laptops_max_width = $tablet_max_width;
	}

	if ( $width <= $mobile_max_width ) {
		return 'mobiles';
	}

	if ( $width <= $tablet_max_width ) {
		return 'tablets';
	}

	if ( $width <= $laptops_max_width ) {
		return 'laptops';
	}

	return 'default';
}

/**
 * Classifica la larghezza viewport in device legacy.
 *
 * @param int $width Larghezza viewport.
 * @param int|null $mobile_max_width Larghezza massima mobile opzionale.
 * @param int|null $tablet_max_width Larghezza massima tablet opzionale.
 * @param int|null $laptops_max_width Larghezza massima laptop opzionale.
 * @return string desktop|tablet|mobile
 */
function impreza_display_logic_get_device_type_from_width( $width, $mobile_max_width = null, $tablet_max_width = null, $laptops_max_width = null ) {
	$responsive_state = impreza_display_logic_get_responsive_state_from_width(
		$width,
		$mobile_max_width,
		$tablet_max_width,
		$laptops_max_width
	);

	return in_array( $responsive_state, array( 'default', 'laptops' ), true )
		? 'desktop'
		: rtrim( $responsive_state, 's' );
}

/**
 * Restituisce lo stato responsive corrente secondo i breakpoint Impreza.
 *
 * @param int|null $mobile_max_width Larghezza massima mobile opzionale.
 * @param int|null $tablet_max_width Larghezza massima tablet opzionale.
 * @param int|null $laptops_max_width Larghezza massima laptop opzionale.
 * @return string default|laptops|tablets|mobiles
 */
function impreza_display_logic_get_current_responsive_state( $mobile_max_width = null, $tablet_max_width = null, $laptops_max_width = null ) {
	$viewport_width = impreza_display_logic_get_viewport_width();
	if ( $viewport_width > 0 ) {
		return impreza_display_logic_get_responsive_state_from_width(
			$viewport_width,
			$mobile_max_width,
			$tablet_max_width,
			$laptops_max_width
		);
	}

	$viewport_state = isset( $_COOKIE['impreza_render_state'] )
		? sanitize_key( wp_unslash( $_COOKIE['impreza_render_state'] ) )
		: '';

	if ( in_array( $viewport_state, array( 'default', 'laptops', 'tablets', 'mobiles' ), true ) ) {
		return $viewport_state;
	}

	$current_device = impreza_display_logic_get_current_device_type();

	if ( $current_device === 'mobile' ) {
		return 'mobiles';
	}

	if ( $current_device === 'tablet' ) {
		return 'tablets';
	}

	return 'default';
}

/**
 * Sincronizza il device server-side con la larghezza viewport reale.
 *
 * Senza questo passaggio, PHP puo usare solo lo User-Agent: ridurre la finestra
 * o usare un tablet in modalita desktop non cambia la risposta server.
 */
function impreza_display_logic_output_viewport_device_cookie_script() {
	if (
		is_admin()
		|| ( function_exists( 'usb_is_preview' ) && usb_is_preview() )
	) {
		return;
	}

	$mobiles_breakpoint = function_exists( 'us_get_option' )
		? (int) us_get_option( 'mobiles_breakpoint', 600 )
		: 600;
	$tablets_breakpoint = function_exists( 'us_get_option' )
		? (int) us_get_option( 'tablets_breakpoint', 1024 )
		: 1024;
	$laptops_breakpoint = function_exists( 'us_get_option' )
		? (int) us_get_option( 'laptops_breakpoint', 1380 )
		: 1380;

	// Stato responsive realmente usato da PHP per renderizzare questa richiesta.
	// Serve a ricaricare solo quando la stima server-side non coincide con il viewport reale,
	// evitando il reload inutile quando UA/cookie avevano gia indovinato lo stato corretto.
	$server_state = impreza_display_logic_get_current_responsive_state();
	?>
	<script>
	(function() {
		var deviceCookieName = 'impreza_render_device';
		var stateCookieName = 'impreza_render_state';
		var widthCookieName = 'impreza_render_width';
		var mobileMax = <?php echo (int) $mobiles_breakpoint; ?>;
		var tabletMax = <?php echo (int) $tablets_breakpoint; ?>;
		var laptopMax = <?php echo (int) $laptops_breakpoint; ?>;
		var serverState = <?php echo wp_json_encode( $server_state ); ?>;
		var width = Math.max( 0, Math.round( window.innerWidth || document.documentElement.clientWidth || screen.width || 0 ) );
		var state = width <= mobileMax ? 'mobiles' : ( width <= tabletMax ? 'tablets' : ( width <= laptopMax ? 'laptops' : 'default' ) );
		var device = state === 'mobiles' ? 'mobile' : ( state === 'tablets' ? 'tablet' : 'desktop' );
		var deviceMatch = document.cookie.match( new RegExp( '(?:^|; )' + deviceCookieName + '=([^;]*)' ) );
		var stateMatch = document.cookie.match( new RegExp( '(?:^|; )' + stateCookieName + '=([^;]*)' ) );
		var widthMatch = document.cookie.match( new RegExp( '(?:^|; )' + widthCookieName + '=([^;]*)' ) );
		var currentDevice = deviceMatch ? decodeURIComponent( deviceMatch[1] ) : '';
		var currentState = stateMatch ? decodeURIComponent( stateMatch[1] ) : '';
		var currentWidth = widthMatch ? decodeURIComponent( widthMatch[1] ) : '';

		// Mantiene sempre i cookie allineati al viewport reale, cosi le richieste successive
		// (navigazione/caching) partono gia con lo stato corretto, senza ricaricare.
		if ( currentDevice !== device || currentState !== state || currentWidth !== String( width ) ) {
			document.cookie = deviceCookieName + '=' + encodeURIComponent( device ) + '; path=/; max-age=2592000; SameSite=Lax';
			document.cookie = stateCookieName + '=' + encodeURIComponent( state ) + '; path=/; max-age=2592000; SameSite=Lax';
			document.cookie = widthCookieName + '=' + encodeURIComponent( width ) + '; path=/; max-age=2592000; SameSite=Lax';
		}

		// Ricarica solo se il server ha renderizzato uno stato diverso da quello reale:
		// se coincidono, l'HTML in pagina e gia corretto e il reload non serve.
		if ( width > 0 && state !== serverState ) {
			try {
				var reloadKey = 'impreza_render_state_reload_' + state + '_' + width;
				if ( ! sessionStorage.getItem( reloadKey ) ) {
					sessionStorage.setItem( reloadKey, '1' );
					window.location.reload();
				}
			} catch ( error ) {
				window.location.reload();
			}
		}
	})();
	</script>
	<?php
}
add_action( 'wp_head', 'impreza_display_logic_output_viewport_device_cookie_script', 0 );

/**
 * Header diagnostico per capire se questa richiesta passa dal MU plugin.
 */
function impreza_display_logic_send_diagnostic_header() {
	// Header diagnostico riservato allo sviluppo: in produzione (WP_DEBUG spento)
	// non viene emesso, per non esporre lo stato interno del plugin.
	if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
		return;
	}

	if ( headers_sent() || is_admin() ) {
		return;
	}

	header(
		sprintf(
			'X-Impreza-Device-Logic: active; device=%s; state=%s',
			impreza_display_logic_get_current_device_type(),
			impreza_display_logic_get_current_responsive_state()
		),
		false
	);
}
add_action( 'send_headers', 'impreza_display_logic_send_diagnostic_header', 100 );

/**
 * Impedisce che una variante HTML specifica per device venga salvata come cache generica.
 */
function impreza_display_logic_disable_page_cache_for_device_conditions() {
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}

	if ( function_exists( 'nocache_headers' ) && ! headers_sent() ) {
		nocache_headers();
	}

	if ( ! headers_sent() ) {
		header( 'Vary: Cookie, User-Agent, Sec-CH-UA-Mobile', false );
		header( 'Accept-CH: Sec-CH-UA-Mobile', false );
	}
}

/**
 * Restituisce gli stati responsive permessi dal campo diretto "Render HTML on Breakpoint".
 *
 * @param string $visibility Valore del campo.
 * @return string[]
 */
function impreza_display_logic_allowed_states_for_visibility( $visibility ) {
	switch ( (string) $visibility ) {
		case 'desktop':
			return array( 'default' );

		case 'laptops':
			return array( 'laptops' );

		case 'desktop_laptops':
			return array( 'default', 'laptops' );

		case 'mobile_tablet':
			return array( 'tablets', 'mobiles' );

		case 'mobile':
		case 'mobiles':
			return array( 'mobiles' );

		case 'tablet':
		case 'tablets':
			return array( 'tablets' );
	}

	return array( 'default', 'laptops', 'tablets', 'mobiles' );
}

/**
 * Verifica se un elemento con il campo diretto breakpoint deve essere renderizzato.
 *
 * @param string $visibility Valore del campo.
 * @param array $settings Impostazioni dell'elemento.
 * @return bool
 */
function impreza_display_logic_should_render_on_device( $visibility, $settings = array() ) {
	$visibility = (string) $visibility;

	if ( $visibility === '' || $visibility === 'all' ) {
		return true;
	}

	impreza_display_logic_disable_page_cache_for_device_conditions();

	if ( function_exists( 'usb_is_preview' ) && usb_is_preview() ) {
		return true;
	}

	$mobile_max_width = is_array( $settings ) && isset( $settings['impreza_render_mobile_max_width'] )
		? $settings['impreza_render_mobile_max_width']
		: null;
	$tablet_max_width = is_array( $settings ) && isset( $settings['impreza_render_tablet_max_width'] )
		? $settings['impreza_render_tablet_max_width']
		: null;
	$laptops_max_width = is_array( $settings ) && isset( $settings['impreza_render_laptops_max_width'] )
		? $settings['impreza_render_laptops_max_width']
		: null;

	return in_array(
		impreza_display_logic_get_current_responsive_state( $mobile_max_width, $tablet_max_width, $laptops_max_width ),
		impreza_display_logic_allowed_states_for_visibility( $visibility ),
		true
	);
}

/**
 * Applica il campo diretto device agli shortcode prima del controllo Display Logic core.
 *
 * @param array $atts Attributi finali dello shortcode.
 * @param string $shortcode Nome shortcode.
 * @return array Attributi aggiornati.
 */
function impreza_display_logic_apply_device_visibility_to_shortcode_atts( $atts, $shortcode ) {
	if ( ! is_array( $atts ) ) {
		return $atts;
	}

	if (
		! empty( $atts['impreza_render_on_device'] )
		&& $atts['impreza_render_on_device'] !== 'all'
		&& ! impreza_display_logic_should_render_on_device( $atts['impreza_render_on_device'], $atts )
	) {
		$atts['conditions_operator'] = 'never';
		$atts['conditions'] = array();
	}

	return $atts;
}
add_filter( 'us_shortcode_atts', 'impreza_display_logic_apply_device_visibility_to_shortcode_atts', 100, 2 );

/**
 * Applica il campo diretto device agli elementi Header e Grid Layout.
 *
 * @param array $settings Impostazioni builder.
 * @return array Impostazioni aggiornate.
 */
function impreza_display_logic_apply_device_visibility_to_builder_settings( $settings ) {
	if (
		! is_array( $settings )
		|| empty( $settings['data'] )
		|| ! is_array( $settings['data'] )
	) {
		return $settings;
	}

	foreach ( $settings['data'] as &$elm_data ) {
		if (
			! empty( $elm_data['impreza_render_on_device'] )
			&& $elm_data['impreza_render_on_device'] !== 'all'
			&& ! impreza_display_logic_should_render_on_device( $elm_data['impreza_render_on_device'], $elm_data )
		) {
			$elm_data['conditions_operator'] = 'never';
			$elm_data['conditions'] = array();
		}
	}
	unset( $elm_data );

	return $settings;
}
add_filter( 'us_load_header_settings', 'impreza_display_logic_apply_device_visibility_to_builder_settings', 100 );
add_filter( 'us_grid_layout_settings', 'impreza_display_logic_apply_device_visibility_to_builder_settings', 100 );

/**
 * Valuta le condizioni breakpoint aggiunte alla Display Logic.
 *
 * @param bool $condition_result Risultato corrente della condizione.
 * @param string $condition_param Nome della condizione.
 * @param int|string $current_id ID del contesto corrente.
 * @return bool Risultato aggiornato.
 */
function impreza_display_logic_device_condition_result( $condition_result, $condition_param, $current_id ) {
	$device_conditions = impreza_display_logic_device_conditions_map();

	if ( ! isset( $device_conditions[ $condition_param ] ) ) {
		return $condition_result;
	}

	impreza_display_logic_disable_page_cache_for_device_conditions();

	if ( function_exists( 'usb_is_preview' ) && usb_is_preview() ) {
		return true;
	}

	$current_state = impreza_display_logic_get_current_responsive_state();

	return in_array( $current_state, $device_conditions[ $condition_param ], true );
}
add_filter( 'us_conditional_param_result', 'impreza_display_logic_device_condition_result', 40, 3 );
