<?php
/**
 * Plugin Name: Impreza - Admin Menu Width
 * Description: Allarga il menu laterale dell'amministrazione per ospitare meglio le voci più lunghe. La larghezza (da 180px a 260px) si sceglie dalla select nella scheda "MU Plugin Impreza".
 * Version: 1.2.0
 * Author: Ubiquo Agency
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const IMPREZA_ADMIN_MENU_WIDTH_OPTION  = 'impreza_admin_menu_width';
const IMPREZA_ADMIN_MENU_WIDTH_MIN     = 180;
const IMPREZA_ADMIN_MENU_WIDTH_MAX     = 260;
const IMPREZA_ADMIN_MENU_WIDTH_STEP    = 10;
const IMPREZA_ADMIN_MENU_WIDTH_DEFAULT = 200;
const IMPREZA_ADMIN_MENU_WIDTH_SLUG    = 'Impreza__admin-menu-width.php';

/**
 * Restituisce le larghezze selezionabili (da MIN a MAX a passi di STEP).
 *
 * @return int[]
 */
function impreza_admin_menu_width_choices() {
	$choices = array();

	for ( $width = IMPREZA_ADMIN_MENU_WIDTH_MIN; $width <= IMPREZA_ADMIN_MENU_WIDTH_MAX; $width += IMPREZA_ADMIN_MENU_WIDTH_STEP ) {
		$choices[] = $width;
	}

	return $choices;
}

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
		// Larghezza della barra e spostamento del contenuto.
		. "body:not(.folded) #adminmenu,"
		. "body:not(.folded) #adminmenuback,"
		. "body:not(.folded) #adminmenuwrap{width:{$width}px;}"
		. "body:not(.folded) #wpcontent,"
		. "body:not(.folded) #wpfooter{margin-left:{$width}px;}"
		// Flyout dei sotto-menu in hover/focus: allinea al bordo destro della barra allargata.
		// Targetizza gli stessi stati di WordPress (:hover/.opensub/focus) per vincere in specificità.
		. "body:not(.folded) #adminmenu li.menu-top:hover .wp-submenu,"
		. "body:not(.folded) #adminmenu li.opensub .wp-submenu,"
		. "body:not(.folded) #adminmenu li.menu-top > a.menu-top:focus + .wp-submenu{left:{$width}px;}"
		// Le voci correnti tengono il sotto-menu inline (non come flyout), anche in hover.
		. "body:not(.folded) #adminmenu li.wp-has-current-submenu .wp-submenu,"
		. "body:not(.folded) #adminmenu li.wp-has-current-submenu:hover .wp-submenu,"
		. "body:not(.folded) #adminmenu li.wp-has-current-submenu.opensub .wp-submenu{left:auto;}"
		// RTL.
		. ".rtl body:not(.folded) #wpcontent,"
		. ".rtl body:not(.folded) #wpfooter{margin-left:0;margin-right:{$width}px;}"
		. ".rtl body:not(.folded) #adminmenu li.menu-top:hover .wp-submenu,"
		. ".rtl body:not(.folded) #adminmenu li.opensub .wp-submenu,"
		. ".rtl body:not(.folded) #adminmenu li.menu-top > a.menu-top:focus + .wp-submenu{left:auto;right:{$width}px;}"
		. ".rtl body:not(.folded) #adminmenu li.wp-has-current-submenu .wp-submenu,"
		. ".rtl body:not(.folded) #adminmenu li.wp-has-current-submenu:hover .wp-submenu,"
		. ".rtl body:not(.folded) #adminmenu li.wp-has-current-submenu.opensub .wp-submenu{right:auto;}"
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
 * Mostra la select della larghezza nella riga del plugin, dentro la scheda "MU Plugin Impreza".
 *
 * @param string $slug Filename del plugin in fase di render.
 */
