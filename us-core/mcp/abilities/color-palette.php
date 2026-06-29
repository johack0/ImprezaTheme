<?php
/**
 * UpSolution MCP — Theme Options color palette editor.
 *
 * Two abilities for inspecting and changing the site-wide color fields and
 * the Custom Global Colors group in Theme Options → Colors:
 *
 *   upsolution-get-palette  — return the current value of every color picker
 *                             in the requested section(s) (header, content,
 *                             footer) plus the Custom Global Colors array.
 *   upsolution-set-palette  — patch any subset of the same fields and / or
 *                             replace the Custom Global Colors array. Saving
 *                             triggers regeneration of the CSS asset files
 *                             via the usof_after_save → us_generate_asset_files
 *                             hook chain.
 *
 * Both tools work off a hardcoded mirror of the `colors` section of
 * plugins/us-core/config/theme-options.php — see us_mcp_palette_field_spec().
 * When new color fields are added to that config, extend the spec here so the
 * agent can reach them.
 *
 * Out of scope: the predefined "alternate" / "dark" / ... color SCHEMES (those
 * are end-user templates returned by us_get_color_schemes(), not values the
 * agent should pick) and the per-shortcode `color_scheme="..."` attribute
 * (that's a fixed enum on the shortcode side, see composition-rules doc).
 *
 * @package usCore\Mcp
 */

defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

// Permission gate is the shared us_mcp_theme_option_permission_callback()
// from abilities/theme-option.php — edit_theme_options across every ability
// that touches Theme Options (typography, palette, buttons, preview).

/**
 * Hardcoded mirror of the color-picker fields under the `colors` section of
 * config/theme-options.php. Section keys mirror the six h_colors_* groupings
 * the Theme Options admin UI renders, in screen order. The first two are site
 * chrome; the latter four are row palettes selected via the per-row
 * `color_scheme` attribute (see vc_row.php — the enum has six values, of which
 * four target these picker groups):
 *
 *   header             — "Header colors"            (active header: middle +
 *                                                    transparent state + chrome
 *                                                    toolbar — site chrome)
 *   alternate_header   — "Alternate Header colors"  (the top bar above the
 *                                                    header — color_header_top_*;
 *                                                    site chrome)
 *   content            — "Content colors"           (color_content_* — the
 *                                                    default row palette, used
 *                                                    by rows that do NOT set a
 *                                                    color_scheme attribute)
 *   alternate_content  — "Alternate Content colors" (color_alt_content_* —
 *                                                    applied to rows with
 *                                                    color_scheme="alternate")
 *   footer             — "Footer colors"            (color_footer_* — applied
 *                                                    to rows with
 *                                                    color_scheme="footer-bottom";
 *                                                    NOT automatically used by
 *                                                    the page's footer area)
 *   alternate_footer   — "Alternate Footer colors"  (color_subfooter_* —
 *                                                    applied to rows with
 *                                                    color_scheme="footer-top";
 *                                                    same row-scheme mechanism
 *                                                    as `footer`, legacy key
 *                                                    prefix)
 *
 * Per key:
 *   gradient bool — whether the original `with_gradient` flag is TRUE; gates
 *                   acceptance of linear-gradient(...) on the write side.
 *                   Only linear-gradient is supported (the color picker at
 *                   usof/js/field_color.js:239 hardcodes the regex
 *                   /^linear-gradient\(.+\)$/). radial / conic / repeating-*
 *                   are detected via the generic /gradient/ substring probe
 *                   in us_is_gradient() and then rejected by
 *                   us_mcp_palette_check_color() with HTTP 400 — they would
 *                   render as broken CSS if saved.
 *
 * @return array<string, array<string, array{gradient: bool}>>
 */
