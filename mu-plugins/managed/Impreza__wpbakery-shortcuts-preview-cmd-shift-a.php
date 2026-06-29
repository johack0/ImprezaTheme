<?php
/**
 * Plugin Name: Impreza - WPBakery Preview Shortcut
 * Description: Usa Cmd+Shift+A / Ctrl+Shift+A per aprire l'anteprima e blocca Cmd+Shift+P / Ctrl+Shift+P in WPBakery.
 * Version: 1.0.0
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_enqueue_scripts', function() {
	global $pagenow;
	if ( ! in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	if ( ! class_exists( 'Vc_Manager' ) ) {
		return;
	}

	$js = <<<'JS'
(function() {
	"use strict";

	var PREVIEW_SELECTORS = [
		// WPBakery backend: preview dentro Post Settings panel
		"#wpb-settings-preview",

		// Fallback WordPress (classic)
		"#post-preview",
		"a#post-preview",
		"a.preview.button",
		"#preview-action a",

		// Possibili varianti UI/toolbar
		".vc_navbar a[title*=\"Preview\" i]",
		".vc_navbar a[title*=\"Anteprima\" i]"
	];

	function isVisible(el) {
		return !!(el && el.offsetParent !== null);
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

	function tryPreviewPage() {
		for (var i = 0; i < PREVIEW_SELECTORS.length; i++) {
			var el = document.querySelector(PREVIEW_SELECTORS[i]);
			if (el && isVisible(el)) {
				return clickLikeUser(el);
			}
		}
		return false;
	}

	function onKeyDown(e) {
		var key = (e.key || "").toLowerCase();
		var code = e.keyCode;

		// Cmd+Shift+A / Ctrl+Shift+A -> Preview pagina
		var isPreviewA = (key === "a" || code === 65) && (e.metaKey || e.ctrlKey) && e.shiftKey;
		if (isPreviewA) {
			var didPreview = tryPreviewPage();
			if (didPreview) {
				e.preventDefault();
				e.stopPropagation();
			}
			return;
		}

		// Disabilita Cmd+Shift+P / Ctrl+Shift+P (shortcut interna WPBakery)
		var isPreviewP = (key === "p" || code === 80) && (e.metaKey || e.ctrlKey) && e.shiftKey;
		if (isPreviewP) {
			e.preventDefault();
			e.stopPropagation();
		}
	}

	document.addEventListener("keydown", onKeyDown, true);
	window.addEventListener("keydown", onKeyDown, true);
})();
JS;

	if ( wp_script_is( 'us_vc_backend_scripts', 'enqueued' ) ) {
		wp_add_inline_script( 'us_vc_backend_scripts', $js, 'after' );
		return;
	}

	wp_register_script( 'impreza_wpbakery_shortcuts_preview', '', array(), '1.0.0', true );
	wp_enqueue_script( 'impreza_wpbakery_shortcuts_preview' );
	wp_add_inline_script( 'impreza_wpbakery_shortcuts_preview', $js, 'after' );
}, 20 );

