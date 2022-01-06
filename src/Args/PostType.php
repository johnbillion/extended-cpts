<?php
declare( strict_types=1 );

namespace ExtCPTs\Args;

class PostType extends \Args\register_post_type {
	/**
	 * Associative array of admin screen columns to show for this post type.
	 *
	 * @var array<string,mixed>
	 */
	public array $admin_cols;

	/**
	 * Associative array of admin screen filters to show for this post type.
	 *
	 * @var array<string,mixed>
	 */
	public array $admin_filters;

	/**
	 * Associative array of query vars to override on this post type's archive.
	 *
	 * @var array<string,mixed>
	 */
	public array $archive;

	/**
	 * Force the use of the block editor for this post type. Must be used in
	 * combination with the `show_in_rest` argument.
	 *
	 * The primary use of this argument
	 * is to prevent the block editor from being used by setting it to false when
	 * `show_in_rest` is set to true.
	 */
	public bool $block_editor;

	/**
	 * Whether to show this post type on the 'At a Glance' section of the admin
	 * dashboard.
	 *
	 * Default true.
	 */
	public bool $dashboard_glance;

	/**
	 * Whether to show this post type on the 'Recently Published' section of the
	 * admin dashboard.
	 *
	 * Default true.
	 */
	public bool $dashboard_activity;

	/**
	 * Placeholder text which appears in the title field for this post type.
	 */
	public string $enter_title_here;

	/**
	 * Text which replaces the 'Featured Image' phrase for this post type.
	 */
	public string $featured_image;

	/**
	 * Whether to show Quick Edit links for this post type.
	 *
	 * Default true.
	 */
	public bool $quick_edit;

	/**
	 * Whether to include this post type in the site's main feed.
	 *
	 * Default false.
	 */
	public bool $show_in_feed;

	/**
	 * Associative array of query vars and their parameters for front end filtering.
	 *
	 * @var array<string,mixed>
	 */
	public array $site_filters;

	/**
	 * Associative array of query vars and their parameters for front end sorting.
	 *
	 * @var array<string,mixed>
	 */
	public array $site_sortables;
}
