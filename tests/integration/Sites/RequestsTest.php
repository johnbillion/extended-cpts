<?php
declare( strict_types=1 );

namespace ExtCPTs\Tests\Sites;

use ExtCPTs\Tests\Site;

class Requests extends Site {

	public function testSiteFilterQueryVarsRegistered(): void {

		// Need to trigger a new request
		$this->go_to( home_url() );

		global $wp, $wp_query;

		$filters = array_keys( $this->args['hello']->site_filters );
		$found = array_intersect( $filters, $wp->public_query_vars );

		self::assertEquals( $filters, $found );

	}

	public function testAdminColQueryVarsNotRegistered(): void {

		// Need to trigger a new request
		$this->go_to( home_url() );

		global $wp, $wp_query;

		$filters = array_keys( $this->args['hello']->admin_cols );
		$found = array_intersect( $filters, $wp->public_query_vars );

		self::assertSame( array(), $found );

	}

	public function testHomeRequestIsCorrect(): void {

		$this->go_to( home_url() );

		global $wp, $wp_query;

		self::assertSame( array(), $wp->query_vars );

	}

	public function testFeedRequestIsCorrect(): void {

		$this->go_to( get_feed_link() );

		global $wp, $wp_query;

		self::assertEquals( array(
			'post_type' => array(
				'post',
				'person',
				'foo',
			),
			'feed' => 'feed',
		), $wp->query_vars );

	}

	public function testPostTypeArchiveRequestIsCorrect(): void {
		$link = get_post_type_archive_link( 'hello' );

		self::assertIsString( $link );

		$this->go_to( $link );

		global $wp, $wp_query;

		self::assertEquals( array_merge( array(
			'post_type' => 'hello',
		), $this->args['hello']->archive ), $wp->query_vars );

	}

	public function testPostTypeArchiveRequestWithFilterIsCorrect(): void {

		$this->go_to( add_query_arg( array(
			'test_site_filters_post_meta_key' => 'Alpha',
		), get_post_type_archive_link( 'hello' ) ) );

		global $wp, $wp_query;

		self::assertEquals( array_merge( array(
			'post_type'                       => 'hello',
			'test_site_filters_post_meta_key' => 'Alpha',
		), $this->args['hello']->archive ), $wp->query_vars );

	}

	public function testPostTypeArchiveRequestWithOrderbyIsCorrect(): void {

		$this->go_to( add_query_arg( array(
			'orderby' => 'test_site_sortables_post_meta',
		), get_post_type_archive_link( 'hello' ) ) );

		global $wp, $wp_query;

		self::assertEquals( array_merge( array(
			'post_type' => 'hello',
			'orderby'   => 'test_site_sortables_post_meta',
		), $this->args['hello']->archive ), $wp->query_vars );

	}

	public function testPostTypePermalinkRequestIsCorrect(): void {
		$link = get_permalink( $this->posts['hello'][0] );

		self::assertIsString( $link );

		$this->go_to( $link );

		global $wp, $wp_query;

		self::assertEquals( array(
			'post_type' => 'hello',
			'name'      => 'alpha',
			'page'      => '',
			'hi'        => 'alpha',
		), $wp->query_vars );

	}

}
