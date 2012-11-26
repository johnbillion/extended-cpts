<?php
/*
Plugin Name:  Extended CPTs
Description:  Extended custom post types.
Version:      1.7.4
Author:       John Blackbourn
Author URI:   http://johnblackbourn.com

Extended CPTs started off with several features such as extended localisation, post type listings and post type listing pages. The extended localisation has since been removed, and the post type listings (aka post type archives) functionality has since been rolled into WordPress.

 * Better defaults for everything:
   - Intelligent defaults for all labels
   - Automatic support for post updated messages
   - Hierarchical by default, with page capability type
   - Drop with_front from rewrite rules
   - Support post thumbnails
 * Insanely easy custom, sortable admin columns:
   - Out of the box sorting by meta key or taxonomy
   - Specify a default sort column (great for CPTs with custom date fields)
 * Custom filter dropdowns on CPT management screens
 * Override default posts_per_page value

@TODO:

 * Look at improving the selection of fields shown in the Quick Edit boxes
 * Add the meta_key filter dropdown 

*/

class ExtendedCPT {

	private $post_type;
	private $post_slug;
	private $post_singular;
	private $post_plural;
	private $post_singular_low;
	private $post_plural_low;
	private $args;
	private $defaults = array(
		'public'              => true,
		'publicly_queryable'  => true,
		'exclude_from_search' => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 6,
		'menu_icon'           => null,
		'capability_type'     => 'page',
		'hierarchical'        => true,
		'supports'            => array( 'title', 'editor', 'thumbnail' ),
		'has_archive'         => true,
		'query_var'           => true,
		'can_export'          => true,
		'show_in_nav_menus'   => false
	);

