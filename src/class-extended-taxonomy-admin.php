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

class Extended_Taxonomy_Admin {

	/**
	 * Default arguments for custom taxonomies.
	 *
	 * @var array
	 */
	protected $defaults = [
		'meta_box'          => null,  # Custom arg
		'dashboard_glance'  => false, # Custom arg
		'checked_ontop'     => null,  # Custom arg
		'admin_cols'        => null,  # Custom arg
		'required'          => false, # Custom arg
	];

	public $taxo;
	public $args;
	protected $_cols;
	protected $the_cols = null;

	/**
	* Class constructor.
	*
	* @param Extended_Taxonomy $taxo An extended taxonomy object.
	* @param array             $args Optional. The admin arguments.
	*/
	public function __construct( Extended_Taxonomy $taxo, array $args = [] ) {

		$this->taxo = $taxo;

		# Merge our args with the defaults:
		$this->args = array_merge( $this->defaults, $args );

		# Set checked on top to false unless we're using the default meta box:
		if ( null === $this->args['checked_ontop'] ) {
			$this->args['checked_ontop'] = empty( $this->args['meta_box'] );
		}

		# Meta boxes:
		if ( $this->taxo->args['exclusive'] || isset( $this->args['meta_box'] ) ) {
			add_action( 'add_meta_boxes', [ $this, 'meta_boxes' ], 10, 2 );
		}

		# 'At a Glance' dashboard panels:
		if ( $this->args['dashboard_glance'] ) {
			add_filter( 'dashboard_glance_items', [ $this, 'glance_items' ] );
		}

		# Term updated messages:
		add_filter( 'term_updated_messages', [ $this, 'term_updated_messages' ], 1, 2 );

		# Admin columns:
		if ( $this->args['admin_cols'] ) {
			add_filter( "manage_edit-{$this->taxo->taxonomy}_columns",  [ $this, '_log_default_cols' ], 0 );
			add_filter( "manage_edit-{$this->taxo->taxonomy}_columns",  [ $this, 'cols' ] );
			add_action( "manage_{$this->taxo->taxonomy}_custom_column", [ $this, 'col' ], 10, 3 );
		}

	}

	/**
	 * Logs the default columns so we don't remove any custom columns added by other plugins.
	 *
	 * @param  array $cols The default columns for this taxonomy screen
	 * @return array       The default columns for this taxonomy screen
	 */
	public function _log_default_cols( array $cols ) {

		$this->_cols = $cols;
		return $this->_cols;

	}

