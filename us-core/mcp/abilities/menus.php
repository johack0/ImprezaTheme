<?php
/**
 * UpSolution MCP — WordPress Nav Menus editor.
 *
 * Four abilities for inspecting and changing the site's navigation menus
 * (Appearance → Menus — the WP-native `nav_menu` taxonomy + `nav_menu_item`
 * posts; nothing us-core-specific in storage):
 *
 *   upsolution-list-menus     — every menu (id / name / slug / item count).
 *   upsolution-get-menu       — one menu's items as a nested tree. Item ids
 *                               from here are what set-menu-items targets.
 *   upsolution-set-menu-items — apply a sequence of operations
 *                               (add / update / remove / reorder) to one
 *                               menu's items as one transaction.
 *   upsolution-set-menu-dropdown — style one first-level item's mega-menu
 *                               dropdown (per-item us_mega_menu_settings:
 *                               columns, side panel, width, background, …).
 *
 * Transaction model mirrors upsolution-set-button-styles: every operation is
 * validated against and applied to an in-memory copy of the item tree first;
 * if any operation fails, the call returns the error and NOTHING is written.
 * Unlike button styles (one option row) menu items are separate DB rows, so
 * the guarantee is validation-level — a DB failure mid-persist (rare
 * infrastructure case) is reported explicitly with what had been written.
 *
 * Storage facts the implementation leans on:
 *   - Item hierarchy lives in the `_menu_item_menu_item_parent` meta, NOT in
 *     post_parent (wp_update_nav_menu_item() repurposes post_parent for the
 *     linked object's own parent). Deleting an item never cascades in core;
 *     both child policies (reparent / cascade) are implemented here.
 *   - Rendered order is the global `menu_order` sequence over the depth-first
 *     flattened tree — recomputed and renumbered whenever an operation
 *     changes the structure.
 *   - wp_update_nav_menu_item() resets every arg it is not given, so the
 *     persist phase always writes an item's COMPLETE state assembled from
 *     the simulated tree — never a partial patch.
 *   - An empty stored title on a post_type / taxonomy / post_type_archive
 *     item means "inherit the linked object's current title" (it then
 *     follows renames automatically). Custom links have no inherit source,
 *     so their title is required.
 *
 * The menu objects themselves (create / rename / delete a menu, assign it to
 * a location) are NOT covered here — as of us-core 8.46 those remain
 * wp-admin territory (Appearance → Menus). The abilities operate on items
 * of menus that already exist.
 *
 * @package usCore\Mcp
 */

defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Hard cap on the number of items in one menu. Far above real-world menus
 * (mega-menus peak around 100 entries); guards against a runaway agent loop
 * bloating the posts table before anything else notices.
 */
const US_MCP_MENU_ITEMS_MAX_COUNT = 300;

/**
 * Permission gate for the mutating menus ability — the same capability
 * wp-admin requires for the Appearance → Menus screen. The read-only
 * abilities in this file use the broad transport gate instead (menu
 * structure is rendered publicly on the site anyway).
 *
 * @return bool
 */
function us_mcp_menus_permission_callback() {
	return current_user_can( 'edit_theme_options' );
}

/**
 * Whether a value is an integer literal — a real int, or an all-digits string
 * (surrounding whitespace tolerated). Shared by every `*_id` / position / count
 * input check; callers apply their own >0 / range test afterwards.
 *
 * @param mixed $value
 * @return bool
 */
function us_mcp_menus_looks_like_int( $value ) {
	return is_int( $value ) OR ( is_string( $value ) AND ctype_digit( trim( $value ) ) );
}

/**
 * Resolve and validate the `menu_id` input into a nav_menu term.
 *
 * @param array $input
 * @return WP_Term|WP_Error
 */
function us_mcp_menus_resolve_menu( $input ) {
	$menu_id = isset( $input['menu_id'] ) ? $input['menu_id'] : NULL;
	if ( ! us_mcp_menus_looks_like_int( $menu_id ) ) {
		return new WP_Error(
			'us_mcp_menus_bad_menu_id',
			'`menu_id` must be a positive integer — get it from upsolution-list-menus.',
			array( 'status' => 400 )
		);
	}
	$menu = wp_get_nav_menu_object( (int) $menu_id );
	if ( ! $menu ) {
		$known = array();
		foreach ( wp_get_nav_menus() as $term ) {
			$known[] = sprintf( '%d "%s"', $term->term_id, $term->name );
		}
		return new WP_Error(
			'us_mcp_menus_menu_not_found',
			sprintf(
				'No menu with id=%d on this site. Existing menus: %s.',
				(int) $menu_id,
				empty( $known ) ? '(none — create one under Appearance → Menus first)' : implode( ', ', $known )
			),
			array( 'status' => 404 )
		);
	}
	return $menu;
}

/**
 * Read one menu's items into the working-state shape every other helper
 * consumes:
 *
 *   records  item id => normalised field record (see below)
 *   parent   item id => parent item id (0 = top level)
 *   children parent id => ordered list of child item ids (key 0 = roots)
 *
 * A record carries the agent-writable fields in their RAW stored form
 * (`title` is post_title — '' means "inherit"; `description` is untrimmed
 * post_content) plus bookkeeping: `parent_raw` / `menu_order` as stored in
 * the DB (used by the persist diff), `invalid` (linked object deleted) and
 * `is_new` (created by an add op in the current call, no DB row yet).
 *
 * Items whose stored parent id does not exist in the menu (orphans left by
 * an out-of-band deletion) are attached to the top level — exactly the way
 * get-menu presents them. Their stored parent is corrected on the first
 * set-menu-items write.
 *
 * @param int $menu_id
 * @return array{records: array, parent: array, children: array}|WP_Error
 */
function us_mcp_menus_read_state( $menu_id ) {
	$items = wp_get_nav_menu_items( (int) $menu_id );
	if ( $items === FALSE ) {
		return new WP_Error(
			'us_mcp_menus_menu_not_found',
			sprintf( 'Menu id=%d could not be read.', (int) $menu_id ),
			array( 'status' => 404 )
		);
	}

	$state = array(
		'records'  => array(),
		'parent'   => array(),
		'children' => array( 0 => array() ),
	);

	foreach ( $items as $item ) {
		$id = (int) $item->ID;
		$classes = array();
		foreach ( (array) $item->classes as $token ) {
			$token = trim( (string) $token );
			if ( $token !== '' ) {
				$classes[] = $token;
			}
		}
		// A us_page_block-backed item is stored by WordPress as a plain
		// post_type item (type=post_type, object=us_page_block). It renders
		// nothing like a link — the walker (common/functions/menu.php) embeds
		// the Reusable Block's content into the dropdown — so we surface it as
		// its own `reusable_block` type and carry the block-specific
		// `_menu_item_remove_rows` flag (strip the block's wrapping vc_row /
		// vc_column shortcodes so its content flows into the menu's own grid).
		$is_page_block = ( $item->type === 'post_type' AND $item->object === 'us_page_block' );
		$state['records'][ $id ] = array(
			'type'        => $is_page_block ? 'reusable_block' : (string) $item->type,
			'object'      => (string) $item->object,
			'object_id'   => (int) $item->object_id,
			'title'       => (string) $item->post_title,
			'url'         => ( $item->type === 'custom' ) ? (string) $item->url : '',
			'target'      => (string) $item->target,
			'classes'     => $classes,
			'xfn'         => (string) $item->xfn,
			// Raw post_content, not the decorated `description` (that one is
			// wp_trim_words()'d). wp_update_nav_menu_item() stores ' ' when
			// both title and description are empty — trim() folds that
			// storage artefact back to ''.
			'description' => trim( (string) $item->post_content ),
			'attr_title'  => (string) $item->post_excerpt,
			// Absent meta reads as FALSE — and the walker treats a falsy value
			// as "keep the rows" too, so this matches render behaviour exactly.
			'remove_rows' => $is_page_block ? (bool) get_post_meta( $id, '_menu_item_remove_rows', TRUE ) : FALSE,
			// `_menu_item_btn_style` ("Show as Button") is human-set styling we
			// only PRESERVE, never edit. Like remove_rows it lives outside
			// wp_update_nav_menu_item(), and the admin-side
			// us_update_menu_custom_field hook DELETES it on every save that
			// lacks the POST field — i.e. every MCP write. Carry the stored value
			// so persist can re-assert it after a rewrite. Only non-reusable_block
			// items can hold it (the hook manages btn-style on its
			// non-us_page_block branch).
			'btn_style'   => $is_page_block ? '' : (string) get_post_meta( $id, '_menu_item_btn_style', TRUE ),
			// Mega-menu dropdown settings (only honoured on first-level items).
			// Surfaced by state_tree at depth 0; edited via set-menu-dropdown.
			'dropdown_raw' => us_mcp_menu_dropdown_read( $id ),
			'invalid'     => ! empty( $item->_invalid ),
			'parent_raw'  => (int) $item->menu_item_parent,
			'menu_order'  => (int) $item->menu_order,
			'is_new'      => FALSE,
		);
	}

	// Hierarchy pass. $items comes sorted by menu_order, so appending each id
	// to its parent's list preserves the rendered order within every parent.
	foreach ( $items as $item ) {
		$id = (int) $item->ID;
		$pid = (int) $item->menu_item_parent;
		if ( $pid !== 0 AND ! isset( $state['records'][ $pid ] ) ) {
			$pid = 0; // orphan — surface at top level
		}
		$state['parent'][ $id ] = $pid;
		$state['children'][ $pid ][] = $id;
		if ( ! isset( $state['children'][ $id ] ) ) {
			$state['children'][ $id ] = array();
		}
	}

	return $state;
}

/**
 * The title a record renders when its stored title is '' — the linked
 * object's CURRENT name.
 *
 * @param array $rec
 * @return string
 */
function us_mcp_menus_inherited_title( array $rec ) {
	switch ( $rec['type'] ) {
		case 'post_type':
		case 'reusable_block':
			$post = get_post( $rec['object_id'] );
			return $post ? (string) $post->post_title : '';
		case 'taxonomy':
			$term = get_term( $rec['object_id'] );
			return ( $term AND ! is_wp_error( $term ) ) ? (string) $term->name : '';
		case 'post_type_archive':
			$pt_obj = get_post_type_object( $rec['object'] );
			return $pt_obj ? (string) $pt_obj->labels->archives : '';
	}
	return '';
}

/**
 * The URL a record currently resolves to (live permalink for object-backed
 * items, the stored value for custom links). '' when the linked object is
 * gone.
 *
 * @param array $rec
 * @return string
 */
function us_mcp_menus_item_url( array $rec ) {
	switch ( $rec['type'] ) {
		case 'custom':
			return (string) $rec['url'];
		case 'post_type':
			$permalink = get_permalink( $rec['object_id'] );
			return is_string( $permalink ) ? $permalink : '';
		case 'taxonomy':
			$link = get_term_link( (int) $rec['object_id'] );
			return is_wp_error( $link ) ? '' : (string) $link;
		case 'post_type_archive':
			$link = get_post_type_archive_link( $rec['object'] );
			return is_string( $link ) ? $link : '';
	}
	return '';
}

/**
 * Render the working state as the agent-facing nested tree (the `items` of
 * get-menu and the before / after snapshots of set-menu-items). Empty
 * optional fields are omitted to keep the payload readable.
 *
 * @param array $state
 * @param int|string $parent
 * @param int $depth  Tree depth; mega-menu dropdown settings are surfaced only
 *                    at depth 0 (the only level the theme honours them).
 * @return array
 */
