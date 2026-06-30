<?php
/**
 * Plugin Name: Impreza - Librerie JS
 * Description: Carica GSAP, Lenis e MouseFollower per il child theme e aggiunge una pagina (Impostazioni &rarr; Librerie JS Impreza) per attivarle o disattivarle singolarmente, inclusi i singoli plugin GSAP. Separato dal MU Plugin Manager.
 * Version: 1.1.0
 */

defined( 'ABSPATH' ) || exit;

const IMPREZA_JS_LIBRARIES_OPTION             = 'impreza_js_libraries_enabled';
const IMPREZA_JS_LIBRARIES_GSAP_PLUGINS_OPTION = 'impreza_js_libraries_gsap_plugins';

/**
 * Definizione delle librerie gestibili.
 *
 * @return array<string,array<string,string>>
 */
function impreza_js_libraries_definitions() {
	return array(
		'gsap'          => array(
			'label'       => 'GSAP',
			'description' => 'Core GSAP. I singoli plugin sono attivabili qui sotto.',
		),
		'lenis'         => array(
			'label'       => 'Lenis',
			'description' => 'Smooth scroll.',
		),
		'mousefollower' => array(
			'label'       => 'MouseFollower',
			'description' => 'Cursore personalizzato (Cuberto). Caricato dal child theme come modulo ES, controllato da questo interruttore.',
		),
	);
}

/**
 * Definizione dei plugin GSAP (key => label + filename).
 *
 * @return array<string,array<string,string>>
 */
function impreza_js_libraries_gsap_plugin_definitions() {
	return array(
		'scroll'   => array( 'label' => 'ScrollTrigger', 'file' => 'ScrollTrigger.min.js' ),
		'smooth'   => array( 'label' => 'ScrollSmoother', 'file' => 'ScrollSmoother.min.js' ),
		'observer' => array( 'label' => 'Observer', 'file' => 'Observer.min.js' ),
		'text'     => array( 'label' => 'TextPlugin', 'file' => 'TextPlugin.min.js' ),
		'split'    => array( 'label' => 'SplitText', 'file' => 'SplitText.min.js' ),
		'draw'     => array( 'label' => 'DrawSVGPlugin', 'file' => 'DrawSVGPlugin.min.js' ),
		'motion'   => array( 'label' => 'MotionPathPlugin', 'file' => 'MotionPathPlugin.min.js' ),
	);
}

/**
 * Restituisce le chiavi delle librerie abilitate.
 *
 * Se l'opzione non è mai stata salvata, tutte le librerie sono attive
 * per preservare il comportamento corrente del sito.
 *
 * @return string[]
 */
function impreza_js_libraries_enabled_keys() {
	$all = array_keys( impreza_js_libraries_definitions() );
	$opt = get_option( IMPREZA_JS_LIBRARIES_OPTION, false );

	if ( false === $opt ) {
		return $all;
	}

	if ( ! is_array( $opt ) ) {
		return array();
	}

	return array_values( array_intersect( array_map( 'sanitize_key', $opt ), $all ) );
}

/**
 * Restituisce le chiavi dei plugin GSAP abilitati.
 *
 * Default (mai salvato): tutti attivi.
 *
 * @return string[]
 */
function impreza_js_libraries_gsap_enabled_plugins() {
	$all = array_keys( impreza_js_libraries_gsap_plugin_definitions() );
	$opt = get_option( IMPREZA_JS_LIBRARIES_GSAP_PLUGINS_OPTION, false );

	if ( false === $opt ) {
		return $all;
	}

	if ( ! is_array( $opt ) ) {
		return array();
	}

	return array_values( array_intersect( array_map( 'sanitize_key', $opt ), $all ) );
}

/**
 * @param string $key
 * @return bool
 */
function impreza_js_libraries_is_enabled( $key ) {
	return in_array( $key, impreza_js_libraries_enabled_keys(), true );
}

/**
 * @param string $key
 * @return bool
 */
function impreza_js_libraries_gsap_plugin_is_enabled( $key ) {
	return in_array( $key, impreza_js_libraries_gsap_enabled_plugins(), true );
}

