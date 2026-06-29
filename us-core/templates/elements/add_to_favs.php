<?php defined( 'ABSPATH' ) or die( 'This script cannot be accessed directly.' );

/**
 * Add to Favorites element
 */

/** @var string $label_before_adding
 * @var string $label_after_adding
 * @var string $message_for_non_registered
 * @var string $message_after_adding
 * @var bool $show_icon
 */

// Cases when the element shouldn't be shown
if ( us_in_the_loop() AND us_get_loop_item_type() != 'post' ) {
	return;
}
if ( ! us_in_the_loop() AND is_archive() ) {
	return;
}

$_atts['class'] = 'w-btn-wrapper for_add_to_favs';
$_atts['class'] .= $classes ?? '';

// When some values are set in Design options, add the specific classes
if ( us_design_options_has_property( $css, array( 'width', 'max-width' ) ) ) {
	$_atts['class'] .= ' has_width';
}
if ( us_design_options_has_property( $css, array( 'height', 'max-height' ) ) ) {
	$_atts['class'] .= ' has_height';
}
if ( us_design_options_has_property( $css, array( 'background-color' ) ) ) {
	$_atts['class'] .= ' has_bg_color';
}
if ( us_design_options_has_property( $css, array( 'font-size' ) ) ) {
	$_atts['class'] .= ' has_font_size';
}

if ( ! empty( $el_id ) ) {
	$_atts['id'] = $el_id;
}

$btn_params = array(
	'html_atts' => array(
		'class' => 'w-btn us_add_to_favs ' . ( $style ? us_get_btn_class( $style ) : 'us-btn-style_0' ),
	),
	'icon' => $show_icon ? 'far|heart' : '',
);

$post_ID = us_get_current_id();
$label_before_adding_std = us_config( 'elements/add_to_favs.params.label_before_adding.std' );
$label_after_adding_std = us_config( 'elements/add_to_favs.params.label_after_adding.std' );

if ( in_array( $post_ID, us_get_user_favorite_post_ids() ) ) {
	$btn_params['html_atts']['class'] .= ' added';
	$btn_params['label'] = $label_after_adding;
	if ( $btn_params['label'] == '' ) {
		$btn_params['html_atts']['aria-label'] = $label_after_adding_std;
	}

} else {
	$btn_params['label'] = $label_before_adding;
	if ( $btn_params['label'] == '' ) {
		$btn_params['html_atts']['aria-label'] = $label_before_adding_std;
	}
}

$js_data = array(
	'post_ID' => $post_ID,
	'labelAfterAdding' => $label_after_adding ?: $label_after_adding_std,
	'labelBeforeAdding' => $label_before_adding ?: $label_before_adding_std,
	'userLoggedIn' => is_user_logged_in(),
	'allowGuests' => apply_filters( 'us_allow_guest_favs', TRUE ),
);

$btn_params['html_atts']['onclick'] = us_pass_data_to_js( $js_data, FALSE );

// Output the element
$output = '<div' . us_implode_atts( $_atts ) . '>';

$output .= us_get_btn( $btn_params );

if ( ! is_user_logged_in() AND ! apply_filters( 'us_allow_guest_favs', TRUE ) ) {
	$message_for_non_registered = us_replace_dynamic_value( $message_for_non_registered );
	$output .= '<span class="us-add-to-favs-tooltip not-logged-in">' . strip_tags( $message_for_non_registered, '<a><br><strong>' ) . '</span>';

} elseif ( $message_after_adding ) {
	$message_after_adding = us_replace_dynamic_value( $message_after_adding );
	$output .= '<span class="us-add-to-favs-tooltip message-after-adding">' . strip_tags( $message_after_adding, '<a><br><strong>' ) . '</span>';
}

$output .= '</div>';

echo $output;
