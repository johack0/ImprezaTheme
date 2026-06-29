<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

// Custom output for "Menu" element in Headers
class US_Walker_Nav_Menu extends Walker_Nav_Menu {

	private $mobile_behavior;

	public function __construct( $mobile_behavior = 0 ) {
		$this->mobile_behavior = (int) $mobile_behavior;
	}

	public function start_lvl( &$output, $depth = 0, $args = array() ) {

		$level = ( $depth + 2 ); // because it counts the first submenu as 0

		$output .= '<ul class="w-nav-list level_' . $level . '">';
	}

	public function end_lvl( &$output, $depth = 0, $args = array() ) {
		$output .= '</ul>';
	}

	public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {

		$level = ( $depth + 1 ); // because it counts the first submenu as 0

		$class_arr = empty( $item->classes ) ? array() : (array) $item->classes;
		$class_arr[] = 'w-nav-item';
		$class_arr[] = 'level_' . $level;
		$class_arr[] = 'menu-item-' . $item->ID;

		$style = '';

		if ( ! empty( $item->mega_menu_cols ) OR ! empty( $item->has_side_panel ) ) {

			$class_arr[] = empty( $item->has_side_panel ) ? 'has_cols' : 'has_side_panel';

			$class_arr[] = 'fill_direction_' . ( $item->columns_fill_direction ?? 'hor' );

			$style = ' style="--menu-cols:' . ( $item->mega_menu_cols ?? '1' ) . '"';
		}

		if ( ! empty( $item->mobile_behavior ) ) {
			$class_arr[] = 'mobile-drop-by_' . $item->mobile_behavior;
		}
		if ( $item->object == 'us_page_block' AND ! get_post_meta( $item->ID, '_menu_item_remove_rows', TRUE ) ) {
			$class_arr[] = 'us_page_block_with_rows';
		}

		// Removing active classes for scroll links, so they could be handled by JavaScript instead
		if ( isset( $item->url ) AND strpos( $item->url, '#' ) !== FALSE ) {
			$class_arr = array_diff(
				$class_arr,
				array(
					'current-menu-item',
					'current-menu-ancestor',
					'current-menu-parent',
				)
			);
		}
		$class = implode( ' ', apply_filters( 'nav_menu_css_class', array_filter( $class_arr ), $item, $args, $depth ) );
		$class = $class ? ' class="' . esc_attr( $class ) . '"' : '';

		$id = apply_filters( 'nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args );
		$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

		$output .= '<li' . $id . $class . $style . '>';

		// Output Reusable Block content
		if ( $item->object == 'us_page_block' AND $page_block = get_post( $item->object_id ) ) {

			global $us_is_page_block_in_menu;
			$us_is_page_block_in_menu = TRUE;

			$page_block_content = $page_block->post_content;

			// Remove Row and Column shortcodes, if set in the item
			if ( get_post_meta( $item->ID, '_menu_item_remove_rows', TRUE ) ) {
				$page_block_content = str_replace(
					array(
						'[vc_row]',
						'[/vc_row]',
						'[vc_column]',
						'[/vc_column]',
					), '', $page_block_content
				);
				$page_block_content = preg_replace( '~\[vc_row (.+?)]~', '', $page_block_content );
				$page_block_content = preg_replace( '~\[vc_column (.+?)]~', '', $page_block_content );
			}
			us_add_page_shortcodes_custom_css( $page_block->ID );

			$output .= apply_filters( 'us_page_block_the_content', $page_block_content );

			$us_is_page_block_in_menu = FALSE;

			// Output Menu Items
		} else {
			$anchor_atts = array( 'class' => 'w-nav-anchor level_' . $level );

			if ( ! empty( $item->has_children ) ) {
				$anchor_atts['aria-haspopup'] = 'menu';
			}

			// Add Button Styles
			if ( $depth === 0 AND $btn_style = get_post_meta( $item->ID, '_menu_item_btn_style', TRUE ) ) {
				$anchor_atts['class'] .= ' w-btn ' . us_get_btn_class( $btn_style );
			}

			if ( ! empty( $item->url ) ) {
				$anchor_atts['href'] = $item->url;
			}
			if ( ! empty( $item->attr_title ) ) {
				$anchor_atts['title'] = $item->attr_title;
			}
			if ( ! empty( $item->target ) ) {
				$anchor_atts['target'] = $item->target;
			}
			if ( ! empty( $item->xfn ) ) {
				$anchor_atts['rel'] = $item->xfn;
			}

			// Default menu item link tag
			$link_tag = 'a';

			// Remove href from AMP links and set items to expand sub-items instead
			if (
				function_exists( 'us_amp' )
				AND us_amp()
				AND $this->mobile_behavior
				AND ! empty( $item->has_children )
			) {
				$link_tag = 'span';
				$anchor_atts['on'] = 'tap:menu-item-' . $item->ID . '.toggleClass(class=\'opened\')';
				if ( isset( $anchor_atts['href'] ) ) {
					unset( $anchor_atts['href'] );
				}
			}

			$anchor_atts_string = '';
			foreach ( $anchor_atts as $key => $value ) {
				$anchor_atts_string .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( $value ) );
			}

			$item_output = $args->before;
			$item_output .= '<' . $link_tag . $anchor_atts_string . '>';

			// Span for border gradient animation
			if ( strpos( $anchor_atts['class'], ' with_border_animation' ) !== FALSE ) {
				$item_output .= '<span class="w-btn-inner">';
			}

			$item_output .= $args->link_before;
			$item_output .= '<span class="w-nav-title">' . apply_filters( 'the_title', $item->title, $item->ID ) . '</span>';
			if (
				function_exists( 'us_amp' )
				AND ! us_amp()
				AND ! empty( $item->has_children )
			) {
				$item_output .= '<span class="w-nav-arrow" tabindex="0" role="button" aria-expanded="false" aria-label="' . esc_attr( $item->title ) . ' ' . us_translate( 'Menu' ) . '"></span>';
			}

			$item_output .= $args->link_after;

			if ( strpos( $anchor_atts['class'], ' with_border_animation' ) !== FALSE ) {
				$item_output .= '</span>';
			}

			$item_output .= '</' . $link_tag . '>';

			// Move outside of the anchor to make it clickable on AMP pages
			if ( function_exists( 'us_amp' ) AND us_amp() ) {
				$item_output .= '<span class="w-nav-arrow" on="tap:menu-item-' . $item->ID . '.toggleClass(class=\'opened\')"></span>';
			}
			$item_output .= $args->after;

			$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
		}
	}

