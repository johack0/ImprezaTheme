<?php
/**
 * Plugin Name: Impreza - Button Style Examples
 * Description: Mostra tutti gli stili disponibili sotto la select "Style" del widget Button.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Genera gli esempi cliccabili dagli stili bottone disponibili.
 *
 * @param array $options Opzioni del campo style.
 * @return string HTML degli esempi.
 */
function impreza_button_style_examples_html( $options ) {
	$examples = array();

	foreach ( (array) $options as $value => $label ) {
		if ( $value === '' ) {
			continue;
		}

		$examples[] = '<span class="usof-example impreza-button-style-example" data-value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</span>';
	}

	return implode( ', ', $examples );
}

/**
 * Aggiunge sotto la select Style del widget Button l'elenco degli stili disponibili.
 * Non modifica l'ordine originale dei campi.
 *
 * @param array $config Configurazione dell'elemento Button.
 * @return array Configurazione aggiornata.
 */
function impreza_add_style_examples_to_button_element( $config ) {
	if (
		! isset( $config['params']['style'] )
		|| ! is_array( $config['params']['style'] )
	) {
		return $config;
	}

	$options = isset( $config['params']['style']['options'] ) && is_array( $config['params']['style']['options'] )
		? $config['params']['style']['options']
		: array();

	if ( empty( $options ) && function_exists( 'us_get_btn_styles' ) ) {
		$options = us_get_btn_styles();
	}

	$examples = impreza_button_style_examples_html( $options );
	if ( $examples === '' ) {
		return $config;
	}

	$current_description = isset( $config['params']['style']['description'] )
		? trim( (string) $config['params']['style']['description'] )
		: '';

	if ( strpos( $current_description, 'impreza-button-style-example' ) !== false ) {
		return $config;
	}

	$examples_description = __( 'Examples:', 'us' ) . ' ' . $examples;

	$config['params']['style']['description'] = $current_description === ''
		? $examples_description
		: $current_description . '<br>' . $examples_description;

	return $config;
}
add_filter( 'us_config_elements/btn', 'impreza_add_style_examples_to_button_element', 20 );

/**
 * Rende cliccabili gli esempi del campo Button Style.
 */
function impreza_button_style_examples_script() {
	?>
	<script type="text/javascript">
	jQuery( document ).ready( function( $ ) {
		$( document ).on( 'click', '.impreza-button-style-example[data-value]', function( e ) {
			e.preventDefault();
			e.stopPropagation();

			var $example = $( this );
			var value = $example.attr( 'data-value' );
			var $row = $example.closest( '.usof-form-row[data-name="style"]' );
			var $select = $row.find( 'select' );
			var hasOption = false;

			if ( ! value || ! $row.length || ! $select.length ) {
				return;
			}

			$select.find( 'option' ).each( function() {
				if ( $( this ).val() === value ) {
					hasOption = true;
					return false;
				}
			} );

			if ( ! hasOption ) {
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
add_action( 'admin_footer', 'impreza_button_style_examples_script', 999 );
add_action( 'usb_admin_footer_scripts', 'impreza_button_style_examples_script', 999 );
