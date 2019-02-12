<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Job_Manager_Widget' ) ) {
	return;
}

/**
 * Recent Resumes Widget
 */
class WP_Resume_Manager_Widget_Recent_Resumes extends WP_Job_Manager_Widget {

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wp_post_types;

		$this->widget_cssclass    = 'job_manager widget_recent_resumes';
		$this->widget_description = __( 'Display a list of recent listings on your site, optionally matching a keyword and location.', 'wp-job-manager-resumes' );
		$this->widget_id          = 'widget_recent_resumes';
		$this->widget_name        = sprintf( __( 'Recent %s', 'wp-job-manager-resumes' ), $wp_post_types['resume']->labels->name );
		$this->settings           = array(
			'title' => array(
				'type'  => 'text',
				'std'   => sprintf( __( 'Recent %s', 'wp-job-manager-resumes' ), $wp_post_types['resume']->labels->name ),
				'label' => __( 'Title', 'wp-job-manager-resumes' )
			),
			'keyword' => array(
				'type'  => 'text',
				'std'   => '',
				'label' => __( 'Keyword', 'wp-job-manager-resumes' )
			),
			'location' => array(
				'type'  => 'text',
				'std'   => '',
				'label' => __( 'Location', 'wp-job-manager-resumes' )
			),
			'number' => array(
				'type'  => 'number',
				'step'  => 1,
				'min'   => 1,
				'max'   => '',
				'std'   => 10,
				'label' => __( 'Number of listings to show', 'wp-job-manager-resumes' )
			)
		);
		$this->register();
	}

	/**
	 * widget function.
	 *
	 * @see WP_Widget
	 * @access public
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		if ( ! resume_manager_user_can_browse_resumes() ) {
			return;
		}

		if ( $this->get_cached_widget( $args ) ) {
			return;
		}

		ob_start();

		extract( $args );

		$title   = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		$number  = absint( $instance['number'] );
		$resumes = get_resumes( array(
			'search_location'   => isset( $instance['location'] ) ? $instance['location'] : '',
			'search_keywords'   => isset( $instance['keyword'] ) ? $instance['keyword'] : '',
			'posts_per_page'    => $number,
			'orderby'           => 'date',
			'order'             => 'DESC',
		) );

		if ( $resumes->have_posts() ) : ?>

			<?php echo $before_widget; ?>

			<?php if ( $title ) echo $before_title . $title . $after_title; ?>

			<ul class="resumes">

				<?php while ( $resumes->have_posts() ) : $resumes->the_post(); ?>

					<?php get_job_manager_template_part( 'content-widget', 'resume', 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' ); ?>

				<?php endwhile; ?>

			</ul>

			<?php echo $after_widget; ?>

		<?php else : ?>

			<?php get_job_manager_template_part( 'content-widget', 'no-resumes-found', 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' ); ?>

		<?php endif;

		wp_reset_postdata();

		$content = ob_get_clean();

		echo $content;

		$this->cache_widget( $args, $content );
	}
}

/**
 * Featured Resumes Widget
 */
class WP_Resume_Manager_Widget_Featured_Resumes extends WP_Job_Manager_Widget {

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wp_post_types;

		$this->widget_cssclass    = 'job_manager widget_featured_resumes';
		$this->widget_description = __( 'Display a list of featured listings on your site.', 'wp-job-manager-resumes' );
		$this->widget_id          = 'widget_featured_resumes';
		$this->widget_name        = sprintf( __( 'Featured %s', 'wp-job-manager-resumes' ), $wp_post_types['resume']->labels->name );
		$this->settings           = array(
			'title' => array(
				'type'  => 'text',
				'std'   => sprintf( __( 'Featured %s', 'wp-job-manager-resumes' ), $wp_post_types['resume']->labels->name ),
				'label' => __( 'Title', 'wp-job-manager-resumes' )
			),
			'number' => array(
				'type'  => 'number',
				'step'  => 1,
				'min'   => 1,
				'max'   => '',
				'std'   => 10,
				'label' => __( 'Number of listings to show', 'wp-job-manager-resumes' )
			)
		);
		$this->register();
	}

	/**
	 * widget function.
	 *
	 * @see WP_Widget
	 * @access public
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		if ( ! resume_manager_user_can_browse_resumes() ) {
			return;
		}

		if ( $this->get_cached_widget( $args ) ) {
			return;
		}

		ob_start();

		extract( $args );

		$title   = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		$number  = absint( $instance['number'] );
		$resumes = get_resumes( array(
			'posts_per_page' => $number,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'featured'       => true
		) );

		if ( $resumes->have_posts() ) : ?>

			<?php echo $before_widget; ?>

			<?php if ( $title ) echo $before_title . $title . $after_title; ?>

			<ul class="resumes">

				<?php while ( $resumes->have_posts() ) : $resumes->the_post(); ?>

					<?php get_job_manager_template_part( 'content-widget', 'resume', 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' ); ?>

				<?php endwhile; ?>

			</ul>

			<?php echo $after_widget; ?>

		<?php else : ?>

			<?php get_job_manager_template_part( 'content-widget', 'no-resumes-found', 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' ); ?>

		<?php endif;

		wp_reset_postdata();

		$content = ob_get_clean();

		echo $content;

		$this->cache_widget( $args, $content );
	}
}

register_widget( 'WP_Resume_Manager_Widget_Recent_Resumes' );
register_widget( 'WP_Resume_Manager_Widget_Featured_Resumes' );
