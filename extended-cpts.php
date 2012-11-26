<?php
/*
Plugin Name:  Extended CPTs
Description:  Extended custom post types.
Version:      1.8
Author:       John Blackbourn
Author URI:   http://johnblackbourn.com

Extended CPTs started off with several features such as extended localisation, post type listings and post type listing pages. The extended localisation has since been removed, and the post type listings (aka post type archives) functionality has since been rolled into WordPress.

 * Better defaults for everything:
   - Intelligent defaults for all labels and post updated messages
   - Hierarchical by default, with page capability type
   - Drop with_front from rewrite rules
   - Support post thumbnails
   - Optimal menu placement
 * Insanely easy custom admin columns:
   - Add columns for post fields, post meta, taxonomies, or callback functions
   - Out of the box sorting by post field, post meta, or taxonomy terms
   - Specify a default sort column (great for CPTs with custom date fields)
 * Custom filter dropdowns on CPT management screens
 * Override default query variables such as posts_per_page, orderby, order and nopaging
 * Easily add CPTs to feeds

@TODO:

 * Improve the selection of fields shown in the Quick Edit boxes
 * Add the meta_key filter dropdown (clashes with the sortables)
 * Inline docs

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

	function __construct( $post_type, $args = array(), $plural = null, $slug = null ) {

		$this->post_type = $post_type;

		if ( $slug )
			$this->post_slug = $slug;
		else if ( $plural )
			$this->post_slug = $plural;
		else
			$this->post_slug = $post_type . 's';

		if ( $plural )
			$this->post_plural = $plural;
		else
			$this->post_plural = $this->post_slug;

		$this->post_singular     = ucwords( str_replace( array( '-', '_' ), ' ', $this->post_type ) );
		$this->post_plural       = ucwords( str_replace( array( '-', '_' ), ' ', $this->post_plural ) );
		$this->post_singular_low = strtolower( $this->post_singular );
		$this->post_plural_low   = strtolower( $this->post_plural );

		$this->defaults['labels'] = array(
			'name'               => $this->post_plural,
			'singular_name'      => $this->post_singular,
			'menu_name'          => $this->post_plural,
			'name_admin_bar'     => $this->post_singular,
			'add_new'            => 'Add New',
			'add_new_item'       => sprintf( 'Add New %s', $this->post_singular ),
			'edit_item'          => sprintf( 'Edit %s', $this->post_singular ),
			'new_item'           => sprintf( 'New %s', $this->post_singular ),
			'view_item'          => sprintf( 'View %s', $this->post_singular ),
			'search_items'       => sprintf( 'Search %s', $this->post_plural ),
			'not_found'          => sprintf( 'No %s found', $this->post_plural_low ),
			'not_found_in_trash' => sprintf( 'No %s found in trash', $this->post_plural_low ),
			'parent_item_colon'  => sprintf( 'Parent %s', $this->post_singular ),
			'all_items'          => sprintf( 'All %s', $this->post_plural )
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

		if ( isset( $this->args['right_now'] ) )
			add_action( 'right_now_content_table_end', array( $this, 'right_now' ) );

		if ( isset( $this->args['show_in_feed'] ) and $this->args['show_in_feed'] )
			add_filter( 'request', array( $this, 'feed_request' ) );

		if ( !$this->args['publicly_queryable'] ) {
			$actions = ( $this->args['hierarchical'] ) ? 'page_row_actions' : 'post_row_actions';
			add_filter( $actions, array( $this, 'remove_view_action' ) );
		}

		add_action( 'init',                       array( $this, 'register_post_type' ), 9 );
		add_filter( 'post_updated_messages',      array( $this, 'post_updated_messages' ), 1 );
		add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_post_updated_messages' ), 1, 2 );
		add_filter( 'parse_request',              array( $this, 'parse_request' ), 1 );

	}

	function remove_view_action( $actions ) {
		if ( get_query_var('post_type') == $this->post_type )
			unset( $actions['view'] ); # This bug is fixed in 3.2
		return $actions;
	}

	function default_sort() {
		if ( isset( $this->args['cols'] ) and isset( $_GET['post_type'] ) and ( $_GET['post_type'] == $this->post_type ) and !isset( $_GET['orderby'] ) ) {
			foreach ( $this->args['cols'] as $id => $col ) {
				if ( is_array( $col ) and isset( $col['default'] ) ) {
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

		$pto = get_post_type_object( $this->post_type );

		foreach ( $this->args['filters'] as $filter_key => $filter ) {

			if ( isset( $filter['tax'] ) ) {

				$tax = get_taxonomy( $filter['tax'] );

				if ( class_exists( 'Walker_ExtendedTaxonomyDropdownSlug' ) )
					$walker = new Walker_ExtendedTaxonomyDropdownSlug;
				else
					$walker = null;

				wp_dropdown_categories( array(
					'show_option_all' => $tax->labels->all_items . '&nbsp;',
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

	function right_now() {

		$pto   = get_post_type_object( $this->post_type );
		$count = wp_count_posts( $this->post_type );
		$text  = _n( $pto->labels->singular_name, $pto->labels->name, $count->publish );
		$num   = number_format_i18n( $count->publish );

		if ( current_user_can( $pto->cap->edit_posts ) ) {
			$num  = '<a href="edit.php?post_type=' . $this->post_type . '">' . $num . '</a>';
			$text = '<a href="edit.php?post_type=' . $this->post_type . '">' . $text . '</a>';
		}

		echo '<tr>';
		echo '<td class="first b b-' . $this->post_type . '">' . $num . '</td>';
		echo '<td class="t ' . $this->post_type . '">' . $text . '</td>';
		echo '</tr>';

	}

	function post_updated_messages( $messages ) {

		global $post;

		$messages[$this->post_type] = array(
			0 => '',
			1 => sprintf( ( $this->args['publicly_queryable'] ? '%1$s updated. <a href="%2$s">View %3$s</a>' : '%1$s updated.' ),
				$this->post_singular,
				esc_url( get_permalink( $post->ID ) ),
				$this->post_singular_low
			),
			2 => 'Custom field updated.',
			3 => 'Custom field deleted.',
			4 => sprintf( '%s updated.',
				$this->post_singular
			),
			5 => isset( $_GET['revision'] ) ? sprintf( '%1$s restored to revision from %2$s',
				$this->post_singular,
				wp_post_revision_title( intval( $_GET['revision'] ), false )
			) : false,
			6 => sprintf( ( $this->args['publicly_queryable'] ? '%1$s published. <a href="%2$s">View %3$s</a>' : '%1$s published.' ),
				$this->post_singular,
				esc_url( get_permalink( $post->ID ) ),
				$this->post_singular_low
			),
			7 => sprintf( '%s saved.',
				$this->post_singular
			),
			8 => sprintf( ( $this->args['publicly_queryable'] ? '%1$s submitted. <a target="_blank" href="%2$s">Preview %3$s</a>' : '%1$s submitted.' ),
				$this->post_singular,
				esc_url( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ),
				$this->post_singular_low
			),
			9 => sprintf( ( $this->args['publicly_queryable'] ? '%1$s scheduled for: <strong>%2$s</strong>. <a target="_blank" href="%3$s">Preview %4$s</a>' : '%1$s scheduled for: <strong>%2$s</strong>.' ),
				$this->post_singular,
				date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) ),
				esc_url( get_permalink( $post->ID ) ),
				$this->post_singular_low
			),
			10 => sprintf( ( $this->args['publicly_queryable'] ? '%1$s draft updated. <a target="_blank" href="%2$s">Preview %3$s</a>' : '%1$s draft updated.' ),
				$this->post_singular,
				esc_url( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ),
				$this->post_singular_low
			)
		);

		return $messages;

	}

	function bulk_post_updated_messages( $messages, $counts ) {

		# http://core.trac.wordpress.org/ticket/18710

		$messages[$this->post_type] = array(
			0 => '',
			1 => sprintf( $this->n( '%2$s updated.', '%1$s %3$s updated.', $counts[1] ),
				$counts[1],
				$this->post_singular,
				$this->post_plural_low
			),
			2 => sprintf( $this->n( '%2$s not updated, somebody is editing it.', '%1$s %3$s not updated, somebody is editing them.', $counts[2] ),
				$counts[2],
				$this->post_singular,
				$this->post_plural_low
			),
			3 => sprintf( $this->n( '%2$s permanently deleted.', '%1$s %3$s permanently deleted.', $counts[3] ),
				$counts[3],
				$this->post_singular,
				$this->post_plural_low
			),
			4 => sprintf( $this->n( '%2$s moved to the trash.', '%1$s %3$s moved to the trash.', $counts[4] ),
				$counts[4],
				$this->post_singular,
				$this->post_plural_low
			),
			5 => sprintf( $this->n( '%2$s restored from the trash.', '%1$s %3$s restored from the trash.', $counts[5] ),
				$counts[5],
				$this->post_singular,
				$this->post_plural_low
			)
		);

		return $messages;

	}

	function sort_column_by_meta( $vars ) {
		# @TODO this might need a post_type check
		if ( isset( $vars['orderby'] ) ) {
			$o = $vars['orderby'];
			if ( isset( $this->args['cols'][$o]['meta_key'] ) ) {
				$vars['meta_key'] = $this->args['cols'][$o]['meta_key'];
				$vars['orderby']  = 'meta_value';
			}
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

		if ( in_array( $col, $custom_cols ) ) {

			if ( isset( $this->args['cols'][$col]['function'] ) )
				call_user_func( $this->args['cols'][$col]['function'] );
			else if ( isset( $this->args['cols'][$col]['meta_key'] ) )
				$this->col_meta( $this->args['cols'][$col]['meta_key'] );
			else if ( isset( $this->args['cols'][$col]['tax'] ) )
				$this->col_tax( $this->args['cols'][$col]['tax'] );
			else if ( isset( $this->args['cols'][$col]['field'] ) )
				$this->col_field( $this->args['cols'][$col]['field'] );

		}

	}

	function col_meta( $meta_key, $post_id = 0 ) {

		$post = get_post( $post_id );
		echo esc_html( get_post_meta( $post->ID, $meta_key, true ) );

	}

	function col_tax( $taxonomy, $post_id = 0 ) {

		$post  = get_post( $post_id );
		$terms = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'names' ) );

		if ( is_wp_error( $terms ) or empty( $terms ) )
			return;

		echo implode( ', ', array_map( 'esc_html', $terms ) );

	}

	function col_field( $field, $post_id = 0 ) {

		$post = get_post( $post_id );
		$field_short = str_replace( 'post_', '', $field );

		if ( isset( $post->$field ) )
			echo esc_html( $post->$field );
		else if ( isset( $post->$field_short ) )
			echo esc_html( $post->$field_short );

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

		if ( isset( $this->args['archive'] ) and is_array( $this->args['archive'] ) ) {
			foreach ( $this->args['archive'] as $var => $value )
				$p->query_vars[$var] = $value;
		}

		return $p;

	}

	function n( $singular, $plural, $count ) {
		# This is a non-localised version of _n()
		return ( 1 == $count ) ? $singular : $plural;
	}

	function register_post_type() {
		if ( is_wp_error( $cpt = register_post_type( $this->post_type, $this->args ) ) )
			trigger_error( $cpt->get_error_message(), E_USER_ERROR );
	}

}

function register_extended_post_type( $post_type, $args = array(), $plural = null, $slug = null ) {
	return new ExtendedCPT( $post_type, $args, $plural, $slug );
}

?>