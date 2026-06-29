<?php
/**
 * UpSolution MCP — Theme Options typography editor.
 *
 * Two abilities for inspecting and changing the site-wide typography settings
 * (Theme Options → Typography):
 *
 *   upsolution-list-fonts      — enumerate font-family values acceptable for
 *                                set-typography (google + adobe + web-safe +
 *                                uploaded fonts configured on this install).
 *   upsolution-set-typography  — patch one tag (body / h1 .. h6) in
 *                                usof_options. After the save, CSS assets are
 *                                regenerated automatically through the
 *                                usof_after_save → us_generate_asset_files
 *                                hook chain.
 *
 * The seven tag keys in usof_options (`body`, `h1` .. `h6`) match
 * `US_TYPOGRAPHY_TAGS`. Each tag is an associative array; field set is
 * documented in config/theme-options/typography.php. A responsive-capable
 * field (font-size, line-height, font-weight, bold-font-weight, font-stretch,
 * text-transform, font-style, letter-spacing, margin-bottom) can be stored
 * either as a plain scalar string OR as `rawurlencode( json_encode( {default,
 * laptops, tablets, mobiles} ) )` — the same wire format the Theme Options
 * admin UI uses.
 *
 * Reading the current values uses the existing upsolution-get-theme-option
 * tool (name = "body" / "h1" / .. / "h6").
 *
 * @package usCore\Mcp
 */

defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

// Permission gate is the shared us_mcp_theme_option_permission_callback()
// from abilities/theme-option.php — edit_theme_options across every ability
// that touches Theme Options (typography, palette, buttons, preview).

/**
 * Allowed text-transform values.
 *
 * @return string[]
 */
function us_mcp_typography_text_transform_enum() {
	return array( 'none', 'uppercase', 'lowercase', 'capitalize' );
}

/**
 * Allowed font-style values.
 *
 * @return string[]
 */
function us_mcp_typography_font_style_enum() {
	return array( 'normal', 'italic' );
}

/**
 * Responsive breakpoint keys, in the same order us-core uses internally
 * (see us_get_responsive_states()).
 *
 * @return string[]
 */
function us_mcp_typography_responsive_keys() {
	return array( 'default', 'laptops', 'tablets', 'mobiles' );
}

/**
 * Per-tag field schema. Each entry:
 *   responsive bool  — accepts {default,laptops,tablets,mobiles} object
 *   tags       string[] — which tags this field is valid on
 *   enum       string[]|null — enum of acceptable raw values (besides
 *                              var(--h1-…) inheritance and `inherit`)
 *
 * Field set mirrors plugins/us-core/config/theme-options/typography.php.
 *
 * `font-weight` / `bold-font-weight` carry no enum on purpose: Variable Fonts
 * accept intermediate values inside their wght axis (350, 425 …), so these
 * two are integer-checked (whole numbers 1..1000) in
 * us_mcp_typography_check_scalar() instead. `font-stretch` is a percentage tied to the wdth axis of Variable
 * Fonts and is free-form like font-size.
 *
 * Heading-only fields handled here: `margin-bottom`. The `color` /
 * `color_override` keys also live on h1..h6 in usof_options but they
 * belong to the colour palette, not typography — they are owned by
 * upsolution-set-palette and are intentionally NOT writable from here.
 *
 * @return array<string, array{responsive: bool, tags: string[], enum: ?array}>
 */
