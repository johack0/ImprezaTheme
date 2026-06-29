<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * (!) DEPRECATED TEMPLATE. Only used in the 'us_grid' and 'us_carousel' elements
 *
 * @action Before the template: 'us_before_template:templates/us_grid/listing'
 * @action After the template: 'us_after_template:templates/us_grid/listing'
 * @filter Template variables: 'us_template_vars:templates/us_grid/listing'
 *
 * Note: An example of a correct element structure:
 * 		<div class="w-grid">....</div>
 * 		<div class="w-grid-none hidden"></div>
 */
global $us_is_page_block_in_menu, $us_is_page_block_in_no_results, $us_is_page_has_current_query_grid;

$us_grid_index = $us_grid_index ?? 0;
$post_id = $post_id ?? NULL;
$is_widget = $is_widget ?? FALSE;
$classes = $classes ?? '';
$filter_taxonomy_name = $filter_taxonomy_name ?? '';
$terms = $terms ?? FALSE; // for empty condition
$_default_query_args = $_default_query_args ?? NULL;
$query_args = $query_args ?? array();

// Set unique grid ID
if ( ! empty( $el_id ) ) {
	$grid_elm_id = $el_id;
} elseif (
	usb_is_post_preview()
	OR ( function_exists( 'vc_is_page_editable' ) AND vc_is_page_editable() )
) {
	$grid_elm_id = 'us_grid_' . us_uniqid();
} else {
	$grid_elm_id = 'us_grid_' . $us_grid_index;
}

// For support us_grid_order
$orderby_query_args = isset( $orderby_query_args ) ? $orderby_query_args : array();
$grid_orderby = isset( $grid_orderby ) ? $grid_orderby : NULL;

if ( is_array( $terms ) ) {
	if ( count( $terms ) > 0 ) {
		// for disable $use_custom_query
		$query_args = FALSE;
	} else {
		$terms = FALSE;
	}
}

global $us_loop_item_type;
$us_loop_item_type = ! empty( $terms ) ? 'term' : 'post';

// Check Grid params and use default values from config, if its not set
$default_grid_params = us_shortcode_atts( array(), 'us_grid' );
extract( $default_grid_params, EXTR_SKIP );

// Check Carousel params and use default values from config, if its not set
if ( $type == 'carousel' ) {
	$default_carousel_params = us_shortcode_atts( array(), 'us_carousel' );
	extract( $default_carousel_params, EXTR_SKIP );
}

if (
	! $is_widget
	AND ! $us_is_page_block_in_menu
	AND ! $us_is_page_block_in_no_results
	AND ! empty( $post_id )
	AND $type != 'carousel'
) {
	$us_grid_ajax_indexes[ $post_id ] = isset( $us_grid_ajax_indexes[ $post_id ] )
		? ( $us_grid_ajax_indexes[ $post_id ] )
		: 1;
} else {
	$us_grid_ajax_indexes = NULL;
}

// Disable pagination for grid inside specific Reusable Blocks
if ( $us_is_page_block_in_menu OR $us_is_page_block_in_no_results ) {
	$pagination = 'none';
}

// Get Grid Layout settings
$grid_layout_settings = us_get_grid_layout_settings( $items_layout );

/*
 * Set items offset to WP Query flow
 * Needed both for regular us_grid element on page and it's AJAX pagination.
 */
if ( $exclude_items == 'offset' AND $items_offset ) {
	global $us_list_query_offset;
	$us_list_query_offset = absint( $items_offset );
	$query_args['_id'] = 'us_list_with_offset';
	add_action( 'pre_get_posts', 'us_list_query_offset', 1 );
	add_filter( 'found_posts', 'us_list_adjust_offset_pagination', 1, 2 );
}

// Filter and execute database query
global $wp_query;

// Grid Filter parameters obtained through AJAX
if ( ! wp_doing_ajax() OR ! isset( $us_grid_filter_query_string ) ) {
	$us_grid_filter_query_string = NULL;
}