function us_mcp_menus_state_tree( array $state, $parent = 0, $depth = 0 ) {
	$out = array();
	if ( ! isset( $state['children'][ $parent ] ) ) {
		return $out;
	}
	foreach ( $state['children'][ $parent ] as $id ) {
		$rec = $state['records'][ $id ];
		$node = array(
			'id'    => $id,
			'title' => ( $rec['title'] !== '' ) ? $rec['title'] : us_mcp_menus_inherited_title( $rec ),
			'type'  => $rec['type'],
		);
		if ( $rec['title'] === '' AND $rec['type'] !== 'custom' ) {
			$node['title_inherited'] = TRUE;
		}
		if ( $rec['type'] === 'post_type' OR $rec['type'] === 'taxonomy' OR $rec['type'] === 'reusable_block' ) {
			$node['object'] = $rec['object'];
			$node['object_id'] = (int) $rec['object_id'];
		} elseif ( $rec['type'] === 'post_type_archive' ) {
			$node['object'] = $rec['object'];
		}
		if ( $rec['type'] === 'reusable_block' ) {
			// No front-end URL — the block's content is embedded into the
			// parent's dropdown. `remove_rows` is the one block-specific knob.
			$node['remove_rows'] = (bool) $rec['remove_rows'];
		} else {
			$node['url'] = us_mcp_menus_item_url( $rec );
		}
		foreach ( array( 'target', 'attr_title', 'description', 'xfn' ) as $optional ) {
			if ( $rec[ $optional ] !== '' ) {
				$node[ $optional ] = $rec[ $optional ];
			}
		}
		if ( ! empty( $rec['classes'] ) ) {
			$node['classes'] = implode( ' ', $rec['classes'] );
		}
		if ( ! empty( $rec['invalid'] ) ) {
			$node['invalid'] = TRUE;
		}
		// Mega-menu dropdown styling is read off the first level only.
		if ( $depth === 0 AND ! empty( $rec['dropdown_raw'] ) AND is_array( $rec['dropdown_raw'] ) ) {
			$node['dropdown'] = us_mcp_menu_dropdown_for_output( $rec['dropdown_raw'] );
		}
		$node['children'] = us_mcp_menus_state_tree( $state, $id, $depth + 1 );
		$out[] = $node;
	}
	return $out;
}

/**
 * Validate a single-line text field. Returns the trimmed string or WP_Error.
 *
 * @param string $key
 * @param mixed  $value
 * @param int    $idx              Operation index (for error messages).
 * @param bool   $allow_multiline  TRUE for `description`.
 * @return string|WP_Error
 */
function us_mcp_menus_check_text( $key, $value, $idx, $allow_multiline = FALSE ) {
	if ( ! is_string( $value ) AND ! is_numeric( $value ) ) {
		return new WP_Error(
			'us_mcp_menus_bad_text',
			sprintf( 'operations[%d]: field "%s" must be a string.', $idx, $key ),
			array( 'status' => 400 )
		);
	}
	$value = trim( (string) $value );
	if ( preg_match( $allow_multiline ? '/[\t]/' : '/[\r\n\t]/', $value ) ) {
		return new WP_Error(
			'us_mcp_menus_bad_text_chars',
			sprintf( 'operations[%d]: field "%s" must not contain %s.', $idx, $key, $allow_multiline ? 'tabs' : 'line breaks or tabs' ),
			array( 'status' => 400 )
		);
	}
	return $value;
}

/**
 * Validate a custom-link URL. Mild: any non-empty single-line string that
 * survives esc_url_raw() — absolute http(s), site-relative "/path", "#anchor",
 * mailto: / tel: all pass; schemes outside wp_allowed_protocols() do not.
 *
 * @param mixed $value
 * @param int   $idx
 * @return string|WP_Error
 */
function us_mcp_menus_check_url( $value, $idx ) {
	if ( ! is_string( $value ) OR trim( $value ) === '' ) {
		return new WP_Error(
			'us_mcp_menus_bad_url',
			sprintf( 'operations[%d]: `url` must be a non-empty string.', $idx ),
			array( 'status' => 400 )
		);
	}
	$value = trim( $value );
	if ( preg_match( '/[\r\n\t ]/', $value ) ) {
		return new WP_Error(
			'us_mcp_menus_bad_url',
			sprintf( 'operations[%d]: `url` must not contain whitespace.', $idx ),
			array( 'status' => 400 )
		);
	}
	if ( esc_url_raw( $value ) === '' ) {
		return new WP_Error(
			'us_mcp_menus_bad_url',
			sprintf( 'operations[%d]: URL "%s" is not accepted by WordPress URL sanitization. Use an absolute http(s):// URL, a site-relative path ("/about/"), an anchor ("#contact"), or a mailto: / tel: link.', $idx, $value ),
			array( 'status' => 400 )
		);
	}
	return $value;
}

/**
 * Validate `target`. The admin UI stores exactly two values: '' (same tab)
 * and '_blank' (new tab).
 *
 * @param mixed $value
 * @param int   $idx
 * @return string|WP_Error
 */
function us_mcp_menus_check_target( $value, $idx ) {
	if ( ! is_string( $value ) OR ! in_array( $value, array( '', '_blank' ), TRUE ) ) {
		return new WP_Error(
			'us_mcp_menus_bad_target',
			sprintf( 'operations[%d]: `target` must be "" (same tab) or "_blank" (new tab).', $idx ),
			array( 'status' => 400 )
		);
	}
	return $value;
}

/**
 * Validate `classes` (and `xfn`, which shares the storage path). WordPress
 * runs every token through sanitize_html_class() on save, silently mangling
 * anything outside [A-Za-z0-9_-] — rejecting such tokens upfront beats
 * storing a class the agent did not write.
 *
 * @param mixed  $value  Space-separated string or array of tokens.
 * @param string $key    'classes' or 'xfn' (error messages).
 * @param int    $idx
 * @return array|WP_Error Deduped token list.
 */
function us_mcp_menus_check_class_list( $value, $key, $idx ) {
	if ( is_string( $value ) ) {
		$tokens = preg_split( '/\s+/', trim( $value ) );
	} elseif ( is_array( $value ) ) {
		$tokens = $value;
	} else {
		return new WP_Error(
			'us_mcp_menus_bad_class_list',
			sprintf( 'operations[%d]: `%s` must be a space-separated string or an array of tokens.', $idx, $key ),
			array( 'status' => 400 )
		);
	}
	$clean = array();
	foreach ( $tokens as $token ) {
		if ( ! is_scalar( $token ) ) {
			return new WP_Error(
				'us_mcp_menus_bad_class_token',
				sprintf( 'operations[%d]: every `%s` token must be a string.', $idx, $key ),
				array( 'status' => 400 )
			);
		}
		$token = trim( (string) $token );
		if ( $token === '' ) {
			continue;
		}
		if ( sanitize_html_class( $token ) !== $token ) {
			return new WP_Error(
				'us_mcp_menus_bad_class_token',
				sprintf( 'operations[%d]: `%s` token "%s" contains characters WordPress strips on save. Use letters, digits, "-" and "_" only.', $idx, $key, $token ),
				array( 'status' => 400 )
			);
		}
		if ( ! in_array( $token, $clean, TRUE ) ) {
			$clean[] = $token;
		}
	}
	return $clean;
}

/**
 * Validate the `fields` object of an `add` operation into a complete new
 * item record. Enforces the per-type field matrix (see the design/menus doc):
 * custom needs url + title; post_type / taxonomy need an existing object_id
 * (`object` is derived from it); post_type_archive needs an archive-enabled
 * `object`.
 *
 * @param mixed $fields
 * @param int   $idx
 * @return array|WP_Error
 */
function us_mcp_menus_validate_add_fields( $fields, $idx ) {
	if ( ! is_array( $fields ) ) {
		return new WP_Error(
			'us_mcp_menus_fields_not_object',
			sprintf( 'operations[%d]: `fields` must be an object with the new item\'s values.', $idx ),
			array( 'status' => 400 )
		);
	}
	$allowed = array( 'type', 'title', 'url', 'object', 'object_id', 'target', 'classes', 'xfn', 'description', 'attr_title', 'remove_rows' );
	foreach ( array_keys( $fields ) as $key ) {
		if ( ! in_array( (string) $key, $allowed, TRUE ) ) {
			return new WP_Error(
				'us_mcp_menus_unknown_field',
				sprintf( 'operations[%d]: unknown item field "%s". Allowed fields: %s.', $idx, $key, implode( ', ', $allowed ) ),
				array( 'status' => 400 )
			);
		}
	}

	// Resolve the item type. Explicit `type` wins; a `url` with no type can
	// only mean a custom link; anything else is ambiguous.
	$type = isset( $fields['type'] ) ? $fields['type'] : NULL;
	if ( $type === NULL ) {
		if ( isset( $fields['url'] ) ) {
			$type = 'custom';
		} else {
			return new WP_Error(
				'us_mcp_menus_missing_type',
				sprintf( 'operations[%d]: pass `type` — "post_type" (a page / post, identified by object_id), "taxonomy" (a term, identified by object_id), "post_type_archive" (object = post type name), "reusable_block" (a Reusable Block embedded in the dropdown, identified by object_id) or "custom" (url + title). Only a custom link (when `url` is present) may omit it.', $idx ),
				array( 'status' => 400 )
			);
		}
	}
	if ( ! in_array( $type, array( 'custom', 'post_type', 'taxonomy', 'post_type_archive', 'reusable_block' ), TRUE ) ) {
		return new WP_Error(
			'us_mcp_menus_bad_type',
			sprintf( 'operations[%d]: unknown item type "%s". Allowed: custom, post_type, taxonomy, post_type_archive, reusable_block.', $idx, is_scalar( $type ) ? (string) $type : gettype( $type ) ),
			array( 'status' => 400 )
		);
	}

	$rec = array(
		'type'        => $type,
		'object'      => '',
		'object_id'   => 0,
		'title'       => '',
		'url'         => '',
		'target'      => '',
		'classes'     => array(),
		'xfn'         => '',
		'description' => '',
		'attr_title'  => '',
		'remove_rows' => FALSE,
		// Preserved-only (never edited via MCP); a brand-new item never carries
		// a "Show as Button" style, so the persist re-assert below is a no-op.
		'btn_style'   => '',
		'dropdown_raw' => NULL,
		'invalid'     => FALSE,
		'parent_raw'  => 0,
		'menu_order'  => 0,
		'is_new'      => TRUE,
	);

	if ( isset( $fields['remove_rows'] ) AND $type !== 'reusable_block' ) {
		return new WP_Error(
			'us_mcp_menus_field_not_applicable',
			sprintf( 'operations[%d]: `remove_rows` only applies to reusable_block items.', $idx ),
			array( 'status' => 400 )
		);
	}

	// --- Type-specific identifying fields --------------------------------
	if ( $type === 'custom' ) {
		foreach ( array( 'object', 'object_id' ) as $bad ) {
			if ( isset( $fields[ $bad ] ) ) {
				return new WP_Error(
					'us_mcp_menus_field_not_applicable',
					sprintf( 'operations[%d]: `%s` does not apply to custom links — they are identified by `url` alone.', $idx, $bad ),
					array( 'status' => 400 )
				);
			}
		}
		if ( ! isset( $fields['url'] ) ) {
			return new WP_Error(
				'us_mcp_menus_missing_url',
				sprintf( 'operations[%d]: custom links require `url`.', $idx ),
				array( 'status' => 400 )
			);
		}
		$url = us_mcp_menus_check_url( $fields['url'], $idx );
		if ( is_wp_error( $url ) ) {
			return $url;
		}
		$rec['url'] = $url;
		$rec['object'] = 'custom';
		if ( ! isset( $fields['title'] ) OR trim( (string) ( is_scalar( $fields['title'] ) ? $fields['title'] : '' ) ) === '' ) {
			return new WP_Error(
				'us_mcp_menus_missing_title',
				sprintf( 'operations[%d]: custom links require a non-empty `title` — they have no linked object to inherit a label from.', $idx ),
				array( 'status' => 400 )
			);
		}
	} elseif ( $type === 'post_type' OR $type === 'taxonomy' ) {
		if ( isset( $fields['url'] ) ) {
			return new WP_Error(
				'us_mcp_menus_field_not_applicable',
				sprintf( 'operations[%d]: `url` only applies to custom links — a %s item always links to its object\'s current URL.', $idx, $type ),
				array( 'status' => 400 )
			);
		}
		$object_id = isset( $fields['object_id'] ) ? $fields['object_id'] : NULL;
		if ( ! us_mcp_menus_looks_like_int( $object_id ) ) {
			return new WP_Error(
				'us_mcp_menus_bad_object_id',
				sprintf( 'operations[%d]: a %s item requires `object_id` — a positive integer %s id.', $idx, $type, ( $type === 'post_type' ) ? 'post / page (see upsolution-list-posts)' : 'term (see upsolution-list-terms)' ),
				array( 'status' => 400 )
			);
		}
		$object_id = (int) $object_id;
		$derived = us_mcp_menus_check_object( $type, $object_id, $idx );
		if ( is_wp_error( $derived ) ) {
			return $derived;
		}
		if ( isset( $fields['object'] ) AND (string) $fields['object'] !== $derived ) {
			return new WP_Error(
				'us_mcp_menus_object_mismatch',
				sprintf( 'operations[%d]: `object` "%s" does not match what object_id=%d resolves to ("%s"). Omit `object` — it is derived from the id.', $idx, (string) $fields['object'], $object_id, $derived ),
				array( 'status' => 400 )
			);
		}
		$rec['object'] = $derived;
		$rec['object_id'] = $object_id;
	} elseif ( $type === 'reusable_block' ) {
		// A Reusable Block (us_page_block) embedded into the parent's dropdown.
		// It renders no anchor, so the link-only fields do not apply.
		foreach ( array( 'url', 'object', 'target', 'xfn', 'description', 'attr_title' ) as $bad ) {
			if ( isset( $fields[ $bad ] ) ) {
				return new WP_Error(
					'us_mcp_menus_field_not_applicable',
					sprintf( 'operations[%d]: `%s` does not apply to reusable_block items — they embed the block\'s content into the dropdown instead of rendering a link. Applicable fields: object_id, remove_rows, title (admin label), classes.', $idx, $bad ),
					array( 'status' => 400 )
				);
			}
		}
		$object_id = isset( $fields['object_id'] ) ? $fields['object_id'] : NULL;
		if ( ! us_mcp_menus_looks_like_int( $object_id ) ) {
			return new WP_Error(
				'us_mcp_menus_bad_object_id',
				sprintf( 'operations[%d]: a reusable_block item requires `object_id` — the id of a Reusable Block (resolve names to ids via upsolution-list-posts with post_type="us_page_block").', $idx ),
				array( 'status' => 400 )
			);
		}
		$check = us_mcp_menus_check_page_block( (int) $object_id, $idx );
		if ( is_wp_error( $check ) ) {
			return $check;
		}
		$rec['object'] = 'us_page_block';
		$rec['object_id'] = (int) $object_id;
		// "Exclude Rows and Columns" — enabled by default in the admin metabox,
		// so a block authored as a standalone page flows into the menu's grid.
		$rec['remove_rows'] = array_key_exists( 'remove_rows', $fields )
			? us_mcp_menus_check_bool( $fields['remove_rows'], 'remove_rows', $idx )
			: TRUE;
		if ( is_wp_error( $rec['remove_rows'] ) ) {
			return $rec['remove_rows'];
		}
	} else { // post_type_archive
		foreach ( array( 'url', 'object_id' ) as $bad ) {
			if ( isset( $fields[ $bad ] ) ) {
				return new WP_Error(
					'us_mcp_menus_field_not_applicable',
					sprintf( 'operations[%d]: `%s` does not apply to post_type_archive items — they are identified by `object` (the post type name).', $idx, $bad ),
					array( 'status' => 400 )
				);
			}
		}
		$object = isset( $fields['object'] ) ? $fields['object'] : NULL;
		if ( ! is_string( $object ) OR $object === '' ) {
			return new WP_Error(
				'us_mcp_menus_bad_object',
				sprintf( 'operations[%d]: a post_type_archive item requires `object` — the post type name whose archive it links to.', $idx ),
				array( 'status' => 400 )
			);
		}
		$pt_obj = get_post_type_object( $object );
		if ( ! $pt_obj ) {
			return new WP_Error(
				'us_mcp_menus_object_not_found',
				sprintf( 'operations[%d]: post type "%s" is not registered on this site.', $idx, $object ),
				array( 'status' => 404 )
			);
		}
		if ( empty( $pt_obj->has_archive ) ) {
			return new WP_Error(
				'us_mcp_menus_no_archive',
				sprintf( 'operations[%d]: post type "%s" has no archive page — the item would render a dead link.', $idx, $object ),
				array( 'status' => 400 )
			);
		}
		$rec['object'] = $object;
	}

	// --- Shared optional fields -------------------------------------------
	if ( isset( $fields['title'] ) ) {
		$title = us_mcp_menus_check_text( 'title', $fields['title'], $idx );
		if ( is_wp_error( $title ) ) {
			return $title;
		}
		$rec['title'] = $title;
	}
	if ( isset( $fields['target'] ) ) {
		$target = us_mcp_menus_check_target( $fields['target'], $idx );
		if ( is_wp_error( $target ) ) {
			return $target;
		}
		$rec['target'] = $target;
	}
	if ( isset( $fields['classes'] ) ) {
		$classes = us_mcp_menus_check_class_list( $fields['classes'], 'classes', $idx );
		if ( is_wp_error( $classes ) ) {
			return $classes;
		}
		$rec['classes'] = $classes;
	}
	if ( isset( $fields['xfn'] ) ) {
		$xfn = us_mcp_menus_check_class_list( $fields['xfn'], 'xfn', $idx );
		if ( is_wp_error( $xfn ) ) {
			return $xfn;
		}
		$rec['xfn'] = implode( ' ', $xfn );
	}
	if ( isset( $fields['description'] ) ) {
		$description = us_mcp_menus_check_text( 'description', $fields['description'], $idx, /* allow_multiline */ TRUE );
		if ( is_wp_error( $description ) ) {
			return $description;
		}
		$rec['description'] = $description;
	}
	if ( isset( $fields['attr_title'] ) ) {
		$attr_title = us_mcp_menus_check_text( 'attr_title', $fields['attr_title'], $idx );
		if ( is_wp_error( $attr_title ) ) {
			return $attr_title;
		}
		$rec['attr_title'] = $attr_title;
	}

	return $rec;
}

