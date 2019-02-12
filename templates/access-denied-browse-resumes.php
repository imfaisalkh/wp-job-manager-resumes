<?php
/**
 * Access denied message when attempting to browse resumes.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-resumes/access-denied-browse-resumes.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     WP Job Manager - Resume Manager
 * @category    Template
 * @version     1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<p class="job-manager-error"><?php _e( 'Sorry, you do not have permission to browse resumes.', 'wp-job-manager-resumes' ); ?></p>
