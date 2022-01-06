<?php
declare( strict_types=1 );

namespace ExtCPTs\Walker;

use WP_Term;

/**
 * A term walker class for a dropdown menu.
 */
class Dropdown extends \Walker {

	/**
	 * @var string
	 */
	public $tree_type = 'category';

	/**
	 * @var array<string,string>
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
	 * @param array<string,mixed> $args Optional arguments.
	 */
	public function __construct( $args = null ) {
		if ( $args && isset( $args['field'] ) ) {
			$this->field = $args['field'];
		}
	}

	/**
	 * Start the element output.
	 *
	 * @param string              $output            Passed by reference. Used to append additional content.
	 * @param WP_Term             $object            Term data object.
	 * @param int                 $depth             Depth of term in reference to parents.
	 * @param array<string,mixed> $args              Optional arguments.
	 * @param int                 $current_object_id Current object ID.
	 * @return void
	 */
	public function start_el( &$output, $object, $depth = 0, $args = [], $current_object_id = 0 ) {
		$pad = str_repeat( '&nbsp;', $depth * 3 );
		$tax = get_taxonomy( $args['taxonomy'] );

		if ( ! $tax ) {
			return;
		}

		if ( $this->field ) {
			$value = $object->{$this->field};
		} else {
			$value = $tax->hierarchical ? $object->term_id : $object->name;
		}

		if ( empty( $object->term_id ) && ! $tax->hierarchical ) {
			$value = '';
		}

		$cat_name = apply_filters( 'list_cats', $object->name, $object );
		$output .= "\t<option class=\"level-{$depth}\" value=\"" . esc_attr( $value ) . '"';

		if ( isset( $args['selected_cats'] ) && in_array( $value, (array) $args['selected_cats'] ) ) {
			$output .= ' selected="selected"';
		} elseif ( isset( $args['selected'] ) && in_array( $object->term_id, (array) $args['selected'] ) ) {
			$output .= ' selected="selected"';
		}

		$output .= '>';
		$output .= $pad . esc_html( $cat_name );

		if ( $args['show_count'] ) {
			$output .= '&nbsp;&nbsp;(' . esc_html( number_format_i18n( $object->count ) ) . ')';
		}

		$output .= "</option>\n";
	}

}