// Combined query parameters
$query_args_from_orderby = array_merge(
	is_array( $query_args ) ? $query_args : array(),
	$orderby_query_args
);

// Get an array of all defined variables
$defined_vars = get_defined_vars();

// Filter for query arguments
$query_args_from_orderby = apply_filters( 'us_grid_listing_query_args', $query_args_from_orderby, $defined_vars );

$use_custom_query = (
	! empty( $query_args )
	AND ! empty( $query_args_from_orderby )
	AND is_array( $query_args_from_orderby )
	AND empty( $terms )
);

if ( $use_custom_query ) {
	us_open_wp_query_context();

	// Run actions before data is received
	do_action( 'us_grid_before_custom_query', $defined_vars );

	$wp_query = new WP_Query( $query_args_from_orderby );

	// Run actions after data is received
	do_action( 'us_grid_after_custom_query', $defined_vars );

	// current query
} elseif ( empty( $terms ) ) {

	$query_args = $wp_query->query;

	// Extracting query arguments from WP_Query that are not shown but relevant
	if ( ! isset( $query_args['post_type'] ) ) {
		$request_where = substr( $wp_query->request, strpos( $wp_query->request, 'WHERE' ) );
		if ( preg_match_all( '~\.post_type = \'([a-z0-9\_\-]+)\'~', $request_where, $matches ) ) {
			$query_args['post_type'] = $matches[1];
		} elseif ( preg_match( '~\.post_type IN (\((\'([a-z0-9\_\-]+)\'(, )?)+\))~', $request_where, $matches ) ) {
			$post_types_str = substr( $matches[1], 2, - 2 );
			$post_types = explode( "', '", $post_types_str );
			$query_args['post_type'] = $post_types;
		}

	}
	if ( ! isset( $query_args['post_status'] ) AND preg_match_all( '~\.post_status = \'([a-z]+)\'~', $wp_query->request, $matches ) ) {
		$query_args['post_status'] = $matches[1];
	}
	// Fetching additional params for WooCommerce Products
	if (
		! empty( $query_args['post_type'] )
		AND in_array( 'product', (array) $query_args['post_type'] )
	) {
		if (
			! isset( $query_args['posts_per_page'] )
			AND ! empty( $wp_query->query_vars['posts_per_page'] )
		) {
			$query_args['posts_per_page'] = $wp_query->query_vars['posts_per_page'];
		}
		$order_key = us_get_grid_url_prefix( 'order' );
		if (
			! isset( $_GET[ $order_key ], $orderby_query_args['order'] )
			AND ! empty( $wp_query->query_vars['order'] )
		) {
			$orderby_query_args['order'] = $wp_query->query_vars['order'];
		}
		if (
			! isset( $_GET[ $order_key ], $orderby_query_args['orderby'] )
			AND ! empty( $wp_query->query_vars['orderby'] )
		) {
			$orderby_query_args['orderby'] = $wp_query->query_vars['orderby'];
		}
	}
	// Tax filter from url
	if ( isset ( $wp_query->tax_query ) ) {
		$query_args['tax_query'] = $wp_query->tax_query->queries;
	}
}
unset( $_query_args );

// Check if the grid have items to output, separately for posts and terms
$no_results = FALSE;
if ( us_post_type_is_available( $post_type, array( 'taxonomy_terms', 'current_child_terms', 'ids_terms' ) ) ) {
	if ( empty( $terms ) ) {
		$no_results = TRUE;
	}

} elseif ( ! have_posts() ) {
	$no_results = TRUE;
}

// Get all needed variables to pass into listing-start & listing-end templates
$template_vars = array(
	'_default_query_args' => $_default_query_args,
	'classes' => $classes,
	'grid_elm_id' => $grid_elm_id,
	'grid_layout_settings' => $grid_layout_settings,
	'ignore_items_size' => $ignore_items_size,
	'is_widget' => $is_widget,
	'items_count' => 0,
	'no_results' => $no_results,
	'orderby_query_args' => $orderby_query_args,
	'post_id' => $post_id,
	'query_args' => $query_args,
	'shortcode_base' => $shortcode_base,
	'us_grid_post_type' => $us_grid_post_type ?? NULL,
	'us_grid_ajax_indexes' => $us_grid_ajax_indexes,
	'us_grid_filter_query_string' => $us_grid_filter_query_string,
	'us_grid_index' => $us_grid_index,
	'wp_query' => $wp_query,
);

