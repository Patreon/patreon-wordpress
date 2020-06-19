<?php

// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
	die;
}

class Patreon_User_Profiles {

	function __construct() {
		
		add_action( 'show_user_profile', array( $this, 'patreon_user_profile_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'patreon_user_profile_fields' ) );
		add_action( 'personal_options_update', array( $this, 'save_patreon_user_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_patreon_user_profile_fields' ) );
		add_action( 'user_profile_update_errors', array( $this, 'prevent_email_change'), 10, 3 );
		
	}

	function patreon_user_profile_fields( $user ) {

		if( current_user_can( 'manage_options' ) ) {
			
			?>

			<br />

			<h3><?php _e( "Patreon Profile", "blank" ); ?></h3>

			<table class="form-table">
				<tr>
					<th><label for="patreon_user"><?php _e( "Patreon User" ); ?></label></th>
					<td>
						<input type="text" name="patreon_user" id="patreon_user" disabled value="<?php echo esc_attr( get_the_author_meta( 'patreon_user', $user->ID ) ); ?>" class="regular-text" /><br />
					</td>
				</tr>
				<tr>
					<th><label for="patreon_created"><?php _e( "Patreon Created" ); ?></label></th>
					<td>
						<input type="text" name="patreon_created" id="patreon_created" disabled value="<?php echo esc_attr( get_the_author_meta( 'patreon_created', $user->ID ) ); ?>" class="regular-text" /><br />
					</td>
				</tr>
				<tr>
					<th><label for="user_firstname"><?php _e( "Patreon First name" ); ?></label></th>
					<td>
						<input type="text" name="user_firstname" id="user_firstname" disabled value="<?php echo esc_attr( get_the_author_meta( 'user_firstname', $user->ID ) ); ?>" class="regular-text" /><br />
					</td>
				</tr>
				<tr>
					<th><label for="user_lastname"><?php _e( "Patreon Last name" ); ?></label></th>
					<td>
						<input type="text" name="user_lastname" id="user_lastname" disabled value="<?php echo esc_attr( get_the_author_meta( 'user_lastname', $user->ID ) ); ?>" class="regular-text" /><br />
					</td>
				</tr>
			</table>

			<?php

		}
		
		// Add disconnect field 
		global $user_id;
		
		if ( current_user_can( 'manage_options' ) OR ( isset( $user_id ) AND ( get_current_user_id() == $user_id  ) ) ) {
			
			// This is either an admin in profile page or a user who is viewing his/her own profile page. Go ahead.
			
			?>

			<br />

			<h3><?php _e( "Patreon account", "blank" ); ?></h3>
		
			<?php
			
				// Check if this is a connected account.
				
				$linked_patreon_account = get_user_meta( $user_id, 'patreon_user_id', true );
				
				if ( $linked_patreon_account == '' AND ( isset( $user_id ) AND ( get_current_user_id() == $user_id  ) ) ) {
					// Only show this if the current user is the owner of the profile - an admin cant link a user's Patreon account for that user
					
					$user = wp_get_current_user();
					
					$login_flow_url = Patreon_Frontend::patreonMakeLoginLink( false, array( 'final_redirect_uri' => get_edit_profile_url( $user->ID ) ) );
			
					
					?>
						<div id="patreon_wordpress_user_profile_account_connection_wrapper">
							<table class="form-table">
								<tr>
									<th><label for="patreon_user">Connect your site account to your Patreon account</label></th>
									<td>
										<button id="patreon_wordpress_connect_patreon_account" class="button button-primary button-large" patreon_login_url="<?php echo $login_flow_url; ?>" target="">Connect to Patreon</button><br />
									</td>
								</tr>
							</table>
						</div>
					<?php
				}
				
				if ( $linked_patreon_account != '' ) {
					// Admins can disconnect someone's account as well as the user himself/herself
					
					// Set the warning note:
					
					$disconnect_warning = 'Note: If you log out of the website after disconnecting your Patreon account, you will have to use your site username/password in order to login. Only after that you can connect your account to Patreon again.';
					
					$disconnect_label = 'Disconnect your site account from your Patreon account';
					
					// If user is an admin and not the owner of this account, set the admin version of the warning
					
					if ( current_user_can( 'manage_options' ) AND !( isset( $user_id ) AND ( get_current_user_id() == $user_id  ) ) ) {
					
						$disconnect_warning = 'Note: If the user logs out of the website after you disconnect the linked Patreon account, the user will have to use his/her site username/password in order to login. Only after that the account can be reconnected to a Patreon account.';
						
						$disconnect_label = 'Disconnect this site account from linked Patreon account';
					
					}
					
					?>
						<div id="patreon_wordpress_user_profile_account_connection_wrapper">
							<table class="form-table">
								<tr>
									<th><label id="patreon_wordpress_disconnect_patreon_account_label" for="patreon_user"><?php echo $disconnect_label; ?></label></th>
									<td id="patreon_wordpress_disconnect_patreon_account_content">
										<button id="patreon_wordpress_disconnect_patreon_account" patreon_disconnect_user_id="<?php echo $user_id; ?>" class="button button-primary button-large" target="">Disconnect from Patreon</button><br /><br /><?php echo $disconnect_warning; ?>
									</td>
								</tr>
							</table>
						</div>
					<?php
				}
		
		}
		
	}

	function save_patreon_user_profile_fields( $user_id ) {

		if ( !current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		// if ( is_email( $_POST['patreon_email'] ) ) {

			//update_user_meta( $user_id, 'patreon_email', $_POST['patreon_email'] );

		// }

		// update_user_meta( $user_id, 'patreon_user', $_POST['patreon_user'] );
		// update_user_meta( $user_id, 'patreon_created', $_POST['patreon_created'] );
		// update_user_meta( $user_id, 'user_firstname', $_POST['province'] );
		// update_user_meta( $user_id, 'user_lastname', $_POST['postalcode'] );
		
		
	}

	function prevent_email_change( $errors, $update, $user ) {
		
		if ( $user AND isset ( $user->ID ) ) {
			
			$old = get_user_by( 'id', $user->ID );

			if( $user->user_email != $old->user_email   && ( !current_user_can( 'create_users' ) ) ) {
				$user->user_email = $old->user_email;
			}
			
		}
		
	}
	
}