<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Theme Options > Advanced
 */

global $usof_options;

if ( ! empty( $usof_options['portfolio_rename'] ) ) {
	$renamed_portfolio_label = ' (' . strip_tags( $usof_options['portfolio_label_name'] ) . ')';
} else {
	$renamed_portfolio_label = '';
}

$public_post_types = us_get_public_post_types();

$user_roles_can_edit_posts = array();

require_once ABSPATH . 'wp-admin/includes/user.php'; // required for correct work of get_editable_roles()

// Check if the get_editable_roles function exists for AJAX calls of other plugins compatibility
$editable_roles = ( function_exists( 'get_editable_roles' ) ) ? get_editable_roles() : array();

foreach ( $editable_roles as $role_key => $role ) {
	$caps = $role['capabilities'] ?? array();
	if ( ! empty( $caps['edit_posts'] ) ) {
		$user_roles_can_edit_posts[ $role_key ] = translate_user_role( $role['name'] );
	}
}

$live_builder_post_types = array_merge(
	$public_post_types,
	array(
		'us_content_template' => __( 'Page Templates', 'us' ),
		'us_page_block' => __( 'Reusable Blocks', 'us' ),
	)
);

// Get CSS & JS assets
$usof_assets = $usof_assets_std = array();

foreach ( us_config( 'assets', array() ) as $component => $component_atts ) {

	// Skip assets without title
	if ( empty( $component_atts['title'] ) ) {
		continue;
	}

	$usof_assets[ $component ] = array(
		'title' => $component_atts['title'],
		'group' => $component_atts['group'] ?? NULL,
	);

	$usof_assets_std[ $component ] = 1;

	// Count files sizes for admin area only
	if ( is_admin() ) {
		if ( isset( $component_atts['css'] ) ) {
			$usof_assets[ $component ]['css_size'] = file_exists( $us_template_directory . $component_atts['css'] )
				? number_format_i18n( filesize( $us_template_directory . $component_atts['css'] ) / 1024 * 0.8, 1 )
				: NULL;
		}
		if ( isset( $component_atts['js'] ) ) {
			$js_filename = str_replace( '.js', '.min.js', $us_template_directory . $component_atts['js'] );
			$usof_assets[ $component ]['js_size'] = file_exists( $js_filename )
				? number_format_i18n( filesize( $js_filename ) / 1024, 1 )
				: NULL;
		}
	}

}

// Check if "uploads" directory is writable
$upload_dir = wp_get_upload_dir();
$upload_dir_not_writable = wp_is_writable( $upload_dir['basedir'] ) ? FALSE : TRUE;

// AI Assistant (MCP server) — version gate + connection instructions.
// This config is `require`d by `us_config('theme-options')` from many
// non-render contexts (frontend `usof_get_option` fallback lookups, the
// usof save-AJAX, etc.). Only `$mcp_wp_not_supported` is referenced by the
// always-present field array; the snippet HTML / JSON / wp_get_current_user
// call cost is paid only when actually rendering the Theme Options page.
global $wp_version, $pagenow;
$mcp_wp_not_supported = version_compare( $wp_version, '7.0', '<' );

$mcp_connect_instructions = '';

