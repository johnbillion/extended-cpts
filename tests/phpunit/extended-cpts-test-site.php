<?php

abstract class Extended_CPT_Test_Site extends Extended_CPT_Test {

	public function setUp() {
		parent::setUp();
		$this->register_post_types();
	}

}
