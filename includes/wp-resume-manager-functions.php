<?php
if ( ! function_exists( 'get_resumes' ) ) :
/**
 * Queries job listings with certain criteria and returns them
 *
 * @access public
 * @return void
 */
function get_resumes( $args = array() ) {
	global $wpdb, $resume_manager_keyword;

	$args = wp_parse_args( $args, array(
		'search_location'   => '',
		'search_keywords'   => '',
		'search_categories' => array(),
		'offset'            => '',
		'posts_per_page'    => '-1',
		'orderby'           => 'date',
		'order'             => 'DESC',
		'featured'          => null,
		'fields'            => 'all'
	) );

	$query_args = array(
		'post_type'              => 'resume',
		'post_status'            => 'publish',
		'ignore_sticky_posts'    => 1,
		'offset'                 => absint( $args['offset'] ),
		'posts_per_page'         => intval( $args['posts_per_page'] ),
		'orderby'                => $args['orderby'],
		'order'                  => $args['order'],
		'tax_query'              => array(),
		'meta_query'             => array(),
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,
		'cache_results'          => false,
		'fields'                 => $args['fields']
	);

	if ( $args['posts_per_page'] < 0 ) {
		$query_args['no_found_rows'] = true;
	}

	if ( ! empty( $args['search_location'] ) ) {
		$location_meta_keys = array( 'geolocation_formatted_address', '_candidate_location', 'geolocation_state_long' );
		$location_search    = array( 'relation' => 'OR' );
		foreach ( $location_meta_keys as $meta_key ) {
			$location_search[] = array(
				'key'     => $meta_key,
				'value'   => $args['search_location'],
				'compare' => 'like'
			);
		}
		$query_args['meta_query'][] = $location_search;
	}

	if ( ! is_null( $args['featured'] ) ) {
		$query_args['meta_query'][] = array(
			'key'     => '_featured',
			'value'   => '1',
			'compare' => $args['featured'] ? '=' : '!='
		);
	}

	if ( ! empty( $args['search_categories'] ) ) {
		$field    = is_numeric( $args['search_categories'][0] ) ? 'term_id' : 'slug';
		$operator = 'all' === get_option( 'resume_manager_category_filter_type', 'all' ) && sizeof( $args['search_categories'] ) > 1 ? 'AND' : 'IN';
		$query_args['tax_query'][] = array(
			'taxonomy'         => 'resume_category',
			'field'            => $field,
			'terms'            => array_values( $args['search_categories'] ),
			'include_children' => $operator !== 'AND' ,
			'operator'         => $operator
		);
	}

	if ( 'featured' === $args['orderby'] ) {
		$query_args['orderby'] = array(
			'menu_order' => 'ASC',
			'date'      => 'DESC',
			'ID'      => 'DESC',
		);
	}

	if ( 'rand_featured' === $args['orderby'] ) {
		$query_args['orderby'] = array(
			'menu_order' => 'ASC',
			'rand'       => 'ASC'
		);
	}

	if ( $resume_manager_keyword = sanitize_text_field( $args['search_keywords'] ) ) {
		$query_args['_keyword'] = $resume_manager_keyword; // Does nothing but needed for unique hash
		add_filter( 'posts_clauses', 'get_resumes_keyword_search' );
	}

	$query_args = apply_filters( 'resume_manager_get_resumes', $query_args, $args );

	if ( empty( $query_args['meta_query'] ) ) {
		unset( $query_args['meta_query'] );
	}

	if ( empty( $query_args['tax_query'] ) ) {
		unset( $query_args['tax_query'] );
	}

	// Filter args
	$query_args = apply_filters( 'get_resumes_query_args', $query_args, $args );

	// Generate hash
	$to_hash         = defined( 'ICL_LANGUAGE_CODE' ) ? json_encode( $query_args ) . ICL_LANGUAGE_CODE : json_encode( $query_args );
	$query_args_hash = 'jm_' . md5( $to_hash ) . WP_Job_Manager_Cache_Helper::get_transient_version( 'get_resume_listings' );

	do_action( 'before_get_resumes', $query_args, $args );
	$cached_query = true;
	if ( false === ( $result = get_transient( $query_args_hash ) ) ) {
		$cached_query = false;
		$result = new WP_Query( $query_args );
		set_transient( $query_args_hash, $result, DAY_IN_SECONDS * 30 );
	}
	if ( $cached_query ) {
		// random order is cached so shuffle them
		if ( 'rand_featured' === $args['orderby'] ) {
			usort( $result->posts, '_wpjm_resumes_shuffle_featured_post_results_helper' );
		} elseif ( 'rand' === $args['orderby'] ) {
			shuffle( $result->posts );
		}
	}
	do_action( 'after_get_resumes', $query_args, $args );

	remove_filter( 'posts_clauses', 'get_resumes_keyword_search' );

	return $result;
}
endif;

