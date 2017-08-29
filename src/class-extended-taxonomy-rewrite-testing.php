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

class Extended_Taxonomy_Rewrite_Testing extends Extended_Rewrite_Testing {

	public $taxo;

	public function __construct( Extended_Taxonomy $taxo ) {
		$this->taxo = $taxo;
	}

	public function get_tests() {

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

		return array(
			$name => $this->get_rewrites( $struct, [] ),
		);

	}

}
