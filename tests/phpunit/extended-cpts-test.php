<?php

abstract class Extended_CPT_Test extends WP_UnitTestCase {

	public $cpts  = array();
	public $taxos = array();
	public $posts = array();
	public $args  = array();

	protected function register_post_types() {

		global $wp_rewrite;

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );

		$this->args['hello'] = array(
			'site_sortables' => array(
				'test_site_sortables_post_meta' => array(
					'meta_key' => 'test_meta_key',
				),
				'test_site_sortables_post_field' => array(
					'post_field' => 'name',
				),
				'test_site_sortables_taxonomy' => array(
					'taxonomy' => 'hello_category',
				),
			),
			'admin_cols' => array(
				'test_admin_cols_post_meta' => array(
					'meta_key' => 'test_meta_key',
				),
				'test_admin_cols_post_field' => array(
					'post_field' => 'name',
				),
				'test_admin_cols_taxonomy' => array(
					'taxonomy' => 'hello_category',
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
				'test_site_filters_post_meta_query_deprecated' => array(
					'meta_key'     => 'test_meta_key',
					'meta_compare' => '>=',
					'meta_value'   => 'B',
					'meta_type'    => 'CHAR',
				),
				'test_site_filters_invalid' => array(
					'meta_query' => array(
						'key'     => 'foo',
						'value'   => 'bar',
					),
				),
			),
			'archive' => array(
				'orderby' => 'post_title',
			),
			'query_var' => 'hi',
		);

		$this->cpts['hello']  = register_extended_post_type( 'hello', $this->args['hello'] );
		$this->cpts['hello']->add_taxonomy( 'hello_category' );

		$this->cpts['person'] = register_extended_post_type( 'person', array(
			'has_archive'    => 'team',
			'show_in_feed'   => true,
			'site_sortables' => array(
				'test_site_sortables_post_name' => array(
					'post_field' => 'post_name',
					'default'    => 'asc',
				),
			),
			'admin_cols' => array(
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
			),
		), array(
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

		$wp_rewrite->flush_rules();

		foreach ( array( 'Alpha', 'Beta', 'Gamma', 'Delta' ) as $slug ) {
			wp_insert_term( $slug, 'hello_category' );
			wp_insert_term( $slug, 'foo_category' );
		}

		// Post
		$this->posts['post'][] = $this->factory->post->create( array(
			'guid'      => 'guid',
			'post_type' => 'post',
			'post_date' => '1984-02-25 00:05:00'
		) );

		// Hello 0
		$this->posts['hello'][0] = $this->factory->post->create( array(
			'guid'      => 'guid',
			'post_type' => 'hello',
			'post_name' => 'Alpha',
			'post_date' => '1984-02-25 00:04:00'
		) );
		add_post_meta( $this->posts['hello'][0], 'test_meta_key', 'Delta' );
		wp_add_object_terms( $this->posts['hello'][0], 'Beta', 'hello_category' );

		// Hello 1
		$this->posts['hello'][1] = $this->factory->post->create( array(
			'guid'      => 'guid',
			'post_type' => 'hello',
			'post_name' => 'Delta',
			'post_date' => '1984-02-25 00:03:00'
		) );
		add_post_meta( $this->posts['hello'][1], 'test_meta_key', 'Alpha' );

		// Hello 2
		$this->posts['hello'][2] = $this->factory->post->create( array(
			'guid'      => 'guid',
			'post_type' => 'hello',
			'post_name' => 'Beta',
			'post_date' => '1984-02-25 00:02:00'
		) );
		add_post_meta( $this->posts['hello'][2], 'test_meta_key', 'Beta' );
		wp_add_object_terms( $this->posts['hello'][2], 'Alpha', 'hello_category' );

		// Hello 3
		$this->posts['hello'][3] = $this->factory->post->create( array(
			'guid'      => 'guid',
			'post_type' => 'hello',
			'post_name' => 'Gamma',
			'post_date' => '1984-02-25 00:01:00'
		) );
		wp_add_object_terms( $this->posts['hello'][3], 'Gamma', 'hello_category' );

		$this->posts['person'][0] = $this->factory->post->create( array(
			'guid'      => 'guid',
			'post_type' => 'person',
			'post_name' => 'Beta',
			'post_date' => '1984-02-25 00:01:00'
		) );
		$this->posts['person'][1] = $this->factory->post->create( array(
			'guid'      => 'guid',
			'post_type' => 'person',
			'post_name' => 'Alpha',
			'post_date' => '1984-02-25 00:02:00'
		) );
		$this->posts['nice-thing'][0] = $this->factory->post->create( array(
			'guid'      => 'guid',
			'post_type' => 'nice-thing',
		) );
		$this->posts['foo'][0] = $this->factory->post->create( array(
			'guid'        => 'guid',
			'post_type'   => 'foo',
			'post_author' => 1,
		) );
		wp_add_object_terms( $this->posts['foo'][0], array( 'Gamma', 'Delta' ), 'foo_category' );

		$this->posts['bar'][0] = $this->factory->post->create( array(
			'guid'        => 'guid',
			'post_type'   => 'bar',
		) );

		$this->posts['baz'][0] = $this->factory->post->create( array(
			'guid'        => 'guid',
			'post_type'   => 'baz',
		) );

	}

	public function tearDown() {

		parent::tearDown();

		foreach ( $this->cpts as $cpt => $cpto ) {
			$pto = get_post_type_object( $cpt );
			if ( ! $pto->_builtin ) {
				_unregister_post_type( $cpt );
				_unregister_taxonomy( "{$cpt}_category" );
			}
		}

	}

}
