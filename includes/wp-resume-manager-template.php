<?php
/**
 * Template Functions
 *
 * Template functions specifically created for resumes
 *
 * @author 		Mike Jolley
 * @category 	Core
 * @package 	Resume Manager/Template
 * @version     1.0.0
 */

/**
 * Echo the location for a resume/candidate
 * @param  boolean $map_link whether or not to link to the map on google maps
 * @param WP_Post|int $post (default: null)
 */
function the_candidate_location( $map_link = true, $post = null ) {
	$location = get_the_candidate_location( $post );

	if ( $location ) {
		if ( $map_link )
			echo apply_filters( 'the_candidate_location_map_link', '<a class="google_map_link candidate-location" href="http://maps.google.com/maps?q=' . urlencode( $location ) . '&zoom=14&size=512x512&maptype=roadmap&sensor=false">' . $location . '</a>', $location, $post );
		else
			echo '<span class="candidate-location">' . $location . '</span>';
	}
}

/**
 * Get the location for a resume/candidate
 *
 * @param WP_Post|int $post (default: null)
 * @return string
 */
function get_the_candidate_location( $post = null ) {
	$post = get_post( $post );
	if ( $post->post_type !== 'resume' )
		return;

	return apply_filters( 'the_candidate_location', $post->_candidate_location, $post );
}

/**
 * Display a candidates given job title
 *
 * @param  string  $before
 * @param  string  $after
 * @param  boolean $echo
 * @param WP_Post|int $post (default: null)
 * @return string
 */
function the_candidate_title( $before = '', $after = '', $echo = true, $post = null ) {
	$title = get_the_candidate_title( $post );

	if ( strlen( $title ) == 0 )
		return;

	$title = esc_attr( strip_tags( $title ) );
	$title = $before . $title . $after;

	if ( $echo )
		echo $title;
	else
		return $title;
}

/**
 * Get a candidates given job title
 *
 * @param WP_Post|int $post (default: null)
 * @return string
 */
function get_the_candidate_title( $post = null ) {
	$post = get_post( $post );

	if ( $post->post_type !== 'resume' )
		return '';

	return apply_filters( 'the_candidate_title', $post->_candidate_title, $post );
}

/**
 * Display friendly drop-down select descriptor for resume.
 *
 * @since 1.15.5
 *
 * @param  string  $before
 * @param  string  $after
 * @param  boolean $echo
 * @param WP_Post|int $post (default: null)
 * @return string
 */
function the_resume_select_label( $before = '', $after = '', $echo = true, $post = null ) {
	$title = get_resume_select_label( $post );

	if ( strlen( $title ) == 0 )
		return;

	$title = esc_attr( strip_tags( $title ) );
	$title = $before . $title . $after;

	if ( $echo ) {
		echo $title;
	} else {
		return $title;
	}
}

/**
 * Get the friendly drop-down select descriptor for resume.
 *
 * @since 1.15.5
 *
 * @param WP_Post|int $post (default: null)
 * @return string
 */
function get_resume_select_label( $post = null ) {
	$post = get_post( $post );

	if ( $post->post_type !== 'resume' )
		return '';

	$title =  get_the_candidate_title( $post );
	$label = $post->post_title;
	if ( ! empty( $title ) ) {
		$label .= ' (' . $title . ')';
	}

	/**
	 * Filters the label used for resumes in drop-down select lists.
	 *
	 * @since 1.15.5
	 *
	 * @param string  $label  Label to be filtered.
	 * @param WP_Post $post Resume to get label for.
	 */
	return apply_filters( 'resume_manager_resume_select_label', $label, $post );
}

/**
 * Output the photo for the resume/candidate
 *
 * @param string $size (default: 'full')
 * @param mixed $default (default: null)
 * @param WP_Post|int $post (default: null)
 */
