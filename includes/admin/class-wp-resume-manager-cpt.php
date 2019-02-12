<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WP_Resume_Manager_CPT class.
 */
class WP_Resume_Manager_CPT {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_filter( 'enter_title_here', array( $this, 'enter_title_here' ), 1, 2 );
		add_filter( 'manage_edit-resume_columns', array( $this, 'columns' ) );
		add_action( 'manage_resume_posts_custom_column', array( $this, 'custom_columns' ), 2 );
		add_filter( 'manage_edit-resume_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'parse_query', array( $this, 'search_meta' ) );
		add_filter( 'get_search_query', array( $this, 'search_meta_label' ) );
		add_filter( 'request', array( $this, 'sort_columns' ) );
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_action( 'admin_footer-edit.php', array( $this, 'add_bulk_actions' ) );
		add_action( 'load-edit.php', array( $this, 'do_bulk_actions' ) );
		add_action( 'admin_init', array( $this, 'approve_resume' ) );
		add_action( 'admin_notices', array( $this, 'approved_notice' ) );

		if ( get_option( 'resume_manager_enable_categories' ) ) {
			add_action( "restrict_manage_posts", array( $this, "resumes_by_category" ) );
		}

		foreach ( array( 'post', 'post-new' ) as $hook ) {
			add_action( "admin_footer-{$hook}.php", array( $this,'extend_submitdiv_post_status' ) );
		}
	}

	/**
	 * Edit bulk actions
	 */
	public function add_bulk_actions() {
		global $post_type;

		if ( $post_type == 'resume' ) {
			?>
			<script type="text/javascript">
		      jQuery(document).ready(function() {
		        jQuery('<option>').val('approve_resumes').text('<?php _e( 'Approve Resumes', 'wp-job-manager-resumes' )?>').appendTo("select[name='action']");
		        jQuery('<option>').val('approve_resumes').text('<?php _e( 'Approve Resumes', 'wp-job-manager-resumes' )?>').appendTo("select[name='action2']");
		      });
		    </script>
		    <?php
		}
	}

	/**
	 * Do custom bulk actions
	 */
	public function do_bulk_actions() {
		$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
		$action        = $wp_list_table->current_action();

		switch( $action ) {
			case 'approve_resumes' :
				check_admin_referer( 'bulk-posts' );

				$post_ids      = array_map( 'absint', array_filter( (array) $_GET['post'] ) );
				$approved_resumes = array();

				if ( ! empty( $post_ids ) )
					foreach( $post_ids as $post_id ) {
						$resume_data = array(
							'ID'          => $post_id,
							'post_status' => 'publish'
						);
						if ( get_post_status( $post_id ) == 'pending' && wp_update_post( $resume_data ) )
							$approved_resumes[] = $post_id;
					}

				wp_redirect( remove_query_arg( 'approve_resumes', add_query_arg( 'approved_resumes', $approved_resumes, admin_url( 'edit.php?post_type=resume' ) ) ) );
				exit;
			break;
		}

		return;
	}

