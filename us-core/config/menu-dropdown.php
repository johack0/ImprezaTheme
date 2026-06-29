<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Menu Dropdown settings
 *
 * @filter us_config_menu-dropdown
 */

$misc = us_config( 'elements_misc' );

return array(
	'has_side_panel' => array(
		'type' => 'switch',
		'switch_text' => __( 'Enable Side Panel', 'us' ),
		'std' => 0,
		'description' => __( '2nd level menu items will be displayed aside.', 'us' ),
		'classes' => 'desc_2',
	),
	'side_item_font_size' => array(
		'title' => __( 'Side Item Font Size', 'us' ),
		'description' => $misc['desc_font_size'],
		'type' => 'text',
		'placeholder' => 'inherit',
		'std' => '1.15em',
		'cols' => 2,
		'show_if' => array( 'has_side_panel', '=', 1 ),
	),
	'side_item_font_weight' => array(
		'title' => __( 'Side Item Font Weight', 'us' ),
		'description' => $misc['desc_font_weight'],
		'type' => 'text',
		'std' => '',
		'cols' => 2,
		'show_if' => array( 'has_side_panel', '=', 1 ),
	),
	'side_item_ver_indent' => array(
		'title' => __( 'Side Item Vertical Indents', 'us' ),
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
		'show_if' => array( 'has_side_panel', '=', 1 ),
	),
	'side_item_hor_indent' => array(
		'title' => __( 'Side Item Horizontal Indents', 'us' ),
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
		'show_if' => array( 'has_side_panel', '=', 1 ),
	),
	'dropdown_height' => array(
		'title' => __( 'Dropdown Height', 'us' ),
		'type' => 'slider',
		'std' => '400px',
		'options' => array(
			'px' => array(
				'min' => 100,
				'max' => 1000,
				'step' => 10,
			),
			'rem' => array(
				'min' => 10,
				'max' => 60,
			),
			'em' => array(
				'min' => 10,
				'max' => 60,
			),
			'vh' => array(
				'min' => 10,
				'max' => 100,
			),
			'vmin' => array(
				'min' => 10,
				'max' => 100,
			),
			'vmax' => array(
				'min' => 10,
				'max' => 100,
			),
		),
		'cols' => 2,
		'show_if' => array( 'has_side_panel', '=', 1 ),
	),
	'side_item_width' => array(
		'title' => __( 'Side Item Width', 'us' ),
		'type' => 'slider',
		'std' => '250px',
		'options' => array(
			'px' => array(
				'min' => 100,
				'max' => 500,
				'step' => 10,
			),
			'%' => array(
				'min' => 10,
				'max' => 50,
			),
			'rem' => array(
				'min' => 5.0,
				'max' => 25.0,
				'step' => 0.5,
			),
			'em' => array(
				'min' => 5.0,
				'max' => 25.0,
				'step' => 0.5,
			),
		),
		'cols' => 2,
		'show_if' => array( 'has_side_panel', '=', 1 ),
	),
	'width' => array(
		'title' => us_translate( 'Width' ),
		'type' => 'radio',
		'options' => array(
			'auto' => us_translate_x( 'Auto', 'auto preload' ),
			'full' => __( 'Full Width', 'us' ),
			'custom' => __( 'Custom', 'us' ),
		),
		'show_if' => array( 'has_side_panel', '=', 0 ),
		'std' => 'auto',
	),
	'custom_width' => array(
		'type' => 'slider',
		'std' => '600px',
		'options' => array(
			'px' => array(
				'min' => 200,
				'max' => 1000,
			),
			'rem' => array(
				'min' => 10,
				'max' => 60,
			),
			'em' => array(
				'min' => 10,
				'max' => 60,
			),
			'vw' => array(
				'min' => 20,
				'max' => 100,
			),
			'vmin' => array(
				'min' => 20,
				'max' => 100,
			),
			'vmax' => array(
				'min' => 20,
				'max' => 100,
			),
		),
		'classes' => 'for_above',
		'show_if' => array(
			array( 'width', '=', 'custom' ),
			'and',
			array( 'has_side_panel', '=', 0 ),
		),
	),
	'stretch' => array(
		'type' => 'switch',
		'switch_text' => __( 'Stretch background to the screen edges', 'us' ),
		'std' => 0,
		'classes' => 'for_above',
		'show_if' => array( 'width', '=', 'full' ),
	),
	'drop_from' => array(
		'title' => __( 'Drop from', 'us' ),
		'type' => 'select',
		'options' => array(
			'menu_item' => __( 'Menu item', 'us' ),
			'header' => _x( 'Header', 'site top area', 'us' ),
		),
		'std' => 'menu_item',
		'cols' => 2,
		'show_if' => array(
			array( 'width', '!=', 'full' ),
			'and',
			array( 'has_side_panel', '=', 0 ),
		),
	),
	'drop_to' => array(
		'title' => __( 'Drop to', 'us' ),
		'type' => 'select',
		'options' => array(
			'left' => us_translate( 'Left' ),
			'center' => us_translate( 'Center' ),
			'right' => us_translate( 'Right' ),
		),
		'std' => 'right',
		'cols' => 2,
		'show_if' => array(
			array( 'width', '!=', 'full' ),
			'and',
			array( 'has_side_panel', '=', 0 ),
		),
	),

	// Columns
	'columns' => array(
		'title' => __( 'Columns for sub-items', 'us' ),
		'type' => 'slider',
		'std' => '1',
		'options' => array(
			'' => array(
				'min' => 1,
				'max' => 10,
			),
		),
		'cols' => 2,
	),
	'padding' => array(
		'title' => __( 'Inner Indents', 'us' ) . ' (padding)',
		'description' => $misc['desc_padding'],
		'type' => 'text',
		'std' => '',
		'cols' => 2,
	),
	'columns_fill_direction' => array(
		'title' => __( 'Direction of filling columns', 'us' ),
		'type' => 'radio',
		'options' => array(
			'hor' => __( 'Horizontally', 'us' ),
			'ver' => __( 'Vertically', 'us' ),
		),
		'std' => 'hor',
		'show_if' => array( 'columns', '!=', '1' ),
	),

	'color_bg' => array(
		'title' => __( 'Background Color', 'us' ),
		'type' => 'color',
		'clear_pos' => 'right',
		'std' => '',
		'cols' => 2,
	),
	'color_text' => array(
		'title' => __( 'Text Color', 'us' ),
		'type' => 'color',
		'clear_pos' => 'right',
		'with_gradient' => FALSE,
		'std' => '',
		'cols' => 2,
	),
	'bg_image' => array(
		'title' => __( 'Background Image', 'us' ),
		'type' => 'upload',
	),
	'bg_image_size' => array(
		'title' => __( 'Background Size', 'us' ),
		'type' => 'radio',
		'options' => array(
			'cover' => __( 'Fill Area', 'us' ),
			'contain' => __( 'Fit to Area', 'us' ),
			'initial' => __( 'Initial', 'us' ),
		),
		'std' => 'cover',
		'show_if' => array( 'bg_image', '!=', '' ),
	),
	'bg_image_repeat' => array(
		'title' => __( 'Background Repeat', 'us' ),
		'type' => 'radio',
		'options' => array(
			'repeat' => __( 'Repeat', 'us' ),
			'repeat-x' => __( 'Horizontally', 'us' ),
			'repeat-y' => __( 'Vertically', 'us' ),
			'no-repeat' => us_translate( 'None' ),
		),
		'std' => 'repeat',
		'show_if' => array( 'bg_image', '!=', '' ),
	),
	'bg_image_position' => array(
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
		'show_if' => array( 'bg_image', '!=', '' ),
	),
	'override_settings' => array(
		'title' => __( 'Mobile Menu', 'us' ),
		'type' => 'switch',
		'switch_text' => __( 'Override settings for this menu item', 'us' ),
		'std' => 0,
	),
	'mobile_behavior' => array(
		'title' => __( 'Show dropdown by click on', 'us' ),
		'type' => 'radio',
		'options' => array(
			'arrow' => __( 'Arrow', 'us' ),
			'label' => __( 'Label and Arrow', 'us' ),
		),
		'std' => 'arrow',
		'classes' => 'for_above',
		'show_if' => array( 'override_settings', '=', 1 ),
	),
);
