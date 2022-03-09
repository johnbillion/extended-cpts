<?php
declare( strict_types=1 );

namespace ExtCPTs\Tests;

class Setup extends Test {

	public function setUp(): void {
		parent::setUp();
		$this->register_post_types();
	}

	public function testMinimumWordPressVersion(): void {
		global $wp_version;

		$filename = dirname( dirname( dirname( __FILE__ ) ) ) . '/README.md';
		self::assertFileExists( $filename );

		$min = self::get_minimum_version( 'WordPress', $filename );

		self::assertNotNull( $min );
		self::assertTrue( is_numeric( $min ), "Min is not numeric: {$min}" );
		self::assertTrue( version_compare( $wp_version, $min, '>=' ), "{$wp_version} is not >= {$min}" );
	}

	public function testMinimumPHPVersion(): void {
		$php_version = PHP_VERSION;
		$filename = dirname( dirname( dirname( __FILE__ ) ) ) . '/README.md';
		self::assertFileExists( $filename );

		$min = self::get_minimum_version( 'PHP', $filename );

		self::assertNotNull( $min );
		self::assertTrue( is_numeric( $min ), "Min is not numeric: {$min}" );
		self::assertTrue( version_compare( $php_version, $min, '>=' ), "{$php_version} is not >= {$min}" );
	}

	public function testCodeSnifferMinimumWordPressVersionIsCorrect(): void {
		$filename = dirname( dirname( dirname( __FILE__ ) ) ) . '/phpcs.xml.dist';
		self::assertFileExists( $filename );

		$phpcs = (string) file_get_contents( $filename );
		$pattern = '/minimum_supported_version" value="(?P<version>[0-9]\.[0-9])"/';
		$result = preg_match( $pattern, $phpcs, $matches );
		self::assertNotEmpty( $result );

		$filename = dirname( dirname( dirname( __FILE__ ) ) ) . '/README.md';
		self::assertFileExists( $filename );

		$min = self::get_minimum_version( 'WordPress', $filename );

		self::assertSame( $min, $matches['version'] );
	}

	public function testPostTypeArgsAreCorrect(): void {

		self::assertEquals( 'hello',  $this->cpts['hello']->post_type );
		self::assertEquals( 'hellos', $this->cpts['hello']->post_slug );
		self::assertEquals( 'Hello',  $this->cpts['hello']->post_singular );
		self::assertEquals( 'Hellos', $this->cpts['hello']->post_plural );
		self::assertEquals( 'hello',  $this->cpts['hello']->post_singular_low );
		self::assertEquals( 'hellos', $this->cpts['hello']->post_plural_low );

		self::assertEquals( 'person', $this->cpts['person']->post_type );
		self::assertEquals( 'people', $this->cpts['person']->post_slug );
		self::assertEquals( 'Person', $this->cpts['person']->post_singular );
		self::assertEquals( 'People', $this->cpts['person']->post_plural );
		self::assertEquals( 'person', $this->cpts['person']->post_singular_low );
		self::assertEquals( 'people', $this->cpts['person']->post_plural_low );

		self::assertEquals( 'nice-thing',  $this->cpts['nice-thing']->post_type );
		self::assertEquals( 'things',      $this->cpts['nice-thing']->post_slug );
		self::assertEquals( 'Nice Thing',  $this->cpts['nice-thing']->post_singular );
		self::assertEquals( 'Nice Things', $this->cpts['nice-thing']->post_plural );
		self::assertEquals( 'nice thing',  $this->cpts['nice-thing']->post_singular_low );
		self::assertEquals( 'nice things', $this->cpts['nice-thing']->post_plural_low );

		self::assertEquals( 'foo',  $this->cpts['foo']->post_type );
		self::assertEquals( 'foos', $this->cpts['foo']->post_slug );
		self::assertEquals( 'Bar',  $this->cpts['foo']->post_singular );
		self::assertEquals( 'Bars', $this->cpts['foo']->post_plural );
		self::assertEquals( 'bar',  $this->cpts['foo']->post_singular_low );
		self::assertEquals( 'bars', $this->cpts['foo']->post_plural_low );

		self::assertEquals( 'bar',      $this->cpts['bar']->post_type );
		self::assertEquals( 'slug',     $this->cpts['bar']->post_slug );
		self::assertEquals( 'Singular', $this->cpts['bar']->post_singular );
		self::assertEquals( 'Plural',   $this->cpts['bar']->post_plural );
		self::assertEquals( 'singular', $this->cpts['bar']->post_singular_low );
		self::assertEquals( 'plural',   $this->cpts['bar']->post_plural_low );

	}

