<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

// Typography style tag id in builder
if ( ! defined( 'US_BUILDER_TYPOGRAPHY_TAG_ID' ) ) {
	define( 'US_BUILDER_TYPOGRAPHY_TAG_ID', 'usb-customize-fonts' );
}

if ( ! function_exists( 'usb_get_post_id' ) ) {
	/**
	 * Get the ID of the post or term you are edit
	 *
	 * @return int Returns, if successful, post_id or term_id, otherwise zero
	 */
	function usb_get_post_id() {
		if ( usb_is_builder_page() OR usb_is_preview() ) {
			return (int) us_arr_path( $_REQUEST, 'post', get_queried_object_id() );
		}
		return 0;
	}
}

if ( ! function_exists( 'usb_is_post_editing' ) ) {
	/**
	 * Determines if this is a post edit page
	 *
	 * @return bool Returns TRUE if this is a post edit page, otherwise FALSE
	 */
	function usb_is_post_editing() {
		// Action definitions based on referer link for AJAX requests
		if ( wp_doing_ajax() ) {
			$url_params = (string) wp_parse_url( us_get_safe_var( 'HTTP_REFERER' ), PHP_URL_QUERY );
			return $url_params AND strpos( $url_params, '&action=us-builder' ) !== FALSE;
		}
		global $pagenow;
		return (
			us_strtolower( basename( $pagenow, '.php' ) ) == 'post'
			AND isset( $_REQUEST['post'] )
			AND us_strtolower( us_arr_path( $_REQUEST, 'action' ) ) == 'us-builder'
		);
	}
}

if ( ! function_exists( 'usb_is_site_settings' ) ) {
	/**
	 * Determines if this is a site settings edit page
	 *
	 * @return bool Returns TRUE if this is the site settings edit page, otherwise FALSE
	 */
	function usb_is_site_settings() {
		// Action definitions based on referer link for AJAX requests
		if ( wp_doing_ajax() ) {
			$url_params = (string) wp_parse_url( us_get_safe_var( 'HTTP_REFERER' ), PHP_URL_QUERY );
			return $url_params AND strpos( $url_params, '&action=us-site-settings' ) !== FALSE;
		}
		global $pagenow;
		return (
			us_strtolower( basename( $pagenow, '.php' ) ) == 'post'
			AND isset( $_REQUEST['post'] )
			AND us_strtolower( us_arr_path( $_REQUEST, 'action' ) ) == 'us-site-settings'
		);
	}
}

if ( ! function_exists( 'usb_is_builder_page' ) ) {
	/**
	 * Determines if this is a builder page
	 *
	 * @return bool Returns TRUE if this is a builder page, otherwise FALSE
	 */
	function usb_is_builder_page() {
		$is_builder_page = (
			usb_is_post_editing()
			OR usb_is_site_settings()
		);
		return (bool) apply_filters( 'usb_is_builder_page', $is_builder_page );
	}
}

if ( ! function_exists( 'usb_is_post_preview' ) ) {
	/**
	 * Determines if builder preview page is shown for Reusable Block or Page Template
	 *
	 * @return Returns TRUE if the current page is a preview in the builder, otherwise FALSE
	 */
	function usb_is_post_preview() {
		// Preview page definitions via query params
		if ( $nonce = us_arr_path( $_REQUEST, 'us-builder' ) ) {
			return (bool) wp_verify_nonce( $nonce, 'us-builder' );
		}
		// Preview page definitions via action in AJAX requests
		if ( wp_doing_ajax() ) {
			// Note: USBuilder_Ajax::get_action( 'action_render_shortcode' );
			return us_arr_path( $_REQUEST, 'action' ) == 'usb_render_shortcode';
		}

		return FALSE;
	}
}

