<?php
/**
 * UpSolution MCP server — bootstrap.
 *
 * Wires the `wordpress/mcp-adapter` Composer package into us-core to expose
 * a set of UpSolution-specific abilities (post CRUD, term reader, theme-option
 * reader, agent-facing docs) over the MCP Streamable HTTP transport at:
 *
 *     /wp-json/upsolution/v1/mcp
 *
 * Soft-disabled when:
 *   - the Abilities API is not loaded (either via WP core, the Feature Plugin,
 *     or the mcp-adapter's bundled fallback) — gated by function_exists
 *     ('wp_register_ability'),
 *   - the adapter Composer package is not installed,
 *   - the `mcp_enabled` theme option is not truthy (default off; toggled in
 *     Theme Options → Advanced → AI Assistant).
 *
 * @package usCore\Mcp
 */

defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

define( 'US_CORE_MCP_DIR', US_CORE_DIR . 'mcp/' );

// Early gating: feature off → do not touch the adapter at all, no admin
// notice (the feature simply doesn't exist until the user enables it).
if ( ! us_get_option( 'mcp_enabled', 0 ) ) {
	return;
}

/**
 * Check the runtime prerequisites for the MCP server: the mcp-adapter classes
 * (installed via Composer) and the Abilities API (provided by WP core, the
 * Feature Plugin, or the adapter's own fallback — all surface the same
 * `wp_register_ability` function).
 *
 * @return true|string TRUE on success, otherwise a short reason string for the admin notice.
 */
function us_mcp_check_prereqs() {
	if ( ! class_exists( '\WP\MCP\Core\McpAdapter' ) ) {
		return 'UpSolution AI Assistant could not start: the mcp-adapter library is missing. Reinstall or update UpSolution Core.';
	}
	if ( ! function_exists( 'wp_register_ability' ) ) {
		return 'UpSolution AI Assistant could not start: the WordPress Abilities API is not available. Install the Abilities API Feature Plugin or upgrade to a WordPress version that ships it in core.';
	}
	return TRUE;
}

// Feature on, but prereqs fail → register an admin notice and bail.
$us_mcp_prereqs = us_mcp_check_prereqs();
if ( $us_mcp_prereqs !== TRUE ) {
	add_action( 'admin_notices', function () use ( $us_mcp_prereqs ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html( $us_mcp_prereqs )
		);
	} );
	return;
}
unset( $us_mcp_prereqs );

// All clear — load abilities, then the server.
require_once US_CORE_MCP_DIR . 'abilities/posts.php';
require_once US_CORE_MCP_DIR . 'abilities/terms.php';
require_once US_CORE_MCP_DIR . 'abilities/theme-option.php';
require_once US_CORE_MCP_DIR . 'abilities/typography.php';
require_once US_CORE_MCP_DIR . 'abilities/color-palette.php';
require_once US_CORE_MCP_DIR . 'abilities/button-styles.php';
require_once US_CORE_MCP_DIR . 'abilities/field-styles.php';
// site-layout.php must load after color-palette.php (it reuses the shared
// palette color checker) and before preview.php (create-preview calls its
// apply-to-options helper).
require_once US_CORE_MCP_DIR . 'abilities/site-layout.php';
require_once US_CORE_MCP_DIR . 'abilities/preview.php';
require_once US_CORE_MCP_DIR . 'preview-runtime.php';
require_once US_CORE_MCP_DIR . 'abilities/media.php';
require_once US_CORE_MCP_DIR . 'abilities/headers.php';
require_once US_CORE_MCP_DIR . 'abilities/grid-layouts.php';
require_once US_CORE_MCP_DIR . 'abilities/menus.php';
require_once US_CORE_MCP_DIR . 'abilities/docs.php';

/**
 * Permission gate for both the HTTP transport handshake and the simpler
 * ability callbacks. WordPress Application Passwords handle authentication;
 * we additionally require the caller to hold `edit_posts`, the same gate
 * that wp-admin uses for editing pages.
 *
 * Two-layer gating:
 *
 *   1. Transport / read-only abilities use this callback — the broad
 *      edit_posts cap. Sufficient for docs / theme-option / terms reads.
 *   2. The generic post-CRUD abilities in abilities/posts.php carry their
 *      own callback (us_mcp_post_permission_callback) which inspects the
 *      requested `post_type` and consults the registered post-type
 *      object's capability mapping — so e.g. us_testimonial
 *      (capability_type=page) requires edit_pages and the white-label-
 *      remapped template caps on us_content_template / us_page_block are
 *      honoured automatically. Per-post checks (edit_post / delete_post)
 *      run inside those callbacks after the post id has been resolved.
 *
 * Returning FALSE here causes the HTTP transport itself to reject the
 * request with 401/403 before any ability runs.
 *
 * @return bool
 */
function us_mcp_permission_callback() {
	return current_user_can( 'edit_posts' );
}

/**
 * Full list of ability names exposed as MCP tools. Kept here as a flat
 * literal — each name must match a `wp_register_ability( '…' )` call in the
 * corresponding abilities/*.php file. When you add a tool there, append the
 * name here.
 *
 * @return string[]
 */
