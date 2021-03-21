[![Build Status](https://img.shields.io/github/workflow/status/johnbillion/extended-cpts/Test/develop?style=flat-square)](https://github.com/johnbillion/extended-cpts/actions)
[![Stable Release](https://img.shields.io/packagist/v/johnbillion/extended-cpts.svg)](https://packagist.org/packages/johnbillion/extended-cpts)
[![License](https://img.shields.io/badge/license-GPL_v2%2B-blue.svg)](https://github.com/johnbillion/extended-cpts/blob/master/LICENSE)
[![PHP 7 and 8](https://img.shields.io/badge/php-7%20/%208-blue.svg)](https://wordpress.org/support/update-php/)
[![Documentation](https://img.shields.io/badge/documentation-wiki-blue.svg)](https://github.com/johnbillion/extended-cpts/wiki)

# Extended CPTs #

Extended CPTs is a library which provides extended functionality to WordPress custom post types and taxonomies. This allows developers to quickly build post types and taxonomies without having to write the same code again and again.

Extended CPTs works with both the block editor and the classic editor.

[See the wiki for full documentation.](https://github.com/johnbillion/extended-cpts/wiki)

Not your first time here? See [Recent Changes for Developers](https://github.com/johnbillion/extended-cpts/wiki/Recent-Changes-for-Developers) to see what features are new in recent versions of Extended CPTs.

## Improved Defaults for Post Types ##

 * Automatically generated labels and post updated messages (in English)
 * Public post type with admin UI and post thumbnails enabled
 * Hierarchical with `page` capability type
 * Optimal admin menu placement

## Improved Defaults for Taxonomies ##

 * Automatically generated labels and term updated messages (in English)
 * Hierarchical public taxonomy with admin UI enabled

## Extended Admin Features ##

 * Declarative creation of table columns on the post type listing screen:
   * Columns for post meta, taxonomy terms, featured images, post fields, [Posts 2 Posts](https://wordpress.org/plugins/posts-to-posts/) connections, and custom functions
   * Sortable columns for post meta, taxonomy terms, and post fields
   * User capability restrictions
   * Default sort column and sort order
 * Declarative creation of table columns on the taxonomy term listing screen:
   * Columns for term meta and custom functions
   * User capability restrictions
 * Filter controls on the post type listing screen to enable filtering posts by post meta, taxonomy terms, post author, and post dates
 * Override the 'Featured Image' and 'Enter title here' text
 * Several custom meta boxes available for taxonomies on the post editing screen:
   * Simplified list of checkboxes
   * Radio buttons
   * Dropdown menu
   * Custom function
 * Post types and taxonomies automatically added to the 'At a Glance' section on the dashboard
 * Post types optionally added to the 'Recently Published' section on the dashboard

## Extended Front-end Features for Post Types ##

 * Specify a custom permalink structure:
   * For example `reviews/%year%/%month%/%review%`
   * Supports all relevant rewrite tags including dates and custom taxonomies
   * Automatic integration with the [Rewrite Rule Testing](https://wordpress.org/plugins/rewrite-testing/) plugin
 * Specify public query vars which enable filtering by post meta and post dates
 * Specify public query vars which enable sorting by post meta, taxonomy terms, and post fields
 * Override default public or private query vars such as `posts_per_page`, `orderby`, `order`, and `nopaging`
 * Add the post type to the site's main RSS feed

## Minimum Requirements ##

* **PHP:** 7.0  
  - PHP 7.4+ is recommended
  - PHP 8 is supported
* **WordPress:** 4.8  
  - Tested up to WP 5.7

## Installation ##

Extended CPTs is a developer library, not a plugin, which means you need to include it as a dependency in your project. Install it using Composer:

```bash
composer require johnbillion/extended-cpts
```

Other means of installation or usage, particularly bundling within a plugin, is not officially supported and done at your own risk.

Note that *Extended Taxonomies* is part of this library since version 4.0, so there's no need to require that too.

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
	register_extended_taxonomy( 'location', 'article' );
} );
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

		# Add the post type to the 'Recently Published' section of the dashboard:
		'dashboard_activity' => true,

		# Add some custom columns to the admin screen:
		'admin_cols' => [
			'story_featured_image' => [
				'title'          => 'Illustration',
				'featured_image' => 'thumbnail'
			],
			'story_published' => [
				'title_icon'  => 'dashicons-calendar-alt',
				'meta_key'    => 'published_date',
				'date_format' => 'd/m/Y'
			],
			'story_genre' => [
				'taxonomy' => 'genre'
			],
		],

		# Add some dropdown filters to the admin screen:
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
				'title_cb'    => function() {
					return '<em>Last</em> Updated';
				},
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

The `register_extended_post_type()` and `register_extended_taxonomy()` functions are ultimately wrappers for the `register_post_type()` and `register_taxonomy()` functions in WordPress core, so any of the parameters from those functions can be used.

There's quite a bit more you can do. [See the wiki for full documentation.](https://github.com/johnbillion/extended-cpts/wiki)

## Contributing and Testing ##

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for information on contributing.

## License: GPLv2 or later ##

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
