<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Output Post Content element
 *
 * @var $us_elm_context string: 'shortcode' / 'grid' / 'header'
 * @var $type string Show: 'excerpt_only' / 'excerpt_content' / 'part_content' / 'full_content'
 * @var $length int Amount of words
 * @var $design_options array
 * @var bool $show_more_toggle
 * @var string $show_more_toggle_height
 *
 * @var $classes string
 * @var $id string
 */

// Do not output on admin pages except ajax requests
if ( is_admin() AND ! wp_doing_ajax() ) {
	return;
}

// Fix #3336 issue with Yoast SEO plugin
if (
	! us_in_the_loop()
	AND wp_doing_ajax()
	AND is_main_query()
	AND ! usb_is_preview()
) {
	return;
}

// Do not output when used in grid for posts with "Link" format
if ( $us_elm_context == 'grid' AND us_get_loop_item_type() == 'post' AND get_post_format() == 'link' ) {
	return;
}

// Do not output when used as shortcode with Excerpt on Search Results page
if (
	$us_elm_context == 'shortcode'
	AND in_array( $type, array( 'excerpt_content', 'excerpt_only' ) )
	AND is_search()
) {
	return;
}

// Check if post_content exists.
if ( ! defined( 'US_POST_CONTENT_EXISTS' ) ) {
	define( 'US_POST_CONTENT_EXISTS', TRUE );
}

// Calculate amount of usage the element with full content to avoid infinite recursion
global $us_full_content_stack;
if ( ! is_numeric( $us_full_content_stack ) ) {
	$us_full_content_stack = 0;
}

if ( $us_full_content_stack > 10 AND $type == 'full_content' ) {
	echo '<h5 style="text-align:center; margin-top:20vh; padding:5%;">Post Content outputs itself infinitely. Fix layout of this page.</h5>';

	return;
}

// Define when the Post Content element outputs its content inside Grid Layout
global $us_post_content_in_grid_outputs_content;
if ( $us_elm_context == 'grid' ) {
	$us_post_content_in_grid_outputs_content = TRUE;
}

// Find Post Image element with media preview in Reusable Block
global $us_page_block_ids;
$strip_from_the_content = FALSE;
if ( ! empty( $us_page_block_ids ) ) {
	$page_block = get_post( $us_page_block_ids[0] );

	// Find Post Image element
	if ( preg_match( '~\[us_post_image.+media_preview="1".+?\]~', $page_block->post_content ) ) {
		$strip_from_the_content = TRUE;
	}
}

// Adding ID of the post, which content we will output now, to the contexts list
us_add_to_page_block_ids( get_the_ID() );

if ( $type == 'full_content' ) {

	// Counting amount of nested post content elements with full content output. Used to avoid infinite recursion
	$us_full_content_stack ++;

	// If rows are removed, define a constant for the Live Builder.
	if (
		usb_is_post_preview()
		AND ! usb_is_template_preview()
		AND ! defined( 'US_POST_CONTENT_REMOVE_ROWS' )
		AND get_the_ID() == get_queried_object_id()
		AND $remove_rows == '1'
	) {
		define( 'US_POST_CONTENT_REMOVE_ROWS', TRUE );
	}
}

// Default case
$the_content = '';