function us_mcp_palette_field_spec() {
	static $spec = NULL;
	if ( $spec !== NULL ) {
		return $spec;
	}

	$spec = array(
		'header' => array(
			'color_header_middle_bg'              => array( 'gradient' => TRUE ),
			'color_header_middle_text'            => array( 'gradient' => FALSE ),
			'color_header_middle_text_hover'      => array( 'gradient' => FALSE ),
			'color_header_transparent_bg'         => array( 'gradient' => TRUE ),
			'color_header_transparent_text'       => array( 'gradient' => FALSE ),
			'color_header_transparent_text_hover' => array( 'gradient' => FALSE ),
			'color_chrome_toolbar'                => array( 'gradient' => FALSE ),
		),
		'alternate_header' => array(
			'color_header_top_bg'                     => array( 'gradient' => TRUE ),
			'color_header_top_text'                   => array( 'gradient' => FALSE ),
			'color_header_top_text_hover'             => array( 'gradient' => FALSE ),
			'color_header_top_transparent_bg'         => array( 'gradient' => TRUE ),
			'color_header_top_transparent_text'       => array( 'gradient' => FALSE ),
			'color_header_top_transparent_text_hover' => array( 'gradient' => FALSE ),
		),
		'content' => array(
			'color_content_bg'         => array( 'gradient' => TRUE ),
			'color_content_bg_alt'     => array( 'gradient' => TRUE ),
			'color_content_border'     => array( 'gradient' => FALSE ),
			'color_content_heading'    => array( 'gradient' => TRUE ),
			'color_content_text'       => array( 'gradient' => FALSE ),
			'color_content_link'       => array( 'gradient' => FALSE ),
			'color_content_link_hover' => array( 'gradient' => FALSE ),
			'color_content_primary'    => array( 'gradient' => TRUE ),
			'color_content_secondary'  => array( 'gradient' => TRUE ),
			'color_content_faded'      => array( 'gradient' => FALSE ),
			'color_content_overlay'    => array( 'gradient' => TRUE ),
		),
		'alternate_content' => array(
			'color_alt_content_bg'         => array( 'gradient' => TRUE ),
			'color_alt_content_bg_alt'     => array( 'gradient' => TRUE ),
			'color_alt_content_border'     => array( 'gradient' => FALSE ),
			'color_alt_content_heading'    => array( 'gradient' => TRUE ),
			'color_alt_content_text'       => array( 'gradient' => FALSE ),
			'color_alt_content_link'       => array( 'gradient' => FALSE ),
			'color_alt_content_link_hover' => array( 'gradient' => FALSE ),
			'color_alt_content_primary'    => array( 'gradient' => TRUE ),
			'color_alt_content_secondary'  => array( 'gradient' => TRUE ),
			'color_alt_content_faded'      => array( 'gradient' => FALSE ),
			'color_alt_content_overlay'    => array( 'gradient' => TRUE ),
		),
		'footer' => array(
			'color_footer_bg'         => array( 'gradient' => TRUE ),
			'color_footer_bg_alt'     => array( 'gradient' => TRUE ),
			'color_footer_border'     => array( 'gradient' => FALSE ),
			'color_footer_heading'    => array( 'gradient' => TRUE ),
			'color_footer_text'       => array( 'gradient' => FALSE ),
			'color_footer_link'       => array( 'gradient' => FALSE ),
			'color_footer_link_hover' => array( 'gradient' => FALSE ),
		),
		'alternate_footer' => array(
			'color_subfooter_bg'         => array( 'gradient' => TRUE ),
			'color_subfooter_bg_alt'     => array( 'gradient' => TRUE ),
			'color_subfooter_border'     => array( 'gradient' => FALSE ),
			'color_subfooter_heading'    => array( 'gradient' => TRUE ),
			'color_subfooter_text'       => array( 'gradient' => FALSE ),
			'color_subfooter_link'       => array( 'gradient' => FALSE ),
			'color_subfooter_link_hover' => array( 'gradient' => FALSE ),
		),
	);
	return $spec;
}

/**
 * The six picker sections (everything except `custom_colors`). Useful where
 * set-palette needs to iterate the partial-patch sections.
 *
 * @return string[]
 */
function us_mcp_palette_picker_sections() {
	return array( 'header', 'alternate_header', 'content', 'alternate_content', 'footer', 'alternate_footer' );
}

/**
 * All sections recognised by both abilities, in canonical (Theme Options
 * screen) order — the six picker sections plus the Custom Global Colors group.
 *
 * @return string[]
 */
function us_mcp_palette_sections() {
	$sections = us_mcp_palette_picker_sections();
	$sections[] = 'custom_colors';
	return $sections;
}

/**
 * Slugs of the predefined color fields, in the form a custom_colors entry
 * would collide with — i.e. the field key with the leading "color" prefix
 * stripped. Matches the reservation logic in config/theme-options.php:2487.
 *
 * @return string[]
 */
function us_mcp_palette_reserved_slugs() {
	static $slugs = NULL;
	if ( $slugs !== NULL ) {
		return $slugs;
	}
	$slugs = array();
	foreach ( us_mcp_palette_field_spec() as $section_keys ) {
		foreach ( array_keys( $section_keys ) as $key ) {
			if ( strpos( $key, 'color_' ) === 0 ) {
				$slugs[] = substr( $key, strlen( 'color' ) ); // "_header_middle_bg"
			}
		}
	}
	return $slugs;
}

/**
 * Mirror of the JS sanitize_color_slug filter (usof/js/_general.js:1230).
 * Forces a leading underscore, lowercases, replaces whitespace / hyphens
 * with underscores, strips characters outside `[a-z0-9_]`, and collapses
 * runs of underscores. Returns '' if nothing usable remains.
 *
 * @param string $slug
 * @return string
 */
