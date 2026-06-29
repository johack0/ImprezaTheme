<?php
/**
 * UpSolution MCP — content authoring docs as agent tools.
 *
 * Exposes the curated content-authoring docs under
 * `plugins/us-core/llms/content/` through two MCP tools:
 *
 *   upsolution-list-docs   — returns the manifest (id / label / description /
 *                            mime / bytes for every available doc).
 *   upsolution-read-doc    — reads one doc by `id` and returns its text.
 *
 * The docs are agent-only material (rules, shortcode reference, section
 * snapshots). They are NOT addressed at humans browsing the site, so they
 * are deliberately NOT registered as MCP resources — the resources
 * primitive in the MCP protocol is user-facing (clients surface them in
 * an attach / `@` menu for a human to opt-in), which would never trigger
 * for the kind of automated authoring flow we run here. Tools, on the
 * other hand, are surfaced to the model directly: the agent calls
 * `list-docs` on first use, then `read-doc` for the entries it actually
 * needs to compose markup.
 *
 * Manifest entries are keyed by a short stable id derived from the file's
 * relative path inside `llms/content/` (extension stripped). Examples:
 *
 *   composition-rules           ← composition-rules.md
 *   shortcodes                  ← shortcodes.md        (slim index of all shortcodes)
 *   shortcodes/vc_row           ← shortcodes/vc_row.md (per-shortcode body)
 *   sections                    ← sections.md          (category index)
 *   sections/pr                 ← sections/pr.md       (per-category snapshot)
 *
 * Adding a new top-level doc: extend $top in us_mcp_docs_manifest().
 * Adding a per-shortcode entry or per-category section snapshot: just drop
 * the file into `llms/content/shortcodes/` or `llms/content/sections/` —
 * both folders are auto-discovered by a glob below.
 *
 * @package usCore\Mcp
 */

defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Hard cap on the size of a single read-doc response, in bytes. Above this,
 * read-doc returns 413 with a pointer back to list-docs (which carries the
 * exact `bytes` per entry, so the agent can decide whether to ask for it).
 *
 * Real authoring docs in this manifest peak around 50–80 KB (the largest
 * sections snapshot); 512 KB is well above that headroom but small enough
 * to keep one accidental read from consuming the agent's entire context.
 *
 * If a future doc legitimately needs to be larger, split it into chapters
 * under sections/ rather than raising this — the agent gains finer-grained
 * pulls and never has to load the whole monolith.
 */
const US_MCP_DOC_MAX_BYTES = 524288;

/**
 * Build the docs manifest. Each entry: doc-id => spec.
 *
 * Cached per-request — the manifest does ~13 is_readable() + one glob() of
 * the sections directory, and it gets hit from three call sites in the same
 * request lifecycle (the boot-instruction snippet, the list-docs callback,
 * and the read-doc callback). Sub-millisecond either way, but the cache makes
 * the cost predictable and removes redundant filesystem stats.
 *
 * @return array<string, array{file: string, label: string, description: string, mime: string}>
 */
