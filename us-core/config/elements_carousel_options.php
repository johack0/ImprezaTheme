<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Configuration for all carousel elements
 */

$misc = us_config( 'elements_misc' );

$laptops_breakpoint = (int) us_get_option( 'laptops_breakpoint' );
$tablets_breakpoint = (int) us_get_option( 'tablets_breakpoint' );
$mobiles_breakpoint = (int) us_get_option( 'mobiles_breakpoint' );

return array(

	// Carousel
	'items' => array(
		'title' => __( 'Number of Items to Show', 'us' ),
		'type' => 'select',
		'options' => array(
			'auto' => __( 'Auto (for items of different widths)', 'us' ),
			'1' => '1',
			'2' => '2',
			'3' => '3',
			'4' => '4',
			'5' => '5',
			'6' => '6',
			'7' => '7',
			'8' => '8',
			'9' => '9',
			'10' => '10',
		),
		'std' => '3',
		'cols' => 2,
		'usb_preview' => TRUE,
		'group' => __( 'Carousel', 'us' ),
	),
	'next_item_offset' => array(
		'title' => __( 'Next Item Offset', 'us' ),
		'type' => 'slider',
		'std' => '0px',
		'options' => array(
			'px' => array(
				'min' => 0,
				'max' => 100,
				'step' => 5,
			),
		),
		'cols' => 2,
		'usb_preview' => TRUE,
		'group' => __( 'Carousel', 'us' ),
	),
	'items_valign' => array(
		'title' => __( 'Items Vertical Alignment', 'us' ),
		'type' => 'select',
		'options' => array(
			'stretch' => __( 'Stretch', 'us' ),
			'top' => us_translate( 'Top' ),
			'middle' => us_translate( 'Middle' ),
			'bottom' => us_translate( 'Bottom' ),
		),
		'std' => 'stretch',
		'cols' => 2,
		'usb_preview' => array(
			'elm' => '.owl-carousel',
			'mod' => 'valign',
		),
		'group' => __( 'Carousel', 'us' ),
	),
	'center_item' => array(
		'type' => 'switch',
		'switch_text' => __( 'Current item in the center', 'us' ),
		'std' => 0,
		'show_if' => array( 'items', '!=', array( 'auto', '1' ) ),
		'usb_preview' => TRUE,
		'group' => __( 'Carousel', 'us' ),
	),
	'slide_by_one' => array(
		'type' => 'switch',
		'switch_text' => __( 'Slide by one item', 'us' ),
		'std' => 1,
		'show_if' => array( 'items', '!=', array( 'auto', '1' ) ),
		'usb_preview' => TRUE,
		'group' => __( 'Carousel', 'us' ),
	),
	'autoheight' => array(
		'type' => 'switch',
		'switch_text' => __( 'Auto Height', 'us' ),
		'std' => 0,
		'show_if' => array( 'items', '=', '1' ),
		'usb_preview' => TRUE,
		'group' => __( 'Carousel', 'us' ),
	),
	'loop' => array(
		'type' => 'switch',
		'switch_text' => us_translate( 'Loop' ),
		'std' => 0,
		'usb_preview' => TRUE,
		'group' => __( 'Carousel', 'us' ),
	),
	'autoplay' => array(
		'type' => 'switch',
		'switch_text' => __( 'Auto Rotation', 'us' ),
		'std' => 0,
		'usb_preview' => TRUE,
		'group' => __( 'Carousel', 'us' ),
	),
	'autoplay_pause_on_hover' => array(
		'type' => 'switch',
		'switch_text' => __( 'Pause on hover', 'us' ),
		'std' => 0,
		'show_if' => array( 'autoplay', '=', 1 ),
		'group' => __( 'Carousel', 'us' ),
	),
	'autoplay_continual' => array(
		'type' => 'switch',
		'switch_text' => __( 'Continual Rotation', 'us' ),
		'std' => 0,
		'show_if' => array( 'autoplay', '=', 1 ),
		'usb_preview' => TRUE,
		'group' => __( 'Carousel', 'us' ),
	),
	'autoplay_continual_css' => array(
		'type' => 'switch',
		'switch_text' => __( 'Continual Rotation via CSS', 'us' ),
		'description' => __( 'Navigation will be unavailable', 'us' ),
		'std' => 0,
		'show_if' => array( 'autoplay_continual', '=', 1 ),
		'classes' => 'desc_2',
		'usb_preview' => TRUE,
		'group' => __( 'Carousel', 'us' ),
	),
	'autoplay_timeout' => array(
		'title' => __( 'Auto Rotation Interval', 'us' ),
		'type' => 'slider',
		'std' => '3s',
		'options' => array(
			's' => array(
				'min' => 1,
				'max' => 10,
			),
		),
		'usb_preview' => TRUE,
		'show_if' => array( 'autoplay', '=', 1 ),
		'group' => __( 'Carousel', 'us' ),
	),
	'transition_speed' => array(
		'title' => __( 'Transition Duration', 'us' ),
		'type' => 'slider',
		'std' => '350ms',
		'options' => array(
			'ms' => array(
				'min' => 0,
				'max' => 2000,
				'step' => 50,
			),
		),
		'usb_preview' => TRUE,
		'group' => __( 'Carousel', 'us' ),
	),
	'transition_animation' => array(
		'title' => __( 'Transition Effect', 'us' ),
		'type' => 'select',
		'options' => array(
			'none' => us_translate( 'Default' ),
			'fade' => __( 'Fade (for 1 column only)', 'us' ),
		),
		'std' => 'none',
		'usb_preview' => TRUE,
		'group' => __( 'Carousel', 'us' ),
	),
	'transition_timing_function' => array(
		'title' => __( 'Transition Timing Function', 'us' ),
		'description' => '<a href="http://cubic-bezier.com/" target="_blank">' . __( 'Use timing function', 'us' ) . '</a>' . '. ' . __( 'Examples:', 'us' ) . ' <span class="usof-example">linear</span>, <span class="usof-example">cubic-bezier(0,1,.8,1)</span>, <span class="usof-example">cubic-bezier(.78,.13,.15,.86)</span>',
		'type' => 'text',
		'placeholder' => 'ease',
		'std' => '',
		'usb_preview' => TRUE,
		'group' => __( 'Carousel', 'us' ),
	),

	// Navigation
	// ARROWS
	'arrows' => array(
		'type' => 'switch',
		'switch_text' => __( 'Prev/Next arrows', 'us' ),
		'std' => 1,
		'group' => us_translate_x( 'Navigation', 'block title' ),
		'usb_preview' => TRUE,
	),
	'arrows_style' => array(
		'title' => __( 'Arrows Style', 'us' ),
		'description' => $misc['desc_btn_styles'],
		'type' => 'select',
		'options' => us_array_merge(
			array(
				'circle' => '– ' . __( 'Circles', 'us' ) . ' –',
				'square' => '– ' . __( 'Squares', 'us' ) . ' –',
			), us_get_btn_styles()
		),
		'std' => 'circle',
		'cols' => 2,
		'show_if' => array( 'arrows', '=', 1 ),
		'group' => us_translate_x( 'Navigation', 'block title' ),
		'usb_preview' => array(
			'elm' => '.owl-carousel',
			'mod' => 'navstyle',
		),
	),
	'arrows_size' => array(
		'title' => __( 'Arrows Size', 'us' ),
		'type' => 'slider',
		'std' => '1.5rem',
		'options' => array(
			'px' => array(
				'min' => 20,
				'max' => 60,
			),
			'rem' => array(
				'min' => 1.0,
				'max' => 4.0,
				'step' => 0.1,
			),
			'em' => array(
				'min' => 1.0,
				'max' => 4.0,
				'step' => 0.1,
			),
		),
		'cols' => 2,
		'show_if' => array( 'arrows', '=', 1 ),
		'group' => us_translate_x( 'Navigation', 'block title' ),
		'usb_preview' => array(
			'elm' => '.owl-carousel',
			'css' => '--arrows-size',
		),
	),
	'arrows_ver_pos' => array(
		'title' => __( 'Vertical Position', 'us' ),
		'type' => 'select',
		'options' => array(
			'middle' => us_translate( 'Middle' ),
			'stretch' => __( 'Stretch', 'us' ),
			'top_outside' => __( 'Top Outside', 'us' ),
			'top_inside' => __( 'Top Inside', 'us' ),
			'bottom_outside' => __( 'Bottom Outside', 'us' ),
			'bottom_inside' => __( 'Bottom Inside', 'us' ),
		),
		'std' => 'middle',
		'cols' => 2,
		'show_if' => array( 'arrows', '=', 1 ),
		'group' => us_translate_x( 'Navigation', 'block title' ),
		'usb_preview' => array(
			'elm' => '.owl-carousel',
			'mod' => 'arrows-ver-pos',
		),
	),
	'arrows_ver_offset' => array(
		'title' => __( 'Vertical Offset', 'us' ),
		'type' => 'slider',
		'std' => '0px',
		'options' => array(
			'px' => array(
				'min' => -200,
				'max' => 200,
			),
			'rem' => array(
				'min' => -10.0,
				'max' => 10.0,
				'step' => 0.1,
			),
			'em' => array(
				'min' => -10.0,
				'max' => 10.0,
				'step' => 0.1,
			),
			'%' => array(
				'min' => -50,
				'max' => 50,
			),
		),
		'cols' => 2,
		'show_if' => array( 'arrows', '=', 1 ),
		'group' => us_translate_x( 'Navigation', 'block title' ),
		'usb_preview' => array(
			'elm' => '.owl-carousel',
			'css' => '--arrows-ver-offset',
		),
	),
	'arrows_hor_pos' => array(
		'title' => __( 'Horizontal Position', 'us' ),
		'type' => 'select',
		'options' => array(
			'on_sides_outside' => __( 'On the Sides Outside', 'us' ),
			'on_sides_inside' => __( 'On the Sides Inside', 'us' ),
			'left_inside' => us_translate( 'Left' ),
			'center' => us_translate( 'Center' ),
			'right_inside' => us_translate( 'Right' ),
		),
		'std' => 'on_sides_outside',
		'cols' => 2,
		'show_if' => array( 'arrows', '=', 1 ),
		'group' => us_translate_x( 'Navigation', 'block title' ),
		'usb_preview' => array(
			'elm' => '.owl-carousel',
			'mod' => 'arrows-hor-pos',
		),
	),
	'arrows_hor_offset' => array(
		'title' => __( 'Horizontal Offset', 'us' ),
		'type' => 'slider',
		'std' => '0px',
		'options' => array(
			'px' => array(
				'min' => -200,
				'max' => 200,
			),
			'rem' => array(
				'min' => -10.0,
				'max' => 10.0,
				'step' => 0.1,
			),
			'em' => array(
				'min' => -10.0,
				'max' => 10.0,
				'step' => 0.1,
			),
			'%' => array(
				'min' => -50,
				'max' => 50,
			),
		),
		'cols' => 2,
		'show_if' => array( 'arrows', '=', 1 ),
		'group' => us_translate_x( 'Navigation', 'block title' ),
		'usb_preview' => array(
			'elm' => '.owl-carousel',
			'css' => '--arrows-hor-offset',
		),
	),
	'arrows_gap' => array(
		'title' => __( 'Gap between Arrows', 'us' ),
		'type' => 'slider',
		'std' => '10px',
		'options' => array(
			'px' => array(
				'min' => 0,
				'max' => 50,
			),
			'rem' => array(
				'min' => 0.0,
				'max' => 3.0,
				'step' => 0.1,
			),
			'em' => array(
				'min' => 0.0,
				'max' => 3.0,
				'step' => 0.1,
			),
		),
		'show_if' => array( 'arrows_hor_pos', '=', array( 'left_inside', 'center', 'right_inside' ) ),
		'group' => us_translate_x( 'Navigation', 'block title' ),
		'usb_preview' => array(
			'elm' => '.owl-carousel',
			'css' => '--arrows-gap',
		),
	),
	'arrows_disabled' => array(
		'title' => __( 'Disabled Arrow', 'us' ),
		'type' => 'radio',
		'options' => array(
			'hide' => us_translate( 'Hide' ),
			'fade' => __( 'Fade', 'us' ),
		),
		'std' => 'hide',
		'show_if' => array( 'arrows', '=', 1 ),
		'group' => us_translate_x( 'Navigation', 'block title' ),
		'usb_preview' => array(
			'elm' => '.owl-carousel',
			'mod' => 'arrows-disabled',
		),
	),

	// DOTS
	'dots' => array(
		'type' => 'switch',
		'switch_text' => __( 'Dots', 'us' ),
		'std' => 0,
		'group' => us_translate_x( 'Navigation', 'block title' ),
		'usb_preview' => TRUE,
	),
	'dots_style' => array(
		'title' => __( 'Dots Style', 'us' ),
		'type' => 'radio',
		'options' => array(
			'circle' => '1',
			'diamond' => '2',
			'dash' => '3',
			'smudge' => '4',
		),
		'std' => 'circle',
		'show_if' => array( 'dots', '=', 1 ),
		'group' => us_translate_x( 'Navigation', 'block title' ),
		'usb_preview' => array(
			'elm' => '.owl-carousel',
			'mod' => 'dotstyle',
		),
	),

	'mouse_drag' => array(
		'type' => 'switch',
		'switch_text' => __( 'Slide by mouse drag', 'us' ),
		'std' => 1,
		'group' => us_translate_x( 'Navigation', 'block title' ),
	),
	'touch_drag' => array(
		'type' => 'switch',
		'switch_text' => __( 'Slide by touch drag', 'us' ),
		'std' => 1,
		'group' => us_translate_x( 'Navigation', 'block title' ),
	),

	// Responsive
	'responsive' => array(
		'type' => 'group',
		'show_controls' => TRUE,
		'is_sortable' => TRUE,
		'is_accordion' => TRUE,
		'accordion_title' => 'breakpoint',
		'params' => array(
			'breakpoint' => array(
				'title' => __( 'Breakpoint Width', 'us' ),
				'description' => __( 'Options below will apply to screen widths smaller than the selected value.', 'us' ),
				'type' => 'select',
				'options' => array(
					'laptops' => sprintf( '%s (%spx)', __( 'Laptops', 'us' ), $laptops_breakpoint + 1 ),
					'tablets' => sprintf( '%s (%spx)', __( 'Tablets', 'us' ), $tablets_breakpoint + 1 ),
					'mobiles' => sprintf( '%s (%spx)', __( 'Mobiles', 'us' ), $mobiles_breakpoint + 1 ),
					'custom' => __( 'Custom', 'us' ),
				),
				'std' => 'laptops',
				'classes' => 'desc_4',
				'admin_label' => TRUE,
			),
			'breakpoint_width' => array(
				'type' => 'slider',
				'options' => array(
					'px' => array(
						'min' => 320,
						'max' => 2560,
					),
				),
				'std' => '1024px',
				'classes' => 'for_above',
				'show_if' => array( 'breakpoint', '=', 'custom' ),
			),
			'items' => array(
				'title' => __( 'Number of Items to Show', 'us' ),
				'type' => 'select',
				'options' => array(
					'auto' => __( 'Auto (for items of different widths)', 'us' ),
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
					'7' => '7',
					'8' => '8',
					'9' => '9',
					'10' => '10',
				),
				'std' => '1',
				'cols' => 2,
			),
			'items_offset' => array(
				'title' => __( 'Next Item Offset', 'us' ),
				'type' => 'slider',
				'options' => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
						'step' => 5,
					),
				),
				'std' => '0px',
				'cols' => 2,
			),
			'center_item' => array(
				'type' => 'switch',
				'switch_text' => __( 'Current item in the center', 'us' ),
				'std' => 0,
				'show_if' => array( 'items', '!=', array( 'auto', '1' ) ),
			),
			'autoheight' => array(
				'type' => 'switch',
				'switch_text' => __( 'Auto Height', 'us' ),
				'std' => 0,
				'show_if' => array( 'items', '=', '1' ),
			),
			'loop' => array(
				'type' => 'switch',
				'switch_text' => us_translate( 'Loop' ),
				'std' => 0,
			),
			'autoplay' => array(
				'type' => 'switch',
				'switch_text' => __( 'Auto Rotation', 'us' ),
				'std' => 0,
			),
			'arrows' => array(
				'type' => 'switch',
				'switch_text' => __( 'Prev/Next arrows', 'us' ),
				'std' => 0,
			),
			'dots' => array(
				'type' => 'switch',
				'switch_text' => __( 'Dots', 'us' ),
				'std' => 0,
			),
		),
		'std' => array(
			array(
				'breakpoint' => 'mobiles',
				'items' => '1',
				'items_offset' => '0px',
				'center_item' => 0,
				'autoheight' => 0,
				'loop' => 0,
				'autoplay' => 0,
				'arrows' => 0,
				'dots' => 0,
			),
		),
		'group' => __( 'Responsive', 'us' ),
		'usb_preview' => TRUE,
	),
);
