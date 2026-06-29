<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * UpSolution Widget: Portfolio
 *
 * Class US_Widget_Portfolio
 */

class US_Widget_Portfolio extends US_Widget {

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

		$shortcode_atts = array();

		// Portfolio posts of the specific Portfolio Category
		if ( ! empty( $instance['categories'] ) ) {
			// Retrieving term IDs from term slugs, because Post List can only use IDs 
			$terms = get_terms(
				array(
					'fields'   => 'ids',
					'taxonomy' => 'us_portfolio_category',
					'slug' => $instance['categories'],
					'hide_empty' => TRUE,
				)
			);
			$shortcode_atts['tax_query'] = array(
				array(
					'operator' => 'IN',
					'taxonomy' => 'us_portfolio_category',
					'terms' => implode( ',', $terms ),
					'include_children' => '0',
				)
			);

			$shortcode_atts['tax_query_relation'] = 'AND';
			$shortcode_atts['tax_query'] = urlencode( json_encode( $shortcode_atts['tax_query'] ) );
		}

		$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );

		$output = $args['before_widget'];

		if ( $title ) {
			$output .= '<h3 class="widgettitle">' . $title . '</h3>';
		}

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

		$shortcode_atts = array_merge(
			$shortcode_atts,
			array(
			'post_type' => 'us_portfolio',
			'orderby' => $_orderby[ $instance['orderby'] ] ?? 'date',
			'order_invert' => $_order_invert[ $instance['orderby'] ] ?? 0,
			'quantity' => (int) $instance['items'],
			'items_layout' => $instance['layout'],
			'columns' => (int) $instance['columns'],
			'items_gap' => '1px', // fixed value for Portfolio widget
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
		$us_portfolio_categories = array();
		$us_portfolio_categories_raw = get_categories(
			array(
				'taxonomy' => 'us_portfolio_category',
				'hierarchical' => 0,
			)
		);
		if ( $us_portfolio_categories_raw ) {
			foreach ( $us_portfolio_categories_raw as $portfolio_category_raw ) {
				if ( is_object( $portfolio_category_raw ) ) {
					$us_portfolio_categories[$portfolio_category_raw->name] = $portfolio_category_raw->slug;
				}
			}
		}

		if ( ! empty( $us_portfolio_categories ) ) {
			$this->config['params']['categories'] = array(
				'type' => 'checkbox',
				'heading' => __( 'Display Items of selected categories', 'us' ),
				'value' => $us_portfolio_categories,
			);
		}

		return parent::form( $instance );
	}

}
