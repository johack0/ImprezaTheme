<?php defined( 'ABSPATH' ) or die( 'This script cannot be accessed directly.' );

/**
 * Shortcode: us_list_result_counter
 *
 * @var string $text Main text string
 * @var string $text_single Single result text string
 * @var string $text_no_results No results text string
 * @var string $classes Extend class names
 *
 * @param string $el_class Extra class name
 * @param string $el_id Element ID
 */

global $us_is_page_block_in_no_results, $us_is_page_block_in_menu;
if (
	us_in_the_loop()
	OR $us_is_page_block_in_no_results
	OR $us_is_page_block_in_menu
	OR us_amp()
) {
	return;
}

$_atts = array(
	'class' => 'w-list-result-counter',
);

$_atts['class'] .= $classes ?? '';

if ( ! empty( $el_id ) ) {
	$_atts['id'] = $el_id;
}

global $wp_query;

if ( usb_is_preview() ) {
	$total = $total_unfiltered = 24; // placeholder value for Live Builder
} else {
	$total = $total_unfiltered = $wp_query->found_posts;
}

$posts_per_page = intval( $wp_query->get( 'posts_per_page' ) );
$paged = max( 1, get_query_var( 'paged', 1 ) );

$lower = ( ( $paged - 1 ) * $posts_per_page ) + 1;
$upper = min( $total, $paged * $posts_per_page );

$json_data = array(
	'totalUnfiltered' => $total_unfiltered,
	'perPage' => $posts_per_page,
	'listSelectorToCount' => $list_selector_to_count,
);

$formatted_text = strtr( $text, array(
	'[total_unfiltered]' => '<span class="total-unfiltered">' . $total_unfiltered . '</span>',
	'[lower]' => '<span class="lower">' . $lower . '</span>',
	'[upper]' => '<span class="upper">' . $upper . '</span>',
	'[total]' => '<span class="total">' . $total . '</span>',
) );

if ( empty( $formatted_text ) ) {
	return;
}

// Output
echo '<div' . us_implode_atts( $_atts ) . us_pass_data_to_js( $json_data ) . '>';
echo '<span' . ( $total <= 1 ? ' class="hidden"' : '' ) . '>' . $formatted_text . '</span>';
if ( ! empty( $text_single ) ) {
	echo '<span class="one-result' . ( $total === 1 ? '' : ' hidden' ) . '">' . $text_single . '</span>';
}
if ( ! empty( $text_no_results ) ) {
	echo '<span class="no-results' . ( $total === 0 ? '' : ' hidden' ) . '">' . $text_no_results . '</span>';
}
echo '</div>';
