<?php
/**
 * UpSolution MCP — Theme Options site-layout editor.
 *
 * Two abilities for inspecting and changing the three layout sections of
 * Theme Options:
 *
 *   upsolution-get-site-layout  — return every editable field of the
 *                                 `layout` (Site Layout), `pages_layout`
 *                                 (Pages Layout) and `archives_layout`
 *                                 (Archives Layout) sections, with its type,
 *                                 current value and default. Picker fields
 *                                 (Header / Titlebar / Page Template / Reusable
 *                                 Block / Sidebar / Page) report which list in
 *                                 the top-level `available` map holds the valid
 *                                 ids.
 *   upsolution-set-site-layout  — patch any subset of those fields. After the
 *                                 save, CSS assets are regenerated through the
 *                                 usof_after_save → us_generate_asset_files
 *                                 hook chain.
 *
 * Unlike typography.php / color-palette.php — which keep a hardcoded mirror of
 * a fixed field set — the layout field set is DYNAMIC: pages_layout /
 * archives_layout fields are generated per registered public post type and per
 * public taxonomy (see config/theme-options.php), so a static mirror would
 * silently miss custom post types and taxonomies. We therefore derive the
 * editable field set at runtime from the live `theme-options` config, dropping
 * the presentational field types (heading / message / wrapper_*). Validation
 * uses each field's own type + options. Field VALUES are stored raw — usof
 * does no sanitisation on save (see usof_save_options) — so every value is
 * pre-encoded here to the exact on-disk shape the admin UI produces:
 *
 *   slider (responsive)  rawurlencode( json_encode( {default, laptops,
 *                        tablets, mobiles} ) ) — same wire format as typography
 *   slider (scalar)      plain CSS length string ("1300px", "0.3rem")
 *   switch               int 1 / 0
 *   imgradio / radio     the chosen option key, verbatim
 *   select               the chosen option key or, for picker selects, a post
 *                        id (resolved against the live posts) or a literal
 *                        token ("" / "__defaults__" / "default")
 *   checkboxes           comma-joined option keys
 *   color                hex / rgb()/rgba() / "transparent" / "_slug" token —
 *                        validated through the shared palette color checker
 *   upload               attachment id(s)
 *
 * Out of scope: every Theme Options section other than the three named above
 * (Colors are owned by upsolution-set-palette, Typography by
 * upsolution-set-typography, etc.).
 *
 * @package usCore\Mcp
 */

defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

// Permission gate is the shared us_mcp_theme_option_permission_callback()
// from abilities/theme-option.php — edit_theme_options across every ability
// that touches Theme Options.

/**
 * The three Theme Options sections this tool covers, in screen order.
 *
 * @return string[]
 */
function us_mcp_site_layout_sections() {
	return array( 'layout', 'pages_layout', 'archives_layout' );
}

/**
 * Field `type`s that carry no editable value — pure layout/markup in the admin
 * UI. Skipped when building the field spec.
 *
 * @return string[]
 */
function us_mcp_site_layout_presentational_types() {
	return array( 'heading', 'message', 'wrapper_start', 'wrapper_end' );
}

/**
 * Field `type`s this tool knows how to read and write. Any other type in the
 * three sections is skipped (it would be unsafe to round-trip a value whose
 * storage shape we don't model).
 *
 * @return string[]
 */
function us_mcp_site_layout_editable_types() {
	return array( 'imgradio', 'radio', 'select', 'switch', 'slider', 'color', 'upload', 'checkboxes' );
}

/**
 * Responsive breakpoint keys in canonical order. Mirrors the keys
 * us_get_responsive_states() emits; falls back to the literal order if the
 * helper isn't loaded.
 *
 * @return string[]
 */
function us_mcp_site_layout_responsive_keys() {
	if ( function_exists( 'us_get_responsive_states' ) ) {
		$keys = array_values( (array) us_get_responsive_states( /* only_keys */ TRUE ) );
		if ( ! empty( $keys ) ) {
			return $keys;
		}
	}
	return array( 'default', 'laptops', 'tablets', 'mobiles' );
}

/**
 * Build the editable field spec for the three layout sections from the live
 * Theme Options config. Statically cached per request.
 *
 * Each entry:
 *   section        string   one of us_mcp_site_layout_sections()
 *   type           string   field type (one of us_mcp_site_layout_editable_types())
 *   title          string   human label (tags stripped)
 *   std            mixed    default value from the config
 *   is_responsive  bool     slider accepts a {default,…} object
 *   units          string[] slider — allowed CSS units (option keys)
 *   options        string[] imgradio/radio/select(static)/checkboxes — the
 *                           closed enum of acceptable option keys; for picker
 *                           selects this is just the literal tokens ("" /
 *                           "__defaults__" / "default")
 *   with_gradient  bool     color — whether linear-gradient(...) is accepted
 *   hints_for      ?string  select — the post type behind the picker (null for
 *                           static selects and regular-sidebar pickers)
 *   source         ?string  select/checkboxes — key into the context maps that
 *                           lists the valid ids (headers / page_blocks /
 *                           content_templates / pages / sidebars / post_types);
 *                           null for static-option selects
 *   show_if        mixed    raw show_if condition, when the field has one
 *
 * @return array<string, array>
 */