// Generate Filter Bar HTML
if (
	! us_amp()
	AND ! $is_widget
	AND ! $us_is_page_block_in_menu
	AND ! $us_is_page_block_in_no_results
	AND $type != 'carousel'
	AND $pagination != 'regular'
	AND ! empty( $filter_taxonomy_name )
	AND $filter_taxonomies
) {
	// $categories_names already contains only the used categories
	$filter_html = '<div class="g-filters ' . $filter_style . ' align_' . $filter_align . '">';

	$active_item_class = ' active';
	// Output "All" item
	if ( $filter_show_all ) {
		$filter_html .= '<button class="g-filters-item' . $active_item_class . '" data-taxonomy="*">';
		$filter_html .= '<span>' . __( 'All', 'us' ) . '</span>';
		$filter_html .= '</button>';
		$active_item_class = '';
	}

	// Output taxonomy Items
	foreach ( $filter_taxonomies as $filter_taxonomy ) {
		$filter_html .= '<button class="g-filters-item' . $active_item_class . '"';
		$filter_html .= ' data-taxonomy="' . $filter_taxonomy->slug . '"';
		$filter_html .= ' data-amount="' . $filter_taxonomy->count . '"';
		$filter_html .= '>';
		$filter_html .= '<span>' . $filter_taxonomy->name . '</span>';
		$filter_html .= '<span class="g-filters-item-amount">' . $filter_taxonomy->count . '</span>';
		$filter_html .= '</button>';
		$active_item_class = '';
	}

	$filter_html .= '</div>';

	$data_atts['data-filter_taxonomy_name'] = $filter_taxonomy_name;

	if ( ! $filter_show_all ) {
		$filter_default_taxonomies = $filter_taxonomies[0]->slug;
		$data_atts['data-filter_default_taxonomies'] = $filter_default_taxonomies;

	} elseif ( ! empty( $filter_default_taxonomies ) ) {
		$data_atts['data-filter_default_taxonomies'] = $filter_default_taxonomies;
	}

	$template_vars['filter_html'] = $filter_html;
	$template_vars['list_data_atts'] = $data_atts;
	$template_vars['classes'] .= ' with_filters';
}

// Add default values for unset variables from Grid config
foreach ( $default_grid_params as $param => $value ) {
	$template_vars[ $param ] = $$param ?? $value;
}

// Add default values for unset variables from Carousel config
if ( $type == 'carousel' ) {
	foreach ( $default_carousel_params as $param => $value ) {
		$template_vars[ $param ] = $$param ?? $value;
	}

	// Fallback for source
	if ( $post_type == 'current_query' ) {
		$template_vars['source'] = 'current_wp_query';
	} else {
		$template_vars['source'] = $post_type;
	}

	// Fallback for old Arrows settings
	$arrows_offset = $arrows_offset ?: '0px';
	$items_gap = $items_gap ?: '0px';

	// Possible cases with old content values
	if ( $items_gap == 'px' ) {
		$items_gap = '0px';
	}
	if ( $items == '1' ) {
		$items_gap = '0px';
	}

	if ( $arrows_pos == 'inside' ) {
		$_sign = '-';
		$template_vars['arrows_hor_pos'] = 'on_sides_inside';
	} else {
		$_sign = '+';
		$template_vars['arrows_hor_pos'] = 'on_sides_outside';
	}

	$template_vars['arrows_hor_offset'] = sprintf( 'calc(%s %s %s)', $arrows_offset, $_sign, $items_gap );

	if ( $arrows_style == 'square' ) {
		$template_vars['arrows_ver_pos'] = 'stretch';
	} else {
		$template_vars['arrows_ver_pos'] = 'middle';
	}
	$template_vars['arrows_ver_offset'] = '0px';
	$template_vars['arrows_gap'] = '10px';
	$template_vars['arrows_disabled'] = 'hide';
}

