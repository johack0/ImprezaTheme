<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

// Disable Gutenberg (Block Editor) when editing any posts in admin
if ( ! us_get_option( 'block_editor' ) ) {
	remove_theme_support( 'core-block-patterns' );
	add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );
	remove_action( 'wp_enqueue_scripts', 'wp_common_block_scripts_and_styles' );
}

if ( ! function_exists( 'us_templates_admin_menu' ) ) {
	/**
	 * Add "Templates" admin menu item when White Label is not active
	 */
	if ( ! us_get_option( 'white_label' ) ) {
		add_action( 'admin_menu', 'us_templates_admin_menu', 9 );
	}
	function us_templates_admin_menu() {
		$capability = us_get_option( 'templates_access_for_editors' ) ? 'edit_pages' : 'manage_options';

		add_menu_page( us_translate( 'Templates' ), us_translate( 'Templates' ), $capability, 'edit.php?post_type=us_header', '', 'dashicons-welcome-widgets-menus', '59.002' );
	}
}

// Should be inited before the WPBakery Page Builder (that is 9)
global $portfolio_slug;
$portfolio_slug = us_get_option( 'portfolio_slug', 'portfolio' );

if ( ! function_exists( 'us_create_testimonials_post_type' ) ) {
	/**
	 * Register post type Testimonials
	 */
	function us_create_testimonials_post_type() {

		// Testimonial Categories
		register_taxonomy(
			'us_testimonial_category', array( 'us_testimonial' ), array(
				'labels' => array(
					'name' => __( 'Testimonial Categories', 'us' ),
					'menu_name' => us_translate( 'Categories' ),
				),
				'public' => TRUE,
				'show_admin_column' => TRUE,
				'publicly_queryable' => FALSE,
				'show_in_nav_menus' => FALSE,
				'show_in_rest' => FALSE,
				'show_tagcloud' => FALSE,
				'hierarchical' => TRUE,
			)
		);

		// Testimonial post type
		register_post_type(
			'us_testimonial', array(
				'labels' => array(
					'name' => __( 'Testimonials', 'us' ),
					'singular_name' => __( 'Testimonial', 'us' ),
					'add_new' => __( 'Add Testimonial', 'us' ),
					'add_new_item' => __( 'Add Testimonial', 'us' ),
					'edit_item' => __( 'Edit Testimonial', 'us' ),
					'featured_image' => __( 'Author Photo', 'us' ),
				),
				'public' => TRUE,
				'publicly_queryable' => FALSE,
				'show_in_nav_menus' => FALSE,
				'capability_type' => 'page',
				'supports' => array(
					'title',
					'editor',
					'thumbnail',
				),
				'menu_icon' => 'dashicons-testimonial',
				'rewrite' => FALSE,
				'query_var' => FALSE,
			)
		);
	}
}

if ( ! function_exists( 'us_create_portfolio_post_type' ) ) {
	/**
	 * Register post type Portfolio
	 */
	function us_create_portfolio_post_type() {

		global $portfolio_slug;
		if ( $portfolio_slug == '' ) {
			$portfolio_rewrite = array(
				'slug' => FALSE,
				'with_front' => FALSE,
			);
		} else {
			$portfolio_rewrite = array(
				'slug' => untrailingslashit( $portfolio_slug ),
				'with_front' => (bool) ! us_get_option( 'portfolio_slug_ignore_prefix', 0 ),
			);
		}

		// Portfolio Categories
		register_taxonomy(
			'us_portfolio_category', array( 'us_portfolio' ), array(
				'labels' => array(
					'name' => apply_filters( 'us_portfolio_category_label', __( 'Portfolio Categories', 'us' ) ),
				),
				'show_admin_column' => TRUE,
				'hierarchical' => TRUE,
				'rewrite' => array( 'slug' => us_get_option( 'portfolio_category_slug', 'portfolio_category' ) ),
			)
		);

		// Portfolio Tags
		register_taxonomy(
			'us_portfolio_tag', array( 'us_portfolio' ), array(
				'labels' => array(
					'name' => apply_filters( 'us_portfolio_tags_label', __( 'Portfolio Tags', 'us' ) ),
				),
				'show_admin_column' => TRUE,
				'rewrite' => array( 'slug' => us_get_option( 'portfolio_tag_slug' ) ),
			)
		);

		// Portfolio Page post type
		register_post_type(
			'us_portfolio', array(
				'labels' => apply_filters(
					'us_portfolio_labels', array(
						'name' => __( 'Portfolio', 'us' ),
						'singular_name' => __( 'Portfolio Page', 'us' ),
						'add_new' => __( 'Add Portfolio Page', 'us' ),
						'add_new_item' => __( 'Add Portfolio Page', 'us' ),
						'edit_item' => __( 'Edit Portfolio Page', 'us' ),
						'featured_image' => us_translate_x( 'Featured Image', 'page' ),
						'view_item' => us_translate( 'View Page' ),
						'not_found' => us_translate( 'No pages found.' ),
						'not_found_in_trash' => us_translate( 'No pages found in Trash.' ),
					)
				),
				'public' => TRUE,
				'rewrite' => $portfolio_rewrite,
				'supports' => array(
					'title',
					'editor',
					'excerpt',
					'thumbnail',
					'custom-fields',
					'revisions',
					'comments',
					'author',
				),
				'menu_icon' => 'dashicons-images-alt',
			)
		);

		// Add "Preview" column for Portfolio Pages
		add_filter( 'manage_us_portfolio_posts_columns', 'us_add_preview_column' );
		add_action( 'manage_us_portfolio_posts_custom_column', 'us_add_preview_column_value', 10, 2 );
		function us_add_preview_column( $columns ) {
			$num = 1; // after which column paste new column
			$new_column = array( 'us_preview' => '&nbsp;' );

			return array_slice( $columns, 0, $num ) + $new_column + array_slice( $columns, $num );
		}

		function us_add_preview_column_value( $column_name, $post_ID ) {
			if ( $column_name == 'us_preview' AND $thumbnail_id = get_post_meta( $post_ID, '_thumbnail_id', TRUE ) ) {
				echo wp_get_attachment_image( $thumbnail_id, 'thumbnail', TRUE );
			}
		}

		// Portfolio slug may have changed, so we need to keep WP's rewrite rules fresh
		$alloptions = wp_load_alloptions();
		if (
			us_get_option( 'enable_portfolio', 1 )
			AND isset( $alloptions['us_flush_rules'] )
		) {
			flush_rewrite_rules();
			delete_option( 'us_flush_rules' );
		}

		if ( ! function_exists( 'us_portfolio_hide_metabox' ) ) {
			/**
			 * Hidden meta box in us_portfolio
			 * @param $hidden
			 * @param $screen
			 * @return mixed|string[]
			 */
			function us_portfolio_hide_metabox( $hidden, $screen ) {
				if ( ! empty( $screen->post_type ) AND $screen->post_type === 'us_portfolio' ) {
					$hidden = array(
						'slugdiv',
						'trackbacksdiv',
						'postcustom',
						'postexcerpt',
						'commentstatusdiv',
						'commentsdiv',
						'authordiv',
						'revisionsdiv',
					);
				}

				return $hidden;
			}

			add_filter( 'default_hidden_meta_boxes', 'us_portfolio_hide_metabox', 10, 2 );
		}
	}
}

