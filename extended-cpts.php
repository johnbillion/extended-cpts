<?php
/**
 * Extended custom post types for WordPress.
 *
 * @package   ExtendedCPTs
 * @version   3.2.0
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

if ( ! function_exists( 'register_extended_post_type' ) ) {
/**
 * Register an Extended Post Type.
 *
 * The `$args` parameter accepts all the standard arguments for `register_post_type()` in addition to several custom
 * arguments that provide extended functionality. Some of the default arguments differ from the defaults in
 * `register_post_type()`.
 *
 * The `$post_type` parameter is used as the post type name and to build the post type labels. This means you can create
 * a post type with just one parameter and all labels and post updated messages will be generated for you. Example:
 *
 *     register_extended_post_type( 'event' );
 *
 * The singular name, plural name, and slug are generated from the post type name. These can be overridden with the
 * `$names` parameter if necessary. Example:
 *
 *     register_extended_post_type( 'person', array(), array(
 *         'plural' => 'People',
 *         'slug'   => 'meet-the-team'
 *     ) );
 *
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
function register_extended_post_type( $post_type, array $args = array(), array $names = array() ) {

	$cpt = new Extended_CPT( $post_type, $args, $names );

	if ( is_admin() ) {
		new Extended_CPT_Admin( $cpt, $args );
	}

	return $cpt;

}
}

if ( ! class_exists( 'Extended_CPT' ) ) {
class Extended_CPT {

	/**
	 * Default arguments for custom post types.
	 *
	 * The arguments listed are the ones which differ from the defaults in `register_post_type()`.
	 *
	 * @var array
	 */
	protected $defaults = array(
		'public'          => true,
		'menu_position'   => 6,
		'capability_type' => 'page',
		'hierarchical'    => true,
		'supports'        => array( 'title', 'editor', 'thumbnail' ),
		'site_filters'    => null,  # Custom arg
		'site_sortables'  => null,  # Custom arg
		'show_in_feed'    => false, # Custom arg
		'archive'         => null,  # Custom arg
		'featured_image'  => null,  # Custom arg
	);

	/**
	 * Some other member variables you don't need to worry about:
	 */
	public $post_type;
	public $post_slug;
	public $post_singular;
	public $post_plural;
	public $post_singular_low;
	public $post_plural_low;
	public $args;

	/**
	 * Class constructor.
	 *
	 * @see register_extended_post_type()
	 *
	 * @param string $post_type The post type name.
	 * @param array  $args      Optional. The post type arguments.
	 * @param array  $names     Optional. The plural, singular, and slug names.
	 */
	public function __construct( $post_type, array $args = array(), array $names = array() ) {

		/**
		 * Filter the arguments for this post type.
		 *
		 * @since 2.4.0
		 *
		 * @param array $args The post type arguments.
		 */
		$args  = apply_filters( "ext-cpts/{$post_type}/args", $args );
		/**
		 * Filter the names for this post type.
		 *
		 * @since 2.4.0
		 *
		 * @param array $names The plural, singular, and slug names (if any were specified).
		 */
		$names = apply_filters( "ext-cpts/{$post_type}/names", $names );

		if ( isset( $names['singular'] ) ) {
			$this->post_singular = $names['singular'];
		} else {
			$this->post_singular = ucwords( str_replace( array( '-', '_' ), ' ', $post_type ) );
		}

		if ( isset( $names['slug'] ) ) {
			$this->post_slug = $names['slug'];
		} else if ( isset( $names['plural'] ) ) {
			$this->post_slug = $names['plural'];
		} else {
			$this->post_slug = $post_type . 's';
		}

		if ( isset( $names['plural'] ) ) {
			$this->post_plural = $names['plural'];
		} else {
			$this->post_plural = $this->post_singular . 's';
		}

		$this->post_type = strtolower( $post_type );
		$this->post_slug = strtolower( $this->post_slug );

		# Build our base post type names:
		$this->post_singular_low = strtolower( $this->post_singular );
		$this->post_plural_low   = strtolower( $this->post_plural );

		# Build our labels:
		# Why aren't these translatable?
		# Answer: https://github.com/johnbillion/extended-cpts/pull/5#issuecomment-33756474
		$this->defaults['labels'] = array(
			'name'                  => $this->post_plural,
			'singular_name'         => $this->post_singular,
			'menu_name'             => $this->post_plural,
			'name_admin_bar'        => $this->post_singular,
			'add_new'               => 'Add New',
			'add_new_item'          => sprintf( 'Add New %s', $this->post_singular ),
			'edit_item'             => sprintf( 'Edit %s', $this->post_singular ),
			'new_item'              => sprintf( 'New %s', $this->post_singular ),
			'view_item'             => sprintf( 'View %s', $this->post_singular ),
			'view_items'            => sprintf( 'View %s', $this->post_plural ),
			'search_items'          => sprintf( 'Search %s', $this->post_plural ),
			'not_found'             => sprintf( 'No %s found.', $this->post_plural_low ),
			'not_found_in_trash'    => sprintf( 'No %s found in trash.', $this->post_plural_low ),
			'parent_item_colon'     => sprintf( 'Parent %s:', $this->post_singular ),
			'all_items'             => sprintf( 'All %s', $this->post_plural ),
			'archives'              => sprintf( '%s Archives', $this->post_singular ),
			'attributes'            => sprintf( '%s Attributes', $this->post_singular ),
			'insert_into_item'      => sprintf( 'Insert into %s', $this->post_singular_low ),
			'uploaded_to_this_item' => sprintf( 'Uploaded to this %s', $this->post_singular_low ),
			'filter_items_list'     => sprintf( 'Filter %s list', $this->post_plural_low ),
			'items_list_navigation' => sprintf( '%s list navigation', $this->post_plural ),
			'items_list'            => sprintf( '%s list', $this->post_plural ),
		);

		# Build the featured image labels:
		if ( isset( $args['featured_image'] ) ) {
			$featured_image_low = strtolower( $args['featured_image'] );
			$this->defaults['labels']['featured_image']        = $args['featured_image'];
			$this->defaults['labels']['set_featured_image']    = sprintf( 'Set %s', $featured_image_low );
			$this->defaults['labels']['remove_featured_image'] = sprintf( 'Remove %s', $featured_image_low );
			$this->defaults['labels']['use_featured_image']    = sprintf( 'Use as %s', $featured_image_low );
		}

		# Only set default rewrites if we need them
		if ( isset( $args['public'] ) && ! $args['public'] ) {
			$this->defaults['rewrite'] = false;
		} else {
			$this->defaults['rewrite'] = array(
				'slug'       => $this->post_slug,
				'with_front' => false,
			);
		}

		# Merge our args with the defaults:
		$this->args = array_merge( $this->defaults, $args );

		# This allows the 'labels' and 'rewrite' args to contain all, some, or no values:
		foreach ( array( 'labels', 'rewrite' ) as $arg ) {
			if ( isset( $args[ $arg ] ) && is_array( $args[ $arg ] ) ) {
				$this->args[ $arg ] = array_merge( $this->defaults[ $arg ], $args[ $arg ] );
			}
		}

		# Enable post type archives by default
		if ( ! isset( $this->args['has_archive'] ) ) {
			$this->args['has_archive'] = $this->args['public'];
		}

		# Front-end sortables:
		if ( $this->args['site_sortables'] && ! is_admin() ) {
			add_filter( 'pre_get_posts', array( $this, 'maybe_sort_by_fields' ) );
			add_filter( 'posts_clauses', array( $this, 'maybe_sort_by_taxonomy' ), 10, 2 );
		}

		# Front-end filters:
		if ( $this->args['site_filters'] && ! is_admin() ) {
			add_action( 'pre_get_posts', array( $this, 'maybe_filter' ) );
			add_filter( 'query_vars',    array( $this, 'add_query_vars' ) );
		}

		# Post type in the site's main feed:
		if ( $this->args['show_in_feed'] ) {
			add_filter( 'request', array( $this, 'add_to_feed' ) );
		}

		# Post type archive query vars:
		if ( $this->args['archive'] && ! is_admin() ) {
			add_filter( 'parse_request', array( $this, 'override_private_query_vars' ), 1 );
		}

		# Custom post type permastruct:
		if ( $this->args['rewrite'] && ! empty( $this->args['rewrite']['permastruct'] ) ) {
			add_action( 'registered_post_type', array( $this, 'registered_post_type' ), 1, 2 );
			add_filter( 'post_type_link',       array( $this, 'post_type_link' ), 1, 4 );
		}

		# Rewrite testing:
		if ( $this->args['rewrite'] ) {
			add_filter( 'rewrite_testing_tests', array( $this, 'rewrite_testing_tests' ), 1 );
		}

		# Register post type when WordPress initialises:
		if ( did_action( 'init' ) ) {
			$this->register_post_type();
		} else {
			// @codeCoverageIgnoreStart
			add_action( 'init', array( $this, 'register_post_type' ), 9 );
			// @codeCoverageIgnoreEnd
		}

		/**
		 * Fired when the extended post type instance is set up.
		 *
		 * @since 3.1.0
		 *
		 * @param Extended_CPT $instance The extended post type instance.
		 */
		do_action( "ext-cpts/{$post_type}/instance", $this );

	}

	/**
	 * Set the relevant query vars for filtering posts by our front-end filters.
	 *
	 * @param WP_Query $wp_query The current WP_Query object.
	 */
	public function maybe_filter( WP_Query $wp_query ) {

		if ( empty( $wp_query->query['post_type'] ) || ! in_array( $this->post_type, (array) $wp_query->query['post_type'] ) ) {
			return;
		}

		$vars = Extended_CPT::get_filter_vars( $wp_query->query, $this->args['site_filters'] );

		if ( empty( $vars ) ) {
			return;
		}

		foreach ( $vars as $key => $value ) {
			if ( is_array( $value ) ) {
				$query = $wp_query->get( $key );
				if ( empty( $query ) ) {
					$query = array();
				}
				$value = array_merge( $query, $value );
			}
			$wp_query->set( $key, $value );
		}

	}

	/**
	 * Set the relevant query vars for sorting posts by our front-end sortables.
	 *
	 * @param WP_Query $wp_query The current WP_Query object.
	 */
	public function maybe_sort_by_fields( WP_Query $wp_query ) {

		if ( empty( $wp_query->query['post_type'] ) || ! in_array( $this->post_type, (array) $wp_query->query['post_type'] ) ) {
			return;
		}

		// If we've not specified an order:
		if ( empty( $wp_query->query['orderby'] ) ) {

			// Loop over our sortables to find the default sort field (if there is one):
			foreach ( $this->args['site_sortables'] as $id => $col ) {
				if ( is_array( $col ) && isset( $col['default'] ) ) {
					// @TODO Don't set 'order' if 'orderby' is an array (WP 4.0+)
					$wp_query->query['orderby'] = $id;
					$wp_query->query['order']   = ( 'desc' === strtolower( $col['default'] ) ? 'desc' : 'asc' );
					break;
				}
			}
		}

		$sort = Extended_CPT::get_sort_field_vars( $wp_query->query, $this->args['site_sortables'] );

		if ( empty( $sort ) ) {
			return;
		}

		foreach ( $sort as $key => $value ) {
			$wp_query->set( $key, $value );
		}

	}

	/**
	 * Filter the query's SQL clauses so we can sort posts by taxonomy terms.
	 *
	 * @param  array    $clauses  The current query's SQL clauses.
	 * @param  WP_Query $wp_query The current `WP_Query` object.
	 * @return array              The updated SQL clauses.
	 */
	public function maybe_sort_by_taxonomy( array $clauses, WP_Query $wp_query ) {

		if ( empty( $wp_query->query['post_type'] ) || ! in_array( $this->post_type, (array) $wp_query->query['post_type'] ) ) {
			return $clauses;
		}

		$sort = Extended_CPT::get_sort_taxonomy_clauses( $clauses, $wp_query->query, $this->args['site_sortables'] );

		if ( empty( $sort ) ) {
			return $clauses;
		}

		return array_merge( $clauses, $sort );

	}

	/**
	 * Get the array of private query vars for the given filters, to apply to the current query in order to filter it by the
	 * given public query vars.
	 *
	 * @param  array $query   The public query vars, usually from `$wp_query->query`.
	 * @param  array $filters The filters valid for this query (usually the value of the `admin_filters` or
	 *                        `site_filters` argument when registering an extended post type).
	 * @return array          The list of private query vars to apply to the query.
	 */
	public static function get_filter_vars( array $query, array $filters ) {

		$return = array();

		foreach ( $filters as $filter_key => $filter ) {

			if ( ! isset( $query[ $filter_key ] ) || ( '' === $query[ $filter_key ] ) ) {
				continue;
			}
			if ( isset( $filter['cap'] ) && ! current_user_can( $filter['cap'] ) ) {
				continue;
			}

			if ( isset( $filter['meta_key'] ) ) {
				$meta_query = array(
					'key'   => $filter['meta_key'],
					'value' => wp_unslash( $query[ $filter_key ] ),
				);
			} else if ( isset( $filter['meta_search_key'] ) ) {
				$meta_query = array(
					'key'     => $filter['meta_search_key'],
					'value'   => wp_unslash( $query[ $filter_key ] ),
					'compare' => 'LIKE',
				);
			} else if ( isset( $filter['meta_exists'] ) ) {
				$meta_query = array(
					'key'     => wp_unslash( $query[ $filter_key ] ),
					'compare' => 'NOT IN',
					'value'   => array( '', '0', 'false', 'null' ),
				);
			} else {
				continue;
			}

			if ( isset( $filter['meta_query'] ) ) {
				$meta_query = array_merge( $meta_query, $filter['meta_query'] );
			}

			if ( ! empty( $meta_query ) ) {
				$return['meta_query'][] = $meta_query;
			}
		}

		return $return;

	}

	/**
	 * Get the array of private and public query vars for the given sortables, to apply to the current query in order to
	 * sort it by the requested orderby field.
	 *
	 * @param  array $vars      The public query vars, usually from `$wp_query->query`.
	 * @param  array $sortables The sortables valid for this query (usually the value of the `admin_cols` or
	 *                          `site_sortables` argument when registering an extended post type.
	 * @return array            The list of private and public query vars to apply to the query.
	 */
	public static function get_sort_field_vars( array $vars, array $sortables ) {

		if ( ! isset( $vars['orderby'] ) ) {
			return array();
		}
		if ( ! isset( $sortables[ $vars['orderby'] ] ) ) {
			return array();
		}

		$orderby = $sortables[ $vars['orderby'] ];

		if ( ! is_array( $orderby ) ) {
			return array();
		}
		if ( isset( $orderby['sortable'] ) && ! $orderby['sortable'] ) {
			return array();
		}

		$return = array();

		if ( isset( $orderby['meta_key'] ) ) {
			$return['meta_key'] = $orderby['meta_key'];
			$return['orderby']  = 'meta_value';
			// @TODO meta_value_num
		} else if ( isset( $orderby['post_field'] ) ) {
			$field = str_replace( 'post_', '', $orderby['post_field'] );
			$return['orderby'] = $field;
		}

		if ( isset( $vars['order'] ) ) {
			$return['order'] = $vars['order'];
		}

		return $return;

	}

	/**
	 * Get the array of SQL clauses for the given sortables, to apply to the current query in order to
	 * sort it by the requested orderby field.
	 *
	 * @param  array $clauses   The query's SQL clauses.
	 * @param  array $vars      The public query vars, usually from `$wp_query->query`.
	 * @param  array $sortables The sortables valid for this query (usually the value of the `admin_cols` or
	 *                          `site_sortables` argument when registering an extended post type).
	 * @return array            The list of SQL clauses to apply to the query.
	 */
	public static function get_sort_taxonomy_clauses( array $clauses, array $vars, array $sortables ) {

		global $wpdb;

		if ( ! isset( $vars['orderby'] ) ) {
			return array();
		}
		if ( ! isset( $sortables[ $vars['orderby'] ] ) ) {
			return array();
		}

		$orderby = $sortables[ $vars['orderby'] ];

		if ( ! is_array( $orderby ) ) {
			return array();
		}
		if ( isset( $orderby['sortable'] ) && ! $orderby['sortable'] ) {
			return array();
		}
		if ( ! isset( $orderby['taxonomy'] ) ) {
			return array();
		}

		# Taxonomy term ordering courtesy of http://scribu.net/wordpress/sortable-taxonomy-columns.html
		$clauses['join'] .= "
			LEFT OUTER JOIN {$wpdb->term_relationships} as ext_cpts_tr
			ON ( {$wpdb->posts}.ID = ext_cpts_tr.object_id )
			LEFT OUTER JOIN {$wpdb->term_taxonomy} as ext_cpts_tt
			ON ( ext_cpts_tr.term_taxonomy_id = ext_cpts_tt.term_taxonomy_id )
			LEFT OUTER JOIN {$wpdb->terms} as ext_cpts_t
			ON ( ext_cpts_tt.term_id = ext_cpts_t.term_id )
		";
		$clauses['where'] .= $wpdb->prepare( ' AND ( taxonomy = %s OR taxonomy IS NULL )', $orderby['taxonomy'] );
		$clauses['groupby'] = 'ext_cpts_tr.object_id';
		$clauses['orderby'] = 'GROUP_CONCAT( ext_cpts_t.name ORDER BY name ASC ) ';
		$clauses['orderby'] .= ( isset( $vars['order'] ) && ( 'ASC' === strtoupper( $vars['order'] ) ) ) ? 'ASC' : 'DESC';

		return $clauses;

	}

	/**
	 * Add our filter names to the public query vars.
	 *
	 * @param  array $vars Public query variables.
	 * @return array       Updated public query variables.
	 */
	public function add_query_vars( array $vars ) {

		$filters = array_keys( $this->args['site_filters'] );

		return array_merge( $vars, $filters );

	}

	/**
	 * Add our post type to the feed.
	 *
	 * @param  array $vars Request parameters.
	 * @return array       Updated request parameters.
	 */
	public function add_to_feed( array $vars ) {

		# If it's not a feed, we're not interested:
		if ( ! isset( $vars['feed'] ) ) {
			return $vars;
		}

		if ( ! isset( $vars['post_type'] ) ) {
			$vars['post_type'] = array( 'post', $this->post_type );
		} else if ( is_array( $vars['post_type'] ) && ( count( $vars['post_type'] ) > 1 ) ) {
			$vars['post_type'][] = $this->post_type;
		}

		return $vars;

	}

	/**
	 * Add to or override our post type archive's private query vars.
	 *
	 * @param  WP $wp The WP request object.
	 * @return WP     Updated WP request object.
	 */
	public function override_private_query_vars( WP $wp ) {

		# If it's not our post type, bail out:
		if ( ! isset( $wp->query_vars['post_type'] ) || ( $this->post_type !== $wp->query_vars['post_type'] ) ) {
			return $wp;
		}

		# If it's a single post, bail out:
		if ( isset( $wp->query_vars['name'] ) ) {
			return $wp;
		}

		# Set the vars:
		foreach ( $this->args['archive'] as $var => $value ) {
			$wp->query_vars[ $var ] = $value;
		}

		return $wp;

	}

	/**
	 * Action fired after a CPT is registered in order to set up the custom permalink structure for the post type.
	 *
	 * @param string                $post_type Post type name.
	 * @param stdClass|WP_Post_Type $args      Arguments used to register the post type.
	 */
	public function registered_post_type( $post_type, $args ) {
		if ( $post_type !== $this->post_type ) {
			return;
		}
		$struct = str_replace( "%{$this->post_type}_slug%", $this->post_slug, $args->rewrite['permastruct'] );
		$struct = str_replace( '%postname%', "%{$this->post_type}%", $struct );
		add_permastruct( $this->post_type, $struct, $args->rewrite );
	}

	/**
	 * Filter the post type permalink in order to populate its rewrite tags.
	 *
	 * @param  string  $post_link The post's permalink.
	 * @param  WP_Post $post      The post in question.
	 * @param  bool    $leavename Whether to keep the post name.
	 * @param  bool    $sample    Is it a sample permalink.
	 * @return string             The post's permalink.
	 */
	public function post_type_link( $post_link, WP_Post $post, $leavename, $sample ) {

		# If it's not our post type, bail out:
		if ( $this->post_type !== $post->post_type ) {
			return $post_link;
		}

		$date = explode( ' ', mysql2date( 'Y m d H i s', $post->post_date ) );
		$replacements = array(
			'%year%'     => $date[0],
			'%monthnum%' => $date[1],
			'%day%'      => $date[2],
			'%hour%'     => $date[3],
			'%minute%'   => $date[4],
			'%second%'   => $date[5],
			'%post_id%'  => $post->ID,
		);

		if ( false !== strpos( $post_link, '%author%' ) ) {
			$replacements['%author%'] = get_userdata( $post->post_author )->user_nicename;
		}

		foreach ( get_object_taxonomies( $post ) as $tax ) {
			if ( false === strpos( $post_link, "%{$tax}%" ) ) {
				continue;
			}

			if ( $terms = get_the_terms( $post, $tax ) ) {

				/**
				 * Filter the term that gets used in the `$tax` permalink token.
				 * @TODO make this more betterer ^
				 *
				 * @param WP_Term  $term  The `$tax` term to use in the permalink.
				 * @param array    $terms Array of all `$tax` terms associated with the post.
				 * @param WP_Post  $post  The post in question.
				 */
				$term_object = apply_filters( "post_link_{$tax}", reset( $terms ), $terms, $post );

				$term = get_term( $term_object, $tax )->slug;

			} else {
				$term = $post->post_type;

				/**
				 * Filter the default term name that gets used in the `$tax` permalink token.
				 * @TODO make this more betterer ^
				 *
				 * @param string  $term The `$tax` term name to use in the permalink.
				 * @param WP_Post $post The post in question.
				 */
				$default_term_name = apply_filters( "default_{$tax}", get_option( "default_{$tax}", '' ), $post );
				if ( $default_term_name ) {
					if ( ! is_wp_error( $default_term = get_term( $default_term_name, $tax ) ) ) {
						$term = $default_term->slug;
					}
				}
			}

			$replacements[ "%{$tax}%" ] = $term;

		}

		$post_link = str_replace( array_keys( $replacements ), $replacements, $post_link );

		return $post_link;

	}

	/**
	 * Add our rewrite tests to the Rewrite Rule Testing tests array.
	 *
	 * @codeCoverageIgnore
	 *
	 * @param  array $tests The existing rewrite rule tests.
	 * @return array        Updated rewrite rule tests.
	 */
	public function rewrite_testing_tests( array $tests ) {

		$extended = new Extended_CPT_Rewrite_Testing( $this );

		return array_merge( $tests, $extended->get_tests() );

	}

	/**
	 * Registers our post type.
	 *
	 * The only difference between this and regular `register_post_type()` calls is this will trigger an error of
	 * `E_USER_ERROR` level if a `WP_Error` is returned.
	 *
	 */
	public function register_post_type() {

		if ( ! isset( $this->args['query_var'] ) || ( true === $this->args['query_var'] ) ) {
			$query_var = $this->post_type;
		} else {
			$query_var = $this->args['query_var'];
		}

		$existing = get_post_type_object( $this->post_type );

		if ( $query_var && count( $taxonomies = get_taxonomies( array( 'query_var' => $query_var ), 'objects' ) ) ) {

			// https://core.trac.wordpress.org/ticket/35089
			foreach ( $taxonomies as $tax ) {
				if ( $tax->query_var === $query_var ) {
					trigger_error( esc_html( sprintf(
						__( 'Post type query var "%s" clashes with a taxonomy query var of the same name', 'extended-cpts' ),
						$query_var
					) ), E_USER_ERROR );
				}
			}

		}

		if ( empty( $existing ) ) {

			$cpt = register_post_type( $this->post_type, $this->args );

			if ( is_wp_error( $cpt ) ) {
				trigger_error( esc_html( $cpt->get_error_message() ), E_USER_ERROR );
			}
		} else {

			# This allows us to call `register_extended_post_type()` on an existing post type to add custom functionality
			# to the post type.
			$this->extend( $existing );

		}

	}

	/**
	 * Extends an existing post type object. Currently only handles labels.
	 *
	 * @param stdClass|WP_Post_Type $pto A post type object
	 */
	public function extend( $pto ) {

		# Merge core with overridden labels
		$this->args['labels'] = array_merge( (array) get_post_type_labels( $pto ), $this->args['labels'] );

		$GLOBALS['wp_post_types'][ $pto->name ]->labels = (object) $this->args['labels'];

	}

	/**
	 * Helper function for registering a taxonomy and adding it to this post type.
	 *
	 * Accepts the same parameters as `register_extended_taxonomy()`, minus the `$object_type` parameter. Will fall back
	 * to `register_taxonomy()` if Extended Taxonomies isn't present.
	 *
	 * Example usage:
	 *
	 *     $events   = register_extended_post_type( 'event' );
	 *     $location = $events->add_taxonomy( 'location' );
	 *
	 * @param  string $taxonomy The taxonomy name.
	 * @param  array  $args     Optional. The taxonomy arguments.
	 * @param  array  $names    Optional. An associative array of the plural, singular, and slug names.
	 * @return object|false     Taxonomy object, or boolean false if there's a problem.
	 */
	public function add_taxonomy( $taxonomy, array $args = array(), array $names = array() ) {

		if ( taxonomy_exists( $taxonomy ) ) {
			register_taxonomy_for_object_type( $taxonomy, $this->post_type );
		} else if ( function_exists( 'register_extended_taxonomy' ) ) {
			register_extended_taxonomy( $taxonomy, $this->post_type, $args, $names );
		} else {
			register_taxonomy( $taxonomy, $this->post_type, $args );
		}

		return get_taxonomy( $taxonomy );

	}

}
}

