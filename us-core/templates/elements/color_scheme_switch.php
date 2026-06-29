<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Output Color Scheme Switch element
 *
 */

$_atts = array(
	'class' => 'w-color-switch',
	'style' => '',
);

$_atts['class'] .= $classes ?? '';

if ( ! empty( $el_id ) ) {
	$_atts['id'] = $el_id;
}

if ( $inactive_switch_bg ) {
	$_atts['style'] .= '--color-inactive-switch-bg:' . us_get_color( $inactive_switch_bg, TRUE ) . ';';
}
if ( $active_switch_bg ) {
	$_atts['style'] .= '--color-active-switch-bg:' . us_get_color( $active_switch_bg, TRUE ) . ';';
}

$scheme_output = '';
global $us_color_scheme_switch_is_used;

// Output JS and CSS only once if multiple switches are shown
if ( ! $us_color_scheme_switch_is_used ) {
	$us_color_scheme_switch_is_used = TRUE;

	$color_schemes = us_get_color_schemes();

	/**
	 * Get Global Dark Theme scheme and override scheme from Color Scheme Switch settings
	 *
	 * Note: This setting can return "0" if 1st Color Scheme is selected, so don't use it in conditions
	 */
	$global_dark_theme = us_get_option( 'dark_theme', 'none' );
	if ( $global_dark_theme !== 'none' ) {
		$color_scheme = $global_dark_theme;
	}

	if ( isset( $color_schemes[ $color_scheme ]['values'] ) ) {
		$scheme_output .= '<style id="us-color-scheme-switch-css">';
		$scheme_output .= 'html.us-color-scheme-on {';

		foreach( $color_schemes[ $color_scheme ]['values'] as $color_schemes_option => $color_value ) {
			if ( ! empty( $color_value ) ) {
				$scheme_output .= '--' . str_replace( '_', '-', $color_schemes_option ) . ': ' . us_get_color( $color_value, FALSE, FALSE ) . ';';

				// Add separate values from color pickers that support gradients
				foreach( us_config( 'theme-options.colors.fields' ) as $color_option => $color_option_params ) {
					if ( ! empty( $color_option_params['with_gradient'] ) AND $color_option === $color_schemes_option ) {
						$scheme_output .= '--' . str_replace( '_', '-', $color_schemes_option ) . '-grad: ' . us_get_color( $color_value, TRUE, FALSE ) . ';';
					}
				}

				if ( $color_schemes_option === 'color_content_primary' ) {
					$scheme_output .= '--color-content-primary-faded:' . us_hex2rgba( us_get_color( $color_value, FALSE, FALSE ), 0.15 ) . ';';
				}
			}
		}

		$scheme_output .= '}';
		$scheme_output .= '</style>';
	}
}

// Text before switch
if ( $text_before !== '' OR usb_is_preview() ) {
	$text_before = '<span class="w-color-switch-before">' . $text_before . '</span>';
}

// Text after switch
if ( $text_after !== '' OR usb_is_preview() ) {
	$text_after = '<span class="w-color-switch-after">' . $text_after . '</span>';
}

// Checked logic
$checked = FALSE;
if ( ! empty( $_COOKIE['us_color_scheme_switch_is_on'] ) AND $_COOKIE['us_color_scheme_switch_is_on'] !== 'false' ) {
	$checked = TRUE;
}

// Output the element
$output = '<div' . us_implode_atts( $_atts ) . '>';
$output .= $scheme_output;
$output .= '<label>';
$output .= '<input class="screen-reader-text" type="checkbox" name="us-color-scheme-switch"' . checked( $checked, TRUE, FALSE ) . '>';
$output .= $text_before;
$output .= '<span class="w-color-switch-box"><i></i></span>';
$output .= $text_after;
$output .= '</label>';
$output .= '</div>';

echo $output;
