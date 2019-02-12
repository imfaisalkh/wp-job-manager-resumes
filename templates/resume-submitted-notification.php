<?php
/**
 * This is the email notification sent to admin when a resume is submitted.
 * Note: This is in plain text format
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-resumes/resume-submitted-notification.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     WP Job Manager - Resume Manager
 * @category    Template
 * @version     1.15.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$message             = array();
$message['greeting'] = __( 'Hello', 'wp-job-manager-resumes' ) . "\n" . "\n";
$message['intro']    = sprintf( __( 'A new resume has just been submitted by *%s*. The details of their resume are as follows:', 'wp-job-manager-resumes' ), $resume->post_title ) . "\n" . "\n";

// Get admin custom fields and loop through
foreach ( $custom_fields as $meta_key => $field ) {
	if ( ( $meta_value = get_post_meta( $resume_id, $meta_key, true ) ) && ! empty( $meta_value ) && is_string( $meta_value ) ) {
		$message_line = ' - ' . sprintf( '%s: %s', $field['label'], esc_html( $meta_value ) ) . "\n";
	} else {
		$message_line = '';
	}
	$message[] = apply_filters( 'resume_manager_new_resume_notification_meta_row', $message_line, $meta_key, $field );
}

// Show Resume Content
$message['content_intro'] = "\n" . __( 'The content of their resume is as follows:', 'wp-job-manager-resumes' ) . "\n" . "\n";
$message['content']       = strip_tags( $resume->post_content ) . "\n" . "\n" . '-----------' . "\n" . "\n";

// Output Links
if ( $items = get_post_meta( $resume_id, '_links', true ) ) {
	$message['link_start'] = __( 'Links:', 'wp-job-manager-resumes' ) . "\n" . "\n";
	foreach ( $items as $key => $item ) {
		$message[ 'link_' . $key ] = $item['name'] . ': ' . $item['url'] . "\n";
	}
	$message['link_end'] = "\n" . '-----------' . "\n" . "\n";
}

// Education
if ( $items = get_post_meta( $resume_id, '_candidate_education', true ) ) {
	$message['education_start'] = __( 'Education:', 'wp-job-manager-resumes' ) . "\n" . "\n";
	foreach ( $items as $key => $item ) {
		$message[ 'education_location_' . $key ]      = sprintf( __( 'Location: %s', 'wp-job-manager-resumes' ), $item['location'] ) . "\n";
		$message[ 'education_date_' . $key ]          = sprintf( __( 'Date: %s', 'wp-job-manager-resumes' ), $item['date'] ) . "\n";
		$message[ 'education_qualification_' . $key ] = sprintf( __( 'Qualification: %s', 'wp-job-manager-resumes' ), $item['qualification'] ) . "\n";
		$message[ 'education_notes_' . $key ]         = $item['notes'] . "\n" . "\n";
	}
	$message['education_end'] = '-----------' . "\n" . "\n";
}

// Experience
if ( $items = get_post_meta( $resume_id, '_candidate_experience', true ) ) {
	$message['experience_start'] = __( 'Experience:', 'wp-job-manager-resumes' ) . "\n" . "\n";
	foreach ( $items as $key => $item ) {
		$message[ 'experience_employer_' . $key ] = sprintf( __( 'Employer: %s', 'wp-job-manager-resumes' ), $item['employer'] ) . "\n";
		$message[ 'experience_location_' . $key ] = sprintf( __( 'Date: %s', 'wp-job-manager-resumes' ), $item['date'] ) . "\n";
		$message[ 'experience_title_' . $key ]    = sprintf( __( 'Job Title: %s', 'wp-job-manager-resumes' ), $item['job_title'] ) . "\n";
		$message[ 'experience_notes_' . $key ]    = $item['notes'] . "\n" . "\n";
	}
	$message['experience_end'] = '-----------' . "\n" . "\n";
}

$message['view_resume_link']       = sprintf( __( 'You can view this resume here: %s', 'wp-job-manager-resumes' ), get_permalink( $resume_id ) ) . "\n";
$message['admin_view_resume_link'] = sprintf( __( 'You can view/edit this resume in the backend by clicking here: %s', 'wp-job-manager-resumes' ), admin_url( 'post.php?post=' . $resume_id . '&action=edit' ) ) . "\n" . "\n";

echo implode( "", apply_filters( 'resume_manager_new_resume_notification_meta', $message, $resume_id, $resume ) );
