<?php
/**
 * Plugin Name: Impreza - Animation Delay Examples
 * Description: Aggiunge 0.2s, 0.3s e 0.4s agli esempi esistenti del campo Animation Delay
 * Version: 1.0.0
 */

// Prevenire accesso diretto
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aggiunge 0.2s, 0.3s e 0.4s agli esempi esistenti del campo Animation Delay (250ms, 0.5s, 1s, 1.5s)
 */
add_filter( 'us_config_elements_design_options', function( $design_options ) {
	if ( ! isset( $design_options['css']['params']['animation-delay'] ) ) {
		return $design_options;
	}

	$param = &$design_options['css']['params']['animation-delay'];
	$desc  = isset( $param['description'] ) ? $param['description'] : '';

	if ( strpos( $desc, '<span class="usof-example">0.2s</span>' ) === false ) {
		$param['description'] = str_replace(
			'<span class="usof-example">250ms</span>, <span class="usof-example">0.5s</span>',
			'<span class="usof-example">250ms</span>, <span class="usof-example">0.2s</span>, <span class="usof-example">0.3s</span>, <span class="usof-example">0.4s</span>, <span class="usof-example">0.5s</span>',
			$desc
		);
	}

	return $design_options;
} );
