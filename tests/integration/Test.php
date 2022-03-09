<?php
declare( strict_types=1 );

namespace ExtCPTs\Tests;

abstract class Test extends \Codeception\TestCase\WPTestCase {

	use \FalseyAssertEqualsDetector\Test;

	/**
	 * @var array<string,\ExtCPTs\PostType>
	 */
	public $cpts = array();

	/**
	 * @var array<string,\ExtCPTs\Taxonomy>
	 */
	public $taxos = array();

	/**
	 * @var array<string,\WP_Post[]>
	 */
	public $posts = array();

	/**
	 * @var array<string,mixed>
	 */
	public $args = array();

	protected function register_post_types(): void {

		global $wp_rewrite;

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );

		$hello = new \ExtCPTs\Args\PostType;
		$hello->site_sortables = array(
			'test_site_sortables_post_meta' => array(
				'meta_key' => 'test_meta_key',
			),
			'test_site_sortables_post_field' => array(
				'post_field' => 'name',
			),
			'test_site_sortables_taxonomy' => array(
				'taxonomy' => 'hello_category',
			),
		);
		$hello->admin_cols = array(
			'test_admin_cols_post_meta' => array(
				'meta_key' => 'test_meta_key',
			),
			'test_admin_cols_post_field' => array(
				'post_field' => 'name',
			),
			'test_admin_cols_taxonomy' => array(
				'taxonomy' => 'hello_category',
			),
		);
		$hello->site_filters = array(
			'test_site_filters_post_meta_key' => array(
				'meta_key' => 'test_meta_key',
			),
			'test_site_filters_post_meta_search' => array(
				'meta_search_key' => 'test_meta_key',
			),
			'test_site_filters_post_meta_exists' => array(
				'meta_exists' => array(
					'test_meta_key' => 'Test',
				),
			),
			'test_site_filters_post_meta_key_exists' => array(
				'meta_key_exists' => array(
					'test_meta_key' => 'Test',
				),
			),
			'test_site_filters_with_cap' => array(
				'meta_key' => 'test_meta_key',
				'cap'      => 'have_kittens',
			),
			'test_site_filters_post_meta_query' => array(
				'meta_key'   => 'test_meta_key',
				'meta_query' => array(
					'compare' => '>=',
					'value'   => 'B',
					'type'    => 'CHAR',
				),
			),
			'test_site_filters_date_from' => array(
				'post_date' => 'after',
			),
			'test_site_filters_date_to' => array(
				'post_date' => 'before',
			),
			'test_site_filters_invalid' => array(
				'meta_query' => array(
					'key'     => 'foo',
					'value'   => 'bar',
				),
			),
		);
		$hello->archive = array(
			'orderby' => 'post_title',
		);
		$hello->query_var = 'hi';

		$this->args['hello'] = $hello;

		$this->cpts['hello'] = register_extended_post_type( 'hello', $hello->toArray() );
		$this->cpts['hello']->add_taxonomy( 'hello_category' );

		$person = new \ExtCPTs\Args\PostType;

		$person->has_archive = 'team';
		$person->show_in_feed = true;
		$person->site_sortables = array(
			'test_site_sortables_post_name' => array(
				'post_field' => 'post_name',
				'default'    => 'asc',
			),
		);
		$person->admin_cols = array(
			'test_admin_cols_post_name' => array(
				'post_field' => 'post_name',
				'default'    => 'asc',
			),
			'test_admin_cols_unsortable' => array(
				'meta_key' => 'test_meta_key',
				'sortable' => false,
			),
			'test_admin_cols_test_meta_key' => array(
				'meta_key' => 'test_meta_key',
			),
			'test_admin_cols_person_category' => array(
				'taxonomy' => 'person_category',
			),
		);

		$this->args['person'] = $person;

		$this->cpts['person'] = register_extended_post_type( 'person', $person->toArray(), array(
			'plural' => 'People',
		) );
		$this->cpts['person']->add_taxonomy( 'person_category' );
		$this->cpts['nice-thing'] = register_extended_post_type( 'nice-thing', array(), array(
			'slug' => 'Things',
		) );
		$this->cpts['foo'] = register_extended_post_type( 'foo', array(
			'rewrite' => array(
				'permastruct' => 'foo/%author%/%foo_category%/%foo%',
			),
			'show_in_feed' => true,
		), array(
			'singular' => 'Bar',
		) );
		$this->cpts['foo']->add_taxonomy( 'foo_category' );

		$this->cpts['bar'] = register_extended_post_type( 'bar', array(
			'public'         => false,
			'featured_image' => 'Icon',
		), array(
			'plural'   => 'Plural',
			'singular' => 'Singular',
			'slug'     => 'Slug',
		) );

		$this->cpts['baz'] = register_extended_post_type( 'baz', array(
			'rewrite' => array(
				'permastruct' => 'baz/%postname%',
			),
			'has_archive' => false,
		) );

		$this->cpts['post'] = register_extended_post_type( 'post', array(
			'labels' => array(
				'remove_featured_image' => 'Remove!',
			),
		) );

		$this->cpts['rewrite-false'] = register_extended_post_type( 'rewrite-false', array(
			'public' => false,
			'rewrite' => [
				'slug' => 'rewrite-false',
			]
		) );

