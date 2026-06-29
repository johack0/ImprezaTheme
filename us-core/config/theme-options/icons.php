<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Theme Options > Icons
 */


$icon_sets_config = array();

foreach ( us_config( 'icon-sets', array() ) as $icon_set_slug => $icon_set ) {
	$icon_sets_config += array(
		'icons_' . $icon_set_slug => array(
			'title' => $icon_set['set_name'],
			'title_pos' => 'side',
			'type' => 'radio',
			'options' => array(
				'default' => us_translate( 'Default' ),
				'custom' => __( 'Custom', 'us' ),
				'none' => us_translate( 'None' ),
			),
			'std' => 'default',
		),
		'icons_' . $icon_set_slug . '_custom_font' => array(
			'title_pos' => 'side',
			'description' => __( 'Link to "woff2" font file.', 'us' ),
			'type' => 'text',
			'std' => '',
			'show_if' => array( 'icons_' . $icon_set_slug, '=', 'custom' ),
			'classes' => 'for_above',
		),
	);
}

return array(
	'title' => __( 'Icons', 'us' ),
	'fields' => array(
		'used_icons_info' => array(
			'button_text' => __( 'Show used icons', 'us' ),
			'type' => 'used_icons_info',
			'classes' => 'desc_4',
		),
		'h_icons_2' => array(
			'title' => __( 'Icon Sets', 'us' ),
			'description' => __( 'If "None" is selected, the corresponding icon set won\'t load font files and won\'t appear in the icon selection of elements settings.', 'us' ),
			'type' => 'heading',
			'classes' => 'with_separator',
		),
	)
	+ $icon_sets_config +
	array(
		'fallback_icon_font' => array(
			'title' => __( 'Fallback icon font', 'us' ),
			'title_pos' => 'side',
			'description' => '<a href="' . US_HELP_PORTAL_URL . '/' . strtolower( US_THEMENAME ) . '/icons/#fallback-icon-font" target="_blank">' . __( 'Learn more', 'us' ) . '</a>',
			'type' => 'switch',
			'switch_text' => __( 'Use fallback icon font for theme UI controls', 'us' ),
			'std' => 1,
			'classes' => 'desc_2',
			'place_if' => ( US_THEMENAME === 'Impreza' ), // fallback icon font exists in Impreza only
			'show_if' => array(
				array( 'icons_fas', '!=', 'default' ),
				'and',
				array( 'icons_far', '!=', 'default' ),
				'and',
				array( 'icons_fal', '!=', 'default' ),
			),
		),
	),
);