function us_mcp_palette_sanitize_slug( $slug ) {
	$slug = (string) $slug;
	$slug = strtolower( $slug );
	$slug = preg_replace( '/[\s\-]+/', '_', $slug );
	$slug = preg_replace( '/[^a-z0-9_]+/', '', $slug );
	$slug = preg_replace( '/_+/', '_', $slug );
	if ( $slug === '' ) {
		return '';
	}
	if ( $slug[0] !== '_' ) {
		$slug = '_' . $slug;
	}
	// Trim trailing underscore (cosmetic, mirrors what the JS sanitizer
	// produces when the input was a single-word string). The leading "_"
	// added above survives — rtrim only touches the right side, so a second
	// prepend check here would be dead code.
	$slug = rtrim( $slug, '_' );
	if ( $slug === '' ) {
		return '';
	}
	return $slug;
}

/**
 * Validate a single color value submitted by the agent for one of the
 * predefined palette fields. Accepts the syntaxes that Theme Options stores
 * on disk and that `us_get_color()` (common/functions/helpers.php) knows how
 * to consume — which includes dynamic-value tokens that resolve to a Custom
 * Global Color slug or to another predefined palette field.
 *
 * Accepted:
 *   - empty string                        — clears the field (uses the
 *                                           current scheme's default)
 *   - "transparent"
 *   - "#rgb" / "#rgba" / "#rrggbb" / "#rrggbbaa"
 *   - "rgb(...)" / "rgba(...)"
 *   - "linear-gradient(...)" — gated by $allows_gradient. Only linear-gradient
 *     is accepted. radial-gradient / conic-gradient / repeating-*-gradient
 *     would slip past the runtime helper us_is_gradient() (substring check on
 *     "gradient"), so they reach the gradient branch here and are explicitly
 *     refused with us_mcp_palette_unsupported_gradient — the upstream color
 *     picker (usof/js/field_color.js:239) recognises only linear-gradient and
 *     would render the saved value as broken CSS.
 *   - "_<slug>" — dynamic reference. Must resolve at write time to either:
 *       (a) the `slug` of a current Custom Global Colors entry, or
 *       (b) a predefined palette field key (e.g. "_content_primary" maps to
 *           `color_content_primary`). Self-references to the field being
 *           written are rejected to avoid an empty-resolve cycle at render
 *           time.
 *
 * Rejected:
 *   - bare `var(--…)` references (palette values are absolute or token-typed,
 *     not CSS-variable text — the CSS generator emits the var()'s itself)
 *   - named colors ("red", "white", …)
 *   - any other free-form text
 *
 * Returns the normalised value (trimmed) or a WP_Error.
 *
 * @param mixed  $value
 * @param bool   $allows_gradient
 * @param string $field_label  Used in error messages.
 * @param string $current_key  Field key being written, for self-reference detection (optional).
 * @param array|null $custom_colors_lookup  Optional list of {color, name, slug} entries
 *        to resolve `_slug` tokens against — pass the in-flight snapshot (e.g. when
 *        validating a per-field patch in the same call that also rewrites
 *        custom_colors, or a preview that adds a slug and uses it in one shot).
 *        NULL falls back to the live Theme Options via us_get_custom_global_colors().
 * @return string|WP_Error
 */
