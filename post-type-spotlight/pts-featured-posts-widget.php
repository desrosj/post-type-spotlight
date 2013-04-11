<?php

/**
 * PTS_Featured_Posts_Widget class.
 *
 * @extends WP_Widget
 */
class PTS_Featured_Posts_Widget extends WP_Widget {

	private $order_options = array( 'DESC', 'ASC' );
	private $order_by_options = array( 'date', 'title', 'rand' );
	private $featured_post_types = array();

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->featured_post_types = (array) get_option( 'pts_featured_post_types_settings' );

		if ( empty( $this->featured_post_types ) )
			return;

		$this->WP_Widget( 'pts_featured_posts_widget', 'Featured Posts Widget', array( 'description' => 'Featured Posts Widget' ) );
	}

	/**
	 * widget function.
	 *
	 * @access public
	 * @param mixed $args
	 * @param mixed $instance
	 * @return void
	 */
	public function widget( $args, $instance ) {
		extract( $args );

		$widget_settings = wp_parse_args( $instance,
			array(
				'number' => 5,
				'title' => '',
				'post_type' => '',
				'orderby' => 'date',
				'order' => 'DESC',
				'content_or_excerpt' => 'excerpt',
				'tax_items_exclude' => array(),
				'tax_items_include' => array(),
			)
		);

		if ( empty( $widget_settings['post_type'] ) )
			return;

		$title = apply_filters( 'widget_title', $widget_settings['title'] );

		$show_excerpt = ( $widget_settings['content_or_excerpt'] == 'excerpt' ) ? true : false;

		echo $before_widget;

		if ( ! empty( $title ) )
			echo $before_title . $title . $after_title;

		$args = array(
			'posts_per_page' => (int) $widget_settings['number'],
			'post_type' => 'post',
			'meta_query' => array(
				array(
					'key' => '_pts_featured_post',
				)
			),
			'order' => ( in_array( $widget_settings['order'], $this->order_options ) ) ? $widget_settings['order'] : 'DESC',
			'orderby' => ( in_array( $widget_settings['orderby'], $this->order_by_options ) ) ? $widget_settings['orderby'] : 'date',
			'tax_query' => array(),
		);

		//Figure out what taxonomies and terms to include
		if ( ! empty( $widget_settings['tax_items_include'] ) ) {

			foreach ( $widget_settings['tax_items_include'] as $tax => $terms ) {
				//if this is not a tax we are not going to query by it
				if ( ! get_taxonomy( $tax ) )
					continue;

				//Verify each term
				foreach ( $terms as $key => $term ) {
					if ( ! get_term( (int) $term, $tax ) )
						unset( $terms[ $key ] );
				}

				$args['tax_query'][] = array(
					'taxonomy' => $tax,
					'field' => 'id',
					'terms' => $terms,
					'operator' => 'IN',
				);
			}
		}

		//Figure out what taxonomies and terms to exclude
		if ( ! empty( $widget_settings['tax_items_exclude'] ) ) {

			foreach ( $widget_settings['tax_items_exclude'] as $tax => $terms ) {
				//if this is not a tax we are not going to query by it
				if ( ! get_taxonomy( $tax ) )
					continue;

				//Verify each term
				foreach ( $terms as $key => $term ) {
					if ( ! get_term( (int) $term, $tax ) )
						unset( $terms[ $key ] );
				}

				$args['tax_query'][] = array(
					'taxonomy' => $tax,
					'field' => 'id',
					'terms' => $terms,
					'operator' => 'NOT IN',
				);
			}
		}

		$featured_posts = new WP_Query( $args );

		if ( $featured_posts->have_posts() ) : ?>
			<div class="pts-widget-post-container">
				<?php while ( $featured_posts->have_posts() ) : $featured_posts->the_post(); ?>
					<div <?php post_class( 'pts-featured-post' ); ?>>
						<h3 title="<?php the_title_attribute(); ?>"><a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h3>
						<div class="post-content">
						<?php
							if ( $show_excerpt )
								the_excerpt();
							else
								the_content();
						?>
						</div><!-- close .post-content -->
					</div><!-- close .pts-featured-post -->
				<?php endwhile; ?>
			</div><!-- close .pts-widget-post-container -->
		<?php endif;

		echo $after_widget;
	}

	/**
	 * update function.
	 *
	 * @access public
	 * @param mixed $new_instance
	 * @param mixed $old_instance
	 * @return void
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['number'] = (int) $new_instance['number'];

		if ( in_array( $new_instance['orderby'], $this->order_by_options ) )
			$instance['orderby'] = $new_instance['orderby'];
		else
			$instance['orderby'] = 'date';

		if ( in_array( $new_instance['order'], $this->order_options ) )
			$instance['order'] = $new_instance['order'];
		else
			$instance['order'] = 'DESC';

		if ( in_array( $new_instance['post_type'], $this->featured_post_types ) )
			$instance['post_type'] = $new_instance['post_type'];
		else
			$instance['post_type'] = 'post';

		if ( isset( $new_instance['tax_items_include'] ) ) {
			foreach ( $new_instance['tax_items_include'] as $tax => $term_ids )
				$instance['tax_items_include'][ $tax ] = array_map( 'intval', $new_instance['tax_items_include'][ $tax ] );
		} else {
			$instance['tax_items_include'] = array();
		}

		if ( isset( $new_instance['tax_items_exclude'] ) ) {
			foreach ( $new_instance['tax_items_exclude'] as $tax => $term_ids )
				$instance['tax_items_exclude'][ $tax ] = array_map( 'intval', $new_instance['tax_items_exclude'][ $tax ] );
		} else {
			$instance['tax_items_exclude'] = array();
		}

		if ( in_array( $new_instance['content_or_excerpt'], array( 'content', 'excerpt' ) ) )
			$instance['content_or_excerpt'] = $new_instance['content_or_excerpt'];
		else
			$instance['content_or_excerpt'] = 'excerpt';

		return $instance;
	}

	/**
	 * form function.
	 *
	 * @access public
	 * @param mixed $instance
	 * @return void
	 */
	public function form( $instance ) {
		extract( wp_parse_args( $instance,
			array(
				'number' => 5,
				'title' => '',
				'post_type' => '',
				'orderby' => 'date',
				'order' => 'DESC',
				'tax_items_exclude' => array(),
				'tax_items_include' => array(),
				'content_or_excerpt' => 'excerpt',
			)
		) );

		if ( empty( $this->featured_post_types ) ) { ?>
			<p>You need to select a featured post type on the <a href="<?php echo admin_url( 'options-writing.php' ); ?>">Settings->Writing screen</a> before you can use this widget.</p><?php

		} else { ?>

			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'post_type' ); ?>">Post type to feature:</label>
				<select id="<?php echo $this->get_field_id( 'post_type' ); ?>" name="<?php echo $this->get_field_name( 'post_type' ); ?>" class="widefat">
					<option value="">Select post type...</option>
					<?php
						foreach ( $this->featured_post_types as $pt ) {
							if ( $current_post_type = get_post_type_object( $pt ) ) : ?>
								<option value="<?php echo esc_attr( $pt ); ?>" <?php selected( $post_type, $pt ); ?>><?php echo $current_post_type->labels->name; ?></option>
							<?php endif;
						}
					?>
				</select>
			</p>

			<div class="featured-post-type-taxonomies">
			<?php
				if ( $taxonomies = get_object_taxonomies( $post_type ) ) {
					foreach ( $taxonomies as $tax ) { ?>
						<p>
						<?php
							if ( $current_tax = get_taxonomy( $tax ) ) { ?>
								<label for="<?php echo $this->get_field_id( 'tax_items_include' ); ?>">Include <?php echo $current_tax->labels->name; ?>:</label><br /><?php

								if ( $terms = get_terms( $tax, array( 'hide_empty' => false ) ) ) {

									foreach ( $terms as $term ) { ?>
										<input id="<?php echo $this->get_field_id( 'tax_items_include' ); ?>[<?php echo $tax; ?>][]" name="<?php echo $this->get_field_name( 'tax_items_include' ); ?>[<?php echo $tax; ?>][]" type="checkbox" value="<?php echo $term->term_id; ?>" <?php checked( in_array( $term->term_id, ( isset( $tax_items_include[ $tax ] ) && is_array( $tax_items_include[ $tax ] ) ) ? $tax_items_include[ $tax ] : array() ) ); ?> /> <?php echo $term->name; ?><br /><?php
									}
								}
							}
						?>
						</p>

						<p>
						<?php
							if ( $current_tax = get_taxonomy( $tax ) ) { ?>
								<label for="<?php echo $this->get_field_id( 'tax_items_exclude' ); ?>">Exclude <?php echo $current_tax->labels->name; ?>:</label><br /><?php

								if ( $terms = get_terms( $tax, array( 'hide_empty' => false ) ) ) {

									foreach ( $terms as $term ) { ?>
										<input id="<?php echo $this->get_field_id( 'tax_items_exclude' ); ?>[<?php echo $tax; ?>][]" name="<?php echo $this->get_field_name( 'tax_items_exclude' ); ?>[<?php echo $tax; ?>][]" type="checkbox" value="<?php echo $term->term_id; ?>" <?php checked( in_array( $term->term_id, ( isset( $tax_items_exclude[ $tax ] ) && is_array( $tax_items_exclude[ $tax ] ) ) ? $tax_items_exclude[ $tax ] : array() ) ); ?> /> <?php echo $term->name; ?><br /><?php
									}
								}
							}
						?>
						</p>
						<?php
					}
				}
			?>
			</div>

			<p>
				<label for="<?php echo $this->get_field_id( 'number' ); ?>">Number of Posts:</label>
				<input size="2" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo esc_attr( $number ); ?>" />
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'content_or_excerpt' ); ?>">Show content or excerpt:</label>
				<select id="<?php echo $this->get_field_id( 'content_or_excerpt' ); ?>" name="<?php echo $this->get_field_name( 'content_or_excerpt' ); ?>" class="widefat">
					<option value="excerpt" <?php selected( 'excerpt', $content_or_excerpt ); ?>>Excerpt</option>
					<option value="content" <?php selected( 'content', $content_or_excerpt ); ?>>Content</option>
				</select>
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'orderby' ); ?>">Order By:</label>
				<select id="<?php echo $this->get_field_id( 'orderby' ); ?>" name="<?php echo $this->get_field_name( 'orderby' ); ?>" class="widefat">
					<?php
						foreach ( $this->order_by_options as $option ) { ?>
							<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $option, $orderby ); ?>><?php echo sanitize_text_field( $option ); ?></option>
							<?php
						}
					?>
				</select>
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'order' ); ?>">Order:</label>
				<select id="<?php echo $this->get_field_id( 'order' ); ?>" name="<?php echo $this->get_field_name( 'order' ); ?>" class="widefat">

					<?php foreach ( $this->order_options as $option ) : ?>
						<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $option, $order ); ?>><?php echo sanitize_text_field( $option ); ?></option>
					<?php endforeach; ?>

				</select>
			</p><?php

		}
	}
}