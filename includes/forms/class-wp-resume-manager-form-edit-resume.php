<?php

include_once( 'class-wp-resume-manager-form-submit-resume.php' );

/**
 * WP_Resume_Manager_Form_Edit_Resume class.
 */
class WP_Resume_Manager_Form_Edit_Resume extends WP_Resume_Manager_Form_Submit_Resume {

	public $form_name = 'edit-resume';

	/** @var WP_Resume_Manager_Form_Edit_Resume The single instance of the class */
	protected static $_instance = null;

	/**
	 * Main Instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->resume_id = ! empty( $_REQUEST['resume_id'] ) ? absint( $_REQUEST[ 'resume_id' ] ) : 0;

		if  ( ! resume_manager_user_can_edit_resume( $this->resume_id ) ) {
			$this->resume_id = 0;
		}
	}

	/**
	 * output function.
	 */
	public function output( $atts = array() ) {
		$this->submit_handler();
		$this->submit();
	}

	/**
	 * Submit Step
	 */
	public function submit() {
		global $post;

		$resume = get_post( $this->resume_id );

		if ( empty( $this->resume_id  ) || ( $resume->post_status !== 'publish' && $resume->post_status !== 'hidden' ) ) {
			echo wpautop( __( 'Invalid resume', 'wp-job-manager-resumes' ) );
			return;
		}

		$this->init_fields();

		foreach ( $this->fields as $group_key => $group_fields ) {
			foreach ( $group_fields as $key => $field ) {
				if ( ! isset( $this->fields[ $group_key ][ $key ]['value'] ) ) {
					if ( 'candidate_name' === $key ) {
						$this->fields[ $group_key ][ $key ]['value'] = $resume->post_title;

					} elseif ( 'resume_content' === $key ) {
						$this->fields[ $group_key ][ $key ]['value'] = $resume->post_content;

					} elseif ( ! empty( $field['taxonomy'] ) ) {
						$this->fields[ $group_key ][ $key ]['value'] = wp_get_object_terms( $resume->ID, $field['taxonomy'], array( 'fields' => 'ids' ) );

					} elseif ( 'resume_skills' === $key ) {
						$this->fields[ $group_key ][ $key ]['value'] = implode( ', ', wp_get_object_terms( $resume->ID, 'resume_skill', array( 'fields' => 'names' ) ) );

					} else {
						$this->fields[ $group_key ][ $key ]['value'] = get_post_meta( $resume->ID, '_' . $key, true );
					}
				}
			}
		}

		$this->fields = apply_filters( 'submit_resume_form_fields_get_resume_data', $this->fields, $resume );

		get_job_manager_template( 'resume-submit.php', array(
			'class'              => $this,
			'form'               => $this->form_name,
			'job_id'             => '',
			'resume_id'          => $this->get_resume_id(),
			'action'             => $this->get_action(),
			'resume_fields'      => $this->get_fields( 'resume_fields' ),
			'step'               => $this->get_step(),
			'submit_button_text' => __( 'Save changes', 'wp-job-manager-resumes' )
		), 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' );
	}

	/**
	 * Submit Step is posted
	 */
	public function submit_handler() {
		if ( empty( $_POST['submit_resume'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'submit_form_posted' ) )
			return;

		try {

			// Init fields
			$this->init_fields();

			// Get posted values
			$values = $this->get_posted_fields();

			// Validate required
			if ( is_wp_error( ( $return = $this->validate_fields( $values ) ) ) )
				throw new Exception( $return->get_error_message() );

			// Update the resume
			$this->save_resume( $values['resume_fields']['candidate_name'], $values['resume_fields']['resume_content'], 'publish', $values );
			$this->update_resume_data( $values );

			// Successful
			echo '<div class="job-manager-message">' . __( 'Your changes have been saved.', 'wp-job-manager-resumes' ), ' <a href="' . get_permalink( $this->resume_id ) . '">' . __( 'View Resume &rarr;', 'wp-job-manager-resumes' ) . '</a>' . '</div>';

		} catch ( Exception $e ) {
			echo '<div class="job-manager-error">' . $e->getMessage() . '</div>';
			return;
		}
	}
}
