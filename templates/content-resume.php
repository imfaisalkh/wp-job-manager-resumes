<?php
/**
 * Template for resume content inside a list of resumes.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-resumes/content-resume.php.
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

$category = get_the_resume_category(); ?>
<li <?php resume_class(); ?>>
	<a href="<?php the_resume_permalink(); ?>">
		<?php the_candidate_photo(); ?>
		<div class="candidate-column">
			<h3><?php the_title(); ?></h3>
			<div class="candidate-title">
				<?php the_candidate_title( '<strong>', '</strong> ' ); ?>
			</div>
		</div>
		<div class="candidate-location-column">
			<?php the_candidate_location( false ); ?>
		</div>
		<div class="resume-posted-column <?php if ( $category ) : ?>resume-meta<?php endif; ?>">
			<date><?php printf( __( '%s ago', 'wp-job-manager-resumes' ), human_time_diff( get_post_time( 'U' ), current_time( 'timestamp' ) ) ); ?></date>

			<?php if ( $category ) : ?>
				<div class="resume-category">
					<?php echo $category ?>
				</div>
			<?php endif; ?>
		</div>
	</a>
</li>
