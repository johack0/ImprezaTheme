<?php
/**
 * Plugin Name: Impreza - Separator Height Examples
 * Description: Aggiunge valori cliccabili inline al campo Height del widget Separator.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'US_SEPARATOR_HEIGHT_EXAMPLES', array( '0', '8px', '16px', '24px', '32px', '36px', '40px', '48px', '56px', '64px', '72px', '80px', '88px', '96px', '104px' ) );

function us_get_separator_height_examples_html() {
	$examples = array();

	foreach ( US_SEPARATOR_HEIGHT_EXAMPLES as $value ) {
		$examples[] = '<span class="usof-example">' . esc_html( $value ) . '</span>';
	}

	return implode( ', ', $examples );
}

/**
 * Mostra gli esempi del separatore sulla stessa riga della descrizione.
 *
 * @param string $description  Descrizione corrente del campo.
 * @param string $examples_html HTML degli esempi.
 * @return string Descrizione aggiornata.
 */
function us_separator_height_examples_inline_description( $description, $examples_html ) {
	$description = trim( (string) $description );

	if ( $description === '' ) {
		return __( 'Exa:', 'us' ) . ' ' . $examples_html;
	}

	if ( strpos( $description, 'usof-example' ) !== false ) {
		$description = preg_replace( '/\s*<span class="usof-example">[^<]*<\/span>\s*,?/', '', $description );
		$description = rtrim( trim( $description ), ':,' );
	}

	return $description . ': ' . $examples_html;
}

add_filter( 'us_config_elements/separator', static function ( $config ) {
	if ( empty( $config['params'] ) || ! is_array( $config['params'] ) ) {
		return $config;
	}

	$examples_html = us_get_separator_height_examples_html();

	foreach ( array( 'height', 'breakpoint_1_height', 'breakpoint_2_height' ) as $param_name ) {
		if ( ! isset( $config['params'][ $param_name ] ) ) {
			continue;
		}

		$current_desc = isset( $config['params'][ $param_name ]['description'] )
			? $config['params'][ $param_name ]['description']
			: '';

		$config['params'][ $param_name ]['description'] = us_separator_height_examples_inline_description( $current_desc, $examples_html );
	}

	return $config;
} );

function us_separator_height_examples_script() {
	$allowed_json = wp_json_encode( US_SEPARATOR_HEIGHT_EXAMPLES );
	?>
	<script type="text/javascript">
	jQuery( document ).ready( function( $ ) {
		var allowedValues = <?php echo $allowed_json; ?>;
		var selectors = '.usof-form-row[data-name="height"] .usof-example,'
			+ '.usof-form-row[data-name="breakpoint_1_height"] .usof-example,'
			+ '.usof-form-row[data-name="breakpoint_2_height"] .usof-example';

		$( document ).on( 'click', selectors, function( e ) {
			var $example = $( this );
			var exampleText = $example.text().trim();

			if ( allowedValues.indexOf( exampleText ) === -1 ) {
				return;
			}

			e.preventDefault();
			e.stopPropagation();

			var $row = $example.closest( '.usof-form-row' );
			if ( ! $row.length ) {
				return;
			}

			var $input = $row.find( 'input[type="text"]' );
			if ( ! $input.length ) {
				return;
			}

			$input.val( exampleText );
			$input.trigger( 'change' );

			var usofField = $row.data( 'usofField' );
			if ( usofField && typeof usofField.setValue === 'function' ) {
				try {
					usofField.setValue( exampleText, false );
				} catch ( err ) {
					// Ignora errori JS.
				}
			}
		} );
	} );
	</script>
	<?php
}

add_action( 'admin_footer', 'us_separator_height_examples_script', 999 );
add_action( 'usb_admin_footer_scripts', 'us_separator_height_examples_script', 999 );