/**
 * Validate that object_id points at a real, publicly linkable object and
 * derive the stored `object` value from it.
 *
 * @param string $type      'post_type' or 'taxonomy'.
 * @param int    $object_id
 * @param int    $idx
 * @return string|WP_Error  Derived `object` (post type name / taxonomy name).
 */
function us_mcp_menus_check_object( $type, $object_id, $idx ) {
	if ( $object_id < 1 ) {
		return new WP_Error(
			'us_mcp_menus_bad_object_id',
			sprintf( 'operations[%d]: `object_id` must be a positive integer.', $idx ),
			array( 'status' => 400 )
		);
	}
	if ( $type === 'post_type' ) {
		$post = get_post( $object_id );
		if ( ! $post OR in_array( $post->post_status, array( 'trash', 'auto-draft' ), TRUE ) ) {
			return new WP_Error(
				'us_mcp_menus_object_not_found',
				sprintf( 'operations[%d]: no post found with object_id=%d. Pass the id of an existing page / post — resolve titles to ids via upsolution-list-posts.', $idx, $object_id ),
				array( 'status' => 404 )
			);
		}
		if ( $post->post_type === 'us_page_block' ) {
			return new WP_Error(
				'us_mcp_menus_use_reusable_block',
				sprintf( 'operations[%d]: post id=%d is a Reusable Block — add it with type "reusable_block" (it embeds the block into the dropdown), not "post_type".', $idx, $object_id ),
				array( 'status' => 400 )
			);
		}
		if ( $post->post_type === 'nav_menu_item' OR ! is_post_type_viewable( $post->post_type ) ) {
			return new WP_Error(
				'us_mcp_menus_object_not_viewable',
				sprintf( 'operations[%d]: post id=%d is of type "%s", which has no public URL — it cannot be a menu destination.', $idx, $object_id, $post->post_type ),
				array( 'status' => 400 )
			);
		}
		return (string) $post->post_type;
	}
	// taxonomy
	$term = get_term( $object_id );
	if ( ! $term OR is_wp_error( $term ) ) {
		return new WP_Error(
			'us_mcp_menus_object_not_found',
			sprintf( 'operations[%d]: no term found with object_id=%d. Resolve term names to ids via upsolution-list-terms.', $idx, $object_id ),
			array( 'status' => 404 )
		);
	}
	if ( $term->taxonomy === 'nav_menu' ) {
		return new WP_Error(
			'us_mcp_menus_object_not_viewable',
			sprintf( 'operations[%d]: term id=%d is a navigation menu itself — menus cannot link to menus.', $idx, $object_id ),
			array( 'status' => 400 )
		);
	}
	if ( ! is_taxonomy_viewable( $term->taxonomy ) ) {
		return new WP_Error(
			'us_mcp_menus_object_not_viewable',
			sprintf( 'operations[%d]: term id=%d belongs to taxonomy "%s", which has no public URL — it cannot be a menu destination.', $idx, $object_id, $term->taxonomy ),
			array( 'status' => 400 )
		);
	}
	return (string) $term->taxonomy;
}

/**
 * Validate that object_id points at a real Reusable Block (us_page_block).
 * Unlike us_mcp_menus_check_object(), viewability is NOT required — a Reusable
 * Block has no public URL by design; its content is embedded into the dropdown.
 *
 * @param int $object_id
 * @param int $idx
 * @return true|WP_Error
 */
function us_mcp_menus_check_page_block( $object_id, $idx ) {
	if ( $object_id < 1 ) {
		return new WP_Error(
			'us_mcp_menus_bad_object_id',
			sprintf( 'operations[%d]: `object_id` must be a positive integer.', $idx ),
			array( 'status' => 400 )
		);
	}
	if ( ! post_type_exists( 'us_page_block' ) ) {
		return new WP_Error(
			'us_mcp_menus_no_page_block_type',
			sprintf( 'operations[%d]: the Reusable Blocks feature (us_page_block) is not available on this site.', $idx ),
			array( 'status' => 400 )
		);
	}
	$post = get_post( $object_id );
	if ( ! $post OR in_array( $post->post_status, array( 'trash', 'auto-draft' ), TRUE ) ) {
		return new WP_Error(
			'us_mcp_menus_object_not_found',
			sprintf( 'operations[%d]: no Reusable Block found with object_id=%d. List blocks via upsolution-list-posts with post_type="us_page_block".', $idx, $object_id ),
			array( 'status' => 404 )
		);
	}
	if ( $post->post_type !== 'us_page_block' ) {
		return new WP_Error(
			'us_mcp_menus_not_a_page_block',
			sprintf( 'operations[%d]: post id=%d is a "%s", not a Reusable Block. For a normal page / post link use type "post_type".', $idx, $object_id, $post->post_type ),
			array( 'status' => 400 )
		);
	}
	return TRUE;
}

/**
 * Coerce a boolean-ish input. Accepts real booleans, 0/1, "0"/"1",
 * "true"/"false". Returns bool or WP_Error.
 *
 * @param mixed  $value
 * @param string $key
 * @param int    $idx
 * @return bool|WP_Error
 */
function us_mcp_menus_check_bool( $value, $key, $idx ) {
	if ( is_bool( $value ) ) {
		return $value;
	}
	if ( $value === 1 OR $value === '1' OR $value === 'true' ) {
		return TRUE;
	}
	if ( $value === 0 OR $value === '0' OR $value === 'false' ) {
		return FALSE;
	}
	return new WP_Error(
		'us_mcp_menus_bad_bool',
		sprintf( 'operations[%d]: `%s` must be a boolean (true / false).', $idx, $key ),
		array( 'status' => 400 )
	);
}

/**
 * Validate the `fields` object of an `update` operation into a partial patch
 * against an existing record. null (or '' for title) clears: title falls
 * back to the linked object's current name, the cosmetic fields to ''.
 * `type` is immutable; `object` is always derived from `object_id`.
 *
 * @param mixed $fields
 * @param array $rec    The record being patched (type gates field validity).
 * @param int   $idx
 * @return array|WP_Error Patch map; values ready to assign.
 */
