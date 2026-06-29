<?php
/**
 * UpSolution MCP — generic Theme Options reader.
 *
 * Single ability `upsolution-get-theme-option`. Thin wrapper over
 * us_get_option() so an agent can fetch any value an admin has set in Theme
 * Options — most usefully:
 *
 *   - `buttons`               array of button styles; the `id` of each entry
 *                             is what you pass in markup as `us_btn style="N"`,
 *                             `us_cta btn_style="N"`, etc.
 *   - `input_fields`          array of field styles; the `id` is used as
 *                             `us_field_style="N"` on us_cform / us_login.
 *   - `color_content_primary` (and the 7 siblings: _secondary, _heading,
 *                             _text, _faded, _border, _bg, _bg_alt) — each
 *                             returns the hex/rgba value behind the
 *                             `_content_*` family of dynamic-value tokens
 *                             used in shortcode attributes
 *                             (e.g. `color="_content_primary"`).
 *
 * Denylist: option names matching any pattern from
 * us_mcp_denied_option_patterns() return 403. Adjust that list when
 * sensitive Theme Options fields are added to us-core.
 *
 * Out of scope: `sidebars` (lives in $wp_registered_sidebars, not us_get_option)
 * and custom `color_schemes` (lives in get_option('usof_style_schemes_<theme>')).
 * Add dedicated abilities if those become needed.
 *
 * @package usCore\Mcp
 */

defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Wildcard-glob patterns matching Theme Option names that are NOT readable
 * via MCP. The agent gets HTTP 403 for any matching name. `*` is the only
 * wildcard recognised (via PHP's fnmatch()).
 *
 * @return string[]
 */
function us_mcp_denied_option_patterns() {
	return array(
		// Specific known sensitive fields in us-core's Theme Options:
		'mcp_enabled',
		'maintenance_private_key',
		'gmaps_api_key',
		'facebook_app_id',
		'reCAPTCHA_*',

		// Forward-compatible patterns — anything that looks like a credential
		// is blocked even if we haven't audited it yet:
		'*_key',
		'*_token',
		'*_secret',
		'*_password',
	);
}

/**
 * @param string $name
 * @return bool
 */
function us_mcp_option_is_denied( $name ) {
	foreach ( us_mcp_denied_option_patterns() as $pattern ) {
		if ( fnmatch( $pattern, $name, FNM_CASEFOLD ) ) {
			return TRUE;
		}
	}
	return FALSE;
}

/**
 * Permission gate for every ability that reads or writes Theme Options.
 *
 * Same cap wp-admin uses for the Theme Options / Customizer screens —
 * `edit_posts` from us_mcp_permission_callback would let any author or
 * editor touch site-wide configuration that the admin UI itself only
 * exposes to manage_options-equivalent roles.
 *
 * Shared by:
 *   - upsolution-get-theme-option              (this file)
 *   - upsolution-list-fonts / set-typography   (typography.php)
 *   - upsolution-get-palette / set-palette     (color-palette.php)
 *   - upsolution-list-button-styles / set-button-styles (button-styles.php)
 *   - upsolution-create-preview / delete-preview (preview.php — the snapshot
 *     touches the same three Theme Options the set-* abilities patch).
 *
 * If any one ability ever needs a tighter / different cap, split it back
 * out into a dedicated callback — until then, single source of truth.
 *
 * @return bool
 */
function us_mcp_theme_option_permission_callback() {
	return current_user_can( 'edit_theme_options' );
}

add_action( 'wp_abilities_api_init', function () {
	wp_register_ability( 'upsolution/get-theme-option', array(
		'label'               => 'Read a Theme Options value',
		'description'         => 'Read one value from this site\'s Theme Options via us_get_option(). Common names: "buttons" (button style ids for us_btn style="N" / us_cta btn_style="N" / etc.); "input_fields" (field style ids for us_field_style="N" on us_cform / us_login); "color_content_primary" / "color_content_secondary" / "color_content_heading" / "color_content_text" / "color_content_faded" / "color_content_border" / "color_content_bg" / "color_content_bg_alt" (hex / rgba values behind the _content_* dynamic-value tokens used in shortcode attributes). Returns {name, value, found}. Names matching a credential pattern (api_key / secret / access_token / password / recaptcha_*) are blocked with 403.',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'       => 'object',
			'required'   => array( 'name' ),
			'properties' => array(
				'name' => array(
					'type'        => 'string',
					'description' => 'Theme Options key as known to us_get_option() (e.g. "buttons", "input_fields", "color_content_primary").',
					'minLength'   => 1,
				),
			),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'required'   => array( 'name', 'value', 'found' ),
			'properties' => array(
				'name'  => array( 'type' => 'string' ),
				// `value` intentionally has no type — it can be scalar, array
				// or null depending on the option. JSON Schema empty object
				// means "any value is allowed".
				'value' => array( 'description' => 'Raw value stored under that key (scalar / array / null).' ),
				'found' => array( 'type' => 'boolean', 'description' => 'False when us_get_option returned null (key never set or unknown).' ),
			),
		),
		'execute_callback'    => 'us_mcp_ability_get_theme_option',
		'permission_callback' => 'us_mcp_theme_option_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );
} );

/**
 * @param array $input
 * @return array|WP_Error
 */
function us_mcp_ability_get_theme_option( $input ) {
	$input = (array) $input;
	$name = isset( $input['name'] ) ? trim( (string) $input['name'] ) : '';

	if ( $name === '' ) {
		return new WP_Error(
			'us_mcp_theme_option_missing_name',
			'Provide a `name` argument (e.g. "buttons", "color_content_primary").',
			array( 'status' => 400 )
		);
	}

	if ( us_mcp_option_is_denied( $name ) ) {
		return new WP_Error(
			'us_mcp_theme_option_denied',
			sprintf( 'Reading the "%s" theme option is not permitted via MCP.', $name ),
			array( 'status' => 403 )
		);
	}

	if ( ! function_exists( 'us_get_option' ) ) {
		return new WP_Error(
			'us_mcp_theme_option_core_not_loaded',
			'us_get_option() is not available — us-core helpers did not load.',
			array( 'status' => 503 )
		);
	}

	// `usof_get_option` returns an empty string '' for keys that were never
	// set, which is indistinguishable from a deliberately empty value via
	// null-check alone. Pass a sentinel that the option storage cannot return
	// to detect "key truly absent".
	$sentinel = new \stdClass();
	$value = us_get_option( $name, $sentinel );
	$found = ! ( $value instanceof \stdClass );
	if ( ! $found ) {
		$value = NULL;
	}

	return array(
		'name'  => $name,
		'value' => $value,
		'found' => $found,
	);
}
