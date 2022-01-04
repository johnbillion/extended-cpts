<?php
declare( strict_types=1 );

namespace ExtCPTs\Tests;

abstract class Admin extends Test {

	public function setUp(): void {
		parent::setUp();

		// lie about being in the admin area so is_admin() returns true
		set_current_screen( 'edit.php' );
		$this->assertTrue( is_admin() );

		$this->register_post_types();
	}

	protected function go_to_listing( array $args ) {

		$_GET = $_REQUEST = $args;

		$GLOBALS['hook_suffix'] = 'edit.php';

		set_current_screen( 'edit-' . $args['post_type'] );
		do_action( 'load-edit.php' );

		wp_set_current_user( 1 ); // @TODO change

		$GLOBALS['wp_the_query'] = new \WP_Query( $args );
		$GLOBALS['wp_query']     = $GLOBALS['wp_the_query'];

		$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );

		$this->assertSame( 'edit', get_current_screen()->base );
		$this->assertSame( $args['post_type'], get_current_screen()->post_type );
		$this->assertInstanceOf( 'WP_List_Table', $wp_list_table );

		ob_start();
		$wp_list_table->prepare_items();
		$wp_list_table->views();
		$wp_list_table->display();
		$output = ob_get_clean();

		return $output;

	}

	protected function default_listing_vars() {
		$vars = array(
			'posts_per_page' => 20,
		);

		// https://core.trac.wordpress.org/changeset/44338
		if ( version_compare( $GLOBALS['wp_version'], '5.0.2', '>=' ) ) {
			$vars['order']       = '';
			$vars['orderby']     = '';
			$vars['perm']        = '';
			$vars['post_status'] = '';
		}

		return $vars;
	}

	public function tearDown(): void {
		parent::tearDown();

		// reset
		set_current_screen( 'front' );
	}

}
