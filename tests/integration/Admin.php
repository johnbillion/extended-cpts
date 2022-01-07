<?php
declare( strict_types=1 );

namespace ExtCPTs\Tests;

abstract class Admin extends Test {

	public function setUp(): void {
		parent::setUp();

		// lie about being in the admin area so is_admin() returns true
		set_current_screen( 'edit.php' );
		self::assertTrue( is_admin() );

		$this->register_post_types();
	}

	/**
	 * @param array<string,mixed> $args
	 */
	protected function go_to_listing( array $args ): string {

		$_GET = $_REQUEST = $args;

		$GLOBALS['hook_suffix'] = 'edit.php';

		set_current_screen( 'edit-' . $args['post_type'] );
		do_action( 'load-edit.php' );

		wp_set_current_user( 1 ); // @TODO change

		$GLOBALS['wp_the_query'] = new \WP_Query( $args );
		$GLOBALS['wp_query'] = $GLOBALS['wp_the_query'];

		$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );

		$screen = get_current_screen();

		self::assertNotNull( $screen );
		self::assertSame( 'edit', $screen->base );
		self::assertSame( $args['post_type'], $screen->post_type );
		self::assertInstanceOf( 'WP_List_Table', $wp_list_table );

		ob_start();
		$wp_list_table->prepare_items();
		$wp_list_table->views();
		$wp_list_table->display();
		$output = (string) ob_get_clean();

		return $output;

	}

	/**
	 * @return array<string, mixed>
	 */
	protected function default_listing_vars(): array {
		return array(
			'posts_per_page' => 20,
			'order' => '',
			'orderby' => '',
			'perm' => '',
			'post_status' => '',
		);
	}

	public function tearDown(): void {
		parent::tearDown();

		// reset
		set_current_screen( 'front' );
	}

}
