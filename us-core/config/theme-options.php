<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Theme Options
 *
 * @filter us_config_theme-options
 */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

global $usof_options, $pagenow;

$sidebar_titlebar_are_enabled = ! empty( $usof_options['enable_sidebar_titlebar'] );

if ( ! empty( $usof_options['portfolio_rename'] ) ) {
	$renamed_portfolio_label = ' (' . wp_strip_all_tags( $usof_options['portfolio_label_name'], TRUE ) . ')';
} else {
	$renamed_portfolio_label = '';
}

$posts_titles = array();
$color_scheme_exclude_dynamic_colors = 'scheme, custom_field';

// Variables used for config in admin area only
if (
	! wp_doing_ajax()
	AND $pagenow == 'admin.php'
	AND isset( $_GET['page'] )
	AND $_GET['page'] == 'us-theme-options'
) {
	$posts_titles = us_get_all_posts_titles_for( array(
		'page',
		'us_header',
		'us_page_block',
		'us_content_template',
	) );

	if ( empty( $usof_options['custom_colors'] ) ) {
		$color_scheme_exclude_dynamic_colors = 'all';
	}

	$image_sizes_list = us_get_image_sizes_list();
}

// Get list of specific post types and order alphabetically
$us_page_list = us_filter_posts_by_language( $posts_titles['page'] ?? array() );
$us_headers_list = us_filter_posts_by_language( $posts_titles['us_header'] ?? array() );
$us_page_blocks_list = us_filter_posts_by_language( $posts_titles['us_page_block'] ?? array() );
$us_content_templates_list = us_filter_posts_by_language( $posts_titles['us_content_template'] ?? array() );

// Use Reusable Blocks as Sidebars, if set in Theme Options
if ( ! empty( $usof_options['enable_page_blocks_for_sidebars'] ) ) {
	$sidebars_list = $us_page_blocks_list;
	$sidebar_hints_for = 'us_page_block';

	// else use regular sidebars
} else {
	$sidebars_list = us_get_sidebars();
	$sidebar_hints_for = NULL;
}

// Descriptions
$misc = us_config( 'elements_misc' );
$misc['headers_description'] .= '<br><img src="' . US_CORE_URI . '/admin/img/l-header.png">';
$misc['content_description'] .= '<br><img src="' . US_CORE_URI . '/admin/img/l-content.png">';
$misc['footers_description'] .= '<br><img src="' . US_CORE_URI . '/admin/img/l-footer.png">';

// Generate 'Pages Layout' options
$pages_layout_config = array();

foreach ( us_get_public_post_types( /* exclude */array( 'page', 'product' ) ) as $type => $title ) {

	// Rename "us_portfolio" suffix to avoid migration from old theme options
	if ( $type == 'us_portfolio' ) {
		$type = 'portfolio';
	}

	// Skip Events settings if the "Default Events Templates" is set
	if (
		$type == 'tribe_events'
		AND function_exists( 'tribe_get_option' )
		AND tribe_get_option( 'tribeEventsTemplate' ) != 'default'
	) {
		continue;
	}

	// Events Calendar separate option
	$tribe_events_full_event_template = array();
	if ( $type == 'tribe_events' AND class_exists( 'Tribe__Events__Query' ) ) {
		$tribe_events_full_event_template = array(
			'tribe_events_full_event_template' => array(
				'type' => 'switch',
				'switch_text' => __( 'Use full event template in the post content', 'us' ),
				'std' => 1,
				'classes' => 'for_above force_right',
			),
		);
	}

	$pages_layout_config += array(
		'h_' . $type => array(
			'title' => $title,
			'type' => 'heading',
			'classes' => 'with_separator sticky',
		),
		'header_' . $type . '_id' => array(
			'title' => _x( 'Header', 'site top area', 'us' ),
			'title_pos' => 'side',
			'type' => 'select',
			'hints_for' => 'us_header',
			'options' => us_array_merge(
				array(
					'__defaults__' => '&ndash; ' . __( 'As in Pages', 'us' ) . ' &ndash;',
					'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
				), $us_headers_list
			),
			'std' => '__defaults__',
		),
		'titlebar_' . $type . '_id' => array(
			'title' => __( 'Titlebar', 'us' ),
			'title_pos' => 'side',
			'type' => 'select',
			'hints_for' => 'us_page_block',
			'options' => us_array_merge(
				array(
					'__defaults__' => '&ndash; ' . __( 'As in Pages', 'us' ) . ' &ndash;',
					'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
				), $us_page_blocks_list
			),
			'std' => '__defaults__',
			'place_if' => $sidebar_titlebar_are_enabled,
		),
		'content_' . $type . '_id' => array(
			'title' => __( 'Page Template', 'us' ),
			'title_pos' => 'side',
			'type' => 'select',
			'hints_for' => 'us_content_template',
			'options' => us_array_merge(
				array(
					'__defaults__' => '&ndash; ' . __( 'As in Pages', 'us' ) . ' &ndash;',
					'' => '&ndash; ' . __( 'Show content as is', 'us' ) . ' &ndash;',
				), $us_content_templates_list
			),
			'std' => '__defaults__',
		),
	)
	+ $tribe_events_full_event_template +
	array(
		'sidebar_' . $type . '_id' => array(
			'title' => __( 'Sidebar', 'us' ),
			'title_pos' => 'side',
			'type' => 'select',
			'options' => us_array_merge(
				array(
					'__defaults__' => '&ndash; ' . __( 'As in Pages', 'us' ) . ' &ndash;',
					'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
				), $sidebars_list
			),
			'std' => '__defaults__',
			'hints_for' => $sidebar_hints_for,
			'place_if' => $sidebar_titlebar_are_enabled,
		),
		'sidebar_' . $type . '_pos' => array(
			'title_pos' => 'side',
			'type' => 'radio',
			'options' => array(
				'left' => us_translate( 'Left' ),
				'right' => us_translate( 'Right' ),
			),
			'std' => 'right',
			'classes' => 'for_above',
			'show_if' => array( 'sidebar_' . $type . '_id', '!=', array( '', '__defaults__' ) ),
			'place_if' => $sidebar_titlebar_are_enabled,
		),
		'footer_' . $type . '_id' => array(
			'title' => __( 'Footer', 'us' ),
			'title_pos' => 'side',
			'type' => 'select',
			'hints_for' => 'us_page_block',
			'options' => us_array_merge(
				array(
					'__defaults__' => '&ndash; ' . __( 'As in Pages', 'us' ) . ' &ndash;',
					'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
				), $us_page_blocks_list
			),
			'std' => '__defaults__',
		),
	);
}

// Generate 'Archives Layout' options
$archives_layout_config = $tribe_archive_default = array();
$public_taxonomies = us_get_taxonomies( TRUE, FALSE, 'woocommerce_exclude' );
$custom_post_type_archives = (array) us_get_public_post_types( array( 'page', 'post', 'product' ), /* archive_only */TRUE );

foreach ( ( $custom_post_type_archives + $public_taxonomies ) as $type => $title ) {

	if ( $type == 'tribe_events' ) {

		// Skip Events settings if the "Default Events Templates" is set
		if ( function_exists( 'tribe_get_option' ) AND tribe_get_option( 'tribeEventsTemplate' ) != 'default' ) {
			continue;

			// Additional "Default Events Template" for archive
		} else {
			$tribe_archive_default[''] = '&ndash; ' . us_translate( 'Default Events Template', 'the-events-calendar' ) . ' &ndash;';
		}
	}

	$archives_layout_config += array(
		'h_tax_' . $type => array(
			'title' => $title,
			'type' => 'heading',
			'classes' => 'with_separator sticky',
		),
		'header_tax_' . $type . '_id' => array(
			'title' => _x( 'Header', 'site top area', 'us' ),
			'title_pos' => 'side',
			'type' => 'select',
			'hints_for' => 'us_header',
			'options' => us_array_merge(
				array(
					'__defaults__' => '&ndash; ' . __( 'As in Archives', 'us' ) . ' &ndash;',
					'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
				), $us_headers_list
			),
			'std' => '__defaults__',
		),
		'titlebar_tax_' . $type . '_id' => array(
			'title' => __( 'Titlebar', 'us' ),
			'title_pos' => 'side',
			'type' => 'select',
			'hints_for' => 'us_page_block',
			'options' => us_array_merge(
				array(
					'__defaults__' => '&ndash; ' . __( 'As in Archives', 'us' ) . ' &ndash;',
					'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
				), $us_page_blocks_list
			),
			'std' => '__defaults__',
			'place_if' => $sidebar_titlebar_are_enabled,
		),
		'content_tax_' . $type . '_id' => array(
			'title' => __( 'Page Template', 'us' ),
			'title_pos' => 'side',
			'type' => 'select',
			'hints_for' => 'us_content_template',
			'options' => us_array_merge(
				array(
					'__defaults__' => '&ndash; ' . __( 'As in Archives', 'us' ) . ' &ndash;',
				), $tribe_archive_default, $us_content_templates_list
			),
			'std' => '__defaults__',
		),
		'sidebar_tax_' . $type . '_id' => array(
			'title' => __( 'Sidebar', 'us' ),
			'title_pos' => 'side',
			'type' => 'select',
			'options' => us_array_merge(
				array(
					'__defaults__' => '&ndash; ' . __( 'As in Archives', 'us' ) . ' &ndash;',
					'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
				), $sidebars_list
			),
			'hints_for' => $sidebar_hints_for,
			'std' => '__defaults__',
			'place_if' => $sidebar_titlebar_are_enabled,
		),
		'sidebar_tax_' . $type . '_pos' => array(
			'title_pos' => 'side',
			'type' => 'radio',
			'options' => array(
				'left' => us_translate( 'Left' ),
				'right' => us_translate( 'Right' ),
			),
			'std' => 'right',
			'classes' => 'for_above',
			'show_if' => array( 'sidebar_tax_' . $type . '_id', '!=', array( '', '__defaults__' ) ),
			'place_if' => $sidebar_titlebar_are_enabled,
		),
		'footer_tax_' . $type . '_id' => array(
			'title' => __( 'Footer', 'us' ),
			'title_pos' => 'side',
			'type' => 'select',
			'hints_for' => 'us_page_block',
			'options' => us_array_merge(
				array(
					'__defaults__' => '&ndash; ' . __( 'As in Archives', 'us' ) . ' &ndash;',
					'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
				), $us_page_blocks_list
			),
			'std' => '__defaults__',
		),
	);
}

