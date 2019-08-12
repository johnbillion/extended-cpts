<?php
declare( strict_types=1 );

/**
 * Walker to output an unordered list of category checkbox <input> elements properly.
 *
 * @uses Walker
 */
class Walker_ExtendedTaxonomyCheckboxes extends Walker {

	/**
	 * @var string
	 */
	public $tree_type = 'category';

	/**
	 * @var array
	 */
	public $db_fields = [
		'parent' => 'parent',
		'id'     => 'term_id',
	];

	/**
	 * @var string
	 */
	public $field = null;

	/**
	 * Class constructor.
	 *
	 * @param array $args Optional arguments.
	 */
	public function __construct( $args = null ) {
		if ( $args && isset( $args['field'] ) ) {
			$this->field = $args['field'];
		}
	}

	/**
	 * Starts the list before the elements are added.
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int    $depth  Depth of term in reference to parents.
	 * @param array  $args   Optional arguments.
	 */
	public function start_lvl( &$output, $depth = 0, $args = [] ) {
		$indent = str_repeat( "\t", $depth );
		$output .= "$indent<ul class='children'>\n";
	}

	/**
	 * Ends the list of after the elements are added.
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int    $depth  Depth of term in reference to parents.
	 * @param array  $args   Optional arguments.
	 */
	public function end_lvl( &$output, $depth = 0, $args = [] ) {
		$indent = str_repeat( "\t", $depth );
		$output .= "$indent</ul>\n";
	}

	/**
	 * Start the element output.
	 *
	 * @param string $output            Passed by reference. Used to append additional content.
	 * @param object $object            Term data object.
	 * @param int    $depth             Depth of term in reference to parents.
	 * @param array  $args              Optional arguments.
	 * @param int    $current_object_id Current object ID.
	 */
	public function start_el( &$output, $object, $depth = 0, $args = [], $current_object_id = 0 ) {
		$tax = get_taxonomy( $args['taxonomy'] );

		if ( $this->field ) {
			$value = $object->{$this->field};
		} else {
			$value = $tax->hierarchical ? $object->term_id : $object->name;
		}

		if ( empty( $object->term_id ) && ! $tax->hierarchical ) {
			$value = '';
		}

		$output .= "\n<li id='{$args['taxonomy']}-{$object->term_id}'>" .
			'<label class="selectit">' .
			'<input value="' . esc_attr( $value ) . '" type="checkbox" name="tax_input[' . esc_attr( $args['taxonomy'] ) . '][]" ' .
				'id="in-' . esc_attr( $args['taxonomy'] ) . '-' . intval( $object->term_id ) . '"' .
				checked( in_array( $object->term_id, (array) $args['selected_cats'] ), true, false ) .
				disabled( empty( $args['disabled'] ), false, false ) .
			' /> ' .
			esc_html( apply_filters( 'the_category', $object->name ) ) .
			'</label>';
	}

	/**
	 * Ends the element output, if needed.
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $object Term data object.
	 * @param int    $depth  Depth of term in reference to parents.
	 * @param array $args Optional arguments.
	 */
	public function end_el( &$output, $object, $depth = 0, $args = [] ) {
		$output .= "</li>\n";
	}

}