	function __construct( $post_type, $args = array(), $plural = null ) {

		$this->post_type         = $post_type;
		$this->post_slug         = ( $plural ) ? $plural : $post_type . 's';
		$this->post_singular     = ucwords( str_replace( array( '-', '_' ), ' ', $this->post_type ) );
		$this->post_plural       = ucwords( str_replace( array( '-', '_' ), ' ', $this->post_slug ) );
		$this->post_type         = strtolower( $this->post_type );
		$this->post_slug         = strtolower( $this->post_slug );
		$this->post_singular_low = strtolower( $this->post_singular );
		$this->post_plural_low   = strtolower( $this->post_plural );

		$this->defaults['labels'] = array(
			'name'               => $this->post_plural,
			'singular_name'      => $this->post_singular,
			'menu_name'          => $this->post_plural,
			'name_admin_bar'     => $this->post_singular,
			'add_new'            => __( 'Add New', 'theme_admin' ),
			'add_new_item'       => sprintf( __( 'Add New %s', 'theme_admin' ), $this->post_singular ),
			'edit_item'          => sprintf( __( 'Edit %s', 'theme_admin' ), $this->post_singular ),
			'new_item'           => sprintf( __( 'New %s', 'theme_admin' ), $this->post_singular ),
			'view_item'          => sprintf( __( 'View %s', 'theme_admin' ), $this->post_singular ),
			'search_items'       => sprintf( __( 'Search %s', 'theme_admin' ), $this->post_plural ),
			'not_found'          => sprintf( __( 'No %s found', 'theme_admin' ), $this->post_plural_low ),
			'not_found_in_trash' => sprintf( __( 'No %s found in trash', 'theme_admin' ), $this->post_plural_low ),
			'parent_item_colon'  => sprintf( __( 'Parent %s', 'theme_admin' ), $this->post_singular ),
			'all_items'          => sprintf( __( 'All %s', 'theme_admin' ), $this->post_plural )
		);

		# 'public' is a meta argument, set some defaults
		if ( isset( $args['public'] ) ) {
			$this->defaults['publicly_queryable']  =  $args['public'];
			$this->defaults['show_ui']             =  $args['public'];
			$this->defaults['show_in_menu']        =  $args['public'];
			$this->defaults['show_in_nav_menus']   =  $args['public'];
			$this->defaults['exclude_from_search'] = !$args['public'];
		}

		# 'show_ui' is a meta argument, set some defaults
		if ( isset( $args['show_ui'] ) )
			$this->defaults['show_in_menu'] = $args['show_ui'];

		# Only set rewrites if we need them
		if ( ( isset( $args['publicly_queryable'] ) and !$args['publicly_queryable'] ) or ( !$this->defaults['publicly_queryable'] ) ) {
			$this->defaults['rewrite'] = false;
		} else {
			$this->defaults['rewrite'] = array(
				'slug'       => $this->post_slug,
				'with_front' => false
			);
		}

		$this->args = wp_parse_args( $args, $this->defaults );

		if ( !isset( $args['exclude_from_search'] ) )
			$this->args['exclude_from_search'] = !$this->args['publicly_queryable'];

		# This allows the labels arg to contain some or all labels:
		if ( isset( $args['labels'] ) )
			$this->args['labels'] = wp_parse_args( $args['labels'], $this->defaults['labels'] );

		if ( isset( $this->args['cols'] ) ) {
			add_action( "manage_{$this->post_type}_posts_columns",         array( $this, 'cols' ) );
			add_filter( "manage_{$this->post_type}_posts_custom_column",   array( $this, 'col' ), 10, 2 );
			add_filter( "manage_edit-{$this->post_type}_sortable_columns", array( $this, 'sortables' ) );
			add_action( 'load-edit.php',                                   array( $this, 'default_sort' ) );
			add_action( 'load-edit.php',                                   array( $this, 'maybe_sort' ) );
		}

		if ( isset( $this->args['filters'] ) )
			add_action( 'load-edit.php', array( $this, 'maybe_filter' ) );

		if ( isset( $this->args['show_in_feed'] ) and $this->args['show_in_feed'] )
			add_filter( 'request', array( $this, 'feed_request' ) );

		if ( !$this->args['publicly_queryable'] ) {
			$actions = ( $this->args['hierarchical'] ) ? 'page_row_actions' : 'post_row_actions';
			add_filter( $actions, array( $this, 'remove_view_action' ) );
		}

		add_action( 'init',                  array( $this, 'register_post_type' ), 9 );
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ), 1 );
		add_filter( 'parse_request',         array( $this, 'parse_request' ), 1 );

	}

	function remove_view_action( $actions ) {
		if ( get_query_var('post_type') == $this->post_type )
			unset( $actions['view'] ); # This bug is actually fixed in 3.2
		return $actions;
	}

	function default_sort() {
		if ( isset( $this->args['cols'] ) and isset( $_GET['post_type'] ) and ( $_GET['post_type'] == $this->post_type ) and !isset( $_GET['orderby'] ) ) {
			foreach ( $this->args['cols'] as $id => $col ) {
				if ( isset( $col['default'] ) ) {
					$_GET['orderby'] = $id;
					$_GET['order'] = ( 'desc' == strtolower( $col['default'] ) ? 'desc' : 'asc' );
					break;
				}
			}
		}
	}

	function maybe_sort() {
		if ( $this->post_type == get_current_screen()->post_type ) {
			add_filter( 'request',       array( $this, 'sort_column_by_meta' ) );
			add_filter( 'posts_clauses', array( $this, 'sort_column_by_tax' ), 10, 2 );
		}
	}

	function maybe_filter() {
		if ( $this->post_type == get_current_screen()->post_type ) {
			add_action( 'restrict_manage_posts', array( $this, 'filters' ) );
		}
	}

	function filters() {

		global $wpdb, $wp_locale;

		$pto = get_post_type_object( $this->post_type );

		foreach ( $this->args['filters'] as $filter_key => $filter ) {

			if ( isset( $filter['tax'] ) ) {

				$tax = get_taxonomy( $filter['tax'] );

				if ( class_exists( 'Walker_ExtendedTaxonomyDropdown' ) )
					$walker = new Walker_ExtendedTaxonomyDropdown;
				else
					$walker = null;

				wp_dropdown_categories( array(
					'show_option_all' => $tax->labels->all_items,
					'hide_empty'      => false,
					'hierarchical'    => true,
					'show_count'      => false,
					'orderby'         => 'name',
					'selected'        => get_query_var( $filter['tax'] ),
					'name'            => $filter['tax'],
					'taxonomy'        => $filter['tax'],
					'walker'          => $walker
				) );

			} else if ( isset( $filter['meta_key'] ) ) {

				# @TODO meta key filters

			}

		}

	}

	function post_updated_messages( $messages ) {

		# @see http://core.trac.wordpress.org/ticket/17609

		global $post;

		$messages[$this->post_type] = array(
			0 => '',
			1 => sprintf( __( ( $this->args['publicly_queryable'] ? '%1$s updated. <a href="%2$s">View %3$s</a>' : '%1$s updated.' ), 'theme_admin' ),
				$this->post_singular,
				esc_url( get_permalink( $post->ID ) ),
				$this->post_singular_low
			),
			2 => __( 'Custom field updated.', 'theme_admin' ),
			3 => __( 'Custom field deleted.', 'theme_admin' ),
			4 => sprintf( __( '%s updated.', 'theme_admin' ),
				$this->post_singular
			),
			5 => isset( $_GET['revision'] ) ? sprintf( __( '%1$s restored to revision from %2$s', 'theme_admin' ),
				$this->post_singular,
				wp_post_revision_title( intval( $_GET['revision'] ), false )
			) : false,
			6 => sprintf( __( ( $this->args['publicly_queryable'] ? '%1$s published. <a href="%2$s">View %3$s</a>' : '%1$s published.' ), 'theme_admin' ),
				$this->post_singular,
				esc_url( get_permalink( $post->ID ) ),
				$this->post_singular_low
			),
			7 => sprintf( __( '%s saved.', 'theme_admin' ),
				$this->post_singular
			),
			8 => sprintf( __( ( $this->args['publicly_queryable'] ? '%1$s submitted. <a target="_blank" href="%2$s">Preview %3$s</a>' : '%1$s submitted.' ), 'theme_admin' ),
				$this->post_singular,
				esc_url( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ),
				$this->post_singular_low
			),
			9 => sprintf( __( ( $this->args['publicly_queryable'] ? '%1$s scheduled for: <strong>%2$s</strong>. <a target="_blank" href="%3$s">Preview %4$s</a>' : '%1$s scheduled for: <strong>%2$s</strong>.' ), 'theme_admin' ),
				$this->post_singular,
				date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) ),
				esc_url( get_permalink( $post->ID ) ),
				$this->post_singular_low
			),
			10 => sprintf( __( ( $this->args['publicly_queryable'] ? '%1$s draft updated. <a target="_blank" href="%2$s">Preview %3$s</a>' : '%1$s draft updated.' ), 'theme_admin' ),
				$this->post_singular,
				esc_url( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ),
				$this->post_singular_low
			)
		);

		return $messages;

	}

	function sort_column_by_meta( $vars ) {
		# @TODO this might need a post_type check
		$o = $vars['orderby'];
		if ( isset( $this->args['cols'][$o]['meta_key'] ) ) {
			$vars['meta_key'] = $this->args['cols'][$o]['meta_key'];
			$vars['orderby']  = 'meta_value';
		}
		return $vars;
	}

	function sort_column_by_tax( $clauses, $q ) {
		# @TODO this might need a post_type check

		global $wpdb;

		if ( isset( $q->query['orderby'] ) ) {

			$o = $q->query['orderby'];

			if ( isset( $this->args['cols'][$o]['tax'] ) ) {

				# Taxonomy term ordering courtesy of http://scribu.net/wordpress/sortable-taxonomy-columns.html

				$clauses['join'] .= "
					LEFT OUTER JOIN {$wpdb->term_relationships} as ecpt_tr ON ( {$wpdb->posts}.ID = ecpt_tr.object_id )
					LEFT OUTER JOIN {$wpdb->term_taxonomy} as ecpt_tt ON ( ecpt_tr.term_taxonomy_id = ecpt_tt.term_taxonomy_id )
					LEFT OUTER JOIN {$wpdb->terms} as ecpt_t ON ( ecpt_tt.term_id = ecpt_t.term_id )
				";
				$clauses['where']   .= " AND ( taxonomy = '{$this->args['cols'][$o]['tax']}' OR taxonomy IS NULL )";
				$clauses['groupby'] = 'ecpt_tr.object_id';
				$clauses['orderby'] = "GROUP_CONCAT( ecpt_t.name ORDER BY name ASC ) ";
				$clauses['orderby'] .= ( 'ASC' == strtoupper( $q->get('order') ) ) ? 'ASC' : 'DESC';

			}

		}

		return $clauses;

	}

	function sortables( $cols ) {
		foreach ( $this->args['cols'] as $id => $col ) {
			if ( is_array( $col ) and ( isset( $col['meta_key'] ) or isset( $col['tax'] ) ) )
				$cols[$id] = $id;
		}
		return $cols;
	}

	function cols( $cols ) {

		# This isn't really the best way to do this. It could override custom cols from other plugins.
		# @TODO 'Page Manager' might be well suited to have as part of Extended CPTs

		$new_cols = array();
		$keep = array(
			'cb', 'title'
		);

		foreach ( $cols as $id => $title ) {
			if ( in_array( $id, $keep ) )
				$new_cols[$id] = $title;
		}

		foreach ( $this->args['cols'] as $id => $col ) {
			if ( is_string( $col ) and isset( $cols[$col] ) )
				$new_cols[$col] = $cols[$col];
			else if ( is_array( $col ) )
				$new_cols[$id] = $col['title'];
		}

		if ( post_type_supports( $this->post_type, 'reordering' ) )
			$new_cols['verplaats'] = $cols['verplaats'];

		return $new_cols;

	}

	function col( $col, $post_id ) {
		$custom_cols = array_filter( array_keys( $this->args['cols'] ) );
		if ( in_array( $col, $custom_cols ) )
			call_user_func( $this->args['cols'][$col]['function'] );
	}

	function feed_request( $vars ) {
		if ( isset( $vars['feed'] ) ) {
			if ( !isset( $vars['post_type'] ) )
				$vars['post_type'] = array( 'post', $this->post_type );
			else if ( is_array( $vars['post_type'] ) )
				$vars['post_type'][] = $this->post_type;
		}
		return $vars;
	}

	function parse_request( $p ) {

		if ( is_admin() )
			return $p;
		if ( !isset( $p->query_vars['post_type'] ) )
			return $p;
		if ( $this->post_type != $p->query_vars['post_type'] )
			return $p;
		if ( isset( $p->query_vars['name'] ) )
			return $p;

		if ( isset( $this->args['posts_per_page'] ) )
			$p->query_vars['posts_per_page'] = $this->args['posts_per_page'];

		return $p;

	}


	function register_post_type() {
		if ( is_wp_error( $cpt = register_post_type( $this->post_type, $this->args ) ) )
			trigger_error( $cpt->get_error_message(), E_USER_ERROR );
	}


}

function register_extended_post_type( $post_type, $args = array(), $plural = null ) {
	return new ExtendedCPT( $post_type, $args, $plural );
}


?>