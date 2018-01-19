<?php


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Patreon_Wordpress {

	private static $Patreon_Routing;
	private static $Patreon_Frontend;
	private static $Patreon_Posts;
	private static $Patreon_Protect;
	private static $Patreon_Options;
	private static $Patron_Metabox;
	private static $Patreon_User_Profiles;

	function __construct() {

		include 'patreon_login.php';
		include 'patreon_routing.php';
		include 'patreon_frontend.php';
		include 'patreon_api.php';
		include 'patreon_oauth.php';
		include 'patreon_options.php';
		include 'patreon_metabox.php';
		include 'patreon_user_profiles.php';

		self::$Patreon_Routing = new Patreon_Routing;
		self::$Patreon_Frontend = new Patreon_Frontend;
		self::$Patreon_Options = new Patreon_Options;
		self::$Patron_Metabox = new Patron_Metabox;
		self::$Patreon_User_Profiles = new Patreon_User_Profiles;

		add_action('wp_head', array($this, 'updatePatreonUser') );
		add_action('init', array($this, 'checkPatreonCreatorID'));
		add_action('init', array($this, 'checkPatreonCreatorURL'));
		add_action('init', array($this, 'checkPatreonCreatorName'));
		add_action('init', 'Patreon_Login::checkTokenExpiration');

	}
	
	static function getPatreonUser($user) {

		/* get user meta data and query patreon api */
		$user_meta = get_user_meta($user->ID);
		if(isset($user_meta['patreon_access_token'][0])) {
			$api_client = new Patreon_API($user_meta['patreon_access_token'][0]);

			// Below is a code that caches user object for 60 seconds. This can be commented out depending on the response from Infrastructure team about contacting api to check for user on every page load
			/*
			$cache_key = 'patreon_user_'.$user->ID;
			$user = get_transient( $cache_key );
			if ( false === $user ) {
				$user = $api_client->fetch_user();
				set_transient( $cache_key, $user, 60);
			}
			*/
			// For now we are always getting user from APi fresh:
			$user = $api_client->fetch_user();
	
			return $user;
		}

		return false;

	}
	
	static function updatePatreonUser() {

		/* check if current user is loggedin, get ID */

		if(is_user_logged_in() == false) {
			return false;
		}

		$user = wp_get_current_user();
		if($user == false) {
			return false;
		}

		/* query Patreon API to get users patreon details */
		$user_response = self::getPatreonUser($user);

		if(isset($user_response['errors']) && is_array($user_response['errors'])) {

			foreach($user_response['errors'] as $error) {
				if($error['code'] == 1) {
					/* refresh users token if error 1 */

					$refresh_token = get_user_meta($user->ID, 'patreon_refresh_token', true);

					$oauth_client = new Patreon_Oauth;
					$tokens = $oauth_client->refresh_token($refresh_token, site_url().'/patreon-authorization/');

					update_user_meta($user->ID, 'patreon_refresh_token', $tokens['refresh_token']);
					update_user_meta($user->ID, 'patreon_access_token', $tokens['access_token']);

					$user_response = self::getPatreonUser($user);
				}
			}

		}

		if($user_response == false) {
			return false;
		}

		if(isset($user_response['data'])) {
			/* all the details you want to update on wordpress user account */
			update_user_meta($user->ID, 'patreon_user', $user_response['data']['attributes']['vanity']);
			update_user_meta($user->ID, 'patreon_created', $user_response['data']['attributes']['created']);
			update_user_meta($user->ID, 'user_firstname', $user_response['data']['attributes']['first_name']);
			update_user_meta($user->ID, 'user_lastname', $user_response['data']['attributes']['last_name']);
		}

	}
	
	public static function checkPatreonCreatorID() {
		
		// Check if creator id doesnt exist. Account for the case in which creator id was saved as empty by the Creator
		
		if(!get_option('patreon-creator-id', false) OR get_option('patreon-creator-id', false)=='') {	
			// Making sure access credentials are there to avoid fruitlessly contacting the api:
			
			if(get_option('patreon-client-id', false) 
				&& get_option('patreon-client-secret', false) 
				&& get_option('patreon-creators-access-token', false)
			) {
				
				// Credentials are in. Go.
				
				$creator_id = self::getPatreonCreatorID();
			}
			if(isset($creator_id)) {
				// Creator id acquired. Update.
				
				update_option( 'patreon-creator-id', $creator_id );
			}
		}
	}
	public static function checkPatreonCreatorURL() {
		
		// Check if creator url doesnt exist. 
		
		if(!get_option('patreon-creator-url', false) OR get_option('patreon-creator-url', false)=='') {	
			// Making sure access credentials are there to avoid fruitlessly contacting the api:
			
			if(get_option('patreon-client-id', false) 
				&& get_option('patreon-client-secret', false) 
				&& get_option('patreon-creators-access-token', false)
			) {
				
				// Credentials are in. Go.
				
				$creator_url = self::getPatreonCreatorURL();
			}
			if(isset($creator_url)) {
				// Creator id acquired. Update.
				
				update_option( 'patreon-creator-url', $creator_url );
			}
		}
		
	}
	public static function checkPatreonCreatorName() {
		
		// This function checks and saves creator's full name, name and surname. These are used in post locking interface
		
		if(!get_option('patreon-creator-full-name', false) OR get_option('patreon-creator-full-name', false)=='') {
			// Making sure access credentials are there to avoid fruitlessly contacting the api:
			
			if(get_option('patreon-client-id', false) && get_option('patreon-client-secret', false) && get_option('patreon-creators-access-token', false)) {
				
				// Credentials are in. Go.
				
				$creator_info = self::getPatreonCreatorInfo();
			}
			if(isset($creator_info['included'][0]['attributes']['full_name'])) {
				// Creator id acquired. Update.
				
				update_option( 'patreon-creator-full-name', $creator_info['included'][0]['attributes']['full_name'] );
			}
			if(isset($creator_info['included'][0]['attributes']['first_name'])) {
				// Creator id acquired. Update.
				
				update_option( 'patreon-creator-first-name', $creator_info['included'][0]['attributes']['first_name'] );
			}
			if(isset($creator_info['included'][0]['attributes']['last_name'])) {
				// Creator id acquired. Update.
				
				update_option( 'patreon-creator-last-name', $creator_info['included'][0]['attributes']['last_name'] );
			}
		}
	}
	
	public static function getPatreonCreatorInfo() {
	
		$api_client = new Patreon_API(get_option('patreon-creators-access-token', false));

        $user_response = $api_client->fetch_creator_info();

        if(empty($user_response)) {
        	return false;
        }

        if(isset($user_response['errors']) && is_array($user_response['errors'])) {

			foreach($user_response['errors'] as $error) {
				if($error['code'] == 1) {

					/* refresh creators token if error 1 */

					$refresh_token = get_option('patreon-creators-refresh-token', false);

					if($refresh_token == false) {
						return false;
					}

					$oauth_client = new Patreon_Oauth;
					$tokens = $oauth_client->refresh_token($refresh_token, site_url().'/patreon-authorization/');

					if(isset($tokens['refresh_token']) && isset($tokens['access_token'])) {
						update_option('patreon-creators-refresh-token', $tokens['refresh_token']);
						update_option('patreon-creators-access-token', $tokens['access_token']);
					}

					$user_response = $api_client->fetch_creator_info();
				}
			}

		}
		
		return $user_response;
	}
	
	public static function getPatreonCreatorID() {

		$creator_info = self::getPatreonCreatorInfo();

		if(isset($creator_info['included'][0]['id']))
		{
			return $creator_info['included'][0]['id'];
		}

        return false;

	}
	public static function getPatreonCreatorURL() {

		$creator_info = self::getPatreonCreatorInfo();

		if(isset($creator_info['included'][0]['attributes']['url'])) {
			return $creator_info['included'][0]['attributes']['url'];
		}

        return false;

	}
	
	public static function getUserPatronage() {

		if(is_user_logged_in() == false) {
			return false;
		}

		$user = wp_get_current_user();
		if($user == false) {
			return false;
		}

		$creator_id = get_option('patreon-creator-id', false);

		if($creator_id == false) {
			return false;
		}

		/* get current users meta data */
		$user_meta = get_user_meta($user->ID);

		$user_response = self::getPatreonUser($user);

		if($user_response == false) {
			return false;
		}

		$pledge = false;
		if (array_key_exists('included', $user_response)) {
			foreach ($user_response['included'] as $obj) {
				if ($obj["type"] == "pledge" && $obj["relationships"]["creator"]["data"]["id"] == $creator_id) {
					$pledge = $obj;
					break;
				}
			}
		}

		if(isset($pledge['attributes']['declined_since']) && !is_null($pledge['attributes']['declined_since'])) {
			do_action('ptrn/declined_since', $pledge, $pledge['attributes']['declined_since']);
			return false;
		}

		if($pledge != false) {
			return self::getUserPatronageLevel($pledge);
		}

		return false;

	}
	
	public static function getUserPatronageDuration($pledge) {

		$user_response = self::getPatreonUser($user);

		$patronage_age = 0;

	}
	
	public static function checkDeclinedPatronage($user) {
		
		if(!$user) {
			$user = wp_get_current_user();
		}

		$user_response = self::getPatreonUser($user);
		
		// If no user exists, the patronage cannot have been declined.
		if(!$user_response) {
			return false;
		}
		
		$creator_id = get_option('patreon-creator-id', false);

		$pledge = false;
		if (array_key_exists('included', $user_response)) {
			foreach ($user_response['included'] as $obj) {
				if ($obj["type"] == "pledge" && $obj["relationships"]["creator"]["data"]["id"] == $creator_id) {
					$pledge = $obj;
					break;
				}
			}
		}		
			
		if(isset($pledge['attributes']['declined_since']) && !is_null($pledge['attributes']['declined_since'])) {
			do_action('ptrn/declined_since', $pledge, $pledge['attributes']['declined_since']);
			return true;
		}
		return false;
	}
	
	public static function getUserPatronageLevel($pledge) {

		$patronage_level = 0;

		if(isset($pledge['attributes']['amount_cents'])) {
			$patronage_level = $pledge['attributes']['amount_cents'];
		}

		return $patronage_level;

	}
	
	public static function isPatron() {

		$user_patronage = self::getUserPatronage();

		if(is_numeric($user_patronage) && $user_patronage > 0) {
			return true;
		}

		return false;

	}

}

?>
