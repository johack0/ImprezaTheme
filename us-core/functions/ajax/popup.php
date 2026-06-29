<?php defined( 'ABSPATH' ) or die( 'This script cannot be accessed directly.' );

add_action( 'wp_ajax_us_list_item_popup_content', 'us_list_item_popup_content' );
add_action( 'wp_ajax_nopriv_us_list_item_popup_content', 'us_list_item_popup_content' );

/**
 * Get popup content in loop elements via AJAX
 */
function us_list_item_popup_content() {

	global $post, $us_in_the_loop, $us_loop_item_type, $us_loop_term, $us_loop_user_ID;

	// Set environment for dynamic values
	$us_in_the_loop = TRUE;

	$popup_data = us_get_HTTP_POST_json( 'grid_layout_popup_data' );

	if ( isset( $popup_data['term_id'] ) ) {
		$us_loop_item_type = 'term';
		$us_loop_term = get_term( (int) $popup_data['term_id'] );
	}
	if ( isset( $popup_data['user_id'] ) ) {
		$us_loop_item_type = 'user';
		$us_loop_user_ID = (int) $popup_data['user_id'];
	}

	$popup_design_css = '';

	$post = get_post( $_POST['post_id'] ?? us_get_current_id() );

	if ( $post instanceof WP_Post ) {
		setup_postdata( $post );

		// Generate CSS from Design settings of inner popup elements
		if (
			$raw_design_settings = get_post_meta( $post->ID, '_us_jsoncss_data', TRUE )
			AND is_array( $raw_design_settings )
		) {
			$compiled_css_rules = array();

			foreach ( $raw_design_settings as $element_design_options ) {
				us_append_elm_design_settings( $element_design_options, $compiled_css_rules );
			}

			$popup_design_css = '<style>' . us_compile_css( $compiled_css_rules ) . '</style>';
		}
	}

	$popup_content = '';

	if ( isset( $popup_data['page_block_id'] ) ) {

		global $us_page_block_is_in_popup;
		$us_page_block_is_in_popup = TRUE;

		$popup_content = do_shortcode( '[us_page_block id="' . (int) $popup_data['page_block_id'] . '"]' );

		$us_page_block_is_in_popup = NULL;

	} elseif ( isset( $popup_data['popup_content'] ) AND strpos( $popup_data['popup_content'], '|' ) !== FALSE ) {

		$hash = strtok( $popup_data['popup_content'], '|' );
		$content_encoded = strtok( '|' );
		$content_decoded = '';

		// NOTE: Used hash as protection against substitution.
		if ( $hash === wp_hash( $content_encoded ) ) {
			$content_decoded = base64_decode( $content_encoded );
		}

		$popup_content = do_shortcode( wpautop( us_replace_dynamic_value( $content_decoded ) ) );
	}

	if ( $post instanceof WP_Post ) {
		wp_reset_postdata();
	}

	wp_send_json_success( $popup_design_css . $popup_content );
}
