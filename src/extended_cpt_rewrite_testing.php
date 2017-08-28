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
class Extended_CPT_Rewrite_Testing extends Extended_Rewrite_Testing {

	public $cpt;

	public function __construct( Extended_CPT $cpt ) {
		$this->cpt = $cpt;
	}

	public function get_tests() : array {

		global $wp_rewrite;

		if ( ! $wp_rewrite->using_permalinks() ) {
			return [];
		}

		if ( ! isset( $wp_rewrite->extra_permastructs[ $this->cpt->post_type ] ) ) {
			return [];
		}

		$struct     = $wp_rewrite->extra_permastructs[ $this->cpt->post_type ];
		$pto        = get_post_type_object( $this->cpt->post_type );
		$name       = sprintf( '%s (%s)', $pto->labels->name, $this->cpt->post_type );
		$additional = [];

		// Post type archive rewrites are generated separately. See the `has_archive` handling in `register_post_type()`.
		if ( $pto->has_archive ) {
			$archive_slug = ( true === $pto->has_archive ) ? $pto->rewrite['slug'] : $pto->has_archive;

			if ( $pto->rewrite['with_front'] ) {
				$archive_slug = substr( $wp_rewrite->front, 1 ) . $archive_slug;
			} else {
				$archive_slug = $wp_rewrite->root . $archive_slug;
			}

			$additional[ "{$archive_slug}/?$" ] = "index.php?post_type={$this->cpt->post_type}";

			if ( $pto->rewrite['feeds'] && $wp_rewrite->feeds ) {
				$feeds = '(' . trim( implode( '|', $wp_rewrite->feeds ) ) . ')';
				$additional[ "{$archive_slug}/feed/{$feeds}/?$" ] = "index.php?post_type={$this->cpt->post_type}" . '&feed=$matches[1]';
				$additional[ "{$archive_slug}/{$feeds}/?$" ] = "index.php?post_type={$this->cpt->post_type}" . '&feed=$matches[1]';
			}
			if ( $pto->rewrite['pages'] ) {
				$additional[ "{$archive_slug}/{$wp_rewrite->pagination_base}/([0-9]{1,})/?$" ] = "index.php?post_type={$this->cpt->post_type}" . '&paged=$matches[1]';
			}
		}

		return [
			$name => $this->get_rewrites( $struct, $additional ),
		];

	}

}
