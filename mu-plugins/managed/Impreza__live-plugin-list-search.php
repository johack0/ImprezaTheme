<?php
/**
 * Plugin Name: Impreza - Live Plugin Search
 * Description: Aggiunge una live search client-side alla pagina Plugin installati.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_footer-plugins.php', static function () {
	if ( ! current_user_can( 'activate_plugins' ) && ! current_user_can( 'manage_network_plugins' ) ) {
		return;
	}
	?>
	<style>
		#mu-live-plugin-filter {
			width: 180px;
			max-width: 32vw;
			margin-left: 6px;
			vertical-align: middle;
		}

		#mu-live-plugin-filter-status {
			display: inline-block;
			min-width: 56px;
			margin-left: 6px;
			color: #646970;
			vertical-align: middle;
		}

		#mu-live-plugin-filter-empty td {
			padding: 14px 10px;
			color: #646970;
		}

		@media screen and (max-width: 782px) {
			#mu-live-plugin-filter {
				display: block;
				width: 100%;
				max-width: none;
				margin: 6px 0 0;
			}

			#mu-live-plugin-filter-status {
				margin: 6px 0 0;
			}
		}
	</style>
	<script>
		(function () {
			const bulkForm = document.getElementById('bulk-action-form');
			let searchBox = document.querySelector('.search-plugins .search-box, .search-form .search-box, .search-box');
			const table = (bulkForm ? bulkForm.querySelector('.wp-list-table.plugins') : null) || document.querySelector('.wp-list-table.plugins');
			const tableBody = table ? (table.querySelector('tbody#the-list, tbody') || document.getElementById('the-list')) : document.getElementById('the-list');

			if (!table || !tableBody || document.getElementById('mu-live-plugin-filter')) {
				return;
			}

			if (!searchBox) {
				searchBox = document.createElement('p');
				searchBox.className = 'search-box';
				table.parentNode.insertBefore(searchBox, table);
			}

			const liveInput = document.createElement('input');
			liveInput.id = 'mu-live-plugin-filter';
			liveInput.type = 'search';
			liveInput.autocomplete = 'off';
			liveInput.placeholder = 'Live search';
			liveInput.setAttribute('aria-label', 'Filtra i plugin installati in tempo reale');

			const status = document.createElement('span');
			status.id = 'mu-live-plugin-filter-status';
			status.setAttribute('aria-live', 'polite');

			const submit = searchBox.querySelector('#search-submit, input[type="submit"], button[type="submit"]');
			searchBox.insertBefore(liveInput, submit || null);
			searchBox.appendChild(status);

			let rowIndex = [];
			let rowIndexDirty = true;
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

			const isPluginMainRow = function (row) {
				return row.matches('tr[data-plugin], tr[data-slug]') && !row.classList.contains('plugin-update-tr');
			};

			const getPluginRows = function () {
				return Array.from(tableBody.querySelectorAll('tr')).filter(isPluginMainRow);
			};

			const getRelatedRows = function (row) {
				const rows = [];
				let sibling = row.nextElementSibling;

				while (sibling && !isPluginMainRow(sibling)) {
					if (sibling.id !== 'mu-live-plugin-filter-empty' && !sibling.classList.contains('no-items')) {
						rows.push(sibling);
					}
					sibling = sibling.nextElementSibling;
				}

				return rows;
			};

			const getColumnSpan = function () {
				const visibleColumns = Array.from(table.querySelectorAll('thead tr:first-child > *')).filter(function (cell) {
					return window.getComputedStyle(cell).display !== 'none';
				}).length;

				return Math.max(visibleColumns, 1);
			};

			const getEmptyRow = function () {
				let emptyRow = document.getElementById('mu-live-plugin-filter-empty');

				if (!emptyRow) {
					emptyRow = document.createElement('tr');
					emptyRow.id = 'mu-live-plugin-filter-empty';
					emptyRow.style.display = 'none';

					const cell = document.createElement('td');
					cell.className = 'colspanchange';
					cell.textContent = 'Nessun plugin trovato per questa live search.';
					emptyRow.appendChild(cell);
					tableBody.appendChild(emptyRow);
				}

				emptyRow.firstElementChild.colSpan = getColumnSpan();
				return emptyRow;
			};

			const getRowText = function (row, relatedRows) {
				const copy = row.cloneNode(true);
				const textParts = [
					row.getAttribute('data-plugin') || '',
					row.getAttribute('data-slug') || ''
				];

				copy.querySelectorAll('.row-actions, .screen-reader-text, script, style, input, button, select, textarea').forEach(function (node) {
					node.remove();
				});

				textParts.push(copy.textContent || '');

				copy.querySelectorAll('[title], [aria-label], img[alt]').forEach(function (node) {
					textParts.push(node.getAttribute('title') || '');
					textParts.push(node.getAttribute('aria-label') || '');
					textParts.push(node.getAttribute('alt') || '');
				});

				relatedRows.forEach(function (relatedRow) {
					textParts.push(relatedRow.textContent || '');
				});

				return normalize(textParts.join(' '));
			};

			const buildRowIndex = function () {
				const rows = getPluginRows();

				if (!rowIndexDirty && rows.length === rowIndex.length) {
					return;
				}

				rowIndex = rows.map(function (row) {
					const relatedRows = getRelatedRows(row);

					return {
						row: row,
						relatedRows: relatedRows,
						text: getRowText(row, relatedRows)
					};
				});

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
					item.relatedRows.forEach(function (relatedRow) {
						relatedRow.style.display = isMatch ? '' : 'none';
					});

					if (isMatch) {
						visibleCount++;
					}
				});

				emptyRow.style.display = terms.length && visibleCount === 0 ? '' : 'none';
				updateStatus(terms.length ? visibleCount + '/' + rowIndex.length : '');
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
