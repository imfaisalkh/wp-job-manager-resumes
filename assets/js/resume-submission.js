jQuery(document).ready(function($) {
	$( '.resume-manager-add-row' ).click(function() {
		var $wrap     = $(this).closest('.field');
		var max_index = 0;

		$wrap.find('input.repeated-row-index').each(function(){
			if ( parseInt( $(this).val() ) > max_index ) {
				max_index = parseInt( $(this).val() );
			}
		});

		var html          = $(this).data('row').replace( /%%repeated-row-index%%/g, max_index + 1 );
		$(this).before( html );
		return false;
	});
	$( '#submit-resume-form' ).on('click', '.resume-manager-remove-row', function() {
		if ( confirm( resume_manager_resume_submission.i18n_confirm_remove ) ) {
			$(this).closest( 'div.resume-manager-data-row' ).remove();
		}
		return false;
	});
	$( '#submit-resume-form' ).on('click', '.job-manager-remove-uploaded-file', function() {
		$(this).closest( '.job-manager-uploaded-file' ).remove();
		return false;
	});
	$('.fieldset-candidate_experience .field, .fieldset-candidate_education .field, .fieldset-links .field').sortable({
		items:'.resume-manager-data-row',
		cursor:'move',
		axis:'y',
		scrollSensitivity:40,
		forcePlaceholderSize: true,
		helper: 'clone',
		opacity: 0.65
	});

	// Confirm navigation
	var confirm_nav = false;

	if ( $('form#resume_preview').size() ) {
		confirm_nav = true;
	}
	$( 'form#submit-resume-form' ).on( 'change', 'input', function() {
		confirm_nav = true;
	});
	$( 'form#submit-resume-form, form#resume_preview' ).submit(function(){
		confirm_nav = false;
		return true;
	});
	$(window).bind('beforeunload', function(event) {
		if ( confirm_nav ) {
			return resume_manager_resume_submission.i18n_navigate;
		}
	});

	// Linkedin import
	$('input.import-from-linkedin').click(function() {
		if ( IN.User.isAuthorized() ) {
			import_linkedin_resume_data();
		} else {
			IN.Event.on( IN, "auth", import_linkedin_resume_data );
			IN.UI.Authorize().place();
		}
		return false;
	});

	function import_linkedin_resume_data() {
		$( 'fieldset.import-from-linkedin' ).remove();
		var default_profile_values = {
			formattedName: '',
			headline: '',
			summary: false,
			pictureUrl: false,
			emailAddress: '',
			location: { name: '' },
			dateOfBirth: '',
			threeCurrentPositions: { values: [] },
			threePastPositions: { values: [] },
			positions: { values: [] },
			educations: { values: [] },
			skills: { values: [] },
			memberUrlResources: { values: [] }
		};
		IN.API.Profile("me")
			.fields(
				[
					// Provided with permission level `r_basicprofile` (all apps)
					"formattedName",
					"headline",
					"summary",
					"pictureUrl",
					"emailAddress",
					"location:(name)",
					"dateOfBirth",

					// Unused basic fields in our form, but may be used by plugins.
					"firstName",
					"lastName",
					"publicProfileUrl",
					"specialties",
					"industry",

					// Limited on `r_basicprofile`
					"positions:(title,company,summary,startDate,endDate,isCurrent)",

					// Only retrieved on `r_fullprofile` level of app access (must be requested from LinkedIn).
					"threeCurrentPositions:(title,company,summary,startDate,endDate,isCurrent)",
					"threePastPositions:(title,company,summary,startDate,endDate,isCurrent)",
					"educations:(schoolName,degree,fieldOfStudy,startDate,endDate,activities,notes)",
					"skills:(skill)",
					"memberUrlResources",

					// Unused full profile fields
					"associations",
					"interests"
				]
			)
			.result( function( result ) {
				var profile = jQuery.extend( true, {}, default_profile_values, result.values[0] );
				$form       = $( '#submit-resume-form' );

				$form.find('input[name="candidate_name"]').val( profile.formattedName );
				$form.find('input[name="candidate_email"]').val( profile.emailAddress );
				$form.find('input[name="candidate_title"]').val( profile.headline );
				$form.find('input[name="candidate_location"]').val( profile.location.name );

				if ( profile.summary ) {
					$form.find('textarea[name="resume_content"]').val( profile.summary );

					if ( $.type( tinymce ) === 'object' ) {
						tinymce.get('resume_content').setContent( profile.summary );
					}
				}

				$( profile.skills.values ).each( function( i, e ) {
					if ( $form.find('input[name="resume_skills"]').val() ) {
						$form.find('input[name="resume_skills"]').val( $form.find('input[name="resume_skills"]').val() + ', ' + e.skill.name );
					} else {
						$form.find('input[name="resume_skills"]').val( e.skill.name );
					}
				});

				$( profile.memberUrlResources.values ).each( function( i, e ) {
					if ( e.name && e.url ) {
						$( '.fieldset-links' ).find( '.resume-manager-add-row' ).click();
						$( '.fieldset-links' ).find( 'input[name^="link_name"]' ).last().val( e.name );
						$( '.fieldset-links' ).find( 'input[name^="link_url"]' ).last().val( e.url );
					}
				});

				$( profile.educations.values ).each( function( i, e ) {
					var qual = [];
					var date = [];

					if ( e.fieldOfStudy ) qual.push( e.fieldOfStudy );
					if ( e.degree ) qual.push( e.degree );
					if ( e.startDate ) date.push( e.startDate.year );
					if ( e.endDate ) date.push( e.endDate.year );

					$( '.fieldset-candidate_education' ).find( '.resume-manager-add-row' ).click();
					$( '.fieldset-candidate_education' ).find( 'input[name^="candidate_education_location"]' ).last().val( e.schoolName );
					$( '.fieldset-candidate_education' ).find( 'input[name^="candidate_education_qualification"]' ).last().val( qual.join( ', ' ) );
					$( '.fieldset-candidate_education' ).find( 'input[name^="candidate_education_date"]' ).last().val( date.join( '-' ) );
					$( '.fieldset-candidate_education' ).find( 'textarea[name^="candidate_education_notes"]' ).last().val( e.notes );
				});

				$( profile.positions.values ).each( function( i, e ) {
					var date = [];

					if ( e.startDate ) date.push( e.startDate.year );
					if ( e.endDate ) date.push( e.endDate.year );

					$( '.fieldset-candidate_experience' ).find( '.resume-manager-add-row' ).click();
					$( '.fieldset-candidate_experience' ).find( 'input[name^="candidate_experience_employer"]' ).last().val( e.company.name );
					$( '.fieldset-candidate_experience' ).find( 'input[name^="candidate_experience_job_title"]' ).last().val( e.title );
					$( '.fieldset-candidate_experience' ).find( 'input[name^="candidate_experience_date"]' ).last().val( date.join( '-' ) );
					$( '.fieldset-candidate_experience' ).find( 'textarea[name^="candidate_experience_notes"]' ).last().val( e.summary );
				});

				if ( profile.pictureUrl ) {
					var photo_field = $('.fieldset-candidate_photo .field');

					if ( photo_field ) {
						var photo_field_name = photo_field.find(':input[type="file"]').attr( 'name' );
					}
					$('.fieldset-candidate_photo .field').prepend('<div class="job-manager-uploaded-files"><div class="job-manager-uploaded-file"><span class="job-manager-uploaded-file-preview"><img src="' + profile.pictureUrl + '" /> <a class="job-manager-remove-uploaded-file" href="#">[' + resume_manager_resume_submission.i18n_remove + ']</a></span><input type="hidden" class="input-text" name="current_' + photo_field_name + '" value="' + profile.pictureUrl + '" /></div></div>');
				}

				$form.trigger( 'linkedin_import', profile );
			}
		);
	}
});