function us_mcp_docs_manifest() {
	static $cached = NULL;
	if ( $cached !== NULL ) {
		return $cached;
	}
	$content_dir = US_CORE_DIR . 'llms/content/';
	$manifest = array();

	// --- Top-level entry points -----------------------------------------
	// Order in this array is the order shown to the agent in the boot
	// instructions, so keep the root dispatcher first and the per-element
	// parameter groups last.
	$top = array(
		'llms-content.txt' => array(
			'label'       => 'UpSolution content agent — root entry',
			'description' => 'Root dispatcher for the content authoring agent. Lists scope and points at composition-rules / shortcodes / sections.',
			'mime'        => 'text/plain',
		),
		'composition-rules.md' => array(
			'label'       => 'UpSolution composition rules',
			'description' => 'MANDATORY before generating shortcode markup. Root structure, nesting graph, attribute encoding (link / css / responsive JSON), HTML allowlists, palette tokens, anti-patterns.',
			'mime'        => 'text/markdown',
		),
		'shortcodes.md' => array(
			'label'       => 'UpSolution shortcodes index',
			'description' => 'Index of 73 shortcodes grouped by category. Pull individual `shortcodes/<config-id>` records on demand — never preload them all. Config-id rule: us_* tags drop their prefix (us_btn → shortcodes/btn), vc_* tags keep it (shortcodes/vc_row).',
			'mime'        => 'text/markdown',
		),
		'sections.md' => array(
			'label'       => 'UpSolution pre-built section index',
			'description' => 'Index of 18 pre-built section categories. Two-letter ids: in=Intro, ab=About, fe=Features, se=Services, ct=Call To Action, nu=Numbers & Stats, pr=Pricing Plans, st=Steps, qa=FAQ, tm=Team, co=Contact, te=Text Only, kp=Key Phrase, bl=Blog & News, po=Portfolio, re=Testimonials, ga=Gallery, fo=Footer. Read before passing `sections/<id>` to read-doc.',
			'mime'        => 'text/markdown',
		),
		'post-types.md' => array(
			'label'       => 'Post types matrix',
			'description' => 'MANDATORY before calling create-post / update-post with a non-page post_type. Per-type matrix: which optional fields (excerpt, featured_image_id, terms, meta) apply, taxonomy + meta whitelists for post / us_portfolio / us_testimonial / us_content_template / us_page_block, and how to pick page vs Content Template vs Reusable Block.',
			'mime'        => 'text/markdown',
		),
		'element-design.md' => array(
			'label'       => 'Element Design parameters',
			'description' => 'Shared Design parameter group: the central `css=` attribute and its prose specification.',
			'mime'        => 'text/markdown',
		),
		'element-display-logic.md' => array(
			'label'       => 'Element Display Logic parameters',
			'description' => 'Shared Display-Logic parameter group: conditional rendering based on user, screen size, post fields.',
			'mime'        => 'text/markdown',
		),
		'element-effects.md' => array(
			'label'       => 'Element Effects parameters',
			'description' => 'Shared Effects parameter group: motion / parallax / scroll-triggered animations.',
			'mime'        => 'text/markdown',
		),
		'element-dynamic-values.md' => array(
			'label'       => 'Dynamic value tokens',
			'description' => 'Dynamic-value tokens and link enums usable inside shortcode attributes.',
			'mime'        => 'text/markdown',
		),
	);
	foreach ( $top as $rel => $meta ) {
		$path = $content_dir . $rel;
		if ( ! is_readable( $path ) ) {
			continue;
		}
		$id = pathinfo( $rel, PATHINFO_FILENAME ); // strip extension
		$manifest[ $id ] = array(
			'file'        => $path,
			'label'       => $meta['label'],
			'description' => $meta['description'],
			'mime'        => $meta['mime'],
		);
	}

	// --- Design docs (Theme Options surfaces — not content authoring) ---
	// Each id is prefixed `design/` so it stays distinct from any future
	// content-side doc that happens to share a basename, and so it's clearly
	// readable in the standing prompt as "Theme Options territory".
	$design_dir = US_CORE_DIR . 'llms/design/';
	$design = array(
		'typography.md' => array(
			'label'       => 'Typography editor reference',
			'description' => 'MANDATORY before calling upsolution-set-typography. Field set per tag (body / h1..h6), responsive object shape ({default, laptops, tablets, mobiles}), font-weight rules for Variable vs static fonts (intermediate values, wght/wdth axes, font-stretch), inheritance tokens (inherit, var(--h1-…)), merge semantics, workflow, anti-patterns.',
			'mime'        => 'text/markdown',
		),
		'color-palette.md' => array(
			'label'       => 'Color palette editor reference',
			'description' => 'MANDATORY before calling upsolution-set-palette. Full field map for header / content / footer color pickers (49 keys) with the gradient policy per key, accepted color syntax, Custom Global Colors entry shape and slug rules, patch-vs-replace semantics, workflow, anti-patterns.',
			'mime'        => 'text/markdown',
		),
		'buttons.md' => array(
			'label'       => 'Button styles editor reference',
			'description' => 'MANDATORY before calling upsolution-set-button-styles. Anatomy of a button-style entry (identification, hover/animation, colors with gradient policy, two box-shadow groups, typography and sizes, font enum), the four operations (add / update / delete / reorder), the two hard rules (id immutable, list never empty), preview integration, workflow, anti-patterns.',
			'mime'        => 'text/markdown',
		),
		'field-styles.md' => array(
			'label'       => 'Field styles editor reference',
			'description' => 'MANDATORY before calling upsolution-set-field-styles. Anatomy of a field-style entry (identification, idle/focus colors with gradient policy, two box-shadow groups, typography and sizes, font enum), the four operations (add / update / delete / reorder), the hard rules (id immutable, list never empty, first entry = site-wide default), preview integration, workflow, anti-patterns.',
			'mime'        => 'text/markdown',
		),
		'menus.md' => array(
			'label'       => 'Navigation menus editor reference',
			'description' => 'MANDATORY before calling upsolution-set-menu-items. Item anatomy per type (post_type / taxonomy / custom / post_type_archive / reusable_block) with the field matrix and title-inheritance rules, the four operations (add / update / remove / reorder) with "new:<i>" tokens and the declarative reorder tree, where menus render in the theme (header Menu element, us_additional_menu), workflow, anti-patterns.',
			'mime'        => 'text/markdown',
		),
		'menu-dropdown.md' => array(
			'label'       => 'Menu dropdown (mega-menu) settings reference',
			'description' => 'MANDATORY before calling upsolution-set-menu-dropdown. The per-first-level-item dropdown styling (us_mega_menu_settings): the full field list with accepted values, the show_if dependencies between fields (side panel, width modes, background image), partial-patch vs reset semantics, the first-level-only rule, how it relates to reusable_block mega-menus, workflow, anti-patterns.',
			'mime'        => 'text/markdown',
		),
		'preview.md' => array(
			'label'       => 'Theme options preview links reference',
			'description' => 'MANDATORY before calling upsolution-create-preview. Input shape (palette + typography array + TTL + label), public-bearer URL semantics, click-through behaviour, denylist and security model, storage / lifetime, workflow, anti-patterns.',
			'mime'        => 'text/markdown',
		),
	);
	foreach ( $design as $rel => $meta ) {
		$path = $design_dir . $rel;
		if ( ! is_readable( $path ) ) {
			continue;
		}
		$id = 'design/' . pathinfo( $rel, PATHINFO_FILENAME );
		$manifest[ $id ] = array(
			'file'        => $path,
			'label'       => $meta['label'],
			'description' => $meta['description'],
			'mime'        => $meta['mime'],
		);
	}

	// --- Per-shortcode authoring records --------------------------------
	// `shortcodes/<config-id>` — one file per shortcode (containers, basic,
	// interactive, lists, post-elements, …). The slim index at top-level id
	// `shortcodes` lists them all and is the entry point; agents pull these
	// on demand as they compose markup.
	//
	// Label/description are synthesized from the filename so adding a new
	// shortcode overlay (run `scripts/llms/build.php --only=content-shortcodes`)
	// flows into the manifest without a code change here. The public tag is
	// derived the same way `scripts/llms/generators/_helpers.php` does it:
	// `vc_*` ids keep their prefix, everything else is `us_<id>`.
	$shortcodes_dir = $content_dir . 'shortcodes/';
	if ( is_dir( $shortcodes_dir ) ) {
		$files = glob( $shortcodes_dir . '*.md' );
		if ( is_array( $files ) ) {
			foreach ( $files as $path ) {
				$config_id  = pathinfo( $path, PATHINFO_FILENAME );
				$public_tag = ( strpos( $config_id, 'vc_' ) === 0 ) ? $config_id : 'us_' . $config_id;
				$id = 'shortcodes/' . $config_id;
				// Deliberately terse: what a "shortcode authoring record" contains
				// is described ONCE in the list-docs tool description — repeating
				// it per entry cost ~3-4k tokens on every list-docs response.
				$manifest[ $id ] = array(
					'file'        => $path,
					'label'       => sprintf( 'Shortcode: %s', $public_tag ),
					'description' => sprintf( 'Authoring record for [%s].', $public_tag ),
					'mime'        => 'text/markdown',
				);
			}
		}
	}

	// --- Per-category section snapshots ---------------------------------
	// Human names for the two-letter us.api category ids. Mirrors the category
	// list in llms/content/sections.md (rebuilt by scripts/llms/build.php
	// --only=sections); a new category missing here falls back to its raw id.
	$section_names = array(
		'in' => 'Intro',
		'ab' => 'About',
		'fe' => 'Features',
		'se' => 'Services',
		'ct' => 'Call To Action',
		'nu' => 'Numbers & Stats',
		'pr' => 'Pricing Plans',
		'st' => 'Steps',
		'qa' => 'FAQ',
		'tm' => 'Team',
		'co' => 'Contact',
		'te' => 'Text Only',
		'kp' => 'Key Phrase',
		'bl' => 'Blog & News',
		'po' => 'Portfolio',
		're' => 'Testimonials',
		'ga' => 'Gallery',
		'fo' => 'Footer',
	);
	$sections_dir = $content_dir . 'sections/';
	if ( is_dir( $sections_dir ) ) {
		// glob() returns FALSE on read failure, not an empty array — guard
		// explicitly so a stray FALSE doesn't slip into the foreach and
		// trip pathinfo()'s string typehint in PHP 8.1+.
		$files = glob( $sections_dir . '*.md' );
		if ( is_array( $files ) ) {
			foreach ( $files as $path ) {
				$category = pathinfo( $path, PATHINFO_FILENAME );
				$cat_name = $section_names[ $category ] ?? strtoupper( $category );
				$id = 'sections/' . $category;
				$manifest[ $id ] = array(
					'file'        => $path,
					'label'       => sprintf( 'Section templates: %s — %s', strtoupper( $category ), $cat_name ),
					'description' => sprintf( 'Pre-built "%s" section templates.', $cat_name ),
					'mime'        => 'text/markdown',
				);
			}
		}
	}

	$cached = $manifest;
	return $cached;
}

