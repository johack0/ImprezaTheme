<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Helper functions for loop (List/Carousel) elements
 */

if ( ! function_exists( 'us_in_the_loop' ) ) {

	/**
	 * The analog of in_the_loop() WordPress function, but works for loops with terms and users (not only posts)
	 *
	 * @return bool
	 */
	function us_in_the_loop() {
		global $us_in_the_loop;

		return $us_in_the_loop ?? FALSE;
	}
}

if ( ! function_exists( 'us_get_loop_item_type' ) ) {

	/**
	 * Get the object type in the loop, can be only: 'post', 'term' or 'user'
	 * This function does NOT validate the loop is ON or OFF, use us_in_the_loop() instead
	 *
	 * @return string
	 */
	function us_get_loop_item_type() {
		global $us_loop_item_type;

		return $us_loop_item_type ?? 'post';
	}
}

if ( ! function_exists( 'us_get_loop_post_types' ) ) {

	/**
	 * Get post types for selection in "loop" elements (Filter, Lists and Carousels)
	 *
	 * @param bool $reload used when list of available post types should be reloaded
	 * because data that affects it was changed
	 *
	 * @return array
	 */
	function us_get_loop_post_types( $reload = FALSE, $show_slug = TRUE ) {

		static $posts_types = array();

		if ( empty( $posts_types ) OR $reload ) {
			$posts_types_params = array(
				'show_in_menu' => TRUE,
			);
			$skip_post_types = array(
				'us_header',
				'us_page_block',
				'us_content_template',
				'us_grid_layout',
				'shop_order',
				'shop_coupon',
			);
			foreach ( get_post_types( $posts_types_params, 'objects' ) as $post_type_name => $post_type ) {
				if ( in_array( $post_type_name, $skip_post_types ) ) {
					continue;
				}
				$_label = $post_type->labels->name;
				if ( $show_slug ) {
					$_label .= ' (' . $post_type_name . ')';
				}
				$posts_types[ $post_type_name ] = $_label;
			}
		}

		// Fallback for old hook
		$posts_types = apply_filters( 'us_grid_available_post_types', $posts_types );

		return apply_filters( 'us_get_loop_post_types', $posts_types );
	}
}

if ( ! function_exists( 'us_get_loop_post_types_for_import' ) ) {

	/**
	 * Get post types that have published posts.
	 * Array used while importing shortcodes in builders (via "Paste Section" feature)
	 *
	 * @return array
	 */
	function us_get_loop_post_types_for_import() {

		foreach ( array_keys( us_get_loop_post_types() ) as $post_type ) {
			if ( wp_count_posts( $post_type )->publish ) {
				$post_types[] = $post_type;
			}
		}

		return $post_types;
	}
}

if ( ! function_exists( 'us_import_grid_layout' ) ) {

	/**
	 * This is a method to add a layout based on the passed data
	 *
	 * @param string $data The data
	 * @param string $post_type The post type
	 * @return int|string
	 */
	function us_import_grid_layout( $data, $post_type = 'us_grid_layout' ) {
		$result = 'blog_1'; // the default layout
		$data = explode( '|', $data );
		if ( count( $data ) != 2 ) {
			return $result;
		}
		$post_content = base64_decode( $data[1] );
		if ( json_decode( $post_content ) === NULL ) {
			$post_content = NULL;
		}
		if ( ! $post_content OR ! isset( $data[0] ) ) {
			return $result;
		}

		global $wpdb;

		// Preparing a query to find a duplicate us_grid_layout
		$sql = $wpdb->prepare(
			"SELECT id FROM $wpdb->posts WHERE post_type = %s AND TRIM(`post_content`) = %s LIMIT 1",
			$post_type,
			$post_content
		);
		if ( $post_id = $wpdb->get_var( $sql ) ) {
			// If the record exists, we get the identifier
			$result = $post_id;
		} else {
			$post_id = wp_insert_post(
				array(
					'post_type' => $post_type,
					'post_content' => $post_content,
					'post_author' => get_current_user_id(),
					'post_title' => trim( base64_decode( (string) $data[0] ) ),
					'post_status' => 'publish',
					'comment_status' => 'closed',
					'ping_status' => 'closed',
				)
			);
			if ( $post_id > 0 ) {
				$result = $post_id;
			}
		}

		return $result;
	}
}

if ( ! function_exists( 'us_get_grid_layout_settings' ) ) {

	/**
	 * Get settings of the provided Grid Layout (post or predefined template)
	 *
	 * @param int|string $grid_layout Post ID or Template name
	 * @param array $default_template Template name which is used in case of incorrect $grid_layout
	 * @return array
	 */
	function us_get_grid_layout_settings( $grid_layout, $default_template = 'blog_1' ) {

		// Get Grid Layout templates
		$templates = us_config( 'grid-templates', array(), TRUE );

		// Use Grid Layout template if exists
		if ( isset( $templates[ $grid_layout ] ) ) {
			$result = us_fix_grid_layout_settings( $templates[ $grid_layout ] );

			// If not, use the "Grid Layout" post
		} elseif (
			$grid_layout_post = get_post( $grid_layout )
			AND $grid_layout_post instanceof WP_Post
			AND $grid_layout_post->post_type == 'us_grid_layout'
		) {
			// If the post has translated version use it instead
			$translated_post_id = apply_filters( 'us_tr_object_id', $grid_layout_post->ID, 'us_grid_layout', TRUE );
			if ( $translated_post_id != $grid_layout_post->ID ) {
				$grid_layout_post = get_post( $translated_post_id );
			}

			// Get the post content as result settings
			if ( ! empty( $grid_layout_post->post_content ) AND strpos( $grid_layout_post->post_content, '{' ) === 0 ) {
				try {
					$result = json_decode( $grid_layout_post->post_content, TRUE );
				}
				catch ( Exception $e ) {
				}
			}
		}

		// In case of empty settings use the default template as fallback
		if ( empty( $result ) ) {
			$result = us_fix_grid_layout_settings( $templates[ $default_template ] );
		}

		// Fallback for options
		$result = us_grid_layout_settings_fallback( $result );

		return apply_filters( 'us_grid_layout_settings', $result );
	}
}

