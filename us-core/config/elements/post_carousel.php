<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Configuration for shortcode: us_post_carousel
 */

// Get params from Post List and Carousel options to avoid params duplication
$post_list_params = $conditional_params = $design_options_params = $responsive_params = array();

if ( us_is_elm_editing_page() ) {
	$post_list_params = us_config( 'elements/post_list.params' );
	$responsive_params = us_config( 'elements_responsive_options' );
	$conditional_params = us_config( 'elements_conditional_options' );
	$design_options_params = us_config( 'elements_design_options' );
}

$carousel_params = us_config( 'elements_carousel_options' );

foreach( $post_list_params as $_param_name => &$_param ) {

	if ( in_array( $_param_name, array_keys( $conditional_params ) ) ) {
		unset( $post_list_params[ $_param_name ] );
	}
	if ( in_array( $_param_name, array_keys( $design_options_params ) ) ) {
		unset( $post_list_params[ $_param_name ] );
	}
	if ( in_array( $_param_name, array_keys( $responsive_params ) ) ) {
		unset( $post_list_params[ $_param_name ] );
	}
	if ( ! empty( $_param['exclude_for_carousel'] ) ) {
		unset( $post_list_params[ $_param_name ] );
	}
	if ( $_param_name === 'items_gap' ) {
		$_param['usb_preview'] = TRUE;
	}
}
unset( $_param );

return array(
	'title' => __( 'Post Carousel', 'us' ),
	'category' => __( 'Lists', 'us' ),
	'icon' => 'fas fa-laptop-code',
	'class' => 'improve_list_elm_ui',
	'usb_reload_element' => TRUE,
	'params' => us_set_params_weight(
		$post_list_params,
		$carousel_params,
		$conditional_params,
		$design_options_params
	),
	'usb_init_js' => '
		$elm.usCarousel();
		$us.$window.trigger( \'scroll.waypoints\' );
		jQuery( \'[data-content-height]\', $elm ).usCollapsibleContent()
	',
);