function the_candidate_photo( $size = 'thumbnail', $default = null, $post = null ) {
	$logo = get_the_candidate_photo( $post );

	if ( $logo ) {

		if ( $size !== 'full' ) {
			$logo = job_manager_get_resized_image( $logo, $size );
		}

		echo '<img class="candidate_photo" src="' . $logo . '" alt="Photo" />';

	} elseif ( $default )
		echo '<img class="candidate_photo" src="' . $default . '" alt="Photo" />';
	else
		echo '<img class="candidate_photo" src="' . apply_filters( 'resume_manager_default_candidate_photo', RESUME_MANAGER_PLUGIN_URL . '/assets/images/candidate.png' ) . '" alt="Logo" />';
}

/**
 * Get the photo for the resume/candidate
 *
 * @param WP_Post|int $post (default: null)
 * @return string
 */
function get_the_candidate_photo( $post = null ) {
	$post = get_post( $post );
	if ( $post->post_type !== 'resume' )
		return;

	return apply_filters( 'the_candidate_photo', $post->_candidate_photo, $post );
}

/**
 * Output the category
 * @param WP_Post|int $post (default: null)
 */
function the_resume_category( $post = null ) {
	echo get_the_resume_category( $post );
}

/**
 * Get the category
 * @param WP_Post|int $post (default: null)
 * @return  string
 */
function get_the_resume_category( $post = null ) {
	$post = get_post( $post );
	if ( $post->post_type !== 'resume' )
		return '';

	if ( ! get_option( 'resume_manager_enable_categories' ) )
		return '';

	$categories = wp_get_object_terms( $post->ID, 'resume_category', array( 'fields' => 'names' ) );

	if ( is_wp_error( $categories ) ) {
		return '';
	}

	return implode( ', ', $categories );
}

/**
 * Outputs the jobs status
 *
 * @param WP_Post|int $post (default: null)
 */
function the_resume_status( $post = null ) {
	echo get_the_resume_status( $post );
}

/**
 * Gets the jobs status
 * @param WP_Post|int $post (default: null)
 * @return string
 */
function get_the_resume_status( $post = null ) {
	$post = get_post( $post );

	$status = $post->post_status;

	if ( $status == 'publish' )
		$status = __( 'Published', 'wp-job-manager-resumes' );
	elseif ( $status == 'expired' )
		$status = __( 'Expired', 'wp-job-manager-resumes' );
	elseif ( $status == 'pending' )
		$status = __( 'Pending Review', 'wp-job-manager-resumes' );
	elseif ( $status == 'hidden' )
		$status = __( 'Hidden', 'wp-job-manager-resumes' );
	else
		$status = __( 'Inactive', 'wp-job-manager-resumes' );

	return apply_filters( 'the_resume_status', $status, $post );
}

/**
 * Returns the registration fields used when an account is required.
 *
 * @since 1.18.0
 *
 * @return array $registration_fields.
 */
function resume_manager_get_registration_fields() {
	$generate_username_from_email      = resume_manager_generate_username_from_email();
	$use_standard_password_setup_email = resume_manager_use_standard_password_setup_email();
	$account_required                  = resume_manager_user_requires_account();

	$registration_fields = array();
	if ( resume_manager_enable_registration() ) {
		if ( ! $generate_username_from_email ) {
			$registration_fields['create_account_username'] = array(
				'type'     => 'text',
				'label'    => esc_html__( 'Username', 'wp-job-manager-resumes' ),
				'required' => $account_required,
				'value'    => isset( $_POST['create_account_username'] ) ? $_POST['create_account_username'] : '',
			);
		}
		if ( ! $use_standard_password_setup_email ) {
			$registration_fields['create_account_password'] = array(
				'type'         => 'password',
				'label'        => esc_html__( 'Password', 'wp-job-manager-resumes' ),
				'autocomplete' => false,
				'required'     => $account_required,
			);
			$password_hint                                  = wpjm_get_password_rules_hint();
			if ( $password_hint ) {
				$registration_fields['create_account_password']['description'] = $password_hint;
			}
			$registration_fields['create_account_password_verify'] = array(
				'type'         => 'password',
				'label'        => esc_html__( 'Verify Password', 'wp-job-manager-resumes' ),
				'autocomplete' => false,
				'required'     => $account_required,
			);
		}
	}

	/**
	 * Filters the fields used at registration.
	 *
	 * @since 1.18.0
	 *
	 * @param array $registration_fields
	 */
	return apply_filters( 'resume_manager_get_registration_fields', $registration_fields );
}

