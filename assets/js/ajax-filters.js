jQuery( document ).ready( function ( $ ) {

	var xhr = [];

	$( '.resumes' ).on( 'update_results', function( event, page, append ) {
		var data     = '';
		var target   = $(this);
		var form     = target.find( '.resume_filters' );
		var showing  = target.find( '.showing_resumes' );
		var results  = target.find( '.resumes' );
		var per_page = target.data( 'per_page' );
		var orderby  = target.data( 'orderby' );
		var order    = target.data( 'order' );
		var featured = target.data( 'featured' );
		var index    = $( 'div.resumes' ).index(this);

		if ( xhr[index] ) {
			xhr[index].abort();
		}

		if ( append ) {
			$( '.load_more_resumes', target ).addClass( 'loading' );
		} else {
			$( results).addClass( 'loading' );
			$( 'li.resume, li.no_resumes_found', results ).css( 'visibility', 'hidden' );
		}

		if ( true == target.data( 'show_filters' ) ) {

			var categories = form.find(':input[name^="search_categories"]').map(function () { return $(this).val(); }).get();
			var keywords  = '';
			var location  = '';
			var $keywords = form.find(':input[name="search_keywords"]');
			var $location = form.find(':input[name="search_location"]');

			// Workaround placeholder scripts
			if ( $keywords.val() != $keywords.attr( 'placeholder' ) )
				keywords = $keywords.val();

			if ( $location.val() != $location.attr( 'placeholder' ) )
				location = $location.val();

			var data = {
				action: 			'resume_manager_get_resumes',
				search_keywords: 	keywords,
				search_location: 	location,
				search_categories:  categories,
				per_page: 			per_page,
				orderby: 			orderby,
				order: 			    order,
				page:               page,
				featured:           featured,
				show_pagination:    target.data( 'show_pagination' ),
				form_data:          form.serialize()
			};

		} else {

			var data = {
				action: 			'resume_manager_get_resumes',
				search_categories:  target.data('categories').split(','),
                		search_keywords: target.data('keywords'),
		                search_location: target.data('location'),
				per_page: 			per_page,
				orderby: 			orderby,
				order: 			    order,
				featured:           featured,
				page:               page,
				show_pagination:    target.data( 'show_pagination' ),
			};

		}

		xhr[index] = $.ajax( {
			type: 		'POST',
			url: 		resume_manager_ajax_filters.ajax_url,
			data: 		data,
			success: 	function( response ) {
				if ( response ) {
					try {

						// Get the valid JSON only from the returned string
						if ( response.indexOf("<!--WPJM-->") >= 0 )
							response = response.split("<!--WPJM-->")[1]; // Strip off before WPJM

						if ( response.indexOf("<!--WPJM_END-->") >= 0 )
							response = response.split("<!--WPJM_END-->")[0]; // Strip off anything after WPJM_END

						var result = $.parseJSON( response );

						if ( result.showing ) {
							$(showing).show().html('').append( '<span>' + result.showing + '</span>' + result.showing_links );
						} else {
							$(showing).hide();
						}

						if ( result.html ) {
							if ( append ) {
								$(results).append( result.html );
							} else {
								$(results).html( result.html );
							}
						}

						if ( true == target.data( 'show_pagination' ) ) {
							target.find('.job-manager-pagination').remove();

							if ( result.pagination ) {
								target.append( result.pagination );
							}
						} else {
							if ( ! result.found_resumes || result.max_num_pages === page ) {
								$( '.load_more_resumes', target ).hide();
							} else {
								$( '.load_more_resumes', target ).show().data( 'page', page );
							}
							$( '.load_more_resumes', target ).removeClass( 'loading' );
							$( 'li.resume', results ).css( 'visibility', 'visible' );
						}

						$( results ).removeClass( 'loading' );

						target.triggerHandler( 'updated_results', result );

					} catch(err) {
						//console.log(err);
					}
				}
			}
		} );
	} );

	$( '#search_keywords, #search_location, #search_categories' ).change( function() {
		var target = $(this).closest( 'div.resumes' );

		target.triggerHandler( 'update_results', [ 1, false ] );
	} ).change();

	$( '.resume_filters' ).on( 'click', '.reset', function() {
		var target  = $(this).closest( 'div.resumes' );
		var form    = $(this).closest( 'form' );

		form.find(':input[name="search_keywords"]').not(':input[type="hidden"]').val('');
		form.find(':input[name="search_location"]').not(':input[type="hidden"]').val('');
		form.find(':input[name^="search_categories"]').not(':input[type="hidden"]').val( 0 ).trigger( 'chosen:updated' );

		target.triggerHandler( 'reset' );
		target.triggerHandler( 'update_results', [ 1, false ] );

		return false;
	} );

	$( '.load_more_resumes' ).click( function () {
		var target = $( this ).closest( 'div.resumes' );
		var page = $( this ).data( 'page' );

		if ( ! page ) {
			page = 1;
		} else {
			page = parseInt( page );
		}

		$( this ).data( 'page', ( page + 1 ) );

		target.triggerHandler( 'update_results', [ page + 1, true ] );

		return false;
	} );

	$( 'div.resumes' ).on( 'click', '.job-manager-pagination a', function() {
		var target = $( this ).closest( 'div.resumes' );
		var page   = $( this ).data( 'page' );

		target.triggerHandler( 'update_results', [ page, false ] );

		return false;
	} );

	if ( $.isFunction( $.fn.chosen ) ) {
		$( 'select[name^="search_categories"]' ).chosen();
	}
});
