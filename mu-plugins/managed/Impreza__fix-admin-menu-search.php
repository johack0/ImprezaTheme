<?php
/**
 * Plugin Name: Impreza - Admin Menu Search
 * Description: Minimal MU plugin that adds a search field to the WordPress admin menu.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_footer', static function () {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || 'site-editor' === $screen->id ) {
		return;
	}
	?>
	<style>
		#mu-admin-menu-search-item {
			padding: 10px;
		}

		#mu-admin-menu-search {
			width: 100%;
			box-sizing: border-box;
			padding: 7px 10px;
			border: 1px solid rgba(255, 255, 255, 0.14);
			border-radius: 4px;
			background: rgba(255, 255, 255, 0.08);
			color: #fff;
		}

		#mu-admin-menu-search::placeholder {
			color: rgba(255, 255, 255, 0.72);
		}
	</style>
	<script>
		(function () {
			const menu = document.getElementById('adminmenu');
			if (!menu || document.getElementById('mu-admin-menu-search')) {
				return;
			}

			const item = document.createElement('li');
			item.id = 'mu-admin-menu-search-item';

			const input = document.createElement('input');
			input.id = 'mu-admin-menu-search';
			input.type = 'search';
			input.placeholder = 'Search menus';
			input.setAttribute('aria-label', 'Search admin menus');

			item.appendChild(input);
			menu.insertBefore(item, menu.firstChild);

			const topItems = Array.from(menu.querySelectorAll(':scope > li.menu-top'));

			const normalize = (value) => value.replace(/\s+/g, ' ').trim().toLowerCase();
			const getText = (node) => normalize(node ? node.textContent || '' : '');

			input.addEventListener('input', function () {
				const query = normalize(input.value);

				topItems.forEach(function (topItem) {
					const topName = getText(topItem.querySelector('.wp-menu-name'));
					const submenuNames = Array.from(topItem.querySelectorAll('.wp-submenu li'))
						.map(getText)
						.filter(Boolean);

					const matchesTop = !query || topName.includes(query);
					const matchesSubmenu = submenuNames.some(function (name) {
						return name.includes(query);
					});

					topItem.style.display = matchesTop || matchesSubmenu ? '' : 'none';
				});
			});
		}());
	</script>
	<?php
} );
