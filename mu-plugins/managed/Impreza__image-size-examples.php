<?php
/**
 * Plugin Name: Impreza - Image Size Examples
 * Description: Mostra solo "Dimensione reale" accanto alla descrizione della select "Image Size" del widget Image.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Genera il link cliccabile per impostare la dimensione reale dell'immagine.
 *
 * @param array $options Opzioni del campo size.
 * @return string HTML degli esempi.
 */
function impreza_image_size_examples_html( $options ) {
	$label = isset( $options['full'] )
		? $options['full']
		: ( function_exists( 'us_translate' ) ? us_translate( 'Full Size' ) : __( 'Full Size', 'us' ) );

	return '<span class="usof-example impreza-image-size-example" data-value="full">' . esc_html( $label ) . '</span>';
}

/**
 * Aggiunge accanto alla descrizione Image Size il solo valore "Dimensione reale".
 * Non modifica l'ordine originale dei campi del widget.
 *
 * @param array $config Configurazione dell'elemento Image.
 * @return array Configurazione aggiornata.
 */
function impreza_add_size_examples_to_image_element( $config ) {
	if (
		! isset( $config['params']['size'] )
		|| ! is_array( $config['params']['size'] )
	) {
		return $config;
	}

	$options = isset( $config['params']['size']['options'] ) && is_array( $config['params']['size']['options'] )
		? $config['params']['size']['options']
		: array();

	if ( empty( $options ) && function_exists( 'us_get_image_sizes_list' ) ) {
		$options = us_get_image_sizes_list();
	}

	$examples = impreza_image_size_examples_html( $options );
	if ( $examples === '' ) {
		return $config;
	}

	$current_description = isset( $config['params']['size']['description'] )
		? trim( (string) $config['params']['size']['description'] )
		: '';

	if ( strpos( $current_description, 'impreza-image-size-example' ) !== false ) {
		return $config;
	}

	$config['params']['size']['description'] = $current_description === ''
		? $examples
		: $current_description . ' ' . $examples;

	return $config;
}
add_filter( 'us_config_elements/image', 'impreza_add_size_examples_to_image_element', 20 );

/**
 * Rende cliccabili gli esempi del campo Image Size.
 */
function impreza_image_size_examples_script() {
	?>
	<script type="text/javascript">
	jQuery( document ).ready( function( $ ) {
		$( document ).on( 'click', '.impreza-image-size-example[data-value]', function( e ) {
			e.preventDefault();
			e.stopPropagation();

			var $example = $( this );
			var value = $example.attr( 'data-value' );
			var $row = $example.closest( '.usof-form-row[data-name="size"]' );
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
add_action( 'admin_footer', 'impreza_image_size_examples_script', 999 );
add_action( 'usb_admin_footer_scripts', 'impreza_image_size_examples_script', 999 );