if ( ! function_exists( 'usb_is_site_settings_preview' ) ) {
	/**
	 * Determines if builder preview site is shown
	 *
	 * @return bool TRUE if builder preview site, FALSE otherwise
	 */
	function usb_is_site_settings_preview() {
		// Preview page definitions via query params
		if ( $nonce = us_arr_path( $_REQUEST, 'us-site-settings' ) ) {
			return (bool) wp_verify_nonce( $nonce, 'us-site-settings' );
		}
		// Get a referer link on the basis of which we will try to determine the preview of the site
		$referer = us_get_safe_var( 'HTTP_REFERER' );
		if ( ! empty( $referer ) AND strpos( $referer, 'us-site-settings' ) !== FALSE ) {
			parse_str( wp_parse_url( $referer, PHP_URL_QUERY ), $params );
			if ( isset( $params[ 'us-site-settings' ] ) ) {
				return (bool) wp_verify_nonce( $params[ 'us-site-settings' ], 'us-site-settings' );
			}
		}

		return FALSE;
	}
}

if ( ! function_exists( 'usb_is_preview' ) ) {
	/**
	 * Determines if builder preview site or page is shown
	 *
	 * @return bool TRUE if builder preview site or page, FALSE otherwise
	 */
	function usb_is_preview() {
		return (
			usb_is_post_preview()
			OR usb_is_site_settings_preview()
		);
	}
}

if ( ! function_exists( 'usb_is_search_preview' ) ) {
	/**
	 * Determines if preview page for search page
	 *
	 * @return bool TRUE if search page, FALSE otherwise
	 */
	function usb_is_search_preview() {
		$post_id = usb_get_post_id();
		if ( $post_id AND $post_id == us_get_option( 'search_page' ) ) {
			return TRUE;
		}
		return FALSE;
	}
}

if ( ! function_exists( 'usb_is_template_preview' ) ) {
	/**
	 * Determines if builder preview page is shown for Reusable Block or Page Template
	 *
	 * @return bool TRUE if builder page, FALSE otherwise
	 */
	function usb_is_template_preview() {
		if ( usb_is_post_preview() ) {
			$post_type = get_post_type( usb_get_post_id() );
			if ( $post_type AND in_array( $post_type, array( 'us_page_block', 'us_content_template' ) ) ) {
				return TRUE;
			}
		}
		return FALSE;
	}
}

if ( ! function_exists( 'usb_post_editing_is_locked' ) ) {
	/**
	 * Determine if a post editing is locked
	 * Note: This method only works after WP is initialized!
	 *
	 * @return bool Returns true if the post editing is locked, false otherwise
	 */
	function usb_post_editing_is_locked() {
		if ( ! $post_id = usb_get_post_id() ) {
			return FALSE;
		}
		return (bool) wp_check_post_lock( $post_id );
	}
}

if ( ! function_exists( 'usb_get_active_panel_name' ) ) {
	/**
	 * Get active panel name
	 *
	 * @return string Returns the name of the active panel
	 */
	function usb_get_active_panel_name() {
		return us_arr_path( $_REQUEST, 'section', /* default */'add_elms' );
	}
}

if ( ! function_exists( 'usb_get_edit_link' ) ) {
	/**
	 * Get edit link in Live Builder
	 *
	 * @param int $post_id The post ID
	 * @param array $params The additional parameters for the URL
	 * @return string Returns a link to edits in builder
	 */
	function usb_get_edit_link( $post_id, $params = array() ) {
		if ( $post_id === 0 ) {
			return '';
		}
		$default_params = array(
			'post' => (int) $post_id,
			'action' => 'us-builder'
		);
		return apply_filters(
			'usb_get_edit_link',
			admin_url( 'post.php?' . build_query( array_merge( $default_params, (array) $params ) ) ),
			$params,
			$default_params
		);
	}
}

