[![Build Status](https://travis-ci.org/johnbillion/extended-cpts.svg?branch=master)](https://travis-ci.org/johnbillion/extended-cpts)
[![Stable Release](https://img.shields.io/packagist/v/johnbillion/extended-cpts.svg)](https://packagist.org/packages/johnbillion/extended-cpts)
[![License](https://img.shields.io/badge/license-GPL_v2%2B-blue.svg)](https://github.com/johnbillion/extended-cpts/blob/master/LICENSE)

# Extended CPTs #

Extended CPTs is a library which provides extended functionality to WordPress custom post types and taxonomies. This allows developers to quickly build post types and taxonomies without having to write the same code again and again.

[See the wiki for full documentation.](https://github.com/johnbillion/extended-cpts/wiki)

**Note that *Extended Taxonomies* was merged into this library with version 4.0. There's now no need to use the separate *Extended Taxonomies* library.**

## Improved Defaults for Post Types ##

 * Automatically generated labels and post updated messages
 * Public post type with admin UI enabled
 * Hierarchical with `page` capability type
 * Support for post thumbnails
 * Optimal admin menu placement

## Improved Defaults for Taxonomies ##

 * Automatically generated labels and term updated messages
 * Hierarchical public taxonomy with admin UI enabled

## Extended Admin Features ##

 * Ridiculously easy custom columns on the post type listing screen:
   * Columns for post meta, taxonomy terms, featured images, post fields, [Posts 2 Posts](https://wordpress.org/plugins/posts-to-posts/) connections, and callback functions
   * Sortable columns for post meta, taxonomy terms, and post fields
   * User capability restrictions
   * Default sort column and sort order
 * Ridiculously easy custom columns on the term listing screen:
   * Columns available for term meta and callback functions
   * User capability restrictions
 * Filter controls on the post type listing screen to enable filtering by post meta and taxonomy terms
 * Override the 'Featured Image' and 'Enter title here' text
 * Several custom meta boxes available for taxonomies on the post editing screen:
   * A simplified list of checkboxes
   * Radio inputs
   * A dropdown menu
   * Or a callback function
 * Post types and taxonomies automatically added to the 'At a Glance' section on the dashboard

## Extended Front-end Features for Post Types ##

 * Specify a custom permalink structure:
   * For example `reviews/%year%/%month%/%review%`
   * Supports all relevant rewrite tags including dates and custom taxonomies
   * Automatic integration with the [Rewrite Rule Testing](https://wordpress.org/plugins/rewrite-testing/) plugin
 * Specify public query vars which enable filtering by post meta
 * Specify public query vars which enable sorting by post meta, taxonomy terms, and post fields
 * Override public or private query vars such as `posts_per_page`, `orderby`, `order`, and `nopaging`
 * Add the post type to the site's main RSS feed

## Minimum Requirements ##

**PHP:** 7.0  
**WordPress:** 4.8  

## Installation ##

Extended CPTs is a developer library, not a plugin, which means you need to include it somewhere in your own project.
You can use Composer:

```bash
composer require johnbillion/extended-cpts
```

Or you can download the library and include it manually:

```php
require_once 'extended-cpts/extended-cpts.php';
```

## Usage ##

Need a simple post type with no frills? You can register a post type with a single parameter:

```php
add_action( 'init', function() {
	register_extended_post_type( 'article' );
} );
```

And you can register a taxonomy with just two parameters:

```php
add_action( 'init', function() {
	register_extended_taxonomy( 'location', 'post' );
) };
```

Try it. You'll have a hierarchical public post type with an admin UI, a hierarchical public taxonomy with an admin UI, and all the labels and updated messages for them will be automatically generated.

Or for a bit more functionality:

```php
add_action( 'init', function() {
	register_extended_post_type( 'story', [

		# Add the post type to the site's main RSS feed:
		'show_in_feed' => true,

		# Show all posts on the post type archive:
		'archive' => [
			'nopaging' => true,
		],

		# Add some custom columns to the admin screen:
		'admin_cols' => [
			'story_featured_image' => [
				'title'          => 'Illustration',
				'featured_image' => 'thumbnail'
			],
			'story_published' => [
				'title'       => 'Published',
				'meta_key'    => 'published_date',
				'date_format' => 'd/m/Y'
			],
			'story_genre' => [
				'taxonomy' => 'genre'
			],
		],

		# Add a dropdown filter to the admin screen:
		'admin_filters' => [
			'story_genre' => [
				'taxonomy' => 'genre'
			],
			'story_rating' => [
				'meta_key' => 'star_rating',
			],
		],

	], [

		# Override the base names used for labels:
		'singular' => 'Story',
		'plural'   => 'Stories',
		'slug'     => 'stories',

	] );

	register_extended_taxonomy( 'genre', 'story', [

		# Use radio buttons in the meta box for this taxonomy on the post editing screen:
		'meta_box' => 'radio',

		# Add a custom column to the admin screen:
		'admin_cols' => [
			'updated' => [
				'title'       => 'Updated',
				'meta_key'    => 'updated_date',
				'date_format' => 'd/m/Y'
			],
		],

	] );
} );
```

Bam, we now have:

* A 'Stories' post type, with correctly generated labels and post updated messages, three custom columns in the admin area (two of which are sortable), stories added to the main RSS feed, and all stories displayed on the post type archive.
* A 'Genre' taxonomy attached to the 'Stories' post type, with correctly generated labels and term updated messages, and a custom column in the admin area.

The `register_extended_post_type()` and  `register_extended_taxonomy()` functions are ultimately wrappers for the `register_post_type()` and `register_taxonomy()` functions in WordPress core, so any of the parameters from those functions can be used.

There's quite a bit more you can do. [See the wiki for full documentation.](https://github.com/johnbillion/extended-cpts/wiki)

## Contributing and Testing ##

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for information on contributing.

Please see [the tests readme](tests/README.md) for information on running the unit test suite.

## License: GPLv2 or later ##

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
