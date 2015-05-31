<?php

class Extended_CPT_Test_Site_Requests extends Extended_CPT_Test_Site {

	function testFilterQueryVarsRegistered() {

		// Need to trigger a new request
		$this->go_to( home_url() );

		global $wp, $wp_query;

		$filters = array_keys( $this->args['hello']['site_filters'] );
		$found   = array_intersect( $filters, $wp->public_query_vars );

		$this->assertEquals( $filters, $found );

	}

	function testHomeRequestIsCorrect() {

		$this->go_to( home_url() );

		global $wp, $wp_query;

		$this->assertEquals( array(), $wp->query_vars );

	}

	function testFeedRequestIsCorrect() {

		$this->go_to( get_feed_link() );

		global $wp, $wp_query;

		$this->assertEquals( array(
			'post_type' => array(
				'post',
				'person',
				'foo',
			),
			'feed' => 'feed',
		), $wp->query_vars );

	}

	function testPostTypeArchiveRequestIsCorrect() {

		$this->go_to( get_post_type_archive_link( 'hello' ) );

		global $wp, $wp_query;

		$this->assertEquals( array_merge( array(
			'post_type' => 'hello',
		), $this->args['hello']['archive'] ), $wp->query_vars );

	}

	function testPostTypePermalinkRequestIsCorrect() {

		$this->go_to( get_permalink( $this->posts['hello'][0] ) );

		global $wp, $wp_query;

		$this->assertEquals( array(
			'post_type' => 'hello',
			'name'      => 'alpha',
			'page'      => '',
			'hi'        => 'alpha',
		), $wp->query_vars );

	}

}