function us_mcp_typography_field_spec() {
	$body_and_headings = array( 'body', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
	$headings_only     = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );

	return array(
		'font-family'      => array( 'responsive' => FALSE, 'tags' => $body_and_headings, 'enum' => NULL ),
		'font-size'        => array( 'responsive' => TRUE,  'tags' => $body_and_headings, 'enum' => NULL ),
		'line-height'      => array( 'responsive' => TRUE,  'tags' => $body_and_headings, 'enum' => NULL ),
		'font-weight'      => array( 'responsive' => TRUE,  'tags' => $body_and_headings, 'enum' => NULL ),
		'bold-font-weight' => array( 'responsive' => TRUE,  'tags' => $body_and_headings, 'enum' => NULL ),
		'font-stretch'     => array( 'responsive' => TRUE,  'tags' => $body_and_headings, 'enum' => NULL ),
		'text-transform'   => array( 'responsive' => TRUE,  'tags' => $body_and_headings, 'enum' => us_mcp_typography_text_transform_enum() ),
		'font-style'       => array( 'responsive' => TRUE,  'tags' => $body_and_headings, 'enum' => us_mcp_typography_font_style_enum() ),
		'letter-spacing'   => array( 'responsive' => TRUE,  'tags' => $body_and_headings, 'enum' => NULL ),
		'margin-bottom'    => array( 'responsive' => TRUE,  'tags' => $headings_only,     'enum' => NULL ),
	);
}

/**
 * Collect every font-family value that's valid for this site: google fonts
 * config, configured Adobe Fonts, web-safe stacks, uploaded fonts from Theme
 * Options. Used both by upsolution-list-fonts (discovery) and by
 * upsolution-set-typography (validation).
 *
 * @return array{google: string[], adobe: string[], web_safe: string[], uploaded: string[]}
 */
function us_mcp_typography_collect_fonts() {
	$google_cfg = us_config( 'google-fonts' );
	$google     = is_array( $google_cfg ) ? array_keys( $google_cfg ) : array();

	$adobe = array();
	if ( function_exists( 'us_get_adobe_fonts' ) ) {
		foreach ( (array) us_get_adobe_fonts() as $slug => $_label ) {
			$adobe[] = (string) $slug;
		}
	}

	$web_safe = array();
	foreach ( (array) us_config( 'web-safe-fonts' ) as $stack ) {
		$web_safe[] = (string) $stack;
	}

	$uploaded = array();
	$uploaded_fonts = us_get_option( 'uploaded_fonts', array() );
	if ( is_array( $uploaded_fonts ) ) {
		foreach ( $uploaded_fonts as $entry ) {
			if ( ! empty( $entry['name'] ) AND ! empty( $entry['files'] ) ) {
				$uploaded[] = function_exists( 'us_sanitize_font_family' )
					? us_sanitize_font_family( $entry['name'] )
					: (string) $entry['name'];
			}
		}
	}

	return array(
		'google'   => $google,
		'adobe'    => $adobe,
		'web_safe' => $web_safe,
		'uploaded' => $uploaded,
	);
}

/**
 * upsolution-list-fonts execute callback — the four font-family groups from
 * us_mcp_typography_collect_fonts() plus per-font weight capabilities:
 *
 *   variable        — font-family => axis ranges. `wght` bounds the values
 *                     acceptable as font-weight / bold-font-weight (any whole
 *                     number inside the range, intermediate ones included);
 *                     `wdth` bounds font-stretch, in percent.
 *   static_weights  — font-family => the numeric weights the font actually
 *                     ships. Other weights get synthesized by the browser.
 *
 * Families missing from both maps (Adobe Fonts, web-safe stacks) have unknown
 * weight sets.
 *
 * @return array
 */