/**
 * True if an the user can post a resume. By default, you must be logged in.
 *
 * @return bool
 */
function resume_manager_user_can_post_resume() {
	$can_post = true;

	if ( ! is_user_logged_in() ) {
		if ( resume_manager_user_requires_account() && ! resume_manager_enable_registration() ) {
			$can_post = false;
		}
	}

	return apply_filters( 'resume_manager_user_can_post_resume', $can_post );
}

/**
 * True if registration is enabled.
 *
 * @return bool
 */
function resume_manager_enable_registration() {
	return apply_filters( 'resume_manager_enable_registration', get_option( 'resume_manager_enable_registration' ) == 1 ? true : false );
}

/**
 * True if an account is required to post.
 *
 * @return bool
 */
function resume_manager_user_requires_account() {
	return apply_filters( 'resume_manager_user_requires_account', get_option( 'resume_manager_user_requires_account' ) == 1 ? true : false );
}

/**
 * True if usernames are generated from email addresses.
 *
 * @return bool
 */
function resume_manager_generate_username_from_email() {
	return apply_filters( 'resume_manager_generate_username_from_email', get_option( 'resume_manager_generate_username_from_email' ) == 1 ? true : false );
}

/**
 * Output the class
 *
 * @param string $class (default: '')
 * @param mixed $post_id (default: null)
 * @return void
 */
function resume_class( $class = '', $post_id = null ) {
	echo 'class="' . join( ' ', get_resume_class( $class, $post_id ) ) . '"';
}

/**
 * Get the class
 *
 * @access public
 * @return array
 */
function get_resume_class( $class = '', $post_id = null ) {
	$post = get_post( $post_id );
	if ( $post->post_type !== 'resume' )
		return array();

	$classes = array();

	if ( empty( $post ) ) {
		return $classes;
	}

	$classes[] = 'resume';

	if ( is_resume_featured( $post ) ) {
		$classes[] = 'resume_featured';
	}

	return get_post_class( $classes, $post->ID );
}

/**
 * Output the resume permalinks
 *
 * @param WP_Post|int $post (default: null)
 */
function the_resume_permalink( $post = null ) {
	$post = get_post( $post );
	echo get_the_resume_permalink( $post );
}

/**
 * Output the resume links
 *
 * @param WP_Post|int $post (default: null)
 */
