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

		$this->assertEquals( 1, $query->found_posts );

		$this->assertEquals( '',     $query->get( 'orderby' ) ); // date
		$this->assertEquals( 'DESC', $query->get( 'order' ) );
		$this->assertEquals( '',     $query->get( 'meta_key' ) );
		$this->assertEquals( '',     $query->get( 'meta_value' ) );
		$this->assertEquals( '',     $query->get( 'meta_query' ) );

		$this->assertEquals( $this->posts['post'], wp_list_pluck( $query->posts, 'ID' ) );

	}

	function test_no_args() {

		$query = new WP_Query( array(
			'post_type' => 'hello',
			'nopaging'  => true,
		) );

		$this->assertEquals( count( $this->posts['hello'] ), $query->found_posts );

		$this->assertEquals( '',     $query->get( 'orderby' ) ); // date
		$this->assertEquals( 'DESC', $query->get( 'order' ) );
		$this->assertEquals( '',     $query->get( 'meta_key' ) );
		$this->assertEquals( '',     $query->get( 'meta_value' ) );
		$this->assertEquals( '',     $query->get( 'meta_query' ) );

		$this->assertEquals( $this->posts['hello'], wp_list_pluck( $query->posts, 'ID' ) );

	}

	function test_site_sortables_post_meta() {

		$query = new WP_Query( array(
			'post_type' => 'hello',
			'nopaging'  => true,
			'orderby'   => 'test_site_sortables_post_meta',
			'order'     => 'ASC',
		) );

		$this->assertEquals( 3, $query->found_posts );

		$this->assertEquals( 'meta_value',    $query->get( 'orderby' ) );
		$this->assertEquals( 'ASC',           $query->get( 'order' ) );
		$this->assertEquals( 'test_meta_key', $query->get( 'meta_key' ) );
		$this->assertEquals( '',              $query->get( 'meta_value' ) );
		$this->assertEquals( '',              $query->get( 'meta_query' ) );

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

		$this->assertEquals( 4, $query->found_posts );

		$this->assertEquals( 'name', $query->get( 'orderby' ) );
		$this->assertEquals( 'ASC',  $query->get( 'order' ) );
		$this->assertEquals( '',     $query->get( 'meta_key' ) );
		$this->assertEquals( '',     $query->get( 'meta_value' ) );
		$this->assertEquals( '',     $query->get( 'meta_query' ) );

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

		$this->assertEquals( 4, $query->found_posts );

		$this->assertEquals( 'test_site_sortables_taxonomy', $query->get( 'orderby' ) );
		$this->assertEquals( 'DESC',                         $query->get( 'order' ) );
		$this->assertEquals( '',                             $query->get( 'meta_key' ) );
		$this->assertEquals( '',                             $query->get( 'meta_value' ) );
		$this->assertEquals( '',                             $query->get( 'meta_query' ) );

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

		$this->assertEquals( 1, $query->found_posts );

		$this->assertEquals( '',              $query->get( 'meta_key' ) );
		$this->assertEquals( '',              $query->get( 'meta_value' ) );
		$this->assertEquals( 'test_meta_key', $meta_query[0]['key'] );
		$this->assertEquals( 'Alpha',         $meta_query[0]['value'] );

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

		$this->assertEquals( 2, $query->found_posts );

		$this->assertEquals( '',              $query->get( 'meta_key' ) );
		$this->assertEquals( '',              $query->get( 'meta_value' ) );
		$this->assertEquals( 'test_meta_key', $meta_query[0]['key'] );
		$this->assertEquals( 'ta',            $meta_query[0]['value'] );
		$this->assertEquals( 'LIKE',          $meta_query[0]['compare'] );

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

		$this->assertEquals( 3, $query->found_posts );

		$this->assertEquals( '',              $query->get( 'meta_key' ) );
		$this->assertEquals( '',              $query->get( 'meta_value' ) );
		$this->assertEquals( 'test_meta_key', $meta_query[0]['key'] );
		$this->assertEquals( 'NOT IN',        $meta_query[0]['compare'] );

		$this->assertEquals( array( '', '0', 'false', 'null' ), $meta_query[0]['value'] );

		$this->assertEquals( array(
			$this->posts['hello'][0],
			$this->posts['hello'][1],
			$this->posts['hello'][2],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

}