function us_mcp_site_layout_build_spec() {
	static $spec = NULL;
	if ( $spec !== NULL ) {
		return $spec;
	}
	$spec = array();

	$config = function_exists( 'us_config' ) ? us_config( 'theme-options' ) : NULL;
	if ( ! is_array( $config ) ) {
		return $spec;
	}

	$skip     = us_mcp_site_layout_presentational_types();
	$editable = us_mcp_site_layout_editable_types();

	foreach ( us_mcp_site_layout_sections() as $section ) {
		if ( empty( $config[ $section ]['fields'] ) OR ! is_array( $config[ $section ]['fields'] ) ) {
			continue;
		}
		foreach ( $config[ $section ]['fields'] as $key => $def ) {
			if ( ! is_array( $def ) OR empty( $def['type'] ) ) {
				continue;
			}
			$type = $def['type'];
			if ( in_array( $type, $skip, TRUE ) OR ! in_array( $type, $editable, TRUE ) ) {
				continue;
			}
			// place_if === FALSE means the field is not active on this install
			// (e.g. sidebar pickers when the Sidebar feature is disabled). The
			// config keeps it in the array with the flag, so honour it.
			if ( array_key_exists( 'place_if', $def ) AND ! $def['place_if'] ) {
				continue;
			}

			$entry = array(
				'section'       => $section,
				'type'          => $type,
				'title'         => us_mcp_site_layout_field_label( $key, $def ),
				'std'           => array_key_exists( 'std', $def ) ? $def['std'] : '',
				'is_responsive' => ! empty( $def['is_responsive'] ),
			);

			if ( $type === 'slider' ) {
				$entry['units'] = ( isset( $def['options'] ) AND is_array( $def['options'] ) )
					? array_map( 'strval', array_keys( $def['options'] ) )
					: array();
			}
			if ( in_array( $type, array( 'imgradio', 'radio', 'select', 'checkboxes' ), TRUE ) ) {
				$entry['options'] = ( isset( $def['options'] ) AND is_array( $def['options'] ) )
					? array_map( 'strval', array_keys( $def['options'] ) )
					: array();
			}
			if ( $type === 'color' ) {
				$entry['with_gradient'] = ! empty( $def['with_gradient'] );
			}
			if ( $type === 'select' ) {
				$entry['hints_for'] = isset( $def['hints_for'] ) ? $def['hints_for'] : NULL;
				$entry['source']    = us_mcp_site_layout_field_source( $key, $def );
			}
			if ( $type === 'checkboxes' ) {
				// The only checkboxes field in these sections is the search
				// post-type exclusion — its values are public post types.
				$entry['source'] = 'post_types';
			}
			if ( isset( $def['show_if'] ) ) {
				$entry['show_if'] = $def['show_if'];
			}

			$spec[ $key ] = $entry;
		}
	}

	return $spec;
}

/**
 * Resolve which context list a picker `select` field draws its valid ids from.
 *
 * @param string $key
 * @param array  $def  Raw config field definition.
 * @return string|null  Context-map key, or NULL for a static-option select.
 */
function us_mcp_site_layout_field_source( $key, $def ) {
	// A null `hints_for` (the regular-sidebar case sets it to NULL explicitly)
	// trips isset() to FALSE — exactly the fall-through we want.
	$hints = isset( $def['hints_for'] ) ? $def['hints_for'] : NULL;

	if ( $hints === 'us_header' ) {
		return 'headers';
	}
	if ( $hints === 'us_content_template' ) {
		return 'content_templates';
	}
	if ( $hints === 'page' ) {
		return 'pages';
	}
	if ( $hints === 'us_page_block' ) {
		// Titlebar / Footer pickers, and Sidebar pickers when Reusable Blocks
		// are used as sidebars.
		return 'page_blocks';
	}
	// Sidebar picker on a site using regular WP sidebars — valid values are
	// registered sidebar ids.
	if ( strpos( $key, 'sidebar' ) === 0 AND substr( $key, -3 ) === '_id' ) {
		return 'sidebars';
	}
	// Static-option select (e.g. row_height).
	return NULL;
}

/**
 * Human label for a field, tags + entities stripped. Falls back to the field
 * `text` (color pickers use it) then the option key.
 *
 * @param string $key
 * @param array  $def
 * @return string
 */
