jQuery(document).ready(function($) {
	// Slide toggle
	jQuery( '.resume_contact_details' ).hide();
	jQuery( '.resume_contact_button' ).click(function() {
		jQuery( '.resume_contact_details' ).slideToggle();
	});
});