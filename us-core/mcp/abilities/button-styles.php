<?php
/**
 * UpSolution MCP — Theme Options button styles editor.
 *
 * Two abilities for inspecting and changing the site-wide Button Styles list
 * (Theme Options → Button Styles, stored under the `buttons` Theme Option):
 *
 *   upsolution-list-button-styles  — return the full list of entries. Each
 *                                    entry's `id` is what shortcode markup
 *                                    references via `style="N"` / `btn_style="N"`
 *                                    (us_btn, us_cta, us_pricing, us_cform,
 *                                    us_popup, us_flipbox, us_post_list, …).
 *   upsolution-set-button-styles   — apply a sequence of operations
 *                                    (add / update / delete / reorder) atomically.
 *                                    Saving triggers the usof_after_save →
 *                                    us_generate_asset_files hook chain so the
 *                                    CSS asset files are regenerated.
 *
 * The on-disk shape of one entry mirrors the param map under
 * `config/theme-options/buttons.php` (type=group) — see
 * us_mcp_button_styles_field_spec() for the agent-facing field list. UI-only
 * group markers (wrapper_shadow_start / _end / wrapper_shadow_hover_start /
 * _end) are NOT stored and are deliberately not exposed.
 *
 * Two hard rules enforced here that the admin UI handles by other means:
 *   - The list cannot become empty. Deleting the last remaining style is
 *     rejected — every us_btn / us_cta with style="N" / btn_style="N" across
 *     the site would silently fall through to the "Style 1" fallback.
 *   - An entry's `id` is immutable once assigned. The id is what shortcode
 *     markup throughout the site refers to; renumbering would silently
 *     repaint or unstyle every button referencing the old id.
 *
 * Color value validation reuses us_mcp_palette_check_color() — the same
 * syntaxes (#hex / rgb()/rgba() / transparent / linear-gradient(...) only /
 * palette tokens "_slug") that work in upsolution-set-palette work here.
 * hsl()/hsla() are NOT supported (the color picker cannot parse them).
 * radial-gradient() / conic-gradient() / repeating-*-gradient() are NOT
 * supported either — the underlying color picker (usof/js/field_color.js:239)
 * only recognises linear-gradient.
 *
 * @package usCore\Mcp
 */

defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

// Permission gate is the shared us_mcp_theme_option_permission_callback()
// from abilities/theme-option.php — edit_theme_options across every ability
// that touches Theme Options (typography, palette, buttons, preview).

/**
 * Hard cap on the number of stored button styles. Way above any plausible
 * real-world usage (sites typically have 2–8 styles); a runaway loop or a
 * confused agent would otherwise be able to bloat the options row to MB
 * sizes before any cap kicks in.
 */
const US_MCP_BUTTON_STYLES_MAX_COUNT = 50;

/**
 * Per-field schema for one button-style entry. Mirrors
 * config/theme-options/buttons.php (type=group). Each entry:
 *
 *   type     string  — 'string' / 'enum' / 'color' / 'css_value' /
 *                      'font_weight' / 'font' / 'checkbox_1' / 'text_style'.
 *   values   string[]? — enum members (for type=enum).
 *   gradient bool?   — for type=color, whether gradient syntax is allowed.
 *   default  scalar  — std value from the config; used to seed a new entry on
 *                      `add` and on `update merge=false` so the entry has a
 *                      complete field set the CSS renderer can consume
 *                      (templates/css-theme-options.php concatenates field
 *                      values into CSS without checking for absence).
 *
 * UI-only `wrapper_shadow_*` keys from the source config are intentionally
 * absent — they are accordion-section markers in the admin form, not stored
 * fields. So is `id`, which is owned by this file (auto-assigned, immutable).
 *
 * @return array<string, array{type: string, default: mixed, values?: array, gradient?: bool}>
 */
