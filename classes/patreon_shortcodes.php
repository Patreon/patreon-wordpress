<?php


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Patreon_Shortcodes {

	function __construct() {

		add_shortcode( 'patreon_content', array($this, 'embedPatreonContent') );
		add_shortcode( 'patreon_register_button', array($this, 'renderPatreonButton') );
		add_shortcode( 'patrons_name', array($this, 'renderPatronsName') );
		add_shortcode( 'personal_message', array($this, 'renderPersonalMessage') );
		add_shortcode( 'patrons_only', array($this, 'patronsOnlyContent') );

	}

	public function patronsOnlyContent($args, $content = '') {

		$user_patronage = Patreon_Wordpress::getUserPatronage();

		$min_level = 0;
		if(isset($args['min_level']) && is_numeric($args['min_level'])) {
			$min_level = $args['min_level'];
		}

		if($user_patronage == false || $user_patronage < ($min_level*100)) {

			/* check if shortcode has slug parameter */
			if(isset($args['slug'])) {

				/* get patreon-content post with matching url slug */
				$patreon_content = get_page_by_path($args['slug'],OBJECT,'patreon-content');

				if($patreon_content == false) {
					$content = 'Patreon content not found.';
				} else {
					$content = Patreon_Frontend::returnPatreonEmbeddedContent($patreon_content->post_content);
				}

			} else {
				$content = Patreon_Frontend::displayPatreonCampaignBanner($min_level);
			}

		}

		return $content;

	}

	public function renderPersonalMessage($args) {

		$before = '';
		if(isset($args['before_name'])) {
			$before = $args['before_name'];
		}

		$after = '';
		if(isset($args['after_name'])) {
			$after = $args['after_name'];
		}

		$patrons_name = '';

		if(is_user_logged_in()) {

			$user = wp_get_current_user();
			$patrons_name = get_user_meta($user->ID, 'user_firstname', true);

			if($patrons_name) {
				$patrons_name = ucfirst($patrons_name);
			}

			return $before . $patrons_name . $after;

		}

		return '';

	}

	public function renderPatronsName() {

		if(is_user_logged_in()) {
			$user = wp_get_current_user();
			$patrons_name = get_user_meta($user->ID, 'user_firstname', true);

			if($patrons_name) {
				return ucfirst($patrons_name);
			}

		}

		return '';

	}

	public function renderPatreonButton() {

		global $post;

		$client_id = get_option('patreon-client-id', false);

		if($client_id == false) {
			return '';
		}

		$redirect_uri = site_url().'/patreon-authorization/';

		$href = 'https://www.patreon.com/oauth2/authorize?response_type=code&client_id='.$client_id.'&redirect_uri='.urlencode($redirect_uri);

		$button_html = '<a href="'.$href.'" class="ptrn-branded-button ptrn-login" data-ptrn_nonce="' . wp_create_nonce( 'patreon-nonce' ).'"><img class="logo" src="'.PATREON_PLUGIN_ASSETS.'/img/patreon-logomark-on-coral.svg" alt=""> Login with Patreon</a>';

		return $button_html;

	}

	public function embedPatreonContent($args) {

		/* check if shortcode has slug parameter */
		if(isset($args['slug'])) {

			if(isset($args['username'])) {

				$current_user = wp_get_current_user();

				if ( !($current_user instanceof WP_User) ) {
					// user not logged in, lets pretend the content doesn't exist
					return 'Patreon content not found.';
				}

				if($current_user->user_login != $args['username']) {
					// this content is not for this user
					return 'This content is exclusive.';
				}

			}

			/* get patreon-content post with matching url slug */
			$patreon_content = get_page_by_path($args['slug'],OBJECT,'patreon-content');

			if($patreon_content == false) {
				return 'Patreon content not found.';
			}

			if(current_user_can('manage_options')) {
				return Patreon_Frontend::returnPatreonEmbeddedContent($patreon_content->post_content);
			}

			$patreon_level = get_post_meta( $patreon_content->ID, 'patreon-level', true );

			if($patreon_level == 0) {
				return Patreon_Frontend::returnPatreonEmbeddedContent($patreon_content->post_content);
			}

			$user_patronage = Patreon_Wordpress::getUserPatronage();

			if($user_patronage != false) {

				if(is_numeric($patreon_level) && $user_patronage >= ($patreon_level*100) ) {

					return Patreon_Frontend::returnPatreonEmbeddedContent($patreon_content->post_content);

				}

			}

			$embed_post = false;
			if(isset($args['paywall_embed_post_slug']) && isset($args['paywall_embed_post_type'])) {

				$post_slug = $args['paywall_embed_post_slug'];
				$post_type = $args['paywall_embed_post_type'];
				$post = get_page_by_path($post_slug, OBJECT, $post_type);

				if(isset($post->ID)) {
					$embed_post = Patreon_Frontend::returnPatreonEmbeddedContent($post->post_content);
				} else {
					$embed_post = false;
				}

			}

			$embed_code = false;
			if(isset($args['paywall_embed_url'])) {
				$embed_code = wp_oembed_get( $args['paywall_embed_url']);
			}

			$image_url = false;
			if(isset($args['paywall_image_url'])) {
				$image_url = '<img src="'.$args['paywall_image_url'].'"/>';
			}

			if($embed_post) {
				return $embed_post . '<br/>' . Patreon_Frontend::displayPatreonCampaignBanner($patreon_level);
			} if($embed_code) {
				return $embed_code . '<br/>' . Patreon_Frontend::displayPatreonCampaignBanner($patreon_level);
			} else if ($image_url) {
				return $image_url . '<br/>' . Patreon_Frontend::displayPatreonCampaignBanner($patreon_level);
			} else {
				return Patreon_Frontend::displayPatreonCampaignBanner($patreon_level);
			}

		}

	}

}


?>
