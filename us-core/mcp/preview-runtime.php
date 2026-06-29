<?php
/**
 * UpSolution MCP — preview-mode frontend activation.
 *
 * When a frontend request carries `?us_theme_options_preview=<key>` AND the
 * matching transient exists, this file:
 *
 *   1. overlays the snapshot stored under the key on top of $usof_options
 *      via the `usof_load_options_once` filter — every us_get_option() call
 *      thereafter returns the preview value;
 *   2. sends no-cache headers so page caches (W3TC / WP Rocket / Varnish /
 *      object cache) don't bake a preview into a shared cache entry;
 *   3. emits a regenerated <style> block in <head> AFTER the baseline
 *      `us-assets/style_theme.css` <link>, so the CSS cascade wins —
 *      the static asset file still carries the live values and is shared
 *      across every visitor, including the preview viewer;
 *   4. injects a small floating "Preview mode · exit" banner in the bottom
 *      right of every preview page;
 *   5. rewrites same-origin <a href> values via JS so internal navigation
 *      keeps the preview key — without this, the first click would drop the
 *      visitor back into the live site.
 *
 * Keys not matching the format / not matching an existing transient are
 * silently ignored: the visitor sees the live site (no error, no leak).
 *
 * Bearer-token model: anyone with the URL sees the preview. No login check
 * intentionally — agents share links with non-logged clients for review.
 *
 * Companion: abilities/preview.php (create-preview / delete-preview tools).
 *
 * @package usCore\Mcp
 */

defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

// Public surface (query var, transient prefix, key format) — also referenced
// by abilities/preview.php and any future tooling.
if ( ! defined( 'US_MCP_PREVIEW_QUERY_VAR' ) ) {
	define( 'US_MCP_PREVIEW_QUERY_VAR', 'us_theme_options_preview' );
}
if ( ! defined( 'US_MCP_PREVIEW_TRANSIENT_PREFIX' ) ) {
	define( 'US_MCP_PREVIEW_TRANSIENT_PREFIX', 'us_mcp_preview_' );
}
if ( ! defined( 'US_MCP_PREVIEW_KEY_REGEX' ) ) {
	// 16 bytes of entropy expressed as 32 lowercase hex chars.
	define( 'US_MCP_PREVIEW_KEY_REGEX', '/^[a-f0-9]{32}$/' );
}

/**
 * Resolve and validate the current request's preview key. Returns the raw
 * hex string if the query var is set, well-formed, AND the matching
 * transient exists; '' otherwise. Cached per-request — safe to call from
 * multiple hooks without re-hitting the object cache for the transient.
 *
 * Admin requests are excluded — preview is a frontend mechanism.
 *
 * @return string
 */
function us_mcp_preview_active_key() {
	static $resolved = NULL;
	if ( $resolved !== NULL ) {
		return $resolved;
	}
	$resolved = '';

	if ( is_admin() ) {
		return $resolved;
	}
	if ( empty( $_GET[ US_MCP_PREVIEW_QUERY_VAR ] ) ) {
		return $resolved;
	}
	$raw = sanitize_text_field( wp_unslash( (string) $_GET[ US_MCP_PREVIEW_QUERY_VAR ] ) );
	if ( ! preg_match( US_MCP_PREVIEW_KEY_REGEX, $raw ) ) {
		return $resolved;
	}
	$payload = get_transient( US_MCP_PREVIEW_TRANSIENT_PREFIX . $raw );
	if ( ! is_array( $payload ) OR ! isset( $payload['snapshot'] ) OR ! is_array( $payload['snapshot'] ) ) {
		return $resolved;
	}

	$resolved = $raw;
	return $resolved;
}

/**
 * Return the full payload stored under the active key. NULL if no key.
 *
 * @return array|null
 */
function us_mcp_preview_payload() {
	static $cached = NULL;
	if ( $cached !== NULL ) {
		return $cached ?: NULL;
	}
	$key = us_mcp_preview_active_key();
	if ( $key === '' ) {
		$cached = array();
		return NULL;
	}
	$payload = get_transient( US_MCP_PREVIEW_TRANSIENT_PREFIX . $key );
	if ( ! is_array( $payload ) ) {
		$cached = array();
		return NULL;
	}
	$cached = $payload;
	return $payload;
}

