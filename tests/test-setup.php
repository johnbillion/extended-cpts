<?php

class Extended_CPT_Test_Setup extends WP_UnitTestCase {

	public $cpts  = array();
	public $posts = array();

	function setUp() {

		global $wp_rewrite;

		parent::setUp();

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );

		$this->cpts['hello']  = register_extended_post_type( 'hello' );
		$this->cpts['person'] = register_extended_post_type( 'person', array(
			'has_archive' => 'team',
		), array(
			'plural' => 'People',
		) );
		$this->cpts['nice-thing'] = register_extended_post_type( 'nice-thing', array(), array(
			'slug' => 'Things',
		) );
		$this->cpts['foo'] = register_extended_post_type( 'foo', array(
			'rewrite' => array(
				'permastruct' => 'foo/bar/%foo%',
			),
		) );

		$wp_rewrite->flush_rules();

		$this->posts['hello'] = $this->factory->post->create( array(
			'post_type' => 'hello',
		) );
		$this->posts['person'] = $this->factory->post->create( array(
			'post_type' => 'person',
		) );
		$this->posts['nice-thing'] = $this->factory->post->create( array(
			'post_type' => 'nice-thing',
		) );
		$this->posts['foo'] = $this->factory->post->create( array(
			'post_type' => 'foo',
		) );

	}

	function tearDown() {

		parent::tearDown();

		foreach ( $this->cpts as $cpt => $cpto ) {
			_unregister_post_type( $cpt );
		}

	}

	function test_properties() {

		$this->assertEquals( $this->cpts['hello']->post_type, 'hello' );
		$this->assertEquals( $this->cpts['hello']->post_slug, 'hellos' );
		$this->assertEquals( $this->cpts['hello']->post_singular, 'Hello' );
		$this->assertEquals( $this->cpts['hello']->post_plural, 'Hellos' );
		$this->assertEquals( $this->cpts['hello']->post_singular_low, 'hello' );
		$this->assertEquals( $this->cpts['hello']->post_plural_low, 'hellos' );

		$this->assertEquals( $this->cpts['person']->post_type, 'person' );
		$this->assertEquals( $this->cpts['person']->post_slug, 'people' );
		$this->assertEquals( $this->cpts['person']->post_singular, 'Person' );
		$this->assertEquals( $this->cpts['person']->post_plural, 'People' );
		$this->assertEquals( $this->cpts['person']->post_singular_low, 'person' );
		$this->assertEquals( $this->cpts['person']->post_plural_low, 'people' );

		$this->assertEquals( $this->cpts['nice-thing']->post_type, 'nice-thing' );
		$this->assertEquals( $this->cpts['nice-thing']->post_slug, 'things' );
		$this->assertEquals( $this->cpts['nice-thing']->post_singular, 'Nice Thing' );
		$this->assertEquals( $this->cpts['nice-thing']->post_plural, 'Nice Things' );
		$this->assertEquals( $this->cpts['nice-thing']->post_singular_low, 'nice thing' );
		$this->assertEquals( $this->cpts['nice-thing']->post_plural_low, 'nice things' );

	}

	function test_archive_links() {

		$link = get_post_type_archive_link( $this->cpts['hello']->post_type );
		$this->assertEquals( $link, user_trailingslashit( home_url( 'hellos' ) ) );

		$link = get_post_type_archive_link( $this->cpts['person']->post_type );
		$this->assertEquals( $link, user_trailingslashit( home_url( 'team' ) ) );

		$link = get_post_type_archive_link( $this->cpts['nice-thing']->post_type );
		$this->assertEquals( $link, user_trailingslashit( home_url( 'things' ) ) );

		$link = get_post_type_archive_link( $this->cpts['foo']->post_type );
		$this->assertEquals( $link, user_trailingslashit( home_url( 'foos' ) ) );

	}

	function test_permalinks() {

		$post = get_post( $this->posts['hello'] );
		$link = get_permalink( $post );
		$this->assertEquals( $link, user_trailingslashit( home_url( sprintf( 'hellos/%s', $post->post_name ) ) ) );

		$post = get_post( $this->posts['person'] );
		$link = get_permalink( $post );
		$this->assertEquals( $link, user_trailingslashit( home_url( sprintf( 'people/%s', $post->post_name ) ) ) );

		$post = get_post( $this->posts['nice-thing'] );
		$link = get_permalink( $post );
		$this->assertEquals( $link, user_trailingslashit( home_url( sprintf( 'things/%s', $post->post_name ) ) ) );

		$post = get_post( $this->posts['foo'] );
		$link = get_permalink( $post );
		$this->assertEquals( $link, user_trailingslashit( home_url( sprintf( 'foo/bar/%s', $post->post_name ) ) ) );

	}

}
