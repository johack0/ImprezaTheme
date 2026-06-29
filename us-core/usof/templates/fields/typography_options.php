<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Typography Options
 *
 * @var $name string Field name
 * @var $id string Field ID
 * @var $field array Field options
 *
 * @var $value string
 */

// Input atts
$input_atts = array(
	'type' => 'hidden',
	'name' => $name,
	'value' => $value,
);

// Implement support for responsive values at the global level
if ( is_array( $input_atts['value'] ) ) {
	$input_atts['value'] = rawurlencode( json_encode( $value ) );
}

// Output the HTML
echo '<input' . us_implode_atts( $input_atts ) . '>';
if ( isset( $field['fields'] ) AND is_array( $field['fields'] ) ) {
	foreach ( $field['fields'] as $child_field_name => $child_field ) {
		echo us_get_template( 'usof/templates/field', array(
			'context' => $context,
			'field' => $child_field,
			'id' => sprintf( '%s_%s_%s', $context, $child_field['type'], $child_field_name ),
			'name' => $child_field_name,
		) );
	}
}

// Get font weights and styles (built once per request)
global $us_google_fonts;
if ( empty( $us_google_fonts ) ) {
	foreach ( us_config( 'google-fonts' ) as $font_family => $font_options ) {
		$us_google_fonts[ $font_family ] = ! empty( $font_options[ 'variants' ] )
			? implode( ',', $font_options[ 'variants' ] )
			: '';
	}
}

// Export font data to the global data object
echo '<script>
	$usof = window.$usof || { _$$data: {} };
	$usof._$$data.googleFonts = \''. json_encode( $us_google_fonts ) .'\';
	$usof._$$data.googleFontsAxes = \''. json_encode( (object) us_get_google_fonts_axes() ) .'\';
	$usof._$$data.uploadedFonts = \''. json_encode( (object) us_get_uploaded_fonts_data() ) .'\';
	$usof.googlefontEndpoint = "' . sprintf( '%s://fonts.googleapis.com/css', is_ssl() ? 'https' : 'http' ) . '";
</script>';