if ( ! class_exists( 'Extended_CPT_Admin' ) ) {
class Extended_CPT_Admin {

	/**
	 * Default arguments for custom post types.
	 *
	 * @var array
	 */
	protected $defaults = array(
		'quick_edit'           => true,  # Custom arg
		'dashboard_glance'     => true,  # Custom arg
		'admin_cols'           => null,  # Custom arg
		'admin_filters'        => null,  # Custom arg
		'enter_title_here'     => null,  # Custom arg
	);
	public $cpt;
	public $args;
	protected $_cols;
	protected $the_cols = null;
	protected $connection_exists = array();

	/**
	 * Class constructor.
	 *
	 * @param Extended_CPT $cpt  An extended post type object.
	 * @param array        $args Optional. The post type arguments.
	 */
	public function __construct( Extended_CPT $cpt, array $args = array() ) {

		$this->cpt = $cpt;
		# Merge our args with the defaults:
		$this->args = array_merge( $this->defaults, $args );

		# Admin columns:
		if ( $this->args['admin_cols'] ) {
			add_filter( 'manage_posts_columns',                                 array( $this, '_log_default_cols' ), 0 );
			add_filter( 'manage_pages_columns',                                 array( $this, '_log_default_cols' ), 0 );
			add_filter( "manage_edit-{$this->cpt->post_type}_sortable_columns", array( $this, 'sortables' ) );
			add_filter( "manage_{$this->cpt->post_type}_posts_columns",         array( $this, 'cols' ) );
			add_action( "manage_{$this->cpt->post_type}_posts_custom_column",   array( $this, 'col' ) );
			add_action( 'load-edit.php',                                        array( $this, 'default_sort' ) );
			add_filter( 'pre_get_posts',                                        array( $this, 'maybe_sort_by_fields' ) );
			add_filter( 'posts_clauses',                                        array( $this, 'maybe_sort_by_taxonomy' ), 10, 2 );
		}

		# Admin filters:
		if ( $this->args['admin_filters'] ) {
			add_filter( 'pre_get_posts',         array( $this, 'maybe_filter' ) );
			add_filter( 'query_vars',            array( $this, 'add_query_vars' ) );
			add_action( 'restrict_manage_posts', array( $this, 'filters' ) );
		}

		# 'Enter title here' filter:
		if ( $this->args['enter_title_here'] ) {
			add_filter( 'enter_title_here', array( $this, 'enter_title_here' ), 10, 2 );
		}

		# Hide month filter:
		if ( isset( $this->args['admin_filters']['m'] ) && ! $this->args['admin_filters']['m'] ) {
			add_action( 'admin_head-edit.php', array( $this, 'admin_head' ) );
		}

		# Quick Edit:
		if ( ! $this->args['quick_edit'] ) {
			add_filter( 'post_row_actions',                          array( $this, 'remove_quick_edit_action' ), 10, 2 );
			add_filter( 'page_row_actions',                          array( $this, 'remove_quick_edit_action' ), 10, 2 );
			add_filter( "bulk_actions-edit-{$this->cpt->post_type}", array( $this, 'remove_quick_edit_menu' ) );
		}

		# 'At a Glance' dashboard panels:
		if ( $this->args['dashboard_glance'] ) {
			add_filter( 'dashboard_glance_items', array( $this, 'glance_items' ), $this->cpt->args['menu_position'] );
		}

		# Post updated messages:
		add_filter( 'post_updated_messages',      array( $this, 'post_updated_messages' ), 1 );
		add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_post_updated_messages' ), 1, 2 );

	}

	/**
	 * Add some CSS to the post listing screen. Used to hide various screen elements.
	 */
	public function admin_head() {

		if ( $this->cpt->post_type !== self::get_current_post_type() ) {
			return;
		}

		?>
		<?php if ( isset( $this->args['admin_filters']['m'] ) && ! $this->args['admin_filters']['m'] ) { ?>
			<style type="text/css">
				#posts-filter select[name="m"] {
					display: none;
				}
			</style>
		<?php } ?>
		<?php

	}

	/**
	 * Set the default sort field and sort order on our post type admin screen.
	 */
	public function default_sort() {

		if ( $this->cpt->post_type !== self::get_current_post_type() ) {
			return;
		}

		# If we've already ordered the screen, bail out:
		if ( isset( $_GET['orderby'] ) ) {
			return;
		}

		# Loop over our columns to find the default sort column (if there is one):
		foreach ( $this->args['admin_cols'] as $id => $col ) {
			if ( is_array( $col ) && isset( $col['default'] ) ) {
				$_GET['orderby'] = $id;
				$_GET['order']   = ( 'desc' === strtolower( $col['default'] ) ? 'desc' : 'asc' );
				break;
			}
		}

	}

	/**
	 * Set the placeholder text for the title field for this post type.
	 *
	 * @param  string  $title The placeholder text.
	 * @param  WP_Post $post  The current post.
	 * @return string         The updated placeholder text.
	 */
	public function enter_title_here( $title, WP_Post $post ) {

		if ( $this->cpt->post_type !== $post->post_type ) {
			return $title;
		}

		return $this->args['enter_title_here'];

	}

	/**
	 * Returns the name of the post type for the current request.
	 *
	 * @return string The post type name.
	 */
	protected static function get_current_post_type() {

		if ( function_exists( 'get_current_screen' ) && is_object( get_current_screen() ) && 'edit' === get_current_screen()->base ) {
			return get_current_screen()->post_type;
		} else {
			return '';
		}

	}

	/**
	 * Output custom filter dropdown menus on the admin screen for this post type.
	 *
	 * Each item in the `admin_filters` array is an associative array of information for a filter. Defining a filter is
	 * easy. Just define an array which includes the filter title and filter type. You can display filters for post meta
	 * fields and taxonomy terms.
	 *
	 * The example below adds filters for the `event_type` meta key and the `location` taxonomy:
	 *
	 *     register_extended_post_type( 'event', array(
	 *         'admin_filters' => array(
	 *             'event_type' => array(
	 *                 'title'    => 'Event Type',
	 *                 'meta_key' => 'event_type'
	 *             ),
	 *             'event_location' => array(
	 *                 'title'    => 'Location',
	 *                 'taxonomy' => 'location'
	 *             ),
	 *             'event_is' => array(
	 *                 'title'       => 'All Events',
	 *                 'meta_exists' => array(
	 *                     'event_featured'  => 'Featured Events',
	 *                     'event_cancelled' => 'Cancelled Events'
	 *                 )
	 *             ),
	 *         )
	 *     ) );
	 *
	 * That's all you need to do. WordPress handles taxonomy term filtering itself, and the plugin handles the dropdown
	 * menu and filtering for post meta.
	 *
	 * Each item in the `admin_filters` array needs either a `taxonomy`, `meta_key`, `meta_search`, or `meta_exists`
	 * element containing the corresponding taxonomy name or post meta key.
	 *
	 * The `meta_exists` filter outputs a dropdown menu listing each of the meta_exists fields, allowing users to
	 * filter the screen by posts which have the corresponding meta field.
	 *
	 * The `meta_search` filter outputs a search input, allowing users to filter the screen by an arbitrary search value.
	 *
	 * There are a few optional elements:
	 *
	 *  - title - The filter title. If omitted, the title will use the `all_items` taxonomy label or a formatted version
	 *    of the post meta key.
	 *  - cap - A capability required in order for this filter to be displayed to the current user. Defaults to null,
	 *    meaning the filter is shown to all users.
	 *
	 * @TODO - meta_query - array
	 *
	 * @TODO - options - array or callable
	 *
	 */
	public function filters() {

		global $wpdb;

		if ( $this->cpt->post_type !== self::get_current_post_type() ) {
			return;
		}

		$pto = get_post_type_object( $this->cpt->post_type );

		foreach ( $this->args['admin_filters'] as $filter_key => $filter ) {

			if ( isset( $filter['cap'] ) && ! current_user_can( $filter['cap'] ) ) {
				continue;
			}

			if ( isset( $filter['taxonomy'] ) ) {

				$tax = get_taxonomy( $filter['taxonomy'] );

				if ( empty( $tax ) ) {
					continue;
				}

				# For this, we need the dropdown walker from Extended Taxonomies:
				if ( ! class_exists( $class = 'Walker_ExtendedTaxonomyDropdown' ) ) {
					trigger_error( esc_html( sprintf(
						__( 'The "%s" class is required in order to display taxonomy filters', 'extended-cpts' ),
						$class
					) ), E_USER_WARNING );
					continue;
				} else {
					$walker = new Walker_ExtendedTaxonomyDropdown( array(
						'field' => 'slug',
					) );
				}

				# If we haven't specified a title, use the all_items label from the taxonomy:
				if ( ! isset( $filter['title'] ) ) {
					$filter['title'] = $tax->labels->all_items;
				}

				# Output the dropdown:
				wp_dropdown_categories( array(
					'show_option_all' => $filter['title'],
					'hide_empty'      => false,
					'hide_if_empty'   => true,
					'hierarchical'    => true,
					'show_count'      => false,
					'orderby'         => 'name',
					'selected_cats'   => get_query_var( $tax->query_var ),
					'id'              => 'filter_' . $filter_key,
					'name'            => $tax->query_var,
					'taxonomy'        => $filter['taxonomy'],
					'walker'          => $walker,
				) );

			} else if ( isset( $filter['meta_key'] ) ) {

				# If we haven't specified a title, generate one from the meta key:
				if ( ! isset( $filter['title'] ) ) {
					$filter['title'] = str_replace( array( '-', '_' ), ' ', $filter['meta_key'] );
					$filter['title'] = ucwords( $filter['title'] ) . 's';
					$filter['title'] = sprintf( 'All %s', $filter['title'] );
				}

				if ( ! isset( $filter['options'] ) ) {
					# Fetch all the values for our meta key:
					# @TODO AND m.meta_value != null ?
					$filter['options'] = $wpdb->get_col( $wpdb->prepare( "
						SELECT DISTINCT meta_value
						FROM {$wpdb->postmeta} as m
						JOIN {$wpdb->posts} as p ON ( p.ID = m.post_id )
						WHERE m.meta_key = %s
						AND m.meta_value != ''
						AND p.post_type = %s
						ORDER BY m.meta_value ASC
					", $filter['meta_key'], $this->cpt->post_type ) );
				} else if ( is_callable( $filter['options'] ) ) {
					$filter['options'] = call_user_func( $filter['options'] );
				}

				if ( empty( $filter['options'] ) ) {
					continue;
				}

				$selected = wp_unslash( get_query_var( $filter_key ) );

				$use_key = false;

				foreach ( $filter['options'] as $k => $v ) {
					if ( ! is_numeric( $k ) ) {
						$use_key = true;
						break;
					}
				}

				# Output the dropdown:
				?>
				<select name="<?php echo esc_attr( $filter_key ); ?>" id="filter_<?php echo esc_attr( $filter_key ); ?>">
					<option value=""><?php echo esc_html( $filter['title'] ); ?></option>
					<?php
						foreach ( $filter['options'] as $k => $v ) {
							$key = ( $use_key ? $k : $v );
						?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected, $key ); ?>><?php echo esc_html( $v ); ?></option>
					<?php } ?>
				</select>
				<?php

			} else if ( isset( $filter['meta_search_key'] ) ) {

				# If we haven't specified a title, generate one from the meta key:
				if ( ! isset( $filter['title'] ) ) {
					$filter['title'] = str_replace( array( '-', '_' ), ' ', $filter['meta_search_key'] );
					$filter['title'] = ucwords( $filter['title'] );
				}

				$value = wp_unslash( get_query_var( $filter_key ) );

				# Output the search box:
				?>
				<label><?php printf( '%s:', esc_html( $filter['title'] ) ); ?>&nbsp;<input type="text" name="<?php echo esc_attr( $filter_key ); ?>" id="filter_<?php echo esc_attr( $filter_key ); ?>" value="<?php echo esc_attr( $value ); ?>" /></label>
				<?php

			} else if ( isset( $filter['meta_exists'] ) ) {

				# If we haven't specified a title, use the all_items label from the post type:
				if ( ! isset( $filter['title'] ) ) {
					$filter['title'] = $pto->labels->all_items;
				}

				$selected = wp_unslash( get_query_var( $filter_key ) );

				if ( 1 === count( $filter['meta_exists'] ) ) {

					# Output a checkbox:
					foreach ( $filter['meta_exists'] as $v => $t ) {
						?>
						<label><input type="checkbox" name="<?php echo esc_attr( $filter_key ); ?>" id="filter_<?php echo esc_attr( $filter_key ); ?>" value="<?php echo esc_attr( $v ); ?>" <?php checked( $selected, $v ); ?>>&nbsp;<?php echo esc_html( $t ); ?></label>
						<?php
					}
				} else {

					# Output a dropdown:
					?>
					<select name="<?php echo esc_attr( $filter_key ); ?>" id="filter_<?php echo esc_attr( $filter_key ); ?>">
						<option value=""><?php echo esc_html( $filter['title'] ); ?></option>
						<?php foreach ( $filter['meta_exists'] as $v => $t ) { ?>
							<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $selected, $v ); ?>><?php echo esc_html( $t ); ?></option>
						<?php } ?>
					</select>
					<?php

				}
			}
		}

	}

	/**
	 * Add our filter names to the public query vars.
	 *
	 * @param  array $vars Public query variables
	 * @return array       Updated public query variables
	 */
	public function add_query_vars( array $vars ) {

		$filters = array_keys( $this->args['admin_filters'] );

		return array_merge( $vars, $filters );

	}

	/**
	 * Filter posts by our custom admin filters.
	 *
	 * @param WP_Query $wp_query Looks a bit like a `WP_Query` object
	 */
	public function maybe_filter( WP_Query $wp_query ) {

		if ( empty( $wp_query->query['post_type'] ) || ! in_array( $this->cpt->post_type, (array) $wp_query->query['post_type'] ) ) {
			return;
		}

		$vars = Extended_CPT::get_filter_vars( $wp_query->query, $this->cpt->args['admin_filters'] );

		if ( empty( $vars ) ) {
			return;
		}

		foreach ( $vars as $key => $value ) {
			if ( is_array( $value ) ) {
				$query = $wp_query->get( $key );
				if ( empty( $query ) ) {
					$query = array();
				}
				$value = array_merge( $query, $value );
			}
			$wp_query->set( $key, $value );
		}

	}

	/**
	 * Set the relevant query vars for sorting posts by our admin sortables.
	 *
	 * @param WP_Query $wp_query The current `WP_Query` object.
	 */
	public function maybe_sort_by_fields( WP_Query $wp_query ) {

		if ( empty( $wp_query->query['post_type'] ) || ! in_array( $this->cpt->post_type, (array) $wp_query->query['post_type'] ) ) {
			return;
		}

		$sort = Extended_CPT::get_sort_field_vars( $wp_query->query, $this->cpt->args['admin_cols'] );

		if ( empty( $sort ) ) {
			return;
		}

		foreach ( $sort as $key => $value ) {
			$wp_query->set( $key, $value );
		}

	}

	/**
	 * Filter the query's SQL clauses so we can sort posts by taxonomy terms.
	 *
	 * @param  array    $clauses  The current query's SQL clauses.
	 * @param  WP_Query $wp_query The current `WP_Query` object.
	 * @return array              The updated SQL clauses.
	 */
	public function maybe_sort_by_taxonomy( array $clauses, WP_Query $wp_query ) {

		if ( empty( $wp_query->query['post_type'] ) || ! in_array( $this->cpt->post_type, (array) $wp_query->query['post_type'] ) ) {
			return $clauses;
		}

		$sort = Extended_CPT::get_sort_taxonomy_clauses( $clauses, $wp_query->query, $this->cpt->args['admin_cols'] );

		if ( empty( $sort ) ) {
			return $clauses;
		}

		return array_merge( $clauses, $sort );

	}

	/**
	 * Add our post type to the 'At a Glance' widget on the WordPress 3.8+ dashboard.
	 *
	 * @param  array $items Array of items to display on the widget.
	 * @return array        Updated array of items.
	 */
	public function glance_items( array $items ) {

		$pto = get_post_type_object( $this->cpt->post_type );

		if ( ! current_user_can( $pto->cap->edit_posts ) ) {
			return $items;
		}
		if ( $pto->_builtin ) {
			return $items;
		}

		# Get the labels and format the counts:
		$count = wp_count_posts( $this->cpt->post_type );
		$text  = self::n( $pto->labels->singular_name, $pto->labels->name, $count->publish );
		$num   = number_format_i18n( $count->publish );

		# This is absolutely not localisable. WordPress 3.8 didn't add a new post type label.
		$url = add_query_arg( [
			'post_type' => $this->cpt->post_type,
		], admin_url( 'edit.php' ) );
		$text = '<a href="' . esc_url( $url ) . '">' . esc_html( $num . ' ' . $text ) . '</a>';

		# Go!
		$items[] = $text;

		return $items;

	}

	/**
	 * Add our post type updated messages.
	 *
	 * The messages are as follows:
	 *
	 *   1 => "Post updated. {View Post}"
	 *   2 => "Custom field updated."
	 *   3 => "Custom field deleted."
	 *   4 => "Post updated."
	 *   5 => "Post restored to revision from [date]."
	 *   6 => "Post published. {View post}"
	 *   7 => "Post saved."
	 *   8 => "Post submitted. {Preview post}"
	 *   9 => "Post scheduled for: [date]. {Preview post}"
	 *  10 => "Post draft updated. {Preview post}"
	 *
	 * @param  array $messages An associative array of post updated messages with post type as keys.
	 * @return array           Updated array of post updated messages.
	 */
	public function post_updated_messages( array $messages ) {

		global $post;

		$pto = get_post_type_object( $this->cpt->post_type );

		$messages[ $this->cpt->post_type ] = array(
			1 => sprintf(
				( $pto->publicly_queryable ? '%1$s updated. <a href="%2$s">View %3$s</a>' : '%1$s updated.' ),
				esc_html( $this->cpt->post_singular ),
				esc_url( get_permalink( $post ) ),
				esc_html( $this->cpt->post_singular_low )
			),
			2 => 'Custom field updated.',
			3 => 'Custom field deleted.',
			4 => sprintf(
				'%s updated.',
				esc_html( $this->cpt->post_singular )
			),
			5 => isset( $_GET['revision'] ) ? sprintf(
				'%1$s restored to revision from %2$s',
				esc_html( $this->cpt->post_singular ),
				wp_post_revision_title( intval( $_GET['revision'] ), false )
			) : false,
			6 => sprintf(
				( $pto->publicly_queryable ? '%1$s published. <a href="%2$s">View %3$s</a>' : '%1$s published.' ),
				esc_html( $this->cpt->post_singular ),
				esc_url( get_permalink( $post ) ),
				esc_html( $this->cpt->post_singular_low )
			),
			7 => sprintf(
				'%s saved.',
				esc_html( $this->cpt->post_singular )
			),
			8 => sprintf(
				( $pto->publicly_queryable ? '%1$s submitted. <a target="_blank" href="%2$s">Preview %3$s</a>' : '%1$s submitted.' ),
				esc_html( $this->cpt->post_singular ),
				esc_url( add_query_arg( 'preview', 'true', get_permalink( $post ) ) ),
				esc_html( $this->cpt->post_singular_low )
			),
			9 => sprintf(
				( $pto->publicly_queryable ? '%1$s scheduled for: <strong>%2$s</strong>. <a target="_blank" href="%3$s">Preview %4$s</a>' : '%1$s scheduled for: <strong>%2$s</strong>.' ),
				esc_html( $this->cpt->post_singular ),
				esc_html( date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) ) ),
				esc_url( get_permalink( $post ) ),
				esc_html( $this->cpt->post_singular_low )
			),
			10 => sprintf(
				( $pto->publicly_queryable ? '%1$s draft updated. <a target="_blank" href="%2$s">Preview %3$s</a>' : '%1$s draft updated.' ),
				esc_html( $this->cpt->post_singular ),
				esc_url( add_query_arg( 'preview', 'true', get_permalink( $post ) ) ),
				esc_html( $this->cpt->post_singular_low )
			),
		);

		return $messages;

	}

	/**
	 * Add our bulk post type updated messages.
	 *
	 * The messages are as follows:
	 *
	 *  - updated   => "Post updated." | "[n] posts updated."
	 *  - locked    => "Post not updated, somebody is editing it." | "[n] posts not updated, somebody is editing them."
	 *  - deleted   => "Post permanently deleted." | "[n] posts permanently deleted."
	 *  - trashed   => "Post moved to the trash." | "[n] posts moved to the trash."
	 *  - untrashed => "Post restored from the trash." | "[n] posts restored from the trash."
	 *
	 * @param  array $messages An associative array of bulk post updated messages with post type as keys.
	 * @param  array $counts   An array of counts for each key in `$messages`.
	 * @return array           Updated array of bulk post updated messages.
	 */
	public function bulk_post_updated_messages( array $messages, array $counts ) {

		$messages[ $this->cpt->post_type ] = array(
			'updated' => sprintf(
				self::n( '%2$s updated.', '%1$s %3$s updated.', $counts['updated'] ),
				esc_html( number_format_i18n( $counts['updated'] ) ),
				esc_html( $this->cpt->post_singular ),
				esc_html( $this->cpt->post_plural_low )
			),
			'locked' => sprintf(
				self::n( '%2$s not updated, somebody is editing it.', '%1$s %3$s not updated, somebody is editing them.', $counts['locked'] ),
				esc_html( number_format_i18n( $counts['locked'] ) ),
				esc_html( $this->cpt->post_singular ),
				esc_html( $this->cpt->post_plural_low )
			),
			'deleted' => sprintf(
				self::n( '%2$s permanently deleted.', '%1$s %3$s permanently deleted.', $counts['deleted'] ),
				esc_html( number_format_i18n( $counts['deleted'] ) ),
				esc_html( $this->cpt->post_singular ),
				esc_html( $this->cpt->post_plural_low )
			),
			'trashed' => sprintf(
				self::n( '%2$s moved to the trash.', '%1$s %3$s moved to the trash.', $counts['trashed'] ),
				esc_html( number_format_i18n( $counts['trashed'] ) ),
				esc_html( $this->cpt->post_singular ),
				esc_html( $this->cpt->post_plural_low )
			),
			'untrashed' => sprintf(
				self::n( '%2$s restored from the trash.', '%1$s %3$s restored from the trash.', $counts['untrashed'] ),
				esc_html( number_format_i18n( $counts['untrashed'] ) ),
				esc_html( $this->cpt->post_singular ),
				esc_html( $this->cpt->post_plural_low )
			),
		);

		return $messages;

	}

	/**
	 * Add our custom columns to the list of sortable columns.
	 *
	 * @param  array $cols Associative array of sortable columns
	 * @return array       Updated array of sortable columns
	 */
	public function sortables( array $cols ) {

		foreach ( $this->args['admin_cols'] as $id => $col ) {
			if ( ! is_array( $col ) ) {
				continue;
			}
			if ( isset( $col['sortable'] ) && ! $col['sortable'] ) {
				continue;
			}
			if ( isset( $col['meta_key'] ) || isset( $col['taxonomy'] ) || isset( $col['post_field'] ) ) {
				$cols[ $id ] = $id;
			}
		}

		return $cols;

	}

	/**
	 * Add columns to the admin screen for this post type.
	 *
	 * Each item in the `admin_cols` array is either a string name of an existing column, or an associative
	 * array of information for a custom column.
	 *
	 * Defining a custom column is easy. Just define an array which includes the column title, column
	 * type, and optional callback function. You can display columns for post meta, taxonomy terms,
	 * post fields, the featured image, and custom functions.
	 *
	 * The example below adds two columns; one which displays the value of the post's `event_type` meta
	 * key and one which lists the post's terms from the `location` taxonomy:
	 *
	 *     register_extended_post_type( 'event', array(
	 *         'admin_cols' => array(
	 *             'event_type' => array(
	 *                 'title'    => 'Event Type',
	 *                 'meta_key' => 'event_type'
	 *             ),
	 *             'event_location' => array(
	 *                 'title'    => 'Location',
	 *                 'taxonomy' => 'location'
	 *             )
	 *         )
	 *     ) );
	 *
	 * That's all you need to do. The columns will handle all the sorting and safely outputting the data
	 * (escaping text, and comma-separating taxonomy terms). No more messing about with all of those
	 * annoyingly named column filters and actions.
	 *
	 * Each item in the `admin_cols` array must contain one of the following elements which defines the column type:
	 *
	 *  - taxonomy       - The name of a taxonomy
	 *  - meta_key       - A post meta key
	 *  - post_field     - The name of a post field (eg. post_excerpt)
	 *  - featured_image - A featured image size (eg. thumbnail)
	 *  - connection     - A connection ID registered with the Posts 2 Posts plugin
	 *  - function       - The name of a callback function
	 *
	 * The value for the corresponding taxonomy terms, post meta or post field are safely escaped and output
	 * into the column, and the values are used to provide the sortable functionality for the column. For
	 * featured images, the post's featured image of that size will be displayed if there is one.
	 *
	 * There are a few optional elements:
	 *
	 *  - title - Generated from the field if not specified.
	 *  - function - The name of a callback function for the column (eg. `my_function`) which gets called
	 *    instead of the built-in function for handling that column. Note that it's not passed any parameters,
	 *    so it must use the global $post object.
	 *  - default - Specifies that the admin screen should be sorted by this column by default (instead of
	 *    sorting by post date). Value should be one of `asc` or `desc` to control the default order.
	 *  - width & height - These are only used for the `featured_image` column type and allow you to set an
	 *    explicit width and/or height on the <img> tag. Handy for downsizing the image.
	 *  - field & value - These are used for the `connection` column type and allow you to specify a
	 *    connection meta field and value from the fields argument of the connection type.
	 *  - date_format - This is used with the `meta_key` column type. The value of the meta field will be
	 *    treated as a timestamp if this is present. Unix and MySQL format timestamps are supported in the
	 *    meta value. Pass in boolean true to format the date according to the 'Date Format' setting, or pass
	 *    in a valid date formatting string (eg. `d/m/Y H:i:s`).
	 *  - cap - A capability required in order for this column to be displayed to the current user. Defaults
	 *    to null, meaning the column is shown to all users.
	 *  - sortable - A boolean value which specifies whether the column should be sortable. Defaults to true.
	 *
	 * @TODO - post_cap
	 *
	 * @TODO - link
	 *
	 * In addition to custom columns there are also columns built in to WordPress which you can
	 * use: `comments`, `date`, `title` and `author`. You can use these column names as the array value, or as the
	 * array key with a string value to change the column title. You can also pass boolean false to remove
	 * the `cb` or `title` columns, which are otherwise kept regardless.
	 *
	 * @param  array $cols Associative array of columns
	 * @return array       Updated array of columns
	 */
	public function cols( array $cols ) {

		// This function gets called multiple times, so let's cache it for efficiency:
		if ( isset( $this->the_cols ) ) {
			return $this->the_cols;
		}

		$new_cols = array();
		$keep = array(
			'cb',
			'title',
		);

		# Add existing columns we want to keep:
		foreach ( $cols as $id => $title ) {
			if ( in_array( $id, $keep ) && ! isset( $this->args['admin_cols'][ $id ] ) ) {
				$new_cols[ $id ] = $title;
			}
		}

		# Add our custom columns:
		foreach ( array_filter( $this->args['admin_cols'] ) as $id => $col ) {
			if ( is_string( $col ) && isset( $cols[ $col ] ) ) {
				# Existing (ie. built-in) column with id as the value
				$new_cols[ $col ] = $cols[ $col ];
			} else if ( is_string( $col ) && isset( $cols[ $id ] ) ) {
				# Existing (ie. built-in) column with id as the key and title as the value
				$new_cols[ $id ] = esc_html( $col );
			} else if ( 'author' === $col ) {
				# Automatic support for Co-Authors Plus plugin and special case for
				# displaying author column when the post type doesn't support 'author'
				if ( class_exists( 'coauthors_plus' ) ) {
					$k = 'coauthors';
				} else {
					$k = 'author';
				}
				$new_cols[ $k ] = esc_html__( 'Author' );
			} else if ( is_array( $col ) ) {
				if ( isset( $col['cap'] ) && ! current_user_can( $col['cap'] ) ) {
					continue;
				}
				if ( isset( $col['connection'] ) && ! function_exists( 'p2p_type' ) ) {
					continue;
				}
				if ( ! isset( $col['title'] ) ) {
					$col['title'] = $this->get_item_title( $col );
				}
				$new_cols[ $id ] = esc_html( $col['title'] );
			}
		}

		# Re-add any custom columns:
		$custom   = array_diff_key( $cols, $this->_cols );
		$new_cols = array_merge( $new_cols, $custom );

		return $this->the_cols = $new_cols;

	}

	/**
	 * Output the column data for our custom columns.
	 *
	 * @param string $col The column name
	 */
	public function col( $col ) {

		# Shorthand:
		$c = $this->args['admin_cols'];

		# We're only interested in our custom columns:
		$custom_cols = array_filter( array_keys( $c ) );

		if ( ! in_array( $col, $custom_cols ) ) {
			return;
		}

		if ( isset( $c[ $col ]['post_cap'] ) && ! current_user_can( $c[ $col ]['post_cap'], get_the_ID() ) ) {
			return;
		}

		if ( ! isset( $c[ $col ]['link'] ) ) {
			$c[ $col ]['link'] = 'list';
		}

		if ( isset( $c[ $col ]['function'] ) ) {
			call_user_func( $c[ $col ]['function'] );
		} else if ( isset( $c[ $col ]['meta_key'] ) ) {
			$this->col_post_meta( $c[ $col ]['meta_key'], $c[ $col ] );
		} else if ( isset( $c[ $col ]['taxonomy'] ) ) {
			$this->col_taxonomy( $c[ $col ]['taxonomy'], $c[ $col ] );
		} else if ( isset( $c[ $col ]['post_field'] ) ) {
			$this->col_post_field( $c[ $col ]['post_field'], $c[ $col ] );
		} else if ( isset( $c[ $col ]['featured_image'] ) ) {
			$this->col_featured_image( $c[ $col ]['featured_image'], $c[ $col ] );
		} else if ( isset( $c[ $col ]['connection'] ) ) {
			$this->col_connection( $c[ $col ]['connection'], $c[ $col ] );
		}

	}

	/**
	 * Output column data for a post meta field.
	 *
	 * @param string $meta_key The post meta key
	 * @param array  $args     Array of arguments for this field
	 */
	public function col_post_meta( $meta_key, array $args ) {

		$vals = get_post_meta( get_the_ID(), $meta_key, false );
		$echo = array();
		sort( $vals );

		if ( isset( $args['date_format'] ) ) {

			if ( true === $args['date_format'] ) {
				$args['date_format'] = get_option( 'date_format' );
			}

			foreach ( $vals as $val ) {

				if ( is_numeric( $val ) ) {
					$echo[] = date( $args['date_format'], $val );
				} else if ( ! empty( $val ) ) {
					$echo[] = mysql2date( $args['date_format'], $val );
				}
			}
		} else {

			foreach ( $vals as $val ) {

				if ( ! empty( $val ) || ( '0' === $val ) ) {
					$echo[] = $val;
				}
			}
		}

		if ( empty( $echo ) ) {
			echo '&#8212;';
		} else {
			echo esc_html( implode( ', ', $echo ) );
		}

	}

	/**
	 * Output column data for a taxonomy's term names.
	 *
	 * @param string $taxonomy The taxonomy name
	 * @param array  $args     Array of arguments for this field
	 */
	public function col_taxonomy( $taxonomy, array $args ) {

		global $post;

		$terms = get_the_terms( $post, $taxonomy );
		$tax   = get_taxonomy( $taxonomy );

		if ( is_wp_error( $terms ) ) {
			echo esc_html( $terms->get_error_message() );
			return;
		}

		if ( empty( $terms ) ) {
			echo '&#8212;';
			return;
		}

		$out = array();

		foreach ( $terms as $term ) {

			if ( $args['link'] ) {

				switch ( $args['link'] ) {
					case 'view':
						if ( $tax->public ) {
							$out[] = sprintf(
								'<a href="%1$s">%2$s</a>',
								esc_url( get_term_link( $term ) ),
								esc_html( $term->name )
							);
						} else {
							$out[] = esc_html( $term->name );
						}
						break;
					case 'edit':
						if ( current_user_can( $tax->cap->edit_terms ) ) {
							$out[] = sprintf(
								'<a href="%1$s">%2$s</a>',
								esc_url( get_edit_term_link( $term, $taxonomy, $post->post_type ) ),
								esc_html( $term->name )
							);
						} else {
							$out[] = esc_html( $term->name );
						}
						break;
					case 'list':
						$link = add_query_arg( array(
							'post_type' => $post->post_type,
							$taxonomy   => $term->slug,
						), admin_url( 'edit.php' ) );
						$out[] = sprintf(
							'<a href="%1$s">%2$s</a>',
							esc_url( $link ),
							esc_html( $term->name )
						);
						break;
				}
			} else {

				$out[] = esc_html( $term->name );

			}
		}

		echo implode( ', ', $out ); // WPCS: XSS ok.

	}

	/**
	 * Output column data for a post field.
	 *
	 * @param string $field The post field
	 * @param array  $args  Array of arguments for this field
	 */
	public function col_post_field( $field, array $args ) {

		global $post;

		switch ( $field ) {

			case 'post_date':
			case 'post_date_gmt':
			case 'post_modified':
			case 'post_modified_gmt':
				if ( '0000-00-00 00:00:00' !== get_post_field( $field, $post ) ) {
					if ( ! isset( $args['date_format'] ) ) {
						$args['date_format'] = get_option( 'date_format' );
					}
					echo esc_html( mysql2date( $args['date_format'], get_post_field( $field, $post ) ) );
				}
				break;

			case 'post_status':
				if ( $status = get_post_status_object( get_post_status( $post ) ) ) {
					echo esc_html( $status->label );
				}
				break;

			case 'post_author':
				echo esc_html( get_the_author() );
				break;

			case 'post_title':
				echo esc_html( get_the_title() );
				break;

			case 'post_excerpt':
				echo esc_html( get_the_excerpt() );
				break;

			default:
				echo esc_html( get_post_field( $field, $post ) );
				break;

		}

	}

	/**
	 * Output column data for a post's featured image.
	 *
	 * @param string $image_size The image size
	 * @param array  $args       Array of `width` and `height` attributes for the image
	 */
	public function col_featured_image( $image_size, array $args ) {

		if ( ! function_exists( 'has_post_thumbnail' ) ) {
			return;
		}

		if ( isset( $args['width'] ) ) {
			$width = is_numeric( $args['width'] ) ? sprintf( '%dpx', $args['width'] ) : $args['width'];
		} else {
			$width = 'auto';
		}

		if ( isset( $args['height'] ) ) {
			$height = is_numeric( $args['height'] ) ? sprintf( '%dpx', $args['height'] ) : $args['height'];
		} else {
			$height = 'auto';
		}

		$image_atts = array(
			'style' => esc_attr( sprintf(
				'width:%1$s;height:%2$s',
				$width,
				$height
			) ),
			'title' => '',
		);

		if ( has_post_thumbnail() ) {
			the_post_thumbnail( $image_size, $image_atts );
		}

	}

	/**
	 * Output column data for a Posts 2 Posts connection.
	 *
	 * @param string $connection The ID of the connection type
	 * @param array  $args       Array of arguments for a given connection type
	 */
	public function col_connection( $connection, array $args ) {

		global $post, $wp_query;

		if ( ! function_exists( 'p2p_type' ) ) {
			return;
		}

		if ( ! $this->p2p_connection_exists( $connection ) ) {
			echo esc_html( sprintf(
				__( 'Invalid connection type: %s', 'extended-cpts' ),
				$connection
			) );
			return;
		}

		$_post = $post;
		$meta  = $out = array();
		$field = 'connected_' . $connection;

		if ( isset( $args['field'] ) && isset( $args['value'] ) ) {
			$meta = array(
				'connected_meta' => array(
					$args['field'] => $args['value'],
				),
			);
			$field .= sanitize_title( '_' . $args['field'] . '_' . $args['value'] );
		}

		if ( ! isset( $_post->$field ) ) {
			if ( $type = p2p_type( $connection ) ) {
				$type->each_connected( array( $_post ), $meta, $field );
			} else {
				echo esc_html( sprintf(
					__( 'Invalid connection type: %s', 'extended-cpts' ),
					$connection
				) );
				return;
			}
		}

		foreach ( $_post->$field as $post ) {

			setup_postdata( $post );

			$pto = get_post_type_object( $post->post_type );
			$pso = get_post_status_object( $post->post_status );

			if ( $pso->protected && ! current_user_can( 'edit_post', $post->ID ) ) {
				continue;
			}
			if ( 'trash' === $post->post_status ) {
				continue;
			}

			if ( $args['link'] ) {

				switch ( $args['link'] ) {
					case 'view':

						if ( $pto->public ) {
							if ( $pso->protected ) {
								$out[] = sprintf(
									'<a href="%1$s">%2$s</a>',
									esc_url( get_preview_post_link() ),
									esc_html( get_the_title() )
								);
							} else {
								$out[] = sprintf(
									'<a href="%1$s">%2$s</a>',
									esc_url( get_permalink() ),
									esc_html( get_the_title() )
								);
							}
						} else {
							$out[] = esc_html( get_the_title() );
						}

						break;
					case 'edit':
						if ( current_user_can( 'edit_post', $post->ID ) ) {
							$out[] = sprintf(
								'<a href="%1$s">%2$s</a>',
								esc_url( get_edit_post_link() ),
								esc_html( get_the_title() )
							);
						} else {
							$out[] = esc_html( get_the_title() );
						}
						break;
					case 'list':
						$link = add_query_arg( array_merge( array(
							'post_type'       => $_post->post_type,
							'connected_type'  => $connection,
							'connected_items' => $post->ID,
						), $meta ), admin_url( 'edit.php' ) );
						$out[] = sprintf(
							'<a href="%1$s">%2$s</a>',
							esc_url( $link ),
							esc_html( get_the_title() )
						);
						break;
				}
			} else {

				$out[] = esc_html( get_the_title() );

			}
		}

		$post = $_post; // WPCS: override ok.

		echo implode( ', ', $out ); // WPCS: XSS ok.

	}

	/**
	 * Removes the Quick Edit link from the post row actions.
	 *
	 * @param  array   $actions Array of post actions
	 * @param  WP_Post $post    The current post object
	 * @return array            Array of updated post actions
	 */
	public function remove_quick_edit_action( array $actions, WP_Post $post ) {

		if ( $this->cpt->post_type !== $post->post_type ) {
			return $actions;
		}

		unset( $actions['inline'], $actions['inline hide-if-no-js'] );
		return $actions;

	}

	/**
	 * Removes the Quick Edit link from the bulk actions menu.
	 *
	 * @param  array $actions Array of bulk actions
	 * @return array          Array of updated bulk actions
	 */
	public function remove_quick_edit_menu( array $actions ) {

		unset( $actions['edit'] );
		return $actions;

	}

	/**
	 * Logs the default columns so we don't remove any custom columns added by other plugins.
	 *
	 * @param  array $cols The default columns for this post type screen
	 * @return array       The default columns for this post type screen
	 */
	public function _log_default_cols( array $cols ) {

		return $this->_cols = $cols;

	}

	/**
	 * A non-localised version of _n()
	 *
	 * @param  string $single The text that will be used if $number is 1
	 * @param  string $plural The text that will be used if $number is not 1
	 * @param  int    $number The number to compare against to use either `$single` or `$plural`
	 * @return string         Either `$single` or `$plural` text
	 */
	protected static function n( $single, $plural, $number ) {

		return ( 1 === intval( $number ) ) ? $single : $plural;

	}

	/**
	 * Get a sensible title for the current item (usually the arguments array for a column)
	 *
	 * @param  array  $item An array of arguments
	 * @return string       The item title
	 */
	protected function get_item_title( array $item ) {

		if ( isset( $item['taxonomy'] ) ) {
			if ( $tax = get_taxonomy( $item['taxonomy'] ) ) {
				if ( ! empty( $tax->exclusive ) ) {
					return $tax->labels->singular_name;
				} else {
					return $tax->labels->name;
				}
			} else {
				return $item['taxonomy'];
			}
		} else if ( isset( $item['post_field'] ) ) {
			return ucwords( trim( str_replace( array( 'post_', '_' ), ' ', $item['post_field'] ) ) );
		} else if ( isset( $item['meta_key'] ) ) {
			return ucwords( trim( str_replace( array( '_', '-' ), ' ', $item['meta_key'] ) ) );
		} else if ( isset( $item['connection'] ) && isset( $item['value'] ) ) {
			return ucwords( trim( str_replace( array( '_', '-' ), ' ', $item['value'] ) ) );
		} else if ( isset( $item['connection'] ) ) {
			if ( function_exists( 'p2p_type' ) && $this->p2p_connection_exists( $item['connection'] ) ) {
				if ( $ctype = p2p_type( $item['connection'] ) ) {
					$other = ( 'from' === $ctype->direction_from_types( 'post', $this->cpt->post_type ) ) ? 'to' : 'from';
					return $ctype->side[ $other ]->get_title();
				}
			}
			return $item['connection'];
		}

	}

	/**
	 * Check if a certain Posts 2 Posts connection exists.
	 *
	 * This is just a caching wrapper for `p2p_connection_exists()`, which performs a
	 * database query on every call.
	 *
	 * @param string $connection A connection type.
	 * @return bool Whether the connection exists.
	 */
	protected function p2p_connection_exists( $connection ) {
		if ( ! isset( $this->connection_exists[ $connection ] ) ) {
			$this->connection_exists[ $connection ] = p2p_connection_exists( $connection );
		}
		return $this->connection_exists[ $connection ];
	}

}
}