/**
 * Accoda le librerie attive (script classici che espongono i global usati dal child).
 */
function impreza_js_libraries_enqueue() {
	$dir     = trailingslashit( get_stylesheet_directory() ) . 'minified/';
	$dir_uri = trailingslashit( get_stylesheet_directory_uri() ) . 'minified/';

	if ( impreza_js_libraries_is_enabled( 'gsap' ) ) {
		$core = $dir . 'gsap.min.js';
		if ( file_exists( $core ) ) {
			wp_enqueue_script( 'gsap-js', $dir_uri . 'gsap.min.js', array(), filemtime( $core ), true );

			foreach ( impreza_js_libraries_gsap_plugin_definitions() as $suffix => $plugin ) {
				if ( ! impreza_js_libraries_gsap_plugin_is_enabled( $suffix ) ) {
					continue;
				}
				$path = $dir . $plugin['file'];
				if ( file_exists( $path ) ) {
					wp_enqueue_script( "gsap-js-{$suffix}", $dir_uri . $plugin['file'], array( 'gsap-js' ), filemtime( $path ), true );
				}
			}
		}
	}

	if ( impreza_js_libraries_is_enabled( 'lenis' ) ) {
		$lenis = $dir . 'lenis.min.js';
		if ( file_exists( $lenis ) ) {
			wp_enqueue_script( 'lenis-js', $dir_uri . 'lenis.min.js', array(), filemtime( $lenis ), true );
		}
	}

	// MouseFollower è un modulo ES puro: viene importato dinamicamente dal child
	// (main.js) solo quando il flag è attivo. Vedi window.ImprezaJSLibs.
}
add_action( 'wp_enqueue_scripts', 'impreza_js_libraries_enqueue', 5 );

/**
 * Pubblica lo stato degli interruttori per il JS del child.
 *
 * Stampato in <head> come script classico: viene eseguito prima del modulo
 * main.js (che è deferred), così le guardie sui flag funzionano sempre.
 * I plugin GSAP sono registrati dal child in base ai global effettivamente
 * presenti, quindi qui basta lo stato delle tre librerie principali.
 */
function impreza_js_libraries_print_flags() {
	$flags = array(
		'gsap'          => impreza_js_libraries_is_enabled( 'gsap' ),
		'lenis'         => impreza_js_libraries_is_enabled( 'lenis' ),
		'mousefollower' => impreza_js_libraries_is_enabled( 'mousefollower' ),
	);

	echo '<script id="impreza-js-libraries-flags">window.ImprezaJSLibs = ' . wp_json_encode( $flags ) . ";</script>\n";
}
add_action( 'wp_head', 'impreza_js_libraries_print_flags', 1 );

/**
 * Gestisce il salvataggio del form prima del render, poi redirige.
 */
function impreza_js_libraries_handle_save() {
	if ( empty( $_POST['impreza_js_libraries_save'] ) ) {
		return;
	}

	check_admin_referer( 'impreza_js_libraries_save' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to access this page.' ) );
	}

	$all_libs = array_keys( impreza_js_libraries_definitions() );
	$selected = isset( $_POST['impreza_js_libraries_enabled'] )
		? (array) wp_unslash( $_POST['impreza_js_libraries_enabled'] )
		: array();
	$enabled  = array_values( array_intersect( array_map( 'sanitize_key', $selected ), $all_libs ) );
	update_option( IMPREZA_JS_LIBRARIES_OPTION, $enabled, false );

	$all_plugins      = array_keys( impreza_js_libraries_gsap_plugin_definitions() );
	$selected_plugins = isset( $_POST['impreza_js_libraries_gsap_plugins'] )
		? (array) wp_unslash( $_POST['impreza_js_libraries_gsap_plugins'] )
		: array();
	$enabled_plugins  = array_values( array_intersect( array_map( 'sanitize_key', $selected_plugins ), $all_plugins ) );
	update_option( IMPREZA_JS_LIBRARIES_GSAP_PLUGINS_OPTION, $enabled_plugins, false );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'                       => 'impreza-js-libraries',
				'impreza_js_libraries_saved' => '1',
			),
			admin_url( 'options-general.php' )
		)
	);
	exit;
}
add_action( 'admin_init', 'impreza_js_libraries_handle_save' );

