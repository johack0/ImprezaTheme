<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Timeline element
 */

if ( empty( $content ) ) { // Timeline is meaningless without sections
    return;
}

$_atts['class'] = 'w-timeline';
$_atts['class'] .= $classes ?? '';
$_atts['class'] .= ' line_pos_' . $line_pos;
$_atts['class'] .= ' marker_style_' . $marker_style;
$_atts['class'] .= ' marker_valign_' . $marker_valign;

if ( ! empty( $el_id ) ) {
	$_atts['id'] = $el_id;
}

$_atts['style'] = '--line-thickness:' . $line_thickness . ';';
$_atts['style'] .= ' --line-style:' . $line_style . ';';
$_atts['style'] .= ' --line-color: ' . us_get_color( $line_color, TRUE ) . ';';
$_atts['style'] .= ' --line-offset:' . $line_offset . ';';
$_atts['style'] .= ' --marker-size:' . $marker_size . ';';

$_atts['style'] .= ' --marker-circle-scale:' . $marker_circle_scale . ';';
$_atts['style'] .= ' --section-gap:' . $section_gap . ';';

if ( $hide_line_endings ) {
    $_atts['class'] .= ' hide_line_endings';
}

if ( ! empty( $marker_background_color ) ) {
	$_atts['style'] .= ' --marker-background-color: ' . us_get_color( $marker_background_color, TRUE ) . ';';
}
if ( ! empty( $marker_text_color ) ) {
	$_atts['style'] .= ' --marker-text-color: ' . us_get_color( $marker_text_color ) . ';';
}
if ( ! empty( $marker_border_color ) ) {
	$_atts['style'] .= ' --marker-border-color: ' . us_get_color( $marker_border_color ) . ';';
}

// Border width also applies to the default outline of number/icon markers
$_atts['style'] .= ' --marker-border-width:' . $marker_border_width . ';';

if ( ! empty( $sticky_markers ) ) {
    $_atts['class'] .= ' sticky_markers';
}

// Allow all sections to use common animations and markers, to simplify element setup
global $us_timeline_section_config;
$us_timeline_section_config = array(
	'marker_icon' => $marker_icon ?? '',
);

// Hide markers and the line below the configured screen width (scoped via an index class)
$responsive_styles = '';
if ( ! empty( $marker_hide_screen_width ) ) {

	// Unique index so each timeline on the page gets its own breakpoint
	global $us_timeline_elm_index;
	if ( usb_is_preview() ) {
		$us_timeline_elm_index = us_uniqid();
	} else {
		$us_timeline_elm_index += 1;
	}
	$_atts['class'] .= ' elm_index_' . $us_timeline_elm_index;

	$elm = '.w-timeline.elm_index_' . $us_timeline_elm_index;
	$css = '@media (max-width:' . (int) $marker_hide_screen_width . 'px){';
		$css .= "$elm .w-timeline-sections { padding-inline: 0 !important; }";
		$css .= "$elm .w-timeline-section-marker, $elm .w-timeline-line { display: none; }";
		$css .= "$elm .w-timeline-section-content { text-align: start !important; margin-inline: 0; max-width: 100%; }";
	$css .= '}';
	$responsive_styles = '<style>' . us_minify_css( $css ) . '</style>';
}

// Output the element
$output = '<div' . us_implode_atts( $_atts ) . '>';
    $output .= $responsive_styles;
    $output .= '<div class="w-timeline-sections">';
        $output .= do_shortcode( $content );
    $output .= '</div>';
$output .= '</div>';

unset( $us_timeline_section_config );

echo $output;
