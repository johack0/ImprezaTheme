<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Type: Range input
 */

if ( empty( $item_values ) ) {
	return;
}

// Needed for number formats (price etc.)
$range_opts = array();

// Text before/after value
$text_before_value = $text_before_value ?? '';
$text_after_value = $text_after_value ?? '';

if ( $text_before_value OR $text_after_value ) {
	$range_opts['unitFormat'] = $text_before_value . '%d' . $text_after_value;
}

$min_value = $text_before_value . $item_values['min_value'] . $text_after_value;
$max_value = $text_before_value . $item_values['max_value'] . $text_after_value;

$values = array();

// Set values for cache
if ( $prepare_html_for_caching AND $selected_values ) {
	$values = explode( '-', $selected_values );
}

$min_input = array(
	'type' => 'text',
	'class' => 'w-filter-item-value for_min_value',
	'inputmode' => 'decimal',
	'aria-label' => __( 'Min', 'us' ),
	'placeholder' => apply_filters( 'us_list_filter_value_label', $min_value, $min_value, $item_name ),
	'data-value' => (float) $min_value,
	'value' => $values[0] ?? '',
);
echo '<input ' . us_implode_atts( $min_input ) . '>';

$max_input = array(
	'type' => 'text',
	'class' => 'w-filter-item-value for_max_value',
	'inputmode' => 'decimal',
	'aria-label' => __( 'Max', 'us' ),
	'placeholder' => apply_filters( 'us_list_filter_value_label', $max_value, $max_value, $item_name ),
	'data-value' => (float) $max_value,
	'value' => $values[1] ?? '',
);
echo '<input ' . us_implode_atts( $max_input ) . '>';

// Number format options to pass to JS and apply via AJAX.
if ( ! empty( $show_post_count ) OR $range_opts ) {
	$onclick_attr = us_pass_data_to_js( apply_filters( 'us_list_filter_range_input_options', $range_opts, $item_name ) );
	echo '<div class="for_range_input_options hidden"' . $onclick_attr . '></div>';
}