function us_mcp_menus_validate_patch_fields( $fields, array $rec, $idx ) {
	if ( ! is_array( $fields ) OR empty( $fields ) ) {
		return new WP_Error(
			'us_mcp_menus_empty_patch',
			sprintf( 'operations[%d]: pass at least one field to update in `fields`.', $idx ),
			array( 'status' => 400 )
		);
	}
	if ( array_key_exists( 'type', $fields ) ) {
		return new WP_Error(
			'us_mcp_menus_type_immutable',
			sprintf( 'operations[%d]: an item\'s `type` is immutable — remove the item and add a new one instead.', $idx ),
			array( 'status' => 400 )
		);
	}
	if ( array_key_exists( 'object', $fields ) ) {
		return new WP_Error(
			'us_mcp_menus_object_not_writable',
			sprintf( 'operations[%d]: `object` is derived from `object_id` — to repoint a post_type / taxonomy item pass a new `object_id`; to change a post_type_archive item remove it and add a new one.', $idx ),
			array( 'status' => 400 )
		);
	}
	$allowed = array( 'title', 'url', 'object_id', 'target', 'classes', 'xfn', 'description', 'attr_title', 'remove_rows' );
	foreach ( array_keys( $fields ) as $key ) {
		if ( ! in_array( (string) $key, $allowed, TRUE ) ) {
			return new WP_Error(
				'us_mcp_menus_unknown_field',
				sprintf( 'operations[%d]: unknown item field "%s". Allowed in update: %s. Moving an item is a `reorder` operation, not a field.', $idx, $key, implode( ', ', $allowed ) ),
				array( 'status' => 400 )
			);
		}
	}
	// A reusable_block renders no anchor, so only the block-relevant fields
	// apply — gate them up front instead of relying on each case below.
	if ( $rec['type'] === 'reusable_block' ) {
		$block_fields = array( 'title', 'object_id', 'remove_rows', 'classes' );
		foreach ( array_keys( $fields ) as $key ) {
			if ( ! in_array( (string) $key, $block_fields, TRUE ) ) {
				return new WP_Error(
					'us_mcp_menus_field_not_applicable',
					sprintf( 'operations[%d]: `%s` does not apply to a reusable_block item. Editable fields: object_id (repoint to another block), remove_rows, title (admin label), classes.', $idx, $key ),
					array( 'status' => 400 )
				);
			}
		}
	} elseif ( array_key_exists( 'remove_rows', $fields ) ) {
		return new WP_Error(
			'us_mcp_menus_field_not_applicable',
			sprintf( 'operations[%d]: `remove_rows` only applies to reusable_block items.', $idx ),
			array( 'status' => 400 )
		);
	}

	$patch = array();
	foreach ( $fields as $key => $value ) {
		switch ( (string) $key ) {
			case 'title':
				if ( $value === NULL OR ( is_string( $value ) AND trim( $value ) === '' ) ) {
					if ( $rec['type'] === 'custom' ) {
						return new WP_Error(
							'us_mcp_menus_missing_title',
							sprintf( 'operations[%d]: a custom link\'s `title` cannot be cleared — there is no linked object to inherit a label from.', $idx ),
							array( 'status' => 400 )
						);
					}
					$patch['title'] = ''; // inherit the object's current name
				} else {
					$title = us_mcp_menus_check_text( 'title', $value, $idx );
					if ( is_wp_error( $title ) ) {
						return $title;
					}
					$patch['title'] = $title;
				}
				break;

			case 'url':
				if ( $rec['type'] !== 'custom' ) {
					return new WP_Error(
						'us_mcp_menus_field_not_applicable',
						sprintf( 'operations[%d]: `url` only applies to custom links — this %s item always links to its object\'s current URL. Pass `object_id` to repoint it.', $idx, $rec['type'] ),
						array( 'status' => 400 )
					);
				}
				$url = us_mcp_menus_check_url( $value, $idx );
				if ( is_wp_error( $url ) ) {
					return $url;
				}
				$patch['url'] = $url;
				break;

			case 'object_id':
				if ( $rec['type'] !== 'post_type' AND $rec['type'] !== 'taxonomy' AND $rec['type'] !== 'reusable_block' ) {
					return new WP_Error(
						'us_mcp_menus_field_not_applicable',
						sprintf( 'operations[%d]: `object_id` only applies to post_type / taxonomy / reusable_block items — this is a %s item.', $idx, $rec['type'] ),
						array( 'status' => 400 )
					);
				}
				if ( ! us_mcp_menus_looks_like_int( $value ) ) {
					return new WP_Error(
						'us_mcp_menus_bad_object_id',
						sprintf( 'operations[%d]: `object_id` must be a positive integer.', $idx ),
						array( 'status' => 400 )
					);
				}
				if ( $rec['type'] === 'reusable_block' ) {
					$check = us_mcp_menus_check_page_block( (int) $value, $idx );
					if ( is_wp_error( $check ) ) {
						return $check;
					}
					$patch['object'] = 'us_page_block';
				} else {
					$derived = us_mcp_menus_check_object( $rec['type'], (int) $value, $idx );
					if ( is_wp_error( $derived ) ) {
						return $derived;
					}
					$patch['object'] = $derived;
				}
				$patch['object_id'] = (int) $value;
				$patch['invalid'] = FALSE; // repointing heals a dead item
				break;

			case 'remove_rows':
				$remove_rows = us_mcp_menus_check_bool( $value, 'remove_rows', $idx );
				if ( is_wp_error( $remove_rows ) ) {
					return $remove_rows;
				}
				$patch['remove_rows'] = $remove_rows;
				break;

			case 'target':
				$target = us_mcp_menus_check_target( $value === NULL ? '' : $value, $idx );
				if ( is_wp_error( $target ) ) {
					return $target;
				}
				$patch['target'] = $target;
				break;

			case 'classes':
				$classes = us_mcp_menus_check_class_list( $value === NULL ? array() : $value, 'classes', $idx );
				if ( is_wp_error( $classes ) ) {
					return $classes;
				}
				$patch['classes'] = $classes;
				break;

			case 'xfn':
				$xfn = us_mcp_menus_check_class_list( $value === NULL ? array() : $value, 'xfn', $idx );
				if ( is_wp_error( $xfn ) ) {
					return $xfn;
				}
				$patch['xfn'] = implode( ' ', $xfn );
				break;

			case 'description':
				$description = ( $value === NULL ) ? '' : us_mcp_menus_check_text( 'description', $value, $idx, /* allow_multiline */ TRUE );
				if ( is_wp_error( $description ) ) {
					return $description;
				}
				$patch['description'] = $description;
				break;

			case 'attr_title':
				$attr_title = ( $value === NULL ) ? '' : us_mcp_menus_check_text( 'attr_title', $value, $idx );
				if ( is_wp_error( $attr_title ) ) {
					return $attr_title;
				}
				$patch['attr_title'] = $attr_title;
				break;
		}
	}
	return $patch;
}

/**
 * Resolve an item reference from operation input: a positive integer id of
 * an existing item, or a "new:<i>" token referencing the item created by the
 * `add` at operations[<i>] earlier in the same call. For $context 'parent',
 * 0 (top level) is also valid.
 *
 * @param mixed  $ref
 * @param array  $state
 * @param int    $idx      Index of the referencing operation.
 * @param string $context  'item' or 'parent' (error wording; 'parent' allows 0).
 * @return int|string|WP_Error Working-state key.
 */
function us_mcp_menus_resolve_ref( $ref, array $state, $idx, $context = 'item' ) {
	if ( us_mcp_menus_looks_like_int( $ref ) ) {
		$id = (int) $ref;
		if ( $id === 0 AND $context === 'parent' ) {
			return 0;
		}
		if ( $id > 0 AND isset( $state['records'][ $id ] ) ) {
			return $id;
		}
		return new WP_Error(
			'us_mcp_menus_id_not_found',
			sprintf( 'operations[%d]: no menu item with id=%d in this menu (it may have been removed by an earlier operation of this call). Current ids: %s.', $idx, $id, json_encode( array_keys( $state['records'] ) ) ),
			array( 'status' => 404 )
		);
	}
	if ( is_string( $ref ) AND preg_match( '~^new:(\d+)$~', trim( $ref ), $matches ) ) {
		$key = 'new:' . (int) $matches[1];
		if ( isset( $state['records'][ $key ] ) ) {
			return $key;
		}
		return new WP_Error(
			'us_mcp_menus_bad_token',
			sprintf( 'operations[%d]: token "%s" does not resolve — it must reference an `add` operation at an EARLIER index of this same call (and one whose item was not removed by a later operation).', $idx, trim( $ref ) ),
			array( 'status' => 400 )
		);
	}
	return new WP_Error(
		'us_mcp_menus_bad_id',
		sprintf( 'operations[%d]: %s must be a positive integer item id from upsolution-get-menu, or a "new:<i>" token referencing an earlier add of this call%s.', $idx, ( $context === 'parent' ) ? '`parent_id`' : '`id`', ( $context === 'parent' ) ? ' (0 / omitted = top level)' : '' ),
		array( 'status' => 400 )
	);
}

/**
 * Collect an item and all of its descendants (depth-first).
 *
 * @param array      $state
 * @param int|string $id
 * @return array Working-state keys, the item itself first.
 */
function us_mcp_menus_collect_subtree( array $state, $id ) {
	$out = array( $id );
	foreach ( $state['children'][ $id ] as $child_id ) {
		$out = array_merge( $out, us_mcp_menus_collect_subtree( $state, $child_id ) );
	}
	return $out;
}

/**
 * Validate the agent's `operations` AND apply them to the working state.
 * Pure simulation — no DB writes. Any failure leaves nothing half-applied
 * because the caller discards the state on WP_Error.
 *
 * Supported ops (each op accepts ONLY its own keys — strays are rejected so
 * a misplaced `position` on update fails loudly instead of being ignored):
 *   - add     { fields: {...}, parent_id?: id|"new:<i>", position?: int }
 *   - update  { id: id|"new:<i>", fields: {...} }
 *   - remove  { id: id|"new:<i>", children?: "reparent"|"cascade" }
 *   - reorder { tree: [ {id, children?: [...]} ] }
 *
 * @param array $state      Working state from us-mcp menus read (mutated).
 * @param mixed $operations
 * @return array{applied: array, structure_changed: bool}|WP_Error
 */
