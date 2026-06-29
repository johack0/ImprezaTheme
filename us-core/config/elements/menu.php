<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Configuration for shortcode: menu
 */

$misc = us_config( 'elements_misc' );
$conditional_params = us_config( 'elements_conditional_options' );
$design_options_params = us_config( 'elements_design_options' );
$effect_options_params = us_config( 'elements_effect_options' );

$mobile_menu_icon_styles = array(
	'hamburger_1' => __( 'Hamburger', 'us' ) . ' 1',
	'hamburger_2' => __( 'Hamburger', 'us' ) . ' 2',
	'hamburger_3' => __( 'Hamburger', 'us' ) . ' 3',
	'hamburger_4' => __( 'Hamburger', 'us' ) . ' 4',
	'hamburger_5' => __( 'Hamburger', 'us' ) . ' 5',
	'hamburger_6' => __( 'Hamburger', 'us' ) . ' 6',
	'hamburger_7' => __( 'Hamburger', 'us' ) . ' 7',
	'hamburger_8' => __( 'Hamburger', 'us' ) . ' 8',
	'kebab_1' => __( 'Kebab', 'us' ) . ' 1',
	'kebab_2' => __( 'Kebab', 'us' ) . ' 2',
	'dots_1' => __( 'Dots', 'us' ),
	'custom_icon' => __( 'Custom Icon', 'us' ),
	'custom_image' => __( 'Custom Image', 'us' ),
);

/**
 * @return array
 */
