<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Theme Options > Site Layout
 */

global $usof_options;

$grid_columns_layout = (
	! empty( $usof_options['live_builder'] )
	AND ! empty( $usof_options['grid_columns_layout'] )
);

// Create live edit link for Site Layout
$live_edit_layout_link = usb_get_edit_link(
	get_option( 'page_on_front', 0 ),
	array(
		'action' => 'us-site-settings',
		'group' => 'layout'
	)
);

return array(
	'title' => __( 'Site Layout', 'us' ),
	'fields' => array(
		'layout_head_message' => array(
			'description' => '<a target="_blank" href="' . esc_url( $live_edit_layout_link ) . '"><strong>' . __( 'Edit Live', 'us' ) . '</strong></a>',
			'type' => 'message',
			'classes' => 'customize_live',
			'place_if' => ! empty( $usof_options['live_builder'] ),
		),
		'canvas_layout' => array(
			'title' => __( 'Site Canvas Layout', 'us' ),
			'title_pos' => 'side',
			'type' => 'imgradio',
			'preview_path' => '/admin/img/%s.png',
			'options' => array(
				'wide' => '',
				'boxed' => '',
				'outlined' => '',
			),
			'std' => 'wide',
			'usb_preview' => TRUE,
		),
		'color_site_outline' => array(
			'title_pos' => 'side',
			'type' => 'color',
			'with_gradient' => FALSE,
			'exclude_dynamic_colors' => 'custom_field',
			'title' => __( 'Site Outline Color', 'us' ),
			'std' => '_content_bg_alt',
			'show_if' => array( 'canvas_layout', '=', 'outlined' ),
			'usb_preview' => array(
				'css' => '--site-outline-color',
				'elm' => 'html',
			),
		),
		'site_outline_width' => array(
			'title' => __( 'Site Outline Width', 'us' ),
			'title_pos' => 'side',
			'type' => 'slider',
			'std' => '15px',
			'options' => array(
				'px' => array(
					'min' => 0,
					'max' => 50,
				),
			),
			'is_responsive' => TRUE,
			'show_if' => array( 'canvas_layout', '=', 'outlined' ),
			'usb_preview' => array(
				'css' => '--site-outline-width',
				'elm' => 'html',
			),
		),
		'color_body_bg' => array(
			'title_pos' => 'side',
			'type' => 'color',
			'with_gradient' => TRUE,
			'exclude_dynamic_colors' => 'custom_field',
			'title' => __( 'Body Background Color', 'us' ),
			'std' => '_content_bg_alt',
			'show_if' => array( 'canvas_layout', '=', 'boxed' ),
			'usb_preview' => array(
				'css' => 'background',
				'elm' => 'body',
			),
		),
		'body_bg_image' => array(
			'title' => __( 'Body Background Image', 'us' ),
			'title_pos' => 'side',
			'type' => 'upload',
			'show_if' => array( 'canvas_layout', '=', 'boxed' ),
			'usb_preview' => TRUE,
		),
		'wrapper_body_bg_start' => array(
			'type' => 'wrapper_start',
			'classes' => 'force_right',
			'show_if' => array(
				array( 'canvas_layout', '=', 'boxed' ),
				'and',
				array( 'body_bg_image', '!=', '' ),
			),
		),
		'body_bg_image_size' => array(
			'title' => __( 'Background Size', 'us' ),
			'type' => 'radio',
			'options' => array(
				'cover' => __( 'Fill Area', 'us' ),
				'contain' => __( 'Fit to Area', 'us' ),
				'initial' => __( 'Initial', 'us' ),
			),
			'std' => 'cover',
			'usb_preview' => array(
				'css' => 'background-size',
				'elm' => 'body',
			),
		),
		'body_bg_image_repeat' => array(
			'title' => __( 'Background Repeat', 'us' ),
			'type' => 'radio',
			'options' => array(
				'repeat' => __( 'Repeat', 'us' ),
				'repeat-x' => __( 'Horizontally', 'us' ),
				'repeat-y' => __( 'Vertically', 'us' ),
				'no-repeat' => us_translate( 'None' ),
			),
			'std' => 'repeat',
			'usb_preview' => array(
				'css' => 'background-repeat',
				'elm' => 'body',
			),
		),
		'body_bg_image_position' => array(
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
			'std' => 'top left',
			'classes' => 'bgpos',
			'usb_preview' => array(
				'css' => 'background-position',
				'elm' => 'body',
			),
		),
		'body_bg_image_attachment' => array(
			'type' => 'switch',
			'switch_text' => us_translate( 'Scroll with Page' ),
			'std' => 1,
			'usb_preview' => TRUE,
		),
		'wrapper_body_bg_end' => array(
			'type' => 'wrapper_end',
		),
		'site_canvas_width' => array(
			'title' => __( 'Site Canvas Width', 'us' ),
			'title_pos' => 'side',
			'type' => 'slider',
			'std' => '1300px',
			'options' => array(
				'px' => array(
					'min' => 1000,
					'max' => 1700,
					'step' => 10,
				),
			),
			'show_if' => array( 'canvas_layout', '=', 'boxed' ),
			'usb_preview' => array(
				'css' => '--site-canvas-width',
				'elm' => 'html',
			),
		),
		'site_content_width' => array(
			'title' => __( 'Site Content Width', 'us' ),
			'title_pos' => 'side',
			'type' => 'slider',
			'std' => '1140px',
			'options' => array(
				'px' => array(
					'min' => 900,
					'max' => 1600,
					'step' => 10,
				),
			),
			'usb_preview' => array(
				'css' => '--site-content-width',
				'elm' => 'html',
			),
		),
		'sidebar_width' => array(
			'title' => __( 'Sidebar Width', 'us' ),
			'title_pos' => 'side',
			'type' => 'slider',
			'std' => '25%',
			'options' => array(
				'%' => array(
					'min' => 15,
					'max' => 45,
				),
			),
			'place_if' => ! empty( $usof_options['enable_sidebar_titlebar'] ),
			'usb_preview' => array(
				'css' => '--site-sidebar-width',
				'elm' => 'html',
			),
		),
		'header_inline_padding' => array(
			'title' => _x( 'Header Horizontal Indents', 'site top area', 'us' ),
			'title_pos' => 'side',
			'type' => 'slider',
			'std' => '5vmin',
			'options' => array(
				'px' => array(
					'min' => 0,
					'max' => 100,
				),
				'rem' => array(
					'min' => 0,
					'max' => 10,
					'step' => 0.1,
				),
				'vw' => array(
					'min' => 0,
					'max' => 10,
					'step' => 0.1,
				),
				'vh' => array(
					'min' => 0,
					'max' => 10,
					'step' => 0.1,
				),
				'vmin' => array(
					'min' => 0,
					'max' => 10,
					'step' => 0.1,
				),
				'vmax' => array(
					'min' => 0,
					'max' => 10,
					'step' => 0.1,
				),
			),
			'is_responsive' => TRUE,
			'usb_preview' => array(
				'css' => '--padding-inline',
				'elm' => '.l-subheader',
			),
		),
		'row_inline_padding' => array(
			'title' => __( 'Row Horizontal Indents', 'us' ),
			'title_pos' => 'side',
			'type' => 'slider',
			'std' => '5vmin',
			'options' => array(
				'px' => array(
					'min' => 0,
					'max' => 100,
				),
				'rem' => array(
					'min' => 0,
					'max' => 10,
					'step' => 0.1,
				),
				'vw' => array(
					'min' => 0,
					'max' => 10,
					'step' => 0.1,
				),
				'vh' => array(
					'min' => 0,
					'max' => 10,
					'step' => 0.1,
				),
				'vmin' => array(
					'min' => 0,
					'max' => 10,
					'step' => 0.1,
				),
				'vmax' => array(
					'min' => 0,
					'max' => 10,
					'step' => 0.1,
				),
			),
			'is_responsive' => TRUE,
			'usb_preview' => array(
				'css' => '--padding-inline',
				'elm' => '.l-section',
			),
		),
		'columns_gap' => array(
			'title' => __( 'Gap between columns', 'us' ),
			'title_pos' => 'side',
			'type' => 'slider',
			'options' => array(
				'px' => array(
					'min' => 0,
					'max' => 100,
				),
				'%' => array(
					'min' => 0.0,
					'max' => 10.0,
					'step' => 0.5,
				),
				'rem' => array(
					'min' => 0.0,
					'max' => 10.0,
					'step' => 0.1,
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
			'std' => '3rem',
			'is_responsive' => TRUE,
			'place_if' => $grid_columns_layout,
		),
		'row_height' => array(
			'title' => __( 'Row Vertical Indents', 'us' ),
			'title_pos' => 'side',
			'type' => 'select',
			'options' => array(
				'auto' => us_translate( 'None' ),
				'small' => 'S',
				'medium' => 'M',
				'large' => 'L',
				'huge' => 'XL',
				'custom' => __( 'Custom', 'us' ),
			),
			'std' => 'medium',
			'usb_preview' => TRUE,
		),
		'row_height_custom' => array(
			'title_pos' => 'side',
			'type' => 'slider',
			'std' => '5vmax',
			'classes' => 'for_above',
			'options' => array(
				'rem' => array(
					'min' => 0,
					'max' => 10,
					'step' => 0.1,
				),
				'vw' => array(
					'min' => 0,
					'max' => 20,
					'step' => 0.1,
				),
				'vh' => array(
					'min' => 0,
					'max' => 20,
					'step' => 0.1,
				),
				'vmin' => array(
					'min' => 0,
					'max' => 20,
					'step' => 0.1,
				),
				'vmax' => array(
					'min' => 0,
					'max' => 20,
					'step' => 0.1,
				),
			),
			'show_if' => array( 'row_height', '=', 'custom' ),
			'usb_preview' => array(
				'css' => '--section-custom-padding',
				'elm' => 'html',
			),
		),
		'text_bottom_indent' => array(
			'title' => __( 'Bottom Indent of Text Blocks', 'us' ),
			'title_pos' => 'side',
			'type' => 'slider',
			'std' => '0rem',
			'options' => array(
				'px' => array(
					'min' => 0,
					'max' => 50,
				),
				'rem' => array(
					'min' => 0,
					'max' => 3,
					'step' => 0.1,
				),
				'em' => array(
					'min' => 0,
					'max' => 3,
					'step' => 0.1,
				),
			),
			'usb_preview' => array(
				'css' => '--text-block-margin-bottom',
				'elm' => 'html',
			),
		),
		'site_border_radius' => array(
			'title' => __( 'Site Border Radius', 'us' ),
			'title_pos' => 'side',
			'type' => 'slider',
			'description' => __( 'A global value that affects many elements of the theme.', 'us' ),
			'std' => '0.3rem',
			'options' => array(
				'px' => array(
					'min' => 0,
					'max' => 50,
				),
				'rem' => array(
					'min' => 0,
					'max' => 3,
					'step' => 0.1,
				),
				'em' => array(
					'min' => 0,
					'max' => 3,
					'step' => 0.1,
				),
			),
			'classes' => 'desc_3',
			'usb_preview' => array(
				'css' => '--site-border-radius',
				'elm' => 'html',
			),
		),
		'footer_reveal' => array(
			'title' => __( 'Footer', 'us' ),
			'title_pos' => 'side',
			'type' => 'switch',
			'switch_text' => __( 'Enable Footer Reveal Effect', 'us' ),
			'std' => 0,
			'usb_preview' => TRUE,
		),

		// Breakpoints
		'h_breakpoints' => array(
			'title' => __( 'Breakpoints', 'us' ),
			'type' => 'heading',
			'classes' => 'with_separator',
		),
		'disable_post_popup_width' => array(
			'title' => __( 'Disable Post Popup on Width', 'us' ),
			'title_pos' => 'side',
			'description' => __( 'When the screen width is less than this value, opening post pages in a popup is disabled.', 'us' ),
			'type' => 'slider',
			'std' => '900px',
			'options' => array(
				'px' => array(
					'min' => 300,
					'max' => 1025,
				),
			),
			'classes' => 'desc_3',
			'usb_preview' => TRUE,
		),
		'disable_effects_width' => array(
			'title' => __( 'Disable Animations on Width', 'us' ),
			'title_pos' => 'side',
			'description' => __( 'When the screen width is less than this value, vertical parallax, appearance animations, and scrolling effects are disabled.', 'us' ),
			'type' => 'slider',
			'std' => '900px',
			'options' => array(
				'px' => array(
					'min' => 300,
					'max' => 1025,
				),
			),
			'classes' => 'desc_3',
			'usb_preview' => TRUE,
		),
		'columns_stacking_width' => array(
			'title' => __( 'Columns Stacking Width', 'us' ),
			'title_pos' => 'side',
			'description' => __( 'When screen width is less than this value, all columns within a row become a single column.', 'us' ),
			'type' => 'slider',
			'std' => '600px',
			'options' => array(
				'px' => array(
					'min' => 600,
					'max' => 1025,
				),
			),
			'classes' => 'desc_3',
			'usb_preview' => TRUE,
		),
		'laptops_breakpoint' => array(
			'title' => __( 'Laptops Screen Width', 'us' ),
			'title_pos' => 'side',
			'type' => 'slider',
			'std' => '1380px',
			'options' => array(
				'px' => array(
					'min' => 1024,
					'max' => 1500,
				),
			),
			'classes' => 'desc_3',
			'usb_preview' => TRUE,
		),
		'tablets_breakpoint' => array(
			'title' => __( 'Tablets Screen Width', 'us' ),
			'title_pos' => 'side',
			'type' => 'slider',
			'std' => '1024px',
			'options' => array(
				'px' => array(
					'min' => 768,
					'max' => 1280,
				),
			),
			'classes' => 'desc_3',
			'usb_preview' => TRUE,
		),
		'mobiles_breakpoint' => array(
			'title' => __( 'Mobiles Screen Width', 'us' ),
			'title_pos' => 'side',
			'type' => 'slider',
			'std' => '600px',
			'options' => array(
				'px' => array(
					'min' => 320,
					'max' => 768,
				),
			),
			'classes' => 'desc_3',
			'usb_preview' => TRUE,
		),
	),
);
