<?php

abstract class Extended_CPT_Test_Admin extends Extended_CPT_Test {

	public function setUp() {
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
		return array(
			'posts_per_page' => 20,
		);
	}

	public function tearDown() {
		parent::tearDown();

		// reset
		set_current_screen( 'front' );
	}

}