// =====================================================================
// Single gate: if the current request is not a valid preview activation,
// stop here. Non-preview production requests pay exactly the cost of one
// `us_mcp_preview_active_key()` call above (which short-circuits at the
// first `empty($_GET[…])` check) plus two function declarations — no hook
// registrations, no per-hook gate re-checks, no closure churn.
// =====================================================================
if ( us_mcp_preview_active_key() === '' ) {
	return;
}

/**
 * Overlay the preview snapshot on top of the loaded $usof_options. Denied
 * options (us_mcp_denied_option_patterns()) were dropped from the snapshot
 * at create-preview time, so they keep their live values automatically —
 * credentials are never serialised through a transient.
 *
 * Priority 100: runs after any third-party customisation of the option
 * array, so our preview is what the visitor actually sees.
 */
add_filter( 'usof_load_options_once', function ( $opts ) {
	$payload = us_mcp_preview_payload();
	if ( $payload === NULL ) {
		return $opts;
	}
	// Parentheses + `&&` — `AND` would be assigned before the comparison
	// runs and $snapshot would silently become a boolean.
	$snapshot = ( isset( $payload['snapshot'] ) && is_array( $payload['snapshot'] ) ) ? $payload['snapshot'] : array();
	if ( ! is_array( $opts ) ) {
		$opts = array();
	}
	foreach ( $snapshot as $k => $v ) {
		$opts[ $k ] = $v;
	}
	// Pin asset optimization OFF for the preview request. The optimized branch
	// lazily regenerates the SHARED combined CSS from $usof_options when the file
	// is missing (functions/enqueue.php → us_generate_asset_file) — during a
	// preview that global holds draft values, so the regenerated file (served to
	// every visitor) would carry the preview styling, and the write-failure
	// fallback (enqueue.php) would persist the whole preview snapshot to the live
	// options row. Forcing the non-optimized branch makes the frontend use the
	// static stylesheet + our inline preview <style>, so nothing shared is
	// regenerated or written. Reads only — the overlay is never saved, and the
	// guards below block any stray persist anyway.
	$opts['optimize_assets'] = 0;
	return $opts;
}, 100, 1 );

/**
 * Belt-and-suspenders: while a preview is active, no frontend code path may
 * persist Theme Options or the derived theme-options CSS. A preview request is
 * a frontend GET — there is no legitimate option write to make here, and any
 * update_option() on these keys would bake the overlaid preview values into
 * live storage (the very leak the optimize_assets=0 pin above prevents on the
 * known path; this backstops every other path). Returning the stored value
 * short-circuits update_option to a no-op. Reads are untouched — header.php
 * still gets the preview CSS through the pre_option_us_theme_options_css filter
 * below; only writes are blocked.
 */
if ( defined( 'US_THEMENAME' ) ) {
	add_filter( 'pre_update_option_usof_options_' . US_THEMENAME, function ( $value, $old_value ) {
		return $old_value;
	}, 10, 2 );
}
add_filter( 'pre_update_option_us_theme_options_css', function ( $value, $old_value ) {
	return $old_value;
}, 10, 2 );

/**
 * Strip every layer of HTTP caching on preview responses. Page caches keyed
 * by URL would otherwise capture a preview page and serve it to anonymous
 * visitors hitting the SAME URL with the SAME query string — a small
 * window, but not zero.
 */
add_action( 'send_headers', function () {
	nocache_headers();
	if ( ! headers_sent() ) {
		header( 'Cache-Control: private, no-store, max-age=0' );
		header( 'X-Robots-Tag: noindex, nofollow', TRUE );
	}
} );

/**
 * Render the theme-options CSS against the overlaid $usof_options. Used by
 * both override paths below (the cached-option filter and the wp_head inline
 * injection) — same regen on both sides keeps them consistent.
 *
 * @return string Empty string if the template helpers are unavailable.
 */
function us_mcp_preview_render_theme_options_css() {
	if ( ! function_exists( 'us_get_template' ) ) {
		return '';
	}
	$css = us_get_template( 'templates/css-theme-options' );
	if ( $css === '' OR $css === FALSE OR $css === NULL ) {
		return '';
	}
	if ( function_exists( 'us_minify_css' ) ) {
		$css = us_minify_css( $css );
	}
	return (string) $css;
}

