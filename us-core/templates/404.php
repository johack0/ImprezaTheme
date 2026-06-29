<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * The template for displaying the 404 page
 */

get_header();

// Output specific page
if ( $page_404 = get_post( us_get_option( 'page_404' ) ) ) {

	if ( has_filter( 'us_tr_object_id' ) ) {
		$page_404 = get_post( apply_filters( 'us_tr_object_id', $page_404->ID, 'page', TRUE ) );
	}

	?>
	<main id="page-content" class="l-main"<?= ( us_get_option( 'schema_markup' ) ) ? ' itemprop="mainContentOfPage"' : '' ?>>
		<?php
		do_action( 'us_before_404' );

		if ( us_get_option( 'enable_sidebar_titlebar' ) ) {
			us_load_template( 'templates/titlebar' );
			us_load_template( 'templates/sidebar', array( 'place' => 'before' ) );
		}

		if (
			$page_template_id = us_get_page_area_id( 'content' )
			AND get_post_status( $page_template_id ) == 'publish'
		) {
			us_load_template( 'templates/content' );

		} else {
			us_open_wp_query_context();
			us_add_page_shortcodes_custom_css( $page_404->ID );

			remove_filter( 'the_content', 'prepend_attachment' );

			echo apply_filters( 'the_content', $page_404->post_content );

			us_close_wp_query_context();
		}

		if ( us_get_option( 'enable_sidebar_titlebar' ) ) {
			us_load_template( 'templates/sidebar', array( 'place' => 'after' ) );
		}

		do_action( 'us_after_404' );
		?>
	</main>
	<?php

	// Output predefined layout
} else {
	?>
	<main id="page-content" class="l-main">
		<?php do_action( 'us_before_404' ) ?>
		<section class="l-section height_<?= us_get_option( 'row_height', 'medium' ) ?>">
			<div class="l-section-h i-cf">
				<div class="page-404 align_center">
					<?php
					$the_content = '<h1>' . us_translate( 'Page not found' ) . '</h1>';
					$the_content .= '<p>' . __( 'The link you followed may be broken, or the page may have been removed.', 'us' ) . '</p>';
					echo apply_filters( 'us_404_content', $the_content );
					?>
				</div>
			</div>
		</section>
		<?php do_action( 'us_after_404' ) ?>
	</main>
	<?php
}

get_footer();
