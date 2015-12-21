<?php

class Extended_CPT_Test_Reflection extends \Johnbillion\WPDocsStandards\TestCase {

	protected function getTestFunctions() {
		return array(
			'register_extended_post_type',
		);
	}

	protected function getTestClasses() {
		return array(
			'Extended_CPT',
			'Extended_CPT_Admin',
		);
	}

}
