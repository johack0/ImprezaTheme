<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Type: Range slider
 */

if ( empty( $item_values ) ) {
	return;
}

// Enqueue ui-slider script
us_enqueue_ui_slider_script();

// Get values from URL param
if ( $values_from_url = (string) us_arr_path( $_GET, sprintf( '_%s|between', $item_name ) ) ) {
	$values_from_url = explode( '-', $values_from_url );
	$values_from_url = array_map( 'floatval', $values_from_url );
} else {
	$values_from_url = array();
}

$min = (float) $item_values['min_value'];
$max = (float) $item_values['max_value'];
$step = abs( (float) $item_values['step_size'] );

// Set values for cache
if ( $prepare_html_for_caching AND $cached_post_count ) {
	$min = $cached_post_count[0] ?? $min;
	$max = $cached_post_count[1] ?? $max;
	$values_from_url = $cached_post_count;
}
if ( $prepare_html_for_caching AND $selected_values ) {
	$values_from_url = explode( '-', $selected_values );
}

$input_atts = array(
	'type' => 'hidden',
	'name' => $item_name,
	'min' => $min,
	'max' => $max,
	'value' => implode( '-', $values_from_url ),
);
$output = '<input' . us_implode_atts( $input_atts ) . '>';

// https://api.jqueryui.com/slider/
$slider_opts = array(
	'slider' => array(
		'min' => $min,
		'max' => $max,
		'step' => $step ?: 1,
		'values' => $values_from_url,
	),
);

// Text before/after value for slider options
$text_before_value = $text_before_value ?? '';
$text_after_value = $text_after_value ?? '';

if ( $text_before_value OR $text_after_value ) {
	$slider_opts['unitFormat'] = $text_before_value . '%d' . $text_after_value;
}

$output .= '<div class="ui-slider"' . us_pass_data_to_js( apply_filters( 'us_list_filter_range_slider_options', $slider_opts, $item_name ) ) . '>';
$output .= '<div class="ui-slider-handle" title="' . esc_attr( __( 'Min', 'us' ) ) . '"></div>';
$output .= '<div class="ui-slider-handle" title="' . esc_attr( __( 'Max', 'us' ) ) . '"></div>';
$output .= '</div>'; // ui-slider

$min_value_label = $text_before_value . ( $values_from_url[0] ?? $min ) . $text_after_value;
$max_value_label = $text_before_value . ( $values_from_url[1] ?? $max ) . $text_after_value;

$output .= '<div class="w-filter-item-slider-result">';
$output .= '<div class="for_min_value">' . apply_filters( 'us_list_filter_value_label', $min_value_label, $min_value_label, $item_name ) . '</div>';
$output .= '<div class="for_max_value">' . apply_filters( 'us_list_filter_value_label', $max_value_label, $max_value_label, $item_name ) . '</div>';
$output .= '</div>'; // w-filter-item-slider-result

echo $output;
