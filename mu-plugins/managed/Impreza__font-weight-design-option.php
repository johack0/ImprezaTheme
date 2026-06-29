<?php
/**
 * Plugin Name: Impreza - Font Weight Examples
 * Description: Mostra i pesi cliccabili sotto la select "Font Weight" nella tab Design > Text.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Genera gli esempi cliccabili per il campo font-weight.
 *
 * @return string HTML degli esempi.
 */
function impreza_get_font_weight_examples_html() {
	$examples = array();

	foreach ( array( '100', '200', '300', '400', '500', '600', '700', '800', '900' ) as $value ) {
		$examples[] = '<span class="usof-example" data-value="' . esc_attr( $value ) . '">' . esc_html( $value ) . '</span>';
	}

	return implode( ', ', $examples );
}

/**
 * Aggiunge gli esempi alla descrizione del campo senza duplicarli.
 *
 * @param string $description Descrizione corrente.
 * @return string Descrizione aggiornata.
 */
function impreza_font_weight_examples_description( $description ) {
	$description          = trim( (string) $description );
	$examples             = impreza_get_font_weight_examples_html();
	$examples_description = __( 'Examples:', 'us' ) . ' ' . $examples;

	$clean_description = preg_replace(
		'/(?:<br\s*\/?>\s*)?(?:(?:Examples|Esempi):\s*)?<span class="usof-example"[^>]*>\s*100\s*<\/span>(?:\s*,\s*<span class="usof-example"[^>]*>\s*(?:200|300|400|500|600|700|800|900)\s*<\/span>)+/i',
		'',
		$description
	);

	if ( is_string( $clean_description ) ) {
		$description = trim( $clean_description );
	}

	if ( $description === '' ) {
		return $examples_description;
	}

	return $description . '<br>' . $examples_description;
}

/**
 * Aggiunge gli esempi cliccabili al campo font-weight gia presente.
 * Non modifica l'ordine originale dei campi Impreza.
 *
 * @param array $design_options Configurazione corrente delle Design Options.
 * @return array Configurazione aggiornata.
 */
function impreza_show_font_weight_design_option( $design_options ) {
	if (
		! isset( $design_options['css']['params']['font-weight'] )
		|| ! is_array( $design_options['css']['params']['font-weight'] )
	) {
		return $design_options;
	}

	$current_description = isset( $design_options['css']['params']['font-weight']['description'] )
		? $design_options['css']['params']['font-weight']['description']
		: '';
	$design_options['css']['params']['font-weight']['description'] = impreza_font_weight_examples_description( $current_description );

	return $design_options;
}
add_filter( 'us_config_elements_design_options', 'impreza_show_font_weight_design_option', 30 );

/**
 * Rende cliccabili gli esempi del campo font-weight anche se il campo è una select.
 */
function impreza_font_weight_examples_script() {
	?>
	<script type="text/javascript">
	jQuery( document ).ready( function( $ ) {
		$( document ).on( 'click', '.usof-form-row[data-name="font-weight"] .usof-example[data-value]', function( e ) {
			e.preventDefault();
			e.stopPropagation();

			var $example = $( this );
			var value = $example.attr( 'data-value' );
			var $row = $example.closest( '.usof-form-row' );
			var $select = $row.find( 'select' );

			if ( ! value || ! $select.length || ! $select.find( 'option[value="' + value + '"]' ).length ) {
				return;
			}

			$select.val( value ).trigger( 'change' );

			var usofField = $row.data( 'usofField' );
			if ( usofField && typeof usofField.setValue === 'function' ) {
				try {
					usofField.setValue( value, false );
				} catch ( err ) {
					// Il change sul select e sufficiente nella maggior parte dei contesti.
				}
			}
		} );
	} );
	</script>
	<?php
}
add_action( 'admin_footer', 'impreza_font_weight_examples_script', 999 );
add_action( 'usb_admin_footer_scripts', 'impreza_font_weight_examples_script', 999 );