// Generate Product taxonomies Layout options
$shop_layout_config = array();
if ( class_exists( 'woocommerce' ) ) {
	foreach ( us_get_taxonomies( TRUE, FALSE, 'woocommerce_only' ) as $type => $title ) {
		$shop_layout_config += array(
			'h_tax_' . $type => array(
				'title' => $title,
				'type' => 'heading',
				'classes' => 'with_separator sticky',
			),
			'header_tax_' . $type . '_id' => array(
				'title' => _x( 'Header', 'site top area', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'hints_for' => 'us_header',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Shop Page', 'us' ) . ' &ndash;',
						'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
					), $us_headers_list
				),
				'std' => '__defaults__',
			),
			'titlebar_tax_' . $type . '_id' => array(
				'title' => __( 'Titlebar', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'hints_for' => 'us_page_block',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Shop Page', 'us' ) . ' &ndash;',
						'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
					), $us_page_blocks_list
				),
				'std' => '__defaults__',
				'place_if' => $sidebar_titlebar_are_enabled,
			),
			'content_tax_' . $type . '_id' => array(
				'title' => __( 'Page Template', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'hints_for' => 'us_content_template',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Shop Page', 'us' ) . ' &ndash;',
					), $us_content_templates_list
				),
				'std' => '__defaults__',
			),
			'sidebar_tax_' . $type . '_id' => array(
				'title' => __( 'Sidebar', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Shop Page', 'us' ) . ' &ndash;',
						'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
					), $sidebars_list
				),
				'std' => '__defaults__',
				'hints_for' => $sidebar_hints_for,
				'place_if' => $sidebar_titlebar_are_enabled,
			),
			'sidebar_tax_' . $type . '_pos' => array(
				'title_pos' => 'side',
				'type' => 'radio',
				'options' => array(
					'left' => us_translate( 'Left' ),
					'right' => us_translate( 'Right' ),
				),
				'std' => 'right',
				'classes' => 'for_above',
				'show_if' => array( 'sidebar_tax_' . $type . '_id', '!=', array( '', '__defaults__' ) ),
				'place_if' => $sidebar_titlebar_are_enabled,
			),
			'footer_tax_' . $type . '_id' => array(
				'title' => __( 'Footer', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'hints_for' => 'us_page_block',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Shop Page', 'us' ) . ' &ndash;',
						'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
					), $us_page_blocks_list
				),
				'std' => '__defaults__',
			),
		);
	}
}

// Get White Label settings
$white_label_config = us_config( 'white-label.white_label', array(), TRUE );
$white_label_config['place_if'] = FALSE;