if ( ! function_exists( 'us_create_cform_inbound_post_type' ) ) {
	/**
	 * Register post type Inbound Messages
	 */
	function us_create_cform_inbound_post_type() {

		register_post_type(
			'us_cform_inbound', array(
				'labels' => array(
					'name' => __( 'Inbound Messages', 'us' ),
					'singular_name' => __( 'Inbound Message', 'us' ),
					'edit_item' => __( 'Inbound Message', 'us' ),
					'search_items' => us_translate( 'Search' ),
					'not_found' => us_translate( 'No results found.' ),
					'not_found_in_trash' => us_translate( 'No results found.' ),
				),
				'capabilities' => array(
					'create_posts' => FALSE, // Removes the "Add New" link
				),
				'map_meta_cap' => TRUE,
				'public' => FALSE,
				'rewrite' => FALSE,
				'query_var' => FALSE,
				'show_ui' => TRUE,
				'menu_icon' => 'dashicons-email',
				'has_archive' => FALSE,
			)
		);

		if ( ! function_exists( 'us_cform_inbound_after_title' ) ) {
			add_action( 'edit_form_after_title', 'us_cform_inbound_after_title', 10, 1 );

			/**
			 * Display Title + Subject: + From: on single Inbound Message
			 * @param WP_Post $post Current post
			 */
			function us_cform_inbound_after_title( $post ) {
				if ( $post->post_type !== 'us_cform_inbound' ) {
					return;
				}
				echo sprintf(
					'<div class="subheading"><strong>%s:</strong>&nbsp;&nbsp;%s</div>',
					us_translate( 'Subject' ),
					get_the_title( $post->ID )
				);
				echo sprintf(
					'<div class="subheading"><strong>%s:</strong>&nbsp;&nbsp;%s &lt;%s&gt;</div>',
					_x( 'From', 'email field', 'us' ),
					(string) get_post_meta( $post->ID, '_cform_inbound_from_name', TRUE ),
					(string) get_post_meta( $post->ID, '_cform_inbound_from_email', TRUE )
				);
			}
		}

		if ( ! function_exists( 'us_cform_inbound_custom_metaboxes' ) ) {
			add_action( 'add_meta_boxes_us_cform_inbound', 'us_cform_inbound_custom_metaboxes' );

			/**
			 * Removes default metaboxes and adds a custom readonly metaboxes
			 * to display the inbound message details
			 */
			function us_cform_inbound_custom_metaboxes() {

				// Remove default metaboxes
				remove_meta_box( 'submitdiv', 'us_cform_inbound', 'side' );
				remove_meta_box( 'slugdiv', 'us_cform_inbound', 'normal' );
				remove_meta_box( 'authordiv', 'us_cform_inbound', 'normal' );
				remove_meta_box( 'commentsdiv', 'us_cform_inbound', 'normal' );
				remove_post_type_support( 'us_cform_inbound', 'editor' );
				remove_post_type_support( 'us_cform_inbound', 'title' );

				// Sidebar content
				add_meta_box(
					'us_cform_inbound_sidebar',
					us_translate( 'Available Actions' ),
					function( $post ) {
						if ( $post->post_status !== 'trash' ) {
							$trash_url = get_delete_post_link( $post->ID );
							echo '<p><a href="' . esc_url( $trash_url ) . '" class="button-link-delete">'
								. us_translate( 'Move to Trash' ) . '</a></p>';
						}
					},
					'us_cform_inbound',
					'side'
				);

				// Fields: Label + Value
				add_meta_box(
					'us_cform_inbound_fields_table',
					__( 'Fields', 'us' ),
					function( $post ) {
						$labels = get_post_meta( $post->ID, '_cform_inbound_field_labels', TRUE );
						$values = get_post_meta( $post->ID, '_cform_inbound_field_values', TRUE );

						echo '<table class="us_cform_inbound">';
						echo '<tbody>';

						$count = is_array( $labels ) ? count( $labels ) : 0;

						for ( $i = 0; $i < $count; $i++ ) {
							$label = $labels[ $i ];
							$value = $values[ $i ] ?? '';

							echo '<tr>';
							echo '<th>' . esc_html( $label ) . '</th>';
							echo '<td>' . ( $value === '' ? '-' : esc_html( $value ) ) . '</td>';
							echo '</tr>';
						}

						echo '</tbody></table>';
					},
					'us_cform_inbound',
					'normal'
				);

				// Mail details
				add_meta_box(
					'us_cform_inbound_mail_data',
					us_translate( 'More Details' ),
					function ( $post ) {
						$page_title = get_post_meta( $post->ID, '_cform_inbound_page_title', TRUE ) ?: '';
						$page_url = get_post_meta( $post->ID, '_cform_inbound_page_url', TRUE ) ?: '';
						$sender_ip = get_post_meta( $post->ID, '_cform_inbound_sender_ip', TRUE ) ?: '';
						$sender_user_agent = get_post_meta( $post->ID, '_cform_inbound_sender_user_agent', TRUE ) ?: '';

						echo '<table class="us_cform_inbound">';
						echo '<tbody>';
						echo '<tr><th>' . __( 'Sent from page', 'us' ) . '</th>'
							. '<td><a href="' . esc_url( $page_url ) . '" target="_blank">' . esc_html( $page_title )
							. '</a></td></tr>';
						echo '<tr><th>' . us_translate( 'Date' ) . '</th><td>' . (string) get_post_time( get_option( 'date_format' ), $post ) . '</td></tr>';
						echo '<tr><th>' . us_translate( 'Time' ) . '</th><td>' . (string) get_post_time( get_option( 'time_format' ), $post ) . '</td></tr>';
						echo '<tr><th>' . __( 'Sender IP', 'us' ) . '</th><td>' . wp_strip_all_tags( $sender_ip ) . '</td></tr>';
						echo '<tr><th>' . __( 'Sender User Agent', 'us' ) . '</th><td>' . wp_strip_all_tags( $sender_user_agent ) . '</td></tr>';
						echo '</tbody>';
						echo '</table>';
					},
					'us_cform_inbound',
					'normal'
				);
			}
		}

		if ( ! function_exists( 'us_cform_inbound_posts_columns' ) ) {
			add_filter( 'manage_us_cform_inbound_posts_columns', 'us_cform_inbound_posts_columns' );

			/**
			 * Customize the columns for the Inbound Messages post type
			 *
			 * @param array $columns Existing columns
			 * @return array Modified columns
			 */
			function us_cform_inbound_posts_columns( $columns ) {

				// Change "Title" label
				$columns['title'] = us_translate( 'Subject' );

				$news_columns = array(
					'from' => _x( 'From', 'email field', 'us' ),
					'sent_from_page' => __( 'Sent from page', 'us' ),
				);

				return array_slice( $columns, 0, 2 ) + $news_columns + array_slice( $columns, 2 );
			}
		}

		if ( ! function_exists( 'us_cform_inbound_posts_custom_column' ) ) {
			add_action( 'manage_us_cform_inbound_posts_custom_column', 'us_cform_inbound_posts_custom_column', 10, 2 );

			/**
			 * Custom column values for admin posts table
			 * 
			 * @param string $column_name Current column name
			 * @param int $post_ID Current post ID
			 */
			function us_cform_inbound_posts_custom_column( $column_name, $post_ID ) {
				if (
					$column_name == 'from'
					AND $from_name = get_post_meta( $post_ID, '_cform_inbound_from_name', TRUE )
					AND $from_email = get_post_meta( $post_ID, '_cform_inbound_from_email', TRUE )
				) {
					echo sprintf( '%s &lt;%s&gt;', $from_name, $from_email );
				}
				if (
					$column_name == 'sent_from_page'
					AND $_page_url = get_post_meta( $post_ID, '_cform_inbound_page_url', TRUE )
					AND $_page_title = get_post_meta( $post_ID, '_cform_inbound_page_title', TRUE )
				) {
					echo sprintf( '<a href="%s" target="_blank">%s</a>', $_page_url, $_page_title );
				}
			}
		}

		if ( ! function_exists( 'us_cform_post_date_column_status' ) ) {
			add_filter( 'post_date_column_status', 'us_cform_post_date_column_status', 10, 4 );

			/**
			 * Add custom status for post date column
			 *
			 * @param string $status Current status
			 * @param WP_Post $post Current post
			 * @param string $column_name Current column name
			 * @param string $mode Current mode
			 * @return string Modified status
			 */
			function us_cform_post_date_column_status( $status, $post, $column_name, $mode ) {
				if ( $post->post_type === 'us_cform_inbound' ) {
					return us_translate_x( 'Submitted on', 'column name' );
				}
				return $status;
			}
		}

		if ( ! function_exists( 'us_cform_inbound_post_row_actions' ) ) {
			add_filter( 'post_row_actions', 'us_cform_inbound_post_row_actions', 10, 2 );

			/**
			 * Remove extra actions
			 * 
			 * @param array $actions List of actions
			 * @param WP_Post $post Current post
			 * @return array Modified list of actions
			 */
			function us_cform_inbound_post_row_actions( $actions, $post ) {

				if ( $post->post_type === 'us_cform_inbound' AND $post->post_status !== 'trash' ) {
					return array(
						'view' => '<a href="' . get_edit_post_link( $post->ID ) . '" class="view">' . us_translate( 'View' ) . '</a>',
						'trash' => '<a href="' . get_delete_post_link( $post->ID ) . '" class="submitdelete">' . us_translate_x( 'Trash', 'verb' ) . '</a>',
					);
				}

				return $actions;
			}
		}
		
		if ( ! function_exists( 'us_cform_force_publish_after_untrash' ) ) {
			add_action( 'transition_post_status', 'us_cform_force_publish_after_untrash', 10, 3 );

			/**
			 * Force publish after untrash (to avoid Draft status)
			 * 
			 * @param string $new_status
			 * @param string $old_status
			 * @param WP_Post $post
			 */
			function us_cform_force_publish_after_untrash( $new_status, $old_status, $post ) {
				if (
					$post->post_type === 'us_cform_inbound'
					AND $old_status === 'trash'
					AND $new_status === 'draft'
				) {
					// To avoid infinite loop
					remove_action( 'transition_post_status', 'us_cform_force_publish_after_untrash', 10 );
					wp_update_post( array(
						'ID' => $post->ID,
						'post_status' => 'publish',
					) );
					add_action( 'transition_post_status', 'us_cform_force_publish_after_untrash', 10, 3 );
				}
			}	
		}

		// Remove "Publish" tab link
		add_filter( 'views_edit-us_cform_inbound', function( $views ) {
			unset( $views['publish'] );
			return $views;
		} );

		// Export to CSV
		if ( ! function_exists( 'us_cform_inbound_export_csv_button' ) ) {
			add_action( 'manage_posts_extra_tablenav', 'us_cform_inbound_export_csv_button', 10, 1 );

			/**
			 * Add export CSV button to Inbound Messages page
			 *
			 * @param string $which Current location
			 */
			function us_cform_inbound_export_csv_button( $which ) {
				global $typenow;
				if ( $typenow !== 'us_cform_inbound' OR $which !== 'top' ) {
					return;
				}
				?>
				<a href="<?= esc_url( add_query_arg( array( 'action' => 'us_cform_export_csv' ) ) ); ?>" class="button button-primary">
					<?= __( 'Download CSV', 'us' ); ?>
				</a>
				<?php
			}
		}

		if ( ! function_exists( 'us_cform_inbound_export_csv' ) ) {
			add_action( 'admin_init', 'us_cform_inbound_export_csv' );

			/**
			 * Export Inbound Messages to CSV
			 */
			function us_cform_inbound_export_csv() {
				if ( ! isset( $_GET['action'] ) OR $_GET['action'] !== 'us_cform_export_csv' ) {
					return;
				}

				$args = array(
					'post_status' => 'publish',
					'posts_per_page' => -1,
					'orderby' => 'date',
					'order' => 'DESC',
				);

				$args = apply_filters( 'us_cform_inbound_export_csv_args', $args );

				$args['post_type'] = 'us_cform_inbound';

				$inbound_messages = get_posts( $args );

				if ( empty( $inbound_messages ) ) {
					return;
				}

				// Get unique column names (forms may have different fields)
				$columns = array();

				foreach ( $inbound_messages as $message ) {

					$labels = get_post_meta( $message->ID, '_cform_inbound_field_labels', TRUE );

					if ( ! is_array( $labels ) ) {
						continue;
					}

					foreach ( $labels as $label ) {
						$label = trim( $label );

						if ( $label !== '' AND ! in_array( $label, $columns ) ) {
							$columns[] = $label;
						}
					}
				}

				$columns[] = us_translate( 'Date' );

				$columns = apply_filters(
					'us_cform_inbound_export_csv_columns',
					$columns,
					$inbound_messages
				);

				// Generate headers to download CSV
				$file_name = sprintf(
					'%1$s-inbound-messages-%2$s.csv',
					sanitize_key( get_bloginfo( 'name' ) ),
					wp_date( 'Y-m-d' )
				);

				header( 'Content-Description: File Transfer' );
				header( "Content-Disposition: attachment; filename=$file_name" );
				header( 'Content-Type: text/csv; charset=' . get_option( 'blog_charset' ) );

				// Output columns to CSV
				$output = us_cform_write_csv_row( $columns, FALSE );

				// Get values for each column and output to CSV
				foreach ( $inbound_messages as $message ) {

					$labels = get_post_meta( $message->ID, '_cform_inbound_field_labels', TRUE );
					$values = get_post_meta( $message->ID, '_cform_inbound_field_values', TRUE );

					$field_map = array();

					if ( is_array( $labels ) AND is_array( $values ) ) {
						foreach ( $labels as $i => $label ) {
							$field_map[ $label ] = $values[ $i ] ?? '';
						}
					}

					$values_row = array();

					foreach ( $columns as $column ) {
						if ( $column === us_translate( 'Date' ) ) {
							$value = get_post_time( apply_filters( 'us_cform_inbound_export_csv_date_format', 'c' ), false, $message );
						} else {
							$value = $field_map[ $column ] ?? '';
						}

						$values_row[] = $value;
					}

					$values_row = apply_filters(
						'us_cform_inbound_export_csv_values',
						$values_row,
						$message,
						$columns
					);

					$output .= us_cform_write_csv_row( $values_row );
				}

				echo $output;
				exit;
			}
		}

		if ( ! function_exists( 'us_cform_write_csv_row' ) ) {
			/**
			 * Write a row to a CSV file with quotes and line breaks
			 *
			 * @param array $row Row of values
			 * @param bool $use_line_break Use line break
			 *
			 * @return string Formatted row
			 */
			function us_cform_write_csv_row( $row, $use_line_break = TRUE ) {
				$line_break = '';

				if ( $use_line_break ) {
					$line_break = "\r\n";
				}

				$separator = apply_filters( 'us_cform_write_csv_value_separator', ',' );

				$escaped_values = array();

				foreach ( $row as $value ) {

					$value = (string) $value;

					// CSV format
					$value = str_replace( '"', '""', $value );

					$escaped_value = apply_filters( 'us_cform_write_csv_value', '"' . $value . '"', $value );

					$escaped_values[] = $escaped_value;
				}

				if ( empty( $escaped_values ) ) {
					return '';
				}

				return $line_break . implode( $separator, $escaped_values );
			}
		}
	}
}

