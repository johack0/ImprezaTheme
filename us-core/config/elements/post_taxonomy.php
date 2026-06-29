<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Configuration for shortcode: post_taxonomy
 */

$misc = us_config( 'elements_misc' );
$conditional_params = us_config( 'elements_conditional_options' );
$design_options_params = us_config( 'elements_design_options' );
$hover_options_params = us_config( 'elements_hover_options' );

/**
 * @return array
 */
return array(
	'title' => __( 'Post Taxonomy', 'us' ),
	'category' => __( 'Post Elements', 'us' ),
	'icon' => 'fas fa-tags',
	'params' => us_set_params_weight(

		// General section
		array(
			'taxonomy_name' => array(
				'title' => us_translate( 'Show' ),
				'type' => 'select',
				'options' => us_is_elm_editing_page() ? us_get_taxonomies() : array(),
				'std' => 'category',
				'admin_label' => TRUE,
				'us_admin_label' => TRUE,
				'usb_preview' => TRUE,
			),
			'style' => array(
				'title' => __( 'Display as', 'us' ),
				'type' => 'radio',
				'options' => array(
					'simple' => us_translate( 'Text' ),
					'badge' => __( 'Button', 'us' ),
				),
				'std' => 'simple',
				'cols' => 2,
				'usb_preview' => TRUE,
			),
			'btn_style' => array(
				'title' => __( 'Button Style', 'us' ),
				'description' => $misc['desc_btn_styles'],
				'type' => 'select',
				'options' => us_array_merge(
					array( 'badge' => '– ' . __( 'Badge by default', 'us' ) . ' –' ),
					us_get_btn_styles()
				),
				'std' => 'badge',
				'cols' => 2,
				'show_if' => array( 'style', '=', 'badge' ),
				'usb_preview' => TRUE, // NOTE: Buttons with “Border Gradient Animation” have their own structure.
			),
			'separator' => array(
				'title' => __( 'Separator between items', 'us' ),
				'type' => 'text',
				'std' => ', ',
				'cols' => 2,
				'show_if' => array( 'style', '=', 'simple' ),
				'usb_preview' => array(
					'attr' => 'html',
					'elm' => 'b',
				),
			),
			'link' => array(
				'title' => us_translate( 'Link' ),
				'type' => 'link',
				'dynamic_values' => array(
					'term' => array(
						'archive' => __( 'Archive Page', 'us' ),
					),
				),
				'std' => '{"type":"archive"}',
			),
			'color_link' => array(
				'title' => __( 'Link Color', 'us' ),
				'type' => 'switch',
				'switch_text' => __( 'Inherit from text color', 'us' ),
				'std' => 1,
				'show_if' => array( 'link', '!=', 'none' ),
				'usb_preview' => array(
					'toggle_class' => 'color_link_inherit',
				),
			),
			'icon' => array(
				'title' => __( 'Icon', 'us' ),
				'type' => 'icon',
				'std' => '',
				'usb_preview' => TRUE,
			),
			'text_before' => array(
				'title' => __( 'Text before value', 'us' ),
				'type' => 'text',
				'std' => '',
				'cols' => 2,
				'dynamic_values' => TRUE,
				'usb_preview' => TRUE,
			),
			'text_after' => array(
				'title' => __( 'Text after value', 'us' ),
				'type' => 'text',
				'std' => '',
				'cols' => 2,
				'dynamic_values' => TRUE,
				'usb_preview' => TRUE,
			),
			'show_color_swatch' => array(
				'switch_text' => __( 'Show color swatches', 'us' ),
				'type' => 'switch',
				'std' => 0,
				'usb_preview' => TRUE,
			),
			'hide_color_swatch_label' => array(
				'switch_text' => __( 'Hide color names', 'us' ),
				'type' => 'switch',
				'std' => 0,
				'show_if' => array( 'show_color_swatch', '=', '1' ),
				'usb_preview' => TRUE,
			),
			'apply_swatch_colors' => array(
				'switch_text' => sprintf( __( 'Apply colors from the "%s" option', 'us' ), __( 'Color Swatch', 'us' ) ),
				'type' => 'switch',
				'std' => 0,
				'usb_preview' => TRUE,
			),
		),

		$conditional_params,
		$design_options_params,
		$hover_options_params
	),

	// Not used params, required for correct fallback
	'fallback_params' => array(
		'custom_link',
	),
);