if ( ! function_exists( '_wpjm_resumes_shuffle_featured_post_results_helper' ) ) :
	/**
	 * Helper function to maintain featured status when shuffling results.
	 *
	 * @param WP_Post $a
	 * @param WP_Post $b
	 *
	 * @return bool
	 */
	function _wpjm_resumes_shuffle_featured_post_results_helper( $a, $b ) {
		if ( -1 === $a->menu_order || -1 === $b->menu_order ) {
			// Left is featured
			if ( 0 === $b->menu_order ) {
				return -1;
			}
			// Right is featured
			if ( 0 === $a->menu_order ) {
				return 1;
			}
		}
		return rand( -1, 1 );
	}
endif;

if ( ! function_exists( 'get_resumes_keyword_search' ) ) :
	/**
	 * Join and where query for keywords
	 *
	 * @param array $args
	 * @return array
	 */
	function get_resumes_keyword_search( $args ) {
		global $wpdb, $resume_manager_keyword;

		// Meta searching - Query matching ids to avoid more joins
		$post_ids = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value LIKE '%" . esc_sql( $resume_manager_keyword ) . "%'" );

		// Term searching
		$post_ids = array_merge( $post_ids, $wpdb->get_col( "SELECT object_id FROM {$wpdb->term_relationships} AS tr LEFT JOIN {$wpdb->terms} AS t ON tr.term_taxonomy_id = t.term_id WHERE t.name LIKE '" . esc_sql( $resume_manager_keyword ) . "%'" ) );

		// Title and content searching
		$conditions = array();
		$conditions[] = "{$wpdb->posts}.post_title LIKE '%" . esc_sql( $resume_manager_keyword ) . "%'";
		$conditions[] = "{$wpdb->posts}.post_content RLIKE '[[:<:]]" . esc_sql( $resume_manager_keyword ) . "[[:>:]]'";

		if ( $post_ids ) {
			$conditions[] = "{$wpdb->posts}.ID IN (" . esc_sql( implode( ',', array_unique( $post_ids ) ) ) . ")";
		}

		$args['where'] .= " AND ( " . implode( ' OR ', $conditions ) . " ) ";

		return $args;
	}
endif;

if ( ! function_exists( 'order_featured_resume' ) ) :
	/**
	 * WP Core doens't let us change the sort direction for invidual orderby params - http://core.trac.wordpress.org/ticket/17065
	 *
	 * @access public
	 * @param array $args
	 * @return array
	 */
	function order_featured_resume( $args ) {
		global $wpdb;

		$args['orderby'] = "$wpdb->postmeta.meta_value+0 DESC, $wpdb->posts.post_title ASC";

		return $args;
	}
endif;

if ( ! function_exists( 'get_resume_share_link' ) ) :
/**
 * Generates a sharing link which allows someone to view the resume directly (even if permissions do not usually allow it)
 *
 * @access public
 * @return array
 */
function get_resume_share_link( $resume_id ) {
	if ( ! $key = get_post_meta( $resume_id, 'share_link_key', true ) ) {
		$key = wp_generate_password( 32, false );
		update_post_meta( $resume_id, 'share_link_key', $key );
	}

	return add_query_arg( 'key', $key, get_permalink( $resume_id ) );
}
endif;

if ( ! function_exists( 'get_resume_categories' ) ) :
/**
 * Outputs a form to submit a new job to the site from the frontend.
 *
 * @access public
 * @return array
 */
function get_resume_categories() {
	if ( ! get_option( 'resume_manager_enable_categories' ) ) {
		return array();
	}

	return get_terms( "resume_category", array(
		'orderby'       => 'name',
	    'order'         => 'ASC',
	    'hide_empty'    => false,
	) );
}
endif;

