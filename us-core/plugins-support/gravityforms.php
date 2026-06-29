<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Gravity Forms support
 *
 * @link http://www.gravityforms.com/
 */

if ( ! class_exists( 'GFForms' ) ) {
	return;
}

// Add theme styling
if (
	defined( 'US_DEV' )
	OR ! us_get_option( 'optimize_assets' )
	OR usb_is_post_preview()
) {
	add_action( 'wp_enqueue_scripts', 'us_gforms_add_styles', 14 );
	function us_gforms_add_styles( $styles ) {
		global $us_template_directory_uri;
		$min_ext = defined( 'US_DEV' ) ? '' : '.min';
		wp_enqueue_style( 'us-gravityforms', $us_template_directory_uri . '/common/css/plugins/gravityforms' . $min_ext . '.css', array(), US_THEMEVERSION, 'all' );
	}
}

// Add selectors of submit button to apply the default Button Style
add_filter( 'us_default_btn_selector', function( $selector ) {
	$selector .= '[type=submit].gform_button, .woocommerce [type=submit].gform_button, ';
	return $selector;
} );

add_filter( 'us_default_btn_selector_hover', function( $selector ) {
	$selector .= '.no-touch [type=submit].gform_button:hover,';
	return $selector;
} );

// Remove plugin's datepicker CSS
if ( ! function_exists( 'us_gforms_remove_styles' ) ) {
	add_action( 'wp_enqueue_scripts', 'us_gforms_remove_styles', 15 );
	function us_gforms_remove_styles() {
		wp_dequeue_style( 'gforms_datepicker_css' );
		wp_deregister_style( 'gforms_datepicker_css' );
	}
}

if ( ! function_exists( 'us_gforms_disable_buffer' ) ) {
	add_action( 'init', 'us_gforms_disable_buffer', 9 );
	/**
	 * Improve performance on LiteSpeed servers when using GFForms.
	 *
	 * https://help.us-themes.com/impreza/tickets/62919/#reply-62919
	 */
	function us_gforms_disable_buffer() {
		if ( is_admin() ) {
			global $pagenow;
			if (
				$pagenow == 'admin.php'
				AND ( us_arr_path( $_GET, 'page' ) == 'gf_edit_forms' OR us_arr_path( $_GET, 'page' ) == 'gf_entries' )
			) {
				return;
			}
			if ( method_exists( 'GFForms', 'init_buffer' ) ) {
				remove_action( 'init', array( 'GFForms', 'init_buffer' ) );
			}
		}
	}
}
