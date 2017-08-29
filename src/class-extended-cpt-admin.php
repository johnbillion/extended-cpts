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

class Extended_CPT_Admin {

	/**
	 * Default arguments for custom post types.
	 *
	 * @var array
	 */
	protected $defaults = [
		'quick_edit'           => true,  # Custom arg
		'dashboard_glance'     => true,  # Custom arg
		'admin_cols'           => null,  # Custom arg
		'admin_filters'        => null,  # Custom arg
		'enter_title_here'     => null,  # Custom arg
	];
	public $cpt;
	public $args;
	protected $_cols;
	protected $the_cols = null;
	protected $connection_exists = [];

	/**
	 * Class constructor.
	 *
	 * @param Extended_CPT $cpt  An extended post type object.
	 * @param array        $args Optional. The post type arguments.
	 */
	public function __construct( Extended_CPT $cpt, array $args = [] ) {

		$this->cpt = $cpt;
		# Merge our args with the defaults:
		$this->args = array_merge( $this->defaults, $args );

		# Admin columns:
		if ( $this->args['admin_cols'] ) {
			add_filter( 'manage_posts_columns',                                 [ $this, '_log_default_cols' ], 0 );
			add_filter( 'manage_pages_columns',                                 [ $this, '_log_default_cols' ], 0 );
			add_filter( "manage_edit-{$this->cpt->post_type}_sortable_columns", [ $this, 'sortables' ] );
			add_filter( "manage_{$this->cpt->post_type}_posts_columns",         [ $this, 'cols' ] );
			add_action( "manage_{$this->cpt->post_type}_posts_custom_column",   [ $this, 'col' ] );
			add_action( 'load-edit.php',                                        [ $this, 'default_sort' ] );
			add_filter( 'pre_get_posts',                                        [ $this, 'maybe_sort_by_fields' ] );
			add_filter( 'posts_clauses',                                        [ $this, 'maybe_sort_by_taxonomy' ], 10, 2 );
		}

		# Admin filters:
		if ( $this->args['admin_filters'] ) {
			add_filter( 'pre_get_posts',         [ $this, 'maybe_filter' ] );
			add_filter( 'query_vars',            [ $this, 'add_query_vars' ] );
			add_action( 'restrict_manage_posts', [ $this, 'filters' ] );
		}

		# 'Enter title here' filter:
		if ( $this->args['enter_title_here'] ) {
			add_filter( 'enter_title_here', [ $this, 'enter_title_here' ], 10, 2 );
		}

		# Hide month filter:
		if ( isset( $this->args['admin_filters']['m'] ) && ! $this->args['admin_filters']['m'] ) {
			add_action( 'admin_head-edit.php', [ $this, 'admin_head' ] );
		}

		# Quick Edit:
		if ( ! $this->args['quick_edit'] ) {
			add_filter( 'post_row_actions',                          [ $this, 'remove_quick_edit_action' ], 10, 2 );
			add_filter( 'page_row_actions',                          [ $this, 'remove_quick_edit_action' ], 10, 2 );
			add_filter( "bulk_actions-edit-{$this->cpt->post_type}", [ $this, 'remove_quick_edit_menu' ] );
		}

		# 'At a Glance' dashboard panels:
		if ( $this->args['dashboard_glance'] ) {
			add_filter( 'dashboard_glance_items', [ $this, 'glance_items' ], $this->cpt->args['menu_position'] );
		}

		# Post updated messages:
		add_filter( 'post_updated_messages',      [ $this, 'post_updated_messages' ], 1 );
		add_filter( 'bulk_post_updated_messages', [ $this, 'bulk_post_updated_messages' ], 1, 2 );

	}

