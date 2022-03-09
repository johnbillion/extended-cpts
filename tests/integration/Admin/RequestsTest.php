<?php
declare( strict_types=1 );

namespace ExtCPTs\Tests\Admin;

use ExtCPTs\Tests\Admin;

class Requests extends Admin {

	public function testDefaultPostTypeListingRequestIsCorrect(): void {

		$this->go_to_listing( array(
			'post_type' => 'post',
		) );

		global $wp;

		$expected = array_merge( $this->default_listing_vars(), array(
			'post_type' => 'post',
		) );
		self::assertEquals( $expected, $wp->query_vars );

	}

	public function testPostTypeListingRequestWithDefaultOrderIsCorrect(): void {

		$this->go_to_listing( array(
			'post_type' => 'person',
		) );

		global $wp;

		$expected = array_merge( $this->default_listing_vars(), array(
			'post_type' => 'person',
			'orderby'   => 'test_admin_cols_post_name',
			'order'     => 'asc',
		) );
		self::assertEquals( $expected, $wp->query_vars );

	}

	public function testPostTypeListingRequestWithStandardOrderIsCorrect(): void {

		$this->go_to_listing( array(
			'post_type' => 'person',
			'orderby'   => 'hello',
			'order'     => 'desc',
		) );

		global $wp;

		$expected = array_merge( $this->default_listing_vars(), array(
			'post_type' => 'person',
			'orderby'   => 'hello',
			'order'     => 'desc',
		) );
		self::assertEquals( $expected, $wp->query_vars );

	}

	public function testPostTypeListingRequestWithPostFieldOrderIsCorrect(): void {

		$this->go_to_listing( array(
			'post_type' => 'person',
			'orderby'   => 'test_admin_cols_post_name',
			'order'     => 'desc',
		) );

		global $wp;

		$expected = array_merge( $this->default_listing_vars(), array(
			'post_type' => 'person',
			'orderby'   => 'test_admin_cols_post_name',
			'order'     => 'desc',
		) );
		self::assertEquals( $expected, $wp->query_vars );

	}

	public function testPostTypeListingRequestWithPostMetaOrderIsCorrect(): void {

		$this->go_to_listing( array(
			'post_type' => 'person',
			'orderby'   => 'test_admin_cols_test_meta_key',
		) );

		global $wp;

		$expected = array_merge( $this->default_listing_vars(), array(
			'post_type' => 'person',
			'orderby'   => 'test_admin_cols_test_meta_key',
		) );
		self::assertEquals( $expected, $wp->query_vars );

	}

	public function testPostTypeListingRequestWithTaxonomyOrderIsCorrect(): void {

		$this->go_to_listing( array(
			'post_type' => 'person',
			'orderby'   => 'test_admin_cols_person_category',
		) );

		global $wp;

		$expected = array_merge( $this->default_listing_vars(), array(
			'post_type' => 'person',
			'orderby'   => 'test_admin_cols_person_category',
		) );
		self::assertEquals( $expected, $wp->query_vars );

	}

	public function testPostTypeListingRequestWithUnsortableOrderIsCorrect(): void {

		// Even though the `test_admin_cols_unsortable` column is unsortable, the request should still reflect
		// the orderby value as requested. The actual sort order is handled at the query level.

		$this->go_to_listing( array(
			'post_type' => 'person',
			'orderby'   => 'test_admin_cols_unsortable',
		) );

		global $wp;

		$expected = array_merge( $this->default_listing_vars(), array(
			'post_type' => 'person',
			'orderby'   => 'test_admin_cols_unsortable',
		) );
		self::assertEquals( $expected, $wp->query_vars );

	}

}
