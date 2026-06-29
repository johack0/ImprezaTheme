<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Configuration for shortcode: us_timeline_section
 * Timeline section filled with content.
 */

$conditional_params = us_config( 'elements_conditional_options' );
$design_options_params = us_config( 'elements_design_options' );

$general_params = array(
	'marker_icon' => array(
		'title' => __( 'Icon', 'us' ),
		'type' => 'icon',
		'std' => '',
		'usb_preview' => TRUE,
	),
	'marker_background_color' => array(
		'title' => __( 'Marker Background Color', 'us' ),
		'type' => 'color',
		'clear_pos' => 'right',
		'std' => '',
		'usb_preview' => array(
			array(
				'elm' => '.w-timeline-section-marker',
				'css' => '--marker-background-color',
			),
		),
	),
	'marker_text_color' => array(
		'title' => __( 'Marker Text Color', 'us' ),
		'type' => 'color',
		'clear_pos' => 'right',
		'std' => '',
		'usb_preview' => array(
			array(
				'elm' => '.w-timeline-section-marker',
				'css' => '--marker-text-color',
			),
		),
	),
	'marker_border_color' => array(
		'title' => __( 'Marker Border Color', 'us' ),
		'type' => 'color',
		'clear_pos' => 'right',
		'std' => '',
		'usb_preview' => array(
			array(
				'elm' => '.w-timeline-section-marker',
				'css' => '--marker-border-color',
			),
		),
	),
);

/**
 * @return array
 */
return array(
	'title' => __( 'Timeline Section', 'us' ),
	'category' => __( 'Containers', 'us' ),
	'icon' => 'far fa-square',
	'is_container' => TRUE,
	'show_settings_on_create' => FALSE,
	'hide_on_adding_list' => TRUE,
	'js_view' => 'VcColumnView',
	'usb_root_container_selector' => '.w-timeline-section-content',
	'usb_reload_parent_element' => TRUE,
    'as_child' => array(
        'only' => 'us_timeline'
    ),
	'as_parent' => array(
		'except' => 'vc_row,vc_row_inner,vc_column,vc_tta_tabs,vc_tta_tour,vc_tta_accordion,vc_tta_section,us_content_carousel,us_timeline,us_timeline_section',
	),
	'params' => us_set_params_weight(
		$general_params,
		$conditional_params,
		$design_options_params
	),
);
