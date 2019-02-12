<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

wp_clear_scheduled_hook( 'resume_manager_check_for_expired_resumes' );

wp_trash_post( get_option( 'resume_manager_submit_resume_form_page_id' ) );

$options = array(
	'wp_resume_manager_version',
	'resume_manager_submit_resume_form_page_id',
	'resume_manager_per_page',
	'resume_manager_enable_categories',
	'resume_manager_enable_skills',
	'resume_manager_enable_resume_upload',
	'resume_manager_enable_application',
	'resume_manager_force_resume',
	'resume_manager_force_application',
	'resume_manager_autohide',
	'resume_manager_user_requires_account',
	'resume_manager_enable_registration',
	'resume_manager_registration_role',
	'resume_manager_submission_requires_approval',
	'resume_manager_submission_duration',
	'resume_manager_linkedin_import',
	'job_manager_linkedin_api_key',
	'resume_manager_browse_resume_capability',
	'resume_manager_view_resume_capability',
	'resume_manager_contact_resume_capability',
	'resume_manager_submit_page_slug',
	'resume_manager_generate_username_from_email'
);

foreach ( $options as $option ) {
	delete_option( $option );
}