function us_mcp_typography_list_fonts() {
	$result = us_mcp_typography_collect_fonts();

	$variable = array();
	$static_weights = array();

	// Google fonts: 'axes' ranges for Variable Fonts, numeric 'variants' for
	// static ones. Only the wght / wdth axes matter here — other axes (opsz,
	// GRAD, …) are not editable through set-typography.
	$google_cfg = us_config( 'google-fonts' );
	foreach ( ( is_array( $google_cfg ) ? $google_cfg : array() ) as $font_family => $font_options ) {
		$axes = array();
		foreach ( array( 'wght', 'wdth' ) as $axis ) {
			if ( isset( $font_options['axes'][ $axis ]['min'], $font_options['axes'][ $axis ]['max'] ) ) {
				// Font weights are whole numbers; the wdth percent axis may be fractional
				if ( $axis === 'wght' ) {
					$axes[ $axis ] = array(
						'min' => (int) $font_options['axes'][ $axis ]['min'],
						'max' => (int) $font_options['axes'][ $axis ]['max'],
					);
				} else {
					$axes[ $axis ] = array(
						'min' => (float) $font_options['axes'][ $axis ]['min'],
						'max' => (float) $font_options['axes'][ $axis ]['max'],
					);
				}
			}
		}
		if ( ! empty( $axes ) ) {
			$variable[ $font_family ] = $axes;
		}
		// The wght axis supersedes discrete variants; without it the variants
		// list is the source of truth for available weights.
		if ( isset( $axes['wght'] ) ) {
			continue;
		}
		$weights = array();
		$variants = isset( $font_options['variants'] ) ? (array) $font_options['variants'] : array();
		foreach ( $variants as $variant ) {
			$weight = (int) $variant; // "400italic" → 400
			if ( $weight AND ! in_array( $weight, $weights, TRUE ) ) {
				$weights[] = $weight;
			}
		}
		if ( ! empty( $weights ) ) {
			sort( $weights );
			$static_weights[ $font_family ] = $weights;
		}
	}

	// Uploaded fonts: axes ranges when "Variable Font" is checked in Theme
	// Options, a single configured weight otherwise.
	if ( function_exists( 'us_get_uploaded_fonts_data' ) ) {
		foreach ( (array) us_get_uploaded_fonts_data() as $font_name => $font_data ) {
			if ( ! empty( $font_data['axes'] ) ) {
				$axes = $font_data['axes'];
				// Core casts axis bounds to float — font weights are whole numbers
				if ( isset( $axes['wght']['min'], $axes['wght']['max'] ) ) {
					$axes['wght'] = array(
						'min' => (int) $axes['wght']['min'],
						'max' => (int) $axes['wght']['max'],
					);
				}
				$variable[ $font_name ] = $axes;
			} elseif ( ! empty( $font_data['weight'] ) ) {
				$static_weights[ $font_name ] = array( (int) $font_data['weight'] );
			}
		}
	}

	// Cast to objects so empty maps serialize as {} rather than [].
	$result['variable'] = (object) $variable;
	$result['static_weights'] = (object) $static_weights;

	return $result;
}

/**
 * Decode a stored typography field value into a normalised, agent-facing shape.
 * A plain scalar is returned as-is; a URL-encoded JSON object becomes an
 * associative array of breakpoint values. Anything else is returned verbatim.
 *
 * @param mixed $value
 * @return mixed
 */
function us_mcp_typography_decode_value( $value ) {
	if ( ! is_string( $value ) ) {
		return $value;
	}
	// Heuristic — only attempt JSON decode if the URL-encoded payload looks
	// like an object. Otherwise plain strings like "16px" pass through.
	$trimmed = trim( $value );
	if ( $trimmed === '' OR ( $trimmed[0] !== '{' AND substr( rawurldecode( $trimmed ), 0, 1 ) !== '{' ) ) {
		return $value;
	}
	$decoded = json_decode( rawurldecode( $trimmed ), /* assoc */ TRUE );
	if ( ! is_array( $decoded ) ) {
		return $value;
	}
	$out = array();
	foreach ( us_mcp_typography_responsive_keys() as $key ) {
		if ( array_key_exists( $key, $decoded ) ) {
			$out[ $key ] = $decoded[ $key ];
		}
	}
	return $out;
}

/**
 * Decode every field in a tag dict for output (e.g. `before`/`after` blocks).
 *
 * @param array $tag_dict
 * @return array
 */
function us_mcp_typography_decode_tag( $tag_dict ) {
	if ( ! is_array( $tag_dict ) ) {
		return array();
	}
	$out = array();
	foreach ( $tag_dict as $name => $value ) {
		$out[ $name ] = us_mcp_typography_decode_value( $value );
	}
	return $out;
}

