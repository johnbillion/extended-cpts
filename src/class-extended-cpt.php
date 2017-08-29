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

class Extended_CPT {

	/**
	 * Default arguments for custom post types.
	 *
	 * The arguments listed are the ones which differ from the defaults in `register_post_type()`.
	 *
	 * @var array
	 */
	protected $defaults = [
		'public'          => true,
		'menu_position'   => 6,
		'capability_type' => 'page',
		'hierarchical'    => true,
		'supports'        => [
			'title',
			'editor',
			'thumbnail',
		],
		'site_filters'    => null,  # Custom arg
		'site_sortables'  => null,  # Custom arg
		'show_in_feed'    => false, # Custom arg
		'archive'         => null,  # Custom arg
		'featured_image'  => null,  # Custom arg
	];

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
	 * @param string   $post_type The post type name.
	 * @param array    $args      Optional. The post type arguments.
	 * @param string[] $names     Optional. The plural, singular, and slug names.
	 */
	public function __construct( string $post_type, array $args = [], array $names = [] ) {

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
		 * @param string[] $names The plural, singular, and slug names (if any were specified).
		 */
		$names = apply_filters( "ext-cpts/{$post_type}/names", $names );

		if ( isset( $names['singular'] ) ) {
			$this->post_singular = $names['singular'];
		} else {
			$this->post_singular = ucwords( str_replace( [
				'-',
				'_',
			], ' ', $post_type ) );
		}

		if ( isset( $names['slug'] ) ) {
			$this->post_slug = $names['slug'];
		} elseif ( isset( $names['plural'] ) ) {
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
		$this->defaults['labels'] = [
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
		];

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
			$this->defaults['rewrite'] = [
				'slug'       => $this->post_slug,
				'with_front' => false,
			];
		}

		# Merge our args with the defaults:
		$this->args = array_merge( $this->defaults, $args );

		# This allows the 'labels' and 'rewrite' args to contain all, some, or no values:
		foreach ( [ 'labels', 'rewrite' ] as $arg ) {
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
			add_filter( 'pre_get_posts', [ $this, 'maybe_sort_by_fields' ] );
			add_filter( 'posts_clauses', [ $this, 'maybe_sort_by_taxonomy' ], 10, 2 );
		}

		# Front-end filters:
		if ( $this->args['site_filters'] && ! is_admin() ) {
			add_action( 'pre_get_posts', [ $this, 'maybe_filter' ] );
			add_filter( 'query_vars',    [ $this, 'add_query_vars' ] );
		}

		# Post type in the site's main feed:
		if ( $this->args['show_in_feed'] ) {
			add_filter( 'request', [ $this, 'add_to_feed' ] );
		}

		# Post type archive query vars:
		if ( $this->args['archive'] && ! is_admin() ) {
			add_filter( 'parse_request', [ $this, 'override_private_query_vars' ], 1 );
		}

		# Custom post type permastruct:
		if ( $this->args['rewrite'] && ! empty( $this->args['rewrite']['permastruct'] ) ) {
			add_action( 'registered_post_type', [ $this, 'registered_post_type' ], 1, 2 );
			add_filter( 'post_type_link',       [ $this, 'post_type_link' ], 1, 4 );
		}

		# Rewrite testing:
		if ( $this->args['rewrite'] ) {
			add_filter( 'rewrite_testing_tests', [ $this, 'rewrite_testing_tests' ], 1 );
		}

		# Register post type:
		$this->register_post_type();

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

		if ( empty( $wp_query->query['post_type'] ) || ! in_array( $this->post_type, (array) $wp_query->query['post_type'], true ) ) {
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
					$query = [];
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

		if ( empty( $wp_query->query['post_type'] ) || ! in_array( $this->post_type, (array) $wp_query->query['post_type'], true ) ) {
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
	public function maybe_sort_by_taxonomy( array $clauses, WP_Query $wp_query ) : array {

		if ( empty( $wp_query->query['post_type'] ) || ! in_array( $this->post_type, (array) $wp_query->query['post_type'], true ) ) {
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
	public static function get_filter_vars( array $query, array $filters ) : array {

		$return = [];

		foreach ( $filters as $filter_key => $filter ) {

			if ( ! isset( $query[ $filter_key ] ) || ( '' === $query[ $filter_key ] ) ) {
				continue;
			}
			if ( isset( $filter['cap'] ) && ! current_user_can( $filter['cap'] ) ) {
				continue;
			}

			if ( isset( $filter['meta_key'] ) ) {
				$meta_query = [
					'key'   => $filter['meta_key'],
					'value' => wp_unslash( $query[ $filter_key ] ),
				];
			} elseif ( isset( $filter['meta_search_key'] ) ) {
				$meta_query = [
					'key'     => $filter['meta_search_key'],
					'value'   => wp_unslash( $query[ $filter_key ] ),
					'compare' => 'LIKE',
				];
			} elseif ( isset( $filter['meta_exists'] ) ) {
				$meta_query = [
					'key'     => wp_unslash( $query[ $filter_key ] ),
					'compare' => 'NOT IN',
					'value'   => [ '', '0', 'false', 'null' ],
				];
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
	public static function get_sort_field_vars( array $vars, array $sortables ) : array {

		if ( ! isset( $vars['orderby'] ) ) {
			return [];
		}
		if ( ! isset( $sortables[ $vars['orderby'] ] ) ) {
			return [];
		}

		$orderby = $sortables[ $vars['orderby'] ];

		if ( ! is_array( $orderby ) ) {
			return [];
		}
		if ( isset( $orderby['sortable'] ) && ! $orderby['sortable'] ) {
			return [];
		}

		$return = [];

		if ( isset( $orderby['meta_key'] ) ) {
			$return['meta_key'] = $orderby['meta_key'];
			$return['orderby']  = 'meta_value';
			// @TODO meta_value_num
		} elseif ( isset( $orderby['post_field'] ) ) {
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
	public static function get_sort_taxonomy_clauses( array $clauses, array $vars, array $sortables ) : array {

		global $wpdb;

		if ( ! isset( $vars['orderby'] ) ) {
			return [];
		}
		if ( ! isset( $sortables[ $vars['orderby'] ] ) ) {
			return [];
		}

		$orderby = $sortables[ $vars['orderby'] ];

		if ( ! is_array( $orderby ) ) {
			return [];
		}
		if ( isset( $orderby['sortable'] ) && ! $orderby['sortable'] ) {
			return [];
		}
		if ( ! isset( $orderby['taxonomy'] ) ) {
			return [];
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
	public function add_query_vars( array $vars ) : array {

		$filters = array_keys( $this->args['site_filters'] );

		return array_merge( $vars, $filters );

	}

	/**
	 * Add our post type to the feed.
	 *
	 * @param  array $vars Request parameters.
	 * @return array       Updated request parameters.
	 */
	public function add_to_feed( array $vars ) : array {

		# If it's not a feed, we're not interested:
		if ( ! isset( $vars['feed'] ) ) {
			return $vars;
		}

		if ( ! isset( $vars['post_type'] ) ) {
			$vars['post_type'] = [
				'post',
				$this->post_type,
			];
		} elseif ( is_array( $vars['post_type'] ) && ( count( $vars['post_type'] ) > 1 ) ) {
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
	public function override_private_query_vars( WP $wp ) : WP {

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
	 * @param string       $post_type        Post type name.
	 * @param WP_Post_Type $post_type_object Post type object.
	 */
	public function registered_post_type( string $post_type, WP_Post_Type $post_type_object ) {
		if ( $post_type !== $this->post_type ) {
			return;
		}
		$struct = str_replace( "%{$this->post_type}_slug%", $this->post_slug, $post_type_object->rewrite['permastruct'] );
		$struct = str_replace( '%postname%', "%{$this->post_type}%", $struct );
		add_permastruct( $this->post_type, $struct, $post_type_object->rewrite );
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
	public function post_type_link( string $post_link, WP_Post $post, bool $leavename, bool $sample ) : string {

		# If it's not our post type, bail out:
		if ( $this->post_type !== $post->post_type ) {
			return $post_link;
		}

		$date = explode( ' ', mysql2date( 'Y m d H i s', $post->post_date ) );
		$replacements = [
			'%year%'     => $date[0],
			'%monthnum%' => $date[1],
			'%day%'      => $date[2],
			'%hour%'     => $date[3],
			'%minute%'   => $date[4],
			'%second%'   => $date[5],
			'%post_id%'  => $post->ID,
		];

		if ( false !== strpos( $post_link, '%author%' ) ) {
			$replacements['%author%'] = get_userdata( $post->post_author )->user_nicename;
		}

		foreach ( get_object_taxonomies( $post ) as $tax ) {
			if ( false === strpos( $post_link, "%{$tax}%" ) ) {
				continue;
			}

			$terms = get_the_terms( $post, $tax );
			if ( $terms ) {

				/**
				 * Filter the term that gets used in the `$tax` permalink token.
				 * @TODO make this more betterer ^
				 *
				 * @param WP_Term   $term  The `$tax` term to use in the permalink.
				 * @param WP_Term[] $terms Array of all `$tax` terms associated with the post.
				 * @param WP_Post   $post  The post in question.
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
					$default_term = get_term( $default_term_name, $tax );
					if ( ! is_wp_error( $default_term ) ) {
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
	public function rewrite_testing_tests( array $tests ) : array {
		require_once __DIR__ . '/class-extended-rewrite-testing.php';
		require_once __DIR__ . '/class-extended-cpt-rewrite-testing.php';

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
		$taxonomies = get_taxonomies( [
			'query_var' => $query_var,
		], 'objects' );

		if ( $query_var && count( $taxonomies ) ) {
			// https://core.trac.wordpress.org/ticket/35089
			foreach ( $taxonomies as $tax ) {
				if ( $tax->query_var === $query_var ) {
					trigger_error( esc_html( sprintf(
						/* translators: %s: Post type query variable name */
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
	 * @param WP_Post_Type $pto A post type object.
	 */
	public function extend( WP_Post_Type $pto ) {

		# Merge core with overridden labels
		$this->args['labels'] = array_merge( (array) get_post_type_labels( $pto ), $this->args['labels'] );

		$GLOBALS['wp_post_types'][ $pto->name ]->labels = (object) $this->args['labels'];

	}

	/**
	 * Helper function for registering a taxonomy and adding it to this post type.
	 *
	 * Accepts the same parameters as `register_extended_taxonomy()`, minus the `$object_type` parameter.
	 *
	 * Example usage:
	 *
	 *     $events   = register_extended_post_type( 'event' );
	 *     $location = $events->add_taxonomy( 'location' );
	 *
	 * @param  string $taxonomy The taxonomy name.
	 * @param  array  $args     Optional. The taxonomy arguments.
	 * @param  array  $names    Optional. An associative array of the plural, singular, and slug names.
	 * @return WP_Taxonomy      Taxonomy object.
	 */
	public function add_taxonomy( string $taxonomy, array $args = [], array $names = [] ) : WP_Taxonomy {

		if ( taxonomy_exists( $taxonomy ) ) {
			register_taxonomy_for_object_type( $taxonomy, $this->post_type );
		} else {
			register_extended_taxonomy( $taxonomy, $this->post_type, $args, $names );
		}

		return get_taxonomy( $taxonomy );

	}

}
