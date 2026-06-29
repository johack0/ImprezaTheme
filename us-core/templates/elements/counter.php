<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Counter
 *
 * Dev note: if you want to change some of the default values or acceptable attributes, overload the shortcodes config.
 *
 */

$_atts['class'] = 'w-counter';
$_atts['class'] .= $classes ?? '';
$_atts['class'] .= ' color_' . $color;
$_atts['class'] .= ' align_' . $align;
$_atts['class'] .= ' animation_' . $animation;

// When some values are set in Design options, add the specific classes
if ( us_design_options_has_property( $css, 'font-size' ) ) {
	$_atts['class'] .= ' has_font_size';
}

if ( ! empty( $el_id ) ) {
	$_atts['id'] = $el_id;
}

// If we are in WPB front end editor mode, make sure the counter has an ID
if ( function_exists( 'vc_is_page_editable' ) AND vc_is_page_editable() AND empty( $_atts['id'] ) ) {
	$_atts['id'] = us_uniqid();
}

$_atts['data-duration'] = (float) $duration;
$_atts['style'] = '--w-counter-duration: ' . (float) $duration . 's;';

$value_inline_css = us_prepare_inline_css(
	array(
		'color' => ( $color == 'custom' )
			? us_get_color( $custom_color )
			: '',
	)
);

// Check for custom fields
$final = us_replace_dynamic_value( $final );

// Finding the positions of numbers in both the initial and final strings.
$pos = array();
foreach ( array( 'initial', 'final' ) as $key ) {
	$string = $$key;

	$pos[ $key ] = array();

	preg_match_all( '/(\(\-?\d+([\.,\'·\s]\d+)*\))|(\-?\d+([\.,\'·\s]\d+)*)/u', $string, $matches, PREG_OFFSET_CAPTURE );

	foreach ( $matches[0] as [ $match, $byteOffset ] ) {

		// Convert byte offset to character offset (multibyte-safe)
		$charOffset = mb_strlen( substr( $string, 0, $byteOffset ), 'UTF-8' );

		// The preg_* functions are not multibyte-safe, so instead of using the position captured by
		// preg_match_all directly, we first take the substring from the start up to that position
		// and then measure its length using a multibyte-aware function.
		$pos[ $key ][] = $charOffset;
		$pos[ $key ][] = $charOffset + mb_strlen( $match, 'UTF-8');
	}
}

// Making sure both strings contain the same number of numeric values.
$initial_count = count( $pos['initial'] );
$final_count = count( $pos['final'] );

if ( $initial_count != $final_count ) {

	// Not-paired numbers will be treated as letters
	if ( $initial_count > $final_count ) {
		$pos['initial'] = array_slice( $pos['initial'], 0, $final_count );

	} else/*if ( $initial_count < $final_count )*/ {
		$pos['final'] = array_slice( $pos['final'], 0, $initial_count );
	}
}

// Position boundaries
foreach ( array( 'initial', 'final' ) as $key ) {
	$string = $$key;

	array_unshift( $pos[ $key ], 0 );
	$pos[ $key ][] = mb_strlen( $string, 'UTF-8' );
}

// Output the element
$output = '<div' . us_implode_atts( $_atts ) . '>';

$output .= '<div class="w-counter-value" role="text" aria-label="' . $final . '"' . $value_inline_css . '>';

// Output the final value on page load (after hide in js), needed for SEO and accessibility
$output .= '<span class="w-counter-value-part final" aria-hidden="true">' . $final . '</span>';

// Determining whether to treat each part as a number or as a letter combination.
if ( ! us_amp() ) {
	for ( $index = 0, $length = count( $pos['initial'] ) -1; $index < $length; $index++ ) {

		$part_initial = mb_substr( $initial, $pos['initial'][ $index ], $pos['initial'][ $index + 1 ] - $pos['initial'][ $index ] );
		$part_final = mb_substr( $final, $pos['final'][ $index ], $pos['final'][ $index + 1 ] - $pos['final'][ $index ] );

		$value_part_atts = array(
			'class' => 'w-counter-value-part hidden type_' . ( ( $index % 2 ) ? 'number' : 'text' ),
			'aria-hidden' => 'true',
			'data-final' => $part_final,
		);
		$output .= '<span' . us_implode_atts( $value_part_atts ) . '>' . $part_initial . '</span>';
	}
}

$output .= '</div>';

if ( ! empty( $title ) ) {

	$title_inline_css = us_prepare_inline_css(
		array(
			'font-size' => $title_size,
			'font-weight' => $title_weight,
			'margin-top' => ( $title_indent === '0.6rem') ? '' : $title_indent,
		)
	);

	$title = us_replace_dynamic_value( $title );
	$title = wptexturize( $title );

	$output .= '<' . $title_tag . ' class="w-counter-title"' . $title_inline_css . '>' . $title . '</' . $title_tag . '>';
}
$output .= '</div>';

// If we are in WPB front end editor mode, apply JS to the counter
if ( function_exists( 'vc_is_page_editable' ) AND vc_is_page_editable() ) {
	$output .= '<script>
	jQuery( function( $ ) {
		if ( typeof $us !== "undefined" && typeof $.fn.usCounter === "function" ) {
			var $elm = jQuery( "#' . $_atts['id'] . '" );
			if ( $elm.data( "usCounter" ) === undefined ) {
				$elm.usCounter();
			}
		}
	} );
	</script>';
}

echo $output;
