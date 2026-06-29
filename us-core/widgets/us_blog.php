<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * UpSolution Widget: Blog
 *
 * Class US_Widget_Blog
 */

class US_Widget_Blog extends US_Widget {

	/**
	 * Output the widget
	 *
	 * @param array $args Display arguments including before_title, after_title, before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget.
	 * @return NULL
	 */
	function widget( $args, $instance ) {

		// If we are running loop already, return nothing
		if ( us_in_the_loop() ) {
			return NULL;
		}

		parent::before_widget( $args, $instance );

		$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );

		$output = $args['before_widget'];

		$shortcode_atts = array();

		if ( $title ) {
			$output .= '<h3 class="widgettitle">' . $title . '</h3>';
		}

		// Posts of the specific categories
		if ( ! empty( $instance['categories'] ) ) {
			// Retrieving term IDs from term slugs, because Post List can only use IDs 
			$terms = get_terms(
				array(
					'fields'   => 'ids',
					'taxonomy' => 'category',
					'slug' => $instance['categories'],
					'hide_empty' => TRUE,
				)
			);
			$shortcode_atts['tax_query'] = array(
				array(
					'operator' => 'IN',
					'taxonomy' => 'category',
					'terms' => implode( ',', $terms ),
					'include_children' => '0',
				)
			);

			$shortcode_atts['tax_query_relation'] = 'AND';
			$shortcode_atts['tax_query'] = urlencode( json_encode( $shortcode_atts['tax_query'] ) );
		}

		// Setting posts order
		$_orderby = array(
			'date' => 'date',
			'date_asc' => 'date',
			'modified' => 'modified',
			'modified_asc' => 'modified',
			'alpha' => 'title',
			'rand' => 'rand',
		);
		$_order_invert = array(
			'date' => 1,
			'date_asc' => 0,
			'modified' => 1,
			'modified_asc' => 0,
			'alpha' => 0,
			'rand' => 0,
		);

		$shortcode_atts = array_merge( $shortcode_atts, array(
			'ignore_sticky_posts' => ! empty( $instance['ignore_sticky'] ),
			'orderby' => $_orderby[ $instance['orderby'] ] ?? 'date',
			'order_invert' => $_order_invert[ $instance['orderby'] ] ?? 0,
			'columns' => 1, // fixed number of columns
			'quantity' => (int) $instance['items'],
			'items_gap' => '1rem', // fixed value for Blog widget
			'items_layout' => $instance['layout'],
			'ignore_items_size' => 1,
			'overriding_link' => '%7B%22type%22%3A%22post%22%7D',
			'is_widget' => TRUE,
		) );

		global $us_shortcodes;
		$output .= $us_shortcodes->us_post_list( $shortcode_atts );

		$output .= $args['after_widget'];

		echo $output;
	}

	/**
	 * Output the settings update form.
	 *
	 * @param array $instance Current settings.
	 *
	 * @return string Form's output marker that could be used for further hooks
	 */
	public function form( $instance ) {
		$us_post_categories = array();
		$us_post_categories_raw = get_categories( "hierarchical=0" );
		foreach ( $us_post_categories_raw as $post_category_raw ) {
			$us_post_categories[$post_category_raw->name] = $post_category_raw->slug;
		}

		if ( ! empty( $us_post_categories ) ) {
			$this->config['params']['categories'] = array(
				'type' => 'checkbox',
				'heading' => __( 'Display Items of selected categories', 'us' ),
				'value' => $us_post_categories,
			);
		}

		return parent::form( $instance );
	}

}
