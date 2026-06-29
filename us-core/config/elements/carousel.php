<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Configuration for shortcode: us_carousel
 */

$grid_params = $conditional_params = $design_options_params = array();

if ( us_is_elm_editing_page() ) {
	$grid_params = us_config( 'elements/grid.params' );
	$conditional_params = us_config( 'elements_conditional_options' );
	$design_options_params = us_config( 'elements_design_options' );
}

$carousel_params = us_config( 'elements_carousel_options' );

foreach( $grid_params as $_param_name => &$_param ) {

	if ( in_array( $_param_name, array_keys( $conditional_params ) ) ) {
		unset( $grid_params[ $_param_name ] );
	}
	if ( in_array( $_param_name, array_keys( $design_options_params ) ) ) {
		unset( $grid_params[ $_param_name ] );
	}
	if ( ! empty( $_param['exclude_for_carousel'] ) ) {
		unset( $grid_params[ $_param_name ] );
	}
	if ( isset( $_param['weight'] ) ) {
		unset( $grid_params['weight'] );
	}
	if ( $_param_name === 'items_gap' ) {
		$_param['usb_preview'] = TRUE;
	}
}
unset( $_param );

$exclude_params = array(
	'arrows_ver_pos',
	'arrows_ver_offset',
	'arrows_hor_pos',
	'arrows_hor_offset',
	'arrows_gap',
	'arrows_disabled',
);

foreach ( $carousel_params as $_param_name => &$_param ) {

	// Change default values for correct fallback
	if ( $_param_name === 'items' ) {
		$_param['std'] = '2';
	}
	if ( $_param_name === 'arrows' ) {
		$_param['std'] = '0';
	}
	if ( $_param_name === 'loop' ) {
		$_param['std'] = '1';
	}

	// Exclude new params
	if ( in_array( $_param_name, $exclude_params ) ) {
		unset( $carousel_params[ $_param_name ] );
	}
}
unset( $_param );

// Add old params separately
$carousel_params['arrows_pos'] = array(
	'title' => __( 'Horizontal Position', 'us' ),
	'type' => 'select',
	'options' => array(
		'outside' => __( 'On the Sides Outside', 'us' ),
		'inside' => __( 'On the Sides Inside', 'us' ),
	),
	'std' => 'outside',
	'cols' => 2,
	'weight' => 58,
	'show_if' => array( 'arrows', '=', 1 ),
	'group' => us_translate_x( 'Navigation', 'block title' ),
	'usb_preview' => TRUE,
);
$carousel_params['arrows_offset'] = array(
	'title' => __( 'Horizontal Offset', 'us' ),
	'type' => 'slider',
	'std' => '0px',
	'options' => array(
		'px' => array(
			'min' => -60,
			'max' => 60,
		),
		'rem' => array(
			'min' => -4.0,
			'max' => 4.0,
			'step' => 0.1,
		),
		'em' => array(
			'min' => -4.0,
			'max' => 4.0,
			'step' => 0.1,
		),
	),
	'cols' => 2,
	'weight' => 58,
	'show_if' => array( 'arrows', '=', 1 ),
	'group' => us_translate_x( 'Navigation', 'block title' ),
	'usb_preview' => TRUE,
);

return array(
	'title' => __( 'Carousel', 'us' ),
	'category' => __( 'Deprecated', 'us' ),
	'icon' => 'fas fa-laptop-code',
	'class' => 'improve_list_elm_ui',
	'usb_reload_element' => TRUE,
	'deprecated' => TRUE,
	'alternative_elms' => implode( ', ', array(
		__( 'Post Carousel', 'us' ),
		__( 'Product Carousel', 'us' ),
		__( 'Term Carousel', 'us' ),
	) ),
	'params' => us_set_params_weight(
		$grid_params,
		$carousel_params,
		$conditional_params,
		$design_options_params
	),
	'fallback_params' => array(
		'carousel_arrows',
		'carousel_arrows_style',
		'carousel_arrows_size',
		'carousel_arrows_pos',
		'carousel_arrows_offset',
		'carousel_items_offset',
		'carousel_dots',
		'carousel_center',
		'carousel_slideby',
		'carousel_loop',
		'carousel_autoheight',
		'carousel_fade',
		'carousel_autoplay',
		'carousel_interval',
		'carousel_autoplay_smooth',
		'carousel_speed',
		'carousel_transition',
		'columns',
		'breakpoint_1_width',
		'breakpoint_1_cols',
		'breakpoint_1_offset',
		'breakpoint_1_autoplay',
		'breakpoint_2_width',
		'breakpoint_2_cols',
		'breakpoint_2_offset',
		'breakpoint_2_autoplay',
		'breakpoint_3_width',
		'breakpoint_3_cols',
		'breakpoint_3_offset',
		'breakpoint_3_autoplay',
		'items_offset',
	),
	'usb_init_js' => '
		$elm.usCarousel();
		$us.$window.trigger( \'scroll.waypoints\' );
		jQuery( \'[data-content-height]\', $elm ).usCollapsibleContent()
	',
);