function us_mcp_site_layout_field_label( $key, $def ) {
	$label = '';
	if ( isset( $def['title'] ) AND $def['title'] !== '' ) {
		$label = $def['title'];
	} elseif ( isset( $def['text'] ) AND $def['text'] !== '' ) {
		$label = $def['text'];
	}
	$label = trim( html_entity_decode( wp_strip_all_tags( (string) $label ), ENT_QUOTES ) );
	return $label !== '' ? $label : $key;
}

/**
 * Build the per-request context of valid picker ids. Picker selects validate
 * post-id values against these lists, and the read tool returns them under
 * `available` so an agent has the ids + titles in one round-trip.
 *
 * @return array{headers: array, page_blocks: array, content_templates: array, pages: array, sidebars: array, post_types: array}
 */
function us_mcp_site_layout_build_context() {
	$titles = function_exists( 'us_get_all_posts_titles_for' )
		? (array) us_get_all_posts_titles_for( array( 'us_header', 'us_page_block', 'us_content_template', 'page' ) )
		: array();

	$page_blocks = isset( $titles['us_page_block'] ) ? (array) $titles['us_page_block'] : array();

	// Sidebars are Reusable Blocks when that Theme Option is on, registered WP
	// sidebars otherwise — mirrors config/theme-options.php.
	$page_blocks_as_sidebars = function_exists( 'us_get_option' ) ? us_get_option( 'enable_page_blocks_for_sidebars', 0 ) : 0;
	$sidebars = $page_blocks_as_sidebars
		? $page_blocks
		: ( function_exists( 'us_get_sidebars' ) ? (array) us_get_sidebars() : array() );

	return array(
		'headers'           => isset( $titles['us_header'] ) ? (array) $titles['us_header'] : array(),
		'page_blocks'       => $page_blocks,
		'content_templates' => isset( $titles['us_content_template'] ) ? (array) $titles['us_content_template'] : array(),
		'pages'             => isset( $titles['page'] ) ? (array) $titles['page'] : array(),
		'sidebars'          => $sidebars,
		'post_types'        => function_exists( 'us_get_public_post_types' ) ? (array) us_get_public_post_types() : array(),
	);
}

/**
 * Decode a stored field value into the agent-facing shape: responsive sliders
 * become {default,…} objects, checkboxes become arrays, switches become ints,
 * everything else is a string. NULL passes through (the field is at default).
 *
 * @param array $entry  Field spec entry.
 * @param mixed $value  Raw stored value (or std).
 * @return mixed
 */
function us_mcp_site_layout_decode_value( $entry, $value ) {
	if ( $value === NULL ) {
		return NULL;
	}
	$type = $entry['type'];

	if ( $type === 'switch' ) {
		return (int) $value ? 1 : 0;
	}
	if ( $type === 'checkboxes' ) {
		if ( is_array( $value ) ) {
			$value = implode( ',', $value );
		}
		$value = (string) $value;
		if ( $value === '' ) {
			return array();
		}
		return array_values( array_filter( array_map( 'trim', explode( ',', $value ) ), function ( $s ) {
			return $s !== '';
		} ) );
	}
	if ( $type === 'slider' AND ! empty( $entry['is_responsive'] ) AND is_string( $value ) ) {
		$responsive = us_mcp_site_layout_decode_responsive( $value );
		if ( $responsive !== NULL ) {
			return $responsive;
		}
		return $value; // single value applied to all breakpoints
	}
	if ( is_scalar( $value ) ) {
		return (string) $value;
	}
	return $value;
}

/**
 * Decode a responsive slider value (rawurlencoded JSON object) into an ordered
 * breakpoint map. Returns NULL when the value isn't a responsive object (a
 * plain "5vmin" decodes to NULL and the caller keeps the scalar).
 *
 * @param string $value
 * @return array|null
 */
function us_mcp_site_layout_decode_responsive( $value ) {
	$trimmed = trim( $value );
	if ( $trimmed === '' ) {
		return NULL;
	}
	$decoded = json_decode( rawurldecode( $trimmed ), /* assoc */ TRUE );
	if ( ! is_array( $decoded ) ) {
		return NULL;
	}
	$out = array();
	foreach ( us_mcp_site_layout_responsive_keys() as $bp ) {
		if ( array_key_exists( $bp, $decoded ) ) {
			$out[ $bp ] = $decoded[ $bp ];
		}
	}
	return ! empty( $out ) ? $out : NULL;
}

/**
 * Validate one scalar slider value ("1300px", "0.3rem", "-2vh") against the
 * field's allowed units.
 *
 * @param string $key
 * @param array  $entry
 * @param string $value
 * @return true|WP_Error
 */
