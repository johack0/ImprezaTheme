<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Add to cart element
 */

if ( ! class_exists( 'woocommerce' ) ) {
	return;
}

global $product;

// Never output this element inside Term List or User List
if ( us_in_the_loop() AND us_get_loop_item_type() != 'post' ) {
	return;
}

$classes = $classes ?? '';

// Output WooCommerce Add to cart
if ( $us_elm_context == 'shortcode' ) {

	// Do not output this shortcode on the front-end of non-product pages
	if ( ! $product AND ! usb_is_post_preview() ) {
		return;
	}

	$_atts['class'] = 'w-post-elm add_to_cart';
	$_atts['class'] .= ' qty-btn-style_' . $qty_btn_style;
	$_atts['class'] .= $classes;
	$_atts['style'] = '';

	if ( empty( $view_cart_link ) ) {
		$_atts['class'] .= ' no_view_cart_link';
	}
	if ( empty( $show_qty ) ) {
		$_atts['class'] .= ' hide_qty';
	}
	if ( $btn_fullwidth ) {
		$_atts['class'] .= ' btn_fullwidth';
	}
	if ( $btn_size ) {
		$_atts['style'] .= sprintf( '--btn-size:%s;', $btn_size );
	}
	if ( $qty_btn_size ) {
		$_atts['style'] .= sprintf( '--qty-btn-size:%s;', $qty_btn_size );
	}
	if (
		is_object( $product )
		AND (
			$product->is_sold_individually()
			OR (
				$product->managing_stock()
				AND $product->get_stock_quantity() === 1
			)
		)
	) {
		$_atts['class'] .= ' is_sold_individually';
	}

	// Control buttons to quantity
	$qty_btn_minus = '<input type="button" value="-" class="minus" disabled>';
	$qty_btn_plus = '<input type="button" value="+" class="plus">';

	// For correct button placeholder preview in Live builder
	if ( usb_is_template_preview() ) {
		$_atts['class'] .= ' woocommerce';
	}

	if ( ! empty( $el_id ) ) {
		$_atts['id'] = $el_id;
	}

	echo '<div' . us_implode_atts( $_atts ) . '>';
	if ( is_object( $product ) AND method_exists( $product, 'get_type' ) ) {

		// Add control buttons to quantity
		if (
			! $product->is_sold_individually()
			AND ! ( $product->managing_stock() AND $product->get_stock_quantity() === 1 )
		) {
			add_action( 'woocommerce_before_quantity_input_field', function () use ( $qty_btn_minus ) { echo $qty_btn_minus; } );
			add_action( 'woocommerce_after_quantity_input_field', function () use ( $qty_btn_plus ) { echo $qty_btn_plus; } );
		}
		woocommerce_template_single_add_to_cart();

		/*
		 * Checking if both woocommerce_output_all_notices and wc_print_notices functions exist
		 * because woocommerce_output_all_notices uses wc_print_notices,
		 * however these functions being included separately
		 */
		if (
			function_exists( 'woocommerce_output_all_notices' )
			AND function_exists( 'wc_print_notices' )
		) {
			woocommerce_output_all_notices();
		}

		// Output placeholder for Live Builder for Page Template / Reusable Block
	} elseif ( usb_is_template_preview() ) {
		echo '<div class="cart">';
		echo '<div class="quantity">';
		echo '<input type="button" value="-" class="minus" disabled>';
		echo '<input type="number" value="1" class="input-text qty text">';
		echo '<input type="button" value="+" class="plus">';
		echo '</div>';
		echo '<div class="single_add_to_cart_button button alt">' . us_translate( 'Add to cart', 'woocommerce' ) . '</div>';
		echo '</div>';
	}
	echo '</div>';

} elseif ( function_exists( 'woocommerce_template_loop_add_to_cart' ) ) {
	add_filter( 'woocommerce_product_add_to_cart_text', 'us_add_to_cart_text', 99, 2 );
	add_filter( 'woocommerce_loop_add_to_cart_link', 'us_add_to_cart_text_replace', 99, 3 );

	if ( us_design_options_has_property( $css, 'border-radius' ) ) {
		$classes .= ' has_border_radius';
	}
	if ( us_design_options_has_property( $css, 'font-size' ) ) {
		$classes .= ' has_font_size';
	}
	if ( empty( $view_cart_link ) ) {
		$classes .= ' no_view_cart_link';
	}

	echo '<div class="w-btn-wrapper woocommerce' . $classes . '">';
	woocommerce_template_loop_add_to_cart();
	echo '</div>';

	remove_filter( 'woocommerce_product_add_to_cart_text', 'us_add_to_cart_text', 99 );
	remove_filter( 'woocommerce_loop_add_to_cart_link', 'us_add_to_cart_text_replace', 99 );
}
