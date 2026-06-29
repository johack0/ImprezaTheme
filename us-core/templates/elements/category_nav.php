<?php

/**
 * Category Nav element
 */

$_atts['class'] = 'w-menu for_category_nav';
$_atts['class'] .= $classes ?? '';
$_atts['class'] .= ' style_' . $item_style;
$_atts['class'] .= ' parent_level_' . $max_parent_level;
$_atts['class'] .= ' child_level_' . $max_child_level;

if ( $show_count ) {
	$_atts['class'] .= ' show_count';
}

if ( ! empty( $el_id ) ) {
	$_atts['id'] = $el_id;
}

$_atts['style'] = '';

// CSS Variables
if ( ! in_array( $item_gap, array( '', '0', '0em', '0px' ) ) ) {
	$_atts['style'] .= '--main-gap:' . $item_gap . ';';
}
if ( ! in_array( $item_ver_indent, array( '', '0', '0em', '0px' ) ) ) {
	$_atts['style'] .= '--main-ver-indent:' . $item_ver_indent . ';';
}
if ( ! in_array( $item_hor_indent, array( '', '0', '0em', '0px' ) ) ) {
	$_atts['style'] .= '--main-hor-indent:' . $item_hor_indent . ';';
}

// Colors
if ( $item_color_bg = us_get_color( $item_color_bg, /* Gradient */ TRUE ) and $item_style == 'blocks' ) {
	$_atts['style'] .= '--main-bg-color:' . $item_color_bg . ';';
}
if ( $item_color_text = us_get_color( $item_color_text ) ) {
	$_atts['style'] .= '--main-color:' . $item_color_text . ';';
}
if ( $item_color_bg_hover = us_get_color( $item_color_bg_hover, /* Gradient */ TRUE ) and $item_style == 'blocks' ) {
	$_atts['style'] .= '--main-hover-bg-color:' . $item_color_bg_hover . ';';
}
if ( $item_color_text_hover = us_get_color( $item_color_text_hover ) ) {
	$_atts['style'] .= '--main-hover-color:' . $item_color_text_hover . ';';
}
if ( $item_color_bg_active = us_get_color( $item_color_bg_active, /* Gradient */ TRUE ) and $item_style == 'blocks' ) {
	$_atts['style'] .= '--main-active-bg-color:' . $item_color_bg_active . ';';
}
if ( $item_color_text_active = us_get_color( $item_color_text_active ) ) {
	$_atts['style'] .= '--main-active-color:' . $item_color_text_active . ';';
}

if ( $show_as_accordion ) {
	$_atts['class'] .= ' type_accordion';

	if ( $accordion_allow_multiple_open ) {
		$_atts['class'] .= ' allow_multiple_open';
	}
	if ( $accordion_control_icon ) {
		$_atts['class'] .= ' icontype_' . $accordion_control_icon;
	}
	if ( $accordion_control_position ) {
		$_atts['class'] .= ' iconpos_' . $accordion_control_position;
	}
}

$current_parent_level = 0;

// Define the current term and its parents
if (
	( is_category() OR is_tax() OR is_tag() )
	AND $current_term_object = get_queried_object()
	AND $current_term_object->taxonomy === $taxonomy
) {
	$current_term_id = $current_term_object->term_id;
	$current_parent_id = $max_parent_id = $current_term_object->parent;
	$current_parent_level = 1;

	// Define parent term of specified max level
	while ( $max_parent_level > 1 ) {
		$_term = get_term( $max_parent_id, $taxonomy );

		if ( ! $_term OR is_wp_error( $_term ) ) {
			break;
		}

		$max_parent_id = $_term->parent;

		$max_parent_level--;
		$current_parent_level++;
	}

	// Output max parent term as a separate link with arrow
	if ( $max_parent_id AND $max_parent = get_term( $max_parent_id, $taxonomy ) ) {
		$main_parent_link = '<div class="cat-item main-parent">';
		$main_parent_link .= sprintf( '<a href="%s">', get_term_link( $max_parent_id, $taxonomy ) );
		$main_parent_link .= '<span>';
		$main_parent_link .= $max_parent->name;
		$main_parent_link .= '</span>';
		$main_parent_link .= '</a>';
		$main_parent_link .= '</div>';
	}

} else {
	$_atts['class'] .= ' no_current_term';
}

$list_args = array(
	'taxonomy' => $taxonomy,
	'hide_empty' => $hide_empty,
	'current_category' => $current_term_id ?? 0,
	'child_of' => $max_parent_id ?? 0,
	'orderby' => 'menu_order',
	'title_li' => '',
	'show_count' => $show_count,
	'echo' => 0,

	// Custom properties
	'current_parent' => $current_parent_id ?? 0, // used for assign classes to ancestors
	'max_level' => $current_parent_level + $max_child_level - 1, // used to limit showing levels
	'show_as_accordion' => $show_as_accordion,
	'walker' => new US_Walker_Category(),
);

$list_args = apply_filters( 'us_category_nav_args', $list_args, $atts );

$terms_output = wp_list_categories( $list_args );

if ( ! empty( $terms_output ) ) {
	$output = '<nav' . us_implode_atts( $_atts ) . '>';
	$output .= ( $main_parent_link ?? '' );
	$output .= '<ul class="menu level_1">';
	$output .= $terms_output;
	$output .= '</ul>';
	$output .= '</nav>';

	echo $output;
}
