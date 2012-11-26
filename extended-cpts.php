<?php
/*
Plugin Name:  Extended CPTs
Description:  Extended custom post types.
Version:      1.7.1
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
 * Override default posts_per_page value

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
		$this->defaults['rewrite'] = array(
			'slug'       => $this->post_slug,
			'with_front' => false
		);

		if ( isset( $args['public'] ) and !$args['public'] ) {
			$this->defaults['publicly_queryable']  = false;
			$this->defaults['show_ui']             = false;
			$this->defaults['show_in_menu']        = false;
			$this->defaults['show_in_nav_menus']   = false;
			$this->defaults['exclude_from_search'] = true;
		}

		$this->args = wp_parse_args( $args, $this->defaults );

		if ( !isset( $args['exclude_from_search'] ) )
			$this->args['exclude_from_search'] = !$this->args['publicly_queryable'];

		if ( isset( $args['labels'] ) )
			$this->args['labels'] = wp_parse_args( $args['labels'], $this->defaults['labels'] );

		if ( isset( $this->args['cols'] ) ) {
			add_action( "manage_{$this->post_type}_posts_columns",         array( $this, '_cols' ) );
			add_filter( "manage_{$this->post_type}_posts_custom_column",   array( $this, '_col' ), 10, 2 );
			add_filter( "manage_edit-{$this->post_type}_sortable_columns", array( $this, '_sortables' ) );
			add_action( 'load-edit.php',                                   array( $this, '_default_sort' ) );
			add_action( 'load-edit.php',                                   array( $this, '_maybe_sort' ) );
		}

		if ( isset( $this->args['filters'] ) ) {
			add_action( 'load-edit.php', array( $this, '_maybe_filter' ) );
		}

		if ( !$this->args['publicly_queryable'] ) {
			$actions = ( $this->args['hierarchical'] ) ? 'page_row_actions' : 'post_row_actions';
			add_filter( $actions, array( $this, '_remove_view_action' ) );
		}

		add_action( 'init',                  array( $this, 'register_post_type' ), 9 );
		add_filter( 'post_updated_messages', array( $this, '_post_updated_messages' ), 1 );
		add_filter( 'parse_request',         array( $this, '_parse_request' ), 1 );

	}

	function _remove_view_action( $actions ) {
		if ( get_query_var('post_type') == $this->post_type )
			unset( $actions['view'] ); # This bug is fixed in 3.2
		return $actions;
	}

	function _default_sort() {
		if ( isset( $this->args['cols'] ) and ( @$_GET['post_type'] == $this->post_type ) and !isset( $_GET['orderby'] ) ) {
			foreach ( $this->args['cols'] as $id => $col ) {
				if ( isset( $col['default'] ) ) {
					$_GET['orderby'] = $id;
					$_GET['order'] = ( 'desc' == strtolower( $col['default'] ) ? 'desc' : 'asc' );
					break;
				}
			}
		}
	}

	function _maybe_sort() {
		if ( $this->post_type == get_current_screen()->post_type ) {
			add_filter( 'request',       array( $this, '_sort_column_by_meta' ) );
			add_filter( 'posts_clauses', array( $this, '_sort_column_by_tax' ), 10, 2 );
		}
	}

	function _maybe_filter() {
		if ( $this->post_type == get_current_screen()->post_type ) {
			add_action( 'restrict_manage_posts', array( $this, '_filters' ) );
		}
	}

	function _filters() {
		# @TODO filter dropdowns for taxos and meta keys
	}

	function _post_updated_messages( $messages ) {

		global $post, $post_ID;

		$messages[$this->post_type] = array(
			0 => '',
			1 => sprintf( __( ( $this->args['publicly_queryable'] ? '%1$s updated. <a href="%2$s">View %3$s</a>' : '%1$s updated.' ), 'theme_admin' ),
				$this->post_singular,
				esc_url( get_permalink( $post_ID ) ),
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
				esc_url( get_permalink( $post_ID ) ),
				$this->post_singular_low
			),
			7 => sprintf( __( '%s saved.', 'theme_admin' ),
				$this->post_singular
			),
			8 => sprintf( __( ( $this->args['publicly_queryable'] ? '%1$s submitted. <a target="_blank" href="%2$s">Preview %3$s</a>' : '%1$s submitted.' ), 'theme_admin' ),
				$this->post_singular,
				esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ),
				$this->post_singular_low
			),
			9 => sprintf( __( ( $this->args['publicly_queryable'] ? '%1$s scheduled for: <strong>%2$s</strong>. <a target="_blank" href="%3$s">Preview %4$s</a>' : '%1$s scheduled for: <strong>%2$s</strong>.' ), 'theme_admin' ),
				$this->post_singular,
				date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) ),
				esc_url( get_permalink( $post_ID ) ),
				$this->post_singular_low
			),
			10 => sprintf( __( ( $this->args['publicly_queryable'] ? '%1$s draft updated. <a target="_blank" href="%2$s">Preview %3$s</a>' : '%1$s draft updated.' ), 'theme_admin' ),
				$this->post_singular,
				esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ),
				$this->post_singular_low
			)
		);

		return $messages;

	}

	function _sort_column_by_meta( $vars ) {
		$o = $vars['orderby'];
		if ( isset( $this->args['cols'][$o]['meta_key'] ) ) {
			$vars['meta_key'] = $this->args['cols'][$o]['meta_key'];
			$vars['orderby']  = 'meta_value';
		}
		return $vars;
	}

	function _sort_column_by_tax( $clauses, $q ) {

		global $wpdb;

		if ( isset( $q->query['orderby'] ) ) {

			$o = $q->query['orderby'];

			if ( isset( $this->args['cols'][$o]['tax'] ) ) {

				# Taxonomy term ordering courtesy of http://scribu.net/wordpress/sortable-taxonomy-columns.html

				$clauses['join'] .= "
					LEFT OUTER JOIN {$wpdb->term_relationships} ON {$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id
					LEFT OUTER JOIN {$wpdb->term_taxonomy} USING ( term_taxonomy_id )
					LEFT OUTER JOIN {$wpdb->terms} USING ( term_id )
				";
				$clauses['where']   .= " AND ( taxonomy = '{$this->args['cols'][$o]['tax']}' OR taxonomy IS NULL )";
				$clauses['groupby'] = 'object_id';
				$clauses['orderby'] = "GROUP_CONCAT( {$wpdb->terms}.name ORDER BY name ASC ) ";
				$clauses['orderby'] .= ( 'ASC' == strtoupper( $q->get('order') ) ) ? 'ASC' : 'DESC';

			}

		}

		return $clauses;

	}

	function _sortables( $cols ) {
		foreach ( $this->args['cols'] as $id => $col ) {
			if ( is_array( $col ) and ( isset( $col['meta_key'] ) or isset( $col['tax'] ) ) )
				$cols[$id] = $id;
		}
		return $cols;
	}

	function _cols( $cols ) {

		# This isn't the best way to do this. It overrides custom cols from other plugins.
		# @TODO 'Page Manager' would be well suited to have as part of ExtCPTs

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

	function _col( $col, $post_id ) {
		$custom_cols = array_filter( array_keys( $this->args['cols'] ) );
		if ( in_array( $col, $custom_cols ) )
			call_user_func( $this->args['cols'][$col]['function'] );
	}

	function _parse_request( $p ) {

		if ( is_admin() )
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
		return register_post_type( $this->post_type, $this->args );
	}


}

function register_extended_post_type( $post_type, $args = array(), $plural = null ) {
	return new ExtendedCPT( $post_type, $args, $plural );
}


?>