/**
 * Normalise a single field value submitted by the agent into the on-disk
 * format (plain string OR URL-encoded JSON). Performs enum / type checks.
 *
 * @param string $tag      Typography tag (`body`, `h1` .. `h6`).
 * @param string $field    Field name (e.g. `font-size`).
 * @param mixed  $value    Submitted value (string|array|null).
 * @param array  $fonts    Result of us_mcp_typography_collect_fonts() — passed
 *                         in so we don't recompute it per field.
 * @return string|null|WP_Error  Normalised value, NULL to delete, or WP_Error.
 */
function us_mcp_typography_normalise_field( $tag, $field, $value, $fonts ) {
	$spec_map = us_mcp_typography_field_spec();
	if ( ! isset( $spec_map[ $field ] ) ) {
		return new WP_Error(
			'us_mcp_typography_unknown_field',
			sprintf( 'Unknown typography field "%s". Known fields: %s.', $field, implode( ', ', array_keys( $spec_map ) ) ),
			array( 'status' => 400 )
		);
	}
	$spec = $spec_map[ $field ];

	if ( ! in_array( $tag, $spec['tags'], TRUE ) ) {
		return new WP_Error(
			'us_mcp_typography_field_not_for_tag',
			sprintf( 'Field "%s" is not applicable to tag "%s" (allowed tags: %s).', $field, $tag, implode( ', ', $spec['tags'] ) ),
			array( 'status' => 400 )
		);
	}

	// `null` means delete the key.
	if ( $value === NULL ) {
		return NULL;
	}

	// Responsive object — must use `default`/`laptops`/`tablets`/`mobiles`.
	if ( is_array( $value ) ) {
		if ( ! $spec['responsive'] ) {
			return new WP_Error(
				'us_mcp_typography_not_responsive',
				sprintf( 'Field "%s" does not accept a responsive object — pass a plain string.', $field ),
				array( 'status' => 400 )
			);
		}
		$allowed_keys = us_mcp_typography_responsive_keys();
		$unknown = array_diff( array_keys( $value ), $allowed_keys );
		if ( ! empty( $unknown ) ) {
			return new WP_Error(
				'us_mcp_typography_unknown_breakpoint',
				sprintf( 'Unknown responsive breakpoint(s): %s. Allowed: %s.', implode( ', ', $unknown ), implode( ', ', $allowed_keys ) ),
				array( 'status' => 400 )
			);
		}
		if ( ! array_key_exists( 'default', $value ) ) {
			return new WP_Error(
				'us_mcp_typography_missing_default',
				sprintf( 'Responsive value for "%s" must include a "default" breakpoint.', $field ),
				array( 'status' => 400 )
			);
		}
		// Re-order in the canonical sequence and string-cast each entry.
		$normalised = array();
		foreach ( $allowed_keys as $bp ) {
			if ( ! array_key_exists( $bp, $value ) ) {
				continue;
			}
			$bp_value = $value[ $bp ];
			if ( $bp_value !== '' AND ! is_scalar( $bp_value ) ) {
				return new WP_Error(
					'us_mcp_typography_bad_breakpoint_value',
					sprintf( 'Breakpoint "%s" of "%s" must be a string.', $bp, $field ),
					array( 'status' => 400 )
				);
			}
			$bp_value = (string) $bp_value;
			$check = us_mcp_typography_check_scalar( $tag, $field, $bp_value, $spec, $fonts );
			if ( is_wp_error( $check ) ) {
				return $check;
			}
			$normalised[ $bp ] = $bp_value;
		}
		return rawurlencode( wp_json_encode( $normalised ) );
	}

	// Scalar — accept string / int / float and coerce to string.
	if ( ! is_scalar( $value ) ) {
		return new WP_Error(
			'us_mcp_typography_bad_value_type',
			sprintf( 'Field "%s" must be a string, number, or null.', $field ),
			array( 'status' => 400 )
		);
	}
	$value = (string) $value;
	$check = us_mcp_typography_check_scalar( $tag, $field, $value, $spec, $fonts );
	if ( is_wp_error( $check ) ) {
		return $check;
	}
	return $value;
}