function us_mcp_button_styles_field_spec() {
	static $spec = NULL;
	if ( $spec !== NULL ) {
		return $spec;
	}
	$spec = array(
		'name'                       => array( 'type' => 'string',     'default' => 'Style' ),
		'class'                      => array( 'type' => 'string',     'default' => '' ),

		'hover'                      => array( 'type' => 'enum',       'values' => array( 'fade', 'slide', 'slideLeft', 'slideRight', 'slideBottom', 'scaleUp', 'scaleDown', 'circle' ), 'default' => 'fade' ),
		'hover_text_animation'       => array( 'type' => 'enum',       'values' => array( 'fade', 'slideTop', 'slideLeft', 'slideRight', 'slideBottom', 'scaleUp', 'scaleDown' ), 'default' => 'fade' ),
		'border_animation'           => array( 'type' => 'enum',       'values' => array( 'none', 'play_on_hover', 'pause_on_hover', 'play_always' ), 'default' => 'none' ),

		'transition_duration'        => array( 'type' => 'css_value',  'default' => '0.3s' ),
		'transition_timing_function' => array( 'type' => 'css_value',  'default' => '' ),
		'animation_duration'         => array( 'type' => 'css_value',  'default' => '3s' ),

		'color_bg'                   => array( 'type' => 'color',      'gradient' => TRUE,  'default' => '_content_secondary' ),
		'color_bg_hover'             => array( 'type' => 'color',      'gradient' => TRUE,  'default' => '' ),
		'color_border'               => array( 'type' => 'color',      'gradient' => TRUE,  'default' => '' ),
		'color_border_hover'         => array( 'type' => 'color',      'gradient' => TRUE,  'default' => '_content_secondary' ),
		'color_text'                 => array( 'type' => 'color',      'gradient' => FALSE, 'default' => '#fff' ),
		'color_text_hover'           => array( 'type' => 'color',      'gradient' => FALSE, 'default' => '_content_secondary' ),
		'color_shadow'               => array( 'type' => 'color',      'gradient' => FALSE, 'default' => '' ),
		'color_shadow_hover'         => array( 'type' => 'color',      'gradient' => FALSE, 'default' => '' ),

		'shadow_offset_h'            => array( 'type' => 'css_value',  'default' => '0px' ),
		'shadow_offset_v'            => array( 'type' => 'css_value',  'default' => '0px' ),
		'shadow_blur'                => array( 'type' => 'css_value',  'default' => '0px' ),
		'shadow_spread'              => array( 'type' => 'css_value',  'default' => '0px' ),
		'shadow_inset'               => array( 'type' => 'checkbox_1', 'default' => '' ),

		'shadow_hover_offset_h'      => array( 'type' => 'css_value',  'default' => '0px' ),
		'shadow_hover_offset_v'      => array( 'type' => 'css_value',  'default' => '0px' ),
		'shadow_hover_blur'          => array( 'type' => 'css_value',  'default' => '0px' ),
		'shadow_hover_spread'        => array( 'type' => 'css_value',  'default' => '0px' ),
		'shadow_hover_inset'         => array( 'type' => 'checkbox_1', 'default' => '' ),

		'font'                       => array( 'type' => 'font',       'default' => '' ),
		'height'                     => array( 'type' => 'css_value',  'default' => '0.8em' ),
		'font_size'                  => array( 'type' => 'css_value',  'default' => '1rem' ),
		'width'                      => array( 'type' => 'css_value',  'default' => '1.8em' ),
		'line_height'                => array( 'type' => 'css_value',  'default' => '1.2' ),
		'border_width'               => array( 'type' => 'css_value',  'default' => '2px' ),
		'font_weight'                => array( 'type' => 'font_weight','default' => '400' ),
		'border_radius'              => array( 'type' => 'css_value',  'default' => 'var(--site-border-radius)' ),
		'letter_spacing'             => array( 'type' => 'css_value',  'default' => '0' ),
		'text_style'                 => array( 'type' => 'text_style', 'default' => '' ),
	);
	return $spec;
}

/**
 * Default entry skeleton derived from the field spec — used to seed new
 * entries (`add` and `update merge=false`) so the CSS renderer always sees
 * a complete field set.
 *
 * @return array<string, mixed>
 */
function us_mcp_button_styles_default_entry() {
	static $defaults = NULL;
	if ( $defaults !== NULL ) {
		return $defaults;
	}
	$defaults = array();
	foreach ( us_mcp_button_styles_field_spec() as $key => $meta ) {
		$defaults[ $key ] = isset( $meta['default'] ) ? $meta['default'] : '';
	}
	return $defaults;
}

/**
 * The two tokens accepted by `text_style` (a checkboxes field — stored as
 * a comma-separated string).
 *
 * @return string[]
 */
function us_mcp_button_styles_text_style_tokens() {
	return array( 'uppercase', 'italic' );
}

/**
 * Collect every value the `font` field accepts on this install. The buttons
 * field is broader than typography's `font-family`: in addition to the named
 * families (Google / Adobe / web-safe / uploaded), buttons accept a
 * typography-tag reference (`body` / `h1` .. `h6`) that resolves at render
 * time to var(--<tag>-font-family) — see templates/css-theme-options.php
 * around line 1085 — and the "Additional Google Fonts" entries from the
 * `custom_font` Theme Option.
 *
 * @return array{tags: string[], typography_fonts: string[], custom_fonts: string[]}
 */
function us_mcp_button_styles_collect_fonts() {
	$tags = defined( 'US_TYPOGRAPHY_TAGS' ) ? (array) US_TYPOGRAPHY_TAGS : array( 'body', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );

	$typo_groups = function_exists( 'us_mcp_typography_collect_fonts' ) ? us_mcp_typography_collect_fonts() : array();
	$typo_fonts = array();
	foreach ( array( 'google', 'adobe', 'web_safe', 'uploaded' ) as $group ) {
		if ( isset( $typo_groups[ $group ] ) AND is_array( $typo_groups[ $group ] ) ) {
			$typo_fonts = array_merge( $typo_fonts, $typo_groups[ $group ] );
		}
	}

	$custom = array();
	$custom_fonts = function_exists( 'us_get_option' ) ? us_get_option( 'custom_font', array() ) : array();
	if ( is_array( $custom_fonts ) ) {
		foreach ( $custom_fonts as $entry ) {
			if ( ! is_array( $entry ) OR empty( $entry['font_family'] ) ) {
				continue;
			}
			// Stored as "FamilyName|400,500" — name is everything before the
			// first "|". Fall back to the raw value if no separator present.
			$family = (string) $entry['font_family'];
			$name = strstr( $family, '|', TRUE );
			if ( $name === FALSE ) {
				$name = $family;
			}
			$name = trim( $name );
			if ( $name !== '' ) {
				$custom[] = $name;
			}
		}
	}

	return array(
		'tags'             => array_values( $tags ),
		'typography_fonts' => array_values( $typo_fonts ),
		'custom_fonts'     => array_values( $custom ),
	);
}

/**
 * Validate one field value against the spec. Returns the normalised value
 * (ready to store), NULL (meaning "clear this field" — only valid when
 * $allow_clear is TRUE), or a WP_Error.
 *
 * @param string $key
 * @param mixed  $value
 * @param bool   $allow_clear  TRUE in merge=true update context (null clears);
 *                             FALSE on add / replace (full entries can't have
 *                             null fields — defaults are applied instead).
 * @return string|null|WP_Error
 */
