<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Configuration for shortcode: ibanner
 */

$misc = us_config( 'elements_misc' );
$conditional_params = us_config( 'elements_conditional_options' );
$design_options_params = us_config( 'elements_design_options' );
$effect_options_params = us_config( 'elements_effect_options' );

/**
 * @return array
 */
return array(
	'title' => __( 'Interactive Banner', 'us' ),
	'description' => __( 'Image and text with hover effect', 'us' ),
	'category' => __( 'Interactive', 'us' ),
	'icon' => 'fas fa-camera-retro',
	'params' => us_set_params_weight(

		// General section
		array(
			'image' => array(
				'title' => us_translate( 'Image' ),
				'type' => 'upload',
				'dynamic_values' => TRUE,
				'extension' => 'png,jpg,jpeg,gif,svg',
				'cols' => 2,
				'usb_preview' => TRUE,
			),
			'size' => array(
				'title' => __( 'Image Size', 'us' ),
				'description' => $misc['desc_img_sizes'],
				'type' => 'select',
				'options' => us_is_elm_editing_page() ? us_get_image_sizes_list() : array(),
				'std' => 'large',
				'cols' => 2,
				'usb_preview' => TRUE,
			),
			'title' => array(
				'title' => us_translate( 'Title' ),
				'type' => 'text',
				'std' => us_translate( 'Title' ),
				'dynamic_values' => TRUE,
				'admin_label' => TRUE,
				'usb_preview' => array(
					'attr' => 'text',
					'elm' => '.w-ibanner-title',
				),
			),
			'title_size' => array(
				'title' => __( 'Title Size', 'us' ),
				'description' => $misc['desc_font_size'],
				'type' => 'text',
				'std' => '',
				'cols' => 2,
				'show_if' => array( 'title', '!=', '' ),
				'usb_preview' => array(
					'css' => 'font-size',
					'elm' => '.w-ibanner-title',
				),
			),
			'title_tag' => array(
				'title' => __( 'Title HTML tag', 'us' ),
				'type' => 'select',
				'options' => $misc['html_tag_values'],
				'std' => 'h2',
				'cols' => 2,
				'show_if' => array( 'title', '!=', '' ),
				'usb_preview' => array(
					'attr' => 'tag',
					'elm' => '.w-ibanner-title',
				),
			),
			'desc' => array(
				'title' => us_translate( 'Description' ),
				'type' => 'textarea',
				'show_ai_icon' => TRUE,
				'std' => '',
				'dynamic_values' => TRUE,
				'usb_preview' => array(
					'attr' => 'html',
					'elm' => '.w-ibanner-desc',
				),
			),
			'link' => array(
				'title' => us_translate( 'Link' ),
				'type' => 'link',
				'dynamic_values' => TRUE,
				'std' => '{"url":""}',
			),
		),

		// Appearance section
		array(
			'animation' => array(
				'title' => __( 'Animation', 'us' ),
				'type' => 'select',
				'options' => array(
					'melete' => 'Melete',
					'soter' => 'Soter',
					'phorcys' => 'Phorcys',
					'aidos' => 'Aidos',
					'caeros' => 'Caeros',
					'hebe' => 'Hebe',
					'aphelia' => 'Aphelia',
					'nike' => 'Nike',
				),
				'std' => 'melete',
				'cols' => 2,
				'group' => us_translate( 'Appearance' ),
				'usb_preview' => array(
					'mod' => 'animation',
				),
			),
			'easing' => array(
				'title' => __( 'Animation Easing', 'us' ),
				'type' => 'select',
				'options' => array(
					'ease' => 'ease',
					'easeInOutExpo' => 'easeInOutExpo',
					'easeInOutCirc' => 'easeInOutCirc',
				),
				'std' => 'ease',
				'cols' => 2,
				'group' => us_translate( 'Appearance' ),
				'usb_preview' => array(
					'mod' => 'easing',
				),
			),

		),

		$effect_options_params,
		$conditional_params,
		$design_options_params
	),

	// Not used params, required for correct fallback
	'fallback_params' => array(
		'align',
		'ratio',
	),
);
