<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Grid Layout and Elements Options.
 * Options and elements' fields are described in USOF-style format.
 */

$misc = us_config( 'elements_misc' );

$elements = array(
	'hwrapper',
	'vwrapper',
	'post_title',
	'post_image',
	'post_date',
	'post_taxonomy',
	'post_content',
	'post_custom_field',
	'post_author',
	'post_comments',
	'user_data',
	'user_picture',
	'btn',
	'add_to_favs',
	'text',
	'image',
	'vc_video',
	'image_slider',
	'html',
	'popup',
	'event_date',
);
if ( class_exists( 'Post_Views_Counter' ) ) {
	$elements[] = 'post_views';
}
if ( class_exists( 'woocommerce' ) ) {
	$elements[] = 'product_field';
	$elements[] = 'add_to_cart';
}

// Set image sources for selection
$bg_img_sources = array(
	'none' => us_translate( 'None' ),
	'media' => __( 'Custom', 'us' ),
	'featured' => us_translate_x( 'Featured image', 'post' ),
);
if ( us_get_option( 'enable_additional_settings', 1 ) ) {
	$bg_img_sources[ __( 'Additional Settings', 'us' ) ] = array(
		'us_tile_additional_image' => us_translate( 'Images' ),
	);
}

// Get a list of all fields.
if (
	function_exists( 'us_acf_get_fields' )
	AND us_is_elm_editing_page()
) {
	$bg_img_sources += (array) us_acf_get_fields( /* type */'image', /* to_list */TRUE );
}

$image_sizes_list = us_is_elm_editing_page() ? us_get_image_sizes_list() : array();

return array(

	// Supported elements
	'elements' => $elements,

	// General Options
	'options' => array(
		'global' => array(
			'fixed' => array(
				'switch_text' => __( 'Set Aspect Ratio', 'us' ),
				'type' => 'switch',
				'std' => 0,
			),
			'aspect_ratio' => array(
				'description' => $misc['desc_aspect_ratio'],
				'type' => 'text',
				'std' => '1',
				'classes' => 'for_above',
				'show_if' => array( 'fixed', '=', 1 ),
			),
			'overflow' => array(
				'switch_text' => __( 'Hide Overflowing Content', 'us' ),
				'type' => 'switch',
				'std' => 0,
				'show_if' => array( 'fixed', '=', 0 ),
			),
			'color_bg' => array(
				'title' => __( 'Background Color', 'us' ),
				'type' => 'color',
				'clear_pos' => 'right',
				'std' => '',
			),
			'color_text' => array(
				'title' => __( 'Text Color', 'us' ),
				'type' => 'color',
				'clear_pos' => 'right',
				'with_gradient' => FALSE,
				'std' => '',
			),
			'ignore_us_tile_colors' => array(
				'switch_text' => __( 'Ignore colors from Additional Settings', 'us' ),
				'type' => 'switch',
				'std' => 0,
			),

			// Background
			'bg_img_source' => array(
				'title' => __( 'Background Image', 'us' ),
				'type' => 'select',
				'options' => $bg_img_sources,
				'std' => 'none',
			),
			'bg_img' => array(
				'type' => 'upload',
				'std' => '',
				'classes' => 'for_above',
				'show_if' => array( 'bg_img_source', '=', 'media' ),
			),
			'bg_img_wrapper_start' => array(
				'type' => 'wrapper_start',
				'show_if' => array( 'bg_img_source', '!=', 'none' ),
			),
			'bg_file_size' => array(
				'title' => __( 'Image Size', 'us' ),
				'description' => $misc['desc_img_sizes'],
				'type' => 'select',
				'options' => $image_sizes_list,
				'std' => 'large',
			),
			'bg_img_size' => array(
				'title' => __( 'Background Size', 'us' ),
				'type' => 'radio',
				'options' => array(
					'cover' => __( 'Fill Area', 'us' ),
					'contain' => __( 'Fit to Area', 'us' ),
				),
				'std' => 'cover',
			),
			'bg_img_position' => array(
				'title' => __( 'Background Position', 'us' ),
				'type' => 'radio',
				'labels_as_icons' => 'fas fa-arrow-up',
				'options' => array(
					'top left' => us_translate( 'Top Left' ),
					'top center' => us_translate( 'Top' ),
					'top right' => us_translate( 'Top Right' ),
					'center left' => us_translate( 'Left' ),
					'center center' => us_translate( 'Center' ),
					'center right' => us_translate( 'Right' ),
					'bottom left' => us_translate( 'Bottom Left' ),
					'bottom center' => us_translate( 'Bottom' ),
					'bottom right' => us_translate( 'Bottom Right' ),
				),
				'std' => 'center center',
				'classes' => 'bgpos',
			),
			'bg_img_repeat' => array(
				'title' => __( 'Background Repeat', 'us' ),
				'type' => 'select',
				'options' => array(
					'no-repeat' => us_translate( 'None' ),
					'repeat' => __( 'Repeat', 'us' ),
					'repeat-x' => __( 'Horizontally', 'us' ),
					'repeat-y' => __( 'Vertically', 'us' ),
				),
				'std' => 'no-repeat',
			),
			'bg_img_wrapper_end' => array(
				'type' => 'wrapper_end',
			),

			'border_radius' => array(
				'title' => __( 'Border Radius', 'us' ),
				'description' => $misc['desc_border_radius'],
				'type' => 'text',
				'std' => '',
				'classes' => 'desc_4',
			),
			'box_shadow' => array(
				'title' => __( 'Shadow', 'us' ),
				'type' => 'slider',
				'std' => 0,
				'options' => array(
					'rem' => array(
						'min' => 0.0,
						'max' => 3.0,
						'step' => 0.1,
					),
				),
			),
			'box_shadow_hover' => array(
				'title' => __( 'Shadow on hover', 'us' ),
				'type' => 'slider',
				'std' => 0,
				'options' => array(
					'rem' => array(
						'min' => 0.0,
						'max' => 3.0,
						'step' => 0.1,
					),
				),
			),

			// Extra CSS class
			'el_class' => array(
				'title' => __( 'Extra class', 'us' ),
				'type' => 'text',
				'std' => '',
			),
		),
	),

);