// Create theme related post types
add_action( 'init', 'us_create_post_types', 8 );
function us_create_post_types() {

	// Define templates editing parent menu and capability
	if ( us_get_option( 'white_label' ) ) {
		$templates_parent_menu = 'us-theme-options';
		$templates_capability = 'us_page_block';
	} else {
		$templates_parent_menu = 'edit.php?post_type=us_header';
		$templates_capability = us_get_option( 'templates_access_for_editors' ) ? 'page' : 'us_page_block';
	}

	if ( us_get_option( 'enable_portfolio', 1 ) ) {
		us_create_portfolio_post_type();
	}

	if ( us_get_option( 'enable_testimonials', 1 ) ) {
		us_create_testimonials_post_type();
	}

	if ( us_get_option( 'cform_inbound_messages', 0 ) ) {
		us_create_cform_inbound_post_type();
	}

	// Media Categories
	if ( us_get_option( 'media_category' ) ) {
		register_taxonomy(
			'us_media_category', array( 'attachment' ), array(
				'labels' => array(
					'name' => __( 'Media Categories', 'us' ),
					'menu_name' => us_translate( 'Categories' ),
				),
				'public' => TRUE,
				'show_admin_column' => TRUE,
				'publicly_queryable' => FALSE,
				'show_in_nav_menus' => FALSE,
				'show_in_rest' => FALSE,
				'show_tagcloud' => FALSE,
				'hierarchical' => TRUE,
				'update_count_callback' => 'us_media_category_update_count_callback',
			)
		);
	}

	// Headers
	register_post_type(
		'us_header', array(
			'labels' => array(
				'name' => _x( 'Headers', 'site top area', 'us' ),
				'singular_name' => _x( 'Header', 'site top area', 'us' ),
				'add_new' => _x( 'Add Header', 'site top area', 'us' ),
				'add_new_item' => _x( 'Add Header', 'site top area', 'us' ),
				'edit_item' => _x( 'Edit Header', 'site top area', 'us' ),
			),
			'public' => TRUE,
			'show_in_menu' => $templates_parent_menu,
			'exclude_from_search' => TRUE,
			'show_in_admin_bar' => FALSE,
			'publicly_queryable' => FALSE,
			'show_in_nav_menus' => FALSE,
			'capability_type' => $templates_capability,
			'map_meta_cap' => TRUE,
			'supports' => FALSE,
			'rewrite' => FALSE,
			'query_var' => FALSE,
			'register_meta_box_cb' => 'us_duplicate_post',
		)
	);

	// Content templates
	register_post_type(
		'us_content_template', array(
			'labels' => array(
				'name' => __( 'Page Templates', 'us' ),
				'singular_name' => __( 'Page Template', 'us' ),
				'add_new' => __( 'Add Page Template', 'us' ),
				'add_new_item' => __( 'Add Page Template', 'us' ),
				'edit_item' => __( 'Edit Page Template', 'us' ),
			),
			'public' => TRUE,
			'show_in_menu' => $templates_parent_menu,
			'exclude_from_search' => TRUE,
			'show_in_admin_bar' => FALSE,
			'publicly_queryable' => usb_is_post_preview(), // Inclusions on the builder page for editing
			'show_in_nav_menus' => FALSE,
			'capability_type' => $templates_capability,
			'map_meta_cap' => TRUE,
			'rewrite' => FALSE,
			'query_var' => FALSE,
			'register_meta_box_cb' => 'us_duplicate_post',
			'supports' => array(
				'title',
				'editor',
				'revisions',
			),
		)
	);

	// Reusable Blocks
	register_post_type(
		'us_page_block', array(
			'labels' => array(
				'name' => __( 'Reusable Blocks', 'us' ),
				'singular_name' => __( 'Reusable Block', 'us' ),
				'add_new' => __( 'Add Reusable Block', 'us' ),
				'add_new_item' => __( 'Add Reusable Block', 'us' ),
				'edit_item' => __( 'Edit Reusable Block', 'us' ),
			),
			'public' => TRUE,
			'show_in_menu' => $templates_parent_menu,
			'exclude_from_search' => TRUE,
			'show_in_admin_bar' => FALSE,
			'publicly_queryable' => usb_is_post_preview(), // inclusions on the builder page
			'show_in_nav_menus' => TRUE,
			'capability_type' => $templates_capability,
			'map_meta_cap' => TRUE,
			'rewrite' => FALSE,
			'query_var' => FALSE,
			'register_meta_box_cb' => 'us_duplicate_post',
			'supports' => array(
				'title',
				'editor',
				'revisions',
			),
		)
	);

	// Grid Layouts
	register_post_type(
		'us_grid_layout', array(
			'labels' => array(
				'name' => __( 'Grid Layouts', 'us' ),
				'singular_name' => __( 'Grid Layout', 'us' ),
				'add_new' => __( 'Add Grid Layout', 'us' ),
				'add_new_item' => __( 'Add Grid Layout', 'us' ),
				'edit_item' => __( 'Edit Grid Layout', 'us' ),
			),
			'public' => TRUE,
			'show_in_menu' => $templates_parent_menu,
			'exclude_from_search' => TRUE,
			'show_in_admin_bar' => FALSE,
			'publicly_queryable' => FALSE,
			'show_in_nav_menus' => FALSE,
			'capability_type' => $templates_capability,
			'map_meta_cap' => TRUE,
			'supports' => FALSE,
			'rewrite' => FALSE,
			'query_var' => FALSE,
			'register_meta_box_cb' => 'us_duplicate_post',
		)
	);

	/*
	* Creates duplication of the post in admin list, called via "Duplicate" link from 'us_post_row_actions_duplicate'
	* also creates post instantly instead of WP auto-draft status
	* also creates additional conditions for "us_header" post types
	*/
	if ( ! function_exists( 'us_duplicate_post' ) ) {
		function us_duplicate_post( $post ) {
			if ( $post->post_status === 'auto-draft' ) {
				// Do not process posts that being created as translations for existing posts
				if ( isset( $_GET['from_post'] ) AND isset( $_GET['new_lang'] ) ) {
					return FALSE;
				}

				// Page for creating new header: creating it instantly and proceeding to editing
				$post_data = array( 'ID' => $post->ID );

				// Retrieve occupied names to generate new post title properly
				$existing_posts = us_get_posts_titles_for( $post->post_type );

				// Handle post duplication
				if ( isset( $_GET['duplicate_from'] ) AND $original_post = get_post( (int) $_GET['duplicate_from'] ) ) {
					$post_data['post_content'] = $original_post->post_content;

					// Add slashes for headers / grid layouts content
					if ( $post->post_type == 'us_header' OR $post->post_type == 'us_grid_layout' ) {
						$post_data['post_content'] = wp_slash( $post_data['post_content'] );
					}
					$title_pattern = $original_post->post_title . ' (%d)';
					$cur_index = 2;

					// Adds all post metadata
					$post_data['meta_input'] = array_map(
						function( $values ) { return $values[0] ?? ''; },
						get_post_meta( $original_post->ID )
					);

					// Handle creation from scratch
				} else {
					$post_obj = get_post_type_object( $post->post_type );
					$title_pattern = $post_obj->labels->singular_name . ' %d';
					$cur_index = count( $existing_posts ) + 1;
				}

				// Generate new post title
				while ( in_array( $post_data['post_title'] = sprintf( $title_pattern, $cur_index ), $existing_posts ) ) {
					$cur_index ++;
				}
				wp_update_post( $post_data );
				wp_publish_post( $post->ID );

				// Redirect
				if ( isset( $_GET['duplicate_from'] ) ) {

					// When duplicating post, showing posts list next
					wp_redirect( admin_url( 'edit.php?post_type=' . $post->post_type ) );
				} else {

					$extra_get = '';
					if ( ! empty( $_GET['from_post'] ) AND ! empty( $_GET['new_lang'] ) ) {
						$extra_get = "&from_post={$_GET['from_post']}&new_lang={$_GET['new_lang']}";
					}

					// When creating from scratch proceeding to post editing next
					wp_redirect( admin_url( 'post.php?post=' . $post->ID . '&action=edit' . $extra_get ) );
				}

			} elseif ( $post->post_type == 'us_header' ) {
				add_filter( 'admin_body_class', 'us_builder_admin_body_class' );
				add_action( 'admin_enqueue_scripts', 'us_hb_enqueue_scripts' );
				add_action( 'edit_form_top', 'us_hb_edit_form_top' );

			} elseif ( $post->post_type == 'us_grid_layout' ) {
				add_filter( 'admin_body_class', 'us_builder_admin_body_class' );
				add_action( 'admin_enqueue_scripts', 'usgb_enqueue_scripts' );
				add_action( 'edit_form_top', 'usgb_edit_form_top' );
			}
		}
	}

	// Add links to duplicate posts in admin list
	if ( ! function_exists( 'us_post_row_actions_duplicate' ) ) {
		add_filter( 'post_row_actions', 'us_post_row_actions_duplicate', 11, 2 );

		function us_post_row_actions_duplicate( $actions, $post ) {
			$duplicated_post_types = array(
				// 'us_portfolio',
				// 'us_testimonial',
				'us_header',
				'us_grid_layout',
				'us_content_template',
				'us_page_block',
			);
			if ( in_array( $post->post_type, $duplicated_post_types ) ) {

				// Removing duplicate post plugin affection
				if ( is_array( $actions ) ) {
					unset( $actions['duplicate'], $actions['edit_as_new_draft'] );
				}

				if ( empty( $actions ) ) {
					$actions = array();
				}

				$actions = us_array_merge_insert(
					$actions, array(
					'duplicate' => '<a href="' . admin_url( 'post-new.php?post_type=' . $post->post_type . '&duplicate_from=' . $post->ID ) . '" aria-label="' . esc_attr__( 'Duplicate', 'us' ) . '">' . esc_html__( 'Duplicate', 'us' ) . '</a>',
				), 'before', isset( $actions['trash'] ) ? 'trash' : 'untrash'
				);
			}

			return $actions;
		}
	}

	// Add "Used in" column into several admin page
	add_filter( 'manage_us_grid_layout_posts_columns', 'us_post_admin_columns_head' );
	add_action( 'manage_us_grid_layout_posts_custom_column', 'us_post_admin_columns_content', 10, 2 );
	add_filter( 'manage_us_header_posts_columns', 'us_post_admin_columns_head' );
	add_action( 'manage_us_header_posts_custom_column', 'us_post_admin_columns_content', 10, 2 );
	add_filter( 'manage_us_content_template_posts_columns', 'us_post_admin_columns_head' );
	add_action( 'manage_us_content_template_posts_custom_column', 'us_post_admin_columns_content', 10, 2 );
	add_filter( 'manage_us_page_block_posts_columns', 'us_post_admin_columns_head' );
	add_action( 'manage_us_page_block_posts_custom_column', 'us_post_admin_columns_content', 10, 2 );
	if ( ! function_exists( 'us_post_admin_columns_head' ) ) {
		function us_post_admin_columns_head( $defaults ) {
			$result = array();
			foreach ( $defaults as $key => $title ) {
				if ( $key == 'date' ) {
					$result['used_in'] = __( 'Used in', 'us' );
				}
				$result[ $key ] = $title;
			}

			return $result;
		}
	}
	if ( ! function_exists( 'us_post_admin_columns_content' ) ) {
		function us_post_admin_columns_content( $column_name, $post_ID ) {
			if ( $column_name == 'used_in' ) {
				global $wp_query;
				if ( count( (array) $wp_query->posts ) ) {
					// The function itself is able to cache data, it does not need to be taken care of after the call
					$used_in_locations = (array) us_get_all_used_in_locations( wp_list_pluck( $wp_query->posts, 'ID' ) );
					if ( ! empty( $used_in_locations[ $post_ID ] ) ) {
						echo $used_in_locations[ $post_ID ];
					}
				}
			}
		}
	}

	// Remove new lines on post insert - fix for headers import for PHP 7.3
	add_filter( 'wp_insert_post_data', 'us_header_wp_insert_post_data', 11, 2 );
	function us_header_wp_insert_post_data( $data, $postarr ) {
		if ( $data['post_type'] == 'us_header' ) {
			$data['post_content'] = str_replace( array( "\n", "\r" ), '', $data['post_content'] );
		}

		return $data;
	}

	global $us_iframe, $us_ajax_list_pagination;

	$us_iframe = ( ! empty( $_GET['us_iframe'] ) );
	$us_ajax_list_pagination = ( ! empty( $_POST['us_ajax_list_pagination'] ) );

	if ( $us_iframe OR $us_ajax_list_pagination ) {
		add_filter( 'show_admin_bar', '__return_false' );
		remove_action( 'wp_head', '_admin_bar_bump_cb' );
	}
	if ( $us_ajax_list_pagination ) {
		remove_all_actions( 'wp_head' );
		remove_all_actions( 'wp_footer' );

		$page_args = (array) us_arr_path( $_POST, 'page_args', array() );

		if ( us_get_page_area_id( 'content', $page_args ) ) {
			add_filter( 'us_content_template_the_content', 'us_ajax_output_list_pagination', PHP_INT_MAX );

			// Search page without Page Template
		} else if ( isset( $_GET['s'] ) ) {
			add_filter( 'the_content', 'us_ajax_output_list_pagination', PHP_INT_MAX );
		}

	}
}