if ( ! class_exists( 'Extended_Rewrite_Testing' ) ) {
/**
 * @codeCoverageIgnore
 */
abstract class Extended_Rewrite_Testing {

	abstract public function get_tests();

	public function get_rewrites( array $struct, array $additional ) {

		global $wp_rewrite;

		if ( ! $wp_rewrite->using_permalinks() ) {
			return array();
		}

		$new   = array();
		$rules = $wp_rewrite->generate_rewrite_rules(
			$struct['struct'],
			$struct['ep_mask'],
			$struct['paged'],
			$struct['feed'],
			$struct['forcomments'],
			$struct['walk_dirs'],
			$struct['endpoints']
		);
		$rules = array_merge( $rules, $additional );
		$feedregex = implode( '|', $wp_rewrite->feeds );
		$replace   = array(
			'(.+?)'          => 'hello',
			'.+?'            => 'hello',
			'([^/]+)'        => 'world',
			'[^/]+'          => 'world',
			'(?:/([0-9]+))?' => '/456',
			'([0-9]{4})'     => date( 'Y' ),
			'[0-9]{4}'       => date( 'Y' ),
			'([0-9]{1,2})'   => date( 'm' ),
			'[0-9]{1,2}'     => date( 'm' ),
			'([0-9]{1,})'    => '123',
			'[0-9]{1,}'      => '789',
			'([0-9]+)'       => date( 'd' ),
			'[0-9]+'         => date( 'd' ),
			"({$feedregex})" => end( $wp_rewrite->feeds ),
			'/?'             => '/',
			'$'              => '',
		);

		foreach ( $rules as $regex => $result ) {
			$regex  = str_replace( array_keys( $replace ), $replace, $regex );
			// Change '$2' to '$matches[2]'
			$result = preg_replace( '/\$([0-9]+)/', '\$matches[$1]', $result );
			$new[ "/{$regex}" ] = $result;
			if ( false !== strpos( $regex, $replace['(?:/([0-9]+))?'] ) ) {
				// Add an extra rule for this optional block
				$regex = str_replace( $replace['(?:/([0-9]+))?'], '', $regex );
				$new[ "/{$regex}" ] = $result;
			}
		}

		return $new;

	}

}
}

