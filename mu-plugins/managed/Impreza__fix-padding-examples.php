<?php
/**
 * Plugin Name: Impreza - Padding Examples
 * Description: Aggiunge "0", "unset", "16px", "24px" e "48px" come esempi cliccabili inline per i campi Padding nelle opzioni di design.
 * Version: 1.0.0
 */

// Prevenire accesso diretto
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array<string> Valori esempio cliccabili per i campi padding */
define( 'US_PADDING_EXAMPLES', array( '0', 'unset', '16px', '24px', '48px' ) );

/**
 * Genera gli span usof-example per i valori padding supportati.
 *
 * @return string HTML degli esempi.
 */
function us_get_padding_examples_html() {
	$examples = array();
	foreach ( US_PADDING_EXAMPLES as $value ) {
		$examples[] = '<span class="usof-example">' . esc_html( $value ) . '</span>';
	}

	return implode( ', ', $examples );
}

/**
 * Mostra gli esempi sulla stessa riga dell'etichetta direzionale.
 *
 * @param string $description  Descrizione corrente del campo.
 * @param string $examples_html HTML degli esempi.
 * @return string Descrizione aggiornata.
 */
function us_padding_examples_inline_description( $description, $examples_html ) {
	$description = trim( (string) $description );

	if ( $description === '' ) {
		return __( 'Exa:', 'us' ) . ' ' . $examples_html;
	}

	if ( strpos( $description, '<span class="usof-example">0</span>' ) !== false ) {
		return $description;
	}

	if ( strpos( $description, 'usof-example' ) !== false ) {
		return $description . ', ' . $examples_html;
	}

	return $description . ': ' . $examples_html;
}

/**
 * Aggiunge gli esempi cliccabili inline per i campi padding.
 */
add_filter(
	'us_config_elements_design_options',
	function( $design_options ) {
		if ( ! isset( $design_options['css']['params'] ) || ! is_array( $design_options['css']['params'] ) ) {
			return $design_options;
		}

		// Nomi dei parametri di padding usati dal tema.
		$padding_params = array(
			'padding',
			'padding-top',
			'padding-right',
			'padding-bottom',
			'padding-left',
		);

		$examples_html = us_get_padding_examples_html();

		foreach ( $padding_params as $param_name ) {
			if ( ! isset( $design_options['css']['params'][ $param_name ] ) ) {
				continue;
			}

			$current_desc = isset( $design_options['css']['params'][ $param_name ]['description'] )
				? $design_options['css']['params'][ $param_name ]['description']
				: '';

			$design_options['css']['params'][ $param_name ]['description'] = us_padding_examples_inline_description( $current_desc, $examples_html );
		}

		return $design_options;
	}
);

/**
 * Aggiunge il supporto JavaScript per impostare il valore quando si clicca su un esempio di padding.
 */
function us_padding_examples_script() {
	$allowed_values = US_PADDING_EXAMPLES;
	$allowed_json   = wp_json_encode( $allowed_values );
	?>
	<script type="text/javascript">
	jQuery( document ).ready( function( $ ) {
		var allowedValues = <?php echo $allowed_json; ?>;
		var paddingSelectors = '.usof-form-row[data-name="padding"] .usof-example,'
			+ '.usof-form-row[data-name="padding-top"] .usof-example,'
			+ '.usof-form-row[data-name="padding-right"] .usof-example,'
			+ '.usof-form-row[data-name="padding-bottom"] .usof-example,'
			+ '.usof-form-row[data-name="padding-left"] .usof-example';

		$( document ).on( 'click', paddingSelectors, function( e ) {
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

// Aggiungi lo script nell'admin normale.
add_action( 'admin_footer', 'us_padding_examples_script', 999 );

// Aggiungi lo script anche nel builder.
add_action( 'usb_admin_footer_scripts', 'us_padding_examples_script', 999 );

