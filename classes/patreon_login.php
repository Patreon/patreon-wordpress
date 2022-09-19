<?php


// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
	die;
}

class Patreon_Login {

	public static function updateExistingUser( $user_id, $user_response, $tokens ) {
		
		/* update user meta data with patreon data */
		update_user_meta( $user_id, 'patreon_refresh_token', $tokens['refresh_token'] );
		update_user_meta( $user_id, 'patreon_access_token', $tokens['access_token'] );
		update_user_meta( $user_id, 'patreon_user', $user_response['data']['attributes']['vanity'] );
		update_user_meta( $user_id, 'patreon_user_id', $user_response['data']['id'] );
		update_user_meta( $user_id, 'patreon_last_logged_in', time() );
		
		$patreon_created = '';
		if ( isset( $user_response['data']['attributes']['created'] ) ) {
			$patreon_created = $user_response['data']['attributes']['created'];
		}

		update_user_meta( $user_id, 'patreon_created', $patreon_created );
		update_user_meta( $user_id, 'patreon_token_minted', microtime() );
		update_user_meta( $user_id, 'patreon_token_expires_in', $tokens['expires_in'] );

		$user = get_user_by( 'ID', $user_id );

		// Below filter vars and the following filter allows plugin devs to acquire/filter info about Patron/user after the user returns from Patreon
		
		$filter_args = array(
			'user' => $user,
			'user_response' => $user_response,
			'tokens' => $tokens,
		);
		
		do_action( 'patreon_do_action_after_local_user_is_updated_with_patreon_info', $filter_args );					

	}

	public static function updateLoggedInUserForStrictoAuth( $user_response, $tokens, $redirect = false ) {

		$user = wp_get_current_user();

		if ( $user->ID == 0 ) {
			
			$redirect = add_query_arg( 'patreon_message', 'patreon_cant_login_strict_oauth', $redirect );
			wp_redirect( $redirect );
			exit;
			
		} else {
			
			/* update user meta data with patreon data */
			self::updateExistingUser( $user->ID, $user_response, $tokens );
			wp_redirect( $redirect );
			exit;	
			
		}
		
	}

	public static function checkTokenExpiration( $user_id = false ) {

		if ( $user_id ) {
			$user = get_user_by( 'ID', $user_id );
		} else {
			$user = wp_get_current_user();
		}

		if ( $user AND $user->ID != 0 ) {
			
			// Valid user is logged in. Check the token:
			
			$expiration = get_user_meta( $user->ID, 'patreon_token_expires_in', true );
			$minted     = get_user_meta( $user->ID, 'patreon_token_minted', true );
			
			if ( $minted != '' ) {
				// We have value. get secs to use them in comparison.

				$minted = explode(' ',$minted);
				// Cast to integer
				$minted = (int) $minted[1];
				
				if ( (int) microtime( true ) >= ( $minted + $expiration ) ) {
					
					// This token is expired. Nuke it.
					delete_user_meta( $user->ID, 'patreon_access_token' );
				}
		
			} else {
				
				// No minted value. Even if there may be no access token created and saved, still nuke it.
				delete_user_meta( $user->ID, 'patreon_access_token' );
				
			}
			
		}
		
	}