function us_mcp_button_styles_check_field( $key, $value, $allow_clear = TRUE ) {
	$spec_map = us_mcp_button_styles_field_spec();
	if ( ! isset( $spec_map[ $key ] ) ) {
		return new WP_Error(
			'us_mcp_button_styles_unknown_field',
			sprintf( 'Unknown button-style field "%s". Allowed fields: %s.', $key, implode( ', ', array_keys( $spec_map ) ) ),
			array( 'status' => 400 )
		);
	}
	$spec = $spec_map[ $key ];

	if ( $value === NULL ) {
		if ( ! $allow_clear ) {
			return new WP_Error(
				'us_mcp_button_styles_null_not_allowed',
				sprintf( 'Field "%s" cannot be null in this context (add / replace requires concrete values; defaults fill the gaps for omitted fields).', $key ),
				array( 'status' => 400 )
			);
		}
		return NULL;
	}

	switch ( $spec['type'] ) {
		case 'string':
			if ( ! is_string( $value ) AND ! is_numeric( $value ) ) {
				return new WP_Error(
					'us_mcp_button_styles_bad_string',
					sprintf( 'Field "%s" must be a string.', $key ),
					array( 'status' => 400 )
				);
			}
			$value = trim( (string) $value );
			if ( $key === 'name' AND $value === '' ) {
				return new WP_Error(
					'us_mcp_button_styles_empty_name',
					'Field "name" cannot be an empty string. Pass null in a merge=true patch to clear it (falls back to "Style <id>" at render time), or omit the field.',
					array( 'status' => 400 )
				);
			}
			if ( preg_match( '/[\r\n\t]/', $value ) ) {
				return new WP_Error(
					'us_mcp_button_styles_bad_string_chars',
					sprintf( 'Field "%s" must not contain line breaks or tabs.', $key ),
					array( 'status' => 400 )
				);
			}
			return $value;

		case 'enum':
			if ( ! is_string( $value ) OR ! in_array( $value, $spec['values'], TRUE ) ) {
				return new WP_Error(
					'us_mcp_button_styles_bad_enum',
					sprintf( 'Value "%s" is not allowed for "%s". Allowed: %s.', is_scalar( $value ) ? (string) $value : gettype( $value ), $key, implode( ', ', $spec['values'] ) ),
					array( 'status' => 400 )
				);
			}
			return $value;

		case 'color':
			if ( ! function_exists( 'us_mcp_palette_check_color' ) ) {
				return new WP_Error(
					'us_mcp_button_styles_palette_unavailable',
					'us_mcp_palette_check_color() is not available — abilities/color-palette.php did not load.',
					array( 'status' => 503 )
				);
			}
			// `$current_key` (4th param) is palette-specific — it detects a
			// token referencing the field being written (e.g. "_content_bg"
			// into color_content_bg). On the button side none of the field
			// names collide with palette field names, so we leave it empty
			// to disable that check.
			return us_mcp_palette_check_color( $value, (bool) $spec['gradient'], $key );

		case 'css_value':
			// Mild: accept any scalar, coerce to string, allow empty (clear).
			// Mostly used for slider-typed fields (sizes / shadow offsets /
			// transitions) — the renderer accepts any CSS value so we don't
			// reach for full CSS-length parsing here.
			if ( ! is_scalar( $value ) ) {
				return new WP_Error(
					'us_mcp_button_styles_bad_css_value',
					sprintf( 'Field "%s" must be a string or number.', $key ),
					array( 'status' => 400 )
				);
			}
			$value = trim( (string) $value );
			if ( preg_match( '/[\r\n\t]/', $value ) ) {
				return new WP_Error(
					'us_mcp_button_styles_bad_css_value_chars',
					sprintf( 'Field "%s" must not contain line breaks or tabs.', $key ),
					array( 'status' => 400 )
				);
			}
			return $value;

		case 'font_weight':
			// Mild: accept numeric 100..900 in 100 steps, or a CSS keyword.
			// The admin slider only emits the numeric form, but the renderer
			// (`echo 'font-weight:' . $value`) accepts keywords too.
			if ( is_int( $value ) OR ( is_string( $value ) AND ctype_digit( trim( $value ) ) ) ) {
				$n = (int) $value;
				if ( $n >= 100 AND $n <= 900 ) {
					return (string) $n;
				}
			}
			if ( is_string( $value ) AND in_array( strtolower( trim( $value ) ), array( '', 'normal', 'bold', 'lighter', 'bolder' ), TRUE ) ) {
				return strtolower( trim( $value ) );
			}
			return new WP_Error(
				'us_mcp_button_styles_bad_font_weight',
				sprintf( 'Field "font_weight" must be a number 100..900 or one of: normal / bold / lighter / bolder. Got: %s.', is_scalar( $value ) ? (string) $value : gettype( $value ) ),
				array( 'status' => 400 )
			);

		case 'font':
			if ( ! is_string( $value ) ) {
				return new WP_Error(
					'us_mcp_button_styles_bad_font',
					'Field "font" must be a string.',
					array( 'status' => 400 )
				);
			}
			$value = trim( $value );
			if ( $value === '' ) {
				return '';
			}
			$fonts = us_mcp_button_styles_collect_fonts();
			$all = array_merge( $fonts['tags'], $fonts['typography_fonts'], $fonts['custom_fonts'] );
			if ( ! in_array( $value, $all, TRUE ) ) {
				return new WP_Error(
					'us_mcp_button_styles_unknown_font',
					sprintf( 'Font "%s" is not available on this install. Accepted values: empty string (use default body font); a typography tag (%s) to inherit that tag\'s font-family; any name returned by upsolution-list-fonts; or a name from the Additional Google Fonts list (Theme Options → Typography → Additional Google Fonts).', $value, implode( ' / ', $fonts['tags'] ) ),
					array( 'status' => 422 )
				);
			}
			return $value;

		case 'checkbox_1':
			// Off → ''; on → '1'. Accept boolean / int 0|1 / string / array
			// forms — the admin UI variant is `array('1')` for checked.
			if ( $value === '' OR $value === FALSE OR $value === 0 OR $value === '0' OR ( is_array( $value ) AND empty( $value ) ) ) {
				return '';
			}
			if ( $value === TRUE OR $value === 1 OR $value === '1' ) {
				return '1';
			}
			if ( is_array( $value ) ) {
				$flat = array_map( 'strval', $value );
				if ( in_array( '1', $flat, TRUE ) ) {
					return '1';
				}
				return '';
			}
			return new WP_Error(
				'us_mcp_button_styles_bad_checkbox',
				sprintf( 'Field "%s" must be a boolean, "1"/"" string, 0/1 number, or [] / ["1"] array.', $key ),
				array( 'status' => 400 )
			);

		case 'text_style':
			$tokens = us_mcp_button_styles_text_style_tokens();
			if ( $value === '' OR ( is_array( $value ) AND empty( $value ) ) ) {
				return '';
			}
			$list = array();
			if ( is_string( $value ) ) {
				foreach ( explode( ',', $value ) as $token ) {
					$t = trim( $token );
					if ( $t !== '' ) {
						$list[] = $t;
					}
				}
			} elseif ( is_array( $value ) ) {
				foreach ( $value as $token ) {
					if ( ! is_scalar( $token ) ) {
						return new WP_Error(
							'us_mcp_button_styles_bad_text_style_token',
							'Each text_style token must be a string.',
							array( 'status' => 400 )
						);
					}
					$t = trim( (string) $token );
					if ( $t !== '' ) {
						$list[] = $t;
					}
				}
			} else {
				return new WP_Error(
					'us_mcp_button_styles_bad_text_style',
					'Field "text_style" must be a string ("uppercase,italic") or an array (["uppercase","italic"]).',
					array( 'status' => 400 )
				);
			}
			$invalid = array_values( array_diff( $list, $tokens ) );
			if ( ! empty( $invalid ) ) {
				return new WP_Error(
					'us_mcp_button_styles_unknown_text_style',
					sprintf( 'Unknown text_style token(s): %s. Allowed: %s.', implode( ', ', $invalid ), implode( ', ', $tokens ) ),
					array( 'status' => 400 )
				);
			}
			// Dedupe while preserving order.
			$seen = array();
			$dedup = array();
			foreach ( $list as $t ) {
				if ( ! isset( $seen[ $t ] ) ) {
					$dedup[] = $t;
					$seen[ $t ] = TRUE;
				}
			}
			return implode( ',', $dedup );

		default:
			return new WP_Error(
				'us_mcp_button_styles_internal_unknown_type',
				sprintf( 'Internal: unknown field type "%s" for "%s".', $spec['type'], $key ),
				array( 'status' => 500 )
			);
	}
}