function us_mcp_site_layout_check_slider_scalar( $key, $entry, $value ) {
	$value = trim( $value );
	if ( $value === '' ) {
		return new WP_Error(
			'us_mcp_site_layout_empty_slider',
			sprintf( 'Field "%s" needs a CSS length like "1300px" or "0.3rem" — pass null to reset it to the default instead.', $key ),
			array( 'status' => 400 )
		);
	}
	if ( ! preg_match( '/^(-?\d+(?:\.\d+)?)\s*([a-z%]*)$/i', $value, $m ) ) {
		return new WP_Error(
			'us_mcp_site_layout_bad_slider',
			sprintf( 'Value "%s" for "%s" is not a CSS length (number with an optional unit, e.g. "1300px", "5vmin").', $value, $key ),
			array( 'status' => 400 )
		);
	}
	$unit  = strtolower( $m[2] );
	$units = isset( $entry['units'] ) ? $entry['units'] : array();
	if ( $unit !== '' AND ! empty( $units ) AND ! in_array( $unit, $units, TRUE ) ) {
		return new WP_Error(
			'us_mcp_site_layout_bad_unit',
			sprintf( 'Unit "%s" is not allowed for "%s". Allowed units: %s.', $unit, $key, implode( ', ', $units ) ),
			array( 'status' => 400 )
		);
	}
	return TRUE;
}

/**
 * Validate + encode a responsive slider object into the on-disk URL-encoded
 * JSON string.
 *
 * @param string $key
 * @param array  $entry
 * @param array  $value  {default,…} map supplied by the agent.
 * @return string|WP_Error
 */
function us_mcp_site_layout_encode_responsive_slider( $key, $entry, $value ) {
	$keys    = us_mcp_site_layout_responsive_keys();
	$unknown = array_diff( array_keys( $value ), $keys );
	if ( ! empty( $unknown ) ) {
		return new WP_Error(
			'us_mcp_site_layout_unknown_breakpoint',
			sprintf( 'Unknown responsive breakpoint(s) for "%s": %s. Allowed: %s.', $key, implode( ', ', $unknown ), implode( ', ', $keys ) ),
			array( 'status' => 400 )
		);
	}
	if ( ! array_key_exists( 'default', $value ) ) {
		return new WP_Error(
			'us_mcp_site_layout_missing_default',
			sprintf( 'Responsive value for "%s" must include a "default" breakpoint.', $key ),
			array( 'status' => 400 )
		);
	}
	$normalised = array();
	foreach ( $keys as $bp ) {
		if ( ! array_key_exists( $bp, $value ) ) {
			continue;
		}
		$bp_value = $value[ $bp ];
		if ( $bp_value !== '' AND ! is_scalar( $bp_value ) ) {
			return new WP_Error(
				'us_mcp_site_layout_bad_breakpoint_value',
				sprintf( 'Breakpoint "%s" of "%s" must be a CSS length string.', $bp, $key ),
				array( 'status' => 400 )
			);
		}
		$bp_value = (string) $bp_value;
		// An empty breakpoint is allowed — it inherits the wider one.
		if ( $bp_value !== '' ) {
			$check = us_mcp_site_layout_check_slider_scalar( $key, $entry, $bp_value );
			if ( is_wp_error( $check ) ) {
				return $check;
			}
		}
		$normalised[ $bp ] = $bp_value;
	}
	return rawurlencode( wp_json_encode( $normalised ) );
}

/**
 * Normalise + validate one field value into its on-disk form. NULL means
 * "delete the key" (reset to default). On a bad value returns WP_Error.
 *
 * @param string $key
 * @param array  $entry    Field spec entry.
 * @param mixed  $value    Agent-supplied value.
 * @param array  $context  us_mcp_site_layout_build_context() result.
 * @return string|int|null|WP_Error
 */