	public static function createOrLogInUserFromPatreon($user_response, $tokens, $redirect = false) {

		global $wpdb;

		$login_with_patreon                 = get_option( 'patreon-enable-login-with-patreon', true );
		$admins_editors_login_with_patreon  = get_option( 'patreon-enable-allow-admins-login-with-patreon', false );
		$danger_user_list                   = Patreon_Login::getDangerUserList();
		
		// Check if user is logged in to wp:
		
		// Logged in user. We just link the user up and be done.
		if ( is_user_logged_in() ) {
			
			$user = wp_get_current_user();

			self::updateExistingUser( $user->ID, $user_response, $tokens );	
			
			// Below filter vars and the following filter allows plugin devs to acquire/filter info about Patron/user after the user returns from Patreon
			
			$filter_args = array(
				'user' => $user,
				'redirect' => $redirect,
				'user_response' => $user_response,
				'tokens' => $tokens,
			);
			
			do_action( 'patreon_action_after_wp_logged_user_is_updated', $filter_args );
			
			// Save this time when patron returned from a Patreon flow to use for deciding when to call the api in unlock actions
			update_user_meta( $user->ID, 'patreon_user_last_returned_from_any_flow', time() );
			
			wp_redirect( $redirect );
			exit;
						
		}
		
		////////////////////////////////////////////////
		// If we are here, User is not logged in. Go through login or creation procedure:
		////////////////////////////////////////////////
		
		$patreon_user_id = $user_response['data']['id'];
		
		// First see if any user was linked to this patreon user:
		
		global $wpdb;
		
		$prepared_sql = $wpdb->prepare(
			"SELECT * FROM " . $wpdb->usermeta . " WHERE meta_key = 'patreon_user_id' AND meta_value = %s",
			array( $patreon_user_id )
		);
		
		// Now get the result : 
	
		$patreon_linked_accounts = $wpdb->get_results( $prepared_sql, ARRAY_A );
		
		if ( count( $patreon_linked_accounts ) > 0 ) {
			
			////////////////////////////////////////////////
			// We have linked users. Get the last login dates for each. The reason we did it by taking ids and iterating them is to avoid querying both patreon ids and login dates at the same time and having to use a self join query on wp_usermeta
			////////////////////////////////////////////////
			
			$sort_logins = array();
			
			foreach ( $patreon_linked_accounts as $key => $value ) {
				
				$last_logged_in = get_user_meta( $patreon_linked_accounts[$key]['user_id'], 'patreon_last_logged_in', true );
		
				$sort_logins[ $patreon_linked_accounts[ $key ]['user_id'] ] = $last_logged_in;
				
			}
			
			// Sort by time, descending
			arsort( $sort_logins );
		
			// We got the last login dates of the accounts, sorted. The first one is the last account used.
				
			$user_id_to_log_in = key( $sort_logins );
		
			// Attempt logging in that user.
			
			$user = get_user_by( 'id', $user_id_to_log_in );

			if ( $login_with_patreon ) {

				if ( $admins_editors_login_with_patreon == false && array_key_exists( $user->user_login, $danger_user_list ) ) {

					/* dont log admin / editor in */
					wp_redirect( wp_login_url() . '?patreon_message=admin_login_with_patreon_disabled', '301' );
					exit;

				} else {

					/* log user into existing wordpress account with matching username */
					wp_set_current_user( $user->ID, $user->user_login );
					wp_set_auth_cookie( $user->ID );
					do_action( 'wp_login', $user->user_login, $user );
 
					// Import Patreon avatar for this user since it is a new user
				
					self::get_update_user_patreon_avatar( $user_response['data']['attributes']['thumb_url'], $user );
					
					/* update user meta data with patreon data */
					self::updateExistingUser( $user->ID, $user_response, $tokens );

					// Below filter vars and the following filter allows plugin devs to acquire/filter info about Patron/user after the user returns from Patreon
					
					$filter_args = array(
						'user' => $user,
						'redirect' => $redirect,
						'user_response' => $user_response,
						'tokens' => $tokens,
					);
					
					do_action( 'patreon_do_action_after_user_logged_in_via_patreon', $filter_args );
					

					// Save this time when patron returned from a Patreon flow to use for deciding when to call the api in unlock actions
					update_user_meta( $user->ID, 'patreon_user_last_returned_from_any_flow', time() );
						
					wp_redirect( $redirect );
					exit;
					
				}

			}
			else {
				
				wp_redirect( wp_login_url() . '?patreon_message=login_with_patreon_disabled', '301' );
				exit;
				
			}
			
		}
		
		// At this point lets do a check for existing email if the email is going to be imported:
		
		// Default email to random complex string so get_user_by search will fail if email doesnt come from Patreon. SHA256
		$check_user_email = 'F01F2C59D413DB4B63882B0F25EB5FDD621946A99F6A2D6C2F8D26C1D600584F';
		
		if ( isset( $user_response['data']['attributes']['is_email_verified'] ) AND $user_response['data']['attributes']['is_email_verified'] ) {
			$check_user_email = $user_response['data']['attributes']['email'];
		}		
		
		$user = get_user_by( 'email', $check_user_email );
		
		if ( $user != false ) {
			
			// A user with same Patreon email exists. This means that we cannot create this user with this email, but also we cannot link to this account since there may be WP installs which dont do email verification - could lead to identity spoofing
			
			// Give a message to the user to log in with the WP account and then log in with Patreon
			
			wp_redirect( wp_login_url() . '?patreon_message=email_exists_login_with_wp_first', '301' );
			exit;
			
		}
		
		// We are here, meaning that user was not logged in, and there were no linked accounts, no matching email. This means we will create a new user.
		
		
		$username = 'patreon_' . $patreon_user_id;
		$user 	  = get_user_by( 'login', $username );

		if ( $user == false ) {

			/* create wordpress user with provided username */
			
			$random_password = wp_generate_password( 64, false );
			$user_email 	 = '';
			
			// Import user email only if the email was verified

			if ( isset($user_response['data']['attributes']['is_email_verified']) AND $user_response['data']['attributes']['is_email_verified'] ) {
				$user_email = $user_response['data']['attributes']['email'];
			}
			
			$user_id = wp_create_user( $username, $random_password, $user_email );

			if ( $user_id ) {

				$user = get_user_by( 'id', $user_id );
				
				// Check and set user names:
				
				$display_name = $username;
				$first_name   = '';
				$last_name    = '';
				
				
				if ( isset( $user_response['data']['attributes']['full_name'] ) ) {
					$display_name = $user_response['data']['attributes']['full_name'];
				}		
				
				if ( isset( $user_response['data']['attributes']['first_name'] ) ) {
					
					update_user_meta( $user_id, 'first_name', $user_response['data']['attributes']['first_name'] );
					
					$first_name   = $user_response['data']['attributes']['first_name'];
					// Override display name with first name if its set
					$display_name = $user_response['data']['attributes']['first_name'];
					
				}
				
				if ( isset( $user_response['data']['attributes']['last_name'] ) ) {
					$last_name = $user_response['data']['attributes']['last_name'];					
				}
				
				$args = array(
					'ID'           => $user_id,
					'display_name' => $display_name,
					'first_name'   => $first_name,
					'last_name'    => $last_name,
				);
				
				wp_update_user( $args );
							
				// Import Patreon avatar for this user since it is a new user
				
				self::get_update_user_patreon_avatar( $user_response['data']['attributes']['thumb_url'], $user );
					
				
				wp_set_current_user( $user->ID, $user->data->user_login );
				wp_set_auth_cookie( $user->ID );
				do_action( 'wp_login', $user->data->user_login, $user );
				
				/* update user meta data with patreon data */
				self::updateExistingUser( $user->ID, $user_response, $tokens );	
				
				// Below filter vars and the following filter allows plugin devs to acquire/filter info about Patron/user after the user returns from Patreon
				
				$filter_args = array(
					'user' => $user,
					'redirect' => $redirect,
					'user_response' => $user_response,
					'tokens' => $tokens,
				);
				
				do_action( 'patreon_do_action_after_new_user_created_from_patreon_logged_in', $filter_args );
				
				// Save this time when patron returned from a Patreon flow to use for deciding when to call the api in unlock actions
				update_user_meta( $user->ID, 'patreon_user_last_returned_from_any_flow', time() );

				wp_redirect( $redirect );
				exit;	

			} else {
				
				/* wordpress account creation failed */
				
				$redirect = add_query_arg( 'patreon_message', 'patreon_could_not_create_wp_account', $redirect );
				wp_redirect( $redirect );
				exit;	
				
			}
		} else {
			
				/* We created this patreon user before. Update and log in.
			
				/* update user meta data with patreon data */
				wp_set_current_user( $user->ID, $user->data->user_login );
				wp_set_auth_cookie( $user->ID );
				do_action( 'wp_login', $user->data->user_login, $user );

				/* update user meta data with patreon data */
				self::updateExistingUser( $user->ID, $user_response, $tokens );
				
				// Below filter vars and the following filter allows plugin devs to acquire/filter info about Patron/user after the user returns from Patreon
				
				$filter_args = array(
					'user' => $user,
					'redirect' => $redirect,
					'user_response' => $user_response,
					'tokens' => $tokens,
				);
				
				do_action( 'patreon_do_action_after_existing_user_from_patreon_logged_in', $filter_args );				

				// Save this time when patron returned from a Patreon flow to use for deciding when to call the api in unlock actions
				update_user_meta( $user->ID, 'patreon_user_last_returned_from_any_flow', time() );

				
				wp_redirect( $redirect );
				exit;
				
		}
		
	}

