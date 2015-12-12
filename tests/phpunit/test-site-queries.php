<?php

class Extended_CPT_Test_Site_Queries extends Extended_CPT_Test_Site {

	public function testDefaultPostTypeQueryNotAffected() {

		$query = $this->get_query( array(
			'post_type' => 'post',
		) );

		$this->assertEquals( 1, $query->found_posts );

		$this->assertSame( '',     $query->get( 'orderby' ) ); // date
		$this->assertSame( 'DESC', $query->get( 'order' ) );
		$this->assertSame( '',     $query->get( 'meta_key' ) );
		$this->assertSame( '',     $query->get( 'meta_value' ) );
		$this->assertSame( '',     $query->get( 'meta_query' ) );

		$this->assertEquals( $this->posts['post'], wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQueryWithNoArgsNotAffected() {

		$query = $this->get_query( array(
			'post_type' => 'hello',
		) );

		$this->assertEquals( count( $this->posts['hello'] ), $query->found_posts );

		$this->assertSame( '',     $query->get( 'orderby' ) ); // date
		$this->assertSame( 'DESC', $query->get( 'order' ) );
		$this->assertSame( '',     $query->get( 'meta_key' ) );
		$this->assertSame( '',     $query->get( 'meta_value' ) );
		$this->assertSame( '',     $query->get( 'meta_query' ) );

		$this->assertEquals( $this->posts['hello'], wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQueryWithNoCustomValuesNotAffected() {

		$query = $this->get_query( array(
			'post_type' => 'hello',
			'orderby'   => 'post_name',
			'order'     => 'ASC',
		) );

		$this->assertEquals( count( $this->posts['hello'] ), $query->found_posts );

		$this->assertSame( 'post_name', $query->get( 'orderby' ) );
		$this->assertSame( 'ASC',       $query->get( 'order' ) );
		$this->assertSame( '',          $query->get( 'meta_key' ) );
		$this->assertSame( '',          $query->get( 'meta_value' ) );
		$this->assertSame( '',          $query->get( 'meta_query' ) );

		$this->assertEquals( array(
			$this->posts['hello'][0],
			$this->posts['hello'][2],
			$this->posts['hello'][1],
			$this->posts['hello'][3],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQuerySortedByPostMeta() {

		$query = $this->get_query( array(
			'post_type' => 'hello',
			'orderby'   => 'test_site_sortables_post_meta',
			'order'     => 'ASC',
		) );

		$this->assertEquals( 3, $query->found_posts );

		$this->assertSame( 'meta_value',    $query->get( 'orderby' ) );
		$this->assertSame( 'ASC',           $query->get( 'order' ) );
		$this->assertSame( 'test_meta_key', $query->get( 'meta_key' ) );
		$this->assertSame( '',              $query->get( 'meta_value' ) );
		$this->assertSame( '',              $query->get( 'meta_query' ) );

		$this->assertEquals( array(
			$this->posts['hello'][1],
			$this->posts['hello'][2],
			$this->posts['hello'][0],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQuerySortedByPostField() {

		$query = $this->get_query( array(
			'post_type' => 'hello',
			'orderby'   => 'test_site_sortables_post_field',
			'order'     => 'ASC',
		) );

		$this->assertEquals( count( $this->posts['hello'] ), $query->found_posts );

		$this->assertSame( 'name', $query->get( 'orderby' ) );
		$this->assertSame( 'ASC',  $query->get( 'order' ) );
		$this->assertSame( '',     $query->get( 'meta_key' ) );
		$this->assertSame( '',     $query->get( 'meta_value' ) );
		$this->assertSame( '',     $query->get( 'meta_query' ) );

		$this->assertEquals( array(
			$this->posts['hello'][0],
			$this->posts['hello'][2],
			$this->posts['hello'][1],
			$this->posts['hello'][3],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQuerySortedByTaxonomyTerms() {

		$query = $this->get_query( array(
			'post_type' => 'hello',
			'orderby'   => 'test_site_sortables_taxonomy',
			'order'     => 'DESC',
		) );

		$this->assertEquals( count( $this->posts['hello'] ), $query->found_posts );

		$this->assertSame( 'test_site_sortables_taxonomy', $query->get( 'orderby' ) );
		$this->assertSame( 'DESC',                         $query->get( 'order' ) );
		$this->assertSame( '',                             $query->get( 'meta_key' ) );
		$this->assertSame( '',                             $query->get( 'meta_value' ) );
		$this->assertSame( '',                             $query->get( 'meta_query' ) );

		$this->assertEquals( array(
			$this->posts['hello'][3],
			$this->posts['hello'][0],
			$this->posts['hello'][2],
			$this->posts['hello'][1],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQueryFilteredByPostMetaKey() {

		$query = $this->get_query( array(
			'post_type'                       => 'hello',
			'test_site_filters_post_meta_key' => 'Alpha',
		) );

		$meta_query = $query->get( 'meta_query' );

		$this->assertEquals( 1, $query->found_posts );

		$this->assertSame( '',              $query->get( 'meta_key' ) );
		$this->assertSame( '',              $query->get( 'meta_value' ) );
		$this->assertSame( 'test_meta_key', $meta_query[0]['key'] );
		$this->assertSame( 'Alpha',         $meta_query[0]['value'] );

		$this->assertEquals( array(
			$this->posts['hello'][1],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQueryFilteredByPostMetaQuery() {

		$query = $this->get_query( array(
			'post_type'                         => 'hello',
			'test_site_filters_post_meta_query' => 'ZZZ',
		) );

		$meta_query = $query->get( 'meta_query' );

		$this->assertEquals( 2, $query->found_posts );

		$this->assertSame( '', $query->get( 'meta_key' ) );
		$this->assertSame( '', $query->get( 'meta_value' ) );
		$this->assertEquals( array(
			'key'     => 'test_meta_key',
			'value'   => 'B',
			'compare' => '>=',
			'type'    => 'CHAR',
		), $meta_query[0] );

		$this->assertEquals( array(
			$this->posts['hello'][0],
			$this->posts['hello'][2],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQueryFilteredByInvalidFilter() {

		$query = $this->get_query( array(
			'post_type'                 => 'hello',
			'test_site_filters_invalid' => 'ZZZ',
		) );

		$meta_query = $query->get( 'meta_query' );

		$this->assertSame( '', $query->get( 'meta_key' ) );
		$this->assertSame( '', $query->get( 'meta_value' ) );
		$this->assertEmpty( $meta_query );

		$this->assertEquals( $this->posts['hello'], wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQueryFilteredByPostMetaSearch() {

		$query = $this->get_query( array(
			'post_type'                          => 'hello',
			'test_site_filters_post_meta_search' => 'ta',
		) );

		$meta_query = $query->get( 'meta_query' );

		$this->assertEquals( 2, $query->found_posts );

		$this->assertSame( '',              $query->get( 'meta_key' ) );
		$this->assertSame( '',              $query->get( 'meta_value' ) );
		$this->assertSame( 'test_meta_key', $meta_query[0]['key'] );
		$this->assertSame( 'ta',            $meta_query[0]['value'] );
		$this->assertSame( 'LIKE',          $meta_query[0]['compare'] );

		$this->assertEquals( array(
			$this->posts['hello'][0],
			$this->posts['hello'][2],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQueryFilteredByPostMetaExists() {

		$query = $this->get_query( array(
			'post_type'                          => 'hello',
			'test_site_filters_post_meta_exists' => 'test_meta_key',
		) );

		$meta_query = $query->get( 'meta_query' );

		$this->assertEquals( 3, $query->found_posts );

		$this->assertSame( '',              $query->get( 'meta_key' ) );
		$this->assertSame( '',              $query->get( 'meta_value' ) );
		$this->assertSame( 'test_meta_key', $meta_query[0]['key'] );
		$this->assertSame( 'NOT IN',        $meta_query[0]['compare'] );

		$this->assertEquals( array( '', '0', 'false', 'null' ), $meta_query[0]['value'] );

		$this->assertEquals( array(
			$this->posts['hello'][0],
			$this->posts['hello'][1],
			$this->posts['hello'][2],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQueryNotFilteredWithoutRequiredCap() {

		$query = $this->get_query( array(
			'post_type'                  => 'hello',
			'test_site_filters_with_cap' => 'Alpha',
		) );

		$this->assertEquals( count( $this->posts['hello'] ), $query->found_posts );

		$this->assertSame( '',     $query->get( 'orderby' ) ); // date
		$this->assertSame( 'DESC', $query->get( 'order' ) );
		$this->assertSame( '',     $query->get( 'meta_key' ) );
		$this->assertSame( '',     $query->get( 'meta_value' ) );
		$this->assertSame( '',     $query->get( 'meta_query' ) );

		$this->assertEquals( $this->posts['hello'], wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQueryWithDefaultSortOrder() {

		$query = $this->get_query( array(
			'post_type' => 'person',
		) );

		$this->assertEquals( count( $this->posts['person'] ), $query->found_posts );

		$this->assertSame( 'name', $query->get( 'orderby' ) );
		$this->assertSame( 'ASC',  $query->get( 'order' ) );
		$this->assertSame( '',     $query->get( 'meta_key' ) );
		$this->assertSame( '',     $query->get( 'meta_value' ) );
		$this->assertSame( '',     $query->get( 'meta_query' ) );

		$this->assertEquals( array(
			$this->posts['person'][1],
			$this->posts['person'][0],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	protected function get_query( array $args ) {
		$args = array_merge( array(
			'nopaging' => true,
		), $args );
		return new WP_Query( $args );
	}

}