	/**
	 * Add columns to the admin screen for this taxonomy.
	 *
	 * Each item in the `admin_cols` array is either a string name of an existing column, or an associative
	 * array of information for a custom column.
	 *
	 * Defining a custom column is easy. Just define an array which includes the column title, column
	 * type, and optional callback function. You can display columns for term meta or custom functions.
	 *
	 * The example below adds two columns; one which displays the value of the term's `term_updated` meta
	 * key, and one which calls a custom callback function:
	 *
	 *     register_extended_taxonomy( 'foo', 'bar', array(
	 *         'admin_cols' => array(
	 *             'foo_updated' => array(
	 *                 'title'    => 'Updated',
	 *                 'meta_key' => 'term_updated'
	 *             ),
	 *             'foo_bar' => array(
	 *                 'title'    => 'Example',
	 *                 'function' => 'my_custom_callback'
	 *             )
	 *         )
	 *     ) );
	 *
	 * That's all you need to do. The columns will handle safely outputting the data
	 * (escaping text, and comma-separating taxonomy terms). No more messing about with all of those
	 * annoyingly named column filters and actions.
	 *
	 * Each item in the `admin_cols` array must contain one of the following elements which defines the column type:
	 *
	 *  - meta_key - A term meta key
	 *  - function - The name of a callback function
	 *
	 * The value for the corresponding term meta are safely escaped and output into the column.
	 *
	 * There are a few optional elements:
	 *
	 *  - title - Generated from the field if not specified.
	 *  - function - The name of a callback function for the column (eg. `my_function`) which gets called
	 *    instead of the built-in function for handling that column. The function is passed the term ID as
	 *    its first parameter.
	 *  - date_format - This is used with the `meta_key` column type. The value of the meta field will be
	 *    treated as a timestamp if this is present. Unix and MySQL format timestamps are supported in the
	 *    meta value. Pass in boolean true to format the date according to the 'Date Format' setting, or pass
	 *    in a valid date formatting string (eg. `d/m/Y H:i:s`).
	 *  - cap - A capability required in order for this column to be displayed to the current user. Defaults
	 *    to null, meaning the column is shown to all users.
	 *
	 * Note that sortable admin columns are not yet supported.
	 *
	 * @param  array $cols Associative array of columns
	 * @return array       Updated array of columns
	 */
	public function cols( array $cols ) {

		// This function gets called multiple times, so let's cache it for efficiency:
		if ( isset( $this->the_cols ) ) {
			return $this->the_cols;
		}

		$new_cols = [];
		$keep = array(
			'cb',
			'name',
			'description',
			'slug',
		);

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
			} elseif ( is_array( $col ) ) {
				if ( isset( $col['cap'] ) && ! current_user_can( $col['cap'] ) ) {
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

		$this->the_cols = $new_cols;
		return $this->the_cols;

	}

	/**
	 * Output the column data for our custom columns.
	 *
	 * @param string $string  Blank string.
	 * @param string $col     Name of the column.
	 * @param int    $term_id Term ID.
	 */
	public function col( $string, $col, $term_id ) {

		# Shorthand:
		$c = $this->args['admin_cols'];

		# We're only interested in our custom columns:
		$custom_cols = array_filter( array_keys( $c ) );

		if ( ! in_array( $col, $custom_cols, true ) ) {
			return;
		}

		if ( isset( $c[ $col ]['function'] ) ) {
			call_user_func( $c[ $col ]['function'], $term_id );
		} elseif ( isset( $c[ $col ]['meta_key'] ) ) {
			$this->col_term_meta( $c[ $col ]['meta_key'], $c[ $col ], $term_id );
		}

	}

	/**
	 * Output column data for a term meta field.
	 *
	 * @param string $meta_key The term meta key
	 * @param array  $args     Array of arguments for this field
	 * @param int    $term_id  Term ID.
	 */
	public function col_term_meta( $meta_key, array $args, $term_id ) {

		$vals = get_term_meta( $term_id, $meta_key, false );
		$echo = [];
		sort( $vals );

		if ( isset( $args['date_format'] ) ) {

			if ( true === $args['date_format'] ) {
				$args['date_format'] = get_option( 'date_format' );
			}

			foreach ( $vals as $val ) {

				if ( is_numeric( $val ) ) {
					$echo[] = date( $args['date_format'], $val );
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
	 * Get a sensible title for the current item (usually the arguments array for a column)
	 *
	 * @param  array  $item An array of arguments
	 * @return string       The item title
	 */
	protected function get_item_title( array $item ) {

		if ( isset( $item['meta_key'] ) ) {
			return ucwords( trim( str_replace( [ '_', '-' ], ' ', $item['meta_key'] ) ) );
		} else {
			return '';
		}

	}

	/**
	 * Remove the default meta box from the post editing screen and add our custom meta box.
	 *
	 * @param string $object_type The object type (eg. the post type)
	 * @param mixed  $object      The object (eg. a WP_Post object)
	 * @return null
	 */
	public function meta_boxes( $object_type, $object ) {

		if ( ! is_a( $object, 'WP_Post' ) ) {
			return;
		}

		$post_type = $object_type;
		$post      = $object;
		$taxos     = get_post_taxonomies( $post );

		if ( in_array( $this->taxo->taxonomy, $taxos, true ) ) {

			$tax = get_taxonomy( $this->taxo->taxonomy );

			# Remove default meta box:
			if ( $this->taxo->args['hierarchical'] ) {
				remove_meta_box( "{$this->taxo->taxonomy}div", $post_type, 'side' );
			} else {
				remove_meta_box( "tagsdiv-{$this->taxo->taxonomy}", $post_type, 'side' );
			}

			if ( ! current_user_can( $tax->cap->assign_terms ) ) {
				return;
			}

			if ( $this->args['meta_box'] ) {

				# Set the 'meta_box' argument to the actual meta box callback function name:
				if ( 'simple' === $this->args['meta_box'] ) {
					if ( $this->taxo->args['exclusive'] ) {
						$this->args['meta_box'] = [ $this, 'meta_box_radio' ];
					} else {
						$this->args['meta_box'] = [ $this, 'meta_box_simple' ];
					}
				} elseif ( 'radio' === $this->args['meta_box'] ) {
					$this->taxo->args['exclusive'] = true;
					$this->args['meta_box'] = [ $this, 'meta_box_radio' ];
				} elseif ( 'dropdown' === $this->args['meta_box'] ) {
					$this->taxo->args['exclusive'] = true;
					$this->args['meta_box'] = [ $this, 'meta_box_dropdown' ];
				}

				# Add the meta box, using the plural or singular taxonomy label where relevant:
				if ( $this->taxo->args['exclusive'] ) {
					add_meta_box( "{$this->taxo->taxonomy}div", $tax->labels->singular_name, $this->args['meta_box'], $post_type, 'side' );
				} else {
					add_meta_box( "{$this->taxo->taxonomy}div", $tax->labels->name, $this->args['meta_box'], $post_type, 'side' );
				}
			} elseif ( false !== $this->args['meta_box'] ) {

				# This must be an 'exclusive' taxonomy. Add the radio meta box:
				add_meta_box( "{$this->taxo->taxonomy}div", $tax->labels->singular_name, [ $this, 'meta_box_radio' ], $post_type, 'side' );

			}
		}

	}

	/**
	 * Display the 'radio' meta box on the post editing screen.
	 *
	 * Uses the Walker_ExtendedTaxonomyRadios class for the walker.
	 *
	 * @param WP_Post $post     The post object.
	 * @param array   $meta_box The meta box arguments.
	 * @return null
	 */
	public function meta_box_radio( WP_Post $post, array $meta_box ) {
		require_once __DIR__ . '/class-walker-extendedtaxonomyradios.php';

		$walker = new Walker_ExtendedTaxonomyRadios;
		$this->do_meta_box( $post, $walker, true, 'checklist' );

	}

	/**
	 * Display the 'dropdown' meta box on the post editing screen.
	 *
	 * Uses the Walker_ExtendedTaxonomyDropdown class for the walker.
	 *
	 * @param WP_Post $post     The post object.
	 * @param array   $meta_box The meta box arguments.
	 * @return null
	 */
	public function meta_box_dropdown( WP_Post $post, array $meta_box ) {
		require_once __DIR__ . '/class-walker-extendedtaxonomydropdown.php';

		$walker = new Walker_ExtendedTaxonomyDropdown;
		$this->do_meta_box( $post, $walker, true, 'dropdown' );

	}

	/**
	 * Display the 'simple' meta box on the post editing screen.
	 *
	 * @param WP_Post $post     The post object.
	 * @param array   $meta_box The meta box arguments.
	 * @return null
	 */
	public function meta_box_simple( WP_Post $post, array $meta_box ) {

		$this->do_meta_box( $post );

	}

	/**
	 * Display a meta box on the post editing screen.
	 *
	 * @param WP_Post $post      The post object.
	 * @param Walker  $walker    Optional. A term walker.
	 * @param bool    $show_none Optional. Whether to include a 'none' item in the term list. Default false.
	 * @param string  $type      Optional. The taxonomy list type (checklist or dropdown). Default 'checklist'.
	 * @return null
	 */
	protected function do_meta_box( WP_Post $post, Walker $walker = null, $show_none = false, $type = 'checklist' ) {

		$taxonomy = $this->taxo->taxonomy;
		$tax      = get_taxonomy( $taxonomy );
		$selected = wp_get_object_terms( $post->ID, $taxonomy, [
			'fields' => 'ids',
		] );

		if ( $show_none ) {
			if ( isset( $tax->labels->no_item ) ) {
				$none = $tax->labels->no_item;
			} else {
				$none = esc_html__( 'Not specified', 'extended-cpts' );
			}
		} else {
			$none = '';
		}

		/**
		 * Execute code before the taxonomy meta box content outputs to the page.
		 *
		 * @since 2.0.0
		 *
		 * @param WP_Taxonomy $tax  The current taxonomy object.
		 * @param WP_Post     $post The current post object.
		 * @param string      $type The taxonomy list type ('checklist' or 'dropdown').
		 */
		do_action( 'ext-taxos/meta_box/before', $tax, $post, $type );

		?>
		<div id="taxonomy-<?php echo esc_attr( $taxonomy ); ?>" class="categorydiv">

			<?php

			switch ( $type ) {

				case 'dropdown':
					wp_dropdown_categories( array(
						'option_none_value' => ( is_taxonomy_hierarchical( $taxonomy ) ? '-1' : '' ),
						'show_option_none'  => $none,
						'hide_empty'        => false,
						'hierarchical'      => true,
						'show_count'        => false,
						'orderby'           => 'name',
						'selected'          => reset( $selected ),
						'id'                => "{$taxonomy}dropdown",
						'name'              => "tax_input[{$taxonomy}]",
						'taxonomy'          => $taxonomy,
						'walker'            => $walker,
						'required'          => $this->args['required'],
					) );
					break;
				case 'checklist':
				default:
					?>
					<style type="text/css">
						/* Style for the 'none' item: */
						#<?php echo esc_attr( $taxonomy ); ?>-0 {
							color: #888;
							border-top: 1px solid #eee;
							margin-top: 5px;
							padding-top: 5px;
						}
					</style>

					<input type="hidden" name="tax_input[<?php echo esc_attr( $taxonomy ); ?>][]" value="0" />

					<ul id="<?php echo esc_attr( $taxonomy ); ?>checklist" class="list:<?php echo esc_attr( $taxonomy ); ?> categorychecklist form-no-clear">
						<?php

						# Standard WP Walker_Category_Checklist does not cut it
						if ( empty( $walker ) || ! is_a( $walker, 'Walker' ) ) {
							require_once __DIR__ . '/class-walker-extendedtaxonomycheckboxes.php';
							$walker = new Walker_ExtendedTaxonomyCheckboxes;
						}

						# Output the terms:
						wp_terms_checklist( $post->ID, array(
							'taxonomy'      => $taxonomy,
							'walker'        => $walker,
							'selected_cats' => $selected,
							'checked_ontop' => $this->args['checked_ontop'],
						) );

						# Output the 'none' item:
						if ( $show_none ) {
							$output = '';
							$o = (object) array(
								'term_id' => 0,
								'name'    => $none,
								'slug'    => 'none',
							);
							if ( empty( $selected ) ) {
								$_selected = [ 0 ];
							} else {
								$_selected = $selected;
							}
							$args = array(
								'taxonomy'      => $taxonomy,
								'selected_cats' => $_selected,
								'disabled'      => false,
							);
							$walker->start_el( $output, $o, 1, $args );
							$walker->end_el( $output, $o, 1, $args );
							echo $output; // WPCS: XSS ok.
						}

						?>

					</ul>

					<?php
					break;

			}

		?>

		</div>
		<?php

		/**
		 * Execute code after the taxonomy meta box content outputs to the page.
		 *
		 * @since 2.0.0
		 *
		 * @param WP_Taxonomy $tax  The current taxonomy object.
		 * @param WP_Post     $post The current post object.
		 * @param string      $type The taxonomy list type ('checklist' or 'dropdown').
		 */
		do_action( 'ext-taxos/meta_box/after', $tax, $post, $type );

	}

	/**
	 * Add our taxonomy to the 'At a Glance' widget on the WordPress 3.8+ dashboard.
	 *
	 * @param  array $items Array of items to display on the widget.
	 * @return array        Updated array of items.
	 */
	public function glance_items( array $items ) {

		$taxonomy = get_taxonomy( $this->taxo->taxonomy );

		if ( ! current_user_can( $taxonomy->cap->manage_terms ) ) {
			return $items;
		}
		if ( $taxonomy->_builtin ) {
			return $items;
		}

		# Get the labels and format the counts:
		$count = wp_count_terms( $this->taxo->taxonomy );
		$text  = self::n( $taxonomy->labels->singular_name, $taxonomy->labels->name, $count );
		$num   = number_format_i18n( $count );

		# This is absolutely not localisable. WordPress 3.8 didn't add a new taxonomy label.
		$url = add_query_arg( [
			'taxonomy'  => $this->taxo->taxonomy,
			'post_type' => reset( $taxonomy->object_type ),
		], admin_url( 'edit-tags.php' ) );
		$text = '<a href="' . esc_url( $url ) . '">' . esc_html( $num . ' ' . $text ) . '</a>';

		# Go!
		$items[] = $text;

		return $items;

	}

	/**
	 * Add our term updated messages.
	 *
	 * The messages are as follows:
	 *
	 *   1 => "Term added."
	 *   2 => "Term deleted."
	 *   3 => "Term updated."
	 *   4 => "Term not added."
	 *   5 => "Term not updated."
	 *   6 => "Terms deleted."
	 *
	 * @param string[] $messages An associative array of term updated messages with taxonomy name as keys.
	 * @return array Updated array of term updated messages.
	 */
	public function term_updated_messages( array $messages ) {

		$messages[ $this->taxo->taxonomy ] = array(
			1 => esc_html( sprintf( '%s added.', $this->taxo->tax_singular ) ),
			2 => esc_html( sprintf( '%s deleted.', $this->taxo->tax_singular ) ),
			3 => esc_html( sprintf( '%s updated.', $this->taxo->tax_singular ) ),
			4 => esc_html( sprintf( '%s not added.', $this->taxo->tax_singular ) ),
			5 => esc_html( sprintf( '%s not updated.', $this->taxo->tax_singular ) ),
			6 => esc_html( sprintf( '%s deleted.', $this->taxo->tax_plural ) ),
		);

		return $messages;

	}

	/**
	 * A non-localised version of _n()
	 *
	 * @param string $single The text that will be used if $number is 1
	 * @param string $plural The text that will be used if $number is not 1
	 * @param int $number The number to compare against to use either $single or $plural
	 * @return string Either $single or $plural text
	 */
	public static function n( $single, $plural, $number ) {

		return ( 1 === intval( $number ) ) ? $single : $plural;

	}

}
