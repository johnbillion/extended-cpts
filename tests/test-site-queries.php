<?php

class Extended_CPT_Test_Site_Queries extends Extended_CPT_Test {

	function test_query_vars() {

		// Need to trigger a new request
		$this->go_to( home_url( '/' ) );

		// These globals need to be declared after `go_to()` because of the way it resets vars
		global $wp;

		$filters = array_keys( $this->args['hello']['site_filters'] );
		$found   = array_intersect( $filters, $wp->public_query_vars );

		$this->assertEquals( $filters, $found );

		// @TODO test that the admin query vars are not present

	}

	function test_default() {

		$query = new WP_Query( array(
			'post_type' => 'post',
			'nopaging'  => true,
		) );

		$this->assertEquals( $query->found_posts, 1 );
		$this->assertEquals( $query->get( 'orderby' ), '' ); // date
		$this->assertEquals( $query->get( 'order' ), 'DESC' );
		$this->assertEquals( $query->get( 'meta_key' ), '' );
		$this->assertEquals( $query->get( 'meta_value' ), '' );
		$this->assertEquals( $query->get( 'meta_query' ), '' );

		$this->assertEquals( $this->posts['post'], wp_list_pluck( $query->posts, 'ID' ) );

	}

	function test_no_args() {

		$query = new WP_Query( array(
			'post_type' => 'hello',
			'nopaging'  => true,
		) );

		$this->assertEquals( $query->found_posts, count( $this->posts['hello'] ) );
		$this->assertEquals( $query->get( 'orderby' ), '' ); // date
		$this->assertEquals( $query->get( 'order' ), 'DESC' );
		$this->assertEquals( $query->get( 'meta_key' ), '' );
		$this->assertEquals( $query->get( 'meta_value' ), '' );
		$this->assertEquals( $query->get( 'meta_query' ), '' );

		$this->assertEquals( $this->posts['hello'], wp_list_pluck( $query->posts, 'ID' ) );

	}

	function test_site_sortables_post_meta() {

		$query = new WP_Query( array(
			'post_type' => 'hello',
			'nopaging'  => true,
			'orderby'   => 'test_site_sortables_post_meta',
			'order'     => 'ASC',
		) );

		$this->assertEquals( $query->found_posts, 3 );
		$this->assertEquals( $query->get( 'orderby' ), 'meta_value' );
		$this->assertEquals( $query->get( 'order' ), 'ASC' );
		$this->assertEquals( $query->get( 'meta_key' ), 'test_meta_key' );
		$this->assertEquals( $query->get( 'meta_value' ), '' );
		$this->assertEquals( $query->get( 'meta_query' ), '' );

		$this->assertEquals( array(
			$this->posts['hello'][1],
			$this->posts['hello'][2],
			$this->posts['hello'][0],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	function test_site_sortables_post_field() {

		$query = new WP_Query( array(
			'post_type' => 'hello',
			'nopaging'  => true,
			'orderby'   => 'test_site_sortables_post_field',
			'order'     => 'ASC',
		) );

		$this->assertEquals( $query->found_posts, 4 );
		$this->assertEquals( $query->get( 'orderby' ), 'name' );
		$this->assertEquals( $query->get( 'order' ), 'ASC' );
		$this->assertEquals( $query->get( 'meta_key' ), '' );
		$this->assertEquals( $query->get( 'meta_value' ), '' );
		$this->assertEquals( $query->get( 'meta_query' ), '' );

		$this->assertEquals( array(
			$this->posts['hello'][0],
			$this->posts['hello'][2],
			$this->posts['hello'][1],
			$this->posts['hello'][3],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	function test_site_sortables_taxonomy() {

		$query = new WP_Query( array(
			'post_type' => 'hello',
			'nopaging'  => true,
			'orderby'   => 'test_site_sortables_taxonomy',
			'order'     => 'DESC',
		) );

		$this->assertEquals( $query->found_posts, 4 );
		$this->assertEquals( $query->get( 'orderby' ), 'test_site_sortables_taxonomy' );
		$this->assertEquals( $query->get( 'order' ), 'DESC' );
		$this->assertEquals( $query->get( 'meta_key' ), '' );
		$this->assertEquals( $query->get( 'meta_value' ), '' );
		$this->assertEquals( $query->get( 'meta_query' ), '' );

		$this->assertEquals( array(
			$this->posts['hello'][3],
			$this->posts['hello'][0],
			$this->posts['hello'][2],
			$this->posts['hello'][1],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	function test_site_filters_post_meta_key() {

		$query = new WP_Query( array(
			'post_type'                       => 'hello',
			'nopaging'                        => true,
			'test_site_filters_post_meta_key' => 'Alpha',
		) );

		$meta_query = $query->get( 'meta_query' );

		$this->assertEquals( $query->found_posts, 1 );
		$this->assertEquals( $query->get( 'meta_key' ), '' );
		$this->assertEquals( $query->get( 'meta_value' ), '' );
		$this->assertEquals( $meta_query[0]['key'], 'test_meta_key' );
		$this->assertEquals( $meta_query[0]['value'], 'Alpha' );

		$this->assertEquals( array(
			$this->posts['hello'][1],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	function test_site_filters_post_meta_search() {

		$query = new WP_Query( array(
			'post_type'                          => 'hello',
			'nopaging'                           => true,
			'test_site_filters_post_meta_search' => 'ta',
		) );

		$meta_query = $query->get( 'meta_query' );

		$this->assertEquals( $query->found_posts, 2 );
		$this->assertEquals( $query->get( 'meta_key' ), '' );
		$this->assertEquals( $query->get( 'meta_value' ), '' );
		$this->assertEquals( $meta_query[0]['key'], 'test_meta_key' );
		$this->assertEquals( $meta_query[0]['value'], 'ta' );
		$this->assertEquals( $meta_query[0]['compare'], 'LIKE' );

		$this->assertEquals( array(
			$this->posts['hello'][0],
			$this->posts['hello'][2],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	function test_site_filters_post_meta_exists() {

		$query = new WP_Query( array(
			'post_type'                          => 'hello',
			'nopaging'                           => true,
			'test_site_filters_post_meta_exists' => 'test_meta_key',
		) );

		$meta_query = $query->get( 'meta_query' );

		$this->assertEquals( $query->found_posts, 3 );
		$this->assertEquals( $query->get( 'meta_key' ), '' );
		$this->assertEquals( $query->get( 'meta_value' ), '' );
		$this->assertEquals( $meta_query[0]['key'], 'test_meta_key' );
		$this->assertEquals( $meta_query[0]['value'], array( '', '0', 'false', 'null' ) );
		$this->assertEquals( $meta_query[0]['compare'], 'NOT IN' );

		$this->assertEquals( array(
			$this->posts['hello'][0],
			$this->posts['hello'][1],
			$this->posts['hello'][2],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

}
