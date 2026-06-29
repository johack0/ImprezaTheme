<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Type: Checkbox
 */

if ( empty( $item_values ) ) {
	return;
}

// Enqueue datepicker script
us_enqueue_datepicker_script();

// Set values for cache
if ( $prepare_html_for_caching AND $selected_values ) {
	foreach ( explode( ',', $selected_values ) as $i => $_value ) {
		$item_values[ $i ]['value'] = $_value;
	}
}

$output = '';

foreach ( $item_values as $item_value ) {

	$_value = $item_value['value'] ?? '';
	$_name = $item_value['name'] ?? $item_name;
	$_label = $item_value['label'] ?? $_value;

	$datepicker_options = array(
		'changeMonth' => TRUE,
		'changeYear' => TRUE,
	);
	$datepicker_options = apply_filters( 'us_list_filter_datepicker_options', $datepicker_options, $_name );

	$_atts = array(
		'class' => 'w-filter-item-value for_' . $_name,
		'onclick' => us_pass_data_to_js( $datepicker_options, /*onclick*/FALSE ),
	);

	$input_atts = array(
		'type' => 'text',
		'placeholder' => apply_filters( 'us_list_filter_value_label', $_label, $item_value, $item_name ),
		'name' => $_name,
		'value' => $_value,
		'inputmode' => 'none', // remove keyboard appearance on focus for mobiles
		'data-date-format' => $date_format ?? '',
	);

	$output .= '<div' . us_implode_atts( $_atts ) . '>';
	$output .= '<input' . us_implode_atts( $input_atts ) . '>';
	$output .= '</div>'; // w-filter-item-value
}

echo $output;