function us_mcp_menus_apply_operations( array &$state, $operations ) {
	if ( ! is_array( $operations ) OR empty( $operations ) ) {
		return new WP_Error(
			'us_mcp_menus_no_op',
			'Pass at least one operation in `operations` (add / update / remove / reorder).',
			array( 'status' => 400 )
		);
	}

	$applied = array();
	$structure_changed = FALSE;

	// Per-op key whitelist (constant) — strays are rejected so a misplaced
	// `position` on update fails loudly instead of being silently ignored.
	$op_keys = array(
		'add'     => array( 'op', 'fields', 'parent_id', 'position' ),
		'update'  => array( 'op', 'id', 'fields' ),
		'remove'  => array( 'op', 'id', 'children' ),
		'reorder' => array( 'op', 'tree' ),
	);

	foreach ( $operations as $idx => $op_entry ) {
		if ( ! is_array( $op_entry ) ) {
			return new WP_Error(
				'us_mcp_menus_bad_op',
				sprintf( 'operations[%d] must be an object.', $idx ),
				array( 'status' => 400 )
			);
		}
		$op = isset( $op_entry['op'] ) ? (string) $op_entry['op'] : '';
		if ( ! isset( $op_keys[ $op ] ) ) {
			return new WP_Error(
				'us_mcp_menus_unknown_op',
				sprintf( 'operations[%d]: unknown op "%s". Allowed: add, update, remove, reorder.', $idx, $op ),
				array( 'status' => 400 )
			);
		}
		foreach ( array_keys( $op_entry ) as $key ) {
			if ( ! in_array( (string) $key, $op_keys[ $op ], TRUE ) ) {
				return new WP_Error(
					'us_mcp_menus_op_key_not_allowed',
					sprintf( 'operations[%d]: key "%s" does not apply to op "%s" (accepted: %s).%s', $idx, $key, $op, implode( ', ', $op_keys[ $op ] ), ( $op === 'update' AND in_array( (string) $key, array( 'position', 'parent_id' ), TRUE ) ) ? ' Moving an existing item is done with a `reorder` op carrying the full target tree.' : '' ),
					array( 'status' => 400 )
				);
			}
		}

		switch ( $op ) {
			case 'add':
				if ( count( $state['records'] ) >= US_MCP_MENU_ITEMS_MAX_COUNT ) {
					return new WP_Error(
						'us_mcp_menus_max_count',
						sprintf( 'operations[%d]: cannot add — the menu already has %d items (hard cap is %d).', $idx, count( $state['records'] ), US_MCP_MENU_ITEMS_MAX_COUNT ),
						array( 'status' => 400 )
					);
				}
				$rec = us_mcp_menus_validate_add_fields( isset( $op_entry['fields'] ) ? $op_entry['fields'] : NULL, $idx );
				if ( is_wp_error( $rec ) ) {
					return $rec;
				}
				$parent_ref = array_key_exists( 'parent_id', $op_entry ) ? $op_entry['parent_id'] : 0;
				$parent = us_mcp_menus_resolve_ref( $parent_ref === NULL ? 0 : $parent_ref, $state, $idx, 'parent' );
				if ( is_wp_error( $parent ) ) {
					return $parent;
				}

				$sibling_count = count( $state['children'][ $parent ] );
				$position = array_key_exists( 'position', $op_entry ) ? $op_entry['position'] : NULL;
				if ( $position === NULL ) {
					$final_idx = $sibling_count;
				} else {
					if ( ! us_mcp_menus_looks_like_int( $position ) ) {
						return new WP_Error(
							'us_mcp_menus_bad_position',
							sprintf( 'operations[%d]: `position` must be a non-negative integer (or omitted to append).', $idx ),
							array( 'status' => 400 )
						);
					}
					$position = (int) $position;
					if ( $position < 0 OR $position > $sibling_count ) {
						return new WP_Error(
							'us_mcp_menus_position_out_of_range',
							sprintf( 'operations[%d]: `position` %d is out of range — the target parent has %d children (valid: 0..%d, inclusive).', $idx, $position, $sibling_count, $sibling_count ),
							array( 'status' => 400 )
						);
					}
					$final_idx = $position;
				}

				$new_key = 'new:' . $idx;
				$state['records'][ $new_key ] = $rec;
				$state['children'][ $new_key ] = array();
				$state['parent'][ $new_key ] = $parent;
				array_splice( $state['children'][ $parent ], $final_idx, 0, array( $new_key ) );

				$applied[] = array(
					'op'        => 'add',
					'ref'       => $new_key,
					'id'        => $new_key, // replaced by the real id after persist
					'parent_id' => $parent,
					'position'  => $final_idx,
				);
				$structure_changed = TRUE;
				break;

			case 'update':
				$id = us_mcp_menus_resolve_ref( isset( $op_entry['id'] ) ? $op_entry['id'] : NULL, $state, $idx, 'item' );
				if ( is_wp_error( $id ) ) {
					return $id;
				}
				$patch = us_mcp_menus_validate_patch_fields( isset( $op_entry['fields'] ) ? $op_entry['fields'] : NULL, $state['records'][ $id ], $idx );
				if ( is_wp_error( $patch ) ) {
					return $patch;
				}
				$changed = array();
				foreach ( $patch as $key => $value ) {
					if ( $state['records'][ $id ][ $key ] !== $value ) {
						$state['records'][ $id ][ $key ] = $value;
						if ( $key !== 'invalid' ) {
							$changed[] = $key;
						}
					}
				}
				$applied[] = array(
					'op'             => 'update',
					'id'             => $id,
					'changed_fields' => $changed,
				);
				break;

			case 'remove':
				$id = us_mcp_menus_resolve_ref( isset( $op_entry['id'] ) ? $op_entry['id'] : NULL, $state, $idx, 'item' );
				if ( is_wp_error( $id ) ) {
					return $id;
				}
				$mode = array_key_exists( 'children', $op_entry ) ? $op_entry['children'] : 'reparent';
				if ( ! in_array( $mode, array( 'reparent', 'cascade' ), TRUE ) ) {
					return new WP_Error(
						'us_mcp_menus_bad_children_mode',
						sprintf( 'operations[%d]: `children` must be "reparent" (sub-items move up to the removed item\'s parent — default) or "cascade" (the whole subtree is removed).', $idx ),
						array( 'status' => 400 )
					);
				}

				$parent = $state['parent'][ $id ];
				$pos = array_search( $id, $state['children'][ $parent ], TRUE );

				if ( $mode === 'cascade' ) {
					$removed_ids = us_mcp_menus_collect_subtree( $state, $id );
					array_splice( $state['children'][ $parent ], $pos, 1 );
				} else {
					$removed_ids = array( $id );
					$lifted = $state['children'][ $id ];
					// The removed item's children take its place, in order.
					array_splice( $state['children'][ $parent ], $pos, 1, $lifted );
					foreach ( $lifted as $child_id ) {
						$state['parent'][ $child_id ] = $parent;
					}
				}
				foreach ( $removed_ids as $removed_id ) {
					unset( $state['records'][ $removed_id ], $state['parent'][ $removed_id ], $state['children'][ $removed_id ] );
				}

				$applied[] = array(
					'op'          => 'remove',
					'id'          => $id,
					'children'    => $mode,
					'removed_ids' => $removed_ids,
				);
				$structure_changed = TRUE;
				break;

			case 'reorder':
				$tree = isset( $op_entry['tree'] ) ? $op_entry['tree'] : NULL;
				if ( ! is_array( $tree ) ) {
					return new WP_Error(
						'us_mcp_menus_bad_tree',
						sprintf( 'operations[%d]: `tree` must be an array of {id, children?} nodes describing the COMPLETE target structure.', $idx ),
						array( 'status' => 400 )
					);
				}
				$new_children = array( 0 => array() );
				$new_parent = array();
				$seen = array();
				$walk_error = NULL;

				$walk = function ( $nodes, $parent_key ) use ( &$walk, &$new_children, &$new_parent, &$seen, &$walk_error, $state, $idx ) {
					foreach ( $nodes as $node ) {
						if ( $walk_error !== NULL ) {
							return;
						}
						if ( ! is_array( $node ) ) {
							$walk_error = new WP_Error(
								'us_mcp_menus_bad_tree',
								sprintf( 'operations[%d]: every `tree` node must be an object {id, children?}.', $idx ),
								array( 'status' => 400 )
							);
							return;
						}
						foreach ( array_keys( $node ) as $node_key ) {
							if ( ! in_array( (string) $node_key, array( 'id', 'children' ), TRUE ) ) {
								$walk_error = new WP_Error(
									'us_mcp_menus_bad_tree',
									sprintf( 'operations[%d]: `tree` node key "%s" is not allowed — nodes carry only `id` and `children`. Field changes belong in separate update ops.', $idx, $node_key ),
									array( 'status' => 400 )
								);
								return;
							}
						}
						$id = us_mcp_menus_resolve_ref( isset( $node['id'] ) ? $node['id'] : NULL, $state, $idx, 'item' );
						if ( is_wp_error( $id ) ) {
							$walk_error = $id;
							return;
						}
						if ( isset( $seen[ (string) $id ] ) ) {
							$walk_error = new WP_Error(
								'us_mcp_menus_tree_duplicate',
								sprintf( 'operations[%d]: item id=%s appears more than once in `tree` — every item must appear exactly once.', $idx, (string) $id ),
								array( 'status' => 400 )
							);
							return;
						}
						$seen[ (string) $id ] = $id;
						$new_parent[ $id ] = $parent_key;
						$new_children[ $parent_key ][] = $id;
						if ( ! isset( $new_children[ $id ] ) ) {
							$new_children[ $id ] = array();
						}
						if ( array_key_exists( 'children', $node ) AND $node['children'] !== NULL ) {
							if ( ! is_array( $node['children'] ) ) {
								$walk_error = new WP_Error(
									'us_mcp_menus_bad_tree',
									sprintf( 'operations[%d]: `children` of tree node id=%s must be an array.', $idx, (string) $id ),
									array( 'status' => 400 )
								);
								return;
							}
							$walk( $node['children'], $id );
						}
					}
				};
				$walk( $tree, 0 );
				if ( $walk_error !== NULL ) {
					return $walk_error;
				}

				$missing = array();
				foreach ( array_keys( $state['records'] ) as $key ) {
					if ( ! isset( $seen[ (string) $key ] ) ) {
						$missing[] = $key;
					}
				}
				if ( ! empty( $missing ) ) {
					return new WP_Error(
						'us_mcp_menus_tree_mismatch',
						sprintf( 'operations[%d]: `tree` must contain EVERY item of the menu exactly once — missing: %s. Reorder is declarative (the full target structure); drop items with explicit remove ops in the same call instead of omitting them.', $idx, json_encode( $missing ) ),
						array( 'status' => 400 )
					);
				}

				$state['children'] = $new_children;
				$state['parent'] = $new_parent;
				$applied[] = array( 'op' => 'reorder' );
				$structure_changed = TRUE;
				break;
		}
	}

	return array(
		'applied'           => $applied,
		'structure_changed' => $structure_changed,
	);
}

/**
 * Assemble the COMPLETE wp_update_nav_menu_item() argument set for one
 * record. Always complete — that function resets any arg it is not given.
 *
 * @param array $rec
 * @param int   $parent_id Real (persisted) parent item id, 0 for top level.
 * @param int   $position  Global menu_order value.
 * @return array
 */
function us_mcp_menus_item_args( array $rec, $parent_id, $position ) {
	// A reusable_block is stored as a plain post_type item pointing at the
	// us_page_block post — the menu walker keys off object=='us_page_block'.
	$is_object_item = ( $rec['type'] === 'post_type' OR $rec['type'] === 'taxonomy' OR $rec['type'] === 'reusable_block' );
	$wp_type = ( $rec['type'] === 'reusable_block' ) ? 'post_type' : (string) $rec['type'];
	// wp_update_nav_menu_item() funnels these into wp_insert_post(), which
	// unslashes its input — so any free-text value carrying a backslash must be
	// wp_slash()'d first to survive the round-trip unchanged (same convention as
	// the post CRUD path, see posts.php). object / type / target are fixed slugs
	// and classes / xfn are token-sanitised downstream, so they need no slashing.
	return array(
		'menu-item-type'        => $wp_type,
		'menu-item-object'      => (string) $rec['object'],
		'menu-item-object-id'   => $is_object_item ? (int) $rec['object_id'] : 0,
		'menu-item-parent-id'   => (int) $parent_id,
		'menu-item-position'    => (int) $position,
		'menu-item-title'       => wp_slash( (string) $rec['title'] ),
		'menu-item-url'         => ( $rec['type'] === 'custom' ) ? wp_slash( (string) $rec['url'] ) : '',
		'menu-item-description' => wp_slash( (string) $rec['description'] ),
		'menu-item-attr-title'  => wp_slash( (string) $rec['attr_title'] ),
		'menu-item-target'      => (string) $rec['target'],
		'menu-item-classes'     => implode( ' ', $rec['classes'] ),
		'menu-item-xfn'         => (string) $rec['xfn'],
		'menu-item-status'      => 'publish',
	);
}

/**
 * Whether two records carry the same writable field values (bookkeeping
 * keys ignored). `remove_rows` is handled separately (it is a post-meta, not
 * a wp_update_nav_menu_item argument), so it is intentionally not compared
 * here.
 *
 * @param array $a
 * @param array $b
 * @return bool
 */
function us_mcp_menus_fields_equal( array $a, array $b ) {
	foreach ( array( 'type', 'object', 'object_id', 'title', 'url', 'target', 'classes', 'xfn', 'description', 'attr_title' ) as $key ) {
		if ( $a[ $key ] !== $b[ $key ] ) {
			return FALSE;
		}
	}
	return TRUE;
}

/**
 * Write the simulated final state to the DB. Depth-first over the final
 * tree, so a parent's real id always exists before its children are written:
 * new items are created, surviving items are rewritten only when their
 * fields / parent / position actually changed, removed items are deleted
 * last (after their lifted children have been re-parented).
 *
 * menu_order is renumbered to the contiguous depth-first sequence ONLY when
 * an operation changed the structure; pure field updates preserve stored
 * order values so untouched rows stay untouched.
 *
 * @param int   $menu_id
 * @param array $before_records  Records as read from the DB (with parent_raw / menu_order).
 * @param array $state           Final simulated state.
 * @param bool  $structure_changed
 * @param array $id_map          OUT: "new:<i>" => real item id.
 * @return array{created: int[], updated: int[], deleted: int[]}|WP_Error
 */
