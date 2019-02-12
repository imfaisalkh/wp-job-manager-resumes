<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WP_Resume_Manager_Ajax class.
 */
class WP_Resume_Manager_Ajax {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_nopriv_resume_manager_get_resumes', array( $this, 'get_resumes' ) );
		add_action( 'wp_ajax_resume_manager_get_resumes', array( $this, 'get_resumes' ) );
	}

	/**
	 * Get resumes via ajax
	 */
	public function get_resumes() {
		global $wpdb;

		ob_start();

		$search_location   = sanitize_text_field( stripslashes( $_POST['search_location'] ) );
		$search_keywords   = sanitize_text_field( stripslashes( $_POST['search_keywords'] ) );
		$search_categories = isset( $_POST['search_categories'] ) ? $_POST['search_categories'] : '';

		if ( is_array( $search_categories ) ) {
			$search_categories = array_map( 'sanitize_text_field', array_map( 'stripslashes', $search_categories ) );
		} else {
			$search_categories = array( sanitize_text_field( stripslashes( $search_categories ) ), 0 );
		}

		$search_categories = array_filter( $search_categories );

		$args = array(
			'search_location'   => $search_location,
			'search_keywords'   => $search_keywords,
			'search_categories' => $search_categories,
			'orderby'           => sanitize_text_field( $_POST['orderby'] ),
			'order'             => sanitize_text_field( $_POST['order'] ),
			'offset'            => ( absint( $_POST['page'] ) - 1 ) * absint( $_POST['per_page'] ),
			'posts_per_page'    => absint( $_POST['per_page'] )
		);

		if ( isset( $_POST['featured'] ) && ( $_POST['featured'] === 'true' || $_POST['featured'] === 'false' ) ) {
			$args['featured'] = $_POST['featured'] === 'true' ? true : false;
		}

		$resumes = get_resumes( apply_filters( 'resume_manager_get_resumes_args', $args ) );

		$result = array();
		$result['found_resumes'] = false;

		if ( $resumes->have_posts() ) : $result['found_resumes'] = true; ?>

			<?php while ( $resumes->have_posts() ) : $resumes->the_post(); ?>

				<?php get_job_manager_template_part( 'content', 'resume', 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' ); ?>

			<?php endwhile; ?>

		<?php else : ?>

			<li class="no_resumes_found"><?php _e( 'No resumes found matching your selection.', 'wp-job-manager-resumes' ); ?></li>

		<?php endif;

		$result['html']    = ob_get_clean();

		// Generate 'showing' text
		if ( $search_keywords || $search_location || $search_categories || apply_filters( 'resume_manager_get_resumes_custom_filter', false ) ) {

			$showing_categories = array();

			if ( $search_categories ) {
				foreach ( $search_categories as $category ) {
					if ( ! is_numeric( $category ) ) {
						$category_object = get_term_by( 'slug', $category, 'resume_category' );
					}
					if ( is_numeric( $category ) || is_wp_error( $category_object ) || ! $category_object ) {
						$category_object = get_term_by( 'id', $category, 'resume_category' );
					}
					if ( ! is_wp_error( $category_object ) ) {
						$showing_categories[] = $category_object->name;
					}
				}
			}

			if ( $search_keywords ) {
				$showing_resumes  = sprintf( __( 'Showing &ldquo;%s&rdquo; %sresumes', 'wp-job-manager-resumes' ), $search_keywords, implode( ', ', $showing_categories ) );
			} else {
				$showing_resumes  = sprintf( __( 'Showing all %sresumes', 'wp-job-manager-resumes' ), implode( ', ', $showing_categories ) . ' ' );
			}

			$showing_location  = $search_location ? sprintf( ' ' . __( 'located in &ldquo;%s&rdquo;', 'wp-job-manager-resumes' ), $search_location ) : '';

			$result['showing'] = apply_filters( 'resume_manager_get_resumes_custom_filter_text', $showing_resumes . $showing_location );

		} else {
			$result['showing'] = '';
		}

		// Generate RSS link
		$result['showing_links'] = resume_manager_get_filtered_links( array(
			'search_location'   => $search_location,
			'search_categories' => $search_categories,
			'search_keywords'   => $search_keywords
		) );

		// Generate pagination
		if ( isset( $_POST['show_pagination'] ) && $_POST['show_pagination'] === 'true' ) {
			$result['pagination'] = get_job_listing_pagination( $resumes->max_num_pages, absint( $_POST['page'] ) );
		}

		$result['max_num_pages'] = $resumes->max_num_pages;

		echo '<!--WPJM-->';
		echo json_encode( $result );
		echo '<!--WPJM_END-->';

		die();
	}
}

new WP_Resume_Manager_Ajax();