// Portfolio labels
if ( ! function_exists( 'us_portfolio_labels' ) ) {
	add_filter( 'us_portfolio_labels', 'us_portfolio_labels' );

	function us_portfolio_labels( $labels ) {
		if ( us_get_option( 'portfolio_rename', 0 ) ) {
			$portofolio_keys = array( 'name', 'singular_name', 'add_new', 'edit_item' );
			foreach ( $portofolio_keys as $key ) {
				if ( us_get_option( 'portfolio_label_' . $key, '' ) != '' ) {
					$labels[ $key ] = wp_strip_all_tags( us_get_option( 'portfolio_label_' . $key ), TRUE );
					if ( $key == 'add_new' ) {
						$labels['add_new_item'] = $labels['add_new'];
					}
				}
			}
		}

		return $labels;
	}
}

// Portfolio Label Category
if ( ! function_exists( 'us_portfolio_category_label' ) ) {
	add_filter( 'us_portfolio_category_label', 'us_portfolio_category_label' );
	function us_portfolio_category_label( $label ) {
		if ( us_get_option( 'portfolio_rename', 0 ) AND us_get_option( 'portfolio_label_category', '' ) != '' ) {
			$label = wp_strip_all_tags( us_get_option( 'portfolio_label_category' ), TRUE );
		}

		return $label;
	}
}