if ( ! function_exists( 'us_fix_grid_layout_settings' ) ) {

	/**
	 * Make the provided Grid Layout settings consistent and proper (adds default values for missed settings)
	 *
	 * @param $value array
	 *
	 * @return array
	 */
	function us_fix_grid_layout_settings( $value ) {

		if ( empty( $value ) OR ! is_array( $value ) ) {
			$value = array();
		}
		if ( ! isset( $value['data'] ) OR ! is_array( $value['data'] ) ) {
			$value['data'] = array();
		}

		$opt_defaults = array();
		$elm_defaults = array();

		if ( function_exists( 'usof_get_default' ) ) {
			foreach ( us_config( 'grid-settings.options', array() ) as $opt_name => $opt_group ) {
				foreach ( $opt_group as $opt_name => $opt_field ) {
					$opt_defaults[ $opt_name ] = usof_get_default( $opt_field );
				}
			}

			foreach ( us_config( 'grid-settings.elements', array() ) as $elm_name ) {
				$elm_settings = us_config( 'elements/' . $elm_name );
				$elm_defaults[ $elm_name ] = array();
				foreach ( $elm_settings['params'] as $param_name => $param_field ) {
					$elm_defaults[ $elm_name ][ $param_name ] = usof_get_default( $param_field );
				}
			}
		}
		foreach ( $opt_defaults as $opt_name => $opt_default ) {
			if ( ! isset( $value['default']['options'][ $opt_name ] ) ) {
				$value['default']['options'][ $opt_name ] = $opt_default;
			}
		}
		foreach ( $value['data'] as $elm_name => $elm_values ) {
			$elm_type = strtok( $elm_name, ':' );
			if ( ! isset( $elm_defaults[ $elm_type ] ) ) {
				continue;
			}
			foreach ( $elm_defaults[ $elm_type ] as $param_name => $param_default ) {
				if ( ! isset( $value['data'][ $elm_name ][ $param_name ] ) ) {
					$value['data'][ $elm_name ][ $param_name ] = $param_default;
				}
			}
		}

		foreach ( array( 'default' ) as $state ) {

			if ( ! isset( $value[ $state ] ) OR ! is_array( $value[ $state ] ) ) {
				$value[ $state ] = array();
			}

			if ( ! isset( $value[ $state ]['layout'] ) OR ! is_array( $value[ $state ]['layout'] ) ) {
				if ( $state != 'default' AND isset( $value['default']['layout'] ) ) {
					$value[ $state ]['layout'] = $value['default']['layout'];
				} else {
					$value[ $state ]['layout'] = array();
				}
			}

			$state_elms = array();

			foreach ( $value[ $state ]['layout'] as $place => $elms ) {
				if ( ! is_array( $elms ) ) {
					$elms = array();
				}
				foreach ( $elms as $index => $elm_id ) {
					if ( ! is_string( $elm_id ) OR strpos( $elm_id, ':' ) == -1 ) {
						unset( $elms[ $index ] );
					} else {
						$state_elms[] = $elm_id;
						if ( ! isset( $value['data'][ $elm_id ] ) ) {
							$value['data'][ $elm_id ] = array();
						}
					}
				}
				$value[ $state ]['layout'][ $place ] = array_values( $elms );
			}

			if ( ! isset( $value[ $state ]['layout']['hidden'] ) OR ! is_array( $value[ $state ]['layout']['hidden'] ) ) {
				$value[ $state ]['layout']['hidden'] = array();
			}

			$value[ $state ]['layout']['hidden'] = array_merge( $value[ $state ]['layout']['hidden'], array_diff( array_keys( $value['data'] ), $state_elms ) );

			if ( ! isset( $value[ $state ]['options'] ) OR ! is_array( $value[ $state ]['options'] ) ) {
				$value[ $state ]['options'] = array();
			}

			$value[ $state ]['options'] = array_merge( $opt_defaults,
				( $state != 'default' )
					? $value['default']['options']
					: array(), $value[ $state ]['options']
			);
		}

		return $value;
	}
}

if ( ! function_exists( 'us_get_grid_layouts_for_selection' ) ) {

	/**
	 * Get the Grid Layouts including templates divided by group
	 *
	 * @param array $template_prefixes Include templates with provided prefixes only
	 * @return array
	 */
	function us_get_grid_layouts_for_selection( $template_prefixes = array() ) {

		// Break further execution on the frontend to avoid extra DB queries
		if ( ! wp_doing_ajax() OR ! is_admin() ) {
			return array();
		}

		// Show Grid Layouts first
		$result = array(
			__( 'Grid Layouts', 'us' ) => us_get_posts_titles_for( 'us_grid_layout' ),
		);

		$current_group = '';

		// Get grid templates and divide them by group
		foreach ( us_config( 'grid-templates', array(), TRUE ) as $template_name => $template ) {

			if ( ! empty( $template['group'] ) AND $template['group'] != $current_group ) {
				$current_group = $template['group'];
			}

			// Include templates with provided prefixes only
			if ( $template_prefixes ) {
				foreach ( (array) $template_prefixes as $_prefix ) {
					if ( strpos( $template_name, $_prefix ) === 0 ) {
						$result[ $current_group ][ $template_name ] = $template['title'];
					}
				}

				// If prefixes are not provided include all templates
			} else {
				$result[ $current_group ][ $template_name ] = $template['title'];
			}
		}

		return $result;
	}
}

if ( ! function_exists( 'us_process_grid_layout_dynamic_values' ) ) {

	/**
	 * Process grid layout dynamic values of the provided object ID
	 *
	 * @param string $object_type / post / term / user /
	 * @param int $object_ID
	 * @return array Updated collection of grid layout CSS properties
	 */
	function us_process_grid_layout_dynamic_values( $object_type, $object_ID ) {
		global $us_grid_layout_dynamic_values;
		$result = array();

		foreach ( (array) $us_grid_layout_dynamic_values as $screen => $jsoncss ) {
			foreach ( $jsoncss as $css_selector => $props ) {
				foreach ( $props as $prop_name => $prop_value ) {

					if ( strpos( $prop_name, 'color' ) !== FALSE ) {
						$prop_value = us_get_color( $prop_value, /* allow_gradient */TRUE, /* css_var */TRUE );
					} else {
						$prop_value = us_replace_dynamic_value( $prop_value, /* acf_format */FALSE );
					}
					if ( $prop_value != '' ) {
						$css_selector = str_replace( '{{grid-item-id}}', $object_type . '-' . $object_ID, $css_selector );
						$result[ $screen ][ $css_selector ][ $prop_name ] = $prop_value;
					}
				}
			}
		}

		return $result;
	}
}