/**
 * Per-field scalar validation. Allows the `inherit` token and `var(--h1-…)`
 * CSS-variable references on non-h1 headings; otherwise enforces per-field
 * rules: configured fonts for font-family, the numeric CSS range (1..1000)
 * for font-weight / bold-font-weight, enums for text-transform / font-style.
 *
 * @param string $tag
 * @param string $field
 * @param string $value
 * @param array  $spec
 * @param array  $fonts
 * @return true|WP_Error
 */
function us_mcp_typography_check_scalar( $tag, $field, $value, $spec, $fonts ) {
	if ( $value === '' ) {
		// Empty string is always allowed — it's how the admin UI represents
		// "clear" for non-enum fields.
		return TRUE;
	}

	// var(--h1-…) inheritance reference — never valid on h1 itself.
	if ( strpos( $value, 'var(--h1-' ) === 0 ) {
		if ( $tag === 'h1' OR $tag === 'body' ) {
			return new WP_Error(
				'us_mcp_typography_bad_inherit',
				sprintf( '"%s" cannot reference var(--h1-…) on tag "%s".', $field, $tag ),
				array( 'status' => 400 )
			);
		}
		return TRUE;
	}

	// "inherit" — only valid as font-family on non-body tags.
	if ( $value === 'inherit' ) {
		if ( $field === 'font-family' AND $tag !== 'body' ) {
			return TRUE;
		}
		return new WP_Error(
			'us_mcp_typography_bad_inherit_token',
			sprintf( 'The "inherit" token is only allowed as font-family on h1..h6, not as "%s" on "%s".', $field, $tag ),
			array( 'status' => 400 )
		);
	}

	if ( $field === 'font-family' ) {
		$all_fonts = array_merge( $fonts['google'], $fonts['adobe'], $fonts['web_safe'], $fonts['uploaded'] );
		if ( ! in_array( $value, $all_fonts, TRUE ) ) {
			return new WP_Error(
				'us_mcp_typography_unknown_font',
				sprintf( 'Font "%s" is not configured for this site. Call upsolution-list-fonts to discover acceptable values (or "inherit" / a var(--h1-…) reference on h2..h6).', $value ),
				array( 'status' => 422 )
			);
		}
		return TRUE;
	}

	// font-weight / bold-font-weight — any whole number within the CSS
	// font-weight range. Variable Fonts accept intermediate axis values
	// (350, 425 …), so this is an integer range check, not a 100..900-in-steps
	// enum. Which values make sense for the current font is advisory data —
	// see the `variable` / `static_weights` maps returned by
	// upsolution-list-fonts.
	if ( $field === 'font-weight' OR $field === 'bold-font-weight' ) {
		if ( ! ctype_digit( $value ) OR (int) $value < 1 OR (int) $value > 1000 ) {
			return new WP_Error(
				'us_mcp_typography_bad_font_weight',
				sprintf( 'Value "%s" is not a valid %s — pass a whole number from 1 to 1000. Variable Fonts accept intermediate integer values within their wght axis; static fonts only really provide the weights listed by upsolution-list-fonts.', $value, $field ),
				array( 'status' => 400 )
			);
		}
		return TRUE;
	}

	if ( $spec['enum'] !== NULL AND ! in_array( $value, $spec['enum'], TRUE ) ) {
		return new WP_Error(
			'us_mcp_typography_bad_enum',
			sprintf( 'Value "%s" is not allowed for "%s". Allowed: %s.', $value, $field, implode( ', ', $spec['enum'] ) ),
			array( 'status' => 400 )
		);
	}

	return TRUE;
}

