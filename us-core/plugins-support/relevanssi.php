<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Relevanssi – A Better Search
 *
 * https://wordpress.org/plugins/relevanssi
 */

if ( ! function_exists( 'relevanssi_init' ) ) {
	return;
}

if ( ! function_exists( 'us_relevanssi_search_for_post_list' ) ) {

	add_filter( 'relevanssi_search_ok', 'us_relevanssi_search_for_post_list', 501, 2 );

	/**
	 * Relevanssi search for Post/Product List
	 *
	 * @param bool $search_ok
	 * @param WP_Query|FALSE $wp_query
	 *
	 * @return bool
	 */
	function us_relevanssi_search_for_post_list( $search_ok, $wp_query ) {

		if ( $wp_query instanceof WP_Query AND $wp_query->get( 'apply_list_url_params' ) AND $wp_query->get( 's' ) ) {
			$search_ok = TRUE;
		}

		return $search_ok;
	}
}

if ( ! function_exists( 'us_relevanssi_search_list_order' ) ) {

	add_filter( 'us_apply_orderby_to_list_query', 'us_relevanssi_search_list_order', 100, 2 );

	/**
	 * Relevanssi support for List Order
	 *
	 * @param array $query_args
	 * @param string $orderby
	 *
	 * @return array
	 */
	function us_relevanssi_search_list_order( $query_args, $orderby ) {

		if ( empty( $query_args['s'] ) ) {
			return $query_args;
		}

		// Relevanssi has own logic for ordering and doesn't support ordering by ID
		if ( isset( $query_args['orderby'] ) ) {
			$query_args['orderby'] = str_replace( ' ID', '', $query_args['orderby'] );
		}

		// Return INNER JOIN logic to ordering by price
		// NOTE: By default we use specific hack for ordering by price (see "us_apply_orderby_to_list_query" filters)
		if (
			$orderby === 'price'
			OR ( isset( $query_args['meta_key'] ) AND $query_args['meta_key'] === '_price' )
		) {

			if ( isset( $query_args['meta_query'] ) ) {
				unset( $query_args['meta_query'] );
			}

			$query_args['meta_key'] = '_price';
			$query_args['orderby']  = 'meta_value_num';
		}

		return $query_args;
	}
}
