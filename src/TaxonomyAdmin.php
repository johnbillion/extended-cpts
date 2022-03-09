<?php
declare( strict_types=1 );

namespace ExtCPTs;

use WP_Post;
use WP_Taxonomy;

class TaxonomyAdmin {

	/**
	 * Default arguments for custom taxonomies.
	 *
	 * @var array<string,mixed>
	 */
	protected array $defaults = [
		'meta_box'         => null,  # Custom arg
		'dashboard_glance' => false, # Custom arg
		'checked_ontop'    => null,  # Custom arg
		'admin_cols'       => null,  # Custom arg
		'required'         => false, # Custom arg
	];

	public Taxonomy $taxo;

	/**
	 * @var array<string,mixed>
	 */
	public array $args;

	/**
	 * @var array<string,string>
	 */
	protected array $_cols;

	/**
	 * @var array<string,string>
	 */
	protected ?array $the_cols = null;

	/**
	* Class constructor.
	*
	* @param Taxonomy            $taxo An extended taxonomy object.
	* @param array<string,mixed> $args Optional. The admin arguments.
	*/
	public function __construct( Taxonomy $taxo, array $args = [] ) {
		$this->taxo = $taxo;

		# Merge our args with the defaults:
		$this->args = array_merge( $this->defaults, $args );

		# Set checked on top to false unless we're using the default meta box:
		if ( null === $this->args['checked_ontop'] ) {
			$this->args['checked_ontop'] = empty( $this->args['meta_box'] );
		}
	}

	/**
	 * Initialise the admin features of the taxonomy by adding the necessary actions and filters.
	 */
	public function init(): void {
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
			add_filter( "manage_{$this->taxo->taxonomy}_custom_column", [ $this, 'col' ], 10, 3 );
		}