/**
 * Build the docs-list snippet that bootstrap.php interpolates into the
 * server's `instructions` string. Iterates the manifest so adding a new
 * top-level doc auto-appears in the boot prompt — the manifest stays the
 * single source of truth for (id, description). Per-shortcode authoring
 * entries (`shortcodes/<config-id>`) and per-category section snapshots
 * (`sections/<id>`) are skipped here: too granular for the standing prompt,
 * they're meant to be pulled on demand via read-doc.
 *
 * @return string Newline-joined lines of the form "  - <id>   <description>".
 */
function us_mcp_docs_instructions_snippet() {
	$lines = array();
	foreach ( us_mcp_docs_manifest() as $id => $spec ) {
		if ( strpos( $id, 'sections/' ) === 0 OR strpos( $id, 'shortcodes/' ) === 0 ) {
			continue;
		}
		$lines[] = sprintf( '  - %s   %s', $id, $spec['description'] );
	}
	return implode( "\n", $lines );
}

add_action( 'wp_abilities_api_init', function () {
	// Derive the top-level id list at registration time so this prose stays
	// in sync with the manifest. Drop the granular families (shortcodes/<id>,
	// sections/<id>) — they're too verbose for the description and get a
	// separate sentence below.
	$top_level_ids = array_filter(
		array_keys( us_mcp_docs_manifest() ),
		function ( $id ) {
			return strpos( $id, 'sections/' ) !== 0
				AND strpos( $id, 'shortcodes/' ) !== 0;
		}
	);

	wp_register_ability( 'upsolution/list-docs', array(
		'label'               => 'List authoring docs available to the agent',
		'description'         => sprintf(
			'Enumerate the content-authoring docs the agent can pull via upsolution-read-doc. Returns one entry per doc with id (the value to pass to read-doc), label, one-line description, mime type, and size in bytes. The top-level entries (%s) are the agent\'s reference material. Entries under shortcodes/<config-id> are per-shortcode authoring records — each covers when to use, when to avoid, key parameters with valid values, a minimal example, common combinations, and anti-patterns; pull one on demand once the `shortcodes` index points the agent at a specific config-id. Entries under sections/<category> are per-category section-template snapshots from the UpSolution library, pulled on demand when composing that section type.',
			implode( ', ', $top_level_ids )
		),
		'category'            => 'upsolution',
		// No input — call with no params.
		'output_schema'       => array(
			'type'       => 'object',
			'required'   => array( 'docs' ),
			'properties' => array(
				'docs' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'required'   => array( 'id', 'label', 'description', 'mime', 'bytes' ),
						'properties' => array(
							'id'          => array( 'type' => 'string', 'description' => 'Identifier to pass to upsolution-read-doc (e.g. "composition-rules", "sections/pr").' ),
							'label'       => array( 'type' => 'string' ),
							'description' => array( 'type' => 'string' ),
							'mime'        => array( 'type' => 'string' ),
							'bytes'       => array( 'type' => 'integer', 'minimum' => 0 ),
						),
					),
				),
			),
		),
		'execute_callback'    => 'us_mcp_ability_list_docs',
		'permission_callback' => 'us_mcp_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );

	wp_register_ability( 'upsolution/read-doc', array(
		'label'               => 'Read one authoring doc by id',
		'description'         => 'Fetch the full text of one doc. Pass an id returned by upsolution-list-docs (e.g. "composition-rules", "shortcodes", "shortcodes/vc_row", "sections/pr"). Shortcode config-ids: us_* tags drop their prefix (us_btn → "shortcodes/btn"), vc_* tags keep it ("shortcodes/vc_row" — "shortcodes/us_btn" is a 404). Ids copied from in-doc cross-links also resolve — a trailing ".md" / ".txt" extension and "#anchor" are stripped. Returns {id, mime, text}. Unknown ids return 404.',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'       => 'object',
			'required'   => array( 'id' ),
			'properties' => array(
				'id' => array(
					'type'        => 'string',
					'description' => 'Doc id as returned by upsolution-list-docs (e.g. "composition-rules", "shortcodes/btn", "sections/qa").',
					'minLength'   => 1,
				),
			),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'required'   => array( 'id', 'mime', 'text' ),
			'properties' => array(
				'id'   => array( 'type' => 'string' ),
				'mime' => array( 'type' => 'string' ),
				'text' => array( 'type' => 'string' ),
			),
		),
		'execute_callback'    => 'us_mcp_ability_read_doc',
		'permission_callback' => 'us_mcp_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );
} );

/**
 * @return array{docs: array<int, array{id: string, label: string, description: string, mime: string, bytes: int}>}
 */
function us_mcp_ability_list_docs() {
	$docs = array();
	foreach ( us_mcp_docs_manifest() as $id => $spec ) {
		$docs[] = array(
			'id'          => $id,
			'label'       => $spec['label'],
			'description' => $spec['description'],
			'mime'        => $spec['mime'],
			'bytes'       => is_readable( $spec['file'] ) ? (int) filesize( $spec['file'] ) : 0,
		);
	}
	return array( 'docs' => $docs );
}

/**
 * @param array $input
 * @return array|WP_Error
 */
function us_mcp_ability_read_doc( $input ) {
	$input = (array) $input;
	$id = isset( $input['id'] ) ? trim( (string) $input['id'] ) : '';

	if ( $id === '' ) {
		return new WP_Error(
			'us_mcp_docs_missing_id',
			'Provide a doc `id` argument (call upsolution-list-docs to see available ids).',
			array( 'status' => 400 )
		);
	}

	// Normalize ids copied from markdown cross-links: docs link to each other
	// as relative files ("composition-rules.md#31-link-picker-link",
	// "sections/pr.md"), while manifest keys are extension-less ids. Strip a
	// "#anchor" suffix, a ".md" / ".txt" extension and a leading "content/" so
	// either form resolves to the same doc.
	$id = preg_replace( '~#.*$~', '', $id );
	$id = preg_replace( '~\.(md|txt)$~i', '', $id );
	$id = preg_replace( '~^content/~', '', $id );
	$id = trim( $id, '/' );

	$manifest = us_mcp_docs_manifest();
	if ( ! isset( $manifest[ $id ] ) ) {
		return new WP_Error(
			'us_mcp_docs_unknown_id',
			sprintf( 'Unknown doc id "%s". Call upsolution-list-docs to see available ids.', $id ),
			array( 'status' => 404 )
		);
	}

	$spec = $manifest[ $id ];
	if ( ! is_readable( $spec['file'] ) ) {
		return new WP_Error(
			'us_mcp_docs_unreadable',
			sprintf( 'Doc "%s" is registered but its file is not readable on disk.', $id ),
			array( 'status' => 500 )
		);
	}

	// Pre-flight size cap. Refuse the read instead of letting the agent burn
	// its context window in one call — list-docs has the exact `bytes` for
	// every entry, so a caller hitting this can pick a narrower doc.
	$bytes = filesize( $spec['file'] );
	if ( $bytes !== FALSE AND $bytes > US_MCP_DOC_MAX_BYTES ) {
		return new WP_Error(
			'us_mcp_docs_too_large',
			sprintf(
				'Doc "%s" is %d bytes — above the read-doc cap of %d. Inspect via upsolution-list-docs (it returns the size of each entry) and pick a narrower doc, or split this one into per-topic files.',
				$id,
				$bytes,
				US_MCP_DOC_MAX_BYTES
			),
			array( 'status' => 413 )
		);
	}

	$text = file_get_contents( $spec['file'] );
	if ( $text === FALSE ) {
		return new WP_Error(
			'us_mcp_docs_read_failed',
			sprintf( 'Failed to read doc "%s".', $id ),
			array( 'status' => 500 )
		);
	}

	return array(
		'id'   => $id,
		'mime' => $spec['mime'],
		'text' => $text,
	);
}
