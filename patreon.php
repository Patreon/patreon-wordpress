<?php 

/*
Plugin Name: Patreon
Plugin URI: 
Description: Stay close with the Artists & Creators you're supporting
Version: 1.0
Author: Ben Parry
Author URI: http://uiux.me
*/


if ( ! defined( 'ABSPATH' ) ) exit;

include 'admin/patreon-options-page.php';

require_once("lib/API.php");
require_once("lib/OAuth.php");

use Patreon\API;
use Patreon\OAuth;

define("PATREON_PLUGIN_URL", plugin_dir_url( __FILE__ ) );

class Patreon {

	private static $Patreon_Routing;
	private static $Patreon_Frontend;

	function __construct() {

		include 'classes/patreon_routing.php';
		include 'classes/patreon_login.php';
		include 'classes/patreon_frontend.php';

		self::$Patreon_Routing = new Patreon_Routing;
		self::$Patreon_Frontend = new Patreon_Frontend;

		add_action('wp_head', array($this, 'updatePatreonUserStats') );

	}

	function getPatreonUser($user) {

		$user_meta = get_user_meta($user->ID);
		if(isset($user_meta['patreon_access_token'][0])) {
			$api_client = new Patreon\API($user_meta['patreon_access_token'][0]);
			$user = $api_client->fetch_user();
			return $user;
		}

		return false;

	}

	function updatePatreonUserStats() {

		$user = wp_get_current_user();
		if($user == false) {
			return false;
		}

		$user = self::getPatreonUser($user);

	}

}

$Patreon = new Patreon;

?>