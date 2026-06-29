<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Output Event Date element for The Event Calendar plugin
 *
 * @var $classes string
 * @var $id string
 */

if ( ! function_exists( 'tribe_events_event_schedule_details' ) ) {
	return;
}

// Cases when the element shouldn't be shown
if ( us_in_the_loop() AND us_get_loop_item_type() != 'post' ) {
	return;
}
if ( ! us_in_the_loop() AND is_archive() ) {
	return;
}

$_atts['class'] = 'w-post-elm event_date';
$_atts['class'] .= $classes ?? '';

if ( ! empty( $el_id ) AND $us_elm_context == 'shortcode' ) {
	$_atts['id'] = $el_id;
}

if ( empty( $format ) ) {
	$format = get_option( 'date_format' );
}

if ( $type == 'start' ) {
	$_atts['data-format'] = $format;

	if ( $start_date = us_get_custom_field( '_EventStartDate' ) ) {
		$date = wp_date( $format, strtotime( $start_date ) );

	} elseif ( usb_is_template_preview() ) {
		$date = wp_date( $format ); // placeholder date

	} else {
		return;
	}

} elseif ( $type == 'end' ) {
	$_atts['data-format'] = $format;

	if ( $end_date = us_get_custom_field( '_EventEndDate' ) ) {
		$date = wp_date( $format, strtotime( $end_date ) );

	} elseif ( usb_is_template_preview() ) {
		$date = wp_date( $format ); // placeholder date

	} else {
		return;
	}

} else {
	$current_event_ID = us_get_current_id();
	$date = tribe_events_event_schedule_details( $current_event_ID );
}

$text_before = us_replace_dynamic_value( trim( (string) $text_before ) );
if ( $text_before !== '' OR usb_is_template_preview() ) {
	$text_before = '<span class="w-post-elm-before">' . $text_before . '</span>';
}
$text_after = us_replace_dynamic_value( trim( (string) $text_after ) );
if ( $text_after !== '' OR usb_is_template_preview() ) {
	$text_after = '<span class="w-post-elm-after">' . $text_after . '</span>';
}

// Output the element
$output = '<div' . us_implode_atts( $_atts ) . '>';
if ( ! empty( $icon ) ) {
	$output .= us_prepare_icon_tag( $icon );
}
$output .= $text_before;
$output .= '<span>' . $date . '</span>';
$output .= $text_after;
$output .= '</div>';

echo $output;
