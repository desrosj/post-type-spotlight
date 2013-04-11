<?php
/*
Plugin Name: Post Type Spotlight
Plugin URI: http://wordpress.org/extend/plugins/
Description: Allows admin chosen post types to have a featured post check box on the edit screen. Also adds appropriate classes to front end post display, and allows featured posts to be queried via a post meta field.
Version: 1.0.1
Author: Linchpin
Author URI: http://linchpinagency.com
License: GPLv2
*/

if ( ! class_exists( 'Post_Type_Spotlight' ) ) {

	/**
	 * Post_Type_Spotlight class.
	 */
	class Post_Type_Spotlight {

		/**
		 * __construct function.
		 *
		 * @access public
		 * @return void
		 */
		function __construct() {
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
			add_action( 'save_post', array( $this, 'save_post' ) );
			add_action( 'edit_attachment', array( $this, 'save_post' ) );
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );

			add_filter( 'post_class', array( $this, 'post_class' ), 10, 3 );
		}

		/**
		 * admin_init function.
		 *
		 * @access public
		 * @return void
		 */
		function admin_init() {
			register_setting( 'pts_featured_post_types_settings', 'pts_featured_post_types_settings', array( $this, 'sanitize_settings' ) );

			//add a section for the plugin's settings on the writing page
			add_settings_section( 'pts_featured_posts_settings_section', 'Featured Post Types', array( $this, 'settings_section_text' ), 'writing' );

			//For each post type add a settings field, excluding revisions and nav menu items
			if ( $post_types = get_post_types() ) {
				foreach ( $post_types as $post_type ) {
					$pt = get_post_type_object( $post_type );

					if ( in_array( $post_type, array( 'revision', 'nav_menu_item' ) ) || ! $pt->public )
						continue;

					add_settings_field( 'pts_featured_post_types' . $post_type, $pt->labels->name, array( $this,'featured_post_types_field' ), 'writing', 'pts_featured_posts_settings_section', array( 'slug' => $pt->name, 'name' => $pt->labels->name ) );
				}
			}

			if ( $featured_pts = get_option( 'pts_featured_post_types_settings' ) ) {
				foreach ( $featured_pts as $pt ) {
					add_action( 'manage_' . $pt . '_posts_columns', array( $this, 'manage_posts_columns' ), 10 );
					add_action( 'manage_' . $pt . '_posts_custom_column' , array( $this, 'manage_posts_custom_column' ), 10, 2 );
					add_filter( 'views_edit-' . $pt, array( $this, 'views_addition' ) );
				}
			}


		}

		/**
		 * settings_section_text function.
		 *
		 * @access public
		 * @return void
		 */
		function settings_section_text() {
			echo "<p>Select which post types can be featured.</p>";
			settings_fields( 'pts_featured_post_types_settings' );
		}

		/**
		 * featured_post_types_field function.
		 *
		 * @access public
		 * @param mixed $args
		 * @return void
		 */
		function featured_post_types_field( $args ) {
			$settings = get_option( 'pts_featured_post_types_settings', array() );

			if ( $post_types = get_post_types() ) { ?>
				<input type="checkbox" name="pts_featured_post_types[]" id="pts_featured_post_types_<?php echo $args['slug']; ?>" value="<?php echo $args['slug']; ?>" <?php in_array( $args['slug'], $settings ) ? checked( true ) : checked( false ); ?>/>
				<?php
			}
		}

		/**
		 * sanitize_settings function.
		 *
		 * @access public
		 * @param mixed $input
		 * @return void
		 */
		function sanitize_settings( $input ) {
			$input = wp_parse_args( $_POST['pts_featured_post_types'], array() );

			$new_input = array();

			foreach ( $input as $pt ) {
				if ( post_type_exists( sanitize_text_field( $pt ) ) )
					$new_input[] = sanitize_text_field( $pt );
			}

			return $new_input;
		}

		/**
		 * add_meta_boxes function.
		 *
		 * @access public
		 * @param mixed $post_type
		 * @return void
		 */
		function add_meta_boxes( $post_type ) {
			$settings = get_option( 'pts_featured_post_types_settings', array() );

			if ( empty( $settings ) )
				return;

			if ( in_array( $post_type, $settings ) ) {

				if ( $post_type == 'attachment' )
					add_action( 'attachment_submitbox_misc_actions', array( $this, 'post_submitbox_misc_actions' ) );
				else
					add_action( 'post_submitbox_misc_actions', array( $this, 'post_submitbox_misc_actions' ) );
			}
		}

		/**
		 * post_submitbox_misc_actions function.
		 *
		 * @access public
		 * @return void
		 */
		function post_submitbox_misc_actions() {
			global $post;
			$pt = get_post_type_object( $post->post_type );

			wp_nonce_field( '_pts_featured_post_nonce', '_pts_featured_post_noncename' );
			?>
			<div class="misc-pub-section lp-featured-post">
				<span>Feature this <?php echo $pt->labels->singular_name; ?>:</span> <input type="checkbox" name="_pts_featured_post" id="_pts_featured_post" <?php checked( get_post_meta( $post->ID, '_pts_featured_post', true ) ); ?> />
			</div>
			<?php
		}

		function manage_posts_columns( $columns ) {

			unset( $columns['date'] );

			return array_merge( $columns, array(
				'lp-featured' => __( 'Featured' ),
				'date' => __( 'Date' ),
			) );
		}

		/**
		 * manage_posts_custom_column function.
		 *
		 * @access public
		 * @param mixed $column
		 * @param mixed $post_id
		 * @return void
		 */
		function manage_posts_custom_column( $column, $post_id ) {
			switch ( $column ) {
				case 'lp-featured':
					if ( get_post_meta( $post_id, '_pts_featured_post', true ) )
						echo '<span style="font-size:24px;">&#10030;</span>';
					break;
			}
		}

		/**
		 * pre_get_posts function.
		 *
		 * @access public
		 * @return void
		 */
		function pre_get_posts() {

			if ( ! is_admin() )
				return;

			global $pagenow;

			if ( $pagenow != 'edit.php' || ! isset( $_GET['meta_key'] ) )
				return;

			global $wp_query;
			$wp_query->query_vars['meta_key'] = sanitize_text_field( $_GET['meta_key'] );
		}

		/**
		 * views_addition function.
		 *
		 * @access public
		 * @param mixed $views
		 * @return void
		 */
		function views_addition( $views ) {
			if ( $featured = get_posts( array( 'post_type' => get_post_type(), 'meta_key' => '_pts_featured_post' ) ) )
				$count = count( $featured );
			else
				$count = 0;

			$link = '<a href="edit.php?post_type=' . get_post_type() . '&meta_key=_pts_featured_post"';

			if ( isset( $_GET['meta_key'] ) && $_GET['meta_key'] == '_pts_featured_post' )
				$link .= ' class="current"';

			$link .= '>Featured</a> <span class="count">(' . $count . ')</span>';

			return array_merge( $views, array( 'featured' => $link ) );
		}

		/**
		 * save_post function.
		 *
		 * @access public
		 * @param mixed $post_id
		 * @return void
		 */
		function save_post( $post_id ) {
			//Skip revisions and autosaves
			if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) )
				return;

			//Users should have the ability to edit listings.
			if ( ! current_user_can( 'edit_post', $post_id ) )
				return;

			if ( isset( $_POST['_pts_featured_post_noncename'] ) && wp_verify_nonce( $_POST['_pts_featured_post_noncename'], '_pts_featured_post_nonce' ) ) {

				if ( isset( $_POST['_pts_featured_post'] ) && ! empty( $_POST['_pts_featured_post'] ) )
					update_post_meta( $post_id, '_pts_featured_post', 1 );
				else
					delete_post_meta( $post_id, '_pts_featured_post' );
			}
		}

		/**
		 * post_class function.
		 *
		 * @access public
		 * @param mixed $classes
		 * @param mixed $class
		 * @param mixed $post_id
		 * @return void
		 */
		function post_class( $classes, $class, $post_id ) {
			if ( get_post_meta( $post_id, '_pts_featured_post', true ) ) {
				$classes[] = 'featured';
				$classes[] = 'featured-' . get_post_type();
			}

			return $classes;
		}
	}
}

$pts_featured_posts = new Post_Type_Spotlight();