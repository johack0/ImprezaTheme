<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Molongui Authorship
 *
 * @link https://wordpress.org/plugins/molongui-authorship/
 */

if ( ! class_exists( '\Molongui\Authorship\MolonguiAuthorship' ) ) {
	return;
}

if ( ! function_exists( 'molongui_us_author_posts_in_post_list' ) ) {

	add_filter( 'us_post_list_query_args', 'molongui_us_author_posts_in_post_list', 2, 10 );

	/**
	 * Display posts in the Post List on the author’s archive page.
	 *
	 * @param array $query_args The query arguments.
	 * @param array $filled_atts The filled atts.
	 *
	 * @return array Returns array of arguments passed to WP_Query.
	 */
	function molongui_us_author_posts_in_post_list( $query_args, $filled_atts ) {

		global $wp_query;

		if (
			$filled_atts['source'] == 'current_wp_query'
			AND $wp_query->is_main_query()
			AND is_author()
			AND strpos( $wp_query->request, '_molongui_author' ) !== FALSE
		) {
			$query_args['author'] = $query_args['author_name'] = '';
			$query_args['meta_query'] = array_merge(
				$query_args['meta_query'],
				(array) $wp_query->get( 'meta_query' )
			);
		}

		return $query_args;
	}
}
