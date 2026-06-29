<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * The template for displaying the Posts page
 */

$posts_page_ID = us_get_page_for_posts();

// Output the content of a page, which is set in Settings > Reading > Posts page
if ( $posts_page = get_post( $posts_page_ID ) AND ! empty( $posts_page->post_content ) ) {

	// If the page has a translated version, use it instead
	if ( has_filter( 'us_tr_object_id' ) ) {
		$posts_page = get_post( (int) apply_filters( 'us_tr_object_id', $posts_page->ID, 'page', TRUE ) );
	}

	get_header();

	?>
	<main id="page-content" class="l-main"<?= ( us_get_option( 'schema_markup' ) ) ? ' itemprop="mainContentOfPage"' : '' ?>>
		<?php
		do_action( 'us_before_page' );

		if ( us_get_option( 'enable_sidebar_titlebar' ) ) {
			us_load_template( 'templates/titlebar' );
			us_load_template( 'templates/sidebar', array( 'place' => 'before' ) );
		}

		// Check if a Page Template is set...
		if (
			$page_template_id = us_get_page_area_id( 'content' )
			AND get_post_status( $page_template_id ) == 'publish'
		) {
			us_load_template( 'templates/content' );

		} else {
			us_open_wp_query_context();
			us_add_page_shortcodes_custom_css( $posts_page->ID );
			us_close_wp_query_context();

			us_add_to_page_block_ids( $posts_page->ID );

			$posts_page_content = $posts_page->post_content;

			// If the page content doesn't have any list, add one with items of the current query
			if ( strpos( $posts_page_content, '[us_post_list' ) === FALSE ) {
				$posts_page_content .= '[vc_row][vc_column]';
				$posts_page_content .= '[us_post_list source="current_wp_query" order_invert="1" pagination="numbered" ignore_items_size="1" img_aspect_ratio="3/2"]';
				$posts_page_content .= '[/vc_column][/vc_row]';
			}

			echo apply_filters( 'the_content', $posts_page_content );

			us_remove_from_page_block_ids();
		}

		if ( us_get_option( 'enable_sidebar_titlebar' ) ) {
			us_load_template( 'templates/sidebar', array( 'place' => 'after' ) );
		}

		do_action( 'us_after_page' );
		?>
	</main>
	<?php

	get_footer();

	// Output default archive layout
} else {
	us_load_template( 'templates/archive' );
}
