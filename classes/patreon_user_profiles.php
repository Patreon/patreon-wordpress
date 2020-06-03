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
					?>
						<div id="patreon_wordpress_user_profile_account_connection_wrapper">
							<table class="form-table">
								<tr>
									<th><label for="patreon_user">Connect your site account to your Patreon account</label></th>
									<td>
										<button id="patreon_wordpress_connect_patreon_account" class="button button-primary button-large" target="">Connect to Patreon</button><br />
									</td>
								</tr>
							</table>
						</div>
					<?php
				}
				
				if ( $linked_patreon_account != '' ) {
					// Admins can disconnect someone's account as well as the user himself/herself
					?>
						<div id="patreon_wordpress_user_profile_account_connection_wrapper">
							<table class="form-table">
								<tr>
									<th><label id="patreon_wordpress_disconnect_patreon_account_label" for="patreon_user">Disconnect your site account from your  Patreon account</label></th>
									<td id="patreon_wordpress_disconnect_patreon_account_content">
										<button id="patreon_wordpress_disconnect_patreon_account" patreon_disconnect_user_id="<?php echo $user_id; ?>" class="button button-primary button-large" target="">Disconnect from Patreon</button><br />
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