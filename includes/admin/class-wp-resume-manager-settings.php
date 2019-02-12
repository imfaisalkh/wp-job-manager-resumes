<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Resume_Manager_Settings class.
 */
class WP_Resume_Manager_Settings extends WP_Job_Manager_Settings {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->settings_group = 'wp-job-manager-resumes';
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Init_settings function.
	 *
	 * @access protected
	 * @return void
	 */
	protected function init_settings() {
		// Prepare roles option.
		$roles         = get_editable_roles();
		$account_roles = array();

		foreach ( $roles as $key => $role ) {
			if ( 'administrator' === $key ) {
				continue;
			}
			$account_roles[ $key ] = $role['name'];
		}

		$empty_trash_days = defined( 'EMPTY_TRASH_DAYS ' ) ? EMPTY_TRASH_DAYS : 30;
		if ( empty( $empty_trash_days ) || $empty_trash_days < 0 ) {
			$trash_description = __( 'They will then need to be manually removed from the trash', 'wp-job-manager-resumes' );
		} else {
			// translators: Placeholder %d is the number of days before items are removed from trash.
			$trash_description = sprintf( __( 'They will then be permanently deleted after %d days.', 'wp-job-manager-resumes' ), $empty_trash_days );
		}

		$this->settings = apply_filters(
			'resume_manager_settings',
			array(
				'resume_listings'    => array(
					__( 'Resume Listings', 'wp-job-manager-resumes' ),
					array(
						array(
							'name'        => 'resume_manager_per_page',
							'std'         => '10',
							'placeholder' => '',
							'label'       => __( 'Resumes Per Page', 'wp-job-manager-resumes' ),
							'desc'        => __( 'How many resumes should be shown per page by default?', 'wp-job-manager-resumes' ),
							'attributes'  => array(),
						),
						array(
							'name'       => 'resume_manager_enable_categories',
							'std'        => '0',
							'label'      => __( 'Categories', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Enable resume categories', 'wp-job-manager-resumes' ),
							'desc'       => __( 'Choose whether to enable resume categories. Categories must be setup by an admin for users to choose during job submission.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => array(),
						),
						array(
							'name'       => 'resume_manager_enable_default_category_multiselect',
							'std'        => '0',
							'label'      => __( 'Multi-select Categories', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Enable category multiselect by default', 'wp-job-manager-resumes' ),
							'desc'       => __( 'If enabled, the category select box will default to a multiselect on the [resumes] shortcode.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => array(),
						),
						array(
							'name'    => 'resume_manager_category_filter_type',
							'std'     => 'any',
							'label'   => __( 'Category Filter Type', 'wp-job-manager-resumes' ),
							'desc'    => __( 'Choose how to filter resumes when Multi-select Categories option is enabled.', 'wp-job-manager-resumes' ),
							'type'    => 'select',
							'options' => array(
								'any' => __( 'Resumes will be shown if within ANY selected category', 'wp-job-manager-resumes' ),
								'all' => __( 'Resumes will be shown if within ALL selected categories', 'wp-job-manager-resumes' ),
							),
						),
						array(
							'name'       => 'resume_manager_enable_skills',
							'std'        => '0',
							'label'      => __( 'Skills', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Enable resume skills', 'wp-job-manager-resumes' ),
							'desc'       => __( 'Choose whether to enable the resume skills field. Skills can be added by users during resume submission.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => array(),
						),
						array(
							'name'        => 'resume_manager_max_skills',
							'std'         => '',
							'label'       => __( 'Maximum Skills', 'wp-job-manager-resumes' ),
							'placeholder' => __( 'Unlimited', 'wp-job-manager-resumes' ),
							'desc'        => __( 'Enter the number of skills per resume submission you wish to allow, or leave blank for unlimited skills.', 'wp-job-manager-resumes' ),
							'type'        => 'input',
						),
						array(
							'name'       => 'resume_manager_enable_resume_upload',
							'std'        => '0',
							'label'      => __( 'Resume Upload', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Enable resume upload', 'wp-job-manager-resumes' ),
							'desc'       => __( 'Choose whether to allow candidates to upload a resume file.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => array(),
						),
						array(
							'name'       => 'resume_manager_erasure_request_removes_resumes',
							'std'        => '0',
							'label'      => __( 'Personal Data Erasure', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Remove resumes on account erasure requests', 'wp-job-manager-resumes' ),
							'desc'       => sprintf(
								// translators: Placeholder %1$s is the URL to the WP Admin page that handles account erasure requests. %2$s is trash notification.
								__( 'If enabled, resumes with a matching email address will be sent to the trash during <a href="%1$s">personal data erasure requests</a>. %2$s', 'wp-job-manager-resumes' ),
								esc_url( admin_url( 'tools.php?page=remove_personal_data' ) ),
								$trash_description
							),
							'type'       => 'checkbox',
							'attributes' => array(),
						),
					),
				),
				'resume_submission'  => array(
					__( 'Resume Submission', 'wp-job-manager-resumes' ),
					array(
						array(
							'name'       => 'resume_manager_user_requires_account',
							'std'        => '1',
							'label'      => __( 'Account Required', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Submitting listings requires an account', 'wp-job-manager-resumes' ),
							'desc'       => __( 'If disabled, non-logged in users will be able to submit listings without creating an account. Please note that this will prevent non-registered users from being able to edit their listings at a later date.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => array(),
						),
						array(
							'name'       => 'resume_manager_enable_registration',
							'std'        => '1',
							'label'      => __( 'Account Creation', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Allow account creation', 'wp-job-manager-resumes' ),
							'desc'       => __( 'If enabled, non-logged in users will be able to create an account by entering their email address on the resume submission form.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => array(),
						),
						array(
							'name'       => 'resume_manager_generate_username_from_email',
							'std'        => '1',
							'label'      => __( 'Account Username', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Automatically Generate Username from Email Address', 'wp-job-manager-resumes' ),
							'desc'       => __( 'If enabled, a username will be generated from the first part of the user email address. Otherwise, a username field will be shown.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => array(),
						),
						array(
							'name'    => 'resume_manager_registration_role',
							'std'     => 'candidate',
							'label'   => __( 'Account Role', 'wp-job-manager-resumes' ),
							'desc'    => __( 'If you enable registration on your submission form, choose a role for the new user.', 'wp-job-manager-resumes' ),
							'type'    => 'select',
							'options' => $account_roles,
						),
						array(
							'name'       => 'resume_manager_submission_requires_approval',
							'std'        => '1',
							'label'      => __( 'Approval Required', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'New submissions require admin approval', 'wp-job-manager-resumes' ),
							'desc'       => __( 'If enabled, new submissions will be inactive, pending admin approval.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => array(),
						),
						array(
							'name'       => 'resume_manager_submission_notification',
							'std'        => '1',
							'label'      => __( 'Email New Submissions', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Email resume details to the admin/notification recipient after submission.', 'wp-job-manager-resumes' ),
							// translators: Placeholder %s is the email address to send admin notifications to.
							'desc'       => sprintf( __( 'If enabled, all resume details for new submissions will be emailed to %s.', 'wp-job-manager-resumes' ), get_option( 'resume_manager_email_notifications' ) ? get_option( 'resume_manager_email_notifications' ) : get_option( 'admin_email' ) ),
							'type'       => 'checkbox',
							'attributes' => array(),
						),
						array(
							'name'        => 'resume_manager_email_notifications',
							'std'         => '',
							'placeholder' => get_option( 'admin_email' ),
							'label'       => __( 'Notify Email Address(es)', 'wp-job-manager-resumes' ),
							'desc'        => __( 'Instead of the admin, email notifications to these folks instead. Comma separate addresses.', 'wp-job-manager-resumes' ),
							'type'        => 'input',
						),
						array(
							'name'        => 'resume_manager_submission_duration',
							'std'         => '',
							'label'       => __( 'Listing Duration', 'wp-job-manager-resumes' ),
							'desc'        => __( 'How many <strong>days</strong> listings are live before expiring. Can be left blank to never expire. Expired listings must be relisted to become visible.', 'wp-job-manager-resumes' ),
							'attributes'  => array(),
							'placeholder' => __( 'Never expire', 'wp-job-manager-resumes' ),
						),
						array(
							'name'        => 'resume_manager_autohide',
							'std'         => '',
							'label'       => __( 'Auto-hide Resumes', 'wp-job-manager-resumes' ),
							'desc'        => __( 'How many <strong>days</strong> un-modified resumes should be published before being hidden. Can be left blank to never hide resumes automatically. Candidates can re-publish hidden resumes form their dashboard.', 'wp-job-manager-resumes' ),
							'attributes'  => array(),
							'placeholder' => __( 'Never auto-hide', 'wp-job-manager-resumes' ),
						),
						array(
							'name'        => 'resume_manager_submission_limit',
							'std'         => '',
							'label'       => __( 'Listing Limit', 'wp-job-manager-resumes' ),
							'desc'        => __( 'How many listings are users allowed to post. Can be left blank to allow unlimited listings per account.', 'wp-job-manager-resumes' ),
							'attributes'  => array(),
							'placeholder' => __( 'No limit', 'wp-job-manager-resumes' ),
						),
						array(
							'name'       => 'resume_manager_linkedin_import',
							'std'        => '0',
							'label'      => __( 'LinkedIn Import', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Allow import of resume data from LinkedIn', 'wp-job-manager-resumes' ),
							'desc'       => __( 'If enabled, users will be able to login to LinkedIn and have the resume submission form automatically populated.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => array(),
						),
						'api_key'   => array(
							'name'  => 'job_manager_linkedin_api_key',
							'std'   => '',
							'label' => __( 'LinkedIn Application Client ID', 'wp-job-manager-resumes' ),
							// translators: Placeholder %s is the URL to the LinkedIn Developer portal.
							'desc'  => sprintf( __( 'Get your Client ID by creating a new application on <a href="%s">LinkedIn Developers</a>. Enter your site\'s domain under the LinkedIn application\'s JavaScript settings.', 'wp-job-manager-resumes' ), 'https://www.linkedin.com/secure/developer' ),
							'type'  => 'input',
						),
						'recaptcha' => array(
							'name'       => 'resume_manager_enable_recaptcha_resume_submission',
							'std'        => '0',
							'label'      => __( 'reCAPTCHA', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Display a reCAPTCHA field on resume submission form.', 'wp-job-manager-resumes' ),
							'desc'       => sprintf(
								// translators: Placeholder %s is the URL to the page in WP Job Manager's settings to make the change.
								__( 'This will help prevent bots from submitting resumes. You must have entered a valid site key and secret key in <a href="%s">WP Job Manager\'s settings</a>.', 'wp-job-manager-resumes' ),
								esc_url( admin_url( 'edit.php?post_type=job_listing&page=job-manager-settings#settings-recaptcha' ) )
							),
							'type'       => 'checkbox',
							'attributes' => array(),
						),
					),
				),
				'resume_application' => array(
					__( 'Apply with Resume', 'wp-job-manager-resumes' ),
					array(
						array(
							'name'     => 'resume_manager_enable_application',
							'std'      => '1',
							'label'    => __( 'Email Based Applications', 'wp-job-manager-resumes' ),
							'cb_label' => __( 'Allow candidates to apply to jobs which use the email application method using their online resume', 'wp-job-manager-resumes' ),
							'desc'     => __( 'The employer will be mailed their message and a private link to the resume.', 'wp-job-manager-resumes' ),
							'type'     => 'checkbox',
						),
						array(
							'name'     => 'resume_manager_enable_application_for_url_method',
							'std'      => '1',
							'label'    => __( 'Website Based Applications', 'wp-job-manager-resumes' ),
							'cb_label' => __( 'Allow candidates to apply to jobs which use the the website URL application method using their online resume', 'wp-job-manager-resumes' ),
							'desc'     => __( 'The application will be stored in the database.', 'wp-job-manager-resumes' ),
							'type'     => 'checkbox',
						),
						array(
							'name'       => 'resume_manager_force_resume',
							'std'        => '0',
							'label'      => __( 'Force Resume Creation', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Force candidates to create an online resume before applying to a job', 'wp-job-manager-resumes' ),
							'desc'       => __( 'Candidates without a resume on file will be taken through the resume submission process. Other details, such as the application email address or application forms, will be hidden.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => array(),
						),
						array(
							'name'       => 'resume_manager_force_application',
							'std'        => '0',
							'label'      => __( 'Force Apply with Resume', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Force candidates to apply through Resume Manager', 'wp-job-manager-resumes' ),
							'desc'       => __( 'If the apply forms are enabled above, they must be used to apply. All other application methods will be hidden.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => array(),
						),
					),
				),
				'resume_pages'       => array(
					__( 'Pages', 'wp-job-manager-resumes' ),
					array(
						array(
							'name'  => 'resume_manager_submit_resume_form_page_id',
							'std'   => '',
							'label' => __( 'Submit Resume Page', 'wp-job-manager-resumes' ),
							'desc'  => __( 'Select the page where you have placed the [submit_resume_form] shortcode. This lets the plugin know where the form is located.', 'wp-job-manager-resumes' ),
							'type'  => 'page',
						),
						array(
							'name'  => 'resume_manager_candidate_dashboard_page_id',
							'std'   => '',
							'label' => __( 'Candidate Dashboard Page', 'wp-job-manager-resumes' ),
							'desc'  => __( 'Select the page where you have placed the [candidate_dashboard] shortcode. This lets the plugin know where the dashboard is located.', 'wp-job-manager-resumes' ),
							'type'  => 'page',
						),
						array(
							'name'  => 'resume_manager_resumes_page_id',
							'std'   => '',
							'label' => __( 'Resume Listings Page', 'wp-job-manager-resumes' ),
							'desc'  => __( 'Select the page where you have placed the [resumes] shortcode. This lets the plugin know where the resume listings page is located.', 'wp-job-manager-resumes' ),
							'type'  => 'page',
						),
					),
				),
				'resume_visibility'  => array(
					__( 'Resume Visibility', 'wp-job-manager-resumes' ),
					array(
						array(
							'name'  => 'resume_manager_view_name_capability',
							'std'   => '',
							'label' => __( 'View Resume name Capability', 'wp-job-manager-resumes' ),
							'type'  => 'input',
							// translators: Placeholder %s is the url to the WordPress core documentation for capabilities and roles.
							'desc'  => sprintf( __( 'Enter the <a href="%s">capability</a> required in order to view resumes names. Supports a comma separated list of roles/capabilities.', 'wp-job-manager-resumes' ), 'http://codex.wordpress.org/Roles_and_Capabilities' ),
						),
						array(
							'name'  => 'resume_manager_browse_resume_capability',
							'std'   => '',
							'label' => __( 'Browse Resume Capability', 'wp-job-manager-resumes' ),
							'type'  => 'input',
							// translators: Placeholder %s is the url to the WordPress core documentation for capabilities and roles.
							'desc'  => sprintf( __( 'Enter the <a href="%s">capability</a> required in order to browse resumes. Supports a comma separated list of roles/capabilities.', 'wp-job-manager-resumes' ), 'http://codex.wordpress.org/Roles_and_Capabilities' ),
						),
						array(
							'name'  => 'resume_manager_view_resume_capability',
							'std'   => '',
							'label' => __( 'View Resume Capability', 'wp-job-manager-resumes' ),
							'type'  => 'input',
							// translators: Placeholder %s is the url to the WordPress core documentation for capabilities and roles.
							'desc'  => sprintf( __( 'Enter the <a href="%s">capability</a> required in order to view a single resume. Supports a comma separated list of roles/capabilities.', 'wp-job-manager-resumes' ), 'http://codex.wordpress.org/Roles_and_Capabilities' ),
						),
						array(
							'name'  => 'resume_manager_contact_resume_capability',
							'std'   => '',
							'label' => __( 'Contact Details Capability', 'wp-job-manager-resumes' ),
							'type'  => 'input',
							// translators: Placeholder %s is the url to the WordPress core documentation for capabilities and roles.
							'desc'  => sprintf( __( 'Enter the <a href="%s">capability</a> required in order to view contact details on a resume. Supports a comma separated list of roles/capabilities.', 'wp-job-manager-resumes' ), 'http://codex.wordpress.org/Roles_and_Capabilities' ),
						),
						array(
							'name'       => 'resume_manager_discourage_resume_search_indexing',
							'std'        => '0',
							'label'      => __( 'Search Engine Visibility', 'wp-job-manager-resumes' ),
							'cb_label'   => __( 'Discourage search engines from indexing resume listings', 'wp-job-manager-resumes' ),
							'desc'       => __( 'Search engines choose whether to honor this request.', 'wp-job-manager-resumes' ),
							'type'       => 'checkbox',
							'attributes' => array(),
						),
					),
				),
			)
		);

		if ( ! defined( 'JOB_MANAGER_VERSION') || version_compare( '1.30.0', JOB_MANAGER_VERSION, '>' ) ) {
			unset( $this->settings['resume_submission'][1]['recaptcha'] );
		}
		if ( ! class_exists( 'WP_Job_Manager_Applications' ) ) {
			unset( $this->settings['resume_application'][1][1] );
		}
	}
}
