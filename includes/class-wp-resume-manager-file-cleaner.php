<?php
/**
 * Defines a class to handle cleaning up files when Resumes are deleted.
 *
 * @package wp-resume-manager
 * @since 1.17.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles the Resume file deletion.
 */
class WP_Resume_Manager_File_Cleaner {
	/**
	 * Initialize the hooks for file cleaning.
	 */
	public static function init() {
		add_action( 'before_delete_post', array( __CLASS__, 'handle_resume_deletion' ) );
	}

	/**
	 * Handle the deletion of a Resume post and delete its files if needed. This
	 * function is used in the `before_delete_post` hook and only works with
	 * posts of type `resume`.
	 *
	 * @param int $resume_id
	 */
	public static function handle_resume_deletion( $resume_id ) {
		if ( 'resume' !== get_post_type( $resume_id ) || ! self::should_delete_files() ) {
			return;
		}

		if ( resume_manager_attach_uploaded_files() ) {
			self::delete_attachments( $resume_id );
		} else {
			self::delete_files_from_fields( $resume_id );
		}
	}

	/**
	 * Whether we should be deleting files along with Resumes.
	 *
	 * @return bool
	 */
	private static function should_delete_files() {
		return get_option( 'resume_manager_delete_files_on_resume_deletion' );
	}

	/**
	 * Delete all attachments from the given Resume.
	 *
	 * @param int $resume_id
	 */
	private static function delete_attachments( $resume_id ) {
		$attachments = get_attached_media( '', $resume_id );
		foreach ( $attachments as $attachment ) {
			wp_delete_attachment( $attachment->ID, true );
		}
	}

	/**
	 * Delete files based on the form fields of type "file".
	 *
	 * @param int $resume_id
	 */
	private static function delete_files_from_fields( $resume_id ) {
		$file_fields = self::get_file_fields( $resume_id );

		foreach ( $file_fields as $key => $field_config ) {
			$meta_key  = "_$key";
			$file_url  = get_post_meta( $resume_id, $meta_key, true );
			$file_path = $file_url ? self::get_filepath_for_upload( $file_url ) : null;

			if ( $file_path ) {
				unlink( $file_path );

				// Look for other sizes.
				$path_parts     = pathinfo( $file_path );
				$file_path_glob = str_replace( '.' . $path_parts['extension'], '-[0-9]*x[0-9]*.' . $path_parts['extension'], $file_path );
				$resized_files  = glob( $file_path_glob );
				foreach ( $resized_files as $resized_file ) {
					unlink( $resized_file );
				}
			}
		}
	}

	/**
	 * Gets the file fields.
	 *
	 * @param int $resume_id
	 * @return array
	 */
	private static function get_file_fields( $resume_id ) {
		include_once JOB_MANAGER_PLUGIN_DIR . '/includes/abstracts/abstract-wp-job-manager-form.php';
		include_once RESUME_MANAGER_PLUGIN_DIR . '/includes/forms/class-wp-resume-manager-form-submit-resume.php';

		$fields_raw = WP_Resume_Manager_Form_Submit_Resume::get_resume_fields();
		$fields     = array();
		foreach ( $fields_raw as $key => $field_config ) {
			if ( 'file' !== $field_config['type'] ) {
				continue;
			}

			$fields[ $key ] = $field_config;
		}

		/**
		 * Allows filtering on what fields should be considered "file" fields
		 * for cleanup on delete.
		 *
		 * @since 1.17.1
		 *
		 * @param $fields    array List of fields (key => config).
		 * @param $resume_id int   Resume ID.
		 */
		return apply_filters( 'resume_manager_file_fields_to_cleanup', $fields, $resume_id );
	}

	/**
	 * Given a URL for an uploaded file, try to determine the file path.
	 *
	 * @param string $upload_url The URL of the uploaded file.
	 *
	 * @return string|null The path of the uploaded file, or null if the URL
	 *                     does not point to a file in the uploads directory.
	 */
	private static function get_filepath_for_upload( $upload_url ) {
		$wp_upload_dir = wp_upload_dir();

		$file_path = str_replace(
			array( $wp_upload_dir['baseurl'], $wp_upload_dir['url'] ),
			array( $wp_upload_dir['basedir'], $wp_upload_dir['path'] ),
			$upload_url
		);

		$found_path    = $file_path !== $upload_url;
		$in_upload_dir = 0 === strpos( realpath( $file_path), realpath( $wp_upload_dir['basedir'] ) );

		if ( ! $found_path || ! $in_upload_dir ) {
			// We were unable to determine a valid path for the URL.
			return null;
		}

		return $file_path;
	}
}
