<?php
declare( strict_types=1 );

namespace ExtCPTs\Args;

class Taxonomy extends \Args\register_taxonomy {

	/**
	 * The name of the custom meta box to use on the post editing screen for this taxonomy.
	 *
	 * Three custom meta boxes are provided:
	 *
	 *  - 'radio' for a meta box with radio inputs
	 *  - 'simple' for a meta box with a simplified list of checkboxes
	 *  - 'dropdown' for a meta box with a dropdown menu
	 *
	 * You can also pass the name of a callback function, eg `my_super_meta_box()`,
	 * or boolean `false` to remove the meta box.
	 *
	 * Default `null`, meaning the standard meta box is used.
	 */
	public string $meta_box;

	/**
	 * Whether to always show checked terms at the top of the meta box.
	 *
	 * This allows you to override WordPress' default behaviour if necessary.
	 *
	 * Default false if you're using a custom meta box (see the `$meta_box` argument), default true otherwise.
	 */
	public bool $checked_ontop;

	/**
	 * Whether to show this taxonomy on the 'At a Glance' section of the admin dashboard.
	 *
	 * Default false.
	 */
	public bool $dashboard_glance;

	/**
	 * Associative array of admin screen columns to show for this taxonomy.
	 *
	 * See the `TaxonomyAdmin::cols()` method for more information.
	 *
	 * @var array<string,mixed>
	 */
	public array $admin_cols;

	/**
	 * This parameter isn't feature complete. All it does currently is set the meta box
	 * to the 'radio' meta box, thus meaning any given post can only have one term
	 * associated with it for that taxonomy.
	 *
	 * 'exclusive' isn't really the right name for this, as terms aren't exclusive to a
	 * post, but rather each post can exclusively have only one term. It's not feature
	 * complete because you can edit a post in Quick Edit and give it more than one term
	 * from the taxonomy.
	 */
	public bool $exclusive;

	/**
	 * All this does currently is disable hierarchy in the taxonomy's rewrite rules.
	 *
	 * Default false.
	 */
	public bool $allow_hierarchy;
}