/**
 * Validate a complete entry's fields (used by `add` and `update merge=false`).
 * Requires `name`. Rejects `id` (auto-assigned / immutable). Disallows null
 * values (full entries are complete — null is ambiguous in that context).
 *
 * @param mixed $fields
 * @return array<string, mixed>|WP_Error
 */
function us_mcp_button_styles_validate_full_fields( $fields ) {
	if ( ! is_array( $fields ) ) {
		return new WP_Error(
			'us_mcp_button_styles_fields_not_object',
			'`fields` must be an object with the entry\'s values.',
			array( 'status' => 400 )
		);
	}
	if ( array_key_exists( 'id', $fields ) ) {
		return new WP_Error(
			'us_mcp_button_styles_explicit_id',
			'Do not pass `id` in `fields`. New ids are auto-assigned on add; existing ids are immutable on update (every shortcode style="N" / btn_style="N" across the site references the id).',
			array( 'status' => 400 )
		);
	}
	if ( ! array_key_exists( 'name', $fields ) ) {
		return new WP_Error(
			'us_mcp_button_styles_missing_name',
			'Field "name" is required when adding a style or replacing one (merge=false).',
			array( 'status' => 400 )
		);
	}
	$normalised = array();
	foreach ( $fields as $key => $value ) {
		$check = us_mcp_button_styles_check_field( (string) $key, $value, /* allow_clear */ FALSE );
		if ( is_wp_error( $check ) ) {
			return $check;
		}
		$normalised[ (string) $key ] = $check;
	}
	return $normalised;
}

/**
 * Validate a partial-patch fields dict (used by `update merge=true`). Allows
 * null to mean "clear this field" (falls back to the default at render time).
 * Rejects `id` and empty patches.
 *
 * @param mixed $fields
 * @return array<string, mixed>|WP_Error
 */
