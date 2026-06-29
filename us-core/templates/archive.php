<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * The template for displaying Archive Pages
 */

get_header();

?>
<main id="page-content" class="l-main"<?= ( us_get_option( 'schema_markup' ) ) ? ' itemprop="mainContentOfPage"' : '' ?>>
	<?php
	do_action( 'us_before_archive' );

	if ( us_get_option( 'enable_sidebar_titlebar' ) ) {
		us_load_template( 'templates/titlebar' );
		us_load_template( 'templates/sidebar', array( 'place' => 'before' ) );
	}

	// Check if a Page Template is set...
	if (
		$page_template_id = us_get_page_area_id( 'content' )
		AND get_post_status( $page_template_id ) == 'publish'
		AND ! is_search()
	) {
		us_load_template( 'templates/content' );

		// ...if not, use the default output
	} else {
		$default_shortcodes = '[vc_row][vc_column]';
		$default_shortcodes .= '[us_post_title tag="h1"]';
		$default_shortcodes .= '[us_separator size="small"]';
		$default_shortcodes .= '[us_post_list source="current_wp_query" order_invert="1" pagination="numbered" ignore_items_size="1" img_aspect_ratio="3/2"]';
		$default_shortcodes .= '[/vc_column][/vc_row]';

		echo do_shortcode( $default_shortcodes );
	}

	if ( us_get_option( 'enable_sidebar_titlebar' ) ) {
		us_load_template( 'templates/sidebar', array( 'place' => 'after' ) );
	}

	do_action( 'us_after_archive' );
	?>
</main>
<?php

get_footer();
