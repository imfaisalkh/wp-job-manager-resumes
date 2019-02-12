<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WP_Resume_Manager_Form_Submit_Resume class.
 */
class WP_Resume_Manager_Form_Submit_Resume extends WP_Job_Manager_Form {

	public    $form_name = 'submit-resume';
	protected $resume_id;
	protected $job_id;
	protected $preview_resume;

	/** @var WP_Resume_Manager_Form_Submit_Resume The single instance of the class */
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
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp', array( $this, 'process' ) );

		if ( $this->use_recaptcha_field() ) {
			add_action( 'submit_resume_form_resume_fields_end', array( $this, 'display_recaptcha_field' ) );
			add_action( 'submit_resume_form_validate_fields', array( $this, 'validate_recaptcha_field' ) );
		}

		$this->steps  = (array) apply_filters( 'submit_resume_steps', array(
			'submit' => array(
				'name'     => __( 'Submit Details', 'wp-job-manager-resumes' ),
				'view'     => array( $this, 'submit' ),
				'handler'  => array( $this, 'submit_handler' ),
				'priority' => 10
				),
			'preview' => array(
				'name'     => __( 'Preview', 'wp-job-manager-resumes' ),
				'view'     => array( $this, 'preview' ),
				'handler'  => array( $this, 'preview_handler' ),
				'priority' => 20
			),
			'done' => array(
				'name'     => __( 'Done', 'wp-job-manager-resumes' ),
				'view'     => array( $this, 'done' ),
				'handler'  => '',
				'priority' => 30
			)
		) );

		uasort( $this->steps, array( $this, 'sort_by_priority' ) );

		// Get step/resume
		if ( ! empty( $_REQUEST['step'] ) ) {
			$this->step = is_numeric( $_REQUEST['step'] ) ? max( absint( $_REQUEST['step'] ), 0 ) : array_search( $_REQUEST['step'], array_keys( $this->steps ) );
		}

		$this->job_id = ! empty( $_REQUEST[ 'job_id' ] ) ? absint( $_REQUEST[ 'job_id' ] ) : 0;
		$this->resume_id = ! empty( $_REQUEST['resume_id'] ) ? absint( $_REQUEST[ 'resume_id' ] ) : 0;

		if ( ! resume_manager_user_can_edit_resume( $this->resume_id ) ) {
			$this->resume_id = 0;
		}

