<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * TablePress
 *
 * @link https://tablepress.org
 */

if ( ! class_exists( 'TablePress' ) ) {
	return;
}

if ( ! function_exists( 'us_tablepress_add_editor_buttons' ) ) {

	add_action( 'usb_enqueue_assets_for_builder', 'us_tablepress_add_editor_buttons', 20 );

	/**
	 * Register actions to add "Table" button to "HTML editor" and "Visual editor" toolbars.
	 */
	function us_tablepress_add_editor_buttons() {

		if ( is_admin() AND usb_is_builder_page() ) {

			$admin_controller = TablePress::load_controller( 'Admin' );
			$admin_controller->add_editor_buttons();

			// Skip adding a button
			remove_filter( 'mce_buttons', array( $admin_controller, 'add_tinymce_button' ) );
		}
	}
}
