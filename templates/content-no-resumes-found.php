<?php
/**
 * Message to show when no resumes are found.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-resumes/content-no-resumes-found.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     WP Job Manager - Resume Manager
 * @category    Template
 * @version     1.10.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'DOING_AJAX' ) ) : ?>
	<li class="no_resumes_found"><?php _e( 'There are no listings matching your search.', 'wp-job-manager' ); ?></li>
<?php else : ?>
	<p class="no_resumes_found"><?php _e( 'There are currently no resumes.', 'wp-job-manager' ); ?></p>
<?php endif; ?>
