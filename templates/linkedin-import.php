<?php
/**
 * Template that enables the importing of resume data from LinkedIn.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-resumes/linkedin-import.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     WP Job Manager - Resume Manager
 * @category    Template
 * @version     1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<script src="//platform.linkedin.com/in.js" type="text/javascript">
	api_key: <?php echo esc_js( get_option( 'job_manager_linkedin_api_key' ) ); ?>
</script>

<fieldset class="import-from-linkedin">
	<label><?php _e( 'LinkedIn', 'wp-job-manager-resumes' ); ?></label>
	<div class="field">
		<input class="import-from-linkedin" type="button" value="<?php _e( 'Import from LinkedIn', 'wp-job-manager-resumes' ); ?>" />
	</div>
</fieldset>
