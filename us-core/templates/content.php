<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Outputs page's content Page Template (us_content_template)
 *
 * (!) Should be called after the current $wp_query is already defined
 *
 * @action Before the template: 'us_before_template:templates/content'
 * @action After the template: 'us_after_template:templates/content'
 * @filter Template variables: 'us_template_vars:templates/content'
 */

// First check the Page Template set for Popup
if (
	$popup_template_id = filter_input( INPUT_GET, 'us_popup_page_template', FILTER_VALIDATE_INT )
	AND get_post_status( $popup_template_id ) == 'publish'
) {
	$page_template_id = $popup_template_id;

} else {
	$page_template_id = us_get_page_area_id( 'content' );
}

if ( ! $page_template_id ) {
	return;
}

$output = '';

if ( $page_template = get_post( (int) $page_template_id ) ) {

	us_open_wp_query_context();

	// Some WPML tweaks
	$translated_content_template_id = apply_filters( 'us_tr_object_id', $page_template->ID, 'us_content_template', TRUE );

	// Fallback for case when post type is not yet migrated
	if ( $page_template->post_type == 'us_page_block' ) {
		$translated_content_template_id = apply_filters( 'us_tr_object_id', $page_template->ID, 'us_page_block', TRUE );
	}
	if ( $translated_content_template_id != $page_template->ID ) {
		$page_template = get_post( $translated_content_template_id );
	}

	us_add_to_page_block_ids( $translated_content_template_id );
	us_add_page_shortcodes_custom_css( $translated_content_template_id );

	$output = $page_template->post_content;

	us_close_wp_query_context();
}

$output = apply_filters( 'us_content_template_the_content', $output );

// If content has no sections, we'll create them manually
if ( strpos( $output, ' class="l-section' ) === FALSE ) {
	$output = '
		<section class="l-section height_' . us_get_option( 'row_height', 'medium' ) . '">
			<div class="l-section-h">' . $output . '</div>
		</section>';
}

echo $output;

if ( $page_template ) {
	us_remove_from_page_block_ids();
}
