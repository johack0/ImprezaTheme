<?php
/**
 * Plugin Name: Impreza - Animation From Bottom
 * Description: Aggiunge un esempio cliccabile "Apparizione dal basso" per selezionare rapidamente l'animazione dal basso
 * Version: 1.0.0
 */

// Prevenire accesso diretto
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aggiunge l'esempio cliccabile "Apparizione dal basso" alla descrizione del campo animation-name
 */
add_filter( 'us_config_elements_design_options', function( $design_options ) {
	if ( ! isset( $design_options['css']['params']['animation-name'] ) ) {
		return $design_options;
	}

	$param = &$design_options['css']['params']['animation-name'];
	$current_desc = isset( $param['description'] ) ? $param['description'] : '';

	$clickable = '<span class="usof-example" data-value="afb">' . esc_html( __( 'Appear From Bottom', 'us' ) ) . '</span>';

	if ( strpos( $current_desc, 'data-value="afb"' ) === false ) {
		$param['description'] = $current_desc . ( $current_desc ? '<br>' : '' ) . __( 'Examples:', 'us' ) . ' ' . $clickable;
	}

	return $design_options;
} );

/**
 * Script per gestire il click sull'esempio e selezionare "Appear From Bottom" (afb)
 */
function us_animation_from_bottom_script() {
	?>
	<script type="text/javascript">
	jQuery( document ).ready( function( $ ) {
		function setAnimationValue( $row, value ) {
			var $select = $row.find( 'select' );
			if ( ! $select.length ) return false;

			var $option = $select.find( 'option[value="' + value + '"]' );
			if ( ! $option.length ) return false;

			var usofField = $row.data( 'usofField' );
			if ( usofField && typeof usofField.setValue === 'function' ) {
				try {
					usofField.setValue( value, false );
					return true;
				} catch( e ) {}
			}

			$select.val( value ).trigger( 'change' );
			return true;
		}

		$( document ).on( 'click', '.usof-form-row[data-name="animation-name"] .usof-example[data-value="afb"]', function( e ) {
			e.preventDefault();
			e.stopPropagation();
			var $row = $( this ).closest( '.usof-form-row' );
			setAnimationValue( $row, 'afb' );
		} );
	} );
	</script>
	<?php
}

add_action( 'admin_footer', 'us_animation_from_bottom_script', 999 );
add_action( 'usb_admin_footer_scripts', 'us_animation_from_bottom_script', 999 );
