<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Polylang Support
 *
 * @link https://polylang.pro/
 */

if ( ! function_exists( 'pll_languages_list' ) ) {
	return;
}

if ( is_admin() ) {
	if ( ! function_exists( 'us_pll_wp_insert_post_data' ) ) {

		add_filter( 'wp_insert_post_data', 'us_pll_wp_insert_post_data', 100, 2 );

		/**
		 * Update post data before creating a translation.
		 *
		 * @param array $data
		 * @param array $postarr
		 * @return array
		 */
		function us_pll_wp_insert_post_data( $data, $postarr ) {
			if (
				in_array( us_arr_path( $data, 'post_type' ), array( 'us_header', 'us_grid_layout' ) )
				AND ! empty( $_GET['new_lang'] )
				AND us_arr_path( $data, 'post_status' ) === 'auto-draft'
			) {
				$data['post_status'] = 'publish';

				if ( $original_post = get_post( (int) $_GET['from_post'] ) ) {
					$data = array_merge(
						$data, array(
							'post_title' => $original_post->post_title,
							'post_content' => $original_post->post_content,
						)
					);
				}
			}

			return $data;
		}
	}

	if ( ! function_exists( 'us_pll_save_post_types' ) ) {

		add_action( 'save_post_us_header', 'us_pll_save_post_types', 10, 3 );
		add_action( 'save_post_us_grid_layout', 'us_pll_save_post_types', 10, 3 );

		/**
		 * @param int $post_ID
		 * @param WP_Post $post
		 * @param bool $update
		 */
		function us_pll_save_post_types( $post_ID, $post, $update ) {
			if (
				isset( $_GET['new_lang'], $_GET['from_post'] )
				AND $update === FALSE
				AND function_exists( 'pll_save_post_translations' )
			) {
				$translations = pll_get_post_translations( (int) $_GET['from_post'] );
				$translations[ sanitize_key( $_GET['new_lang'] ) ] = $post_ID;
				pll_save_post_translations( array_map( 'absint', $translations ) );
			}
		}
	}

	if ( pll_current_language() != pll_default_language() ) {
		global $pagenow;

		// If the link contains post_type from the theme settings, then we will connect the builders
		$post_type = ! empty( $_GET['post_type'] ) ? $_GET['post_type'] : NULL;
		if (
			$pagenow == 'post-new.php'
			AND ! empty( $_GET['new_lang'] )
			AND in_array( $post_type, array( 'us_header', 'us_grid_layout' ) )
		) {
			// Add Header Builder actions for headers
			if ( $post_type == 'us_header' ) {
				add_action( 'admin_enqueue_scripts', 'us_hb_enqueue_scripts' );
				add_action( 'edit_form_top', 'us_hb_edit_form_top' );

				// Add Grid Layout Builder actions for grid layouts
			} elseif ( $post_type == 'us_grid_layout' ) {
				add_action( 'admin_enqueue_scripts', 'usgb_enqueue_scripts' );
				add_action( 'edit_form_top', 'usgb_edit_form_top' );
			}
		}
	}
}

if ( ! function_exists( 'us_pll_init_cpt_labels' ) ) {

	add_action( 'init', 'us_pll_init_cpt_labels' );

	/**
	 * Register custom strings in the translation panel.
	 *
	 * @param string $cpts
	 * @return string
	 */
	function us_pll_init_cpt_labels( $cpts ) {
		$types = get_post_types(
			array(
				'_builtin' => FALSE,
				'public' => TRUE,
				'publicly_queryable' => TRUE,
			),
			'objects'
		);
		if ( ! empty ( $types ) AND function_exists( 'pll_register_string' ) ) {
			foreach ( $types as $type ) {
				pll_register_string( 'themes', $type->name );
				pll_register_string( 'themes', $type->label );
				if ( ! empty ( $type->description ) ) {
					pll_register_string( 'themes', $type->description );
				}
				foreach ( $type->labels as $label ) {
					pll_register_string( 'themes', $label );
				}
			}
		}

		return $cpts;
	}
}

if ( ! function_exists( 'us_pll_tr_selected_lang_page' ) ) {

	add_filter( 'us_tr_selected_lang_page', 'us_pll_tr_selected_lang_page', 10 );

	/**
	 * Check selected language on page.
	 *
	 * @param bool $default_value
	 * @return bool
	 */
	function us_pll_tr_selected_lang_page( $default_value = FALSE ) {
		if ( ! empty( $_REQUEST['lang'] ) ) {
			return strtolower( $_REQUEST['lang'] ) !== 'all';
		} elseif ( ! empty( $_COOKIE['pll_language'] ) ) {
			return strtolower( $_COOKIE['pll_language'] ) !== 'all';
		}

		return $default_value;
	}
}

