<?php
declare( strict_types=1 );

/**
 * Extended custom post types and taxonomies for WordPress.
 *
 * @package   ExtendedCPTs
 * @author    John Blackbourn <https://johnblackbourn.com>
 * @link      https://github.com/johnbillion/extended-cpts
 * @copyright 2012-2023 John Blackbourn
 * @license   GPL v2 or later
 * @version   5.0.6
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/src/PostType.php';
require_once __DIR__ . '/src/PostTypeAdmin.php';
require_once __DIR__ . '/src/Taxonomy.php';
require_once __DIR__ . '/src/TaxonomyAdmin.php';
require_once __DIR__ . '/src/Walker/Checkboxes.php';
require_once __DIR__ . '/src/Walker/Dropdown.php';
require_once __DIR__ . '/src/Walker/Radios.php';
