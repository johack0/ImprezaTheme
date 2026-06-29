<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Output all needed data for pagination and filtering a list
 */

$preloader_type = us_get_option( 'preloader' );
if ( ! is_numeric( $preloader_type ) ) {
	$preloader_type = '1';
}

// Output "Load more" block for "Load on button" and "Load on scroll" pagination
if ( in_array( $pagination, array( 'load_on_btn', 'load_on_scroll' ) ) ) {
	echo '<div class="g-loadmore' . ( $pagination_btn_fullwidth ? ' width_full' : '' ) . ( $max_num_pages <= 1 ? ' hidden' : '' ) . '">';

	echo '<div class="g-preloader type_' . $preloader_type . '"><div></div></div>';

	if ( $pagination == 'load_on_btn' ) {
		$btn_params = array(
			'html_atts' => array(
				'class' => 'w-btn ' . us_get_btn_class( $pagination_btn_style ),
			),
			'label' => $pagination_btn_text,
		);
		if ( $pagination_btn_size ) {
			$btn_params['html_atts']['style'] = 'font-size:' . $pagination_btn_size;
		}
		echo us_get_btn( $btn_params );
	}

	echo '</div>'; // .g-loadmore
}

// Output preloader for filtering (List Search, List Order, List Filter)
if ( isset( $items_preload_style ) AND $items_preload_style == 'spinner' ) {
	echo '<div class="g-preloader type_' . $preloader_type . '"><div></div></div>';
}

// For correct work of numbered pagination via ajax we need to get the BASE of the current page URL
// First check if we have reffer URL from ajax request
if ( wp_doing_ajax() ) {
	$current_url = wp_get_referer();
}

// ...if not, get it from the current wp request
if ( empty( $current_url ) ) {
	global $wp;
	$current_url = home_url( $wp->request );
}

// Remove all query strings
$current_url = strtok( $current_url, '?' );

// Remove all "/page/*/" parts
global $wp_rewrite;
if ( preg_match( '/\/'. $wp_rewrite->pagination_base .'\/?([0-9]{1,})\/?/', $current_url, $matches ) ) {
	$current_url = str_replace( $matches[0], '', $current_url );
}
$current_url = trailingslashit( $current_url );

// Get the params of List Search, List Order, List Filter elements
$url_params = array();
if ( ! empty( $_REQUEST['_s'] ) ) {
	$url_params['_s'] = sanitize_text_field( (string) $_REQUEST['_s'] );
}
if ( ! empty( $_REQUEST['_orderby'] ) ) {
	$url_params['_orderby'] = (string) $_REQUEST['_orderby'];
}
if ( $filter_url_params = us_get_filter_params_from_request() ) {
	foreach ( $filter_url_params as $name => $value ) {
		$url_params[ '_' . $name ] = $value;
	}
}

// Search results page has its own URL params, get them for correct ajax filtering
if ( is_search() ) {
	$url_params['s'] = get_query_var( 's' );

	if ( isset( $_GET['post_type'] ) ) {
		$url_params['post_type'] = ( (array) get_query_var( 'post_type' ) )[0]; // always used in WooCommerce search results page
	}
}

// Encode every param value since the browser automatically decodes cyrillic characters in pagination links
$encoded_url_params = array();
foreach( $url_params as $name => $value ) {

	if ( is_array( $value ) ) {
		foreach ( $value as &$val ) {
			$val = rawurlencode( $val );
		}
		unset( $val );

	} else {
		$value = rawurlencode( $value );
	}

	$encoded_url_params[ $name ] = $value;
}

