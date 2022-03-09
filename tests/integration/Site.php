<?php
declare( strict_types=1 );

namespace ExtCPTs\Tests;

abstract class Site extends Test {

	public function setUp(): void {
		parent::setUp();
		$this->register_post_types();
	}

}
