<?php
/**
 * Displays contact details when viewing a single resume.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-resumes/contact-details.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     WP Job Manager - Resume Manager
 * @category    Template
 * @version     1.13.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $resume_preview;

if ( $resume_preview ) {
	return;
}

if ( resume_manager_user_can_view_contact_details( $post->ID ) ) :
	wp_enqueue_script( 'wp-resume-manager-resume-contact-details' );
	?>
	<div class="resume_contact">
		<input class="resume_contact_button" type="button" value="<?php _e( 'Contact', 'wp-job-manager-resumes' ); ?>" />

		<div class="resume_contact_details">
			<?php do_action( 'resume_manager_contact_details' ); ?>
		</div>
	</div>
<?php else : ?>

	<?php get_job_manager_template_part( 'access-denied', 'contact-details', 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' ); ?>

<?php endif; ?>