add_action( 'wp_abilities_api_init', function () {
	$tag_enum = US_TYPOGRAPHY_TAGS;
	$field_spec = us_mcp_typography_field_spec();
	$field_enum = array_keys( $field_spec );

	wp_register_ability( 'upsolution/list-fonts', array(
		'label'               => 'List fonts available for typography settings',
		'description'         => 'Enumerate every font-family value that upsolution-set-typography will accept on this site, grouped by source: Google Fonts (built-in catalogue), Adobe Fonts (loaded from a configured Adobe Web Project), web-safe stacks, and Uploaded Fonts (woff2 uploads in Theme Options → Typography). The "inherit" token and "var(--h1-…)" CSS-variable references on h2..h6 are also valid in set-typography but are NOT enumerated here. Two extra maps describe per-font weight capabilities: `variable` (Variable Fonts — wght/wdth axis ranges; ANY whole number inside the wght range is a valid font-weight, intermediate values like 350 or 425 included; wdth bounds font-stretch) and `static_weights` (static fonts — the exact numeric weights the font ships; other weights get synthesized by the browser and may render poorly). Fonts in neither map (Adobe Fonts, web-safe stacks) have unknown weight sets — prefer the common 100..900 step-100 values for those.',
		'category'            => 'upsolution',
		// No input.
		'output_schema'       => array(
			'type'       => 'object',
			'required'   => array( 'google', 'adobe', 'web_safe', 'uploaded', 'variable', 'static_weights' ),
			'properties' => array(
				'google'   => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => 'Names from the bundled Google Fonts catalogue (loaded on demand via fonts.googleapis.com).' ),
				'adobe'    => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => 'Adobe Fonts slugs from this site\'s configured Adobe Web Project — empty until an Adobe Fonts project id is set in Theme Options.' ),
				'web_safe' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => 'CSS font-family stacks that need no remote loading (e.g. "Georgia, serif").' ),
				'uploaded' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => 'Names of woff2 fonts uploaded under Theme Options → Typography → Uploaded Fonts.' ),
				'variable' => array(
					'type'        => 'object',
					'description' => 'Variable Fonts: font-family => axis ranges. "wght" bounds font-weight / bold-font-weight — any whole number inside the range is valid, intermediate values included. "wdth" (present only for fonts exposing a width axis) bounds font-stretch, in percent.',
					'additionalProperties' => array(
						'type'       => 'object',
						'properties' => array(
							'wght' => array(
								'type'       => 'object',
								'properties' => array(
									'min' => array( 'type' => 'integer' ),
									'max' => array( 'type' => 'integer' ),
								),
							),
							'wdth' => array(
								'type'       => 'object',
								'properties' => array(
									'min' => array( 'type' => 'number' ),
									'max' => array( 'type' => 'number' ),
								),
							),
						),
					),
				),
				'static_weights' => array(
					'type'        => 'object',
					'description' => 'Static fonts: font-family => the numeric font weights the font actually provides. Weights not in the list are synthesized by the browser and may render poorly.',
					'additionalProperties' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'integer' ),
					),
				),
			),
		),
		'execute_callback'    => 'us_mcp_typography_list_fonts',
		'permission_callback' => 'us_mcp_theme_option_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );

	wp_register_ability( 'upsolution/set-typography', array(
		'label'               => 'Patch site-wide typography for one tag',
		'description'         => 'Update the typography settings for one tag (body or h1..h6) in Theme Options. Fields mirror Theme Options → Typography: font-family, font-size, line-height, font-weight, bold-font-weight, font-stretch, text-transform, font-style, letter-spacing — plus margin-bottom on h1..h6. Every field except font-family accepts either a plain string OR an object {default, laptops, tablets, mobiles} — the default key is required when sending an object. font-family must be a value enumerated by upsolution-list-fonts, or "inherit" (h2..h6) to inherit Global Text, or "var(--h1-…)" (h2..h6) to inherit Heading 1. font-weight / bold-font-weight accept any whole number (integer) from 1 to 1000: Variable Fonts support intermediate values (350, 425, …) within their wght axis, while static fonts only really provide the weights listed in upsolution-list-fonts → static_weights (anything else gets browser-synthesized) — check that tool before picking a non-standard weight. font-stretch is a percentage like "85%" and only has effect on Variable Fonts exposing a wdth axis (see the `variable` map in list-fonts); leave it untouched for other fonts. text-transform is none/uppercase/lowercase/capitalize. font-style is normal/italic. Pass `merge=false` to replace the whole tag dict; defaults to true (partial patch — only the keys you list are touched, null clears a key). Heading color is NOT part of typography — use upsolution-set-palette for that (the `color` / `color_override` keys on h1..h6 are owned by the palette tool and are preserved across set-typography calls). Saving triggers regeneration of the site\'s CSS asset files, so changes take effect on the next page load. Before reading the current state, call upsolution-get-theme-option with name="body" / "h1" / .. — the value returned there is what gets patched.',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'       => 'object',
			'required'   => array( 'tag', 'fields' ),
			'properties' => array(
				'tag'    => array(
					'type'        => 'string',
					'enum'        => $tag_enum,
					'description' => 'Which typography tag to patch. "body" is the Global Text; "h1".."h6" are the heading levels.',
				),
				'fields' => array(
					'type'        => 'object',
					'description' => 'Map of field name → new value. Unlisted fields are left untouched (merge=true) or take their default (merge=false). Pass null to clear a field. Known fields: ' . implode( ', ', $field_enum ) . '. margin-bottom is only valid on h1..h6. Heading color belongs to upsolution-set-palette, not here.',
					'additionalProperties' => TRUE,
				),
				'merge'  => array(
					'type'        => 'boolean',
					'description' => 'true (default): patch — only the fields you list are touched. false: full replace — usof_options[tag] is replaced by exactly the fields you send (plus defaults for required keys).',
					'default'     => TRUE,
				),
			),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'required'   => array( 'tag', 'applied_fields', 'before', 'after', 'regenerated_assets' ),
			'properties' => array(
				'tag'                 => array( 'type' => 'string' ),
				'applied_fields'      => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => 'Field names that changed (write succeeded). Excludes no-op writes where the new value equalled the old one.',
				),
				'before'              => array( 'type' => 'object', 'description' => 'usof_options[tag] before the write, with responsive JSON decoded into objects.', 'additionalProperties' => TRUE ),
				'after'               => array( 'type' => 'object', 'description' => 'usof_options[tag] after the write, with responsive JSON decoded into objects.', 'additionalProperties' => TRUE ),
				'regenerated_assets'  => array( 'type' => 'boolean', 'description' => 'True once usof_save_options has run and the usof_after_save hook chain (including us_generate_asset_files) has fired.' ),
			),
		),
		'execute_callback'    => 'us_mcp_ability_set_typography',
		'permission_callback' => 'us_mcp_theme_option_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );
} );

