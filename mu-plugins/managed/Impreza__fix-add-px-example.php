<?php
/**
 * Plugin Name: Impreza - Add PX Examples
 * Description: Aggiunge "px" come esempio cliccabile separato quando ci sono esempi con unità (px, rem, vh, ecc.)
 * Version: 1.0.0
 */

// Prevenire accesso diretto
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Funzione helper per aggiungere "px" agli esempi solo quando ci sono unità diverse da px
 * Aggiunge "px" solo quando ci sono esempi nel formato: 30px, 2rem, 5vh (con unità diverse)
 */
function us_add_px_example( $description ) {
	if ( empty( $description ) || ! is_string( $description ) ) {
		return $description;
	}
	
	// Verifica se la descrizione contiene esempi con unità CSS diverse da px
	// Pattern per trovare esempi con unità: numero seguito da rem, vh, vw, em, %
	// Deve contenere almeno un esempio con unità diversa da px
	if ( ! preg_match( '/<span class="usof-example">[^<]*\d+(rem|vh|vw|em|%)[^<]*<\/span>/i', $description ) ) {
		return $description;
	}
	
	// Verifica se "px" è già presente come esempio standalone (non seguito da numero)
	if ( preg_match( '/<span class="usof-example">\s*px\s*<\/span>/i', $description ) ) {
		return $description;
	}
	
	// Trova l'ultimo span con classe usof-example e aggiungi ", px" dopo di esso
	$px_example = ', <span class="usof-example">px</span>';
	
	// Cerca l'ultimo esempio (l'ultimo span con usof-example)
	// Pattern: trova l'ultimo </span> che chiude un esempio
	if ( preg_match( '/<span class="usof-example">[^<]+<\/span>/', $description, $matches, PREG_OFFSET_CAPTURE ) ) {
		// Trova tutte le occorrenze per ottenere l'ultima
		preg_match_all( '/<span class="usof-example">[^<]+<\/span>/', $description, $all_matches, PREG_OFFSET_CAPTURE );
		
		if ( ! empty( $all_matches[0] ) ) {
			// Prendi l'ultima occorrenza
			$last_match = end( $all_matches[0] );
			$last_pos = $last_match[1] + strlen( $last_match[0] );
			
			// Inserisci ", px" dopo l'ultimo esempio
			$description = substr_replace( $description, $px_example, $last_pos, 0 );
		}
	}
	
	return $description;
}

/**
 * Modifica le descrizioni negli elementi misc (desc_height, desc_width, desc_padding, desc_margin)
 */
add_filter( 'us_config_elements_misc', function( $misc ) {
	// Lista delle chiavi da modificare
	$keys_to_modify = array( 'desc_height', 'desc_width', 'desc_padding', 'desc_margin' );
	
	foreach ( $keys_to_modify as $key ) {
		if ( isset( $misc[ $key ] ) ) {
			$misc[ $key ] = us_add_px_example( $misc[ $key ] );
		}
	}
	
	return $misc;
} );

/**
 * Modifica le descrizioni negli elementi che hanno esempi con unità
 * Questo copre elementi come separator e altri che hanno descrizioni hardcoded
 */
add_filter( 'us_config_elements/separator', function( $config ) {
	if ( isset( $config['params'] ) && is_array( $config['params'] ) ) {
		foreach ( $config['params'] as $key => $param ) {
			if ( isset( $param['description'] ) && is_string( $param['description'] ) ) {
				$config['params'][ $key ]['description'] = us_add_px_example( $param['description'] );
			}
		}
	}
	return $config;
} );

/**
 * Modifica le descrizioni nelle design options che usano esempi con unità
 */
add_filter( 'us_config_elements_design_options', function( $design_options ) {
	if ( isset( $design_options['css']['params'] ) && is_array( $design_options['css']['params'] ) ) {
		// Lista dei parametri che potrebbero avere esempi con unità
		$params_with_units = array( 'height', 'width', 'min-height', 'max-height', 'min-width', 'max-width' );
		
		foreach ( $params_with_units as $param_name ) {
			if ( isset( $design_options['css']['params'][ $param_name ]['description'] ) ) {
				$design_options['css']['params'][ $param_name ]['description'] = us_add_px_example(
					$design_options['css']['params'][ $param_name ]['description']
				);
			}
		}
	}
	
	return $design_options;
} );

/**
 * Aggiunge il supporto JavaScript per aggiungere "px" al testo esistente nell'input
 */
function us_add_px_example_script() {
	?>
	<script type="text/javascript">
	jQuery( document ).ready( function( $ ) {
		// Intercetta il click in capture phase, prima del handler standard di Impreza.
		document.addEventListener( 'click', function( e ) {
			var target = e.target && e.target.nodeType === 1 ? e.target : e.target.parentElement;
			var example = target && target.closest ? target.closest( '.usof-example' ) : null;
			if ( ! example ) {
				return;
			}

			var $example = $( example );
			var exampleText = $example.text().trim();
			
			// Se non è esattamente "px" (standalone), esci
			// Questo gestisce solo l'esempio "px" aggiunto dal plugin, non esempi tipo "30px"
			if ( exampleText !== 'px' ) {
				return;
			}
			
			// Verifica che ci siano altri esempi con unità diverse nella stessa descrizione
			// per assicurarsi che questo "px" sia stato aggiunto dal plugin
			var $row = $example.closest( '.usof-form-row' );
			if ( ! $row.length ) {
				return;
			}
			
			var $desc = $row.find( '.usof-form-row-desc' );
			if ( ! $desc.length ) {
				return;
			}
			
			// Verifica che ci siano esempi con unità diverse da px (rem, vh, vw, em, %)
			var descText = $desc.html() || '';
			if ( ! /<span class="usof-example">[^<]*\d+(rem|vh|vw|em|%)[^<]*<\/span>/i.test( descText ) ) {
				return;
			}
			
			e.preventDefault();
			e.stopPropagation();
			if ( typeof e.stopImmediatePropagation === 'function' ) {
				e.stopImmediatePropagation();
			}
			
			// Trova l'input di testo
			var $input = $row.find( 'input[type="text"]' );
			if ( ! $input.length ) {
				return;
			}
			
			// Ottieni il valore corrente dell'input
			var currentValue = $input.val() || '';
			currentValue = currentValue.trim();
			
			// Se il valore termina già con "px", non fare nulla
			if ( currentValue.toLowerCase().endsWith( 'px' ) ) {
				return;
			}
			
			// Aggiungi "px" al valore esistente
			var newValue = currentValue + 'px';
			
			// Imposta il nuovo valore nell'input
			$input.val( newValue ).trigger( 'input' ).trigger( 'change' );
			
			// Se il campo ha un handler USOF, aggiorna anche quello
			var usofField = $row.data( 'usofField' );
			if ( usofField && typeof usofField.setValue === 'function' ) {
				try {
					usofField.setValue( newValue, false );
				} catch( e ) {
					// Ignora errori
				}
			}
		}, true );
	} );
	</script>
	<?php
}

// Aggiungi lo script nell'admin normale
add_action( 'admin_footer', 'us_add_px_example_script', 999 );

// Aggiungi lo script anche nel builder
add_action( 'usb_admin_footer_scripts', 'us_add_px_example_script', 999 );