// Portfolio Label Tags
if ( ! function_exists( 'us_portfolio_tags_label' ) ) {
	add_filter( 'us_portfolio_tags_label', 'us_portfolio_tags_label' );
	function us_portfolio_tags_label( $label ) {
		if ( us_get_option( 'portfolio_rename', 0 ) AND us_get_option( 'portfolio_label_tag', '' ) != '' ) {
			$label = wp_strip_all_tags( us_get_option( 'portfolio_label_tag' ), TRUE );
		}

		return $label;
	}
}

// Set Portfolio Pages slug
if ( us_get_option( 'enable_portfolio', 1 ) ) {
	if ( strpos( $portfolio_slug, '%us_portfolio_category%' ) !== FALSE ) {
		function us_portfolio_link( $post_link, $id = 0 ) {
			$post = get_post( $id );
			if ( is_object( $post ) ) {
				$terms = wp_get_object_terms( $post->ID, 'us_portfolio_category' );
				if ( $terms ) {
					return str_replace( '%us_portfolio_category%', $terms[0]->slug, $post_link );
				} else {
					// If no terms are assigned to this post, use a string instead (can't leave the placeholder there)
					return str_replace( '%us_portfolio_category%', 'uncategorized', $post_link );
				}
			}

			return $post_link;
		}

		add_filter( 'post_type_link', 'us_portfolio_link', 1, 3 );
	} elseif ( $portfolio_slug == '' ) {
		function us_portfolio_remove_slug( $post_link, $post, $leavename ) {
			if ( 'us_portfolio' != $post->post_type OR 'publish' != $post->post_status ) {
				return $post_link;
			}
			$post_link = str_replace( '/' . trailingslashit( $post->post_type ), '/', $post_link );

			return $post_link;
		}

		add_filter( 'post_type_link', 'us_portfolio_remove_slug', 10, 3 );

		function us_portfolio_parse_request( $query ) {
			if ( ! $query->is_main_query() OR 2 != count( $query->query ) OR ! isset( $query->query['page'] ) ) {
				return;
			}
			if ( ! empty( $query->query['name'] ) ) {
				$query->set( 'post_type', array( 'post', 'us_portfolio', 'page' ) );
			}
		}

		add_action( 'pre_get_posts', 'us_portfolio_parse_request' );
	}
}

if ( ! function_exists( 'us_search_query_adjustment' ) ) {

	add_action( 'pre_get_posts', 'us_search_query_adjustment' );

	/**
	 * Search query adjustment
	 *
	 * @param WP_Query $query The query
	 */
	function us_search_query_adjustment( $query ) {

		if ( ! $query->is_search OR ! $query->is_main_query() OR is_admin() ) {
			return;
		}

		global $wp_post_types;

		// Always exclude Testimonials, they are public, but don't have the own frontend template
		if ( us_get_option( 'enable_testimonials', 1 ) AND post_type_exists( 'us_testimonial' ) ) {
			$wp_post_types['us_testimonial']->exclude_from_search = TRUE;
		}
		
		// Retrieve post types to exclude
		$exclude_post_types = us_get_option( 'exclude_post_types_in_search', '' );

		// Abort early to avoid extra requests
		if ( empty( $exclude_post_types ) AND empty( $_GET['post_type'] ) ) {
			return;
		}

		// Retrieve already set post types to keep 3rd party plugins compatibility
		$post_type_arr = (array) $query->get( 'post_type' );
		$post_type_arr = array_filter( $post_type_arr ); // remove empty string

		// Post type 'page' is excluded by WordPress core, because it has 'publicly_queryable' => false
		// So we append it here, if it is provided via URL (by our Search element)
		if ( isset( $_GET['post_type'] ) AND in_array( 'page', (array) $_GET['post_type'] ) ) {
			$post_type_arr[] = 'page';

			// When post types are NOT specified explicitly, either in GET or WP_Query object, use public post types
		} elseif ( empty( $post_type_arr ) ) {
			$post_type_arr = array_keys( us_get_public_post_types() );
		}

		// Fallback for var type
		if ( is_array( $exclude_post_types ) ) {
			$exclude_post_types = implode( ',', $exclude_post_types );
		}

		// Exclude specified post types
		$post_type_arr = array_filter(
			$post_type_arr,
			fn( $elm ) => preg_match( "/(^|,)$elm($|,)/", $exclude_post_types ) === 0 
		);

		// If all types were excluded, then add a nonexistent one, to show no results instead of all post types
		if ( empty( $post_type_arr ) ) {
			$query->set( 'post_type', '_not_selected_post_type_' );
		} else {
			$query->set( 'post_type', array_unique( $post_type_arr ) );
		}
	}
}

// Add admin capabilities to Portfolio, Testimonials, Reusable Blocks, Page Templates
add_action( 'admin_init', 'us_add_theme_caps' );
function us_add_theme_caps() {
	global $wp_post_types;
	$role = get_role( 'administrator' );
	if ( empty( $role ) ) {
		return;
	}
	$force_refresh = FALSE;
	$custom_post_types = array( 'us_portfolio', 'us_testimonial', 'us_page_block', 'us_content_template' );
	foreach ( $custom_post_types as $post_type ) {
		if ( ! post_type_exists( $post_type ) ) {
			continue;
		}
		foreach ( $wp_post_types[ $post_type ]->cap as $cap ) {
			if ( ! $role->has_cap( $cap ) ) {
				$role->add_cap( $cap );
				$force_refresh = TRUE;
			}
		}
	}
	if ( $force_refresh AND current_user_can( 'manage_options' ) AND ! isset( $_COOKIE['us_cap_page_refreshed'] ) ) {
		// To prevent infinite refreshes when the DB is not writable
		setcookie( 'us_cap_page_refreshed' );
		header( 'Refresh: 0' );
	}
}

