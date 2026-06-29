<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Configuration for shortcode: image
 */

$misc = us_config( 'elements_misc' );
$conditional_params = us_config( 'elements_conditional_options' );
$design_options_params = us_config( 'elements_design_options' );
$hover_options_params = us_config( 'elements_hover_options' );
$effect_options_params = us_config( 'elements_effect_options' );

/**
 * @return array
 */
return array(
	'title' => us_translate( 'Image' ),
	'category' => __( 'Basic', 'us' ),
	'icon' => 'fas fa-image',
	'params' => us_set_params_weight(

		// General section
		array(
			'img' => array(
				'title' => us_translate( 'Image' ),
				'type' => 'upload',
				'extension' => 'png,jpg,jpeg,gif,svg',
				'context' => array( 'header', 'grid' ),
				'dynamic_values' => TRUE,
				'us_admin_label' => TRUE,
			),
			'image' => array(
				'title' => us_translate( 'Image' ),
				'type' => 'upload',
				'extension' => 'png,jpg,jpeg,gif,svg',
				'context' => array( 'shortcode' ),
				'dynamic_values' => TRUE,
				'usb_preview' => TRUE,
			),
			'disable_lazy_loading' => array(
				'type' => 'switch',
				'switch_text' => __( 'Disable Lazy Loading', 'us' ),
				'std' => 0,
			),
			'meta' => array(
				'type' => 'switch',
				'switch_text' => __( 'Show image title and description', 'us' ),
				'std' => 0,
				'context' => array( 'shortcode' ),
				'usb_preview' => TRUE,
			),
			'meta_style' => array(
				'title' => __( 'Title and Description Style', 'us' ),
				'type' => 'radio',
				'options' => array(
					'simple' => __( 'Below the image', 'us' ),
					'modern' => __( 'Over the image', 'us' ),
				),
				'std' => 'simple',
				'show_if' => array( 'meta', '=', 1 ),
				'context' => array( 'shortcode' ),
				'usb_preview' => TRUE,
			),
			'align' => array(
				'title' => us_translate( 'Alignment' ),
				'type' => 'radio',
				'labels_as_icons' => 'fas fa-align-*',
				'options' => array(
					'none' => us_translate( 'Default' ),
					'left' => us_translate( 'Left' ),
					'center' => us_translate( 'Center' ),
					'right' => us_translate( 'Right' ),
				),
				'std' => 'none',
				'context' => array( 'shortcode' ),
				'usb_preview' => array(
					'mod' => 'align',
				),
			),
			'style' => array(
				'title' => __( 'Image Style', 'us' ),
				'type' => 'select',
				'options' => array(
					'' => us_translate( 'None' ),
					'circle' => __( 'Circle', 'us' ),
					'outlined' => __( 'Outlined', 'us' ),
					'shadow-1' => __( 'Simple Shadow', 'us' ),
					'shadow-2' => __( 'Colored Shadow', 'us' ),
					'phone12' => __( 'Phone 12 Flat', 'us' ),
					'phone6-1' => __( 'Phone 6 Black Realistic', 'us' ),
					'phone6-2' => __( 'Phone 6 White Realistic', 'us' ),
					'phone6-3' => __( 'Phone 6 Black Flat', 'us' ),
					'phone6-4' => __( 'Phone 6 White Flat', 'us' ),
				),
				'cols' => 2,
				'std' => '',
				'usb_preview' => TRUE,
			),
			'size' => array(
				'title' => __( 'Image Size', 'us' ),
				'description' => $misc['desc_img_sizes'],
				'type' => 'select',
				'options' => us_is_elm_editing_page() ? us_get_image_sizes_list() : array(),
				'std' => 'large',
				'cols' => 2,
				'admin_label' => TRUE,
				'usb_preview' => TRUE,
			),
			'img_transparent' => array(
				'title' => __( 'Image for Transparent Header', 'us' ),
				'type' => 'upload',
				'extension' => 'png,jpg,jpeg,gif,svg',
				'context' => array( 'header' ),
				'dynamic_values' => TRUE,
			),
			'dark_img' => array(
				'title' => __( 'Image for Dark Theme', 'us' ),
				'type' => 'upload',
				'extension' => 'png,jpg,jpeg,gif,svg',
				'context' => array( 'header' ),
				'dynamic_values' => TRUE,
			),
			'link' => array(
				'title' => us_translate( 'Link' ),
				'type' => 'link',
				'dynamic_values' => array(
					'global' => array(
						'homepage' => us_translate( 'Homepage' ),
						'popup_image' => __( 'Open Image in a Popup', 'us' ),
					),
				),
				'std' => '{"url":""}',
			),
		),

		// Height in Header only section
		array(
			'heading_1' => array(
				'title' => us_translate( 'Default' ),
				'type' => 'heading',
				'group' => us_translate( 'Height' ),
				'context' => array( 'header' ),
			),
			'height_default' => array(
				'title' => __( 'Desktops', 'us' ),
				'type' => 'text',
				'std' => '35px',
				'cols' => 4,
				'classes' => 'for_above',
				'group' => us_translate( 'Height' ),
				'context' => array( 'header' ),
			),
			'height_laptops' => array(
				'title' => __( 'Laptops', 'us' ),
				'type' => 'text',
				'std' => '30px',
				'cols' => 4,
				'classes' => 'for_above',
				'group' => us_translate( 'Height' ),
				'context' => array( 'header' ),
			),
			'height_tablets' => array(
				'title' => __( 'Tablets', 'us' ),
				'type' => 'text',
				'std' => '25px',
				'cols' => 4,
				'classes' => 'for_above',
				'group' => us_translate( 'Height' ),
				'context' => array( 'header' ),
			),
			'height_mobiles' => array(
				'title' => __( 'Mobiles', 'us' ),
				'type' => 'text',
				'std' => '20px',
				'cols' => 4,
				'classes' => 'for_above',
				'group' => us_translate( 'Height' ),
				'context' => array( 'header' ),
			),
			'heading_2' => array(
				'title' => __( 'Sticky Header', 'us' ),
				'type' => 'heading',
				'group' => us_translate( 'Height' ),
				'context' => array( 'header' ),
			),
			'height_sticky' => array(
				'title' => __( 'Desktops', 'us' ),
				'type' => 'text',
				'std' => '35px',
				'cols' => 4,
				'classes' => 'for_above',
				'group' => us_translate( 'Height' ),
				'context' => array( 'header' ),
			),
			'height_sticky_laptops' => array(
				'title' => __( 'Laptops', 'us' ),
				'type' => 'text',
				'std' => '30px',
				'cols' => 4,
				'classes' => 'for_above',
				'group' => us_translate( 'Height' ),
				'context' => array( 'header' ),
			),
			'height_sticky_tablets' => array(
				'title' => __( 'Tablets', 'us' ),
				'type' => 'text',
				'std' => '25px',
				'cols' => 4,
				'classes' => 'for_above',
				'group' => us_translate( 'Height' ),
				'context' => array( 'header' ),
			),
			'height_sticky_mobiles' => array(
				'title' => __( 'Mobiles', 'us' ),
				'type' => 'text',
				'std' => '20px',
				'cols' => 4,
				'classes' => 'for_above',
				'group' => us_translate( 'Height' ),
				'context' => array( 'header' ),
			),
		),


		$effect_options_params,
		$conditional_params,
		$design_options_params,
		$hover_options_params
	),

	// Not used params, required for correct fallback
	'fallback_params' => array(
		'animate',
		'animate_delay',
		'link_new_tab',
		'onclick',
		'onclick_code',
		'has_ratio',
		'ratio',
		'ratio_width',
		'ratio_height',
	),

	'usb_init_js' => 'jQuery( $elm ).usPopupLink()',
);