if ( ! function_exists( 'us_get_list_filter_params' ) ) {

	/**
	 * Generate params for filtering with unique names (used in URL)
	 */
	function us_get_list_filter_params() {

		static $params = array();

		if ( ! empty( $params ) ) {
			return apply_filters( 'us_get_list_filter_params', $params );
		}

		// Predefined post params
		$params = array(
			'post_type' => array(
				'label' => __( 'Post Type', 'us' ),
				'group' => us_translate( 'Post Attributes' ),
				'source_type' => 'post',
				'source_name' => 'type',
			),
			'post_author' => array(
				'label' => us_translate( 'Author' ),
				'group' => us_translate( 'Post Attributes' ),
				'source_type' => 'post',
				'source_name' => 'author',
			),
			'post_date' => array(
				'label' => __( 'Date of creation', 'us' ),
				'group' => us_translate( 'Post Attributes' ),
				'source_type' => 'post',
				'source_name' => 'date',
				'value_type' => 'date',
			),
			'post_modified' => array(
				'label' => __( 'Date of update', 'us' ),
				'group' => us_translate( 'Post Attributes' ),
				'source_type' => 'post',
				'source_name' => 'date_modified',
				'value_type' => 'date',
			),
		);

		// Predefined WooCommerce params
		if ( class_exists( 'woocommerce' ) ) {
			$params += array(

				// Price and Stock Status are custom fields, so keep the "meta" source type
				'price' => array(
					'label' => us_translate( 'Price', 'woocommerce' ),
					'group' => us_translate( 'WooCommerce', 'woocommerce' ),
					'source_type' => 'meta',
					'source_name' => '_price',
					'value_type' => 'numeric',
				),
				'instock' => array(
					'label' => us_translate( 'Stock status', 'woocommerce' ),
					'group' => us_translate( 'WooCommerce', 'woocommerce' ),
					'source_type' => 'meta',
					'source_name' => '_stock_status',
					'value_type' => 'bool',
					'bool_value_label' => us_translate( 'In stock', 'woocommerce' ),
					'bool_value' => 'instock', // used instead of the default "1" value
				),

				// Definition Onsale and Featured products is more complex, so make them with "woo" source type
				'onsale' => array(
					'label' => us_translate( 'On Sale', 'woocommerce' ),
					'group' => us_translate( 'WooCommerce', 'woocommerce' ),
					'source_type' => 'woo', // used for custom logic in wp_query args
					'source_name' => 'onsale',
					'value_type' => 'bool',
					'bool_value_label' => us_translate( 'On-sale products', 'woocommerce' ),
				),
				'featured' => array(
					'label' => us_translate( 'Featured', 'woocommerce' ),
					'group' => us_translate( 'WooCommerce', 'woocommerce' ),
					'source_type' => 'woo',
					'source_name' => 'featured',
					'value_type' => 'bool',
					'bool_value_label' => us_translate( 'Featured products', 'woocommerce' ),
				),
			);
		}

		$custom_slugs = array(
			'us_portfolio_category' => us_get_option( 'portfolio_category_slug', '' ),
			'us_portfolio_tag' => us_get_option( 'portfolio_tag_slug', '' ),
		);

		foreach( us_get_taxonomies() as $slug => $label ) {

			$original_slug = NULL;

			// Portfolio Category/Tag slug replace
			if ( $custom_slug = us_arr_path( $custom_slugs, $slug ) AND 'us_' . $custom_slug !== $slug ) {

				$original_slug = $slug; // keep the original slug for source name to avoid issues

				$slug = $custom_slug;

				$label = str_replace( $original_slug, $slug, $label );
			}

			// If taxonomy slug is already used in previous params (e.g. 'post_type', 'price', 'featured'), append the count suffix to make it unique
			$unique_tax_name = in_array( $slug, array_merge( array_keys( $params ), array( 'orderby', 's' ) ) )
				? $slug . count( $params )
				: $slug;

			$params[ $unique_tax_name ] = array(
				'label' => $label,
				'group' => __( 'Taxonomies', 'us' ),
				'source_type' => 'tax',
				'source_name' => $original_slug ?? $slug,
			);
		}

		return apply_filters( 'us_get_list_filter_params', $params );
	}
}

if ( ! function_exists( 'us_get_list_orderby_params' ) ) {

	/**
	 * Generate params for sorting with unique names (used in URL)
	 */
	function us_get_list_orderby_params() {

		static $params = array();

		if ( ! empty( $params ) ) {
			return apply_filters( 'us_get_list_orderby_params', $params );
		}

		// Predefined post params
		$params = array(
			'date' => array(
				'label' => __( 'Date of creation', 'us' ),
				'group' => us_translate( 'Post Attributes' ),
			),
			'modified' => array(
				'label' => __( 'Date of update', 'us' ),
				'group' => us_translate( 'Post Attributes' ),
			),
			'title' => array(
				'label' => us_translate( 'Title' ),
				'group' => us_translate( 'Post Attributes' ),
			),
			'author' => array(
				'label' => us_translate( 'Author' ),
				'group' => us_translate( 'Post Attributes' ),
			),
			'comment_count' => array(
				'label' => us_translate( 'Comments' ),
				'group' => us_translate( 'Post Attributes' ),
			),
			'type' => array(
				'label' => __( 'Post Type', 'us' ),
				'group' => us_translate( 'Post Attributes' ),
			),
			'menu_order' => array(
				'label' => us_translate( 'Page Attributes' ) . ': ' . us_translate( 'Order' ),
				'group' => us_translate( 'Post Attributes' ),
			),
		);

		// Predefined WooCommerce params
		if ( class_exists( 'woocommerce' ) ) {
			$params += array(
				'price' => array(
					'label' => us_translate( 'Price', 'woocommerce' ),
					'group' => us_translate( 'WooCommerce', 'woocommerce' ),
					'orderby_param' => 'meta_value_num',
					'meta_key' => '_price',
				),
				'total_sales' => array(
					'label' => us_translate( 'Sales', 'woocommerce' ),
					'group' => us_translate( 'WooCommerce', 'woocommerce' ),
					'orderby_param' => 'meta_value_num',
					'meta_key' => 'total_sales',
				),
				'rating' => array(
					'label' => us_translate( 'Rating', 'woocommerce' ),
					'group' => us_translate( 'WooCommerce', 'woocommerce' ),
					'orderby_param' => 'meta_value_num',
					'meta_key' => '_wc_average_rating',
				),
			);
		}

		return apply_filters( 'us_get_list_orderby_params', $params );
	}
}

if ( ! function_exists( 'us_get_filter_params_from_request' ) ) {

	/**
	 * Get List Filter params from request
	 *
	 * @return array Returns an array of installed filters.
	 */
	function us_get_filter_params_from_request() {

		static $url_params = array();
		if ( ! empty( $url_params ) ) {
			return $url_params;
		}

		$global_filter_params = us_get_list_filter_params();

		// Allow the 'f' param that indicates the enabled Faceted Filtering
		$global_filter_params['f'] = '';

		foreach ( (array) $_REQUEST as $name => $values ) {
			if ( strpos( $name, '_' ) !== 0 ) {
				continue;
			}
			$name = mb_substr( $name, 1 );
			if ( ! isset( $global_filter_params[ strtok( $name, '|' ) ] ) ) {
				continue;
			}
			$url_params[ $name ] = $values;
		}

		// Always keep the same order of params.
		krsort( $url_params );

		return $url_params;
	}
}

