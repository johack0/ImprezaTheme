<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Configuration for shortcode: us_timeline
 */

$conditional_params = us_config( 'elements_conditional_options' );
$design_options_params = us_config( 'elements_design_options' );

// General
$general_params = array(

	'line_pos' => array(
		'title' => __( 'Line Position', 'us' ),
		'type' => 'radio',
		'options' => array(
			'left' => us_translate( 'Left' ),
			'center' => us_translate( 'Center' ),
			'right' =>  us_translate( 'Right' ),
		),
		'std' => 'left',
		'usb_preview' => array(
			'mod' => 'line_pos',
		),
	),
	'hide_line_endings' => array(
		'type' => 'switch',
		'switch_text' => __( 'Hide Line Endings', 'us' ),
		'std' => 0,
		'usb_preview' => array(
			'toggle_class' => 'hide_line_endings',
		),
	),
	'section_gap' => array(
		'title' => __( 'Gap between Sections', 'us' ),
		'type' => 'slider',
		'options' => array(
			'px' => array(
				'min' => 0,
				'max' => 60,
			),
			'rem' => array(
				'min' => 0.0,
				'max' => 4.0,
				'step' => 0.1,
			),
			'em' => array(
				'min' => 0.0,
				'max' => 4.0,
				'step' => 0.1,
			),
			'%' => array(
				'min' => 0.0,
				'max' => 10.0,
				'step' => 0.5,
			),
			'vw' => array(
				'min' => 0.0,
				'max' => 10.0,
				'step' => 0.1,
			),
			'vh' => array(
				'min' => 0.0,
				'max' => 10.0,
				'step' => 0.1,
			),
			'vmin' => array(
				'min' => 0.0,
				'max' => 10.0,
				'step' => 0.1,
			),
			'vmax' => array(
				'min' => 0.0,
				'max' => 10.0,
				'step' => 0.1,
			),
		),
		'std' => '2.5rem',
		'usb_preview' => array(
			'css' => '--section-gap',
		),
	),
	'line_offset' => array(
		'title' => __( 'Line Offset', 'us' ),
		'type' => 'slider',
		'options' => array(
			'px' => array(
				'min' => 0,
				'max' => 60,
			),
			'rem' => array(
				'min' => 0.0,
				'max' => 4.0,
				'step' => 0.1,
			),
			'em' => array(
				'min' => 0.0,
				'max' => 4.0,
				'step' => 0.1,
			),
			'%' => array(
				'min' => 0.0,
				'max' => 10.0,
				'step' => 0.5,
			),
			'vw' => array(
				'min' => 0.0,
				'max' => 10.0,
				'step' => 0.1,
			),
			'vh' => array(
				'min' => 0.0,
				'max' => 10.0,
				'step' => 0.1,
			),
			'vmin' => array(
				'min' => 0.0,
				'max' => 10.0,
				'step' => 0.1,
			),
			'vmax' => array(
				'min' => 0.0,
				'max' => 10.0,
				'step' => 0.1,
			),
		),
		'std' => '1.5rem',
		'usb_preview' => array(
			'css' => '--line-offset',
		),
	),
	'line_style' => array(
		'title' => __( 'Line Style', 'us' ),
		'type' => 'select',
		'options' => array(
			'solid' => __( 'Solid', 'us' ),
			'dashed' => __( 'Dashed', 'us' ),
			'dotted' => __( 'Dotted', 'us' ),
			'double' => __( 'Double', 'us' ),
		),
		'std' => 'solid',
		'cols' => 2,
		'usb_preview' => array(
			'css' => '--line-style',
		),
	),
	'line_color' => array(
		'title' => __( 'Line Color', 'us' ),
		'type' => 'color',
		'std' => '_content_border',
		'cols' => 2,
		'usb_preview' => array(
			'css' => '--line-color',
		),
	),
	'line_thickness' => array(
		'title' => __( 'Line Thickness', 'us' ),
		'type' => 'slider',
		'options' => array(
			'px' => array(
				'min' => 0,
				'max' => 10,
			),
		),
		'std' => '1px',
		'cols' => 2,
		'usb_preview' => array(
			'css' => '--line-thickness',
		),
	),

	// Markers
	'sticky_markers' => array(
		'switch_text' => __( 'Sticky markers on page scroll', 'us' ),
		'type' => 'switch',
		'std' => '0',
		'usb_preview' => array(
			'toggle_class' => 'sticky_markers',
		),
		'group' => __( 'Markers', 'us' ),
	),
	'marker_style' => array(
		'title' => us_translate( 'Style' ),
		'type' => 'select',
		'options' => array(
			'number' => __( 'Number', 'us' ),
			'icon' => __( 'Icon', 'us' ),
			'circle' => __( 'Circle', 'us' ),
			'square' => __( 'Square', 'us' ),
			'diamond' => __( 'Diamond', 'us' ),
			'dash' => __( 'Dash', 'us' ),
		),
		'std' => 'number',
		'usb_preview' => array(
			'mod' => 'marker_style',
		),
		'group' => __( 'Markers', 'us' ),
	),
	'marker_icon' => array(
		'title' => __( 'Icon', 'us' ),
		'type' => 'icon',
		'std' => 'fas|star',
		'show_if' => array( 'marker_style', '=', 'icon' ),
		'usb_preview' => TRUE,
		'group' => __( 'Markers', 'us' ),
	),
	'marker_size' => array(
		'title' => us_translate( 'Size' ),
		'description' => __( 'Examples:', 'us' ) . ' <span class="usof-example">10px</span>, <span class="usof-example">1.2rem</span>, <span class="usof-example">clamp(16px, 3vw, 30px)</span>',
		'type' => 'text',
		'std' => '1.2rem',
		'usb_preview' => array(
			'css' => '--marker-size',
		),
		'group' => __( 'Markers', 'us' ),
	),
	'marker_circle_scale' => array(
		'title' => __( 'Circle Scale', 'us' ),
		'type' => 'slider',
		'std' => '2.5',
		'options' => array(
			'' => array(
				'min' => 1.0,
				'max' => 4.0,
				'step' => 0.1,
			),
		),
		'show_if' => array( 'marker_style', '=', array( 'icon', 'number') ),
		'usb_preview' => array(
			'css' => '--marker-circle-scale',
		),
		'group' => __( 'Markers', 'us' ),
	),
	'marker_valign' => array(
		'title' => __( 'Vertical Alignment', 'us' ),
		'type' => 'radio',
		'options' => array(
			'top' => us_translate( 'Top' ),
			'middle' => us_translate( 'Middle' ),
			'bottom' => us_translate( 'Bottom' ),
		),
		'std' => 'top',
		'cols' => 2,
		'usb_preview' => array(
			'mod' => 'marker_valign',
		),
		'group' => __( 'Markers', 'us' ),
	),
	'marker_background_color' => array(
		'title' => __( 'Marker Background Color', 'us' ),
		'type' => 'color',
		'clear_pos' => 'right',
		'std' => '_content_bg_alt',
		'usb_preview' => array(
			array(
				'css' => '--marker-background-color',
			),
		),
		'group' => __( 'Markers', 'us' ),
	),
	'marker_text_color' => array(
		'title' => __( 'Marker Text Color', 'us' ),
		'type' => 'color',
		'clear_pos' => 'right',
		'std' => '_content_faded',
		'usb_preview' => array(
			array(
				'css' => '--marker-text-color',
			),
		),
		'show_if' => array( 'marker_style', '=', array( 'icon', 'number') ),
		'group' => __( 'Markers', 'us' ),
	),
	'marker_border_color' => array(
		'title' => __( 'Marker Border Color', 'us' ),
		'type' => 'color',
		'clear_pos' => 'right',
		'std' => '_content_border',
		'usb_preview' => array(
			array(
				'css' => '--marker-border-color',
			),
		),
		'show_if' => array( 'marker_style', '=', array( 'icon', 'number') ),
		'group' => __( 'Markers', 'us' ),
	),
	'marker_border_width' => array(
		'title' => __( 'Marker Border Width', 'us' ),
		'type' => 'slider',
		'options' => array(
			'px' => array(
				'min' => 0,
				'max' => 10,
			),
		),
		'std' => '1px',
		'cols' => 2,
		'usb_preview' => array(
			'css' => '--marker-border-width',
		),
		'show_if' => array( 'marker_style', '=', array( 'icon', 'number') ),
		'group' => __( 'Markers', 'us' ),
	),
	'marker_hide_screen_width' => array(
		'title' => __( 'Hide markers and line at screen width', 'us' ),
		'description' => __( 'Leave blank to not hide markers and line.', 'us' ),
		'type' => 'text',
		'std' => '',
		'usb_preview' => TRUE,
		'group' => __( 'Markers', 'us' ),
	),
);

/**
 * @return array
 */
return array(
	'title' => __( 'Timeline', 'us' ),
	'category' => __( 'Containers', 'us' ),
	'icon' => 'fas fa-list-ul',
	'is_container' => TRUE,
	'show_settings_on_create' => FALSE,
	'js_view' => 'VcColumnView',
	'usb_root_container_selector' => '.w-timeline',
	'as_parent' => array(
		'only' => 'us_timeline_section'
	),
	'class' => 'show_new_badge',
	'params' => us_set_params_weight(
		$general_params,
		$conditional_params,
		$design_options_params
	),
);
