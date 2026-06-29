<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Configuration for shortcode: [us_list_result_counter]
 */

$conditional_params = us_config( 'elements_conditional_options' );
$design_options_params = us_config( 'elements_design_options' );

/**
 * @return array
 */
return array(
	'title' => __( 'List Result Counter', 'us' ),
	'category' => __( 'Lists', 'us' ),
	'icon' => 'fas fa-abacus',
	'class' => 'show_new_badge',
	'params' => us_set_params_weight(
		array(
			'list_to_count' => array(
				'title' => __( 'List to count', 'us' ),
				'type' => 'select',
				'options' => array(
					'first' => __( 'First List on a page', 'us' ),
					'selector' => __( 'Custom List selector', 'us' ),
				),
				'std' => 'first',
			),
			'list_selector_to_count' => array(
				'description' => __( 'Use class or ID.', 'us' ) . ' ' . __( 'Examples:', 'us' ) . ' <span class="usof-example">.count-me</span>, <span class="usof-example">#count-list</span>',
				'type' => 'text',
				'std' => '',
				'classes' => 'for_above',
				'show_if' => array( 'list_to_count', '=', 'selector' ),
			),
			'text' => array(
				'title' => us_translate( 'Text' ),
				'type' => 'text',
				'std' => sprintf( __( '%s - %s of %s results', 'us' ), '[lower]', '[upper]', '[total]' ),
				'description' =>
					'[lower] – ' . __( 'Lower result number', 'us' )
					. '<br>[upper] – ' . __( 'Upper result number', 'us' )
					. '<br>[total] – ' . __( 'Total number of filtered results', 'us' )
					. '<br>[total_unfiltered] – ' . __( 'Total number of unfiltered results', 'us' ),
				'admin_label' => TRUE,
				'usb_preview' => TRUE,
			),
			'text_single' => array(
				'title' => __( 'Text for single result', 'us' ),
				'type' => 'text',
				'std' => __( '1 result', 'us' ),
			),
			'text_no_results' => array(
				'title' => __( 'Text when no results', 'us' ),
				'type' => 'text',
				'std' => '',
				'description' => __( 'Leave blank to hide the element', 'us' ),
			),
		),

		$conditional_params,
		$design_options_params
	),
);
