<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Theme Options > Button Styles
 */

$misc = us_config( 'elements_misc' );

return array(
	'title' => __( 'Button Styles', 'us' ),
	'fields' => array(
		'buttons' => array(
			'type' => 'group',
			'preview' => 'button',
			'preview_class_format' => 'us-btn-style_%s',
			'is_accordion' => TRUE,
			'is_duplicate' => TRUE,
			'is_sortable' => TRUE,
			'show_controls' => TRUE,
			'accordion_title' => 'name',
			'params' => array(
				'id' => array(
					'type' => 'hidden',
					'std' => NULL,
				),

				// Hover Style
				'hover' => array(
					'title' => __( 'Background Hover Animation', 'us' ),
					'description' => __( 'Animations other than the first one may not work with buttons of 3rd-party plugins.', 'us' ),
					'type' => 'select',
					'options' => array(
						'fade' => __( 'Fade', 'us' ),
						'slide' => __( 'Slide from the Top', 'us' ),
						'slideLeft' => __( 'Slide from the Left', 'us' ),
						'slideRight' => __( 'Slide from the Right', 'us' ),
						'slideBottom' => __( 'Slide from the Bottom', 'us' ),
						'scaleUp' => __( 'Scale Up', 'us' ),
						'scaleDown' => __( 'Scale Down', 'us' ),
						'circle' => __( 'Circle', 'us' ),
					),
					'std' => 'fade',
					'cols' => 3,
					'classes' => 'desc_4',
				),
				'hover_text_animation' => array(
					'title' => __( 'Text Hover Animation', 'us' ),
					'description' => __( 'Animations other than the first one may not work with buttons of 3rd-party plugins.', 'us' ),
					'type' => 'select',
					'options' => array(
						'fade' => __( 'Fade', 'us' ),
						'slideTop' => __( 'Slide from the Top', 'us' ),
						'slideLeft' => __( 'Slide from the Left', 'us' ),
						'slideRight' => __( 'Slide from the Right', 'us' ),
						'slideBottom' => __( 'Slide from the Bottom', 'us' ),
						'scaleUp' => __( 'Scale Up', 'us' ),
						'scaleDown' => __( 'Scale Down', 'us' ),
					),
					'std' => 'fade',
					'cols' => 3,
					'classes' => 'desc_4',
				),
				'border_animation' => array(
					'title' => __( 'Border Gradient Animation', 'us' ),
					'description' => __( 'Specify the border width and border color as gradient to take effect. This animation may not work with buttons of 3rd-party plugins.', 'us' ),
					'type' => 'select',
					'options' => array(
						'none' => us_translate( 'None' ),
						'play_on_hover' => __( 'Play on hover', 'us' ),
						'pause_on_hover' => __( 'Pause on hover', 'us' ),
						'play_always' => __( 'Play always', 'us' ),
					),
					'std' => 'none',
					'cols' => 3,
					'classes' => 'desc_4',
				),

				// Transition
				'transition_duration' => array(
					'title' => __( 'Transition Duration', 'us' ),
					'type' => 'slider',
					'std' => '0.3s',
					'options' => array(
						's' => array(
							'min' => 0.0,
							'max' => 1.0,
							'step' => 0.05,
						),
					),
					'cols' => 3,
				),
				'transition_timing_function' => array(
					'title' => __( 'Transition Timing Function', 'us' ),
					'description' => '<a href="http://cubic-bezier.com/" target="_blank">' . __( 'Use timing function', 'us' ) . '</a>' . '. ' . __( 'Examples:', 'us' ) . ' <span class="usof-example">linear</span>, <span class="usof-example">cubic-bezier(.7,0,.2,1)</span>, <span class="usof-example">cubic-bezier(.9,-.3,.5,.5)</span>, <span class="usof-example">cubic-bezier(.5,0,0,1.5)</span>',
					'type' => 'text',
					'placeholder' => 'ease',
					'std' => '',
					'cols' => 3,
					'classes' => 'desc_4',
				),
				'animation_duration' => array(
					'title' => __( 'Animation Duration', 'us' ),
					'type' => 'slider',
					'std' => '3s',
					'options' => array(
						's' => array(
							'min' => 1.0,
							'max' => 5.0,
							'step' => 0.5,
						),
					),
					'cols' => 3,
					'show_if' => array( 'border_animation', '!=', 'none' ),
				),

				// Button Colors
				'color_bg' => array(
					'title' => us_translate( 'Colors' ),
					'type' => 'color',
					'clear_pos' => 'left',
					'std' => '_content_secondary',
					'text' => us_translate_x( 'Background', 'custom background' ),
					'cols' => 2,
				),
				'color_bg_hover' => array(
					'title' => __( 'Colors on hover', 'us' ),
					'type' => 'color',
					'clear_pos' => 'left',
					'std' => '',
					'text' => us_translate_x( 'Background', 'custom background' ),
					'cols' => 2,
				),
				'color_border' => array(
					'type' => 'color',
					'clear_pos' => 'left',
					'std' => '',
					'text' => us_translate( 'Border' ),
					'cols' => 2,
				),
				'color_border_hover' => array(
					'type' => 'color',
					'clear_pos' => 'left',
					'std' => '_content_secondary',
					'text' => us_translate( 'Border' ),
					'cols' => 2,
				),
				'color_text' => array(
					'type' => 'color',
					'clear_pos' => 'left',
					'with_gradient' => FALSE,
					'std' => '#fff',
					'text' => us_translate( 'Text' ),
					'cols' => 2,
				),
				'color_text_hover' => array(
					'type' => 'color',
					'clear_pos' => 'left',
					'with_gradient' => FALSE,
					'std' => '_content_secondary',
					'text' => us_translate( 'Text' ),
					'cols' => 2,
				),
				'color_shadow' => array(
					'type' => 'color',
					'clear_pos' => 'left',
					'with_gradient' => FALSE,
					'std' => '',
					'text' => __( 'Shadow', 'us' ),
					'cols' => 2,
				),
				'color_shadow_hover' => array(
					'type' => 'color',
					'clear_pos' => 'left',
					'with_gradient' => FALSE,
					'std' => '',
					'text' => __( 'Shadow', 'us' ),
					'cols' => 2,
				),

				// Shadow
				'wrapper_shadow_start' => array(
					'title' => __( 'Shadow', 'us' ),
					'type' => 'wrapper_start',
					'classes' => 'for_shadow',
				),
				'shadow_offset_h' => array(
					'description' => __( 'Horizontal Offset', 'us' ),
					'type' => 'slider',
					'std' => '0px',
					'options' => array(
						'px' => array(
							'min' => - 50,
							'max' => 50,
						),
						'em' => array(
							'min' => - 5.0,
							'max' => 5.0,
							'step' => 0.1,
						),
					),
					'classes' => 'slider_hide',
				),
				'shadow_offset_v' => array(
					'description' => __( 'Vertical Offset', 'us' ),
					'type' => 'slider',
					'std' => '0px',
					'options' => array(
						'px' => array(
							'min' => - 50,
							'max' => 50,
						),
						'em' => array(
							'min' => - 5.0,
							'max' => 5.0,
							'step' => 0.1,
						),
					),
					'classes' => 'slider_hide',
				),
				'shadow_blur' => array(
					'description' => __( 'Blur', 'us' ),
					'type' => 'slider',
					'std' => '0px',
					'options' => array(
						'px' => array(
							'min' => 0,
							'max' => 50,
						),
						'em' => array(
							'min' => 0.0,
							'max' => 5.0,
							'step' => 0.1,
						),
					),
					'classes' => 'slider_hide',
				),
				'shadow_spread' => array(
					'description' => __( 'Spread', 'us' ),
					'type' => 'slider',
					'std' => '0px',
					'options' => array(
						'px' => array(
							'min' => - 50,
							'max' => 50,
						),
						'em' => array(
							'min' => - 5.0,
							'max' => 5.0,
							'step' => 0.1,
						),
					),
					'classes' => 'slider_hide',
				),
				'shadow_inset' => array(
					'type' => 'checkboxes',
					'options' => array(
						'1' => __( 'Inner shadow', 'us' ),
					),
					'std' => '',
				),
				'wrapper_shadow_end' => array(
					'type' => 'wrapper_end',
				),

				// Shadow on focus
				'wrapper_shadow_hover_start' => array(
					'title' => __( 'Shadow on hover', 'us' ),
					'type' => 'wrapper_start',
					'classes' => 'for_shadow',
				),
				'shadow_hover_offset_h' => array(
					'description' => __( 'Horizontal Offset', 'us' ),
					'type' => 'slider',
					'std' => '0px',
					'options' => array(
						'px' => array(
							'min' => - 50,
							'max' => 50,
						),
						'em' => array(
							'min' => - 5.0,
							'max' => 5.0,
							'step' => 0.1,
						),
					),
					'classes' => 'slider_hide',
				),
				'shadow_hover_offset_v' => array(
					'description' => __( 'Vertical Offset', 'us' ),
					'type' => 'slider',
					'std' => '0px',
					'options' => array(
						'px' => array(
							'min' => - 50,
							'max' => 50,
						),
						'em' => array(
							'min' => - 5.0,
							'max' => 5.0,
							'step' => 0.1,
						),
					),
					'classes' => 'slider_hide',
				),
				'shadow_hover_blur' => array(
					'description' => __( 'Blur', 'us' ),
					'type' => 'slider',
					'std' => '0px',
					'options' => array(
						'px' => array(
							'min' => 0,
							'max' => 50,
						),
						'em' => array(
							'min' => 0.0,
							'max' => 5.0,
							'step' => 0.1,
						),
					),
					'classes' => 'slider_hide',
				),
				'shadow_hover_spread' => array(
					'description' => __( 'Spread', 'us' ),
					'type' => 'slider',
					'std' => '0px',
					'options' => array(
						'px' => array(
							'min' => - 50,
							'max' => 50,
						),
						'em' => array(
							'min' => - 5.0,
							'max' => 5.0,
							'step' => 0.1,
						),
					),
					'classes' => 'slider_hide',
				),
				'shadow_hover_inset' => array(
					'type' => 'checkboxes',
					'options' => array(
						'1' => __( 'Inner shadow', 'us' ),
					),
					'std' => '',
				),
				'wrapper_shadow_hover_end' => array(
					'type' => 'wrapper_end',
				),

				// Typography & Sizes
				'font' => array(
					'title' => __( 'Font', 'us' ),
					'type' => 'select',
					'options' => us_get_fonts_for_selection(),
					'std' => '',
					'cols' => 2,
				),
				'height' => array(
					'title' => __( 'Relative Height', 'us' ),
					'type' => 'slider',
					'std' => '0.8em',
					'options' => array(
						'em' => array(
							'min' => 0.0,
							'max' => 2.0,
							'step' => 0.1,
						),
					),
					'cols' => 2,
				),
				'font_size' => array(
					'title' => __( 'Font Size', 'us' ),
					'description' => $misc['desc_font_size'],
					'type' => 'text',
					'std' => '1rem',
					'classes' => 'desc_4',
					'cols' => 2,
				),
				'width' => array(
					'title' => __( 'Relative Width', 'us' ),
					'type' => 'slider',
					'std' => '1.8em',
					'options' => array(
						'em' => array(
							'min' => 0.0,
							'max' => 5.0,
							'step' => 0.1,
						),
					),
					'cols' => 2,
				),
				'line_height' => array(
					'title' => __( 'Line height', 'us' ),
					'type' => 'slider',
					'std' => '1.2',
					'options' => array(
						'' => array(
							'min' => 1.00,
							'max' => 2.00,
							'step' => 0.01,
						),
						'px' => array(
							'min' => 10,
							'max' => 50,
						),
					),
					'cols' => 2,
				),
				'border_width' => array(
					'title' => __( 'Border Width', 'us' ),
					'type' => 'slider',
					'std' => '2px',
					'options' => array(
						'px' => array(
							'min' => 0,
							'max' => 10,
						),
					),
					'cols' => 2,
				),
				'font_weight' => array(
					'title' => __( 'Font Weight', 'us' ),
					'description' => $misc['desc_font_weight'],
					'type' => 'text',
					'std' => '',
					'classes' => 'desc_4',
					'cols' => 2,
				),
				'border_radius' => array(
					'title' => __( 'Border Radius', 'us' ),
					'description' => $misc['desc_border_radius'],
					'type' => 'text',
					'std' => 'var(--site-border-radius)',
					'classes' => 'desc_4',
					'cols' => 2,
				),
				'letter_spacing' => array(
					'title' => __( 'Letter Spacing', 'us' ),
					'type' => 'slider',
					'std' => 0,
					'options' => array(
						'em' => array(
							'min' => - 0.10,
							'max' => 0.20,
							'step' => 0.01,
						),
					),
					'cols' => 2,
				),
				'text_style' => array(
					'title' => __( 'Text Styles', 'us' ),
					'type' => 'checkboxes',
					'options' => array(
						'uppercase' => __( 'Uppercase', 'us' ),
						'italic' => __( 'Italic', 'us' ),
					),
					'std' => '',
					'cols' => 2,
				),

				'name' => array(
					'title' => __( 'Button Style Name', 'us' ),
					'type' => 'text',
					'std' => us_translate( 'Style' ),
					'cols' => 2,
				),
				'class' => array(
					'title' => __( 'Extra class', 'us' ),
					'description' => __( 'Will be added to all buttons with this style', 'us' ),
					'type' => 'text',
					'std' => '',
					'cols' => 2,
					'classes' => 'desc_4',
				),
			),

			// Default styles after options reset
			'std' => array(
				array(
					'id' => 1,
					'name' => __( 'Default Button', 'us' ),
					'hover' => 'fade',
					// predefined colors after options reset
					'color_bg' => '_content_primary',
					'color_bg_hover' => '_content_secondary',
					'color_border' => '',
					'color_border_hover' => '',
					'color_text' => '#fff',
					'color_text_hover' => '#fff',
					'font' => '',
					'text_style' => '',
					'font_size' => '16px',
					'line_height' => '1.2',
					'font_weight' => '700',
					'letter_spacing' => '0em',
					'height' => '1.0em',
					'width' => '2.0em',
					'border_radius' => 'var(--site-border-radius)',
					'border_width' => '0px',
				),
				array(
					'id' => 2,
					'name' => __( 'Button', 'us' ) . ' 2',
					'hover' => 'fade',
					// predefined colors after options reset
					'color_bg' => '_content_border',
					'color_bg_hover' => '_content_text',
					'color_border' => '',
					'color_border_hover' => '',
					'color_text' => '_content_text',
					'color_text_hover' => '_content_bg',
					'font' => '',
					'text_style' => '',
					'font_size' => '16px',
					'line_height' => '1.2',
					'font_weight' => '700',
					'letter_spacing' => '0em',
					'height' => '1.0em',
					'width' => '2.0em',
					'border_radius' => 'var(--site-border-radius)',
					'border_width' => '0px',
				),
			),
		),
	),
);
