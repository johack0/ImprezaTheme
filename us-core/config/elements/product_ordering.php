<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Configuration for shortcode: product_ordering
 */

$conditional_params = us_config( 'elements_conditional_options' );
$design_options_params = us_config( 'elements_design_options' );

/**
 * @return array
 */
return array(
	'title' => __( 'Product ordering', 'us' ),
	'category' => __( 'Deprecated', 'us' ),
	'icon' => 'fas fa-sort-amount-down',
	'show_for_post_types' => array( 'us_content_template', 'us_page_block' ),
	'place_if' => class_exists( 'woocommerce' ),
	'deprecated' => TRUE,
	'alternative_elms' => __( 'List Order', 'us' ),
	'params' => us_set_params_weight(
		$conditional_params,
		$design_options_params
	),
	'show_settings_on_create' => FALSE, // used in WPBakery editor
);