	public function testPostTypePropertiesAreCorrect(): void {
		$hello = get_post_type_object( 'hello' );

		self::assertNotNull( $hello );
		self::assertTrue( $hello->public );
		self::assertTrue( $hello->hierarchical );
		self::assertTrue( $hello->has_archive );
		self::assertEquals( 'hi',   $hello->query_var );
		self::assertEquals( 'page', $hello->capability_type );

		$bar = get_post_type_object( 'bar' );

		self::assertNotNull( $bar );
		self::assertFalse( $bar->public );
		self::assertTrue( $bar->hierarchical );
		self::assertFalse( $bar->has_archive );
		self::assertFalse( $bar->rewrite );
		// This should be boolean false, but it's not:
		self::assertEquals( 'bar',  $bar->query_var );
		self::assertEquals( 'page', $bar->capability_type );

		$baz = get_post_type_object( 'baz' );

		self::assertNotNull( $baz );
		self::assertTrue( $baz->public );
		self::assertTrue( $baz->hierarchical );
		self::assertFalse( $baz->has_archive );
		self::assertEquals( 'page', $baz->capability_type );
		self::assertEquals( 'baz',  $baz->query_var );

	}

	public function testPostTypeLabelsAreCorrect(): void {
		global $wp_version;

		$bar = get_post_type_object( 'bar' );
		$faq = get_post_type_object( 'faq' );

		self::assertNotNull( $bar );
		self::assertEquals( (object) array(
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
			'filter_by_date'           => 'Filter by date',
			'items_list_navigation'    => 'Plural list navigation',
			'items_list'               => 'Plural list',
			'item_published'           => 'Singular published.',
			'item_published_privately' => 'Singular published privately.',
			'item_reverted_to_draft'   => 'Singular reverted to draft.',
			'item_scheduled'           => 'Singular scheduled.',
			'item_updated'             => 'Singular updated.',
			'item_link'                => 'Singular Link',
			'item_link_description'    => 'A link to a singular.',
		), $bar->labels );

		$featured_image = version_compare( $wp_version, '5.4', '>=' ) ? 'Featured image' : 'Featured Image';

		self::assertNotNull( $faq );
		self::assertEquals( (object) array(
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
			'featured_image'           => $featured_image,
			'set_featured_image'       => 'Set featured image',
			'remove_featured_image'    => 'Remove featured image',
			'use_featured_image'       => 'Use as featured image',
			'filter_items_list'        => 'Filter FAQs list',
			'filter_by_date'           => 'Filter by date',
			'items_list_navigation'    => 'FAQs list navigation',
			'items_list'               => 'FAQs list',
			'item_published'           => 'FAQ published.',
			'item_published_privately' => 'FAQ published privately.',
			'item_reverted_to_draft'   => 'FAQ reverted to draft.',
			'item_scheduled'           => 'FAQ scheduled.',
			'item_updated'             => 'FAQ updated.',
			'item_link'                => 'FAQ Link',
			'item_link_description'    => 'A link to a FAQ.',
		), $faq->labels );

		$post = get_post_type_object( 'post' );

		self::assertNotNull( $post );
		self::assertEquals( $featured_image, $post->labels->featured_image );
		self::assertEquals( 'Remove!', $post->labels->remove_featured_image );

	}

	public function testTaxonomyLabelsAreCorrect(): void {
		$foo = get_taxonomy( 'foo_category' );

		self::assertNotFalse( $foo );
		self::assertEquals( (object) array(
			'menu_name'                  => 'Foo Categorys',
			'name'                       => 'Foo Categorys',
			'singular_name'              => 'Foo Category',
			'search_items'               => 'Search Foo Categorys',
			'popular_items'              => 'Popular Foo Categorys',
			'all_items'                  => 'All Foo Categorys',
			'parent_item'                => 'Parent Foo Category',
			'parent_item_colon'          => 'Parent Foo Category:',
			'edit_item'                  => 'Edit Foo Category',
			'view_item'                  => 'View Foo Category',
			'update_item'                => 'Update Foo Category',
			'add_new_item'               => 'Add New Foo Category',
			'new_item_name'              => 'New Foo Category Name',
			'separate_items_with_commas' => 'Separate foo categorys with commas',
			'add_or_remove_items'        => 'Add or remove foo categorys',
			'choose_from_most_used'      => 'Choose from most used foo categorys',
			'not_found'                  => 'No foo categorys found',
			'no_terms'                   => 'No foo categorys',
			'filter_by_item'             => 'Filter by foo category',
			'items_list_navigation'      => 'Foo Categorys list navigation',
			'items_list'                 => 'Foo Categorys list',
			'most_used'                  => 'Most Used',
			'back_to_items'              => '&larr; Back to Foo Categorys',
			'no_item'                    => 'No foo category',
			'filter_by'                  => 'Filter by foo category',
			'name_admin_bar'             => 'Foo Category',
			'archives'                   => 'Foo Categorys Archives',
			'item_link'                  => 'Foo Category Link',
			'item_link_description'      => 'A link to a foo category.',
		), $foo->labels );
	}

