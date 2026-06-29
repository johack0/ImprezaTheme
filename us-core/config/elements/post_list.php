<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Configuration for shortcode: [us_post_list]
 */

$misc = us_config( 'elements_misc' );
$conditional_params = us_config( 'elements_conditional_options' );
$design_options_params = us_config( 'elements_design_options' );

$source_options = array(
	'all' => us_translate( 'All Posts' ),
	'post__in' => __( 'Selected posts', 'us' ),
	'post__not_in' => __( 'Posts except selected', 'us' ),
	'child_posts_of_current' => __( 'Child posts of the current post', 'us' ),
	'child_posts_of_selected' => __( 'Child posts of selected posts', 'us' ),
	'media' => us_translate_x( 'Media', 'post type general name' ),
	'user_favorite_ids' => __( 'Favorites of the current user', 'us' ),
	'current_wp_query' => __( 'Posts of the current query (archives and search results)', 'us' ),
);

$orderby_options = $post_type_options = $image_sizes_list = array();

if ( us_is_elm_editing_page() ) {
	$_group = '';

	foreach( us_get_list_orderby_params() as $name => $param ) {
		if ( $_group != $param['group'] ) {
			$_group = $param['group'];
		}
		$orderby_options[ $_group ][ $name ] = $param['label'];
	}

	$post_type_options = us_get_loop_post_types( TRUE );
	unset( $post_type_options['attachment'] );

	$image_sizes_list = us_get_image_sizes_list();
}

$orderby_options += array(
	'rand' => us_translate( 'Random' ),
	'post__in' => __( 'Order of selected posts', 'us' ),
	'current_wp_query' => __( 'Order of the current query (archives and search results)', 'us' ),
	'custom' => __( 'Custom Field', 'us' ),
);

