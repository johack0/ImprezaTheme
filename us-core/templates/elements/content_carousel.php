<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Shortcode: us_content_carousel
 *
 * @var   $shortcode      string Current shortcode name
 * @var   $shortcode_base string The original called shortcode name (differs if called an alias)
 * @var   $content        string Shortcode's inner content
 * @var   $classes        string Extend class names
 */

wp_enqueue_script( 'us-owl' );

$_atts['class'] = 'w-content-carousel';
$_atts['class'] .= ' items_' . $items;
$_atts['class'] .= $classes ?? '';

// Fixes for correct work in Live Builder
if ( usb_is_preview() ) {
	$_atts['class'] .= ' wrap';
	$loop = FALSE;
	$autoplay = FALSE;
}

if ( us_design_options_has_property( $css, array( 'height', 'max-height' ) ) ) {
	$_atts['class'] .= ' has_height';
}

if ( ! empty( $el_id ) ) {
	$_atts['id'] = $el_id;
}

$carousel_atts['class'] = 'owl-carousel';
$carousel_atts['class'] .= ' valign_' . $items_valign;
$carousel_atts['class'] .= ' dotstyle_' . $dots_style;
$carousel_atts['class'] .= ' navstyle_' . $arrows_style;
$carousel_atts['class'] .= ' arrows-ver-pos_' . $arrows_ver_pos;
$carousel_atts['class'] .= ' arrows-hor-pos_' . $arrows_hor_pos;
$carousel_atts['class'] .= ' arrows-disabled_' . $arrows_disabled;
$carousel_atts['class'] .= $autoplay_continual ? ' autoplay_continual' : '';

$carousel_atts['class'] .= ' owl-responsive-2000'; // needed for responsive states switch

if ( $autoplay_pause_on_hover AND $autoplay_continual_css ) {
	$carousel_atts['class'] .= ' pause_on_hover';
}

$carousel_atts['style'] = '--items-gap:' . $items_gap . ';';
$carousel_atts['style'] .= '--transition-duration:' . $transition_speed . ';';

if ( $items == '1' AND $autoheight ) {
	$carousel_atts['class'] .= ' autoheight';
}
if ( $center_item ) {
	$carousel_atts['class'] .= ' center_item';
}
if ( $dots ) {
	$carousel_atts['class'] .= ' with_dots';
}
if ( $arrows ) {
	$carousel_atts['class'] .= ' with_arrows';

	if ( ! empty( $arrows_size ) ) {
		$carousel_atts['style'] .= '--arrows-size:' . $arrows_size . ';';
	}
	if ( ! in_array( $arrows_ver_offset, array( '', '0', '0em', '0px' ) ) ) {
		$carousel_atts['style'] .= '--arrows-ver-offset:' . $arrows_ver_offset . ';';
	}
	if ( ! in_array( $arrows_hor_offset, array( '', '0', '0em', '0px' ) ) ) {
		$carousel_atts['style'] .= '--arrows-hor-offset:' . $arrows_hor_offset . ';';
	}
	if ( ! in_array( $arrows_gap, array( '', '0', '0em', '0px' ) ) ) {
		$carousel_atts['style'] .= '--arrows-gap:' . $arrows_gap . ';';
	}
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
	'autoplayTimeout' => (int) $autoplay_timeout * 1000,
	'autoWidth' => ( $items == 'auto' ),
	'smartSpeed' => (int) $transition_speed,
	'margin' => (int) $items_gap,
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
	if ( usb_is_preview() ) {
		$responsive_data['autoplay'] = FALSE;
		$responsive_data['loop'] = FALSE;
	}
	$breakpoints[ $breakpoint_width ] = array(
		'autoHeight' => (bool) $responsive_data['autoheight'],
		'autoplay' => $autoplay_continual_css ? FALSE : (bool) $responsive_data['autoplay'],
		'autoplayContinualCss' => $autoplay_continual_css AND $responsive_data['autoplay'],
		'autoWidth' => ( $responsive_data['items'] == 'auto' ),
		'center' => ( $responsive_data['items'] == 'auto' ) ? FALSE : (bool) $responsive_data['center_item'],
		'dots' => ( $autoplay_continual_css AND $responsive_data['autoplay'] ) ? FALSE : (bool) $responsive_data['dots'],
		'items' => (int) $responsive_data['items'],
		'loop' => ( $autoplay_continual_css AND $responsive_data['autoplay'] ) ? FALSE : (bool) $responsive_data['loop'],
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
	'autoHeight' => (bool) $autoheight,
	'autoplay' => $autoplay_continual_css ? FALSE : (bool) $autoplay,
	'autoplayContinualCss' => (bool) $autoplay_continual_css,
	'autoWidth' => ( $items == 'auto' ),
	'center' => ( $items == 'auto' ) ? FALSE : (bool) $center_item,
	'dots' => $autoplay_continual_css ? FALSE : (bool) $dots,
	'items' => (int) $items,
	'loop' => $autoplay_continual_css ? FALSE : (bool) $loop,
	'nav' => $autoplay_continual_css ? FALSE : (bool) $arrows,
	'stagePadding' => (int) $next_item_offset,
);

$js_options['responsive'] = array_combine( $breakpoint_widths, $breakpoint_values );

unset( $breakpoints, $breakpoint_widths, $breakpoint_values );

$carousel_atts['onclick'] = us_pass_data_to_js( apply_filters( 'us_content_carousel_js_options', $js_options ), /* onclick */FALSE );

// Output the element
echo '<div' . us_implode_atts( $_atts ) . '>';
echo '<div' . us_implode_atts( $carousel_atts ) . '>';

echo (string) apply_filters( 'us_content_carousel_items_html', do_shortcode( $content ) );

echo '</div>'; // .owl-carousel
echo '</div>'; // .w-content-carousel
