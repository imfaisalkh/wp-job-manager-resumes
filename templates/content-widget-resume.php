<?php
/**
 * Content for a single resume widget.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-resumes/content-widget-resume.php.
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
?>
<li <?php resume_class(); ?>>
	<a href="<?php the_resume_permalink(); ?>">
		<div class="candidate">
			<h3><?php the_title(); ?></h3>
		</div>
		<ul class="meta">
			<li class="candidate-title"><?php the_candidate_title(); ?></li>
			<li class="candidate-location"><?php the_candidate_location( false ); ?></li>
		</ul>
	</a>
</li>
