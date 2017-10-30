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
	private static $Patreon_Shortcodes;
	private static $Patreon_Welcome;

	function __construct() {
		
		include 'patreon_welcome_page.php';
		include 'patreon_login.php';
		include 'patreon_routing.php';
		include 'patreon_frontend.php';
		include 'patreon_posts.php';
		include 'patreon_api.php';
		include 'patreon_oauth.php';
		include 'patreon_protect.php';
		include 'patreon_options.php';
		include 'patreon_metabox.php';
		include 'patreon_user_profiles.php';
		include 'patreon_shortcodes.php';

		self::$Patreon_Welcome = new Patreon_Welcome;
		self::$Patreon_Routing = new Patreon_Routing;
		self::$Patreon_Frontend = new Patreon_Frontend;
		self::$Patreon_Posts = new Patreon_Posts;
		self::$Patreon_Protect = new Patreon_Protect;
		self::$Patreon_Options = new Patreon_Options;
		self::$Patron_Metabox = new Patron_Metabox;
		self::$Patreon_User_Profiles = new Patreon_User_Profiles;
		self::$Patreon_Shortcodes = new Patreon_Shortcodes;

		add_action('wp_head', array($this, 'updatePatreonUser') );
	}

	static function getPatreonUser($user) {

		/* get user meta data and query patreon api */
		$user_meta = get_user_meta($user->ID);
		if(isset($user_meta['patreon_access_token'][0])) {
			$api_client = new Patreon_API($user_meta['patreon_access_token'][0]);

			$cache_key = 'patreon_user_'.$user->ID;
			$user = get_transient( $cache_key );
			if ( false === $user ) {
				$user = $api_client->fetch_user();
				set_transient( $cache_key, $user, 60);
			}

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

	public static function getPatreonCreatorID() {

		$api_client = new Patreon_API(get_option('patreon-creators-access-token', false));

        $user_response = $api_client->fetch_campaign_and_patrons();

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

					$user_response = $api_client->fetch_campaign_and_patrons();
				}
			}

		}

        $creator_id = false;

        if (array_key_exists('data', $user_response)) {
            foreach ($user_response['included'] as $obj) {
                if ($obj["type"] == "user") {
                    $creator_id = $obj['id'];
                    break;
                }
            }
        }

        return $creator_id;

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

		if(isset($user_meta['patreon_user_exception'][0]) && $user_meta['patreon_user_exception'][0] == true) {
			return PHP_INT_MAX;
		}

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
