<?php
/**
 * Plugin Name: Impreza - Header Add Element Search
 * Description: Aggiunge una live search al popup "Add element" del builder Header di Impreza.
 * Version: 1.0.0
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_enqueue_scripts', static function() {
	if ( ! wp_script_is( 'us-header-builder', 'enqueued' ) ) {
		return;
	}

	$css = <<<'CSS'
.us-bld-window.for_adding .impreza-hb-add-element-search {
	display: flex;
	gap: 8px;
	align-items: center;
	margin: 0 0 12px;
}
.us-bld-window.for_adding .impreza-hb-add-element-search input {
	flex: 1 1 auto;
	min-width: 0;
	height: 36px;
	padding: 0 12px;
	border: 0;
	border-radius: 4px;
	background: #fff;
	color: #1d2327;
	box-shadow: inset 0 0 0 1px rgba(0,0,0,.22);
}
.us-bld-window.for_adding .impreza-hb-add-element-search button {
	flex: 0 0 auto;
	height: 36px;
	padding: 0 12px;
	border: 0;
	border-radius: 4px;
	cursor: pointer;
	background: var(--usof-color-gray-2, #f0f0f1);
	color: inherit;
}
.us-bld-window.for_adding .impreza-hb-add-element-search-status {
	flex: 0 0 auto;
	min-width: 42px;
	opacity: .7;
}
.us-bld-window.for_adding .impreza-hb-add-element-search-empty {
	display: none;
	margin: 10px 0 0;
	opacity: .7;
}
CSS;

	wp_register_style( 'impreza-header-add-element-search', false, array(), '1.0.0' );
	wp_enqueue_style( 'impreza-header-add-element-search' );
	wp_add_inline_style( 'impreza-header-add-element-search', $css );

	$js = <<<'JS'
(function () {
	"use strict";

	function normalize(value) {
		return String(value || "")
			.normalize("NFD")
			.replace(/[\u0300-\u036f]/g, "")
			.replace(/\s+/g, " ")
			.trim()
			.toLowerCase();
	}

	function enhanceAddElementWindow(win) {
		if (!win || win.dataset.imprezaHbSearchReady === "1") {
			return;
		}

		var list = win.querySelector(".us-bld-window-list");
		var body = win.querySelector(".us-bld-window-body");
		if (!list || !body) {
			return;
		}

		win.dataset.imprezaHbSearchReady = "1";

		var searchWrap = document.createElement("div");
		searchWrap.className = "impreza-hb-add-element-search";

		var input = document.createElement("input");
		input.type = "search";
		input.autocomplete = "off";
		input.placeholder = "Cerca elemento";
		input.setAttribute("aria-label", "Cerca elemento da aggiungere");

		var status = document.createElement("span");
		status.className = "impreza-hb-add-element-search-status";
		status.setAttribute("aria-live", "polite");

		var reset = document.createElement("button");
		reset.type = "button";
		reset.textContent = "Reset";
		reset.style.display = "none";

		var empty = document.createElement("div");
		empty.className = "impreza-hb-add-element-search-empty";
		empty.textContent = "Nessun elemento trovato.";

		searchWrap.appendChild(input);
		searchWrap.appendChild(status);
		searchWrap.appendChild(reset);
		body.insertBefore(searchWrap, list);
		body.insertBefore(empty, list.nextSibling);

		var items = Array.prototype.slice.call(list.querySelectorAll(".us-bld-window-item"));

		function getItemText(item) {
			var title = item.querySelector(".us-bld-window-item-title");
			return normalize([
				item.getAttribute("data-name") || "",
				title ? title.textContent : "",
				item.textContent || ""
			].join(" "));
		}

		var indexedItems = items.map(function (item) {
			return {
				item: item,
				text: getItemText(item)
			};
		});

		function applySearch() {
			var terms = normalize(input.value).split(" ").filter(Boolean);
			var visibleCount = 0;

			indexedItems.forEach(function (entry) {
				var isMatch = !terms.length || terms.every(function (term) {
					return entry.text.indexOf(term) !== -1;
				});

				entry.item.style.display = isMatch ? "" : "none";

				if (isMatch) {
					visibleCount++;
				}
			});

			status.textContent = terms.length ? visibleCount + "/" + indexedItems.length : "";
			reset.style.display = terms.length ? "" : "none";
			empty.style.display = terms.length && visibleCount === 0 ? "block" : "none";
		}

		input.addEventListener("input", applySearch);
		input.addEventListener("search", applySearch);
		input.addEventListener("keydown", function (event) {
			if (event.key === "Enter") {
				event.preventDefault();
			}
		});
		reset.addEventListener("click", function () {
			input.value = "";
			applySearch();
			input.focus();
		});

		applySearch();
	}

	function enhanceAll() {
		document.querySelectorAll(".us-bld-window.for_adding").forEach(enhanceAddElementWindow);
	}

	enhanceAll();

	new MutationObserver(enhanceAll).observe(document.body, {
		childList: true,
		subtree: true
	});
}());
JS;

	wp_add_inline_script( 'us-header-builder', $js, 'after' );
}, 100 );
