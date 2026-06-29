<?php
/**
 * Plugin Name: Impreza - Text Underline Options
 * Description: Aggiunge i controlli per la sottolineatura nella tab Text delle Design Options di Impreza.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aggiunge i campi di text decoration alla tab Text delle Design Options.
 *
 * @param array $design_options Configurazione corrente delle Design Options.
 * @return array Configurazione aggiornata.
 */
function us_add_text_underline_design_option( $design_options ) {
	if ( ! isset( $design_options['css']['params'] ) || ! is_array( $design_options['css']['params'] ) ) {
		return $design_options;
	}

	if ( isset( $design_options['css']['params']['text-decoration-line'] ) ) {
		return $design_options;
	}

	$text_decoration_params = array(
		'text-decoration-line' => array(
			'title' => __( 'Decorazione testo', 'us' ),
			'type' => 'select',
			'options' => array(
				'' => '- ' . us_translate( 'Default' ) . ' -',
				'none' => __( 'Nessuna', 'us' ),
				'underline' => __( 'Sottolineato', 'us' ),
				'overline' => __( 'Sopralineato', 'us' ),
				'line-through' => __( 'Barrato', 'us' ),
			),
			'std' => '',
			'cols' => 2,
			'group' => us_translate( 'Text' ),
		),
		'text-decoration-style' => array(
			'title' => __( 'Stile decorazione', 'us' ),
			'type' => 'select',
			'options' => array(
				'' => '- ' . us_translate( 'Default' ) . ' -',
				'solid' => 'solid',
				'double' => 'double',
				'dotted' => 'dotted',
				'dashed' => 'dashed',
				'wavy' => 'wavy',
			),
			'std' => '',
			'cols' => 2,
			'group' => us_translate( 'Text' ),
			'show_if' => array( 'text-decoration-line', '=', 'underline' ),
		),
		'text-decoration-skip-ink' => array(
			'title' => __( 'Skip ink', 'us' ),
			'type' => 'select',
			'options' => array(
				'' => '- ' . us_translate( 'Default' ) . ' -',
				'auto' => 'auto',
				'none' => 'none',
				'all' => 'all',
			),
			'std' => '',
			'cols' => 2,
			'group' => us_translate( 'Text' ),
			'show_if' => array( 'text-decoration-line', '=', 'underline' ),
		),
		'text-decoration-thickness' => array(
			'title' => __( 'Spessore decorazione', 'us' ),
			'description' => __( 'Examples:', 'us' ) . ' <span class="usof-example">4%</span>, <span class="usof-example">2px</span>, <span class="usof-example">0.08em</span>, <span class="usof-example">from-font</span>',
			'type' => 'text',
			'std' => '',
			'cols' => 2,
			'group' => us_translate( 'Text' ),
			'show_if' => array( 'text-decoration-line', '=', 'underline' ),
		),
		'text-underline-offset' => array(
			'title' => __( 'Offset sottolineatura', 'us' ),
			'description' => __( 'Examples:', 'us' ) . ' <span class="usof-example">25%</span>, <span class="usof-example">2px</span>, <span class="usof-example">0.1em</span>, <span class="usof-example">auto</span>',
			'type' => 'text',
			'std' => '',
			'cols' => 2,
			'group' => us_translate( 'Text' ),
			'show_if' => array( 'text-decoration-line', '=', 'underline' ),
		),
		'text-underline-position' => array(
			'title' => __( 'Posizione sottolineatura', 'us' ),
			'type' => 'select',
			'options' => array(
				'' => '- ' . us_translate( 'Default' ) . ' -',
				'auto' => 'auto',
				'from-font' => 'from-font',
				'under' => 'under',
				'left' => 'left',
				'right' => 'right',
			),
			'std' => '',
			'cols' => 2,
			'group' => us_translate( 'Text' ),
			'show_if' => array( 'text-decoration-line', '=', 'underline' ),
		),
	);

	$params = array();
	$inserted = false;

	foreach ( $design_options['css']['params'] as $param_name => $param ) {
		$params[ $param_name ] = $param;

		if ( $param_name === 'font-style' ) {
			$params = array_merge( $params, $text_decoration_params );
			$inserted = true;
		}
	}

	if ( ! $inserted ) {
		$params = array_merge( $params, $text_decoration_params );
	}

	$design_options['css']['params'] = $params;

	return $design_options;
}
add_filter( 'us_config_elements_design_options', 'us_add_text_underline_design_option', 20 );
