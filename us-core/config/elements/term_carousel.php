<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Configuration for shortcode: us_term_carousel
 */

// Get params from Term List and Carousel options to avoid params duplication
$term_list_params = $conditional_params = $design_options_params = $responsive_params = array();

if ( us_is_elm_editing_page() ) {
	$term_list_params = us_config( 'elements/term_list.params' );
	$responsive_params = us_config( 'elements_responsive_options' );
	$conditional_params = us_config( 'elements_conditional_options' );
	$design_options_params = us_config( 'elements_design_options' );
}

$carousel_params = us_config( 'elements_carousel_options' );

foreach( $term_list_params as $_param_name => &$_param ) {

	if ( in_array( $_param_name, array_keys( $conditional_params ) ) ) {
		unset( $term_list_params[ $_param_name ] );
	}
	if ( in_array( $_param_name, array_keys( $design_options_params ) ) ) {
		unset( $term_list_params[ $_param_name ] );
	}
	if ( in_array( $_param_name, array_keys( $responsive_params ) ) ) {
		unset( $term_list_params[ $_param_name ] );
	}
	if ( ! empty( $_param['exclude_for_carousel'] ) ) {
		unset( $term_list_params[ $_param_name ] );
	}
	if ( $_param_name === 'items_gap' ) {
		$_param['usb_preview'] = TRUE;
	}
}
unset( $_param );

return array(
	'title' => __( 'Term Carousel', 'us' ),
	'category' => __( 'Lists', 'us' ),
	'icon' => 'fas fa-laptop-code',
	'class' => 'improve_list_elm_ui',
	'usb_reload_element' => TRUE,
	'params' => us_set_params_weight(
		$term_list_params,
		$carousel_params,
		$conditional_params,
		$design_options_params
	),
	'fallback_params' => array(
		'arrows_offset',
		'arrows_pos',
	),
	'usb_init_js' => '
		$elm.usCarousel();
		$us.$window.trigger( \'scroll.waypoints\' );
		jQuery( \'[data-content-height]\', $elm ).usCollapsibleContent()
	',
);
