=== Post Type Spotlight ===
Contributors: linchpin_agency, desrosj
Tags: featured, post type, sticky, posts, custom post types
Requires at least: 3.1
Tested up to: 3.5.2
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily allows you to designate posts, pages, attachments and custom post types as featured.

== Description ==

The plugin displays a checkbox in the publish meta box to feature a post. The checkbox only appears on admin selected post types which can be selected in the Settings->Writing screen.

When a post is designated as featured:

*   It receives 'featured' and 'featured-$posttype' classes via the post_class filter.
*   Shows featured posts as such in the post type's admin screen
*   Stores a post meta field, '_pts_featured_post', which can be used to query featured posts.

*Note: For the plugin to work on the core attachment post type, you must be using 3.5 or above. All other features will work on 3.1 and up.*

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to the Settings->Writing section and select the post types you would like to have the featured abilities.

== Frequently Asked Questions ==

= Isn't this the same as sticky posts? =

This is not the same as sticky posts. Sticky functionality can only be applied to the core 'post' post type. [More information on why](http://core.trac.wordpress.org/ticket/12702#comment:28 "Custom Post Types and Sticky Posts")

= How do I find just my featured posts? =

This snippet of code will fetch the 10 most recent posts that are featured.
`<?php
	$featured_posts = new WP_Query( array(
		'post_type' => 'post',
		'posts_per_page' => 10,
		'meta_query' => array(
			array(
				'key' => '_pts_featured_post'
			)
		)
	) );

	if ( $featured_posts->have_posts() ) : while ( $featured_posts->have_posts() ) : $featured_posts->the_post();

		//output featured posts here

	endwhile; endif;
?>`

== Screenshots ==

1. The settings page.
2. Options on the edit screen
3. Markup example when using post_class();
4. Something you can do with a featured post.
5. Shows featured posts in post edit tables.

== Changelog ==

= 1.0.1 =
* The plugin should only allow public post types to be checked off on the settings page.

= 1.0 =
* Hello world!
* Add settings to the Settings->Writing page allowing admins to select the post types that can be featured.
* Add a check box in the publish meta box for marking a post as featured.
* Featured posts receive a featured and featured-$posttype class on them via the post_class filter.
* Admin post type screens have a column for Featured noting which posts are in fact featured.