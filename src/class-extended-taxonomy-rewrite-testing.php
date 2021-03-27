<?php
declare( strict_types=1 );

class Extended_Taxonomy_Rewrite_Testing extends Extended_Rewrite_Testing {

	/**
	 * @var Extended_Taxonomy
	 */
	public $taxo;

	public function __construct( Extended_Taxonomy $taxo ) {
		$this->taxo = $taxo;
	}

	public function get_tests(): array {
		global $wp_rewrite;

		if ( ! $wp_rewrite->using_permalinks() ) {
			return [];
		}

		if ( ! isset( $wp_rewrite->extra_permastructs[ $this->taxo->taxonomy ] ) ) {
			return [];
		}

		$struct     = $wp_rewrite->extra_permastructs[ $this->taxo->taxonomy ];
		$tax        = get_taxonomy( $this->taxo->taxonomy );
		$name       = sprintf( '%s (%s)', $tax->labels->name, $this->taxo->taxonomy );

		return [
			$name => $this->get_rewrites( $struct, [] ),
		];
	}

}