	public function testArchiveLinksAreCorrect(): void {

		$link = get_post_type_archive_link( $this->cpts['hello']->post_type );
		self::assertEquals( user_trailingslashit( home_url( 'hellos' ) ), $link );

		$link = get_post_type_archive_link( $this->cpts['person']->post_type );
		self::assertEquals( user_trailingslashit( home_url( 'team' ) ), $link );

		$link = get_post_type_archive_link( $this->cpts['nice-thing']->post_type );
		self::assertEquals( user_trailingslashit( home_url( 'things' ) ), $link );

		$link = get_post_type_archive_link( $this->cpts['foo']->post_type );
		self::assertEquals( user_trailingslashit( home_url( 'foos' ) ), $link );

		$link = get_post_type_archive_link( $this->cpts['bar']->post_type );
		self::assertFalse( $link );

		$link = get_post_type_archive_link( $this->cpts['baz']->post_type );
		self::assertFalse( $link );

	}

	public function testPermalinksAreCorrect(): void {

		$post = get_post( $this->posts['hello'][0] );
		self::assertNotNull( $post );
		$link = get_permalink( $post );
		self::assertEquals( user_trailingslashit( home_url( sprintf( 'hellos/%s', $post->post_name ) ) ), $link );

		$post = get_post( $this->posts['person'][0] );
		self::assertNotNull( $post );
		$link = get_permalink( $post );
		self::assertEquals( user_trailingslashit( home_url( sprintf( 'people/%s', $post->post_name ) ) ), $link );

		$post = get_post( $this->posts['nice-thing'][0] );
		self::assertNotNull( $post );
		$link = get_permalink( $post );
		self::assertEquals( user_trailingslashit( home_url( sprintf( 'things/%s', $post->post_name ) ) ), $link );

		$post = get_post( $this->posts['foo'][0] );
		self::assertNotNull( $post );
		$link = get_permalink( $post );
		self::assertEquals( user_trailingslashit( home_url( sprintf( 'foo/admin/delta/%s', $post->post_name ) ) ), $link );

		$post = get_post( $this->posts['foo'][1] );
		self::assertNotNull( $post );
		$link = get_permalink( $post );
		self::assertEquals( user_trailingslashit( home_url( sprintf( 'foo/-/gamma/%s', $post->post_name ) ) ), $link );

		add_filter( 'default_foo_category', function(): int {
			$term = get_term_by( 'slug', 'delta', 'foo_category' );
			return ( $term instanceof \WP_Term ) ? $term->term_id : 0;
		} );

		$post = get_post( $this->posts['foo'][2] );
		self::assertNotNull( $post );
		$link = get_permalink( $post );
		self::assertEquals( user_trailingslashit( home_url( sprintf( 'foo/admin/delta/%s', $post->post_name ) ) ), $link );

		$post = get_post( $this->posts['bar'][0] );
		self::assertNotNull( $post );
		$link = get_permalink( $post );
		self::assertEquals( add_query_arg( 'bar', $post->post_name, user_trailingslashit( home_url() ) ), $link );

		$post = get_post( $this->posts['baz'][0] );
		self::assertNotNull( $post );
		$link = get_permalink( $post );
		self::assertEquals( user_trailingslashit( home_url( sprintf( 'baz/%s', $post->post_name ) ) ), $link );

	}

	public function testTaxonomyQueryVarClashTriggersError(): void {
		register_taxonomy( 'public_taxonomy', 'post', array(
			'public'    => true,
			'query_var' => 'public_taxonomy',
		) );

		try {
			register_extended_post_type( 'public_taxonomy' );
			self::fail( 'register_extended_post_type() should trigger an error when registering a post type which clashes with a taxonomy' );
		} catch ( \PHPUnit\Framework\Exception $e ) {
			self::assertStringContainsString( 'public_taxonomy', $e->getMessage() );
			self::assertFalse( post_type_exists( 'public_taxonomy' ) );
		}
	}

	public function testPrivateTaxonomyWithNoQueryVarDoesNotTriggerError(): void {
		register_taxonomy( 'private_taxonomy', 'post', array(
			'public'    => false,
			'query_var' => true,
		) );

		register_extended_post_type( 'private_taxonomy' );
		self::assertTrue( post_type_exists( 'private_taxonomy' ) );
	}

	/**
	 * @expectedIncorrectUsage register_post_type
	 */
	public function testInvalidPostTypeTriggersError(): void {
		$max_length = 20;

		$name = str_repeat( 'a', $max_length + 1 );

		$result = register_post_type( $name );

		self::assertWPError( $result );

		try {
			register_extended_post_type( $name );
			self::fail( 'register_extended_post_type() should trigger an error when registering a post type which causes an error' );
		} catch ( \PHPUnit\Framework\Exception $e ) {
			self::assertStringContainsString( "$max_length", $e->getMessage() );
		}
	}

}