function the_resume_links( $post = null ) {
	$post = get_post( $post );
	get_job_manager_template( 'resume-links.php', array( 'post' => $post ), 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' );
}

/**
 * Get the resume permalinks
 *
 * @param WP_Post|int $post (default: null)
 * @return string
 */
function get_the_resume_permalink( $post = null ) {
	$post = get_post( $post );
	$link = get_permalink( $post );

	return apply_filters( 'the_resume_permalink', $link, $post );
}

/**
 * Returns true or false based on whether the resume has any website links to display.
 * @param  object $post
 * @return bool
 */
function resume_has_links( $post = null ) {
	return sizeof( get_resume_links( $post ) ) ? true : false;
}

/**
 * Returns true or false based on whether the resume has a file uploaded.
 * @param  object $post
 * @return bool
 */
function resume_has_file( $post = null ) {
	return get_resume_file() ? true : false;
}

/**
 * Returns an array of links defined for a resume
 * @param  object $post
 * @return array
 */
function get_resume_links( $post = null ) {
	$post = get_post( $post );

	return array_filter( (array) get_post_meta( $post->ID, '_links', true ) );
}

/**
 * If multiple files have been attached to the resume_file field, return the in array format.
 * @return array
 */
function get_resume_files( $post = null ) {
	$post  = get_post( $post );
	$files = get_post_meta( $post->ID, '_resume_file', true );
	if ( empty( $files ) ) {
		return array();
	}
	$files = is_array( $files ) ? $files : array( $files );
	return $files;
}

/**
 * Return resume attachment URLs and file paths
 * @return array
 */
function get_resume_attachments( $post = null ) {
	$post  = get_post( $post );
	$files = get_post_meta( $post->ID, '_resume_file', true );
	$files = is_array( $files ) ? $files : array( $files );

	foreach ( $files as $id => $file_path ) {
		if ( ! is_multisite() ) {

			/*
			 * Download file may be either http or https.
			 * site_url() depends on whether the page containing the download (ie; My Account) is served via SSL because WC
			 * modifies site_url() via a filter to force_ssl.
			 * So blindly doing a str_replace is incorrect because it will fail when schemes are mismatched. This code
			 * handles the various permutations.
			 */
			$scheme = parse_url( $file_path, PHP_URL_SCHEME );

			if ( $scheme ) {
				$content_url = set_url_scheme( WP_CONTENT_URL, $scheme );
			} else {
				$content_url = is_ssl() ? str_replace( 'https:', 'http:', WP_CONTENT_URL ) : WP_CONTENT_URL;
			}

			$file_path   = str_replace( $content_url, WP_CONTENT_DIR, $file_path );

		} else {

			$network_url = is_ssl() ? str_replace( 'https:', 'http:', network_admin_url() ) : network_admin_url();
			$upload_dir  = wp_upload_dir();

			// Try to replace network url
			$file_path   = str_replace( trailingslashit( $network_url ), ABSPATH, $file_path );

			// Now try to replace upload URL
			$file_path   = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $file_path );
		}
		$file_path = realpath( $file_path );
		if ( $file_path ) {
			$attachments[ $id ] = $file_path;
		} else {
			unset( $files[ $id ] );
		}
	}
	if ( isset( $attachments ) == false )
		$attachments = false;
	return array( "files" => $files, "attachments" => $attachments );
}

/**
 * Returns the resume file attached to a resume.
 * @param  object $post
 * @return string
 */
function get_resume_file( $post = null ) {
	$post = get_post( $post );
	$file = get_post_meta( $post->ID, '_resume_file', true );
	return is_array( $file ) ? current( $file ) : $file;
}

/**
 * Returns a download link for a resume file.
 * @param  object $post
 * @param  int    $key
 * @param  string $url (default false)
 *
 * @return string
 */
function get_resume_file_download_url( $post = null, $key = 0, $url = false ) {
	$post = get_post( $post );
	return add_query_arg( array( 'download-resume' => $post->ID, 'file-id' => $key ), $url );
}

/**
 * Return whether or not the resume has been featured
 *
 * @param  object $post
 * @return boolean
 */
function is_resume_featured( $post = null ) {
	$post = get_post( $post );

	return $post->_featured ? true : false;
}

/**
 * Output the candidate video
 */
function the_candidate_video( $post = null ) {
	$video    = get_the_candidate_video( $post );
	$video    = is_ssl() ? str_replace( 'http:', 'https:', $video ) : $video;
	$filetype = wp_check_filetype( $video );

	if ( ! empty( $filetype['ext'] ) ) {
		$video_embed = wp_video_shortcode( array( 'src' => $video ) );
	} else {
		$video_embed = ! empty( $video ) ? wp_oembed_get( $video ) : false;
	}

	$video_embed = apply_filters( 'the_candidate_video_embed', $video_embed, $post );

	if ( $video_embed ) {
		echo '<div class="candidate-video">' . $video_embed . '</div>';
	}
}

/**
 * Get the candidate video URL
 *
 * @param mixed $post (default: null)
 * @return string
 */
function get_the_candidate_video( $post = null ) {
	$post = get_post( $post );
	if ( $post->post_type !== 'resume' ) {
		return;
	}
	return apply_filters( 'the_candidate_video', $post->_candidate_video, $post );
}
