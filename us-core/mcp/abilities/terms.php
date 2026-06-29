<?php
/**
 * UpSolution MCP — taxonomy term abilities.
 *
 * Three abilities for the taxonomies referenced from us_mcp_post_types():
 *
 *   upsolution-list-terms    — resolve a name to an id (read).
 *   upsolution-create-term   — add a new term to one of those taxonomies.
 *   upsolution-delete-term   — permanently delete a term by id.
 *
 * Only taxonomies referenced from us_mcp_post_types() are exposed; arbitrary
 * site taxonomies are intentionally NOT touchable from here (use the REST API
 * if you need that breadth).
 *
 * @package usCore\Mcp
 */

defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Distinct taxonomy slugs across the post-type registry. Memoised — list-terms'
 * register/execute path hits this three times per request (input enum, runtime
 * validation, error message), and us_mcp_post_types() does non-trivial work.
 *
 * @return string[]
 */
function us_mcp_known_taxonomies() {
	static $cached = NULL;
	if ( $cached !== NULL ) {
		return $cached;
	}
	$out = array();
	foreach ( us_mcp_post_types() as $spec ) {
		foreach ( $spec['taxonomies'] as $tax ) {
			$out[ $tax ] = TRUE;
		}
	}
	$cached = array_keys( $out );
	return $cached;
}

