<?php
/**
 * Plugin Name: Impreza - Letter Spacing Examples
 * Description: Personalizza gli esempi del campo Spazio lettere in Impreza.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'us_config_elements_misc', static function ( $config ) {
	if ( empty( $config['desc_letter_spacing'] ) || ! is_string( $config['desc_letter_spacing'] ) ) {
		return $config;
	}

	$letter_spacing_examples = array( '-0.5px', '0.5px', '1px', '2px' );
	$letter_spacing_spans = array();

	foreach ( $letter_spacing_examples as $example ) {
		$letter_spacing_spans[] = '<span class="usof-example">' . $example . '</span>';
	}

	$description_intro = preg_replace( '/\s*<span class="usof-example">.*$/s', '', $config['desc_letter_spacing'] );
	$config['desc_letter_spacing'] = rtrim( $description_intro ) . ' ' . implode( ', ', $letter_spacing_spans );

	return $config;
} );
