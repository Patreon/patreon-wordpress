jQuery(function($) {

	jQuery( document ).on( 'submit', '#patreon_attachment_patreon_level_form', function( e ) {
		 e.preventDefault();
		 
			jQuery.ajax({
				url: ajaxurl,
				data: jQuery("#patreon_attachment_patreon_level_form").serialize(),
				dataType: 'html',
				type: 'post',
				success: function(response) {
					jQuery(document).find('#TB_window').empty();
					jQuery(document).find('#TB_window').html(response);
				}
			});	
			

	});


});