function us_mcp_menus_persist( $menu_id, array $before_records, array $state, $structure_changed, array &$id_map ) {
	$written = array(
		'created' => array(),
		'updated' => array(),
		'deleted' => array(),
	);
	$order = 0;
	$failure = NULL;

	$walk = function ( $parent_key, $parent_real ) use ( &$walk, &$order, &$written, &$failure, &$id_map, $menu_id, $before_records, $state, $structure_changed ) {
		foreach ( $state['children'][ $parent_key ] as $id ) {
			if ( $failure !== NULL ) {
				return;
			}
			$rec = $state['records'][ $id ];
			$order++;

			if ( ! empty( $rec['is_new'] ) ) {
				$result = wp_update_nav_menu_item( $menu_id, 0, us_mcp_menus_item_args( $rec, $parent_real, $order ) );
				if ( is_wp_error( $result ) ) {
					$failure = sprintf( 'creating the "%s" item failed: %s', $id, $result->get_error_message() );
					return;
				}
				$real = (int) $result;
				$id_map[ $id ] = $real;
				$written['created'][] = $real;
				if ( $rec['type'] === 'reusable_block' ) {
					update_post_meta( $real, '_menu_item_remove_rows', $rec['remove_rows'] ? '1' : '0' );
				}
			} else {
				$real = (int) $id;
				$before = $before_records[ $id ];
				$fields_changed = ! us_mcp_menus_fields_equal( $rec, $before );
				$parent_changed = ( (int) $before['parent_raw'] !== (int) $parent_real );
				$order_changed = ( $structure_changed AND (int) $before['menu_order'] !== $order );
				$meta_changed = ( $rec['type'] === 'reusable_block' AND (bool) $before['remove_rows'] !== (bool) $rec['remove_rows'] );

				$touched = FALSE;
				if ( $fields_changed OR $parent_changed OR $order_changed ) {
					$position = $structure_changed ? $order : (int) $before['menu_order'];
					$result = wp_update_nav_menu_item( $menu_id, $real, us_mcp_menus_item_args( $rec, $parent_real, $position ) );
					if ( is_wp_error( $result ) ) {
						$failure = sprintf( 'updating item id=%d failed: %s', $real, $result->get_error_message() );
						return;
					}
					$touched = TRUE;
				}
				// `_menu_item_remove_rows` lives outside wp_update_nav_menu_item.
				// Re-assert it whenever we rewrote the item (a stray admin-side
				// hook would otherwise reset it) and when only the flag changed.
				if ( $rec['type'] === 'reusable_block' AND ( $touched OR $meta_changed ) ) {
					update_post_meta( $real, '_menu_item_remove_rows', $rec['remove_rows'] ? '1' : '0' );
					$touched = TRUE;
				}
				// `_menu_item_btn_style` ("Show as Button") similarly lives outside
				// wp_update_nav_menu_item, and the same admin hook DELETES it on any
				// save without the POST field present — which is every MCP write. So
				// whenever we actually rewrote this (non-block) item, the hook has
				// just stripped the style; restore the preserved value. For a
				// non-reusable_block item $touched is true only when
				// wp_update_nav_menu_item ran above (the block re-assert never runs
				// for this type), so it precisely marks "the hook just fired".
				if ( $rec['type'] !== 'reusable_block' AND $touched AND $rec['btn_style'] !== '' ) {
					update_post_meta( $real, '_menu_item_btn_style', $rec['btn_style'] );
				}
				if ( $touched ) {
					$written['updated'][] = $real;
				}
			}
			$walk( $id, $real );
		}
	};
	$walk( 0, 0 );

	if ( $failure === NULL ) {
		foreach ( array_keys( $before_records ) as $id ) {
			if ( isset( $state['records'][ $id ] ) ) {
				continue;
			}
			$result = wp_delete_post( (int) $id, TRUE );
			if ( ! $result ) {
				$failure = sprintf( 'deleting item id=%d failed', (int) $id );
				break;
			}
			$written['deleted'][] = (int) $id;
		}
	}

	if ( $failure !== NULL ) {
		return new WP_Error(
			'us_mcp_menus_persist_failed',
			sprintf(
				'All operations validated, but the write stopped mid-way: %s. Already persisted before the failure — created: %s, updated: %s, deleted: %s. Re-read the menu with upsolution-get-menu to see its actual current state before retrying.',
				$failure,
				json_encode( $written['created'] ),
				json_encode( $written['updated'] ),
				json_encode( $written['deleted'] )
			),
			array( 'status' => 500 )
		);
	}
	return $written;
}

/**
 * Replace "new:<i>" tokens in the per-op audit with the real ids assigned
 * during persist. A token with no mapping (an item added AND removed within
 * the same call — it never reached the DB) is left as-is.
 *
 * @param array $applied
 * @param array $id_map
 * @return array
 */
function us_mcp_menus_map_applied( array $applied, array $id_map ) {
	$map_one = function ( $value ) use ( $id_map ) {
		return ( is_string( $value ) AND isset( $id_map[ $value ] ) ) ? $id_map[ $value ] : $value;
	};
	foreach ( $applied as &$entry ) {
		foreach ( array( 'id', 'parent_id' ) as $key ) {
			if ( isset( $entry[ $key ] ) ) {
				$entry[ $key ] = $map_one( $entry[ $key ] );
			}
		}
		if ( isset( $entry['removed_ids'] ) AND is_array( $entry['removed_ids'] ) ) {
			$entry['removed_ids'] = array_map( $map_one, $entry['removed_ids'] );
		}
	}
	unset( $entry );
	return $applied;
}

// ─────────────────────────────────────────────────────────────────────────────
// Menu dropdown (mega-menu) settings — the per-item `us_mega_menu_settings`
// post-meta a top-level item carries to style its dropdown (columns, side
// panel, width/position, background, mobile behaviour). Stored as a complete
// serialized array; only honoured on first-level items that have a dropdown.
// The editable field set is derived live from the `menu-dropdown` element
// config so it tracks the theme instead of duplicating ~23 fields here.
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Per-field validation spec for one mega-menu setting, derived from
 * us_config('menu-dropdown'). Each entry:
 *   type     'switch' | 'text' | 'slider' | 'radio' | 'select' | 'color' | 'upload'
 *   std      default value (seeds the complete stored object)
 *   options  string[]  — enum members for radio / select
 *   gradient bool       — for color, whether gradient syntax is allowed
 *   show_if  raw config condition — when the field is contextually active
 *
 * @return array<string, array>
 */
function us_mcp_menu_dropdown_field_spec() {
	static $spec = NULL;
	if ( $spec !== NULL ) {
		return $spec;
	}
	$config = function_exists( 'us_config' ) ? us_config( 'menu-dropdown', array() ) : array();
	$spec = array();
	if ( ! is_array( $config ) ) {
		return $spec;
	}
	foreach ( $config as $id => $field ) {
		if ( ! is_array( $field ) OR ! isset( $field['type'] ) ) {
			continue;
		}
		$entry = array(
			'type' => (string) $field['type'],
			'std'  => array_key_exists( 'std', $field ) ? $field['std'] : '',
		);
		if ( in_array( $field['type'], array( 'radio', 'select' ), TRUE ) AND isset( $field['options'] ) AND is_array( $field['options'] ) ) {
			$entry['options'] = array_map( 'strval', array_keys( $field['options'] ) );
		}
		if ( $field['type'] === 'color' ) {
			// usof colors allow gradients unless with_gradient is explicitly FALSE.
			$entry['gradient'] = ! ( array_key_exists( 'with_gradient', $field ) AND $field['with_gradient'] === FALSE );
		}
		if ( isset( $field['show_if'] ) ) {
			$entry['show_if'] = $field['show_if'];
		}
		$spec[ (string) $id ] = $entry;
	}
	return $spec;
}

/**
 * The complete default settings object (every field at its config std) — the
 * skeleton the CSS renderer (templates/css-theme-options.php) expects every
 * stored object to be complete against (it accesses keys directly).
 *
 * @return array<string, mixed>
 */
function us_mcp_menu_dropdown_defaults() {
	static $defaults = NULL;
	if ( $defaults !== NULL ) {
		return $defaults;
	}
	$defaults = array();
	foreach ( us_mcp_menu_dropdown_field_spec() as $id => $meta ) {
		$defaults[ $id ] = $meta['std'];
	}
	return $defaults;
}

/**
 * Read one item's stored dropdown settings, merged over defaults so the result
 * is complete. NULL when the item has no settings at all.
 *
 * @param int $item_id
 * @return array<string, mixed>|null
 */
function us_mcp_menu_dropdown_read( $item_id ) {
	$raw = get_post_meta( (int) $item_id, 'us_mega_menu_settings', TRUE );
	if ( ! is_array( $raw ) OR empty( $raw ) ) {
		return NULL;
	}
	return array_merge( us_mcp_menu_dropdown_defaults(), $raw );
}

/**
 * Shape a settings object for the agent: switch fields as real booleans,
 * everything else passed through. Keeps get-menu / set-menu-dropdown output
 * readable instead of leaking the '1' / '0' storage form.
 *
 * @param array $settings
 * @return array
 */
function us_mcp_menu_dropdown_for_output( array $settings ) {
	$spec = us_mcp_menu_dropdown_field_spec();
	$out = array();
	foreach ( $settings as $key => $value ) {
		if ( isset( $spec[ $key ] ) AND $spec[ $key ]['type'] === 'switch' ) {
			$out[ $key ] = ! empty( $value ); // matches the renderer's !empty() gate
		} else {
			$out[ $key ] = $value;
		}
	}
	return $out;
}

/**
 * Validate one dropdown setting against its spec. Returns the normalised value
 * ready to store, or a WP_Error.
 *
 * @param string $field_id
 * @param mixed  $value
 * @param array  $spec
 * @return mixed|WP_Error
 */
function us_mcp_menu_dropdown_check_field( $field_id, $value, array $spec ) {
	if ( ! isset( $spec[ $field_id ] ) ) {
		return new WP_Error(
			'us_mcp_menu_dropdown_unknown_field',
			sprintf( 'Unknown dropdown setting "%s". Allowed: %s.', $field_id, implode( ', ', array_keys( $spec ) ) ),
			array( 'status' => 400 )
		);
	}
	$meta = $spec[ $field_id ];

	switch ( $meta['type'] ) {
		case 'switch':
			if ( is_bool( $value ) ) {
				return $value ? '1' : '0';
			}
			if ( $value === 1 OR $value === '1' OR $value === 'true' ) {
				return '1';
			}
			if ( $value === 0 OR $value === '0' OR $value === '' OR $value === 'false' ) {
				return '0';
			}
			return new WP_Error(
				'us_mcp_menu_dropdown_bad_switch',
				sprintf( 'Setting "%s" must be a boolean (true / false).', $field_id ),
				array( 'status' => 400 )
			);

		case 'radio':
		case 'select':
			$options = isset( $meta['options'] ) ? $meta['options'] : array();
			if ( ! is_string( $value ) OR ! in_array( $value, $options, TRUE ) ) {
				return new WP_Error(
					'us_mcp_menu_dropdown_bad_enum',
					sprintf( 'Setting "%s" must be one of: %s.', $field_id, implode( ', ', $options ) ),
					array( 'status' => 400 )
				);
			}
			return $value;

		case 'color':
			if ( ! function_exists( 'us_mcp_palette_check_color' ) ) {
				return new WP_Error(
					'us_mcp_menu_dropdown_palette_unavailable',
					'Color validation is unavailable — abilities/color-palette.php did not load.',
					array( 'status' => 503 )
				);
			}
			if ( $value === '' OR $value === NULL ) {
				return '';
			}
			return us_mcp_palette_check_color( $value, (bool) $meta['gradient'], $field_id );

		case 'upload':
			// Background image — attachment id, or empty to clear.
			if ( $value === '' OR $value === 0 OR $value === '0' OR $value === NULL ) {
				return '';
			}
			if ( ! us_mcp_menus_looks_like_int( $value ) ) {
				return new WP_Error(
					'us_mcp_menu_dropdown_bad_upload',
					sprintf( 'Setting "%s" must be an image attachment id (see upsolution-list-media / upsolution-upload-media), or empty to clear.', $field_id ),
					array( 'status' => 400 )
				);
			}
			$att_id = (int) $value;
			if ( ! wp_attachment_is_image( $att_id ) ) {
				return new WP_Error(
					'us_mcp_menu_dropdown_not_an_image',
					sprintf( 'Setting "%s": attachment id=%d is not an image. Pick an image via upsolution-list-media.', $field_id, $att_id ),
					array( 'status' => 422 )
				);
			}
			return $att_id;

		case 'slider':
			// `columns` is a bare integer 1..10 (the walker casts (int) and emits
			// `--menu-cols:N`); every other slider is a CSS length value.
			if ( $field_id === 'columns' ) {
				if ( us_mcp_menus_looks_like_int( $value ) ) {
					$n = (int) $value;
					if ( $n >= 1 AND $n <= 10 ) {
						return (string) $n;
					}
				}
				return new WP_Error(
					'us_mcp_menu_dropdown_bad_columns',
					'Setting "columns" must be an integer 1..10.',
					array( 'status' => 400 )
				);
			}
			// fall through to mild CSS-value handling
		case 'text':
			if ( ! is_scalar( $value ) ) {
				return new WP_Error(
					'us_mcp_menu_dropdown_bad_value',
					sprintf( 'Setting "%s" must be a string or number.', $field_id ),
					array( 'status' => 400 )
				);
			}
			$value = trim( (string) $value );
			if ( preg_match( '/[\r\n\t]/', $value ) ) {
				return new WP_Error(
					'us_mcp_menu_dropdown_bad_value_chars',
					sprintf( 'Setting "%s" must not contain line breaks or tabs.', $field_id ),
					array( 'status' => 400 )
				);
			}
			return $value;

		default:
			return new WP_Error(
				'us_mcp_menu_dropdown_internal_type',
				sprintf( 'Internal: unhandled dropdown field type "%s" for "%s".', $meta['type'], $field_id ),
				array( 'status' => 500 )
			);
	}
}

