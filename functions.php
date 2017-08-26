<?php
declare(strict_types=1);

/**
 * Extended custom post types for WordPress.
 *
 * @package   ExtendedCPTs
 * @version   3.2.1
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
 * @param string $post_type The post type name.
 * @param array  $args {
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
 * @param array  $names {
 *     Optional. The plural, singular, and slug names.
 *
 *     @type string $plural   The plural form of the post type name.
 *     @type string $singular The singular form of the post type name.
 *     @type string $slug     The slug used in the permalinks for this post type.
 * }
 * @return Extended_CPT
 */
function register_extended_post_type( string $post_type, array $args = [], array $names = [] ): Extended_CPT {

	$cpt = new Extended_CPT( $post_type, $args, $names );

	if ( is_admin() ) {
		new Extended_CPT_Admin( $cpt, $cpt->args );
	}

	return $cpt;

}