		$this->cpts['faq'] = register_extended_post_type( 'faq', array(
		), array(
			'plural'   => 'FAQs',
			'singular' => 'FAQ',
			'slug'     => 'faqs',
		) );

		$this->cpts['filterable'] = register_extended_post_type( 'filterable', array(
			'site_filters' => array(
				'test_site_filters_post_meta_key' => array(
					'meta_key' => 'test_meta_key',
					'default'  => 'Alpha',
				),
			),
		) );

		$wp_rewrite->flush_rules();

		foreach ( array( '0', 'Alpha', 'Beta', 'Gamma', 'Delta' ) as $slug ) {
			wp_insert_term( $slug, 'hello_category' );
			wp_insert_term( $slug, 'foo_category' );
		}

		// Post
		$this->posts['post'][] = self::factory()->post->create( array(
			'guid'      => 'guid',
			'post_type' => 'post',
			'post_date' => '1984-02-25 00:05:00'
		) );

		// Hello 0
		$this->posts['hello'][0] = self::factory()->post->create( array(
			'guid'      => 'guid',
			'post_type' => 'hello',
			'post_name' => 'Alpha',
			'post_date' => '1984-02-25 00:04:00'
		) );
		add_post_meta( $this->posts['hello'][0], 'test_meta_key', 'Delta' );
		wp_add_object_terms( $this->posts['hello'][0], 'Beta', 'hello_category' );

		// Hello 1
		$this->posts['hello'][1] = self::factory()->post->create( array(
			'guid'      => 'guid',
			'post_type' => 'hello',
			'post_name' => 'Delta',
			'post_date' => '1984-02-25 00:03:00'
		) );
		add_post_meta( $this->posts['hello'][1], 'test_meta_key', '0' );

		// Hello 2
		$this->posts['hello'][2] = self::factory()->post->create( array(
			'guid'      => 'guid',
			'post_type' => 'hello',
			'post_name' => 'Beta',
			'post_date' => '1984-02-25 00:02:00'
		) );
		add_post_meta( $this->posts['hello'][2], 'test_meta_key', 'Beta' );
		wp_add_object_terms( $this->posts['hello'][2], 'Alpha', 'hello_category' );

		// Hello 3
		$this->posts['hello'][3] = self::factory()->post->create( array(
			'guid'      => 'guid',
			'post_type' => 'hello',
			'post_name' => 'Gamma',
			'post_date' => '1984-02-25 00:01:00'
		) );
		wp_add_object_terms( $this->posts['hello'][3], 'Gamma', 'hello_category' );

		$this->posts['person'][0] = self::factory()->post->create( array(
			'guid'      => 'guid',
			'post_type' => 'person',
			'post_name' => 'Beta',
			'post_date' => '1984-02-25 00:01:00'
		) );
		$this->posts['person'][1] = self::factory()->post->create( array(
			'guid'      => 'guid',
			'post_type' => 'person',
			'post_name' => 'Alpha',
			'post_date' => '1984-02-25 00:02:00'
		) );
		$this->posts['nice-thing'][0] = self::factory()->post->create( array(
			'guid'      => 'guid',
			'post_type' => 'nice-thing',
		) );
		$this->posts['foo'][0] = self::factory()->post->create( array(
			'guid'        => 'guid',
			'post_type'   => 'foo',
			'post_author' => 1,
		) );
		wp_add_object_terms( $this->posts['foo'][0], array( 'Gamma', 'Delta' ), 'foo_category' );

		$this->posts['foo'][1] = self::factory()->post->create( array(
			'guid'        => 'guid',
			'post_type'   => 'foo',
			'post_author' => 0,
		) );
		wp_add_object_terms( $this->posts['foo'][1], array( 'Gamma' ), 'foo_category' );

		$this->posts['foo'][2] = self::factory()->post->create( array(
			'guid'        => 'guid',
			'post_type'   => 'foo',
			'post_author' => 1,
		) );

		$this->posts['bar'][0] = self::factory()->post->create( array(
			'guid'        => 'guid',
			'post_type'   => 'bar',
		) );

		$this->posts['baz'][0] = self::factory()->post->create( array(
			'guid'        => 'guid',
			'post_type'   => 'baz',
		) );

		$this->posts['filterable'][0] = self::factory()->post->create( array(
			'guid'        => 'guid',
			'post_type'   => 'filterable',
		) );
		add_post_meta( $this->posts['filterable'][0], 'test_meta_key', 'Alpha' );

		$this->posts['filterable'][1] = self::factory()->post->create( array(
			'guid'        => 'guid',
			'post_type'   => 'filterable',
		) );
		add_post_meta( $this->posts['filterable'][1], 'test_meta_key', 'Beta' );
	}

	protected static function get_minimum_version( string $type, string $filename ): ?string {
		$file = (string) file_get_contents( $filename );
		$pattern = '/^\* \*\*' . preg_quote( $type ) . ':\*\* (?P<version>[0-9]\.[0-9])/m';

		if ( ! preg_match( $pattern, $file, $matches ) ) {
			return null;
		}

		return $matches['version'];
	}
}