if ( ! function_exists( 'resume_manager_get_filtered_links' ) ) :
/**
 * Shows links after filtering resumes
 */
function resume_manager_get_filtered_links( $args = array() ) {

	$links = apply_filters( 'resume_manager_resume_filters_showing_resumes_links', array(
		'reset' => array(
			'name' => __( 'Reset', 'wp-job-manager-resumes' ),
			'url'  => '#'
		)
	), $args );

	$return = '';

	foreach ( $links as $key => $link ) {
		$return .= '<a href="' . esc_url( $link['url'] ) . '" class="' . esc_attr( $key ) . '">' . $link['name'] . '</a>';
	}

	return $return;
}
endif;

/**
 * True if an the user can edit a resume.
 *
 * @param $resume_id
 *
 * @return bool
 */
function resume_manager_user_can_edit_resume( $resume_id ) {
	$can_edit = true;

	if ( ! $resume_id || ! is_user_logged_in() ) {
		$can_edit = false;
		if ( $resume_id
			 && ! resume_manager_user_requires_account()
			 && isset( $_COOKIE[ 'wp-job-manager-submitting-resume-key-' . $resume_id ] )
			 && $_COOKIE[ 'wp-job-manager-submitting-resume-key-' . $resume_id ] === get_post_meta( $resume_id, '_submitting_key', true )
		) {
			$can_edit = true;
		}
	} else {

		$resume = get_post( $resume_id );

		if ( ! $resume || ( absint( $resume->post_author ) !== get_current_user_id() && ! current_user_can( 'edit_post', $resume_id ) ) ) {
			$can_edit = false;
		}
	}

	return apply_filters( 'resume_manager_user_can_edit_resume', $can_edit, $resume_id );
}

/**
 * True if an the user can browse resumes.
 *
 * @return bool
 */
function resume_manager_user_can_browse_resumes() {
	$can_browse = true;
	$caps       = array_filter( array_map( 'trim', array_map( 'strtolower', explode( ',', get_option( 'resume_manager_browse_resume_capability' ) ) ) ) );

	if ( $caps ) {
		$can_browse = false;
		foreach ( $caps as $cap ) {
			if ( current_user_can( $cap ) ) {
				$can_browse = true;
				break;
			}
		}
	}

	return apply_filters( 'resume_manager_user_can_browse_resumes', $can_browse );
}

/**
 * True if an the user can view the full resume name.
 *
 * @return bool
 */
function resume_manager_user_can_view_resume_name( $resume_id ) {
	$can_view = true;
	$resume   = get_post( $resume_id );
	$caps     = array_filter( array_map( 'trim', array_map( 'strtolower', explode( ',', get_option( 'resume_manager_view_name_capability' ) ) ) ) );

	// Allow previews
	if ( $resume->post_status === 'preview' ) {
		return true;
	}

	if ( $caps ) {
		$can_view = false;
		foreach ( $caps as $cap ) {
			if ( current_user_can( $cap ) ) {
				$can_view = true;
				break;
			}
		}
	}

	if ( $resume->post_author > 0 && $resume->post_author == get_current_user_id() ) {
		$can_view = true;
	}

	if ( ( $key = get_post_meta( $resume_id, 'share_link_key', true ) ) && ! empty( $_GET['key'] ) && $key == $_GET['key'] ) {
		$can_view = true;
	}

	return apply_filters( 'resume_manager_user_can_view_resume_name', $can_view, $resume_id );
}

/**
 * Check if the option to discourage resume search indexing is enabled.
 *
 * @since 1.16.1
 *
 * @return bool
 */
function resume_manager_discourage_resume_search_indexing() {
	/**
	 * Allows overriding the option to discourage search indexing.
	 *
	 * @since 1.16.1
	 *
	 * @param bool $discourage_search_indexing
	 */
	return apply_filters( 'resume_manager_discourage_resume_search_indexing', 1 == get_option( 'resume_manager_discourage_resume_search_indexing' ) );
}

/**
 * True if an the user can view a resume.
 *
 * @return bool
 */
