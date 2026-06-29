<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Output Carousel-related data
 */

// Force disabling loop when nothing to rotate to avoid creating item clones
if ( $items == 'auto' ) {
	$_disable_loop = ( $items_count <= 1 );
} else {
	$_disable_loop = ( $items_count <= $items );
}
if ( $autoplay_continual_css ) {
	$_disable_loop = TRUE;
}

// Owl Carousel options https://owlcarousel2.github.io/OwlCarousel2/docs/api-options.html
$js_options = array(
	'aria_labels' => array(
		'prev' => us_translate( 'Previous' ),
		'next' => us_translate( 'Next' ),
	),
	'autoplayContinual' => $autoplay_continual_css ? FALSE : (bool) $autoplay_continual,
	'autoplayContinualCss' => (bool) $autoplay_continual_css,
	'autoplayHoverPause' => (bool) $autoplay_pause_on_hover,
	'autoplayTimeout' => intval( (float) $autoplay_timeout * 1000, 10 ),
	'autoWidth' => ( $items == 'auto' ),
	'smartSpeed' => (int) $transition_speed,
	'margin' => 0, // should always be 0, because gap in the Carousel is set by CSS only
	'mouseDrag' => $autoplay_continual_css ? FALSE : (bool) $mouse_drag,
	'rtl' => is_rtl(),
	'slideBy' => ( ! $slide_by_one OR $items == 'auto' ) ? 'page' : '1',
	'touchDrag' => $autoplay_continual_css ? FALSE : (bool) $touch_drag,
	'slideTransition' => strip_tags( $transition_timing_function ),
);

// https://owlcarousel2.github.io/OwlCarousel2/demos/animate.html
if ( $transition_animation == 'fade' ) {
	$js_options['animateIn'] = 'fadeIn';
	$js_options['animateOut'] = 'fadeOut';
}

// Responsive options https://owlcarousel2.github.io/OwlCarousel2/demos/responsive.html
$breakpoints = array();
if ( is_string( $responsive ) ) {
	$responsive = json_decode( urldecode( $responsive ), TRUE );
}
if ( ! is_array( $responsive ) ) {
	$responsive = array();
}
foreach ( $responsive as $responsive_data ) {
	if ( $responsive_data['breakpoint'] == 'laptops' ) {
		$breakpoint_width = (int) us_get_option( 'laptops_breakpoint' ) + 1;
	} elseif ( $responsive_data['breakpoint'] == 'tablets' ) {
		$breakpoint_width = (int) us_get_option( 'tablets_breakpoint' ) + 1;
	} elseif ( $responsive_data['breakpoint'] == 'mobiles' ) {
		$breakpoint_width = (int) us_get_option( 'mobiles_breakpoint' ) + 1;
	} else {
		$breakpoint_width = (int) $responsive_data['breakpoint_width'];
	}

	if ( $responsive_data['items'] == 'auto' ) {
		$_disable_loop_responsive = ( $items_count <= 1 );
	} else {
		$_disable_loop_responsive = ( $items_count <= $responsive_data['items'] );
	}
	if ( $autoplay_continual_css AND $responsive_data['autoplay'] ) {
		$_disable_loop_responsive = TRUE;
	}

	$breakpoints[ $breakpoint_width ] = array(
		'autoHeight' => (bool) $responsive_data['autoheight'],
		'autoplay' => $autoplay_continual_css ? FALSE : (bool) $responsive_data['autoplay'],
		'autoplayContinualCss' => $autoplay_continual_css AND $responsive_data['autoplay'],
		'autoWidth' => ( $responsive_data['items'] == 'auto' ),
		'center' => ( $responsive_data['items'] == 'auto' ) ? FALSE : (bool) $responsive_data['center_item'],
		'dots' => ( $autoplay_continual_css AND $responsive_data['autoplay'] ) ? FALSE : (bool) $responsive_data['dots'],
		'items' => (int) $responsive_data['items'],
		'loop' => $_disable_loop_responsive ? FALSE : (bool) $responsive_data['loop'],
		'nav' => ( $autoplay_continual_css AND $responsive_data['autoplay'] ) ? FALSE : (bool) $responsive_data['arrows'],
		'stagePadding' => (int) $responsive_data['items_offset'],
		'slideBy' => $responsive_data['items'] == 'auto' ? 'page' : '1',
		'touchDrag' => ( $autoplay_continual_css AND $responsive_data['autoplay'] ) ? FALSE : (bool) $touch_drag,
		'mouseDrag' => ( $autoplay_continual_css AND $responsive_data['autoplay'] ) ? FALSE : (bool) $mouse_drag,
	);
}
ksort( $breakpoints );

$breakpoint_widths = array_merge( array( 0 ), array_keys( $breakpoints ) ); // e.g. array( 0, 601, 1025, 1381 )

$breakpoint_values = array_values( $breakpoints );

// Options below MAY NOT be duplicated in the $js_options
$breakpoint_values[] = array(
	'items' => (int) $items,
	'autoplay' => $autoplay_continual_css ? FALSE : (bool) $autoplay,
	'autoplayContinualCss' => (bool) $autoplay_continual_css,
	'center' => ( $items == 'auto' ) ? FALSE : (bool) $center_item,
	'dots' => $autoplay_continual_css ? FALSE : (bool) $dots,
	'nav' => $autoplay_continual_css ? FALSE : (bool) $arrows,
	'autoHeight' => (bool) $autoheight,
	'autoWidth' => ( $items == 'auto' ),
	'loop' => $_disable_loop ? FALSE : (bool) $loop,
	'stagePadding' => (int) $next_item_offset,
);

$js_options['responsive'] = array_combine( $breakpoint_widths, $breakpoint_values );

unset( $breakpoints, $breakpoint_widths, $breakpoint_values );

// $vars is needed to be able to determine the current carousel by "el_class", "el_id" or etc.
$json_data['carousel_settings'] = apply_filters( 'us_carousel_js_options', $js_options, $vars );

echo '<div class="w-grid-carousel-json hidden"' . us_pass_data_to_js( $json_data ) . '></div>';