add_action( 'wp_abilities_api_init', function () {
	$tax_enum = us_mcp_known_taxonomies();

	$term_output = array(
		'id'     => array( 'type' => 'integer' ),
		'name'   => array( 'type' => 'string' ),
		'slug'   => array( 'type' => 'string' ),
		'parent' => array( 'type' => 'integer' ),
		'count'  => array( 'type' => 'integer' ),
	);

	wp_register_ability( 'upsolution/list-terms', array(
		'label'               => 'List taxonomy terms',
		'description'         => 'List terms of one of the taxonomies registered for the post types this server can edit (category, post_tag, us_portfolio_category, us_portfolio_tag, us_testimonial_category). Returns id/name/slug/parent/count so an agent can resolve a human-readable name to the term id required by upsolution-create-post / update-post `terms` input.',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'       => 'object',
			'required'   => array( 'taxonomy' ),
			'properties' => array(
				'taxonomy' => array(
					'type'        => 'string',
					'enum'        => $tax_enum,
					'description' => 'Taxonomy slug to list terms from.',
				),
				'search'   => array(
					'type'        => 'string',
					'description' => 'Optional substring filter applied to term names (case-insensitive).',
				),
				'parent'   => array(
					'type'        => 'integer',
					'description' => 'Optional parent term id (for hierarchical taxonomies). Pass 0 to list top-level only.',
				),
				'per_page' => array(
					'type'        => 'integer',
					'description' => 'Maximum number of terms to return (default 50, max 200).',
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
				'properties' => $term_output,
			),
		),
		'execute_callback'    => 'us_mcp_ability_list_terms',
		'permission_callback' => 'us_mcp_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );

	wp_register_ability( 'upsolution/create-term', array(
		'label'               => 'Create a taxonomy term',
		'description'         => 'Create a new term in one of the taxonomies registered for the post types this server can edit (category, post_tag, us_portfolio_category, us_portfolio_tag, us_testimonial_category). Returns the same id/name/slug/parent/count shape as list-terms so the new id can be passed straight into upsolution-create-post / update-post `terms`. Slug is derived from name when omitted; `parent` applies only to hierarchical taxonomies (category, us_portfolio_category, us_testimonial_category) and is ignored otherwise. Returns 400 on duplicate name/slug — call upsolution-list-terms first if you are not sure whether the term already exists.',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'       => 'object',
			'required'   => array( 'taxonomy', 'name' ),
			'properties' => array(
				'taxonomy'    => array(
					'type'        => 'string',
					'enum'        => $tax_enum,
					'description' => 'Taxonomy slug to create the term in.',
				),
				'name'        => array(
					'type'        => 'string',
					'description' => 'Human-readable term name.',
				),
				'slug'        => array(
					'type'        => 'string',
					'description' => 'Optional lowercase-hyphenated slug. Derived from name (via sanitize_title) when omitted.',
				),
				'description' => array(
					'type'        => 'string',
					'description' => 'Optional term description.',
				),
				'parent'      => array(
					'type'        => 'integer',
					'description' => 'Optional parent term id for hierarchical taxonomies. Ignored for non-hierarchical taxonomies (post_tag, us_portfolio_tag).',
				),
			),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'properties' => $term_output,
		),
		'execute_callback'    => 'us_mcp_ability_create_term',
		'permission_callback' => 'us_mcp_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );

	wp_register_ability( 'upsolution/delete-term', array(
		'label'               => 'Delete a taxonomy term',
		'description'         => 'Permanently delete a term by id from one of the taxonomies registered for the post types this server can edit. Term deletion is NOT trashable in WordPress — this is a hard delete. Posts that were assigned only this term lose the assignment; for hierarchical taxonomies, child terms are reparented to the deleted term\'s parent. Returns 400 if you try to delete the default category.',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'       => 'object',
			'required'   => array( 'taxonomy', 'id' ),
			'properties' => array(
				'taxonomy' => array(
					'type'        => 'string',
					'enum'        => $tax_enum,
					'description' => 'Taxonomy slug the term belongs to.',
				),
				'id'       => array(
					'type'        => 'integer',
					'description' => 'Term id to delete.',
				),
			),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'properties' => array(
				'id'      => array( 'type' => 'integer' ),
				'deleted' => array( 'type' => 'boolean' ),
			),
		),
		'execute_callback'    => 'us_mcp_ability_delete_term',
		'permission_callback' => 'us_mcp_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );
} );

/**
 * Shared taxonomy validation for create/delete. Returns a registered
 * WP_Taxonomy object on success, or a WP_Error matching the patterns used
 * elsewhere in the abilities surface.
 *
 * @param array $input
 * @return WP_Taxonomy|WP_Error
 */
function us_mcp_resolve_taxonomy( array $input ) {
	$tax = isset( $input['taxonomy'] ) ? (string) $input['taxonomy'] : '';
	if ( $tax === '' OR ! in_array( $tax, us_mcp_known_taxonomies(), TRUE ) ) {
		return new WP_Error(
			'us_mcp_terms_bad_taxonomy',
			sprintf( 'Unknown taxonomy. Allowed: %s.', implode( ', ', us_mcp_known_taxonomies() ) ),
			array( 'status' => 400 )
		);
	}
	if ( ! taxonomy_exists( $tax ) ) {
		return new WP_Error(
			'us_mcp_terms_taxonomy_disabled',
			sprintf( 'Taxonomy "%s" is not registered on this site (check Theme Options).', $tax ),
			array( 'status' => 400 )
		);
	}
	$tax_obj = get_taxonomy( $tax );
	if ( ! $tax_obj ) {
		return new WP_Error(
			'us_mcp_terms_taxonomy_disabled',
			sprintf( 'Taxonomy "%s" is not registered on this site (check Theme Options).', $tax ),
			array( 'status' => 400 )
		);
	}
	return $tax_obj;
}

/**
 * Serialise a WP_Term to the output shape shared by list/create/delete-term.
 *
 * @param WP_Term $term
 * @return array
 */
function us_mcp_term_to_payload( WP_Term $term ) {
	return array(
		'id'     => (int) $term->term_id,
		'name'   => $term->name,
		'slug'   => $term->slug,
		'parent' => (int) $term->parent,
		'count'  => (int) $term->count,
	);
}

/**
 * @param array|object $input
 * @return array|WP_Error
 */
function us_mcp_ability_list_terms( $input ) {
	$input = (array) $input;
	$tax_obj = us_mcp_resolve_taxonomy( $input );
	if ( is_wp_error( $tax_obj ) ) {
		return $tax_obj;
	}

	$per_page = isset( $input['per_page'] ) ? (int) $input['per_page'] : 50;
	$per_page = max( 1, min( 200, $per_page ) );

	$args = array(
		'taxonomy'   => $tax_obj->name,
		'hide_empty' => FALSE,
		'number'     => $per_page,
		'orderby'    => 'name',
		'order'      => 'ASC',
	);
	if ( isset( $input['search'] ) AND trim( (string) $input['search'] ) !== '' ) {
		$args['search'] = (string) $input['search'];
	}
	if ( array_key_exists( 'parent', $input ) ) {
		$args['parent'] = (int) $input['parent'];
	}

	$terms = get_terms( $args );
	if ( is_wp_error( $terms ) ) {
		return $terms;
	}

	$out = array();
	foreach ( (array) $terms as $term ) {
		$out[] = us_mcp_term_to_payload( $term );
	}
	return $out;
}

/**
 * @param array|object $input
 * @return array|WP_Error
 */
function us_mcp_ability_create_term( $input ) {
	$input = (array) $input;
	$tax_obj = us_mcp_resolve_taxonomy( $input );
	if ( is_wp_error( $tax_obj ) ) {
		return $tax_obj;
	}

	if ( ! current_user_can( $tax_obj->cap->edit_terms ) ) {
		return new WP_Error(
			'us_mcp_terms_forbidden',
			sprintf( 'You do not have permission to create terms in taxonomy "%s".', $tax_obj->name ),
			array( 'status' => 403 )
		);
	}

	$name = isset( $input['name'] ) ? trim( (string) $input['name'] ) : '';
	if ( $name === '' ) {
		return new WP_Error( 'us_mcp_terms_missing_name', 'Provide a non-empty `name`.', array( 'status' => 400 ) );
	}

	$args = array();
	if ( isset( $input['slug'] ) AND trim( (string) $input['slug'] ) !== '' ) {
		$args['slug'] = sanitize_title( (string) $input['slug'] );
	}
	if ( isset( $input['description'] ) ) {
		$args['description'] = (string) $input['description'];
	}
	// `parent` only meaningful for hierarchical taxonomies — wp_insert_term
	// would otherwise reject a non-zero parent with `taxonomy_does_not_allow_hierarchy`.
	if ( array_key_exists( 'parent', $input ) AND $tax_obj->hierarchical ) {
		$args['parent'] = (int) $input['parent'];
	}

	$result = wp_insert_term( $name, $tax_obj->name, $args );
	if ( is_wp_error( $result ) ) {
		// `term_exists` lacks an HTTP status — promote to 400 so the transport
		// surfaces it as a client error rather than a generic 500.
		$data = $result->get_error_data();
		if ( ! is_array( $data ) OR ! isset( $data['status'] ) ) {
			$result->add_data( array( 'status' => 400 ), $result->get_error_code() );
		}
		return $result;
	}

	$term = get_term( (int) $result['term_id'], $tax_obj->name );
	if ( ! $term OR is_wp_error( $term ) ) {
		return new WP_Error( 'us_mcp_terms_create_failed', 'Term was created but could not be re-read.', array( 'status' => 500 ) );
	}
	return us_mcp_term_to_payload( $term );
}

/**
 * @param array|object $input
 * @return array|WP_Error
 */
function us_mcp_ability_delete_term( $input ) {
	$input = (array) $input;
	$tax_obj = us_mcp_resolve_taxonomy( $input );
	if ( is_wp_error( $tax_obj ) ) {
		return $tax_obj;
	}

	if ( ! current_user_can( $tax_obj->cap->delete_terms ) ) {
		return new WP_Error(
			'us_mcp_terms_forbidden',
			sprintf( 'You do not have permission to delete terms in taxonomy "%s".', $tax_obj->name ),
			array( 'status' => 403 )
		);
	}

	$id = isset( $input['id'] ) ? (int) $input['id'] : 0;
	if ( $id <= 0 ) {
		return new WP_Error( 'us_mcp_terms_missing_id', 'Provide a positive integer `id`.', array( 'status' => 400 ) );
	}

	$term = get_term( $id, $tax_obj->name );
	if ( ! $term OR is_wp_error( $term ) ) {
		return new WP_Error(
			'us_mcp_terms_not_found',
			sprintf( 'No term with id %d in taxonomy "%s".', $id, $tax_obj->name ),
			array( 'status' => 404 )
		);
	}

	$result = wp_delete_term( $id, $tax_obj->name );
	if ( is_wp_error( $result ) ) {
		return $result;
	}
	// wp_delete_term returns 0 specifically when refusing to delete the default
	// category for `category` taxonomy. FALSE means the term did not exist —
	// shouldn't happen here because we re-checked above, but treat it as 404.
	if ( $result === 0 ) {
		return new WP_Error(
			'us_mcp_terms_default_term',
			sprintf( 'Term id %d is the default term for taxonomy "%s" and cannot be deleted.', $id, $tax_obj->name ),
			array( 'status' => 400 )
		);
	}
	if ( $result === FALSE ) {
		return new WP_Error(
			'us_mcp_terms_not_found',
			sprintf( 'No term with id %d in taxonomy "%s".', $id, $tax_obj->name ),
			array( 'status' => 404 )
		);
	}

	return array(
		'id'      => $id,
		'deleted' => TRUE,
	);
}
