<?php
/**
 * UpSolution MCP — Grid Layouts (us_grid_layout) + built-in templates.
 *
 * Currently exposes one read-only ability:
 *
 *   upsolution-list-grid-layouts
 *
 * Resolves a human-readable Grid Layout name to the identifier accepted by
 * the `items_layout="…"` attribute on the list-family shortcodes —
 * [us_post_list] / [us_product_list] / [us_user_list] / [us_term_list]
 * and their *_carousel aliases registered in config/shortcodes.php.
 *
 * Two families share the same attribute:
 *
 *   - custom records of the `us_grid_layout` post type (numeric id);
 *   - built-in templates declared in config/grid-templates.php
 *     (string keys like "blog_1", "portfolio_3").
 *
 * us_get_grid_layout_settings() in functions/list.php:165-194 resolves both:
 * it checks the templates config first, then falls back to a post lookup.
 * The admin selector (us_get_grid_layouts_for_selection, list.php:320)
 * presents both families in one dropdown, custom records first, then
 * built-in templates by group. This tool mirrors that mental model — set
 * `source` if you want to narrow to one family.
 *
 * The `us_grid_layout` post type itself is registered with
 * `'supports' => FALSE` and `'capability_type' => $templates_capability`
 * (white-label-aware) in functions/post-types.php. Its `post_content` is a
 * JSON config produced by the Grid Builder (see functions/list.php:187-189:
 * `json_decode( $grid_layout_post->post_content, TRUE )`), NOT shortcode
 * markup, so the generic upsolution-create-post / update-post pipeline
 * must not be used on it. The generic CRUD's `post_type` enum already
 * excludes `us_grid_layout` for this reason. Built-in templates live in a
 * PHP config file and are not editable at runtime.
 *
 * Future work: get / create / update / delete grid-layout tools for the
 * custom-record family belong in this file, with a Grid-Builder-JSON-shape
 * doc to back them. They share the permission callback below.
 *
 * @package usCore\Mcp
 */

defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

