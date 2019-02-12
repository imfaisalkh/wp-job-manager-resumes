<?php
/**
 * Defines a class to handle the user data export
 *
 * @package wp-resume-manager
 * @since 1.17.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles the user data export.
 *
 * @package
 * @since
 */
class WP_Resume_Manager_Privacy {
	const EXPORT_PER_PAGE = 10;
	const ERASE_PER_PAGE  = 50;

	/**
	 * Sets up initial hooks.
	 *
	 * @static
	 */
	public static function init() {
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_user_data_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_user_data_eraser' ) );
	}

	/**
	 * Register the user data exporter method.
	 *
	 * @param array $exporters The exporter array.
	 * @return array $exporters The exporter array.
	 */
	public static function register_user_data_exporter( $exporters ) {
		$exporters['wp-job-manager-resumes'] = array(
			'exporter_friendly_name' => __( 'WP Job Manager - Resume Manager', 'wp-job-manager-resumes' ),
			'callback'               => array( __CLASS__, 'user_data_exporter' ),
		);
		return $exporters;
	}

	/**
	 * Register the user data eraser method.
	 *
	 * @param array $erasers The eraser array.
	 * @return array $erasers The eraser array.
	 */
	public static function register_user_data_eraser( $erasers ) {
		if ( ! self::is_resume_personal_data_erasure_enabled() ) {
			return $erasers;
		}
		$erasers['wp-job-manager-resumes'] = array(
			'eraser_friendly_name' => __( 'WP Job Manager - Resume Manager', 'wp-job-manager-resumes' ),
			'callback'             => array( __CLASS__, 'user_data_eraser' ),
		);
		return $erasers;
	}

	/**
	 * Data exporter.
	 *
	 * @param string $email_address User email address.
	 * @param int    $page          Page number.
	 * @return array
	 */
	public static function user_data_exporter( $email_address, $page ) {
		$per_page       = self::EXPORT_PER_PAGE;
		$page           = (int) $page;
		$data_to_export = array();

		$resume_ids = self::get_personal_data_post_ids_by_email( $email_address, $per_page, $page );
		foreach ( $resume_ids as $resume_id ) {
			$resume           = get_post( $resume_id );
			$data_to_export[] = array(
				'group_id'    => 'wp_job_manager_resumes',
				'group_label' => __( 'Resumes', 'wp-job-manager-resumes' ),
				'item_id'     => 'resume-' . $resume->ID,
				'data'        => self::get_resume_personal_data( $resume ),
			);
		}

		$done = $per_page > count( $data_to_export );

		return array(
			'data' => $data_to_export,
			'done' => $done,
		);
	}

	/**
	 * Get the personal data from a resume.
	 *
	 * @param WP_Post $resume
	 * @return array
	 */
	public static function get_resume_personal_data( WP_Post $resume ) {
		$personal_data   = array();
		$props_to_export = self::get_personal_data_fields( $resume );

		foreach ( $props_to_export as $field ) {
			if ( ! isset( $field['name'] ) || ! isset( $field['key'] ) ) {
				continue;
			}

			$field = array_merge( array(
				'type'     => 'meta',
				'callback' => false,
			), $field );
			$value = self::get_field_value( $resume, $field );

			/**
			 * Filter the value of a particular field for personal data export.
			 *
			 * @since 1.17.0
			 *
			 * @param mixed   $value  Value to be filtered.
			 * @param string  $field  Field name.
			 * @param WP_Post $resume Post object.
			 */
			$value = apply_filters( 'resume_manager_privacy_export_personal_data_value', $value, $field, $resume );

			if ( $value ) {
				$personal_data[] = array(
					'name'  => $field['name'],
					'value' => $value,
				);
			}
		}

		return $personal_data;
	}

	/**
	 * Get the field value for a personal data export.
	 *
	 * @param WP_Post $post  Post object.
	 * @param array   $field Field configuration.
	 *
	 * @return mixed|string
	 */
	protected static function get_field_value( $post, $field ) {
		$raw_value = self::get_field_raw_value( $post, $field );
		if ( $field['callback'] && is_callable( $field['callback'] ) ) {
			return call_user_func( $field['callback'], $raw_value, $post, $field );
		}
		return esc_html( $raw_value );
	}

