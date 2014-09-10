<?php

class Extended_CPT_Test_Setup extends Extended_CPT_Test {

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

		$this->assertEquals( $this->cpts['foo']->post_type, 'foo' );
		$this->assertEquals( $this->cpts['foo']->post_slug, 'foos' );
		$this->assertEquals( $this->cpts['foo']->post_singular, 'Bar' );
		$this->assertEquals( $this->cpts['foo']->post_plural, 'Bars' );
		$this->assertEquals( $this->cpts['foo']->post_singular_low, 'bar' );
		$this->assertEquals( $this->cpts['foo']->post_plural_low, 'bars' );

	}

	function test_args() {

		$hello = get_post_type_object( 'hello' );

		$this->assertEquals( $hello->public, true );
		$this->assertEquals( $hello->capability_type, 'page' );
		$this->assertEquals( $hello->hierarchical, true );
		$this->assertEquals( $hello->has_archive, true );

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