function us_mcp_site_layout_normalise_field( $key, $entry, $value, $context ) {
	// null → delete the key (the field falls back to its configured default).
	if ( $value === NULL ) {
		return NULL;
	}

	$type  = $entry['type'];
	$label = $entry['title'];

	switch ( $type ) {

		case 'switch':
			if ( is_bool( $value ) ) {
				return $value ? 1 : 0;
			}
			if ( in_array( $value, array( 1, '1', 'true', 'on' ), TRUE ) ) {
				return 1;
			}
			if ( in_array( $value, array( 0, '0', 'false', 'off', '' ), TRUE ) ) {
				return 0;
			}
			return new WP_Error(
				'us_mcp_site_layout_bad_switch',
				sprintf( 'Field "%s" is a switch — pass true or false.', $key ),
				array( 'status' => 400 )
			);

		case 'slider':
			if ( is_array( $value ) ) {
				if ( empty( $entry['is_responsive'] ) ) {
					return new WP_Error(
						'us_mcp_site_layout_not_responsive',
						sprintf( 'Field "%s" does not accept a responsive object — pass a single CSS length string.', $key ),
						array( 'status' => 400 )
					);
				}
				return us_mcp_site_layout_encode_responsive_slider( $key, $entry, $value );
			}
			if ( ! is_scalar( $value ) ) {
				return new WP_Error(
					'us_mcp_site_layout_bad_slider_type',
					sprintf( 'Field "%s" must be a CSS length string or, for responsive fields, a {default,…} object.', $key ),
					array( 'status' => 400 )
				);
			}
			$value = (string) $value;
			$check = us_mcp_site_layout_check_slider_scalar( $key, $entry, $value );
			if ( is_wp_error( $check ) ) {
				return $check;
			}
			return $value;

		case 'imgradio':
		case 'radio':
			if ( ! is_scalar( $value ) ) {
				return new WP_Error(
					'us_mcp_site_layout_bad_choice_type',
					sprintf( 'Field "%s" must be one of its option keys (a string).', $key ),
					array( 'status' => 400 )
				);
			}
			$value   = (string) $value;
			$options = isset( $entry['options'] ) ? $entry['options'] : array();
			if ( ! in_array( $value, $options, TRUE ) ) {
				return new WP_Error(
					'us_mcp_site_layout_bad_choice',
					sprintf( 'Value "%s" is not allowed for "%s". Allowed: %s.', $value, $key, implode( ', ', $options ) ),
					array( 'status' => 400 )
				);
			}
			return $value;

		case 'select':
			if ( ! is_scalar( $value ) ) {
				return new WP_Error(
					'us_mcp_site_layout_bad_select_type',
					sprintf( 'Field "%s" must be a string (an option key or a post id).', $key ),
					array( 'status' => 400 )
				);
			}
			$value  = (string) $value;
			$source = isset( $entry['source'] ) ? $entry['source'] : NULL;
			$tokens = isset( $entry['options'] ) ? $entry['options'] : array();

			if ( $source === NULL ) {
				// Static-option select (closed enum).
				if ( ! in_array( $value, $tokens, TRUE ) ) {
					return new WP_Error(
						'us_mcp_site_layout_bad_select',
						sprintf( 'Value "%s" is not allowed for "%s". Allowed: %s.', $value, $key, implode( ', ', $tokens ) ),
						array( 'status' => 400 )
					);
				}
				return $value;
			}

			// Picker select — a literal token (e.g. "" / "__defaults__") or a
			// valid id from the matching context list.
			if ( in_array( $value, $tokens, TRUE ) ) {
				return $value;
			}
			$valid_ids = isset( $context[ $source ] ) ? array_map( 'strval', array_keys( $context[ $source ] ) ) : array();
			if ( in_array( $value, $valid_ids, TRUE ) ) {
				return $value;
			}
			return new WP_Error(
				'us_mcp_site_layout_unknown_id',
				sprintf(
					'Value "%s" for "%s" is neither an accepted token (%s) nor a known %s id. Call upsolution-get-site-layout — its `available.%s` map lists the valid ids.',
					$value,
					$key,
					implode( ', ', array_map( function ( $t ) {
						return $t === '' ? '""' : $t;
					}, $tokens ) ),
					$source,
					$source
				),
				array( 'status' => 422 )
			);

		case 'checkboxes':
			if ( is_string( $value ) ) {
				$value = ( $value === '' ) ? array() : explode( ',', $value );
			}
			if ( ! is_array( $value ) ) {
				return new WP_Error(
					'us_mcp_site_layout_bad_checkboxes',
					sprintf( 'Field "%s" takes an array of option keys (or "" for none).', $key ),
					array( 'status' => 400 )
				);
			}
			$valid = array_keys( isset( $context['post_types'] ) ? $context['post_types'] : array() );
			$clean = array();
			foreach ( $value as $item ) {
				$item = trim( (string) $item );
				if ( $item === '' ) {
					continue;
				}
				if ( ! in_array( $item, $valid, TRUE ) ) {
					return new WP_Error(
						'us_mcp_site_layout_bad_checkbox_value',
						sprintf( 'Value "%s" for "%s" is not a public post type. Allowed: %s.', $item, $key, implode( ', ', $valid ) ),
						array( 'status' => 400 )
					);
				}
				if ( ! in_array( $item, $clean, TRUE ) ) {
					$clean[] = $item;
				}
			}
			return implode( ',', $clean );

		case 'color':
			if ( ! is_scalar( $value ) ) {
				return new WP_Error(
					'us_mcp_site_layout_bad_color_type',
					sprintf( 'Field "%s" must be a color string (or "" to clear).', $key ),
					array( 'status' => 400 )
				);
			}
			$value = (string) $value;
			if ( function_exists( 'us_mcp_palette_check_color' ) ) {
				// Reuse the shared palette color checker so layout colors accept
				// exactly what set-palette accepts (hex / rgb()/rgba() /
				// "transparent" / "_slug" tokens / linear-gradient when allowed).
				return us_mcp_palette_check_color( $value, ! empty( $entry['with_gradient'] ), $label );
			}
			return $value;

		case 'upload':
			if ( ! is_scalar( $value ) ) {
				return new WP_Error(
					'us_mcp_site_layout_bad_upload_type',
					sprintf( 'Field "%s" takes an attachment id (or "" for none).', $key ),
					array( 'status' => 400 )
				);
			}
			$value = trim( (string) $value );
			if ( $value === '' ) {
				return '';
			}
			$ids = array_values( array_filter( array_map( 'trim', explode( ',', $value ) ), function ( $s ) {
				return $s !== '';
			} ) );
			foreach ( $ids as $id ) {
				if ( ! ctype_digit( $id ) OR (int) $id < 1 OR get_post_type( (int) $id ) !== 'attachment' ) {
					return new WP_Error(
						'us_mcp_site_layout_bad_attachment',
						sprintf( 'Value "%s" for "%s" is not a valid Media Library attachment id. Use upsolution-list-media / upsolution-upload-media to obtain one.', $id, $key ),
						array( 'status' => 422 )
					);
				}
			}
			return implode( ',', $ids );

		default:
			return new WP_Error(
				'us_mcp_site_layout_unsupported_type',
				sprintf( 'Field "%s" has an unsupported type "%s".', $key, $type ),
				array( 'status' => 400 )
			);
	}
}