if ( ! function_exists( 'us_apply_filtering_to_list_query' ) ) {

	/**
	 * Apply the List Filter params to the provided query_args.
	 */
	function us_apply_filtering_to_list_query( &$query_args, $list_filter ) {

		if ( ! is_array( $list_filter ) OR empty( $list_filter ) ) {
			return;
		}

		$global_filter_params = us_get_list_filter_params();

		foreach ( $list_filter as $name => $values ) {

			// Skip "f" param, it only indicates that Faceted Filtering is ON
			if ( $name == 'f' ) {
				continue;
			}

			$values = rawurldecode( $values );
			$values = explode( ',', $values ); // transform to array in all cases
			$values = array_map( 'rawurldecode', $values );

			// Restore comma from escaped QUOTATION MARK in every value (comma was used above to explode different values into array)
			foreach ( $values as &$value ) {
				$value = str_replace( /*U+0201A*/'\‚', ',', $value );
			}
			unset( $value );

			$query_args = apply_filters( 'us_apply_filtering_to_list_query', $query_args, $name, $values );

			// Source name may include the value compare type: 'post_date|after', 'price|between', 'category|and'
			$value_compare = '';

			if ( strpos( $name, '|' ) !== FALSE ) {
				$name = strtok( $name, '|' );
				$value_compare = strtok( '|' );
			}

			if ( empty( $value_compare ) ) {
				$value_compare = $global_filter_params[ $name ]['value_compare'] ?? '';
			}

			$source_type = $global_filter_params[ $name ]['source_type']; // required for conditions below
			$source_name = $global_filter_params[ $name ]['source_name'] ?? '';
			$value_type = $global_filter_params[ $name ]['value_type'] ?? '';

			// Param "f" means that filter has enabled Faceted Filtering
			if ( ! empty( $list_filter['f'] ) ) {

				$post_ids = us_get_post_ids_from_filter_index( $name, $values, $value_compare, $value_type );

				if ( ! empty( $query_args['post__in'] ) ) {
					$query_args['post__in'] = array_intersect( $post_ids, $query_args['post__in'] );
				} else {
					$query_args['post__in'] = $post_ids;
				}

				// Use the non-existing id to get no results, because empty 'post__in' is ignored by query
				if ( empty( $query_args['post__in'] ) ) {
					$query_args['post__in'] = array( 0 );
				}

				// Skip further 'query_args' changes
				continue;
			}

			// All conditions below applied only when Faceted Filtering is OFF
			if ( $source_type == 'post' ) {
				if ( $source_name == 'type' ) {
					$query_args['post_type'] = $values;

				} elseif ( $source_name == 'author' ) {
					$query_args['author__in'] = $values;

				} elseif ( $source_name == 'date' OR $source_name == 'date_modified' ) {
					if ( $source_name == 'date_modified' ) {
						$query_args['date_query']['column'] = 'post_modified';
					}

					if ( $value_compare == 'between' ) {
						$default_values = array(
							'1970-01-01 00:00:00',
							'3000-01-01 00:00:00',
						);
						foreach( $default_values as $i => $default_value ) {
							if ( empty( $values[ $i ] ) ) {
								$values[ $i ] = $default_value;
							}
						}
						$query_args['date_query'][] = array(
							'after' => $values[0],
							'before' => $values[1],
							'inclusive' => TRUE,
							'compare' => 'BETWEEN',
						);

					} elseif ( $value_compare == 'before' ) {
						$query_args['date_query'][] = array(
							'before' => $values[0],
							'inclusive' => TRUE,
						);

					} elseif ( $value_compare == 'after' ) {
						$query_args['date_query'][] = array(
							'after' => $values[0],
							'inclusive' => TRUE,
						);

					} else {
						foreach ( $values as $value ) {
							$query_args['date_query'][] = array(
								'before' => $value,
								'after' => $value,
								'inclusive' => TRUE,
							);
						}
						$query_args['date_query']['relation'] = 'OR';
					}
				}

			} elseif ( $source_type === 'tax' ) {
				$query_args['tax_query']['relation'] = 'AND';

				$tax_query_item = array(
					'taxonomy' => $source_name,
					'field' => 'slug',
					'terms' => $values,
				);

				if ( $value_compare == 'and' ) {
					$tax_query_item['operator'] = 'AND';
					$tax_query_item['include_children'] = FALSE;
				}

				$query_args['tax_query'][] = apply_filters( 'us_apply_filtering_tax_query_item', $tax_query_item, $source_name );

			} elseif ( $source_type === 'woo' ) {
				if ( $source_name == 'onsale' AND $values ) {
					$onsale_ids = wc_get_product_ids_on_sale();

					// Exclude ids matching 'post__not_in' first
					if ( ! empty( $query_args['post__not_in'] ) ) {
						$onsale_ids = array_diff( $onsale_ids, $query_args['post__not_in'] );
					}

					// then add ids matching 'post__in' if set
					if ( ! empty( $query_args['post__in'] ) ) {
						$query_args['post__in'] = array_intersect( $onsale_ids, $query_args['post__in'] );
					} else {
						$query_args['post__in'] = $onsale_ids;
					}

					// Use the non-existing id to get no results, because empty 'post__in' is ignored by query
					if ( ! $query_args['post__in'] ) {
						$query_args['post__in'] = array( 0 );
					}

				} elseif ( $source_name == 'featured' AND $values ) {
					$featured_ids = wc_get_featured_product_ids();

					// Exclude ids matching 'post__not_in' first
					if ( ! empty( $query_args['post__not_in'] ) ) {
						$featured_ids = array_diff( $featured_ids, $query_args['post__not_in'] );
					}

					// then add ids matching 'post__in' if set
					if ( ! empty( $query_args['post__in'] ) ) {
						$query_args['post__in'] = array_intersect( $featured_ids, $query_args['post__in'] );
					} else {
						$query_args['post__in'] = $featured_ids;
					}

					// Use the non-existing id to get no results, because empty 'post__in' is ignored by query
					if ( ! $query_args['post__in'] ) {
						$query_args['post__in'] = array( 0 );
					}
				}

			} elseif ( $source_type === 'meta' ) {
				$query_args['meta_query']['relation'] = 'AND';

				// ACF "Date Picker" values use 'Ymd' format: 20240915
				if ( $value_type == 'date' ) {

					if ( $value_compare == 'before' ) {
						$query_args['meta_query'][] = array(
							'key' => $source_name,
							'value' => array( '19700101', str_replace( '-', '', $values[0] ) ),
							'compare' => 'BETWEEN',
						);

					} elseif ( $value_compare == 'after' ) {
						$query_args['meta_query'][] = array(
							'key' => $source_name,
							'value' => array( str_replace( '-', '', $values[0] ), '30000101' ),
							'compare' => 'BETWEEN',
						);

					} elseif ( $value_compare == 'between' ) {

						$max = $values[1] ?? '30000101';

						$query_args['meta_query'][] = array(
							'key' => $source_name,
							'value' => array( str_replace( '-', '', $values[0] ), str_replace( '-', '', $max ) ),
							'compare' => 'BETWEEN',
						);

					} else {
						$_meta_query_inner = array(
							'relation' => 'OR',
						);
						foreach ( $values as $value ) {

							// 2024
							if ( strlen( $value ) === 4 ) {
								$min = $value . '0101';
								$max = $value . '1231';

								// 2024-09
							} elseif ( strlen( $value ) === 7 ) {
								$min = str_replace( '-', '', $value ) . '01';
								$max = str_replace( '-', '', $value ) . '31';

								// 2024-09-30
							} else {
								$min = str_replace( '-', '', $value );
								$max = str_replace( '-', '', $value );
							}

							$_meta_query_inner[] = array(
								'key' => $source_name,
								'value' => array( $min, $max ),
								'compare' => 'BETWEEN',
							);
						}
						$query_args['meta_query'][] = $_meta_query_inner;
					}

					// ACF "Date Time Picker" values use 'Y-m-d H:i:s' format: 2024-09-15 21:02:59
				} elseif ( $value_type == 'date_time' ) {

					if ( $value_compare == 'before' ) {
						$query_args['meta_query'][] = array(
							'key' => $source_name,
							'value' => array( '1970-01-01 00:00:00', $values[0] . ' 00:00:00' ),
							'compare' => 'BETWEEN',
						);

					} elseif ( $value_compare == 'after' ) {
						$query_args['meta_query'][] = array(
							'key' => $source_name,
							'value' => array( $values[0] . ' 00:00:00', '3000-01-01 00:00:00' ),
							'compare' => 'BETWEEN',
						);

					} elseif ( $value_compare == 'between' ) {

						$max = $values[1] ?? '3000-01-01';

						$query_args['meta_query'][] = array(
							'key' => $source_name,
							'value' => array( $values[0] . ' 00:00:00', $max . ' 00:00:00' ),
							'compare' => 'BETWEEN',
						);

					} else {
						$_meta_query_inner = array(
							'relation' => 'OR',
						);
						foreach ( $values as $value ) {

							// 2024
							if ( strlen( $value ) === 4 ) {
								$min = $value . '-01-01 00:00:00';
								$max = $value . '-12-31 23:59:00';

								// 2024-09
							} elseif ( strlen( $value ) === 7 ) {
								$min = $value . '-01 00:00:00';
								$max = $value . '-31 23:59:00';

								// 2024-09-30
							} else {
								$min = $value . ' 00:00:00';
								$max = $value . ' 23:59:00';
							}

							$_meta_query_inner[] = array(
								'key' => $source_name,
								'value' => array( $min, $max ),
								'compare' => 'BETWEEN',
							);
						}
						$query_args['meta_query'][] = $_meta_query_inner;
					}

					// Not Date and Time comparisons
				} elseif ( $value_compare == 'between' ) {
					$_meta_query_inner = array(
						'relation' => 'OR',
					);
					foreach ( $values as $value ) {
						if ( strpos( $value, '-' ) === FALSE ) {
							continue;
						}
						$_meta_query_inner[] = array(
							'key' => $source_name,
							'value' => explode( '-', $value ),
							'compare' => 'BETWEEN',
							'type' => 'DECIMAL(10,3)',
						);
					}
					$query_args['meta_query'][] = $_meta_query_inner;

				} elseif ( $value_compare == 'like' ) {
					$_meta_query_inner = array(
						'relation' => 'OR',
					);
					foreach ( $values as $value ) {
						$_meta_query_inner[] = array(
							'key' => $source_name,
							'value' => sprintf( ':"%s";', $value ),
							'compare' => 'LIKE',
						);
					}
					$query_args['meta_query'][] = $_meta_query_inner;

				} else {
					$query_args['meta_query'][] = array(
						'key' => $source_name,
						'value' => $values,
						'compare' => 'IN',
					);
				}
			}
		}
	}
}

