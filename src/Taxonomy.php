<?php
declare( strict_types=1 );

namespace ExtCPTs;

class Taxonomy {

	/**
	 * Default arguments for custom taxonomies.
	 * Several of these differ from the defaults in WordPress' register_taxonomy() function.
	 *
	 * @var array<string,mixed>
	 */
	protected array $defaults = [
		'public'          => true,
		'show_ui'         => true,
		'hierarchical'    => true,
		'query_var'       => true,
		'exclusive'       => false, # Custom arg
		'allow_hierarchy' => false, # Custom arg
	];

	public string $taxonomy;

	/**
	 * @var array<int,string>
	 */
	public array $object_type;

	public string $tax_slug;

	public string $tax_singular;

	public string $tax_plural;

	public string $tax_singular_low;

	public string $tax_plural_low;

	/**
	 * @var array<string,mixed>
	 */
	public array $args;

	/**
	 * Class constructor.
	 *
	 * @see register_extended_taxonomy()
	 *
	 * @param string               $taxonomy    The taxonomy name.
	 * @param array<int,string>    $object_type Names of the object types for the taxonomy.
	 * @param array<string,mixed>  $args        Optional. The taxonomy arguments.
	 * @param array<string,string> $names       Optional. An associative array of the plural, singular, and slug names.
	 */
	public function __construct( string $taxonomy, array $object_type, array $args = [], array $names = [] ) {
		/**
		 * Filter the arguments for a taxonomy.
		 *
		 * @since 4.4.1
		 *
		 * @param array<string,mixed> $args     The taxonomy arguments.
		 * @param string              $taxonomy The taxonomy name.
		 */
		$args = apply_filters( 'ext-taxos/args', $args, $taxonomy );

		/**
		 * Filter the arguments for this taxonomy.
		 *
		 * @since 2.0.0
		 *
		 * @param array<string,mixed> $args The taxonomy arguments.
		 */
		$args = apply_filters( "ext-taxos/{$taxonomy}/args", $args );

		/**
		 * Filter the plural, singular, and slug for a taxonomy.
		 *
		 * @since 4.4.1
		 *
		 * @param array<string,string> $names    The plural, singular, and slug names (if any were specified).
		 * @param string               $taxonomy The taxonomy name.
		 */
		$names = apply_filters( 'ext-taxos/names', $names, $taxonomy );

		/**
		 * Filter the plural, singular, and slug for this taxonomy.
		 *
		 * @since 2.0.0
		 *
		 * @param array<string,string> $names The plural, singular, and slug names (if any were specified).
		 */
		$names = apply_filters( "ext-taxos/{$taxonomy}/names", $names );

		if ( isset( $names['singular'] ) ) {
			$this->tax_singular = $names['singular'];
		} else {
			$this->tax_singular = ucwords( str_replace( [ '-', '_' ], ' ', $taxonomy ) );
		}

		if ( isset( $names['slug'] ) ) {
			$this->tax_slug = $names['slug'];
		} elseif ( isset( $names['plural'] ) ) {
			$this->tax_slug = $names['plural'];
		} else {
			$this->tax_slug = $taxonomy . 's';
		}

		if ( isset( $names['plural'] ) ) {
			$this->tax_plural = $names['plural'];
		} else {
			$this->tax_plural = ucwords( str_replace( [ '-', '_' ], ' ', $this->tax_slug ) );
		}

		$this->object_type = $object_type;
		$this->taxonomy = strtolower( $taxonomy );
		$this->tax_slug = strtolower( $this->tax_slug );

		# Build our base taxonomy names:
		# Lower-casing is not forced if the name looks like an initialism, eg. FAQ.
		if ( ! preg_match( '/[A-Z]{2,}/', $this->tax_singular ) ) {
			$this->tax_singular_low = strtolower( $this->tax_singular );
		} else {
			$this->tax_singular_low = $this->tax_singular;
		}

		if ( ! preg_match( '/[A-Z]{2,}/', $this->tax_plural ) ) {
			$this->tax_plural_low = strtolower( $this->tax_plural );
		} else {
			$this->tax_plural_low = $this->tax_plural;
		}

		# Build our labels:
		$this->defaults['labels'] = [
			'menu_name'                  => $this->tax_plural,
			'name'                       => $this->tax_plural,
			'singular_name'              => $this->tax_singular,
			'name_admin_bar'             => $this->tax_singular,
			'search_items'               => sprintf( 'Search %s', $this->tax_plural ),
			'popular_items'              => sprintf( 'Popular %s', $this->tax_plural ),
			'all_items'                  => sprintf( 'All %s', $this->tax_plural ),
			'archives'                   => sprintf( '%s Archives', $this->tax_plural ),
			'parent_item'                => sprintf( 'Parent %s', $this->tax_singular ),
			'parent_item_colon'          => sprintf( 'Parent %s:', $this->tax_singular ),
			'edit_item'                  => sprintf( 'Edit %s', $this->tax_singular ),
			'view_item'                  => sprintf( 'View %s', $this->tax_singular ),
			'update_item'                => sprintf( 'Update %s', $this->tax_singular ),
			'add_new_item'               => sprintf( 'Add New %s', $this->tax_singular ),
			'new_item_name'              => sprintf( 'New %s Name', $this->tax_singular ),
			'separate_items_with_commas' => sprintf( 'Separate %s with commas', $this->tax_plural_low ),
			'add_or_remove_items'        => sprintf( 'Add or remove %s', $this->tax_plural_low ),
			'choose_from_most_used'      => sprintf( 'Choose from most used %s', $this->tax_plural_low ),
			'not_found'                  => sprintf( 'No %s found', $this->tax_plural_low ),
			'no_terms'                   => sprintf( 'No %s', $this->tax_plural_low ),
			'filter_by_item'             => sprintf( 'Filter by %s', $this->tax_singular_low ),
			'items_list_navigation'      => sprintf( '%s list navigation', $this->tax_plural ),
			'items_list'                 => sprintf( '%s list', $this->tax_plural ),
			'most_used'                  => 'Most Used',
			'back_to_items'              => sprintf( '&larr; Back to %s', $this->tax_plural ),
			'item_link'                  => sprintf( '%s Link', $this->tax_singular ),
			'item_link_description'      => sprintf( 'A link to a %s.', $this->tax_singular_low ),
			'no_item'                    => sprintf( 'No %s', $this->tax_singular_low ), # Custom label
			'filter_by'                  => sprintf( 'Filter by %s', $this->tax_singular_low ), # Custom label
		];

		# Only set rewrites if we need them
		if ( isset( $args['public'] ) && ! $args['public'] ) {
			$this->defaults['rewrite'] = false;
		} else {
			$this->defaults['rewrite'] = [
				'slug'         => $this->tax_slug,
				'with_front'   => false,
				'hierarchical' => isset( $args['allow_hierarchy'] ) ? $args['allow_hierarchy'] : $this->defaults['allow_hierarchy'],
			];
		}

		# Merge our args with the defaults:
		$this->args = array_merge( $this->defaults, $args );

		# This allows the 'labels' arg to contain some, none or all labels:
		if ( isset( $args['labels'] ) ) {
			$this->args['labels'] = array_merge( $this->defaults['labels'], $args['labels'] );
		}
	}

