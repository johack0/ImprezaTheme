<?php
/**
 * UpSolution MCP — generic post CRUD abilities.
 *
 * Six abilities for editing any of the UpSolution-authored post types via
 * the builder shortcode pipeline:
 *
 *   upsolution-list-posts
 *   upsolution-get-post
 *   upsolution-create-post
 *   upsolution-update-post
 *   upsolution-duplicate-post
 *   upsolution-delete-post
 *
 * Every tool requires a `post_type` argument constrained to the registry in
 * us_mcp_post_types(). For each registered type the registry records which
 * optional payload fields make sense:
 *
 *   - supports_excerpt   — include excerpt in input / output
 *   - supports_thumbnail — accept featured_image_id (= attachment id), return
 *                         featured_image_id and featured_image_url
 *   - taxonomies         — taxonomy slugs whose terms may be assigned via the
 *                         `terms` input and returned in the `terms` output
 *   - meta_whitelist     — meta keys the agent may read / write (used for
 *                         testimonial author fields, Additional Settings, etc.)
 *
 * Per-type capability comes from the registered post type object's `cap`
 * mapping, so testimonials (capability_type=page) require edit_pages, while
 * portfolio (default capability_type=post) requires edit_posts, etc. White-
 * label remapping of template post types is honoured automatically.
 *
 * @package usCore\Mcp
 */

defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Theme-integration meta fields shared across multiple post types in
 * config/meta-boxes.php. Each helper corresponds to one metabox group.
 *
 * Each entry is keyed by the meta key and carries a value-type spec:
 *
 *   type       string  — one of: 'string' / 'int' / 'bool_01' / 'enum' / 'color'.
 *   values     ?array  — for type='enum', the accepted values (as strings).
 *   gradient   ?bool   — for type='color', whether the renderer accepts a
 *                        *-gradient(…) value (defaults to FALSE).
 *
 * Value-type validation runs in us_mcp_validate_meta_value() — see comments
 * there for the per-type semantics. Agents that pass the wrong type get a 400
 * with a precise error rather than a silent admin-side fall through to the
 * default.
 *
 * Composite fields (`type=upload` multi, `type=link` rawurlencode'd JSON) are
 * intentionally excluded from MVP — they need bespoke encoding the agent
 * cannot infer from a JSON schema. Display-only `type=message` fields are
 * skipped too (no real value to read or write).
 *
 * Per-install field availability is gated by Theme Options at runtime
 * (`enable_sidebar_titlebar`, `enable_additional_settings`,
 * `additional_settings_post_types`, `og_enabled`). The MCP whitelist is the
 * UNION across all settings — if Theme Options hides a field in the admin
 * UI, the meta key is still harmless to write.
 *
 * @return array<string, array{type: string, values?: string[], gradient?: bool}>
 */
function us_mcp_meta_group_page_layout() {
	// `us_page_settings` metabox in config/meta-boxes.php — applied to every
	// public post type by `array_keys( $public_post_types )`. Covers Header,
	// Titlebar, Page Template, Sidebar and Footer overrides per post.
	//
	// The *_id keys (us_header_id / us_titlebar_id / us_content_id /
	// us_sidebar_id / us_footer_id) are stored as strings because the admin
	// `select` accepts both numeric ids AND special markers '__defaults__'
	// (inherit from Theme Options) and '0' (none).
	//
	// us_header_sticky / us_header_transparent / us_remove_header_offset are
	// `checkboxes` over the responsive states — stored as a comma-separated
	// string like "default,laptops". String here; the renderer parses it.
	return array(
		'us_header_id'                     => array( 'type' => 'string' ),
		'us_header_sticky_override'        => array( 'type' => 'bool_01' ),
		'us_header_sticky'                 => array( 'type' => 'string' ),
		'us_header_transparent_override'   => array( 'type' => 'bool_01' ),
		'us_header_transparent'            => array( 'type' => 'string' ),
		'us_header_shadow'                 => array( 'type' => 'bool_01' ),
		'us_remove_header_offset_override' => array( 'type' => 'bool_01' ),
		'us_remove_header_offset'          => array( 'type' => 'string' ),
		'us_header_sticky_pos'             => array( 'type' => 'enum', 'values' => array( '', 'bottom', 'above', 'below' ) ),
		'us_titlebar_id'                   => array( 'type' => 'string' ),
		'us_content_id'                    => array( 'type' => 'string' ),
		'us_sidebar_id'                    => array( 'type' => 'string' ),
		'us_sidebar_pos'                   => array( 'type' => 'enum', 'values' => array( 'left', 'right' ) ),
		'us_footer_id'                     => array( 'type' => 'string' ),
	);
}

/**
 * @return array<string, array{type: string, values?: string[], gradient?: bool}>
 */
function us_mcp_meta_group_seo() {
	// `us_seo_settings` metabox — config/seo-meta-fields.php. Applies to
	// every public post type when `og_enabled` is on (default).
	//
	// us_meta_robots / us_meta_itemtype are deliberately free-form strings —
	// the admin UI shows examples but doesn't constrain (a Schema.org type
	// catalogue is huge; robots directives compose with commas).
	return array(
		'us_meta_title'       => array( 'type' => 'string' ),
		'us_meta_description' => array( 'type' => 'string' ),
		'us_meta_robots'      => array( 'type' => 'string' ),
		'us_meta_itemtype'    => array( 'type' => 'string' ),
	);
}

/**
 * @return array<string, array{type: string, values?: string[], gradient?: bool}>
 */
function us_mcp_meta_group_additional_settings() {
	// "Additional Settings" metabox (id `us_portfolio_settings` for legacy
	// reasons). The metabox `post_types` come from the
	// `additional_settings_post_types` theme option, which defaults to ALL
	// public post types — so these keys apply to page / post / us_portfolio
	// (and any custom public type the site adds), not just us_portfolio.
	// us_tile_additional_image (multi-upload) and us_tile_link (composite
	// link JSON) excluded from MVP.
	//
	// us_tile_icon stores the icon ident (e.g. "fab|youtube"); free-form string.
	// us_tile_bg_color / us_tile_text_color go through the same palette color
	// validator as Theme Options pickers — the admin field is type=color with
	// no `with_gradient` flag, so gradients are not accepted.
	return array(
		'us_tile_icon'       => array( 'type' => 'string' ),
		'us_tile_size'       => array( 'type' => 'enum', 'values' => array( '1x1', '2x1', '1x2', '2x2' ) ),
		'us_tile_bg_color'   => array( 'type' => 'color', 'gradient' => TRUE ),
		'us_tile_text_color' => array( 'type' => 'color', 'gradient' => FALSE ),
	);
}

/**
 * @return array<string, array{type: string, values?: string[], gradient?: bool}>
 */
function us_mcp_meta_group_testimonial() {
	// `us_testimonials_settings` metabox — author metadata rendered by the
	// testimonial element. us_testimonial_link (composite link JSON) excluded.
	//
	// us_testimonial_rating is a `radio` field — note "none" is the explicit
	// "no rating" value, NOT an empty string.
	return array(
		'us_testimonial_author'  => array( 'type' => 'string' ),
		'us_testimonial_role'    => array( 'type' => 'string' ),
		'us_testimonial_company' => array( 'type' => 'string' ),
		'us_testimonial_rating'  => array( 'type' => 'enum', 'values' => array( 'none', '1', '2', '3', '4', '5' ) ),
	);
}

/**
 * @return array<string, array{type: string, values?: string[], gradient?: bool}>
 */
function us_mcp_meta_group_content_template_header() {
	// `us_content_template_settings` metabox — transparent-header override
	// applied to posts that render through this Page Template.
	return array(
		'us_header_transparent_override' => array( 'type' => 'bool_01' ),
		'us_header_transparent'          => array( 'type' => 'string' ),
	);
}

/**
 * Registry of post types this server is willing to expose, plus the optional
 * payload fields that apply to each.
 *
 * Keys MUST match real WP post-type slugs. Values:
 *   supports_excerpt   bool
 *   supports_thumbnail bool
 *   taxonomies         string[]  taxonomy slugs available for term assignment
 *   meta_whitelist     array<string, array>  meta keys readable AND writable via MCP,
 *                         each carrying a value-type spec (see us_mcp_meta_group_*).
 *
 * Meta maps are unions of the relevant config/meta-boxes.php groups for
 * each type — see us_mcp_meta_group_* helpers above. Adding a new type here
 * is enough to expose it through every CRUD ability; no other file needs
 * to change.
 *
 * @return array<string, array{supports_excerpt: bool, supports_thumbnail: bool, taxonomies: string[], meta_whitelist: array<string, array>}>
 */
function us_mcp_post_types() {
	// Memoised for the request: the registry is a pure literal (no runtime
	// state — post_type_exists() / capability checks happen in the callers,
	// not here) but it is consulted many times per request (permission gate,
	// every validator, the payload serializer, the registration loops), and
	// each build reassembles three meta-group arrays via array_merge. Cache
	// the assembled result. Safe because PHP returns arrays by value, so a
	// caller mutating its copy can't reach this static.
	static $types = NULL;
	if ( $types !== NULL ) {
		return $types;
	}

	$page_layout         = us_mcp_meta_group_page_layout();
	$seo                 = us_mcp_meta_group_seo();
	$additional_settings = us_mcp_meta_group_additional_settings();

	$types = array(
		'page' => array(
			'supports_excerpt'   => TRUE,
			'supports_thumbnail' => TRUE,
			'taxonomies'         => array(),
			'meta_whitelist'     => array_merge( $page_layout, $seo, $additional_settings ),
		),
		'post' => array(
			'supports_excerpt'   => TRUE,
			'supports_thumbnail' => TRUE,
			'taxonomies'         => array( 'category', 'post_tag' ),
			'meta_whitelist'     => array_merge( $page_layout, $seo, $additional_settings ),
		),
		'us_portfolio' => array(
			'supports_excerpt'   => TRUE,
			'supports_thumbnail' => TRUE,
			'taxonomies'         => array( 'us_portfolio_category', 'us_portfolio_tag' ),
			'meta_whitelist'     => array_merge( $page_layout, $seo, $additional_settings ),
		),
		'us_testimonial' => array(
			'supports_excerpt'   => FALSE,
			// The "featured image" on a testimonial is the author photo.
			'supports_thumbnail' => TRUE,
			'taxonomies'         => array( 'us_testimonial_category' ),
			'meta_whitelist'     => us_mcp_meta_group_testimonial(),
		),
		'us_content_template' => array(
			'supports_excerpt'   => FALSE,
			'supports_thumbnail' => FALSE,
			'taxonomies'         => array(),
			'meta_whitelist'     => us_mcp_meta_group_content_template_header(),
		),
		'us_page_block' => array(
			'supports_excerpt'   => FALSE,
			'supports_thumbnail' => FALSE,
			'taxonomies'         => array(),
			'meta_whitelist'     => array(),
		),
	);

	return $types;
}

/**
 * The enum used in input_schema for every `post_type` field.
 *
 * @return string[]
 */
function us_mcp_post_type_enum() {
	return array_keys( us_mcp_post_types() );
}

// Register the MCP discovery-UI category that every UpSolution ability lives in.
add_action( 'wp_abilities_api_categories_init', function () {
	wp_register_ability_category( 'upsolution', array(
		'label'       => 'UpSolution',
		'description' => 'Post / page / portfolio / testimonial / template authoring and theme inspection for the Impreza / Zephyr themes.',
	) );
} );

add_action( 'wp_abilities_api_init', 'us_mcp_register_post_abilities' );

/**
 * Register the generic CRUD abilities.
 *
 * @return void
 */
function us_mcp_register_post_abilities() {
	$type_enum = us_mcp_post_type_enum();

	// Union of taxonomies across every registered post type — used as the
	// enum on the list-posts `taxonomy` filter, so the agent sees the closed
	// set up front rather than getting a 400 only after the call lands.
	// (Per-post-type validity is checked at runtime in the execute callback.)
	$taxonomy_union = array();
	foreach ( us_mcp_post_types() as $pt_spec ) {
		foreach ( $pt_spec['taxonomies'] as $tax_slug ) {
			$taxonomy_union[ $tax_slug ] = TRUE;
		}
	}
	$taxonomy_enum = array_keys( $taxonomy_union );

	// -----------------------------------------------------------------
	// list-posts
	// -----------------------------------------------------------------
	wp_register_ability( 'upsolution/list-posts', array(
		'label'               => 'List posts of a given type',
		'description'         => 'List posts of the given post_type (page / post / us_portfolio / us_testimonial / us_content_template / us_page_block), optionally filtered by status, free-text search, or a taxonomy term. Returns a compact list of id/slug/title/modified/status/link suitable for picking a record to edit.',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'       => 'object',
			'required'   => array( 'post_type' ),
			'properties' => array(
				'post_type' => array(
					'type'        => 'string',
					'description' => 'Which post type to list. Use upsolution-get-theme-option with name "enable_portfolio" / "enable_testimonials" if you are unsure whether the type is active on this site.',
					'enum'        => $type_enum,
				),
				'status'    => array(
					'type'        => 'string',
					'description' => 'Comma-separated WP post statuses (e.g. "publish,draft", or "future" to list scheduled posts). Default: "publish,draft".',
					'default'     => 'publish,draft',
				),
				'search'    => array(
					'type'        => 'string',
					'description' => 'Optional. Forwarded to WP_Query\'s full-text search (`s`): matches against post_title, post_content AND post_excerpt, and splits the query into space-separated terms required to ALL match. Caveat: post_content holds raw shortcode markup, so a query like "primary" matches every post whose markup contains color="_content_primary", and "row" matches everything with a [vc_row]. For exact lookup of a known title, prefer upsolution-get-post with `slug` (or `id`).',
				),
				'taxonomy'  => array(
					'type'        => 'string',
					'description' => 'Restrict to posts assigned to the given term. Taxonomy slug; the enum lists every taxonomy registered for ANY exposed post type, but the slug must also be registered for the post_type you pass (otherwise 400). See the `post-types` doc via upsolution-read-doc for the per-type matrix.',
					'enum'        => $taxonomy_enum,
				),
				'term_id'   => array(
					'type'        => 'integer',
					'description' => 'Term id to filter by. Required together with `taxonomy`.',
				),
				'per_page'  => array(
					'type'        => 'integer',
					'description' => 'Maximum number of posts to return (default 20, max 100).',
					'default'     => 20,
					'minimum'     => 1,
					'maximum'     => 100,
				),
			),
		),
		'output_schema'       => array(
			'type'  => 'array',
			'items' => array(
				'type'       => 'object',
				'properties' => array(
					'id'        => array( 'type' => 'integer' ),
					'post_type' => array( 'type' => 'string' ),
					'slug'      => array( 'type' => 'string' ),
					'title'     => array( 'type' => 'string' ),
					'modified'  => array( 'type' => array( 'string', 'null' ) ),
					'date'      => array( 'type' => array( 'string', 'null' ) ),
					'status'    => array( 'type' => 'string' ),
					'link'      => array( 'type' => 'string' ),
				),
			),
		),
		'execute_callback'    => 'us_mcp_ability_list_posts',
		'permission_callback' => 'us_mcp_post_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );

	// -----------------------------------------------------------------
	// get-post
	// -----------------------------------------------------------------
	wp_register_ability( 'upsolution/get-post', array(
		'label'               => 'Get a single post',
		'description'         => 'Fetch the full record for one post of the given type. Returns the shortcode `content`, plus excerpt / featured_image_id / featured_image_url / terms / meta when the type supports them. Pass either `id` or `slug` together with `post_type` (slugs can collide between types, so post_type is required).',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'       => 'object',
			'required'   => array( 'post_type' ),
			'properties' => array(
				'post_type' => array( 'type' => 'string', 'enum' => $type_enum ),
				'id'        => array( 'type' => 'integer', 'description' => 'Post id. Either id or slug is required.' ),
				'slug'      => array( 'type' => 'string',  'description' => 'Single-segment post slug (no slashes) for non-hierarchical types (post / us_portfolio / us_testimonial / us_content_template / us_page_block). For `page` the resolver walks the page hierarchy, so a "parent/child" path is accepted too. Either id or slug is required.' ),
			),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'properties' => us_mcp_post_output_properties( /* include_content */ TRUE ),
		),
		'execute_callback'    => 'us_mcp_ability_get_post',
		'permission_callback' => 'us_mcp_post_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );

	// -----------------------------------------------------------------
	// create-post
	// -----------------------------------------------------------------
	wp_register_ability( 'upsolution/create-post', array(
		'label'               => 'Create a post',
		'description'         => 'Create a new post of the given type with UpSolution shortcode markup. Defaults to status "publish"; to schedule it for future publication pass a future `date` (ISO 8601) — WordPress sets the status to "future" and publishes it automatically at that time. By default the response is a small status stub (id/title/status/modified/link/...); pass return_content=true to echo the saved post_content back. Authoring rules and the required pre-read docs are in the server instructions; per-element design (color / spacing / borders / typography) goes in the css="..." attribute as URL-encoded JSON in DOUBLE quotes, color_scheme is a fixed enum only. Optional fields (excerpt / featured_image_id / terms / meta) are silently ignored when the chosen post_type does not support them — pull the `post-types` doc via upsolution-read-doc for the per-type matrix.',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'       => 'object',
			'required'   => array( 'post_type', 'title' ),
			'properties' => us_mcp_post_input_properties( /* for_create */ TRUE, $type_enum ),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'properties' => us_mcp_post_output_properties( /* include_content */ FALSE ),
		),
		'execute_callback'    => 'us_mcp_ability_create_post',
		'permission_callback' => 'us_mcp_post_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );

	// -----------------------------------------------------------------
	// update-post
	// -----------------------------------------------------------------
	wp_register_ability( 'upsolution/update-post', array(
		'label'               => 'Update a post',
		'description'         => 'Update an existing post of the given type. Lookup is by id or slug; any of title/new_slug/status/date/content/excerpt/featured_image_id/terms/meta may be changed. Omitted fields are left untouched. A future `date` (ISO 8601) schedules the post for later publication (status becomes "future"); a past `date` back-dates it. By default the response is a small status stub (id/title/status/modified/link/...); pass return_content=true to echo the saved post_content back. Authoring rules and the required pre-read docs are in the server instructions; per-element design goes in css="..." as URL-encoded JSON in DOUBLE quotes.',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'       => 'object',
			'required'   => array( 'post_type' ),
			'properties' => us_mcp_post_input_properties( /* for_create */ FALSE, $type_enum ),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'properties' => array_merge(
				us_mcp_post_output_properties( /* include_content */ FALSE ),
				array(
					'patches_applied' => array(
						'type'        => 'array',
						'description' => 'Present when `patches` was used. Per-patch report of what was applied.',
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'index'        => array( 'type' => 'integer' ),
								'matches'      => array( 'type' => 'integer' ),
								'bytes_before' => array( 'type' => 'integer' ),
								'bytes_after'  => array( 'type' => 'integer' ),
							),
						),
					),
					'content_bytes'   => array(
						'type'        => 'integer',
						'description' => 'Present when `patches` was used. Size of the resulting post_content in bytes — lets callers track page size without echoing the body.',
					),
				)
			),
		),
		'execute_callback'    => 'us_mcp_ability_update_post',
		'permission_callback' => 'us_mcp_post_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );

	// -----------------------------------------------------------------
	// duplicate-post
	// -----------------------------------------------------------------
	wp_register_ability( 'upsolution/duplicate-post', array(
		'label'               => 'Duplicate a post',
		'description'         => 'Server-side copy of an existing post of the given type in one call, without reading it first. Clones post_content, excerpt, page attributes (parent / menu order / comment + ping status), the post author, EVERY taxonomy term, the featured image, and ALL custom meta (builder data, page / SEO settings, custom fields). The copy defaults to status "draft" and a "<title> (copy)" title so a duplicate never goes live unexpectedly; override title / new_slug / status / date as needed. WordPress-internal bookkeeping meta (edit locks, old-slug / old-date history, trash markers) is intentionally not copied. Returns the new post stub plus source_id; pass return_content=true to echo the copied body.',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'       => 'object',
			'required'   => array( 'post_type' ),
			'properties' => array(
				'post_type' => array( 'type' => 'string', 'enum' => $type_enum ),
				'id'        => array( 'type' => 'integer', 'description' => 'Source post id. Either id or slug is required.' ),
				'slug'      => array( 'type' => 'string',  'description' => 'Source post slug — single segment (no slashes) for non-hierarchical types; `page` accepts a hierarchical "parent/child" path. Either id or slug is required.' ),
				'title'     => array( 'type' => 'string',  'description' => 'Title for the copy. Default: the source title with " (copy)" appended.' ),
				'new_slug'  => array( 'type' => 'string',  'description' => 'Slug for the copy — single segment, no slashes. Default: derived from the title by WordPress and kept unique.' ),
				'status'    => array(
					'type'        => 'string',
					'enum'        => array( 'publish', 'draft', 'private', 'pending', 'future' ),
					'default'     => 'draft',
					'description' => 'Status of the copy. Default "draft" so duplicates do not publish themselves. "future" + a future `date` schedules it.',
				),
				'date'      => array(
					'type'        => 'string',
					'description' => 'Publication date/time for the copy as an ISO 8601 string (same forms as create-post / update-post). A future value schedules the copy; omit to use the current time.',
				),
				'return_content' => array(
					'type'        => 'boolean',
					'description' => 'Echo the copied post_content back in the response. Default false.',
					'default'     => FALSE,
				),
			),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'properties' => array_merge(
				us_mcp_post_output_properties( /* include_content */ FALSE ),
				array(
					'source_id' => array( 'type' => 'integer', 'description' => 'Id of the post that was copied.' ),
				)
			),
		),
		'execute_callback'    => 'us_mcp_ability_duplicate_post',
		'permission_callback' => 'us_mcp_post_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );

	// -----------------------------------------------------------------
	// delete-post
	// -----------------------------------------------------------------
	wp_register_ability( 'upsolution/delete-post', array(
		'label'               => 'Move a post to the trash',
		'description'         => 'Move a post of the given type to the trash. This is the only deletion this tool performs — permanent deletion is intentionally NOT available via MCP, so a removed post can always be restored from wp-admin (Trash). Calling it on an already-trashed post is a no-op that still reports trashed=true (idempotent — safe to retry).',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'       => 'object',
			'required'   => array( 'post_type' ),
			'properties' => array(
				'post_type' => array( 'type' => 'string', 'enum' => $type_enum ),
				'id'        => array( 'type' => 'integer', 'description' => 'Post id. Either id or slug is required.' ),
				'slug'      => array( 'type' => 'string',  'description' => 'Single-segment post slug (no slashes) for non-hierarchical types; `page` accepts hierarchical "parent/child" paths. Either id or slug is required.' ),
			),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'properties' => array(
				'id'      => array( 'type' => 'integer' ),
				'deleted' => array( 'type' => 'boolean', 'description' => 'Always false — this tool never permanently deletes. Kept for response-shape stability.' ),
				'trashed' => array( 'type' => 'boolean', 'description' => 'True once the post is in the trash (or already was).' ),
			),
		),
		'execute_callback'    => 'us_mcp_ability_delete_post',
		'permission_callback' => 'us_mcp_post_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );
}

// ---------------------------------------------------------------------
// Schema helpers
// ---------------------------------------------------------------------

/**
 * Properties for the get/create/update response. Marker fields that only
 * make sense for some types (excerpt, featured_image_*, terms, meta) are
 * declared at top level — agents inspecting the schema see what *can* be
 * returned; runtime payloads only populate them when the actual type
 * supports the field.
 *
 * @param bool $include_content Whether `content` (the shortcode body) is part of the response.
 * @return array<string, array>
 */
function us_mcp_post_output_properties( $include_content ) {
	$props = array(
		'id'        => array( 'type' => 'integer' ),
		'post_type' => array( 'type' => 'string' ),
		'slug'      => array( 'type' => 'string' ),
		'title'     => array( 'type' => 'string' ),
		'status'    => array( 'type' => 'string' ),
		'modified'  => array(
			'type'        => array( 'string', 'null' ),
			'description' => 'ISO 8601 GMT timestamp, or null in the rare case the post has no valid modification or insertion timestamp on record.',
		),
		'date'      => array(
			'type'        => array( 'string', 'null' ),
			'description' => 'ISO 8601 GMT publication date. For a scheduled post (status "future") this is when it will go live; null when the post has no valid date on record.',
		),
		'link'      => array( 'type' => 'string' ),
		'edit_link' => array( 'type' => 'string' ),
	);
	if ( $include_content ) {
		$props['content']             = array( 'type' => 'string' );
		$props['excerpt']             = array( 'type' => 'string', 'description' => 'Present only when the type supports excerpts (post, page, us_portfolio).' );
		$props['featured_image_id']   = array( 'type' => array( 'integer', 'null' ), 'description' => 'Attachment id, or null when no thumbnail set. Present only when the type supports thumbnails.' );
		$props['featured_image_url']  = array( 'type' => array( 'string', 'null' ), 'description' => 'Full-size attachment URL. For us_testimonial this is the author photo.' );
		$props['terms']               = array(
			'type'        => 'object',
			'description' => 'Map of taxonomy slug → [{id, name, slug}]. Only taxonomies registered for the post type appear.',
		);
		$props['meta']                = array(
			'type'        => 'object',
			'description' => 'Whitelisted meta keys for the post type (e.g. us_testimonial_author / us_tile_icon).',
		);
	}
	return $props;
}

/**
 * Properties for create-post / update-post input schemas. `for_create` adds
 * the `title` requirement implicitly (handled by the caller's `required` list).
 *
 * @param bool $for_create
 * @param string[] $type_enum
 * @return array<string, array>
 */
function us_mcp_post_input_properties( $for_create, $type_enum ) {
	$props = array(
		'post_type' => array( 'type' => 'string', 'enum' => $type_enum ),
	);
	if ( $for_create ) {
		$props['title']   = array( 'type' => 'string', 'description' => 'Human-readable title.' );
		$props['slug']    = array( 'type' => 'string', 'description' => 'Optional lowercase-hyphenated slug — a single segment, no slashes (wp_insert_post stores it as post_name and the page hierarchy is set separately via post_parent, which this tool does not expose). Derived from the title if omitted.' );
		$props['status']  = array(
			'type'        => 'string',
			'enum'        => array( 'publish', 'draft', 'private', 'pending', 'future' ),
			'default'     => 'publish',
			'description' => 'Post status. Use "future" together with a future `date` to schedule the post for later publication — WordPress publishes it automatically at that time. (Passing a future `date` with status "publish" schedules it too.)',
		);
		$props['content'] = array( 'type' => 'string', 'description' => 'Body — UpSolution shortcode markup (e.g. [vc_row]...[/vc_row]).' );
	} else {
		$props['id']       = array( 'type' => 'integer', 'description' => 'Post id. Either id or slug is required.' );
		$props['slug']     = array( 'type' => 'string',  'description' => 'Single-segment post slug for non-hierarchical types; `page` accepts hierarchical "parent/child" paths (the resolver walks the page tree). Either id or slug is required.' );
		$props['title']    = array( 'type' => 'string' );
		$props['new_slug'] = array( 'type' => 'string', 'description' => 'New slug — single segment, no slashes. Renames the post_name only; the page parent / hierarchy is not changed by this tool.' );
		$props['status']   = array(
			'type'        => 'string',
			'enum'        => array( 'publish', 'draft', 'private', 'pending', 'future' ),
			'description' => 'Post status. "future" + a future `date` schedules publication (the post auto-publishes at that time). Omit to leave the status unchanged.',
		);
		$props['content']  = array( 'type' => 'string' );
		$props['patches']  = array(
			'type'        => 'array',
			'description' => 'Sequential literal find/replace patches applied to post_content. Byte-exact match (not regex), case-sensitive. Each patch must occur exactly `expect_matches` times in the current content or the WHOLE call is rejected with no DB write (atomic). Patches are applied in array order; later patches see the result of earlier ones. Mutually exclusive with `content` — pass one or the other. Use this instead of resending the whole body for small edits.',
			'maxItems'    => 50,
			'items'       => array(
				'type'       => 'object',
				'required'   => array( 'find', 'replace' ),
				'properties' => array(
					'find'           => array(
						'type'        => 'string',
						'minLength'   => 1,
						'description' => 'Literal byte-exact substring to find in the raw post_content (not regex, case-sensitive). Mind UpSolution attribute encoding: link= JSON, css= JSON, and group attributes (items= / responsive= / tax_query= / …) are stored URL-encoded in double quotes — match `link="%7B%22url%22%3A%22%2Fsignup%22%7D"`, `css="%7B%22color%22%3A..."`, NOT the decoded form. Content saved before us-core 8.16 may still carry the legacy pipe form (`link="url:%2Fsignup"`) — match the bytes actually present. JSON `\\"` is one level of escaping — in the matched content it is a single literal `"`. The body may also contain hand-edited single-quoted attribute values; match the exact bytes present rather than what you would emit from scratch.',
					),
					'replace'        => array(
						'type'        => 'string',
						'description' => 'Replacement (empty string deletes the matched range).',
					),
					'expect_matches' => array(
						'type'        => 'integer',
						'minimum'     => 1,
						'default'     => 1,
						'description' => 'Number of times `find` must occur in the current post_content before this patch is applied. Default 1 — asserts uniqueness. Set higher for an intentional bulk replace.',
					),
				),
			),
		);
	}
	$props['date'] = array(
		'type'        => 'string',
		'description' => 'Intended publication date/time as an ISO 8601 string. A value WITH an explicit offset or trailing "Z" (e.g. "2026-07-01T09:00:00+03:00", "2026-07-01T06:00:00Z") is read as an absolute instant; a bare datetime WITHOUT an offset (e.g. "2026-07-01 09:00:00", "2026-07-01") is read as the site\'s local time. A FUTURE value schedules the post for later publication — WordPress sets the status to "future" and publishes it automatically at that time (you may also set status "future" explicitly). A PAST value back-dates the post. Omit to publish now (create) or keep the existing date (update).',
	);
	$props['return_content'] = array(
		'type'        => 'boolean',
		'description' => 'Echo the saved post_content back in the response. Default false — most callers do not need the body returned after a successful write; check `modified` to confirm the write landed.',
		'default'     => FALSE,
	);
	$props['excerpt']           = array( 'type' => 'string', 'description' => 'Plain-text excerpt. Ignored for types that do not support excerpts.' );
	$props['featured_image_id'] = array(
		'type'        => array( 'integer', 'null' ),
		'description' => 'Attachment id to use as the featured image. Pass null to clear an existing thumbnail. Ignored for types without thumbnail support. For us_testimonial this sets the author photo.',
	);
	$props['terms']             = array(
		'type'                 => 'object',
		'description'          => 'Map of taxonomy slug → array of term ids to assign. Only taxonomies registered for the post type are accepted; unknown keys return 400. Use upsolution-list-terms to resolve names to ids.',
		'additionalProperties' => array(
			'type'  => 'array',
			'items' => array( 'type' => 'integer' ),
		),
	);
	$props['meta']              = array(
		'type'                 => 'object',
		'description'          => 'Map of whitelisted meta key → scalar value. Values are type-checked against the per-key spec: bool fields accept TRUE/FALSE/0/1 (normalised to 0|1), enum fields require a value from the allowed set (e.g. us_testimonial_rating ∈ {none,1,2,3,4,5}; us_tile_size ∈ {1x1,2x1,1x2,2x2}; us_sidebar_pos ∈ {left,right}; us_header_sticky_pos ∈ {"",bottom,above,below}), color fields use the same syntax upsolution-set-palette accepts (hex / rgba / linear-gradient / palette token `_slug` — hsl/hsla and non-linear gradients are NOT supported). Pass null (or "") to clear a key: the stored meta is DELETED and the post reverts to Theme Options / defaults for that setting. For the page-layout `*_id` keys (us_header_id / us_titlebar_id / us_content_id / us_sidebar_id / us_footer_id) clearing means "use defaults" — to hide the area instead, send "0"; full value-semantics table in the `post-types` doc (upsolution-read-doc). Unknown keys return 400; bad value types return 400.',
		'additionalProperties' => array( 'type' => array( 'string', 'number', 'boolean', 'null' ) ),
	);
	return $props;
}

// ---------------------------------------------------------------------
// Permission callback
// ---------------------------------------------------------------------

/**
 * Permission callback for every generic post ability. Reads `post_type` from
 * the input and consults the registered post-type object's capability map,
 * so capability_type=page (us_testimonial), default `post` (us_portfolio),
 * and the white-label-aware caps on us_content_template / us_page_block are
 * all honoured without hardcoding.
 *
 * For mutating operations the per-post check happens inside the execute
 * callback after the post has been resolved (we cannot resolve id/slug here
 * without doing the work twice). This callback gates broad type access.
 *
 * @param array|object $input
 * @return bool
 */
function us_mcp_post_permission_callback( $input ) {
	$input     = (array) $input;
	$post_type = isset( $input['post_type'] ) ? (string) $input['post_type'] : '';
	if ( $post_type === '' OR ! isset( us_mcp_post_types()[ $post_type ] ) ) {
		// Caught later by execute callback with a clearer error message;
		// here we just reject obvious bad input at the transport layer.
		return current_user_can( 'edit_posts' );
	}
	if ( ! post_type_exists( $post_type ) ) {
		return FALSE;
	}
	$pt_obj = get_post_type_object( $post_type );
	if ( ! $pt_obj OR ! isset( $pt_obj->cap->edit_posts ) ) {
		return FALSE;
	}
	return current_user_can( $pt_obj->cap->edit_posts );
}

// ---------------------------------------------------------------------
// Execute helpers
// ---------------------------------------------------------------------

/**
 * Validate post_type input. Returns a normalised slug or WP_Error.
 *
 * @param array $input
 * @return string|WP_Error
 */
function us_mcp_validate_post_type( array $input ) {
	$type = isset( $input['post_type'] ) ? (string) $input['post_type'] : '';
	$registry = us_mcp_post_types();
	if ( $type === '' ) {
		return new WP_Error( 'us_mcp_posts_missing_post_type', 'Provide post_type.', array( 'status' => 400 ) );
	}
	if ( ! isset( $registry[ $type ] ) ) {
		return new WP_Error(
			'us_mcp_posts_unsupported_post_type',
			sprintf( 'post_type "%s" is not exposed via MCP. Supported: %s.', $type, implode( ', ', array_keys( $registry ) ) ),
			array( 'status' => 400 )
		);
	}
	if ( ! post_type_exists( $type ) ) {
		// e.g. us_portfolio with enable_portfolio=0
		return new WP_Error(
			'us_mcp_posts_post_type_disabled',
			sprintf( 'post_type "%s" is not enabled on this site (check Theme Options).', $type ),
			array( 'status' => 400 )
		);
	}
	return $type;
}

/**
 * Resolve an `id`/`slug` input pair to a post of the given type.
 *
 * @param string $post_type
 * @param array $input
 * @return WP_Post|WP_Error
 */
function us_mcp_resolve_post( $post_type, array $input ) {
	$id   = isset( $input['id'] )   ? (int) $input['id']    : 0;
	$slug = isset( $input['slug'] ) ? (string) $input['slug'] : '';

	if ( ! $id AND $slug === '' ) {
		return new WP_Error( 'us_mcp_posts_missing_identifier', 'Provide either id or slug.', array( 'status' => 400 ) );
	}

	if ( ! $id AND $slug !== '' ) {
		// get_page_by_path works for any post type, not just `page`.
		$post = get_page_by_path( $slug, OBJECT, $post_type );
		if ( ! $post ) {
			return new WP_Error(
				'us_mcp_posts_post_not_found',
				sprintf( 'No %s with slug "%s".', $post_type, $slug ),
				array( 'status' => 404 )
			);
		}
		$id = (int) $post->ID;
	}

	$post = get_post( $id );
	if ( ! $post OR $post->post_type !== $post_type ) {
		return new WP_Error(
			'us_mcp_posts_post_not_found',
			sprintf( 'No %s with id %d.', $post_type, $id ),
			array( 'status' => 404 )
		);
	}
	return $post;
}

/**
 * Per-post capability check. Returns TRUE or a WP_Error with 403.
 *
 * @param string $cap
 * @param int $post_id
 * @return true|WP_Error
 */
function us_mcp_require_cap( $cap, $post_id ) {
	if ( current_user_can( $cap, $post_id ) ) {
		return TRUE;
	}
	return new WP_Error( 'us_mcp_posts_forbidden', sprintf( 'You do not have permission to %s this post.', $cap ), array( 'status' => 403 ) );
}

/**
 * Format a GMT timestamp (with a local-time fallback) as an ISO 8601 string,
 * or NULL when both are the zeroed sentinel. Shared by the `modified` and
 * `date` payload fields.
 *
 * wp_insert_post() leaves the *_gmt column = '0000-00-00 00:00:00' for posts
 * inserted with a non-publish status (it mirrors post_date_gmt, which WP
 * intentionally zeroes for drafts / pending / auto-drafts). In that case fall
 * back to the local-time column (always set on insert), converted to GMT via
 * get_gmt_from_date(); if both are zeroed return NULL (shouldn't happen in
 * practice but keeps the contract explicit).
 *
 * The stored value is interpreted explicitly as UTC and emitted as a true GMT
 * ISO 8601 string ("...+00:00") regardless of the site's configured timezone —
 * mysql2date() would read it in the site timezone and mislabel the offset on a
 * non-UTC install.
 *
 * @param string $gmt    A *_gmt column value (post_date_gmt / post_modified_gmt).
 * @param string $local  The matching local-time column (post_date / post_modified).
 * @return string|null
 */
function us_mcp_format_gmt_date( $gmt, $local ) {
	$gmt = (string) $gmt;
	if ( $gmt === '' OR $gmt === '0000-00-00 00:00:00' ) {
		$local = (string) $local;
		if ( $local === '' OR $local === '0000-00-00 00:00:00' ) {
			return NULL;
		}
		$gmt = get_gmt_from_date( $local );
	}
	try {
		return ( new DateTimeImmutable( $gmt, new DateTimeZone( 'UTC' ) ) )->format( 'c' );
	} catch ( Exception $e ) {
		return NULL;
	}
}

/**
 * Parse the optional `date` input (an intended publication date) into the
 * post_date / post_date_gmt pair WordPress stores.
 *
 * Accepted forms:
 *   - ISO 8601 WITH an explicit offset or trailing "Z"
 *     ("2026-07-01T09:00:00+03:00", "2026-07-01T06:00:00Z") — read as an
 *     absolute instant, then split into the site-local post_date and the UTC
 *     post_date_gmt.
 *   - A bare datetime WITHOUT an offset ("2026-07-01 09:00:00",
 *     "2026-07-01T09:00:00") — read as SITE-LOCAL wall-clock time (the
 *     WordPress convention), then converted to GMT.
 *   - A date-only value ("2026-07-01") — treated as local midnight.
 *
 * @param mixed $value
 * @return array{post_date: string, post_date_gmt: string}|WP_Error
 */
function us_mcp_parse_date_input( $value ) {
	if ( ! is_scalar( $value ) ) {
		return new WP_Error( 'us_mcp_posts_bad_date', '`date` must be an ISO 8601 datetime string.', array( 'status' => 400 ) );
	}
	$raw = trim( (string) $value );
	if ( $raw === '' ) {
		return new WP_Error( 'us_mcp_posts_bad_date', '`date` must not be empty.', array( 'status' => 400 ) );
	}

	// Does the string carry its own UTC offset / "Z"? If so it is an absolute
	// instant; otherwise it is wall-clock time in the site timezone.
	$has_offset = (bool) preg_match( '~(?:Z|[+-]\d{2}:?\d{2})$~', $raw );

	try {
		if ( $has_offset ) {
			$gmt = ( new DateTimeImmutable( $raw ) )->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
			return array(
				'post_date'     => get_date_from_gmt( $gmt ),
				'post_date_gmt' => $gmt,
			);
		}
		// No offset: a wall-clock value in the site timezone. Normalise it to
		// "Y-m-d H:i:s" (this also validates it) and derive GMT from there.
		$local = ( new DateTimeImmutable( $raw ) )->format( 'Y-m-d H:i:s' );
		return array(
			'post_date'     => $local,
			'post_date_gmt' => get_gmt_from_date( $local ),
		);
	} catch ( Exception $e ) {
		return new WP_Error(
			'us_mcp_posts_bad_date',
			sprintf( 'Could not parse `date` "%s" as an ISO 8601 datetime.', $raw ),
			array( 'status' => 400 )
		);
	}
}

/**
 * Serialize a post into the response shape used by get/create/update.
 *
 * @param WP_Post $post
 * @param bool $include_content
 * @return array
 */
function us_mcp_post_to_payload( WP_Post $post, $include_content = FALSE ) {
	$registry = us_mcp_post_types();
	$spec     = isset( $registry[ $post->post_type ] ) ? $registry[ $post->post_type ] : NULL;

	$out = array(
		'id'        => (int) $post->ID,
		'post_type' => $post->post_type,
		'slug'      => $post->post_name,
		'title'     => $post->post_title,
		'status'    => $post->post_status,
		'modified'  => us_mcp_format_gmt_date( $post->post_modified_gmt, $post->post_modified ),
		'date'      => us_mcp_format_gmt_date( $post->post_date_gmt, $post->post_date ),
		'link'      => get_permalink( $post ),
		'edit_link' => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
	);

	if ( ! $include_content OR ! $spec ) {
		return $out;
	}

	$out['content'] = $post->post_content;

	if ( $spec['supports_excerpt'] ) {
		$out['excerpt'] = $post->post_excerpt;
	}
	if ( $spec['supports_thumbnail'] ) {
		$thumb_id = (int) get_post_thumbnail_id( $post );
		$out['featured_image_id']  = $thumb_id ?: NULL;
		$out['featured_image_url'] = $thumb_id ? wp_get_attachment_url( $thumb_id ) : NULL;
	}
	if ( ! empty( $spec['taxonomies'] ) ) {
		$terms_out = array();
		foreach ( $spec['taxonomies'] as $tax ) {
			$terms = get_the_terms( $post, $tax );
			if ( is_wp_error( $terms ) OR empty( $terms ) ) {
				$terms_out[ $tax ] = array();
				continue;
			}
			$terms_out[ $tax ] = array_map( function ( $t ) {
				return array( 'id' => (int) $t->term_id, 'name' => $t->name, 'slug' => $t->slug );
			}, $terms );
		}
		$out['terms'] = $terms_out;
	}
	if ( ! empty( $spec['meta_whitelist'] ) ) {
		$meta_out = array();
		foreach ( array_keys( $spec['meta_whitelist'] ) as $key ) {
			$meta_out[ $key ] = get_post_meta( $post->ID, $key, TRUE );
		}
		$out['meta'] = $meta_out;
	}

	return $out;
}

/**
 * Validate / apply the optional `terms` input. Returns null on no-op, an
 * array of [taxonomy => int[]] to assign, or WP_Error on bad input.
 *
 * @param array $input
 * @param array $spec   Registry entry for the post type.
 * @return array|null|WP_Error
 */
function us_mcp_validate_terms_input( array $input, array $spec ) {
	if ( ! array_key_exists( 'terms', $input ) ) {
		return NULL;
	}
	$terms = $input['terms'];
	if ( ! is_array( $terms ) ) {
		return new WP_Error( 'us_mcp_posts_bad_terms', 'terms must be an object of {taxonomy: [term_id, ...]}.', array( 'status' => 400 ) );
	}
	$allowed = $spec['taxonomies'];
	$out = array();
	foreach ( $terms as $tax => $ids ) {
		if ( ! in_array( $tax, $allowed, TRUE ) ) {
			return new WP_Error(
				'us_mcp_posts_bad_terms',
				sprintf( 'Taxonomy "%s" is not registered for this post type. Allowed: %s.', $tax, $allowed ? implode( ', ', $allowed ) : '(none)' ),
				array( 'status' => 400 )
			);
		}
		if ( ! is_array( $ids ) ) {
			return new WP_Error( 'us_mcp_posts_bad_terms', sprintf( 'terms["%s"] must be an array of integer term ids.', $tax ), array( 'status' => 400 ) );
		}
		$out[ $tax ] = array_values( array_map( 'intval', $ids ) );
	}
	return $out;
}

/**
 * Validate one supplied meta value against its per-key spec from the registry.
 * Returns the normalised value ready for update_post_meta(), or a WP_Error.
 *
 * Per-type semantics:
 *   string  — coerce any scalar to string (booleans become '1' / '').
 *   int     — accept int or numeric string; reject bool (FALSE != 0 here).
 *             Empty string is preserved (lets the caller "clear" without
 *             switching to null).
 *   bool_01 — accept TRUE/FALSE/0/1/'0'/'1'/''; normalise to int 0 or 1, the
 *             same shape the admin `switch` field stores.
 *   enum    — coerce to string and require it appears in `spec['values']`.
 *   color   — delegate to us_mcp_palette_check_color() — same syntaxes that
 *             work in upsolution-set-palette work here. `spec['gradient']`
 *             gates gradient acceptance.
 *
 * A missing spec falls back to a generic scalar accept — safer than rejecting,
 * since the registry is the single source of truth.
 *
 * @param string $key
 * @param mixed  $value   Already known to be scalar (caller filters arrays / objects).
 * @param array  $spec    {type: …, values?: …, gradient?: …}
 * @return string|int|WP_Error
 */
function us_mcp_validate_meta_value( $key, $value, array $spec ) {
	$type = isset( $spec['type'] ) ? (string) $spec['type'] : 'string';

	switch ( $type ) {
		case 'int':
			if ( $value === '' ) {
				return '';
			}
			if ( is_bool( $value ) OR ! is_numeric( $value ) ) {
				return new WP_Error(
					'us_mcp_posts_bad_meta',
					sprintf( 'Meta "%s" must be an integer (got %s).', $key, is_scalar( $value ) ? var_export( $value, TRUE ) : gettype( $value ) ),
					array( 'status' => 400 )
				);
			}
			return (int) $value;

		case 'bool_01':
			if ( $value === '' OR $value === FALSE OR $value === 0 OR $value === '0' ) {
				return 0;
			}
			if ( $value === TRUE OR $value === 1 OR $value === '1' ) {
				return 1;
			}
			return new WP_Error(
				'us_mcp_posts_bad_meta',
				sprintf( 'Meta "%s" must be a boolean / 0 / 1 (got %s).', $key, var_export( $value, TRUE ) ),
				array( 'status' => 400 )
			);

		case 'enum':
			$values  = isset( $spec['values'] ) ? (array) $spec['values'] : array();
			$coerced = is_bool( $value ) ? ( $value ? '1' : '' ) : (string) $value;
			if ( ! in_array( $coerced, $values, TRUE ) ) {
				return new WP_Error(
					'us_mcp_posts_bad_meta',
					sprintf( 'Meta "%s" must be one of: %s (got "%s").', $key, implode( ', ', array_map( function ( $v ) { return $v === '' ? '""' : $v; }, $values ) ), $coerced ),
					array( 'status' => 400 )
				);
			}
			return $coerced;

		case 'color':
			$allows_gradient = ! empty( $spec['gradient'] );
			if ( ! function_exists( 'us_mcp_palette_check_color' ) ) {
				// Defensive — palette validator ships in the same package and
				// is required by the bootstrap. Without it, accept scalars.
				return is_scalar( $value ) ? (string) $value : new WP_Error(
					'us_mcp_posts_bad_meta',
					sprintf( 'Meta "%s" must be a color string.', $key ),
					array( 'status' => 400 )
				);
			}
			return us_mcp_palette_check_color( $value, $allows_gradient, $key );

		case 'string':
		default:
			// Booleans coerce to '1' / '' — same shape admin switch fields use.
			if ( is_bool( $value ) ) {
				return $value ? '1' : '';
			}
			return (string) $value;
	}
}

/**
 * Validate the optional `meta` input against the type's whitelist.
 *
 * @param array $input
 * @param array $spec
 * @return array|null|WP_Error
 */
function us_mcp_validate_meta_input( array $input, array $spec ) {
	if ( ! array_key_exists( 'meta', $input ) ) {
		return NULL;
	}
	$meta = $input['meta'];
	if ( ! is_array( $meta ) ) {
		return new WP_Error( 'us_mcp_posts_bad_meta', 'meta must be an object of {key: value}.', array( 'status' => 400 ) );
	}
	$allowed_specs = $spec['meta_whitelist'];
	$out = array();
	foreach ( $meta as $key => $value ) {
		if ( ! array_key_exists( $key, $allowed_specs ) ) {
			return new WP_Error(
				'us_mcp_posts_bad_meta',
				sprintf( 'Meta key "%s" is not on the whitelist for this post type. Allowed: %s.', $key, $allowed_specs ? implode( ', ', array_keys( $allowed_specs ) ) : '(none)' ),
				array( 'status' => 400 )
			);
		}
		if ( is_array( $value ) OR is_object( $value ) ) {
			return new WP_Error( 'us_mcp_posts_bad_meta', sprintf( 'Meta value for "%s" must be a scalar or null.', $key ), array( 'status' => 400 ) );
		}
		// NULL is the explicit "clear" intent — let the apply step delete the
		// meta key without funnelling NULL through value validation, where
		// per-type validators would reject it.
		if ( $value === NULL ) {
			$out[ $key ] = NULL;
			continue;
		}
		$normalised = us_mcp_validate_meta_value( $key, $value, $allowed_specs[ $key ] );
		if ( is_wp_error( $normalised ) ) {
			return $normalised;
		}
		$out[ $key ] = $normalised;
	}
	return $out;
}

/**
 * Validate the optional extras (terms / meta / featured image) WITHOUT touching
 * the database. Split out from the apply step so create-post / update-post can
 * reject a bad value BEFORE the post write — otherwise wp_insert_post() leaves
 * an orphaned record (and wp_update_post() a half-applied one) when, say, a meta
 * enum or featured_image_id turns out invalid.
 *
 * Returns a normalised directive consumed by us_mcp_apply_post_extras():
 *   terms     => array<taxonomy, int[]> | null   null = field not supplied
 *   meta      => array<key, scalar|null> | null   null = field not supplied
 *   thumbnail => array{action:'clear'} | array{action:'set', id:int} | null
 *
 * @param array $input
 * @param array $spec
 * @return array{terms: ?array, meta: ?array, thumbnail: ?array}|WP_Error
 */
function us_mcp_validate_post_extras( array $input, array $spec ) {
	$terms = us_mcp_validate_terms_input( $input, $spec );
	if ( is_wp_error( $terms ) ) {
		return $terms;
	}

	$meta = us_mcp_validate_meta_input( $input, $spec );
	if ( is_wp_error( $meta ) ) {
		return $meta;
	}

	$thumbnail = NULL;
	if ( array_key_exists( 'featured_image_id', $input ) AND $spec['supports_thumbnail'] ) {
		$thumb = $input['featured_image_id'];
		// All four shapes mean "clear the thumbnail" — null, int 0, the strings
		// "0" and "". Without the '' branch a UI sending an empty string
		// dropped into `(int) '' = 0` and erroneously surfaced as 400
		// "0 is not an attachment".
		if ( $thumb === NULL OR $thumb === 0 OR $thumb === '0' OR $thumb === '' ) {
			$thumbnail = array( 'action' => 'clear' );
		} else {
			$thumb_id = (int) $thumb;
			if ( ! $thumb_id OR get_post_type( $thumb_id ) !== 'attachment' ) {
				return new WP_Error(
					'us_mcp_posts_bad_thumbnail',
					sprintf( 'featured_image_id %d is not an attachment.', $thumb_id ),
					array( 'status' => 400 )
				);
			}
			$thumbnail = array( 'action' => 'set', 'id' => $thumb_id );
		}
	}

	return array(
		'terms'     => is_array( $terms ) ? $terms : NULL,
		'meta'      => is_array( $meta ) ? $meta : NULL,
		'thumbnail' => $thumbnail,
	);
}

/**
 * Apply pre-validated terms / meta / thumbnail to a post. Takes the directive
 * produced by us_mcp_validate_post_extras() — every value here is already known
 * to be well-formed, so the only errors this can surface are storage-level
 * failures (e.g. wp_set_object_terms() hitting a DB error), which legitimately
 * happen after the post write regardless.
 *
 * @param WP_Post $post
 * @param array   $extras  Result of us_mcp_validate_post_extras().
 * @return true|WP_Error
 */
function us_mcp_apply_post_extras( WP_Post $post, array $extras ) {
	// terms
	if ( isset( $extras['terms'] ) AND is_array( $extras['terms'] ) ) {
		foreach ( $extras['terms'] as $tax => $ids ) {
			$res = wp_set_object_terms( $post->ID, $ids, $tax, /* append */ FALSE );
			if ( is_wp_error( $res ) ) {
				return $res;
			}
		}
	}

	// meta
	if ( isset( $extras['meta'] ) AND is_array( $extras['meta'] ) ) {
		foreach ( $extras['meta'] as $key => $value ) {
			if ( $value === NULL OR $value === '' ) {
				delete_post_meta( $post->ID, $key );
			} else {
				update_post_meta( $post->ID, $key, $value );
			}
		}
	}

	// featured image
	if ( ! empty( $extras['thumbnail'] ) AND is_array( $extras['thumbnail'] ) ) {
		if ( $extras['thumbnail']['action'] === 'clear' ) {
			delete_post_thumbnail( $post );
		} else {
			set_post_thumbnail( $post, (int) $extras['thumbnail']['id'] );
		}
	}

	return TRUE;
}

// ---------------------------------------------------------------------
// Execute callbacks
// ---------------------------------------------------------------------

function us_mcp_ability_list_posts( $input ) {
	$input = (array) $input;
	$post_type = us_mcp_validate_post_type( $input );
	if ( is_wp_error( $post_type ) ) {
		return $post_type;
	}

	$status_csv = isset( $input['status'] ) ? (string) $input['status'] : 'publish,draft';
	$statuses   = array_filter( array_map( 'trim', explode( ',', $status_csv ) ) );
	if ( empty( $statuses ) ) {
		$statuses = array( 'publish', 'draft' );
	}
	$per_page = isset( $input['per_page'] ) ? (int) $input['per_page'] : 20;
	$per_page = max( 1, min( 100, $per_page ) );

	$args = array(
		'post_type'      => $post_type,
		'post_status'    => $statuses,
		'posts_per_page' => $per_page,
		'orderby'        => 'modified',
		'order'          => 'DESC',
	);
	if ( isset( $input['search'] ) AND trim( (string) $input['search'] ) !== '' ) {
		$args['s'] = (string) $input['search'];
	}
	if ( ! empty( $input['taxonomy'] ) AND ! empty( $input['term_id'] ) ) {
		$tax     = (string) $input['taxonomy'];
		$term_id = (int) $input['term_id'];
		$spec    = us_mcp_post_types()[ $post_type ];
		if ( ! in_array( $tax, $spec['taxonomies'], TRUE ) ) {
			return new WP_Error(
				'us_mcp_posts_bad_taxonomy',
				sprintf( 'Taxonomy "%s" is not registered for post_type "%s". Allowed: %s.', $tax, $post_type, $spec['taxonomies'] ? implode( ', ', $spec['taxonomies'] ) : '(none)' ),
				array( 'status' => 400 )
			);
		}
		$args['tax_query'] = array(
			array( 'taxonomy' => $tax, 'field' => 'term_id', 'terms' => array( $term_id ) ),
		);
	}

	$query = new WP_Query( $args );
	$out   = array();
	foreach ( $query->posts as $post ) {
		$out[] = array(
			'id'        => (int) $post->ID,
			'post_type' => $post->post_type,
			'slug'      => $post->post_name,
			'title'     => $post->post_title,
			'modified'  => us_mcp_format_gmt_date( $post->post_modified_gmt, $post->post_modified ),
			'date'      => us_mcp_format_gmt_date( $post->post_date_gmt, $post->post_date ),
			'status'    => $post->post_status,
			'link'      => get_permalink( $post ),
		);
	}
	return $out;
}

function us_mcp_ability_get_post( $input ) {
	$input = (array) $input;
	$post_type = us_mcp_validate_post_type( $input );
	if ( is_wp_error( $post_type ) ) {
		return $post_type;
	}
	$post = us_mcp_resolve_post( $post_type, $input );
	if ( is_wp_error( $post ) ) {
		return $post;
	}
	return us_mcp_post_to_payload( $post, /* include_content */ TRUE );
}

function us_mcp_ability_create_post( $input ) {
	$input = (array) $input;
	$post_type = us_mcp_validate_post_type( $input );
	if ( is_wp_error( $post_type ) ) {
		return $post_type;
	}
	$spec = us_mcp_post_types()[ $post_type ];

	// Up-front cap check for create — at this stage there's no post id to
	// gate on, so the broad edit_posts cap of the type is the right one.
	$pt_obj = get_post_type_object( $post_type );
	if ( ! current_user_can( $pt_obj->cap->edit_posts ) ) {
		return new WP_Error( 'us_mcp_posts_forbidden', 'You do not have permission to create this post type.', array( 'status' => 403 ) );
	}

	// Validate the optional extras BEFORE inserting, so a bad terms / meta /
	// featured_image value rejects the call without leaving an orphaned post.
	$extras = us_mcp_validate_post_extras( $input, $spec );
	if ( is_wp_error( $extras ) ) {
		return $extras;
	}

	$args = array(
		'post_type'    => $post_type,
		'post_title'   => isset( $input['title'] )   ? (string) $input['title']   : '',
		'post_content' => isset( $input['content'] ) ? (string) $input['content'] : '',
		'post_status'  => isset( $input['status'] )  ? (string) $input['status']  : 'publish',
	);
	if ( $spec['supports_excerpt'] AND array_key_exists( 'excerpt', $input ) ) {
		$args['post_excerpt'] = (string) $input['excerpt'];
	}
	if ( ! empty( $input['slug'] ) ) {
		$args['post_name'] = sanitize_title( (string) $input['slug'] );
	}

	// Optional scheduled / back-dated publication date. A future date makes
	// WordPress schedule the post (it sets the status to "future" itself); a
	// past date back-dates it. Parse before inserting so a bad value rejects
	// the call without leaving an orphaned post.
	if ( array_key_exists( 'date', $input ) AND trim( (string) $input['date'] ) !== '' ) {
		$date = us_mcp_parse_date_input( $input['date'] );
		if ( is_wp_error( $date ) ) {
			return $date;
		}
		$args['post_date']     = $date['post_date'];
		$args['post_date_gmt'] = $date['post_date_gmt'];
	}

	$id = wp_insert_post( $args, /* wp_error */ TRUE );
	if ( is_wp_error( $id ) ) {
		return $id;
	}

	$post = get_post( $id );

	$applied = us_mcp_apply_post_extras( $post, $extras );
	if ( is_wp_error( $applied ) ) {
		return $applied;
	}

	// Re-fetch so the payload reflects post-meta / post-term changes.
	$post = get_post( $id );
	return us_mcp_post_to_payload( $post, /* include_content */ ! empty( $input['return_content'] ) );
}

/**
 * When a patch fails to match, probe a few plausible encoding variants of the
 * caller's `find` string. If any of them matches the current content, return
 * a short hint identifying which transform turns the caller's input into a
 * real substring of the body. Returns '' when nothing helpful is found.
 *
 * Why bother: the model has to track two stacked encodings — JSON for the
 * MCP transport (\" inside "..."), AND UpSolution's own per-attribute
 * scheme (URL-encoded JSON in double quotes for link= / css= / group
 * attributes — the canonical authoring form across the codebase). Without
 * a hint, a `0 occurrences` response forces a full get-post round-trip.
 * With the hint, the model fixes the encoding and retries.
 *
 * Variants probed (first hit wins, message stays short):
 *   1. rawurldecode($find) — model over-encoded literal chars.
 *   2. rawurlencode($find) — model passed a fully-decoded blob for an attr
 *      that is stored URL-encoded end-to-end (rare but cheap to check).
 *   3. $find with `/` → `%2F`, ` ` → `%20` — typical link="url:..." path
 *      under-encoding; whole-string urlencode is too aggressive here
 *      because it would also encode the surrounding `link="`.
 *   4. $find with `"` → `'` — caller wrote double-quoted attribute style
 *      but the body has a hand-edited single-quoted value. The canonical
 *      authoring form is double quotes everywhere; we keep this variant
 *      because real pages can drift from canonical and the hint is cheap.
 *
 * @param string $content
 * @param string $find
 * @return string  Empty string if no useful hint; otherwise a single
 *                 sentence prefixed with a leading space, ready to be
 *                 appended to the no-match error message.
 */
function us_mcp_patch_no_match_hint( $content, $find ) {
	$variants = array(
		array( 'URL-decoded form',                  rawurldecode( $find ) ),
		array( 'URL-encoded form',                  rawurlencode( $find ) ),
		array( "form with '/' and ' ' URL-encoded", str_replace( array( '/', ' ' ), array( '%2F', '%20' ), $find ) ),
		array( 'form with `\"` swapped to `\'`',    str_replace( '"', "'", $find ) ),
	);
	foreach ( $variants as $v ) {
		list( $label, $candidate ) = $v;
		if ( $candidate === $find OR $candidate === '' ) {
			continue;
		}
		$n = substr_count( $content, $candidate );
		if ( $n > 0 ) {
			return sprintf(
				' Hint: the %s of your `find` matches %d time%s — re-check the encoding (canonical authoring form: URL-encoded JSON in double quotes for link= / css= / group attrs).',
				$label, $n, ( $n === 1 ? '' : 's' )
			);
		}
	}
	return '';
}

/**
 * Apply a sequence of literal find/replace patches to a content string.
 *
 * All-or-nothing: returns WP_Error on the first patch whose match count
 * doesn't equal its `expect_matches`. Patches are applied in order to a
 * single in-memory buffer, so later patches see the result of earlier ones.
 *
 * Byte-exact match (substr_count / str_replace) — UTF-8 safe because
 * shortcode markup is stored as bytes and the caller's `find` came from a
 * prior byte-exact get-post payload.
 *
 * @param string $original
 * @param array  $patches  Already known to be a non-empty array.
 * @return array|WP_Error  On success: { content: string, report: array }.
 */
function us_mcp_apply_content_patches( $original, array $patches ) {
	if ( count( $patches ) > 50 ) {
		return new WP_Error(
			'us_mcp_posts_bad_patches',
			sprintf( 'Too many patches: %d (max 50).', count( $patches ) ),
			array( 'status' => 400 )
		);
	}
	$s      = (string) $original;
	$report = array();
	foreach ( $patches as $i => $p ) {
		$p       = (array) $p;
		$find    = isset( $p['find'] ) ? (string) $p['find'] : '';
		$replace = isset( $p['replace'] ) ? (string) $p['replace'] : '';
		$expect  = isset( $p['expect_matches'] ) ? (int) $p['expect_matches'] : 1;
		if ( $find === '' ) {
			return new WP_Error(
				'us_mcp_posts_bad_patches',
				sprintf( 'patches[%d]: `find` is empty.', $i ),
				array( 'status' => 400 )
			);
		}
		if ( $expect < 1 ) {
			return new WP_Error(
				'us_mcp_posts_bad_patches',
				sprintf( 'patches[%d]: `expect_matches` must be >= 1, got %d.', $i, $expect ),
				array( 'status' => 400 )
			);
		}
		$count = substr_count( $s, $find );
		if ( $count !== $expect ) {
			$hint = ( $count === 0 ) ? us_mcp_patch_no_match_hint( $s, $find ) : '';
			return new WP_Error(
				'us_mcp_posts_patch_no_match',
				sprintf( 'patches[%d]: `find` occurred %d times in the current post_content, expected %d. Re-read the post or adjust expect_matches.%s', $i, $count, $expect, $hint ),
				array( 'status' => 400 )
			);
		}
		$before  = strlen( $s );
		$s       = str_replace( $find, $replace, $s );
		$after   = strlen( $s );
		$report[] = array(
			'index'        => $i,
			'matches'      => $count,
			'bytes_before' => $before,
			'bytes_after'  => $after,
		);
	}
	return array( 'content' => $s, 'report' => $report );
}

function us_mcp_ability_update_post( $input ) {
	$input = (array) $input;
	$post_type = us_mcp_validate_post_type( $input );
	if ( is_wp_error( $post_type ) ) {
		return $post_type;
	}
	$spec = us_mcp_post_types()[ $post_type ];

	$post = us_mcp_resolve_post( $post_type, $input );
	if ( is_wp_error( $post ) ) {
		return $post;
	}

	$pt_obj = get_post_type_object( $post_type );
	$cap_check = us_mcp_require_cap( $pt_obj->cap->edit_post, $post->ID );
	if ( is_wp_error( $cap_check ) ) {
		return $cap_check;
	}

	// Validate the optional extras BEFORE any write, so an invalid terms / meta /
	// featured_image value rejects the whole call before title / content / status
	// or patches land — keeping the update atomic.
	$extras = us_mcp_validate_post_extras( $input, $spec );
	if ( is_wp_error( $extras ) ) {
		return $extras;
	}

	$args = array( 'ID' => (int) $post->ID );
	if ( array_key_exists( 'title',   $input ) ) { $args['post_title']   = (string) $input['title']; }
	if ( array_key_exists( 'content', $input ) ) { $args['post_content'] = (string) $input['content']; }
	if ( array_key_exists( 'status',  $input ) ) { $args['post_status']  = (string) $input['status']; }
	if ( array_key_exists( 'new_slug', $input ) AND trim( (string) $input['new_slug'] ) !== '' ) {
		$args['post_name'] = sanitize_title( (string) $input['new_slug'] );
	}
	if ( $spec['supports_excerpt'] AND array_key_exists( 'excerpt', $input ) ) {
		$args['post_excerpt'] = (string) $input['excerpt'];
	}

	// Optional scheduled / back-dated publication date. A future date makes
	// WordPress schedule the post (it sets the status to "future" itself); a
	// past date back-dates it. `edit_date` tells wp_update_post() to honour the
	// new date instead of preserving the stored one. Validate before any write
	// so a bad value rejects the whole call.
	if ( array_key_exists( 'date', $input ) AND trim( (string) $input['date'] ) !== '' ) {
		$date = us_mcp_parse_date_input( $input['date'] );
		if ( is_wp_error( $date ) ) {
			return $date;
		}
		$args['post_date']     = $date['post_date'];
		$args['post_date_gmt'] = $date['post_date_gmt'];
		$args['edit_date']     = TRUE;
	}

	$patches_report = NULL;
	if ( array_key_exists( 'patches', $input ) ) {
		if ( array_key_exists( 'content', $input ) ) {
			return new WP_Error(
				'us_mcp_posts_patches_conflict_with_content',
				'Pass either `content` (full rewrite) or `patches` (incremental), not both.',
				array( 'status' => 400 )
			);
		}
		if ( ! is_array( $input['patches'] ) ) {
			return new WP_Error(
				'us_mcp_posts_bad_patches',
				'`patches` must be an array.',
				array( 'status' => 400 )
			);
		}
		if ( empty( $input['patches'] ) ) {
			return new WP_Error(
				'us_mcp_posts_bad_patches',
				'`patches` must contain at least one entry.',
				array( 'status' => 400 )
			);
		}
		$applied = us_mcp_apply_content_patches( $post->post_content, $input['patches'] );
		if ( is_wp_error( $applied ) ) {
			return $applied;
		}
		$args['post_content'] = $applied['content'];
		$patches_report       = $applied['report'];
	}

	$has_extras = (
		array_key_exists( 'terms', $input )
		OR array_key_exists( 'meta', $input )
		OR array_key_exists( 'featured_image_id', $input )
	);

	if ( count( $args ) === 1 AND ! $has_extras ) {
		return new WP_Error(
			'us_mcp_posts_no_changes',
			'No mutable fields supplied (title / content / patches / status / date / new_slug / excerpt / featured_image_id / terms / meta).',
			array( 'status' => 400 )
		);
	}

	if ( count( $args ) > 1 ) {
		$result = wp_update_post( $args, /* wp_error */ TRUE );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
	}

	$extras_applied = us_mcp_apply_post_extras( $post, $extras );
	if ( is_wp_error( $extras_applied ) ) {
		return $extras_applied;
	}

	$post = get_post( $post->ID );
	$payload = us_mcp_post_to_payload( $post, /* include_content */ ! empty( $input['return_content'] ) );
	if ( $patches_report !== NULL ) {
		$payload['patches_applied'] = $patches_report;
		$payload['content_bytes']   = strlen( $post->post_content );
	}
	return $payload;
}

/**
 * Copy every taxonomy-term assignment and all custom meta from one post to
 * another (used by duplicate-post). Terms are copied for every taxonomy
 * attached to the source's post type; meta is copied verbatim except for the
 * WordPress-internal bookkeeping keys that must never travel to a fresh copy
 * (edit locks, old-slug / old-date history, trash markers, moderation flags).
 *
 * get_post_meta( $id ) with no key returns the RAW (still-serialized) stored
 * strings, so each value is run back through maybe_unserialize() and then
 * wp_slash()'d — the Meta API unslashes on write, so without the re-slash a
 * value containing backslashes would be corrupted. add_post_meta() (not
 * update_post_meta) is used so multi-value meta keys are preserved.
 *
 * @param WP_Post $source
 * @param int     $target_id
 * @return true|WP_Error
 */
function us_mcp_copy_post_terms_and_meta( WP_Post $source, $target_id ) {
	// Terms — across every taxonomy registered for the type (categories, tags,
	// post_format, custom taxonomies), for a faithful copy.
	foreach ( get_object_taxonomies( $source->post_type ) as $tax ) {
		$term_ids = wp_get_object_terms( $source->ID, $tax, array( 'fields' => 'ids' ) );
		if ( is_wp_error( $term_ids ) ) {
			return $term_ids;
		}
		if ( ! empty( $term_ids ) ) {
			$set = wp_set_object_terms( $target_id, array_map( 'intval', $term_ids ), $tax, /* append */ FALSE );
			if ( is_wp_error( $set ) ) {
				return $set;
			}
		}
	}

	// Meta — everything except WP-internal bookkeeping. _thumbnail_id is copied
	// here too, so the featured image carries over by reference.
	$skip = array(
		'_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date',
		'_wp_trash_meta_status', '_wp_trash_meta_time', '_pingme', '_encloseme',
	);
	$all_meta = get_post_meta( $source->ID );
	if ( is_array( $all_meta ) ) {
		foreach ( $all_meta as $key => $values ) {
			if ( in_array( $key, $skip, TRUE ) ) {
				continue;
			}
			foreach ( (array) $values as $value ) {
				add_post_meta( $target_id, $key, wp_slash( maybe_unserialize( $value ) ) );
			}
		}
	}

	return TRUE;
}

function us_mcp_ability_duplicate_post( $input ) {
	$input = (array) $input;
	$post_type = us_mcp_validate_post_type( $input );
	if ( is_wp_error( $post_type ) ) {
		return $post_type;
	}

	// A duplicate is a new post of the type — gate on the broad create cap,
	// same as create-post (there is no target post id to gate on yet).
	$pt_obj = get_post_type_object( $post_type );
	if ( ! current_user_can( $pt_obj->cap->edit_posts ) ) {
		return new WP_Error( 'us_mcp_posts_forbidden', 'You do not have permission to create this post type.', array( 'status' => 403 ) );
	}

	$source = us_mcp_resolve_post( $post_type, $input );
	if ( is_wp_error( $source ) ) {
		return $source;
	}

	$title = $source->post_title . ' (copy)';
	if ( array_key_exists( 'title', $input ) AND trim( (string) $input['title'] ) !== '' ) {
		$title = (string) $input['title'];
	}

	$args = array(
		'post_type'      => $post_type,
		'post_title'     => $title,
		'post_content'   => $source->post_content,
		'post_excerpt'   => $source->post_excerpt,
		'post_status'    => isset( $input['status'] ) ? (string) $input['status'] : 'draft',
		'post_author'    => $source->post_author,
		'post_parent'    => $source->post_parent,
		'menu_order'     => $source->menu_order,
		'comment_status' => $source->comment_status,
		'ping_status'    => $source->ping_status,
	);
	if ( array_key_exists( 'new_slug', $input ) AND trim( (string) $input['new_slug'] ) !== '' ) {
		$args['post_name'] = sanitize_title( (string) $input['new_slug'] );
	}

	// Optional scheduled / back-dated date for the copy (parsed before insert).
	if ( array_key_exists( 'date', $input ) AND trim( (string) $input['date'] ) !== '' ) {
		$date = us_mcp_parse_date_input( $input['date'] );
		if ( is_wp_error( $date ) ) {
			return $date;
		}
		$args['post_date']     = $date['post_date'];
		$args['post_date_gmt'] = $date['post_date_gmt'];
	}

	// wp_slash the content / excerpt: wp_insert_post() unslashes its input, so
	// the raw source body (which may contain backslashes inside shortcode JSON)
	// has to be re-slashed to survive the round-trip unchanged.
	$args['post_content'] = wp_slash( $args['post_content'] );
	$args['post_excerpt'] = wp_slash( $args['post_excerpt'] );
	$args['post_title']   = wp_slash( $args['post_title'] );

	$new_id = wp_insert_post( $args, /* wp_error */ TRUE );
	if ( is_wp_error( $new_id ) ) {
		return $new_id;
	}

	$copied = us_mcp_copy_post_terms_and_meta( $source, (int) $new_id );
	if ( is_wp_error( $copied ) ) {
		return $copied;
	}

	$new_post = get_post( $new_id );
	$payload  = us_mcp_post_to_payload( $new_post, /* include_content */ ! empty( $input['return_content'] ) );
	$payload['source_id'] = (int) $source->ID;
	return $payload;
}

function us_mcp_ability_delete_post( $input ) {
	$input = (array) $input;
	$post_type = us_mcp_validate_post_type( $input );
	if ( is_wp_error( $post_type ) ) {
		return $post_type;
	}
	$post = us_mcp_resolve_post( $post_type, $input );
	if ( is_wp_error( $post ) ) {
		return $post;
	}

	$pt_obj = get_post_type_object( $post_type );
	$cap_check = us_mcp_require_cap( $pt_obj->cap->delete_post, $post->ID );
	if ( is_wp_error( $cap_check ) ) {
		return $cap_check;
	}

	// Idempotent path: trashing a post that is already in the trash is a no-op.
	// wp_trash_post() returns FALSE in that case; treat it as success since the
	// post-condition "this id is trashed" already holds, so callers can retry
	// safely.
	if ( $post->post_status === 'trash' ) {
		return array(
			'id'      => (int) $post->ID,
			'deleted' => FALSE,
			'trashed' => TRUE,
		);
	}

	// Permanent deletion is never performed here. When the site has the trash
	// disabled (EMPTY_TRASH_DAYS = 0), wp_trash_post() would silently fall
	// through to a permanent wp_delete_post() — which this tool must not do.
	// Refuse instead, so a removal can never become irreversible via MCP.
	if ( ! EMPTY_TRASH_DAYS ) {
		return new WP_Error(
			'us_mcp_posts_trash_disabled',
			sprintf( 'Cannot move %s id %d to the trash: this site has the trash disabled (EMPTY_TRASH_DAYS = 0), and permanent deletion is not allowed via MCP. Delete it from wp-admin if that is truly intended.', $post_type, $post->ID ),
			array( 'status' => 409 )
		);
	}

	$result = wp_trash_post( (int) $post->ID );
	if ( ! $result ) {
		return new WP_Error( 'us_mcp_posts_delete_failed', sprintf( 'Could not move %s id %d to the trash.', $post_type, $post->ID ), array( 'status' => 500 ) );
	}

	return array(
		'id'      => (int) $post->ID,
		'deleted' => FALSE,
		'trashed' => TRUE,
	);
}