add_action( 'wp_abilities_api_init', function () {
	wp_register_ability( 'upsolution/list-grid-layouts', array(
		'label'               => 'List Grid Layouts',
		'description'         => 'List Grid Layouts so the agent can resolve a human-readable name to the identifier required by the `items_layout="…"` attribute on [us_post_list] / [us_product_list] / [us_user_list] / [us_term_list] (and their *_carousel aliases). Returns BOTH families that this attribute accepts: custom us_grid_layout records (numeric id) and built-in templates from config/grid-templates.php (string keys like "blog_1"). Set `source` to narrow. Each item carries its `source` tag plus a `selector` field — pass selector verbatim into the shortcode attribute. Read-only — custom records store a Grid Builder JSON config in post_content (the generic CRUD tools refuse this type); built-in templates ship in PHP and are not editable at runtime.',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'       => 'object',
			'properties' => array(
				'source'   => array(
					'type'        => 'string',
					'enum'        => array( 'all', 'posts', 'presets' ),
					'description' => 'Which family to return. "posts" → only custom us_grid_layout records. "presets" → only built-in templates from config/grid-templates.php. "all" (default) → both, custom records first then presets grouped by config order.',
					'default'     => 'all',
				),
				'search'   => array(
					'type'        => 'string',
					'description' => 'Optional. Behaviour differs by family. For the "posts" family (custom us_grid_layout records): forwarded to WP_Query\'s full-text search (`s`), which matches against post_title, post_content AND post_excerpt; us_grid_layout.post_content is a Grid Builder JSON blob, so search terms can hit JSON keys (e.g. "row", "column", "items") and surface false positives. For the "presets" family (config/grid-templates.php): real case-insensitive substring match against `title` + preset key. For exact custom-record lookup, call with no `search` and pick by selector / title client-side.',
				),
				'status'   => array(
					'type'        => 'string',
					'description' => 'Comma-separated WP post statuses (e.g. "publish,draft"). Applies to the "posts" family only; ignored for presets. Default: "publish,draft".',
					'default'     => 'publish,draft',
				),
				'per_page' => array(
					'type'        => 'integer',
					'description' => 'Maximum number of records to return overall (default 50, max 200). With source="all" this cap is shared across both families.',
					'default'     => 50,
					'minimum'     => 1,
					'maximum'     => 200,
				),
			),
		),
		'output_schema'       => array(
			'type'  => 'array',
			'items' => array(
				'type'       => 'object',
				'properties' => array(
					'selector'  => array(
						'type'        => array( 'integer', 'string' ),
						'description' => 'The value to put inside items_layout="…" — integer for custom records (e.g. 42), string key for built-in templates (e.g. "blog_1").',
					),
					'source'    => array(
						'type'        => 'string',
						'enum'        => array( 'post', 'preset' ),
						'description' => '"post" for a custom us_grid_layout record, "preset" for a built-in template.',
					),
					'title'     => array( 'type' => 'string' ),
					'group'     => array( 'type' => 'string', 'description' => 'Preset group label (e.g. "Blog Templates (for several columns)"). Empty string for the "post" source.' ),
					'slug'      => array( 'type' => 'string', 'description' => 'Post slug. Empty string for the "preset" source.' ),
					'status'    => array( 'type' => 'string', 'description' => 'Empty string for the "preset" source.' ),
					'modified'  => array( 'type' => 'string', 'description' => 'Empty string for the "preset" source.' ),
					'edit_link' => array( 'type' => array( 'string', 'null' ), 'description' => 'wp-admin URL of the Grid Builder for custom records. NULL for presets — they are not editable at runtime.' ),
				),
			),
		),
		'execute_callback'    => 'us_mcp_ability_list_grid_layouts',
		'permission_callback' => 'us_mcp_grid_layout_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );
} );

/**
 * Permission gate for the grid-layout tools. Consults the registered
 * post-type object's edit_posts cap, because us_grid_layout uses
 * $templates_capability (white-label-aware) at registration — hardcoding
 * edit_posts would bypass the white-label remap.
 *
 * Shared by every ability declared in this file so a future CRUD does not
 * have to re-derive the cap. Mutating callbacks should still do a per-post
 * `edit_post` / `delete_post` check after resolving the id.
 *
 * @return bool
 */
function us_mcp_grid_layout_permission_callback() {
	if ( ! post_type_exists( 'us_grid_layout' ) ) {
		return current_user_can( 'edit_posts' );
	}
	$pt_obj = get_post_type_object( 'us_grid_layout' );
	if ( ! $pt_obj OR ! isset( $pt_obj->cap->edit_posts ) ) {
		return FALSE;
	}
	return current_user_can( $pt_obj->cap->edit_posts );
}

/**
 * @param array|object $input
 * @return array|WP_Error
 */
function us_mcp_ability_list_grid_layouts( $input ) {
	$input = (array) $input;

	$source = isset( $input['source'] ) ? (string) $input['source'] : 'all';
	if ( ! in_array( $source, array( 'all', 'posts', 'presets' ), TRUE ) ) {
		$source = 'all';
	}
	$per_page = isset( $input['per_page'] ) ? (int) $input['per_page'] : 50;
	$per_page = max( 1, min( 200, $per_page ) );
	$search   = isset( $input['search'] ) ? trim( (string) $input['search'] ) : '';

	$out = array();

	// --- Custom records (us_grid_layout) ---------------------------------
	if ( $source === 'all' OR $source === 'posts' ) {
		if ( ! post_type_exists( 'us_grid_layout' ) ) {
			// Hard fail only when the caller explicitly asked for posts; in
			// "all" mode silently skip and return presets only.
			if ( $source === 'posts' ) {
				return new WP_Error(
					'us_mcp_grid_layouts_disabled',
					'"us_grid_layout" is not registered on this site.',
					array( 'status' => 400 )
				);
			}
		} else {
			$status_csv = isset( $input['status'] ) ? (string) $input['status'] : 'publish,draft';
			$statuses   = array_filter( array_map( 'trim', explode( ',', $status_csv ) ) );
			if ( empty( $statuses ) ) {
				$statuses = array( 'publish', 'draft' );
			}

			$args = array(
				'post_type'      => 'us_grid_layout',
				'post_status'    => $statuses,
				'posts_per_page' => $per_page,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			);
			if ( $search !== '' ) {
				$args['s'] = $search;
			}

			$query = new WP_Query( $args );
			foreach ( $query->posts as $post ) {
				$out[] = array(
					'selector'  => (int) $post->ID,
					'source'    => 'post',
					'title'     => $post->post_title,
					'group'     => '',
					'slug'      => $post->post_name,
					'status'    => $post->post_status,
					'modified'  => mysql2date( 'c', $post->post_modified_gmt, FALSE ),
					'edit_link' => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
				);
			}
		}
	}

	// --- Built-in templates (config/grid-templates.php) ------------------
	if ( $source === 'all' OR $source === 'presets' ) {
		$remaining = $per_page - count( $out );
		if ( $remaining > 0 ) {
			$current_group = '';
			$search_lc     = $search !== '' ? mb_strtolower( $search ) : '';
			foreach ( us_config( 'grid-templates', array(), TRUE ) as $key => $tpl ) {
				if ( ! is_array( $tpl ) ) {
					continue;
				}
				// `group` is sticky in the config — only the first entry of a
				// group declares it; later entries inherit the last seen
				// label. us_get_grid_layouts_for_selection() does the same.
				if ( ! empty( $tpl['group'] ) AND $tpl['group'] !== $current_group ) {
					$current_group = (string) $tpl['group'];
				}
				$title = isset( $tpl['title'] ) ? (string) $tpl['title'] : (string) $key;
				if ( $search_lc !== '' ) {
					$haystack = mb_strtolower( $title . ' ' . $key );
					if ( strpos( $haystack, $search_lc ) === FALSE ) {
						continue;
					}
				}
				$out[] = array(
					'selector'  => (string) $key,
					'source'    => 'preset',
					'title'     => $title,
					'group'     => $current_group,
					'slug'      => '',
					'status'    => '',
					'modified'  => '',
					'edit_link' => NULL,
				);
				if ( count( $out ) >= $per_page ) {
					break;
				}
			}
		}
	}

	return $out;
}