	/**
	 * Approve a single resume
	 */
	public function approve_resume() {
		if ( ! empty( $_GET['approve_resume'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'approve_resume' ) && current_user_can( 'edit_post', $_GET['approve_resume'] ) ) {
			$post_id = absint( $_GET['approve_resume'] );
			$resume_data = array(
				'ID'          => $post_id,
				'post_status' => 'publish'
			);
			wp_update_post( $resume_data );
			wp_redirect( remove_query_arg( 'approve_resume', add_query_arg( 'approved_resumes', $post_id, admin_url( 'edit.php?post_type=resume' ) ) ) );
			exit;
		}
	}

	/**
	 * Show a notice if we did a bulk action or approval
	 */
	public function approved_notice() {
		 global $post_type, $pagenow;

		if ( $pagenow == 'edit.php' && $post_type == 'resume' && ! empty( $_REQUEST['approved_resumes'] ) ) {
			$approved_resumes = $_REQUEST['approved_resumes'];
			if ( is_array( $approved_resumes ) ) {
				$approved_resumes = array_map( 'absint', $approved_resumes );
				$titles           = array();
				foreach ( $approved_resumes as $resume_id )
					$titles[] = get_the_title( $resume_id );
				echo '<div class="updated"><p>' . sprintf( __( '%s approved', 'wp-job-manager-resumes' ), '&quot;' . implode( '&quot;, &quot;', $titles ) . '&quot;' ) . '</p></div>';
			} else {
				echo '<div class="updated"><p>' . sprintf( __( '%s approved', 'wp-job-manager-resumes' ), '&quot;' . get_the_title( $approved_resumes ) . '&quot;' ) . '</p></div>';
			}
		}
	}

	/**
	 * resumes_by_category function.
	 *
	 * @access public
	 * @param int $show_counts (default: 1)
	 * @param int $hierarchical (default: 1)
	 * @param int $show_uncategorized (default: 1)
	 * @param string $orderby (default: '')
	 * @return void
	 */
	public function resumes_by_category( $show_counts = 1, $hierarchical = 1, $show_uncategorized = 1, $orderby = '' ) {
		global $typenow, $wp_query;

	    if ( $typenow != 'resume' || ! taxonomy_exists( 'resume_category' ) ) {
	    	return;
	    }

	    if ( file_exists( JOB_MANAGER_PLUGIN_DIR . '/includes/admin/class-wp-job-manager-category-walker.php' ) ) {
			include_once( JOB_MANAGER_PLUGIN_DIR . '/includes/admin/class-wp-job-manager-category-walker.php' );
		} else {
			include_once( JOB_MANAGER_PLUGIN_DIR . '/includes/class-wp-job-manager-category-walker.php' );
		}

		$r = array();
		$r['pad_counts']   = 1;
		$r['hierarchical'] = $hierarchical;
		$r['hide_empty']   = 0;
		$r['show_count']   = $show_counts;
		$r['selected']     = isset( $wp_query->query['resume_category'] ) ? $wp_query->query['resume_category'] : '';
		$r['menu_order']   = false;

		if ( $orderby == 'order' ) {
			$r['menu_order'] = 'asc';
		} elseif ( $orderby ) {
			$r['orderby'] = $orderby;
		}

		$terms = get_terms( 'resume_category', $r );

		if ( ! $terms )
			return;

		$output  = "<select name='resume_category' id='dropdown_resume_category'>";
		$output .= '<option value="" ' .  selected( isset( $_GET['resume_category'] ) ? $_GET['resume_category'] : '', '', false ) . '>'.__( 'Select a category', 'wp-job-manager-resumes' ).'</option>';
		$output .= $this->walk_category_dropdown_tree( $terms, 0, $r );
		$output .="</select>";

		echo $output;
	}

	/**
	 * Walk the Product Categories.
	 *
	 * @access public
	 * @return void
	 */
	private function walk_category_dropdown_tree() {
		$args = func_get_args();

		// the user's options are the third parameter
		if ( empty($args[2]['walker']) || !is_a($args[2]['walker'], 'Walker') ) {
			$walker = new WP_Job_Manager_Category_Walker;
		} else {
			$walker = $args[2]['walker'];
		}

		return call_user_func_array( array( $walker, 'walk' ), $args );
	}

	/**
	 * enter_title_here function.
	 * @return string
	 */
	public function enter_title_here( $text, $post ) {
		if ( $post->post_type == 'resume' ) {
			return __( 'Candidate name', 'wp-job-manager-resumes' );
		}
		return $text;
	}

	/**
	 * post_updated_messages function.
	 * @param array $messages
	 * @return array
	 */
	public function post_updated_messages( $messages ) {
		global $post, $post_ID;

		$messages['resume'] = array(
			0 => '',
			1 => sprintf( __( 'Resume updated. <a href="%s">View Resume</a>', 'wp-job-manager-resumes' ), esc_url( get_permalink( $post_ID ) ) ),
			2 => __( 'Custom field updated.', 'wp-job-manager-resumes' ),
			3 => __( 'Custom field deleted.', 'wp-job-manager-resumes' ),
			4 => __( 'Resume updated.', 'wp-job-manager-resumes' ),
			5 => isset( $_GET['revision'] ) ? sprintf( __( 'Resume restored to revision from %s', 'wp-job-manager-resumes' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __( 'Resume published. <a href="%s">View Resume</a>', 'wp-job-manager-resumes' ), esc_url( get_permalink( $post_ID ) ) ),
			7 => __('Resume saved.', 'wp-job-manager-resumes'),
			8 => sprintf( __( 'Resume submitted. <a target="_blank" href="%s">Preview Resume</a>', 'wp-job-manager-resumes' ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			9 => sprintf( __( 'Resume scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Resume</a>', 'wp-job-manager-resumes' ),
			  date_i18n( __( 'M j, Y @ G:i', 'wp-job-manager-resumes' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
			10 => sprintf( __( 'Resume draft updated. <a target="_blank" href="%s">Preview Resume</a>', 'wp-job-manager-resumes' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		);
		return $messages;
	}

	/**
	 * columns function.
	 *
	 * @access public
	 * @param mixed $columns
	 * @return void
	 */
	public function columns( $columns ) {
		if ( ! is_array( $columns ) ) {
			$columns = array();
		}

		unset( $columns['title'], $columns['date'] );

		$columns["candidate"]          = __( "Candidate", 'wp-job-manager-resumes' );
		$columns["candidate_location"] = __( "Location", 'wp-job-manager-resumes' );
		$columns['resume_status']   = '<span class="tips" data-tip="' . __( "Status", 'wp-job-manager-resumes' ) . '">' . __( "Status", 'wp-job-manager-resumes' ) . '</span>';
		$columns["resume_posted"]   = __( "Posted", 'wp-job-manager-resumes' );
		$columns["resume_expires"]  = __( "Expires", 'wp-job-manager-resumes' );

		if ( get_option( 'resume_manager_enable_skills' ) ) {
			$columns["resume_skills"] = __( "Skills", 'wp-job-manager-resumes' );
		}

		if ( get_option( 'resume_manager_enable_categories' ) ) {
			$columns["resume_category"] = __( "Categories", 'wp-job-manager-resumes' );
		}

		$columns['featured_resume'] = '<span class="tips" data-tip="' . __( "Featured?", 'wp-job-manager-resumes' ) . '">' . __( "Featured?", 'wp-job-manager-resumes' ) . '</span>';
		$columns['resume_actions']  = __( "Actions", 'wp-job-manager-resumes' );

		return $columns;
	}

	/**
	 * sortable_columns function.
	 * @param array $columns
	 * @return array
	 */
	public function sortable_columns( $columns ) {
		$custom = array(
			'resume_posted'      => 'date',
			'candidate'          => 'title',
			'candidate_location' => 'candidate_location',
			'resume_expires'     => 'resume_expires'
		);
		return wp_parse_args( $custom, $columns );
	}

	/**
	 * Search custom fields as well as content.
	 * @param WP_Query $wp
	 */
	public function search_meta( $wp ) {
		global $pagenow, $wpdb;

		if ( 'edit.php' != $pagenow || empty( $wp->query_vars['s'] ) || $wp->query_vars['post_type'] != 'resume' ) {
			return;
		}

		$post_ids = array_unique( array_merge(
			$wpdb->get_col(
				$wpdb->prepare( "
					SELECT posts.ID
					FROM {$wpdb->posts} posts
					INNER JOIN {$wpdb->postmeta} p1 ON posts.ID = p1.post_id
					WHERE p1.meta_value LIKE '%%%s%%'
					OR posts.post_title LIKE '%%%s%%'
					OR posts.post_content LIKE '%%%s%%'
					AND posts.post_type = 'resume'
					",
					esc_attr( $wp->query_vars['s'] ),
					esc_attr( $wp->query_vars['s'] ),
					esc_attr( $wp->query_vars['s'] )
				)
			),
			array( 0 )
		) );

		// Adjust the query vars
		unset( $wp->query_vars['s'] );
		$wp->query_vars['resume_search'] = true;
		$wp->query_vars['post__in'] = $post_ids;
	}

	/**
	 * Change the label when searching meta.
	 * @param string $query
	 * @return string
	 */
	public function search_meta_label( $query ) {
		global $pagenow, $typenow;

		if ( 'edit.php' != $pagenow || $typenow != 'resume' || ! get_query_var( 'resume_search' ) ) {
			return $query;
		}

		return wp_unslash( sanitize_text_field( $_GET['s'] ) );
	}

	/**
	 * sort_columns function.
	 * @param array $vars
	 * @return array
	 */
	public function sort_columns( $vars ) {
		if ( isset( $vars['orderby'] ) ) {
			if ( 'resume_expires' === $vars['orderby'] ) {
				$vars = array_merge( $vars, array(
					'meta_key' 	=> '_resume_expires',
					'orderby' 	=> 'meta_value'
				) );
			} elseif ( 'candidate_location' === $vars['orderby'] ) {
				$vars = array_merge( $vars, array(
					'meta_key' 	=> '_candidate_location',
					'orderby' 	=> 'meta_value'
				) );
			}
		}
		return $vars;
	}

	/**
	 * custom_columns function.
	 * @param string $column
	 */
	public function custom_columns( $column ) {
		global $post;

		switch ( $column ) {
			case "candidate" :
				echo '<a href="' . admin_url('post.php?post=' . $post->ID . '&action=edit') . '" class="tips candidate_name" data-tip="' . sprintf( __( 'Resume ID: %d', 'wp-job-manager-resumes' ), $post->ID ) . '">' . $post->post_title . '</a>';
				echo '<div class="candidate_title">';
				the_candidate_title();
				echo '</div>';
				the_candidate_photo();
			break;
			case 'candidate_location' :
				the_candidate_location( true, $post );
			break;
			case "resume_skills" :
				if ( ! $terms = get_the_term_list( $post->ID, 'resume_skill', '', ', ', '' ) ) echo '<span class="na">&ndash;</span>'; else echo $terms;
			break;
			case "resume_category" :
				if ( ! $terms = get_the_term_list( $post->ID, $column, '', ', ', '' ) ) echo '<span class="na">&ndash;</span>'; else echo $terms;
			break;
			case "resume_posted" :
				echo '<strong>' . date_i18n( __( 'M j, Y', 'wp-job-manager-resumes' ), strtotime( $post->post_date ) ) . '</strong><span>';
				echo ( empty( $post->post_author ) ? __( 'by a guest', 'wp-job-manager-resumes' ) : sprintf( __( 'by %s', 'wp-job-manager-resumes' ), '<a href="' . get_edit_user_link( $post->post_author ) . '">' . get_the_author() . '</a>' ) ) . '</span>';
			break;
			case "resume_expires" :
				if ( $post->_resume_expires ) {
					echo '<strong>' . date_i18n( __( 'M j, Y', 'wp-job-manager-resumes' ), strtotime( $post->_resume_expires ) ) . '</strong>';
				} else {
					echo '&ndash;';
				}
			break;
			case "featured_resume" :
				if ( is_resume_featured( $post ) ) echo '&#10004;'; else echo '&ndash;';
			break;
			case "resume_status" :
				echo '<span data-tip="' . esc_attr( get_the_resume_status( $post ) ) . '" class="tips status-' . esc_attr( $post->post_status ) . '">' . get_the_resume_status( $post ) . '</span>';
			break;
			case "resume_actions" :
				echo '<div class="actions">';
				$admin_actions           = array();

				if ( $post->post_status == 'pending' ) {
					$admin_actions['approve']   = array(
						'action'  => 'approve',
						'name'    => __( 'Approve', 'wp-job-manager-resumes' ),
						'url'     =>  wp_nonce_url( add_query_arg( 'approve_resume', $post->ID ), 'approve_resume' )
					);
				}

				if ( $post->post_status !== 'trash' ) {
					$admin_actions['view']   = array(
						'action'  => 'view',
						'name'    => __( 'View', 'wp-job-manager-resumes' ),
						'url'     => get_permalink( $post->ID )
					);
					if ( $email = get_post_meta( $post->ID, '_candidate_email', true ) ) {
						$admin_actions['email']   = array(
							'action'  => 'email',
							'name'    => __( 'Email Candidate', 'wp-job-manager-resumes' ),
							'url'     =>  'mailto:' . esc_attr( $email )
						);
					}
					$admin_actions['edit']   = array(
						'action'  => 'edit',
						'name'    => __( 'Edit', 'wp-job-manager-resumes' ),
						'url'     => get_edit_post_link( $post->ID )
					);
					$admin_actions['delete'] = array(
						'action'  => 'delete',
						'name'    => __( 'Delete', 'wp-job-manager-resumes' ),
						'url'     => get_delete_post_link( $post->ID )
					);
				}

				$admin_actions = apply_filters( 'resume_manager_admin_actions', $admin_actions, $post );

				foreach ( $admin_actions as $action ) {
					printf( '<a class="icon-%s button tips" href="%s" data-tip="%s">%s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
				}

				echo '</div>';

			break;
		}
	}

    /**
	 * Adds post status to the "submitdiv" Meta Box and post type WP List Table screens. Based on https://gist.github.com/franz-josef-kaiser/2930190
	 *
	 * @return void
	 */
	public function extend_submitdiv_post_status() {
		global $wp_post_statuses, $post, $post_type;

		// Abort if we're on the wrong post type, but only if we got a restriction
		if ( 'resume' !== $post_type ) {
			return;
		}

		// Get all non-builtin post status and add them as <option>
		$options = $display = '';
		foreach ( get_resume_post_statuses() as $status => $name ) {
			$selected = selected( $post->post_status, $status, false );

			// If we one of our custom post status is selected, remember it
			$selected AND $display = $name;

			// Build the options
			$options .= "<option{$selected} value='{$status}'>{$name}</option>";
		}
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function($) {
				<?php if ( ! empty( $display ) ) : ?>
					jQuery( '#post-status-display' ).html( '<?php echo $display; ?>' );
				<?php endif; ?>

				var select = jQuery( '#post-status-select' ).find( 'select' );
				jQuery( select ).html( "<?php echo $options; ?>" );
			} );
		</script>
		<?php
	}
}

new WP_Resume_Manager_CPT();