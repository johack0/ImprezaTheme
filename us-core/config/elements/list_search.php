<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Configuration for shortcode: [us_list_search]
 */

$misc = us_config( 'elements_misc' );
$conditional_params = us_config( 'elements_conditional_options' );
$design_options_params = us_config( 'elements_design_options' );

// General
$general_params = array(
	'list_to_search' => array(
		'title' => __( 'List to search', 'us' ),
		'type' => 'select',
		'options' => array(
			'first' => __( 'First List on a page', 'us' ),
			'selector' => __( 'Custom List selector', 'us' ),
		),
		'std' => 'first',
	),
	'list_selector_to_search' => array(
		'description' => __( 'Use class or ID.', 'us' ) . ' ' . __( 'Examples:', 'us' ) . ' <span class="usof-example">.filter-me</span>, <span class="usof-example">#filterable-list</span>',
		'type' => 'text',
		'std' => '',
		'classes' => 'for_above',
		'show_if' => array( 'list_to_search', '=', 'selector' ),
	),
	'live_search' => array(
		'type' => 'switch',
		'switch_text' => __( 'Live search', 'us' ),
		'description' => __( 'Will search while typing', 'us' ),
		'classes' => 'desc_2',
		'std' => 1,
	),
	'change_url_params' => array(
		'switch_text' => __( 'Change URL params', 'us' ),
		'type' => 'switch',
		'std' => 1,
	),
	'text' => array(
		'title' => __( 'Placeholder', 'us' ),
		'type' => 'text',
		'std' => us_translate( 'Search' ),
		'admin_label' => TRUE,
		'usb_preview' => array(
			'elm' => 'input',
			'attr' => 'placeholder',
		),
	),
	'icon' => array(
		'title' => __( 'Icon', 'us' ),
		'type' => 'icon',
		'std' => 'fas|search',
		'usb_preview' => TRUE,
	),
	'icon_pos' => array(
		'title' => __( 'Icon Position', 'us' ),
		'type' => 'radio',
		'options' => array(
			'left' => __( 'Before text', 'us' ),
			'right' => __( 'After text', 'us' ),
		),
		'std' => 'right',
		'usb_preview' => array(
			'mod' => 'iconpos',
		),
	),
	'icon_size' => array(
		'title' => __( 'Icon Size', 'us' ),
		'description' => $misc['desc_font_size'],
		'type' => 'text',
		'std' => '18px',
		'usb_preview' => array(
			'css' => '--icon-size',
			'elm' => '.w-search-form-btn',
		),
	),
	'us_field_style' => array(
		'title' => __( 'Field Style', 'us' ),
		'description' => $misc['desc_field_styles'],
		'type' => 'select',
		'options' => us_get_field_styles(),
		'std' => 'default',
		'usb_preview' => array(
			'mod' => 'us-field-style',
		),
	),
);

/**
 * @return array
 */
return array(
	'title' => __( 'List Search', 'us' ),
	'category' => __( 'Lists', 'us' ),
	'icon' => 'fas fa-search-location',
	'params' => us_set_params_weight(
		$general_params,
		$conditional_params,
		$design_options_params
	),

	'usb_init_js' => '$elm.usListSearch()',
);