	/**
	 * Initialise the taxonomy by adding the necessary actions and filters.
	 */
	public function init(): void {
		# Rewrite testing:
		if ( $this->args['rewrite'] ) {
			add_filter( 'rewrite_testing_tests', [ $this, 'rewrite_testing_tests' ], 1 );
		}

		$existing = get_taxonomy( $this->taxonomy );

		if ( empty( $existing ) ) {
			# Register taxonomy:
			$this->register_taxonomy();
		} else {
			$this->extend( $existing );
		}

		/**
		 * Fired when the extended taxonomy instance is set up.
		 *
		 * @since 4.0.0
		 *
		 * @param \ExtCPTs\Taxonomy $instance The extended taxonomy instance.
		 */
		do_action( "ext-taxos/{$this->taxonomy}/instance", $this );
	}

	/**
	 * Extends an existing taxonomy object. Currently only handles labels.
	 *
	 * @param WP_Taxonomy $taxonomy A taxonomy object.
	 */
	public function extend( WP_Taxonomy $taxonomy ) {
		# Merge core with overridden labels
		$this->args['labels'] = array_merge( (array) get_taxonomy_labels( $taxonomy ), $this->args['labels'] );

		$GLOBALS['wp_taxonomies'][ $taxonomy->name ]->labels = (object) $this->args['labels'];
	}

	/**
	 * Add our rewrite tests to the Rewrite Rule Testing tests array.
	 *
	 * @codeCoverageIgnore
	 *
	 * @param array<string,array<string,string>> $tests The existing rewrite rule tests.
	 * @return array<string,array<string,string>> Updated rewrite rule tests.
	 */
	public function rewrite_testing_tests( array $tests ): array {
		require_once __DIR__ . '/ExtendedRewriteTesting.php';
		require_once __DIR__ . '/TaxonomyRewriteTesting.php';

		$extended = new TaxonomyRewriteTesting( $this );

		return array_merge( $tests, $extended->get_tests() );
	}

	/**
	 * Registers our taxonomy.
	 */
	public function register_taxonomy(): void {
		if ( true === $this->args['query_var'] ) {
			$query_var = $this->taxonomy;
		} else {
			$query_var = $this->args['query_var'];
		}

		$post_types = get_post_types(
			[
				'query_var' => $query_var,
			]
		);

		if ( $query_var && count( $post_types ) ) {
			trigger_error(
				esc_html(
					sprintf(
						/* translators: %s: Taxonomy query variable name */
						__( 'Taxonomy query var "%s" clashes with a post type query var of the same name', 'extended-cpts' ),
						$query_var
					)
				),
				E_USER_ERROR
			);
		} elseif ( in_array( $query_var, [ 'type', 'tab' ], true ) ) {
			trigger_error(
				esc_html(
					sprintf(
						/* translators: %s: Taxonomy query variable name */
						__( 'Taxonomy query var "%s" is not allowed', 'extended-cpts' ),
						$query_var
					)
				),
				E_USER_ERROR
			);
		} else {
			register_taxonomy( $this->taxonomy, $this->object_type, $this->args );
		}
	}

}
