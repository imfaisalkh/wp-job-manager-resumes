<?php
/**
 * Adds additional compatibility with Yoast SEO.
 */

// Yoast SEO will by default include the `resume` post type because it is flagged as public.

/**
 * Skip resume listings in sitemap generation if the setting is enabled.
 *
 * @param array  $url  Array of URL parts.
 * @param string $type URL type.
 * @param object $post Post object.
 * @return array|bool False if we're skipping
 */
function resume_manager_yoast_discourage_search_index( $url, $type, $post ) {
	if ( 'resume' === $post->post_type ) {
		return false;
	}

	return $url;
}
if ( resume_manager_discourage_resume_search_indexing() ) {
	add_action( 'wpseo_sitemap_entry', 'resume_manager_yoast_discourage_search_index', 10, 3 );
}
