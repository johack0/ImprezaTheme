<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Timeline Section
*/

$_atts['class'] = 'w-timeline-section';
$_atts['class'] .= $classes ?? '';

if ( ! empty( $el_id ) ) {
	$_atts['id'] = $el_id;
}

global $us_timeline_section_config; // settings common for all sections passed from the parent Timeline

// Always render the icon; its visibility per marker style is handled in CSS (needed for Live Builder mod works)
$icon_html = '';
if ( ! empty( $marker_icon ) ) {
	$icon_html = us_prepare_icon_tag( $marker_icon );
	$_atts['class'] .= ' marker_style_icon';

} elseif ( ! empty( $us_timeline_section_config['marker_icon'] ) ) {
	$icon_html = us_prepare_icon_tag( $us_timeline_section_config['marker_icon'] );
}

$marker_atts = array(
	'class' => 'w-timeline-section-marker',
	'style' => '',
);

if ( ! empty( $marker_background_color ) ) {
	$marker_atts['style'] .= '--marker-background-color: ' . us_get_color( $marker_background_color, TRUE ) . ';';
}
if ( ! empty( $marker_text_color ) ) {
	$marker_atts['style'] .= '--marker-text-color: ' . us_get_color( $marker_text_color ) . ';';
}
if ( ! empty( $marker_border_color ) ) {
	$marker_atts['style'] .= '--marker-border-color: ' . us_get_color( $marker_border_color ) . ';';
}

$content_atts = array(
	'class' => 'w-timeline-section-content',
);

// Move classes related to design settings from section container to section content
if ( ! empty( $design_css_class ) ) {
	foreach( array( $design_css_class, 'us_animate_this' ) as $css_class_name ) {
		if ( strpos( $_atts['class'], $css_class_name ) !== FALSE ) {
			$_atts['class'] = str_replace( ' ' . $css_class_name, '', $_atts['class'] );
			$content_atts['class'] .= ' ' . $css_class_name;
		}
	}
}

// Output the element
$output = '<div' . us_implode_atts( $_atts ) . '>';
	$output .= '<div class="w-timeline-line"></div>';
    $output .= '<div class="w-timeline-section-h">';
		$output .= '<div' . us_implode_atts( $marker_atts ) . '>' . $icon_html . '</div>';
		$output .= '<div' . us_implode_atts( $content_atts ) . '>';
			$output .= do_shortcode( $content );
		$output .= '</div>';
    $output .= '</div>';
$output .= '</div>';

echo $output;