/**
 * Override the cached "us_theme_options_css" option so any reader of it
 * during a preview request gets the regenerated preview CSS rather than the
 * baked-from-live value.
 *
 * Why this matters: templates/header.php prints
 * `<style id="us-theme-options-css">…</style>` INSIDE <head> but AFTER the
 * wp_head() call (header.php:90-100). Our wp_head priority-999 injection
 * lands earlier in the document → the baseline `<style>` would otherwise win
 * the cascade and undo the preview. Filtering `pre_option_us_theme_options_css`
 * makes header.php emit our preview CSS in its native slot, so cascade order
 * is correct in the cached-inline mode.
 *
 * In US_DEV mode header.php re-renders the template every request anyway
 * (and the template now reads the overlaid $usof_options) — this filter's
 * value gets ignored in that branch, but registering it costs nothing.
 *
 * Returning FALSE here = "filter declines, fall back to normal lookup"; we
 * only fall through if the helpers aren't loaded, which would mean the site
 * is broken in worse ways anyway.
 */
add_filter( 'pre_option_us_theme_options_css', function () {
	$css = us_mcp_preview_render_theme_options_css();
	return $css === '' ? FALSE : $css;
}, 10, 0 );

/**
 * Fallback for the static-assets mode (`optimize_assets` ON, US_DEV unset):
 * header.php's inline-style block is skipped entirely and the baseline CSS
 * comes from a `<link>` to `wp-content/uploads/us-assets/style_theme.css`.
 * Filtering an option doesn't help — nothing reads `us_theme_options_css`
 * in that mode. So we ALSO emit our preview CSS as an inline `<style>` at
 * the end of <head>, where it cascades over any earlier <link>.
 *
 * Priority 999: late, so we land after the theme's own enqueues and the
 * static <link>.
 *
 * In cached-inline / US_DEV modes this injection is redundant (header.php
 * emits the same preview CSS later in the document), but harmless — the
 * later baseline block carries identical rules.
 */
add_action( 'wp_head', function () {
	$css = us_mcp_preview_render_theme_options_css();
	if ( $css === '' ) {
		return;
	}
	echo "\n<style id=\"us-mcp-preview-overrides\">\n" . $css . "\n</style>\n";
}, 999 );

/**
 * Register the link-rewrite / TTL-countdown JS via wp_add_inline_script
 * against a dummy enqueued handle. Going through the script pipeline (rather
 * than emitting a bare <script> from wp_footer) lets any CSP-nonce middleware
 * filtering `script_loader_tag` decorate the resulting tag the same way it
 * decorates theme-bundled scripts — without this, a strict
 * `script-src 'self' 'nonce-…'` policy silently drops the script and the
 * banner countdown / link-rewriting break with no console diagnostic.
 *
 * The handle has no src (`FALSE`); WP emits only the inline payload.
 * `in_footer=TRUE` puts the script at the end of body. Note WP prints footer
 * scripts on wp_footer at priority 20, while the banner holding the
 * `[data-us-mcp-preview-ttl]` slot is printed at priority 999 — AFTER this
 * script. The payload therefore defers its DOM work to DOMContentLoaded, by
 * which point both the anchors and the (later-printed) countdown slot exist.
 *
 * Dynamic links added after initial load (AJAX, infinite scroll) are NOT
 * rewritten in this MVP. Document as a limitation in llms/design/preview.md.
 */
