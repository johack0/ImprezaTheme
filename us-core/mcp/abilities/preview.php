<?php
/**
 * UpSolution MCP — Theme Options preview drafts.
 *
 * Two abilities for building and tearing down public-bearer preview links
 * that show the site with a draft set of Theme Options applied, without
 * persisting anything to the live `usof_options_<theme>` row:
 *
 *   upsolution-create-preview  — validate the supplied palette / typography /
 *                                button-styles / field-styles changes against
 *                                the same rules the corresponding set-* tools
 *                                enforce, build a full snapshot
 *                                of the current Theme Options (minus the
 *                                credentials matched by
 *                                us_mcp_denied_option_patterns()), overlay the
 *                                changes onto the snapshot, persist as a
 *                                WP transient `us_mcp_preview_<key>`, and
 *                                return a public URL whose query string
 *                                activates the preview.
 *   upsolution-delete-preview  — drop the transient. Idempotent.
 *
 * Frontend activation logic lives in mcp/preview-runtime.php — it
 * detects ?us_theme_options_preview=<key> on any URL, overlays the snapshot
 * via the usof_load_options_once filter, emits a regenerated <style> block
 * in <head> so the cached us-assets/style_theme.css doesn't dominate, sends
 * no-cache headers, and renders a floating "Exit preview" banner.
 *
 * Security model: the key is a bearer token. Anyone with the URL sees the
 * preview — no login check. This is deliberate so an agent can hand a link
 * to a client who has no account.
 *
 * Denylist: keys matching us_mcp_denied_option_patterns() (credentials —
 * api_key / secret / access_token / password / recaptcha_*) are NEVER
 * stored in the transient. On preview render they fall through to live
 * values so site functionality (Google Maps, reCAPTCHA, etc.) doesn't
 * break.
 *
 * @package usCore\Mcp
 */

defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

// Permission gate is the shared us_mcp_theme_option_permission_callback()
// from abilities/theme-option.php — a preview is effectively a draft of
// the same write that set-palette / set-typography / set-button-styles
// would do, so it shares the edit_theme_options gate.

/**
 * Default TTL for a new preview if the agent omits it (6 hours).
 */
const US_MCP_PREVIEW_DEFAULT_TTL = 6 * HOUR_IN_SECONDS;

/**
 * Hard ceiling on TTL so a forgotten draft can't sit in the options table
 * indefinitely (24 hours).
 */
const US_MCP_PREVIEW_MAX_TTL = DAY_IN_SECONDS;

/**
 * Hard floor on TTL — anything shorter than a minute is unusable in
 * practice and almost certainly an input mistake.
 */
const US_MCP_PREVIEW_MIN_TTL = 60;

/**
 * Build a snapshot of $usof_options suitable for storing in a transient:
 * everything except the keys matched by us_mcp_denied_option_patterns().
 * Credentials are never sent through the transient cache layer.
 *
 * @param array $options
 * @return array
 */
function us_mcp_preview_build_denylist_filtered_snapshot( array $options ) {
	if ( ! function_exists( 'us_mcp_option_is_denied' ) ) {
		// theme-option.php declares both the denylist and the matcher; if it
		// hasn't loaded, fail closed — we'd rather refuse to build a snapshot
		// than risk leaking a credential.
		return array();
	}
	$out = array();
	foreach ( $options as $key => $value ) {
		if ( us_mcp_option_is_denied( (string) $key ) ) {
			continue;
		}
		$out[ $key ] = $value;
	}
	return $out;
}

