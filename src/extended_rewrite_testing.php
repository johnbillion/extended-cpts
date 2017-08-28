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

/**
 * @codeCoverageIgnore
 */
abstract class Extended_Rewrite_Testing {

	abstract public function get_tests();

	public function get_rewrites( array $struct, array $additional ) : array {

		global $wp_rewrite;

		if ( ! $wp_rewrite->using_permalinks() ) {
			return [];
		}

		$new   = [];
		$rules = $wp_rewrite->generate_rewrite_rules(
			$struct['struct'],
			$struct['ep_mask'],
			$struct['paged'],
			$struct['feed'],
			$struct['forcomments'],
			$struct['walk_dirs'],
			$struct['endpoints']
		);
		$rules = array_merge( $rules, $additional );
		$feedregex = implode( '|', $wp_rewrite->feeds );
		$replace   = [
			'(.+?)'          => 'hello',
			'.+?'            => 'hello',
			'([^/]+)'        => 'world',
			'[^/]+'          => 'world',
			'(?:/([0-9]+))?' => '/456',
			'([0-9]{4})'     => date( 'Y' ),
			'[0-9]{4}'       => date( 'Y' ),
			'([0-9]{1,2})'   => date( 'm' ),
			'[0-9]{1,2}'     => date( 'm' ),
			'([0-9]{1,})'    => '123',
			'[0-9]{1,}'      => '789',
			'([0-9]+)'       => date( 'd' ),
			'[0-9]+'         => date( 'd' ),
			"({$feedregex})" => end( $wp_rewrite->feeds ),
			'/?'             => '/',
			'$'              => '',
		];

		foreach ( $rules as $regex => $result ) {
			$regex  = str_replace( array_keys( $replace ), $replace, $regex );
			// Change '$2' to '$matches[2]'
			$result = preg_replace( '/\$([0-9]+)/', '\$matches[$1]', $result );
			$new[ "/{$regex}" ] = $result;
			if ( false !== strpos( $regex, $replace['(?:/([0-9]+))?'] ) ) {
				// Add an extra rule for this optional block
				$regex = str_replace( $replace['(?:/([0-9]+))?'], '', $regex );
				$new[ "/{$regex}" ] = $result;
			}
		}

		return $new;

	}

}
