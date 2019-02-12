<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WP_Resume_Manager_Resume_Lifecycle class. Ensures lifecycle hooks are called
 * at the right time.
 */
class WP_Resume_Manager_Resume_Lifecycle {

	/**
	 * The single instance of the class.
	 *
	 * @var WP_Resume_Manager_Resume_Lifecycle
	 */
	protected static $_instance = null;

	/**
	 * Get singleton instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'initialize_lifecycle_hooks' ) );
	}

	/**
	 * Set up the lifecycle hooks for Resumes.
	 */
	public function initialize_lifecycle_hooks() {
		add_action( 'transition_post_status', array( $this, 'transition_post_status' ), 10, 3 );
	}

	/**
	 * Capture the resume post status transition to "publish" or "pending", and
	 * finalize the submission at that point.
	 *
	 * @param string  $new_status The new post status.
	 * @param string  $old_status The new post status.
	 * @param WP_Post $post       The Post object.
	 */
	public function transition_post_status( $new_status, $old_status, $post ) {
		if ( 'resume' === $post->post_type ) {
			// Finalize public submission when new status is publish or pending.
			if ( in_array( $new_status, array( 'publish', 'pending' ), true ) ) {
				$this->finalize_submission( $post );
			}
		}
	}

	/**
	 * Finalize the submission for a public submitted resume.
	 *
	 * @param string $resume the Resume.
	 */
	private function finalize_submission( $resume ) {
		// Only finalize if it is a public submission.
		if ( ! get_post_meta( $resume->ID, '_public_submission', true ) ) {
			return;
		}

		// Only finalize once!
		if ( get_post_meta( $resume->ID, '_submission_finalized', true ) ) {
			return;
		} else {
			update_post_meta( $resume->ID, '_submission_finalized', true );
		}

		/**
		 * Fire action after a resume is submitted.
		 *
		 * @since 1.0.0
		 *
		 * @param int $resume_id Resume ID.
		 */
		do_action( 'resume_manager_resume_submitted', $resume->ID );
		delete_post_meta( $resume->id, '_submitting_key' );
	}
}

WP_Resume_Manager_Resume_Lifecycle::instance();
