<?php
/**
 * Plugin Name: Impreza - Admin Menu Width
 * Description: Allarga il menu laterale dell'amministrazione per ospitare meglio le voci più lunghe. Larghezza regolabile da 180px a 260px in Impostazioni → Larghezza Menu Admin.
 * Version: 1.0.0
 * Author: Ubiquo Agency
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const IMPREZA_ADMIN_MENU_WIDTH_OPTION  = 'impreza_admin_menu_width';
const IMPREZA_ADMIN_MENU_WIDTH_MIN     = 180;
const IMPREZA_ADMIN_MENU_WIDTH_MAX     = 260;
const IMPREZA_ADMIN_MENU_WIDTH_STEP    = 5;
const IMPREZA_ADMIN_MENU_WIDTH_DEFAULT = 200;

/**
 * Restituisce la larghezza salvata, normalizzata tra MIN e MAX.
 *
 * @return int
 */
function impreza_admin_menu_width_get() {
	$value = (int) get_option( IMPREZA_ADMIN_MENU_WIDTH_OPTION, IMPREZA_ADMIN_MENU_WIDTH_DEFAULT );

	return impreza_admin_menu_width_clamp( $value );
}

/**
 * Normalizza un valore di larghezza nell'intervallo consentito.
 *
 * @param mixed $value Valore grezzo.
 * @return int
 */
function impreza_admin_menu_width_clamp( $value ) {
	$value = (int) $value;

	if ( $value < IMPREZA_ADMIN_MENU_WIDTH_MIN ) {
		return IMPREZA_ADMIN_MENU_WIDTH_MIN;
	}

	if ( $value > IMPREZA_ADMIN_MENU_WIDTH_MAX ) {
		return IMPREZA_ADMIN_MENU_WIDTH_MAX;
	}

	return $value;
}

/**
 * Genera il CSS che applica la larghezza al menu admin.
 *
 * Si applica solo da desktop (>= 961px) e solo a menu espanso (body non .folded),
 * per non interferire con la modalità compressa né con la vista mobile.
 *
 * @param int $width Larghezza in pixel.
 * @return string
 */
function impreza_admin_menu_width_css( $width ) {
	$width = impreza_admin_menu_width_clamp( $width );

	return "@media only screen and (min-width: 961px) {"
		. "body:not(.folded) #adminmenu,"
		. "body:not(.folded) #adminmenuback,"
		. "body:not(.folded) #adminmenuwrap{width:{$width}px;}"
		. "body:not(.folded) #wpcontent,"
		. "body:not(.folded) #wpfooter{margin-left:{$width}px;}"
		. "body:not(.folded) #adminmenu .wp-not-current-submenu .wp-submenu{left:{$width}px;}"
		. ".rtl body:not(.folded) #wpcontent,"
		. ".rtl body:not(.folded) #wpfooter{margin-left:0;margin-right:{$width}px;}"
		. ".rtl body:not(.folded) #adminmenu .wp-not-current-submenu .wp-submenu{left:auto;right:{$width}px;}"
		. "}";
}

/**
 * Stampa il CSS nell'head dell'area amministrativa.
 */
function impreza_admin_menu_width_print_styles() {
	$css = impreza_admin_menu_width_css( impreza_admin_menu_width_get() );

	printf(
		'<style id="impreza-admin-menu-width">%s</style>',
		wp_strip_all_tags( $css )
	);
}
add_action( 'admin_head', 'impreza_admin_menu_width_print_styles' );

/**
 * Gestisce il salvataggio del form prima del render, poi reindirizza.
 */
function impreza_admin_menu_width_handle_save() {
	if ( empty( $_POST['impreza_admin_menu_width_save'] ) ) {
		return;
	}

	check_admin_referer( 'impreza_admin_menu_width_save' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to access this page.' ) );
	}

	$width = isset( $_POST['impreza_admin_menu_width'] )
		? impreza_admin_menu_width_clamp( wp_unslash( $_POST['impreza_admin_menu_width'] ) )
		: IMPREZA_ADMIN_MENU_WIDTH_DEFAULT;

	update_option( IMPREZA_ADMIN_MENU_WIDTH_OPTION, $width, false );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'                            => 'impreza-admin-menu-width',
				'impreza_admin_menu_width_saved'  => '1',
			),
			admin_url( 'options-general.php' )
		)
	);
	exit;
}
add_action( 'admin_init', 'impreza_admin_menu_width_handle_save' );

