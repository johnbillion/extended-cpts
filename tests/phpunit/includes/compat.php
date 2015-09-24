<?php
/*
 * Because we are using the WordPress tests from trunk, we may occasionally
 * find functions are called which are not included in the version of
 * WordPress which we are testing against. This file conditionally
 * defines these functions so that the tests will not fatal.
 *
 * A function can be removed from this file once the minimum version we
 * test against is greater than the `@since` of the function.
 */

if ( ! function_exists( 'is_post_type_viewable' ) ) :
/**
 * Determines whether a post type is considered "viewable".
 *
 * For built-in post types such as posts and pages, the 'public' value will be evaluated.
 * For all others, the 'publicly_queryable' value will be used.
 *
 * @since 4.4.0
 *
 * @param object $post_type_object Post type object.
 * @return bool Whether the post type should be considered viewable.
 */
function is_post_type_viewable( $post_type_object ) {
	return $post_type_object->publicly_queryable || ( $post_type_object->_builtin && $post_type_object->public );
}
endif;