if (
	is_admin()
	AND $pagenow === 'admin.php'
	AND ( $_GET['page'] ?? '' ) === 'us-theme-options'
) {
	$mcp_endpoint_url   = rest_url( 'upsolution/v1/mcp' );
	$mcp_endpoint_parts = (array) parse_url( $mcp_endpoint_url ); // one parse, reused
	$mcp_endpoint_host  = strtolower( (string) ( $mcp_endpoint_parts['host'] ?? '' ) );

	// On a local-dev host (loopback / private LAN / *.local / *.test):
	//   - HTTPS uses a self-signed cert that node-based MCP clients won't
	//     trust, so the snippets use plain http;
	//   - the stdio proxy (mcp-remote) needs --allow-http to talk to a
	//     non-localhost http URL, so we append it to the stdio args.
	$mcp_is_local = (
		$mcp_endpoint_host === 'localhost'
		OR preg_match( '/\.(local|test|localhost)$/', $mcp_endpoint_host )
		OR preg_match( '/^(127\.|192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $mcp_endpoint_host )
	);
	$mcp_snippet_endpoint = ( $mcp_is_local AND ( $mcp_endpoint_parts['scheme'] ?? '' ) === 'https' )
		? 'http://' . $mcp_endpoint_host . ( $mcp_endpoint_parts['path'] ?? '' )
		: $mcp_endpoint_url;

	// Derive the JSON key in mcpServers from the hostname: strip "www.",
	// collapse any non-alphanumeric run to "-", trim, cap length.
	$mcp_server_name = preg_replace(
		array( '/^www\./', '/[^a-z0-9]+/' ),
		array( '', '-' ),
		$mcp_endpoint_host
	);
	$mcp_server_name = trim( $mcp_server_name, '-' );
	if ( strlen( $mcp_server_name ) > 24 ) {
		$mcp_server_name = rtrim( substr( $mcp_server_name, 0, 24 ), '-' );
	}
	if ( $mcp_server_name === '' ) {
		$mcp_server_name = 'us-site';
	}

	// Snippet templates — per-server entry only (no outer mcpServers
	// wrapper) so the user pastes them under their existing config. JS
	// swaps __AUTH__ for the base64 Basic header on input.
	$mcp_http_tpl = '"' . $mcp_server_name . '": ' . json_encode( array(
		'url'     => $mcp_snippet_endpoint,
		'headers' => array( 'Authorization' => '__AUTH__' ),
	), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

	$mcp_stdio_args = array( '-y', 'mcp-remote', $mcp_snippet_endpoint, '--header', 'Authorization: __AUTH__' );
	if ( $mcp_is_local ) {
		$mcp_stdio_args[] = '--allow-http';
	}
	$mcp_stdio_tpl = '"' . $mcp_server_name . '": ' . json_encode( array(
		'command' => 'npx',
		'args'    => $mcp_stdio_args,
	), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

	$mcp_login        = wp_get_current_user()->user_login;
	$mcp_profile_link = admin_url( 'profile.php#application-passwords-section' );
	$mcp_help_link = US_HELP_PORTAL_URL . '/' . strtolower( US_THEMENAME ) . '/mcp-server/#how-to-connect-your-ai-application-to-mcp';

	ob_start();
	?>
	<ol>
		<li>
			<?php
			printf(
				__( 'Open your %s section and create a password with any name like "MCP Agent".', 'us' ),
				'<a href="' . esc_url( $mcp_profile_link ) . '" target="_blank"><strong>' . us_translate( 'Application Passwords' ) . '</strong></a>'
			);
			?>
		</li>
		<li>
			<?php esc_html_e( 'Copy the generated value and paste it here:', 'us' ); ?>
			<input type="text" id="us-mcp-pw" autocomplete="off" spellcheck="false"
			       placeholder="xxxx xxxx xxxx xxxx xxxx xxxx"
			       style="display:block;background:#fff;max-width:260px;margin:6px 0;font-family:monospace;" />
		</li>
		<li id="us-mcp-step3" style="display:none;">
			<?php esc_html_e( 'Pick the snippet that matches your MCP client and merge it into the client config.', 'us' ); ?>
			<a href="<?= esc_url( $mcp_help_link ) ?>" target="_blank"><?= __( 'Learn more', 'us' ) ?></a>
			<h4 style="margin:10px 0 0;"><?= ( 'A. HTTP transport — Cursor, Claude Code CLI, MCP Inspector' ); ?></h4>
			<pre id="us-mcp-snippet-http" style="font-size:12px;margin:6px 0;padding:8px;background:rgba(0,0,0,.05);border:1px solid rgba(0,0,0,.1);"></pre>
			<h4 style="margin:10px 0 0;"><?= ( 'B. Stdio transport — Claude Desktop' ); ?></h4>
			<pre id="us-mcp-snippet-stdio" style="font-size:12px;margin:6px 0;padding:8px;background:rgba(0,0,0,.05);border:1px solid rgba(0,0,0,.1);"></pre>
		</li>
	</ol>
	<script>
	(function () {
		var login     = <?= wp_json_encode( $mcp_login ); ?>;
		var tplHttp   = <?= wp_json_encode( $mcp_http_tpl ); ?>;
		var tplStdio  = <?= wp_json_encode( $mcp_stdio_tpl ); ?>;
		var pwInput   = document.getElementById( 'us-mcp-pw' );
		var step3     = document.getElementById( 'us-mcp-step3' );
		var snipHttp  = document.getElementById( 'us-mcp-snippet-http' );
		var snipStdio = document.getElementById( 'us-mcp-snippet-stdio' );
		if ( ! pwInput || ! step3 || ! snipHttp || ! snipStdio ) return;
		function update() {
			var v = pwInput.value.trim();
			if ( ! v ) {
				step3.style.display = 'none';
				return;
			}
			var enc = btoa( unescape( encodeURIComponent( login + ':' + v ) ) );
			snipHttp.textContent  = tplHttp.replace( '__AUTH__', 'Basic ' + enc );
			snipStdio.textContent = tplStdio.replace( '__AUTH__', 'Basic ' + enc );
			step3.style.display = '';
		}
		pwInput.addEventListener( 'input', update );
	})();
	</script>
	<?php
	$mcp_connect_instructions = ob_get_clean();
}

return array(
	'title' => _x( 'Advanced', 'Advanced Settings', 'us' ),
	'fields' => array(

		// AI Assistant (MCP server)
		'h_advanced_5' => array(
			'title' => __( 'AI Assistant', 'us' ),
			'type' => 'heading',
			'classes' => 'with_separator',
		),
		'mcp_enabled' => array(
			'type' => 'switch',
			'switch_text' => __( 'Enable MCP server', 'us' ),
			'description' => __( 'Lets AI assistants like Claude or Cursor edit your website.', 'us' ) . ' <a href="' . US_HELP_PORTAL_URL . '/' . strtolower( US_THEMENAME ) . '/mcp-server/" target="_blank">' . __( 'Learn more', 'us' ) . '</a>',
			'std' => 0,
			'disabled' => $mcp_wp_not_supported,
			'classes' => 'desc_2',
		),
		'mcp_alert' => array(
			'description' => sprintf( 'Requires WordPress 7.0 or newer (you are on %s).', $wp_version ),
			'type' => 'message',
			'classes' => 'string',
			'place_if' => $mcp_wp_not_supported,
		),
		'mcp_instructions' => array(
			'type' => 'message',
			'description' => $mcp_connect_instructions,
			'classes' => 'for_above',
			'show_if' => array( 'mcp_enabled', '=', 1 ),
		),

		// Global Values
		'h_advanced_2' => array(
			'title' => __( 'Global Values', 'us' ),
			'type' => 'heading',
			'classes' => 'with_separator',
		),
		'reCAPTCHA_site_key' => array(
			'title' => __( 'reCAPTCHA Site Key', 'us' ),
			'title_pos' => 'side',
			'description' => '<a href="https://www.google.com/recaptcha/admin/" target="_blank">' . strip_tags( __( 'Get reCAPTCHA keys', 'us' ) ) . '</a>',
			'type' => 'text',
			'std' => '',
			'classes' => 'desc_3',
		),
		'reCAPTCHA_secret_key' => array(
			'title' => __( 'reCAPTCHA Secret Key', 'us' ),
			'title_pos' => 'side',
			'description' => '<a href="https://www.google.com/recaptcha/admin/" target="_blank">' . strip_tags( __( 'Get reCAPTCHA keys', 'us' ) ) . '</a>',
			'type' => 'text',
			'std' => '',
			'classes' => 'desc_3',
		),
		'reCAPTCHA_hide_badge' => array(
			'switch_text' => __( 'Hide reCAPTCHA badge', 'us' ),
			'type' => 'switch',
			'std' => '0',
			'classes' => 'for_above force_right',
			'show_if' => array( 'reCAPTCHA_secret_key', '!=', '' ),
		),
		'reCAPTCHA_policy_text' => array(
			'title' => __( 'Text in Contact Forms', 'us' ),
			'description' => __( 'This text will be shown in every contact form with reCAPTCHA enabled.', 'us' ),
			'type' => 'textarea',
			'std' => sprintf(
				'This site is protected by reCAPTCHA and the Google %s and %s apply.',
				'<a href="https://policies.google.com/privacy" target="_blank">Privacy Policy</a>',
				'<a href="https://policies.google.com/terms" target="_blank">Terms of Service</a>'
			),
			'classes' => 'for_above force_right desc_3',
			'show_if' => array(
				array( 'reCAPTCHA_hide_badge', '=', '1' ),
				'and',
				array( 'reCAPTCHA_secret_key', '!=', '' ),
			),
		),
		'gmaps_api_key' => array(
			'title' => __( 'Google Maps API Key', 'us' ),
			'title_pos' => 'side',
			'description' => '<a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">' . strip_tags( __( 'Get API key', 'us' ) ) . '</a>',
			'type' => 'text',
			'std' => '',
			'classes' => 'desc_3',
		),
		'facebook_app_id' => array(
			'title' => __( 'Facebook Application ID', 'us' ),
			'title_pos' => 'side',
			'description' => __( 'Required for Sharing Buttons on AMP version of website.', 'us' ) . ' <a href="https://developers.facebook.com/apps" target="_blank">developers.facebook.com</a>',
			'type' => 'text',
			'std' => '',
			'classes' => 'desc_3',
			'place_if' => function_exists( 'amp_is_request' ),
		),

		// Faceted Filter
		'h_advanced_4' => array(
			'title' => __( 'Faceted Filtering', 'us' ),
			'description' => sprintf( __( 'Manage a separate database table for using by the %s element.', 'us' ), __( 'List Filter', 'us' ) ) . ' <a href="' . US_HELP_PORTAL_URL . '/' . strtolower( US_THEMENAME ) . '/faceted-filter/" target="_blank">' . __( 'Learn more', 'us' ) . '</a>',
			'type' => 'heading',
			'classes' => 'with_separator',
		),
		'index_filter_panel' => array(
			'type' => 'index_filter_panel',
		),
		'enable_auto_filter_reindex' => array(
			'type' => 'switch',
			'switch_text' => __( 'Automatic re-indexing', 'us' ),
			'description' => __( 'Performs a single re-index when an individual post or term is added, edited or deleted.', 'us' ),
			'std' => 0,
			'classes' => 'desc_2',
		),
		'enable_filter_cache' => array(
			'type' => 'switch',
			'switch_text' => __( 'Enable filter caching', 'us' ),
			'description' => '<a href="' . US_HELP_PORTAL_URL . '/' . strtolower( US_THEMENAME ) . '/faceted-filter/" target="_blank">' . __( 'Learn more', 'us' ) . '</a>',
			'std' => 0,
			'classes' => 'for_above desc_2',
		),
		'filter_cache_lifetime' => array(
			'title' => __( 'Cache Lifetime', 'us' ),
			'type' => 'radio',
			'options' => array(
				'3600' => sprintf( us_translate_n( '%s hour', '%s hours', 1 ), 1 ),
				'43200' => sprintf( us_translate_n( '%s hour', '%s hours', 12 ), 12 ),
				'86400' => sprintf( us_translate_n( '%s day', '%s days', 1 ), 1 ),
				'2592000' => sprintf( us_translate_n( '%s month', '%s months', 1 ), 1 ),
				'31536000' => sprintf( us_translate_n( '%s year', '%s years', 1 ), 1 ),
				'custom' => __( 'Custom', 'us' ),
			),
			'std' => '2592000',
			'classes' => 'for_above',
			'show_if' => array( 'enable_filter_cache', '=', 1 ),
		),
		'filter_cache_lifetime_custom' => array(
			'type' => 'text',
			'description' => __( 'In seconds', 'us' ),
			'std' => '1800',
			'classes' => 'for_above',
			'show_if' => array( 'filter_cache_lifetime', '=', 'custom' ),
		),
		'filter_cache_panel' => array(
			'type' => 'filter_cache_panel',
			'classes' => 'for_above',
			'show_if' => array( 'enable_filter_cache', '=', 1 ),
		),

		// Theme Modules
		'h_advanced_1' => array(
			'title' => __( 'Theme Modules', 'us' ),
			'type' => 'heading',
			'classes' => 'with_separator',
		),
		'live_builder' => array(
			'type' => 'switch',
			'switch_text' => __( '“Live Builder”', 'us' ),
			'description' => __( 'Allows to edit website pages on the front end via green "Edit Live" button.', 'us' ) . ' <a href="https://youtu.be/lcTFtiFGZng" target="_blank">' . __( 'Learn more', 'us' ) . '</a>',
			'std' => 1,
			'classes' => 'desc_2 color_brand',
		),
		'live_builder_start' => array(
			'type' => 'wrapper_start',
			'show_if' => array( 'live_builder', '=', 1 ),
		),
		'section_templates' => array(
			'type' => 'switch',
			'switch_text' => __( 'Section Templates', 'us' ),
			'description' => __( 'Shows a categorized list of templates in the “Live Builder”.', 'us' ) . ' <a href="https://youtu.be/1eV1GesTnjs" target="_blank">' . __( 'Learn more', 'us' ) . '</a>',
			'std' => 1,
			'classes' => 'for_above desc_2',
		),
		'section_favorites' => array(
			'type' => 'switch',
			'switch_text' => _x( 'Favorites', 'Favorite Sections', 'us' ),
			'description' => _x( 'Save your favorite sections to make them quickly available on all of your websites.', 'Favorite Sections', 'us' ) . ' <a href="' . US_HELP_PORTAL_URL . '/' . strtolower( US_THEMENAME ) . '/fav-sections/" target="_blank">' . __( 'Learn more', 'us' ) . '</a>',
			'std' => 1,
			'classes' => 'for_above desc_2',
		),
		'grid_columns_layout' => array(
			'type' => 'switch',
			'switch_text' => __( 'Columns Layout via CSS grid', 'us' ),
			'description' => '<a href="' . US_HELP_PORTAL_URL . '/' . strtolower( US_THEMENAME ) . '/rows-and-columns/" target="_blank">' . __( 'Learn more', 'us' ) . '</a>',
			'std' => 1,
			'classes' => 'for_above desc_2',
		),
		'live_builder_post_types' => array(
			'title' => __( 'Post Type', 'us' ),
			'type' => 'checkboxes',
			'options' => $live_builder_post_types,
			'std' => implode( ',', array_keys( $live_builder_post_types ) ),
			'cols' => 2,
			'classes' => 'vertical',
		),
		'live_builder_user_roles' => array(
			'title' => __( 'User Role', 'us' ),
			'type' => 'checkboxes',
			'options' => $user_roles_can_edit_posts,
			'std' => implode( ',', array_keys( $user_roles_can_edit_posts ) ),
			'cols' => 2,
			'classes' => 'vertical',
		),
		'live_builder_end' => array(
			'type' => 'wrapper_end',
		),

		'full_width_direction_fields' => array(
			'type' => 'switch',
			'switch_text' => __( 'Full-width fields for directions', 'us' ),
			'description' => sprintf(
				__( 'When this option is ON, all the "%s", "%s", "%s", "%s" fields will be displayed in full width. Applies to Design settings in all builders.', 'us' ),
				us_translate( 'Top' ),
				us_translate( 'Right' ),
				us_translate( 'Bottom' ),
				us_translate( 'Left' )
			),
			'std' => 0,
			'classes' => 'desc_2',
		),
		'block_editor' => array(
			'type' => 'switch',
			'switch_text' => __( 'Gutenberg (block editor)', 'us' ),
			'std' => 0,
			'classes' => 'for_above',
		),
		'enable_sidebar_titlebar' => array(
			'type' => 'switch',
			'switch_text' => __( 'Titlebars & Sidebars', 'us' ),
			'std' => 0,
			'classes' => 'for_above',
		),
		'enable_page_blocks_for_sidebars' => array(
			'type' => 'switch',
			'switch_text' => __( 'Use Reusable Blocks for Sidebars', 'us' ),
			'std' => 0,
			'classes' => 'for_above',
			'show_if' => array( 'enable_sidebar_titlebar', '=', 1 ),
		),
		'enable_portfolio' => array(
			'type' => 'switch',
			'switch_text' => __( 'Portfolio', 'us' ) . $renamed_portfolio_label,
			'std' => 1,
			'classes' => 'for_above',
		),
		'enable_testimonials' => array(
			'type' => 'switch',
			'switch_text' => __( 'Testimonials', 'us' ),
			'std' => 1,
			'classes' => 'for_above',
		),
		'cform_inbound_messages' => array(
			'type' => 'switch',
			'switch_text' => __( 'Contact Form Inbound Messages', 'us' ),
			'description' => __( 'Allows to save and view messages received through the built-in contact form.', 'us' ),
			'std' => 0,
			'classes' => 'desc_2 for_above',
		),
		'media_category' => array(
			'type' => 'switch',
			'switch_text' => __( 'Media Categories', 'us' ),
			'std' => 1,
			'classes' => 'for_above',
		),
		'enable_additional_settings' => array(
			'type' => 'switch',
			'switch_text' => __( 'Additional Settings', 'us' ),
			'std' => 1,
			'classes' => 'for_above',
		),
		'additional_settings_post_types' => array(
			'type' => 'checkboxes',
			'options' => $public_post_types,
			'std' => implode( ',', array_keys( $public_post_types ) ),
			'classes' => 'for_above align_with_switch vertical',
			'show_if' => array( 'enable_additional_settings', '=', 1 ),
		),
		'og_enabled' => array(
			'type' => 'switch',
			'switch_text' => __( 'SEO meta tags', 'us' ),
			'description' => __( 'If you\'re using any SEO plugin, turn OFF this option to avoid conflicts.', 'us' ) . ' <a href="' . US_HELP_PORTAL_URL . '/' . strtolower( US_THEMENAME ) . '/seo/" target="_blank">' . __( 'Learn more', 'us' ) . '</a>',
			'std' => 1,
			'classes' => 'desc_2 for_above',
		),
		'schema_markup' => array(
			'type' => 'switch',
			'switch_text' => __( 'Schema.org markup', 'us' ),
			'std' => 1,
			'classes' => 'for_above',
		),
		'templates_access_for_editors' => array(
			'type' => 'switch',
			'switch_text' => __( 'Access to Templates for Editors', 'us' ),
			'description' => sprintf(
				__( 'When this option is ON, all users who can edit pages, will also be able to edit the following: %s, %s, %s and %s.', 'us' ),
				_x( 'Headers', 'site top area', 'us' ),
				__( 'Page Templates', 'us' ),
				__( 'Reusable Blocks', 'us' ),
				__( 'Grid Layouts', 'us' )
			),
			'std' => 0,
			'classes' => 'desc_2 for_above',
			'place_if' => empty( $usof_options['white_label'] ),
		),

		// Website Performance
		'h_advanced_3' => array(
			'title' => __( 'Website Performance', 'us' ),
			'type' => 'heading',
			'classes' => 'with_separator',
		),
		'keep_url_protocol' => array(
			'type' => 'switch',
			'switch_text' => __( 'Keep "http/https" in the paths to files', 'us' ),
			'description' => __( 'If your site uses both "HTTP" and "HTTPS" and has some appearance issues, turn OFF this option.', 'us' ),
			'std' => 1,
			'classes' => 'desc_2',
		),
		'disable_jquery_migrate' => array(
			'type' => 'switch',
			'switch_text' => __( 'Disable jQuery migrate script', 'us' ),
			'description' => __( 'When this option is ON, "jquery-migrate.min.js" file won\'t be loaded on the front end.', 'us' ) . ' ' . __( 'This will improve page loading speed.', 'us' ),
			'std' => 1,
			'classes' => 'desc_2 for_above',
		),
		'jquery_footer' => array(
			'type' => 'switch',
			'switch_text' => __( 'Move jQuery scripts to the footer', 'us' ),
			'description' => __( 'When this option is ON, jQuery library files will be loaded after the page content.', 'us' ) . ' ' . __( 'This will improve page loading speed.', 'us' ),
			'std' => 1,
			'classes' => 'desc_2 for_above',
		),
		'disable_extra_vc' => array(
			'type' => 'switch',
			'switch_text' => __( 'Disable extra features of WPBakery Page Builder', 'us' ),
			'description' => __( 'When this option is ON, the original CSS and JS files of WPBakery Page Builder won\'t be loaded on the front end.', 'us' ) . ' ' . __( 'This will improve page loading speed.', 'us' ),
			'std' => 1,
			'place_if' => class_exists( 'Vc_Manager' ),
			'classes' => 'desc_2 for_above',
		),
		'optimize_assets' => array(
			'type' => 'switch',
			'switch_text' => __( 'Optimize JS and CSS size', 'us' ),
			'description' => __( 'When this option is ON, your site will compress scripts to a single JS file and compress styles to a single CSS file. You can disable unused components to reduce their sizes.', 'us' ) . ' ' . __( 'This will improve page loading speed.', 'us' ),
			'std' => 0,
			'classes' => 'desc_2 for_above',
			'disabled' => $upload_dir_not_writable,
		),
		'optimize_assets_alert' => array(
			'description' => __( 'Your uploads folder is not writable. Change your server permissions to use this option.', 'us' ),
			'type' => 'message',
			'classes' => 'string',
			'place_if' => $upload_dir_not_writable,
		),
		'optimize_assets_start' => array(
			'type' => 'wrapper_start',
			'show_if' => array( 'optimize_assets', '=', 1 ),
		),
		'assets' => array(
			'type' => 'check_table',
			'show_auto_optimize_button' => TRUE,
			'options' => $usof_assets,
			'std' => $usof_assets_std,
			'classes' => 'desc_4',
		),
		'optimize_assets_end' => array(
			'type' => 'wrapper_end',
		),
		'include_gfonts_css' => array(
			'type' => 'switch',
			'switch_text' => __( 'Merge Google Fonts styles into single CSS file', 'us' ),
			'description' => __( 'When this option is ON, Google Fonts CSS file won\'t be loaded separately.', 'us' ) . ' ' . __( 'This will improve page loading speed.', 'us' ),
			'std' => 0,
			'classes' => 'desc_2',
			'show_if' => array( 'optimize_assets', '=', 1 ),
		),
	),
);