if ( ! function_exists( 'us_apply_orderby_to_list_query' ) ) {

	/**
	 * Apply the orderby params to the provided query_args.
	 */
	function us_apply_orderby_to_list_query( &$query_args, $orderby_params ) {

		if ( empty( $orderby_params ) OR ! is_string( $orderby_params ) ) {
			return;
		}

		$orderby_params = rawurldecode( $orderby_params );

		// Examples of $orderby_params values:
		// 'date'
		// 'date,asc'
		// 'comment_count'
		// 'comment_count,asc'
		// 'custom_field'
		// 'custom_field,num'
		// 'custom_field,num,asc'
		$orderby_params = array_map( 'trim', explode( ',', $orderby_params ) );

		$orderby = $orderby_params[0] ?? '';

		// Cancel sorting for this specific values
		if ( $orderby == 'current_wp_query' ) {
			return;
		}

		$query_args['order'] = ( end( $orderby_params ) == 'asc' ) ? 'DESC' : 'ASC';

		$predefined_params = us_get_list_orderby_params();

		if ( isset( $predefined_params[ $orderby ] ) ) {

			if ( isset( $predefined_params[ $orderby ]['orderby_param'] ) ) {
				$query_args['orderby'] = $predefined_params[ $orderby ]['orderby_param'];
				$query_args['meta_key'] = $predefined_params[ $orderby ]['meta_key'] ?? '';
			} else {
				$query_args['orderby'] = $orderby;
			}

			// if provided param is not predefined but can be used by Post List, use it as is
		} elseif ( $orderby == 'post__in' OR stripos( $orderby, 'rand' ) === 0 ) {
			$query_args['orderby'] = $orderby;

			// in other cases use it as custom field value
		} else {
			$query_args['orderby'] = ( isset( $orderby_params[1] ) AND $orderby_params[1] == 'num' )
				? 'meta_value_num'
				: 'meta_value';
			$query_args['meta_key'] = $orderby;
		}

		// Add second sorting param to prevent posts from duplicating with ajax pagination (issue #4870)
		if ( in_array( $query_args['orderby'], array( 'meta_value', 'meta_value_num', 'menu_order', 'date', 'modified' ) ) ) {
			$query_args['orderby'] .= ' ID';
		}

		$query_args = apply_filters( 'us_apply_orderby_to_list_query', $query_args, $orderby );
	}
}

