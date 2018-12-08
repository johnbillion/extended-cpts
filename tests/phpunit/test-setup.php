<?php

class Extended_CPT_Test_Setup extends Extended_CPT_Test {

	public function setUp() {
		parent::setUp();
		$this->register_post_types();
	}

	public function testMinimumWordPressVersion() {
		global $wp_version;

		$filename = dirname( dirname( dirname( __FILE__ ) ) ) . '/README.md';
		$this->assertFileExists( $filename );

		$min = self::get_minimum_version( 'WordPress', $filename );

		$this->assertNotFalse( $min );
		$this->assertTrue( is_numeric( $min ), "Min is not numeric: {$min}" );
		$this->assertTrue( version_compare( $wp_version, $min, '>=' ), "{$wp_version} is not >= {$min}" );
	}

	public function testMinimumPHPVersion() {
		$php_version = PHP_VERSION;
		$filename = dirname( dirname( dirname( __FILE__ ) ) ) . '/README.md';
		$this->assertFileExists( $filename );

		$min = self::get_minimum_version( 'PHP', $filename );

		$this->assertNotFalse( $min );
		$this->assertTrue( is_numeric( $min ), "Min is not numeric: {$min}" );
		$this->assertTrue( version_compare( $php_version, $min, '>=' ), "{$php_version} is not >= {$min}" );
	}

	public function testCodeSnifferMinimumWordPressVersionIsCorrect() {
		$filename = dirname( dirname( dirname( __FILE__ ) ) ) . '/phpcs.xml.dist';
		$this->assertFileExists( $filename );

		$phpcs   = file_get_contents( $filename );
		$pattern = '/minimum_supported_version" value="(?P<version>[0-9]\.[0-9])"/';
		$result  = preg_match( $pattern, $phpcs, $matches );
		$this->assertNotEmpty( $result );

		$filename = dirname( dirname( dirname( __FILE__ ) ) ) . '/README.md';
		$this->assertFileExists( $filename );

		$min = self::get_minimum_version( 'WordPress', $filename );

		$this->assertSame( $min, $matches['version'] );
	}

	public function testVersionMatchesTag() {
		$filename = dirname( dirname( dirname( __FILE__ ) ) ) . '/extended-cpts.php';
		$this->assertFileExists( $filename );

		$file    = file_get_contents( $filename );
		$pattern = '/@version\s+(?P<version>[0-9]\.[0-9]\.[0-9])$/m';
		$result  = preg_match( $pattern, $file, $matches );
		$this->assertNotEmpty( $result );

		$tag = trim( exec( 'git describe --abbrev=0 --tags' ) );
		$this->assertSame( $tag, $matches['version'] );
	}

