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

class Patreon_Wordpress {

	private static $Patreon_Routing;
	private static $Patreon_Frontend;
	private static $Patreon_Posts;

	function __construct() {

		include 'patreon_oauth.php';
		include 'patreon_api.php';
		include 'patreon_login.php';
		include 'patreon_routing.php';
		include 'patreon_frontend.php';
		include 'patreon_posts.php';

		self::$Patreon_Routing = new Patreon_Routing;
		self::$Patreon_Frontend = new Patreon_Frontend;
		self::$Patreon_Posts = new Patreon_Posts;

		add_action('wp_head', array($this, 'updatePatreonUser') );

	}

	static function getPatreonUser($user) {

		/* get user meta data and query patreon api */
		$user_meta = get_user_meta($user->ID);
		if(isset($user_meta['patreon_access_token'][0])) {
			$api_client = new Patreon_API($user_meta['patreon_access_token'][0]);
			$user = $api_client->fetch_user();
			return $user;
		}

		return false;

	}

	static function updatePatreonUser() {

		/* check if current user is loggedin, get ID */
		$user = wp_get_current_user();
		if($user == false) {
			return false;
		}

		/* query Patreon API to get users patreon details */
		$user_reponse = self::getPatreonUser($user);
		if($user_reponse == false) {
			return false;
		}

		/* all the details you want to update on wordpress user account */
		update_user_meta($user->ID, 'patreon_user', $user_reponse['data']['attributes']['vanity']);
		update_user_meta($user->ID, 'patreon_created', $user_reponse['data']['attributes']['created']);
		update_user_meta($user->ID, 'user_firstname', $user_reponse['data']['attributes']['first_name']);
		update_user_meta($user->ID, 'user_lastname', $user_reponse['data']['attributes']['last_name']);

	}

	public static function checkUserPatronage($user) {

		/* get current users meta data */
		$user_meta = get_user_meta($user->ID);

		$user_patronage = array(
			'patreon_user'		=> $user_meta['patreon_user'],
			'patreon_created'	=> $user_meta['patreon_created'],
			);

		/* check users patronage level and return boolean for content security */
		/* TODO */
		return false;

	}

}

?>