/**
 * Registra la pagina impostazioni.
 */
function impreza_admin_menu_width_admin_menu() {
	add_options_page(
		'Larghezza Menu Admin',
		'Larghezza Menu Admin',
		'manage_options',
		'impreza-admin-menu-width',
		'impreza_admin_menu_width_render_page'
	);
}
add_action( 'admin_menu', 'impreza_admin_menu_width_admin_menu' );

/**
 * Render della pagina impostazioni con slider e anteprima live.
 */
function impreza_admin_menu_width_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to access this page.' ) );
	}

	$width = impreza_admin_menu_width_get();
	?>
	<div class="wrap">
		<h1>Larghezza Menu Admin</h1>

		<p class="description">
			Imposta la larghezza del menu laterale dell'amministrazione per dare più spazio alle voci più lunghe.
			Valori consentiti: da <?php echo (int) IMPREZA_ADMIN_MENU_WIDTH_MIN; ?>px a <?php echo (int) IMPREZA_ADMIN_MENU_WIDTH_MAX; ?>px.
			L'anteprima è immediata; ricordati di salvare per rendere la modifica permanente.
		</p>

		<?php if ( ! empty( $_GET['impreza_admin_menu_width_saved'] ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>Larghezza del menu salvata.</p>
			</div>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'impreza_admin_menu_width_save' ); ?>
			<input type="hidden" name="impreza_admin_menu_width_save" value="1">

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="impreza-admin-menu-width-range">Larghezza menu</label>
					</th>
					<td>
						<input
							type="range"
							id="impreza-admin-menu-width-range"
							name="impreza_admin_menu_width"
							min="<?php echo (int) IMPREZA_ADMIN_MENU_WIDTH_MIN; ?>"
							max="<?php echo (int) IMPREZA_ADMIN_MENU_WIDTH_MAX; ?>"
							step="<?php echo (int) IMPREZA_ADMIN_MENU_WIDTH_STEP; ?>"
							value="<?php echo (int) $width; ?>"
							style="width: 320px; max-width: 100%; vertical-align: middle;"
						>
						<output id="impreza-admin-menu-width-output" style="display:inline-block; min-width: 56px; margin-left: 10px; font-weight: 600;">
							<?php echo (int) $width; ?>px
						</output>
					</td>
				</tr>
			</table>

			<?php submit_button( 'Salva larghezza' ); ?>
		</form>

		<style id="impreza-admin-menu-width-live"></style>

		<script>
			(function () {
				var range  = document.getElementById('impreza-admin-menu-width-range');
				var output = document.getElementById('impreza-admin-menu-width-output');
				var live   = document.getElementById('impreza-admin-menu-width-live');
				var isRtl  = document.documentElement.dir === 'rtl' || document.body.classList.contains('rtl');

				if (!range || !output || !live) {
					return;
				}

				function buildCss(width) {
					var marginSide = isRtl ? 'margin-right' : 'margin-left';
					var subSide    = isRtl ? 'right' : 'left';

					return '@media only screen and (min-width: 961px){'
						+ 'body:not(.folded) #adminmenu,'
						+ 'body:not(.folded) #adminmenuback,'
						+ 'body:not(.folded) #adminmenuwrap{width:' + width + 'px;}'
						+ 'body:not(.folded) #wpcontent,'
						+ 'body:not(.folded) #wpfooter{' + marginSide + ':' + width + 'px;}'
						+ 'body:not(.folded) #adminmenu .wp-not-current-submenu .wp-submenu{' + subSide + ':' + width + 'px;}'
						+ '}';
				}

				function apply() {
					var width = parseInt(range.value, 10) || <?php echo (int) IMPREZA_ADMIN_MENU_WIDTH_DEFAULT; ?>;
					output.textContent = width + 'px';
					live.textContent = buildCss(width);
				}

				range.addEventListener('input', apply);
				range.addEventListener('change', apply);
				apply();
			}());
		</script>
	</div>
	<?php
}
