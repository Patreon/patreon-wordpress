<?php 

/*
Plugin Name: Patreon
Plugin URI: 
Description: Stay close with the Artists & Creators you're supporting
Version: 1.0
Author: Ben Parry
Author URI: http://uiux.me
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Patreon_Login {

	public static function createUserFromPatreon($user_response, $tokens) {

		$email = $user_response['data']['attributes']['email'];

		$user = get_user_by( 'email', $email );

		if($user == false) {

			/* create wordpress user if no account exists with provided email address */
			$random_password = wp_generate_password( 12, false );
			$user_id = wp_create_user( $email, $random_password, $email );

			if($user_id) {

				/* update user meta data with patreon data */
				update_user_meta($user_id, 'patreon_refresh_token', $tokens['refresh_token']);
				update_user_meta($user_id, 'patreon_access_token', $tokens['access_token']);
				update_user_meta($user_id, 'patreon_user', $user_response['data']['attributes']['vanity']);
				update_user_meta($user_id, 'patreon_created', $user_response['data']['attributes']['created']);
				update_user_meta($user_id, 'user_firstname', $user_response['data']['attributes']['first_name']);
				update_user_meta($user_id, 'user_lastname', $user_response['data']['attributes']['last_name']);
				update_user_meta($user_id, 'patreon_token_minted', microtime());
				
			} else {
				/* wordpress account creation failed #HANDLE_ERROR */
			}

		} else {

			/* log user into existing wordpress account with matching email address */
			wp_set_current_user( $user->ID, $user->user_login );
			wp_set_auth_cookie( $user->ID );
			do_action( 'wp_login', $user->user_login );

			/* update user meta data with patreon data */
			update_user_meta($user->ID, 'patreon_refresh_token', $tokens['refresh_token']);
			update_user_meta($user->ID, 'patreon_access_token', $tokens['access_token']);
			update_user_meta($user->ID, 'patreon_user', $user_response['data']['attributes']['vanity']);
			update_user_meta($user->ID, 'patreon_created', $user_response['data']['attributes']['created']);
			update_user_meta($user->ID, 'user_firstname', $user_response['data']['attributes']['first_name']);
			update_user_meta($user->ID, 'user_lastname', $user_response['data']['attributes']['last_name']);
		}

	}

}


?>