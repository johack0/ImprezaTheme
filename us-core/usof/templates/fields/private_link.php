<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Theme Options Field: Private Link (used for Theme Options > Maintenance Mode)
 *
 * @var $name  string Field name
 * @var $id    string Field ID
 * @var $field array Field options
 *
 * @param $field ['title'] string Field title
 * @param $field ['description'] string Field title
 * @param $field ['placeholder'] string Field placeholder
 *
 * @var $value string Current value
 */

$hidden_atts = array(
	'name' => $name,
	'type' => 'hidden',
	'value' => $value,
);

// Output the element
$output = '<input' . us_implode_atts( $hidden_atts ) . '/>';
$output .= '<div class="usof-private-link">';
$output .= home_url( '/?us-share=' ) . $value;
$output .= '</div>';

echo $output;
