<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Type: Checkbox
 */

if ( empty( $item_values ) ) {
	return;
}

// Get array of selected values
if ( $prepare_html_for_caching ) {
	if ( strpos( $selected_values, ',' ) !== FALSE ) {
		$selected_values = explode( ',', $selected_values );
	} else {
		$selected_values = array();
	}
}

$output = '';

foreach ( $item_values as $item_value ) {
	
	$_value = $item_value['value'] ?? $item_value;

	if ( $_value == '' ) {
		continue;
	}

	// Replace comma to escaped QUOTATION MARK, cause comma is used in URL to separate different values
	$encoded_value = rawurlencode( str_replace( ',', /*U+0201A*/'\‚', $_value ) );

	$_atts = array(
		'class' => 'w-filter-item-value' . ( $btn_class ?? '' ),
		'data-value' => $_value,
		'style' => '',
	);

	if ( $show_post_count ) {

		$post_count = $cached_post_count[ $encoded_value ] ?? '';

		if ( $prepare_html_for_caching AND empty( $post_count ) ) {
			$_atts['class'] .= ' disabled';
		}
	}

	if ( ! empty( $item_value['depth'] ) ) {
		$_atts['class'] .= ' depth_' . $item_value['depth'];
	}

	$_atts = apply_filters( 'us_list_filter_value_html_atts', $_atts, $item_value, $item_name );

	$_label = esc_html( $item_value['label'] ?? $_value );
	$_label = apply_filters( 'us_list_filter_value_label', $_label, $item_value, $item_name );

	$input_atts = array(
		'type' => 'checkbox',
		'value' => $encoded_value,
		'name' => $item_name,
	);

	if ( $prepare_html_for_caching AND in_array( $_value, $selected_values ) ) {
		$input_atts['checked'] = '';
	}

	// Add color swatch values
	if ( isset( $item_value['color_swatch'] ) ) {
		$_atts['style'] .= '--swatch-color:' . us_get_color( $item_value['color_swatch'], TRUE ) . ';';
		$_atts['style'] .= '--swatch-contrast-color:' . us_get_contrast_color( $item_value['color_swatch'] ) . ';';
		$input_atts['title'] = $_label;
	}

	$output .= '<div' . us_implode_atts( $_atts ) . '>';
	$output .= '<label>';
	$output .= '<input' . us_implode_atts( $input_atts ) . '>';
	$output .= '<span class="w-filter-item-value-label">' . ( $text_before_value ?? '' ) . $_label . ( $text_after_value ?? '' ) . '</span>';

	if ( $show_post_count ) {
		$output .= '<span class="w-filter-item-value-amount">' . ( $post_count ?: '' ) . '</span>'; // set via JS
	}

	$output .= '</label>';
	$output .= '</div>'; // w-filter-item-value
}

echo $output;
