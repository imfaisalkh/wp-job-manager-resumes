<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WP_Resume_Manager_Admin class.
 */
class WP_Resume_Manager_Admin {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		include_once( 'class-wp-resume-manager-cpt.php' );
		include_once( 'class-wp-resume-manager-writepanels.php' );
		include_once( 'class-wp-resume-manager-settings.php' );
		include_once( 'class-wp-resume-manager-setup.php' );

		add_action( 'job_manager_admin_screen_ids', array( $this, 'add_screen_ids' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 12 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 20 );

		$this->settings_page = new WP_Resume_Manager_Settings();
	}

	/**
	 * Add screen ids
	 * @param array $screen_ids
	 * @return  array
	 */
	public function add_screen_ids( $screen_ids ) {
		$screen_ids[] = 'edit-resume';
		$screen_ids[] = 'resume';
		return $screen_ids;
	}

	/**
	 * admin_enqueue_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		global $wp_scripts;

		$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';

		wp_enqueue_style( 'jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/' . $jquery_version . '/themes/smoothness/jquery-ui.css' );
		wp_enqueue_style( 'resume_manager_admin_css', RESUME_MANAGER_PLUGIN_URL . '/assets/css/admin.css' );
		wp_register_script( 'jquery-tiptip', JOB_MANAGER_PLUGIN_URL. '/assets/js/jquery-tiptip/jquery.tipTip.min.js', array( 'jquery' ), JOB_MANAGER_VERSION, true );
		wp_enqueue_script( 'resume_manager_admin_js', RESUME_MANAGER_PLUGIN_URL. '/assets/js/admin.min.js', array( 'jquery', 'jquery-tiptip', 'jquery-ui-datepicker', 'jquery-ui-sortable' ), RESUME_MANAGER_VERSION, true );
	}

	/**
	 * admin_menu function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_menu() {
		add_submenu_page( 'edit.php?post_type=resume', __( 'Settings', 'wp-job-manager-resumes' ), __( 'Settings', 'wp-job-manager-resumes' ), 'manage_options', 'resume-manager-settings', array( $this->settings_page, 'output' ) );
	}
}

new WP_Resume_Manager_Admin();