function us_mcp_button_styles_validate_patch_fields( $fields ) {
	if ( ! is_array( $fields ) ) {
		return new WP_Error(
			'us_mcp_button_styles_fields_not_object',
			'`fields` must be an object with the partial patch.',
			array( 'status' => 400 )
		);
	}
	if ( array_key_exists( 'id', $fields ) ) {
		return new WP_Error(
			'us_mcp_button_styles_explicit_id',
			'Do not pass `id` in `fields` — ids are immutable once assigned.',
			array( 'status' => 400 )
		);
	}
	if ( empty( $fields ) ) {
		return new WP_Error(
			'us_mcp_button_styles_empty_patch',
			'No fields supplied — nothing to update. Pass at least one field in `fields` (or use merge=false with a full replacement).',
			array( 'status' => 400 )
		);
	}
	$normalised = array();
	foreach ( $fields as $key => $value ) {
		$check = us_mcp_button_styles_check_field( (string) $key, $value, /* allow_clear */ TRUE );
		if ( is_wp_error( $check ) ) {
			return $check;
		}
		$normalised[ (string) $key ] = $check; // may be NULL → clear
	}
	return $normalised;
}

/**
 * Compute the next free id given the current list of styles. Mirrors the
 * admin-side logic in usof/js/_general.js:1691 — `max(existing ids) + 1`.
 *
 * @param array<int, array> $styles
 * @return int
 */
function us_mcp_button_styles_next_id( array $styles ) {
	$max = 0;
	foreach ( $styles as $entry ) {
		if ( isset( $entry['id'] ) ) {
			$n = (int) $entry['id'];
			if ( $n > $max ) {
				$max = $n;
			}
		}
	}
	return $max + 1;
}

/**
 * Find the array index of the entry with id=$id, or -1.
 *
 * @param array<int, array> $styles
 * @param int $id
 * @return int
 */
function us_mcp_button_styles_find_index( array $styles, $id ) {
	foreach ( $styles as $idx => $entry ) {
		if ( isset( $entry['id'] ) AND (int) $entry['id'] === (int) $id ) {
			return $idx;
		}
	}
	return -1;
}

/**
 * Normalise an entry read from storage — ensure `id` is an int. Other fields
 * are passed through as-is.
 *
 * @param array $entry
 * @return array
 */
function us_mcp_button_styles_normalise_entry( array $entry ) {
	if ( isset( $entry['id'] ) ) {
		$entry['id'] = (int) $entry['id'];
	}
	return $entry;
}

/**
 * Read every stored button-style entry. Entries with no `id` are dropped —
 * they would never be addressable by shortcodes anyway.
 *
 * @return array<int, array>
 */
function us_mcp_button_styles_read_all() {
	$raw = function_exists( 'us_get_option' ) ? us_get_option( 'buttons', array() ) : array();
	return us_mcp_button_styles_read_from( array( 'buttons' => $raw ) );
}

/**
 * Same as us_mcp_button_styles_read_all() but reads from an arbitrary options
 * array — so the preview helper can introspect a snapshot without touching
 * the live $usof_options global.
 *
 * @param array $options
 * @return array<int, array>
 */
function us_mcp_button_styles_read_from( array $options ) {
	$raw = isset( $options['buttons'] ) ? $options['buttons'] : array();
	if ( ! is_array( $raw ) ) {
		return array();
	}
	$out = array();
	foreach ( $raw as $entry ) {
		if ( is_array( $entry ) AND isset( $entry['id'] ) ) {
			$out[] = us_mcp_button_styles_normalise_entry( $entry );
		}
	}
	return $out;
}

/**
 * Validate the agent's `operations` input AND apply the result to the supplied
 * options array. No DB write — the caller decides whether to persist via
 * usof_save_options (set-button-styles) or keep the result in-memory
 * (create-preview).
 *
 * Operations are applied sequentially to a working copy of the list. If any
 * operation fails validation, $options is left untouched and a WP_Error is
 * returned — partial application would be surprising and hard to roll back.
 *
 * Supported ops:
 *   - add     { fields: {...}, position?: int }
 *               Auto-assigns id = max(existing)+1. position omitted = append;
 *               otherwise insert at that 0-based index (0..count valid).
 *   - update  { id: int, fields: {...}, merge?: bool=true }
 *               merge=true → partial patch (null clears a field).
 *               merge=false → replace the entry (id preserved, defaults seed
 *               omitted fields, `name` required).
 *   - delete  { id: int }
 *               Rejected if it would leave 0 entries — see header comment.
 *   - reorder { ids: int[] }
 *               Must be a permutation of the current id set.
 *
 * @param array $input    { operations: [...] }
 * @param array $options  Reference to the options array to mutate.
 * @return array{applied: array, before: array, after: array}|WP_Error
 */