if ( ! function_exists( 'us_pll_tr_default_language' ) ) {

	add_filter( 'us_tr_default_language', 'us_pll_tr_default_language', 10, 1 );

	/**
	 * Returns the default language.
	 *
	 * @param mixed $empty_value
	 * @return string
	 */
	function us_pll_tr_default_language( $empty_value = NULL ) {
		return pll_default_language();
	}
}

if ( ! function_exists( 'us_pll_tr_current_language' ) ) {

	add_filter( 'us_tr_current_language', 'us_pll_tr_current_language', 10, 1 );

	/**
	 * Get the current language for an interface.
	 *
	 * @param mixed $empty_value Filter plug
	 * @return string
	 */
	function us_pll_tr_current_language( $empty_value = NULL ) {
		return pll_current_language();
	}
}

if ( ! function_exists( 'us_pll_tr_object_id' ) ) {

	add_filter( 'us_tr_object_id', 'us_pll_tr_object_id', 10, 4 );

	/**
	 * Returns a translated post.
	 *
	 * @param integer $elm_id
	 * @param string $type
	 * @param bool $return_original_if_missing
	 * @param mixed $lang_code
	 * @return int|bool
	 */
	function us_pll_tr_object_id( $elm_id, $post_type = 'post', $return_original_if_missing = FALSE, $lang_code = NULL ) {
		if ( $tr_elm_id = pll_get_post( $elm_id ) ) {
			return $tr_elm_id;
		}

		// If there is no translation, we will return the original $elm_id
		return $elm_id;
	}
}

if ( ! function_exists( 'us_pll_tr_get_post_language_code' ) ) {

	add_filter( 'us_tr_get_post_language_code', 'us_pll_tr_get_post_language_code', 10, 1 );

	/**
	 * Get post language code.
	 *
	 * @param intval|string $post_id
	 * @return bool|string
	 */
	function us_pll_tr_get_post_language_code( $post_id = '' ) {
		return pll_get_post_language( $post_id );
	}
}

if ( ! function_exists( 'us_pll_tr_home_url' ) ) {

	add_filter( 'us_tr_home_url', 'us_pll_tr_home_url', 10, 2 );

	function us_pll_tr_home_url() {
		return pll_home_url();
	}
}

if ( ! function_exists( 'us_pll_tr_switch_language' ) ) {

	add_action( 'us_tr_switch_language', 'us_pll_tr_switch_language', 10, 1 );

	/**
	 * Switch a global language.
	 *
	 * @param string $language_code
	 */
	function us_pll_tr_switch_language( $language_code = NULL ) {
		// We are using action named wpml_switch_language, because polylang added its own fallback for action with this name
		do_action( 'wpml_switch_language', $language_code );
	}
}

if ( ! function_exists( 'us_pll_tr_get_term_language' ) ) {

	add_filter( 'us_tr_get_term_language', 'us_pll_tr_get_term_language', 10, 1 );

	/**
	 * Returns the term language.
	 *
	 * @param int $term_id
	 * @return bool|string
	 */
	function us_pll_tr_get_term_language( $term_id ) {
		return pll_get_term_language( $term_id );
	}
}

if ( ! function_exists( 'us_pll_tr_setting' ) ) {

	add_filter( 'us_tr_setting', 'us_pll_tr_setting', 10, 2 );

	/**
	 * Returns a Polylang setting value.
	 *
	 * @param mixed $default
	 * @param string $key
	 * @return mixed
	 */
	function us_pll_tr_setting( $key, $default ) {
		$options = get_option( 'polylang' );

		return ( isset( $options[ $key ] ) ? $options[ $key ] : $default );
	}
}

if ( ! function_exists( 'us_pll_translate_post_meta' ) ) {

	add_filter( 'pll_translate_post_meta', 'us_pll_translate_post_meta', 10, 5 );

	/**
	 * Keeps current values on original post Page Layout.
	 *
	 * @param $value string Meta value
	 * @param $key string Meta key
	 * @param $lang string Language slug of the target post
	 * @param $from integer Id of the post from which we copy information
	 * @param $to integer Id of the post to which we paste information
	 */
	function us_pll_translate_post_meta( $value, $key, $lang, $from, $to ) {
		$skipped_metas = array(
			'us_header_id',
			'us_footer_id',
			'us_content_id',
			'us_titlebar_id',
			'us_sidebar_id',
		);

		if (
			in_array( $key, $skipped_metas )
			AND function_exists( 'pll_default_language' )
			AND function_exists( 'pll_get_post_language' )
		) {

			$default_language = pll_default_language();
			$current_language = pll_get_post_language( $from );

			// Check whether we translate to default language
			if ( $default_language == $lang ) {
				if ( $current_language != $default_language ) {
					// Save values of the main post as the current post values
					update_post_meta( $from, $key, get_post_meta( $to, $key, TRUE ) );
				}

				return get_post_meta( $to, $key, TRUE );
			}
		}

		return $value;
	}
}