// Theme Options Config
$theme_options_config = array(

	'general' => array(
		'title' => us_translate( 'General' ),
		'fields' => array(

			'maintenance_mode' => array(
				'title' => __( 'Maintenance Mode', 'us' ),
				'title_pos' => 'side',
				'description' => __( 'When this option is ON, all non-logged in users will see only the selected page. This is useful when your site is under construction.', 'us' ),
				'type' => 'switch',
				'switch_text' => __( 'Show site visitors only one specific page', 'us' ),
				'std' => 0,
				'classes' => 'color_yellow desc_3',
				// show the setting, but disable it, if true
				'disabled' => get_option( 'us_license_dev_activated', 0 ),
			),
			'maintenance_mode_alert' => array(
				'title_pos' => 'side',
				'description' => sprintf( __( 'It\'s not possible to switch off this setting, while %s is activated for development.', 'us' ), US_THEMENAME ) . ' ' . sprintf( __( 'You can deactivate it on your %sLicenses%s page.', 'us' ), '<a href="' . US_HELP_PORTAL_URL . '/user/licenses/" target="_blank">', '</a>' ),
				'type' => 'message',
				'classes' => 'string',
				'place_if' => get_option( 'us_license_dev_activated', 0 ),
			),
			'maintenance_page' => array(
				'title_pos' => 'side',
				'type' => 'select',
				'options' => $us_page_list,
				'std' => '',
				'hints_for' => 'page',
				'classes' => 'for_above',
				'show_if' => array( 'maintenance_mode', '=', 1 ),
			),
			'maintenance_private_access' => array(
				'title_pos' => 'side',
				'description' => __( 'When this option is ON, site visitors with a private link will be able to see the site pages for 30 days.', 'us' ),
				'type' => 'switch',
				'switch_text' => __( 'Share your site with a private link', 'us' ),
				'std' => 0,
				'classes' => 'for_above desc_3',
				'show_if' => array( 'maintenance_mode', '=', 1 ),
			),
			'maintenance_private_key' => array(
				'title_pos' => 'side',
				'type' => 'private_link',
				'std' => wp_generate_password( 32, FALSE ),
				'classes' => 'for_above',
				'show_if' => array( 'maintenance_private_access', '=', 1 ),
			),
			'maintenance_503' => array(
				'title_pos' => 'side',
				'description' => __( 'When this option is ON, your site will send HTTP 503 response to search engines. Use this option only for short period of time.', 'us' ),
				'type' => 'switch',
				'switch_text' => __( 'Enable "503 Service Unavailable" status', 'us' ),
				'std' => 0,
				'classes' => 'for_above desc_3',
				'show_if' => array( 'maintenance_mode', '=', 1 ),
			),

			'site_icon' => array(
				'title' => us_translate( 'Site Icon' ),
				'title_pos' => 'side',
				'description' => us_translate( 'Site Icons are what you see in browser tabs, bookmark bars, and within the WordPress mobile apps. Upload one here!' ) . '<br>' . sprintf( us_translate( 'Site Icons should be square and at least %s pixels.' ), '<strong>512</strong>' ),
				'type' => 'upload',
				'classes' => 'desc_3',
			),
			'dark_theme' => array(
				'title' => __( 'Dark Theme', 'us' ),
				'title_pos' => 'side',
				'description' => __( 'The selected color scheme will be displayed to site visitors if the dark theme is enabled on their device.', 'us' ),
				'type' => 'select',
				'options' => us_array_merge(
					array(
						'none' => '&ndash; ' . us_translate( 'None' ) . ' &ndash;',
					),
					us_get_color_schemes( TRUE )
				),
				'std' => 'none',
				'classes' => 'desc_3',
			),
			'preloader' => array(
				'title' => __( 'Preloader Screen', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'options' => array(
					'disabled' => '&ndash; ' . us_translate( 'None' ) . ' &ndash;',
					'1' => __( 'Spinner', 'us' ) . ' 1',
					'2' => __( 'Spinner', 'us' ) . ' 2',
					'3' => __( 'Circle', 'us' ) . ' 1',
					'4' => __( 'Circle', 'us' ) . ' 2',
					'5' => __( 'Cube Tilt', 'us' ),
					'custom' => __( 'Custom Image', 'us' ),
				),
				'std' => 'disabled',
			),
			'preloader_image' => array(
				'title' => '',
				'title_pos' => 'side',
				'type' => 'upload',
				'classes' => 'for_above',
				'show_if' => array( 'preloader', '=', 'custom' ),
			),
			'img_placeholder' => array(
				'title' => __( 'Image Placeholder', 'us' ),
				'title_pos' => 'side',
				'type' => 'upload',
				'std' => sprintf( '%s/assets/images/placeholder.svg', US_CORE_URI ),
			),
			'ripple_effect' => array(
				'title' => __( 'Ripple Effect', 'us' ),
				'title_pos' => 'side',
				'type' => 'switch',
				'switch_text' => __( 'Show the ripple effect when theme elements are clicked', 'us' ),
				'std' => 0,
			),

			// Links Underline
			'links_underline' => array(
				'title' => __( 'Underlining Links', 'us' ),
				'title_pos' => 'side',
				'type' => 'switch',
				'switch_text' => __( 'Underline text links globally', 'us' ),
				'std' => 0,
			),
			'wrapper_links_underline_start' => array(
				'type' => 'wrapper_start',
				'classes' => 'force_right',
				'show_if' => array( 'links_underline', '=', 1 ),
			),
			'links_underline_thickness' => array(
				'title' => __( 'Line options by default', 'us' ),
				'description' => __( 'Thickness', 'us' ),
				'type' => 'slider',
				'std' => '0px',
				'options' => array(
					'px' => array(
						'min' => 0,
						'max' => 10,
					),
					'em' => array(
						'min' => 0.0,
						'max' => 1.0,
						'step' => 0.1,
					),
				),
				'cols' => 2,
			),
			'links_underline_thickness_hover' => array(
				'title' => __( 'Line options on hover', 'us' ),
				'description' => __( 'Thickness', 'us' ),
				'type' => 'slider',
				'std' => '1px',
				'options' => array(
					'px' => array(
						'min' => 0,
						'max' => 10,
					),
					'em' => array(
						'min' => 0.0,
						'max' => 1.0,
						'step' => 0.1,
					),
				),
				'cols' => 2,
			),
			'links_underline_offset' => array(
				'description' => __( 'Offset', 'us' ),
				'type' => 'slider',
				'std' => '0.2em',
				'options' => array(
					'px' => array(
						'min' => -10,
						'max' => 10,
					),
					'em' => array(
						'min' => -1.0,
						'max' => 1.0,
						'step' => 0.1,
					),
				),
				'cols' => 2,
				'classes' => 'for_above',
			),
			'links_underline_offset_hover' => array(
				'description' => __( 'Offset', 'us' ),
				'type' => 'slider',
				'std' => '0.2em',
				'options' => array(
					'px' => array(
						'min' => -10,
						'max' => 10,
					),
					'em' => array(
						'min' => -1.0,
						'max' => 1.0,
						'step' => 0.1,
					),
				),
				'cols' => 2,
				'classes' => 'for_above',
			),
			'links_underline_style' => array(
				'description' => us_translate( 'Style' ),
				'type' => 'select',
				'options' => array(
					'solid' => __( 'Solid', 'us' ),
					'dashed' => __( 'Dashed', 'us' ),
					'dotted' => __( 'Dotted', 'us' ),
					'double' => __( 'Double', 'us' ),
					'wavy' => __( 'Wavy', 'us' ),
				),
				'std' => 'solid',
				'cols' => 2,
				'classes' => 'for_above',
			),
			'links_underline_style_hover' => array(
				'description' => us_translate( 'Style' ),
				'type' => 'select',
				'options' => array(
					'solid' => __( 'Solid', 'us' ),
					'dashed' => __( 'Dashed', 'us' ),
					'dotted' => __( 'Dotted', 'us' ),
					'double' => __( 'Double', 'us' ),
					'wavy' => __( 'Wavy', 'us' ),
				),
				'std' => 'solid',
				'cols' => 2,
				'classes' => 'for_above',
			),
			'links_underline_skip_ink' => array(
				'description' => __( 'Skip glyph descenders', 'us' ),
				'type' => 'select',
				'options' => array(
					'auto' => us_translate_x( 'Auto', 'auto preload' ),
					'none' => us_translate( 'None' ),
				),
				'std' => 'auto',
				'cols' => 2,
				'classes' => 'for_above',
			),
			'links_underline_skip_ink_hover' => array(
				'description' => __( 'Skip glyph descenders', 'us' ),
				'type' => 'select',
				'options' => array(
					'auto' => us_translate_x( 'Auto', 'auto preload' ),
					'none' => us_translate( 'None' ),
				),
				'std' => 'auto',
				'cols' => 2,
				'classes' => 'for_above',
			),
			'links_underline_color' => array(
				'description' => us_translate( 'Color' ),
				'type' => 'color',
				'with_gradient' => FALSE,
				'clear_pos' => 'left',
				'exclude_dynamic_colors' => 'custom_field',
				'std' => '',
				'cols' => 2,
				'classes' => 'for_above',
			),
			'links_underline_color_hover' => array(
				'description' => us_translate( 'Color' ),
				'type' => 'color',
				'with_gradient' => FALSE,
				'clear_pos' => 'left',
				'exclude_dynamic_colors' => 'custom_field',
				'std' => '',
				'cols' => 2,
				'classes' => 'for_above',
			),
			'wrapper_links_underline_end' => array(
				'type' => 'wrapper_end',
			),

			// Back to Top
			'back_to_top' => array(
				'title' => sprintf( __( '"%s" Button', 'us' ), __( 'Back to Top', 'us' ) ),
				'title_pos' => 'side',
				'type' => 'switch',
				'switch_text' => __( 'Show the button that helps users navigate to the top of long pages', 'us' ),
				'std' => 1,
			),
			'wrapper_back_to_top_start' => array(
				'type' => 'wrapper_start',
				'classes' => 'force_right',
				'show_if' => array( 'back_to_top', '=', 1 ),
			),
			'back_to_top_style' => array(
				'title' => __( 'Button Style', 'us' ),
				'description' => '<a href="' . admin_url() . 'admin.php?page=us-theme-options#buttons">' . __( 'Edit Button Styles', 'us' ) . '</a>',
				'type' => 'select',
				'options' => us_array_merge(
					array(
						'' => '&ndash; ' . us_translate( 'Default' ) . ' &ndash;',
					), us_get_btn_styles()
				),
				'std' => '',
			),
			'back_to_top_icon' => array(
				'title' => __( 'Button Icon', 'us' ),
				'type' => 'icon',
				'std' => ( US_THEMENAME === 'Impreza' ) ? 'far|angle-up' : 'material|keyboard_arrow_up',
			),
			'back_to_top_pos' => array(
				'title' => __( 'Button Position', 'us' ),
				'type' => 'radio',
				'options' => array(
					'left' => us_translate( 'Left' ),
					'right' => us_translate( 'Right' ),
				),
				'std' => 'right',
				'classes' => 'cols_2',
			),
			'back_to_top_color' => array(
				'type' => 'color',
				'with_gradient' => TRUE,
				'exclude_dynamic_colors' => 'custom_field',
				'title' => __( 'Button Color', 'us' ),
				'std' => 'rgba(0,0,0,0.3)',
				'classes' => 'cols_2',
				'show_if' => array( 'back_to_top_style', '=', '' ),
			),
			'back_to_top_display' => array(
				'title' => __( 'Page Scroll Amount to Show the Button', 'us' ),
				'type' => 'slider',
				'std' => '100vh',
				'options' => array(
					'vh' => array(
						'min' => 10,
						'max' => 200,
						'step' => 10,
					),
				),
				'classes' => 'desc_3',
			),
			'wrapper_back_to_top_end' => array(
				'type' => 'wrapper_end',
			),
			'smooth_scroll_duration' => array(
				'title' => __( 'Smooth Scroll Duration', 'us' ),
				'title_pos' => 'side',
				'type' => 'slider',
				'std' => '1000ms',
				'options' => array(
					'ms' => array(
						'min' => 0,
						'max' => 3000,
						'step' => 100,
					),
				),
			),

			// Cookie Notice
			'cookie_notice' => array(
				'title' => __( 'Cookie Notice', 'us' ),
				'title_pos' => 'side',
				'type' => 'switch',
				'switch_text' => __( 'Show floating notice for new site visitors', 'us' ),
				'std' => 0,
			),
			'wrapper_cookie_start' => array(
				'type' => 'wrapper_start',
				'classes' => 'force_right',
				'show_if' => array( 'cookie_notice', '=', 1 ),
			),
			'cookie_message' => array(
				'title' => __( 'Message', 'us' ),
				'type' => 'textarea',
				'std' => 'This website uses cookies to improve your experience. If you continue to use this site, you agree with it.',
				'classes' => 'desc_3',
			),
			'cookie_privacy' => array(
				'type' => 'checkboxes',
				'options' => array(
					'page_link' => sprintf( __( 'Show link to the %s page', 'us' ), '<a href="' . admin_url( 'options-privacy.php' ) . '" target="_blank">' . us_translate( 'Privacy Policy' ) . '</a>' ),
				),
				'std' => '',
				'classes' => 'for_above',
			),
			'cookie_message_pos' => array(
				'title' => us_translate( 'Position' ),
				'type' => 'radio',
				'options' => array(
					'top' => us_translate( 'Top' ),
					'bottom' => us_translate( 'Bottom' ),
				),
				'std' => 'bottom',
			),
			'cookie_btn_label' => array(
				'title' => __( 'Button Label', 'us' ),
				'type' => 'text',
				'std' => 'Ok',
				'classes' => 'cols_2',
			),
			'cookie_btn_style' => array(
				'title' => __( 'Button Style', 'us' ),
				'description' => '<a href="' . admin_url() . 'admin.php?page=us-theme-options#buttons">' . __( 'Edit Button Styles', 'us' ) . '</a>',
				'type' => 'select',
				'options' => us_get_btn_styles(),
				'std' => '1',
				'classes' => 'cols_2',
			),
			'wrapper_cookie_end' => array(
				'type' => 'wrapper_end',
			),

			// Block 3rd-party files
			'block_third_party_files' => array(
				'title' => __( 'GDPR Compliance', 'us' ),
				'title_pos' => 'side',
				'type' => 'switch',
				'switch_text' => __( 'Block loading of third-party files until the consent of the site visitor', 'us' ),
				'description' => __( 'Applies to Map and Video Player elements.', 'us' ),
				'std' => 0,
				'classes' => 'desc_3',
			),

			// Keyboard Accessibility
			'h_keyboard_accessibility' => array(
				'title' => __( 'Keyboard Accessibility', 'us' ),
				'type' => 'heading',
				'classes' => 'with_separator sticky',
			),
			'wrapper_focus_outline_start' => array(
				'title' => __( 'Outline for clickable elements', 'us' ),
				'type' => 'wrapper_start',
				'classes' => 'force_right',
			),
			'focus_outline_width' => array(
				'title' => __( 'Line Thickness', 'us' ),
				'type' => 'slider',
				'std' => '2px',
				'options' => array(
					'px' => array(
						'min' => 1,
						'max' => 10,
					),
					'em' => array(
						'min' => 0.1,
						'max' => 1.0,
						'step' => 0.1,
					),
				),
				'cols' => 2,
			),
			'focus_outline_style' => array(
				'title' => us_translate( 'Style' ),
				'type' => 'select',
				'options' => array(
					'solid' => __( 'Solid', 'us' ),
					'dashed' => __( 'Dashed', 'us' ),
					'dotted' => __( 'Dotted', 'us' ),
					'double' => __( 'Double', 'us' ),
				),
				'std' => 'solid',
				'cols' => 2,
			),
			'focus_outline_offset' => array(
				'title' => __( 'Line Offset', 'us' ),
				'type' => 'slider',
				'std' => '2px',
				'options' => array(
					'px' => array(
						'min' => -2,
						'max' => 10,
					),
					'em' => array(
						'min' => -0.2,
						'max' => 1.0,
						'step' => 0.1,
					),
				),
				'cols' => 2,
			),
			'focus_outline_color' => array(
				'title' => us_translate( 'Color' ),
				'type' => 'color',
				'with_gradient' => FALSE,
				'clear_pos' => 'left',
				'exclude_dynamic_colors' => 'custom_field',
				'std' => '_content_primary',
				'cols' => 2,
			),
			'wrapper_focus_outline_end' => array(
				'type' => 'wrapper_end',
			),

			'skip_to_content_btn' => array(
				'title' => sprintf( __( '"%s" Button', 'us' ), __( 'Skip to main content', 'us' ) ),
				'title_pos' => 'side',
				'type' => 'switch',
				'switch_text' => __( 'Show button to skip page header', 'us' ),
				'std' => 0,
			),
			'wrapper_skip_to_content_btn_start' => array(
				'type' => 'wrapper_start',
				'classes' => 'force_right',
				'show_if' => array( 'skip_to_content_btn', '=', 1 ),
			),
			'skip_to_content_btn_label' => array(
				'title' => __( 'Button Label', 'us' ),
				'type' => 'text',
				'std' => __( 'Skip to main content', 'us' ),
				'cols' => 2,
			),
			'skip_to_content_btn_style' => array(
				'title' => __( 'Button Style', 'us' ),
				'description' => '<a href="' . admin_url() . 'admin.php?page=us-theme-options#buttons">' . __( 'Edit Button Styles', 'us' ) . '</a>',
				'type' => 'select',
				'options' => us_array_merge(
					array(
						'' => '&ndash; ' . us_translate( 'Default' ) . ' &ndash;',
					), us_get_btn_styles()
				),
				'std' => '',
				'cols' => 2,
			),
			'wrapper_skip_to_content_btn_end' => array(
				'type' => 'wrapper_end',
			),

			'skip_to_footer_btn' => array(
				'title' => sprintf( __( '"%s" Button', 'us' ), __( 'Skip to footer', 'us' ) ),
				'title_pos' => 'side',
				'type' => 'switch',
				'switch_text' => __( 'Show button to skip page header and content', 'us' ),
				'std' => 0,
			),
			'wrapper_skip_to_footer_btn_start' => array(
				'type' => 'wrapper_start',
				'classes' => 'force_right',
				'show_if' => array( 'skip_to_footer_btn', '=', 1 ),
			),
			'skip_to_footer_btn_label' => array(
				'title' => __( 'Button Label', 'us' ),
				'type' => 'text',
				'std' => __( 'Skip to footer', 'us' ),
				'cols' => 2,
			),
			'skip_to_footer_btn_style' => array(
				'title' => __( 'Button Style', 'us' ),
				'description' => '<a href="' . admin_url() . 'admin.php?page=us-theme-options#buttons">' . __( 'Edit Button Styles', 'us' ) . '</a>',
				'type' => 'select',
				'options' => us_array_merge(
					array(
						'' => '&ndash; ' . us_translate( 'Default' ) . ' &ndash;',
					), us_get_btn_styles()
				),
				'std' => '',
				'cols' => 2,
			),
			'wrapper_skip_to_footer_btn_end' => array(
				'type' => 'wrapper_end',
			),
		),
	),

	// Site Layout
	'layout' => us_config( 'theme-options/layout' ),

	// Pages Layout
	'pages_layout' => array(
		'title' => __( 'Pages Layout', 'us' ),
		'fields' => array(

				// Search Results
				'search_page' => array(
					'title' => us_translate_x( 'Search Results', 'Template name' ),
					'title_pos' => 'side',
					'description' => sprintf(
						__( 'The selected page must contain a "%s" element showing "%s".', 'us' ),
						__( 'Post List', 'us' ),
						__( 'Posts of the current query (archives and search results)', 'us' )
					),
					'type' => 'select',
					'options' => us_array_merge(
						array( 'default' => '&ndash; ' . us_translate( 'Default' ) . ' &ndash;' ), $us_page_list
					),
					'std' => 'default',
					'hints_for' => 'page',
					'classes' => 'desc_3',
				),
				'exclude_post_types_in_search' => array(
					'title' => __( 'Exclude from Search Results', 'us' ),
					'title_pos' => 'side',
					'type' => 'checkboxes',
					'options' => us_get_public_post_types(),
					'std' => '',
				),

				// 404 page
				'page_404' => array(
					'title' => __( 'Page "404 Not Found"', 'us' ),
					'title_pos' => 'side',
					'description' => __( 'The selected page will be shown instead of the "Page not found" message.', 'us' ),
					'type' => 'select',
					'options' => us_array_merge(
						array( 'default' => '&ndash; ' . us_translate( 'Default' ) . ' &ndash;' ), $us_page_list
					),
					'std' => 'default',
					'hints_for' => 'page',
					'classes' => 'desc_3',
				),

				// Pages
				'h_defaults' => array(
					'title' => us_translate_x( 'Pages', 'post type general name' ),
					'type' => 'heading',
					'classes' => 'with_separator sticky',
				),
				'header_id' => array(
					'title' => _x( 'Header', 'site top area', 'us' ),
					'title_pos' => 'side',
					'description' => $misc['headers_description'],
					'type' => 'select',
					'hints_for' => 'us_header',
					'options' => us_array_merge(
						array( '' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;' ), $us_headers_list
					),
					'std' => '',
					'classes' => 'desc_3',
				),
				'titlebar_id' => array(
					'title' => __( 'Titlebar', 'us' ),
					'title_pos' => 'side',
					'type' => 'select',
					'hints_for' => 'us_page_block',
					'options' => us_array_merge(
						array(
							'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
						), $us_page_blocks_list
					),
					'std' => '',
					'place_if' => $sidebar_titlebar_are_enabled,
				),
				'content_id' => array(
					'title' => __( 'Page Template', 'us' ),
					'title_pos' => 'side',
					'description' => $misc['content_description'],
					'type' => 'select',
					'hints_for' => 'us_content_template',
					'options' => us_array_merge(
						array( '' => '&ndash; ' . __( 'Show content as is', 'us' ) . ' &ndash;' ), $us_content_templates_list
					),
					'std' => '',
					'classes' => 'desc_3',
				),
				'sidebar_id' => array(
					'title' => __( 'Sidebar', 'us' ),
					'title_pos' => 'side',
					'type' => 'select',
					'options' => us_array_merge(
						array(
							'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
						), $sidebars_list
					),
					'std' => '',
					'hints_for' => $sidebar_hints_for,
					'place_if' => $sidebar_titlebar_are_enabled,
				),
				'sidebar_pos' => array(
					'title_pos' => 'side',
					'type' => 'radio',
					'options' => array(
						'left' => us_translate( 'Left' ),
						'right' => us_translate( 'Right' ),
					),
					'std' => 'right',
					'classes' => 'for_above',
					'show_if' => array( 'sidebar_id', '!=', '' ),
					'place_if' => $sidebar_titlebar_are_enabled,
				),
				'footer_id' => array(
					'title' => __( 'Footer', 'us' ),
					'title_pos' => 'side',
					'description' => $misc['footers_description'],
					'type' => 'select',
					'hints_for' => 'us_page_block',
					'options' => us_array_merge(
						array( '' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;' ), $us_page_blocks_list
					),
					'std' => '',
					'classes' => 'desc_3',
				),

			) + $pages_layout_config
	),

	// Archives Layout
	'archives_layout' => array(
		'title' => __( 'Archives Layout', 'us' ),
		'fields' => array(

			// Archives
			'h_archive_defaults' => array(
				'title' => us_translate( 'Archives' ),
				'type' => 'heading',
				'classes' => 'with_separator sticky',
			),
			'header_archive_id' => array(
				'title' => _x( 'Header', 'site top area', 'us' ),
				'title_pos' => 'side',
				'description' => $misc['headers_description'],
				'type' => 'select',
				'hints_for' => 'us_header',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Pages', 'us' ) . ' &ndash;',
						'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
					),
					$us_headers_list
				),
				'std' => '__defaults__',
				'classes' => 'desc_3',
			),
			'titlebar_archive_id' => array(
				'title' => __( 'Titlebar', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'hints_for' => 'us_page_block',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Pages', 'us' ) . ' &ndash;',
						'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
					), $us_page_blocks_list
				),
				'std' => '__defaults__',
				'place_if' => $sidebar_titlebar_are_enabled,
			),
			'content_archive_id' => array(
				'title' => __( 'Page Template', 'us' ),
				'title_pos' => 'side',
				'description' => $misc['content_description'],
				'type' => 'select',
				'hints_for' => 'us_content_template',
				'options' => us_array_merge(
					array( '' => '&ndash; ' . us_translate( 'Default' ) . ' &ndash;' ), $us_content_templates_list
				),
				'std' => '',
				'classes' => 'desc_3',
			),
			'sidebar_archive_id' => array(
				'title' => __( 'Sidebar', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Pages', 'us' ) . ' &ndash;',
						'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
					), $sidebars_list
				),
				'std' => '__defaults__',
				'hints_for' => $sidebar_hints_for,
				'place_if' => $sidebar_titlebar_are_enabled,
			),
			'sidebar_archive_pos' => array(
				'title_pos' => 'side',
				'type' => 'radio',
				'options' => array(
					'left' => us_translate( 'Left' ),
					'right' => us_translate( 'Right' ),
				),
				'std' => 'right',
				'classes' => 'for_above',
				'show_if' => array( 'sidebar_archive_id', '!=', '' ),
				'place_if' => $sidebar_titlebar_are_enabled,
			),
			'footer_archive_id' => array(
				'title' => __( 'Footer', 'us' ),
				'title_pos' => 'side',
				'description' => $misc['footers_description'],
				'type' => 'select',
				'hints_for' => 'us_page_block',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Pages', 'us' ) . ' &ndash;',
						'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
					),
					$us_page_blocks_list
				),
				'std' => '__defaults__',
				'classes' => 'desc_3',
			),

		)
		+ $archives_layout_config +
		array(

			// Authors
			'h_authors' => array(
				'title' => __( 'Authors', 'us' ),
				'type' => 'heading',
				'classes' => 'with_separator sticky',
			),
			'header_author_id' => array(
				'title' => _x( 'Header', 'site top area', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'hints_for' => 'us_header',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Archives', 'us' ) . ' &ndash;',
						'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
					), $us_headers_list
				),
				'std' => '__defaults__',
			),
			'titlebar_author_id' => array(
				'title' => __( 'Titlebar', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'hints_for' => 'us_page_block',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Archives', 'us' ) . ' &ndash;',
						'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
					), $us_page_blocks_list
				),
				'std' => '__defaults__',
				'place_if' => $sidebar_titlebar_are_enabled,
			),
			'content_author_id' => array(
				'title' => __( 'Page Template', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'hints_for' => 'us_page_block',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Archives', 'us' ) . ' &ndash;',
					), $us_content_templates_list
				),
				'std' => '__defaults__',
			),
			'sidebar_author_id' => array(
				'title' => __( 'Sidebar', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Archives', 'us' ) . ' &ndash;',
						'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
					), $sidebars_list
				),
				'std' => '__defaults__',
				'hints_for' => $sidebar_hints_for,
				'place_if' => $sidebar_titlebar_are_enabled,
			),
			'sidebar_author_pos' => array(
				'title_pos' => 'side',
				'type' => 'radio',
				'options' => array(
					'left' => us_translate( 'Left' ),
					'right' => us_translate( 'Right' ),
				),
				'std' => 'right',
				'classes' => 'for_above',
				'show_if' => array( 'sidebar_author_id', '!=', array( '', '__defaults__' ) ),
				'place_if' => $sidebar_titlebar_are_enabled,
			),
			'footer_author_id' => array(
				'title' => __( 'Footer', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'hints_for' => 'us_page_block',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Archives', 'us' ) . ' &ndash;',
						'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
					), $us_page_blocks_list
				),
				'std' => '__defaults__',
			),
		),
	),

	// Colors
	'colors' => array(
		'title' => us_translate( 'Colors' ),
		'fields' => array(

			// Custom Global Colors
			'h_custom_colors' => array(
				'title' => __( 'Custom Global Colors', 'us' ),
				'type' => 'heading',
				'classes' => 'with_separator',
			),
			'custom_colors' => array(
				'type' => 'group',
				'show_controls' => TRUE,
				'is_sortable' => TRUE,
				'is_accordion' => FALSE,
				'params' => array(
					'color' => array(
						'type' => 'color',
						'with_gradient' => TRUE,
						'exclude_dynamic_colors' => 'all',
						'std' => '',
					),
					'name' => array(
						'placeholder' => us_translate( 'Name' ),
						'type' => 'text',
						'std' => us_translate( 'Custom color' ),
					),
					'slug' => array(
						'placeholder' => us_translate( 'Slug' ),
						'type' => 'text',
						'unique_value' => array(), // unique value in a group (only works in group context)
						'sanitize_color_slug' => TRUE, // sanitize color slug (only works in group context)
						'std' => 'custom',
					),
				),
				'std' => array(),
			),

			// Color Schemes
			'style_scheme' => array(
				'type' => 'style_scheme',
			),

			// Header colors
			'change_header_colors_start' => array(
				'type' => 'wrapper_start',
				'classes' => 'for_colors',
			),
			'h_colors_1' => array(
				'title' => __( 'Header colors', 'us' ),
				'type' => 'heading',
				'classes' => 'with_separator sticky',
			),
			'color_header_middle_bg' => array(
				'type' => 'color',
				'with_gradient' => TRUE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => us_translate_x( 'Background', 'custom background' ),
			),
			'color_header_middle_text' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => us_translate( 'Text' ) . ' / ' . us_translate( 'Link' ),
			),
			'color_header_middle_text_hover' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => __( 'Link on hover', 'us' ),
			),
			'color_header_transparent_bg' => array(
				'type' => 'color',
				'with_gradient' => TRUE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'std' => 'transparent',
				'text' => __( 'Transparent Header', 'us' ) . ': ' . us_translate_x( 'Background', 'custom background' ),
			),
			'color_header_transparent_text' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => __( 'Transparent Header', 'us' ) . ': ' . us_translate( 'Text' ) . ' / ' . us_translate( 'Link' ),
			),
			'color_header_transparent_text_hover' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => __( 'Transparent Header', 'us' ) . ': ' . __( 'Link on hover', 'us' ),
			),
			'color_chrome_toolbar' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => __( 'Browser Toolbar', 'us' ),
			),
			'change_header_colors_end' => array(
				'type' => 'wrapper_end',
			),

			// Alternate Header colors
			'change_header_alt_colors_start' => array(
				'type' => 'wrapper_start',
				'classes' => 'for_colors',
			),
			'h_colors_2' => array(
				'title' => __( 'Alternate Header colors', 'us' ),
				'type' => 'heading',
				'classes' => 'with_separator sticky',
			),
			'color_header_top_bg' => array(
				'type' => 'color',
				'clear_pos' => 'left',
				'with_gradient' => TRUE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => us_translate_x( 'Background', 'custom background' ),
			),
			'color_header_top_text' => array(
				'type' => 'color',
				'clear_pos' => 'left',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => us_translate( 'Text' ) . ' / ' . us_translate( 'Link' ),
			),
			'color_header_top_text_hover' => array(
				'type' => 'color',
				'clear_pos' => 'left',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => __( 'Link on hover', 'us' ),
			),
			'color_header_top_transparent_bg' => array(
				'type' => 'color',
				'clear_pos' => 'left',
				'with_gradient' => TRUE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'std' => 'rgba(0,0,0,0.2)',
				'text' => __( 'Transparent Header', 'us' ) . ': ' . us_translate_x( 'Background', 'custom background' ),
			),
			'color_header_top_transparent_text' => array(
				'type' => 'color',
				'clear_pos' => 'left',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'std' => 'rgba(255,255,255,0.66)',
				'text' => __( 'Transparent Header', 'us' ) . ': ' . us_translate( 'Text' ) . ' / ' . us_translate( 'Link' ),
			),
			'color_header_top_transparent_text_hover' => array(
				'type' => 'color',
				'clear_pos' => 'left',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'std' => '#fff',
				'text' => __( 'Transparent Header', 'us' ) . ': ' . __( 'Link on hover', 'us' ),
			),
			'change_header_alt_colors_end' => array(
				'type' => 'wrapper_end',
			),

			// Content colors
			'change_content_colors_start' => array(
				'type' => 'wrapper_start',
				'classes' => 'for_colors',
			),
			'h_colors_3' => array(
				'title' => __( 'Content colors', 'us' ),
				'type' => 'heading',
				'classes' => 'with_separator sticky',
			),
			'color_content_bg' => array(
				'type' => 'color',
				'with_gradient' => TRUE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => us_translate_x( 'Background', 'custom background' ),
			),
			'color_content_bg_alt' => array(
				'type' => 'color',
				'with_gradient' => TRUE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => __( 'Alternate Background', 'us' ),
			),
			'color_content_border' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => us_translate( 'Border' ),
			),
			'color_content_heading' => array(
				'type' => 'color',
				'with_gradient' => TRUE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => __( 'Headings', 'us' ),
			),
			'color_content_text' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => us_translate( 'Text' ),
			),
			'color_content_link' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => us_translate( 'Link' ),
			),
			'color_content_link_hover' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => __( 'Link on hover', 'us' ),
			),
			'color_content_primary' => array(
				'type' => 'color',
				'with_gradient' => TRUE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => __( 'Primary Color', 'us' ),
			),
			'color_content_secondary' => array(
				'type' => 'color',
				'with_gradient' => TRUE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => __( 'Secondary Color', 'us' ),
			),
			'color_content_faded' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => __( 'Faded Text', 'us' ),
			),
			'color_content_overlay' => array(
				'type' => 'color',
				'with_gradient' => TRUE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'std' => 'rgba(0,0,0,0.75)',
				'text' => __( 'Background Overlay', 'us' ),
			),
			'change_content_colors_end' => array(
				'type' => 'wrapper_end',
			),

			// Alternate Content colors
			'change_alt_content_colors_start' => array(
				'type' => 'wrapper_start',
				'classes' => 'for_colors',
			),
			'h_colors_4' => array(
				'title' => __( 'Alternate Content colors', 'us' ),
				'type' => 'heading',
				'classes' => 'with_separator sticky',
			),
			'color_alt_content_bg' => array(
				'type' => 'color',
				'clear_pos' => 'left',
				'with_gradient' => TRUE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => us_translate_x( 'Background', 'custom background' ),
			),
			'color_alt_content_bg_alt' => array(
				'type' => 'color',
				'clear_pos' => 'left',
				'with_gradient' => TRUE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => __( 'Alternate Background', 'us' ),
			),
			'color_alt_content_border' => array(
				'type' => 'color',
				'clear_pos' => 'left',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => us_translate( 'Border' ),
			),
			'color_alt_content_heading' => array(
				'type' => 'color',
				'clear_pos' => 'left',
				'with_gradient' => TRUE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => __( 'Headings', 'us' ),
			),
			'color_alt_content_text' => array(
				'type' => 'color',
				'clear_pos' => 'left',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => us_translate( 'Text' ),
			),
			'color_alt_content_link' => array(
				'type' => 'color',
				'clear_pos' => 'left',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => us_translate( 'Link' ),
			),
			'color_alt_content_link_hover' => array(
				'type' => 'color',
				'clear_pos' => 'left',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => __( 'Link on hover', 'us' ),
			),
			'color_alt_content_primary' => array(
				'type' => 'color',
				'clear_pos' => 'left',
				'with_gradient' => TRUE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => __( 'Primary Color', 'us' ),
			),
			'color_alt_content_secondary' => array(
				'type' => 'color',
				'clear_pos' => 'left',
				'with_gradient' => TRUE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => __( 'Secondary Color', 'us' ),
			),
			'color_alt_content_faded' => array(
				'type' => 'color',
				'clear_pos' => 'left',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => __( 'Faded Text', 'us' ),
			),
			'color_alt_content_overlay' => array(
				'type' => 'color',
				'clear_pos' => 'left',
				'with_gradient' => TRUE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'std' => 'rgba(0,0,0,0.75)',
				'text' => __( 'Background Overlay', 'us' ),
			),
			'change_alt_content_colors_end' => array(
				'type' => 'wrapper_end',
			),

			// Footer colors
			'change_footer_colors_start' => array(
				'type' => 'wrapper_start',
				'classes' => 'for_colors',
			),
			'h_colors_6' => array(
				'title' => __( 'Footer colors', 'us' ),
				'type' => 'heading',
				'classes' => 'with_separator sticky',
			),
			'color_footer_bg' => array(
				'type' => 'color',
				'with_gradient' => TRUE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => us_translate_x( 'Background', 'custom background' ),
			),
			'color_footer_bg_alt' => array(
				'type' => 'color',
				'with_gradient' => TRUE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => __( 'Alternate Background', 'us' ),
			),
			'color_footer_border' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => us_translate( 'Border' ),
			),
			'color_footer_heading' => array(
				'type' => 'color',
				'with_gradient' => TRUE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => __( 'Headings', 'us' ),
			),
			'color_footer_text' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => us_translate( 'Text' ),
			),
			'color_footer_link' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => us_translate( 'Link' ),
			),
			'color_footer_link_hover' => array(
				'type' => 'color',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => __( 'Link on hover', 'us' ),
			),
			'change_footer_colors_end' => array(
				'type' => 'wrapper_end',
			),

			// Alternate Footer colors
			'change_subfooter_colors_start' => array(
				'type' => 'wrapper_start',
				'classes' => 'for_colors',
			),
			'h_colors_5' => array(
				'title' => __( 'Alternate Footer colors', 'us' ),
				'type' => 'heading',
				'classes' => 'with_separator sticky',
			),
			'color_subfooter_bg' => array(
				'type' => 'color',
				'clear_pos' => 'left',
				'with_gradient' => TRUE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => us_translate_x( 'Background', 'custom background' ),
			),
			'color_subfooter_bg_alt' => array(
				'type' => 'color',
				'clear_pos' => 'left',
				'with_gradient' => TRUE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => __( 'Alternate Background', 'us' ),
			),
			'color_subfooter_border' => array(
				'type' => 'color',
				'clear_pos' => 'left',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => us_translate( 'Border' ),
			),
			'color_subfooter_heading' => array(
				'type' => 'color',
				'clear_pos' => 'left',
				'with_gradient' => TRUE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => __( 'Headings', 'us' ),
			),
			'color_subfooter_text' => array(
				'type' => 'color',
				'clear_pos' => 'left',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => us_translate( 'Text' ),
			),
			'color_subfooter_link' => array(
				'type' => 'color',
				'clear_pos' => 'left',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => us_translate( 'Link' ),
			),
			'color_subfooter_link_hover' => array(
				'type' => 'color',
				'clear_pos' => 'left',
				'with_gradient' => FALSE,
				'exclude_dynamic_colors' => $color_scheme_exclude_dynamic_colors,
				'text' => __( 'Link on hover', 'us' ),
			),
			'change_subfooter_colors_end' => array(
				'type' => 'wrapper_end',
			),
		),
	),

	// Typography
	'typography' => us_config( 'theme-options/typography' ),

	// Button Styles
	'buttons' => us_config( 'theme-options/buttons' ),

	// Field Styles
	'input_fields' => us_config( 'theme-options/input_fields', array(), TRUE ),

	// Portfolio
	'portfolio' => array(
		'title' => __( 'Portfolio', 'us' ) . $renamed_portfolio_label,
		'place_if' => ! empty( $usof_options['enable_portfolio'] ),
		'fields' => array(

			'portfolio_breadcrumbs_page' => array(
				'title' => __( 'Intermediate Breadcrumbs Page', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'options' => us_array_merge(
					array( '' => '&ndash; ' . us_translate( 'None' ) . ' &ndash;' ), $us_page_list
				),
				'std' => '',
			),

			// Slugs
			'portfolio_slug' => array(
				'title' => __( 'Portfolio Page Slug', 'us' ),
				'title_pos' => 'side',
				'type' => 'text',
				'std' => 'portfolio',
			),
			'portfolio_category_slug' => array(
				'title' => __( 'Portfolio Category Slug', 'us' ),
				'title_pos' => 'side',
				'type' => 'text',
				'std' => 'portfolio_category',
				'classes' => 'for_above',
			),
			'portfolio_tag_slug' => array(
				'title' => __( 'Portfolio Tag Slug', 'us' ),
				'title_pos' => 'side',
				'type' => 'text',
				'std' => 'portfolio_tag',
				'classes' => 'for_above',
			),
			'portfolio_slug_ignore_prefix' => array(
				'switch_text' => __( 'Ignore the prefix of the post permalink structure', 'us' ),
				'type' => 'switch',
				'std' => 0,
			),

			// Rename Portfolio
			'portfolio_rename' => array(
				'switch_text' => sprintf( __( 'Rename "%s" labels', 'us' ), __( 'Portfolio', 'us' ) ),
				'type' => 'switch',
				'std' => 0,
			),
			'portfolio_label_name' => array(
				'title' => __( 'Portfolio', 'us' ),
				'title_pos' => 'side',
				'std' => __( 'Portfolio', 'us' ),
				'type' => 'text',
				'classes' => 'for_above',
				'show_if' => array( 'portfolio_rename', '=', 1 ),
			),
			'portfolio_label_singular_name' => array(
				'title' => __( 'Portfolio Page', 'us' ),
				'title_pos' => 'side',
				'std' => __( 'Portfolio Page', 'us' ),
				'type' => 'text',
				'classes' => 'for_above',
				'show_if' => array( 'portfolio_rename', '=', 1 ),
			),
			'portfolio_label_add_new' => array(
				'title' => __( 'Add Portfolio Page', 'us' ),
				'title_pos' => 'side',
				'std' => __( 'Add Portfolio Page', 'us' ),
				'type' => 'text',
				'classes' => 'for_above',
				'show_if' => array( 'portfolio_rename', '=', 1 ),
			),
			'portfolio_label_edit_item' => array(
				'title' => __( 'Edit Portfolio Page', 'us' ),
				'title_pos' => 'side',
				'std' => __( 'Edit Portfolio Page', 'us' ),
				'type' => 'text',
				'classes' => 'for_above',
				'show_if' => array( 'portfolio_rename', '=', 1 ),
			),
			'portfolio_label_category' => array(
				'title' => __( 'Portfolio Categories', 'us' ),
				'title_pos' => 'side',
				'std' => __( 'Portfolio Categories', 'us' ),
				'type' => 'text',
				'classes' => 'for_above',
				'show_if' => array( 'portfolio_rename', '=', 1 ),
			),
			'portfolio_label_tag' => array(
				'title' => __( 'Portfolio Tags', 'us' ),
				'title_pos' => 'side',
				'std' => __( 'Portfolio Tags', 'us' ),
				'type' => 'text',
				'classes' => 'for_above',
				'show_if' => array( 'portfolio_rename', '=', 1 ),
			),
		),
	),

	// Shop
	'woocommerce' => array(
		'title' => us_translate_x( 'Shop', 'Page title', 'woocommerce' ),
		'place_if' => class_exists( 'woocommerce' ),
		'fields' => array(

			// Global Settings
			'h_more' => array(
				'title' => us_translate( 'Global Settings' ),
				'type' => 'heading',
				'classes' => 'with_separator sticky',
			),
			'shop_catalog' => array(
				'title' => __( 'Catalog Mode', 'us' ),
				'title_pos' => 'side',
				'type' => 'switch',
				'switch_text' => sprintf( __( 'Remove "%s" buttons', 'us' ), us_translate( 'Add to cart', 'woocommerce' ) ),
				'std' => 0,
			),
			'ajax_add_to_cart' => array(
				'title' => us_translate( 'Add to cart behaviour', 'woocommerce' ),
				'title_pos' => 'side',
				'type' => 'switch',
				'switch_text' => __( 'Enable AJAX add to cart buttons on product pages', 'us' ),
				'std' => 0,
				'show_if' => array( 'shop_catalog', '=', 0 ),
			),
			'shop_primary_btn_style' => array(
				'title' => __( 'Primary Buttons Style', 'us' ),
				'title_pos' => 'side',
				'description' => '<a href="' . admin_url() . 'admin.php?page=us-theme-options#buttons">' . __( 'Edit Button Styles', 'us' ) . '</a>',
				'type' => 'select',
				'options' => us_get_btn_styles(),
				'std' => '1',
			),
			'shop_secondary_btn_style' => array(
				'title' => __( 'Secondary Buttons Style', 'us' ),
				'title_pos' => 'side',
				'description' => '<a href="' . admin_url() . 'admin.php?page=us-theme-options#buttons">' . __( 'Edit Button Styles', 'us' ) . '</a>',
				'type' => 'select',
				'options' => us_get_btn_styles(),
				'std' => '2',
			),

			// Product gallery
			'product_gallery' => array(
				'title' => us_translate( 'Product gallery', 'woocommerce' ),
				'title_pos' => 'side',
				'type' => 'radio',
				'options' => array(
					'slider' => __( 'Slider', 'us' ),
					'gallery' => us_translate( 'Gallery' ),
				),
				'std' => 'slider',
			),
			'wrapper_product_gallery_start' => array(
				'type' => 'wrapper_start',
				'classes' => 'force_right',
			),
			'product_gallery_options' => array(
				'type' => 'checkboxes',
				'options' => array(
					'zoom' => __( 'Zoom images on hover', 'us' ),
					'lightbox' => __( 'Allow Full Screen view', 'us' ),
				),
				'std' => 'zoom,lightbox',
				'classes' => 'vertical',
			),
			'product_gallery_fullscreen_color' => array(
				'type' => 'color',
				'title' => __( 'Background Color in Full Screen view', 'us' ),
				'std' => '#000000',
				'show_if' => array( 'product_gallery_options', 'str_contains', 'lightbox' ),
			),
			'product_gallery_aspect_ratio' => array(
				'title' => __( 'Image Aspect Ratio', 'us' ),
				'type' => 'select',
				'options' => array(
					'auto' => __( 'Initial', 'us' ),
					'1' => '1:1 ' . __( 'square', 'us' ),
					'4/3' => '4:3 ' . __( 'landscape', 'us' ),
					'3/2' => '3:2 ' . __( 'landscape', 'us' ),
					'16/9' => '16:9 ' . __( 'landscape', 'us' ),
					'2/3' => '2:3 ' . __( 'portrait', 'us' ),
					'3/4' => '3:4 ' . __( 'portrait', 'us' ),
					'custom' => __( 'Custom', 'us' ),
				),
				'std' => 'auto',
				'cols' => 2,
			),
			'product_gallery_img_size' => array(
				'title' => __( 'Image Size', 'us' ),
				'description' => '<a href="#image_sizes">' . __( 'Edit image sizes', 'us' ) . '</a>.',
				'type' => 'select',
				'options' => $image_sizes_list ?? array(),
				'std' => 'woocommerce_single',
				'classes' => 'desc_4',
				'cols' => 2,
			),
			'product_gallery_aspect_ratio_custom' => array(
				'description' => $misc['desc_aspect_ratio'],
				'type' => 'text',
				'std' => '21/9',
				'classes' => 'for_above',
				'cols' => 2,
				'show_if' => array( 'product_gallery_aspect_ratio', '=', 'custom' ),
			),
			'product_gallery_arrows' => array(
				'type' => 'switch',
				'switch_text' => __( 'Prev/Next arrows', 'us' ),
				'std' => 0,
				'show_if' => array( 'product_gallery', '=', 'slider' ),
			),
			'product_gallery_thumbs_aspect_ratio' => array(
				'title' => __( 'Thumbnails Aspect Ratio', 'us' ),
				'type' => 'select',
				'options' => array(
					'auto' => __( 'Initial', 'us' ),
					'1' => '1:1 ' . __( 'square', 'us' ),
					'4/3' => '4:3 ' . __( 'landscape', 'us' ),
					'3/2' => '3:2 ' . __( 'landscape', 'us' ),
					'16/9' => '16:9 ' . __( 'landscape', 'us' ),
					'2/3' => '2:3 ' . __( 'portrait', 'us' ),
					'3/4' => '3:4 ' . __( 'portrait', 'us' ),
					'custom' => __( 'Custom', 'us' ),
				),
				'std' => 'auto',
				'cols' => 2,
				'show_if' => array( 'product_gallery', '=', 'slider' ),
			),
			'product_gallery_thumbs_img_size' => array(
				'title' => __( 'Thumbnails Image Size', 'us' ),
				'description' => '<a href="#image_sizes">' . __( 'Edit image sizes', 'us' ) . '</a>.',
				'type' => 'select',
				'options' => $image_sizes_list ?? array(),
				'std' => 'woocommerce_thumbnail',
				'classes' => 'desc_4',
				'cols' => 2,
				'show_if' => array( 'product_gallery', '=', 'slider' ),
			),
			'product_gallery_thumbs_aspect_ratio_custom' => array(
				'description' => $misc['desc_aspect_ratio'],
				'type' => 'text',
				'std' => '21/9',
				'classes' => 'for_above',
				'cols' => 2,
				'show_if' => array( 'product_gallery_thumbs_aspect_ratio', '=', 'custom' ),
			),
			'product_gallery_thumbs_pos' => array(
				'title' => __( 'Thumbnails Position', 'us' ),
				'type' => 'radio',
				'options' => array(
					'bottom' => us_translate( 'Bottom' ),
					'left' => us_translate( 'Left' ),
				),
				'std' => 'bottom',
				'show_if' => array( 'product_gallery', '=', 'slider' ),
			),
			'product_gallery_thumbs_cols' => array(
				'title' => __( 'Number of Items to Show', 'us' ),
				'type' => 'slider',
				'options' => array(
					'' => array(
						'min' => 2,
						'max' => 8,
					),
				),
				'std' => '4',
				'show_if' => array(
					array( 'product_gallery', '=', 'slider' ),
					'and',
					array( 'product_gallery_thumbs_pos', '=', 'bottom' ),
				),
			),
			'product_gallery_thumbs_width' => array(
				'title' => __( 'Thumbnails Width', 'us' ),
				'type' => 'slider',
				'options' => array(
					'px' => array(
						'min' => 40,
						'max' => 200,
					),
					'rem' => array(
						'min' => 3,
						'max' => 15,
						'step' => 0.1,
					),
				),
				'std' => '6rem',
				'show_if' => array(
					array( 'product_gallery', '=', 'slider' ),
					'and',
					array( 'product_gallery_thumbs_pos', '=', array( 'left', 'right' ) ),
				),
			),
			'product_gallery_thumbs_gap' => array(
				'title' => __( 'Gap between Thumbnails', 'us' ),
				'type' => 'slider',
				'options' => array(
					'px' => array(
						'min' => 0,
						'max' => 20,
					),
				),
				'std' => '4px',
				'show_if' => array( 'product_gallery', '=', 'slider' ),
			),
			'product_gallery_thumbs_nav_style' => array(
				'title' => __( 'Thumbnails Prev/Next arrows', 'us' ),
				'type' => 'select',
				'options' => array(
					'style_1' => sprintf( '&ndash; %s 1 &ndash;', us_translate( 'Style' ) ),
					'style_2' => sprintf( '&ndash; %s 2 &ndash;', us_translate( 'Style' ) ),
					'style_3' => sprintf( '&ndash; %s 3 &ndash;', us_translate( 'Style' ) ),
				),
				'std' => 'style_1',
				'show_if' => array( 'product_gallery', '=', 'slider' ),
			),
			'product_gallery_cols' => array(
				'title' => us_translate( 'Columns' ),
				'type' => 'slider',
				'options' => array(
					'' => array(
						'min' => 1,
						'max' => 12,
					),
				),
				'std' => '1',
				'show_if' => array( 'product_gallery', '=', 'gallery' ),
			),
			'product_gallery_gap' => array(
				'title' => __( 'Gap between Images', 'us' ),
				'type' => 'slider',
				'options' => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
					'rem' => array(
						'min' => 0.0,
						'max' => 4.0,
						'step' => 0.1,
					),
				),
				'std' => '1.5rem',
				'show_if' => array( 'product_gallery', '=', 'gallery' ),
			),
			'wrapper_product_gallery_end' => array(
				'type' => 'wrapper_end',
			),

			// Products
			'h_product' => array(
				'title' => us_translate( 'Products', 'woocommerce' ),
				'type' => 'heading',
				'classes' => 'with_separator sticky',
			),
			'header_product_id' => array(
				'title' => _x( 'Header', 'site top area', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'hints_for' => 'us_header',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Pages', 'us' ) . ' &ndash;',
						'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
					), $us_headers_list
				),
				'std' => '__defaults__',
			),
			'titlebar_product_id' => array(
				'title' => __( 'Titlebar', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'hints_for' => 'us_page_block',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Pages', 'us' ) . ' &ndash;',
						'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
					), $us_page_blocks_list
				),
				'std' => '__defaults__',
				'place_if' => $sidebar_titlebar_are_enabled,
			),
			'content_product_id' => array(
				'title' => __( 'Page Template', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'hints_for' => 'us_page_block',
				'options' => us_array_merge(
					array(
						'' => '&ndash; ' . __( 'Default WooCommerce template', 'us' ) . ' &ndash;',
					), $us_content_templates_list
				),
				'std' => '',
			),
			'sidebar_product_id' => array(
				'title' => __( 'Sidebar', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Pages', 'us' ) . ' &ndash;',
						'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
					), $sidebars_list
				),
				'std' => '__defaults__',
				'hints_for' => $sidebar_hints_for,
				'place_if' => $sidebar_titlebar_are_enabled,
			),
			'sidebar_product_pos' => array(
				'title_pos' => 'side',
				'type' => 'radio',
				'options' => array(
					'left' => us_translate( 'Left' ),
					'right' => us_translate( 'Right' ),
				),
				'std' => 'right',
				'classes' => 'for_above',
				'show_if' => array( 'sidebar_product_id', '!=', array( '', '__defaults__' ) ),
				'place_if' => $sidebar_titlebar_are_enabled,
			),
			'footer_product_id' => array(
				'title' => __( 'Footer', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'hints_for' => 'us_page_block',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Pages', 'us' ) . ' &ndash;',
						'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
					), $us_page_blocks_list
				),
				'std' => '__defaults__',
			),

			// Shop page
			'h_shop' => array(
				'title' => us_translate( 'Shop Page', 'woocommerce' ),
				'type' => 'heading',
				'classes' => 'with_separator sticky',
			),
			'header_shop_id' => array(
				'title' => _x( 'Header', 'site top area', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'hints_for' => 'us_header',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Pages', 'us' ) . ' &ndash;',
						'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
					), $us_headers_list
				),
				'std' => '__defaults__',
			),
			'titlebar_shop_id' => array(
				'title' => __( 'Titlebar', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'hints_for' => 'us_page_block',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Pages', 'us' ) . ' &ndash;',
						'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
					), $us_page_blocks_list
				),
				'std' => '__defaults__',
				'place_if' => $sidebar_titlebar_are_enabled,
			),
			'content_shop_id' => array(
				'title' => __( 'Page Template', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'hints_for' => 'us_page_block',
				'options' => us_array_merge(
					array(
						'' => '&ndash; ' . __( 'Default WooCommerce template', 'us' ) . ' &ndash;',
					), $us_content_templates_list
				),
				'std' => '',
			),
			'wrapper_shop_start' => array(
				'type' => 'wrapper_start',
				'classes' => 'force_right',
				'show_if' => array( 'content_shop_id', '=', '' ),
			),
			'shop_columns' => array(
				'title' => us_translate( 'Columns' ),
				'type' => 'radio',
				'options' => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				),
				'std' => '3',
			),
			'wrapper_shop_end' => array(
				'type' => 'wrapper_end',
			),
			'sidebar_shop_id' => array(
				'title' => __( 'Sidebar', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Pages', 'us' ) . ' &ndash;',
						'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
					), $sidebars_list
				),
				'std' => '__defaults__',
				'hints_for' => $sidebar_hints_for,
				'place_if' => $sidebar_titlebar_are_enabled,
			),
			'sidebar_shop_pos' => array(
				'title_pos' => 'side',
				'type' => 'radio',
				'options' => array(
					'left' => us_translate( 'Left' ),
					'right' => us_translate( 'Right' ),
				),
				'std' => 'right',
				'classes' => 'for_above',
				'show_if' => array( 'sidebar_shop_id', '!=', array( '', '__defaults__' ) ),
				'place_if' => $sidebar_titlebar_are_enabled,
			),
			'footer_shop_id' => array(
				'title' => __( 'Footer', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'hints_for' => 'us_page_block',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Pages', 'us' ) . ' &ndash;',
						'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
					), $us_page_blocks_list
				),
				'std' => '__defaults__',
			),

			// Products Search Results Page
			'h_shop_search' => array(
				'title' => __( 'Products Search Results Page', 'us' ),
				'type' => 'heading',
				'classes' => 'with_separator sticky',
			),
			'header_shop_search_id' => array(
				'title' => _x( 'Header', 'site top area', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'hints_for' => 'us_header',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Shop Page', 'us' ) . ' &ndash;',
						'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
					), $us_headers_list
				),
				'std' => '__defaults__',
			),
			'titlebar_shop_search_id' => array(
				'title' => __( 'Titlebar', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'hints_for' => 'us_page_block',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Shop Page', 'us' ) . ' &ndash;',
						'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
					), $us_page_blocks_list
				),
				'std' => '__defaults__',
				'place_if' => $sidebar_titlebar_are_enabled,
			),
			'content_shop_search_id' => array(
				'title' => __( 'Page Template', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'hints_for' => 'us_page_block',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Shop Page', 'us' ) . ' &ndash;',
					), $us_content_templates_list
				),
				'std' => '',
			),
			'sidebar_shop_search_id' => array(
				'title' => __( 'Sidebar', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Shop Page', 'us' ) . ' &ndash;',
						'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
					), $sidebars_list
				),
				'std' => '__defaults__',
				'hints_for' => $sidebar_hints_for,
				'place_if' => $sidebar_titlebar_are_enabled,
			),
			'sidebar_shop_search_pos' => array(
				'title_pos' => 'side',
				'type' => 'radio',
				'options' => array(
					'left' => us_translate( 'Left' ),
					'right' => us_translate( 'Right' ),
				),
				'std' => 'right',
				'classes' => 'for_above',
				'show_if' => array( 'sidebar_shop_search_id', '!=', array( '', '__defaults__' ) ),
				'place_if' => $sidebar_titlebar_are_enabled,
			),
			'footer_shop_search_id' => array(
				'title' => __( 'Footer', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'hints_for' => 'us_page_block',
				'options' => us_array_merge(
					array(
						'__defaults__' => '&ndash; ' . __( 'As in Shop Page', 'us' ) . ' &ndash;',
						'' => '&ndash; ' . __( 'Do not display', 'us' ) . ' &ndash;',
					), $us_page_blocks_list
				),
				'std' => '__defaults__',
			),

		)
		+ $shop_layout_config +
		array(

			// Orders template
			'h_order' => array(
				'title' => us_translate_x( 'Orders', 'Admin menu name', 'woocommerce' ),
				'description' => sprintf( __( 'Selected template will be applied to the "%s" page.', 'us' ), us_translate( 'Checkout', 'woocommerce' ) . ' &rarr; ' . us_translate( 'Order received', 'woocommerce' ) ),
				'type' => 'heading',
				'classes' => 'with_separator sticky',
			),
			'content_order_id' => array(
				'title' => __( 'Page Template', 'us' ),
				'title_pos' => 'side',
				'type' => 'select',
				'hints_for' => 'us_content_template',
				'options' => us_array_merge(
					array(
						'' => '&ndash; ' . __( 'Default WooCommerce template', 'us' ) . ' &ndash;',
					), $us_content_templates_list
				),
				'std' => '',
			),

			// Cart page
			'h_cart' => array(
				'title' => us_translate( 'Cart Page', 'woocommerce' ),
				'type' => 'heading',
				'classes' => 'with_separator sticky',
			),
			'shop_cart' => array(
				'title' => __( 'Layout', 'us' ),
				'title_pos' => 'side',
				'type' => 'radio',
				'options' => array(
					'standard' => __( 'Standard', 'us' ),
					'compact' => __( 'Compact', 'us' ),
				),
				'std' => 'compact',
			),
			'product_related_qty' => array(
				'title' => us_translate( 'Cross-sells', 'woocommerce' ),
				'title_pos' => 'side',
				'type' => 'radio',
				'options' => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				),
				'std' => '3',
			),
		),
	),

	// Icons
	'icons' => us_config( 'theme-options/icons' ),

	// Image Sizes
	'image_sizes' => us_config( 'theme-options/image_sizes' ),

	// Advanced
	'advanced' => us_config( 'theme-options/advanced', array(), TRUE ),

	// Custom Code
	'code' => array(
		'title' => __( 'Custom Code', 'us' ),
		'fields' => array(
			'custom_css' => array(
				'title' => __( 'Custom CSS', 'us' ),
				'description' => sprintf( __( 'CSS code from this field will overwrite theme styles. It will be located inside the %s tags just before the %s tag of every site page.', 'us' ), '<code>&lt;style&gt;&lt;/style&gt;</code>', '<code>&lt;/head&gt;</code>' ),
				'type' => 'css',
				'std' => '',
				'classes' => 'desc_4',
			),
			'custom_html_head' => array(
				'title' => sprintf( __( 'Code before %s', 'us' ), '&lt;/head&gt;' ),
				'description' => sprintf( __( 'Use this field for Google Analytics code or other tracking code. If you paste custom JavaScript, use it inside the %s tags.', 'us' ), '<code>&lt;script&gt;&lt;/script&gt;</code>' ) . '<br><br>' . sprintf( __( 'Content from this field will be located just before the %s tag of every site page.', 'us' ), '<code>&lt;/head&gt;</code>' ),
				'type' => 'html',
				'std' => '',
				'classes' => 'desc_4',
			),
			'custom_html_body' => array(
				'title' => sprintf( __( 'Code after %s', 'us' ), '&lt;body&gt;' ),
				'description' => sprintf( __( 'Use this field for Google Analytics code or other tracking code. If you paste custom JavaScript, use it inside the %s tags.', 'us' ), '<code>&lt;script&gt;&lt;/script&gt;</code>' ) . '<br><br>' . sprintf( __( 'Content from this field will be located just after the %s tag of every site page.', 'us' ), '<code>&lt;body&gt;</code>' ),
				'type' => 'html',
				'std' => '',
				'classes' => 'desc_4',
			),
			'custom_html' => array(
				'title' => sprintf( __( 'Code before %s', 'us' ), '&lt;/body&gt;' ),
				'description' => sprintf( __( 'Use this field for Google Analytics code or other tracking code. If you paste custom JavaScript, use it inside the %s tags.', 'us' ), '<code>&lt;script&gt;&lt;/script&gt;</code>' ) . '<br><br>' . sprintf( __( 'Content from this field will be located just before the %s tag of every site page.', 'us' ), '<code>&lt;/body&gt;</code>' ),
				'type' => 'html',
				'std' => '',
				'classes' => 'desc_4',
			),
		),
	),

	'manage' => array(
		'title' => __( 'Manage Options', 'us' ),
		'fields' => array(
			'of_backup' => array(
				'title' => __( 'Backup Theme Options', 'us' ),
				'title_pos' => 'side',
				'type' => 'backup',
			),
			'of_transfer' => array(
				'title' => __( 'Transfer Theme Options', 'us' ),
				'title_pos' => 'side',
				'type' => 'transfer',
				'description' => __( 'You can transfer the saved options data between different installations by copying the text inside the text box. To import data from another installation, replace the data in the text box with the one from another installation and click "Import Options".', 'us' ),
				'classes' => 'desc_3',
			),
			'of_reset' => array(
				'title' => __( 'Reset Theme Options', 'us' ),
				'title_pos' => 'side',
				'type' => 'reset',
			),
		),
	),

	'white_label' => $white_label_config,
);

// Generate a list of predefined color slugs, which cannot be used in Global Custom Colors
$reserved_color_slugs = array();
foreach ( array_keys( $theme_options_config['colors']['fields'] ) as $field_name ) {
	if ( strpos( $field_name, 'color_' ) === 0 ) {
		$reserved_color_slugs[] = substr( $field_name, strlen( 'color' ) );
	}
}
$theme_options_config['colors']['fields']['custom_colors']['params']['slug']['unique_value'] = $reserved_color_slugs;

return $theme_options_config;