// General
$general_params = array(

	'source' => array(
		'title' => us_translate( 'Show' ),
		'type' => 'select',
		'options' => apply_filters( 'us_post_list_source_options', $source_options ),
		'std' => 'all',
		'admin_label' => TRUE,
		'usb_preview' => TRUE,
	),
	'ids' => array(
		'type' => 'autocomplete',
		'search_text' => __( 'Select posts', 'us' ),
		'ajax_data' => array(
			'_nonce' => wp_create_nonce( 'us_ajax_get_post_ids_for_autocomplete' ),
			'action' => 'us_get_post_ids_for_autocomplete',
			'post_type' => array_keys( $post_type_options ),
		),
		'options' => us_is_elm_editing_page() ? us_get_post_ids_for_autocomplete( array_keys( $post_type_options ) ) : array(),
		'is_multiple' => TRUE,
		'is_sortable' => TRUE,
		'std' => '',
		'classes' => 'for_above',
		'show_if' => array( 'source', '=', array( 'post__in', 'post__not_in', 'child_posts_of_selected' ) ),
		'usb_preview' => TRUE,
	),
	'post_type' => array(
		'title' => __( 'Post Type', 'us' ),
		'type' => 'checkboxes',
		'options' => $post_type_options,
		'std' => 'post',
		'show_if' => array( 'source', '!=', array( 'child_posts_of_current', 'current_wp_query', 'media' ) ),
		'usb_preview' => TRUE,
	),	
	'attachment_ids' => array(
		'title' => us_translate( 'Images' ),
		'type' => 'upload',
		'is_multiple' => TRUE,
		'extension' => 'png,jpg,jpeg,gif,svg',
		'dynamic_values' => TRUE,
		'show_if' => array( 'source', '=', 'media' ),
		'usb_preview' => TRUE,
	),
	'include_post_thumbnail' => array(
		'type' => 'switch',
		'switch_text' => __( 'Include Featured image', 'us' ),
		'std' => 0,
		'classes' => 'for_above',
		'show_if' => array( 'source', '=', 'media' ),
		'usb_preview' => TRUE,
	),

	// AUTHOR
	'post_author' => array(
		'title' => us_translate( 'Author' ),
		'type' => 'select',
		'options' => array(
			'any' => __( 'Any', 'us' ),
			'include' => __( 'Selected authors', 'us' ),
			'exclude' => __( 'Exclude selected authors', 'us' ),
			'current_author' => __( 'Author of the current post', 'us' ),
			'current_user' => __( 'Current user', 'us' ),
		),
		'std' => 'any',
		'show_if' => array( 'source', '!=', 'current_wp_query' ),
		'usb_preview' => TRUE,
	),
	'post_author_ids' => array(
		'type' => 'autocomplete',
		'search_text' => __( 'Select authors', 'us' ),
		'is_multiple' => TRUE,
		'ajax_data' => array(
			'_nonce' => wp_create_nonce( 'us_ajax_get_user_ids_for_autocomplete' ),
			'action' => 'us_get_user_ids_for_autocomplete',
		),
		'options' => array(), // will be loaded via ajax
		'std' => '',
		'show_if' => array( 'post_author', '=', array( 'include', 'exclude' ) ),
		'classes' => 'for_above',
		'usb_preview' => TRUE,
	),

	'apply_url_params' => array(
		'type' => 'switch',
		'switch_text' => __( 'Use URL params to show results', 'us' ),
		'std' => 0,
		'show_if' => array( 'source', '!=', array( 'child_posts_of_current', 'child_posts_of_selected', 'current_wp_query' ) ),
	),
	'ignore_sticky_posts' => array(
		'type' => 'switch',
		'switch_text' => __( 'Ignore sticky posts', 'us' ),
		'std' => 1,
		'classes' => 'for_above',
		'show_if' => array( 'post_type', '=', 'post' ),
		'usb_preview' => TRUE,
	),
	'exclude_children' => array(
		'type' => 'switch',
		'switch_text' => __( 'Exclude child posts', 'us' ),
		'std' => 0,
		'classes' => 'for_above',
		'show_if' => array( 'source', '!=', array( 'child_posts_of_current', 'child_posts_of_selected', 'current_wp_query', 'media' ) ),
		'usb_preview' => TRUE,
	),
	'exclude_current_post' => array(
		'type' => 'switch',
		'switch_text' => __( 'Exclude the current post', 'us' ),
		'std' => 1,
		'classes' => 'for_above',
		'show_if' => array( 'source', '!=', array( 'child_posts_of_current', 'current_wp_query', 'media' ) ),
		'usb_preview' => TRUE,
	),
	'exclude_prev_posts' => array(
		'type' => 'switch',
		'switch_text' => __( 'Exclude posts of previous lists', 'us' ),
		'std' => 0,
		'classes' => 'for_above',
		'show_if' => array( 'source', '!=', array( 'child_posts_of_current', 'current_wp_query' ) ),
		'usb_preview' => TRUE,
	),
	'exclude_past_events' => array(
		'type' => 'switch',
		'switch_text' => __( 'Exclude past events', 'us' ),
		'std' => 0,
		'classes' => 'for_above',
		'place_if' => class_exists( 'Tribe__Events__Query' ),
		'show_if' => array(
			array( 'post_type', '=', 'tribe_events' ),
			'or',
			array( 'source', '=', 'current_wp_query' ),
		),
		'usb_preview' => TRUE,
	),

	// OFFSET
	'enable_items_offset' => array(
		'type' => 'switch',
		'switch_text' => __( 'Skip the specified quantity of posts', 'us' ),
		'std' => 0,
		'classes' => 'for_above',
		'show_if' => array( 'source', '!=', array( 'child_posts_of_current', 'child_posts_of_selected' ) ),
		'usb_preview' => TRUE,
	),
	'items_offset' => array(
		'type' => 'slider',
		'options' => array(
			'' => array(
				'min' => 0,
				'max' => 36,
			),
		),
		'std' => '1',
		'classes' => 'for_above',
		'show_if' => array( 'enable_items_offset', '=', '1' ),
		'usb_preview' => TRUE,
	),

	// TAXONOMIES
	'tax_query_relation' => array(
		'title' => __( 'Posts with specific taxonomies', 'us' ),
		'type' => 'select',
		'options' => array(
			'none' => us_translate( 'None' ),
			'AND' => __( 'If EVERY condition below is met', 'us' ),
			'OR' => __( 'If ANY condition below is met', 'us' ),
		),
		'std' => 'none',
		'usb_preview' => TRUE,
	),
	'tax_query' => array(
		'type' => 'group',
		'show_controls' => TRUE,
		'label_for_add_button' => __( 'Add condition', 'us' ),
		'is_sortable' => FALSE,
		'is_accordion' => FALSE,
		'accordion_title' => 'taxonomy',
		'params' => array(
			'operator' => array(
				'title' => __( 'Show posts', 'us' ),
				'type' => 'select',
				'options' => array(
					'IN' => __( 'with ANY of selected terms', 'us' ),
					'AND' => __( 'with ALL selected terms', 'us' ),
					'NOT IN' => __( 'WITHOUT selected terms', 'us' ),
					'CURRENT' => __( 'with the same terms of the current post', 'us' ),
				),
				'std' => 'IN',
			),
			'taxonomy' => array(
				'type' => 'select',
				'options' => us_is_elm_editing_page() ? us_get_taxonomies() : array(),
				'std' => 'category',
				'classes' => 'for_above',
				'admin_label' => TRUE,
			),
			'terms' => array(
				'type' => 'autocomplete',
				'search_text' => __( 'Select terms', 'us' ),
				'is_multiple' => TRUE,
				'is_sortable' => FALSE,
				'ajax_data' => array(
					'_nonce' => wp_create_nonce( 'us_ajax_get_terms_for_autocomplete' ),
					'action' => 'us_get_terms_for_autocomplete',
					'use_term_ids' => TRUE, // use ids instead of slugs
				),
				'options' => array(), // will be loaded via ajax
				'options_filtered_by_param' => 'taxonomy',
				'std' => '',
				'classes' => 'for_above',
				'show_if' => array( 'operator', '!=', array( 'CURRENT' ) ),
			),
			'include_children' => array(
				'type' => 'switch',
				'switch_text' => __( 'Include child terms', 'us' ),
				'std' => 0,
				'classes' => 'for_above',
			),
		),
		'std' => array(
			array(
				'operator' => 'IN',
				'taxonomy' => 'category',
				'terms' => '',
				'include_children' => 0,
			),
		),
		'show_if' => array( 'tax_query_relation', '!=', 'none' ),
		'usb_preview' => TRUE,
	),

	// CUSTOM FIELDS
	'meta_query_relation' => array(
		'title' => __( 'Posts with specific custom fields', 'us' ),
		'type' => 'select',
		'options' => array(
			'none' => us_translate( 'None' ),
			'AND' => __( 'If EVERY condition below is met', 'us' ),
			'OR' => __( 'If ANY condition below is met', 'us' ),
		),
		'std' => 'none',
		'usb_preview' => TRUE,
	),
	'meta_query' => array(
		'type' => 'group',
		'show_controls' => TRUE,
		'label_for_add_button' => __( 'Add condition', 'us' ),
		'is_sortable' => FALSE,
		'is_accordion' => FALSE,
		'accordion_title' => 'key',
		'params' => array(
			'key' => array(
				'title' => __( 'Custom Field', 'us' ),
				'placeholder' => us_translate( 'Field name' ),
				'type' => 'text',
				'std' => 'custom_field_name',
				'admin_label' => TRUE,
			),
			'compare' => array(
				'type' => 'select',
				'options' => array(
					'=' => '=',
					'!=' => '!=',
					'>' => '>',
					'>=' => '≥',
					'<' => '<',
					'<=' => '≤',
					'LIKE' => __( 'Includes', 'us' ),
					'NOT LIKE' => __( 'Excludes', 'us' ),
					'EXISTS' => __( 'Has a value', 'us' ),
					'NOT EXISTS' => __( 'Doesn\'t have a value', 'us' ),
				),
				'std' => '=',
				'classes' => 'for_above',
			),
			'value' => array(
				'placeholder' => us_translate( 'Value' ),
				'type' => 'text',
				'std' => '',
				'show_if' => array( 'compare', '!=', array( 'EXISTS', 'NOT EXISTS' ) ),
				'classes' => 'for_above',
			),
		),
		'std' => array(
			array(
				'key' => 'custom_field_name',
				'compare' => '=',
				'value' => '',
			),
		),
		'show_if' => array( 'meta_query_relation', '!=', 'none' ),
		'usb_preview' => TRUE,
	),
);