function us_mcp_button_styles_apply_to_options( $input, array &$options ) {
	$input = (array) $input;
	$operations = isset( $input['operations'] ) ? $input['operations'] : NULL;
	if ( ! is_array( $operations ) OR empty( $operations ) ) {
		return new WP_Error(
			'us_mcp_button_styles_no_op',
			'Pass at least one operation in `operations` (add / update / delete / reorder).',
			array( 'status' => 400 )
		);
	}

	$styles = us_mcp_button_styles_read_from( $options );
	$before_styles = $styles;
	$applied = array();

	foreach ( $operations as $idx => $op_entry ) {
		if ( ! is_array( $op_entry ) ) {
			return new WP_Error(
				'us_mcp_button_styles_bad_op',
				sprintf( 'operations[%d] must be an object.', $idx ),
				array( 'status' => 400 )
			);
		}
		$op = isset( $op_entry['op'] ) ? (string) $op_entry['op'] : '';

		switch ( $op ) {
			case 'add':
				if ( count( $styles ) >= US_MCP_BUTTON_STYLES_MAX_COUNT ) {
					return new WP_Error(
						'us_mcp_button_styles_max_count',
						sprintf( 'operations[%d]: cannot add — the list already has %d entries (hard cap is %d).', $idx, count( $styles ), US_MCP_BUTTON_STYLES_MAX_COUNT ),
						array( 'status' => 400 )
					);
				}
				$fields_in = isset( $op_entry['fields'] ) ? $op_entry['fields'] : NULL;
				$normalised = us_mcp_button_styles_validate_full_fields( $fields_in );
				if ( is_wp_error( $normalised ) ) {
					return $normalised;
				}
				$new_id = us_mcp_button_styles_next_id( $styles );
				$entry = array_merge( us_mcp_button_styles_default_entry(), $normalised, array( 'id' => $new_id ) );

				$position = array_key_exists( 'position', $op_entry ) ? $op_entry['position'] : NULL;
				if ( $position === NULL ) {
					$final_idx = count( $styles );
					$styles[] = $entry;
				} else {
					if ( ! is_int( $position ) AND ! ( is_string( $position ) AND ctype_digit( trim( $position ) ) ) ) {
						return new WP_Error(
							'us_mcp_button_styles_bad_position',
							sprintf( 'operations[%d]: `position` must be a non-negative integer (or omitted to append).', $idx ),
							array( 'status' => 400 )
						);
					}
					$position = (int) $position;
					if ( $position < 0 OR $position > count( $styles ) ) {
						return new WP_Error(
							'us_mcp_button_styles_position_out_of_range',
							sprintf( 'operations[%d]: `position` %d is out of range (must be 0..%d, inclusive).', $idx, $position, count( $styles ) ),
							array( 'status' => 400 )
						);
					}
					array_splice( $styles, $position, 0, array( $entry ) );
					$final_idx = $position;
				}

				$applied[] = array(
					'op'       => 'add',
					'id'       => $new_id,
					'position' => $final_idx,
				);
				break;

			case 'update':
				$id_in = isset( $op_entry['id'] ) ? $op_entry['id'] : NULL;
				if ( ! is_int( $id_in ) AND ! ( is_string( $id_in ) AND ctype_digit( trim( $id_in ) ) ) ) {
					return new WP_Error(
						'us_mcp_button_styles_bad_id',
						sprintf( 'operations[%d]: `id` must be a positive integer.', $idx ),
						array( 'status' => 400 )
					);
				}
				$id_in = (int) $id_in;
				$entry_idx = us_mcp_button_styles_find_index( $styles, $id_in );
				if ( $entry_idx < 0 ) {
					return new WP_Error(
						'us_mcp_button_styles_id_not_found',
						sprintf( 'operations[%d]: no button style found with id=%d. Current ids: %s.', $idx, $id_in, json_encode( array_values( array_map( function( $e ) { return (int) $e['id']; }, $styles ) ) ) ),
						array( 'status' => 404 )
					);
				}
				$fields_in = isset( $op_entry['fields'] ) ? $op_entry['fields'] : NULL;
				$merge = array_key_exists( 'merge', $op_entry ) ? (bool) $op_entry['merge'] : TRUE;

				if ( $merge ) {
					$patch = us_mcp_button_styles_validate_patch_fields( $fields_in );
					if ( is_wp_error( $patch ) ) {
						return $patch;
					}
					$changed = array();
					foreach ( $patch as $key => $value ) {
						$prev = array_key_exists( $key, $styles[ $entry_idx ] ) ? $styles[ $entry_idx ][ $key ] : NULL;
						if ( $value === NULL ) {
							if ( array_key_exists( $key, $styles[ $entry_idx ] ) ) {
								unset( $styles[ $entry_idx ][ $key ] );
								$changed[] = $key;
							}
							continue;
						}
						if ( $prev === $value ) {
							continue;
						}
						$styles[ $entry_idx ][ $key ] = $value;
						$changed[] = $key;
					}
					$applied[] = array(
						'op'             => 'update',
						'id'             => $id_in,
						'merge'          => TRUE,
						'changed_fields' => $changed,
					);
				} else {
					$normalised = us_mcp_button_styles_validate_full_fields( $fields_in );
					if ( is_wp_error( $normalised ) ) {
						return $normalised;
					}
					$new_entry = array_merge(
						us_mcp_button_styles_default_entry(),
						$normalised,
						array( 'id' => $id_in )
					);
					$styles[ $entry_idx ] = $new_entry;
					$applied[] = array(
						'op'             => 'update',
						'id'             => $id_in,
						'merge'          => FALSE,
						'changed_fields' => array_keys( $normalised ),
					);
				}
				break;

			case 'delete':
				$id_in = isset( $op_entry['id'] ) ? $op_entry['id'] : NULL;
				if ( ! is_int( $id_in ) AND ! ( is_string( $id_in ) AND ctype_digit( trim( $id_in ) ) ) ) {
					return new WP_Error(
						'us_mcp_button_styles_bad_id',
						sprintf( 'operations[%d]: `id` must be a positive integer.', $idx ),
						array( 'status' => 400 )
					);
				}
				$id_in = (int) $id_in;
				$entry_idx = us_mcp_button_styles_find_index( $styles, $id_in );
				if ( $entry_idx < 0 ) {
					return new WP_Error(
						'us_mcp_button_styles_id_not_found',
						sprintf( 'operations[%d]: no button style found with id=%d.', $idx, $id_in ),
						array( 'status' => 404 )
					);
				}
				if ( count( $styles ) <= 1 ) {
					return new WP_Error(
						'us_mcp_button_styles_last_style',
						sprintf( 'operations[%d]: cannot delete style id=%d — at least one button style must remain in the list. Every us_btn / us_cta / us_pricing / us_cform across the site references a style by id; if none remain, all of those silently fall back to a hardcoded default. Add a replacement first, then delete this one.', $idx, $id_in ),
						array( 'status' => 400 )
					);
				}
				array_splice( $styles, $entry_idx, 1 );
				$applied[] = array(
					'op' => 'delete',
					'id' => $id_in,
				);
				break;

			case 'reorder':
				$ids_in = isset( $op_entry['ids'] ) ? $op_entry['ids'] : NULL;
				if ( ! is_array( $ids_in ) ) {
					return new WP_Error(
						'us_mcp_button_styles_bad_reorder',
						sprintf( 'operations[%d]: `ids` must be an array of integers.', $idx ),
						array( 'status' => 400 )
					);
				}
				$clean_ids = array();
				foreach ( $ids_in as $rid ) {
					if ( ! is_int( $rid ) AND ! ( is_string( $rid ) AND ctype_digit( trim( $rid ) ) ) ) {
						return new WP_Error(
							'us_mcp_button_styles_bad_reorder_id',
							sprintf( 'operations[%d]: `ids` must contain only positive integers.', $idx ),
							array( 'status' => 400 )
						);
					}
					$clean_ids[] = (int) $rid;
				}
				// Explicit duplicate-id check BEFORE the permutation
				// comparison: `[1,1,2,3]` against current `[1,2,3,4]` would
				// otherwise surface as a generic "not a permutation" error,
				// which doesn't point the caller at the actual problem.
				$dup_counts = array_count_values( $clean_ids );
				$dup_ids = array_keys( array_filter( $dup_counts, function( $count ) { return $count > 1; } ) );
				if ( ! empty( $dup_ids ) ) {
					sort( $dup_ids );
					return new WP_Error(
						'us_mcp_button_styles_reorder_duplicate_id',
						sprintf( 'operations[%d]: `ids` contains duplicate entries: %s. Each existing button-style id must appear exactly once.', $idx, json_encode( $dup_ids ) ),
						array( 'status' => 400 )
					);
				}
				$current_ids = array_map( function( $e ) { return (int) $e['id']; }, $styles );
				$a = $clean_ids; sort( $a );
				$b = $current_ids; sort( $b );
				if ( $a !== $b ) {
					return new WP_Error(
						'us_mcp_button_styles_reorder_mismatch',
						sprintf( 'operations[%d]: `ids` must be a permutation of the current id set %s; got %s. Use separate add / delete ops in the same call to change membership.', $idx, json_encode( array_values( $current_ids ) ), json_encode( $clean_ids ) ),
						array( 'status' => 400 )
					);
				}
				$reordered = array();
				foreach ( $clean_ids as $rid ) {
					$reordered[] = $styles[ us_mcp_button_styles_find_index( $styles, $rid ) ];
				}
				$styles = array_values( $reordered );
				$applied[] = array(
					'op'  => 'reorder',
					'ids' => $clean_ids,
				);
				break;

			default:
				return new WP_Error(
					'us_mcp_button_styles_unknown_op',
					sprintf( 'operations[%d]: unknown op "%s". Allowed: add, update, delete, reorder.', $idx, $op ),
					array( 'status' => 400 )
				);
		}
	}

	$options['buttons'] = $styles;

	return array(
		'applied' => $applied,
		'before'  => $before_styles,
		'after'   => $styles,
	);
}

