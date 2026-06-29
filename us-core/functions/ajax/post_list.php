<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * AJAX pagination for the Post List shortcode
 */
if ( ! function_exists( 'us_ajax_post_list' ) ) {

	add_action( 'wp_ajax_nopriv_us_ajax_post_list', 'us_ajax_post_list' );
	add_action( 'wp_ajax_us_ajax_post_list', 'us_ajax_post_list' );

	function us_ajax_post_list() {

		add_filter( 'us_get_current_id', 'us_get_current_id_from_list_ajax' );

		$received_vars = us_get_HTTP_POST_json( 'template_vars' );

		if ( ! us_is_valid_ajax_referer() ) {
			wp_die( '0' );
		}

		// Exclude posts of previous lists
		if ( isset( $received_vars['us_post_ids_shown_by_grid'] ) ) {
			global $us_post_ids_shown_by_grid;
			$us_post_ids_shown_by_grid = array_map( 'intval', (array) $received_vars['us_post_ids_shown_by_grid'] );
		}

		// Function 'us_shortcode_atts' works like a white list, it removes all vars that are not specified in the element config.
		// So, another required variables should be set manually below.
		$vars = us_shortcode_atts( $received_vars, 'us_post_list' );

		$vars['shortcode_base'] = 'us_post_list';

		if ( isset( $_POST['paged'] ) ) {
			$vars['paged'] = (int) $_POST['paged'];
		}
		if ( ! empty( $_POST['orderby_random_seed'] ) ) {
			$vars['orderby_random_seed'] = (int) $_POST['orderby_random_seed'];
		}
		if ( ! empty( $_POST['_s'] ) ) {
			$vars['list_search'] = (string) $_POST['_s'];
		}
		if ( ! empty( $_POST['_orderby'] ) ) {
			$vars['list_order'] = (string) $_POST['_orderby'];
		}
		if ( $list_filter = us_get_filter_params_from_request() ) {
			$vars['list_filter'] = $list_filter;
		}

		us_load_template( 'templates/elements/post_list', $vars );

		die;
	}
}

/**
 * AJAX pagination for the Product List shortcode
 */
if ( ! function_exists( 'us_ajax_product_list' ) ) {

	add_action( 'wp_ajax_nopriv_us_ajax_product_list', 'us_ajax_product_list' );
	add_action( 'wp_ajax_us_ajax_product_list', 'us_ajax_product_list' );

	function us_ajax_product_list() {

		add_filter( 'us_get_current_id', 'us_get_current_id_from_list_ajax' );

		$received_vars = us_get_HTTP_POST_json( 'template_vars' );

		if ( ! us_is_valid_ajax_referer() ) {
			wp_die( '0' );
		}

		// Exclude posts of previous lists
		if ( isset( $received_vars['us_post_ids_shown_by_grid'] ) ) {
			global $us_post_ids_shown_by_grid;
			$us_post_ids_shown_by_grid = array_map( 'intval', (array) $received_vars['us_post_ids_shown_by_grid'] );
		}

		// Function 'us_shortcode_atts' works like a white list, it removes all vars that are not specified in the element config.
		// So, another required variables should be set manually below.
		$vars = us_shortcode_atts( $received_vars, 'us_product_list' );

		$vars['shortcode_base'] = 'us_product_list';

		if ( isset( $_POST['paged'] ) ) {
			$vars['paged'] = (int) $_POST['paged'];
		}
		if ( ! empty( $_POST['orderby_random_seed'] ) ) {
			$vars['orderby_random_seed'] = (int) $_POST['orderby_random_seed'];
		}
		if ( ! empty( $_POST['_s'] ) ) {
			$vars['list_search'] = (string) $_POST['_s'];
		}
		if ( ! empty( $_POST['_orderby'] ) ) {
			$vars['list_order'] = (string) $_POST['_orderby'];
		}
		if ( $list_filter = us_get_filter_params_from_request() ) {
			$vars['list_filter'] = $list_filter;
		}

		us_load_template( 'templates/elements/product_list', $vars );

		die;
	}
}

if ( ! function_exists( 'us_get_current_id_from_list_ajax' ) ) {
	/**
	 * Get the current ID from AJAX requests of Post/Product List elements.
	 */
	function us_get_current_id_from_list_ajax( $current_id ) {
		if ( $current_id < 1 AND isset( $_POST['object_id'] ) ) {
			return (int) $_POST['object_id'];
		}
		return $current_id;
	}
}

if ( ! function_exists( 'us_list_filter_post_count' ) ) {

	add_action( 'wp_ajax_nopriv_us_list_filter_post_count', 'us_list_filter_post_count' );
	add_action( 'wp_ajax_us_list_filter_post_count', 'us_list_filter_post_count' );

	/**
	 * Get post count for all List Filter values on page load
	 */
	function us_list_filter_post_count() {

		$query_args_unfiltered = us_get_HTTP_POST_json( 'query_args_unfiltered' );
		$list_filters = us_get_HTTP_POST_json( 'list_filters' );

		$results = us_list_filter_get_post_count( $query_args_unfiltered, $list_filters );

		// Save post count to cache
		if ( us_get_option( 'enable_filter_cache' ) AND ! is_user_logged_in() ) {

			$cache_key = (string) ( $_POST['cache_key'] ?? '' );

			if ( preg_match( '/^[a-f\d]{32}$/i', $cache_key ) ) {
				us_filter_cache()->set( $cache_key, $results );
			}
		}

		if ( $results ) {
			wp_send_json_success( $results );
		}

		wp_send_json_error(
			array(
				'message' => 'Failed to count posts for faceted filters.',
			)
		);
	}
}

if ( ! function_exists( 'us_list_result_counter_total' ) ) {

	add_action( 'wp_ajax_nopriv_us_list_result_counter_total', 'us_list_result_counter_total' );
	add_action( 'wp_ajax_us_list_result_counter_total', 'us_list_result_counter_total' );

	/**
	 * Get found posts from unfiltered Post/Product List WP_Query
	 */
	function us_list_result_counter_total() {

		$query_args_unfiltered = us_get_HTTP_POST_json( 'query_args_unfiltered' );

		$wp_query = new WP_Query( $query_args_unfiltered );

		wp_send_json_success(
			array(
				'total_unfiltered' => $wp_query->found_posts ?? 0,
			)
		);
	}
}