		/**
		 * Fired when the extended taxonomy admin instance is set up.
		 *
		 * @since 5.0.0
		 *
		 * @param \ExtCPTs\TaxonomyAdmin $instance The extended taxonomy admin instance.
		 */
		do_action( "ext-taxos/{$this->taxo->taxonomy}/admin-instance", $this );
	}

	/**
	 * Logs the default columns so we don't remove any custom columns added by other plugins.
	 *
	 * @param  array<string,string> $cols The default columns for this taxonomy screen.
	 * @return array<string,string> The default columns for this taxonomy screen.
	 */
	public function _log_default_cols( array $cols ): array {
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
	 * @param  array<string,string> $cols Associative array of columns.
	 * @return array<string,string> Updated array of columns.
	 */
	public function cols( array $cols ): array {
		// This function gets called multiple times, so let's cache it for efficiency:
		if ( isset( $this->the_cols ) ) {
			return $this->the_cols;
		}

		$new_cols = [];
		$keep = [
			'cb',
			'name',
			'description',
			'slug',
		];

		# Add existing columns we want to keep:
		foreach ( $cols as $id => $title ) {
			if ( in_array( $id, $keep, true ) && ! isset( $this->args['admin_cols'][ $id ] ) ) {
				$new_cols[ $id ] = $title;
			}
		}

		/** @var array<string,(string|mixed[])> */
		$admin_cols = array_filter( $this->args['admin_cols'] );

		# Add our custom columns:
		foreach ( $admin_cols as $id => $col ) {
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

				if ( isset( $col['title_cb'] ) ) {
					$new_cols[ $id ] = call_user_func( $col['title_cb'], $col );
				} else {
					$title = esc_html( $this->get_item_title( $col, $id ) );

					if ( isset( $col['title_icon'] ) ) {
						$title = sprintf(
							'<span class="dashicons %s" aria-hidden="true"></span><span class="screen-reader-text">%s</span>',
							esc_attr( $col['title_icon'] ),
							$title
						);
					}

					$new_cols[ $id ] = $title;
				}
			}
		}

		# Re-add any custom columns:
		$custom = array_diff_key( $cols, $this->_cols );
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
	 * @return string Blank string.
	 */
	public function col( string $string, string $col, int $term_id ): string {
		# Shorthand:
		$c = $this->args['admin_cols'];

		# We're only interested in our custom columns:
		$custom_cols = array_filter( array_keys( $c ) );

		if ( ! in_array( $col, $custom_cols, true ) ) {
			return $string;
		}

		if ( isset( $c[ $col ]['function'] ) ) {
			call_user_func( $c[ $col ]['function'], $term_id );
		} elseif ( isset( $c[ $col ]['meta_key'] ) ) {
			$this->col_term_meta( $c[ $col ]['meta_key'], $c[ $col ], $term_id );
		}

		return $string;
	}

	/**
	 * Output column data for a term meta field.
	 *
	 * @param string              $meta_key The term meta key.
	 * @param array<string,mixed> $args     Array of arguments for this field.
	 * @param int                 $term_id  Term ID.
	 */
	public function col_term_meta( string $meta_key, array $args, int $term_id ): void {
		$vals = get_term_meta( $term_id, $meta_key, false );
		$echo = [];

		sort( $vals );

		if ( isset( $args['date_format'] ) ) {
			if ( true === $args['date_format'] ) {
				$args['date_format'] = get_option( 'date_format' );
			}

			foreach ( $vals as $val ) {
				if ( is_numeric( $val ) ) {
					$echo[] = date( $args['date_format'], (int) $val );
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
	 * Returns a sensible title for the current item (usually the arguments array for a column).
	 *
	 * @param array<string,mixed> $item An array of arguments.
	 * @param string              $fallback Fallback item title.
	 * @return string The item title.
	 */
	protected function get_item_title( array $item, string $fallback = '' ): string {
		if ( isset( $item['title'] ) ) {
			return $item['title'];
		} elseif ( isset( $item['meta_key'] ) ) {
			return ucwords( trim( str_replace( [ '_', '-' ], ' ', $item['meta_key'] ) ) );
		}

		return $fallback;
	}

	/**
	 * Removes the default meta box from the post editing screen and adds our custom meta box.
	 *
	 * @param string $object_type The object type (eg. the post type).
	 * @param mixed  $object      The object (eg. a WP_Post object).
	 */
	public function meta_boxes( string $object_type, $object ): void {
		if ( ! is_a( $object, 'WP_Post' ) ) {
			return;
		}

		$post_type = $object_type;
		$post = $object;
		$taxos = get_post_taxonomies( $post );

		if ( in_array( $this->taxo->taxonomy, $taxos, true ) ) {
			/** @var WP_Taxonomy */
			$tax = get_taxonomy( $this->taxo->taxonomy );

			# Remove default meta box from classic editor:
			if ( $this->taxo->args['hierarchical'] ) {
				remove_meta_box( "{$this->taxo->taxonomy}div", $post_type, 'side' );
			} else {
				remove_meta_box( "tagsdiv-{$this->taxo->taxonomy}", $post_type, 'side' );
			}

			# Remove default meta box from block editor:
			wp_add_inline_script(
				'wp-edit-post',
				sprintf(
					'wp.data.dispatch( "core/edit-post" ).removeEditorPanel( "taxonomy-panel-%s" );',
					$this->taxo->taxonomy
				)
			);

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
	 * Displays the 'radio' meta box on the post editing screen.
	 *
	 * Uses the Walker\Radios class for the walker.
	 *
	 * @param WP_Post             $post     The post object.
	 * @param array<string,mixed> $meta_box The meta box arguments.
	 */
	public function meta_box_radio( WP_Post $post, array $meta_box ): void {
		$walker = new Walker\Radios();
		$this->do_meta_box( $post, $walker, true, 'checklist' );
	}

	/**
	 * Displays the 'dropdown' meta box on the post editing screen.
	 *
	 * Uses the Walker\Dropdown class for the walker.
	 *
	 * @param WP_Post             $post     The post object.
	 * @param array<string,mixed> $meta_box The meta box arguments.
	 */
	public function meta_box_dropdown( WP_Post $post, array $meta_box ): void {
		$walker = new Walker\Dropdown();
		$this->do_meta_box( $post, $walker, true, 'dropdown' );
	}

	/**
	 * Displays the 'simple' meta box on the post editing screen.
	 *
	 * @param WP_Post             $post     The post object.
	 * @param array<string,mixed> $meta_box The meta box arguments.
	 */
	public function meta_box_simple( WP_Post $post, array $meta_box ): void {
		$this->do_meta_box( $post );
	}

	/**
	 * Displays a meta box on the post editing screen.
	 *
	 * @param WP_Post $post      The post object.
	 * @param \Walker $walker    Optional. A term walker.
	 * @param bool    $show_none Optional. Whether to include a 'none' item in the term list. Default false.
	 * @param string  $type      Optional. The taxonomy list type (checklist or dropdown). Default 'checklist'.
	 */
	protected function do_meta_box( WP_Post $post, \Walker $walker = null, bool $show_none = false, string $type = 'checklist' ): void {
		$taxonomy = $this->taxo->taxonomy;
		/** @var WP_Taxonomy */
		$tax = get_taxonomy( $taxonomy );
		/** @var array<int,int> */
		$selected = wp_get_object_terms(
			$post->ID,
			$taxonomy,
			[
				'fields' => 'ids',
			]
		);

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
					printf(
						'<label for="%1$s" class="screen-reader-text">%2$s</label>',
						esc_attr( "{$taxonomy}dropdown" ),
						esc_html( $tax->labels->singular_name )
					);
					wp_dropdown_categories(
						[
							'option_none_value' => ( is_taxonomy_hierarchical( $taxonomy ) ? '-1' : '' ),
							'show_option_none'  => $none,
							'hide_empty'        => false,
							'hierarchical'      => true,
							'show_count'        => false,
							'orderby'           => 'name',
							'selected'          => reset( $selected ) ?: 0,
							'id'                => "{$taxonomy}dropdown",
							'name'              => is_taxonomy_hierarchical( $taxonomy ) ? "tax_input[{$taxonomy}][]" : "tax_input[{$taxonomy}]",
							'taxonomy'          => $taxonomy,
							'walker'            => $walker,
							'required'          => $this->args['required'],
						]
					);
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
						if ( ! $walker ) {
							$walker = new Walker\Checkboxes();
						}

						# Output the terms:
						wp_terms_checklist(
							$post->ID,
							[
								'taxonomy'      => $taxonomy,
								'walker'        => $walker,
								'selected_cats' => $selected,
								'checked_ontop' => $this->args['checked_ontop'],
							]
						);

						# Output the 'none' item:
						if ( $show_none ) {
							$output = '';
							$o = (object) [
								'term_id' => 0,
								'name'    => $none,
								'slug'    => 'none',
							];
							if ( empty( $selected ) ) {
								$_selected = [ 0 ];
							} else {
								$_selected = $selected;
							}
							$args = [
								'taxonomy'      => $taxonomy,
								'selected_cats' => $_selected,
								'disabled'      => false,
							];
							$walker->start_el( $output, $o, 1, $args );
							$walker->end_el( $output, $o, 1, $args );

							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo $output;
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
	 * Adds our taxonomy to the 'At a Glance' widget on the dashboard.
	 *
	 * @param array<int,string> $items Array of items to display on the widget.
	 * @return array<int,string> Updated array of items.
	 */
	public function glance_items( array $items ): array {
		/** @var WP_Taxonomy */
		$taxonomy = get_taxonomy( $this->taxo->taxonomy );

		if ( ! current_user_can( $taxonomy->cap->manage_terms ) ) {
			return $items;
		}
		if ( $taxonomy->_builtin ) {
			return $items;
		}

		# Get the labels and format the counts:
		$count = wp_count_terms( $this->taxo->taxonomy );

		if ( is_wp_error( $count ) ) {
			return $items;
		}

		$text = self::n( $taxonomy->labels->singular_name, $taxonomy->labels->name, (int) $count );
		$num = number_format_i18n( (int) $count );

		# This is absolutely not localisable. WordPress 3.8 didn't add a new taxonomy label.
		$url = add_query_arg(
			[
				'taxonomy'  => $this->taxo->taxonomy,
				'post_type' => reset( $taxonomy->object_type ),
			],
			admin_url( 'edit-tags.php' )
		);
		$text = '<a href="' . esc_url( $url ) . '" class="taxo-' . esc_attr( $this->taxo->taxonomy ) . '-count">' . esc_html( $num . ' ' . $text ) . '</a>';

		# Go!
		$items[] = $text;

		return $items;
	}

	/**
	 * Adds our term updated messages.
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
	 * @param array<string, array<int, string>> $messages An array of term updated message arrays keyed by taxonomy name.
	 * @return array<string, array<int, string>> Updated array of term updated messages.
	 */
	public function term_updated_messages( array $messages ): array {
		$messages[ $this->taxo->taxonomy ] = [
			1 => esc_html( sprintf( '%s added.', $this->taxo->tax_singular ) ),
			2 => esc_html( sprintf( '%s deleted.', $this->taxo->tax_singular ) ),
			3 => esc_html( sprintf( '%s updated.', $this->taxo->tax_singular ) ),
			4 => esc_html( sprintf( '%s not added.', $this->taxo->tax_singular ) ),
			5 => esc_html( sprintf( '%s not updated.', $this->taxo->tax_singular ) ),
			6 => esc_html( sprintf( '%s deleted.', $this->taxo->tax_plural ) ),
		];

		return $messages;
	}

	/**
	 * A non-localised version of _n()
	 *
	 * @param string $single The text that will be used if $number is 1.
	 * @param string $plural The text that will be used if $number is not 1.
	 * @param int    $number The number to compare against to use either $single or $plural.
	 * @return string Either $single or $plural text.
	 */
	public static function n( string $single, string $plural, int $number ): string {
		return ( 1 === intval( $number ) ) ? $single : $plural;
	}

}