if ( ! function_exists( 'usb_disable_query_monitor_on_preview_page' ) ) {

	if ( ! defined( 'US_DEV_ENABLE_QM_IN_LIVE_BUILDER' ) ) {
		add_filter( 'plugins_loaded', 'usb_disable_query_monitor_on_preview_page', /* before init QM */1 );
	}

	/**
	 * Disable QueryMonitor on preview page
	 */
	function usb_disable_query_monitor_on_preview_page() {
		if ( class_exists( 'QueryMonitor' ) AND usb_is_preview() ) {
			// see https://github.com/johnbillion/query-monitor/blob/develop/classes/QueryMonitor.php#L16
			remove_action( 'plugins_loaded', array( QueryMonitor::init(), 'action_plugins_loaded' ) );
		}
	}
}

if ( ! function_exists( 'usb_extend_basic_options_to_show_previews' ) ) {

	add_filter( 'usof_load_options_once', 'usb_extend_basic_options_to_show_previews', 10, 1 );

	/**
	 * Extend the basic options to show previews
	 *
	 * @param array $usof_options The usof options
	 * @return array Returns advanced usof options
	 */
	function usb_extend_basic_options_to_show_previews( $usof_options ) {
		// Check if we are on the preview site
		if ( ! usb_is_site_settings_preview() ) {
			return $usof_options;
		}

		/**
		 * @var string Cookie name where options for previews are stored
		 */
		$_cookie_name = 'usb_preview_site_options';

		// Check the availability of live options for preview
		if ( ! isset( $_COOKIE ) OR empty( $_COOKIE[ $_cookie_name ] ) ) {
			return $usof_options;
		}

		// If there are options and not a preview context, then delete cookies and exit the function
		if ( ! usb_is_preview() ) {
			unset( $_COOKIE[ $_cookie_name ] );
			return $usof_options;
		}

		// Get live options and extend usod_options
		$preview_site_options = us_arr_path( $_COOKIE, $_cookie_name );
		$preview_site_options = json_decode( base64_decode( $preview_site_options ), /* as array */TRUE );
		if ( ! is_array( $preview_site_options ) ) {
			return $usof_options;
		}

		// If the parameters have not changed, return without change
		if ( $preview_site_options === us_get_live_options() ) {
			return $usof_options;
		}

		return us_array_merge( $usof_options, $preview_site_options );
	}
}


if ( ! function_exists( 'usb_get_dropzone_html' ) ) {

	/**
	 * Dropzone in preview.
	 *
	 * @return string
	 */
	function usb_get_dropzone_html() {

		$text_and_navigation = __( 'Drag here:', 'us' );
		$text_and_navigation .= ' <span class="usbp_action show_tab_elements">' . strip_tags( __( 'Elements', 'us' ) ) . '</span>';

		if ( us_get_option( 'section_templates', 1 ) ) {
			$text_and_navigation .= ', <span class="usbp_action show_tab_templates">' . strip_tags( us_translate( 'Templates' ) ) . '</span>';
		}
		if ( us_get_option( 'section_favorites', 1 ) ) {
			$text_and_navigation .= ', <span class="usbp_action show_tab_favorites">' . strip_tags( _x( 'Favorites', 'Favorite Sections', 'us' ) ) . '</span>';
		}

		$output = '
			<section class="l-section wpb_row height_medium hidden" id="usb-dropzone-section">
				<div class="l-section-h">
					<div class="usb-dropzone">
						<div class="usb-dropzone-text">' . wp_kses_post( $text_and_navigation ) . '</div>
						<div class="usb-dropzone-buttons">
							<button type="button" class="usbp_action_add_row">' . strip_tags( __( 'Add Row/Section', 'us' ) ) . '</button>
							<button type="button" class="usbp_action_paste_here">' . strip_tags( __( 'Paste from this site', 'us' ) ) . '</button>
							<button type="button" class="usbp_action_open_paste_panel">' . strip_tags( __( 'Paste from another site', 'us' ) ) . '</button>
						</div>
					</div>
				</div>
			</section>
		';

		return apply_filters( 'usb_dropzone_html', $output );
	}
}
