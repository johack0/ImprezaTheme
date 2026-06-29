<?php
/**
 * Plugin Name: Impreza - Background Position Center
 * Description: Aggiunge l'opzione "center" alle impostazioni di posizione sfondo in Impreza (Row, Grid, Header, Menu, Theme Options).
 * Version: 1.0.0
 */

// Prevenire accesso diretto
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aggiunge l'opzione 'center' alle opzioni di background position.
 * In CSS, 'center' è equivalente a 'center center'.
 *
 * @param array $options Le opzioni esistenti.
 * @return array Le opzioni con 'center' aggiunto.
 */
function add_bg_position_center_options( array $options ): array {
	// Evita duplicati se già presente
	if ( isset( $options['center'] ) ) {
		return $options;
	}

	$center_label = function_exists( 'us_translate' ) ? us_translate( 'Center' ) : 'Center';
	$center_option = array( 'center' => $center_label );

	return array_merge( $options, $center_option );
}

/**
 * Modifica il campo bg_img_position in grid-settings.
 *
 * @param array $config Configurazione grid-settings.
 * @return array Configurazione modificata.
 */
function add_bg_position_center_grid_settings( array $config ): array {
	if ( isset( $config['options']['global']['bg_img_position']['options'] ) ) {
		$config['options']['global']['bg_img_position']['options'] = add_bg_position_center_options(
			$config['options']['global']['bg_img_position']['options']
		);
	}

	return $config;
}

/**
 * Modifica il campo bg_img_position in header-settings.
 *
 * @param array $config Configurazione header-settings.
 * @return array Configurazione modificata.
 */
function add_bg_position_center_header_settings( array $config ): array {
	foreach ( $config['options'] ?? array() as $section => $options ) {
		if ( isset( $options['bg_img_position']['options'] ) ) {
			$config['options'][ $section ]['bg_img_position']['options'] = add_bg_position_center_options(
				$options['bg_img_position']['options']
			);
		}
	}

	return $config;
}

/**
 * Modifica il campo bg_image_position in menu-dropdown.
 *
 * @param array $config Configurazione menu-dropdown.
 * @return array Configurazione modificata.
 */
function add_bg_position_center_menu_dropdown( array $config ): array {
	if ( isset( $config['bg_image_position']['options'] ) ) {
		$config['bg_image_position']['options'] = add_bg_position_center_options(
			$config['bg_image_position']['options']
		);
	}

	return $config;
}

/**
 * Modifica il campo us_bg_pos in elements/vc_row.
 *
 * @param array $config Configurazione elemento vc_row.
 * @return array Configurazione modificata.
 */
function add_bg_position_center_vc_row( array $config ): array {
	if ( isset( $config['params']['us_bg_pos']['options'] ) ) {
		$config['params']['us_bg_pos']['options'] = add_bg_position_center_options(
			$config['params']['us_bg_pos']['options']
		);
	}

	return $config;
}

/**
 * Aggiunge "center" agli esempi di background position nelle Design Options.
 *
 * @param array $config Configurazione elements_misc.
 * @return array Configurazione modificata.
 */
function add_bg_position_center_elements_misc( array $config ): array {
	if ( isset( $config['desc_bg_pos'] ) && strpos( $config['desc_bg_pos'], 'center' ) === false ) {
		$config['desc_bg_pos'] .= ', <span class="usof-example">center</span>';
	}

	return $config;
}

/**
 * Modifica body_bg_image_position in theme-options (layout).
 *
 * @param array $config Configurazione theme-options.
 * @return array Configurazione modificata.
 */
function add_bg_position_center_theme_options( array $config ): array {
	if (
		isset( $config['layout']['fields']['body_bg_image_position']['options'] )
		&& is_array( $config['layout']['fields']['body_bg_image_position']['options'] )
	) {
		$config['layout']['fields']['body_bg_image_position']['options'] = add_bg_position_center_options(
			$config['layout']['fields']['body_bg_image_position']['options']
		);
	}

	return $config;
}

add_filter( 'us_config_elements_misc', 'add_bg_position_center_elements_misc', 20 );
add_filter( 'us_config_grid-settings', 'add_bg_position_center_grid_settings', 20 );
add_filter( 'us_config_header-settings', 'add_bg_position_center_header_settings', 20 );
add_filter( 'us_config_menu-dropdown', 'add_bg_position_center_menu_dropdown', 20 );
add_filter( 'us_config_theme-options', 'add_bg_position_center_theme_options', 20 );

// Filter per elements/vc_row - il config name contiene lo slash
add_filter( 'us_config_elements/vc_row', 'add_bg_position_center_vc_row', 20 );