function us_mcp_palette_check_color( $value, $allows_gradient, $field_label, $current_key = '', $custom_colors_lookup = NULL ) {
	if ( $value === NULL OR $value === '' ) {
		return '';
	}
	if ( ! is_string( $value ) ) {
		return new WP_Error(
			'us_mcp_palette_bad_color_type',
			sprintf( 'Field "%s" must be a color string (or null / "" to clear).', $field_label ),
			array( 'status' => 400 )
		);
	}

	$v = trim( $value );
	if ( $v === '' ) {
		return '';
	}
	if ( strcasecmp( $v, 'transparent' ) === 0 ) {
		return 'transparent';
	}

	// Palette token (_slug) — resolved by us_get_color() at render time
	// against Custom Global Colors first, then predefined palette fields.
	if ( $v[0] === '_' ) {
		if ( ! preg_match( '/^_[a-z0-9_]+$/', $v ) ) {
			return new WP_Error(
				'us_mcp_palette_bad_token',
				sprintf( 'Value "%s" for "%s" looks like a dynamic-value token but contains characters outside [a-z0-9_].', $v, $field_label ),
				array( 'status' => 400 )
			);
		}
		// Reject self-reference: writing "_content_bg" into color_content_bg
		// resolves to empty at render time (us_get_color falls through), which
		// is almost certainly not what the caller intended.
		if ( $current_key !== '' AND $current_key === ( 'color' . $v ) ) {
			return new WP_Error(
				'us_mcp_palette_self_reference',
				sprintf( 'Field "%s" cannot reference itself via token "%s".', $field_label, $v ),
				array( 'status' => 400 )
			);
		}
		// Must resolve to either a Custom Global Color slug or another
		// predefined palette field. When the caller passed an in-flight
		// snapshot (the patched custom_colors list for THIS call), use it —
		// otherwise tokens to slugs added in the same call would fail
		// validation because us_get_custom_global_colors() only sees the live
		// $usof_options. NULL lookup keeps the legacy live-lookup behaviour for
		// callers that don't carry a snapshot (e.g. button-styles).
		if ( is_array( $custom_colors_lookup ) ) {
			$resolves_to_custom = FALSE;
			foreach ( $custom_colors_lookup as $entry ) {
				if ( is_array( $entry ) AND isset( $entry['slug'] ) AND $entry['slug'] === $v ) {
					$resolves_to_custom = TRUE;
					break;
				}
			}
		} else {
			$resolves_to_custom = function_exists( 'us_get_custom_global_colors' )
				? ( us_get_custom_global_colors( $v ) AND us_get_custom_global_colors( $v ) !== $v )
				: FALSE;
		}
		$resolves_to_predefined = FALSE;
		foreach ( us_mcp_palette_field_spec() as $section_keys ) {
			if ( isset( $section_keys[ 'color' . $v ] ) ) {
				$resolves_to_predefined = TRUE;
				break;
			}
		}
		if ( ! $resolves_to_custom AND ! $resolves_to_predefined ) {
			return new WP_Error(
				'us_mcp_palette_unresolved_token',
				sprintf( 'Token "%s" on "%s" matches no Custom Global Colors slug and no predefined palette field — add the slug first (set-palette custom_colors=[…]) or pick an existing one.', $v, $field_label ),
				array( 'status' => 400 )
			);
		}
		return $v;
	}

	$is_gradient = function_exists( 'us_is_gradient' )
		? (bool) us_is_gradient( $v )
		: (bool) preg_match( '/-gradient\s*\(/i', $v );

	if ( $is_gradient ) {
		if ( ! $allows_gradient ) {
			return new WP_Error(
				'us_mcp_palette_no_gradient',
				sprintf( 'Field "%s" does not accept a gradient value (only solid colors).', $field_label ),
				array( 'status' => 400 )
			);
		}
		// Only linear-gradient is actually supported. The upstream color picker
		// (usof/js/field_color.js:239) detects gradients via the hardcoded regex
		// /^linear-gradient\(.+\)$/, and the runtime helper us_is_gradient()
		// (functions/helpers.php:304) merely checks for the substring "gradient",
		// so radial / conic / repeating-* values would be accepted by the helper,
		// saved to the options row, classified as "gradient" by every runtime
		// template that calls us_is_gradient(), and then rendered as broken CSS
		// because the picker can't serialise them and the generated
		// --color-<slug> / --color-<slug>-grad variables come out unusable.
		// Reject them at the API boundary so set-palette can't silently corrupt
		// the palette.
		if ( ! preg_match( '/^linear-gradient\(.+\)$/i', $v ) ) {
			return new WP_Error(
				'us_mcp_palette_unsupported_gradient',
				sprintf( 'Value "%s" for "%s" uses an unsupported gradient type. Only linear-gradient(...) is supported — radial-gradient, conic-gradient, and any repeating-*-gradient form are not recognised by the color picker (usof/js/field_color.js:239) and would render as broken CSS. Convert the design to a linear-gradient(...) before writing.', $v, $field_label ),
				array( 'status' => 400 )
			);
		}
		return $v;
	}

	if ( preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $v ) ) {
		return $v;
	}
	if ( preg_match( '/^rgba?\(\s*[\d.,%\s\/]+\s*\)$/i', $v ) ) {
		return $v;
	}
	return new WP_Error(
		'us_mcp_palette_bad_color',
		sprintf( 'Value "%s" for "%s" is not a recognised color. Use a hex (#rrggbb / #rrggbbaa), rgb()/rgba(), "transparent", a linear-gradient(...) (on fields that accept gradients), or a palette token "_slug" referring to a Custom Global Color or another predefined palette field. Note: hsl()/hsla() are not supported — the color picker (usof/js/field_color.js) cannot parse them; convert to hex or rgb()/rgba() before writing.', $v, $field_label ),
		array( 'status' => 400 )
	);
}

/**
 * Build the read response for one palette section (header / content / footer).
 * Empty / missing values come back as NULL so the agent can tell "key never
 * set" from "key set to empty string" (us_get_option normalises both, but the
 * intent is the same — the field shows the scheme default).
 *
 * @param string $section
 * @return array<string, string|null>
 */