add_action( 'wp_abilities_api_init', function () {
	wp_register_ability( 'upsolution/create-preview', array(
		'label'               => 'Create a public preview link for draft Theme Options changes',
		'description'         => 'Build a shareable preview URL that renders the site with a draft set of Theme Options applied — without touching the live values. Accepts the same `palette` patch shape upsolution-set-palette consumes (per-section partial patch + custom_colors full-replace), a `typography` array of per-tag patches (each entry: {tag, fields, merge?} — same fields upsolution-set-typography accepts), a `button_styles` operations list (same {operations:[{op,...}]} shape upsolution-set-button-styles accepts — add / update / delete / reorder applied to the buttons list), a `field_styles` operations list (same shape upsolution-set-field-styles accepts — applied to the Field Styles list), and a `site_layout` patch ({fields:{key:value}}, same payload upsolution-set-site-layout accepts — Site Layout / Pages Layout / Archives Layout settings). Values are validated by the same rules the corresponding set-* tools enforce. Credential-like options (api_key / secret / access_token / password / recaptcha_* and similar) are stripped from the preview snapshot by a deny-list and never reach visitors. Returns a public URL of the form `https://<site>/?us_theme_options_preview=<key>` that expires after the chosen TTL (default 6h, min 60s, max 24h). Visitors with the URL see the draft styling without logging in; internal navigation auto-carries the key. Pass an optional `label` to surface in the preview banner so reviewers can tell concurrent previews apart. Remember the returned key — there is no list-previews tool. To iterate, call create-preview again and share the new URL (or delete-preview the old key first).',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'                 => 'object',
			'additionalProperties' => FALSE,
			'properties'           => array(
				'palette' => array(
					'type'                 => 'object',
					'description'          => 'Optional palette patch — same input shape as upsolution-set-palette (header / alternate_header / content / alternate_content / footer / alternate_footer / custom_colors). Omit to leave palette unchanged from the live values.',
					'additionalProperties' => TRUE,
				),
				'typography' => array(
					'type'        => 'array',
					'description' => 'Optional list of typography patches, one entry per tag. Each entry takes the same shape upsolution-set-typography accepts (tag / fields / merge). Sending two entries with the same tag is rejected.',
					'items'       => array(
						'type'       => 'object',
						'required'   => array( 'tag', 'fields' ),
						'properties' => array(
							'tag'    => array( 'type' => 'string', 'description' => 'Typography tag: body / h1 / h2 / h3 / h4 / h5 / h6.' ),
							'fields' => array( 'type' => 'object', 'description' => 'Map of field name → new value. Same schema as upsolution-set-typography.', 'additionalProperties' => TRUE ),
							'merge'  => array( 'type' => 'boolean', 'description' => 'true (default): partial patch. false: replace the tag dict (palette-owned color / color_override keys are preserved).', 'default' => TRUE ),
						),
					),
				),
				'button_styles' => array(
					'type'                 => 'object',
					'description'          => 'Optional button-styles draft — same {operations:[{op,...}]} payload upsolution-set-button-styles accepts. Operations apply atomically to the preview snapshot; the same two hard rules hold (ids are immutable, the list cannot become empty). Omit to leave button styles unchanged from the live values.',
					'required'             => array( 'operations' ),
					'additionalProperties' => TRUE,
					'properties'           => array(
						'operations' => array(
							'type'        => 'array',
							'minItems'    => 1,
							'description' => 'See upsolution-set-button-styles for per-op shapes (add / update / delete / reorder).',
							'items'       => array( 'type' => 'object', 'required' => array( 'op' ), 'additionalProperties' => TRUE ),
						),
					),
				),
				'field_styles' => array(
					'type'                 => 'object',
					'description'          => 'Optional field-styles draft — same {operations:[{op,...}]} payload upsolution-set-field-styles accepts. Operations apply atomically to the preview snapshot; the same hard rules hold (ids are immutable, the list cannot become empty, the first entry is the site-wide default). Omit to leave field styles unchanged from the live values.',
					'required'             => array( 'operations' ),
					'additionalProperties' => TRUE,
					'properties'           => array(
						'operations' => array(
							'type'        => 'array',
							'minItems'    => 1,
							'description' => 'See upsolution-set-field-styles for per-op shapes (add / update / delete / reorder).',
							'items'       => array( 'type' => 'object', 'required' => array( 'op' ), 'additionalProperties' => TRUE ),
						),
					),
				),
				'site_layout' => array(
					'type'                 => 'object',
					'description'          => 'Optional Site Layout / Pages Layout / Archives Layout draft — same {fields:{key:value}} payload upsolution-set-site-layout accepts. Field values are validated by the same rules. Omit to leave layout settings unchanged from the live values.',
					'required'             => array( 'fields' ),
					'additionalProperties' => FALSE,
					'properties'           => array(
						'fields' => array(
							'type'                 => 'object',
							'description'          => 'Map of layout option-key → new value — see upsolution-set-site-layout / upsolution-get-site-layout.',
							'additionalProperties' => TRUE,
						),
					),
				),
				'ttl_seconds' => array(
					'type'        => 'integer',
					'description' => sprintf(
						'Lifetime of the preview link in seconds. Default %d (%dh), minimum %d, maximum %d (%dh).',
						US_MCP_PREVIEW_DEFAULT_TTL,
						US_MCP_PREVIEW_DEFAULT_TTL / HOUR_IN_SECONDS,
						US_MCP_PREVIEW_MIN_TTL,
						US_MCP_PREVIEW_MAX_TTL,
						US_MCP_PREVIEW_MAX_TTL / HOUR_IN_SECONDS
					),
					'minimum'     => US_MCP_PREVIEW_MIN_TTL,
					'maximum'     => US_MCP_PREVIEW_MAX_TTL,
					'default'     => US_MCP_PREVIEW_DEFAULT_TTL,
				),
				'label' => array(
					'type'        => 'string',
					'description' => 'Optional short label rendered in the preview banner ("Brand v2", "Dark mode trial", …). Maximum 60 chars.',
					'maxLength'   => 60,
				),
			),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'required'   => array( 'key', 'url', 'expires_at', 'ttl_seconds', 'applied' ),
			'properties' => array(
				'key'         => array( 'type' => 'string', 'description' => '32-char hex key — the URL query var value. Pass it to upsolution-delete-preview to revoke the link.' ),
				'url'         => array( 'type' => 'string', 'description' => 'Public preview URL — append the same query var (?us_theme_options_preview=<key>) to ANY URL on the site to land directly there in preview mode.' ),
				'expires_at'  => array( 'type' => 'integer', 'description' => 'Unix epoch seconds. Banner countdown ticks against this.' ),
				'ttl_seconds' => array( 'type' => 'integer', 'description' => 'Effective TTL (after clamping to min / max).' ),
				'applied'     => array(
					'type'        => 'object',
					'description' => 'Same per-section applied / before / after structure the corresponding set-* tools return — agent introspection aid.',
					'properties'  => array(
						'palette'       => array( 'type' => 'object', 'description' => 'Palette helper result (applied / before / after).' ),
						'typography'    => array( 'type' => 'array', 'description' => 'Per-tag helper results.', 'items' => array( 'type' => 'object' ) ),
						'button_styles' => array( 'type' => 'object', 'description' => 'Button-styles helper result (applied / before / after — full list snapshots).' ),
						'field_styles'  => array( 'type' => 'object', 'description' => 'Field-styles helper result (applied / before / after — full list snapshots).' ),
						'site_layout'   => array( 'type' => 'object', 'description' => 'Site-layout helper result (applied / before / after).' ),
					),
				),
			),
		),
		'execute_callback'    => 'us_mcp_ability_create_preview',
		'permission_callback' => 'us_mcp_theme_option_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );

	wp_register_ability( 'upsolution/delete-preview', array(
		'label'               => 'Delete a Theme Options preview by key',
		'description'         => 'Drop the transient backing a preview key. Idempotent — deleting an already-missing key returns `existed: false, deleted: true`. After deletion, the URL stops working and any visitor on it sees the live site.',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'                 => 'object',
			'required'             => array( 'key' ),
			'additionalProperties' => FALSE,
			'properties'           => array(
				'key' => array(
					'type'        => 'string',
					'description' => '32-char hex key as returned by upsolution-create-preview.',
					'pattern'     => '^[a-f0-9]{32}$',
				),
			),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'required'   => array( 'key', 'existed', 'deleted' ),
			'properties' => array(
				'key'     => array( 'type' => 'string' ),
				'existed' => array( 'type' => 'boolean', 'description' => 'TRUE if the transient existed at deletion time.' ),
				'deleted' => array( 'type' => 'boolean', 'description' => 'TRUE if the post-condition is "key no longer exists" (always TRUE for valid keys, even when existed=false).' ),
			),
		),
		'execute_callback'    => 'us_mcp_ability_delete_preview',
		'permission_callback' => 'us_mcp_theme_option_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );
} );

/**
 * @param array $input
 * @return array|WP_Error
 */
function us_mcp_ability_create_preview( $input ) {
	$input = (array) $input;

	// NB: parentheses are NOT optional here — `=` binds tighter than `AND`,
	// so without them `$x = isset(...) AND is_array(...) ? a : b` evaluates
	// as `($x = isset(...)) AND (...)` and $x silently becomes a boolean,
	// breaking every downstream is_array / array_key_exists check.
	$palette_in       = ( isset( $input['palette'] )       && is_array( $input['palette'] )       ) ? $input['palette']       : NULL;
	$typography_in    = ( isset( $input['typography'] )    && is_array( $input['typography'] )    ) ? $input['typography']    : NULL;
	$button_styles_in = ( isset( $input['button_styles'] ) && is_array( $input['button_styles'] ) ) ? $input['button_styles'] : NULL;
	$field_styles_in  = ( isset( $input['field_styles'] )  && is_array( $input['field_styles'] )  ) ? $input['field_styles']  : NULL;
	$site_layout_in   = ( isset( $input['site_layout'] )   && is_array( $input['site_layout'] )   ) ? $input['site_layout']   : NULL;
	$ttl_seconds      = isset( $input['ttl_seconds'] ) ? (int) $input['ttl_seconds'] : US_MCP_PREVIEW_DEFAULT_TTL;
	$label            = isset( $input['label'] ) ? trim( (string) $input['label'] ) : '';

	if ( $palette_in === NULL AND $typography_in === NULL AND $button_styles_in === NULL AND $field_styles_in === NULL AND $site_layout_in === NULL ) {
		return new WP_Error(
			'us_mcp_preview_no_op',
			'Pass at least one of `palette` / `typography` / `button_styles` / `field_styles` / `site_layout` — an empty preview would just mirror the live site.',
			array( 'status' => 400 )
		);
	}

	if ( $ttl_seconds < US_MCP_PREVIEW_MIN_TTL ) {
		$ttl_seconds = US_MCP_PREVIEW_MIN_TTL;
	} elseif ( $ttl_seconds > US_MCP_PREVIEW_MAX_TTL ) {
		$ttl_seconds = US_MCP_PREVIEW_MAX_TTL;
	}

	if ( strlen( $label ) > 60 ) {
		$label = substr( $label, 0, 60 );
	}

	if ( ! function_exists( 'usof_load_options_once' ) ) {
		return new WP_Error(
			'us_mcp_preview_core_not_loaded',
			'usof_load_options_once() is not available — us-core usof loader did not run.',
			array( 'status' => 503 )
		);
	}

	global $usof_options;
	usof_load_options_once();
	if ( ! is_array( $usof_options ) ) {
		$usof_options = array();
	}

	// Start the snapshot from the live options, minus anything that looks
	// like a credential. Credentials are intentionally NEVER sent through the
	// transient layer — the preview-runtime filter leaves them on their live
	// values when rendering.
	$snapshot = us_mcp_preview_build_denylist_filtered_snapshot( $usof_options );

	// Build `applied` lazily — keys only show up for sections the agent
	// actually patched. This matches the output schema (which lists palette
	// / typography as optional under applied) and avoids returning
	// `palette: null` for a typography-only preview, which would fail the
	// `type: object` constraint on the palette sub-key.
	$applied = array();

	// Apply palette patch via the shared helper. The helper validates the
	// input AND mutates $snapshot in place; on a bad input we abort and
	// nothing has been persisted.
	if ( is_array( $palette_in ) && ! empty( $palette_in ) ) {
		if ( ! function_exists( 'us_mcp_palette_apply_to_options' ) ) {
			return new WP_Error(
				'us_mcp_preview_palette_unavailable',
				'us_mcp_palette_apply_to_options() is not available — abilities/color-palette.php did not load.',
				array( 'status' => 503 )
			);
		}
		$result = us_mcp_palette_apply_to_options( $palette_in, $snapshot );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$applied['palette'] = $result;
	}

	// Apply each typography patch in turn. Order matters when an agent
	// passes interacting tags (e.g. body before h1), so we honour the
	// supplied list order. Duplicate tags within one call are rejected to
	// keep the audit shape stable.
	if ( is_array( $typography_in ) && ! empty( $typography_in ) ) {
		if ( ! function_exists( 'us_mcp_typography_apply_to_options' ) ) {
			return new WP_Error(
				'us_mcp_preview_typography_unavailable',
				'us_mcp_typography_apply_to_options() is not available — abilities/typography.php did not load.',
				array( 'status' => 503 )
			);
		}
		$seen_tags = array();
		foreach ( $typography_in as $idx => $entry ) {
			if ( ! is_array( $entry ) ) {
				return new WP_Error(
					'us_mcp_preview_bad_typography_entry',
					sprintf( 'typography[%d] must be an object with `tag` / `fields` / optional `merge`.', $idx ),
					array( 'status' => 400 )
				);
			}
			$tag    = isset( $entry['tag'] )    ? (string) $entry['tag']    : '';
			$fields = isset( $entry['fields'] ) ? $entry['fields']           : NULL;
			$merge  = array_key_exists( 'merge', $entry ) ? (bool) $entry['merge'] : TRUE;
			// Validate the tag value BEFORE dedup so a more specific
			// "unknown tag" error wins over the generic duplicate one in
			// the (degenerate) case of a malformed tag repeated twice.
			// us_mcp_typography_apply_to_options would catch this too, but
			// surfacing it here lets the dedup map stay clean (we never
			// record a known-bad tag as "seen").
			if ( ! in_array( $tag, US_TYPOGRAPHY_TAGS, TRUE ) ) {
				return new WP_Error(
					'us_mcp_preview_unknown_typography_tag',
					sprintf( 'typography[%d]: unknown tag "%s". Allowed: %s.', $idx, $tag, implode( ', ', US_TYPOGRAPHY_TAGS ) ),
					array( 'status' => 400 )
				);
			}
			if ( isset( $seen_tags[ $tag ] ) ) {
				return new WP_Error(
					'us_mcp_preview_duplicate_typography_tag',
					sprintf( 'typography[%d]: tag "%s" already appears in this call — combine the field maps into a single entry.', $idx, $tag ),
					array( 'status' => 400 )
				);
			}
			$seen_tags[ $tag ] = TRUE;

			$result = us_mcp_typography_apply_to_options( $tag, $fields, $merge, $snapshot );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$applied['typography'][] = $result;
		}
	}

	// Apply button-styles operations via the shared helper. Same atomic
	// semantics as set-button-styles: a single invalid op aborts the whole
	// preview and nothing has been persisted.
	if ( is_array( $button_styles_in ) && ! empty( $button_styles_in ) ) {
		if ( ! function_exists( 'us_mcp_button_styles_apply_to_options' ) ) {
			return new WP_Error(
				'us_mcp_preview_button_styles_unavailable',
				'us_mcp_button_styles_apply_to_options() is not available — abilities/button-styles.php did not load.',
				array( 'status' => 503 )
			);
		}
		$result = us_mcp_button_styles_apply_to_options( $button_styles_in, $snapshot );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$applied['button_styles'] = $result;
	}

	// Apply field-styles operations via the shared helper. Same atomic
	// semantics as set-field-styles: a single invalid op aborts the whole
	// preview and nothing has been persisted.
	if ( is_array( $field_styles_in ) && ! empty( $field_styles_in ) ) {
		if ( ! function_exists( 'us_mcp_field_styles_apply_to_options' ) ) {
			return new WP_Error(
				'us_mcp_preview_field_styles_unavailable',
				'us_mcp_field_styles_apply_to_options() is not available — abilities/field-styles.php did not load.',
				array( 'status' => 503 )
			);
		}
		$result = us_mcp_field_styles_apply_to_options( $field_styles_in, $snapshot );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$applied['field_styles'] = $result;
	}

	// Apply the site-layout patch via the shared helper. Same all-or-nothing
	// semantics as set-site-layout: a single invalid field aborts the whole
	// preview and nothing has been persisted.
	if ( is_array( $site_layout_in ) && ! empty( $site_layout_in ) ) {
		if ( ! function_exists( 'us_mcp_site_layout_apply_to_options' ) ) {
			return new WP_Error(
				'us_mcp_preview_site_layout_unavailable',
				'us_mcp_site_layout_apply_to_options() is not available — abilities/site-layout.php did not load.',
				array( 'status' => 503 )
			);
		}
		$site_layout_fields = ( isset( $site_layout_in['fields'] ) && is_array( $site_layout_in['fields'] ) )
			? $site_layout_in['fields']
			: array();
		$result = us_mcp_site_layout_apply_to_options( $site_layout_fields, $snapshot );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$applied['site_layout'] = $result;
	}

	// Generate a fresh key. wp_generate_password is cryptographically random
	// when available_chars are limited; bin2hex(random_bytes) is the cleanest
	// equivalent and matches our key regex on the runtime side.
	try {
		$key = bin2hex( random_bytes( 16 ) );
	} catch ( \Throwable $e ) {
		return new WP_Error(
			'us_mcp_preview_rng_unavailable',
			'Failed to generate a secure preview key — random_bytes() threw.',
			array( 'status' => 500 )
		);
	}

	$expires_at = time() + $ttl_seconds;
	$payload = array(
		'snapshot'   => $snapshot,
		'created_at' => time(),
		'expires_at' => $expires_at,
		'label'      => $label,
	);

	$stored = set_transient( US_MCP_PREVIEW_TRANSIENT_PREFIX . $key, $payload, $ttl_seconds );
	if ( $stored === FALSE ) {
		return new WP_Error(
			'us_mcp_preview_store_failed',
			'set_transient() returned FALSE — the preview snapshot was not persisted.',
			array( 'status' => 500 )
		);
	}

	$url = add_query_arg( US_MCP_PREVIEW_QUERY_VAR, $key, home_url( '/' ) );

	return array(
		'key'         => $key,
		'url'         => $url,
		'expires_at'  => $expires_at,
		'ttl_seconds' => $ttl_seconds,
		'applied'     => $applied,
	);
}

/**
 * @param array $input
 * @return array|WP_Error
 */
function us_mcp_ability_delete_preview( $input ) {
	$input = (array) $input;
	$key = isset( $input['key'] ) ? trim( (string) $input['key'] ) : '';

	if ( ! preg_match( US_MCP_PREVIEW_KEY_REGEX, $key ) ) {
		return new WP_Error(
			'us_mcp_preview_bad_key',
			'Pass a 32-char lowercase hex key as returned by upsolution-create-preview.',
			array( 'status' => 400 )
		);
	}

	$existed = ( get_transient( US_MCP_PREVIEW_TRANSIENT_PREFIX . $key ) !== FALSE );
	delete_transient( US_MCP_PREVIEW_TRANSIENT_PREFIX . $key );

	return array(
		'key'     => $key,
		'existed' => $existed,
		'deleted' => TRUE,
	);
}
