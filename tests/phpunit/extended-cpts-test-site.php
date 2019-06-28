<?php

namespace ExtCPTs\Tests;

use ExtCPTs\Test;

abstract class Site extends Test {

	public function setUp() {
		parent::setUp();
		$this->register_post_types();
	}

}
