<?php

class Extended_CPT_Test_Queries extends WP_UnitTestCase {

	public static $post_type = 'test_queries';
	public static $taxonomy  = 'test_queries_taxonomy';
	public $posts = array();

	function setUp() {

		parent::setUp();

		$this->args = array(
			'site_sortables' => array(
				'test_site_sortables_post_meta' => array(
					'meta_key' => 'test_meta_key',
				),
				'test_site_sortables_post_field' => array(
					'post_field' => 'name',
				),
				'test_site_sortables_taxonomy' => array(
					'taxonomy' => self::$taxonomy,
				),
			),
			'site_filters' => array(
				'test_site_filters_post_meta_key' => array(
					'meta_key' => 'test_meta_key',
				),
				'test_site_filters_post_meta_search' => array(
					'meta_search_key' => 'test_meta_key',
				),
				'test_site_filters_post_meta_exists' => array(
					'meta_exists' => array(
						'test_meta_key',
					),
				),
			),
		);

		$post_type = register_extended_post_type( self::$post_type, $this->args );
		$post_type->add_taxonomy( self::$taxonomy );

		foreach ( array( 'Alpha', 'Beta', 'Gamma', 'Delta' ) as $slug ) {
			wp_insert_term( $slug, self::$taxonomy );
		}

		// Standard post
		$this->post = $this->factory->post->create( array(
			'post_type' => 'post',
			'post_date' => '1984-02-25 00:05:00'
		) );

		// Post 0
		$this->posts[0] = $this->factory->post->create( array(
			'post_type' => self::$post_type,
			'post_name' => 'Alpha',
			'post_date' => '1984-02-25 00:04:00'
		) );
		add_post_meta( $this->posts[0], 'test_meta_key', 'Delta' );
		wp_add_object_terms( $this->posts[0], 'Beta', self::$taxonomy );

		// Post 1
		$this->posts[1] = $this->factory->post->create( array(
			'post_type' => self::$post_type,
			'post_name' => 'Delta',
			'post_date' => '1984-02-25 00:03:00'
		) );
		add_post_meta( $this->posts[1], 'test_meta_key', 'Alpha' );

		// Post 2
		$this->posts[2] = $this->factory->post->create( array(
			'post_type' => self::$post_type,
			'post_name' => 'Beta',
			'post_date' => '1984-02-25 00:02:00'
		) );
		add_post_meta( $this->posts[2], 'test_meta_key', 'Beta' );
		wp_add_object_terms( $this->posts[2], 'Alpha', self::$taxonomy );

		// Post 3
		$this->posts[3] = $this->factory->post->create( array(
			'post_type' => self::$post_type,
			'post_name' => 'Gamma',
			'post_date' => '1984-02-25 00:01:00'
		) );
		wp_add_object_terms( $this->posts[3], 'Gamma', self::$taxonomy );

	}

	function tearDown() {

		parent::tearDown();

		_unregister_post_type( self::$post_type );
		_unregister_taxonomy( self::$taxonomy );

	}

	function test_query_vars() {

		// Need to trigger a new request
		$this->go_to( home_url() );

		// These globals need to be declared after `go_to()` because of the way it resets vars
		global $wp;

		$filters = array_keys( $this->args['site_filters'] );
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

		$this->assertEquals( array( $this->post ), wp_list_pluck( $query->posts, 'ID' ) );

	}

	function test_no_args() {

		$query = new WP_Query( array(
			'post_type' => self::$post_type,
			'nopaging'  => true,
		) );

		$this->assertEquals( $query->found_posts, count( $this->posts ) );
		$this->assertEquals( $query->get( 'orderby' ), '' ); // date
		$this->assertEquals( $query->get( 'order' ), 'DESC' );
		$this->assertEquals( $query->get( 'meta_key' ), '' );
		$this->assertEquals( $query->get( 'meta_value' ), '' );
		$this->assertEquals( $query->get( 'meta_query' ), '' );

		$this->assertEquals( $this->posts, wp_list_pluck( $query->posts, 'ID' ) );

	}

	function test_site_sortables_post_meta() {

		$query = new WP_Query( array(
			'post_type' => self::$post_type,
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
			$this->posts[1],
			$this->posts[2],
			$this->posts[0],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	function test_site_sortables_post_field() {

		$query = new WP_Query( array(
			'post_type' => self::$post_type,
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
			$this->posts[0],
			$this->posts[2],
			$this->posts[1],
			$this->posts[3],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	function test_site_sortables_taxonomy() {

		$query = new WP_Query( array(
			'post_type' => self::$post_type,
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
			$this->posts[3],
			$this->posts[0],
			$this->posts[2],
			$this->posts[1],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	function test_site_filters_post_meta_key() {

		$query = new WP_Query( array(
			'post_type'                       => self::$post_type,
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
			$this->posts[1],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	function test_site_filters_post_meta_search() {

		$query = new WP_Query( array(
			'post_type'                          => self::$post_type,
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
			$this->posts[0],
			$this->posts[2],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	function test_site_filters_post_meta_exists() {

		$query = new WP_Query( array(
			'post_type'                          => self::$post_type,
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
			$this->posts[0],
			$this->posts[1],
			$this->posts[2],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

}
