<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Configuration for shortcode: us_category_nav
 */

$conditional_params = us_config( 'elements_conditional_options' );
$design_options_params = us_config( 'elements_design_options' );

return array(
	'title' => __( 'Category Navigation', 'us' ),
	'icon' => 'fas fa-stream',
	'class' => 'show_new_badge',
	'params' => us_set_params_weight(
		array(
			'taxonomy' => array(
				'title' => us_translate( 'Taxonomy' ),
				'type' => 'select',
				'options' => us_is_elm_editing_page() ? us_get_taxonomies() : array(),
				'std' => 'category',
				'admin_label' => TRUE,
				'usb_preview' => TRUE,
			),
			'hide_empty' => array(
				'type' => 'switch',
				'switch_text' => __( 'Hide empty terms', 'us' ),
				'std' => 0,
				'usb_preview' => TRUE,
			),
			'show_count' => array(
				'type' => 'switch',
				'switch_text' => __( 'Show post count', 'us' ),
				'std' => 0,
				'usb_preview' => TRUE,
			),
			'max_parent_level' => array(
				'title' => __( 'Max Parent Visible Level', 'us' ),
				'type' => 'slider',
				'std' => '1',
				'options' => array(
					'' => array(
						'min' => 1,
						'max' => 3,
					),
				),
				'cols' => 2,
			),
			'max_child_level' => array(
				'title' => __( 'Max Child Visible Level', 'us' ),
				'type' => 'slider',
				'std' => '1',
				'options' => array(
					'' => array(
						'min' => 1,
						'max' => 3,
					),
				),
				'cols' => 2,
				'usb_preview' => TRUE,
			),
			'show_as_accordion' => array(
				'switch_text' => __( 'Display as Accordion', 'us' ),
				'type' => 'switch',
				'admin_label' => TRUE,
				'std' => 0,
				'usb_preview' => TRUE,
			),
			'accordion_allow_multiple_open' => array(
				'switch_text' => __( 'Allow several sections to be opened at the same time', 'us' ),
				'type' => 'switch',
				'std' => 0,
				'show_if' => array( 'show_as_accordion', '=', 1 ),
			),
			'accordion_control_icon' => array(
				'title' => __( 'Control Icon', 'us' ),
				'type' => 'radio',
				'options' => array(
					'chevron' => 'Chevron',
					'plus' => 'Plus',
					'triangle' => 'Triangle',
				),
				'std' => 'chevron',
				'show_if' => array( 'show_as_accordion', '=', 1 ),
				'usb_preview' => array(
					'mod' => 'icontype',
				)
			),
			'accordion_control_position' => array(
				'title' => __( 'Control Position', 'us' ),
				'type' => 'radio',
				'options' => array(
					'before' => 'Before title',
					'after' => 'After title',
				),
				'std' => 'after',
				'show_if' => array( 'show_as_accordion', '=', 1 ),
				'usb_preview' => array(
					'mod' => 'iconpos',
				)
			),
		),

		// Appearance
		array(
			'item_gap' => array(
				'title' => __( 'Gap between Items', 'us' ),
				'type' => 'slider',
				'std' => '0.4rem',
				'options' => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
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
				'group' => us_translate( 'Appearance' ),
				'usb_preview' => array(
					'css' => '--main-gap',
				),
			),
			'item_style' => array(
				'title' => us_translate( 'Style' ),
				'type' => 'radio',
				'options' => array(
					'links' => us_translate( 'Links' ),
					'blocks' => us_translate( 'Blocks' ),
				),
				'std' => 'links',
				'group' => us_translate( 'Appearance' ),
				'usb_preview' => TRUE,
			),
			'item_ver_indent' => array(
				'title' => __( 'Vertical Indents', 'us' ),
				'type' => 'slider',
				'std' => '0.4em',
				'options' => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
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
				'cols' => 2,
				'show_if' => array( 'item_style', '=', 'blocks' ),
				'group' => us_translate( 'Appearance' ),
				'usb_preview' => array(
					'css' => '--main-ver-indent',
				),
			),
			'item_hor_indent' => array(
				'title' => __( 'Horizontal Indents', 'us' ),
				'type' => 'slider',
				'std' => '0.6em',
				'options' => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
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
				'cols' => 2,
				'show_if' => array( 'item_style', '=', 'blocks' ),
				'group' => us_translate( 'Appearance' ),
				'usb_preview' => array(
					'css' => '--main-hor-indent',
				),
			),
			'item_color_bg' => array(
				'title' => __( 'Menu Item Background', 'us' ),
				'type' => 'color',
				'clear_pos' => 'right',
				'std' => '_content_bg_alt',
				'cols' => 2,
				'show_if' => array( 'item_style', '=', 'blocks' ),
				'group' => us_translate( 'Appearance' ),
				'usb_preview' => array(
					'css' => '--main-bg-color',
				),
			),
			'item_color_text' => array(
				'title' => __( 'Menu Item Text', 'us' ),
				'type' => 'color',
				'clear_pos' => 'right',
				'with_gradient' => FALSE,
				'std' => 'inherit',
				'cols' => 2,
				'group' => us_translate( 'Appearance' ),
				'usb_preview' => array(
					'css' => '--main-color',
				),
			),
			'item_color_bg_hover' => array(
				'title' => __( 'Menu Item Background on hover', 'us' ),
				'type' => 'color',
				'clear_pos' => 'right',
				'std' => '_content_border',
				'cols' => 2,
				'show_if' => array( 'item_style', '=', 'blocks' ),
				'group' => us_translate( 'Appearance' ),
				'usb_preview' => array(
					'css' => '--main-hover-bg-color',
				),
			),
			'item_color_text_hover' => array(
				'title' => __( 'Menu Item Text on hover', 'us' ),
				'type' => 'color',
				'clear_pos' => 'right',
				'with_gradient' => FALSE,
				'std' => '',
				'cols' => 2,
				'group' => us_translate( 'Appearance' ),
				'usb_preview' => array(
					'css' => '--main-hover-color',
				),
			),
			'item_color_bg_active' => array(
				'title' => __( 'Active Menu Item Background', 'us' ),
				'type' => 'color',
				'clear_pos' => 'right',
				'std' => '',
				'cols' => 2,
				'show_if' => array( 'item_style', '=', 'blocks' ),
				'group' => us_translate( 'Appearance' ),
				'usb_preview' => array(
					'css' => '--main-active-bg-color',
				),
			),
			'item_color_text_active' => array(
				'title' => __( 'Active Menu Item Text', 'us' ),
				'type' => 'color',
				'clear_pos' => 'right',
				'with_gradient' => FALSE,
				'std' => '',
				'cols' => 2,
				'group' => us_translate( 'Appearance' ),
				'usb_preview' => array(
					'css' => '--main-active-color',
				),
			),
		),
		$conditional_params,
		$design_options_params
	),
);
