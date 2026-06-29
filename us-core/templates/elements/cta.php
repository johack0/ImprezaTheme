<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Shortcode: us_cta
 *
 * Dev note: if you want to change some of the default values or acceptable attributes, overload the shortcodes config.
 *
 * @var   $shortcode      string Current shortcode name
 * @var   $shortcode_base string The original called shortcode name (differs if called an alias)
 * @var   $content        string Shortcode's inner content
 * @var   $classes        string Extend class names
 *
 * @param $title		 string ActionBox title
 * @param $title_tag	 string Title HTML tag: 'div' / 'h2'/ 'h3'/ 'h4'/ 'h5'/ 'h6'/ 'p'
 * @param $title_size	 string Title Size
 * @param $color		 string ActionBox color style: 'primary' / 'secondary' / 'light' / 'custom'
 * @param $bg_color		 string Background color
 * @param $text_color	 string Text color
 * @param $controls		 string Button(s) location: 'right' / 'bottom'
 * @param $btn_label	 string Button 1 label
 * @param $btn_link		 string Button 1 link in a serialized format: 'url:http%3A%2F%2Fwordpress.org|title:WP%20Website|target:_blank|rel:nofollow'
 * @param $btn_style	 string Button 2 Style: 'primary' / 'secondary' / 'light' / 'contrast' / 'black' / 'white'
 * @param $btn_size		 string Button 1 size
 * @param $btn_icon		 string Button 1 icon
 * @param $btn_iconpos	 string Button 1 icon position: 'left' / 'right'
 * @param $second_button bool Has second button?
 * @param $btn2_label	 string Button 2 label
 * @param $btn2_link	 string Button 2 link in a serialized format: 'url:http%3A%2F%2Fwordpress.org|title:WP%20Website|target:_blank|rel:nofollow'
 * @param $btn2_style	 string Button 2 Style: 'primary' / 'secondary' / 'light' / 'contrast' / 'black' / 'white'
 * @param $btn2_size	 string Button 2 size
 * @param $btn2_icon	 string Button 2 icon
 * @param $btn2_iconpos	 string Button 2 icon position: 'left' / 'right'
 * @param $el_class		 string Extra class name
 * @param $el_id		 string Element ID
 * @param $css			 string CSS box
 */

$_atts['class'] = 'w-actionbox';
$_atts['class'] .= $classes ?? '';
$_atts['class'] .= ' color_' . $color;
$_atts['class'] .= ' controls_' . $controls;

if ( ! empty( $el_id ) ) {
	$_atts['id'] = $el_id;
}

// Button keys that will be parsed
$btn_prefixes = array( 'btn' );
if ( $second_button ) {
	$btn_prefixes[] = 'btn2';
}

$buttons = array();

foreach ( $btn_prefixes as $prefix ) {

	if ( empty( ${$prefix . '_label'} ) ) {
		continue;
	}

	$btn_params = array(
		'html_atts' => array(
			'class' => 'w-btn ' . us_get_btn_class( ${$prefix . '_style'} ),
		),
		'label' => ${$prefix . '_label'},
		'icon' => ${$prefix . '_icon'},
		'iconpos' => ${$prefix . '_iconpos'},
	);

	if ( ${$prefix . '_size'} ) {
		$btn_params['html_atts']['style'] = 'font-size:' . ${$prefix . '_size'};
	}

	$btn_params['html_atts'] += us_generate_link_atts( ${$prefix . '_link'} );

	$buttons[ $prefix ] = us_get_btn( $btn_params );
}

// Output the element
$output = '<div' . us_implode_atts( $_atts ) . '>';
$output .= '<div class="w-actionbox-text">';

// Title
if ( ! empty( $title ) OR usb_is_post_preview() ) {

	$title = us_replace_dynamic_value( $title );
	$title = wptexturize( $title );

	$title_atts = array(
		'class' => 'w-actionbox-title',
		'style' => '',
	);
	if ( ! empty( $title_size ) ) {
		$title_atts['style'] = 'font-size:' . $title_size;
	}
	$output .= '<' . $title_tag . us_implode_atts( $title_atts ) . '>' . $title . '</' . $title_tag . '>';
}

// Description
if ( ! empty( $content ) OR usb_is_post_preview() ) {
	$output .= '<div class="w-actionbox-description">';
	$output .= do_shortcode( wpautop( us_replace_dynamic_value( $content ) ) );
	$output .= '</div>';
}

$output .= '</div>'; // .w-actionbox-text

if ( ! empty( $buttons ) ) {
	$output .= '<div class="w-actionbox-controls">' . implode( '', $buttons ) . '</div>';
}

$output .= '</div>';// .w-actionbox

echo $output;
