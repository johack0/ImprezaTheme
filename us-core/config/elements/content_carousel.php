<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Configuration for shortcode: us_content_carousel
 */

$carousel_params = us_config( 'elements_carousel_options' );
$conditional_params = us_config( 'elements_conditional_options' );
$design_options_params = us_config( 'elements_design_options' );

// This param is differ from other Carousel elements (supports px only)
$items_gap_param = array(
	'items_gap' => array(
		'title' => __( 'Gap between Items', 'us' ),
		'type' => 'slider',
		'std' => '30px',
		'options' => array(
			'px' => array(
				'min' => 0,
				'max' => 100,
			),
		),
		'cols' => 2,
		'usb_preview' => TRUE,
		'group' => __( 'Carousel', 'us' ),
		'weight' => 37,
	),
);

$exclude_elements = array(
	'us_carousel',
	'us_cform',
	'us_color_scheme_switch',
	'us_content_carousel',
	'us_dropdown',
	'us_gallery',
	'us_gmaps',
	'us_grid',
	'us_grid_filter',
	'us_grid_order',
	'us_image_slider',
	'us_list_filter',
	'us_list_order',
	'us_list_search',
	'us_login',
	'us_page_block',
	'us_post_list',
	'us_product_list',
	'us_search',
	'us_separator',
	'us_scroller',
	'us_term_list',
	'us_term_carousel',
	'us_post_carousel',
	'us_product_carousel',
	'us_user_list',
	'us_user_carousel',
	'vc_column',
	'vc_tta_accordion',
	'vc_tta_section',
	'vc_tta_tabs',
	'vc_tta_toggle_section',
	'vc_tta_tour',
);

return array(
	'title' => __( 'Content Carousel', 'us' ),
	'description' => __( 'Allows you to rotate any content.', 'us' ),
	'category' => __( 'Containers', 'us' ),
	'icon' => 'fas fa-laptop-code',
	'is_container' => TRUE,
	'as_parent' => array(
		'except' => implode( ',', $exclude_elements ),
	),
	'usb_moving_only_x_axis' => TRUE,
	'usb_root_container_selector' => '.owl-stage:first',
	'usb_reload_element' => TRUE,
	'show_settings_on_create' => FALSE,
	'js_view' => 'VcColumnView',
	'params' => us_set_params_weight(
		$items_gap_param,
		$carousel_params,
		$conditional_params,
		$design_options_params
	),
	'fallback_params' => array(
		'arrows_offset',
		'arrows_pos',
		'items_offset',
	),
	'usb_init_js' => '$elm.usContentCarousel()',
);