/**
 * Evaluate one show_if condition triple [field, op, value] against a merged
 * settings array. Only '=' / '!=' appear in the config; unknown ops pass.
 *
 * @param array $cond
 * @param array $merged
 * @return bool
 */
function us_mcp_menu_dropdown_eval_cond( array $cond, array $merged ) {
	if ( count( $cond ) < 3 ) {
		return TRUE;
	}
	$field = $cond[0];
	$op = $cond[1];
	$expected = $cond[2];
	$actual = isset( $merged[ $field ] ) ? $merged[ $field ] : '';
	$a = (string) $actual;
	$b = is_scalar( $expected ) ? (string) $expected : '';
	if ( $op === '=' ) {
		return $a === $b;
	}
	if ( $op === '!=' ) {
		return $a !== $b;
	}
	return TRUE;
}

/**
 * Whether a field's show_if makes it active in the given merged context.
 * Handles a single condition triple and an AND/OR compound
 * ([cond, 'and'|'or', cond, …]). No show_if → always active.
 *
 * @param mixed $show_if
 * @param array $merged
 * @return bool
 */
function us_mcp_menu_dropdown_show_if_active( $show_if, array $merged ) {
	if ( ! is_array( $show_if ) OR empty( $show_if ) ) {
		return TRUE;
	}
	if ( is_array( $show_if[0] ) ) {
		$result = NULL;
		$joiner = 'and';
		foreach ( $show_if as $part ) {
			if ( is_string( $part ) AND in_array( $part, array( 'and', 'or' ), TRUE ) ) {
				$joiner = $part;
				continue;
			}
			if ( is_array( $part ) ) {
				$cond = us_mcp_menu_dropdown_eval_cond( $part, $merged );
				if ( $result === NULL ) {
					$result = $cond;
				} else {
					$result = ( $joiner === 'and' ) ? ( $result AND $cond ) : ( $result OR $cond );
				}
			}
		}
		return $result === NULL ? TRUE : (bool) $result;
	}
	return us_mcp_menu_dropdown_eval_cond( $show_if, $merged );
}

/**
 * Human-readable rendering of a show_if for the `ignored` note.
 *
 * @param mixed $show_if
 * @return string
 */
function us_mcp_menu_dropdown_show_if_reason( $show_if ) {
	$render = function ( $cond ) {
		return sprintf( '%s %s %s', $cond[0], $cond[1], is_scalar( $cond[2] ) ? (string) $cond[2] : '' );
	};
	if ( is_array( $show_if ) AND isset( $show_if[0] ) AND is_array( $show_if[0] ) ) {
		$parts = array();
		$joiner = 'and';
		foreach ( $show_if as $part ) {
			if ( is_string( $part ) AND in_array( $part, array( 'and', 'or' ), TRUE ) ) {
				$joiner = $part;
			} elseif ( is_array( $part ) ) {
				$parts[] = $render( $part );
			}
		}
		return 'only active when ' . implode( ' ' . $joiner . ' ', $parts );
	}
	if ( is_array( $show_if ) ) {
		return 'only active when ' . $render( $show_if );
	}
	return 'not active in this context';
}

/**
 * Validate the agent's `settings` patch AND fold it into a complete stored
 * object. Fields whose show_if is not satisfied in the resulting context are
 * NOT stored — they are reverted to the base value and reported in `ignored`
 * (the caller surfaces that to the agent). Reverting runs to a fixed point so
 * a controller field reverting (e.g. `width` when a side panel is on) cascades
 * to its dependents (`stretch`, `custom_width`, …) regardless of input order.
 *
 * @param array|null $current  Currently stored settings (NULL = none).
 * @param mixed      $input     The agent's `settings` object.
 * @param bool       $merge     TRUE = patch over current; FALSE = over defaults.
 * @return array{stored: array, ignored: array}|WP_Error
 */
function us_mcp_menu_dropdown_apply( $current, $input, $merge ) {
	$spec = us_mcp_menu_dropdown_field_spec();
	if ( empty( $spec ) ) {
		return new WP_Error(
			'us_mcp_menu_dropdown_no_config',
			'The menu-dropdown settings config is unavailable on this site.',
			array( 'status' => 503 )
		);
	}
	if ( ! is_array( $input ) OR empty( $input ) ) {
		return new WP_Error(
			'us_mcp_menu_dropdown_empty',
			'Pass at least one field in `settings`.',
			array( 'status' => 400 )
		);
	}

	$patch = array();
	foreach ( $input as $key => $value ) {
		$checked = us_mcp_menu_dropdown_check_field( (string) $key, $value, $spec );
		if ( is_wp_error( $checked ) ) {
			return $checked;
		}
		$patch[ (string) $key ] = $checked;
	}

	$defaults = us_mcp_menu_dropdown_defaults();
	$base = ( $merge AND is_array( $current ) ) ? array_merge( $defaults, $current ) : $defaults;

	$merged = $base;
	foreach ( $patch as $key => $value ) {
		$merged[ $key ] = $value;
	}

	// Fixed-point revert of contextually inactive patch fields.
	$ignored = array();
	$ignored_keys = array();
	do {
		$changed = FALSE;
		foreach ( $patch as $key => $value ) {
			if ( isset( $ignored_keys[ $key ] ) ) {
				continue;
			}
			$meta = $spec[ $key ];
			if ( isset( $meta['show_if'] ) AND ! us_mcp_menu_dropdown_show_if_active( $meta['show_if'], $merged ) ) {
				$merged[ $key ] = array_key_exists( $key, $base ) ? $base[ $key ] : ( $defaults[ $key ] ?? '' );
				$ignored_keys[ $key ] = TRUE;
				$ignored[] = array(
					'field'  => $key,
					'reason' => us_mcp_menu_dropdown_show_if_reason( $meta['show_if'] ),
				);
				$changed = TRUE;
			}
		}
	} while ( $changed );

	return array(
		'stored'  => $merged,
		'ignored' => $ignored,
	);
}