// If the Grid of type 'current_query' is output on the archive or search page,
// by default we will bind it to filters if there are
if (
	$post_type === 'current_query'
	AND (
		is_archive()
		OR is_search()
	)
) {
	$template_vars['classes'] .= ' used_by_grid_filter';
}

// Define if the Grid is available for filtering via Grid Filter and sorting via Grid Order
if (
	empty( $filter_html )
	AND $type !== 'carousel'
	AND us_get_loop_item_type() !== 'term' // skip because it is not possible to filter terms through grid filters
	AND ! $us_is_page_block_in_menu // skip for grid inside Reusable Block in Header Menu
	AND ! $us_is_page_block_in_no_results // skip for grid inside Reusable Block in "no results"
) {
	if ( is_archive() OR is_search() ) {
		$template_vars['grid_data_atts']['data-filterable'] = 'true';

	} elseif (
		! $us_is_page_has_current_query_grid // skip on the archive page when there is a current_query grid on the page
		AND ! us_post_type_is_available( $post_type, array(
			'ids',
			'ids_terms',
			'taxonomy_terms',
			'current_child_terms',
		) )
	) {
		$template_vars['grid_data_atts']['data-filterable'] = 'true';
	}
}

// Load listing Start
us_load_template( 'templates/us_grid/listing-start', $template_vars );

// Design settings of Post Content inner elements
global $us_post_content_design_css;
if ( ! $us_post_content_design_css ) {
	$us_post_content_design_css = array();
}

$is_enabled_full_content = FALSE;
if ( ! empty( $grid_layout_settings ) AND is_array( $grid_layout_settings ) ) {
	$is_enabled_full_content = strpos( json_encode( $grid_layout_settings ), '"full_content"' ) !== FALSE;
}

// If there are no results, then we will skip this part of the block and save only the grid structure
if ( ! $no_results ) {

	// Define variables which needed in the item template
	$item_vars = array(
		'columns' => $columns,
		'grid_layout_settings' => $grid_layout_settings,
		'type' => $type,
		'ignore_items_size' => $ignore_items_size,
		'load_animation' => $load_animation,
		'overriding_link' => $overriding_link,
		'is_widget' => $is_widget,
	);

	if ( empty( $terms ) ) {
		$template_vars['items_count'] = $wp_query->post_count;

		while ( have_posts() ) {
			the_post();

			// Collect Design settings of content elements inside every post
			if (
				$is_enabled_full_content
				AND $elm_design_settings = get_post_meta( get_the_ID(), '_us_jsoncss_data', TRUE )
				AND is_array( $elm_design_settings )
			) {
				foreach ( $elm_design_settings as $_elm_settings ) {
					us_append_elm_design_settings( $_elm_settings, $us_post_content_design_css );
				}
			}

			us_load_template( 'templates/us_grid/listing-post', $item_vars );
		}

	} else {
		global $us_loop_item_type, $us_loop_term;

		$us_loop_item_type = 'term';

		$template_vars['items_count'] = count( $terms );

		foreach ( $terms as $term ) {
			$us_loop_term = $term;
			us_load_template( 'templates/us_grid/listing-term', $item_vars );
		}

		$us_loop_item_type = NULL;
		$us_loop_term = NULL;
	}

	// Fix for multi-filter ajax pagination
	if ( isset( $paged ) ) {
		$template_vars['paged'] = (int) $paged;
	}
}

// Additional variables
$template_vars['grid_orderby'] = $grid_orderby;
$template_vars['us_post_content_design_css'] = $us_post_content_design_css;
$template_vars['use_custom_query'] = $use_custom_query;

// Load new carousel-related template to avoid code duplication
if ( $type == 'carousel' ) {
	us_load_template( 'templates/loop/end', $template_vars );

} else {
	us_load_template( 'templates/us_grid/listing-end', $template_vars );
}