function us_mcp_palette_read_section( $section ) {
	$spec = us_mcp_palette_field_spec();
	if ( ! isset( $spec[ $section ] ) ) {
		return array();
	}
	$out = array();
	foreach ( array_keys( $spec[ $section ] ) as $key ) {
		$value = us_get_option( $key, '' );
		$out[ $key ] = ( $value === '' OR $value === NULL ) ? NULL : (string) $value;
	}
	return $out;
}

/**
 * Read the Custom Global Colors group as a list of {color, name, slug}
 * entries (the on-disk shape). Empty / malformed entries are dropped so
 * the agent always sees what the CSS generator will actually consume —
 * see us_get_custom_global_colors() in common/functions/helpers.php.
 *
 * @return array<int, array{color: string, name: string, slug: string}>
 */
function us_mcp_palette_read_custom_colors() {
	global $usof_options;
	usof_load_options_once();
	return us_mcp_palette_read_custom_colors_from( is_array( $usof_options ) ? $usof_options : array() );
}

/**
 * Validate and normalise the list of Custom Global Colors entries supplied by
 * the agent. Sanitises slugs to match the JS-side filter, rejects collisions
 * with the predefined palette slugs, and rejects duplicate slugs within the
 * list. Returns the normalised list or a WP_Error.
 *
 * @param mixed $entries
 * @return array<int, array{color: string, name: string, slug: string}>|WP_Error
 */
function us_mcp_palette_validate_custom_colors( $entries ) {
	if ( $entries === NULL OR ( is_array( $entries ) AND empty( $entries ) ) ) {
		return array();
	}
	if ( ! is_array( $entries ) ) {
		return new WP_Error(
			'us_mcp_palette_custom_not_array',
			'custom_colors must be an array of {color, name, slug} objects (pass [] to clear).',
			array( 'status' => 400 )
		);
	}

	$reserved = us_mcp_palette_reserved_slugs();
	$seen_slugs = array();
	$normalised = array();
	foreach ( $entries as $index => $entry ) {
		if ( ! is_array( $entry ) ) {
			return new WP_Error(
				'us_mcp_palette_custom_entry_not_object',
				sprintf( 'custom_colors[%d] must be an object with color / name / slug.', $index ),
				array( 'status' => 400 )
			);
		}
		$color_raw = isset( $entry['color'] ) ? $entry['color'] : '';
		$name_raw  = isset( $entry['name'] )  ? (string) $entry['name']  : '';
		$slug_raw  = isset( $entry['slug'] )  ? (string) $entry['slug']  : '';

		if ( trim( $name_raw ) === '' ) {
			return new WP_Error(
				'us_mcp_palette_custom_missing_name',
				sprintf( 'custom_colors[%d]: `name` is required.', $index ),
				array( 'status' => 400 )
			);
		}
		if ( trim( $slug_raw ) === '' ) {
			return new WP_Error(
				'us_mcp_palette_custom_missing_slug',
				sprintf( 'custom_colors[%d]: `slug` is required.', $index ),
				array( 'status' => 400 )
			);
		}

		$color = us_mcp_palette_check_color( $color_raw, /* gradient */ TRUE, sprintf( 'custom_colors[%d].color', $index ) );
		if ( is_wp_error( $color ) ) {
			return $color;
		}
		if ( $color === '' ) {
			return new WP_Error(
				'us_mcp_palette_custom_missing_color',
				sprintf( 'custom_colors[%d]: `color` is required.', $index ),
				array( 'status' => 400 )
			);
		}

		$slug = us_mcp_palette_sanitize_slug( $slug_raw );
		if ( $slug === '' ) {
			return new WP_Error(
				'us_mcp_palette_custom_bad_slug',
				sprintf( 'custom_colors[%d]: `slug` "%s" reduces to empty after sanitisation (allowed: a-z 0-9 _).', $index, $slug_raw ),
				array( 'status' => 400 )
			);
		}
		if ( in_array( $slug, $reserved, TRUE ) ) {
			return new WP_Error(
				'us_mcp_palette_custom_reserved_slug',
				sprintf( 'custom_colors[%d]: slug "%s" collides with a predefined palette field — pick another.', $index, $slug ),
				array( 'status' => 400 )
			);
		}
		if ( isset( $seen_slugs[ $slug ] ) ) {
			return new WP_Error(
				'us_mcp_palette_custom_duplicate_slug',
				sprintf( 'custom_colors[%d]: slug "%s" duplicates an earlier entry — slugs must be unique within the list.', $index, $slug ),
				array( 'status' => 400 )
			);
		}
		$seen_slugs[ $slug ] = TRUE;

		$normalised[] = array(
			'color' => $color,
			'name'  => trim( $name_raw ),
			'slug'  => $slug,
		);
	}
	return $normalised;
}