/**
 * Validate the agent's site-layout `fields` patch AND apply it to the supplied
 * options array. No DB write — the caller decides whether to persist via
 * usof_save_options (the set-site-layout tool) or keep the result in-memory
 * (the create-preview tool).
 *
 * On a bad input the options array is left untouched (all-or-nothing).
 *
 * @param mixed $fields   Map of option-key → new value.
 * @param array $options  Reference to the options array to mutate.
 * @return array{applied: string[], before: array, after: array}|WP_Error
 */
function us_mcp_site_layout_apply_to_options( $fields, array &$options ) {
	if ( ! is_array( $fields ) ) {
		return new WP_Error(
			'us_mcp_site_layout_missing_fields',
			'Pass a `fields` object mapping option keys to new values.',
			array( 'status' => 400 )
		);
	}
	if ( empty( $fields ) ) {
		return new WP_Error(
			'us_mcp_site_layout_no_op',
			'No fields supplied — nothing to update.',
			array( 'status' => 400 )
		);
	}

	$spec = us_mcp_site_layout_build_spec();
	if ( empty( $spec ) ) {
		return new WP_Error(
			'us_mcp_site_layout_spec_unavailable',
			'The Theme Options layout config could not be loaded — us-core config helpers did not run.',
			array( 'status' => 503 )
		);
	}
	$context = us_mcp_site_layout_build_context();

	// Pre-validate every field BEFORE touching $options — a single bad field
	// aborts the whole call.
	$normalised = array();
	foreach ( $fields as $key => $value ) {
		$key = (string) $key;
		if ( ! isset( $spec[ $key ] ) ) {
			return new WP_Error(
				'us_mcp_site_layout_unknown_field',
				sprintf( 'Unknown layout field "%s". Call upsolution-get-site-layout for the editable keys of the layout / pages_layout / archives_layout sections.', $key ),
				array( 'status' => 400 )
			);
		}
		$result = us_mcp_site_layout_normalise_field( $key, $spec[ $key ], $value, $context );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$normalised[ $key ] = $result;
	}

	$applied = array();
	$before  = array();
	$after   = array();

	foreach ( $normalised as $key => $new_value ) {
		$entry = $spec[ $key ];
		$had   = array_key_exists( $key, $options );
		$prev  = $had ? $options[ $key ] : NULL;
		$before[ $key ] = $had ? us_mcp_site_layout_decode_value( $entry, $prev ) : NULL;

		if ( $new_value === NULL ) {
			// Delete → reset to default.
			if ( $had ) {
				unset( $options[ $key ] );
				$applied[] = $key;
			}
			$after[ $key ] = NULL;
			continue;
		}
		if ( $had AND (string) $prev === (string) $new_value ) {
			// No-op write.
			$after[ $key ] = $before[ $key ];
			continue;
		}
		$options[ $key ] = $new_value;
		$applied[]       = $key;
		$after[ $key ]   = us_mcp_site_layout_decode_value( $entry, $new_value );
	}

	return array(
		'applied' => $applied,
		'before'  => $before,
		'after'   => $after,
	);
}

/**
 * Read response for one section: title + a map of field-key → metadata + the
 * current value / default. Reads from the supplied (live or preview) options.
 *
 * @param string $section
 * @param array  $spec     Full field spec.
 * @param array  $options  Options array to read current values from.
 * @return array
 */
