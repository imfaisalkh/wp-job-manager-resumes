jQuery(document).ready(function($) {
	// Data rows
	$( "input.resume_manager_add_row" ).click(function(){
		$(this).closest('table').find('tbody').append( $(this).data('row') );
		return false;
	});

	// Sorting
	$('.wc-job-manager-resumes-repeated-rows tbody').sortable({
		items:'tr',
		cursor:'move',
		axis:'y',
		handle: 'td.sort-column',
		scrollSensitivity:40,
		forcePlaceholderSize: true,
		helper: 'clone',
		opacity: 0.65
	});

	// Datepicker
	$( "input#_resume_expires" ).datepicker({
		dateFormat: 'yy-mm-dd',
		minDate: 0
	});

	// Settings
	$('.job-manager-settings-wrap')
		.on( 'change', '#setting-resume_manager_enable_skills', function() {
			if ( $( this ).is(':checked') ) {
				$('#setting-resume_manager_max_skills').closest('tr').show();
			} else {
				$('#setting-resume_manager_max_skills').closest('tr').hide();
			}
		})
		.on( 'change', '#setting-resume_manager_enable_categories', function() {
			if ( $( this ).is(':checked') ) {
				$('#setting-resume_manager_enable_default_category_multiselect, #setting-resume_manager_category_filter_type').closest('tr').show();
			} else {
				$('#setting-resume_manager_enable_default_category_multiselect, #setting-resume_manager_category_filter_type').closest('tr').hide();
			}
		});

	// Account creation settings.
	var $generate_username_from_email      = jQuery('#setting-resume_manager_generate_username_from_email');
	var $use_standard_password_setup_email = jQuery('#setting-resume_manager_use_standard_password_setup_email');
	var $resume_manager_registration_role  = jQuery('#setting-resume_manager_registration_role');
	$('.job-manager-settings-wrap').on( 'change', '#setting-resume_manager_enable_registration', function() {
		if ( $( this ).is(':checked') ) {
			$generate_username_from_email.closest('tr').show();
			$use_standard_password_setup_email.closest('tr').show();
			$resume_manager_registration_role.closest('tr').show();
		} else {
			$generate_username_from_email.closest('tr').hide();
			$use_standard_password_setup_email.closest('tr').hide();
			$resume_manager_registration_role.closest('tr').hide();
		}
	});

	// If generate username is enabled on page load, assume use_standard_password_setup_email has been cleared.
	// Default is true, so let's sneakily set it to that before it gets cleared and disabled.
	if ( $generate_username_from_email.is(':checked') ) {
		$use_standard_password_setup_email.prop( 'checked', true );
	}

	// Ensure use_standard_password_setup_email is checked when generate username is checked.
	$generate_username_from_email.change(function() {
		if ( jQuery( this ).is(':checked') ) {
			$use_standard_password_setup_email.data( 'original-state', $use_standard_password_setup_email.is(':checked') ).prop( 'checked', true ).prop( 'disabled', true );
		} else {
			$use_standard_password_setup_email.prop( 'disabled', false );
			if ( undefined !== $use_standard_password_setup_email.data('original-state') ) {
				$use_standard_password_setup_email.prop( 'checked', $use_standard_password_setup_email.data('original-state') );
			}
		}
	}).change();

	$('#setting-resume_manager_enable_skills, #setting-resume_manager_enable_categories, #setting-resume_manager_enable_registration').change();
});
