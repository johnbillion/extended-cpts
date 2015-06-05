<?php

class Extended_CPT_Test_Setup extends Extended_CPT_Test {

	function setUp() {
		parent::setUp();
		$this->register_post_types();
	}

	function testPostTypeArgsAreCorrect() {

		$this->assertEquals( 'hello',  $this->cpts['hello']->post_type );
		$this->assertEquals( 'hellos', $this->cpts['hello']->post_slug );
		$this->assertEquals( 'Hello',  $this->cpts['hello']->post_singular );
		$this->assertEquals( 'Hellos', $this->cpts['hello']->post_plural );
		$this->assertEquals( 'hello',  $this->cpts['hello']->post_singular_low );
		$this->assertEquals( 'hellos', $this->cpts['hello']->post_plural_low );

		$this->assertEquals( 'person', $this->cpts['person']->post_type );
		$this->assertEquals( 'people', $this->cpts['person']->post_slug );
		$this->assertEquals( 'Person', $this->cpts['person']->post_singular );
		$this->assertEquals( 'People', $this->cpts['person']->post_plural );
		$this->assertEquals( 'person', $this->cpts['person']->post_singular_low );
		$this->assertEquals( 'people', $this->cpts['person']->post_plural_low );

		$this->assertEquals( 'nice-thing',  $this->cpts['nice-thing']->post_type );
		$this->assertEquals( 'things',      $this->cpts['nice-thing']->post_slug );
		$this->assertEquals( 'Nice Thing',  $this->cpts['nice-thing']->post_singular );
		$this->assertEquals( 'Nice Things', $this->cpts['nice-thing']->post_plural );
		$this->assertEquals( 'nice thing',  $this->cpts['nice-thing']->post_singular_low );
		$this->assertEquals( 'nice things', $this->cpts['nice-thing']->post_plural_low );

		$this->assertEquals( 'foo',  $this->cpts['foo']->post_type );
		$this->assertEquals( 'foos', $this->cpts['foo']->post_slug );
		$this->assertEquals( 'Bar',  $this->cpts['foo']->post_singular );
		$this->assertEquals( 'Bars', $this->cpts['foo']->post_plural );
		$this->assertEquals( 'bar',  $this->cpts['foo']->post_singular_low );
		$this->assertEquals( 'bars', $this->cpts['foo']->post_plural_low );

		$this->assertEquals( 'bar',      $this->cpts['bar']->post_type );
		$this->assertEquals( 'slug',     $this->cpts['bar']->post_slug );
		$this->assertEquals( 'Singular', $this->cpts['bar']->post_singular );
		$this->assertEquals( 'Plural',   $this->cpts['bar']->post_plural );
		$this->assertEquals( 'singular', $this->cpts['bar']->post_singular_low );
		$this->assertEquals( 'plural',   $this->cpts['bar']->post_plural_low );

	}

	function testPostTypePropertiesAreCorrect() {

		$hello = get_post_type_object( 'hello' );

		$this->assertEquals( true,   $hello->public );
		$this->assertEquals( 'page', $hello->capability_type );
		$this->assertEquals( true,   $hello->hierarchical );
		$this->assertEquals( true,   $hello->has_archive );
		$this->assertEquals( 'hi',   $hello->query_var );

	}

	function testPostTypeLabelsAreCorrect() {

		$bar = get_post_type_object( 'bar' );

		$this->assertEquals( (object) array(
			'name'               => 'Plural',
			'singular_name'      => 'Singular',
			'menu_name'          => 'Plural',
			'name_admin_bar'     => 'Singular',
			'add_new'            => 'Add New',
			'add_new_item'       => 'Add New Singular',
			'edit_item'          => 'Edit Singular',
			'new_item'           => 'New Singular',
			'view_item'          => 'View Singular',
			'search_items'       => 'Search Plural',
			'not_found'          => 'No plural found.',
			'not_found_in_trash' => 'No plural found in trash.',
			'parent_item_colon'  => 'Parent Singular:',
			'all_items'          => 'All Plural',
		), $bar->labels );
	}

	function testArchiveLinksAreCorrect() {

		$link = get_post_type_archive_link( $this->cpts['hello']->post_type );
		$this->assertEquals( user_trailingslashit( home_url( 'hellos' ) ), $link );

		$link = get_post_type_archive_link( $this->cpts['person']->post_type );
		$this->assertEquals( user_trailingslashit( home_url( 'team' ) ), $link );

		$link = get_post_type_archive_link( $this->cpts['nice-thing']->post_type );
		$this->assertEquals( user_trailingslashit( home_url( 'things' ) ), $link );

		$link = get_post_type_archive_link( $this->cpts['foo']->post_type );
		$this->assertEquals( user_trailingslashit( home_url( 'foos' ) ), $link );

	}

	function testPermalinksAreCorrect() {

		$post = get_post( $this->posts['hello'][0] );
		$link = get_permalink( $post );
		$this->assertEquals( user_trailingslashit( home_url( sprintf( 'hellos/%s', $post->post_name ) ) ), $link );

		$post = get_post( $this->posts['person'][0] );
		$link = get_permalink( $post );
		$this->assertEquals( user_trailingslashit( home_url( sprintf( 'people/%s', $post->post_name ) ) ), $link );

		$post = get_post( $this->posts['nice-thing'][0] );
		$link = get_permalink( $post );
		$this->assertEquals( user_trailingslashit( home_url( sprintf( 'things/%s', $post->post_name ) ) ), $link );

		$post = get_post( $this->posts['foo'][0] );
		$link = get_permalink( $post );
		$this->assertEquals( user_trailingslashit( home_url( sprintf( 'foo/admin/delta/%s', $post->post_name ) ) ), $link );

	}

}
