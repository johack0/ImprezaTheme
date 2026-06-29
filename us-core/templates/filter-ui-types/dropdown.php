<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Type: Dropdown <select>
 */

if ( empty( $item_values ) ) {
	return;
}

$select_atts = array(
	'class' => 'w-filter-item-value-select',
	'name' => $item_name,
	'aria-label' => $item_title,
);

$output = '<select' . us_implode_atts( $select_atts ) . '>';

foreach ( $item_values as $i => $item_value ) {

	$_value = $item_value['value'] ?? $item_value;

	if ( $_value == '' ) {
		continue;
	}

	// Replace comma to escaped QUOTATION MARK, cause comma is used in URL to separate different values
	$encoded_value = rawurlencode( str_replace( ',', /*U+0201A*/'\‚', $_value ) );

	$option_atts = array(
		'class' => '',
		'value' => $encoded_value,
	);

	$_label = '';

	// Prepend non-breaking spaces for visual hierarchy
	if ( ! empty( $item_value['depth'] ) ) {
		$_label .= implode( '', array_fill( 0, $item_value['depth'] - 1, html_entity_decode( '&nbsp;&nbsp;&nbsp;' ) ) );
	}

	$_label .= $item_value['label'] ?? $_value;

	if ( $show_post_count AND $_value != '*' ) {

		$option_atts['data-label-template'] = $_label . ' (%d)';

		if ( $prepare_html_for_caching ) {

			if ( ! empty( $cached_post_count[ $encoded_value ] ) ) {
				$post_count = $cached_post_count[ $encoded_value ];
			} else {
				$post_count = '';
			}

			if ( ! empty( $post_count ) ) {
				$_label = sprintf( $option_atts['data-label-template'], $post_count );

			} else {
				$option_atts['class'] .= 'disabled';
			}

			if ( $selected_values == $encoded_value ) {
				$option_atts['selected'] = '';
			}
		}
	}

	$output .= '<option' . us_implode_atts( $option_atts ) . '>';

	// Text before value (exclude for "Any" value)
	if ( $_value !== '*' ) {
		$output .= $text_before_value ?? '';
	}

	$output .= esc_html( apply_filters( 'us_list_filter_value_label', $_label, $item_value, $item_name ) );

	// Text after value (exclude for "Any" value)
	if ( $_value !== '*' ) {
		$output .= $text_after_value ?? '';
	}

	$output .= '</option>';
}

$output .= '</select>'; // w-filter-item-value-select

echo $output;
