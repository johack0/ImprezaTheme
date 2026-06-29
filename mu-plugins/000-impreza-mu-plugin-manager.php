<?php
/**
 * Plugin Name: Impreza - MU Plugin Manager
 * Description: Adds a Settings page to enable or disable managed MU plugins.
 * Version: 1.0.0
 */

defined( 'ABSPATH' ) || exit;

const IMPREZA_MU_PLUGIN_MANAGER_OPTION = 'impreza_mu_plugin_manager_enabled';
const IMPREZA_MU_PLUGIN_MANAGER_DIR    = __DIR__ . '/managed';
const IMPREZA_MU_PLUGIN_MANAGER_DISABLED_OPTION = 'impreza_mu_plugin_manager_disabled';

/**
 * Returns newly introduced plugins that should be enabled by default.
 *
 * @return string[]
 */
function impreza_mu_plugin_manager_get_default_enabled_slugs() {
	return array(
		'Impreza__header-add-element-live-search.php',
		'Impreza__display-logic-device-conditions.php',
		'Impreza__admin-menu-width.php',
	);
}

/**
 * Returns all managed MU plugins indexed by filename.
 *
 * @return array<string,array<string,string>>
 */
function impreza_mu_plugin_manager_get_plugins() {
	if ( ! is_dir( IMPREZA_MU_PLUGIN_MANAGER_DIR ) ) {
		return array();
	}

	$files = glob( IMPREZA_MU_PLUGIN_MANAGER_DIR . '/*.php' );
	if ( ! is_array( $files ) ) {
		return array();
	}

	natcasesort( $files );

	$plugins = array();
	foreach ( $files as $file ) {
		$slug    = basename( $file );
		$headers = get_file_data(
			$file,
			array(
				'name'        => 'Plugin Name',
				'description' => 'Description',
				'version'     => 'Version',
				'author'      => 'Author',
			),
			'plugin'
		);

		$plugins[ $slug ] = array(
			'file'        => $file,
			'name'        => $headers['name'] ? $headers['name'] : $slug,
			'description' => $headers['description'],
			'version'     => $headers['version'],
			'author'      => $headers['author'],
		);
	}

	return $plugins;
}

/**
 * Returns the enabled plugin filenames.
 *
 * On first install, every managed plugin stays enabled to preserve current site behavior.
 *
 * @return string[]
 */
function impreza_mu_plugin_manager_get_enabled_slugs() {
	$plugins = impreza_mu_plugin_manager_get_plugins();
	$enabled = get_option( IMPREZA_MU_PLUGIN_MANAGER_OPTION, false );

	if ( false === $enabled ) {
		return array_keys( $plugins );
	}

	if ( ! is_array( $enabled ) ) {
		return array();
	}

	$enabled = array_values(
		array_intersect(
			array_map( 'sanitize_file_name', $enabled ),
			array_keys( $plugins )
		)
	);
	$disabled = get_option( IMPREZA_MU_PLUGIN_MANAGER_DISABLED_OPTION, array() );
	$disabled = is_array( $disabled ) ? array_map( 'sanitize_file_name', $disabled ) : array();

	return array_values(
		array_diff(
			array_unique(
				array_merge(
					$enabled,
					array_intersect( impreza_mu_plugin_manager_get_default_enabled_slugs(), array_keys( $plugins ) )
				)
			),
			$disabled
		)
	);
}

/**
 * Includes enabled managed MU plugins.
 */
function impreza_mu_plugin_manager_load_enabled_plugins() {
	$plugins = impreza_mu_plugin_manager_get_plugins();
	$enabled = array_flip( impreza_mu_plugin_manager_get_enabled_slugs() );

	foreach ( $plugins as $slug => $plugin ) {
		if ( isset( $enabled[ $slug ] ) && is_readable( $plugin['file'] ) ) {
			require_once $plugin['file'];
		}
	}
}
impreza_mu_plugin_manager_load_enabled_plugins();

/**
 * Handles settings form submissions before rendering the page, then redirects.
 *
 * The redirect is important because MU plugins are loaded before this POST can be
 * processed. A fresh request is needed to reflect disabled plugins immediately.
 */
