<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Type: Radio Buttons
 */

if ( empty( $item_values ) ) {
	return;
}

$output = '';

foreach ( $item_values as $i => $item_value ) {

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

		if ( $prepare_html_for_caching AND $_value != '*' AND empty( $post_count ) ) {
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
		'type' => 'radio',
		'value' => $encoded_value,
		'name' => $item_name,
	);

	if ( $prepare_html_for_caching AND $_value == $selected_values ) {
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

	$_before_label = $text_before_value ?? '';
	$_after_label = $text_after_value ?? '';

	// The "Any" value is checked by default and doesn't have text before/after value
	if ( $_value == '*' ) {
		$input_atts['checked'] = 'checked';

		$_before_label = '';
		$_after_label = '';
	}

	$output .= '<input' . us_implode_atts( $input_atts ) . '>';
	$output .= '<span class="w-filter-item-value-label">' . $_before_label . $_label . $_after_label . '</span>';

	if ( $show_post_count ) {
		$output .= '<span class="w-filter-item-value-amount">' . ( $post_count ?: '' ) . '</span>'; // set via JS
	}

	$output .= '</label>';
	$output .= '</div>'; // w-filter-item-value
}

echo $output;
