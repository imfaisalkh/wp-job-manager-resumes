<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Resume_Manager_Email_Notification class.
 */
class WP_Resume_Manager_Email_Notification {

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( get_option( 'resume_manager_submission_notification' ) ) {
			add_action( 'resume_manager_resume_submitted', array( $this, 'new_resume_submitted' ) );
		}
	}

	/**
	 * New resume notification
	 */
	public function new_resume_submitted( $resume_id ) {
		include_once( 'admin/class-wp-resume-manager-writepanels.php' );

		$custom_fields = array_diff_key( WP_Resume_Manager_Writepanels::resume_fields(), array( '_resume_file' => '', '_resume_expires' => '' ) );
		$resume        = get_post( $resume_id );
		$recipient     = get_option( 'resume_manager_email_notifications' );
		$recipient     = ! empty( $recipient ) ? $recipient : get_option( 'admin_email' );
		$subject       = sprintf( __( 'New Resume Submission From %s', 'wp-job-manager-resumes' ), $resume->post_title );
		$attachments   = array();
		$files         = get_resume_attachments( $resume );

		$attachments = $files[ 'attachments' ];

		ob_start();
		get_job_manager_template( 'resume-submitted-notification.php', array(
			'resume'        => $resume,
			'resume_id'     => $resume_id,
			'custom_fields' => $custom_fields
		), 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' );
		$message = ob_get_clean();

		add_filter( 'wp_mail_from', array( __CLASS__, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( __CLASS__, 'get_from_name' ) );

		wp_mail(
			apply_filters( 'resume_manager_new_resume_notification_recipient', $recipient, $resume_id ),
			apply_filters( 'resume_manager_new_resume_notification_subject', $subject, $resume_id ),
			$message,
			apply_filters( 'resume_manager_new_resume_notification_headers', '', $resume_id ),
			apply_filters( 'resume_manager_new_resume_notification_attachments', array_filter( $attachments ), $resume_id )
		);

		remove_filter( 'wp_mail_from', array( __CLASS__, 'get_from_address' ) );
		remove_filter( 'wp_mail_from_name', array( __CLASS__, 'get_from_name' ) );
	}

	/**
	 * Get from name for email.
	 *
	 * @access public
	 * @return string
	 */
	public static function get_from_name() {
		return wp_specialchars_decode( esc_html( get_bloginfo( 'name' ) ), ENT_QUOTES );
	}

	/**
	 * Get from email address.
	 *
	 * @access public
	 * @return string
	 */
	public static function get_from_address() {
		$site_url  = parse_url( site_url() );
		$nice_host = str_replace( 'www.', '', $site_url['host'] );
		return sanitize_email( 'noreply@' . $nice_host );
	}
}

new WP_Resume_Manager_Email_Notification();
