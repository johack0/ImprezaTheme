<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Theme Options > Images Sizes
 */

global $pagenow;

if ( ! wp_doing_ajax() AND $pagenow == 'admin.php' ) {

	$image_sizes_list = us_get_image_sizes_list();

	$img_size_info = '<span class="usof-tooltip"><strong>';
	$img_size_info .= sprintf( __( '%s different images sizes are registered.', 'us' ), count( us_get_image_sizes_list( FALSE ) ) );
	$img_size_info .= '</strong><span class="usof-tooltip-text">';
	foreach ( us_get_image_sizes_list( FALSE ) as $size_name => $size_title ) {
		$img_size_info .= $size_title . ' <code>' . $size_name . '</code>';
		$img_size_info .= '<br>';
	}
	$img_size_info .= '</span></span><br>';

	// Add link to Media Settings admin page
	$img_size_info .= sprintf( __( 'To change the default image sizes, go to %s.', 'us' ), '<a target="_blank" href="' . admin_url( 'options-media.php' ) . '">' . us_translate( 'Media Settings' ) . '</a>' );

	// Add link to Customizing > WooCommerce > Product Images
	if ( class_exists( 'woocommerce' ) ) {
		$img_size_info .= '<br>' . sprintf(
				__( 'To change the Product image sizes, go to %s.', 'us' ), '<a target="_blank" href="' . esc_url(
					add_query_arg(
						array(
							'autofocus' => array(
								'panel' => 'woocommerce',
								'section' => 'woocommerce_product_images',
							),
							'url' => wc_get_page_permalink( 'shop' ),
						), admin_url( 'customize.php' )
					)
				) . '">' . us_translate( 'WooCommerce settings', 'woocommerce' ) . '</a>'
			);
	}
}

return array(
	'title' => us_translate( 'Image sizes' ),
	'fields' => array(

		'img_size_info' => array(
			'description' => $img_size_info ?? '',
			'type' => 'message',
			'classes' => 'color_blue for_above',
		),

		'h_image_sizes' => array(
			'title' => __( 'Additional Image Sizes', 'us' ),
			'type' => 'heading',
			'classes' => 'with_separator',
		),
		'img_size' => array(
			'type' => 'group',
			'is_accordion' => FALSE,
			'is_duplicate' => FALSE,
			'show_controls' => TRUE,
			'params' => array(
				'width' => array(
					'title' => us_translate( 'Max Width' ),
					'type' => 'slider',
					'std' => '600px',
					'options' => array(
						'px' => array(
							'min' => 0,
							'max' => 1000,
						),
					),
					'classes' => 'inline slider_below',
				),
				'height' => array(
					'title' => us_translate( 'Max Height' ),
					'type' => 'slider',
					'std' => '400px',
					'options' => array(
						'px' => array(
							'min' => 0,
							'max' => 1000,
						),
					),
					'classes' => 'inline slider_below',
				),
				'crop' => array(
					'type' => 'checkboxes',
					'options' => array(
						'crop' => __( 'Crop to exact dimensions', 'us' ),
					),
					'std' => '',
					'classes' => 'inline',
				),
			),
			'std' => array(),
		),

		'h_more_options' => array(
			'title' => __( 'More Options', 'us' ),
			'type' => 'heading',
			'classes' => 'with_separator',
		),
		'big_image_size_threshold' => array(
			'title' => __( 'Big Image Size Threshold', 'us' ),
			'title_pos' => 'side',
			'description' => sprintf( __( 'If an image height or width is above this threshold, it will be scaled down and used as the "%s".', 'us' ), us_translate( 'Full Size' ) ) . '<br><br><strong>' . __( 'Set "0px" to disable threshold.', 'us' ) . '</strong> <a target="blank" href="https://make.wordpress.org/core/2019/10/09/introducing-handling-of-big-images-in-wordpress-5-3/">' . __( 'Learn more', 'us' ) . '</a>',
			'type' => 'slider',
			'options' => array(
				'px' => array(
					'min' => 0,
					'max' => 4000,
					'step' => 20,
				),
			),
			'std' => '2560px',
			'classes' => 'desc_3',
		),
		'delete_unused_images' => array(
			'title' => __( 'Unused Thumbnails', 'us' ),
			'title_pos' => 'side',
			'description' => __( 'When this option is ON, all the thumbnails of non-registered image sizes are deleted.', 'us' ) . ' ' . __( 'It helps keep free space in your storage.', 'us' ),
			'type' => 'switch',
			'switch_text' => __( 'Delete unused image thumbnails', 'us' ),
			'std' => 0,
			'classes' => 'desc_3',
		),
	),
);
