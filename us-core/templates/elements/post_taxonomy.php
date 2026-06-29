<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Output Post Taxonomy element
 *
 * @var $taxonomy_name string Taxonomy name
 * @var $link string Link type: 'post' / 'archive' / 'custom' / 'none'
 * @var $custom_link array
 * @var $color string Custom color
 * @var $icon string Icon name
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
if (
	(
		empty( $taxonomy_name )
		OR ! taxonomy_exists( $taxonomy_name )
	)
	AND ! usb_is_post_preview()
) {
	return;
}

// In Live Builder for Reusable Block / Page Template show placeholders for shortcode
if ( usb_is_template_preview() AND ! us_in_the_loop() ) {
	$terms = array(
		(object) array(
			'term_id' => 0,
			'slug' => $taxonomy_name,
			'name' => us_translate( 'Value' ) . ' 1',
			'swatch_color' => '#8c0',
		),
		(object) array(
			'term_id' => 0,
			'slug' => $taxonomy_name,
			'name' => us_translate( 'Value' ) . ' 2',
			'swatch_color' => '#c80',
		),
	);

} else {
	$terms = get_the_terms( get_the_ID(), $taxonomy_name );
}

if ( ! is_array( $terms ) OR count( $terms ) == 0 ) {

	// Output empty container for USBuilder
	if ( usb_is_post_preview() ) {
		echo '<div class="w-post-elm"></div>';
	}
	return;
}

// Sorting terms from woocommerce
if ( strpos( $taxonomy_name, 'pa_' ) === 0 ) {
	$_terms = array();
	foreach ( $terms as $term ) {
		if ( ( $order = get_term_meta( $term->term_id, 'order', TRUE ) ) == FALSE OR ! is_numeric( $order ) ) {
			$order = count( $_terms ) + 1;
		}
		$_terms[ $order ] = $term;
	}
	ksort( $_terms );
	$terms = $_terms;
}

$_atts['class'] = 'w-post-elm post_taxonomy';
$_atts['class'] .= $classes ?? '';
$_atts['class'] .= ' style_' . $style;

if ( $color_link ) {
	$_atts['class'] .= ' color_link_inherit';
}

// Show color swatches (only for product attributes)
if ( $show_color_swatch ) {
	$_atts['class'] .= ' with_color_swatch';
	if ( $hide_color_swatch_label ) {
		$_atts['class'] .= ' hide_color_swatch_label';
	}
}

if ( ! empty( $el_id ) AND $us_elm_context == 'shortcode' ) {
	$_atts['id'] = $el_id;
}

$text_before = us_replace_dynamic_value( trim( (string) $text_before ) );
if ( $text_before != '' ) {
	$text_before = '<span class="w-post-elm-before">' . $text_before . ' </span>';
}

$text_after = us_replace_dynamic_value( trim( (string) $text_after ) );
if ( $text_after !== '' ) {
	$text_after = '<span class="w-post-elm-after"> ' . $text_after . '</span>';
}

$terms = apply_filters( 'us_post_taxonomy_terms', $terms, $_atts );

// Output the element
$output = '<div' . us_implode_atts( $_atts ) . '>';
if ( ! empty( $icon ) ) {
	$output .= us_prepare_icon_tag( $icon );
}
$output .= $text_before;
if ( $style == 'badge' AND count( $terms ) > 1 ) {
	$output .= '<div class="w-post-elm-list">';
}

$i = 1;
foreach ( $terms as $term ) {

	$term_name = $term->name;

	// Set Button Style class
	if ( $style == 'badge' ) {

		// Skip the "badge" style
		if ( $btn_style == 'badge' ) {
			$btn_class = 'us-btn-style_badge';
		} else {
			$btn_class = us_get_btn_class( $btn_style );
		}
		$term_class = sprintf( 'w-btn %s term-%s term-%s', $btn_class, $term->term_id, $term->slug );

	} else {
		$term_class = sprintf( 'term-%s term-%s', $term->term_id, $term->slug );
	}

	// Get color of color swatches where $term->swatch_color is placeholder data 
	if ( $show_color_swatch OR $apply_swatch_colors ) {
		$_color_swatch_value = $term->swatch_color ?? (string) get_term_meta( $term->term_id, 'color_swatch', /* single */TRUE );
	}

	// Show color swatches
	if ( $show_color_swatch ) {
		if ( $_color_swatch_value ) {
			$color_swatch_style = 'background:' . us_get_color( $_color_swatch_value, TRUE ) . ';';
		}
		$_swatch_atts = array(
			'class' => 'w-color-swatch',
			'style' => $color_swatch_style ?? '',
			'title' => $term_name,
		);
		$term_name = '<span' . us_implode_atts( $_swatch_atts ) . '></span><span>' . $term_name . '</span>';
	}

	// Apply color from "Color Swatch" settings to the term
	$term_inline_style = '';
	if ( $apply_swatch_colors AND $_color_swatch_value ) {
		$term_inline_style .= '--swatch-color:' . us_get_color( $_color_swatch_value, TRUE ) . ';';
		$term_inline_style .= '--swatch-contrast-color:' . us_get_contrast_color( $_color_swatch_value ) . ';';
	}

	$link_atts = us_generate_link_atts( $link, /* additional data */array( 'term_id' => $term->term_id ) );

	// Button
	if ( $style == 'badge' ) {
		$btn_params = array(
			'html_atts' => array(
				'class' => $term_class,
				'style' => $term_inline_style,
			),
			'label' => $term_name,
		);
		$btn_params['html_atts'] += $link_atts;

		$output .= us_get_btn( $btn_params );

		// Text
	} else {
		$_span_atts = array(
			'class' => $term_class,
			'style' => $term_inline_style,
		);
		if ( ! empty( $link_atts['href'] ) ) {
			$output .= '<a' . us_implode_atts( $_span_atts + $link_atts ) . '>' . $term_name . '</a>';
		} else {
			$output .= '<span' . us_implode_atts( $_span_atts ) . '>' . $term_name . '</span>';
		}
	}

	// Separator
	if (
		$style != 'badge'
		AND $i != count( $terms )
	) {
		$output .= '<b>' . $separator . '</b>';
	}
	$i++;
}

if ( $style == 'badge' AND count( $terms ) > 1 ) {
	$output .= '</div>';
}
$output .= $text_after;
$output .= '</div>';

echo $output;
