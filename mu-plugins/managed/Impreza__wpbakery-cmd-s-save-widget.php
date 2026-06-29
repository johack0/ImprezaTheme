<?php
/**
 * Plugin Name: Impreza - WPBakery Cmd+S Save
 * Description: Usa Cmd+S / Ctrl+S per premere "Save changes" quando è aperto un pannello elemento di WPBakery.
 * Version: 1.0.0
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_enqueue_scripts', function() {
	// Carichiamo solo nelle schermate dove tipicamente gira WPBakery backend editor.
	global $pagenow;
	if ( ! in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	// Evita di fare qualsiasi cosa se WPBakery non è attivo.
	if ( ! class_exists( 'Vc_Manager' ) ) {
		return;
	}

	$js = <<<'JS'
(function() {
	"use strict";

	var SAVE_LABELS = [
		"save changes",
		"salva modifiche",
		"salva cambiamenti"
	];

	function isVisible(el) {
		return !!(el && el.offsetParent !== null);
	}

	function findActiveVcPanel() {
		// WPBakery usa `.vc_active` sul pannello attivo.
		var panel = document.querySelector(".vc_ui-panel-window.vc_active");
		if (panel && isVisible(panel)) return panel;

		// Fallback: un pannello visibile qualsiasi (quando la classe non è presente).
		var panels = document.querySelectorAll(".vc_ui-panel-window");
		for (var i = panels.length - 1; i >= 0; i--) {
			if (isVisible(panels[i])) return panels[i];
		}
		return null;
	}

	function findSaveButton(panel) {
		if (!panel) return null;

		// Se WPBakery espone un attributo dedicato, preferiscilo.
		var byData = panel.querySelector('[data-vc-ui-element="button-save"]');
		if (byData) return byData;

		// Heuristica: nei pannelli "Edit" il bottone principale è spesso `.vc_ui-button-action`.
		var candidates = panel.querySelectorAll(".vc_ui-button-action, .vc_ui-button.vc_ui-button-action, button.vc_ui-button-action");
		for (var i = 0; i < candidates.length; i++) {
			var btn = candidates[i];
			if (!btn) continue;

			// Scarta pulsanti disabilitati.
			if (btn.disabled) continue;
			var cls = String(btn.className || "");
			if (cls.indexOf("vc_disabled") !== -1) continue;

			var text = (btn.textContent || "").trim().toLowerCase();
			if (!text) continue;

			for (var j = 0; j < SAVE_LABELS.length; j++) {
				if (text === SAVE_LABELS[j]) return btn;
			}
		}

		return null;
	}

	function clickLikeUser(el) {
		if (!el) return false;
		try {
			el.dispatchEvent(new MouseEvent("mousedown", { bubbles: true, cancelable: true, view: window }));
			el.dispatchEvent(new MouseEvent("mouseup", { bubbles: true, cancelable: true, view: window }));
			el.dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true, view: window }));
			return true;
		} catch (err) {
			try { el.click(); return true; } catch (err2) {}
		}
		return false;
	}

	function trySaveActiveElement() {
		// Non fare nulla se VC non è presente.
		if (typeof window.vc === "undefined") return false;

		var panel = findActiveVcPanel();
		if (!panel) {
			// Ultimo fallback: qualsiasi bottone "save" visibile.
			var globalBtn = document.querySelector('.vc_ui-panel-window [data-vc-ui-element="button-save"]');
			if (globalBtn && isVisible(globalBtn)) {
				return clickLikeUser(globalBtn);
			}
			return false;
		}

		var saveBtn = findSaveButton(panel);
		if (!saveBtn) return false;

		return clickLikeUser(saveBtn);
	}

	function onKeyDown(e) {
		var key = (e.key || "").toLowerCase();
		var code = e.keyCode;

		// Cmd+S / Ctrl+S
		var isSave = key === "s" || code === 83;
		if (!isSave) return;
		if (!(e.metaKey || e.ctrlKey)) return;

		// Se c'è un pannello VC attivo, blocchiamo il comportamento browser (salva pagina) e salviamo l'elemento.
		var didSave = trySaveActiveElement();
		if (didSave) {
			e.preventDefault();
			e.stopPropagation();
		}
	}

	// Aggancio sia a document che a window (capture), per casi tipo editor embedded che fermano bubbling.
	document.addEventListener("keydown", onKeyDown, true);
	window.addEventListener("keydown", onKeyDown, true);
})();
JS;

	// Aggancio "after" allo script già presente di Impreza, se disponibile.
	if ( wp_script_is( 'us_vc_backend_scripts', 'enqueued' ) ) {
		wp_add_inline_script( 'us_vc_backend_scripts', $js, 'after' );
		return;
	}

	// Fallback: enqueue dedicato.
	wp_register_script( 'impreza_wpbakery_cmd_s_save_widget', '', array(), '1.0.0', true );
	wp_enqueue_script( 'impreza_wpbakery_cmd_s_save_widget' );
	wp_add_inline_script( 'impreza_wpbakery_cmd_s_save_widget', $js, 'after' );
}, 20 );

