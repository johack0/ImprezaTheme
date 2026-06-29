<?php
/**
 * UpSolution MCP — Headers (us_header).
 *
 * Currently exposes one read-only ability:
 *
 *   upsolution-list-headers
 *
 * Resolves a human-readable Header name to its post id. The id is used by
 * meta `us_header_id` on page / post / us_portfolio (whitelisted in the
 * post-types registry) to assign a custom header to a single record via
 * upsolution-update-post.
 *
 * The `us_header` post type is registered with `'supports' => FALSE` and
 * `'capability_type' => $templates_capability` (white-label-aware) in
 * functions/post-types.php. Its `post_content` is a JSON config produced by
 * the Header Builder (see functions/header.php: `json_decode(
 * $header->post_content, TRUE )`), NOT shortcode markup, so the generic
 * upsolution-create-post / update-post pipeline must not be used on it —
 * doing so would overwrite valid JSON and silently break the header. The
 * generic CRUD's `post_type` enum already excludes `us_header` for this
 * reason.
 *
 * Future work: get / create / update / delete header tools belong in this
 * file, with a Header-Builder-JSON-shape doc to back them. They share the
 * permission callback and registry helper below.
 *
 * @package usCore\Mcp
 */

defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

add_action( 'wp_abilities_api_init', function () {
	wp_register_ability( 'upsolution/list-headers', array(
		'label'               => 'List Headers',
		'description'         => 'List Headers (us_header) so the agent can resolve a human-readable name to a numeric id. Pass the returned id as meta `us_header_id` on upsolution-update-post (whitelisted on page / post / us_portfolio) to assign a custom header to that record. Read-only — us_header stores a custom Header Builder JSON config in post_content (not shortcode markup), so the generic CRUD tools refuse this type. Returns id / slug / title / status / modified / edit_link.',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'       => 'object',
			'properties' => array(
				'search'   => array(
					'type'        => 'string',
					'description' => 'Optional. Forwarded to WP_Query\'s full-text search (`s`): matches against post_title, post_content AND post_excerpt; multi-word queries are AND\'d across terms. Caveat: us_header.post_content is a Header Builder JSON blob, so search terms collide with JSON keys (e.g. "sticky", "transparent", "position" will hit configuration tokens, not just human-readable labels). For narrow lookup, pass an empty `search` and filter the returned list by title client-side, or call with no params and pick by id.',
				),
				'status'   => array(
					'type'        => 'string',
					'description' => 'Comma-separated WP post statuses (e.g. "publish,draft"). Default: "publish,draft".',
					'default'     => 'publish,draft',
				),
				'per_page' => array(
					'type'        => 'integer',
					'description' => 'Maximum number of records to return (default 20, max 100).',
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
					'id'        => array( 'type' => 'integer', 'description' => 'Post id. Pass this as meta `us_header_id` on upsolution-update-post.' ),
					'post_type' => array( 'type' => 'string' ),
					'slug'      => array( 'type' => 'string' ),
					'title'     => array( 'type' => 'string' ),
					'status'    => array( 'type' => 'string' ),
					'modified'  => array( 'type' => 'string' ),
					'edit_link' => array( 'type' => 'string', 'description' => 'wp-admin URL of the Header Builder for this record.' ),
				),
			),
		),
		'execute_callback'    => 'us_mcp_ability_list_headers',
		'permission_callback' => 'us_mcp_header_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );
} );

/**
 * Permission gate for the headers tools. Consults the registered post-type
 * object's edit_posts cap, because us_header uses $templates_capability
 * (white-label-aware) at registration — hardcoding edit_posts would bypass
 * the white-label remap.
 *
 * Shared by every ability declared in this file so a future CRUD does not
 * have to re-derive the cap. Mutating callbacks should still do a per-post
 * `edit_post` / `delete_post` check after resolving the id.
 *
 * @return bool
 */
function us_mcp_header_permission_callback() {
	if ( ! post_type_exists( 'us_header' ) ) {
		return current_user_can( 'edit_posts' );
	}
	$pt_obj = get_post_type_object( 'us_header' );
	if ( ! $pt_obj OR ! isset( $pt_obj->cap->edit_posts ) ) {
		return FALSE;
	}
	return current_user_can( $pt_obj->cap->edit_posts );
}

/**
 * @param array|object $input
 * @return array|WP_Error
 */
function us_mcp_ability_list_headers( $input ) {
	$input = (array) $input;
	if ( ! post_type_exists( 'us_header' ) ) {
		return new WP_Error(
			'us_mcp_headers_disabled',
			'"us_header" is not registered on this site.',
			array( 'status' => 400 )
		);
	}

	$status_csv = isset( $input['status'] ) ? (string) $input['status'] : 'publish,draft';
	$statuses   = array_filter( array_map( 'trim', explode( ',', $status_csv ) ) );
	if ( empty( $statuses ) ) {
		$statuses = array( 'publish', 'draft' );
	}
	$per_page = isset( $input['per_page'] ) ? (int) $input['per_page'] : 20;
	$per_page = max( 1, min( 100, $per_page ) );

	$args = array(
		'post_type'      => 'us_header',
		'post_status'    => $statuses,
		'posts_per_page' => $per_page,
		'orderby'        => 'modified',
		'order'          => 'DESC',
	);
	if ( isset( $input['search'] ) AND trim( (string) $input['search'] ) !== '' ) {
		$args['s'] = (string) $input['search'];
	}

	$query = new WP_Query( $args );
	$out   = array();
	foreach ( $query->posts as $post ) {
		$out[] = array(
			'id'        => (int) $post->ID,
			'post_type' => $post->post_type,
			'slug'      => $post->post_name,
			'title'     => $post->post_title,
			'status'    => $post->post_status,
			'modified'  => mysql2date( 'c', $post->post_modified_gmt, FALSE ),
			'edit_link' => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
		);
	}
	return $out;
}
