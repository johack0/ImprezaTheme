<?php
/**
 * Plugin Name: Impreza - Line Height Examples
 * Description: Personalizza gli esempi del campo Interlinea in Impreza.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'us_config_elements_misc', static function ( $config ) {
	if ( empty( $config['desc_line_height'] ) || ! is_string( $config['desc_line_height'] ) ) {
		return $config;
	}

	$config['desc_line_height'] = __( 'Examples:', 'us' )
		. ' <span class="usof-example">1.2</span>, <span class="usof-example">1.4</span>, <span class="usof-example">1.5</span>, <span class="usof-example">1.6</span>, <span class="usof-example">1.7</span>';

	return $config;
} );