		// Load resume details
		if ( $this->resume_id ) {
			$resume_status = get_post_status( $this->resume_id );
			if ( 'expired' === $resume_status ) {
				if ( ! resume_manager_user_can_edit_resume( $this->resume_id ) ) {
					$this->resume_id = 0;
					$this->job_id    = 0;
					$this->step      = 0;
				}
			} elseif ( 0 === $this->step && ! in_array( $resume_status, apply_filters( 'resume_manager_valid_submit_resume_statuses', array( 'preview' ) ) ) && empty( $_POST['resume_application_submit_button'] ) ) {
				$this->resume_id = 0;
				$this->job_id    = 0;
				$this->step      = 0;
			}
		}
	}

	/**
	 * Get the submitted resume ID
	 * @return int
	 */
	public function get_resume_id() {
		return absint( $this->resume_id );
	}

	/**
	 * Get the job ID if applying
	 * @return int
	 */
	public function get_job_id() {
		return absint( $this->job_id );
	}

	/**
	 * Get a field from either resume manager or job manager
	 */
	public function get_field_template( $key, $field ) {
		switch ( $field['type'] ) {
			case 'repeated' :
			case 'education' :
			case 'experience' :
			case 'links' :
				get_job_manager_template( 'form-fields/repeated-field.php', array( 'key' => $key, 'field' => $field, 'class' => $this ), 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' );
			break;
			default :
				get_job_manager_template( 'form-fields/' . $field['type'] . '-field.php', array( 'key' => $key, 'field' => $field, 'class' => $this ) );
			break;
		}
	}

	/**
	 * init_fields function.
	 */
	public function init_fields() {
		if ( $this->fields ) {
			return;
		}
		if ( $max = get_option( 'resume_manager_max_skills' ) ) {
			$max = ' ' . sprintf( __( 'Maximum of %d.', 'wp-job-manager-resumes' ), $max );
		}

		$this->fields = apply_filters( 'submit_resume_form_fields', array(
			'resume_fields' => array(
				'candidate_name'       => array(
					'label'         => __( 'Your name', 'wp-job-manager-resumes' ),
					'type'          => 'text',
					'required'      => true,
					'placeholder'   => __( 'Your full name', 'wp-job-manager-resumes' ),
					'priority'      => 1,
					'personal_data' => true,
				),
				'candidate_email'      => array(
					'label'         => __( 'Your email', 'wp-job-manager-resumes' ),
					'type'          => 'text',
					'required'      => true,
					'placeholder'   => __( 'you@yourdomain.com', 'wp-job-manager-resumes' ),
					'priority'      => 2,
					'personal_data' => true,
				),
				'candidate_title'      => array(
					'label'         => __( 'Professional title', 'wp-job-manager-resumes' ),
					'type'          => 'text',
					'required'      => true,
					'placeholder'   => __( 'e.g. "Web Developer"', 'wp-job-manager-resumes' ),
					'priority'      => 3,
					'personal_data' => true,
				),
				'candidate_location'   => array(
					'label'         => __( 'Location', 'wp-job-manager-resumes' ),
					'type'          => 'text',
					'required'      => true,
					'placeholder'   => __( 'e.g. "London, UK", "New York", "Houston, TX"', 'wp-job-manager-resumes' ),
					'priority'      => 4,
					'personal_data' => true,
				),
				'candidate_photo'      => array(
					'label'              => __( 'Photo', 'wp-job-manager-resumes' ),
					'type'               => 'file',
					'required'           => false,
					'placeholder'        => '',
					'priority'           => 5,
					'ajax'               => true,
					'allowed_mime_types' => array(
						'jpg'  => 'image/jpeg',
						'jpeg' => 'image/jpeg',
						'gif'  => 'image/gif',
						'png'  => 'image/png',
					),
					'personal_data'      => true,
				),
				'candidate_video'      => array(
					'label'         => __( 'Video', 'wp-job-manager-resumes' ),
					'type'          => 'text',
					'required'      => false,
					'priority'      => 6,
					'placeholder'   => __( 'A link to a video about yourself', 'wp-job-manager-resumes' ),
					'personal_data' => true,
				),
				'resume_category'      => array(
					'label'         => __( 'Resume category', 'wp-job-manager-resumes' ),
					'type'          => 'term-multiselect',
					'taxonomy'      => 'resume_category',
					'required'      => true,
					'placeholder'   => '',
					'priority'      => 7,
					'personal_data' => true,
				),
				'resume_content'       => array(
					'label'         => __( 'Resume Content', 'wp-job-manager-resumes' ),
					'type'          => 'wp-editor',
					'required'      => true,
					'placeholder'   => '',
					'priority'      => 8,
					'personal_data' => true,
				),
				'resume_skills'        => array(
					'label'         => __( 'Skills', 'wp-job-manager-resumes' ),
					'type'          => 'text',
					'required'      => false,
					'placeholder'   => __( 'Comma separate a list of relevant skills', 'wp-job-manager-resumes' ) . $max,
					'priority'      => 9,
					'personal_data' => true,
				),
				'links'                => array(
					'label'         => __( 'URL(s)', 'wp-job-manager-resumes' ),
					'add_row'       => __( 'Add URL', 'wp-job-manager-resumes' ),
					'type'          => 'links', // repeated
					'required'      => false,
					'placeholder'   => '',
					'description'   => __( 'Optionally provide links to any of your websites or social network profiles.', 'wp-job-manager-resumes' ),
					'priority'      => 10,
					'fields'        => array(
						'name' => array(
							'label'       => __( 'Name', 'wp-job-manager-resumes' ),
							'type'        => 'text',
							'required'    => true,
							'placeholder' => '',
							'priority'    => 1,
						),
						'url'  => array(
							'label'       => __( 'URL', 'wp-job-manager-resumes' ),
							'type'        => 'text',
							'required'    => true,
							'placeholder' => '',
							'priority'    => 2,
						),
					),
					'personal_data' => true,
				),
				'candidate_education'  => array(
					'label'         => __( 'Education', 'wp-job-manager-resumes' ),
					'add_row'       => __( 'Add Education', 'wp-job-manager-resumes' ),
					'type'          => 'education', // repeated
					'required'      => false,
					'placeholder'   => '',
					'priority'      => 11,
					'fields'        => array(
						'location'      => array(
							'label'       => __( 'School name', 'wp-job-manager-resumes' ),
							'type'        => 'text',
							'required'    => true,
							'placeholder' => '',
						),
						'qualification' => array(
							'label'       => __( 'Qualification(s)', 'wp-job-manager-resumes' ),
							'type'        => 'text',
							'required'    => true,
							'placeholder' => '',
						),
						'date'          => array(
							'label'       => __( 'Start/end date', 'wp-job-manager-resumes' ),
							'type'        => 'text',
							'required'    => true,
							'placeholder' => '',
						),
						'notes'         => array(
							'label'       => __( 'Notes', 'wp-job-manager-resumes' ),
							'type'        => 'textarea',
							'required'    => false,
							'placeholder' => '',
						),
					),
					'personal_data' => true,
				),
				'candidate_experience' => array(
					'label'         => __( 'Experience', 'wp-job-manager-resumes' ),
					'add_row'       => __( 'Add Experience', 'wp-job-manager-resumes' ),
					'type'          => 'experience', // repeated
					'required'      => false,
					'placeholder'   => '',
					'priority'      => 12,
					'fields'        => array(
						'employer'  => array(
							'label'       => __( 'Employer', 'wp-job-manager-resumes' ),
							'type'        => 'text',
							'required'    => true,
							'placeholder' => '',
						),
						'job_title' => array(
							'label'       => __( 'Job Title', 'wp-job-manager-resumes' ),
							'type'        => 'text',
							'required'    => true,
							'placeholder' => '',
						),
						'date'      => array(
							'label'       => __( 'Start/end date', 'wp-job-manager-resumes' ),
							'type'        => 'text',
							'required'    => true,
							'placeholder' => '',
						),
						'notes'     => array(
							'label'       => __( 'Notes', 'wp-job-manager-resumes' ),
							'type'        => 'textarea',
							'required'    => false,
							'placeholder' => '',
						),
					),
					'personal_data' => true,
				),
				'resume_file'          => array(
					'label'         => __( 'Resume file', 'wp-job-manager-resumes' ),
					'type'          => 'file',
					'required'      => false,
					'ajax'          => true,
					'description'   => sprintf( __( 'Optionally upload your resume for employers to view. Max. file size: %s.', 'wp-job-manager-resumes' ), size_format( wp_max_upload_size() ) ),
					'priority'      => 13,
					'placeholder'   => '',
					'personal_data' => true,
				),
			),
		) );

		if ( ! get_option( 'resume_manager_enable_resume_upload' ) ) {
			unset( $this->fields['resume_fields']['resume_file'] );
		}

		if ( ! get_option( 'resume_manager_enable_categories' ) || wp_count_terms( 'resume_category' ) == 0 ) {
			unset( $this->fields['resume_fields']['resume_category'] );
		}

		if ( ! get_option( 'resume_manager_enable_skills' ) ) {
			unset( $this->fields['resume_fields']['resume_skills'] );
		}
	}

	/**
	 * Get the value of a repeated fields (e.g. education, links)
	 * @param  array $fields
	 * @return array
	 */
	public function get_repeated_field( $field_prefix, $fields ) {
		$items       = array();
		$field_keys  = array_keys( $fields );

		if ( ! empty( $_POST[ 'repeated-row-' . $field_prefix ] ) && is_array( $_POST[ 'repeated-row-' . $field_prefix ] ) ) {
			$indexes = array_map( 'absint', $_POST[ 'repeated-row-' . $field_prefix ] );
			foreach ( $indexes as $index ) {
				$item = array();
				foreach ( $fields as $key => $field ) {
					$field_name = $field_prefix . '_' . $key . '_' . $index;

					switch ( $field['type'] ) {
						case 'textarea' :
							$item[ $key ] = wp_kses_post( stripslashes( $_POST[ $field_name ] ) );
						break;
						case 'file' :
							$file = $this->upload_file( $field_name, $field );

							if ( ! $file ) {
								$file = $this->get_posted_field( 'current_' . $field_name, $field );
							} elseif ( is_array( $file ) ) {
								$file = array_filter( array_merge( $file, (array) $this->get_posted_field( 'current_' . $field_name, $field ) ) );
							}

							$item[ $key ] = $file;
						break;
						default :
							if ( is_array( $_POST[ $field_name ] ) ) {
								$item[ $key ] = array_filter( array_map( 'sanitize_text_field', array_map( 'stripslashes', $_POST[ $field_name ] ) ) );
							} else {
								$item[ $key ] = sanitize_text_field( stripslashes( $_POST[ $field_name ] ) );
							}
						break;
					}
					if ( empty( $item[ $key ] ) && ! empty( $field['required'] ) ) {
						continue 2;
					}
				}
				$items[] = $item;
			}
		}
		return $items;
	}

	/**
	 * Use reCAPTCHA field on the form?
	 *
	 * @return bool
	 */
	public function use_recaptcha_field() {
		if ( ! method_exists( $this, 'is_recaptcha_available' ) || ! $this->is_recaptcha_available() ) {
			return false;
		}
		return 1 === absint( get_option( 'resume_manager_enable_recaptcha_resume_submission' ) );
	}

	/**
	 * Get the value of a posted repeated field
	 * @since  1.22.4
	 * @param  string $key
	 * @param  array $field
	 * @return string
	 */
	public function get_posted_repeated_field( $key, $field ) {
		return apply_filters( 'submit_resume_form_fields_get_repeated_field_data', $this->get_repeated_field( $key, $field['fields'] ) );
	}

	/**
	 * Get the value of a posted file field
	 * @param  string $key
	 * @param  array $field
	 * @return string
	 */
	public function get_posted_links_field( $key, $field ) {
		return apply_filters( 'submit_resume_form_fields_get_links_data', $this->get_repeated_field( $key, $field['fields'] ) );
	}

	/**
	 * Get the value of a posted file field
	 * @param  string $key
	 * @param  array $field
	 * @return string
	 */
	public function get_posted_education_field( $key, $field ) {
		return apply_filters( 'submit_resume_form_fields_get_education_data', $this->get_repeated_field( $key, $field['fields'] ) );
	}

	/**
	 * Get the value of a posted file field
	 * @param  string $key
	 * @param  array $field
	 * @return string
	 */
	public function get_posted_experience_field( $key, $field ) {
		return apply_filters( 'submit_resume_form_fields_get_experience_data', $this->get_repeated_field( $key, $field['fields'] ) );
	}

	/**
	 * Validate the posted fields
	 *
	 * @return bool on success, WP_ERROR on failure
	 */
	protected function validate_fields( $values ) {
		foreach ( $this->fields as $group_key => $fields ) {
			foreach ( $fields as $key => $field ) {
				if ( $field['required'] && empty( $values[ $group_key ][ $key ] ) ) {
					return new WP_Error( 'validation-error', sprintf( __( '%s is a required field', 'wp-job-manager-resumes' ), $field['label'] ) );
				}
				if ( ! empty( $field['taxonomy'] ) && in_array( $field['type'], array( 'term-checklist', 'term-select', 'term-multiselect' ) ) ) {
					if ( is_array( $values[ $group_key ][ $key ] ) ) {
						foreach ( $values[ $group_key ][ $key ] as $term ) {
							if ( ! term_exists( $term, $field['taxonomy'] ) ) {
								return new WP_Error( 'validation-error', sprintf( __( '%s is invalid', 'wp-job-manager-resumes' ), $field['label'] ) );
							}
						}
					} elseif ( ! empty( $values[ $group_key ][ $key ] ) ) {
						if ( ! term_exists( $values[ $group_key ][ $key ], $field['taxonomy'] ) ) {
							return new WP_Error( 'validation-error', sprintf( __( '%s is invalid', 'wp-job-manager-resumes' ), $field['label'] ) );
						}
					}
				}

				if ( 'candidate_email' === $key ) {
					if ( ! empty( $values[ $group_key ][ $key ] ) && ! is_email( $values[ $group_key ][ $key ] ) ) {
						throw new Exception( __( 'Please enter a valid email address', 'wp-job-manager-resumes' ) );
					}
				}

				if ( 'resume_skills' === $key ) {
					if ( is_string( $values[ $group_key ][ $key ] ) ) {
						$raw_skills = explode( ',', $values[ $group_key ][ $key ] );
					} else {
						$raw_skills = $values[ $group_key ][ $key ];
					}
					$max = get_option( 'resume_manager_max_skills' );

					if ( $max && sizeof( $raw_skills ) > $max ) {
						return new WP_Error( 'validation-error', sprintf( __( 'Please enter no more than %d skills.', 'wp-job-manager-resumes' ), $max ) );
					}
				}
			}
		}

		return apply_filters( 'submit_resume_form_validate_fields', true, $this->fields, $values );
	}

	/**
	 * get categories.
	 *
	 * @access private
	 * @return void
	 */
	private function resume_categories() {
		$options = array();
		$terms   = get_resume_categories();
		foreach ( $terms as $term )
			$options[ $term->slug ] = $term->name;
		return $options;
	}

	/**
	 * Submit Step
	 */
	public function submit() {
		global $job_manager, $post;

		$this->init_fields();

		// Load data if neccessary
		if ( $this->resume_id ) {
			$resume = get_post( $this->resume_id );
			foreach ( $this->fields as $group_key => $fields ) {
				foreach ( $fields as $key => $field ) {
					switch ( $key ) {
						case 'candidate_name' :
							$this->fields[ $group_key ][ $key ]['value'] = $resume->post_title;
						break;
						case 'resume_content' :
							$this->fields[ $group_key ][ $key ]['value'] = $resume->post_content;
						break;
						case 'resume_skills' :
							$this->fields[ $group_key ][ $key ]['value'] = implode( ', ', wp_get_object_terms( $resume->ID, 'resume_skill', array( 'fields' => 'names' ) ) );
						break;
						case 'resume_category' :
							$this->fields[ $group_key ][ $key ]['value'] = wp_get_object_terms( $resume->ID, 'resume_category', array( 'fields' => 'ids' ) );
						break;
						default:
							$this->fields[ $group_key ][ $key ]['value'] = get_post_meta( $resume->ID, '_' . $key, true );
						break;
					}
				}
			}
			$this->fields = apply_filters( 'submit_resume_form_fields_get_resume_data', $this->fields, $resume );

		// Get user meta
		} elseif ( is_user_logged_in() && empty( $_POST['submit_resume'] ) ) {
			$user = wp_get_current_user();
			foreach ( $this->fields as $group_key => $fields ) {
				foreach ( $fields as $key => $field ) {
					switch ( $key ) {
						case 'candidate_name' :
							$this->fields[ $group_key ][ $key ]['value'] = $user->first_name . ' ' . $user->last_name;
						break;
						case 'candidate_email' :
							$this->fields[ $group_key ][ $key ]['value'] = $user->user_email;
						break;
					}
				}
			}
			$this->fields = apply_filters( 'submit_resume_form_fields_get_user_data', $this->fields, get_current_user_id() );
		}

		get_job_manager_template( 'resume-submit.php', array(
			'class'              => $this,
			'form'               => $this->form_name,
			'resume_id'          => $this->get_resume_id(),
			'job_id'             => $this->get_job_id(),
			'action'             => $this->get_action(),
			'resume_fields'      => $this->get_fields( 'resume_fields' ),
			'step'               => $this->get_step(),
			'submit_button_text' => apply_filters( 'submit_resume_form_submit_button_text', __( 'Preview &rarr;', 'wp-job-manager-resumes' ) )
		), 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' );
	}

	/**
	 * Submit Step is posted
	 */
	public function submit_handler() {
		try {

			// Init fields
			$this->init_fields();

			// Get posted values
			$values = $this->get_posted_fields();

			if ( empty( $_POST['submit_resume'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'submit_form_posted' ) )
				return;

			// Validate required
			if ( is_wp_error( ( $return = $this->validate_fields( $values ) ) ) ) {
				throw new Exception( $return->get_error_message() );
			}

			// Account creation
			if ( ! is_user_logged_in() ) {
				$create_account = false;

				if ( resume_manager_enable_registration() ) {
					if ( resume_manager_user_requires_account() ) {
						if ( ! resume_manager_generate_username_from_email() && empty( $_POST['create_account_username'] ) ) {
							throw new Exception( __( 'Please enter a username.', 'wp-job-manager-resumes' ) );
						}
						if ( empty( $_POST['candidate_email'] ) ) {
							throw new Exception( __( 'Please enter your email address.', 'wp-job-manager-resumes' ) );
						}
					}
					if ( ! empty( $_POST['candidate_email'] ) ) {
						if ( version_compare( JOB_MANAGER_VERSION, '1.20.0', '<' ) ) {
							$create_account = wp_job_manager_create_account( $_POST['candidate_email'], get_option( 'resume_manager_registration_role', 'candidate' ) );
						} else {
							$create_account = wp_job_manager_create_account( array(
								'username' => empty( $_POST['create_account_username'] ) ? '' : $_POST['create_account_username'],
								'email'    => $_POST['candidate_email'],
								'role'     => get_option( 'resume_manager_registration_role', 'candidate' )
							) );
						}
					}
				}

				if ( is_wp_error( $create_account ) ) {
					throw new Exception( $create_account->get_error_message() );
				}
			}

			if ( resume_manager_user_requires_account() && ! is_user_logged_in() ) {
				throw new Exception( __( 'You must be signed in to post your resume.', 'wp-job-manager-resumes' ) );
			}

			// Update the job
			$this->save_resume( $values['resume_fields']['candidate_name'], $values['resume_fields']['resume_content'], $this->resume_id ? '' : 'preview', $values );
			$this->update_resume_data( $values );

			// Successful, show next step
			$this->step ++;

		} catch ( Exception $e ) {
			$this->add_error( $e->getMessage() );
			return;
		}
	}

	/**
	 * Update or create a job listing from posted data
	 *
	 * @param  string $post_title
	 * @param  string $post_content
	 * @param  string $status
	 */
	protected function save_resume( $post_title, $post_content, $status = 'preview', $values = array() ) {
		// Get random key
		if ( $this->resume_id ) {
			$prefix = get_post_meta( $this->resume_id, '_resume_name_prefix', true );

			if ( ! $prefix ) {
				$prefix = wp_generate_password( 10 );
			}
		} else {
			$prefix        = wp_generate_password( 10 );
		}

		$resume_slug   = array();
		$resume_slug[] = current( explode( ' ', $post_title ) );
		$resume_slug[] = $prefix;

		if ( ! empty( $values['resume_fields']['candidate_title'] ) ) {
			$resume_slug[] = $values['resume_fields']['candidate_title'];
		}

		if ( ! empty( $values['resume_fields']['candidate_location'] ) ) {
			$resume_slug[] = $values['resume_fields']['candidate_location'];
		}

		$data = array(
			'post_title'     => $post_title,
			'post_content'   => $post_content,
			'post_type'      => 'resume',
			'comment_status' => 'closed',
			'post_password'  => '',
			'post_name'      => sanitize_title( implode( '-', $resume_slug ) )
		);

		if ( $status ) {
			$data['post_status'] = $status;
		}

		$data = apply_filters( 'submit_resume_form_save_resume_data', $data, $post_title, $post_content, $status, $values, $this );

		if ( $this->resume_id ) {
			$data['ID'] = $this->resume_id;
			wp_update_post( $data );
		} else {
			$this->resume_id = wp_insert_post( $data );
			update_post_meta( $this->resume_id, '_resume_name_prefix', $prefix );
			update_post_meta( $this->resume_id, '_public_submission', true );

			// If and only if we're dealing with a logged out user and that is allowed, allow the user to continue a submission after it was started.
			if ( ! is_user_logged_in() && ! resume_manager_user_requires_account() ) {
				$submitting_key = sha1( uniqid() );
				setcookie( 'wp-job-manager-submitting-resume-key-' . $this->resume_id, $submitting_key, 0, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
				update_post_meta( $this->resume_id, '_submitting_key', $submitting_key );
			}

			// Save profile fields
			$current_user   = wp_get_current_user();
			$candidate_name = explode( ' ', $post_title );

			if ( empty( $current_user->first_name ) && empty( $current_user->last_name ) && sizeof( $candidate_name ) > 1 ) {
				wp_update_user(
					array(
						'ID'         => $current_user->ID,
						'first_name' => current( $candidate_name ),
						'last_name'  => end( $candidate_name )
					)
				);
			}
		}
	}

	/**
	 * Set job meta + terms based on posted values
	 *
	 * @param  array $values
	 */
	protected function update_resume_data( $values ) {
		// Set defaults
		add_post_meta( $this->resume_id, '_featured', 0, true );
		add_post_meta( $this->resume_id, '_applying_for_job_id', $this->job_id, true );

		$maybe_attach = array();

		// Loop fields and save meta and term data
		foreach ( $this->fields as $group_key => $group_fields ) {
			foreach ( $group_fields as $key => $field ) {
				// Save taxonomies
				if ( ! empty( $field['taxonomy'] ) ) {
					if ( is_array( $values[ $group_key ][ $key ] ) ) {
						wp_set_object_terms( $this->resume_id, $values[ $group_key ][ $key ], $field['taxonomy'], false );
					} else {
						wp_set_object_terms( $this->resume_id, array( $values[ $group_key ][ $key ] ), $field['taxonomy'], false );
					}

				// Save meta data
				} else {
					update_post_meta( $this->resume_id, '_' . $key, $values[ $group_key ][ $key ] );
				}

				// Handle attachments
				if ( 'file' === $field['type'] ) {
					// Must be absolute
					if ( is_array( $values[ $group_key ][ $key ] ) ) {
						foreach ( $values[ $group_key ][ $key ] as $file_url ) {
							$maybe_attach[] = str_replace( array( WP_CONTENT_URL, site_url() ), array( WP_CONTENT_DIR, ABSPATH ), $file_url );
						}
					} else {
						$maybe_attach[] = str_replace( array( WP_CONTENT_URL, site_url() ), array( WP_CONTENT_DIR, ABSPATH ), $values[ $group_key ][ $key ] );
					}
				}
			}
		}

		if ( get_option( 'resume_manager_enable_skills' ) && isset( $values['resume_fields']['resume_skills'] ) ) {

			$tags     = array();
			$raw_tags = $values['resume_fields']['resume_skills'];

			if ( is_string( $raw_tags ) ) {
				// Explode and clean
				$raw_tags = array_filter( array_map( 'sanitize_text_field', explode( ',', $raw_tags ) ) );

				if ( ! empty( $raw_tags ) ) {
					foreach ( $raw_tags as $tag ) {
						if ( $term = get_term_by( 'name', $tag, 'resume_skill' ) ) {
							$tags[] = $term->term_id;
						} else {
							$term = wp_insert_term( $tag, 'resume_skill' );

							if ( ! is_wp_error( $term ) ) {
								$tags[] = $term['term_id'];
							}
						}
					}
				}
			} else {
				$tags = array_map( 'absint', $raw_tags );
			}

			wp_set_object_terms( $this->resume_id, $tags, 'resume_skill', false );
		}

		// Handle attachments
		if ( sizeof( $maybe_attach ) && apply_filters( 'resume_manager_attach_uploaded_files', false ) ) {
			/** WordPress Administration Image API */
			include_once( ABSPATH . 'wp-admin/includes/image.php' );

			// Get attachments
			$attachments     = get_posts( 'post_parent=' . $this->resume_id . '&post_type=attachment&fields=ids&post_mime_type=image&numberposts=-1' );
			$attachment_urls = array();

			// Loop attachments already attached to the job
			foreach ( $attachments as $attachment_key => $attachment ) {
				$attachment_urls[] = str_replace( array( WP_CONTENT_URL, site_url() ), array( WP_CONTENT_DIR, ABSPATH ), wp_get_attachment_url( $attachment ) );
			}

			foreach ( $maybe_attach as $attachment_url ) {
				if ( ! in_array( $attachment_url, $attachment_urls ) ) {
					$attachment = array(
						'post_title'   => get_the_title( $this->resume_id ),
						'post_content' => '',
						'post_status'  => 'inherit',
						'post_parent'  => $this->resume_id,
						'guid'         => $attachment_url
					);

					if ( $info = wp_check_filetype( $attachment_url ) ) {
						$attachment['post_mime_type'] = $info['type'];
					}

					$attachment_id = wp_insert_attachment( $attachment, $attachment_url, $this->resume_id );

					if ( ! is_wp_error( $attachment_id ) ) {
						wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $attachment_url ) );
					}
				}
			}
		}

		do_action( 'resume_manager_update_resume_data', $this->resume_id, $values );
	}

	/**
	 * Preview Step
	 */
	public function preview() {
		global $post, $resume_preview;

		wp_enqueue_script( 'wp-resume-manager-resume-submission' );

		if ( $this->resume_id ) {

			$resume_preview = true;
			$post = get_post( $this->resume_id );
			setup_postdata( $post );
			?>
			<form method="post" id="resume_preview">
				<div class="resume_preview_title">
					<input type="submit" name="continue" id="resume_preview_submit_button" class="button" value="<?php echo apply_filters( 'submit_resume_step_preview_submit_text', __( 'Submit Resume &rarr;', 'wp-job-manager-resumes' ) ); ?>" />
					<input type="submit" name="edit_resume" class="button" value="<?php _e( '&larr; Edit resume', 'wp-job-manager-resumes' ); ?>" />
					<input type="hidden" name="resume_id" value="<?php echo esc_attr( $this->resume_id ); ?>" />
					<input type="hidden" name="job_id" value="<?php echo esc_attr( $this->job_id ); ?>" />
					<input type="hidden" name="step" value="<?php echo esc_attr( $this->step ); ?>" />
					<input type="hidden" name="resume_manager_form" value="<?php echo $this->form_name; ?>" />
					<h2>
						<?php _e( 'Preview', 'wp-job-manager-resumes' ); ?>
					</h2>
				</div>
				<div class="resume_preview single-resume">
					<h1><?php the_title(); ?></h1>
					<?php get_job_manager_template_part( 'content-single', 'resume', 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' ); ?>
				</div>
			</form>
			<?php

			wp_reset_postdata();
		}
	}

	/**
	 * Preview Step Form handler
	 */
	public function preview_handler() {
		if ( ! $_POST ) {
			return;
		}

		// Edit = show submit form again
		if ( ! empty( $_POST['edit_resume'] ) ) {
			$this->step --;
		}

		// Continue = change job status then show next screen
		if ( ! empty( $_POST['continue'] ) ) {
			$resume = get_post( $this->resume_id );

			if ( in_array( $resume->post_status, array( 'preview', 'expired' ) ) ) {
				// Reset expiry
				delete_post_meta( $resume->ID, '_resume_expires' );

				// Update listing
				$update_resume                  = array();
				$update_resume['ID']            = $resume->ID;
				$update_resume['post_date']     = current_time( 'mysql' );
				$update_resume['post_date_gmt'] = current_time( 'mysql', 1 );
				$update_resume['post_author']   = get_current_user_id();
				$update_resume['post_status']   = apply_filters( 'submit_resume_post_status', get_option( 'resume_manager_submission_requires_approval' ) ? 'pending' : 'publish', $resume );

				wp_update_post( $update_resume );
			}

			$this->step ++;

			/**
			 * Do not redirect if WCPL is set to choose package before submitting listing
			 *
			 * By not redirecting, we allow $this->process() (@see abstract-wp-job-manager-form.php) to call the 'wc-process-package'
			 * handler first, instead of view, which does not exist in 'wc-process-package' (and would be called first on redirect).
			 */
			if( 'before' !== get_option( 'resume_manager_paid_listings_flow' ) ){
				wp_safe_redirect( esc_url_raw( add_query_arg( array( 'step' => $this->step, 'job_id' => $this->job_id, 'resume_id' => $this->resume_id ) ) ) );
				exit;
			}

		}
	}

	/**
	 * Done Step
	 */
	public function done() {
		get_job_manager_template( 'resume-submitted.php', array( 'resume' => get_post( $this->resume_id ), 'job_id' => $this->job_id ), 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' );

		// Allow application
		if ( $this->job_id ) {
			echo '<h3 class="applying_for">' . sprintf( __( 'Submit your application to the job "%s".', 'wp-job-manager-resumes' ), '<a href="' . get_permalink( $this->job_id ) . '">' . get_the_title( $this->job_id ) . '</a>' ) .'</h3>';

			echo do_shortcode( '[job_apply id="' . absint( $this->job_id ) . '"]' );
		}
	}

	/**
	 * @return array
	 */
	public static function get_resume_fields() {
		$instance = self::instance();
		$instance->init_fields();
		return $instance->get_fields( 'resume_fields' );
	}
}