	/**
	 * Get the raw value for a field.
	 *
	 * @param WP_Post $post  Post object.
	 * @param array   $field Field configuration.
	 *
	 * @return mixed
	 */
	protected static function get_field_raw_value( $post, $field ) {
		if ( 'raw' === $field['type'] ) {
			if ( ! isset( $field['value'] ) ) {
				$field['value'] = '';
			}
			return $field['value'];
		}
		if ( 'meta' === $field['type'] ) {
			return get_post_meta( $post->ID, '_' . $field['key'], true );
		}
		if ( 'post' === $field['type'] ) {
			return $post->{$field['key']};
		}
		if ( 'taxonomy' === $field['type'] && taxonomy_exists( $field['key'] ) ) {
			$terms = wp_get_post_terms( $post->ID, $field['key'], array( 'fields' => 'names' ) );
			if ( ! is_wp_error( $terms ) ) {
				return $terms;
			}
		}
		return '';
	}

	/**
	 * Export the value from a taxonomy list.
	 *
	 * @param mixed   $raw_value
	 * @param WP_Post $resume
	 * @param array   $field
	 *
	 * @return string
	 */
	public static function export_field_taxonomy( $raw_value, $resume, $field ) {
		return implode( ', ', $raw_value );
	}

	/**
	 * Export the resume file list.
	 *
	 * @param mixed   $raw_value
	 * @param WP_Post $resume
	 * @param array   $field
	 *
	 * @return string
	 */
	public static function export_field_resume_file( $raw_value, $resume, $field ) {
		$value = '';
		$files = array();
		if ( ! empty( $raw_value ) && is_array( $raw_value ) ) {
			foreach ( $raw_value as $key => $resume_file ) {
				$files[] = sprintf( '<a href="%s" target="_blank" rel="noreferrer noopener">%s</a>', esc_url( get_resume_file_download_url( $resume, $key, get_the_resume_permalink( $resume ) ) ), esc_attr( basename( $resume_file ) ) );
			}
			$value = implode( '<br />', $files );
		}
		return $value;
	}

	/**
	 * Export the value from an education field.
	 *
	 * @param mixed   $raw_value
	 * @param WP_Post $resume
	 * @param array   $field
	 *
	 * @return string
	 */
	public static function export_field_education( $raw_value, $resume, $field ) {
		$value     = '';
		$education = array();
		if ( ! empty( $raw_value ) && is_array( $raw_value ) ) {
			foreach ( $raw_value as $item ) {
				$item = array_merge(
					array(
						'location'      => '',
						'date'          => '',
						'qualification' => '',
						'notes'         => '',
					),
					$item
				);

				$education_str_parts = array();
				// translators: The placeholder %s is the location of someone's educational institution on their resume.
				$education_str_parts[] = sprintf( __( 'Location: %s', 'wp-job-manager-resumes' ), esc_html( $item['location'] ) );
				// translators: The placeholder %s is the date (range) someone attended a particular educational institution.
				$education_str_parts[] = sprintf( __( 'Date: %s', 'wp-job-manager-resumes' ), esc_html( $item['date'] ) );
				// translators: The placeholder %s is a list of qualifications someone obtained at a particular education institution.
				$education_str_parts[] = sprintf( __( 'Qualifications: %s', 'wp-job-manager-resumes' ), esc_html( $item['qualification'] ) );
				// translators: The placeholder %s are the notes regarding an education listing on their resume.
				$education_str_parts[] = sprintf( __( 'Notes: %s', 'wp-job-manager-resumes' ), esc_html( $item['notes'] ) );
				$education[]           = implode( '<br />', $education_str_parts );
			}
			$value = implode( '<br /><br />', $education );
		}
		return $value;
	}

