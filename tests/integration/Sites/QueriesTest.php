<?php
declare( strict_types=1 );

namespace ExtCPTs\Tests\Sites;

use ExtCPTs\Tests\Site;
use WP_Query;

class Queries extends Site {

	public function testDefaultPostTypeQueryNotAffected(): void {

		$query = $this->get_query( array(
			'post_type' => 'post',
		) );

		self::assertEquals( 1, $query->found_posts );

		self::assertSame( '',     $query->get( 'orderby' ) ); // date
		self::assertSame( 'DESC', $query->get( 'order' ) );
		self::assertSame( '',     $query->get( 'meta_key' ) );
		self::assertSame( '',     $query->get( 'meta_value' ) );
		self::assertSame( '',     $query->get( 'meta_query' ) );

		self::assertEquals( $this->posts['post'], wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQueryWithNoArgsNotAffected(): void {

		$query = $this->get_query( array(
			'post_type' => 'hello',
		) );

		self::assertEquals( count( $this->posts['hello'] ), $query->found_posts );

		self::assertSame( '',     $query->get( 'orderby' ) ); // date
		self::assertSame( 'DESC', $query->get( 'order' ) );
		self::assertSame( '',     $query->get( 'meta_key' ) );
		self::assertSame( '',     $query->get( 'meta_value' ) );
		self::assertSame( '',     $query->get( 'meta_query' ) );

		self::assertEquals( $this->posts['hello'], wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQueryWithNoCustomValuesNotAffected(): void {

		$query = $this->get_query( array(
			'post_type' => 'hello',
			'orderby'   => 'post_name',
			'order'     => 'ASC',
		) );

		self::assertEquals( count( $this->posts['hello'] ), $query->found_posts );

		self::assertSame( 'post_name', $query->get( 'orderby' ) );
		self::assertSame( 'ASC',       $query->get( 'order' ) );
		self::assertSame( '',          $query->get( 'meta_key' ) );
		self::assertSame( '',          $query->get( 'meta_value' ) );
		self::assertSame( '',          $query->get( 'meta_query' ) );

		self::assertEquals( array(
			$this->posts['hello'][0],
			$this->posts['hello'][2],
			$this->posts['hello'][1],
			$this->posts['hello'][3],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQuerySortedByPostMeta(): void {

		$query = $this->get_query( array(
			'post_type' => 'hello',
			'orderby'   => 'test_site_sortables_post_meta',
			'order'     => 'ASC',
		) );

		self::assertEquals( 3, $query->found_posts );

		self::assertSame( 'meta_value',    $query->get( 'orderby' ) );
		self::assertSame( 'ASC',           $query->get( 'order' ) );
		self::assertSame( 'test_meta_key', $query->get( 'meta_key' ) );
		self::assertSame( '',              $query->get( 'meta_value' ) );
		self::assertSame( '',              $query->get( 'meta_query' ) );

		self::assertEquals( array(
			$this->posts['hello'][1],
			$this->posts['hello'][2],
			$this->posts['hello'][0],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQuerySortedByPostField(): void {

		$query = $this->get_query( array(
			'post_type' => 'hello',
			'orderby'   => 'test_site_sortables_post_field',
			'order'     => 'ASC',
		) );

		self::assertEquals( count( $this->posts['hello'] ), $query->found_posts );

		self::assertSame( 'name', $query->get( 'orderby' ) );
		self::assertSame( 'ASC',  $query->get( 'order' ) );
		self::assertSame( '',     $query->get( 'meta_key' ) );
		self::assertSame( '',     $query->get( 'meta_value' ) );
		self::assertSame( '',     $query->get( 'meta_query' ) );

		self::assertEquals( array(
			$this->posts['hello'][0],
			$this->posts['hello'][2],
			$this->posts['hello'][1],
			$this->posts['hello'][3],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQuerySortedByTaxonomyTerms(): void {

		$query = $this->get_query( array(
			'post_type' => 'hello',
			'orderby'   => 'test_site_sortables_taxonomy',
			'order'     => 'DESC',
		) );

		self::assertEquals( count( $this->posts['hello'] ), $query->found_posts );

		self::assertSame( 'test_site_sortables_taxonomy', $query->get( 'orderby' ) );
		self::assertSame( 'DESC',                         $query->get( 'order' ) );
		self::assertSame( '',                             $query->get( 'meta_key' ) );
		self::assertSame( '',                             $query->get( 'meta_value' ) );
		self::assertSame( '',                             $query->get( 'meta_query' ) );

		self::assertEquals( array(
			$this->posts['hello'][3],
			$this->posts['hello'][0],
			$this->posts['hello'][2],
			$this->posts['hello'][1],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQueryFilteredByPostMetaKey(): void {

		$query = $this->get_query( array(
			'post_type'                       => 'hello',
			'test_site_filters_post_meta_key' => '0',
		) );

		$meta_query = $query->get( 'meta_query' );

		self::assertEquals( 1, $query->found_posts );

		self::assertSame( '',              $query->get( 'meta_key' ) );
		self::assertSame( '',              $query->get( 'meta_value' ) );
		self::assertSame( 'test_meta_key', $meta_query[0]['key'] );
		self::assertSame( '0',             $meta_query[0]['value'] );

		self::assertEquals( array(
			$this->posts['hello'][1],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQueryFilteredByPostMetaQuery(): void {

		$query = $this->get_query( array(
			'post_type'                         => 'hello',
			'test_site_filters_post_meta_query' => 'ZZZ',
		) );

		$meta_query = $query->get( 'meta_query' );

		self::assertEquals( 2, $query->found_posts );

		self::assertSame( '', $query->get( 'meta_key' ) );
		self::assertSame( '', $query->get( 'meta_value' ) );
		self::assertEquals( array(
			'key'     => 'test_meta_key',
			'value'   => 'B',
			'compare' => '>=',
			'type'    => 'CHAR',
		), $meta_query[0] );

		self::assertEquals( array(
			$this->posts['hello'][0],
			$this->posts['hello'][2],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQueryFilteredByInvalidFilter(): void {

		$query = $this->get_query( array(
			'post_type'                 => 'hello',
			'test_site_filters_invalid' => 'ZZZ',
		) );

		$meta_query = $query->get( 'meta_query' );

		self::assertSame( '', $query->get( 'meta_key' ) );
		self::assertSame( '', $query->get( 'meta_value' ) );
		self::assertEmpty( $meta_query );

		self::assertEquals( $this->posts['hello'], wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQueryFilteredByPostMetaSearch(): void {

		$query = $this->get_query( array(
			'post_type'                          => 'hello',
			'test_site_filters_post_meta_search' => 'ta',
		) );

		$meta_query = $query->get( 'meta_query' );

		self::assertEquals( 2, $query->found_posts );

		self::assertSame( '',              $query->get( 'meta_key' ) );
		self::assertSame( '',              $query->get( 'meta_value' ) );
		self::assertSame( 'test_meta_key', $meta_query[0]['key'] );
		self::assertSame( 'ta',            $meta_query[0]['value'] );
		self::assertSame( 'LIKE',          $meta_query[0]['compare'] );

		self::assertEquals( array(
			$this->posts['hello'][0],
			$this->posts['hello'][2],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQueryFilteredByPostMetaExists(): void {

		$query = $this->get_query( array(
			'post_type'                          => 'hello',
			'test_site_filters_post_meta_exists' => 'test_meta_key',
		) );

		$meta_query = $query->get( 'meta_query' );

		self::assertEquals( 2, $query->found_posts );

		self::assertSame( '',                $query->get( 'meta_key' ) );
		self::assertSame( '',                $query->get( 'meta_value' ) );
		self::assertEquals( 'test_meta_key', $meta_query[0]['key'] );
		self::assertEquals( 'NOT IN',        $meta_query[0]['compare'] );

		self::assertEquals( array( '', '0', 'false', 'null' ), $meta_query[0]['value'] );

		self::assertEquals( array(
			$this->posts['hello'][0],
			$this->posts['hello'][2],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQueryFilteredByPostMetaKeyExists(): void {

		$query = $this->get_query( array(
			'post_type'                              => 'hello',
			'test_site_filters_post_meta_key_exists' => 'test_meta_key',
		) );

		$meta_query = $query->get( 'meta_query' );

		self::assertEquals( 3, $query->found_posts );

		self::assertSame( '',                $query->get( 'meta_key' ) );
		self::assertSame( '',                $query->get( 'meta_value' ) );
		self::assertEquals( 'test_meta_key', $meta_query[0]['key'] );
		self::assertEquals( 'EXISTS',        $meta_query[0]['compare'] );

		self::assertEquals( array(
			$this->posts['hello'][0],
			$this->posts['hello'][1],
			$this->posts['hello'][2],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQueryFilteredByPostDate(): void {
		$query = $this->get_query( array(
			'post_type'                   => 'hello',
			'test_site_filters_date_from' => '2019-08-05',
			'test_site_filters_date_to'   => '2019-08-08',
		) );

		$date_query = $query->get( 'date_query' );

		self::assertEquals( '2019-08-05', $date_query[0]['after'] );
		self::assertEquals( '2019-08-08', $date_query[1]['before'] );
	}

	public function testQueryNotFilteredWithoutRequiredCap(): void {

		$query = $this->get_query( array(
			'post_type'                  => 'hello',
			'test_site_filters_with_cap' => 'Alpha',
		) );

		self::assertEquals( count( $this->posts['hello'] ), $query->found_posts );

		self::assertSame( '',     $query->get( 'orderby' ) ); // date
		self::assertSame( 'DESC', $query->get( 'order' ) );
		self::assertSame( '',     $query->get( 'meta_key' ) );
		self::assertSame( '',     $query->get( 'meta_value' ) );
		self::assertSame( '',     $query->get( 'meta_query' ) );

		self::assertEquals( $this->posts['hello'], wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQueryWithDefaultSortOrder(): void {

		$query = $this->get_query( array(
			'post_type' => 'person',
		) );

		self::assertEquals( count( $this->posts['person'] ), $query->found_posts );

		self::assertSame( 'name', $query->get( 'orderby' ) );
		self::assertSame( 'ASC',  $query->get( 'order' ) );
		self::assertSame( '',     $query->get( 'meta_key' ) );
		self::assertSame( '',     $query->get( 'meta_value' ) );
		self::assertSame( '',     $query->get( 'meta_query' ) );

		self::assertEquals( array(
			$this->posts['person'][1],
			$this->posts['person'][0],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	/**
	 * @param array<string,mixed> $args
	 */
	protected function get_query( array $args ): \WP_Query {
		$args = array_merge( array(
			'nopaging' => true,
		), $args );
		return new WP_Query( $args );
	}

}
