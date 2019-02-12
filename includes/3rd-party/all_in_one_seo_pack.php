<?php
/**
 * Adds additional compatibility with All in One SEO Pack.
 */

/**
 * Skip resume listings in sitemap generation if the setting is enabled.
 *
 * @param WP_Post[] $posts
 * @return WP_Post[]
 */
function resume_manager_aiosp_discourage_search_index( $posts ) {
	foreach ( $posts as $index => $post ) {
		if ( $post instanceof WP_Post && 'resume' === $post->post_type ) {
			unset( $posts[ $index ] );
		}
	}
	return $posts;
}
if ( resume_manager_discourage_resume_search_indexing() ) {
	add_action( 'aiosp_sitemap_post_filter', 'resume_manager_aiosp_discourage_search_index', 10, 3 );
}
