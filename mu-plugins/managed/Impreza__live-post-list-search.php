<?php
/**
 * Plugin Name: Impreza - Live Post Search
 * Description: Adds an instant client-side search field next to the default search on WordPress post type list screens.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_footer-edit.php', static function () {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || 'edit' !== $screen->base || empty( $screen->post_type ) ) {
		return;
	}

	$post_type_object = get_post_type_object( $screen->post_type );

	if ( ! $post_type_object || empty( $post_type_object->cap->edit_posts ) || ! current_user_can( $post_type_object->cap->edit_posts ) ) {
		return;
	}
	?>
	<style>
		#mu-live-post-filter {
			width: 180px;
			max-width: 32vw;
			margin-left: 6px;
			vertical-align: middle;
		}

		#mu-live-post-filter-status {
			display: inline-block;
			min-width: 56px;
			margin-left: 6px;
			color: #646970;
			vertical-align: middle;
		}

		#mu-live-post-filter-empty td {
			padding: 14px 10px;
			color: #646970;
		}

		@media screen and (max-width: 782px) {
			#mu-live-post-filter {
				display: block;
				width: 100%;
				max-width: none;
				margin: 6px 0 0;
			}

			#mu-live-post-filter-status {
				margin: 6px 0 0;
			}
		}
	</style>
	<script>
		(function () {
			const form = document.getElementById('posts-filter');
			const searchBox = form ? form.querySelector('.search-box') : null;
			const table = form ? form.querySelector('.wp-list-table') : null;
			const tableBody = document.getElementById('the-list');

			if (!form || !searchBox || !table || !tableBody || document.getElementById('mu-live-post-filter')) {
				return;
			}

			const liveInput = document.createElement('input');
			liveInput.id = 'mu-live-post-filter';
			liveInput.type = 'search';
			liveInput.autocomplete = 'off';
			liveInput.placeholder = 'Live search';
			liveInput.setAttribute('aria-label', 'Filtra questa lista in tempo reale');

			const status = document.createElement('span');
			status.id = 'mu-live-post-filter-status';
			status.setAttribute('aria-live', 'polite');

			const submit = searchBox.querySelector('#search-submit, input[type="submit"], button[type="submit"]');
			searchBox.insertBefore(liveInput, submit || null);
			searchBox.appendChild(status);

			let rowIndex = [];
			let rowIndexDirty = true;
			let lastColumnSignature = '';
			let frameId = 0;

			const updateStatus = function (message) {
				status.textContent = message || '';
			};

			const normalize = function (value) {
				return String(value || '')
					.normalize('NFD')
					.replace(/[\u0300-\u036f]/g, '')
					.replace(/\s+/g, ' ')
					.trim()
					.toLowerCase();
			};

			const isHidden = function (element) {
				return element.classList.contains('hidden') || window.getComputedStyle(element).display === 'none';
			};

			const isSearchableCell = function (cell) {
				return !cell.classList.contains('check-column') && !isHidden(cell);
			};

			const getVisibleColumnSignature = function () {
				return Array.from(table.querySelectorAll('thead tr:first-child > *'))
					.filter(isSearchableCell)
					.map(function (cell) {
						return cell.id || cell.className || cell.textContent;
					})
					.join('|');
			};

			const getSearchableRows = function () {
				return Array.from(tableBody.querySelectorAll('tr'))
					.filter(function (row) {
						return !row.classList.contains('no-items') && !row.classList.contains('inline-edit-row') && row.id !== 'mu-live-post-filter-empty';
					});
			};

			const getColumnSpan = function () {
				const visibleColumns = Array.from(table.querySelectorAll('thead tr:first-child > *'))
					.filter(function (cell) {
						return !isHidden(cell);
					})
					.length;

				return Math.max(visibleColumns, 1);
			};

			const getEmptyRow = function () {
				let emptyRow = document.getElementById('mu-live-post-filter-empty');

				if (!emptyRow) {
					emptyRow = document.createElement('tr');
					emptyRow.id = 'mu-live-post-filter-empty';
					emptyRow.style.display = 'none';

					const cell = document.createElement('td');
					cell.className = 'colspanchange';
					cell.textContent = 'Nessun risultato per questa live search.';
					emptyRow.appendChild(cell);
					tableBody.appendChild(emptyRow);
				}

				emptyRow.firstElementChild.colSpan = getColumnSpan();
				return emptyRow;
			};

			const getCellText = function (cell) {
				const copy = cell.cloneNode(true);

				copy.querySelectorAll('.row-actions, .screen-reader-text, script, style, input, button, select, textarea').forEach(function (node) {
					node.remove();
				});

				const textParts = [copy.textContent || ''];

				copy.querySelectorAll('[title], [aria-label], img[alt]').forEach(function (node) {
					textParts.push(node.getAttribute('title') || '');
					textParts.push(node.getAttribute('aria-label') || '');
					textParts.push(node.getAttribute('alt') || '');
				});

				return normalize(textParts.join(' '));
			};

			const buildRowIndex = function () {
				const currentRows = getSearchableRows();
				const currentColumnSignature = getVisibleColumnSignature();

				if (!rowIndexDirty && currentColumnSignature === lastColumnSignature && currentRows.length === rowIndex.length) {
					return;
				}

				rowIndex = currentRows.map(function (row) {
					const text = Array.from(row.children)
						.filter(isSearchableCell)
						.map(getCellText)
						.join(' ');

					return {
						row: row,
						text: text
					};
				});

				lastColumnSignature = currentColumnSignature;
				rowIndexDirty = false;
			};

			const applyFilter = function () {
				frameId = 0;
				buildRowIndex();

				const query = normalize(liveInput.value);
				const terms = query.split(' ').filter(Boolean);
				const emptyRow = getEmptyRow();
				let visibleCount = 0;

				rowIndex.forEach(function (item) {
					const isMatch = !terms.length || terms.every(function (term) {
						return item.text.includes(term);
					});

					item.row.style.display = isMatch ? '' : 'none';

					if (isMatch) {
						visibleCount++;
					}
				});

				emptyRow.style.display = terms.length && visibleCount === 0 ? '' : 'none';
				updateStatus(terms.length ? visibleCount + '/' + rowIndex.length : '');

				document.dispatchEvent(
					new CustomEvent('muLivePostFilterUpdated', {
						detail: {
							query: liveInput.value,
							visible: visibleCount,
							total: rowIndex.length
						}
					})
				);
			};

			const queueFilter = function () {
				if (frameId) {
					window.cancelAnimationFrame(frameId);
				}

				frameId = window.requestAnimationFrame(applyFilter);
			};

			new MutationObserver(function () {
				rowIndexDirty = true;
			}).observe(tableBody, {
				childList: true
			});

			liveInput.addEventListener('input', queueFilter);
			liveInput.addEventListener('search', queueFilter);
			liveInput.addEventListener('keydown', function (event) {
				if (event.key === 'Enter') {
					event.preventDefault();
					if (frameId) {
						window.cancelAnimationFrame(frameId);
						frameId = 0;
					}
					applyFilter();
				}
			});
		}());
	</script>
	<?php
} );