function impreza_admin_menu_width_render_manager_control( $slug ) {
	if ( $slug !== IMPREZA_ADMIN_MENU_WIDTH_SLUG ) {
		return;
	}

	$current = impreza_admin_menu_width_get();
	?>
	<p style="margin: 10px 0 0;">
		<label for="impreza-admin-menu-width-select" style="display:block; margin-bottom:4px; font-weight:600;">
			Larghezza menu
		</label>
		<select
			id="impreza-admin-menu-width-select"
			name="<?php echo esc_attr( IMPREZA_ADMIN_MENU_WIDTH_OPTION ); ?>"
		>
			<?php foreach ( impreza_admin_menu_width_choices() as $width ) : ?>
				<option value="<?php echo (int) $width; ?>" <?php selected( $current, $width ); ?>>
					<?php echo (int) $width; ?>px<?php echo IMPREZA_ADMIN_MENU_WIDTH_DEFAULT === $width ? ' (default)' : ''; ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>
	<style id="impreza-admin-menu-width-live"></style>
	<script>
		(function () {
			var select = document.getElementById('impreza-admin-menu-width-select');
			var live   = document.getElementById('impreza-admin-menu-width-live');
			var isRtl  = document.documentElement.dir === 'rtl' || ( document.body && document.body.classList.contains('rtl') );

			if ( ! select || ! live ) {
				return;
			}

			function buildCss( width ) {
				var marginSide = isRtl ? 'margin-right' : 'margin-left';
				var subSide    = isRtl ? 'right' : 'left';
				var subReset   = isRtl ? 'right:auto;' : 'left:auto;';

				return '@media only screen and (min-width: 961px){'
					+ 'body:not(.folded) #adminmenu,'
					+ 'body:not(.folded) #adminmenuback,'
					+ 'body:not(.folded) #adminmenuwrap{width:' + width + 'px;}'
					+ 'body:not(.folded) #wpcontent,'
					+ 'body:not(.folded) #wpfooter{' + marginSide + ':' + width + 'px;}'
					+ 'body:not(.folded) #adminmenu li.menu-top:hover .wp-submenu,'
					+ 'body:not(.folded) #adminmenu li.opensub .wp-submenu,'
					+ 'body:not(.folded) #adminmenu li.menu-top > a.menu-top:focus + .wp-submenu{' + ( isRtl ? 'left:auto;' : '' ) + subSide + ':' + width + 'px;}'
					+ 'body:not(.folded) #adminmenu li.wp-has-current-submenu .wp-submenu,'
					+ 'body:not(.folded) #adminmenu li.wp-has-current-submenu:hover .wp-submenu,'
					+ 'body:not(.folded) #adminmenu li.wp-has-current-submenu.opensub .wp-submenu{' + subReset + '}'
					+ '}';
			}

			function apply() {
				var width = parseInt( select.value, 10 ) || <?php echo (int) IMPREZA_ADMIN_MENU_WIDTH_DEFAULT; ?>;
				live.textContent = buildCss( width );
			}

			select.addEventListener( 'change', apply );
			apply();
		}());
	</script>
	<?php
}
add_action( 'impreza_mu_plugin_manager_render_row_controls', 'impreza_admin_menu_width_render_manager_control', 10, 2 );

/**
 * Salva la larghezza selezionata quando si salva la scheda "MU Plugin Impreza".
 *
 * Il nonce e i permessi sono già stati verificati dal manager prima di questo hook.
 */
function impreza_admin_menu_width_save_from_manager() {
	if ( ! isset( $_POST[ IMPREZA_ADMIN_MENU_WIDTH_OPTION ] ) ) {
		return;
	}

	$width = impreza_admin_menu_width_clamp( wp_unslash( $_POST[ IMPREZA_ADMIN_MENU_WIDTH_OPTION ] ) );

	update_option( IMPREZA_ADMIN_MENU_WIDTH_OPTION, $width, false );
}
add_action( 'impreza_mu_plugin_manager_save', 'impreza_admin_menu_width_save_from_manager' );
