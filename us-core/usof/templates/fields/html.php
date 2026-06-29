<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Theme Options Field: Html
 *
 * Simple textarea field.
 *
 * @var   $name string Field name
 * @var   $id string Field ID
 * @var   $field array Field options
 *
 * @param $field ['title'] string Field title
 * @param $field ['description'] string Field title
 * @param $field ['encoded'] bool Value is encoded
 *
 * @var $value string Current value
 */

$params = array(
	'editor' => FALSE,
);

// Hidden result field
$hidden_atts = array(
	'name' => $name,
	'type' => 'hidden',
	'value' => $value,
);

if ( ! empty( $field['encoded'] ) ) {
	$params['encoded'] = 1;
}

// Field for editing in WPBakery
// Via the `wpb_vc_param_value` class WPBakery receives the final value
if ( isset( $field['us_vc_field'] ) ) {
	$hidden_atts['class'] = 'wpb_vc_param_value';
	$params['encoded'] = 1;
}

if ( function_exists( 'wp_enqueue_code_editor' ) ) {
	$params['editor'] = wp_enqueue_code_editor( array(
		'type' => 'text/html',
		/**
		 * @link https://codemirror.net/doc/manual.html#config
		 */
		'codemirror' => array(
			'viewportMargin' => 100,
			'lineNumbers' => FALSE,
			'lineWrapping' => TRUE,
			'autoRefresh' => TRUE,
		)
	) );
}

$input_html = '<textarea' . us_implode_atts( $hidden_atts ) . '>' . esc_textarea( $value ) . '</textarea>';

$output = '<div class="usof-form-row-control-params"' . us_pass_data_to_js( $params ) . '></div>';
if ( $dynamic_values = us_arr_path( $field, 'dynamic_values' ) ) {
	$popup_id = us_uniqid( 6 );

	$output .= $input_html;
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

	$textarea_dynamic_values = us_config( 'dynamic-values.for_textarea' );

	// Append dynamic values from the element config if defined
	if ( is_array( $dynamic_values ) ) {
		$textarea_dynamic_values = array_merge( $textarea_dynamic_values, $dynamic_values );
	}

	// Add popup to output
	$output .= us_get_template( 'usof/templates/popup_dynamic_values', array(
		'id' => $popup_id,
		'group_buttons' => (array) apply_filters( 'us_html_dynamic_values', $textarea_dynamic_values ),
	) );

} else {
	$output .= $input_html;
}
echo $output;
