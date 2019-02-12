<?php
/**
 * Message to display when a resume has been submitted.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-resumes/resume-submitted.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     WP Job Manager - Resume Manager
 * @category    Template
 * @version     1.15.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

switch ( $resume->post_status ) :
	case 'publish' :
		if ( resume_manager_user_can_view_resume( $resume->ID ) ) {
			printf( '<p class="resume-submitted">' . __( 'Your resume has been submitted successfully. To view your resume <a href="%s">click here</a>.', 'wp-job-manager-resumes' ) . '</p>', get_permalink( $resume->ID ) );
		} else {
			print( '<p class="resume-submitted">' . __( 'Your resume has been submitted successfully.', 'wp-job-manager-resumes' ) . '</p>' );
		}
	break;
	case 'pending' :
		print( '<p class="resume-submitted">' . __( 'Your resume has been submitted successfully and is pending approval.', 'wp-job-manager-resumes' ) . '</p>' );
	break;
	default :
		do_action( 'resume_manager_resume_submitted_content_' . str_replace( '-', '_', sanitize_title( $resume->post_status ) ), $resume );
	break;
endswitch;