$order_pagination_params = array(

	// ORDER
	'orderby' => array(
		'title' => __( 'Order by', 'us' ),
		'type' => 'select',
		'options' => apply_filters( 'us_post_list_orderby_options', $orderby_options ),
		'std' => 'date',
		'show_if' => array( 'source', '!=', 'recently_viewed' ),
		'group' => __( 'Order & Quantity', 'us' ),
		'usb_preview' => TRUE,
	),
	'orderby_custom_field' => array(
		'description' => __( 'Enter custom field name to order items by its value', 'us' ),
		'type' => 'text',
		'std' => '',
		'classes' => 'for_above',
		'show_if' => array( 'orderby', '=', 'custom' ),
		'group' => __( 'Order & Quantity', 'us' ),
		'usb_preview' => TRUE,
	),
	'orderby_custom_type' => array(
		'type' => 'switch',
		'switch_text' => __( 'Order by numeric values', 'us' ),
		'std' => 0,
		'classes' => 'for_above',
		'show_if' => array( 'orderby', '=', 'custom' ),
		'group' => __( 'Order & Quantity', 'us' ),
		'usb_preview' => TRUE,
	),
	'order_invert' => array(
		'type' => 'switch',
		'switch_text' => __( 'Invert order', 'us' ),
		'std' => 0,
		'classes' => 'for_above',
		'show_if' => array( 'orderby', '!=', array( 'rand', 'post__in', 'current_wp_query' ) ),
		'group' => __( 'Order & Quantity', 'us' ),
		'usb_preview' => TRUE,
	),

	// QUANTITY
	'show_all' => array(
		'title' => __( 'Quantity', 'us' ),
		'type' => 'switch',
		'switch_text' => __( 'Show all posts', 'us' ),
		'std' => 0,
		'show_if' => array( 'source', '!=', 'current_wp_query' ),
		'group' => __( 'Order & Quantity', 'us' ),
		'usb_preview' => TRUE,
	),
	'quantity' => array(
		'type' => 'slider',
		'options' => array(
			'' => array(
				'min' => 1,
				'max' => 30,
			),
		),
		'std' => '12',
		'classes' => 'for_above',
		'show_if' => array( 'show_all', '=', 0 ),
		'group' => __( 'Order & Quantity', 'us' ),
		'usb_preview' => TRUE,
	),
	'posts_per_archive_page' => array(
		'title' => __( 'Posts per page', 'us' ),
		'description' => sprintf( __( 'If set to 0, the "%s" setting will be used.', 'us' ), '<a href="' . admin_url( 'options-reading.php' ) . '" target="_blank">' . us_translate( 'Blog pages show at most' ) . '</a>' ),
		'type' => 'slider',
		'options' => array(
			'' => array(
				'min' => 0,
				'max' => 30,
			),
		),
		'std' => 0,
		'show_if' => array( 'source', '=', 'current_wp_query' ),
		'group' => __( 'Order & Quantity', 'us' ),
		'usb_preview' => TRUE,
	),

	// NO RESULTS
	'no_items_action'=> array(
		'title' => __( 'Action when no results found', 'us' ),
		'type' => 'select',
		'options' => array(
			'message' => __( 'Show the message', 'us' ),
			'page_block' => __( 'Show the Reusable Block', 'us' ),
			'hide_grid' => __( 'Hide this element', 'us' ),
		),
		'std' => 'message',
		'group' => __( 'Order & Quantity', 'us' ),
		'usb_preview' => TRUE,
	),
	'no_items_message' => array(
		'type' => 'text',
		'std' => us_translate( 'No results found.' ),
		'classes' => 'for_above',
		'show_if' => array( 'no_items_action', '=', 'message' ),
		'group' => __( 'Order & Quantity', 'us' ),
		'usb_preview' => array(
			'elm' => '.w-grid-none',
			'attr' => 'html',
		),
	),
	'no_items_page_block' => array(
		'options' => us_is_elm_editing_page()
			? array( '' => '– ' . us_translate( 'None' ) . ' –' ) + us_get_posts_titles_for( 'us_page_block' )
			: array(),
		'type' => 'select',
		'hints_for' => 'us_page_block',
		'std' => '',
		'classes' => 'for_above',
		'show_if' => array( 'no_items_action', '=', 'page_block' ),
		'group' => __( 'Order & Quantity', 'us' ),
		'usb_preview' => TRUE,
	),

	// PAGINATION
	'pagination' => array(
		'title' => us_translate( 'Pagination' ),
		'type' => 'select',
		'options' => array(
			'none' => us_translate( 'None' ),
			'numbered' => __( 'Numbered pagination', 'us' ),
			'numbered_ajax' => __( 'Numbered without page reloading', 'us' ),
			'load_on_btn' => __( 'Load posts on button click', 'us' ),
			'load_on_scroll' => __( 'Load posts on page scroll', 'us' ),
		),
		'std' => 'none',
		'group' => __( 'Order & Quantity', 'us' ),
		'usb_preview' => TRUE,
		'exclude_for_carousel' => TRUE,
	),
	'pagination_style' => array(
		'title' => __( 'Pagination Style', 'us' ),
		'description' => $misc['desc_btn_styles'],
		'type' => 'select',
		'options' => us_array_merge(
			array(
				'' => '– ' . us_translate( 'Default' ) . ' –',
			), us_get_btn_styles()
		),
		'std' => '',
		'show_if' => array( 'pagination', '=', array( 'numbered', 'numbered_ajax' ) ),
		'group' => __( 'Order & Quantity', 'us' ),
		'usb_preview' => array(
			array(
				'elm' => 'nav.pagination:first > .nav-links',
				'toggle_class' => 'custom',
			),
			array(
				'elm' => 'nav.pagination:first > .nav-links',
				'mod' => 'us-nav-style',
			),
		),
		'exclude_for_carousel' => TRUE,
	),
	'pagination_btn_text' => array(
		'title' => __( 'Button Label', 'us' ),
		'type' => 'text',
		'dynamic_values' => TRUE,
		'std' => __( 'Load More', 'us' ),
		'cols' => 2,
		'show_if' => array( 'pagination', '=', 'load_on_btn' ),
		'group' => __( 'Order & Quantity', 'us' ),
		'usb_preview' => array(
			'elm' => '.g-loadmore:first .w-btn-label',
			'attr' => 'text',
		),
		'exclude_for_carousel' => TRUE,
	),
	'pagination_btn_size' => array(
		'title' => __( 'Button Size', 'us' ),
		'description' => $misc['desc_font_size'],
		'type' => 'text',
		'std' => '',
		'cols' => 2,
		'show_if' => array( 'pagination', '=', 'load_on_btn' ),
		'group' => __( 'Order & Quantity', 'us' ),
		'usb_preview' => array(
			'elm' => '.g-loadmore:first .w-btn',
			'css' => 'font-size',
		),
		'exclude_for_carousel' => TRUE,
	),
	'pagination_btn_style' => array(
		'title' => __( 'Button Style', 'us' ),
		'description' => $misc['desc_btn_styles'],
		'type' => 'select',
		'options' => us_get_btn_styles(),
		'std' => '1',
		'show_if' => array( 'pagination', '=', 'load_on_btn' ),
		'group' => __( 'Order & Quantity', 'us' ),
		'usb_preview' => array(
			'elm' => '.g-loadmore:first .w-btn',
			'mod' => 'us-btn-style',
		),
		'exclude_for_carousel' => TRUE,
	),
	'pagination_btn_fullwidth' => array(
		'type' => 'switch',
		'switch_text' => __( 'Stretch to the full width', 'us' ),
		'std' => 0,
		'show_if' => array( 'pagination', '=', 'load_on_btn' ),
		'group' => __( 'Order & Quantity', 'us' ),
		'usb_preview' => array(
			'elm' => '.g-loadmore:first',
			'toggle_class' => 'width_full',
		),
		'exclude_for_carousel' => TRUE,
	),
);

