<?php


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Patreon_Login {

	public static function updateExistingUser($user_id, $user_response, $tokens) {

		/* update user meta data with patreon data */
		update_user_meta($user_id, 'patreon_refresh_token', $tokens['refresh_token']);
		update_user_meta($user_id, 'patreon_access_token', $tokens['access_token']);
		update_user_meta($user_id, 'patreon_user', $user_response['data']['attributes']['vanity']);
		update_user_meta($user_id, 'patreon_created', $user_response['data']['attributes']['created']);
		update_user_meta($user_id, 'user_firstname', $user_response['data']['attributes']['first_name']);
		update_user_meta($user_id, 'user_lastname', $user_response['data']['attributes']['last_name']);
		update_user_meta($user_id, 'patreon_token_minted', microtime());

	}

	public static function updateLoggedInUser($user_response, $tokens, $redirect = false) {

		$user = wp_get_current_user();

		if(0 == $user->ID) {
		} else {
			/* update user meta data with patreon data */
			self::updateExistingUser($user->ID, $user_response, $tokens);
		}

		if($redirect == false || is_null($redirect) ) {
			wp_redirect(home_url());
			exit;
		} else {
			wp_redirect( get_permalink($redirect) );
			exit;
		}

	}

	public static function createUserFromPatreon($user_response, $tokens, $redirect = false) {

		global $wpdb;

		$login_with_patreon = get_option('patreon-enable-login-with-patreon', false);
		$admins_editors_login_with_patreon = get_option('patreon-enable-allow-admins-login-with-patreon', false);

		$email = $user_response['data']['attributes']['email'];

		//use email as login name & do bunch of stuff to it
		$username = $email;
		$namesplosion = explode('@', $username, 2);
		$firstchunk = $namesplosion[0];
		$username = $firstchunk . base_convert(password_hash($username.time(),PASSWORD_BCRYPT), 8, 32);

		//if login with patreon is enabled
		if($login_with_patreon == false ) {

			if(username_exists($username)) {

				$suffix = $wpdb->get_var( $wpdb->prepare(
					"SELECT 1 + SUBSTR(user_login, %d) FROM $wpdb->users WHERE user_login REGEXP %s ORDER BY 1 DESC LIMIT 1",
					strlen( $username ) + 2, '^' . $username . '(\.[0-9]+)?$' ) );

				if( !empty( $suffix ) ) {
					$username .= ".{$suffix}";
				}

			}

		}

		$user = get_user_by( 'email', $email );

		if($user == false) {

			/* create wordpress user if no account exists with provided email address */
			$random_password = wp_generate_password( 64, false );
			$user_id = wp_create_user( $username, $random_password, $email );

			if($user_id) {

				$user = get_user_by( 'id', $user_id );

				wp_set_current_user( $user->ID, $user->data->user_login );
				wp_set_auth_cookie( $user->ID );
				do_action( 'wp_login', $user->data->user_login, $user );



				/* update user meta data with patreon data */
				self::updateExistingUser($user->ID, $user_response, $tokens);

			} else {
				/* wordpress account creation failed #HANDLE_ERROR */
			}

		} else {

			$danger_user_list = Patreon_Login::getDangerUserList();

			if($login_with_patreon) {

				if($admins_editors_login_with_patreon == false && array_key_exists($user->user_login, $danger_user_list) ) {

					/* dont log admin / editor in */
					wp_redirect( wp_login_url().'?patreon-msg=login_with_wordpress', '301' );
					exit;

				} else {

					/* log user into existing wordpress account with matching email address */
					wp_set_current_user( $user->ID, $user->user_login );
					wp_set_auth_cookie( $user->ID );
					do_action( 'wp_login', $user->user_login, $user);
				}

			}

			/* update user meta data with patreon data */
			self::updateExistingUser($user->ID, $user_response, $tokens);

		}

		if($login_with_patreon) {

			if($redirect == false || is_null($redirect) ) {
				wp_redirect(home_url());
				exit;
			}

			wp_redirect( get_permalink($redirect) );
			exit;

		} else {
			wp_redirect( wp_login_url().'?patreon-msg=login_with_patreon', '301' );
			exit;
		}

	}

	public static function getDangerUserList() {

		$args = array(
            'role__in'  =>  array('administrator','editor'),
            'orderby'   => 'login',
            'order'     => 'ASC',
        );

	    $danger_users = get_users($args);

	    $danger_user_list = array();

	    if(!empty($danger_users)) {

	        foreach($danger_users as $danger_user) {
	            $danger_user_list[$danger_user->data->user_login] = $danger_user;
	        }
	    }

	    return $danger_user_list;
	}

}


?>
