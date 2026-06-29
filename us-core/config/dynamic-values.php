<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Predefined dynamic values.
 */

$config = array();

// Field: Text
$config['for_text'] = array(
	'global' => array(
		'{{site_title}}' => us_translate( 'Site Title' ),
	),
	'post' => array(
		'{{the_title}}' => __( 'Post Title', 'us' ),
		'{{post_type_singular}}' => __( 'Post Type (singular)', 'us' ),
		'{{post_type_plural}}' => __( 'Post Type (plural)', 'us' ),
		'{{comment_count}}' => __( 'Comments Amount', 'us' ),
	),
	'term' => array(
		'{{taxonomy_label_singular}}' => __( 'Taxonomy Name (singular)', 'us' ),
		'{{taxonomy_label_plural}}' => __( 'Taxonomy Name (plural)', 'us' ),
	),
	'acf_types' => array(
		'text',
		'number',
		'range',
		'email',
		'date_picker',
		'date_time_picker',
		'time_picker',
	),
);

// Field: Link
$config['for_link'] = array(
	'global' => array(
		'homepage' => us_translate( 'Homepage' ),
	),
	'post' => array(
		'post' => __( 'Post Link', 'us' ),
		'custom_field|us_tile_link' => sprintf( '%s: %s', __( 'Additional Settings', 'us' ), __( 'Custom Link', 'us' ) ),
		'custom_field|us_testimonial_link' => sprintf( '%s: %s', __( 'Testimonial', 'us' ), __( 'Author Link', 'us' ) ),
	),
	'user' => array(
		'author_page' => __( 'Author Archive', 'us' ),
		'author_website' => __( 'User Website (if set)', 'us' ),
	),
	'acf_types' => array(
		'email',
		'file',
		'link',
		'page_link',
		'post_object',
		'url',
	),
);

if ( us_get_option( 'enable_testimonials', 1 ) ) {
	$config['for_link']['post']['custom_field|us_testimonial_link'] = sprintf( '%s: %s', __( 'Testimonial', 'us' ), __( 'Author Link', 'us' ) );
}

// Field: Image(s)
$config['for_image'] = array(
	'global' => array(
		'{{site_icon}}' => us_translate( 'Site Icon' ),
	),
	'post' => array(
		'{{the_thumbnail}}' => us_translate_x( 'Featured image', 'post' ),
		'{{us_tile_additional_image}}' => sprintf( '%s: %s', __( 'Additional Settings', 'us' ), us_translate( 'Images' ) ),
	),
	'acf_types' => array(
		'image',
		'gallery',
	),
);

if ( class_exists( 'woocommerce' ) ) {
	$config['for_image']['post']['{{_product_image_gallery}}'] = us_translate( 'Product gallery', 'woocommerce' );
}

// Field: HTML/Textarea
$config['for_textarea'] = array(
	'acf_types' => array(
		'text',
		'textarea',
		'wysiwyg',
	),
);

return $config;
