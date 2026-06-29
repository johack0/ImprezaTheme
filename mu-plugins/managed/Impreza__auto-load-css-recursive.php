<?php
/**
 * Plugin Name: Impreza - Auto Load CSS
 * Description: Carica automaticamente tutti i file CSS dalla cartella css del tema child in modo ricorsivo
 * Version: 1.0.0
 */

// Prevenire accesso diretto
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Carica ricorsivamente tutti i file CSS dalla cartella /css/ del tema attivo
 */
function carica_css_automatici_ricorsivo() {
	$path_server = get_stylesheet_directory() . '/css/';
	$path_url    = get_stylesheet_directory_uri() . '/css/';

	if ( ! file_exists( $path_server ) ) {
		return;
	}

	$directory = new RecursiveDirectoryIterator( $path_server );
	$iterator  = new RecursiveIteratorIterator( $directory );

	foreach ( $iterator as $info ) {
		if ( $info->isFile() && $info->getExtension() === 'css' ) {
			$file_full_path = $info->getPathname();
			$relative_path  = str_replace( $path_server, '', $file_full_path );
			$relative_path  = str_replace( '\\', '/', $relative_path );

			$handle = 'auto-style-' . sanitize_title( str_replace( array( '/', '.css' ), '-', $relative_path ) );

			wp_enqueue_style(
				$handle,
				$path_url . $relative_path,
				array(),
				filemtime( $file_full_path )
			);
		}
	}
}

// Esegue il caricamento dopo gli stili del parent theme per dare precedenza alle override custom.
add_action( 'wp_enqueue_scripts', 'carica_css_automatici_ricorsivo', 100 );