function us_mcp_tool_ability_names() {
	return array(
		'upsolution/list-posts',
		'upsolution/get-post',
		'upsolution/create-post',
		'upsolution/update-post',
		'upsolution/duplicate-post',
		'upsolution/delete-post',
		'upsolution/list-terms',
		'upsolution/create-term',
		'upsolution/delete-term',
		'upsolution/get-theme-option',
		'upsolution/list-fonts',
		'upsolution/set-typography',
		'upsolution/get-palette',
		'upsolution/set-palette',
		'upsolution/get-site-layout',
		'upsolution/set-site-layout',
		'upsolution/list-button-styles',
		'upsolution/set-button-styles',
		'upsolution/list-field-styles',
		'upsolution/set-field-styles',
		'upsolution/create-preview',
		'upsolution/delete-preview',
		'upsolution/list-media',
		'upsolution/get-media',
		'upsolution/upload-media',
		'upsolution/update-media',
		'upsolution/create-media-upload-url',
		'upsolution/list-headers',
		'upsolution/list-grid-layouts',
		'upsolution/list-menus',
		'upsolution/get-menu',
		'upsolution/set-menu-items',
		'upsolution/set-menu-dropdown',
		'upsolution/list-docs',
		'upsolution/read-doc',
	);
}

\WP\MCP\Core\McpAdapter::instance();

/**
 * Register the UpSolution MCP server.
 *
 * Endpoint: /wp-json/upsolution/v1/mcp
 * Tools:    post CRUD, term reader, theme-option reader, docs reader.
 * Resources: none — the authoring docs live in the abilities tool surface
 *            (see abilities/docs.php) because they are agent-only material.
 */
add_action( 'mcp_adapter_init', function ( $adapter ) {
	/** @var \WP\MCP\Core\McpAdapter $adapter */
	$version = defined( 'US_CORE_VERSION' ) ? US_CORE_VERSION : 'unknown';

	// The `instructions` string is shipped to every connected MCP client as
	// the server's standing prompt — paid for once per session, but counted
	// against every turn's input tokens. Keep it lean:
	//
	//   1. A generated list of top-level authoring docs — pulled from the
	//      docs manifest via us_mcp_docs_instructions_snippet(). Manifest
	//      stays the single source of truth for (id, one-line description);
	//      adding a top-level doc auto-appears here.
	//   2. The non-negotiable policy that must reach the agent BEFORE any
	//      docs are pulled (use-existing-templates, css="..." vs color_scheme,
	//      group-attribute encoding, IDs-must-come-from-tools) — cannot live
	//      in the docs themselves because a client may skip reading them.
	//
	// What is NOT here: per-tool selection / workflow chains. The MCP
	// transport returns each ability's `description` in tools/list, and that
	// is where workflow hints (list-terms before assigning terms, list-media
	// before referencing images, etc.) belong — duplicating them here just
	// doubles the input-token cost on every turn.
	$docs_snippet = us_mcp_docs_instructions_snippet();
	$instructions = <<<EOT
Authoring tools for UpSolution Impreza & Zephyr (us-core {$version}). CRUD edits pages, posts, portfolio, testimonials, Page Templates (us_content_template) and Reusable Blocks (us_page_block) via the shortcode pipeline.

Before writing shortcode markup, read the relevant docs via `upsolution-read-doc`. Top-level ids:

{$docs_snippet}

Per-shortcode (`shortcodes/<config-id>`) and per-category section (`sections/<id>`) records are pulled on demand — see the `shortcodes` and `sections` indexes for the full list.

Non-negotiable rules (apply BEFORE pulling docs):
  - Creating a new section / block / page (hero, about, features, services, CTA, stats, pricing, steps, FAQ, team, contact, blog, portfolio, testimonials, gallery, footer, …): first call upsolution-list-docs, read the matching `sections/<category>` snapshot, start from one of its templates. Adapt text / images / links but keep the `css="…"` attributes — they carry the design. Composing from scratch when a template fits silently produces broken sections.
  - Per-element colors / backgrounds / spacing / borders / typography go in `css="…"` (URL-encoded JSON in DOUBLE quotes). `color_scheme` is a fixed enum — see `composition-rules`.
  - Group attributes (items, responsive, tax_query, orderby_items, …) MUST be URL-encoded JSON in DOUBLE quotes — raw JSON is mangled by wptexturize and the shortcode silently falls back to defaults. The keys INSIDE differ per element (us_socials items use "type", not "icon") — copy the shape from that shortcode's doc, never guess it.
  - Use only documented attributes. Before emitting a shortcode you have not already read this session, pull its `shortcodes/<config-id>` record and use only the parameters and values it lists. Invented attributes and wrong icon names are dropped silently — the element renders with defaults while the page log fills with errors.
  - Per-install IDs and palette values come from lookup tools (upsolution-list-button-styles, upsolution-list-field-styles, upsolution-get-palette, upsolution-get-theme-option). Never guess — you get the wrong fallback.
EOT;

	$adapter->create_server(
		'upsolution',
		'upsolution/v1',
		'mcp',
		'UpSolution Impreza / Zephyr',
		$instructions,
		defined( 'US_CORE_VERSION' ) ? US_CORE_VERSION : '0.0.0',
		array( \WP\MCP\Transport\HttpTransport::class ),
		\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,
		\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class,
		us_mcp_tool_ability_names(),
		array(), // resources — none; docs are exposed as tools (see abilities/docs.php).
		array(), // prompts
		'us_mcp_permission_callback'
	);
} );