add_action( 'wp_abilities_api_init', function () {
	$sections_enum = us_mcp_palette_sections();
	$spec = us_mcp_palette_field_spec();

	// Build the per-section schema fragments programmatically so adding a new
	// picker section to us_mcp_palette_field_spec() automatically extends both
	// abilities — no extra wiring needed.
	$nullable_string_map = array( 'type' => 'object', 'additionalProperties' => array( 'type' => array( 'string', 'null' ) ) );
	$picker_get_output   = array();
	$picker_set_input    = array();
	$picker_set_applied  = array();
	foreach ( us_mcp_palette_picker_sections() as $section ) {
		$picker_get_output[ $section ]  = $nullable_string_map;
		$picker_set_input[ $section ]   = array( 'type' => 'object', 'description' => sprintf( 'Partial patch over the "%s" color pickers.', $section ), 'additionalProperties' => array( 'type' => array( 'string', 'null' ) ) );
		$picker_set_applied[ $section ] = array( 'type' => 'array', 'items' => array( 'type' => 'string' ) );
	}

	wp_register_ability( 'upsolution/get-palette', array(
		'label'               => 'Read the site\'s color palette (six picker sections + Custom Global Colors)',
		'description'         => 'Return the current values of every color picker under Theme Options → Colors for the requested sections. Six picker sections mirror the admin UI screen order: "header" (' . count( $spec['header'] ) . ' fields — active header: middle area, transparent state, chrome toolbar; site chrome), "alternate_header" (' . count( $spec['alternate_header'] ) . ' fields — top bar above the header, color_header_top_*; site chrome). The next four are row palettes selected via the per-row color_scheme attribute: "content" (' . count( $spec['content'] ) . ' fields — color_content_*, the default applied to rows with NO color_scheme attribute), "alternate_content" (' . count( $spec['alternate_content'] ) . ' fields — color_alt_content_*, applied to rows with color_scheme="alternate"), "footer" (' . count( $spec['footer'] ) . ' fields — color_footer_*, applied to rows with color_scheme="footer-bottom"; NOT used by the page footer automatically), "alternate_footer" (' . count( $spec['alternate_footer'] ) . ' fields — color_subfooter_*, applied to rows with color_scheme="footer-top"). The seventh section, "custom_colors", is the Custom Global Colors group — a list of {color, name, slug} entries that become CSS variables --color<slug> (a list, not a picker map). Pass `sections=[]` or omit to read all seven (6 picker sections + custom_colors). Empty / unset color picker values come back as null (the field is showing the active scheme default).',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'       => 'object',
			'properties' => array(
				'sections' => array(
					'type'        => 'array',
					'description' => 'Subset of sections to return. Omit or pass an empty array for all seven (6 picker sections + custom_colors). Unknown names are ignored.',
					'items'       => array( 'type' => 'string', 'enum' => $sections_enum ),
				),
			),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'description' => 'One key per requested section. Picker sections are flat maps of palette-key → value (nullable). custom_colors is an ordered list.',
			'properties' => $picker_get_output + array(
				'custom_colors' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'required'   => array( 'color', 'name', 'slug' ),
						'properties' => array(
							'color' => array( 'type' => 'string', 'description' => 'CSS color or gradient.' ),
							'name'  => array( 'type' => 'string', 'description' => 'Human-readable label (admin UI only).' ),
							'slug'  => array( 'type' => 'string', 'description' => 'Sanitised slug — becomes the CSS variable --color<slug> (with underscores swapped for hyphens).' ),
						),
					),
				),
			),
		),
		'execute_callback'    => 'us_mcp_ability_get_palette',
		'permission_callback' => 'us_mcp_theme_option_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );

	wp_register_ability( 'upsolution/set-palette', array(
		'label'               => 'Patch the site\'s color palette and / or Custom Global Colors',
		'description'         => 'Update any subset of the color pickers under Theme Options → Colors and / or replace the Custom Global Colors list. Six picker sections (header / alternate_header / content / alternate_content / footer / alternate_footer), each accepting a partial patch over its own keys. MANDATORY pre-read: the `design/color-palette` doc (upsolution-read-doc) — it carries the full key map per section and the per-key gradient policy.' . "\n"
			. 'Per-field semantics: pass any subset of a section\'s keys — unlisted keys are left untouched (merge). Pass null or "" to clear a field (it falls back to the active color scheme). Accepted value syntax: hex (#rgb / #rrggbb / #rrggbbaa), rgb()/rgba(), "transparent", linear-gradient(...) only on gradient-capable keys (per-key policy in design/color-palette; radial / conic / repeating-* gradients are NOT supported anywhere — the color picker only recognises linear-gradient), and palette tokens "_<slug>" referring either to a Custom Global Colors entry (e.g. "_brand") or to another predefined palette field (e.g. "_content_primary"). hsl()/hsla() are NOT supported — use hex or rgb()/rgba(). Palette tokens resolve at CSS-generation time into var(--color-…) — changing the source slug repaints everything that referenced it. Tokens must match an existing slug at write time; self-references and bare var(--…) text are rejected.' . "\n"
			. 'custom_colors: full-array replace (not a patch). Pass the desired final list — entries are {color, name, slug}; slugs are sanitised (lowercased, [a-z0-9_], forced leading "_") and must be unique, must not collide with the predefined palette slugs, and entries with empty color / name / slug are rejected. Pass [] to clear all custom global colors. Send only `custom_colors` to keep the predefined pickers untouched.' . "\n"
			. 'Saving regenerates the site\'s CSS asset files — changes take effect on the next page load.',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'                 => 'object',
			'additionalProperties' => FALSE,
			'properties'           => $picker_set_input + array(
				'custom_colors' => array(
					'type'        => 'array',
					'description' => 'Full replacement of the Custom Global Colors list. Pass [] to clear.',
					'items'       => array(
						'type'       => 'object',
						'required'   => array( 'color', 'name', 'slug' ),
						'properties' => array(
							'color' => array( 'type' => 'string' ),
							'name'  => array( 'type' => 'string' ),
							'slug'  => array( 'type' => 'string' ),
						),
					),
				),
			),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'required'   => array( 'applied', 'before', 'after', 'regenerated_assets' ),
			'properties' => array(
				'applied' => array(
					'type'        => 'object',
					'description' => 'Per-section list of field names whose stored value actually changed (no-op writes are dropped). For custom_colors, a boolean — TRUE if the list was replaced with a different value.',
					'properties'  => $picker_set_applied + array(
						'custom_colors' => array( 'type' => 'boolean' ),
					),
				),
				'before'             => array( 'type' => 'object', 'description' => 'Pre-write snapshot of the sections you sent (same shape as upsolution-get-palette).' ),
				'after'              => array( 'type' => 'object', 'description' => 'Post-write snapshot of the sections you sent.' ),
				'regenerated_assets' => array( 'type' => 'boolean', 'description' => 'TRUE once usof_save_options has run and the usof_after_save hook chain has fired.' ),
			),
		),
		'execute_callback'    => 'us_mcp_ability_set_palette',
		'permission_callback' => 'us_mcp_theme_option_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );
} );

