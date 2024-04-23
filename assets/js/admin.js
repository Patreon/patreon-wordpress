;(function() {

	jQuery( function( $ ) {

		jQuery(document).on( 'click', '.patreon-wordpress .notice-dismiss', function(e) {


			var patreon_wordpress_nonce_disconnect_success_notice = jQuery( this ).parent().attr( 'patreon_wordpress_nonce_disconnect_success_notice' );
			var patreon_wordpress_nonce_setup_needed = jQuery( this ).parent().attr( 'patreon_wordpress_nonce_setup_needed' );
			var patreon_wordpress_nonce_patron_pro_addon_notice_shown = jQuery( this ).parent().attr( 'patreon_wordpress_nonce_patron_pro_addon_notice_shown' );
			var patreon_wordpress_nonce_patron_content_manager_addon_notice_shown = jQuery( this ).parent().attr( 'patreon_wordpress_nonce_patron_content_manager_addon_notice_shown' );
			var patreon_wordpress_nonce_rate_plugin_notice = jQuery( this ).parent().attr( 'patreon_wordpress_nonce_rate_plugin_notice' );
			var patreon_wordpress_nonce_plugin_critical_issues = jQuery( this ).parent().attr( 'patreon_wordpress_nonce_plugin_critical_issues' );
			jQuery.ajax({
				url: ajaxurl,
				type:"POST",
				dataType : 'html',
				data: {
					action: 'patreon_wordpress_dismiss_admin_notice',
					notice_id: jQuery( this ).parent().attr( "id" ),
					patreon_wordpress_nonce_disconnect_success_notice: patreon_wordpress_nonce_disconnect_success_notice,
					patreon_wordpress_nonce_setup_needed: patreon_wordpress_nonce_setup_needed,
					patreon_wordpress_nonce_patron_pro_addon_notice_shown: patreon_wordpress_nonce_patron_pro_addon_notice_shown,
					patreon_wordpress_nonce_patron_content_manager_addon_notice_shown: patreon_wordpress_nonce_patron_content_manager_addon_notice_shown,
					patreon_wordpress_nonce_plugin_critical_issues: patreon_wordpress_nonce_plugin_critical_issues,
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
		
		jQuery(document).on( 'click', '#patreon_wordpress_disconnect_reconnect_to_patreon', function(e) {
			e.preventDefault();
			var target = jQuery(this).attr( 'target' );
			window.location.replace( target );
		});
		
		jQuery(document).on( 'click', '.patreon-wordpress-admin-toggle', function(e) {
			
			e.preventDefault();
			
			var toggle_id = jQuery( this ).attr( 'toggle' );
			var toggle_target = document.getElementById( toggle_id );
			var patreon_wordpress_advanced_options_toggle_nonce = jQuery( this ).attr( 'patreon_wordpress_advanced_options_toggle_nonce' );

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
					patreon_wordpress_advanced_options_toggle_nonce: patreon_wordpress_advanced_options_toggle_nonce,
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
					patreon_wordpress_nonce_post_sync: pw_admin_js.patreon_wordpress_nonce_post_sync,
				},
				success: function( response ) {
					
					jQuery( '#patreon_wp_post_import_status' ).empty();
					
					if ( response == 'apiv2fail') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'Wrong api version! Please upgrade to v2 using the tutorial <a href="https://www.patreondevelopers.com/t/how-to-upgrade-your-patreon-wordpress-to-use-api-v2/3249" target="_blank">here</a>' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#f31d00' );
						return;
					}
					
					if ( response == 'need_admin_privileges') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'You need admin privileges to be able to do this' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#f31d00' );
						return;
					}
					
					if ( response == 'nonce_fail') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'Form expired - please reload the page and try again' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#f31d00' );
						return;
					}
					
					jQuery( '#patreon_wp_post_import_status' ).html( 'Started a post import' );
					jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#129500' );
					
					// Replace the button with post batch import button
					
					jQuery( '#patreon_post_import_button_container' ).html( '<button id="patreon_wordpress_import_next_batch_of_posts" class="button button-primary button-large" pw_input_target="#patreon_wp_post_import_status" target="" style="margin-right: 10px;">Import next batch</button><button id="patreon_wordpress_cancel_manual_post_import" class="button button-primary button-large" pw_input_target="#patreon_wp_post_import_status" target="">Cancel</button>' );
					jQuery( '#post_import_status_heading' ).html( 'Ongoing post import' );
					jQuery( '#post_import_info_text' ).html( "Posts will be imported automatically every 5 minutes. If they are not, or you want to do it faster, click to import next batch of posts. This will import the next batch of posts in the queue. You can do this every 10 seconds." );
					
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
					action: 'patreon_wordpress_import_next_batch_of_posts',
					patreon_wordpress_nonce_post_sync: pw_admin_js.patreon_wordpress_nonce_post_sync,
				},
				beforeSend: function(e) {
					jQuery( '#patreon_wp_post_import_status' ).html( 'Importing next batch...' );
					jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#129500' );
				},
				success: function( response ) {
					
					jQuery( '#patreon_wp_post_import_status' ).empty();
					
					if ( response == 'apiv2fail') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'Wrong api version! Please upgrade to v2 using the tutorial <a href="https://www.patreondevelopers.com/t/how-to-upgrade-your-patreon-wordpress-to-use-api-v2/3249" target="_blank">here</a>' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#f31d00' );
						return;
					}
					
					
					if ( response == 'need_admin_privileges') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'You need admin privileges to be able to do this' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#f31d00' );
						return;
					}
					
					if ( response == 'nonce_fail') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'Form expired - please reload the page and try again' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#f31d00' );
						return;
					}
					
					
					if ( response == 'time_limit_error') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'You can trigger next batch every 10 seconds. Please wait a few seconds more.' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#f31d00' );
						return;
					}
					
					if ( response == 'no_ongoing_post_import') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'There is no ongoing post import' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#f31d00' );
						
						// Replace the post import setting info with original
						jQuery( '#patreon_post_import_button_container' ).html( '<button id="patreon_wordpress_start_post_import" class="button button-primary button-large" pw_input_target="#patreon_wp_post_import_status" target="">Start an import</button>' );
						jQuery( '#post_import_status_heading' ).html( 'Start a post import' );
						jQuery( '#post_import_info_text' ).html( "Start an import of your posts from Patreon if you haven't done it before. After import of existing posts is complete, new posts will automatically be imported and existing posts automatically updated so you don't need to do this again." );
						
						return;
					}
					
					if ( response == 'did_not_import_any_post') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'Failed to import any post...' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#f31d00' );
						return;
					}

					if ( response == 'expired_or_lost_cursor_deleted') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'Expired or lost page cursor deleted. Post import will restart from scratch...' );
						return;
					}
					
					if ( response == 'throttled_internally') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'Patreon api was contacted too frequently. Please wait a few minutes and try again...' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#f31d00' );
						return;
					}
					if ( response == 'couldnt_get_posts') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'Failed to get posts from Patreon...' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#f31d00' );
						return;
					}
					
					if ( response == 'post_import_ended') {
						
						jQuery( '#patreon_wp_post_import_status' ).html( 'Post import ended' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#129500' );
						
						// Replace the post import setting info with original
						jQuery( '#patreon_post_import_button_container' ).html( '<button id="patreon_wordpress_start_post_import" class="button button-primary button-large" pw_input_target="#patreon_wp_post_import_status" target="">Start an import</button>' );
						jQuery( '#post_import_status_heading' ).html( 'Start a post import' );
						jQuery( '#post_import_info_text' ).html( "Start an import of your posts from Patreon if you haven't done it before. After import of existing posts is complete, new posts will automatically be imported and existing posts automatically updated so you don't need to do this again." );
						
						return;
					}
					
					if ( response == 'imported_posts') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'Imported next batch' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#129500' );
						return;
					}
					
					jQuery( '#patreon_wp_post_import_status' ).html( 'An unexpected issue occurred' );
					jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#f31d00' );
					
				},
			});		
			
		});
		jQuery(document).on( 'click', '#patreon_wordpress_cancel_manual_post_import', function(e) {
			
			e.preventDefault();
			var pw_input_target = jQuery( this ).attr( 'pw_input_target' );
			
			jQuery.ajax({
				url: ajaxurl,
				type:"POST",
				dataType : 'html',
				data: {
					action: 'patreon_wordpress_cancel_manual_post_import',
					patreon_wordpress_nonce_post_sync: pw_admin_js.patreon_wordpress_nonce_post_sync,
				},
				beforeSend: function(e) {
				},
				success: function( response ) {
					
					jQuery( '#patreon_wp_post_import_status' ).empty();
					
					if ( response == 'apiv2fail') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'Wrong api version! Please upgrade to v2 using the tutorial <a href="https://www.patreondevelopers.com/t/how-to-upgrade-your-patreon-wordpress-to-use-api-v2/3249" target="_blank">here</a>' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#f31d00' );
						return;
					}
					
					if ( response == 'time_limit_error') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'You can trigger next batch every 10 seconds. Please wait a few seconds more.' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#f31d00' );
						return;
					}
					
					
					if ( response == 'need_admin_privileges') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'You need admin privileges to be able to do this' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#f31d00' );
						return;
					}
					
					if ( response == 'nonce_fail') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'Form expired - please reload the page and try again' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#f31d00' );
						return;
					}
					
					if ( response == 'no_ongoing_post_import') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'There is no ongoing post import' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#f31d00' );
						
						// Replace the post import setting info with original
						jQuery( '#patreon_post_import_button_container' ).html( '<button id="patreon_wordpress_start_post_import" class="button button-primary button-large" pw_input_target="#patreon_wp_post_import_status" target="">Start an import</button>' );
						jQuery( '#post_import_status_heading' ).html( 'Start a post import' );
						jQuery( '#post_import_info_text' ).html( "Start an import of your posts from Patreon if you haven't done it before. After import of existing posts is complete, new posts will automatically be imported and existing posts automatically updated so you don't need to do this again." );
						
						return;
					}
					
					if ( response == 'did_not_import_any_post') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'Failed to import any post...' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#f31d00' );
						return;
					}

					if ( response == 'expired_or_lost_cursor_deleted') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'Expired or lost page cursor deleted. Post import will restart from scratch...' );
						return;
					}
					
					if ( response == 'throttled_internally') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'Patreon api was contacted too frequently. Please wait a few minutes and try again...' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#f31d00' );
						return;
					}
					if ( response == 'couldnt_get_posts') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'Failed to get posts from Patreon...' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#f31d00' );
						return;
					}
					
					if ( response == 'post_import_ended') {
						
						jQuery( '#patreon_wp_post_import_status' ).html( 'Post import ended' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#129500' );
						
						// Replace the post import setting info with original
						jQuery( '#patreon_post_import_button_container' ).html( '<button id="patreon_wordpress_start_post_import" class="button button-primary button-large" pw_input_target="#patreon_wp_post_import_status" target="">Start an import</button>' );
						jQuery( '#post_import_status_heading' ).html( 'Start a post import' );
						jQuery( '#post_import_info_text' ).html( "Start an import of your posts from Patreon if you haven't done it before. After import of existing posts is complete, new posts will automatically be imported and existing posts automatically updated so you don't need to do this again." );
						
						return;
					}
					
					if ( response == 'imported_posts') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'Imported next batch' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#129500' );
						return;
					}
					
					if ( response == 'manual_post_import_canceled') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'Canceled' );
						jQuery( '#patreon_post_import_button_container' ).html( '<button id="patreon_wordpress_start_post_import" class="button button-primary button-large" pw_input_target="#patreon_wp_post_import_status" target="">Start an import</button>' );
						jQuery( '#post_import_status_heading' ).html( 'Start a post import' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#129500' );
						return;
					}
					
					if ( response == 'couldnt_cancel_manual_post_import') {
						jQuery( '#patreon_wp_post_import_status' ).html( 'Could not cancel import' );
						jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#f31d00' );
						return;
					}
					
					jQuery( '#patreon_wp_post_import_status' ).html( 'An unexpected issue occurred' );
					jQuery( '#patreon_wp_post_import_status' ).css( 'color', '#f31d00' );
					
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
			var patreon_wordpress_nonce_save_post_sync_options = jQuery( this ).attr( 'patreon_wordpress_nonce_save_post_sync_options' );
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
					patreon_wordpress_nonce_save_post_sync_options: patreon_wordpress_nonce_save_post_sync_options,
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
			var patreon_wordpress_nonce_save_post_sync_options = jQuery( this ).attr( 'patreon_wordpress_nonce_save_post_sync_options' );
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
					patreon_wordpress_nonce_save_post_sync_options: patreon_wordpress_nonce_save_post_sync_options,
				},
				beforeSend: function( e ) {			
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
		
		// Save patreon post author option upon change in post sync wizard screens
		jQuery( "#patreon-post-author-for-synced-posts" ).on( 'change', function(e) {
			
			// Just in case
			e.preventDefault();
			var pw_input_target = jQuery( this ).attr( 'pw_input_target' );
			var patreon_wordpress_nonce_save_post_sync_options = jQuery( this ).attr( 'patreon_wordpress_nonce_save_post_sync_options' );
			var option_value = jQuery(this).val();
			
			if (  option_value == '' ) {
				// Do nothing if value is empty
				jQuery( pw_input_target ).html('');
				console.log('empty');
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
					patreon_wordpress_nonce_save_post_sync_options: patreon_wordpress_nonce_save_post_sync_options,
				},
				beforeSend: function( e ) {			
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
		
		// Save patreon-remove-deleted-posts option upon change during post sync wizard screens
		jQuery( "#patreon-remove-deleted-posts" ).on( 'change', function(e) {
			
			// Just in case
			e.preventDefault();
			var pw_input_target = jQuery( this ).attr( 'pw_input_target' );
			var option_value = jQuery(this).val();
			var patreon_wordpress_nonce_save_post_sync_options = jQuery( this ).attr( 'patreon_wordpress_nonce_save_post_sync_options' );
			
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
					patreon_wordpress_nonce_save_post_sync_options: patreon_wordpress_nonce_save_post_sync_options,
				},
				beforeSend: function( e ) {			
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

		jQuery( '#patreon_patron_pro_upsell' ).on( 'click', function (e) {
			e.preventDefault();
			target_url = jQuery(this).attr('go_to_url')
			window.location.replace(target_url)
			
		});

		// Only trigger if the select dropdown is actually present
		
		jQuery(document).on( 'click', '#patreon_level_refresh', function(e) {
			
			var pw_input_target = jQuery( "#patreon_level_select" );
			var pw_post_id = pw_input_target.attr( 'pw_post_id' );		
			var patreon_wordpress_nonce_populate_tier_dropdown = jQuery( this ).attr( 'patreon_wordpress_nonce_populate_tier_dropdown' );

			jQuery.ajax({
				url: ajaxurl,
				async: true, // Just to make sure
				type:"POST",
				dataType : 'html',
				data: {
					action: 'patreon_wordpress_populate_patreon_level_select',
					pw_post_id: pw_post_id,
					patreon_wordpress_nonce_populate_tier_dropdown: patreon_wordpress_nonce_populate_tier_dropdown,
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