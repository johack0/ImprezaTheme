<?php
/**
 * Plugin Name: Impreza - Admin Bar Media Link
 * Description: Adds a Media shortcut to the WordPress admin bar.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_bar_menu', static function ( WP_Admin_Bar $wp_admin_bar ) {
	if ( ! is_admin_bar_showing() || ! current_user_can( 'upload_files' ) ) {
		return;
	}

	$wp_admin_bar->add_node(
		array(
			'id'    => 'mu-admin-bar-media-link',
			'title' => 'Media',
			'href'  => admin_url( 'upload.php' ),
			'meta'  => array(
				'title' => __( 'Open Media Library', 'default' ),
			),
		)
	);
}, 90 );
