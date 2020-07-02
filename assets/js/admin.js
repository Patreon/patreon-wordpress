;(function() {

	jQuery( function( $ ) {

		setTimeout(function () {
			
			jQuery( document ).on( 'click', '#patreon-image-lock-icon', function( e ) {
				
				var attachment_id = jQuery(this).attr('pw_attachment_id');

				var image_lock_icon = jQuery( document ).find( '#patreon-image-toolbar' );
				pos_x = jQuery( image_lock_icon ).attr('place_modal_x');
				pos_y = jQuery( image_lock_icon ).attr('place_modal_y');
				pw_image_source = jQuery( image_lock_icon ).attr('pw_image_source');
				
				jQuery.ajax( ajaxurl, {
					type: "POST",
					dataType : 'html',
					data: {
						action: "patreon_make_attachment_pledge_editor",
						patreon_attachment_id: attachment_id,
						pw_image_source: pw_image_source,
					},
					beforeSend: function(e) {
					},
					success: function(response) {
						
						if ( jQuery( document ).find( '#patreon_image_lock_modal' ).length ) {
							// Remove modal if it exists
							jQuery( document ).find( '#patreon_image_lock_modal' ).remove();				
						}
						
						modal = '<div id="patreon_image_lock_modal" class="patreon_image_lock_modal">' + response + '</div>';
						
						// Add the fresh
						jQuery( modal ).appendTo("body");
						
						var image_locker = jQuery( document ).find( '#patreon_image_lock_modal' );
						
						// if not tinymce and gutenberg editor is here
						
						if( jQuery( document ).find( '#editor' ).length ) {
											
							pos_x = pos_x - ( image_locker.width() /2 );
							pos_y = pos_y - ( image_locker.height() /2 );
							
							image_locker.css({
								display : 'block',
								top: pos_y + "px",
								left: pos_x + "px"
							});
							
						}
						if( typeof tinymce !== 'undefined' && jQuery( document ).find( '#editor' ).length == 0 ) {
							// Tinymce, not gutenberg
							// Place at the center of the screen with scroll
							
							pos_x = ( jQuery(window).width() / 2 ) - ( image_locker.width() /2 );
							pos_y = ( jQuery(window).height() / 2 ) - ( image_locker.height() /2 ) + jQuery(window).scrollTop();
									
							image_locker.css({
								display : 'block',
								top: pos_y + "px",
								left: pos_x + "px"
							});
							
						}	
						
					},
				});			
				
			})
		}, 1000 );
		
		// Centers image lock interface modal on window resize
		jQuery( window ).resize(function() {
			
			if ( jQuery( document ).find( '#patreon_image_lock_modal' ).length ) {

				pos_x = ( jQuery(window).width() / 2 ) - ( image_locker.width() /2 );
				pos_y = ( jQuery(window).height() / 2 ) - ( image_locker.height() /2 ) + jQuery(window).scrollTop();
						
				image_locker.css({
					top: pos_y + "px",
					left: pos_x + "px"
				});
		
			}
			
		});
		
		jQuery( document ).on( 'submit', '#patreon_attachment_patreon_level_form', function( e ) {
			e.preventDefault();
			jQuery.ajax({
				url: ajaxurl,
				data: jQuery( '#patreon_attachment_patreon_level_form' ).serialize(),
				dataType: 'html',
				type: 'post',
				beforeSend: function(e) {
					jQuery( document ).find( '#patreon_image_locking_interface_message' ).html('Updating...');
				},
				success: function( response ) {
					jQuery( document ).find( '#patreon_image_lock_modal' ).html(response);
				}
			});	
		});
		
	// Hook to image click events for classic editor below

	setTimeout( function () {
		
		if( typeof tinymce === 'undefined' || jQuery( document ).find( '#editor' ).length) {
			return;
		}
		for ( var i = 0; i < tinymce.editors.length; i++ ) {
			
			tinymce.editors[i].on( 'click', function ( e ) {
				
				if( e.target.nodeName == 'IMG' ) {
					var $ = tinymce.dom.DomQuery;
					var tinymce_iframe_offset = jQuery( '#content_ifr' ).offset();
					var clicked_image_inside_frame_offset = $( e.target ).offset();
					
					// Add a special attribute to easily find the item because tinymce's domquery doesnt have functions to get width of the relevant element - we have to get it from jquery from outside the iframe. 
					
					$( e.target ).attr( 'data-patreon-selected-image', '1' );
					
					// Get the clicked image inside iframe
					var clicked_image = jQuery( '#content_ifr' ).contents().find( '[data-patreon-selected-image="1"]' );
					var pw_image_source = clicked_image.attr('src');
					var clicked_image_width = clicked_image.width();
					
					// Remove the added attribute
					$( e.target ).removeAttr( 'data-patreon-selected-image' );
					
					// if done out of this context of tinymce, the placement goes haywire so this code to show the lock icon is repeated here
					
					jQuery( '<div id="patreon-image-toolbar"><div id="patreon-image-lock-icon"><img src="' + pw_admin_js.patreon_wordpress_assets_url + '/img/patreon-image-lock-icon.png" /></div></div>' ).appendTo("body");
					
					jQuery( '#patreon-image-lock-icon' ).css({
						border: '1px solid #c0c0c0'
					});
				
					jQuery( '#patreon-image-toolbar' ).css({
							position : 'absolute',
							top: tinymce_iframe_offset.top + clicked_image_inside_frame_offset.top + 20 + "px",
							left: tinymce_iframe_offset.left + clicked_image_inside_frame_offset.left + clicked_image_width + 10 + "px"
					}).show();
					
					
					jQuery( document ).find(  '#patreon-image-toolbar' ).attr('place_modal_x', tinymce_iframe_offset.top + clicked_image_inside_frame_offset.top );
					jQuery( document ).find(  '#patreon-image-toolbar' ).attr('place_modal_y', tinymce_iframe_offset.left + clicked_image_inside_frame_offset.left + clicked_image_width + 10);
					jQuery( document ).find(  '#patreon-image-toolbar' ).attr('pw_image_source', pw_image_source);
					jQuery( document ).find(  '#patreon-image-toolbar' ).show();	
					
				}
				else {
					// Need this here again despite the global hook to cover for tinymce cases - tinymce still keeps context on image if text inside an editor window which also has an image is clicked
					if( jQuery( document ).find( '#patreon-image-toolbar' ).length ) {
						jQuery( document ).find( '#patreon-image-toolbar' ).hide();
					}
				}
					
			});
		}
	}, 1000);


	jQuery(document).ready(function(){
		
		// Gutenberg variant - interim solution
		// Need to bind to event after tinymce is initialized - so we hook to all tinymce instances after a timeout
		
		jQuery(document).on( 'click', '#editor img', function(e) {
			
			pw_launch_image_lock_toolbar( jQuery(this),e.pageX,e.pageY );
			
		});
		
		jQuery(document).on( 'click', function(e) {
			
			if ( jQuery( e.target ).is('img') ) {
				// Potential future use
			}
			else {
				if( jQuery( document ).find( '#patreon-image-toolbar' ).length ) {
					jQuery( document ).find( '#patreon-image-toolbar' ).hide();
				}
			}
			
		});

	});

	function pw_launch_image_lock_toolbar(image_var, pos_x, pos_y) {
		
		var image = jQuery(image_var);
		var clicked_image_inside_frame_offset = image.offset();
		var clicked_image_inside_frame_pos = image.position();
		var clicked_image_width = image.width();
		var clicked_image_height = image.height();
								
		// Remove the added attribute
		
		jQuery( '<div id="patreon-image-toolbar"><div id="patreon-image-lock-icon"><img src="' + pw_admin_js.patreon_wordpress_assets_url + '/img/patreon-image-lock-icon.png" /></div></div>' ).appendTo("body");
		
		jQuery( '#patreon-image-toolbar' ).css({
				position : 'absolute',
				top: pos_y + "px",
				left: pos_x + "px",
		});
		
		jQuery( '#patreon-image-lock-icon' ).css({
			border: '1px solid #c0c0c0'
		});
		
		jQuery( document ).find(  '#patreon-image-toolbar' ).attr('place_modal_x', clicked_image_inside_frame_pos.left + clicked_image_inside_frame_offset.left + ( clicked_image_width / 2 )  );
		jQuery( document ).find(  '#patreon-image-toolbar' ).attr('place_modal_y', clicked_image_inside_frame_offset.top);
		jQuery( document ).find(  '#patreon-image-toolbar' ).attr('pw_image_source', image.attr('src'));
		jQuery( document ).find(  '#patreon-image-toolbar' ).show();	

	}


		jQuery(document).on( 'click', '.patreon_image_lock_modal_close', function(e) {
			e.preventDefault();
			jQuery( document ).find( '#patreon_image_lock_modal' ).hide();
			jQuery( document ).find( '#patreon-image-toolbar' ).hide();
		});	
		
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
		
		// Doing the below via jQuery because we have to submit some POST info inside another form. Avoided using a link inside button to account for older devices
		
		jQuery(document).on( 'click', '#patreon_wordpress_disconnect_from_patreon', function(e) {
			e.preventDefault();
			var target = jQuery(this).attr( 'target' );
			window.location.replace( target );
		});
		
		// Doing the below via jQuery because we have to submit some POST info inside another form. Avoided using a link inside button to account for older devices
		
		jQuery(document).on( 'click', '#patreon_wordpress_reconnect_to_patreon', function(e) {
			e.preventDefault();
			var target = jQuery(this).attr( 'target' );
			window.location.replace( target );
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
		
		jQuery(document).on( 'click', '#patreon_wordpress_start_post_import', function(e) {
			
			e.preventDefault();
			var pw_input_target = jQuery( this ).attr( 'pw_input_target' );
			
			jQuery.ajax({
				url: ajaxurl,
				type:"POST",
				dataType : 'html',
				data: {
					action: 'patreon_wordpress_start_post_import',
				},
				success: function( response ) {
					
					jQuery( '#patreon_wp_post_import_status' ).empty();
					
					if ( response == 'apiv2fail') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'Wrong api version! Please upgrade to v2 using the tutorial <a href="https://www.patreondevelopers.com/t/how-to-upgrade-your-patreon-wordpress-to-use-api-v2/3249" target="_blank">here</a>' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#f31d00' );
						return;
					}
					
					jQuery( '#patreon_wp_post_import_status' ).html( 'Started a post import' );
					jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#129500' );
					
					// Replace the button with post batch import button
					
					jQuery( '#patreon_post_import_button_container' ).html( '<button id="patreon_wordpress_import_next_batch_of_posts" class="button button-primary button-large" pw_input_target="#patreon_wp_post_import_status" target="">Import next batch</button>' );
					jQuery( '#post_import_status_heading' ).html( 'Ongoing post import' );
					jQuery( '#post_import_info_text' ).html( 'Click to import next batch of posts. This will import the next batch of posts in the queue. You can do this every 5 seconds.' );
					
				},
			});		
			
		});
		
		jQuery(document).on( 'click', '#patreon_wordpress_import_next_batch_of_posts', function(e) {
			
			e.preventDefault();
			var pw_input_target = jQuery( this ).attr( 'pw_input_target' );
			
			jQuery.ajax({
				url: ajaxurl,
				type:"POST",
				dataType : 'html',
				data: {
					action: 'patreon_wordpress_start_post_import',
				},
				success: function( response ) {
					
					jQuery( '#patreon_wp_post_import_status' ).empty();
					
					if ( response == 'apiv2fail') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'Wrong api version! Please upgrade to v2 using the tutorial <a href="https://www.patreondevelopers.com/t/how-to-upgrade-your-patreon-wordpress-to-use-api-v2/3249" target="_blank">here</a>' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#f31d00' );
						return;
					}
					
					jQuery( '#patreon_wp_post_import_status' ).html( 'Started a post import' );
					jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#129500' );
					
				},
			});		
			
		});
		
		jQuery(document).on( 'click', '#patreon_wordpress_disconnect_patreon_account', function(e) {
			
			e.preventDefault();
			var pw_input_target = jQuery( this ).attr( 'pw_input_target' );
			
			jQuery.ajax({
				url: ajaxurl,
				type:"POST",
				dataType : 'html',
				data: {
					action: 'patreon_wordpress_disconnect_patreon_account',
					patreon_disconnect_user_id: jQuery( this ).attr( 'patreon_disconnect_user_id' ),
				},
				beforeSend: function(e) {
					jQuery( '#patreon_wordpress_user_profile_account_connection_wrapper' ).html( 'A moment...' );
				},
				success: function( response ) {
					jQuery( '#patreon_wordpress_user_profile_account_connection_wrapper' ).html( response );
				},
			});		
			
		});
		
		jQuery(document).on( 'click', '#patreon_wordpress_connect_patreon_account', function(e) {
			
			e.preventDefault();

			var patreon_login_url = jQuery( this ).attr( 'patreon_login_url' );
			window.location.replace( patreon_login_url );
			
		});
		
		jQuery(document).on( 'click', '#patreon_wordpress_connect_patreon_account', function(e) {
			
			// Disconnects a connected Patreon account from local WP account. Does not contact the api
			
			e.preventDefault();
			
			jQuery.ajax({
				url: ajaxurl,
				type:"POST",
				dataType : 'html',
				data: {
					action: 'patreon_wordpress_disconnect_account_from_patreon',
					user_id: jQuery( this ).attr( "patreon_user_id" ),
				},
				success: function( response ) {
					jQuery( '#patreon_wp_post_import_status' ).empty();
					jQuery( '#patreon_wp_post_import_status' ).html( 'Started a post import' );
					jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#129500' );
					
				},
			});		
			
		});
		

		jQuery(document).on( 'click', '#patreon_wordpress_save_post_sync_category', function(e) {
			
			e.preventDefault();
			var pw_input_target = jQuery( this ).attr( 'pw_input_target' );
			var patreon_sync_post_type = jQuery('#patreon_sync_post_type').val();
			var patreon_sync_post_category = jQuery('#patreon_sync_post_category').val();
			var patreon_sync_post_term = jQuery('#patreon_sync_post_term').val();
			
			jQuery.ajax({
				url: ajaxurl,
				type:"POST",
				dataType : 'html',
				data: {
					action: 'patreon_wordpress_save_post_sync_category',
					patreon_sync_post_type: patreon_sync_post_type,
					patreon_sync_post_category: patreon_sync_post_category,
					patreon_sync_post_term: patreon_sync_post_term,
				},
				beforeSend: function( xhr ) {
					jQuery( '#patreon_wordpress_post_import_category_status' ).empty();					
				},
				success: function( response ) {
					jQuery( '#patreon_wordpress_post_import_category_status' ).empty();
					jQuery( '#patreon_wordpress_post_import_category_status' ).html( response );
					jQuery( '#patreon_wordpress_post_import_category_status' ).css( 'color', '#129500' );
					
				},
				error: function( response ) {
					jQuery( '#patreon_wordpress_post_import_category_status' ).empty();
					jQuery( '#patreon_wordpress_post_import_category_status' ).html( 'Sorry, encountered an issue' );
					
				},
			});		
			
		});		
		
		jQuery(document).on( 'click', '.patreon_wordpress_interface_toggle', function(e) {
			
			e.preventDefault();
			
			var toggles = jQuery( this ).attr( 'toggle' );
			var toggle_array = toggles.split(" ");
			toggle_array.forEach( function( toggle_id, index, toggle_array ) {
				var toggle_target = document.getElementById( toggle_id );

				jQuery( toggle_target ).slideToggle();
								
			}, jQuery( this ) );
			
		});	
		
		// Sync the exact amount value to select value if select is changed
		jQuery( "#patreon_level_select" ).on( 'change', function() {
			jQuery( "#patreon-level-exact" ).val( this.value );
		});
		
		// Save patreon-update-posts option upon change during post sync wizard screens
		jQuery( "#patreon-update-posts" ).on( 'change', function(e) {
			
			// Just in case
			e.preventDefault();
			var pw_input_target = jQuery( this ).attr( 'pw_input_target' );
			var option_value = jQuery(this).val();
			
			if (  option_value == '' ) {
				// Do nothing if value is empty
				jQuery( pw_input_target ).html('');
				return;
			}
			
			jQuery.ajax({
				url: ajaxurl,
				async: true, // Just to make sure
				type:"POST",
				dataType : 'html',
				data: {
					action: 'patreon_wordpress_set_update_posts_option',
					update_posts_option_value: option_value,
				},
				beforeSend: function( e ) {			
				},
				success: function( response ) {
					jQuery( pw_input_target ).empty();
					jQuery( pw_input_target ).html( 'Saved!' );
					
				},
				error: function( response ) {
					jQuery( pw_input_target ).empty();
					jQuery( pw_input_target ).html( 'Sorry - could not save' );
				},
				statusCode: {
					500: function(error) {
						jQuery( pw_input_target ).empty();
						jQuery( pw_input_target ).html( 'Sorry - error (500)' );
					}
				}
			});	
			
		});
		
		// Save patreon post author option upon change in post sync wizard screens
		jQuery( "#patreon-post-author-for-synced-posts" ).on( 'change', function(e) {
			
			// Just in case
			e.preventDefault();
			var pw_input_target = jQuery( this ).attr( 'pw_input_target' );
			var option_value = jQuery(this).val();
			
			if (  option_value == '' ) {
				// Do nothing if value is empty
				jQuery( pw_input_target ).html('');
				return;
			}
			
			jQuery.ajax({
				url: ajaxurl,
				async: true, // Just to make sure
				type:"POST",
				dataType : 'html',
				data: {
					action: 'patreon_wordpress_set_post_author_for_post_sync',
					patreon_post_author_for_post_sync: option_value,
				},
				beforeSend: function( e ) {			
				},
				success: function( response ) {
					jQuery( pw_input_target ).empty();
					jQuery( pw_input_target ).html( 'Saved!' );
					
				},
				error: function( response ) {
					jQuery( pw_input_target ).empty();
					jQuery( pw_input_target ).html( 'Sorry - could not save' );
				},
				statusCode: {
					500: function(error) {
						jQuery( pw_input_target ).empty();
						jQuery( pw_input_target ).html( 'Sorry - error (500)' );
					}
				}
			});	
			
		});
		
		// Save patreon-remove-deleted-posts option upon change during post sync wizard screens
		jQuery( "#patreon-remove-deleted-posts" ).on( 'change', function(e) {
			
			// Just in case
			e.preventDefault();
			var pw_input_target = jQuery( this ).attr( 'pw_input_target' );
			var option_value = jQuery(this).val();

			if (  option_value == '' ) {
				// Do nothing if value is empty
				jQuery( pw_input_target ).html('');
				return;
			}
			
			jQuery.ajax({
				url: ajaxurl,
				async: true, // Just to make sure
				type:"POST",
				dataType : 'html',
				data: {
					action: 'patreon_wordpress_set_delete_posts_option',
					delete_posts_option_value: option_value,
				},
				beforeSend: function( e ) {			
				},
				success: function( response ) {
					jQuery( pw_input_target ).empty();
					jQuery( pw_input_target ).html( 'Saved!' );
					
				},
				error: function( response ) {
					jQuery( pw_input_target ).empty();
					jQuery( pw_input_target ).html( 'Sorry - could not save' );
				},
				statusCode: {
					500: function(error) {
						jQuery( pw_input_target ).empty();
						jQuery( pw_input_target ).html( 'Sorry - error (500)' );
					}
				}
			});	
			
		});
		
		// Post sync post type selection dropdown action
		jQuery( "#patreon_sync_post_type" ).on( 'change', function(e) {
			
			var patreon_wordpress_post_type = jQuery(this).val();
			var patreon_wordpress_input_target = jQuery('#patreon_sync_post_category');
			var patreon_wordpress_general_error = 'Sorry - could not get the category list for this post type';
			
			e.preventDefault();
					
			jQuery('#patreon_sync_post_category').hide('slow');
			jQuery('#patreon_sync_post_term').hide('slow');

			jQuery.ajax({
				url: ajaxurl,
				type:"POST",
				dataType : 'html',
				cache: false,
				data: {
					action: 'patreon_wordpress_get_taxonomies_for_post_type',
					patreon_wordpress_post_type: patreon_wordpress_post_type,
				},
				success: function( response ) {
					if( response == '' ) {
						response = patreon_wordpress_general_error;
					}
					jQuery(patreon_wordpress_input_target).html('<option selected value="-">Select</option>' + response);
				},
				complete: function( response ) {
					jQuery('#patreon_sync_post_category').show('slow');
				},
				error: function( response ) {
					if( response == '' ) {
						//White page - possibly an issue with the server/site caused an error during updates
						response = patreon_wordpress_general_error;
					}
					jQuery(patreon_wordpress_input_target).html(response);
				},
				statusCode: {
					500: function(error) {
						response = 'Sorry, a program error was encountered on WordPress side. (500 error)';
						jQuery(patreon_wordpress_input_target).html(response);
					}
				}
			});		
		});
		
		// Post sync post - category selection dropdown action
		jQuery( "#patreon_sync_post_category" ).on( 'change', function(e) {
			
			var patreon_sync_post_category = jQuery(this).val();
			var patreon_wordpress_input_target = jQuery('#patreon_sync_post_term');
			var patreon_wordpress_general_error = 'Sorry - could not get the category list for this post type';
			
			e.preventDefault();
					
			jQuery('#patreon_sync_post_term').hide('slow');

			jQuery.ajax({
				url: ajaxurl,
				type:"POST",
				dataType : 'html',
				cache: false,
				data: {
					action: 'patreon_wordpress_get_terms_for_taxonomy',
					patreon_sync_post_category: patreon_sync_post_category,
				},
				success: function( response ) {
					if( response == '' ) {
						response = patreon_wordpress_general_error;
					}
					jQuery(patreon_wordpress_input_target).html('<option selected value="-">Select</option>' + response);
				},
				complete: function( response ) {
					jQuery('#patreon_sync_post_term').show('slow');
				},
				error: function( response ) {
					if( response == '' ) {
						//White page - possibly an issue with the server/site caused an error during updates
						response = patreon_wordpress_general_error;
					}
					jQuery(patreon_wordpress_input_target).html(response);
				},
				statusCode: {
					500: function(error) {
						response = 'Sorry, a program error was encountered on WordPress side. (500 error)';
						jQuery(patreon_wordpress_input_target).html(response);
					}
				}
			});		
		});
		
		jQuery( ".patreon_toggle_admin_sections" ).on( 'click', function (e) {
			
			if ( jQuery( e.target ).hasClass( 'patreon_setting_section_help_icon' ) ) { 
				return 
			};
			
			jQuery( '#footer-thankyou' ).remove();
			var patreon_target = jQuery( this ).parent('.patreon_admin_health_content_box').find( jQuery( this ).attr( 'target' ) );
			e.preventDefault();
			if ( jQuery( this ).find( 'span:first' ).hasClass( 'dashicons-arrow-down-alt2' ) ) {
				jQuery( this ).find( 'span:first' ).removeClass( 'dashicons-arrow-down-alt2' );
				jQuery( this ).find( 'span:first' ).addClass( 'dashicons-arrow-up-alt2' );
			}
			else if ( jQuery( this ).find( 'span:first' ).hasClass( 'dashicons-arrow-up-alt2' ) ) {
				jQuery( this ).find( 'span:first' ).removeClass( 'dashicons-arrow-up-alt2' );
				jQuery( this ).find( 'span:first' ).addClass( 'dashicons-arrow-down-alt2' );
			}
			jQuery( patreon_target ).toggle( 'slow' );
		});
		
		jQuery( '#patreon_copy_health_check_output' ).on( 'click', function (e) {
			e.preventDefault();
			// Some of below is from stack https://stackoverflow.com/questions/23048550/how-to-copy-a-divs-content-to-clipboard-without-flash
			var textarea = document.createElement( 'textarea' );
			  textarea.id = 'patreon_temp_element'
			  // Optional step to make less noise on the page, if any!
			  textarea.style.height = 0
			  // Now append it to your page somewhere, I chose <body>
			  document.body.appendChild( textarea )
			  // Give our textarea a value of whatever inside the div of id=containerid
			  textarea.value = jQuery( '#patreon_health_check_output_for_support' ).text();
			  // Now copy whatever inside the textarea to clipboard
			  var selector = document.querySelector( '#patreon_temp_element' );
			  selector.select();
			  document.execCommand('copy');
			  // Remove the textarea
			  document.body.removeChild(textarea);
			jQuery( "#patreon_copied" ).text( "Copied!" ).show().fadeOut( 1000 );
		});

		// Only trigger if the select dropdown is actually present
		
		jQuery(document).on( 'click', '#patreon_level_refresh', function(e) {
			
			var pw_input_target = jQuery( "#patreon_level_select" );
			var pw_post_id = pw_input_target.attr( 'pw_post_id' );
			
			jQuery.ajax({
				url: ajaxurl,
				async: true, // Just to make sure
				type:"POST",
				dataType : 'html',
				data: {
					action: 'patreon_wordpress_populate_patreon_level_select',
					pw_post_id: pw_post_id,
				},
				beforeSend: function( e ) {
					jQuery( pw_input_target ).html( '<option value="">Loading...</option>' );				
				},
				success: function( response ) {
					jQuery( pw_input_target ).empty();
					jQuery( pw_input_target ).html( response );
					
				},
				error: function( response ) {
					jQuery( pw_input_target ).empty();
					jQuery( pw_input_target ).html( response );
				},
				statusCode: {
					500: function(error) {
						jQuery( pw_input_target ).empty();
						jQuery( pw_input_target ).html( 'Sorry - error (500)' );
					}
				}
			});	
			
		});
		
		jQuery.fn.pw_screen_center = function () {
			this.css( "position", "absolute" );
			this.css( "top", Math.max(0, ((jQuery(window).height() - $(this).outerHeight()) / 2) + jQuery(window).scrollTop()) + "px");
			this.css( "left", Math.max(0, ((jQuery(window).width() - $(this).outerWidth()) / 2) + jQuery(window).scrollLeft()) + "px");
			return this;
		}	
		
	});
	
})()