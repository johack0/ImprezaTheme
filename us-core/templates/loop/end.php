<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * End part of List / Carousel output
 */

$output = '</div>'; // .w-grid-list

if ( $source != 'current_wp_query' ) {

	// Reset global $wp_query and $post variables
	wp_reset_query();

	// Reset global $authordata, that is not reseted if author archive is empty, see #5179
	if ( is_author() ) {
		$GLOBALS['authordata'] = get_userdata( get_queried_object_id() );
	}
}

// Reset loop items counter
global $us_loop_item_counter;
$us_loop_item_counter = 0;

// Set the loop end
global $us_in_the_loop;
$us_in_the_loop = FALSE;

// Reset the image size for the next loop element
global $us_loop_img_size;
$us_loop_img_size = NULL;

// Output custom styles from Design settings of every post in List, if it has Post Content with Full Content
if ( $post_content_css = us_compile_css( $us_post_content_design_css ?? array() ) ) {
	$output .= '<style id="grid-post-content-css">' . $post_content_css . '</style>';
}

// Get popup-related data
if ( ! us_amp() AND strpos( $overriding_link, 'popup_post' ) !== FALSE ) {
	$popup_vars = array(
		'popup_width' => $popup_width,
		'popup_arrows' => $popup_arrows,
		'popup_page_template' => $popup_page_template ?? 0,
	);
	$output .= us_get_template( 'templates/loop/end-popup', $popup_vars );
}

// Get data for pagination and filtering
if ( $shortcode_base == 'us_post_list' OR $shortcode_base == 'us_product_list' ) {
	$output .= us_get_template( 'templates/loop/end-pagination', $vars );
}

// Get carousel-related data
if ( strpos( $shortcode_base, 'carousel' ) !== FALSE ) {
	$output .= us_get_template( 'templates/loop/end-carousel', $vars );
}

$output .= '</div>'; // .w-grid

echo $output;

// Output the "No results" block AFTER the "w-grid" div container
if ( $no_results ) {

	// Show nothing
	if ( $no_items_action == 'hide_grid' ) {
		return;
	}

	$classes = $content = '';

	// Get relevant classes to hide the "No results" block according to "Hide on" settings
	if ( $hide_on_classes = us_get_specific_classes_by_shortcode( array( 'hide_on_states' => $hide_on_states ) ) ) {
		$classes .= ' ' . $hide_on_classes;
	}

	// Show the message
	if ( $no_items_action == 'message' ) {
		$classes .= ' type_message';
		$content = $no_items_message ?? us_translate( 'No results found.' );
		$content = strip_tags( $content, '<br><strong>' );
	}

	// Show the Reusable Block
	// DEV: also we avoid a possible recursion: Reusable Block has a Post List with the same Reusable Block in its settings
	global $us_is_page_block_in_no_results;
	if ( $no_items_action == 'page_block' AND ! $us_is_page_block_in_no_results ) {

		$classes .= ' type_page_block';

		// Get translated version if exist
		if ( has_filter( 'us_tr_object_id' ) ) {
			$no_items_page_block = apply_filters( 'us_tr_object_id', $no_items_page_block, 'us_page_block', TRUE );
		}

		if (
			$page_block = get_post( $no_items_page_block )
			AND $page_block->post_type == 'us_page_block'
			AND $page_block->post_status == 'publish'
		) {
			// Define if the content is showing via Reusable Block inside "w-grid-none" block
			$us_is_page_block_in_no_results = TRUE;

			us_add_to_page_block_ids( $no_items_page_block );

			$page_block_content = $page_block->post_content;

			us_add_page_shortcodes_custom_css( $no_items_page_block );

			// Remove [vc_row] and [vc_column]
			$page_block_content = str_replace(
				array(
					'[vc_row]',
					'[/vc_row]',
					'[vc_column]',
					'[/vc_column]',
				), '', $page_block_content
			);
			$page_block_content = preg_replace( '~\[vc_row (.+?)]~', '', $page_block_content );
			$page_block_content = preg_replace( '~\[vc_column (.+?)]~', '', $page_block_content );

			$content = apply_filters( 'us_page_block_the_content', $page_block_content );

			us_remove_from_page_block_ids();

			$us_is_page_block_in_no_results = FALSE;
		}
	}

	echo '<div class="w-grid-none' . $classes . '">' . $content . '</div>';
}