	/**
	 * Export the value from a experience field.
	 *
	 * @param mixed   $raw_value
	 * @param WP_Post $resume
	 * @param array   $field
	 *
	 * @return string
	 */
	public static function export_field_experience( $raw_value, $resume, $field ) {
		$value       = '';
		$experiences = array();
		if ( ! empty( $raw_value ) && is_array( $raw_value ) ) {
			foreach ( $raw_value as $item ) {
				$item = array_merge(
					array(
						'employer'  => '',
						'date'      => '',
						'job_title' => '',
						'notes'     => '',
					),
					$item
				);

				$experience_str_parts = array();
				// translators: The placeholder %s is the name of an employer on a resume.
				$experience_str_parts[] = sprintf( __( 'Employer: %s', 'wp-job-manager-resumes' ), esc_html( $item['employer'] ) );
				// translators: The placeholder %s is the date (range) someone was employed by a particular employer.
				$experience_str_parts[] = sprintf( __( 'Date: %s', 'wp-job-manager-resumes' ), esc_html( $item['date'] ) );
				// translators: The placeholder %s is the job title of a work experience on a resume.
				$experience_str_parts[] = sprintf( __( 'Job Title: %s', 'wp-job-manager-resumes' ), esc_html( $item['job_title'] ) );
				// translators: The placeholder %s are the notes regarding a work listing on their resume.
				$experience_str_parts[] = sprintf( __( 'Notes: %s', 'wp-job-manager-resumes' ), esc_html( $item['notes'] ) );
				$experiences[]          = implode( '<br />', $experience_str_parts );
			}
			$value = implode( '<br /><br />', $experiences );
		}
		return $value;
	}

	/**
	 * Export the value from links collection field.
	 *
	 * @param mixed   $raw_value
	 * @param WP_Post $resume
	 * @param array   $field
	 *
	 * @return string
	 */
	public static function export_field_links( $raw_value, $resume, $field ) {
		$value = '';
		$links = array();
		if ( ! empty( $raw_value ) && is_array( $raw_value ) ) {
			foreach ( $raw_value as $link ) {
				if ( empty( $link['url'] ) ) {
					continue;
				}
				if ( ! isset( $link['name'] ) ) {
					$link['name'] = $link['url'];
				}
				$links[] = sprintf( '<a href="%s" target="_blank" rel="noreferrer noopener">%s</a>', esc_url( $link['url'] ), esc_attr( $link['name'] ) );
			}
			$value = implode( '<br />', $links );
		}
		return $value;
	}

	/**
	 * Gets the fields that are exported with personal data.
	 *
	 * @param WP_Post $resume Resume post object.
	 * @return array
	 */
	public static function get_personal_data_fields( $resume ) {
		include_once JOB_MANAGER_PLUGIN_DIR . '/includes/abstracts/abstract-wp-job-manager-form.php';
		include_once RESUME_MANAGER_PLUGIN_DIR . '/includes/forms/class-wp-resume-manager-form-submit-resume.php';

		$fields_raw = WP_Resume_Manager_Form_Submit_Resume::get_resume_fields();
		$fields     = array();
		foreach ( $fields_raw as $key => $field_config ) {
			if ( ! isset( $field_config['personal_data'] ) || ! $field_config['personal_data'] ) {
				continue;
			}

			$field_default             = array();
			$field_default['name']     = $field_config['label'];
			$field_default['type']     = 'meta';
			$field_default['key']      = $key;
			$field_default['callback'] = false;

			$field = isset( $field_config['personal_data_config'] ) ? array_merge( $field_default, $field_config['personal_data_config'] ) : $field_default;

			if ( 'resume_content' === $key ) {
				$field['key']  = 'post_content';
				$field['type'] = 'post';
			} elseif ( 'resume_file' === $key ) {
				$field['type']     = 'raw';
				$field['value']    = get_resume_files( $resume );
				$field['callback'] = array( __CLASS__, 'export_field_resume_file' );
			} elseif ( in_array( $field_config['type'], array( 'education', 'experience', 'links' ), true ) ) {
				$field['callback'] = array( __CLASS__, 'export_field_' . $field_config['type'] );
			} elseif ( in_array( $field_config['type'], array( 'term-multiselect', 'term-checklist', 'term-select' ), true ) ) {
				$field['type']     = 'taxonomy';
				$field['callback'] = array( __CLASS__, 'export_field_taxonomy' );
				if ( isset( $field['taxonomy'] ) ) {
					$field['key'] = $field['taxonomy'];
				}
			}

			$fields[ $field['key'] ] = $field;
		}

		/**
		 * Allows filtering on what fields are considered personal data from someone's resume record.
		 *
		 * @since 1.17.0
		 *
		 * @param $fields array   List of fields (key => name).
		 * @param $resume WP_Post Resume post object.
		 */
		return apply_filters( 'resume_manager_privacy_export_personal_data_fields', $fields, $resume );
	}

