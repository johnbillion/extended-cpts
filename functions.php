<?php
declare(strict_types=1);

/**
 * Extended custom post types for WordPress.
 *
 * @package   ExtendedCPTs
 * @author    John Blackbourn <https://johnblackbourn.com>
 * @link      https://github.com/johnbillion/extended-cpts
 * @copyright 2012-2017 John Blackbourn
 * @license   GPL v2 or later
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

/**
 * Register an Extended Post Type.
 *
 * The `$args` parameter accepts all the standard arguments for `register_post_type()` in addition to several custom
 * arguments that provide extended functionality. Some of the default arguments differ from the defaults in
 * `register_post_type()`.
 *
 * @link https://github.com/johnbillion/extended-cpts/wiki/Basic-usage
 * @see register_post_type() for default arguments.
 *
 * @param string   $post_type The post type name.
 * @param array    $args {
 *     Optional. The post type arguments.
 *
 *     @type array  $admin_cols           Associative array of admin screen columns to show for this post type.
 *     @type array  $admin_filters        Associative array of admin screen filters to show for this post type.
 *     @type array  $archive              Associative array of query vars to override on this post type's archive.
 *     @type bool   $dashboard_glance     Whether to show this post type on the 'At a Glance' section of the admin
 *                                        dashboard. Default true.
 *     @type string $enter_title_here     Placeholder text which appears in the title field for this post type.
 *     @type string $featured_image       Text which replaces the 'Featured Image' phrase for this post type.
 *     @type bool   $quick_edit           Whether to show Quick Edit links for this post type. Default true.
 *     @type bool   $show_in_feed         Whether to include this post type in the site's main feed. Default false.
 *     @type array  $site_filters         Associative array of query vars and their parameters for front end filtering.
 *     @type array  $site_sortables       Associative array of query vars and their parameters for front end sorting.
 * }
 * @param string[] $names {
 *     Optional. The plural, singular, and slug names.
 *
 *     @type string $plural   The plural form of the post type name.
 *     @type string $singular The singular form of the post type name.
 *     @type string $slug     The slug used in the permalinks for this post type.
 * }
 * @return Extended_CPT
 */
function register_extended_post_type( string $post_type, array $args = [], array $names = [] ) : Extended_CPT {

	$cpt = new Extended_CPT( $post_type, $args, $names );

	if ( is_admin() ) {
		new Extended_CPT_Admin( $cpt, $cpt->args );
	}

	return $cpt;

}

/**
 * Register an extended custom taxonomy.
 *
 * The `$args` parameter accepts all the standard arguments for `register_taxonomy()` in addition to several custom
 * arguments that provide extended functionality. Some of the default arguments differ from the defaults in
 * `register_taxonomy()`.
 *
 * The `$taxonomy` parameter is used as the taxonomy name and to build the taxonomy labels. This means you can create
 * a taxonomy with just two parameters and all labels and term updated messages will be generated for you. Example:
 *
 *     register_extended_taxonomy( 'location', 'post' );
 *
 * The singular name, plural name, and slug are generated from the taxonomy name. These can be overridden with the
 * `$names` parameter if necessary. Example:
 *
 *     register_extended_taxonomy( 'story', 'post' [], [
 *         'plural' => 'Stories',
 *         'slug'   => 'tales',
 *     ] );
 *
 * @see register_taxonomy() for default arguments.
 *
 * @param string       $taxonomy    The taxonomy name.
 * @param array|string $object_type Name(s) of the object type(s) for the taxonomy.
 * @param array        $args {
 *     Optional. The taxonomy arguments.
 *
 *     @type string $meta_box         The name of the custom meta box to use on the post editing screen for this
 *                                    taxonomy. Three custom meta boxes are provided: 'radio' for a meta box with radio
 *                                    inputs, 'simple' for a meta box with a simplified list of checkboxes, and
 *                                    'dropdown' for a meta box with a dropdown menu. You can also pass the name of a
 *                                    callback function, eg my_super_meta_box(), or boolean false to remove the meta
 *                                    box. Default null, meaning the standard meta box is used.
 *     @type bool   $checked_ontop    Whether to always show checked terms at the top of the meta box. This allows you
 *                                    to override WordPress' default behaviour if necessary. Default false if you're
 *                                    using a custom meta box (see the $meta_box argument), default true otherwise.
 *     @type bool   $dashboard_glance Whether to show this taxonomy on the 'At a Glance' section of the admin dashboard.
 *                                    Default false.
 *     @type array  $admin_cols       Associative array of admin screen columns to show for this taxonomy. See the
 *                                    `Extended_Taxonomy_Admin::cols()` method for more information.
 *     @type bool   $exclusive        This parameter isn't feature complete. All it does currently is set the meta box
 *                                    to the 'radio' meta box, thus meaning any given post can only have one term
 *                                    associated with it for that taxonomy. 'exclusive' isn't really the right name for
 *                                    this, as terms aren't exclusive to a post, but rather each post can exclusively
 *                                    have only one term. It's not feature complete because you can edit a post in
 *                                    Quick Edit and give it more than one term from the taxonomy.
 *     @type bool   $allow_hierarchy  All this does currently is disable hierarchy in the taxonomy's rewrite rules.
 *                                    Default false.
 * }
 * @param string[]     $names {
 *     Optional. The plural, singular, and slug names.
 *
 *     @type string $plural   The plural form of the taxonomy name.
 *     @type string $singular The singular form of the taxonomy name.
 *     @type string $slug     The slug used in the term permalinks for this taxonomy.
 * }
 * @return Extended_Taxonomy
 */
function register_extended_taxonomy( string $taxonomy, $object_type, array $args = [], array $names = [] ) : Extended_Taxonomy {

	$taxo = new Extended_Taxonomy( $taxonomy, $object_type, $args, $names );

	if ( is_admin() ) {
		new Extended_Taxonomy_Admin( $taxo, $taxo->args );
	}

	return $taxo;

}