// Numbered pagination
if ( in_array( $pagination, array( 'numbered', 'numbered_ajax' ) ) ) {

	$paginate_links = '';

	if ( $max_num_pages > 1 OR usb_is_preview() ) {

		$paginate_args = array(
			'base' => $current_url . '%_%', // required to output correct links via ajax
			'add_args' => $encoded_url_params,
			'after_page_number' => '</span>',
			'before_page_number' => '<span>',
			'mid_size' => 3,
			'next_text' => '<span>' . us_translate( 'Next' ) . '</span>',
			'prev_text' => '<span>' . us_translate( 'Previous' ) . '</span>',
			'total' => $max_num_pages,
		);

		if ( $pagination == 'numbered_ajax' ) {
			$paginate_args['current'] = $paged;
		}

		// Static front (home) page uses "page" var instead of "paged"
		if ( is_front_page() AND ! us_amp() ) {
			set_query_var( 'paged', get_query_var( 'page' ) );
		}

		$paginate_args = apply_filters( 'us_post_list_paginate_links_args', $paginate_args, $vars );
		$paginate_links = paginate_links( $paginate_args );
	}

	$paginate_class = 'nav-links';
	if ( ! empty( $pagination_style ) ) {
		$paginate_class .= ' custom us-nav-style_' . (int) $pagination_style;
	}
	if ( ! empty( $paginate_links ) ) {
		echo '<nav class="pagination navigation" role="navigation">';
		echo '<div class="' . $paginate_class . '">' . $paginate_links . '</div>';
		echo '</nav>';
	}
}

// Query args unfiltered for "Faceted Filtering"
$query_args_unfiltered = $query_args_unfiltered ?? array();
if ( isset( $vars['query_args_unfiltered'] ) ) {
	unset( $vars['query_args_unfiltered'] );
}

// Disable pagination on scroll in templates as there is no specific post to respond to such request
if (
	usb_is_template_preview()
	AND $pagination == 'load_on_scroll'
	AND $source == 'current_wp_query'
) {
	$pagination = 'none';
}

// Collect data for ajax requests
$json_data = array(
	'max_num_pages' => $max_num_pages,
	'paginationBase' => $wp_rewrite->pagination_base,
	'pagination' => $pagination,
	'paged' => $paged,
	'ajaxData' => array(),
	'facetedFilter' => array(),
);

if ( $source == 'current_wp_query' ) {

	if ( $pagination != 'numbered' ) {
		$encoded_url_params['paged'] = rawurlencode( '{num_page}' );
	}
	$json_data['ajaxUrl'] = add_query_arg( $encoded_url_params, $current_url );

	global $us_post_list_index;
	if ( is_null( $us_post_list_index ) ) {
		$us_post_list_index = 0;
	}
	$json_data['ajaxData'] = array(
		'us_ajax_list_pagination' => 1,
		'us_ajax_list_index' => $us_post_list_index++,
	);
	$json_data['ajaxData']['found_posts'] = $found_posts;

} else {
	$json_data['ajaxData'] = array(
		'action' => ! empty( $is_product_list ) ? 'us_ajax_product_list' : 'us_ajax_post_list',
		'meta_type' => us_get_current_meta_type(),
		'object_id' => us_get_current_id(),
		'template_vars' => $vars,
		'found_posts' => $found_posts,
		'per_page' => $per_page,
	);
	if ( $apply_url_params ) {
		$json_data['ajaxData'] += $url_params;
	}
}

if ( $orderby == 'rand' AND $orderby_random_seed ) {
	$json_data['ajaxData']['orderby_random_seed'] = (int) $orderby_random_seed;
}

// Generate post count data for "Faceted Filtering"
if ( $apply_url_params OR $source == 'current_wp_query' ) {

	$list_filters = us_get_HTTP_POST_json( 'list_filters' ) ?? array();

	global $us_ajax_list_pagination;

	// Get post_count from ajax requests only to avoid heavy queries on page load
	if ( ( wp_doing_ajax() OR $us_ajax_list_pagination ) AND $paged == 1 ) {
		$json_data['facetedFilter']['post_count'] = us_list_filter_get_post_count( $query_args_unfiltered, $list_filters );
	} else {
		$json_data['facetedFilter']['query_args_unfiltered'] = json_encode( $query_args_unfiltered );
	}

	$json_data['ajaxData']['list_filters'] = json_encode( $list_filters );
}

// Required for correct work of ajax pagination with 'posts_per_archive_page' on archives
global $us_page_args;
if ( is_array( $us_page_args ) AND $us_page_args ) {
	$json_data['ajaxData']['page_args'] = $us_page_args;
}

echo '<div class="w-grid-list-json hidden"' . us_pass_data_to_js( $json_data ) . '></div>';
