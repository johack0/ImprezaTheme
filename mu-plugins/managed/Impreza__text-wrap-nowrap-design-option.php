<?php
/**
 * Plugin Name: Impreza - Text Wrap No Wrap
 * Description: Aggiunge l'opzione "No Wrap" al campo Text Wrap delle Design Options di Impreza.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aggiunge l'opzione CSS text-wrap: nowrap alle Design Options.
 *
 * @param array $design_options Configurazione corrente delle Design Options.
 * @return array Configurazione aggiornata.
 */
function impreza_add_text_wrap_nowrap_design_option( $design_options ) {
	if (
		! isset( $design_options['css']['params']['text-wrap']['options'] )
		|| ! is_array( $design_options['css']['params']['text-wrap']['options'] )
	) {
		return $design_options;
	}

	$design_options['css']['params']['text-wrap']['options']['nowrap'] = 'No Wrap';

	return $design_options;
}
add_filter( 'us_config_elements_design_options', 'impreza_add_text_wrap_nowrap_design_option', 20 );