function impreza_mu_plugin_manager_handle_save_request() {
	if ( empty( $_POST['impreza_mu_plugin_manager_save'] ) ) {
		return;
	}

	check_admin_referer( 'impreza_mu_plugin_manager_save' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to access this page.' ) );
	}

	$plugins  = impreza_mu_plugin_manager_get_plugins();
	$selected = isset( $_POST['impreza_mu_plugin_manager_enabled'] )
		? (array) $_POST['impreza_mu_plugin_manager_enabled']
		: array();

	$enabled = array_values(
		array_intersect(
			array_map( 'sanitize_file_name', wp_unslash( $selected ) ),
			array_keys( $plugins )
		)
	);

	update_option( IMPREZA_MU_PLUGIN_MANAGER_OPTION, $enabled, false );
	update_option( IMPREZA_MU_PLUGIN_MANAGER_DISABLED_OPTION, array_values( array_diff( array_keys( $plugins ), $enabled ) ), false );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'                            => 'impreza-mu-plugin-manager',
				'impreza_mu_plugin_manager_saved' => '1',
			),
			admin_url( 'options-general.php' )
		)
	);
	exit;
}
add_action( 'admin_init', 'impreza_mu_plugin_manager_handle_save_request' );

/**
 * Registers the settings page.
 */
function impreza_mu_plugin_manager_admin_menu() {
	add_options_page(
		'MU Plugin Impreza',
		'MU Plugin Impreza',
		'manage_options',
		'impreza-mu-plugin-manager',
		'impreza_mu_plugin_manager_render_page'
	);
}
add_action( 'admin_menu', 'impreza_mu_plugin_manager_admin_menu' );

/**
 * Renders the settings page.
 */
