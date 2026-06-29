<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Menu element
 *
 * @var $hover_effect    string Hover Effect: 'simple' / 'underline'
 * @var $dropdown_open   string 'click' / 'hover'
 * @var $dropdown_effect string Dropdown Effect
 * @var $vstretch        boolean Stretch menu items vertically to fit the available height
 * @var $indents         int Items Indents
 * @var $mobile_width    int On which screen width menu becomes mobile
 * @var $mobile_behavior boolean Mobile behavior
 * @var $design_options  array
 * @var $id              string
 * @var $classes         string
 * @var $source          string WP Menu source
 */

if ( substr( $source, 0, 9 ) == 'location:' ) {
	$location = substr( $source, 9 );
	$theme_locations = get_nav_menu_locations();
	if ( isset( $theme_locations[ $location ] ) ) {
		$menu_obj = get_term( $theme_locations[ $location ], 'nav_menu' );
		if ( $menu_obj ) {
			$source = $menu_obj->slug;
		} else {
			return;
		}
	} else {
		return;
	}
} else {
	$location = NULL;
}

if ( empty( $location ) AND ( empty( $source ) OR ! is_nav_menu( $source ) ) ) {
	return;
}

// Force dropdown "none" animation for AMP
if ( us_amp() ) {
	$dropdown_effect = 'none';
}

$_atts['class'] = 'w-nav';
$_atts['class'] .= us_amp() ? ' type_mobile' : ' type_desktop';
$_atts['class'] .= $classes ?? '';
$_atts['class'] .= ( $vstretch ) ? ' height_full' : '';
$_atts['class'] .= ( $spread ) ? ' spread' : '';
$_atts['class'] .= ( $align_edges ) ? ' align-edges' : '';
$_atts['class'] .= ( $dropdown_arrow ) ? ' show_main_arrows' : '';
$_atts['class'] .= ( $mobile_header_visible ) ? ' header_is_visible' : '';
$_atts['class'] .= ' open_on_' . $dropdown_open;
$_atts['class'] .= ' dropdown_' . $dropdown_effect;
$_atts['class'] .= ' m_align_' . $mobile_align;
$_atts['class'] .= ' m_layout_' . $mobile_layout;
$_atts['class'] .= ' dropdown_shadow_' . $dropdown_shadow;

if ( $mobile_layout == 'panel' ) {
	$_atts['class'] .= ' m_effect_' . $mobile_effect_p;
}
if ( $mobile_layout == 'dropdown' OR $mobile_layout == 'panel' ) {
	$_atts['class'] .= ' m_shadow_' . $mobile_shadow;
}
if ( $mobile_layout == 'fullscreen' ) {
	$_atts['class'] .= ' m_effect_' . $mobile_effect_f;
}

$_atts['style'] = '--sub-item-hor-indent:' . $sub_item_hor_indent . ';';
$_atts['style'] .= '--sub-item-ver-indent:' . $sub_item_ver_indent . ';';
if ( $dropdown_font_size ) {
	$_atts['style'] .= '--dropdown-font-size:' . $dropdown_font_size . ';';
}
if ( $dropdown_font_weight ) {
	$_atts['style'] .= '--dropdown-font-weight:' . $dropdown_font_weight . ';';
}
if ( ! in_array( $dropdown_padding, array( '', '0', '0px', '0em' ) ) ) { // do not save zero value
	$_atts['style'] .= '--dropdown-padding:' . $dropdown_padding . ';';
}
if ( $dropdown_border_radius ) {
	$_atts['style'] .= '--dropdown-border-radius:' . $dropdown_border_radius . ';';
}
if ( $mobile_font_size ) {
	$_atts['style'] .= '--mobile-font-size:' . $mobile_font_size . ';';
}
if ( $mobile_dropdown_font_size ) {
	$_atts['style'] .= '--mobile-dropdown-font-size:' . $mobile_dropdown_font_size . ';';
}

if ( ! empty( $el_id ) ) {
	$_atts['id'] = $el_id;
}

if ( us_get_option( 'schema_markup' ) ) {
	$_atts['itemscope'] = '';
	$_atts['itemtype'] = 'https://schema.org/SiteNavigationElement';
}

// Output the element
echo '<nav' . us_implode_atts( $_atts ) . '>';

$_open_button_atts = array(
	'class' => 'w-nav-control',
	'aria-label' => us_translate( 'Menu' ),
	'aria-expanded' => 'false',
	'role' => 'button',
);

// Set AMP page attributes
if ( us_amp() ) {
	$amp_menu_id = str_replace( 'menu:', '', $id );

	$_open_button_atts['id'] = 'hamburger-' . $amp_menu_id;
	$_open_button_atts['on'] = 'tap:hamburger-' . $amp_menu_id . '.toggleClass(class=\'active\'),w-nav-list-' . $amp_menu_id . '.toggleClass(class=\'active\')';
} else {
	$_open_button_atts['href'] = '#';
}

