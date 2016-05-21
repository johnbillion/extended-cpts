<?php
/**
 * Extended_CPT loader
 *
 * Handles checking for and smartly loading the newest version of this library.
 *
 * @category  WordPressLibrary
 * @package   ExtendedCPTs
 * @author    John Blackbourn <https://johnblackbourn.com>
 * @copyright 2012-2016 John Blackbourn
 * @license   GPL v2 or later
 * @version   3.0.1
 * @link      http://johnbillion.com/extended-cpts/
 * @since     3.0.1
 */

/**
 * Copyright (c) 2016 John Blackbourn (https://johnblackbourn.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

/**
 * Loader versioning: http://jtsternberg.github.io/wp-lib-loader/
 */

if ( ! class_exists( 'Extended_CPT_302', false ) ) {

	/**
	 * Versioned loader class-name
	 *
	 * This ensures each version is loaded/checked.
	 *
	 * @category WordPressLibrary
	 * @package  ExtendedCPTs
	 * @author   John Blackbourn <https://johnblackbourn.com>
	 * @license  GPL-2.0+
	 * @version  3.0.1
	 * @link     http://johnbillion.com/extended-cpts/
	 * @since    3.0.2
	 */
	class Extended_CPT_302 {

		/**
		 * Extended_CPT version number
		 * @var   string
		 * @since 3.0.2
		 */
		const VERSION = '3.0.2';

		/**
		 * Current version hook priority.
		 * Will decrement with each release
		 *
		 * @var   int
		 * @since 3.0.2
		 */
		const PRIORITY = 9999;

		/**
		 * Starts the version checking process.
		 * Creates EXTENDED_CPT_LOADED definition for early detection by
		 * other scripts.
		 *
		 * Hooks Extended_CPT inclusion to the extended_cpt_load hook
		 * on a high priority which decrements (increasing the priority) with
		 * each version release.
		 *
		 * @since 3.0.2
		 */
		public function __construct() {
			if ( ! defined( 'EXTENDED_CPT_LOADED' ) ) {
				/**
				 * A constant you can use to check if Extended_CPT is loaded
				 * for your plugins/themes with Extended_CPT dependency.
				 *
				 * Can also be used to determine the priority of the hook
				 * in use for the currently loaded version.
				 */
				define( 'EXTENDED_CPT_LOADED', self::PRIORITY );
			}

			// Use the hook system to ensure only the newest version is loaded.
			add_action( 'extended_cpt_load', array( $this, 'include_lib' ), self::PRIORITY );

			/*
			 * Hook in to the first hook we have available and
			 * fire our `extended_cpt_load' hook.
			 */
			add_action( 'muplugins_loaded', array( __CLASS__, 'fire_hook' ), 9 );
			add_action( 'plugins_loaded', array( __CLASS__, 'fire_hook' ), 9 );
			add_action( 'after_setup_theme', array( __CLASS__, 'fire_hook' ), 9 );
		}

		/**
		 * Fires the extended_cpt_load action hook.
		 *
		 * @since 3.0.2
		 */
		public static function fire_hook() {
			if ( ! did_action( 'extended_cpt_load' ) ) {
				// Then fire our hook.
				do_action( 'extended_cpt_load' );
			}
		}

		/**
		 * A final check if Extended_CPT exists before kicking off
		 * our Extended_CPT loading.
		 *
		 * EXTENDED_CPT_VERSION and EXTENDED_CPT_DIR constants are
		 * set at this point.
		 *
		 * @since  3.0.2
		 */
		public function include_lib() {
			if ( class_exists( 'Extended_CPT', false ) ) {
				return;
			}

			if ( ! defined( 'EXTENDED_CPT_VERSION' ) ) {
				/**
				 * Defines the currently loaded version of Extended_CPT.
				 */
				define( 'EXTENDED_CPT_VERSION', self::VERSION );
			}

			if ( ! defined( 'EXTENDED_CPT_DIR' ) ) {
				/**
				 * Defines the directory of the currently loaded version of Extended_CPT.
				 */
				define( 'EXTENDED_CPT_DIR', dirname( __FILE__ ) . '/' );
			}

			// Include and initiate Extended_CPT.
			require_once EXTENDED_CPT_DIR . 'class-extended-cpt.php';
		}

	}

	// Kick it off.
	new Extended_CPT_302;
}