if ( ! class_exists( 'Extended_CPT_Rewrite_Testing' ) ) {
/**
 * @codeCoverageIgnore
 */
class Extended_CPT_Rewrite_Testing extends Extended_Rewrite_Testing {

	public $cpt;

	public function __construct( Extended_CPT $cpt ) {
		$this->cpt = $cpt;
	}

	public function get_tests() {

		global $wp_rewrite;

		if ( ! $wp_rewrite->using_permalinks() ) {
			return array();
		}

		if ( ! isset( $wp_rewrite->extra_permastructs[ $this->cpt->post_type ] ) ) {
			return array();
		}

		$struct     = $wp_rewrite->extra_permastructs[ $this->cpt->post_type ];
		$pto        = get_post_type_object( $this->cpt->post_type );
		$name       = sprintf( '%s (%s)', $pto->labels->name, $this->cpt->post_type );
		$additional = array();

		// Post type archive rewrites are generated separately. See the `has_archive` handling in `register_post_type()`.
		if ( $pto->has_archive ) {
			$archive_slug = ( $pto->has_archive === true ) ? $pto->rewrite['slug'] : $pto->has_archive;

			if ( $pto->rewrite['with_front'] ) {
				$archive_slug = substr( $wp_rewrite->front, 1 ) . $archive_slug;
			} else {
				$archive_slug = $wp_rewrite->root . $archive_slug;
			}

			$additional[ "{$archive_slug}/?$" ] = "index.php?post_type={$this->cpt->post_type}";

			if ( $pto->rewrite['feeds'] && $wp_rewrite->feeds ) {
				$feeds = '(' . trim( implode( '|', $wp_rewrite->feeds ) ) . ')';
				$additional[ "{$archive_slug}/feed/{$feeds}/?$" ] = "index.php?post_type={$this->cpt->post_type}" . '&feed=$matches[1]';
				$additional[ "{$archive_slug}/{$feeds}/?$" ] = "index.php?post_type={$this->cpt->post_type}" . '&feed=$matches[1]';
			}
			if ( $pto->rewrite['pages'] ) {
				$additional[ "{$archive_slug}/{$wp_rewrite->pagination_base}/([0-9]{1,})/?$" ] = "index.php?post_type={$this->cpt->post_type}" . '&paged=$matches[1]';
			}
		}

		return array(
			$name => $this->get_rewrites( $struct, $additional ),
		);

	}

}
}