	/**
	 * Add some CSS to the post listing screen. Used to hide various screen elements.
	 */
	public function admin_head() {

		if ( self::get_current_post_type() !== $this->cpt->post_type ) {
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

		if ( self::get_current_post_type() !== $this->cpt->post_type ) {
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
	public function enter_title_here( string $title, WP_Post $post ) : string {

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
	 * Output custom filter controls on the admin screen for this post type.
	 *
	 * @link https://github.com/johnbillion/extended-cpts/wiki/Admin-filters
	 *
	 */
	public function filters() {

		global $wpdb;

		if ( self::get_current_post_type() !== $this->cpt->post_type ) {
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

				require_once __DIR__ . '/class-walker-extendedtaxonomydropdown.php';

				$walker = new Walker_ExtendedTaxonomyDropdown( [
					'field' => 'slug',
				] );

				# If we haven't specified a title, use the all_items label from the taxonomy:
				if ( ! isset( $filter['title'] ) ) {
					$filter['title'] = $tax->labels->all_items;
				}

				# Output the dropdown:
				wp_dropdown_categories( [
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
				] );

			} elseif ( isset( $filter['meta_key'] ) ) {

				# If we haven't specified a title, generate one from the meta key:
				if ( ! isset( $filter['title'] ) ) {
					$filter['title'] = str_replace( [
						'-',
						'_',
					], ' ', $filter['meta_key'] );
					$filter['title'] = ucwords( $filter['title'] ) . 's';
					$filter['title'] = sprintf( 'All %s', $filter['title'] );
				}

				if ( ! isset( $filter['options'] ) ) {
					# Fetch all the values for our meta key:
					# @TODO AND m.meta_value != null ?
					// @codingStandardsIgnoreStart
					$filter['options'] = $wpdb->get_col( $wpdb->prepare( "
						SELECT DISTINCT meta_value
						FROM {$wpdb->postmeta} as m
						JOIN {$wpdb->posts} as p ON ( p.ID = m.post_id )
						WHERE m.meta_key = %s
						AND m.meta_value != ''
						AND p.post_type = %s
						ORDER BY m.meta_value ASC
					", $filter['meta_key'], $this->cpt->post_type ) );
					// @codingStandardsIgnoreEnd
				} elseif ( is_callable( $filter['options'] ) ) {
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

			} elseif ( isset( $filter['meta_search_key'] ) ) {

				# If we haven't specified a title, generate one from the meta key:
				if ( ! isset( $filter['title'] ) ) {
					$filter['title'] = str_replace( [
						'-',
						'_',
					], ' ', $filter['meta_search_key'] );
					$filter['title'] = ucwords( $filter['title'] );
				}

				$value = wp_unslash( get_query_var( $filter_key ) );

				# Output the search box:
				?>
				<label><?php printf( '%s:', esc_html( $filter['title'] ) ); ?>&nbsp;<input type="text" name="<?php echo esc_attr( $filter_key ); ?>" id="filter_<?php echo esc_attr( $filter_key ); ?>" value="<?php echo esc_attr( $value ); ?>" /></label>
				<?php

			} elseif ( isset( $filter['meta_exists'] ) ) {

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
	public function add_query_vars( array $vars ) : array {

		$filters = array_keys( $this->args['admin_filters'] );

		return array_merge( $vars, $filters );

	}

	/**
	 * Filter posts by our custom admin filters.
	 *
	 * @param WP_Query $wp_query Looks a bit like a `WP_Query` object
	 */
	public function maybe_filter( WP_Query $wp_query ) {

		if ( empty( $wp_query->query['post_type'] ) || ! in_array( $this->cpt->post_type, (array) $wp_query->query['post_type'], true ) ) {
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
					$query = [];
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

		if ( empty( $wp_query->query['post_type'] ) || ! in_array( $this->cpt->post_type, (array) $wp_query->query['post_type'], true ) ) {
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
	public function maybe_sort_by_taxonomy( array $clauses, WP_Query $wp_query ) : array {

		if ( empty( $wp_query->query['post_type'] ) || ! in_array( $this->cpt->post_type, (array) $wp_query->query['post_type'], true ) ) {
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
	public function glance_items( array $items ) : array {

		$pto = get_post_type_object( $this->cpt->post_type );

		if ( ! current_user_can( $pto->cap->edit_posts ) ) {
			return $items;
		}
		if ( $pto->_builtin ) {
			return $items;
		}

		# Get the labels and format the counts:
		$count = wp_count_posts( $this->cpt->post_type );
		$text  = self::n( $pto->labels->singular_name, $pto->labels->name, (int) $count->publish );
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
	public function post_updated_messages( array $messages ) : array {

		global $post;

		$pto = get_post_type_object( $this->cpt->post_type );

		$messages[ $this->cpt->post_type ] = [
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
		];

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
	public function bulk_post_updated_messages( array $messages, array $counts ) : array {

		$messages[ $this->cpt->post_type ] = [
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
		];

		return $messages;

	}

	/**
	 * Add our custom columns to the list of sortable columns.
	 *
	 * @param  array $cols Associative array of sortable columns
	 * @return array       Updated array of sortable columns
	 */
	public function sortables( array $cols ) : array {

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
	 * @link https://github.com/johnbillion/extended-cpts/wiki/Admin-columns
	 *
	 * @param  array $cols Associative array of columns
	 * @return array       Updated array of columns
	 */
	public function cols( array $cols ) : array {

		// This function gets called multiple times, so let's cache it for efficiency:
		if ( isset( $this->the_cols ) ) {
			return $this->the_cols;
		}

		$new_cols = [];
		$keep = [
			'cb',
			'title',
		];

		# Add existing columns we want to keep:
		foreach ( $cols as $id => $title ) {
			if ( in_array( $id, $keep, true ) && ! isset( $this->args['admin_cols'][ $id ] ) ) {
				$new_cols[ $id ] = $title;
			}
		}

		# Add our custom columns:
		foreach ( array_filter( $this->args['admin_cols'] ) as $id => $col ) {
			if ( is_string( $col ) && isset( $cols[ $col ] ) ) {
				# Existing (ie. built-in) column with id as the value
				$new_cols[ $col ] = $cols[ $col ];
			} elseif ( is_string( $col ) && isset( $cols[ $id ] ) ) {
				# Existing (ie. built-in) column with id as the key and title as the value
				$new_cols[ $id ] = esc_html( $col );
			} elseif ( 'author' === $col ) {
				# Automatic support for Co-Authors Plus plugin and special case for
				# displaying author column when the post type doesn't support 'author'
				if ( class_exists( 'coauthors_plus' ) ) {
					$k = 'coauthors';
				} else {
					$k = 'author';
				}
				$new_cols[ $k ] = esc_html__( 'Author', 'extended-cpts' );
			} elseif ( is_array( $col ) ) {
				if ( isset( $col['cap'] ) && ! current_user_can( $col['cap'] ) ) {
					continue;
				}
				if ( isset( $col['connection'] ) && ! function_exists( 'p2p_type' ) ) {
					continue;
				}
				if ( ! isset( $col['title'] ) ) {
					$col['title'] = $this->get_item_title( $col ) ?? $id;
				}
				$new_cols[ $id ] = esc_html( $col['title'] );
			}
		}

		# Re-add any custom columns:
		$custom   = array_diff_key( $cols, $this->_cols );
		$new_cols = array_merge( $new_cols, $custom );

		$this->the_cols = $new_cols;
		return $this->the_cols;

	}

	/**
	 * Output the column data for our custom columns.
	 *
	 * @param string $col The column name
	 */
	public function col( string $col ) {

		# Shorthand:
		$c = $this->args['admin_cols'];

		# We're only interested in our custom columns:
		$custom_cols = array_filter( array_keys( $c ) );

		if ( ! in_array( $col, $custom_cols, true ) ) {
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
		} elseif ( isset( $c[ $col ]['meta_key'] ) ) {
			$this->col_post_meta( $c[ $col ]['meta_key'], $c[ $col ] );
		} elseif ( isset( $c[ $col ]['taxonomy'] ) ) {
			$this->col_taxonomy( $c[ $col ]['taxonomy'], $c[ $col ] );
		} elseif ( isset( $c[ $col ]['post_field'] ) ) {
			$this->col_post_field( $c[ $col ]['post_field'], $c[ $col ] );
		} elseif ( isset( $c[ $col ]['featured_image'] ) ) {
			$this->col_featured_image( $c[ $col ]['featured_image'], $c[ $col ] );
		} elseif ( isset( $c[ $col ]['connection'] ) ) {
			$this->col_connection( $c[ $col ]['connection'], $c[ $col ] );
		}

	}

	/**
	 * Output column data for a post meta field.
	 *
	 * @param string $meta_key The post meta key
	 * @param array  $args     Array of arguments for this field
	 */
	public function col_post_meta( string $meta_key, array $args ) {

		$vals = get_post_meta( get_the_ID(), $meta_key, false );
		$echo = [];
		sort( $vals );

		if ( isset( $args['date_format'] ) ) {

			if ( true === $args['date_format'] ) {
				$args['date_format'] = get_option( 'date_format' );
			}

			foreach ( $vals as $val ) {

				if ( is_numeric( $val ) ) {
					$echo[] = date_i18n( $args['date_format'], $val );
				} elseif ( ! empty( $val ) ) {
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
	public function col_taxonomy( string $taxonomy, array $args ) {

		global $post;

		$terms = get_the_terms( $post, $taxonomy );
		$tax   = get_taxonomy( $taxonomy );

		if ( is_wp_error( $terms ) ) {
			echo esc_html( $terms->get_error_message() );
			return;
		}

		if ( empty( $terms ) ) {
			printf(
				'<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">%s</span>',
				esc_html( $tax->labels->no_terms )
			);
			return;
		}

		$out = [];

		foreach ( $terms as $term ) {

			if ( $args['link'] ) {

				switch ( $args['link'] ) {
					case 'view':
						if ( $tax->public ) {
							// https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/issues/1096
							// @codingStandardsIgnoreStart
							$out[] = sprintf(
								'<a href="%1$s">%2$s</a>',
								esc_url( get_term_link( $term ) ),
								esc_html( $term->name )
							);
							// @codingStandardsIgnoreEnd
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
						$link = add_query_arg( [
							'post_type' => $post->post_type,
							$taxonomy   => $term->slug,
						], admin_url( 'edit.php' ) );
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
	public function col_post_field( string $field, array $args ) {

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
				$status = get_post_status_object( get_post_status( $post ) );
				if ( $status ) {
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
	public function col_featured_image( string $image_size, array $args ) {

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

		$image_atts = [
			'style' => esc_attr( sprintf(
				'width:%1$s;height:%2$s',
				$width,
				$height
			) ),
			'title' => '',
		];

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
	public function col_connection( string $connection, array $args ) {

		global $post, $wp_query;

		if ( ! function_exists( 'p2p_type' ) ) {
			return;
		}

		if ( ! $this->p2p_connection_exists( $connection ) ) {
			echo esc_html( sprintf(
				/* translators: %s: The ID of the Posts 2 Posts connection type */
				__( 'Invalid connection type: %s', 'extended-cpts' ),
				$connection
			) );
			return;
		}

		$_post = $post;
		$meta  = [];
		$out   = [];
		$field = 'connected_' . $connection;

		if ( isset( $args['field'] ) && isset( $args['value'] ) ) {
			$meta = [
				'connected_meta' => [
					$args['field'] => $args['value'],
				],
			];
			$field .= sanitize_title( '_' . $args['field'] . '_' . $args['value'] );
		}

		if ( ! isset( $_post->$field ) ) {
			$type = p2p_type( $connection );
			if ( $type ) {
				$type->each_connected( [ $_post ], $meta, $field );
			} else {
				echo esc_html( sprintf(
					/* translators: %s: The ID of the Posts 2 Posts connection type */
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
						$link = add_query_arg( array_merge( [
							'post_type'       => $_post->post_type,
							'connected_type'  => $connection,
							'connected_items' => $post->ID,
						], $meta ), admin_url( 'edit.php' ) );
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
	public function remove_quick_edit_action( array $actions, WP_Post $post ) : array {

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
	public function remove_quick_edit_menu( array $actions ) : array {

		unset( $actions['edit'] );
		return $actions;

	}

	/**
	 * Logs the default columns so we don't remove any custom columns added by other plugins.
	 *
	 * @param  array $cols The default columns for this post type screen
	 * @return array       The default columns for this post type screen
	 */
	public function _log_default_cols( array $cols ) : array {

		$this->_cols = $cols;
		return $this->_cols;

	}

	/**
	 * A non-localised version of _n()
	 *
	 * @param  string $single The text that will be used if $number is 1
	 * @param  string $plural The text that will be used if $number is not 1
	 * @param  int    $number The number to compare against to use either `$single` or `$plural`
	 * @return string         Either `$single` or `$plural` text
	 */
	protected static function n( string $single, string $plural, int $number ) : string {

		return ( 1 === intval( $number ) ) ? $single : $plural;

	}

	/**
	 * Get a sensible title for the current item (usually the arguments array for a column)
	 *
	 * @param  array  $item An array of arguments
	 * @return string|null  The item title
	 */
	protected function get_item_title( array $item ) {

		if ( isset( $item['taxonomy'] ) ) {
			$tax = get_taxonomy( $item['taxonomy'] );
			if ( $tax ) {
				if ( ! empty( $tax->exclusive ) ) {
					return $tax->labels->singular_name;
				} else {
					return $tax->labels->name;
				}
			} else {
				return $item['taxonomy'];
			}
		} elseif ( isset( $item['post_field'] ) ) {
			return ucwords( trim( str_replace( [
				'post_',
				'_',
			], ' ', $item['post_field'] ) ) );
		} elseif ( isset( $item['meta_key'] ) ) {
			return ucwords( trim( str_replace( [
				'_',
				'-',
			], ' ', $item['meta_key'] ) ) );
		} elseif ( isset( $item['connection'] ) && isset( $item['field'] ) && isset( $item['value'] ) ) {

			$fallback = ucwords( trim( str_replace( [
				'_',
				'-',
			], ' ', $item['value'] ) ) );

			if ( ! function_exists( 'p2p_type' ) || ! $this->p2p_connection_exists( $item['connection'] ) ) {
				return $fallback;
			}

			$ctype = p2p_type( $item['connection'] );
			if ( ! $ctype ) {
				return $fallback;
			}

			if ( isset( $ctype->fields[ $item['field'] ]['values'][ $item['value'] ] ) ) {
				if ( '' === trim( $ctype->fields[ $item['field'] ]['values'][ $item['value'] ] ) ) {
					return $ctype->fields[ $item['field'] ]['title'];
				} else {
					return $ctype->fields[ $item['field'] ]['values'][ $item['value'] ];
				}
			}

			return $fallback;

		} elseif ( isset( $item['connection'] ) ) {
			if ( function_exists( 'p2p_type' ) && $this->p2p_connection_exists( $item['connection'] ) ) {
				$ctype = p2p_type( $item['connection'] );
				if ( $ctype ) {
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
	protected function p2p_connection_exists( string $connection ) : bool {
		if ( ! isset( $this->connection_exists[ $connection ] ) ) {
			$this->connection_exists[ $connection ] = p2p_connection_exists( $connection );
		}
		return $this->connection_exists[ $connection ];
	}

}
