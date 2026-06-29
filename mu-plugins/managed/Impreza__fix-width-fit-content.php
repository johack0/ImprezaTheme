<?php
/**
 * Plugin Name: Impreza - Width Fit Content Example
 * Description: Aggiunge "fit-content" come esempio cliccabile sotto i campi Width, Max Width e Min Width nelle opzioni di design
 * Version: 1.0.0
 */

// Prevenire accesso diretto
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aggiunge fit-content agli esempi di desc_width (usato da width, max-width, min-width)
 */
add_filter( 'us_config_elements_misc', function( $misc ) {
	if ( isset( $misc['desc_width'] ) && strpos( $misc['desc_width'], 'fit-content' ) === false ) {
		$misc['desc_width'] .= ', <span class="usof-example">fit-content</span>';
	}
	return $misc;
} );
