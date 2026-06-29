<?php
/**
 * Plugin Name: Impreza - Text Extra Class Examples
 * Description: Aggiunge esempi cliccabili p16, p18, p20, p22, p24 per il campo Extra class solo nei widget di testo e blocco di testo
 * Version: 1.0.0
 */

// Prevenire accesso diretto
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aggiunge esempi p16, p18, p20, p22, p24 per el_class solo nell'elemento Text
 * Usa una priorità alta per sovrascrivere dopo che design_options_params viene incluso
 */
add_filter( 'us_config_elements/text', function( $config ) {
	return us_add_el_class_examples_to_text_element( $config );
}, 999 );

/**
 * Aggiunge esempi p16, p18, p20, p22, p24 per el_class solo nell'elemento Text Block (vc_column_text)
 * Usa una priorità alta per sovrascrivere dopo che design_options_params viene incluso
 */
add_filter( 'us_config_elements/vc_column_text', function( $config ) {
	return us_add_el_class_examples_to_text_element( $config );
}, 999 );

/**
 * Funzione helper per aggiungere esempi a el_class negli elementi di testo
 */
function us_add_el_class_examples_to_text_element( $config ) {
	if ( ! isset( $config['params'] ) || ! is_array( $config['params'] ) ) {
		return $config;
	}
	
	// Cerca el_class nei params
	// el_class può essere incluso direttamente o tramite design_options_params
	foreach ( $config['params'] as $key => $param ) {
		// Se è un array e ha il nome el_class
		if ( is_array( $param ) && isset( $param['name'] ) && $param['name'] === 'el_class' ) {
			$config['params'][ $key ]['description'] = __( 'Examples:', 'us' ) . ' <span class="usof-example">p16</span>, <span class="usof-example">p18</span>, <span class="usof-example">p20</span>, <span class="usof-example">p22</span>, <span class="usof-example">p24</span>';
			break;
		}
		// Se la chiave è direttamente 'el_class'
		elseif ( $key === 'el_class' && is_array( $param ) ) {
			$config['params'][ $key ]['description'] = __( 'Examples:', 'us' ) . ' <span class="usof-example">p16</span>, <span class="usof-example">p18</span>, <span class="usof-example">p20</span>, <span class="usof-example">p22</span>, <span class="usof-example">p24</span>';
			break;
		}
	}
	
	return $config;
}

/**
 * Aggiunge il supporto JavaScript per aggiungere dinamicamente gli esempi e gestire i click
 */
function us_text_el_class_examples_script() {
	?>
	<script type="text/javascript">
	jQuery( document ).ready( function( $ ) {
		function getElementName( $form ) {
			var elementName = $form.data( 'for' ) || '';

			if ( ! elementName ) {
				var match = ( $form.attr( 'class' ) || '' ).match( /(?:^|\s)for_([^\s]+)/ );
				if ( match ) {
					elementName = match[1];
				}
			}

			return elementName;
		}

		// Funzione per aggiungere gli esempi al campo el_class se siamo in text o vc_column_text
		function addExamplesToElClass() {
			// Trova tutti i campi el_class
			$( '.usof-form-row[data-name="el_class"]' ).each( function() {
				var $row = $( this );
				var $form = $row.closest( '.usof-form' );
				
				if ( ! $form.length ) {
					return;
				}
				
				// Verifica il nome dell'elemento
				var elementName = getElementName( $form );
				if ( elementName !== 'text' && elementName !== 'vc_column_text' ) {
					return;
				}
				
				// Verifica se gli esempi sono già stati aggiunti
				var $desc = $row.find( '.usof-form-row-desc' );
				if ( $desc.length && $desc.find( '.usof-example' ).length > 0 ) {
					// Verifica se contiene già p16, p18, p20, p22, p24
					var descHtml = $desc.html() || '';
					if ( descHtml.indexOf( 'p16' ) !== -1 || descHtml.indexOf( 'p18' ) !== -1 || descHtml.indexOf( 'p20' ) !== -1 || descHtml.indexOf( 'p22' ) !== -1 || descHtml.indexOf( 'p24' ) !== -1 ) {
						return; // Già aggiunto
					}
				}
				
				// Aggiungi gli esempi alla descrizione
				var examplesHtml = 'Examples: <span class="usof-example">p16</span>, <span class="usof-example">p18</span>, <span class="usof-example">p20</span>, <span class="usof-example">p22</span>, <span class="usof-example">p24</span>';
				
				if ( $desc.length ) {
					var currentDesc = $desc.html() || '';
					if ( currentDesc.trim() !== '' ) {
						$desc.html( currentDesc + '<br>' + examplesHtml );
					} else {
						$desc.html( examplesHtml );
					}
				} else {
					// Se non c'è descrizione, creala
					$row.find( '.usof-form-row-title' ).after( '<div class="usof-form-row-desc">' + examplesHtml + '</div>' );
				}
			} );
		}
		
		// Aggiungi gli esempi quando il DOM è pronto
		addExamplesToElClass();
		
		// Aggiungi gli esempi quando viene aperto un elemento (per il builder)
		$( document ).on( 'usb.panel.showFieldset', function() {
			setTimeout( addExamplesToElClass, 100 );
		} );
		
		// Aggiungi gli esempi quando viene caricato un elemento
		$( document ).on( 'usof.afterShow', function() {
			setTimeout( addExamplesToElClass, 100 );
		} );
		
		// Gestisci i click in capture phase, prima del handler standard di Impreza.
		document.addEventListener( 'click', function( e ) {
			var target = e.target && e.target.nodeType === 1 ? e.target : e.target.parentElement;
			var example = target && target.closest ? target.closest( '.usof-form-row[data-name="el_class"] .usof-example' ) : null;
			if ( ! example ) {
				return;
			}

			var $example = $( example );
			var exampleText = $example.text().trim();
			
			// Verifica che sia uno degli esempi che vogliamo gestire
			if ( exampleText !== 'p16' && exampleText !== 'p18' && exampleText !== 'p20' && exampleText !== 'p22' && exampleText !== 'p24' ) {
				return;
			}
			
			// Verifica che sia nell'elemento text o vc_column_text
			var $row = $example.closest( '.usof-form-row' );
			if ( ! $row.length ) {
				return;
			}
			
			// Trova il form parent per verificare l'elemento
			var $form = $row.closest( '.usof-form' );
			if ( ! $form.length ) {
				return;
			}
			
			// Verifica il nome dell'elemento dal form
			var elementName = getElementName( $form );
			if ( elementName !== 'text' && elementName !== 'vc_column_text' ) {
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
			
			// Se il valore è vuoto, imposta direttamente l'esempio
			// Se c'è già un valore, aggiungi lo spazio e l'esempio
			var newValue = '';
			if ( currentValue === '' ) {
				newValue = exampleText;
			} else {
				// Verifica se l'esempio è già presente
				var classes = currentValue.split( ' ' );
				if ( classes.indexOf( exampleText ) === -1 ) {
					newValue = currentValue + ' ' + exampleText;
				} else {
					// Se è già presente, rimuovilo (toggle)
					classes = classes.filter( function( cls ) {
						return cls !== exampleText;
					} );
					newValue = classes.join( ' ' ).trim();
				}
			}
			
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
add_action( 'admin_footer', 'us_text_el_class_examples_script', 999 );

// Aggiungi lo script anche nel builder
add_action( 'usb_admin_footer_scripts', 'us_text_el_class_examples_script', 999 );