/**
 * @param array $input
 * @return array
 */
function us_mcp_ability_get_palette( $input ) {
	$input = (array) $input;
	// Parentheses + `&&` — `=` binds tighter than `AND`, so without them
	// $requested silently becomes a boolean and the foreach below iterates
	// nothing (or warns, depending on PHP version).
	$requested = ( isset( $input['sections'] ) && is_array( $input['sections'] ) && ! empty( $input['sections'] ) )
		? array_values( array_intersect( us_mcp_palette_sections(), $input['sections'] ) )
		: us_mcp_palette_sections();

	$out = array();
	foreach ( $requested as $section ) {
		if ( $section === 'custom_colors' ) {
			$out['custom_colors'] = us_mcp_palette_read_custom_colors();
		} else {
			$out[ $section ] = us_mcp_palette_read_section( $section );
		}
	}
	return $out;
}

/**
 * Validate the agent's palette input AND apply the result to the supplied
 * options array. No DB write — the caller decides whether to persist via
 * usof_save_options (the set-palette tool) or keep the result in-memory (the
 * create-preview tool).
 *
 * On a bad input the options array is left untouched.
 *
 * @param array $input    Agent-supplied palette patch (same shape as the
 *                        upsolution-set-palette input).
 * @param array $options  Reference to the options array to mutate.
 * @return array{applied: array, before: array, after: array}|WP_Error
 */