// Appearance
$appearance_params = array(
	'items_layout' => array(
		'title' => __( 'Grid Layout', 'us' ),
		'description' => $misc['desc_grid_layout'],
		'type' => 'select',
		'options' => us_is_elm_editing_page() ? us_get_grid_layouts_for_selection() : array(),
		'std' => 'blog_1',
		'classes' => 'for_grid_layouts',
		'settings' => array(
			'html-data' => array(
				'edit_link' => admin_url( '/post.php?post=%d&action=edit' ),
			),
		),
		'admin_label' => TRUE,
		'group' => us_translate( 'Appearance' ),
		'usb_preview' => TRUE,
	),
	'type' => array(
		'title' => __( 'Display as', 'us' ),
		'type' => 'select',
		'options' => array(
			'grid' => __( 'Grid', 'us' ),
			'masonry' => __( 'Masonry', 'us' ),
			'metro' => __( 'METRO', 'us' ),
		),
		'std' => 'grid',
		'group' => us_translate( 'Appearance' ),
		'usb_preview' => TRUE,
		'exclude_for_carousel' => TRUE,
	),
	'items_valign' => array(
		'switch_text' => __( 'Center items vertically', 'us' ),
		'type' => 'switch',
		'std' => 0,
		'classes' => 'for_above',
		'show_if' => array( 'type', '=', 'grid' ),
		'group' => us_translate( 'Appearance' ),
		'usb_preview' => array(
			'elm' => '.w-grid',
			'toggle_class' => 'valign_center',
		),
		'exclude_for_carousel' => TRUE,
	),
	'ignore_items_size' => array(
		'switch_text' => __( 'Ignore items custom size', 'us' ),
		'type' => 'switch',
		'std' => 0,
		'classes' => 'for_above',
		'show_if' => array( 'type', '!=', 'metro' ),
		'group' => us_translate( 'Appearance' ),
		'usb_preview' => TRUE,
		'exclude_for_carousel' => TRUE,
	),
	'columns' => array(
		'title' => us_translate( 'Columns' ),
		'type' => 'slider',
		'options' => array(
			'' => array(
				'min' => 1,
				'max' => 10,
			),
		),
		'std' => '4',
		'admin_label' => TRUE,
		'cols' => 2,
		'show_if' => array( 'type', '!=', 'metro' ),
		'group' => us_translate( 'Appearance' ),
		'usb_preview' => array(
			array(
				'elm' => '.w-grid',
				'mod' => 'cols',
			),
			array(
				'elm' => '.w-grid',
				'css' => '--columns',
			),
			array(
				'elm' => '.w-grid.with_isotope',
				'trigger' => 'usbReloadIsotopeLayout',
			),
		),
		'exclude_for_carousel' => TRUE,
	),
	'items_gap' => array(
		'title' => __( 'Gap between Items', 'us' ),
		'type' => 'slider',
		'std' => '1.5rem',
		'options' => $misc['items_gap'],
		'cols' => 2,
		'group' => us_translate( 'Appearance' ),
		'usb_preview' => array(
			array(
				'elm' => '.w-grid',
				'css' => '--gap',
			),
			array(
				'elm' => '.w-grid.with_isotope',
				'trigger' => 'usbReloadIsotopeLayout',
			),
		),
	),
	'load_animation' => array(
		'title' => __( 'Items animation on load', 'us' ),
		'type' => 'select',
		'options' => array(
			'none' => us_translate( 'None' ),
			'fade' => __( 'Fade', 'us' ),
			'afc' => __( 'Appear From Center', 'us' ),
			'afl' => __( 'Appear From Left', 'us' ),
			'afr' => __( 'Appear From Right', 'us' ),
			'afb' => __( 'Appear From Bottom', 'us' ),
			'aft' => __( 'Appear From Top', 'us' ),
			'hfc' => __( 'Height Stretch', 'us' ),
			'wfc' => __( 'Width Stretch', 'us' ),
		),
		'std' => 'none',
		'group' => us_translate( 'Appearance' ),
		'exclude_for_carousel' => TRUE,
	),
	'items_preload_style' => array(
		'title' => __( 'Preload Style', 'us' ),
		'description' => __( 'What will be displayed in the current list while filtering.', 'us' ),
		'type' => 'select',
		'options' => array(
			'spinner' => __( 'Spinner', 'us' ),
			'fade' => __( 'Faded Items', 'us' ),
			'placeholders' => __( 'Animated Placeholders', 'us' ),
			'none' => us_translate( 'None' ),
		),
		'std' => 'spinner',
		'group' => us_translate( 'Appearance' ),
		'exclude_for_carousel' => TRUE,
	),
	'img_size' => array(
		'title' => __( 'Post Image Size', 'us' ),
		'description' => $misc['desc_img_sizes'],
		'type' => 'select',
		'options' => array( 'default' => __( 'As in Grid Layout', 'us' ) ) + $image_sizes_list,
		'std' => 'default',
		'cols' => 2,
		'group' => us_translate( 'Appearance' ),
		'usb_preview' => TRUE,
	),
	'img_aspect_ratio' => array(
		'title' => __( 'Post Image Aspect Ratio', 'us' ),
		'description' => $misc['desc_aspect_ratio'],
		'type' => 'text',
		'std' => '',
		'cols' => 2,
		'group' => us_translate( 'Appearance' ),
		'usb_preview' => array(
			'elm' => '.w-grid',
			'css' => '--img-aspect-ratio',
		),
	),
	'title_size' => array(
		'title' => __( 'Post Title Size', 'us' ),
		'description' => $misc['desc_font_size'],
		'type' => 'text',
		'std' => '',
		'group' => us_translate( 'Appearance' ),
		'usb_preview' => array(
			'elm' => '.w-grid',
			'css' => '--title-font-size',
		),
	),
	'item_aspect_ratio' => array(
		'title' => __( 'Items Aspect Ratio', 'us' ),
		'description' => $misc['desc_aspect_ratio'],
		'type' => 'text',
		'std' => '',
		'group' => us_translate( 'Appearance' ),
		'usb_preview' => array(
			'elm' => '.w-grid',
			'css' => '--item-aspect-ratio',
		),
	),
	'overriding_link' => array(
		'title' => __( 'Overriding Link', 'us' ),
		'description' => __( 'Applies to every post of this list.', 'us' ) . ' ' . __( 'All inner elements become not clickable.', 'us' ),
		'type' => 'link',
		'dynamic_values' => array(
			'post' => array(
				'post' => __( 'Post Link', 'us' ),
				'popup_post' => __( 'Open Post Page in a Popup', 'us' ),
				'popup_image' => __( 'Open Post Image in a Popup', 'us' ),
				'custom_field|us_tile_link' => sprintf( '%s: %s', __( 'Additional Settings', 'us' ), __( 'Custom Link', 'us' ) ),
			),
			'media' => array(
				'custom_field|us_attachment_link' => __( 'Custom Link', 'us' ),
			),
		),
		'std' => '{"url":""}',
		'group' => us_translate( 'Appearance' ),
		'usb_preview' => TRUE,
	),
	'popup_page_template' => array(
		'title' => __( 'Page Template', 'us' ),
		'type' => 'select',
		'options' => us_is_elm_editing_page()
			? array( '0' => '– ' . __( 'As in Theme Options', 'us' ) . ' –' ) + us_get_posts_titles_for( 'us_content_template' )
			: array(),
		'std' => '0',
		'show_if' => array( 'overriding_link', 'str_contains', 'popup_post' ),
		'group' => us_translate( 'Appearance' ),
	),
	'popup_width' => array(
		'title' => __( 'Popup Width', 'us' ),
		'description' => $misc['desc_width'],
		'type' => 'text',
		'std' => '',
		'show_if' => array( 'overriding_link', 'str_contains', 'popup_post' ),
		'group' => us_translate( 'Appearance' ),
	),
	'popup_arrows' => array(
		'switch_text' => __( 'Prev/Next arrows', 'us' ),
		'type' => 'switch',
		'std' => 1,
		'show_if' => array( 'overriding_link', 'str_contains', 'popup_post' ),
		'group' => us_translate( 'Appearance' ),
	),
);

// Responsive Options
$responsive_params = us_config( 'elements_responsive_options' );

/**
 * @return array
 */
return array(
	'title' => __( 'Post List', 'us' ),
	'category' => __( 'Lists', 'us' ),
	'icon' => 'fas fa-th-large',
	'class' => 'improve_list_elm_ui',
	'params' => us_set_params_weight(
		$general_params,
		$order_pagination_params,
		$appearance_params,
		$responsive_params,
		$conditional_params,
		$design_options_params
	),
	'fallback_params' => array(
		'items_ratio',
		'items_ratio_width',
		'items_ratio_height'
	),
	'usb_init_js' => '
		$elm.usPostList();
		$us.$window.trigger( \'scroll.waypoints\' );
		jQuery( \'[data-content-height]\', $elm ).usCollapsibleContent()
	',
);
