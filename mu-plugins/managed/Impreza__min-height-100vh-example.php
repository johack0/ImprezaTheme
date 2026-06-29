<?php
/**
 * Plugin Name: Impreza - Min Height 100vh Example
 * Description: Aggiunge "100vh" come esempio cliccabile al campo "Min Height" nelle Design Options.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aggiunge l'esempio 100vh alla descrizione del campo min-height.
 *
 * @param string $description Descrizione corrente.
 * @return string Descrizione aggiornata.
 */
function impreza_add_100vh_to_min_height_description( $description ) {
	$description = trim( (string) $description );

	if ( strpos( $description, '<span class="usof-example">100vh</span>' ) !== false ) {
		return $description;
	}

	if ( $description === '' ) {
		return __( 'Examples:', 'us' ) . ' <span class="usof-example">100vh</span>';
	}

	if ( strpos( $description, 'usof-example' ) !== false ) {
		return $description . ', <span class="usof-example">100vh</span>';
	}

	return $description . ': <span class="usof-example">100vh</span>';
}

/**
 * Applica l'esempio solo al parametro CSS min-height.
 *
 * @param array $design_options Configurazione corrente delle Design Options.
 * @return array Configurazione aggiornata.
 */
function impreza_add_100vh_min_height_design_option( $design_options ) {
	if (
		! isset( $design_options['css']['params']['min-height'] )
		|| ! is_array( $design_options['css']['params']['min-height'] )
	) {
		return $design_options;
	}

	$current_description = isset( $design_options['css']['params']['min-height']['description'] )
		? $design_options['css']['params']['min-height']['description']
		: '';

	$design_options['css']['params']['min-height']['description'] = impreza_add_100vh_to_min_height_description( $current_description );

	return $design_options;
}
add_filter( 'us_config_elements_design_options', 'impreza_add_100vh_min_height_design_option', 40 );

/**
 * Rende cliccabile l'esempio 100vh per il campo min-height.
 */
function impreza_min_height_100vh_example_script() {
	?>
	<script type="text/javascript">
	jQuery( document ).ready( function( $ ) {
		$( document ).on( 'click', '.usof-form-row[data-name="min-height"] .usof-example', function( e ) {
			var $example = $( this );
			var value = $example.text().trim();

			if ( value !== '100vh' ) {
				return;
			}

			e.preventDefault();
			e.stopPropagation();

			var $row = $example.closest( '.usof-form-row' );
			var $input = $row.find( 'input[type="text"]' );

			if ( ! $input.length ) {
				return;
			}

			$input.val( value ).trigger( 'change' );

			var usofField = $row.data( 'usofField' );
			if ( usofField && typeof usofField.setValue === 'function' ) {
				try {
					usofField.setValue( value, false );
				} catch ( err ) {
					// Il change sull'input basta nei contesti in cui USOF non espone setValue.
				}
			}
		} );
	} );
	</script>
	<?php
}
add_action( 'admin_footer', 'impreza_min_height_100vh_example_script', 999 );
add_action( 'usb_admin_footer_scripts', 'impreza_min_height_100vh_example_script', 999 );
