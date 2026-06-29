<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Output Button element
 *
 * @var $link string Link json variables
 */

$btn_class = 'w-btn ' . us_get_btn_class( $style );
$btn_class .= $classes ?? '';

$wrapper_class = '';

if ( $us_elm_context == 'shortcode' ) {

	// Set alignment classes
	if ( $_align_classes = us_get_class_by_responsive_values( $align, /* template */'align_%s' ) ) {
		$wrapper_class .= ' ' . $_align_classes;
	}

	// Moving usb_display_never class to wrapper because buttons with gradient background cannot apply it correctly
	if ( usb_is_preview() AND strpos( $btn_class, 'usb_display_never' ) !== FALSE ) {
		$btn_class = str_replace( 'usb_display_never', '', $btn_class );
		$wrapper_class .= ' usb_display_never';
	}

	// Moving classes `hide_on_*` from the button to the wrapper
	$hide_on_prefix = 'hide_on_';
	if ( strpos( $btn_class, $hide_on_prefix ) !== FALSE ) {
		$classes = &$btn_class;
		foreach ( (array) us_get_responsive_states( /* only keys */TRUE ) as $state ) {
			$hide_classname = $hide_on_prefix . $state;
			if ( strpos( $classes, $hide_classname ) !== FALSE ) {
				$wrapper_class .= ' ' . $hide_classname;
				$classes = preg_replace( '/\s?' . $hide_classname . '/', '', $classes );
			}
		}
		unset( $classes );
	}
}

// Get link attributes
$link_atts = us_generate_link_atts( $link, /* additional data */array( 'label' => us_replace_dynamic_value( $label ) ) );

// Do not output the element with empty link, if set
if (
	empty( $link_atts['href'] )
	AND $hide_with_empty_link
	AND ! usb_is_post_preview()
) {
	return;
}

$btn_params = array(
	'html_atts' => array(
		'class' => $btn_class,
		'id' => $el_id ?? '',
	),
	'label' => $label,
	'icon' => $icon,
	'iconpos' => $iconpos,
);

$btn_params['html_atts'] += $link_atts;

// Add Custom HTML attributes and Scrolling Effects
if ( isset( $_atts ) ) {
	$btn_params['html_atts'] += $_atts;
}

// Output the element
if ( $us_elm_context == 'shortcode' ) {
	echo '<div class="w-btn-wrapper' . $wrapper_class . '">';
}

echo us_get_btn( $btn_params );

if ( $us_elm_context == 'shortcode' ) {
	echo '</div>'; // .w-btn-wrapper 
}
