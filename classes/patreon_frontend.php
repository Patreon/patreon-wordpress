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

class Patreon_Frontend {

	function __construct() {
		add_action( 'login_form', array($this, 'showPatreonButton' ) );
	}

	public function showPatreonButton() {

		$client_id = get_option('patreon-client-id', false);

		$logo = PATREON_PLUGIN_URL . 'img/patreon-logo.png';

		if($client_id == false) {
			return '';
		}

		echo '
		<style type="text/css">
		.ptrn-button{margin-bottom:20px!important;background: #232D32;line-height:1;color: white;text-decoration: none;vertical-align: middle;padding: 10px;text-align: center;border-radius: 6px;font-size: 17px;}
		.ptrn-button:hover,.ptrn-button:active,.ptrn-button:focus {color:white;}
		.ptrn-button img {height:30px;}
		</style>';

		$href = 'https://www.patreon.com/oauth2/authorize?response_type=code&client_id='.$client_id.'&redirect_uri='.urlencode(site_url().'/patreon-authorization/');

		echo apply_filters('ptrn/login_button', '<a  href="'.$href.'" style="display:block;margin-bottom:4px;" class="ptrn-button" data-ptrn_nonce="' . wp_create_nonce( 'patreon-nonce' ).'">Connect with&nbsp;&nbsp; <img src="'.$logo.'"/></a>');
	}

}

?>