// Add role capabilities to Portfolio & Testimonials
add_action( 'admin_init', 'us_theme_activation_add_caps' );
function us_theme_activation_add_caps() {
	global $pagenow;
	if ( is_admin() AND $pagenow == 'themes.php' AND isset( $_GET['activated'] ) ) {
		if ( ! defined( 'US_THEMENAME' ) ) {
			return;
		}
		if ( get_option( US_THEMENAME . '_editor_caps_set' ) == 1 ) {
			return;
		}
		update_option( US_THEMENAME . '_editor_caps_set', 1 );
		global $wp_post_types;
		$role = get_role( 'editor' );
		if ( empty( $role ) ) {
			return;
		}
		$custom_post_types = array( 'us_portfolio', 'us_testimonial' );
		foreach ( $custom_post_types as $post_type ) {
			if ( ! post_type_exists( $post_type ) ) {
				continue;
			}
			foreach ( $wp_post_types[ $post_type ]->cap as $cap ) {
				if ( ! $role->has_cap( $cap ) ) {
					$role->add_cap( $cap );
				}
			}
		}
	}
}

// Remove not public post types from insert/edit link dialog
add_filter( 'wp_link_query_args', 'us_link_query_filter' );
function us_link_query_filter( $query ) {

	$not_public_post_types = get_post_types(
		array(
			'publicly_queryable' => FALSE,
			'_builtin' => FALSE,
		)
	);

	foreach ( $query['post_type'] as $key => $value ) {
		if ( in_array( $value, $not_public_post_types ) ) {
			unset( $query['post_type'][ $key ] );
		}
	}

	return $query;
}

// Add needed filters to Reusable Block and Page Template content
foreach ( array( 'page_block', 'content_template' ) as $page_type_name ) {
	add_filter( 'us_' . $page_type_name . '_the_content', 'wptexturize' );
	add_filter( 'us_' . $page_type_name . '_the_content', 'wpautop' );
	add_filter( 'us_' . $page_type_name . '_the_content', 'shortcode_unautop' );
	if ( ! function_exists( 'wp_filter_content_tags' ) ) {
		// Deprecated since WP 5.5
		add_filter( 'us_' . $page_type_name . '_the_content', 'wp_make_content_images_responsive' );
	} else {
		add_filter( 'us_' . $page_type_name . '_the_content', 'wp_filter_content_tags' );
	}
	add_filter( 'us_' . $page_type_name . '_the_content', 'do_shortcode', 12 );
	add_filter( 'us_' . $page_type_name . '_the_content', 'convert_smilies', 20 );
}

// Save Grid Layout and Reusable Block IDs for "Used in" interface
add_action( 'save_post', function( $post_id ) {

	$ids = array();
	$post = get_post( $post_id );
	$the_content = $post->post_content;

	// Grid Layouts
	if ( preg_match_all( '/\sitems_layout="(\d+)"/i', $the_content, $matches ) ) {
		$ids = array_merge( $ids, $matches[1] );
	}

	// Reusable Blocks showing when no results found
	if ( preg_match_all( '/no_items_page_block="(\d+)"/i', $the_content, $matches ) ) {
		$ids = array_merge( $ids, $matches[1] );
	}

	// Reusable Blocks as separate elements
	if ( preg_match_all( '/\[us_page_block[^\]]+id="(\d+)"/i', $the_content, $matches ) ) {
		$ids = array_merge( $ids, $matches[1] );
	}

	// Reusable Blocks used in Popups
	if (
		preg_match_all( '/use_page_block="(\d+)"/i', $the_content, $matches )
		OR preg_match_all( '/\"use_page_block\":\"(\d+)\"/i' , $the_content, $matches ) // header post type
	) {
		$ids = array_merge( $ids, $matches[1] );
	}

	// Reusable Blocks used in Contact Forms
	if ( preg_match_all( '/reusable_block="(\d+)"/i', $the_content, $matches ) ) {
		$ids = array_merge( $ids, $matches[1] );
	}

	if ( count( $ids ) > 0 ) {
		$ids = implode( ',', $ids );
	}

	// Save only non-empty value
	if ( ! empty( $ids ) ) {
		update_post_meta( $post_id, '_us_in_content_ids', $ids );
	} else {
		delete_post_meta( $post_id, '_us_in_content_ids' );
	}
} );

if ( ! function_exists( 'us_is_post_visible_for_curr_lang' ) ) {
	/**
	 * Should the post be visible for the current language?
	 *
	 * @param int $post_ID The post id
	 * @param mixed $page_block_ID The Reusable Block id
	 * @return bool
	 */
	function us_is_post_visible_for_curr_lang( $post_ID, $page_block_ID = NULL ) {
		$is_post_visible_for_curr_lang = TRUE;
		if ( has_filter( 'us_tr_get_post_language_code' ) ) {
			$post_language_code = apply_filters( 'us_tr_get_post_language_code', (int) $post_ID );
			$page_block_language_code = apply_filters( 'us_tr_get_post_language_code', $page_block_ID );
			if ( $page_block_language_code != $post_language_code ) {
				$is_post_visible_for_curr_lang = FALSE;
			}
		}
		return $is_post_visible_for_curr_lang;
	}
}

if ( ! function_exists( 'us_iterate_queries' ) ) {
	/**
	 * Iterate queries
	 *
	 * @param $query SQL query (no limit)
	 * @param function $callback The callback
	 * @param int $max_num_entries The maximum number entries
	 * @param int $query_limit This is the limit on the amount of data per iteration
	 */
	function us_iterate_queries( $query, $callback, $max_num_entries = 9999, $query_limit = 100 ) {
		if ( empty( $query ) ) {
			return;
		}

		$max_num_entries = (int) $max_num_entries;
		$query_limit = (int) $query_limit;
		$max_iterations = ceil( $max_num_entries / $query_limit );

		// Remove the limit from the query
		if ( ( $pos = strpos( us_strtolower( $query ), ' limit ' ) ) !== FALSE ) {
			$query = substr( $query, 0, $pos );
		}

		// Total records received
		$total_records = 0;
		$iterations = 0;

		global $wpdb;

		// Get data by iterations
		while ( $max_num_entries >= $total_records ) {
			// After exceeding the specified number of iterations, the loop will be stopped
			if ( $iterations >= $max_iterations ) {
				break;
			}
			$iterations++;

			// Set a limit in the query and receipt of data
			$current_query = $query . ' LIMIT ' . $total_records . ',' . $query_limit;
			$results = $wpdb->get_results( $current_query );
			$count_results = count( $results );

			if ( $count_results ) {
				$total_records += $count_results;
				if ( is_callable( $callback ) ) {
					call_user_func( $callback, $results );
				}
				if ( $count_results < $query_limit ) {
					break;
				}
			} else {
				break;
			}
		}
	}
}

