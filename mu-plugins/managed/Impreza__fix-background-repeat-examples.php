<?php
/**
 * Plugin Name: Impreza - Background Repeat Examples
 * Description: Aggiunge esempi cliccabili per il campo Background Repeat nelle opzioni di design, simile a Background Size
 * Version: 1.0.0
 */

// Prevenire accesso diretto
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aggiunge la descrizione con esempi per background-repeat
 * Gli esempi corrispondono esattamente alle opzioni del menu a tendina
 */
add_filter( 'us_config_elements_misc', function( $misc ) {
	// Aggiungi la descrizione per background-repeat se non esiste già
	// Ordine: no-repeat (nessuno) per primo come suggerito, poi le altre opzioni del menu
	if ( ! isset( $misc['desc_bg_repeat'] ) ) {
		$misc['desc_bg_repeat'] = __( 'Examples:', 'us' ) . ' <span class="usof-example" data-value="no-repeat">' . us_translate( 'None' ) . '</span>, <span class="usof-example" data-value="repeat">' . __( 'Repeat', 'us' ) . '</span>, <span class="usof-example" data-value="repeat-x">' . __( 'Horizontally', 'us' ) . '</span>, <span class="usof-example" data-value="repeat-y">' . __( 'Vertically', 'us' ) . '</span>';
	}
	return $misc;
} );

/**
 * Aggiunge la descrizione con esempi cliccabili a background-repeat mantenendo il menu a tendina
 */
add_filter( 'us_config_elements_design_options', function( $design_options ) {
	// Verifica che esista la configurazione css.params
	if ( isset( $design_options['css']['params']['background-repeat'] ) ) {
		$misc = us_config( 'elements_misc' );
		
		// Mantieni il tipo select e aggiungi solo la descrizione con esempi
		// Gli esempi corrispondono esattamente alle opzioni del menu a tendina
		$design_options['css']['params']['background-repeat']['description'] = isset( $misc['desc_bg_repeat'] ) 
			? $misc['desc_bg_repeat'] 
			: __( 'Examples:', 'us' ) . ' <span class="usof-example" data-value="no-repeat">' . us_translate( 'None' ) . '</span>, <span class="usof-example" data-value="repeat">' . __( 'Repeat', 'us' ) . '</span>, <span class="usof-example" data-value="repeat-x">' . __( 'Horizontally', 'us' ) . '</span>, <span class="usof-example" data-value="repeat-y">' . __( 'Vertically', 'us' ) . '</span>';
	}
	
	return $design_options;
} );

/**
 * Aggiunge il supporto per gli esempi cliccabili nei campi select
 */
function us_background_repeat_examples_script() {
	?>
	<script type="text/javascript">
	jQuery( document ).ready( function( $ ) {
		// Funzione helper per impostare il valore nel campo select
		function setBackgroundRepeatValue( $row, value ) {
			var $select = $row.find( 'select' );
			
			if ( ! $select.length ) {
				return false;
			}
			
			// Verifica che il valore esista nelle opzioni del select
			var $option = $select.find( 'option[value="' + value + '"]' );
			if ( ! $option.length ) {
				return false;
			}
			
			// Ottieni il campo USOF dalla row
			var usofField = $row.data( 'usofField' );
			
			// Metodo 1: Se il campo USOF esiste e ha il metodo setValue, usalo
			if ( usofField && typeof usofField.setValue === 'function' ) {
				try {
					usofField.setValue( value, false );
					return true;
				} catch( e ) {
					console.warn( 'Errore impostazione valore campo USOF:', e );
				}
			}
			
			// Metodo 2: Imposta il valore nel select e triggera il change
			$select.val( value );
			
			// Triggera l'evento change che dovrebbe attivare _changeSelect del campo
			$select.trigger( 'change' );
			
			// Se il campo USOF non esiste ancora, prova a inizializzarlo
			if ( ! usofField && typeof window.$usof !== 'undefined' && typeof window.$usof.field !== 'undefined' ) {
				try {
					var field = $row.usofField();
					if ( field && typeof field.setValue === 'function' ) {
						// Aspetta un momento per assicurarsi che il campo sia inizializzato
						setTimeout( function() {
							field.setValue( value, false );
						}, 50 );
					}
				} catch( e ) {
					// Ignora errori di inizializzazione
				}
			}
			
			return true;
		}
		
		// Gestisci i click sugli esempi per i campi select di background-repeat
		$( document ).on( 'click', '.usof-form-row[data-name="background-repeat"] .usof-example', function( e ) {
			e.preventDefault();
			e.stopPropagation();
			
			var $example = $( this ),
				// Usa l'attributo data-value (obbligatorio)
				value = $example.attr( 'data-value' ) || '',
				$row = $example.closest( '.usof-form-row' );
			
			if ( value ) {
				setBackgroundRepeatValue( $row, value );
			}
		} );
	} );
	</script>
	<?php
}

// Aggiungi lo script nell'admin normale
add_action( 'admin_footer', 'us_background_repeat_examples_script', 999 );

// Aggiungi lo script anche nel builder
add_action( 'usb_admin_footer_scripts', 'us_background_repeat_examples_script', 999 );
