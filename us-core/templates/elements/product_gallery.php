<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * WooCommerce Product gallery
 */

if ( ! class_exists( 'woocommerce' ) ) {
	return;
}

global $product;

if ( ! $product AND ! usb_is_template_preview() ) {
	return;
}

$_atts['class'] = 'w-post-elm product_gallery';
$_atts['class'] .= $classes ?? '';

if ( ! empty( $el_id ) AND $us_elm_context == 'shortcode' ) {
	$_atts['id'] = $el_id;
}

// Output the element
echo '<div' . us_implode_atts( $_atts ) . '>';

// Live preview for Reusable Block / Page Template
if ( usb_is_template_preview() ) {

	// Find the last modified product and use its gallery for preview
	$args = array(
		'post_type' => 'product',
		'orderby' => 'modified',
		'numberposts' => 1,
		'fields' => 'ids',
	);
	if ( $ids = get_posts( $args ) AND $latest_product = wc_get_product( $ids[0] ) ) {

		$product = $latest_product;

		// if no product found show a placeholder
	} else {
		echo us_get_img_placeholder();
	}
}

if ( $product ) {
	wc_get_template( 'single-product/product-image.php' );

	if ( us_get_option( 'product_gallery', 'slider' ) == 'slider' ) {
		?>
		<div class="us-thumbs-nav">
			<button type="button" class="us-thumb-prev" tabindex="-1"></button>
			<button type="button" class="us-thumb-next" tabindex="-1"></button>
		</div>
		<?php
	}
}

echo '</div>';
