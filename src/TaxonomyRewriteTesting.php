<?php
declare( strict_types=1 );

namespace ExtCPTs;

use WP_Taxonomy;

class TaxonomyRewriteTesting extends ExtendedRewriteTesting {

	public Taxonomy $taxo;

	public function __construct( Taxonomy $taxo ) {
		$this->taxo = $taxo;
	}

	/**
	 * @return array<string,array<string,string>>
	 */
	public function get_tests(): array {
		global $wp_rewrite;

		if ( ! $wp_rewrite->using_permalinks() ) {
			return [];
		}

		if ( ! isset( $wp_rewrite->extra_permastructs[ $this->taxo->taxonomy ] ) ) {
			return [];
		}

		$struct = $wp_rewrite->extra_permastructs[ $this->taxo->taxonomy ];
		/** @var WP_Taxonomy */
		$tax = get_taxonomy( $this->taxo->taxonomy );
		$name = sprintf( '%s (%s)', $tax->labels->name, $this->taxo->taxonomy );

		return [
			$name => $this->get_rewrites( $struct, [] ),
		];
	}

}
