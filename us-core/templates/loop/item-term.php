<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Output single Term as list item
 */

global $us_loop_term;

$html_atts = array(
	'class' => 'w-grid-item type_term term-' . $us_loop_term->term_id . ' term-' . $us_loop_term->slug,
);

// Add items appearance animation on loading
// TODO: add animation preview for Edit Live
if ( $load_animation !== 'none' AND ! us_amp() AND ! usb_is_post_preview() ) {
	$html_atts['class'] .= ' us_animate_' . $load_animation;

	// We need to hide CSS animation before isotope.js initialization
	if ( $type === 'masonry' AND $columns > 1 ) {
		$post_atts['class'] .= ' off_autostart';
	}

	// Set "animation-delay" for every doubled amount of columns
	if ( $columns > 1 ) {
		global $us_loop_item_counter;
		$html_atts['style'] = sprintf( 'animation-delay:%ss', 0.1 * $us_loop_item_counter );

		// Calcualte columns factor for better population on single screen
		if ( $columns >= 7 ) {
			$columns_factor = 4;
		} elseif ( $columns >= 5 ) {
			$columns_factor = 3;
		} else {
			$columns_factor = 2;
		}

		if ( ( $us_loop_item_counter + 1 ) < $columns * $columns_factor ) {
			$us_loop_item_counter++;
		} else {
			$us_loop_item_counter = 0;
		}
	}
}

// Aspect ratio class
if ( us_arr_path( $grid_layout_settings, 'default.options.ratio' ) ) {
	$html_atts['class'] .= ' ratio_' . us_arr_path( $grid_layout_settings, 'default.options.ratio' );
}

// Generate background property based on image and color
$bg_img_source = us_arr_path( $grid_layout_settings, 'default.options.bg_img_source' );

// Check if image source is set and it's not from Media Library (cause it's set in listing-start.php)
$background_value = '';
if ( ! in_array( $bg_img_source, array( 'none', 'media' ) ) ) {

	$bg_file_size = us_arr_path( $grid_layout_settings, 'default.options.bg_file_size', 'full' );

	// Get Product Category thumbnail
	if ( $bg_img_source == 'featured' ) {
		$_thumbnail_id = get_term_meta( $us_loop_term->term_id, 'thumbnail_id', TRUE );
		$bg_img_url = wp_get_attachment_image_url( $_thumbnail_id, $bg_file_size );

		// Custom Field image source
	} elseif ( $_img_id = us_get_custom_field( $bg_img_source, FALSE ) ) {
		$bg_img_url = wp_get_attachment_image_url( $_img_id, $bg_file_size );
	}

	// If the image exists, combine it with other background properties
	if ( ! empty( $bg_img_url ) ) {
		$background_value = 'url(' . $bg_img_url . ') ';
		$background_value .= us_arr_path( $grid_layout_settings, 'default.options.bg_img_position' );
		$background_value .= '/';
		$background_value .= us_arr_path( $grid_layout_settings, 'default.options.bg_img_size' );
		$background_value .= ' ';
		$background_value .= us_arr_path( $grid_layout_settings, 'default.options.bg_img_repeat' );

		$bg_color = us_arr_path( $grid_layout_settings, 'default.options.color_bg' );
		$bg_color = us_get_color( $bg_color, /* Gradient */ TRUE );

		// If the color value contains gradient, add comma for correct appearance
		if ( us_is_gradient( $bg_color ) ) {
			$background_value .= ',';
		}
		$background_value .= ' ' . $bg_color;
	}
}
// Needed for support dynamic variables in grid layout background color
if (
	empty( $bg_color )
	AND $bg_color = us_arr_path( $grid_layout_settings, 'default.options.color_bg' )
	AND us_is_dynamic_variable( $bg_color )
) {
	$background_value .= us_get_color( $bg_color );
}
$inline_css = us_prepare_inline_css(
	array(
		'background' => $background_value,
	)
);

// Generate Overriding Link attributes to the whole grid item
$link_atts = us_generate_link_atts( $overriding_link, /* additional data */array( 'img_id' => get_term_meta( $us_loop_term->term_id, 'thumbnail_id', TRUE ) ) );

// If overriding link is not empty
if ( ! empty( $link_atts['href'] ) ) {
	$link_atts['class'] = 'w-grid-item-anchor';
	$link_atts['aria-label'] = $us_loop_term->name;

	// Get term thumbnail title to show it in popup
	if (
		isset( $link_atts['ref'] )
		AND $link_atts['ref'] == 'magnificPopup'
		AND $thumbnail_id = get_term_meta( $us_loop_term->term_id, 'thumbnail_id', TRUE )
		AND $attachment = get_post( $thumbnail_id )
	) {
		$link_atts['ref'] = 'magnificPopupList';

		// Get the Caption first
		$link_atts['title'] = $attachment->post_excerpt;

		// if it's empty, get the Alt
		if ( empty( $link_atts['title'] ) ) {
			$link_atts['title'] = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', TRUE );
		}

		// if it's empty, get the Title
		if ( empty( $link_atts['title'] ) ) {
			$link_atts['title'] = $us_loop_term->name;
		}
	}
}

// Apply theme filter
$html_atts['class'] = apply_filters( 'us_grid_item_classes', $html_atts['class'], $us_loop_term->term_id );

ob_start();
?>
	<div<?= us_implode_atts( $html_atts ) ?>>
		<div class="w-grid-item-h"<?= $inline_css ?>>
			<?php if ( ! empty( $link_atts['href'] ) ): ?>
				<a<?= us_implode_atts( $link_atts ) ?>></a>
			<?php endif ?>
			<?php us_output_builder_elms( $grid_layout_settings, 'default', 'middle_center', 'grid', ! empty( $link_atts['href'] ) ) ?>
		</div>
		<?php if ( $grid_layout_css = us_process_grid_layout_dynamic_values( 'term', $us_loop_term->term_id ) ): ?>
			<style><?= us_compile_css( $grid_layout_css ) ?></style>
		<?php endif ?>
	</div>
<?php
echo apply_filters( 'us_grid_listing_term', ob_get_clean() );
