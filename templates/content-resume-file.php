<?php
/**
 * Display a link to a file inside the resume content of a resume list.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-resumes/content-resume-file.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     WP Job Manager - Resume Manager
 * @category    Template
 * @version     1.15.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( $resume_files = get_resume_files() ) && apply_filters( 'resume_manager_user_can_download_resume_file', true, $post->ID ) ) : ?>
	<?php foreach ( $resume_files as $key => $resume_file ) : ?>
		<li class="resume-file resume-file-<?php echo substr( strrchr( $resume_file, '.' ), 1 ); ?>">
			<a rel="nofollow" target="_blank" href="<?php echo esc_url( get_resume_file_download_url( null, $key ) ); ?>"><?php echo basename( $resume_file ); ?></a>
		</li>
	<?php endforeach; ?>
<?php endif; ?>
