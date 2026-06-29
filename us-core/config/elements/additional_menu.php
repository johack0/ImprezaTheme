<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Configuration for shortcode: us_additional_menu
 */

$misc = us_config( 'elements_misc' );
$conditional_params = us_config( 'elements_conditional_options' );
$design_options_params = us_config( 'elements_design_options' );
$effect_options_params = us_config( 'elements_effect_options' );

$nav_menus = us_is_elm_editing_page() ? us_get_nav_menus() : array();

/**
 * @return array
 */
return array(
	'title' => __( 'Simple Menu', 'us' ),
	'icon' => 'fas fa-bars',
	'params' => us_set_params_weight(

		// General section
		array(
			'source' => array(
				'title' => us_translate( 'Menu' ),
				'description' => $misc['desc_menu_select'],
				'type' => 'select',
				'options' => $nav_menus,
				'std' => key( $nav_menus ),
				'admin_label' => TRUE,
				'usb_preview' => TRUE,
				'usb_default_value' => TRUE,
			),
			'layout' => array(
				'title' => __( 'Layout', 'us' ),
				'type' => 'radio',
				'options' => array(
					'ver' => __( 'Vertical', 'us' ),
					'hor' => __( 'Horizontal', 'us' ),
				),
				'std' => 'ver',
				'admin_label' => TRUE,
				'context' => array( 'shortcode' ),
				'usb_preview' => array(
					'mod' => 'layout',
				),
			),
			'spread' => array(
				'type' => 'switch',
				'switch_text' => __( 'Spread menu items evenly over the available width', 'us' ),
				'std' => 0,
				'classes' => 'for_above',
				'shortcode_show_if' => array( 'layout', '=', 'hor' ),
				'usb_preview' => array(
					'toggle_class' => 'spread',
				),
			),
			'responsive_width' => array(
				'title' => __( 'Switch to vertical at screens below', 'us' ),
				'description' => __( 'Examples:', 'us' ) . ' <span class="usof-example">600px</span>, <span class="usof-example">768px</span>. ' . __( 'Leave blank to enable horizontal scrolling on small screens.', 'us' ),
				'type' => 'text',
				'std' => '600px',
				'context' => array( 'shortcode' ),
				'show_if' => array( 'layout', '=', 'hor' ),
				'usb_preview' => TRUE, // Note: Generates styles on the backend side
			),
			'show_as_accordion' => array(
				'switch_text' => __( 'Display as Accordion', 'us' ),
				'type' => 'switch',
				'admin_label' => TRUE,
				'std' => 0,
				'show_if' => array( 'layout', '=', 'ver' ),
				'context' => array( 'shortcode' ),
				'usb_preview' => TRUE,
			),
			'accordion_allow_multiple_open' => array(
				'switch_text' => __( 'Allow several sections to be opened at the same time', 'us' ),
				'type' => 'switch',
				'std' => 0,
				'context' => array( 'shortcode' ),
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
				'context' => array( 'shortcode' ),
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
				'context' => array( 'shortcode' ),
				'show_if' => array( 'show_as_accordion', '=', 1 ),
				'usb_preview' => array(
					'mod' => 'iconpos',
				)
			),
		),

		// Main Items section
		array(
			'main_gap' => array(
				'title' => __( 'Gap between Items', 'us' ),
				'type' => 'slider',
				'std' => '1.5rem',
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
				'group' => _x( 'Main Items', 'In menus', 'us' ),
				'usb_preview' => array(
					'css' => '--main-gap',
				),
			),
			'main_style' => array(
				'title' => us_translate( 'Style' ),
				'type' => 'radio',
				'options' => array(
					'links' => us_translate( 'Links' ),
					'blocks' => us_translate( 'Blocks' ),
				),
				'std' => 'links',
				'context' => array( 'shortcode' ),
				'group' => _x( 'Main Items', 'In menus', 'us' ),
				'usb_preview' => TRUE,
			),
			'main_ver_indent' => array(
				'title' => __( 'Vertical Indents', 'us' ),
				'type' => 'slider',
				'std' => '0.8em',
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
				'show_if' => array( 'main_style', '=', 'blocks' ),
				'context' => array( 'shortcode' ),
				'group' => _x( 'Main Items', 'In menus', 'us' ),
				'usb_preview' => array(
					'css' => '--main-ver-indent',
				),
			),
			'main_hor_indent' => array(
				'title' => __( 'Horizontal Indents', 'us' ),
				'type' => 'slider',
				'std' => '0.8em',
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
				'show_if' => array( 'main_style', '=', 'blocks' ),
				'context' => array( 'shortcode' ),
				'group' => _x( 'Main Items', 'In menus', 'us' ),
				'usb_preview' => array(
					'css' => '--main-hor-indent',
				),
			),
		),

		// Main Items color section
		array(
			'main_color_bg' => array(
				'title' => __( 'Menu Item Background', 'us' ),
				'type' => 'color',
				'clear_pos' => 'right',
				'std' => 'rgba(0,0,0,0.1)',
				'cols' => 2,
				'context' => array( 'shortcode' ),
				'show_if' => array( 'main_style', '=', 'blocks' ),
				'group' => _x( 'Main Items', 'In menus', 'us' ),
				'usb_preview' => array(
					'css' => '--main-bg-color',
				),
			),
			'main_color_text' => array(
				'title' => __( 'Menu Item Text', 'us' ),
				'type' => 'color',
				'clear_pos' => 'right',
				'with_gradient' => FALSE,
				'std' => 'inherit',
				'cols' => 2,
				'context' => array( 'shortcode' ),
				'group' => _x( 'Main Items', 'In menus', 'us' ),
				'usb_preview' => array(
					'css' => '--main-color',
				),
			),
			'main_color_bg_hover' => array(
				'title' => __( 'Menu Item Background on hover', 'us' ),
				'type' => 'color',
				'clear_pos' => 'right',
				'std' => '',
				'cols' => 2,
				'context' => array( 'shortcode' ),
				'show_if' => array( 'main_style', '=', 'blocks' ),
				'group' => _x( 'Main Items', 'In menus', 'us' ),
				'usb_preview' => array(
					'css' => '--main-hover-bg-color',
				),
			),
			'main_color_text_hover' => array(
				'title' => __( 'Menu Item Text on hover', 'us' ),
				'type' => 'color',
				'clear_pos' => 'right',
				'with_gradient' => FALSE,
				'std' => '',
				'cols' => 2,
				'context' => array( 'shortcode' ),
				'group' => _x( 'Main Items', 'In menus', 'us' ),
				'usb_preview' => array(
					'css' => '--main-hover-color',
				),
			),
			'main_color_bg_active' => array(
				'title' => __( 'Active Menu Item Background', 'us' ),
				'type' => 'color',
				'clear_pos' => 'right',
				'std' => '',
				'cols' => 2,
				'context' => array( 'shortcode' ),
				'show_if' => array( 'main_style', '=', 'blocks' ),
				'group' => _x( 'Main Items', 'In menus', 'us' ),
				'usb_preview' => array(
					'css' => '--main-active-bg-color',
				),
			),
			'main_color_text_active' => array(
				'title' => __( 'Active Menu Item Text', 'us' ),
				'type' => 'color',
				'clear_pos' => 'right',
				'with_gradient' => FALSE,
				'std' => '',
				'cols' => 2,
				'context' => array( 'shortcode' ),
				'group' => _x( 'Main Items', 'In menus', 'us' ),
				'usb_preview' => array(
					'css' => '--main-active-color',
				),
			),
		),

		// Sub Items section
		array(
			'sub_items' => array(
				'type' => 'switch',
				'switch_text' => __( 'Show menu sub items', 'us' ),
				'std' => 0,
				'context' => array( 'shortcode' ),
				'group' => _x( 'Sub Items', 'In menus', 'us' ),
				'usb_preview' => TRUE,
			),
			'sub_gap' => array(
				'title' => __( 'Gap between Items', 'us' ),
				'type' => 'slider',
				'std' => '0px',
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
				'context' => array( 'shortcode' ),
				'show_if' => array( 'sub_items', '=', 1 ),
				'group' => _x( 'Sub Items', 'In menus', 'us' ),
				'usb_preview' => array(
					'css' => '--sub-gap',
				),
			),
		),

		$effect_options_params,
		$conditional_params,
		$design_options_params
	),
	'fallback_params' => array(
		'align',
	),
);
