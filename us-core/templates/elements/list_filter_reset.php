<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Shortcode: us_list_filter_reset
 *
 * @var string $reset_all_label
 * @var string $reset_all_style
 * @var boolean $show_selected_values
 * @var string $selected_values_style
 * @var string $values_gap
 * @var string $selected_values_pos
 */

// Never output inside loop or specific Reusable Blocks or AMP
global $us_is_page_block_in_no_results, $us_is_page_block_in_menu;
if (
	us_in_the_loop()
	OR $us_is_page_block_in_no_results
	OR $us_is_page_block_in_menu
	OR us_amp()
) {
	return;
}

$_atts = array(
	'class' => 'w-filter-reset',
	'style' => '--values-gap:' . $values_gap . ';',
);

if ( ! usb_is_preview() ) {
	$_atts['class'] .= ' hidden';
}

if ( $show_selected_values ) {
	$_atts['class'] .= ' show_selected_values';
	$_atts['class'] .= ' pos_' . $selected_values_pos;
}

$_atts['class'] .= $classes ?? '';

if ( ! empty( $el_id ) ) {
	$_atts['id'] = $el_id;
}

$reset_all_params = array(
	'html_atts' => array(
		'class' => 'w-filter-reset-all',
	),
	'label' => $reset_all_label,
);

$reset_single_params = array(
	'html_atts' => array(
		'class' => 'w-filter-reset-single',
	),
	'label' => sprintf( '<t>%s: </t><v>%s</v>', us_translate( 'Title' ), us_translate( 'Value' ) ), // placeholder
);

if ( is_numeric( $reset_all_style ) ) {
	$reset_all_params['html_atts']['class'] .= ' w-btn ' . us_get_btn_class( $reset_all_style );
} else {
	$reset_all_params['html_atts']['class'] .= ' ' . $reset_all_style;
}

if ( is_numeric( $selected_values_style ) ) {
	$reset_single_params['html_atts']['class'] .= ' w-btn ' . us_get_btn_class( $selected_values_style );
} else {
	$reset_single_params['html_atts']['class'] .= ' ' . $selected_values_style;
}

$json_data = array(
	'singleButtonTemplate' => us_get_btn( $reset_single_params ),
);

echo '<div' . us_implode_atts( $_atts ) . us_pass_data_to_js( $json_data ) . '>';

// Show selected values via 2 placeholders for Live preview
if ( usb_is_preview() ) {
	echo us_get_btn( $reset_single_params );
	echo us_get_btn( $reset_single_params ); // double is intented
}

echo us_get_btn( $reset_all_params );

echo '</div>'; // .w-filter-reset
