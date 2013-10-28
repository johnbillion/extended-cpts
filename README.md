# Extended CPTs #

Extended CPTs is library which provides extended functionality to WordPress custom post types, allowing developers to quickly build post types without having to write the same code again and again.

## Improved defaults ##

 * Automatically generated labels and post updated messages
 * Public post type with admin UI enabled
 * Hierarchical with `page` capability type
 * Support post thumbnails
 * Optimal admin menu placement
 * Remove `with_front` from rewrite rules

## Extended admin features ##

 * Ridiculously easy custom columns on the post type listing screen:
    * Add columns for post meta, taxonomy terms, post fields, featured images, Posts 2 Posts connections, and callbacks
    * Out of the box column sorting for post meta, taxonomy terms (yes, really), and post fields
    * Add user capability restrictions to columns
    * Specify a default sort column and default sort order
 * Custom admin screen filters for post meta fields and taxonomy terms
 * Override the 'Featured Image' and 'Enter title here' text
 * Add the post type archive to the nav menus screen
 * Add the post type to the 'Right Now' section on the dashboard

## Extended front-end features ##

 * Override public or private query variables such as `posts_per_page`, `orderby`, `order` and `nopaging`
 * Add post types to the site's main RSS feed

## Usage ##

Need a simple post type with no frills? You can register a post type with a single parameter:

```php
register_extended_post_type( 'article' );
```

Try it. All the labels and post updated messages will be automatically generated, and you'll have a hierarchical public post type with an admin UI. Or for a bit more functionality:

```php
register_extended_post_type( 'story', array(

	# Add the post type to the site's main RSS feed:
	'show_in_feed' => true,

	# Show all posts on the post type archive:
	'archive' => array(
		'no_paging' => true
	),

	# Add some custom columns to the admin screen:
	'cols' => array(
		'featured_image' => array(
			'title'          => 'Illustration',
			'featured_image' => 'thumbnail'
		),
		'published' => array(
			'title'       => 'Published',
			'meta_key'    => 'published_date',
			'date_format' => 'd/m/Y'
		),
		'genre' => array(
			'title'    => 'Genre',
			'taxonomy' => 'genre'
		)
	),

	# Add a dropdown filter to the admin screen:
	'filters' => array(
		'genre' => array(
			'title'    => 'Genre',
			'taxonomy' => 'genre'
		)
	)

), array(

	# Specify the base label names:
	'singular' => 'Story',
	'plural'   => 'Stories',
	'slug'     => 'stories'

) );
```

Bam, we have a 'Stories' post type, with correctly generated labels and post updated messages, two custom columns in the admin area (the taxonomy one is sortable), and a parameter that overrides a private query var on the post type archive.

There's quite a bit more you can do. See the wiki for more examples.

## @TODO ##

 * Allow checkbox, radio and text input admin screen filters
 * Allow overriding of post updated messages via the `$args` parameter
 * Checkbox input type for `meta_exists` admin screen filter

## License ##

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