/**
 * Registra la sottopagina in Impostazioni.
 */
function impreza_js_libraries_admin_menu() {
	add_options_page(
		'Librerie JS Impreza',
		'Librerie JS Impreza',
		'manage_options',
		'impreza-js-libraries',
		'impreza_js_libraries_render_page'
	);
}
add_action( 'admin_menu', 'impreza_js_libraries_admin_menu' );

/**
 * Render della pagina impostazioni.
 */
function impreza_js_libraries_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to access this page.' ) );
	}

	$libraries      = impreza_js_libraries_definitions();
	$enabled        = impreza_js_libraries_enabled_keys();
	$gsap_plugins   = impreza_js_libraries_gsap_plugin_definitions();
	$enabled_plugins = impreza_js_libraries_gsap_enabled_plugins();
	$gsap_on        = in_array( 'gsap', $enabled, true );
	?>
	<div class="wrap">
		<h1>Librerie JS Impreza</h1>

		<p class="description">
			Attiva o disattiva il caricamento delle librerie JavaScript usate dal child theme.
			Le modifiche hanno effetto dal prossimo caricamento della pagina front-end.
		</p>

		<?php if ( ! empty( $_GET['impreza_js_libraries_saved'] ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>Impostazioni salvate.</p>
			</div>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'impreza_js_libraries_save' ); ?>
			<input type="hidden" name="impreza_js_libraries_save" value="1">

			<table class="form-table" role="presentation">
				<tbody>
					<?php foreach ( $libraries as $key => $lib ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $lib['label'] ); ?></th>
							<td>
								<label>
									<input
										type="checkbox"
										name="impreza_js_libraries_enabled[]"
										value="<?php echo esc_attr( $key ); ?>"
										<?php checked( in_array( $key, $enabled, true ) ); ?>
										<?php echo ( 'gsap' === $key ) ? 'id="impreza-js-libraries-gsap-core"' : ''; ?>
									>
									Attiva
								</label>
								<p class="description"><?php echo esc_html( $lib['description'] ); ?></p>

								<?php if ( 'gsap' === $key ) : ?>
									<fieldset id="impreza-js-libraries-gsap-plugins" style="margin-top: 10px; padding-left: 4px; border-left: 3px solid #c3c4c7;">
										<legend class="screen-reader-text">Plugin GSAP</legend>
										<p class="description" style="margin: 0 0 6px 8px;">Plugin GSAP (richiedono il core attivo):</p>
										<?php foreach ( $gsap_plugins as $pkey => $plugin ) : ?>
											<label style="display: block; margin: 2px 0 2px 8px;">
												<input
													type="checkbox"
													name="impreza_js_libraries_gsap_plugins[]"
													class="impreza-js-libraries-gsap-plugin"
													value="<?php echo esc_attr( $pkey ); ?>"
													<?php checked( in_array( $pkey, $enabled_plugins, true ) ); ?>
													<?php disabled( ! $gsap_on ); ?>
												>
												<?php echo esc_html( $plugin['label'] ); ?>
											</label>
										<?php endforeach; ?>
									</fieldset>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php submit_button( 'Salva impostazioni' ); ?>
		</form>

		<script>
			(function () {
				var core = document.getElementById('impreza-js-libraries-gsap-core');
				var plugins = Array.prototype.slice.call(
					document.querySelectorAll('.impreza-js-libraries-gsap-plugin')
				);
				if (!core || !plugins.length) {
					return;
				}
				function sync() {
					plugins.forEach(function (cb) {
						cb.disabled = !core.checked;
						cb.closest('label').style.opacity = core.checked ? '' : '0.5';
					});
				}
				core.addEventListener('change', sync);
				sync();
			}());
		</script>
	</div>
	<?php
}
