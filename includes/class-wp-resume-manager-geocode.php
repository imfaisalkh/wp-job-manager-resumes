<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WP_Resume_Manager_Geocode
 *
 * Obtains Geolocation data for posted resumes from Google.
 */
class WP_Resume_Manager_Geocode {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'resume_manager_update_resume_data', array( $this, 'update_location_data' ), 20, 2 );
		add_action( 'resume_manager_candidate_location_edited', array( $this, 'change_location_data' ), 20, 2 );
	}

	/**
	 * Update location data - when submitting a resume
	 */
	public function update_location_data( $resume_id, $values ) {
		if ( apply_filters( 'resume_manager_geolocation_enabled', true ) ) {
			$address_data = WP_Job_Manager_Geocode::get_location_data( $values['resume_fields']['candidate_location'] );
			WP_Job_Manager_Geocode::save_location_data( $resume_id, $address_data );
		}
	}

	/**
	 * Change a resumes location data upon editing
	 * @param  int $resume_id
	 * @param  string $new_location
	 */
	public function change_location_data( $resume_id, $new_location ) {
		if ( apply_filters( 'resume_manager_geolocation_enabled', true ) ) {
			$address_data = WP_Job_Manager_Geocode::get_location_data( $new_location );
			WP_Job_Manager_Geocode::clear_location_data( $resume_id );
			WP_Job_Manager_Geocode::save_location_data( $resume_id, $address_data );
		}
	}
}

new WP_Resume_Manager_Geocode();