<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Output cart element
 *
 * @var $icon           int
 * @var $dropdown_effect string Dropdown Effect
 * @var $icon_size      int
 * @var $design_options array
 * @var $classes        string
 * @var $id             string
 */

if ( ! class_exists( 'woocommerce' ) ) {
	return;
}

$_atts['class'] = 'w-cart';
$_atts['class'] .= $classes ?? '';

if ( $vstretch ) {
	$_atts['class'] .= ' height_full';
}
if ( $hide_empty ) {
	$_atts['class'] .= ' hide_if_empty';
}

$content_atts = array(
	'class' => 'w-cart-content',
);

$link_atts = array(
	'class' => 'w-cart-link',
	'href' => wc_get_cart_url(),
	'aria-label' => us_translate( 'Cart', 'woocommerce' ),
);

if ( $show_content ) {

	$_atts['class'] .= ' layout_' . $content_layout;
	$_atts['class'] .= ' shadow_' . $content_shadow;

	// Content Layout: Dropdown
	if ( $content_layout == 'dropdown' ) {
		$_atts['class'] .= ' dropdown_' . $dropdown_effect;
		$_atts['class'] .= ' drop_on_' . $drop_on;

		// Content Layout: Panel
	} else {
		$content_atts['id'] = uniqid( 'cart_content_' );
		$content_atts['role'] = 'dialog';

		$link_atts['role'] = 'button';
		$link_atts['aria-expanded'] = 'false';
		$link_atts['aria-haspopup'] = 'dialog';
		$link_atts['aria-controls'] = $content_atts['id'];

		if ( $show_content_after_ajax ) {
			$_atts['class'] .= ' open_on_ajax';
		}
	}

	// No content
} else {
	$_atts['class'] .= ' hide_content';
}

$quantity_inline_css = us_prepare_inline_css(
	array(
		'background' => us_get_color( $quantity_color_bg, /* Gradient */ TRUE ),
		'color' => us_get_color( $quantity_color_text ),
	)
);

$close_btn_atts = array(
	'aria-label' => us_translate( 'Close' ),
	'class' => 'w-cart-closer',
	'type' => 'button',
);

// Set quantity for AMP pages because JS isn't supported
$quantity = ( us_amp() AND class_exists( 'WC_Cart' ) ) ? WC()->cart->get_cart_contents_count() : '';

if ( ! $quantity ) {
	$_atts['class'] .= ' empty';
}

echo '<div' . us_implode_atts( $_atts ) . '>';
echo '<a' . us_implode_atts( $link_atts ) . '>';
echo '<span class="w-cart-icon">';

if ( ! empty( $icon ) ) {
	echo us_prepare_icon_tag( $icon );
}

echo '<span class="w-cart-quantity"' . $quantity_inline_css . '>' . $quantity . '</span>';
echo '</span>'; // w-cart-icon
echo '</a>'; // w-cart-link

if ( ! us_amp() ) {
	
	// Notification
	echo '<div class="w-cart-notification"><div>';
	echo sprintf( us_translate_n( '%s has been added to your cart.', '%s have been added to your cart.', 1, 'woocommerce' ), '<span class="product-name">' . us_translate( 'Product', 'woocommerce' ) . '</span>' );
	echo '</div></div>'; // w-cart-notification

	// Content
	if ( ! empty( $btn_size ) ) {
		$content_atts['style'] = '--btn-size:' . $btn_size;
	}
	echo '<div' . us_implode_atts( $content_atts ) . '>';
	if ( $content_layout != 'dropdown' ) {
		echo '<button' . us_implode_atts( $close_btn_atts ) . '></button>';
	}
	the_widget( 'WC_Widget_Cart', 'title=0' ); // required widget to calculate quantity via AJAX
	echo '</div>'; // w-cart-content
}

echo '</div>'; // w-cart
