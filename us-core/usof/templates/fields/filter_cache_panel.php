<?php defined('ABSPATH') or die('This script cannot be accessed directly.');

/**
 * Filter Cache Panel.
 */
$json_data = array(
	'ajaxData' => array(
		'action' => 'us_filter_clear_cache',
		'_nonce' => wp_create_nonce( 'us_filter_clear_cache' ),
	),
);

$button_atts = array(
	'class' => 'usof-button for_clear_cache disabled',
	'type' => 'button',
	'onclick' => us_pass_data_to_js( $json_data, /* onclick */FALSE ),
);

// Output
$output = '<div class="usof-data-list">';
$output .= '<div class="usof-data-list-item for_num_of_rows">';
$output .= sprintf( '%s: <span>%s</span>', __( 'Cached filters', 'us' ), us_filter_cache()->get_size() );
$output .= '</div>'; // .usof-data-list-item
$output .= '</div>'; // .usof-data-list

$output .= '<button' . us_implode_atts( $button_atts ) . '>';
$output .= '<span class="usof-button-text">' . __( 'Clear all cache', 'us' ) . '</span>';
$output .= '<div class="usof-preloader"></div>';
$output .= '</button>';

$output .= '<div class="usof-message hidden"></div>';

echo $output;
