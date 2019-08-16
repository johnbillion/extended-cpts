<?php
declare( strict_types=1 );

/**
 * @codeCoverageIgnore
 */
class Extended_CPT_Rewrite_Testing extends Extended_Rewrite_Testing {

	/**
	 * @var Extended_CPT
	 */
	public $cpt;

	public function __construct( Extended_CPT $cpt ) {
		$this->cpt = $cpt;
	}

	public function get_tests() : array {
		global $wp_rewrite;

		/** @var \WP_Rewrite $wp_rewrite */

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