add_action( 'wp_abilities_api_init', function () {
	wp_register_ability( 'upsolution/list-button-styles', array(
		'label'               => 'List the site\'s button styles',
		'description'         => 'Return every button-style entry currently stored under the `buttons` Theme Option (Theme Options → Button Styles). Each entry\'s `id` is what shortcode markup references via `style="N"` / `btn_style="N"` (us_btn, us_cta, us_pricing, us_cform, us_popup, us_flipbox, us_post_list, us_post_taxonomy, …) — reading the list BEFORE composing a section that uses buttons ensures you target an existing style rather than silently falling through to the "Style 1" fallback. Returns `{styles: [...], count: N, ids: [1, 2, …]}`. Each entry exposes the same fields the admin Button Styles editor manages; field-by-field semantics, accepted values, and the operation shapes of set-button-styles live in the `design/buttons` doc (pull it via upsolution-read-doc before modifying styles).',
		'category'            => 'upsolution',
		// No input — call with no params.
		'output_schema'       => array(
			'type'       => 'object',
			'required'   => array( 'styles', 'count', 'ids' ),
			'properties' => array(
				'styles' => array(
					'type'        => 'array',
					'description' => 'Ordered list of button-style entries. Order matches the storage and admin-UI order.',
					'items'       => array(
						'type'                 => 'object',
						'description'          => 'A button-style entry. `id` (int) is referenced by shortcode style="N" / btn_style="N"; `name` (string) is the editor label. Other keys mirror Theme Options → Button Styles (colors, shadow, hover animation, typography, sizes). Field details live in the design/buttons doc.',
						'required'             => array( 'id' ),
						'additionalProperties' => TRUE,
						'properties'           => array(
							'id'   => array( 'type' => 'integer', 'description' => 'Immutable identifier referenced from shortcode markup.' ),
							'name' => array( 'type' => 'string', 'description' => 'Editor label.' ),
						),
					),
				),
				'count' => array( 'type' => 'integer', 'minimum' => 0, 'description' => 'Number of styles stored.' ),
				'ids'   => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ), 'description' => 'Flat list of ids — convenient for "does style N exist?" checks.' ),
			),
		),
		'execute_callback'    => 'us_mcp_ability_list_button_styles',
		'permission_callback' => 'us_mcp_theme_option_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );

	wp_register_ability( 'upsolution/set-button-styles', array(
		'label'               => 'Edit the site\'s button styles',
		'description'         => 'Mutate the `buttons` Theme Option (Theme Options → Button Styles) by applying a sequence of operations as one transaction. If any operation fails validation, none are persisted. MANDATORY pre-read: the `design/buttons` doc (upsolution-read-doc) — it lists every field with accepted values, the per-field gradient policy, the `font` enum, and operational facts the validator does not enforce.' . "\n\n"
			. 'Operation shapes:' . "\n"
			. '  - add     { fields: {...}, position?: int }  — create a new style. `name` is REQUIRED in `fields`. `id` is AUTO-assigned; passing `id` in `fields` is rejected. Omitted fields take their config defaults. position is 0-based — omit to append.' . "\n"
			. '  - update  { id: int, fields: {...}, merge?: bool=true }  — merge=true: partial patch (pass null to clear a field back to its default). merge=false: replace the entry — id preserved, `name` required. `id` cannot appear in `fields`.' . "\n"
			. '  - delete  { id: int }  — refused if it would leave 0 entries (shortcodes reference styles by id; an empty list silently falls back to a hardcoded default). Add a replacement first.' . "\n"
			. '  - reorder { ids: int[] }  — replace the storage order. Must be a permutation of the current id set — combine with add / delete earlier in the SAME call to change membership.' . "\n\n"
			. 'Hard rules: ids are immutable (shortcode style="N" references them); the list can never become empty.' . "\n\n"
			. 'Color fields accept the same syntax upsolution-set-palette accepts (hex / rgb()/rgba() / "transparent" / palette tokens "_<slug>"); linear-gradient(...) ONLY on color_bg / color_bg_hover / color_border / color_border_hover — the text / shadow colors are solid-only; hsl()/hsla() and non-linear gradients are rejected. The `font` field takes "" (default body font), a typography tag (body / h1 .. h6), or a family name from upsolution-list-fonts — unknown names return 422.' . "\n\n"
			. 'Saving regenerates the site\'s CSS asset files — changes take effect on the next page load.',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'                 => 'object',
			'required'             => array( 'operations' ),
			'additionalProperties' => FALSE,
			'properties'           => array(
				'operations' => array(
					'type'        => 'array',
					'minItems'    => 1,
					'description' => 'Ordered list of operations applied as a single transaction. Each entry: {op: "add"|"update"|"delete"|"reorder", ...}. See the tool description for per-op fields.',
					'items'       => array(
						'type'                 => 'object',
						'required'             => array( 'op' ),
						'additionalProperties' => TRUE,
						'properties'           => array(
							'op'       => array( 'type' => 'string', 'enum' => array( 'add', 'update', 'delete', 'reorder' ) ),
							'id'       => array( 'type' => 'integer', 'description' => 'For update / delete: id of the existing style to operate on. Forbidden on add / reorder.' ),
							'fields'   => array( 'type' => 'object', 'description' => 'For add / update: per-field value map. See design/buttons doc for accepted values.', 'additionalProperties' => TRUE ),
							'merge'    => array( 'type' => 'boolean', 'description' => 'For update only: true (default) = partial patch, false = replace.', 'default' => TRUE ),
							'position' => array( 'type' => 'integer', 'description' => 'For add only: 0-based insertion index. Omit to append.' ),
							'ids'      => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ), 'description' => 'For reorder only: target order as a permutation of the current id set.' ),
						),
					),
				),
			),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'required'   => array( 'applied', 'before', 'after', 'regenerated_assets' ),
			'properties' => array(
				'applied'            => array(
					'type'        => 'array',
					'description' => 'Per-operation audit. Each entry mirrors the input op + the assigned/affected id and, for updates, the list of fields that actually changed (no-op writes are dropped).',
					'items'       => array( 'type' => 'object', 'additionalProperties' => TRUE ),
				),
				'before'             => array(
					'type'        => 'array',
					'description' => 'Full list of entries before the call (same shape as upsolution-list-button-styles.styles).',
					'items'       => array( 'type' => 'object', 'additionalProperties' => TRUE ),
				),
				'after'              => array(
					'type'        => 'array',
					'description' => 'Full list of entries after the call. New ids and any reordering visible here.',
					'items'       => array( 'type' => 'object', 'additionalProperties' => TRUE ),
				),
				'regenerated_assets' => array( 'type' => 'boolean', 'description' => 'TRUE once usof_save_options has run and the usof_after_save hook chain (including us_generate_asset_files) has fired.' ),
			),
		),
		'execute_callback'    => 'us_mcp_ability_set_button_styles',
		'permission_callback' => 'us_mcp_theme_option_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );
} );

/**
 * @return array{styles: array, count: int, ids: array<int, int>}
 */
function us_mcp_ability_list_button_styles() {
	$styles = us_mcp_button_styles_read_all();
	$ids = array();
	foreach ( $styles as $entry ) {
		if ( isset( $entry['id'] ) ) {
			$ids[] = (int) $entry['id'];
		}
	}
	return array(
		'styles' => $styles,
		'count'  => count( $styles ),
		'ids'    => $ids,
	);
}

/**
 * @param array $input
 * @return array|WP_Error
 */
function us_mcp_ability_set_button_styles( $input ) {
	if ( ! function_exists( 'usof_save_options' ) OR ! function_exists( 'usof_load_options_once' ) ) {
		return new WP_Error(
			'us_mcp_button_styles_core_not_loaded',
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

	$result = us_mcp_button_styles_apply_to_options( $input, $updated_options );
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