	public function end_el( &$output, $item, $depth = 0, $args = array() ) {
		$output .= '</li>';
	}
}

// Custom output for "Category Navigation" element
class US_Walker_Category extends Walker_Category {

	public function start_lvl( &$output, $depth = 0, $args = array() ) {

		if ( isset( $args['max_level'] ) AND $depth >= $args['max_level'] ) {
			return;
		}

		$indent  = str_repeat( "\t", $depth );
		$output .= "$indent<ul class='children level_" . ( $depth + 2 ) . "'>\n";
	}

	public function end_lvl( &$output, $depth = 0, $args = array() ) {

		if ( isset( $args['max_level'] ) AND $depth >= $args['max_level'] ) {
			return;
		}

		$indent  = str_repeat( "\t", $depth );
		$output .= "$indent</ul>\n";
	}

	public function start_el( &$output, $category, $depth = 0, $args = array(), $current_object_id = 0 ) {

		if ( isset( $args['max_level'] ) AND $depth > $args['max_level'] ) {
			return;
		}

		$cat_name = apply_filters( 'list_cats', esc_attr( $category->name ), $category );

		if ( '' === $cat_name ) {
			return;
		}

		$cat_name = '<span>' . $cat_name . '</span>';

		if ( ! empty( $args['show_count'] ) ) {
			$cat_name .= '<c>';
			$cat_name .= number_format_i18n( $category->count );
			$cat_name .= '</c>';
		}

		if ( ! empty( $args['show_as_accordion'] ) ) {
			$cat_name .= '<b></b>';
		}

		$css_classes = array(
			'cat-item',
			'cat-item-' . $category->term_id,
		);

		// Link atts
		$link_atts['href'] = get_term_link( $category );

		// Variables are set inside category_nav.php
		$_current_term_id = $args['current_category'] ?? 0;
		$_current_parent_id = $args['current_parent'] ?? 0;

		if ( $category->term_id === $_current_term_id ) {
			$css_classes[] = 'current-cat';
			if ( ! empty( $args['show_as_accordion'] ) ) {
				$css_classes[] = 'expanded';
			}
			$link_atts['aria-current'] = 'page';

		} elseif ( $category->term_id === $_current_parent_id ) {
			$css_classes[] = 'current-cat-parent';
		}

		while ( $_current_parent_id ) {
			if ( $category->term_id === $_current_parent_id ) {
				$css_classes[] = 'current-cat-ancestor';

				if ( ! empty( $args['show_as_accordion'] ) ) {
					$css_classes[] = 'expanded';
					$link_atts['aria-expanded'] = 'true';
				}
				break;
			}

			$_term = get_term( $_current_parent_id, $category->taxonomy );
			$_current_parent_id = $_term->parent;
		}

		$link_atts = apply_filters( 'category_list_link_attributes', $link_atts, $category, $depth, $args, $current_object_id );

		$link_atts_str = '';
		foreach ( $link_atts as $attr => $value ) {
			if (
				is_scalar( $value )
				AND $value !== ''
				AND $value !== FALSE
			) {
				$value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				$link_atts_str .= ' ' . $attr . '="' . $value . '"';
			}
		}

		$link = sprintf(
			'<a%s>%s</a>',
			$link_atts_str,
			$cat_name
		);

		$css_classes = ' class="' . esc_attr( implode( ' ', $css_classes ) ) . '"';

		$output .= sprintf(
			"\t<li %s>%s\n",
			$css_classes,
			$link
		);
	}