/**
 * Validate the agent's typography input for one tag AND apply the result to
 * the supplied options array. No DB write — the caller decides whether to
 * persist via usof_save_options (the set-typography tool) or keep the result
 * in-memory (the create-preview tool, which loops this helper across many
 * tag patches in one snapshot).
 *
 * On a bad input the options array is left untouched.
 *
 * @param string $tag      One of US_TYPOGRAPHY_TAGS (body / h1 .. h6).
 * @param mixed  $fields   Map of field-name → new value (responsive-aware).
 * @param bool   $merge    TRUE: partial patch. FALSE: full replace (keeps the
 *                         palette-owned `color` / `color_override` keys).
 * @param array  $options  Reference to the options array to mutate.
 * @return array{tag: string, applied_fields: array, before: array, after: array}|WP_Error
 */
function us_mcp_typography_apply_to_options( $tag, $fields, $merge, array &$options ) {
	if ( ! in_array( $tag, US_TYPOGRAPHY_TAGS, TRUE ) ) {
		return new WP_Error(
			'us_mcp_typography_bad_tag',
			sprintf( 'Unknown tag "%s". Allowed: %s.', $tag, implode( ', ', US_TYPOGRAPHY_TAGS ) ),
			array( 'status' => 400 )
		);
	}
	if ( ! is_array( $fields ) ) {
		return new WP_Error(
			'us_mcp_typography_missing_fields',
			'Pass a `fields` object with the field names you want to update.',
			array( 'status' => 400 )
		);
	}
	if ( empty( $fields ) AND $merge ) {
		return new WP_Error(
			'us_mcp_typography_no_op',
			'No fields supplied — nothing to update.',
			array( 'status' => 400 )
		);
	}

	$fonts = us_mcp_typography_collect_fonts();

	// Normalise + validate every supplied field BEFORE touching the options
	// array. A single invalid field aborts the whole write — partial application
	// would be surprising and hard to roll back.
	$normalised = array();
	foreach ( $fields as $field_name => $field_value ) {
		$result = us_mcp_typography_normalise_field( $tag, (string) $field_name, $field_value, $fonts );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$normalised[ $field_name ] = $result;
	}

	$before_raw = isset( $options[ $tag ] ) ? (array) $options[ $tag ] : array();

	// On full-replace we still preserve keys owned by upsolution-set-palette
	// (color / color_override on h1..h6) — they live in the same tag dict but
	// are not typography concerns and dropping them would silently move colour
	// state under set-typography's effective ownership.
	if ( $merge ) {
		$new_tag = $before_raw;
	} else {
		$new_tag = array();
		foreach ( array( 'color', 'color_override' ) as $palette_key ) {
			if ( array_key_exists( $palette_key, $before_raw ) ) {
				$new_tag[ $palette_key ] = $before_raw[ $palette_key ];
			}
		}
	}
	$applied = array();
	foreach ( $normalised as $field_name => $new_value ) {
		$prev = array_key_exists( $field_name, $new_tag ) ? $new_tag[ $field_name ] : NULL;
		if ( $new_value === NULL ) {
			if ( array_key_exists( $field_name, $new_tag ) ) {
				unset( $new_tag[ $field_name ] );
				$applied[] = $field_name;
			}
			continue;
		}
		if ( $prev === $new_value ) {
			continue;
		}
		$new_tag[ $field_name ] = $new_value;
		$applied[] = $field_name;
	}

	$options[ $tag ] = $new_tag;

	return array(
		'tag'            => $tag,
		'applied_fields' => $applied,
		'before'         => us_mcp_typography_decode_tag( $before_raw ),
		'after'          => us_mcp_typography_decode_tag( $new_tag ),
	);
}

/**
 * @param array $input
 * @return array|WP_Error
 */
function us_mcp_ability_set_typography( $input ) {
	$input  = (array) $input;
	$tag    = isset( $input['tag'] ) ? (string) $input['tag'] : '';
	$fields = isset( $input['fields'] ) ? $input['fields'] : NULL;
	$merge  = array_key_exists( 'merge', $input ) ? (bool) $input['merge'] : TRUE;

	if ( ! function_exists( 'usof_save_options' ) OR ! function_exists( 'usof_load_options_once' ) ) {
		return new WP_Error(
			'us_mcp_typography_core_not_loaded',
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

	$result = us_mcp_typography_apply_to_options( $tag, $fields, $merge, $updated_options );
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	usof_save_options( $updated_options );

	return array(
		'tag'                => $result['tag'],
		'applied_fields'     => $result['applied_fields'],
		'before'             => $result['before'],
		'after'              => $result['after'],
		'regenerated_assets' => TRUE,
	);
}