	public static function getDangerUserList() {

		$args = array(
            'role__in'  =>  array( 'administrator','editor' ),
            'orderby'   => 'login',
            'order'     => 'ASC',
        );
 
	    $danger_users = get_users( $args );

	    $danger_user_list = array();

	    if ( !empty( $danger_users ) ) {

	        foreach( $danger_users as $danger_user ) {
	            $danger_user_list[$danger_user->data->user_login] = $danger_user;
	        }
	    }

	    return $danger_user_list;
		
	}
	
	public static function get_update_user_patreon_avatar( $patreon_image_url, $user = false ) {

		// Gets and saves a user's Patreon profile image from Patreon into WP media folder

		// Get user from current user if user object was not provided
		if ( $user == false ) {
			$user = wp_get_current_user();
		}
		
		// If still no user object, return false
		if ( $user->ID == 0 ) {
			return false;
		}
		
		// Abort if GD is not installed
		
		if ( !( extension_loaded( 'gd' ) AND function_exists( 'gd_info' ) ) ) {
			return false;
		}
		
		$patreon_image_data = wp_remote_get( $patreon_image_url );
		
		if ( is_wp_error( $patreon_image_data ) ) {
			return false;
		}		
		
		$headers = $patreon_image_data['headers'];
		
		// If mime type is not set, abort
		
		if ( !isset( $headers ) OR !isset( $headers['content-type'] ) ) {
			return false;
		}
		
		$mime_type = $headers['content-type'];
		
		$patreon_image = $patreon_image_data['body'];
		
		if ( $mime_type == 'image/png' ) {
			$extension = 'png';
		}
		if ( $mime_type == 'image/gif' ) {
			$extension = 'gif';
		}
		if ($mime_type =='image/jpeg') {
			$extension = 'jpg';
		}
		if ( $mime_type == 'image/bmp' ) {
			$extension = 'bmp';
		}
				
		// Get the existing avatar from user meta if it was saved:
		
		$user_patreon_avatar_path = get_user_meta( $user->ID, 'patreon-avatar-file', true );
		
		// Delete existing Patreon avatar
				
		if ( file_exists( $user_patreon_avatar_path ) ) {
			unlink( $user_patreon_avatar_path );
		}
		
		// Upload new one
		$uploaded = wp_upload_bits( 'patreon_avatar_' . $user->ID . '.' . $extension, null, $patreon_image );
		
		if ( is_wp_error( $uploaded ) ) {
			return false;	
		}
		
		// All went through.		
		
		// Remove the existing avatar metas
		
		delete_user_meta( $user->ID, 'patreon-avatar-url' );
		delete_user_meta( $user->ID, 'patreon-avatar-file' );
		
		// Add the Patreon avatar as meta to the user - this will false if adding meta fails, primary key if it succeeds
		
		// At this point, if earlier file deletion succeeded, the avatar should be named properly with patreon_avatar_ + user id + extension. If not, then it would get -1, -2 etc suffix after patreon_avatar + user id since WP would upload it as a new file to not override existing file
		
		if ( add_user_meta( $user->ID, 'patreon-avatar-url', $uploaded['url'] ) AND
			add_user_meta( $user->ID, 'patreon-avatar-file', $uploaded['file'] )
		) {
			return true;
		}
		
		return false;
		
	}