if ( ! function_exists( 'us_list_filter_for_current_wp_query' ) ) {

	add_action( 'pre_get_posts', 'us_list_filter_for_current_wp_query', 501 );

	/**
	 * Applies "List Filter" query to the global wp_query.
	 */
	function us_list_filter_for_current_wp_query( $wp_query ) {
		if (
			! $wp_query->is_main_query()
			AND ! $wp_query->get( 'us_faceted_filtering' ) // prevent applying filters while processing in us_get_faceted_filter_post_ids()
			AND $wp_query->get( 'apply_list_url_params' )
			AND (
				! is_admin()
				OR wp_doing_ajax()
			)
		) {
			us_apply_filtering_to_list_query( $wp_query->query_vars, us_get_filter_params_from_request() );
		}
	}
}

if ( ! function_exists( 'us_list_order_for_current_wp_query' ) ) {

	add_action( 'pre_get_posts', 'us_list_order_for_current_wp_query', 501 );

	/**
	 * Applies "List Order" query to the global wp_query.
	 */
	function us_list_order_for_current_wp_query( $wp_query ) {
		if (
			! $wp_query->is_main_query()
			AND $wp_query->get( 'apply_list_url_params' )
			AND (
				! is_admin()
				OR wp_doing_ajax()
			)
			AND $orderby_params = $_REQUEST['_orderby'] ?? ''
		) {
			us_apply_orderby_to_list_query( $wp_query->query_vars, $orderby_params );
		}
	}
}

if ( ! function_exists( 'us_list_search_for_current_wp_query' ) ) {

	add_action( 'pre_get_posts', 'us_list_search_for_current_wp_query', 501 );

	/**
	 * Applies "List Search" query to the global wp_query.
	 */
	function us_list_search_for_current_wp_query( $wp_query ) {
		if (
			! $wp_query->is_main_query()
			AND $wp_query->get( 'apply_list_url_params' )
			AND (
				! is_admin()
				OR wp_doing_ajax()
			)
			AND $search_query = $_REQUEST['_s'] ?? ''
		) {
			$wp_query->set( 's', sanitize_text_field( $search_query ) );
		}
	}
}

if ( ! function_exists( 'us_ajax_output_list_pagination' ) ) {

	/**
	 * Filters a page HTML to return the div with the "for_current_wp_query" class.
	 *
	 * @param string $content The post content.
	 * @return string Returns HTML of div with the "for_current_wp_query" class.
	 */
	function us_ajax_output_list_pagination( $content ) {
		if (
			class_exists( 'DOMDocument' )
			AND strpos( $content, 'for_current_wp_query' ) !== FALSE
		) {
			$document = new DOMDocument;
			// LIBXML_NOERROR is used to disable errors when HTML5 tags are not recognized by DOMDocument (which supports only HTML4).
			$document->loadHTML( '<meta http-equiv="Content-Type" content="text/html; charset=' . get_bloginfo( 'charset' ) . '">' . $content, LIBXML_NOERROR );
			$query_expression = '//div[contains(@class, "for_current_wp_query") and not(contains(@class, "_carousel"))]';
			$nodes = ( new DOMXpath( $document ) )->query( $query_expression );
			if ( $nodes->count() ) {
				$the_element = $nodes->item( (int) us_arr_path( $_POST, 'us_ajax_list_index' ) );
				$new_document = new DOMDocument;
				$new_document->appendChild( $new_document->importNode( $the_element, TRUE ) );
				$next_element = $the_element->nextSibling;
				if ( $next_element AND ! ( $next_element instanceof DOMText ) ) {
					$next_element_class = (string) $next_element->getAttribute( 'class' );
					if ( strpos( $next_element_class, 'w-grid-none' ) !== FALSE ) {
						$new_document->appendChild( $new_document->importNode( $next_element, TRUE ) );
					}
				}
				return $new_document->saveHTML();
			}
		}
		return $content;
	}
}

if ( ! function_exists( 'us_sort_terms_hierarchically' ) ) {

	/**
	 * Sort terms taking into account their hierarchy
	 *
	 * @param array $terms
	 * @param int $parent
	 * @return array
	 */
	function us_sort_terms_hierarchically( &$terms, $parent = 0 ) {
		$result = array();
		foreach ( $terms as $i => $term ) {
			if ( $term->parent == $parent ) {
				$result[] = $term;
				unset( $terms[ $i ] );
				foreach ( $terms as $item ) {
					if ( $item->parent AND $item->parent === $term->term_id ) {
						$result = array_merge( $result, us_sort_terms_hierarchically( $terms, $term->term_id ) );
					}
				}
			}
		}

		return $result;
	}
}

if ( ! function_exists( 'us_get_faceted_filter_post_ids' ) ) {

	/**
	 * Get only post ids by provided query and applied filters
	 *
	 * @param array $query_args
	 * @param array $filters Specified filter params
	 *
	 * @return array
	 */
	function us_get_faceted_filter_post_ids( $query_args, $filters = array() ) {

		// Force needed params to speed up the query
		$query_args = array_merge( $query_args, array(
			'paged' => 1,
			'posts_per_page' => -1,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'cache_results' => FALSE,
			'no_found_rows' => TRUE,
			'nopaging' => TRUE,
			'fields' => 'ids',
			'us_faceted_filtering' => TRUE, // prevent applying filters from URL
		) );

		$query_args = apply_filters( 'us_get_faceted_filter_post_ids_args', $query_args );

		us_apply_filtering_to_list_query( $query_args, $filters );

		$query = new WP_Query( $query_args );

		return ( $query->posts ) ? $query->posts : array( 0 );
	}
}