	public function end_el( &$output, $data_object, $depth = 0, $args = array() ) {

		if ( isset( $args['max_level'] ) AND $depth > $args['max_level'] ) {
			return;
		}

		$output .= "</li>\n";
	}
}

// Customize Menu Dropdowns regarding its settings
add_filter( 'wp_nav_menu_objects', function( $sorted_menu_items ) {

	$parent_items = wp_list_pluck( $sorted_menu_items, 'menu_item_parent' );

	foreach ( $sorted_menu_items as $index => $item ) {

		// Save items with children to pass them to walker
		if ( in_array( $item->ID, $parent_items ) ) {
			$item->has_children = TRUE;
		}

		// If it is a first level item or if it is a fake last item
		if ( $item->menu_item_parent == 0 ) {
			if (
				$dropdown_settings = get_post_meta( $item->ID, 'us_mega_menu_settings', TRUE )
				AND is_array( $dropdown_settings )
			) {
				if ( ! empty( $dropdown_settings['has_side_panel'] ) ) {
					$item->has_side_panel = 1;
				}
				if ( ! empty( $dropdown_settings['columns'] ) AND (int) $dropdown_settings['columns'] > 1 ) {
					$item->mega_menu_cols = (int) $dropdown_settings['columns'];
				}
				if ( ! empty( $dropdown_settings['columns_fill_direction'] ) ) {
					$item->columns_fill_direction = $dropdown_settings['columns_fill_direction'];
				}
				if ( ! empty( $dropdown_settings['override_settings'] ) ) {
					$item->mobile_behavior = $dropdown_settings['mobile_behavior'];
				}
			}
			$sorted_menu_items[ $index ] = $item;
		}
	}

	return $sorted_menu_items;
} );

// Current "accordion" menu item should be expanded
add_filter( 'nav_menu_css_class', function( $classes, $item, $menu_args ) {
	if ( ! empty( $menu_args->us_menu_accordion ) AND $item->current_item_ancestor ) {
		$classes[] = 'expanded';
	}
	return $classes;
}, 10, 3 );

// Current "accordion" menu item should have the aria attribute
add_filter( 'nav_menu_link_attributes', function( $atts, $item, $menu_args ) {
	if ( ! empty( $menu_args->us_menu_accordion ) AND $item->current_item_ancestor ) {
		$atts['aria-expanded'] = 'true';
	}
	return $atts;
}, 10, 3 );

// Add fallback menu location, which can be used in plugins
add_action( 'init', function() {
	register_nav_menus(
		array(
			'us_main_menu' => __( 'Custom Menu', 'us' ),
		)
	);
} );