	/**
	 * Get the post IDs related to a particular email address.
	 *
	 * @param string $email_address
	 * @param int    $per_page
	 * @param int    $page
	 * @param bool   $include_trashed Defaults to false.
	 * @return array Post IDs.
	 */
	public static function get_personal_data_post_ids_by_email( $email_address, $per_page, $page, $include_trashed = false ) {
		if ( empty( $email_address ) ) {
			return array();
		}

		$post_statuses = array_keys( get_resume_post_statuses() );
		if ( $include_trashed ) {
			$post_statuses[] = 'trash';
		}

		$resume_query_args = array(
			'post_type'      => 'resume',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'post_status'    => $post_statuses,
			'orderby'        => 'ID',
			'order'          => 'DESC',
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'   => '_candidate_email',
					'value' => $email_address,
				),
			),
		);

		$resume_query = new WP_Query( $resume_query_args );
		return $resume_query->get_posts();
	}

	/**
	 * Data eraser.
	 *
	 * @param string $email_address User email address.
	 * @param int    $page          Page number.
	 * @return array
	 */
	public static function user_data_eraser( $email_address, $page ) {
		$per_page       = self::ERASE_PER_PAGE;
		$items_removed  = 0;
		$items_retained = 0;
		$messages       = array();

		$resume_ids = self::get_personal_data_post_ids_by_email( $email_address, $per_page, $page, true );
		foreach ( $resume_ids as $resume_id ) {
			if ( 'trash' === get_post_status( $resume_id ) ) {
				// Don't include it as removed or retained.
				continue;
			}

			/**
			 * Allows the prevention of resume deletion on user request.
			 *
			 * @since 1.17.0
			 *
			 * @param bool $trash_post
			 * @param int  $resume_id
			 */
			$trash_post = apply_filters( 'resume_manager_privacy_erase_user_data_resume', true, $resume_id );

			if ( $trash_post && self::trash_post( $resume_id ) ) {
				$items_removed++;
			} else {
				$items_retained++;
				$messages[] = sprintf(
					// translators: Placeholder %d is the post ID for the resume that couldn't be removed.
					__( 'Resume ID %d could not be removed at this time.', 'wp-job-manager-resumes' ),
					$resume_id
				);
			}
		}

		$response = array(
			'items_removed'  => $items_removed > 0,
			'items_retained' => $items_retained > 0,
			'messages'       => $messages,
			'done'           => $per_page > count( $resume_ids ),
		);
		return $response;
	}

	/**
	 * If trash is enabled with standard garbage collection, use standard `wp_trash_post()`, otherwise set to trash manually.
	 *
	 * @param int $post_id
	 * @return bool
	 */
	private static function trash_post( $post_id ) {
		if ( EMPTY_TRASH_DAYS > 0 ) {
			wp_trash_post( $post_id );
			return 'trash' === get_post_status( $post_id );
		}

		// Post trash is disabled. Permanent removal will require manual trash emptying.
		$result = wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'trash',
			)
		);
		return ! is_wp_error( $result );
	}

	/**
	 * Checks if resumes should be removed on personal data erasure requests.
	 *
	 * @return bool
	 */
	public static function is_resume_personal_data_erasure_enabled() {
		return (bool) get_option( 'resume_manager_erasure_request_removes_resumes', false );
	}
}
