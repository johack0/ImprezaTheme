<?php
/**
 * Plugin Name: Impreza - Librerie JS
 * Description: Carica GSAP, Lenis e MouseFollower per il child theme e aggiunge una pagina (Impostazioni &rarr; Librerie JS Impreza) per attivarle o disattivarle singolarmente, inclusi i singoli plugin GSAP. Separato dal MU Plugin Manager.
 * Version: 1.4.0
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
 * Stato "effettivo" di caricamento di ogni componente (etichetta => bool).
 *
 * Tiene conto delle dipendenze: GSAP core risulta attivo anche se acceso solo
 * come dipendenza di MouseFollower; i plugin GSAP sono attivi solo se lo è
 * l'interruttore GSAP. Usato sia dalla console sia dal riquadro nel backend.
 *
 * @return array<string,bool>
 */
function impreza_js_libraries_effective_status() {
	$gsap_on = impreza_js_libraries_is_enabled( 'gsap' );
	$mf_on   = impreza_js_libraries_is_enabled( 'mousefollower' );

	$status = array(
		'GSAP core'     => ( $gsap_on || $mf_on ),
		'Lenis'         => impreza_js_libraries_is_enabled( 'lenis' ),
		'MouseFollower' => $mf_on,
	);

	foreach ( impreza_js_libraries_gsap_plugin_definitions() as $pkey => $plugin ) {
		$status[ $plugin['label'] ] = $gsap_on && impreza_js_libraries_gsap_plugin_is_enabled( $pkey );
	}

	return $status;
}

/**
 * Avvolge uno script UMD con due script inline che, durante la sua esecuzione,
 * nascondono define/module/exports.
 *
 * Sul sito un altro script definisce temporaneamente questi global: l'UMD di
 * GSAP/Lenis verrebbe così dirottato sul ramo CommonJS/AMD e NON creerebbe il
 * global atteso (window.gsap / window.Lenis). Neutralizzandoli solo attorno al
 * file, l'UMD prende il ramo globale corretto. Lo stack supporta l'annidamento.
 *
 * @param string $handle Handle dello script già accodato.
 */
function impreza_js_libraries_guard_umd_global( $handle ) {
	wp_add_inline_script(
		$handle,
		'window.__impUMD=window.__impUMD||[];window.__impUMD.push([window.define,window.module,window.exports]);window.define=window.module=window.exports=undefined;',
		'before'
	);
	wp_add_inline_script(
		$handle,
		'(function(){var s=(window.__impUMD||[]).pop()||[];window.define=s[0];window.module=s[1];window.exports=s[2];})();',
		'after'
	);
}

/**
 * Accoda le librerie attive (script classici che espongono i global usati dal child).
 */
function impreza_js_libraries_enqueue() {
	$dir     = trailingslashit( get_stylesheet_directory() ) . 'minified/';
	$dir_uri = trailingslashit( get_stylesheet_directory_uri() ) . 'minified/';

	// GSAP core va caricato sia quando l'interruttore GSAP è attivo, sia quando lo è
	// MouseFollower: quest'ultimo dipende da GSAP (usa gsap.quickSetter/ticker/to).
	// Così attivando MouseFollower la libreria necessaria si carica da sola.
	$gsap_core_needed = impreza_js_libraries_is_enabled( 'gsap' ) || impreza_js_libraries_is_enabled( 'mousefollower' );

	if ( $gsap_core_needed ) {
		$core = $dir . 'gsap.min.js';
		if ( file_exists( $core ) ) {
			wp_enqueue_script( 'gsap-js', $dir_uri . 'gsap.min.js', array(), filemtime( $core ), true );
			impreza_js_libraries_guard_umd_global( 'gsap-js' );

			// I plugin GSAP sono una funzionalità del solo interruttore GSAP
			// (MouseFollower non li richiede), quindi restano legati al flag 'gsap'.
			if ( impreza_js_libraries_is_enabled( 'gsap' ) ) {
				foreach ( impreza_js_libraries_gsap_plugin_definitions() as $suffix => $plugin ) {
					if ( ! impreza_js_libraries_gsap_plugin_is_enabled( $suffix ) ) {
						continue;
					}
					$path = $dir . $plugin['file'];
					if ( file_exists( $path ) ) {
						wp_enqueue_script( "gsap-js-{$suffix}", $dir_uri . $plugin['file'], array( 'gsap-js' ), filemtime( $path ), true );
						impreza_js_libraries_guard_umd_global( "gsap-js-{$suffix}" );
					}
				}
			}
		}
	}

	if ( impreza_js_libraries_is_enabled( 'lenis' ) ) {
		$lenis = $dir . 'lenis.min.js';
		if ( file_exists( $lenis ) ) {
			wp_enqueue_script( 'lenis-js', $dir_uri . 'lenis.min.js', array(), filemtime( $lenis ), true );
			impreza_js_libraries_guard_umd_global( 'lenis-js' );
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

	// Tabella leggibile per la console, dalla stessa logica del riquadro nel backend.
	$status = array();
	foreach ( impreza_js_libraries_effective_status() as $label => $on ) {
		$status[ $label ] = $on ? 'ATTIVO' : 'DISATTIVATO';
	}

	echo '<script id="impreza-js-libraries-flags">'
		. 'window.ImprezaJSLibs = ' . wp_json_encode( $flags ) . ';'
		. 'console.log("%c Librerie JS Impreza ","background:#2271b1;color:#fff;font-weight:bold;padding:2px 4px;border-radius:3px");'
		. 'console.table(' . wp_json_encode( $status ) . ');'
		. "</script>\n";
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
	$gsap_plugins    = impreza_js_libraries_gsap_plugin_definitions();
	$enabled_plugins = impreza_js_libraries_gsap_enabled_plugins();
	?>
	<div class="wrap">
		<h1>Librerie JS Impreza</h1>

		<p class="description">
			Attiva o disattiva il caricamento delle librerie JavaScript usate dal child theme.
			Le modifiche hanno effetto dal prossimo caricamento della pagina front-end.
		</p>

		<h2 style="margin-top: 1em;">Stato attuale</h2>
		<div style="display: flex; flex-wrap: wrap; gap: 8px; margin: 8px 0 4px;">
			<?php foreach ( impreza_js_libraries_effective_status() as $label => $on ) : ?>
				<span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; background: <?php echo $on ? '#d6f0d6' : '#f0f0f1'; ?>; color: <?php echo $on ? '#0a6b1c' : '#646970'; ?>; border: 1px solid <?php echo $on ? '#9bd6a0' : '#dcdcde'; ?>;">
					<span style="width: 8px; height: 8px; border-radius: 50%; background: <?php echo $on ? '#0a6b1c' : '#a7aaad'; ?>;"></span>
					<?php echo esc_html( $label ); ?>: <?php echo $on ? 'ATTIVO' : 'DISATTIVATO'; ?>
				</span>
			<?php endforeach; ?>
		</div>
		<p class="description" style="margin-top: 4px;">
			Riflette la configurazione salvata. GSAP core risulta ATTIVO anche quando caricato solo come dipendenza di MouseFollower. La stessa tabella viene stampata anche nella console del front-end.
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
				// NB: non disabilitiamo le checkbox (i campi disabled non verrebbero
				// inviati nel POST e la selezione dei plugin andrebbe persa al salvataggio).
				// Le ingrigiamo soltanto come promemoria: i plugin richiedono GSAP core attivo.
				function sync() {
					plugins.forEach(function (cb) {
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