echo '<a' . us_implode_atts( $_open_button_atts ) . '>';

if ( $mobile_icon_text == 'left' ) {
	echo '<span>' . strip_tags( $mobile_icon_text_label ) . '</span>';
}

// Mobile Icon Styles
echo '<div class="w-nav-icon style_' . $mobile_icon_style . '" style="--icon-thickness:' . $mobile_icon_thickness . '">';

if ( in_array( $mobile_icon_style, array( 'hamburger_1', 'hamburger_2', 'hamburger_4', 'hamburger_5', 'hamburger_6', 'hamburger_7', 'hamburger_8', 'kebab_1', 'kebab_2' ) ) ) {
	echo '<div></div>'; // empty div
}
if ( in_array( $mobile_icon_style, array( 'hamburger_2', 'kebab_1' ) ) ) {
	echo '<div></div>'; // second empty div

} elseif ( $mobile_icon_style === 'hamburger_3' ) {
	echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
		<path class="line top" d="m 70,33 h -40 c 0,0 -8.5,-0.15 -8.5,8.5 0,8.6 8.5,8.5 8.5,8.5 h 20 v -20" />
		<path class="line middle" d="m 70,50 h -40" />
		<path class="line bottom" d="m 30,67 h 40 c 0,0 8.5,0.15 8.5,-8.5 0,-8.6 -8.5,-8.5 -8.5,-8.5 h -20 v 20" />
	</svg>';

} elseif ( $mobile_icon_style === 'dots_1' ) {
	echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30">
		<circle cx="5"  cy="5"  r="2" />
		<circle cx="15" cy="5"  r="2" />
		<circle cx="25" cy="5"  r="2" />
		<circle cx="5"  cy="15" r="2" />
		<circle cx="15" cy="15" r="2" />
		<circle cx="25" cy="15" r="2" />
		<circle cx="5"  cy="25" r="2" />
		<circle cx="15" cy="25" r="2" />
		<circle cx="25" cy="25" r="2" />
	</svg>';

} elseif ( $mobile_icon_style === 'custom_icon' ) {
	echo us_prepare_icon_tag( $mobile_custom_icon_open );
	echo us_prepare_icon_tag( $mobile_custom_icon_close );

} elseif ( $mobile_icon_style === 'custom_image' ) {

	if ( $_image_open = wp_get_attachment_image( $mobile_custom_image_open ) ) {
		echo $_image_open;
	} else {
		echo us_get_img_placeholder();
	}
	if ( $_image_close = wp_get_attachment_image( $mobile_custom_image_close ) ) {
		echo $_image_close;
	} else {
		echo us_get_img_placeholder();
	}
}

echo apply_filters( 'us_mobile_menu_icon_html', '', $mobile_icon_style );

echo '</div>'; // w-nav-icon
if ( $mobile_icon_text == 'right' ) {
	echo '<span>' . strip_tags( $mobile_icon_text_label ) . '</span>';
}
echo '</a>'; // w-nav-control

$_list_atts['class'] = 'w-nav-list level_1 hide_for_mobiles hover_' . $hover_effect;

if ( us_amp() ) {
	$_list_atts['id'] = 'w-nav-list-' . $amp_menu_id;
}

// Items list
echo '<ul' . us_implode_atts( $_list_atts ) . '>';
if ( $location ) {
	wp_nav_menu(
		array(
			'theme_location' => $location,
			'container' => FALSE,
			'walker' => new US_Walker_Nav_Menu( $mobile_behavior ),
			'items_wrap' => '%3$s',
			'fallback_cb' => FALSE,
		)
	);
} else {
	wp_nav_menu(
		array(
			'menu' => $source,
			'container' => FALSE,
			'walker' => new US_Walker_Nav_Menu( $mobile_behavior ),
			'items_wrap' => '%3$s',
			'fallback_cb' => FALSE,
		)
	);
}

$_close_button_atts['class'] = 'w-nav-close';
if ( us_amp() ) {
	$_close_button_atts['id'] = 'w-nav-close-' . $amp_menu_id;
	$_close_button_atts['on'] = 'tap:hamburger-' . $amp_menu_id . '.toggleClass(class=\'active\'),w-nav-list-' . $amp_menu_id . '.toggleClass(class=\'active\')';
}
echo '<li' . us_implode_atts( $_close_button_atts ) . '></li>';
echo '</ul>';

if ( ! us_amp() ) {
	echo '<div class="w-nav-options hidden"';
	echo us_pass_data_to_js(
		array(
			'mobileWidth' => (int) $mobile_width,
			'mobileBehavior' => (int) $mobile_behavior,
		)
	);
	echo '></div>';
}

echo '</nav>';