function us_mcp_palette_apply_to_options( $input, array &$options ) {
	$input = (array) $input;
	$spec = us_mcp_palette_field_spec();

	$has_field_patch = FALSE;
	foreach ( us_mcp_palette_picker_sections() as $section ) {
		if ( array_key_exists( $section, $input ) AND is_array( $input[ $section ] ) AND ! empty( $input[ $section ] ) ) {
			$has_field_patch = TRUE;
			break;
		}
	}
	$has_custom = array_key_exists( 'custom_colors', $input );
	if ( ! $has_field_patch AND ! $has_custom ) {
		return new WP_Error(
			'us_mcp_palette_no_op',
			'Nothing to update — pass at least one of header / alternate_header / content / alternate_content / footer / alternate_footer (with field overrides) or custom_colors.',
			array( 'status' => 400 )
		);
	}

	// Validate `custom_colors` FIRST when supplied, so per-field token
	// resolution (below) can see slugs being added in the same call. Without
	// this, a one-shot call that adds a slug AND references it elsewhere would
	// fail validation — us_mcp_palette_check_color()'s live-options lookup
	// doesn't know about the in-flight snapshot.
	$normalised_custom = NULL;
	if ( $has_custom ) {
		$normalised_custom = us_mcp_palette_validate_custom_colors( $input['custom_colors'] );
		if ( is_wp_error( $normalised_custom ) ) {
			return $normalised_custom;
		}
	}

	// Effective custom_colors snapshot for `_slug` token resolution in the
	// per-field validation loop. If this call rewrites custom_colors, use the
	// new list (it's about to land in $options). Otherwise use whatever's
	// currently in $options — which is the live list for set-palette, or the
	// preview snapshot for create-preview.
	$effective_custom = ( $normalised_custom !== NULL )
		? $normalised_custom
		: us_mcp_palette_read_custom_colors_from( $options );

	// Pre-validate every section BEFORE touching $options. A single bad
	// field aborts the whole call — partial application would be surprising
	// and hard to roll back.
	$normalised_fields = array(); // section => key => normalised value
	foreach ( us_mcp_palette_picker_sections() as $section ) {
		if ( ! array_key_exists( $section, $input ) OR ! is_array( $input[ $section ] ) ) {
			continue;
		}
		foreach ( $input[ $section ] as $key => $value ) {
			if ( ! isset( $spec[ $section ][ $key ] ) ) {
				return new WP_Error(
					'us_mcp_palette_unknown_key',
					sprintf( 'Unknown palette key "%s" in section "%s". Allowed keys: %s.', $key, $section, implode( ', ', array_keys( $spec[ $section ] ) ) ),
					array( 'status' => 400 )
				);
			}
			$allows_gradient = (bool) $spec[ $section ][ $key ]['gradient'];
			$normalised = us_mcp_palette_check_color( $value, $allows_gradient, $key, $key, $effective_custom );
			if ( is_wp_error( $normalised ) ) {
				return $normalised;
			}
			$normalised_fields[ $section ][ $key ] = $normalised;
		}
	}

	$applied = array();
	$before  = array();
	$after   = array();

	foreach ( $normalised_fields as $section => $key_values ) {
		$applied[ $section ] = array();
		$before[ $section ]  = array();
		$after[ $section ]   = array();
		foreach ( $key_values as $key => $new_value ) {
			$prev_value = isset( $options[ $key ] ) ? (string) $options[ $key ] : '';
			$before[ $section ][ $key ] = ( $prev_value === '' ) ? NULL : $prev_value;
			if ( $prev_value === $new_value ) {
				$after[ $section ][ $key ] = $before[ $section ][ $key ];
				continue;
			}
			$options[ $key ] = $new_value;
			$applied[ $section ][] = $key;
			$after[ $section ][ $key ] = ( $new_value === '' ) ? NULL : $new_value;
		}
	}

	if ( $normalised_custom !== NULL ) {
		$prev_custom = us_mcp_palette_read_custom_colors_from( $options );
		$options['custom_colors'] = $normalised_custom;
		$applied['custom_colors'] = ( $prev_custom !== $normalised_custom );
		$before['custom_colors'] = $prev_custom;
		$after['custom_colors']  = $normalised_custom;
	}

	return array(
		'applied' => $applied,
		'before'  => $before,
		'after'   => $after,
	);
}

/**
 * Same shape as us_mcp_palette_read_custom_colors(), but reads from an
 * arbitrary options array (so we can introspect a preview snapshot without
 * touching the live $usof_options global).
 *
 * @param array $options
 * @return array<int, array{color: string, name: string, slug: string}>
 */
function us_mcp_palette_read_custom_colors_from( array $options ) {
	$raw = isset( $options['custom_colors'] ) ? $options['custom_colors'] : array();
	if ( ! is_array( $raw ) ) {
		return array();
	}
	$out = array();
	foreach ( $raw as $entry ) {
		if ( ! is_array( $entry ) ) {
			continue;
		}
		$out[] = array(
			'color' => isset( $entry['color'] ) ? (string) $entry['color'] : '',
			'name'  => isset( $entry['name'] )  ? (string) $entry['name']  : '',
			'slug'  => isset( $entry['slug'] )  ? (string) $entry['slug']  : '',
		);
	}
	return $out;
}

/**
 * @param array $input
 * @return array|WP_Error
 */
function us_mcp_ability_set_palette( $input ) {
	if ( ! function_exists( 'usof_save_options' ) OR ! function_exists( 'usof_load_options_once' ) ) {
		return new WP_Error(
			'us_mcp_palette_core_not_loaded',
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

	$result = us_mcp_palette_apply_to_options( $input, $updated_options );
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

