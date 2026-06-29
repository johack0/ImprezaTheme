<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Interactive Banner
 *
 * @var $image string Image ID
 * @var $size string Image size
 * @var $title string
 * @var $desc string description field
 * @var $link string
 * @var $font string
 * @var $tag string Title HTML tag
 * @var $align string
 * @var $animation string
 * @var $easing string
 * @var $aspect_ratio string Aspect ratio
 * @var $desc_size string
 * @var $classes string Extend class names
 */

$_atts['class'] = 'w-ibanner';
$_atts['class'] .= $classes ?? '';
$_atts['class'] .= ' animation_' . $animation;
$_atts['class'] .= ' easing_' . $easing;

// Fallback aspect ratio (since version 8.46.1)
if ( ! us_design_options_has_property( $css, 'aspect-ratio' ) ) {
	$_atts['class'] .= ( ! empty( $atts['ratio'] ) ) ? ' ratio_' . $atts['ratio'] : ' ratio_1x1';
}

if ( ! empty( $el_id ) ) {
	$_atts['id'] = $el_id;
}

// Fallback since version 7.1
if ( ! empty( $align ) ) {
	$_atts['class'] .= ' align_' . $align;
}

$title_inline_css = us_prepare_inline_css(
	array(
		'font-size' => $title_size,
	)
);

$image = us_replace_dynamic_value( $image, /* acf_format */ FALSE );

$image_url = wp_get_attachment_image_url( $image, $size );

if ( empty( $image_url ) ) {
	$image_url = us_get_img_placeholder( $size, TRUE );
}

// Apply filters to title
$title = us_replace_dynamic_value( $title );
$title = wptexturize( $title );
$title = strip_tags( $title, '<strong><br>' );


// Output the element
$output = '<div' . us_implode_atts( $_atts ) . '>';

// link
$link_atts = us_generate_link_atts( $link );
if ( ! empty( $link_atts['href'] ) ) {
	$link_atts['aria-label'] = strip_tags( $title );
	$output .= '<a' . us_implode_atts( $link_atts ) . '></a>';
}

$output .= '<div class="w-ibanner-h">';
$output .= '<div class="w-ibanner-image" style="background-image: url(' . esc_url( $image_url ) . ')"></div>';
$output .= '<div class="w-ibanner-content">';
$output .= '<div class="w-ibanner-content-h">';
$output .= '<' . $title_tag . ' class="w-ibanner-title"' . $title_inline_css . '>' . $title . '</' . $title_tag . '>';
$output .= '<div class="w-ibanner-desc">' . wpautop( us_replace_dynamic_value( $desc ) ) . '</div>';
$output .= '</div>'; // w-ibanner-content-h
$output .= '</div>'; // w-ibanner-content
$output .= '</div>'; // w-ibanner-h
$output .= '</div>'; // w-ibanner

echo $output;