function impreza_mu_plugin_manager_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to access this page.' ) );
	}

	$plugins = impreza_mu_plugin_manager_get_plugins();

	$enabled       = impreza_mu_plugin_manager_get_enabled_slugs();
	$enabled_count = count( $enabled );
	$total_count   = count( $plugins );
	?>
	<div class="wrap">
		<h1>MU Plugin Impreza</h1>

		<p>
			<?php
			printf(
				esc_html__( 'Plugin gestiti: %1$d. Plugin abilitati: %2$d.', 'default' ),
				(int) $total_count,
				(int) $enabled_count
			);
			?>
		</p>
		<p class="description">
			Le modifiche hanno effetto dal prossimo caricamento di WordPress. Il manager resta sempre attivo.
		</p>

		<?php if ( ! empty( $_GET['impreza_mu_plugin_manager_saved'] ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>Impostazioni MU plugin salvate.</p>
			</div>
		<?php endif; ?>

		<?php if ( empty( $plugins ) ) : ?>
			<div class="notice notice-warning">
				<p>Nessun MU plugin gestibile trovato nella cartella <code>mu-plugins/managed</code>.</p>
			</div>
		<?php else : ?>
			<form method="post">
				<?php wp_nonce_field( 'impreza_mu_plugin_manager_save' ); ?>
				<input type="hidden" name="impreza_mu_plugin_manager_save" value="1">

				<p style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
					<label for="impreza-mu-plugin-manager-search">
						Cerca
						<input
							type="search"
							id="impreza-mu-plugin-manager-search"
							autocomplete="off"
							placeholder="Filtra MU plugin"
							style="min-width: 260px; margin-left: 6px;"
						>
					</label>
					<span id="impreza-mu-plugin-manager-search-status" class="description" aria-live="polite"></span>
					<label>
						<input type="checkbox" id="impreza-mu-plugin-manager-select-all">
						Seleziona tutti
					</label>
					<?php submit_button( 'Salva impostazioni', 'primary', 'submit', false ); ?>
				</p>

				<style>
					.impreza-mu-plugin-manager-grid {
						display: grid;
						grid-template-columns: repeat(5, minmax(0, 1fr));
						gap: 12px;
						margin-top: 14px;
					}

					.impreza-mu-plugin-manager-row {
						box-sizing: border-box;
						min-width: 0;
						padding: 12px;
						border: 1px solid #c3c4c7;
						background: #fff;
					}

					.impreza-mu-plugin-manager-row label {
						display: flex;
						gap: 8px;
						align-items: flex-start;
					}

					.impreza-mu-plugin-manager-row strong {
						display: block;
						margin-bottom: 6px;
					}

					.impreza-mu-plugin-manager-version {
						display: block;
						margin-top: 6px;
					}

					@media screen and (max-width: 1500px) {
						.impreza-mu-plugin-manager-grid {
							grid-template-columns: repeat(4, minmax(0, 1fr));
						}
					}

					@media screen and (max-width: 1200px) {
						.impreza-mu-plugin-manager-grid {
							grid-template-columns: repeat(3, minmax(0, 1fr));
						}
					}

					@media screen and (max-width: 960px) {
						.impreza-mu-plugin-manager-grid {
							grid-template-columns: repeat(2, minmax(0, 1fr));
						}
					}

					@media screen and (max-width: 782px) {
						.impreza-mu-plugin-manager-grid {
							grid-template-columns: 1fr;
						}
					}
				</style>

				<div class="impreza-mu-plugin-manager-grid">
					<?php foreach ( $plugins as $slug => $plugin ) : ?>
						<div
							class="impreza-mu-plugin-manager-row"
							data-plugin-search="<?php echo esc_attr( $plugin['name'] . ' ' . $plugin['description'] . ' managed/' . $slug ); ?>"
						>
							<label>
								<input
									type="checkbox"
									name="impreza_mu_plugin_manager_enabled[]"
									class="impreza-mu-plugin-manager-plugin-toggle"
									value="<?php echo esc_attr( $slug ); ?>"
									<?php checked( in_array( $slug, $enabled, true ) ); ?>
								>
								<span>
									<strong><?php echo esc_html( $plugin['name'] ); ?></strong>
									<?php if ( $plugin['description'] ) : ?>
										<span class="description"><?php echo esc_html( $plugin['description'] ); ?></span>
									<?php endif; ?>
									<span class="description impreza-mu-plugin-manager-version">
										Versione: <?php echo $plugin['version'] ? esc_html( $plugin['version'] ) : '&ndash;'; ?>
									</span>
								</span>
							</label>
						</div>
					<?php endforeach; ?>
				</div>

				<script>
					(function () {
						var selectAll = document.getElementById('impreza-mu-plugin-manager-select-all');
						var searchInput = document.getElementById('impreza-mu-plugin-manager-search');
						var searchStatus = document.getElementById('impreza-mu-plugin-manager-search-status');
						var checkboxes = Array.prototype.slice.call(
							document.querySelectorAll('.impreza-mu-plugin-manager-plugin-toggle')
						);
						var rows = Array.prototype.slice.call(
							document.querySelectorAll('.impreza-mu-plugin-manager-row')
						);

						if (!selectAll || !checkboxes.length) {
							return;
						}

						function normalize(value) {
							return String(value || '')
								.normalize('NFD')
								.replace(/[\u0300-\u036f]/g, '')
								.replace(/\s+/g, ' ')
								.trim()
								.toLowerCase();
						}

						function syncSelectAll() {
							var checkedCount = checkboxes.filter(function (checkbox) {
								return checkbox.checked;
							}).length;

							selectAll.checked = checkedCount === checkboxes.length;
							selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
						}

						function applySearch() {
							if (!searchInput || !rows.length) {
								return;
							}

							var terms = normalize(searchInput.value).split(' ').filter(Boolean);
							var visibleCount = 0;

							rows.forEach(function (row) {
								var text = normalize((row.getAttribute('data-plugin-search') || '') + ' ' + row.textContent);
								var isMatch = !terms.length || terms.every(function (term) {
									return text.indexOf(term) !== -1;
								});

								row.style.display = isMatch ? '' : 'none';

								if (isMatch) {
									visibleCount++;
								}
							});

							if (searchStatus) {
								searchStatus.textContent = terms.length ? visibleCount + '/' + rows.length : '';
							}
						}

						selectAll.addEventListener('change', function () {
							checkboxes.forEach(function (checkbox) {
								checkbox.checked = selectAll.checked;
							});
						});

						checkboxes.forEach(function (checkbox) {
							checkbox.addEventListener('change', syncSelectAll);
						});

						if (searchInput) {
							searchInput.addEventListener('input', applySearch);
							searchInput.addEventListener('search', applySearch);
						}

						syncSelectAll();
						applySearch();
					}());
				</script>

			</form>
		<?php endif; ?>
	</div>
	<?php
}
