<?php

class Extended_CPT_Test_Admin_Requests extends Extended_CPT_Test_Admin {

	public function testDefaultPostTypeListingRequestIsCorrect() {

		$this->go_to_listing( array(
			'post_type' => 'post',
		) );

		global $wp;

		$expected = array_merge( $this->default_listing_vars(), array(
			'post_type' => 'post',
		) );
		$this->assertEquals( $expected, $wp->query_vars );

	}

	public function testPostTypeListingRequestWithDefaultOrderIsCorrect() {

		$this->go_to_listing( array(
			'post_type' => 'person',
		) );

		global $wp;

		$expected = array_merge( $this->default_listing_vars(), array(
			'post_type' => 'person',
			'orderby'   => 'test_admin_cols_post_name',
			'order'     => 'asc',
		) );
		$this->assertEquals( $expected, $wp->query_vars );

	}

	public function testPostTypeListingRequestWithStandardOrderIsCorrect() {

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
		$this->assertEquals( $expected, $wp->query_vars );

	}

	public function testPostTypeListingRequestWithPostFieldOrderIsCorrect() {

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
		$this->assertEquals( $expected, $wp->query_vars );

	}

	public function testPostTypeListingRequestWithPostMetaOrderIsCorrect() {

		$this->go_to_listing( array(
			'post_type' => 'person',
			'orderby'   => 'test_admin_cols_test_meta_key',
		) );

		global $wp;

		$expected = array_merge( $this->default_listing_vars(), array(
			'post_type' => 'person',
			'orderby'   => 'test_admin_cols_test_meta_key',
		) );
		$this->assertEquals( $expected, $wp->query_vars );

	}

	public function testPostTypeListingRequestWithTaxonomyOrderIsCorrect() {

		$this->go_to_listing( array(
			'post_type' => 'person',
			'orderby'   => 'test_admin_cols_person_category',
		) );

		global $wp;

		$expected = array_merge( $this->default_listing_vars(), array(
			'post_type' => 'person',
			'orderby'   => 'test_admin_cols_person_category',
		) );
		$this->assertEquals( $expected, $wp->query_vars );

	}

	public function testPostTypeListingRequestWithUnsortableOrderIsCorrect() {

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
		$this->assertEquals( $expected, $wp->query_vars );

	}

}
