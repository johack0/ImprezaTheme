<?php
/**
 * Plugin Name: Impreza - Text HTML Tag Examples
 * Description: Aggiunge tag HTML cliccabili sotto la select "HTML tag" del widget Text.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Genera gli esempi cliccabili dai valori consentiti dalla select HTML tag.
 *
 * @param array $options Opzioni del campo tag.
 * @return string HTML degli esempi.
 */
function impreza_text_html_tag_examples_html( $options ) {
	$examples = array();

	foreach ( (array) $options as $value => $label ) {
		if ( $value === '' ) {
			continue;
		}

		$examples[] = '<span class="usof-example" data-value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</span>';
	}

	return implode( ', ', $examples );
}

/**
 * Aggiunge la lista dei tag HTML al campo "HTML tag" nella tab General del widget Text.
 *
 * @param array $config Configurazione dell'elemento Text.
 * @return array Configurazione aggiornata.
 */
function impreza_add_html_tag_examples_to_text_element( $config ) {
	if (
		! isset( $config['params']['tag'] )
		|| ! is_array( $config['params']['tag'] )
		|| empty( $config['params']['tag']['options'] )
	) {
		return $config;
	}

	$current_description = isset( $config['params']['tag']['description'] )
		? trim( (string) $config['params']['tag']['description'] )
		: '';

	if ( strpos( $current_description, 'data-value="h1"' ) !== false ) {
		return $config;
	}

	$examples = impreza_text_html_tag_examples_html( $config['params']['tag']['options'] );
	if ( $examples === '' ) {
		return $config;
	}

	$examples_description = __( 'Examples:', 'us' ) . ' ' . $examples;

	$config['params']['tag']['description'] = $current_description === ''
		? $examples_description
		: $current_description . '<br>' . $examples_description;

	return $config;
}
add_filter( 'us_config_elements/text', 'impreza_add_html_tag_examples_to_text_element', 20 );

/**
 * Rende cliccabili gli esempi del campo HTML tag.
 */
function impreza_text_html_tag_examples_script() {
	?>
	<script type="text/javascript">
	jQuery( document ).ready( function( $ ) {
		$( document ).on( 'click', '.usof-form-row[data-name="tag"] .usof-example[data-value]', function( e ) {
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
					// Il change sul select basta nei contesti in cui USOF non espone setValue.
				}
			}
		} );
	} );
	</script>
	<?php
}
add_action( 'admin_footer', 'impreza_text_html_tag_examples_script', 999 );
add_action( 'usb_admin_footer_scripts', 'impreza_text_html_tag_examples_script', 999 );