if ( ! function_exists( 'us_list_filter_get_post_count' ) ) {

	/**
	 * Get post count for every value of all filter items.
	 *
	 * @param array $query_args Post/Product List query arguments.
	 * @param array $list_filters List of names and types of all defined filters.
	 *
	 * @return array
	 */
	function us_list_filter_get_post_count( $query_args, $list_filters = array() ) {

		if ( ! is_array( $query_args ) OR ! is_array( $list_filters ) OR empty( $list_filters ) ) {
			return array();
		}

		if ( ! US_Filter_Indexer::instance()->get_row_count() ) {
			return array();
		}

		// Get unfiltered post_ids
		$unfiltered_post_ids = us_get_faceted_filter_post_ids( $query_args );

		// Get filtered post_ids
		$filters_with_or_values = $filtered_post_ids = array();
		if ( $selected_filters = us_get_filter_params_from_request() ) {

			$filtered_post_ids = us_get_faceted_filter_post_ids( $query_args, $selected_filters );

			// Collect filtered post IDs separately for every filter_name
			foreach ( $list_filters as $filter_name => $filter_type ) {

				// Exclude filter types that don't change their values when filtering
				if ( $filter_type == 'date_picker' ) {
					continue;
				}

				// Exclude taxonomies that use the "AND" term operator
				if ( mb_substr( $filter_name, -4 ) == '|and' ) {
					continue;
				}

				// Get post IDs from applied filter items EXCEPT the current one to support "OR" choise
				if ( isset( $selected_filters[ $filter_name ] ) ) {

					$processed_filters = $selected_filters; // keep 'selected_filters' untouched
					unset( $processed_filters[ $filter_name ] );

					if ( ! empty( $processed_filters ) ) {
						$post_ids = us_get_faceted_filter_post_ids( $query_args, $processed_filters );

					} else {
						$post_ids = $unfiltered_post_ids;
					}

					// Remove suffix: 'price|between' => 'price'
					$filter_name = strtok( $filter_name, '|' );

					$filters_with_or_values[ $filter_name ] = $post_ids;
				}
			}
		}

		global $wpdb;

		$filter_names = array();
		$filters_with_range_selection = array();
		$filters_with_date_values = array();

		$global_filter_params = us_get_list_filter_params();

		foreach ( $list_filters as $filter_name => $filter_type ) {
			$name = strtok( $filter_name, '|' );

			if (
				us_arr_path( $global_filter_params, $name . '.value_type' ) == 'date'
				OR us_arr_path( $global_filter_params, $name . '.value_type' ) == 'date_time'
			) {
				$filters_with_date_values[] = $name;
			}

			if (
				$filter_type == 'range_slider'
				OR $filter_type == 'range_input'
			) {
				$filters_with_range_selection[] = $name;
			}

			// Escape values for using in SQL
			$filter_names[] = $wpdb->prepare( '%s', $name );
		}

		// Collect unfiltered post IDs grouped by filter_name and filter_value
		$all_filters = array();
		$sql = "
			SELECT
				`post_id`, `filter_name`, `filter_value`
			FROM `{$wpdb->us_filter_index}`
			WHERE
				`filter_name` IN (" . implode( ',', $filter_names ) . ")
				AND `post_id` IN (" . implode( ',', $unfiltered_post_ids ) . ");
		";
		foreach ( (array) $wpdb->get_results( $sql ) as $row ) {
			foreach ( (array) maybe_unserialize( $row->filter_value ) as $filter_value ) {

				$filter_name = $row->filter_name;

				// Change all date values into a single format: 2024-01
				if ( in_array( $filter_name, $filters_with_date_values ) ) {
					$filter_value = wp_date( 'Y-m', (int) strtotime( $filter_value ) );
				}

				$all_filters[ $filter_name ][ $filter_value ][] = (int) $row->post_id;
			}
		}

		$results = array();

		// Intersect unfiltered post IDs with filtered post IDs to get post count for every filter value
		foreach ( $all_filters as $filter_name => $filter_values ) {
			foreach ( $filter_values as $filter_value => $post_ids ) {

				if ( isset( $filters_with_or_values[ $filter_name ] ) ) {
					$post_ids = array_intersect( $filters_with_or_values[ $filter_name ], $post_ids );

				} elseif ( $filtered_post_ids ) {
					$post_ids = array_intersect( $filtered_post_ids, $post_ids );
				}

				$post_count = count( $post_ids );

				// Filters with range values don't need post count, but need all values to define their min and max
				if ( in_array( $filter_name, $filters_with_range_selection ) ) {
					if ( $post_count ) {
						$results[ $filter_name ][] = (float) $filter_value;
					}

				} else {
					$results[ $filter_name ][ $filter_value ] = $post_count;
				}
			}
		}

		// Collect min and max values for filters with range values
		foreach ( $filters_with_range_selection as $filter_name ) {
			if ( ! isset( $results[ $filter_name ] ) ) {
				$results[ $filter_name ] = array( 0, 0 );
				continue;
			}
			$results[ $filter_name ] = array(
				min( $results[ $filter_name ] ),
				max( $results[ $filter_name ] ),
			);
		}

		return $results;
	}
}

if ( ! function_exists( 'us_get_post_ids_from_filter_index' ) ) {

	/**
	 * Get post ids from 'us_filter_index' database table by provided values
	 */
	function us_get_post_ids_from_filter_index( $filter_name, $values, $value_compare, $value_type ) {

		if ( empty( $values ) ) {
			return array();
		}

		// Escape vars for SQL queries below
		$filter_name = esc_sql( $filter_name );
		$values = esc_sql( $values );

		$post_ids = array();

		global $wpdb;
		$sql = "
			SELECT DISTINCT post_id
			FROM {$wpdb->us_filter_index}
			WHERE filter_name = '{$filter_name}'";

		// First process the "Date" values.
		// They can have all selection types and compare types.
		// All possible URL cases (spaces added for better understanding):
		// _date = 2024
		// _date = 2024-01
		// _date = 2024-01-01
		// _date = 2023-02, 2024-01, 2025-12
		// _date = 2020, 2024, 2025
		// _date|after = 2024-01-01
		// _date|before = 2024-01-01
		// _date|between = 2024-01-01, 2025-12-31
		// _date|between = 2024-01-01,
		// _date|between = , 2025-12-31
		// _date|between = 2023-2025
		if ( $value_type == 'date' OR $value_type == 'date_time' ) {

			$min = ! empty( $values[0] ) ? $values[0] : FALSE;
			$max = ! empty( $values[1] ) ? $values[1] : FALSE;

			if ( $value_compare == 'after' ) {
				$max = FALSE;

			} elseif ( $value_compare == 'before' ) {
				$max = $min;
				$min = FALSE;

			} elseif ( $value_compare == 'between' AND count( $values ) === 1 ) {
				$values = explode( '-', $values[0] );

			} elseif ( $value_compare == '' ) {
				$max = $min;
			}

			// Date value may have formats: '2024' or '2024-01' or '2024-01-01'
			// Also, there can be more than 2 values, so we use foreach()
			foreach ( $values as $value ) {

				if ( empty( $value ) ) {
					continue;
				}

				if ( strlen( $value ) === 4 ) {
					$min = $value . '-01-01';
					$max = $value . '-12-31';

				} elseif ( strlen( $value ) === 7 ) {
					$min = $value . '-01';
					$max = $value . '-31';
				}

				$sql_where = '';

				if ( $min !== FALSE ) {
					$sql_where .= " AND LEFT(filter_value, 10) >= '$min'";
				}
				if ( $max !== FALSE ) {
					$sql_where .= " AND LEFT(filter_value, 10) <= '$max'";
				}

				$result = $wpdb->get_col( $sql . $sql_where );

				$post_ids = array_merge( $post_ids, $result );
			}

			// Can be used in all selection type
			// Available URL cases (spaces added for better understanding):
			// _param|between = 33-222
			// _param|between = 0-100, 200-300, 400-500
		} elseif ( $value_compare == 'between' ) {
			foreach ( $values as $value ) {

				$minmax = explode( '-', $value );
				$min = $minmax[0] ?? -999999999999;
				$max = $minmax[1] ?? 999999999999;

				$result = $wpdb->get_col( $sql . " AND (filter_value + 0) >= '$min' AND (filter_value + 0) <= '$max'" );

				$post_ids = array_merge( $post_ids, $result );
			}

			// Used in "checkboxes" selection type
			// Available URL cases (spaces added for better understanding):
			// _param|and = first
			// _param|and = first, second, third
		} elseif ( $value_compare == 'and' ) {

			foreach ( $values as $i => $value ) {

				$result = $wpdb->get_col( $sql . " AND filter_value IN ('$value')" );

				$post_ids = ( $i > 0 ) ? array_intersect( $post_ids, $result ) : $result;

				if ( empty( $post_ids ) ) {
					break;
				}
			}

			// Available URL cases (spaces added for better understanding):
			// _param = 1
			// _param = first
			// _param = first, second, third
		} else {
			$values = implode( "','", $values );

			$post_ids = $wpdb->get_col( $sql . " AND filter_value IN ('$values')" );
		}

		return $post_ids;
	}
}

