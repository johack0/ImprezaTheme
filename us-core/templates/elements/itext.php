<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Interactive Text
 */

if ( empty( $texts ) ) {
	$texts = 'Cannot be empty';
}

$_atts['class'] = 'w-itext';
$_atts['class'] .= $classes ?? '';
$_atts['class'] .= ' type_' . $animation_type;
$_atts['class'] .= ' align_' . $align;
$_atts['style'] = '';

// Reset some values, if part animation is disabled
if ( $disable_part_animation ) {
	$_atts['class'] .= ' disable_part_animation';

	$dynamic_bold = FALSE;
	$dynamic_color = '';
}

if ( $dynamic_bold ) {
	$_atts['class'] .= ' dynamic_bold';
}
if ( ! empty( $el_id ) ) {
	$_atts['id'] = $el_id;
}
if ( $dynamic_color OR usb_is_preview() ) {
	$_atts['style'] = '--itext-dynamic-color:' . us_get_color( $dynamic_color ) . ';';
}
if ( $duration OR usb_is_preview() ) {
	$_atts['style'] .= '--itext-transition-duration:' . (float) $duration . 's;';
}
if ( $transition_timing_function OR usb_is_preview() ) {
	$_atts['style'] .= '--itext-timing-function:' . $transition_timing_function . ';';
}

// Allows usage of &nbsp; and other entities
$texts = trim( strip_tags( html_entity_decode( us_replace_dynamic_value( $texts ) ) ) );

$texts_arr = explode( "\n", $texts );

// Remove empty values
$texts_arr = array_values( array_filter( $texts_arr ) );

$groups = $group_map_changes = $group_map_unique = array();

if ( ! $disable_part_animation ) {

	// Get words and their delimiters to work on this level of abstraction
	$_parts = $_parts_reverse = array();

	foreach ( $texts_arr as $index => $text ) {
		if ( preg_match_all( '~[\w\-]+|[^\w\-]+~u', $text, $matches ) ) {
			$_parts[ $index ] = $_parts_reverse[ $index ] = $matches[0];
		}
	}

	$_max_parts = 0;

	if ( ! empty( $_parts ) ) {
		$_max_parts = count( max( $_parts ) );
	}

	// Get the whole set of parts with all the intermediate values (part_index => part_states)
	for ( $i = count( $_parts ) - 1; $i > - 1; $i -- ) {
		$empty_list = array_fill( 0, $_max_parts, ' ' );
		$_parts[ $i ] = $_parts[ $i ] + $empty_list;
		$empty_list = array_fill( 0, count( $_parts[ $i ] ) - count( $_parts_reverse[ $i ] ), ' ' );
		$_parts_reverse[ $i ] = array_merge( $empty_list, $_parts_reverse[ $i ] );
	}

	// Determine where fewer changes are and choose a smaller option
	$_part_changes = $_part_reverse_changes = 0;
	for ( $i = $_max_parts - 1; $i > - 1; $i -- ) {
		if ( count( array_unique( wp_list_pluck( $_parts, $i ) ) ) > 1 ) {
			$_part_changes ++;
		}
		if ( count( array_unique( wp_list_pluck( $_parts_reverse, $i ) ) ) > 1 ) {
			$_part_reverse_changes ++;
		}
	}
	$_parts = $_part_changes < $_part_reverse_changes
		? $_parts
		: $_parts_reverse;
	unset( $_part_reverse, $_part_changes, $_part_reverse_changes );

	// Group and receive map changes
	if ( ! empty( $_parts ) ) {
		for ( $i = count( max( $_parts ) ); $i > 0; $i -- ) {
			$groups[ $i ] = wp_list_pluck( $_parts, $i - 1 );
			$group_map_unique[ $i - 1 ] = count( array_unique( $groups[ $i ] ) );
			$group_map_changes[ $i - 1 ] = $group_map_unique[ $i - 1 ] > 1;
		}
	}

	$groups = array_reverse( $groups );
	$group_map_changes = array_reverse( $group_map_changes );

} else {
	$groups = array( $texts_arr );
	$group_map_changes = array_fill( 0, count( $texts_arr ), TRUE );
}

$space_char = ' ';

// Add spaces to word ends
for ( $i = count( $groups ) - 1; $i > 0; $i -- ) {
	$is_empty = ! preg_replace( '/([\s]+)$/ui', '', implode( '', $groups[ $i ] ) );
	if ( $group_map_unique[ $i ] == 1 AND $is_empty ) {
		unset( $group_map_changes[ $i ] );
	}
	if ( isset( $groups[ $i - 1 ] ) AND $is_empty ) {
		foreach ( $groups[ $i - 1 ] as &$text ) {
			$text .= $space_char;
		}
		unset( $text, $groups[ $i ] );
	}
}
unset( $group_map_unique );

// Reset indexes
$groups = array_values( $groups );
$group_map_changes = array_values( $group_map_changes );

// The combination of words that are near or all for printing
for ( $i = count( $groups ); $i > 0; $i -- ) {
	if (
		isset( $group_map_changes[ $i ], $group_map_changes[ $i - 1 ] )
		AND $group_map_changes[ $i ] === TRUE
		AND $group_map_changes[ $i - 1 ] === TRUE
		OR (
			$animation_type === 'typingChars'
			AND $group_map_changes[0] === TRUE
		)
	) {
		foreach ( $groups[ $i - 1 ] as $text_i => &$text ) {
			if ( isset( $groups[ $i ][ $text_i ] ) ) {
				$text .= $groups[ $i ][ $text_i ];
			}
		}
		unset( $text, $groups[ $i ], $group_map_changes[ $i ] );
	}
}

// Reset indexes
$groups = array_values( $groups );
$group_map_changes = array_values( $group_map_changes );
$group_keys = array_keys( $groups );

// Remove extra spaces from the end of a line
foreach ( $groups[ end( $group_keys ) ] as &$text ) {
	$text = preg_replace( '/([\s]+)$/ui', '', $text );
}
unset( $text );

$js_data = array(
	'duration' => (float) $duration * 1000,
	'delay' => (float) $delay * 1000,
);

// Output the element
$output = '<' . $tag . us_implode_atts( $_atts );
if ( ! us_amp() ) {
	$output .= us_pass_data_to_js( $js_data );
}
$output .= '>';

foreach ( $groups as $index => $group ) {

	// Remove leading spaces in the first part
	if ( $index === 0 ) {
		$group = array_map( 'ltrim', $group );
	}

	ksort( $group );

	if ( empty( $group_map_changes[ $index ] ) ) {
		$output .= $group[0]; // static part

	} else {

		// Delete all indents and spaces at the beginning of a line
		$group = array_map(
			function ( $text ) {
				return ltrim( $text, " \t\n\r\0\x0B\xC2\xA0" );
			},
			$group
		);

		$output .= '<span class="w-itext-part">';

		foreach ( $group as $i => $state_text ) {

			$state_text = preg_replace( '/\s+/', $space_char, htmlentities( $state_text ) );

			$state_class = 'w-itext-state state-' . $i;
			if ( $i === 0 ) {
				$state_class .= ' is-active';
			}
			$output .= '<span class="' . $state_class . '">';
			$output .= $state_text;
			$output .= '</span>';
		}

		$output .= '</span>';

		if (
			$animation_type === 'typingChars'
			AND $state_text
			AND $state_text !== $space_char
		) {
			$output .= '<i class="w-itext-cursor"></i>';
		}
	}
}
$output .= '</' . $tag . '>';

echo $output;
