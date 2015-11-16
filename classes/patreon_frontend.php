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
		add_shortcode( 'patreon_content', array($this, 'embedPatreonContent') );
	}

	public function showPatreonButton() {

		$logo_img = PATREON_PLUGIN_URL . 'img/patreon-logo.png';

		$client_id = get_option('patreon-client-id', false);

		if($client_id == false) {
			return '';
		}

		$href = 'https://www.patreon.com/oauth2/authorize?response_type=code&client_id='.$client_id.'&redirect_uri='.urlencode(site_url().'/patreon-authorization/');

		/* inline styles, for shame */
		echo '
		<style type="text/css">
		.ptrn-button{display:block;margin-bottom:20px!important;background: #232D32;line-height:1;color: white;text-decoration: none;vertical-align: middle;padding: 10px;text-align: center;border-radius: 6px;font-size: 17px;}
		.ptrn-button:hover,.ptrn-button:active,.ptrn-button:focus {color:white;}
		.ptrn-button img {height:30px;}
		</style>';

		echo apply_filters('ptrn/login_button', '<a href="'.$href.'" class="ptrn-button" data-ptrn_nonce="' . wp_create_nonce( 'patreon-nonce' ).'">Connect with&nbsp;&nbsp; <img src="'.$logo_img.'"/></a>');
	}

	public function displayPatreonCampaignBanner() {

		/* patreon banner when user patronage not high enough */
		/* TODO: get marketing collateral */
		echo '<img src="http://placehold.it/500x150?text=PATREON MARKETING COLLATERAL"/>';

	}

	public function embedPatreonContent($args) {

		/* example shortcode [patreon_content slug="test-example"]


		/* check if shortcode has slug parameter */
		if(isset($args['slug'])) {

			/* get patreon-content post with matching url slug */
			$patreon_content = get_page_by_path($args['slug'],OBJECT,'patreon-content');
			
			$user = wp_get_current_user();
			if($user == false) {
				return false;
			}

			/* check users patronage level */
			/* TODO: get users patronage level */
			$patronage_level = Patreon_Wordpress::checkUserPatronage($user);

			if($patreon_content != false && $patronage_level != false) {
				return $patreon_content->post_content;
			}

			return self::displayPatreonCampaignBanner();

		}

	}

}

?>