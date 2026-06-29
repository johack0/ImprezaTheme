<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Output Post Date element
 *
 * @var $type string Date type: 'published' / 'modified'
 * @var $format string Date format selected from preset
 * @var $format_custom string Date custom format
 * @var $icon string Icon name
 * @var $tag string 'h1' / 'h2' / 'h3' / 'h4' / 'h5' / 'h6' / 'p' / 'div'
 * @var $color string Custom color
 * @var $design_options array
 *
 * @var $classes string
 * @var $id string
 */

// Cases when the element shouldn't be shown
if ( us_in_the_loop() AND us_get_loop_item_type() != 'post' ) {
	return;
}
if ( ! us_in_the_loop() AND is_archive() ) {
	return;
}

$_atts['class'] = 'w-post-elm post_date';
$_atts['class'] .= $classes ?? '';

// Classes for Google structured data
if ( $type == 'modified' ) {
	$_atts['class'] .= ' entry-date updated';

} elseif ( $type == 'published' ) {
	$_atts['class'] .= ' entry-date published';

} else {
	$_atts['class'] .= ' ' . $type;
}

if ( ! empty( $el_id ) AND $us_elm_context == 'shortcode' ) {
	$_atts['id'] = $el_id;
}

$tag = 'time';

$time_diff_date = $smart_date = FALSE;

// Generate date format
if ( $format == 'default' ) {
	$format = get_option( 'date_format' );

} elseif ( $format == 'custom' ) {
	$format = $format_custom;

} elseif ( $format == 'smart' ) {
	$format = 'U';
	$smart_date = TRUE;

} elseif ( $format == 'time_diff' ) {
	$format = 'U';
	$time_diff_date = TRUE;
}

if ( $type == 'modified' ) {
	$date = get_the_modified_date( $format );

	$_atts['datetime'] = get_the_modified_date( 'c' ); // needed datetime attribute for <time> tag

	if ( $smart_date OR $time_diff_date ) {
		$_atts['title'] = sprintf( us_translate( '%1$s at %2$s' ), get_the_modified_date( 'j F Y' ), get_the_modified_date( 'H:i:s e' ) );
	}

} elseif ( $type == 'published' ) {
	$date = get_the_date( $format );

	$_atts['datetime'] = get_the_date( 'c' ); // needed datetime attribute for <time> tag

	if ( $smart_date OR $time_diff_date ) {
		$_atts['title'] = sprintf( us_translate( '%1$s at %2$s' ), get_the_date( 'j F Y' ), get_the_date( 'H:i:s e' ) );
	}

	// Date from a custom field
} elseif ( $cf_value = us_get_custom_field( $type, FALSE ) AND is_string( $cf_value ) ) {

	$cf_timestamp = strtotime( $cf_value );

	$date = wp_date( $format, $cf_timestamp ) ?: '';

	$_atts['datetime'] = wp_date( 'c', $cf_timestamp ); // needed datetime attribute for <time> tag

	if ( $smart_date OR $time_diff_date ) {
		$_atts['title'] = sprintf( us_translate( '%1$s at %2$s' ), wp_date( 'j F Y', $cf_timestamp ), wp_date( 'H:i:s e', $cf_timestamp ) );
	}

	// Show the current date as placeholder for the Live preview
} elseif ( usb_is_preview() ) {
	$date = wp_date( $format );

	// Do not output the element when the custom field is empty or not a string
} else {
	return;
}

// Generate date in smart format
if ( $smart_date ) {
	$date = us_get_smart_date( $date );

} elseif ( $time_diff_date ) {
	$date = sprintf( us_translate( '%s ago' ), human_time_diff( $date, current_time( 'U' ) ) );
}

// Add Schema.org markup
if (
	us_get_option( 'schema_markup' )
	AND $us_elm_context == 'shortcode'
	AND ( $type == 'published' OR $type == 'modified' )
) {
	$_atts['itemprop'] = ( $type == 'modified' ) ? 'dateModified' : 'datePublished';
}

$text_before = us_replace_dynamic_value( trim( (string) $text_before ) );
if ( $text_before !== '' OR usb_is_post_preview() ) {
	$text_before = '<span class="w-post-elm-before">' . $text_before . ' </span>';
}
$text_after = us_replace_dynamic_value( trim( (string) $text_after ) );
if ( $text_after !== '' OR usb_is_post_preview() ) {
	$text_after = '<span class="w-post-elm-after"> ' . $text_after . '</span>';
}

// Output the element
$output = '<' . $tag . us_implode_atts( $_atts ) . '>';
if ( ! empty( $icon ) ) {
	$output .= us_prepare_icon_tag( $icon );
}
$output .= $text_before;
$output .= $date;
$output .= $text_after;
$output .= '</' . $tag . '>';

echo $output;
