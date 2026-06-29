<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Configuration for shortcode: [us_list_filter_reset]
 */

$misc = us_config( 'elements_misc' );
$conditional_params = us_config( 'elements_conditional_options' );
$design_options_params = us_config( 'elements_design_options' );

/**
 * @return array
 */
return array(
	'title' => __( 'List Filter Reset', 'us' ),
	'category' => __( 'Lists', 'us' ),
	'icon' => 'far fa-filter',
	'class' => 'improve_list_elm_ui show_new_badge',
	'params' => us_set_params_weight(
		array(
			'reset_all_label' => array(
				'title' => __( 'Button Label', 'us' ),
				'type' => 'text',
				'std' => __( 'Reset All', 'us' ),
				'usb_preview' => array(
					'attr' => 'html',
					'elm' => '.w-filter-reset-all > .w-btn-label',
				),
			),
			'reset_all_style' => array(
				'title' => __( 'Button Style', 'us' ),
				'description' => $misc['desc_btn_styles'],
				'type' => 'select',
				'options' => array(
					'style_1' => sprintf( '&ndash; %s 1 &ndash;', us_translate( 'Style' ) ),
					'style_2' => sprintf( '&ndash; %s 2 &ndash;', us_translate( 'Style' ) ),
				) + us_get_btn_styles(),
				'std' => 'style_1',
				'usb_preview' => TRUE,
			),
			'show_selected_values' => array(
				'type' => 'switch',
				'switch_text' => __( 'Show selected filter values', 'us' ),
				'std' => 1,
				'usb_preview' => array(
					'toggle_class' => 'show_selected_values',
				),
			),
			'selected_values_style' => array(
				'title' => __( 'Selected Values Style', 'us' ),
				'description' => $misc['desc_btn_styles'],
				'type' => 'select',
				'options' => array(
						'style_1' => sprintf( '&ndash; %s 1 &ndash;', us_translate( 'Style' ) ),
						'style_2' => sprintf( '&ndash; %s 2 &ndash;', us_translate( 'Style' ) ),
					) + us_get_btn_styles(),
				'std' => 'style_1',
				'show_if' => array( 'show_selected_values', '=', '1' ),
				'usb_preview' => TRUE,
			),
			'values_gap' => array(
				'title' => __( 'Gap between elements', 'us' ),
				'type' => 'slider',
				'std' => '10px',
				'options' => array(
					'px' => array(
						'min' => 0,
						'max' => 30,
					),
					'em' => array(
						'min' => 0,
						'max' => 2.0,
						'step' => 0.1,
					),
					'rem' => array(
						'min' => 0,
						'max' => 2.0,
						'step' => 0.1,
					),
				),
				'show_if' => array( 'show_selected_values', '=', '1' ),
				'usb_preview' => array(
					'css' => '--values-gap',
				),
			),
			'selected_values_pos' => array(
				'title' => __( 'Selected Values Position', 'us' ),
				'type' => 'radio',
				'options' => array(
					'before' => __( 'Before Reset All', 'us' ),
					'after' => __( 'After Reset All', 'us' ),
				),
				'std' => 'before',
				'show_if' => array( 'show_selected_values', '=', '1' ),
				'usb_preview' => array(
					'mod' => 'pos',
				),
			),
		),

		$conditional_params,
		$design_options_params
	),

	'usb_init_js' => '$elm.usListFilter()',
);
