<?php

class Extended_CPT_Test_Admin_Requests extends Extended_CPT_Test_Admin {

	function testDefaultPostTypeListingRequestIsCorrect() {

		$this->go_to_listing( array(
			'post_type' => 'post',
		) );

		global $wp;

		$expected = array_merge( $this->default_listing_vars(), array(
			'post_type' => 'post',
		) );
		$this->assertEquals( $expected, $wp->query_vars );

	}

	function testPostTypeListingRequestWithDefaultOrderIsCorrect() {

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

	function testPostTypeListingRequestWithOrderIsCorrect() {

		$this->go_to_listing( array(
			'post_type' => 'person',
			'orderby'   => 'date',
			'order'     => 'desc',
		) );

		global $wp;

		$expected = array_merge( $this->default_listing_vars(), array(
			'post_type' => 'person',
			'orderby'   => 'date',
			'order'     => 'desc',
		) );
		$this->assertEquals( $expected, $wp->query_vars );

	}

}
