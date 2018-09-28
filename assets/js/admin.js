jQuery( function( $ ) {

	setTimeout(function () {
		
		jQuery( document ).on( 'click', '#patreon-image-lock-icon', function( e ) {
			
			var image_classes = tinymce.editors[0].selection.getNode().className.split(' ');
			var arrayLength = image_classes.length;
			
			for ( var i = 0; i < arrayLength; i++ ) {
				if ( image_classes[i].indexOf( 'wp-image-' ) !== -1 ) {
					var attachment_id = image_classes[i].replace( "wp-image-", "" );
				}
			}		

			tb_show( 'Patron only image', "#TB_inline?width=350&height=150&inlineId=mygallery-form", false );
			
			jQuery( document ).find( '#TB_window' ).width( TB_WIDTH ).height( TB_HEIGHT ).css( 'margin-left', - TB_WIDTH / 2 );
			
			data = { 
				action: "patreon_make_attachment_pledge_editor",
				patreon_attachment_id: attachment_id
			};     
			jQuery.ajax({
				url: ajaxurl,
				data: data,
				dataType: 'html',
				type: 'post',
				success: function(response) {
					jQuery( document ).find( '#TB_window' ).html( response );
				}
			});
		})
	}, 1000 );
	
	jQuery( document ).on( 'submit', '#patreon_attachment_patreon_level_form', function( e ) {
		e.preventDefault();
		jQuery.ajax({
			url: ajaxurl,
			data: jQuery( '#patreon_attachment_patreon_level_form' ).serialize(),
			dataType: 'html',
			type: 'post',
			success: function( response ) {
				jQuery( document ).find( '#TB_window' ).empty();
				jQuery( document ).find( '#TB_window' ).html(response);
			}
		});	
	});
	
	// Need to bind to event after tinymce is initialized - so we hook to all tinymce instances after a timeout
	setTimeout( function () {
		
		if( typeof tinymce === 'undefined' ) {
			return;
		}
		for ( var i = 0; i < tinymce.editors.length; i++ ) {
			
			tinymce.editors[i].on( 'click', function ( e ) {
				
				if( e.target.nodeName = 'img' ) {
					
					var $ = tinymce.dom.DomQuery;
					var tinymce_iframe_offset = jQuery( '#content_ifr' ).offset();
					var clicked_image_inside_frame_offset = $( e.target ).offset();
					
					// Add a special attribute to easily find the item because tinymce's domquery doesnt have functions to get width of the relevant element - we have to get it from jquery from outside the iframe. 
					
					$( e.target ).attr( 'data-patreon-selected-image', '1' );
					
					// Get the clicked image inside iframe
					var clicked_image = jQuery( '#content_ifr' ).contents().find( '[data-patreon-selected-image="1"]' );
					var clicked_image_width = clicked_image.width();
					
					// Remove the added attribute
					$( e.target ).removeAttr( 'data-patreon-selected-image' );
					
					
					jQuery( '#patreon-image-toolbar' ).css({
							position : 'absolute',
							top: tinymce_iframe_offset.top + clicked_image_inside_frame_offset.top + 20 + "px",
							left: tinymce_iframe_offset.left + clicked_image_inside_frame_offset.left + clicked_image_width + 10 + "px"
						}).show();
					}
			});
		}
	}, 1000);

	jQuery(document).on( 'click', '.patreon-wordpress .notice-dismiss', function(e) {

		jQuery.ajax({
			url: ajaxurl,
			type:"POST",
			dataType : 'html',
			data: {
				action: 'patreon_wordpress_dismiss_admin_notice',
				notice_id: jQuery( this ).parent().attr( "id" ),
			}
		});
	});	
	
	jQuery(document).on( 'click', '.patreon-wordpress-admin-toggle', function(e) {
		
		e.preventDefault();
		
		var toggle_id = jQuery( this ).attr( 'toggle' );
		var toggle_target = document.getElementById( toggle_id );

		jQuery( toggle_target ).slideToggle();
		
		if( jQuery( this ).attr( 'togglestatus' ) == 'off' ) {
			
			jQuery( this ).html( jQuery( this ).attr( 'ontext' ) );
			jQuery( this ).attr( 'togglestatus', 'on' );
			
		}
		else if( jQuery( this ).attr( 'togglestatus' ) == 'on' ) {
			
			jQuery( this ).html( jQuery( this ).attr( 'offtext' ) );
			jQuery( this ).attr( 'togglestatus', 'off' );
			
		}
				
		jQuery.ajax({
			url: ajaxurl,
			type:"POST",
			dataType : 'html',
			data: {
				action: 'patreon_wordpress_toggle_option',
				toggle_id: toggle_id,
			}
		});		
		
	});
});