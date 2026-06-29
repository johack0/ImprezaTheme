<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Output a form's hidden field
 *
 * @var $name                string Field name
 * @var $classes             string Additional field classes
 * @var $title               string Submit button title
 * @var $btn_class           string Additional button classes
 * @var $btn_inner_css       string Button inner css
 * @var $btn_size_mobiles    string Button Size on Mobiles
 *
 * @action Before the template: 'us_before_template:templates/form/submit'
 * @action After the template: 'us_after_template:templates/form/submit'
 * @filter Template variables: 'us_template_vars:templates/form/submit'
 */

$_atts['class'] = 'w-form-row for_' . $type;

if ( ! empty( $class ) ) {
	$_atts['class'] .= ' ' . $class;
}

$btn_params = array(
	'html_atts' => array(
		'class' => $btn_class ?? '',
		'style' => $btn_inner_css ?? '',
		'type' => $type,
	),
	'label' => ! empty( $title ) ? $title : us_translate( 'Submit' ),
	'icon' => $icon ?? '',
	'iconpos' => $icon_pos ?? 'left',
	'include_preloader' => TRUE,
	'force_aria_label' => TRUE,
);

if ( ! empty( $btn_size_mobiles ) ) {
	$_atts['style'] = '--btn-size-mobiles:' . $btn_size_mobiles . ';';
}

echo '<div' . us_implode_atts( $_atts ) . '>';
echo us_get_btn( $btn_params );
echo $after_btn_html ?? '';
echo '</div>'; // .w-form-row