if ( ! function_exists( 'us_get_used_in_locations' ) ) {
	/**
	 * Generate all locations names where used specific element
	 *
	 * @param array $post_ID
	 * @param bool $show_no_results
	 * @return array
	 */
	function us_get_all_used_in_locations( $post_IDs, $show_no_results = FALSE ) {
		if ( empty( $post_IDs ) OR ! is_array( $post_IDs ) ) {
			return array();
		}

		$ids = array_unique( array_map( 'intval', $post_IDs ) );
		static $results = array();

		$is_empty_result = FALSE;
		foreach ( $ids as $id ) {
			if ( ! isset( $results[ $id ] ) ) {
				$is_empty_result = TRUE;
				break;
			}
		}

		if ( $is_empty_result ) {
			global $usof_options, $wpdb;
			usof_load_options_once();

			$used_in = $posts_types = array();
			$areas = array(
				'header' => '',
				'titlebar' => ' > ' . __( 'Titlebar', 'us' ),
				'sidebar' => ' > ' . __( 'Sidebar', 'us' ),
				'content' => '',
				'footer' => ' > ' . __( 'Footer', 'us' ),
			);

			foreach ( $ids as $id ) {
				$used_in[ $id ] = array(
					'theme_options' => array(),
					'singulars_meta' => array(),
					'singulars_content' => array(),
					'nav_menu_item' => array(),
				);
				$results[ $id ] = '';
				$posts_types[ $id ] = get_post_type( $id );
			}

			// Theme Options > Pages Layout
			foreach ( us_get_public_post_types( /* exclude */'product' ) as $type => $title ) {
				// Fix suffixes regarding historical theme options names
				switch ( $type ) {
					case 'page':
						$type = '';
						break;
					case 'us_portfolio':
						$type = '_portfolio';
						break;
					default:
						$type = '_' . $type;
						break;
				}

				$link_atts = array(
					'href' => admin_url( 'admin.php?page=us-theme-options#pages_layout' ),
					'target' => '_blank',
				);
				$edit_link = ' (<a ' . us_implode_atts( $link_atts ) . '>' . __( 'edit in Theme Options', 'us' ) . '</a>)</div>';

				foreach ( $ids as $id ) {
					foreach ( $areas as $area => $area_name ) {
						if ( isset( $usof_options[ $area . $type . '_id' ] ) AND $usof_options[ $area . $type . '_id' ] == $id ) {
							$used_in[ $id ]['theme_options'][] = '<div><strong>' . $title . $area_name . '</strong>' . $edit_link;
						}
					}
				}
			}

			// Theme Options > Archives Layout
			$archives_layout_types = array_merge(
				array(
					'archive' => us_translate( 'Archives' ),
					'author' => __( 'Authors', 'us' ),
				),
				us_get_taxonomies( TRUE, FALSE, 'woocommerce_exclude' ),
				us_get_public_post_types( array( 'page', 'post', 'product' ), /* archive_only */TRUE )
			);

			foreach ( $archives_layout_types as $type => $title ) {
				if ( ! in_array( $type, array( 'archive', 'author' ) ) ) {
					$type = 'tax_' . $type;
				}

				$link_atts = array(
					'href' => admin_url( 'admin.php?page=us-theme-options#archives_layout' ),
					'target' => '_blank',
				);
				$edit_link = ' (<a' . us_implode_atts( $link_atts ) . '>' . __( 'edit in Theme Options', 'us' ) . '</a>)</div>';

				foreach ( $ids as $id ) {
					foreach ( $areas as $area => $area_name ) {
						if ( isset( $usof_options[ $area . '_' . $type . '_id' ] ) AND $usof_options[ $area . '_' . $type . '_id' ] == $id ) {
							$used_in[ $id ]['theme_options'][] = '<div><strong>' . $title . $area_name . '</strong>' . $edit_link;
						}
					}
				}
			}

			// Theme Options > Shop
			if ( class_exists( 'woocommerce' ) ) {
				$woocommerce_types = array_merge(
					array(
						'product' => us_translate( 'Products', 'woocommerce' ),
						'shop' => us_translate( 'Shop Page', 'woocommerce' ),
						'shop_search' => __( 'Products Search Results Page', 'us' ),
						'order' => us_translate( 'Orders', 'woocommerce' ),
					),
					us_get_taxonomies( TRUE, FALSE, 'woocommerce_only' )
				);

				$link_atts = array(
					'href' => admin_url( 'admin.php?page=us-theme-options#woocommerce' ),
					'target' => '_blank',
				);
				$edit_link = ' (<a' . us_implode_atts( $link_atts ) . '>' . __( 'edit in Theme Options', 'us' ) . '</a>)</div>';

				foreach ( $woocommerce_types as $type => $title ) {
					if ( ! in_array( $type, array( 'product', 'shop', 'shop_search', 'order' ) ) ) {
						$type = 'tax_' . $type;
					}

					foreach ( $ids as $id ) {
						foreach ( $areas as $area => $area_name ) {
							if ( isset( $usof_options[ $area . '_' . $type . '_id' ] ) AND $usof_options[ $area . '_' . $type . '_id' ] == $id ) {
								$used_in[ $id ]['theme_options'][] = '<div><strong>' . $title . $area_name . '</strong>' . $edit_link;
							}
						}
					}
				}
			}

			// Append locations to result string
			foreach ( $ids as $id ) {
				$results[ $id ] .= implode( $used_in[ $id ]['theme_options'] );
			}

			// Singulars (metabox)
			if ( ! empty( $areas ) ) {
				$usage_meta_keys = array_map(
					function ( $area ) {
						return sprintf( 'us_%s_id', $area );
					}, array_keys( $areas )
				);

				$query = "
					SELECT
						pm.post_id, pm.meta_key, pm.meta_value,
						p.post_title
					FROM {$wpdb->postmeta} AS pm
					LEFT JOIN {$wpdb->posts} AS p
						ON pm.post_id = p.ID
					WHERE
						pm.meta_value IN( '" . implode( "','", $ids ) . "' )
						AND pm.meta_key IN( '" . implode( "','", $usage_meta_keys ) . "' )";

				// Iterate queries
				us_iterate_queries(
					$query,
					function ( $items ) use ( &$used_in, $areas ) {
						foreach ( $items as $item ) {
							if (
								is_null( $item->post_title )
								OR ! us_is_post_visible_for_curr_lang( $item->post_id, $item->meta_value )
							) {
								continue;
							}

							// Get post title
							$post_title = empty( $item->post_title )
								? us_translate( '(no title)' )
								: $item->post_title;

							// Get post link atts
							$link_atts = array(
								'href' => get_permalink( $item->post_id ),
								'target' => '_blank',
								'title' => us_translate( 'View Page' ),
							);

							$used_in[ $item->meta_value ]['singulars_meta'][] = '<div><a' . us_implode_atts( $link_atts ) . '>' . $post_title . '</a>' . us_arr_path( $areas, $item->meta_key, '' ) . '</div>';
						}
					}
				);
			}

			// Append locations to result string
			foreach ( $ids as $id ) {
				$results[ $id ] .= implode( $used_in[ $id ]['singulars_meta'] );
			}

			// Singulars (content)
			$meta_value_like = '';
			foreach ( $ids as $id ) {
				if ( ! empty( $meta_value_like ) ) {
					$meta_value_like .= ' OR';
				}
				$meta_value_like .= " meta_value LIKE '%" . $id . "%'";
			}

			$query = "
				SELECT
					pm.post_id, pm.meta_value,
					p.post_title, p.post_type
				FROM {$wpdb->postmeta} AS pm
				LEFT JOIN {$wpdb->posts} AS p
					ON pm.post_id = p.ID
				WHERE
					meta_key = '_us_in_content_ids'
					AND ({$meta_value_like})
			";

			// Iterate queries
			us_iterate_queries(
				$query,
				function ( $items ) use ( &$used_in, $ids, &$posts_types ) {
					foreach ( $items as $item ) {
						if ( ! $post_id = $item->post_id ) {
							continue;
						}
						if ( ! isset( $posts_types[ $post_id ] ) ) {
							$posts_types[ $post_id ] = get_post_type( $post_id );
						}
						$meta_value_ids = explode( ',', $item->meta_value );
						foreach ( $ids as $id ) {
							if (
								in_array( $id, $meta_value_ids )
								AND us_is_post_visible_for_curr_lang( $post_id, $id )
							) {
								$used_in[ $id ]['singulars_content'][ $post_id ] = array(
									'url' => get_permalink( $post_id ),
									'edit_url' => us_get_edit_post_link( $post_id, $item->post_type ),
									'title' => empty( $item->post_title )
										? us_translate( '(no title)' )
										: $item->post_title,
									'post_type' => $posts_types[ $post_id ] ?? NULL,
								);
							}
						}
					}
				}
			);

			// Append locations to result string
			foreach ( $ids as $id ) {
				if ( ! empty( $used_in[ $id ] ) AND ! empty( $used_in[ $id ]['singulars_content'] ) ) {
					foreach ( $used_in[ $id ]['singulars_content'] as $location ) {
						switch ( $location['post_type'] ) {
							case 'us_header':
								$url = $location['edit_url'];
								$title = _x( 'Edit Header', 'site top area', 'us' );
								break;
							case 'us_content_template':
								$url = $location['edit_url'];
								$title = __( 'Edit Page Template', 'us' );
								break;
							case 'us_page_block':
								$url = $location['edit_url'];
								$title = __( 'Edit Reusable Block', 'us' );
								break;
							case 'us_grid_layout':
								$url = $location['edit_url'];
								$title = __( 'Edit Grid Layout', 'us' );
								break;
							default:
								$url = $location['url'];
								$title = us_translate( 'View Page' );
								break;
						}

						$link_atts = array(
							'href' => $url,
							'target' => '_blank',
							'title' => $title,
						);
						$results[ $id ] .= '<div><a' . us_implode_atts( $link_atts ) . '>' . $location['title'] . '</a></div>';
					}
				}
			}

			// Widgets (for Grid Layouts only)
			$regexp_layouts = array();
			foreach ( $ids as $id ) {
				$regexp_layouts[] = strlen( $id ) . ':"' . $id;
			}
			$regexp_layouts = implode( '|', $regexp_layouts );

			$query = "
				SELECT
					`option_name`, `option_value`
				FROM {$wpdb->options}
				WHERE
					option_name LIKE 'widget%'
					AND option_value REGEXP '\"layout\";s:({$regexp_layouts})\"'
				LIMIT 0, 100";

			if ( $widget_options = $wpdb->get_results( $query ) ) {
				global $wp_registered_sidebars, $wp_registered_widgets;

				$_widget_titles = $_sidebars_widgets = array();

				// Get widget_id => Sidebar name
				foreach ( wp_get_sidebars_widgets() as $sidebar_id => $widget_ids ) {
					if (
						$sidebar_id === 'wp_inactive_widgets'
						OR ! isset( $wp_registered_sidebars[ $sidebar_id ] )
					) {
						continue;
					}

					$_sidebars_widgets = array_merge(
						$_sidebars_widgets,
						array_fill_keys( array_values( $widget_ids ), $wp_registered_sidebars[ $sidebar_id ]['name'] )
					);
				}

				// Get widget name
				foreach ( $wp_registered_widgets as $base_id => $widget ) {
					foreach ( $widget['callback'] as $callback ) {
						if ( isset( $callback->option_name, $_sidebars_widgets[ $base_id ] ) ) {
							$number = substr( $base_id, mb_strlen( $callback->id_base . '-' ) );
							$_widget_titles[ $callback->option_name ][ $number ] = [
								'sidebar_name' => $_sidebars_widgets[ $base_id ],
								'name' => $callback->name,
							];
						}
					}
				}
				unset( $_sidebars_widgets );

				// Creating links for widgets
				foreach ( $widget_options as $usage_result ) {
					foreach ( $ids as $id ) {
						foreach ( unserialize( $usage_result->option_value ) as $number => $value ) {
							if ( ! is_array( $value ) OR ! isset( $value['layout'] ) OR $value['layout'] != $id ) {
								continue;
							}

							$_widget = isset( $_widget_titles[ $usage_result->option_name ][ $number ] )
								? $_widget_titles[ $usage_result->option_name ][ $number ]
								: [];

							$name = isset( $_widget['name'] )
								? $_widget['name']
								: '';

							if ( ! empty( $value['title'] ) ) {
								$name .= ': ' . $value['title'];
							}

							$sidebar_name = isset( $_widget['sidebar_name'] )
								? $_widget['sidebar_name'] . ' > '
								: '';

							// NOTE: The widget is in the config because it is not deleted, you can find it on
							// the widgets page in the "Inactive Sidebar (not used)" action, but we do not display this.
							if ( empty( $sidebar_name ) ) {
								continue;
							}

							$results[ $id ] .= '<div>' . esc_html( $sidebar_name );
							$results[ $id ] .= '<a href="' . admin_url( 'widgets.php' ) . '">' . esc_html( $name ) . '</a>';
							$results[ $id ] .= '</div>';
							unset( $_widget, $name, $sidebar_name );
						}
					}
				}
			}

			$group_posts_types = array();
			foreach ( $posts_types as $id => $post_type ) {
				$group_posts_types[ $post_type ][] = $id;
			}


			/**
			 * Layouts for archives or taxonomies
			 *
			 * Note: Despite the fact that here the foreach receipt of data
			 * occurs for one type of post (the current one)
			 */
			foreach( array( 'us_header', 'us_content_template', 'us_page_block' ) as $us_post_type ) {
				if ( ! empty( $group_posts_types[ $us_post_type ] ) ) {
					// Keys from the archives or taxonomy page
					$usage_meta_keys = array(
						// Header
						'us_header' => array(
							'archive_header_id' => __( 'Archives Layout', 'us' ),
							'pages_header_id' => __( 'Pages Layout', 'us' ),
						),
						// Page Templates
						'us_content_template' => array(
							'archive_content_id' => __( 'Archives Layout', 'us' ),
							'pages_content_id' => __( 'Pages Layout', 'us' ),
						),
						// Footer
						'us_page_block' => array(
							'archive_footer_id' => __( 'Archives Layout', 'us' ),
							'pages_footer_id' => __( 'Pages Layout', 'us' ),
						),
					);

					$query = "
						SELECT
							tm.term_id, t.name, tt.taxonomy, tm.meta_key, tm.meta_value
						FROM {$wpdb->termmeta} AS tm
						LEFT JOIN {$wpdb->terms} AS t
							ON tm.term_id = t.term_id
						LEFT JOIN {$wpdb->term_taxonomy} AS tt
							ON tm.term_id = tt.term_id
						WHERE
							tm.meta_value IN( '" . implode( "','", $group_posts_types[ $us_post_type ] ) . "' )
							AND tm.meta_key IN( '" . implode( "','", array_keys( $usage_meta_keys[ $us_post_type ] ) ) . "' )
					";

					// Iterate queries
					us_iterate_queries(
						$query,
						function ( $items ) use ( &$results, $group_posts_types, $usage_meta_keys, $us_post_type ) {
							foreach ( $items as $item ) {
								foreach ( $group_posts_types[ $us_post_type ] as $id ) {
									if (
										in_array( $id, explode( ',', $item->meta_value ) )
										AND $tax = get_taxonomy( $item->taxonomy )
									) {
										$result = '<div><strong>' . $tax->label . ' > ';
										$result .= $item->name . ' > ';
										$result .= us_translate( $usage_meta_keys[ $us_post_type ][ $item->meta_key ] ) . '</strong>';
										$result .= ' (<a href="term.php?taxonomy=' . esc_attr( $item->taxonomy );
										$result .= '&tag_ID=' . (int) $item->term_id;
										$result .= '&post_type=' . esc_attr( $tax->object_type[0] );
										$result .= '" target="_blank">' . us_translate( 'Edit' ) . '</a>)</div>';
										if ( isset( $results[ $id ] ) ) {
											$results[ $id ] .= $result;
										} else {
											$results[ $id ] = $result;
										}
									}
								}
							}
						}
					);
				}
			}

			// Menus (nav_menu_item) for Reusable Blocks only
			if ( ! empty( $group_posts_types['us_page_block'] ) ) {
				$meta_value_like = '';
				foreach ( $group_posts_types['us_page_block'] as $id ) {
					if ( ! empty( $meta_value_like ) ) {
						$meta_value_like .= ' OR';
					}
					$meta_value_like .= " meta1.meta_value LIKE '%" . $id . "%'";
				}

				$query = "
					SELECT
						meta1.meta_value, p.ID as post_id
					FROM {$wpdb->postmeta} meta1
					LEFT JOIN {$wpdb->postmeta} meta2
						ON (
							meta1.post_id = meta2.post_id
							AND meta2.meta_key = '_menu_item_object'
							AND meta2.meta_value = 'us_page_block'
						)
					LEFT JOIN {$wpdb->posts} AS p
						ON meta1.post_id = p.ID
					WHERE
						meta1.meta_key = '_menu_item_object_id'
						AND ({$meta_value_like})
				";

				// Iterate queries
				us_iterate_queries(
					$query,
					function ( $items ) use ( &$used_in, $ids ) {
						foreach ( $items as $item ) {
							if ( ! $post_id = $item->post_id ) {
								continue;
							}
							$meta_value_ids = explode( ',', $item->meta_value );
							foreach ( $ids as $id ) {
								if ( ! in_array( $id, $meta_value_ids ) ) {
									continue;
								}
								$used_in[ $id ]['nav_menu_item'][ $post_id ] = wp_get_post_terms(
									$post_id,
									'nav_menu',
									array( 'fields' => 'all' )
								);
							}
						}
					}
				);
			}

			// Append locations to result string
			foreach ( $ids as $id ) {
				if ( ! empty( $used_in[ $id ] ) AND ! empty( $used_in[ $id ]['nav_menu_item'] ) ) {
					foreach ( $used_in[ $id ]['nav_menu_item'] as $location ) {
						if ( ! empty( $location ) ) {
							$link_atts = array(
								'href' => admin_url( 'nav-menus.php?action=edit&menu=' . $location[0]->term_id ),
								'target' => '_blank',
								'title' => us_translate( 'Edit Menu' ),
							);
							$results[ $id ] .= '<div><strong>' . us_translate( 'Menus' ) . '</strong> > <a' . us_implode_atts( $link_atts ) . '>' . $location[0]->name . '</a></div>';
						}
					}
				}
			}

			// Return "No results" message if set
			foreach ( $results as &$result ) {
				if ( empty( $result ) AND $show_no_results ) {
					$result = us_translate( 'No results found.' );
				}
			}
		}

		return $results;
	}

	/**
	 * Generate locations names where used specific element
	 *
	 * @param int $post_ID
	 * @param bool $show_no_results
	 * @return string
	 */
	function us_get_used_in_locations( $post_ID, $show_no_results = FALSE ) {
		$results = (array) us_get_all_used_in_locations( array( $post_ID ), $show_no_results );

		return ! empty( $results[ $post_ID ] )
			? $results[ $post_ID ]
			: '';
	}
}
