<?php
/**
 * WP_Resume_Manager_Post_Types class.
 */
class WP_Resume_Manager_Post_Types {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_types' ), 0 );
	}

	/**
	 * Sets up actions related to the resume post type.
	 */
	public function init_post_types() {
		add_action( 'wp', array( $this, 'download_resume_handler' ) );
		add_filter( 'admin_head', array( $this, 'admin_head' ) );
		add_filter( 'the_title', array( $this, 'resume_title' ), 10, 2 );
		add_filter( 'single_post_title', array( $this, 'resume_title' ), 10, 2 );
		add_filter( 'the_content', array( $this, 'resume_content' ) );
		if ( resume_manager_discourage_resume_search_indexing() ) {
			add_filter( 'wp_head', array( $this, 'add_no_robots' ) );
		}

		add_filter( 'the_resume_description', 'wptexturize'        );
		add_filter( 'the_resume_description', 'convert_smilies'    );
		add_filter( 'the_resume_description', 'convert_chars'      );
		add_filter( 'the_resume_description', 'wpautop'            );
		add_filter( 'the_resume_description', 'shortcode_unautop'  );
		add_filter( 'the_resume_description', 'prepend_attachment' );

		add_action( 'resume_manager_contact_details', array( $this, 'contact_details_email' ) );

		add_action( 'pending_to_publish', array( $this, 'setup_autohide_cron' ) );
		add_action( 'preview_to_publish', array( $this, 'setup_autohide_cron' ) );
		add_action( 'draft_to_publish', array( $this, 'setup_autohide_cron' ) );
		add_action( 'auto-draft_to_publish', array( $this, 'setup_autohide_cron' ) );
		add_action( 'hidden_to_publish', array( $this, 'setup_autohide_cron' ) );
		add_action( 'expired_to_publish', array( $this, 'setup_autohide_cron' ) );
		add_action( 'save_post', array( $this, 'setup_autohide_cron' ) );
		add_action( 'auto-hide-resume', array( $this, 'hide_resume' ) );

		add_action( 'update_post_meta', array( $this, 'maybe_update_menu_order' ), 10, 4 );
		add_filter( 'wp_insert_post_data', array( $this, 'fix_post_name' ), 10, 2 );
		add_action( 'pending_payment_to_publish', array( $this, 'set_expiry' ) );
		add_action( 'pending_to_publish', array( $this, 'set_expiry' ) );
		add_action( 'preview_to_publish', array( $this, 'set_expiry' ) );
		add_action( 'draft_to_publish', array( $this, 'set_expiry' ) );
		add_action( 'auto-draft_to_publish', array( $this, 'set_expiry' ) );
		add_action( 'expired_to_publish', array( $this, 'set_expiry' ) );
		add_action( 'resume_manager_check_for_expired_resumes', array( $this, 'check_for_expired_resumes' ) );

		add_action( 'save_post', array( $this, 'flush_get_resume_listings_cache' ) );
		add_action( 'resume_manager_my_resume_do_action', array( $this, 'resume_manager_my_resume_do_action' ) );
	}

	/**
	 * Flush the cache
	 */
	public function flush_get_resume_listings_cache( $post_id ) {
		if ( 'resume' === get_post_type( $post_id ) ) {
			WP_Job_Manager_Cache_Helper::get_transient_version( 'get_resume_listings', true );
		}
	}

	/**
	 * Flush the cache
	 */
	public function resume_manager_my_resume_do_action( $action ) {
		WP_Job_Manager_Cache_Helper::get_transient_version( 'get_resume_listings', true );
	}

	/**
	 * register_post_types function.
	 *
	 * @access public
	 * @return void
	 */
	public function register_post_types() {

		if ( post_type_exists( "resume" ) )
			return;

		$admin_capability = 'manage_resumes';

		/**
		 * Taxonomies
		 */
		if ( get_option( 'resume_manager_enable_categories' ) ) {
			$singular  = __( 'Resume Category', 'wp-job-manager-resumes' );
			$plural    = __( 'Resume Categories', 'wp-job-manager-resumes' );

			if ( current_theme_supports( 'resume-manager-templates' ) ) {
				$rewrite     = array(
					'slug'         => _x( 'resume-category', 'Resume category slug - resave permalinks after changing this', 'wp-job-manager-resumes' ),
					'with_front'   => false,
					'hierarchical' => false
				);
			} else {
				$rewrite = false;
			}

			register_taxonomy( "resume_category",
		        array( "resume" ),
		        array(
		            'hierarchical' 			=> true,
		            'update_count_callback' => '_update_post_term_count',
		            'label' 				=> $plural,
		            'labels' => array(
	                    'name' 				=> $plural,
	                    'singular_name' 	=> $singular,
	                    'search_items' 		=> sprintf( __( 'Search %s', 'wp-job-manager-resumes' ), $plural ),
	                    'all_items' 		=> sprintf( __( 'All %s', 'wp-job-manager-resumes' ), $plural ),
	                    'parent_item' 		=> sprintf( __( 'Parent %s', 'wp-job-manager-resumes' ), $singular ),
	                    'parent_item_colon' => sprintf( __( 'Parent %s:', 'wp-job-manager-resumes' ), $singular ),
	                    'edit_item' 		=> sprintf( __( 'Edit %s', 'wp-job-manager-resumes' ), $singular ),
	                    'update_item' 		=> sprintf( __( 'Update %s', 'wp-job-manager-resumes' ), $singular ),
	                    'add_new_item' 		=> sprintf( __( 'Add New %s', 'wp-job-manager-resumes' ), $singular ),
	                    'new_item_name' 	=> sprintf( __( 'New %s Name', 'wp-job-manager-resumes' ),  $singular )
	            	),
		            'show_ui' 				=> true,
		            'query_var' 			=> true,
		            'capabilities'			=> array(
		            	'manage_terms' 		=> $admin_capability,
		            	'edit_terms' 		=> $admin_capability,
		            	'delete_terms' 		=> $admin_capability,
		            	'assign_terms' 		=> $admin_capability,
		            ),
		            'rewrite' 				=> $rewrite,
		        )
		    );
		}

		if ( get_option( 'resume_manager_enable_skills' ) ) {
			$singular  = __( 'Candidate Skill', 'wp-job-manager-resumes' );
			$plural    = __( 'Candidate Skills', 'wp-job-manager-resumes' );

			if ( current_theme_supports( 'resume-manager-templates' ) ) {
				$rewrite     = array(
					'slug'         => _x( 'resume-skill', 'Resume skill slug - resave permalinks after changing this', 'wp-job-manager-resumes' ),
					'with_front'   => false,
					'hierarchical' => false
				);
			} else {
				$rewrite = false;
			}

			register_taxonomy( "resume_skill",
		        array( "resume" ),
		        array(
		            'hierarchical' 			=> false,
		            'update_count_callback' => '_update_post_term_count',
		            'label' 				=> $plural,
		            'labels' => array(
	                    'name' 				=> $plural,
	                    'singular_name' 	=> $singular,
	                    'search_items' 		=> sprintf( __( 'Search %s', 'wp-job-manager-resumes' ), $plural ),
	                    'all_items' 		=> sprintf( __( 'All %s', 'wp-job-manager-resumes' ), $plural ),
	                    'parent_item' 		=> sprintf( __( 'Parent %s', 'wp-job-manager-resumes' ), $singular ),
	                    'parent_item_colon' => sprintf( __( 'Parent %s:', 'wp-job-manager-resumes' ), $singular ),
	                    'edit_item' 		=> sprintf( __( 'Edit %s', 'wp-job-manager-resumes' ), $singular ),
	                    'update_item' 		=> sprintf( __( 'Update %s', 'wp-job-manager-resumes' ), $singular ),
	                    'add_new_item' 		=> sprintf( __( 'Add New %s', 'wp-job-manager-resumes' ), $singular ),
	                    'new_item_name' 	=> sprintf( __( 'New %s Name', 'wp-job-manager-resumes' ),  $singular )
	            	),
		            'show_ui' 				=> true,
		            'query_var' 			=> true,
		            'capabilities'			=> array(
		            	'manage_terms' 		=> $admin_capability,
		            	'edit_terms' 		=> $admin_capability,
		            	'delete_terms' 		=> $admin_capability,
		            	'assign_terms' 		=> $admin_capability,
		            ),
		            'rewrite' 				=> $rewrite,
		        )
		    );
		}

	    /**
		 * Post types
		 */
		$singular  = __( 'Resume', 'wp-job-manager-resumes' );
		$plural    = __( 'Resumes', 'wp-job-manager-resumes' );

		if ( current_theme_supports( 'resume-manager-templates' ) ) {
			$has_archive = _x( 'resumes', 'Post type archive slug - resave permalinks after changing this', 'wp-job-manager-resumes' );
		} else {
			$has_archive = false;
		}

		$rewrite     = array(
			'slug'       => _x( 'resume', 'Resume permalink - resave permalinks after changing this', 'wp-job-manager-resumes' ),
			'with_front' => false,
			'feeds'      => false,
			'pages'      => false
		);

		register_post_type( "resume",
			apply_filters( "register_post_type_resume", array(
				'labels' => array(
					'name' 					=> $plural,
					'singular_name' 		=> $singular,
					'menu_name'             => $plural,
					'all_items'             => sprintf( __( 'All %s', 'wp-job-manager-resumes' ), $plural ),
					'add_new' 				=> __( 'Add New', 'wp-job-manager-resumes' ),
					'add_new_item' 			=> sprintf( __( 'Add %s', 'wp-job-manager-resumes' ), $singular ),
					'edit' 					=> __( 'Edit', 'wp-job-manager-resumes' ),
					'edit_item' 			=> sprintf( __( 'Edit %s', 'wp-job-manager-resumes' ), $singular ),
					'new_item' 				=> sprintf( __( 'New %s', 'wp-job-manager-resumes' ), $singular ),
					'view' 					=> sprintf( __( 'View %s', 'wp-job-manager-resumes' ), $singular ),
					'view_item' 			=> sprintf( __( 'View %s', 'wp-job-manager-resumes' ), $singular ),
					'search_items' 			=> sprintf( __( 'Search %s', 'wp-job-manager-resumes' ), $plural ),
					'not_found' 			=> sprintf( __( 'No %s found', 'wp-job-manager-resumes' ), $plural ),
					'not_found_in_trash' 	=> sprintf( __( 'No %s found in trash', 'wp-job-manager-resumes' ), $plural ),
					'parent' 				=> sprintf( __( 'Parent %s', 'wp-job-manager-resumes' ), $singular )
				),
				'description' => __( 'This is where you can create and manage user resumes.', 'wp-job-manager-resumes' ),
				'public' 				=> true,
				// Hide the UI when the plugin is secretly disabled because WPJM core isn't activated.
				'show_ui' 				=> class_exists( 'WP_Job_Manager' ),
				'capability_type' 		=> 'post',
				'capabilities' => array(
					'publish_posts' 		=> $admin_capability,
					'edit_posts' 			=> $admin_capability,
					'edit_others_posts' 	=> $admin_capability,
					'delete_posts' 			=> $admin_capability,
					'delete_others_posts'	=> $admin_capability,
					'read_private_posts'	=> $admin_capability,
					'edit_post' 			=> $admin_capability,
					'delete_post' 			=> $admin_capability,
					'read_post' 			=> $admin_capability
				),
				'publicly_queryable' 	=> true,
				'exclude_from_search' 	=> true,
				'hierarchical' 			=> false,
				'rewrite' 				=> $rewrite,
				'query_var' 			=> true,
				'supports' 				=> array( 'title', 'editor', 'custom-fields' ),
				'has_archive' 			=> $has_archive,
				'show_in_nav_menus' 	=> false,
				'delete_with_user'		=> true,
			) )
		);

		register_post_status( 'hidden', array(
			'label'                     => _x( 'Hidden', 'post status', 'wp-job-manager-resumes' ),
			'public'                    => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Hidden <span class="count">(%s)</span>', 'Hidden <span class="count">(%s)</span>', 'wp-job-manager-resumes' ),
		) );
	}

	public function download_resume_handler() {
		global $post, $is_IE;

		if ( empty( $_GET['download-resume'] ) ) {
			return;
		}

		$resume_id = absint( $_GET['download-resume'] );

		if ( $resume_id && resume_manager_user_can_view_resume( $resume_id ) && apply_filters( 'resume_manager_user_can_download_resume_file', true, $resume_id ) ) {
			$files = get_resume_attachments( $resume_id );
			$file_id    = ! empty( $_GET['file-id'] ) ? absint( $_GET['file-id'] ) : 0;
			$file_path  = $files[ 'attachments' ][ $file_id ];

			$file_extension = strtolower( substr( strrchr( $file_path, "." ), 1 ) );
			$ctype          = "application/force-download";

			foreach ( get_allowed_mime_types() as $mime => $type ) {
				$mimes = explode( '|', $mime );
				if ( in_array( $file_extension, $mimes ) ) {
					$ctype = $type;
					break;
				}
			}

			// Start setting headers
			if ( ! ini_get('safe_mode') ) { // Option deprecated in PHP 5.3, removed in PHP 5.4.
				@set_time_limit(0);
			}

			if ( function_exists( 'apache_setenv' ) ) {
				@apache_setenv( 'no-gzip', 1 );
			}

			@session_write_close();
			@ini_set( 'zlib.output_compression', 'Off' );

			/**
			 * Prevents errors, for example: transfer closed with 3 bytes remaining to read
			 */
			@ob_end_clean(); // Clear the output buffer

			if ( ob_get_level() ) {

				$levels = ob_get_level();

				for ( $i = 0; $i < $levels; $i++ ) {
					@ob_end_clean(); // Zip corruption fix
				}

			}

			if ( $is_IE && is_ssl() ) {
				// IE bug prevents download via SSL when Cache Control and Pragma no-cache headers set.
				header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
				header( 'Cache-Control: private' );
			} else {
				nocache_headers();
			}

			$filename = basename( $file_path );

			if ( strstr( $filename, '?' ) ) {
				$filename = current( explode( '?', $filename ) );
			}

			header( "X-Robots-Tag: noindex, nofollow", true );
			header( "Content-Type: " . $ctype );
			header( "Content-Description: File Transfer" );
			header( "Content-Disposition: attachment; filename=\"" . $filename . "\";" );
			header( "Content-Transfer-Encoding: binary" );

	        if ( $size = @filesize( $file_path ) ) {
	        	header( "Content-Length: " . $size );
	        }

			$this->readfile_chunked( $file_path ) or wp_die( __( 'File not found', 'wp-job-manager-resumes' ) . ' <a href="' . esc_url( home_url() ) . '" class="wc-forward">' . __( 'Go to homepage', 'wp-job-manager-resumes' ) . '</a>' );

        	exit;
		}
	}

	/**
	 * readfile_chunked
	 * Reads file in chunks so big downloads are possible without changing PHP.INI - http://codeigniter.com/wiki/Download_helper_for_large_files/
	 * @param    string $file
	 * @param    bool   $retbytes return bytes of file
	 * @return bool|int
	 * @todo Meaning of the return value? Last return is status of fclose?
	 */
	public static function readfile_chunked( $file, $retbytes = true ) {

		$chunksize = 1 * ( 1024 * 1024 );
		$buffer = '';
		$cnt = 0;

		$handle = @fopen( $file, 'r' );
		if ( $handle === FALSE ) {
			return FALSE;
		}

		while ( ! feof( $handle ) ) {
			$buffer = fread( $handle, $chunksize );
			echo $buffer;
			@ob_flush();
			@flush();

			if ( $retbytes ) {
				$cnt += strlen( $buffer );
			}
		}

		$status = fclose( $handle );

		if ( $retbytes && $status ) {
			return $cnt;
		}

		return $status;
	}

	/**
	 * Change label
	 */
	public function admin_head() {
		global $menu;

		$plural        = __( 'Resumes', 'wp-job-manager-resumes' );
		$count_resumes = wp_count_posts( 'resume', 'readable' );

		foreach ( $menu as $key => $menu_item ) {
			if ( strpos( $menu_item[0], $plural ) === 0 ) {
				if ( $resume_count = $count_resumes->pending ) {
					$menu[ $key ][0] .= " <span class='awaiting-mod update-plugins count-$resume_count'><span class='pending-count'>" . number_format_i18n( $count_resumes->pending ) . "</span></span>" ;
				}
				break;
			}
		}
	}

	/**
	 * Adds robots `noindex` meta tag to discourage search indexing.
	 */
	public function add_no_robots() {
		if ( ! is_single() ) {
			return;
		}

		$post = get_post();
		if ( ! $post || 'resume' !== $post->post_type ) {
			return;
		}

		wp_no_robots();
	}

	/**
	 * Hide resume titles from users without access
	 * @param  string $title
	 * @param  int $post_or_id
	 * @return string
	 */
	public function resume_title( $title, $post_or_id = null ) {
		if ( $post_or_id && 'resume' === get_post_type( $post_or_id ) && ! resume_manager_user_can_view_resume_name( $post_or_id ) ) {
			$title_parts    = explode( ' ', $title );
			$hidden_title[] = array_shift( $title_parts );
			foreach ( $title_parts as $title_part ) {
				$hidden_title[] = str_repeat( '*', strlen( $title_part ) );
			}
			return apply_filters( 'resume_manager_hidden_resume_title', implode( ' ', $hidden_title ), $title, $post_or_id );
		}
		return $title;
	}

	/**
	 * Add extra content when showing resumes
	 */
	public function resume_content( $content ) {
		global $post;

		if ( ! is_singular( 'resume' ) || ! in_the_loop() ) {
			return $content;
		}

		remove_filter( 'the_content', array( $this, 'resume_content' ) );

		if ( $post->post_type == 'resume' ) {
			ob_start();

			get_job_manager_template_part( 'content-single', 'resume', 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' );

			$content = ob_get_clean();
		}

		add_filter( 'the_content', array( $this, 'resume_content' ) );

		return $content;
	}

	/**
	 * The application content when the application method is an email
	 */
	public function contact_details_email() {
		global $post;

		$email   = get_post_meta( $post->ID, '_candidate_email', true );
		$subject = sprintf( __( 'Contact via the resume for "%s" on %s', 'wp-job-manager-resumes' ), single_post_title( '', false ), home_url() );

		get_job_manager_template( 'contact-details-email.php', array( 'email' => $email, 'subject' => $subject ), 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' );
	}

	/**
	 * Setup event to hide a resume after X days
	 * @param  object $post
	 */
	public function setup_autohide_cron( $post ) {
		if ( ! is_object( $post ) ) {
			$post = get_post( $post );
		}
		if ( $post->post_type !== 'resume' ) {
			return;
		}

		add_post_meta( $post->ID, '_featured', 0, true );
		wp_clear_scheduled_hook( 'auto-hide-resume', array( $post->ID ) );

		$resume_manager_autohide = get_option( 'resume_manager_autohide' );

		if ( $resume_manager_autohide ) {
			wp_schedule_single_event( strtotime( "+{$resume_manager_autohide} day" ), 'auto-hide-resume', array( $post->ID ) );
		}
	}

	/**
	 * Hide a resume
	 * @param  int
	 */
	public function hide_resume( $resume_id ) {
		$resume = get_post( $resume_id );
		if ( $resume->post_status === 'publish' ) {
			$update_resume = array( 'ID' => $resume_id, 'post_status' => 'hidden' );
			wp_update_post( $update_resume );
			wp_clear_scheduled_hook( 'auto-hide-resume', array( $resume_id ) );
		}
	}

	/**
	 * Maybe set menu_order if the featured status of a resume is changed
	 */
	public function maybe_update_menu_order( $meta_id, $object_id, $meta_key, $_meta_value ) {
		if ( '_featured' !== $meta_key || 'resume' !== get_post_type( $object_id ) ) {
			return;
		}
		global $wpdb;

		if ( '1' == $_meta_value ) {
			$wpdb->update( $wpdb->posts, array( 'menu_order' => -1 ), array( 'ID' => $object_id ) );
		} else {
			$wpdb->update( $wpdb->posts, array( 'menu_order' => 0 ), array( 'ID' => $object_id, 'menu_order' => -1 ) );
		}

		clean_post_cache( $object_id );
	}

	/**
	 * Fix post name when wp_update_post changes it
	 * @param  array $data
	 * @return array
	 */
	public function fix_post_name( $data, $postarr ) {
		 if ( 'resume' === $data['post_type'] && 'pending' === $data['post_status'] && ! current_user_can( 'publish_posts' ) ) {
			$data['post_name'] = $postarr['post_name'];
		 }
		 return $data;
	}

	/**
	 * Typo -.-
	 */
	public function set_expirey( $post ) {
		$this->set_expiry( $post );
	}

	/**
	 * Set expirey date when resume status changes
	 */
	public function set_expiry( $post ) {
		if ( $post->post_type !== 'resume' ) {
			return;
		}

		// See if it is already set
		if ( metadata_exists( 'post', $post->ID, '_resume_expires' ) ) {
			$expires = get_post_meta( $post->ID, '_resume_expires', true );
			if ( $expires && strtotime( $expires ) < current_time( 'timestamp' ) ) {
				update_post_meta( $post->ID, '_resume_expires', '' );
				$_POST[ '_resume_expires' ] = '';
			}
			return;
		}

		// No metadata set so we can generate an expiry date
		// See if the user has set the expiry manually:
		if ( ! empty( $_POST[ '_resume_expires' ] ) ) {
			update_post_meta( $post->ID, '_resume_expires', date( 'Y-m-d', strtotime( sanitize_text_field( $_POST[ '_resume_expires' ] ) ) ) );

		// No manual setting? Lets generate a date
		} else {
			$expires = calculate_resume_expiry( $post->ID );
			update_post_meta( $post->ID, '_resume_expires', $expires );

			// In case we are saving a post, ensure post data is updated so the field is not overridden
			if ( isset( $_POST[ '_resume_expires' ] ) ) {
				$_POST[ '_resume_expires' ] = $expires;
			}
		}
	}

	/**
	 * Expire resumes
	 */
	public function check_for_expired_resumes() {
		global $wpdb;

		// Change status to expired
		$resume_ids = $wpdb->get_col( $wpdb->prepare( "
			SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta
			LEFT JOIN {$wpdb->posts} as posts ON postmeta.post_id = posts.ID
			WHERE postmeta.meta_key = '_resume_expires'
			AND postmeta.meta_value > 0
			AND postmeta.meta_value < %s
			AND posts.post_status = 'publish'
			AND posts.post_type = 'resume'
		", date( 'Y-m-d', current_time( 'timestamp' ) ) ) );

		if ( $resume_ids ) {
			foreach ( $resume_ids as $resume_id ) {
				$data                = array();
				$data['ID']          = $resume_id;
				$data['post_status'] = 'expired';
				wp_update_post( $data );
			}
		}

		// Delete old expired resumes
		if ( apply_filters( 'resume_manager_delete_expired_resumes', true ) ) {
			$resume_ids = $wpdb->get_col( $wpdb->prepare( "
				SELECT posts.ID FROM {$wpdb->posts} as posts
				WHERE posts.post_type = 'resume'
				AND posts.post_modified < %s
				AND posts.post_status = 'expired'
			", date( 'Y-m-d', strtotime( '-' . apply_filters( 'resume_manager_delete_expired_resumes_days', 30 ) . ' days', current_time( 'timestamp' ) ) ) ) );

			if ( $resume_ids ) {
				foreach ( $resume_ids as $resume_id ) {
					wp_trash_post( $resume_id );
				}
			}
		}
	}
}