function us_mcp_site_layout_read_section( $section, $spec, array $options ) {
	$config = function_exists( 'us_config' ) ? us_config( 'theme-options' ) : array();
	$title  = isset( $config[ $section ]['title'] )
		? trim( html_entity_decode( wp_strip_all_tags( (string) $config[ $section ]['title'] ), ENT_QUOTES ) )
		: $section;

	$fields = array();
	foreach ( $spec as $key => $entry ) {
		if ( $entry['section'] !== $section ) {
			continue;
		}
		$had        = array_key_exists( $key, $options );
		$is_default = ! $had;
		$raw        = $is_default ? $entry['std'] : $options[ $key ];

		$field = array(
			'type'       => $entry['type'],
			'title'      => $entry['title'],
			'value'      => us_mcp_site_layout_decode_value( $entry, $raw ),
			'default'    => us_mcp_site_layout_decode_value( $entry, $entry['std'] ),
			'is_default' => $is_default,
		);
		if ( $entry['type'] === 'slider' ) {
			$field['is_responsive'] = ! empty( $entry['is_responsive'] );
			$field['units']         = isset( $entry['units'] ) ? $entry['units'] : array();
		}
		if ( $entry['type'] === 'color' ) {
			$field['with_gradient'] = ! empty( $entry['with_gradient'] );
		}
		$source = isset( $entry['source'] ) ? $entry['source'] : NULL;
		if ( $source !== NULL ) {
			// Open id-list field — name the available map and (for selects) the
			// literal tokens it also accepts.
			$field['source'] = $source;
			if ( $entry['type'] === 'select' ) {
				$field['tokens'] = isset( $entry['options'] ) ? $entry['options'] : array();
			}
		} elseif ( isset( $entry['options'] ) ) {
			// Closed enum.
			$field['options'] = $entry['options'];
		}
		if ( isset( $entry['show_if'] ) ) {
			$field['show_if'] = $entry['show_if'];
		}

		$fields[ $key ] = $field;
	}

	return array(
		'title'  => $title,
		'fields' => $fields,
	);
}

