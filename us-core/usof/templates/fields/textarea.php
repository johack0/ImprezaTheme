<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Theme Options Field: Textarea
 *
 * Simple textarea field.
 *
 * @var   $name  string Field name
 * @var   $id    string Field ID
 * @var   $field array Field options
 *
 * @param $field ['title'] string Field title
 * @param $field ['description'] string Field title
 * @param $field ['placeholder'] string Field placeholder
 *
 * @var   $value string Current value
 */

$input_atts = array(
	'name' => $name,
);

if ( isset( $field['placeholder'] ) ) {
	$input_atts['placeholder'] = $field['placeholder'];
}

// Field for editing in WPBakery
// Via the `wpb_vc_param_value` class WPBakery receives the final value
if ( isset( $field['us_vc_field'] ) ) {
	$input_atts['class'] = 'wpb_vc_param_value';
}

$output = '<textarea'. us_implode_atts( $input_atts ) .'>' . esc_textarea( $value ) . '</textarea>';

if ( $dynamic_values = us_arr_path( $field, 'dynamic_values' ) ) {
	$popup_id = us_uniqid( 6 );

	$output .= '<div class="usof-form-input-group">';
	$output .= '<div class="usof-form-input-dynamic-value hidden" data-popup-show="' . esc_attr( $popup_id ) . '">';
	$output .= '<span class="usof-form-input-dynamic-value-title"></span>';
	$output .= '<button type="button" class="action_remove_dynamic_value ui-icon_close" title="' . esc_attr( us_translate( 'Remove' ) ) . '"></button>';
	$output .= '</div>'; // .usof-form-input-dynamic-value

	$output .= '<div class="usof-form-input-group-controls">';
	$show_button_atts = array(
		'class' => 'fas fa-database',
		'data-popup-show' => $popup_id,
		'title' => __( 'Select Dynamic Value', 'us' ),
		'type' => 'button',
	);
	$output .= '<button' . us_implode_atts( $show_button_atts ) . '></button>';
	$output .= '</div>'; // .usof-form-input-group-controls
	$output .= '</div>'; // .usof-form-input-group

	// Predefined text values
	$predefined_dynamic_values = array(
		'acf_types' => array(
			'text',
			'textarea',
			'wysiwyg',
		),
	);

	// Append dynamic values from the config if defined
	if ( is_array( $dynamic_values ) ) {
		$predefined_dynamic_values = array_merge( $predefined_dynamic_values, $dynamic_values );
	}

	// Add popup to output
	$output .= us_get_template( 'usof/templates/popup_dynamic_values', array(
		'id' => $popup_id,
		'group_buttons' => (array) apply_filters( 'us_textarea_dynamic_values', $predefined_dynamic_values ),
	) );

}

echo $output;