// Get term description as "Excerpt" for Term List
if ( us_in_the_loop() AND us_get_loop_item_type() == 'term' ) {
	global $us_loop_term;
	$the_content = $us_loop_term->description;

	// Limit the amount of words for the Excerpt
	if ( (int) $excerpt_length > 0 ) {
		$the_content = wp_trim_words( $the_content, (int) $excerpt_length );
	}

	// Get term description as "Excerpt" for archive pages
} elseif ( ! us_in_the_loop() AND ( is_category() OR is_tag() OR is_tax() ) ) {
	if ( usb_is_template_preview() ) {
		$the_content = us_config( 'elements/post_content.usb_preview_dummy_data.term_description', '' );
	} else {
		$the_content = do_shortcode( term_description() );
	}

	// Post excerpt is not empty
} elseif (
	in_array( $type, array( 'excerpt_content', 'excerpt_only' ) )
	AND ( has_excerpt() OR usb_is_template_preview() )
) {
	if ( usb_is_template_preview() ) {
		$the_content = us_config( 'elements/post_content.usb_preview_dummy_data.excerpt', '' );
	} else {
		$the_content = do_shortcode( apply_filters( 'the_excerpt', get_the_excerpt() ) );
	}

	// Limit the amount of words for the Excerpt
	if ( (int) $excerpt_length > 0 ) {
		$the_content = wp_trim_words( $the_content, (int) $excerpt_length );
	}

	// Either the excerpt is empty and we show the content instead or we show the content only
} elseif ( in_array( $type, array( 'excerpt_content', 'part_content', 'full_content' ) ) ) {
	global $us_is_search_page_block;

	if ( usb_is_template_preview() ) {
		$the_content = us_config( 'elements/post_content.usb_preview_dummy_data.full_content', '' );

	} elseif (
		get_post_type() == 'attachment'
		AND empty( $us_is_search_page_block ) // Ignore if there is a Reusable Block (templates)
	) {
		$the_content = get_the_content();

	} else {

		// WooCommerce Shop Page content
		if (
			function_exists( 'is_shop' )
			AND is_shop()
			AND $us_elm_context == 'shortcode'
		) {

			if ( ! is_search() AND $shop_page = get_post( wc_get_page_id( 'shop' ) ) ) {
				$the_content = $shop_page->post_content;
			}

			// Search Results Page content
		} elseif (
			! empty( $us_is_search_page_block )
			AND $us_elm_context == 'shortcode'
			AND $search_page = get_post( us_get_option( 'search_page' ) )
		) {
			if ( has_filter( 'us_tr_object_id' ) ) {
				$search_page = get_post( apply_filters( 'us_tr_object_id', $search_page->ID, 'page', TRUE ) );
			}

			// Replacing last post ID at Reusable Blocks stack with actual search page template ID
			us_remove_from_page_block_ids();
			us_add_to_page_block_ids( $search_page->ID );

			$the_content = $search_page->post_content;
			$us_is_search_page_block = FALSE;

			// 404 Error Page content
		} elseif (
			is_404()
			AND $us_elm_context == 'shortcode'
			AND $page_404 = get_post( us_get_option( 'page_404' ) )
		) {
			if ( has_filter( 'us_tr_object_id' ) ) {
				$page_404 = get_post( apply_filters( 'us_tr_object_id', $page_404->ID, 'page', TRUE ) );
			}
			$the_content = $page_404->post_content;

			// Posts Page content
		} elseif (
			is_home()
			AND $us_elm_context == 'shortcode'
			AND $posts_page = get_post( get_option( 'page_for_posts' ) )
		) {
			if ( has_filter( 'us_tr_object_id' ) ) {
				$posts_page = get_post( apply_filters( 'us_tr_object_id', $posts_page->ID, 'page', TRUE ) );
			}
			$the_content = $posts_page->post_content;

			// Default content
		} else {
			$the_content = get_the_content();
		}

		// Remove [vc_row] and [vc_column] if set
		if ( $remove_rows == '1' ) {
			$the_content = str_replace( array( '[vc_row]', '[/vc_row]', '[vc_column]', '[/vc_column]' ), '', $the_content );
			$the_content = preg_replace( '~\[(vc_row|vc_column) (.+?)]~', '', $the_content );

		// Force fullwidth for all [vc_row] if set
		} elseif ( $force_fullwidth_rows ) {
			/**
			 * Set full width for the row
			 *
			 * @param array $matches The matches
			 * @return string Returns corrected shortcode with full width
			 */
			$func_rows_width = function( $matches ) {
				// Replace parameter if given in input
				if ( strpos( $matches[/* input */0], ' width="' ) !== FALSE ) {
					return preg_replace( '/\swidth="(\w+)"/', ' width="full"', $matches[/* input */0] );
				}
				return '[vc_row'. $matches[/* atts */3] .' width="full"]';
			};
			$shortcode_regex = get_shortcode_regex( /* tagnames */array( 'vc_row' ) );
			$the_content = preg_replace_callback( '/' . $shortcode_regex . '/Ui', $func_rows_width, $the_content );
		}

		// Check enabled option show image title and description
		if ( ! $strip_from_the_content AND preg_match( '/\[us_image_slider.+meta="1[^\]]\]/', $the_content ) ) {
			$strip_from_the_content = TRUE;
		}

		// Remove video, audio, slider, gallery from the content for relevant post formats
		us_get_post_preview( $the_content, $strip_from_the_content );

		$the_content = apply_filters( 'the_content', $the_content );
	}

	// Limit the amount of words for the Content
	if ( in_array( $type, array( 'excerpt_content', 'part_content' ) ) AND (int) $length > 0 ) {
		$the_content = wp_trim_words( $the_content, (int) $length );
	}
}