return array(
	'title' => us_translate( 'Menu' ),
	'icon' => 'fas fa-bars',
	'params' => us_set_params_weight(
		array(
			'source' => array(
				'title' => us_translate( 'Menu' ),
				'description' => $misc['desc_menu_select'],
				'type' => 'select',
				'options' => us_get_nav_menus(),
				'std' => 'header-menu',
			),

			// Main Items
			'main_items_heading' => array(
				'title' => _x( 'Main Items', 'In menus', 'us' ),
				'type' => 'heading',
				'classes' => 'with_separator',
			),
			'vstretch' => array(
				'type' => 'switch',
				'switch_text' => __( 'Stretch to the full available height', 'us' ),
				'std' => 1,
			),
			'align_edges' => array(
				'type' => 'switch',
				'switch_text' => __( 'Align the first/last menu item to the header edge', 'us' ),
				'std' => 0,
				'classes' => 'for_above',
			),
			'dropdown_arrow' => array(
				'type' => 'switch',
				'switch_text' => __( 'Show arrows for main items with dropdown', 'us' ),
				'std' => 0,
				'classes' => 'for_above',
			),
			'spread' => array(
				'type' => 'switch',
				'switch_text' => __( 'Spread menu items evenly over the available width', 'us' ),
				'std' => 0,
				'classes' => 'for_above',
			),
			'indents' => array(
				'title' => __( 'Distance Between Main Items', 'us' ),
				'type' => 'slider',
				'std' => '20px',
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
					'vw' => array(
						'min' => 0,
						'max' => 10,
					),
					'vh' => array(
						'min' => 0,
						'max' => 10,
					),
					'vmin' => array(
						'min' => 0,
						'max' => 10,
					),
					'vmax' => array(
						'min' => 0,
						'max' => 10,
					),
				),
				'is_responsive' => TRUE,
				'cols' => 2,
			),
			'hover_effect' => array(
				'title' => __( 'Main Items Hover Effect', 'us' ),
				'type' => 'select',
				'options' => array(
					'simple' => us_translate( 'None' ),
					'underline' => us_translate( 'Underline' ),
				),
				'std' => 'simple',
				'cols' => 2,
			),

			// Sub Items (Dropdowns)
			'sub_items_heading' => array(
				'title' => __( 'Dropdowns', 'us' ) . ' / ' . _x( 'Sub Items', 'In menus', 'us' ),
				'type' => 'heading',
				'classes' => 'with_separator',
			),
			'mega_menu_notice' => array(
				'description' => sprintf( __( 'Multi-columns dropdowns can be enabled for individual menu item on the %s page.', 'us' ), '<a href="' . admin_url( 'nav-menus.php' ) . '" target="_blank">' . us_translate( 'Menus' ) . '</a>' ),
				'type' => 'message',
			),
			'dropdown_font_size' => array(
				'title' => __( 'Font Size', 'us' ),
				'description' => $misc['desc_font_size'],
				'type' => 'text',
				'std' => '16px',
				'cols' => 2,
			),
			'dropdown_font_weight' => array(
				'title' => __( 'Font Weight', 'us' ),
				'description' => $misc['desc_font_weight'],
				'type' => 'text',
				'std' => '',
				'cols' => 2,
			),
			'sub_item_hor_indent' => array(
				'title' => __( 'Sub Item Horizontal Indents', 'us' ),
				'type' => 'slider',
				'std' => '20px',
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
			),
			'sub_item_ver_indent' => array(
				'title' => __( 'Sub Item Vertical Indents', 'us' ),
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
			),
			'dropdown_padding' => array(
				'title' => __( 'Inner Indents', 'us' ) . ' (padding)',
				'description' => $misc['desc_padding'],
				'type' => 'text',
				'std' => '',
				'cols' => 2,
			),
			'dropdown_border_radius' => array(
				'title' => __( 'Border Radius', 'us' ),
				'description' => $misc['desc_border_radius'],
				'type' => 'text',
				'std' => '',
				'cols' => 2,
			),
			'dropdown_open' => array(
				'title' => __( 'Open Dropdown', 'us' ),
				'type' => 'select',
				'options' => array(
					'hover' => __( 'On hover', 'us' ),
					'click' => __( 'On click', 'us' ),
				),
				'std' => 'hover',
				'cols' => 2,
			),
			'dropdown_effect' => array(
				'title' => __( 'Dropdown Effect', 'us' ),
				'type' => 'select',
				'options' => $misc['dropdown_effect_values'],
				'std' => 'height',
				'cols' => 2,
			),
			'dropdown_shadow' => array(
				'type' => 'radio',
				'title' => __( 'Shadow', 'us' ),
				'options' => array(
					'none' => us_translate( 'None' ),
					'thin' => __( 'Thin', 'us' ),
					'wide' => __( 'Wide', 'us' ),
				),
				'std' => 'wide',
			),
			'dropdown_width' => array(
				'type' => 'switch',
				'switch_text' => __( 'Limit full-width dropdowns by a menu width', 'us' ),
				'std' => 0,
			),
		),

		// Mobile Menu settings
		array(
			'mobile_width' => array(
				'title' => __( 'Show mobile menu when screen width is less than', 'us' ),
				'type' => 'slider',
				'std' => '900px',
				'options' => array(
					'px' => array(
						'min' => 300,
						'max' => 3000,
						'step' => 10,
					),
				),
				'group' => __( 'Mobile Menu', 'us' ),
			),

			// Mobile Menu Icon
			'mobile_icon_heading' => array(
				'title' => __( 'Mobile Menu Icon', 'us' ),
				'type' => 'heading',
				'classes' => 'with_separator',
				'group' => __( 'Mobile Menu', 'us' ),
			),
			'mobile_icon_style' => array(
				'title' => us_translate( 'Style' ),
				'type' => 'select',
				'options' => apply_filters( 'us_mobile_menu_icon_styles', $mobile_menu_icon_styles ),
				'std' => 'hamburger_1',
				'group' => __( 'Mobile Menu', 'us' ),
			),
			'mobile_icon_thickness' => array(
				'title' => __( 'Line Thickness', 'us' ),
				'type' => 'slider',
				'std' => '3px',
				'options' => array(
					'px' => array(
						'min' => 1.0,
						'max' => 8.0,
						'step' => 0.5,
					),
				),
				'show_if' => array( 'mobile_icon_style', '!=', array( 'custom_icon', 'custom_image' ) ),
				'group' => __( 'Mobile Menu', 'us' ),
			),
			'mobile_custom_icon_open' => array(
				'title' => __( 'Icon for openning', 'us' ),
				'type' => 'icon',
				'std' => 'fas|bars',
				'cols' => 2,
				'show_if' => array( 'mobile_icon_style', '=', 'custom_icon' ),
				'group' => __( 'Mobile Menu', 'us' ),
			),
			'mobile_custom_icon_close' => array(
				'title' => __( 'Icon for closing', 'us' ),
				'type' => 'icon',
				'std' => 'fas|times',
				'cols' => 2,
				'show_if' => array( 'mobile_icon_style', '=', 'custom_icon' ),
				'group' => __( 'Mobile Menu', 'us' ),
			),
			'mobile_custom_image_open' => array(
				'title' => __( 'Image for openning', 'us' ),
				'type' => 'upload',
				'preview_type' => 'image',
				'extension' => 'png,jpg,jpeg,gif,svg',
				'cols' => 2,
				'show_if' => array( 'mobile_icon_style', '=', 'custom_image' ),
				'group' => __( 'Mobile Menu', 'us' ),
			),
			'mobile_custom_image_close' => array(
				'title' => __( 'Image for closing', 'us' ),
				'type' => 'upload',
				'preview_type' => 'image',
				'extension' => 'png,jpg,jpeg,gif,svg',
				'cols' => 2,
				'show_if' => array( 'mobile_icon_style', '=', 'custom_image' ),
				'group' => __( 'Mobile Menu', 'us' ),
			),
			'mobile_icon_size' => array(
				'title' => __( 'Size on Desktops', 'us' ),
				'type' => 'text',
				'std' => '36px',
				'cols' => 4,
				'group' => __( 'Mobile Menu', 'us' ),
			),
			'mobile_icon_size_laptops' => array(
				'title' => __( 'Size on Laptops', 'us' ),
				'type' => 'text',
				'std' => '32px',
				'cols' => 4,
				'group' => __( 'Mobile Menu', 'us' ),
			),
			'mobile_icon_size_tablets' => array(
				'title' => __( 'Size on Tablets', 'us' ),
				'type' => 'text',
				'std' => '28px',
				'cols' => 4,
				'group' => __( 'Mobile Menu', 'us' ),
			),
			'mobile_icon_size_mobiles' => array(
				'title' => __( 'Size on Mobiles', 'us' ),
				'type' => 'text',
				'std' => '24px',
				'cols' => 4,
				'group' => __( 'Mobile Menu', 'us' ),
			),
			'mobile_icon_text' => array(
				'title' => __( 'Text near Icon', 'us' ),
				'type' => 'radio',
				'options' => array(
					'none' => us_translate( 'None' ),
					'left' => us_translate( 'Left' ),
					'right' => us_translate( 'Right' ),
				),
				'std' => 'none',
				'group' => __( 'Mobile Menu', 'us' ),
			),
			'mobile_icon_text_label' => array(
				'type' => 'text',
				'std' => us_translate( 'Menu' ),
				'classes' => 'for_above',
				'show_if' => array( 'mobile_icon_text', '!=', 'none' ),
				'group' => __( 'Mobile Menu', 'us' ),
			),

			// Mobile Menu Body
			'mobile_body_heading' => array(
				'title' => __( 'Mobile Menu', 'us' ),
				'type' => 'heading',
				'classes' => 'with_separator',
				'group' => __( 'Mobile Menu', 'us' ),

			),
			'mobile_layout' => array(
				'title' => __( 'Layout', 'us' ),
				'type' => 'select',
				'options' => array(
					'dropdown' => __( 'Dropdown', 'us' ),
					'panel' => __( 'Vertical Panel', 'us' ),
					'fullscreen' => __( 'Full Screen', 'us' ),
				),
				'std' => 'dropdown',
				'group' => __( 'Mobile Menu', 'us' ),
			),
			'mobile_effect_p' => array(
				'title' => __( 'Animation', 'us' ),
				'type' => 'select',
				'options' => array(
					'afl' => __( 'Appear From Left', 'us' ),
					'afr' => __( 'Appear From Right', 'us' ),
				),
				'std' => 'afl',
				'show_if' => array( 'mobile_layout', '=', 'panel' ),
				'group' => __( 'Mobile Menu', 'us' ),
			),
			'mobile_effect_f' => array(
				'title' => __( 'Animation', 'us' ),
				'type' => 'select',
				'options' => array(
					'fade' => __( 'Fade', 'us' ),
					'aft' => __( 'Appear From Top', 'us' ),
					'afc' => __( 'Appear From Center', 'us' ),
					'afb' => __( 'Appear From Bottom', 'us' ),
				),
				'std' => 'aft',
				'show_if' => array( 'mobile_layout', '=', 'fullscreen' ),
				'group' => __( 'Mobile Menu', 'us' ),
			),
			'mobile_header_visible' => array(
				'type' => 'switch',
				'switch_text' => _x( 'Header is always visible', 'site top area', 'us' ),
				'std' => 0,
				'show_if' => array( 'mobile_layout', '=', array( 'fullscreen', 'panel' ) ),
				'group' => __( 'Mobile Menu', 'us' ),
			),
			'mobile_font_size' => array(
				'title' => __( 'Main Items Font Size', 'us' ),
				'description' => $misc['desc_font_size'],
				'type' => 'text',
				'std' => '1.2rem',
				'cols' => 2,
				'group' => __( 'Mobile Menu', 'us' ),
			),
			'mobile_dropdown_font_size' => array(
				'title' => __( 'Sub Items Font Size', 'us' ),
				'description' => $misc['desc_font_size'],
				'type' => 'text',
				'std' => '1rem',
				'cols' => 2,
				'group' => __( 'Mobile Menu', 'us' ),
			),
			'mobile_shadow' => array(
				'title' => __( 'Shadow', 'us' ),
				'type' => 'radio',
				'options' => array(
					'none' => us_translate( 'None' ),
					'thin' => __( 'Thin', 'us' ),
					'wide' => __( 'Wide', 'us' ),
				),
				'std' => 'thin',
				'show_if' => array( 'mobile_layout', '=', array( 'dropdown', 'panel' ) ),
				'group' => __( 'Mobile Menu', 'us' ),
			),
			'mobile_align' => array(
				'title' => us_translate( 'Alignment' ),
				'type' => 'radio',
				'labels_as_icons' => 'fas fa-align-*',
				'options' => array(
					'none' => us_translate( 'Default' ),
					'center' => us_translate( 'Center' ),
					'justify' => us_translate( 'Justify' ),
				),
				'std' => 'none',
				'group' => __( 'Mobile Menu', 'us' ),
			),
			'mobile_behavior' => array(
				'title' => __( 'Show Sub Items by click on', 'us' ),
				'description' => sprintf( __( 'You can change this behavior separately for every menu item on the %s page', 'us' ), '<a href="' . admin_url( 'nav-menus.php' ) . '" target="_blank">' . us_translate( 'Menus' ) . '</a>' ),
				'type' => 'radio',
				'options' => array(
					'0' => __( 'Arrow', 'us' ),
					'1' => __( 'Label and Arrow', 'us' ),
				),
				'std' => '1',
				'group' => __( 'Mobile Menu', 'us' ),
			),
		),

		// Colors settings
		array(

			// Main Items
			'main_items_colors_heading' => array(
				'title' => _x( 'Main Items', 'In menus', 'us' ),
				'type' => 'heading',
				'classes' => 'with_separator',
				'group' => us_translate( 'Colors' ),
			),
			'color_hover_bg' => array(
				'type' => 'color',
				'text' => __( 'Background on hover', 'us' ),
				'std' => 'transparent',
				'group' => us_translate( 'Colors' ),
			),
			'color_hover_text' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'text' => __( 'Text on hover', 'us' ),
				'std' => '_header_middle_text_hover',
				'group' => us_translate( 'Colors' ),
			),
			'color_active_bg' => array(
				'type' => 'color',
				'text' => __( 'Background when active', 'us' ),
				'std' => 'transparent',
				'group' => us_translate( 'Colors' ),
			),
			'color_active_text' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'text' => __( 'Text when active', 'us' ),
				'std' => '_header_middle_text_hover',
				'group' => us_translate( 'Colors' ),
			),
			'color_transparent_hover_bg' => array(
				'type' => 'color',
				'text' => __( 'Transparent Header', 'us' ) . ': ' . __( 'Background on hover', 'us' ),
				'std' => '',
				'group' => us_translate( 'Colors' ),
			),
			'color_transparent_hover_text' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'text' => __( 'Transparent Header', 'us' ) . ': ' . __( 'Text on hover', 'us' ),
				'std' => '',
				'group' => us_translate( 'Colors' ),
			),
			'color_transparent_active_bg' => array(
				'type' => 'color',
				'text' => __( 'Transparent Header', 'us' ) . ': ' . __( 'Background when active', 'us' ),
				'std' => 'transparent',
				'group' => us_translate( 'Colors' ),
			),
			'color_transparent_active_text' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'text' => __( 'Transparent Header', 'us' ) . ': ' . __( 'Text when active', 'us' ),
				'std' => '_header_transparent_text_hover',
				'group' => us_translate( 'Colors' ),
			),

			// Sub Items (Dropdowns)
			'sub_items_colors_heading' => array(
				'title' => __( 'Dropdowns', 'us' ) . ' / ' . _x( 'Sub Items', 'In menus', 'us' ),
				'type' => 'heading',
				'classes' => 'with_separator',
				'group' => us_translate( 'Colors' ),
			),
			'color_drop_bg' => array(
				'type' => 'color',
				'text' => us_translate_x( 'Background', 'custom background' ),
				'std' => '_header_middle_bg',
				'group' => us_translate( 'Colors' ),
			),
			'color_drop_text' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'text' => us_translate( 'Text' ),
				'std' => '_header_middle_text',
				'group' => us_translate( 'Colors' ),
			),
			'color_drop_hover_bg' => array(
				'type' => 'color',
				'text' => __( 'Background on hover', 'us' ),
				'std' => 'transparent',
				'group' => us_translate( 'Colors' ),
			),
			'color_drop_hover_text' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'text' => __( 'Text on hover', 'us' ),
				'std' => '_header_middle_text_hover',
				'group' => us_translate( 'Colors' ),
			),
			'color_drop_active_bg' => array(
				'type' => 'color',
				'text' => __( 'Background when active', 'us' ),
				'std' => 'transparent',
				'group' => us_translate( 'Colors' ),
			),
			'color_drop_active_text' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'text' => __( 'Text when active', 'us' ),
				'std' => '_header_middle_text_hover',
				'group' => us_translate( 'Colors' ),
			),

			// Mobile Menu Main Items
			'mobile_colors' => array(
				'type' => 'switch',
				'switch_text' => __( 'Customize Mobile Menu Colors', 'us' ),
				'std' => 0,
				'group' => us_translate( 'Colors' ),
			),
			'main_items_mobile_colors_heading' => array(
				'title' => __( 'Mobile Menu', 'us' ) . ': ' . _x( 'Main Items', 'In menus', 'us' ),
				'type' => 'heading',
				'classes' => 'with_separator',
				'show_if' => array( 'mobile_colors', '=', '1' ),
				'group' => us_translate( 'Colors' ),
			),
			'color_mobile_bg' => array(
				'type' => 'color',
				'text' => us_translate_x( 'Background', 'custom background' ),
				'std' => '_content_bg',
				'show_if' => array( 'mobile_colors', '=', '1' ),
				'group' => us_translate( 'Colors' ),
			),
			'color_mobile_text' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'text' => us_translate( 'Text' ),
				'std' => '_content_heading',
				'show_if' => array( 'mobile_colors', '=', '1' ),
				'group' => us_translate( 'Colors' ),
			),
			'color_mobile_hover_bg' => array(
				'type' => 'color',
				'text' => __( 'Background on hover', 'us' ),
				'std' => 'transparent',
				'show_if' => array( 'mobile_colors', '=', '1' ),
				'group' => us_translate( 'Colors' ),
			),
			'color_mobile_hover_text' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'text' => __( 'Text on hover', 'us' ),
				'std' => '_content_link_hover',
				'show_if' => array( 'mobile_colors', '=', '1' ),
				'group' => us_translate( 'Colors' ),
			),
			'color_mobile_active_bg' => array(
				'type' => 'color',
				'text' => __( 'Background when active', 'us' ),
				'std' => 'transparent',
				'show_if' => array( 'mobile_colors', '=', '1' ),
				'group' => us_translate( 'Colors' ),
			),
			'color_mobile_active_text' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'text' => __( 'Text when active', 'us' ),
				'std' => '_content_link',
				'show_if' => array( 'mobile_colors', '=', '1' ),
				'group' => us_translate( 'Colors' ),
			),

			// Mobile Sub Items
			'sub_items_mobile_colors_heading' => array(
				'title' => __( 'Mobile Menu', 'us' ) . ': ' . _x( 'Sub Items', 'In menus', 'us' ),
				'type' => 'heading',
				'classes' => 'with_separator',
				'show_if' => array( 'mobile_colors', '=', '1' ),
				'group' => us_translate( 'Colors' ),
			),
			'color_mobile_sub_bg' => array(
				'type' => 'color',
				'text' => us_translate_x( 'Background', 'custom background' ),
				'std' => 'transparent',
				'show_if' => array( 'mobile_colors', '=', '1' ),
				'group' => us_translate( 'Colors' ),
			),
			'color_mobile_sub_text' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'text' => us_translate( 'Text' ),
				'std' => '_content_text',
				'show_if' => array( 'mobile_colors', '=', '1' ),
				'group' => us_translate( 'Colors' ),
			),
			'color_mobile_sub_hover_bg' => array(
				'type' => 'color',
				'text' => __( 'Background on hover', 'us' ),
				'std' => 'transparent',
				'show_if' => array( 'mobile_colors', '=', '1' ),
				'group' => us_translate( 'Colors' ),
			),
			'color_mobile_sub_hover_text' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'text' => __( 'Text on hover', 'us' ),
				'std' => '_content_link_hover',
				'show_if' => array( 'mobile_colors', '=', '1' ),
				'group' => us_translate( 'Colors' ),
			),
			'color_mobile_sub_active_bg' => array(
				'type' => 'color',
				'text' => __( 'Background when active', 'us' ),
				'std' => 'transparent',
				'show_if' => array( 'mobile_colors', '=', '1' ),
				'group' => us_translate( 'Colors' ),
			),
			'color_mobile_sub_active_text' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'text' => __( 'Text when active', 'us' ),
				'std' => '_content_link',
				'show_if' => array( 'mobile_colors', '=', '1' ),
				'group' => us_translate( 'Colors' ),
			),
		),

		$effect_options_params,
		$conditional_params,
		$design_options_params
	),
);