	public static function disconnect_account_from_patreon() {
		
		// Disconnects an account from Patreon.
		
		$user = wp_get_current_user();

		if ( current_user_can( 'manage_options' ) OR $user->ID == $_REQUEST['patreon_disconnect_user_id'] ) {
			
			// Delete all Patreon user meta for this WP user id here
			// User id to delete:
			
			$user_to_disconnect = get_user_by( 'ID', $_REQUEST['patreon_disconnect_user_id'] );
			
			if ( isset( $user_to_disconnect->ID ) ) {
			
				delete_user_meta( $user_to_disconnect->ID, 'patreon_refresh_token' );
				delete_user_meta( $user_to_disconnect->ID, 'patreon_access_token' );
				delete_user_meta( $user_to_disconnect->ID, 'patreon_user' );
				delete_user_meta( $user_to_disconnect->ID, 'patreon_user_id' );
				delete_user_meta( $user_to_disconnect->ID, 'patreon_last_logged_in' );
				delete_user_meta( $user_to_disconnect->ID, 'patreon_created' );
				delete_user_meta( $user_to_disconnect->ID, 'patreon_token_minted' );
				delete_user_meta( $user_to_disconnect->ID, 'patreon_token_expires_in' );
				delete_user_meta( $user_to_disconnect->ID, 'patreon-avatar-url' );
				delete_user_meta( $user_to_disconnect->ID, 'patreon-avatar-file' );
				delete_user_meta( $user_to_disconnect->ID, 'patreon_user' );
				delete_user_meta( $user_to_disconnect->ID, 'patreon_latest_patron_info' );
				delete_user_meta( $user_to_disconnect->ID, 'patreon_email' );
				delete_user_meta( $user_to_disconnect->ID, 'patreon_latest_patron_info_timestamp' );
				delete_user_meta( $user_to_disconnect->ID, 'patreon_user_details_last_updated' );
				
			}
			else {
				// Problem! No valid user id to disconnect or no valid user
				
				?>
				
					Ooops! Disconnect failed - could not find matching user account!
				
				<?php
				exit;
			}
			
			$login_flow_url = Patreon_Frontend::patreonMakeLoginLink( false, array( 'final_redirect_uri' => get_edit_profile_url( $user->ID ) ) );
			
			if ( !current_user_can( 'manage_options' ) ) {
			
				?>
					Disconnected! Now you can connect your site account to another Patreon account.
					<table class="form-table">
						<tr>
							<th><label for="patreon_user">Connect your site account to your Patreon account</label></th>
							<td>
								<button id="patreon_wordpress_connect_patreon_account" class="button button-primary button-large" patreon_login_url="<?php echo $login_flow_url; ?>" target="">Connect to Patreon</button><br />
							</td>
						</tr>
					</table>
				
				<?php
				
			}
			
			if ( current_user_can( 'manage_options' ) ) {
			
				?>
					Disconnected! Only the owner of this user account can reconnect it to his/her Patreon account.
		
				<?php
				
			}
			
		}
		else {
			echo 'Sorry, you must be an admin or owner of this account to disconnect it from Patreon';
		}
		
		exit;
	}
	
}