add_action( 'wp_abilities_api_init', function () {
	wp_register_ability( 'upsolution/list-menus', array(
		'label'               => 'List navigation menus',
		'description'         => 'List the site\'s navigation menus (Appearance → Menus). Returns {menus: [{id, name, slug, count, edit_link}]}. `id` is the menu_id that upsolution-get-menu and upsolution-set-menu-items take. `slug` is how the THEME references a menu: the us_additional_menu shortcode\'s source="<slug>" and the Header Builder\'s Menu element both select menus by slug.',
		'category'            => 'upsolution',
		// No input — call with no params.
		'output_schema'       => array(
			'type'       => 'object',
			'required'   => array( 'menus' ),
			'properties' => array(
				'menus'     => array(
					'type'        => 'array',
					'description' => 'Every nav_menu term on the site, name-ordered.',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'        => array( 'type' => 'integer', 'description' => 'Menu id — pass as menu_id to upsolution-get-menu / upsolution-set-menu-items.' ),
							'name'      => array( 'type' => 'string' ),
							'slug'      => array( 'type' => 'string', 'description' => 'What us_additional_menu source="…" and the header Menu element reference.' ),
							'count'     => array( 'type' => 'integer', 'description' => 'Number of items in the menu.' ),
							'edit_link' => array( 'type' => 'string', 'description' => 'wp-admin URL of this menu\'s editor screen.' ),
						),
					),
				),
			),
		),
		'execute_callback'    => 'us_mcp_ability_list_menus',
		'permission_callback' => 'us_mcp_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );

	wp_register_ability( 'upsolution/get-menu', array(
		'label'               => 'Read one navigation menu\'s item tree',
		'description'         => 'Return one menu\'s items as a nested tree (menu_id from upsolution-list-menus). Each node: {id, title, type, object, object_id, url, children, …} — `id` is what upsolution-set-menu-items operations target, and the array order inside every `children` list is the rendered order, so positions for an insert ("add X after Y") are read off this tree directly. `title_inherited: true` marks items whose label automatically follows the linked object\'s current title (their stored label is empty). `invalid: true` marks items whose linked page / post / term has been deleted — they render as dead entries; remove them or repoint their object_id via upsolution-set-menu-items. ALWAYS read this immediately before composing set-menu-items operations: item ids, parent nesting and positions all come from here.',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'       => 'object',
			'required'   => array( 'menu_id' ),
			'properties' => array(
				'menu_id' => array(
					'type'        => 'integer',
					'description' => 'Menu id as returned by upsolution-list-menus.',
				),
			),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'required'   => array( 'menu', 'items', 'count' ),
			'properties' => array(
				'menu'  => array(
					'type'       => 'object',
					'properties' => array(
						'id'        => array( 'type' => 'integer' ),
						'name'      => array( 'type' => 'string' ),
						'slug'      => array( 'type' => 'string' ),
						'edit_link' => array( 'type' => 'string' ),
					),
				),
				'items' => array(
					'type'        => 'array',
					'description' => 'Nested item tree in rendered order. Node fields: id, title, type (post_type / taxonomy / custom / post_type_archive / reusable_block), object, object_id, url, children; plus target / classes / attr_title / description / xfn when set, title_inherited / invalid flags. reusable_block nodes carry remove_rows and no url (the Reusable Block\'s content is embedded into the parent\'s dropdown). A first-level node that has mega-menu dropdown styling carries a `dropdown` object with its current settings — edit it via upsolution-set-menu-dropdown.',
					'items'       => array( 'type' => 'object', 'additionalProperties' => TRUE ),
				),
				'count' => array( 'type' => 'integer', 'minimum' => 0, 'description' => 'Total number of items (all depths).' ),
			),
		),
		'execute_callback'    => 'us_mcp_ability_get_menu',
		'permission_callback' => 'us_mcp_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );

	wp_register_ability( 'upsolution/set-menu-items', array(
		'label'               => 'Edit one navigation menu\'s items',
		'description'         => 'Mutate one menu\'s items (Appearance → Menus) by applying a sequence of operations as one transaction — every operation is validated against an in-memory copy of the tree first; if any fails, nothing is persisted. MANDATORY pre-read: the design/menus doc (upsolution-read-doc) — per-type field matrix, title inheritance, reorder semantics. Get menu_id from upsolution-list-menus and the current tree / item ids from upsolution-get-menu immediately before calling.' . "\n\n"
			. 'Operation shapes:' . "\n"
			. '  - add     { fields: {…}, parent_id?: id, position?: int }  — insert a new item. fields.type: "post_type" (fields.object_id = page / post id; links to that object\'s CURRENT permalink), "taxonomy" (object_id = term id), "custom" (url + title required), "post_type_archive" (object = post type name), "reusable_block" (object_id = a Reusable Block / us_page_block id — embeds its content into the dropdown; place as a sub-item of a parent; optional remove_rows bool). Omit title on object-backed items to inherit the object\'s current title. parent_id omitted / 0 = top level; position is the 0-based index among that parent\'s children — omit to append.' . "\n"
			. '  - update  { id, fields: {…} }  — partial patch: title, url (custom only), object_id (repoint — post_type / taxonomy / reusable_block), target ("" / "_blank"), classes, xfn, description, attr_title, remove_rows (reusable_block only). null clears a field. `type` is immutable — remove + add instead.' . "\n"
			. '  - remove  { id, children?: "reparent"|"cascade" }  — take the item out of the menu; the linked page / post itself is NOT touched. reparent (default): its sub-items move up into its place; cascade: the whole subtree goes.' . "\n"
			. '  - reorder { tree: [ {id, children?: […]} ] }  — declarative restructure: pass the COMPLETE target tree (every item exactly once). This is the op for moving / reparenting existing items.' . "\n\n"
			. 'Anywhere an item id is expected, the token "new:<i>" references the id auto-assigned by the add at operations[<i>] of the same call — so a parent and its sub-items can be created in one transaction.' . "\n\n"
			. 'Example — insert an existing page after the first top-level item ("add About after Home"): upsolution-get-menu shows top-level order [Home, Services, Contact]; call with operations: [{op: "add", fields: {type: "post_type", object_id: <About page id>}, position: 1}].',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'                 => 'object',
			'required'             => array( 'menu_id', 'operations' ),
			'additionalProperties' => FALSE,
			'properties'           => array(
				'menu_id'    => array(
					'type'        => 'integer',
					'description' => 'Target menu id — from upsolution-list-menus.',
				),
				'operations' => array(
					'type'        => 'array',
					'minItems'    => 1,
					'description' => 'Ordered list of operations applied as a single transaction. Each entry: {op: "add"|"update"|"remove"|"reorder", …} — see the tool description for per-op fields.',
					'items'       => array(
						'type'                 => 'object',
						'required'             => array( 'op' ),
						'additionalProperties' => TRUE,
						'properties'           => array(
							'op'        => array( 'type' => 'string', 'enum' => array( 'add', 'update', 'remove', 'reorder' ) ),
							'id'        => array( 'type' => array( 'integer', 'string' ), 'description' => 'For update / remove: target item id from upsolution-get-menu, or a "new:<i>" token referencing the add at operations[i] of this call.' ),
							'fields'    => array( 'type' => 'object', 'additionalProperties' => TRUE, 'description' => 'For add / update: item field map — see the design/menus doc for the per-type matrix.' ),
							'parent_id' => array( 'type' => array( 'integer', 'string' ), 'description' => 'For add only: parent item id (or "new:<i>" token). 0 / omitted = top level.' ),
							'position'  => array( 'type' => 'integer', 'description' => 'For add only: 0-based index among the parent\'s children. Omit to append.' ),
							'children'  => array( 'type' => 'string', 'enum' => array( 'reparent', 'cascade' ), 'description' => 'For remove only: what happens to sub-items. Default: reparent.' ),
							'tree'      => array( 'type' => 'array', 'items' => array( 'type' => 'object', 'additionalProperties' => TRUE ), 'description' => 'For reorder only: the complete target tree — [{id, children?: […]}] containing every item exactly once.' ),
						),
					),
				),
			),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'required'   => array( 'applied', 'before', 'after', 'count' ),
			'properties' => array(
				'applied' => array(
					'type'        => 'array',
					'description' => 'Per-operation audit. Adds carry the real assigned id (plus the "new:<i>" ref used in this call); updates list the fields that actually changed; removes list every removed item id.',
					'items'       => array( 'type' => 'object', 'additionalProperties' => TRUE ),
				),
				'before'  => array(
					'type'        => 'array',
					'description' => 'Item tree before the call (same shape as upsolution-get-menu items).',
					'items'       => array( 'type' => 'object', 'additionalProperties' => TRUE ),
				),
				'after'   => array(
					'type'        => 'array',
					'description' => 'Item tree after the call, re-read from the database — new ids, order and nesting visible here.',
					'items'       => array( 'type' => 'object', 'additionalProperties' => TRUE ),
				),
				'count'   => array( 'type' => 'integer', 'minimum' => 0, 'description' => 'Total number of items after the call.' ),
			),
		),
		'execute_callback'    => 'us_mcp_ability_set_menu_items',
		'permission_callback' => 'us_mcp_menus_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );

	wp_register_ability( 'upsolution/set-menu-dropdown', array(
		'label'               => 'Edit one menu item\'s mega-menu dropdown styling',
		'description'         => 'Style the dropdown of ONE first-level menu item — the "Dropdown Settings" / mega-menu panel (stored per item as us_mega_menu_settings). Use this AFTER building the item tree with upsolution-set-menu-items: it controls how that item\'s dropdown looks (columns for sub-items, side panel, width / position, background color or image, mobile behaviour), not its contents. MANDATORY pre-read: the design/menu-dropdown doc (upsolution-read-doc) — the field list, accepted values and the show_if dependencies between fields.' . "\n\n"
			. 'Only first-level items that actually have a dropdown (sub-items, or an embedded reusable_block) render these settings — setting them on a nested item is rejected. Get menu_id from upsolution-list-menus and item_id from upsolution-get-menu (the item\'s current settings, if any, are on its `dropdown` node).' . "\n\n"
			. '`settings` is a partial patch by default (merge=true): only the keys you pass change, the rest keep their stored values; pass merge=false to reset every other field to its default first. Switch fields take true / false; color fields accept the same syntax as upsolution-set-palette (hex / rgba / "transparent" / linear-gradient on color_bg / palette tokens "_<slug>"); bg_image takes an image attachment id (upsolution-list-media). Fields that are not applicable in the resulting context (their show_if dependency is unmet — e.g. custom_width while width is not "custom", or any side_item_* while has_side_panel is off) are NOT stored and are returned in `ignored` with the reason. Saving regenerates the site CSS asset files.',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'                 => 'object',
			'required'             => array( 'menu_id', 'item_id', 'settings' ),
			'additionalProperties' => FALSE,
			'properties'           => array(
				'menu_id'  => array( 'type' => 'integer', 'description' => 'Menu id — from upsolution-list-menus.' ),
				'item_id'  => array( 'type' => 'integer', 'description' => 'First-level item id — from upsolution-get-menu.' ),
				'settings' => array(
					'type'                 => 'object',
					'additionalProperties' => TRUE,
					'description'          => 'Field => value map. Keys: has_side_panel, side_item_font_size / side_item_font_weight / side_item_ver_indent / side_item_hor_indent / side_item_width / dropdown_height (side-panel mode), width (auto / full / custom), custom_width, stretch, drop_from (menu_item / header), drop_to (left / center / right), columns (1..10), columns_fill_direction (hor / ver), padding, color_bg, color_text, bg_image (attachment id), bg_image_size / bg_image_repeat / bg_image_position, override_settings, mobile_behavior (arrow / label). See the design/menu-dropdown doc for accepted values and dependencies.',
				),
				'merge'    => array( 'type' => 'boolean', 'default' => TRUE, 'description' => 'true (default) = partial patch over current settings; false = reset other fields to defaults first.' ),
			),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'required'   => array( 'item_id', 'before', 'after', 'ignored', 'regenerated_assets' ),
			'properties' => array(
				'item_id'            => array( 'type' => 'integer' ),
				'before'             => array( 'type' => array( 'object', 'null' ), 'additionalProperties' => TRUE, 'description' => 'Settings before the call (complete object), or null if the item had none.' ),
				'after'              => array( 'type' => 'object', 'additionalProperties' => TRUE, 'description' => 'Complete settings stored after the call.' ),
				'ignored'            => array(
					'type'        => 'array',
					'description' => 'Patch fields that were NOT stored because their show_if dependency is unmet in the resulting context. Each: {field, reason}.',
					'items'       => array( 'type' => 'object', 'additionalProperties' => TRUE ),
				),
				'warning'            => array( 'type' => 'string', 'description' => 'Present when the item currently has no dropdown (no sub-items / reusable_block) — the settings are stored but render nothing until it does.' ),
				'regenerated_assets' => array( 'type' => 'boolean', 'description' => 'TRUE once the mega-menu save hook (us_generate_asset_files) has run.' ),
			),
		),
		'execute_callback'    => 'us_mcp_ability_set_menu_dropdown',
		'permission_callback' => 'us_mcp_menus_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );
} );

/**
 * @return array{menus: array}
 */
function us_mcp_ability_list_menus() {
	$menus = array();
	foreach ( wp_get_nav_menus() as $term ) {
		$menus[] = array(
			'id'        => (int) $term->term_id,
			'name'      => (string) $term->name,
			'slug'      => (string) $term->slug,
			'count'     => (int) $term->count,
			'edit_link' => admin_url( 'nav-menus.php?action=edit&menu=' . (int) $term->term_id ),
		);
	}

	return array( 'menus' => $menus );
}

/**
 * @param array|object $input
 * @return array|WP_Error
 */
function us_mcp_ability_get_menu( $input ) {
	$input = (array) $input;
	$menu = us_mcp_menus_resolve_menu( $input );
	if ( is_wp_error( $menu ) ) {
		return $menu;
	}
	$state = us_mcp_menus_read_state( $menu->term_id );
	if ( is_wp_error( $state ) ) {
		return $state;
	}

	return array(
		'menu'  => array(
			'id'        => (int) $menu->term_id,
			'name'      => (string) $menu->name,
			'slug'      => (string) $menu->slug,
			'edit_link' => admin_url( 'nav-menus.php?action=edit&menu=' . (int) $menu->term_id ),
		),
		'items' => us_mcp_menus_state_tree( $state ),
		'count' => count( $state['records'] ),
	);
}

/**
 * @param array|object $input
 * @return array|WP_Error
 */
function us_mcp_ability_set_menu_items( $input ) {
	$input = (array) $input;
	$menu = us_mcp_menus_resolve_menu( $input );
	if ( is_wp_error( $menu ) ) {
		return $menu;
	}

	$state = us_mcp_menus_read_state( $menu->term_id );
	if ( is_wp_error( $state ) ) {
		return $state;
	}
	$before_records = $state['records'];
	$before_tree = us_mcp_menus_state_tree( $state );

	$result = us_mcp_menus_apply_operations( $state, isset( $input['operations'] ) ? $input['operations'] : NULL );
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	$id_map = array();
	$persisted = us_mcp_menus_persist( $menu->term_id, $before_records, $state, $result['structure_changed'], $id_map );
	if ( is_wp_error( $persisted ) ) {
		return $persisted;
	}

	// The hook cache plugins / menu-related integrations listen to after the
	// admin Menus screen saves items — fire it so they invalidate too.
	do_action( 'wp_update_nav_menu', (int) $menu->term_id );

	$after_state = us_mcp_menus_read_state( $menu->term_id );
	if ( is_wp_error( $after_state ) ) {
		return $after_state;
	}

	return array(
		'applied' => us_mcp_menus_map_applied( $result['applied'], $id_map ),
		'before'  => $before_tree,
		'after'   => us_mcp_menus_state_tree( $after_state ),
		'count'   => count( $after_state['records'] ),
	);
}

/**
 * @param array|object $input
 * @return array|WP_Error
 */
function us_mcp_ability_set_menu_dropdown( $input ) {
	$input = (array) $input;
	$menu = us_mcp_menus_resolve_menu( $input );
	if ( is_wp_error( $menu ) ) {
		return $menu;
	}

	$item_id_in = isset( $input['item_id'] ) ? $input['item_id'] : NULL;
	if ( ! us_mcp_menus_looks_like_int( $item_id_in ) ) {
		return new WP_Error(
			'us_mcp_menus_bad_item_id',
			'`item_id` must be a positive integer — get it from upsolution-get-menu.',
			array( 'status' => 400 )
		);
	}
	$item_id = (int) $item_id_in;

	$state = us_mcp_menus_read_state( $menu->term_id );
	if ( is_wp_error( $state ) ) {
		return $state;
	}
	if ( ! isset( $state['records'][ $item_id ] ) ) {
		return new WP_Error(
			'us_mcp_menus_id_not_found',
			sprintf( 'No menu item with id=%d in menu "%s". Item ids come from upsolution-get-menu.', $item_id, $menu->name ),
			array( 'status' => 404 )
		);
	}
	if ( $state['parent'][ $item_id ] !== 0 ) {
		return new WP_Error(
			'us_mcp_menus_dropdown_not_top_level',
			sprintf( 'Item id=%d is not a first-level item — dropdown styling is only honoured on top-level items (the theme reads us_mega_menu_settings only when menu_item_parent is 0). Move it to the top level with a reorder first, or target its top-level ancestor.', $item_id ),
			array( 'status' => 422 )
		);
	}

	$merge = array_key_exists( 'merge', $input ) ? (bool) $input['merge'] : TRUE;
	$current = get_post_meta( $item_id, 'us_mega_menu_settings', TRUE );
	$current = is_array( $current ) ? $current : NULL;
	$before = ! empty( $current )
		? us_mcp_menu_dropdown_for_output( array_merge( us_mcp_menu_dropdown_defaults(), $current ) )
		: NULL;

	$result = us_mcp_menu_dropdown_apply( $current, isset( $input['settings'] ) ? $input['settings'] : NULL, $merge );
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	update_post_meta( $item_id, 'us_mega_menu_settings', $result['stored'] );

	// Same action the admin "Dropdown Settings" save fires — regenerates the
	// site CSS asset files (us_generate_asset_files) so the styling takes effect.
	do_action( 'usof_ajax_mega_menu_save_settings' );

	$out = array(
		'item_id'            => $item_id,
		'before'             => $before,
		'after'              => us_mcp_menu_dropdown_for_output( $result['stored'] ),
		'ignored'            => $result['ignored'],
		'regenerated_assets' => TRUE,
	);
	if ( empty( $state['children'][ $item_id ] ) ) {
		$out['warning'] = sprintf( 'Item id=%d currently has no sub-items or embedded Reusable Block, so it shows no dropdown — these settings are stored but render nothing until it gets a dropdown.', $item_id );
	}
	return $out;
}
