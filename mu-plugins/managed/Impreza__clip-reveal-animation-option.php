<?php
/**
 * Plugin Name: Impreza - Clip Reveal Animation
 * Description: Aggiunge l'animazione Clip Reveal alla lista Animation senza sostituire Bounce.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const IMPREZA_CLIP_REVEAL_ANIMATION = 'ht_clip_reveal';

/**
 * Aggiunge Clip Reveal alla select Design > Animation > Animation Name.
 *
 * @param array $design_options Configurazione corrente delle Design Options.
 * @return array Configurazione aggiornata.
 */
function impreza_add_clip_reveal_animation_option( $design_options ) {
	if (
		empty( $design_options['css']['params']['animation-name']['options'] )
		|| ! is_array( $design_options['css']['params']['animation-name']['options'] )
	) {
		return $design_options;
	}

	$options = $design_options['css']['params']['animation-name']['options'];

	if ( isset( $options[ IMPREZA_CLIP_REVEAL_ANIMATION ] ) ) {
		return $design_options;
	}

	$updated_options = array();
	$inserted        = false;

	foreach ( $options as $value => $label ) {
		$updated_options[ $value ] = $label;

		if ( $value === 'bounce' ) {
			$updated_options[ IMPREZA_CLIP_REVEAL_ANIMATION ] = __( 'Clip Reveal', 'us' );
			$inserted = true;
		}
	}

	if ( ! $inserted ) {
		$updated_options[ IMPREZA_CLIP_REVEAL_ANIMATION ] = __( 'Clip Reveal', 'us' );
	}

	$design_options['css']['params']['animation-name']['options'] = $updated_options;

	return $design_options;
}
add_filter( 'us_config_elements_design_options', 'impreza_add_clip_reveal_animation_option', 40 );

/**
 * Registra i keyframes della nuova animazione.
 */
function impreza_enqueue_clip_reveal_animation_css() {
	$css = '
@keyframes ht_clip_reveal {
	0% {
		opacity: 0;
		clip-path: inset(30%);
	}
	100% {
		opacity: 1;
		clip-path: inset(0%);
	}
}
.us_animate_ht_clip_reveal {
	animation-name: ht_clip_reveal;
}
';

	wp_register_style( 'impreza-clip-reveal-animation', false, array(), '1.0.0' );
	wp_enqueue_style( 'impreza-clip-reveal-animation' );
	wp_add_inline_style( 'impreza-clip-reveal-animation', $css );
}
add_action( 'wp_enqueue_scripts', 'impreza_enqueue_clip_reveal_animation_css', 120 );
add_action( 'admin_enqueue_scripts', 'impreza_enqueue_clip_reveal_animation_css', 120 );
