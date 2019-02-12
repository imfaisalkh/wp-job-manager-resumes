<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Resume_Manager_Setup class.
 */
class WP_Resume_Manager_Setup {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 12 );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_init', array( $this, 'redirect' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 12 );
	}

	/**
	 * admin_menu function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_menu() {
		add_dashboard_page( __( 'Setup', 'wp-job-manager-resumes' ), __( 'Setup', 'wp-job-manager-resumes' ), 'manage_options', 'resume-manager-setup', array( $this, 'output' ) );
	}

	/**
	 * Add styles just for this page, and remove dashboard page links.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_head() {
		remove_submenu_page( 'index.php', 'resume-manager-setup' );
	}

	/**
	 * Sends user to the setup page on first activation
	 */
	public function redirect() {
		// Bail if no activation redirect transient is set
	    if ( ! get_transient( '_resume_manager_activation_redirect' ) ) {
			return;
	    }

	    if ( ! current_user_can( 'manage_options' ) ) {
	    	return;
	    }

		// Delete the redirect transient
		delete_transient( '_resume_manager_activation_redirect' );

		// Bail if activating from network, or bulk, or within an iFrame
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) || defined( 'IFRAME_REQUEST' ) ) {
			return;
		}

		if ( ( isset( $_GET['action'] ) && 'upgrade-plugin' == $_GET['action'] ) && ( isset( $_GET['plugin'] ) && strstr( $_GET['plugin'], 'wp-job-manager-resumes.php' ) ) ) {
			return;
		}

		wp_redirect( admin_url( 'index.php?page=resume-manager-setup' ) );
		exit;
	}

	/**
	 * Enqueue scripts for setup page
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_style( 'resume_manager_setup_css', RESUME_MANAGER_PLUGIN_URL . '/assets/css/setup.css', array( 'dashicons' ) );
	}

	/**
	 * Create a page.
	 * @param  string $title
	 * @param  string $content
	 * @param  string $option
	 */
	public function create_page( $title, $content, $option ) {
		$page_data = array(
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_author'    => 1,
			'post_name'      => sanitize_title( $title ),
			'post_title'     => $title,
			'post_content'   => $content,
			'post_parent'    => 0,
			'comment_status' => 'closed'
		);
		$page_id = wp_insert_post( $page_data );

		if ( $option ) {
			update_option( $option, $page_id );
		}
	}

	/**
	 * Output addons page
	 */
	public function output() {
		$step = ! empty( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;

		if ( 3 === $step && ! empty( $_POST ) ) {
			$create_pages    = isset( $_POST['wp-resume-manager-create-page'] ) ? $_POST['wp-resume-manager-create-page'] : array();
			$page_titles     = $_POST['wp-resume-manager-page-title'];
			$pages_to_create = array(
				'submit_resume_form'  => '[submit_resume_form]',
				'candidate_dashboard' => '[candidate_dashboard]',
				'resumes'             => '[resumes]'
			);

			foreach ( $pages_to_create as $page => $content ) {
				if ( ! isset( $create_pages[ $page ] ) || empty( $page_titles[ $page ] ) ) {
					continue;
				}
				$this->create_page( sanitize_text_field( $page_titles[ $page ] ), $content, 'resume_manager_' . $page . '_page_id' );
			}
		}
		?>
		<div class="wrap wp_job_manager wp_job_manager_addons_wrap">
			<h2><?php _e( 'Resume Manager Setup', 'wp-job-manager-resumes' ); ?></h2>

			<ul class="wp-resume-manager-setup-steps">
				<li class="<?php if ( $step === 1 ) echo 'wp-resume-manager-setup-active-step'; ?>"><?php _e( '1. Introduction', 'wp-job-manager-resumes' ); ?></li>
				<li class="<?php if ( $step === 2 ) echo 'wp-resume-manager-setup-active-step'; ?>"><?php _e( '2. Page Setup', 'wp-job-manager-resumes' ); ?></li>
				<li class="<?php if ( $step === 3 ) echo 'wp-resume-manager-setup-active-step'; ?>"><?php _e( '3. Done', 'wp-job-manager-resumes' ); ?></li>
			</ul>

			<?php if ( 1 === $step ) : ?>

				<h3><?php _e( 'Setup Wizard Introduction', 'wp-job-manager-resumes' ); ?></h3>

				<p><?php _e( 'Thanks for installing <em>Resume Manager</em>!', 'wp-job-manager-resumes' ); ?></p>
				<p><?php _e( 'This setup wizard will help you get started by creating the pages for resume submission, resume management, and resume listing.', 'wp-job-manager-resumes' ); ?></p>
				<p><?php printf( __( 'If you want to skip the wizard and setup the pages and shortcodes yourself manually, the process is still reletively simple. Refer to the %sdocumentation%s for help.', 'wp-job-manager-resumes' ), '<a href=https://wpjobmanager.com/documentation/add-ons/resume-manager/">', '</a>' ); ?></p>

				<p class="submit">
					<a href="<?php echo esc_url( add_query_arg( 'step', 2 ) ); ?>" class="button button-primary"><?php _e( 'Continue to page setup', 'wp-job-manager-resumes' ); ?></a>
					<a href="<?php echo esc_url( add_query_arg( 'skip-resume-manager-setup', 1, admin_url( 'index.php?page=resume-manager-setup&step=3' ) ) ); ?>" class="button"><?php _e( 'Skip setup. I will setup the plugin manually', 'wp-job-manager-resumes' ); ?></a>
				</p>

			<?php endif; ?>
			<?php if ( 2 === $step ) : ?>

				<h3><?php _e( 'Page Setup', 'wp-job-manager-resumes' ); ?></h3>

				<p><?php printf( __( '<em>Resume Manager</em> includes %1$sshortcodes%2$s which can be used within your %3$spages%2$s to output content. These can be created for you below. For more information on the resume shortcodes view the %4$sshortcode documentation%2$s.', 'wp-job-manager-resumes' ), '<a href="http://codex.wordpress.org/Shortcode" title="What is a shortcode?" target="_blank" class="help-page-link">', '</a>', '<a href="http://codex.wordpress.org/Pages" target="_blank" class="help-page-link">', '<a href="https://wpjobmanager.com/document/resume-manager/#section-4" target="_blank" class="help-page-link">' ); ?></p>

				<form action="<?php echo esc_url( add_query_arg( 'step', 3 ) ); ?>" method="post">
					<table class="wp-resume-manager-shortcodes widefat">
						<thead>
							<tr>
								<th>&nbsp;</th>
								<th><?php _e( 'Page Title', 'wp-job-manager-resumes' ); ?></th>
								<th><?php _e( 'Page Description', 'wp-job-manager-resumes' ); ?></th>
								<th><?php _e( 'Content Shortcode', 'wp-job-manager-resumes' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><input type="checkbox" checked="checked" name="wp-resume-manager-create-page[submit_resume_form]" /></td>
								<td><input type="text" value="<?php echo esc_attr( _x( 'Submit Resume', 'Default page title (wizard)', 'wp-job-manager-resumes' ) ); ?>" name="wp-resume-manager-page-title[submit_resume_form]" /></td>
								<td>
									<p><?php _e( 'This page allows candidates to post their resume to your website from the front-end.', 'wp-job-manager-resumes' ); ?></p>

									<p><?php _e( 'If you do not want to accept submissions from users in this way (for example you just want to post resumes from the admin dashboard) you can skip creating this page.', 'wp-job-manager-resumes' ); ?></p>
								</td>
								<td><code>[submit_resume_form]</code></td>
							</tr>
							<tr>
								<td><input type="checkbox" checked="checked" name="wp-resume-manager-create-page[candidate_dashboard]" /></td>
								<td><input type="text" value="<?php echo esc_attr( _x( 'Candidate Dashboard', 'Default page title (wizard)', 'wp-job-manager-resumes' ) ); ?>" name="wp-resume-manager-page-title[candidate_dashboard]" /></td>
								<td>
									<p><?php _e( 'This page allows candidates to manage and edit their own resumes from the front-end.', 'wp-job-manager-resumes' ); ?></p>

									<p><?php _e( 'If you plan on managing all listings from the admin dashboard you can skip creating this page.', 'wp-job-manager-resumes' ); ?></p>
								</td>
								<td><code>[candidate_dashboard]</code></td>
							</tr>
							<tr>
								<td><input type="checkbox" checked="checked" name="wp-resume-manager-create-page[resumes]" /></td>
								<td><input type="text" value="<?php echo esc_attr( _x( 'Resumes', 'Default page title (wizard)', 'wp-job-manager-resumes' ) ); ?>" name="wp-resume-manager-page-title[resumes]" /></td>
								<td><?php _e( 'This page allows users to browse, search, and filter resume listings on the front-end of your site.', 'wp-job-manager-resumes' ); ?></td>
								<td><code>[resumes]</code></td>
							</tr>
						</tbody>
						<tfoot>
							<tr>
								<th colspan="4">
									<input type="submit" class="button button-primary" value="Create selected pages" />
									<a href="<?php echo esc_url( add_query_arg( 'step', 3 ) ); ?>" class="button"><?php _e( 'Skip this step', 'wp-job-manager-resumes' ); ?></a>
								</th>
							</tr>
						</tfoot>
					</table>
				</form>

			<?php endif; ?>
			<?php if ( 3 === $step ) : ?>

				<h3><?php _e( 'All Done!', 'wp-job-manager-resumes' ); ?></h3>

				<p><?php _e( 'Looks like you\'re all set to start using the plugin. In case you\'re wondering where to go next:', 'wp-job-manager-resumes' ); ?></p>

				<ul class="wp-resume-manager-next-steps">
					<li><a href="<?php echo admin_url( 'edit.php?post_type=resume&page=resume-manager-settings' ); ?>"><?php _e( 'Tweak the plugin settings', 'wp-job-manager-resumes' ); ?></a></li>
					<li><a href="<?php echo admin_url( 'post-new.php?post_type=resume' ); ?>"><?php _e( 'Add a resume via the back-end', 'wp-job-manager-resumes' ); ?></a></li>

					<?php if ( $permalink = resume_manager_get_permalink( 'submit_resume_form' ) ) : ?>
						<li><a href="<?php echo esc_url( $permalink ); ?>"><?php _e( 'Add a resume via the front-end', 'wp-job-manager-resumes' ); ?></a></li>
					<?php endif; ?>

					<?php if ( $permalink = resume_manager_get_permalink( 'resumes' ) ) : ?>
						<li><a href="<?php echo esc_url( $permalink ); ?>"><?php _e( 'View submitted job listings', 'wp-job-manager-resumes' ); ?></a></li>
					<?php endif; ?>

					<?php if ( $permalink = resume_manager_get_permalink( 'candidate_dashboard' ) ) : ?>
						<li><a href="<?php echo esc_url( $permalink ); ?>"><?php _e( 'View the candidate dashboard', 'wp-job-manager-resumes' ); ?></a></li>
					<?php endif; ?>
				</ul>

				<p><?php printf( __( 'And don\'t forget, if you need any more help using <em>Resume Manager</em> you can consult the %1$sdocumentation%2$s or %3$scontact us via our support area%2$s!', 'wp-job-manager-resumes' ), '<a href="https://wpjobmanager.com/document/resume-manager/">', '</a>', '<a href="https://wpjobmanager.com/support/">' ); ?></p>

				<div class="wp-resume-manager-support-the-plugin">
					<h3><?php _e( 'Support the Ongoing Development of WP Job Manager', 'wp-job-manager-resumes' ); ?></h3>
					<p><?php _e( 'There are many ways to support open-source projects such as WP Job Manager, for example code contribution, translation, or even telling your friends how awesome the plugin (hopefully) is. Thanks in advance for your support - it is much appreciated!', 'wp-job-manager-resumes' ); ?></p>
					<ul>
						<li class="icon-review"><a href="https://wordpress.org/support/view/plugin-reviews/wp-job-manager#postform"><?php _e( 'Leave a positive review', 'wp-job-manager-resumes' ); ?></a></li>
						<li class="icon-localization"><a href="https://www.transifex.com/projects/p/wp-job-manager/"><?php _e( 'Contribute a localization', 'wp-job-manager-resumes' ); ?></a></li>
						<li class="icon-code"><a href="https://github.com/mikejolley/WP-Job-Manager"><?php _e( 'Contribute code or report a bug', 'wp-job-manager-resumes' ); ?></a></li>
						<li class="icon-forum"><a href="https://wordpress.org/support/plugin/wp-job-manager"><?php _e( 'Help other users on the forums', 'wp-job-manager-resumes' ); ?></a></li>
					</ul>
				</div>

			<?php endif; ?>
		</div>
		<?php
	}
}

new WP_Resume_Manager_Setup();