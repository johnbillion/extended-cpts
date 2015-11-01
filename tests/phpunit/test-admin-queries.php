<?php

class Extended_CPT_Test_Admin_Queries extends Extended_CPT_Test_Admin {

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

		$this->assertSame( 'menu_order title', $query->get( 'orderby' ) );
		$this->assertSame( 'ASC',              $query->get( 'order' ) );
		$this->assertSame( '',                 $query->get( 'meta_key' ) );
		$this->assertSame( '',                 $query->get( 'meta_value' ) );
		$this->assertSame( '',                 $query->get( 'meta_query' ) );

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
			'orderby'   => 'test_admin_cols_post_meta',
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
			'orderby'   => 'test_admin_cols_post_field',
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
			'orderby'   => 'test_admin_cols_taxonomy',
			'order'     => 'DESC',
		) );

		$this->assertEquals( count( $this->posts['hello'] ), $query->found_posts );

		$this->assertSame( 'test_admin_cols_taxonomy', $query->get( 'orderby' ) );
		$this->assertSame( 'DESC',                     $query->get( 'order' ) );
		$this->assertSame( '',                         $query->get( 'meta_key' ) );
		$this->assertSame( '',                         $query->get( 'meta_value' ) );
		$this->assertSame( '',                         $query->get( 'meta_query' ) );

		$this->assertEquals( array(
			$this->posts['hello'][3],
			$this->posts['hello'][0],
			$this->posts['hello'][2],
			$this->posts['hello'][1],
		), wp_list_pluck( $query->posts, 'ID' ) );

	}

	public function testQueryWithDefaultSortOrder() {

		$query = $this->get_query( array(
			'post_type' => 'person',
		) );

		$this->assertEquals( count( $this->posts['person'] ), $query->found_posts );

		$this->assertSame( 'menu_order title', $query->get( 'orderby' ) );
		$this->assertSame( 'ASC',  $query->get( 'order' ) );
		$this->assertSame( '',     $query->get( 'meta_key' ) );
		$this->assertSame( '',     $query->get( 'meta_value' ) );
		$this->assertSame( '',     $query->get( 'meta_query' ) );

		$this->assertEquals( $this->posts['person'], wp_list_pluck( $query->posts, 'ID' ) );

	}

	protected function get_query( array $args ) {
		global $wp_query, $wp_the_query;

		$wp_the_query = $wp_query = new WP_Query;

		wp_edit_posts_query( $args );

		return $wp_query;
	}

}