if ( ! function_exists( 'us_polylang_add_og_meta_tags' ) ) {

	add_filter( 'us_meta_tags', 'us_polylang_add_og_meta_tags', 10, 1 );

	/**
	 * Add "og:locale:alternate" meta tags for Polylang.
	 *
	 * @param $meta_tags
	 * @return array
	 */
	function us_polylang_add_og_meta_tags( $meta_tags ) {

		$languages = pll_the_languages( array( 'raw' => true ) );
		$current_language = pll_current_language();

		if ( ! empty( $languages ) ) {
			foreach ( $languages as $lang_code => $lang_data ) {
				if ( $lang_code !== $current_language AND ! empty( $lang_data['locale'] ) ) {
					$meta_tags['og:locale:alternate'][] = $lang_data['locale'];
				}
			}
		}

		return (array) $meta_tags;
	}
}

if ( ! function_exists( 'us_pll_include_untranslated_media' ) ) {

	add_filter( 'us_grid_listing_query_args', 'us_pll_include_untranslated_media' );
	add_filter( 'us_gallery_query_args', 'us_pll_include_untranslated_media' );
	add_filter( 'us_post_list_query_args', 'us_pll_include_untranslated_media' );
	add_filter( 'us_image_slider_query_args', 'us_pll_include_untranslated_media' );

	/**
	 * Allow lists to query untranslated media for manually selected images.
	*/
	function us_pll_include_untranslated_media( $query_args ) {

		// Do not apply for post types other than media
		if (
			! empty( $query_args['post_type'] )
			AND $query_args['post_type'] !== 'attachment'
			AND ! (
				is_array( $query_args['post_type'] )
				AND in_array( 'attachment', $query_args['post_type'], TRUE )
			)
		) {
			return $query_args;
		}
		
		// Do not apply if Polylang Media translation is not enabled
		if ( ! function_exists('PLL') OR empty( PLL()->options['media_support'] ) ) {
			return $query_args;
		}

		// Apply only if there are manually selected images
		if (
			! empty ( $query_args['post__in'] )
			OR ! empty ( $query_args['include'] )
		) {
			$query_args['lang'] = ''; // disabling polylang language-based filtering

			$selected_images_ids = ! empty( $query_args['post__in'] ) ? $query_args['post__in'] : $query_args['include'];
			$translated_selected_images_ids = array();

			if ( ! is_array( $selected_images_ids ) ) {
				$selected_images_ids = explode( ',', $selected_images_ids );
			}
			foreach ( $selected_images_ids as $id ) {
				$translations = pll_get_post_translations( $id );
				$maybe_translated_image_id = $id;
				foreach ( $translations as $lang => $translation_id ) {
					if ( $lang === pll_current_language() ) {
						$maybe_translated_image_id = $translation_id;
						break;
					}
				}
				$translated_selected_images_ids[] = (string) $maybe_translated_image_id;
			}

			if ( ! empty( $query_args['post__in'] ) ) {
				$query_args['post__in'] = $translated_selected_images_ids;
			} else {
				$query_args['include'] = $translated_selected_images_ids;
			}
		}

		return $query_args;
	}
}

if ( ! function_exists( 'us_pll_force_translate_tax_query' ) ) {

	add_filter( 'us_post_list_query_args', 'us_pll_force_translate_tax_query' );

	function us_pll_force_translate_tax_query( $query_args ) {

		// If language is explicitly set, polylang parses tax query by language automatically
		if ( ! empty( $query_args['tax_query'] ) AND ! isset( $query_args['lang'] ) ) {
			$query_args['lang'] = pll_current_language();
		}

		return $query_args;
	}
}

if ( ! function_exists( 'us_pll_get_post_ids_for_autocomplete' ) ) {
	add_filter( 'us_get_post_ids_for_autocomplete_query_args', 'us_pll_get_post_ids_for_autocomplete' );

	/**
	 * Add language parameter to get post ids for current language in autocomplete
	 *
	 * @param $query_args
	 * @return array
	 */
	function us_pll_get_post_ids_for_autocomplete( $query_args ) {
		if ( ! isset( $query_args['lang'] ) ) {
			$query_args['lang'] = pll_current_language();
		}

		return $query_args;
	}
}