function resume_manager_user_can_view_resume( $resume_id ) {
	$can_view = true;
	$resume   = get_post( $resume_id );

	// Allow previews
	if ( $resume->post_status === 'preview' ) {
		return true;
	}

	$caps = array_filter( array_map( 'trim', array_map( 'strtolower', explode( ',', get_option( 'resume_manager_view_resume_capability' ) ) ) ) );

	if ( $caps ) {
		$can_view = false;
		foreach ( $caps as $cap ) {
			if ( current_user_can( $cap ) ) {
				$can_view = true;
				break;
			}
		}
	}

	if ( $resume->post_status === 'expired' ) {
		$can_view = false;
	}

	if ( $resume->post_author > 0 && $resume->post_author == get_current_user_id() ) {
		$can_view = true;
	}

	if ( ( $key = get_post_meta( $resume_id, 'share_link_key', true ) ) && ! empty( $_GET['key'] ) && $key == $_GET['key'] ) {
		$can_view = true;
	}

	return apply_filters( 'resume_manager_user_can_view_resume', $can_view, $resume_id );
}

/**
 * True if an the user can view a resume.
 *
 * @return bool
 */
function resume_manager_user_can_view_contact_details( $resume_id ) {
	$can_view = true;
	$resume   = get_post( $resume_id );
	$caps     = array_filter( array_map( 'trim', array_map( 'strtolower', explode( ',', get_option( 'resume_manager_contact_resume_capability' ) ) ) ) );

	if ( $caps ) {
		$can_view = false;
		foreach ( $caps as $cap ) {
			if ( current_user_can( $cap ) ) {
				$can_view = true;
				break;
			}
		}
	}

	if ( $resume->post_author > 0 && $resume->post_author == get_current_user_id() ) {
		$can_view = true;
	}

	if ( ( $key = get_post_meta( $resume_id, 'share_link_key', true ) ) && ! empty( $_GET['key'] ) && $key == $_GET['key'] ) {
		$can_view = true;
	}

	return apply_filters( 'resume_manager_user_can_view_contact_details', $can_view, $resume_id );
}

if ( ! function_exists( 'get_resume_post_statuses' ) ) :
/**
 * Get post statuses used for resumes
 *
 * @access public
 * @return array
 */
function get_resume_post_statuses() {
	return apply_filters( 'resume_post_statuses', array(
		'draft'           => _x( 'Draft', 'post status', 'wp-job-manager-resumes' ),
		'expired'         => _x( 'Expired', 'post status', 'wp-job-manager-resumes' ),
		'hidden'          => _x( 'Hidden', 'post status', 'wp-job-manager-resumes' ),
		'preview'         => _x( 'Preview', 'post status', 'wp-job-manager-resumes' ),
		'pending'         => _x( 'Pending approval', 'post status', 'wp-job-manager-resumes' ),
		'pending_payment' => _x( 'Pending payment', 'post status', 'wp-job-manager-resumes' ),
		'publish'         => _x( 'Published', 'post status', 'wp-job-manager-resumes' ),
	) );
}
endif;

/**
 * Upload dir
 */
function resume_manager_upload_dir( $dir, $field ) {
	if ( 'resume_file' === $field ) {
		$dir = 'resumes/resume_files';
	}
	return $dir;
}
add_filter( 'job_manager_upload_dir', 'resume_manager_upload_dir', 10, 2 );

/**
 * Count user resumes
 * @param  integer $user_id
 * @return int
 */
function resume_manager_count_user_resumes( $user_id = 0 ) {
	global $wpdb;

	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_author = %d AND post_type = 'resume' AND post_status IN ( 'publish', 'pending', 'expired', 'hidden' );", $user_id ) );
}

/**
 * Get the permalink of a page if set
 * @param  string $page e.g. candidate_dashboard, submit_resume_form, resumes
 * @return string|bool
 */
function resume_manager_get_permalink( $page ) {
	$page_id = get_option( 'resume_manager_' . $page . '_page_id', false );
	if ( $page_id ) {
		return get_permalink( $page_id );
	} else {
		return false;
	}
}

/**
 * Calculate and return the resume expiry date
 * @param  int $resume_id
 * @return string
 */
function calculate_resume_expiry( $resume_id ) {
	// Get duration from the product if set...
	$duration = get_post_meta( $resume_id, '_resume_duration', true );

	// ...otherwise use the global option
	if ( ! $duration ) {
		$duration = absint( get_option( 'resume_manager_submission_duration' ) );
	}

	if ( $duration ) {
		return date( 'Y-m-d', strtotime( "+{$duration} days", current_time( 'timestamp' ) ) );
	}

	return '';
}
