<?php
/**
 * Plugin Name: Impreza - Edit Reusable Block Link
 * Description: Mostra il link "modifica blocco" sotto ai selettori Reusable Block e Popup.
 * Version: 1.0.0
 *
 * MU Plugin: link "modifica blocco" per l'elemento Reusable Block di Impreza.
 *
 * Obiettivo: quando nei widget "Reusable Block" e "Popup" viene selezionato un blocco,
 * mostra sotto al campo una piccola voce "modifica blocco" che apre la modifica
 * dello stesso blocco in una nuova scheda.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'us_config_elements/page_block', function( $config ) {
	if ( isset( $config['params']['id'] ) AND is_array( $config['params']['id'] ) ) {
		$config['params']['id']['classes'] = trim(
			( $config['params']['id']['classes'] ?? '' ) . ' impreza-edit-reusable-block-link'
		);
	}

	return $config;
} );

add_filter( 'us_config_elements/popup', function( $config ) {
	if ( isset( $config['params']['use_page_block'] ) AND is_array( $config['params']['use_page_block'] ) ) {
		$config['params']['use_page_block']['classes'] = trim(
			( $config['params']['use_page_block']['classes'] ?? '' ) . ' impreza-edit-reusable-block-link'
		);
	}

	return $config;
} );

add_action( 'admin_enqueue_scripts', function() {
	global $pagenow;

	if ( ! in_array( $pagenow, array( 'post.php', 'post-new.php', 'admin.php' ), true ) ) {
		return;
	}

	$css = <<<'CSS'
.usof-form-row.impreza-edit-reusable-block-link .usof-form-row-hint,
.impreza-reusable-block-edit-link {
	display: block;
	font-size: .9em;
	line-height: 1.2;
	padding-top: .3em;
	color: var(--usof-color-gray-30, #72777c);
}

.usof-form-row.impreza-edit-reusable-block-link .usof-form-row-hint a,
.impreza-reusable-block-edit-link a {
	text-decoration: none;
}
CSS;

	wp_register_style( 'impreza-edit-reusable-block-link', false, array(), '1.0.0' );
	wp_enqueue_style( 'impreza-edit-reusable-block-link' );
	wp_add_inline_style( 'impreza-edit-reusable-block-link', $css );

	$admin_post_url = admin_url( 'post.php' );

	$js = sprintf(
		<<<'JS'
(function() {
	"use strict";

	var ADMIN_POST_URL = %s;
	var EDIT_TEXT = "modifica blocco";
	var TARGET_CLASS = "impreza-edit-reusable-block-link";
	var TARGET_FIELD_NAMES = {
		id: true,
		use_page_block: true
	};

	function normalize(text) {
		return String(text || "").replace(/\s+/g, " ").trim().toLowerCase();
	}

	function isPostId(value) {
		return /^[1-9]\d*$/.test(String(value || ""));
	}

	function getFieldName(row, select) {
		if (!row) return "";

		return row.getAttribute("data-name")
			|| row.getAttribute("data-vc-shortcode-param-name")
			|| (select && select.getAttribute("name"))
			|| "";
	}

	function getFieldTitle(row) {
		if (!row) return "";

		var titleNode = row.querySelector(
			".usof-form-row-title span, .wpb_element_label, .vc_element_label, label"
		);

		return normalize(titleNode ? titleNode.textContent : "");
	}

	function isReusableBlockRow(row, select) {
		if (!row || !select) return false;

		if (row.classList.contains(TARGET_CLASS)) {
			return true;
		}

		var fieldName = getFieldName(row, select);
		if (fieldName && !TARGET_FIELD_NAMES[fieldName]) {
			return false;
		}

		var title = getFieldTitle(row);
		return title === "reusable block" || title === "blocco riutilizzabile";
	}

	function getRows(root) {
		root = root || document;

		var rows = Array.prototype.slice.call(
			root.querySelectorAll(".usof-form-row." + TARGET_CLASS)
		);

		Array.prototype.forEach.call(
			root.querySelectorAll(".usof-form-row.type_select, .vc_shortcode-param"),
			function(row) {
				if (rows.indexOf(row) === -1 && isReusableBlockRow(row, row.querySelector("select"))) {
					rows.push(row);
				}
			}
		);

		return rows;
	}

	function getOrCreateHint(row) {
		var hint = row.querySelector(".usof-form-row-hint");
		if (hint) {
			hint.classList.add("usof-form-row-hint-text");
			return hint;
		}

		hint = document.createElement("div");
		hint.className = "impreza-reusable-block-edit-link usof-form-row-hint usof-form-row-hint-text";

		var field = row.querySelector(".usof-form-row-field") || row;
		var control = row.querySelector(".usof-form-row-control");

		if (control && control.parentNode === field) {
			control.insertAdjacentElement("afterend", hint);
		} else {
			field.appendChild(hint);
		}

		return hint;
	}

	function editHref(postId) {
		return ADMIN_POST_URL + "?post=" + encodeURIComponent(postId) + "&action=edit";
	}

	function updateRow(row) {
		var select = row ? row.querySelector("select") : null;
		if (!isReusableBlockRow(row, select)) {
			return;
		}

		var postId = select.value;
		var hint = getOrCreateHint(row);

		if (!isPostId(postId)) {
			hint.innerHTML = "";
			return;
		}

		var link = hint.querySelector("a");
		if (!link) {
			link = document.createElement("a");
			hint.innerHTML = "";
			hint.appendChild(link);
		}

		link.setAttribute("href", editHref(postId));

		link.textContent = EDIT_TEXT;
		link.setAttribute("target", "_blank");
		link.setAttribute("rel", "noopener noreferrer");
	}

	function updateAll(root) {
		if (
			root
			&& root.nodeType === 1
			&& root.matches(".usof-form-row, .vc_shortcode-param")
			&& isReusableBlockRow(root, root.querySelector("select"))
		) {
			updateRow(root);
			return;
		}

		getRows(root).forEach(updateRow);
	}

	function scheduleUpdate(root) {
		window.setTimeout(function() {
			updateAll(root || document);
		}, 0);
	}

	document.addEventListener("change", function(event) {
		if (!event.target || event.target.tagName !== "SELECT") {
			return;
		}

		var row = event.target.closest(".usof-form-row, .vc_shortcode-param");
		if (row && isReusableBlockRow(row, event.target)) {
			scheduleUpdate(row);
		}
	}, true);

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", function() {
			updateAll(document);
		});
	} else {
		updateAll(document);
	}

	var observer = new MutationObserver(function(mutations) {
		for (var i = 0; i < mutations.length; i++) {
			if (mutations[i].addedNodes && mutations[i].addedNodes.length) {
				scheduleUpdate(document);
				return;
			}
		}
	});

	observer.observe(document.documentElement, {
		childList: true,
		subtree: true
	});
})();
JS,
		wp_json_encode( $admin_post_url )
	);

	wp_register_script( 'impreza-edit-reusable-block-link', false, array(), '1.0.0', true );
	wp_enqueue_script( 'impreza-edit-reusable-block-link' );
	wp_add_inline_script( 'impreza-edit-reusable-block-link', $js, 'after' );
}, 100 );