// Add pagination for Full Content only
if ( $type == 'full_content' AND ! usb_is_template_preview() ) {
	$the_content .= us_wp_link_pages();

	if ( usb_is_post_preview() ) {
		$the_content .= usb_get_dropzone_html();
	}
}

// In case of excluding parent Row and Columns, output the content itself
if (
	$type == 'full_content'
	AND ! usb_is_template_preview()
	AND ( $remove_rows == 'parent_row' OR $remove_rows == '1' )
) {
	$output = $the_content;

	// Wrapper container is needed for correct work of Drag & Drop in Live preview
	if ( usb_is_post_preview() ) {
		$output = '<div data-usbid="post_content_root_container" class="remove_rows-' . esc_attr( $remove_rows ) . '">' . $output . '</div>';
	}

} else {

	$_atts['class'] = 'w-post-elm post_content';
	$_atts['class'] .= $classes ?? '';

	// Undicate removed sections inside the content
	if ( $remove_rows == '1' ) {
		$_atts['class'] .= ' without_sections';
	}

	if ( ! empty( $el_id ) AND $us_elm_context == 'shortcode' ) {
		$_atts['id'] = $el_id;
	}

	// Schema.org markup
	if ( us_get_option( 'schema_markup' ) AND $us_elm_context == 'shortcode' ) {
		$_atts['itemprop'] = 'text';
	}

	// Add specific class, when "Show More" is enabled
	if ( $show_more_toggle ) {
		$_atts['class'] .= ' with_collapsible_content';
		$_atts['data-content-height'] = $show_more_toggle_height;
	}

	if ( $type == 'full_content' AND usb_is_post_preview() ) {
		$_atts['data-usbid'] = 'post_content_root_container';
	}

	// Output the element
	$output = '<div' . us_implode_atts( $_atts ) . '>';

	// Additional <div>, when "Show More" is enabled
	if ( $show_more_toggle AND ! us_amp() ) {
		$output .= '<div>';
	}

	$output .= $the_content;

	if ( $show_more_toggle AND ! us_amp() ) {
		$output .= '</div>';
		$output .= '<div class="toggle-links align_' . $show_more_toggle_alignment . '">';
		$output .= '<button class="collapsible-content-more">' . strip_tags( $show_more_toggle_text_more ) . '</button>';
		$output .= '<button class="collapsible-content-less">' . strip_tags( $show_more_toggle_text_less ) . '</button>';
		$output .= '</div>';
	}
	$output .= '</div>';
}

if ( $type == 'full_content' ) {
	$us_full_content_stack --;
}
us_remove_from_page_block_ids();

// Output nothing when no content
if ( $the_content == '' AND ! usb_is_post_preview() ) {
	echo apply_filters( 'us_post_content_output', '', $atts );
} else {
	echo apply_filters( 'us_post_content_output', $output, $atts );
}

$us_post_content_in_grid_outputs_content = FALSE;
