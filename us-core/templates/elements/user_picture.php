<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * WP User Picture (Avatar)
 */

global $us_loop_user_ID;

$user_ID = $us_loop_user_ID ?? get_current_user_id();

// Do not output this element if no user ID
if ( empty( $user_ID ) ) {
	return;
}

$_atts['class'] = 'w-user-elm picture';
$_atts['class'] .= $classes ?? '';
$_atts['class'] .= $circle ? ' as_circle' : '';

// Limit the container width 
$_atts['style'] = 'max-width:' . $width;

// User avatar arguments
$avatar_args = array(
	'force_display' => TRUE, // show avatar disregarding the WP Discussion Settings
);

// Link
$link_atts = us_generate_link_atts( $link );

// Output the element
$output = '<div' . us_implode_atts( $_atts ) . '>';
$output .= empty( $link_atts['href'] ) ? '' : ( '<a' . us_implode_atts( $link_atts ) . '>' );

$output .= (string) get_avatar( $user_ID, $width, $default_avatar, '', $avatar_args );

$output .= empty( $link_atts['href'] ) ? '' : '</a>';
$output .= '</div>';

echo $output;
