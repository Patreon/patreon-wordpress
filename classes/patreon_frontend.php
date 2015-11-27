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
		add_filter( 'the_content', array($this, 'protectContentFromUsers') );
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
		.patreon-msg {-webkit-border-radius: 6px;-moz-border-radius: 6px;-ms-border-radius: 6px;-o-border-radius: 6px;border-radius: 6px;padding:8px;margin-bottom:20px!important;display:block;border:1px solid #E6461A;background-color:#484848;color:#ffffff;}
		</style>';

		if(isset($_REQUEST['patreon-msg']) && $_REQUEST['patreon-msg'] == 'login_with_patreon') {
			echo '<p class="patreon-msg">You can now login with your wordpress username/password.</p>';
		} else {
			echo apply_filters('ptrn/login_button', '<a href="'.$href.'" class="ptrn-button" data-ptrn_nonce="' . wp_create_nonce( 'patreon-nonce' ).'">Connect with&nbsp;&nbsp; <img src="'.$logo_img.'"/></a>');
		}
		
	}

	public function displayPatreonCampaignBanner() {

		/* patreon banner when user patronage not high enough */
		/* TODO: get marketing collateral */
		return '<img src="http://placehold.it/500x150?text=PATREON MARKETING COLLATERAL"/>';

	}

	public function embedPatreonContent($args) {

		/* example shortcode [patreon_content slug="test-example"]

		/* check if shortcode has slug parameter */
		if(isset($args['slug'])) {

			/* get patreon-content post with matching url slug */
			$patreon_content = get_page_by_path($args['slug'],OBJECT,'patreon-content');

			if($patreon_content == false) {
				return 'Patreon content not found.';
			}

			$patreon_level = get_post_meta( $patreon_content->ID, 'patreon-level', true );

			if($patreon_level == 0) {
				return $patreon_content->post_content;
			}

			$user_patronage = Patreon_Wordpress::getUserPatronage();

			if($user_patronage != false) {

				if(is_numeric($patreon_level) && $user_patronage >= ($patreon_level*100) ) {
					return $patreon_content->post_content;
				}

			}

			if(isset($args['youtube_id']) && isset($args['youtube_width']) && is_numeric($args['youtube_width']) && isset($args['youtube_height']) && is_numeric($args['youtube_height']) ) {
				return '<iframe width="'.$args['youtube_width'].'" height="'.$args['youtube_height'].'" src="https://www.youtube.com/embed/'.$args['youtube_id'].'?rel=0&amp;controls=0&amp;showinfo=0" frameborder="0" allowfullscreen></iframe>';
			} else {
				return self::displayPatreonCampaignBanner();
			}

		}

	}

	function protectContentFromUsers($content) {

		global $post;

		if(is_singular('patreon-content') && get_post_type() == 'patreon-content') {

			$patreon_level = get_post_meta( $post->ID, 'patreon-level', true );

			if($patreon_level == 0) {
				return $content;
			}

			$user_patronage = Patreon_Wordpress::getUserPatronage();

			if($user_patronage != false) {

				if(empty($patreon_level) || $user_patronage < ($patreon_level*100) ) {
					$content = self::displayPatreonCampaignBanner();
				}
				
			}

		} else if (is_singular() && get_post_type() == 'post') {

			$patreon_level = get_post_meta( $post->ID, 'patreon-level', true );

			if($patreon_level == 0) {
				return $content;
			}

			$user_patronage = Patreon_Wordpress::getUserPatronage();

			if($user_patronage == false || $user_patronage < ($patreon_level*100)) {
				$content = self::displayPatreonCampaignBanner();
			}

		}

		return $content;

	}

}

?>