add_action( 'wp_enqueue_scripts', function () {
	$key        = us_mcp_preview_active_key(); // already validated by the file-load gate
	$payload    = us_mcp_preview_payload();
	$expires_at = isset( $payload['expires_at'] ) ? (int) $payload['expires_at'] : 0;

	$param_js = esc_js( US_MCP_PREVIEW_QUERY_VAR );
	$key_js   = esc_js( $key );

	$js  = '(function(){';
	$js .= 'var KEY="' . $key_js . '",PARAM="' . $param_js . '",EXPIRES=' . $expires_at . ';';
	$js .= 'function init(){';
	// Rewrite same-origin <a href> values.
	$js .= 'try{document.querySelectorAll("a[href]").forEach(function(a){';
	$js .= 'if(a.hasAttribute("data-us-mcp-preview-exit"))return;';
	$js .= 'try{var u=new URL(a.href,location.href);';
	$js .= 'if(u.origin!==location.origin)return;';
	$js .= 'if(u.searchParams.has(PARAM))return;';
	$js .= 'u.searchParams.set(PARAM,KEY);';
	$js .= 'a.href=u.toString();}catch(_){}})}catch(_){}';
	// TTL countdown.
	$js .= 'if(EXPIRES>0){var n=document.querySelector("[data-us-mcp-preview-ttl]");';
	$js .= 'if(n){var t=function(){var l=EXPIRES-Math.floor(Date.now()/1000);';
	$js .= 'if(l<=0){n.textContent="expired";return}';
	$js .= 'var h=Math.floor(l/3600),m=Math.floor((l%3600)/60),s=l%60;';
	$js .= 'n.textContent="expires in "+(h>0?h+"h ":"")+(m>0||h>0?m+"m ":"")+s+"s"};';
	$js .= 't();setInterval(t,1000)}}';
	$js .= '}'; // end init()
	// This script is printed by wp_print_footer_scripts (wp_footer priority 20),
	// but the banner that holds the [data-us-mcp-preview-ttl] slot is printed at
	// priority 999 — later in the document. Defer init() to DOMContentLoaded so
	// the slot (and all anchors) exist before we query for them.
	$js .= 'if(document.readyState!=="loading"){init();}';
	$js .= 'else{document.addEventListener("DOMContentLoaded",init);}';
	$js .= '})();';

	wp_register_script( 'us-mcp-preview', FALSE, array(), NULL, /* in_footer */ TRUE );
	wp_enqueue_script( 'us-mcp-preview' );
	wp_add_inline_script( 'us-mcp-preview', $js );
} );

/**
 * Render the bottom-centred "Preview mode" banner. Banner styles are inline —
 * no external CSS file is shipped just for the preview UI. Z-index is the
 * maximum signed 32-bit int so we sit above theme stacking contexts.
 *
 * The link-rewriting / countdown JS that targets the
 * `[data-us-mcp-preview-ttl]` slot below is registered separately in the
 * wp_enqueue_scripts hook above.
 */
add_action( 'wp_footer', function () {
	$payload = us_mcp_preview_payload();
	$label   = ( isset( $payload['label'] ) && is_string( $payload['label'] ) ) ? (string) $payload['label'] : '';

	$req      = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
	$exit_url = remove_query_arg( US_MCP_PREVIEW_QUERY_VAR, $req );

	$exit_attr  = esc_attr( $exit_url );
	$label_html = $label !== '' ? esc_html( $label ) : '';

	echo "\n";
	echo '<div id="us-mcp-preview-banner" role="status" aria-label="Theme options preview"';
	echo ' style="position:fixed;bottom:16px;left:50%;transform:translateX(-50%);z-index:2147483647;background:#1a1a1a;color:#fff3c2;padding:10px 14px;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.3);font:13px/1.4 system-ui,-apple-system,Segoe UI,sans-serif;display:flex;align-items:center;max-width:90vw;">';
	echo '<span><strong>Preview mode:</strong> ';
	echo $label_html;
	echo '<span data-us-mcp-preview-ttl style="opacity:.7;margin-inline:8px;"></span></span>';
	// data-us-mcp-preview-exit pins this anchor as exempt from the
	// link-rewriting JS — otherwise the rewriter would notice the missing
	// preview query var and put it right back, leaving the user trapped
	// inside the preview session.
	echo '<a href="' . $exit_attr . '" data-us-mcp-preview-exit="1" style="color:#fff3c2;text-decoration:underline;opacity:.85;">Exit preview</a>';
	echo '</div>';
	echo "\n";
}, 999 );

/**
 * Force a re-load of $usof_options NOW so our `usof_load_options_once`
 * filter (registered above) actually fires.
 *
 * Why this is needed: in functions/init.php some early code paths call
 * us_get_option() BEFORE the line that requires mcp/bootstrap.php
 * (e.g. the media_category gate). usof_load_options_once() caches the
 * loaded array in a global and short-circuits subsequent calls — by the
 * time bootstrap.php → preview-runtime.php registers our filter, the
 * global already holds the unfiltered live values and no code in the
 * normal request lifecycle calls usof_load_options_once() a second time.
 *
 * Without this re-load, the filter is registered but never invoked, and
 * preview pages render with live colors / typography.
 *
 * No gate here — we're already past the file-load `us_mcp_preview_active_key()`
 * guard above, so we only reach this line when a preview is active.
 */
if ( function_exists( 'usof_load_options_once' ) ) {
	usof_load_options_once( /* $force_reload */ TRUE );
}
