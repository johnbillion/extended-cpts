<?php

namespace ExtCPTs\Tests;

class Docs extends \Johnbillion\DocsStandards\TestCase {

	protected function getTestFunctions() {
		return array(
			'register_extended_post_type',
			'register_extended_taxonomy',
		);
	}

	protected function getTestClasses() {
		return array(
			'Extended_CPT',
			'Extended_CPT_Admin',
			'Extended_Taxonomy',
			'Extended_Taxonomy_Admin',
		);
	}

}