add_action( 'wp_abilities_api_init', function () {

	wp_register_ability( 'upsolution/get-site-layout', array(
		'label'               => 'Read the Site Layout / Pages Layout / Archives Layout settings',
		'description'         => 'Read the three layout sections of Theme Options. Returns `sections` with one entry each: "layout" (Site Layout — site canvas type, content/sidebar widths, header/row horizontal indents, column gap, row vertical spacing, text/site border radius, footer reveal, plus the responsive breakpoints), "pages_layout" (the default Header / Titlebar / Page Template / Sidebar / Footer assigned to pages and to each public post type, plus Search Results and 404 page), and "archives_layout" (the same assignment set for taxonomy archives, custom-post-type archives and author pages). Each field reports its `type`, current `value`, `default`, and `is_default`. Sliders also report `is_responsive` and the CSS `units` they accept; a responsive value comes back as a {default, laptops, tablets, mobiles} object. Closed-choice fields (image radio / radio / static select) report their `options`. Picker fields (Header / Titlebar / Page Template / Reusable Block / Sidebar / Page) report a `source` naming which list in the top-level `available` map holds the valid ids (id → title), plus the literal `tokens` they also accept (e.g. "" = do not display, "__defaults__" = inherit the parent section, "default" = theme default). Pass `sections` to read a subset; omit for all three. Pass include_available=false to skip the id lists. Change values with upsolution-set-site-layout.',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'       => 'object',
			'properties' => array(
				'sections' => array(
					'type'        => 'array',
					'description' => 'Subset of sections to read. Omit or pass an empty array for all three. Unknown names are ignored.',
					'items'       => array( 'type' => 'string', 'enum' => us_mcp_site_layout_sections() ),
				),
				'include_available' => array(
					'type'        => 'boolean',
					'description' => 'Include the top-level `available` map (headers / page_blocks / content_templates / pages / sidebars / post_types → id-or-key → title). Defaults to true; set false to trim the response when you only need current values.',
					'default'     => TRUE,
				),
			),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'required'   => array( 'sections' ),
			'properties' => array(
				'sections'  => array(
					'type'                 => 'object',
					'description'          => 'One key per requested section (layout / pages_layout / archives_layout). Each is {title, fields}, where fields maps an option key to its metadata + current value.',
					'additionalProperties' => TRUE,
				),
				'available' => array(
					'type'                 => 'object',
					'description'          => 'Valid ids for the picker fields, grouped by source: headers, page_blocks, content_templates, pages (id → title), sidebars (id → name), post_types (key → label). Present unless include_available=false.',
					'additionalProperties' => TRUE,
				),
			),
		),
		'execute_callback'    => 'us_mcp_ability_get_site_layout',
		'permission_callback' => 'us_mcp_theme_option_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );

	wp_register_ability( 'upsolution/set-site-layout', array(
		'label'               => 'Patch the Site Layout / Pages Layout / Archives Layout settings',
		'description'         => 'Patch any subset of the three layout sections of Theme Options. Pass `fields` as a flat map of option-key → new value; option keys are unique across the three sections. Unlisted keys are left untouched; null clears a key so it falls back to its configured default. Read upsolution-get-site-layout first — it lists every editable key, the value each accepts, and (for the Header / Titlebar / Page Template / Reusable Block / Sidebar / Page pickers) the valid ids under `available`. Value formats by field type: sliders take a CSS length string like "1300px" / "0.3rem" using one of the units the read tool lists, and responsive sliders also accept a {default, laptops, tablets, mobiles} object (default is required, other breakpoints may be "" to inherit); switches take true / false; image-radio / radio / static select take one of the option keys; picker selects take a post id from the matching `available` list or a literal token ("" = do not display, "__defaults__" = inherit, "default"); the Search-exclusion checkboxes take an array of post-type keys; color fields take a hex / rgb()/rgba() / "transparent" / palette token "_slug" (same syntax as upsolution-set-palette) or "" to clear; the Body Background upload takes a Media Library attachment id. A single invalid field rejects the whole call — nothing is written. Saving regenerates the site CSS asset files, so changes take effect on the next page load. To preview the same change without saving, pass the identical `fields` map to upsolution-create-preview under `site_layout`.',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'                 => 'object',
			'required'             => array( 'fields' ),
			'additionalProperties' => FALSE,
			'properties'           => array(
				'fields' => array(
					'type'                 => 'object',
					'description'          => 'Map of option key → new value (string / number / boolean / array / object / null). Keys must be editable fields of the layout / pages_layout / archives_layout sections — see upsolution-get-site-layout. null clears a key (resets it to the default).',
					'additionalProperties' => TRUE,
				),
			),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'required'   => array( 'applied', 'before', 'after', 'regenerated_assets' ),
			'properties' => array(
				'applied'            => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => 'Option keys whose stored value actually changed (no-op writes excluded; a cleared key counts as changed).',
				),
				'before'             => array( 'type' => 'object', 'description' => 'Pre-write value of each submitted key (responsive JSON decoded; null = was at default).', 'additionalProperties' => TRUE ),
				'after'              => array( 'type' => 'object', 'description' => 'Post-write value of each submitted key (null = now at default after a clear).', 'additionalProperties' => TRUE ),
				'regenerated_assets' => array( 'type' => 'boolean', 'description' => 'TRUE once usof_save_options has run and the usof_after_save hook chain (including us_generate_asset_files) has fired.' ),
			),
		),
		'execute_callback'    => 'us_mcp_ability_set_site_layout',
		'permission_callback' => 'us_mcp_theme_option_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );
} );

/**
 * @param array $input
 * @return array
 */
function us_mcp_ability_get_site_layout( $input ) {
	$input = (array) $input;

	$requested = ( isset( $input['sections'] ) && is_array( $input['sections'] ) && ! empty( $input['sections'] ) )
		? array_values( array_intersect( us_mcp_site_layout_sections(), $input['sections'] ) )
		: us_mcp_site_layout_sections();

	$include_available = array_key_exists( 'include_available', $input ) ? (bool) $input['include_available'] : TRUE;

	global $usof_options;
	if ( function_exists( 'usof_load_options_once' ) ) {
		usof_load_options_once();
	}
	$options = is_array( $usof_options ) ? $usof_options : array();

	$spec = us_mcp_site_layout_build_spec();

	$sections = array();
	foreach ( $requested as $section ) {
		$sections[ $section ] = us_mcp_site_layout_read_section( $section, $spec, $options );
	}

	$out = array( 'sections' => $sections );

	if ( $include_available ) {
		$context   = us_mcp_site_layout_build_context();
		$available = array();
		foreach ( $context as $name => $map ) {
			// Cast so an empty list serialises as {} rather than [].
			$available[ $name ] = (object) $map;
		}
		$out['available'] = $available;
	}

	return $out;
}

/**
 * @param array $input
 * @return array|WP_Error
 */
function us_mcp_ability_set_site_layout( $input ) {
	$input  = (array) $input;
	$fields = isset( $input['fields'] ) ? $input['fields'] : NULL;

	if ( ! function_exists( 'usof_save_options' ) OR ! function_exists( 'usof_load_options_once' ) ) {
		return new WP_Error(
			'us_mcp_site_layout_core_not_loaded',
			'usof_save_options() / usof_load_options_once() are not available — us-core usof loader did not run.',
			array( 'status' => 503 )
		);
	}

	global $usof_options;
	usof_load_options_once();
	if ( ! is_array( $usof_options ) ) {
		$usof_options = array();
	}
	$updated_options = $usof_options;

	$result = us_mcp_site_layout_apply_to_options( $fields, $updated_options );
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	usof_save_options( $updated_options );

	return array(
		'applied'            => $result['applied'],
		'before'             => $result['before'],
		'after'              => $result['after'],
		'regenerated_assets' => TRUE,
	);
}
