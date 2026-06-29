<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Template to show single page or any post type
 */

get_header();

$main_atts = array(
	'id' => 'page-content',
	'class' => 'l-main',
);

// Check if a page template is applied to this page.
if (
	$page_template_id = us_get_page_area_id( 'content' )
	AND get_post_status( $page_template_id ) == 'publish'
) {
	$has_content_template = TRUE;
} else {
	$has_content_template = FALSE;
}

if ( usb_is_post_preview() AND ! $has_content_template ) {
	$main_atts['data-usbid'] = 'root_container';
}

if ( us_get_option( 'schema_markup' ) ) {
	$main_atts['itemprop'] = 'mainContentOfPage';
}

?>
<main <?= us_implode_atts( $main_atts ) ?>>
	<?php
	do_action( 'us_before_page' );

	if ( us_get_option( 'enable_sidebar_titlebar' ) ) {
		us_load_template( 'templates/titlebar' );
		us_load_template( 'templates/sidebar', array( 'place' => 'before' ) );
	}

	while ( have_posts() ) {
		the_post();

		if ( $has_content_template ) {
			us_load_template( 'templates/content' );
		} else {
			$the_content = apply_filters( 'the_content', get_the_content() );

			// The page may be paginated itself via <!--nextpage--> tags
			$pagination = us_wp_link_pages();

			// If content has no sections, we'll create them manually
			if (
				! ( function_exists( 'vc_is_page_editable' ) AND vc_is_page_editable() )
				AND (
					get_post_type() == 'tribe_events'
					OR ( ! empty( $the_content ) AND strpos( $the_content, ' class="l-section-h ' ) === FALSE )
				)
			) {
				echo '<section class="l-section height_' . us_get_option( 'row_height', 'medium' ) . '">';
				echo '<div class="l-section-h i-cf">';
				echo $the_content;
				echo $pagination; // append pagination into the same section
				echo '</div>';
				echo '</section>';

			} elseif ( ! empty( $pagination ) ) {
				echo $the_content;
				echo '<section class="l-section height_' . us_get_option( 'row_height', 'medium' ) . '">';
				echo '<div class="l-section-h i-cf">';
				echo $pagination; // append pagination in a separate section
				echo '</div>';
				echo '</section>';

			} else {
				echo $the_content;
			}

			// Post comments
			if (
				( comments_open() OR get_comments_number() )
				AND ! isset( $main_atts['data-usbid'] ) // Do not show the comments in live builder preview mode
			) {
				$show_comments = TRUE;

				// Check comments option of Events Calendar plugin
				if ( function_exists( 'tribe_get_option' ) AND get_post_type() == 'tribe_events' ) {
					$show_comments = tribe_get_option( 'showComments' );
				}

				if ( $show_comments ) {
					?>
				<section class="l-section height_<?= us_get_option( 'row_height', 'medium' ) ?> for_comments">
					<div class="l-section-h i-cf"><?php
						if ( ! us_amp() ) {
							wp_enqueue_script( 'comment-reply' );
						}
						comments_template();
						?></div>
					</section><?php
				}
			}
		}
	}

	if ( us_get_option( 'enable_sidebar_titlebar' ) ) {
		us_load_template( 'templates/sidebar', array( 'place' => 'after' ) );
	}

	do_action( 'us_after_page' );
	?>
</main>
<?php

get_footer();