if ( ! function_exists( 'us_list_posts_per_archive_page_save_to_post_meta' ) ) {

	add_action( 'post_updated', 'us_list_posts_per_archive_page_save_to_post_meta', 501, 2 );

	/**
	 * Save "posts_per_archive_page" to post metadata.
	 */
	function us_list_posts_per_archive_page_save_to_post_meta( $post_id, $post ) {

		if ( ! in_array( $post->post_type, array( 'us_page_block', 'us_content_template', 'page' ) ) ) {
			return;
		}

		$the_content = $post->post_content ?? '';

		// Saving the "Reusable Blocks" IDs of the current post.
		if ( preg_match_all( '/\[us_page_block[^\]]+id="(\d+)"/i', $the_content, $matches ) ) {
			update_post_meta( $post_id, '_us_page_block_ids', implode( ',', $matches[1] ) );
		} else {
			delete_post_meta( $post_id, '_us_page_block_ids' );
		}

		if ( ! in_array( $post->post_type, array( 'us_page_block', 'us_content_template' ) ) ) {
			return;
		}

		$tagnames = array( 'us_post_list', 'us_product_list' );

		if ( preg_match_all( '/' . get_shortcode_regex( $tagnames ) . '/', $the_content, $matches ) ) {
			foreach ( array_reverse( $matches[3] ) as $atts ) {
				if ( strpos( $atts, 'pagination="' ) === FALSE ) {
					continue;
				}
				if ( preg_match( '/\s?posts_per_archive_page="(\d+)"/', $atts, $match ) ) {
					$meta_value = $match[1];
					break;
				}
			}
		}

		if ( isset( $meta_value ) ) {
			update_post_meta( $post_id, '_us_posts_per_archive_page', $meta_value );
		} else {
			delete_post_meta( $post_id, '_us_posts_per_archive_page' );
		}
	}
}

if ( ! function_exists( 'us_list_set_posts_per_archive_page' ) ) {

	add_filter( 'pre_get_posts', 'us_list_set_posts_per_archive_page', 501 );

	/**
	 * Set number of posts on archive page.
	 */
	function us_list_set_posts_per_archive_page( $wp_query ) {

		// Exclude "/wp-admin/..." non-AJAX requests. See issue #5157
		if ( is_admin() AND ! wp_doing_ajax() ) {
			return;
		}

		if ( ! $wp_query->is_main_query() ) {
			return;
		}

		// Search Results page ID if set
		if ( is_search() AND ( $search_page = us_get_option( 'search_page' ) ) !== 'default' ) {
			$post_id = (int) $search_page;

			// Posts page ID if set
		} elseif ( is_home() AND $posts_page = us_get_page_for_posts() ) {
			$post_id = (int) $posts_page;

			// Page Templates (Archive Page)
		} elseif ( $wp_query->is_archive ) {
			$post_id = us_get_page_area_id( 'content' );
		}

		if ( isset( $post_id ) AND get_post_status( $post_id ) !== FALSE ) {

			$posts_per_page = (int) get_post_meta( $post_id, '_us_posts_per_archive_page', TRUE );

			// If no data, check the first "Reusable Block"
			if ( ! $posts_per_page AND $first_page_block_id = (int) get_post_meta( $post_id, '_us_page_block_ids', TRUE ) ) {
				$posts_per_page = (int) get_post_meta( $first_page_block_id, '_us_posts_per_archive_page', TRUE );
			}

			if ( $posts_per_page ) {
				$wp_query->set( 'posts_per_page', $posts_per_page );
			}
		}
	}
}

if ( ! function_exists( 'us_list_query_offset' ) ) {

	/**
	 * Adjust pagination count if offset is set
	 */
	function us_list_query_offset( &$query ) {
		if ( ! isset( $query->query['_id'] ) OR $query->query['_id'] !== 'us_list_with_offset' ) {
			return;
		}

		global $us_list_query_offset;

		// First check if 'posts_per_archive_page' is set
		$posts_per_page = $query->query['posts_per_archive_page'] ?? 0;

		if ( empty( $posts_per_page ) ) {
			$posts_per_page = $query->query['posts_per_page'] ?? get_option( 'posts_per_page' );
		}

		if ( $query->is_paged ) {
			$page_offset = $us_list_query_offset + ( ( $query->query_vars['paged'] - 1 ) * $posts_per_page );

			// Apply adjust page offset
			$query->set( 'offset', $page_offset );

		} else {
			// This is the first page. Just use the offset...
			$query->set( 'offset', $us_list_query_offset );
		}

		remove_action( 'pre_get_posts', 'us_list_query_offset' );
	}
}

if ( ! function_exists( 'us_list_adjust_offset_pagination' ) ) {

	/**
	 * Adjust pagination count if offset is set
	 */
	function us_list_adjust_offset_pagination( $found_posts, $query ) {
		if ( ! isset( $query->query['_id'] ) OR $query->query['_id'] !== 'us_list_with_offset' ) {
			return $found_posts;
		}

		global $us_list_query_offset;
		remove_filter( 'found_posts', 'us_list_adjust_offset_pagination' );

		// Reduce WordPress's found_posts count by the offset...
		return $found_posts - $us_list_query_offset;
	}
}
