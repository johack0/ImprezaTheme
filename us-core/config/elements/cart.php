<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Configuration for shortcode: cart
 */

$misc = us_config( 'elements_misc' );
$conditional_params = us_config( 'elements_conditional_options' );
$design_options_params = us_config( 'elements_design_options' );
$effect_options_params = us_config( 'elements_effect_options' );

/**
 * @return array
 */
return array(
	'title' => us_translate( 'Cart', 'woocommerce' ),
	'category' => 'WooCommerce',
	'icon' => 'fas fa-shopping-cart',
	'place_if' => class_exists( 'woocommerce' ),
	'params' => us_set_params_weight(

		// General section
		array(
			'hide_empty' => array(
				'type' => 'switch',
				'switch_text' => us_translate( 'Hide if cart is empty', 'woocommerce' ),
				'std' => 0,
				'group' => __( 'Icon', 'us' ),
			),
			'vstretch' => array(
				'type' => 'switch',
				'switch_text' => __( 'Stretch to the full available height', 'us' ),
				'std' => 1,
				'classes' => 'for_above',
				'group' => __( 'Icon', 'us' ),
			),
			'quantity_color_bg' => array(
				'title' => __( 'Quantity Badge Background', 'us' ),
				'type' => 'color',
				'clear_pos' => 'right',
				'std' => '_header_middle_text_hover',
				'cols' => 2,
				'group' => __( 'Icon', 'us' ),
			),
			'quantity_color_text' => array(
				'title' => __( 'Quantity Badge Text', 'us' ),
				'type' => 'color',
				'clear_pos' => 'right',
				'with_gradient' => FALSE,
				'std' => '_header_middle_bg',
				'cols' => 2,
				'group' => __( 'Icon', 'us' ),
			),
			'icon' => array(
				'title' => __( 'Icon', 'us' ),
				'type' => 'icon',
				'std' => 'fas|shopping-cart',
				'group' => __( 'Icon', 'us' ),
			),
			'heading_1' => array(
				'title' => __( 'Icon Size', 'us' ),
				'type' => 'heading',
				'group' => __( 'Icon', 'us' ),
			),
			'size' => array(
				'title' => __( 'Desktops', 'us' ),
				'type' => 'text',
				'std' => '20px',
				'cols' => 4,
				'classes' => 'for_above',
				'group' => __( 'Icon', 'us' ),
			),
			'size_laptops' => array(
				'title' => __( 'Laptops', 'us' ),
				'type' => 'text',
				'std' => '20px',
				'cols' => 4,
				'classes' => 'for_above',
				'group' => __( 'Icon', 'us' ),
			),
			'size_tablets' => array(
				'title' => __( 'Tablets', 'us' ),
				'type' => 'text',
				'std' => '20px',
				'cols' => 4,
				'classes' => 'for_above',
				'group' => __( 'Icon', 'us' ),
			),
			'size_mobiles' => array(
				'title' => __( 'Mobiles', 'us' ),
				'type' => 'text',
				'std' => '20px',
				'cols' => 4,
				'classes' => 'for_above',
				'group' => __( 'Icon', 'us' ),
			),
			'show_content' => array(
				'type' => 'switch',
				'switch_text' => __( 'Show Cart content', 'us' ),
				'std' => 1,
				'group' => us_translate( 'Content' ),
			),
			'content_layout' => array(
				'title' => us_translate( 'Layout' ),
				'type' => 'select',
				'options' => array(
					'dropdown' => __( 'Dropdown', 'us' ),
					'left_panel' => __( 'Left Panel', 'us' ),
					'right_panel' => __( 'Right Panel', 'us' ),
				),
				'std' => 'dropdown',
				'show_if' => array( 'show_content', '=', 1 ),
				'group' => us_translate( 'Content' ),
			),
			'show_content_after_ajax' => array(
				'type' => 'switch',
				'switch_text' => __( 'Show Cart content after clicking "Add to Cart" button', 'us' ),
				'std' => 0,
				'show_if' => array( 'content_layout', '=', array( 'left_panel', 'right_panel' ) ),
				'group' => us_translate( 'Content' ),
			),
			'dropdown_effect' => array(
				'title' => __( 'Dropdown Effect', 'us' ),
				'type' => 'select',
				'options' => $misc['dropdown_effect_values'],
				'std' => 'height',
				'show_if' => array( 'content_layout', '=', 'dropdown' ),
				'group' => us_translate( 'Content' ),
			),
			'drop_on' => array(
				'type' => 'radio',
				'title' => __( 'Open Dropdown', 'us' ),
				'options' => array(
					'hover' => __( 'On hover', 'us' ),
					'click' => __( 'On click', 'us' ),
				),
				'std' => 'hover',
				'show_if' => array( 'content_layout', '=', 'dropdown' ),
				'group' => us_translate( 'Content' ),
			),
			'btn_size' => array(
				'title' => __( 'Button Size', 'us' ),
				'description' => $misc['desc_font_size'],
				'type' => 'text',
				'std' => '0.9rem',
				'show_if' => array( 'show_content', '=', 1 ),
				'group' => us_translate( 'Content' ),
			),
			'content_shadow' => array(
				'title' => __( 'Shadow', 'us' ),
				'type' => 'radio',
				'options' => array(
					'none' => us_translate( 'None' ),
					'thin' => __( 'Thin', 'us' ),
					'wide' => __( 'Wide', 'us' ),
				),
				'std' => 'thin',
				'show_if' => array( 'show_content', '=', 1 ),
				'group' => us_translate( 'Content' ),
			),
		),

		$effect_options_params,
		$conditional_params,
		$design_options_params
	),
);
