<?php
declare( strict_types=1 );

namespace ExtCPTs\Tests\Admin;

use ExtCPTs\Tests\Admin;
use WP_Query;

class Queries extends Admin {

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

		self::assertSame( 'menu_order title', $query->get( 'orderby' ) );
		self::assertSame( 'ASC',              $query->get( 'order' ) );
		self::assertSame( '',                 $query->get( 'meta_key' ) );
		self::assertSame( '',                 $query->get( 'meta_value' ) );
		self::assertSame( '',                 $query->get( 'meta_query' ) );

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
			'orderby'   => 'test_admin_cols_post_meta',
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
			'orderby'   => 'test_admin_cols_post_field',
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
			'orderby'   => 'test_admin_cols_taxonomy',
			'order'     => 'DESC',
		) );

		self::assertEquals( count( $this->posts['hello'] ), $query->found_posts );

		self::assertSame( 'test_admin_cols_taxonomy', $query->get( 'orderby' ) );
		self::assertSame( 'DESC',                     $query->get( 'order' ) );
		self::assertSame( '',                         $query->get( 'meta_key' ) );
		self::assertSame( '',                         $query->get( 'meta_value' ) );
		self::assertSame( '',                         $query->get( 'meta_query' ) );

		self::assertEquals( array(
			$this->posts['hello'][3],
			$this->posts['hello'][0],
			$this->posts['hello'][2],
			$this->posts['hello'][1],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQueryWithDefaultSortOrder(): void {

		$query = $this->get_query( array(
			'post_type' => 'person',
		) );

		self::assertEquals( count( $this->posts['person'] ), $query->found_posts );

		self::assertSame( 'menu_order title', $query->get( 'orderby' ) );
		self::assertSame( 'ASC',  $query->get( 'order' ) );
		self::assertSame( '',     $query->get( 'meta_key' ) );
		self::assertSame( '',     $query->get( 'meta_value' ) );
		self::assertSame( '',     $query->get( 'meta_query' ) );

		self::assertEquals( $this->posts['person'], wp_list_pluck( $query->posts, 'ID' ) );

	}

	/**
	 * @param array<string,mixed> $args
	 */
	protected function get_query( array $args ): \WP_Query {
		global $wp_query, $wp_the_query;

		$wp_the_query = $wp_query = new WP_Query;

		wp_edit_posts_query( $args );

		return $wp_query;
	}

}
