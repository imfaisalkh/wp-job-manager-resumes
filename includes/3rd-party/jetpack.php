<?php
/**
 * Adds additional compatibility with Jetpack.
 */

/**
 * Add `resume` post type to sitemap.
 *
 * @param array $post_types
 * @return array
 */
function resume_manager_jetpack_add_post_type( $post_types ) {
	$post_types[] = 'resume';
	return $post_types;
}
if ( ! resume_manager_discourage_resume_search_indexing() ) {
	add_filter( 'jetpack_sitemap_post_types', 'resume_manager_jetpack_add_post_type' );
}