	public function testPostTypeArgsAreCorrect() {

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

	public function testPostTypePropertiesAreCorrect() {

		$hello = get_post_type_object( 'hello' );

		$this->assertTrue( $hello->public );
		$this->assertTrue( $hello->hierarchical );
		$this->assertTrue( $hello->has_archive );
		$this->assertEquals( 'hi',   $hello->query_var );
		$this->assertEquals( 'page', $hello->capability_type );

		$bar = get_post_type_object( 'bar' );

		$this->assertFalse( $bar->public );
		$this->assertTrue( $bar->hierarchical );
		$this->assertFalse( $bar->has_archive );
		$this->assertFalse( $bar->rewrite );
		// This should be boolean false, but it's not:
		$this->assertEquals( 'bar',  $bar->query_var );
		$this->assertEquals( 'page', $bar->capability_type );

		$baz = get_post_type_object( 'baz' );

		$this->assertTrue( $baz->public );
		$this->assertTrue( $baz->hierarchical );
		$this->assertFalse( $baz->has_archive );
		$this->assertEquals( 'page', $baz->capability_type );
		$this->assertEquals( 'baz',  $baz->query_var );

	}

	public function testPostTypeLabelsAreCorrect() {

		$bar = get_post_type_object( 'bar' );
		$faq = get_post_type_object( 'faq' );

		$this->assertEquals( (object) array(
			'name'                     => 'Plural',
			'singular_name'            => 'Singular',
			'menu_name'                => 'Plural',
			'name_admin_bar'           => 'Singular',
			'add_new'                  => 'Add New',
			'add_new_item'             => 'Add New Singular',
			'edit_item'                => 'Edit Singular',
			'new_item'                 => 'New Singular',
			'view_item'                => 'View Singular',
			'view_items'               => 'View Plural',
			'search_items'             => 'Search Plural',
			'not_found'                => 'No plural found.',
			'not_found_in_trash'       => 'No plural found in trash.',
			'parent_item_colon'        => 'Parent Singular:',
			'all_items'                => 'All Plural',
			'archives'                 => 'Singular Archives',
			'attributes'               => 'Singular Attributes',
			'insert_into_item'         => 'Insert into singular',
			'uploaded_to_this_item'    => 'Uploaded to this singular',
			'featured_image'           => 'Icon',
			'set_featured_image'       => 'Set icon',
			'remove_featured_image'    => 'Remove icon',
			'use_featured_image'       => 'Use as icon',
			'filter_items_list'        => 'Filter plural list',
			'items_list_navigation'    => 'Plural list navigation',
			'items_list'               => 'Plural list',
			'item_published'           => 'Singular published.',
			'item_published_privately' => 'Singular published privately.',
			'item_reverted_to_draft'   => 'Singular reverted to draft.',
			'item_scheduled'           => 'Singular scheduled.',
			'item_updated'             => 'Singular updated.',
		), $bar->labels );

		$this->assertEquals( (object) array(
			'name'                     => 'FAQs',
			'singular_name'            => 'FAQ',
			'menu_name'                => 'FAQs',
			'name_admin_bar'           => 'FAQ',
			'add_new'                  => 'Add New',
			'add_new_item'             => 'Add New FAQ',
			'edit_item'                => 'Edit FAQ',
			'new_item'                 => 'New FAQ',
			'view_item'                => 'View FAQ',
			'view_items'               => 'View FAQs',
			'search_items'             => 'Search FAQs',
			'not_found'                => 'No FAQs found.',
			'not_found_in_trash'       => 'No FAQs found in trash.',
			'parent_item_colon'        => 'Parent FAQ:',
			'all_items'                => 'All FAQs',
			'archives'                 => 'FAQ Archives',
			'attributes'               => 'FAQ Attributes',
			'insert_into_item'         => 'Insert into FAQ',
			'uploaded_to_this_item'    => 'Uploaded to this FAQ',
			'featured_image'           => 'Featured Image',
			'set_featured_image'       => 'Set featured image',
			'remove_featured_image'    => 'Remove featured image',
			'use_featured_image'       => 'Use as featured image',
			'filter_items_list'        => 'Filter FAQs list',
			'items_list_navigation'    => 'FAQs list navigation',
			'items_list'               => 'FAQs list',
			'item_published'           => 'FAQ published.',
			'item_published_privately' => 'FAQ published privately.',
			'item_reverted_to_draft'   => 'FAQ reverted to draft.',
			'item_scheduled'           => 'FAQ scheduled.',
			'item_updated'             => 'FAQ updated.',
		), $faq->labels );

		$post = get_post_type_object( 'post' );

		$this->assertEquals( 'Featured Image', $post->labels->featured_image );
		$this->assertEquals( 'Remove!', $post->labels->remove_featured_image );

	}

	public function testArchiveLinksAreCorrect() {

		$link = get_post_type_archive_link( $this->cpts['hello']->post_type );
		$this->assertEquals( user_trailingslashit( home_url( 'hellos' ) ), $link );

		$link = get_post_type_archive_link( $this->cpts['person']->post_type );
		$this->assertEquals( user_trailingslashit( home_url( 'team' ) ), $link );

		$link = get_post_type_archive_link( $this->cpts['nice-thing']->post_type );
		$this->assertEquals( user_trailingslashit( home_url( 'things' ) ), $link );

		$link = get_post_type_archive_link( $this->cpts['foo']->post_type );
		$this->assertEquals( user_trailingslashit( home_url( 'foos' ) ), $link );

		$link = get_post_type_archive_link( $this->cpts['bar']->post_type );
		$this->assertFalse( $link );

		$link = get_post_type_archive_link( $this->cpts['baz']->post_type );
		$this->assertFalse( $link );

	}

	public function testPermalinksAreCorrect() {

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

		$post = get_post( $this->posts['bar'][0] );
		$link = get_permalink( $post );
		$this->assertEquals( add_query_arg( 'bar', $post->post_name, user_trailingslashit( home_url() ) ), $link );

		$post = get_post( $this->posts['baz'][0] );
		$link = get_permalink( $post );
		$this->assertEquals( user_trailingslashit( home_url( sprintf( 'baz/%s', $post->post_name ) ) ), $link );

	}

	public function testTaxonomyQueryVarClashTriggersError() {
		register_taxonomy( 'public_taxonomy', 'post', array(
			'public'    => true,
			'query_var' => 'public_taxonomy',
		) );

		try {
			register_extended_post_type( 'public_taxonomy' );
			$this->fail( 'register_extended_post_type() should trigger an error when registering a post type which clashes with a taxonomy' );
		} catch ( PHPUnit_Framework_Error $e ) {
			$this->assertContains( 'public_taxonomy', $e->getMessage() );
			$this->assertFalse( post_type_exists( 'public_taxonomy' ) );
		}

		_unregister_taxonomy( 'public_taxonomy' );

	}

	public function testPrivateTaxonomyWithNoQueryVarDoesNotTriggerError() {
		register_taxonomy( 'private_taxonomy', 'post', array(
			'public'    => false,
			'query_var' => true,
		) );

		register_extended_post_type( 'private_taxonomy' );
		$this->assertTrue( post_type_exists( 'private_taxonomy' ) );

		_unregister_post_type( 'private_taxonomy' );
		_unregister_taxonomy( 'private_taxonomy' );

	}

	/**
	 * @expectedIncorrectUsage register_post_type
	 */
	public function testInvalidPostTypeTriggersError() {
		$max_length = 20;

		$name = str_repeat( 'a', $max_length + 1 );

		$result = register_post_type( $name );

		$this->assertWPError( $result );

		try {
			register_extended_post_type( $name );
			$this->fail( 'register_extended_post_type() should trigger an error when registering a post type which causes an error' );
		} catch ( PHPUnit_Framework_Error $e ) {
			$this->assertContains( "$max_length", $e->getMessage() );
		}

		_unregister_post_type( $name );

	}

}
