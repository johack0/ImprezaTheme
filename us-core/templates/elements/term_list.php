<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Shortcode: us_term_list
 */

// Never output a 'loop' element inside other 'loop' elements
if ( us_in_the_loop() ) {
	return;
}

// Get the ID of the current object (post, term, user)
$current_object_id = us_get_current_id();

/*
 * Generate query for get_terms()
 */
$query_args = array();

// Child terms of the current term
if ( $source == 'current_term' AND ! usb_is_template_preview() ) {
	if ( $current_term = get_term( $current_object_id ) AND ! is_wp_error( $current_term ) ) {
		$query_args['taxonomy'] = $current_term->taxonomy;
	}

	// Show all levels of child terms
	if ( $include_children ) {
		$query_args['child_of'] = $current_object_id;

		// Show only the first sub-level of child terms
	} else {
		$query_args['parent'] = $current_object_id;
	}

	// Cases with user selected taxonomy
} else {
	if ( ! empty( $taxonomy ) ) {
		$query_args['taxonomy'] = explode( ',', $taxonomy );
	} else {
		$query_args['taxonomy'] = 'category';
	}

	// Include selected terms
	if ( $source == 'include' ) {
		$query_args['include'] = explode( ',', $term_ids );

		// Exclude selected terms
	} elseif ( $source == 'exclude' ) {

		// Exclude child terms or not
		if ( $include_children AND $term_ids ) {
			$query_args['exclude_tree'] = explode( ',', $term_ids );
		} else {
			$query_args['exclude'] = explode( ',', $term_ids );
		}

		// Child terms of the first selected term
	} elseif ( $source == 'children' ) {

		// Get only the first term ID
		if ( ! $_first_term_id = strstr( $term_ids, ',', TRUE ) ) {
			$_first_term_id = $term_ids;
		}

		// Show all levels of child terms
		if ( $include_children ) {
			$query_args['child_of'] = $_first_term_id;

			// Show only the first sub-level of child terms
		} else {
			$query_args['parent'] = $_first_term_id;
		}

		// Terms of the current post
	} elseif ( $source == 'current_post' AND ! usb_is_template_preview() ) {

		// Use the current post ID to get its terms
		$query_args['object_ids'] = $current_object_id;

		// All terms
	} else {
		if ( ! $include_children ) {
			$query_args['parent'] = '0';
		}
	}

	// Exclude the current term
	if ( $exclude_current AND is_archive() ) {
		if ( ! empty( $query_args['exclude'] ) ) {
			$query_args['exclude'][] = $current_object_id;
		} else {
			$query_args['exclude'] = $current_object_id;
		}
	}
}

// Hide empty terms
$query_args['hide_empty'] = (bool) $hide_empty;

// Order
if ( $order_invert ) {
	$query_args['order'] = 'DESC';
} else {
	$query_args['order'] = 'ASC';
}

// Order by
if ( $orderby == 'custom' AND ! empty( $orderby_custom_field ) ) {
	if ( $orderby_custom_type ) {
		$orderby = 'meta_value_num';
	} else {
		$orderby = 'meta_value';
	}
	$query_args['meta_key'] = $orderby_custom_field;
}
$query_args['orderby'] = $orderby;

// Generate meta_query based on Custom Fields conditions
if ( is_string( $meta_query ) ) {
	$meta_query = json_decode( urldecode( $meta_query ), TRUE );
}
if ( ! is_array( $meta_query ) ) {
	$meta_query = array();
}
if ( $meta_query_relation != 'none' AND ! empty( $meta_query ) ) {
	foreach ( $meta_query as &$_meta ) {

		// Set the NUMERIC type for specific "compare" values
		if ( in_array( $_meta['compare'], array( '>', '>=', '<', '<=' ) ) ) {
			$_meta['type'] = 'NUMERIC';
		}

		// Force date/time type if the relevant dynamic value is set
		if ( $_meta['value'] == '{{today_now}}' OR strpos( $_meta['value'], '{{date|') !== FALSE ) {
			$_meta['type'] = 'DATETIME';
		} elseif ( $_meta['value'] == '{{today}}' ) {
			$_meta['type'] = 'DATE';
		} elseif ( $_meta['value'] == '{{now}}' ) {
			$_meta['type'] = 'TIME';
		}

		// Unset the field value for specific "compare" values
		if ( in_array( $_meta['compare'], array( 'EXISTS', 'NOT EXISTS' ) ) AND isset( $_meta['value'] ) ) {
			unset( $_meta['value'] );
		} else {
			$_meta['value'] = us_replace_dynamic_value( $_meta['value'] );
		}
	}
	unset( $_meta );
	$meta_query['relation'] = $meta_query_relation;
	$query_args['meta_query'] = $meta_query;
}

// Number
if (
	$limit_number
	AND (int) $number
	AND $orderby !== 'rand'
) {
	$query_args['number'] = (int) $number;
}

// Apply filter for developers purposes
$query_args = apply_filters( 'us_term_list_query_args', $query_args, $filled_atts );

// Get result by query args
$terms = get_terms( $query_args );

// Reset the result in case of error
if ( is_wp_error( $terms ) ) {
	$terms = array();
}

// Order by random
if ( $orderby == 'rand' ) {
	shuffle( $terms );

	if ( $limit_number AND (int) $number ) {
		$terms = array_slice( $terms, 0, (int) $number );
	}
}

$grid_layout_settings = us_get_grid_layout_settings( $items_layout );

$template_vars = array(
	'shortcode_base' => $shortcode_base,
	'classes' => $classes ?? '',
	'grid_elm_id' => ! empty( $el_id ) ? $el_id : 'us_grid_' . us_uniqid(),
	'grid_layout_settings' => $grid_layout_settings,
	'no_results' => empty( $terms ),
	'items_count' => count( $terms ),
);

// Override 'type' param for Term Carousel
if ( $shortcode_base == 'us_term_carousel' ) {
	$template_vars['type'] = 'carousel';
}

us_load_template( 'templates/loop/start', $template_vars + $filled_atts );

if ( ! empty( $terms ) ) {

	$list_term_vars = array(
		'columns' => $columns,
		'grid_layout_settings' => $grid_layout_settings,
		'type' => $type,
		'load_animation' => $load_animation,
		'overriding_link' => $overriding_link,
	);

	global $us_loop_item_type, $us_loop_term;

	$us_loop_item_type = 'term';

	foreach ( $terms as $term ) {
		$us_loop_term = $term;
		us_load_template( 'templates/loop/item-term', $list_term_vars );
	}

	$us_loop_item_type = NULL;
	$us_loop_term = NULL;
}

us_load_template( 'templates/loop/end', $template_vars + $filled_atts );
